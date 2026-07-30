<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CIA-triad scores feeding KritikalitasService (see Panduan Perancangan
 * Aplikasi Web Inventarisasi dan Manajemen Risiko Aset TIK, Bab 9 & 11) —
 * nullable so the 16 existing Excel-imported assets keep their manually
 * set criticality_level unchanged until someone fills these in.
 *
 * verification_status defaults to 'tervalidasi' so existing rows and
 * future Excel imports stay valid without a backfill; only non-admin
 * saves through the web form flip it to 'menunggu_verifikasi'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->unsignedTinyInteger('confidentiality_score')->nullable()->after('criticality_level');
            $table->unsignedTinyInteger('integrity_score')->nullable()->after('confidentiality_score');
            $table->unsignedTinyInteger('availability_score')->nullable()->after('integrity_score');
            $table->string('verification_status', 20)->default('tervalidasi')->index()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['confidentiality_score', 'integrity_score', 'availability_score', 'verification_status']);
        });
    }
};
