<?php

namespace App\Livewire\Risks;

use App\Enums\Level;
use App\Enums\RiskCategory;
use App\Enums\RiskStatus;
use App\Enums\TreatmentStrategy;
use App\Livewire\Concerns\GeneratesPersonnelRef;
use App\Livewire\Concerns\GuardsWriteAccess;
use App\Livewire\Concerns\NormalizesEnumInputs;
use App\Models\Asset;
use App\Models\HrRisk;
use App\Models\Risk;
use App\Services\RiskLevelService;
use Livewire\Attributes\On;
use Livewire\Component;

class RiskFormModal extends Component
{
    use GuardsWriteAccess, NormalizesEnumInputs, GeneratesPersonnelRef;

    public bool $show = false;

    public ?int $riskId = null;

    public ?int $asset_id = null;

    public string $title = '';

    public string $category = '';

    public ?string $threat_source = '';

    public ?string $vulnerability = '';

    public string $likelihood = '';

    public string $impact = '';

    public string $risk_owner = '';

    public string $treatment_strategy = '';

    public string $status = 'open';

    public ?string $identified_at = null;

    public ?string $review_due_at = null;

    public ?string $personnel_ref = null;

    /** @var array<int, array{key: string, value: string}> */
    public array $dynamicRows = [];

    protected function rules(): array
    {
        return [
            'asset_id' => ['nullable', 'exists:assets,id'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:' . implode(',', array_column(RiskCategory::cases(), 'value'))],
            'threat_source' => ['nullable', 'string', 'max:255'],
            'vulnerability' => ['nullable', 'string', 'max:2000'],
            'likelihood' => ['required', 'string', 'in:' . implode(',', array_column(Level::cases(), 'value'))],
            'impact' => ['required', 'string', 'in:' . implode(',', array_column(Level::cases(), 'value'))],
            'risk_owner' => ['nullable', 'string', 'max:255'],
            'treatment_strategy' => ['nullable', 'string', 'in:' . implode(',', array_column(TreatmentStrategy::cases(), 'value'))],
            'status' => ['required', 'string', 'in:' . implode(',', array_column(RiskStatus::cases(), 'value'))],
            'identified_at' => ['nullable', 'date'],
            'review_due_at' => ['nullable', 'date', 'after_or_equal:identified_at'],
            'personnel_ref' => ['nullable', 'string', 'max:50'],
            'dynamicRows.*.key' => ['nullable', 'string', 'max:100'],
            'dynamicRows.*.value' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * likelihood x impact, escalated by the linked asset's kritikalitas AND
     * by any Tinggi/Kritis SDM record sharing this risk's Kode Personil —
     * see RiskLevelService for the formula. Kept deterministic and
     * server-side so the badge shown in the table always matches the
     * inputs actually chosen. $personnelRef is passed in explicitly (rather
     * than read from $this->personnel_ref) because save() calls this after
     * ensurePersonnelRef() has already resolved a blank field to a freshly
     * generated code — the property itself is never written back.
     */
    protected function deriveRiskLevel(?string $personnelRef = null): ?string
    {
        $assetCriticality = $this->asset_id
            ? Asset::find($this->asset_id)?->criticality_level?->value
            : null;

        $sdmSeverity = HrRisk::hasSevereRiskFor($personnelRef) ? Level::Tinggi->value : null;

        return (new RiskLevelService())->derive($this->likelihood, $this->impact, $assetCriticality, $sdmSeverity);
    }

    /**
     * Exposed to the Blade view so the form can show *why* the level might
     * be escalated — the linked asset's own kritikalitas badge.
     */
    public function selectedAssetCriticality(): ?Level
    {
        if (! $this->asset_id) {
            return null;
        }

        return Asset::find($this->asset_id)?->criticality_level;
    }

    /**
     * Exposed to the Blade view for the same "why the badge jumped" caption
     * as selectedAssetCriticality() — true if the *currently entered* Kode
     * Personil (still live-typed, not yet resolved by ensurePersonnelRef())
     * already has a Tinggi/Kritis SDM record attached.
     */
    public function hasSevereSdmLink(): bool
    {
        return HrRisk::hasSevereRiskFor($this->personnel_ref);
    }

    #[On('open-risk-form')]
    public function open(?int $riskId = null): void
    {
        $this->resetValidation();
        $this->riskId = $riskId;

        if ($riskId) {
            $risk = Risk::findOrFail($riskId);
            $this->asset_id = $risk->asset_id;
            $this->title = $risk->title;
            $this->category = $risk->category?->value ?? '';
            $this->threat_source = $risk->threat_source;
            $this->vulnerability = $risk->vulnerability;
            $this->likelihood = $risk->likelihood?->value ?? '';
            $this->impact = $risk->impact?->value ?? '';
            $this->risk_owner = $risk->risk_owner ?? '';
            $this->treatment_strategy = $risk->treatment_strategy?->value ?? '';
            $this->status = $risk->status?->value ?? 'open';
            $this->identified_at = optional($risk->identified_at)->format('Y-m-d');
            $this->review_due_at = optional($risk->review_due_at)->format('Y-m-d');
            $this->personnel_ref = $risk->personnel_ref;
            $this->dynamicRows = collect($risk->dynamic_data ?? [])
                ->map(fn ($value, $key) => ['key' => $key, 'value' => is_array($value) ? json_encode($value) : $value])
                ->values()
                ->toArray();
        } else {
            $this->reset([
                'asset_id', 'title', 'category', 'threat_source', 'vulnerability',
                'likelihood', 'impact', 'risk_owner', 'treatment_strategy',
                'identified_at', 'review_due_at', 'personnel_ref', 'dynamicRows',
            ]);
            $this->status = 'open';
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
        $payload = $this->nullifyBlankEnums($payload, ['treatment_strategy']);
        $payload['dynamic_data'] = $dynamicData;
        $payload['personnel_ref'] = $this->ensurePersonnelRef($payload['personnel_ref'] ?? null);
        $payload['risk_level'] = $this->deriveRiskLevel($payload['personnel_ref']);
        $payload['verification_status'] = auth()->user()?->canAutoVerify('risiko') ? 'tervalidasi' : 'menunggu_verifikasi';

        if ($this->riskId) {
            Risk::findOrFail($this->riskId)->update($payload);
        } else {
            Risk::create($payload);
        }

        $this->show = false;
        $this->dispatch('risk-saved');
        $this->dispatch('toast', message: $this->riskId ? 'Risiko diperbarui.' : 'Risiko baru ditambahkan.');
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.risks.risk-form-modal', [
            'assets' => Asset::orderBy('name')->get(['id', 'name', 'asset_code']),
            'hrRisks' => HrRisk::whereNotNull('personnel_ref')->where('personnel_ref', '!=', '')->orderBy('subject')->get(['id', 'subject', 'record_code', 'personnel_ref']),
            'categories' => RiskCategory::cases(),
            'levels' => Level::cases(),
            'treatmentStrategies' => TreatmentStrategy::cases(),
            'statuses' => RiskStatus::cases(),
        ]);
    }
}
