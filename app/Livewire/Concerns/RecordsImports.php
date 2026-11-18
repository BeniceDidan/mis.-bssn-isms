<?php

namespace App\Livewire\Concerns;

use App\Imports\ImportSummary;
use App\Models\ImportLog;
use Carbon\CarbonInterface;
use Throwable;

/**
 * Mencatat setiap impor Excel ke import_logs supaya Asisten bisa menjawab
 * "impor yang barusan saya lakukan masuk ke mana?". Kegagalan mencatat
 * tidak boleh menggagalkan impor itu sendiri.
 */
trait RecordsImports
{
    protected function recordImport(string $moduleKey, ?string $fileName, ImportSummary $summary, CarbonInterface $startedAt): void
    {
        try {
            ImportLog::create([
                'user_id' => auth()->id(),
                'module_key' => $moduleKey,
                'file_name' => $fileName,
                'created_count' => $summary->created,
                'updated_count' => $summary->updated,
                'error_count' => count($summary->errors),
                'started_at' => $startedAt,
                'finished_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
