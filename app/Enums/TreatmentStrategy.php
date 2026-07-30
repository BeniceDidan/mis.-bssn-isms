<?php

namespace App\Enums;

enum TreatmentStrategy: string
{
    case Mitigate = 'mitigate';
    case Accept = 'accept';
    case Transfer = 'transfer';
    case Avoid = 'avoid';

    public function label(): string
    {
        return match ($this) {
            self::Mitigate => 'Mitigasi',
            self::Accept => 'Diterima',
            self::Transfer => 'Dialihkan',
            self::Avoid => 'Dihindari',
        };
    }
}
