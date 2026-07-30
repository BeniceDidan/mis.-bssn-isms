<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extracted from "5. Manajemen Pengetahuan.xlsx" — sheet "1. Register Aset
 * Pengetahuan" (the other 3 sheets in that workbook — Peta Keahlian
 * Pegawai, Log Aktivitas Berbagi, Register Risiko KM — are a good future
 * expansion but out of scope for this pass).
 *
 * asset_id does NOT come from the source file — it's added deliberately so
 * a knowledge/documentation entry can be linked back to the IT asset it
 * documents, closing the loop described in the integration flow (Aset ->
 * Risiko -> Perubahan -> updated Aset -> Pengetahuan/manual book).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_assets', function (Blueprint $table) {
            $table->id();

            $table->string('record_code', 20)->unique(); // KNW-2026-0001
            $table->string('legacy_code', 20)->nullable()->index(); // AP-001

            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();

            $table->string('title'); // Nama Aset Pengetahuan
            $table->string('knowledge_type', 20)->nullable(); // App\Enums\KnowledgeType (Tacit/Explicit)
            $table->string('category', 100)->nullable(); // Kategori Pengetahuan
            $table->string('owner_unit')->nullable(); // Unit Pemilik (Owner)
            $table->string('access_level', 100)->nullable(); // Tingkat Aksesibilitas
            $table->date('last_reviewed_at')->nullable(); // Tanggal Pembaruan Terakhir

            $table->jsonb('dynamic_data')->default('{}');

            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('CREATE INDEX knowledge_assets_dynamic_data_gin_idx ON knowledge_assets USING GIN (dynamic_data)');
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_assets');
    }
};
