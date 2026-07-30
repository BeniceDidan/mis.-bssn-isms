<?php

namespace App\Livewire\DataInformation;

use App\Enums\Level;
use App\Livewire\Concerns\DedupesActivityFeed;
use App\Models\DataInformation;
use Livewire\Attributes\On;
use Livewire\Component;

class DataInformationStats extends Component
{
    use DedupesActivityFeed;

    #[On('data-information-saved')]
    public function refresh(): void
    {
        // no-op
    }

    public function render()
    {
        return view('livewire.data-information.data-information-stats', [
            'totalRecords' => DataInformation::count(),
            'highRiskRecords' => DataInformation::whereIn('inherent_risk_level', [Level::Tinggi->value, Level::Kritis->value])->count(),
            'recentActivity' => $this->dedupedRecentActivity('data_information'),
        ]);
    }
}
