<div x-data="{ show: @entangle('show') }">
    <div
        x-show="show"
        x-transition:enter="transition ease-out duration-250"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm"
        wire:click.self="close"
    >
        <div
            x-show="show"
            x-transition:enter="transition ease-spring duration-400"
            x-transition:enter-start="opacity-0 scale-75 translate-y-6"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-90 translate-y-4"
            class="glass-panel max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl shadow-2xl"
        >
            <div class="flex items-center justify-between border-b border-white/40 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Impor dari Excel</h2>
                <button type="button" wire:click="close" class="rounded-full p-1.5 text-gray-400 transition-all duration-300 ease-spring hover:rotate-90 hover:scale-110 hover:bg-white/60 hover:text-gray-600">
                    <x-heroicon-o-x-mark class="h-5 w-5" />
                </button>
            </div>

            <div class="space-y-4 px-6 py-5">
                <p class="text-sm text-gray-600">
                    Unggah "Daftar Inventaris Aset.xlsx" sesuai template resmi (sheet Data &amp; Informasi,
                    Perangkat Lunak, Perangkat Keras, Sarana Pendukung, SDM &amp; Pihak Ketiga). Baris dengan
                    <span class="font-mono">Kode Aset</span> yang sudah ada akan <strong>diperbarui</strong>,
                    bukan diduplikasi.
                </p>

                <div>
                    <input
                        type="file"
                        wire:model="file"
                        accept=".xlsx,.xls"
                        class="block w-full text-sm text-gray-600 transition-colors duration-150 file:mr-3 file:rounded-full file:border-0 file:bg-sky-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-sky-700 file:transition-all file:duration-300 file:ease-spring hover:file:scale-105 hover:file:bg-sky-200"
                    >
                    @error('file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                    <div wire:loading wire:target="file" class="mt-2 text-xs text-gray-400">Mengunggah...</div>
                </div>

                @if ($result)
                    <div
                        x-data="{ shown: false }"
                        x-init="setTimeout(() => shown = true, 10)"
                        x-show="shown"
                        x-transition:enter="transition ease-spring duration-400"
                        x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        class="rounded-xl border border-white/50 bg-white/50 p-4"
                    >
                        <div class="flex gap-4 text-sm">
                            <span class="font-medium text-emerald-700">{{ $result['created'] }} dibuat</span>
                            <span class="font-medium text-sky-700">{{ $result['updated'] }} diperbarui</span>
                            <span class="font-medium text-red-700">{{ count($result['errors']) }} error</span>
                        </div>

                        @if (! empty($result['errors']))
                            <ul class="mt-3 max-h-40 space-y-1 overflow-y-auto text-xs text-gray-600">
                                @foreach ($result['errors'] as $error)
                                    <li>[{{ $error['sheet'] }} baris {{ $error['row'] }}] {{ $error['message'] }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-white/40 px-6 py-4">
                <button type="button" wire:click="close" x-ripple class="rounded-full border border-white/60 bg-white/40 px-4 py-2 text-sm font-medium text-gray-700 transition-all duration-300 ease-spring hover:scale-105 hover:bg-white/70 active:scale-95">
                    Tutup
                </button>
                <button
                    wire:click="import"
                    wire:loading.attr="disabled"
                    wire:target="import"
                    x-ripple
                    class="rounded-full bg-gradient-to-r from-sky-500 to-sky-600 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-sky-500/30 transition-all duration-300 ease-spring hover:scale-105 hover:shadow-sky-500/50 active:scale-95 disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="import">Impor</span>
                    <span wire:loading wire:target="import">Memproses...</span>
                </button>
            </div>
        </div>
    </div>
</div>
