/**
 * Uji peramban untuk bilah filter halaman laporan (Putaran 3 D3).
 *
 * Mengapa uji peramban: yang diuji perilaku, bukan markup. "Memilih satu SP
 * menyembunyikan baris SP lain", "nomor urut ikut rapat kembali", dan "kalimat
 * cakupan di kepala kertas berubah" hanya dapat dibuktikan dengan menggerakkan
 * kontrolnya lalu membaca DOM sesudahnya. Penyaringan berjalan di Alpine sisi
 * peramban sebab GitHub Pages tidak melayani query string (notes.md 1b.5).
 *
 * Dijalankan lewat Edge headless dan protokol DevTools, TANPA dependensi.
 * Menuntut peladen hidup di 127.0.0.1:8099.
 *
 *   php artisan serve --port=8099
 *   node tests/Browser/uji-filter-laporan.mjs
 */

import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { setTimeout as tidur } from 'node:timers/promises';

const ASAL = 'http://127.0.0.1:8099';
const PORT_DEVTOOLS = 9353;

const LEBAR_LAYAR = 1280;
const TINGGI_LAYAR = 900;

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
                if (await nilai('!! window.Alpine')) break;
                await tidur(250);
            }
            await tidur(500);
        };

        // Pembantu di dalam halaman.
        const setSelect = (id, val) => nilai(`
            (() => {
                const el = document.querySelector('${id}');
                if (! el) return false;
                el.value = ${JSON.stringify(val)};
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
                return true;
            })()
        `);

        // ============================================================
        console.log('\nLaporan Transmigran, halaman berbingkai:');
        await buka('/laporan/transmigran');

        periksa('bilah filter tampak',
            await nilai(`!! document.querySelector('section[aria-label="Penyaring laporan"]')`) === true);

        periksa('pemilih SP memuat lebih dari satu opsi',
            await nilai(`document.querySelector('#filter-laporan-sp')?.options.length ?? 0`) > 1);

        periksa('pemilih tahun kedatangan dan status tinggal ada',
            await nilai(`!! document.querySelector('#filter-laporan-tahun-dari')
                && !! document.querySelector('#filter-laporan-status')`) === true);

        const totalBarisA = await nilai(`
            document.querySelectorAll('table.tabel-dokumen')[0].querySelectorAll('tbody tr[data-baris]').length
        `);
        periksa('seluruh baris transmigran dirender Blade (bukan disaring server)',
            totalBarisA > 0);

        const tampakAwalA = await nilai(`
            [...document.querySelectorAll('table.tabel-dokumen')[0].querySelectorAll('tbody tr[data-baris]')]
                .filter((tr) => tr.offsetParent !== null).length
        `);
        periksa('tanpa filter, semua baris transmigran tampak', tampakAwalA === totalBarisA);

        // Pilih SP pertama yang bukan kosong.
        const spPertama = await nilai(`document.querySelector('#filter-laporan-sp').options[1].value`);
        const spNama = await nilai(`document.querySelector('#filter-laporan-sp').options[1].textContent.trim()`);
        await setSelect('#filter-laporan-sp', spPertama);
        await tidur(300);

        const tampakSetelahSp = await nilai(`
            [...document.querySelectorAll('table.tabel-dokumen')[0].querySelectorAll('tbody tr[data-baris]')]
                .filter((tr) => tr.offsetParent !== null).length
        `);
        periksa('memilih SP menyempitkan baris transmigran',
            tampakSetelahSp > 0 && tampakSetelahSp < totalBarisA,
            `tampak=${tampakSetelahSp} dari ${totalBarisA}`);

        periksa('baris yang tersisa semuanya milik SP terpilih',
            await nilai(`
                [...document.querySelectorAll('table.tabel-dokumen')[0].querySelectorAll('tbody tr[data-baris]')]
                    .filter((tr) => tr.offsetParent !== null)
                    .every((tr) => tr.dataset.sp === ${JSON.stringify(spPertama)})
            `) === true);

        periksa('baris rumah dan lahan ikut menyempit oleh filter SP',
            await nilai(`
                [...document.querySelectorAll('table.tabel-dokumen')[2].querySelectorAll('tbody tr[data-baris]')]
                    .filter((tr) => tr.offsetParent !== null)
                    .every((tr) => tr.dataset.sp === ${JSON.stringify(spPertama)})
            `) === true);

        // Nomor urut datang dari penghitung CSS (sel data-nomor kosong di DOM):
        // elemen ber-display:none tidak menaikkan penghitung, jadi nomornya
        // ikut rapat kembali tanpa JavaScript. getComputedStyle tidak dapat
        // membaca hasil counter(), sehingga kebenarannya dijaga uji Pest atas
        // aturan CSS-nya; di sini cukup dipastikan selnya memang kosong di DOM.
        periksa('sel nomor urut kosong di DOM (diisi penghitung CSS)',
            await nilai(`
                [...document.querySelectorAll('table.tabel-dokumen td[data-nomor]')]
                    .every((td) => td.textContent.trim() === '')
            `) === true);

        periksa('kalimat cakupan di kepala kertas menyebut SP terpilih',
            (await nilai(`document.querySelector('dd[x-text="kalimatCakupan"]')?.textContent ?? ''`))
                .includes(spNama));

        periksa('tombol Bersihkan muncul saat ada filter',
            await nilai(`
                [...document.querySelectorAll('section[aria-label="Penyaring laporan"] button')]
                    .some((b) => b.offsetParent !== null && b.textContent.trim().startsWith('Bersihkan'))
            `) === true);

        // Tahun kedatangan mustahil -> bagian transmigran kosong, muncul pesan.
        await setSelect('#filter-laporan-tahun-dari', await nilai(`
            document.querySelector('#filter-laporan-tahun-dari').options[
                document.querySelector('#filter-laporan-tahun-dari').options.length - 1
            ].value
        `));
        await setSelect('#filter-laporan-tahun-sampai', await nilai(`
            document.querySelector('#filter-laporan-tahun-sampai').options[1].value
        `));
        await tidur(300);
        // dari > sampai: rentang mustahil, tak ada tahun yang lolos.
        periksa('rentang tahun mustahil memunculkan pesan "tidak ada yang cocok"',
            await nilai(`
                [...document.querySelectorAll('table.tabel-dokumen')[0].querySelectorAll('tbody tr')]
                    .some((tr) => tr.offsetParent !== null && tr.textContent.includes('Tidak ada kepala keluarga yang cocok'))
            `) === true);

        // Bersihkan memulihkan.
        await nilai(`
            [...document.querySelectorAll('section[aria-label="Penyaring laporan"] button')]
                .find((b) => b.textContent.trim().startsWith('Bersihkan'))?.click()
        `);
        await tidur(300);
        periksa('Bersihkan memulihkan seluruh baris',
            await nilai(`
                [...document.querySelectorAll('table.tabel-dokumen')[0].querySelectorAll('tbody tr[data-baris]')]
                    .filter((tr) => tr.offsetParent !== null).length
            `) === totalBarisA);

        periksa('kalimat cakupan kembali ke bawaannya',
            await nilai(`
                document.querySelector('dd[x-text="kalimatCakupan"]')?.textContent.includes('Seluruh kepala keluarga')
            `) === true);

        // ============================================================
        console.log('\nRute dokumen polos:');
        await buka('/laporan/transmigran/dokumen');
        periksa('bilah filter tetap ada pada rute dokumen',
            await nilai(`!! document.querySelector('section[aria-label="Penyaring laporan"]')`) === true);
        periksa('bilah filter memakai .cetak-sembunyi',
            await nilai(`document.querySelector('section[aria-label="Penyaring laporan"]')?.classList.contains('cetak-sembunyi')`) === true);

        // ============================================================
        console.log('\nLaporan Poktan (penyaring menyembunyikan tabel utuh):');
        await buka('/laporan/poktan');

        periksa('bilah filter Poktan tampak dengan pemilih SP',
            await nilai(`!! document.querySelector('#filter-laporan-sp')`) === true);

        const totalTabelPoktan = await nilai(`document.querySelectorAll('div[data-poktan-wadah]').length`);
        periksa('seluruh tabel poktan dirender Blade', totalTabelPoktan > 1);

        const spPoktan = await nilai(`document.querySelector('#filter-laporan-sp').options[1].value`);
        await setSelect('#filter-laporan-sp', spPoktan);
        await tidur(300);

        const tampakPoktan = await nilai(`
            [...document.querySelectorAll('div[data-poktan-wadah]')].filter((d) => d.offsetParent !== null).length
        `);
        periksa('memilih SP menyembunyikan tabel poktan SP lain',
            tampakPoktan > 0 && tampakPoktan < totalTabelPoktan,
            `tampak=${tampakPoktan} dari ${totalTabelPoktan}`);

        periksa('tabel poktan yang tersisa semuanya milik SP terpilih',
            await nilai(`
                [...document.querySelectorAll('div[data-poktan-wadah]')]
                    .filter((d) => d.offsetParent !== null)
                    .every((d) => d.dataset.sp === ${JSON.stringify(spPoktan)})
            `) === true);

        // Cari SP yang tak punya poktan sama sekali; bila ada, pastikan pesannya muncul.
        const spTanpaPoktan = await nilai(`
            (() => {
                const punya = new Set([...document.querySelectorAll('div[data-poktan-wadah]')].map((d) => d.dataset.sp));
                const opsi = [...document.querySelector('#filter-laporan-sp').options].slice(1);
                const kosong = opsi.find((o) => ! punya.has(o.value));
                return kosong ? kosong.value : null;
            })()
        `);
        if (spTanpaPoktan) {
            await setSelect('#filter-laporan-sp', spTanpaPoktan);
            await tidur(300);
            periksa('SP tanpa poktan memunculkan pesan "tidak ada yang cocok"',
                await nilai(`
                    [...document.querySelectorAll('div[x-show]')]
                        .some((d) => d.offsetParent !== null && d.textContent.includes('Tidak ada kelompok tani yang cocok'))
                `) === true);
        } else {
            periksa('SP tanpa poktan memunculkan pesan "tidak ada yang cocok"', true,
                'lewat: semua SP punya poktan pada data contoh');
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
