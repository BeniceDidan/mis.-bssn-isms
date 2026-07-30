<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extracted from "3. Manajemen Data dan Informasi.xlsx" — sheet "Log
 * Manajemen Data & Informasi". Same risk-register shape as changes/risks
 * (SPBE governance template reused across modules): identify a data asset,
 * score inherent risk, plan treatment, score residual risk. asset_id is
 * the cross-module thread back to the asset the data risk concerns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_informations', function (Blueprint $table) {
            $table->id();

            $table->string('record_code', 20)->unique(); // MDI-2026-0001
            $table->string('legacy_code', 20)->nullable()->index(); // MDI-001 from the register

            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();

            $table->string('title'); // Ancaman / Kejadian Gangguan Data
            $table->string('risk_type', 20)->nullable(); // App\Enums\ChangeType (Positif/Negatif — same vocabulary)
            $table->string('category', 100)->nullable(); // Kategori Aset Data
            $table->string('priority', 50)->nullable(); // Prioritas Penanganan
            $table->string('inherent_risk_level', 20)->nullable()->index(); // App\Enums\Level
            $table->string('decision', 150)->nullable(); // Keputusan Penanganan Risiko
            $table->string('status', 50)->default('diajukan')->index(); // Status Kelayakan Layanan
            $table->string('pic')->nullable(); // Penanggung Jawab
            $table->date('target_date')->nullable();

            $table->jsonb('dynamic_data')->default('{}');

            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('CREATE INDEX data_informations_dynamic_data_gin_idx ON data_informations USING GIN (dynamic_data)');
    }

    public function down(): void
    {
        Schema::dropIfExists('data_informations');
    }
};
