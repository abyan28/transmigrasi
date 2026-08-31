/**
 * Uji peramban untuk auto-fill Satuan Permukiman pada Form Lahan dan Form Rumah.
 *
 * 1. Form Lahan: Memilih Pemilik (transmigran) otomatis mengisi satuan_permukiman_id.
 * 2. Form Rumah: Memilih Penghuni (transmigran) saat status Dihuni otomatis mengisi satuan_permukiman_id.
 * 3. Form Rumah: Mengubah status ke Tidak Dihuni menonaktifkan pemilih penghuni dan mengizinkan pemilihan SP secara manual.
 *
 * Dijalankan lewat Edge headless dan protokol DevTools.
 *   php artisan serve --port=8099
 *   node tests/Browser/uji-autofill-sp.mjs
 */

import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { setTimeout as tidur } from 'node:timers/promises';

const ASAL = 'http://127.0.0.1:8099';
const PORT_DEVTOOLS = 9366;

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

    const edge = cariEdge();
    const proses = spawn(edge, [
        '--headless=new',
        `--remote-debugging-port=${PORT_DEVTOOLS}`,
        '--no-first-run',
        '--disable-gpu',
        '--window-size=1280,900',
        'about:blank',
    ]);

    try {
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

        if (!daftar) {
            throw new Error('DevTools tidak merespons.');
        }

        const sasaran = daftar.find((t) => t.type === 'page');
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

        const kirim = (method, params = {}) =>
            new Promise((selesai) => {
                nomor += 1;
                menunggu.set(nomor, selesai);
                soket.send(JSON.stringify({ id: nomor, method, params }));
            });

        const nilai = async (ekspresi) => {
            const hasil = await kirim('Runtime.evaluate', {
                expression: ekspresi,
                returnByValue: true,
                awaitPromise: true,
            });
            return hasil.result?.value;
        };

        await kirim('Runtime.enable');

        // ==========================================
        // 1. UJI FORM LAHAN
        // ==========================================
        console.log('Uji Auto-Fill Form Lahan:');
        await kirim('Page.navigate', { url: `${ASAL}/lahan` });

        for (let i = 0; i < 60; i += 1) {
            const siap = await nilai('!! window.Alpine');
            if (siap) break;
            await tidur(250);
        }
        await tidur(500);

        // Buka modal tambah lahan
        await nilai(`window.dispatchEvent(new CustomEvent('buka-modal', { detail: 'formTambahLahan' }))`);
        await tidur(600);

        // Periksa urutan: field transmigran_id muncul sebelum satuan_permukiman_id di DOM
        const urutanLahan = await nilai(`
            (() => {
                const form = document.querySelector('form[action*="lahan"]:not([method="GET"])');
                const pemilik = form.querySelector('[name="transmigran_id"]');
                const sp = form.querySelector('[name="satuan_permukiman_id"]');
                return pemilik && sp && (pemilik.compareDocumentPosition(sp) & Node.DOCUMENT_POSITION_FOLLOWING) !== 0;
            })()
        `);
        periksa('field Pemilik tampil sebelum Satuan Permukiman pada Form Lahan', urutanLahan === true);

        // Ubah nilai Pemilik ke ID 1 (YOHANES BERE -> SP 1 Kapitan Meo)
        await nilai(`
            (() => {
                const form = document.querySelector('form[action*="lahan"]:not([method="GET"])');
                const input = form.querySelector('[name="transmigran_id"]');
                input.value = '1';
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            })()
        `);
        await tidur(300);

        const spLahanTerisi = await nilai(`
            (() => {
                const form = document.querySelector('form[action*="lahan"]:not([method="GET"])');
                const sp = form.querySelector('[name="satuan_permukiman_id"]');
                return sp ? sp.value : '';
            })()
        `);
        periksa('pilihan Pemilik otomatis mengisi Satuan Permukiman (SP 1)', String(spLahanTerisi) === '1');

        // ==========================================
        // 2. UJI FORM RUMAH
        // ==========================================
        console.log('\nUji Auto-Fill Form Rumah:');
        await kirim('Page.navigate', { url: `${ASAL}/rumah` });

        for (let i = 0; i < 60; i += 1) {
            const siap = await nilai('!! window.Alpine');
            if (siap) break;
            await tidur(250);
        }
        await tidur(500);

        // Buka modal tambah rumah
        await nilai(`window.dispatchEvent(new CustomEvent('buka-modal', { detail: 'formTambahRumah' }))`);
        await tidur(600);

        // Periksa urutan: field status_hunian & transmigran_id muncul sebelum no_rumah
        const urutanRumah = await nilai(`
            (() => {
                const form = document.querySelector('form[action*="rumah"]:not([method="GET"])');
                const penghuni = form.querySelector('[name="transmigran_id"]');
                const noRumah = form.querySelector('[name="no_rumah"]');
                return penghuni && noRumah && (penghuni.compareDocumentPosition(noRumah) & Node.DOCUMENT_POSITION_FOLLOWING) !== 0;
            })()
        `);
        periksa('section Penghunian tampil sebelum Spesifikasi Bangunan pada Form Rumah', urutanRumah === true);

        // Pilih transmigran calon penghuni ID 1
        await nilai(`
            (() => {
                const form = document.querySelector('form[action*="rumah"]:not([method="GET"])');
                const input = form.querySelector('[name="transmigran_id"]');
                input.value = '1';
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            })()
        `);
        await tidur(300);

        const spRumahTerisi = await nilai(`
            (() => {
                const form = document.querySelector('form[action*="rumah"]:not([method="GET"])');
                const sp = form.querySelector('[name="satuan_permukiman_id"]');
                return sp ? sp.value : '';
            })()
        `);
        periksa('pilihan Penghuni otomatis mengisi Satuan Permukiman pada Form Rumah (SP 1)', String(spRumahTerisi) === '1');

        // Ubah status ke Tidak Dihuni
        await nilai(`
            (() => {
                const form = document.querySelector('form[action*="rumah"]:not([method="GET"])');
                const status = form.querySelector('#tambah_status_hunian');
                status.value = 'Tidak Dihuni';
                status.dispatchEvent(new Event('input', { bubbles: true }));
                status.dispatchEvent(new Event('change', { bubbles: true }));
            })()
        `);
        await tidur(400);

        const tombolDisabled = await nilai(`
            (() => {
                const tombol = document.querySelector('#tambah_transmigran_id_tombol');
                return tombol ? (tombol.disabled || tombol.hasAttribute('disabled')) : false;
            })()
        `);
        periksa('status Tidak Dihuni menonaktifkan tombol pemilih Penghuni', tombolDisabled === true);

        soket.close();
    } finally {
        proses.kill();
    }

    console.log(`\n${lulus} lulus, ${gagal} gagal.`);
    if (gagal > 0) {
        process.exit(1);
    }
}

main().catch((err) => {
    console.error(err);
    process.exit(1);
});
