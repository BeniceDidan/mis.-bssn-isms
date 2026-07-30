<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the same verification workflow piloted on Aset to the other 5
 * built modules — Risiko, Perubahan, Data & Informasi, SDM, Pengetahuan —
 * so a pengelola's submit/edit on any of them goes through Admin review,
 * not just Aset. Defaults to 'tervalidasi' for the same reason as Aset:
 * existing rows and future Excel imports stay usable without a backfill.
 */
return new class extends Migration
{
    private const TABLES = ['risks', 'changes', 'data_informations', 'hr_risks', 'knowledge_assets'];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            // Not every table here has a 'status' column (knowledge_assets
            // doesn't), so this is appended at the end rather than
            // anchored with ->after() — column order isn't load-bearing.
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('verification_status', 20)->default('tervalidasi')->index();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('verification_status');
            });
        }
    }
};
