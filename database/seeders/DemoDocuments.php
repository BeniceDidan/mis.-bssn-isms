<?php

namespace Database\Seeders;

/**
 * Isi dokumen contoh untuk data demo. SEMUA FIKTIF — ditulis untuk
 * mencoba fitur Tanya Dokumen dan Evaluasi Bukti Dukung, bukan salinan
 * dokumen resmi instansi mana pun. Setiap dokumen diberi tanda "CONTOH".
 */
class DemoDocuments
{
    private const HEADER = "DINAS KOMUNIKASI DAN INFORMATIKA\nPEMERINTAH KOTA CONTOH\n(DOKUMEN CONTOH UNTUK DEMO APLIKASI)\n\n";

    /** @return array<string, array{title: string, category: string, owner: string, access: string, pages: array<int, string>}> */
    public static function knowledge(): array
    {
        return [
            'sop-permohonan-server' => [
                'title' => 'SOP Permohonan Server dan Hosting Aplikasi',
                'category' => 'SOP Infrastruktur',
                'owner' => 'Bidang Aplikasi Informatika',
                'access' => 'Internal',
                'pages' => [
                    self::HEADER . "# STANDAR OPERASIONAL PROSEDUR\n# PERMOHONAN SERVER DAN HOSTING APLIKASI\n\n"
                    . "# 1. Tujuan\nProsedur ini mengatur cara Perangkat Daerah (OPD) mengajukan permohonan server, virtual machine (VM), atau ruang hosting di pusat data pemerintah daerah agar setiap aplikasi ditempatkan pada infrastruktur yang aman, tercatat sebagai aset TIK, dan terpantau.\n\n"
                    . "# 2. Ruang Lingkup\nBerlaku untuk permohonan server baru, penambahan kapasitas (CPU, RAM, penyimpanan), perpanjangan masa hosting, dan pemindahan aplikasi dari server pihak ketiga ke pusat data.\n\n"
                    . "# 3. Persyaratan\na. Surat permohonan yang ditandatangani kepala OPD.\nb. Formulir spesifikasi kebutuhan: jumlah CPU, RAM, kapasitas penyimpanan, sistem operasi, dan basis data.\nc. Nama aplikasi, fungsi singkat, dan perkiraan jumlah pengguna.\nd. Nama dan kontak penanggung jawab teknis dari OPD.\ne. Hasil pemindaian kerentanan aplikasi bila aplikasi sudah pernah berjalan.",
                    "# 4. Langkah Prosedur\n1. OPD mengirim surat permohonan dan formulir spesifikasi melalui layanan helpdesk atau surat dinas.\n2. Petugas helpdesk mencatat permohonan sebagai tiket kategori Server, Hosting & Jaringan.\n3. Tim infrastruktur memeriksa ketersediaan kapasitas di pusat data paling lambat 2 hari kerja.\n4. Bila kapasitas tersedia, tim infrastruktur menyiapkan VM sesuai standar pengerasan (hardening): pembaruan sistem operasi, firewall aktif, akses SSH hanya dengan kunci, dan pencadangan terjadwal.\n5. Server baru dicatat di modul Manajemen Aset dengan kode aset dan tingkat kritikalitas.\n6. Kredensial akses diserahkan kepada penanggung jawab teknis secara langsung, tidak melalui pesan biasa.\n7. Tiket ditutup setelah OPD mengonfirmasi server dapat dipakai.\n\n"
                    . "# 5. Waktu Layanan\nPenyiapan server standar paling lama 5 hari kerja sejak persyaratan lengkap. Permohonan yang memerlukan pengadaan perangkat baru mengikuti jadwal pengadaan.\n\n"
                    . "# 6. Catatan\nPermohonan yang tidak lengkap dikembalikan kepada OPD melalui tiket yang sama dengan keterangan kekurangannya.",
                ],
            ],
            'sop-integrasi-api' => [
                'title' => 'SOP Permohonan Integrasi Data dan API',
                'category' => 'SOP Interoperabilitas',
                'owner' => 'Bidang Aplikasi Informatika',
                'access' => 'Internal',
                'pages' => [
                    self::HEADER . "# STANDAR OPERASIONAL PROSEDUR\n# PERMOHONAN INTEGRASI DATA DAN API\n\n"
                    . "# 1. Tujuan\nMengatur permohonan akses API dan integrasi antaraplikasi agar pertukaran data antar-OPD berjalan aman, terdokumentasi, dan sesuai prinsip Satu Data.\n\n"
                    . "# 2. Persyaratan\na. Surat permohonan dari kepala OPD peminta data.\nb. Daftar data atau field yang dibutuhkan beserta tujuan penggunaannya.\nc. Perkiraan frekuensi pemanggilan API per hari.\nd. Kontak teknis pengembang aplikasi peminta.\ne. Persetujuan walidata bila data yang diminta termasuk data pribadi.\n\n"
                    . "# 3. Langkah Prosedur\n1. Permohonan dicatat sebagai tiket kategori Integrasi / API.\n2. Tim integrasi memeriksa klasifikasi data. Data pribadi hanya dibuka sebatas yang diperlukan.\n3. Tim integrasi memberikan dokumentasi API dan token akses lingkungan uji (sandbox).\n4. OPD peminta melakukan uji integrasi paling lama 14 hari.\n5. Setelah uji berhasil, token produksi diterbitkan dengan masa berlaku 1 tahun dan pembatasan jumlah panggilan.\n6. Bila diperlukan endpoint baru, permohonan diteruskan sebagai permintaan perubahan di modul Manajemen Perubahan.\n\n"
                    . "# 4. Keamanan\nToken akses tidak boleh disimpan di kode sumber publik. Setiap pemanggilan API dicatat dalam log dan dipantau.",
                ],
            ],
            'prosedur-insiden-siber' => [
                'title' => 'Prosedur Penanganan Insiden Siber',
                'category' => 'Keamanan Informasi',
                'owner' => 'Bidang Persandian dan Keamanan Informasi',
                'access' => 'Terbatas',
                'pages' => [
                    self::HEADER . "# PROSEDUR PENANGANAN INSIDEN SIBER\n\n"
                    . "# 1. Pengertian\nInsiden siber adalah kejadian yang mengganggu kerahasiaan, keutuhan, atau ketersediaan sistem elektronik, misalnya peretasan situs web (deface), sisipan konten judi online, malware, ransomware, kebocoran data, dan serangan DDoS.\n\n"
                    . "# 2. Pelaporan\nSetiap pegawai yang menemukan dugaan insiden wajib segera melapor ke helpdesk atau tim tanggap insiden siber (CSIRT) pemerintah daerah, dengan melampirkan alamat sistem yang terdampak, waktu pertama diketahui, dan tangkapan layar.\n\n"
                    . "# 3. Langkah Penanganan\n1. Identifikasi: CSIRT memastikan laporan merupakan insiden dan menetapkan tingkat keparahan.\n2. Penahanan: sistem yang terdampak diisolasi dari jaringan; akun yang diduga bocor dinonaktifkan sementara.\n3. Pengumpulan bukti: log server dan aplikasi diamankan sebelum ada perubahan.\n4. Pemberantasan: celah keamanan ditutup, berkas berbahaya dihapus, kata sandi diganti.\n5. Pemulihan: sistem dipulihkan dari cadangan yang bersih lalu dipantau ketat selama 7 hari.\n6. Pelaporan: laporan insiden disampaikan kepada pimpinan paling lambat 3 hari kerja setelah pemulihan.\n7. Pembelajaran: akar masalah dicatat sebagai risiko di modul Manajemen Risiko dan dibahas dalam rapat evaluasi.",
                ],
            ],
            'kebijakan-kata-sandi' => [
                'title' => 'Kebijakan Kata Sandi dan Hak Akses',
                'category' => 'Kebijakan Keamanan',
                'owner' => 'Bidang Persandian dan Keamanan Informasi',
                'access' => 'Internal',
                'pages' => [
                    self::HEADER . "# KEBIJAKAN KATA SANDI DAN HAK AKSES\n\n"
                    . "# 1. Kata Sandi\na. Panjang kata sandi paling sedikit 12 karakter dan tidak memakai nama, NIP, atau tanggal lahir.\nb. Kata sandi akun administrator diganti paling lama setiap 90 hari.\nc. Kata sandi tidak boleh dibagikan, termasuk kepada petugas helpdesk. Petugas helpdesk tidak akan pernah meminta kata sandi.\nd. Aplikasi yang memproses data pribadi wajib menggunakan autentikasi dua faktor.\n\n"
                    . "# 2. Hak Akses\na. Hak akses diberikan sesuai tugas (prinsip hak akses minimum).\nb. Penambahan hak akses memerlukan persetujuan atasan langsung.\nc. Akun pegawai yang pindah tugas atau pensiun dinonaktifkan paling lambat 1 hari kerja setelah pemberitahuan dari bagian kepegawaian.\n\n"
                    . "# 3. Reset Kata Sandi\nPermohonan reset kata sandi diajukan melalui helpdesk. Petugas memverifikasi identitas pemohon (nama, NIP, dan unit kerja) sebelum mengirim tautan reset ke surel dinas pemohon.",
                ],
            ],
            'sop-backup' => [
                'title' => 'SOP Pencadangan dan Pemulihan Data',
                'category' => 'SOP Infrastruktur',
                'owner' => 'Bidang Aplikasi Informatika',
                'access' => 'Internal',
                'pages' => [
                    self::HEADER . "# STANDAR OPERASIONAL PROSEDUR\n# PENCADANGAN (BACKUP) DAN PEMULIHAN DATA\n\n"
                    . "# 1. Jadwal Pencadangan\na. Basis data aplikasi layanan publik: pencadangan penuh setiap hari pukul 01.00 dan pencadangan inkremental setiap 4 jam.\nb. Berkas aplikasi dan konfigurasi server: pencadangan mingguan setiap Minggu.\nc. Salinan cadangan disimpan di dua lokasi: pusat data utama dan lokasi cadangan terpisah.\n\n"
                    . "# 2. Pengujian Pemulihan\nUji pemulihan dilakukan setiap 3 bulan untuk memastikan cadangan dapat dipakai. Hasil uji dicatat beserta waktu yang dibutuhkan untuk pulih.\n\n"
                    . "# 3. Permohonan Pemulihan Data\n1. OPD mengajukan permohonan pemulihan melalui helpdesk dengan menyebutkan aplikasi, data, dan titik waktu yang diinginkan.\n2. Petugas memastikan permohonan disetujui pemilik data.\n3. Data dipulihkan terlebih dahulu ke lingkungan uji untuk diperiksa OPD, baru kemudian ke lingkungan produksi.\n4. Setiap pemulihan dicatat dalam log operasional layanan.",
                ],
            ],
        ];
    }

    /** @return array<string, array{module: string, level: int, unit: string, title: string, pages: array<int, string>}> */
    public static function evidence(): array
    {
        return [
            'pedoman-risiko' => [
                'module' => 'risiko',
                'level' => 3,
                'unit' => 'Diskominfo (contoh)',
                'title' => 'Keputusan Kepala Dinas tentang Pedoman Manajemen Risiko SPBE',
                'pages' => [
                    self::HEADER . "# KEPUTUSAN KEPALA DINAS KOMUNIKASI DAN INFORMATIKA\nNomor: 800/245/419.48/2026\n\n# TENTANG\n# PEDOMAN MANAJEMEN RISIKO SPBE\n\n"
                    . "KESATU: Menetapkan Pedoman Manajemen Risiko SPBE sebagaimana tercantum dalam lampiran keputusan ini.\n\n"
                    . "KEDUA: Pedoman ini berlaku untuk seluruh layanan SPBE yang diselenggarakan oleh Dinas.\n\n"
                    . "# LAMPIRAN\n# A. Penetapan Konteks\nRuang lingkup manajemen risiko meliputi aplikasi, infrastruktur, data, dan sumber daya manusia pengelola SPBE. Selera risiko ditetapkan oleh pimpinan untuk setiap kategori risiko.\n\n"
                    . "# B. Identifikasi Risiko\nSetiap unit mencatat risiko pada register risiko yang memuat kejadian, penyebab, dan dampak.\n\n"
                    . "# C. Analisis Risiko\nRisiko dianalisis dengan menetapkan level kemungkinan (1 sampai 5) dan level dampak (1 sampai 5). Besaran risiko adalah hasil perkalian keduanya dan dikelompokkan menjadi level risiko sangat rendah, rendah, sedang, tinggi, dan sangat tinggi.",
                    "# D. Ketentuan Penutup\nKeputusan ini mulai berlaku pada tanggal ditetapkan.\n\nDitetapkan di Kota Contoh\npada tanggal 3 Maret 2026\n\nKEPALA DINAS KOMUNIKASI DAN INFORMATIKA\n\nDitandatangani secara elektronik\n\nNAMA CONTOH, S.Kom., M.T.\nNIP. 19790312 200501 1 004",
                ],
            ],
            'sop-helpdesk' => [
                'module' => 'layanan',
                'level' => 2,
                'unit' => 'Diskominfo (contoh)',
                'title' => 'SOP Layanan Helpdesk dan Service Level Agreement',
                'pages' => [
                    self::HEADER . "# STANDAR OPERASIONAL PROSEDUR LAYANAN HELPDESK\nNomor: 800/310/419.48/2026\n\n"
                    . "# 1. Fasilitas Dukungan Pengguna\nDinas menyediakan helpdesk sebagai layanan bantuan bagi pengguna aplikasi pemerintah daerah melalui telepon, surel, dan formulir tiket daring.\n\n"
                    . "# 2. Service Level Agreement (SLA)\nPrioritas P1: waktu respons 30 menit, waktu penyelesaian 4 jam.\nPrioritas P2: waktu respons 2 jam, waktu penyelesaian 1 hari kerja.\nPrioritas P3: waktu respons 1 hari kerja, waktu penyelesaian 3 hari kerja.\nPrioritas P4: waktu respons 2 hari kerja, waktu penyelesaian 5 hari kerja.\n\n"
                    . "# 3. Pemantauan SLA\nPemenuhan SLA dipantau otomatis melalui dasbor layanan dan direkap setiap bulan.\n\n"
                    . "Ditetapkan di Kota Contoh pada tanggal 12 Mei 2026\nKEPALA DINAS KOMUNIKASI DAN INFORMATIKA\nNAMA CONTOH, S.Kom., M.T.\nNIP. 19790312 200501 1 004",
                ],
            ],
            'laporan-pelatihan' => [
                'module' => 'sdm',
                'level' => 3,
                'unit' => 'Bagian Kepegawaian (contoh)',
                'title' => 'Laporan Pelatihan Kompetensi Digital ASN 2026',
                'pages' => [
                    self::HEADER . "# LAPORAN PELAKSANAAN PELATIHAN KOMPETENSI DIGITAL ASN TAHUN 2026\n\n"
                    . "# 1. Dasar\nPeta kompetensi digital pegawai yang disusun pada awal tahun menunjukkan kebutuhan pelatihan keamanan informasi dan pengelolaan aplikasi bagi ASN pengelola TIK.\n\n"
                    . "# 2. Pelaksanaan\nBimbingan teknis dilaksanakan tiga angkatan pada Juni sampai Agustus 2026 dengan materi literasi digital, keamanan informasi, dan administrasi server.\n\n"
                    . "# 3. Capaian\nBerdasarkan rekapitulasi peserta, 46 dari 50 ASN pengelola TIK (92 persen) telah mengikuti pelatihan. Daftar peserta terlampir.",
                ],
            ],
        ];
    }
}
