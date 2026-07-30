<?php

namespace App\Livewire\SecurityPrograms;

use App\Enums\Level;
use App\Livewire\Concerns\DedupesActivityFeed;
use App\Models\Asset;
use App\Models\KnowledgeActivity;
use App\Models\Risk;
use App\Models\SecurityProgram;
use Livewire\Attributes\On;
use Livewire\Component;

class SecurityProgramStats extends Component
{
    use DedupesActivityFeed;

    #[On('security-program-saved')]
    #[On('asset-saved')]
    #[On('risk-saved')]
    #[On('knowledge-activity-saved')]
    public function refresh(): void
    {
        // no-op
    }

    /**
     * The 5 program-kerja items describe work the OTHER modules already do
     * (item 3 is literally "inventarisasi aset & identifikasi risiko" —
     * Aset + Risiko; item 5 is "sosialisasi/pelatihan" — Pengetahuan's
     * Aktivitas Berbagi). This module has no per-item asset_id, but it's
     * meant as an oversight layer over that work, not a silo — so its
     * dashboard pulls live counts from those modules as evidence the plan
     * is actually being executed, not just written down.
     */
    public function render()
    {
        return view('livewire.security-programs.security-program-stats', [
            'totalRecords' => SecurityProgram::count(),
            'assetCount' => Asset::count(),
            'riskCount' => Risk::count(),
            'severeRiskCount' => Risk::whereIn('risk_level', [Level::Tinggi->value, Level::Kritis->value])->count(),
            'sharingActivityCount' => KnowledgeActivity::count(),
            'recentActivity' => $this->dedupedRecentActivity('security_program'),
        ]);
    }
}
