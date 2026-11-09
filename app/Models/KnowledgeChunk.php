<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeChunk extends Model
{
    protected $fillable = [
        'knowledge_asset_id',
        'chunk_index',
        'page_number',
        'content',
        'embedding',
    ];

    protected $casts = [
        'embedding' => 'array',
    ];

    public function knowledgeAsset(): BelongsTo
    {
        return $this->belongsTo(KnowledgeAsset::class);
    }
}
