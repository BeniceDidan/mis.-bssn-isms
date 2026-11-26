<?php

namespace App\Services\Ai\Concerns;

use App\Models\Asset;
use App\Models\Change;
use App\Models\DataInformation;
use App\Models\EvidenceDocument;
use App\Models\KnowledgeAsset;
use App\Models\Risk;
use App\Models\SecurityProgram;
use App\Models\ServiceTicket;
use App\Support\AdminModules;
use App\Support\AppFaq;
use Illuminate\Support\Facades\DB;

/**
 * Asisten sebagai teman bicara dan penunjuk jalan. Masukan pengguna 26 Nov
 * 2026: "halo" ditolak, "semuanya" dan "iya kasih ke aku" tidak nyambung ke
 * pertanyaan sebelumnya, "dokumen di manajemen aset" dijawab dengan nama
 * isian formulir, dan pengguna ingin dipandu sampai ke halaman yang terkait.
 *
 * Semua jawaban di sini disusun kode dari data aplikasi, tanpa LLM.
 * Tombol "actions" mengarah ke halaman; "tunjuk" di alamatnya membuat
 * halaman tujuan menyorot tombol yang perlu diklik (resources/js/point.js).
 */
trait GuidesConversation
{
    /**
     * Halaman yang bisa dituju. Urutan penting: yang lebih spesifik dicek dulu.
     * where: letaknya di aplikasi; about: isinya dalam satu-dua kalimat.
     */
    private const PAGES = [
        'tanya' => ['words' => ['tanya dokumen'], 'label' => 'Tanya Dokumen', 'route' => 'knowledge.index', 'params' => ['tab' => 'tanya'],
            'where' => 'di Manajemen Pengetahuan, tab Tanya Dokumen',
            'about' => 'Di sini Anda bisa bertanya tentang isi SOP dan panduan. Jawabannya selalu menyebut dokumen dan halamannya.'],
        'tiket' => ['words' => ['tiket', 'helpdesk', 'aduan'], 'label' => 'Tiket Helpdesk', 'route' => 'services.index', 'params' => ['tab' => 'helpdesk'],
            'where' => 'di Manajemen Layanan, tab Tiket Helpdesk',
            'about' => 'Di sini keluhan dan gangguan dicatat sebagai tiket. Klik Tiket Baru untuk membuat tiket.'],
        'keahlian' => ['words' => ['peta keahlian', 'keahlian'], 'label' => 'Peta Keahlian', 'route' => 'knowledge.index', 'params' => ['tab' => 'keahlian'],
            'where' => 'di Manajemen Pengetahuan, tab Peta Keahlian',
            'about' => 'Berisi daftar pegawai beserta keahliannya, supaya tahu harus bertanya ke siapa.'],
        'bukti' => ['words' => ['bukti dukung', 'evaluasi bukti'], 'label' => 'Evaluasi Bukti Dukung', 'route' => 'evidence.index', 'params' => [],
            'where' => 'di menu samping',
            'about' => 'Di sini PDF bukti dukung diunggah lalu diperiksa: level yang diklaim, nilai yang didapat, dan unsur yang belum ada.'],
        'panduan' => ['words' => ['panduan', 'petunjuk'], 'label' => 'Panduan', 'route' => 'guide.index', 'params' => [],
            'where' => 'di menu nama Anda, pojok kanan atas',
            'about' => 'Berisi urutan kerja dan alur data semua modul.'],
        'profil' => ['words' => ['profil', 'akun saya'], 'label' => 'Profil', 'route' => 'profile.edit', 'params' => [],
            'where' => 'di menu nama Anda, pojok kanan atas',
            'about' => 'Di sini Anda bisa mengganti nama dan kata sandi.'],
        'dashboard' => ['words' => ['dashboard', 'beranda', 'halaman utama', 'home'], 'label' => 'Dashboard', 'route' => 'dashboard', 'params' => [],
            'where' => 'di menu samping paling atas',
            'about' => 'Berisi ringkasan delapan modul. Klik salah satu kotak untuk membuka modulnya.'],
        'sdm' => ['words' => ['manajemen sdm', 'risiko sdm', 'sdm', 'pihak ketiga'], 'label' => 'Manajemen SDM', 'route' => 'hr-risks.index', 'params' => [],
            'where' => 'di menu samping',
            'about' => 'Mencatat orang yang mengurus teknologi dan bahaya yang bisa muncul darinya, misalnya hanya satu orang yang bisa menjalankan server.'],
        'pengetahuan' => ['words' => ['manajemen pengetahuan', 'pengetahuan'], 'label' => 'Manajemen Pengetahuan', 'route' => 'knowledge.index', 'params' => [],
            'where' => 'di menu samping',
            'about' => 'Menyimpan SOP, panduan, dan PDF lain supaya ilmu tidak hilang saat pegawai pindah atau pensiun.'],
        'keamanan' => ['words' => ['keamanan informasi', 'program keamanan', 'keamanan'], 'label' => 'Manajemen Keamanan Informasi', 'route' => 'security-programs.index', 'params' => [],
            'where' => 'di menu samping',
            'about' => 'Berisi program kerja supaya data dan layanan kantor tetap aman.'],
        'data_informasi' => ['words' => ['data dan informasi', 'data informasi'], 'label' => 'Manajemen Data & Informasi', 'route' => 'data-information.index', 'params' => [],
            'where' => 'di menu samping',
            'about' => 'Mencatat masalah pada data, supaya data tetap benar dan aman.'],
        'perubahan' => ['words' => ['perubahan'], 'label' => 'Manajemen Perubahan', 'route' => 'changes.index', 'params' => [],
            'where' => 'di menu samping',
            'about' => 'Mencatat setiap perubahan, supaya tidak asal diubah dan layanan tidak terganggu.'],
        'risiko' => ['words' => ['risiko'], 'label' => 'Manajemen Risiko', 'route' => 'risks.index', 'params' => [],
            'where' => 'di menu samping',
            'about' => 'Mencatat hal buruk yang mungkin terjadi dan cara menanganinya. Levelnya dihitung otomatis.'],
        'layanan' => ['words' => ['layanan'], 'label' => 'Manajemen Layanan', 'route' => 'services.index', 'params' => [],
            'where' => 'di menu samping',
            'about' => 'Berisi daftar layanan kantor beserta tiket keluhan dan gangguannya.'],
        'aset' => ['words' => ['aset', 'inventaris', 'perangkat'], 'label' => 'Manajemen Aset', 'route' => 'assets.index', 'params' => [],
            'where' => 'di menu samping',
            'about' => 'Daftar perangkat, aplikasi, dan data milik kantor. Modul lain menunjuk ke aset di sini.'],
    ];

    /** Tombol tambah di kanan atas tiap halaman (label disalin dari tampilan). */
    private const ADD_BUTTONS = [
        'aset' => 'Aset Baru', 'risiko' => 'Risiko Baru', 'perubahan' => 'Perubahan Baru', 'data_informasi' => 'Catatan Baru',
        'sdm' => 'Risiko Baru', 'pengetahuan' => 'Tambah Baru', 'keamanan' => 'Program Baru', 'layanan' => 'Layanan Baru', 'tiket' => 'Tiket Baru',
    ];

    /** Sebutan data tiap halaman, untuk kalimat "Cara menambah …" dan hitungan. */
    private const NOUNS = [
        'aset' => 'aset', 'risiko' => 'risiko', 'perubahan' => 'catatan perubahan', 'data_informasi' => 'catatan data', 'sdm' => 'risiko SDM',
        'pengetahuan' => 'dokumen pengetahuan', 'keamanan' => 'program keamanan', 'layanan' => 'layanan', 'tiket' => 'tiket',
    ];

    /** Hal yang perlu diingat setelah menambah data (dari aturan aplikasi). */
    private const ADD_NOTES = [
        'aset' => 'Isi nilai Kerahasiaan, Integritas, dan Ketersediaan supaya tingkat pentingnya aset terhitung otomatis.',
        'risiko' => 'Levelnya dihitung otomatis. Kalau hasilnya Tinggi atau Kritis, modul Keamanan Informasi, Perubahan, Data & Informasi, dan Layanan langsung mendapat tindak lanjut.',
        'pengetahuan' => 'Lampirkan PDF-nya supaya isinya bisa ditanya di Tanya Dokumen.',
        'tiket' => 'Sebelum menyimpan, klik Analisis AI untuk melihat saran kategori dan prioritas.',
        'perubahan' => 'Pilih Aset Terkait supaya perubahan ini tercatat di riwayat aset itu.',
        'data_informasi' => 'Pilih Aset Terkait supaya catatan ini tercatat di riwayat aset itu.',
        'keamanan' => 'Pilih Aset Terkait supaya program ini tercatat di riwayat aset itu.',
    ];

    /** Halaman yang bisa langsung membuka formulir lewat ?create=1. */
    private const CREATE_PARAM = ['aset', 'risiko', 'perubahan', 'data_informasi', 'sdm', 'pengetahuan', 'keamanan', 'layanan'];

    private const CAPABILITIES = "Yang bisa saya bantu:\n"
        . "• Menjelaskan cara memakai aplikasi, misalnya cara menambah aset.\n"
        . "• Mencarikan data, misalnya risiko kritis atau tiket yang masih terbuka.\n"
        . "• Mencarikan dokumen dan menjawab dari isinya, misalnya SOP server.\n"
        . "• Mengantar Anda ke halaman yang dituju dan menunjukkan tombolnya.\n\n"
        . 'Silakan tulis pertanyaannya, atau pilih salah satu contoh di bawah.';

    private const STARTERS = ['Cara menambah aset', 'Berapa risiko kritis?', 'Dokumen di Manajemen Layanan', 'Bawa saya ke Tiket Helpdesk'];

    /** Kata yang boleh ada di jawaban "iya / semuanya / kasih ke saya" (setelah normalize). */
    private const YES_WORDS = [
        'iya', 'iyaa', 'ya', 'yaa', 'yap', 'yup', 'yes', 'boleh', 'mau', 'lanjut', 'lanjutkan', 'semua', 'semuanya', 'seluruhnya',
        'tampilkan', 'lihat', 'kasih', 'berikan', 'kirim', 'kirimkan', 'ke', 'saya', 'untuk', 'dong', 'deh', 'saja', 'sekarang',
        'yang', 'lain', 'lainnya', 'selengkapnya', 'lengkapnya', 'lengkap', 'tolong', 'please', 'itu', 'sih', 'juga', 'lagi', 'dan', 'sama',
    ];

    /** Apakah penanya ingin semua hasil, bukan enam teratas. */
    private bool $wantAll = false;

    /** Pertanyaan yang sudah dipahami, dikembalikan sebagai 'topic' untuk riwayat berikutnya. */
    private ?string $resolved = null;

    /** Sapaan, terima kasih, "kamu bisa apa?", "oke": dijawab ramah, bukan ditolak. */
    private function smallTalk(string $flat): ?array
    {
        $words = count(preg_split('/\s+/u', $flat, -1, PREG_SPLIT_NO_EMPTY));

        if ($words <= 5 && preg_match('/^(halo+|hallo|hai+|hi|hello|hey|hei|pagi|siang|sore|malam|selamat (pagi|siang|sore|malam)|assalamualaikum|assalamu alaikum|permisi|punten)\b/u', $flat, $m)) {
            $greet = preg_match('/^selamat (pagi|siang|sore|malam)/u', $flat, $time) ? 'Selamat ' . $time[1] . '!' : 'Halo!';

            return $this->chat("{$greet} Saya Asisten BSSN ISMS.\n\n" . self::CAPABILITIES, self::STARTERS);
        }
        if ($words <= 6 && preg_match('/\b(terima kasih|terimakasih|makasih|makasi|thanks|thank you|thx|tq|mksh|suwun|nuhun)\b/u', $flat)) {
            return $this->chat('Sama-sama. Kalau ada yang ingin ditanyakan lagi, tulis saja di sini.');
        }
        if (preg_match('/\b(bisa apa|bisa bantu apa|apa yang bisa (kamu|anda|asisten)|apa saja yang bisa (kamu|anda|asisten)|kamu siapa|siapa kamu|anda siapa|siapa anda|kamu itu apa|kemampuan (kamu|anda|asisten))\b/u', $flat)
            || preg_match('/^(bantuan|help|tolong|bantu saya|tolong bantu|tolong bantu saya)$/u', $flat)) {
            return $this->chat("Saya Asisten BSSN ISMS.\n\n" . self::CAPABILITIES, self::STARTERS);
        }
        if ($words <= 3 && preg_match('/^(ok|oke|okay|okey|okee|sip|siap|baik|baiklah|mantap|noted|paham|mengerti|oh begitu|oh gitu|oh oke|oh|wah|keren|bagus)\b/u', $flat)) {
            return $this->chat('Baik. Ada lagi yang bisa saya bantu?', self::STARTERS);
        }

        return null;
    }

    /** "iya", "semuanya", "iya kasih ke aku", "tampilkan semua": jawaban atas tawaran sebelumnya. */
    private static function isContinuation(string $flat): bool
    {
        $words = preg_split('/\s+/u', $flat, -1, PREG_SPLIT_NO_EMPTY);

        return $words !== [] && count($words) <= 6 && array_diff($words, self::YES_WORDS) === [];
    }

    /** Pertanyaan terbaru yang punya pokok bahasan (modul, halaman, atau dokumen). */
    private function topic(array $history): ?string
    {
        foreach (array_reverse($history) as $turn) {
            $q = self::normalize($turn['q'] ?? '');
            if ($this->detectModule($q) !== null || $this->pageKey($q) !== null || preg_match('/\b(berkas|dokumen|pdf|sop)\b/u', $q)) {
                return $q;
            }
        }

        return null;
    }

    /** "bawa saya ke risiko", "buka halaman layanan", "buka halamannya", "ke halaman aset". */
    private static function wantsNavigation(string $flat): bool
    {
        if (preg_match('/\b(bawa|antar|antarkan|arahkan|pergi|pindah|menuju)\b/u', $flat)) {
            return true;
        }
        if (preg_match('/\bbuka\b.*\b(halaman|menu|modul|laman|tab)|\bbuka (sana|situ)\b|\bke (halaman|menu)\b/u', $flat)) {
            return true;
        }

        // "buka layanan", "buka tanya dokumen", bukan "buka berkas SOP server".
        return (bool) preg_match('/^buka\s+\S/u', $flat)
            && ! preg_match('/\b(berkas|pdf|file|lampiran|sop)\b/u', $flat)
            && ! preg_match('/\bdokumen\b/u', str_replace('tanya dokumen', '', $flat));
    }

    /** "Tanya Dokumen di mana?", "menu tiket ada di mana" (tanpa saringan data). */
    private function asksWhere(string $flat): bool
    {
        return (bool) preg_match('/\b(di mana|letak|letaknya)\b/u', $flat)
            && ! preg_match('/\b(kritis|tinggi|sedang|rendah|p[1-4]|berapa|cara|bagaimana|siapa|kapan)\b/u', $flat)
            && ! $this->wantsToAdd($flat);
    }

    private function pageKey(string $text): ?string
    {
        foreach (self::PAGES as $key => $page) {
            foreach ($page['words'] as $word) {
                if (preg_match('/(?<![\p{L}\p{N}])' . preg_quote($word, '/') . '(?![\p{L}\p{N}])/u', $text)) {
                    return $key;
                }
            }
        }

        return null;
    }

    private static function pageUrl(string $key, array $extra = []): string
    {
        $page = self::PAGES[$key];

        return route($page['route'], $page['params'] + $extra);
    }

    private function navigationAnswer(string $key, bool $go): array
    {
        $page = self::PAGES[$key];
        $url = self::pageUrl($key);
        $text = $go
            ? "Baik, saya bukakan halaman {$page['label']}."
            : "{$page['label']} ada {$page['where']}. Klik tombol di bawah untuk membukanya.";
        $text .= "\n\n" . $page['about'];

        $actions = [['label' => 'Buka ' . $page['label'], 'url' => $url]];
        if (isset(self::ADD_BUTTONS[$key])) {
            $actions[] = ['label' => 'Tunjukkan tombol ' . self::ADD_BUTTONS[$key], 'url' => self::pageUrl($key, ['tunjuk' => 'add-button']), 'point' => true];
        }

        return ['answer' => $text, 'engine' => 'Asisten', 'links' => [], 'actions' => $actions, 'go' => $go ? $url : null];
    }

    /** "saya mau lapor gangguan website", "mau melaporkan insiden". */
    private static function wantsReport(string $flat): bool
    {
        return (bool) preg_match('/\b(lapor|melapor|laporkan|melaporkan|mengadu|mengadukan|komplain|keluhkan|mengeluhkan)\b/u', $flat)
            && ! preg_match('/\b(berapa|daftar|siapa|kapan|tampilkan)\b/u', $flat);
    }

    private function reportAnswer(string $flat): array
    {
        $text = "Gangguan atau keluhan dicatat sebagai tiket helpdesk:\n"
            . "1. Buka Manajemen Layanan, lalu tab Tiket Helpdesk.\n"
            . "2. Klik Tiket Baru.\n"
            . "3. Pilih layanan yang terganggu, lalu tulis masalahnya.\n"
            . "4. Klik Analisis AI untuk melihat saran kategori, prioritas, dan langkah awal.\n"
            . '5. Periksa isinya, lalu klik Simpan.';

        $links = [];
        if (preg_match('/\b(diretas|retas|peretasan|hack|judi|virus|malware|serangan|insiden|bocor|kebocoran|phishing|disusupi|deface|ransomware|siber)\b/u', $flat)) {
            $doc = KnowledgeAsset::query()->where('title', 'ilike', '%insiden%')->orderBy('title')->first();
            if ($doc) {
                $text .= "\n\nKarena ini menyangkut keamanan, ikuti juga langkah di {$doc->title}.";
                $links = $this->links(collect([$this->describe('pengetahuan', $doc)]), collect(), collect());
            }
        }

        return [
            'answer' => $text,
            'engine' => 'Panduan aplikasi',
            'links' => $links,
            'actions' => [
                ['label' => 'Buat tiket sekarang', 'url' => self::pageUrl('tiket', ['tunjuk' => 'add-button']), 'point' => true],
            ],
        ];
    }

    private function wantsToAdd(string $flat): bool
    {
        return (bool) preg_match('/\b(tambah|menambah|menambahkan|tambahkan|buat|membuat|bikin|input|menginput|masukkan|memasukkan|catat|mencatat|daftarkan|mendaftarkan|mengisi)\b/u', $flat);
    }

    /** "cara menambah aset", "di mana saya menambah aset?", "saya mau mencatat risiko baru". */
    private function howToAddAnswer(string $flat): ?array
    {
        if (! $this->wantsToAdd($flat) || ! preg_match('/\b(cara|caranya|bagaimana|di mana|mau|ingin|pengen|bisa|tombol|langkah)\b/u', $flat)) {
            return null;
        }
        // "tiket baru" lebih spesifik daripada "layanan"; selebihnya urutan PAGES.
        $key = $this->pageKey($flat);
        if ($key === null || ! isset(self::ADD_BUTTONS[$key])) {
            return null;
        }

        $page = self::PAGES[$key];
        $open = $key === 'tiket' ? 'Buka Manajemen Layanan, lalu tab Tiket Helpdesk.' : "Buka {$page['label']}.";
        $text = 'Cara menambah ' . self::NOUNS[$key] . ":\n1. {$open}\n2. Klik tombol " . self::ADD_BUTTONS[$key] . " di kanan atas.\n3. Isi formulirnya, lalu klik Simpan.";
        if (isset(self::ADD_NOTES[$key])) {
            $text .= "\n\n" . self::ADD_NOTES[$key];
        }
        $text .= "\n\nKlik Tunjukkan tombolnya, saya antar ke halamannya dan tunjukkan tombol yang perlu diklik.";

        $actions = [['label' => 'Tunjukkan tombolnya', 'url' => self::pageUrl($key, ['tunjuk' => 'add-button']), 'point' => true]];
        if (in_array($key, self::CREATE_PARAM, true)) {
            $actions[] = ['label' => 'Langsung isi formulir', 'url' => self::pageUrl($key, ['create' => 1])];
        }

        return ['answer' => $text, 'engine' => 'Panduan aplikasi', 'links' => [], 'actions' => $actions];
    }

    /**
     * "Dokumen di Manajemen Layanan": modul tidak menyimpan PDF sendiri. PDF
     * ada di Manajemen Pengetahuan (menempel ke aset) dan Evaluasi Bukti Dukung
     * (per modul), jadi keduanya dikumpulkan untuk modul itu.
     */
    private function moduleDocumentsAnswer(string $moduleKey): array
    {
        $scope = self::ADMIN_SCOPE[$moduleKey] ?? $moduleKey;
        $label = AdminModules::LABELS[$scope] ?? 'modul ini';

        $evidence = $scope === 'pengetahuan' ? collect() : EvidenceDocument::query()->where('module_key', $scope)->latest()->get();
        $knowledge = $scope === 'pengetahuan'
            ? KnowledgeAsset::query()->orderBy('title')->get()
            : (($ids = $this->moduleAssetIds($scope)) === [] ? collect() : KnowledgeAsset::query()->whereIn('asset_id', $ids)->orderBy('title')->get());

        $total = $evidence->count() + $knowledge->count();
        $pageKey = $scope === 'data_informasi' ? 'data_informasi' : $scope;
        $actions = [['label' => 'Buka ' . $label, 'url' => self::pageUrl($pageKey)]];
        $count = $this->moduleCountSentence($scope);

        if ($total === 0) {
            $text = "Belum ada dokumen yang berhubungan dengan {$label}.\n\n"
                . 'Dokumen seperti SOP bisa ditambahkan di Manajemen Pengetahuan, tab Aset Pengetahuan. Pilih Aset Terkait supaya dokumennya tersambung ke modul yang memakai aset itu.'
                . ($count ? "\n\n{$count}" : '');
            $actions[] = ['label' => 'Tambah dokumen', 'url' => self::pageUrl('pengetahuan', ['tunjuk' => 'add-button']), 'point' => true];

            return ['answer' => $text, 'engine' => 'Pencarian langsung di basis data', 'links' => [], 'actions' => $actions];
        }

        if ($scope === 'pengetahuan') {
            $text = "Ada {$total} dokumen di Manajemen Pengetahuan. Klik nama dokumen di bawah untuk membuka PDF-nya.";
        } else {
            $parts = [];
            if ($knowledge->isNotEmpty()) {
                $parts[] = $scope === 'aset'
                    ? "• {$knowledge->count()} dokumen pengetahuan (SOP dan panduan) yang tersambung ke aset"
                    : "• {$knowledge->count()} dokumen pengetahuan (SOP dan panduan) untuk aset yang dipakai di {$label}";
            }
            if ($evidence->isNotEmpty()) {
                $parts[] = "• {$evidence->count()} bukti dukung yang dinilai untuk {$label}";
            }
            $text = "{$label} tidak menyimpan PDF sendiri. PDF disimpan di Manajemen Pengetahuan dan Evaluasi Bukti Dukung.\n\n"
                . "Yang berhubungan dengan {$label} ada {$total} dokumen:\n" . implode("\n", $parts)
                . "\n\nKlik nama dokumen di bawah untuk membukanya." . ($count ? "\n\n{$count}" : '');
        }

        $rows = $knowledge->map(fn ($doc) => $this->describe('pengetahuan', $doc))
            ->concat($evidence->map(fn ($doc) => $this->describe('bukti', $doc)));

        return [
            'answer' => $text,
            'engine' => 'Pencarian langsung di basis data',
            'links' => $this->links($rows, collect(), collect(), 30),
            'actions' => $actions,
            'expand' => true,
        ];
    }

    /** @return array<int, int> aset yang dipakai data di modul itu */
    private function moduleAssetIds(string $scope): array
    {
        $ids = match ($scope) {
            'aset' => Asset::query()->pluck('id'),
            'layanan' => DB::table('service_assets')->pluck('asset_id'),
            'risiko' => Risk::query()->whereNotNull('asset_id')->pluck('asset_id'),
            'perubahan' => Change::query()->whereNotNull('asset_id')->pluck('asset_id'),
            'data_informasi' => DataInformation::query()->whereNotNull('asset_id')->pluck('asset_id'),
            'keamanan' => SecurityProgram::query()->whereNotNull('asset_id')->pluck('asset_id'),
            default => collect(),
        };

        return $ids->unique()->values()->all();
    }

    /** "Datanya sendiri: 5 layanan dan 14 tiket, ada di halaman Manajemen Layanan." */
    private function moduleCountSentence(string $scope): ?string
    {
        $model = collect(AdminModules::MODELS[$scope] ?? [])->first();
        if (! $model || ! isset(self::NOUNS[$scope])) {
            return null;
        }
        $what = $model::query()->count() . ' ' . self::NOUNS[$scope];
        if ($scope === 'layanan') {
            $what .= ' dan ' . ServiceTicket::query()->count() . ' tiket';
        }

        return 'Datanya sendiri (' . $what . ') ada di halaman ' . AdminModules::LABELS[$scope] . '.';
    }

    /** Penjelasan baku untuk hal yang sering membingungkan (App\Support\AppFaq). */
    private function faqAnswer(string $flat): ?array
    {
        $faq = AppFaq::find($flat);
        // "Kalau aset diarsipkan apa yang terjadi?" dijawab dari peta integrasi, yang lebih rinci.
        if ($faq === null || ($faq['id'] === 'arsip' && self::wantsConnection($flat))) {
            return null;
        }
        $text = $faq['answer'];
        if ($faq['id'] === 'verifikasi') {
            $text .= "\n\n" . $this->pendingSummary();
        }

        return ['answer' => $text, 'engine' => 'Panduan aplikasi', 'links' => [], 'actions' => $faq['actions']];
    }

    /** "Apa fungsi manajemen perubahan?", "Tanya Dokumen itu apa?": penjelasan seperti di slide. */
    private function moduleExplainAnswer(string $flat): ?array
    {
        if (! preg_match('/\b(apa itu|itu apa|apa fungsi|fungsi|fungsinya|kegunaan|kegunaannya|gunanya|tujuan|tujuannya|untuk apa|jelaskan|penjelasan|maksud|isinya apa|isinya|tentang)\b/u', $flat)) {
            return null;
        }
        $rest = str_replace('tanya dokumen', '', $flat);
        if (preg_match('/\b(berapa|daftar|tampilkan|cari|carikan|cara|caranya|tambah|menambah|kritis|tinggi|sedang|rendah|kode|riwayat|inheren|sisa|p[1-4]|dokumen|berkas|pdf|alur data|dasar|aturan|regulasi|pasal)\b/u', $rest)) {
            return null;
        }
        $key = $this->pageKey($flat);
        $text = $key !== null ? AppFaq::explain($key) : null;
        if ($text === null) {
            return null;
        }
        $this->resolved = $flat;

        $actions = [['label' => 'Buka ' . self::PAGES[$key]['label'], 'url' => self::pageUrl($key)]];
        if (isset(self::ADD_BUTTONS[$key])) {
            $actions[] = ['label' => 'Tunjukkan tombol ' . self::ADD_BUTTONS[$key], 'url' => self::pageUrl($key, ['tunjuk' => 'add-button']), 'point' => true];
        } elseif ($key === 'bukti') {
            $actions[] = ['label' => 'Tunjukkan tombol Unggah', 'url' => self::pageUrl($key, ['tunjuk' => 'evidence-upload-button']), 'point' => true];
        }

        return ['answer' => $text, 'engine' => 'Panduan aplikasi', 'links' => [], 'actions' => $actions];
    }

    /** Jawaban percakapan biasa, dengan contoh pertanyaan yang bisa diklik. */
    private function chat(string $text, array $suggestions = []): array
    {
        return ['answer' => $text, 'engine' => 'Asisten', 'links' => [], 'suggestions' => $suggestions];
    }

    /** Tombol ke halaman modul untuk jawaban data ("Buka Manajemen Risiko"). */
    private function moduleAction(?string $moduleKey): array
    {
        $key = match ($moduleKey) {
            null => null,
            'keahlian' => 'keahlian',
            default => isset(self::PAGES[$moduleKey]) ? $moduleKey : null,
        };

        return $key === null ? [] : [['label' => 'Buka ' . self::PAGES[$key]['label'], 'url' => self::pageUrl($key)]];
    }

    private static function wantsAll(string $flat): bool
    {
        return (bool) preg_match('/\b(semua|semuanya|seluruhnya|selengkapnya|lengkapnya|lainnya|yang lain)\b/u', $flat);
    }
}
