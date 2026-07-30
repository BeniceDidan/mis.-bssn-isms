<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extracted from sheet "Log Operasional Layanan" — an operational
 * ticket/incident log, many rows per service (unlike Evaluasi Kinerja,
 * which is closer to one snapshot per service). Read-only child data
 * displayed on the parent Service's detail panel, not its own CRUD screen.
 *
 * "Risiko TIK Terkait (Korelasi)" references a legacy risk numbering
 * ("R-02 (...)") that predates this app's Risk module and doesn't share a
 * code format with live RSK-2026-xxxx records, so it's kept as free text
 * rather than a fragile guessed foreign key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_tickets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('legacy_code', 20)->nullable()->index(); // Tiket ID (TK-001)

            $table->date('reported_at')->nullable(); // Tanggal Masuk
            $table->string('requester_name')->nullable(); // Nama Pemohon
            $table->text('issue')->nullable(); // Jenis Permintaan / Gangguan
            $table->string('impact_level', 20)->nullable(); // Tingkat Dampak (Kritikalitas)
            $table->string('related_risk_text')->nullable(); // Risiko TIK Terkait (Korelasi) — raw text
            $table->text('resolution')->nullable(); // Solusi / Tindakan Operasional
            $table->string('status', 50)->nullable(); // Status Tiket

            $table->jsonb('dynamic_data')->default('{}');

            $table->timestamps();
        });

        DB::statement('CREATE INDEX service_tickets_dynamic_data_gin_idx ON service_tickets USING GIN (dynamic_data)');
    }

    public function down(): void
    {
        Schema::dropIfExists('service_tickets');
    }
};
