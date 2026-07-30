<?php

namespace App\Livewire\Risks;

use App\Enums\Level;
use App\Enums\RiskCategory;
use App\Enums\RiskStatus;
use App\Livewire\Concerns\GuardsWriteAccess;
use App\Models\Risk;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class RiskTable extends Component
{
    use WithPagination, GuardsWriteAccess;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $categoryFilter = '';

    #[Url(history: true)]
    public string $riskLevelFilter = '';

    #[Url(history: true)]
    public string $statusFilter = '';

    #[Url(history: true)]
    public bool $showArchived = false;

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public ?Risk $viewingRisk = null;

    /**
     * Entangling the full $viewingRisk model into Alpine for the slide-over's
     * x-show proved unreliable (the panel's content updated but visibility
     * didn't track it correctly) — a plain boolean entangles cleanly instead.
     */
    public bool $detailOpen = false;

    protected string $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingRiskLevelFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
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

    public function viewDetail(int $riskId): void
    {
        $this->viewingRisk = Risk::withTrashed()->with(['asset', 'verifications' => fn ($q) => $q->with('user')->limit(3)])->findOrFail($riskId);
        $this->detailOpen = true;
    }

    public function closeDetail(): void
    {
        $this->detailOpen = false;
    }

    public function archive(int $riskId): void
    {
        $this->ensureCanWrite();

        $risk = Risk::findOrFail($riskId);
        $risk->delete();

        $this->dispatch('toast', message: "Risiko \"{$risk->title}\" diarsipkan.");
    }

    public function restore(int $riskId): void
    {
        $this->ensureCanWrite();

        $risk = Risk::onlyTrashed()->findOrFail($riskId);
        $risk->restore();

        $this->dispatch('toast', message: "Risiko \"{$risk->title}\" dipulihkan.");
    }

    #[On('risk-saved')]
    public function refreshTable(): void
    {
        // no-op: re-render is enough, listed so Livewire wires the listener
    }

    public function render()
    {
        $risks = Risk::query()
            ->with('asset')
            ->when($this->showArchived, fn ($query) => $query->onlyTrashed())
            ->when($this->search, function ($query) {
                $term = "%{$this->search}%";
                $query->where(function ($q) use ($term) {
                    $q->where('title', 'ilike', $term)
                        ->orWhere('risk_code', 'ilike', $term)
                        ->orWhere('risk_owner', 'ilike', $term)
                        ->orWhere('threat_source', 'ilike', $term)
                        ->orWhereRaw('dynamic_data::text ilike ?', [$term]);
                });
            })
            ->when($this->categoryFilter, fn ($query) => $query->where('category', $this->categoryFilter))
            ->when($this->riskLevelFilter, fn ($query) => $query->where('risk_level', $this->riskLevelFilter))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.risks.risk-table', [
            'risks' => $risks,
            'categories' => RiskCategory::cases(),
            'levels' => Level::cases(),
            'statuses' => RiskStatus::cases(),
        ]);
    }
}
