@extends('layouts.app')

@section('title', 'Manajemen SDM')

@section('content')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Manajemen SDM</h1>
            <p class="text-sm text-gray-500">Register risiko sumber daya manusia dan pihak ketiga</p>
        </div>
        @if (auth()->user()?->canWrite())
            <div class="flex items-center gap-2" x-data>
                <button
                    x-ripple
                    @click="$dispatch('open-hr-risk-importer')"
                    class="glass-panel inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-sm font-medium text-gray-700 transition-all duration-300 ease-spring hover:scale-105 hover:bg-white/80 active:scale-95"
                >
                    <x-heroicon-o-arrow-up-tray class="h-4 w-4" />
                    Impor Excel
                </button>
                <button
                    x-ripple
                    @click="$dispatch('open-hr-risk-form')"
                    class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-sky-500 to-sky-600 px-3 py-2 text-sm font-medium text-white shadow-lg shadow-sky-500/30 transition-all duration-300 ease-spring hover:scale-105 hover:shadow-sky-500/50 active:scale-95"
                >
                    <x-heroicon-o-plus class="h-4 w-4" />
                    Risiko Baru
                </button>
            </div>
        @endif
    </div>

    @if (auth()->user()?->admin_module === 'sdm')
        <livewire:admin-verification-queue />
    @endif

    <livewire:hr-risks.hr-risk-stats />
    <livewire:hr-risks.hr-risk-table />
    <livewire:hr-risks.hr-risk-form-modal />
    <livewire:hr-risks.hr-risk-importer />

    @if (request()->boolean('create'))
        <div x-data x-init="$dispatch('open-hr-risk-form')"></div>
    @endif
@endsection
