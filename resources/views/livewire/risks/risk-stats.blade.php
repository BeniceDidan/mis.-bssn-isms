<div class="space-y-4">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div
            x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 0)"
            x-show="shown"
            x-transition:enter="transition ease-spring duration-500"
            x-transition:enter-start="opacity-0 translate-y-6 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            class="group relative overflow-hidden rounded-2xl border border-white/30 bg-gradient-to-br from-purple-400/90 to-purple-600/90 p-5 text-white shadow-lg shadow-purple-900/20 backdrop-blur-xl transition-all duration-300 ease-spring hover:-translate-y-2 hover:scale-[1.02] hover:shadow-2xl"
        >
            <x-heroicon-o-shield-exclamation class="pointer-events-none absolute -right-4 -top-4 h-28 w-28 text-white/20 transition-transform duration-500 ease-spring group-hover:scale-125 group-hover:rotate-12" />
            <p class="text-3xl font-bold">{{ number_format($totalRisks) }}</p>
            <p class="mt-1 text-sm text-purple-50">Total Risiko</p>
        </div>

        <div
            x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 80)"
            x-show="shown"
            x-transition:enter="transition ease-spring duration-500"
            x-transition:enter-start="opacity-0 translate-y-6 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            class="group relative overflow-hidden rounded-2xl border border-white/30 bg-gradient-to-br from-red-400/90 to-red-600/90 p-5 text-white shadow-lg shadow-red-900/20 backdrop-blur-xl transition-all duration-300 ease-spring hover:-translate-y-2 hover:scale-[1.02] hover:shadow-2xl"
        >
            <x-heroicon-o-fire class="pointer-events-none absolute -right-4 -top-4 h-28 w-28 text-white/20 transition-transform duration-500 ease-spring group-hover:scale-125 group-hover:rotate-12" />
            <p class="text-3xl font-bold">{{ number_format($openRisks) }}</p>
            <p class="mt-1 text-sm text-red-50">Risiko Terbuka</p>
        </div>
    </div>

    @if ($recentActivity->isNotEmpty())
        <div class="glass-panel rounded-2xl shadow-lg">
            <div class="border-b border-white/40 px-5 py-3">
                <h2 class="text-sm font-semibold text-gray-900">Aktivitas Risiko Terbaru</h2>
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
                        $bridgeModule = $activity->new_values['bridge_module'] ?? null;
                        $bridgeNote = $activity->new_values['bridge_note'] ?? null;
                    @endphp
                    <div class="flex items-center justify-between px-5 py-2.5 text-sm transition-all duration-200 hover:translate-x-1 hover:bg-white/50">
                        <div class="flex items-center gap-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $actionLabels['class'] }}">{{ $actionLabels['label'] }}</span>
                            @if ($bridgeModule)
                                <span class="rounded-full bg-teal-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-teal-700">{{ $bridgeModule }}</span>
                            @endif
                            <span class="text-gray-700">{{ $bridgeNote ?? ($activity->loggable?->title ?? "#{$activity->loggable_id}") }}</span>
                        </div>
                        <span class="text-xs text-gray-400">{{ $activity->performed_at->diffForHumans() }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
