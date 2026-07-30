<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Generalizes asset_verifications (single-table pilot) into a polymorphic
 * history shared by all 6 modules that go through the BPMN verification
 * workflow, mirroring how activity_logs already works for the audit trail.
 * Any existing rows in asset_verifications are carried over before that
 * table is dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->string('verifiable_type', 40);
            $table->unsignedBigInteger('verifiable_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20); // tervalidasi / ditolak
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['verifiable_type', 'verifiable_id']);
        });

        if (Schema::hasTable('asset_verifications')) {
            DB::table('asset_verifications')->orderBy('id')->each(function ($row) {
                DB::table('verifications')->insert([
                    'verifiable_type' => 'asset',
                    'verifiable_id' => $row->asset_id,
                    'user_id' => $row->user_id,
                    'status' => $row->status,
                    'notes' => $row->notes,
                    'created_at' => $row->created_at,
                ]);
            });

            Schema::dropIfExists('asset_verifications');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('verifications');
    }
};
