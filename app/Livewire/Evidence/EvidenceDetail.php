<?php

namespace App\Livewire\Evidence;

use App\Livewire\Concerns\GuardsWriteAccess;
use App\Models\EvidenceDocument;
use App\Services\Ai\EvidenceAssessor;
use Livewire\Attributes\On;
use Livewire\Component;

class EvidenceDetail extends Component
{
    use GuardsWriteAccess;

    public bool $show = false;

    public ?int $recordId = null;

    public string $decision = '';

    public string $note = '';

    #[On('open-evidence-detail')]
    public function open(int $id): void
    {
        $record = EvidenceDocument::findOrFail($id);
        $this->recordId = $record->id;
        $this->decision = $record->reviewer_decision ?? '';
        $this->note = $record->reviewer_note ?? '';
        $this->resetValidation();
        $this->show = true;
    }

    public function reassess(EvidenceAssessor $assessor): void
    {
        $this->ensureCanWrite();
        $assessor->assess($this->record());
        $this->dispatch('evidence-saved');
        $this->dispatch('toast', message: 'Dokumen dinilai ulang.');
    }

    /** Keputusan asesor hanya oleh admin modul yang bersangkutan. */
    public function saveDecision(): void
    {
        $record = $this->record();
        abort_unless(auth()->user()?->canAutoVerify($record->module_key), 403, 'Hanya admin modul ini yang dapat memberi keputusan.');

        $this->validate([
            'decision' => ['required', 'in:' . implode(',', array_keys(EvidenceDocument::DECISIONS))],
            'note' => ['nullable', 'string', 'max:2000'],
        ], ['decision.required' => 'Pilih keputusan.']);

        $record->update([
            'reviewer_decision' => $this->decision,
            'reviewer_note' => $this->note ?: null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $this->dispatch('evidence-saved');
        $this->dispatch('toast', message: 'Keputusan asesor disimpan.');
    }

    public function archive(): void
    {
        $record = $this->record();
        abort_unless(
            auth()->id() === $record->uploaded_by || auth()->user()?->canAutoVerify($record->module_key),
            403
        );

        $record->delete();
        $this->show = false;
        $this->dispatch('evidence-saved');
        $this->dispatch('toast', message: 'Bukti dukung diarsipkan.');
    }

    public function close(): void
    {
        $this->show = false;
    }

    private function record(): EvidenceDocument
    {
        return EvidenceDocument::findOrFail($this->recordId);
    }

    public function render()
    {
        $record = $this->recordId ? EvidenceDocument::with(['uploader:id,name', 'reviewer:id,name'])->find($this->recordId) : null;

        return view('livewire.evidence.evidence-detail', [
            'record' => $record,
            'canDecide' => $record && auth()->user()?->canAutoVerify($record->module_key),
            'canArchive' => $record && (auth()->id() === $record->uploaded_by || auth()->user()?->canAutoVerify($record->module_key)),
            'indicator' => $record ? config("bukti_dukung.modules.{$record->module_key}.indicator") : null,
        ]);
    }
}
