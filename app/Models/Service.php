<?php

namespace App\Models;

use App\Traits\HasFormattedCode;
use App\Traits\HasVerificationWorkflow;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Service extends Model
{
    use SoftDeletes, HasFormattedCode, LogsActivity, HasVerificationWorkflow;

    protected $fillable = [
        'service_code',
        'legacy_code',
        'name',
        'description',
        'owner_unit',
        'technical_manager',
        'access_method',
        'sla_target',
        'service_hours',
        'status',
        'verification_status',
        'personnel_ref',
        'dynamic_data',
    ];

    protected $casts = [
        'dynamic_data' => 'array',
    ];

    public function getCodeColumn(): string
    {
        return 'service_code';
    }

    public static function codePrefix(): string
    {
        return 'LYN';
    }

    public static function verificationLabel(): string
    {
        return 'Manajemen Layanan';
    }

    /**
     * A service can lean on several IT assets at once (the source sheet's
     * "Aset TIK Utama Terkait" is a comma-separated list), so this is a
     * pivot rather than the single-asset_id pattern every other module
     * uses — see BridgesToAsset for the 1:1 version of this same idea.
     */
    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'service_assets')->withTimestamps();
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(ServiceTicket::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(ServiceEvaluation::class);
    }

    /**
     * The multi-asset equivalent of BridgesToAsset's echoToAsset() — called
     * explicitly after the assets() pivot is synced (not via a model event)
     * since sync() always runs after create/update, so a boot-time hook
     * would fire before the links even exist.
     */
    public function bridgeToLinkedAssets(string $actionType): void
    {
        foreach ($this->assets as $asset) {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'loggable_type' => 'asset',
                'loggable_id' => $asset->id,
                'action_type' => $actionType,
                'old_values' => [],
                'new_values' => [
                    'bridge_note' => "Layanan {$this->service_code}: {$this->name}",
                    'bridge_module' => 'Layanan',
                ],
                'performed_at' => now(),
            ]);

            Asset::where('id', $asset->id)->update(['updated_at' => now()]);
        }
    }
}
