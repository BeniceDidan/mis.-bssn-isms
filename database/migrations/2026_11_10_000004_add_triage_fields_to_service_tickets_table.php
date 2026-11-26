<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Helpdesk: kategori layanan, prioritas (P1–P4 dari dampak × urgensi),
 * draf balasan untuk operator/programmer, dan jejak triase AI.
 * Semua nullable — tiket lama dari impor Excel tetap valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->string('category', 40)->nullable()->after('issue')->index();
            $table->string('urgency', 20)->nullable()->after('impact_level');
            $table->string('priority', 5)->nullable()->after('urgency')->index();
            $table->text('draft_reply')->nullable()->after('resolution');
            $table->jsonb('triage')->nullable()->after('draft_reply');
        });
    }

    public function down(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->dropColumn(['category', 'urgency', 'priority', 'draft_reply', 'triage']);
        });
    }
};
