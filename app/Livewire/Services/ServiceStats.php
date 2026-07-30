<?php

namespace App\Livewire\Services;

use App\Livewire\Concerns\DedupesActivityFeed;
use App\Models\Service;
use App\Models\ServiceTicket;
use Livewire\Attributes\On;
use Livewire\Component;

class ServiceStats extends Component
{
    use DedupesActivityFeed;

    #[On('service-saved')]
    public function refresh(): void
    {
        // no-op
    }

    public function render()
    {
        return view('livewire.services.service-stats', [
            'totalRecords' => Service::count(),
            'openTickets' => ServiceTicket::whereNotIn('status', ['Selesai', 'selesai'])->count(),
            'recentActivity' => $this->dedupedRecentActivity('service'),
        ]);
    }
}
