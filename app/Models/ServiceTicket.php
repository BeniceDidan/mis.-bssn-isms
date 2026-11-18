<?php

namespace App\Models;

use App\Enums\Level;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTicket extends Model
{
    protected $fillable = [
        'service_id',
        'legacy_code',
        'reported_at',
        'requester_name',
        'issue',
        'category',
        'impact_level',
        'urgency',
        'priority',
        'related_risk_text',
        'resolution',
        'draft_reply',
        'triage',
        'status',
        'dynamic_data',
    ];

    protected $casts = [
        'reported_at' => 'date',
        'impact_level' => Level::class,
        'triage' => 'array',
        'dynamic_data' => 'array',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /** Kode tampilan: kode lama dari Excel bila ada, selain itu TKT-000123. */
    public function label(): string
    {
        return $this->legacy_code ?: sprintf('TKT-%06d', $this->id);
    }

    public function categoryLabel(): ?string
    {
        return $this->category ? config("helpdesk.categories.{$this->category}.label", $this->category) : null;
    }

    /** @return array{label: string, response: string, resolution: string, color: string}|null */
    public function priorityInfo(): ?array
    {
        return $this->priority ? config("helpdesk.priorities.{$this->priority}") : null;
    }

    public function isOpen(): bool
    {
        return ! in_array(strtolower((string) $this->status), ['selesai', 'ditutup'], true);
    }
}
