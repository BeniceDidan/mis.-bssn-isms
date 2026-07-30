<?php

namespace App\Console\Commands;

use App\Imports\DataInformationImport;
use Illuminate\Console\Command;

class ImportDataInformationFromExcel extends Command
{
    protected $signature = 'isms:import-data-information {path : Absolute path to the Manajemen Data dan Informasi .xlsx file}';

    protected $description = 'Upserts rows from the "Log Manajemen Data & Informasi" Excel register into the data_informations table.';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $import = new DataInformationImport();
        $import->run($path);

        $summary = $import->summary();

        $this->info("Dibuat: {$summary->created} | Diperbarui: {$summary->updated} | Error: " . count($summary->errors));

        foreach ($summary->errors as $error) {
            $this->warn("[{$error['sheet']} baris {$error['row']}] {$error['message']}");
        }

        return self::SUCCESS;
    }
}
