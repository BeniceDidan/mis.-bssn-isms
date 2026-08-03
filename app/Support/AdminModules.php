<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\Change;
use App\Models\DataInformation;
use App\Models\HrRisk;
use App\Models\KnowledgeActivity;
use App\Models\KnowledgeAsset;
use App\Models\KnowledgeExpert;
use App\Models\KnowledgeRisk;
use App\Models\Risk;
use App\Models\SecurityProgram;
use App\Models\Service;

/**
 * The 8 "alur manajemen" as admin-approval scopes — a per-module admin's
 * verification power (User::admin_module) is limited to exactly one key
 * here. Pengetahuan is one scope even though it's 4 separate Eloquent
 * models under the hood; every other module is a 1:1 mapping.
 */
class AdminModules
{
    /** @var array<string, string> module key => display label */
    public const LABELS = [
        'sdm' => 'Manajemen SDM',
        'pengetahuan' => 'Manajemen Pengetahuan',
        'aset' => 'Manajemen Aset',
        'keamanan' => 'Manajemen Keamanan Informasi',
        'risiko' => 'Manajemen Risiko',
        'perubahan' => 'Manajemen Perubahan',
        'layanan' => 'Manajemen Layanan',
        'data_informasi' => 'Manajemen Data & Informasi',
    ];

    /** @var array<string, array<int, class-string>> module key => Eloquent model classes it governs */
    public const MODELS = [
        'sdm' => [HrRisk::class],
        'pengetahuan' => [KnowledgeAsset::class, KnowledgeExpert::class, KnowledgeActivity::class, KnowledgeRisk::class],
        'aset' => [Asset::class],
        'keamanan' => [SecurityProgram::class],
        'risiko' => [Risk::class],
        'perubahan' => [Change::class],
        'layanan' => [Service::class],
        'data_informasi' => [DataInformation::class],
    ];

    public static function label(?string $key): ?string
    {
        return $key ? (self::LABELS[$key] ?? $key) : null;
    }

    /** @return array<int, class-string> */
    public static function modelsFor(?string $key): array
    {
        return self::MODELS[$key] ?? [];
    }

    public static function moduleKeyForModel(string $modelClass): ?string
    {
        foreach (self::MODELS as $key => $models) {
            if (in_array($modelClass, $models, true)) {
                return $key;
            }
        }

        return null;
    }
}
