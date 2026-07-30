<?php

namespace App\Livewire\SecurityPrograms;

use App\Imports\SecurityProgramImport;
use App\Livewire\Concerns\GuardsWriteAccess;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class SecurityProgramImporter extends Component
{
    use WithFileUploads, GuardsWriteAccess;

    public bool $show = false;

    public $file = null;

    public ?array $result = null;

    #[On('open-security-program-importer')]
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

        $import = new SecurityProgramImport();
        $import->run($this->file->getRealPath());

        $summary = $import->summary();

        $this->result = [
            'created' => $summary->created,
            'updated' => $summary->updated,
            'errors' => $summary->errors,
        ];

        $this->file = null;

        $this->dispatch('security-program-saved');
    }

    public function render()
    {
        return view('livewire.security-programs.security-program-importer');
    }
}
