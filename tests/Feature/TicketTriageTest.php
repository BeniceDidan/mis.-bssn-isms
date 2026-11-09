<?php

namespace Tests\Feature;

use App\Livewire\Services\ServiceTicketFormModal;
use App\Models\Asset;
use App\Models\Service;
use App\Models\ServiceTicket;
use App\Models\User;
use App\Services\Ai\TicketTriage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class TicketTriageTest extends TestCase
{
    use RefreshDatabase;

    private function service(string $criticality = 'sedang'): Service
    {
        $asset = Asset::create([
            'category' => 'perangkat_lunak', 'sub_classification' => 'Aplikasi', 'name' => 'Aplikasi Uji',
            'criticality_level' => $criticality, 'verification_status' => 'tervalidasi', 'dynamic_data' => [],
        ]);
        $service = Service::create(['name' => 'Layanan Uji', 'verification_status' => 'tervalidasi', 'dynamic_data' => []]);
        $service->assets()->sync([$asset->id]);

        return $service->load('assets');
    }

    public function test_defacement_report_is_a_p1_security_incident(): void
    {
        $result = (new TicketTriage())->triage('Website kami diretas, muncul tulisan judi online di beranda', $this->service());

        $this->assertSame('keamanan', $result['category']);
        $this->assertSame('P1', $result['priority']);
        $this->assertNotEmpty($result['triage']['steps']);
        $this->assertStringContainsString('terkait Layanan Uji', $result['draft_reply']);
        $this->assertStringContainsString('jangan kirimkan kata sandi', $result['draft_reply']);
    }

    public function test_api_request_is_low_priority_and_reply_lists_requirements(): void
    {
        $result = (new TicketTriage())->triage('Mohon akses API data penduduk untuk integrasi aplikasi', $this->service(), 'Bappeda');

        $this->assertSame('permintaan_api', $result['category']);
        $this->assertSame('P4', $result['priority']);
        $this->assertStringContainsString('Yth. Bappeda', $result['draft_reply']);
        $this->assertStringContainsString('surat permohonan', $result['draft_reply']);
    }

    public function test_outage_on_critical_asset_raises_impact(): void
    {
        $normal = (new TicketTriage())->ruleBased('Aplikasi error 502 tidak bisa dibuka', $this->service('sedang'));
        $critical = (new TicketTriage())->ruleBased('Aplikasi error 502 tidak bisa dibuka', $this->service('kritis'));

        $this->assertSame('gangguan', $critical['category']);
        $this->assertSame('tinggi', $normal['impact']);
        $this->assertSame('kritis', $critical['impact']);
    }

    public function test_llm_can_upgrade_but_never_downgrade_and_cannot_invent_categories(): void
    {
        config(['ai.provider' => 'ollama']);
        Http::fake([
            '*/api/tags' => Http::response(['models' => [['name' => 'llama3.2:latest']]]),
            '*/api/generate' => Http::response(['response' => json_encode([
                'kategori' => 'kategori_karangan', 'dampak' => 'rendah', 'urgensi' => 'kritis',
                'alasan' => 'disebut mendesak', 'draf_balasan' => 'Yth. Bapak/Ibu, laporan Anda sudah kami terima dan sedang kami periksa bersama tim teknis terkait.',
            ])]),
        ]);

        $result = (new TicketTriage())->triage('Aplikasi error 502 tidak bisa dibuka', $this->service());

        $this->assertSame('gangguan', $result['category']);     // kategori karangan ditolak
        $this->assertSame('tinggi', $result['impact_level']);   // tidak diturunkan ke "rendah"
        $this->assertSame('kritis', $result['urgency']);        // boleh dinaikkan
        $this->assertSame('P1', $result['priority']);
        $this->assertStringContainsString('LLM lokal', $result['triage']['engine']);
        // Kalimat AI dipakai sebagai pembuka; salam tidak dobel, daftar kelengkapan tetap ada.
        $this->assertSame(1, substr_count($result['draft_reply'], 'Yth.'));
        $this->assertStringContainsString('sedang kami periksa', $result['draft_reply']);
        $this->assertStringContainsString('mohon lengkapi', $result['draft_reply']);
    }

    public function test_llm_reply_with_blank_placeholders_is_discarded(): void
    {
        config(['ai.provider' => 'ollama']);
        Http::fake([
            '*/api/tags' => Http::response(['models' => [['name' => 'llama3.2:latest']]]),
            '*/api/generate' => Http::response(['response' => json_encode([
                'kategori' => 'bug', 'dampak' => 'sedang', 'urgensi' => 'sedang', 'alasan' => '-',
                'draf_balasan' => 'Silakan hubungi kami di [nomor telepon] atau [alamat email] untuk informasi lebih lanjut.',
            ])]),
        ]);

        $result = (new TicketTriage())->triage('Tombol simpan tidak berfungsi', $this->service());

        $this->assertStringNotContainsString('[nomor telepon]', $result['draft_reply']);
        $this->assertStringContainsString('Terima kasih atas laporan Anda', $result['draft_reply']);
    }

    public function test_saving_a_ticket_without_category_triages_it_automatically(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']));
        $service = $this->service();

        Livewire::test(ServiceTicketFormModal::class)
            ->call('open', null)
            ->set('serviceId', $service->id)
            ->set('requester_name', 'Staf Kecamatan')
            ->set('issue', 'Lupa kata sandi, akun terkunci')
            ->call('save')
            ->assertHasNoErrors();

        $ticket = ServiceTicket::firstOrFail();
        $this->assertSame('akun_akses', $ticket->category);
        $this->assertSame('P3', $ticket->priority);
        $this->assertNotEmpty($ticket->draft_reply);
        $this->assertSame('Mesin aturan', $ticket->triage['engine']);
    }
}
