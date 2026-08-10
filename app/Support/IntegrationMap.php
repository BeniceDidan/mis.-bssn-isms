<?php

namespace App\Support;

/**
 * Plain-language answer to "modul ini nyambung ke mana?", shown as a pop-out
 * on each of the 8 module index pages (see components/integration-map).
 *
 * The guided tour explains *how to operate* a module; this explains *where its
 * data goes*. Written for Kominfo staff reading it cold, so every entry names
 * the linking key in the same words the forms use ("Kode Aset", "Kode
 * Personil") rather than column names.
 *
 * Keys match AdminModules::LABELS and the startModuleTour() ids.
 *
 * Every claim here is load-bearing — it mirrors real behavior:
 *   - asset_id FKs                    -> App\Traits\BridgesToAsset
 *   - service_assets pivot            -> App\Models\Service
 *   - personnel_ref exact-match       -> App\Livewire\Concerns\GeneratesPersonnelRef
 *   - risk tinggi/kritis auto-records -> App\Services\RiskEscalationResponseService
 *   - SDM raising linked risk levels  -> App\Models\HrRisk::booted()
 * If any of those change, change the wording here too.
 */
class IntegrationMap
{
    /**
     * flows[].type drives the badge in the UI:
     *   'auto'   — happens by itself, no one has to remember it
     *   'manual' — only happens when someone fills the linking field
     */
    public const MAP = [
        'sdm' => [
            'label' => 'Manajemen SDM',
            'color' => 'slate',
            'keys' => ['Kode Personil'],
            'intro' => 'SDM sengaja tidak punya kolom "Aset Terkait" — ini satu-satunya modul yang tidak menempel langsung ke Aset. Sambungannya lewat Kode Personil, dan hanya nyambung kalau kodenya ditulis sama persis.',
            'flows' => [
                [
                    'type' => 'auto',
                    'to' => 'Manajemen Risiko',
                    'text' => 'Kalau risiko seorang pegawai di sini berlevel Tinggi atau Kritis, semua Risiko yang memakai Kode Personil yang sama otomatis dinaikkan levelnya jadi Tinggi, lengkap dengan catatan alasannya.',
                ],
                [
                    'type' => 'manual',
                    'to' => 'Manajemen Pengetahuan',
                    'text' => 'Profil keahlian orang yang sama muncul berdampingan, asal Kode Personilnya cocok. Berguna untuk melihat siapa yang berisiko sekaligus siapa yang memegang pengetahuan kunci.',
                ],
                [
                    'type' => 'manual',
                    'to' => 'Aset, Perubahan, Layanan, Keamanan Informasi, Data & Informasi',
                    'text' => 'Semua modul itu juga punya kolom Kode Personil. Isi dengan kode yang sama untuk menandai bahwa orang atau pihak ketiga yang terlibat adalah orang yang sama.',
                ],
            ],
            'warning' => 'Sistem tidak pernah menebak berdasarkan kemiripan nama. "Budi Santoso" di dua modul dianggap dua orang berbeda sampai Kode Personilnya benar-benar sama.',
        ],

        'pengetahuan' => [
            'label' => 'Manajemen Pengetahuan',
            'color' => 'amber',
            'keys' => ['Kode Aset', 'Kode Personil'],
            'intro' => 'Modul ini punya 4 tab, dan tiap tab nyambung ke tempat yang berbeda. Aset Pengetahuan memakai Kode Aset, Peta Keahlian memakai Kode Personil.',
            'flows' => [
                [
                    'type' => 'manual',
                    'to' => 'Manajemen Aset',
                    'text' => 'Aset Pengetahuan (manual book, dokumentasi) bisa ditautkan ke satu Aset TIK. Setelah ditautkan, dokumen itu muncul di panel "Riwayat Terkait" aset tersebut.',
                ],
                [
                    'type' => 'manual',
                    'to' => 'Manajemen SDM',
                    'text' => 'Peta Keahlian nyambung ke risiko SDM orang yang sama lewat Kode Personil — memperlihatkan siapa ahlinya dan sekaligus risikonya apa.',
                ],
                [
                    'type' => 'auto',
                    'to' => 'Manajemen Keamanan Informasi',
                    'text' => 'Jumlah Aktivitas Berbagi dihitung otomatis di sana sebagai bukti pelaksanaan item 5 (sosialisasi, pelatihan, bimbingan). Tidak perlu dilaporkan ulang.',
                ],
            ],
        ],

        'aset' => [
            'label' => 'Manajemen Aset',
            'color' => 'teal',
            'keys' => ['Kode Aset'],
            'intro' => 'Ini pusatnya. Enam modul lain menunjuk ke sini, jadi aset harus didaftarkan lebih dulu sebelum modul lain bisa menautkan diri padanya.',
            'flows' => [
                [
                    'type' => 'auto',
                    'to' => 'Riwayat Terkait (di halaman detail aset)',
                    'text' => 'Setiap kali ada Risiko, Perubahan, Data & Informasi, Pengetahuan, atau Keamanan Informasi yang tertaut ke sebuah aset dibuat atau diubah, aset itu ikut ditandai baru diperbarui dan kejadiannya muncul di Aktivitas Terbaru.',
                ],
                [
                    'type' => 'manual',
                    'to' => 'Manajemen Layanan',
                    'text' => 'Satu-satunya hubungan yang bisa banyak-ke-banyak: satu layanan boleh bergantung pada beberapa aset sekaligus, dan satu aset bisa menopang beberapa layanan.',
                ],
                [
                    'type' => 'manual',
                    'to' => 'Manajemen SDM',
                    'text' => 'Aset juga punya kolom Kode Personil, jadi penanggung jawab aset bisa dihubungkan ke catatan risiko SDM orang yang sama.',
                ],
            ],
            'warning' => 'Kalau sebuah aset dihapus atau diarsipkan, catatan di modul lain tidak ikut hilang — tautannya saja yang kosong. Periksa "Riwayat Terkait" dulu sebelum mengarsipkan aset.',
        ],

        'keamanan' => [
            'label' => 'Manajemen Keamanan Informasi',
            'color' => 'emerald',
            'keys' => ['Kode Aset', 'Kode Personil'],
            'intro' => 'Modul ini lebih banyak menerima daripada mengirim. Sebagian isinya terisi sendiri dari modul lain sebagai bukti pelaksanaan program SPBE.',
            'flows' => [
                [
                    'type' => 'auto',
                    'to' => 'dari Manajemen Risiko',
                    'text' => 'Setiap Risiko berlevel Tinggi atau Kritis otomatis melahirkan satu program tindak lanjut keamanan di sini. Tidak perlu dibuat manual.',
                ],
                [
                    'type' => 'auto',
                    'to' => 'dari Aset, Risiko, dan Pengetahuan',
                    'text' => 'Panel "Bukti Pelaksanaan Lintas Modul" menghitung sendiri jumlah aset terinventarisasi, risiko teridentifikasi, dan aktivitas sosialisasi — itu angka hidup, bukan ketikan manual.',
                ],
                [
                    'type' => 'manual',
                    'to' => 'Manajemen Aset',
                    'text' => 'Program kerja bisa ditautkan ke satu aset supaya muncul di Riwayat Terkait aset itu.',
                ],
            ],
        ],

        'risiko' => [
            'label' => 'Manajemen Risiko',
            'color' => 'purple',
            'keys' => ['Kode Aset', 'Kode Personil'],
            'intro' => 'Modul dengan efek paling luas. Menaikkan level sebuah risiko ke Tinggi atau Kritis akan membuat catatan baru di empat modul sekaligus, otomatis.',
            'flows' => [
                [
                    'type' => 'auto',
                    'to' => 'Keamanan Informasi, Perubahan, Data & Informasi',
                    'text' => 'Begitu level risiko menjadi Tinggi atau Kritis, tindak lanjut otomatis dibuat di ketiga modul itu. Sistem menandainya supaya tidak dibuat dua kali walau risikonya disimpan berulang.',
                ],
                [
                    'type' => 'auto',
                    'to' => 'Manajemen Layanan',
                    'text' => 'Kalau aset yang terkena risiko itu menopang sebuah layanan, tiket layanan ikut dibuat otomatis. Kalau asetnya tidak punya layanan, tidak ada tiket.',
                ],
                [
                    'type' => 'auto',
                    'to' => 'dari Manajemen SDM',
                    'text' => 'Arah sebaliknya juga berlaku: kalau ada risiko SDM berlevel Tinggi/Kritis dengan Kode Personil yang sama, level risiko di sini ikut dinaikkan otomatis.',
                ],
                [
                    'type' => 'manual',
                    'to' => 'Manajemen Aset',
                    'text' => 'Pilih "Aset Terkait" saat mengisi form supaya risiko ini muncul di Riwayat Terkait aset tersebut.',
                ],
            ],
            'warning' => 'Karena satu perubahan level bisa melahirkan empat catatan baru, periksa dulu sebelum menaikkan level risiko secara massal lewat impor Excel.',
        ],

        'perubahan' => [
            'label' => 'Manajemen Perubahan',
            'color' => 'indigo',
            'keys' => ['Kode Aset', 'Kode Personil'],
            'intro' => 'Mencatat revisi dan modifikasi terhadap aset. Sebagian catatannya bisa lahir sendiri dari Manajemen Risiko.',
            'flows' => [
                [
                    'type' => 'auto',
                    'to' => 'dari Manajemen Risiko',
                    'text' => 'Risiko berlevel Tinggi atau Kritis otomatis membuat usulan perubahan di sini sebagai tindak lanjut.',
                ],
                [
                    'type' => 'manual',
                    'to' => 'Manajemen Aset',
                    'text' => 'Isi "Aset Terkait" supaya perubahan ini tercatat di Riwayat Terkait aset dan asetnya ikut ditandai baru diperbarui.',
                ],
                [
                    'type' => 'auto',
                    'to' => 'saat impor Excel',
                    'text' => 'Kolom "Aset Terkait" di file Excel dicocokkan otomatis ke aset yang sudah terdaftar, jadi tidak perlu menautkan satu per satu.',
                ],
            ],
        ],

        'layanan' => [
            'label' => 'Manajemen Layanan',
            'color' => 'rose',
            'keys' => ['Kode Aset', 'Kode Personil'],
            'intro' => 'Satu-satunya modul yang boleh bergantung pada banyak aset sekaligus — karena satu layanan publik memang biasanya ditopang beberapa aset TIK.',
            'flows' => [
                [
                    'type' => 'manual',
                    'to' => 'Manajemen Aset',
                    'text' => 'Pilih beberapa aset sekaligus di "Aset TIK Terkait". Perubahan pada layanan akan muncul di Riwayat Terkait semua aset yang dipilih.',
                ],
                [
                    'type' => 'auto',
                    'to' => 'dari Manajemen Risiko',
                    'text' => 'Kalau ada risiko Tinggi/Kritis pada aset yang menopang layanan ini, tiket operasional otomatis dibuat supaya gangguannya tidak terlewat.',
                ],
                [
                    'type' => 'auto',
                    'to' => 'saat impor Excel',
                    'text' => 'Kolom "Aset TIK Terkait" boleh berisi beberapa nama aset, dan semuanya dicocokkan otomatis saat impor.',
                ],
            ],
        ],

        'data_informasi' => [
            'label' => 'Manajemen Data & Informasi',
            'color' => 'sky',
            'keys' => ['Kode Aset', 'Kode Personil'],
            'intro' => 'Tata kelola risiko data. Menempel ke Aset, dan sebagian isinya lahir otomatis sebagai tindak lanjut risiko.',
            'flows' => [
                [
                    'type' => 'auto',
                    'to' => 'dari Manajemen Risiko',
                    'text' => 'Risiko berlevel Tinggi atau Kritis otomatis membuat catatan tinjauan data di sini.',
                ],
                [
                    'type' => 'manual',
                    'to' => 'Manajemen Aset',
                    'text' => 'Isi "Aset Terkait" supaya catatan ini muncul di Riwayat Terkait aset yang datanya dimaksud.',
                ],
                [
                    'type' => 'auto',
                    'to' => 'saat impor Excel',
                    'text' => 'Sama seperti Perubahan — kolom aset di Excel ditautkan otomatis ke aset yang sudah ada.',
                ],
            ],
        ],
    ];

    /** @return array<string, mixed>|null */
    public static function for(?string $key): ?array
    {
        return $key ? (self::MAP[$key] ?? null) : null;
    }
}
