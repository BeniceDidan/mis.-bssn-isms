{{-- Livewire requires exactly one root element on every render, so this
     wrapper must stay present even while the modal itself is hidden.
     @entangle syncs $show into Alpine so x-show can animate the modal
     in/out instead of the instant show/hide an @if would give. --}}
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
            {{-- Editing an existing asset asks for confirmation first (per
                 request: every asset data change gets an "are you sure?"
                 step) — creating a brand new asset does not, since there's
                 no prior state to accidentally overwrite. Intercepting the
                 form's submit event (not just the button's click) covers
                 both clicking "Simpan" and pressing Enter in a field. --}}
            <form
                x-on:submit.prevent="
                    @if ($assetId)
                        (await $store.confirm.ask('Simpan perubahan?', 'Yakin ingin menyimpan perubahan pada data aset ini?')) && $wire.save()
                    @else
                        $wire.save()
                    @endif
                "
            >
                <div class="flex items-center justify-between border-b border-white/40 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">
                        {{ $assetId ? 'Ubah Aset' : 'Aset Baru' }}
                    </h2>
                    <button type="button" wire:click="close" class="rounded-full p-1.5 text-gray-400 transition-all duration-300 ease-spring hover:rotate-90 hover:scale-110 hover:bg-white/60 hover:text-gray-600">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Kategori</label>
                            <select wire:model="category" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                                <option value="">Pilih kategori...</option>
                                @foreach ($categories as $categoryOption)
                                    <option value="{{ $categoryOption->value }}">{{ $categoryOption->label() }}</option>
                                @endforeach
                            </select>
                            @error('category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Kritikalitas
                                @if ($this->computedCriticality())
                                    <span class="ml-1 text-[10px] font-normal uppercase tracking-wide text-emerald-600">Dihitung otomatis</span>
                                @endif
                            </label>
                            @if ($this->computedCriticality())
                                @php
                                    $badgeClasses = [
                                        'green' => 'bg-green-100 text-green-700',
                                        'yellow' => 'bg-yellow-100 text-yellow-700',
                                        'orange' => 'bg-orange-100 text-orange-700',
                                        'red' => 'bg-red-100 text-red-700',
                                    ][\App\Enums\Level::from($criticality_level)->badgeColor()];
                                @endphp
                                <div class="flex h-[38px] items-center rounded-md border border-gray-200 bg-gray-50 px-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $badgeClasses }}">
                                        {{ \App\Enums\Level::from($criticality_level)->label() }}
                                    </span>
                                </div>
                            @else
                                <select wire:model="criticality_level" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                                    <option value="">Pilih tingkat...</option>
                                    @foreach ($levels as $levelOption)
                                        <option value="{{ $levelOption->value }}">{{ $levelOption->label() }}</option>
                                    @endforeach
                                </select>
                            @endif
                            @error('criticality_level') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="rounded-md border border-sky-100 bg-sky-50/60 p-3">
                        <p class="mb-2 text-xs font-medium text-sky-800">
                            Penilaian CIA (opsional) — isi ketiganya untuk menghitung Kritikalitas otomatis
                        </p>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600">Kerahasiaan</label>
                                <select wire:model.live="confidentiality_score" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                                    <option value="">-</option>
                                    @for ($n = 1; $n <= 5; $n++)
                                        <option value="{{ $n }}">{{ $n }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600">Integritas</label>
                                <select wire:model.live="integrity_score" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                                    <option value="">-</option>
                                    @for ($n = 1; $n <= 5; $n++)
                                        <option value="{{ $n }}">{{ $n }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600">Ketersediaan</label>
                                <select wire:model.live="availability_score" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                                    <option value="">-</option>
                                    @for ($n = 1; $n <= 5; $n++)
                                        <option value="{{ $n }}">{{ $n }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Nama Aset</label>
                        <input type="text" wire:model="name" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Sub Klasifikasi Aset</label>
                        <input
                            type="text"
                            list="sub-classification-suggestions"
                            wire:model="sub_classification"
                            class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150"
                        >
                        <datalist id="sub-classification-suggestions">
                            <option value="Business Process/Prosedur">
                            <option value="Formulir">
                            <option value="Dokumen Kontrak dan Legal">
                            <option value="Server">
                            <option value="PC/Laptop/Smartphone">
                            <option value="Perangkat Jaringan (Network Device)">
                            <option value="Perangkat Penyimpanan (Storage Device)">
                            <option value="Aplikasi Berbasis Website">
                            <option value="Sistem Utility">
                            <option value="Support Appliances">
                            <option value="Technical">
                        </datalist>
                        @error('sub_classification') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Pemilik Aset</label>
                            <input type="text" wire:model="owner" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Lokasi</label>
                            <input type="text" wire:model="location" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Kode Personil <span class="font-normal text-gray-400">(opsional)</span></label>
                        <input type="text" wire:model="personnel_ref" placeholder="Dikosongkan = digenerate otomatis" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                        <p class="mt-1 text-[11px] text-gray-400">Isi kode yang sama di record lain (modul apa pun) untuk menautkannya secara pasti sebagai orang/pihak ketiga yang sama.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                            <input
                                type="text"
                                list="status-suggestions"
                                wire:model="status"
                                class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150"
                            >
                            <datalist id="status-suggestions">
                                <option value="Aktif"><option value="Non-Aktif">
                                <option value="Layak"><option value="Rusak Ringan"><option value="Rusak Berat">
                                <option value="ASN"><option value="Non-ASN">
                            </datalist>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Tahun Referensi</label>
                            <input type="number" wire:model="reference_year" min="1990" max="2100" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500 transition-colors duration-150">
                            @error('reference_year') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-4">
                        <div class="mb-2 flex items-center justify-between">
                            <label class="text-sm font-medium text-gray-700">Data Dinamis (Spesifik Kategori)</label>
                            <button type="button" wire:click="addDynamicRow" class="group inline-flex items-center gap-1 text-xs font-medium text-sky-600 transition-colors duration-150 hover:text-sky-700 active:scale-95">
                                <x-heroicon-o-plus class="h-3.5 w-3.5 transition-transform duration-150 group-hover:rotate-90" /> Tambah Kolom
                            </button>
                        </div>

                        <div class="space-y-2">
                            @foreach ($dynamicRows as $index => $row)
                                <div class="flex items-center gap-2" wire:key="dynamic-row-{{ $index }}">
                                    <input
                                        type="text"
                                        placeholder="Nama Kolom (mis. Alamat IP)"
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
                                <p class="text-xs text-gray-400">Belum ada kolom dinamis. Klik "Tambah Kolom" untuk menambahkan atribut spesifik kategori ini.</p>
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
