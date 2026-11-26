# Riset Rujukan — STATUS: DRAF, BELUM DITINJAU

Folder ini berisi hasil riset internet yang dipakai sebagai rujukan pengembangan
fitur AI. **Isinya belum ditinjau.** Sebelum dipakai untuk penilaian resmi atau
dijadikan bahan presentasi, setiap poin perlu dicek ke sumber aslinya.

| Berkas | Isi | Dipakai oleh |
|---|---|---|
| [01-rubrik-bukti-dukung.md](01-rubrik-bukti-dukung.md) | Struktur Evaluasi Kinerja Pemerintah Digital (PermenPANRB 8/2026), pemetaan 8 modul ke indikator, dan tafsiran unsur bukti dukung per level | `config/bukti_dukung.php`, Evaluasi Bukti Dukung |
| [02-pengetahuan-rag.md](02-pengetahuan-rag.md) | Cara kerja tanya-jawab dokumen (RAG), pilihan model lokal, dan batasannya | Tab Tanya Dokumen, tag otomatis |
| [03-triase-helpdesk.md](03-triase-helpdesk.md) | Matriks prioritas dampak × urgensi, kategori tiket, dan target waktu | `config/helpdesk.php`, Tiket Helpdesk |
| [04-rujukan-per-modul.md](04-rujukan-per-modul.md) | Rujukan resmi untuk setiap aturan dan fitur di 8 modul + audit kesesuaian (sesuai / sebagian / perlu penyesuaian / belum ada rujukan) | Seluruh aplikasi |
| [05-dasar-angka-penilaian.md](05-dasar-angka-penilaian.md) | Dasar resmi angka penilaian: rumus Indeks Pemdi, skala Peraturan BSSN 10/2023, skor Indeks KAMI 5.0, dan pilihan pengganti "Kelengkapan 86%" | Evaluasi Bukti Dukung |
| [06-alur-dan-bahasa.md](06-alur-dan-bahasa.md) | Dasar alur data antarmodul, Panduan, Asisten sebagai layanan bantuan, dan bahasa Indonesia di tampilan (Permenkomdigi 6/2025, Perpres 63/2019, Perpres 95/2018) | Alur Data, Panduan, tampilan 8 modul |

## Temuan yang paling perlu diketahui

1. **PermenPANRB 59/2020 sudah dicabut** dan diganti PermenPANRB 8/2026 tentang
   Evaluasi Kinerja Pemerintah Digital (berlaku 5 Juni 2026). Istilah "Indeks SPBE"
   diganti "Indeks Pemdi"; ada 7 aspek dan 20 indikator; level kematangan bernama
   Merintis, Membangun, Berkembang, Melembaga, dan Unggul.
2. **8 modul aplikasi ini tidak lagi sama persis dengan indikator evaluasi.** Di
   aturan lama ada 8 indikator "Manajemen SPBE". Di aturan baru, Manajemen Risiko,
   Perubahan, dan Pengetahuan digabung ke Indikator 2 (Manajemen Layanan Digital
   Pemerintah). SDM, Data, Keamanan, dan Infrastruktur punya indikator sendiri.
   Pemetaannya ada di berkas 01.
3. **Regulasi tidak merinci daftar bukti dukung per level.** Kriteria level ada di
   lampiran, tetapi daftar dokumen yang dianggap bukti adalah tafsiran kita. Itulah
   sebabnya rubrik di aplikasi diberi label draf.
4. **Cap basah dan tanda tangan basah tidak bisa dibaca dari teks PDF.** Aplikasi
   hanya bisa mendeteksi tanda tangan elektronik yang tertanam, blok NIP/jabatan,
   dan keberadaan gambar di halaman pengesahan. Membaca cap secara visual butuh
   model vision (lihat berkas 02).
5. **Perpres 95/2018 masih berlaku** (dicek di JDIH BPK dan JDIH Kemenkeu) dan
   Pasal 46-nya memuat persis 8 manajemen yang dipakai aplikasi.
6. **Pedoman Manajemen Aset TIK dan Layanan SPBE sudah resmi** sebagai
   Permenkomdigi 12/2026 (berlaku 11 Agustus 2026). Pedoman ini punya model
   prioritas gangguan sendiri (klasifikasi layanan Kritis/Esensial/Normal ×
   dampak Mayor/Minor), berbeda dengan matriks P1–P4 di aplikasi. Lihat berkas 04.
7. **Skala risiko 4 tingkat diperbolehkan** PermenPANRB 5/2020 (boleh 3, 4, atau
   5 level), asalkan matriksnya ditetapkan pimpinan instansi.
8. **Persentase kelengkapan bukti dukung tidak punya dasar resmi.** Evaluasi
   Kinerja Pemdi memberi nilai tingkat kematangan 1–5 per indikator, lalu
   menghitungnya menjadi indeks 1,00–5,00. Persentase resmi hanya ditemukan di
   modul Suplemen Indeks KAMI 5.0 (BSSN). Sejak 19 November 2026 aplikasi
   menampilkan Nilai Level 1–5. Lihat berkas 05.
9. **Alur data dan bahasa tampilan punya dasar resmi.** Permenkomdigi 6/2025
   Pasal 9 ayat (5) mewajibkan rancang bangun aplikasi SPBE memuat alur proses,
   keterhubungan fungsi, antarmuka dan navigasi, serta rancangan integrasi;
   Pasal 17 meminta layanan bantuan dengan pertanyaan yang sering diajukan.
   Perpres 63/2019 Pasal 25 dan 28 mewajibkan bahasa Indonesia di sistem
   informasi pelayanan dan komunikasi resmi. Lihat berkas 06.

## Cara meninjau

- Buka tautan sumber di setiap berkas, cocokkan kutipan/ringkasan.
- Tandai yang salah langsung di berkas ini (atau minta Claude Code mengubahnya).
- Setelah rubrik disetujui, ubah `'status' => 'draf'` di `config/bukti_dukung.php`
  menjadi `'ditinjau'` supaya peringatan "Rubrik masih draf" hilang dari aplikasi.

Riset dilakukan 10 November 2026; berkas 05 dan 06 ditambahkan 19 November 2026.
