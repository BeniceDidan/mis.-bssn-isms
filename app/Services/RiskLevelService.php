<?php

namespace App\Services;

use App\Enums\Level;

/**
 * Derives a Risk's level from likelihood x impact — extracted from what
 * was previously RiskFormModal::deriveRiskLevel() so both the form and the
 * Asset::saved() ripple hook (Asset criticality changes -> linked Risks
 * recompute) can share one deterministic, server-side source of truth.
 *
 * Per the Kediri guide Bab 11 ("kritikalitas aset digunakan sebagai
 * parameter dalam Analisis Risiko"), a linked asset's criticality is now a
 * real input here too: a risk sitting on a Tinggi/Kritis asset gets
 * escalated one tier, capped at Kritis — a risk on a critical asset is
 * itself more critical, even if likelihood/impact alone wouldn't say so.
 *
 * $sdmSeverity extends the same idea to the "Kode Personil" link: if any
 * Manajemen SDM record sharing this risk's personnel_ref is itself
 * Tinggi/Kritis, that's a second independent escalation input (a risk tied
 * to a high-risk person/pihak ketiga is itself more critical) — stacks with
 * the asset escalation rather than replacing it, capped at Kritis either way.
 */
class RiskLevelService
{
    private const SCALE = ['rendah' => 1, 'sedang' => 2, 'tinggi' => 3, 'kritis' => 4];

    public function derive(?string $likelihood, ?string $impact, ?string $assetCriticality = null, ?string $sdmSeverity = null): ?string
    {
        if (! $likelihood || ! $impact) {
            return null;
        }

        $score = (self::SCALE[$likelihood] ?? 1) * (self::SCALE[$impact] ?? 1);

        $level = match (true) {
            $score >= 9 => Level::Kritis,
            $score >= 6 => Level::Tinggi,
            $score >= 3 => Level::Sedang,
            default => Level::Rendah,
        };

        if (in_array($assetCriticality, [Level::Tinggi->value, Level::Kritis->value], true)) {
            $level = $this->escalate($level);
        }

        if (in_array($sdmSeverity, [Level::Tinggi->value, Level::Kritis->value], true)) {
            $level = $this->escalate($level);
        }

        return $level->value;
    }

    private function escalate(Level $level): Level
    {
        return match ($level) {
            Level::Rendah => Level::Sedang,
            Level::Sedang => Level::Tinggi,
            Level::Tinggi, Level::Kritis => Level::Kritis,
        };
    }
}
