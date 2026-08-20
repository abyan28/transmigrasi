/**
 * Uji peramban untuk komposisi luas lahan usaha.
 *
 * Mengapa uji peramban, bukan uji Pest: total luas lahan usaha adalah nilai
 * TURUNAN yang dihitung Alpine dari kedua bagiannya. Uji berbasis string hanya
 * dapat memastikan isiannya ada di HTML, bukan bahwa angkanya benar-benar
 * berubah saat petugas mengetik. Kekeliruan yang sama sudah tercatat dua kali
 * pada agents/notes.md bagian 1d.2 dan butir b799.
 *
 * Dijalankan lewat Edge headless dan protokol DevTools, TANPA menambah
 * dependensi. Menuntut peladen hidup di 127.0.0.1:8099.
 *
 *   php artisan serve --port=8099
 *   node tests/Browser/uji-komposisi-lahan.mjs
 */

import { spawn } from 'node:child_process';
import { setTimeout as tidur } from 'node:timers/promises';

const ASAL = 'http://127.0.0.1:8099';
const PORT_DEVTOOLS = 9333;

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

async function cariEdge() {
    const { existsSync } = await import('node:fs');

    for (const jalur of JALUR_EDGE) {
        if (existsSync(jalur)) {
            return jalur;
        }
    }

    throw new Error('Edge tidak ditemukan pada jalur yang dikenal.');
}

async function main() {
    const edge = await cariEdge();

    const proses = spawn(edge, [
        '--headless=new',
        `--remote-debugging-port=${PORT_DEVTOOLS}`,
        '--no-first-run',
        '--disable-gpu',
        'about:blank',
    ]);

    try {
        // Menunggu DevTools siap. Dijajal berulang, bukan tidur sekali,
        // sebab waktu siapnya berbeda antar-mesin.
        let daftar = null;

        for (let i = 0; i < 40; i += 1) {
            try {
                const balas = await fetch(`http://127.0.0.1:${PORT_DEVTOOLS}/json/list`);
                daftar = await balas.json();
                break;
            } catch {
                await tidur(250);
            }
        }

        if (! daftar) {
            throw new Error('DevTools tidak merespons.');
        }

        const sasaran = daftar.find((t) => t.type === 'page');
        const { default: WS } = await import('node:worker_threads')
            .then(() => ({ default: null }))
            .catch(() => ({ default: null }));

        // WebSocket bawaan Node 22+. Bila tidak ada, uji dilewati dengan
        // pesan yang jelas alih-alih memerah tanpa sebab.
        if (typeof WebSocket === 'undefined') {
            console.log('  LEWAT: WebSocket bawaan tidak tersedia pada Node ini.');
            return;
        }

        const soket = new WebSocket(sasaran.webSocketDebuggerUrl);
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
        await kirim('Page.navigate', { url: `${ASAL}/lahan` });

        // Menunggu Alpine benar-benar memulai, bukan sekadar HTML termuat.
        for (let i = 0; i < 60; i += 1) {
            const siap = await nilai('!! window.Alpine');

            if (siap) {
                break;
            }

            await tidur(250);
        }

        await tidur(500);

        // Membuka modal tambah lahan.
        await nilai(`window.dispatchEvent(new CustomEvent('buka-modal', { detail: 'formTambahLahan' }))`);
        await tidur(600);

        const adaKering = await nilai(`!! document.querySelector('#tambah_luas_kering')`);
        periksa('isian luas kering dirender', adaKering === true);

        const adaBasah = await nilai(`!! document.querySelector('#tambah_luas_basah')`);
        periksa('isian luas basah dirender', adaBasah === true);

        // Beralih ke lahan usaha agar bagian komposisi tampil.
        await nilai(`
            (() => {
                const s = document.querySelector('#tambah_peruntukan_lahan');
                s.value = 'Lahan Usaha';
                s.dispatchEvent(new Event('input', { bubbles: true }));
                s.dispatchEvent(new Event('change', { bubbles: true }));
                return s.value;
            })()
        `);
        await tidur(400);

        const komposisiTampil = await nilai(`
            document.querySelector('#tambah_luas_kering').getClientRects().length > 0
        `);
        periksa('komposisi tampil untuk lahan usaha', komposisiTampil === true);

        // Inti pengujian: total benar-benar dihitung, bukan diketik.
        await nilai(`
            (() => {
                const isi = (id, v) => {
                    const el = document.querySelector(id);
                    el.value = v;
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                };
                isi('#tambah_luas_kering', '1.25');
                isi('#tambah_luas_basah', '0.75');
            })()
        `);
        await tidur(400);

        const totalTampil = await nilai(`
            document.querySelector('[x-text="totalUsaha.toFixed(2)"]')?.textContent?.trim()
        `);
        periksa('total dihitung dari kedua bagian', totalTampil === '2.00', `terbaca "${totalTampil}"`);

        const totalTerkirim = await nilai(`
            document.querySelector('input[name="luas"].sr-only')?.value
        `);
        periksa('nilai terkirim mengikuti total', totalTerkirim === '2' || totalTerkirim === '2.00',
            `terbaca "${totalTerkirim}"`);

        // Mengubah satu bagian wajib mengubah totalnya.
        await nilai(`
            (() => {
                const el = document.querySelector('#tambah_luas_basah');
                el.value = '0.25';
                el.dispatchEvent(new Event('input', { bubbles: true }));
            })()
        `);
        await tidur(400);

        const totalBaru = await nilai(`
            document.querySelector('[x-text="totalUsaha.toFixed(2)"]')?.textContent?.trim()
        `);
        periksa('total ikut berubah saat bagian disunting', totalBaru === '1.50', `terbaca "${totalBaru}"`);

        // Lahan pekarangan: komposisi wajib hilang, luas diketik langsung.
        await nilai(`
            (() => {
                const s = document.querySelector('#tambah_peruntukan_lahan');
                s.value = 'Lahan Pekarangan';
                s.dispatchEvent(new Event('input', { bubbles: true }));
                s.dispatchEvent(new Event('change', { bubbles: true }));
            })()
        `);
        await tidur(400);

        const komposisiHilang = await nilai(`
            document.querySelector('#tambah_luas_kering').getClientRects().length === 0
        `);
        periksa('komposisi disembunyikan untuk pekarangan', komposisiHilang === true);

        const luasLangsung = await nilai(`
            document.querySelector('#tambah_luas')?.getClientRects().length > 0
        `);
        periksa('luas diketik langsung untuk pekarangan', luasLangsung === true);

        // Isian yang tersembunyi wajib nonaktif, jika tidak keduanya ikut
        // terkirim dan peladen menerima dua nilai luas yang bertentangan.
        const keringNonaktif = await nilai(`
            document.querySelector('#tambah_luas_kering')?.disabled === true
        `);
        periksa('komposisi nonaktif saat tersembunyi', keringNonaktif === true);

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
