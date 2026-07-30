<?php

namespace App\Livewire\HrRisks;

use App\Enums\Level;
use App\Livewire\Concerns\GeneratesPersonnelRef;
use App\Livewire\Concerns\GuardsWriteAccess;
use App\Livewire\Concerns\NormalizesEnumInputs;
use App\Models\HrRisk;
use Livewire\Attributes\On;
use Livewire\Component;

class HrRiskFormModal extends Component
{
    use GuardsWriteAccess, NormalizesEnumInputs, GeneratesPersonnelRef;

    public bool $show = false;

    public ?int $recordId = null;

    public string $subject = '';

    public string $title = '';

    public string $category = '';

    public string $inherent_risk_level = '';

    public string $status = 'diajukan';

    public string $pic = '';

    public ?string $target_date = null;

    public ?string $personnel_ref = null;

    /** @var array<int, array{key: string, value: string}> */
    public array $dynamicRows = [];

    protected function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'inherent_risk_level' => ['nullable', 'string', 'in:' . implode(',', array_column(Level::cases(), 'value'))],
            'status' => ['required', 'string', 'max:50'],
            'pic' => ['nullable', 'string', 'max:255'],
            'target_date' => ['nullable', 'date'],
            'personnel_ref' => ['nullable', 'string', 'max:50'],
            'dynamicRows.*.key' => ['nullable', 'string', 'max:100'],
            'dynamicRows.*.value' => ['nullable', 'string', 'max:2000'],
        ];
    }

    #[On('open-hr-risk-form')]
    public function open(?int $recordId = null): void
    {
        $this->resetValidation();
        $this->recordId = $recordId;

        if ($recordId) {
            $record = HrRisk::findOrFail($recordId);
            $this->subject = $record->subject;
            $this->title = $record->title;
            $this->category = $record->category ?? '';
            $this->inherent_risk_level = $record->inherent_risk_level?->value ?? '';
            $this->status = $record->status ?? 'diajukan';
            $this->pic = $record->pic ?? '';
            $this->target_date = optional($record->target_date)->format('Y-m-d');
            $this->personnel_ref = $record->personnel_ref;
            $this->dynamicRows = collect($record->dynamic_data ?? [])
                ->map(fn ($value, $key) => ['key' => $key, 'value' => is_array($value) ? json_encode($value) : $value])
                ->values()
                ->toArray();
        } else {
            $this->reset(['subject', 'title', 'category', 'inherent_risk_level', 'pic', 'target_date', 'personnel_ref', 'dynamicRows']);
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
        $payload = $this->nullifyBlankEnums($payload, ['inherent_risk_level']);
        $payload['dynamic_data'] = $dynamicData;
        $payload['personnel_ref'] = $this->ensurePersonnelRef($payload['personnel_ref'] ?? null);
        $payload['verification_status'] = auth()->user()?->isAdmin() ? 'tervalidasi' : 'menunggu_verifikasi';

        if ($this->recordId) {
            HrRisk::findOrFail($this->recordId)->update($payload);
        } else {
            HrRisk::create($payload);
        }

        $this->show = false;
        $this->dispatch('hr-risk-saved');
        $this->dispatch('toast', message: $this->recordId ? 'Risiko diperbarui.' : 'Risiko baru ditambahkan.');
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.hr-risks.hr-risk-form-modal', [
            'levels' => Level::cases(),
        ]);
    }
}
