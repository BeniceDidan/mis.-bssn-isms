<?php

namespace App\Models;

use App\Traits\HasFormattedCode;
use App\Traits\HasVerificationWorkflow;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class KnowledgeActivity extends Model
{
    use SoftDeletes, HasFormattedCode, LogsActivity, HasVerificationWorkflow;

    protected $fillable = [
        'record_code',
        'legacy_code',
        'tanggal_pelaksanaan',
        'nama_kegiatan',
        'materi_topik',
        'narasumber_name',
        'narasumber_id',
        'jumlah_peserta',
        'link_bukti',
        'verification_status',
        'personnel_ref',
        'dynamic_data',
    ];

    protected $casts = [
        'tanggal_pelaksanaan' => 'date',
        'jumlah_peserta' => 'integer',
        'dynamic_data' => 'array',
    ];

    public function getCodeColumn(): string
    {
        return 'record_code';
    }

    public static function codePrefix(): string
    {
        return 'KLB';
    }

    public static function verificationLabel(): string
    {
        return 'Manajemen Pengetahuan';
    }

    public function narasumber(): BelongsTo
    {
        return $this->belongsTo(KnowledgeExpert::class, 'narasumber_id');
    }
}
