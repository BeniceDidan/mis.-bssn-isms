<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extracted from sheet "Evaluasi Kinerja Layanan" — an SLA/performance
 * snapshot per service (uptime realized vs. target, incident count, MTTR,
 * a written recommendation). Read-only child data shown on the parent
 * Service's detail panel, same reasoning as service_tickets.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_evaluations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_id')->constrained()->cascadeOnDelete();

            $table->string('uptime_actual', 20)->nullable(); // Realisasi Uptime (%)
            $table->string('sla_target', 20)->nullable(); // Target SLA (%)
            $table->string('achievement_status', 50)->nullable(); // Status Capaian
            $table->unsignedInteger('incident_count')->nullable(); // Total Gangguan (Bulan Ini)
            $table->string('mttr', 50)->nullable(); // Rata-rata Waktu Resolusi (MTTR)
            $table->text('recommendation')->nullable(); // Rekomendasi Peningkatan Kontrol & Mitigasi

            $table->jsonb('dynamic_data')->default('{}');

            $table->timestamps();
        });

        DB::statement('CREATE INDEX service_evaluations_dynamic_data_gin_idx ON service_evaluations USING GIN (dynamic_data)');
    }

    public function down(): void
    {
        Schema::dropIfExists('service_evaluations');
    }
};
