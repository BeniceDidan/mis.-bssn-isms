@extends('layouts.app')

@section('title', 'Manajemen Layanan')

@section('content')
    <div x-data="{ tab: new URLSearchParams(window.location.search).get('tab') || 'katalog' }" class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Manajemen Layanan</h1>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    x-ripple
                    onclick="window.startModuleTour && window.startModuleTour('layanan')"
                    title="Tutorial modul ini"
                    class="glass-panel inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-xs font-medium text-brand-700 hover:bg-brand-50"
                >
                    <x-heroicon-o-play class="h-3.5 w-3.5" />
                    Tutorial
                </button>
                <x-integration-map module="layanan" />
                <div x-show="tab === 'helpdesk'" x-cloak><x-ai-guide feature="triase" /></div>
                @if (auth()->user()?->canWrite())
                    <button
                        x-show="tab === 'katalog'"
                        x-ripple
                        @click="$dispatch('open-service-importer')"
                        class="glass-panel inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-sm font-medium text-gray-700 hover:bg-slate-50"
                    >
                        <x-heroicon-o-arrow-up-tray class="h-4 w-4" />
                        Impor Excel
                    </button>
                    <button
                        x-ripple
                        data-tour="add-button"
                        @click="tab === 'helpdesk' ? $dispatch('open-service-ticket-form') : $dispatch('open-service-form')"
                        class="inline-flex items-center gap-1.5 rounded-full bg-brand-700 px-3 py-2 text-sm font-medium text-white shadow-lg"
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
                    data-tour="tab-{{ $t['key'] }}"
                    @click="tab = '{{ $t['key'] }}'"
                    :class="tab === '{{ $t['key'] }}' ? 'bg-brand-700 text-white shadow-md' : 'text-gray-600 hover:bg-slate-50'"
                    class="flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-300 ease-spring"
                >
                    <x-dynamic-component :component="'heroicon-' . $t['icon']" class="h-4 w-4" />
                    {{ $t['label'] }}
                </button>
            @endforeach
        </div>

        <x-module-flow module="layanan" />

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
            data-tour="helpdesk-panel"
            x-data="{ toast: null }"
            x-on:toast.window="toast = $event.detail.message; setTimeout(() => toast = null, Math.min(9000, Math.max(3000, $event.detail.message.length * 55)))"
        >
            <livewire:services.helpdesk-queue />
            <div x-show="toast" x-transition x-cloak class="fixed bottom-6 right-6 z-[60] max-w-sm rounded-2xl bg-slate-900 px-4 py-2 text-sm leading-snug text-white shadow-xl" x-text="toast"></div>
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
