<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extracted from "Manajemen_Layanan_SPBE_Terintegrasi_Aset_Risiko.xlsx" —
 * sheet "Katalog Layanan" is the master catalog and becomes this table, the
 * main CRUD entity for the 7th module (Manajemen Layanan). The workbook's
 * other two sheets (Log Operasional Layanan, Evaluasi Kinerja Layanan) are
 * child records of a service — see service_tickets/service_evaluations.
 *
 * "Aset TIK Utama Terkait" in the source sheet lists multiple assets per
 * service (unlike every other module's single asset_id), so that link is a
 * many-to-many pivot (service_assets) instead of a foreign key column here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();

            $table->string('service_code', 20)->unique(); // LYN-2026-0001
            $table->string('legacy_code', 20)->nullable()->index(); // LYN-01

            $table->string('name'); // Nama Layanan SPBE
            $table->text('description')->nullable(); // Deskripsi / Cakupan Layanan
            $table->string('owner_unit')->nullable(); // Pengelola Layanan (Owner)
            $table->string('technical_manager')->nullable(); // Pengelola Teknis
            $table->string('access_method')->nullable(); // Cara Akses
            $table->string('sla_target', 50)->nullable(); // Target Ketersediaan (SLA)
            $table->string('service_hours')->nullable(); // Waktu Pelayanan
            $table->string('status', 50)->nullable();

            $table->string('verification_status', 30)->default('tervalidasi');

            $table->jsonb('dynamic_data')->default('{}');

            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('CREATE INDEX services_dynamic_data_gin_idx ON services USING GIN (dynamic_data)');
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
