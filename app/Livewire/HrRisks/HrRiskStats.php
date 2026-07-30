<?php

namespace App\Livewire\HrRisks;

use App\Enums\Level;
use App\Livewire\Concerns\DedupesActivityFeed;
use App\Models\HrRisk;
use Livewire\Attributes\On;
use Livewire\Component;

class HrRiskStats extends Component
{
    use DedupesActivityFeed;

    #[On('hr-risk-saved')]
    public function refresh(): void
    {
        // no-op
    }

    public function render()
    {
        return view('livewire.hr-risks.hr-risk-stats', [
            'totalRecords' => HrRisk::count(),
            'highRiskRecords' => HrRisk::whereIn('inherent_risk_level', [Level::Tinggi->value, Level::Kritis->value])->count(),
            'recentActivity' => $this->dedupedRecentActivity('hr_risk'),
        ]);
    }
}
