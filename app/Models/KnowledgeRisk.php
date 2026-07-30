<?php

namespace App\Models;

use App\Enums\Level;
use App\Traits\HasFormattedCode;
use App\Traits\HasVerificationWorkflow;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class KnowledgeRisk extends Model
{
    use SoftDeletes, HasFormattedCode, LogsActivity, HasVerificationWorkflow;

    protected $fillable = [
        'record_code',
        'legacy_code',
        'knowledge_asset_id',
        'pernyataan_risiko',
        'akar_penyebab',
        'area_dampak',
        'dampak',
        'kemungkinan',
        'skor_risiko_bawaan',
        'tingkat_risiko_bawaan',
        'rencana_mitigasi',
        'penanggung_jawab',
        'tingkat_residual_risk',
        'verification_status',
        'personnel_ref',
        'dynamic_data',
    ];

    protected $casts = [
        'dampak' => 'integer',
        'kemungkinan' => 'integer',
        'skor_risiko_bawaan' => 'integer',
        'tingkat_risiko_bawaan' => Level::class,
        'tingkat_residual_risk' => Level::class,
        'dynamic_data' => 'array',
    ];

    public function getCodeColumn(): string
    {
        return 'record_code';
    }

    public static function codePrefix(): string
    {
        return 'RKM';
    }

    public static function verificationLabel(): string
    {
        return 'Manajemen Pengetahuan';
    }

    public function knowledgeAsset(): BelongsTo
    {
        return $this->belongsTo(KnowledgeAsset::class);
    }
}
