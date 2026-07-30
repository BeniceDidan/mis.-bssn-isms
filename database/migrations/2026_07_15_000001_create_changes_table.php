<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extracted from "6. Manajemen Perubahan_Final_Sempurna_35_Kolom.xlsx" — a
 * change-management register that doubles as an inherent/residual risk
 * assessment per change (SPBE-style). Static columns are the ones used for
 * filtering/sorting/relations; the rest (impact/likelihood sub-scores,
 * rollback plan detail, residual-risk checkboxes, etc.) live in
 * dynamic_data, same anti-loss pattern as assets/risks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('changes', function (Blueprint $table) {
            $table->id();

            $table->string('change_code', 20)->unique(); // CHG-2026-0001

            // Original "Change No" from the source register (RFC-001, ...).
            $table->string('legacy_code', 20)->nullable()->index();

            // "Aset Terkait" cells are formatted "LEGACY_CODE: Nama Aset" —
            // resolved to a real FK during import when the code matches.
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();

            $table->string('title'); // Usulan Modifikasi Sistem
            $table->string('change_type', 20)->nullable(); // App\Enums\ChangeType
            $table->string('category', 50)->nullable(); // Kategori Perubahan (Major/Minor)
            $table->string('priority', 50)->nullable(); // Prioritas Eksekusi
            $table->string('inherent_risk_level', 20)->nullable()->index(); // App\Enums\Level
            $table->string('decision', 150)->nullable(); // Keputusan Penanganan Perubahan
            $table->string('status', 50)->default('diajukan')->index(); // Status Akhir Kelayakan
            $table->string('pic')->nullable(); // Penanggung Jawab
            $table->date('target_date')->nullable(); // Target/Jadwal Implementasi

            $table->jsonb('dynamic_data')->default('{}');

            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('CREATE INDEX changes_dynamic_data_gin_idx ON changes USING GIN (dynamic_data)');
    }

    public function down(): void
    {
        Schema::dropIfExists('changes');
    }
};
