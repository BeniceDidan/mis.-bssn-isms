<?php

/*
|--------------------------------------------------------------------------
| Helpdesk — kategori, prioritas, dan target waktu (DRAF)
|--------------------------------------------------------------------------
| Prioritas = dampak × urgensi (pola matriks prioritas ITIL 4). Target
| waktu di bawah adalah usulan awal; sesuaikan dengan SOP/SLA layanan yang
| ditetapkan instansi. Rincian sumber: docs/riset/03-triase-helpdesk.md.
*/

return [

    'categories' => [
        'gangguan' => [
            'label' => 'Gangguan Layanan',
            'keywords' => ['tidak bisa diakses', 'tidak dapat diakses', 'down', 'mati', 'error 500', 'error 502', 'error 503', 'gateway', 'timeout', 'time out', 'lambat sekali', 'sangat lambat', 'tidak bisa dibuka', 'tidak bisa login', 'gagal login', 'koneksi terputus', 'offline'],
            'impact' => 'tinggi', 'urgency' => 'tinggi', 'asset_boost' => true,
            'needs' => ['waktu mulai gangguan', 'tangkapan layar pesan error', 'jumlah pengguna yang terdampak'],
            'steps' => ['Cek status server dan layanan terkait di pemantauan', 'Periksa log aplikasi dan web server pada jam kejadian', 'Informasikan estimasi pemulihan kepada pemohon'],
        ],
        'bug' => [
            'label' => 'Laporan Bug Aplikasi',
            'keywords' => ['bug', 'error', 'galat', 'salah hitung', 'tidak tersimpan', 'tidak muncul', 'tidak berfungsi', 'tombol', 'tampilan berantakan', 'data hilang', 'exception', 'tidak sesuai'],
            'impact' => 'sedang', 'urgency' => 'sedang', 'asset_boost' => true,
            'needs' => ['langkah yang dilakukan sebelum error', 'tangkapan layar', 'peramban (browser) dan perangkat yang dipakai', 'akun yang dipakai (tanpa kata sandi)'],
            'steps' => ['Reproduksi masalah di lingkungan uji', 'Periksa log error aplikasi', 'Catat sebagai permintaan perubahan bila perlu perbaikan kode'],
        ],
        'permintaan_api' => [
            'label' => 'Permintaan Integrasi / API',
            'keywords' => ['api', 'integrasi', 'web service', 'webservice', 'endpoint', 'interoperabilitas', 'bridging', 'token akses', 'pertukaran data', 'sinkronisasi data'],
            'impact' => 'sedang', 'urgency' => 'rendah',
            'needs' => ['surat permohonan dari kepala OPD', 'data/field yang dibutuhkan dan tujuannya', 'perkiraan frekuensi pemanggilan', 'kontak teknis pengembang'],
            'steps' => ['Verifikasi dasar hukum dan klasifikasi data yang diminta', 'Siapkan dokumentasi API dan kredensial lingkungan uji', 'Ajukan sebagai permintaan perubahan bila perlu endpoint baru'],
        ],
        'akun_akses' => [
            'label' => 'Akun & Hak Akses',
            'keywords' => ['lupa kata sandi', 'lupa password', 'reset password', 'reset kata sandi', 'akun terkunci', 'akun diblokir', 'hak akses', 'buat akun', 'akun baru', 'username', 'otp', 'verifikasi akun'],
            'impact' => 'rendah', 'urgency' => 'sedang',
            'needs' => ['nama dan NIP pemilik akun', 'nama aplikasi', 'persetujuan atasan untuk penambahan hak akses'],
            'steps' => ['Verifikasi identitas pemohon sebelum mengubah akun', 'Berikan hak akses minimum sesuai tugas', 'Catat perubahan akun di log'],
        ],
        'infrastruktur' => [
            'label' => 'Server, Hosting & Jaringan',
            'keywords' => ['server', 'hosting', 'vps', 'virtual machine', 'domain', 'subdomain', 'ssl', 'sertifikat ssl', 'dns', 'jaringan', 'internet', 'wifi', 'bandwidth', 'vpn', 'storage', 'penyimpanan'],
            'impact' => 'sedang', 'urgency' => 'sedang',
            'needs' => ['surat permohonan dari kepala OPD', 'spesifikasi yang dibutuhkan (CPU, RAM, penyimpanan, sistem operasi)', 'nama aplikasi yang akan dipasang', 'penanggung jawab teknis'],
            'steps' => ['Cek ketersediaan kapasitas di pusat data', 'Siapkan server sesuai standar hardening', 'Catat aset baru di modul Manajemen Aset'],
        ],
        'keamanan' => [
            'label' => 'Insiden Keamanan',
            'keywords' => ['diretas', 'peretasan', 'hack', 'deface', 'defacement', 'judi online', 'judi slot', 'slot gacor', 'phishing', 'malware', 'ransomware', 'virus', 'kebocoran data', 'data bocor', 'serangan', 'ddos', 'akun dibajak', 'mencurigakan'],
            'impact' => 'kritis', 'urgency' => 'kritis', 'asset_boost' => true,
            'needs' => ['alamat halaman/sistem yang terdampak', 'waktu pertama kali diketahui', 'tangkapan layar bukti'],
            'steps' => ['Isolasi sistem terdampak dan amankan log sebagai bukti', 'Laporkan ke tim tanggap insiden siber (CSIRT) pemerintah daerah', 'Catat sebagai risiko di modul Manajemen Risiko'],
        ],
        'data' => [
            'label' => 'Permintaan Data',
            'keywords' => ['permintaan data', 'minta data', 'rekap data', 'unduh data', 'ekspor data', 'dataset', 'laporan data', 'statistik'],
            'impact' => 'rendah', 'urgency' => 'rendah',
            'needs' => ['tujuan penggunaan data', 'periode dan rincian data', 'surat permohonan resmi'],
            'steps' => ['Cek klasifikasi data dan kewenangan pemohon', 'Koordinasikan dengan walidata', 'Hapus data pribadi yang tidak diperlukan sebelum diserahkan'],
        ],
        'fitur' => [
            'label' => 'Permintaan Fitur',
            'keywords' => ['tambah fitur', 'fitur baru', 'penambahan menu', 'pengembangan', 'usulan', 'kebutuhan baru', 'modul baru', 'tambahkan'],
            'impact' => 'rendah', 'urgency' => 'rendah',
            'needs' => ['uraian kebutuhan dan alur kerja', 'pengguna yang akan memakai fitur', 'target waktu yang diharapkan'],
            'steps' => ['Analisis kebutuhan bersama pemilik layanan', 'Ajukan sebagai permintaan perubahan', 'Masukkan ke rencana pengembangan'],
        ],
        'lainnya' => [
            'label' => 'Pertanyaan / Lainnya',
            'keywords' => [],
            'impact' => 'rendah', 'urgency' => 'rendah',
            'needs' => ['penjelasan lebih rinci tentang kebutuhan'],
            'steps' => ['Hubungi pemohon untuk memperjelas permintaan'],
        ],
    ],

    // Penanda cakupan/urgensi di teks tiket (dinaikkan, tidak pernah diturunkan).
    'impact_signals' => [
        'kritis' => ['seluruh opd', 'semua opd', 'seluruh pengguna', 'semua pengguna', 'layanan publik terhenti', 'data bocor', 'kebocoran data'],
        'tinggi' => ['banyak pengguna', 'satu opd', 'seluruh pegawai', 'semua pegawai', 'tidak bisa diakses', 'tidak dapat diakses', 'masyarakat'],
        'sedang' => ['beberapa pengguna', 'sebagian pengguna', 'beberapa pegawai'],
    ],

    'urgency_signals' => [
        'kritis' => ['darurat', 'sekarang juga'],
        'tinggi' => ['segera', 'mendesak', 'urgent', 'hari ini', 'secepatnya', 'deadline', 'tenggat', 'besok', 'terkunci', 'tidak bisa bekerja'],
    ],

    // Matriks dampak × urgensi → prioritas.
    'priority_matrix' => [
        'kritis' => ['kritis' => 'P1', 'tinggi' => 'P1', 'sedang' => 'P2', 'rendah' => 'P2'],
        'tinggi' => ['kritis' => 'P1', 'tinggi' => 'P2', 'sedang' => 'P2', 'rendah' => 'P3'],
        'sedang' => ['kritis' => 'P2', 'tinggi' => 'P2', 'sedang' => 'P3', 'rendah' => 'P4'],
        'rendah' => ['kritis' => 'P2', 'tinggi' => 'P3', 'sedang' => 'P4', 'rendah' => 'P4'],
    ],

    'priorities' => [
        'P1' => ['label' => 'P1 · Kritis', 'response' => '30 menit', 'resolution' => '4 jam', 'color' => '#ef4444'],
        'P2' => ['label' => 'P2 · Tinggi', 'response' => '2 jam', 'resolution' => '1 hari kerja', 'color' => '#f97316'],
        'P3' => ['label' => 'P3 · Sedang', 'response' => '1 hari kerja', 'resolution' => '3 hari kerja', 'color' => '#eab308'],
        'P4' => ['label' => 'P4 · Rendah', 'response' => '2 hari kerja', 'resolution' => '5 hari kerja', 'color' => '#22c55e'],
    ],
];
