@extends('layouts.app')

@section('title', 'Panduan Penggunaan')

@php
    $flowSteps = [
        ['n' => 1, 'label' => 'Manajemen SDM', 'icon' => 'o-users', 'color' => 'slate', 'active' => true, 'desc' => 'Catat risiko terkait pegawai & pihak ketiga yang mengelola TIK.'],
        ['n' => 2, 'label' => 'Manajemen Pengetahuan', 'icon' => 'o-light-bulb', 'color' => 'amber', 'active' => true, 'desc' => 'Register aset pengetahuan, peta keahlian pegawai, aktivitas berbagi, dan risiko KM — 4 tab dalam satu modul.'],
        ['n' => 3, 'label' => 'Manajemen Aset', 'icon' => 'o-archive-box', 'color' => 'teal', 'active' => true, 'desc' => 'Pusat data — semua aset TIK didaftarkan di sini terlebih dahulu.'],
        ['n' => 4, 'label' => 'Manajemen Keamanan Informasi', 'icon' => 'o-shield-check', 'color' => 'emerald', 'active' => true, 'desc' => 'Program kerja keamanan informasi SPBE (pedoman, perencanaan, standar teknis).'],
        ['n' => 5, 'label' => 'Manajemen Risiko', 'icon' => 'o-exclamation-triangle', 'color' => 'purple', 'active' => true, 'desc' => 'Risiko diperiksa dan dicatat dari aset yang sudah terdaftar.'],
        ['n' => 6, 'label' => 'Manajemen Perubahan', 'icon' => 'o-arrow-path', 'color' => 'indigo', 'active' => true, 'desc' => 'Revisi/modifikasi terhadap aset dicatat di sini.'],
        ['n' => 7, 'label' => 'Manajemen Layanan', 'icon' => 'o-wrench-screwdriver', 'color' => 'rose', 'active' => true, 'desc' => 'Katalog layanan SPBE, tiket operasional, dan evaluasi SLA — bergantung pada aset yang sudah didaftarkan.'],
        ['n' => 8, 'label' => 'Manajemen Data & Informasi', 'icon' => 'o-circle-stack', 'color' => 'sky', 'active' => true, 'desc' => 'Tata kelola risiko data hasil dari aset yang diperbarui.'],
    ];

    $colorClasses = [
        'teal' => 'from-teal-400 to-teal-600',
        'purple' => 'from-purple-400 to-purple-600',
        'sky' => 'from-sky-400 to-sky-600',
        'emerald' => 'from-emerald-400 to-emerald-600',
        'amber' => 'from-amber-400 to-amber-600',
        'indigo' => 'from-indigo-400 to-indigo-600',
        'rose' => 'from-rose-400 to-rose-600',
        'slate' => 'from-slate-400 to-slate-600',
    ];

    $linkedSpokes = [
        ['label' => 'Manajemen Risiko', 'icon' => 'o-exclamation-triangle', 'color' => 'text-purple-600 bg-purple-50', 'desc' => 'Setiap risiko dicatat dengan memilih "Aset Terkait" pada form.'],
        ['label' => 'Manajemen Perubahan', 'icon' => 'o-arrow-path', 'color' => 'text-indigo-600 bg-indigo-50', 'desc' => 'Kolom "Aset Terkait" pada Excel otomatis dicocokkan ke aset saat impor.'],
        ['label' => 'Manajemen Data & Informasi', 'icon' => 'o-circle-stack', 'color' => 'text-sky-600 bg-sky-50', 'desc' => 'Sama seperti Perubahan — tertaut otomatis saat impor Excel.'],
        ['label' => 'Manajemen Pengetahuan', 'icon' => 'o-light-bulb', 'color' => 'text-amber-600 bg-amber-50', 'desc' => 'Ditautkan secara manual lewat form (tidak otomatis dari Excel).'],
        ['label' => 'Manajemen Layanan', 'icon' => 'o-wrench-screwdriver', 'color' => 'text-rose-600 bg-rose-50', 'desc' => 'Satu layanan bisa bergantung pada beberapa aset sekaligus — kolom "Aset TIK Terkait" pada Excel dicocokkan otomatis ke semua aset yang disebut.'],
    ];

    $correlationLayers = [
        [
            'title' => 'Lapisan 1 — Fondasi (The Enabler)',
            'desc' => 'SDM & pihak ketiga adalah aktor utama. Tanpa SDM ini, seluruh perangkat dan sistem TIK tidak akan dapat berjalan.',
            'actors' => ['Admin', 'Developer', 'Analis', 'Vendor', 'Penyedia'],
            'modules' => ['Manajemen SDM'],
        ],
        [
            'title' => 'Lapisan 2 — Operasional & Layanan',
            'desc' => 'SDM pada lapisan 1 mengoperasikan 3 komponen teknis utama, yang kemudian disatukan oleh Layanan sebagai bentuk pelayanan nyata bagi pengguna.',
            'components' => [
                ['label' => 'Perangkat Keras & Sarana Pendukung', 'desc' => 'Menyiapkan server, PC, serta ruang data center.', 'icon' => 'o-server'],
                ['label' => 'Jaringan TIK', 'desc' => 'Menyediakan jalur komunikasi data — LAN, WAN, router, firewall, bandwidth.', 'icon' => 'o-signal'],
                ['label' => 'Perangkat Lunak & Aplikasi', 'desc' => 'Menjalankan sistem informasi, basis data, dan aplikasi web/mobile.', 'icon' => 'o-code-bracket'],
            ],
            'componentsNote' => 'Ketiganya dicatat sebagai satu kesatuan lewat Manajemen Aset (dibedakan lewat kategori aset) — Manajemen Perubahan mencatat setiap revisi yang terjadi padanya.',
            'unifier' => ['label' => 'Manajemen Layanan', 'desc' => 'Menjadikan seluruh kombinasi hardware, network, dan software di atas sebagai bentuk pelayanan nyata bagi pengguna.'],
        ],
        [
            'title' => 'Lapisan 3 — Pengaman & Pembelajaran',
            'desc' => 'Kedua fungsi ini bekerja secara simultan menjaga kualitas dan kelangsungan sistem.',
            'groups' => [
                ['label' => 'Risiko & Keamanan Informasi', 'desc' => 'Memetakan ancaman seperti cyber attack, server down, peretasan, dan sebagainya.', 'modules' => ['Manajemen Risiko', 'Manajemen Keamanan Informasi']],
                ['label' => 'Pengetahuan (Knowledge Management)', 'desc' => 'Merekam seluruh riwayat troubleshooting, SOP instalasi, panduan aplikasi, serta solusi atas risiko yang pernah terjadi.', 'modules' => ['Manajemen Pengetahuan']],
            ],
        ],
        [
            'title' => 'Lapisan 4 — Nilai & Aset Utama',
            'desc' => 'Muara dari seluruh kerja keras SDM, infrastruktur, layanan, keamanan, dan pengetahuan: menjaga kerahasiaan, keutuhan, dan ketersediaan data & informasi organisasi.',
            'modules' => ['Manajemen Data & Informasi'],
        ],
    ];

    $correlationSummary = 'SDM TIK dan pihak ketiga membangun dan mengoperasikan server, jaringan, dan aplikasi untuk disajikan sebagai layanan TIK — agar layanan tersebut aman dari gangguan dan terstruktur. Prosesnya dipagari oleh mitigasi risiko keamanan, serta didokumentasikan ke dalam SOP dan knowledge base Manajemen Pengetahuan.';

    $reviewWorkflow = [
        ['label' => 'Manajemen SDM', 'output' => 'Mencari temuan/inovasi terbaru dari aplikasi.'],
        ['label' => 'Manajemen Pengetahuan', 'output' => 'Mengecek SOP dan menyamakannya dengan data valid.'],
        ['label' => 'Manajemen Aset', 'output' => 'Mengecek apakah aplikasi sudah sesuai spesifikasi Diskominfo.'],
        ['label' => 'Manajemen Keamanan Informasi', 'output' => 'Memastikan aplikasi sudah aman dari virus.'],
        ['label' => 'Manajemen Risiko', 'output' => 'Melakukan perhitungan risiko.'],
        ['label' => 'Manajemen Perubahan', 'output' => 'Mencatat & mengajukan perubahan dari aset yang bersangkutan.'],
        ['label' => 'Manajemen Layanan', 'output' => 'Memantau aksesibilitas aplikasi & menerima feedback pengguna.'],
        ['label' => 'Manajemen Data & Informasi', 'output' => 'Memastikan hasil akhir aplikasi sudah selesai dan valid.'],
    ];
@endphp

@section('content')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Panduan Penggunaan</h1>
            <p class="text-sm text-gray-500">Cara kerja sistem dan titik integrasi antar 8 manajemen</p>
        </div>
        <a
            href="{{ route('dashboard') }}"
            x-data x-ripple
            class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-sky-500 to-sky-600 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-sky-500/30 transition-all duration-300 ease-spring hover:scale-105 hover:shadow-sky-500/50 active:scale-95"
        >
            <x-heroicon-o-home class="h-4 w-4" /> Kembali ke Dashboard
        </a>
    </div>

    {{-- Section 1: alur 8 modul --}}
    <div class="glass-panel rounded-2xl p-6 shadow-lg">
        <h2 class="text-base font-semibold text-gray-900">1. Alur 8 Manajemen</h2>
        <p class="mt-1 text-sm text-gray-500">
            Urutannya mengikuti benang merah yang sudah dirancang: SDM &amp; Pengetahuan membekali pengelolaan
            Aset, Aset diperiksa keamanan &amp; risikonya, revisinya dicatat di Perubahan, lalu hasilnya mengalir
            ke Layanan dan Data &amp; Informasi. Nomor yang sama muncul di ubin Dashboard, jadi urutannya gampang
            diikuti dari sana juga.
        </p>

        <div class="mt-5 space-y-3">
            @foreach ($flowSteps as $step)
                <div class="flex items-start gap-4 rounded-xl border border-white/40 bg-white/40 p-3 transition-all duration-300 ease-spring hover:translate-x-1 hover:bg-white/70 {{ ! $step['active'] ? 'opacity-60' : '' }}">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br {{ $colorClasses[$step['color']] }} text-white shadow-md">
                        <x-dynamic-component :component="'heroicon-' . $step['icon']" class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-gray-900 text-[10px] font-bold text-white">{{ $step['n'] }}</span>
                            <h3 class="text-sm font-semibold text-gray-900">{{ $step['label'] }}</h3>
                            @if ($step['active'])
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">Aktif</span>
                            @else
                                <span class="rounded-full bg-gray-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-500">Segera Hadir</span>
                            @endif
                        </div>
                        <p class="mt-0.5 text-xs text-gray-500">{{ $step['desc'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Section 2: model korelasi 4 lapisan --}}
    <div class="glass-panel rounded-2xl p-6 shadow-lg">
        <h2 class="text-base font-semibold text-gray-900">2. Model Korelasi 4 Lapisan</h2>
        <p class="mt-1 text-sm text-gray-500">
            Cara lain melihat 8 manajemen di atas: bukan cuma urutan alur, tapi 4 lapisan yang saling menopang —
            dari fondasi SDM di paling bawah sampai data &amp; informasi sebagai muara nilai di paling atas.
        </p>

        <div class="mt-5 space-y-2">
            @foreach ($correlationLayers as $li => $layer)
                <div class="rounded-xl border border-white/40 bg-white/40 p-4">
                    <h3 class="text-sm font-semibold text-gray-900">{{ $layer['title'] }}</h3>
                    <p class="mt-1 text-xs text-gray-500">{{ $layer['desc'] }}</p>

                    @if (isset($layer['actors']))
                        {{-- Lapisan 1: aktor SDM & pihak ketiga sebagai pil kecil, lalu chip modul --}}
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach ($layer['actors'] as $actor)
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-600">{{ $actor }}</span>
                            @endforeach
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($layer['modules'] as $moduleLabel)
                                @php $matched = collect($flowSteps)->firstWhere('label', $moduleLabel); @endphp
                                @if ($matched)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r {{ $colorClasses[$matched['color']] }} px-3 py-1 text-xs font-medium text-white shadow-sm">
                                        <x-dynamic-component :component="'heroicon-' . $matched['icon']" class="h-3.5 w-3.5" />
                                        {{ $matched['label'] }}
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    @elseif (isset($layer['components']))
                        {{-- Lapisan 2: 3 komponen teknis (semuanya tercatat lewat Manajemen Aset), lalu disatukan oleh Manajemen Layanan --}}
                        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
                            @foreach ($layer['components'] as $component)
                                <div class="flex items-start gap-2 rounded-lg bg-white/60 p-2.5">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br {{ $colorClasses['teal'] }} text-white">
                                        <x-dynamic-component :component="'heroicon-' . $component['icon']" class="h-3.5 w-3.5" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-gray-800">{{ $component['label'] }}</p>
                                        <p class="mt-0.5 text-[11px] text-gray-500">{{ $component['desc'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-2 text-[11px] italic text-gray-400">{{ $layer['componentsNote'] }}</p>
                        <div class="mt-2 flex justify-center">
                            <x-heroicon-o-arrow-down class="h-4 w-4 text-gray-300" />
                        </div>
                        @php $unifierMatch = collect($flowSteps)->firstWhere('label', $layer['unifier']['label']); @endphp
                        <div class="flex justify-center">
                            @if ($unifierMatch)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r {{ $colorClasses[$unifierMatch['color']] }} px-3 py-1.5 text-xs font-medium text-white shadow-sm">
                                    <x-dynamic-component :component="'heroicon-' . $unifierMatch['icon']" class="h-3.5 w-3.5" />
                                    {{ $unifierMatch['label'] }}
                                </span>
                            @endif
                        </div>
                        <p class="mt-1.5 text-center text-[11px] text-gray-500">{{ $layer['unifier']['desc'] }}</p>
                    @elseif (isset($layer['groups']))
                        {{-- Lapisan 3: 2 fungsi berjalan simultan — masing-masing bisa berisi lebih dari 1 modul --}}
                        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            @foreach ($layer['groups'] as $group)
                                <div class="rounded-lg bg-white/60 p-2.5">
                                    <p class="text-xs font-semibold text-gray-800">{{ $group['label'] }}</p>
                                    <p class="mt-0.5 text-[11px] text-gray-500">{{ $group['desc'] }}</p>
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        @foreach ($group['modules'] as $moduleLabel)
                                            @php $matched = collect($flowSteps)->firstWhere('label', $moduleLabel); @endphp
                                            @if ($matched)
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r {{ $colorClasses[$matched['color']] }} px-2.5 py-1 text-[11px] font-medium text-white shadow-sm">
                                                    <x-dynamic-component :component="'heroicon-' . $matched['icon']" class="h-3 w-3" />
                                                    {{ $matched['label'] }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($layer['modules'] as $moduleLabel)
                                @php $matched = collect($flowSteps)->firstWhere('label', $moduleLabel); @endphp
                                @if ($matched)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r {{ $colorClasses[$matched['color']] }} px-3 py-1 text-xs font-medium text-white shadow-sm">
                                        <x-dynamic-component :component="'heroicon-' . $matched['icon']" class="h-3.5 w-3.5" />
                                        {{ $matched['label'] }}
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
                @if (! $loop->last)
                    <div class="flex justify-center py-0.5">
                        <x-heroicon-o-arrow-down class="h-4 w-4 text-gray-300" />
                    </div>
                @endif
            @endforeach
        </div>

        <div class="mt-4 rounded-xl border border-sky-200 bg-sky-50/70 p-4">
            <p class="flex items-center gap-1.5 text-sm font-semibold text-sky-800">
                <x-heroicon-o-squares-2x2 class="h-4 w-4" /> Ringkasan
            </p>
            <p class="mt-1 text-xs text-sky-700">{{ $correlationSummary }}</p>
        </div>
    </div>

    {{-- Section 3: titik integrasi nyata --}}
    <div class="glass-panel rounded-2xl p-6 shadow-lg">
        <h2 class="text-base font-semibold text-gray-900">3. Titik Integrasi yang Benar-Benar Berfungsi</h2>
        <p class="mt-1 text-sm text-gray-500">
            <span class="font-semibold text-gray-700">Manajemen Aset adalah pusatnya.</span>
            Empat modul lain menyimpan referensi (<code class="rounded bg-gray-100 px-1 py-0.5 text-[11px]">asset_id</code>) ke aset yang bersangkutan.
        </p>

        <div class="mt-5 flex flex-col items-center gap-4">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-400 to-teal-600 text-white shadow-lg shadow-teal-900/20">
                <x-heroicon-o-archive-box class="h-8 w-8" />
            </div>
            <span class="text-sm font-semibold text-gray-900">Manajemen Aset</span>

            <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach ($linkedSpokes as $spoke)
                    <div class="flex items-start gap-3 rounded-xl border border-white/40 bg-white/40 p-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $spoke['color'] }}">
                            <x-dynamic-component :component="'heroicon-' . $spoke['icon']" class="h-4 w-4" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900">{{ $spoke['label'] }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ $spoke['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-5 rounded-xl border border-sky-200 bg-sky-50/70 p-4">
            <p class="flex items-center gap-1.5 text-sm font-semibold text-sky-800">
                <x-heroicon-o-eye class="h-4 w-4" /> Cara melihatnya: panel "Riwayat Terkait"
            </p>
            <p class="mt-1 text-xs text-sky-700">
                Buka <span class="font-medium">Manajemen Aset</span> → klik ikon mata pada baris aset →
                gulir ke bawah pada panel yang muncul. Semua Risiko, Perubahan, Data &amp; Informasi, dan
                Pengetahuan yang tertaut ke aset tersebut akan tampil dalam satu tempat.
            </p>
        </div>

        <div class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50/70 p-4">
            <p class="flex items-center gap-1.5 text-sm font-semibold text-emerald-800">
                <x-heroicon-o-bolt class="h-4 w-4" /> Efek nyata — bukan cuma referensi diam
            </p>
            <p class="mt-1 text-xs text-emerald-700">
                Coba catat atau ubah sesuatu di Risiko, Perubahan, Data &amp; Informasi, atau Pengetahuan, lalu
                buka lagi asetnya — akan langsung muncul entri baru di "Aktivitas Aset Terbaru" lengkap dengan
                nama modul asalnya, badge angka di kolom "Terhubung" ikut naik, dan tanggal "terakhir diperbarui"
                aset itu ikut ter-refresh. Jadi perubahannya benar-benar kelihatan di modul lain, tidak cuma
                tersimpan sendiri-sendiri.
            </p>
        </div>

        <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50/70 p-4">
            <p class="flex items-center gap-1.5 text-sm font-semibold text-amber-800">
                <x-heroicon-o-sparkles class="h-4 w-4" /> Perubahan → Pengetahuan diingatkan otomatis
            </p>
            <p class="mt-1 text-xs text-amber-700">
                Kalau ada aset yang sudah punya catatan Perubahan tapi belum didokumentasikan di Pengetahuan,
                panel Riwayat Terkait akan menyapa dengan ajakan "buat dokumentasinya?" — tinggal klik, dan form
                Pengetahuan langsung terbuka dengan aset itu sudah terisi. Jadi tidak perlu mengingat-ingat
                sendiri mana yang belum didokumentasikan.
            </p>
        </div>

        <div class="mt-3 rounded-xl border border-gray-200 bg-gray-50/70 p-4">
            <p class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                <x-heroicon-o-shield-check class="h-4 w-4" /> Penaut otomatis punya pengaman
            </p>
            <p class="mt-1 text-xs text-gray-600">
                Saat impor Excel Perubahan/Data &amp; Informasi, kolom "Aset Terkait" dicocokkan ke kode aset
                <span class="font-medium">dan</span> namanya harus mirip. Kalau tidak mirip, data tetap disimpan
                apa adanya tapi <span class="font-medium">tidak</span> dipaksa tertaut — supaya tidak salah kaitkan
                aset yang berbeda. Manajemen SDM dan Manajemen Keamanan Informasi sengaja tidak punya
                <code class="rounded bg-white px-1 py-0.5">asset_id</code> (datanya soal risiko orang/pihak ketiga
                dan program kerja keamanan, bukan aset per item). Catatan operasional layanan (tiket &amp;
                evaluasi SLA pada modul Layanan) ikut tersimpan lengkap tapi ditautkan ke Risiko lewat teks bebas,
                bukan tautan otomatis — nomor risiko pada file sumbernya (mis. "R-02") memakai penomoran lama yang
                tidak sama dengan kode Risiko yang berjalan sekarang. Risiko KM pada tab "Risiko KM" di Manajemen
                Pengetahuan juga sedikit berbeda: itu ditautkan ke aset <span class="font-medium">pengetahuan</span>
                (kode AP-xxx), bukan ke aset TIK utama — jadi tidak muncul di panel Riwayat Terkait Aset.
            </p>
        </div>
    </div>

    {{-- Section 4: contoh alur kerja peninjauan aplikasi --}}
    <div class="glass-panel rounded-2xl p-6 shadow-lg">
        <h2 class="text-base font-semibold text-gray-900">4. Contoh Alur Kerja: Peninjauan Aplikasi</h2>
        <p class="mt-1 text-sm text-gray-500">
            Contoh nyata memakai kedelapan modul secara berurutan untuk satu tujuan: meninjau kelayakan sebuah
            aplikasi. Tiap modul punya output spesifik pada tahap ini.
        </p>

        <div class="mt-5 space-y-0">
            @foreach ($reviewWorkflow as $wi => $step)
                @php $matched = collect($flowSteps)->firstWhere('label', $step['label']); @endphp
                <div class="relative flex gap-4 pb-5 pl-1 {{ ! $loop->last ? 'border-l-2 border-dashed border-gray-200' : '' }}">
                    <div class="-ml-[21px] flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br {{ $colorClasses[$matched['color']] }} text-white shadow-md ring-4 ring-white">
                        <x-dynamic-component :component="'heroicon-' . $matched['icon']" class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1 pt-1.5">
                        <div class="flex items-center gap-2">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-gray-900 text-[10px] font-bold text-white">{{ $wi + 1 }}</span>
                            <h3 class="text-sm font-semibold text-gray-900">{{ $step['label'] }}</h3>
                        </div>
                        <p class="mt-1 text-xs text-gray-600">
                            <span class="font-semibold text-gray-700">Output:</span> {{ $step['output'] }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Section 5: langkah cepat --}}
    <div class="glass-panel rounded-2xl p-6 shadow-lg">
        <h2 class="text-base font-semibold text-gray-900">5. Langkah Cepat Memulai</h2>
        <ol class="mt-4 space-y-3">
            @foreach ([
                'Tambah atau impor data Aset TIK terlebih dahulu — ini dataset awal semua modul lainnya.',
                'Catat Risiko yang teridentifikasi dari aset tersebut, pilih "Aset Terkait" pada form.',
                'Saat ada revisi/modifikasi sistem, catat di Manajemen Perubahan dan tautkan ke aset yang sama.',
                'Perbarui data Aset bila hasil perubahan mengubah spesifikasi, status, atau kritikalitasnya.',
                'Dokumentasikan hasil akhirnya di Manajemen Pengetahuan (manual book) supaya bisa dipelajari ulang.',
                'Lihat semuanya sekaligus lewat panel "Riwayat Terkait" di halaman detail Aset.',
            ] as $index => $item)
                <li class="flex items-start gap-3 text-sm text-gray-700">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-sky-100 text-xs font-bold text-sky-700">{{ $index + 1 }}</span>
                    <span class="pt-0.5">{{ $item }}</span>
                </li>
            @endforeach
        </ol>
    </div>

    {{-- Section 4: status modul --}}
    <div class="glass-panel rounded-2xl p-6 shadow-lg">
        <h2 class="text-base font-semibold text-gray-900">6. Status Modul Saat Ini</h2>
        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-xl bg-emerald-50 p-3 text-center">
                <p class="text-2xl font-bold text-emerald-700">8</p>
                <p class="text-xs text-emerald-600">Modul Aktif</p>
            </div>
            <div class="rounded-xl bg-gray-100 p-3 text-center">
                <p class="text-2xl font-bold text-gray-500">0</p>
                <p class="text-xs text-gray-500">Segera Hadir</p>
            </div>
            <div class="rounded-xl bg-sky-50 p-3 text-center">
                <p class="text-2xl font-bold text-sky-700">5</p>
                <p class="text-xs text-sky-600">Modul Tertaut ke Aset</p>
            </div>
            <div class="rounded-xl bg-amber-50 p-3 text-center">
                <p class="text-2xl font-bold text-amber-700">2</p>
                <p class="text-xs text-amber-600">Modul Berdiri Sendiri (SDM, Keamanan Informasi)</p>
            </div>
        </div>
    </div>

    {{-- Section 5: alur verifikasi & peran --}}
    <div class="glass-panel rounded-2xl p-6 shadow-lg">
        <h2 class="text-base font-semibold text-gray-900">7. Alur Verifikasi &amp; Peran Pengguna</h2>
        <p class="mt-1 text-sm text-gray-500">
            Hanya ada dua peran: Admin dan User. Setiap catatan yang dibuat atau diubah User tidak langsung
            tersimpan final — semuanya melewati satu alur persetujuan yang sama dulu di seluruh modul aktif,
            termasuk 3 tab baru Manajemen Pengetahuan (Peta Keahlian, Aktivitas Berbagi, Risiko KM) dan
            Manajemen Keamanan Informasi.
        </p>

        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="rounded-xl border border-white/40 bg-white/40 p-3">
                <p class="text-sm font-semibold text-gray-900">User</p>
                <p class="mt-1 text-xs text-gray-600">Input/ubah data di semua modul. Setiap simpan (baru atau edit ulang) otomatis berstatus "Menunggu Verifikasi".</p>
            </div>
            <div class="rounded-xl border border-white/40 bg-white/40 p-3">
                <p class="text-sm font-semibold text-gray-900">Admin</p>
                <p class="mt-1 text-xs text-gray-600">Satu-satunya yang melihat menu "Verifikasi" — antrian gabungan dari seluruh modul sekaligus. Setujui (ACC) atau tolak dengan catatan koreksi.</p>
            </div>
        </div>

        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50/70 p-4">
            <p class="flex items-center gap-1.5 text-sm font-semibold text-amber-800">
                <x-heroicon-o-clipboard-document-check class="h-4 w-4" /> Cara kerjanya
            </p>
            <p class="mt-1 text-xs text-amber-700">
                Badge kuning "Menunggu Verifikasi" atau merah "Ditolak" muncul langsung di tabel setiap modul.
                Kalau ditolak, catatan koreksi Admin tersimpan permanen dan terlihat di panel detail record —
                User tinggal edit ulang, yang otomatis mengembalikannya ke antrian untuk ditinjau lagi.
            </p>
        </div>
    </div>
@endsection
