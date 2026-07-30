@extends('layouts.app')

@section('title', 'Manajemen Data & Informasi')

@section('content')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Manajemen Data & Informasi</h1>
            <p class="text-sm text-gray-500">Register risiko tata kelola data dan informasi (SPBE)</p>
        </div>
        @if (auth()->user()?->canWrite())
            <div class="flex items-center gap-2" x-data>
                <button
                    x-ripple
                    @click="$dispatch('open-data-information-importer')"
                    class="glass-panel inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-sm font-medium text-gray-700 transition-all duration-300 ease-spring hover:scale-105 hover:bg-white/80 active:scale-95"
                >
                    <x-heroicon-o-arrow-up-tray class="h-4 w-4" />
                    Impor Excel
                </button>
                <button
                    x-ripple
                    @click="$dispatch('open-data-information-form')"
                    class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-sky-500 to-sky-600 px-3 py-2 text-sm font-medium text-white shadow-lg shadow-sky-500/30 transition-all duration-300 ease-spring hover:scale-105 hover:shadow-sky-500/50 active:scale-95"
                >
                    <x-heroicon-o-plus class="h-4 w-4" />
                    Catatan Baru
                </button>
            </div>
        @endif
    </div>

    <livewire:data-information.data-information-stats />
    <livewire:data-information.data-information-table />
    <livewire:data-information.data-information-form-modal />
    <livewire:data-information.data-information-importer />

    @if (request()->boolean('create'))
        <div x-data x-init="$dispatch('open-data-information-form')"></div>
    @endif
@endsection
