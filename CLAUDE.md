# BSSN ISMS — Project Context for Claude

## Read this first if you're a new Claude Code session

The user just moved from a Windows laptop to a MacBook (Apple Silicon,
M1) and cloned this repo there. **You are very likely starting this
conversation with zero memory of anything below** — a previous Claude
Code session (on the old Windows machine) built this entire project
collaboratively with the user over one very long session, and that
conversation history does not transfer across machines. This file is
the bridge: it's what that previous session would tell you if it could
talk to you directly.

Read this whole file before touching anything. Then set up the local
dev environment (see "First-time setup on a new machine" below) — the
user's stated goal for the move is to **keep developing the app
locally with Claude Code's help, same as before**, not just view it.
Do the installs yourself via the terminal; don't ask the user to run
commands unless something genuinely needs their manual action (like
creating accounts on third-party services).

The user is not a professional developer — they've been directing
this project's *what* and *why* in plain language, not writing code
themselves. Explain what you're doing in simple terms as you go, the
same way you would to someone who isn't going to read code, but keep
actually doing the technical work yourself.

## What this project is

**BSSN ISMS** — an Information Security Management System built for
Badan Siber dan Sandi Negara (BSSN), covering 8 integrated management
areas: SDM (HR), Pengetahuan (Knowledge), Aset (Assets), Keamanan
Informasi (Information Security), Risiko (Risk), Perubahan (Change),
Layanan (Service), and Data & Informasi. It replaces what used to be
8 separate Excel spreadsheets with one system where the modules
actually reference each other.

**Stack:** Laravel 11 (PHP 8.3), Livewire 3, PostgreSQL, Tailwind
CSS v4, Vite. No frontend framework beyond Livewire/Alpine — server-
rendered Blade + Livewire components throughout.

**Two directories exist historically** — on the old Windows machine
there was a `kominfo-isms` "prototype" dir and a `kominfo-isms-app`
"runnable" dir, kept in sync by copying files between them (an
artifact of how the project started, not a deliberate architecture).
**On this fresh clone, there's only one directory — this one.** Don't
recreate that two-directory split; it added confusion, not value.

## The integration model (why this project exists)

Two independent linking mechanisms hold the 8 modules together —
important to understand before touching any of the cross-module code:

1. **Kode Aset (Asset Code)** — Aset is the real hub. Risiko,
   Perubahan, Layanan, Keamanan Informasi, Data & Informasi, and
   Pengetahuan (its "Aset Pengetahuan" part) all carry a nullable
   `asset_id` FK back to `assets`. Layanan↔Aset is many-to-many (one
   service can span multiple assets) via a `service_assets` pivot;
   everything else is a plain nullable FK.
2. **Kode Personil (Personnel Code)** — a separate, cross-cutting
   `personnel_ref` string column that exists on ~10 tables (assets,
   risks, changes, data_informations, services, security_programs,
   hr_risks, knowledge_activities, knowledge_risks, knowledge_experts,
   knowledge_assets). It links *people*, not assets, and is generated
   automatically (`PSN-XXXXXX`) when left blank on save — see
   `app/Livewire/Concerns/GeneratesPersonnelRef.php`. **Linking is
   always exact-string-match, never fuzzy/guessed-by-name** — this was
   an explicit, repeated instruction from the user this whole build:
   never auto-match records by similar-looking names.
3. **SDM is deliberately standalone** — `hr_risks` has no `asset_id`
   at all. It only connects to other modules if someone manually sets
   the same `personnel_ref` on both records.
4. **Risk escalation automation** — when a Risk's `risk_level` becomes
   `tinggi` or `kritis`, `App\Services\RiskEscalationResponseService`
   (fired from `Risk::booted()`'s `saved` hook) auto-creates follow-up
   records in SecurityProgram, Change, DataInformation (and
   ServiceTicket if the linked asset has services), each tagged
   `dynamic_data->auto_generated_from_risk = risk_code` for dedup.
5. **Verification workflow** — only two roles exist, `admin` and
   `user` (see `app/Models/User.php`). Both can write; the only
   difference is `verification_status`. Non-admin saves start as
   `menunggu_verifikasi`. **Admins are scoped to exactly one of the 8
   modules each via `admin_module`** (added after this file's original
   writing — see migration `2026_08_03_000001_add_admin_module_to_users_table`
   and `App\Support\AdminModules`) — "gaada super admin yang menyetujui
   semua", so an admin save is only immediately `tervalidasi` when
   `User::canAutoVerify($moduleKey)` is true, i.e. `admin_module`
   matches the module being saved (`app/Models/User.php`). A save by an
   admin scoped to a *different* module still starts as
   `menunggu_verifikasi`, same as a plain user. **There is no standalone
   `/verifikasi` route** (despite what an earlier version of this file
   said) — the verification queue (`AdminVerificationQueue`) is a
   Livewire component embedded directly inside each of the 8 module
   index pages (see e.g. `resources/views/assets/index.blade.php`),
   scoped via `AdminModules::modelsFor($admin->admin_module)` so it only
   ever shows that admin's own module, never all 8 at once. Enforced server-side via
   `App\Livewire\Concerns\GuardsWriteAccess` (`ensureCanWrite()`), not
   just hidden UI — don't ever gate a save purely in the Blade view.

There's a `/panduan` (guide) page in the app itself with a fuller
plain-language walkthrough of this model — read it live if you want
the user-facing version of the above.

## Known gotchas (learned the hard way — don't re-discover these)

- **`wasChanged()` is never true inside `performInsert()`** — only
  populated on updates. Any `Model::booted()` hook that needs to react
  to both creates *and* updates must check
  `$model->wasRecentlyCreated || $model->wasChanged('field')`, not
  `wasChanged()` alone. This bit the risk-escalation trigger badly —
  it silently never fired on fresh Excel imports until this was fixed.
- **Never trust an echoed local PHP variable as proof a DB write
  succeeded** — always re-query with `->fresh()` or a separate query
  before believing a save worked.
- **Mermaid diagrams break on a literal `&` inside a quoted node
  label** (e.g. `"Data & Informasi"`) even when written as `&amp;` in
  the source — the browser HTML-decodes `&amp;` back to `&` before
  mermaid.js ever parses the text, so the escape doesn't help. Write
  `"Data dan Informasi"` instead, or restructure to avoid the
  ampersand entirely, in any Artifact you publish with a diagram.
- **Postgres self-managed on Windows was extremely fragile** — it
  crashed on nearly every turn of the old session, requiring manual
  `rm postmaster.pid` + relaunch every time, and the actual data
  directory (`~/pgdata`) was *not* the default install location,
  which caused real confusion more than once. **This should mostly be
  a non-issue on macOS** if Postgres is installed via Homebrew as a
  proper background service (`brew services start postgresql@16` or
  similar) instead of launched manually — prefer that approach here
  rather than replicating the manual-launch pattern from Windows.
- **A stray `npm run dev` process left running in the background will
  regenerate `public/hot`**, which breaks Vite asset loading (pages
  render completely unstyled) because Laravel's `@vite()` directive
  then tries to load from a dev server that isn't actually reachable.
  If styling ever looks broken, check for a stray node/vite process
  before anything else.
- **Laravel behind Railway's HTTPS-terminating proxy generated
  `http://` asset URLs on an `https://` page**, which browsers
  silently block as mixed content (page renders totally unstyled,
  no console error). Fixed via `$middleware->trustProxies(at: '*')`
  in `bootstrap/app.php` — already in place, don't remove it. This
  class of bug (proxy doesn't forward scheme info) is common to most
  PaaS hosts, not Railway-specific, so keep it if this ever moves
  hosts again.

## Deployment (already live, don't need to redo this)

- **GitHub:** `github.com/BeniceDidan/mis.-bssn-isms`, branch `main`.
- **Hosting:** Railway project `wonderful-upliftment`, two services —
  `mis.-bssn-isms` (the app, deployed via the `Dockerfile` in this
  repo) and `Postgres` (managed database, separate from whatever
  local Postgres you set up here).
- **Live URL:** `https://mis-bssn-isms-production.up.railway.app` —
  login `admin`/`admin123` (admin_module: `aset`) or `user`/`user123`
  for the basic pair. There are also 7 more per-module admin logins
  seeded (`admin.sdm`, `admin.pengetahuan`, `admin.keamanan`,
  `admin.risiko`, `admin.perubahan`, `admin.layanan`, `admin.data` —
  same `admin123` password), one per `admin_module` scope, so testing
  the verification queue for a module other than Aset needs the
  matching per-module login, not the plain `admin` one.
- **To ship a change:** commit, then `git push`. Railway auto-builds
  and redeploys from the Dockerfile on every push to `main` — no
  manual deploy step. **Always ask the user before pushing** — it's a
  visible, shared-state action per standing operating rules, not
  something to do silently even mid-task.
- The Railway Postgres database already has the same demo dataset as
  local (26 assets etc.) — copied over via `pg_dump`/`psql` once.
  Local edits to data don't sync to Railway automatically; only code
  changes do (via git push). If the user wants local data changes
  reflected on the live site too, that's a manual data copy, same as
  code needs a manual push.

## First-time setup on a new machine (do this now if the repo is freshly cloned and nothing runs yet)

Written for macOS with Homebrew, since that's the target machine.
Adjust if it turns out to be something else.

1. **Homebrew** — check with `brew --version`; install from
   brew.sh if missing.
2. **PHP 8.3 + extensions:** `brew install php@8.3` — this app needs
   `pdo_pgsql`, `gd`, `mbstring`, `zip`, `bcmath` (the `gd` extension
   specifically trips people up — `maatwebsite/excel` needs it for
   Excel import/export; Homebrew's default PHP build usually includes
   it, but verify with `php -m | grep gd`).
3. **Composer:** `brew install composer`.
4. **Node.js:** `brew install node` (need a reasonably current LTS —
   check `package.json` engines field if one exists, otherwise latest
   LTS is fine).
5. **PostgreSQL:** `brew install postgresql@16` (or whatever's
   current), then `brew services start postgresql@16` — let Homebrew
   manage it as a background service rather than launching manually;
   this avoids the exact fragility that plagued the Windows setup.
6. **Clone (if not already done):**
   `git clone https://github.com/BeniceDidan/mis.-bssn-isms.git`
7. **Install deps:** `composer install` and `npm install` in the
   project root.
8. **Database:** create a local Postgres database and user, e.g.
   `createdb kominfo_isms`.
9. **Environment:** `cp .env.example .env`, then fill in `DB_*` to
   point at the local database from step 8, then
   `php artisan key:generate`.
10. **Schema:** `php artisan migrate`.
11. **Demo data (optional but recommended):** ask the user whether
    they want a copy of the real demo dataset from Railway's
    production database, or want to start empty and re-import from
    the original Excel files (there are `php artisan import:*`
    commands in `app/Console/Commands/` for that — check
    `app/Imports/` for what each one expects). Pulling from Railway is
    the faster path: `pg_dump` from the Railway public connection
    (get the connection string via `railway variable list --service
    Postgres --kv` if the Railway CLI is set up) into the fresh local
    database.
12. **Build assets:** `npm run build` (or `npm run dev` for local
    development with hot reload — just remember to stop it cleanly
    when done, per the stray-process gotcha above).
13. **Run it:** `php artisan serve`, confirm `http://localhost:8000`
    loads and login works.
14. **Verify nothing is different from before:** log in as both
    `admin` and `user`, open a couple of modules, confirm dark mode
    toggle works, confirm the global search returns results. The goal
    is this feels identical to how the app worked on the old Windows
    machine — if anything looks or behaves differently, that's a bug
    to fix, not an acceptable platform difference.

## Working style notes (from the user, this whole build)

- The user directs *what* and *why* in plain conversational Indonesian
  and expects the assistant to figure out *how* — they are not going
  to read code or debug it themselves.
- When something breaks in a way that's happened before (Postgres
  down, stray process, etc.), just fix it the established way without
  re-explaining the whole diagnosis every time — but do mention
  briefly what was wrong and that it's fixed.
- Prefers being asked before risky/visible actions (git push, hosting
  account creation, anything touching a shared/external system) but
  wants everything *else* handled proactively without checking in at
  every step.
- Appreciates concrete, verified proof of a fix (a live screenshot, a
  measured pixel offset, an actual query result) over a claim that
  something is fixed.


## Setup aktual di PC Windows (benic) — 2026-09-08

Bagian "First-time setup on a new machine" di atas ditulis untuk macOS +
Homebrew. Pindahnya ternyata tidak ke MacBook: proyek ini sekarang berjalan di
PC Windows (pengguna `benic`, ASRock B760M / i5-13400F). Yang di bawah ini
adalah keadaan sebenarnya di mesin itu — ikuti ini, bukan langkah Homebrew.

**Tidak ada hak admin di mesin ini.** Semua dipasang portabel di folder
pengguna, pola yang sama dengan Node dan Postgres yang sudah ada di sana.

| Perkakas | Jalur |
|---|---|
| PHP 8.4.25 (NTS x64) | `C:\Users\benic\AppData\Local\Programs\php-8.4` |
| Composer 2.10.3 | `C:\Users\benic\AppData\Local\Programs\composer\composer.phar` |
| Node 22.14.0 | `C:\Users\benic\AppData\Local\Programs\nodejs-22.14.0` |
| PostgreSQL 16 | `C:\Users\benic\AppData\Local\Programs\pgsql-16`, data di `AppData\Local\pgsql-16-data` |
| Proyek | `C:\Users\benic\mis-bssn-isms` |

Ekstensi PHP yang diaktifkan di `php.ini`: `pdo_pgsql`, `pgsql`, `mbstring`,
`openssl`, `fileinfo`, `zip`, `curl`, `gd`, `intl`. `extension_dir` disetel ke
jalur mutlak — tanpa itu PHP mencari `ext/` di folder kerja saat ini.

**JANGAN percaya `DB_CONNECTION=sqlite` di `.env.example`.** Itu bawaan Laravel
yang tidak pernah diedit, dan aplikasinya TIDAK jalan di SQLite: migrasi
`2026_07_14_000002_create_assets_table` membuat
`CREATE INDEX ... USING GIN (dynamic_data)`, dan `USING GIN` khas PostgreSQL —
SQLite gagal dengan `near "USING": syntax error`. Bagian "Stack" di atas benar;
berkas contohnya yang menyesatkan.

Database lokalnya `bssn_isms`, satu cluster dengan `bakoel`, `bioverse`, dan
`englishify` di port 5432. Cluster itu **dipakai bersama proyek lain** — jangan
matikan atau reset servernya demi proyek ini saja.

Postgres di sini bukan layanan Windows; dinyalakan lewat pintasan di folder
Startup yang memanggil `C:\Users\benic\bioverse bajol\start-bioverse-pg.cmd`.
Skrip itu sudah menangani `postmaster.pid` basi — masalah yang disebut di
catatan gotcha di atas, dan di mesin ini terulang pada 19 Agustus dan
8 September.

Menjalankan aplikasinya: klik dua kali `start-dev-benic.cmd` di akar proyek.
`start-dev.bat` yang asli dibiarkan apa adanya sebagai catatan sejarah —
jalurnya menunjuk ke `C:\Users\user\...` dan PostgreSQL port 5433, keduanya
tidak ada di mesin ini.

**Akun login dibuat ulang secara lokal.** `DatabaseSeeder` di repo ini hanya
berisi factory bawaan Laravel; sembilan akun yang disebut di bagian Deployment
tidak ada di dalamnya, hanya ada di database Railway. Yang lokal dibuat manual
dengan kata sandi yang sama seperti yang didokumentasikan.

**Railway sudah mati** (per 10 November 2026 URL-nya membalas "Application
not found"), jadi dataset 26 aset di sana tidak bisa diambil lagi. Sebagai
gantinya ada data demo fiktif:

```
php artisan migrate --force
php artisan db:seed --class=DemoDataSeeder --force
php artisan pengetahuan:indeks
php artisan storage:link
```

`.env` di PC ini berisi `APP_ENV=production`, jadi perintah artisan yang
mengubah database butuh `--force`. `APP_URL` menunjuk ke alamat Tailscale
(https) sehingga URL yang dibuat Laravel ikut https; untuk mencoba di
localhost, jalankan server dengan `APP_URL=http://localhost:8001` di
lingkungan prosesnya (lihat `D:\kominfo\.claude\launch.json`, config
`bssn-isms`).

Tes otomatis memakai database `kominfo_isms_test` di cluster yang sama;
host/user/kata sandi diambil dari `.env` (tidak lagi ditulis di
`phpunit.xml`). Jalankan `php artisan test`.

## Fitur AI (cabang `fitur-ai`, dibangun 10 November 2026)

Permintaan mentor Kominfo: (1) AI Assessor bukti dukung, (2) Manajemen
Pengetahuan jadi pencarian semantik + RAG + tag otomatis, (3) triase tiket
Helpdesk. Ketiganya:

- jalan **lokal lewat Ollama** (`config/ai.php`: `llama3.2` untuk teks,
  `bge-m3` untuk embedding) — tidak ada data yang dikirim ke layanan awan;
- punya **jalur cadangan tanpa AI** (mesin aturan / pencarian kata kunci
  Postgres berbahasa Indonesia), sehingga aplikasi tidak pernah macet karena
  model mati; tes otomatis selalu memakai jalur ini (`AI_PROVIDER=none`);
- menandai keluaran sebagai draf dan mencatat setiap keluaran di tabel
  `ai_logs` (mesin apa yang dipakai, masukan, keluaran, durasi).

| Fitur | Di mana | Kode inti | Data yang bisa diedit |
|---|---|---|---|
| Evaluasi Bukti Dukung | menu samping "Evaluasi Bukti Dukung" (`/bukti-dukung`) | `App\Services\Ai\EvidenceAssessor`, `PdfReader` | `config/bukti_dukung.php` (rubrik, **DRAF**) |
| Tanya Dokumen + tag | Manajemen Pengetahuan → tab "Tanya Dokumen" | `KnowledgeIndexer`, `KnowledgeSearch`, job `IndexKnowledgeDocument`, perintah `pengetahuan:indeks` | kosakata tag di `KnowledgeIndexer::VOCABULARY` |
| Triase Helpdesk | Manajemen Layanan → tab "Tiket Helpdesk"; tombol "Analisis AI" di form tiket | `TicketTriage` | `config/helpdesk.php` (kategori, matriks prioritas, target waktu — DRAF) |

Aturan yang sengaja dipegang:

- **Skor/level bukti dukung dan prioritas tiket hanya dari mesin aturan.**
  LLM hanya memberi catatan, memilih kategori bila kata kunci lemah (dampak &
  urgensi lalu dihitung ulang dari aturan kategori itu), dan menulis kalimat
  pembuka draf balasan. LLM tidak lagi boleh menaikkan dampak/urgensi: pada uji
  13 November 2026 permintaan API dan permintaan data biasa naik ke P2 tanpa
  alasan yang masuk akal. Alasannya: hasil harus bisa dijelaskan dan diaudit.
- Kata kunci triase (`TicketTriage::contains`) boleh diselingi sampai dua kata
  ("akun saya terkunci" cocok dengan "akun terkunci").
- Tanya Dokumen hanya memakai potongan yang skornya paling banyak 0,08 di bawah
  potongan terbaik (`KnowledgeSearch::RELEVANCE_MARGIN`), bukan lagi maksimal
  2 potongan per dokumen; nomor halaman tidak dikirim ke model, dan kebiasaan
  format model kecil dirapikan di `KnowledgeSearch::tidy()`.
- Bukti dukung disimpan di disk privat (`storage/app/private/bukti-dukung`),
  dibuka lewat route ber-login `evidence.file`. Keputusan asesor hanya oleh
  admin modul yang bersangkutan (`canAutoVerify`).
- Cap basah tidak terbaca dari teks PDF; yang dideteksi: tanda tangan digital
  tertanam (`/Sig` + `/ByteRange`), blok NIP, keterangan TTE, dan gambar di
  halaman terakhir.
- Riset dasar (PermenPANRB 8/2026 menggantikan 59/2020, pemetaan modul ke
  indikator, matriks prioritas) ada di `docs/riset/` dan **belum ditinjau
  pengguna** — jangan anggap rubrik itu final.
- `docs/riset/04-rujukan-per-modul.md` memetakan setiap aturan aplikasi ke
  peraturan (Perpres 95/2018 Pasal 46–54, PermenPANRB 5/2020, BSSN 4/2021,
  Permen PPN 16/2020, **Permenkomdigi 12/2026** pedoman aset TIK & layanan,
  BRIN 2/2024) dan mencatat yang perlu disesuaikan — terutama prioritas
  helpdesk (pedoman resmi: Pk/Pe/Pn dari klasifikasi layanan × Mayor/Minor,
  bukan P1–P4) dan register risiko (16 kategori, area dampak, selera risiko).
  Belum ada kode yang diubah berdasarkan temuan itu; tunggu keputusan pengguna.

**Dua Ollama di PC ini.** Port 11434 dilayani Ollama di **WSL** (`llama3.2`
dan `bge-m3`, yang terakhir terpasang 10 Nov). Ollama Windows
(`AppData\Local\Programs\Ollama`, model di `~\.ollama\models`: bge-m3,
qwen2.5, qwen3.5, gemma2, sahabatai-9b, nomic-embed-text) tidak bisa
memakai port yang sama. Bila ingin memakai model Windows (mis. Sahabat-AI
yang lebih fasih berbahasa Indonesia), jalankan servernya di port lain dan
setel `AI_OLLAMA_URL` + `AI_CHAT_MODEL` di `.env`.

## Gaya tampilan (diubah 10 November 2026 atas permintaan pengguna)

Pengguna meminta gaya **situs pemerintahan profesional berwarna biru tua**:
satu warna dominan, tidak transparan, tanpa hiasan "AI slop". Tata letak
tidak diubah. Aturannya:

- **Satu warna aksen** `brand-*` (biru tua, didefinisikan di `@theme` pada
  `resources/css/app.css`). Warna per modul (amber, teal, emerald, ungu,
  rose, dst.) sudah dihapus — jangan dikembalikan. Merah hanya untuk galat,
  tombol hapus, dan level Kritis.
- **Tanpa transparansi/kaca:** `.glass-panel` sekarang putih solid bergaris
  tipis (nama kelas dipertahankan). Jangan pakai `bg-white/NN`,
  `backdrop-blur`, gradien (`bg-gradient-*`), atau bayangan berwarna.
  Sidebar dan toast biru tua solid (`.glass-panel-dark`).
- **Notifikasi tidak berwarna-warni:** kotak info/peringatan memakai biru
  netral; hanya bahaya yang merah.
- **Tingkat Rendah→Kritis** memakai tangga biru (abu-biru, biru sedang,
  biru tua) dan merah untuk Kritis — lihat `Level::accentHex()` dan
  `config/helpdesk.php`.
- Gumpalan warna latar per modul dan ilustrasi `module-ornament` dihapus.
- Konversi massal dilakukan dengan skrip regex (bukan disimpan di repo);
  view baru cukup memakai kelas `brand-*`, `slate-*`, `gray-*`, `red-*`.

## Konteks lintas proyek

Ada di vault Obsidian di `C:\Users\benic\Documents\Obsidian Vault`. Mulai dari `MOC - Memori Bos Didan.md`; catatan repo ini di `02-Projects/Lainnya/BSSN ISMS.md`.

Yang relevan:

- `02-Projects/Sekolah-Rakyat-Nganjuk/Anggaran Internal Eduriens.md` berisi satu temuan pengerasan server yang berlaku di sini juga: **Docker menembus ufw**. Menjalankan `docker run -p 5432:5432` setelah memasang firewall membuat Docker menulis langsung ke iptables dan melewati aturan ufw. `ufw status` tetap bilang port itu tertutup padahal terbuka ke seluruh internet. Untuk aplikasi ISMS, itu kelas risiko yang jelas tidak boleh terjadi.

Vault adalah potret 22 September 2026. Kuat pada *kenapa*, menua pada *apa*. Verifikasi hal yang menentukan ke kode.
