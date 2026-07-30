<?php

namespace App\Imports;

use App\Enums\ChangeType;
use App\Imports\Concerns\NormalizesImportValues;
use App\Models\Asset;
use App\Models\DataInformation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

/**
 * Reads "Manajemen Data dan Informasi...xlsx" — sheet "Log Manajemen Data &
 * Informasi", the exact same 3-row-header SPBE risk-register template as
 * ChangeImport, just with different column wording. See ChangeImport for
 * the shared anti-loss/header-discovery rationale.
 */
class DataInformationImport
{
    use NormalizesImportValues;

    private const SHEET_NAME = 'Log Manajemen Data & Informasi';

    private const STATIC_COLUMN_ALIASES = [
        'ancaman / kejadian gangguan data' => 'title',
        'jenis risiko' => 'risk_type',
        'kategori aset data' => 'category',
        'prioritas penanganan' => 'priority',
        'level risiko inherent' => 'inherent_risk_level',
        'keputusan penanganan risiko' => 'decision',
        'status kelayakan layanan' => 'status',
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
        $headers = $this->resolveThreeRowHeaders($rows, 'Risk No');

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

            $value = $this->normalizeCellValue($row[$col] ?? null);

            if (blank($value) && array_key_exists($label, $values)) {
                continue;
            }

            $values[$label] = $value;
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

            if (Str::lower($label) === 'aset terkait') {
                $static['asset_id'] = $this->resolveAssetId((string) $value);
                $dynamic['Aset Terkait (dari file)'] = $value;

                continue;
            }

            $key = self::STATIC_COLUMN_ALIASES[Str::lower($label)] ?? null;

            match ($key) {
                'risk_type' => $static[$key] = $this->normalizeEnumValue(ChangeType::class, (string) $value),
                'inherent_risk_level' => $static[$key] = $this->normalizeLevelValue((string) $value),
                'target_date' => $static[$key] = $this->parseDate($value),
                null => $dynamic[$label] = $value,
                default => $static[$key] = $value,
            };
        }

        if (blank($static['title'] ?? null)) {
            $this->summary->addError(self::SHEET_NAME, $excelRowNumber, "Baris dilewati (Kode {$legacyCode}): kolom ancaman/kejadian kosong.");

            return;
        }

        $static['dynamic_data'] = $dynamic;

        $record = DataInformation::withTrashed()->where('legacy_code', $legacyCode)->first();

        if ($record) {
            $record->update($static);
            $this->summary->incrementUpdated();
        } else {
            DataInformation::create([...$static, 'legacy_code' => $legacyCode]);
            $this->summary->incrementCreated();
        }
    }

    /**
     * Same asset-linking safeguard as ChangeImport::resolveAssetId — code
     * match alone isn't proof it's the same real-world asset.
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
