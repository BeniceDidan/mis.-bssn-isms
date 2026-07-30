<div class="space-y-4">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div
            x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 0)"
            x-show="shown"
            x-transition:enter="transition ease-spring duration-500"
            x-transition:enter-start="opacity-0 translate-y-6 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            class="group relative overflow-hidden rounded-2xl border border-white/30 bg-gradient-to-br from-emerald-400/90 to-emerald-600/90 p-5 text-white shadow-lg shadow-emerald-900/20 backdrop-blur-xl transition-all duration-300 ease-spring hover:-translate-y-2 hover:scale-[1.02] hover:shadow-2xl"
        >
            <x-heroicon-o-shield-check class="pointer-events-none absolute -right-4 -top-4 h-28 w-28 text-white/20 transition-transform duration-500 ease-spring group-hover:scale-125 group-hover:rotate-12" />
            <p class="text-3xl font-bold">{{ number_format($totalRecords) }}</p>
            <p class="mt-1 text-sm text-emerald-50">Total Program Kerja</p>
        </div>
    </div>

    {{-- This module has no per-item asset_id (the source data is a
         work-plan, not per-asset records), but its 5 program items
         literally describe work the other modules already do — so instead
         of a fake link, this pulls live counts as evidence the plan is
         actually being carried out. --}}
    <div class="glass-panel rounded-2xl p-5 shadow-lg">
        <p class="mb-3 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-400">
            <x-heroicon-o-link class="h-3.5 w-3.5" /> Bukti Pelaksanaan Lintas Modul
        </p>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-xl bg-teal-50 p-3 text-center">
                <p class="text-xl font-bold text-teal-700">{{ number_format($assetCount) }}</p>
                <p class="text-[11px] text-teal-600">Aset Terinventarisasi</p>
            </div>
            <div class="rounded-xl bg-purple-50 p-3 text-center">
                <p class="text-xl font-bold text-purple-700">{{ number_format($riskCount) }}</p>
                <p class="text-[11px] text-purple-600">Risiko Teridentifikasi</p>
            </div>
            <div class="rounded-xl bg-red-50 p-3 text-center">
                <p class="text-xl font-bold text-red-700">{{ number_format($severeRiskCount) }}</p>
                <p class="text-[11px] text-red-600">Risiko Tinggi/Kritis</p>
            </div>
            <div class="rounded-xl bg-amber-50 p-3 text-center">
                <p class="text-xl font-bold text-amber-700">{{ number_format($sharingActivityCount) }}</p>
                <p class="text-[11px] text-amber-600">Sosialisasi &amp; Pelatihan</p>
            </div>
        </div>
        <p class="mt-3 text-[11px] text-gray-400">
            Item 3 ("Pelaksanaan program kerja SPBE") ditopang oleh data Aset &amp; Risiko; item 5 ("Sosialisasi, pelatihan, bimbingan") ditopang oleh Aktivitas Berbagi di Manajemen Pengetahuan.
        </p>
    </div>

    @if ($recentActivity->isNotEmpty())
        <div class="glass-panel rounded-2xl shadow-lg">
            <div class="border-b border-white/40 px-5 py-3">
                <h2 class="text-sm font-semibold text-gray-900">Aktivitas Terbaru</h2>
            </div>
            <div class="divide-y divide-white/40">
                @foreach ($recentActivity as $activity)
                    @php
                        $actionLabels = [
                            'created' => ['label' => 'dibuat', 'class' => 'bg-emerald-100 text-emerald-700'],
                            'updated' => ['label' => 'diperbarui', 'class' => 'bg-sky-100 text-sky-700'],
                            'archived' => ['label' => 'diarsipkan', 'class' => 'bg-orange-100 text-orange-700'],
                            'deleted' => ['label' => 'dihapus', 'class' => 'bg-red-100 text-red-700'],
                        ][$activity->action_type] ?? ['label' => $activity->action_type, 'class' => 'bg-gray-100 text-gray-700'];
                    @endphp
                    <div class="flex items-center justify-between px-5 py-2.5 text-sm transition-all duration-200 hover:translate-x-1 hover:bg-white/50">
                        <div class="flex items-center gap-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $actionLabels['class'] }}">{{ $actionLabels['label'] }}</span>
                            <span class="text-gray-700">{{ \Illuminate\Support\Str::limit($activity->loggable?->program_kerja ?? "#{$activity->loggable_id}", 40) }}</span>
                        </div>
                        <span class="text-xs text-gray-400">{{ $activity->performed_at->diffForHumans() }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
