@php
    // Full literal class strings (not runtime-interpolated) so Tailwind's
    // scanner picks them all up — dynamic `bg-{$color}-500` strings would
    // silently fail to compile.
    $colorClasses = [
        'teal' => ['grad' => ' ', 'shadow' => '', 'ring' => ''],
        'purple' => ['grad' => ' ', 'shadow' => '', 'ring' => ''],
        'sky' => ['grad' => ' ', 'shadow' => '', 'ring' => ''],
        'emerald' => ['grad' => ' ', 'shadow' => '', 'ring' => ''],
        'amber' => ['grad' => ' ', 'shadow' => '', 'ring' => ''],
        'indigo' => ['grad' => ' ', 'shadow' => '', 'ring' => ''],
        'rose' => ['grad' => ' ', 'shadow' => '', 'ring' => ''],
        'slate' => ['grad' => ' ', 'shadow' => '', 'ring' => ''],
    ];
@endphp

<div class="space-y-6">
    <div
        x-data="{ show: false }"
        x-init="show = ! localStorage.getItem('kominfo_isms_guide_seen')"
        x-show="show"
        x-transition:enter="transition ease-spring duration-500"
        x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
        x-cloak
        class="glass-panel flex flex-col gap-3 rounded-2xl border border-brand-200 bg-brand-50 p-4 shadow-lg sm:flex-row sm:items-center sm:justify-between"
    >
        <div class="flex items-start gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-700 text-white shadow-md">
                <x-heroicon-o-sparkles class="h-5 w-5" />
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-900">Baru pertama kali di sini?</p>
                <p class="text-xs text-gray-600">
                    Urutan ubin di bawah mengikuti alur 8 manajemennya, dan panel "Riwayat Terkait" di detail
                    Aset adalah tempat semuanya bertemu. Baca panduan singkatnya dulu biar tidak bingung.
                </p>
            </div>
        </div>
        <div class="flex shrink-0 items-center gap-2 self-end sm:self-center">
            <a
                href="{{ route('guide.index') }}"
                x-ripple
                class="rounded-full bg-brand-700 px-3 py-1.5 text-xs font-medium text-white shadow-md transition-all duration-300 ease-spring hover:scale-105 active:scale-95"
            >
                Lihat Panduan
            </a>
            <button
                x-ripple
                @click="localStorage.setItem('kominfo_isms_guide_seen', '1'); show = false"
                class="rounded-full p-1.5 text-gray-400 transition-all duration-300 ease-spring hover:rotate-90 hover:bg-slate-50 hover:text-gray-600"
            >
                <x-heroicon-o-x-mark class="h-4 w-4" />
            </button>
        </div>
    </div>

    <div class="glass-panel flex flex-col gap-1 rounded-2xl px-5 py-3 shadow-lg sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-gray-600">
            <span class="font-semibold text-gray-900">{{ $activeCount }}</span> dari
            <span class="font-semibold text-gray-900">{{ count($modules) }}</span> modul aktif
        </p>
        <p class="flex items-center gap-1.5 text-xs text-gray-500">
            <x-heroicon-o-arrow-path-rounded-square class="h-3.5 w-3.5 text-brand-500" />
            Urutan ubin mengikuti alur korelasi 8 manajemen — benang merah antar modul
        </p>
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4" data-tour="tiles-grid">
        @foreach ($modules as $index => $module)
            @php $colors = $colorClasses[$module['color']]; @endphp

            @if ($module['enabled'])
                <a
                    href="{{ route($module['route']) }}"
                    @if ($index === 0) data-tour="tile-sdm" @endif
                    x-data="{ shown: false }" x-init="setTimeout(() => shown = true, {{ $index * 60 }})"
                    x-show="shown"
                    x-transition:enter="transition ease-spring duration-500"
                    x-transition:enter-start="opacity-0 translate-y-6 scale-90"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    class="group glass-panel relative overflow-hidden rounded-2xl p-5 shadow-lg transition-all duration-300 ease-spring hover:-translate-y-2 hover:scale-[1.03] hover:shadow-2xl {{ $colors['ring'] }}"
                >
                    <div class="flex items-start justify-between">
                        <div class="relative flex h-12 w-12 items-center justify-center rounded-2xl bg-muda-100 text-muda-600 transition-transform duration-300 ease-spring group-hover:scale-110">
                            <x-dynamic-component :component="'heroicon-' . $module['icon']" class="relative h-6 w-6" />
                        </div>
                        <span class="rounded-full bg-brand-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-brand-700">Aktif</span>
                    </div>

                    <h2 class="mt-4 text-sm font-semibold text-gray-900">{{ $module['label'] }}</h2>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $module['description'] }}</p>

                    <div class="mt-3 flex items-center justify-between">
                        <span class="text-2xl font-bold text-gray-900">{{ number_format($module['count']) }}</span>
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-brand-600 transition-transform duration-300 ease-spring group-hover:translate-x-1">
                            Buka <x-heroicon-o-arrow-right class="h-3 w-3" />
                        </span>
                    </div>
                </a>
            @else
                <div
                    x-data="{ shown: false }" x-init="setTimeout(() => shown = true, {{ $index * 60 }})"
                    x-show="shown"
                    x-transition:enter="transition ease-spring duration-500"
                    x-transition:enter-start="opacity-0 translate-y-6 scale-90"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    class="relative flex cursor-not-allowed flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 opacity-60 shadow-sm "
                >
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-300 text-white">
                        <x-dynamic-component :component="'heroicon-' . $module['icon']" class="h-6 w-6" />
                    </div>
                    <span class="mt-4 w-fit rounded-full bg-gray-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-500">Segera Hadir</span>

                    <h2 class="mt-3 text-sm font-semibold text-gray-700">{{ $module['label'] }}</h2>
                    <p class="mt-0.5 text-xs text-gray-400">{{ $module['description'] }}</p>
                </div>
            @endif
        @endforeach
    </div>
</div>
