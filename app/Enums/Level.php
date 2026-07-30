<?php

namespace App\Enums;

/**
 * Shared Rendah/Sedang/Tinggi/Kritis scale, reused for Asset::criticality_level
 * and Risk::likelihood / impact / risk_level so the whole ISMS speaks one
 * severity vocabulary instead of a different enum per module.
 */
enum Level: string
{
    case Rendah = 'rendah';
    case Sedang = 'sedang';
    case Tinggi = 'tinggi';
    case Kritis = 'kritis';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Rendah => 'green',
            self::Sedang => 'yellow',
            self::Tinggi => 'orange',
            self::Kritis => 'red',
        };
    }

    /**
     * Literal hex (not a Tailwind class) for the colored left-edge accent
     * strip on table rows — an inline style, not a class, since Tailwind's
     * JIT scanner can't discover a runtime-built `border-l-{color}` string.
     */
    public function accentHex(): string
    {
        return match ($this) {
            self::Rendah => '#22c55e',
            self::Sedang => '#eab308',
            self::Tinggi => '#f97316',
            self::Kritis => '#ef4444',
        };
    }

    /**
     * Whether this level is severe enough to warrant the bold, solid-fill
     * badge treatment instead of the usual soft pastel one — reserving
     * "loud" color for what actually needs attention (iOS-style).
     */
    public function isSevere(): bool
    {
        return $this === self::Tinggi || $this === self::Kritis;
    }
}
