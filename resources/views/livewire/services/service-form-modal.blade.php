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
            class="glass-panel max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl shadow-2xl"
        >
            <form wire:submit="save">
                <div class="flex items-center justify-between border-b border-white/40 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">
                        {{ $recordId ? 'Ubah Layanan' : 'Layanan Baru' }}
                    </h2>
                    <button type="button" wire:click="close" class="rounded-full p-1.5 text-gray-400 transition-all duration-300 ease-spring hover:rotate-90 hover:scale-110 hover:bg-white/60 hover:text-gray-600">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Nama Layanan SPBE</label>
                        <input type="text" wire:model="name" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-sky-400 focus:ring-sky-400">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Deskripsi / Cakupan Layanan</label>
                        <textarea wire:model="description" rows="2" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-sky-400 focus:ring-sky-400"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Pengelola Layanan (Owner)</label>
                            <input type="text" wire:model="owner_unit" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-sky-400 focus:ring-sky-400">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Pengelola Teknis</label>
                            <input type="text" wire:model="technical_manager" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-sky-400 focus:ring-sky-400">
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Kode Personil <span class="font-normal text-gray-400">(opsional)</span></label>
                        <input type="text" wire:model="personnel_ref" placeholder="Dikosongkan = digenerate otomatis" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-sky-400 focus:ring-sky-400">
                        <p class="mt-1 text-[11px] text-gray-400">Isi kode yang sama di record lain (modul apa pun) untuk menautkannya secara pasti sebagai orang/pihak ketiga yang sama.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Cara Akses</label>
                            <input type="text" wire:model="access_method" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-sky-400 focus:ring-sky-400">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Waktu Pelayanan</label>
                            <input type="text" wire:model="service_hours" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-sky-400 focus:ring-sky-400">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Target Ketersediaan (SLA)</label>
                            <input type="text" wire:model="sla_target" placeholder="mis. 99.5%" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-sky-400 focus:ring-sky-400">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                            <input type="text" list="service-status-suggestions" wire:model="status" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-sky-400 focus:ring-sky-400">
                            <datalist id="service-status-suggestions">
                                <option value="Aktif"><option value="Non-aktif"><option value="Dalam Pemeliharaan">
                            </datalist>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Aset TIK Terkait</label>
                        <select wire:model="asset_ids" multiple size="5" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:border-sky-400 focus:ring-sky-400">
                            @foreach ($assets as $asset)
                                <option value="{{ $asset->id }}">{{ $asset->asset_code }} — {{ $asset->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-400">Tahan Ctrl (atau Cmd) untuk memilih lebih dari satu aset.</p>
                    </div>

                    <div class="border-t border-white/40 pt-4">
                        <div class="mb-2 flex items-center justify-between">
                            <label class="text-sm font-medium text-gray-700">Data Dinamis</label>
                            <button type="button" wire:click="addDynamicRow" class="group inline-flex items-center gap-1 text-xs font-medium text-sky-600 transition-colors duration-150 hover:text-sky-700 active:scale-95">
                                <x-heroicon-o-plus class="h-3.5 w-3.5 transition-transform duration-150 group-hover:rotate-90" /> Tambah Kolom
                            </button>
                        </div>

                        <div class="space-y-2">
                            @foreach ($dynamicRows as $index => $row)
                                <div class="flex items-center gap-2" wire:key="service-dynamic-row-{{ $index }}">
                                    <input type="text" placeholder="Nama Kolom" wire:model="dynamicRows.{{ $index }}.key" class="w-2/5 rounded-lg border-white/60 bg-white/50 text-sm focus:border-sky-400 focus:ring-sky-400">
                                    <input type="text" placeholder="Nilai" wire:model="dynamicRows.{{ $index }}.value" class="flex-1 rounded-lg border-white/60 bg-white/50 text-sm focus:border-sky-400 focus:ring-sky-400">
                                    <button type="button" wire:click="removeDynamicRow({{ $index }})" class="rounded p-1.5 text-gray-400 transition-all duration-150 hover:bg-red-50 hover:text-red-600 active:scale-90">
                                        <x-heroicon-o-trash class="h-4 w-4" />
                                    </button>
                                </div>
                            @endforeach

                            @if (empty($dynamicRows))
                                <p class="text-xs text-gray-400">Belum ada kolom dinamis.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-white/40 px-6 py-4">
                    <button type="button" wire:click="close" x-ripple class="rounded-full border border-white/60 bg-white/40 px-4 py-2 text-sm font-medium text-gray-700 transition-all duration-300 ease-spring hover:scale-105 hover:bg-white/70 active:scale-95">
                        Batal
                    </button>
                    <button type="submit" x-ripple class="rounded-full bg-gradient-to-r from-sky-500 to-sky-600 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-sky-500/30 transition-all duration-300 ease-spring hover:scale-105 hover:shadow-sky-500/50 active:scale-95">
                        <span wire:loading.remove wire:target="save">Simpan</span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
