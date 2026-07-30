<?php

namespace App\Models;

use App\Traits\HasFormattedCode;
use App\Traits\HasVerificationWorkflow;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KnowledgeExpert extends Model
{
    use SoftDeletes, HasFormattedCode, LogsActivity, HasVerificationWorkflow;

    protected $fillable = [
        'record_code',
        'legacy_code',
        'nama_pegawai',
        'jabatan_unit',
        'keahlian_spesifik',
        'sertifikasi_lisensi',
        'peran_km',
        'verification_status',
        'personnel_ref',
        'dynamic_data',
    ];

    protected $casts = [
        'dynamic_data' => 'array',
    ];

    public function getCodeColumn(): string
    {
        return 'record_code';
    }

    public static function codePrefix(): string
    {
        return 'PKP';
    }

    public static function verificationLabel(): string
    {
        return 'Manajemen Pengetahuan';
    }

    public function activities(): HasMany
    {
        return $this->hasMany(KnowledgeActivity::class, 'narasumber_id');
    }
}
