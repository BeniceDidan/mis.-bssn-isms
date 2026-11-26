<?php

namespace App\Enums;

enum ChangeType: string
{
    case Positif = 'positif';
    case Negatif = 'negatif';

    public function label(): string
    {
        return match ($this) {
            // Kata dalam kurung supaya awam paham (masukan 26 Nov 2026: "Positif/Negatif" membingungkan).
            self::Positif => 'Positif (peluang)',
            self::Negatif => 'Negatif (ancaman)',
        };
    }
}
