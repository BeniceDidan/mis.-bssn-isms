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
                        {{ $recordId ? 'Ubah Aset Pengetahuan' : 'Aset Pengetahuan Baru' }}
                    </h2>
                    <button type="button" wire:click="close" class="rounded-full p-1.5 text-gray-400 transition-all duration-300 ease-spring hover:rotate-90 hover:scale-110 hover:bg-white/60 hover:text-gray-600">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Nama Aset Pengetahuan</label>
                        <input type="text" wire:model="title" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-amber-400 focus:ring-amber-400">
                        @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Aset Terkait</label>
                            <select wire:model="asset_id" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:border-amber-400 focus:ring-amber-400">
                                <option value="">Tidak terkait aset spesifik</option>
                                @foreach ($assets as $assetOption)
                                    <option value="{{ $assetOption->id }}">{{ $assetOption->asset_code }} &mdash; {{ $assetOption->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Jenis Pengetahuan</label>
                            <select wire:model="knowledge_type" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:border-amber-400 focus:ring-amber-400">
                                <option value="">Pilih jenis...</option>
                                @foreach ($types as $typeOption)
                                    <option value="{{ $typeOption->value }}">{{ $typeOption->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Kategori Pengetahuan</label>
                            <input type="text" wire:model="category" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-amber-400 focus:ring-amber-400">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Unit Pemilik (Owner)</label>
                            <input type="text" wire:model="owner_unit" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-amber-400 focus:ring-amber-400">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Tingkat Aksesibilitas</label>
                            <input
                                type="text"
                                list="knw-access-suggestions"
                                wire:model="access_level"
                                class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-amber-400 focus:ring-amber-400"
                            >
                            <datalist id="knw-access-suggestions">
                                <option value="Publik"><option value="Internal"><option value="Terbatas"><option value="Rahasia">
                            </datalist>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Tanggal Pembaruan Terakhir</label>
                            <input type="date" wire:model="last_reviewed_at" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-amber-400 focus:ring-amber-400">
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Kode Personil <span class="font-normal text-gray-400">(opsional)</span></label>
                        <input type="text" list="knowledge-personnel-suggestions" wire:model="personnel_ref" placeholder="Pilih dari SDM yang sudah ada, atau ketik kode baru" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-amber-400 focus:ring-amber-400">
                        <datalist id="knowledge-personnel-suggestions">
                            @foreach ($hrRisks as $hrRisk)
                                <option value="{{ $hrRisk->personnel_ref }}">{{ $hrRisk->subject }} — {{ $hrRisk->record_code }}</option>
                            @endforeach
                        </datalist>
                        <p class="mt-1 text-[11px] text-gray-400">Ketik untuk melihat saran SDM yang sudah diinput (mis. penulisnya), atau kode yang sama di modul lain untuk menautkannya secara pasti.</p>
                    </div>

                    <div class="border-t border-white/40 pt-4">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Tautan Referensi</label>
                        <input type="url" wire:model="external_link" placeholder="https://..." class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-amber-400 focus:ring-amber-400">
                        @error('external_link') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-gray-400">Boleh diisi salah satu atau keduanya bersama file PDF di bawah — mis. tautan ke dokumen bersama atau wiki internal.</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Lampiran PDF</label>

                        @if ($existingDocumentPath)
                            <div class="flex items-center justify-between rounded-lg border border-white/50 bg-white/50 px-3 py-2 text-sm">
                                <a href="{{ Illuminate\Support\Facades\Storage::disk('public')->url($existingDocumentPath) }}" target="_blank" class="flex items-center gap-2 truncate text-sky-700 hover:underline">
                                    <x-heroicon-o-document-text class="h-4 w-4 shrink-0" />
                                    <span class="truncate">{{ $existingDocumentName ?? 'Lihat dokumen' }}</span>
                                </a>
                                <button type="button" wire:click="removeDocument" class="ml-2 shrink-0 rounded p-1 text-gray-400 transition-all duration-150 hover:bg-red-50 hover:text-red-600 active:scale-90">
                                    <x-heroicon-o-trash class="h-4 w-4" />
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-gray-400">Pilih file baru di bawah untuk menggantinya.</p>
                        @endif

                        <input
                            type="file"
                            wire:model="document"
                            accept="application/pdf"
                            class="mt-2 block w-full text-sm text-gray-600 transition-colors duration-150 file:mr-3 file:rounded-full file:border-0 file:bg-amber-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-amber-700 file:transition-all file:duration-300 file:ease-spring hover:file:scale-105 hover:file:bg-amber-200"
                        >
                        @error('document') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        <div wire:loading wire:target="document" class="mt-1 text-xs text-gray-400">Mengunggah...</div>
                    </div>

                    <div class="border-t border-white/40 pt-4">
                        <div class="mb-2 flex items-center justify-between">
                            <label class="text-sm font-medium text-gray-700">Data Dinamis</label>
                            <button type="button" wire:click="addDynamicRow" class="group inline-flex items-center gap-1 text-xs font-medium text-amber-600 transition-colors duration-150 hover:text-amber-700 active:scale-95">
                                <x-heroicon-o-plus class="h-3.5 w-3.5 transition-transform duration-150 group-hover:rotate-90" /> Tambah Kolom
                            </button>
                        </div>

                        <div class="space-y-2">
                            @foreach ($dynamicRows as $index => $row)
                                <div class="flex items-center gap-2" wire:key="knw-dynamic-row-{{ $index }}">
                                    <input type="text" placeholder="Nama Kolom" wire:model="dynamicRows.{{ $index }}.key" class="w-2/5 rounded-lg border-white/60 bg-white/50 text-sm focus:border-amber-400 focus:ring-amber-400">
                                    <input type="text" placeholder="Nilai" wire:model="dynamicRows.{{ $index }}.value" class="flex-1 rounded-lg border-white/60 bg-white/50 text-sm focus:border-amber-400 focus:ring-amber-400">
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
                    <button type="submit" x-ripple class="rounded-full bg-gradient-to-r from-amber-500 to-amber-600 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-amber-500/30 transition-all duration-300 ease-spring hover:scale-105 hover:shadow-amber-500/50 active:scale-95">
                        <span wire:loading.remove wire:target="save">Simpan</span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
