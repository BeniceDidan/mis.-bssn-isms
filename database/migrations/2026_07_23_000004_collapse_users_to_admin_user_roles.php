<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Simplifies the 3-role split (admin/pengelola/pimpinan) down to admin/user
 * per Kominfo's request — real staff accounts are only ever "admin" or
 * "user" here, the read-only pimpinan role and the pengelola label are
 * gone. Behavior for the new "user" role is unchanged from the old
 * pengelola: can write, submissions still go through Admin verification
 * (see User::canWrite() / isAdmin()).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereIn('role', ['pengelola', 'pimpinan'])->update(['role' => 'user']);

        DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'user'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'pengelola'");
    }
};
