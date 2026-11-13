<?php

namespace App\Services\Ai;

use App\Models\AiLog;
use App\Models\KnowledgeChunk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Tanya-jawab atas isi dokumen Pengetahuan (RAG).
 *
 * Pencarian: kemiripan embedding (bila bge-m3 tersedia) digabung dengan
 * pencarian teks Postgres berbahasa Indonesia; hanya potongan yang skornya
 * dekat dengan potongan terbaik yang dipakai. Jawaban: LLM lokal hanya
 * boleh memakai potongan yang ditemukan dan wajib menyebut nomor sumbernya;
 * bila LLM mati, pengguna tetap mendapat potongan paling relevan + tautan.
 */
class KnowledgeSearch
{
    private const MIN_SIMILARITY = 0.45;

    /**
     * Sebuah potongan dipakai hanya bila skornya paling banyak sebesar ini
     * di bawah skor potongan terbaik. Sebelumnya dibatasi 2 potongan per dokumen;
     * akibatnya daftar syarat di SOP server terbuang dan digantikan potongan
     * SOP lain yang kurang relevan, lalu jawabannya tercampur (uji 13 Nov 2026).
     */
    private const RELEVANCE_MARGIN = 0.08;

    public function __construct(private LocalLlm $llm = new LocalLlm())
    {
    }

    /**
     * @return array{answer: string, engine: string, sources: array<int, array{id: int, title: string, code: string, page: ?int, snippet: string, url: ?string, score: float}>}
     */
    public function ask(string $question): array
    {
        $startedAt = microtime(true);
        $question = trim($question);
        $hits = $this->retrieve($question, 5);

        $sources = $hits->values()->map(fn ($hit, $i) => [
            'n' => $i + 1,
            'id' => $hit['chunk']->knowledgeAsset->id,
            'title' => $hit['chunk']->knowledgeAsset->title,
            'code' => $hit['chunk']->knowledgeAsset->record_code,
            'page' => $hit['chunk']->page_number,
            'snippet' => Str::limit($hit['chunk']->content, 280),
            'url' => $this->documentUrl($hit['chunk']),
            'score' => round($hit['score'], 3),
        ])->all();

        if ($hits->isEmpty()) {
            $answer = 'Tidak ditemukan dokumen pengetahuan yang membahas pertanyaan ini. Coba kata lain, atau tambahkan dokumennya di tab Aset Pengetahuan.';
            $engine = $this->retrievalEngine();
        } else {
            $answer = $this->generateAnswer($question, $hits);
            $engine = $answer !== null
                ? $this->llm->chatEngineName() . ' + ' . $this->retrievalEngine()
                : $this->retrievalEngine();
            $answer ??= 'Model bahasa sedang tidak aktif, jadi jawaban ringkas tidak dapat dibuat. Berikut bagian dokumen yang paling relevan.';
        }

        AiLog::record('tanya_pengetahuan', $engine, null, $question, $answer, $startedAt);

        return ['answer' => $answer, 'engine' => $engine, 'sources' => $sources];
    }

    /** @return Collection<int, array{chunk: KnowledgeChunk, score: float}> */
    public function retrieve(string $question, int $limit = 5): Collection
    {
        $scored = collect();

        $queryVector = $this->llm->embed([$question])[0] ?? null;
        if ($queryVector) {
            KnowledgeChunk::query()
                ->whereNotNull('embedding')
                ->whereHas('knowledgeAsset')
                ->select(['id', 'embedding'])
                ->chunkById(500, function ($rows) use ($queryVector, $scored) {
                    foreach ($rows as $row) {
                        $similarity = LocalLlm::cosine($queryVector, $row->embedding);
                        if ($similarity >= self::MIN_SIMILARITY) {
                            $scored[$row->id] = $similarity;
                        }
                    }
                });
        }

        // Pencarian teks berbahasa Indonesia: jalur utama bila embedding
        // mati, dan penguat untuk istilah persis (mis. nama aplikasi).
        $keywordHits = KnowledgeChunk::query()
            ->whereHas('knowledgeAsset')
            ->whereRaw("to_tsvector('indonesian', content) @@ websearch_to_tsquery('indonesian', ?)", [$question])
            ->orderByRaw("ts_rank(to_tsvector('indonesian', content), websearch_to_tsquery('indonesian', ?)) DESC", [$question])
            ->limit($limit * 2)
            ->pluck('id');

        if ($keywordHits->isEmpty() && ! $queryVector) {
            // websearch_to_tsquery mensyaratkan SEMUA kata; longgarkan jadi OR.
            $terms = collect(preg_split('/\s+/u', Str::lower($question)))
                ->map(fn ($w) => preg_replace('/[^\p{L}\p{N}]/u', '', $w))
                ->filter(fn ($w) => mb_strlen($w) >= 4)
                ->take(8);
            if ($terms->isNotEmpty()) {
                $keywordHits = KnowledgeChunk::query()
                    ->whereHas('knowledgeAsset')
                    ->whereRaw("to_tsvector('indonesian', content) @@ to_tsquery('indonesian', ?)", [$terms->implode(' | ')])
                    ->orderByRaw("ts_rank(to_tsvector('indonesian', content), to_tsquery('indonesian', ?)) DESC", [$terms->implode(' | ')])
                    ->limit($limit * 2)
                    ->pluck('id');
            }
        }

        foreach ($keywordHits as $rank => $id) {
            $bonus = 0.15 * (1 - $rank / max(1, $keywordHits->count()));
            $scored[$id] = ($scored[$id] ?? ($queryVector ? self::MIN_SIMILARITY : 0.5)) + $bonus;
        }

        $ranked = $scored->sortDesc();
        $best = $ranked->first();
        $top = $ranked->filter(fn ($score) => $score >= $best - self::RELEVANCE_MARGIN)->take($limit);
        $chunks = KnowledgeChunk::with('knowledgeAsset:id,title,record_code,document_path')->findMany($top->keys())->keyBy('id');

        return $top->map(fn ($score, $id) => ['chunk' => $chunks[$id] ?? null, 'score' => $score])
            ->filter(fn ($hit) => $hit['chunk'] !== null)
            ->values();
    }

    public function retrievalEngine(): string
    {
        return $this->llm->embedAvailable() ? 'pencarian semantik ' . config('ai.embed_model') : 'pencarian kata kunci';
    }

    private function generateAnswer(string $question, Collection $hits): ?string
    {
        // Nomor halaman tidak dikirim ke model: model kecil sering menyalin
        // nomor yang salah. Halaman yang benar sudah tampil di daftar sumber.
        $context = $hits->values()->map(
            fn ($hit, $i) => '[' . ($i + 1) . '] ' . $hit['chunk']->knowledgeAsset->title . ":\n" . $hit['chunk']->content
        )->implode("\n\n");

        $answer = $this->llm->generate(
            "Sumber:\n{$context}\n\nPertanyaan: {$question}\n\n"
            . 'Tulis jawabannya langsung, tanpa judul atau label apa pun, paling banyak 100 kata. '
            . 'Bila sumber berisi daftar syarat atau langkah yang ditanyakan, salin butirnya sebagai daftar bernomor tanpa menambah butir, langkah, atau saran sendiri. '
            . 'Pakai hanya sumber yang benar-benar menjawab pertanyaan dan abaikan sumber lain. '
            . 'Sebutkan nomor sumber dalam kurung siku di akhir kalimat yang memakainya, misalnya [1]; jangan menulis nomor halaman. '
            . 'Bila jawabannya tidak ada di sumber, tulis hanya kalimat ini: Tidak ditemukan di dokumen yang tersedia.',
            false,
            'Anda petugas layanan pengetahuan di dinas pemerintahan Indonesia. Jawab dalam Bahasa Indonesia baku, '
            . 'HANYA berdasarkan sumber yang diberikan. Jangan menambah informasi, langkah, atau saran yang tidak tertulis di sumber.'
        );

        return is_string($answer) ? Str::limit(self::tidy($answer), 1500) : null;
    }

    /**
     * Merapikan kebiasaan model kecil: label "Jawaban:"/"Daftar:", nomor
     * sumber yang berdiri sendiri di satu baris, kalimat tambahan setelah
     * menyatakan tidak ditemukan, dan baris kosong ganda.
     */
    public static function tidy(string $answer): string
    {
        $answer = preg_replace('/^[ \t]*(jawaban|daftar)[ \t]*:[ \t]*/imu', '', $answer) ?? $answer;
        $answer = preg_replace('/\n\s*((?:\[\d+\]\s*)+)(?=\n|$)/u', ' $1', $answer) ?? $answer;
        $answer = trim(preg_replace("/\n\s*\n+/u", "\n", $answer) ?? $answer);

        if (preg_match('/^tidak ditemukan di dokumen yang tersedia/iu', $answer)) {
            return 'Tidak ditemukan di dokumen yang tersedia.';
        }

        return $answer;
    }

    private function documentUrl(KnowledgeChunk $chunk): ?string
    {
        $path = $chunk->knowledgeAsset->document_path;

        return $path ? Storage::disk('public')->url($path) . ($chunk->page_number ? '#page=' . $chunk->page_number : '') : null;
    }
}
