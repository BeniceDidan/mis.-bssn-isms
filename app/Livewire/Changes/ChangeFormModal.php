<?php

namespace App\Livewire\Changes;

use App\Enums\ChangeType;
use App\Enums\Level;
use App\Livewire\Concerns\GeneratesPersonnelRef;
use App\Livewire\Concerns\GuardsWriteAccess;
use App\Livewire\Concerns\NormalizesEnumInputs;
use App\Models\Asset;
use App\Models\Change;
use App\Models\HrRisk;
use Livewire\Attributes\On;
use Livewire\Component;

class ChangeFormModal extends Component
{
    use GuardsWriteAccess, NormalizesEnumInputs, GeneratesPersonnelRef;

    public bool $show = false;

    public ?int $changeId = null;

    public ?int $asset_id = null;

    public string $title = '';

    public string $change_type = '';

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
            'change_type' => ['nullable', 'string', 'in:' . implode(',', array_column(ChangeType::cases(), 'value'))],
            'category' => ['nullable', 'string', 'max:50'],
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

    #[On('open-change-form')]
    public function open(?int $changeId = null): void
    {
        $this->resetValidation();
        $this->changeId = $changeId;

        if ($changeId) {
            $change = Change::findOrFail($changeId);
            $this->asset_id = $change->asset_id;
            $this->title = $change->title;
            $this->change_type = $change->change_type?->value ?? '';
            $this->category = $change->category ?? '';
            $this->priority = $change->priority ?? '';
            $this->inherent_risk_level = $change->inherent_risk_level?->value ?? '';
            $this->decision = $change->decision ?? '';
            $this->status = $change->status ?? 'diajukan';
            $this->pic = $change->pic ?? '';
            $this->target_date = optional($change->target_date)->format('Y-m-d');
            $this->personnel_ref = $change->personnel_ref;
            $this->dynamicRows = collect($change->dynamic_data ?? [])
                ->map(fn ($value, $key) => ['key' => $key, 'value' => is_array($value) ? json_encode($value) : $value])
                ->values()
                ->toArray();
        } else {
            $this->reset([
                'asset_id', 'title', 'change_type', 'category', 'priority',
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
        $payload = $this->nullifyBlankEnums($payload, ['change_type', 'inherent_risk_level']);
        $payload['dynamic_data'] = $dynamicData;
        $payload['personnel_ref'] = $this->ensurePersonnelRef($payload['personnel_ref'] ?? null);
        $payload['verification_status'] = auth()->user()?->canAutoVerify('perubahan') ? 'tervalidasi' : 'menunggu_verifikasi';

        if ($this->changeId) {
            Change::findOrFail($this->changeId)->update($payload);
        } else {
            Change::create($payload);
        }

        $this->show = false;
        $this->dispatch('change-saved');
        $this->dispatch('toast', message: $this->changeId ? 'Perubahan diperbarui.' : 'Perubahan baru ditambahkan.');
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.changes.change-form-modal', [
            'assets' => Asset::orderBy('name')->get(['id', 'name', 'asset_code']),
            'hrRisks' => HrRisk::whereNotNull('personnel_ref')->where('personnel_ref', '!=', '')->orderBy('subject')->get(['id', 'subject', 'record_code', 'personnel_ref']),
            'types' => ChangeType::cases(),
            'levels' => Level::cases(),
        ]);
    }
}
