<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colleague-requested addition (not from the source Excel): let a
 * Pengetahuan entry carry a reference link and/or an uploaded PDF, since
 * some documentation lives as a shared link and some as a standalone file.
 * Both are optional and independent — a record can have either, both, or
 * neither.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_assets', function (Blueprint $table) {
            $table->string('external_link')->nullable()->after('access_level');
            $table->string('document_path')->nullable()->after('external_link');
            $table->string('document_original_name')->nullable()->after('document_path');
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_assets', function (Blueprint $table) {
            $table->dropColumn(['external_link', 'document_path', 'document_original_name']);
        });
    }
};
