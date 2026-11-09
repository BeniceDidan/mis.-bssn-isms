<?php

namespace App\Livewire\Services;

use App\Enums\Level;
use App\Livewire\Concerns\GuardsWriteAccess;
use App\Livewire\Concerns\NormalizesEnumInputs;
use App\Models\Service;
use App\Models\ServiceTicket;
use App\Services\Ai\TicketTriage;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * "Log Operasional" was previously view-only — every ServiceTicket in the
 * demo data came from either the original Excel import or the automatic
 * risk-escalation chain (RiskEscalationResponseService), with no way for a
 * User to log one by hand. This is that missing manual path.
 *
 * Sekarang juga menjadi form tiket Helpdesk: bisa dibuka tanpa layanan
 * terpilih (dari tab Tiket Helpdesk), bisa mengubah tiket lama, dan punya
 * tombol "Analisis AI" yang mengisi kategori, dampak, urgensi, prioritas,
 * dan draf balasan. Bila disimpan tanpa kategori, triase berjalan otomatis.
 */
class ServiceTicketFormModal extends Component
{
    use GuardsWriteAccess, NormalizesEnumInputs;

    public bool $show = false;

    public ?int $ticketId = null;

    public ?int $serviceId = null;

    /** Layanan dikunci bila form dibuka dari panel detail layanan. */
    public bool $serviceLocked = false;

    public ?string $legacy_code = null;

    public ?string $reported_at = null;

    public string $requester_name = '';

    public string $issue = '';

    public string $category = '';

    public string $impact_level = '';

    public string $urgency = '';

    public ?string $related_risk_text = null;

    public ?string $resolution = null;

    public ?string $draft_reply = null;

    public string $status = 'baru';

    public ?array $triage = null;

    protected function rules(): array
    {
        $levels = implode(',', array_column(Level::cases(), 'value'));

        return [
            'serviceId' => ['required', 'exists:services,id'],
            'legacy_code' => ['nullable', 'string', 'max:20'],
            'reported_at' => ['nullable', 'date'],
            'requester_name' => ['nullable', 'string', 'max:255'],
            'issue' => ['required', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'in:' . implode(',', array_keys(config('helpdesk.categories')))],
            'impact_level' => ['nullable', 'string', 'in:' . $levels],
            'urgency' => ['nullable', 'string', 'in:' . $levels],
            'related_risk_text' => ['nullable', 'string', 'max:255'],
            'resolution' => ['nullable', 'string', 'max:2000'],
            'draft_reply' => ['nullable', 'string', 'max:4000'],
            'status' => ['required', 'string', 'max:50'],
        ];
    }

    protected $messages = [
        'serviceId.required' => 'Pilih layanan yang dilaporkan.',
        'issue.required' => 'Tulis isi permintaan atau gangguannya.',
    ];

    #[On('open-service-ticket-form')]
    public function open(?int $serviceId = null, ?int $ticketId = null): void
    {
        $this->resetValidation();
        $this->reset(['ticketId', 'legacy_code', 'reported_at', 'requester_name', 'issue', 'category', 'impact_level', 'urgency', 'related_risk_text', 'resolution', 'draft_reply', 'triage']);

        if ($ticketId) {
            $ticket = ServiceTicket::findOrFail($ticketId);
            $this->ticketId = $ticket->id;
            $this->serviceId = $ticket->service_id;
            $this->legacy_code = $ticket->legacy_code;
            $this->reported_at = optional($ticket->reported_at)->format('Y-m-d');
            $this->requester_name = (string) $ticket->requester_name;
            $this->issue = (string) $ticket->issue;
            $this->category = (string) $ticket->category;
            $this->impact_level = (string) $ticket->impact_level?->value;
            $this->urgency = (string) $ticket->urgency;
            $this->related_risk_text = $ticket->related_risk_text;
            $this->resolution = $ticket->resolution;
            $this->draft_reply = $ticket->draft_reply;
            $this->status = $ticket->status ?: 'baru';
            $this->triage = $ticket->triage;
            $this->serviceLocked = false;
        } else {
            $this->serviceId = $serviceId;
            $this->serviceLocked = $serviceId !== null;
            $this->reported_at = now()->format('Y-m-d');
            $this->status = 'baru';
        }

        $this->show = true;
    }

    /** Isi kategori, dampak, urgensi, dan draf balasan dari teks tiket. */
    public function analyze(TicketTriage $triage): void
    {
        $this->validateOnly('issue');
        $this->applyTriage($triage);
    }

    public function save(TicketTriage $triage): void
    {
        $this->ensureCanWrite();
        $this->validate();

        if ($this->category === '') {
            $this->applyTriage($triage, onlyEmpty: true);
        }

        $payload = [
            'service_id' => $this->serviceId,
            'legacy_code' => $this->legacy_code ?: null,
            'reported_at' => $this->reported_at ?: null,
            'requester_name' => $this->requester_name ?: null,
            'issue' => $this->issue,
            'category' => $this->category ?: null,
            'impact_level' => $this->impact_level ?: null,
            'urgency' => $this->urgency ?: null,
            'priority' => TicketTriage::priorityFor($this->impact_level ?: null, $this->urgency ?: null),
            'related_risk_text' => $this->related_risk_text ?: null,
            'resolution' => $this->resolution ?: null,
            'draft_reply' => $this->draft_reply ?: null,
            'triage' => $this->triage,
            'status' => $this->status,
        ];

        if ($this->ticketId) {
            ServiceTicket::findOrFail($this->ticketId)->update($payload);
        } else {
            ServiceTicket::create($payload);
        }

        $this->show = false;
        $this->dispatch('service-ticket-saved');
        $this->dispatch('toast', message: $this->ticketId ? 'Tiket diperbarui.' : 'Tiket ditambahkan.');
    }

    private function applyTriage(TicketTriage $triage, bool $onlyEmpty = false): void
    {
        $service = $this->serviceId ? Service::with('assets')->find($this->serviceId) : null;
        $label = $this->ticketId ? ServiceTicket::find($this->ticketId)?->label() : null;
        $result = $triage->triage($this->issue, $service, $this->requester_name ?: null, $label);

        foreach (['category', 'impact_level', 'urgency', 'draft_reply'] as $field) {
            if (! $onlyEmpty || blank($this->{$field})) {
                $this->{$field} = $result[$field];
            }
        }
        $this->triage = $result['triage'];
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.services.service-ticket-form-modal', [
            'levels' => Level::cases(),
            'categories' => collect(config('helpdesk.categories'))->map(fn ($c) => $c['label']),
            'services' => $this->serviceLocked ? collect() : Service::orderBy('name')->get(['id', 'name', 'service_code']),
            'lockedService' => $this->serviceLocked && $this->serviceId ? Service::find($this->serviceId, ['id', 'name']) : null,
            'priority' => TicketTriage::priorityFor($this->impact_level ?: null, $this->urgency ?: null),
        ]);
    }
}
