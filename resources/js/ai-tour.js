import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';

/**
 * Tutorial interaktif tiga fitur AI: Evaluasi Bukti Dukung, Tanya Dokumen,
 * dan Analisis AI tiket helpdesk. Dimulai dari tombol "Mulai tutorial" di
 * pop-out Panduan AI (components/ai-guide).
 *
 * Berbeda dengan tour.js (tur modul yang berpindah halaman), tur ini tetap di
 * satu halaman dan benar-benar menjalankan fiturnya: membuka formulir, mengisi
 * contoh bila pengguna membiarkannya kosong, menekan tombol AI, lalu menunggu
 * hasil AI masuk sebelum menjelaskan hasilnya.
 *
 * Isian langkah:
 *  - el       elemen yang disorot (selalu atribut data-tour di Blade).
 *  - click    pengguna menekan tombol aslinya ("Lanjut" menekankannya bila
 *             pengguna tidak mau). Tur menunggu permintaan Livewire selesai
 *             dan `expect` tampil; bila tidak tampil (mis. validasi gagal),
 *             langkah yang sama diulang dengan catatan `retry`.
 *  - wait     teks yang ditampilkan selama menunggu AI.
 *  - prepare  dijalankan saat Lanjut ditekan, mis. mengisi contoh bila isian
 *             masih kosong. Mengembalikan teks bila gagal.
 *  - optional langkah dilewati bila elemennya tidak tampil.
 *  - doneWhen bila selektor ini sudah tampil saat tur dimulai, langkah ini
 *             dan langkah sebelumnya dianggap selesai (mis. formulir sudah
 *             terbuka karena tur dimulai dari dalam formulir).
 */

const CONTOH_PERTANYAAN = 'Berapa lama server baru selesai disiapkan?';
const CONTOH_LAPORAN = 'Halaman depan website resmi berisi iklan judi online sejak pagi, sudah banyak warga yang melapor.';

const $ = (sel) => (typeof sel === 'string' ? document.querySelector(sel) : sel);
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

function visible(sel) {
    const el = $(sel);
    return !!el && el.getClientRects().length > 0 && getComputedStyle(el).visibility !== 'hidden';
}

function setValue(sel, value) {
    const el = $(sel);
    if (!el) return;
    el.value = value;
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
}

// Jumlah permintaan Livewire yang sedang berjalan. `succeed` dipanggil
// Livewire setelah tampilan diperbarui, jadi saat angka ini kembali 0 dan
// tenang sebentar, hasil AI sudah ada di halaman.
const net = { busy: 0, last: 0 };

function trackLivewire() {
    if (trackLivewire.done || !window.Livewire?.hook) return;
    trackLivewire.done = true;
    window.Livewire.hook('request', ({ succeed, fail }) => {
        net.busy++;
        net.last = Date.now();
        let open = true;
        const finish = () => {
            if (!open) return;
            open = false;
            net.busy = Math.max(0, net.busy - 1);
            net.last = Date.now();
        };
        succeed(finish);
        fail(finish);
    });
}
document.addEventListener('livewire:init', trackLivewire);
trackLivewire();

async function settle(expect, timeout) {
    const start = Date.now();
    await sleep(300);
    while (Date.now() - start < timeout) {
        const quietFor = net.busy === 0 ? Date.now() - Math.max(net.last, start) : 0;
        if (quietFor > 450 && visible(expect)) {
            await sleep(300); // biarkan animasi modal selesai
            return true;
        }
        if (quietFor > 1500) return false;
        await sleep(150);
    }
    return visible(expect);
}

async function uploadSample(input, url) {
    try {
        const res = await fetch(url, { credentials: 'same-origin' });
        if (!res.ok) return false;
        const file = new File([await res.blob()], 'Contoh Pedoman Manajemen Risiko SPBE.pdf', { type: 'application/pdf' });
        const files = new DataTransfer();
        files.items.add(file);
        input.files = files.files;
    } catch {
        return false;
    }
    const done = new Promise((resolve) => {
        input.addEventListener('livewire-upload-finish', () => resolve(true), { once: true });
        input.addEventListener('livewire-upload-error', () => resolve(false), { once: true });
        setTimeout(() => resolve(false), 30000);
    });
    input.dispatchEvent(new Event('change', { bubbles: true }));
    return done;
}

const TOURS = {
    bukti: [
        {
            el: '[data-tour="evidence-upload-button"]',
            title: 'Unggah bukti dukung',
            desc: 'Klik <b>Unggah</b> untuk membuka formulir.',
            click: true,
            expect: '[data-tour="evidence-form"]',
            doneWhen: '[data-tour="evidence-form"]',
        },
        {
            el: '[data-tour="evidence-claim"]',
            title: 'Modul dan level',
            desc: 'Pilih modul yang dinilai dan level yang diklaim OPD. Sistem memeriksa apakah isi PDF memenuhi level itu.',
        },
        {
            el: '[data-tour="evidence-meta"]',
            title: 'Judul dan OPD',
            desc: 'Boleh dikosongkan. Kalau judul kosong, nama berkas dipakai sebagai judul.',
        },
        {
            el: '[data-tour="evidence-file"]',
            title: 'Berkas PDF',
            desc: 'Pilih PDF yang teksnya bisa disalin, maksimal 10 MB.<br><br>Belum punya? Tekan <b>Lanjut</b> saja, tur memakai PDF contoh: Pedoman Manajemen Risiko yang mengklaim Level 3.',
            prepare: async (wait) => {
                const box = $('[data-tour="evidence-file"]');
                const input = box?.querySelector('input[type="file"]');
                if (!input || input.files.length) return;
                setValue('[data-field="module_key"]', 'risiko');
                setValue('[data-field="target_level"]', '3');
                if (!$('[data-field="unit_name"]')?.value) setValue('[data-field="unit_name"]', 'Diskominfo (contoh)');
                wait('Mengunggah PDF contoh', 'Sebentar, PDF contoh sedang diunggah.');
                if (!(await uploadSample(input, box.dataset.sampleUrl))) {
                    return 'PDF contoh gagal diunggah. Pilih berkas PDF sendiri, lalu tekan Lanjut.';
                }
            },
        },
        {
            el: '[data-tour="evidence-submit"]',
            title: 'Nilai dokumen',
            desc: 'Klik <b>Unggah &amp; Nilai</b>.',
            click: true,
            expect: '[data-tour="evidence-summary"]',
            wait: {
                title: 'Sedang menilai',
                desc: 'Sistem membaca PDF dan mencocokkannya dengan rubrik, lalu AI menulis catatan. Biasanya 5 sampai 20 detik.',
                timeout: 120000,
            },
            retry: 'Belum ada hasil. Periksa isian yang bertanda merah, lalu klik Unggah &amp; Nilai lagi.',
        },
        {
            el: '[data-tour="evidence-summary"]',
            title: 'Hasil penilaian',
            desc: '<b>Klaim</b> adalah level yang diajukan OPD. <b>Hasil</b> adalah level tertinggi yang semua unsurnya ditemukan. <b>Skor</b> adalah persentase unsur yang ditemukan sampai level yang diklaim.',
        },
        {
            el: '[data-tour="evidence-missing"]',
            title: 'Yang masih kurang',
            desc: 'Unsur rubrik yang tidak ditemukan di PDF. Inilah yang membuat level klaim belum tercapai.',
            optional: true,
        },
        {
            el: '[data-tour="evidence-validity"]',
            title: 'Keabsahan dokumen',
            desc: 'Dicek apakah ada nomor, tanggal, NIP penandatangan, keterangan TTE, dan tanda tangan digital yang tertanam di berkas.',
            optional: true,
        },
        {
            el: '[data-tour="evidence-details"]',
            title: 'Rincian dan catatan AI',
            desc: 'Buka <b>Rincian per level</b> untuk melihat centang tiap unsur. <b>Catatan AI</b> berisi ringkasan dari AI. AI tidak mengubah level atau skor.',
            optional: true,
            side: 'top',
        },
        {
            el: '[data-tour="evidence-decision"]',
            title: 'Keputusan',
            desc: 'Keputusan akhir tetap di admin modul: Diterima, Perlu perbaikan, atau Ditolak.',
            side: 'top',
        },
        {
            el: '[data-tour="evidence-actions"]',
            title: 'Buka PDF dan nilai ulang',
            desc: '<b>Buka PDF</b> untuk mencocokkan sendiri. <b>Nilai ulang</b> dipakai kalau dokumen atau rubrik berubah.',
            side: 'top',
        },
    ],

    tanya: [
        {
            el: '[data-tour="tab-tanya"]',
            title: 'Buka Tanya Dokumen',
            desc: 'Klik tab <b>Tanya Dokumen</b>.',
            click: true,
            expect: '[data-tour="ask-input"]',
            doneWhen: '[data-tour="ask-input"]',
        },
        {
            el: '[data-tour="ask-input"]',
            title: 'Tulis pertanyaan',
            desc: `Tanya seperti ke rekan kerja, kata-katanya tidak harus sama dengan isi dokumen.<br><br>Kalau dibiarkan kosong, tekan <b>Lanjut</b> dan tur memakai contoh: “${CONTOH_PERTANYAAN}”`,
            prepare: () => {
                const input = $('[data-tour="ask-input"]');
                if (input && !input.value.trim()) setValue(input, CONTOH_PERTANYAAN);
            },
        },
        {
            el: '[data-tour="ask-submit"]',
            title: 'Kirim pertanyaan',
            desc: 'Klik <b>Tanya</b>.',
            click: true,
            expect: '[data-tour="ask-answer"]',
            wait: {
                title: 'Mencari jawaban',
                desc: 'Sistem mencari potongan dokumen yang paling mirip maknanya, lalu AI menyusun jawaban dari potongan itu. Biasanya 3 sampai 15 detik.',
                timeout: 90000,
            },
            retry: 'Belum ada jawaban. Pastikan pertanyaan sudah terisi, lalu klik Tanya lagi.',
        },
        {
            el: '[data-tour="ask-answer"]',
            title: 'Jawaban',
            desc: 'Jawaban disusun hanya dari isi dokumen. Kalau tidak ada yang cocok, sistem menjawab tidak ditemukan.',
        },
        {
            el: '[data-tour="ask-sources"]',
            title: 'Sumber',
            desc: 'Dokumen dan halaman yang dipakai untuk menjawab. Klik <b>Buka PDF</b> untuk memeriksanya sendiri.',
            optional: true,
            side: 'top',
        },
        {
            el: '[data-tour="tab-aset"]',
            title: 'Menambah dokumen',
            desc: 'Dokumen baru ditambahkan lewat tab <b>Aset Pengetahuan</b>, lalu <b>Tambah Baru</b>. Beberapa detik setelah PDF diunggah, dokumen itu sudah bisa ditanya.',
        },
    ],

    triase: [
        {
            el: '[data-tour="tab-helpdesk"]',
            title: 'Buka Tiket Helpdesk',
            desc: 'Klik tab <b>Tiket Helpdesk</b>.',
            click: true,
            expect: '[data-tour="helpdesk-panel"]',
            doneWhen: '[data-tour="helpdesk-panel"]',
        },
        {
            el: '[data-tour="add-button"]',
            title: 'Buat tiket',
            desc: 'Klik <b>Tiket Baru</b>.',
            click: true,
            expect: '[data-tour="ticket-form"]',
            doneWhen: '[data-tour="ticket-form"]',
        },
        {
            el: '[data-tour="ticket-service"]',
            title: 'Layanan',
            desc: 'Pilih layanan yang bermasalah. Kalau layanan itu bergantung pada aset kritis, prioritasnya bisa naik.<br><br>Kalau kosong, tur memilih Layanan Website Resmi.',
            prepare: () => {
                const select = $('[data-tour="ticket-service"] select');
                if (!select || select.value) return;
                const options = [...select.options].filter((o) => o.value);
                const pick = options.find((o) => /website/i.test(o.text)) ?? options[0];
                if (pick) setValue(select, pick.value);
            },
        },
        {
            el: '[data-tour="ticket-issue"]',
            title: 'Isi laporan',
            desc: 'Tulis apa yang terjadi, sejak kapan, dan siapa yang terdampak.<br><br>Kalau dibiarkan kosong, tekan <b>Lanjut</b> dan tur memakai contoh laporan website yang disusupi iklan judi online.',
            prepare: () => {
                const text = $('[data-tour="ticket-issue"]');
                if (text && !text.value.trim()) setValue(text, CONTOH_LAPORAN);
            },
        },
        {
            el: '[data-tour="ticket-analyze"]',
            title: 'Analisis AI',
            desc: 'Klik <b>Analisis AI</b>.',
            click: true,
            expect: '[data-tour="ticket-priority"]',
            wait: {
                title: 'Sedang menganalisis',
                desc: 'Aturan menentukan kategori dan prioritas, lalu AI menulis kalimat pembuka balasan. Biasanya 3 sampai 10 detik.',
                timeout: 90000,
            },
            retry: 'Belum ada hasil. Pastikan isi laporan sudah terisi, lalu klik Analisis AI lagi.',
        },
        {
            el: '[data-tour="ticket-levels"]',
            title: 'Kategori, dampak, urgensi',
            desc: 'Terisi otomatis dari kata kunci di laporan. Boleh diubah, dan prioritas akan menyesuaikan.',
        },
        {
            el: '[data-tour="ticket-priority"]',
            title: 'Prioritas',
            desc: 'Dihitung dari tabel dampak × urgensi, bukan oleh AI, lengkap dengan target waktu tanggapan dan penyelesaian.',
        },
        {
            el: '[data-tour="ticket-reasons"]',
            title: 'Alasan dan langkah awal',
            desc: 'Dari mana prioritas itu didapat, dan langkah pertama yang bisa dilakukan operator.',
            optional: true,
        },
        {
            el: '[data-tour="ticket-reply"]',
            title: 'Draf balasan',
            desc: 'Kalimat pembuka ditulis AI, sisanya dari templat. Periksa dulu sebelum dikirim ke pelapor.',
            side: 'top',
        },
        {
            el: '[data-tour="ticket-actions"]',
            title: 'Simpan',
            desc: 'Klik <b>Simpan</b> untuk menyimpan tiket, atau <b>Batal</b> kalau hanya mencoba.',
            side: 'top',
        },
    ],
};

let drv = null;
let run = null;
let token = 0;
let switching = false;

function getDriver() {
    if (!drv) {
        drv = driver({
            allowClose: true,
            overlayOpacity: 0.55,
            stagePadding: 6,
            stageRadius: 12,
            popoverClass: 'bssn-tour-popover',
            smoothScroll: true,
            // Ditutup lewat Esc atau klik di luar: hentikan juga tur ini.
            onDestroyed: () => {
                if (!switching) run = null;
            },
        });
    }
    return drv;
}

function render(config) {
    const d = getDriver();
    switching = true;
    d.destroy();
    d.highlight(config);
    switching = false;
}

function stop() {
    run = null;
    token++;
    getDriver().destroy();
}

function wait(title, desc) {
    token++;
    render({
        popover: {
            title,
            description: desc,
            showButtons: ['close'],
            onCloseClick: stop,
        },
    });
}

async function afterClick(index) {
    const step = run.steps[index];
    if (step.wait) wait(step.wait.title, step.wait.desc);
    const ok = await settle(step.expect, step.wait?.timeout ?? 8000);
    if (!run) return;
    if (ok) show(index + 1, 1);
    else show(index, 1, step.retry ?? 'Belum berhasil. Coba klik lagi.');
}

function show(index, dir = 1, note = '') {
    if (!run) return;
    const { steps, first } = run;
    if (index >= steps.length) return stop();
    if (index < first) {
        index = first;
        dir = 1;
    }
    const step = steps[index];
    if (step.optional && !visible(step.el)) return show(index + dir, dir);

    const mine = ++token;
    const isLast = index === steps.length - 1;
    const prev = steps[index - 1];
    const canBack = index > first && prev && !prev.click && !prev.prepare;
    let moved = false;

    const next = async () => {
        if (moved || mine !== token) return;
        moved = true;
        if (step.prepare) {
            const problem = await step.prepare(wait);
            if (!run) return;
            if (problem) return show(index, 1, problem);
        }
        isLast ? stop() : show(index + 1, 1);
    };

    render({
        element: step.el,
        onHighlightStarted: (el) => {
            if (!step.click || !el) return;
            el.addEventListener(
                'click',
                () => {
                    if (moved || mine !== token) return;
                    moved = true;
                    afterClick(index);
                },
                { once: true },
            );
        },
        popover: {
            title: step.title,
            description: step.desc + (note ? `<span class="bssn-tour-note">${note}</span>` : ''),
            side: step.side ?? 'bottom',
            align: 'start',
            showProgress: true,
            progressText: `${index + 1} dari ${steps.length}`,
            showButtons: canBack ? ['next', 'previous', 'close'] : ['next', 'close'],
            nextBtnText: isLast ? 'Selesai' : 'Lanjut',
            prevBtnText: 'Kembali',
            onNextClick: () => {
                // Langkah klik: "Lanjut" menekan tombol aslinya, supaya
                // langkah berikutnya tetap punya hasil untuk dijelaskan.
                const el = step.click ? $(step.el) : null;
                if (el) {
                    if (!el.disabled) el.click();
                    return;
                }
                next();
            },
            onPrevClick: () => {
                if (moved || mine !== token) return;
                moved = true;
                show(index - 1, -1);
            },
            onCloseClick: stop,
        },
    });
}

export function startAiTour(feature) {
    const steps = TOURS[feature];
    if (!steps) return;
    trackLivewire();
    let first = 0;
    steps.forEach((step, i) => {
        if (step.doneWhen && visible(step.doneWhen)) first = i + 1;
    });
    run = { steps, first };
    show(first, 1);
}
