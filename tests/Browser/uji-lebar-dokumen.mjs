/**
 * Uji peramban untuk lebar dokumen laporan.
 *
 * Mengapa uji peramban: yang dikeluhkan pemilik proyek murni geometri.
 * "Kolomnya banyak sampai harus pakai slider" hanya dapat dibuktikan dengan
 * membandingkan `scrollWidth` terhadap `clientWidth` sesudah peramban
 * menghitung tata letaknya. Uji string sama sekali tidak dapat mengetahuinya:
 * kelas `max-w-[1160px]` dan `overflow-x-auto` tetap tertulis benar meski
 * tabelnya meluber.
 *
 * `rules.md` 876 poin 10 mewajibkan uji peramban untuk hal yang bergantung
 * tata letak.
 *
 * Yang diperiksa pada tiap rute dokumen:
 *   1. Kertasnya membawa kelas orientasi yang benar.
 *   2. Tabel terlebar MUAT di dalam kertas, tanpa perlu digulir mendatar.
 *   3. Halaman itu sendiri tidak menggulir mendatar.
 *   4. Garis pemisah kolom benar-benar terlukis, bukan sekadar tertulis
 *      di CSS.
 *
 * Ukuran layar 1440x900 dipilih sebagai laptop kerja yang wajar di dinas,
 * bukan monitor lebar. Bila muat di sini, ia muat di tempat yang lebih besar.
 *
 * Dijalankan lewat Edge headless dan protokol DevTools, TANPA menambah
 * dependensi. Menuntut peladen hidup di 127.0.0.1:8099.
 *
 *   php artisan serve --port=8099
 *   node tests/Browser/uji-lebar-dokumen.mjs
 */

import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { setTimeout as tidur } from 'node:timers/promises';

const ASAL = 'http://127.0.0.1:8099';
const PORT_DEVTOOLS = 9349;

const LEBAR_LAYAR = 1440;
const TINGGI_LAYAR = 900;

// Slug beserta orientasi yang seharusnya, diturunkan dari jumlah kolom di
// LaporanData::meta(). Daftar ini wajib sejalan dengan angka `kolom` di sana;
// uji Pest "menurunkan orientasi kertas dari jumlah kolom yang sebenarnya"
// menjaga sisi PHP-nya.
const LAPORAN = [
    ['hasil-panen', 'landscape'],
    ['monografi-sp', 'landscape'],
    ['alsintan', 'landscape'],
    ['saprotan', 'landscape'],
    ['indikator-kawasan', 'portrait'],
    ['poktan', 'landscape'],
    ['transmigran', 'landscape'],
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

        const buka = async (jalur) => {
            await kirim('Page.navigate', { url: `${ASAL}${jalur}` });

            for (let i = 0; i < 60; i += 1) {
                if (await nilai('document.readyState === "complete"')) {
                    break;
                }

                await tidur(250);
            }

            await tidur(500);
        };

        for (const [slug, orientasi] of LAPORAN) {
            console.log(`\n/laporan/${slug}/dokumen (${orientasi}):`);
            await buka(`/laporan/${slug}/dokumen`);

            const kertas = await nilai(`
                (() => {
                    const k = document.querySelector('.kertas-dokumen');
                    if (! k) return null;

                    // Wadah bergulir tiap tabel: itulah yang memunculkan slider.
                    const wadah = [...k.querySelectorAll('.overflow-x-auto')].map((w) => ({
                        gulir: w.scrollWidth,
                        tampak: w.clientWidth,
                    }));

                    return {
                        landscape: k.classList.contains('dokumen-landscape'),
                        portrait: k.classList.contains('dokumen-portrait'),
                        lebar: Math.round(k.getBoundingClientRect().width),
                        wadah,
                        // Halaman itu sendiri tidak boleh menggulir mendatar.
                        halamanMeluber: document.documentElement.scrollWidth > document.documentElement.clientWidth,
                        // Garis kolom benar-benar terlukis, bukan hanya di CSS.
                        garisSel: (() => {
                            const sel = k.querySelector('.tabel-dokumen td');
                            if (! sel) return null;
                            const g = getComputedStyle(sel);
                            return { kiri: g.borderLeftWidth, atas: g.borderTopWidth };
                        })(),
                    };
                })()
            `);

            if (! kertas) {
                periksa('kertas dokumen ditemukan', false, 'elemen .kertas-dokumen tidak ada');

                continue;
            }

            periksa(
                `orientasi ${orientasi}`,
                orientasi === 'landscape' ? kertas.landscape : kertas.portrait,
                `landscape=${kertas.landscape} portrait=${kertas.portrait}`,
            );

            periksa('halaman tidak menggulir mendatar', kertas.halamanMeluber === false);

            periksa(
                'garis pemisah kolom terlukis',
                kertas.garisSel !== null
                    && kertas.garisSel.kiri !== '0px'
                    && kertas.garisSel.atas !== '0px',
                JSON.stringify(kertas.garisSel),
            );

            // Inti keluhannya: tabel harus muat tanpa slider.
            const meluber = kertas.wadah.filter((w) => w.gulir > w.tampak + 1);

            periksa(
                `${kertas.wadah.length} tabel muat tanpa gulir mendatar`,
                meluber.length === 0,
                meluber.map((w) => `${w.gulir}>${w.tampak}`).join(', ') + ` (kertas ${kertas.lebar}px)`,
            );
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
