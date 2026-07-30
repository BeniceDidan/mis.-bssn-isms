<?php

namespace App\Imports;

use App\Enums\KnowledgeType;
use App\Imports\Concerns\NormalizesImportValues;
use App\Models\KnowledgeActivity;
use App\Models\KnowledgeAsset;
use App\Models\KnowledgeExpert;
use App\Models\KnowledgeRisk;
use App\Services\KnowledgeRiskLevelService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Reads a Manajemen Pengetahuan workbook — either the original single-sheet
 * "5. Manajemen Pengetahuan.xlsx" (just the asset register) or the fuller
 * "Manajemen_Pengetahuan_BSSN.xlsx" (adds the other 3 "komponen utama":
 * Peta Keahlian Pegawai, Log Aktivitas Berbagi, Register Risiko KM — the
 * BSSN design guide's full scope). Each sheet is optional; missing ones are
 * silently skipped rather than erroring, so both file shapes work with the
 * same importer.
 *
 * asset_id on the asset register itself still isn't imported — that stays
 * a manual link via the form, same as before.
 */
class KnowledgeImport
{
    use NormalizesImportValues;

    private const ASSETS_SHEET = '1. Register Aset Pengetahuan';

    private const EXPERTS_SHEET = '2. Peta Keahlian Pegawai';

    private const ACTIVITIES_SHEET = '3. Log Aktivitas Berbagi';

    private const RISKS_SHEET = '4. Register Risiko KM';

    private const ASSET_COLUMN_ALIASES = [
        'nama aset pengetahuan' => 'title',
        'jenis pengetahuan (tacit/explicit)' => 'knowledge_type',
        'kategori pengetahuan' => 'category',
        'unit pemilik (owner)' => 'owner_unit',
        'tingkat aksesibilitas' => 'access_level',
        'tanggal pembaruan terakhir' => 'last_reviewed_at',
    ];

    private const EXPERT_COLUMN_ALIASES = [
        'nama pegawai' => 'nama_pegawai',
        'jabatan & unit kerja' => 'jabatan_unit',
        'keahlian spesifik' => 'keahlian_spesifik',
        'sertifikasi / lisensi' => 'sertifikasi_lisensi',
        'peran km (mentor/kontributor)' => 'peran_km',
    ];

    private const ACTIVITY_COLUMN_ALIASES = [
        'tanggal pelaksanaan' => 'tanggal_pelaksanaan',
        'nama kegiatan' => 'nama_kegiatan',
        'materi / topik' => 'materi_topik',
        'narasumber' => 'narasumber_name',
        'jumlah peserta' => 'jumlah_peserta',
        'link bukti dukung (notulen/absen)' => 'link_bukti',
    ];

    private const RISK_COLUMN_ALIASES = [
        'id aset terkait' => 'asset_legacy_code',
        'pernyataan risiko (ancaman / kerentanan km)' => 'pernyataan_risiko',
        'akar penyebab' => 'akar_penyebab',
        'area dampak bssn' => 'area_dampak',
        'dampak (1-5)' => 'dampak',
        'kemungkinan (1-5)' => 'kemungkinan',
        'rencana mitigasi / kontrol pengendalian' => 'rencana_mitigasi',
        'penanggung jawab (risk owner)' => 'penanggung_jawab',
        'tingkat residual risk' => 'tingkat_residual_risk',
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

        DB::transaction(function () use ($spreadsheet) {
            $this->importAssets($spreadsheet);
            $this->importExperts($spreadsheet);
            $this->importActivities($spreadsheet);
            $this->importRisks($spreadsheet);
        });

        $spreadsheet->disconnectWorksheets();
    }

    public function summary(): ImportSummary
    {
        return $this->summary;
    }

    /**
     * Every sheet in this workbook uses a plain single-row header, but the
     * literal text of the first (ID) column varies between file revisions
     * (one copy's header cell is just "a", presumably a source typo) — so
     * the ID column is always read positionally (index 0) rather than by
     * matching its header text, while every other column still resolves by
     * label as usual.
     */
    private function resolveSheet(Spreadsheet $spreadsheet, string $sheetName, string $anchorLabel): ?array
    {
        $sheet = $spreadsheet->getSheetByName($sheetName);

        if ($sheet === null) {
            return null;
        }

        $rows = $sheet->toArray(null, true, true, false);
        $headers = $this->resolveSingleRowHeaders($rows, $anchorLabel);

        if ($headers === null) {
            $this->summary->addError($sheetName, 0, "Baris header \"{$anchorLabel}\" tidak ditemukan.");

            return null;
        }

        return [$rows, ...$headers];
    }

    private function importAssets(Spreadsheet $spreadsheet): void
    {
        $resolved = $this->resolveSheet($spreadsheet, self::ASSETS_SHEET, 'Nama Aset Pengetahuan');

        if ($resolved === null) {
            return;
        }

        [$rows, $headerRowIndex, $columnLabels] = $resolved;
        $idColumnLabel = $columnLabels[0];

        foreach ($rows as $index => $row) {
            if ($index <= $headerRowIndex) {
                continue;
            }

            $this->importAssetRow($row, $columnLabels, $idColumnLabel, $index + 1);
        }
    }

    private function importAssetRow(array $row, array $columnLabels, string $idColumnLabel, int $excelRowNumber): void
    {
        $values = [];

        foreach ($columnLabels as $col => $label) {
            if (blank($label)) {
                continue;
            }

            $values[$label] = $this->normalizeCellValue($row[$col] ?? null);
        }

        $legacyCode = $values[$idColumnLabel] ?? null;

        if (blank($legacyCode)) {
            return;
        }

        $static = [];
        $dynamic = [];

        foreach ($values as $label => $value) {
            if ($label === $idColumnLabel || blank($value)) {
                continue;
            }

            $key = self::ASSET_COLUMN_ALIASES[Str::lower($label)] ?? null;

            match ($key) {
                'knowledge_type' => $static[$key] = $this->normalizeEnumValue(KnowledgeType::class, (string) $value),
                'last_reviewed_at' => $static[$key] = $this->parseDate($value),
                null => $dynamic[$label] = $value,
                default => $static[$key] = $value,
            };
        }

        if (blank($static['title'] ?? null)) {
            $this->summary->addError(self::ASSETS_SHEET, $excelRowNumber, "Baris dilewati (Kode {$legacyCode}): Nama Aset Pengetahuan kosong.");

            return;
        }

        $static['dynamic_data'] = $dynamic;

        $record = KnowledgeAsset::withTrashed()->where('legacy_code', $legacyCode)->first();

        if ($record) {
            $record->update($static);
            $this->summary->incrementUpdated();
        } else {
            KnowledgeAsset::create([...$static, 'legacy_code' => $legacyCode]);
            $this->summary->incrementCreated();
        }
    }

    private function importExperts(Spreadsheet $spreadsheet): void
    {
        $resolved = $this->resolveSheet($spreadsheet, self::EXPERTS_SHEET, 'Nama Pegawai');

        if ($resolved === null) {
            return;
        }

        [$rows, $headerRowIndex, $columnLabels] = $resolved;
        $idColumnLabel = $columnLabels[0];

        foreach ($rows as $index => $row) {
            if ($index <= $headerRowIndex) {
                continue;
            }

            $values = [];

            foreach ($columnLabels as $col => $label) {
                if (blank($label)) {
                    continue;
                }

                $values[$label] = $this->normalizeCellValue($row[$col] ?? null);
            }

            $legacyCode = $values[$idColumnLabel] ?? null;

            if (blank($legacyCode)) {
                continue;
            }

            $static = [];
            $dynamic = [];

            foreach ($values as $label => $value) {
                if ($label === $idColumnLabel || blank($value)) {
                    continue;
                }

                $key = self::EXPERT_COLUMN_ALIASES[Str::lower($label)] ?? null;
                $key === null ? $dynamic[$label] = $value : $static[$key] = $value;
            }

            if (blank($static['nama_pegawai'] ?? null)) {
                $this->summary->addError(self::EXPERTS_SHEET, $index + 1, "Baris dilewati (Kode {$legacyCode}): Nama Pegawai kosong.");

                continue;
            }

            $static['dynamic_data'] = $dynamic;

            $record = KnowledgeExpert::withTrashed()->where('legacy_code', $legacyCode)->first();

            if ($record) {
                $record->update($static);
                $this->summary->incrementUpdated();
            } else {
                KnowledgeExpert::create([...$static, 'legacy_code' => $legacyCode]);
                $this->summary->incrementCreated();
            }
        }
    }

    private function importActivities(Spreadsheet $spreadsheet): void
    {
        $resolved = $this->resolveSheet($spreadsheet, self::ACTIVITIES_SHEET, 'Nama Kegiatan');

        if ($resolved === null) {
            return;
        }

        [$rows, $headerRowIndex, $columnLabels] = $resolved;
        $idColumnLabel = $columnLabels[0];

        foreach ($rows as $index => $row) {
            if ($index <= $headerRowIndex) {
                continue;
            }

            $values = [];

            foreach ($columnLabels as $col => $label) {
                if (blank($label)) {
                    continue;
                }

                $values[$label] = $this->normalizeCellValue($row[$col] ?? null);
            }

            $legacyCode = $values[$idColumnLabel] ?? null;

            if (blank($legacyCode)) {
                continue;
            }

            $static = [];
            $dynamic = [];

            foreach ($values as $label => $value) {
                if ($label === $idColumnLabel || blank($value)) {
                    continue;
                }

                $key = self::ACTIVITY_COLUMN_ALIASES[Str::lower($label)] ?? null;

                match ($key) {
                    'tanggal_pelaksanaan' => $static[$key] = $this->parseDate($value),
                    'jumlah_peserta' => $static[$key] = (int) $value,
                    null => $dynamic[$label] = $value,
                    default => $static[$key] = $value,
                };
            }

            if (blank($static['nama_kegiatan'] ?? null)) {
                $this->summary->addError(self::ACTIVITIES_SHEET, $index + 1, "Baris dilewati (Kode {$legacyCode}): Nama Kegiatan kosong.");

                continue;
            }

            if (filled($static['narasumber_name'] ?? null)) {
                $expert = KnowledgeExpert::where('nama_pegawai', $static['narasumber_name'])->first();
                $static['narasumber_id'] = $expert?->id;
            }

            $static['dynamic_data'] = $dynamic;

            $record = KnowledgeActivity::withTrashed()->where('legacy_code', $legacyCode)->first();

            if ($record) {
                $record->update($static);
                $this->summary->incrementUpdated();
            } else {
                KnowledgeActivity::create([...$static, 'legacy_code' => $legacyCode]);
                $this->summary->incrementCreated();
            }
        }
    }

    private function importRisks(Spreadsheet $spreadsheet): void
    {
        $resolved = $this->resolveSheet($spreadsheet, self::RISKS_SHEET, 'Pernyataan Risiko (Ancaman / Kerentanan KM)');

        if ($resolved === null) {
            return;
        }

        [$rows, $headerRowIndex, $columnLabels] = $resolved;
        $idColumnLabel = $columnLabels[0];
        $matrix = new KnowledgeRiskLevelService();

        foreach ($rows as $index => $row) {
            if ($index <= $headerRowIndex) {
                continue;
            }

            $values = [];

            foreach ($columnLabels as $col => $label) {
                if (blank($label)) {
                    continue;
                }

                $values[$label] = $this->normalizeCellValue($row[$col] ?? null);
            }

            $legacyCode = $values[$idColumnLabel] ?? null;

            if (blank($legacyCode)) {
                continue;
            }

            $static = [];
            $dynamic = [];

            foreach ($values as $label => $value) {
                if ($label === $idColumnLabel || blank($value)) {
                    continue;
                }

                $key = self::RISK_COLUMN_ALIASES[Str::lower($label)] ?? null;

                match ($key) {
                    'dampak', 'kemungkinan' => $static[$key] = (int) $value,
                    'tingkat_residual_risk' => $static[$key] = $this->normalizeLevelValue((string) $value),
                    'asset_legacy_code', null => $dynamic[$label] = $value,
                    default => $static[$key] = $value,
                };
            }

            // The sheet's own "ID Aset Terkait" column is preserved in
            // dynamic_data above (asset_legacy_code isn't a real column) —
            // resolve the real FK here via an exact legacy_code match, no
            // fuzzy name guard needed since both come from the same
            // workbook with consistent AP-xxx codes.
            $assetLegacyCode = $values['ID Aset Terkait'] ?? null;

            if (filled($assetLegacyCode)) {
                $asset = KnowledgeAsset::withTrashed()->where('legacy_code', $assetLegacyCode)->first();
                $static['knowledge_asset_id'] = $asset?->id;
            }

            if (blank($static['pernyataan_risiko'] ?? null)) {
                $this->summary->addError(self::RISKS_SHEET, $index + 1, "Baris dilewati (Kode {$legacyCode}): Pernyataan Risiko kosong.");

                continue;
            }

            if (isset($static['dampak'], $static['kemungkinan'])) {
                $static['skor_risiko_bawaan'] = $static['dampak'] * $static['kemungkinan'];
                $static['tingkat_risiko_bawaan'] = $matrix->derive($static['kemungkinan'], $static['dampak'])?->value;
            }

            $static['dynamic_data'] = $dynamic;

            $record = KnowledgeRisk::withTrashed()->where('legacy_code', $legacyCode)->first();

            if ($record) {
                $record->update($static);
                $this->summary->incrementUpdated();
            } else {
                KnowledgeRisk::create([...$static, 'legacy_code' => $legacyCode]);
                $this->summary->incrementCreated();
            }
        }
    }
}
