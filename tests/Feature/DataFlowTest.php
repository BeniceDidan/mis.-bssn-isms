<?php

namespace Tests\Feature;

use App\Livewire\Risks\RiskFormModal;
use App\Models\Asset;
use App\Models\Risk;
use App\Models\Service;
use App\Models\User;
use App\Services\Ai\AppAssistant;
use App\Support\IntegrationMap;
use App\Support\SaveNotice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Alur data antarmodul yang terlihat pengguna (19 Nov 2026, permintaan
 * "ini ke mana habis ini ke mana"): pop-out Alur Data, tabel di Panduan,
 * pesan setelah menyimpan, dan jawaban Asisten. Dasar: docs/riset/06.
 */
class DataFlowTest extends TestCase
{
    use RefreshDatabase;

    private function asset(): Asset
    {
        return Asset::create(['asset_code' => 'AST-UJI-0001', 'category' => 'perangkat_lunak', 'sub_classification' => 'Aplikasi',
            'name' => 'Portal Uji', 'criticality_level' => 'sedang', 'verification_status' => 'tervalidasi', 'dynamic_data' => []]);
    }

    public function test_every_flow_says_its_direction_and_kind(): void
    {
        foreach (array_keys(IntegrationMap::MAP) as $key) {
            foreach (IntegrationMap::for($key)['flows'] as $flow) {
                $this->assertContains($flow['dir'], ['in', 'out'], "{$key}: {$flow['to']}");
                $this->assertContains($flow['type'], ['auto', 'manual'], "{$key}: {$flow['to']}");
            }
        }
        $this->assertSame('dari Manajemen Risiko', IntegrationMap::direction(['dir' => 'in', 'to' => 'Manajemen Risiko']));
    }

    public function test_module_page_and_guide_show_where_data_goes(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']));

        $this->get(route('risks.index'))->assertOk()
            ->assertSee('Alur Data')
            ->assertSee('Dikirim ke modul lain')
            ->assertSee('Datang dari modul lain')
            ->assertDontSee('Titik Integrasi');

        $this->get(route('guide.index'))->assertOk()
            ->assertSee('Setelah Anda menyimpan, datanya ke mana')
            ->assertSee(IntegrationMap::EVENTS[0]['action'])
            ->assertSee('Alur per modul');
    }

    public function test_saving_a_critical_risk_says_where_the_follow_ups_went(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']));
        $asset = $this->asset();
        $service = Service::create(['name' => 'Layanan Portal', 'verification_status' => 'tervalidasi', 'dynamic_data' => []]);
        $service->assets()->sync([$asset->id]);

        Livewire::test(RiskFormModal::class)
            ->call('open')
            ->set('title', 'Portal diretas')
            ->set('asset_id', $asset->id)
            ->set('category', 'teknis')
            ->set('likelihood', 'kritis')
            ->set('impact', 'kritis')
            ->set('status', 'open')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: 'Risiko baru ditambahkan. Menunggu verifikasi admin Manajemen Risiko. '
                . 'Muncul juga di Riwayat Terkait aset Portal Uji. '
                . 'Karena levelnya Kritis, tindak lanjut dibuat di Keamanan Informasi, Perubahan, Data & Informasi, dan 1 tiket di Layanan.');
    }

    public function test_save_notice_stays_short_when_nothing_flows(): void
    {
        $since = SaveNotice::since();
        $risk = Risk::create(['risk_code' => 'RSK-UJI-0009', 'title' => 'Listrik padam', 'category' => 'teknis', 'likelihood' => 'rendah',
            'impact' => 'rendah', 'risk_level' => 'rendah', 'status' => 'open', 'verification_status' => 'tervalidasi']);

        $this->assertSame('Risiko baru ditambahkan.', SaveNotice::make('Risiko baru ditambahkan.', 'risiko', $risk, $since));
    }

    public function test_assistant_answers_flow_and_term_questions_without_the_llm(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']));
        $a = new AppAssistant();

        $saved = $a->answer('Habis saya simpan risiko, datanya kemana?');
        $this->assertStringStartsWith('Setelah disimpan di Manajemen Risiko:', $saved['answer']);
        $this->assertStringContainsString('1 usulan di Perubahan', $saved['answer']);
        $this->assertSame('Alur data aplikasi', $saved['engine']);

        $mttr = $a->answer('MTTR itu apa?');
        $this->assertStringContainsString('Rata-rata Waktu Pemulihan', $mttr['answer']);
        $this->assertSame('Panduan aplikasi', $mttr['engine']);

        $panel = $a->answer('Riwayat terkait itu apa?');
        $this->assertStringContainsString('Riwayat Terkait', $panel['answer']);
        $this->assertSame('Panduan aplikasi', $panel['engine']);

        // "Cara impor" tetap dijawab panduan cara pakai, bukan alur setelah impor.
        $this->assertStringStartsWith('Klik Impor Excel', $a->answer('Bagaimana cara impor data aset dari Excel?')['answer']);
    }
}
