<?php

namespace App\Livewire\Assets;

use App\Enums\AssetCategory;
use App\Enums\Level;
use App\Livewire\Concerns\GeneratesPersonnelRef;
use App\Livewire\Concerns\GuardsWriteAccess;
use App\Models\Asset;
use App\Services\KritikalitasService;
use Livewire\Attributes\On;
use Livewire\Component;

class AssetFormModal extends Component
{
    use GuardsWriteAccess, GeneratesPersonnelRef;

    public bool $show = false;

    public ?int $assetId = null;

    public string $category = '';

    public string $sub_classification = '';

    public string $name = '';

    public ?string $owner = '';

    public ?string $location = '';

    public ?string $status = '';

    public string $criticality_level = '';

    /**
     * CIA-triad inputs feeding KritikalitasService. Bound as strings (empty
     * string = "not scored yet") so the manual criticality_level dropdown
     * keeps working unchanged for the assets that don't have these set —
     * the moment all three are filled, updated() below auto-computes and
     * takes over criticality_level.
     */
    public string $confidentiality_score = '';

    public string $integrity_score = '';

    public string $availability_score = '';

    public ?int $reference_year = null;

    public ?string $personnel_ref = null;

    /**
     * Free-form key/value editor for dynamic_data. A category-aware field
     * template (so "Alamat IP" only appears for Perangkat Lunak, etc.) is a
     * good follow-up but out of scope for this pass — this generic editor
     * still guarantees no field from the source Excel is ever unrepresentable.
     *
     * @var array<int, array{key: string, value: string}>
     */
    public array $dynamicRows = [];

    protected function rules(): array
    {
        return [
            'category' => ['required', 'string', 'in:' . implode(',', array_column(AssetCategory::cases(), 'value'))],
            'sub_classification' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:255'],
            'owner' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'criticality_level' => ['required', 'string', 'in:' . implode(',', array_column(Level::cases(), 'value'))],
            'confidentiality_score' => ['nullable', 'integer', 'between:1,5'],
            'integrity_score' => ['nullable', 'integer', 'between:1,5'],
            'availability_score' => ['nullable', 'integer', 'between:1,5'],
            'reference_year' => ['nullable', 'integer', 'min:1990', 'max:2100'],
            'personnel_ref' => ['nullable', 'string', 'max:50'],
            'dynamicRows.*.key' => ['nullable', 'string', 'max:100'],
            'dynamicRows.*.value' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Live-recomputes criticality_level the moment all three CIA scores are
     * filled, so the read-only badge in the form always matches what save()
     * is about to persist — the same "server-side, always in sync" spirit
     * as RiskFormModal::deriveRiskLevel().
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['confidentiality_score', 'integrity_score', 'availability_score'], true)) {
            $computed = $this->computedCriticality();

            if ($computed !== null) {
                $this->criticality_level = $computed;
            }
        }
    }

    public function computedCriticality(): ?string
    {
        if ($this->confidentiality_score === '' || $this->integrity_score === '' || $this->availability_score === '') {
            return null;
        }

        return (new KritikalitasService())->compute(
            (int) $this->confidentiality_score,
            (int) $this->integrity_score,
            (int) $this->availability_score
        );
    }

    #[On('open-asset-form')]
    public function open(?int $assetId = null): void
    {
        $this->resetValidation();
        $this->assetId = $assetId;

        if ($assetId) {
            $asset = Asset::findOrFail($assetId);
            $this->category = $asset->category?->value ?? '';
            $this->sub_classification = $asset->sub_classification;
            $this->name = $asset->name;
            $this->owner = $asset->owner;
            $this->location = $asset->location;
            $this->status = $asset->status;
            $this->criticality_level = $asset->criticality_level?->value ?? '';
            $this->confidentiality_score = $asset->confidentiality_score !== null ? (string) $asset->confidentiality_score : '';
            $this->integrity_score = $asset->integrity_score !== null ? (string) $asset->integrity_score : '';
            $this->availability_score = $asset->availability_score !== null ? (string) $asset->availability_score : '';
            $this->reference_year = $asset->reference_year;
            $this->personnel_ref = $asset->personnel_ref;
            $this->dynamicRows = collect($asset->dynamic_data ?? [])
                ->map(fn ($value, $key) => ['key' => $key, 'value' => is_array($value) ? json_encode($value) : $value])
                ->values()
                ->toArray();
        } else {
            $this->reset([
                'category', 'sub_classification', 'name', 'owner', 'location', 'status', 'criticality_level',
                'confidentiality_score', 'integrity_score', 'availability_score', 'reference_year', 'personnel_ref', 'dynamicRows',
            ]);
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

    /**
     * Upsert logic per rule 3: an existing record is updated in place by ID,
     * never blindly re-inserted as a duplicate.
     */
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
        $payload['confidentiality_score'] = $this->confidentiality_score !== '' ? (int) $this->confidentiality_score : null;
        $payload['integrity_score'] = $this->integrity_score !== '' ? (int) $this->integrity_score : null;
        $payload['availability_score'] = $this->availability_score !== '' ? (int) $this->availability_score : null;

        $computed = $this->computedCriticality();
        if ($computed !== null) {
            $payload['criticality_level'] = $computed;
        }

        // Verifikasi workflow (BPMN: User submit -> Admin memeriksa -> ACC
        // atau koreksi): a non-admin's save always goes back into the
        // queue, even edits to an already-approved asset, so the admin
        // sees every change again. Admin saves are self-verifying.
        $payload['verification_status'] = auth()->user()?->isAdmin() ? 'tervalidasi' : 'menunggu_verifikasi';

        if ($this->assetId) {
            Asset::findOrFail($this->assetId)->update($payload);
        } else {
            Asset::create($payload);
        }

        $this->show = false;
        $this->dispatch('asset-saved');
        $this->dispatch('toast', message: $this->assetId ? 'Aset diperbarui.' : 'Aset baru ditambahkan.');
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.assets.asset-form-modal', [
            'categories' => AssetCategory::cases(),
            'levels' => Level::cases(),
        ]);
    }
}
