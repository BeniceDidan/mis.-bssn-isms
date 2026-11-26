<div class="space-y-6">
    <div
        x-data="{ show: false }"
        x-init="show = ! localStorage.getItem('kominfo_isms_guide_seen')"
        x-show="show"
        x-cloak
        class="glass-panel flex flex-col gap-3 rounded-2xl px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <p class="text-sm font-semibold text-gray-900">Baru pertama kali di sini?</p>
            <p class="text-sm text-gray-600">Panduan menjelaskan urutan kerja dan ke mana data tiap modul dikirim.</p>
        </div>
        <div class="flex shrink-0 items-center gap-2 self-end sm:self-center">
            <a href="{{ route('guide.index') }}" class="rounded-full bg-brand-700 px-4 py-1.5 text-sm font-medium text-white hover:bg-brand-800">Buka Panduan</a>
            <button
                type="button"
                title="Tutup"
                @click="localStorage.setItem('kominfo_isms_guide_seen', '1'); show = false"
                class="rounded-full p-1.5 text-gray-400 hover:bg-slate-50 hover:text-gray-600"
            >
                <x-heroicon-o-x-mark class="h-4 w-4" />
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4" data-tour="tiles-grid">
        @foreach ($modules as $index => $module)
            @if ($module['enabled'])
                <a
                    href="{{ route($module['route']) }}"
                    @if ($index === 0) data-tour="tile-sdm" @endif
                    class="glass-panel flex flex-col rounded-2xl p-5 transition-colors hover:bg-slate-50"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-muda-100 text-muda-600">
                        <x-dynamic-component :component="'heroicon-' . $module['icon']" class="h-5 w-5" />
                    </div>
                    <h2 class="mt-4 text-sm font-semibold text-gray-900">{{ $module['label'] }}</h2>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-semibold text-gray-900">{{ number_format($module['count']) }}</span>
                        <span class="text-sm text-gray-500">{{ $module['description'] }}</span>
                    </div>
                </a>
            @else
                <div class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 opacity-60">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-200 text-gray-500">
                        <x-dynamic-component :component="'heroicon-' . $module['icon']" class="h-5 w-5" />
                    </div>
                    <h2 class="mt-4 text-sm font-semibold text-gray-700">{{ $module['label'] }}</h2>
                    <p class="mt-1 text-sm text-gray-400">Belum tersedia</p>
                </div>
            @endif
        @endforeach
    </div>
</div>
