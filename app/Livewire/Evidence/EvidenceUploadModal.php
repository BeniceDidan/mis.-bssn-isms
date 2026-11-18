<?php

namespace App\Livewire\Evidence;

use App\Livewire\Concerns\GuardsWriteAccess;
use App\Models\EvidenceDocument;
use App\Services\Ai\EvidenceAssessor;
use App\Support\AdminModules;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class EvidenceUploadModal extends Component
{
    use GuardsWriteAccess, WithFileUploads;

    public bool $show = false;

    public string $module_key = '';

    public int $target_level = 2;

    public string $unit_name = '';

    public string $title = '';

    public $document = null;

    protected function rules(): array
    {
        return [
            'module_key' => ['required', 'in:' . implode(',', array_keys(config('bukti_dukung.modules')))],
            'target_level' => ['required', 'integer', 'between:1,5'],
            'unit_name' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'document' => ['required', 'file', 'mimes:pdf', 'max:' . config('bukti_dukung.max_upload_kb')],
        ];
    }

    protected $messages = [
        'module_key.required' => 'Pilih modul yang dinilai.',
        'document.required' => 'Pilih berkas PDF.',
        'document.mimes' => 'Berkas harus PDF.',
        'document.max' => 'Ukuran PDF maksimal 10 MB.',
    ];

    #[On('open-evidence-upload')]
    public function open(?string $module = null): void
    {
        $this->resetValidation();
        $this->reset(['unit_name', 'title', 'document']);
        $this->module_key = $module ?? (auth()->user()?->admin_module ?? '');
        $this->target_level = 2;
        $this->show = true;
    }

    public function save(EvidenceAssessor $assessor): void
    {
        $this->ensureCanWrite();
        $validated = $this->validate();

        $original = $this->document->getClientOriginalName();
        $path = $this->document->store('bukti-dukung', 'local');

        $record = EvidenceDocument::create([
            'module_key' => $validated['module_key'],
            'target_level' => $validated['target_level'],
            'unit_name' => $validated['unit_name'] ?: null,
            'title' => $validated['title'] ?: pathinfo($original, PATHINFO_FILENAME),
            'file_path' => $path,
            'original_name' => $original,
            'uploaded_by' => auth()->id(),
        ]);

        $assessor->assess($record);

        $this->show = false;
        $this->dispatch('evidence-saved');
        $this->dispatch('open-evidence-detail', id: $record->id);
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.evidence.evidence-upload-modal', [
            'modules' => collect(config('bukti_dukung.modules'))->mapWithKeys(fn ($m, $key) => [$key => AdminModules::label($key)]),
            'levels' => config('bukti_dukung.levels'),
        ]);
    }
}
