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

{{-- Mounted once at the layout root (see layouts/app.blade.php), not
     inside the topbar — the topbar's own .glass-panel backdrop-filter
     would otherwise become the containing block for this `fixed` overlay
     and trap it inside the header's own tiny box instead of the real
     viewport. Driven by $store.search.open (resources/js/app.js) rather
     than local x-data, since the trigger button lives in the topbar,
     elsewhere in the DOM — see the store's own comment for why this
     replaced an earlier x-teleport-based version. --}}
<div
    x-data
    x-show="$store.search.open"
    x-cloak
    class="fixed inset-0 z-[70] flex justify-center overflow-y-auto bg-slate-900/40 px-4 pb-8 pt-[12vh] backdrop-blur-md sm:pt-[16vh]"
    @click.self="$store.search.open = false"
    @keydown.escape.window="$store.search.open = false"
>
    <div
        x-show="$store.search.open"
        x-transition:enter="transition ease-spring duration-300"
        x-transition:enter-start="opacity-0 -translate-y-6 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-3 scale-95"
        class="glass-panel relative z-10 h-fit w-full max-w-xl overflow-hidden rounded-2xl shadow-2xl"
    >
        <div class="relative border-b border-white/40 dark:border-white/10">
            <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
            <input
                id="global-search"
                type="search"
                aria-label="Cari di seluruh modul"
                wire:model.live.debounce.300ms="query"
                placeholder="Cari di seluruh modul..."
                autocomplete="off"
                x-effect="if ($store.search.open) $nextTick(() => $el.focus())"
                @keydown.escape.prevent="$store.search.open = false"
                class="w-full border-0 bg-transparent py-4 pl-12 pr-4 text-base text-gray-900 placeholder:text-gray-400 focus:ring-0 dark:text-gray-100"
            >
            <button
                type="button"
                title="Tutup"
                @click="$store.search.open = false"
                class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full p-1.5 text-gray-400 transition-all duration-300 ease-spring hover:scale-110 hover:bg-white/60 hover:text-gray-600 dark:hover:bg-white/10"
            >
                <x-heroicon-o-x-mark class="h-4 w-4" />
            </button>
        </div>

        <div class="max-h-[60vh] overflow-y-auto p-2">
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
                        @if (strlen(trim($query)) >= 2)
                            Tidak ada hasil untuk "<span class="font-medium text-gray-700 dark:text-gray-300">{{ trim($query) }}</span>"
                        @else
                            Ketik minimal 2 huruf untuk mulai mencari
                        @endif
                    </p>
                @endforelse
            </div>
        </div>
    </div>
</div>
