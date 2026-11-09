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
            class="glass-panel max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl shadow-2xl"
        >
            <form wire:submit="save">
                <div class="flex items-center justify-between border-b border-white/40 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">Unggah Bukti Dukung</h2>
                    <button type="button" wire:click="close" class="rounded-full p-1.5 text-gray-400 transition-all duration-300 ease-spring hover:rotate-90 hover:scale-110 hover:bg-white/60 hover:text-gray-600">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Modul</label>
                            <select wire:model="module_key" class="w-full rounded-lg border-white/60 bg-white/50 text-sm focus:border-sky-400 focus:ring-sky-400">
                                <option value="">Pilih modul...</option>
                                @foreach ($modules as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('module_key') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Level yang diklaim</label>
                            <select wire:model="target_level" class="w-full rounded-lg border-white/60 bg-white/50 text-sm focus:border-sky-400 focus:ring-sky-400">
                                @foreach ($levels as $no => $level)
                                    <option value="{{ $no }}">Level {{ $no }} — {{ $level['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Judul dokumen <span class="font-normal text-gray-400">(opsional)</span></label>
                        <input type="text" wire:model="title" placeholder="Kosongkan untuk memakai nama berkas" class="w-full rounded-lg border-white/60 bg-white/50 text-sm focus:border-sky-400 focus:ring-sky-400">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">OPD / unit kerja pengirim</label>
                        <input type="text" wire:model="unit_name" class="w-full rounded-lg border-white/60 bg-white/50 text-sm focus:border-sky-400 focus:ring-sky-400">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Berkas PDF</label>
                        <input
                            type="file"
                            wire:model="document"
                            accept="application/pdf"
                            class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-full file:border-0 file:bg-sky-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-sky-700 hover:file:bg-sky-200"
                        >
                        @error('document') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        <div wire:loading wire:target="document" class="mt-1 text-xs text-gray-400">Mengunggah...</div>
                        <p class="mt-1 text-xs text-gray-400">Maksimal 10 MB. PDF hasil pindai tidak bisa dibaca isinya.</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-white/40 px-6 py-4">
                    <span wire:loading wire:target="save" class="mr-auto text-xs text-gray-500">Membaca dan menilai dokumen, mohon tunggu...</span>
                    <button type="button" wire:click="close" class="rounded-full px-4 py-2 text-sm font-medium text-gray-600 hover:bg-white/60">Batal</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save,document" class="rounded-full bg-gradient-to-r from-sky-500 to-sky-600 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-sky-500/30 transition-all duration-300 ease-spring hover:scale-105 disabled:opacity-60">
                        Unggah & Nilai
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
