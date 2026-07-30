<?php

namespace App\Enums;

enum AssetCategory: string
{
    case DataInformasi = 'data_informasi';
    case PerangkatLunak = 'perangkat_lunak';
    case PerangkatKeras = 'perangkat_keras';
    case SaranaPendukung = 'sarana_pendukung';
    case SdmPihakKetiga = 'sdm_pihak_ketiga';

    public function label(): string
    {
        return match ($this) {
            self::DataInformasi => 'Data & Informasi',
            self::PerangkatLunak => 'Perangkat Lunak',
            self::PerangkatKeras => 'Perangkat Keras',
            self::SaranaPendukung => 'Sarana Pendukung',
            self::SdmPihakKetiga => 'SDM & Pihak Ketiga',
        };
    }
}
