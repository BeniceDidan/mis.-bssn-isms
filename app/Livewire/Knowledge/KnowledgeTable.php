<?php

namespace App\Livewire\Knowledge;

use App\Enums\KnowledgeType;
use App\Livewire\Concerns\GuardsWriteAccess;
use App\Models\KnowledgeAsset;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class KnowledgeTable extends Component
{
    use WithPagination, GuardsWriteAccess;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $typeFilter = '';

    #[Url(history: true)]
    public bool $showArchived = false;

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public ?KnowledgeAsset $viewingRecord = null;

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
        $this->viewingRecord = KnowledgeAsset::withTrashed()->with(['asset', 'verifications' => fn ($q) => $q->with('user')->limit(3)])->findOrFail($id);
        $this->detailOpen = true;
    }

    public function closeDetail(): void
    {
        $this->detailOpen = false;
    }

    public function archive(int $id): void
    {
        $this->ensureCanWrite();

        $record = KnowledgeAsset::findOrFail($id);
        $record->delete();

        $this->dispatch('toast', message: "Dokumen \"{$record->title}\" diarsipkan.");
    }

    public function restore(int $id): void
    {
        $this->ensureCanWrite();

        $record = KnowledgeAsset::onlyTrashed()->findOrFail($id);
        $record->restore();

        $this->dispatch('toast', message: "Dokumen \"{$record->title}\" dipulihkan.");
    }

    #[On('knowledge-saved')]
    public function refreshTable(): void
    {
        // no-op
    }

    public function render()
    {
        $records = KnowledgeAsset::query()
            ->with('asset')
            ->when($this->showArchived, fn ($query) => $query->onlyTrashed())
            ->when($this->search, function ($query) {
                $term = "%{$this->search}%";
                $query->where(function ($q) use ($term) {
                    $q->where('title', 'ilike', $term)
                        ->orWhere('record_code', 'ilike', $term)
                        ->orWhere('legacy_code', 'ilike', $term)
                        ->orWhere('owner_unit', 'ilike', $term)
                        ->orWhereRaw('dynamic_data::text ilike ?', [$term]);
                });
            })
            ->when($this->typeFilter, fn ($query) => $query->where('knowledge_type', $this->typeFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.knowledge.knowledge-table', [
            'records' => $records,
            'types' => KnowledgeType::cases(),
        ]);
    }
}
