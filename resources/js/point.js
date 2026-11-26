import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';

/*
 * Tombol "Tunjukkan tombolnya" di Asisten membuka halaman dengan ?tunjuk=<data-tour>.
 * Di halaman itu elemen yang dimaksud disorot satu kali, lalu ?tunjuk dihapus dari
 * alamat supaya muat ulang tidak menyorot lagi. Hanya nama data-tour yang dikenal,
 * dan teks sorotan dibuat dari tabel ini, bukan dari alamat.
 */
const MESSAGES = {
    'add-button': 'Klik tombol ini, isi formulirnya, lalu klik Simpan.',
    'evidence-upload-button': 'Klik Unggah, pilih modul dan level yang diklaim, lalu pilih PDF-nya.',
    'integration-map': 'Klik Alur Data untuk melihat data modul ini dikirim ke mana dan datang dari mana.',
    'tab-tanya': 'Klik tab ini, lalu tulis pertanyaan tentang isi dokumen.',
    'tiles-grid': 'Klik salah satu kotak untuk membuka modulnya.',
};

function escapeHtml(text) {
    return text.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
}

function visibleTarget(key) {
    return [...document.querySelectorAll(`[data-tour="${key}"]`)].find((el) => el.offsetParent !== null) ?? null;
}

export function pointFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const key = params.get('tunjuk');
    if (!key || !Object.prototype.hasOwnProperty.call(MESSAGES, key)) {
        return;
    }
    params.delete('tunjuk');
    const rest = params.toString();
    window.history.replaceState(window.history.state, '', window.location.pathname + (rest ? `?${rest}` : '') + window.location.hash);

    let tries = 0;
    const attempt = () => {
        // Tab dan label tombol diatur Alpine setelah halaman dimuat; tunggu sebentar.
        const el = visibleTarget(key);
        if (!el) {
            if (++tries < 30) setTimeout(attempt, 150);
            return;
        }
        el.scrollIntoView({ block: 'center' });
        const label = (el.innerText || '').trim().replace(/\s+/g, ' ').slice(0, 40);
        const spot = driver({
            allowClose: true,
            overlayOpacity: 0.45,
            stagePadding: 6,
            stageRadius: 999,
            showButtons: ['close'],
        });
        spot.highlight({
            element: el,
            popover: {
                title: label ? `Klik "${escapeHtml(label)}"` : 'Di sini',
                description: escapeHtml(MESSAGES[key]),
                side: 'bottom',
                align: 'end',
            },
        });
        // Tombol yang disorot tetap bisa diklik; sorotan ditutup saat itu.
        el.addEventListener('click', () => spot.destroy(), { once: true });
    };
    setTimeout(attempt, 250);
}
