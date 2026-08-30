/**
 * Uji peramban untuk lebar badan halaman detail.
 *
 * Mengapa uji peramban: keluhan pemilik proyek murni geometri. Di
 * `/transmigran/1?tab=keluarga` muncul scrollbar mendatar pada BADAN HALAMAN
 * (bukan wadah gulir tabel di dalam tab), sehingga seluruh halaman tergeser
 * ke kanan dan kolom ringkasan tertutup sidebar. Uji string tidak dapat
 * membuktikannya: kelas `min-w-0`, `overflow-hidden`, dan
 * `grid-cols-[20rem_minmax(0,1fr)]` tetap tertulis benar meski tata letaknya
 * meluber. Hanya `documentElement.scrollWidth` sesudah peramban menghitung
 * tata letak yang dapat menjawabnya.
 *
 * Akar masalah: halaman detail menyimpang dari cangkang dua kolom baku
 * (lihat `pages/poktan/detail.blade.php`) — trek grid `1fr` polos
 * (minimum otomatisnya `min-content`) plus kartu tab tanpa
 * `min-w-0 overflow-hidden`. Tabel tab terlebar lalu melebarkan grid
 * melewati wadah `.mx-auto max-w-(--breakpoint-2xl)`.
 *
 * `rules.md` 876 poin 10 mewajibkan uji peramban untuk hal yang bergantung
 * tata letak.
 *
 * Yang diperiksa pada tiap rute, di dua lebar layar:
 *   1. Badan halaman TIDAK menggulir mendatar
 *      (documentElement.scrollWidth <= clientWidth).
 *   2. Kartu tab tidak sendiri yang meluber (scrollWidth-nya <= clientWidth).
 *   3. Wadah `.overflow-x-auto` pembungkus tabel BOLEH menggulir — itu memang
 *      tugasnya — jadi tidak diadukan.
 *
 * Dijalankan lewat Edge headless dan protokol DevTools, TANPA menambah
 * dependensi. Menuntut peladen hidup di 127.0.0.1:8099.
 *
 *   php artisan serve --port=8099
 *   node tests/Browser/uji-lebar-halaman.mjs
 */

import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { setTimeout as tidur } from 'node:timers/promises';

const ASAL = 'http://127.0.0.1:8099';
const PORT_DEVTOOLS = 9351;

// Laptop kerja dinas yang wajar, lalu monitor lebar tempat bug ini terlihat
// pada tangkapan layar pemilik proyek.
const LEBAR_LAYAR = [1440, 1920];
const TINGGI_LAYAR = 900;

// Rute halaman detail yang memakai cangkang dua kolom + tab. Tab keluarga
// disebut eksplisit sebab tabelnya paling lebar (11 kolom).
const RUTE = [
    '/transmigran/1?tab=keluarga',
    '/transmigran/1?tab=biodata',
    '/poktan/1',
    '/alsintan/1',
    '/saprotan/1',
    '/lahan/1',
    '/infrastruktur/1',
    '/sp/inventaris/1',
    '/sp/fasilitas/1',
];

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
        `--window-size=${LEBAR_LAYAR[LEBAR_LAYAR.length - 1]},${TINGGI_LAYAR}`,
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

        const buka = async (jalur) => {
            await kirim('Page.navigate', { url: `${ASAL}${jalur}` });

            for (let i = 0; i < 60; i += 1) {
                if (await nilai('document.readyState === "complete"')) {
                    break;
                }

                await tidur(250);
            }

            // Beri Alpine waktu memasang tab dan menghitung ulang tata letak.
            await tidur(500);
        };

        for (const lebar of LEBAR_LAYAR) {
            await kirim('Emulation.setDeviceMetricsOverride', {
                width: lebar,
                height: TINGGI_LAYAR,
                deviceScaleFactor: 1,
                mobile: false,
            });

            for (const jalur of RUTE) {
                console.log(`\n${jalur}  @ ${lebar}px:`);
                await buka(jalur);

                const ukur = await nilai(`
                    (() => {
                        const doc = document.documentElement;
                        const kartu = document.querySelector('[x-data^="hashTabs"] > div');

                        return {
                            adaHalaman: true,
                            halamanGulir: doc.scrollWidth,
                            halamanTampak: doc.clientWidth,
                            kartuGulir: kartu ? kartu.scrollWidth : null,
                            kartuTampak: kartu ? kartu.clientWidth : null,
                        };
                    })()
                `);

                if (! ukur) {
                    periksa(`${jalur} @ ${lebar}`, false, 'gagal mengukur halaman');

                    continue;
                }

                periksa(
                    'badan halaman tidak menggulir mendatar',
                    ukur.halamanGulir <= ukur.halamanTampak + 1,
                    `scrollWidth ${ukur.halamanGulir} > clientWidth ${ukur.halamanTampak}`,
                );

                if (ukur.kartuGulir !== null) {
                    periksa(
                        'kartu tab tidak meluber',
                        ukur.kartuGulir <= ukur.kartuTampak + 1,
                        `kartu scrollWidth ${ukur.kartuGulir} > clientWidth ${ukur.kartuTampak}`,
                    );
                }
            }
        }

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
