<?php

namespace Tests\Feature;

use App\Livewire\Assistant;
use App\Livewire\Services\ServiceTicketFormModal;
use App\Models\AiLog;
use App\Models\Asset;
use App\Models\KnowledgeAsset;
use App\Models\Risk;
use App\Models\Service;
use App\Models\ServiceTicket;
use App\Models\User;
use App\Services\Ai\AppAssistant;
use App\Services\Ai\TicketTriage;
use App\Support\IntegrationMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Asisten aplikasi dan titik integrasi AI ke modul lain. Tes berjalan dengan
 * AI_PROVIDER=none (jalur cadangan tanpa LLM), kecuali yang memalsukan Ollama.
 */
class AppAssistantTest extends TestCase
{
    use RefreshDatabase;

    private function risk(string $code, string $title, string $level, ?int $assetId = null, string $status = 'open'): Risk
    {
        return Risk::create([
            'risk_code' => $code, 'title' => $title, 'category' => 'teknis', 'asset_id' => $assetId,
            'likelihood' => $level, 'impact' => $level, 'risk_level' => $level, 'status' => $status,
        ]);
    }

    public function test_how_to_question_is_answered_from_the_app_guide_with_a_page_link(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']));

        $result = (new AppAssistant())->answer('Bagaimana cara ganti kata sandi?');

        $this->assertStringContainsString('Profil', $result['answer']);
        $this->assertStringContainsString('Ubah Kata Sandi', $result['answer']);
        $this->assertContains(route('profile.edit'), array_column($result['links'], 'url'));
        $this->assertSame(1, AiLog::where('feature', 'asisten')->count());
    }

    public function test_count_questions_are_answered_straight_from_the_database(): void
    {
        $this->risk('RSK-UJI-0001', 'Peretasan website resmi', 'kritis');
        $this->risk('RSK-UJI-0002', 'Server basis data rusak', 'kritis');
        $this->risk('RSK-UJI-0003', 'Listrik padam singkat', 'sedang');

        $result = (new AppAssistant())->answer('Berapa risiko yang levelnya kritis?');

        $this->assertSame('Ada 2 risiko level kritis. Daftarnya ada di bawah.', $result['answer']);
        $this->assertSame('Hitungan langsung dari basis data', $result['engine']);
        $titles = implode(' | ', array_column($result['links'], 'title'));
        $this->assertStringContainsString('RSK-UJI-0001', $titles);
        $this->assertStringContainsString('RSK-UJI-0002', $titles);
        $this->assertStringNotContainsString('RSK-UJI-0003', $titles);
    }

    public function test_file_requests_return_links_to_the_pdf(): void
    {
        KnowledgeAsset::create([
            'record_code' => 'KNW-UJI-0001', 'title' => 'SOP Permohonan Server dan Hosting', 'document_path' => 'knowledge-documents/sop-server.pdf',
            'verification_status' => 'tervalidasi', 'dynamic_data' => [],
        ]);
        KnowledgeAsset::create([
            'record_code' => 'KNW-UJI-0002', 'title' => 'Pedoman Cuti Pegawai', 'document_path' => 'knowledge-documents/cuti.pdf',
            'verification_status' => 'tervalidasi', 'dynamic_data' => [],
        ]);

        $result = (new AppAssistant())->answer('Carikan file SOP permohonan server');

        $files = array_values(array_filter($result['links'], fn ($l) => $l['kind'] === 'file'));
        $this->assertCount(1, $files);
        $this->assertSame('SOP Permohonan Server dan Hosting', $files[0]['title']);
        $this->assertStringEndsWith('knowledge-documents/sop-server.pdf', $files[0]['url']);
    }

    public function test_ticket_filters_by_priority_and_open_status(): void
    {
        $service = Service::create(['name' => 'Portal Uji', 'verification_status' => 'tervalidasi', 'dynamic_data' => []]);
        ServiceTicket::create(['service_id' => $service->id, 'legacy_code' => 'TIK-A', 'issue' => 'Portal diretas', 'priority' => 'P1', 'status' => 'baru']);
        ServiceTicket::create(['service_id' => $service->id, 'legacy_code' => 'TIK-B', 'issue' => 'Portal lambat', 'priority' => 'P1', 'status' => 'selesai']);
        ServiceTicket::create(['service_id' => $service->id, 'legacy_code' => 'TIK-C', 'issue' => 'Minta akun', 'priority' => 'P4', 'status' => 'baru']);

        $result = (new AppAssistant())->answer('Berapa tiket P1 yang masih terbuka?');

        $this->assertStringStartsWith('Ada 1 tiket helpdesk prioritas P1 yang masih terbuka.', $result['answer']);
        $this->assertSame(['TIK-A · Portal diretas'], array_column($result['links'], 'title'));
    }

    public function test_codes_invented_by_the_llm_are_never_shown(): void
    {
        $this->risk('RSK-UJI-0001', 'Peretasan website resmi', 'kritis');
        config(['ai.provider' => 'ollama']);
        Http::fake([
            '*/api/tags' => Http::response(['models' => [['name' => 'llama3.2:latest']]]),
            '*/api/generate' => Http::response(['response' => 'Risiko kritis: RSK-UJI-0001 dan RSK-UJI-0099.']),
        ]);

        $result = (new AppAssistant())->answer('Cari risiko peretasan');

        $this->assertStringNotContainsString('RSK-UJI-0099', $result['answer']);
        $this->assertStringContainsString('RSK-UJI-0001', $result['answer']);
    }

    public function test_answer_text_from_the_llm_is_tidied(): void
    {
        $this->assertSame(
            "Langkahnya:\n• Klik Profil.",
            AppAssistant::tidy("**Langkahnya:**\n\n* Klik Profil. (https://contoh.go.id/profil)")
        );
        $this->assertStringStartsWith('Maaf, saya belum menemukan', AppAssistant::tidy('Maaf, saya belum menemukan informasinya di aplikasi ini. Silakan hubungi admin.'));
    }

    public function test_widget_keeps_the_conversation_across_pages_and_can_be_cleared(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']));

        $this->get(route('dashboard'))->assertOk()->assertSee('data-tour="assistant-button"', false);

        Livewire::test(Assistant::class)
            ->call('send', 'Bagaimana cara ganti kata sandi?')
            ->assertSee('Bagaimana cara ganti kata sandi?')
            ->assertSee('Ubah Kata Sandi');

        $this->assertCount(2, session('assistant.messages'));
        Livewire::test(Assistant::class)->assertSee('Bagaimana cara ganti kata sandi?')->call('clear');
        $this->assertNull(session('assistant.messages'));
    }

    public function test_guests_do_not_get_the_widget(): void
    {
        $this->get(route('login'))->assertOk()->assertDontSee('data-tour="assistant-button"', false);
    }

    public function test_triage_shows_assets_risks_and_fills_related_risk(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']));
        $asset = Asset::create([
            'asset_code' => 'AST-UJI-0001', 'category' => 'perangkat_lunak', 'sub_classification' => 'Aplikasi', 'name' => 'Server Portal',
            'criticality_level' => 'kritis', 'verification_status' => 'tervalidasi', 'dynamic_data' => [],
        ]);
        $service = Service::create(['name' => 'Portal Uji', 'verification_status' => 'tervalidasi', 'dynamic_data' => []]);
        $service->assets()->sync([$asset->id]);
        $this->risk('RSK-UJI-0010', 'Listrik padam', 'rendah', $asset->id);
        $this->risk('RSK-UJI-0011', 'Peretasan portal', 'tinggi', $asset->id);
        $this->risk('RSK-UJI-0012', 'Risiko lama', 'kritis', $asset->id, 'closed');

        $related = (new TicketTriage())->related('Portal diretas', $service->load('assets'));

        $this->assertSame('AST-UJI-0001', $related['assets'][0]['code']);
        $this->assertSame(['RSK-UJI-0011', 'RSK-UJI-0010'], array_column($related['risks'], 'code'));

        Livewire::test(ServiceTicketFormModal::class)
            ->call('open', $service->id)
            ->set('issue', 'Website portal diretas, muncul tulisan judi online di beranda')
            ->call('analyze')
            ->assertSet('related_risk_text', 'RSK-UJI-0011')
            ->assertSee('Terkait di modul lain')
            ->assertSee('Peretasan portal');
    }

    public function test_integration_map_lists_the_ai_touch_points(): void
    {
        $layanan = collect(IntegrationMap::for('layanan')['flows'])->pluck('to')->implode(' | ');
        $this->assertStringContainsString('Analisis AI tiket', $layanan);
        $this->assertStringContainsString('Evaluasi Bukti Dukung', $layanan);
        $this->assertStringContainsString('Asisten', collect(IntegrationMap::for('sdm')['flows'])->pluck('to')->implode(' | '));
    }
}
