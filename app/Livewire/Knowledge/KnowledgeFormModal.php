<?php

namespace App\Livewire\Knowledge;

use App\Enums\KnowledgeType;
use App\Livewire\Concerns\GeneratesPersonnelRef;
use App\Livewire\Concerns\GuardsWriteAccess;
use App\Livewire\Concerns\NormalizesEnumInputs;
use App\Models\Asset;
use App\Models\HrRisk;
use App\Models\KnowledgeAsset;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class KnowledgeFormModal extends Component
{
    use GuardsWriteAccess, NormalizesEnumInputs, WithFileUploads, GeneratesPersonnelRef;

    public bool $show = false;

    public ?int $recordId = null;

    public ?int $asset_id = null;

    public string $title = '';

    public string $knowledge_type = '';

    public string $category = '';

    public string $owner_unit = '';

    public string $access_level = '';

    public ?string $last_reviewed_at = null;

    public string $external_link = '';

    public ?string $personnel_ref = null;

    /** Newly-chosen upload, held in memory until save(). */
    public $document = null;

    /** Path of a document already attached (when editing an existing record). */
    public ?string $existingDocumentPath = null;

    public ?string $existingDocumentName = null;

    /** @var array<int, array{key: string, value: string}> */
    public array $dynamicRows = [];

    protected function rules(): array
    {
        return [
            'asset_id' => ['nullable', 'exists:assets,id'],
            'title' => ['required', 'string', 'max:255'],
            'knowledge_type' => ['nullable', 'string', 'in:' . implode(',', array_column(KnowledgeType::cases(), 'value'))],
            'category' => ['nullable', 'string', 'max:100'],
            'owner_unit' => ['nullable', 'string', 'max:255'],
            'access_level' => ['nullable', 'string', 'max:100'],
            'last_reviewed_at' => ['nullable', 'date'],
            'external_link' => ['nullable', 'url', 'max:500'],
            'personnel_ref' => ['nullable', 'string', 'max:50'],
            'document' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'dynamicRows.*.key' => ['nullable', 'string', 'max:100'],
            'dynamicRows.*.value' => ['nullable', 'string', 'max:2000'],
        ];
    }

    #[On('open-knowledge-form')]
    public function open(?int $recordId = null, ?int $assetId = null): void
    {
        $this->resetValidation();
        $this->recordId = $recordId;

        if ($recordId) {
            $record = KnowledgeAsset::findOrFail($recordId);
            $this->asset_id = $record->asset_id;
            $this->title = $record->title;
            $this->knowledge_type = $record->knowledge_type?->value ?? '';
            $this->category = $record->category ?? '';
            $this->owner_unit = $record->owner_unit ?? '';
            $this->access_level = $record->access_level ?? '';
            $this->last_reviewed_at = optional($record->last_reviewed_at)->format('Y-m-d');
            $this->external_link = $record->external_link ?? '';
            $this->personnel_ref = $record->personnel_ref;
            $this->existingDocumentPath = $record->document_path;
            $this->existingDocumentName = $record->document_original_name;
            $this->dynamicRows = collect($record->dynamic_data ?? [])
                ->map(fn ($value, $key) => ['key' => $key, 'value' => is_array($value) ? json_encode($value) : $value])
                ->values()
                ->toArray();
        } else {
            $this->reset([
                'asset_id', 'title', 'knowledge_type', 'category', 'owner_unit',
                'access_level', 'last_reviewed_at', 'external_link', 'personnel_ref',
                'existingDocumentPath', 'existingDocumentName', 'dynamicRows',
            ]);

            // Lets the "buat dokumentasi" nudge on an Asset's Riwayat
            // Terkait panel (Perubahan tercatat tapi belum ada Pengetahuan)
            // pre-fill the link instead of leaving the user to pick it
            // manually from a dropdown of every asset.
            $this->asset_id = $assetId;
        }

        $this->document = null;

        $this->show = true;
    }

    /**
     * Detaches (and deletes) the currently-attached PDF without needing to
     * pick a replacement first — separate from just leaving $document
     * empty, which on its own means "don't change the attachment."
     */
    public function removeDocument(): void
    {
        if ($this->existingDocumentPath) {
            Storage::disk('public')->delete($this->existingDocumentPath);
        }

        $this->existingDocumentPath = null;
        $this->existingDocumentName = null;
    }

    public function addDynamicRow(): void
    {
        $this->dynamicRows[] = ['key' => '', 'value' => ''];
    }

    public function removeDynamicRow(int $index): void
    {
        unset($this->dynamicRows[$index]);
        $this->dynamicRows = array_values($this->dynamicRows);
    }

    public function save(): void
    {
        $this->ensureCanWrite();

        $validated = $this->validate();

        $dynamicData = collect($this->dynamicRows)
            ->filter(fn ($row) => filled($row['key'] ?? null))
            ->mapWithKeys(fn ($row) => [$row['key'] => $row['value']])
            ->toArray();

        $payload = collect($validated)->except(['dynamicRows', 'document'])->toArray();
        $payload = $this->nullifyBlankEnums($payload, ['knowledge_type']);
        $payload['dynamic_data'] = $dynamicData;
        $payload['personnel_ref'] = $this->ensurePersonnelRef($payload['personnel_ref'] ?? null);
        $payload['verification_status'] = auth()->user()?->canAutoVerify('pengetahuan') ? 'tervalidasi' : 'menunggu_verifikasi';
        $payload['document_path'] = $this->existingDocumentPath;
        $payload['document_original_name'] = $this->existingDocumentName;

        if ($this->document) {
            // Replacing an existing file — remove the old one so uploads
            // don't pile up unreferenced in storage.
            if ($this->existingDocumentPath) {
                Storage::disk('public')->delete($this->existingDocumentPath);
            }

            $payload['document_path'] = $this->document->store('knowledge-documents', 'public');
            $payload['document_original_name'] = $this->document->getClientOriginalName();
        }

        if ($this->recordId) {
            KnowledgeAsset::findOrFail($this->recordId)->update($payload);
        } else {
            KnowledgeAsset::create($payload);
        }

        $this->show = false;
        $this->dispatch('knowledge-saved');
        $this->dispatch('toast', message: $this->recordId ? 'Dokumen diperbarui.' : 'Dokumen baru ditambahkan.');
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.knowledge.knowledge-form-modal', [
            'assets' => Asset::orderBy('name')->get(['id', 'name', 'asset_code']),
            'hrRisks' => HrRisk::whereNotNull('personnel_ref')->where('personnel_ref', '!=', '')->orderBy('subject')->get(['id', 'subject', 'record_code', 'personnel_ref']),
            'types' => KnowledgeType::cases(),
        ]);
    }
}
