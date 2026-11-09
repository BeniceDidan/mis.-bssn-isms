<?php

/*
|--------------------------------------------------------------------------
| Rubrik Evaluasi Bukti Dukung — STATUS: DRAF, BELUM DITINJAU
|--------------------------------------------------------------------------
| Nama level dan kriteria diringkas dari Lampiran PermenPANRB 8/2026
| (Evaluasi Kinerja Pemerintah Digital). "Unsur" dan kata kuncinya adalah
| TAFSIRAN tim pengembang tentang dokumen apa yang lazim membuktikan
| kriteria tersebut — regulasinya sendiri tidak merinci daftar bukti
| dukung per level. Sebelum dipakai untuk penilaian resmi, rubrik ini harus
| ditinjau. Rincian sumber: docs/riset/01-rubrik-bukti-dukung.md.
|
| Cara kerja: sebuah unsur dianggap ADA bila salah satu kata kuncinya
| ditemukan di teks PDF. Level tercapai = level tertinggi yang semua unsur
| di level itu dan di bawahnya ada. Ubah kata kunci di sini, bukan di kode.
*/

return [

    'status' => 'draf',

    'max_upload_kb' => 10240,

    // Teks lebih pendek dari ini per halaman dianggap hasil pindai (gambar).
    'min_chars_per_page' => 120,

    'levels' => [
        1 => ['name' => 'Merintis', 'predikat' => 'Kurang'],
        2 => ['name' => 'Membangun', 'predikat' => 'Cukup'],
        3 => ['name' => 'Berkembang', 'predikat' => 'Baik'],
        4 => ['name' => 'Melembaga', 'predikat' => 'Sangat Baik'],
        5 => ['name' => 'Unggul', 'predikat' => 'Memuaskan'],
    ],

    // Atribut keabsahan yang dicek pada setiap dokumen, apa pun modulnya.
    'pengesahan' => [
        'nomor' => [
            'label' => 'Nomor dokumen',
            'patterns' => ['/\bnomor\s*:?\s*[0-9a-z]{1,10}[\.\/\-]/iu', '/\bno\.\s*[0-9]{1,5}\s*\//iu'],
        ],
        'tanggal' => [
            'label' => 'Tanggal penetapan',
            'patterns' => [
                '/\b\d{1,2}\s+(januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember)\s+\d{4}\b/iu',
                '/\b(ditetapkan|disahkan)\s+(di|pada)\b/iu',
            ],
        ],
        'penandatangan' => [
            'label' => 'Jabatan & NIP penandatangan',
            'patterns' => ['/\bnip\.?\s*:?\s*\d[\d\s]{15,22}\d/iu'],
        ],
        'tte' => [
            'label' => 'Keterangan tanda tangan elektronik',
            'patterns' => ['/ditandatangani\s+secara\s+elektronik/iu', '/tanda\s+tangan\s+elektronik/iu', '/\bbsre\b/iu', '/balai\s+sertifikasi\s+elektronik/iu'],
        ],
    ],

    'modules' => [

        'risiko' => [
            'indicator' => 'Indikator 2 · Manajemen Layanan Digital Pemerintah — Manajemen Risiko',
            'levels' => [
                1 => [
                    'kriteria' => 'Manajemen risiko dilaksanakan sesuai kebutuhan; rencana masih disusun.',
                    'unsur' => [
                        ['label' => 'Dokumentasi kegiatan manajemen risiko', 'keywords' => ['manajemen risiko', 'identifikasi risiko', 'risiko spbe', 'risiko pemdi']],
                    ],
                ],
                2 => [
                    'kriteria' => 'Mengacu pada pedoman manajemen risiko, pada sebagian layanan digital.',
                    'unsur' => [
                        ['label' => 'Kebijakan atau pedoman manajemen risiko', 'keywords' => ['pedoman manajemen risiko', 'kebijakan manajemen risiko', 'kebijakan risiko', 'penerapan manajemen risiko']],
                        ['label' => 'Penetapan konteks / ruang lingkup', 'keywords' => ['penetapan konteks', 'konteks risiko', 'ruang lingkup', 'selera risiko']],
                        ['label' => 'Register / daftar risiko', 'keywords' => ['register risiko', 'daftar risiko', 'profil risiko', 'identifikasi risiko']],
                    ],
                ],
                3 => [
                    'kriteria' => 'Mengacu pada pedoman manajemen risiko, pada seluruh layanan digital.',
                    'unsur' => [
                        ['label' => 'Analisis risiko (kemungkinan × dampak)', 'keywords' => ['level kemungkinan', 'level dampak', 'besaran risiko', 'level risiko', 'matriks analisis risiko', 'kemungkinan dan dampak']],
                        ['label' => 'Matriks mitigasi / rencana penanganan risiko', 'keywords' => ['matriks mitigasi', 'rencana penanganan risiko', 'rencana mitigasi', 'penanganan risiko', 'rencana aksi penanganan', 'opsi penanganan']],
                        ['label' => 'Cakupan seluruh layanan / unit', 'keywords' => ['seluruh layanan', 'semua layanan', 'seluruh unit', 'seluruh perangkat daerah', 'seluruh opd']],
                    ],
                ],
                4 => [
                    'kriteria' => 'Pelaksanaan manajemen risiko telah direviu secara menyeluruh.',
                    'unsur' => [
                        ['label' => 'Laporan pemantauan / reviu risiko', 'keywords' => ['pemantauan risiko', 'laporan pemantauan', 'reviu', 'review', 'evaluasi berkala', 'laporan manajemen risiko']],
                    ],
                ],
                5 => [
                    'kriteria' => 'Tindak lanjut hasil reviu telah diterapkan menyeluruh.',
                    'unsur' => [
                        ['label' => 'Bukti tindak lanjut hasil reviu', 'keywords' => ['tindak lanjut hasil reviu', 'tindak lanjut reviu', 'tindak lanjut rekomendasi', 'telah ditindaklanjuti', 'perbaikan berkelanjutan']],
                    ],
                ],
            ],
        ],

        'perubahan' => [
            'indicator' => 'Indikator 2 · Manajemen Layanan Digital Pemerintah — Manajemen Perubahan',
            'levels' => [
                1 => [
                    'kriteria' => 'Pengendalian perubahan dilaksanakan sesuai kebutuhan.',
                    'unsur' => [
                        ['label' => 'Catatan permintaan perubahan', 'keywords' => ['permintaan perubahan', 'permohonan perubahan', 'change request', 'manajemen perubahan']],
                    ],
                ],
                2 => [
                    'kriteria' => 'Mengacu pada pedoman manajemen perubahan, pada sebagian layanan digital.',
                    'unsur' => [
                        ['label' => 'Pedoman / SOP manajemen perubahan', 'keywords' => ['pedoman manajemen perubahan', 'prosedur manajemen perubahan', 'sop perubahan', 'standar operasional prosedur']],
                        ['label' => 'Formulir permintaan perubahan', 'keywords' => ['formulir permintaan perubahan', 'formulir perubahan', 'formulir permohonan']],
                    ],
                ],
                3 => [
                    'kriteria' => 'Mengacu pada pedoman manajemen perubahan, pada seluruh layanan digital.',
                    'unsur' => [
                        ['label' => 'Analisis dampak & persetujuan perubahan', 'keywords' => ['analisis dampak', 'analisis perubahan', 'persetujuan perubahan', 'disetujui oleh']],
                        ['label' => 'Rencana implementasi & pemulihan (rollback)', 'keywords' => ['rencana implementasi', 'rencana pemulihan', 'rollback', 'roll back', 'uji coba', 'pengujian']],
                    ],
                ],
                4 => [
                    'kriteria' => 'Pelaksanaan manajemen perubahan telah direviu secara menyeluruh.',
                    'unsur' => [
                        ['label' => 'Reviu pasca-implementasi', 'keywords' => ['reviu pasca', 'evaluasi pasca', 'laporan perubahan', 'reviu', 'review']],
                    ],
                ],
                5 => [
                    'kriteria' => 'Tindak lanjut hasil reviu telah diterapkan menyeluruh.',
                    'unsur' => [
                        ['label' => 'Bukti tindak lanjut hasil reviu', 'keywords' => ['tindak lanjut hasil reviu', 'tindak lanjut reviu', 'telah ditindaklanjuti', 'perbaikan berkelanjutan']],
                    ],
                ],
            ],
        ],

        'pengetahuan' => [
            'indicator' => 'Indikator 2 · Manajemen Layanan Digital Pemerintah — Manajemen Pengetahuan',
            'levels' => [
                1 => [
                    'kriteria' => 'Pengelolaan pengetahuan dilaksanakan sesuai kebutuhan.',
                    'unsur' => [
                        ['label' => 'Dokumentasi pengetahuan', 'keywords' => ['manajemen pengetahuan', 'pengetahuan', 'dokumentasi', 'knowledge']],
                    ],
                ],
                2 => [
                    'kriteria' => 'Mengacu pada pedoman manajemen pengetahuan, pada sebagian layanan digital.',
                    'unsur' => [
                        ['label' => 'Kebijakan / pedoman manajemen pengetahuan', 'keywords' => ['pedoman manajemen pengetahuan', 'kebijakan manajemen pengetahuan', 'rencana manajemen pengetahuan']],
                        ['label' => 'Repositori / basis pengetahuan', 'keywords' => ['repositori', 'basis pengetahuan', 'knowledge base', 'sistem informasi manajemen pengetahuan']],
                    ],
                ],
                3 => [
                    'kriteria' => 'Mengacu pada pedoman manajemen pengetahuan, pada seluruh layanan digital.',
                    'unsur' => [
                        ['label' => 'Siklus pengetahuan (kumpul–olah–simpan–guna–alih)', 'keywords' => ['alih pengetahuan', 'berbagi pengetahuan', 'pengumpulan pengetahuan', 'penyimpanan pengetahuan', 'pemanfaatan pengetahuan']],
                        ['label' => 'Cakupan seluruh layanan / unit', 'keywords' => ['seluruh layanan', 'seluruh unit', 'seluruh perangkat daerah', 'seluruh opd']],
                    ],
                ],
                4 => [
                    'kriteria' => 'Pelaksanaan manajemen pengetahuan telah direviu secara menyeluruh.',
                    'unsur' => [
                        ['label' => 'Laporan reviu / evaluasi', 'keywords' => ['reviu', 'review', 'evaluasi manajemen pengetahuan', 'laporan evaluasi']],
                    ],
                ],
                5 => [
                    'kriteria' => 'Tindak lanjut hasil reviu telah diterapkan menyeluruh.',
                    'unsur' => [
                        ['label' => 'Bukti tindak lanjut hasil reviu', 'keywords' => ['tindak lanjut hasil reviu', 'tindak lanjut reviu', 'telah ditindaklanjuti']],
                    ],
                ],
            ],
        ],

        'layanan' => [
            'indicator' => 'Indikator 2 & 19 · Manajemen Layanan Digital & Fasilitas Dukungan Pengguna',
            'levels' => [
                1 => [
                    'kriteria' => 'Fasilitas dukungan pengguna dan SOP layanan dengan SLA sedang disusun.',
                    'unsur' => [
                        ['label' => 'Rancangan dukungan pengguna / SOP layanan', 'keywords' => ['layanan bantuan', 'dukungan pengguna', 'helpdesk', 'help desk', 'standar operasional prosedur', 'sop']],
                    ],
                ],
                2 => [
                    'kriteria' => 'Sudah ada fasilitas dukungan pengguna, SOP dengan rincian SLA, dan pemantauan SLA terotomasi.',
                    'unsur' => [
                        ['label' => 'Fasilitas dukungan pengguna (helpdesk)', 'keywords' => ['helpdesk', 'help desk', 'service desk', 'layanan bantuan', 'pusat bantuan', 'pusat layanan']],
                        ['label' => 'SOP dengan rincian SLA', 'keywords' => ['service level agreement', 'tingkat layanan', 'sla', 'waktu respons', 'waktu respon', 'waktu penyelesaian']],
                        ['label' => 'Sistem pemantauan pemenuhan SLA', 'keywords' => ['pemantauan sla', 'monitoring sla', 'pemenuhan sla', 'dasbor layanan', 'dashboard layanan']],
                    ],
                ],
                3 => [
                    'kriteria' => 'Dukungan pengguna, SLA, dan pemantauannya terintegrasi ke tingkat nasional.',
                    'unsur' => [
                        ['label' => 'Integrasi dengan layanan nasional', 'keywords' => ['tingkat nasional', 'terintegrasi nasional', 'sp4n', 'lapor!', 'span lapor', 'layanan nasional']],
                    ],
                ],
                4 => [
                    'kriteria' => 'Reviu dan evaluasi pemenuhan SLA serta fasilitas dukungan pengguna.',
                    'unsur' => [
                        ['label' => 'Laporan reviu pemenuhan SLA', 'keywords' => ['evaluasi pemenuhan sla', 'laporan sla', 'reviu sla', 'reviu', 'evaluasi kinerja layanan']],
                    ],
                ],
                5 => [
                    'kriteria' => 'Tindak lanjut hasil reviu SLA dan dukungan pengguna telah dilaksanakan.',
                    'unsur' => [
                        ['label' => 'Bukti tindak lanjut hasil reviu', 'keywords' => ['tindak lanjut hasil reviu', 'tindak lanjut reviu', 'telah ditindaklanjuti']],
                    ],
                ],
            ],
        ],

        'keamanan' => [
            'indicator' => 'Indikator 10 · Tingkat Kematangan Keamanan Pemerintah Digital',
            'levels' => [
                1 => [
                    'kriteria' => 'Tata kelola keamanan informasi dan identifikasi Infrastruktur Informasi Vital sedang disusun.',
                    'unsur' => [
                        ['label' => 'Rancangan tata kelola keamanan informasi', 'keywords' => ['keamanan informasi', 'keamanan siber', 'keamanan pemdi', 'keamanan spbe']],
                    ],
                ],
                2 => [
                    'kriteria' => 'Tata kelola manajemen keamanan informasi diterapkan sesuai pedoman; IIV sudah diidentifikasi.',
                    'unsur' => [
                        ['label' => 'Kebijakan / pedoman keamanan informasi', 'keywords' => ['kebijakan keamanan informasi', 'pedoman keamanan informasi', 'sistem manajemen keamanan informasi', 'smki', 'iso/iec 27001', 'iso 27001']],
                        ['label' => 'Identifikasi Infrastruktur Informasi Vital', 'keywords' => ['infrastruktur informasi vital', 'iiv', 'aset informasi vital']],
                    ],
                ],
                3 => [
                    'kriteria' => 'Kontrol teknis diterapkan berdasarkan hasil pengukuran kematangan keamanan siber.',
                    'unsur' => [
                        ['label' => 'Hasil pengukuran kematangan (IKAS / Indeks KAMI)', 'keywords' => ['ikasandi', 'indeks keamanan informasi', 'indeks kami', 'tingkat kematangan keamanan', 'pengukuran kematangan']],
                        ['label' => 'Implementasi kontrol teknis', 'keywords' => ['kontrol teknis', 'kontrol keamanan', 'hardening', 'firewall', 'enkripsi', 'pengendalian akses']],
                    ],
                ],
                4 => [
                    'kriteria' => 'Tata kelola keamanan terorganisir, terpadu, dan direviu berkala.',
                    'unsur' => [
                        ['label' => 'Reviu / audit keamanan berkala', 'keywords' => ['audit keamanan', 'reviu keamanan', 'reviu berkala', 'evaluasi berkala', 'penilaian keamanan']],
                    ],
                ],
                5 => [
                    'kriteria' => 'Tindak lanjut hasil reviu keamanan dilaksanakan melalui otomasi.',
                    'unsur' => [
                        ['label' => 'Tindak lanjut terotomasi', 'keywords' => ['otomasi', 'otomatisasi', 'secara otomatis']],
                        ['label' => 'Bukti tindak lanjut hasil reviu', 'keywords' => ['tindak lanjut hasil reviu', 'tindak lanjut audit', 'telah ditindaklanjuti']],
                    ],
                ],
            ],
        ],

        'data_informasi' => [
            'indicator' => 'Indikator 5 · Tingkat Kematangan Tata Kelola Data',
            'levels' => [
                1 => [
                    'kriteria' => 'Penyiapan penyelenggara Satu Data Indonesia dan dokumentasi dasar tata kelola data.',
                    'unsur' => [
                        ['label' => 'Dokumentasi inisiatif tata kelola data', 'keywords' => ['satu data', 'tata kelola data', 'pengelolaan data']],
                    ],
                ],
                2 => [
                    'kriteria' => 'Penyelenggara SDI terbentuk; metadata/standar data mulai disusun; ada pemanfaatan data untuk layanan.',
                    'unsur' => [
                        ['label' => 'Penyelenggara SDI (walidata / produsen data)', 'keywords' => ['walidata', 'produsen data', 'forum satu data', 'pembina data']],
                        ['label' => 'Metadata / standar data', 'keywords' => ['metadata', 'standar data', 'kode referensi', 'data induk']],
                    ],
                ],
                3 => [
                    'kriteria' => 'Kelembagaan & regulasi dasar tersedia; sebagian besar data punya standar & metadata; ada berbagi pakai data.',
                    'unsur' => [
                        ['label' => 'Regulasi / kelembagaan tata kelola data', 'keywords' => ['peraturan wali kota', 'peraturan bupati', 'keputusan', 'satu data indonesia']],
                        ['label' => 'Sistem berbagi pakai data', 'keywords' => ['berbagi pakai data', 'portal data', 'portal satu data', 'interoperabilitas data', 'pertukaran data']],
                    ],
                ],
                4 => [
                    'kriteria' => 'Integrasi sistem, data dipakai lintas instansi, pemanfaatan data dievaluasi.',
                    'unsur' => [
                        ['label' => 'Evaluasi pemanfaatan data lintas instansi', 'keywords' => ['evaluasi pemanfaatan data', 'lintas instansi', 'dasbor presiden', 'evaluasi data']],
                    ],
                ],
                5 => [
                    'kriteria' => 'Prinsip SDI menjadi budaya kerja; tindak lanjut hasil reviu dilaksanakan.',
                    'unsur' => [
                        ['label' => 'Bukti tindak lanjut hasil reviu', 'keywords' => ['tindak lanjut hasil reviu', 'tindak lanjut reviu', 'telah ditindaklanjuti']],
                    ],
                ],
            ],
        ],

        'aset' => [
            'indicator' => 'Indikator 14 · Tingkat Kematangan Infrastruktur Pemerintah Digital',
            'levels' => [
                1 => [
                    'kriteria' => 'Arsitektur teknologi tingkat mikro masih disusun sebagai acuan infrastruktur.',
                    'unsur' => [
                        ['label' => 'Rancangan arsitektur / inventaris aset', 'keywords' => ['arsitektur', 'inventaris', 'daftar aset', 'aset tik']],
                    ],
                ],
                2 => [
                    'kriteria' => 'Arsitektur menjadi acuan penyediaan infrastruktur; sebagian layanan berbasis teknologi awan.',
                    'unsur' => [
                        ['label' => 'Arsitektur teknologi sebagai acuan', 'keywords' => ['arsitektur teknologi', 'arsitektur spbe', 'arsitektur pemdi', 'arsitektur infrastruktur']],
                        ['label' => 'Inventaris / register aset TIK', 'keywords' => ['inventaris aset', 'register aset', 'daftar aset', 'kode aset']],
                        ['label' => 'Pemanfaatan pusat data / komputasi awan', 'keywords' => ['komputasi awan', 'cloud', 'pusat data', 'data center']],
                    ],
                ],
                3 => [
                    'kriteria' => 'Seluruh layanan prioritas/tematik berbasis teknologi awan.',
                    'unsur' => [
                        ['label' => 'Cakupan seluruh layanan prioritas', 'keywords' => ['seluruh layanan', 'layanan prioritas', 'semua aplikasi']],
                    ],
                ],
                4 => [
                    'kriteria' => 'Terintegrasi dengan infrastruktur nasional dan direviu berkala.',
                    'unsur' => [
                        ['label' => 'Integrasi PDN / Jaringan Intra Pemerintah', 'keywords' => ['pusat data nasional', 'jaringan intra pemerintah', 'infrastruktur digital pemerintah nasional']],
                        ['label' => 'Reviu berkala', 'keywords' => ['reviu', 'review', 'evaluasi berkala']],
                    ],
                ],
                5 => [
                    'kriteria' => 'Tindak lanjut hasil reviu pemanfaatan infrastruktur nasional dilaksanakan.',
                    'unsur' => [
                        ['label' => 'Bukti tindak lanjut hasil reviu', 'keywords' => ['tindak lanjut hasil reviu', 'tindak lanjut reviu', 'telah ditindaklanjuti', 'keberlangsungan layanan']],
                    ],
                ],
            ],
        ],

        'sdm' => [
            'indicator' => 'Indikator 3 · Tingkat Kematangan SDM Pemerintah Digital',
            'levels' => [
                1 => [
                    'kriteria' => 'Persiapan peningkatan kompetensi digital ASN berbasis peta kompetensi.',
                    'unsur' => [
                        ['label' => 'Peta / rencana kompetensi digital', 'keywords' => ['peta kompetensi', 'kompetensi digital', 'analisis kebutuhan pelatihan', 'rencana pengembangan kompetensi']],
                    ],
                ],
                2 => [
                    'kriteria' => 'Peningkatan kompetensi digital sudah dilaksanakan untuk lebih dari 90% ASN pengelola.',
                    'unsur' => [
                        ['label' => 'Pelaksanaan pelatihan / bimtek', 'keywords' => ['pelatihan', 'bimbingan teknis', 'bimtek', 'diklat', 'sertifikasi']],
                        ['label' => 'Daftar peserta / capaian ASN pengelola', 'keywords' => ['daftar peserta', 'daftar hadir', 'rekapitulasi peserta', 'asn pengelola', 'persentase']],
                    ],
                ],
                3 => [
                    'kriteria' => 'Lebih dari 90% ASN pengelola dan pengguna layanan digital sudah ditingkatkan kompetensinya.',
                    'unsur' => [
                        ['label' => 'Cakupan ASN pengguna layanan', 'keywords' => ['asn pengguna', 'pengguna layanan', 'seluruh asn', 'seluruh pegawai']],
                    ],
                ],
                4 => [
                    'kriteria' => 'ASN mampu memanfaatkan AI dan analisis data secara intensif.',
                    'unsur' => [
                        ['label' => 'Kompetensi AI & analisis data', 'keywords' => ['kecerdasan artifisial', 'kecerdasan buatan', 'artificial intelligence', 'analisis data', 'analitik data']],
                    ],
                ],
                5 => [
                    'kriteria' => 'ASN memanfaatkan AI, analisis data, dan teknologi mutakhir secara intensif.',
                    'unsur' => [
                        ['label' => 'Pemanfaatan teknologi mutakhir', 'keywords' => ['teknologi mutakhir', 'teknologi terkini', 'inovasi digital']],
                    ],
                ],
            ],
        ],
    ],
];
