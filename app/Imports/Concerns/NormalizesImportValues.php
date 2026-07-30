<?php

namespace App\Imports\Concerns;

use App\Enums\Level;
use Illuminate\Support\Str;

/**
 * Shared value-normalization helpers used by every SPBE-register importer
 * (Change, DataInformation, HrRisk, Knowledge). Kept as a trait rather than
 * a base class since some importers have a 3-row merged header and others
 * a plain single-row header, so the resolveHeaders() shape differs too
 * much to share a common parent.
 */
trait NormalizesImportValues
{
    private function formatNumericValue(int|float $value): string
    {
        if (is_float($value) && fmod($value, 1.0) === 0.0) {
            return sprintf('%.0f', $value);
        }

        return (string) $value;
    }

    private function looksLikeScientificNotation(string $value): bool
    {
        return (bool) preg_match('/^-?\d+(\.\d+)?E[+-]?\d+$/i', trim($value));
    }

    private function normalizeLabel(string $label): string
    {
        return trim(preg_replace('/\s+/', ' ', $label));
    }

    private function normalizeCellValue(mixed $raw): mixed
    {
        return match (true) {
            is_string($raw) && $this->looksLikeScientificNotation($raw) => $this->formatNumericValue((float) $raw),
            is_string($raw) => trim($raw),
            is_int($raw), is_float($raw) => $this->formatNumericValue($raw),
            default => $raw,
        };
    }

    /**
     * The source registers write risk levels inconsistently — "Tinggi",
     * "Tinggi (12)" (score appended), "Sangat Tinggi" (a fifth tier that
     * doesn't exist in our 4-tier Level enum) all show up across the
     * different module files. Strips a trailing "(...)" score, maps the
     * "Sangat Tinggi" synonym to Kritis, then matches against Level's own
     * values — returns null (not a guess) if nothing matches.
     */
    private function normalizeLevelValue(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $cleaned = trim(preg_replace('/\s*\([^)]*\)\s*$/', '', $value));
        $normalized = Str::lower($cleaned);

        if ($normalized === 'sangat tinggi') {
            return Level::Kritis->value;
        }

        return collect(Level::cases())->first(fn (Level $level) => $level->value === $normalized)?->value;
    }

    /**
     * @param class-string $enumClass
     */
    private function normalizeEnumValue(string $enumClass, string $value): ?string
    {
        $normalized = Str::lower(trim($value));

        return collect($enumClass::cases())->first(fn ($case) => $case->value === $normalized)?->value;
    }

    private function parseDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Resolves a 3-row merged header (group label, mid label, sub label —
     * used only for score sub-columns like Dampak/Kemungkinan/IR/Level)
     * into one label per column, keyed by column index. Shared by
     * ChangeImport and DataInformationImport, which use the exact same
     * SPBE register template shape, differing only in the anchor cell that
     * marks the header row (e.g. "Change No" vs. "Risk No").
     *
     * @return array{0: int, 1: array<int, string|null>}|null
     */
    private function resolveThreeRowHeaders(array $rows, string $anchorLabel): ?array
    {
        foreach ($rows as $index => $row) {
            $normalized = array_map(fn ($cell) => is_string($cell) ? trim($cell) : $cell, $row);

            if (! in_array($anchorLabel, $normalized, true)) {
                continue;
            }

            $midRow = $rows[$index + 1] ?? [];
            $subRow = $rows[$index + 2] ?? [];
            $labels = [];
            $lastGroupLabel = null;

            foreach ($normalized as $col => $label) {
                if (filled($label)) {
                    $lastGroupLabel = $this->normalizeLabel((string) $label);
                }

                $mid = isset($midRow[$col]) ? $this->normalizeLabel((string) $midRow[$col]) : '';
                $sub = isset($subRow[$col]) ? $this->normalizeLabel((string) $subRow[$col]) : '';

                $labels[$col] = filled($sub) ? $sub : (filled($mid) ? $mid : $lastGroupLabel);
            }

            // Three header rows were consumed (group + mid + sub); data
            // starts on the row after all three.
            return [$index + 2, $labels];
        }

        return null;
    }

    /**
     * Resolves a plain single-row header (no merged group/sub rows) into
     * one label per column. Shared by HrRiskImport and KnowledgeImport.
     *
     * @return array{0: int, 1: array<int, string|null>}|null
     */
    private function resolveSingleRowHeaders(array $rows, string $anchorLabel): ?array
    {
        foreach ($rows as $index => $row) {
            $normalized = array_map(fn ($cell) => is_string($cell) ? $this->normalizeLabel($cell) : $cell, $row);

            if (! in_array($anchorLabel, $normalized, true)) {
                continue;
            }

            return [$index, $normalized];
        }

        return null;
    }
}
