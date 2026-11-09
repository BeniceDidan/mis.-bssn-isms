<div class="space-y-4">
    <form wire:submit="ask" class="glass-panel rounded-2xl p-4 shadow-lg">
        <div class="flex flex-col gap-2 md:flex-row">
            <div class="relative flex-1">
                <x-heroicon-o-chat-bubble-left-right class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                <input
                    type="text"
                    wire:model="question"
                    placeholder="Contoh: Gimana prosedur permohonan server?"
                    class="w-full rounded-full border-white/60 bg-white/60 pl-9 text-sm focus:border-amber-400 focus:bg-white/90 focus:ring-amber-400"
                >
            </div>
            <button type="submit" wire:loading.attr="disabled" wire:target="ask" class="inline-flex items-center justify-center gap-1.5 rounded-full bg-gradient-to-r from-amber-500 to-amber-600 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-amber-500/30 transition-all duration-300 ease-spring hover:scale-105 disabled:opacity-60">
                <span wire:loading.remove wire:target="ask">Tanya</span>
                <span wire:loading wire:target="ask">Mencari...</span>
            </button>
        </div>
        @error('question') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
        <p class="mt-2 text-xs text-gray-400">
            {{ $indexedCount }} dokumen bisa ditanya
            @if ($pendingCount) &middot; {{ $pendingCount }} masih diproses @endif
        </p>
    </form>

    @if ($result)
        <div class="glass-panel rounded-2xl p-5 shadow-lg">
            <p class="mb-2 text-xs text-gray-500">{{ $askedQuestion }}</p>
            <div class="whitespace-pre-line text-sm leading-relaxed text-gray-800">{{ $result['answer'] }}</div>
            <p class="mt-3 text-xs text-gray-400">Dijawab oleh {{ $result['engine'] }}. Periksa kembali di dokumen sumber.</p>
        </div>

        @if ($result['sources'])
            <div class="glass-panel overflow-hidden rounded-2xl shadow-lg">
                <p class="border-b border-white/40 px-5 py-3 text-sm font-semibold text-gray-800">Sumber</p>
                <ul class="divide-y divide-white/40">
                    @foreach ($result['sources'] as $source)
                        <li class="px-5 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-sm font-medium text-gray-900">
                                    <span class="text-gray-400">[{{ $source['n'] }}]</span>
                                    {{ $source['title'] }}
                                    @if ($source['page']) <span class="font-normal text-gray-500">· hal. {{ $source['page'] }}</span> @endif
                                </p>
                                @if ($source['url'])
                                    <a href="{{ $source['url'] }}" target="_blank" class="inline-flex shrink-0 items-center gap-1 text-xs font-medium text-amber-700 hover:underline">
                                        <x-heroicon-o-document-text class="h-3.5 w-3.5" /> Buka PDF
                                    </a>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-gray-500">{{ $source['snippet'] }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif
</div>
