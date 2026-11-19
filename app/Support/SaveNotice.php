<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\Change;
use App\Models\DataInformation;
use App\Models\HrRisk;
use App\Models\KnowledgeAsset;
use App\Models\Risk;
use App\Models\SecurityProgram;
use App\Models\Service;
use App\Models\ServiceTicket;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Pesan toast setelah menyimpan yang menyebut ke mana data itu dikirim,
 * misalnya "Risiko baru ditambahkan. Menunggu verifikasi admin Manajemen
 * Risiko. Tindak lanjut dibuat di Keamanan Informasi, Perubahan, dan Data &
 * Informasi."
 *
 * Hanya menyebut hal yang benar-benar terjadi pada penyimpanan ini (dicek di
 * basis data sejak $since), bukan aturan umum. Aturan umumnya ada di
 * IntegrationMap. Dasar: Permenkomdigi 6/2025 Pasal 9 ayat (5) huruf b dan g
 * (alur proses dan rancangan integrasi aplikasi), lihat docs/riset/06.
 */
class SaveNotice
{
    /** Titik waktu sebelum menyimpan; kolom created_at/updated_at berpresisi detik. */
    public static function since(): CarbonInterface
    {
        return Carbon::now()->startOfSecond()->subSecond();
    }

    public static function make(string $base, string $moduleKey, Model $record, CarbonInterface $since): string
    {
        $parts = [$base];

        if (($record->verification_status ?? null) === 'menunggu_verifikasi') {
            $parts[] = 'Menunggu verifikasi admin ' . (AdminModules::LABELS[$moduleKey] ?? 'modul ini') . '.';
        }

        if (filled($record->asset_id ?? null) && ($asset = Asset::find($record->asset_id))) {
            $parts[] = "Muncul juga di Riwayat Terkait aset {$asset->name}.";
        }

        $extra = match (true) {
            $record instanceof Risk => self::risk($record, $since),
            $record instanceof Asset => self::asset($record, $since),
            $record instanceof HrRisk => self::hrRisk($record, $since),
            $record instanceof Service => self::service($record),
            $record instanceof KnowledgeAsset && $record->index_status === 'menunggu' => 'Isi PDF sedang dibaca supaya bisa dicari di Tanya Dokumen dan Asisten.',
            default => null,
        };
        if ($extra) {
            $parts[] = $extra;
        }

        return implode(' ', $parts);
    }

    private static function risk(Risk $risk, CarbonInterface $since): ?string
    {
        if (! in_array($risk->risk_level?->value, ['tinggi', 'kritis'], true)) {
            return null;
        }

        $made = collect([
            'Keamanan Informasi' => SecurityProgram::class,
            'Perubahan' => Change::class,
            'Data & Informasi' => DataInformation::class,
        ])->filter(fn ($class) => $class::where('dynamic_data->auto_generated_from_risk', $risk->risk_code)
            ->where('created_at', '>=', $since)->exists())->keys();
        $tickets = ServiceTicket::where('dynamic_data->auto_generated_from_risk', $risk->risk_code)
            ->where('created_at', '>=', $since)->count();

        if ($made->isEmpty() && $tickets === 0) {
            return null;
        }

        $places = $made->all();
        if ($tickets > 0) {
            $places[] = "{$tickets} tiket di Layanan";
        }

        return "Karena levelnya {$risk->risk_level->label()}, tindak lanjut dibuat di " . self::join($places) . '.';
    }

    private static function asset(Asset $asset, CarbonInterface $since): ?string
    {
        $count = Risk::where('asset_id', $asset->id)->where('updated_at', '>=', $since)->count();

        return $count > 0 && $asset->wasChanged('criticality_level')
            ? "Level {$count} risiko pada aset ini ikut disesuaikan."
            : null;
    }

    private static function hrRisk(HrRisk $hrRisk, CarbonInterface $since): ?string
    {
        if (blank($hrRisk->personnel_ref)) {
            return null;
        }
        $count = Risk::where('personnel_ref', $hrRisk->personnel_ref)->where('updated_at', '>=', $since)->count();

        return $count > 0 ? "Level {$count} risiko dengan Kode Personil yang sama ikut disesuaikan." : null;
    }

    private static function service(Service $service): ?string
    {
        $count = $service->assets()->count();

        return $count > 0 ? "Muncul juga di Riwayat Terkait {$count} aset." : null;
    }

    /** ["A", "B", "C"] -> "A, B, dan C" */
    private static function join(array $items): string
    {
        $items = array_values($items);

        return count($items) <= 2
            ? implode(' dan ', $items)
            : implode(', ', array_slice($items, 0, -1)) . ', dan ' . end($items);
    }
}
