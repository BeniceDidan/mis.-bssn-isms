# 04 · Rujukan per Modul dan Audit Kesesuaian — DRAF, BELUM DITINJAU

Dokumen ini memasangkan setiap aturan dan fitur di aplikasi BSSN ISMS dengan
rujukan resminya, lalu menilai apakah logika aplikasi sudah sesuai.
Disusun 10 November 2026 dari teks peraturan (JDIH BPK, peraturan.go.id,
JDIH kementerian/lembaga) dan kode di branch `fitur-ai`.

**Belum ada kode yang diubah berdasarkan dokumen ini.** Temuan "perlu
penyesuaian" menunggu keputusan peninjau.

Arti status:

| Status | Arti |
|---|---|
| ✅ Sesuai | Logika aplikasi sejalan dengan rujukan. |
| 🟡 Sesuai sebagian | Arahnya benar, tetapi ada isian/proses yang belum ada. |
| 🔶 Perlu penyesuaian | Berbeda dengan rujukan resmi yang berlaku. |
| ⚪ Belum ada rujukan resmi | Tidak diatur; aplikasi memakai praktik baik/standar internasional. |

---

## 1. Daftar sumber

### Peraturan (status dicek 10 Nov 2026)

| Kode | Peraturan | Status | Tautan |
|---|---|---|---|
| P1 | Perpres 95/2018 tentang SPBE (Pasal 46–54: Manajemen SPBE) | Berlaku | https://peraturan.bpk.go.id/Details/96913/perpres-no-95-tahun-2018 |
| P2 | PermenPANRB 5/2020 tentang Pedoman Manajemen Risiko SPBE | Berlaku | https://peraturan.bpk.go.id/Details/143664/permen-pan-rb-no-5-tahun-2020 |
| P3 | Peraturan BSSN 4/2021 tentang Pedoman Manajemen Keamanan Informasi SPBE dan Standar Teknis & Prosedur Keamanan SPBE | Berlaku | https://peraturan.go.id/files/bn541-2021.pdf · ringkasan pasal: https://pasal.id/peraturan/perban/peraturan-bssn-no-4-tahun-2021 |
| P4 | Permen PPN/Kepala Bappenas 16/2020 tentang Manajemen Data SPBE | Berlaku (direncanakan revisi) | https://data.go.id/regulation/peraturan-menteri-ppnkepala-bappenas-nomor-16-tahun-2020-tentang-manajemen-data-sistem-pemerintahan-berbasis-elektronik/12 |
| P5 | Perpres 39/2019 tentang Satu Data Indonesia | Berlaku | https://peraturan.bpk.go.id/Details/108813/perpres-no-39-tahun-2019 |
| P6 | **Permenkomdigi 12/2026** tentang Pedoman Manajemen Aset TIK SPBE dan Pedoman Manajemen Layanan SPBE (ditetapkan 7 Agu 2026, berlaku 11 Agu 2026) | Berlaku | https://peraturan.bpk.go.id/Details/353730/permenkomdigi-no-12-tahun-2026 |
| P7 | Peraturan BRIN 2/2024 tentang Pedoman Manajemen Pengetahuan SPBE | Berlaku | https://peraturan.bpk.go.id/Download/339522/peraturan%20brin%20no%202%20tahun%202024.pdf |
| P8 | PermenPANRB 8/2026 tentang Evaluasi Kinerja Pemerintah Digital (mencabut PermenPANRB 59/2020) | Berlaku 5 Jun 2026 | https://peraturan.bpk.go.id/Details/351330/permen-panrb-no-8-tahun-2026 |
| P9 | Peraturan BSSN 8/2020 tentang Sistem Pengamanan dalam Penyelenggaraan Sistem Elektronik | Berlaku | https://peraturan.bpk.go.id/Details/174285 |
| P10 | Peraturan BSSN 10/2023 tentang Pengukuran Tingkat Kematangan Keamanan Siber (dasar IKAS) | Berlaku | https://peraturan.bpk.go.id/Download/350568/%5BSALINAN%5D-Peraturan-BSSN-Nomor-10-Tahun-2023_signed.pdf |
| P11 | UU 27/2022 tentang Pelindungan Data Pribadi | Berlaku | https://jdih.kemenkeu.go.id/download/46c17b53-4a45-411c-9b76-c6799e5769fc/UU_Nomor_27_Tahun_2022%20Perlindungan%20Data%20Pribadi.pdf |
| P12 | SE Menkominfo 9/2023 tentang Etika Kecerdasan Artifisial | Pedoman etika | https://jdih.komdigi.go.id/produk_hukum/view/id/883/t/surat+edaran+menteri+komunikasi+dan+informatika+nomor+9+tahun+2023 |
| P13 | Permendagri 19/2016 tentang Pedoman Pengelolaan Barang Milik Daerah, terakhir diubah Permendagri 7/2024 | Berlaku | https://peraturan.bpk.go.id/Details/137669/permendagri-no-19-tahun-2016 · https://peraturan.bpk.go.id/Details/300030 |

Belum terbit / belum ditemukan:

- **Pedoman Manajemen SDM SPBE** (amanat P1 Pasal 51 ayat 6, oleh MenPANRB) dan
  **Pedoman Manajemen Perubahan SPBE** (amanat P1 Pasal 53 ayat 5, oleh MenPANRB):
  tidak ditemukan peraturan yang sudah terbit. Materi evaluasi SPBE pemda tetap
  meminta dokumen pedoman instansi untuk keduanya.
- **Rancangan Perpres Peta Jalan AI Nasional dan Rancangan Perpres Etika AI**: per
  30 Okt 2026 masih menunggu tanda tangan Presiden
  (https://www.cnbcindonesia.com/tech/20261030162941-37-781009/ri-atur-ai-sesuai-risiko-komdigi-beberkan-isi-regulasinya).

### Standar (dirujuk karena P1 Pasal 46 ayat 3–4: Manajemen SPBE berpedoman pada SNI, atau standar internasional bila SNI belum ada)

| Kode | Standar | Tautan |
|---|---|---|
| S1 | ISO 31000:2018 Manajemen risiko (diadopsi sebagai SNI ISO 31000) | https://www.iso.org/standard/65694.html |
| S2 | ISO/IEC 27001:2022 Sistem manajemen keamanan informasi | https://www.iso.org/standard/27001 |
| S3 | ISO/IEC 27005:2022 Manajemen risiko keamanan informasi | https://www.iso.org/standard/80585.html |
| S4 | ISO/IEC 20000-1:2018 Sistem manajemen layanan TI | https://www.iso.org/standard/70636.html |
| S5 | NIST FIPS 199 Standards for Security Categorization (prinsip "high-water mark") | https://csrc.nist.gov/pubs/fips/199/final |
| S6 | ITIL 4 (praktik Incident Management, Change Enablement, Service Desk) | https://www.axelos.com/certifications/itil-service-management |

---

## 2. Kerangka umum

| Fitur aplikasi | Rujukan | Status | Catatan |
|---|---|---|---|
| 8 modul: Risiko, Keamanan Informasi, Data, Aset TIK, SDM, Pengetahuan, Perubahan, Layanan | P1 Pasal 46 ayat (1) huruf a–h | ✅ | Delapan modul aplikasi persis sama dengan delapan Manajemen SPBE. |
| Memakai standar internasional (ISO) sebagai acuan teknis | P1 Pasal 46 ayat (3)–(4) | ✅ | Boleh bila SNI belum tersedia. |
| Satu admin per modul + verifikasi simpanan pengguna lain | P2 Lampiran Bab III huruf C angka 2 huruf a (sistem pengendalian internal: SOP, pengawasan melekat, **reviu berjenjang**) | ✅ | Verifikasi admin modul adalah bentuk reviu berjenjang. Alur persisnya tidak diatur. |
| Kode Aset sebagai pusat penghubung | P6 Lampiran I (memutakhirkan basis data Aset TIK SPBE; pengaturan konfigurasi mencakup Layanan SPBE) | ✅ | Pedoman menempatkan basis data aset sebagai acuan layanan. |
| Kode Personil (kode, bukan nama) | P11 (prinsip pembatasan pemrosesan data pribadi) | ⚪ | Tidak diatur khusus. Memakai kode alih-alih nama sejalan dengan semangat minimisasi data pribadi. |
| Eskalasi otomatis risiko Tinggi/Kritis ke 4 modul | P1 Pasal 47–48, 53, 54 ayat (3); P3 Pasal 8 (program kerja keamanan disusun berdasarkan kategori risiko) | ⚪ | Mekanisme otomatisnya tidak diatur; tiap tindak lanjut punya dasar di pasal modul tujuannya. |

## 3. Manajemen Aset

| Fitur aplikasi | Rujukan | Status | Catatan |
|---|---|---|---|
| Kategori aset: Data & Informasi, Perangkat Lunak, Perangkat Keras, Sarana Pendukung, SDM & Pihak Ketiga | P6 Lampiran I huruf D (Aset TIK = perangkat keras + perangkat lunak); P3 Pasal 10 huruf a (inventaris aset SPBE: data dan informasi, aplikasi, infrastruktur); S3 (aset primer & pendukung, termasuk personel dan lokasi) | 🟡 | Aplikasi memakai cakupan **aset informasi** (gaya ISO 27005), lebih luas dari Aset TIK SPBE. Untuk laporan Manajemen Aset TIK SPBE, cukup saring kategori Perangkat Keras dan Perangkat Lunak. |
| Skor kerahasiaan, keutuhan, ketersediaan 1–5 | P3 Pasal 19 (lima aspek: kerahasiaan, keaslian, keutuhan, kenirsangkalan, ketersediaan) | 🟡 | Aplikasi baru menilai 3 dari 5 aspek P3. Keaslian dan kenirsangkalan belum ada. |
| Kritikalitas = skor C/I/A tertinggi | S5 (prinsip high-water mark); S3 (penilaian aset) | ⚪ | Tidak ada angka resmi nasional. Prinsip "ambil yang tertinggi" adalah praktik standar internasional. Ambang 5/4/3 perlu ditetapkan instansi. |
| Status aset (teks bebas, mis. "Aktif") | P1 Pasal 50 ayat (2); P6 Lampiran I (proses perencanaan, pengadaan, pengelolaan, penghapusan) | 🔶 | Belum ada isian **tahap siklus hidup** sesuai empat proses resmi. |
| Atribut teknis aset (hanya lewat "Data Dinamis") | P6 Lampiran I hal. 12 (pengaturan konfigurasi: perangkat keras, perangkat lunak, sistem operasi, pangkalan data, alamat IP, layanan cloud, dokumentasi teknis, layanan SPBE) | 🔶 | Item konfigurasi resmi belum punya kolom baku. |
| Kategori sistem elektronik | P9 (strategis / tinggi / rendah, menentukan standar pengamanan) | 🔶 | Belum ada isian; berguna untuk menentukan kewajiban SMKI per aset aplikasi. |
| Hubungan dengan Barang Milik Daerah | P13 | ⚪ | Aplikasi tidak mencatat kode barang BMD. Bisa ditambah sebagai kolom referensi bila perlu rekonsiliasi dengan aset BPKAD. |

## 4. Manajemen Risiko

| Fitur aplikasi | Rujukan | Status | Catatan |
|---|---|---|---|
| Register: kejadian (judul), sumber ancaman, kerentanan, kemungkinan, dampak, level, pemilik, strategi, status, tanggal tinjau | P1 Pasal 47 ayat (2) (identifikasi, analisis, pengendalian, pemantauan, evaluasi); P2 Lampiran Bab III huruf C | 🟡 | Kolom inti ada. Yang belum: **penyebab** dan **dampak** sebagai teks terpisah, **area dampak**, **sistem pengendalian yang ada**, penjelasan pemilihan level. |
| Skala kemungkinan & dampak 4 tingkat (Rendah–Kritis) | P2 Lampiran Bab III huruf B angka 8 ("dapat menggunakan 3 level, 4 level, 5 level, atau level lainnya") | ✅ | **Diperbolehkan.** Pedoman memberi contoh 5 level (Hampir Tidak Terjadi … Hampir Pasti Terjadi; Tidak Signifikan … Sangat Signifikan). |
| Level risiko = kemungkinan × dampak dengan ambang 9/6/3 | P2 Lampiran Bab III huruf B angka 9 dan huruf C angka 2 huruf d (besaran & level dari **matriks analisis risiko** instansi) | 🟡 | Boleh memakai matriks sendiri, tetapi matriks dan ambangnya harus **ditetapkan pimpinan** dalam dokumen konteks risiko. Saat ini hanya ada di kode. |
| Nama level: Rendah, Sedang, Tinggi, Kritis | P2 Lampiran Bab III huruf B angka 9 (contoh 5 level: Sangat Rendah biru, Rendah hijau, Sedang kuning, Tinggi jingga, Sangat Tinggi merah) | 🟡 | Sah untuk 4 level, tetapi berbeda istilah dengan contoh pedoman yang dipakai luas di instansi lain. |
| Level naik bila aset kritis / SDM berisiko | P2 Lampiran Bab III huruf C angka 2 huruf a ("sistem pengendalian internal … dapat menurunkan/meningkatkan level Risiko SPBE") | ⚪ | Bukan aturan pedoman; tafsiran pengembang. Perlu disahkan sebagai kebijakan instansi atau dihapus. |
| Kategori risiko (6: operasional, teknis, SDM, pihak ketiga, kepatuhan/legal, bencana alam) | P2 Lampiran Bab III huruf B angka 6 (16 kategori: Rencana Induk SPBE Nasional, Arsitektur SPBE, Peta Rencana SPBE, Proses Bisnis, Rencana dan Anggaran, Inovasi, Kepatuhan terhadap Peraturan, Pengadaan Barang dan Jasa, Proyek Pembangunan/Pengembangan Sistem, Data dan Informasi, Infrastruktur SPBE, Aplikasi SPBE, Keamanan SPBE, Layanan SPBE, SDM SPBE, Bencana Alam) | 🔶 | Pedoman membolehkan penyesuaian, tetapi daftar aplikasi jauh berbeda. Disarankan memakai 16 kategori pedoman. |
| Area dampak | P2 Lampiran Bab III huruf B angka 7 (Finansial, Reputasi, Kinerja, Layanan Organisasi, Operasional & Aset TIK, Hukum & Regulasi, SDM) | 🔶 | Belum ada isian. |
| Strategi penanganan: Mitigasi, Diterima, Dialihkan, Dihindari | P2 Lampiran Bab III huruf D (risiko negatif: eskalasi, mitigasi, transfer, penghindaran, penerimaan; risiko positif: eskalasi, eksploitasi, peningkatan, pembagian, penerimaan) | 🟡 | Kurang opsi **Eskalasi**; risiko positif belum didukung. |
| Selera risiko | P2 Lampiran Bab III huruf B angka 10 (per kategori risiko, ditetapkan pimpinan) | 🔶 | Belum ada. |
| Formulir | P2 Lampiran Bab III huruf G (formulir konteks, penilaian, rencana penanganan, pemantauan) | 🟡 | Register mencakup penilaian; konteks dan rencana penanganan rinci belum ada. |
| Risiko KM memakai matriks 5×5 sendiri | P2 (5 level boleh) | 🔶 | **Tidak konsisten di dalam aplikasi**: Risiko memakai 4×4, Risiko KM memakai 5×5 dengan peta level berbeda. Sebaiknya satu matriks. |

## 5. Manajemen Keamanan Informasi

| Fitur aplikasi | Rujukan | Status | Catatan |
|---|---|---|---|
| Program kerja + kegiatan + PIC | P1 Pasal 48 ayat (2); P3 Pasal 8 (program kerja: edukasi kesadaran, penilaian kerentanan, peningkatan keamanan, penanganan insiden, audit) | 🟡 | Program kerja masih teks bebas. Disarankan isian **jenis program** sesuai lima jenis P3 Pasal 8 ayat (3) dan target realisasinya. |
| Penanggung jawab | P3 Pasal 5–7 (sekretaris instansi selaku koordinator SPBE menunjuk pelaksana teknis) | 🟡 | Ada PIC, belum ada penetapan penanggung jawab formal. |
| Panel bukti lintas modul (jumlah aset, risiko, aktivitas berbagi) | P3 Pasal 9 (edukasi kesadaran: sosialisasi, pelatihan), Pasal 10 (inventaris aset, ukur risiko), Pasal 15 (evaluasi kinerja dengan indikator kuantitatif, minimal setahun sekali) | ✅ | Angka hidup sejalan dengan kebutuhan evaluasi kinerja. |
| Penanganan insiden (lewat tiket helpdesk kategori Insiden Keamanan) | P3 Pasal 12 (identifikasi sumber serangan, analisis, prioritas berdasarkan dampak, dokumentasi bukti, mitigasi) | 🟡 | Langkah awal di triase sudah memuat isolasi, pengamanan log, lapor CSIRT. Belum ada catatan bukti insiden yang terstruktur. |
| Pengukuran kematangan | P10 (IKAS); P8 Indikator 10 | ⚪ | Aplikasi belum menyimpan hasil IKAS/Indeks KAMI. Bisa ditambah sebagai bukti dukung. |

## 6. Manajemen Data & Informasi

| Fitur aplikasi | Rujukan | Status | Catatan |
|---|---|---|---|
| Catatan "tata kelola risiko data" (judul, jenis, kategori, prioritas, level risiko, keputusan, status) | P1 Pasal 49 ayat (2) (arsitektur data, data induk, data referensi, basis data, kualitas data); P4; P5 (standar data, metadata, interoperabilitas, kode referensi, data induk; walidata, produsen data) | 🔶 | Isian aplikasi berpola register risiko, belum mengikuti **lima proses** manajemen data. Disarankan kolom proses (arsitektur / data induk / referensi / basis data / kualitas) dan peran SDI (walidata/produsen data). |
| Data pribadi | P11 | 🔶 | Belum ada penanda "memuat data pribadi" pada aset data/catatan. |

## 7. Manajemen SDM

| Fitur aplikasi | Rujukan | Status | Catatan |
|---|---|---|---|
| Risiko SDM & pihak ketiga | P1 Pasal 51 (perencanaan, pengembangan, pembinaan, pendayagunaan; memastikan ketersediaan dan kompetensi SDM); P2 (kategori risiko "SDM SPBE") | 🟡 | Aplikasi baru mencatat sisi risiko. Pedoman Manajemen SDM SPBE belum terbit. |
| Kompetensi | P8 Indikator 3 (kompetensi digital ASN: kepemimpinan digital, kemahiran digital, literasi digital; ambang >90% ASN pengelola) | 🔶 | Belum ada pencatatan kompetensi/pelatihan per pegawai, padahal itu yang dinilai evaluasi. Peta Keahlian di modul Pengetahuan bisa jadi titik awal. |

## 8. Manajemen Pengetahuan

| Fitur aplikasi | Rujukan | Status | Catatan |
|---|---|---|---|
| Jenis pengetahuan "Tacit / Explicit" | P7 Pasal 3 (pengetahuan **eksplisit** dan **implisit**) | 🟡 | Konsep sama, istilah berbeda. Disarankan label resmi: Eksplisit / Implisit. |
| Aset Pengetahuan, Peta Keahlian, Aktivitas Berbagi, Risiko KM | P1 Pasal 52 ayat (2) (pengumpulan, pengolahan, penyimpanan, penggunaan, alih pengetahuan); P7 Pasal 2 huruf e (memperkecil risiko hilangnya pengetahuan akibat mutasi dan pensiun), Pasal 4 (sosialisasi, eksternalisasi, kombinasi, internalisasi) | ✅ | Empat tab menutup siklus; Aktivitas Berbagi = sosialisasi, dokumen = eksternalisasi. |
| Tanya Dokumen, tag otomatis | P7 (penyimpanan & penggunaan pengetahuan, repositori); P12 | ✅ | Lihat juga 02-pengetahuan-rag.md. |

## 9. Manajemen Perubahan

| Fitur aplikasi | Rujukan | Status | Catatan |
|---|---|---|---|
| Register perubahan (jenis positif/negatif, kategori, prioritas, risiko bawaan, keputusan, status, PIC, target) | P1 Pasal 53 ayat (2) (perencanaan, analisis, pengembangan, implementasi, pemantauan & evaluasi); S4 klausul 8.5.1; S6 Change Enablement | 🔶 | Status masih teks bebas. Disarankan **tahap** sesuai lima proses P1 Pasal 53 ayat (2), plus isian analisis dampak dan rencana pemulihan (rollback). Pedoman Manajemen Perubahan SPBE belum terbit. |
| Jenis "positif/negatif" | — | ⚪ | Berasal dari templat Excel; tidak ada rujukan. |

## 10. Manajemen Layanan

| Fitur aplikasi | Rujukan | Status | Catatan |
|---|---|---|---|
| Katalog layanan (nama, deskripsi, unit pemilik, pengelola teknis, cara akses, target SLA, jam layanan, status) | P6 Lampiran II (katalog minimal 11 isian: nama, deskripsi, penyedia, pelaksana, tim teknis, cara akses, target ketersediaan, kontak permintaan bantuan, waktu pelayanan bantuan, target pemenuhan layanan, ketersediaan layanan) | 🟡 | Kurang: pelaksana layanan, kontak permintaan bantuan, target pemenuhan layanan, ketersediaan layanan. |
| Klasifikasi layanan | P6 Lampiran II (Kritis / Esensial / Normal; layanan Kritis wajib menetapkan RTO, RPO, dan Rencana Pemulihan Bencana) | 🔶 | **Belum ada.** Ini dasar target layanan dan prioritas gangguan menurut pedoman resmi. |
| Jenis tiket (kategori helpdesk) | P1 Pasal 54 ayat (3) (pelayanan terhadap keluhan, gangguan, masalah, permintaan, dan perubahan) | 🟡 | Kategori aplikasi bisa dipetakan ke lima jenis ini; sebaiknya ada kolom jenis resmi di samping kategori teknis. |
| **Prioritas tiket P1–P4 (dampak × urgensi, gaya ITIL)** | P6 Lampiran II Tabel 3–4: prioritas penanganan gangguan dari **klasifikasi layanan (Kritis/Esensial/Normal) × dampak (Mayor/Minor) × jenis layanan (publik/administrasi pemerintahan)** → Pk1–Pk6, Pe1–Pe6, Pn1–Pn6, dengan target waktu tanggap dan penyelesaian | 🔶 | **Berbeda dengan pedoman resmi.** Contoh pedoman: gangguan Mayor pada layanan publik Kritis = Pk1, tanggap < 30 menit, selesai < 1 jam; Minor pada layanan administrasi Normal = Pn6, tanggap < 24 jam, selesai < 168 jam. Disarankan mengganti matriks P1–P4 dengan model ini (tabelnya "contoh", jadi angka target tetap ditetapkan instansi). |
| Target waktu P1–P4 (30 menit … 5 hari kerja) | P6 Tabel 3 (contoh target per Pk/Pe/Pn); P8 Indikator 19 | 🔶 | Angka aplikasi berasal dari praktik umum, bukan pedoman. |
| Dokumentasi permintaan layanan | P6 Lampiran II (deskripsi, tanggal & waktu, klasifikasi, persetujuan, pelaksana pemenuhan, konfirmasi pemohon) | 🟡 | Belum ada persetujuan, pelaksana, dan waktu selesai/konfirmasi. |
| Evaluasi layanan (uptime, SLA, jumlah insiden, MTTR) | P6 Lampiran II (target ketersediaan; evaluasi); P8 Indikator 19 (pemantauan pemenuhan SLA terotomasi) | 🟡 | Belum menghitung otomatis tiket tepat waktu/terlambat. |

## 11. Fitur AI

| Fitur | Rujukan | Status | Catatan |
|---|---|---|---|
| Evaluasi bukti dukung: level Merintis–Unggul, metode penilaian mandiri | P8 Pasal 3–4, Lampiran Bab II huruf C–D | ✅ | Lihat 01-rubrik-bukti-dukung.md. |
| Unsur & kata kunci rubrik | P8 hanya memuat kriteria, bukan daftar bukti | ⚪ | Tafsiran, DRAF. |
| Pemetaan modul → indikator | P8 Tabel 1 | ✅ | |
| Cek keabsahan (nomor, tanggal, NIP, TTE tertanam) | — | ⚪ | Praktik verifikasi dokumen dinas; tidak ada rujukan khusus. |
| Prinsip AI: lokal, draf, keputusan manusia, tercatat | P12; P11; RPerpres Etika AI (belum terbit) | ✅ | Perlu ditinjau ulang setelah Perpres Etika AI terbit. |
| Triase helpdesk | lihat baris prioritas di bagian 10 | 🔶 | Ikut menyesuaikan bila model prioritas P6 dipakai. |

---

## 12. Ringkasan temuan

**Sudah kuat dasarnya**
1. Delapan modul = delapan Manajemen SPBE di Perpres 95/2018 Pasal 46.
2. Skala risiko 4 tingkat diperbolehkan PermenPANRB 5/2020 (boleh 3/4/5 level).
3. Siklus pengetahuan dan jenis pengetahuan sejalan dengan Peraturan BRIN 2/2024.
4. Verifikasi admin modul = reviu berjenjang (sistem pengendalian internal PermenPANRB 5/2020).

**Perlu penyesuaian (urut dari dampak terbesar)**
1. **Prioritas helpdesk** → ikuti model Permenkomdigi 12/2026 (klasifikasi layanan × Mayor/Minor × jenis layanan; Pk/Pe/Pn).
2. **Klasifikasi layanan Kritis/Esensial/Normal** + RTO/RPO/DRP untuk layanan Kritis (Permenkomdigi 12/2026).
3. **Kategori risiko, area dampak, selera risiko** mengikuti PermenPANRB 5/2020; tetapkan matriks analisis risiko secara resmi; samakan matriks Risiko dan Risiko KM.
4. **Tahap siklus aset** (perencanaan, pengadaan, pengelolaan, penghapusan) dan **item konfigurasi** aset (Permenkomdigi 12/2026).
5. **Lima proses manajemen data** dan penanda data pribadi (Perpres 95/2018 Pasal 49, Permen PPN 16/2020, UU PDP).
6. **Tahap perubahan** sesuai Perpres 95/2018 Pasal 53 ayat (2).
7. Jenis program kerja keamanan sesuai Peraturan BSSN 4/2021 Pasal 8.
8. Istilah Eksplisit/Implisit (Peraturan BRIN 2/2024).
9. Pencatatan kompetensi digital ASN (PermenPANRB 8/2026 Indikator 3).

**Tafsiran yang perlu disahkan atau dibuang**
- Kenaikan level risiko karena aset kritis atau SDM berisiko.
- Ambang kritikalitas aset dari skor C/I/A.
- Rubrik bukti dukung (kata kunci) dan target waktu helpdesk.

## 13. Pertanyaan untuk peninjau

1. Apakah Diskominfo sudah punya **Keputusan/Perwali tentang manajemen risiko SPBE** (konteks, kategori, matriks, selera risiko)? Bila ada, matriks aplikasi harus mengikuti dokumen itu.
2. Apakah layanan-layanan Diskominfo sudah punya **klasifikasi Kritis/Esensial/Normal** dan katalog layanan resmi?
3. Penyesuaian mana yang dikerjakan lebih dulu? Saran: nomor 1–3 di atas karena paling sering dilihat asesor.
