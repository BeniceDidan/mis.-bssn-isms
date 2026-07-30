<?php

namespace App\Services;

use App\Enums\Level;

/**
 * Hardcodes the exact 25-cell (kemungkinan x dampak) matrix from
 * "Manajemen_Pengetahuan_BSSN.xlsx" sheet "5. Matriks Risiko BSSN 5x5" —
 * not a linear/computed approximation, since the sheet's own bucketing
 * isn't a simple score-threshold split (e.g. score 8 reads "Tinggi" at
 * kemungkinan=4/dampak=2 but "Sedang" at kemungkinan=2/dampak=4 is not the
 * case here — the matrix is symmetric on score but this keeps it faithful
 * to the source either way). "Sangat Tinggi" collapses to Level::Kritis,
 * the same synonym handling NormalizesImportValues::normalizeLevelValue()
 * already applies elsewhere.
 */
class KnowledgeRiskLevelService
{
    /**
     * [kemungkinan][dampak] => Level
     *
     * @var array<int, array<int, Level>>
     */
    private const MATRIX = [
        1 => [1 => Level::Rendah, 2 => Level::Rendah, 3 => Level::Rendah, 4 => Level::Sedang, 5 => Level::Kritis],
        2 => [1 => Level::Rendah, 2 => Level::Rendah, 3 => Level::Sedang, 4 => Level::Tinggi, 5 => Level::Kritis],
        3 => [1 => Level::Rendah, 2 => Level::Sedang, 3 => Level::Tinggi, 4 => Level::Tinggi, 5 => Level::Kritis],
        4 => [1 => Level::Sedang, 2 => Level::Tinggi, 3 => Level::Tinggi, 4 => Level::Kritis, 5 => Level::Kritis],
        5 => [1 => Level::Kritis, 2 => Level::Kritis, 3 => Level::Kritis, 4 => Level::Kritis, 5 => Level::Kritis],
    ];

    public function derive(?int $kemungkinan, ?int $dampak): ?Level
    {
        if ($kemungkinan === null || $dampak === null) {
            return null;
        }

        if ($kemungkinan < 1 || $kemungkinan > 5 || $dampak < 1 || $dampak > 5) {
            return null;
        }

        return self::MATRIX[$kemungkinan][$dampak];
    }
}
