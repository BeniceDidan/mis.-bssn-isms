<?php

namespace App\Jobs;

use App\Models\KnowledgeAsset;
use App\Services\Ai\KnowledgeIndexer;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Dijalankan setelah respons terkirim (dispatchAfterResponse), jadi
 * pengguna tidak menunggu proses embedding saat menyimpan dokumen dan
 * tidak butuh queue worker terpisah.
 */
class IndexKnowledgeDocument
{
    use Dispatchable;

    public function __construct(public int $knowledgeAssetId)
    {
    }

    public function handle(KnowledgeIndexer $indexer): void
    {
        $asset = KnowledgeAsset::find($this->knowledgeAssetId);

        if ($asset) {
            $indexer->index($asset);
        }
    }
}
