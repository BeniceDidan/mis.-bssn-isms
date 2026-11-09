@extends('layouts.app')

@section('title', 'Evaluasi Bukti Dukung')

@section('content')
    <div
        x-data="{ tab: new URLSearchParams(window.location.search).get('tab') || 'hasil', toast: null }"
        x-on:toast.window="toast = $event.detail.message; setTimeout(() => toast = null, 3000)"
        class="space-y-4"
    >
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Evaluasi Bukti Dukung</h1>
                <p class="text-sm text-gray-500">Pemeriksaan otomatis dokumen PDF terhadap rubrik tingkat kematangan</p>
            </div>
            @if (auth()->user()?->canWrite())
                <button
                    x-ripple
                    @click="$dispatch('open-evidence-upload')"
                    class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-sky-500 to-sky-600 px-3 py-2 text-sm font-medium text-white shadow-lg shadow-sky-500/30 transition-all duration-300 ease-spring hover:scale-105 active:scale-95"
                >
                    <x-heroicon-o-arrow-up-tray class="h-4 w-4" />
                    Unggah Bukti Dukung
                </button>
            @endif
        </div>

        <div class="glass-panel flex flex-wrap gap-1 rounded-2xl p-1.5 shadow-lg">
            @foreach ([['key' => 'hasil', 'label' => 'Hasil Penilaian'], ['key' => 'rubrik', 'label' => 'Rubrik']] as $t)
                <button
                    x-ripple
                    @click="tab = '{{ $t['key'] }}'"
                    :class="tab === '{{ $t['key'] }}' ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-md' : 'text-gray-600 hover:bg-white/60'"
                    class="rounded-xl px-3 py-2 text-sm font-medium transition-all duration-300 ease-spring"
                >{{ $t['label'] }}</button>
            @endforeach
        </div>

        <div x-show="tab === 'hasil'" x-cloak>
            <livewire:evidence.evidence-table />
        </div>

        <div x-show="tab === 'rubrik'" x-cloak class="space-y-4">
            @if (config('bukti_dukung.status') === 'draf')
                <div class="rounded-xl border border-amber-200 bg-amber-50/80 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                    Rubrik masih draf dan belum ditinjau. Skor otomatis belum boleh dipakai sebagai nilai resmi.
                </div>
            @endif
            @foreach (config('bukti_dukung.modules') as $key => $module)
                <div class="glass-panel overflow-hidden rounded-2xl shadow-lg">
                    <div class="border-b border-white/40 px-5 py-3">
                        <p class="font-semibold text-gray-900">{{ \App\Support\AdminModules::label($key) }}</p>
                        <p class="text-xs text-gray-500">{{ $module['indicator'] }}</p>
                    </div>
                    <div class="divide-y divide-white/40">
                        @foreach ($module['levels'] as $no => $level)
                            <div class="grid gap-2 px-5 py-3 md:grid-cols-[10rem_1fr]">
                                <p class="text-sm font-medium text-gray-800">Level {{ $no }} · {{ config("bukti_dukung.levels.$no.name") }}</p>
                                <div>
                                    <p class="text-sm text-gray-600">{{ $level['kriteria'] }}</p>
                                    <ul class="mt-1 space-y-0.5 text-xs text-gray-500">
                                        @foreach ($level['unsur'] as $unsur)
                                            <li>{{ $unsur['label'] }} <span class="text-gray-400">— {{ implode(', ', $unsur['keywords']) }}</span></li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <livewire:evidence.evidence-upload-modal />
        <livewire:evidence.evidence-detail />

        <div
            x-show="toast"
            x-transition
            x-cloak
            class="fixed bottom-6 right-6 z-[60] rounded-full bg-slate-900/90 px-4 py-2 text-sm text-white shadow-xl"
            x-text="toast"
        ></div>
    </div>
@endsection
