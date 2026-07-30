<?php

namespace App\Models;

use App\Enums\Level;
use App\Services\RiskLevelService;
use App\Traits\HasFormattedCode;
use App\Traits\HasVerificationWorkflow;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class HrRisk extends Model
{
    use SoftDeletes, HasFormattedCode, LogsActivity, HasVerificationWorkflow;

    protected $fillable = [
        'record_code',
        'legacy_code',
        'subject',
        'title',
        'category',
        'inherent_risk_level',
        'status',
        'verification_status',
        'personnel_ref',
        'pic',
        'target_date',
        'dynamic_data',
    ];

    protected $casts = [
        'inherent_risk_level' => Level::class,
        'target_date' => 'date',
        'dynamic_data' => 'array',
    ];

    protected static function booted(): void
    {
        static::saved(function (HrRisk $hrRisk) {
            // wasChanged() is only populated on updates (Eloquent doesn't
            // sync $changes on a fresh insert) — wasRecentlyCreated covers
            // a brand-new severe HrRisk record too.
            $relevant = $hrRisk->wasRecentlyCreated || $hrRisk->wasChanged(['inherent_risk_level', 'personnel_ref']);

            if (! $hrRisk->personnel_ref || ! $relevant) {
                return;
            }

            // The "Kode Personil" answer to "kan harusnya terintegrasi":
            // an SDM record's own risk level is now a real escalation input
            // into every Risk sharing the same Kode Personil, mirroring the
            // existing Asset criticality -> Risk level ripple (see
            // RiskLevelService and Asset::booted()) — not just a visibility
            // link, an actual recomputed value.
            $service = new RiskLevelService();

            foreach (Risk::where('personnel_ref', $hrRisk->personnel_ref)->with('asset')->get() as $risk) {
                $newLevel = $service->derive(
                    $risk->likelihood?->value,
                    $risk->impact?->value,
                    $risk->asset?->criticality_level?->value,
                    HrRisk::hasSevereRiskFor($hrRisk->personnel_ref) ? Level::Tinggi->value : null,
                );

                if ($newLevel === $risk->risk_level?->value) {
                    continue;
                }

                $risk->update(['risk_level' => $newLevel]);

                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'loggable_type' => 'risk',
                    'loggable_id' => $risk->id,
                    'action_type' => 'updated',
                    'old_values' => [],
                    'new_values' => [
                        'bridge_note' => "Level risiko otomatis disesuaikan menjadi {$newLevel} karena SDM terkait (Kode Personil {$hrRisk->personnel_ref}) berlevel {$hrRisk->inherent_risk_level?->label()}.",
                        'bridge_module' => 'Manajemen SDM',
                    ],
                    'performed_at' => now(),
                ]);
            }
        });
    }

    /**
     * True if ANY (non-archived) SDM record under this Kode Personil is
     * itself Tinggi/Kritis — used as a single boolean escalation input,
     * same shape as an asset's criticality_level in RiskLevelService.
     */
    public static function hasSevereRiskFor(?string $personnelRef): bool
    {
        if (! $personnelRef) {
            return false;
        }

        return static::where('personnel_ref', $personnelRef)
            ->whereIn('inherent_risk_level', [Level::Tinggi->value, Level::Kritis->value])
            ->exists();
    }

    public function getCodeColumn(): string
    {
        return 'record_code';
    }

    public static function codePrefix(): string
    {
        return 'SDM';
    }

    public static function verificationLabel(): string
    {
        return 'Manajemen SDM';
    }
}
