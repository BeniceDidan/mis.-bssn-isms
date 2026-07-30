<?php

namespace App\Livewire\Changes;

use App\Enums\ChangeType;
use App\Enums\Level;
use App\Livewire\Concerns\GuardsWriteAccess;
use App\Models\Change;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ChangeTable extends Component
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

    public ?Change $viewingChange = null;

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

    public function viewDetail(int $changeId): void
    {
        $this->viewingChange = Change::withTrashed()->with(['asset', 'verifications' => fn ($q) => $q->with('user')->limit(3)])->findOrFail($changeId);
        $this->detailOpen = true;
    }

    public function closeDetail(): void
    {
        $this->detailOpen = false;
    }

    public function archive(int $changeId): void
    {
        $this->ensureCanWrite();

        $change = Change::findOrFail($changeId);
        $change->delete();

        $this->dispatch('toast', message: "Perubahan \"{$change->title}\" diarsipkan.");
    }

    public function restore(int $changeId): void
    {
        $this->ensureCanWrite();

        $change = Change::onlyTrashed()->findOrFail($changeId);
        $change->restore();

        $this->dispatch('toast', message: "Perubahan \"{$change->title}\" dipulihkan.");
    }

    #[On('change-saved')]
    public function refreshTable(): void
    {
        // no-op: re-render is enough
    }

    public function render()
    {
        $changes = Change::query()
            ->with('asset')
            ->when($this->showArchived, fn ($query) => $query->onlyTrashed())
            ->when($this->search, function ($query) {
                $term = "%{$this->search}%";
                $query->where(function ($q) use ($term) {
                    $q->where('title', 'ilike', $term)
                        ->orWhere('change_code', 'ilike', $term)
                        ->orWhere('legacy_code', 'ilike', $term)
                        ->orWhere('pic', 'ilike', $term)
                        ->orWhereRaw('dynamic_data::text ilike ?', [$term]);
                });
            })
            ->when($this->typeFilter, fn ($query) => $query->where('change_type', $this->typeFilter))
            ->when($this->riskLevelFilter, fn ($query) => $query->where('inherent_risk_level', $this->riskLevelFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.changes.change-table', [
            'changes' => $changes,
            'types' => ChangeType::cases(),
            'levels' => Level::cases(),
        ]);
    }
}
