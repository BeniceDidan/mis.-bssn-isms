<?php

namespace App\Livewire\Assets;

use App\Imports\AssetsImport;
use App\Livewire\Concerns\GuardsWriteAccess;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class AssetImporter extends Component
{
    use WithFileUploads, GuardsWriteAccess;

    public bool $show = false;

    public $file = null;

    public ?array $result = null;

    #[On('open-asset-importer')]
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

        $import = new AssetsImport();
        $import->run($this->file->getRealPath());

        $summary = $import->summary();

        $this->result = [
            'created' => $summary->created,
            'updated' => $summary->updated,
            'errors' => $summary->errors,
        ];

        $this->file = null;

        // Refreshes AssetTable, which listens for the same event the
        // create/edit modal dispatches on save.
        $this->dispatch('asset-saved');
    }

    public function render()
    {
        return view('livewire.assets.asset-importer');
    }
}
