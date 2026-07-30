<div class="space-y-4" x-data="{ toast: null }" x-on:toast.window="toast = $event.detail.message; setTimeout(() => toast = null, 3000)">

    {{-- Toolbar: local search + strict dropdown filters (rule 4.4) --}}
    <div class="glass-panel flex flex-col gap-3 rounded-2xl p-4 shadow-lg md:flex-row md:flex-wrap md:items-center md:justify-center md:gap-6">
        <div class="relative w-full md:max-w-sm" x-data="{ focused: false }">
            <x-heroicon-o-magnifying-glass
                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 transition-all duration-300 ease-spring"
                x-bind:class="focused ? 'text-sky-500 scale-125' : ''"
            />
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari kode, nama, pemilik, atau data lainnya..."
                class="w-full rounded-full border-white/60 bg-white/50 pl-9 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-sky-400 focus:bg-white/90 focus:ring-sky-400"
                @focus="focused = true"
                @blur="focused = false"
            >
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <select wire:model.live="categoryFilter" class="rounded-full border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring hover:bg-white/80 focus:border-sky-400 focus:ring-sky-400">
                <option value="">Semua Kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->value }}">{{ $category->label() }}</option>
                @endforeach
            </select>

            <select wire:model.live="criticalityFilter" class="rounded-full border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring hover:bg-white/80 focus:border-sky-400 focus:ring-sky-400">
                <option value="">Semua Kritikalitas</option>
                @foreach ($levels as $level)
                    <option value="{{ $level->value }}">{{ $level->label() }}</option>
                @endforeach
            </select>

            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-600 transition-colors duration-150 hover:text-gray-800">
                <input type="checkbox" wire:model.live="showArchived" class="rounded border-gray-300 text-sky-600 transition-colors duration-150 focus:ring-sky-500">
                Tampilkan Arsip
            </label>
        </div>
    </div>

    {{-- Data grid --}}
    <div class="glass-panel overflow-hidden rounded-2xl shadow-lg">
        <div class="max-h-[65vh] overflow-auto">
            <table class="min-w-full divide-y divide-white/40 text-sm">
                <thead class="sticky top-0 z-10 bg-white/70 backdrop-blur-xl">
                    <tr>
                        @foreach ([
                            'name' => 'Nama Aset',
                            'category' => 'Kategori',
                            'owner' => 'Pemilik',
                            'criticality_level' => 'Kritikalitas',
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
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Terhubung</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody wire:loading wire:target="search,categoryFilter,criticalityFilter,showArchived,sortBy,previousPage,nextPage,gotoPage" class="divide-y divide-white/40">
                    <x-table-skeleton :columns="7" />
                </tbody>
                <tbody wire:loading.remove wire:target="search,categoryFilter,criticalityFilter,showArchived,sortBy,previousPage,nextPage,gotoPage" class="divide-y divide-white/40">
                    @forelse ($assets as $asset)
                        <tr
                            wire:key="asset-{{ $asset->id }}"
                            class="transition-all duration-200 hover:bg-white/60 hover:shadow-sm {{ $asset->trashed() ? 'opacity-60' : '' }}"
                            style="border-left: 3px solid {{ $asset->criticality_level?->accentHex() ?? 'transparent' }}"
                        >
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-900">{{ $asset->name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $asset->category?->label() }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $asset->owner ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @if ($asset->criticality_level)
                                    @php
                                        $isSevere = $asset->criticality_level->isSevere();
                                        $badgeClasses = $isSevere
                                            ? [
                                                'green' => 'bg-green-500 text-white shadow-sm shadow-green-500/40',
                                                'yellow' => 'bg-yellow-500 text-white shadow-sm shadow-yellow-500/40',
                                                'orange' => 'bg-orange-500 text-white shadow-sm shadow-orange-500/40',
                                                'red' => 'bg-red-500 text-white shadow-sm shadow-red-500/40',
                                            ][$asset->criticality_level->badgeColor()]
                                            : [
                                                'green' => 'bg-green-100 text-green-700',
                                                'yellow' => 'bg-yellow-100 text-yellow-700',
                                                'orange' => 'bg-orange-100 text-orange-700',
                                                'red' => 'bg-red-100 text-red-700',
                                            ][$asset->criticality_level->badgeColor()];
                                    @endphp
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold transition-transform duration-300 ease-spring hover:scale-110 {{ $badgeClasses }}">
                                        {{ $asset->criticality_level->label() }}
                                    </span>
                                @else
                                    &mdash;
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                                {{ $asset->status ?: '—' }}
                                @php
                                    $verificationBadge = [
                                        'menunggu_verifikasi' => ['label' => 'Menunggu Verifikasi', 'class' => 'bg-amber-100 text-amber-700'],
                                        'ditolak' => ['label' => 'Ditolak', 'class' => 'bg-red-100 text-red-700'],
                                    ][$asset->verification_status] ?? null;
                                @endphp
                                @if ($verificationBadge)
                                    <span class="mt-0.5 block w-fit rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $verificationBadge['class'] }}">
                                        {{ $verificationBadge['label'] }}
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @php
                                    $links = [
                                        ['count' => $asset->risks_count, 'icon' => 'o-exclamation-triangle', 'class' => 'bg-purple-100 text-purple-700'],
                                        ['count' => $asset->changes_count, 'icon' => 'o-arrow-path', 'class' => 'bg-indigo-100 text-indigo-700'],
                                        ['count' => $asset->data_information_records_count, 'icon' => 'o-circle-stack', 'class' => 'bg-sky-100 text-sky-700'],
                                        ['count' => $asset->knowledge_assets_count, 'icon' => 'o-light-bulb', 'class' => 'bg-amber-100 text-amber-700'],
                                        ['count' => $asset->services_count, 'icon' => 'o-wrench-screwdriver', 'class' => 'bg-rose-100 text-rose-700'],
                                        ['count' => $asset->security_programs_count, 'icon' => 'o-shield-check', 'class' => 'bg-emerald-100 text-emerald-700'],
                                    ];
                                @endphp
                                @if (collect($links)->sum('count') > 0)
                                    <div class="flex items-center gap-1">
                                        @foreach ($links as $link)
                                            @if ($link['count'] > 0)
                                                <span class="inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-[10px] font-medium transition-transform duration-300 ease-spring hover:scale-110 {{ $link['class'] }}">
                                                    <x-dynamic-component :component="'heroicon-' . $link['icon']" class="h-3 w-3" />
                                                    {{ $link['count'] }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button wire:click="viewDetail({{ $asset->id }})" title="Lihat Detail" class="rounded-full p-1.5 text-gray-500 transition-all duration-300 ease-spring hover:scale-125 hover:bg-sky-100 hover:text-sky-600 active:scale-90">
                                        <x-heroicon-o-eye class="h-4 w-4" />
                                    </button>

                                    @if (auth()->user()?->canWrite())
                                        @if (! $asset->trashed())
                                            <button
                                                wire:click="$dispatch('open-asset-form', { assetId: {{ $asset->id }} })"
                                                title="Ubah"
                                                class="rounded-full p-1.5 text-gray-500 transition-all duration-300 ease-spring hover:scale-125 hover:bg-sky-100 hover:text-sky-600 active:scale-90"
                                            >
                                                <x-heroicon-o-pencil-square class="h-4 w-4" />
                                            </button>
                                            <button
                                                x-on:click="if (await $store.confirm.ask('Arsipkan aset ini?', 'Data tidak akan dihapus dan dapat dipulihkan kembali.', 'danger')) $wire.archive({{ $asset->id }})"
                                                title="Arsipkan"
                                                class="rounded-full p-1.5 text-gray-500 transition-all duration-300 ease-spring hover:scale-125 hover:bg-red-100 hover:text-red-600 active:scale-90"
                                            >
                                                <x-heroicon-o-archive-box-x-mark class="h-4 w-4" />
                                            </button>
                                        @else
                                            <button wire:click="restore({{ $asset->id }})" title="Pulihkan" class="rounded-full p-1.5 text-gray-500 transition-all duration-300 ease-spring hover:scale-125 hover:bg-emerald-100 hover:text-emerald-600 active:scale-90">
                                                <x-heroicon-o-arrow-uturn-left class="h-4 w-4" />
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">
                                Tidak ada aset yang cocok dengan pencarian ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-white/40 px-4 py-3">
            {{ $assets->links() }}
        </div>
    </div>

    {{-- Detail slide-over: proves the JSONB anti-loss column round-trips
         cleanly back into a readable view, category-specific fields and all.
         @entangle syncs $detailOpen into Alpine so the panel can slide
         in/out instead of popping instantly. --}}
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
                @if ($viewingAsset)
                    <div class="flex items-center justify-between border-b border-white/40 px-5 py-4">
                        <div>
                            <p class="text-xs font-mono text-gray-400">{{ $viewingAsset->asset_code }}</p>
                            <h2 class="text-lg font-semibold text-gray-900">{{ $viewingAsset->name }}</h2>
                        </div>
                        <button wire:click="closeDetail" class="rounded-full p-1.5 text-gray-400 transition-all duration-300 ease-spring hover:rotate-90 hover:scale-110 hover:bg-white/60 hover:text-gray-600">
                            <x-heroicon-o-x-mark class="h-5 w-5" />
                        </button>
                    </div>

                    <dl class="divide-y divide-white/40 px-5">
                        @foreach ([
                            'Kategori' => $viewingAsset->category?->label(),
                            'Sub Klasifikasi' => $viewingAsset->sub_classification,
                            'Pemilik' => $viewingAsset->owner,
                            'Lokasi' => $viewingAsset->location,
                            'Status' => $viewingAsset->status,
                            'Kritikalitas' => $viewingAsset->criticality_level?->label(),
                            'Status Verifikasi' => [
                                'menunggu_verifikasi' => 'Menunggu Verifikasi',
                                'tervalidasi' => 'Tervalidasi',
                                'ditolak' => 'Ditolak',
                            ][$viewingAsset->verification_status] ?? $viewingAsset->verification_status,
                            'Tahun Referensi' => $viewingAsset->reference_year,
                            'Kode Legacy (Excel)' => $viewingAsset->legacy_code,
                        ] as $label => $value)
                            <div class="grid grid-cols-2 gap-2 py-2.5 text-sm">
                                <dt class="text-gray-500">{{ $label }}</dt>
                                <dd class="text-gray-900">{{ $value ?: '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    @if ($viewingAsset->verifications->isNotEmpty())
                        <div class="border-t border-white/40 px-5 py-4">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Riwayat Verifikasi</p>
                            <div class="space-y-2">
                                @foreach ($viewingAsset->verifications as $verification)
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

                    @if (! empty($viewingAsset->dynamic_data))
                        <div class="border-t border-white/40 px-5 py-4">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Data Dinamis (Spesifik Kategori)</p>
                            <dl class="divide-y divide-white/40">
                                @foreach ($viewingAsset->dynamic_data as $key => $value)
                                    <div class="grid grid-cols-2 gap-2 py-2.5 text-sm">
                                        <dt class="text-gray-500">{{ $key }}</dt>
                                        <dd class="break-words text-gray-900">{{ is_array($value) ? json_encode($value) : $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endif

                    {{-- Riwayat Terkait: the "benang merah" panel — every other
                         module that carries this asset's asset_id shows up
                         here, so the cross-module flow (Risiko → Perubahan →
                         Data Informasi → Pengetahuan) is visible from the
                         asset itself instead of only inside each module. --}}
                    <div class="border-t border-white/40 px-5 py-4">
                        <p class="mb-3 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-400">
                            <x-heroicon-o-link class="h-3.5 w-3.5" /> Riwayat Terkait
                        </p>

                        <div class="space-y-4">
                            <div>
                                <p class="mb-1.5 flex items-center gap-1.5 text-xs font-medium text-purple-600">
                                    <x-heroicon-o-exclamation-triangle class="h-3.5 w-3.5" /> Risiko ({{ $viewingAsset->risks->count() }})
                                </p>
                                @forelse ($viewingAsset->risks as $relatedRisk)
                                    <div class="mb-1 flex items-center justify-between rounded-lg bg-purple-50/70 px-3 py-2 text-xs transition-all duration-200 hover:translate-x-1 hover:bg-purple-100/70">
                                        <span class="truncate text-gray-700">{{ $relatedRisk->title }}</span>
                                        <span class="ml-2 shrink-0 font-mono text-purple-500">{{ $relatedRisk->risk_code }}</span>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-400">Belum ada risiko tercatat.</p>
                                @endforelse
                            </div>

                            <div>
                                <p class="mb-1.5 flex items-center gap-1.5 text-xs font-medium text-emerald-600">
                                    <x-heroicon-o-shield-check class="h-3.5 w-3.5" /> Keamanan Informasi ({{ $viewingAsset->securityPrograms->count() }})
                                </p>
                                @forelse ($viewingAsset->securityPrograms as $relatedProgram)
                                    <div class="mb-1 flex items-center justify-between rounded-lg bg-emerald-50/70 px-3 py-2 text-xs transition-all duration-200 hover:translate-x-1 hover:bg-emerald-100/70">
                                        <span class="truncate text-gray-700">{{ $relatedProgram->program_kerja }}</span>
                                        <span class="ml-2 shrink-0 font-mono text-emerald-500">{{ $relatedProgram->record_code }}</span>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-400">Belum ada program keamanan tercatat.</p>
                                @endforelse
                            </div>

                            <div>
                                <p class="mb-1.5 flex items-center gap-1.5 text-xs font-medium text-indigo-600">
                                    <x-heroicon-o-arrow-path class="h-3.5 w-3.5" /> Perubahan ({{ $viewingAsset->changes->count() }})
                                </p>
                                @forelse ($viewingAsset->changes as $relatedChange)
                                    <div class="mb-1 flex items-center justify-between rounded-lg bg-indigo-50/70 px-3 py-2 text-xs transition-all duration-200 hover:translate-x-1 hover:bg-indigo-100/70">
                                        <span class="truncate text-gray-700">{{ $relatedChange->title }}</span>
                                        <span class="ml-2 shrink-0 font-mono text-indigo-500">{{ $relatedChange->change_code }}</span>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-400">Belum ada perubahan tercatat.</p>
                                @endforelse
                            </div>

                            <div>
                                <p class="mb-1.5 flex items-center gap-1.5 text-xs font-medium text-sky-600">
                                    <x-heroicon-o-circle-stack class="h-3.5 w-3.5" /> Data & Informasi ({{ $viewingAsset->dataInformationRecords->count() }})
                                </p>
                                @forelse ($viewingAsset->dataInformationRecords as $relatedRecord)
                                    <div class="mb-1 flex items-center justify-between rounded-lg bg-sky-50/70 px-3 py-2 text-xs transition-all duration-200 hover:translate-x-1 hover:bg-sky-100/70">
                                        <span class="truncate text-gray-700">{{ $relatedRecord->title }}</span>
                                        <span class="ml-2 shrink-0 font-mono text-sky-500">{{ $relatedRecord->record_code }}</span>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-400">Belum ada catatan data terkait.</p>
                                @endforelse
                            </div>

                            <div>
                                <p class="mb-1.5 flex items-center gap-1.5 text-xs font-medium text-amber-600">
                                    <x-heroicon-o-light-bulb class="h-3.5 w-3.5" /> Pengetahuan ({{ $viewingAsset->knowledgeAssets->count() }})
                                </p>
                                @forelse ($viewingAsset->knowledgeAssets as $relatedDoc)
                                    <div class="mb-1 flex items-center justify-between rounded-lg bg-amber-50/70 px-3 py-2 text-xs transition-all duration-200 hover:translate-x-1 hover:bg-amber-100/70">
                                        <span class="truncate text-gray-700">{{ $relatedDoc->title }}</span>
                                        <span class="ml-2 shrink-0 font-mono text-amber-500">{{ $relatedDoc->record_code }}</span>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-400">Belum ada dokumentasi pengetahuan.</p>

                                    {{-- Menutup siklus Perubahan -> Pengetahuan dari flowchart: kalau
                                         aset ini sudah punya Perubahan tapi belum ada manual book-nya,
                                         ajak langsung buat, sudah tertaut ke aset ini. --}}
                                    @if ($viewingAsset->changes->isNotEmpty())
                                        <a
                                            href="{{ route('knowledge.index', ['create' => 1, 'asset_id' => $viewingAsset->id]) }}"
                                            class="mt-1.5 flex items-center gap-1.5 rounded-lg border border-dashed border-amber-300 bg-amber-50/50 px-3 py-2 text-xs font-medium text-amber-700 transition-all duration-300 ease-spring hover:translate-x-1 hover:bg-amber-100"
                                        >
                                            <x-heroicon-o-sparkles class="h-3.5 w-3.5" />
                                            Ada {{ $viewingAsset->changes->count() }} perubahan tercatat — buat dokumentasi pengetahuannya?
                                        </a>
                                    @endif
                                @endforelse
                            </div>

                            <div>
                                <p class="mb-1.5 flex items-center gap-1.5 text-xs font-medium text-rose-600">
                                    <x-heroicon-o-wrench-screwdriver class="h-3.5 w-3.5" /> Layanan ({{ $viewingAsset->services->count() }})
                                </p>
                                @forelse ($viewingAsset->services as $relatedService)
                                    <div class="mb-1 flex items-center justify-between rounded-lg bg-rose-50/70 px-3 py-2 text-xs transition-all duration-200 hover:translate-x-1 hover:bg-rose-100/70">
                                        <span class="truncate text-gray-700">{{ $relatedService->name }}</span>
                                        <span class="ml-2 shrink-0 font-mono text-rose-500">{{ $relatedService->service_code }}</span>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-400">Belum ada layanan yang bergantung pada aset ini.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Toast --}}
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
