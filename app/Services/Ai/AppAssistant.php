<?php

namespace App\Services\Ai;

use App\Models\AiLog;
use App\Models\Asset;
use App\Models\Change;
use App\Models\DataInformation;
use App\Models\EvidenceDocument;
use App\Models\HrRisk;
use App\Models\KnowledgeAsset;
use App\Models\KnowledgeExpert;
use App\Models\Risk;
use App\Models\SecurityProgram;
use App\Models\Service;
use App\Models\ServiceTicket;
use App\Support\AppHelp;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Asisten aplikasi: satu kotak tanya untuk seluruh isi BSSN ISMS.
 *
 * Bahan jawaban dikumpulkan KODE, bukan LLM, dari empat sumber:
 * 1. Panduan aplikasi (App\Support\AppHelp): menu, tombol, aturan hitung.
 * 2. Data modul: ringkasan hitungan dan baris yang cocok dengan pertanyaan
 *    (termasuk saringan level, prioritas, dan status).
 * 3. Berkas: PDF Aset Pengetahuan dan bukti dukung yang cocok.
 * 4. Isi dokumen: potongan PDF terdekat (pencarian yang sama dengan Tanya
 *    Dokumen).
 *
 * LLM lokal hanya merangkai kalimat dari bahan itu. Tautan ke data dan
 * berkas dibuat kode, jadi tidak bisa salah alamat atau dikarang. Bila LLM
 * mati, jawaban disusun dari templat. Asisten hanya membaca; tidak ada data
 * yang diubah.
 */
class AppAssistant
{
    /**
     * Urutan penting: yang lebih spesifik dicek dulu ("risiko sdm" sebelum
     * "risiko", "aset pengetahuan" sebelum "aset").
     */
    private const MODULES = [
        'bukti' => ['label' => 'Bukti dukung', 'words' => ['bukti dukung', 'evaluasi bukti'], 'model' => EvidenceDocument::class, 'code' => 'record_code', 'title' => 'title', 'search' => ['title', 'original_name', 'unit_name', 'record_code'], 'level' => null],
        'sdm' => ['label' => 'Risiko SDM', 'words' => ['risiko sdm', 'sdm', 'pihak ketiga'], 'model' => HrRisk::class, 'code' => 'record_code', 'title' => 'title', 'search' => ['title', 'subject', 'record_code', 'pic'], 'level' => 'inherent_risk_level'],
        'data_informasi' => ['label' => 'Data & Informasi', 'words' => ['data informasi', 'data dan informasi'], 'model' => DataInformation::class, 'code' => 'record_code', 'title' => 'title', 'search' => ['title', 'record_code', 'risk_type', 'pic'], 'level' => 'inherent_risk_level'],
        'keamanan' => ['label' => 'Program keamanan informasi', 'words' => ['keamanan informasi', 'program keamanan', 'program kerja'], 'model' => SecurityProgram::class, 'code' => 'record_code', 'title' => 'program_kerja', 'search' => ['program_kerja', 'kegiatan', 'record_code', 'pic'], 'level' => null],
        'tiket' => ['label' => 'Tiket helpdesk', 'words' => ['tiket', 'helpdesk', 'aduan'], 'model' => ServiceTicket::class, 'code' => 'legacy_code', 'title' => 'issue', 'search' => ['issue', 'requester_name', 'legacy_code'], 'level' => 'priority'],
        'keahlian' => ['label' => 'Peta keahlian', 'words' => ['ahli', 'keahlian', 'pakar', 'narasumber', 'expert'], 'model' => KnowledgeExpert::class, 'code' => 'record_code', 'title' => 'nama_pegawai', 'search' => ['nama_pegawai', 'jabatan_unit', 'keahlian_spesifik', 'sertifikasi_lisensi'], 'level' => null],
        'pengetahuan' => ['label' => 'Aset pengetahuan', 'words' => ['aset pengetahuan', 'pengetahuan', 'sop', 'manual book', 'dokumentasi'], 'model' => KnowledgeAsset::class, 'code' => 'record_code', 'title' => 'title', 'search' => ['title', 'record_code', 'category', 'owner_unit', 'ai_summary'], 'level' => null],
        'perubahan' => ['label' => 'Perubahan', 'words' => ['perubahan', 'change'], 'model' => Change::class, 'code' => 'change_code', 'title' => 'title', 'search' => ['title', 'change_code', 'pic'], 'level' => 'inherent_risk_level'],
        'risiko' => ['label' => 'Risiko', 'words' => ['risiko'], 'model' => Risk::class, 'code' => 'risk_code', 'title' => 'title', 'search' => ['title', 'risk_code', 'threat_source', 'vulnerability', 'risk_owner'], 'level' => 'risk_level'],
        'layanan' => ['label' => 'Layanan', 'words' => ['layanan'], 'model' => Service::class, 'code' => 'service_code', 'title' => 'name', 'search' => ['name', 'service_code', 'description', 'owner_unit'], 'level' => null],
        'aset' => ['label' => 'Aset', 'words' => ['aset', 'asset', 'inventaris', 'perangkat'], 'model' => Asset::class, 'code' => 'asset_code', 'title' => 'name', 'search' => ['name', 'asset_code', 'owner', 'location', 'sub_classification'], 'level' => 'criticality_level'],
    ];

    private const LEVELS = ['kritis', 'tinggi', 'sedang', 'rendah'];

    private const RISK_STATUS = ['open' => 'terbuka', 'in_treatment' => 'dalam penanganan', 'monitoring' => 'pemantauan', 'closed' => 'ditutup'];

    /** Kata yang tidak dipakai untuk mencari baris data atau berkas. */
    private const STOP = [
        'yang', 'dan', 'atau', 'dengan', 'untuk', 'dari', 'dalam', 'pada', 'kepada', 'oleh', 'itu', 'ini', 'ada', 'apa', 'apakah', 'saja', 'mana',
        'siapa', 'kapan', 'berapa', 'bagaimana', 'gimana', 'gmn', 'cara', 'caranya', 'tolong', 'mohon', 'minta', 'saya', 'aku', 'kami', 'kita',
        'bisa', 'dong', 'deh', 'sih', 'kak', 'pak', 'bu', 'min', 'halo', 'hai', 'terima', 'kasih', 'yg', 'tsb', 'tersebut', 'sudah', 'belum',
        'akan', 'masih', 'semua', 'seluruh', 'daftar', 'tampilkan', 'tunjukkan', 'lihat', 'cari', 'carikan', 'temukan', 'cek', 'file', 'berkas',
        'dokumen', 'pdf', 'unduh', 'download', 'lampiran', 'data', 'jumlah', 'total', 'rekap', 'level', 'status', 'prioritas', 'terbuka',
        'selesai', 'aktif', 'tentang', 'mengenai', 'soal', 'punya', 'milik', 'nya', 'adalah', 'jadi', 'harus', 'perlu', 'butuh', 'mau', 'ingin',
        'list', 'sebutkan', 'buka', 'kasih', 'beri', 'berikan', 'apa', 'banyak', 'brp', 'tidak', 'nggak', 'gak', 'ya', 'kah', 'nih', 'tuh', 'baru',
        'kritis', 'tinggi', 'sedang', 'rendah', 'p1', 'p2', 'p3', 'p4', 'modul', 'manajemen', 'menu', 'halaman', 'aplikasi', 'sistem',
    ];

    public function __construct(private LocalLlm $llm = new LocalLlm())
    {
    }

    /**
     * @param  array<int, array{q: string, a: string}>  $history  percakapan sebelumnya, terlama dulu
     * @return array{answer: string, engine: string, links: array<int, array{kind: string, title: string, meta: ?string, url: string}>}
     */
    public function answer(string $question, ?string $page = null, array $history = []): array
    {
        $startedAt = microtime(true);
        $question = Str::limit(trim($question), 500, '');
        $flat = EvidenceAssessor::flatten($question);

        // Pertanyaan lanjutan pendek ("yang kritis saja?") dicari bersama
        // pertanyaan sebelumnya supaya modulnya tidak hilang.
        $searchText = $flat;
        $followUp = $history !== [] && count(self::terms($flat)) <= 2 && $this->detectModule($flat) === null;
        if ($followUp) {
            $searchText = EvidenceAssessor::flatten(end($history)['q']) . ' ' . $flat;
        }

        $intent = $this->intent($searchText, $followUp ? $searchText : $flat);
        if (preg_match('/\b(kritis|tinggi|sedang|rendah)\b/u', $flat, $m)) {
            $intent['filters']['level'] = $m[1]; // "yang tinggi saja?" setelah "berapa risiko kritis?"
        }
        if (preg_match('/\bp([1-4])\b/u', $flat, $m)) {
            $intent['filters']['priority'] = 'P' . $m[1];
        }
        $moduleKey = $this->detectModule($searchText);
        $terms = $this->searchTerms($flat, $moduleKey);
        $queryVector = $this->llm->embed([$question])[0] ?? null;

        $help = $this->searchHelp($question, $queryVector);
        $strongHelp = $help->isNotEmpty() && $help->first()['strong'];

        // Baris data hanya dicari bila pengguna memang mencari/menghitung data,
        // bukan untuk pertanyaan cara pakai ("cara menambah aset") atau isi
        // dokumen ("berapa lama server disiapkan"), supaya tautannya tidak ngawur.
        $records = collect();
        $exact = null;
        $searching = $intent['list'] || $intent['code'] || $intent['count'] || $intent['filters'] !== [];
        if ($moduleKey !== null && ($searching || ($terms !== [] && ! $intent['howto'] && ! $intent['file']))) {
            [$records, $exact] = $this->moduleRecords($moduleKey, $terms, $intent['filters']);
        } elseif ($terms !== [] && ($intent['list'] || $intent['code']) && ! $intent['file']) {
            $records = $this->searchAllModules($terms);
        }

        $files = $intent['file'] || in_array($moduleKey, ['pengetahuan', 'bukti'], true) ? $this->searchFiles($terms) : collect();
        if ($files->isNotEmpty() && in_array($moduleKey, ['pengetahuan', 'bukti'], true)) {
            $records = collect(); // berkasnya sudah tampil; tidak perlu baris tabel yang sama
        }

        $chunks = collect();
        if (! ($intent['howto'] && $strongHelp) && ! ($intent['count'] && $moduleKey)) {
            $chunks = $this->documentChunks($question, $intent['file']);
            // Dokumen yang isinya dipakai untuk menjawab selalu diberi tautan,
            // supaya jawabannya bisa dicek di sumber aslinya.
            $files = $files->concat($this->filesFromChunks($chunks))->unique('title')->take(5);
        }

        $facts = $intent['count'] || ($moduleKey !== null && $intent['filters'] !== []) ? $this->facts() : [];
        if ($exact !== null) {
            array_unshift($facts, $exact);
        }

        // Hanya panduan yang skornya dekat dengan yang terbaik; model kecil
        // cenderung memakai semua panduan yang diberikan walau tidak relevan.
        $bestHelp = $help->first()['score'] ?? 0;
        $useHelp = $help->filter(fn ($h) => ($h['strong'] || $intent['howto']) && $h['score'] >= $bestHelp - 0.04)->take(2);
        $links = $this->links($records, $files, $intent['howto'] ? $useHelp : collect());

        if ($intent['howto'] && $strongHelp && $useHelp->isNotEmpty() && $records->isEmpty() && $files->isEmpty() && $exact === null) {
            // Teks panduan sudah ditulis rapi dan akurat; uji 18 Nov 2026:
            // llama3.2 memparafrasekan rumus skor bukti dukung menjadi salah
            // ("mendapatkan skor 100 lalu dibagi"). Jadi tidak diserahkan ke LLM.
            $answer = $useHelp->first()['text'];
            $engine = 'Panduan aplikasi';
        } elseif ($exact !== null && ($intent['count'] || $intent['filters'] !== [])) {
            // Hitungan tidak diserahkan ke LLM: pada uji 18 Nov 2026 model
            // menambah kode risiko yang tidak ada saat diminta menghitung.
            $answer = $exact . ($records->isNotEmpty() ? ' Daftarnya ada di bawah.' : '');
            $engine = 'Hitungan langsung dari basis data';
        } else {
            $answer = $this->compose($question, $page, $history, $intent, $useHelp, $facts, $records, $files, $chunks);
            if ($answer !== null && $this->mentionsUnknownCode($answer, $records, $files, $facts)) {
                $answer = null;
            }
            $engine = $answer !== null
                ? $this->llm->chatEngineName() . ' + data aplikasi'
                : ($this->llm->chatAvailable() ? 'Pencarian aplikasi' : 'Pencarian aplikasi (AI tidak aktif)');
            $answer ??= $this->fallback($intent, $useHelp, $facts, $records, $files, $chunks);
        }

        AiLog::record('asisten', $engine, null, $question, $answer, $startedAt);

        return ['answer' => $answer, 'engine' => $engine, 'links' => $links];
    }

    // ------------------------------------------------------------------
    // Memahami pertanyaan
    // ------------------------------------------------------------------

    /** @return array{howto: bool, count: bool, list: bool, file: bool, code: bool, filters: array<string, mixed>} */
    private function intent(string $text, string $current): array
    {
        $filters = [];
        if (preg_match('/\bp([1-4])\b/u', $text, $m)) {
            $filters['priority'] = 'P' . $m[1];
        }
        if (preg_match('/\b(kritis|tinggi|sedang|rendah)\b/u', $current, $m) || preg_match('/\b(kritis|tinggi|sedang|rendah)\b/u', $text, $m)) {
            $filters['level'] = $m[1];
        }
        if (preg_match('/\b(terbuka|belum selesai|belum ditangani|masih|aktif|open)\b/u', $text)) {
            $filters['open'] = true;
        }
        if (preg_match('/\b(menunggu verifikasi|belum diverifikasi|belum divalidasi)\b/u', $text)) {
            $filters['pending'] = true;
        }

        return [
            'howto' => (bool) preg_match('/\b(cara|caranya|bagaimana|gimana|gmn|langkah|tutorial|panduan|fungsi|kegunaan|maksud|arti|apa itu|bisakah|di ?mana|dmn|menu|tombol|fitur|kenapa|mengapa|dari mana|asal)\b/u', $current),
            'count' => (bool) preg_match('/\b(berapa|brp|jumlah|total|rekap|statistik|banyaknya)\b/u', $current)
                && ! preg_match('/\bberapa\s+(lama|hari|jam|menit|kali|biaya|tahun|persen)\b/u', $current),
            'list' => (bool) preg_match('/\b(daftar|tampilkan|tunjukkan|list|apa saja|mana saja|sebutkan|lihat|cari|carikan|temukan|siapa)\b/u', $text),
            'file' => (bool) preg_match('/\b(file|berkas|dokumen|pdf|unduh|download|lampiran|templat|template|sop|pedoman)\b/u', $text),
            'code' => (bool) preg_match('/\b[a-z]{2,5}-\d{2,}/u', $text),
            'filters' => $filters,
        ];
    }

    private function detectModule(string $text): ?string
    {
        foreach (self::MODULES as $key => $def) {
            foreach ($def['words'] as $word) {
                if (preg_match('/(?<![\p{L}\p{N}])' . preg_quote($word, '/') . '(?![\p{L}\p{N}])/u', $text)) {
                    return $key;
                }
            }
        }

        return null;
    }

    /** @return array<int, string> */
    private static function terms(string $text): array
    {
        $words = preg_split('/[^\p{L}\p{N}\-]+/u', Str::lower($text), -1, PREG_SPLIT_NO_EMPTY);

        // "levelnya", "statusnya", "risikonya" diperlakukan seperti kata dasarnya.
        $words = array_map(fn ($w) => mb_strlen($w) > 5 ? (preg_replace('/(nya|lah|kah)$/u', '', $w) ?? $w) : $w, $words);

        return array_values(array_unique(array_filter(
            $words,
            fn ($w) => mb_strlen($w) >= 3 && ! in_array($w, self::STOP, true)
        )));
    }

    /** Kata pencarian tanpa kata nama modul (supaya "risiko" tidak ikut dicari di judul risiko). */
    private function searchTerms(string $text, ?string $moduleKey): array
    {
        $moduleWords = collect(self::MODULES)->flatMap(fn ($d) => $d['words'])->flatMap(fn ($w) => explode(' ', $w))->all();

        return array_slice(array_values(array_filter(self::terms($text), fn ($t) => ! in_array($t, $moduleWords, true))), 0, 6);
    }

    // ------------------------------------------------------------------
    // Sumber 1: panduan aplikasi
    // ------------------------------------------------------------------

    /** @return Collection<int, array{title: string, text: string, url: ?string, score: float, strong: bool}> */
    private function searchHelp(string $question, ?array $queryVector): Collection
    {
        $entries = AppHelp::entries();
        $vectors = $queryVector ? $this->helpVectors($entries) : null;
        $terms = self::terms(EvidenceAssessor::flatten($question));

        return collect($entries)->map(function ($entry, $i) use ($vectors, $queryVector, $terms) {
            $haystack = self::terms($entry['title'] . ' ' . $entry['words']);
            $body = self::terms($entry['text']);
            $hits = count(array_intersect($terms, $haystack)) * 2 + count(array_intersect($terms, $body));
            $keyword = $terms === [] ? 0 : min(1, $hits / (2 * count($terms)));

            $score = $vectors && isset($vectors[$i])
                ? LocalLlm::cosine($queryVector, $vectors[$i]) + 0.1 * $keyword
                : $keyword;
            $strong = $vectors ? $score >= 0.62 : $keyword >= 0.5;

            return $entry + ['score' => $score, 'strong' => $strong];
        })->sortByDesc('score')->take(3)->filter(fn ($e) => $e['score'] > ($vectors ? 0.5 : 0.25))->values();
    }

    /** Vektor panduan dihitung sekali per isi panduan, lalu disimpan di cache. */
    private function helpVectors(array $entries): ?array
    {
        $texts = array_map(fn ($e) => Str::limit($e['title'] . '. ' . $e['text'], 1200, ''), $entries);
        $key = 'ai.apphelp.vectors.' . md5(config('ai.embed_model') . json_encode($texts));

        $vectors = Cache::get($key);
        if (is_array($vectors) && count($vectors) === count($texts)) {
            return $vectors;
        }

        $vectors = $this->llm->embed($texts);
        if ($vectors) {
            Cache::forever($key, $vectors);
        }

        return $vectors;
    }

    // ------------------------------------------------------------------
    // Sumber 2: data modul
    // ------------------------------------------------------------------

    /**
     * Baris satu modul dengan saringan level/prioritas/status + kata.
     *
     * @return array{0: Collection<int, array>, 1: ?string} baris dan kalimat jumlah persis
     */
    private function moduleRecords(string $key, array $terms, array $filters): array
    {
        $def = self::MODULES[$key];
        $query = $this->filtered($key, $filters);

        $applied = [];
        if (isset($filters['level']) && $def['level'] && $key !== 'tiket') {
            $applied[] = $key === 'aset' ? "berkritikalitas {$filters['level']}" : "level {$filters['level']}";
        }
        if (isset($filters['priority']) && $key === 'tiket') {
            $applied[] = "prioritas {$filters['priority']}";
        }
        if (! empty($filters['open']) && in_array($key, ['tiket', 'risiko'], true)) {
            $applied[] = 'yang masih terbuka';
        }
        if (! empty($filters['pending']) && $this->hasColumn($key, 'verification_status')) {
            $applied[] = 'yang menunggu verifikasi';
        }

        if ($terms !== []) {
            $query->where(function ($q) use ($def, $terms) {
                foreach ($terms as $term) {
                    foreach ($def['search'] as $column) {
                        $q->orWhere($column, 'ilike', '%' . $term . '%');
                    }
                }
            });
            $applied[] = 'yang memuat kata ' . implode(' atau ', array_map(fn ($t) => '"' . $t . '"', $terms));
        }

        $count = (clone $query)->count();
        $what = Str::lower($def['label']) . ($applied ? ' ' . implode(' ', $applied) : '');
        $exact = $count === 0 ? "Tidak ada {$what}." : "Ada {$count} {$what}.";

        $rows = $query->limit(60)->get();
        $rows = $terms === [] ? $this->sortBySeverity($rows, $def) : $this->rankByTerms($rows, $def, $terms);

        return [$rows->take(6)->map(fn ($row) => $this->describe($key, $row))->values(), $exact];
    }

    private function filtered(string $key, array $filters)
    {
        $def = self::MODULES[$key];
        $query = $def['model']::query();

        if ($key === 'tiket') {
            if (isset($filters['priority'])) {
                $query->where('priority', $filters['priority']);
            }
            if (! empty($filters['open'])) {
                $query->where(fn ($w) => $w->whereNull('status')->orWhereNotIn(DB::raw('lower(status)'), ['selesai', 'ditutup']));
            }
        } elseif (isset($filters['level']) && $def['level']) {
            $query->where($def['level'], $filters['level']);
        }

        if ($key === 'risiko' && ! empty($filters['open'])) {
            $query->where(fn ($w) => $w->whereNull('status')->orWhere('status', '!=', 'closed'));
        }
        if (! empty($filters['pending']) && $this->hasColumn($key, 'verification_status')) {
            $query->where('verification_status', 'menunggu_verifikasi');
        }

        return $query;
    }

    private function hasColumn(string $key, string $column): bool
    {
        return in_array($column, (new (self::MODULES[$key]['model']))->getFillable(), true);
    }

    /** Cari kata di semua modul, untuk pertanyaan yang tidak menyebut modul. */
    private function searchAllModules(array $terms): Collection
    {
        $found = collect();
        foreach (self::MODULES as $key => $def) {
            $rows = $def['model']::query()
                ->where(function ($q) use ($def, $terms) {
                    foreach ($terms as $term) {
                        foreach ($def['search'] as $column) {
                            $q->orWhere($column, 'ilike', '%' . $term . '%');
                        }
                    }
                })
                ->limit(20)
                ->get();

            foreach ($this->rankByTerms($rows, $def, $terms) as $row) {
                $found->push(['hits' => $this->termHits($row, $def, $terms), 'item' => $this->describe($key, $row)]);
            }
        }

        $best = $found->max('hits') ?? 0;
        // Kalau ada baris yang cocok dengan lebih banyak kata, buang yang hanya cocok satu kata.
        return $found->filter(fn ($f) => $f['hits'] >= max(1, $best - 1))
            ->sortByDesc('hits')
            ->take(6)
            ->pluck('item')
            ->values();
    }

    private function termHits(Model $row, array $def, array $terms): int
    {
        $text = Str::lower(collect($def['search'])->map(fn ($c) => $this->plain($row->{$c}))->implode(' '));

        return collect($terms)->filter(fn ($t) => str_contains($text, $t))->count();
    }

    private function rankByTerms(Collection $rows, array $def, array $terms): Collection
    {
        return $rows->sortByDesc(fn ($row) => $this->termHits($row, $def, $terms))->values();
    }

    private function sortBySeverity(Collection $rows, array $def): Collection
    {
        if (! $def['level']) {
            return $rows->sortByDesc('id')->values();
        }
        $rank = ['kritis' => 4, 'tinggi' => 3, 'sedang' => 2, 'rendah' => 1, 'P1' => 4, 'P2' => 3, 'P3' => 2, 'P4' => 1];

        return $rows->sortByDesc(fn ($row) => $rank[$this->plain($row->{$def['level']})] ?? 0)->values();
    }

    /** @return array{kind: string, title: string, meta: ?string, url: string, line: string} */
    private function describe(string $key, Model $row): array
    {
        $def = self::MODULES[$key];
        $code = $key === 'tiket' ? $row->label() : $this->plain($row->{$def['code']});
        $title = Str::limit($this->plain($row->{$def['title']}), 90);
        $level = $def['level'] ? $this->plain($row->{$def['level']}) : null;
        $status = $this->plain($row->status ?? null);
        $status = self::RISK_STATUS[$status] ?? $status;

        $meta = collect([
            $def['label'],
            $level ? match ($key) { 'tiket' => "prioritas {$level}", 'aset' => "kritikalitas {$level}", default => "level {$level}" } : null,
            $status ? "status {$status}" : null,
            $key === 'keahlian' ? $row->jabatan_unit : null,
            $key === 'bukti' && $row->achieved_level !== null ? "hasil Level {$row->achieved_level}, skor {$row->score}" : null,
        ])->filter()->implode(' · ');

        $detail = match ($key) {
            'keahlian' => ' — keahlian: ' . Str::limit((string) $row->keahlian_spesifik, 120),
            'layanan' => $row->description ? ' — ' . Str::limit((string) $row->description, 120) : '',
            default => '',
        };

        return [
            'kind' => 'data',
            'title' => trim(($code ? $code . ' · ' : '') . $title),
            'meta' => $meta,
            'url' => $this->recordUrl($key, $row, $code),
            'line' => "{$code}: {$title} ({$meta}){$detail}",
        ];
    }

    private function recordUrl(string $key, Model $row, ?string $code): string
    {
        return match ($key) {
            'aset' => route('assets.index', ['search' => $code]),
            'risiko' => route('risks.index', ['search' => $code]),
            'perubahan' => route('changes.index', ['search' => $code]),
            'data_informasi' => route('data-information.index', ['search' => $code]),
            'sdm' => route('hr-risks.index', ['search' => $code]),
            'keamanan' => route('security-programs.index', ['search' => $code]),
            'layanan' => route('services.index', ['search' => $code]),
            'tiket' => route('services.index', ['tab' => 'helpdesk', 'statusFilter' => '', 'search' => $code]),
            'pengetahuan' => route('knowledge.index', ['tab' => 'aset', 'search' => $code]),
            'keahlian' => route('knowledge.index', ['tab' => 'keahlian', 'search' => $row->nama_pegawai]),
            'bukti' => route('evidence.index', ['search' => $row->title]),
        };
    }

    /** Ringkasan angka seluruh modul, dihitung langsung dari basis data. */
    private function facts(): array
    {
        $byLevel = function (string $model, string $column): string {
            $counts = $model::query()->whereNotNull($column)->selectRaw("{$column} as k, count(*) as n")->groupBy($column)->pluck('n', 'k');
            $parts = collect(self::LEVELS)->filter(fn ($l) => isset($counts[$l]))->map(fn ($l) => "{$l} {$counts[$l]}");

            return $parts->isEmpty() ? '' : ' (' . $parts->implode(', ') . ')';
        };

        $openTickets = ServiceTicket::query()->where(fn ($w) => $w->whereNull('status')->orWhereNotIn(DB::raw('lower(status)'), ['selesai', 'ditutup']));
        $openByPriority = (clone $openTickets)->whereNotNull('priority')->selectRaw('priority as k, count(*) as n')->groupBy('priority')->orderBy('priority')->pluck('n', 'k')
            ->map(fn ($n, $k) => "{$k} {$n}")->implode(', ');

        $pending = collect([Asset::class, Risk::class, Change::class, DataInformation::class, HrRisk::class, KnowledgeAsset::class])
            ->sum(fn ($model) => $model::query()->where('verification_status', 'menunggu_verifikasi')->count());

        return [
            'Aset: ' . Asset::count() . $byLevel(Asset::class, 'criticality_level') . ' menurut kritikalitas',
            'Risiko: ' . Risk::count() . $byLevel(Risk::class, 'risk_level') . ' menurut level',
            'Risiko SDM: ' . HrRisk::count() . $byLevel(HrRisk::class, 'inherent_risk_level'),
            'Perubahan: ' . Change::count(),
            'Data & Informasi: ' . DataInformation::count(),
            'Program keamanan informasi: ' . SecurityProgram::count(),
            'Layanan: ' . Service::count(),
            'Tiket helpdesk: ' . ServiceTicket::count() . ', masih terbuka ' . (clone $openTickets)->count() . ($openByPriority ? " ({$openByPriority})" : ''),
            'Aset pengetahuan: ' . KnowledgeAsset::count() . ', PDF yang bisa ditanya ' . KnowledgeAsset::whereIn('index_status', ['terindeks', 'kata_kunci'])->count(),
            'Pegawai di peta keahlian: ' . KnowledgeExpert::count(),
            'Bukti dukung: ' . EvidenceDocument::count() . ', belum diputuskan admin ' . EvidenceDocument::whereNull('reviewer_decision')->count(),
            'Data menunggu verifikasi admin: ' . $pending,
        ];
    }

    // ------------------------------------------------------------------
    // Sumber 3 dan 4: berkas dan isi dokumen
    // ------------------------------------------------------------------

    private function searchFiles(array $terms): Collection
    {
        if ($terms === []) {
            return collect();
        }

        $match = function ($query, array $columns) use ($terms) {
            $query->where(function ($q) use ($columns, $terms) {
                foreach ($terms as $term) {
                    foreach ($columns as $column) {
                        $q->orWhere($column, 'ilike', '%' . $term . '%');
                    }
                }
            });
        };

        $knowledge = KnowledgeAsset::query()
            ->where(fn ($q) => $q->whereNotNull('document_path')->orWhereNotNull('external_link'))
            ->tap(fn ($q) => $match($q, ['title', 'record_code', 'document_original_name', 'category', 'ai_summary']))
            ->limit(30)->get()
            ->map(fn ($doc) => ['hits' => $this->countHits($doc->title . ' ' . $doc->category . ' ' . $doc->ai_summary, $terms) + 2 * $this->countHits($doc->title, $terms), 'item' => $this->knowledgeFile($doc)]);

        $evidence = EvidenceDocument::query()
            ->tap(fn ($q) => $match($q, ['title', 'original_name', 'unit_name', 'record_code']))
            ->limit(30)->get()
            ->map(fn ($doc) => ['hits' => 3 * $this->countHits($doc->title . ' ' . $doc->unit_name, $terms), 'item' => [
                'kind' => 'file',
                'title' => $doc->title,
                'meta' => 'Bukti dukung · ' . $doc->moduleLabel() . ($doc->unit_name ? ' · ' . $doc->unit_name : ''),
                'url' => route('evidence.file', $doc),
                'line' => "{$doc->title} (bukti dukung {$doc->moduleLabel()}" . ($doc->achieved_level !== null ? ", hasil Level {$doc->achieved_level}, skor {$doc->score}" : '') . ')',
            ]]);

        $all = $knowledge->concat($evidence);
        $best = $all->max('hits') ?? 0;

        return $all->filter(fn ($f) => $f['hits'] > 0 && $f['hits'] >= $best / 2)->sortByDesc('hits')->take(5)->pluck('item')->values();
    }

    private function countHits(?string $text, array $terms): int
    {
        $text = Str::lower((string) $text);

        return collect($terms)->filter(fn ($t) => str_contains($text, $t))->count();
    }

    private function knowledgeFile(KnowledgeAsset $doc, ?int $page = null): array
    {
        $url = $doc->document_path
            ? Storage::disk('public')->url($doc->document_path) . ($page ? '#page=' . $page : '')
            : ($doc->external_link ?: route('knowledge.index', ['tab' => 'aset', 'search' => $doc->record_code]));

        return [
            'kind' => 'file',
            'title' => $doc->title,
            'meta' => collect(['Pengetahuan', $doc->record_code, $page ? "hal. {$page}" : null, $doc->owner_unit])->filter()->implode(' · '),
            'url' => $url,
            'line' => "{$doc->title} ({$doc->record_code}, aset pengetahuan" . ($doc->owner_unit ? ", {$doc->owner_unit}" : '') . ')',
        ];
    }

    /** Potongan isi PDF pengetahuan yang paling dekat dengan pertanyaan. */
    private function documentChunks(string $question, bool $wantsFile): Collection
    {
        try {
            $hits = (new KnowledgeSearch($this->llm))->retrieve($question, 3);
        } catch (Throwable) {
            return collect();
        }

        $min = $this->llm->embedAvailable() ? ($wantsFile ? 0.48 : 0.52) : 0.5;

        return $hits->filter(fn ($hit) => $hit['score'] >= $min)->values();
    }

    private function filesFromChunks(Collection $chunks): Collection
    {
        return $chunks->unique(fn ($hit) => $hit['chunk']->knowledge_asset_id)
            ->map(fn ($hit) => $this->knowledgeFile($hit['chunk']->knowledgeAsset()->first(), $hit['chunk']->page_number))
            ->values();
    }

    // ------------------------------------------------------------------
    // Menyusun jawaban
    // ------------------------------------------------------------------

    private function links(Collection $records, Collection $files, Collection $help): array
    {
        $helpLinks = $help->filter(fn ($h) => $h['url'])->unique('url')->take(1)->map(fn ($h) => [
            'kind' => 'page', 'title' => 'Buka ' . $h['page'], 'meta' => null, 'url' => $h['url'],
        ]);

        return $files->concat($records)->concat($helpLinks)
            ->unique('url')
            ->take(8)
            ->map(fn ($l) => ['kind' => $l['kind'], 'title' => $l['title'], 'meta' => $l['meta'], 'url' => $l['url']])
            ->values()
            ->all();
    }

    private function compose(string $question, ?string $page, array $history, array $intent, Collection $help, array $facts, Collection $records, Collection $files, Collection $chunks): ?string
    {
        if (! $this->llm->chatAvailable()) {
            return null;
        }

        $sections = [];
        if ($page) {
            $sections[] = "Halaman yang sedang dibuka pengguna: {$page}";
        }
        if ($history !== []) {
            $sections[] = "Percakapan sebelumnya:\n" . collect($history)->take(-2)
                ->map(fn ($h) => 'Pengguna: ' . Str::limit($h['q'], 200) . "\nAsisten: " . Str::limit($h['a'], 300))->implode("\n");
        }
        if ($help->isNotEmpty()) {
            $sections[] = "Panduan aplikasi:\n" . $help->map(fn ($h) => "- {$h['title']}: {$h['text']}")->implode("\n");
        }
        if ($facts !== []) {
            $sections[] = "Data aplikasi saat ini (angka pasti):\n" . collect($facts)->map(fn ($f) => "- {$f}")->implode("\n");
        }
        if ($records->isNotEmpty()) {
            $sections[] = "Data yang cocok dengan pertanyaan:\n" . $records->map(fn ($r) => "- {$r['line']}")->implode("\n");
        }
        if ($files->isNotEmpty()) {
            $sections[] = "Berkas yang cocok dengan pertanyaan:\n" . $files->map(fn ($f) => "- {$f['line']}")->implode("\n");
        }
        if ($chunks->isNotEmpty()) {
            $sections[] = "Isi dokumen pengetahuan:\n" . $chunks->values()->map(
                fn ($hit, $i) => '[' . ($i + 1) . '] ' . $hit['chunk']->knowledgeAsset->title . ":\n" . Str::limit($hit['chunk']->content, 1000)
            )->implode("\n\n");
        }

        // Aturan disesuaikan jenis pertanyaan: model kecil cenderung menjalankan
        // semua aturan yang diberikan, mis. menulis langkah menu karangan untuk
        // pertanyaan hitungan (uji 18 Nov 2026).
        $rules = ['Jawab langsung dalam paling banyak 100 kata, tanpa judul, tanpa salam, dan tanpa mengulang nama bagian bahan.'];
        if ($intent['howto'] && $help->isNotEmpty()) {
            $rules[] = 'Bila pertanyaannya tentang cara melakukan sesuatu, tulis langkahnya sebagai daftar bernomor yang disalin dari panduan. Jangan menambah menu, tombol, atau langkah yang tidak tertulis di panduan.';
        } else {
            $rules[] = 'Jangan menulis langkah memakai aplikasi; pertanyaan ini tidak menanyakannya.';
        }
        if ($facts !== []) {
            $rules[] = 'Untuk angka, salin persis dari bagian data aplikasi. Jangan menghitung sendiri.';
        }
        if ($records->isNotEmpty() || ($files->isNotEmpty() && $intent['file'])) {
            $rules[] = 'Sebutkan judul atau kode data/berkas yang cocok, paling banyak 5. Tautannya otomatis tampil di bawah jawaban, jadi jangan menulis alamat web.';
        } else {
            $rules[] = 'Jangan menulis alamat web; dokumen sumber otomatis ditautkan di bawah jawaban.';
        }
        $rules[] = 'Pakai hanya bahan yang benar-benar menjawab pertanyaan; abaikan sisanya. Jangan mengubah rumus, angka, atau istilah dari bahan.';
        $rules[] = 'Bila bahan tidak memuat jawabannya, tulis hanya: Maaf, saya belum menemukan informasinya di aplikasi ini.';

        $answer = $this->llm->generate(
            implode("\n\n", $sections) . "\n\nPertanyaan: {$question}\n\nAturan menjawab:\n- " . implode("\n- ", $rules),
            false,
            'Anda Asisten aplikasi BSSN ISMS (Sistem Manajemen Keamanan Informasi) di Dinas Komunikasi dan Informatika. '
            . 'Jawab dalam Bahasa Indonesia yang singkat, sopan, dan jelas, HANYA berdasarkan bahan yang diberikan. '
            . 'Jangan mengarang menu, angka, nama, kode, atau isi dokumen. Jangan pernah meminta atau menyebut kata sandi.'
        );

        return is_string($answer) ? self::tidy($answer) : null;
    }

    /** Kode data (RSK-2026-0001, TKT-000001, ...) yang disebut LLM tetapi tidak ada di bahan. */
    private function mentionsUnknownCode(string $answer, Collection $records, Collection $files, array $facts): bool
    {
        preg_match_all('/\b(?:[A-Z]{2,5}-[A-Z0-9]{2,6}-\d{3,6}|TKT-\d{6})\b/u', $answer, $m);
        if ($m[0] === []) {
            return false;
        }
        $known = $records->pluck('line')->concat($files->pluck('line'))->concat($facts)->implode(' ');

        return collect($m[0])->contains(fn ($code) => ! str_contains($known, $code));
    }

    /** Jawaban tanpa LLM: bahan terbaik disajikan apa adanya. */
    private function fallback(array $intent, Collection $help, array $facts, Collection $records, Collection $files, Collection $chunks): string
    {
        if ($intent['howto'] && $help->isNotEmpty()) {
            return $help->first()['text'];
        }
        if ($intent['count'] && $facts !== []) {
            return implode("\n", array_slice($facts, 0, 1)) . ($records->isNotEmpty() ? "\n\nDaftarnya ada di bawah." : '');
        }
        if ($files->isNotEmpty()) {
            return 'Berkas yang cocok ada di bawah. Klik untuk membukanya.';
        }
        if ($records->isNotEmpty()) {
            return 'Ditemukan ' . $records->count() . " data yang cocok:\n" . $records->map(fn ($r) => '• ' . $r['title'])->implode("\n");
        }
        if ($help->isNotEmpty()) {
            return $help->first()['text'];
        }
        if ($chunks->isNotEmpty()) {
            $hit = $chunks->first();

            return 'Bagian dokumen yang paling dekat (' . $hit['chunk']->knowledgeAsset->title . "):\n" . Str::limit($hit['chunk']->content, 500);
        }

        return 'Maaf, saya belum menemukan informasinya di aplikasi ini. Coba sebut nama modul, kode data, atau judul dokumennya.';
    }

    /** Merapikan kebiasaan model kecil: markdown tebal, alamat web karangan, baris kosong ganda. */
    public static function tidy(string $answer): string
    {
        $answer = str_replace(['**', '__', '###', '##'], '', $answer);
        $answer = preg_replace('/^[ \t]*[\*\-][ \t]+/mu', '• ', $answer) ?? $answer;
        $answer = preg_replace('~\(?https?://\S+\)?~u', '', $answer) ?? $answer;
        $answer = preg_replace('/^[ \t]*(jawaban|asisten)[ \t]*:[ \t]*/imu', '', $answer) ?? $answer;
        // Judul bagian bahan yang ikut tersalin ("Data yang cocok dengan pertanyaan:").
        $answer = preg_replace('/^[ \t]*(panduan aplikasi|data aplikasi saat ini[^:\n]*|data yang cocok[^:\n]*|berkas yang cocok[^:\n]*|isi dokumen[^:\n]*|cara memakai aplikasi[^:\n]*|judul)[ \t]*:[ \t]*$/imu', '', $answer) ?? $answer;
        $answer = preg_replace('/^[ \t]*kode data\/berkas[^:\n]*:.*$/imu', '', $answer) ?? $answer;
        $answer = trim(preg_replace("/\n\s*\n+/u", "\n", $answer) ?? $answer);

        if (preg_match('/^maaf, saya belum menemukan informasinya/iu', $answer)) {
            return 'Maaf, saya belum menemukan informasinya di aplikasi ini. Coba sebut nama modul, kode data, atau judul dokumennya.';
        }

        return Str::limit($answer, 1500);
    }

    private function plain(mixed $value): ?string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return $value === null ? null : (string) $value;
    }
}
