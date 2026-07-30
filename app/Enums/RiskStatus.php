<?php

namespace App\Enums;

enum RiskStatus: string
{
    case Open = 'open';
    case InTreatment = 'in_treatment';
    case Monitoring = 'monitoring';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Terbuka',
            self::InTreatment => 'Dalam Penanganan',
            self::Monitoring => 'Pemantauan',
            self::Closed => 'Selesai',
        };
    }
}
