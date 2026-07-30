<?php

namespace App\Traits;

use App\Models\ActivityLog;
use App\Models\Asset;
use Illuminate\Support\Facades\Auth;

/**
 * The concrete "efek" mechanism behind the ISMS integration flow: a model
 * that carries asset_id (Risk, Change, DataInformation, KnowledgeAsset)
 * echoes its create/update/archive/restore events onto the linked Asset's
 * own activity_logs feed and bumps the Asset's updated_at. This is what
 * makes editing one module visibly ripple into Manajemen Aset — the
 * Aktivitas Terbaru list on the Asset page shows it immediately, not only
 * when someone opens "Riwayat Terkait" on that specific asset.
 *
 * Consuming models must implement bridgeModuleLabel() and expose a
 * human-readable summary via title/name/subject.
 */
trait BridgesToAsset
{
    protected static function bootBridgesToAsset(): void
    {
        static::created(fn ($model) => $model->echoToAsset('created'));
        static::updated(fn ($model) => $model->echoToAsset('updated'));
        static::deleted(fn ($model) => $model->echoToAsset($model->isForceDeleting() ? 'deleted' : 'archived'));
        static::restored(fn ($model) => $model->echoToAsset('restored'));
    }

    protected function echoToAsset(string $actionType): void
    {
        if (blank($this->asset_id)) {
            return;
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => 'asset',
            'loggable_id' => $this->asset_id,
            'action_type' => $actionType,
            'old_values' => [],
            'new_values' => [
                'bridge_note' => $this->bridgeNote(),
                'bridge_module' => static::bridgeModuleLabel(),
            ],
            'performed_at' => now(),
        ]);

        // Touching the Asset's own updated_at means "recently changed"
        // sorts/filters on Aset naturally surface it as freshly updated —
        // exactly the "dataset aset yang ter-update" effect the flow calls
        // for, without duplicating data into a second, staleness-prone copy.
        Asset::where('id', $this->asset_id)->update(['updated_at' => now()]);
    }

    protected function bridgeNote(): string
    {
        $code = $this->{$this->getCodeColumn()} ?? '';
        $summary = $this->title ?? $this->name ?? $this->subject ?? '';

        return trim(static::bridgeModuleLabel() . " {$code}: {$summary}");
    }
}
