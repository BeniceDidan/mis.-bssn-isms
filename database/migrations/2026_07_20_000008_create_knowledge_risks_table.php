<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extracted from "Manajemen_Pengetahuan_BSSN.xlsx" — sheet "4. Register
 * Risiko KM". knowledge_asset_id links to knowledge_assets (the Pengetahuan
 * module's own asset register, AP-xxx codes) — not the main assets table —
 * since this is knowledge-management risk (e.g. losing tacit knowledge),
 * conceptually distinct from IT-asset risk already covered by the main
 * Risk module.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_risks', function (Blueprint $table) {
            $table->id();

            $table->string('record_code', 20)->unique(); // RKM-2026-0001
            $table->string('legacy_code', 20)->nullable()->index(); // RKM-001

            $table->foreignId('knowledge_asset_id')->nullable()->constrained('knowledge_assets')->nullOnDelete();

            $table->text('pernyataan_risiko');
            $table->text('akar_penyebab')->nullable();
            $table->string('area_dampak')->nullable();
            $table->unsignedTinyInteger('dampak')->nullable(); // 1-5
            $table->unsignedTinyInteger('kemungkinan')->nullable(); // 1-5
            $table->unsignedSmallInteger('skor_risiko_bawaan')->nullable(); // dampak x kemungkinan
            $table->string('tingkat_risiko_bawaan', 20)->nullable(); // Level enum, via BSSN 5x5 matrix
            $table->text('rencana_mitigasi')->nullable();
            $table->string('penanggung_jawab')->nullable(); // free text — sample data is unit names, not people
            $table->string('tingkat_residual_risk', 20)->nullable(); // Level enum

            $table->string('verification_status', 30)->default('tervalidasi');

            $table->jsonb('dynamic_data')->default('{}');

            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('CREATE INDEX knowledge_risks_dynamic_data_gin_idx ON knowledge_risks USING GIN (dynamic_data)');
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_risks');
    }
};
