<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Gaada super admin yang menyetujui semua" — replaces the single
 * blanket-admin verification model with per-module admins. An admin's
 * approval power (see AdminVerificationQueue) is now scoped to exactly
 * one of the 8 module keys here; null for 'user' role accounts, for
 * whom this column is meaningless.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('admin_module', 30)->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('admin_module');
        });
    }
};
