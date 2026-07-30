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
use App\Models\Verification;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Unified admin inbox for the BPMN "Pemeriksaan data -> ACC/Koreksi" step
 * across all 6 modules — one review queue instead of six near-identical
 * per-module pages. Each of the 6 models is a separate table, so this
 * fetches each model's pending rows separately and merges them in PHP
 * (the row counts here are small — dozens, not millions — so this is
 * simpler and plenty fast compared to a raw SQL UNION across 6 tables).
 */
class AdminVerificationQueue extends Component
{
    private const MODELS = [
        Asset::class, Risk::class, Change::class, DataInformation::class, HrRisk::class,
        KnowledgeAsset::class, Service::class,
        KnowledgeExpert::class, KnowledgeActivity::class, KnowledgeRisk::class,
        SecurityProgram::class,
    ];

    public ?string $decidingModuleKey = null;

    public ?int $decidingId = null;

    public string $decision = '';

    public string $notes = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    private function pendingItems(): Collection
    {
        $items = collect();

        foreach (self::MODELS as $modelClass) {
            $key = array_search($modelClass, Relation::morphMap(), true) ?: $modelClass;

            $modelClass::where('verification_status', 'menunggu_verifikasi')
                ->get()
                ->each(function ($record) use (&$items, $key) {
                    $items->push([
                        'moduleKey' => $key,
                        'moduleLabel' => $record::verificationLabel(),
                        'id' => $record->id,
                        'code' => $record->{$record->getCodeColumn()},
                        'title' => $record->title ?? $record->name ?? $record->subject
                            ?? $record->nama_pegawai ?? $record->nama_kegiatan
                            ?? $record->pernyataan_risiko ?? $record->program_kerja
                            ?? '(tanpa judul)',
                        'updatedAt' => $record->updated_at,
                    ]);
                });
        }

        return $items->sortBy('updatedAt')->values();
    }

    public function openDecision(string $moduleKey, int $id, string $decision): void
    {
        $this->decidingModuleKey = $moduleKey;
        $this->decidingId = $id;
        $this->decision = $decision;
        $this->notes = '';
    }

    public function cancelDecision(): void
    {
        $this->decidingModuleKey = null;
        $this->decidingId = null;
        $this->decision = '';
        $this->notes = '';
    }

    public function confirmDecision(): void
    {
        if (! in_array($this->decision, ['tervalidasi', 'ditolak'], true)) {
            return;
        }

        if ($this->decision === 'ditolak') {
            $this->validate(['notes' => ['required', 'string', 'max:2000']], [
                'notes.required' => 'Catatan koreksi wajib diisi saat menolak data.',
            ]);
        }

        $modelClass = Relation::morphMap()[$this->decidingModuleKey] ?? null;
        abort_unless($modelClass !== null, 404);

        $record = $modelClass::findOrFail($this->decidingId);
        $record->update(['verification_status' => $this->decision]);

        Verification::create([
            'verifiable_type' => $this->decidingModuleKey,
            'verifiable_id' => $record->id,
            'user_id' => auth()->id(),
            'status' => $this->decision,
            'notes' => $this->notes ?: null,
            'created_at' => now(),
        ]);

        $label = $record->title ?? $record->name ?? $record->subject
            ?? $record->nama_pegawai ?? $record->nama_kegiatan
            ?? $record->pernyataan_risiko ?? $record->program_kerja
            ?? $record->getKey();

        $this->dispatch('toast', message: $this->decision === 'tervalidasi'
            ? "\"{$label}\" disetujui."
            : "\"{$label}\" dikembalikan untuk koreksi.");

        $this->cancelDecision();
    }

    #[On('asset-saved')]
    #[On('risk-saved')]
    #[On('change-saved')]
    #[On('data-information-saved')]
    #[On('hr-risk-saved')]
    #[On('knowledge-saved')]
    #[On('service-saved')]
    #[On('knowledge-expert-saved')]
    #[On('knowledge-activity-saved')]
    #[On('knowledge-risk-saved')]
    #[On('security-program-saved')]
    public function refresh(): void
    {
        // no-op: re-render picks up fresh queue state
    }

    public function render()
    {
        return view('livewire.admin-verification-queue', [
            'pending' => $this->pendingItems(),
        ]);
    }
}
