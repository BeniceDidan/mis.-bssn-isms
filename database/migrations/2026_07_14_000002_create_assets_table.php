<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unifies the 5 asset category sheets from "Daftar Inventaris Aset 2026.xlsx"
 * (Data & Informasi, Perangkat Lunak, Perangkat Keras, Sarana Pendukung,
 * SDM & Pihak Ketiga) into one table. Columns that exist identically across
 * all 5 sheets become static SQL columns; category-specific columns (which
 * differ per sheet and change shape every year) live in dynamic_data JSONB.
 * See the Phase 1 chat summary for the full column mapping.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();

            // Custom formatted display ID, e.g. AST-2026-0001 (rule 6).
            $table->string('asset_code', 20)->unique();

            // Original per-category code from the source spreadsheet
            // (DI-001, PL-007, PK-014, SP-003, PS-009), kept for traceability.
            $table->string('legacy_code', 20)->nullable()->index();

            $table->string('category', 30)->index(); // App\Enums\AssetCategory
            $table->string('sub_classification', 120);
            $table->string('name');
            $table->string('owner')->nullable();
            $table->string('location')->nullable();
            $table->string('status', 50)->nullable();
            $table->string('criticality_level', 20)->nullable()->index(); // App\Enums\Level
            $table->unsignedSmallInteger('reference_year')->nullable();

            // Anti-loss column: every category-specific / volatile indicator
            // (CIA scores, app URLs, hardware specs, NIP, jabatan, etc.)
            $table->jsonb('dynamic_data')->default('{}');

            $table->timestamps();
            $table->softDeletes();
        });

        // GIN index so the global search (rule 4.1) can query inside JSONB
        // efficiently instead of falling back to a sequential scan.
        DB::statement('CREATE INDEX assets_dynamic_data_gin_idx ON assets USING GIN (dynamic_data)');
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
