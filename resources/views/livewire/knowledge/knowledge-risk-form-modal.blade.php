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
            <form wire:submit="save">
                <div class="flex items-center justify-between border-b border-white/40 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">
                        {{ $recordId ? 'Ubah Risiko KM' : 'Risiko KM Baru' }}
                    </h2>
                    <button type="button" wire:click="close" class="rounded-full p-1.5 text-gray-400 transition-all duration-300 ease-spring hover:rotate-90 hover:scale-110 hover:bg-white/60 hover:text-gray-600">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Aset Pengetahuan Terkait</label>
                        <select wire:model="knowledge_asset_id" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:border-amber-400 focus:ring-amber-400">
                            <option value="">Tidak terkait aset spesifik</option>
                            @foreach ($assets as $asset)
                                <option value="{{ $asset->id }}">{{ $asset->legacy_code }} &mdash; {{ $asset->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Pernyataan Risiko</label>
                        <textarea wire:model="pernyataan_risiko" rows="2" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-amber-400 focus:ring-amber-400"></textarea>
                        @error('pernyataan_risiko') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Akar Penyebab</label>
                        <textarea wire:model="akar_penyebab" rows="2" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-amber-400 focus:ring-amber-400"></textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Area Dampak BSSN</label>
                        <input type="text" list="area-dampak-suggestions" wire:model="area_dampak" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-amber-400 focus:ring-amber-400">
                        <datalist id="area-dampak-suggestions">
                            <option value="Operasional & SDM"><option value="Kepatuhan & Hukum"><option value="Kerahasiaan & Keamanan"><option value="Ketersediaan & Operasional">
                        </datalist>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Dampak (1-5)</label>
                            <select wire:model.live="dampak" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:border-amber-400 focus:ring-amber-400">
                                <option value="">Pilih...</option>
                                @foreach (range(1, 5) as $n)
                                    <option value="{{ $n }}">{{ $n }}</option>
                                @endforeach
                            </select>
                            @error('dampak') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Kemungkinan (1-5)</label>
                            <select wire:model.live="kemungkinan" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:border-amber-400 focus:ring-amber-400">
                                <option value="">Pilih...</option>
                                @foreach (range(1, 5) as $n)
                                    <option value="{{ $n }}">{{ $n }}</option>
                                @endforeach
                            </select>
                            @error('kemungkinan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    @if ($this->derivedLevel())
                        <div class="flex items-center gap-2 rounded-lg border border-white/40 bg-white/40 px-3 py-2 text-xs">
                            <span class="text-gray-500">Tingkat Risiko Bawaan (otomatis, matriks BSSN 5x5):</span>
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold text-white" style="background-color: {{ $this->derivedLevel()->accentHex() }}">{{ $this->derivedLevel()->label() }}</span>
                        </div>
                    @endif

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Rencana Mitigasi / Kontrol Pengendalian</label>
                        <textarea wire:model="rencana_mitigasi" rows="2" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-amber-400 focus:ring-amber-400"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Penanggung Jawab</label>
                            <input type="text" wire:model="penanggung_jawab" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-amber-400 focus:ring-amber-400">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Tingkat Residual Risk</label>
                            <select wire:model="tingkat_residual_risk" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:border-amber-400 focus:ring-amber-400">
                                <option value="">Pilih tingkat...</option>
                                @foreach ($levels as $level)
                                    <option value="{{ $level->value }}">{{ $level->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Kode Personil <span class="font-normal text-gray-400">(opsional)</span></label>
                        <input type="text" list="knowledge-risk-personnel-suggestions" wire:model="personnel_ref" placeholder="Pilih dari SDM yang sudah ada, atau ketik kode baru" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-amber-400 focus:ring-amber-400">
                        <datalist id="knowledge-risk-personnel-suggestions">
                            @foreach ($hrRisks as $hrRisk)
                                <option value="{{ $hrRisk->personnel_ref }}">{{ $hrRisk->subject }} — {{ $hrRisk->record_code }}</option>
                            @endforeach
                        </datalist>
                        <p class="mt-1 text-[11px] text-gray-400">Ketik untuk melihat saran SDM yang sudah diinput, atau kode yang sama di modul lain untuk menautkannya secara pasti sebagai orang/pihak ketiga yang sama.</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-white/40 px-6 py-4">
                    <button type="button" wire:click="close" x-ripple class="rounded-full border border-white/60 bg-white/40 px-4 py-2 text-sm font-medium text-gray-700 transition-all duration-300 ease-spring hover:scale-105 hover:bg-white/70 active:scale-95">
                        Batal
                    </button>
                    <button type="submit" x-ripple class="rounded-full bg-gradient-to-r from-amber-500 to-amber-600 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-amber-500/30 transition-all duration-300 ease-spring hover:scale-105 hover:shadow-amber-500/50 active:scale-95">
                        <span wire:loading.remove wire:target="save">Simpan</span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
