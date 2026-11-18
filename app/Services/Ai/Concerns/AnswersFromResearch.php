<?php

namespace App\Services\Ai\Concerns;

use App\Services\Ai\LocalLlm;
use App\Support\ResearchLibrary;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Asisten: pertanyaan tentang riset rujukan di docs/riset (peraturan,
 * standar, literatur yang dikumpulkan dari web selama pengembangan).
 *
 * Jawaban diambil apa adanya dari tulisan riset, tidak dirangkai LLM: uji
 * 18 Nov 2026, llama3.2 menjawab "dasar aturan manajemen risiko" dengan
 * isi karangan ("kepentingan perusahaan"). Riset masih DRAF, jadi setiap
 * jawaban membawa penanda 'draft' selama berkasnya belum ditandai DITINJAU.
 */
trait AnswersFromResearch
{
    /** Kata riset/rujukan; tidak dipakai sebagai kata cari isi riset. */
    private const RESEARCH_WORDS = [
        'riset', 'rujukan', 'rujukannya', 'referensi', 'referensinya', 'literatur', 'literasi', 'regulasi', 'regulasinya', 'peraturan',
        'peraturannya', 'dasar', 'dasarnya', 'hukum', 'hukumnya', 'landasan', 'landasannya', 'aturan', 'aturannya', 'resmi', 'pedoman',
        'sumber', 'sumbernya', 'acuan', 'acuannya', 'dipakai', 'pakai', 'dicari', 'mencari', 'web', 'internet', 'diambil', 'berasal',
        'asal', 'tentang', 'fitur', 'dikumpulkan', 'kumpulkan', 'kenapa', 'mengapa', 'alasan', 'alasannya', 'kita', 'mana',
    ];

    /** Topik khusus → berkas/bagian riset. Dicek sebelum nama modul. */
    private const RESEARCH_TOPICS = [
        '/\b(rubrik|bukti dukung|evaluasi kinerja|indeks pemdi|level kematangan)\b/u' => ['01-rubrik-bukti-dukung.md', null],
        '/\b(rag|tanya dokumen|bge m3|bge-m3|embedding|llama|llama3 2|model ai|model bahasa|pencarian semantik|vektor)\b/u' => ['02-pengetahuan-rag.md', null],
        '/\b(triase|helpdesk|prioritas tiket|tiket)\b/u' => ['03-triase-helpdesk.md', null],
        '/\bfitur ai\b/u' => ['04-rujukan-per-modul.md', 'Fitur AI'],
    ];

    /** Modul asisten → bagian berkas 04. */
    private const RESEARCH_MODULE_SECTIONS = [
        'aset' => 'Manajemen Aset', 'risiko' => 'Manajemen Risiko', 'keamanan' => 'Manajemen Keamanan Informasi',
        'data_informasi' => 'Manajemen Data & Informasi', 'sdm' => 'Manajemen SDM', 'pengetahuan' => 'Manajemen Pengetahuan',
        'keahlian' => 'Manajemen Pengetahuan', 'perubahan' => 'Manajemen Perubahan', 'layanan' => 'Manajemen Layanan',
    ];

    private const RESEARCH_ENGINE = 'Riset rujukan (docs/riset)';

    /** 'strong': jelas menanyakan riset; 'weak': "kenapa…", bandingkan dengan panduan; null: bukan. */
    private static function researchIntent(string $text, bool $fileIntent): ?string
    {
        $strong = preg_match('/\b(riset|rujukan|rujukannya|referensi|referensinya|literatur|literasi|regulasi|regulasinya|peraturan|peraturannya|dasar hukum|landasan hukum|landasan|dasar aturan|dasar aturannya|aturan resmi|pedoman resmi|permen\w*|perpres|perban|permendagri|undang undang|iso|itil|nist|jurnal|paper|artikel|standar internasional|pasal|dicabut)\b/u', $text)
            || preg_match('/\bsumber(?:nya)?\b(?!\s+(?:ancaman|risiko|daya|dana|data|jawaban))/u', $text)
            || self::regulationNeedle($text) !== null;
        if ($strong && (! $fileIntent || preg_match('/\briset\b/u', $text))) {
            return 'strong';
        }
        if (preg_match('/\b(kenapa|mengapa|alasan|alasannya|apa dasar|dasarnya|diperbolehkan|dibolehkan|boleh|sesuai aturan|sesuai regulasi|sesuai pedoman|kesesuaian|resmi|disahkan)\b/u', $text)) {
            return 'weak';
        }

        return null;
    }

    /** "95/2018", "nomor 95 tahun 2018" → "95/2018"; "iso 27001" → "27001". */
    private static function regulationNeedle(string $text): ?string
    {
        if (preg_match('/(?<![\d\/])(\d{1,3})\s*\/\s*(\d{4})(?!\d)/u', $text, $m)
            || preg_match('/\b(?:nomor|no)\s*(\d{1,3})\s+(?:tahun|thn)\s+(\d{4})\b/u', $text, $m)
            || preg_match('/\b(\d{1,3})\s+tahun\s+(\d{4})\b/u', $text, $m)) {
            return $m[1] . '/' . $m[2];
        }
        if (preg_match('/\biso(?:\/iec)?\s*(\d{4,5}(?:-\d)?)\b/u', $text, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * @return array{answer: string, engine: string, links: array, draft: bool, score: float}|null
     */
    private function researchAnswer(string $text, ?string $moduleKey, ?array $queryVector, bool $strong): ?array
    {
        $lib = ResearchLibrary::load();
        if ($lib['units'] === []) {
            return null;
        }

        if (($needle = self::regulationNeedle($text)) !== null && ($answer = $this->regulationAnswer($lib, $needle, $text, $queryVector)) !== null) {
            return $answer;
        }

        $scope = $this->researchScope($text, $moduleKey, $lib);
        $specific = $this->researchSpecificTerms($text, $scope);
        $asksSources = (bool) preg_match('/\b(rujukan|rujukannya|referensi|referensinya|sumber|sumbernya|literatur|literasi|regulasi|peraturan|dasar|dasarnya|landasan|acuan|aturan|aturannya)\b/u', $text);
        $asksWhy = (bool) preg_match('/\b(kenapa|mengapa|alasan|alasannya|bagaimana)\b/u', $text);
        $asksCompliance = (bool) preg_match('/\b(sudah sesuai|sesuai aturan|sesuai regulasi|sesuai pedoman|kesesuaian|patuh)\b/u', $text);

        // "Referensi apa saja?", "Dasar aturan manajemen risiko?", "Sumber rubrik dari mana?",
        // "Manajemen layanan sudah sesuai aturan?"
        if ((($strong && $asksSources) || ($scope && $asksCompliance)) && ! $asksWhy && $specific === []) {
            return $scope ? $this->researchScopeAnswer($lib, $scope) : $this->researchListAnswer($lib);
        }

        // "Kenapa …" dijawab dengan alasan/catatan, bukan judul sebuah sumber.
        $kinds = $asksWhy ? ['bullet', 'para', 'row'] : null;
        $threshold = $queryVector ? 0.58 : 0.5;
        // Potongan dalam topik yang disebut didahulukan, kecuali potongan di luar topik jelas lebih cocok
        // (uji: "rubrik sudah resmi?" paling tepat dijawab temuan di README, bukan berkas 01).
        $hit = $this->bestResearchUnit($lib, $text, $queryVector, null, $kinds);
        $inScope = $scope ? $this->bestResearchUnit($lib, $text, $queryVector, $scope, $kinds) : null;
        if ($inScope && (! $hit || $inScope['score'] + 0.03 >= $hit['score'])) {
            $hit = $inScope;
        }
        if (! $hit || $hit['score'] < $threshold) {
            return null;
        }

        return $this->researchUnitAnswer($lib, $hit['unit'], $hit['score']);
    }

    /** @return array{file: string, section: ?string, label: string, words: array<int, string>}|null */
    private function researchScope(string $text, ?string $moduleKey, array $lib): ?array
    {
        foreach (self::RESEARCH_TOPICS as $pattern => [$file, $section]) {
            if (isset($lib['files'][$file]) && preg_match($pattern, $text, $m)) {
                return ['file' => $file, 'section' => $section, 'label' => $section ?? $lib['files'][$file]['title'], 'words' => explode(' ', $m[1])];
            }
        }
        $section = self::RESEARCH_MODULE_SECTIONS[$moduleKey ?? ''] ?? null;
        if ($section !== null && isset($lib['files']['04-rujukan-per-modul.md'])) {
            return ['file' => '04-rujukan-per-modul.md', 'section' => $section, 'label' => $section, 'words' => []];
        }

        return null;
    }

    /** Kata cari yang tersisa setelah kata riset, nama modul, dan nama topik dibuang. */
    private function researchSpecificTerms(string $text, ?array $scope): array
    {
        $moduleWords = collect(self::MODULES)->flatMap(fn ($d) => $d['words'])->flatMap(fn ($w) => explode(' ', $w))->all();
        $drop = array_merge(self::RESEARCH_WORDS, $moduleWords, $scope['words'] ?? [], ['aplikasi', 'ini', 'apa', 'saja', 'riset', 'masih', 'berlaku', 'sesuai', 'kesesuaian', 'patuh']);

        return array_values(array_filter(self::terms($text), fn ($t) => ! in_array($t, $drop, true)));
    }

    /** Sumber yang dirujuk di sebuah berkas/bagian, plus tabel sumber berkas itu bila cakupannya seluruh berkas. */
    private function researchScopeAnswer(array $lib, array $scope): array
    {
        $units = collect($lib['units'])->filter(fn ($u) => $u['file'] === $scope['file'] && ($scope['section'] === null || $u['section'] === $scope['section']));
        $refs = $units->flatMap(fn ($u) => $u['refs'])->unique()->all();
        $sources = collect($lib['sources'])->filter(fn ($s) => $s['file'] === $scope['file'] && in_array($s['id'], $refs, true));
        $sources = collect(ResearchLibrary::uniqueSources($sources->all()));

        $answer = $sources->isEmpty()
            ? "Riset belum mencatat rujukan untuk {$scope['label']}."
            : "Rujukan {$scope['label']}: " . self::joinNames($sources->pluck('short')->all()) . '.';

        $statuses = $units->filter(fn ($u) => $u['kind'] === 'row')
            ->map(fn ($u) => preg_match('/^Status: (.+)$/mu', $u['text'], $m) ? $m[1] : null)->filter();
        if ($statuses->isNotEmpty()) {
            $order = array_values(ResearchLibrary::STATUS_LABELS);
            $parts = $statuses->countBy()->sortBy(fn ($n, $label) => array_search($label, $order, true))
                ->map(fn ($n, $label) => $n . ' ' . Str::lower($label))->values()->all();
            $answer .= "\nHasil pengecekan {$statuses->count()} fitur: " . self::joinNames($parts) . '.';
        }

        return [
            'answer' => $answer,
            'engine' => self::RESEARCH_ENGINE,
            'links' => $this->researchLinks($sources),
            'draft' => $lib['files'][$scope['file']]['draft'] ?? true,
            'score' => 1.0,
        ];
    }

    private function researchListAnswer(array $lib): array
    {
        $sources = collect(ResearchLibrary::uniqueSources($lib['sources']))->filter(fn ($s) => $s['url'] !== null)->values();

        return [
            'answer' => 'Ada ' . $sources->count() . ' sumber di riset: peraturan, standar, dan literatur.',
            'engine' => self::RESEARCH_ENGINE,
            'links' => $this->researchLinks($sources, 40),
            'draft' => collect($lib['files'])->contains(fn ($f) => $f['draft']),
            'score' => 1.0,
        ];
    }

    private function regulationAnswer(array $lib, string $needle, string $text, ?array $queryVector): ?array
    {
        $iso = ! str_contains($needle, '/');
        $rows = collect(ResearchLibrary::uniqueSources($lib['sources']))->filter(fn ($s) => $iso
            ? str_contains(Str::lower($s['title']), $needle)
            : (bool) preg_match('/(?<![\d\/])' . preg_quote($needle, '/') . '(?!\d)/u', $s['short']))->values();

        // Kalimat riset lain yang menyebut peraturan itu ("Perpres 95/2018 masih berlaku …").
        $mention = $this->bestResearchUnit($lib, $text, $queryVector, null, ['bullet', 'para', 'row'],
            fn ($u) => (bool) preg_match('/(?<![\d\/])' . preg_quote($needle, '/') . '(?!\d)/u', $u['text']));

        if ($rows->isEmpty()) {
            return $mention ? $this->researchUnitAnswer($lib, $mention['unit'], 1.0) : null;
        }

        $source = $rows->first();
        $group = $this->sourceUnit($lib, $source)['subsection'] ?? null;
        $answer = $source['title']
            . ($source['status'] ? "\nStatus: {$source['status']}" . $this->checkedOn($lib, $source) . '.' : '')
            . ($source['note'] ? "\nIsi yang dipakai: {$source['note']}" : '')
            // Standar tanpa status: sebut alasan dirujuk ("Standar (dirujuk karena Perpres 95/2018 Pasal 46 …)").
            . (! $source['status'] && $group ? "\n{$group}" : '');
        if ($mention) {
            $answer .= "\n\n" . $mention['unit']['text'];
        }
        $files = collect([$source['file'], $mention['unit']['file'] ?? null])->filter();

        return [
            'answer' => $answer,
            'engine' => self::RESEARCH_ENGINE,
            'links' => $this->researchLinks($rows),
            'draft' => $files->contains(fn ($f) => $lib['files'][$f]['draft'] ?? true),
            'score' => 1.0,
        ];
    }

    /** "(dicek 10 Nov 2026)" dari judul bagian tabel peraturan. */
    private function checkedOn(array $lib, array $source): string
    {
        $unit = $this->sourceUnit($lib, $source);

        return $unit && preg_match('/dicek ([^)]+)/u', (string) $unit['subsection'], $m) ? " (dicek {$m[1]})" : '';
    }

    private function sourceUnit(array $lib, array $source): ?array
    {
        return collect($lib['units'])->first(fn ($u) => $u['kind'] === 'source' && $u['file'] === $source['file'] && ($u['source'] ?? null) === $source['id']);
    }

    private function researchUnitAnswer(array $lib, array $unit, float $score): array
    {
        $text = $unit['text'];
        if ($unit['kind'] === 'row' && $unit['section'] && $unit['file'] === '04-rujukan-per-modul.md') {
            $text = $unit['section'] . ': ' . $text;
        }
        $sources = collect($lib['sources'])->filter(fn ($s) => $s['file'] === $unit['file'] && in_array($s['id'], $unit['refs'], true));
        if ($sources->isEmpty()) {
            // Ringkasan yang menyebut nama peraturan langsung ("diperbolehkan PermenPANRB 5/2020").
            $sources = collect(ResearchLibrary::uniqueSources($lib['sources']))
                ->filter(fn ($s) => mb_strlen($s['short']) >= 6 && preg_match('/(?<![\w\/])' . preg_quote($s['short'], '/') . '(?![\w\/])/u', $text));
        }

        return [
            'answer' => $text,
            'engine' => self::RESEARCH_ENGINE,
            'links' => $this->researchLinks(collect(ResearchLibrary::uniqueSources($sources->all()))),
            'draft' => $unit['draft'],
            'score' => $score,
        ];
    }

    /**
     * Potongan riset yang paling cocok: kemiripan makna (bge-m3) + kata yang sama.
     *
     * @return array{unit: array, score: float}|null
     */
    private function bestResearchUnit(array $lib, string $text, ?array $queryVector, ?array $scope, ?array $kinds = null, ?\Closure $where = null): ?array
    {
        $vectors = $queryVector ? $this->researchVectors($lib['units']) : null;
        $terms = array_values(array_diff(self::terms($text), self::RESEARCH_WORDS));

        $best = null;
        foreach ($lib['units'] as $i => $unit) {
            if (($scope && ($unit['file'] !== $scope['file'] || ($scope['section'] !== null && $unit['section'] !== $scope['section'])))
                || ($kinds && ! in_array($unit['kind'], $kinds, true)) || ($where && ! $where($unit))) {
                continue;
            }
            $haystack = Str::lower($unit['section'] . ' ' . $unit['subsection'] . ' ' . $unit['text']);
            $keyword = $terms === [] ? 0 : collect($terms)->filter(fn ($t) => str_contains($haystack, $t))->count() / count($terms);
            $score = $vectors && isset($vectors[$i])
                ? LocalLlm::cosine($queryVector, $vectors[$i]) + 0.1 * $keyword
                : $keyword;
            if ($best === null || $score > $best['score']) {
                $best = ['unit' => $unit, 'score' => $score];
            }
        }

        return $best;
    }

    /** Vektor potongan riset; dihitung sekali per isi riset, lalu disimpan di cache. */
    private function researchVectors(array $units): ?array
    {
        $texts = array_map(fn ($u) => Str::limit(trim($u['section'] . ($u['subsection'] ? ' › ' . $u['subsection'] : '') . ': ' . $u['text']), 1000, ''), $units);
        $key = 'ai.riset.vectors.' . md5(config('ai.embed_model') . json_encode($texts));

        $vectors = Cache::get($key);
        if (is_array($vectors) && count($vectors) === count($texts)) {
            return $vectors;
        }

        $vectors = $this->llm->embed($texts);
        if ($vectors && count($vectors) === count($texts)) {
            Cache::forever($key, $vectors);

            return $vectors;
        }

        return null;
    }

    private function researchLinks(Collection $sources, int $take = 12): array
    {
        return $sources->filter(fn ($s) => $s['url'] !== null)->take($take)->map(fn ($s) => [
            'kind' => 'web',
            'title' => Str::limit($s['title'], 110),
            'meta' => collect([$s['status'], parse_url($s['url'], PHP_URL_HOST)])->filter()->implode(' · '),
            'url' => $s['url'],
        ])->values()->all();
    }

    /** ["a", "b", "c"] → "a, b, dan c". */
    private static function joinNames(array $names): string
    {
        $names = array_values($names);

        return count($names) <= 1 ? ($names[0] ?? '') : implode(', ', array_slice($names, 0, -1)) . (count($names) > 2 ? ', dan ' : ' dan ') . end($names);
    }
}
