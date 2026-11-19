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

    /**
     * Asal skor: skor = unsur rubrik Level 1 s.d. level klaim yang ditemukan
     * dibagi semua unsur Level 1 s.d. level klaim (lihat EvidenceAssessor).
     * Contoh pedoman risiko klaim Level 3: 6 dari 7 unsur = 86.
     *
     * @return array{found: int, total: int}
     */
    public function scoreBreakdown(): array
    {
        $unsur = collect($this->checks['levels'] ?? [])
            ->filter(fn ($level, $no) => (int) $no <= $this->target_level)
            ->flatMap(fn ($level) => $level['unsur'] ?? []);

        return ['found' => $unsur->where('found', true)->count(), 'total' => $unsur->count()];
    }

    /**
     * Skor ditampilkan sebagai "Kelengkapan 86%": persentase unsur rubrik yang
     * ditemukan, bukan nilai resmi evaluasi (PermenPANRB 8/2026 memakai indeks
     * 1,00–5,00). PDF hasil scan atau tidak terbaca tidak dinilai isinya → "—".
     */
    public function completenessLabel(): string
    {
        $pdf = $this->checks['pdf'] ?? [];
        if ($this->score === null || ! ($pdf['readable'] ?? true) || ($pdf['scanned'] ?? false)) {
            return '—';
        }

        return $this->score . '%';
    }

    /** Kata kunci rubrik untuk sebuah unsur, supaya tampilan bisa menyebut apa yang dicari. */
    public function keywordsFor(int|string $level, string $label): array
    {
        return collect(config("bukti_dukung.modules.{$this->module_key}.levels.{$level}.unsur", []))
            ->firstWhere('label', $label)['keywords'] ?? [];
    }
}
