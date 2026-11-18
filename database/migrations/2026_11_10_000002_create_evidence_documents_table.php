<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bukti dukung (PDF dari OPD/unit kerja) yang dinilai otomatis terhadap
 * rubrik tingkat kematangan di config/bukti_dukung.php. Hasil mesin hanya
 * saran; keputusan akhir ada di kolom reviewer_* yang diisi admin modul.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_documents', function (Blueprint $table) {
            $table->id();
            $table->string('record_code', 20)->unique();
            $table->string('module_key', 30)->index();
            $table->unsignedTinyInteger('target_level');
            $table->string('unit_name')->nullable();
            $table->string('title');

            $table->string('file_path');
            $table->string('original_name');
            $table->unsignedInteger('page_count')->default(0);
            $table->longText('extracted_text')->nullable();

            $table->unsignedTinyInteger('achieved_level')->default(0);
            $table->unsignedTinyInteger('score')->default(0);
            $table->jsonb('checks')->default('{}');
            $table->jsonb('warnings')->default('[]');
            $table->text('ai_notes')->nullable();
            $table->string('engine', 120)->nullable();
            $table->timestamp('assessed_at')->nullable();

            $table->string('reviewer_decision', 30)->nullable();
            $table->text('reviewer_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_documents');
    }
};
