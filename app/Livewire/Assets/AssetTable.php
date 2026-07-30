<?php

namespace App\Livewire\Assets;

use App\Enums\AssetCategory;
use App\Enums\Level;
use App\Livewire\Concerns\GuardsWriteAccess;
use App\Models\Asset;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AssetTable extends Component
{
    use WithPagination, GuardsWriteAccess;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $categoryFilter = '';

    #[Url(history: true)]
    public string $criticalityFilter = '';

    #[Url(history: true)]
    public bool $showArchived = false;

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public ?Asset $viewingAsset = null;

    /**
     * Entangling the full $viewingAsset model into Alpine for the slide-over's
     * x-show proved unreliable (the panel's content updated but visibility
     * didn't track it correctly) — a plain boolean entangles cleanly instead.
     */
    public bool $detailOpen = false;

    protected string $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCriticalityFilter(): void
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

    public function viewDetail(int $assetId): void
    {
        $this->viewingAsset = Asset::withTrashed()
            ->with(['risks' => fn ($q) => $q->latest()->limit(5)])
            ->with(['changes' => fn ($q) => $q->latest()->limit(5)])
            ->with(['dataInformationRecords' => fn ($q) => $q->latest()->limit(5)])
            ->with(['knowledgeAssets' => fn ($q) => $q->latest()->limit(5)])
            ->with(['securityPrograms' => fn ($q) => $q->latest()->limit(5)])
            // ->latest() alone is ambiguous here: services() is a
            // belongsToMany through service_assets, and both tables have
            // their own created_at once the pivot is joined in — Postgres
            // (unlike MySQL) rejects the unqualified column.
            ->with(['services' => fn ($q) => $q->latest('services.created_at')->limit(5)])
            ->with(['verifications' => fn ($q) => $q->with('user')->limit(3)])
            ->findOrFail($assetId);
        $this->detailOpen = true;
    }

    public function closeDetail(): void
    {
        $this->detailOpen = false;
    }

    /**
     * SoftDeletes turns this into an archive, never a physical delete
     * (rule 4 — no data loss).
     */
    public function archive(int $assetId): void
    {
        $this->ensureCanWrite();

        $asset = Asset::findOrFail($assetId);
        $asset->delete();

        $this->dispatch('toast', message: "Aset \"{$asset->name}\" diarsipkan.");
    }

    public function restore(int $assetId): void
    {
        $this->ensureCanWrite();

        $asset = Asset::onlyTrashed()->findOrFail($assetId);
        $asset->restore();

        $this->dispatch('toast', message: "Aset \"{$asset->name}\" dipulihkan.");
    }

    #[On('asset-saved')]
    public function refreshTable(): void
    {
        // no-op body: Livewire re-renders on every dispatched event this
        // component listens for, which is enough to reflect the new row.
    }

    public function render()
    {
        $assets = Asset::query()
            ->withCount(['risks', 'changes', 'dataInformationRecords', 'knowledgeAssets', 'services', 'securityPrograms'])
            ->when($this->showArchived, fn ($query) => $query->onlyTrashed())
            ->when($this->search, function ($query) {
                $term = "%{$this->search}%";
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'ilike', $term)
                        ->orWhere('asset_code', 'ilike', $term)
                        ->orWhere('legacy_code', 'ilike', $term)
                        ->orWhere('owner', 'ilike', $term)
                        // Casting jsonb to text lets the same search box
                        // reach into category-specific dynamic_data fields.
                        ->orWhereRaw('dynamic_data::text ilike ?', [$term]);
                });
            })
            ->when($this->categoryFilter, fn ($query) => $query->where('category', $this->categoryFilter))
            ->when($this->criticalityFilter, fn ($query) => $query->where('criticality_level', $this->criticalityFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.assets.asset-table', [
            'assets' => $assets,
            'categories' => AssetCategory::cases(),
            'levels' => Level::cases(),
        ]);
    }
}
