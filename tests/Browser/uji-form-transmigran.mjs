/**
 * Uji peramban untuk form transmigran bertahap (Putaran 4).
 *
 * Mengapa uji peramba: yang diuji adalah perilaku, bukan markup. "Tombol
 * Lanjut tidak boleh maju bila langkah aktif belum sah" dan "tombol Simpan
 * harus MELOMPAT ke langkah bermasalah, bukan menolak diam-diam" hanya dapat
 * dibuktikan dengan menekan tombolnya dan melihat apa yang terjadi pada DOM.
 *
 * Cacat "form menolak diam-diam" sudah tiga kali terjadi di repo ini
 * (notes.md 1877 form wilayah, 2197 saprotan, 2299 panen): peramban menahan
 * pengiriman sambil menunjuk elemen yang sedang tersembunyi (display:none),
 * tanpa pesan yang terbaca petugas. Butir terakhir uji ini menjaganya.
 *
 * Dijalankan lewat Edge headless dan protokol DevTools, TANPA menambah
 * dependensi. Menuntut peladen hidup di 127.0.0.1:8099.
 *
 *   php artisan serve --port=8099
 *   node tests/Browser/uji-form-transmigran.mjs
 */

import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { setTimeout as tidur } from 'node:timers/promises';

const ASAL = 'http://127.0.0.1:8099';
const PORT_DEVTOOLS = 9351;

const LEBAR_LAYAR = 1280;
const TINGGI_LAYAR = 900;

const JALUR_EDGE = [
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
];

let gagal = 0;
let lulus = 0;

function periksa(nama, benar, keterangan = '') {
    if (benar) {
        lulus += 1;
        console.log(`  OK   ${nama}`);
    } else {
        gagal += 1;
        console.log(`  GAGAL ${nama}${keterangan ? ` - ${keterangan}` : ''}`);
    }
}

function cariEdge() {
    for (const jalur of JALUR_EDGE) {
        if (existsSync(jalur)) {
            return jalur;
        }
    }
    throw new Error('Edge tidak ditemukan pada jalur yang dikenal.');
}

async function main() {
    if (typeof WebSocket === 'undefined') {
        console.log('  LEWAT: WebSocket bawaan tidak tersedia pada Node ini.');

        return;
    }

    const proses = spawn(cariEdge(), [
        '--headless=new',
        `--remote-debugging-port=${PORT_DEVTOOLS}`,
        '--no-first-run',
        '--disable-gpu',
        `--window-size=${LEBAR_LAYAR},${TINGGI_LAYAR}`,
        'about:blank',
    ]);

    try {
        let daftar = null;

        for (let i = 0; i < 40; i += 1) {
            try {
                daftar = await (await fetch(`http://127.0.0.1:${PORT_DEVTOOLS}/json/list`)).json();
                break;
            } catch {
                await tidur(250);
            }
        }

        if (! daftar) {
            throw new Error('DevTools tidak merespons.');
        }

        const soket = new WebSocket(daftar.find((t) => t.type === 'page').webSocketDebuggerUrl);
        let nomor = 0;
        const menunggu = new Map();

        soket.addEventListener('message', (peristiwa) => {
            const pesan = JSON.parse(peristiwa.data);
            if (pesan.id && menunggu.has(pesan.id)) {
                menunggu.get(pesan.id)(pesan.result);
                menunggu.delete(pesan.id);
            }
        });

        await new Promise((selesai, tolak) => {
            soket.addEventListener('open', selesai);
            soket.addEventListener('error', tolak);
        });

        const kirim = (metode, params = {}) => new Promise((selesai) => {
            nomor += 1;
            menunggu.set(nomor, selesai);
            soket.send(JSON.stringify({ id: nomor, method: metode, params }));
        });

        const nilai = async (ungkapan) => {
            const hasil = await kirim('Runtime.evaluate', {
                expression: ungkapan,
                returnByValue: true,
                awaitPromise: true,
            });

            return hasil?.result?.value;
        };

        await kirim('Page.enable');
        await kirim('Runtime.enable');
        await kirim('Emulation.setDeviceMetricsOverride', {
            width: LEBAR_LAYAR,
            height: TINGGI_LAYAR,
            deviceScaleFactor: 1,
            mobile: false,
        });

        await kirim('Page.navigate', { url: `${ASAL}/transmigran` });
        for (let i = 0; i < 60; i += 1) {
            if (await nilai('!! window.Alpine')) break;
            await tidur(250);
        }
        await tidur(600);

        // Pembantu di dalam halaman: cari wadah langkah yang sedang tampak.
        const langkahTampak = `
            (() => {
                const wadah = [...document.querySelectorAll('[data-langkah]')]
                    .filter((w) => w.offsetParent !== null);
                return wadah.length === 1 ? Number(wadah[0].dataset.langkah) : -1;
            })()
        `;
        const isiKk = (kunci, val) => nilai(`
            (() => {
                const el = document.querySelector('#tambah_${kunci}');
                if (! el) return false;
                el.value = ${JSON.stringify(val)};
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
                return true;
            })()
        `);
        const klikTombol = (teks) => nilai(`
            (() => {
                const b = [...document.querySelectorAll('[role="dialog"] button')]
                    .find((x) => x.offsetParent !== null && x.textContent.trim().startsWith(${JSON.stringify(teks)}));
                if (! b) return false;
                b.click();
                return true;
            })()
        `);

        console.log('\nForm transmigran bertahap:');

        await nilai(`window.dispatchEvent(new CustomEvent('buka-modal', { detail: 'formTambahTransmigran' }))`);
        await tidur(600);

        periksa('modal terbuka pada langkah 1', await nilai(langkahTampak) === 1);

        // Halaman memuat dua modal (tambah + ubah berbaris); ambil penunjuk
        // langkah yang sedang tampak.
        periksa('penunjuk langkah menampilkan empat nama',
            await nilai(`[...document.querySelectorAll('[aria-label="Langkah pengisian"]')]
                .find((ol) => ol.offsetParent !== null)?.querySelectorAll('li').length ?? 0`) === 4);

        periksa('tombol Simpan belum tampak di langkah 1',
            await nilai(`! [...document.querySelectorAll('[role="dialog"] button')]
                .some((b) => b.offsetParent !== null && b.textContent.includes('Simpan Data Transmigran'))`) === true);

        // Lanjut dengan isian wajib kosong -> TIDAK boleh maju.
        await klikTombol('Lanjut');
        await tidur(300);
        periksa('Lanjut ditahan saat isian wajib langkah 1 kosong', await nilai(langkahTampak) === 1);

        // Isi langkah 1 lalu maju.
        await isiKk('nama', 'BUDI SANTOSO');
        await isiKk('nik', '1234567890123456');
        await isiKk('no_kk', '6543210987654321');
        await isiKk('pekerjaan', 'PETANI');
        await tidur(200);
        await klikTombol('Lanjut');
        await tidur(400);
        periksa('maju ke langkah 2 setelah langkah 1 lengkap', await nilai(langkahTampak) === 2);

        // Isi langkah 2. wilayah-picker: pilih SP pertama.
        await nilai(`
            (() => {
                const sp = document.querySelector('#tambah_tahun_kedatangan');
                if (sp) { sp.value = '2019'; sp.dispatchEvent(new Event('input', { bubbles: true })); }
                const st = document.querySelector('#tambah_status_tinggal');
                if (st) { st.value = 'Aktif'; st.dispatchEvent(new Event('change', { bubbles: true })); }
                const pilihSp = document.querySelector('[name="satuan_permukiman_id"]');
                if (pilihSp && pilihSp.options && pilihSp.options.length > 1) {
                    pilihSp.value = pilihSp.options[1].value;
                    pilihSp.dispatchEvent(new Event('change', { bubbles: true }));
                }
            })()
        `);
        await tidur(200);
        await klikTombol('Lanjut');
        await tidur(400);
        periksa('maju ke langkah 3', await nilai(langkahTampak) === 3);

        await klikTombol('Lanjut');
        await tidur(400);
        periksa('maju ke langkah 4 (anggota keluarga boleh kosong)', await nilai(langkahTampak) === 4);

        periksa('tombol Simpan tampak di langkah 4',
            await nilai(`[...document.querySelectorAll('[role="dialog"] button')]
                .some((b) => b.offsetParent !== null && b.textContent.includes('Simpan Data Transmigran'))`) === true);

        // Kosongkan isian wajib langkah 1 (sekarang tersembunyi), lalu Simpan.
        // Modal WAJIB melompat kembali ke langkah 1, bukan diam.
        await nilai(`
            (() => {
                const el = document.querySelector('#tambah_nama');
                el.value = '';
                el.dispatchEvent(new Event('input', { bubbles: true }));
            })()
        `);
        await tidur(150);
        await klikTombol('Simpan Data Transmigran');
        await tidur(500);
        periksa('Simpan melompat kembali ke langkah 1 yang bermasalah, bukan menolak diam-diam',
            await nilai(langkahTampak) === 1);
        periksa('modal masih terbuka setelah Simpan gagal',
            await nilai(`[...document.querySelectorAll('[role="dialog"]')].some((d) => d.getClientRects().length > 0)`) === true);

        soket.close();
    } finally {
        proses.kill();
    }

    console.log(`\n${lulus} lulus, ${gagal} gagal.`);
    process.exit(gagal > 0 ? 1 : 0);
}

main().catch((galat) => {
    console.error(galat);
    process.exit(1);
});
