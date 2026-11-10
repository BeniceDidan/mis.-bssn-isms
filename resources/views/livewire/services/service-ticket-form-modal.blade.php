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
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 "
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
            <form wire:submit.prevent="save">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">{{ $ticketId ? 'Ubah Tiket' : 'Tiket Baru' }}</h2>
                    <button type="button" wire:click="close" class="rounded-full p-1.5 text-gray-400 transition-all duration-300 ease-spring hover:rotate-90 hover:scale-110 hover:bg-slate-50 hover:text-gray-600">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Layanan</label>
                        @if ($lockedService)
                            <p class="rounded-md bg-white px-3 py-2 text-sm text-gray-800">{{ $lockedService->name }}</p>
                        @else
                            <select wire:model="serviceId" class="w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Pilih layanan...</option>
                                @foreach ($services as $serviceOption)
                                    <option value="{{ $serviceOption->id }}">{{ $serviceOption->name }}</option>
                                @endforeach
                            </select>
                        @endif
                        @error('serviceId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Tanggal Masuk</label>
                            <input type="date" wire:model="reported_at" class="w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Nama Pemohon</label>
                            <input type="text" wire:model="requester_name" class="w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                    </div>

                    <div>
                        <div class="mb-1 flex items-center justify-between">
                            <label class="text-sm font-medium text-gray-700">Isi Permintaan / Gangguan <span class="text-red-500">*</span></label>
                            <button type="button" wire:click="analyze" wire:loading.attr="disabled" wire:target="analyze" class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-700 hover:bg-brand-100 disabled:opacity-60">
                                <x-heroicon-o-sparkles class="h-3.5 w-3.5" />
                                <span wire:loading.remove wire:target="analyze">Analisis AI</span>
                                <span wire:loading wire:target="analyze">Menganalisis...</span>
                            </button>
                        </div>
                        <textarea wire:model="issue" rows="3" placeholder="mis. Aplikasi e-Surat tidak bisa diakses sejak pagi, semua pegawai kena" class="w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                        @error('issue') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                        <div class="col-span-2">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Kategori</label>
                            <select wire:model="category" class="w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Otomatis saat disimpan</option>
                                @foreach ($categories as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Dampak</label>
                            <select wire:model.live="impact_level" class="w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">—</option>
                                @foreach ($levels as $level)
                                    <option value="{{ $level->value }}">{{ $level->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Urgensi</label>
                            <select wire:model.live="urgency" class="w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">—</option>
                                @foreach ($levels as $level)
                                    <option value="{{ $level->value }}">{{ $level->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if ($priority)
                        @php $p = config("helpdesk.priorities.$priority"); @endphp
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-lg bg-white px-3 py-2 text-sm">
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold text-white" style="background-color: {{ $p['color'] }}">{{ $p['label'] }}</span>
                            <span class="text-gray-600">Tanggapan pertama {{ $p['response'] }} · selesai {{ $p['resolution'] }}</span>
                        </div>
                    @endif

                    @if ($triage)
                        <div class="rounded-lg bg-white px-3 py-2 text-xs text-gray-600">
                            @if (! empty($triage['reasons']))
                                <ul class="list-disc space-y-0.5 pl-4">
                                    @foreach ($triage['reasons'] as $reason)
                                        <li>{{ $reason }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            @if (! empty($triage['steps']))
                                <p class="mt-2 font-medium text-gray-700">Langkah awal untuk operator</p>
                                <ol class="list-decimal space-y-0.5 pl-4">
                                    @foreach ($triage['steps'] as $step)
                                        <li>{{ $step }}</li>
                                    @endforeach
                                </ol>
                            @endif
                            <p class="mt-2 text-gray-400">Saran dari {{ $triage['engine'] }}. Periksa sebelum dipakai.</p>
                        </div>
                    @endif

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Draf Balasan untuk Pemohon</label>
                        <textarea wire:model="draft_reply" rows="5" class="w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Status Tiket</label>
                            <select wire:model="status" class="w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="baru">Baru</option>
                                <option value="diproses">Diproses</option>
                                <option value="selesai">Selesai</option>
                                <option value="ditutup">Ditutup</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Risiko TIK Terkait <span class="text-gray-400">(opsional)</span></label>
                            <input type="text" wire:model="related_risk_text" placeholder="mis. RSK-2026-0006" class="w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Solusi / Tindakan Operasional</label>
                        <textarea wire:model="resolution" rows="2" class="w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Kode Legacy <span class="text-gray-400">(opsional)</span></label>
                        <input type="text" wire:model="legacy_code" class="w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-6 py-4">
                    <button type="button" wire:click="close" x-ripple class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-all duration-300 ease-spring hover:scale-105 hover:bg-slate-50 active:scale-95">
                        Batal
                    </button>
                    <button type="submit" x-ripple wire:loading.attr="disabled" wire:target="save,analyze" class="rounded-full bg-brand-700 px-4 py-2 text-sm font-medium text-white shadow-lg transition-all duration-300 ease-spring hover:scale-105 active:scale-95 disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">Simpan</span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
