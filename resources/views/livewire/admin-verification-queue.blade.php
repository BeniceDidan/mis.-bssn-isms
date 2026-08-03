<div
    class="space-y-4"
    x-data="{ toast: null, open: {{ $pending->isNotEmpty() ? 'true' : 'false' }} }"
    x-on:toast.window="toast = $event.detail.message; setTimeout(() => toast = null, 3000)"
>
    <button
        type="button"
        x-ripple
        @click="open = !open"
        class="glass-panel flex w-full items-center justify-between rounded-2xl px-4 py-3 text-left shadow-lg transition-all duration-300 ease-spring hover:bg-white/80"
    >
        <span class="flex items-center gap-2 text-sm font-semibold text-gray-900">
            <x-heroicon-o-clipboard-document-check class="h-5 w-5 text-amber-600" />
            Verifikasi — {{ auth()->user()?->adminModuleLabel() }}
            @if ($pending->isNotEmpty())
                <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white">{{ $pending->count() }}</span>
            @endif
        </span>
        <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-400 transition-transform duration-300 ease-spring" x-bind:class="open ? 'rotate-180' : ''" />
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-250"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="glass-panel overflow-hidden rounded-2xl shadow-lg"
    >
        <div class="max-h-[70vh] overflow-auto">
            <table class="min-w-full divide-y divide-white/40 text-sm">
                <thead class="sticky top-0 z-10 bg-white/70 backdrop-blur-xl">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Modul</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Kode</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Judul / Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Diajukan</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/40">
                    @forelse ($pending as $item)
                        <tr wire:key="pending-{{ $item['moduleKey'] }}-{{ $item['id'] }}" class="transition-all duration-200 hover:bg-white/60">
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="rounded-full bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-700">{{ $item['moduleLabel'] }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-700">{{ $item['code'] }}</td>
                            <td class="max-w-xs truncate px-4 py-3 font-semibold text-gray-900">{{ $item['title'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500">{{ $item['updatedAt']?->diffForHumans() }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                @if ($decidingModuleKey === $item['moduleKey'] && $decidingId === $item['id'])
                                    <div class="flex items-center justify-end gap-2">
                                        <input
                                            type="text"
                                            wire:model="notes"
                                            placeholder="{{ $decision === 'ditolak' ? 'Catatan koreksi (wajib)...' : 'Catatan (opsional)...' }}"
                                            class="w-56 rounded-full border-gray-300 text-xs focus:border-sky-500 focus:ring-sky-500"
                                        >
                                        <button wire:click="confirmDecision" x-ripple class="rounded-full bg-gray-900 px-3 py-1.5 text-xs font-medium text-white transition-all duration-300 ease-spring hover:scale-105 active:scale-95">
                                            Konfirmasi
                                        </button>
                                        <button wire:click="cancelDecision" class="rounded-full p-1.5 text-gray-400 transition-all duration-300 ease-spring hover:bg-gray-100 hover:text-gray-600">
                                            <x-heroicon-o-x-mark class="h-4 w-4" />
                                        </button>
                                    </div>
                                    @error('notes') <p class="mt-1 text-right text-xs text-red-600">{{ $message }}</p> @enderror
                                @else
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            wire:click="openDecision('{{ $item['moduleKey'] }}', {{ $item['id'] }}, 'tervalidasi')"
                                            x-ripple
                                            title="Setujui"
                                            class="rounded-full p-1.5 text-gray-500 transition-all duration-300 ease-spring hover:scale-125 hover:bg-emerald-100 hover:text-emerald-600 active:scale-90"
                                        >
                                            <x-heroicon-o-check-circle class="h-5 w-5" />
                                        </button>
                                        <button
                                            wire:click="openDecision('{{ $item['moduleKey'] }}', {{ $item['id'] }}, 'ditolak')"
                                            x-ripple
                                            title="Tolak / Minta Koreksi"
                                            class="rounded-full p-1.5 text-gray-500 transition-all duration-300 ease-spring hover:scale-125 hover:bg-red-100 hover:text-red-600 active:scale-90"
                                        >
                                            <x-heroicon-o-x-circle class="h-5 w-5" />
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">
                                Tidak ada data yang menunggu verifikasi di modul ini saat ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div
        x-show="toast"
        x-transition:enter="transition ease-spring duration-400"
        x-transition:enter-start="opacity-0 translate-y-6 scale-90"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-90"
        x-cloak
        class="glass-panel-dark fixed bottom-5 right-5 z-50 rounded-full px-4 py-2.5 text-sm text-white shadow-2xl"
    >
        <span x-text="toast"></span>
    </div>
</div>
