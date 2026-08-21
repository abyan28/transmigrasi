/**
 * Uji peramban untuk halaman indeks data master referensi.
 *
 * Mengapa uji peramban: alasan perubahan ini adalah ANGKA YANG DIUKUR di
 * peramban, bukan markup. Dengan empat belas tab, bar tab mencapai 2309px
 * pada ruang 705px sehingga hanya empat tab terlihat dan sepuluh sisanya
 * tersembunyi di balik gulir mendatar. Uji string tidak dapat melihat itu:
 * keempat belas tab tetap ada di HTML, hanya tidak terlihat mata. Karena
 * angka itu yang menjadi alasan, angka itu pula yang dijaga.
 *
 * Dijalankan lewat Edge headless dan protokol DevTools, TANPA menambah
 * dependensi. Menuntut peladen hidup di 127.0.0.1:8099.
 *
 *   php artisan serve --port=8099
 *   node tests/Browser/uji-master-referensi.mjs
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

        // Indeks: seluruh daftar wajib terlihat sekaligus.
        await buka('/master/referensi');

        const indeks = JSON.parse(await nilai(`(() => {
            const kartu = [...document.querySelectorAll('a[href*="/master/referensi/"]')];
            // Terlihat berarti BENAR-BENAR DI DALAM LAYAR, bukan sekadar
            // punya ukuran. Elemen yang terdorong ke luar batas mendatar tetap
            // melaporkan lebar dan tinggi seperti biasa, dan justru itulah
            // keadaan yang membuat sepuluh tab dahulu tidak terlihat.
            const terlihat = kartu.filter((k) => {
                const r = k.getBoundingClientRect();

                return r.width > 0
                    && r.height > 0
                    && r.left >= 0
                    && r.right <= document.documentElement.clientWidth;
            });

            return JSON.stringify({
                jumlahKartu: kartu.length,
                kartuTerlihat: terlihat.length,
                adaTablist: !! document.querySelector('[role="tablist"]'),
                gulirMendatar: document.documentElement.scrollWidth > document.documentElement.clientWidth,
                kelompok: [...document.querySelectorAll('section h2')].map((h) => h.textContent.trim()),
            });
        })()`));

        periksa('empat belas daftar dirender sebagai kartu', indeks.jumlahKartu === 14, `dapat ${indeks.jumlahKartu}`);

        // INTI UJI INI. Sebelumnya empat dari empat belas; sekarang seluruhnya.
        periksa(
            'seluruh kartu terlihat tanpa perlu menggulir',
            indeks.kartuTerlihat === 14,
            `terlihat ${indeks.kartuTerlihat} dari ${indeks.jumlahKartu}`
        );

        periksa('tidak ada gulir mendatar pada halaman', indeks.gulirMendatar === false);
        periksa('tab lama sudah tidak dipakai', indeks.adaTablist === false);
        periksa('empat kelompok dirender', indeks.kelompok.length === 4, indeks.kelompok.join(', '));

        // Kartu benar-benar menuju halaman daftarnya, bukan sekadar tampak.
        await nilai(`document.querySelector('a[href$="/master/referensi/sumber_dana"]').click()`);
        await tidur(900);

        const tujuan = await nilai('location.pathname');

        periksa('klik kartu membuka halaman daftarnya', tujuan === '/master/referensi/sumber_dana', tujuan);

        const halamanJenis = JSON.parse(await nilai(`(() => {
            const tersembunyi = document.querySelector('input[type="hidden"][name="jenis"]');

            return JSON.stringify({
                jenisTerkunci: tersembunyi ? tersembunyi.value : null,
                adaDropdownJenis: !! document.querySelector('select[name="jenis"]'),
                adaTautanKembali: !! document.querySelector('a[href$="/master/referensi"]'),
                barisTabel: document.querySelectorAll('tbody tr').length,
            });
        })()`));

        periksa(
            'jenis dikunci ke halaman lewat isian tersembunyi',
            halamanJenis.jenisTerkunci === 'sumber_dana',
            String(halamanJenis.jenisTerkunci)
        );

        periksa('jenis tidak lagi dapat diganti dari dalam form', halamanJenis.adaDropdownJenis === false);
        periksa('tersedia jalan kembali ke indeks', halamanJenis.adaTautanKembali === true);
        periksa('daftar sumber dana terisi', halamanJenis.barisTabel === 8, `dapat ${halamanJenis.barisTabel}`);

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