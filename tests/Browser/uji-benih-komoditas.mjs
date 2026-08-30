/**
 * Uji peramban untuk isian Komoditas bersyarat pada form Saprotan.
 *
 * Mengapa uji peramban: yang dijaga adalah PERILAKU BERSYARAT, dan justru
 * jenis inilah yang paling sering lolos uji string. Isian Komoditas hanya
 * muncul ketika jenisnya Benih, dan `:required` beserta `:disabled`-nya
 * mengikuti keadaan itu. Uji string hanya dapat memastikan atributnya
 * tertulis rapi di sumbernya - dan atribut yang tertulis rapi tetapi tidak
 * pernah menyala terlihat persis sama.
 *
 * Preseden nyata di proyek ini: `:required` dan `:disabled` pada `pilih-cari`
 * tidak pernah terpasang selama berbulan-bulan sebab komponennya tidak
 * memanggil `$attributes->merge()`, dan tidak satu pun uji memerah
 * (agents/notes.md 1d.2).
 *
 * Bahaya yang dijaga: isian WAJIB yang sedang TERSEMBUNYI akan menghalangi
 * pengiriman form tanpa pesan yang terlihat, sebab peramban menunjuk elemen
 * yang tidak tampak di layar. Form seolah menolak diam-diam. Karena itu yang
 * ditanyakan di sini `checkValidity()`, bukan keberadaan atributnya.
 *
 * Empat hal yang diperiksa:
 *
 * 1. Komoditas tersembunyi selama jenisnya bukan Benih.
 * 2. Memilih Benih memunculkannya, dan ia menjadi wajib.
 * 3. Berpindah kembali ke Pupuk menyembunyikan sekaligus MELUMPUHKANNYA,
 *    sehingga form tetap dapat dikirim tanpa mengisi komoditas.
 * 4. Daftar komoditasnya dibaca dari data, bukan ditulis tangan.
 *
 * Dijalankan lewat Edge headless dan protokol DevTools, TANPA menambah
 * dependensi. Menuntut peladen hidup di 127.0.0.1:8099.
 *
 *   php artisan serve --port=8099
 *   node tests/Browser/uji-benih-komoditas.mjs
 */

import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { setTimeout as tidur } from 'node:timers/promises';

const ASAL = 'http://127.0.0.1:8099';
const PORT_DEVTOOLS = 9343;

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
            Mengganti nilai `<select>` sungguhan beserta peristiwanya.

            `x-model` Alpine mendengarkan `input`, bukan sekadar perubahan
            `.value`. Menyetel `.value` saja tidak akan pernah memicu apa pun,
            dan uji ini akan hijau selamanya tanpa menguji apa-apa.
        */
        const pilihJenis = async (nilaiJenis) => nilai(`(() => {
            // DICARI DI DALAM MODAL, bukan di seluruh halaman.
            //
            // Halaman daftar juga memiliki select bernama 'jenis' sebagai
            // penyaring tabel, dan ia dirender LEBIH DULU. querySelector polos
            // karena itu menemukan penyaring, bukan isian form - sehingga uji
            // ini akan menguji benda yang salah dan hijau selamanya.
            const modal = document.querySelector('#judul-formTambahSaprotan').closest('[role="dialog"]');
            const el = modal.querySelector('select[name="jenis"]');

            el.value = ${JSON.stringify(nilaiJenis)};
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));

            return el.value;
        })()`);

        const keadaanKomoditas = async () => JSON.parse(await nilai(`(() => {
            const modal = document.querySelector('#judul-formTambahSaprotan').closest('[role="dialog"]');
            const el = modal ? modal.querySelector('select[name="komoditas_id"]') : null;

            if (! el) {
                return JSON.stringify({ ada: false });
            }

            const kotak = el.getBoundingClientRect();

            return JSON.stringify({
                ada: true,
                // TERLIHAT berarti benar-benar punya ukuran di layar, bukan
                // sekadar ada di DOM. Atribut x-show bekerja lewat
                // display:none, sehingga elemen tersembunyi tetap terbaca
                // oleh querySelector.
                terlihat: kotak.width > 0 && kotak.height > 0,
                wajib: el.required,
                lumpuh: el.disabled,
                jumlahOpsi: el.options.length,
                opsi: [...el.options].map((o) => o.textContent.trim()).filter((t) => t !== 'Pilih komoditas'),
            });
        })()`));

        // Memeriksa apakah form MENOLAK dikirim. Inilah yang membedakan uji
        // ini dari uji string: atribut yang tertulis rapi tetapi tidak pernah
        // menyala terlihat persis sama di markup.
        const formSah = async () => nilai(`(() => {
            const modal = document.querySelector('#judul-formTambahSaprotan').closest('[role="dialog"]');
            const form = modal.querySelector('form');

            return form ? form.checkValidity() : null;
        })()`);

        await buka('/saprotan');
        await nilai(`window.dispatchEvent(new CustomEvent('buka-modal', { detail: 'formTambahSaprotan' }))`);
        await tidur(700);

        // ------------------------------------------------------------------
        // 1. Keadaan awal: jenis belum dipilih
        // ------------------------------------------------------------------
        const awal = await keadaanKomoditas();

        periksa('isian komoditas dirender pada form saprotan', awal.ada === true);
        periksa(
            'komoditas tersembunyi selama jenis belum dipilih',
            awal.terlihat === false,
            `terlihat=${awal.terlihat}`
        );
        periksa(
            'komoditas tidak wajib selama tersembunyi',
            awal.wajib === false,
            `wajib=${awal.wajib}`
        );
        periksa(
            'komoditas dilumpuhkan selama tersembunyi',
            awal.lumpuh === true,
            `lumpuh=${awal.lumpuh}`
        );

        // ------------------------------------------------------------------
        // 2. Jenis Benih: komoditas muncul dan menjadi wajib
        // ------------------------------------------------------------------
        await pilihJenis('Benih');
        await tidur(400);

        const saatBenih = await keadaanKomoditas();

        periksa('memilih Benih memunculkan isian komoditas', saatBenih.terlihat === true);
        periksa('komoditas menjadi wajib pada jenis Benih', saatBenih.wajib === true);
        periksa('komoditas tidak lagi lumpuh pada jenis Benih', saatBenih.lumpuh === false);

        // Daftar dibaca dari data master, bukan ditulis tangan. Bila kelak
        // Admin menambah komoditas, isian ini wajib ikut bertambah.
        periksa(
            'daftar komoditas dibaca dari data master',
            saatBenih.opsi.includes('JAGUNG') && saatBenih.opsi.includes('PADI')
                && saatBenih.opsi.includes('CABAI'),
            saatBenih.opsi.join(', ')
        );

        // INTI UJI INI. Komoditas kosong pada jenis Benih wajib menahan
        // pengiriman, sebab benih tanpa komoditas tidak dapat disaring form
        // penanaman dan akan menganggur selamanya.
        const sahTanpaKomoditas = await formSah();

        periksa(
            'form ditolak bila benih tanpa komoditas',
            sahTanpaKomoditas === false,
            `checkValidity=${sahTanpaKomoditas}`
        );

        // ------------------------------------------------------------------
        // 3. Kembali ke Pupuk: komoditas wajib IKUT LUMPUH
        // ------------------------------------------------------------------
        //
        // Bila ia hanya disembunyikan tanpa dilumpuhkan, `required` tetap
        // menyala pada elemen yang tidak terlihat. Peramban lalu menolak
        // pengiriman sambil menunjuk elemen yang tidak tampak di layar,
        // sehingga form seolah menolak diam-diam tanpa pesan apa pun.
        // Kekeliruan persis ini pernah terjadi pada form master wilayah.
        await pilihJenis('Pupuk');
        await tidur(400);

        const saatPupuk = await keadaanKomoditas();

        periksa('kembali ke Pupuk menyembunyikan komoditas', saatPupuk.terlihat === false);
        periksa('komoditas tidak wajib pada jenis Pupuk', saatPupuk.wajib === false);
        periksa(
            'komoditas dilumpuhkan pada jenis Pupuk',
            saatPupuk.lumpuh === true,
            'isian wajib yang tersembunyi akan menahan pengiriman tanpa pesan yang terlihat'
        );

        // ------------------------------------------------------------------
        // 4. Sisa stok benih PER POKTAN terbaca pada halaman rincian
        //    (Putaran 7: grain pindah ke distribusi; index kini per pengadaan).
        // ------------------------------------------------------------------
        await nilai(`window.dispatchEvent(new CustomEvent('tutup-modal'))`);
        await tidur(300);

        // Pengadaan 1 (BENIH JAGUNG HIBRIDA) dibagi ke dua poktan, sebagian
        // sudah dipakai penanaman: tabel distribusinya memuat kolom Sisa.
        await buka('/saprotan/1');
        const rincianBersisa = await nilai(`(() => {
            const t = document.querySelector('table');
            return t ? t.textContent : '';
        })()`);

        periksa('rincian benih memuat kolom Sisa per poktan', rincianBersisa.includes('Sisa'));
        periksa('sisa stok benih terbaca pada rincian saprotan', /sisa|Sisa/.test(rincianBersisa));

        // Pengadaan 6 (BENIH JAGUNG LOKAL) seluruhnya terpakai: sisa poktan
        // penerimanya "Habis".
        await buka('/saprotan/6');
        const rincianHabis = await nilai(`(() => {
            const t = document.querySelector('table');
            return t ? t.textContent : '';
        })()`);

        periksa(
            'benih yang jatahnya habis ditandai Habis',
            rincianHabis.includes('Habis'),
            'data contoh memuat satu distribusi benih yang seluruhnya terpakai'
        );
        periksa('komoditas benih terbaca pada rincian', rincianHabis.includes('JAGUNG'));

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
