<?php

namespace App\Console\Commands;

use App\Imports\AssetsImport;
use Illuminate\Console\Command;

class ImportAssetsFromExcel extends Command
{
    protected $signature = 'isms:import-assets {path : Absolute path to the Daftar Inventaris Aset .xlsx file}';

    protected $description = 'Upserts asset rows from the Kominfo asset inventory Excel template into the assets table.';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $import = new AssetsImport();
        $import->run($path);

        $summary = $import->summary();

        $this->info("Dibuat: {$summary->created} | Diperbarui: {$summary->updated} | Error: " . count($summary->errors));

        foreach ($summary->errors as $error) {
            $this->warn("[{$error['sheet']} baris {$error['row']}] {$error['message']}");
        }

        return self::SUCCESS;
    }
}
