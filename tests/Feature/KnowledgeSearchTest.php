<?php

namespace Tests\Feature;

use App\Livewire\Knowledge\KnowledgeAsk;
use App\Models\KnowledgeAsset;
use App\Models\User;
use App\Services\Ai\KnowledgeIndexer;
use App\Services\Ai\KnowledgeSearch;
use App\Support\SimplePdf;
use Database\Seeders\DemoDocuments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class KnowledgeSearchTest extends TestCase
{
    use RefreshDatabase;

    private function seedDocuments(): void
    {
        foreach (DemoDocuments::knowledge() as $slug => $doc) {
            $path = "knowledge-documents/{$slug}.pdf";
            Storage::disk('public')->put($path, SimplePdf::make($doc['pages']));
            $asset = KnowledgeAsset::create([
                'title' => $doc['title'], 'document_path' => $path, 'document_original_name' => "{$slug}.pdf",
                'verification_status' => 'tervalidasi', 'dynamic_data' => [],
            ]);
            (new KnowledgeIndexer())->index($asset);
        }
    }

    public function test_indexing_chunks_pdf_and_tags_it_from_the_vocabulary(): void
    {
        Storage::fake('public');
        $this->seedDocuments();

        $server = KnowledgeAsset::where('title', 'like', 'SOP Permohonan Server%')->firstOrFail();
        $this->assertSame('kata_kunci', $server->index_status); // tanpa model embedding saat tes
        $this->assertGreaterThan(1, $server->chunks()->count());
        $this->assertContains('Server & Hosting', $server->ai_tags);
        $this->assertContains('SOP', $server->ai_tags);
        $this->assertSame(2, $server->chunks()->max('page_number'));
    }

    public function test_keyword_fallback_finds_the_server_sop_with_page_link(): void
    {
        Storage::fake('public');
        $this->seedDocuments();

        $result = (new KnowledgeSearch())->ask('Gimana prosedur permohonan server?');

        $this->assertNotEmpty($result['sources']);
        $this->assertSame('SOP Permohonan Server dan Hosting Aplikasi', $result['sources'][0]['title']);
        $this->assertStringContainsString('#page=', $result['sources'][0]['url']);
        $this->assertSame('pencarian kata kunci', $result['engine']);
    }

    public function test_semantic_search_and_answer_use_local_models_when_available(): void
    {
        Storage::fake('public');
        $this->seedDocuments();
        config(['ai.provider' => 'ollama']);

        // Vektor palsu: potongan SOP backup "dekat" dengan pertanyaan.
        Http::fake([
            '*/api/tags' => Http::response(['models' => [['name' => 'llama3.2:latest'], ['name' => 'bge-m3:latest']]]),
            '*/api/embed' => fn ($request) => Http::response(['embeddings' => array_map(
                fn ($text) => str_contains(mb_strtolower($text), 'pencadangan') || str_contains(mb_strtolower($text), 'cadangan') ? [1.0, 0.0] : [0.0, 1.0],
                (array) $request['input']
            )]),
            '*/api/generate' => Http::response(['response' => '1. Ajukan permohonan pemulihan melalui helpdesk [1].']),
        ]);
        (new KnowledgeIndexer())->index(KnowledgeAsset::where('title', 'like', 'SOP Pencadangan%')->firstOrFail());

        $result = (new KnowledgeSearch())->ask('cara minta cadangan data dipulihkan');

        $this->assertSame('SOP Pencadangan dan Pemulihan Data', $result['sources'][0]['title']);
        $this->assertStringContainsString('[1]', $result['answer']);
        $this->assertStringContainsString('bge-m3', $result['engine']);
    }

    public function test_all_close_chunks_of_the_best_document_are_used_and_weaker_documents_are_dropped(): void
    {
        Storage::fake('public');
        $this->seedDocuments();
        config(['ai.provider' => 'ollama']);

        // Semua potongan SOP server sangat mirip dengan pertanyaan, SOP
        // pencadangan cukup mirip (di atas ambang) tapi jauh di bawahnya.
        $mode = 'dokumen-utama';
        Http::fake([
            '*/api/tags' => Http::response(['models' => [['name' => 'llama3.2:latest'], ['name' => 'bge-m3:latest']]]),
            '*/api/embed' => function ($request) use (&$mode) {
                $vector = $mode === 'dokumen-lain' ? [0.6, 0.8] : [1.0, 0.0];

                return Http::response(['embeddings' => array_map(fn () => $vector, (array) $request['input'])]);
            },
            '*/api/generate' => Http::response(['response' => 'Syaratnya tercantum di SOP [1].']),
        ]);
        $server = KnowledgeAsset::where('title', 'like', 'SOP Permohonan Server%')->firstOrFail();
        (new KnowledgeIndexer())->index($server);
        $mode = 'dokumen-lain';
        (new KnowledgeIndexer())->index(KnowledgeAsset::where('title', 'like', 'SOP Pencadangan%')->firstOrFail());
        $mode = 'pertanyaan';

        $result = (new KnowledgeSearch())->ask('qqq');

        $chunkCount = $server->chunks()->count();
        $this->assertGreaterThan(2, $chunkCount); // dulu dibatasi 2 potongan per dokumen
        $this->assertCount(min(5, $chunkCount), $result['sources']);
        $this->assertSame([$server->title], collect($result['sources'])->pluck('title')->unique()->values()->all());
    }

    public function test_small_model_formatting_habits_are_tidied(): void
    {
        $this->assertSame('5 hari kerja sejak persyaratan lengkap. [1]', KnowledgeSearch::tidy("Jawaban: 5 hari kerja sejak persyaratan lengkap.\nDaftar:\n[1]"));
        $this->assertSame("Syaratnya:\n1. Surat permohonan [1]", KnowledgeSearch::tidy("Syaratnya:\n\n1. Surat permohonan\n[1]"));
        $this->assertSame('Tidak ditemukan di dokumen yang tersedia.', KnowledgeSearch::tidy("Jawaban: Tidak ditemukan di dokumen yang tersedia.\nDaftar:\n- [1] Waktu layanan 5 hari kerja."));
    }

    public function test_archived_documents_are_not_answered_from(): void
    {
        Storage::fake('public');
        $this->seedDocuments();
        KnowledgeAsset::where('title', 'like', 'SOP Permohonan Server%')->firstOrFail()->delete();

        $titles = collect((new KnowledgeSearch())->ask('permohonan server')['sources'])->pluck('title');

        $this->assertNotContains('SOP Permohonan Server dan Hosting Aplikasi', $titles);
    }

    public function test_ask_tab_renders_answer_and_sources(): void
    {
        Storage::fake('public');
        $this->seedDocuments();
        $this->actingAs(User::factory()->create(['role' => 'user']));

        Livewire::test(KnowledgeAsk::class)
            ->set('question', 'prosedur penanganan insiden siber')
            ->call('ask')
            ->assertSee('Prosedur Penanganan Insiden Siber')
            ->assertSee('Buka PDF');
    }
}
