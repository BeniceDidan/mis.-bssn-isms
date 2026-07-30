<?php

namespace App\Models;

use App\Traits\BridgesToAsset;
use App\Traits\HasFormattedCode;
use App\Traits\HasVerificationWorkflow;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SecurityProgram extends Model
{
    use SoftDeletes, HasFormattedCode, LogsActivity, BridgesToAsset, HasVerificationWorkflow;

    protected $fillable = [
        'record_code',
        'legacy_code',
        'asset_id',
        'program_kerja',
        'kegiatan',
        'pic',
        'verification_status',
        'personnel_ref',
        'dynamic_data',
    ];

    protected $casts = [
        'dynamic_data' => 'array',
    ];

    public function getCodeColumn(): string
    {
        return 'record_code';
    }

    public static function codePrefix(): string
    {
        return 'KIN';
    }

    public static function verificationLabel(): string
    {
        return 'Manajemen Keamanan Informasi';
    }

    public static function bridgeModuleLabel(): string
    {
        return 'Keamanan Informasi';
    }

    /**
     * BridgesToAsset::bridgeNote() falls back through title/name/subject,
     * none of which this model has — program_kerja is its summary field.
     */
    protected function bridgeNote(): string
    {
        $code = $this->record_code ?? '';

        return trim(static::bridgeModuleLabel() . " {$code}: {$this->program_kerja}");
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
