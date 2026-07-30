<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceEvaluation extends Model
{
    protected $fillable = [
        'service_id',
        'uptime_actual',
        'sla_target',
        'achievement_status',
        'incident_count',
        'mttr',
        'recommendation',
        'dynamic_data',
    ];

    protected $casts = [
        'incident_count' => 'integer',
        'dynamic_data' => 'array',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
