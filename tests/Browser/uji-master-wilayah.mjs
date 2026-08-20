/**
 * Uji peramban untuk data master wilayah dan kawasan transmigrasi.
 *
 * Mengapa uji peramban: ketiga butir yang diperbaiki adalah PERILAKU, bukan
 * markup. Uji string tidak dapat memastikan daftar kabupaten benar-benar
 * tersaring mengikuti provinsi, tidak dapat menangkap isian tersembunyi yang
 * `required`-nya memblokir pengiriman tanpa pesan yang terlihat, dan tidak
 * dapat membedakan tab yang tampil dari yang hanya ada di HTML.
 *
 * Dijalankan lewat Edge headless dan protokol DevTools, TANPA menambah
 * dependensi. Menuntut peladen hidup di 127.0.0.1:8099.
 *
 *   php artisan serve --port=8099
 *   node tests/Browser/uji-master-wilayah.mjs
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

        // Modal wajib ditutup sebelum berpindah halaman: penguncian gulir milik
        // modal lama masih menempel saat halaman berikutnya dimuat.
        const tutupModal = async () => {
            await nilai(`window.dispatchEvent(new CustomEvent('tutup-modal'))`);
            await tidur(400);
        };

        const terlihat = (pemilih) => nilai(`
            (document.querySelector('${pemilih}')?.getClientRects().length ?? 0) > 0
        `);

        await kirim('Page.enable');
        await kirim('Runtime.enable');

        /* ---------------------------------------------------------------
         | Butir 1039: tab bawaan halaman master wilayah
         --------------------------------------------------------------- */

        console.log('\nTab bawaan master wilayah:');
        await buka('/wilayah');

        const tabAktif = await nilai(`
            [...document.querySelectorAll('button[role="tab"]')]
                .filter((b) => b.getAttribute('aria-selected') === 'true')
                .map((b) => b.textContent.trim())
        `);
        periksa(
            'tab bawaan adalah Provinsi, bukan Kecamatan',
            Array.isArray(tabAktif) && tabAktif.length === 1 && tabAktif[0].startsWith('Provinsi'),
            `terbaca "${JSON.stringify(tabAktif)}"`
        );

        // Panel bawaan wajib benar-benar tampil, bukan sekadar ada di HTML.
        const panelProvinsi = await nilai(`
            (() => {
                const panel = [...document.querySelectorAll('[role="tabpanel"]')]
                    .filter((p) => p.getClientRects().length > 0);
                return panel.length === 1 && panel[0].textContent.includes('Nusa Tenggara Timur');
            })()
        `);
        periksa('panel provinsi tampil, panel lain tersembunyi', panelProvinsi === true);

        /* ---------------------------------------------------------------
         | Butir 1040: tingkat bawaan mengikuti tab yang sedang dibuka
         --------------------------------------------------------------- */

        console.log('\nTingkat bawaan form tambah wilayah:');
        await nilai(`window.dispatchEvent(new CustomEvent('buka-modal', { detail: 'formTambahWilayah' }))`);
        await tidur(600);

        const tingkatBawaan = await nilai(`document.querySelector('#tambah_tingkat')?.value`);
        periksa('tingkat bawaan mengikuti tab provinsi', tingkatBawaan === 'provinsi', `terbaca "${tingkatBawaan}"`);

        // Provinsi tidak punya induk; ketiga isian induk wajib tersembunyi.
        periksa(
            'isian induk tersembunyi untuk tingkat provinsi',
            (await terlihat('#tambah_induk_provinsi')) === false
                && (await terlihat('#tambah_induk_kabupaten')) === false
                && (await terlihat('#tambah_induk_kecamatan')) === false
        );

        // INTI PERBAIKAN: dengan `required` tetap, ketiga isian induk yang
        // saling meniadakan itu dituntut terisi sekaligus dan form tidak pernah
        // dapat dikirim untuk tingkat apa pun.
        await nilai(`
            (() => {
                const el = document.querySelector('#tambah_nama_wilayah');
                el.value = 'Nusa Tenggara Barat';
                el.dispatchEvent(new Event('input', { bubbles: true }));
            })()
        `);
        await tidur(300);

        const sahProvinsi = await nilai(`
            document.querySelector('#tambah_nama_wilayah')?.closest('form')?.checkValidity()
        `);
        periksa('form dapat dikirim untuk tingkat provinsi', sahProvinsi === true);

        // Berpindah ke tingkat desa: induk kecamatan wajib muncul dan menuntut.
        await nilai(`
            (() => {
                const el = document.querySelector('#tambah_tingkat');
                el.value = 'desa';
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            })()
        `);
        await tidur(500);

        periksa(
            'isian induk kecamatan muncul untuk tingkat desa',
            (await terlihat('#tambah_induk_kecamatan')) === true
        );
        periksa(
            'isian induk lain tetap tersembunyi',
            (await terlihat('#tambah_induk_provinsi')) === false
                && (await terlihat('#tambah_induk_kabupaten')) === false
        );

        const sahDesaKosong = await nilai(`
            document.querySelector('#tambah_nama_wilayah')?.closest('form')?.checkValidity()
        `);
        periksa('form tertahan selama induk kecamatan kosong', sahDesaKosong === false);

        // Isian induk yang tidak berlaku wajib nonaktif, jika tidak ikut
        // terkirim dan peladen menerima dua induk yang bertentangan.
        periksa(
            'induk yang tidak berlaku dinonaktifkan',
            (await nilai(`document.querySelector('#tambah_induk_provinsi')?.disabled === true`)) === true
        );

        await tutupModal();

        /* ---------------------------------------------------------------
         | Butir 1040 lanjutan: tingkat mengikuti tab lain
         --------------------------------------------------------------- */

        await buka('/wilayah?tab=kecamatan');
        await nilai(`window.dispatchEvent(new CustomEvent('buka-modal', { detail: 'formTambahWilayah' }))`);
        await tidur(600);

        const tingkatKecamatan = await nilai(`document.querySelector('#tambah_tingkat')?.value`);
        periksa(
            'tingkat bawaan mengikuti tab kecamatan',
            tingkatKecamatan === 'kecamatan',
            `terbaca "${tingkatKecamatan}"`
        );
        periksa(
            'induk kabupaten langsung muncul',
            (await terlihat('#tambah_induk_kabupaten')) === true
        );

        await tutupModal();

        /* ---------------------------------------------------------------
         | Butir 1041: dropdown bertingkat pada form kawasan
         --------------------------------------------------------------- */

        console.log('\nDropdown bertingkat form kawasan:');
        await buka('/kawasan');
        await nilai(`window.dispatchEvent(new CustomEvent('buka-modal', { detail: 'formTambahKawasan' }))`);
        await tidur(600);

        periksa('isian provinsi dirender', (await terlihat('#tambah_provinsi_kawasan')) === true);
        periksa('isian kabupaten dirender', (await terlihat('#tambah_kabupaten_kawasan')) === true);

        // Selama provinsi belum dipilih, kabupaten wajib terkunci: dropdown
        // yang tampak dapat dibuka tetapi tidak menawarkan apa pun menyesatkan.
        const terkunci = await nilai(`
            document.querySelector('#tambah_kabupaten_kawasan')?.disabled === true
        `);
        periksa('kabupaten terkunci sebelum provinsi dipilih', terkunci === true);

        const teksAjakan = await nilai(`
            document.querySelector('#tambah_kabupaten_kawasan')?.options[0]?.textContent?.trim()
        `);
        periksa(
            'kabupaten mengajak memilih provinsi lebih dulu',
            teksAjakan === 'Pilih provinsi lebih dulu',
            `terbaca "${teksAjakan}"`
        );

        // INTI PERBAIKAN: daftar kabupaten benar-benar tersaring.
        await nilai(`
            (() => {
                const el = document.querySelector('#tambah_provinsi_kawasan');
                el.value = '1';
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            })()
        `);
        await tidur(500);

        const terbuka = await nilai(`
            document.querySelector('#tambah_kabupaten_kawasan')?.disabled === false
        `);
        periksa('kabupaten terbuka setelah provinsi dipilih', terbuka === true);

        const opsiKabupaten = await nilai(`
            [...document.querySelectorAll('#tambah_kabupaten_kawasan option')]
                .map((o) => o.textContent.trim())
                .filter((t) => t !== 'Pilih kabupaten' && t !== 'Pilih provinsi lebih dulu')
        `);
        periksa(
            'daftar kabupaten tersaring pada provinsi terpilih',
            Array.isArray(opsiKabupaten) && opsiKabupaten.includes('Malaka'),
            `terbaca "${JSON.stringify(opsiKabupaten)}"`
        );

        // Mengganti provinsi wajib melepas kabupaten yang tidak lagi berada di
        // dalamnya, jika tidak form terkirim membawa kabupaten dari provinsi
        // yang keliru tanpa terlihat di layar.
        await nilai(`
            (() => {
                const el = document.querySelector('#tambah_kabupaten_kawasan');
                el.value = '1';
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            })()
        `);
        await tidur(300);

        const sebelumGanti = await nilai(`document.querySelector('#tambah_kabupaten_kawasan')?.value`);
        periksa('kabupaten dapat dipilih', sebelumGanti === '1', `terbaca "${sebelumGanti}"`);

        await nilai(`
            (() => {
                const el = document.querySelector('#tambah_provinsi_kawasan');
                el.value = '';
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            })()
        `);
        await tidur(500);

        // DIBACA DARI STATE ALPINE, bukan dari `.value` DOM.
        //
        // Ketika opsi terpilih lenyap dari daftar, peramban mengosongkan
        // `.value` dengan sendirinya, sehingga membaca DOM SELALU menghasilkan
        // string kosong dan uji ini akan hijau meski pelepasannya dilumpuhkan.
        // Sementara itu `x-model` masih memegang id lama, dan nilai itulah yang
        // ikut terkirim begitu isian kembali punya opsi yang cocok.
        //
        // Terbukti lewat mutasi: melumpuhkan `gantiProvinsi()` tidak mengubah
        // `.value` sama sekali, tetapi menyisakan `kabupatenId` bernilai lama.
        const sesudahGanti = await nilai(`
            Alpine.$data(document.querySelector('#tambah_kabupaten_kawasan').closest('[x-data]')).kabupatenId
        `);
        periksa(
            'kabupaten dilepas saat provinsi berganti',
            sesudahGanti === '',
            `terbaca "${sesudahGanti}"`
        );

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
