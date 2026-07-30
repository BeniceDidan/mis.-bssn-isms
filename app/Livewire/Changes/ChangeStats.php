<?php

namespace App\Livewire\Changes;

use App\Enums\Level;
use App\Livewire\Concerns\DedupesActivityFeed;
use App\Models\Change;
use Livewire\Attributes\On;
use Livewire\Component;

class ChangeStats extends Component
{
    use DedupesActivityFeed;

    #[On('change-saved')]
    public function refresh(): void
    {
        // no-op: re-render picks up fresh counts
    }

    public function render()
    {
        return view('livewire.changes.change-stats', [
            'totalChanges' => Change::count(),
            'highRiskChanges' => Change::whereIn('inherent_risk_level', [Level::Tinggi->value, Level::Kritis->value])->count(),
            'recentActivity' => $this->dedupedRecentActivity('change'),
        ]);
    }
}
