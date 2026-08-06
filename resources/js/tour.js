import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';

/**
 * A guided tour across the whole 8-module flow (SDM -> Data & Informasi),
 * not just one page — so state has to survive full page loads. localStorage
 * is the simplest thing that does that without a server round-trip: on
 * every DOMContentLoaded, resumeTour() checks whether a step is "in
 * progress" and, if the current URL matches that step's page, renders it.
 * "Next" on the last step of a page just writes the next step's index and
 * navigates there via a normal link — no SPA routing to keep in sync with.
 */
const STORAGE_KEY = 'bssnTourStep';

const STEPS = [
    {
        path: '/dashboard',
        el: '[data-tour="tiles-grid"]',
        title: 'Selamat Datang di BSSN ISMS',
        desc: 'Ini 8 modul manajemen yang saling terhubung. Tur singkat ini akan mengajak Anda berkeliling dari SDM sampai Data &amp; Informasi, urut sesuai alurnya — sambil menunjukkan titik-titik yang saling terkait.',
    },
    {
        path: '/dashboard',
        el: '[data-tour="tile-sdm"]',
        title: 'Mulai dari SDM',
        desc: 'SDM adalah langkah pertama — mencatat risiko yang berhubungan dengan orang, pegawai maupun pihak ketiga. Klik "Lanjut" untuk masuk ke modulnya.',
    },
    {
        path: '/hr-risks',
        el: '[data-tour="add-button"]',
        title: 'Manajemen SDM',
        desc: 'Klik tombol ini untuk menambah data baru. Ada kolom "Kode Personil" di form-nya — kode unik yang nanti menghubungkan data ini ke modul lain.',
    },
    {
        path: '/hr-risks',
        preClick: 'button[title="Lihat Detail"]',
        el: '[data-tour="cross-module-panel"]',
        title: 'Keterkaitan Lintas Modul',
        desc: 'Ini bagian paling penting: begitu Kode Personil yang sama dipakai di modul lain, semuanya otomatis terkumpul di sini — tidak perlu dicari manual satu-satu.',
        side: 'top',
    },
    {
        path: '/knowledge',
        el: '[data-tour="add-button"]',
        title: 'Manajemen Pengetahuan',
        desc: 'Tempat menyimpan SOP, manual, dan peta keahlian pegawai — ada 4 bagian yang bisa dipilih lewat tab di atas. Tombol ini menyesuaikan tab yang sedang aktif.',
    },
    {
        path: '/assets',
        el: '[data-tour="add-button"]',
        title: 'Manajemen Aset',
        desc: 'Daftar semua perangkat dan sistem TIK — modul ini paling banyak disebut oleh modul lain (Risiko, Perubahan, Keamanan, dst).',
    },
    {
        path: '/assets',
        preClick: 'button[title="Lihat Detail"]',
        el: '[data-tour="related-panel"]',
        title: 'Riwayat Terkait',
        desc: 'Sama seperti panel di SDM tadi, tapi dari sisi Aset — semua Risiko, Perubahan, Keamanan Informasi, dan lainnya yang menyebut Aset ini otomatis terkumpul di sini.',
        side: 'top',
    },
    {
        path: '/security-programs',
        el: '[data-tour="add-button"]',
        title: 'Manajemen Keamanan Informasi',
        desc: 'Mencatat program kerja untuk menjaga keamanan sistem. Sering terisi otomatis juga sebagai tindak lanjut kalau ada Risiko yang levelnya naik tinggi.',
    },
    {
        path: '/risks',
        el: '[data-tour="add-button"]',
        title: 'Manajemen Risiko',
        desc: 'Daftar ancaman/masalah yang mungkin terjadi. Kalau levelnya naik jadi Tinggi/Kritis, sistem otomatis membuatkan draf tindak lanjut di 4 modul lain sekaligus.',
    },
    {
        path: '/changes',
        el: '[data-tour="add-button"]',
        title: 'Manajemen Perubahan',
        desc: 'Setiap kali ada perubahan ke sebuah sistem — upgrade, perbaikan, ganti pengaturan — dicatat di sini supaya ada jejaknya.',
    },
    {
        path: '/services',
        el: '[data-tour="add-button"]',
        title: 'Manajemen Layanan',
        desc: 'Katalog layanan yang disediakan untuk pengguna, plus catatan gangguan (Log Operasional) dan performanya (Evaluasi Kinerja). Satu-satunya modul yang boleh dikaitkan ke lebih dari satu Aset sekaligus.',
    },
    {
        path: '/data-information',
        el: '[data-tour="add-button"]',
        title: 'Manajemen Data &amp; Informasi',
        desc: 'Modul terakhir dalam alur — memastikan data dan informasi yang dihasilkan dari semua proses sebelumnya sudah benar dan aman. Tur selesai — klik "Selesai" kapan saja, dan tombol "Mulai Tutorial" di atas selalu bisa dipakai untuk mengulang.',
        isFinal: true,
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

function saveStepIndex(i) {
    localStorage.setItem(STORAGE_KEY, String(i));
}

function loadStepIndex() {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (raw === null) return null;
    const i = parseInt(raw, 10);
    return Number.isNaN(i) ? null : i;
}

function clearTourState() {
    localStorage.removeItem(STORAGE_KEY);
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

function goToStep(index) {
    if (index < 0) return;

    if (index >= STEPS.length) {
        clearTourState();
        getDriver().destroy();
        return;
    }

    saveStepIndex(index);
    const step = STEPS[index];

    if (normalizePath(step.path) !== currentPath()) {
        window.location.href = step.path;
        return;
    }

    renderStep(index);
}

function renderStep(index) {
    const step = STEPS[index];
    const d = getDriver();

    if (step.preClick) {
        const trigger = document.querySelector(step.preClick);
        if (trigger) trigger.click();
    }

    d.highlight({
        element: step.el,
        popover: {
            title: step.title,
            description: step.desc,
            side: step.side || 'bottom',
            align: 'start',
            showProgress: true,
            progressText: `Langkah ${index + 1} dari ${STEPS.length}`,
            showButtons: index === 0 ? ['next', 'close'] : ['next', 'previous', 'close'],
            nextBtnText: step.isFinal ? 'Selesai' : 'Lanjut',
            prevBtnText: 'Kembali',
            onNextClick: () => {
                d.destroy();
                if (step.isFinal) {
                    clearTourState();
                } else {
                    goToStep(index + 1);
                }
            },
            onPrevClick: () => {
                d.destroy();
                goToStep(index - 1);
            },
            onCloseClick: () => {
                clearTourState();
                d.destroy();
            },
        },
        // Gives Livewire's AJAX-rendered detail panel (after preClick) time
        // to actually land in the DOM before driver.js gives up looking.
        waitForElement: step.preClick ? 2000 : undefined,
        skipMissingElement: true,
    });
}

export function startTour() {
    clearTourState();
    goToStep(0);
}

export function resumeTourIfActive() {
    const index = loadStepIndex();
    if (index === null) return;

    const step = STEPS[index];
    if (!step) {
        clearTourState();
        return;
    }

    if (normalizePath(step.path) === currentPath()) {
        renderStep(index);
    }
    // If the path doesn't match, the visitor navigated away manually —
    // leave the saved step alone rather than guessing; "Mulai Tutorial"
    // always restarts cleanly from the top.
}
