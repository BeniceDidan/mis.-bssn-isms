<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rounds out the "Kode Personil" linking key (see
 * 2026_07_21_000001_add_personnel_ref_to_all_modules_table) on the one
 * table it missed: knowledge_experts (Peta Keahlian). The SDM <-> Peta
 * Keahlian pairing was the original name-guessing pilot
 * (CrossReferencesEmployeeNames) — it now moves to the same ID-only
 * matching as the other 7 modules, so this table needs the column too.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_experts', function (Blueprint $table) {
            $table->string('personnel_ref', 50)->nullable()->index()->after('verification_status');
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_experts', function (Blueprint $table) {
            $table->dropColumn('personnel_ref');
        });
    }
};
