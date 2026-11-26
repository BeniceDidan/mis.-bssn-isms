# 01 · Rubrik Evaluasi Bukti Dukung — DRAF

## Sumber utama

| Kode | Sumber | Tautan |
|---|---|---|
| R1 | PermenPANRB 8/2026 tentang Evaluasi Kinerja Pemerintah Digital — salinan JDIH BPK (46 hal.; lampiran hal. 9–46) | https://peraturan.bpk.go.id/Details/351330/permen-panrb-no-8-tahun-2026 · PDF: https://peraturan.bpk.go.id/Download/415109/permenpanrb-no-8-tahun-2026.pdf |
| R2 | Ringkasan pasal PermenPANRB 8/2026 (Pasal 3–12) | https://pasal.id/peraturan/permen/permenpanrb-no-8-tahun-2026 |
| R3 | Artikel Diskominfo Kutai Timur tentang Indikator 13 (cocok dengan R1) | https://aptika-diskominfo.kutaitimurkab.go.id/publik/blog/evaluasi-kinerja-pemerintah-digital-tingkat-kematangan-aplikasi-pemerintah-digital |
| R4 | PermenPANRB 5/2020 tentang Pedoman Manajemen Risiko SPBE | https://bkd.kalteng.go.id/wp-content/uploads/2020/04/Permen-PANRB-No.-5-Tahun-2020-.pdf |
| R5 | Peraturan BSSN 10/2023 tentang Pengukuran Tingkat Kematangan Keamanan Siber (dasar IKAS, dirujuk Indikator 10) | https://peraturan.bpk.go.id/Download/350568/%5BSALINAN%5D-Peraturan-BSSN-Nomor-10-Tahun-2023_signed.pdf |
| R6 | Peraturan BRIN 2/2024 tentang Pedoman Manajemen Pengetahuan SPBE | https://peraturan.bpk.go.id/Download/339522/peraturan%20brin%20no%202%20tahun%202024.pdf |
| R7 | Materi sosialisasi pemda tentang bukti dukung evaluasi SPBE lama (PermenPANRB 59/2020) — hanya untuk gambaran praktik, aturannya sudah dicabut | https://simpspbe.purbalinggakab.go.id/wp-content/uploads/2023/05/6-PAPARAN-DOMAIN-MANAJEMEN-SONI-TEL-U-TONI-ITS.pdf |

## Yang dinyatakan regulasi (R1)

- **Metode** (Pasal 3–4; Lampiran Bab II huruf D, hal. 39–40): Penilaian Mandiri
  oleh Tim Asesor Internal → Penilaian Dokumen oleh Tim Asesor Eksternal (verifikasi
  jawaban, penjelasan, dan **bukti pendukung**) → Penilaian Interviu → bila perlu
  Penilaian Visitasi. Fitur aplikasi ini membantu tahap **Penilaian Mandiri**:
  memeriksa kelengkapan bukti sebelum dikirim.
- **Struktur** (Tabel 1, hal. 10–11): 7 aspek, 20 indikator, total bobot 100%.
- **Level & predikat** (Tabel 4, hal. 38): 1 Kurang/Merintis (1,00–1,50) ·
  2 Cukup/Membangun (≤2,50) · 3 Baik/Berkembang (≤3,50) · 4 Sangat Baik/Melembaga
  (≤4,00) · 5 Memuaskan/Unggul (≤5,00).
- **Pola umum kriteria** (Bab II huruf C, hal. 39): Merintis = ad-hoc/masih
  disusun; Membangun = terencana, sebagian unit; Berkembang = sistematis, seluruh
  unit, ada standar & prosedur; Melembaga = reviu berkala; Unggul = tindak lanjut
  hasil reviu dan inovasi.

## Pemetaan 8 modul aplikasi → indikator R1

| Modul aplikasi | Indikator R1 | Hal. | Catatan |
|---|---|---|---|
| Manajemen Risiko | 2 · Manajemen Layanan Digital Pemerintah (subtopik Manajemen Risiko; pembina BSSN) | 12–13 | Pedoman teknis: R4 |
| Manajemen Perubahan | 2 · (subtopik Manajemen Perubahan) | 12–13 | |
| Manajemen Pengetahuan | 2 · (subtopik Manajemen Pengetahuan; pembina BRIN) | 12–13 | Pedoman teknis: R6 |
| Manajemen Layanan | 2 dan 19 · Fasilitas Dukungan Pengguna Layanan Digital | 12–13, 32 | Indikator 19 bobotnya 10% — terbesar kedua |
| Manajemen Keamanan Informasi | 10 · Keamanan Pemdi (juga terkait 9 Audit, 12 Insiden) | 22 | Mengacu IKAS (R5) |
| Manajemen Data & Informasi | 5 · Tata Kelola Data (juga terkait 8 PDP) | 16–17 | Ada ambang skor SDI |
| Manajemen Aset | 14 · Infrastruktur Pemdi (juga terkait 13 Aplikasi) | 26 | Manajemen Aset TIK tidak lagi disebut sebagai subtopik Indikator 2 |
| Manajemen SDM | 3 · SDM Pemdi | 14 | Ambang ">90% ASN pengelola" di Level 2 |

Subtopik Indikator 2 menurut R1: Manajemen Risiko, Perubahan, Pengetahuan,
**Keberlangsungan**, dan **Relasi Pengguna**. Dua yang terakhir belum punya modul di
aplikasi ini — kandidat pengembangan berikutnya.

## Tafsiran kita (perlu ditinjau)

R1 hanya memuat **kriteria** per level, tidak memuat daftar dokumen bukti dukung.
Daftar "unsur" dan kata kunci di `config/bukti_dukung.php` adalah tafsiran: dokumen
apa yang lazim membuktikan kriteria tersebut. Contoh untuk Manajemen Risiko:

| Level | Kriteria R1 (ringkas) | Unsur yang dicari di PDF (tafsiran) |
|---|---|---|
| 1 Merintis | Dilaksanakan sesuai kebutuhan | Ada dokumentasi kegiatan manajemen risiko |
| 2 Membangun | Mengacu pedoman, sebagian layanan | Kebijakan/pedoman; penetapan konteks; register risiko (tahapan R4) |
| 3 Berkembang | Mengacu pedoman, seluruh layanan | Analisis kemungkinan × dampak; **matriks mitigasi/rencana penanganan**; cakupan seluruh layanan |
| 4 Melembaga | Direviu menyeluruh | Laporan pemantauan/reviu |
| 5 Unggul | Tindak lanjut reviu diterapkan | Bukti tindak lanjut |

Pertanyaan untuk peninjau:

1. Apakah KemenPANRB menerbitkan **kuesioner/petunjuk teknis** yang merinci bukti
   dukung per level untuk 2026? Bila ada, rubrik sebaiknya mengikuti dokumen itu.
2. Apakah "matriks mitigasi" memang syarat Level 3 Manajemen Risiko, atau cukup di
   Level 2? (R4 menaruh rencana penanganan sebagai bagian dari proses dasar.)
3. Untuk Data (Indikator 5), kriteria memakai **nilai SDI**. Nilai itu datang dari
   instansi pembina, bukan dari dokumen — perlu kolom input terpisah?

## Atribut keabsahan dokumen

Yang dicek otomatis: nomor dokumen, tanggal penetapan, NIP penandatangan, keterangan
"ditandatangani secara elektronik", **tanda tangan digital tertanam** (struktur
`/Sig` + `/ByteRange` di berkas PDF, mis. dari BSrE), dan jumlah gambar di halaman
terakhir. Batasannya:

- Cap basah/tanda tangan basah hasil pindai = gambar. Aplikasi hanya bisa bilang
  "ada gambar di halaman pengesahan, cek secara visual".
- PDF hasil pindai seluruhnya (tanpa teks) tidak bisa dinilai isinya. Perlu OCR
  (mis. Tesseract bahasa Indonesia) — belum dipasang.
- Keberadaan tanda tangan digital ≠ tanda tangan valid. Verifikasi sertifikat BSrE
  belum dilakukan.
