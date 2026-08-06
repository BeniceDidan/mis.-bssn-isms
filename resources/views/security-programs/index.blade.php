@extends('layouts.app')

@section('title', 'Manajemen Keamanan Informasi')

@section('content')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Manajemen Keamanan Informasi</h1>
            <p class="text-sm text-gray-500">Program kerja keamanan informasi SPBE</p>
        </div>
        <div class="flex items-center gap-2" x-data>
            <button
                type="button"
                x-ripple
                onclick="window.startModuleTour && window.startModuleTour('keamanan')"
                title="Tutorial Modul Ini"
                class="glass-panel inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-xs font-medium text-emerald-700 transition-all duration-300 ease-spring hover:scale-105 hover:bg-emerald-50 active:scale-95"
            >
                <x-heroicon-o-play class="h-3.5 w-3.5" />
                Tutorial Modul Ini
            </button>
            @if (auth()->user()?->canWrite())
                <button
                    x-ripple
                    @click="$dispatch('open-security-program-importer')"
                    class="glass-panel inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-sm font-medium text-gray-700 transition-all duration-300 ease-spring hover:scale-105 hover:bg-white/80 active:scale-95"
                >
                    <x-heroicon-o-arrow-up-tray class="h-4 w-4" />
                    Impor Excel
                </button>
                <button
                    x-ripple
                    data-tour="add-button"
                    @click="$dispatch('open-security-program-form')"
                    class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-emerald-500 to-emerald-600 px-3 py-2 text-sm font-medium text-white shadow-lg shadow-emerald-500/30 transition-all duration-300 ease-spring hover:scale-105 hover:shadow-emerald-500/50 active:scale-95"
                >
                    <x-heroicon-o-plus class="h-4 w-4" />
                    Program Baru
                </button>
            @endif
        </div>
    </div>

    @if (auth()->user()?->admin_module === 'keamanan')
        <livewire:admin-verification-queue />
    @endif

    <livewire:security-programs.security-program-stats />
    <livewire:security-programs.security-program-table />
    <livewire:security-programs.security-program-form-modal />
    <livewire:security-programs.security-program-importer />

    @if (request()->boolean('create'))
        <div x-data x-init="$dispatch('open-security-program-form')"></div>
    @endif
@endsection
