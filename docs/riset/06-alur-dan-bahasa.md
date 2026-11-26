# 06 · Dasar Alur Data dan Bahasa Tampilan — DRAF, BELUM DITINJAU

Disusun 19 November 2026 dari teks peraturan di JDIH BPK dan JDIH Kemenkeu,
untuk penataan ulang kata-kata, tampilan, dan alur data di 8 halaman modul.

**Perubahan di aplikasi sudah dibuat berdasarkan dokumen ini.** Isinya tetap
draf sampai ditinjau. Rujukan tidak ditulis di layar; Asisten menjawabnya bila
ditanya.

## Sumber

| Kode | Sumber | Tautan |
|---|---|---|
| D1 | Perpres 95/2018 tentang Sistem Pemerintahan Berbasis Elektronik — Pasal 2 (prinsip SPBE), Pasal 46–54 (tujuan dan proses delapan manajemen SPBE) | https://peraturan.bpk.go.id/Details/96913/perpres-no-95-tahun-2018 |
| D2 | Permenkomdigi 6/2025 tentang Standar Teknis dan Prosedur Pembangunan dan Pengembangan Aplikasi SPBE — Pasal 4, Pasal 9 ayat (5), Pasal 17, Pasal 19 | https://peraturan.bpk.go.id/Details/317343/permenkomdigi-no-6-tahun-2025 |
| D3 | Perpres 63/2019 tentang Penggunaan Bahasa Indonesia — Pasal 2, Pasal 25, Pasal 28 | https://peraturan.bpk.go.id/Details/121661 |
| D4 | UU 25/2009 tentang Pelayanan Publik — Pasal 4 huruf l | https://jdih.kemenkeu.go.id/api/download/fulltext/2009/25TAHUN2009UU.htm |
| D5 | Permenkomdigi 12/2026 tentang Pedoman Manajemen Aset TIK SPBE dan Pedoman Manajemen Layanan SPBE — Lampiran II (katalog layanan, dampak Mayor/Minor) | https://peraturan.bpk.go.id/Details/353730/permenkomdigi-no-12-tahun-2026 |

## Jawaban singkat

- **Alur data dan integrasi antarmodul wajib dirancang dan dijelaskan.**
  Rancang bangun aplikasi SPBE paling kurang memuat alur proses aplikasi,
  keterhubungan fungsi dan basis data, rancangan antarmuka dan navigasi
  layar, kendali validasi dan otorisasi, serta rancangan integrasi dengan
  aplikasi lain (D2 Pasal 9 ayat (5)). Huruf g (aplikasi lain) belum ada di
  aplikasi ini; alur antarmodul memakai huruf b dan c. Karena itu setiap
  modul kini punya tombol Alur Data, dan Panduan memuat tabel "Setelah Anda
  menyimpan, datanya ke mana".
- **Bahasa di layar wajib bahasa Indonesia yang baik dan benar.** Bahasa
  Indonesia wajib dipakai dalam sistem informasi pelayanan dan komunikasi
  resmi di lingkungan kerja pemerintah. Bahasa asing hanya boleh sebagai
  padanan atau terjemahan untuk mempermudah pemahaman (D3 Pasal 2, 25, 28).
  Karena itu istilah seperti Likelihood, Impact, Owner, Residual Risk, Uptime,
  MTTR, SLA, PIC, Legacy, dan "1 week ago" diganti.
- **Pengguna berhak atas bantuan dan pertanyaan yang sering diajukan.**
  Aplikasi SPBE wajib menyediakan layanan bantuan (helpdesk) untuk proses
  bisnis dan teknis, lewat berbagai saluran, dilengkapi pertanyaan yang sering
  diajukan (D2 Pasal 17 ayat (3)–(6)). Asisten dipakai sebagai salah satu
  saluran elektronik itu.

## Permenkomdigi 6/2025: yang diminta dari aplikasi (D2)

- **Pasal 4 huruf c dan e:** aplikasi dapat diakses lewat web dan mobile,
  serta menyediakan fitur interoperabilitas untuk integrasi antaraplikasi.
- **Pasal 9 ayat (5)** — rancang bangun paling kurang memuat:
  a. pemodelan rancang bangun; b. alur proses aplikasi; c. pemetaan dan
  keterhubungan fungsi dan basis data; d. pemetaan hak akses dan peran;
  e. rancangan antarmuka pengguna dan navigasi layar ke layar sesuai
  tingkatan pengguna; f. rancangan kendali internal untuk validasi,
  otorisasi, dan pencatatan aktivitas; g. rancangan integrasi dengan
  aplikasi lain.
- **Pasal 17 ayat (3)–(6):** layanan pengguna untuk bantuan proses bisnis dan
  bantuan teknis, berbentuk layanan bantuan (helpdesk), dengan berbagai
  saluran elektronik maupun nonelektronik, dan dilengkapi pertanyaan yang
  sering diajukan.
- **Pasal 19 ayat (2) huruf d:** dokumentasi memuat manual penggunaan bagi
  administrator, manual penggunaan bagi pengguna, dan manual penanganan
  masalah.

## Perpres 95/2018: tujuan dan proses tiap manajemen (D1)

Prinsip SPBE (Pasal 2) antara lain **keterpaduan** (pengintegrasian sumber
daya), **akuntabilitas** (kejelasan fungsi dan pertanggungjawaban), dan
**interoperabilitas** (koordinasi dan kolaborasi antarproses bisnis dan
antarsistem elektronik untuk pertukaran data).

| Manajemen | Pasal | Tujuan | Proses |
|---|---|---|---|
| Risiko | 47 | menjamin keberlangsungan SPBE dengan meminimalkan dampak risiko | identifikasi, analisis, pengendalian, pemantauan, evaluasi |
| Keamanan informasi | 48 | menjamin keberlangsungan SPBE dengan meminimalkan dampak risiko keamanan informasi | penetapan ruang lingkup, penetapan penanggung jawab, perencanaan, dukungan pengoperasian, evaluasi kinerja, perbaikan berkelanjutan |
| Data | 49 | data yang akurat, mutakhir, terintegrasi, dan dapat diakses | arsitektur data, data induk, data referensi, basis data, kualitas data |
| Aset TIK | 50 | ketersediaan dan optimalisasi pemanfaatan aset TIK | perencanaan, pengadaan, pengelolaan, penghapusan perangkat keras dan perangkat lunak |
| SDM | 51 | keberlangsungan dan peningkatan mutu layanan; ketersediaan dan kompetensi SDM | perencanaan, pengembangan, pembinaan, pendayagunaan |
| Pengetahuan | 52 | meningkatkan kualitas layanan dan mendukung pengambilan keputusan | pengumpulan, pengolahan, penyimpanan, penggunaan, alih pengetahuan |
| Perubahan | 53 | menjamin keberlangsungan dan kualitas layanan lewat pengendalian perubahan | perencanaan, analisis, pengembangan, implementasi, pemantauan dan evaluasi |
| Layanan SPBE | 54 | keberlangsungan dan kualitas layanan kepada pengguna | pelayanan pengguna (keluhan, gangguan, masalah, permintaan, perubahan), pengoperasian layanan, pengelolaan aplikasi |

Kalimat pembuka tiap modul di pop-out Alur Data diturunkan dari tujuan di
atas dan ditulis dengan bahasa sehari-hari. Dasar per alur otomatis (misalnya
risiko Tinggi/Kritis membuat tindak lanjut di modul lain) ada di berkas 04
bagian 2.

## Perubahan di aplikasi dan dasarnya

| Perubahan | Dasar | Status |
|---|---|---|
| Tombol Alur Data di tiap modul: "Dikirim ke modul lain" dan "Datang dari modul lain", masing-masing ditandai Otomatis atau Bila kolomnya diisi | D2 Pasal 9 ayat (5) huruf b dan c; D1 Pasal 2 (keterpaduan, interoperabilitas) | ✅ Sesuai |
| Halaman Panduan: urutan kerja, tabel "Setelah Anda menyimpan, datanya ke mana", alur per modul, verifikasi dan peran | D2 Pasal 19 ayat (2) huruf d (manual penggunaan); D2 Pasal 9 ayat (5) huruf b, d | ✅ Sesuai |
| Pesan setelah menyimpan menyebut status verifikasi, aset tujuan, dan tindak lanjut yang benar-benar dibuat | D2 Pasal 9 ayat (5) huruf f (kendali validasi, otorisasi, pencatatan); D4 Pasal 4 huruf l (kemudahan) | 🟡 Tafsiran: aturan meminta rancangannya ada, bukan cara menampilkannya |
| Daftar aktivitas menulis asal kejadian ("dari Manajemen Risiko") | D2 Pasal 9 ayat (5) huruf f (pencatatan aktivitas) | 🟡 Tafsiran |
| Tutorial tiap modul ditutup dengan langkah "Ke mana datanya" | D2 Pasal 19 ayat (2) huruf d | 🟡 Tafsiran |
| Asisten menjawab alur data, istilah, dan dasar aturan; contoh pertanyaan di Panduan | D2 Pasal 17 ayat (4)–(6) (helpdesk, banyak saluran, pertanyaan yang sering diajukan) | 🟡 Asisten bukan pengganti helpdesk petugas |
| Istilah asing diganti: Kemungkinan, Dampak, Unit Pemilik, Pengelola Layanan, Tingkat Risiko Sisa, Ketersediaan Tercapai, Rata-rata Waktu Pemulihan, Penanggung Jawab, Kode Lama, Kolom Tambahan, waktu relatif berbahasa Indonesia | D3 Pasal 25 ayat (2) huruf d, Pasal 28; istilah asing boleh tetap muncul sebagai padanan di penjelasan (D3 Pasal 25 ayat (3)) | ✅ Sesuai |
| "Target SLA" menjadi "Target Ketersediaan" | D5 Lampiran II (isian katalog layanan: target ketersediaan) | ✅ Sesuai |
| Saran kategori perubahan "Major" menjadi "Mayor" | D5 Lampiran II (dampak Mayor/Minor); D3 Pasal 2 ayat (4) (kaidah ejaan) | ✅ Sesuai |
| Nama modul disamakan: "Manajemen Data & Informasi" (sebelumnya juga "Manajemen Data Informasi") | D1 Pasal 2 ayat (6) (kejelasan fungsi) | 🟡 Tafsiran |
| Judul tabel dan label tidak lagi huruf kapital semua; subjudul modul, ikon besar transparan, label "AKTIF", lingkaran bernomor, dan efek tombol membesar dihapus | D4 Pasal 4 huruf l (kemudahan); D2 Pasal 9 ayat (5) huruf e | ⚪ Tidak ada aturan khusus soal gaya huruf dan hiasan |
| Kotak isian form diberi garis dan jarak dalam (sebelumnya tidak terlihat) | Perbaikan galat tampilan | ⚪ Bukan soal aturan |

## Yang tidak ditemukan

- **Pedoman desain antarmuka nasional.** Belum ditemukan standar desain atau
  sistem desain resmi yang terbit untuk aplikasi SPBE (mis. dari INA Digital).
  D2 hanya mewajibkan rancangan antarmuka dan navigasi ada di rancang bangun.
- **Aturan huruf kapital pada tampilan aplikasi.** EYD Edisi V mengatur huruf
  kapital di awal kalimat, nama, dan judul, bukan gaya huruf antarmuka.
  Penghapusan huruf kapital semua adalah pilihan keterbacaan.

## Keputusan

- 19 November 2026: bagian "Model Korelasi 4 Lapisan" dan "Contoh Alur
  Kerja: Peninjauan Aplikasi" di Panduan lama tidak dikembalikan. Keduanya
  tidak menjelaskan alur data yang benar-benar terjadi di aplikasi.

Pertanyaan untuk peninjau:

1. Apakah Diskominfo punya rancang bangun atau manual aplikasi yang harus
   diikuti formatnya (D2 Pasal 9 dan 19)? Halaman Panduan bisa disesuaikan
   dengan format itu.
2. Apakah istilah "Level Risiko Inheren" perlu diganti? Istilah ini
   dipertahankan karena berasal dari templat Excel resmi instansi.
