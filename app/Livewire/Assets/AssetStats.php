<?php

namespace App\Livewire\Assets;

use App\Enums\Level;
use App\Livewire\Concerns\DedupesActivityFeed;
use App\Models\Asset;
use Livewire\Attributes\On;
use Livewire\Component;

class AssetStats extends Component
{
    use DedupesActivityFeed;

    #[On('asset-saved')]
    public function refresh(): void
    {
        // no-op: re-render picks up fresh counts
    }

    public function render()
    {
        $assetsByCriticality = Asset::query()
            ->selectRaw('criticality_level, count(*) as total')
            ->whereNotNull('criticality_level')
            ->groupBy('criticality_level')
            ->pluck('total', 'criticality_level');

        return view('livewire.assets.asset-stats', [
            'totalAssets' => Asset::count(),
            'highCriticalityAssets' => Asset::whereIn('criticality_level', [Level::Tinggi->value, Level::Kritis->value])->count(),
            'assetsByCriticality' => $assetsByCriticality,
            'levels' => Level::cases(),
            'recentActivity' => $this->dedupedRecentActivity('asset'),
        ]);
    }
}
