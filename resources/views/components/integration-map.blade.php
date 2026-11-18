@props(['module'])

@php
    $map = \App\Support\IntegrationMap::for($module);

    // Literal class strings (not interpolated) so Tailwind's JIT scanner picks
    // them up — same convention as the dashboard/guide color maps.
    $accents = [
        'slate' => 'bg-slate-100 text-slate-700 dark:bg-slate-500/20 dark:text-slate-200',
        'amber' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-200',
        'teal' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-200',
        'emerald' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-200',
        'purple' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-200',
        'indigo' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-200',
        'rose' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-200',
        'sky' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-200',
    ];
    $accent = $accents[$map['color'] ?? 'sky'] ?? $accents['sky'];
@endphp

@if ($map)
    <div x-data="{ open: false }" @keydown.escape.window="open = false">
        <button
            type="button"
            x-ripple
            @click="open = true"
            title="Titik Integrasi"
            class="glass-panel inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-xs font-medium text-gray-700 transition-all duration-300 ease-spring hover:scale-105 hover:bg-slate-50 active:scale-95"
        >
            <x-heroicon-o-share class="h-3.5 w-3.5" />
            Titik Integrasi
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[65] flex items-start justify-center overflow-y-auto bg-slate-900/40 px-4 pb-10 pt-[8vh] "
            @click.self="open = false"
        >
            <div
                x-show="open"
                x-transition:enter="transition ease-spring duration-300"
                x-transition:enter-start="opacity-0 -translate-y-5 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 -translate-y-3 scale-95"
                class="glass-panel h-fit w-full max-w-2xl overflow-hidden rounded-2xl shadow-2xl"
            >
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-4 dark:border-white/10">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Titik Integrasi</h2>
                        <p class="mt-0.5 text-xs text-gray-500">{{ $map['label'] }} — nyambung ke mana saja</p>
                    </div>
                    <button
                        type="button"
                        @click="open = false"
                        class="shrink-0 rounded-full p-1.5 text-gray-400 transition-all duration-300 ease-spring hover:rotate-90 hover:scale-110 hover:bg-slate-50 hover:text-gray-600 dark:hover:bg-white/10"
                    >
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="max-h-[68vh] space-y-5 overflow-y-auto px-6 py-5">
                    <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-200">{{ $map['intro'] }}</p>

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Kunci penghubung</span>
                        @foreach ($map['keys'] as $key)
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $accent }}">{{ $key }}</span>
                        @endforeach
                    </div>

                    <div class="space-y-3">
                        @foreach ($map['flows'] as $flow)
                            <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($flow['type'] === 'auto')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-brand-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-brand-700 dark:bg-brand-500/20 dark:text-brand-200">
                                            <x-heroicon-o-bolt class="h-3 w-3" /> Otomatis
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-600 dark:bg-white/10 dark:text-gray-300">
                                            <x-heroicon-o-hand-raised class="h-3 w-3" /> Diisi manual
                                        </span>
                                    @endif
                                    <span class="text-sm font-semibold text-gray-900">{{ $flow['to'] }}</span>
                                </div>
                                <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-300">{{ $flow['text'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    @if (! empty($map['warning']))
                        <div class="flex gap-3 rounded-xl border border-brand-200 bg-brand-50 p-4 dark:border-brand-400/20 dark:bg-brand-500/10">
                            <x-heroicon-o-exclamation-triangle class="h-5 w-5 shrink-0 text-brand-600 dark:text-brand-300" />
                            <p class="text-sm leading-relaxed text-brand-900 dark:text-brand-100">{{ $map['warning'] }}</p>
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-between gap-3 border-t border-slate-200 px-6 py-3 dark:border-white/10">
                    <a
                        href="{{ \Illuminate\Support\Facades\Route::has('guide.index') ? route('guide.index') : '#' }}"
                        class="inline-flex items-center gap-1.5 text-xs font-medium text-brand-600 transition-transform duration-200 hover:translate-x-0.5"
                    >
                        Lihat alur lengkap 8 manajemen
                        <x-heroicon-o-arrow-right class="h-3 w-3" />
                    </a>
                    <button
                        type="button"
                        x-ripple
                        @click="open = false"
                        class="rounded-full border border-slate-200 bg-white px-4 py-1.5 text-xs font-medium text-gray-700 transition-all duration-300 ease-spring hover:scale-105 hover:bg-slate-50 active:scale-95 dark:border-white/10 dark:bg-white/5"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
