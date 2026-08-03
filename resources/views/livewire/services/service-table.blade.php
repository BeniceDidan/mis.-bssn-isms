<div class="space-y-4" x-data="{ toast: null }" x-on:toast.window="toast = $event.detail.message; setTimeout(() => toast = null, 3000)">

    <div class="glass-panel flex flex-col gap-3 rounded-2xl p-4 shadow-lg md:flex-row md:flex-wrap md:items-center md:justify-center md:gap-6">
        <div class="relative w-full md:max-w-sm" x-data="{ focused: false }">
            <x-heroicon-o-magnifying-glass
                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 transition-all duration-300 ease-spring"
                x-bind:class="focused ? 'text-sky-500 scale-125' : ''"
            />
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari kode, nama layanan, pengelola..."
                class="w-full rounded-full border-white/60 bg-white/50 pl-9 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-sky-400 focus:bg-white/90 focus:ring-sky-400"
                @focus="focused = true"
                @blur="focused = false"
            >
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-600 transition-colors duration-150 hover:text-gray-800">
                <input type="checkbox" wire:model.live="showArchived" class="rounded border-gray-300 text-sky-600 transition-colors duration-150 focus:ring-sky-500">
                Tampilkan Arsip
            </label>
        </div>
    </div>

    <div class="glass-panel overflow-hidden rounded-2xl shadow-lg">
        <div class="max-h-[65vh] overflow-auto">
            <table class="min-w-full divide-y divide-white/40 text-sm">
                <thead class="sticky top-0 z-10 bg-white/70 backdrop-blur-xl">
                    <tr>
                        @foreach ([
                            'name' => 'Nama Layanan',
                            'owner_unit' => 'Pengelola',
                            'sla_target' => 'SLA',
                            'status' => 'Status',
                        ] as $field => $label)
                            <th
                                wire:click="sortBy('{{ $field }}')"
                                class="cursor-pointer select-none whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 transition-all duration-200 hover:bg-white/60 hover:text-gray-700"
                            >
                                <span class="inline-flex items-center gap-1">
                                    {{ $label }}
                                    @if ($sortField === $field)
                                        <x-dynamic-component
                                            :component="$sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down'"
                                            class="h-3.5 w-3.5 text-sky-600 transition-transform duration-300 ease-spring"
                                        />
                                    @endif
                                </span>
                            </th>
                        @endforeach
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Aset Terkait</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody wire:loading wire:target="search,showArchived,sortBy,previousPage,nextPage,gotoPage" class="divide-y divide-white/40">
                    <x-table-skeleton :columns="6" />
                </tbody>
                <tbody wire:loading.remove wire:target="search,showArchived,sortBy,previousPage,nextPage,gotoPage" class="divide-y divide-white/40">
                    @forelse ($records as $record)
                        <tr
                            wire:key="service-{{ $record->id }}"
                            class="transition-all duration-200 hover:bg-white/60 hover:shadow-sm {{ $record->trashed() ? 'opacity-60' : '' }}"
                        >
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-900">
                                {{ $record->name }}
                                @php
                                    $verificationBadge = [
                                        'menunggu_verifikasi' => ['label' => 'Menunggu Verifikasi', 'class' => 'bg-amber-100 text-amber-700'],
                                        'ditolak' => ['label' => 'Ditolak', 'class' => 'bg-red-100 text-red-700'],
                                    ][$record->verification_status] ?? null;
                                @endphp
                                @if ($verificationBadge)
                                    <span class="mt-0.5 block w-fit rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $verificationBadge['class'] }}">
                                        {{ $verificationBadge['label'] }}
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $record->owner_unit ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $record->sla_target ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $record->status ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex items-center gap-1 rounded-full bg-teal-100 px-2 py-0.5 text-xs font-semibold text-teal-700">
                                    <x-heroicon-o-archive-box class="h-3 w-3" /> {{ $record->assets_count }}
                                </span>
                                @if ($record->tickets_count > 0)
                                    <span class="ml-1 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">
                                        <x-heroicon-o-ticket class="h-3 w-3" /> {{ $record->tickets_count }}
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button wire:click="viewDetail({{ $record->id }})" title="Lihat Detail" class="rounded-full p-1.5 text-gray-500 transition-all duration-300 ease-spring hover:scale-125 hover:bg-sky-100 hover:text-sky-600 active:scale-90">
                                        <x-heroicon-o-eye class="h-4 w-4" />
                                    </button>

                                    @if (auth()->user()?->canWrite())
                                        @if (! $record->trashed())
                                            <button
                                                wire:click="$dispatch('open-service-form', { recordId: {{ $record->id }} })"
                                                title="Ubah"
                                                class="rounded-full p-1.5 text-gray-500 transition-all duration-300 ease-spring hover:scale-125 hover:bg-sky-100 hover:text-sky-600 active:scale-90"
                                            >
                                                <x-heroicon-o-pencil-square class="h-4 w-4" />
                                            </button>
                                            <button
                                                x-on:click="if (await $store.confirm.ask('Arsipkan layanan ini?', 'Data tidak akan dihapus dan dapat dipulihkan kembali.', 'danger')) $wire.archive({{ $record->id }})"
                                                title="Arsipkan"
                                                class="rounded-full p-1.5 text-gray-500 transition-all duration-300 ease-spring hover:scale-125 hover:bg-red-100 hover:text-red-600 active:scale-90"
                                            >
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
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">
                                Tidak ada layanan yang cocok dengan pencarian ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-white/40 px-4 py-3">
            {{ $records->links() }}
        </div>
    </div>

    <div x-data="{ open: @entangle('detailOpen') }">
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-cloak
            class="fixed inset-x-0 top-16 bottom-0 z-50 flex justify-end bg-slate-900/30 backdrop-blur-sm"
            wire:click.self="closeDetail"
        >
            <div
                x-show="open"
                x-transition:enter="transition ease-spring duration-500"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="glass-panel h-full w-full max-w-md overflow-y-auto shadow-2xl"
            >
                @if ($viewingRecord)
                    <div class="flex items-center justify-between border-b border-white/40 px-5 py-4">
                        <div>
                            <p class="text-xs font-mono text-gray-400">{{ $viewingRecord->service_code }}</p>
                            <h2 class="text-lg font-semibold text-gray-900">{{ $viewingRecord->name }}</h2>
                        </div>
                        <button wire:click="closeDetail" class="rounded-full p-1.5 text-gray-400 transition-all duration-300 ease-spring hover:rotate-90 hover:scale-110 hover:bg-white/60 hover:text-gray-600">
                            <x-heroicon-o-x-mark class="h-5 w-5" />
                        </button>
                    </div>

                    <dl class="divide-y divide-white/40 px-5">
                        @foreach ([
                            'Deskripsi' => $viewingRecord->description,
                            'Pengelola Layanan' => $viewingRecord->owner_unit,
                            'Pengelola Teknis' => $viewingRecord->technical_manager,
                            'Cara Akses' => $viewingRecord->access_method,
                            'Target SLA' => $viewingRecord->sla_target,
                            'Waktu Pelayanan' => $viewingRecord->service_hours,
                            'Status' => $viewingRecord->status,
                            'Kode Legacy' => $viewingRecord->legacy_code,
                        ] as $label => $value)
                            <div class="grid grid-cols-2 gap-2 py-2.5 text-sm">
                                <dt class="text-gray-500">{{ $label }}</dt>
                                <dd class="break-words text-gray-900">{{ $value ?: '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    {{-- Aset TIK terkait — many-to-many, so this shows every
                         linked asset, not just one. --}}
                    <div class="border-t border-white/40 px-5 py-4">
                        <p class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-400">
                            <x-heroicon-o-archive-box class="h-3.5 w-3.5" /> Aset TIK Terkait
                        </p>
                        @if ($viewingRecord->assets->isNotEmpty())
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($viewingRecord->assets as $asset)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-teal-50 px-2.5 py-1 text-xs font-medium text-teal-700">
                                        {{ $asset->asset_code }} — {{ $asset->name }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-400">Belum ada aset yang ditautkan.</p>
                        @endif
                    </div>

                    <div class="border-t border-white/40 px-5 py-4">
                        <div class="mb-2 flex items-center justify-between">
                            <p class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                <x-heroicon-o-ticket class="h-3.5 w-3.5" /> Log Operasional Terbaru
                            </p>
                            @if (auth()->user()?->canWrite())
                                <button
                                    x-data
                                    x-ripple
                                    @click="$dispatch('open-service-ticket-form', { serviceId: {{ $viewingRecord->id }} })"
                                    class="flex items-center gap-1 rounded-full bg-sky-50 px-2 py-1 text-[11px] font-medium text-sky-700 transition-all duration-200 hover:scale-105 hover:bg-sky-100"
                                >
                                    <x-heroicon-o-plus class="h-3 w-3" /> Tambah
                                </button>
                            @endif
                        </div>
                        @if ($viewingRecord->tickets->isNotEmpty())
                            <div class="space-y-2">
                                @foreach ($viewingRecord->tickets as $ticket)
                                    <div class="rounded-lg border border-white/40 bg-white/40 p-2.5 text-xs">
                                        <div class="flex items-center justify-between">
                                            <span class="font-mono text-gray-500">{{ $ticket->legacy_code }}</span>
                                            <span class="text-gray-400">{{ optional($ticket->reported_at)->format('d M Y') }}</span>
                                        </div>
                                        <p class="mt-1 text-gray-700">{{ $ticket->issue }}</p>
                                        <div class="mt-1 flex items-center gap-1.5">
                                            @if ($ticket->impact_level)
                                                <span class="rounded-full px-1.5 py-0.5 text-[10px] font-semibold" style="background-color: {{ $ticket->impact_level->accentHex() }}22; color: {{ $ticket->impact_level->accentHex() }}">
                                                    {{ $ticket->impact_level->label() }}
                                                </span>
                                            @endif
                                            <span class="text-gray-500">{{ $ticket->status }}</span>
                                        </div>
                                        @if ($ticket->related_risk_text)
                                            <p class="mt-1 text-gray-400">Risiko terkait: {{ $ticket->related_risk_text }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-400">Belum ada tiket operasional tercatat.</p>
                        @endif
                    </div>

                    <div class="border-t border-white/40 px-5 py-4">
                        <div class="mb-2 flex items-center justify-between">
                            <p class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                <x-heroicon-o-chart-bar class="h-3.5 w-3.5" /> Evaluasi Kinerja
                            </p>
                            @if (auth()->user()?->canWrite())
                                <button
                                    x-data
                                    x-ripple
                                    @click="$dispatch('open-service-evaluation-form', { serviceId: {{ $viewingRecord->id }} })"
                                    class="flex items-center gap-1 rounded-full bg-sky-50 px-2 py-1 text-[11px] font-medium text-sky-700 transition-all duration-200 hover:scale-105 hover:bg-sky-100"
                                >
                                    <x-heroicon-o-plus class="h-3 w-3" /> Tambah
                                </button>
                            @endif
                        </div>
                        @if ($viewingRecord->evaluations->isNotEmpty())
                            <div class="space-y-2">
                                @foreach ($viewingRecord->evaluations as $evaluation)
                                    <div class="rounded-lg border border-white/40 bg-white/40 p-2.5 text-xs">
                                        <div class="flex items-center justify-between">
                                            <span class="font-semibold text-gray-700">Uptime {{ $evaluation->uptime_actual ?: '—' }} / SLA {{ $evaluation->sla_target ?: '—' }}</span>
                                            <span class="text-gray-400">{{ $evaluation->achievement_status }}</span>
                                        </div>
                                        <p class="mt-1 text-gray-500">Gangguan: {{ $evaluation->incident_count ?? '—' }} &middot; MTTR: {{ $evaluation->mttr ?: '—' }}</p>
                                        @if ($evaluation->recommendation)
                                            <p class="mt-1 text-gray-400">{{ $evaluation->recommendation }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-400">Belum ada evaluasi kinerja tercatat.</p>
                        @endif
                    </div>

                    @if (! empty($viewingRecord->dynamic_data))
                        <div class="border-t border-white/40 px-5 py-4">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Data Dinamis</p>
                            <dl class="divide-y divide-white/40">
                                @foreach ($viewingRecord->dynamic_data as $key => $value)
                                    <div class="grid grid-cols-2 gap-2 py-2.5 text-sm">
                                        <dt class="text-gray-500">{{ $key }}</dt>
                                        <dd class="break-words text-gray-900">{{ is_array($value) ? json_encode($value) : $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endif

                    @if ($viewingRecord->verifications->isNotEmpty())
                        <div class="border-t border-white/40 px-5 py-4">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Riwayat Verifikasi</p>
                            <div class="space-y-2">
                                @foreach ($viewingRecord->verifications as $verification)
                                    <div class="rounded-lg bg-white/50 px-3 py-2 text-xs">
                                        <div class="flex items-center justify-between">
                                            <span class="font-medium {{ $verification->status === 'ditolak' ? 'text-red-600' : 'text-emerald-600' }}">
                                                {{ $verification->status === 'ditolak' ? 'Ditolak' : 'Disetujui' }}
                                            </span>
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

    <div
        x-show="toast"
        x-transition:enter="transition ease-spring duration-400"
        x-transition:enter-start="opacity-0 translate-y-6 scale-90"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-90"
        x-cloak
        class="glass-panel-dark fixed bottom-5 right-5 z-50 rounded-full px-4 py-2.5 text-sm text-white shadow-2xl"
    >
        <span x-text="toast"></span>
    </div>
</div>
