<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Insert-only verification history — deliberately NOT the 1:1
 * overwrite-on-reverify design the SIMT reference doc itself flags as a
 * known gap ("belum ada histori"). Every Setujui/Tolak action from the
 * admin verification queue gets its own row here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20); // tervalidasi / ditolak
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_verifications');
    }
};
