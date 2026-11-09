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
}
