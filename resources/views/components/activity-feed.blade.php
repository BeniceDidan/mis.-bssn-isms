@props(['items', 'title' => 'Aktivitas terbaru'])

{{-- Daftar aktivitas terbaru di halaman modul. Kejadian yang datang dari
     modul lain (bridge_module, lihat BridgesToAsset, HrRisk, Asset, Service,
     RiskEscalationResponseService) ditulis "dari Manajemen …" supaya alur
     antarmodul terlihat. --}}
@php
    $actions = ['created' => 'Dibuat', 'updated' => 'Diubah', 'archived' => 'Diarsipkan', 'deleted' => 'Dihapus', 'restored' => 'Dipulihkan'];
    $moduleName = fn (?string $m) => $m === null ? null : (str_starts_with($m, 'Manajemen') ? $m : 'Manajemen ' . $m);
@endphp

@if ($items->isNotEmpty())
    {{-- Tiga teratas saja supaya halaman tidak penuh (masukan 26 Nov 2026); sisanya bisa dibuka. --}}
    <div class="glass-panel rounded-2xl" x-data="{ all: false }">
        <h2 class="border-b border-slate-200 px-5 py-3 text-sm font-semibold text-gray-900">{{ $title }}</h2>
        <ul class="divide-y divide-slate-200">
            @foreach ($items as $activity)
                @php
                    $new = $activity->new_values ?? [];
                    $old = $activity->old_values ?? [];
                    $model = $activity->loggable;
                    $from = $moduleName($new['bridge_module'] ?? null);
                    $label = $new['bridge_note']
                        ?? $model?->title ?? $model?->name ?? $model?->subject ?? $model?->program_kerja
                        ?? $old['title'] ?? $old['name'] ?? $old['subject'] ?? $old['program_kerja']
                        ?? 'Data yang sudah diarsipkan';
                @endphp
                <li class="flex items-start justify-between gap-4 px-5 py-2.5 text-sm" @if ($loop->index >= 3) x-show="all" x-cloak @endif>
                    <div class="min-w-0">
                        <p class="truncate text-gray-800">{{ \Illuminate\Support\Str::limit($label, 120) }}</p>
                        <p class="text-xs text-gray-500">{{ $actions[$activity->action_type] ?? $activity->action_type }}@if ($from) &middot; dari {{ $from }}@endif</p>
                    </div>
                    <span class="shrink-0 pt-0.5 text-xs text-gray-400">{{ $activity->performed_at?->diffForHumans() }}</span>
                </li>
            @endforeach
            @if ($items->count() > 3)
                <li x-show="! all">
                    <button type="button" @click="all = true" class="w-full px-5 py-2 text-left text-xs font-medium text-brand-700 hover:bg-slate-50">{{ $items->count() - 3 }} lainnya</button>
                </li>
            @endif
        </ul>
    </div>
@endif
