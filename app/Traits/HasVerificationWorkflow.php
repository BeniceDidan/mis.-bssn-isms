<?php

namespace App\Traits;

use App\Models\Verification;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Shared by every module that goes through the BPMN "Pemeriksaan data ->
 * ACC/Koreksi" workflow: Aset, Risiko, Perubahan, Data & Informasi, SDM,
 * Pengetahuan. Each carries its own verification_status column
 * (menunggu_verifikasi / tervalidasi / ditolak) plus an insert-only
 * history via the polymorphic `verifications` table — one shared audit
 * table for all 6 modules instead of a table per module.
 */
trait HasVerificationWorkflow
{
    public function verifications(): MorphMany
    {
        return $this->morphMany(Verification::class, 'verifiable')->latest('created_at');
    }
}
