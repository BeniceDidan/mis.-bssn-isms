import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';

/**
 * Guided tour, two ways to run it:
 *  - startTour(): one continuous walk across all 8 modules (SDM -> Data &
 *    Informasi), same order as the app's own flow.
 *  - startModuleTour(id): just one module's steps, launched from that
 *    module's own "Tutorial Modul Ini" button — same step data, just sliced
 *    down to one module and started directly on its page.
 *
 * Every module's steps follow the same rhythm: klik tombol tambah (real
 * click, opens the real form) -> penjelasan kolom-kolom -> penjelasan Kode
 * Personil -> klik X untuk menutup (real click, closes the real form) ->
 * [SDM & Aset only] klik "Lihat Detail" pada baris tabel -> panel
 * keterkaitan lintas modul. "Real click" steps (advanceOnRealClick) attach a
 * one-shot listener straight to the highlighted element: clicking it
 * performs its actual action (open/close the real Livewire modal) AND
 * advances the tour — no separate "Lanjut" click needed, though "Lanjut"
 * stays available as a fallback so the tour can never get stuck.
 *
 * State survives full page loads (this app has no SPA routing) via
 * localStorage: bssnTourMode ('full' or a module id) + bssnTourStep (index
 * into that mode's step list). resumeTourIfActive() re-renders the saved
 * step on DOMContentLoaded if the current URL matches it.
 */
const MODE_KEY = 'bssnTourMode';
const STEP_KEY = 'bssnTourStep';

const PERSONNEL_DESC =
    'Ini <b>Kode Personil</b> — jantung dari integrasi antar modul. Isi dengan kode yang <b>sama persis</b> dengan yang dipakai di modul lain (mis. Manajemen SDM) untuk menautkan record ini ke orang/pihak yang sama secara pasti — bukan tebakan berdasarkan nama yang mirip. Kosongkan untuk digenerate otomatis.';

const DEFAULT_CLOSE_DESC =
    'Klik tombol <b>X</b> ini untuk menutup form dan lanjut ke langkah berikutnya — data uji coba tidak akan tersimpan. Kalau memang ingin menyimpan data sungguhan, klik <b>Simpan</b> di pojok kanan bawah, datanya langsung muncul di tabel di bawah.';

function moduleSteps({ path, title, openDesc, fieldsDesc, closeDesc = DEFAULT_CLOSE_DESC, integration = null }) {
    const steps = [
        {
            path,
            el: '[data-tour="add-button"]',
            title: `${title} — Buka Form`,
            desc: openDesc,
            advanceOnRealClick: true,
            afterClickDelay: 650,
        },
        {
            path,
            el: '[data-tour="form-body"]',
            title: `${title} — Kolom-kolom Form`,
            desc: fieldsDesc,
        },
        {
            path,
            el: '[data-tour="personnel-field"]',
            title: 'Kode Personil',
            desc: PERSONNEL_DESC,
        },
        {
            path,
            el: '[data-tour="modal-close"]',
            title: 'Tutup Form',
            desc: closeDesc,
            advanceOnRealClick: true,
            afterClickDelay: 500,
        },
    ];

    if (integration) {
        steps.push(
            {
                path,
                el: integration.triggerEl,
                title: integration.triggerTitle,
                desc: integration.triggerDesc,
                advanceOnRealClick: true,
                afterClickDelay: 700,
            },
            {
                path,
                el: integration.panelEl,
                title: integration.panelTitle,
                desc: integration.panelDesc,
                side: 'top',
            },
        );
    }

    return steps;
}

const INTRO_STEPS = [
    {
        path: '/dashboard',
        el: '[data-tour="tiles-grid"]',
        title: 'Selamat Datang di BSSN ISMS',
        desc: 'Ini 8 modul manajemen yang saling terhubung. Tur ini akan mengajak Anda berkeliling dari SDM sampai Data &amp; Informasi, urut sesuai alurnya. Di tiap modul, Anda akan benar-benar membuka form, melihat kolom-kolomnya, lalu menutupnya lagi — bukan cuma dibacakan.',
    },
    {
        path: '/dashboard',
        el: '[data-tour="tile-sdm"]',
        title: 'Mulai dari SDM',
        desc: 'SDM adalah langkah pertama — mencatat risiko yang berhubungan dengan orang, pegawai maupun pihak ketiga. Klik "Lanjut" untuk masuk ke modulnya.',
    },
];

const MODULES = [
    {
        id: 'sdm',
        label: 'Manajemen SDM',
        steps: moduleSteps({
            path: '/hr-risks',
            title: 'Manajemen SDM',
            openDesc: 'Klik tombol ini untuk membuka form Risiko SDM baru — langkah pertama dari alur 8 modul.',
            fieldsDesc:
                'Kolom-kolom di form ini: <br>• <b>Subjek</b> — nama pegawai atau pihak ketiga. <br>• <b>Kategori</b> &amp; <b>Ancaman</b> — jenis dan uraian risikonya. <br>• <b>Level Risiko Inheren</b> &amp; <b>Target Waktu</b>. <br>• <b>Status</b> &amp; <b>Penanggung Jawab</b>. <br>• <b>Data Dinamis</b> — kolom bebas tambahan kalau ada atribut khusus yang tidak tertampung kolom baku.',
            integration: {
                triggerEl: 'button[title="Lihat Detail"]',
                triggerTitle: 'Lihat Keterkaitan',
                triggerDesc: 'Sekarang klik tombol "Lihat Detail" pada salah satu baris di tabel bawah untuk melihat keterkaitannya dengan modul lain.',
                panelEl: '[data-tour="cross-module-panel"]',
                panelTitle: 'Keterkaitan Lintas Modul',
                panelDesc: 'Ini bagian paling penting: begitu Kode Personil yang sama dipakai di modul lain, semuanya otomatis terkumpul di sini — tidak perlu dicari manual satu-satu.',
            },
        }),
    },
    {
        id: 'pengetahuan',
        label: 'Manajemen Pengetahuan',
        steps: moduleSteps({
            path: '/knowledge',
            title: 'Manajemen Pengetahuan',
            openDesc: 'Klik untuk membuka form Aset Pengetahuan baru (pastikan tab "Aset Pengetahuan" di atas yang aktif).',
            fieldsDesc:
                'Kolom-kolom di form ini: <br>• <b>Nama Aset Pengetahuan</b> &amp; <b>Aset Terkait</b> (opsional, menaut ke Manajemen Aset). <br>• <b>Jenis</b> &amp; <b>Kategori Pengetahuan</b>. <br>• <b>Unit Pemilik</b> &amp; <b>Tingkat Aksesibilitas</b>. <br>• <b>Tanggal Pembaruan Terakhir</b>. <br>• <b>Tautan Referensi</b> dan/atau <b>Lampiran PDF</b> — boleh isi salah satu atau keduanya.',
        }),
    },
    {
        id: 'aset',
        label: 'Manajemen Aset',
        steps: moduleSteps({
            path: '/assets',
            title: 'Manajemen Aset',
            openDesc: 'Klik untuk membuka form Aset baru — modul paling banyak dirujuk oleh modul lain (Risiko, Perubahan, Keamanan, dst).',
            fieldsDesc:
                'Kolom-kolom di form ini: <br>• <b>Kategori</b> &amp; <b>Kritikalitas</b> — Kritikalitas bisa dihitung otomatis dari Penilaian CIA. <br>• <b>Penilaian CIA</b> (Kerahasiaan/Integritas/Ketersediaan, skala 1-5) — isi ketiganya untuk auto-hitung. <br>• <b>Nama Aset</b> &amp; <b>Sub Klasifikasi</b>. <br>• <b>Pemilik</b> &amp; <b>Lokasi</b>. <br>• <b>Status</b> &amp; <b>Tahun Referensi</b>. <br>• <b>Data Dinamis</b> — atribut spesifik per kategori aset.',
            integration: {
                triggerEl: 'button[title="Lihat Detail"]',
                triggerTitle: 'Lihat Keterkaitan',
                triggerDesc: 'Sekarang klik tombol "Lihat Detail" pada salah satu baris di tabel bawah untuk melihat keterkaitannya dengan modul lain.',
                panelEl: '[data-tour="related-panel"]',
                panelTitle: 'Riwayat Terkait',
                panelDesc: 'Sama seperti panel di SDM tadi, tapi dari sisi Aset — semua Risiko, Perubahan, Keamanan Informasi, SDM, dan lainnya yang menyebut Aset ini otomatis terkumpul di sini.',
            },
        }),
    },
    {
        id: 'keamanan',
        label: 'Manajemen Keamanan Informasi',
        steps: moduleSteps({
            path: '/security-programs',
            title: 'Manajemen Keamanan Informasi',
            openDesc: 'Klik untuk membuka form Program Kerja Keamanan baru.',
            fieldsDesc:
                'Kolom-kolom di form ini: <br>• <b>Aset Terkait</b> (opsional). <br>• <b>Program Kerja</b> — uraian programnya. <br>• <b>Kegiatan</b> &amp; <b>PIC</b>. <br>• <b>Target &amp; Realisasi Tahunan</b> — kolom dinamis per tahun. <br>Program di sini juga bisa terisi otomatis kalau ada Risiko yang levelnya naik jadi Tinggi/Kritis.',
        }),
    },
    {
        id: 'risiko',
        label: 'Manajemen Risiko',
        steps: moduleSteps({
            path: '/risks',
            title: 'Manajemen Risiko',
            openDesc: 'Klik untuk membuka form Risiko baru.',
            fieldsDesc:
                'Kolom-kolom di form ini: <br>• <b>Judul Risiko</b> &amp; <b>Aset Terkait</b>. <br>• <b>Kategori Risiko</b>, <b>Sumber Ancaman</b>, <b>Kerentanan</b>. <br>• <b>Kemungkinan</b> &times; <b>Dampak</b> → <b>Level Risiko</b> dihitung otomatis saat disimpan, dan naik satu-dua tingkat kalau aset terkait kritis dan/atau ada SDM berlevel Tinggi/Kritis dengan Kode Personil sama. <br>• <b>Pemilik Risiko</b> &amp; <b>Strategi Penanganan</b>. <br>• <b>Status</b>, <b>Tanggal Identifikasi</b>, <b>Tinjauan Berikutnya</b>.',
        }),
    },
    {
        id: 'perubahan',
        label: 'Manajemen Perubahan',
        steps: moduleSteps({
            path: '/changes',
            title: 'Manajemen Perubahan',
            openDesc: 'Klik untuk membuka form Perubahan baru.',
            fieldsDesc:
                'Kolom-kolom di form ini: <br>• <b>Judul/Usulan Modifikasi</b> &amp; <b>Aset Terkait</b>. <br>• <b>Jenis Perubahan</b>, <b>Kategori</b> (Major/Minor), <b>Prioritas Eksekusi</b>. <br>• <b>Level Risiko Inheren</b> &amp; <b>Target Implementasi</b>. <br>• <b>Keputusan Penanganan</b> &amp; <b>Status</b>. <br>• <b>Penanggung Jawab</b> &amp; <b>Data Dinamis</b> (detail penilaian risiko, rollback plan, dst).',
        }),
    },
    {
        id: 'layanan',
        label: 'Manajemen Layanan',
        steps: moduleSteps({
            path: '/services',
            title: 'Manajemen Layanan',
            openDesc: 'Klik untuk membuka form Layanan baru.',
            fieldsDesc:
                'Kolom-kolom di form ini: <br>• <b>Nama Layanan SPBE</b> &amp; <b>Deskripsi</b>. <br>• <b>Pengelola Layanan</b> &amp; <b>Pengelola Teknis</b>. <br>• <b>Cara Akses</b>, <b>Waktu Pelayanan</b>, <b>Target SLA</b>. <br>• <b>Status</b>. <br>• <b>Aset TIK Terkait</b> — centang satu atau lebih Aset, satu-satunya modul yang boleh dikaitkan ke banyak Aset sekaligus.',
        }),
    },
    {
        id: 'data_informasi',
        label: 'Manajemen Data & Informasi',
        steps: moduleSteps({
            path: '/data-information',
            title: 'Manajemen Data &amp; Informasi',
            openDesc: 'Klik untuk membuka form Catatan baru — modul terakhir dalam alur.',
            fieldsDesc:
                'Kolom-kolom di form ini: <br>• <b>Ancaman/Kejadian Gangguan Data</b> &amp; <b>Aset Terkait</b>. <br>• <b>Jenis Risiko</b> &amp; <b>Kategori Aset Data</b>. <br>• <b>Prioritas Penanganan</b> &amp; <b>Level Risiko Inheren</b>. <br>• <b>Target Implementasi</b> &amp; <b>Keputusan Penanganan Risiko</b>. <br>• <b>Status</b> &amp; <b>Penanggung Jawab</b>.',
            closeDesc:
                'Klik tombol <b>X</b> ini untuk menutup form. Ini modul terakhir — setelah ini tur selesai. Tombol "Mulai Tutorial" di atas selalu bisa dipakai untuk mengulang semuanya dari awal, dan tiap modul juga punya tombol "Tutorial Modul Ini" sendiri kalau cuma perlu diingatkan satu bagian saja.',
        }),
    },
];

let driverObj = null;

function normalizePath(path) {
    const p = path.replace(/\/+$/, '');
    return p === '' ? '/' : p;
}

function currentPath() {
    return normalizePath(window.location.pathname);
}

function buildStepList(mode) {
    if (mode === 'full') {
        return [...INTRO_STEPS, ...MODULES.flatMap((m) => m.steps)];
    }
    const found = MODULES.find((m) => m.id === mode);
    return found ? found.steps : [];
}

function saveState(mode, index) {
    localStorage.setItem(MODE_KEY, mode);
    localStorage.setItem(STEP_KEY, String(index));
}

function loadMode() {
    return localStorage.getItem(MODE_KEY);
}

function loadStepIndex() {
    const raw = localStorage.getItem(STEP_KEY);
    if (raw === null) return null;
    const i = parseInt(raw, 10);
    return Number.isNaN(i) ? null : i;
}

function clearTourState() {
    localStorage.removeItem(MODE_KEY);
    localStorage.removeItem(STEP_KEY);
}

function getDriver() {
    if (!driverObj) {
        driverObj = driver({
            allowClose: true,
            overlayOpacity: 0.6,
            stagePadding: 6,
            stageRadius: 12,
            popoverClass: 'bssn-tour-popover',
            smoothScroll: true,
        });
    }
    return driverObj;
}

function goToStep(mode, index) {
    if (index < 0) return;

    const list = buildStepList(mode);
    if (index >= list.length) {
        clearTourState();
        getDriver().destroy();
        return;
    }

    saveState(mode, index);
    const step = list[index];

    if (normalizePath(step.path) !== currentPath()) {
        window.location.href = step.path;
        return;
    }

    renderStep(mode, index);
}

function renderStep(mode, index) {
    const list = buildStepList(mode);
    const step = list[index];
    if (!step) {
        clearTourState();
        return;
    }
    const total = list.length;
    const isLast = index === total - 1;
    const d = getDriver();

    // Guards against both the real click AND the popover's own "Lanjut"
    // button firing for the same step (e.g. user clicks the real element,
    // then also happens to hit Enter) — first one wins, the rest are no-ops.
    let advanced = false;
    const advanceTo = (target) => {
        if (advanced) return;
        advanced = true;
        d.destroy();
        goToStep(mode, target);
    };

    d.highlight({
        element: step.el,
        onHighlightStarted: (element) => {
            if (step.advanceOnRealClick && element) {
                element.addEventListener(
                    'click',
                    () => setTimeout(() => advanceTo(index + 1), step.afterClickDelay ?? 400),
                    { once: true },
                );
            }
        },
        popover: {
            title: step.title,
            description: step.desc,
            side: step.side || 'bottom',
            align: 'start',
            showProgress: true,
            progressText: `Langkah ${index + 1} dari ${total}`,
            showButtons: index === 0 ? ['next', 'close'] : ['next', 'previous', 'close'],
            nextBtnText: isLast ? 'Selesai' : step.advanceOnRealClick ? 'Lewati' : 'Lanjut',
            prevBtnText: 'Kembali',
            onNextClick: () => advanceTo(index + 1),
            onPrevClick: () => {
                advanced = true;
                d.destroy();
                goToStep(mode, index - 1);
            },
            onCloseClick: () => {
                clearTourState();
                d.destroy();
            },
        },
        // Gives Livewire's AJAX round-trip (open/close a modal, load the
        // detail panel) time to actually land in the DOM before driver.js
        // gives up looking for the next step's element.
        waitForElement: 2000,
        skipMissingElement: true,
    });
}

export function startTour() {
    clearTourState();
    goToStep('full', 0);
}

export function startModuleTour(moduleId) {
    clearTourState();
    goToStep(moduleId, 0);
}

export function resumeTourIfActive() {
    const mode = loadMode();
    const index = loadStepIndex();
    if (mode === null || index === null) return;

    const list = buildStepList(mode);
    const step = list[index];
    if (!step) {
        clearTourState();
        return;
    }

    if (normalizePath(step.path) === currentPath()) {
        renderStep(mode, index);
    }
    // If the path doesn't match, the visitor navigated away manually —
    // leave the saved step alone rather than guessing; "Mulai Tutorial" (or
    // a module's own tutorial button) always restarts cleanly from the top.
}
