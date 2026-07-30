<?php

namespace App\Livewire\Knowledge;

use App\Livewire\Concerns\DedupesActivityFeed;
use App\Models\KnowledgeAsset;
use Livewire\Attributes\On;
use Livewire\Component;

class KnowledgeStats extends Component
{
    use DedupesActivityFeed;

    #[On('knowledge-saved')]
    public function refresh(): void
    {
        // no-op
    }

    public function render()
    {
        return view('livewire.knowledge.knowledge-stats', [
            'totalRecords' => KnowledgeAsset::count(),
            'linkedToAsset' => KnowledgeAsset::whereNotNull('asset_id')->count(),
            'recentActivity' => $this->dedupedRecentActivity('knowledge_asset'),
        ]);
    }
}
