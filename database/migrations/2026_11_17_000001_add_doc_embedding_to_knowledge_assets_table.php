<?php

use App\Services\Ai\LocalLlm;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vektor rata-rata per dokumen untuk pencarian dua tahap Tanya Dokumen.
 *
 * Uji 17 November 2026: memindai semua potongan butuh ±0,25 ms per potongan
 * (25.000 potongan ≈ 6 detik per pertanyaan). Dengan vektor per dokumen,
 * pertanyaan cukup dibandingkan dengan tiap dokumen dulu, lalu hanya potongan
 * dari dokumen teratas yang diperiksa. Dokumen yang sudah terindeks diisi
 * dari potongannya sendiri, tanpa memanggil model lagi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_assets', function (Blueprint $table) {
            $table->jsonb('doc_embedding')->nullable()->after('indexed_at');
        });

        $assetIds = DB::table('knowledge_chunks')->whereNotNull('embedding')->distinct()->pluck('knowledge_asset_id');
        foreach ($assetIds as $assetId) {
            $vectors = DB::table('knowledge_chunks')
                ->where('knowledge_asset_id', $assetId)
                ->whereNotNull('embedding')
                ->pluck('embedding')
                ->map(fn ($json) => json_decode($json, true))
                ->all();
            $centroid = LocalLlm::centroid($vectors);
            if ($centroid !== null) {
                DB::table('knowledge_assets')->where('id', $assetId)->update(['doc_embedding' => json_encode($centroid)]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('knowledge_assets', function (Blueprint $table) {
            $table->dropColumn('doc_embedding');
        });
    }
};
