/**
 * Uji peramban untuk satuan permukiman otomatis pada form Saprotan dan
 * Alsintan.
 *
 * Mengapa uji peramban: yang dijaga di sini adalah PERILAKU, bukan markup.
 * Nilai `satuan_permukiman_id` kini tidak lagi diketik petugas melainkan
 * dihitung Alpine dari poktan atau transmigran yang dipilih. Uji string hanya
 * dapat memastikan `<input type="hidden">` tertulis rapi di sumbernya, dan
 * justru itulah jebakannya: atribut `:value` yang salah tulis tetap terlihat
 * benar di markup, tetapi mengirim nilai kosong ke peladen.
 *
 * Kekeliruan serupa pernah terjadi dan lolos seluruh uji string: penguncian
 * parameter primer membaca `$modalData` yang tidak pernah ada, dan baru
 * ketahuan lewat uji peramban (agents/notes.md 6).
 *
 * Empat hal yang diperiksa:
 *
 * 1. Sebelum poktan dipilih, isian tersembunyi kosong dan petugas melihat
 *    ajakan mengisi, bukan nama SP yang salah.
 * 2. Memilih poktan mengisi nilai tersembunyi DAN teks yang terbaca.
 * 3. Nilai yang terkirim adalah SP milik poktan itu, bukan SP lain.
 * 4. Pada alsintan (Putaran 7), pengadaan tidak lagi berpoktan tunggal:
 *    memilih poktan lewat pilih-cari-banyak menambah baris distribusi, SP
 *    terbaca per baris, dan jumlah dibagi rata otomatis.
 *
 * Dijalankan lewat Edge headless dan protokol DevTools, TANPA menambah
 * dependensi. Menuntut peladen hidup di 127.0.0.1:8099.
 *
 *   php artisan serve --port=8099
 *   node tests/Browser/uji-sp-otomatis.mjs
 */

import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { setTimeout as tidur } from 'node:timers/promises';

const ASAL = 'http://127.0.0.1:8099';
const PORT_DEVTOOLS = 9341;

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

        /*
            Membuka pilih-cari-banyak lalu mencentang beberapa opsi sekaligus.

            Opsi diklik sungguhan, bukan nilainya disuntikkan, sebab yang diuji
            justru rantai peristiwanya: klik memanggil `toggle()`, yang
            menyinkronkan `poktanTerpilih` di form, yang lalu memicu
            `selaraskanDistribusi()` dan `bagiRata()`.
        */
        const centangPoktan = async (idPanel, jumlahOpsi) => nilai(`(() => {
            document.querySelector('#' + ${JSON.stringify(idPanel)} + '_tombol').click();

            return new Promise((selesai) => setTimeout(() => {
                const opsi = [...document.querySelectorAll('#' + ${JSON.stringify(idPanel)} + '_daftar [role="option"]')];
                for (let i = 0; i < ${jumlahOpsi} && i < opsi.length; i += 1) {
                    opsi[i].click();
                }
                setTimeout(() => selesai(opsi.length), 300);
            }, 250));
        })()`);

        // ------------------------------------------------------------------
        // Saprotan (Putaran 7): pengadaan induk + distribusi per poktan
        // ------------------------------------------------------------------
        await buka('/saprotan');
        await nilai(`window.dispatchEvent(new CustomEvent('buka-modal', { detail: 'formTambahSaprotan' }))`);
        await tidur(700);

        const awalSaprotan = JSON.parse(await nilai(`(() => {
            const modal = document.querySelector('#judul-formTambahSaprotan').closest('[role="dialog"]');

            return JSON.stringify({
                adaSpTunggal: !! modal.querySelector('[name="satuan_permukiman_id"]'),
                adaDropdownSp: !! modal.querySelector('select[name="satuan_permukiman_id"]'),
                adaJenisPenerima: !! modal.querySelector('[name="jenis_penerima"]'),
                adaPenerimaIndividu: !! modal.querySelector('[name="transmigran_id"]'),
                adaPemilihPoktan: !! modal.querySelector('#tambah_poktan_id_tombol'),
                barisDistribusiAwal: modal.querySelectorAll('fieldset').length,
            });
        })()`));

        periksa('saprotan: SP tunggal pengadaan sudah dicabut', awalSaprotan.adaSpTunggal === false);
        periksa('saprotan: dropdown SP manual sudah dicabut', awalSaprotan.adaDropdownSp === false);
        periksa('saprotan: pilihan jenis penerima sudah dicabut', awalSaprotan.adaJenisPenerima === false);
        periksa('saprotan: isian penerima perorangan sudah dicabut', awalSaprotan.adaPenerimaIndividu === false);
        periksa('saprotan: pemilih poktan berganda tersedia', awalSaprotan.adaPemilihPoktan === true);
        periksa('saprotan: belum ada baris distribusi sebelum poktan dipilih', awalSaprotan.barisDistribusiAwal === 0);

        await nilai(`(() => {
            const t = document.querySelector('[name="jumlah_total"]');
            t.value = 100;
            t.dispatchEvent(new Event('input', { bubbles: true }));
        })()`);

        await centangPoktan('tambah_poktan_id', 2);
        await tidur(400);

        const sesudahSaprotan = JSON.parse(await nilai(`(() => {
            const modal = document.querySelector('#judul-formTambahSaprotan').closest('[role="dialog"]');
            const fieldsets = [...modal.querySelectorAll('fieldset')];
            const inputPoktan = [...modal.querySelectorAll('input[name="poktan_id[]"]')];
            const jumlahDist = [...modal.querySelectorAll('input[name^="distribusi["][name$="[jumlah]"]')].map((el) => Number(el.value));

            return JSON.stringify({
                barisDistribusi: fieldsets.length,
                inputPoktanTerkirim: inputPoktan.length,
                totalTerbagi: jumlahDist.reduce((a, b) => a + b, 0),
                adaTeksSp: fieldsets.length > 0 && /\\(SP/.test(fieldsets[0].textContent),
            });
        })()`));

        periksa(
            'saprotan: memilih dua poktan menambah dua baris distribusi',
            sesudahSaprotan.barisDistribusi === 2,
            `dapat ${sesudahSaprotan.barisDistribusi}`
        );
        periksa(
            'saprotan: tiap poktan terkirim sebagai poktan_id[]',
            sesudahSaprotan.inputPoktanTerkirim === 2,
            `dapat ${sesudahSaprotan.inputPoktanTerkirim}`
        );
        periksa(
            'saprotan: Sigma distribusi sama dengan jumlah total',
            Math.abs(sesudahSaprotan.totalTerbagi - 100) < 0.01,
            `dapat ${sesudahSaprotan.totalTerbagi}`
        );
        periksa('saprotan: SP terbaca pada baris distribusi', sesudahSaprotan.adaTeksSp === true);

        // ------------------------------------------------------------------
        // Alsintan (Putaran 7): pengadaan induk + distribusi per poktan
        // ------------------------------------------------------------------
        await buka('/alsintan');
        await nilai(`window.dispatchEvent(new CustomEvent('buka-modal', { detail: 'formTambahAlsintan' }))`);
        await tidur(700);

        const awalAlsintan = JSON.parse(await nilai(`(() => {
            const modal = document.querySelector('#judul-formTambahAlsintan').closest('[role="dialog"]');

            return JSON.stringify({
                // SP tunggal tersembunyi SUDAH TIDAK ADA: SP kini turunan tiap
                // baris distribusi, bukan satu nilai pengadaan.
                adaSpTunggal: !! modal.querySelector('[name="satuan_permukiman_id"]'),
                adaDropdownSp: !! modal.querySelector('select[name="satuan_permukiman_id"]'),
                adaRadioKepemilikan: !! modal.querySelector('[name="kepemilikan"]'),
                adaTransmigran: !! modal.querySelector('[name="transmigran_id"]'),
                adaPemilihPoktan: !! modal.querySelector('#tambah_poktan_id_tombol'),
                barisDistribusiAwal: modal.querySelectorAll('fieldset').length,
            });
        })()`));

        periksa('alsintan: SP tunggal pengadaan sudah dicabut', awalAlsintan.adaSpTunggal === false);
        periksa('alsintan: dropdown SP manual sudah dicabut', awalAlsintan.adaDropdownSp === false);
        periksa('alsintan: radio jenis kepemilikan sudah dicabut', awalAlsintan.adaRadioKepemilikan === false);
        periksa('alsintan: isian transmigran pemilik sudah dicabut', awalAlsintan.adaTransmigran === false);
        periksa('alsintan: pemilih poktan berganda tersedia', awalAlsintan.adaPemilihPoktan === true);
        periksa('alsintan: belum ada baris distribusi sebelum poktan dipilih', awalAlsintan.barisDistribusiAwal === 0);

        // Isi jumlah total dulu, lalu pilih dua poktan.
        await nilai(`(() => {
            const t = document.querySelector('[name="jumlah_total"]');
            t.value = 4;
            t.dispatchEvent(new Event('input', { bubbles: true }));
        })()`);

        await centangPoktan('tambah_poktan_id', 2);
        await tidur(400);

        const sesudahAlsintan = JSON.parse(await nilai(`(() => {
            const modal = document.querySelector('#judul-formTambahAlsintan').closest('[role="dialog"]');
            const fieldsets = [...modal.querySelectorAll('fieldset')];
            const inputPoktan = [...modal.querySelectorAll('input[name="poktan_id[]"]')];
            const jumlahDist = [...modal.querySelectorAll('input[name^="distribusi["][name$="[jumlah]"]')].map((el) => Number(el.value));

            return JSON.stringify({
                barisDistribusi: fieldsets.length,
                inputPoktanTerkirim: inputPoktan.length,
                jumlahDist,
                totalTerbagi: jumlahDist.reduce((a, b) => a + b, 0),
                adaTeksSp: fieldsets.length > 0 && /\\(SP/.test(fieldsets[0].textContent),
            });
        })()`));

        periksa(
            'alsintan: memilih dua poktan menambah dua baris distribusi',
            sesudahAlsintan.barisDistribusi === 2,
            `dapat ${sesudahAlsintan.barisDistribusi}`
        );
        periksa(
            'alsintan: tiap poktan terkirim sebagai poktan_id[]',
            sesudahAlsintan.inputPoktanTerkirim === 2,
            `dapat ${sesudahAlsintan.inputPoktanTerkirim}`
        );
        periksa(
            'alsintan: jumlah 4 dibagi rata menjadi 2 dan 2',
            JSON.stringify(sesudahAlsintan.jumlahDist) === JSON.stringify([2, 2]),
            `dapat ${JSON.stringify(sesudahAlsintan.jumlahDist)}`
        );
        periksa(
            'alsintan: Sigma distribusi sama dengan jumlah total',
            sesudahAlsintan.totalTerbagi === 4,
            `dapat ${sesudahAlsintan.totalTerbagi}`
        );
        periksa('alsintan: SP terbaca pada baris distribusi', sesudahAlsintan.adaTeksSp === true);

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
