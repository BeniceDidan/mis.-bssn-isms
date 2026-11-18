<?php

namespace App\Models;

use App\Support\AdminModules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu kali impor Excel (lihat App\Livewire\Concerns\RecordsImports). */
class ImportLog extends Model
{
    protected $fillable = [
        'user_id',
        'module_key',
        'file_name',
        'created_count',
        'updated_count',
        'error_count',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'created_count' => 'integer',
        'updated_count' => 'integer',
        'error_count' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moduleLabel(): string
    {
        return AdminModules::label($this->module_key) ?? $this->module_key;
    }
}
