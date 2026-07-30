<?php

namespace App\Models;

use App\Enums\Level;
use App\Enums\RiskCategory;
use App\Enums\RiskStatus;
use App\Enums\TreatmentStrategy;
use App\Services\RiskEscalationResponseService;
use App\Traits\BridgesToAsset;
use App\Traits\HasFormattedCode;
use App\Traits\HasVerificationWorkflow;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Risk extends Model
{
    use SoftDeletes, HasFormattedCode, LogsActivity, BridgesToAsset, HasVerificationWorkflow;

    protected static function booted(): void
    {
        static::saved(function (Risk $risk) {
            // wasChanged() is populated by performUpdate() only — Eloquent
            // never syncs $changes on a fresh insert, so it's always false
            // right after create() even when risk_level was set explicitly.
            // wasRecentlyCreated covers that path; wasChanged covers later
            // edits/recomputes.
            if (! $risk->wasRecentlyCreated && ! $risk->wasChanged('risk_level')) {
                return;
            }

            (new RiskEscalationResponseService())->respond($risk);
        });
    }

    protected $fillable = [
        'risk_code',
        'asset_id',
        'title',
        'category',
        'threat_source',
        'vulnerability',
        'likelihood',
        'impact',
        'risk_level',
        'risk_owner',
        'treatment_strategy',
        'status',
        'verification_status',
        'personnel_ref',
        'identified_at',
        'review_due_at',
        'dynamic_data',
    ];

    protected $casts = [
        'category' => RiskCategory::class,
        'likelihood' => Level::class,
        'impact' => Level::class,
        'risk_level' => Level::class,
        'treatment_strategy' => TreatmentStrategy::class,
        'status' => RiskStatus::class,
        'identified_at' => 'date',
        'review_due_at' => 'date',
        'dynamic_data' => 'array',
    ];

    public function getCodeColumn(): string
    {
        return 'risk_code';
    }

    public static function codePrefix(): string
    {
        return 'RSK';
    }

    public static function bridgeModuleLabel(): string
    {
        return 'Risiko';
    }

    public static function verificationLabel(): string
    {
        return 'Manajemen Risiko';
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
