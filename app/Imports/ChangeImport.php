<?php

namespace App\Imports;

use App\Enums\ChangeType;
use App\Imports\Concerns\NormalizesImportValues;
use App\Models\Asset;
use App\Models\Change;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

/**
 * Reads "Manajemen Perubahan...xlsx" — a single sheet named "Log Manajemen
 * Perubahan" with a 3-row header (a top group label, a mid label, and a
 * bottom label only used for the "Nilai Resiko Bawaan" sub-scores). Same
 * anti-loss philosophy as AssetCategorySheetImport: headers are discovered
 * at runtime rather than hardcoded, and any column without a known static
 * mapping still lands in dynamic_data under its own label.
 */
class ChangeImport
{
    use NormalizesImportValues;

    private const SHEET_NAME = 'Log Manajemen Perubahan';

    private const STATIC_COLUMN_ALIASES = [
        'usulan modifikasi sistem' => 'title',
        'jenis perubahan' => 'change_type',
        'kategori perubahan' => 'category',
        'kategori perubahan (skala besar/kecil)' => 'category',
        'prioritas eksekusi' => 'priority',
        'level risiko inherent' => 'inherent_risk_level',
        'keputusan penanganan perubahan' => 'decision',
        'status akhir kelayakan' => 'status',
        'penanggung jawab' => 'pic',
        'target/jadwal implementasi' => 'target_date',
    ];

    private ImportSummary $summary;

    public function __construct()
    {
        $this->summary = new ImportSummary();
    }

    public function run(string $path): void
    {
        $reader = new Xlsx();
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        $sheet = $spreadsheet->getSheetByName(self::SHEET_NAME);

        if ($sheet === null) {
            $this->summary->addError(self::SHEET_NAME, 0, 'Sheet "' . self::SHEET_NAME . '" tidak ditemukan di file ini.');

            return;
        }

        $rows = $sheet->toArray(null, true, true, false);
        $headers = $this->resolveThreeRowHeaders($rows, 'RFC No')
            ?? $this->resolveThreeRowHeaders($rows, 'Change No');

        if ($headers === null) {
            $this->summary->addError(self::SHEET_NAME, 0, 'Baris header "RFC No"/"Change No" tidak ditemukan.');

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

        $spreadsheet->disconnectWorksheets();
    }

    public function summary(): ImportSummary
    {
        return $this->summary;
    }

    private function importRow(array $row, array $columnLabels, int $excelRowNumber): void
    {
        $values = [];

        foreach ($columnLabels as $col => $label) {
            if (blank($label)) {
                continue;
            }

            $value = $this->normalizeCellValue($row[$col] ?? null);

            if (blank($value) && array_key_exists($label, $values)) {
                continue;
            }

            $values[$label] = $value;
        }

        $legacyCode = $values['RFC No'] ?? $values['Change No'] ?? null;

        if (blank($legacyCode)) {
            return; // blank spacer row or end of the data block
        }

        $static = [];
        $dynamic = [];

        foreach ($values as $label => $value) {
            if ($label === 'RFC No' || $label === 'Change No' || blank($value)) {
                continue;
            }

            if (Str::lower($label) === 'aset terkait') {
                $static['asset_id'] = $this->resolveAssetId((string) $value);
                $dynamic['Aset Terkait (dari file)'] = $value;

                continue;
            }

            $key = self::STATIC_COLUMN_ALIASES[Str::lower($label)] ?? null;

            match ($key) {
                'change_type' => $static[$key] = $this->normalizeEnumValue(ChangeType::class, (string) $value),
                'inherent_risk_level' => $static[$key] = $this->normalizeLevelValue((string) $value),
                'target_date' => $static[$key] = $this->parseDate($value),
                null => $dynamic[$label] = $value,
                default => $static[$key] = $value,
            };
        }

        if (blank($static['title'] ?? null)) {
            $this->summary->addError(self::SHEET_NAME, $excelRowNumber, "Baris dilewati (Kode {$legacyCode}): Usulan Modifikasi Sistem kosong.");

            return;
        }

        $static['dynamic_data'] = $dynamic;

        // Upsert Logic (rule 3): match on the source register's own Change
        // No so re-importing an updated file updates rows in place.
        $change = Change::withTrashed()->where('legacy_code', $legacyCode)->first();

        if ($change) {
            $change->update($static);
            $this->summary->incrementUpdated();
        } else {
            Change::create([...$static, 'legacy_code' => $legacyCode]);
            $this->summary->incrementCreated();
        }
    }

    /**
     * "Aset Terkait" cells are formatted "LEGACY_CODE: Nama Aset" (e.g.
     * "PL-002: SIAKAD Polinema"). Matching on legacy_code alone isn't
     * enough proof it's the same real-world asset — the code convention
     * (PL-002, DI-002, ...) is generic enough that a completely unrelated
     * asset register (e.g. this app's own example/placeholder inventory)
     * can reuse the same codes for something else entirely. Requiring the
     * name to share at least one real word with the matched asset's name
     * guards against silently linking to the wrong asset; on mismatch this
     * returns null (unlinked) rather than guessing, and the raw text is
     * still preserved in dynamic_data either way.
     */
    private function resolveAssetId(string $value): ?int
    {
        [$code, $name] = array_pad(explode(':', $value, 2), 2, '');
        $code = trim($code);
        $name = trim($name);

        if (blank($code)) {
            return null;
        }

        $asset = Asset::withTrashed()->where('legacy_code', $code)->first();

        if (! $asset) {
            return null;
        }

        if (filled($name) && ! $this->namesShareAWord($name, $asset->name)) {
            return null;
        }

        return $asset->id;
    }

    private function namesShareAWord(string $a, string $b): bool
    {
        $significantWords = fn (string $text) => array_filter(
            preg_split('/\W+/', Str::lower($text)) ?: [],
            fn ($word) => mb_strlen($word) >= 4
        );

        return count(array_intersect($significantWords($a), $significantWords($b))) > 0;
    }
}
