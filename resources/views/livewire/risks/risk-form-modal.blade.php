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
                        {{ $riskId ? 'Ubah Risiko' : 'Risiko Baru' }}
                    </h2>
                    <button type="button" wire:click="close" class="rounded-full p-1.5 text-gray-400 transition-all duration-300 ease-spring hover:rotate-90 hover:scale-110 hover:bg-white/60 hover:text-gray-600">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Judul Risiko</label>
                        <input type="text" wire:model="title" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                        @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Aset Terkait</label>
                            <select wire:model.live="asset_id" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                                <option value="">Tidak terkait aset spesifik</option>
                                @foreach ($assets as $assetOption)
                                    <option value="{{ $assetOption->id }}">{{ $assetOption->asset_code }} &mdash; {{ $assetOption->name }}</option>
                                @endforeach
                            </select>
                            @if ($this->selectedAssetCriticality())
                                @php
                                    $assetLevel = $this->selectedAssetCriticality();
                                    $badgeClasses = [
                                        'green' => 'bg-green-100 text-green-700',
                                        'yellow' => 'bg-yellow-100 text-yellow-700',
                                        'orange' => 'bg-orange-100 text-orange-700',
                                        'red' => 'bg-red-100 text-red-700',
                                    ][$assetLevel->badgeColor()];
                                @endphp
                                <p class="mt-1 flex items-center gap-1.5 text-xs text-gray-500">
                                    Kritikalitas aset:
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium {{ $badgeClasses }}">{{ $assetLevel->label() }}</span>
                                </p>
                            @endif
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Kategori Risiko</label>
                            <select wire:model="category" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                                <option value="">Pilih kategori...</option>
                                @foreach ($categories as $categoryOption)
                                    <option value="{{ $categoryOption->value }}">{{ $categoryOption->label() }}</option>
                                @endforeach
                            </select>
                            @error('category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Sumber Ancaman</label>
                        <input type="text" wire:model="threat_source" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Kerentanan</label>
                        <textarea wire:model="vulnerability" rows="2" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Kemungkinan (Likelihood)</label>
                            <select wire:model="likelihood" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                                <option value="">Pilih tingkat...</option>
                                @foreach ($levels as $levelOption)
                                    <option value="{{ $levelOption->value }}">{{ $levelOption->label() }}</option>
                                @endforeach
                            </select>
                            @error('likelihood') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Dampak (Impact)</label>
                            <select wire:model="impact" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                                <option value="">Pilih tingkat...</option>
                                @foreach ($levels as $levelOption)
                                    <option value="{{ $levelOption->value }}">{{ $levelOption->label() }}</option>
                                @endforeach
                            </select>
                            @error('impact') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    @php
                        $escalatedByAsset = in_array($this->selectedAssetCriticality()?->value, ['tinggi', 'kritis'], true);
                        $escalatedBySdm = $this->hasSevereSdmLink();
                    @endphp
                    <p class="text-xs text-gray-400">
                        Level Risiko dihitung otomatis dari Kemungkinan &times; Dampak saat disimpan
                        @if ($escalatedByAsset && $escalatedBySdm)
                            , dan dinaikkan <span class="font-medium text-gray-500">dua tingkat</span> karena aset terkait berkritikalitas {{ $this->selectedAssetCriticality()->label() }} <span class="font-medium">dan</span> ada SDM berlevel Tinggi/Kritis dengan Kode Personil yang sama.
                        @elseif ($escalatedByAsset)
                            , dan dinaikkan satu tingkat karena aset terkait berkritikalitas {{ $this->selectedAssetCriticality()->label() }}.
                        @elseif ($escalatedBySdm)
                            , dan dinaikkan satu tingkat karena ada SDM berlevel Tinggi/Kritis dengan Kode Personil yang sama.
                        @else
                            .
                        @endif
                    </p>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Pemilik Risiko</label>
                            <input type="text" wire:model="risk_owner" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Kode Personil <span class="font-normal text-gray-400">(opsional)</span></label>
                            <input type="text" list="risk-personnel-suggestions" wire:model="personnel_ref" placeholder="Pilih dari SDM yang sudah ada, atau ketik kode baru" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                            <datalist id="risk-personnel-suggestions">
                                @foreach ($hrRisks as $hrRisk)
                                    <option value="{{ $hrRisk->personnel_ref }}">{{ $hrRisk->subject }} — {{ $hrRisk->record_code }}</option>
                                @endforeach
                            </datalist>
                            <p class="mt-1 text-[11px] text-gray-400">Ketik untuk melihat saran SDM yang sudah diinput, atau kode yang sama di modul lain = orang/pihak ketiga yang pasti sama.</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Strategi Penanganan</label>
                            <select wire:model="treatment_strategy" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                                <option value="">Pilih strategi...</option>
                                @foreach ($treatmentStrategies as $strategyOption)
                                    <option value="{{ $strategyOption->value }}">{{ $strategyOption->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                            <select wire:model="status" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                                @foreach ($statuses as $statusOption)
                                    <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Tanggal Identifikasi</label>
                            <input type="date" wire:model="identified_at" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Tinjauan Berikutnya</label>
                            <input type="date" wire:model="review_due_at" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                            @error('review_due_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-4">
                        <div class="mb-2 flex items-center justify-between">
                            <label class="text-sm font-medium text-gray-700">Data Dinamis (mis. Rencana Mitigasi, Risiko Residual)</label>
                            <button type="button" wire:click="addDynamicRow" class="group inline-flex items-center gap-1 text-xs font-medium text-sky-600 transition-colors duration-150 hover:text-sky-700 active:scale-95">
                                <x-heroicon-o-plus class="h-3.5 w-3.5 transition-transform duration-150 group-hover:rotate-90" /> Tambah Kolom
                            </button>
                        </div>

                        <div class="space-y-2">
                            @foreach ($dynamicRows as $index => $row)
                                <div class="flex items-center gap-2" wire:key="risk-dynamic-row-{{ $index }}">
                                    <input
                                        type="text"
                                        placeholder="Nama Kolom (mis. Risiko Residual)"
                                        wire:model="dynamicRows.{{ $index }}.key"
                                        class="w-2/5 rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150"
                                    >
                                    <input
                                        type="text"
                                        placeholder="Nilai"
                                        wire:model="dynamicRows.{{ $index }}.value"
                                        class="flex-1 rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150"
                                    >
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
