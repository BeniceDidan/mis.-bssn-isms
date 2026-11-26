<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan setiap impor Excel: siapa, modul apa, berkas apa, dan rentang
 * waktunya. Dipakai Asisten untuk menjawab "file Excel yang barusan saya
 * impor nyambung ke mana?" — data yang masuk dicari di activity_logs dalam
 * rentang started_at..finished_at milik pengguna yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('module_key', 30);
            $table->string('file_name')->nullable();
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('finished_at');
            $table->timestamps();

            $table->index(['user_id', 'finished_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
