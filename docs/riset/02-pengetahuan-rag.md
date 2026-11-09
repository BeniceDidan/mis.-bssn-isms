# 02 · Manajemen Pengetahuan: Pencarian Semantik & RAG — DRAF

## Cara kerja di aplikasi

1. Saat PDF diunggah di Aset Pengetahuan, teksnya dibaca per halaman lalu dipotong
   ±900 karakter (tumpang tindih 150) supaya setiap jawaban bisa menunjuk halaman.
2. Tiap potongan diubah menjadi vektor oleh model embedding **bge-m3** (lokal, lewat
   Ollama) dan disimpan di tabel `knowledge_chunks`.
3. Pertanyaan pengguna juga diubah menjadi vektor; potongan paling mirip diambil,
   digabung dengan pencarian teks Postgres berbahasa Indonesia (untuk istilah persis).
4. LLM lokal (llama3.2) menyusun jawaban **hanya** dari potongan itu dan wajib
   menyebut nomor sumber. Tautan PDF langsung membuka halaman sumber (`#page=N`).
5. Tag otomatis: kosakata tag tetap (konsisten antar-dokumen) + usulan LLM.

Bila Ollama mati, pencarian tetap jalan dengan kata kunci dan menampilkan potongan
dokumen paling relevan tanpa jawaban ringkas.

## Sumber

| Kode | Sumber | Tautan |
|---|---|---|
| K1 | Chen dkk. (2024), *BGE M3-Embedding: Multi-Lingual, Multi-Functionality, Multi-Granularity Text Embeddings* — dievaluasi pada MIRACL (18 bahasa, termasuk Indonesia) | https://arxiv.org/abs/2402.03216 |
| K2 | Model bge-m3 di pustaka Ollama | https://ollama.com/library/bge-m3 |
| K3 | Peraturan BRIN 2/2024 — siklus pengetahuan: pengumpulan, pengolahan, penyimpanan, penggunaan, alih pengetahuan | https://peraturan.bpk.go.id/Download/339522/peraturan%20brin%20no%202%20tahun%202024.pdf |
| K4 | SE Menkominfo 9/2023 tentang Etika Kecerdasan Artifisial (transparansi, akuntabilitas, pelindungan data) | https://jdih.komdigi.go.id/produk_hukum/view/id/883/t/surat+edaran+menteri+komunikasi+dan+informatika+nomor+9+tahun+2023 |
| K5 | UU 27/2022 tentang Pelindungan Data Pribadi | https://jdih.kemenkeu.go.id/download/46c17b53-4a45-411c-9b76-c6799e5769fc/UU_Nomor_27_Tahun_2022%20Perlindungan%20Data%20Pribadi.pdf |

## Alasan desain

- **Lokal (on-premise), bukan API awan.** Dokumen kebijakan dan tiket berisi data
  internal. Menjalankan model di server sendiri menghindari pengiriman data ke
  pihak ketiga (K4, K5).
- **bge-m3, bukan nomic-embed-text.** bge-m3 dilatih multibahasa dan diuji pada
  data berbahasa Indonesia (K1); nomic-embed-text terutama berbahasa Inggris.
- **Tanpa pgvector.** Vektor disimpan sebagai jsonb dan dihitung di PHP. Cukup untuk
  ratusan dokumen; bila sudah ribuan dokumen, pasang ekstensi pgvector.
- **Jawaban selalu disertai sumber** supaya pegawai bisa memeriksa (K4: transparansi).

## Batasan yang perlu diketahui

- llama3.2 (3B) kecil; bahasa Indonesianya cukup tapi kadang kaku. Model yang lebih
  paham Bahasa Indonesia (mis. qwen2.5 7B, gemma2 9B, atau Sahabat-AI 9B dari
  Indonesia) bisa dipasang dengan mengganti `AI_CHAT_MODEL` di `.env`, asalkan
  server punya RAM/GPU cukup. **Belum diuji di sini.**
- PDF hasil pindai belum bisa ditanya (perlu OCR).
- Hak akses dokumen belum membatasi jawaban: semua pengguna yang login bisa
  mendapat jawaban dari dokumen "Terbatas". Perlu diputuskan apakah jawaban harus
  mengikuti tingkat aksesibilitas dokumen.

## Pertanyaan untuk peninjau

1. Dokumen apa saja (SOP, kebijakan, juknis) yang boleh dimasukkan sebagai basis
   pengetahuan pertama?
2. Apakah jawaban dari dokumen "Terbatas"/"Rahasia" perlu dibatasi per peran?
