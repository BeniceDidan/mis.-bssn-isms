<?php

namespace App\Imports;

use App\Imports\Concerns\NormalizesImportValues;
use App\Models\HrRisk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

/**
 * Reads "Manajemen SDM...xlsx" — sheet "Register Risiko Otomatis". Unlike
 * Change/DataInformation this has a plain single-row header (no merged
 * group/sub rows), and its "Aset" column names a person or third party,
 * not an IT asset, so there's no asset_id resolution here.
 */
class HrRiskImport
{
    use NormalizesImportValues;

    private const SHEET_NAME = 'Register Risiko Otomatis';

    private const STATIC_COLUMN_ALIASES = [
        'aset' => 'subject',
        'ancaman' => 'title',
        'kategori risiko spbe' => 'category',
        'level risiko' => 'inherent_risk_level',
        'penanggung jawab' => 'pic',
        'target waktu' => 'target_date',
        'status risiko sisa' => 'status',
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
        $headers = $this->resolveSingleRowHeaders($rows, 'Risk No');

        if ($headers === null) {
            $this->summary->addError(self::SHEET_NAME, 0, 'Baris header "Risk No" tidak ditemukan.');

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

            $values[$label] = $this->normalizeCellValue($row[$col] ?? null);
        }

        $legacyCode = $values['Risk No'] ?? null;

        if (blank($legacyCode)) {
            return;
        }

        $static = [];
        $dynamic = [];

        foreach ($values as $label => $value) {
            if ($label === 'Risk No' || blank($value)) {
                continue;
            }

            $key = self::STATIC_COLUMN_ALIASES[Str::lower($label)] ?? null;

            match ($key) {
                'inherent_risk_level' => $static[$key] = $this->normalizeLevelValue((string) $value),
                'target_date' => $static[$key] = $this->parseDate($value),
                null => $dynamic[$label] = $value,
                default => $static[$key] = $value,
            };
        }

        if (blank($static['subject'] ?? null) || blank($static['title'] ?? null)) {
            $this->summary->addError(self::SHEET_NAME, $excelRowNumber, "Baris dilewati (Kode {$legacyCode}): Aset atau Ancaman kosong.");

            return;
        }

        $static['dynamic_data'] = $dynamic;

        $record = HrRisk::withTrashed()->where('legacy_code', $legacyCode)->first();

        if ($record) {
            $record->update($static);
            $this->summary->incrementUpdated();
        } else {
            HrRisk::create([...$static, 'legacy_code' => $legacyCode]);
            $this->summary->incrementCreated();
        }
    }
}
