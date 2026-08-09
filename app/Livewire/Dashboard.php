<?php

namespace App\Livewire;

use App\Models\Asset;
use App\Models\Change;
use App\Models\DataInformation;
use App\Models\HrRisk;
use App\Models\KnowledgeAsset;
use App\Models\Risk;
use App\Models\SecurityProgram;
use App\Models\Service;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

/**
 * The Dashboard is a pure module hub — one tile per ISMS module, active
 * ones showing a live record count and linking to their own page (which
 * carries its own stats/activity widgets), inactive ones marked "Segera
 * Hadir". Per-module metrics used to live here directly; they moved into
 * each module's own index page so this stays a launcher, not a duplicate
 * of every module's content.
 *
 * Tile order follows the "Alur Korelasi 8 Manajemen" flowchart (the
 * benang merah/common thread the user described): SDM feeds Pengetahuan,
 * which informs Aset, which is assessed for Keamanan and Risiko, which
 * triggers Perubahan, which updates Layanan and, in turn, Data & Informasi
 * — not alphabetical or build order.
 */
class Dashboard extends Component
{
    public function render()
    {
        $modules = [
            ['label' => 'Manajemen SDM', 'route' => 'hr-risks.index', 'icon' => 's-users', 'color' => 'slate', 'description' => 'Risiko SDM & pihak ketiga', 'count' => HrRisk::count()],
            ['label' => 'Manajemen Pengetahuan', 'route' => 'knowledge.index', 'icon' => 's-light-bulb', 'color' => 'amber', 'description' => 'Manual book & dokumentasi', 'count' => KnowledgeAsset::count()],
            ['label' => 'Manajemen Aset', 'route' => 'assets.index', 'icon' => 's-archive-box', 'color' => 'teal', 'description' => 'Inventaris aset TIK', 'count' => Asset::count()],
            ['label' => 'Manajemen Keamanan Informasi', 'route' => 'security-programs.index', 'icon' => 's-shield-check', 'color' => 'emerald', 'description' => 'Program kerja keamanan SPBE', 'count' => SecurityProgram::count()],
            ['label' => 'Manajemen Risiko', 'route' => 'risks.index', 'icon' => 's-exclamation-triangle', 'color' => 'purple', 'description' => 'Risk register terpusat', 'count' => Risk::count()],
            ['label' => 'Manajemen Perubahan', 'route' => 'changes.index', 'icon' => 's-arrow-path', 'color' => 'indigo', 'description' => 'Register perubahan TIK', 'count' => Change::count()],
            ['label' => 'Manajemen Layanan', 'route' => 'services.index', 'icon' => 's-wrench-screwdriver', 'color' => 'rose', 'description' => 'Katalog layanan SPBE', 'count' => Service::count()],
            ['label' => 'Manajemen Data Informasi', 'route' => 'data-information.index', 'icon' => 's-circle-stack', 'color' => 'sky', 'description' => 'Tata kelola data & informasi', 'count' => DataInformation::count()],
        ];

        foreach ($modules as &$module) {
            $module['enabled'] = $module['route'] && Route::has($module['route']);
        }

        return view('livewire.dashboard', [
            'modules' => $modules,
            'activeCount' => count(array_filter($modules, fn ($m) => $m['enabled'])),
        ]);
    }
}
