<?php

namespace App\Console\Commands;

use App\Imports\KnowledgeImport;
use Illuminate\Console\Command;

class ImportKnowledgeFromExcel extends Command
{
    protected $signature = 'isms:import-knowledge {path : Absolute path to the Manajemen Pengetahuan .xlsx file}';

    protected $description = 'Upserts rows from the "1. Register Aset Pengetahuan" Excel sheet into the knowledge_assets table.';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $import = new KnowledgeImport();
        $import->run($path);

        $summary = $import->summary();

        $this->info("Dibuat: {$summary->created} | Diperbarui: {$summary->updated} | Error: " . count($summary->errors));

        foreach ($summary->errors as $error) {
            $this->warn("[{$error['sheet']} baris {$error['row']}] {$error['message']}");
        }

        return self::SUCCESS;
    }
}
