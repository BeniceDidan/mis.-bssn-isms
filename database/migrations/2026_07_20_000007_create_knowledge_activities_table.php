<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extracted from "Manajemen_Pengetahuan_BSSN.xlsx" — sheet "3. Log Aktivitas
 * Berbagi". narasumber_id is resolved against knowledge_experts by exact
 * name match at import time (reliable here since both sheets share the same
 * source document and naming) — narasumber_name is always kept regardless,
 * so nothing is lost if the match misses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_activities', function (Blueprint $table) {
            $table->id();

            $table->string('record_code', 20)->unique(); // KLB-2026-0001
            $table->string('legacy_code', 20)->nullable()->index(); // KKM-001

            $table->date('tanggal_pelaksanaan')->nullable();
            $table->string('nama_kegiatan');
            $table->text('materi_topik')->nullable();
            $table->string('narasumber_name')->nullable();
            $table->foreignId('narasumber_id')->nullable()->constrained('knowledge_experts')->nullOnDelete();
            $table->unsignedInteger('jumlah_peserta')->nullable();
            $table->string('link_bukti')->nullable();

            $table->string('verification_status', 30)->default('tervalidasi');

            $table->jsonb('dynamic_data')->default('{}');

            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('CREATE INDEX knowledge_activities_dynamic_data_gin_idx ON knowledge_activities USING GIN (dynamic_data)');
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_activities');
    }
};
