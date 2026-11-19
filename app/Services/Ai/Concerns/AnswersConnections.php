<?php

namespace App\Services\Ai\Concerns;

use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\EvidenceDocument;
use App\Models\HrRisk;
use App\Models\ImportLog;
use App\Models\KnowledgeAsset;
use App\Models\KnowledgeExpert;
use App\Models\KnowledgeRisk;
use App\Models\Risk;
use App\Models\Service;
use App\Models\ServiceTicket;
use App\Models\User;
use App\Support\AdminModules;
use App\Support\AppHelp;
use App\Support\IntegrationMap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Asisten: "nyambung ke mana?". Tiga tingkat jawaban, semuanya dari kode
 * dan basis data (tanpa LLM; uji 18 Nov 2026: llama3.2 menjawab "SDM
 * terhubung ke Asisten dan Risiko" dan mengarang alasannya):
 *  - berkas yang baru diunggah penanya (PDF pengetahuan, bukti dukung, impor Excel);
 *  - satu data tertentu (aset, risiko, layanan, tiket, dst.);
 *  - satu modul, dari App\Support\IntegrationMap (isi yang sama dengan pop-out
 *    Titik Integrasi), disaring bila pertanyaannya skenario ("kalau risiko jadi kritis").
 */
trait AnswersConnections
{
    /** Kata yang menandai pertanyaan integrasi/skenario, bukan nama data yang dicari. */
    private const CONNECT_WORDS = [
        'tersambung', 'nyambung', 'menyambung', 'sambungan', 'terhubung', 'terkoneksi', 'terintegrasi', 'integrasi', 'keterkaitan',
        'berkaitan', 'berhubungan', 'hubungan', 'terjadi', 'efek', 'efeknya', 'dampaknya', 'pengaruh', 'pengaruhnya', 'memicu',
        'dimanfaatkan', 'larinya', 'kalau', 'jika', 'bila', 'apabila', 'nanti', 'titik', 'unggah', 'diunggah', 'mengunggah', 'impor',
        'diimpor', 'excel', 'naik', 'menjadi', 'diarsipkan', 'arsip', 'dihapus', 'hapus', 'barusan', 'tadi', 'kemana', 'sop',
    ];

    private const INTEGRATION_OVERVIEW = "Semua modul tersambung lewat dua kunci: Kode Aset dan Kode Personil.\n"
        . "• Kode Aset: Risiko, Perubahan, Data & Informasi, Pengetahuan, Keamanan Informasi, dan Layanan menunjuk ke aset. Semuanya tampil di Riwayat Terkait aset itu.\n"
        . "• Kode Personil: menandai orang yang sama di SDM, Peta Keahlian, dan modul lain. Kodenya harus ditulis sama persis.\n"
        . "• Otomatis: risiko berlevel Tinggi atau Kritis membuat tindak lanjut di Keamanan Informasi, Perubahan, dan Data & Informasi, ditambah tiket bila asetnya menopang layanan.\n"
        . "• PDF di Aset Pengetahuan menjadi sumber Tanya Dokumen, Analisis AI tiket, dan Asisten.";

    private const EVIDENCE_FLOW = 'Bukti dukung berdiri sendiri: diunggah dan dinilai di Evaluasi Bukti Dukung, lalu diputuskan admin modul yang dipilih. Bukti dukung tidak menambah atau mengubah data di modul itu.';

    /** Kata benda untuk hasil impor per jenis catatan. */
    private const IMPORT_NOUNS = [
        'asset' => 'aset', 'change' => 'perubahan', 'data_information' => 'catatan data & informasi', 'hr_risk' => 'risiko SDM',
        'knowledge_asset' => 'aset pengetahuan', 'knowledge_expert' => 'profil keahlian', 'knowledge_activity' => 'aktivitas berbagi',
        'knowledge_risk' => 'risiko KM', 'security_program' => 'program keamanan', 'service' => 'layanan',
    ];

    private static function wantsConnection(string $text): bool
    {
        // "integrasi" saja tidak cukup: "syarat permohonan integrasi API" adalah isi dokumen (uji 18 Nov 2026).
        return (bool) preg_match('/\b(tersambung|nyambung|menyambung|sambungan|terhubung|terkoneksi|titik integrasi|integrasi antar ?modul|integrasi modul|integrasi lintas modul|integrasinya|terintegrasi (?:dengan apa|ke mana)|keterkaitan|berkaitan dengan apa|terkait (?:apa|ke mana|dengan apa)|berhubungan dengan apa|ke mana saja|masuk ke mana|larinya ke mana|dipakai di mana|digunakan di mana|dipakai untuk apa|dimanfaatkan di mana|apa yang terjadi|yang terjadi (?:kalau|jika|bila|apabila)|efeknya|dampaknya ke|pengaruhnya|memicu apa)\b/u', $text);
    }

    /** "File yang barusan saya unggah", "impor Excel tadi" — bukan "kalau saya unggah". */
    private static function mentionsOwnUpload(string $text): bool
    {
        return preg_match('/\b(unggah|diunggah|mengunggah|unggahan|impor|diimpor|mengimpor)\b/u', $text)
            && preg_match('/\b(barusan|tadi|baru saja|terakhir|sudah saya|yang saya|saya unggah|saya impor|kemarin|hari ini)\b/u', $text)
            && ! preg_match('/\b(kalau|jika|apabila|bila|seandainya|misal|misalnya)\b/u', $text)
            && ! preg_match('/\b(daftar|semua|apa saja|berapa)\b/u', $text);
    }

    // ------------------------------------------------------------------
    // Berkas yang baru diunggah penanya
    // ------------------------------------------------------------------

    private function uploadAnswer(string $text): ?array
    {
        $userId = auth()->id();
        if ($userId === null) {
            return null;
        }

        $want = match (true) {
            (bool) preg_match('/\bbukti dukung\b/u', $text) => 'bukti',
            (bool) preg_match('/\b(impor|diimpor|mengimpor|excel|xlsx|xls)\b/u', $text) => 'impor',
            (bool) preg_match('/\b(pdf|sop|pengetahuan|manual book|dokumentasi)\b/u', $text) => 'pengetahuan',
            default => null,
        };

        $candidates = collect();
        if (in_array($want, [null, 'bukti'], true)
            && ($evidence = EvidenceDocument::query()->where('uploaded_by', $userId)->latest('created_at')->latest('id')->first())) {
            $candidates->push(['at' => $evidence->created_at, 'answer' => fn () => $this->evidenceConnections($evidence, true)]);
        }
        if (in_array($want, [null, 'pengetahuan'], true)) {
            // PDF pengetahuan tidak punya kolom pengunggah; jejaknya ada di
            // activity_logs (dibuat/diubah dengan document_path terisi).
            $log = ActivityLog::query()->where('user_id', $userId)->where('loggable_type', 'knowledge_asset')
                ->whereIn('action_type', ['created', 'updated'])->whereNotNull('new_values->document_path')
                ->latest('performed_at')->latest('id')->first();
            if ($log && ($doc = KnowledgeAsset::find($log->loggable_id))) {
                $candidates->push(['at' => $log->performed_at, 'answer' => fn () => $this->knowledgeConnections($doc, $log->performed_at)]);
            }
        }
        if (in_array($want, [null, 'impor'], true)
            && ($import = ImportLog::query()->where('user_id', $userId)->latest('finished_at')->latest('id')->first())) {
            $candidates->push(['at' => $import->finished_at, 'answer' => fn () => $this->importConnections($import)]);
        }

        if ($candidates->isEmpty()) {
            return [
                'answer' => match ($want) {
                    'bukti' => 'Belum ada bukti dukung yang diunggah dari akun ini.',
                    'impor' => 'Belum ada impor Excel dari akun ini.',
                    'pengetahuan' => 'Belum ada PDF pengetahuan yang diunggah dari akun ini.',
                    default => 'Belum ada berkas yang diunggah dari akun ini.',
                },
                'engine' => 'Langsung dari basis data',
                'links' => [],
            ];
        }

        return ($candidates->sortByDesc(fn ($c) => $c['at']->getTimestamp())->first()['answer'])();
    }

    private function knowledgeConnections(KnowledgeAsset $doc, $uploadedAt): array
    {
        $head = $uploadedAt
            ? "{$doc->title} ({$doc->record_code}) diunggah " . self::date($uploadedAt, true) . ' di Manajemen Pengetahuan.'
            : "{$doc->title} ({$doc->record_code}):";
        $lines = [];
        $links = collect([$this->knowledgeFile($doc)]);

        if ($asset = $doc->asset) {
            $lines[] = "• Aset {$asset->asset_code} {$asset->name}: dokumen ini tampil di Riwayat Terkait aset itu.";
            $links->push($this->describe('aset', $asset));
        } else {
            $lines[] = '• Belum tertaut ke aset. Isi Aset Terkait supaya tampil di Riwayat Terkait aset.';
        }

        $chunks = $doc->chunks()->count();
        if ($doc->document_path) {
            $lines[] = match (true) {
                $chunks > 0 => "• Isinya sudah terbaca ({$chunks} bagian): bisa ditanyakan di Tanya Dokumen dan Asisten, dan bisa muncul sebagai SOP terkait di Analisis AI tiket helpdesk.",
                $doc->index_status === 'tanpa_teks' => '• PDF-nya tidak memuat teks (hasil pindai), jadi isinya belum bisa ditanyakan di Tanya Dokumen dan Asisten.',
                $doc->index_status === 'gagal' => '• Isinya gagal dibaca, jadi belum bisa ditanyakan di Tanya Dokumen dan Asisten.',
                default => '• Isinya belum dibaca, jadi belum bisa ditanyakan di Tanya Dokumen dan Asisten.',
            };
            if ($chunks > 0) {
                $links->push(['kind' => 'page', 'title' => 'Buka Tanya Dokumen', 'meta' => null, 'url' => route('knowledge.index', ['tab' => 'tanya'])]);
            }
        } elseif ($doc->external_link) {
            $lines[] = '• Berupa tautan luar, jadi isinya tidak dibaca Tanya Dokumen dan Asisten.';
        }

        if ($ref = $doc->personnel_ref) {
            $experts = KnowledgeExpert::query()->where('personnel_ref', $ref)->count();
            $hr = HrRisk::query()->where('personnel_ref', $ref)->count();
            $lines[] = $experts + $hr === 0
                ? "• Kode Personil {$ref}: belum ada Peta Keahlian atau risiko SDM dengan kode yang sama."
                : "• Kode Personil {$ref}: sama dengan " . collect([$experts ? "{$experts} profil Peta Keahlian" : null, $hr ? "{$hr} risiko SDM" : null])->filter()->implode(' dan ') . '.';
        } elseif ($uploadedAt) {
            $lines[] = '• Kode Personil belum diisi.';
        }

        if ($km = KnowledgeRisk::query()->where('knowledge_asset_id', $doc->id)->count()) {
            $lines[] = "• {$km} Risiko KM menunjuk dokumen ini.";
        }
        if ($doc->verification_status === 'menunggu_verifikasi') {
            $lines[] = '• Menunggu verifikasi admin Manajemen Pengetahuan.';
        }

        return ['answer' => $head . "\n" . implode("\n", $lines), 'engine' => 'Titik integrasi dari basis data', 'links' => $this->connectionLinks($links)];
    }

    private function evidenceConnections(EvidenceDocument $doc, bool $upload): array
    {
        $module = $doc->moduleLabel();
        $head = $upload
            ? "{$doc->title} diunggah " . self::date($doc->created_at, true) . " sebagai bukti dukung {$module}."
            : "{$doc->title}: bukti dukung {$module}.";
        $lines = [];

        if ($doc->score !== null) {
            ['found' => $found, 'total' => $total] = $doc->scoreBreakdown();
            $value = $doc->achieved_level
                ? "nilai Level {$doc->achieved_level} (" . EvidenceDocument::levelName($doc->achieved_level) . ')'
                : 'belum mencapai Level 1';
            $lines[] = $doc->pdfUnreadable()
                ? '• Isi PDF tidak bisa dibaca (kemungkinan hasil scan), jadi belum dinilai otomatis.'
                : "• Dinilai otomatis: {$value} dari klaim Level {$doc->target_level}" . ($total ? ", {$found} dari {$total} unsur ditemukan" : '') . '.';
        } else {
            $lines[] = '• Belum dinilai otomatis.';
        }

        if ($doc->reviewer_decision) {
            $lines[] = '• Keputusan admin: ' . (EvidenceDocument::DECISIONS[$doc->reviewer_decision] ?? $doc->reviewer_decision)
                . ($doc->reviewer?->name ? " oleh {$doc->reviewer->name}" : '') . '.';
        } else {
            $admins = User::query()->where('role', 'admin')->where('admin_module', $doc->module_key)->pluck('name');
            $lines[] = "• Menunggu keputusan admin {$module}" . ($admins->isNotEmpty() ? ': ' . $admins->implode(', ') : '') . '.';
        }
        $lines[] = "• Tidak menambah atau mengubah data di {$module}; hanya dinilai di Evaluasi Bukti Dukung.";

        $links = collect([
            $this->describe('bukti', $doc),
            ['kind' => 'page', 'title' => 'Buka Evaluasi Bukti Dukung', 'meta' => null, 'url' => route('evidence.index')],
        ]);

        return ['answer' => $head . "\n" . implode("\n", $lines), 'engine' => 'Titik integrasi dari basis data', 'links' => $this->connectionLinks($links)];
    }

    private function importConnections(ImportLog $import): array
    {
        $module = $import->moduleLabel();
        $head = "Impor Excel {$module}" . ($import->file_name ? " ({$import->file_name})" : '') . ' pada ' . self::date($import->finished_at, true)
            . ": {$import->created_count} data baru, {$import->updated_count} diperbarui" . ($import->error_count ? ", {$import->error_count} baris gagal" : '') . '.';

        // Data yang masuk = catatan yang dibuat/diubah pengguna itu selama impor berjalan.
        $classes = AdminModules::MODELS[$import->module_key] ?? [];
        $ids = ActivityLog::query()
            ->where('user_id', $import->user_id)
            ->whereIn('loggable_type', array_map(fn ($class) => (new $class)->getMorphClass(), $classes))
            ->whereIn('action_type', ['created', 'updated'])
            ->whereBetween('performed_at', [$import->started_at->copy()->subSecond(), $import->finished_at->copy()->addSecond()])
            ->get(['loggable_type', 'loggable_id'])
            ->groupBy('loggable_type')
            ->map(fn ($logs) => $logs->pluck('loggable_id')->unique()->values());

        $lines = [];
        foreach ($classes as $class) {
            $model = new $class();
            $rows = $class::query()->whereIn('id', $ids[$model->getMorphClass()] ?? [])->get();
            if ($rows->isEmpty()) {
                continue;
            }
            $count = $rows->count();
            $noun = self::IMPORT_NOUNS[$model->getMorphClass()] ?? 'data';
            $fillable = $model->getFillable();

            if ($class === Asset::class) {
                $lines[] = "• {$count} aset ini bisa dipilih sebagai Aset Terkait di Risiko, Perubahan, Data & Informasi, Pengetahuan, Keamanan Informasi, dan Layanan.";
            }
            if (in_array('asset_id', $fillable, true)) {
                $linked = $rows->whereNotNull('asset_id')->count();
                $lines[] = "• {$linked} dari {$count} {$noun} tertaut otomatis ke aset" . ($linked < $count ? '; ' . ($count - $linked) . ' tanpa aset (kolom Aset Terkait kosong atau namanya tidak cocok).' : '.');
            }
            if ($class === Service::class) {
                $linked = $rows->filter(fn ($service) => $service->assets()->exists())->count();
                $lines[] = "• {$linked} dari {$count} layanan tertaut ke aset lewat kolom Aset TIK Terkait.";
            }
            if ($class === HrRisk::class) {
                $refs = $rows->filter(fn ($r) => $r->personnel_ref && in_array($this->plain($r->inherent_risk_level), ['tinggi', 'kritis'], true))->pluck('personnel_ref')->unique();
                $raised = $refs->isEmpty() ? 0 : Risk::query()->whereIn('personnel_ref', $refs)->count();
                if ($raised > 0) {
                    $lines[] = "• Ada risiko SDM berlevel Tinggi/Kritis: {$raised} Risiko dengan Kode Personil yang sama ikut disesuaikan levelnya.";
                }
            } elseif (in_array('personnel_ref', $fillable, true) && ($withRef = $rows->whereNotNull('personnel_ref')->count()) > 0) {
                $lines[] = "• {$withRef} {$noun} memakai Kode Personil, jadi tersambung ke Manajemen SDM bila kodenya sama.";
            }
        }
        if ($lines === [] && $import->created_count + $import->updated_count === 0) {
            $lines[] = '• Tidak ada data yang masuk.';
        }

        $links = collect([['kind' => 'page', 'title' => "Buka {$module}", 'meta' => null, 'url' => route(AppHelp::MODULE_ROUTES[$import->module_key])]]);

        return ['answer' => $head . ($lines ? "\n" . implode("\n", $lines) : ''), 'engine' => 'Titik integrasi dari basis data', 'links' => $this->connectionLinks($links)];
    }

    // ------------------------------------------------------------------
    // Data tertentu dan modul
    // ------------------------------------------------------------------

    private function connectionAnswer(string $text, ?string $moduleKey, array $terms): ?array
    {
        $terms = array_values(array_diff($terms, self::CONNECT_WORDS));

        if (($subject = $this->connectionSubject($text, $moduleKey, $terms)) !== null) {
            return $this->recordConnections(...$subject);
        }
        if ($moduleKey === 'bukti') {
            return [
                'answer' => self::EVIDENCE_FLOW,
                'engine' => 'Titik integrasi aplikasi',
                'links' => [['kind' => 'page', 'title' => 'Buka Evaluasi Bukti Dukung', 'meta' => null, 'url' => route('evidence.index')]],
            ];
        }
        if (($mapKey = self::ADMIN_SCOPE[$moduleKey ?? ''] ?? null) !== null) {
            return $this->moduleConnections($mapKey, $text);
        }
        // Tanpa modul dan tanpa data: hanya pertanyaan integrasi yang tegas
        // ("apa yang terjadi kalau server mati" bukan pertanyaan integrasi).
        if (preg_match('/\b(integrasi|terintegrasi|terhubung|tersambung|nyambung|keterkaitan|antarmodul|antar modul|lintas modul)\b/u', $text)) {
            return ['answer' => self::INTEGRATION_OVERVIEW, 'engine' => 'Titik integrasi aplikasi', 'links' => []];
        }

        return null;
    }

    /** @return array{0: string, 1: Model}|null */
    private function connectionSubject(string $text, ?string $moduleKey, array $terms): ?array
    {
        if (($found = $this->findSubject($text, null, [])) !== null) {
            return $found;
        }
        if ($terms === []) {
            return null;
        }

        foreach ($moduleKey !== null ? [$moduleKey] : ['aset', 'layanan'] as $key) {
            $def = self::MODULES[$key];
            $rows = $this->anchors($def['model'], $def['search'], $def['code'], $def['title'], $terms);
            if ($rows !== null) {
                return [$key, $rows->first()];
            }
        }

        return null;
    }

    private function recordConnections(string $key, Model $row): array
    {
        if ($key === 'pengetahuan') {
            return $this->knowledgeConnections($row, null);
        }
        if ($key === 'bukti') {
            return $this->evidenceConnections($row, false);
        }

        $groups = [];
        $add = function (string $label, string $module, $rows) use (&$groups) {
            $rows = collect($rows)->filter()->values();
            if ($rows->isNotEmpty()) {
                $groups[] = [$label, $rows->map(fn ($r) => $this->describe($module, $r))->unique('url')->values()];
            }
        };
        $originRisk = fn () => ($code = data_get($row->dynamic_data, 'auto_generated_from_risk')) ? Risk::query()->where('risk_code', $code)->get() : [];

        switch ($key) {
            case 'aset':
                $services = $row->services()->get();
                $add('risiko', 'risiko', $row->risks()->get());
                $add('layanan', 'layanan', $services);
                $add('tiket helpdesk', 'tiket', ServiceTicket::query()->whereIn('service_id', $services->pluck('id'))->latest('id')->limit(50)->get());
                $add('perubahan', 'perubahan', $row->changes()->get());
                $add('data & informasi', 'data_informasi', $row->dataInformationRecords()->get());
                $add('dokumen pengetahuan', 'pengetahuan', $row->knowledgeAssets()->get());
                $add('program keamanan', 'keamanan', $row->securityPrograms()->get());
                break;
            case 'risiko':
                $add('aset', 'aset', [$row->asset]);
                foreach (['keamanan' => 'program keamanan', 'perubahan' => 'perubahan', 'data_informasi' => 'data & informasi', 'tiket' => 'tiket helpdesk'] as $module => $label) {
                    $add("{$label} (dibuat otomatis)", $module, self::MODULES[$module]['model']::query()->where('dynamic_data->auto_generated_from_risk', $row->risk_code)->get());
                }
                if ($row->asset) {
                    $add('layanan yang ditopang asetnya', 'layanan', $row->asset->services()->get());
                }
                break;
            case 'layanan':
                $assets = $row->assets()->get();
                $add('aset', 'aset', $assets);
                $add('tiket helpdesk', 'tiket', $row->tickets()->latest('id')->limit(50)->get());
                $add('risiko pada asetnya', 'risiko', Risk::query()->whereIn('asset_id', $assets->pluck('id'))->get());
                break;
            case 'perubahan':
            case 'data_informasi':
            case 'keamanan':
                $add('aset', 'aset', [$row->asset]);
                $add('risiko asal (tindak lanjut otomatis)', 'risiko', $originRisk());
                break;
            case 'tiket':
                $add('layanan', 'layanan', [$row->service]);
                if ($row->service) {
                    $add('aset layanan', 'aset', $row->service->assets()->get());
                }
                $code = data_get($row->dynamic_data, 'auto_generated_from_risk') ?? $row->related_risk_text;
                if ($code && preg_match('/^[A-Z]{2,5}-[A-Z0-9]{2,6}-\d{3,6}$/', trim($code))) {
                    $add('risiko terkait', 'risiko', Risk::query()->where('risk_code', trim($code))->get());
                }
                break;
        }

        // Kode Personil: orang yang sama di modul lain.
        $ref = $this->hasColumn($key, 'personnel_ref') ? $row->personnel_ref : null;
        if ($ref) {
            $others = ['sdm' => 'risiko SDM', 'keahlian' => 'profil keahlian', 'risiko' => 'risiko', 'aset' => 'aset', 'perubahan' => 'perubahan',
                'data_informasi' => 'data & informasi', 'keamanan' => 'program keamanan', 'pengetahuan' => 'dokumen pengetahuan', 'layanan' => 'layanan'];
            foreach ($others as $module => $label) {
                if ($module !== $key && $this->hasColumn($module, 'personnel_ref')) {
                    $add("{$label} dengan Kode Personil {$ref}", $module, self::MODULES[$module]['model']::query()->where('personnel_ref', $ref)->limit(20)->get());
                }
            }
        }

        $item = $this->describe($key, $row);
        if ($groups === []) {
            $answer = "{$item['title']} belum terhubung ke data lain.";
            if (self::MODULES[$key]['link'] === 'asset_id') {
                $answer .= ' Isi Aset Terkait supaya tampil di Riwayat Terkait aset.';
            }

            return ['answer' => $answer, 'engine' => 'Titik integrasi dari basis data', 'links' => $this->connectionLinks(collect([$item]))];
        }

        $lines = collect($groups)->map(fn ($g) => $g[1]->count() === 1
            ? '• ' . Str::ucfirst($g[0]) . ': ' . $g[1][0]['title']
            : '• ' . $g[1]->count() . ' ' . $g[0]);
        $links = collect($groups)->flatMap(fn ($g) => $g[1]);

        return [
            'answer' => "{$item['title']} terhubung ke:\n" . $lines->implode("\n"),
            'engine' => 'Titik integrasi dari basis data',
            'links' => $this->connectionLinks($links, 12),
        ];
    }

    private function moduleConnections(string $mapKey, string $text): ?array
    {
        $map = IntegrationMap::for($mapKey);
        if ($map === null) {
            return null;
        }
        // Asisten dan Evaluasi Bukti Dukung berlaku untuk semua modul; tidak perlu diulang di sini.
        $flows = collect($map['flows'])->reject(fn ($f) => in_array($f['to'], ['Asisten', 'Evaluasi Bukti Dukung'], true))->values();

        $focus = null;
        if (preg_match('/\b(arsip|diarsipkan|mengarsipkan|hapus|dihapus|menghapus)\b/u', $text)) {
            if (empty($map['warning'])) {
                return null; // biar panduan arsip/pulihkan yang menjawab
            }
            $flows = collect();
            $focus = 'arsip';
        } elseif (preg_match('/\b(kritis|tinggi)\b/u', $text)) {
            // "Kalau risiko jadi kritis …": semua akibat otomatis (tindak lanjut, tiket, analisis AI).
            $focus = fn ($f) => $f['type'] === 'auto';
        } elseif (preg_match('/\b(impor|diimpor|excel)\b/u', $text)) {
            $focus = fn ($f) => (bool) preg_match('/impor|excel/iu', $f['to'] . ' ' . $f['text']);
        } elseif (preg_match('/\b(unggah|diunggah|mengunggah|pdf|sop|dokumen)\b/u', $text)) {
            $focus = fn ($f) => (bool) preg_match('/pdf|diunggah|dokumen/iu', $f['text']);
        }
        if ($focus instanceof \Closure) {
            $picked = $flows->filter($focus)->values();
            $flows = $picked->isNotEmpty() ? $picked : $flows;
            $focus = $picked->isNotEmpty() ? 'skenario' : null;
        }

        $lines = $flows->map(fn ($f) => '• ' . Str::ucfirst($f['to']) . ' (' . ($f['type'] === 'auto' ? 'otomatis' : 'diisi manual') . '): ' . $f['text']);
        if (! empty($map['warning']) && ($focus === null || $focus === 'arsip')) {
            $lines->push(($focus === 'arsip' ? '' : 'Perhatian: ') . $map['warning']);
        }
        $head = $focus === null ? $map['label'] . ' terhubung lewat ' . implode(' dan ', $map['keys']) . '. ' . $map['intro'] : null;

        return [
            'answer' => trim(($head ? $head . "\n" : '') . $lines->implode("\n")),
            'engine' => 'Titik integrasi aplikasi',
            'links' => [['kind' => 'page', 'title' => 'Buka ' . $map['label'], 'meta' => null, 'url' => route(AppHelp::MODULE_ROUTES[$mapKey])]],
        ];
    }

    private function connectionLinks(Collection $links, int $take = 8): array
    {
        return $links->filter(fn ($l) => ! empty($l['url']))->unique('url')->take($take)
            ->map(fn ($l) => ['kind' => $l['kind'], 'title' => $l['title'], 'meta' => $l['meta'] ?? null, 'url' => $l['url']])
            ->values()->all();
    }
}
