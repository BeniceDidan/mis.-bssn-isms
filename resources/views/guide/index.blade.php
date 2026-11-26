@extends('layouts.app')

@section('title', 'Panduan')

{{--
    Panduan pengguna. Dasar (docs/riset/06-alur-dan-bahasa.md): Permenkomdigi
    6/2025 meminta manual penggunaan bagi pengguna dan administrator serta
    rancangan alur proses dan integrasi aplikasi SPBE. Tabel "Setelah Anda
    menyimpan" dan daftar per modul diambil dari App\Support\IntegrationMap,
    sumber yang sama dengan pop-out Alur Data dan Asisten.
--}}

@php
    $order = [
        ['Manajemen Aset', 'Daftarkan aset TIK lebih dulu. Hampir semua modul lain menautkan datanya ke aset.', 'assets.index'],
        ['Manajemen SDM', 'Catat pegawai dan pihak ketiga pengelola TIK beserta risikonya. Kode Personil menyambungkan orang yang sama di modul lain.', 'hr-risks.index'],
        ['Manajemen Risiko', 'Catat risiko pada aset. Risiko Tinggi atau Kritis otomatis membuat tindak lanjut di modul lain.', 'risks.index'],
        ['Manajemen Keamanan Informasi', 'Program kerja keamanan. Tindak lanjut risiko masuk ke sini.', 'security-programs.index'],
        ['Manajemen Perubahan', 'Catat perubahan pada aset, termasuk usulan yang dibuat dari risiko.', 'changes.index'],
        ['Manajemen Layanan', 'Daftar layanan dan aset yang dipakainya, tiket dari pengguna, dan evaluasi layanan.', 'services.index'],
        ['Manajemen Data & Informasi', 'Catatan pengelolaan data dan risiko pada data.', 'data-information.index'],
        ['Manajemen Pengetahuan', 'Simpan SOP dan manual supaya bisa dicari, dipakai Asisten, dan disarankan saat ada tiket.', 'knowledge.index'],
    ];
    $modules = collect(\App\Support\IntegrationMap::MAP)->map(fn ($m, $key) => \App\Support\IntegrationMap::for($key));
    $askExamples = [
        'Kalau risiko jadi kritis, datanya ke mana saja?',
        'File PDF yang saya unggah di Pengetahuan dipakai di mana?',
        'Apa itu Kode Personil?',
        'Dasar hukum Manajemen Layanan apa?',
        'Berapa aset kritis?',
    ];
@endphp

@section('content')
    <h1 class="text-xl font-semibold text-gray-900">Panduan</h1>

    <section class="glass-panel rounded-2xl p-6">
        <h2 class="text-base font-semibold text-gray-900">Urutan kerja</h2>
        <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-gray-700 marker:text-gray-400">
            @foreach ($order as [$label, $text, $route])
                <li><a href="{{ route($route) }}" class="font-medium text-gray-900 hover:text-brand-700 hover:underline">{{ $label }}</a>. {{ $text }}</li>
            @endforeach
        </ol>
        <p class="mt-3 text-sm text-gray-600">Bukti dukung untuk evaluasi diunggah terpisah di <a href="{{ route('evidence.index') }}" class="font-medium text-brand-700 hover:underline">Evaluasi Bukti Dukung</a>.</p>
    </section>

    <section id="alur" class="glass-panel scroll-mt-24 rounded-2xl p-6">
        <h2 class="text-base font-semibold text-gray-900">Setelah Anda menyimpan, datanya ke mana</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium text-gray-500">
                        <th class="py-2 pr-4 font-medium">Yang Anda lakukan</th>
                        <th class="py-2 pr-4 font-medium">Di</th>
                        <th class="py-2 font-medium">Yang terjadi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 align-top">
                    @foreach (\App\Support\IntegrationMap::EVENTS as $event)
                        <tr>
                            <td class="py-3 pr-4 font-medium text-gray-900">{{ $event['action'] }}</td>
                            <td class="py-3 pr-4 text-gray-600">{{ $event['where'] }}</td>
                            <td class="py-3 text-gray-700">{{ $event['result'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="glass-panel rounded-2xl p-6">
        <h2 class="text-base font-semibold text-gray-900">Penghubung antarmodul</h2>
        <dl class="mt-3 grid gap-4 text-sm sm:grid-cols-2">
            <div class="rounded-xl bg-slate-50 px-4 py-3">
                <dt class="font-medium text-gray-900">Kode Aset</dt>
                <dd class="mt-1 text-gray-600">Dipilih lewat kolom Aset Terkait. Data yang memilih aset muncul di aktivitas aset itu. Layanan boleh memilih beberapa aset sekaligus.</dd>
            </div>
            <div class="rounded-xl bg-slate-50 px-4 py-3">
                <dt class="font-medium text-gray-900">Kode Personil</dt>
                <dd class="mt-1 text-gray-600">Kode orang atau pihak ketiga, misalnya PSN-1A2B3C. Kode yang sama di modul mana pun berarti orang yang sama. Sistem tidak mencocokkan nama. Bila dikosongkan, kode dibuat otomatis.</dd>
            </div>
        </dl>
        <ul class="mt-4 list-disc space-y-1 pl-5 text-sm text-gray-600 marker:text-gray-400">
            <li>Saat impor Excel, aset hanya ditautkan bila kode dan namanya cocok. Bila tidak cocok, data tetap disimpan tanpa tautan.</li>
            <li>Detail aset di Manajemen Aset menampilkan semua data dari modul lain yang memilih aset itu. Bila aset sudah punya catatan perubahan tetapi belum punya dokumen pengetahuan, panel itu menawarkan untuk membuatnya.</li>
            <li>Risiko di tab Risiko KM (Manajemen Pengetahuan) tertaut ke aset pengetahuan, bukan ke aset TIK.</li>
        </ul>
    </section>

    <section class="glass-panel rounded-2xl p-6">
        <h2 class="text-base font-semibold text-gray-900">Alur per modul</h2>
        <div class="mt-3 divide-y divide-slate-200">
            @foreach ($modules as $key => $map)
                <details class="group py-3">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 [&::-webkit-details-marker]:hidden">
                        <span class="text-sm font-medium text-gray-900">{{ $map['label'] }}</span>
                        <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-400 transition group-open:rotate-180" />
                    </summary>
                    <p class="mt-2 text-sm text-gray-600">{{ $map['intro'] }}</p>
                    @foreach (['out' => 'Dikirim ke', 'in' => 'Datang dari'] as $dir => $heading)
                        @php $flows = collect($map['flows'])->where('dir', $dir)->reject(fn ($f) => $f['to'] === 'Asisten'); @endphp
                        @continue($flows->isEmpty())
                        <p class="mt-3 text-xs font-medium text-gray-500">{{ $heading }}</p>
                        <ul class="mt-1 space-y-1.5 text-sm">
                            @foreach ($flows as $flow)
                                <li><span class="font-medium text-gray-900">{{ $flow['to'] }}</span> <span class="text-xs {{ $flow['type'] === 'auto' ? 'text-brand-700' : 'text-gray-500' }}">({{ $flow['type'] === 'auto' ? 'otomatis' : 'bila kolomnya diisi' }})</span> <span class="text-gray-600">{{ $flow['text'] }}</span></li>
                            @endforeach
                        </ul>
                    @endforeach
                    @if (! empty($map['warning']))
                        <p class="mt-3 rounded-lg bg-brand-50 px-3 py-2 text-sm text-brand-900">{{ $map['warning'] }}</p>
                    @endif
                </details>
            @endforeach
        </div>
    </section>

    <section class="glass-panel rounded-2xl p-6">
        <h2 class="text-base font-semibold text-gray-900">Verifikasi dan peran</h2>
        <ul class="mt-3 list-disc space-y-1.5 pl-5 text-sm text-gray-700 marker:text-gray-400">
            <li>Ada dua peran: <span class="font-medium">User</span> dan <span class="font-medium">Admin</span>. Keduanya bisa menambah dan mengubah data di semua modul.</li>
            <li>Setiap admin memegang satu modul. Tidak ada admin yang menyetujui semua modul.</li>
            <li>Data yang disimpan user, atau admin modul lain, berstatus <span class="font-medium">Menunggu Verifikasi</span> dan masuk antrean di halaman modulnya.</li>
            <li>Admin modul menyetujui atau menolak dengan catatan. Data yang ditolak bisa diubah lalu masuk antrean lagi.</li>
        </ul>
    </section>

    <section class="glass-panel rounded-2xl p-6">
        <h2 class="text-base font-semibold text-gray-900">Tanya Asisten</h2>
        <p class="mt-1 text-sm text-gray-600">Tombol Asisten ada di pojok kanan bawah. Asisten bisa menjawab cara pakai, alur data, istilah, dasar aturan, dan menghitung data. Contoh:</p>
        <ul class="mt-3 flex flex-wrap gap-2">
            @foreach ($askExamples as $q)
                <li class="rounded-full bg-slate-100 px-3 py-1 text-sm text-gray-700">{{ $q }}</li>
            @endforeach
        </ul>
    </section>
@endsection
