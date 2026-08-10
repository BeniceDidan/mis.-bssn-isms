@extends('layouts.app')

@section('title', 'Manajemen Pengetahuan')

@section('content')
    <div
        x-data="{
            tab: new URLSearchParams(window.location.search).get('tab') || 'aset',
            createEvent() {
                return {
                    aset: 'open-knowledge-form',
                    keahlian: 'open-knowledge-expert-form',
                    aktivitas: 'open-knowledge-activity-form',
                    risiko: 'open-knowledge-risk-form',
                }[this.tab];
            },
        }"
        class="space-y-4"
    >
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Manajemen Pengetahuan</h1>
                <p class="text-sm text-gray-500">Register aset pengetahuan, peta keahlian, aktivitas berbagi, dan risiko KM</p>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    x-ripple
                    onclick="window.startModuleTour && window.startModuleTour('pengetahuan')"
                    title="Tutorial Modul Ini"
                    class="glass-panel inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-xs font-medium text-amber-700 transition-all duration-300 ease-spring hover:scale-105 hover:bg-amber-50 active:scale-95"
                >
                    <x-heroicon-o-play class="h-3.5 w-3.5" />
                    Tutorial Modul Ini
                </button>
                <x-integration-map module="pengetahuan" />
                @if (auth()->user()?->canWrite())
                    <button
                        x-ripple
                        @click="$dispatch('open-knowledge-importer')"
                        class="glass-panel inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-sm font-medium text-gray-700 transition-all duration-300 ease-spring hover:scale-105 hover:bg-white/80 active:scale-95"
                    >
                        <x-heroicon-o-arrow-up-tray class="h-4 w-4" />
                        Impor Excel
                    </button>
                    <button
                        x-ripple
                        data-tour="add-button"
                        @click="$dispatch(createEvent())"
                        class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-amber-500 to-amber-600 px-3 py-2 text-sm font-medium text-white shadow-lg shadow-amber-500/30 transition-all duration-300 ease-spring hover:scale-105 hover:shadow-amber-500/50 active:scale-95"
                    >
                        <x-heroicon-o-plus class="h-4 w-4" />
                        Tambah Baru
                    </button>
                @endif
            </div>
        </div>

        {{-- The BSSN design guide frames these 4 sheets as "komponen utama
             Manajemen Pengetahuan" (one module, four parts) rather than
             separate modules — tabs keep that framing instead of bloating
             the sidebar to 12 entries. --}}
        <div class="glass-panel flex flex-wrap gap-1 rounded-2xl p-1.5 shadow-lg">
            @foreach ([
                ['key' => 'aset', 'label' => 'Aset Pengetahuan', 'icon' => 'o-light-bulb'],
                ['key' => 'keahlian', 'label' => 'Peta Keahlian', 'icon' => 'o-user-group'],
                ['key' => 'aktivitas', 'label' => 'Aktivitas Berbagi', 'icon' => 'o-microphone'],
                ['key' => 'risiko', 'label' => 'Risiko KM', 'icon' => 'o-exclamation-triangle'],
            ] as $t)
                <button
                    x-ripple
                    @click="tab = '{{ $t['key'] }}'"
                    :class="tab === '{{ $t['key'] }}' ? 'bg-gradient-to-r from-amber-500 to-amber-600 text-white shadow-md' : 'text-gray-600 hover:bg-white/60'"
                    class="flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-300 ease-spring"
                >
                    <x-dynamic-component :component="'heroicon-' . $t['icon']" class="h-4 w-4" />
                    {{ $t['label'] }}
                </button>
            @endforeach
        </div>

        @if (auth()->user()?->admin_module === 'pengetahuan')
            <livewire:admin-verification-queue />
        @endif

        <div x-show="tab === 'aset'" x-cloak class="space-y-4">
            <livewire:knowledge.knowledge-stats />
            <livewire:knowledge.knowledge-table />
        </div>

        <div x-show="tab === 'keahlian'" x-cloak>
            <livewire:knowledge.knowledge-expert-table />
        </div>

        <div x-show="tab === 'aktivitas'" x-cloak>
            <livewire:knowledge.knowledge-activity-table />
        </div>

        <div x-show="tab === 'risiko'" x-cloak>
            <livewire:knowledge.knowledge-risk-table />
        </div>

        <livewire:knowledge.knowledge-form-modal />
        <livewire:knowledge.knowledge-expert-form-modal />
        <livewire:knowledge.knowledge-activity-form-modal />
        <livewire:knowledge.knowledge-risk-form-modal />
        <livewire:knowledge.knowledge-importer />

        @if (request()->boolean('create'))
            @php $presetAssetId = request()->integer('asset_id') ?: 'null'; @endphp
            <div x-data x-init="$dispatch('open-knowledge-form', { assetId: {{ $presetAssetId }} })"></div>
        @endif
    </div>
@endsection
