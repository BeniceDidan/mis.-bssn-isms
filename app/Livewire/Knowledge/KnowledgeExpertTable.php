<?php

namespace App\Livewire\Knowledge;

use App\Livewire\Concerns\GuardsWriteAccess;
use App\Models\HrRisk;
use App\Models\KnowledgeExpert;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class KnowledgeExpertTable extends Component
{
    use WithPagination, GuardsWriteAccess;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public bool $showArchived = false;

    public ?KnowledgeExpert $viewingRecord = null;

    public ?Collection $relatedHrRisks = null;

    public bool $detailOpen = false;

    protected string $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function viewDetail(int $id): void
    {
        $this->viewingRecord = KnowledgeExpert::withTrashed()
            ->with(['activities' => fn ($q) => $q->latest()->limit(5)])
            ->with(['verifications' => fn ($q) => $q->with('user')->limit(3)])
            ->findOrFail($id);

        // Reverse of HrRiskTable's lookup — exact Kode Personil match only.
        $personnelRef = $this->viewingRecord->personnel_ref;
        $this->relatedHrRisks = HrRisk::all()
            ->filter(fn (HrRisk $risk) => $personnelRef && $risk->personnel_ref === $personnelRef)
            ->values();

        $this->detailOpen = true;
    }

    public function closeDetail(): void
    {
        $this->detailOpen = false;
    }

    public function archive(int $id): void
    {
        $this->ensureCanWrite();

        $record = KnowledgeExpert::findOrFail($id);
        $record->delete();

        $this->dispatch('toast', message: "Data \"{$record->nama_pegawai}\" diarsipkan.");
    }

    public function restore(int $id): void
    {
        $this->ensureCanWrite();

        $record = KnowledgeExpert::onlyTrashed()->findOrFail($id);
        $record->restore();

        $this->dispatch('toast', message: "Data \"{$record->nama_pegawai}\" dipulihkan.");
    }

    #[On('knowledge-expert-saved')]
    public function refreshTable(): void
    {
        // no-op
    }

    public function render()
    {
        $records = KnowledgeExpert::query()
            ->withCount('activities')
            ->when($this->showArchived, fn ($query) => $query->onlyTrashed())
            ->when($this->search, function ($query) {
                $term = "%{$this->search}%";
                $query->where(function ($q) use ($term) {
                    $q->where('nama_pegawai', 'ilike', $term)
                        ->orWhere('legacy_code', 'ilike', $term)
                        ->orWhere('jabatan_unit', 'ilike', $term)
                        ->orWhere('keahlian_spesifik', 'ilike', $term);
                });
            })
            ->orderBy('nama_pegawai')
            ->paginate(10);

        return view('livewire.knowledge.knowledge-expert-table', [
            'records' => $records,
        ]);
    }
}
