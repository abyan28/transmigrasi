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
 * 4. Pada alsintan, SP terbaca dari poktan pemilik, dan isian kepemilikan
 *    pribadi benar-benar sudah dicabut.
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
            Membuka modal lalu memilih satu opsi pada `pilih-cari`.

            Opsi diklik sungguhan, bukan nilainya disuntikkan, sebab yang diuji
            justru rantai peristiwanya: klik memanggil `pilih()`, yang
            memancarkan `change` pada isian tersembunyi, yang lalu ditangkap
            `@change` milik pemanggil. Menyuntikkan nilai langsung akan
            melewati seluruh rantai itu dan uji ini kehilangan gunanya.
        */
        const pilihOpsi = async (namaIsian, urutan) => nilai(`(() => {
            const nilaiEl = document.querySelector('[name="${namaIsian}"]');
            const wadah = nilaiEl.closest('[x-data]');
            const tombol = wadah.querySelector('button[type="button"]');

            tombol.click();

            return new Promise((selesai) => setTimeout(() => {
                const opsi = [...wadah.querySelectorAll('[role="option"], li button, ul button')];
                const dipilih = opsi[${urutan}];

                if (! dipilih) {
                    selesai('opsi tidak ditemukan, ada ' + opsi.length);

                    return;
                }

                dipilih.click();
                setTimeout(() => selesai('ok'), 250);
            }, 250));
        })()`);

        // ------------------------------------------------------------------
        // Saprotan
        // ------------------------------------------------------------------
        await buka('/saprotan');
        await nilai(`window.dispatchEvent(new CustomEvent('buka-modal', { detail: 'formTambahSaprotan' }))`);
        await tidur(700);

        const awalSaprotan = JSON.parse(await nilai(`(() => {
            const sp = document.querySelector('[name="satuan_permukiman_id"]');

            return JSON.stringify({
                adaIsianTersembunyi: !! sp && sp.type === 'hidden',
                // Dropdown SP lama WAJIB sudah tidak ada: selama ia masih
                // dirender, petugas tetap dapat memilih SP yang berbeda dari
                // poktannya dan seluruh perubahan ini tidak ada gunanya.
                adaDropdownSp: !! document.querySelector('select[name="satuan_permukiman_id"]'),
                adaJenisPenerima: !! document.querySelector('[name="jenis_penerima"]'),
                adaPenerimaIndividu: !! document.querySelector('[name="transmigran_id"]'),
                nilaiAwal: sp ? sp.value : null,
            });
        })()`));

        periksa('saprotan: SP dikirim lewat isian tersembunyi', awalSaprotan.adaIsianTersembunyi === true);
        periksa('saprotan: dropdown SP manual sudah dicabut', awalSaprotan.adaDropdownSp === false);
        periksa('saprotan: pilihan jenis penerima sudah dicabut', awalSaprotan.adaJenisPenerima === false);
        periksa('saprotan: isian penerima perorangan sudah dicabut', awalSaprotan.adaPenerimaIndividu === false);
        periksa(
            'saprotan: SP kosong selama poktan belum dipilih',
            awalSaprotan.nilaiAwal === '',
            `dapat "${awalSaprotan.nilaiAwal}"`
        );

        const hasilPilih = await pilihOpsi('poktan_id', 0);
        periksa('saprotan: opsi poktan dapat diklik', hasilPilih === 'ok', String(hasilPilih));

        const sesudahSaprotan = JSON.parse(await nilai(`(() => {
            const sp = document.querySelector('[name="satuan_permukiman_id"]');
            const poktan = document.querySelector('[name="poktan_id"]');

            return JSON.stringify({
                poktanTerpilih: poktan.value,
                spTerkirim: sp.value,
                teksTerbaca: sp.parentElement.innerText.trim(),
            });
        })()`));

        periksa(
            'saprotan: memilih poktan mengisi SP tersembunyi',
            sesudahSaprotan.spTerkirim !== '',
            `poktan ${sesudahSaprotan.poktanTerpilih}, sp "${sesudahSaprotan.spTerkirim}"`
        );

        // POKTAN MEKAR JAYA berada di SP Kapitan Meo, yaitu id 1. Nilai inilah
        // yang menjadikan uji ini bermakna: isian yang terisi sembarang angka
        // sama buruknya dengan isian yang kosong.
        periksa(
            'saprotan: SP yang terkirim milik poktan yang dipilih',
            sesudahSaprotan.spTerkirim === '1',
            `dapat "${sesudahSaprotan.spTerkirim}"`
        );

        periksa(
            'saprotan: nama SP terbaca petugas',
            sesudahSaprotan.teksTerbaca.includes('Kapitan Meo'),
            sesudahSaprotan.teksTerbaca
        );

        // ------------------------------------------------------------------
        // Alsintan: sumber SP berpindah mengikuti jenis kepemilikan
        // ------------------------------------------------------------------
        await buka('/alsintan');
        await nilai(`window.dispatchEvent(new CustomEvent('buka-modal', { detail: 'formTambahAlsintan' }))`);
        await tidur(700);

        const awalAlsintan = JSON.parse(await nilai(`(() => {
            const sp = document.querySelector('[name="satuan_permukiman_id"]');

            return JSON.stringify({
                adaIsianTersembunyi: !! sp && sp.type === 'hidden',
                adaDropdownSp: !! document.querySelector('select[name="satuan_permukiman_id"]'),
                nilaiAwal: sp ? sp.value : null,
            });
        })()`));

        periksa('alsintan: SP dikirim lewat isian tersembunyi', awalAlsintan.adaIsianTersembunyi === true);
        periksa('alsintan: dropdown SP manual sudah dicabut', awalAlsintan.adaDropdownSp === false);
        periksa(
            'alsintan: SP kosong selama pemilik belum dipilih',
            awalAlsintan.nilaiAwal === '',
            `dapat "${awalAlsintan.nilaiAwal}"`
        );

        await pilihOpsi('poktan_id', 0);

        const alsintanPoktan = await nilai(`document.querySelector('[name="satuan_permukiman_id"]').value`);

        periksa(
            'alsintan: SP terisi dari poktan pemilik',
            alsintanPoktan === '1',
            `dapat "${alsintanPoktan}"`
        );
        /*
            Kepemilikan pribadi DICABUT 2026-08-22.

            Blok ini dahulu berpindah ke kepemilikan pribadi lalu memastikan
            sumber SP ikut berpindah ke transmigran. Kini yang dijaga
            kebalikannya: radio pilihan dan isian transmigran pemilik benar-
            benar tidak ada lagi, sebab seluruh menu Pertanian mencatat
            kelompok, bukan individu.
        */
        const sisaPribadi = JSON.parse(await nilai(`(() => {
            const modal = document.querySelector('#judul-formTambahAlsintan').closest('[role="dialog"]');

            return JSON.stringify({
                adaRadioKepemilikan: !! modal.querySelector('[name="kepemilikan"]'),
                adaTransmigran: !! modal.querySelector('[name="transmigran_id"]'),
                adaPoktan: !! modal.querySelector('[name="poktan_id"]'),
            });
        })()`));

        periksa(
            'alsintan: radio jenis kepemilikan sudah dicabut',
            sisaPribadi.adaRadioKepemilikan === false,
            'pemilik alsintan kini selalu kelompok tani'
        );

        periksa(
            'alsintan: isian transmigran pemilik sudah dicabut',
            sisaPribadi.adaTransmigran === false,
            'alat pribadi dahulu yatim navigasi: tidak muncul di rincian poktan maupun transmigran'
        );

        periksa('alsintan: isian kelompok tani tetap ada', sisaPribadi.adaPoktan === true);

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
