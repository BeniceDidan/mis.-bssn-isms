<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiLog extends Model
{
    protected $fillable = [
        'user_id',
        'feature',
        'engine',
        'subject_type',
        'subject_id',
        'input_summary',
        'output',
        'duration_ms',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Satu baris log per keluaran AI. $startedAt = microtime(true) saat
     * fitur mulai bekerja.
     */
    public static function record(string $feature, string $engine, ?Model $subject, string $input, string $output, float $startedAt): self
    {
        return self::create([
            'user_id' => auth()->id(),
            'feature' => $feature,
            'engine' => $engine,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'input_summary' => mb_substr($input, 0, 2000),
            'output' => mb_substr($output, 0, 8000),
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);
    }
}
