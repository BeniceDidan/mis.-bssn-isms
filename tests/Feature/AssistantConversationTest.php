<?php

namespace Tests\Feature;

use App\Livewire\Assistant;
use App\Models\Asset;
use App\Models\EvidenceDocument;
use App\Models\KnowledgeAsset;
use App\Models\Risk;
use App\Models\Service;
use App\Models\User;
use App\Services\Ai\AppAssistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Asisten sebagai teman bicara dan penunjuk jalan (masukan pengguna 26 Nov 2026):
 * sapaan, "semuanya" / "iya kasih ke aku" yang merujuk ke permintaan sebelumnya,
 * dokumen per modul, tombol yang mengantar ke halaman, dan penjelasan baku.
 */
class AssistantConversationTest extends TestCase
{
    use RefreshDatabase;

    private function risk(string $code, string $level, ?int $assetId = null): Risk
    {
        return Risk::create([
            'risk_code' => $code, 'title' => "Risiko {$code}", 'category' => 'teknis', 'asset_id' => $assetId,
            'likelihood' => $level, 'impact' => $level, 'risk_level' => $level, 'status' => 'open',
        ]);
    }

    public function test_greetings_and_thanks_get_a_friendly_answer_with_examples(): void
    {
        $a = new AppAssistant();

        $hello = $a->answer('halo');
        $this->assertStringStartsWith('Halo! Saya Asisten BSSN ISMS.', $hello['answer']);
        $this->assertNotEmpty($hello['suggestions']);
        $this->assertStringStartsWith('Selamat pagi!', $a->answer('selamat pagi')['answer']);
        $this->assertStringStartsWith('Sama-sama.', $a->answer('makasih ya')['answer']);
        $this->assertStringContainsString('Yang bisa saya bantu', $a->answer('kamu bisa apa?')['answer']);
        $this->assertNotSame(AppAssistant::OUT_OF_SCOPE, $a->answer('ok')['answer']);
    }

    public function test_short_replies_continue_the_previous_request_in_full(): void
    {
        foreach (range(1, 8) as $i) {
            $this->risk(sprintf('RSK-UJI-%04d', $i), 'kritis');
        }
        $a = new AppAssistant();

        $first = $a->answer('berapa risiko kritis?');
        $this->assertStringStartsWith('Ada 8 risiko level kritis. Yang tampil 6 teratas.', $first['answer']);
        $this->assertCount(6, $first['links']);

        $history = [['q' => $first['topic'], 'a' => $first['answer']]];
        foreach (['semuanya', 'iya kasih ke aku'] as $reply) {
            $more = $a->answer($reply, null, $history);
            $this->assertCount(8, $more['links'], $reply);
            $this->assertTrue($more['expand']);
        }

        $none = $a->answer('iya', null, [['q' => 'halo', 'a' => 'Halo!']]);
        $this->assertNotSame(AppAssistant::OUT_OF_SCOPE, $none['answer']);
    }

    public function test_follow_ups_keep_the_topic_across_several_messages(): void
    {
        $this->risk('RSK-UJI-0001', 'kritis');
        $this->risk('RSK-UJI-0002', 'tinggi');
        $a = new AppAssistant();

        $history = [];
        foreach (['berapa risiko kritis?', 'tampilkan', 'yang tinggi?'] as $q) {
            $r = $a->answer($q, null, $history);
            $history[] = ['q' => $r['topic'] ?? $q, 'a' => $r['answer']];
        }
        $this->assertSame('Ada 1 risiko level tinggi.', $r['answer']);

        $open = $a->answer('buka halamannya', null, $history);
        $this->assertSame(route('risks.index'), $open['go']);
    }

    public function test_navigation_requests_open_the_page_and_offer_to_point_at_the_button(): void
    {
        $a = new AppAssistant();

        $go = $a->answer('bawa saya ke halaman risiko');
        $this->assertSame(route('risks.index'), $go['go']);
        $this->assertContains(route('risks.index', ['tunjuk' => 'add-button']), array_column($go['actions'], 'url'));

        $where = $a->answer('tanya dokumen di mana?');
        $this->assertNull($where['go']);
        $this->assertStringContainsString('tab Tanya Dokumen', $where['answer']);
        $this->assertSame(route('knowledge.index', ['tab' => 'tanya']), $where['actions'][0]['url']);

        $add = $a->answer('di mana saya menambah aset?');
        $this->assertStringContainsString('Klik tombol Aset Baru', $add['answer']);
        $this->assertContains(route('assets.index', ['create' => 1]), array_column($add['actions'], 'url'));

        $report = $a->answer('saya mau lapor gangguan website');
        $this->assertStringContainsString('Tiket Baru', $report['answer']);
        $this->assertSame(route('services.index', ['tab' => 'helpdesk', 'tunjuk' => 'add-button']), $report['actions'][0]['url']);
    }

    public function test_documents_of_a_module_come_from_knowledge_and_evidence(): void
    {
        $asset = Asset::create(['asset_code' => 'AST-UJI-0001', 'category' => 'perangkat_keras', 'sub_classification' => 'Server', 'name' => 'Server Uji', 'criticality_level' => 'tinggi', 'verification_status' => 'tervalidasi', 'dynamic_data' => []]);
        $service = Service::create(['name' => 'Layanan Uji', 'verification_status' => 'tervalidasi', 'dynamic_data' => []]);
        $service->assets()->sync([$asset->id]);
        KnowledgeAsset::create(['record_code' => 'KNW-UJI-0001', 'asset_id' => $asset->id, 'title' => 'SOP Server Uji', 'document_path' => 'knowledge-documents/sop.pdf', 'verification_status' => 'tervalidasi', 'dynamic_data' => []]);
        KnowledgeAsset::create(['record_code' => 'KNW-UJI-0002', 'title' => 'Pedoman Cuti', 'document_path' => 'knowledge-documents/cuti.pdf', 'verification_status' => 'tervalidasi', 'dynamic_data' => []]);
        EvidenceDocument::create(['module_key' => 'layanan', 'target_level' => 2, 'title' => 'SOP Helpdesk', 'file_path' => 'h.pdf', 'original_name' => 'h.pdf']);

        $r = (new AppAssistant())->answer('kasih aku dokumen di manajemen layanan');

        $this->assertStringContainsString('Yang berhubungan dengan Manajemen Layanan ada 2 dokumen', $r['answer']);
        $titles = implode(' | ', array_column($r['links'], 'title'));
        $this->assertStringContainsString('SOP Server Uji', $titles);
        $this->assertStringContainsString('SOP Helpdesk', $titles);
        $this->assertStringNotContainsString('Pedoman Cuti', $titles);
    }

    public function test_confusing_questions_get_plain_fixed_explanations(): void
    {
        $a = new AppAssistant();

        $this->assertStringContainsString('Sistem Pemerintahan Berbasis Elektronik', $a->answer('Apa itu SPBE?')['answer']);
        $this->assertStringContainsString('Alurnya dimulai dari aset', $a->answer('Alur aplikasi ini bagaimana?')['answer']);
        $this->assertStringContainsString('Ada delapan modul', $a->answer('Apa saja modul di aplikasi ini?')['answer']);
        $this->assertStringContainsString('Mulai dari Manajemen Aset', $a->answer('Modul mana yang harus diisi duluan?')['answer']);
        $this->assertStringContainsString('data contoh', $a->answer('Apakah data di aplikasi ini asli?')['answer']);
        $this->assertStringContainsString('P1 Kritis', $a->answer('P1 itu apa?')['answer']);
        $this->assertStringContainsString('Tersambung ke', $a->answer('Apa fungsi manajemen perubahan?')['answer']);
        $this->assertStringContainsString('Level 2 Membangun', $a->answer('Level 1 sampai 5 artinya apa?')['answer']);
    }

    public function test_quoted_button_names_missing_from_the_material_count_as_invented(): void
    {
        $this->assertTrue(AppAssistant::invents('Klik tombol "Laporkan" lalu "Kirim".', 'Klik Tiket Baru lalu Simpan.'));
        $this->assertFalse(AppAssistant::invents('Klik "Tiket Baru".', 'Klik Tiket Baru lalu Simpan.'));
    }

    public function test_the_panel_keeps_the_topic_and_moves_to_the_page(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']));
        $this->risk('RSK-UJI-0001', 'kritis');

        Livewire::test(Assistant::class)
            ->call('send', 'berapa risiko kritis?')
            ->call('send', 'bawa saya ke sana')
            ->assertDispatched('assistant-go', url: route('risks.index'))
            ->assertSet('messages.1.topic', 'berapa risiko kritis');
    }
}
