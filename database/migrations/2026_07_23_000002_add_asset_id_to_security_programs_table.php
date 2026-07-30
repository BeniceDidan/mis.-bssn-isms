<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Closes a real design gap flagged by the user: Manajemen Keamanan
 * Informasi has a genuine per-asset role ("mengamankan data dari aset
 * tersebut supaya aman") just like Risiko/Perubahan/Data & Informasi, so it
 * needed the same real foreign key those three already have — not just a
 * free-text note in dynamic_data. Nullable because the original 8-row work
 * plan (Program Kerja SPBE) genuinely isn't per-asset; only records that
 * legitimately target one asset (like the SPBE import's per-control rows,
 * or the RiskEscalationResponseService cascade) will have it set.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_programs', function (Blueprint $table) {
            $table->foreignId('asset_id')->nullable()->after('legacy_code')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('security_programs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('asset_id');
        });
    }
};
