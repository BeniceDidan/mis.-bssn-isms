<div class="space-y-4">
    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        @foreach (config('helpdesk.priorities') as $code => $p)
            <div class="glass-panel rounded-2xl p-4 shadow-lg" style="border-left: 3px solid {{ $p['color'] }}">
                <p class="text-xs text-gray-500">{{ $p['label'] }} terbuka</p>
                <p class="text-2xl font-bold text-gray-900">{{ $openCounts[$code] ?? 0 }}</p>
                <p class="text-[11px] text-gray-400">tanggapan ≤ {{ $p['response'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="glass-panel flex flex-col gap-3 rounded-2xl p-4 shadow-lg md:flex-row md:items-center md:justify-center md:gap-4">
        <div class="relative w-full md:max-w-sm">
            <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari isi tiket, pemohon, kode..." class="w-full rounded-full border-white/60 bg-white/50 pl-9 text-sm focus:border-sky-400 focus:bg-white/90 focus:ring-sky-400">
        </div>
        <select wire:model.live="statusFilter" class="rounded-full border-white/60 bg-white/50 text-sm focus:border-sky-400 focus:ring-sky-400">
            <option value="terbuka">Terbuka</option>
            <option value="selesai">Selesai / ditutup</option>
            <option value="">Semua status</option>
        </select>
        <select wire:model.live="categoryFilter" class="rounded-full border-white/60 bg-white/50 text-sm focus:border-sky-400 focus:ring-sky-400">
            <option value="">Semua kategori</option>
            @foreach ($categories as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="glass-panel overflow-hidden rounded-2xl shadow-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/40 text-sm">
                <thead class="bg-white/70">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Prioritas</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Tiket</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Kategori</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Layanan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/40">
                    @forelse ($tickets as $ticket)
                        @php $p = $ticket->priorityInfo(); @endphp
                        <tr
                            wire:key="ticket-{{ $ticket->id }}"
                            @if (auth()->user()?->canWrite()) @click="$dispatch('open-service-ticket-form', { ticketId: {{ $ticket->id }} })" @endif
                            class="cursor-pointer transition-all duration-200 hover:bg-white/60"
                            style="border-left: 3px solid {{ $p['color'] ?? 'transparent' }}"
                        >
                            <td class="whitespace-nowrap px-4 py-3">
                                @if ($p)
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold text-white" style="background-color: {{ $p['color'] }}">{{ $ticket->priority }}</span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="max-w-md px-4 py-3">
                                <p class="truncate text-gray-900">{{ $ticket->issue }}</p>
                                <p class="text-xs text-gray-500">
                                    <span class="font-mono">{{ $ticket->label() }}</span>
                                    @if ($ticket->requester_name) &middot; {{ $ticket->requester_name }} @endif
                                    @if ($ticket->reported_at) &middot; {{ $ticket->reported_at->format('d M Y') }} @endif
                                </p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-700">{{ $ticket->categoryLabel() ?? '—' }}</td>
                            <td class="max-w-[12rem] truncate px-4 py-3 text-gray-700">{{ $ticket->service?->name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ ucfirst((string) $ticket->status) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-sm text-gray-500">Tidak ada tiket.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">{{ $tickets->links() }}</div>
    </div>
</div>
