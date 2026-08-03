<?php

namespace App\Livewire;

use App\Models\Verification;
use App\Support\AdminModules;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Admin inbox for the BPMN "Pemeriksaan data -> ACC/Koreksi" step —
 * scoped to whichever single module the logged-in admin is assigned to
 * (User::admin_module), since there is deliberately no blanket
 * super-admin. See App\Support\AdminModules for the module -> models map.
 */
class AdminVerificationQueue extends Component
{
    public ?string $decidingModuleKey = null;

    public ?int $decidingId = null;

    public string $decision = '';

    public string $notes = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    /** @return array<int, class-string> */
    private function scopedModels(): array
    {
        return AdminModules::modelsFor(auth()->user()?->admin_module);
    }

    private function pendingItems(): Collection
    {
        $items = collect();

        foreach ($this->scopedModels() as $modelClass) {
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
        abort_unless(in_array($modelClass, $this->scopedModels(), true), 403);

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
