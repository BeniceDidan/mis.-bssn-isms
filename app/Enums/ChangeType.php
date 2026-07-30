<?php

namespace App\Enums;

enum ChangeType: string
{
    case Positif = 'positif';
    case Negatif = 'negatif';

    public function label(): string
    {
        return match ($this) {
            self::Positif => 'Positif',
            self::Negatif => 'Negatif',
        };
    }
}
