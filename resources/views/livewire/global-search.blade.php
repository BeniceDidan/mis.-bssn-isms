@php
    // Literal classes (not interpolated) so Tailwind's JIT scanner picks
    // them up — same convention as the module color maps elsewhere.
    $badgeClasses = [
        'teal' => 'bg-teal-100 text-teal-700 dark:bg-teal-500/20 dark:text-teal-300',
        'purple' => 'bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300',
        'indigo' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300',
        'sky' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-300',
        'slate' => 'bg-slate-100 text-slate-700 dark:bg-slate-500/20 dark:text-slate-300',
        'rose' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300',
        'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
        'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
    ];
    $groups = strlen(trim($query)) >= 2 ? $this->groups() : [];
@endphp

<div
    class="relative w-full"
    x-data="{ open: false }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
>
    <div class="relative">
        <x-heroicon-o-magnifying-glass
            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 transition-all duration-300 ease-spring"
            x-bind:class="open ? 'text-sky-500 scale-125' : ''"
        />
        <input
            id="global-search"
            type="search"
            wire:model.live.debounce.300ms="query"
            placeholder="Cari di seluruh modul..."
            autocomplete="off"
            class="w-full rounded-full border-white/60 bg-white/50 pl-9 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-sky-400 focus:bg-white/90 focus:ring-sky-400"
            @focus="open = true"
        >
    </div>

    <div
        x-show="open && $wire.query.trim().length >= 2"
        x-cloak
        x-transition:enter="transition ease-spring duration-250"
        x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="glass-panel absolute left-0 right-0 top-full z-50 mt-2 max-h-[70vh] overflow-y-auto rounded-2xl p-2 shadow-2xl"
    >
        {{-- Skeleton while the debounced request is in flight. --}}
        <div wire:loading wire:target="query" class="space-y-3 p-3">
            @for ($i = 0; $i < 3; $i++)
                <div class="animate-pulse space-y-2">
                    <div class="h-2.5 w-24 rounded-full bg-gray-200 dark:bg-white/10"></div>
                    <div class="h-3.5 w-3/4 rounded-full bg-gray-200 dark:bg-white/10"></div>
                    <div class="h-3.5 w-1/2 rounded-full bg-gray-200 dark:bg-white/10"></div>
                </div>
            @endfor
        </div>

        <div wire:loading.remove wire:target="query">
            @forelse ($groups as $group)
                <div class="mb-1 last:mb-0">
                    <p class="flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                        <x-dynamic-component :component="'heroicon-' . $group['icon']" class="h-3.5 w-3.5" />
                        {{ $group['label'] }}
                    </p>
                    @foreach ($group['items'] as $item)
                        <a
                            href="{{ $item['url'] }}"
                            class="flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm text-gray-700 transition-all duration-200 hover:translate-x-1 hover:bg-sky-50 hover:text-sky-700 dark:text-gray-200 dark:hover:bg-white/10"
                        >
                            <span class="truncate">{{ $item['title'] }}</span>
                            @if ($item['meta'])
                                <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium {{ $badgeClasses[$group['color']] ?? $badgeClasses['slate'] }}">
                                    {{ $item['meta'] }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @empty
                <p class="px-3 py-6 text-center text-sm text-gray-500">
                    Tidak ada hasil untuk "<span class="font-medium text-gray-700 dark:text-gray-300">{{ trim($query) }}</span>"
                </p>
            @endforelse
        </div>
    </div>
</div>
