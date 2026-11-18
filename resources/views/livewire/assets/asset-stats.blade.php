<div class="space-y-4">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div
            x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 0)"
            x-show="shown"
            x-transition:enter="transition ease-spring duration-500"
            x-transition:enter-start="opacity-0 translate-y-6 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-brand-700 p-5 text-white shadow-lg transition-all duration-300 ease-spring hover:-translate-y-2 hover:scale-[1.02] hover:shadow-2xl"
        >
            <x-heroicon-o-archive-box class="pointer-events-none absolute -right-4 -top-4 h-28 w-28 text-white/20 transition-transform duration-500 ease-spring group-hover:scale-125 group-hover:rotate-12" />
            <p class="text-3xl font-bold">{{ number_format($totalAssets) }}</p>
            <p class="mt-1 text-sm text-brand-50">Total Aset</p>
        </div>

        <div
            x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 80)"
            x-show="shown"
            x-transition:enter="transition ease-spring duration-500"
            x-transition:enter-start="opacity-0 translate-y-6 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-brand-700 p-5 text-white shadow-lg transition-all duration-300 ease-spring hover:-translate-y-2 hover:scale-[1.02] hover:shadow-2xl"
        >
            <x-heroicon-o-exclamation-triangle class="pointer-events-none absolute -right-4 -top-4 h-28 w-28 text-white/20 transition-transform duration-500 ease-spring group-hover:scale-125 group-hover:rotate-12" />
            <p class="text-3xl font-bold">{{ number_format($highCriticalityAssets) }}</p>
            <p class="mt-1 text-sm text-brand-50">Aset Kritikalitas Tinggi</p>
        </div>

        <div class="glass-panel rounded-2xl p-4 shadow-lg">
            @php $total = $assetsByCriticality->sum(); @endphp
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Berdasarkan Kritikalitas</p>
            @if ($total > 0)
                <ul class="space-y-1 text-sm">
                    @foreach ($levels as $level)
                        @php $count = $assetsByCriticality[$level->value] ?? 0; @endphp
                        @if ($count > 0)
                            <li class="flex items-center justify-between">
                                <span class="flex items-center gap-2 text-gray-600">
                                    <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $level->accentHex() }}"></span>
                                    {{ $level->label() }}
                                </span>
                                <span class="font-medium text-gray-900">{{ $count }}</span>
                            </li>
                        @endif
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-400">Belum ada data.</p>
            @endif
        </div>
    </div>

    @if ($recentActivity->isNotEmpty())
        <div class="glass-panel rounded-2xl shadow-lg">
            <div class="border-b border-slate-200 px-5 py-3">
                <h2 class="text-sm font-semibold text-gray-900">Aktivitas Aset Terbaru</h2>
            </div>
            <div class="divide-y divide-slate-200">
                @foreach ($recentActivity as $activity)
                    @php
                        $actionLabels = [
                            'created' => ['label' => 'dibuat', 'class' => 'bg-brand-100 text-brand-700'],
                            'updated' => ['label' => 'diperbarui', 'class' => 'bg-brand-100 text-brand-700'],
                            'archived' => ['label' => 'diarsipkan', 'class' => 'bg-brand-100 text-brand-800'],
                            'deleted' => ['label' => 'dihapus', 'class' => 'bg-red-50 text-red-700'],
                            'restored' => ['label' => 'dipulihkan', 'class' => 'bg-brand-100 text-brand-700'],
                        ][$activity->action_type] ?? ['label' => $activity->action_type, 'class' => 'bg-gray-100 text-gray-700'];
                        $bridgeModule = $activity->new_values['bridge_module'] ?? null;
                        $bridgeNote = $activity->new_values['bridge_note'] ?? null;
                    @endphp
                    <div class="flex items-center justify-between px-5 py-2.5 text-sm transition-all duration-200 hover:translate-x-1 hover:bg-slate-50">
                        <div class="flex items-center gap-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $actionLabels['class'] }}">{{ $actionLabels['label'] }}</span>
                            @if ($bridgeModule)
                                <span class="rounded-full bg-brand-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-brand-700">{{ $bridgeModule }}</span>
                            @endif
                            <span class="text-gray-700">{{ $bridgeNote ?? ($activity->loggable?->name ?? "#{$activity->loggable_id}") }}</span>
                        </div>
                        <span class="text-xs text-gray-400">{{ $activity->performed_at->diffForHumans() }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
