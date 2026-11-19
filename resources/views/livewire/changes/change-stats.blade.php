<div class="space-y-4">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="glass-panel rounded-2xl px-5 py-4">
            <p class="text-2xl font-semibold text-gray-900">{{ number_format($totalChanges) }}</p>
            <p class="text-sm text-gray-500">Total Perubahan</p>
        </div>

        <div class="glass-panel rounded-2xl px-5 py-4">
            <p class="text-2xl font-semibold text-gray-900">{{ number_format($highRiskChanges) }}</p>
            <p class="text-sm text-gray-500">Risiko Inheren Tinggi</p>
        </div>
    </div>

    <x-activity-feed :items="$recentActivity" title="Aktivitas perubahan terbaru" />
</div>
