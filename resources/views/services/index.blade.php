@extends('layouts.app')

@section('title', 'Manajemen Layanan')

@section('content')
    <div x-data="{ tab: new URLSearchParams(window.location.search).get('tab') || 'katalog' }" class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Manajemen Layanan</h1>
                <p class="text-sm text-gray-500">Katalog layanan SPBE, tiket helpdesk, dan evaluasi kinerja</p>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    x-ripple
                    onclick="window.startModuleTour && window.startModuleTour('layanan')"
                    title="Tutorial Modul Ini"
                    class="glass-panel inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-xs font-medium text-sky-700 transition-all duration-300 ease-spring hover:scale-105 hover:bg-sky-50 active:scale-95"
                >
                    <x-heroicon-o-play class="h-3.5 w-3.5" />
                    Tutorial Modul Ini
                </button>
                <x-integration-map module="layanan" />
                @if (auth()->user()?->canWrite())
                    <button
                        x-show="tab === 'katalog'"
                        x-ripple
                        @click="$dispatch('open-service-importer')"
                        class="glass-panel inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-sm font-medium text-gray-700 transition-all duration-300 ease-spring hover:scale-105 hover:bg-white/80 active:scale-95"
                    >
                        <x-heroicon-o-arrow-up-tray class="h-4 w-4" />
                        Impor Excel
                    </button>
                    <button
                        x-ripple
                        data-tour="add-button"
                        @click="tab === 'helpdesk' ? $dispatch('open-service-ticket-form') : $dispatch('open-service-form')"
                        class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-sky-500 to-sky-600 px-3 py-2 text-sm font-medium text-white shadow-lg shadow-sky-500/30 transition-all duration-300 ease-spring hover:scale-105 hover:shadow-sky-500/50 active:scale-95"
                    >
                        <x-heroicon-o-plus class="h-4 w-4" />
                        <span x-text="tab === 'helpdesk' ? 'Tiket Baru' : 'Layanan Baru'">Layanan Baru</span>
                    </button>
                @endif
            </div>
        </div>

        <div class="glass-panel flex flex-wrap gap-1 rounded-2xl p-1.5 shadow-lg">
            @foreach ([
                ['key' => 'katalog', 'label' => 'Katalog Layanan', 'icon' => 'o-wrench-screwdriver'],
                ['key' => 'helpdesk', 'label' => 'Tiket Helpdesk', 'icon' => 'o-inbox-stack'],
            ] as $t)
                <button
                    x-ripple
                    @click="tab = '{{ $t['key'] }}'"
                    :class="tab === '{{ $t['key'] }}' ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-md' : 'text-gray-600 hover:bg-white/60'"
                    class="flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-300 ease-spring"
                >
                    <x-dynamic-component :component="'heroicon-' . $t['icon']" class="h-4 w-4" />
                    {{ $t['label'] }}
                </button>
            @endforeach
        </div>

        @if (auth()->user()?->admin_module === 'layanan')
            <livewire:admin-verification-queue />
        @endif

        <div x-show="tab === 'katalog'" x-cloak class="space-y-6">
            <livewire:services.service-stats />
            <livewire:services.service-table />
        </div>

        <div
            x-show="tab === 'helpdesk'"
            x-cloak
            x-data="{ toast: null }"
            x-on:toast.window="toast = $event.detail.message; setTimeout(() => toast = null, 3000)"
        >
            <livewire:services.helpdesk-queue />
            <div x-show="toast" x-transition x-cloak class="fixed bottom-6 right-6 z-[60] rounded-full bg-slate-900/90 px-4 py-2 text-sm text-white shadow-xl" x-text="toast"></div>
        </div>

        <livewire:services.service-form-modal />
        <livewire:services.service-ticket-form-modal />
        <livewire:services.service-evaluation-form-modal />
        <livewire:services.service-importer />

        @if (request()->boolean('create'))
            <div x-data x-init="$dispatch('open-service-form')"></div>
        @endif
    </div>
@endsection
