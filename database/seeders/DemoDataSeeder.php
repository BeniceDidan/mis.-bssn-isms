<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Change;
use App\Models\DataInformation;
use App\Models\EvidenceDocument;
use App\Models\HrRisk;
use App\Models\KnowledgeActivity;
use App\Models\KnowledgeAsset;
use App\Models\KnowledgeExpert;
use App\Models\KnowledgeRisk;
use App\Models\Risk;
use App\Models\SecurityProgram;
use App\Models\Service;
use App\Models\ServiceEvaluation;
use App\Models\ServiceTicket;
use App\Models\User;
use App\Services\Ai\EvidenceAssessor;
use App\Services\Ai\LocalLlm;
use App\Services\Ai\TicketTriage;
use App\Services\KritikalitasService;
use App\Services\RiskLevelService;
use App\Support\SimplePdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Data demo pengganti dataset Railway yang sudah tidak ada (server
 * Railway mati per November 2026). Semua isi FIKTIF dan aman ditampilkan.
 *
 *   php artisan db:seed --class=DemoDataSeeder --force
 *   php artisan pengetahuan:indeks
 *
 * Aman dijalankan ulang: berhenti bila data aset sudah ada.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensureUsers();

        if (Asset::exists()) {
            $this->command?->warn('Data aset sudah ada — seeder demo dilewati agar data tidak dobel.');

            return;
        }

        $assets = $this->seedAssets();
        $services = $this->seedServices($assets);
        $this->seedRisks($assets);
        $this->seedOtherModules($assets);
        $this->seedTickets($services);
        $this->seedKnowledge($assets);
        $this->seedEvidence();

        $this->command?->info('Data demo selesai. Jalankan "php artisan pengetahuan:indeks" agar PDF Pengetahuan bisa ditanya.');
    }

    /** Akun demo (kata sandi seperti di CLAUDE.md); tidak menimpa akun yang sudah ada. */
    private function ensureUsers(): void
    {
        $accounts = [
            ['admin', 'Admin Aset', 'admin', 'aset'],
            ['admin.sdm', 'Admin SDM', 'admin', 'sdm'],
            ['admin.pengetahuan', 'Admin Pengetahuan', 'admin', 'pengetahuan'],
            ['admin.keamanan', 'Admin Keamanan Informasi', 'admin', 'keamanan'],
            ['admin.risiko', 'Admin Risiko', 'admin', 'risiko'],
            ['admin.perubahan', 'Admin Perubahan', 'admin', 'perubahan'],
            ['admin.layanan', 'Admin Layanan', 'admin', 'layanan'],
            ['admin.data', 'Admin Data dan Informasi', 'admin', 'data_informasi'],
            ['user', 'Pengguna Biasa', 'user', null],
        ];

        foreach ($accounts as [$username, $name, $role, $module]) {
            User::firstOrCreate(['username' => $username], [
                'name' => $name,
                'email' => str_replace('.', '-', $username) . '@contoh.go.id',
                'password' => $role === 'admin' ? 'admin123' : 'user123',
                'role' => $role,
                'admin_module' => $module,
            ]);
        }
    }

    /** @return array<string, Asset> */
    private function seedAssets(): array
    {
        // Skor kerahasiaan/keutuhan/ketersediaan; kritikalitas dihitung dengan
        // rumus yang sama seperti form Aset (KritikalitasService), tidak diketik.
        $rows = [
            'srv-app' => ['perangkat_keras', 'Server', 'Server Aplikasi Utama (VM Cluster)', 'Bidang Aplikasi Informatika', 'Pusat Data Lt. 2', [3, 4, 5]],
            'srv-db' => ['perangkat_keras', 'Server', 'Server Basis Data Terpusat', 'Bidang Aplikasi Informatika', 'Pusat Data Lt. 2', [5, 5, 5]],
            'firewall' => ['perangkat_keras', 'Perangkat Jaringan', 'Firewall Perimeter Pusat Data', 'Bidang Persandian', 'Pusat Data Lt. 2', [4, 4, 5]],
            'router' => ['perangkat_keras', 'Perangkat Jaringan', 'Router Inti Jaringan Intra Pemerintah', 'Bidang Infrastruktur', 'Ruang Jaringan', [3, 4, 4]],
            'esurat' => ['perangkat_lunak', 'Aplikasi Umum', 'Aplikasi e-Surat dan TTE', 'Sekretariat', 'Server Aplikasi Utama', [4, 4, 4]],
            'satudata' => ['perangkat_lunak', 'Aplikasi Khusus', 'Portal Satu Data', 'Bidang Statistik', 'Server Aplikasi Utama', [2, 4, 4]],
            'website' => ['perangkat_lunak', 'Situs Web', 'Website Resmi Pemerintah Kota', 'Bidang Informasi Publik', 'Server Aplikasi Utama', [1, 4, 4]],
            'simpeg' => ['perangkat_lunak', 'Aplikasi Umum', 'Aplikasi Kepegawaian', 'Bagian Kepegawaian', 'Server Aplikasi Utama', [3, 3, 3]],
            'data-pend' => ['data_informasi', 'Basis Data', 'Data Kependudukan Terintegrasi', 'Bidang Statistik', 'Server Basis Data Terpusat', [5, 5, 4]],
            'genset' => ['sarana_pendukung', 'Kelistrikan', 'Genset dan UPS Ruang Server', 'Bidang Infrastruktur', 'Pusat Data Lt. 1', [1, 2, 3]],
        ];

        $kritikalitas = new KritikalitasService();
        $assets = [];
        foreach ($rows as $key => [$category, $sub, $name, $owner, $location, [$c, $i, $a]]) {
            $assets[$key] = Asset::create([
                'category' => $category,
                'sub_classification' => $sub,
                'name' => $name,
                'owner' => $owner,
                'location' => $location,
                'status' => 'Aktif',
                'criticality_level' => $kritikalitas->compute($c, $i, $a),
                'confidentiality_score' => $c,
                'integrity_score' => $i,
                'availability_score' => $a,
                'verification_status' => 'tervalidasi',
                'reference_year' => 2026,
                'dynamic_data' => [],
            ]);
        }

        return $assets;
    }

    /** @return array<string, Service> */
    private function seedServices(array $assets): array
    {
        $rows = [
            'esurat' => ['Layanan e-Surat dan Tanda Tangan Elektronik', 'Persuratan dinas elektronik antar-OPD dengan TTE.', 'Sekretariat', '99,5%', ['esurat', 'srv-app', 'srv-db']],
            'satudata' => ['Layanan Portal Satu Data', 'Publikasi dan berbagi pakai data sektoral.', 'Bidang Statistik', '99%', ['satudata', 'srv-app', 'data-pend']],
            'website' => ['Layanan Website Resmi', 'Informasi publik pemerintah kota.', 'Bidang Informasi Publik', '99%', ['website', 'srv-app', 'firewall']],
            'hosting' => ['Layanan Server dan Hosting', 'Penyediaan VM dan hosting aplikasi OPD di pusat data.', 'Bidang Aplikasi Informatika', '99,5%', ['srv-app', 'firewall', 'genset']],
            'integrasi' => ['Layanan Integrasi Data (API)', 'Akses API dan pertukaran data antaraplikasi.', 'Bidang Aplikasi Informatika', '99%', ['satudata', 'srv-db']],
        ];

        $services = [];
        foreach ($rows as $key => [$name, $desc, $owner, $sla, $assetKeys]) {
            $services[$key] = Service::create([
                'name' => $name,
                'description' => $desc,
                'owner_unit' => $owner,
                'technical_manager' => 'Tim Layanan TIK',
                'access_method' => 'Web',
                'sla_target' => $sla,
                'service_hours' => 'Senin–Jumat 07.30–16.00',
                'status' => 'Aktif',
                'verification_status' => 'tervalidasi',
                'dynamic_data' => [],
            ]);
            $services[$key]->assets()->sync(collect($assetKeys)->map(fn ($k) => $assets[$k]->id)->all());

            ServiceEvaluation::create([
                'service_id' => $services[$key]->id,
                'uptime_actual' => $key === 'website' ? '98,7%' : '99,6%',
                'sla_target' => $sla,
                'achievement_status' => $key === 'website' ? 'Tidak Tercapai' : 'Tercapai',
                'incident_count' => $key === 'website' ? 3 : 1,
                'mttr' => $key === 'website' ? '5 jam' : '1 jam',
                'recommendation' => $key === 'website' ? 'Tambah pemantauan integritas konten dan WAF.' : 'Pertahankan.',
                'dynamic_data' => [],
            ]);
        }

        return $services;
    }

    private function seedRisks(array $assets): void
    {
        $rows = [
            ['website', 'Sisipan konten judi online pada website resmi', 'teknis', 'Peretas', 'Plugin CMS usang belum diperbarui', 'tinggi', 'kritis', 'mitigate'],
            ['srv-db', 'Kegagalan perangkat penyimpanan server basis data', 'teknis', 'Kerusakan perangkat', 'Disk berumur lebih dari 5 tahun', 'sedang', 'kritis', 'mitigate'],
            ['esurat', 'Penyalahgunaan akun TTE karena kata sandi dibagikan', 'sumber_daya_manusia', 'Pegawai internal', 'Kesadaran keamanan pegawai rendah', 'rendah', 'tinggi', 'mitigate'],
            ['genset', 'Pemadaman listrik melebihi kapasitas UPS', 'bencana_alam', 'Gangguan PLN', 'Baterai UPS menurun', 'rendah', 'tinggi', 'transfer'],
            ['satudata', 'Data sektoral tidak mutakhir', 'operasional', 'Produsen data', 'Belum ada jadwal pemutakhiran', 'sedang', 'sedang', 'mitigate'],
        ];

        // Level dihitung dengan rumus yang sama seperti form Risiko
        // (kemungkinan × dampak, naik satu tingkat bila aset Tinggi/Kritis).
        $levels = new RiskLevelService();
        foreach ($rows as [$assetKey, $title, $category, $source, $vuln, $likelihood, $impact, $strategy]) {
            Risk::create([
                'asset_id' => $assets[$assetKey]->id,
                'title' => $title,
                'category' => $category,
                'threat_source' => $source,
                'vulnerability' => $vuln,
                'likelihood' => $likelihood,
                'impact' => $impact,
                'risk_level' => $levels->derive($likelihood, $impact, $assets[$assetKey]->criticality_level?->value),
                'risk_owner' => $assets[$assetKey]->owner,
                'treatment_strategy' => $strategy,
                'status' => 'open',
                'verification_status' => 'tervalidasi',
                'identified_at' => now()->subDays(20),
                'review_due_at' => now()->addMonths(3),
                'dynamic_data' => [],
            ]);
        }
    }

    private function seedOtherModules(array $assets): void
    {
        foreach ([
            ['esurat', 'Pembaruan modul TTE ke versi API terbaru', 'positif', 'Pengembangan', 'Sedang', 'sedang', 'Disetujui', 'disetujui'],
            ['firewall', 'Penggantian aturan firewall untuk segmen pusat data', 'positif', 'Konfigurasi', 'Tinggi', 'tinggi', 'Menunggu CAB', 'diajukan'],
            ['website', 'Migrasi CMS website resmi', 'positif', 'Pengembangan', 'Tinggi', 'tinggi', 'Disetujui', 'diproses'],
        ] as [$assetKey, $title, $type, $category, $priority, $level, $decision, $status]) {
            Change::create([
                'asset_id' => $assets[$assetKey]->id, 'title' => $title, 'change_type' => $type, 'category' => $category,
                'priority' => $priority, 'inherent_risk_level' => $level, 'decision' => $decision, 'status' => $status,
                'verification_status' => 'tervalidasi', 'pic' => 'Tim Aplikasi', 'target_date' => now()->addWeeks(3), 'dynamic_data' => [],
            ]);
        }

        foreach ([
            ['data-pend', 'Penetapan metadata data kependudukan', 'positif', 'Metadata', 'Tinggi', 'sedang'],
            ['satudata', 'Jadwal pemutakhiran data sektoral', 'positif', 'Kualitas Data', 'Sedang', 'sedang'],
        ] as [$assetKey, $title, $type, $category, $priority, $level]) {
            DataInformation::create([
                'asset_id' => $assets[$assetKey]->id, 'title' => $title, 'risk_type' => $type, 'category' => $category,
                'priority' => $priority, 'inherent_risk_level' => $level, 'status' => 'diproses',
                'verification_status' => 'tervalidasi', 'pic' => 'Walidata', 'target_date' => now()->addMonth(), 'dynamic_data' => [],
            ]);
        }

        foreach ([
            ['Administrator server tunggal', 'Ketergantungan pada satu orang administrator server', 'Keberlanjutan', 'tinggi'],
            ['Pengelola website', 'Belum bersertifikat keamanan aplikasi web', 'Kompetensi', 'sedang'],
        ] as [$subject, $title, $category, $level]) {
            HrRisk::create([
                'subject' => $subject, 'title' => $title, 'category' => $category, 'inherent_risk_level' => $level,
                'status' => 'diproses', 'verification_status' => 'tervalidasi', 'pic' => 'Bagian Kepegawaian',
                'target_date' => now()->addMonths(2), 'dynamic_data' => [],
            ]);
        }

        foreach ([
            ['firewall', 'Penerapan WAF untuk seluruh situs web pemerintah', 'Konfigurasi WAF dan uji penetrasi'],
            ['srv-app', 'Pengerasan (hardening) server aplikasi', 'Audit konfigurasi dan pembaruan OS'],
        ] as [$assetKey, $program, $activity]) {
            SecurityProgram::create([
                'asset_id' => $assets[$assetKey]->id, 'program_kerja' => $program, 'kegiatan' => $activity,
                'pic' => 'Bidang Persandian', 'verification_status' => 'tervalidasi', 'dynamic_data' => [],
            ]);
        }

        $expert = KnowledgeExpert::create([
            'nama_pegawai' => 'Pegawai Contoh A', 'jabatan_unit' => 'Pranata Komputer Ahli Muda',
            'keahlian_spesifik' => 'Administrasi server Linux, virtualisasi', 'sertifikasi_lisensi' => 'Contoh Sertifikasi Linux',
            'peran_km' => 'Narasumber', 'verification_status' => 'tervalidasi', 'dynamic_data' => [],
        ]);
        KnowledgeExpert::create([
            'nama_pegawai' => 'Pegawai Contoh B', 'jabatan_unit' => 'Sandiman Ahli Pertama',
            'keahlian_spesifik' => 'Penanganan insiden siber, forensik log', 'peran_km' => 'Kontributor',
            'verification_status' => 'tervalidasi', 'dynamic_data' => [],
        ]);
        KnowledgeActivity::create([
            'tanggal_pelaksanaan' => now()->subWeeks(2), 'nama_kegiatan' => 'Berbagi pengetahuan: hardening server',
            'materi_topik' => 'Langkah pengerasan VM baru', 'narasumber_name' => $expert->nama_pegawai, 'narasumber_id' => $expert->id,
            'jumlah_peserta' => 18, 'verification_status' => 'tervalidasi', 'dynamic_data' => [],
        ]);
    }

    private function seedTickets(array $services): void
    {
        $rows = [
            ['website', 'Warga', 'Website resmi menampilkan tulisan judi slot gacor di halaman berita, mohon segera ditangani', 'baru'],
            ['esurat', 'Sekretariat Dinas Pendidikan', 'Aplikasi e-Surat tidak bisa diakses sejak pagi, semua pegawai tidak bisa login. Muncul error 502', 'baru'],
            ['hosting', 'Dinas Kesehatan', 'Mohon dibuatkan server baru (VM) untuk aplikasi pendaftaran imunisasi, kebutuhan RAM 8 GB', 'baru'],
            ['integrasi', 'Bappeda', 'Kami membutuhkan akses API data penduduk per kecamatan untuk integrasi dengan aplikasi perencanaan', 'diproses'],
            ['esurat', 'Staf Kecamatan Utara', 'Lupa kata sandi akun e-Surat, akun terkunci setelah 3 kali gagal', 'baru'],
            ['satudata', 'Bidang Statistik', 'Tombol unduh CSV di Portal Satu Data tidak berfungsi, file yang terunduh kosong', 'diproses'],
            ['satudata', 'Dinas Sosial', 'Usulan tambah fitur grafik tren per tahun di halaman dataset', 'baru'],
            ['hosting', 'Dinas Perhubungan', 'Perpanjangan sertifikat SSL subdomain dishub, kedaluwarsa minggu depan', 'selesai'],
        ];

        // Triase demo memakai mesin aturan saja: cepat dan hasilnya tetap.
        $triage = new TicketTriage(new class extends LocalLlm
        {
            public function chatAvailable(): bool
            {
                return false;
            }
        });

        foreach ($rows as $i => [$serviceKey, $requester, $issue, $status]) {
            $service = $services[$serviceKey]->load('assets');
            $ticket = ServiceTicket::create([
                'service_id' => $service->id,
                'reported_at' => now()->subDays(8 - $i),
                'requester_name' => $requester,
                'issue' => $issue,
                'status' => $status,
                'dynamic_data' => [],
            ]);
            $result = $triage->triage($issue, $service, $requester, $ticket->label());
            $ticket->update([
                'category' => $result['category'],
                'impact_level' => $result['impact_level'],
                'urgency' => $result['urgency'],
                'priority' => $result['priority'],
                'draft_reply' => $result['draft_reply'],
                'triage' => $result['triage'],
                'resolution' => $status === 'selesai' ? 'Sertifikat SSL diperpanjang dan dipasang ulang.' : null,
            ]);
        }
    }

    private function seedKnowledge(array $assets): void
    {
        $linked = ['sop-permohonan-server' => 'srv-app', 'sop-integrasi-api' => 'satudata', 'prosedur-insiden-siber' => 'firewall', 'sop-backup' => 'srv-db'];

        foreach (DemoDocuments::knowledge() as $slug => $doc) {
            $path = "knowledge-documents/contoh-{$slug}.pdf";
            Storage::disk('public')->put($path, SimplePdf::make($doc['pages']));

            $asset = KnowledgeAsset::create([
                'asset_id' => isset($linked[$slug]) ? $assets[$linked[$slug]]->id : null,
                'title' => $doc['title'],
                'knowledge_type' => 'explicit',
                'category' => $doc['category'],
                'owner_unit' => $doc['owner'],
                'access_level' => $doc['access'],
                'document_path' => $path,
                'document_original_name' => "{$slug}.pdf",
                'last_reviewed_at' => now()->subMonth(),
                'verification_status' => 'tervalidasi',
                'dynamic_data' => [],
            ]);

            if ($slug === 'prosedur-insiden-siber') {
                KnowledgeRisk::create([
                    'knowledge_asset_id' => $asset->id,
                    'pernyataan_risiko' => 'Prosedur insiden hanya dipahami satu orang',
                    'akar_penyebab' => 'Belum ada sesi berbagi pengetahuan rutin',
                    'area_dampak' => 'Operasional', 'dampak' => 4, 'kemungkinan' => 3, 'skor_risiko_bawaan' => 12,
                    'tingkat_risiko_bawaan' => 'sedang', 'rencana_mitigasi' => 'Simulasi insiden tiap semester',
                    'penanggung_jawab' => 'Bidang Persandian', 'verification_status' => 'tervalidasi', 'dynamic_data' => [],
                ]);
            }
        }
    }

    private function seedEvidence(): void
    {
        $assessor = new EvidenceAssessor(llm: new class extends LocalLlm
        {
            public function chatAvailable(): bool
            {
                return false;
            }
        });
        $uploader = User::where('username', 'user')->value('id');

        foreach (DemoDocuments::evidence() as $slug => $doc) {
            $path = "bukti-dukung/contoh-{$slug}.pdf";
            Storage::disk('local')->put($path, SimplePdf::make($doc['pages']));

            $record = EvidenceDocument::create([
                'module_key' => $doc['module'],
                'target_level' => $doc['level'],
                'unit_name' => $doc['unit'],
                'title' => $doc['title'],
                'file_path' => $path,
                'original_name' => "{$slug}.pdf",
                'uploaded_by' => $uploader,
            ]);
            $assessor->assess($record);
        }
    }
}
