<?php

namespace Tests\Feature;

use App\Livewire\Assistant;
use App\Models\Asset;
use App\Models\Change;
use App\Models\EvidenceDocument;
use App\Models\ImportLog;
use App\Models\KnowledgeAsset;
use App\Models\Risk;
use App\Models\Service;
use App\Models\User;
use App\Services\Ai\AppAssistant;
use App\Support\ResearchLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Asisten: titik integrasi (modul, data, berkas yang baru diunggah) dan
 * riset rujukan di docs/riset. Berjalan tanpa LLM (AI_PROVIDER=none).
 */
class AssistantConnectionsTest extends TestCase
{
    use RefreshDatabase;

    private string $researchDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Riset tiruan: README masih draf, berkas 04 sudah ditinjau.
        $this->researchDir = storage_path('framework/testing/riset-' . uniqid());
        File::ensureDirectoryExists($this->researchDir);
        File::put($this->researchDir . '/README.md', implode("\n", [
            '# Riset Rujukan — STATUS: DRAF, BELUM DITINJAU',
            '',
            '## Temuan',
            '',
            '1. **Perpres 95/2018 masih berlaku** dan Pasal 46-nya memuat 8 manajemen.',
            '2. **PermenPANRB 59/2020 sudah dicabut** dan diganti PermenPANRB 8/2026.',
        ]));
        File::put($this->researchDir . '/04-rujukan-per-modul.md', implode("\n", [
            '# 04 · Rujukan per Modul — DITINJAU',
            '',
            '## 1. Daftar sumber',
            '',
            '### Peraturan (status dicek 10 Nov 2026)',
            '',
            '| Kode | Peraturan | Status | Tautan |',
            '|---|---|---|---|',
            '| P1 | Perpres 95/2018 tentang SPBE | Berlaku | https://contoh.go.id/perpres-95 |',
            '| P2 | **PermenPANRB 5/2020** tentang Pedoman Manajemen Risiko SPBE | Berlaku | https://contoh.go.id/permen-5 |',
            '',
            '## 4. Manajemen Risiko',
            '',
            '| Fitur aplikasi | Rujukan | Status | Catatan |',
            '|---|---|---|---|',
            '| Skala kemungkinan & dampak 4 tingkat | P2 Lampiran Bab III huruf B angka 8 | ✅ | **Diperbolehkan.** |',
            '| Kategori risiko | P2 Lampiran Bab III huruf B angka 6 | 🔶 | Berbeda dengan pedoman. |',
            '| Prioritas tiket P1–P4 | P1 Pasal 54 | 🟡 | Sebagian. |',
        ]));
        config(['ai.research_path' => $this->researchDir]);
        ResearchLibrary::forget();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->researchDir);
        ResearchLibrary::forget();

        parent::tearDown();
    }

    private function asset(string $code, string $name): Asset
    {
        return Asset::create(['asset_code' => $code, 'category' => 'perangkat_keras', 'sub_classification' => 'Server', 'name' => $name,
            'criticality_level' => 'tinggi', 'verification_status' => 'tervalidasi', 'dynamic_data' => []]);
    }

    public function test_module_questions_are_answered_from_the_integration_map(): void
    {
        $result = (new AppAssistant())->answer('Modul SDM terhubung ke modul apa?');

        $this->assertStringStartsWith('Manajemen SDM terhubung lewat Kode Personil.', $result['answer']);
        $this->assertStringContainsString('• Manajemen Risiko (otomatis):', $result['answer']);
        $this->assertStringNotContainsString('Asisten (', $result['answer']);
        $this->assertSame('Titik integrasi aplikasi', $result['engine']);
        $this->assertSame([route('hr-risks.index')], array_column($result['links'], 'url'));
    }

    public function test_scenario_questions_only_show_the_matching_flows(): void
    {
        $a = new AppAssistant();

        $critical = $a->answer('Kalau level risiko jadi kritis apa yang terjadi?')['answer'];
        $this->assertStringContainsString('Keamanan Informasi, Perubahan, Data & Informasi (otomatis)', $critical);
        $this->assertStringNotContainsString('Manajemen Aset (diisi manual)', $critical);

        $upload = $a->answer('Kalau saya unggah SOP di Pengetahuan, dipakai di mana saja?')['answer'];
        $this->assertStringContainsString('Tanya Dokumen, Analisis AI tiket, Asisten (otomatis)', $upload);
        $this->assertStringNotContainsString('Peta Keahlian', $upload);

        $this->assertStringContainsString('tautannya saja yang kosong', $a->answer('Kalau aset diarsipkan apa yang terjadi?')['answer']);
        $this->assertStringStartsWith('Semua modul tersambung lewat dua kunci', $a->answer('Integrasi antarmodul di aplikasi ini gimana?')['answer']);
        // Bukan pertanyaan integrasi: tidak dijawab dengan peta integrasi.
        $this->assertStringNotContainsString('Kode Aset', $a->answer('Apa yang terjadi kalau server mati?')['answer']);
        // "integrasi API" adalah nama layanan/dokumen, bukan pertanyaan titik integrasi (uji 18 Nov 2026).
        $this->assertStringNotContainsString('Kode Aset', $a->answer('Syarat permohonan integrasi API apa saja?')['answer']);
        // "Kenapa …" menanyakan alasan, bukan daftar data tiket P1.
        $this->assertDoesNotMatchRegularExpression('/^(Ada|Tidak ada) \d* ?tiket/u', $a->answer('Kenapa prioritas tiket masih P1-P4?')['answer']);
    }

    public function test_record_questions_list_what_the_record_is_linked_to(): void
    {
        $server = $this->asset('AST-UJI-0001', 'Server Basis Data Terpusat');
        $portal = Service::create(['name' => 'Layanan Website Resmi', 'verification_status' => 'tervalidasi', 'dynamic_data' => []]);
        $portal->assets()->sync([$server->id]);
        // Risiko Tinggi pada aset layanan: tindak lanjut otomatis di 4 modul.
        Risk::create(['risk_code' => 'RSK-UJI-0001', 'title' => 'Kegagalan disk server', 'category' => 'teknis', 'asset_id' => $server->id,
            'likelihood' => 'tinggi', 'impact' => 'tinggi', 'risk_level' => 'tinggi', 'status' => 'open']);

        $a = new AppAssistant();
        $asset = $a->answer('Aset Server Basis Data Terpusat terhubung ke apa saja?');
        $this->assertStringStartsWith('AST-UJI-0001 · Server Basis Data Terpusat terhubung ke:', $asset['answer']);
        $this->assertStringContainsString('• Risiko: RSK-UJI-0001 · Kegagalan disk server', $asset['answer']);
        $this->assertMatchesRegularExpression('/• Layanan: LYN-\S+ · Layanan Website Resmi/u', $asset['answer']);
        $this->assertStringContainsString('• Tiket helpdesk:', $asset['answer']);
        $this->assertSame('Titik integrasi dari basis data', $asset['engine']);

        $risk = $a->answer('Risiko RSK-UJI-0001 memicu apa saja?')['answer'];
        foreach (['Program keamanan (dibuat otomatis)', 'Perubahan (dibuat otomatis)', 'Data & informasi (dibuat otomatis)', 'Tiket helpdesk (dibuat otomatis)'] as $line) {
            $this->assertStringContainsString($line, $risk);
        }

        $lonely = Change::create(['title' => 'Ganti kabel', 'verification_status' => 'tervalidasi', 'dynamic_data' => []]);
        $this->assertStringContainsString('belum terhubung ke data lain. Isi Aset Terkait', $a->answer("Perubahan {$lonely->change_code} nyambung ke mana?")['answer']);
    }

    public function test_the_latest_upload_of_the_asker_is_explained(): void
    {
        $uploader = User::factory()->create(['role' => 'user']);
        User::factory()->create(['role' => 'admin', 'admin_module' => 'risiko', 'name' => 'Bu Admin Risiko']);
        $this->actingAs($uploader);

        KnowledgeAsset::create(['title' => 'SOP Pencadangan Data', 'document_path' => 'knowledge-documents/backup.pdf',
            'verification_status' => 'menunggu_verifikasi', 'dynamic_data' => []]);
        $pdf = (new AppAssistant())->answer('File yang barusan aku unggah nyambung ke mana?')['answer'];
        $this->assertMatchesRegularExpression('/^SOP Pencadangan Data \(\S+\) diunggah .+ di Manajemen Pengetahuan\./u', $pdf);
        $this->assertStringContainsString('• Belum tertaut ke aset.', $pdf);
        $this->assertStringContainsString('• Isinya belum dibaca', $pdf);
        $this->assertStringContainsString('• Kode Personil belum diisi.', $pdf);
        $this->assertStringContainsString('• Menunggu verifikasi admin Manajemen Pengetahuan.', $pdf);

        $this->travel(2)->minutes();
        $levels = [1 => ['unsur' => [['found' => true], ['found' => true]]], 2 => ['unsur' => [['found' => true], ['found' => true]]],
            3 => ['unsur' => [['found' => true], ['found' => true], ['found' => false]]]];
        EvidenceDocument::create(['module_key' => 'risiko', 'target_level' => 3, 'title' => 'Pedoman Manajemen Risiko', 'file_path' => 'a.pdf',
            'original_name' => 'a.pdf', 'score' => 86, 'achieved_level' => 2, 'checks' => ['levels' => $levels], 'uploaded_by' => $uploader->id]);

        $evidence = (new AppAssistant())->answer('Berkas yang tadi saya upload gimana?')['answer'];
        $this->assertStringContainsString('sebagai bukti dukung Manajemen Risiko.', $evidence);
        $this->assertStringContainsString('• Dinilai otomatis: skor 86 (6 dari 7 unsur), hasil Level 2 dari target Level 3.', $evidence);
        $this->assertStringContainsString('• Menunggu keputusan admin Manajemen Risiko: Bu Admin Risiko.', $evidence);
        $this->assertStringContainsString('Tidak menambah atau mengubah data di Manajemen Risiko', $evidence);
        // Bisa juga meminta jenis tertentu.
        $this->assertStringStartsWith('SOP Pencadangan Data', (new AppAssistant())->answer('PDF pengetahuan yang barusan saya unggah nyambung ke mana?')['answer']);

        $this->actingAs(User::factory()->create(['role' => 'user']));
        $this->assertSame('Belum ada berkas yang diunggah dari akun ini.', (new AppAssistant())->answer('File yang barusan saya unggah nyambung ke mana?')['answer']);
    }

    public function test_the_latest_excel_import_is_explained(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);
        $server = $this->asset('AST-UJI-0001', 'Server Basis Data Terpusat');
        $this->travel(1)->minutes();

        $startedAt = now();
        Change::create(['title' => 'Upgrade RAM server', 'asset_id' => $server->id, 'verification_status' => 'tervalidasi', 'dynamic_data' => []]);
        Change::create(['title' => 'Ganti kabel', 'verification_status' => 'tervalidasi', 'dynamic_data' => []]);
        ImportLog::create(['user_id' => $user->id, 'module_key' => 'perubahan', 'file_name' => 'perubahan.xlsx', 'created_count' => 2,
            'updated_count' => 0, 'error_count' => 1, 'started_at' => $startedAt, 'finished_at' => now()]);

        $answer = (new AppAssistant())->answer('Impor Excel yang barusan saya lakukan masuk ke mana?')['answer'];
        $this->assertMatchesRegularExpression('/^Impor Excel Manajemen Perubahan \(perubahan\.xlsx\) pada .+: 2 data baru, 0 diperbarui, 1 baris gagal\./u', $answer);
        $this->assertStringContainsString('• 1 dari 2 perubahan tertaut otomatis ke aset; 1 tanpa aset', $answer);
    }

    public function test_every_excel_importer_records_its_import(): void
    {
        foreach ([\App\Livewire\Assets\AssetImporter::class, \App\Livewire\Changes\ChangeImporter::class, \App\Livewire\DataInformation\DataInformationImporter::class,
            \App\Livewire\HrRisks\HrRiskImporter::class, \App\Livewire\Knowledge\KnowledgeImporter::class, \App\Livewire\SecurityPrograms\SecurityProgramImporter::class,
            \App\Livewire\Services\ServiceImporter::class] as $importer) {
            $this->assertContains(\App\Livewire\Concerns\RecordsImports::class, class_uses($importer), $importer);
        }

        $user = User::factory()->create();
        $this->actingAs($user);
        $summary = new \App\Imports\ImportSummary();
        $summary->incrementCreated();
        $summary->addError('Aset', 3, 'Nama kosong');
        $recorder = new class
        {
            use \App\Livewire\Concerns\RecordsImports;

            public function record(...$args): void
            {
                $this->recordImport(...$args);
            }
        };
        $recorder->record('aset', 'aset.xlsx', $summary, now()->subSeconds(5));

        $log = ImportLog::sole();
        $this->assertSame([$user->id, 'aset', 'aset.xlsx', 1, 0, 1], [$log->user_id, $log->module_key, $log->file_name, $log->created_count, $log->updated_count, $log->error_count]);
    }

    public function test_research_sources_basis_and_regulation_status(): void
    {
        $a = new AppAssistant();

        $all = $a->answer('Referensi apa saja yang sudah dicari?');
        $this->assertSame('Ada 2 sumber di riset: peraturan, standar, dan literatur.', $all['answer']);
        $this->assertSame(['web', 'web'], array_column($all['links'], 'kind'));
        $this->assertSame('Berlaku · contoh.go.id', $all['links'][0]['meta']);
        $this->assertTrue($all['draft']);

        $basis = $a->answer('Apa dasar aturan manajemen risiko?');
        $this->assertSame("Rujukan Manajemen Risiko: Perpres 95/2018 dan PermenPANRB 5/2020.\nHasil pengecekan 3 fitur: 1 sesuai, 1 sesuai sebagian, dan 1 perlu penyesuaian.", $basis['answer']);
        $this->assertFalse($basis['draft']); // berkas 04 sudah ditandai DITINJAU

        $status = $a->answer('Apakah Perpres 95/2018 masih berlaku?');
        $this->assertStringStartsWith("Perpres 95/2018 tentang SPBE\nStatus: Berlaku (dicek 10 Nov 2026).", $status['answer']);
        $this->assertStringContainsString('Pasal 46-nya memuat 8 manajemen', $status['answer']);
        $this->assertSame('https://contoh.go.id/perpres-95', $status['links'][0]['url']);
        $this->assertTrue($status['draft']); // kalimat tambahan dari README yang masih draf

        $this->assertStringContainsString('PermenPANRB 59/2020 sudah dicabut', $a->answer('Apakah PermenPANRB 59/2020 masih berlaku?')['answer']);
    }

    public function test_research_codes_are_expanded_but_ticket_priorities_are_not(): void
    {
        $rows = collect(ResearchLibrary::load()['units'])->where('kind', 'row')->pluck('text')->implode("\n");

        $this->assertStringContainsString("Skala kemungkinan & dampak 4 tingkat\nRujukan: PermenPANRB 5/2020 Lampiran Bab III huruf B angka 8\nStatus: Sesuai\nCatatan: Diperbolehkan.", $rows);
        $this->assertStringContainsString("Prioritas tiket P1–P4\nRujukan: Perpres 95/2018 Pasal 54", $rows);
    }

    public function test_the_real_research_folder_is_readable(): void
    {
        ResearchLibrary::forget();
        $lib = ResearchLibrary::load(base_path('docs/riset'));

        $this->assertArrayNotHasKey('referensi-simantik.md', $lib['files']);
        $this->assertGreaterThanOrEqual(20, count(ResearchLibrary::uniqueSources($lib['sources'])));
        foreach ($lib['sources'] as $source) {
            $this->assertNotSame('', $source['short']);
        }
        ResearchLibrary::forget();
    }

    public function test_widget_marks_research_answers_as_draft_and_opens_sources_in_a_new_tab(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']));

        Livewire::test(Assistant::class)
            ->call('send', 'Dasar aturan manajemen risiko')
            ->assertSee('Rujukan Manajemen Risiko')
            // Bukan pertanyaan lanjutan dari "manajemen risiko": daftar semua sumber.
            ->call('send', 'Referensi apa saja yang sudah dicari?')
            ->assertSee('Ada 2 sumber di riset')
            ->assertSee('Riset draf, belum ditinjau')
            ->assertSeeHtml('href="https://contoh.go.id/perpres-95"')
            ->assertSeeHtml('rel="noopener noreferrer"');
    }
}
