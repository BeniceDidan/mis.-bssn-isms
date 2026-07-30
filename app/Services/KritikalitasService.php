<?php

namespace App\Services;

use App\Enums\Level;

/**
 * Computes an asset's criticality from its CIA-triad scores, per the
 * Panduan Perancangan Aplikasi Web Inventarisasi dan Manajemen Risiko Aset
 * TIK (Bab 9.1.3 & Bab 11): Kerahasiaan (confidentiality), Integritas
 * (integrity), and Ketersediaan (availability) — each 1-5 — feed a
 * "Kritikalitas Service" that produces the asset's overall criticality.
 *
 * The source document names the service but doesn't give an exact
 * formula, so this uses the standard ISO 27005-style "weakest link"
 * convention: the HIGHEST of the three scores drives the result, since a
 * single very sensitive attribute (e.g. availability=5 on an otherwise
 * low-sensitivity system) is enough to make the whole asset critical —
 * not diluted by averaging against the other two.
 */
class KritikalitasService
{
    public function compute(?int $confidentiality, ?int $integrity, ?int $availability): ?string
    {
        if ($confidentiality === null || $integrity === null || $availability === null) {
            return null;
        }

        $highest = max($confidentiality, $integrity, $availability);

        return match (true) {
            $highest >= 5 => Level::Kritis->value,
            $highest >= 4 => Level::Tinggi->value,
            $highest >= 3 => Level::Sedang->value,
            default => Level::Rendah->value,
        };
    }
}
