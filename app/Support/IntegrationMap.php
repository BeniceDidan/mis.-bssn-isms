<?php

namespace App\Support;

/**
 * Jawaban "data modul ini dikirim ke mana dan datang dari mana?", tampil di
 * pop-out "Alur Data" pada 8 halaman modul (components/integration-map),
 * di halaman Panduan, dan dipakai Asisten (Concerns\AnswersConnections,
 * AppHelp).
 *
 * Dasar resmi (docs/riset/06-alur-dan-bahasa.md): Permenkomdigi 6/2025
 * Pasal 9 ayat (5) mewajibkan rancang bangun aplikasi SPBE memuat alur
 * proses, keterhubungan fungsi dan basis data, serta rancangan integrasi;
 * Perpres 95/2018 Pasal 2 (prinsip keterpaduan dan interoperabilitas) dan
 * Pasal 47–54 (tujuan dan proses tiap manajemen). Rujukan tidak ditulis di
 * layar; Asisten menjawabnya bila ditanya.
 *
 * Setiap kalimat mencerminkan perilaku kode:
 *   - tautan asset_id + jejak di aset    -> App\Traits\BridgesToAsset
 *   - layanan ↔ banyak aset              -> App\Models\Service (service_assets)
 *   - Kode Personil sama persis          -> App\Livewire\Concerns\GeneratesPersonnelRef
 *   - tindak lanjut risiko Tinggi/Kritis -> App\Services\RiskEscalationResponseService
 *   - level risiko naik satu tingkat     -> App\Services\RiskLevelService::derive(),
 *                                           dipicu Asset::booted() dan HrRisk::booted()
 *   - angka di Keamanan Informasi        -> Livewire\SecurityPrograms\SecurityProgramStats
 * Kalau salah satunya berubah, ubah juga kalimat di sini.
 *
 * flows[]:
 *   dir  'out' = dikirim ke modul lain, 'in' = datang dari modul lain
 *   type 'auto' = terjadi sendiri, 'manual' = hanya bila kolom penghubung diisi
 *   to   nama modul/fitur di seberang (tujuan untuk 'out', asal untuk 'in')
 */
class IntegrationMap
{
    public const MAP = [
        'sdm' => [
            'label' => 'Manajemen SDM',
            'keys' => ['Kode Personil'],
            'intro' => 'Mencatat risiko pada pegawai dan pihak ketiga pengelola TIK, misalnya hanya satu orang yang menguasai server. Modul ini tidak punya kolom Aset Terkait; penghubungnya Kode Personil.',
            'flows' => [
                [
                    'dir' => 'out',
                    'type' => 'auto',
                    'to' => 'Manajemen Risiko',
                    'text' => 'Kalau risiko seseorang di sini Tinggi atau Kritis, level setiap risiko di Manajemen Risiko yang memakai Kode Personil yang sama naik satu tingkat. Catatan alasannya muncul di aktivitas risiko itu.',
                ],
                [
                    'dir' => 'out',
                    'type' => 'manual',
                    'to' => 'Manajemen Pengetahuan',
                    'text' => 'Isi Kode Personil yang sama di Peta Keahlian supaya terlihat siapa ahlinya sekaligus apa risikonya.',
                ],
                [
                    'dir' => 'out',
                    'type' => 'manual',
                    'to' => 'Aset, Perubahan, Layanan, Keamanan Informasi, Data & Informasi',
                    'text' => 'Modul-modul itu juga punya kolom Kode Personil. Kode yang sama berarti orang atau pihak ketiga yang sama.',
                ],
            ],
            'warning' => 'Sistem tidak mencocokkan nama. "Budi Santoso" di dua modul tetap dianggap dua orang sampai Kode Personilnya sama persis.',
        ],

        'pengetahuan' => [
            'label' => 'Manajemen Pengetahuan',
            'keys' => ['Kode Aset', 'Kode Personil'],
            'intro' => 'Menyimpan SOP, manual, dan dokumentasi, mencatat siapa ahli di bidang apa, kegiatan berbagi pengetahuan, dan risiko hilangnya pengetahuan. Ada 4 tab; Aset Pengetahuan memakai Kode Aset, Peta Keahlian memakai Kode Personil.',
            'flows' => [
                [
                    'dir' => 'out',
                    'type' => 'manual',
                    'to' => 'Manajemen Aset',
                    'text' => 'Pilih Aset Terkait di Aset Pengetahuan. Dokumen itu lalu muncul di Riwayat Terkait aset tersebut.',
                ],
                [
                    'dir' => 'out',
                    'type' => 'manual',
                    'to' => 'Manajemen SDM',
                    'text' => 'Peta Keahlian tersambung ke risiko SDM orang yang sama lewat Kode Personil.',
                ],
                [
                    'dir' => 'out',
                    'type' => 'auto',
                    'to' => 'Manajemen Keamanan Informasi',
                    'text' => 'Jumlah Aktivitas Berbagi ikut dihitung di sana sebagai kegiatan sosialisasi dan pelatihan. Tidak perlu dilaporkan ulang.',
                ],
            ],
        ],

        'aset' => [
            'label' => 'Manajemen Aset',
            'keys' => ['Kode Aset'],
            'intro' => 'Daftar semua aset TIK. Enam modul lain menautkan datanya ke aset di sini, jadi daftarkan aset lebih dulu.',
            'flows' => [
                [
                    'dir' => 'in',
                    'type' => 'auto',
                    'to' => 'Risiko, Perubahan, Data & Informasi, Pengetahuan, Keamanan Informasi, Layanan',
                    'text' => 'Setiap data di modul-modul itu yang memilih sebuah aset ikut muncul di Riwayat Terkait aset tersebut, dan asetnya ditandai baru diperbarui.',
                ],
                [
                    'dir' => 'out',
                    'type' => 'auto',
                    'to' => 'Manajemen Risiko',
                    'text' => 'Kalau kritikalitas aset menjadi Tinggi atau Kritis, level risiko yang tertaut ke aset itu naik satu tingkat.',
                ],
                [
                    'dir' => 'out',
                    'type' => 'manual',
                    'to' => 'Manajemen Layanan',
                    'text' => 'Satu layanan boleh memakai beberapa aset, dan satu aset boleh menopang beberapa layanan. Pilih asetnya di form layanan.',
                ],
                [
                    'dir' => 'out',
                    'type' => 'manual',
                    'to' => 'Manajemen SDM',
                    'text' => 'Isi Kode Personil penanggung jawab aset supaya tersambung ke risiko SDM orang yang sama.',
                ],
            ],
            'warning' => 'Mengarsipkan aset tidak menghapus data di modul lain; tautannya saja yang kosong. Cek Riwayat Terkait aset sebelum mengarsipkan.',
        ],

        'keamanan' => [
            'label' => 'Manajemen Keamanan Informasi',
            'keys' => ['Kode Aset', 'Kode Personil'],
            'intro' => 'Program kerja keamanan informasi beserta kegiatan dan penanggung jawabnya. Sebagian isinya datang sendiri dari modul lain.',
            'flows' => [
                [
                    'dir' => 'in',
                    'type' => 'auto',
                    'to' => 'Manajemen Risiko',
                    'text' => 'Setiap risiko yang menjadi Tinggi atau Kritis otomatis membuat satu program tindak lanjut di sini, menunggu verifikasi admin modul ini.',
                ],
                [
                    'dir' => 'in',
                    'type' => 'auto',
                    'to' => 'Aset, Risiko, Pengetahuan',
                    'text' => 'Angka aset terdaftar, risiko tercatat, dan kegiatan sosialisasi di halaman ini dihitung langsung dari modul-modul itu.',
                ],
                [
                    'dir' => 'out',
                    'type' => 'manual',
                    'to' => 'Manajemen Aset',
                    'text' => 'Pilih Aset Terkait supaya program ini muncul di Riwayat Terkait aset tersebut.',
                ],
            ],
        ],

        'risiko' => [
            'label' => 'Manajemen Risiko',
            'keys' => ['Kode Aset', 'Kode Personil'],
            'intro' => 'Daftar risiko TIK: seberapa mungkin terjadi, seberapa besar dampaknya, dan cara menanganinya. Level dihitung sistem saat disimpan.',
            'flows' => [
                [
                    'dir' => 'out',
                    'type' => 'auto',
                    'to' => 'Keamanan Informasi, Perubahan, Data & Informasi',
                    'text' => 'Kalau level risiko menjadi Tinggi atau Kritis, sistem membuat satu tindak lanjut di masing-masing modul itu. Tidak dibuat dua kali walau risikonya disimpan berulang.',
                ],
                [
                    'dir' => 'out',
                    'type' => 'auto',
                    'to' => 'Manajemen Layanan',
                    'text' => 'Kalau aset yang terkena risiko itu menopang layanan, tiket untuk layanan tersebut ikut dibuat. Aset tanpa layanan tidak menghasilkan tiket.',
                ],
                [
                    'dir' => 'in',
                    'type' => 'auto',
                    'to' => 'Aset dan SDM',
                    'text' => 'Level naik satu tingkat bila aset terkait berkritikalitas Tinggi atau Kritis, dan naik satu tingkat lagi bila ada risiko SDM Tinggi atau Kritis dengan Kode Personil yang sama.',
                ],
                [
                    'dir' => 'out',
                    'type' => 'manual',
                    'to' => 'Manajemen Aset',
                    'text' => 'Pilih Aset Terkait supaya risiko ini muncul di Riwayat Terkait aset tersebut.',
                ],
            ],
            'warning' => 'Satu risiko Tinggi atau Kritis bisa membuat sampai empat data baru di modul lain. Periksa dulu sebelum mengubah banyak level sekaligus.',
        ],

        'perubahan' => [
            'label' => 'Manajemen Perubahan',
            'keys' => ['Kode Aset', 'Kode Personil'],
            'intro' => 'Catatan perubahan pada aset TIK, dari usulan sampai selesai.',
            'flows' => [
                [
                    'dir' => 'in',
                    'type' => 'auto',
                    'to' => 'Manajemen Risiko',
                    'text' => 'Risiko yang menjadi Tinggi atau Kritis otomatis membuat usulan perubahan di sini, menunggu verifikasi admin modul ini.',
                ],
                [
                    'dir' => 'out',
                    'type' => 'manual',
                    'to' => 'Manajemen Aset',
                    'text' => 'Pilih Aset Terkait supaya perubahan ini muncul di Riwayat Terkait aset dan asetnya ditandai baru diperbarui.',
                ],
                [
                    'dir' => 'in',
                    'type' => 'auto',
                    'to' => 'Impor Excel',
                    'text' => 'Kolom Aset Terkait di berkas Excel dicocokkan ke aset yang sudah terdaftar, jadi tidak perlu ditautkan satu per satu.',
                ],
            ],
        ],

        'layanan' => [
            'label' => 'Manajemen Layanan',
            'keys' => ['Kode Aset', 'Kode Personil'],
            'intro' => 'Daftar layanan TIK, tiket keluhan, gangguan, dan permintaan dari pengguna, serta evaluasi layanan.',
            'flows' => [
                [
                    'dir' => 'out',
                    'type' => 'manual',
                    'to' => 'Manajemen Aset',
                    'text' => 'Pilih satu atau beberapa aset di Aset TIK Terkait. Perubahan layanan tercatat di aktivitas semua aset itu.',
                ],
                [
                    'dir' => 'in',
                    'type' => 'auto',
                    'to' => 'Manajemen Risiko',
                    'text' => 'Kalau ada risiko Tinggi atau Kritis pada aset yang menopang layanan ini, tiket otomatis dibuat supaya gangguannya tidak terlewat.',
                ],
                [
                    'dir' => 'in',
                    'type' => 'auto',
                    'to' => 'Impor Excel',
                    'text' => 'Kolom Aset TIK Terkait boleh berisi beberapa nama aset; semuanya dicocokkan saat impor.',
                ],
            ],
        ],

        'data_informasi' => [
            'label' => 'Manajemen Data & Informasi',
            'keys' => ['Kode Aset', 'Kode Personil'],
            'intro' => 'Catatan pengelolaan data dan informasi, termasuk risiko pada data. Sebagian isinya datang sendiri sebagai tindak lanjut risiko.',
            'flows' => [
                [
                    'dir' => 'in',
                    'type' => 'auto',
                    'to' => 'Manajemen Risiko',
                    'text' => 'Risiko yang menjadi Tinggi atau Kritis otomatis membuat catatan tinjauan data di sini, menunggu verifikasi admin modul ini.',
                ],
                [
                    'dir' => 'out',
                    'type' => 'manual',
                    'to' => 'Manajemen Aset',
                    'text' => 'Pilih Aset Terkait supaya catatan ini muncul di Riwayat Terkait aset yang datanya dimaksud.',
                ],
                [
                    'dir' => 'in',
                    'type' => 'auto',
                    'to' => 'Impor Excel',
                    'text' => 'Kolom aset di berkas Excel dicocokkan ke aset yang sudah terdaftar.',
                ],
            ],
        ],
    ];

    /**
     * "Kalau saya melakukan X di modul Y, datanya ke mana?" Satu baris per
     * kejadian, dipakai tabel di halaman Panduan dan jawaban Asisten
     * (AppHelp). Sumber perilaku sama dengan daftar di docblock kelas;
     * verifikasi: User::canAutoVerify(), GuardsWriteAccess; bukti dukung:
     * EvidenceAssessor; impor: App\Imports\{Change,DataInformation,Service}Import.
     */
    public const EVENTS = [
        [
            'action' => 'Menyimpan risiko yang levelnya Tinggi atau Kritis',
            'where' => 'Manajemen Risiko',
            'result' => 'Sistem membuat 1 program tindak lanjut di Keamanan Informasi, 1 usulan di Perubahan, dan 1 catatan tinjauan di Data & Informasi; ketiganya menunggu verifikasi admin modul masing-masing. Bila aset risiko itu dipakai layanan, 1 tiket berstatus Baru dibuat untuk setiap layanan dengan prioritas P1 atau P2.',
            'words' => 'risiko kritis tinggi simpan tindak lanjut otomatis dibuat',
        ],
        [
            'action' => 'Memilih Aset Terkait lalu menyimpan',
            'where' => 'Risiko, Perubahan, Data & Informasi, Pengetahuan, Keamanan Informasi',
            'result' => 'Data itu muncul di Riwayat Terkait aset yang dipilih dan di aktivitas asetnya; asetnya ditandai baru diperbarui.',
            'words' => 'aset terkait pilih tautkan riwayat aktivitas aset',
        ],
        [
            'action' => 'Memilih aset di form layanan',
            'where' => 'Manajemen Layanan',
            'result' => 'Perubahan pada layanan itu tercatat di aktivitas semua aset yang dipilih.',
            'words' => 'layanan aset tik terkait banyak aset',
        ],
        [
            'action' => 'Mengubah kritikalitas aset menjadi Tinggi atau Kritis',
            'where' => 'Manajemen Aset',
            'result' => 'Level setiap risiko yang tertaut ke aset itu naik satu tingkat, dengan catatan alasannya di aktivitas risiko.',
            'words' => 'kritikalitas aset naik level risiko berubah',
        ],
        [
            'action' => 'Menyimpan risiko SDM yang levelnya Tinggi atau Kritis',
            'where' => 'Manajemen SDM',
            'result' => 'Level setiap risiko di Manajemen Risiko dengan Kode Personil yang sama naik satu tingkat.',
            'words' => 'sdm pegawai kode personil level risiko naik',
        ],
        [
            'action' => 'Mengunggah PDF di Aset Pengetahuan',
            'where' => 'Manajemen Pengetahuan',
            'result' => 'Isi PDF dibaca dan disimpan sistem, lalu bisa dicari lewat Tanya Dokumen, Asisten, dan saran SOP di Analisis AI tiket. Bila Aset Terkait dipilih, dokumen juga muncul di Riwayat Terkait aset itu.',
            'words' => 'unggah upload pdf pengetahuan sop manual dokumen',
        ],
        [
            'action' => 'Mencatat Aktivitas Berbagi',
            'where' => 'Manajemen Pengetahuan',
            'result' => 'Jumlahnya ikut dihitung di Manajemen Keamanan Informasi sebagai kegiatan sosialisasi dan pelatihan.',
            'words' => 'aktivitas berbagi sosialisasi pelatihan',
        ],
        [
            'action' => 'Mengimpor berkas Excel',
            'where' => 'Perubahan, Data & Informasi, Layanan',
            'result' => 'Kolom aset di Excel dicocokkan ke aset yang sudah terdaftar, jadi datanya langsung tertaut. Setiap impor dicatat: siapa, kapan, berkas apa, berapa data baru, diperbarui, dan gagal.',
            'words' => 'impor import excel unggah berkas xlsx',
        ],
        [
            'action' => 'Menyimpan data sebagai pengguna biasa atau admin modul lain',
            'where' => 'Semua modul',
            'result' => 'Data berstatus Menunggu Verifikasi dan muncul di antrean admin modul itu. Admin bisa menyetujui atau menolak dengan catatan; data yang ditolak bisa diubah lalu masuk antrean lagi.',
            'words' => 'verifikasi menunggu admin setuju tolak antrean',
        ],
        [
            'action' => 'Mengunggah PDF bukti dukung',
            'where' => 'Evaluasi Bukti Dukung',
            'result' => 'PDF dinilai otomatis dengan Nilai Level 1–5 dan jumlah unsur yang ditemukan. Keputusan diambil admin modul yang bersangkutan. Data di modul tidak berubah.',
            'words' => 'bukti dukung unggah pdf nilai evaluasi',
        ],
        [
            'action' => 'Menekan Analisis AI pada tiket',
            'where' => 'Manajemen Layanan',
            'result' => 'AI membaca aset layanan, risiko pada aset itu, dan SOP di Manajemen Pengetahuan, lalu menyarankan kategori, prioritas, langkah awal, dan draf balasan. Petugas yang memutuskan.',
            'words' => 'tiket helpdesk analisis ai triase',
        ],
    ];

    /** @return array<string, mixed>|null */
    public static function for(?string $key): ?array
    {
        $map = $key ? (self::MAP[$key] ?? null) : null;
        if ($map === null) {
            return null;
        }

        $map['flows'] = array_merge($map['flows'], self::AI_FLOWS[$key] ?? [], self::SHARED_FLOWS);

        return $map;
    }

    /** "ke Manajemen Risiko" / "dari Manajemen Risiko" untuk tampilan dan Asisten. */
    public static function direction(array $flow): string
    {
        return (($flow['dir'] ?? 'out') === 'in' ? 'dari ' : 'ke ') . $flow['to'];
    }

    /**
     * Titik integrasi fitur AI per modul. Mencerminkan kode:
     *   - kritikalitas aset, risiko aset, SOP -> App\Services\Ai\TicketTriage::ruleBased()/related()
     *   - PDF pengetahuan sebagai sumber      -> KnowledgeIndexer, KnowledgeSearch, AppAssistant
     */
    private const AI_FLOWS = [
        'pengetahuan' => [
            [
                'dir' => 'out',
                'type' => 'auto',
                'to' => 'Tanya Dokumen, Analisis AI tiket, Asisten',
                'text' => 'PDF yang diunggah di Aset Pengetahuan dibaca dan disimpan sistem. Isinya menjadi sumber jawaban Tanya Dokumen, saran SOP di Analisis AI tiket, dan jawaban Asisten.',
            ],
        ],
        'aset' => [
            [
                'dir' => 'out',
                'type' => 'auto',
                'to' => 'Analisis AI tiket',
                'text' => 'Kalau layanan yang dilaporkan memakai aset berkritikalitas Tinggi atau Kritis, dampak tiket gangguan dinaikkan satu tingkat dan aset itu ditampilkan di hasil analisis.',
            ],
        ],
        'risiko' => [
            [
                'dir' => 'out',
                'type' => 'auto',
                'to' => 'Analisis AI tiket',
                'text' => 'Risiko pada aset sebuah layanan ditampilkan di hasil Analisis AI tiket layanan itu. Risiko dengan level tertinggi mengisi kolom Risiko TIK Terkait.',
            ],
        ],
        'layanan' => [
            [
                'dir' => 'in',
                'type' => 'auto',
                'to' => 'Aset, Risiko, Pengetahuan (Analisis AI tiket)',
                'text' => 'Analisis AI tiket membaca aset layanan, risiko pada aset itu, dan SOP di Manajemen Pengetahuan, lalu menyarankan kategori, prioritas, langkah awal, dan draf balasan.',
            ],
        ],
    ];

    /** Berlaku untuk kedelapan modul. */
    private const SHARED_FLOWS = [
        [
            'dir' => 'out',
            'type' => 'manual',
            'to' => 'Evaluasi Bukti Dukung',
            'text' => 'PDF bukti dukung modul ini diunggah dan dinilai di menu Evaluasi Bukti Dukung. Keputusannya hanya bisa diambil admin modul ini, dan tidak mengubah data di modul ini.',
        ],
        [
            'dir' => 'out',
            'type' => 'auto',
            'to' => 'Asisten',
            'text' => 'Asisten di pojok kanan bawah bisa mencari dan menghitung data modul ini, misalnya "daftar risiko kritis" atau "berapa aset kritis".',
        ],
    ];
}
