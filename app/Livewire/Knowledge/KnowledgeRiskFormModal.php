<?php

namespace App\Livewire\Knowledge;

use App\Enums\Level;
use App\Livewire\Concerns\GeneratesPersonnelRef;
use App\Livewire\Concerns\GuardsWriteAccess;
use App\Livewire\Concerns\NormalizesEnumInputs;
use App\Models\HrRisk;
use App\Models\KnowledgeAsset;
use App\Models\KnowledgeRisk;
use App\Services\KnowledgeRiskLevelService;
use Livewire\Attributes\On;
use Livewire\Component;

class KnowledgeRiskFormModal extends Component
{
    use GuardsWriteAccess, NormalizesEnumInputs, GeneratesPersonnelRef;

    public bool $show = false;

    public ?int $recordId = null;

    public ?int $knowledge_asset_id = null;

    public string $pernyataan_risiko = '';

    public string $akar_penyebab = '';

    public string $area_dampak = '';

    public string $dampak = '';

    public string $kemungkinan = '';

    public string $rencana_mitigasi = '';

    public string $penanggung_jawab = '';

    public string $tingkat_residual_risk = '';

    public ?string $personnel_ref = null;

    protected function rules(): array
    {
        return [
            'knowledge_asset_id' => ['nullable', 'exists:knowledge_assets,id'],
            'pernyataan_risiko' => ['required', 'string', 'max:2000'],
            'akar_penyebab' => ['nullable', 'string', 'max:2000'],
            'area_dampak' => ['nullable', 'string', 'max:255'],
            'dampak' => ['required', 'integer', 'min:1', 'max:5'],
            'kemungkinan' => ['required', 'integer', 'min:1', 'max:5'],
            'rencana_mitigasi' => ['nullable', 'string', 'max:2000'],
            'penanggung_jawab' => ['nullable', 'string', 'max:255'],
            'tingkat_residual_risk' => ['nullable', 'string', 'in:' . implode(',', array_column(Level::cases(), 'value'))],
            'personnel_ref' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Derived server-side from the exact BSSN 5x5 matrix, same "computed,
     * shown as read-only badge" pattern as Asset kritikalitas and the main
     * Risk module's risk_level.
     */
    public function derivedLevel(): ?Level
    {
        return (new KnowledgeRiskLevelService())->derive(
            $this->kemungkinan !== '' ? (int) $this->kemungkinan : null,
            $this->dampak !== '' ? (int) $this->dampak : null,
        );
    }

    #[On('open-knowledge-risk-form')]
    public function open(?int $recordId = null): void
    {
        $this->resetValidation();
        $this->recordId = $recordId;

        if ($recordId) {
            $record = KnowledgeRisk::findOrFail($recordId);
            $this->knowledge_asset_id = $record->knowledge_asset_id;
            $this->pernyataan_risiko = $record->pernyataan_risiko;
            $this->akar_penyebab = $record->akar_penyebab ?? '';
            $this->area_dampak = $record->area_dampak ?? '';
            $this->dampak = (string) ($record->dampak ?? '');
            $this->kemungkinan = (string) ($record->kemungkinan ?? '');
            $this->rencana_mitigasi = $record->rencana_mitigasi ?? '';
            $this->penanggung_jawab = $record->penanggung_jawab ?? '';
            $this->tingkat_residual_risk = $record->tingkat_residual_risk?->value ?? '';
            $this->personnel_ref = $record->personnel_ref;
        } else {
            $this->reset([
                'knowledge_asset_id', 'pernyataan_risiko', 'akar_penyebab', 'area_dampak',
                'dampak', 'kemungkinan', 'rencana_mitigasi', 'penanggung_jawab', 'tingkat_residual_risk', 'personnel_ref',
            ]);
        }

        $this->show = true;
    }

    public function save(): void
    {
        $this->ensureCanWrite();

        $validated = $this->validate();
        $payload = $this->nullifyBlankEnums($validated, ['tingkat_residual_risk']);

        $payload['personnel_ref'] = $this->ensurePersonnelRef($payload['personnel_ref'] ?? null);
        $payload['skor_risiko_bawaan'] = $payload['dampak'] * $payload['kemungkinan'];
        $payload['tingkat_risiko_bawaan'] = $this->derivedLevel()?->value;
        $payload['verification_status'] = auth()->user()?->canAutoVerify('pengetahuan') ? 'tervalidasi' : 'menunggu_verifikasi';

        if ($this->recordId) {
            KnowledgeRisk::findOrFail($this->recordId)->update($payload);
        } else {
            KnowledgeRisk::create($payload);
        }

        $this->show = false;
        $this->dispatch('knowledge-risk-saved');
        $this->dispatch('toast', message: $this->recordId ? 'Risiko diperbarui.' : 'Risiko baru ditambahkan.');
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.knowledge.knowledge-risk-form-modal', [
            'assets' => KnowledgeAsset::orderBy('title')->get(['id', 'title', 'legacy_code']),
            'hrRisks' => HrRisk::whereNotNull('personnel_ref')->where('personnel_ref', '!=', '')->orderBy('subject')->get(['id', 'subject', 'record_code', 'personnel_ref']),
            'levels' => Level::cases(),
        ]);
    }
}
