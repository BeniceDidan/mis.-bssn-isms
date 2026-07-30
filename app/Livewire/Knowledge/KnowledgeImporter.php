<?php

namespace App\Livewire\Knowledge;

use App\Imports\KnowledgeImport;
use App\Livewire\Concerns\GuardsWriteAccess;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Reads all 4 sheets of a Manajemen Pengetahuan workbook in one pass (see
 * KnowledgeImport) — shared across all 4 tabs on the /knowledge page rather
 * than one importer per tab, since it's genuinely one file.
 */
class KnowledgeImporter extends Component
{
    use WithFileUploads, GuardsWriteAccess;

    public bool $show = false;

    public $file = null;

    public ?array $result = null;

    #[On('open-knowledge-importer')]
    public function open(): void
    {
        $this->reset(['file', 'result']);
        $this->resetValidation();
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function import(): void
    {
        $this->ensureCanWrite();

        $this->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $import = new KnowledgeImport();
        $import->run($this->file->getRealPath());

        $summary = $import->summary();

        $this->result = [
            'created' => $summary->created,
            'updated' => $summary->updated,
            'errors' => $summary->errors,
        ];

        $this->file = null;

        $this->dispatch('knowledge-saved');
        $this->dispatch('knowledge-expert-saved');
        $this->dispatch('knowledge-activity-saved');
        $this->dispatch('knowledge-risk-saved');
    }

    public function render()
    {
        return view('livewire.knowledge.knowledge-importer');
    }
}
