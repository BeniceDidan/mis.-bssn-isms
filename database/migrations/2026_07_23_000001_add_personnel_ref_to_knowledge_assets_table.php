<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Closes a real gap: knowledge_assets ("Aset Pengetahuan" — the main
 * Manajemen Pengetahuan tab) was left out of both
 * 2026_07_21_000001_add_personnel_ref_to_all_modules_table and
 * 2026_07_21_000002_add_personnel_ref_to_knowledge_experts_table, even
 * though its "Penulis / Pemilik Knowledge" column is exactly the kind of
 * PIC-like field the Kode Personil system links on. Caught only when a
 * post-crash sanity query on this column threw "column does not exist" —
 * an earlier backfill attempt via ->update(['personnel_ref' => ...]) had
 * silently no-opped because the key wasn't in $fillable, and the diagnostic
 * printed the local PHP variable rather than re-reading the row, so the
 * failure went unnoticed until now.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_assets', function (Blueprint $table) {
            $table->string('personnel_ref', 50)->nullable()->index()->after('verification_status');
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_assets', function (Blueprint $table) {
            $table->dropColumn('personnel_ref');
        });
    }
};
