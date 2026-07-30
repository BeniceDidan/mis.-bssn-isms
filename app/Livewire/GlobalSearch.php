<?php

namespace App\Livewire;

use App\Models\Asset;
use App\Models\Change;
use App\Models\DataInformation;
use App\Models\HrRisk;
use App\Models\KnowledgeActivity;
use App\Models\KnowledgeAsset;
use App\Models\KnowledgeExpert;
use App\Models\KnowledgeRisk;
use App\Models\Risk;
use App\Models\SecurityProgram;
use App\Models\Service;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Powers the topbar search box — was a plain unwired <input> before (see
 * the "Phase 2" comment it replaced). Each result links back to the
 * matching module's own table with ?search=<code> pre-filled, reusing the
 * #[Url] $search binding every *Table component already has, instead of
 * inventing a separate detail-view route per module.
 */
class GlobalSearch extends Component
{
    public string $query = '';

    private const PER_MODULE_LIMIT = 4;

    /**
     * Called directly from the view ($this->groups()) rather than exposed
     * as a Livewire #[Computed] property — plain and version-proof, and
     * this only renders once per request anyway.
     */
    public function groups(): array
    {
        $term = trim($this->query);

        if (mb_strlen($term) < 2) {
            return [];
        }

        $like = '%'.$term.'%';
        $groups = [];

        $assets = Asset::query()
            ->where(fn ($q) => $q->where('name', 'ilike', $like)->orWhere('asset_code', 'ilike', $like))
            ->limit(self::PER_MODULE_LIMIT)->get();
        if ($assets->isNotEmpty()) {
            $groups[] = [
                'label' => 'Aset', 'icon' => 'o-archive-box', 'color' => 'teal',
                'items' => $assets->map(fn ($r) => [
                    'title' => $r->name, 'meta' => $r->asset_code,
                    'url' => route('assets.index', ['search' => $r->asset_code]),
                ]),
            ];
        }

        $risks = Risk::query()
            ->where(fn ($q) => $q->where('title', 'ilike', $like)->orWhere('risk_code', 'ilike', $like))
            ->limit(self::PER_MODULE_LIMIT)->get();
        if ($risks->isNotEmpty()) {
            $groups[] = [
                'label' => 'Risiko', 'icon' => 'o-exclamation-triangle', 'color' => 'purple',
                'items' => $risks->map(fn ($r) => [
                    'title' => $r->title, 'meta' => $r->risk_code,
                    'url' => route('risks.index', ['search' => $r->risk_code]),
                ]),
            ];
        }

        $changes = Change::query()
            ->where(fn ($q) => $q->where('title', 'ilike', $like)->orWhere('change_code', 'ilike', $like))
            ->limit(self::PER_MODULE_LIMIT)->get();
        if ($changes->isNotEmpty()) {
            $groups[] = [
                'label' => 'Perubahan', 'icon' => 'o-arrow-path', 'color' => 'indigo',
                'items' => $changes->map(fn ($r) => [
                    'title' => $r->title, 'meta' => $r->change_code,
                    'url' => route('changes.index', ['search' => $r->change_code]),
                ]),
            ];
        }

        $dataInformation = DataInformation::query()
            ->where(fn ($q) => $q->where('title', 'ilike', $like)->orWhere('record_code', 'ilike', $like))
            ->limit(self::PER_MODULE_LIMIT)->get();
        if ($dataInformation->isNotEmpty()) {
            $groups[] = [
                'label' => 'Data & Informasi', 'icon' => 'o-circle-stack', 'color' => 'sky',
                'items' => $dataInformation->map(fn ($r) => [
                    'title' => $r->title, 'meta' => $r->record_code,
                    'url' => route('data-information.index', ['search' => $r->record_code]),
                ]),
            ];
        }

        $hrRisks = HrRisk::query()
            ->where(fn ($q) => $q->where('title', 'ilike', $like)->orWhere('subject', 'ilike', $like)->orWhere('record_code', 'ilike', $like))
            ->limit(self::PER_MODULE_LIMIT)->get();
        if ($hrRisks->isNotEmpty()) {
            $groups[] = [
                'label' => 'SDM', 'icon' => 'o-users', 'color' => 'slate',
                'items' => $hrRisks->map(fn ($r) => [
                    'title' => $r->title, 'meta' => $r->subject,
                    'url' => route('hr-risks.index', ['search' => $r->record_code]),
                ]),
            ];
        }

        $services = Service::query()
            ->where(fn ($q) => $q->where('name', 'ilike', $like)->orWhere('service_code', 'ilike', $like))
            ->limit(self::PER_MODULE_LIMIT)->get();
        if ($services->isNotEmpty()) {
            $groups[] = [
                'label' => 'Layanan', 'icon' => 'o-wrench-screwdriver', 'color' => 'rose',
                'items' => $services->map(fn ($r) => [
                    'title' => $r->name, 'meta' => $r->service_code,
                    'url' => route('services.index', ['search' => $r->service_code]),
                ]),
            ];
        }

        $securityPrograms = SecurityProgram::query()
            ->where(fn ($q) => $q->where('program_kerja', 'ilike', $like)->orWhere('kegiatan', 'ilike', $like)->orWhere('record_code', 'ilike', $like))
            ->limit(self::PER_MODULE_LIMIT)->get();
        if ($securityPrograms->isNotEmpty()) {
            $groups[] = [
                'label' => 'Keamanan Informasi', 'icon' => 'o-shield-check', 'color' => 'emerald',
                'items' => $securityPrograms->map(fn ($r) => [
                    'title' => Str::limit($r->program_kerja, 60), 'meta' => $r->record_code,
                    'url' => route('security-programs.index', ['search' => $r->record_code]),
                ]),
            ];
        }

        $knowledgeAssets = KnowledgeAsset::query()
            ->where(fn ($q) => $q->where('title', 'ilike', $like)->orWhere('record_code', 'ilike', $like))
            ->limit(self::PER_MODULE_LIMIT)->get();
        if ($knowledgeAssets->isNotEmpty()) {
            $groups[] = [
                'label' => 'Pengetahuan — Aset', 'icon' => 'o-light-bulb', 'color' => 'amber',
                'items' => $knowledgeAssets->map(fn ($r) => [
                    'title' => $r->title, 'meta' => $r->record_code,
                    'url' => route('knowledge.index', ['search' => $r->record_code, 'tab' => 'aset']),
                ]),
            ];
        }

        $knowledgeExperts = KnowledgeExpert::query()
            ->where(fn ($q) => $q->where('nama_pegawai', 'ilike', $like)->orWhere('jabatan_unit', 'ilike', $like))
            ->limit(self::PER_MODULE_LIMIT)->get();
        if ($knowledgeExperts->isNotEmpty()) {
            $groups[] = [
                'label' => 'Pengetahuan — Keahlian', 'icon' => 'o-user-group', 'color' => 'amber',
                'items' => $knowledgeExperts->map(fn ($r) => [
                    'title' => $r->nama_pegawai, 'meta' => $r->jabatan_unit,
                    'url' => route('knowledge.index', ['search' => $r->nama_pegawai, 'tab' => 'keahlian']),
                ]),
            ];
        }

        $knowledgeActivities = KnowledgeActivity::query()
            ->where(fn ($q) => $q->where('nama_kegiatan', 'ilike', $like)->orWhere('narasumber_name', 'ilike', $like))
            ->limit(self::PER_MODULE_LIMIT)->get();
        if ($knowledgeActivities->isNotEmpty()) {
            $groups[] = [
                'label' => 'Pengetahuan — Aktivitas', 'icon' => 'o-microphone', 'color' => 'amber',
                'items' => $knowledgeActivities->map(fn ($r) => [
                    'title' => $r->nama_kegiatan, 'meta' => $r->narasumber_name,
                    'url' => route('knowledge.index', ['search' => $r->nama_kegiatan, 'tab' => 'aktivitas']),
                ]),
            ];
        }

        $knowledgeRisks = KnowledgeRisk::query()
            ->where(fn ($q) => $q->where('pernyataan_risiko', 'ilike', $like)->orWhere('record_code', 'ilike', $like))
            ->limit(self::PER_MODULE_LIMIT)->get();
        if ($knowledgeRisks->isNotEmpty()) {
            $groups[] = [
                'label' => 'Pengetahuan — Risiko KM', 'icon' => 'o-exclamation-triangle', 'color' => 'amber',
                'items' => $knowledgeRisks->map(fn ($r) => [
                    'title' => Str::limit($r->pernyataan_risiko, 60), 'meta' => $r->record_code,
                    'url' => route('knowledge.index', ['search' => $r->record_code, 'tab' => 'risiko']),
                ]),
            ];
        }

        return $groups;
    }

    public function render()
    {
        return view('livewire.global-search');
    }
}
