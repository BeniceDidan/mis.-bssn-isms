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
            <form wire:submit.prevent="save">
                <div class="flex items-center justify-between border-b border-white/40 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">Log Operasional Baru</h2>
                    <button type="button" wire:click="close" class="rounded-full p-1.5 text-gray-400 transition-all duration-300 ease-spring hover:rotate-90 hover:scale-110 hover:bg-white/60 hover:text-gray-600">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Tanggal Masuk</label>
                            <input type="date" wire:model="reported_at" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Nama Pemohon</label>
                            <input type="text" wire:model="requester_name" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Jenis Permintaan / Gangguan <span class="text-red-500">*</span></label>
                        <textarea wire:model="issue" rows="2" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150"></textarea>
                        @error('issue') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Tingkat Dampak</label>
                            <select wire:model="impact_level" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                                <option value="">Pilih tingkat...</option>
                                @foreach ($levels as $level)
                                    <option value="{{ $level->value }}">{{ $level->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Status Tiket</label>
                            <select wire:model="status" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                                <option value="baru">Baru</option>
                                <option value="diproses">Diproses</option>
                                <option value="selesai">Selesai</option>
                                <option value="ditutup">Ditutup</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Risiko TIK Terkait <span class="text-gray-400">(opsional, teks bebas)</span></label>
                        <input type="text" wire:model="related_risk_text" placeholder="mis. RSK-2026-0006" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Solusi / Tindakan Operasional</label>
                        <textarea wire:model="resolution" rows="2" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150"></textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Kode Legacy <span class="text-gray-400">(opsional)</span></label>
                        <input type="text" wire:model="legacy_code" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
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
