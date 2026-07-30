<?php

namespace App\Livewire\Knowledge;

use App\Livewire\Concerns\GuardsWriteAccess;
use App\Models\KnowledgeActivity;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class KnowledgeActivityTable extends Component
{
    use WithPagination, GuardsWriteAccess;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public bool $showArchived = false;

    public ?KnowledgeActivity $viewingRecord = null;

    public bool $detailOpen = false;

    protected string $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function viewDetail(int $id): void
    {
        $this->viewingRecord = KnowledgeActivity::withTrashed()
            ->with(['narasumber', 'verifications' => fn ($q) => $q->with('user')->limit(3)])
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

        $record = KnowledgeActivity::findOrFail($id);
        $record->delete();

        $this->dispatch('toast', message: "Kegiatan \"{$record->nama_kegiatan}\" diarsipkan.");
    }

    public function restore(int $id): void
    {
        $this->ensureCanWrite();

        $record = KnowledgeActivity::onlyTrashed()->findOrFail($id);
        $record->restore();

        $this->dispatch('toast', message: "Kegiatan \"{$record->nama_kegiatan}\" dipulihkan.");
    }

    #[On('knowledge-activity-saved')]
    public function refreshTable(): void
    {
        // no-op
    }

    public function render()
    {
        $records = KnowledgeActivity::query()
            ->with('narasumber:id,nama_pegawai')
            ->when($this->showArchived, fn ($query) => $query->onlyTrashed())
            ->when($this->search, function ($query) {
                $term = "%{$this->search}%";
                $query->where(function ($q) use ($term) {
                    $q->where('nama_kegiatan', 'ilike', $term)
                        ->orWhere('legacy_code', 'ilike', $term)
                        ->orWhere('narasumber_name', 'ilike', $term);
                });
            })
            ->orderByDesc('tanggal_pelaksanaan')
            ->paginate(10);

        return view('livewire.knowledge.knowledge-activity-table', [
            'records' => $records,
        ]);
    }
}
