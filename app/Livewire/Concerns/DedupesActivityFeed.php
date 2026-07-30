<?php

namespace App\Livewire\Concerns;

use App\Models\ActivityLog;

/**
 * Every auto-generated cross-module record (Asset->Risk cascade, SDM->Risk
 * cascade, and now RiskEscalationResponseService) writes TWO activity_logs
 * rows for the same "created" event on purpose: LogsActivity's automatic
 * raw-attribute audit entry, plus an explicit human-readable bridge note
 * explaining *why*. Both are worth keeping in the database (one is the
 * audit trail, one is the explanation), but showing both in a "recent
 * activity" feed reads as a duplicate — same loggable, same "dibuat"
 * label, same title — since the feed only ever renders the loggable's
 * current title, not the log row's own content.
 *
 * This collapses that specific case: for entries sharing the same
 * loggable + action_type + minute (a same-request pair, not two separate
 * historical events), prefer the one carrying a bridge_note since it's the
 * more informative line for a human skimming the feed.
 */
trait DedupesActivityFeed
{
    protected function dedupedRecentActivity(string $loggableType, int $limit = 5)
    {
        $raw = ActivityLog::with(['user', 'loggable'])
            ->where('loggable_type', $loggableType)
            ->latest('performed_at')
            ->limit($limit * 4)
            ->get();

        return $raw
            ->groupBy(fn (ActivityLog $log) => $log->loggable_id . '|' . $log->action_type . '|' . $log->performed_at->format('Y-m-d H:i'))
            ->map(fn ($group) => $group->first(fn (ActivityLog $l) => isset($l->new_values['bridge_note'])) ?? $group->first())
            ->sortByDesc(fn (ActivityLog $log) => $log->performed_at)
            ->values()
            ->take($limit);
    }
}
