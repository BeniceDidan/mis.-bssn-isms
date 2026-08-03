<?php

namespace App\Livewire\Knowledge;

use App\Livewire\Concerns\GeneratesPersonnelRef;
use App\Livewire\Concerns\GuardsWriteAccess;
use App\Models\HrRisk;
use App\Models\KnowledgeExpert;
use Livewire\Attributes\On;
use Livewire\Component;

class KnowledgeExpertFormModal extends Component
{
    use GuardsWriteAccess, GeneratesPersonnelRef;

    public bool $show = false;

    public ?int $recordId = null;

    public string $nama_pegawai = '';

    public string $jabatan_unit = '';

    public string $keahlian_spesifik = '';

    public string $sertifikasi_lisensi = '';

    public string $peran_km = '';

    public ?string $personnel_ref = null;

    protected function rules(): array
    {
        return [
            'nama_pegawai' => ['required', 'string', 'max:255'],
            'jabatan_unit' => ['nullable', 'string', 'max:255'],
            'keahlian_spesifik' => ['nullable', 'string', 'max:2000'],
            'sertifikasi_lisensi' => ['nullable', 'string', 'max:2000'],
            'peran_km' => ['nullable', 'string', 'max:50'],
            'personnel_ref' => ['nullable', 'string', 'max:50'],
        ];
    }

    #[On('open-knowledge-expert-form')]
    public function open(?int $recordId = null): void
    {
        $this->resetValidation();
        $this->recordId = $recordId;

        if ($recordId) {
            $record = KnowledgeExpert::findOrFail($recordId);
            $this->nama_pegawai = $record->nama_pegawai;
            $this->jabatan_unit = $record->jabatan_unit ?? '';
            $this->keahlian_spesifik = $record->keahlian_spesifik ?? '';
            $this->sertifikasi_lisensi = $record->sertifikasi_lisensi ?? '';
            $this->peran_km = $record->peran_km ?? '';
            $this->personnel_ref = $record->personnel_ref;
        } else {
            $this->reset(['nama_pegawai', 'jabatan_unit', 'keahlian_spesifik', 'sertifikasi_lisensi', 'peran_km', 'personnel_ref']);
        }

        $this->show = true;
    }

    public function save(): void
    {
        $this->ensureCanWrite();

        $payload = $this->validate();
        $payload['personnel_ref'] = $this->ensurePersonnelRef($payload['personnel_ref'] ?? null);
        $payload['verification_status'] = auth()->user()?->canAutoVerify('pengetahuan') ? 'tervalidasi' : 'menunggu_verifikasi';

        if ($this->recordId) {
            KnowledgeExpert::findOrFail($this->recordId)->update($payload);
        } else {
            KnowledgeExpert::create($payload);
        }

        $this->show = false;
        $this->dispatch('knowledge-expert-saved');
        $this->dispatch('toast', message: $this->recordId ? 'Data keahlian diperbarui.' : 'Data keahlian baru ditambahkan.');
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.knowledge.knowledge-expert-form-modal', [
            'hrRisks' => HrRisk::whereNotNull('personnel_ref')->where('personnel_ref', '!=', '')->orderBy('subject')->get(['id', 'subject', 'record_code', 'personnel_ref']),
        ]);
    }
}
