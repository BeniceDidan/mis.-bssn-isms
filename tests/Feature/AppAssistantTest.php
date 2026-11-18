<?php

namespace Tests\Feature;

use App\Livewire\Assistant;
use App\Livewire\Services\ServiceTicketFormModal;
use App\Models\AiLog;
use App\Models\Asset;
use App\Models\EvidenceDocument;
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
use Illuminate\Support\Facades\Storage;
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

        $this->assertSame('Ada 2 risiko level kritis.', $result['answer']);
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

    public function test_llm_answers_with_invented_codes_or_numbers_are_discarded(): void
    {
        config(['ai.provider' => 'ollama']);
        Http::fake([
            '*/api/tags' => Http::response(['models' => [['name' => 'llama3.2:latest']]]),
            '*/api/generate' => Http::response(['response' => 'Ada 3 peran, misalnya RSK-UJI-0099.']),
        ]);

        // Bukan pertanyaan "cara", jadi panduan dirangkai LLM; jawaban LLM
        // yang mengarang angka & kode dibuang dan diganti teks panduan.
        $result = (new AppAssistant())->answer('peran admin dan user');

        $this->assertStringNotContainsString('RSK-UJI-0099', $result['answer']);
        $this->assertStringContainsString('Setiap admin memegang satu modul', $result['answer']);
        $this->assertTrue(AppAssistant::invents('Ada 3 risiko.', 'Risiko: 2'));
        $this->assertFalse(AppAssistant::invents("1. Klik Simpan.\n2. Tunggu 5 hari kerja.", 'selesai dalam 5 hari kerja'));
    }

    public function test_answer_text_from_the_llm_is_tidied(): void
    {
        $this->assertSame(
            "Langkahnya:\n• Klik Profil.",
            AppAssistant::tidy("**Langkahnya:**\n\n* Klik Profil. (https://contoh.go.id/profil)")
        );
        $this->assertSame(AppAssistant::NOT_FOUND, AppAssistant::tidy('Maaf, saya belum menemukan informasinya di aplikasi ini. Silakan hubungi admin.'));
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

    public function test_plain_count_questions_work(): void
    {
        $this->risk('RSK-UJI-0001', 'Peretasan website resmi', 'kritis');

        // "jumlah" sempat terpotong menjadi "jum" oleh aturan akhiran.
        $this->assertStringStartsWith('Ada 1 risiko.', (new AppAssistant())->answer('Berapa jumlah risiko?')['answer']);
    }

    public function test_casual_and_english_questions_are_understood(): void
    {
        $service = Service::create(['name' => 'Portal Uji', 'verification_status' => 'tervalidasi', 'dynamic_data' => []]);
        ServiceTicket::create(['service_id' => $service->id, 'legacy_code' => 'TIK-A', 'issue' => 'Portal lambat', 'priority' => 'P2', 'status' => 'diproses']);
        ServiceTicket::create(['service_id' => $service->id, 'legacy_code' => 'TIK-B', 'issue' => 'Portal error', 'priority' => 'P2', 'status' => 'selesai']);
        $this->risk('RSK-UJI-0001', 'Peretasan website resmi', 'kritis');
        $this->risk('RSK-UJI-0002', 'Server rusak', 'kritis');

        $this->assertStringStartsWith('Ada 1 tiket helpdesk prioritas P2 yang masih terbuka.', (new AppAssistant())->answer('brp tiket p2 yg blm kelar')['answer']);
        $this->assertStringStartsWith('Ada 2 risiko level kritis.', (new AppAssistant())->answer('How many critical risks are there?')['answer']);
    }

    public function test_relations_between_modules(): void
    {
        $server = Asset::create(['asset_code' => 'AST-UJI-0001', 'category' => 'perangkat_keras', 'sub_classification' => 'Server', 'name' => 'Server Basis Data Terpusat', 'criticality_level' => 'tinggi', 'verification_status' => 'tervalidasi', 'dynamic_data' => []]);
        $firewall = Asset::create(['asset_code' => 'AST-UJI-0002', 'category' => 'perangkat_keras', 'sub_classification' => 'Firewall', 'name' => 'Firewall Perimeter Pusat Data', 'criticality_level' => 'tinggi', 'verification_status' => 'tervalidasi', 'dynamic_data' => []]);
        $portal = Service::create(['name' => 'Layanan Website Resmi', 'verification_status' => 'tervalidasi', 'dynamic_data' => []]);
        $portal->assets()->sync([$server->id]);
        $this->risk('RSK-UJI-0001', 'Kegagalan disk server', 'tinggi', $server->id);
        $this->risk('RSK-UJI-0002', 'Aturan firewall terbuka', 'sedang', $firewall->id);
        ServiceTicket::create(['service_id' => $portal->id, 'legacy_code' => 'TIK-A', 'issue' => 'Website tidak bisa dibuka', 'priority' => 'P2', 'status' => 'baru']);

        $a = new AppAssistant();
        $risks = $a->answer('Risiko apa saja pada aset Server Basis Data Terpusat?');
        $this->assertStringStartsWith('Ada 1 risiko pada aset AST-UJI-0001 Server Basis Data Terpusat.', $risks['answer']);
        $this->assertSame(['RSK-UJI-0001 · Kegagalan disk server'], array_column($risks['links'], 'title'));

        $this->assertSame(
            'Tidak ada layanan yang bergantung pada aset AST-UJI-0002 Firewall Perimeter Pusat Data.',
            $a->answer('Layanan apa yang bergantung pada Firewall Perimeter?')['answer']
        );
        // 2 tiket: satu dari helpdesk, satu dibuat otomatis oleh risiko Tinggi pada aset layanan.
        $this->assertMatchesRegularExpression('/^Ada 2 tiket helpdesk untuk LYN-\S+ Layanan Website Resmi\./u', $a->answer('Tiket untuk layanan website resmi')['answer']);
    }

    public function test_status_decision_and_time_filters(): void
    {
        Storage::fake('local');
        EvidenceDocument::create(['module_key' => 'risiko', 'target_level' => 2, 'title' => 'Pedoman A', 'file_path' => 'a.pdf', 'original_name' => 'a.pdf']);
        EvidenceDocument::create(['module_key' => 'aset', 'target_level' => 2, 'title' => 'Inventaris B', 'file_path' => 'b.pdf', 'original_name' => 'b.pdf', 'reviewer_decision' => 'diterima']);
        $service = Service::create(['name' => 'Portal Uji', 'verification_status' => 'tervalidasi', 'dynamic_data' => []]);
        ServiceTicket::create(['service_id' => $service->id, 'legacy_code' => 'TIK-LAMA', 'issue' => 'Lama', 'reported_at' => now()->subMonths(2)]);
        ServiceTicket::create(['service_id' => $service->id, 'legacy_code' => 'TIK-BARU', 'issue' => 'Baru masuk', 'reported_at' => now()]);

        $a = new AppAssistant();
        $this->assertStringStartsWith('Ada 1 bukti dukung yang belum diputuskan admin.', $a->answer('Bukti dukung yang belum diputuskan')['answer']);
        $this->assertStringStartsWith('Ada 1 bukti dukung untuk Manajemen Aset.', $a->answer('Bukti dukung manajemen aset')['answer']);
        $week = $a->answer('Tiket yang masuk minggu ini');
        $this->assertStringStartsWith('Ada 1 tiket helpdesk yang masuk minggu ini', $week['answer']);
        $this->assertSame(['TIK-BARU · Baru masuk'], array_column($week['links'], 'title'));
    }

    public function test_change_history_and_module_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'admin_module' => 'risiko', 'name' => 'Bu Admin Risiko']);
        $this->actingAs($admin);
        $risk = $this->risk('RSK-UJI-0001', 'Peretasan website resmi', 'sedang');
        $risk->update(['risk_owner' => 'Kabid Aptika']);

        $history = (new AppAssistant())->answer('Siapa yang terakhir mengubah risiko RSK-UJI-0001?');
        $this->assertStringContainsString('diubah oleh Bu Admin Risiko', $history['answer']);
        $this->assertStringContainsString('risk_owner', $history['answer']);

        $this->assertStringContainsString('Bu Admin Risiko', (new AppAssistant())->answer('Siapa admin modul risiko?')['answer']);
    }

    public function test_questions_outside_the_app_are_refused_without_asking_the_llm(): void
    {
        config(['ai.provider' => 'ollama']);
        Http::fake([
            '*/api/tags' => Http::response(['models' => [['name' => 'llama3.2:latest']]]),
            '*/api/generate' => Http::response(['response' => 'Cuaca hari ini hujan lebat, suhu 22 derajat.']),
        ]);

        $result = (new AppAssistant())->answer('Cuaca hari ini gimana?');

        $this->assertSame(AppAssistant::OUT_OF_SCOPE, $result['answer']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/api/generate'));
    }
}
