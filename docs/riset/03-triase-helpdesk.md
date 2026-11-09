# 03 · Triase Tiket Helpdesk — DRAF

## Dasar

| Kode | Sumber | Isi yang dipakai | Tautan |
|---|---|---|---|
| H1 | PermenPANRB 8/2026, Indikator 19 Fasilitas Dukungan Pengguna (bobot 10%), hal. 32 | Level 2 menuntut: fasilitas dukungan pengguna, SOP layanan dengan rincian SLA, dan **sistem pemantauan pemenuhan SLA yang terotomatisasi** | https://peraturan.bpk.go.id/Download/415109/permenpanrb-no-8-tahun-2026.pdf |
| H2 | Pola matriks prioritas ITIL (dampak × urgensi) — artikel praktisi | Prioritas = gabungan dampak dan urgensi; grid dan target waktu ditetapkan masing-masing organisasi | https://blog.invgate.com/itil-priority-matrix · https://wiki.en.it-processmaps.com/index.php/Checklist_Incident_Priority |
| H3 | PermenPANRB 8/2026, Indikator 2 (subtopik Manajemen Relasi Pengguna) | Konteks bahwa dukungan pengguna bagian dari manajemen layanan | (sama dengan H1, hal. 12–13) |

## Cara kerja di aplikasi

1. **Kategori** dipilih dari kata kunci (frasa lebih panjang = sinyal lebih kuat).
   Bila kata kunci lemah, LLM lokal boleh memilih kategori, tapi hanya dari daftar
   yang ada di `config/helpdesk.php`.
2. **Dampak** dan **urgensi** mulai dari nilai bawaan kategori, lalu dinaikkan bila
   teks menyebut cakupan luas ("semua pegawai", "seluruh OPD") atau kata mendesak
   ("segera", "darurat"). Untuk gangguan/bug/insiden, dampak naik satu tingkat bila
   layanannya bertumpu pada aset berkritikalitas tinggi/kritis di modul Aset.
   LLM boleh **menaikkan**, tidak boleh **menurunkan**.
3. **Prioritas** dihitung dari matriks — tidak pernah ditebak LLM, supaya selalu bisa
   dijelaskan.
4. **Draf balasan** dari LLM (bila tersedia) atau templat per kategori yang berisi
   daftar kelengkapan. Draf yang meminta kata sandi otomatis dibuang.
5. Tiket yang dibuat otomatis oleh eskalasi risiko tinggi/kritis langsung diberi
   urgensi = level risiko, sehingga masuk P1/P2.

## Matriks yang dipakai (usulan)

| Dampak \ Urgensi | Kritis | Tinggi | Sedang | Rendah |
|---|---|---|---|---|
| **Kritis** | P1 | P1 | P2 | P2 |
| **Tinggi** | P1 | P2 | P2 | P3 |
| **Sedang** | P2 | P2 | P3 | P4 |
| **Rendah** | P2 | P3 | P4 | P4 |

Target waktu usulan: P1 tanggapan 30 menit/selesai 4 jam · P2 2 jam/1 hari kerja ·
P3 1 hari kerja/3 hari kerja · P4 2 hari kerja/5 hari kerja. Angka ini contoh dari
praktik umum (H2), **bukan ketentuan resmi**; harus diganti dengan SLA yang
ditetapkan instansi dalam SOP layanan.

## Pertanyaan untuk peninjau

1. Apakah Diskominfo sudah punya SOP helpdesk dengan SLA resmi? Bila ya, angka di
   `config/helpdesk.php` diganti mengikuti SOP itu.
2. Apakah kategori tiket sudah sesuai jenis permintaan yang sering masuk? (mis.
   perlu kategori "Permohonan Subdomain/Email Dinas" tersendiri?)
3. Untuk mencapai Level 2 Indikator 19, pemantauan pemenuhan SLA harus terotomasi.
   Aplikasi sudah mencatat prioritas dan target; perlu ditambah perhitungan
   "tepat waktu / terlambat" per tiket?
