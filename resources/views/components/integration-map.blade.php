@props(['module'])

@php
    $map = \App\Support\IntegrationMap::for($module);
    $groups = $map ? [
        'Dikirim ke modul lain' => collect($map['flows'])->where('dir', 'out')->values(),
        'Datang dari modul lain' => collect($map['flows'])->where('dir', 'in')->values(),
    ] : [];
@endphp

@if ($map)
    <div x-data="{ open: false }" @keydown.escape.window="open = false">
        <button
            type="button"
            x-ripple
            @click="open = true"
            data-tour="integration-map"
            class="glass-panel inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-xs font-medium text-gray-700 hover:bg-slate-50"
        >
            <x-heroicon-o-arrows-right-left class="h-3.5 w-3.5" />
            Alur Data
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-[65] flex items-start justify-center overflow-y-auto bg-slate-900/40 px-4 pb-10 pt-[8vh]"
            @click.self="open = false"
        >
            <div x-show="open" x-transition class="glass-panel h-fit w-full max-w-2xl overflow-hidden rounded-2xl shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-4">
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold text-gray-900">Alur data {{ $map['label'] }}</h2>
                        <p class="mt-1 text-sm text-gray-600">{{ $map['intro'] }}</p>
                    </div>
                    <button type="button" @click="open = false" class="shrink-0 rounded-full p-1.5 text-gray-400 hover:bg-slate-50 hover:text-gray-600">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="max-h-[66vh] space-y-5 overflow-y-auto px-6 py-5">
                    <p class="text-sm text-gray-600">Penghubung: <span class="font-medium text-gray-900">{{ implode(' dan ', $map['keys']) }}</span></p>

                    @foreach ($groups as $heading => $flows)
                        @continue($flows->isEmpty())
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">{{ $heading }}</h3>
                            <ul class="mt-2 divide-y divide-slate-200 rounded-xl bg-slate-50">
                                @foreach ($flows as $flow)
                                    <li class="px-4 py-3">
                                        <div class="flex flex-wrap items-baseline justify-between gap-x-3">
                                            <p class="text-sm font-medium text-gray-900">{{ $flow['to'] }}</p>
                                            <p class="text-xs {{ $flow['type'] === 'auto' ? 'text-brand-700' : 'text-gray-500' }}">{{ $flow['type'] === 'auto' ? 'Otomatis' : 'Bila kolomnya diisi' }}</p>
                                        </div>
                                        <p class="mt-1 text-sm leading-relaxed text-gray-600">{{ $flow['text'] }}</p>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach

                    @if (! empty($map['warning']))
                        <p class="rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-900">{{ $map['warning'] }}</p>
                    @endif
                </div>

                <div class="flex items-center justify-between gap-3 border-t border-slate-200 px-6 py-3">
                    <a href="{{ route('guide.index') }}#alur" class="inline-flex items-center gap-1 text-sm font-medium text-brand-700 hover:underline">
                        Alur semua modul
                        <x-heroicon-o-arrow-right class="h-3.5 w-3.5" />
                    </a>
                    <button type="button" @click="open = false" class="rounded-full border border-slate-200 bg-white px-4 py-1.5 text-sm font-medium text-gray-700 hover:bg-slate-50">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
