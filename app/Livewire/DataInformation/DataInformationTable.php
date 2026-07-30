<?php

namespace App\Livewire\DataInformation;

use App\Enums\ChangeType;
use App\Enums\Level;
use App\Livewire\Concerns\GuardsWriteAccess;
use App\Models\DataInformation;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DataInformationTable extends Component
{
    use WithPagination, GuardsWriteAccess;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $typeFilter = '';

    #[Url(history: true)]
    public string $riskLevelFilter = '';

    #[Url(history: true)]
    public bool $showArchived = false;

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public ?DataInformation $viewingRecord = null;

    public bool $detailOpen = false;

    protected string $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingRiskLevelFilter(): void
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
        $this->viewingRecord = DataInformation::withTrashed()->with(['asset', 'verifications' => fn ($q) => $q->with('user')->limit(3)])->findOrFail($id);
        $this->detailOpen = true;
    }

    public function closeDetail(): void
    {
        $this->detailOpen = false;
    }

    public function archive(int $id): void
    {
        $this->ensureCanWrite();

        $record = DataInformation::findOrFail($id);
        $record->delete();

        $this->dispatch('toast', message: "Catatan \"{$record->title}\" diarsipkan.");
    }

    public function restore(int $id): void
    {
        $this->ensureCanWrite();

        $record = DataInformation::onlyTrashed()->findOrFail($id);
        $record->restore();

        $this->dispatch('toast', message: "Catatan \"{$record->title}\" dipulihkan.");
    }

    #[On('data-information-saved')]
    public function refreshTable(): void
    {
        // no-op
    }

    public function render()
    {
        $records = DataInformation::query()
            ->with('asset')
            ->when($this->showArchived, fn ($query) => $query->onlyTrashed())
            ->when($this->search, function ($query) {
                $term = "%{$this->search}%";
                $query->where(function ($q) use ($term) {
                    $q->where('title', 'ilike', $term)
                        ->orWhere('record_code', 'ilike', $term)
                        ->orWhere('legacy_code', 'ilike', $term)
                        ->orWhere('pic', 'ilike', $term)
                        ->orWhereRaw('dynamic_data::text ilike ?', [$term]);
                });
            })
            ->when($this->typeFilter, fn ($query) => $query->where('risk_type', $this->typeFilter))
            ->when($this->riskLevelFilter, fn ($query) => $query->where('inherent_risk_level', $this->riskLevelFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.data-information.data-information-table', [
            'records' => $records,
            'types' => ChangeType::cases(),
            'levels' => Level::cases(),
        ]);
    }
}
