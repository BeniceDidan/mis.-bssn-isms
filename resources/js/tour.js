import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';

/**
 * Guided tour, two ways to run it:
 *  - startTour(): one continuous walk across all 8 modules (SDM -> Data &
 *    Informasi), same order as the app's own flow.
 *  - startModuleTour(id): just one module's steps, launched from that
 *    module's own "Tutorial" button — same step data, just sliced
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
    'Kode yang sama di modul lain berarti orang yang sama. Sistem tidak mencocokkan nama. Kosongkan untuk dibuat otomatis.';

const DEFAULT_CLOSE_DESC =
    'Tutup form ini untuk lanjut. Isian uji coba tidak tersimpan. Untuk menyimpan data sungguhan, klik <b>Simpan</b>.';

const FLOW_NOTE = ' Rinciannya ada di tombol <b>Alur Data</b>.';

function moduleSteps({ path, openDesc, fieldsDesc, flowDesc, closeDesc = DEFAULT_CLOSE_DESC, integration = null }) {
    const steps = [
        {
            path,
            el: '[data-tour="add-button"]',
            title: 'Buka form',
            desc: openDesc,
            advanceOnRealClick: true,
            afterClickDelay: 650,
        },
        {
            path,
            el: '[data-tour="form-body"]',
            title: 'Isi form',
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
            title: 'Tutup form',
            desc: closeDesc,
            advanceOnRealClick: true,
            afterClickDelay: 500,
        },
        {
            path,
            el: '[data-tour="integration-map"]',
            title: 'Ke mana datanya',
            desc: flowDesc + FLOW_NOTE,
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
        title: 'Selamat datang',
        desc: 'Delapan modul ini saling terhubung. Tur ini membuka form tiap modul, dari SDM sampai Data &amp; Informasi, dan menunjukkan ke mana datanya dikirim.',
    },
    {
        path: '/dashboard',
        el: '[data-tour="tile-sdm"]',
        title: 'Mulai dari SDM',
        desc: 'Modul pertama mencatat risiko pada pegawai dan pihak ketiga pengelola TIK. Klik Lanjut untuk membukanya.',
    },
];

const MODULES = [
    {
        id: 'sdm',
        label: 'Manajemen SDM',
        steps: moduleSteps({
            path: '/hr-risks',
            openDesc: 'Klik tombol ini untuk menambah risiko SDM.',
            fieldsDesc: 'Isi siapa orangnya, kategori dan uraian ancamannya, level risiko, target waktu, status, dan penanggung jawab.',
            flowDesc: 'Risiko SDM Tinggi atau Kritis menaikkan satu tingkat level risiko di Manajemen Risiko yang memakai Kode Personil yang sama.',
            integration: {
                triggerEl: 'button[title="Lihat Detail"]',
                triggerTitle: 'Lihat detail',
                triggerDesc: 'Klik <b>Lihat Detail</b> pada salah satu baris di tabel.',
                panelEl: '[data-tour="cross-module-panel"]',
                panelTitle: 'Data orang yang sama',
                panelDesc: 'Data di modul lain yang memakai Kode Personil yang sama terkumpul di sini.',
            },
        }),
    },
    {
        id: 'pengetahuan',
        label: 'Manajemen Pengetahuan',
        steps: moduleSteps({
            path: '/knowledge',
            openDesc: 'Pastikan tab Aset Pengetahuan aktif, lalu klik tombol ini.',
            fieldsDesc: 'Isi nama dokumen, jenis dan kategori, unit pemilik, lalu tautan, PDF, atau keduanya. Pilih Aset Terkait bila dokumen membahas aset tertentu.',
            flowDesc: 'PDF yang diunggah bisa dicari di Tanya Dokumen dan Asisten, dan disarankan saat ada tiket helpdesk.',
        }),
    },
    {
        id: 'aset',
        label: 'Manajemen Aset',
        steps: moduleSteps({
            path: '/assets',
            openDesc: 'Klik untuk menambah aset. Daftarkan aset lebih dulu karena modul lain menautkan datanya ke sini.',
            fieldsDesc: 'Isi nama, kategori, pemilik, lokasi, dan status. Isi nilai Kerahasiaan, Integritas, dan Ketersediaan (1–5); kritikalitas dihitung dari nilai tertinggi.',
            flowDesc: 'Kritikalitas Tinggi atau Kritis menaikkan satu tingkat level risiko yang tertaut ke aset ini.',
            integration: {
                triggerEl: 'button[title="Lihat Detail"]',
                triggerTitle: 'Lihat detail',
                triggerDesc: 'Klik <b>Lihat Detail</b> pada salah satu baris di tabel.',
                panelEl: '[data-tour="related-panel"]',
                panelTitle: 'Riwayat Terkait',
                panelDesc: 'Semua risiko, perubahan, layanan, dokumen pengetahuan, dan program keamanan yang memilih aset ini tampil di sini.',
            },
        }),
    },
    {
        id: 'keamanan',
        label: 'Manajemen Keamanan Informasi',
        steps: moduleSteps({
            path: '/security-programs',
            openDesc: 'Klik untuk menambah program kerja keamanan.',
            fieldsDesc: 'Isi program kerja, kegiatan, penanggung jawab, serta target dan realisasi per tahun.',
            flowDesc: 'Risiko Tinggi atau Kritis otomatis membuat program tindak lanjut di sini. Angka aset, risiko, dan sosialisasi di halaman ini dihitung dari modul lain.',
        }),
    },
    {
        id: 'risiko',
        label: 'Manajemen Risiko',
        steps: moduleSteps({
            path: '/risks',
            openDesc: 'Klik untuk menambah risiko.',
            fieldsDesc: 'Isi judul, aset terkait, kategori, sumber ancaman, dan kerentanan. Pilih Kemungkinan dan Dampak; level risiko dihitung saat disimpan.',
            flowDesc: 'Risiko Tinggi atau Kritis otomatis membuat tindak lanjut di Keamanan Informasi, Perubahan, dan Data &amp; Informasi, serta tiket untuk layanan yang memakai asetnya.',
        }),
    },
    {
        id: 'perubahan',
        label: 'Manajemen Perubahan',
        steps: moduleSteps({
            path: '/changes',
            openDesc: 'Klik untuk menambah perubahan.',
            fieldsDesc: 'Isi usulan perubahan, aset terkait, jenis, kategori (Mayor/Minor), prioritas, level risiko, target, keputusan, status, dan penanggung jawab.',
            flowDesc: 'Usulan dari risiko Tinggi atau Kritis masuk ke sini otomatis. Pilih Aset Terkait supaya perubahan muncul di Riwayat Terkait aset.',
        }),
    },
    {
        id: 'layanan',
        label: 'Manajemen Layanan',
        steps: moduleSteps({
            path: '/services',
            openDesc: 'Klik untuk menambah layanan.',
            fieldsDesc: 'Isi nama layanan, deskripsi, pengelola, cara akses, waktu pelayanan, target ketersediaan, dan status. Centang aset yang dipakai layanan ini.',
            flowDesc: 'Bila ada risiko Tinggi atau Kritis pada aset yang dipakai layanan ini, tiket otomatis dibuat di tab Tiket Helpdesk.',
        }),
    },
    {
        id: 'data_informasi',
        label: 'Manajemen Data & Informasi',
        steps: moduleSteps({
            path: '/data-information',
            openDesc: 'Klik untuk menambah catatan data.',
            fieldsDesc: 'Isi ancaman atau gangguan data, aset terkait, jenis risiko, kategori data, prioritas, level risiko, target, keputusan, status, dan penanggung jawab.',
            flowDesc: 'Catatan tinjauan dari risiko Tinggi atau Kritis masuk ke sini otomatis.',
            closeDesc: 'Tutup form. Ini modul terakhir. Tur bisa diulang lewat <b>Mulai Tutorial</b> di atas, atau tombol <b>Tutorial</b> di tiap modul.',
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

    // Kolom yang dilipat di "Isian lain" (components/form-more) dibuka dulu supaya
    // bisa disorot; Alpine menampilkannya sesaat kemudian, jadi langkah diulang.
    const target = document.querySelector(step.el);
    const fold = target?.closest('[data-form-more]');
    if (fold && fold.offsetParent !== null && target.offsetParent === null) {
        fold.querySelector('[data-form-more-toggle]')?.click();
        setTimeout(() => renderStep(mode, index), 120);
        return;
    }

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
