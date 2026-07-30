<?php

namespace App\Models;

use App\Enums\ChangeType;
use App\Enums\Level;
use App\Traits\BridgesToAsset;
use App\Traits\HasFormattedCode;
use App\Traits\HasVerificationWorkflow;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataInformation extends Model
{
    use SoftDeletes, HasFormattedCode, LogsActivity, BridgesToAsset, HasVerificationWorkflow;

    protected $table = 'data_informations';

    protected $fillable = [
        'record_code',
        'legacy_code',
        'asset_id',
        'title',
        'risk_type',
        'category',
        'priority',
        'inherent_risk_level',
        'decision',
        'status',
        'verification_status',
        'personnel_ref',
        'pic',
        'target_date',
        'dynamic_data',
    ];

    protected $casts = [
        'risk_type' => ChangeType::class,
        'inherent_risk_level' => Level::class,
        'target_date' => 'date',
        'dynamic_data' => 'array',
    ];

    public function getCodeColumn(): string
    {
        return 'record_code';
    }

    public static function codePrefix(): string
    {
        return 'MDI';
    }

    public static function bridgeModuleLabel(): string
    {
        return 'Data & Informasi';
    }

    public static function verificationLabel(): string
    {
        return 'Manajemen Data & Informasi';
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
