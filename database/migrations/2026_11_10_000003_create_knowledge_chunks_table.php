<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Potongan isi PDF Aset Pengetahuan untuk pencarian semantik (RAG).
 * Embedding disimpan sebagai jsonb (tanpa pgvector, supaya tidak perlu
 * ekstensi tambahan di Postgres bersama); kemiripan dihitung di PHP —
 * cukup untuk ratusan hingga beberapa ribu potongan. Indeks GIN teks
 * berbahasa Indonesia menjadi jalur cadangan saat model embedding mati.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_asset_id')->constrained('knowledge_assets')->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->unsignedInteger('page_number')->nullable();
            $table->text('content');
            $table->jsonb('embedding')->nullable();
            $table->timestamps();

            $table->index(['knowledge_asset_id', 'chunk_index']);
        });

        DB::statement("CREATE INDEX knowledge_chunks_content_fts_idx ON knowledge_chunks USING GIN (to_tsvector('indonesian', content))");

        Schema::table('knowledge_assets', function (Blueprint $table) {
            $table->jsonb('ai_tags')->default('[]')->after('dynamic_data');
            $table->text('ai_summary')->nullable()->after('ai_tags');
            $table->string('index_status', 20)->nullable()->after('ai_summary');
            $table->timestamp('indexed_at')->nullable()->after('index_status');
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_assets', function (Blueprint $table) {
            $table->dropColumn(['ai_tags', 'ai_summary', 'index_status', 'indexed_at']);
        });

        Schema::dropIfExists('knowledge_chunks');
    }
};
