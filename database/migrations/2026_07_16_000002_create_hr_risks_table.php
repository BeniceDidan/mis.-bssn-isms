<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extracted from "8. Manajemen SDM.xlsx" — sheet "Register Risiko
 * Otomatis". Simpler/flatter than the other risk registers (no separate
 * "decision"/"priority" columns), and its "Aset" column names a person or
 * third party, not an IT asset — so unlike changes/data_informations there
 * is no asset_id FK here, just a free-text subject.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_risks', function (Blueprint $table) {
            $table->id();

            $table->string('record_code', 20)->unique(); // SDM-2026-0001
            $table->string('legacy_code', 20)->nullable()->index(); // R-SDM-001

            $table->string('subject'); // "Aset" — person or third party name
            $table->string('title'); // Ancaman
            $table->string('category', 100)->nullable(); // Kategori Risiko SPBE
            $table->string('inherent_risk_level', 20)->nullable()->index(); // App\Enums\Level
            $table->string('status', 50)->default('diajukan')->index(); // Status Risiko Sisa
            $table->string('pic')->nullable(); // Penanggung Jawab
            $table->date('target_date')->nullable(); // often a quarter string like "Q2 2026" in the source, not always a real date — see HrRiskImport

            $table->jsonb('dynamic_data')->default('{}');

            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('CREATE INDEX hr_risks_dynamic_data_gin_idx ON hr_risks USING GIN (dynamic_data)');
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_risks');
    }
};
