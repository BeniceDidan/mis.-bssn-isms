<?php

namespace App\Models;

use App\Enums\AssetCategory;
use App\Enums\Level;
use App\Services\RiskLevelService;
use App\Traits\HasFormattedCode;
use App\Traits\HasVerificationWorkflow;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Asset extends Model
{
    use SoftDeletes, HasFormattedCode, LogsActivity, HasVerificationWorkflow;

    protected $fillable = [
        'asset_code',
        'legacy_code',
        'category',
        'sub_classification',
        'name',
        'owner',
        'location',
        'status',
        'criticality_level',
        'confidentiality_score',
        'integrity_score',
        'availability_score',
        'verification_status',
        'personnel_ref',
        'reference_year',
        'dynamic_data',
    ];

    protected $casts = [
        'category' => AssetCategory::class,
        'criticality_level' => Level::class,
        'confidentiality_score' => 'integer',
        'integrity_score' => 'integer',
        'availability_score' => 'integer',
        'reference_year' => 'integer',
        // Works transparently against the postgres jsonb column — Laravel's
        // array cast doesn't need a jsonb-specific variant.
        'dynamic_data' => 'array',
    ];

    protected static function booted(): void
    {
        static::saved(function (Asset $asset) {
            // wasChanged() is only populated on updates (Eloquent doesn't
            // sync $changes on a fresh insert) — wasRecentlyCreated covers
            // the case of a brand-new critical Asset that already has
            // linked Risks pointing at it (e.g. import order edge cases).
            if (! $asset->wasRecentlyCreated && ! $asset->wasChanged('criticality_level')) {
                return;
            }

            $service = new RiskLevelService();

            // The concrete implementation of Bab 11 in the Kediri guide:
            // an asset's computed kritikalitas is a real input into every
            // linked Risk's derived level, not just a display field — so
            // raising an asset's criticality here immediately ripples
            // forward into its risks, the reverse direction of the
            // spoke -> hub echo BridgesToAsset already does.
            foreach ($asset->risks as $risk) {
                $newLevel = $service->derive(
                    $risk->likelihood?->value,
                    $risk->impact?->value,
                    $asset->criticality_level?->value,
                    \App\Models\HrRisk::hasSevereRiskFor($risk->personnel_ref) ? 'tinggi' : null,
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
                        'bridge_note' => "Level risiko otomatis disesuaikan menjadi {$newLevel} karena kritikalitas aset {$asset->name} berubah.",
                        'bridge_module' => 'Manajemen Aset',
                    ],
                    'performed_at' => now(),
                ]);
            }
        });
    }

    public function getCodeColumn(): string
    {
        return 'asset_code';
    }

    public static function codePrefix(): string
    {
        return 'AST';
    }

    public static function verificationLabel(): string
    {
        return 'Manajemen Aset';
    }

    /**
     * A single asset may be the origin of multiple identified risks
     * (rule 7: strict FK integrity between Risk and Asset).
     */
    public function risks(): HasMany
    {
        return $this->hasMany(Risk::class);
    }

    /**
     * The "benang merah" (common thread) cross-module links: a revision
     * logged in Manajemen Perubahan against this asset, a data-governance
     * risk raised against it, or a knowledge/manual-book entry documenting
     * it — surfaced together in the asset's "Riwayat Terkait" panel so the
     * SDM → Pengetahuan → Aset → Keamanan → Risiko → Perubahan → Layanan →
     * Data Informasi flow is visible from the asset itself, not just from
     * each module in isolation.
     */
    public function changes(): HasMany
    {
        return $this->hasMany(Change::class);
    }

    public function dataInformationRecords(): HasMany
    {
        return $this->hasMany(DataInformation::class);
    }

    public function knowledgeAssets(): HasMany
    {
        return $this->hasMany(KnowledgeAsset::class);
    }

    public function securityPrograms(): HasMany
    {
        return $this->hasMany(SecurityProgram::class);
    }

    /**
     * Many-to-many, unlike the other spokes above — a service can lean on
     * several assets at once (see Service::assets()).
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_assets')->withTimestamps();
    }
}
