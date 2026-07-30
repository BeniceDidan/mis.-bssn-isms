<div class="space-y-4" x-data="{ toast: null }" x-on:toast.window="toast = $event.detail.message; setTimeout(() => toast = null, 3000)">

    <div class="glass-panel flex flex-col gap-3 rounded-2xl p-4 shadow-lg md:flex-row md:flex-wrap md:items-center md:justify-center md:gap-6">
        <div class="relative w-full md:max-w-sm" x-data="{ focused: false }">
            <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 transition-all duration-300 ease-spring" x-bind:class="focused ? 'text-amber-500 scale-125' : ''" />
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama, unit kerja, keahlian..." class="w-full rounded-full border-white/60 bg-white/50 pl-9 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-amber-400 focus:bg-white/90 focus:ring-amber-400" @focus="focused = true" @blur="focused = false">
        </div>
        <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-600 transition-colors duration-150 hover:text-gray-800">
            <input type="checkbox" wire:model.live="showArchived" class="rounded border-gray-300 text-amber-600 transition-colors duration-150 focus:ring-amber-500">
            Tampilkan Arsip
        </label>
    </div>

    <div class="glass-panel overflow-hidden rounded-2xl shadow-lg">
        <div class="max-h-[60vh] overflow-auto">
            <table class="min-w-full divide-y divide-white/40 text-sm">
                <thead class="sticky top-0 z-10 bg-white/70 backdrop-blur-xl">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Nama Pegawai</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Jabatan & Unit Kerja</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Peran KM</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Kegiatan</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody wire:loading wire:target="search,showArchived,previousPage,nextPage,gotoPage" class="divide-y divide-white/40">
                    <x-table-skeleton :columns="5" />
                </tbody>
                <tbody wire:loading.remove wire:target="search,showArchived,previousPage,nextPage,gotoPage" class="divide-y divide-white/40">
                    @forelse ($records as $record)
                        <tr wire:key="expert-{{ $record->id }}" class="transition-all duration-200 hover:bg-white/60 hover:shadow-sm {{ $record->trashed() ? 'opacity-60' : '' }}">
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-900">
                                {{ $record->nama_pegawai }}
                                @php
                                    $verificationBadge = [
                                        'menunggu_verifikasi' => ['label' => 'Menunggu Verifikasi', 'class' => 'bg-amber-100 text-amber-700'],
                                        'ditolak' => ['label' => 'Ditolak', 'class' => 'bg-red-100 text-red-700'],
                                    ][$record->verification_status] ?? null;
                                @endphp
                                @if ($verificationBadge)
                                    <span class="mt-0.5 block w-fit rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $verificationBadge['class'] }}">{{ $verificationBadge['label'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $record->jabatan_unit ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @if ($record->peran_km)
                                    <span class="inline-flex rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700">{{ $record->peran_km }}</span>
                                @else
                                    &mdash;
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $record->activities_count }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button wire:click="viewDetail({{ $record->id }})" title="Lihat Detail" class="rounded-full p-1.5 text-gray-500 transition-all duration-300 ease-spring hover:scale-125 hover:bg-amber-100 hover:text-amber-600 active:scale-90">
                                        <x-heroicon-o-eye class="h-4 w-4" />
                                    </button>
                                    @if (auth()->user()?->canWrite())
                                        @if (! $record->trashed())
                                            <button wire:click="$dispatch('open-knowledge-expert-form', { recordId: {{ $record->id }} })" title="Ubah" class="rounded-full p-1.5 text-gray-500 transition-all duration-300 ease-spring hover:scale-125 hover:bg-amber-100 hover:text-amber-600 active:scale-90">
                                                <x-heroicon-o-pencil-square class="h-4 w-4" />
                                            </button>
                                            <button x-on:click="if (await $store.confirm.ask('Arsipkan data ini?', 'Data tidak akan dihapus dan dapat dipulihkan kembali.', 'danger')) $wire.archive({{ $record->id }})" title="Arsipkan" class="rounded-full p-1.5 text-gray-500 transition-all duration-300 ease-spring hover:scale-125 hover:bg-red-100 hover:text-red-600 active:scale-90">
                                                <x-heroicon-o-archive-box-x-mark class="h-4 w-4" />
                                            </button>
                                        @else
                                            <button wire:click="restore({{ $record->id }})" title="Pulihkan" class="rounded-full p-1.5 text-gray-500 transition-all duration-300 ease-spring hover:scale-125 hover:bg-emerald-100 hover:text-emerald-600 active:scale-90">
                                                <x-heroicon-o-arrow-uturn-left class="h-4 w-4" />
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">Tidak ada data yang cocok dengan pencarian ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-white/40 px-4 py-3">{{ $records->links() }}</div>
    </div>

    <div x-data="{ open: @entangle('detailOpen') }">
        <div x-show="open" x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak class="fixed inset-x-0 top-16 bottom-0 z-50 flex justify-end bg-slate-900/30 backdrop-blur-sm" wire:click.self="closeDetail">
            <div x-show="open" x-transition:enter="transition ease-spring duration-500" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="glass-panel h-full w-full max-w-md overflow-y-auto shadow-2xl">
                @if ($viewingRecord)
                    <div class="flex items-center justify-between border-b border-white/40 px-5 py-4">
                        <div>
                            <p class="text-xs font-mono text-gray-400">{{ $viewingRecord->record_code }}</p>
                            <h2 class="text-lg font-semibold text-gray-900">{{ $viewingRecord->nama_pegawai }}</h2>
                        </div>
                        <button wire:click="closeDetail" class="rounded-full p-1.5 text-gray-400 transition-all duration-300 ease-spring hover:rotate-90 hover:scale-110 hover:bg-white/60 hover:text-gray-600">
                            <x-heroicon-o-x-mark class="h-5 w-5" />
                        </button>
                    </div>

                    <dl class="divide-y divide-white/40 px-5">
                        @foreach ([
                            'Jabatan & Unit Kerja' => $viewingRecord->jabatan_unit,
                            'Keahlian Spesifik' => $viewingRecord->keahlian_spesifik,
                            'Sertifikasi / Lisensi' => $viewingRecord->sertifikasi_lisensi,
                            'Peran KM' => $viewingRecord->peran_km,
                            'NIP (Kode Legacy)' => $viewingRecord->legacy_code,
                        ] as $label => $value)
                            <div class="grid grid-cols-2 gap-2 py-2.5 text-sm">
                                <dt class="text-gray-500">{{ $label }}</dt>
                                <dd class="break-words text-gray-900">{{ $value ?: '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    <div class="border-t border-white/40 px-5 py-4">
                        <p class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-400">
                            <x-heroicon-o-microphone class="h-3.5 w-3.5" /> Aktivitas Sebagai Narasumber
                        </p>
                        @forelse ($viewingRecord->activities as $activity)
                            <div class="mb-1 flex items-center justify-between rounded-lg bg-amber-50/70 px-3 py-2 text-xs">
                                <span class="truncate text-gray-700">{{ $activity->nama_kegiatan }}</span>
                                <span class="ml-2 shrink-0 font-mono text-amber-600">{{ $activity->legacy_code }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400">Belum tercatat sebagai narasumber.</p>
                        @endforelse
                    </div>

                    {{-- Reverse of HrRiskTable's lookup — same person,
                         matched by name (see CrossReferencesEmployeeNames). --}}
                    <div class="border-t border-white/40 px-5 py-4">
                        <p class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-400">
                            <x-heroicon-o-users class="h-3.5 w-3.5" /> Risiko SDM Terkait
                        </p>
                        @forelse ($relatedHrRisks as $risk)
                            <div class="mb-1 rounded-lg bg-slate-50/70 px-3 py-2 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-gray-800">{{ $risk->subject }}</span>
                                    @if ($risk->inherent_risk_level)
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold text-white" style="background-color: {{ $risk->inherent_risk_level->accentHex() }}">{{ $risk->inherent_risk_level->label() }}</span>
                                    @endif
                                </div>
                                <p class="mt-0.5 text-gray-500">{{ $risk->title }}</p>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400">Belum ada record di Manajemen SDM dengan Kode Personil yang sama.</p>
                        @endforelse
                    </div>

                    @if ($viewingRecord->verifications->isNotEmpty())
                        <div class="border-t border-white/40 px-5 py-4">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Riwayat Verifikasi</p>
                            <div class="space-y-2">
                                @foreach ($viewingRecord->verifications as $verification)
                                    <div class="rounded-lg bg-white/50 px-3 py-2 text-xs">
                                        <div class="flex items-center justify-between">
                                            <span class="font-medium {{ $verification->status === 'ditolak' ? 'text-red-600' : 'text-emerald-600' }}">{{ $verification->status === 'ditolak' ? 'Ditolak' : 'Disetujui' }}</span>
                                            <span class="text-gray-400">{{ $verification->created_at?->diffForHumans() }}</span>
                                        </div>
                                        <p class="mt-0.5 text-gray-600">oleh {{ $verification->user?->name ?? 'Admin' }}</p>
                                        @if ($verification->notes)
                                            <p class="mt-1 rounded bg-white/70 px-2 py-1 text-gray-700">{{ $verification->notes }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <div x-show="toast" x-transition:enter="transition ease-spring duration-400" x-transition:enter-start="opacity-0 translate-y-6 scale-90" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-4 scale-90" x-cloak class="glass-panel-dark fixed bottom-5 right-5 z-50 rounded-full px-4 py-2.5 text-sm text-white shadow-2xl">
        <span x-text="toast"></span>
    </div>
</div>
