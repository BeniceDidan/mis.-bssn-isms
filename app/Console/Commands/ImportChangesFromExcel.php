<?php

namespace App\Console\Commands;

use App\Imports\ChangeImport;
use Illuminate\Console\Command;

class ImportChangesFromExcel extends Command
{
    protected $signature = 'isms:import-changes {path : Absolute path to the Manajemen Perubahan .xlsx file}';

    protected $description = 'Upserts change-management rows from the "Log Manajemen Perubahan" Excel register into the changes table.';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $import = new ChangeImport();
        $import->run($path);

        $summary = $import->summary();

        $this->info("Dibuat: {$summary->created} | Diperbarui: {$summary->updated} | Error: " . count($summary->errors));

        foreach ($summary->errors as $error) {
            $this->warn("[{$error['sheet']} baris {$error['row']}] {$error['message']}");
        }

        return self::SUCCESS;
    }
}
