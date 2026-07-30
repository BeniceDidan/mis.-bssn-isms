<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\Change;
use App\Models\DataInformation;
use App\Models\Risk;
use App\Models\SecurityProgram;
use App\Models\ServiceTicket;
use Illuminate\Support\Facades\Auth;

/**
 * Continues the correlation chain past Risiko, per the user's own flow:
 * SDM/Aset -> Risiko (level naik, sudah ada lewat RiskLevelService) ->
 * Keamanan Informasi (solusi) -> Perubahan (implementasi solusi) -> Layanan
 * (pantau dampak ke layanan yang terpasang di aset yang sama) -> Data &
 * Informasi (validasi akhir). Fires once a Risk sits at Tinggi/Kritis.
 *
 * Every record this creates is tagged in dynamic_data.auto_generated_from_risk
 * with the Risk's own risk_code, so a re-save never spawns duplicates. Every
 * one starts as verification_status = 'menunggu_verifikasi' (or, for
 * ServiceTicket which has no verification workflow, status = 'baru') —
 * these are suggested follow-ups for a human to confirm or reject through
 * the normal review flow, never treated as auto-final.
 */
class RiskEscalationResponseService
{
    public function respond(Risk $risk): void
    {
        if (! in_array($risk->risk_level?->value, ['tinggi', 'kritis'], true)) {
            return;
        }

        $this->ensureSecurityProgram($risk);
        $this->ensureChange($risk);
        $this->ensureServiceTickets($risk);
        $this->ensureDataInformation($risk);
    }

    private function alreadyGenerated(string $modelClass, Risk $risk): bool
    {
        return $modelClass::where('dynamic_data->auto_generated_from_risk', $risk->risk_code)->exists();
    }

    private function ensureSecurityProgram(Risk $risk): void
    {
        if ($this->alreadyGenerated(SecurityProgram::class, $risk)) {
            return;
        }

        $program = SecurityProgram::create([
            'asset_id' => $risk->asset_id,
            'program_kerja' => "Tindak lanjut keamanan untuk risiko \"{$risk->title}\" (level {$risk->risk_level->label()}).",
            'personnel_ref' => $risk->personnel_ref,
            'verification_status' => 'menunggu_verifikasi',
            'dynamic_data' => ['auto_generated_from_risk' => $risk->risk_code],
        ]);

        $this->logBridge('security_program', $program->id, "Program kerja keamanan dibuat otomatis karena Risiko {$risk->risk_code} naik ke level {$risk->risk_level->label()}.");
    }

    private function ensureChange(Risk $risk): void
    {
        if ($this->alreadyGenerated(Change::class, $risk)) {
            return;
        }

        $change = Change::create([
            'asset_id' => $risk->asset_id,
            'title' => "Implementasi solusi keamanan untuk risiko \"{$risk->title}\".",
            'status' => 'diajukan',
            'personnel_ref' => $risk->personnel_ref,
            'verification_status' => 'menunggu_verifikasi',
            'dynamic_data' => ['auto_generated_from_risk' => $risk->risk_code],
        ]);

        $this->logBridge('change', $change->id, "Perubahan diajukan otomatis untuk menindaklanjuti Risiko {$risk->risk_code}.");

        if ($risk->asset_id) {
            Asset::where('id', $risk->asset_id)->update(['updated_at' => now()]);
        }
    }

    private function ensureServiceTickets(Risk $risk): void
    {
        if (! $risk->asset_id || $this->alreadyGenerated(ServiceTicket::class, $risk)) {
            return;
        }

        $asset = $risk->relationLoaded('asset') ? $risk->asset : Asset::find($risk->asset_id);
        if (! $asset) {
            return;
        }

        foreach ($asset->services as $service) {
            $ticket = ServiceTicket::create([
                'service_id' => $service->id,
                'reported_at' => now(),
                'requester_name' => 'Sistem (otomatis)',
                'issue' => "Risiko {$risk->risk_level->label()} terdeteksi pada aset terkait: \"{$risk->title}\".",
                'impact_level' => $risk->risk_level->value,
                'related_risk_text' => $risk->risk_code,
                'status' => 'baru',
                'dynamic_data' => ['auto_generated_from_risk' => $risk->risk_code],
            ]);

            $this->logBridge('service', $service->id, "Tiket layanan #{$ticket->id} dibuat otomatis karena Risiko {$risk->risk_code} naik ke level {$risk->risk_level->label()}.");
        }
    }

    private function ensureDataInformation(Risk $risk): void
    {
        if ($this->alreadyGenerated(DataInformation::class, $risk)) {
            return;
        }

        $record = DataInformation::create([
            'asset_id' => $risk->asset_id,
            'title' => "Tinjauan data & informasi terkait risiko \"{$risk->title}\".",
            'status' => 'diajukan',
            'personnel_ref' => $risk->personnel_ref,
            'verification_status' => 'menunggu_verifikasi',
            'dynamic_data' => ['auto_generated_from_risk' => $risk->risk_code],
        ]);

        $this->logBridge('data_information', $record->id, "Catatan Data & Informasi dibuat otomatis untuk validasi akhir atas Risiko {$risk->risk_code}.");
    }

    private function logBridge(string $loggableType, int $loggableId, string $note): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => $loggableType,
            'loggable_id' => $loggableId,
            'action_type' => 'created',
            'old_values' => [],
            'new_values' => [
                'bridge_note' => $note,
                'bridge_module' => 'Manajemen Risiko',
            ],
            'performed_at' => now(),
        ]);
    }
}
