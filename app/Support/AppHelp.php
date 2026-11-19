<?php

namespace App\Support;

/**
 * Pengetahuan Asisten tentang aplikasi ini sendiri: menu, tombol, alur, dan
 * aturan hitung. Ditulis dari tampilan dan kode yang ada (label tombol
 * disalin persis). Bila tampilan atau aturan berubah, ubah juga teks di
 * sini; Asisten hanya boleh menjawab cara pakai dari daftar ini.
 *
 * Ditambah otomatis dari App\Support\AiGuide (tiga fitur AI) dan
 * App\Support\IntegrationMap (titik integrasi tiap modul), supaya tidak ada
 * dua salinan teks yang bisa berbeda.
 *
 * 'words' berisi sinonim dan bentuk kata yang mungkin diketik pengguna,
 * untuk pencarian kata kunci ketika model embedding tidak aktif.
 */
class AppHelp
{
    /** @var array<string, string> modul => route halaman */
    public const MODULE_ROUTES = [
        'sdm' => 'hr-risks.index',
        'pengetahuan' => 'knowledge.index',
        'aset' => 'assets.index',
        'keamanan' => 'security-programs.index',
        'risiko' => 'risks.index',
        'perubahan' => 'changes.index',
        'layanan' => 'services.index',
        'data_informasi' => 'data-information.index',
    ];

    /** @var array<string, string> route => nama halaman untuk tautan "Buka ..." */
    private const PAGE_LABELS = [
        'dashboard' => 'Dashboard',
        'guide.index' => 'Panduan',
        'profile.edit' => 'Profil',
        'evidence.index' => 'Evaluasi Bukti Dukung',
        'assets.index' => 'Manajemen Aset',
        'risks.index' => 'Manajemen Risiko',
        'services.index' => 'Manajemen Layanan',
    ];

    private const ENTRIES = [
        [
            'title' => 'Tentang aplikasi BSSN ISMS',
            'text' => 'BSSN ISMS adalah aplikasi Sistem Manajemen Keamanan Informasi untuk Diskominfo. Isinya 8 manajemen SPBE yang saling terhubung: SDM, Pengetahuan, Aset, Keamanan Informasi, Risiko, Perubahan, Layanan, serta Data & Informasi. Ada juga menu Evaluasi Bukti Dukung dan tiga fitur AI: Evaluasi Bukti Dukung, Tanya Dokumen, dan Analisis AI tiket helpdesk. Manajemen Aset adalah pusatnya: modul lain menautkan datanya ke aset.',
            'words' => 'aplikasi apa ini isms smki sistem tentang fungsi kegunaan modul menu',
            'route' => 'dashboard',
        ],
        [
            'title' => 'Dashboard',
            'text' => 'Dashboard menampilkan ubin 8 modul sesuai urutan alur, dari Manajemen SDM sampai Data & Informasi, beserta ringkasan datanya. Klik sebuah ubin untuk membuka modulnya. Dashboard bisa dibuka dari menu samping paling atas.',
            'words' => 'beranda halaman utama home ringkasan ubin',
            'route' => 'dashboard',
        ],
        [
            'title' => 'Mencari data di semua modul',
            'text' => 'Klik kotak "Cari di seluruh modul..." di bagian atas halaman, lalu ketik kode atau judul (minimal 2 huruf). Hasilnya dikelompokkan per modul. Klik salah satu hasil untuk membuka tabel modulnya yang sudah tersaring. Asisten ini juga bisa mencarikan data dan berkas.',
            'words' => 'cari mencari pencarian search temukan kode judul',
            'route' => null,
        ],
        [
            'title' => 'Menambah data baru',
            'text' => 'Ada dua cara. Pertama, buka modulnya lalu klik tombol biru di kanan atas: Aset Baru, Risiko Baru, Perubahan Baru, Catatan Baru (Data & Informasi), Risiko Baru (SDM), Tambah Baru (Pengetahuan), Program Baru (Keamanan Informasi), atau Layanan Baru. Kedua, klik Buat Baru di bagian atas halaman mana pun, lalu pilih jenis datanya. Isi form, lalu klik Simpan. Data langsung muncul di tabel.',
            'words' => 'tambah menambah menambahkan input masukkan buat membuat bikin baru entri isi form simpan create',
            'route' => null,
        ],
        [
            'title' => 'Mengubah, mengarsipkan, dan memulihkan data',
            'text' => 'Setiap baris di tabel modul punya tombol Lihat Detail, Ubah, dan Arsipkan. Arsipkan tidak menghapus permanen. Centang Tampilkan Arsip di atas tabel untuk melihat data yang diarsipkan, lalu klik Pulihkan untuk mengembalikannya.',
            'words' => 'ubah mengubah ganti perbarui update hapus menghapus arsip arsipkan mengarsipkan diarsipkan pulihkan memulihkan kembalikan restore detail salah input keliru',
            'route' => null,
        ],
        [
            'title' => 'Impor data dari Excel',
            'text' => 'Klik Impor Excel di kanan atas modul, lalu unggah berkas .xlsx sesuai templat resmi modul itu. Impor Excel tersedia di Manajemen Aset, Perubahan, Data & Informasi, SDM, Pengetahuan, Layanan, dan Keamanan Informasi. Kolom "Aset Terkait" di Excel dicocokkan otomatis ke aset yang sudah terdaftar.',
            'words' => 'impor import excel xlsx unggah upload massal banyak sekaligus templat template spreadsheet',
            'route' => null,
        ],
        [
            'title' => 'Verifikasi data (ACC atau koreksi)',
            'text' => 'Data yang disimpan pengguna biasa, atau admin modul lain, berstatus Menunggu Verifikasi. Admin modul itu melihat antrean verifikasi di bagian atas halaman modulnya, lalu klik Setujui atau Tolak / Minta Koreksi. Data yang disimpan admin modul itu sendiri langsung Tervalidasi. Riwayat verifikasi tersimpan.',
            'words' => 'verifikasi validasi acc setujui persetujuan approve tolak koreksi menunggu tervalidasi antrean',
            'route' => null,
        ],
        [
            'title' => 'Peran pengguna',
            'text' => 'Ada dua peran: admin dan user. Setiap admin memegang satu modul (admin modul) dan berhak memverifikasi data modul itu serta memutuskan bukti dukungnya. Semua pengguna boleh menambah, mengubah, mengimpor, dan mengarsipkan data.',
            'words' => 'peran role hak akses admin user pengguna izin wewenang siapa boleh',
            'route' => null,
        ],
        [
            'title' => 'Kode Personil',
            'text' => 'Kode Personil menautkan data di berbagai modul ke orang atau pihak ketiga yang sama. Isi dengan kode yang sama persis dengan yang dipakai di modul lain, misalnya Manajemen SDM. Kosongkan untuk dibuat otomatis. Sistem tidak menebak dari kemiripan nama.',
            'words' => 'kode personil personel pegawai orang pihak ketiga tautan hubungkan',
            'route' => null,
        ],
        [
            'title' => 'Alur Data dan Riwayat Terkait',
            'text' => 'Tombol Alur Data di kanan atas setiap modul menjelaskan data modul itu dikirim ke mana dan datang dari mana, mana yang otomatis dan mana yang terjadi bila kolom penghubungnya diisi. Halaman Panduan punya tabel "Setelah Anda menyimpan, datanya ke mana" untuk semua modul. Di Manajemen Aset, tombol Lihat Detail menampilkan Riwayat Terkait: semua risiko, perubahan, data & informasi, pengetahuan, layanan, dan program keamanan yang memilih aset itu.',
            'words' => 'alur data integrasi terhubung hubungan keterkaitan relasi nyambung riwayat terkait lintas modul kemana dikirim mengalir',
            'route' => 'guide.index',
        ],
        [
            'title' => 'Tutorial dan panduan',
            'text' => 'Tombol Mulai Tutorial di bagian atas halaman mengajak tur keliling 8 modul. Setiap modul punya tombol Tutorial. Fitur AI punya tombol Panduan AI yang berisi penjelasan dan tutorial interaktif (Mulai tutorial). Urutan kerja dan alur data semua modul ada di halaman Panduan (menu nama pengguna di pojok kanan atas).',
            'words' => 'tutorial panduan bantuan petunjuk belajar tur cara pakai manual help',
            'route' => 'guide.index',
        ],
        [
            'title' => 'Lupa atau ganti kata sandi',
            'text' => 'Lupa kata sandi: di halaman masuk klik "Lupa kata sandi?", isi email akun, lalu buka tautan reset yang dikirim ke email. Ganti kata sandi: klik nama Anda di pojok kanan atas, pilih Profil, lalu isi bagian Ubah Kata Sandi. Jangan pernah mengirim kata sandi lewat chat atau tiket.',
            'words' => 'kata sandi password lupa ganti ubah reset login masuk akun profil',
            'route' => 'profile.edit',
        ],
        [
            'title' => 'Mode gelap',
            'text' => 'Klik ikon bulan atau matahari di bagian atas halaman untuk berganti antara mode gelap dan mode terang. Pilihan disimpan di peramban.',
            'words' => 'mode gelap terang dark light tema tampilan warna',
            'route' => null,
        ],
        [
            'title' => 'Cara menghitung level risiko',
            'text' => 'Level Risiko dihitung otomatis saat disimpan: Kemungkinan × Dampak, dengan Rendah = 1, Sedang = 2, Tinggi = 3, Kritis = 4. Hasil 9 ke atas Kritis, 6 sampai 8 Tinggi, 3 sampai 5 Sedang, di bawahnya Rendah. Level naik satu tingkat bila aset terkait berkritikalitas Tinggi atau Kritis, dan naik satu tingkat lagi bila ada risiko SDM Tinggi atau Kritis dengan Kode Personil yang sama.',
            'words' => 'level risiko hitung menghitung skor nilai kemungkinan dampak matriks rumus kritis tinggi',
            'route' => 'risks.index',
        ],
        [
            'title' => 'Cara menghitung kritikalitas aset',
            'text' => 'Kritikalitas aset dihitung dari nilai tertinggi Penilaian CIA (Kerahasiaan, Integritas, Ketersediaan, skala 1–5): nilai 5 Kritis, 4 Tinggi, 3 Sedang, 1–2 Rendah. Isi ketiga nilai CIA di form aset agar kritikalitas terhitung otomatis.',
            'words' => 'kritikalitas aset cia kerahasiaan integritas ketersediaan nilai hitung skor',
            'route' => 'assets.index',
        ],
        [
            'title' => 'Tindak lanjut otomatis risiko Tinggi atau Kritis',
            'text' => 'Begitu level sebuah risiko menjadi Tinggi atau Kritis, sistem otomatis membuat tindak lanjut di Manajemen Keamanan Informasi, Perubahan, serta Data & Informasi. Bila aset yang terkena menopang sebuah layanan, tiket layanan juga dibuat. Tindak lanjut tidak dibuat dua kali walau risikonya disimpan berulang.',
            'words' => 'otomatis eskalasi tindak lanjut risiko tinggi kritis program perubahan tiket',
            'route' => 'risks.index',
        ],
        [
            'title' => 'Prioritas tiket helpdesk P1 sampai P4',
            'text' => 'Prioritas tiket diambil dari tabel dampak × urgensi. P1 Kritis: tanggapan pertama 30 menit, selesai 4 jam. P2 Tinggi: 2 jam, 1 hari kerja. P3 Sedang: 1 hari kerja, 3 hari kerja. P4 Rendah: 2 hari kerja, 5 hari kerja. Contoh: dampak Kritis dengan urgensi Tinggi menjadi P1; dampak Sedang dengan urgensi Sedang menjadi P3.',
            'words' => 'prioritas tiket helpdesk p1 p2 p3 p4 sla target waktu tanggapan penyelesaian dampak urgensi',
            'route' => 'services.index',
        ],
        [
            'title' => 'Asal nilai bukti dukung',
            'text' => 'Nilai bukti dukung memakai skala tingkat kematangan Level 1–5, sama dengan nilai asesor: Merintis, Membangun, Berkembang, Melembaga, Unggul. Nilai adalah level tertinggi yang semua unsur rubriknya, dari Level 1 sampai level itu, ditemukan di PDF. Sebuah unsur dianggap ada bila salah satu kata kuncinya ditemukan. Contoh: pedoman manajemen risiko yang mengklaim Level 3 memuat 6 dari 7 unsur, tetapi matriks mitigasi Level 3 belum ada, jadi nilainya Level 2 Membangun. Persen kelengkapan tidak dipakai karena aturan evaluasi tidak memakainya. Nilai ini bukan nilai resmi; nilai resmi tetap diberikan asesor. PDF hasil scan tidak dinilai. Rinciannya ada di panel bukti dukung, bagian Asal nilai.',
            'words' => 'nilai level skala 1 5 merintis membangun berkembang melembaga unggul kelengkapan persen lengkap skor bukti dukung 86 penilaian rubrik hasil klaim unsur dari mana asal hitung',
            'route' => 'evidence.index',
        ],
        [
            'title' => 'Kenapa level risiko naik sendiri',
            'text' => 'Level risiko dihitung ulang otomatis dalam tiga keadaan: saat risiko disimpan (Kemungkinan × Dampak), saat kritikalitas aset terkaitnya berubah menjadi Tinggi atau Kritis (naik satu tingkat), dan saat ada risiko SDM Tinggi atau Kritis dengan Kode Personil yang sama (naik satu tingkat lagi). Alasannya tercatat di aktivitas risiko itu.',
            'words' => 'level risiko naik sendiri otomatis berubah sendiri kenapa tiba tiba eskalasi',
            'route' => 'risks.index',
        ],
        [
            'title' => 'Tiket otomatis dari risiko',
            'text' => 'Tiket dengan pemohon "Sistem (otomatis)" dibuat dari Manajemen Risiko. Saat sebuah risiko menjadi Tinggi atau Kritis dan asetnya dipakai sebuah layanan, satu tiket berstatus Baru dibuat untuk setiap layanan itu, dengan prioritas P1 atau P2 dan kolom Risiko TIK Terkait berisi kode risikonya.',
            'words' => 'tiket otomatis sistem dari mana datang sendiri dibuat sendiri helpdesk risiko',
            'route' => 'services.index',
        ],
        [
            'title' => 'Kolom Tambahan',
            'text' => 'Kolom Tambahan di form dipakai untuk isian yang tidak punya kolom baku, misalnya rencana mitigasi atau rencana pemulihan. Klik Tambah Kolom, lalu isi nama dan nilainya. Isinya ikut tersimpan dan tampil di detail data.',
            'words' => 'kolom tambahan data dinamis tambah kolom atribut isian lain',
            'route' => null,
        ],
        [
            'title' => 'Level risiko inheren dan risiko sisa',
            'text' => 'Level risiko inheren adalah level risiko sebelum ada pengendalian. Risiko sisa adalah risiko yang masih tersisa setelah penanganan dijalankan.',
            'words' => 'inheren residual sisa level risiko pengendalian penanganan istilah arti',
            'route' => null,
        ],
        [
            'title' => 'Target ketersediaan dan rata-rata waktu pemulihan',
            'text' => 'Target Ketersediaan adalah persentase waktu sebuah layanan harus bisa dipakai, misalnya 99%. Ketersediaan Tercapai adalah hasil nyatanya dalam satu periode. Rata-rata Waktu Pemulihan adalah rata-rata lama layanan pulih setelah gangguan (dalam istilah asing: MTTR). Ketiganya diisi di evaluasi layanan, Manajemen Layanan.',
            'words' => 'target ketersediaan sla uptime realisasi mttr waktu pemulihan evaluasi layanan istilah arti',
            'route' => 'services.index',
        ],
        [
            'title' => 'Asisten',
            'text' => 'Asisten ada di pojok kanan bawah setiap halaman. Asisten bisa menjelaskan cara memakai aplikasi, mencarikan data dan berkas (PDF pengetahuan dan bukti dukung), menghitung ringkasan data per modul, menjawab pertanyaan dari isi dokumen pengetahuan, menjelaskan alur data antarmodul (misalnya "file yang barusan saya unggah nyambung ke mana?" atau "kalau risiko jadi kritis datanya ke mana?"), dan menunjukkan rujukan riset (peraturan, standar, literatur) beserta tautannya. Tautan ke data dan berkas tampil di bawah jawaban. Asisten berjalan di server sendiri dan setiap jawabannya dicatat di log.',
            'words' => 'asisten chatbot bot tanya bantuan bisa apa saja',
            'route' => null,
        ],
    ];

    /** @var array<string, array{label: string, text: string, words: string}> */
    private const MODULES = [
        'sdm' => [
            'label' => 'Manajemen SDM',
            'text' => 'Mencatat risiko yang berhubungan dengan orang: pegawai maupun pihak ketiga yang mengelola TIK. Tujuan manajemen SDM menurut Perpres 95/2018 Pasal 51: menjamin keberlangsungan dan mutu layanan SPBE serta ketersediaan dan kompetensi SDM. Kolom form: Pegawai atau Pihak Ketiga, Kategori, Ancaman, Level Risiko Inheren, Target Waktu, Status, Penanggung Jawab, dan Kolom Tambahan. Cara menambah: buka Manajemen SDM, klik Risiko Baru di kanan atas, isi form, lalu klik Simpan.',
            'words' => 'sdm pegawai personil pihak ketiga vendor risiko sdm',
        ],
        'pengetahuan' => [
            'label' => 'Manajemen Pengetahuan',
            'text' => 'Tujuan manajemen pengetahuan menurut Perpres 95/2018 Pasal 52: meningkatkan kualitas layanan SPBE dan mendukung pengambilan keputusan. Punya 5 tab: Aset Pengetahuan (SOP, manual, dokumentasi beserta PDF-nya), Peta Keahlian pegawai, Aktivitas Berbagi, Risiko KM, dan Tanya Dokumen. Kolom form Aset Pengetahuan: Nama, Aset Terkait, Jenis, Kategori, Unit Pemilik, Tingkat Aksesibilitas, Tanggal Pembaruan Terakhir, Tautan Referensi, dan Lampiran PDF. Cara menambah: buka Manajemen Pengetahuan tab Aset Pengetahuan, klik Tambah Baru di kanan atas, isi form, lalu klik Simpan.',
            'words' => 'pengetahuan sop manual dokumen dokumentasi keahlian ahli aktivitas berbagi km knowledge',
        ],
        'aset' => [
            'label' => 'Manajemen Aset',
            'text' => 'Pusat data: semua aset TIK (perangkat keras, jaringan, perangkat lunak, sarana pendukung) didaftarkan di sini lebih dulu. Tujuan manajemen aset TIK menurut Perpres 95/2018 Pasal 50: menjamin ketersediaan dan optimalisasi pemanfaatan aset TIK. Kolom form: Kategori, Kritikalitas, Penilaian CIA, Nama Aset, Sub Klasifikasi, Pemilik, Lokasi, Status, Tahun Referensi, dan Kolom Tambahan. Cara menambah: buka Manajemen Aset, klik Aset Baru di kanan atas, isi form, lalu klik Simpan.',
            'words' => 'aset asset inventaris perangkat server jaringan aplikasi hardware software',
        ],
        'keamanan' => [
            'label' => 'Manajemen Keamanan Informasi',
            'text' => 'Program kerja keamanan informasi SPBE (pedoman, perencanaan, standar teknis). Tujuan manajemen keamanan informasi menurut Perpres 95/2018 Pasal 48: menjamin keberlangsungan SPBE dengan meminimalkan dampak risiko keamanan informasi. Kolom form: Aset Terkait, Program Kerja, Kegiatan, Penanggung Jawab, serta Target dan Realisasi Tahunan. Program juga terisi otomatis bila ada risiko yang naik menjadi Tinggi atau Kritis. Cara menambah: buka Manajemen Keamanan Informasi, klik Program Baru di kanan atas, isi form, lalu klik Simpan.',
            'words' => 'keamanan informasi program kerja kegiatan target realisasi security',
        ],
        'risiko' => [
            'label' => 'Manajemen Risiko',
            'text' => 'Risiko diidentifikasi dari aset yang sudah terdaftar. Tujuan manajemen risiko menurut Perpres 95/2018 Pasal 47: menjamin keberlangsungan SPBE dengan meminimalkan dampak risiko, lewat identifikasi, analisis, pengendalian, pemantauan, dan evaluasi. Kolom form: Judul Risiko, Aset Terkait, Kategori Risiko, Sumber Ancaman, Kerentanan, Kemungkinan, Dampak, Pemilik Risiko, Strategi Penanganan, Status, Tanggal Identifikasi, dan Tinjauan Berikutnya. Level Risiko dihitung otomatis. Cara menambah: buka Manajemen Risiko, klik Risiko Baru di kanan atas, isi form, lalu klik Simpan.',
            'words' => 'risiko ancaman kerentanan kemungkinan dampak mitigasi penanganan register',
        ],
        'perubahan' => [
            'label' => 'Manajemen Perubahan',
            'text' => 'Mencatat perubahan pada aset TIK, dari usulan sampai selesai. Tujuan manajemen perubahan menurut Perpres 95/2018 Pasal 53: menjamin keberlangsungan dan meningkatkan kualitas layanan SPBE lewat pengendalian perubahan, dengan proses perencanaan, analisis, pengembangan, implementasi, serta pemantauan dan evaluasi. Kolom form: Usulan Perubahan, Aset Terkait, Jenis Perubahan, Kategori (Mayor/Minor), Prioritas Pelaksanaan, Level Risiko Inheren, Target Implementasi, Keputusan Penanganan, Status, dan Penanggung Jawab. Cara menambah: buka Manajemen Perubahan, klik Perubahan Baru di kanan atas, isi form, lalu klik Simpan.',
            'words' => 'perubahan change revisi modifikasi usulan mayor major minor tujuan',
        ],
        'layanan' => [
            'label' => 'Manajemen Layanan',
            'text' => 'Tujuan manajemen layanan SPBE menurut Perpres 95/2018 Pasal 54: menjamin keberlangsungan dan meningkatkan kualitas layanan kepada pengguna, termasuk melayani keluhan, gangguan, masalah, permintaan, dan perubahan. Punya dua tab: Katalog Layanan (layanan beserta evaluasinya) dan Tiket Helpdesk. Kolom form layanan: Nama Layanan, Deskripsi, Pengelola Layanan, Pengelola Teknis, Cara Akses, Waktu Pelayanan, Target Ketersediaan, Status, dan Aset TIK Terkait (boleh lebih dari satu aset). Cara menambah: buka Manajemen Layanan, klik Layanan Baru di kanan atas, isi form, lalu klik Simpan. Untuk tiket, buka tab Tiket Helpdesk lalu klik Tiket Baru.',
            'words' => 'layanan service katalog tiket helpdesk sla gangguan aduan',
        ],
        'data_informasi' => [
            'label' => 'Manajemen Data & Informasi',
            'text' => 'Catatan pengelolaan data dan informasi, termasuk risiko pada data. Tujuan manajemen data menurut Perpres 95/2018 Pasal 49: data yang akurat, mutakhir, terintegrasi, dan dapat diakses. Kolom form: Ancaman/Kejadian Gangguan Data, Aset Terkait, Jenis Risiko, Kategori Aset Data, Prioritas Penanganan, Level Risiko Inheren, Target Implementasi, Keputusan Penanganan Risiko, Status, dan Penanggung Jawab. Cara menambah: buka Manajemen Data & Informasi, klik Catatan Baru di kanan atas, isi form, lalu klik Simpan.',
            'words' => 'data informasi kebocoran gangguan data catatan',
        ],
    ];

    /**
     * @return array<int, array{title: string, text: string, words: string, url: ?string, page: ?string}>
     */
    public static function entries(): array
    {
        $entries = [];

        foreach (self::ENTRIES as $entry) {
            $entries[] = [
                'title' => $entry['title'],
                'text' => $entry['text'],
                'words' => $entry['words'],
                'url' => $entry['route'] ? route($entry['route']) : null,
                'page' => $entry['route'] ? (self::PAGE_LABELS[$entry['route']] ?? 'halaman') : null,
            ];
        }

        foreach (self::MODULES as $key => $module) {
            $map = IntegrationMap::for($key);
            $entries[] = [
                'title' => $module['label'],
                'text' => $module['text'] . ' Menu: ' . $module['label'] . ' di menu samping.',
                'words' => $module['words'],
                'url' => route(self::MODULE_ROUTES[$key]),
                'page' => $module['label'],
            ];
            if ($map) {
                $flows = collect($map['flows'])->map(fn ($f) => '(' . ($f['type'] === 'auto' ? 'otomatis' : 'bila kolomnya diisi') . ', ' . IntegrationMap::direction($f) . ') ' . $f['text'])->implode(' ');
                $entries[] = [
                    'title' => 'Alur data ' . $module['label'],
                    'text' => $map['intro'] . ' ' . $flows,
                    'words' => 'alur data integrasi terhubung keterkaitan nyambung kemana dikirim datang ' . $module['words'],
                    'url' => route(self::MODULE_ROUTES[$key]),
                    'page' => $module['label'],
                ];
            }
        }

        foreach (IntegrationMap::EVENTS as $event) {
            $entries[] = [
                'title' => 'Setelah ' . lcfirst($event['action']) . ' (' . $event['where'] . ')',
                'text' => $event['result'],
                'words' => 'setelah sesudah habis itu kemana ke mana terjadi apa otomatis alur ' . $event['words'],
                'url' => route('guide.index') . '#alur',
                'page' => 'Panduan',
                // Hanya untuk pertanyaan "setelah …, ke mana?", bukan "cara …" (lihat AppAssistant::searchHelp).
                'event' => true,
            ];
        }

        $aiRoutes = ['bukti' => 'evidence.index', 'tanya' => 'knowledge.index', 'triase' => 'services.index'];
        $aiParams = ['bukti' => [], 'tanya' => ['tab' => 'tanya'], 'triase' => ['tab' => 'helpdesk']];
        $aiWords = [
            'bukti' => 'bukti dukung evaluasi unggah pdf nilai skor kelengkapan level rubrik asesor',
            'tanya' => 'tanya dokumen rag pertanyaan jawaban sop isi dokumen pencarian semantik',
            'triase' => 'tiket helpdesk analisis ai triase prioritas kategori draf balasan',
        ];
        foreach ($aiRoutes as $feature => $routeName) {
            $guide = AiGuide::for($feature);
            $url = route($routeName, $aiParams[$feature]);
            $page = ['bukti' => 'Evaluasi Bukti Dukung', 'tanya' => 'Tanya Dokumen', 'triase' => 'Tiket Helpdesk'][$feature];
            $entries[] = ['title' => $guide['title'], 'text' => $guide['intro'], 'words' => $aiWords[$feature], 'url' => $url, 'page' => $page];
            $entries[] = [
                'title' => 'Cara memakai ' . $guide['title'],
                'text' => collect($guide['steps'])->map(fn ($s, $i) => ($i + 1) . '. ' . $s)->implode("\n"),
                'words' => 'cara langkah pakai menggunakan ' . $aiWords[$feature],
                'url' => $url,
                'page' => $page,
            ];
            foreach ($guide['faq'] as $faq) {
                $entries[] = ['title' => $faq['q'] . ' (' . $guide['title'] . ')', 'text' => $faq['a'], 'words' => $aiWords[$feature], 'url' => $url, 'page' => $page];
            }
        }

        return $entries;
    }
}
