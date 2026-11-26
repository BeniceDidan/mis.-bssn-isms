<?php

namespace App\Livewire\Evidence;

use App\Models\EvidenceDocument;
use App\Support\AdminModules;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class EvidenceTable extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $moduleFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingModuleFilter(): void
    {
        $this->resetPage();
    }

    #[On('evidence-saved')]
    public function refresh(): void
    {
        // re-render
    }

    public function render()
    {
        $records = EvidenceDocument::query()
            ->with('uploader:id,name')
            ->when($this->moduleFilter, fn ($q) => $q->where('module_key', $this->moduleFilter))
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(fn ($w) => $w->where('title', 'ilike', $term)
                    ->orWhere('unit_name', 'ilike', $term)
                    ->orWhere('record_code', 'ilike', $term)
                    ->orWhere('original_name', 'ilike', $term));
            })
            ->latest()
            ->paginate(15);

        return view('livewire.evidence.evidence-table', [
            'records' => $records,
            'modules' => collect(config('bukti_dukung.modules'))->mapWithKeys(fn ($m, $key) => [$key => AdminModules::label($key)]),
        ]);
    }
}
