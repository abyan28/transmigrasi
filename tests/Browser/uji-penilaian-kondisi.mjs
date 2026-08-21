/**
 * Uji peramban untuk halaman pengaturan penilaian kondisi SP.
 *
 * Mengapa uji peramban: yang diperiksa di sini adalah PERILAKU, bukan markup.
 * Kotak centang "Dinilai" menyembunyikan isian bobot dan tingkat, tingkat
 * parameter primer terkunci hanya pada tiga baris tertentu, dan ambang status
 * terendah dikunci sebagai penampung sisa. Ketiganya diputuskan Alpine saat
 * modal terbuka, sehingga tidak terlihat pada HTML yang dikirim peladen. *
 *   php artisan serve --port=8099
 *   node tests/Browser/uji-penilaian-kondisi.mjs
 */

import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { setTimeout as tidur } from 'node:timers/promises';

const ASAL = 'http://127.0.0.1:8099';
const PORT_DEVTOOLS = 9339;

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

        const buka = async (jalur) => {
            await kirim('Page.navigate', { url: `${ASAL}${jalur}` });

            for (let i = 0; i < 60; i += 1) {
                if (await nilai('!! window.Alpine')) {
                    break;
                }

                await tidur(250);
            }

            await tidur(600);
        };

        await buka('/master/penilaian-kondisi');

        const awal = JSON.parse(await nilai(`(() => {
            const tab = [...document.querySelectorAll('[role="tab"]')];
            const baris = [...document.querySelectorAll('tbody tr')];
            const b = document.documentElement.getBoundingClientRect();

            return JSON.stringify({
                jumlahTab: tab.length,
                tabTerlihat: tab.filter((t) => {
                    const r = t.getBoundingClientRect();
                    return r.width > 0 && r.left >= 0 && r.right <= b.width;
                }).length,
                gulirMendatar: document.documentElement.scrollWidth > document.documentElement.clientWidth,
                barisTampak: baris.filter((t) => t.getBoundingClientRect().height > 0).length,
                adaTotalBobot: document.body.textContent.includes('37'),
                adaKeamanan: document.body.textContent.includes('Sarana Keamanan'),
                tidakDinilai: (document.body.textContent.match(/Tidak dinilai/g) || []).length,
            });
        })()`));

        periksa('dua tab dirender', awal.jumlahTab === 2, `dapat ${awal.jumlahTab}`);

        // Dua tab boleh, berbeda dari referensi yang tabnya dibongkar jadi
        // kartu: yang membatasi lebar judul terhadap wadahnya, bukan cacahnya.
        periksa('kedua tab terlihat tanpa menggulir', awal.tabTerlihat === 2, `terlihat ${awal.tabTerlihat}`);
        periksa('tidak ada gulir mendatar', awal.gulirMendatar === false);

        // 19 jenis pada tab parameter; tab status tersembunyi saat termuat.
        periksa('seluruh jenis dirender sebagai baris', awal.barisTampak === 19, `dapat ${awal.barisTampak}`);
        periksa('total bobot 37 ditampilkan', awal.adaTotalBobot === true);
        periksa('parameter Keamanan yang dahulu terlewat kini ada', awal.adaKeamanan === true);
        periksa('dua jenis Lainnya ditandai tidak dinilai', awal.tidakDinilai === 2, `dapat ${awal.tidakDinilai}`);

        // Tab kedua benar-benar berpindah, bukan sekadar ada di HTML.
        await nilai(`[...document.querySelectorAll('[role="tab"]')][1].click()`);
        await tidur(500);

        const tabStatus = JSON.parse(await nilai(`(() => {
            const baris = [...document.querySelectorAll('tbody tr')].filter((t) => t.getBoundingClientRect().height > 0);

            return JSON.stringify({
                barisStatus: baris.length,
                adaPenampung: document.body.textContent.includes('penampung sisa'),
            });
        })()`));

        periksa('tab status menampilkan tiga baris', tabStatus.barisStatus === 3, `dapat ${tabStatus.barisStatus}`);
        periksa('status terendah ditandai penampung sisa', tabStatus.adaPenampung === true);

        // Modal parameter: tingkat terkunci hanya pada parameter primer.
        await nilai(`[...document.querySelectorAll('[role="tab"]')][0].click()`);
        await tidur(400);

        // Baris pertama adalah Air, salah satu dari tiga parameter primer.
        await nilai(`[...document.querySelectorAll('tbody tr')][0].querySelector('button, a[href="#"]').click()`);
        await tidur(700);

        const modalPrimer = JSON.parse(await nilai(`(() => {
            const tingkat = document.querySelector('select[name="tingkat"]');
            const bobot = document.querySelector('input[name="bobot"]');

            return JSON.stringify({
                tingkatTerkunci: tingkat ? tingkat.disabled : null,
                bobotDapatDiubah: bobot ? ! bobot.disabled : null,
                adaJenisSebagaiIsian: !! document.querySelector('[name="jenis"]'),
            });
        })()`));

        periksa('tingkat parameter primer terkunci', modalPrimer.tingkatTerkunci === true, String(modalPrimer.tingkatTerkunci));
        periksa('bobotnya tetap dapat diubah', modalPrimer.bobotDapatDiubah === true);
        periksa('jenis tidak dapat diganti dari dalam form', modalPrimer.adaJenisSebagaiIsian === false);

        soket.close();
    } finally {
        proses.kill();
    }

    console.log(`\n  ${lulus} lulus, ${gagal} gagal.`);

    if (gagal > 0) {
        process.exitCode = 1;
    }
}

main();