<?php

namespace App\Models;

use App\Support\AdminModules;
use App\Traits\HasFormattedCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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

    /**
     * Bagian catatan LLM yang layak tampil: ringkasan satu kalimat dan
     * kekurangan yang BELUM disebut mesin aturan (paling banyak 2).
     *
     * Bagian "Catatan" dan kekurangan yang mengulang daftar "Belum ada" tidak
     * ditampilkan: di data demo 19 Nov 2026 semuanya hanya mengulang hasil
     * aturan, bahkan salah menyebut indikator ("tidak sesuai dengan kriteria
     * Indikator 2. Namun, dokumen ini memenuhi kriteria lainnya").
     *
     * @return array{summary: ?string, gaps: array<int, string>}
     */
    public function aiHighlights(): array
    {
        $notes = str_replace("\r\n", "\n", (string) $this->ai_notes);
        if (trim($notes) === '') {
            return ['summary' => null, 'gaps' => []];
        }

        $summary = preg_match('/Ringkasan:\s*(.+?)(?:\n\s*\n|$)/su', $notes, $m) ? self::plainSummary($m[1]) : null;

        $ruleGaps = collect($this->checks['levels'] ?? [])
            ->flatMap(fn ($level) => collect($level['unsur'] ?? [])->where('found', false)->pluck('label'))
            ->all();
        $gaps = [];
        if (preg_match('/Kekurangan:\s*\n(.+)$/su', $notes, $m)) {
            foreach (preg_split('/\n/u', $m[1]) as $line) {
                $line = trim(preg_replace('/^[\s•*\-]+/u', '', $line) ?? $line);
                if ($line !== '' && ! self::repeatsAny($line, $ruleGaps)) {
                    $gaps[] = Str::ucfirst(Str::limit(rtrim($line, '.'), 160));
                }
            }
        }

        return ['summary' => $summary !== '' ? $summary : null, 'gaps' => array_slice($gaps, 0, 2)];
    }

    /** "Dokumen ini menyajikan pedoman …" → "Pedoman …". */
    public static function plainSummary(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        $text = preg_replace(
            '/^(?:dokumen|berkas)(?:\s+ini|\s+[^,.]{1,90}?)?\s+(?:berisi(?:kan)?|menyajikan|menyatakan|mengatur|menjelaskan|membahas|memuat|merupakan|adalah)\s+(?:tentang\s+)?/iu',
            '',
            $text
        ) ?? $text;

        return Str::ucfirst(Str::limit($text, 220));
    }

    /** Kekurangan dari LLM yang sama dengan unsur yang sudah tercatat tidak ditemukan. */
    private static function repeatsAny(string $gap, array $labels): bool
    {
        $words = fn (string $s) => collect(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($s)))
            ->filter(fn ($w) => mb_strlen($w) >= 4 && ! in_array($w, ['tidak', 'ditemukan', 'dokumen', 'belum', 'yang', 'dalam', 'tentang', 'informasi', 'disebutkan', 'menyebutkan', 'terdapat'], true))
            ->unique();
        $gapWords = $words($gap);
        foreach ($labels as $label) {
            $labelWords = $words($label);
            if ($labelWords->isNotEmpty() && $labelWords->intersect($gapWords)->count() >= min(2, $labelWords->count())) {
                return true;
            }
        }

        return false;
    }
}
