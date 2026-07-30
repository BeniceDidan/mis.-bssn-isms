<?php

namespace App\Imports;

use App\Enums\Level;
use App\Imports\Concerns\NormalizesImportValues;
use App\Models\Asset;
use App\Models\Service;
use App\Models\ServiceEvaluation;
use App\Models\ServiceTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

/**
 * Reads "Manajemen_Layanan_SPBE_Terintegrasi_Aset_Risiko.xlsx" — 3 related
 * sheets, unlike every prior module's single flat register:
 *
 *  - "Katalog Layanan": the master catalog, becomes Service rows.
 *  - "Log Operasional Layanan": many tickets per service, becomes
 *    ServiceTicket rows FK'd to the Service resolved by "Kode Layanan".
 *  - "Evaluasi Kinerja Layanan": an SLA snapshot per service, becomes
 *    ServiceEvaluation rows, same FK resolution.
 *
 * Catalog rows are imported first so the other two sheets can resolve
 * "Kode Layanan" against real Service ids.
 */
class ServiceImport
{
    use NormalizesImportValues;

    private const CATALOG_SHEET = 'Katalog Layanan';

    private const TICKETS_SHEET = 'Log Operasional Layanan';

    private const EVALUATIONS_SHEET = 'Evaluasi Kinerja Layanan';

    private const CATALOG_ALIASES = [
        'nama layanan spbe' => 'name',
        'deskripsi / cakupan layanan' => 'description',
        'pengelola layanan (owner)' => 'owner_unit',
        'pengelola teknis' => 'technical_manager',
        'cara akses' => 'access_method',
        'target ketersediaan (sla)' => 'sla_target',
        'waktu pelayanan' => 'service_hours',
    ];

    private const TICKET_ALIASES = [
        'tanggal masuk' => 'reported_at',
        'nama pemohon' => 'requester_name',
        'jenis permintaan / gangguan' => 'issue',
        'tingkat dampak (kritikalitas)' => 'impact_level',
        'risiko tik terkait (korelasi)' => 'related_risk_text',
        'solusi / tindakan operasional' => 'resolution',
        'status tiket' => 'status',
    ];

    private const EVALUATION_ALIASES = [
        'realisasi uptime (%)' => 'uptime_actual',
        'target sla (%)' => 'sla_target',
        'status capaian' => 'achievement_status',
        'total gangguan (bulan ini)' => 'incident_count',
        'rata-rata waktu resolusi (mttr)' => 'mttr',
        'rekomendasi peningkatan kontrol & mitigasi' => 'recommendation',
    ];

    private ImportSummary $summary;

    /** @var array<string, int> Kode Layanan -> Service id, filled while importing the catalog. */
    private array $serviceLookup = [];

    public function __construct()
    {
        $this->summary = new ImportSummary();
    }

    public function run(string $path): void
    {
        $reader = new Xlsx();
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        DB::transaction(function () use ($spreadsheet) {
            $this->importCatalog($spreadsheet);
            $this->importTickets($spreadsheet);
            $this->importEvaluations($spreadsheet);
        });

        $spreadsheet->disconnectWorksheets();
    }

    public function summary(): ImportSummary
    {
        return $this->summary;
    }

    private function importCatalog(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->getSheetByName(self::CATALOG_SHEET);

        if ($sheet === null) {
            $this->summary->addError(self::CATALOG_SHEET, 0, 'Sheet "' . self::CATALOG_SHEET . '" tidak ditemukan di file ini.');

            return;
        }

        $rows = $sheet->toArray(null, true, true, false);
        $headers = $this->resolveSingleRowHeaders($rows, 'Kode Layanan');

        if ($headers === null) {
            $this->summary->addError(self::CATALOG_SHEET, 0, 'Baris header "Kode Layanan" tidak ditemukan.');

            return;
        }

        [$headerRowIndex, $columnLabels] = $headers;

        foreach ($rows as $index => $row) {
            if ($index <= $headerRowIndex) {
                continue;
            }

            $this->importCatalogRow($row, $columnLabels, $index + 1);
        }
    }

    private function importCatalogRow(array $row, array $columnLabels, int $excelRowNumber): void
    {
        $values = [];

        foreach ($columnLabels as $col => $label) {
            if (blank($label)) {
                continue;
            }

            $values[$label] = $this->normalizeCellValue($row[$col] ?? null);
        }

        $legacyCode = $values['Kode Layanan'] ?? null;

        if (blank($legacyCode)) {
            return;
        }

        $static = [];
        $dynamic = [];
        $assetRefs = null;

        foreach ($values as $label => $value) {
            if ($label === 'Kode Layanan' || blank($value)) {
                continue;
            }

            if (Str::lower($label) === 'aset tik utama terkait') {
                $assetRefs = (string) $value;
                $dynamic['Aset TIK Utama Terkait (dari file)'] = $value;

                continue;
            }

            $key = self::CATALOG_ALIASES[Str::lower($label)] ?? null;
            $key === null ? $dynamic[$label] = $value : $static[$key] = $value;
        }

        if (blank($static['name'] ?? null)) {
            $this->summary->addError(self::CATALOG_SHEET, $excelRowNumber, "Baris dilewati (Kode {$legacyCode}): Nama Layanan SPBE kosong.");

            return;
        }

        $static['dynamic_data'] = $dynamic;

        $service = Service::withTrashed()->where('legacy_code', $legacyCode)->first();

        if ($service) {
            $service->update($static);
            $this->summary->incrementUpdated();
        } else {
            $service = Service::create([...$static, 'legacy_code' => $legacyCode]);
            $this->summary->incrementCreated();
        }

        $this->serviceLookup[$legacyCode] = $service->id;

        $assetIds = $this->resolveAssetIds($assetRefs ?? '');

        if (! empty($assetIds)) {
            $service->assets()->syncWithoutDetaching($assetIds);
            $service->bridgeToLinkedAssets('updated');
        }
    }

    /**
     * "Aset TIK Utama Terkait" lists several assets per row, formatted
     * "CODE (Nama), CODE (Nama)" — e.g. "DI-001 (Data Pegawai Aktif),
     * PL-001 (SIMPEG)". Each fragment is matched the same cautious way
     * ChangeImport::resolveAssetId() does: legacy_code match plus the name
     * sharing at least one real word, otherwise skipped (not guessed).
     *
     * @return array<int, int>
     */
    private function resolveAssetIds(string $value): array
    {
        if (blank($value)) {
            return [];
        }

        $ids = [];

        foreach (explode(',', $value) as $fragment) {
            $fragment = trim($fragment);

            if (blank($fragment) || ! preg_match('/^([A-Za-z0-9_-]+)\s*(?:\((.*)\))?$/', $fragment, $matches)) {
                continue;
            }

            $code = trim($matches[1]);
            $name = trim($matches[2] ?? '');

            $asset = Asset::withTrashed()->where('legacy_code', $code)->first();

            if (! $asset) {
                continue;
            }

            if (filled($name) && ! $this->namesShareAWord($name, $asset->name)) {
                continue;
            }

            $ids[] = $asset->id;
        }

        return array_unique($ids);
    }

    private function namesShareAWord(string $a, string $b): bool
    {
        $significantWords = fn (string $text) => array_filter(
            preg_split('/\W+/', Str::lower($text)) ?: [],
            fn ($word) => mb_strlen($word) >= 4
        );

        return count(array_intersect($significantWords($a), $significantWords($b))) > 0;
    }

    private function importTickets(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->getSheetByName(self::TICKETS_SHEET);

        if ($sheet === null) {
            $this->summary->addError(self::TICKETS_SHEET, 0, 'Sheet "' . self::TICKETS_SHEET . '" tidak ditemukan di file ini.');

            return;
        }

        $rows = $sheet->toArray(null, true, true, false);
        $headers = $this->resolveSingleRowHeaders($rows, 'Tiket ID');

        if ($headers === null) {
            $this->summary->addError(self::TICKETS_SHEET, 0, 'Baris header "Tiket ID" tidak ditemukan.');

            return;
        }

        [$headerRowIndex, $columnLabels] = $headers;

        foreach ($rows as $index => $row) {
            if ($index <= $headerRowIndex) {
                continue;
            }

            $this->importTicketRow($row, $columnLabels, $index + 1);
        }
    }

    private function importTicketRow(array $row, array $columnLabels, int $excelRowNumber): void
    {
        $values = [];

        foreach ($columnLabels as $col => $label) {
            if (blank($label)) {
                continue;
            }

            $values[$label] = $this->normalizeCellValue($row[$col] ?? null);
        }

        $legacyCode = $values['Tiket ID'] ?? null;
        $serviceCode = $values['Kode Layanan'] ?? null;

        if (blank($legacyCode)) {
            return;
        }

        $serviceId = $this->serviceLookup[$serviceCode] ?? null;

        if ($serviceId === null) {
            $this->summary->addError(self::TICKETS_SHEET, $excelRowNumber, "Baris dilewati (Tiket {$legacyCode}): Kode Layanan \"{$serviceCode}\" tidak ditemukan di Katalog Layanan.");

            return;
        }

        $static = ['service_id' => $serviceId];
        $dynamic = [];

        foreach ($values as $label => $value) {
            if (in_array($label, ['Tiket ID', 'Kode Layanan'], true) || blank($value)) {
                continue;
            }

            $key = self::TICKET_ALIASES[Str::lower($label)] ?? null;

            match ($key) {
                'reported_at' => $static[$key] = $this->parseDate($value),
                'impact_level' => $static[$key] = $this->normalizeLevelValue((string) $value),
                null => $dynamic[$label] = $value,
                default => $static[$key] = $value,
            };
        }

        $static['dynamic_data'] = $dynamic;

        $existing = ServiceTicket::where('legacy_code', $legacyCode)->first();

        if ($existing) {
            $existing->update($static);
            $this->summary->incrementUpdated();
        } else {
            ServiceTicket::create([...$static, 'legacy_code' => $legacyCode]);
            $this->summary->incrementCreated();
        }
    }

    private function importEvaluations(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->getSheetByName(self::EVALUATIONS_SHEET);

        if ($sheet === null) {
            $this->summary->addError(self::EVALUATIONS_SHEET, 0, 'Sheet "' . self::EVALUATIONS_SHEET . '" tidak ditemukan di file ini.');

            return;
        }

        $rows = $sheet->toArray(null, true, true, false);
        $headers = $this->resolveSingleRowHeaders($rows, 'Kode Layanan');

        if ($headers === null) {
            $this->summary->addError(self::EVALUATIONS_SHEET, 0, 'Baris header "Kode Layanan" tidak ditemukan.');

            return;
        }

        [$headerRowIndex, $columnLabels] = $headers;

        foreach ($rows as $index => $row) {
            if ($index <= $headerRowIndex) {
                continue;
            }

            $this->importEvaluationRow($row, $columnLabels, $index + 1);
        }
    }

    private function importEvaluationRow(array $row, array $columnLabels, int $excelRowNumber): void
    {
        $values = [];

        foreach ($columnLabels as $col => $label) {
            if (blank($label)) {
                continue;
            }

            $values[$label] = $this->normalizeCellValue($row[$col] ?? null);
        }

        $serviceCode = $values['Kode Layanan'] ?? null;

        if (blank($serviceCode)) {
            return;
        }

        $serviceId = $this->serviceLookup[$serviceCode] ?? null;

        if ($serviceId === null) {
            $this->summary->addError(self::EVALUATIONS_SHEET, $excelRowNumber, "Baris dilewati: Kode Layanan \"{$serviceCode}\" tidak ditemukan di Katalog Layanan.");

            return;
        }

        $static = ['service_id' => $serviceId];
        $dynamic = [];

        foreach ($values as $label => $value) {
            if (in_array($label, ['Kode Layanan', 'Nama Layanan SPBE'], true) || blank($value)) {
                continue;
            }

            $key = self::EVALUATION_ALIASES[Str::lower($label)] ?? null;

            match ($key) {
                'incident_count' => $static[$key] = (int) $value,
                null => $dynamic[$label] = $value,
                default => $static[$key] = $value,
            };
        }

        $static['dynamic_data'] = $dynamic;

        // No natural unique key on this sheet (no ticket-style ID column),
        // so each import run appends a fresh snapshot rather than upserting
        // — matches the sheet's own "one row per evaluation period" shape.
        ServiceEvaluation::create($static);
        $this->summary->incrementCreated();
    }
}
