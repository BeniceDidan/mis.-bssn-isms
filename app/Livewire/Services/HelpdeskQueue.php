<?php

namespace App\Livewire\Services;

use App\Models\ServiceTicket;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Antrean tiket lintas layanan untuk operator helpdesk, diurutkan dari
 * prioritas tertinggi. Tiket tanpa prioritas (data lama) di urutan akhir.
 */
class HelpdeskQueue extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $statusFilter = 'terbuka';

    #[Url(history: true)]
    public string $categoryFilter = '';

    #[Url(history: true)]
    public string $search = '';

    public function updating($name): void
    {
        if (in_array($name, ['statusFilter', 'categoryFilter', 'search'], true)) {
            $this->resetPage();
        }
    }

    #[On('service-ticket-saved')]
    public function refresh(): void
    {
    }

    public function render()
    {
        $tickets = ServiceTicket::query()
            ->with('service:id,name')
            ->when($this->statusFilter === 'terbuka', fn ($q) => $q->where(fn ($w) => $w->whereNull('status')->orWhereNotIn(DB::raw('lower(status)'), ['selesai', 'ditutup'])))
            ->when($this->statusFilter === 'selesai', fn ($q) => $q->whereIn(DB::raw('lower(status)'), ['selesai', 'ditutup']))
            ->when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter))
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('issue', 'ilike', "%{$this->search}%")
                ->orWhere('requester_name', 'ilike', "%{$this->search}%")
                ->orWhere('legacy_code', 'ilike', "%{$this->search}%")))
            ->orderByRaw('priority IS NULL, priority ASC')
            ->orderByDesc('reported_at')
            ->orderByDesc('id')
            ->paginate(15);

        $openCounts = ServiceTicket::query()
            ->where(fn ($w) => $w->whereNull('status')->orWhereNotIn(DB::raw('lower(status)'), ['selesai', 'ditutup']))
            ->whereNotNull('priority')
            ->selectRaw('priority, count(*) as total')
            ->groupBy('priority')
            ->pluck('total', 'priority');

        return view('livewire.services.helpdesk-queue', [
            'tickets' => $tickets,
            'openCounts' => $openCounts,
            'categories' => collect(config('helpdesk.categories'))->map(fn ($c) => $c['label']),
        ]);
    }
}
