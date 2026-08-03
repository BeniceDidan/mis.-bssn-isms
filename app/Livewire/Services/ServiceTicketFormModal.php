<?php

namespace App\Livewire\Services;

use App\Enums\Level;
use App\Livewire\Concerns\GuardsWriteAccess;
use App\Livewire\Concerns\NormalizesEnumInputs;
use App\Models\ServiceTicket;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * "Log Operasional" was previously view-only — every ServiceTicket in the
 * demo data came from either the original Excel import or the automatic
 * risk-escalation chain (RiskEscalationResponseService), with no way for a
 * User to log one by hand. This is that missing manual path.
 */
class ServiceTicketFormModal extends Component
{
    use GuardsWriteAccess, NormalizesEnumInputs;

    public bool $show = false;

    public ?int $serviceId = null;

    public ?string $legacy_code = null;

    public ?string $reported_at = null;

    public string $requester_name = '';

    public string $issue = '';

    public string $impact_level = '';

    public ?string $related_risk_text = null;

    public ?string $resolution = null;

    public string $status = 'baru';

    protected function rules(): array
    {
        return [
            'legacy_code' => ['nullable', 'string', 'max:20'],
            'reported_at' => ['nullable', 'date'],
            'requester_name' => ['nullable', 'string', 'max:255'],
            'issue' => ['required', 'string', 'max:2000'],
            'impact_level' => ['nullable', 'string', 'in:' . implode(',', array_column(Level::cases(), 'value'))],
            'related_risk_text' => ['nullable', 'string', 'max:255'],
            'resolution' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'string', 'max:50'],
        ];
    }

    #[On('open-service-ticket-form')]
    public function open(int $serviceId): void
    {
        $this->resetValidation();
        $this->reset(['legacy_code', 'reported_at', 'requester_name', 'issue', 'impact_level', 'related_risk_text', 'resolution']);
        $this->serviceId = $serviceId;
        $this->reported_at = now()->format('Y-m-d');
        $this->status = 'baru';
        $this->show = true;
    }

    public function save(): void
    {
        $this->ensureCanWrite();

        $validated = $this->validate();
        $validated = $this->nullifyBlankEnums($validated, ['impact_level']);
        $validated['service_id'] = $this->serviceId;

        ServiceTicket::create($validated);

        $this->show = false;
        $this->dispatch('service-ticket-saved');
        $this->dispatch('toast', message: 'Log operasional ditambahkan.');
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.services.service-ticket-form-modal', [
            'levels' => Level::cases(),
        ]);
    }
}
