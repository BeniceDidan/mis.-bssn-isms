<?php

namespace App\Livewire\Services;

use App\Livewire\Concerns\GeneratesPersonnelRef;
use App\Livewire\Concerns\GuardsWriteAccess;
use App\Models\Asset;
use App\Models\Service;
use Livewire\Attributes\On;
use Livewire\Component;

class ServiceFormModal extends Component
{
    use GuardsWriteAccess, GeneratesPersonnelRef;

    public bool $show = false;

    public ?int $recordId = null;

    public string $name = '';

    public string $description = '';

    public string $owner_unit = '';

    public string $technical_manager = '';

    public string $access_method = '';

    public string $sla_target = '';

    public string $service_hours = '';

    public string $status = 'aktif';

    public ?string $personnel_ref = null;

    /** @var array<int, int> */
    public array $asset_ids = [];

    /** @var array<int, array{key: string, value: string}> */
    public array $dynamicRows = [];

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'owner_unit' => ['nullable', 'string', 'max:255'],
            'technical_manager' => ['nullable', 'string', 'max:255'],
            'access_method' => ['nullable', 'string', 'max:255'],
            'sla_target' => ['nullable', 'string', 'max:50'],
            'service_hours' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'personnel_ref' => ['nullable', 'string', 'max:50'],
            'asset_ids' => ['array'],
            'asset_ids.*' => ['exists:assets,id'],
            'dynamicRows.*.key' => ['nullable', 'string', 'max:100'],
            'dynamicRows.*.value' => ['nullable', 'string', 'max:2000'],
        ];
    }

    #[On('open-service-form')]
    public function open(?int $recordId = null): void
    {
        $this->resetValidation();
        $this->recordId = $recordId;

        if ($recordId) {
            $record = Service::with('assets:id')->findOrFail($recordId);
            $this->name = $record->name;
            $this->description = $record->description ?? '';
            $this->owner_unit = $record->owner_unit ?? '';
            $this->technical_manager = $record->technical_manager ?? '';
            $this->access_method = $record->access_method ?? '';
            $this->sla_target = $record->sla_target ?? '';
            $this->service_hours = $record->service_hours ?? '';
            $this->status = $record->status ?? 'aktif';
            $this->asset_ids = $record->assets->pluck('id')->toArray();
            $this->personnel_ref = $record->personnel_ref;
            $this->dynamicRows = collect($record->dynamic_data ?? [])
                ->map(fn ($value, $key) => ['key' => $key, 'value' => is_array($value) ? json_encode($value) : $value])
                ->values()
                ->toArray();
        } else {
            $this->reset([
                'name', 'description', 'owner_unit', 'technical_manager',
                'access_method', 'sla_target', 'service_hours', 'personnel_ref', 'asset_ids', 'dynamicRows',
            ]);
            $this->status = 'aktif';
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

        $payload = collect($validated)->except(['dynamicRows', 'asset_ids'])->toArray();
        $payload['dynamic_data'] = $dynamicData;
        $payload['personnel_ref'] = $this->ensurePersonnelRef($payload['personnel_ref'] ?? null);
        $payload['verification_status'] = auth()->user()?->isAdmin() ? 'tervalidasi' : 'menunggu_verifikasi';

        if ($this->recordId) {
            $service = Service::findOrFail($this->recordId);
            $service->update($payload);
        } else {
            $service = Service::create($payload);
        }

        $service->assets()->sync($validated['asset_ids']);
        $service->bridgeToLinkedAssets($this->recordId ? 'updated' : 'created');

        $this->show = false;
        $this->dispatch('service-saved');
        $this->dispatch('toast', message: $this->recordId ? 'Layanan diperbarui.' : 'Layanan baru ditambahkan.');
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.services.service-form-modal', [
            'assets' => Asset::orderBy('name')->get(['id', 'name', 'asset_code']),
        ]);
    }
}
