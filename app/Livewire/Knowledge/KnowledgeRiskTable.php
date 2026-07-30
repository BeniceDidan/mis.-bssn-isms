<?php

namespace App\Livewire\Knowledge;

use App\Enums\Level;
use App\Livewire\Concerns\GuardsWriteAccess;
use App\Models\KnowledgeRisk;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class KnowledgeRiskTable extends Component
{
    use WithPagination, GuardsWriteAccess;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $levelFilter = '';

    #[Url(history: true)]
    public bool $showArchived = false;

    public ?KnowledgeRisk $viewingRecord = null;

    public bool $detailOpen = false;

    protected string $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingLevelFilter(): void
    {
        $this->resetPage();
    }

    public function viewDetail(int $id): void
    {
        $this->viewingRecord = KnowledgeRisk::withTrashed()
            ->with(['knowledgeAsset:id,title,legacy_code', 'verifications' => fn ($q) => $q->with('user')->limit(3)])
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

        $record = KnowledgeRisk::findOrFail($id);
        $record->delete();

        $this->dispatch('toast', message: "Risiko \"{$record->legacy_code}\" diarsipkan.");
    }

    public function restore(int $id): void
    {
        $this->ensureCanWrite();

        $record = KnowledgeRisk::onlyTrashed()->findOrFail($id);
        $record->restore();

        $this->dispatch('toast', message: "Risiko \"{$record->legacy_code}\" dipulihkan.");
    }

    #[On('knowledge-risk-saved')]
    public function refreshTable(): void
    {
        // no-op
    }

    public function render()
    {
        $records = KnowledgeRisk::query()
            ->with('knowledgeAsset:id,title,legacy_code')
            ->when($this->showArchived, fn ($query) => $query->onlyTrashed())
            ->when($this->search, function ($query) {
                $term = "%{$this->search}%";
                $query->where(function ($q) use ($term) {
                    $q->where('pernyataan_risiko', 'ilike', $term)
                        ->orWhere('legacy_code', 'ilike', $term)
                        ->orWhere('area_dampak', 'ilike', $term);
                });
            })
            ->when($this->levelFilter, fn ($query) => $query->where('tingkat_risiko_bawaan', $this->levelFilter))
            ->orderByDesc('skor_risiko_bawaan')
            ->paginate(10);

        return view('livewire.knowledge.knowledge-risk-table', [
            'records' => $records,
            'levels' => Level::cases(),
        ]);
    }
}
