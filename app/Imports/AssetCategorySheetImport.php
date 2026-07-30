<?php

namespace App\Imports;

use App\Enums\AssetCategory;
use App\Enums\Level;
use App\Models\Asset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Reads one category sheet from "Daftar Inventaris Aset.xlsx". The source
 * workbook uses a 2-row merged header (a group label row, then a sub-label
 * row for grouped fields like "Identifikasi Keberadaan Aset") rather than a
 * single heading row, so WithHeadingRow doesn't apply — headers are instead
 * discovered at runtime by scanning for the row containing "Kode Aset".
 *
 * This is deliberate: hardcoding column letters would be fragile against
 * next year's Excel layout, which is exactly the failure mode dynamic_data
 * exists to prevent. Any column whose label isn't in the known static-field
 * map still gets imported, verbatim, under its own label in dynamic_data —
 * nothing from the source file is ever silently dropped.
 *
 * KRITIKALITAS ASET is a live cross-sheet formula in the template (looked
 * up against 'Definisi Range Aset'), not a stored value. This class takes a
 * plain array already produced by PhpSpreadsheet's own Worksheet::toArray()
 * with formula calculation on — Maatwebsite's WithMultipleSheets + ToArray
 * pipeline was tried first but doesn't keep the full workbook in scope
 * while reading a sheet, so cross-sheet formulas silently resolved to ""
 * instead of "Rendah"/"Sedang"/"Tinggi". Loading the workbook directly with
 * PhpSpreadsheet (see AssetsImport) keeps every sheet available, so the
 * lookup formula resolves correctly.
 */
class AssetCategorySheetImport
{
    private const STATIC_COLUMN_ALIASES = [
        'sub klasifikasi aset' => 'sub_classification',
        'nama aset' => 'name',
        'nama personil' => 'name',
        'pemilik aset' => 'owner',
        'pemilik aset (opd)' => 'owner',
        'lokasi keberadaan aset' => 'location',
        'status aset' => 'status',
        'status' => 'status',
        'kondisi aset' => 'status',
        'kritikalitas aset' => 'criticality_level',
        'tahun penyusunan/pengesahan' => 'reference_year',
        'tahun rilis' => 'reference_year',
        'tahun pengadaan' => 'reference_year',
    ];

    /**
     * Data & Informasi's KRITIKALITAS ASET is a formula using Excel's
     * LET()/IFS() functions, which PhpSpreadsheet's calculation engine
     * doesn't support (resolves to a "#NAME?" error instead of a value).
     * Rather than leave criticality blank for an entire category, this
     * replicates the same scoring rule from the 'Definisi Range Aset'
     * reference sheet directly: each of Kerahasiaan/Integritas/Ketersediaan
     * scores 1-3 by matching the text value against this table, the three
     * scores sum to 3-9, then bucket into Rendah (<=3) / Sedang (<=6) /
     * Tinggi (<=9) — exactly what the source formula does.
     */
    private const CIA_SCORE_TABLE = [
        'Kerahasiaan' => [
            'Informasi Terbuka / Publik' => 1,
            'Informasi Terbatas' => 2,
            'Informasi Strategis / Rahasia' => 3,
        ],
        'Integritas' => [
            'Data Penunjang Umum' => 1,
            'Data Proses Administrasi' => 2,
            'Data Vital Pengambilan Keputusan' => 3,
        ],
        'Ketersediaan' => [
            'Akses Fleksibel / Non-Kritis' => 1,
            'Akses Rutin Terjadwal' => 2,
            'Akses Seketika (Real-time)' => 3,
        ],
    ];

    private ImportSummary $summary;

    public function __construct(private readonly AssetCategory $category)
    {
        $this->summary = new ImportSummary();
    }

    public function import(array $rows): void
    {
        $headers = $this->resolveHeaders($rows);

        if ($headers === null) {
            $this->summary->addError($this->category->label(), 0, 'Baris header "Kode Aset" tidak ditemukan di sheet ini — sheet dilewati.');

            return;
        }

        [$headerRowIndex, $columnLabels] = $headers;

        DB::transaction(function () use ($rows, $headerRowIndex, $columnLabels) {
            foreach ($rows as $index => $row) {
                if ($index <= $headerRowIndex) {
                    continue;
                }

                $this->importRow($row, $columnLabels, $index + 1);
            }
        });
    }

    public function summary(): ImportSummary
    {
        return $this->summary;
    }

    /**
     * @return array{0: int, 1: array<int, string|null>}|null
     */
    private function resolveHeaders(array $rows): ?array
    {
        foreach ($rows as $index => $row) {
            $normalized = array_map(fn ($cell) => is_string($cell) ? trim($cell) : $cell, $row);

            if (in_array('Kode Aset', $normalized, true)) {
                $subRow = $rows[$index + 1] ?? [];
                $labels = [];
                $lastGroupLabel = null;

                foreach ($normalized as $col => $label) {
                    $sub = isset($subRow[$col]) ? $this->normalizeLabel((string) $subRow[$col]) : '';

                    if (filled($label)) {
                        $lastGroupLabel = $this->normalizeLabel((string) $label);
                    }

                    $labels[$col] = filled($sub) ? $sub : $lastGroupLabel;
                }

                // The sub-label row is also a header row and must be skipped
                // by the caller, so report index+1 as the last header row.
                return [$index + 1, $labels];
            }
        }

        return null;
    }

    private function importRow(array $row, array $columnLabels, int $excelRowNumber): void
    {
        $values = [];

        foreach ($columnLabels as $col => $label) {
            if (blank($label)) {
                continue;
            }

            $raw = $row[$col] ?? null;
            $value = match (true) {
                // toArray()'s formatData=true (needed elsewhere for dates
                // etc.) already renders large "General"-formatted numeric
                // cells as a scientific-notation *string* before this code
                // ever sees a float, so the numeric branch below alone
                // isn't enough — this catches that pre-formatted case too.
                is_string($raw) && $this->looksLikeScientificNotation($raw) => $this->formatNumericValue((float) $raw),
                is_string($raw) => trim($raw),
                is_int($raw), is_float($raw) => $this->formatNumericValue($raw),
                default => $raw,
            };

            // The sheet's used-range sometimes extends one phantom column
            // past the real headers; that trailing column inherits the same
            // carried-forward group label (see resolveHeaders) and, being
            // blank, would otherwise silently clobber the real value read
            // from the earlier column sharing that label.
            if (blank($value) && array_key_exists($label, $values)) {
                continue;
            }

            $values[$label] = $value;
        }

        $legacyCode = $values['Kode Aset'] ?? null;

        if (blank($legacyCode)) {
            return; // blank spacer row or end of the data block
        }

        $static = ['category' => $this->category->value];
        $dynamic = [];

        foreach ($values as $label => $value) {
            if ($label === 'Kode Aset' || blank($value)) {
                continue;
            }

            $key = self::STATIC_COLUMN_ALIASES[Str::lower($label)] ?? null;

            match ($key) {
                'criticality_level' => $static[$key] = $this->normalizeLevel((string) $value),
                'reference_year' => $static[$key] = (int) preg_replace('/\D/', '', (string) $value) ?: null,
                null => $dynamic[$label] = $value,
                default => $static[$key] = $value,
            };
        }

        if (blank($static['criticality_level'] ?? null)) {
            $static['criticality_level'] = $this->computeCriticalityFromCia($values);
        }

        // The source template pre-fills Kode Aset (sequential formula) and
        // the CIA/criticality dropdowns with a default option for ~500
        // placeholder rows per sheet, regardless of whether the row has
        // actually been used — only Nama Aset is genuinely blank until
        // someone fills the row in. Treat a blank name as "unused
        // template row" and skip it silently; only flag a row as an error
        // once it's clear a real entry was started (name present) but is
        // still missing another required field.
        if (blank($static['name'] ?? null)) {
            return;
        }

        if (blank($static['sub_classification'] ?? null)) {
            $this->summary->addError($this->category->label(), $excelRowNumber, "Baris dilewati (Kode {$legacyCode}): Nama Aset terisi \"{$static['name']}\" tapi Sub Klasifikasi Aset kosong.");

            return;
        }

        $static['dynamic_data'] = $dynamic;

        // Upsert Logic (rule 3): match on the source spreadsheet's own code
        // so re-importing an updated Excel updates existing rows in place
        // instead of duplicating them.
        $asset = Asset::withTrashed()->where('legacy_code', $legacyCode)->first();

        if ($asset) {
            $asset->update($static);
            $this->summary->incrementUpdated();
        } else {
            Asset::create([...$static, 'legacy_code' => $legacyCode]);
            $this->summary->incrementCreated();
        }
    }

    /**
     * @param array<string, mixed> $values
     */
    private function computeCriticalityFromCia(array $values): ?string
    {
        $total = 0;

        foreach (self::CIA_SCORE_TABLE as $label => $scoreByAnswer) {
            $answer = $values[$label] ?? null;

            if (blank($answer)) {
                return null;
            }

            $normalizedAnswer = $this->normalizeLabel((string) $answer);
            $score = $scoreByAnswer[$normalizedAnswer] ?? null;

            if ($score === null) {
                return null;
            }

            $total += $score;
        }

        return match (true) {
            $total <= 3 => Level::Rendah->value,
            $total <= 6 => Level::Sedang->value,
            default => Level::Tinggi->value,
        };
    }

    /**
     * PHP casts large floats (e.g. an 18-digit NIP typed into a
     * General-formatted Excel cell instead of a Text one) to scientific
     * notation when stringified — "1.9880515201503E+17" instead of a plain
     * integer. sprintf('%.0f', ...) renders the same underlying float as a
     * full digit string instead. Note this cannot recover precision Excel
     * itself already lost by storing the value as a float rather than
     * text — float64 only reliably holds ~15-17 significant digits, so an
     * 18-digit ID stored this way may have inaccurate trailing digits in
     * the source file itself, before this importer ever sees it.
     */
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

    private function normalizeLevel(string $value): ?string
    {
        $normalized = Str::lower(trim($value));

        return collect(Level::cases())->first(fn (Level $level) => $level->value === $normalized)?->value;
    }

    /**
     * Collapses runs of internal whitespace (e.g. the source file's
     * "Sub  Klasifikasi Aset" with a double space) so header labels compare
     * equal regardless of inconsistent spacing between sheets.
     */
    private function normalizeLabel(string $label): string
    {
        return trim(preg_replace('/\s+/', ' ', $label));
    }
}
