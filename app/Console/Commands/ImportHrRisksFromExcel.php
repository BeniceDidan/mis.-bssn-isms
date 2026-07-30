<?php

namespace App\Console\Commands;

use App\Imports\HrRiskImport;
use Illuminate\Console\Command;

class ImportHrRisksFromExcel extends Command
{
    protected $signature = 'isms:import-hr-risks {path : Absolute path to the Manajemen SDM .xlsx file}';

    protected $description = 'Upserts rows from the "Register Risiko Otomatis" Excel sheet into the hr_risks table.';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $import = new HrRiskImport();
        $import->run($path);

        $summary = $import->summary();

        $this->info("Dibuat: {$summary->created} | Diperbarui: {$summary->updated} | Error: " . count($summary->errors));

        foreach ($summary->errors as $error) {
            $this->warn("[{$error['sheet']} baris {$error['row']}] {$error['message']}");
        }

        return self::SUCCESS;
    }
}
