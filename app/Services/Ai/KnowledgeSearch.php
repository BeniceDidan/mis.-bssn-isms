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
 * pencarian teks Postgres berbahasa Indonesia. Jawaban: LLM lokal hanya
 * boleh memakai potongan yang ditemukan dan wajib menyebut nomor sumbernya;
 * bila LLM mati, pengguna tetap mendapat potongan paling relevan + tautan.
 */
class KnowledgeSearch
{
    private const MIN_SIMILARITY = 0.45;

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

        $top = $scored->sortDesc()->take($limit * 2);
        $chunks = KnowledgeChunk::with('knowledgeAsset:id,title,record_code,document_path')->findMany($top->keys())->keyBy('id');

        // Maksimal 2 potongan per dokumen supaya sumber tidak didominasi satu PDF.
        $perDocument = [];

        return $top->map(fn ($score, $id) => ['chunk' => $chunks[$id] ?? null, 'score' => $score])
            ->filter(function ($hit) use (&$perDocument) {
                if (! $hit['chunk']) {
                    return false;
                }
                $docId = $hit['chunk']->knowledge_asset_id;
                $perDocument[$docId] = ($perDocument[$docId] ?? 0) + 1;

                return $perDocument[$docId] <= 2;
            })
            ->take($limit)
            ->values();
    }

    public function retrievalEngine(): string
    {
        return $this->llm->embedAvailable() ? 'pencarian semantik ' . config('ai.embed_model') : 'pencarian kata kunci';
    }

    private function generateAnswer(string $question, Collection $hits): ?string
    {
        $context = $hits->values()->map(function ($hit, $i) {
            $chunk = $hit['chunk'];

            return '[' . ($i + 1) . '] ' . $chunk->knowledgeAsset->title . ($chunk->page_number ? " (hal. {$chunk->page_number})" : '') . ":\n" . $chunk->content;
        })->implode("\n\n");

        $answer = $this->llm->generate(
            "Sumber:\n{$context}\n\nPertanyaan: {$question}\n\n"
            . 'Jawab ringkas (maksimal 120 kata). Bila berupa prosedur, tulis sebagai langkah bernomor. '
            . 'Sebutkan nomor sumber dalam kurung siku, misalnya [1]. Bila jawabannya tidak ada di sumber, katakan tidak ditemukan.',
            false,
            'Anda petugas layanan pengetahuan di dinas pemerintahan Indonesia. Jawab dalam Bahasa Indonesia baku, '
            . 'HANYA berdasarkan sumber yang diberikan. Jangan menambah informasi dari luar sumber.'
        );

        // Model kecil sering memberi baris kosong ganda di antara langkah.
        return is_string($answer) ? Str::limit(preg_replace("/\n\s*\n+/u", "\n", $answer) ?? $answer, 1500) : null;
    }

    private function documentUrl(KnowledgeChunk $chunk): ?string
    {
        $path = $chunk->knowledgeAsset->document_path;

        return $path ? Storage::disk('public')->url($path) . ($chunk->page_number ? '#page=' . $chunk->page_number : '') : null;
    }
}
