<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Central, immutable audit trail for every ISMS module (rule: "Enterprise
 * Audit Trail" — no CRUD action may happen without a recorded before/after
 * state). Uses polymorphic loggable_type/loggable_id so Risk, Incident,
 * Audit, Vendor, Policy, BCP/DRP and Awareness records can all write here
 * once those modules land, without new tables per module.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('loggable_type');
            $table->unsignedBigInteger('loggable_id');

            $table->string('action_type', 20); // created | updated | archived | restored | deleted
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->timestamp('performed_at')->useCurrent();

            $table->index(['loggable_type', 'loggable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
