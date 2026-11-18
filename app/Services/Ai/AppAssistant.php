<?php

namespace App\Services\Ai;

use App\Models\ActivityLog;
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
use App\Models\User;
use App\Services\Ai\Concerns\AnswersConnections;
use App\Services\Ai\Concerns\AnswersFromResearch;
use App\Support\AdminModules;
use App\Support\AppHelp;
use BackedEnum;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
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
 * Bahan jawaban dikumpulkan KODE, bukan LLM, dari:
 * 1. Panduan aplikasi (App\Support\AppHelp): menu, tombol, aturan hitung.
 * 2. Data modul: hitungan dan baris dengan saringan level, prioritas,
 *    status, waktu, dan relasi antarmodul (aset ↔ risiko ↔ layanan ↔ tiket).
 * 3. Riwayat perubahan (activity_logs) dan admin tiap modul.
 * 4. Berkas: PDF Aset Pengetahuan dan bukti dukung.
 * 5. Isi dokumen: potongan PDF terdekat (pencarian Tanya Dokumen).
 * 6. Titik integrasi: modul, data tertentu, dan berkas yang baru diunggah
 *    penanya (Concerns\AnswersConnections).
 * 7. Riset rujukan di docs/riset (Concerns\AnswersFromResearch).
 *
 * Uji 18 Nov 2026 dengan llama3.2 memperlihatkan model kecil mengarang
 * begitu bahannya tidak memuat jawaban (kode risiko, nama layanan, bahkan
 * cuaca). Karena itu:
 * - data, hitungan, relasi, riwayat, dan admin dijawab langsung dari basis
 *   data dengan kalimat templat;
 * - pertanyaan cara pakai dijawab dengan teks panduan apa adanya;
 * - LLM hanya merangkai jawaban dari isi dokumen (dan panduan yang tidak
 *   ditanyakan sebagai "cara"), lalu jawabannya dibuang bila menyebut kode
 *   atau angka yang tidak ada di bahan;
 * - pertanyaan yang tidak punya bahan sama sekali ditolak tanpa LLM.
 * Tautan selalu dibuat kode. Asisten hanya membaca; tidak ada data diubah.
 */
class AppAssistant
{
    use AnswersConnections, AnswersFromResearch;

    /**
     * Urutan penting: yang lebih spesifik dicek dulu ("risiko sdm" sebelum
     * "risiko", "aset pengetahuan" sebelum "aset").
     *
     * status: kolom status; time: kolom waktu catatan; link: cara menaut ke
     * aset (asset_id, service_assets, service_id, services); morph: nama
     * jenis di activity_logs.
     */
    private const MODULES = [
        'bukti' => ['label' => 'Bukti dukung', 'words' => ['bukti dukung', 'evaluasi bukti'], 'model' => EvidenceDocument::class, 'code' => 'record_code', 'title' => 'title', 'search' => ['title', 'original_name', 'unit_name', 'record_code'], 'level' => null, 'status' => null, 'link' => null, 'morph' => null],
        'sdm' => ['label' => 'Risiko SDM', 'words' => ['risiko sdm', 'sdm', 'pihak ketiga'], 'model' => HrRisk::class, 'code' => 'record_code', 'title' => 'title', 'search' => ['title', 'subject', 'record_code', 'pic'], 'level' => 'inherent_risk_level', 'status' => 'status', 'link' => null, 'morph' => 'hr_risk'],
        'data_informasi' => ['label' => 'Data & Informasi', 'words' => ['data informasi', 'data dan informasi'], 'model' => DataInformation::class, 'code' => 'record_code', 'title' => 'title', 'search' => ['title', 'record_code', 'risk_type', 'pic'], 'level' => 'inherent_risk_level', 'status' => 'status', 'link' => 'asset_id', 'morph' => 'data_information'],
        'keamanan' => ['label' => 'Program keamanan informasi', 'words' => ['keamanan informasi', 'program keamanan', 'program kerja'], 'model' => SecurityProgram::class, 'code' => 'record_code', 'title' => 'program_kerja', 'search' => ['program_kerja', 'kegiatan', 'record_code', 'pic'], 'level' => null, 'status' => null, 'link' => 'asset_id', 'morph' => 'security_program'],
        'tiket' => ['label' => 'Tiket helpdesk', 'words' => ['tiket', 'helpdesk', 'aduan'], 'model' => ServiceTicket::class, 'code' => 'legacy_code', 'title' => 'issue', 'search' => ['issue', 'requester_name', 'legacy_code'], 'level' => 'priority', 'status' => 'status', 'link' => 'service_id', 'morph' => null],
        'keahlian' => ['label' => 'Peta keahlian', 'words' => ['ahli', 'keahlian', 'pakar', 'narasumber'], 'model' => KnowledgeExpert::class, 'code' => 'record_code', 'title' => 'nama_pegawai', 'search' => ['nama_pegawai', 'jabatan_unit', 'keahlian_spesifik', 'sertifikasi_lisensi'], 'level' => null, 'status' => null, 'link' => null, 'morph' => 'knowledge_expert'],
        'pengetahuan' => ['label' => 'Aset pengetahuan', 'words' => ['aset pengetahuan', 'pengetahuan', 'sop', 'manual book', 'dokumentasi'], 'model' => KnowledgeAsset::class, 'code' => 'record_code', 'title' => 'title', 'search' => ['title', 'record_code', 'category', 'owner_unit', 'ai_summary'], 'level' => null, 'status' => null, 'link' => 'asset_id', 'morph' => 'knowledge_asset'],
        'perubahan' => ['label' => 'Perubahan', 'words' => ['perubahan'], 'model' => Change::class, 'code' => 'change_code', 'title' => 'title', 'search' => ['title', 'change_code', 'pic'], 'level' => 'inherent_risk_level', 'status' => 'status', 'link' => 'asset_id', 'morph' => 'change'],
        'risiko' => ['label' => 'Risiko', 'words' => ['risiko'], 'model' => Risk::class, 'code' => 'risk_code', 'title' => 'title', 'search' => ['title', 'risk_code', 'threat_source', 'vulnerability', 'risk_owner'], 'level' => 'risk_level', 'status' => 'status', 'link' => 'asset_id', 'morph' => 'risk'],
        'layanan' => ['label' => 'Layanan', 'words' => ['layanan'], 'model' => Service::class, 'code' => 'service_code', 'title' => 'name', 'search' => ['name', 'service_code', 'description', 'owner_unit'], 'level' => null, 'status' => 'status', 'link' => 'service_assets', 'morph' => 'service'],
        'aset' => ['label' => 'Aset', 'words' => ['aset', 'inventaris', 'perangkat'], 'model' => Asset::class, 'code' => 'asset_code', 'title' => 'name', 'search' => ['name', 'asset_code', 'owner', 'location', 'sub_classification'], 'level' => 'criticality_level', 'status' => 'status', 'link' => 'services', 'morph' => 'asset'],
    ];

    /** Modul asisten → modul admin (AdminModules). */
    private const ADMIN_SCOPE = ['sdm' => 'sdm', 'pengetahuan' => 'pengetahuan', 'keahlian' => 'pengetahuan', 'aset' => 'aset', 'keamanan' => 'keamanan',
        'risiko' => 'risiko', 'perubahan' => 'perubahan', 'layanan' => 'layanan', 'tiket' => 'layanan', 'data_informasi' => 'data_informasi'];

    /** Urutan modul saat hasil pencarian umum sama kuat: data induk dulu, catatan turunan belakangan. */
    private const SEARCH_ORDER = ['aset', 'layanan', 'risiko', 'pengetahuan', 'keahlian', 'perubahan', 'keamanan', 'data_informasi', 'sdm', 'tiket', 'bukti'];

    private const LEVELS = ['kritis', 'tinggi', 'sedang', 'rendah'];

    private const RISK_STATUS = ['open' => 'terbuka', 'in_treatment' => 'dalam penanganan', 'monitoring' => 'pemantauan', 'closed' => 'ditutup'];

    private const MONTHS = ['januari', 'februari', 'maret', 'april', 'mei', 'juni', 'juli', 'agustus', 'september', 'oktober', 'november', 'desember'];

    private const TIMEZONE = 'Asia/Jakarta';

    /** Kalimat jawaban baku (dibuat singkat; teks penjelas panjang dihapus atas permintaan pengguna 18 Nov 2026). */
    public const NOT_FOUND = 'Maaf, informasinya belum ditemukan di aplikasi ini.';

    public const OUT_OF_SCOPE = 'Maaf, saya hanya bisa menjawab pertanyaan seputar aplikasi ini.';

    /** Kalimat yang diminta dari LLM bila bahan tidak memuat jawaban (dipetakan ke NOT_FOUND). */
    private const LLM_NOT_FOUND = 'Maaf, saya belum menemukan informasinya di aplikasi ini.';

    /**
     * Bahasa santai dan bahasa Inggris diubah ke bentuk baku sebelum
     * dipahami. Frasa (lebih dari satu kata) diganti lebih dulu.
     */
    private const PHRASES = [
        'how many' => 'berapa', 'how much' => 'berapa', 'how do i' => 'bagaimana cara', 'how to' => 'cara', 'what is' => 'apa itu',
        'show me' => 'tampilkan', 'list of' => 'daftar', 'who is' => 'siapa', 'who are' => 'siapa', 'this week' => 'minggu ini',
        'this month' => 'bulan ini', 'last week' => 'minggu lalu', 'last month' => 'bulan lalu', 'this year' => 'tahun ini',
        'still open' => 'masih terbuka', 'not yet' => 'belum', 'pending verification' => 'menunggu verifikasi',
        'belum kelar' => 'belum selesai', 'belum beres' => 'belum selesai', 'blm kelar' => 'belum selesai', 'blm beres' => 'belum selesai',
    ];

    private const WORDS = [
        // santai → baku
        'yg' => 'yang', 'blm' => 'belum', 'blom' => 'belum', 'udh' => 'sudah', 'udah' => 'sudah', 'sdh' => 'sudah', 'brp' => 'berapa',
        'gmn' => 'bagaimana', 'gimana' => 'bagaimana', 'gmna' => 'bagaimana', 'bgmn' => 'bagaimana', 'dmn' => 'di mana', 'dimana' => 'di mana',
        'tdk' => 'tidak', 'gak' => 'tidak', 'ga' => 'tidak', 'nggak' => 'tidak', 'ngga' => 'tidak', 'enggak' => 'tidak', 'kelar' => 'selesai',
        'beres' => 'selesai', 'aja' => 'saja', 'utk' => 'untuk', 'dgn' => 'dengan', 'sy' => 'saya', 'gw' => 'saya', 'gue' => 'saya',
        'krn' => 'karena', 'dr' => 'dari', 'kpn' => 'kapan', 'msh' => 'masih', 'lg' => 'lagi', 'tgl' => 'tanggal', 'bln' => 'bulan',
        'mgg' => 'minggu', 'thn' => 'tahun', 'ttg' => 'tentang', 'jg' => 'juga', 'bs' => 'bisa', 'apaan' => 'apa', 'pw' => 'kata sandi',
        'password' => 'kata sandi', 'upload' => 'unggah', 'import' => 'impor', 'edit' => 'ubah', 'delete' => 'hapus',
        'aku' => 'saya', 'kemana' => 'ke mana', 'uploaded' => 'diunggah', 'imported' => 'diimpor', 'connected' => 'terhubung',
        'linked' => 'terhubung', 'integration' => 'integrasi', 'integrated' => 'terintegrasi', 'reference' => 'referensi',
        'references' => 'referensi', 'regulation' => 'regulasi', 'regulations' => 'regulasi', 'source' => 'sumber', 'sources' => 'sumber',
        // Inggris → Indonesia
        'critical' => 'kritis', 'high' => 'tinggi', 'medium' => 'sedang', 'low' => 'rendah', 'risks' => 'risiko', 'risk' => 'risiko',
        'assets' => 'aset', 'asset' => 'aset', 'tickets' => 'tiket', 'ticket' => 'tiket', 'services' => 'layanan', 'service' => 'layanan',
        'changes' => 'perubahan', 'open' => 'terbuka', 'closed' => 'ditutup', 'pending' => 'menunggu', 'list' => 'daftar', 'show' => 'tampilkan',
        'find' => 'cari', 'search' => 'cari', 'files' => 'berkas', 'file' => 'berkas', 'documents' => 'dokumen', 'document' => 'dokumen',
        'expert' => 'ahli', 'experts' => 'ahli', 'today' => 'hari ini', 'yesterday' => 'kemarin', 'who' => 'siapa', 'how' => 'bagaimana',
        'what' => 'apa', 'where' => 'di mana', 'why' => 'kenapa', 'evidence' => 'bukti dukung', 'history' => 'riwayat',
    ];

    /** Kata yang tidak dipakai untuk mencari baris data atau berkas. */
    private const STOP = [
        'yang', 'dan', 'atau', 'dengan', 'untuk', 'dari', 'dalam', 'pada', 'kepada', 'oleh', 'itu', 'ini', 'ada', 'apa', 'apakah', 'saja', 'mana',
        'siapa', 'kapan', 'berapa', 'bagaimana', 'cara', 'caranya', 'tolong', 'mohon', 'minta', 'saya', 'kami', 'kita', 'anda',
        'bisa', 'dong', 'deh', 'sih', 'kak', 'pak', 'bu', 'min', 'halo', 'hai', 'terima', 'kasih', 'tersebut', 'sudah', 'belum',
        'akan', 'masih', 'semua', 'seluruh', 'daftar', 'tampilkan', 'tunjukkan', 'lihat', 'cari', 'carikan', 'temukan', 'cek', 'berkas',
        'dokumen', 'pdf', 'unduh', 'lampiran', 'data', 'jumlah', 'total', 'rekap', 'level', 'status', 'statusnya', 'prioritas', 'terbuka',
        'selesai', 'tentang', 'mengenai', 'soal', 'punya', 'milik', 'adalah', 'jadi', 'harus', 'perlu', 'butuh', 'mau', 'ingin',
        'sebutkan', 'buka', 'kasih', 'beri', 'berikan', 'banyak', 'tidak', 'nih', 'tuh', 'baru', 'lagi', 'juga', 'karena',
        'kritis', 'tinggi', 'sedang', 'rendah', 'p1', 'p2', 'p3', 'p4', 'modul', 'manajemen', 'menu', 'halaman', 'aplikasi', 'sistem',
        'bergantung', 'tergantung', 'terkait', 'berkaitan', 'berhubungan', 'menopang', 'ditopang', 'memakai', 'dipakai', 'menggunakan',
        'digunakan', 'masuk', 'dibuat', 'dicatat', 'ditambahkan', 'terdaftar', 'terakhir', 'terbaru', 'riwayat', 'histori', 'mengubah',
        'diubah', 'hari', 'minggu', 'bulan', 'tahun', 'lalu', 'kemarin', 'sekarang', 'saat', 'menunggu', 'keputusan', 'diputuskan',
        'verifikasi', 'diverifikasi', 'ditangani', 'admin', 'dulu', 'apa saja', 'the', 'are', 'there', 'for', 'any', 'all', 'with', 'that',
        'this', 'have', 'has', 'from', 'about', 'please', 'which', 'many',
        ...self::MONTHS,
    ];

    /** Status yang juga kata umum; dianggap status hanya bila didahului kata "status". */
    private const AMBIGUOUS_STATUS = ['baru', 'aktif', 'selesai'];

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
        $result = $this->respond($question, $page, $history);

        AiLog::record('asisten', $result['engine'], null, $question, $result['answer'], $startedAt);

        return $result;
    }

    private function respond(string $question, ?string $page, array $history): array
    {
        $flat = self::normalize($question);

        // Pertanyaan lanjutan pendek ("yang tinggi saja?") mewarisi modul dan
        // maksud pertanyaan sebelumnya; kata cari & saringan dari yang terbaru.
        // Pertanyaan riset/integrasi/unggahan berdiri sendiri: "Referensi apa saja?" sesudah
        // "Dasar aturan manajemen risiko" bukan lanjutan (uji tampilan 18 Nov 2026).
        $followUp = $history !== [] && count(self::terms($flat)) <= 2 && $this->detectModule($flat) === null
            && ! preg_match('/\b(cara|bagaimana|apa itu|siapa)\b/u', $flat)
            && self::researchIntent($flat, false) !== 'strong' && ! self::wantsConnection($flat) && ! self::mentionsOwnUpload($flat);
        $context = $followUp ? self::normalize(end($history)['q']) . ' ' . $flat : $flat;

        $intent = $this->intent($context, $followUp ? $context : $flat, $flat);
        $moduleKey = $this->detectModule($context);
        $terms = $this->searchTerms($flat, $moduleKey, $intent);

        // 1. Admin modul dan riwayat perubahan: langsung dari basis data.
        if ($intent['admin']) {
            return $this->adminAnswer($context);
        }
        if ($intent['history'] && ($logAnswer = $this->historyAnswer($flat, $moduleKey, $terms)) !== null) {
            return $logAnswer;
        }

        // 1b. Titik integrasi: berkas yang baru diunggah, data tertentu, atau modul.
        if ($intent['upload'] && ($upload = $this->uploadAnswer($flat)) !== null) {
            return $upload;
        }
        if ($intent['connect'] && ($connection = $this->connectionAnswer($flat, $moduleKey, $terms)) !== null) {
            return $connection;
        }

        // 1c. Riset rujukan (peraturan, standar, literatur di docs/riset).
        $queryVector = null;
        if ($intent['research'] === 'strong') {
            $queryVector = $this->llm->embed([$flat])[0] ?? null;
            if (($research = $this->researchAnswer($flat, $moduleKey, $queryVector, true)) !== null) {
                return self::withoutScore($research);
            }
        }

        // 2. Data modul (hitungan, daftar, saringan, relasi).
        $records = collect();
        $exact = null;
        $wantsData = $intent['list'] || $intent['code'] || $intent['count'] || $intent['filters'] !== []
            || ($moduleKey !== null && $this->mentionsModuleFilter($moduleKey, $context))
            || ($moduleKey !== null && $terms === [] && ! $intent['howto'] && ! $intent['file']);
        // "Kenapa prioritas tiket masih P1–P4?" menanyakan alasan, bukan data (uji 18 Nov 2026).
        $why = (bool) preg_match('/\b(kenapa|mengapa|alasan|alasannya)\b/u', $flat);
        if ($moduleKey !== null && ! $why && ($wantsData || ($terms !== [] && ! $intent['howto'] && ! $intent['file']))) {
            [$records, $exact, $narrowed] = $this->moduleRecords($moduleKey, $terms, $intent['filters'], $context);
            // Pencarian kata biasa tanpa hasil diteruskan ke isi dokumen,
            // mis. "kebijakan risiko apa yang berlaku?".
            if ($records->isEmpty() && ! $narrowed && ! $intent['count'] && ! $intent['list']) {
                $exact = null;
            } else {
                return $this->dataAnswer($exact, $records, $intent);
            }
        } elseif ($moduleKey === null && ! empty($intent['filters']['pending'])) {
            return ['answer' => $this->pendingSummary(), 'engine' => 'Hitungan langsung dari basis data', 'links' => []];
        } elseif ($terms !== [] && ($intent['search'] || $intent['code']) && ! $intent['file'] && ! $intent['howto']) {
            $records = $this->searchAllModules($terms);
            if ($records->isNotEmpty()) {
                return [
                    'answer' => 'Ditemukan ' . $records->count() . ' data.',
                    'engine' => 'Pencarian langsung di basis data',
                    'links' => $this->links($records, collect(), collect()),
                ];
            }
        }

        // 3. Panduan, berkas, dan isi dokumen.
        $queryVector ??= $this->llm->embed([$flat])[0] ?? null;
        $help = $this->searchHelp($flat, $queryVector);
        $files = $intent['file'] ? $this->searchFiles($terms) : collect();
        $chunks = $this->documentChunks($flat, $intent['file'] || ! $intent['howto']);
        $files = $files->concat($this->filesFromChunks($chunks))->unique('title')->take(5)->values();

        $topHelp = $help->first();
        $topChunk = $chunks->first()['score'] ?? 0;

        // "Kenapa …" / "boleh …": riset menjawab bila lebih cocok daripada panduan dan dokumen.
        // Pertanyaan yang menyebut kode data ("kenapa RSK-… kritis?") tentang data itu, bukan riset.
        if ($intent['research'] === 'weak' && ! $intent['code'] && ($research = $this->researchAnswer($flat, $moduleKey, $queryVector, false)) !== null
            && $research['score'] >= max($topHelp['score'] ?? 0, $topChunk)) {
            return self::withoutScore($research);
        }

        // Cara pakai: teks panduan apa adanya, kecuali isi dokumen jelas
        // lebih cocok (mis. "bagaimana cara melapor insiden siber?").
        if ($intent['howto'] && $topHelp && ($topHelp['strong'] || $topHelp['score'] >= $topChunk) && ! ($intent['file'] && $files->isNotEmpty())) {
            return [
                'answer' => $topHelp['text'],
                'engine' => 'Panduan aplikasi',
                'links' => $this->links(collect(), collect(), collect([$topHelp])),
            ];
        }

        // Berkas yang diminta: daftar saja, tidak dirangkai LLM.
        if ($files->isNotEmpty() && ($intent['file'] || $chunks->isEmpty())) {
            return [
                'answer' => 'Ditemukan ' . $files->count() . ' berkas.',
                'engine' => 'Pencarian langsung di basis data',
                'links' => $this->links(collect(), $files, collect()),
            ];
        }

        $useHelp = $help->filter(fn ($h) => $h['strong'])->take(2);
        if ($useHelp->isEmpty() && $chunks->isEmpty()) {
            // Sebelum menolak: mungkin riset memuat jawabannya (ambang lebih ketat bila tidak ditanyakan sebagai riset).
            $research = $this->researchAnswer($flat, $moduleKey, $queryVector, false);
            if ($research !== null && ($intent['research'] !== null || $research['score'] >= 0.62)) {
                return self::withoutScore($research);
            }

            return [
                'answer' => self::OUT_OF_SCOPE,
                'engine' => 'Asisten',
                'links' => [],
            ];
        }

        $answer = $this->compose($flat, $page, $history, $useHelp, $chunks, $files);
        // Uji 18 Nov 2026: untuk "berapa lama server disiapkan" (skor 0,72) dan
        // "berapa kali backup" (skor 0,585, 2 dari 3 kali menyerah) llama3.2
        // kadang menjawab "tidak ditemukan" padahal potongannya memuat jawaban.
        for ($retry = 0; $retry < 2 && $answer === self::NOT_FOUND && ($chunks->first()['score'] ?? 0) >= 0.55; $retry++) {
            $answer = $this->compose($flat, $page, $history, $useHelp, $chunks, $files);
        }
        $links = $this->links(collect(), $files, collect());
        if ($answer === self::NOT_FOUND && $chunks->isNotEmpty()) {
            $answer = null;
        }
        if ($answer !== null) {
            // Kutipan kalimat asli dari dokumen, supaya salah baca model
            // kecil langsung terlihat (uji 18 Nov 2026: "2 kali sehari" untuk
            // SOP yang menulis penuh harian + inkremental tiap 4 jam).
            return [
                'answer' => $answer,
                'engine' => $this->llm->chatEngineName() . ' + data aplikasi',
                'links' => $links,
                'quote' => $this->quote($chunks, $flat, $answer, $queryVector),
                // Penanda kecil di tampilan: jawaban ini disusun LLM, bukan diambil apa adanya.
                'ai' => $chunks->isNotEmpty() ? 'dokumen' : 'panduan',
            ];
        }

        return [
            'answer' => $this->fallback($useHelp, $chunks, $flat, $queryVector),
            'engine' => $this->llm->chatAvailable() ? 'Pencarian aplikasi' : 'Pencarian aplikasi (AI tidak aktif)',
            'links' => $links,
        ];
    }

    // ------------------------------------------------------------------
    // Memahami pertanyaan
    // ------------------------------------------------------------------

    /** Huruf kecil, spasi tunggal, bahasa santai/Inggris diubah ke baku. */
    public static function normalize(string $text): string
    {
        $text = ' ' . EvidenceAssessor::flatten($text) . ' ';
        $text = preg_replace('/[?!.,;:()"]+/u', ' ', $text) ?? $text;
        foreach (self::PHRASES as $from => $to) {
            $text = preg_replace('/(?<![\p{L}\p{N}])' . preg_quote($from, '/') . '(?![\p{L}\p{N}])/u', $to, $text) ?? $text;
        }
        $text = preg_replace_callback('/[\p{L}\p{N}]+/u', fn ($m) => self::WORDS[$m[0]] ?? $m[0], $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    /**
     * @return array{howto: bool, count: bool, list: bool, file: bool, code: bool, search: bool, who: bool, admin: bool, history: bool, filters: array<string, mixed>}
     */
    private function intent(string $context, string $current, string $latest): array
    {
        $filters = [];
        // Saringan dari pertanyaan terbaru menang atas pertanyaan sebelumnya.
        foreach ([$context, $latest] as $text) {
            if (preg_match('/\bp([1-4])\b/u', $text, $m)) {
                $filters['priority'] = 'P' . $m[1];
            }
            if (preg_match('/\b(kritis|tinggi|sedang|rendah)\b/u', $text, $m)) {
                $filters['level'] = $m[1];
            }
        }
        if (preg_match('/\b(masih terbuka|terbuka|belum selesai|belum ditangani|masih aktif|masih berjalan)\b/u', $context)) {
            $filters['open'] = true;
        }
        if (preg_match('/\b(menunggu verifikasi|belum diverifikasi|belum divalidasi|perlu diverifikasi)\b/u', $context)) {
            $filters['pending'] = true;
        }
        if ($range = $this->timeRange($context)) {
            $filters['time'] = $range;
        }

        return [
            'howto' => (bool) preg_match('/\b(cara|caranya|bagaimana|langkah|tutorial|panduan|fungsi|kegunaan|maksud|arti|apa itu|bisakah|di mana|menu|tombol|fitur|kenapa|mengapa|dari mana|asal|beda|bedanya|perbedaan|jelaskan|pengertian)\b/u', $current),
            'count' => (bool) preg_match('/\b(berapa|jumlah|total|rekap|statistik|banyaknya)\b/u', $current)
                && ! preg_match('/\bberapa\s+(lama|hari|jam|menit|kali|biaya|tahun|persen|karakter|huruf|digit)\b/u', $current),
            'list' => (bool) preg_match('/\b(daftar|tampilkan|tunjukkan|apa saja|mana saja|sebutkan|lihat|cari|carikan|temukan|siapa)\b/u', $context),
            'file' => (bool) preg_match('/\b(berkas|dokumen|pdf|unduh|download|lampiran|templat|template)\b/u', $context),
            'code' => (bool) preg_match('/\b[a-z]{2,5}-[a-z0-9]{2,6}-\d{3,}|\btkt-?\d{3,}/u', $context),
            'search' => (bool) preg_match('/\b(cari|carikan|temukan|tampilkan|tunjukkan|daftar)\b/u', $context),
            'who' => (bool) preg_match('/\bsiapa\b/u', $latest),
            'admin' => (bool) preg_match('/\bsiapa\b.*\badmin\b|\b(daftar|nama|semua) admin\b|\badmin\b.*\bsiapa\b/u', $latest),
            'history' => (bool) preg_match('/\b(riwayat|histori|log aktivitas|aktivitas terakhir|aktivitas terbaru)\b|\bsiapa\b.*\b(mengubah|ubah|menambah|menambahkan|membuat|menginput|input|mengarsipkan|menghapus|hapus)\b|\bkapan\b.*\b(diubah|dibuat|ditambahkan|diarsipkan|diperbarui|diinput)\b/u', $latest),
            'connect' => self::wantsConnection($latest),
            'upload' => self::mentionsOwnUpload($latest),
            'research' => self::researchIntent($latest, (bool) preg_match('/\b(berkas|dokumen|pdf|unduh|download|lampiran|templat|template)\b/u', $latest)),
            'filters' => $filters,
        ];
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string}|null rentang (UTC) dan kalimatnya */
    private function timeRange(string $text): ?array
    {
        $now = CarbonImmutable::now(self::TIMEZONE);
        $range = null;

        if (preg_match('/\bhari ini\b/u', $text)) {
            $range = [$now->startOfDay(), $now, 'hari ini'];
        } elseif (preg_match('/\bkemarin\b/u', $text)) {
            $range = [$now->subDay()->startOfDay(), $now->subDay()->endOfDay(), 'kemarin'];
        } elseif (preg_match('/\b(\d{1,3}) hari terakhir\b/u', $text, $m)) {
            $range = [$now->subDays((int) $m[1])->startOfDay(), $now, "{$m[1]} hari terakhir"];
        } elseif (preg_match('/\bminggu ini\b/u', $text)) {
            $range = [$now->startOfWeek(CarbonInterface::MONDAY), $now, 'minggu ini'];
        } elseif (preg_match('/\bminggu lalu\b/u', $text)) {
            $range = [$now->subWeek()->startOfWeek(CarbonInterface::MONDAY), $now->subWeek()->endOfWeek(CarbonInterface::SUNDAY), 'minggu lalu'];
        } elseif (preg_match('/\bbulan ini\b/u', $text)) {
            $range = [$now->startOfMonth(), $now, 'bulan ini'];
        } elseif (preg_match('/\bbulan lalu\b/u', $text)) {
            $range = [$now->subMonthNoOverflow()->startOfMonth(), $now->subMonthNoOverflow()->endOfMonth(), 'bulan lalu'];
        } elseif (preg_match('/\btahun ini\b/u', $text)) {
            $range = [$now->startOfYear(), $now, 'tahun ini'];
        } elseif (preg_match('/\b(' . implode('|', self::MONTHS) . ')(?:\s+(\d{4}))?\b/u', $text, $m)) {
            $month = array_search($m[1], self::MONTHS, true) + 1;
            $year = isset($m[2]) ? (int) $m[2] : $now->year;
            $start = CarbonImmutable::create($year, $month, 1, 0, 0, 0, self::TIMEZONE);
            $range = [$start, $start->endOfMonth(), 'pada ' . ucfirst($m[1]) . " {$year}"];
        }

        if ($range === null) {
            return null;
        }
        [$from, $to, $label] = $range;
        $span = $from->isSameDay($to) ? self::date($from) : self::date($from) . '–' . self::date($to);

        return [$from->utc(), $to->utc(), "{$label} ({$span})"];
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

    /** @return array<int, string> kata penting (≥3 huruf, bukan kata tanya/sambung) */
    private static function terms(string $text): array
    {
        $out = [];
        foreach (preg_split('/[^\p{L}\p{N}\-]+/u', Str::lower($text), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            if (in_array($word, self::STOP, true)) {
                continue;
            }
            // "risikonya", "statusnya" → kata dasarnya. Hanya akhiran -nya:
            // memotong -lah membuat "jumlah" menjadi "jum" (uji 18 Nov 2026).
            if (mb_strlen($word) > 5 && str_ends_with($word, 'nya')) {
                $word = mb_substr($word, 0, -3);
                if (in_array($word, self::STOP, true)) {
                    continue;
                }
            }
            if (mb_strlen($word) >= 3) {
                $out[] = $word;
            }
        }

        return array_values(array_unique($out));
    }

    /** Kata cari tanpa nama modul dan kata status yang sudah jadi saringan. */
    private function searchTerms(string $text, ?string $moduleKey, array $intent): array
    {
        $moduleWords = collect(self::MODULES)->flatMap(fn ($d) => $d['words'])->flatMap(fn ($w) => explode(' ', $w))->all();
        $statusWords = $moduleKey ? array_merge(...array_map(fn ($s) => explode(' ', $s), array_keys($this->statusValues($moduleKey)))) : [];
        $decisionWords = ['diterima', 'ditolak', 'perbaikan', 'tervalidasi'];

        return array_slice(array_values(array_filter(
            self::terms($text),
            fn ($t) => ! in_array($t, $moduleWords, true) && ! in_array($t, $statusWords, true) && ! in_array($t, $decisionWords, true)
        )), 0, 6);
    }

    // ------------------------------------------------------------------
    // Data modul
    // ------------------------------------------------------------------

    /**
     * Baris satu modul dengan saringan + relasi + kata.
     *
     * @return array{0: Collection<int, array>, 1: string, 2: bool} baris, kalimat jumlah persis, dan apakah ada saringan/relasi
     */
    private function moduleRecords(string $key, array $terms, array $filters, string $text): array
    {
        $def = self::MODULES[$key];
        $query = $def['model']::query();
        $applied = [];

        // Status ("diajukan", "status baru", "dalam penanganan").
        if ($def['status']) {
            foreach ($this->statusValues($key) as $word => $value) {
                $ambiguous = in_array($word, self::AMBIGUOUS_STATUS, true);
                $pattern = $ambiguous ? '/\bstatus(?:nya)?\s+(?:masih\s+)?' . preg_quote($word, '/') . '\b/u' : '/\b' . preg_quote($word, '/') . '\b/u';
                if (preg_match($pattern, $text)) {
                    $query->whereRaw('lower(' . $def['status'] . ') = ?', [Str::lower($value)]);
                    $applied[] = "berstatus {$word}";
                    break;
                }
            }
        }

        // Level / prioritas / terbuka / verifikasi.
        if ($key === 'tiket') {
            if (isset($filters['priority'])) {
                $query->where('priority', $filters['priority']);
                $applied[] = "prioritas {$filters['priority']}";
            }
            if (! empty($filters['open'])) {
                $query->where(fn ($w) => $w->whereNull('status')->orWhereNotIn(DB::raw('lower(status)'), ['selesai', 'ditutup']));
                $applied[] = 'yang masih terbuka';
            }
        } elseif (isset($filters['level']) && $def['level']) {
            $query->where($def['level'], $filters['level']);
            $applied[] = $key === 'aset' ? "berkritikalitas {$filters['level']}" : "level {$filters['level']}";
        }
        if ($key === 'risiko' && ! empty($filters['open'])) {
            $query->where(fn ($w) => $w->whereNull('status')->orWhere('status', '!=', 'closed'));
            $applied[] = 'yang masih terbuka';
        }
        if (! empty($filters['pending']) && $this->hasColumn($key, 'verification_status')) {
            $query->where('verification_status', 'menunggu_verifikasi');
            $applied[] = 'yang menunggu verifikasi admin';
        } elseif (preg_match('/\b(tervalidasi|sudah diverifikasi|sudah divalidasi)\b/u', $text) && $this->hasColumn($key, 'verification_status')) {
            $query->where('verification_status', 'tervalidasi');
            $applied[] = 'yang sudah tervalidasi';
        }

        // Bukti dukung: keputusan admin dan modul yang dinilai.
        if ($key === 'bukti') {
            if (preg_match('/\b(belum diputuskan|belum ada keputusan|menunggu keputusan|menunggu admin|belum dinilai admin|menunggu)\b/u', $text)) {
                $query->whereNull('reviewer_decision');
                $applied[] = 'yang belum diputuskan admin';
            } elseif (preg_match('/\b(diterima|ditolak|perlu perbaikan)\b/u', $text, $m)) {
                $query->where('reviewer_decision', str_replace(' ', '_', $m[1]));
                $applied[] = "yang {$m[1]}";
            }
            foreach (AdminModules::LABELS as $moduleKeyFor => $label) {
                $short = Str::lower(Str::after($label, 'Manajemen '));
                if (str_contains($text, $short) || ($moduleKeyFor === 'sdm' && str_contains($text, 'sdm'))) {
                    $query->where('module_key', $moduleKeyFor);
                    $applied[] = "untuk {$label}";
                    $terms = array_values(array_diff($terms, explode(' ', $short)));
                    break;
                }
            }
        }

        // Waktu catatan.
        if (isset($filters['time'])) {
            [$from, $to, $label] = $filters['time'];
            $column = $key === 'tiket' ? DB::raw('coalesce(reported_at, created_at)') : 'created_at';
            $query->whereBetween($column, [$from, $to]);
            $applied[] = ($key === 'tiket' ? 'yang masuk ' : 'yang dicatat ') . $label;
        }

        // Relasi antarmodul ("risiko pada aset X", "tiket layanan X").
        if ($terms !== [] && ($relation = $this->relation($key, $terms)) !== null) {
            [$apply, $phrase] = $relation;
            $apply($query);
            $applied[] = $phrase;
            $terms = [];
        }

        if ($terms !== []) {
            $query->where(function ($q) use ($def, $terms) {
                foreach ($terms as $term) {
                    foreach ($def['search'] as $column) {
                        $q->orWhere($column, 'ilike', '%' . $term . '%');
                    }
                }
            });
            $applied[] = 'yang cocok dengan "' . implode(' ', $terms) . '"';
        }

        $count = (clone $query)->count();
        $what = Str::lower($def['label']) . ($applied ? ' ' . implode(' ', $applied) : '');
        $exact = $count === 0 ? "Tidak ada {$what}." : "Ada {$count} {$what}.";

        $rows = $query->limit(60)->get();
        $rows = $terms === [] ? $this->sortBySeverity($rows, $def) : $this->rankByTerms($rows, $def, $terms);
        $narrowed = count($applied) > ($terms === [] ? 0 : 1);

        return [$rows->take(6)->map(fn ($row) => $this->describe($key, $row))->values(), $exact, $narrowed];
    }

    /** Apakah pertanyaan menyebut status, keputusan, atau verifikasi untuk modul ini. */
    private function mentionsModuleFilter(string $key, string $text): bool
    {
        if ($key === 'bukti' && preg_match('/\b(belum diputuskan|belum ada keputusan|menunggu|diterima|ditolak|perlu perbaikan)\b/u', $text)) {
            return true;
        }
        if (preg_match('/\b(tervalidasi|sudah diverifikasi|sudah divalidasi)\b/u', $text)) {
            return true;
        }
        foreach (array_keys($this->statusValues($key)) as $word) {
            $pattern = in_array($word, self::AMBIGUOUS_STATUS, true)
                ? '/\bstatus(?:nya)?\s+(?:masih\s+)?' . preg_quote($word, '/') . '\b/u'
                : '/\b' . preg_quote($word, '/') . '\b/u';
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, string> kata status di pertanyaan => nilai di kolom */
    private function statusValues(string $key): array
    {
        $def = self::MODULES[$key];
        if (! $def['status']) {
            return [];
        }

        return Cache::remember("ai.asisten.status.{$key}", 300, function () use ($def, $key) {
            $values = $def['model']::query()->whereNotNull($def['status'])->distinct()->pluck($def['status'])
                ->map(fn ($v) => $v instanceof BackedEnum ? $v->value : (string) $v);
            $map = [];
            foreach ($values as $value) {
                $word = $key === 'risiko' ? (self::RISK_STATUS[$value] ?? $value) : Str::lower($value);
                if ($word !== 'terbuka') { // "terbuka" sudah ditangani saringan open
                    $map[$word] = $value;
                }
            }

            return $map;
        });
    }

    /**
     * Cari "jangkar" (aset atau layanan) yang disebut di pertanyaan, lalu
     * kembalikan saringan untuk modul tujuan.
     *
     * @return array{0: callable, 1: string}|null
     */
    private function relation(string $key, array $terms): ?array
    {
        $assets = fn () => $this->anchors(Asset::class, ['name', 'asset_code'], 'asset_code', 'name', $terms);
        $services = fn () => $this->anchors(Service::class, ['name', 'service_code'], 'service_code', 'name', $terms);
        $label = fn (Collection $rows, string $codeCol, string $nameCol) => $rows->take(2)->map(fn ($r) => $r->{$codeCol} . ' ' . $r->{$nameCol})->implode(', ')
            . ($rows->count() > 2 ? ' dan ' . ($rows->count() - 2) . ' lainnya' : '');

        switch (self::MODULES[$key]['link']) {
            case 'asset_id':
                if (($found = $assets()) !== null) {
                    return [fn ($q) => $q->whereIn('asset_id', $found->pluck('id')), 'pada aset ' . $label($found, 'asset_code', 'name')];
                }
                if (($found = $services()) !== null) {
                    $ids = DB::table('service_assets')->whereIn('service_id', $found->pluck('id'))->pluck('asset_id');

                    return [fn ($q) => $q->whereIn('asset_id', $ids), 'pada aset layanan ' . $label($found, 'service_code', 'name')];
                }
                break;
            case 'service_assets':
                if (($found = $assets()) !== null) {
                    return [fn ($q) => $q->whereHas('assets', fn ($a) => $a->whereIn('assets.id', $found->pluck('id'))), 'yang bergantung pada aset ' . $label($found, 'asset_code', 'name')];
                }
                break;
            case 'service_id':
                if (($found = $services()) !== null) {
                    return [fn ($q) => $q->whereIn('service_id', $found->pluck('id')), 'untuk ' . $label($found, 'service_code', 'name')];
                }
                if (($found = $assets()) !== null) {
                    $ids = DB::table('service_assets')->whereIn('asset_id', $found->pluck('id'))->pluck('service_id');

                    return [fn ($q) => $q->whereIn('service_id', $ids), 'untuk layanan yang bergantung pada aset ' . $label($found, 'asset_code', 'name')];
                }
                break;
            case 'services':
                if (($found = $services()) !== null) {
                    return [fn ($q) => $q->whereHas('services', fn ($s) => $s->whereIn('services.id', $found->pluck('id'))), 'yang menopang ' . $label($found, 'service_code', 'name')];
                }
                break;
        }

        return null;
    }

    /**
     * Baris aset/layanan yang namanya paling cocok dengan kata pertanyaan.
     * Harus cocok dengan sebagian besar kata (≥60%), supaya "risiko
     * peretasan website" tidak dianggap "risiko pada aset Website ...".
     */
    private function anchors(string $model, array $columns, string $codeCol, string $nameCol, array $terms): ?Collection
    {
        $rows = $model::query()->where(function ($q) use ($columns, $terms) {
            foreach ($terms as $term) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'ilike', '%' . $term . '%');
                }
            }
        })->limit(50)->get();
        if ($rows->isEmpty()) {
            return null;
        }

        $scored = $rows->map(fn ($r) => ['row' => $r, 'hits' => $this->countHits($r->{$codeCol} . ' ' . $r->{$nameCol}, $terms)]);
        $best = $scored->max('hits');
        $needed = max(1, (int) ceil(count($terms) * 0.6));
        if ($best < $needed || (count($terms) === 1 && mb_strlen($terms[0]) < 5 && ! preg_match('/\d/', $terms[0]))) {
            return null;
        }

        return $scored->where('hits', $best)->pluck('row')->take(5)->values();
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

            foreach ($rows as $row) {
                $titleHits = $this->countHits($this->plain($row->{$def['code']}) . ' ' . $this->plain($row->{$def['title']}), $terms);
                $found->push([
                    'hits' => $this->termHits($row, $def, $terms) + $titleHits,
                    'rank' => array_search($key, self::SEARCH_ORDER, true),
                    'item' => $this->describe($key, $row),
                ]);
            }
        }

        $best = $found->max('hits') ?? 0;

        return $found->filter(fn ($f) => $f['hits'] >= max(1, $best - 1))
            ->sortBy([['hits', 'desc'], ['rank', 'asc']])
            ->take(6)
            ->pluck('item')
            ->values();
    }

    private function termHits(Model $row, array $def, array $terms): int
    {
        return $this->countHits(collect($def['search'])->map(fn ($c) => $this->plain($row->{$c}))->implode(' '), $terms);
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
        $status = $def['status'] ? $this->plain($row->{$def['status']}) : null;
        $status = self::RISK_STATUS[$status] ?? $status;

        $meta = collect([
            $def['label'],
            $key === 'pengetahuan' ? $code : null,
            $level ? match ($key) { 'tiket' => "prioritas {$level}", 'aset' => "kritikalitas {$level}", default => "level {$level}" } : null,
            $status ? "status {$status}" : null,
            $key === 'keahlian' ? $row->jabatan_unit : null,
            $key === 'keahlian' ? Str::limit((string) $row->keahlian_spesifik, 60) : null,
            $key === 'bukti' ? $row->moduleLabel() : null,
            $key === 'bukti' && $row->achieved_level !== null ? "hasil Level {$row->achieved_level}, skor {$row->score}" : null,
            $key === 'bukti' ? ($row->reviewer_decision ? EvidenceDocument::DECISIONS[$row->reviewer_decision] ?? $row->reviewer_decision : 'belum diputuskan') : null,
        ])->filter()->implode(' · ');

        $kind = 'data';
        $url = $this->recordUrl($key, $row, $code);
        if ($key === 'bukti') {
            [$kind, $url] = ['file', route('evidence.file', $row)];
        } elseif ($key === 'pengetahuan' && ($row->document_path || $row->external_link)) {
            [$kind, $url] = ['file', $row->document_path ? Storage::disk('public')->url($row->document_path) : $row->external_link];
        }

        return [
            'kind' => $kind,
            'title' => trim(($code && $kind === 'data' ? $code . ' · ' : '') . $title),
            'meta' => $meta,
            'url' => $url,
            'line' => "{$code}: {$title} ({$meta})",
            'name' => $title,
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

    private function dataAnswer(string $exact, Collection $records, array $intent): array
    {
        $answer = $exact;
        if ($records->isNotEmpty()) {
            // "Siapa ...": sebut namanya langsung; selebihnya cukup daftar tautan.
            if ($intent['who'] && $records->count() <= 5) {
                $answer = rtrim($answer, '.') . ': ' . $records->pluck('name')->implode(', ') . '.';
            }
        }

        // Semua baris dari modul yang sama: nama modul di setiap baris tidak perlu diulang.
        $label = Str::before((string) ($records->first()['meta'] ?? ''), ' · ');
        $records = $records->map(fn ($r) => ['meta' => Str::after($r['meta'], $label . ' · ')] + $r);

        return ['answer' => $answer, 'engine' => 'Hitungan langsung dari basis data', 'links' => $this->links($records, collect(), collect())];
    }

    private function pendingSummary(): string
    {
        $parts = collect(AdminModules::MODELS)->map(function ($models, $key) {
            $n = collect($models)->sum(fn ($m) => $m::query()->where('verification_status', 'menunggu_verifikasi')->count());

            return $n > 0 ? Str::after(AdminModules::LABELS[$key], 'Manajemen ') . " {$n}" : null;
        })->filter();
        $total = collect(AdminModules::MODELS)->flatten()->sum(fn ($m) => $m::query()->where('verification_status', 'menunggu_verifikasi')->count());

        return $total === 0
            ? 'Tidak ada data yang menunggu verifikasi admin.'
            : "Ada {$total} data yang menunggu verifikasi admin: " . $parts->implode(', ') . '.';
    }

    // ------------------------------------------------------------------
    // Admin modul dan riwayat perubahan
    // ------------------------------------------------------------------

    private function adminAnswer(string $text): array
    {
        $scope = null;
        foreach (AdminModules::LABELS as $key => $label) {
            $short = Str::lower(Str::after($label, 'Manajemen '));
            if (str_contains($text, $short)) {
                $scope = $key;
                break;
            }
        }
        $scope ??= self::ADMIN_SCOPE[$this->detectModule($text) ?? ''] ?? null;

        $admins = User::query()->where('role', 'admin')->whereNotNull('admin_module')
            ->when($scope, fn ($q) => $q->where('admin_module', $scope))
            ->orderBy('admin_module')->get(['name', 'admin_module']);

        if ($admins->isEmpty()) {
            $answer = $scope ? 'Belum ada admin untuk ' . AdminModules::label($scope) . '.' : 'Belum ada admin modul.';
        } elseif ($scope) {
            $answer = 'Admin ' . AdminModules::label($scope) . ': ' . $admins->pluck('name')->implode(', ') . '.';
        } else {
            $answer = "Admin tiap modul:\n" . $admins->map(fn ($u) => '• ' . AdminModules::label($u->admin_module) . ': ' . $u->name)->implode("\n");
        }

        return ['answer' => $answer, 'engine' => 'Langsung dari basis data', 'links' => []];
    }

    private function historyAnswer(string $text, ?string $moduleKey, array $terms): ?array
    {
        $terms = array_values(array_diff($terms, ['aktivitas', 'log', 'perubahan']));
        $subject = $this->findSubject($text, $moduleKey, $terms);

        if ($subject === null) {
            if ($terms !== [] || ($moduleKey !== null && $moduleKey !== 'perubahan')) {
                return null;
            }
            $logs = ActivityLog::query()->with('user:id,name')->latest('performed_at')->limit(6)->get();
            $lines = $logs->unique(fn ($log) => $log->loggable_type . $log->loggable_id . $log->action_type . $log->performed_at)->map(function ($log) {
                $key = collect(self::MODULES)->search(fn ($d) => $d['morph'] === $log->loggable_type);
                $model = $key ? self::MODULES[$key]['model']::query()->withoutGlobalScopes()->find($log->loggable_id) : null;
                $what = $model ? $this->plain($model->{self::MODULES[$key]['code']}) . ' ' . Str::limit($this->plain($model->{self::MODULES[$key]['title']}), 50) : $log->loggable_type . ' #' . $log->loggable_id;

                return '• ' . $this->logLine($log) . ': ' . $what;
            });

            return [
                'answer' => $lines->isEmpty() ? 'Belum ada aktivitas tercatat.' : "Aktivitas terbaru:\n" . $lines->implode("\n"),
                'engine' => 'Langsung dari log aktivitas',
                'links' => [],
            ];
        }

        [$key, $row] = $subject;
        $item = $this->describe($key, $row);
        $logs = self::MODULES[$key]['morph']
            ? ActivityLog::query()->with('user:id,name')->where('loggable_type', self::MODULES[$key]['morph'])->where('loggable_id', $row->getKey())->latest('performed_at')->limit(5)->get()
            : collect();

        if ($logs->isEmpty()) {
            $answer = "Belum ada riwayat perubahan tercatat untuk {$item['title']}.";
        } else {
            $answer = "Riwayat {$item['title']}:\n" . $logs->map(fn ($log) => '• ' . $this->logLine($log))->unique()->implode("\n");
        }

        return ['answer' => $answer, 'engine' => 'Langsung dari log aktivitas', 'links' => $this->links(collect([$item]), collect(), collect())];
    }

    /** @return array{0: string, 1: Model}|null */
    private function findSubject(string $text, ?string $moduleKey, array $terms): ?array
    {
        // Kode yang disebut persis (AST-2026-0001, TKT-000003).
        if (preg_match('/\btkt-?0*(\d{1,9})\b/u', $text, $m) && ($row = ServiceTicket::find((int) $m[1]))) {
            return ['tiket', $row];
        }
        if (preg_match('/\b([a-z]{2,5}-[a-z0-9]{2,6}-\d{3,6})\b/u', $text, $m)) {
            foreach (self::MODULES as $key => $def) {
                if ($key !== 'tiket' && ($row = $def['model']::query()->whereRaw('lower(' . $def['code'] . ') = ?', [$m[1]])->first())) {
                    return [$key, $row];
                }
            }
        }
        if ($moduleKey === null || $terms === []) {
            return null;
        }

        $def = self::MODULES[$moduleKey];
        $rows = $this->rankByTerms($def['model']::query()->where(function ($q) use ($def, $terms) {
            foreach ($terms as $term) {
                foreach ($def['search'] as $column) {
                    $q->orWhere($column, 'ilike', '%' . $term . '%');
                }
            }
        })->limit(20)->get(), $def, $terms);

        return $rows->isEmpty() ? null : [$moduleKey, $rows->first()];
    }

    private function logLine(ActivityLog $log): string
    {
        $action = ['created' => 'dibuat', 'updated' => 'diubah', 'archived' => 'diarsipkan', 'deleted' => 'dihapus', 'restored' => 'dipulihkan'][$log->action_type] ?? $log->action_type;
        $who = $log->user?->name ?? 'sistem (otomatis)';
        $fields = $log->action_type === 'updated'
            ? collect(array_keys($log->new_values ?? []))->reject(fn ($f) => in_array($f, ['updated_at', 'last_activity_at'], true))->take(3)->implode(', ')
            : '';

        return self::date($log->performed_at, true) . " — {$action} oleh {$who}" . ($fields ? " (kolom: {$fields})" : '');
    }

    private static function date($date, bool $withTime = false): string
    {
        $date = ($date instanceof DateTimeInterface ? CarbonImmutable::instance($date) : CarbonImmutable::parse($date))->setTimezone(self::TIMEZONE);
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return $date->day . ' ' . $months[$date->month - 1] . ' ' . $date->year . ($withTime ? ' ' . $date->format('H.i') . ' WIB' : '');
    }

    // ------------------------------------------------------------------
    // Panduan, berkas, dan isi dokumen
    // ------------------------------------------------------------------

    /** @return Collection<int, array{title: string, text: string, url: ?string, page: ?string, score: float, strong: bool}> */
    private function searchHelp(string $question, ?array $queryVector): Collection
    {
        $entries = AppHelp::entries();
        $vectors = $queryVector ? $this->helpVectors($entries) : null;
        $terms = self::terms($question);

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
        })->sortByDesc('score')->take(3)->filter(fn ($e) => $e['score'] >= ($vectors ? 0.55 : 0.34))->values();
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

    private function searchFiles(array $terms): Collection
    {
        if ($terms === []) {
            return collect();
        }

        $match = function (Builder $query, array $columns) use ($terms) {
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
            ->map(fn ($doc) => ['hits' => 3 * $this->countHits($doc->title . ' ' . $doc->unit_name, $terms), 'item' => $this->describe('bukti', $doc)]);

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
            'line' => "{$doc->title} ({$doc->record_code})",
        ];
    }

    /** Potongan isi PDF pengetahuan yang paling dekat dengan pertanyaan. */
    private function documentChunks(string $question, bool $lenient): Collection
    {
        try {
            $hits = (new KnowledgeSearch($this->llm))->retrieve($question, 3);
        } catch (Throwable) {
            return collect();
        }

        $min = $this->llm->embedAvailable() ? ($lenient ? 0.5 : 0.56) : 0.5;

        $hits = $hits->filter(fn ($hit) => $hit['score'] >= $min)->values();
        $best = $hits->first()['score'] ?? 0;

        return $hits->filter(fn ($hit) => $hit['score'] >= $best - 0.04)->values();
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
        $helpLinks = $help->filter(fn ($h) => $h['url'] ?? null)->unique('url')->take(1)->map(fn ($h) => [
            'kind' => 'page', 'title' => 'Buka ' . ($h['page'] ?? 'halaman'), 'meta' => null, 'url' => $h['url'],
        ]);

        return $files->unique('title')->concat($records)->concat($helpLinks)
            ->unique('url')
            ->take(8)
            ->map(fn ($l) => ['kind' => $l['kind'], 'title' => $l['title'], 'meta' => $l['meta'], 'url' => $l['url']])
            ->values()
            ->all();
    }

    /** LLM merangkai jawaban dari panduan dan isi dokumen; dibuang bila mengarang. */
    private function compose(string $question, ?string $page, array $history, Collection $help, Collection $chunks, Collection $files): ?string
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
        if ($chunks->isNotEmpty()) {
            $sections[] = "Isi dokumen pengetahuan:\n" . $chunks->values()->map(
                fn ($hit, $i) => '[' . ($i + 1) . '] ' . $hit['chunk']->knowledgeAsset->title . ":\n" . Str::limit($hit['chunk']->content, 1000)
            )->implode("\n\n");
        }

        $rules = [
            'Jawab langsung dalam paling banyak 100 kata, tanpa judul, tanpa salam, dan tanpa mengulang nama bagian bahan.',
            'Jangan menulis langkah memakai aplikasi kecuali tertulis di panduan dan ditanyakan.',
            'Salin angka, nama, dan istilah persis dari bahan. Jangan menghitung sendiri dan jangan menambah contoh.',
            'Untuk pertanyaan berapa, kapan, jadwal, atau frekuensi, salin kalimat dokumen yang memuat jawabannya apa adanya.',
            'Pakai hanya bahan yang benar-benar menjawab pertanyaan; abaikan sisanya.',
            'Jangan menulis alamat web atau tautan.',
            'Bila bahan tidak memuat jawabannya, tulis hanya: ' . self::LLM_NOT_FOUND,
        ];

        $answer = $this->llm->generate(
            implode("\n\n", $sections) . "\n\nPertanyaan: {$question}\n\nAturan menjawab:\n- " . implode("\n- ", $rules),
            false,
            'Anda Asisten aplikasi BSSN ISMS (Sistem Manajemen Keamanan Informasi) di Dinas Komunikasi dan Informatika. '
            . 'Jawab dalam Bahasa Indonesia yang singkat, sopan, dan jelas, HANYA berdasarkan bahan yang diberikan. '
            . 'Jangan mengarang menu, angka, nama, kode, atau isi dokumen. Jangan pernah meminta atau menyebut kata sandi.'
        );
        if (! is_string($answer)) {
            return null;
        }

        $answer = self::tidy($answer);
        $material = implode("\n", $sections) . "\n" . $question . "\n" . $files->pluck('line')->implode("\n");

        return self::invents($answer, $material) ? null : $answer;
    }

    /**
     * Apakah jawaban menyebut kode data atau angka yang tidak ada di bahan.
     * Nomor urut daftar ("1.", "2)") diabaikan.
     */
    public static function invents(string $answer, string $material): bool
    {
        preg_match_all('/\b(?:[A-Z]{2,5}-[A-Z0-9]{2,6}-\d{3,6}|TKT-\d{6})\b/u', $answer, $codes);
        foreach ($codes[0] as $code) {
            if (! str_contains($material, $code)) {
                return true;
            }
        }

        $body = preg_replace('/^\s*\d+[.)]\s+/mu', '', $answer) ?? $answer;
        preg_match_all('/\d+(?:[.,]\d+)?/u', $body, $numbers);
        foreach ($numbers[0] as $number) {
            if (! preg_match('/(?<![\d])' . preg_quote($number, '/') . '(?![\d])/u', $material)) {
                return true;
            }
        }

        return false;
    }

    /** Kalimat dokumen yang paling cocok dengan pertanyaan dan jawaban, sebagai kutipan. */
    private function quote(Collection $chunks, string $question, string $answer, ?array $queryVector = null): ?string
    {
        $hit = $chunks->first();
        $best = $hit ? $this->bestSentences($hit['chunk']->content, $question, $answer, $queryVector, 1) : null;

        return $best ? Str::limit($best, 260) : null;
    }

    /**
     * Kalimat (bukan judul berhuruf kapital) dari sebuah potongan dokumen
     * yang paling mirip dengan pertanyaan, dan dengan jawaban bila ada.
     * Mengembalikan kalimat terbaik ditambah ($take - 1) kalimat sesudahnya.
     */
    private function bestSentences(string $content, string $question, string $answer, ?array $queryVector, int $take): ?string
    {
        $text = preg_replace('/\s+/u', ' ', $content) ?? '';
        $sentences = [];
        $carry = '';
        foreach (preg_split('/(?<=\.)\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) as $part) {
            // Penanda butir ("a.", "1.") digabung dengan kalimat sesudahnya.
            if (mb_strlen($part) <= 3) {
                $carry .= $part . ' ';
                continue;
            }
            $sentences[] = trim($carry . $part);
            $carry = '';
        }
        // Buang judul berhuruf kapital yang menempel di depan kalimat dan
        // penomoran "1. Jadwal Pencadangan a." di awal.
        $sentences = array_map(fn ($s) => trim(preg_replace('/^(?:[^\p{Ll}]*\p{Lu}{3,}[^\p{Ll}]*\s+)?(?:(?:\d{1,2}|[a-z])\.\s+)*/u', '', $s) ?? $s), $sentences);
        $sentences = array_values(array_filter($sentences, function ($s) {
            $letters = preg_replace('/[^\p{L}]/u', '', $s) ?? '';
            $upper = preg_replace('/[^\p{Lu}]/u', '', $s) ?? '';

            return mb_strlen($s) >= 25 && mb_strlen($letters) > 0 && mb_strlen($upper) / mb_strlen($letters) < 0.5;
        }));
        if ($sentences === []) {
            return null;
        }

        $top = null;
        $inputs = $answer !== '' ? [...$sentences, $answer] : $sentences;
        $vectors = $queryVector ? $this->llm->embed($inputs) : null;
        if ($vectors) {
            $answerVector = $answer !== '' ? array_pop($vectors) : null;
            $scores = array_map(
                fn ($v) => $answerVector ? (LocalLlm::cosine($queryVector, $v) + LocalLlm::cosine($answerVector, $v)) / 2 : LocalLlm::cosine($queryVector, $v),
                $vectors
            );
            arsort($scores);
            $top = $scores[array_key_first($scores)] >= 0.45 ? array_key_first($scores) : null;
        } else {
            $questionTerms = self::terms($question);
            $answerTerms = self::terms($answer);
            $bestScore = 0;
            foreach ($sentences as $i => $sentence) {
                $score = 2 * $this->countHits($sentence, $questionTerms) + $this->countHits($sentence, $answerTerms);
                if ($score > $bestScore) {
                    [$top, $bestScore] = [$i, $score];
                }
            }
            $top = $bestScore >= 3 ? $top : null;
        }

        return $top === null ? null : implode(' ', array_slice($sentences, $top, $take));
    }

    /** Jawaban tanpa LLM: bahan terbaik disajikan apa adanya. */
    private function fallback(Collection $help, Collection $chunks, string $question = '', ?array $queryVector = null): string
    {
        if ($chunks->isNotEmpty()) {
            $hit = $chunks->first();
            $part = $this->bestSentences($hit['chunk']->content, $question, '', $queryVector, 2) ?? $hit['chunk']->content;

            return Str::limit($part, 500);
        }
        if ($help->isNotEmpty()) {
            return $help->first()['text'];
        }

        return self::NOT_FOUND;
    }

    /** Merapikan kebiasaan model kecil: markdown, alamat web, judul bagian, baris kosong ganda. */
    public static function tidy(string $answer): string
    {
        $answer = str_replace(['**', '__', '###', '##'], '', $answer);
        $answer = preg_replace('/^[ \t]*[\*\-][ \t]+/mu', '• ', $answer) ?? $answer;
        $answer = preg_replace('~\(?https?://\S+\)?~u', '', $answer) ?? $answer;
        $answer = preg_replace('/^.*\[(tautan|link)[^\]]*\].*$/imu', '', $answer) ?? $answer;
        $answer = preg_replace('/^[ \t]*(jawaban|asisten)[ \t]*:[ \t]*/imu', '', $answer) ?? $answer;
        $answer = preg_replace('/^[ \t]*(panduan aplikasi|isi dokumen[^:\n]*|dokumen sumber|cara memakai aplikasi[^:\n]*|judul)[ \t]*:[ \t]*$/imu', '', $answer) ?? $answer;
        $answer = trim(preg_replace("/\n\s*\n+/u", "\n", $answer) ?? $answer);

        if (preg_match('/^maaf, saya belum menemukan informasinya/iu', $answer)) {
            return self::NOT_FOUND;
        }
        // Model kadang menempelkan kalimat "tidak ditemukan" di ujung jawaban
        // yang sudah benar; buang kalimat itu (dan nomor butirnya).
        $answer = preg_replace('/^[ \t]*(?:\d+[.)][ \t]*)?maaf, saya belum menemukan[^\n]*$/imu', '', $answer) ?? $answer;
        $answer = preg_replace('/\s*maaf, saya belum menemukan informasinya di aplikasi ini\.?/iu', '', $answer) ?? $answer;
        $answer = trim(preg_replace("/\n\s*\n+/u", "\n", $answer) ?? $answer);

        return Str::limit($answer, 1500);
    }

    private static function withoutScore(array $result): array
    {
        unset($result['score']);

        return $result;
    }

    private function plain(mixed $value): ?string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return $value === null ? null : (string) $value;
    }
}
