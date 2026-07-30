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
        'impact_level',
        'related_risk_text',
        'resolution',
        'status',
        'dynamic_data',
    ];

    protected $casts = [
        'reported_at' => 'date',
        'impact_level' => Level::class,
        'dynamic_data' => 'array',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
