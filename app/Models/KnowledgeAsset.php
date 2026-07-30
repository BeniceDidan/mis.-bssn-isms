<?php

namespace App\Models;

use App\Enums\KnowledgeType;
use App\Traits\BridgesToAsset;
use App\Traits\HasFormattedCode;
use App\Traits\HasVerificationWorkflow;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class KnowledgeAsset extends Model
{
    use SoftDeletes, HasFormattedCode, LogsActivity, BridgesToAsset, HasVerificationWorkflow;

    protected $fillable = [
        'record_code',
        'legacy_code',
        'asset_id',
        'title',
        'knowledge_type',
        'category',
        'owner_unit',
        'access_level',
        'external_link',
        'document_path',
        'document_original_name',
        'last_reviewed_at',
        'verification_status',
        'personnel_ref',
        'dynamic_data',
    ];

    protected $casts = [
        'knowledge_type' => KnowledgeType::class,
        'last_reviewed_at' => 'date',
        'dynamic_data' => 'array',
    ];

    public function getCodeColumn(): string
    {
        return 'record_code';
    }

    public static function codePrefix(): string
    {
        return 'KNW';
    }

    public static function bridgeModuleLabel(): string
    {
        return 'Pengetahuan';
    }

    public static function verificationLabel(): string
    {
        return 'Manajemen Pengetahuan';
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
