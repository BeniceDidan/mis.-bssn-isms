<?php

namespace App\Services\Ai;

use App\Models\AiLog;
use App\Models\Risk;
use App\Models\Service;
use BackedEnum;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Triase tiket helpdesk: kategori, dampak, urgensi, prioritas, draf
 * balasan, dan langkah awal untuk operator/programmer.
 *
 * Dampak, urgensi, dan prioritas SELALU dihitung dari aturan di
 * config/helpdesk.php (kata kunci, sinyal, kritikalitas aset, matriks
 * dampak × urgensi), bukan ditebak LLM, supaya bisa dijelaskan. LLM hanya
 * membantu memilih kategori untuk teks yang ambigu dan menulis kalimat
 * pembuka draf balasan. Uji 13 November 2026: saat LLM masih boleh
 * menaikkan dampak/urgensi, permintaan API dan permintaan data biasa naik
 * ke P2 tanpa alasan yang masuk akal, jadi kewenangan itu dicabut.
 */
class TicketTriage
{
    private const RANK = ['rendah' => 1, 'sedang' => 2, 'tinggi' => 3, 'kritis' => 4];

    /**
     * SOP hanya disarankan bila potongannya cukup mirip dengan isi laporan.
     * Isi tiket biasanya kalimat panjang, jadi kemiripan bge-m3 dengan SOP
     * yang tepat pun sering hanya 0,45-0,5 (uji 18 Nov 2026: laporan judi
     * online vs Prosedur Penanganan Insiden Siber = 0,486).
     */
    private const SOP_MIN_SCORE = 0.45;

    public function __construct(private LocalLlm $llm = new LocalLlm())
    {
    }

    /**
     * @return array{category: string, impact_level: string, urgency: string, priority: string, draft_reply: string, triage: array}
     */
    public function triage(string $issue, ?Service $service = null, ?string $requester = null, ?string $ticketLabel = null): array
    {
        $startedAt = microtime(true);
        $rules = $this->ruleBased($issue, $service);
        $engine = 'Mesin aturan';
        $llmReply = null;

        $llm = $this->askLlm($issue, $service);
        if ($llm !== null) {
            $engine = 'Mesin aturan + ' . $this->llm->chatEngineName();

            // Kategori pilihan AI dihitung ulang dengan aturan yang sama,
            // jadi dampak & urgensinya tetap dari bawaan kategori + sinyal.
            if ($rules['category_strength'] < 2 && $llm['category'] && $llm['category'] !== $rules['category']) {
                $rules = $this->ruleBased($issue, $service, $llm['category']);
                $rules['reasons'][] = 'Kategori dipilih AI karena kata kunci kurang jelas.';
            }
            $llmReply = $llm['reply'];
        }

        $reasons = $rules['reasons'];
        $category = $rules['category'];
        $impact = $rules['impact'];
        $urgency = $rules['urgency'];
        $priority = config("helpdesk.priority_matrix.{$impact}.{$urgency}", 'P4');
        $target = config("helpdesk.priorities.{$priority}");
        $categoryDef = config("helpdesk.categories.{$category}");

        // Kalimat LLM hanya dipakai sebagai pembuka; daftar kelengkapan,
        // peringatan kata sandi, dan penutup selalu dari templat supaya
        // konsisten. Draf berisi isian kosong ("[nomor telepon]") dibuang.
        $opening = $llmReply && mb_strlen($llmReply) >= 40 && ! preg_match('/\[[^\]]{2,}\]/u', $llmReply)
            ? $llmReply
            : null;
        $reply = $this->templateReply($categoryDef, $priority, $target, $service, $requester, $ticketLabel, $opening);

        $triage = [
            'engine' => $engine,
            'reasons' => array_values(array_unique($reasons)),
            'steps' => $categoryDef['steps'],
            'target_response' => $target['response'],
            'target_resolution' => $target['resolution'],
            'related' => $this->related($issue, $service),
            'at' => now()->toIso8601String(),
        ];

        AiLog::record(
            'triase_tiket',
            $engine,
            null,
            Str::limit($issue, 500),
            json_encode(compact('category', 'impact', 'urgency', 'priority'), JSON_UNESCAPED_UNICODE),
            $startedAt,
        );

        return [
            'category' => $category,
            'impact_level' => $impact,
            'urgency' => $urgency,
            'priority' => $priority,
            'draft_reply' => $reply,
            'triage' => $triage,
        ];
    }

    /**
     * Titik integrasi ke modul lain, supaya petugas helpdesk tidak mencari
     * sendiri: aset yang menopang layanan (Manajemen Aset), risiko yang sudah
     * terdaftar pada aset itu (Manajemen Risiko), dan SOP yang paling dekat
     * dengan isi laporan (Manajemen Pengetahuan, lewat pencarian Tanya
     * Dokumen). Hanya membaca; tidak ada data yang diubah.
     *
     * @return array{assets: array<int, array>, risks: array<int, array>, documents: array<int, array>}
     */
    public function related(string $issue, ?Service $service = null): array
    {
        $value = fn ($level) => $level instanceof BackedEnum ? $level->value : $level;
        $assets = $service ? $service->assets : collect();

        $risks = $assets->isEmpty() ? collect() : Risk::query()
            ->whereIn('asset_id', $assets->pluck('id'))
            ->where(fn ($q) => $q->whereNull('status')->orWhere('status', '!=', 'closed'))
            ->get()
            ->sortByDesc(fn ($risk) => self::RANK[$value($risk->risk_level)] ?? 0)
            ->take(3);

        try {
            $hits = (new KnowledgeSearch($this->llm))->retrieve($issue, 3)
                ->filter(fn ($hit) => $hit['score'] >= self::SOP_MIN_SCORE);
        } catch (Throwable) {
            $hits = collect();
        }

        return [
            'assets' => $assets
                ->sortByDesc(fn ($asset) => self::RANK[$value($asset->criticality_level)] ?? 0)
                ->take(4)
                ->map(fn ($asset) => [
                    'code' => $asset->asset_code,
                    'name' => $asset->name,
                    'level' => $value($asset->criticality_level),
                    'url' => route('assets.index', ['search' => $asset->asset_code]),
                ])->values()->all(),
            'risks' => $risks->map(fn ($risk) => [
                'code' => $risk->risk_code,
                'title' => $risk->title,
                'level' => $value($risk->risk_level),
                'url' => route('risks.index', ['search' => $risk->risk_code]),
            ])->values()->all(),
            'documents' => $hits->unique(fn ($hit) => $hit['chunk']->knowledge_asset_id)->take(2)->map(fn ($hit) => [
                'code' => $hit['chunk']->knowledgeAsset->record_code,
                'title' => $hit['chunk']->knowledgeAsset->title,
                'page' => $hit['chunk']->page_number,
                'url' => $hit['chunk']->knowledgeAsset->document_path
                    ? Storage::disk('public')->url($hit['chunk']->knowledgeAsset->document_path) . ($hit['chunk']->page_number ? '#page=' . $hit['chunk']->page_number : '')
                    : route('knowledge.index', ['search' => $hit['chunk']->knowledgeAsset->record_code, 'tab' => 'aset']),
            ])->values()->all(),
        ];
    }

    /** Prioritas dari dampak & urgensi yang diisi manual. */
    public static function priorityFor(?string $impact, ?string $urgency): ?string
    {
        if (! isset(self::RANK[$impact ?? '']) || ! isset(self::RANK[$urgency ?? ''])) {
            return null;
        }

        return config("helpdesk.priority_matrix.{$impact}.{$urgency}");
    }

    /**
     * $category diisi bila kategorinya sudah dipilih (oleh AI untuk teks
     * yang kata kuncinya lemah); dampak & urgensi tetap dihitung di sini.
     *
     * @return array{category: string, category_strength: int, impact: string, urgency: string, reasons: array<int, string>}
     */
    public function ruleBased(string $issue, ?Service $service = null, ?string $category = null): array
    {
        $text = EvidenceAssessor::flatten($issue);
        $reasons = [];

        $best = 'lainnya';
        $bestScore = 0;
        $bestHits = [];
        foreach (config('helpdesk.categories') as $key => $def) {
            $score = 0;
            $hits = [];
            foreach ($def['keywords'] as $keyword) {
                if ($this->contains($text, $keyword)) {
                    $score += substr_count($keyword, ' ') + 1; // frasa lebih panjang = sinyal lebih kuat
                    $hits[] = $keyword;
                }
            }
            if ($score > $bestScore) {
                [$best, $bestScore, $bestHits] = [$key, $score, $hits];
            }
        }

        if ($category !== null && config("helpdesk.categories.{$category}") !== null) {
            [$best, $bestHits] = [$category, []];
        }

        $def = config("helpdesk.categories.{$best}");
        if ($bestHits) {
            $reasons[] = 'Kategori ' . $def['label'] . ' dari kata: ' . implode(', ', array_slice($bestHits, 0, 3)) . '.';
        }

        $impact = $def['impact'];
        foreach (config('helpdesk.impact_signals') as $level => $signals) {
            foreach ($signals as $signal) {
                if ($this->contains($text, $signal) && self::RANK[$level] > self::RANK[$impact]) {
                    $impact = $level;
                    $reasons[] = "Dampak {$level}: menyebut \"{$signal}\".";
                }
            }
        }

        // Integrasi dengan Manajemen Aset: gangguan/bug/insiden pada layanan
        // yang bertumpu pada aset berkritikalitas tinggi/kritis menaikkan
        // dampak satu tingkat. Tidak berlaku untuk permintaan perorangan
        // (reset akun, fitur, data) — kritikalitas aset tidak relevan di sana.
        if ($service && ($def['asset_boost'] ?? false)) {
            $criticalAsset = $service->assets
                ->filter(fn ($a) => in_array($a->criticality_level?->value, ['tinggi', 'kritis'], true))
                ->first();
            if ($criticalAsset && $impact !== 'kritis') {
                $impact = array_search(self::RANK[$impact] + 1, self::RANK, true);
                $reasons[] = "Dampak dinaikkan: layanan bertumpu pada aset {$criticalAsset->asset_code} berkritikalitas {$criticalAsset->criticality_level->value}.";
            }
        }

        $urgency = $def['urgency'];
        foreach (config('helpdesk.urgency_signals') as $level => $signals) {
            foreach ($signals as $signal) {
                if ($this->contains($text, $signal) && self::RANK[$level] > self::RANK[$urgency]) {
                    $urgency = $level;
                    $reasons[] = "Urgensi {$level}: menyebut \"{$signal}\".";
                }
            }
        }

        return ['category' => $best, 'category_strength' => $bestScore, 'impact' => $impact, 'urgency' => $urgency, 'reasons' => $reasons];
    }

    /**
     * Kata-kata dalam frasa kata kunci boleh diselingi sampai dua kata lain
     * di klausa yang sama, supaya "akun saya terkunci" tetap cocok dengan
     * "akun terkunci". Tanda baca di antaranya memutus kecocokan.
     */
    private function contains(string $flatText, string $keyword): bool
    {
        $words = array_map(
            fn ($word) => preg_quote($word, '/'),
            preg_split('/\s+/u', mb_strtolower(trim($keyword)), -1, PREG_SPLIT_NO_EMPTY)
        );
        $pattern = '/(?<![\p{L}\p{N}])' . implode('(?:\s+[\p{L}\p{N}\-]+){0,2}\s+', $words) . '(?![\p{L}\p{N}])/u';

        return (bool) preg_match($pattern, $flatText);
    }

    /** @return array{category: ?string, reply: ?string}|null */
    private function askLlm(string $issue, ?Service $service): ?array
    {
        if (! $this->llm->chatAvailable()) {
            return null;
        }

        $categories = collect(config('helpdesk.categories'))->map(fn ($c, $k) => "{$k} = {$c['label']}")->implode('; ');
        $answer = $this->llm->generate(
            ($service ? "Layanan: {$service->name}\n" : '')
            . "Isi tiket: \"\"\"{$issue}\"\"\"\n\n"
            . "Pilihan kategori (pakai kodenya): {$categories}.\n"
            . 'Jawab dalam JSON: {"kategori": "kode", '
            . '"draf_balasan": "1-2 kalimat pembuka yang menanggapi isi tiket secara spesifik dan sopan; tanpa salam, tanpa menjanjikan waktu, tanpa isian kosong seperti [nama]"}',
            true,
            'Anda petugas helpdesk TIK di dinas pemerintahan Indonesia. Gunakan Bahasa Indonesia baku dan sopan. '
            . 'Jangan meminta kata sandi pemohon.'
        );

        if (! is_array($answer)) {
            return null;
        }

        $pick = fn ($value, array $allowed) => is_string($value) && in_array(Str::lower(trim($value)), $allowed, true) ? Str::lower(trim($value)) : null;
        $reply = is_string($answer['draf_balasan'] ?? null) ? trim($answer['draf_balasan']) : null;
        if ($reply && preg_match('/kata\s+sandi|password/iu', $reply) && preg_match('/kirim|berikan|sebutkan/iu', $reply)) {
            $reply = null; // jangan pernah memakai draf yang meminta kata sandi
        }

        return [
            'category' => $pick($answer['kategori'] ?? null, array_keys(config('helpdesk.categories'))),
            'reply' => $reply ? Str::limit($reply, 1200) : null,
        ];
    }

    private function templateReply(array $categoryDef, string $priority, array $target, ?Service $service, ?string $requester, ?string $ticketLabel, ?string $opening = null): string
    {
        $greeting = $requester ? "Yth. {$requester}," : 'Yth. Bapak/Ibu,';
        $about = $service
            ? ' terkait ' . (Str::startsWith(Str::lower($service->name), 'layanan') ? $service->name : "layanan {$service->name}")
            : '';
        $ticket = $ticketLabel ? " dengan nomor {$ticketLabel}" : '';
        $needs = collect($categoryDef['needs'])->map(fn ($n) => "- {$n}")->implode("\n");
        // Buang salam pembuka bawaan LLM supaya tidak dobel dengan $greeting.
        $opening = $opening ? trim(preg_replace('/^(yth\.?|halo|dear)[^\n,]*,?\s*/iu', '', $opening) ?? $opening) : null;

        $recorded = "sudah kami catat{$ticket} sebagai \"{$categoryDef['label']}\" dengan prioritas {$priority}; "
            . "target tanggapan pertama {$target['response']}.";

        return "{$greeting}\n\n"
            . ($opening
                ? "{$opening}\n\nLaporan{$about} {$recorded}\n\n"
                : "Terima kasih atas laporan Anda{$about}. Permintaan Anda {$recorded}\n\n")
            . "Agar dapat segera kami tindak lanjuti, mohon lengkapi:\n{$needs}\n\n"
            . "Demi keamanan, jangan kirimkan kata sandi melalui kanal apa pun.\n\n"
            . "Salam,\nTim Layanan TIK";
    }
}
