<?php

namespace App\Livewire\Knowledge;

use App\Livewire\Concerns\GeneratesPersonnelRef;
use App\Livewire\Concerns\GuardsWriteAccess;
use App\Models\KnowledgeActivity;
use App\Models\KnowledgeExpert;
use Livewire\Attributes\On;
use Livewire\Component;

class KnowledgeActivityFormModal extends Component
{
    use GuardsWriteAccess, GeneratesPersonnelRef;

    public bool $show = false;

    public ?int $recordId = null;

    public string $nama_kegiatan = '';

    public ?string $tanggal_pelaksanaan = null;

    public string $materi_topik = '';

    public ?int $narasumber_id = null;

    public ?int $jumlah_peserta = null;

    public string $link_bukti = '';

    public ?string $personnel_ref = null;

    protected function rules(): array
    {
        return [
            'nama_kegiatan' => ['required', 'string', 'max:255'],
            'tanggal_pelaksanaan' => ['nullable', 'date'],
            'materi_topik' => ['nullable', 'string', 'max:2000'],
            'narasumber_id' => ['nullable', 'exists:knowledge_experts,id'],
            'jumlah_peserta' => ['nullable', 'integer', 'min:0'],
            'link_bukti' => ['nullable', 'url', 'max:500'],
            'personnel_ref' => ['nullable', 'string', 'max:50'],
        ];
    }

    #[On('open-knowledge-activity-form')]
    public function open(?int $recordId = null): void
    {
        $this->resetValidation();
        $this->recordId = $recordId;

        if ($recordId) {
            $record = KnowledgeActivity::findOrFail($recordId);
            $this->nama_kegiatan = $record->nama_kegiatan;
            $this->tanggal_pelaksanaan = optional($record->tanggal_pelaksanaan)->format('Y-m-d');
            $this->materi_topik = $record->materi_topik ?? '';
            $this->narasumber_id = $record->narasumber_id;
            $this->jumlah_peserta = $record->jumlah_peserta;
            $this->link_bukti = $record->link_bukti ?? '';
            $this->personnel_ref = $record->personnel_ref;
        } else {
            $this->reset(['nama_kegiatan', 'tanggal_pelaksanaan', 'materi_topik', 'narasumber_id', 'jumlah_peserta', 'link_bukti', 'personnel_ref']);
        }

        $this->show = true;
    }

    public function save(): void
    {
        $this->ensureCanWrite();

        $payload = $this->validate();
        $payload['narasumber_name'] = $payload['narasumber_id']
            ? KnowledgeExpert::find($payload['narasumber_id'])?->nama_pegawai
            : null;
        $payload['personnel_ref'] = $this->ensurePersonnelRef($payload['personnel_ref'] ?? null);
        $payload['verification_status'] = auth()->user()?->isAdmin() ? 'tervalidasi' : 'menunggu_verifikasi';

        if ($this->recordId) {
            KnowledgeActivity::findOrFail($this->recordId)->update($payload);
        } else {
            KnowledgeActivity::create($payload);
        }

        $this->show = false;
        $this->dispatch('knowledge-activity-saved');
        $this->dispatch('toast', message: $this->recordId ? 'Kegiatan diperbarui.' : 'Kegiatan baru ditambahkan.');
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.knowledge.knowledge-activity-form-modal', [
            'experts' => KnowledgeExpert::orderBy('nama_pegawai')->get(['id', 'nama_pegawai']),
        ]);
    }
}
