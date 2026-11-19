<div class="space-y-4">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="glass-panel rounded-2xl px-5 py-4">
            <p class="text-2xl font-semibold text-gray-900">{{ number_format($totalAssets) }}</p>
            <p class="text-sm text-gray-500">Total Aset</p>
        </div>

        <div class="glass-panel rounded-2xl px-5 py-4">
            <p class="text-2xl font-semibold text-gray-900">{{ number_format($highCriticalityAssets) }}</p>
            <p class="text-sm text-gray-500">Aset Tinggi/Kritis</p>
        </div>

        <div class="glass-panel rounded-2xl px-5 py-4">
            @php $total = $assetsByCriticality->sum(); @endphp
            <p class="mb-2 text-sm text-gray-500">Per kritikalitas</p>
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

    <x-activity-feed :items="$recentActivity" title="Aktivitas aset terbaru" />
</div>
