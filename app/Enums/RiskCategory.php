<?php

namespace App\Enums;

enum RiskCategory: string
{
    case Operasional = 'operasional';
    case Teknis = 'teknis';
    case SumberDayaManusia = 'sumber_daya_manusia';
    case PihakKetiga = 'pihak_ketiga';
    case KepatuhanLegal = 'kepatuhan_legal';
    case BencanaAlam = 'bencana_alam';

    public function label(): string
    {
        return match ($this) {
            self::Operasional => 'Operasional',
            self::Teknis => 'Teknis',
            self::SumberDayaManusia => 'Sumber Daya Manusia',
            self::PihakKetiga => 'Pihak Ketiga',
            self::KepatuhanLegal => 'Kepatuhan & Legal',
            self::BencanaAlam => 'Bencana Alam',
        };
    }
}
