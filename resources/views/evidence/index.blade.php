@extends('layouts.app')

@section('title', 'Evaluasi Bukti Dukung')

@section('content')
    <div
        x-data="{ tab: new URLSearchParams(window.location.search).get('tab') || 'hasil', toast: null }"
        x-on:toast.window="toast = $event.detail.message; setTimeout(() => toast = null, 3000)"
        class="space-y-4"
    >
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-semibold text-gray-900">Evaluasi Bukti Dukung</h1>
            @if (auth()->user()?->canWrite())
                <button
                    x-ripple
                    @click="$dispatch('open-evidence-upload')"
                    class="inline-flex items-center gap-1.5 rounded-full bg-brand-700 px-4 py-2 text-sm font-medium text-white"
                >
                    <x-heroicon-o-arrow-up-tray class="h-4 w-4" />
                    Unggah
                </button>
            @endif
        </div>

        <div class="glass-panel flex flex-wrap gap-1 rounded-2xl p-1.5 shadow-lg">
            @foreach ([['key' => 'hasil', 'label' => 'Hasil'], ['key' => 'rubrik', 'label' => 'Rubrik (draf)']] as $t)
                <button
                    x-ripple
                    @click="tab = '{{ $t['key'] }}'"
                    :class="tab === '{{ $t['key'] }}' ? 'bg-brand-700 text-white shadow-md' : 'text-gray-600 hover:bg-slate-50'"
                    class="rounded-xl px-3 py-2 text-sm font-medium transition-all duration-300 ease-spring"
                >{{ $t['label'] }}</button>
            @endforeach
        </div>

        <div x-show="tab === 'hasil'" x-cloak>
            <livewire:evidence.evidence-table />
        </div>

        <div x-show="tab === 'rubrik'" x-cloak class="space-y-4">
            @foreach (config('bukti_dukung.modules') as $key => $module)
                <div class="glass-panel overflow-hidden rounded-2xl">
                    <p class="border-b border-slate-200 px-5 py-3 font-medium text-gray-900">{{ \App\Support\AdminModules::label($key) }}</p>
                    <div class="divide-y divide-slate-200">
                        @foreach ($module['levels'] as $no => $level)
                            <div class="grid gap-1 px-5 py-3 md:grid-cols-[10rem_1fr]">
                                <p class="text-sm text-gray-500">Level {{ $no }}</p>
                                <p class="text-sm text-gray-700">{{ collect($level['unsur'])->pluck('label')->implode(', ') }}</p>
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
