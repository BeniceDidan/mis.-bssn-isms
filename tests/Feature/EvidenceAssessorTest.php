<?php

namespace Tests\Feature;

use App\Livewire\Evidence\EvidenceDetail;
use App\Livewire\Evidence\EvidenceUploadModal;
use App\Models\EvidenceDocument;
use App\Models\User;
use App\Services\Ai\EvidenceAssessor;
use App\Support\SimplePdf;
use Database\Seeders\DemoDocuments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EvidenceAssessorTest extends TestCase
{
    use RefreshDatabase;

    private function pdfFor(string $slug): UploadedFile
    {
        $doc = DemoDocuments::evidence()[$slug];

        return UploadedFile::fake()->createWithContent("{$slug}.pdf", SimplePdf::make($doc['pages']));
    }

    public function test_risk_policy_without_mitigation_matrix_reaches_level_2_and_says_what_is_missing(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['role' => 'user']));

        Livewire::test(EvidenceUploadModal::class)
            ->set('module_key', 'risiko')
            ->set('target_level', 3)
            ->set('document', $this->pdfFor('pedoman-risiko'))
            ->call('save')
            ->assertHasNoErrors();

        $record = EvidenceDocument::firstOrFail();

        $this->assertSame(2, $record->achieved_level);
        $this->assertFalse($record->meetsTarget());
        $this->assertSame('Mesin aturan', $record->engine);
        $texts = collect($record->warnings)->pluck('text')->implode(' ');
        $this->assertStringContainsString('Dokumen sesuai untuk Level 2', $texts);
        $this->assertStringContainsString('matriks mitigasi', $texts);
        // Ada tulisan "ditandatangani secara elektronik" tapi tanpa tanda tangan digital tertanam.
        $this->assertStringContainsString('tidak tertanam', $texts);
        $this->assertTrue($record->checks['pengesahan']['penandatangan']['found']);
        Storage::disk('local')->assertExists($record->file_path);
    }

    public function test_score_origin_is_shown_as_found_out_of_total_elements(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['role' => 'user']));

        Livewire::test(EvidenceUploadModal::class)
            ->set('module_key', 'risiko')
            ->set('target_level', 3)
            ->set('document', $this->pdfFor('pedoman-risiko'))
            ->call('save');

        $record = EvidenceDocument::firstOrFail();
        $breakdown = $record->scoreBreakdown();

        $this->assertSame(['found' => 6, 'total' => 7], $breakdown);
        $this->assertSame(86, $record->score);

        Livewire::test(EvidenceDetail::class)
            ->call('open', $record->id)
            ->assertSee('6 dari 7 unsur')
            ->assertSee('Asal kelengkapan dan level')
            ->assertSee('Kelengkapan 86% = 6 dari 7 unsur Level 1–3 ditemukan di PDF.')
            ->assertSee('ditemukan kata')
            ->assertSee('tidak ada kata:');
    }

    public function test_complete_helpdesk_sop_meets_claimed_level(): void
    {
        $doc = DemoDocuments::evidence()['sop-helpdesk'];
        $pages = [];
        foreach ($doc['pages'] as $i => $text) {
            $pages[$i + 1] = $text;
        }

        $result = (new EvidenceAssessor())->evaluate([
            'ok' => true, 'error' => null, 'page_count' => count($pages), 'pages' => $pages,
            'images_per_page' => [1 => 0], 'digital_signature' => false, 'text_length' => 2000,
        ], 'layanan', 2);

        $this->assertSame(2, $result['achieved_level']);
        $this->assertSame(100, $result['score']);
        $this->assertSame('ok', $result['warnings'][0]['type']);
    }

    public function test_scanned_pdf_is_flagged_instead_of_scored(): void
    {
        $result = (new EvidenceAssessor())->evaluate([
            'ok' => true, 'error' => null, 'page_count' => 3, 'pages' => [1 => '', 2 => ' ', 3 => 'hal 3'],
            'images_per_page' => [1 => 1, 2 => 1, 3 => 1], 'digital_signature' => false, 'text_length' => 6,
        ], 'risiko', 2);

        $this->assertSame(0, $result['achieved_level']);
        $this->assertTrue($result['checks']['pdf']['scanned']);
        $this->assertSame('danger', $result['warnings'][0]['type']);
        $this->assertStringContainsString('hasil pindai', $result['warnings'][0]['text']);
    }

    public function test_score_is_shown_as_completeness_percentage_and_dash_for_unread_pdfs(): void
    {
        $read = (new EvidenceDocument())->forceFill(['score' => 86, 'checks' => ['pdf' => ['readable' => true, 'scanned' => false]]]);
        $scanned = (new EvidenceDocument())->forceFill(['score' => 20, 'checks' => ['pdf' => ['readable' => true, 'scanned' => true]]]);
        $unreadable = (new EvidenceDocument())->forceFill(['score' => 0, 'checks' => ['pdf' => ['readable' => false]]]);
        $notAssessed = (new EvidenceDocument())->forceFill(['score' => null]);

        $this->assertSame('86%', $read->completenessLabel());
        $this->assertSame('—', $scanned->completenessLabel());
        $this->assertSame('—', $unreadable->completenessLabel());
        $this->assertSame('—', $notAssessed->completenessLabel());
    }

    public function test_only_the_matching_module_admin_can_record_a_decision(): void
    {
        Storage::fake('local');
        $record = EvidenceDocument::create([
            'module_key' => 'risiko', 'target_level' => 2, 'title' => 'Uji', 'file_path' => 'x.pdf', 'original_name' => 'x.pdf',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin', 'admin_module' => 'aset']));
        Livewire::test(EvidenceDetail::class)
            ->call('open', $record->id)
            ->set('decision', 'diterima')
            ->call('saveDecision')
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'admin', 'admin_module' => 'risiko']));
        Livewire::test(EvidenceDetail::class)
            ->call('open', $record->id)
            ->set('decision', 'perlu_perbaikan')
            ->set('note', 'Lengkapi matriks mitigasi.')
            ->call('saveDecision')
            ->assertHasNoErrors();

        $this->assertSame('perlu_perbaikan', $record->fresh()->reviewer_decision);
    }

    public function test_evidence_file_requires_login(): void
    {
        $record = EvidenceDocument::create([
            'module_key' => 'risiko', 'target_level' => 2, 'title' => 'Uji', 'file_path' => 'x.pdf', 'original_name' => 'x.pdf',
        ]);

        $this->get(route('evidence.file', $record))->assertRedirect(route('login'));
    }

    public function test_tutorial_sample_pdf_is_served_to_logged_in_users_only(): void
    {
        $this->get(route('evidence.sample'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['role' => 'user']));
        $response = $this->get(route('evidence.sample'))->assertOk();

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_ai_pages_carry_the_tutorial_markers(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']));

        $this->get(route('evidence.index'))->assertOk()
            ->assertSee('data-tour="evidence-upload-button"', false)
            ->assertSee('data-tour="evidence-file"', false)
            ->assertSee("startAiTour('bukti')", false);
        $this->get(route('knowledge.index'))->assertOk()
            ->assertSee('data-tour="tab-tanya"', false)
            ->assertSee('data-tour="ask-input"', false)
            ->assertSee("startAiTour('tanya')", false);
        $this->get(route('services.index'))->assertOk()
            ->assertSee('data-tour="tab-helpdesk"', false)
            ->assertSee('data-tour="ticket-analyze"', false)
            ->assertSee("startAiTour('triase')", false);
    }
}
