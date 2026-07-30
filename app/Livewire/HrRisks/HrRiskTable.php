<?php

namespace App\Livewire\HrRisks;

use App\Enums\Level;
use App\Livewire\Concerns\GuardsWriteAccess;
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
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class HrRiskTable extends Component
{
    use WithPagination, GuardsWriteAccess;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $riskLevelFilter = '';

    #[Url(history: true)]
    public bool $showArchived = false;

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public ?HrRisk $viewingRecord = null;

    public ?Collection $relatedExperts = null;

    /** @var array<string, Collection> keyed by module label, non-empty groups only */
    public array $crossModuleLinks = [];

    public bool $detailOpen = false;

    protected string $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRiskLevelFilter(): void
    {
        $this->resetPage();
    }

    public function updatingShowArchived(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function viewDetail(int $id): void
    {
        $this->viewingRecord = HrRisk::withTrashed()->with(['verifications' => fn ($q) => $q->with('user')->limit(3)])->findOrFail($id);

        $personnelRef = $this->viewingRecord->personnel_ref;

        // Cross-reference against Peta Keahlian (Manajemen Pengetahuan) —
        // exact Kode Personil match only, no name-guessing.
        $this->relatedExperts = KnowledgeExpert::all()
            ->filter(fn (KnowledgeExpert $expert) => $personnelRef && $expert->personnel_ref === $personnelRef)
            ->values();

        $this->crossModuleLinks = $this->findCrossModuleLinks($personnelRef);

        $this->detailOpen = true;
    }

    /**
     * "Ketika input data di SDM, harusnya mempengaruhi ke 7 manajemen lain,
     * dengan ID yang sama, jangan tebak-tebakan nama" — the concrete version
     * of that: two records are related only when they carry the exact same
     * user-assigned Kode Personil (personnel_ref). Every writable record
     * gets one automatically at save time if left blank (see
     * GeneratesPersonnelRef), so nothing is ever ambiguous — a link exists
     * only when someone deliberately made two codes match.
     *
     * @return array<string, Collection>
     */
    private function findCrossModuleLinks(?string $personnelRef): array
    {
        if (! $personnelRef) {
            return [];
        }

        $groups = [
            'Manajemen Aset' => Asset::all()
                ->map(fn (Asset $r) => $this->matchByPersonnelRef($personnelRef, $r, $r->asset_code, $r->name))
                ->filter(),

            'Manajemen Risiko' => Risk::all()
                ->map(fn (Risk $r) => $this->matchByPersonnelRef($personnelRef, $r, $r->risk_code, $r->title))
                ->filter(),

            'Manajemen Perubahan' => Change::all()
                ->map(fn (Change $r) => $this->matchByPersonnelRef($personnelRef, $r, $r->change_code, $r->title))
                ->filter(),

            'Manajemen Data & Informasi' => DataInformation::all()
                ->map(fn (DataInformation $r) => $this->matchByPersonnelRef($personnelRef, $r, $r->record_code, $r->title))
                ->filter(),

            'Manajemen Layanan' => Service::all()
                ->map(fn (Service $r) => $this->matchByPersonnelRef($personnelRef, $r, $r->service_code, $r->name))
                ->filter(),

            'Manajemen Keamanan Informasi' => SecurityProgram::all()
                ->map(fn (SecurityProgram $r) => $this->matchByPersonnelRef($personnelRef, $r, $r->record_code, $r->program_kerja))
                ->filter(),

            'Manajemen Pengetahuan (Aset, Aktivitas & Risiko KM)' => KnowledgeAsset::all()
                ->map(fn (KnowledgeAsset $r) => $this->matchByPersonnelRef($personnelRef, $r, $r->record_code, $r->title))
                ->filter()
                ->concat(
                    KnowledgeActivity::all()
                        ->map(fn (KnowledgeActivity $r) => $this->matchByPersonnelRef($personnelRef, $r, $r->record_code, $r->nama_kegiatan))
                        ->filter()
                )
                ->concat(
                    KnowledgeRisk::all()
                        ->map(fn (KnowledgeRisk $r) => $this->matchByPersonnelRef($personnelRef, $r, $r->record_code, $r->pernyataan_risiko))
                        ->filter()
                ),
        ];

        return collect($groups)
            ->map(fn (Collection $c) => $c->values())
            ->filter(fn (Collection $c) => $c->isNotEmpty())
            ->all();
    }

    /** @return array{code: ?string, title: ?string, matched: string}|null */
    private function matchByPersonnelRef(string $personnelRef, $record, ?string $code, ?string $title): ?array
    {
        if ($record->personnel_ref !== $personnelRef) {
            return null;
        }

        return ['code' => $code, 'title' => $title, 'matched' => $record->personnel_ref];
    }

    public function closeDetail(): void
    {
        $this->detailOpen = false;
    }

    public function archive(int $id): void
    {
        $this->ensureCanWrite();

        $record = HrRisk::findOrFail($id);
        $record->delete();

        $this->dispatch('toast', message: "Risiko \"{$record->title}\" diarsipkan.");
    }

    public function restore(int $id): void
    {
        $this->ensureCanWrite();

        $record = HrRisk::onlyTrashed()->findOrFail($id);
        $record->restore();

        $this->dispatch('toast', message: "Risiko \"{$record->title}\" dipulihkan.");
    }

    #[On('hr-risk-saved')]
    public function refreshTable(): void
    {
        // no-op
    }

    public function render()
    {
        $records = HrRisk::query()
            ->when($this->showArchived, fn ($query) => $query->onlyTrashed())
            ->when($this->search, function ($query) {
                $term = "%{$this->search}%";
                $query->where(function ($q) use ($term) {
                    $q->where('title', 'ilike', $term)
                        ->orWhere('subject', 'ilike', $term)
                        ->orWhere('record_code', 'ilike', $term)
                        ->orWhere('legacy_code', 'ilike', $term)
                        ->orWhereRaw('dynamic_data::text ilike ?', [$term]);
                });
            })
            ->when($this->riskLevelFilter, fn ($query) => $query->where('inherent_risk_level', $this->riskLevelFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.hr-risks.hr-risk-table', [
            'records' => $records,
            'levels' => Level::cases(),
        ]);
    }
}
