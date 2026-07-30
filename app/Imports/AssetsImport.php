<?php

namespace App\Imports;

use App\Enums\AssetCategory;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

/**
 * Loads the workbook directly through PhpSpreadsheet (bypassing Maatwebsite
 * Excel's sheet-splitting pipeline) so every sheet stays in scope while
 * reading — required for KRITIKALITAS ASET's cross-sheet lookup formula to
 * resolve correctly. See AssetCategorySheetImport for why.
 */
class AssetsImport
{
    /** @var array<string, AssetCategorySheetImport> */
    private array $sheetImporters = [];

    /**
     * Sheet names must match "Daftar Inventaris Aset.xlsx" exactly. If
     * Kominfo renames a tab next year this map is the one place to update.
     */
    private const SHEET_TO_CATEGORY = [
        'Data & Informasi' => AssetCategory::DataInformasi,
        'Perangkat Lunak' => AssetCategory::PerangkatLunak,
        'Perangkat Keras' => AssetCategory::PerangkatKeras,
        'Sarana Pendukung' => AssetCategory::SaranaPendukung,
        'SDM & Pihak Ketiga' => AssetCategory::SdmPihakKetiga,
    ];

    public function run(string $path): void
    {
        $reader = new Xlsx();
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        foreach (self::SHEET_TO_CATEGORY as $sheetName => $category) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            $importer = new AssetCategorySheetImport($category);
            $this->sheetImporters[$sheetName] = $importer;

            if ($sheet === null) {
                $importer->summary()->addError($category->label(), 0, "Sheet \"{$sheetName}\" tidak ditemukan di file ini — sheet dilewati.");

                continue;
            }

            // calculateFormulas=true (the toArray() default) resolves the
            // KRITIKALITAS ASET lookup formula since the whole workbook —
            // including 'Definisi Range Aset' — stays loaded together.
            $rows = $sheet->toArray(null, true, true, false);
            $importer->import($rows);
        }

        $spreadsheet->disconnectWorksheets();
    }

    public function summary(): ImportSummary
    {
        $summary = new ImportSummary();

        foreach ($this->sheetImporters as $importer) {
            $summary->merge($importer->summary());
        }

        return $summary;
    }
}
