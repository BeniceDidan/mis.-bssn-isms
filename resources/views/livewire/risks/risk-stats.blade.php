<div class="space-y-4">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="glass-panel rounded-2xl px-5 py-4">
            <p class="text-2xl font-semibold text-gray-900">{{ number_format($totalRisks) }}</p>
            <p class="text-sm text-gray-500">Total Risiko</p>
        </div>

        <div class="glass-panel rounded-2xl px-5 py-4">
            <p class="text-2xl font-semibold text-gray-900">{{ number_format($openRisks) }}</p>
            <p class="text-sm text-gray-500">Risiko Terbuka</p>
        </div>
    </div>

    <x-activity-feed :items="$recentActivity" title="Aktivitas risiko terbaru" />
</div>
