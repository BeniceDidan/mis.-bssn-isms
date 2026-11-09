<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak setiap keluaran AI (fitur, mesin yang dipakai, ringkasan masukan,
 * keluaran, durasi). Transparansi: siapa pun bisa menelusuri apakah sebuah
 * skor/draf berasal dari LLM lokal atau dari mesin aturan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('feature', 40)->index();
            $table->string('engine', 120);
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('input_summary')->nullable();
            $table->text('output')->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_logs');
    }
};
