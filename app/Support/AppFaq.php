<?php

namespace App\Support;

/**
 * Penjelasan baku untuk pertanyaan yang sering membingungkan peninjau.
 *
 * Masukan pengguna 26 Nov 2026: Asisten harus bisa menjelaskan alur dan semua
 * hal yang membingungkan tim Kominfo, dengan jawaban lengkap dan mudah
 * dimengerti. Uji 35 pertanyaan memperlihatkan "Apa itu SPBE?" dan "Modul
 * mana yang diisi duluan?" ditolak, sedangkan "Apa saja modulnya?" dan
 * "Datanya asli?" dikarang LLM dari isi SOP. Karena itu jawaban di sini
 * ditulis tetap, dengan bahasa yang sama seperti presentasi "Rancangan BSSN
 * ISMS", dan dicek Asisten sebelum panduan umum dan sebelum LLM.
 *
 * match: pola pada teks yang sudah dinormalkan (AppAssistant::normalize).
 * skip: pola yang membatalkan entri (mis. pertanyaan hitungan).
 * actions: tombol [label, route, params, point]; point = sorot tombol di halaman tujuan.
 */
class AppFaq
{
    public const ENTRIES = [
        'alur' => [
            'match' => [
                '/\b(alur|cara kerja)\s+(aplikasi|aplikasinya|kerja|kerjanya|sistem|sistemnya|keseluruhan|semua modul|antar ?modul|bisnis)\b/u',
                '/\b(alur|cara kerja)\b.*\b(aplikasi ini|bssn|isms)\b/u',
                '/\bhubungan\s+(antar ?modul|antar ?manajemen|semua modul|modul|antara modul)\b/u',
                '/\bmodul\w*\s+(saling )?(tersambung|terhubung|berhubungan|terkait)\b/u',
                '/\bdari aset sampai\b/u',
                '/^(bagaimana |jelaskan )?alur(nya)?( bagaimana| seperti apa| aplikasi)?$/u',
            ],
            'answer' => "Alurnya dimulai dari aset, lalu bergerak ke modul lain:\n"
                . "1. Aset: daftarkan dulu perangkat, aplikasi, dan data kantor. Setiap aset punya kode, misalnya AST-2026-0007.\n"
                . "2. Risiko: catat hal buruk yang mungkin terjadi pada aset itu. Levelnya dihitung otomatis.\n"
                . "3. Kalau risikonya Tinggi atau Kritis, sistem langsung membuat tugas di Keamanan Informasi, Perubahan, serta Data & Informasi.\n"
                . "4. Kalau aset itu dipakai sebuah layanan, tiket juga dibuat di Manajemen Layanan.\n"
                . "5. Pengetahuan menyimpan SOP yang cocok, dan SDM mencatat orang yang mengurusnya.\n"
                . "6. Semua yang dibuat sistem menunggu diperiksa admin modulnya.\n\n"
                . "Contoh: website disusupi iklan judi. Petugas cukup mencatat satu risiko, lalu empat modul lain langsung mendapat tindak lanjut.\n\n"
                . 'Di setiap modul ada tombol Alur Data yang menjelaskan data modul itu dikirim ke mana dan datang dari mana.',
            'actions' => [['Buka Panduan', 'guide.index', [], false], ['Tunjukkan tombol Alur Data', 'risks.index', ['tunjuk' => 'integration-map'], true]],
        ],
        'spbe' => [
            'match' => ['/\bspbe\b/u'],
            'skip' => '/\b(berapa|daftar|dokumen|berkas|pdf|pasal|dasar|aturan|regulasi|peraturan|perpres|manajemen risiko spbe|manajemen data spbe)\b/u',
            'answer' => "SPBE singkatan dari Sistem Pemerintahan Berbasis Elektronik: urusan pemerintahan yang dikerjakan dengan teknologi, misalnya surat dikirim lewat e-Surat.\n\n"
                . 'Aturan SPBE meminta delapan hal dikelola: SDM, Pengetahuan, Aset, Keamanan Informasi, Risiko, Perubahan, Layanan, serta Data & Informasi. Aplikasi ini menyatukan kedelapannya dalam satu tempat.',
        ],
        'isms' => [
            'match' => ['/\bisms\b.*\b(itu apa|apa|artinya|singkatan|maksudnya)\b|\b(apa itu|arti|singkatan|maksud)\b.*\bisms\b/u', '/\b(aplikasi ini|aplikasi apa ini)\b.*\b(apa|untuk apa|fungsinya)\b/u'],
            'answer' => "ISMS singkatan dari Information Security Management System, atau Sistem Manajemen Keamanan Informasi.\n\n"
                . "BSSN ISMS adalah satu aplikasi untuk mengurus delapan bagian pengelolaan teknologi di pemerintahan. Dulu datanya tersebar di banyak berkas Excel. Sekarang satu catatan di satu modul bisa langsung dipakai modul lain.\n\n"
                . 'Ada juga tiga bantuan AI: Evaluasi Bukti Dukung, Tanya Dokumen, dan Analisis tiket.',
            'actions' => [['Buka Dashboard', 'dashboard', [], false]],
        ],
        'modul' => [
            'match' => ['/\b(apa saja|daftar|ada berapa|berapa|sebutkan)\b.*\b(modul|manajemen)\b|\b(modul|manajemen)\w*\b.*\b(apa saja|ada apa saja|ada berapa)\b/u'],
            'skip' => '/\b(risiko|aset|layanan|tiket|perubahan|kritis|tinggi|data informasi|sdm|pengetahuan|keamanan|admin)\b/u',
            'answer' => "Ada delapan modul. Masing-masing menjawab satu pertanyaan:\n"
                . "• SDM: siapa yang mengurus, dan apa risikonya?\n"
                . "• Pengetahuan: di mana panduan kerjanya?\n"
                . "• Aset: kantor punya perangkat apa saja?\n"
                . "• Keamanan Informasi: apa rencana supaya tetap aman?\n"
                . "• Risiko: apa yang bisa membahayakan?\n"
                . "• Perubahan: apa yang mau diubah?\n"
                . "• Layanan: apa layanannya, dan apa keluhannya?\n"
                . "• Data & Informasi: apakah datanya benar dan aman?\n\n"
                . 'Selain itu ada Evaluasi Bukti Dukung untuk memeriksa dokumen sebelum dinilai asesor.',
            'actions' => [['Buka Dashboard', 'dashboard', [], false]],
        ],
        'urutan' => [
            'match' => ['/\b(duluan|dahulu|lebih dulu|terlebih dahulu|pertama kali|mulai dari mana|dari mana mulai|urutan(nya)?)\b.*\b(isi|mengisi|diisi|mulai|modul|data|input)\b|\b(isi|mengisi|diisi|modul)\b.*\b(duluan|dahulu|lebih dulu|pertama)\b/u'],
            'answer' => "Mulai dari Manajemen Aset. Modul lain menunjuk ke aset, jadi asetnya harus terdaftar lebih dulu.\n\n"
                . "Setelah itu:\n"
                . "1. Catat orang yang mengurus di Manajemen SDM dan simpan SOP di Manajemen Pengetahuan.\n"
                . "2. Catat risiko di Manajemen Risiko dan pilih asetnya. Level risiko ikut naik kalau asetnya penting.\n"
                . "3. Lengkapi Keamanan Informasi, Perubahan, serta Data & Informasi. Sebagian sudah terisi otomatis dari risiko besar.\n"
                . '4. Daftarkan layanan dan pilih aset yang dipakainya, supaya tiket gangguannya tersambung.',
            'actions' => [['Tunjukkan tombol Aset Baru', 'assets.index', ['tunjuk' => 'add-button'], true]],
        ],
        'data_asli' => [
            'match' => ['/\b(data|isi|dokumen|contoh)\w*\b.*\b(asli|nyata|beneran|fiktif|dummy|sungguhan|palsu)\b/u'],
            'answer' => "Belum. Aplikasi ini masih tahap uji coba, jadi semua data di dalamnya adalah data contoh (fiktif) untuk demo.\n\n"
                . 'Tidak ada data pegawai atau warga yang asli. PDF contohnya juga diberi tanda sebagai dokumen contoh.',
        ],
        'ai_model' => [
            'match' => ['/\b(ai|ainya|kecerdasan buatan|kecerdasan artifisial|model)\b.*\b(pakai|memakai|menggunakan|pake|jenis|merek|buatan|apa namanya)\b|\bpakai (ai|model) apa\b|\b(llm|ollama|llama)\b/u'],
            'answer' => "AI di aplikasi ini berjalan di server sendiri, bukan layanan di internet:\n"
                . "• llama3.2 untuk menyusun kalimat jawaban dan ringkasan.\n"
                . "• bge-m3 untuk mencari bagian dokumen yang paling cocok dengan pertanyaan.\n\n"
                . 'Keduanya dijalankan dengan Ollama. Nilai, level, dan prioritas tidak ditebak AI; semuanya dihitung dengan aturan tetap.',
        ],
        'ai_internet' => [
            'match' => ['/\b(ai|data|dokumen|isi)\b.*\b(internet|pihak luar|ke luar|keluar|cloud|openai|chatgpt|google)\b/u'],
            'answer' => "Tidak. AI berjalan di server sendiri, jadi isi dokumen dan tiket tidak dikirim ke internet.\n\n"
                . 'Setiap jawaban AI dicatat di log, dan hasil AI selalu berupa saran. Yang memutuskan tetap petugas atau admin modul.',
        ],
        'ai_mati' => [
            'match' => ['/\b(ai|kecerdasan|asisten)\b.*\b(mati|tidak aktif|error|rusak|down|nonaktif|gagal|tidak jalan)\b/u'],
            'answer' => "Aplikasi tetap jalan.\n\n"
                . "Yang dihitung dengan aturan tetap keluar: level risiko, nilai bukti dukung, serta kategori dan prioritas tiket.\n\n"
                . 'Yang tidak keluar hanya kalimat buatan AI, seperti ringkasan dokumen dan kalimat pembuka balasan tiket. Tanya Dokumen tetap menampilkan bagian dokumen yang paling cocok.',
        ],
        'aman' => [
            'match' => ['/\b(data|aplikasi)\w*\s+(saya |kita |di sini |ini |di aplikasi ini )?(aman|terjamin|terlindungi)\b|\b(apakah|seberapa) aman\b|\bkeamanan (data|aplikasi)( ini| di aplikasi ini)?\b/u'],
            'answer' => "Beberapa hal menjaga data di aplikasi ini:\n"
                . "• Harus masuk dengan akun, dan setiap akun punya peran: admin modul atau pengguna biasa.\n"
                . "• Data baru dari pengguna biasa menunggu diperiksa admin modulnya.\n"
                . "• Setiap perubahan tercatat: siapa, kapan, dan apa yang diubah.\n"
                . "• Data yang diarsipkan tidak terhapus dan bisa dipulihkan.\n"
                . '• AI berjalan di server sendiri, jadi isi dokumen tidak dikirim ke internet.',
        ],
        'hp' => [
            'match' => ['/\b(hp|handphone|ponsel|smartphone|android|iphone|tablet|seluler)\b/u', '/\bversi (mobile|hp)\b/u'],
            'answer' => "Bisa. Buka alamat yang sama di peramban HP, lalu masuk dengan akun yang sama.\n\n"
                . 'Isi dan caranya sama seperti di laptop. Menu modul ada di ikon menu di kiri atas, dan Asisten tetap ada di pojok kanan bawah.',
        ],
        'beda_sdm' => [
            'match' => ['/\b(beda|bedanya|perbedaan)\b.*\brisiko\b.*\bsdm\b|\b(beda|bedanya|perbedaan)\b.*\bsdm\b.*\brisiko\b/u'],
            'answer' => "Risiko di Manajemen Risiko adalah bahaya pada aset, misalnya website disusupi iklan judi.\n\n"
                . "Risiko SDM adalah bahaya yang datang dari orang, misalnya hanya satu pegawai yang bisa menjalankan server.\n\n"
                . 'Keduanya tersambung lewat Kode Personil. Kalau orang yang mengurus aset juga berisiko besar, level risiko asetnya naik satu tingkat.',
        ],
        'beda_asisten' => [
            'match' => ['/\b(beda|bedanya|perbedaan)\b.*\btanya dokumen\b|\btanya dokumen\b.*\b(beda|bedanya|perbedaan)\b/u'],
            'answer' => "Tanya Dokumen hanya menjawab dari isi PDF di Manajemen Pengetahuan, lengkap dengan nama dokumen dan halamannya.\n\n"
                . "Asisten menjawab soal aplikasinya: cara memakai, data di modul, alur data, dan mengantar ke halaman.\n\n"
                . 'Kalau pertanyaannya tentang isi SOP, Asisten juga memakai dokumen yang sama.',
            'actions' => [['Buka Tanya Dokumen', 'knowledge.index', ['tab' => 'tanya'], false]],
        ],
        'kode_aset' => [
            'match' => ['/\bkode aset\b/u'],
            'skip' => '/\b(berapa|daftar|cari|carikan|ast-)/u',
            'answer' => "Kode Aset adalah nomor unik setiap aset, mirip nomor barang. Contohnya AST-2026-0007 untuk Website Resmi Pemerintah Kota.\n\n"
                . 'Saat mencatat risiko, perubahan, atau panduan, petugas memilih aset itu. Jadi semua catatan tentang website tersebut terkumpul di satu tempat: Riwayat Terkait di Manajemen Aset.',
            'actions' => [['Buka Manajemen Aset', 'assets.index', [], false]],
        ],
        'kode_personil' => [
            'match' => ['/\bkode personil\b/u'],
            'skip' => '/\b(berapa|daftar|cari|carikan|psn-)/u',
            'answer' => "Kode Personil adalah kode untuk satu orang atau pihak ketiga, mirip nomor pegawai. Contohnya PSN-1A2B3C.\n\n"
                . "Kalau kodenya sama, berarti orangnya sama. Jadi data di beberapa modul tidak tertukar walaupun ada dua orang dengan nama yang mirip. Kode dipakai supaya data pribadi seperti NIP tidak perlu ditulis.\n\n"
                . 'Saat mengisi formulir, kosongkan saja. Sistem akan membuatnya otomatis.',
        ],
        'verifikasi' => [
            'match' => [
                '/\b(siapa|kenapa|mengapa)\b.*\b(verifikasi|memverifikasi|diverifikasi|memeriksa|menyetujui|mengesahkan)\b/u',
                '/\bmenunggu verifikasi\b.*\b(artinya|maksudnya|apa)\b|\b(kenapa|mengapa|arti|maksud)\b.*\bmenunggu verifikasi\b/u',
            ],
            'answer' => "Setiap modul punya admin sendiri, misalnya Admin Risiko untuk Manajemen Risiko.\n\n"
                . "Data yang disimpan pengguna biasa, admin modul lain, atau dibuat otomatis oleh sistem berstatus Menunggu Verifikasi. Admin modul itu membukanya di bagian atas halaman modulnya, lalu memilih Setujui atau Tolak / Minta Koreksi.\n\n"
                . 'Data yang disimpan admin modul itu sendiri langsung Tervalidasi.',
        ],
        'level_bukti' => [
            'match' => [
                '/\blevel 1\b.*\b5\b/u',
                '/\b(merintis|membangun|berkembang|melembaga|unggul)\b.*\b(artinya|maksudnya|apa|itu)\b/u',
                '/\b(arti|maksud)\b.*\blevel\b.*\b(bukti|kematangan|asesor)\b|\btingkat kematangan\b.*\b(apa|artinya|itu)\b/u',
            ],
            'answer' => "Level 1–5 adalah tingkat kematangan yang dipakai asesor:\n"
                . "• Level 1 Merintis: masih disusun.\n"
                . "• Level 2 Membangun: sudah jalan di sebagian layanan.\n"
                . "• Level 3 Berkembang: sudah jalan di semua layanan.\n"
                . "• Level 4 Melembaga: semuanya sudah diperiksa ulang.\n"
                . "• Level 5 Unggul: hasil pemeriksaannya sudah diperbaiki.\n\n"
                . 'Evaluasi Bukti Dukung memakai level yang sama, supaya hasilnya mudah dibandingkan dengan nilai asesor.',
            'actions' => [['Buka Evaluasi Bukti Dukung', 'evidence.index', [], false]],
        ],
        'bukti_resmi' => [
            'match' => ['/\bresmi\b.*\b(bukti|nilai)\b|\b(bukti|nilai)\b.*\bresmi\b/u'],
            'answer' => "Bukan. Pemeriksaan di aplikasi ini adalah latihan sebelum dinilai asesor. Nilai resmi tetap diberikan tim asesor.\n\n"
                . 'Aplikasi hanya membantu melihat level yang kemungkinan tercapai dan unsur yang masih kurang. Keputusan diterima, diperbaiki, atau ditolak tetap di tangan admin modul.',
            'actions' => [['Buka Evaluasi Bukti Dukung', 'evidence.index', [], false]],
        ],
        'kritikalitas' => [
            'match' => ['/\b(apa itu|arti|maksud|maksudnya|itu apa|artinya)\b.*\bkritikalitas\b|\bkritikalitas\b.*\b(itu apa|artinya|maksudnya|apa)\b/u'],
            'skip' => '/\b(berapa|daftar|tampilkan|cari)\b/u',
            'answer' => "Kritikalitas artinya seberapa penting sebuah aset.\n\n"
                . "Setiap aset diberi tiga nilai dari 1 sampai 5: seberapa rahasia, seberapa harus benar, dan seberapa harus selalu bisa dipakai. Nilai yang paling tinggi menentukan kritikalitasnya:\n"
                . "• 5 = Kritis\n• 4 = Tinggi\n• 3 = Sedang\n• 1–2 = Rendah\n\n"
                . 'Aset yang Tinggi atau Kritis membuat risikonya naik satu tingkat, dan tiket pada aset itu didahulukan.',
            'actions' => [['Buka Manajemen Aset', 'assets.index', [], false]],
        ],
        'dasar' => [
            'match' => ['/\b(dasar hukum|dasar aturan|dasar resmi|landasan hukum|payung hukum)\b.*\b(aplikasi|isms|ini|sistem)\b|\b(aplikasi|isms)\b.*\b(dasar hukum|dasar aturan|landasan hukum)\b/u'],
            'answer' => "Dasar utamanya Perpres 95/2018 tentang SPBE, Pasal 46 sampai 54. Aturan itu meminta delapan hal dikelola, dan kedelapannya menjadi modul di aplikasi ini.\n\n"
                . "Tiap modul juga memakai aturan turunannya, misalnya:\n"
                . "• Risiko: PermenPANRB 5/2020\n"
                . "• Keamanan Informasi: Peraturan BSSN 4/2021\n"
                . "• Aset dan Layanan: Permenkomdigi 12/2026\n"
                . "• Bukti Dukung: PermenPANRB 8/2026\n\n"
                . 'Tulis "sumber peraturan" untuk melihat daftar lengkap beserta tautannya.',
        ],
        'arsip' => [
            'match' => ['/\b(arsip|arsipkan|diarsipkan|mengarsipkan|dihapus|terhapus)\b/u'],
            'skip' => '/\b(berapa|daftar|tampilkan|siapa|kapan)\b/u',
            'answer' => "Data yang diarsipkan tidak terhapus. Data itu hilang dari tabel biasa, tetapi bisa dilihat lagi dengan mencentang Tampilkan Arsip di atas tabel, lalu dikembalikan dengan tombol Pulihkan.\n\n"
                . 'Mengarsipkan aset juga tidak menghapus data di modul lain. Hanya tautannya ke aset itu yang kosong. Periksa Riwayat Terkait aset sebelum mengarsipkannya.',
        ],
        'prioritas' => [
            'match' => ['/\bp[1-4]\b.*\b(itu apa|apa itu|artinya|maksudnya|itu)\b|\b(apa itu|arti|maksud)\b.*\bp[1-4]\b|\bprioritas tiket\b.*\b(apa|artinya|maksudnya|bagaimana)\b/u'],
            'skip' => '/\b(berapa|daftar|tampilkan|cari|carikan|yang masih)\b/u',
            'answer' => "P1 sampai P4 adalah tingkat prioritas tiket helpdesk, dari yang paling mendesak:\n"
                . "• P1 Kritis: ditanggapi dalam 30 menit, selesai dalam 4 jam.\n"
                . "• P2 Tinggi: ditanggapi dalam 2 jam, selesai dalam 1 hari kerja.\n"
                . "• P3 Sedang: ditanggapi dalam 1 hari kerja, selesai dalam 3 hari kerja.\n"
                . "• P4 Rendah: ditanggapi dalam 2 hari kerja, selesai dalam 5 hari kerja.\n\n"
                . 'Prioritas dihitung dari seberapa parah dampaknya dan seberapa mendesak. Batas waktu ini masih usulan; menurut aturan, batas waktu ditentukan pemilik layanan.',
            'actions' => [['Buka Tiket Helpdesk', 'services.index', ['tab' => 'helpdesk'], false]],
        ],
    ];

    /**
     * Penjelasan tiap modul dan fitur, sama dengan slide presentasi.
     * Kunci sama dengan halaman di Concerns\GuidesConversation::PAGES.
     */
    public const MODULES = [
        'sdm' => [
            'label' => 'Manajemen SDM',
            'fungsi' => 'Mencatat orang yang mengurus teknologi dan bahaya yang bisa muncul.',
            'contoh' => 'hanya satu orang yang bisa menjalankan server. Kalau orang itu pindah, layanan bisa berhenti.',
            'ditulis' => 'Nama orang, apa bahayanya, seberapa besar, kapan harus selesai, dan siapa yang mengurus.',
            'tersambung' => ['Risiko: kalau orangnya berisiko besar, risiko dengan kode orang itu naik (otomatis)', 'Pengetahuan: terlihat orang itu ahli di bidang apa', 'Modul lain: kode yang sama berarti orang yang sama'],
        ],
        'pengetahuan' => [
            'label' => 'Manajemen Pengetahuan',
            'fungsi' => 'Menyimpan ilmu dan panduan kerja, supaya tidak hilang saat pegawai pindah atau pensiun.',
            'contoh' => 'cara mengganti kata sandi disimpan, lalu semua pegawai bisa membacanya.',
            'ditulis' => 'Panduan dan PDF, siapa ahli di bidang apa, kegiatan berbagi ilmu, dan bahaya kalau ilmu hilang.',
            'tersambung' => ['Aset: panduan muncul di riwayat aset yang dipilih', 'SDM: ahli tersambung ke catatan risikonya', 'Keamanan: kegiatan berbagi ilmu ikut dihitung (otomatis)', 'Layanan: panduan yang cocok disarankan saat ada tiket (otomatis)'],
        ],
        'aset' => [
            'label' => 'Manajemen Aset',
            'fungsi' => 'Daftar semua perangkat, aplikasi, dan data milik kantor. Mirip buku inventaris.',
            'contoh' => 'server, website resmi, dan aplikasi kepegawaian.',
            'ditulis' => 'Nama, jenis, pemilik, dan tempatnya. Lalu tiga nilai (1–5): seberapa rahasia, seberapa harus benar, dan seberapa harus selalu bisa dipakai. Nilai tertinggi menentukan seberapa penting aset itu.',
            'tersambung' => ['Modul lain: catatan yang memilih aset ini terkumpul di riwayatnya (otomatis)', 'Risiko: kalau asetnya penting, risikonya naik satu tingkat (otomatis)', 'Layanan: tiket pada aset penting didahulukan (otomatis)'],
        ],
        'keamanan' => [
            'label' => 'Manajemen Keamanan Informasi',
            'fungsi' => 'Berisi rencana kerja supaya data dan layanan kantor tetap aman.',
            'contoh' => 'melatih pegawai agar tidak asal membuka tautan yang mencurigakan.',
            'ditulis' => 'Nama program, kegiatannya, siapa yang mengurus, target, dan hasilnya setiap tahun.',
            'tersambung' => ['Risiko: risiko besar langsung menjadi satu program kerja (otomatis)', 'Aset dan Risiko: jumlahnya tampil di halaman ini (otomatis)', 'Pengetahuan: kegiatan berbagi ilmu ikut dihitung (otomatis)'],
        ],
        'risiko' => [
            'label' => 'Manajemen Risiko',
            'fungsi' => 'Mencatat hal buruk yang mungkin terjadi, lalu menentukan cara menanganinya.',
            'contoh' => 'website disusupi iklan judi.',
            'ditulis' => 'Kejadiannya, aset yang kena, penyebab, titik lemah, seberapa mungkin, seberapa parah, cara menanganinya, dan sudah sampai mana.',
            'tersambung' => ['Keamanan, Perubahan, Data: kalau risikonya besar, masing-masing mendapat satu tugas (otomatis)', 'Layanan: tiket dibuat kalau asetnya dipakai layanan (otomatis)', 'Aset dan SDM: aset penting atau orang berisiko membuat risikonya naik (otomatis)'],
        ],
        'perubahan' => [
            'label' => 'Manajemen Perubahan',
            'fungsi' => 'Mencatat setiap perubahan, supaya tidak asal diubah dan layanan tidak terganggu.',
            'contoh' => 'website mau diperbarui. Rencananya dicatat dan diputuskan dulu.',
            'ditulis' => 'Apa yang diubah, besar atau kecil, seberapa mendesak, keputusannya, kapan selesai, dan siapa yang mengurus.',
            'tersambung' => ['Risiko: risiko besar langsung menjadi usulan perubahan (otomatis)', 'Aset: perubahan masuk ke riwayat aset yang dipilih', 'SDM: kode yang sama berarti orang yang sama'],
        ],
        'layanan' => [
            'label' => 'Manajemen Layanan',
            'fungsi' => 'Daftar layanan milik kantor, beserta keluhan penggunanya.',
            'contoh' => 'e-Surat tidak bisa dibuka, lalu petugas membuat tiket.',
            'ditulis' => 'Layanan: nama, pengelola, cara memakai, dan targetnya. Tiket: siapa yang melapor, masalahnya, dan penyelesaiannya. Evaluasi: seberapa sering terganggu dan seberapa cepat pulih.',
            'tersambung' => ['Risiko: risiko besar pada aset layanan menjadi tiket (otomatis)', 'Aset: tiket pada aset penting didahulukan (otomatis)', 'Pengetahuan: panduan yang cocok disarankan ke petugas (otomatis)'],
        ],
        'data_informasi' => [
            'label' => 'Manajemen Data & Informasi',
            'fungsi' => 'Mencatat masalah pada data, supaya data tetap benar dan aman.',
            'contoh' => 'isi data di website diubah oleh orang yang tidak berhak.',
            'ditulis' => 'Apa yang terjadi, data yang mana, seberapa bahaya, mau ditangani bagaimana, kapan selesai, dan siapa yang mengurus.',
            'tersambung' => ['Risiko: risiko besar langsung menjadi catatan untuk diperiksa (otomatis)', 'Aset: catatan masuk ke riwayat aset yang dipilih', 'SDM: kode yang sama berarti orang yang sama'],
        ],
        'bukti' => [
            'label' => 'Evaluasi Bukti Dukung',
            'fungsi' => 'Memeriksa dokumen bukti sebelum dinilai asesor. Mirip latihan sebelum ujian.',
            'contoh' => 'sebuah dokumen mengaku Level 3. Ada satu syarat yang belum ada, jadi nilainya Level 2.',
            'ditulis' => 'Unggah PDF, lalu pilih level yang dirasa sudah dicapai. Sistem mencocokkan isinya dengan daftar syarat, lalu keluar nilai Level 1–5 dan apa saja yang masih kurang.',
            'tersambung' => ['Admin modul memutuskan: diterima, diperbaiki, atau ditolak', 'Data di modul tidak berubah karena penilaian ini'],
        ],
        'tanya' => [
            'label' => 'Tanya Dokumen',
            'fungsi' => 'Tidak perlu membaca panduan satu per satu. Cukup bertanya dengan bahasa sehari-hari.',
            'contoh' => '"Berapa lama server baru selesai disiapkan?" Jawabannya 5 hari kerja, diambil dari SOP halaman 2.',
            'ditulis' => 'Jawaban selalu menyebut dokumen dan halamannya, dan PDF aslinya bisa dibuka. Kalau jawabannya tidak ada di dokumen, sistem bilang tidak ditemukan.',
            'tersambung' => ['Pengetahuan: memakai PDF yang diunggah di Aset Pengetahuan', 'Asisten dan Analisis tiket: memakai PDF yang sama, jadi cukup sekali unggah'],
        ],
        'tiket' => [
            'label' => 'Tiket Helpdesk dan Analisis tiket',
            'fungsi' => 'Tiket adalah catatan keluhan, gangguan, atau permintaan dari pengguna. Analisis tiket membantu petugas menentukan mana yang harus ditangani lebih dulu.',
            'contoh' => 'e-Surat tidak bisa dibuka banyak orang. Sistem mengusulkan prioritas P1, paling mendesak.',
            'ditulis' => 'Sistem mengusulkan jenis masalah, seberapa mendesak, langkah awal, panduan yang cocok, dan contoh balasan. Petugas boleh mengubah semuanya.',
            'tersambung' => ['Risiko: risiko besar pada aset layanan menjadi tiket (otomatis)', 'Aset: tiket pada aset penting didahulukan (otomatis)', 'Pengetahuan: panduan yang cocok disarankan ke petugas (otomatis)'],
        ],
    ];

    /** @return array{id: string, answer: string, actions: array}|null */
    public static function find(string $flat): ?array
    {
        foreach (self::ENTRIES as $id => $entry) {
            if (isset($entry['skip']) && preg_match($entry['skip'], $flat)) {
                continue;
            }
            foreach ($entry['match'] as $pattern) {
                if (preg_match($pattern, $flat)) {
                    return ['id' => $id, 'answer' => $entry['answer'], 'actions' => self::actions($entry['actions'] ?? [])];
                }
            }
        }

        return null;
    }

    /** Penjelasan satu modul atau fitur dalam format slide: fungsi, contoh, isian, sambungan. */
    public static function explain(string $key): ?string
    {
        $m = self::MODULES[$key] ?? null;
        if ($m === null) {
            return null;
        }

        return "{$m['label']}: {$m['fungsi']}\n\n"
            . "Contoh: {$m['contoh']}\n\n"
            . ($key === 'bukti' || $key === 'tiket' || $key === 'tanya' ? 'Cara kerjanya' : 'Yang ditulis') . ": {$m['ditulis']}\n\n"
            . "Tersambung ke:\n• " . implode("\n• ", $m['tersambung']);
    }

    /** @return array<int, array{label: string, url: string, point?: bool}> */
    private static function actions(array $rows): array
    {
        return array_map(fn ($a) => ['label' => $a[0], 'url' => route($a[1], $a[2])] + ($a[3] ? ['point' => true] : []), $rows);
    }
}
