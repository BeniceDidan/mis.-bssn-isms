<?php

namespace App\Livewire\SecurityPrograms;

use App\Livewire\Concerns\GuardsWriteAccess;
use App\Models\SecurityProgram;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SecurityProgramTable extends Component
{
    use WithPagination, GuardsWriteAccess;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public bool $showArchived = false;

    public ?SecurityProgram $viewingRecord = null;

    public bool $detailOpen = false;

    protected string $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function viewDetail(int $id): void
    {
        $this->viewingRecord = SecurityProgram::withTrashed()
            ->with(['verifications' => fn ($q) => $q->with('user')->limit(3), 'asset'])
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

        $record = SecurityProgram::findOrFail($id);
        $record->delete();

        $this->dispatch('toast', message: "Program kerja \"{$record->legacy_code}\" diarsipkan.");
    }

    public function restore(int $id): void
    {
        $this->ensureCanWrite();

        $record = SecurityProgram::onlyTrashed()->findOrFail($id);
        $record->restore();

        $this->dispatch('toast', message: "Program kerja \"{$record->legacy_code}\" dipulihkan.");
    }

    #[On('security-program-saved')]
    public function refreshTable(): void
    {
        // no-op
    }

    public function render()
    {
        $records = SecurityProgram::query()
            ->with('asset')
            ->when($this->showArchived, fn ($query) => $query->onlyTrashed())
            ->when($this->search, function ($query) {
                $term = "%{$this->search}%";
                $query->where(function ($q) use ($term) {
                    $q->where('program_kerja', 'ilike', $term)
                        ->orWhere('legacy_code', 'ilike', $term)
                        ->orWhere('kegiatan', 'ilike', $term)
                        ->orWhere('pic', 'ilike', $term);
                });
            })
            ->orderBy('legacy_code')
            ->paginate(15);

        return view('livewire.security-programs.security-program-table', [
            'records' => $records,
        ]);
    }
}
