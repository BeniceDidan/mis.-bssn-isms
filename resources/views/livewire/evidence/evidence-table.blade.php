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
                    <tr class="text-left text-xs font-medium text-gray-500">
                        <th class="px-4 py-3 font-medium">Dokumen</th>
                        <th class="px-4 py-3 font-medium">Klaim</th>
                        <th class="px-4 py-3 font-medium">Hasil</th>
                        <th class="px-4 py-3 font-medium">Skor</th>
                        <th class="px-4 py-3 font-medium">Keputusan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($records as $record)
                        <tr
                            wire:key="evidence-{{ $record->id }}"
                            wire:click="$dispatch('open-evidence-detail', { id: {{ $record->id }} })"
                            class="cursor-pointer hover:bg-slate-50"
                        >
                            <td class="max-w-sm px-4 py-3">
                                <p class="truncate font-medium text-gray-900">{{ $record->title }}</p>
                                <p class="truncate text-xs text-gray-500">{{ $record->moduleLabel() }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-700">Level {{ $record->target_level }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium {{ $record->meetsTarget() ? 'text-brand-700' : 'text-gray-900' }}">
                                {{ $record->achieved_level > 0 ? 'Level ' . $record->achieved_level : '—' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-700">{{ $record->score }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-500">
                                {{ $record->reviewer_decision ? \App\Models\EvidenceDocument::DECISIONS[$record->reviewer_decision] : 'Menunggu' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-sm text-gray-500">Belum ada bukti dukung.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">{{ $records->links() }}</div>
    </div>
</div>
