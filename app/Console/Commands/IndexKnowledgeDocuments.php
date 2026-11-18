<?php

namespace App\Console\Commands;

use App\Models\KnowledgeAsset;
use App\Services\Ai\KnowledgeIndexer;
use App\Services\Ai\LocalLlm;
use Illuminate\Console\Command;

class IndexKnowledgeDocuments extends Command
{
    protected $signature = 'pengetahuan:indeks {--semua : Indeks ulang semua dokumen, termasuk yang sudah terindeks}';

    protected $description = 'Indeks isi PDF Aset Pengetahuan untuk pencarian semantik dan tag otomatis';

    public function handle(KnowledgeIndexer $indexer, LocalLlm $llm): int
    {
        $this->line('Model embedding: ' . ($llm->embedAvailable() ? config('ai.embed_model') . ' (aktif)' : 'tidak aktif — hanya pencarian kata kunci'));
        $this->line('Model teks: ' . ($llm->chatAvailable() ? config('ai.chat_model') . ' (aktif)' : 'tidak aktif — tag dari kosakata saja'));

        $assets = KnowledgeAsset::query()
            ->whereNotNull('document_path')
            ->when(! $this->option('semua'), fn ($q) => $q->where(fn ($w) => $w->whereNull('index_status')->orWhere('index_status', '!=', 'terindeks')))
            ->orderBy('id')
            ->get();

        if ($assets->isEmpty()) {
            $this->info('Tidak ada dokumen yang perlu diindeks.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($assets->count());
        foreach ($assets as $asset) {
            $indexer->index($asset);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        $this->table(['Kode', 'Judul', 'Status', 'Potongan', 'Tag'], $assets->map(fn ($a) => [
            $a->record_code,
            mb_strimwidth($a->title, 0, 40, '…'),
            $a->fresh()->index_status,
            $a->chunks()->count(),
            implode(', ', $a->fresh()->ai_tags ?? []),
        ]));

        return self::SUCCESS;
    }
}
