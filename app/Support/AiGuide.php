<?php

namespace App\Support;

/**
 * Isi pop-out "Panduan AI" untuk tiga fitur AI. Dipisah dari tampilan supaya
 * mudah diperbarui saat cara kerja fitur berubah. 'who' menandai siapa yang
 * mengerjakan langkah: petugas, aturan (mesin aturan aplikasi), atau ai.
 */
class AiGuide
{
    /** @return array{title: string, intro: string, flow: array<int, array{who: string, text: string}>, steps: array<int, string>, example: string, faq: array<int, array{q: string, a: string}>}|null */
    public static function for(string $feature): ?array
    {
        return self::GUIDES[$feature] ?? null;
    }

    public const WHO = ['petugas' => 'Petugas', 'aturan' => 'Aturan', 'ai' => 'AI'];

    private const GUIDES = [
        'bukti' => [
            'title' => 'Evaluasi Bukti Dukung',
            'intro' => 'Fitur ini memeriksa PDF bukti dukung dari OPD terhadap rubrik tingkat kematangan SPBE, lalu memberi hasil level, skor, dan daftar unsur yang masih kurang. Hasilnya bahan pertimbangan; keputusan akhir tetap di admin modul.',
            'flow' => [
                ['who' => 'petugas', 'text' => 'OPD atau petugas mengunggah PDF, lalu memilih modul dan level yang diklaim.'],
                ['who' => 'aturan', 'text' => 'Teks setiap halaman dibaca. Halaman yang hampir tanpa teks dianggap hasil scan dan dokumennya tidak dinilai.'],
                ['who' => 'aturan', 'text' => 'Isi dokumen dicocokkan dengan rubrik modul. Setiap level punya beberapa unsur, dan setiap unsur punya kata kunci yang dicari di PDF.'],
                ['who' => 'aturan', 'text' => 'Hasil adalah level tertinggi yang semua unsurnya, sampai level itu, ditemukan. Skor adalah persentase unsur yang ditemukan sampai level yang diklaim.'],
                ['who' => 'aturan', 'text' => 'Keabsahan diperiksa: nomor dokumen, tanggal penetapan, NIP penandatangan, keterangan tanda tangan elektronik, dan tanda tangan digital yang tertanam di berkas.'],
                ['who' => 'ai', 'text' => 'AI (llama3.2) membaca isi dokumen dan hasil pemeriksaan, lalu menulis ringkasan dan catatan kekurangan. AI tidak bisa mengubah level atau skor.'],
                ['who' => 'petugas', 'text' => 'Admin modul membaca hasilnya dan memutuskan: Diterima, Perlu perbaikan, atau Ditolak.'],
            ],
            'steps' => [
                'Buka menu Evaluasi Bukti Dukung, lalu klik Unggah.',
                'Pilih modul yang dinilai dan level yang diklaim OPD.',
                'Isi judul dan OPD pengirim bila perlu. Judul boleh dikosongkan; nama berkas akan dipakai sebagai judul.',
                'Pilih berkas PDF (maksimal 10 MB), lalu klik Unggah & Nilai. Hasil muncul dalam beberapa detik.',
                'Baca bagian atas panel: Klaim, Hasil, dan Skor. Daftar "Belum ada" menunjukkan unsur yang membuat level belum tercapai.',
                'Baris keabsahan menunjukkan nomor, tanggal, NIP, keterangan TTE, dan apakah tanda tangan digitalnya tertanam.',
                'Ketuk Rincian per level untuk melihat centang setiap unsur, dan Catatan AI untuk membaca ringkasan dari AI.',
                'Bila dokumen diperbaiki atau rubrik berubah, klik Nilai ulang.',
                'Admin modul memilih keputusan, menulis catatan untuk OPD bila perlu, lalu klik Simpan.',
            ],
            'example' => 'Pedoman Manajemen Risiko yang mengklaim Level 3 tetapi belum memuat matriks mitigasi akan mendapat hasil Level 2 dengan skor 86. Versi revisi yang sudah memuat matriks mitigasi mendapat Level 3 dengan skor 100.',
            'faq' => [
                ['q' => 'Apakah isi dokumen dikirim ke internet?', 'a' => 'Tidak. Pembacaan PDF dan AI berjalan di server sendiri. Berkas disimpan di folder privat yang hanya bisa dibuka pengguna yang sudah login.'],
                ['q' => 'Kenapa hasilnya bisa berbeda dengan penilaian manual?', 'a' => 'Sistem mencari kata kunci rubrik. Dokumen yang memakai istilah lain bisa terlewat, jadi admin tetap perlu membaca dokumennya. Rubrik masih draf dan dapat disesuaikan pengelola aplikasi.'],
                ['q' => 'Bagaimana dengan PDF hasil scan atau cap basah?', 'a' => 'Isinya belum bisa dibaca. Minta PDF asli dari aplikasi pembuatnya, atau periksa dokumennya secara manual.'],
                ['q' => 'Apa arti "TTE tertanam" yang tidak dicentang?', 'a' => 'Dokumen menulis keterangan tanda tangan elektronik, tetapi berkasnya tidak membawa tanda tangan digital. Biasanya karena PDF dicetak ulang. Minta berkas asli dari sistem TTE.'],
                ['q' => 'Bagaimana kalau AI sedang tidak aktif?', 'a' => 'Level, skor, dan keabsahan tetap keluar karena dihitung aturan. Hanya Catatan AI yang tidak muncul.'],
                ['q' => 'Siapa yang boleh memutuskan?', 'a' => 'Hanya admin modul yang bersangkutan. Pengguna lain melihat status "Menunggu admin".'],
            ],
        ],

        'tanya' => [
            'title' => 'Tanya Dokumen',
            'intro' => 'Tanya Dokumen menjawab pertanyaan dari isi SOP, kebijakan, dan dokumen lain di Manajemen Pengetahuan. Caranya disebut RAG: sistem mencari dulu potongan dokumen yang relevan, baru AI menyusun jawaban hanya dari potongan itu, lengkap dengan sumber dan halamannya.',
            'flow' => [
                ['who' => 'petugas', 'text' => 'Dokumen PDF diunggah lewat tab Aset Pengetahuan → Tambah Baru, atau banyak sekaligus oleh pengelola aplikasi.'],
                ['who' => 'aturan', 'text' => 'Teks setiap halaman diambil lalu dipotong per ±900 karakter, sedikit tumpang tindih supaya kalimat tidak terputus.'],
                ['who' => 'ai', 'text' => 'Model bge-m3 mengubah setiap potongan menjadi vektor makna (1.024 angka). Model llama3.2 membuat tag dan ringkasan dokumen.'],
                ['who' => 'petugas', 'text' => 'Pengguna mengetik pertanyaan dengan bahasa sehari-hari.'],
                ['who' => 'ai', 'text' => 'Pertanyaan juga diubah menjadi vektor makna, lalu dicari potongan yang maknanya paling mirip. Hasilnya digabung dengan pencarian kata yang persis sama dan kecocokan dengan judul dokumen.'],
                ['who' => 'aturan', 'text' => 'Hanya potongan yang skornya dekat dengan potongan terbaik yang dipakai. Bila tidak ada yang cukup mirip, jawabannya "tidak ditemukan".'],
                ['who' => 'ai', 'text' => 'llama3.2 menyusun jawaban hanya dari potongan tersebut, tanpa menambah informasi dari luar dokumen.'],
                ['who' => 'petugas', 'text' => 'Jawaban tampil bersama dokumen sumber, nomor halaman, dan tombol Buka PDF untuk dicek.'],
            ],
            'steps' => [
                'Buka Manajemen Pengetahuan, lalu tab Tanya Dokumen.',
                'Ketik pertanyaan seperti bertanya ke rekan kerja. Tidak perlu memakai kata yang persis sama dengan isi dokumen.',
                'Klik Tanya. Jawaban muncul dalam beberapa detik.',
                'Periksa daftar Sumber di bawah jawaban. Klik Buka PDF untuk membuka dokumen tepat di halaman yang dirujuk.',
                'Bila jawabannya kurang tepat, ulangi dengan kata yang lebih spesifik, misalnya sebut nama OPD atau nama SOP-nya.',
                'Untuk menambah pengetahuan, buka tab Aset Pengetahuan → Tambah Baru, isi judul, lalu unggah PDF. Beberapa detik kemudian dokumen sudah diberi tag, ringkasan, dan bisa ditanya.',
            ],
            'example' => 'Contoh pertanyaan: "Berapa lama server baru selesai disiapkan?", "Saya lupa kata sandi, bagaimana cara resetnya?", "Website disusupi iklan judi online, apa yang harus dilakukan?"',
            'faq' => [
                ['q' => 'Apakah AI bisa mengarang jawaban?', 'a' => 'AI hanya diberi potongan dokumen yang ditemukan dan diminta menjawab dari situ. Bila tidak ada potongan yang relevan, sistem menjawab tidak ditemukan. Untuk keputusan penting, tetap cek sumbernya.'],
                ['q' => 'Berapa banyak dokumen yang bisa ditampung?', 'a' => 'Ribuan dokumen. Bila potongannya sudah lebih dari 3.000, sistem memilih dulu dokumen yang paling relevan sebelum memeriksa potongannya, sehingga jawaban tetap cepat.'],
                ['q' => 'Dokumen apa yang tidak bisa ditanya?', 'a' => 'PDF hasil scan tanpa teks dan aset pengetahuan yang hanya berupa tautan. Unggah PDF asli yang teksnya bisa disalin.'],
                ['q' => 'Apakah semua dokumen bisa ditanya semua pengguna?', 'a' => 'Saat ini ya. Jangan unggah dokumen rahasia sebelum pembatasan per tingkat akses diterapkan.'],
                ['q' => 'Bagaimana kalau AI sedang tidak aktif?', 'a' => 'Pencarian tetap jalan dengan kata kunci dan menampilkan potongan dokumen yang paling relevan, tanpa jawaban ringkas.'],
                ['q' => 'Apakah pertanyaan dan dokumen dikirim ke internet?', 'a' => 'Tidak. Kedua model AI berjalan di server sendiri, dan setiap jawaban dicatat di log.'],
            ],
        ],

        'triase' => [
            'title' => 'Analisis AI Tiket Helpdesk',
            'intro' => 'Analisis AI membantu petugas helpdesk memilah tiket: kategori, dampak, urgensi, prioritas P1–P4, langkah awal penanganan, dan draf balasan untuk pelapor. Prioritas selalu dihitung dengan aturan; AI hanya membantu bagian bahasa.',
            'flow' => [
                ['who' => 'petugas', 'text' => 'Petugas mengisi layanan, nama pemohon, dan isi laporan, lalu klik Analisis AI.'],
                ['who' => 'aturan', 'text' => 'Kategori ditentukan dari kata kunci di isi laporan, misalnya "judi online" menjadi Insiden Keamanan dan "akun terkunci" menjadi Akun & Hak Akses.'],
                ['who' => 'ai', 'text' => 'Bila kata kuncinya kurang jelas, AI membantu memilih kategori.'],
                ['who' => 'aturan', 'text' => 'Dampak dan urgensi diambil dari bawaan kategori, dinaikkan oleh kata penanda seperti "masyarakat" atau "hari ini", dan naik satu tingkat bila layanan bergantung pada aset berkritikalitas tinggi.'],
                ['who' => 'aturan', 'text' => 'Prioritas P1–P4 diambil dari tabel dampak × urgensi, lengkap dengan target waktu tanggapan dan penyelesaian.'],
                ['who' => 'ai', 'text' => 'AI menulis kalimat pembuka draf balasan. Daftar data yang perlu dilengkapi pelapor dan peringatan kata sandi berasal dari templat.'],
                ['who' => 'petugas', 'text' => 'Petugas memeriksa, mengubah bila perlu, lalu menyimpan tiket.'],
            ],
            'steps' => [
                'Buka Manajemen Layanan, tab Tiket Helpdesk, lalu klik Tiket Baru.',
                'Pilih layanan yang terganggu dan isi nama pemohon.',
                'Tulis isi laporan selengkap mungkin: apa yang terjadi, sejak kapan, dan siapa yang terdampak.',
                'Klik Analisis AI. Kategori, dampak, urgensi, prioritas, langkah awal, dan draf balasan terisi otomatis.',
                'Baca alasan di bawah prioritas untuk melihat dari mana hasil itu didapat.',
                'Ubah kategori, dampak, atau urgensi bila menurut petugas tidak sesuai; prioritas mengikuti otomatis.',
                'Periksa draf balasan sebelum dikirim ke pelapor, lalu klik Simpan.',
            ],
            'example' => '"Halaman depan website resmi berisi iklan judi online sejak pagi" menjadi Insiden Keamanan, P1 Kritis, tanggapan pertama 30 menit. "Mohon dibuatkan akun baru untuk pegawai pindahan" menjadi Akun & Hak Akses, P4 Rendah.',
            'faq' => [
                ['q' => 'Apakah AI yang menentukan prioritas?', 'a' => 'Tidak. Prioritas dihitung dari tabel dampak × urgensi. AI hanya membantu memilih kategori untuk laporan yang kurang jelas dan menulis kalimat pembuka balasan.'],
                ['q' => 'Kenapa tiket ini menjadi P1?', 'a' => 'Lihat baris alasan di bawah prioritas. Biasanya karena kategorinya berdampak tinggi (misalnya insiden keamanan), ada kata penanda dampak luas, atau layanan bergantung pada aset kritis.'],
                ['q' => 'Bolehkah hasil analisis diubah?', 'a' => 'Boleh. Semua isian tetap bisa diubah petugas sebelum disimpan.'],
                ['q' => 'Bagaimana kalau AI sedang tidak aktif?', 'a' => 'Kategori dan prioritas tetap keluar dari aturan. Draf balasan memakai templat tanpa kalimat pembuka dari AI.'],
                ['q' => 'Apakah isi tiket dikirim ke luar?', 'a' => 'Tidak. Analisis berjalan di server sendiri, dan setiap hasil AI dicatat di log.'],
            ],
        ],
    ];
}
