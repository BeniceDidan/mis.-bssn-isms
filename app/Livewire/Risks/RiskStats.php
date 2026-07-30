<?php

namespace App\Livewire\Risks;

use App\Livewire\Concerns\DedupesActivityFeed;
use App\Models\Risk;
use Livewire\Attributes\On;
use Livewire\Component;

class RiskStats extends Component
{
    use DedupesActivityFeed;

    #[On('risk-saved')]
    public function refresh(): void
    {
        // no-op: re-render picks up fresh counts
    }

    public function render()
    {
        return view('livewire.risks.risk-stats', [
            'totalRisks' => Risk::count(),
            'openRisks' => Risk::whereNotIn('status', ['closed'])->count(),
            'recentActivity' => $this->dedupedRecentActivity('risk'),
        ]);
    }
}
