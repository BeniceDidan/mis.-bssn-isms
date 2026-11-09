<?php

namespace App\Models;

use App\Support\AdminModules;
use App\Traits\HasFormattedCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvidenceDocument extends Model
{
    use SoftDeletes, HasFormattedCode;

    public const DECISIONS = [
        'diterima' => 'Diterima',
        'perlu_perbaikan' => 'Perlu perbaikan',
        'ditolak' => 'Ditolak',
    ];

    protected $fillable = [
        'record_code',
        'module_key',
        'target_level',
        'unit_name',
        'title',
        'file_path',
        'original_name',
        'page_count',
        'extracted_text',
        'achieved_level',
        'score',
        'checks',
        'warnings',
        'ai_notes',
        'engine',
        'assessed_at',
        'reviewer_decision',
        'reviewer_note',
        'reviewed_by',
        'reviewed_at',
        'uploaded_by',
    ];

    protected $casts = [
        'target_level' => 'integer',
        'achieved_level' => 'integer',
        'score' => 'integer',
        'page_count' => 'integer',
        'checks' => 'array',
        'warnings' => 'array',
        'assessed_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    protected $hidden = ['extracted_text'];

    public function getCodeColumn(): string
    {
        return 'record_code';
    }

    public static function codePrefix(): string
    {
        return 'BKT';
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function moduleLabel(): string
    {
        return AdminModules::label($this->module_key) ?? $this->module_key;
    }

    public static function levelName(int $level): string
    {
        return config("bukti_dukung.levels.{$level}.name", '—');
    }

    public function meetsTarget(): bool
    {
        return $this->achieved_level >= $this->target_level;
    }
}
