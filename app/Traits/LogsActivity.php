<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

/**
 * Writes an immutable activity_logs entry for every create/update/archive,
 * capturing before/after state as JSON (rule 5). Shared by every ISMS
 * module model so audit coverage is automatic, not opt-in per controller.
 */
trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            $model->recordActivity('created', [], $model->getAttributes());
        });

        static::updated(function ($model) {
            $model->recordActivity('updated', array_intersect_key(
                $model->getOriginal(),
                $model->getChanges()
            ), $model->getChanges());
        });

        static::deleted(function ($model) {
            $action = $model->isForceDeleting() ? 'deleted' : 'archived';
            $model->recordActivity($action, [], []);
        });
    }

    protected function recordActivity(string $actionType, array $oldValues, array $newValues): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            // getMorphClass() honors the morph map registered in
            // AppServiceProvider so this stays "asset"/"risk" instead of the
            // full class name, matching what ActivityLog::loggable() resolves.
            'loggable_type' => $this->getMorphClass(),
            'loggable_id' => $this->getKey(),
            'action_type' => $actionType,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'performed_at' => now(),
        ]);
    }
}
