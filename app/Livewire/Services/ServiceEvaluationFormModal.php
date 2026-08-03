<?php

namespace App\Livewire\Services;

use App\Livewire\Concerns\GuardsWriteAccess;
use App\Models\ServiceEvaluation;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Same gap as ServiceTicketFormModal, for "Evaluasi Kinerja" — no manual
 * way to log a periodic performance review before this.
 */
class ServiceEvaluationFormModal extends Component
{
    use GuardsWriteAccess;

    public bool $show = false;

    public ?int $serviceId = null;

    public ?string $uptime_actual = null;

    public ?string $sla_target = null;

    public ?string $achievement_status = null;

    public ?int $incident_count = null;

    public ?string $mttr = null;

    public ?string $recommendation = null;

    protected function rules(): array
    {
        return [
            'uptime_actual' => ['nullable', 'string', 'max:20'],
            'sla_target' => ['nullable', 'string', 'max:20'],
            'achievement_status' => ['nullable', 'string', 'max:50'],
            'incident_count' => ['nullable', 'integer', 'min:0'],
            'mttr' => ['nullable', 'string', 'max:50'],
            'recommendation' => ['nullable', 'string', 'max:2000'],
        ];
    }

    #[On('open-service-evaluation-form')]
    public function open(int $serviceId): void
    {
        $this->resetValidation();
        $this->reset(['uptime_actual', 'sla_target', 'achievement_status', 'incident_count', 'mttr', 'recommendation']);
        $this->serviceId = $serviceId;
        $this->show = true;
    }

    public function save(): void
    {
        $this->ensureCanWrite();

        $validated = $this->validate();
        $validated['service_id'] = $this->serviceId;

        ServiceEvaluation::create($validated);

        $this->show = false;
        $this->dispatch('service-evaluation-saved');
        $this->dispatch('toast', message: 'Evaluasi kinerja ditambahkan.');
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.services.service-evaluation-form-modal');
    }
}
