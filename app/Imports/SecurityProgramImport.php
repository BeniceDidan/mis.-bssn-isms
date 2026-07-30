<?php

namespace App\Imports;

use App\Imports\Concerns\NormalizesImportValues;
use App\Models\SecurityProgram;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

/**
 * Reads "4. Manajemen Keamanan Informasi.xlsx" — "Sheet1" is a 2-level
 * outline, not flat data: a "2 / Manajemen" section row, a "C / Manajemen
 * Keamanan Informasi" sub-section row, then 5 real leaf rows numbered 1-5
 * in the "No" sub-column with the actual work-plan text in "Program
 * Kerja". Only rows where that sub-column is a plain integer 1-5 are real
 * records — the two heading rows above them are skipped.
 */
class SecurityProgramImport
{
    use NormalizesImportValues;

    private const SHEET_NAME = 'Sheet1';

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
        $headers = $this->resolveSingleRowHeaders($rows, 'Program Kerja');

        if ($headers === null) {
            $this->summary->addError(self::SHEET_NAME, 0, 'Baris header "Program Kerja" tidak ditemukan.');

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

        // Column B ("No" sub-index) has no header text of its own (blank
        // cell in row 1), so it never lands in $values above — read it
        // straight off the raw row by position instead. It's a plain
        // integer only on the 5 real leaf rows; the section/sub-section
        // heading rows above them have text there instead ("Manajemen", "C").
        $itemNumber = $this->normalizeCellValue($row[1] ?? null);

        if (blank($itemNumber) || ! ctype_digit((string) $itemNumber)) {
            return;
        }

        $programKerja = $values['Program Kerja'] ?? null;

        if (blank($programKerja)) {
            $this->summary->addError(self::SHEET_NAME, $excelRowNumber, "Baris dilewati (No {$itemNumber}): Program Kerja kosong.");

            return;
        }

        $static = [
            'program_kerja' => $programKerja,
            'kegiatan' => $values['Kegiatan'] ?? null,
            'pic' => $values['PIC'] ?? null,
        ];

        $dynamic = [];

        foreach ($values as $label => $value) {
            if (in_array($label, ['Program Kerja', 'Kegiatan', 'PIC'], true) || blank($value)) {
                continue;
            }

            $dynamic[$label] = $value;
        }

        $static['dynamic_data'] = $dynamic;

        $record = SecurityProgram::withTrashed()->where('legacy_code', (string) $itemNumber)->first();

        if ($record) {
            $record->update($static);
            $this->summary->incrementUpdated();
        } else {
            SecurityProgram::create([...$static, 'legacy_code' => (string) $itemNumber]);
            $this->summary->incrementCreated();
        }
    }
}
