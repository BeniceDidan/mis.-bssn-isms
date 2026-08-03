<?php

namespace App\Livewire\SecurityPrograms;

use App\Livewire\Concerns\GeneratesPersonnelRef;
use App\Livewire\Concerns\GuardsWriteAccess;
use App\Models\Asset;
use App\Models\HrRisk;
use App\Models\SecurityProgram;
use Livewire\Attributes\On;
use Livewire\Component;

class SecurityProgramFormModal extends Component
{
    use GuardsWriteAccess, GeneratesPersonnelRef;

    public bool $show = false;

    public ?int $recordId = null;

    public ?int $asset_id = null;

    public string $program_kerja = '';

    public string $kegiatan = '';

    public string $pic = '';

    public ?string $personnel_ref = null;

    /** @var array<int, array{key: string, value: string}> */
    public array $dynamicRows = [];

    protected function rules(): array
    {
        return [
            'asset_id' => ['nullable', 'exists:assets,id'],
            'program_kerja' => ['required', 'string', 'max:2000'],
            'kegiatan' => ['nullable', 'string', 'max:255'],
            'pic' => ['nullable', 'string', 'max:255'],
            'personnel_ref' => ['nullable', 'string', 'max:50'],
            'dynamicRows.*.key' => ['nullable', 'string', 'max:100'],
            'dynamicRows.*.value' => ['nullable', 'string', 'max:2000'],
        ];
    }

    #[On('open-security-program-form')]
    public function open(?int $recordId = null): void
    {
        $this->resetValidation();
        $this->recordId = $recordId;

        if ($recordId) {
            $record = SecurityProgram::findOrFail($recordId);
            $this->asset_id = $record->asset_id;
            $this->program_kerja = $record->program_kerja;
            $this->kegiatan = $record->kegiatan ?? '';
            $this->pic = $record->pic ?? '';
            $this->personnel_ref = $record->personnel_ref;
            $this->dynamicRows = collect($record->dynamic_data ?? [])
                ->map(fn ($value, $key) => ['key' => $key, 'value' => is_array($value) ? json_encode($value) : $value])
                ->values()
                ->toArray();
        } else {
            $this->reset(['asset_id', 'program_kerja', 'kegiatan', 'pic', 'personnel_ref', 'dynamicRows']);
        }

        $this->show = true;
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

        $payload = collect($validated)->except('dynamicRows')->toArray();
        $payload['dynamic_data'] = $dynamicData;
        $payload['personnel_ref'] = $this->ensurePersonnelRef($payload['personnel_ref'] ?? null);
        $payload['verification_status'] = auth()->user()?->canAutoVerify('keamanan') ? 'tervalidasi' : 'menunggu_verifikasi';

        if ($this->recordId) {
            SecurityProgram::findOrFail($this->recordId)->update($payload);
        } else {
            SecurityProgram::create($payload);
        }

        $this->show = false;
        $this->dispatch('security-program-saved');
        $this->dispatch('toast', message: $this->recordId ? 'Program kerja diperbarui.' : 'Program kerja baru ditambahkan.');
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.security-programs.security-program-form-modal', [
            'assets' => Asset::orderBy('name')->get(['id', 'name', 'asset_code']),
            'hrRisks' => HrRisk::whereNotNull('personnel_ref')->where('personnel_ref', '!=', '')->orderBy('subject')->get(['id', 'subject', 'record_code', 'personnel_ref']),
        ]);
    }
}
