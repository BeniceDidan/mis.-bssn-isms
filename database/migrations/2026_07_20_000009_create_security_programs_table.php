<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extracted from "4. Manajemen Keamanan Informasi.xlsx" — the 8th and last
 * module. The source sheet is a 2-level outline ("C. Manajemen Keamanan
 * Informasi" heading over 5 numbered work-plan items), not the richer
 * 6-entity model (Pedoman/Perencanaan/Aset SPBE/Risk Register/Standar-SOP/
 * Kegiatan Peningkatan Kapasitas) the design guide envisions — those stay
 * future expansion until real data exists for them, same as every other
 * module's deferred-sheet precedent this session. No asset_id: the sheet
 * has no per-item asset reference (standalone, like HrRisk).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_programs', function (Blueprint $table) {
            $table->id();

            $table->string('record_code', 20)->unique(); // KIN-2026-0001
            $table->string('legacy_code', 10)->nullable()->index(); // item number "1".."5"

            $table->text('program_kerja');
            $table->string('kegiatan')->nullable();
            $table->string('pic')->nullable();

            $table->string('verification_status', 30)->default('tervalidasi');

            // Yearly target/realization columns (2021-2025 in the source
            // sheet, currently blank) — kept flexible here instead of
            // hardcoded year columns that would go stale.
            $table->jsonb('dynamic_data')->default('{}');

            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('CREATE INDEX security_programs_dynamic_data_gin_idx ON security_programs USING GIN (dynamic_data)');
    }

    public function down(): void
    {
        Schema::dropIfExists('security_programs');
    }
};
