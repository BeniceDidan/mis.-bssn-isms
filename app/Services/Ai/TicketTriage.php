<?php

namespace App\Services\Ai;

use App\Models\AiLog;
use App\Models\Service;
use Illuminate\Support\Str;

/**
 * Triase tiket helpdesk: kategori, dampak, urgensi, prioritas, draf
 * balasan, dan langkah awal untuk operator/programmer.
 *
 * Prioritas SELALU dihitung dari matriks dampak × urgensi di
 * config/helpdesk.php (bukan ditebak LLM), supaya bisa dijelaskan. LLM
 * hanya membantu memilih kategori untuk teks yang ambigu, menaikkan
 * dampak/urgensi bila menemukan alasan, dan menulis draf balasan.
 */
class TicketTriage
{
    private const RANK = ['rendah' => 1, 'sedang' => 2, 'tinggi' => 3, 'kritis' => 4];

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
        $reasons = $rules['reasons'];
        $category = $rules['category'];
        $impact = $rules['impact'];
        $urgency = $rules['urgency'];
        $llmReply = null;

        $llm = $this->askLlm($issue, $service);
        if ($llm !== null) {
            $engine = 'Mesin aturan + ' . $this->llm->chatEngineName();

            if ($rules['category_strength'] < 2 && $llm['category'] && $llm['category'] !== $category) {
                $category = $llm['category'];
                $reasons[] = 'Kategori dipilih AI karena kata kunci kurang jelas.';
            }
            if ($llm['impact'] && self::RANK[$llm['impact']] > self::RANK[$impact]) {
                $impact = $llm['impact'];
                $reasons[] = 'Dampak dinaikkan AI: ' . ($llm['reason'] ?: 'berdasarkan isi tiket') . '.';
            }
            if ($llm['urgency'] && self::RANK[$llm['urgency']] > self::RANK[$urgency]) {
                $urgency = $llm['urgency'];
            }
            $llmReply = $llm['reply'];
        }

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

    /** Prioritas dari dampak & urgensi yang diisi manual. */
    public static function priorityFor(?string $impact, ?string $urgency): ?string
    {
        if (! isset(self::RANK[$impact ?? '']) || ! isset(self::RANK[$urgency ?? ''])) {
            return null;
        }

        return config("helpdesk.priority_matrix.{$impact}.{$urgency}");
    }

    /**
     * @return array{category: string, category_strength: int, impact: string, urgency: string, reasons: array<int, string>}
     */
    public function ruleBased(string $issue, ?Service $service = null): array
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

    private function contains(string $flatText, string $keyword): bool
    {
        $pattern = '/(?<![\p{L}\p{N}])' . str_replace(' ', '\s+', preg_quote(mb_strtolower($keyword), '/')) . '(?![\p{L}\p{N}])/u';

        return (bool) preg_match($pattern, $flatText);
    }

    /** @return array{category: ?string, impact: ?string, urgency: ?string, reason: ?string, reply: ?string}|null */
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
            . 'Pilihan dampak dan urgensi: rendah, sedang, tinggi, kritis.' . "\n"
            . 'Jawab dalam JSON: {"kategori": "kode", "dampak": "...", "urgensi": "...", "alasan": "1 kalimat", '
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
            'impact' => $pick($answer['dampak'] ?? null, array_keys(self::RANK)),
            'urgency' => $pick($answer['urgensi'] ?? null, array_keys(self::RANK)),
            'reason' => is_string($answer['alasan'] ?? null) ? Str::limit(trim($answer['alasan'], " ."), 160, '') : null,
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
