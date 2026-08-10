@extends('layouts.app')

@section('title', 'Manajemen Perubahan')

@section('content')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Manajemen Perubahan</h1>
            <p class="text-sm text-gray-500">Register perubahan TIK berbasis penilaian risiko (SPBE)</p>
        </div>
        <div class="flex items-center gap-2" x-data>
            <button
                type="button"
                x-ripple
                onclick="window.startModuleTour && window.startModuleTour('perubahan')"
                title="Tutorial Modul Ini"
                class="glass-panel inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-xs font-medium text-sky-700 transition-all duration-300 ease-spring hover:scale-105 hover:bg-sky-50 active:scale-95"
            >
                <x-heroicon-o-play class="h-3.5 w-3.5" />
                Tutorial Modul Ini
            </button>
            <x-integration-map module="perubahan" />
            @if (auth()->user()?->canWrite())
                <button
                    x-ripple
                    @click="$dispatch('open-change-importer')"
                    class="glass-panel inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-sm font-medium text-gray-700 transition-all duration-300 ease-spring hover:scale-105 hover:bg-white/80 active:scale-95"
                >
                    <x-heroicon-o-arrow-up-tray class="h-4 w-4" />
                    Impor Excel
                </button>
                <button
                    x-ripple
                    data-tour="add-button"
                    @click="$dispatch('open-change-form')"
                    class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-sky-500 to-sky-600 px-3 py-2 text-sm font-medium text-white shadow-lg shadow-sky-500/30 transition-all duration-300 ease-spring hover:scale-105 hover:shadow-sky-500/50 active:scale-95"
                >
                    <x-heroicon-o-plus class="h-4 w-4" />
                    Perubahan Baru
                </button>
            @endif
        </div>
    </div>

    @if (auth()->user()?->admin_module === 'perubahan')
        <livewire:admin-verification-queue />
    @endif

    <livewire:changes.change-stats />
    <livewire:changes.change-table />
    <livewire:changes.change-form-modal />
    <livewire:changes.change-importer />

    @if (request()->boolean('create'))
        <div x-data x-init="$dispatch('open-change-form')"></div>
    @endif
@endsection
