<div class="space-y-4">
    <div class="glass-panel flex flex-col gap-3 rounded-2xl p-4 shadow-lg md:flex-row md:items-center md:justify-center md:gap-6">
        <div class="relative w-full md:max-w-sm">
            <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari judul, OPD, kode..."
                class="w-full rounded-full border-slate-200 bg-white pl-9 text-sm focus:border-brand-400 focus:bg-white focus:ring-brand-400"
            >
        </div>
        <select wire:model.live="moduleFilter" class="rounded-full border-slate-200 bg-white text-sm focus:border-brand-400 focus:ring-brand-400">
            <option value="">Semua Modul</option>
            @foreach ($modules as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="glass-panel overflow-hidden rounded-2xl shadow-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-white">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Dokumen</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Modul</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Klaim</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Hasil</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Skor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Keputusan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($records as $record)
                        @php
                            $accent = $record->meetsTarget() ? '#183f70' : ($record->achieved_level > 0 ? '#5582bd' : '#94a3b8');
                        @endphp
                        <tr
                            wire:key="evidence-{{ $record->id }}"
                            wire:click="$dispatch('open-evidence-detail', { id: {{ $record->id }} })"
                            class="cursor-pointer transition-all duration-200 hover:bg-slate-50"
                            style="border-left: 3px solid {{ $accent }}"
                        >
                            <td class="max-w-sm px-4 py-3">
                                <p class="truncate font-semibold text-gray-900">{{ $record->title }}</p>
                                <p class="truncate text-xs text-gray-500">
                                    <span class="font-mono">{{ $record->record_code }}</span>
                                    @if ($record->unit_name) &middot; {{ $record->unit_name }} @endif
                                    &middot; {{ $record->created_at->format('d M Y') }}
                                </p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-700">{{ $record->moduleLabel() }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-700">Level {{ $record->target_level }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold" style="background-color: {{ $accent }}22; color: {{ $accent }}">
                                    {{ $record->achieved_level > 0 ? 'Level ' . $record->achieved_level : 'Belum memenuhi' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-800">{{ $record->score }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                                {{ $record->reviewer_decision ? \App\Models\EvidenceDocument::DECISIONS[$record->reviewer_decision] : 'Menunggu asesor' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-500">Belum ada bukti dukung yang diunggah.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">{{ $records->links() }}</div>
    </div>
</div>
