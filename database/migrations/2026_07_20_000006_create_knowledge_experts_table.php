<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extracted from "Manajemen_Pengetahuan_BSSN.xlsx" — sheet "2. Peta Keahlian
 * Pegawai", one of the 4 "komponen utama Manajemen Pengetahuan" described in
 * the BSSN design guide. A standalone roster (no FK) — narasumber_id on
 * knowledge_activities points back here instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_experts', function (Blueprint $table) {
            $table->id();

            $table->string('record_code', 20)->unique(); // PKP-2026-0001
            $table->string('legacy_code', 30)->nullable()->index(); // NIP

            $table->string('nama_pegawai');
            $table->string('jabatan_unit')->nullable();
            $table->text('keahlian_spesifik')->nullable();
            $table->text('sertifikasi_lisensi')->nullable();
            $table->string('peran_km', 30)->nullable(); // Mentor/SME, Kontributor Utama, Kontributor

            $table->string('verification_status', 30)->default('tervalidasi');

            $table->jsonb('dynamic_data')->default('{}');

            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('CREATE INDEX knowledge_experts_dynamic_data_gin_idx ON knowledge_experts USING GIN (dynamic_data)');
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_experts');
    }
};
