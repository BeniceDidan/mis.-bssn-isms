<?php

namespace App\Livewire\Services;

use App\Livewire\Concerns\GuardsWriteAccess;
use App\Models\Service;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ServiceTable extends Component
{
    use WithPagination, GuardsWriteAccess;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public bool $showArchived = false;

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public ?Service $viewingRecord = null;

    public bool $detailOpen = false;

    protected string $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingShowArchived(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function viewDetail(int $id): void
    {
        $this->viewingRecord = Service::withTrashed()
            ->with(['assets:id,name,asset_code', 'tickets' => fn ($q) => $q->latest('reported_at')->limit(10), 'evaluations' => fn ($q) => $q->latest('created_at')->limit(5), 'verifications' => fn ($q) => $q->with('user')->limit(3)])
            ->findOrFail($id);
        $this->detailOpen = true;
    }

    public function closeDetail(): void
    {
        $this->detailOpen = false;
    }

    public function archive(int $id): void
    {
        $this->ensureCanWrite();

        $record = Service::findOrFail($id);
        $record->delete();

        $this->dispatch('toast', message: "Layanan \"{$record->name}\" diarsipkan.");
    }

    public function restore(int $id): void
    {
        $this->ensureCanWrite();

        $record = Service::onlyTrashed()->findOrFail($id);
        $record->restore();

        $this->dispatch('toast', message: "Layanan \"{$record->name}\" dipulihkan.");
    }

    #[On('service-saved')]
    public function refreshTable(): void
    {
        // no-op
    }

    /**
     * Ticket/evaluation forms save directly (they're not part of the
     * Service model itself), so the open detail panel's already-loaded
     * $viewingRecord->tickets/evaluations won't reflect a new one without
     * this explicit reload.
     */
    #[On('service-ticket-saved')]
    #[On('service-evaluation-saved')]
    public function refreshDetail(): void
    {
        if ($this->viewingRecord) {
            $this->viewDetail($this->viewingRecord->id);
        }
    }

    public function render()
    {
        $records = Service::query()
            ->withCount(['assets', 'tickets'])
            ->when($this->showArchived, fn ($query) => $query->onlyTrashed())
            ->when($this->search, function ($query) {
                $term = "%{$this->search}%";
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'ilike', $term)
                        ->orWhere('service_code', 'ilike', $term)
                        ->orWhere('legacy_code', 'ilike', $term)
                        ->orWhere('owner_unit', 'ilike', $term)
                        ->orWhereRaw('dynamic_data::text ilike ?', [$term]);
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.services.service-table', [
            'records' => $records,
        ]);
    }
}
