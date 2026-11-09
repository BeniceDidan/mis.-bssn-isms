<?php

namespace App\Services\Ai;

use App\Models\AiLog;
use App\Models\KnowledgeAsset;
use App\Models\KnowledgeChunk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Mengubah PDF Aset Pengetahuan menjadi potongan teks + embedding, lalu
 * memberi tag otomatis dan ringkasan singkat.
 *
 * Tag berasal dari dua sumber: kosakata terkendali di bawah (selalu jalan,
 * konsisten antar-dokumen) dan usulan LLM (bila tersedia). Kosakata
 * terkendali sengaja dipakai sebagai tulang punggung supaya penyaringan
 * per tag tetap rapi — LLM kecil cenderung membuat variasi ejaan.
 */
class KnowledgeIndexer
{
    public const VOCABULARY = [
        'SOP' => ['standar operasional prosedur', 'sop'],
        'Kebijakan' => ['kebijakan'],
        'Pedoman' => ['pedoman', 'petunjuk teknis', 'juknis'],
        'Regulasi' => ['peraturan menteri', 'peraturan presiden', 'peraturan badan', 'undang-undang', 'peraturan wali kota'],
        'Server & Hosting' => ['server', 'hosting', 'virtual machine', 'vps', 'pusat data', 'data center'],
        'Jaringan' => ['jaringan', 'bandwidth', 'vpn', 'internet', 'wifi'],
        'Keamanan Informasi' => ['keamanan informasi', 'keamanan siber', 'smki', 'iso 27001'],
        'Insiden Siber' => ['insiden', 'serangan', 'malware', 'ransomware', 'peretasan', 'csirt'],
        'Kata Sandi & Akses' => ['kata sandi', 'password', 'hak akses', 'akun pengguna', 'autentikasi'],
        'Backup' => ['backup', 'pencadangan', 'cadangan data', 'pemulihan data'],
        'Integrasi & API' => ['api', 'integrasi', 'interoperabilitas', 'web service', 'endpoint'],
        'Data' => ['satu data', 'metadata', 'walidata', 'data pribadi', 'tata kelola data'],
        'Layanan & Helpdesk' => ['helpdesk', 'layanan bantuan', 'tiket', 'sla', 'service desk'],
        'Risiko' => ['manajemen risiko', 'risiko'],
        'Perubahan' => ['manajemen perubahan', 'permintaan perubahan'],
        'Aset TIK' => ['aset tik', 'inventaris', 'perangkat keras', 'perangkat lunak'],
        'SDM & Kompetensi' => ['kompetensi', 'pelatihan', 'bimbingan teknis', 'sumber daya manusia'],
        'Domain & Email' => ['subdomain', 'domain', 'email dinas', 'surel'],
        'Tanda Tangan Elektronik' => ['tanda tangan elektronik', 'tte', 'bsre'],
    ];

    public function __construct(
        private PdfReader $reader = new PdfReader(),
        private LocalLlm $llm = new LocalLlm(),
    ) {
    }

    public function index(KnowledgeAsset $asset): KnowledgeAsset
    {
        $startedAt = microtime(true);

        if (! $asset->document_path || ! Storage::disk('public')->exists($asset->document_path)) {
            $asset->chunks()->delete();
            $asset->updateQuietly(['index_status' => null, 'indexed_at' => null, 'ai_tags' => $this->vocabularyTags($asset->title, ''), 'ai_summary' => null]);

            return $asset;
        }

        $pdf = $this->reader->read(Storage::disk('public')->path($asset->document_path));
        if (! $pdf['ok'] || $pdf['text_length'] < 100) {
            $asset->chunks()->delete();
            $asset->updateQuietly([
                'index_status' => $pdf['ok'] ? 'tanpa_teks' : 'gagal',
                'indexed_at' => now(),
                'ai_tags' => $this->vocabularyTags($asset->title, ''),
            ]);

            return $asset;
        }

        $chunks = $this->chunk($pdf['pages']);
        $vectors = [];
        foreach (array_chunk($chunks, 16) as $batch) {
            $embedded = $this->llm->embed(array_map(fn ($c) => $asset->title . "\n" . $c['content'], $batch));
            $vectors = array_merge($vectors, $embedded ?? array_fill(0, count($batch), null));
        }

        DB::transaction(function () use ($asset, $chunks, $vectors) {
            $asset->chunks()->delete();
            foreach ($chunks as $i => $chunk) {
                KnowledgeChunk::create([
                    'knowledge_asset_id' => $asset->id,
                    'chunk_index' => $i,
                    'page_number' => $chunk['page'],
                    'content' => $chunk['content'],
                    'embedding' => $vectors[$i] ?? null,
                ]);
            }
        });

        $fullText = implode("\n", $pdf['pages']);
        [$tags, $summary, $engine] = $this->tagAndSummarize($asset, $fullText);
        $hasVectors = collect($vectors)->filter()->isNotEmpty();

        $asset->updateQuietly([
            'ai_tags' => $tags,
            'ai_summary' => $summary,
            'index_status' => $hasVectors ? 'terindeks' : 'kata_kunci',
            'indexed_at' => now(),
        ]);

        AiLog::record(
            'indeks_pengetahuan',
            $engine . ($hasVectors ? ' + embedding ' . config('ai.embed_model') : ''),
            $asset,
            "{$asset->record_code} — {$asset->title} ({$pdf['page_count']} hal., " . count($chunks) . ' potongan)',
            json_encode(['tag' => $tags, 'ringkasan' => $summary], JSON_UNESCAPED_UNICODE),
            $startedAt,
        );

        return $asset;
    }

    /**
     * Potong per halaman (supaya sumber jawaban bisa menunjuk nomor
     * halaman), ±900 karakter dengan tumpang tindih 150 karakter.
     *
     * @param  array<int, string>  $pages
     * @return array<int, array{page: int, content: string}>
     */
    public function chunk(array $pages): array
    {
        $size = (int) config('ai.chunk_chars', 900);
        $overlap = (int) config('ai.chunk_overlap', 150);
        $chunks = [];

        foreach ($pages as $pageNo => $text) {
            $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
            $length = mb_strlen($text);
            if ($length < 40) {
                continue;
            }

            $start = 0;
            while ($start < $length) {
                $piece = mb_substr($text, $start, $size);
                // Akhiri di batas kalimat/kata terdekat bila memungkinkan.
                if ($start + $size < $length) {
                    $cut = max(mb_strrpos($piece, '. ') ?: 0, mb_strrpos($piece, '; ') ?: 0);
                    if ($cut < $size * 0.5) {
                        $cut = mb_strrpos($piece, ' ') ?: mb_strlen($piece);
                    }
                    $piece = mb_substr($piece, 0, $cut + 1);
                }

                $chunks[] = ['page' => (int) $pageNo, 'content' => trim($piece)];
                $advance = max(mb_strlen($piece) - $overlap, (int) ($size / 2));
                $start += $advance;
            }
        }

        return $chunks;
    }

    /** @return array<int, string> */
    public function vocabularyTags(string $title, string $text): array
    {
        $title = EvidenceAssessor::flatten($title);
        $body = EvidenceAssessor::flatten($text);
        $scores = [];

        foreach (self::VOCABULARY as $tag => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                $pattern = '/(?<![\p{L}\p{N}])' . str_replace(' ', '\s+', preg_quote($keyword, '/')) . '(?![\p{L}\p{N}])/u';
                $score += 3 * preg_match_all($pattern, $title);
                $score += preg_match_all($pattern, $body);
            }
            if ($score >= 2) {
                $scores[$tag] = $score;
            }
        }

        arsort($scores);

        return array_slice(array_keys($scores), 0, 5);
    }

    /** @return array{0: array<int, string>, 1: ?string, 2: string} */
    private function tagAndSummarize(KnowledgeAsset $asset, string $fullText): array
    {
        $tags = $this->vocabularyTags($asset->title, $fullText);
        $summary = null;
        $engine = 'Kosakata tag';

        $answer = $this->llm->generate(
            "Judul: {$asset->title}\n\nIsi dokumen:\n\"\"\"\n" . mb_substr($fullText, 0, (int) config('ai.max_prompt_chars')) . "\n\"\"\"\n\n"
            . 'Jawab dalam JSON: {"ringkasan": "isi dokumen dalam 1-2 kalimat", "tag": ["maksimal 4 tag topik, masing-masing 1-3 kata"]}',
            true,
            'Anda pustakawan dokumen pemerintahan Indonesia. Gunakan Bahasa Indonesia baku dan hanya informasi dari dokumen.'
        );

        if (is_array($answer)) {
            $engine = 'Kosakata tag + ' . $this->llm->chatEngineName();
            if (filled($answer['ringkasan'] ?? null) && is_string($answer['ringkasan'])) {
                $summary = Str::limit(trim($answer['ringkasan']), 400);
            }
            $tooGeneric = ['pemerintah', 'pemerintahan', 'pemerintah daerah', 'dokumen', 'dinas', 'kota', 'informasi', 'umum'];
            $llmTags = collect($answer['tag'] ?? [])
                ->filter(fn ($t) => is_string($t) && mb_strlen(trim($t)) >= 3 && mb_strlen(trim($t)) <= 30 && str_word_count(trim($t)) <= 3)
                ->reject(fn ($t) => in_array(Str::lower(trim($t)), $tooGeneric, true))
                ->map(fn ($t) => Str::ucfirst(trim($t)));

            // Usulan LLM hanya ditambahkan bila bukan pengulangan tag yang
            // sudah ada (mis. "Insiden" saat sudah ada "Insiden Siber").
            foreach ($llmTags as $candidate) {
                $lower = Str::lower($candidate);
                $duplicate = collect($tags)->contains(fn ($t) => Str::contains(Str::lower($t), $lower) || Str::contains($lower, Str::lower($t)));
                if (! $duplicate && count($tags) < 6) {
                    $tags[] = $candidate;
                }
            }
        }

        return [$tags, $summary, $engine];
    }
}
