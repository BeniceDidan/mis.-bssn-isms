<div class="space-y-4">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div
            x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 0)"
            x-show="shown"
            x-transition:enter="transition ease-spring duration-500"
            x-transition:enter-start="opacity-0 translate-y-6 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-brand-700 p-5 text-white shadow-lg transition-all duration-300 ease-spring hover:-translate-y-2 hover:scale-[1.02] hover:shadow-2xl"
        >
            <x-heroicon-o-wrench-screwdriver class="pointer-events-none absolute -right-4 -top-4 h-28 w-28 text-white/20 transition-transform duration-500 ease-spring group-hover:scale-125 group-hover:rotate-12" />
            <p class="text-3xl font-bold">{{ number_format($totalRecords) }}</p>
            <p class="mt-1 text-sm text-brand-50">Total Layanan</p>
        </div>

        <div
            x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 80)"
            x-show="shown"
            x-transition:enter="transition ease-spring duration-500"
            x-transition:enter-start="opacity-0 translate-y-6 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-brand-700 p-5 text-white shadow-lg transition-all duration-300 ease-spring hover:-translate-y-2 hover:scale-[1.02] hover:shadow-2xl"
        >
            <x-heroicon-o-exclamation-circle class="pointer-events-none absolute -right-4 -top-4 h-28 w-28 text-white/20 transition-transform duration-500 ease-spring group-hover:scale-125 group-hover:rotate-12" />
            <p class="text-3xl font-bold">{{ number_format($openTickets) }}</p>
            <p class="mt-1 text-sm text-brand-50">Tiket Belum Selesai</p>
        </div>
    </div>

    @if ($recentActivity->isNotEmpty())
        <div class="glass-panel rounded-2xl shadow-lg">
            <div class="border-b border-slate-200 px-5 py-3">
                <h2 class="text-sm font-semibold text-gray-900">Aktivitas Terbaru</h2>
            </div>
            <div class="divide-y divide-slate-200">
                @foreach ($recentActivity as $activity)
                    @php
                        $actionLabels = [
                            'created' => ['label' => 'dibuat', 'class' => 'bg-brand-100 text-brand-700'],
                            'updated' => ['label' => 'diperbarui', 'class' => 'bg-brand-100 text-brand-700'],
                            'archived' => ['label' => 'diarsipkan', 'class' => 'bg-brand-100 text-brand-800'],
                            'deleted' => ['label' => 'dihapus', 'class' => 'bg-red-50 text-red-700'],
                        ][$activity->action_type] ?? ['label' => $activity->action_type, 'class' => 'bg-gray-100 text-gray-700'];
                    @endphp
                    <div class="flex items-center justify-between px-5 py-2.5 text-sm transition-all duration-200 hover:translate-x-1 hover:bg-slate-50">
                        <div class="flex items-center gap-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $actionLabels['class'] }}">{{ $actionLabels['label'] }}</span>
                            <span class="text-gray-700">{{ $activity->loggable?->name ?? "#{$activity->loggable_id}" }}</span>
                        </div>
                        <span class="text-xs text-gray-400">{{ $activity->performed_at->diffForHumans() }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
