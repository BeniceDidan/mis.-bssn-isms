<?php

namespace App\Livewire\DataInformation;

use App\Enums\ChangeType;
use App\Enums\Level;
use App\Livewire\Concerns\GeneratesPersonnelRef;
use App\Livewire\Concerns\GuardsWriteAccess;
use App\Livewire\Concerns\NormalizesEnumInputs;
use App\Models\Asset;
use App\Models\DataInformation;
use Livewire\Attributes\On;
use Livewire\Component;

class DataInformationFormModal extends Component
{
    use GuardsWriteAccess, NormalizesEnumInputs, GeneratesPersonnelRef;

    public bool $show = false;

    public ?int $recordId = null;

    public ?int $asset_id = null;

    public string $title = '';

    public string $risk_type = '';

    public string $category = '';

    public string $priority = '';

    public string $inherent_risk_level = '';

    public string $decision = '';

    public string $status = 'diajukan';

    public string $pic = '';

    public ?string $target_date = null;

    public ?string $personnel_ref = null;

    /** @var array<int, array{key: string, value: string}> */
    public array $dynamicRows = [];

    protected function rules(): array
    {
        return [
            'asset_id' => ['nullable', 'exists:assets,id'],
            'title' => ['required', 'string', 'max:255'],
            'risk_type' => ['nullable', 'string', 'in:' . implode(',', array_column(ChangeType::cases(), 'value'))],
            'category' => ['nullable', 'string', 'max:100'],
            'priority' => ['nullable', 'string', 'max:50'],
            'inherent_risk_level' => ['nullable', 'string', 'in:' . implode(',', array_column(Level::cases(), 'value'))],
            'decision' => ['nullable', 'string', 'max:150'],
            'status' => ['required', 'string', 'max:50'],
            'pic' => ['nullable', 'string', 'max:255'],
            'target_date' => ['nullable', 'date'],
            'personnel_ref' => ['nullable', 'string', 'max:50'],
            'dynamicRows.*.key' => ['nullable', 'string', 'max:100'],
            'dynamicRows.*.value' => ['nullable', 'string', 'max:2000'],
        ];
    }

    #[On('open-data-information-form')]
    public function open(?int $recordId = null): void
    {
        $this->resetValidation();
        $this->recordId = $recordId;

        if ($recordId) {
            $record = DataInformation::findOrFail($recordId);
            $this->asset_id = $record->asset_id;
            $this->title = $record->title;
            $this->risk_type = $record->risk_type?->value ?? '';
            $this->category = $record->category ?? '';
            $this->priority = $record->priority ?? '';
            $this->inherent_risk_level = $record->inherent_risk_level?->value ?? '';
            $this->decision = $record->decision ?? '';
            $this->status = $record->status ?? 'diajukan';
            $this->pic = $record->pic ?? '';
            $this->target_date = optional($record->target_date)->format('Y-m-d');
            $this->personnel_ref = $record->personnel_ref;
            $this->dynamicRows = collect($record->dynamic_data ?? [])
                ->map(fn ($value, $key) => ['key' => $key, 'value' => is_array($value) ? json_encode($value) : $value])
                ->values()
                ->toArray();
        } else {
            $this->reset([
                'asset_id', 'title', 'risk_type', 'category', 'priority',
                'inherent_risk_level', 'decision', 'pic', 'target_date', 'personnel_ref', 'dynamicRows',
            ]);
            $this->status = 'diajukan';
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
        $payload = $this->nullifyBlankEnums($payload, ['risk_type', 'inherent_risk_level']);
        $payload['dynamic_data'] = $dynamicData;
        $payload['personnel_ref'] = $this->ensurePersonnelRef($payload['personnel_ref'] ?? null);
        $payload['verification_status'] = auth()->user()?->isAdmin() ? 'tervalidasi' : 'menunggu_verifikasi';

        if ($this->recordId) {
            DataInformation::findOrFail($this->recordId)->update($payload);
        } else {
            DataInformation::create($payload);
        }

        $this->show = false;
        $this->dispatch('data-information-saved');
        $this->dispatch('toast', message: $this->recordId ? 'Catatan diperbarui.' : 'Catatan baru ditambahkan.');
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.data-information.data-information-form-modal', [
            'assets' => Asset::orderBy('name')->get(['id', 'name', 'asset_code']),
            'types' => ChangeType::cases(),
            'levels' => Level::cases(),
        ]);
    }
}
