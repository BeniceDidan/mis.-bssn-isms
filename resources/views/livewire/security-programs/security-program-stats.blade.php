<div class="space-y-4">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="glass-panel rounded-2xl px-5 py-4">
            <p class="text-2xl font-semibold text-gray-900">{{ number_format($totalRecords) }}</p>
            <p class="text-sm text-gray-500">Total Program Kerja</p>
        </div>
    </div>

    {{-- This module has no per-item asset_id (the source data is a
         work-plan, not per-asset records), but its 5 program items
         literally describe work the other modules already do — so instead
         of a fake link, this pulls live counts as evidence the plan is
         actually being carried out. --}}
    <div class="glass-panel rounded-2xl px-5 py-4">
        <h2 class="text-sm font-semibold text-gray-900">Dihitung otomatis dari modul lain</h2>
        <dl class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                <dd class="text-xl font-semibold text-gray-900">{{ number_format($assetCount) }}</dd>
                <dt class="text-xs text-gray-500">Aset terdaftar</dt>
            </div>
            <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                <dd class="text-xl font-semibold text-gray-900">{{ number_format($riskCount) }}</dd>
                <dt class="text-xs text-gray-500">Risiko tercatat</dt>
            </div>
            <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                <dd class="text-xl font-semibold {{ $severeRiskCount > 0 ? 'text-red-700' : 'text-gray-900' }}">{{ number_format($severeRiskCount) }}</dd>
                <dt class="text-xs text-gray-500">Risiko Tinggi/Kritis</dt>
            </div>
            <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                <dd class="text-xl font-semibold text-gray-900">{{ number_format($sharingActivityCount) }}</dd>
                <dt class="text-xs text-gray-500">Sosialisasi &amp; pelatihan</dt>
            </div>
        </dl>
        <p class="mt-3 text-xs text-gray-500">Dari Manajemen Aset, Manajemen Risiko, dan Aktivitas Berbagi di Manajemen Pengetahuan.</p>
    </div>

    <x-activity-feed :items="$recentActivity" title="Aktivitas terbaru" />
</div>
