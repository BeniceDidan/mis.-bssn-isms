<?php

namespace App\Services\Ai;

use App\Models\AiLog;
use App\Models\EvidenceDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * AI Assessor bukti dukung.
 *
 * Dua lapis, sengaja dipisah:
 * 1. Mesin aturan (selalu jalan, deterministik, bisa diaudit): cek atribut
 *    keabsahan + cocokkan unsur rubrik config/bukti_dukung.php per level.
 *    Level dan skor HANYA berasal dari lapis ini.
 * 2. LLM lokal (bila tersedia): ringkasan isi dan catatan asesor sebagai
 *    pendapat kedua. Tidak mengubah skor — keputusan tetap pada manusia.
 */
class EvidenceAssessor
{
    public function __construct(
        private PdfReader $reader = new PdfReader(),
        private LocalLlm $llm = new LocalLlm(),
    ) {
    }

    public function assess(EvidenceDocument $doc): EvidenceDocument
    {
        $startedAt = microtime(true);
        $pdf = $this->reader->read(Storage::disk('local')->path($doc->file_path));

        $result = $this->evaluate($pdf, $doc->module_key, $doc->target_level);
        $engine = 'Mesin aturan';
        $aiNotes = null;

        if ($pdf['ok'] && ! $result['checks']['pdf']['scanned']) {
            $aiNotes = $this->llmReview($doc, $pdf['pages'], $result);
            if ($aiNotes !== null) {
                $engine = 'Mesin aturan + ' . $this->llm->chatEngineName();
            }
        }

        $doc->fill([
            'page_count' => $pdf['page_count'],
            'extracted_text' => PdfReader::fullText($pdf['pages']),
            'achieved_level' => $result['achieved_level'],
            'score' => $result['score'],
            'checks' => $result['checks'],
            'warnings' => $result['warnings'],
            'ai_notes' => $aiNotes,
            'engine' => $engine,
            'assessed_at' => now(),
        ])->save();

        AiLog::record(
            'bukti_dukung',
            $engine,
            $doc,
            "{$doc->moduleLabel()} — target Level {$doc->target_level} — {$doc->original_name}",
            json_encode(['level' => $result['achieved_level'], 'skor' => $result['score'], 'peringatan' => $result['warnings'], 'catatan_ai' => $aiNotes], JSON_UNESCAPED_UNICODE),
            $startedAt,
        );

        return $doc;
    }

    /**
     * Inti mesin aturan — murni (tanpa I/O), supaya mudah diuji.
     *
     * @param  array{ok: bool, error: ?string, page_count: int, pages: array<int, string>, images_per_page: array<int, int>, digital_signature: bool}  $pdf
     * @return array{achieved_level: int, score: int, checks: array, warnings: array<int, array{type: string, text: string}>}
     */
    public function evaluate(array $pdf, string $moduleKey, int $targetLevel): array
    {
        $rubric = config("bukti_dukung.modules.{$moduleKey}");
        $warnings = [];

        if (! $pdf['ok']) {
            return [
                'achieved_level' => 0,
                'score' => 0,
                'checks' => ['pdf' => ['readable' => false, 'scanned' => false, 'page_count' => 0]],
                'warnings' => [['type' => 'danger', 'text' => $pdf['error'] ?? 'PDF tidak dapat dibaca.']],
            ];
        }

        $pages = collect($pdf['pages'])->map(fn ($t) => self::flatten($t))->all();
        $pageCount = max(1, $pdf['page_count']);
        $minChars = (int) config('bukti_dukung.min_chars_per_page', 120);
        $textPages = collect($pages)->filter(fn ($t) => mb_strlen($t) >= $minChars)->count();
        $scanned = $textPages < ceil($pageCount / 2);
        $lastPage = $pdf['page_count'];
        $imagesLastPage = $pdf['images_per_page'][$lastPage] ?? 0;

        $pdfCheck = [
            'readable' => true,
            'page_count' => $pdf['page_count'],
            'text_pages' => $textPages,
            'scanned' => $scanned,
            'digital_signature' => $pdf['digital_signature'],
            'images_last_page' => $imagesLastPage,
        ];

        // --- Atribut keabsahan -------------------------------------------
        $fullText = implode(' ', $pages);
        $pengesahan = [];
        foreach (config('bukti_dukung.pengesahan') as $key => $rule) {
            $match = null;
            foreach ($rule['patterns'] as $pattern) {
                if (preg_match($pattern, $fullText, $m)) {
                    $match = Str::limit(trim($m[0]), 60);
                    break;
                }
            }
            $pengesahan[$key] = ['label' => $rule['label'], 'found' => $match !== null, 'match' => $match];
        }

        // --- Unsur rubrik per level ----------------------------------------
        $levels = [];
        $achieved = 0;
        $chainIntact = true;
        foreach ($rubric['levels'] as $level => $def) {
            $unsur = [];
            foreach ($def['unsur'] as $item) {
                [$keyword, $page] = $this->findKeyword($item['keywords'], $pages);
                $unsur[] = ['label' => $item['label'], 'found' => $keyword !== null, 'keyword' => $keyword, 'page' => $page];
            }
            $met = collect($unsur)->every(fn ($u) => $u['found']);
            $levels[$level] = [
                'name' => config("bukti_dukung.levels.{$level}.name"),
                'kriteria' => $def['kriteria'],
                'unsur' => $unsur,
                'met' => $met,
            ];
            if ($chainIntact && $met) {
                $achieved = $level;
            } else {
                $chainIntact = false;
            }
        }

        $upToTarget = collect($levels)->filter(fn ($l, $lv) => $lv <= $targetLevel)->flatMap(fn ($l) => $l['unsur']);
        $score = $upToTarget->isEmpty() ? 0 : (int) round(100 * $upToTarget->where('found', true)->count() / $upToTarget->count());

        if ($scanned) {
            $score = min($score, 20);
            $achieved = 0;
        }

        // --- Kalimat peringatan --------------------------------------------
        if ($scanned) {
            $warnings[] = ['type' => 'danger', 'text' => 'Sebagian besar halaman tidak berisi teks (kemungkinan hasil pindai). Isi tidak bisa dinilai otomatis — periksa manual atau unggah PDF asli yang berteks.'];
        } elseif ($achieved >= $targetLevel) {
            $warnings[] = ['type' => 'ok', 'text' => "Dokumen mendukung Level {$targetLevel} ({$levels[$targetLevel]['name']})."];
        } else {
            $missing = collect($levels)
                ->filter(fn ($l, $lv) => $lv > $achieved && $lv <= $targetLevel)
                ->flatMap(fn ($l) => $l['unsur'])
                ->where('found', false)
                ->pluck('label')
                ->map(fn ($label) => Str::lcfirst($label))
                ->implode('; ');

            $warnings[] = $achieved > 0
                ? ['type' => 'warning', 'text' => "Dokumen sesuai untuk Level {$achieved} ({$levels[$achieved]['name']}), tetapi belum memenuhi Level {$targetLevel}: belum ada {$missing}."]
                : ['type' => 'warning', 'text' => "Belum memenuhi Level 1. Belum ditemukan: {$missing}."];
        }

        if (! $scanned) {
            if (! $pengesahan['nomor']['found']) {
                $warnings[] = ['type' => 'warning', 'text' => 'Nomor dokumen tidak ditemukan.'];
            }
            if (! $pengesahan['tanggal']['found']) {
                $warnings[] = ['type' => 'warning', 'text' => 'Tanggal penetapan tidak ditemukan.'];
            }
        }

        if ($pdf['digital_signature']) {
            $warnings[] = ['type' => 'ok', 'text' => 'Berkas memuat tanda tangan elektronik tertanam.'];
        } elseif ($pengesahan['tte']['found']) {
            $warnings[] = ['type' => 'warning', 'text' => 'Ada keterangan tanda tangan elektronik, tetapi tanda tangan digitalnya tidak tertanam di berkas — kemungkinan PDF hasil cetak ulang. Minta berkas asli dari sistem TTE.'];
        } elseif ($imagesLastPage > 0) {
            $warnings[] = ['type' => 'warning', 'text' => 'Halaman terakhir memuat gambar — kemungkinan cap/tanda tangan basah hasil pindai. Pastikan secara visual.'];
        } elseif (! $pengesahan['penandatangan']['found']) {
            $warnings[] = ['type' => 'danger', 'text' => 'Tidak ditemukan tanda tangan maupun blok pengesahan (jabatan dan NIP).'];
        }

        return [
            'achieved_level' => $achieved,
            'score' => $score,
            'checks' => ['pdf' => $pdfCheck, 'pengesahan' => $pengesahan, 'levels' => $levels],
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<int, string>  $keywords
     * @param  array<int, string>  $pages  teks yang sudah di-flatten
     * @return array{0: ?string, 1: ?int}
     */
    private function findKeyword(array $keywords, array $pages): array
    {
        foreach ($keywords as $keyword) {
            $pattern = '/(?<![\p{L}\p{N}])' . str_replace(' ', '\s+', preg_quote(mb_strtolower($keyword), '/')) . '/u';
            foreach ($pages as $no => $text) {
                if (preg_match($pattern, $text)) {
                    return [$keyword, $no];
                }
            }
        }

        return [null, null];
    }

    /** Huruf kecil + spasi tunggal, supaya kata kunci lintas baris tetap cocok. */
    public static function flatten(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/-\s*\n\s*/u', '', $text) ?? $text; // pemenggalan kata di akhir baris
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function llmReview(EvidenceDocument $doc, array $pages, array $result): ?string
    {
        if (! $this->llm->chatAvailable()) {
            return null;
        }

        $rubric = config("bukti_dukung.modules.{$doc->module_key}");
        $target = $result['checks']['levels'][$doc->target_level];
        $limit = (int) config('ai.max_prompt_chars');
        $full = PdfReader::fullText($pages);
        // Awal dokumen (isi) + akhir dokumen (biasanya blok pengesahan).
        $excerpt = mb_strlen($full) > $limit
            ? mb_substr($full, 0, (int) ($limit * 0.8)) . "\n...\n" . mb_substr($full, -(int) ($limit * 0.2))
            : $full;

        $ruleSummary = collect($target['unsur'])->map(fn ($u) => '- ' . $u['label'] . ': ' . ($u['found'] ? 'ditemukan' : 'TIDAK ditemukan'))->implode("\n");

        $answer = $this->llm->generate(
            "Indikator: {$rubric['indicator']}\n"
            . "Level yang diklaim: Level {$doc->target_level} ({$target['name']}). Kriteria: {$target['kriteria']}\n"
            . "Hasil pemeriksaan kata kunci otomatis:\n{$ruleSummary}\n\n"
            . "Isi dokumen \"{$doc->title}\":\n\"\"\"\n{$excerpt}\n\"\"\"\n\n"
            . 'Jawab dalam JSON: {"ringkasan": "isi dokumen dalam 1-2 kalimat", "catatan": "penilaian kesesuaian dengan kriteria, maksimal 3 kalimat", "kekurangan": ["hal yang belum ada, maksimal 3"]}',
            true,
            'Anda asesor internal Evaluasi Kinerja Pemerintah Digital di pemerintah daerah Indonesia. '
            . 'Nilai apakah dokumen bukti dukung mendukung kriteria level yang diklaim. Jawab dalam Bahasa Indonesia baku, '
            . 'hanya berdasarkan teks dokumen yang diberikan; jangan mengarang isi yang tidak ada.'
        );

        if (! is_array($answer)) {
            return null;
        }

        $lines = array_filter([
            filled($answer['ringkasan'] ?? null) ? 'Ringkasan: ' . Str::limit((string) $answer['ringkasan'], 400) : null,
            filled($answer['catatan'] ?? null) ? 'Catatan: ' . Str::limit((string) $answer['catatan'], 600) : null,
        ]);
        $gaps = collect($answer['kekurangan'] ?? [])->filter(fn ($g) => is_string($g) && trim($g) !== '')->take(3)->map(fn ($g) => '• ' . Str::limit(trim($g), 200));
        if ($gaps->isNotEmpty()) {
            $lines[] = "Kekurangan:\n" . $gaps->implode("\n");
        }

        return $lines === [] ? null : implode("\n\n", $lines);
    }
}
