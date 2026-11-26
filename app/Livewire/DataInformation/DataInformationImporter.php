<?php

namespace App\Livewire\DataInformation;

use App\Imports\DataInformationImport;
use App\Livewire\Concerns\GuardsWriteAccess;
use App\Livewire\Concerns\RecordsImports;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class DataInformationImporter extends Component
{
    use WithFileUploads, GuardsWriteAccess, RecordsImports;

    public bool $show = false;

    public $file = null;

    public ?array $result = null;

    #[On('open-data-information-importer')]
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

        $startedAt = now();
        $import = new DataInformationImport();
        $import->run($this->file->getRealPath());

        $summary = $import->summary();
        $this->recordImport('data_informasi', $this->file->getClientOriginalName(), $summary, $startedAt);

        $this->result = [
            'created' => $summary->created,
            'updated' => $summary->updated,
            'errors' => $summary->errors,
        ];

        $this->file = null;

        $this->dispatch('data-information-saved');
    }

    public function render()
    {
        return view('livewire.data-information.data-information-importer');
    }
}
