<?php

namespace App\Console\Commands;

use App\Models\KnowledgeAsset;
use App\Services\Ai\KnowledgeIndexer;
use App\Services\Ai\LocalLlm;
use Illuminate\Console\Command;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Mengimpor banyak PDF sekaligus ke Manajemen Pengetahuan lalu langsung
 * mengindeksnya untuk Tanya Dokumen. Dipakai saat Diskominfo menyerahkan
 * satu folder SOP/kebijakan — mengunggah lewat formulir satu per satu tidak
 * praktis. Berkas yang nama aslinya sudah pernah diimpor dilewati, jadi
 * perintah ini aman dijalankan ulang setelah folder ditambah.
 */
class ImportKnowledgeFolder extends Command
{
    protected $signature = 'pengetahuan:impor
        {folder : Folder berisi PDF (subfolder ikut dibaca)}
        {--kategori= : Kategori untuk semua dokumen}
        {--unit= : Unit pemilik untuk semua dokumen}
        {--akses=Internal : Tingkat akses (Publik, Internal, Terbatas)}';

    protected $description = 'Impor semua PDF di satu folder ke Manajemen Pengetahuan dan indeks untuk Tanya Dokumen';

    public function handle(KnowledgeIndexer $indexer, LocalLlm $llm): int
    {
        $folder = rtrim($this->argument('folder'), '\\/');
        if (! is_dir($folder)) {
            $this->error("Folder tidak ditemukan: {$folder}");

            return self::FAILURE;
        }

        $files = collect(iterator_to_array(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS))))
            ->filter(fn ($f) => $f->isFile() && Str::lower($f->getExtension()) === 'pdf')
            ->map(fn ($f) => $f->getPathname())
            ->sort()
            ->values();

        if ($files->isEmpty()) {
            $this->warn('Tidak ada PDF di folder itu.');

            return self::SUCCESS;
        }

        $this->line("{$files->count()} PDF ditemukan. Model embedding: " . ($llm->embedAvailable() ? 'aktif' : 'tidak aktif (hanya kata kunci)'));
        $started = microtime(true);
        $counts = ['diimpor' => 0, 'dilewati' => 0, 'tanpa teks' => 0, 'potongan' => 0];
        $problems = [];

        $bar = $this->output->createProgressBar($files->count());
        foreach ($files as $path) {
            $original = basename($path);
            if (KnowledgeAsset::where('document_original_name', $original)->exists()) {
                $counts['dilewati']++;
                $bar->advance();

                continue;
            }

            $title = trim(preg_replace('/[_\s]+/u', ' ', pathinfo($original, PATHINFO_FILENAME)));
            $stored = Storage::disk('public')->putFileAs(
                'knowledge-documents',
                new File($path),
                Str::slug($title) . '-' . Str::lower(Str::random(6)) . '.pdf'
            );

            $asset = KnowledgeAsset::create([
                'title' => $title,
                'knowledge_type' => 'explicit',
                'category' => $this->option('kategori'),
                'owner_unit' => $this->option('unit'),
                'access_level' => $this->option('akses'),
                'document_path' => $stored,
                'document_original_name' => $original,
                'verification_status' => 'tervalidasi',
                'dynamic_data' => [],
            ]);
            $asset = $indexer->index($asset)->fresh();

            $counts['diimpor']++;
            $counts['potongan'] += $asset->chunks()->count();
            if (! in_array($asset->index_status, ['terindeks', 'kata_kunci'], true)) {
                $counts['tanpa teks']++;
                $problems[] = [$original, $asset->index_status === 'tanpa_teks' ? 'tidak ada teks (hasil scan?)' : 'PDF tidak terbaca'];
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        $seconds = microtime(true) - $started;
        $this->table(['Diimpor', 'Dilewati (sudah ada)', 'Tanpa teks', 'Potongan baru', 'Waktu'], [[
            $counts['diimpor'], $counts['dilewati'], $counts['tanpa teks'], $counts['potongan'],
            sprintf('%.0f dtk (%.1f dtk/dokumen)', $seconds, $seconds / max(1, $counts['diimpor'])),
        ]]);
        if ($problems) {
            $this->warn('Dokumen yang tidak bisa ditanya (perlu PDF berteks):');
            $this->table(['Berkas', 'Masalah'], $problems);
        }

        return self::SUCCESS;
    }
}
