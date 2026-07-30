<?php

namespace App\Enums;

enum KnowledgeType: string
{
    case Tacit = 'tacit';
    case Explicit = 'explicit';

    public function label(): string
    {
        return match ($this) {
            self::Tacit => 'Tacit (Pengalaman/Keahlian)',
            self::Explicit => 'Explicit (Terdokumentasi)',
        };
    }
}
