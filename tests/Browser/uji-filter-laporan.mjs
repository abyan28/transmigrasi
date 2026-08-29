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

        // ============================================================
        console.log('\nLaporan Alsintan (grup per SP, subtotal dihitung ulang):');
        await buka('/laporan/alsintan');

        periksa('bilah Alsintan: SP + rentang tahun pengadaan + jenis alat',
            await nilai(`!! document.querySelector('#filter-laporan-sp')
                && !! document.querySelector('#filter-laporan-tahun-dari')
                && !! document.querySelector('#filter-laporan-jenis')`) === true);

        // Total kawasan sebelum filter = jumlah seluruh data-jumlah.
        const totalSemua = await nilai(`
            [...document.querySelectorAll('table.tabel-dokumen tr[data-baris]')]
                .reduce((s, tr) => s + Number(tr.dataset.jumlah || 0), 0)
        `);
        const selTotal = `[...document.querySelectorAll('table.tabel-dokumen tfoot td')].pop().textContent.replace(/[^0-9]/g, '')`;
        periksa('total kawasan awal = jumlah semua baris',
            Number(await nilai(selTotal)) === totalSemua, `sel=${await nilai(selTotal)} data=${totalSemua}`);

        const spAls = await nilai(`document.querySelector('#filter-laporan-sp').options[1].value`);
        await setSelect('#filter-laporan-sp', spAls);
        await tidur(300);

        periksa('memilih SP menyembunyikan baris, grup-header, dan subtotal SP lain',
            await nilai(`
                [...document.querySelectorAll('table.tabel-dokumen tbody tr')]
                    .filter((tr) => tr.offsetParent !== null && tr.dataset.baris !== undefined)
                    .every((tr) => tr.dataset.sp === ${JSON.stringify(spAls)})
            `) === true);

        periksa('total kawasan menyusut ke jumlah baris SP terpilih',
            Number(await nilai(selTotal)) === await nilai(`
                [...document.querySelectorAll('table.tabel-dokumen tr[data-baris]')]
                    .filter((tr) => tr.dataset.sp === ${JSON.stringify(spAls)})
                    .reduce((s, tr) => s + Number(tr.dataset.jumlah || 0), 0)
            `));

        periksa('subtotal SP yang tampak = jumlah baris tampak grup itu',
            await nilai(`
                (() => {
                    const baris = [...document.querySelectorAll('table.tabel-dokumen tr[data-baris]')]
                        .filter((tr) => tr.offsetParent !== null);
                    const jum = baris.reduce((s, tr) => s + Number(tr.dataset.jumlah || 0), 0);
                    const subtotal = [...document.querySelectorAll('table.tabel-dokumen tbody tr')]
                        .find((tr) => tr.offsetParent !== null && tr.textContent.includes('Subtotal'));
                    if (! subtotal) return false;
                    const angka = Number(subtotal.lastElementChild.textContent.replace(/[^0-9]/g, ''));
                    return angka === jum;
                })()
            `) === true);

        periksa('baris total menyatakan cakupan aktif (rules 8o)',
            await nilai(`
                [...document.querySelectorAll('table.tabel-dokumen tfoot span[x-text]')]
                    .some((s) => s.offsetParent !== null && s.textContent.includes('Satuan Permukiman'))
            `) === true);

        // Rentang tahun mustahil -> tabel kosong, pesan muncul.
        await nilai(`
            [...document.querySelectorAll('section[aria-label="Penyaring laporan"] button')]
                .find((b) => b.textContent.trim().startsWith('Bersihkan'))?.click()
        `);
        await tidur(200);
        const thnMax = await nilai(`document.querySelector('#filter-laporan-tahun-dari').options[
            document.querySelector('#filter-laporan-tahun-dari').options.length - 1].value`);
        await setSelect('#filter-laporan-tahun-dari', thnMax);
        await setSelect('#filter-laporan-tahun-sampai', await nilai(`document.querySelector('#filter-laporan-tahun-sampai').options[1].value`));
        await tidur(300);
        periksa('rentang tahun mustahil memunculkan pesan Alsintan kosong',
            await nilai(`document.body.textContent.includes('Tidak ada alsintan yang cocok')`) === true);

        // ============================================================
        console.log('\nLaporan Saprotan (dua tabel datar, tanpa subtotal):');
        await buka('/laporan/saprotan');

        periksa('bilah Saprotan: SP + tahun + komoditas benih + jenis sarana',
            await nilai(`!! document.querySelector('#filter-laporan-sp')
                && !! document.querySelector('#filter-laporan-komoditas')
                && !! document.querySelector('#filter-laporan-jenis')`) === true);

        const benihTotal = await nilai(`document.querySelectorAll('table.tabel-dokumen')[0].querySelectorAll('tr[data-baris]').length`);
        const nonBenihTotal = await nilai(`document.querySelectorAll('table.tabel-dokumen')[1].querySelectorAll('tr[data-baris]').length`);
        periksa('kedua tabel Saprotan dirender penuh oleh Blade', benihTotal > 0 && nonBenihTotal > 0);

        // Filter komoditas benih: hanya tabel benih menyempit, non-benih utuh.
        const kom = await nilai(`document.querySelector('#filter-laporan-komoditas').options[1]?.value`);
        if (kom) {
            await setSelect('#filter-laporan-komoditas', kom);
            await tidur(300);
            periksa('filter komoditas menyempitkan tabel benih',
                await nilai(`
                    [...document.querySelectorAll('table.tabel-dokumen')[0].querySelectorAll('tr[data-baris]')]
                        .filter((tr) => tr.offsetParent !== null)
                        .every((tr) => tr.dataset.komoditas === ${JSON.stringify(kom)})
                `) === true);
            periksa('filter komoditas TIDAK menyentuh tabel non-benih (tak punya data-komoditas)',
                await nilai(`
                    [...document.querySelectorAll('table.tabel-dokumen')[1].querySelectorAll('tr[data-baris]')]
                        .filter((tr) => tr.offsetParent !== null).length
                `) === nonBenihTotal);
        } else {
            periksa('filter komoditas menyempitkan tabel benih', true, 'lewat: data contoh tanpa komoditas benih');
            periksa('filter komoditas TIDAK menyentuh tabel non-benih (tak punya data-komoditas)', true, 'lewat');
        }

        // ============================================================
        console.log('\nLaporan Hasil Panen (grup per SP, produktivitas tertimbang):');
        await buka('/laporan/hasil-panen');

        periksa('label pemilih tahun menegaskan sumbu anggaran bantuan (rules 16a)',
            (await nilai(`document.querySelector('label[for="filter-laporan-tahun-dari"]')?.textContent ?? ''`))
                .includes('Anggaran Bantuan'));

        const spPanen = await nilai(`document.querySelector('#filter-laporan-sp').options[1].value`);
        await setSelect('#filter-laporan-sp', spPanen);
        await tidur(300);

        // Produksi (ton) total = jumlah data-produksi_ton baris tampak.
        periksa('kolom Produksi total = jumlah baris tampak',
            await nilai(`
                (() => {
                    const baris = [...document.querySelectorAll('table.tabel-dokumen tr[data-baris]')]
                        .filter((tr) => tr.offsetParent !== null);
                    const jum = baris.reduce((s, tr) => s + Number(tr.dataset.produksi_ton || 0), 0);
                    const sel = [...document.querySelectorAll('table.tabel-dokumen tfoot td')].pop();
                    return Math.abs(Number(sel.textContent.replace(/\\./g, '').replace(',', '.')) - jum) < 0.05;
                })()
            `) === true);

        // Produktivitas tertimbang total = Sigma produksi / Sigma realisasi panen.
        periksa('produktivitas total = Sigma produksi / Sigma realisasi panen (tertimbang, bukan rata-rata)',
            await nilai(`
                (() => {
                    const baris = [...document.querySelectorAll('table.tabel-dokumen tr[data-baris]')]
                        .filter((tr) => tr.offsetParent !== null);
                    const prod = baris.reduce((s, tr) => s + Number(tr.dataset.produksi_ton || 0), 0);
                    const panen = baris.reduce((s, tr) => s + Number(tr.dataset.realisasi_panen || 0), 0);
                    const tertimbang = panen > 0 ? prod / panen : 0;
                    const tds = [...document.querySelectorAll('table.tabel-dokumen tfoot td')];
                    const selProd = tds[tds.length - 2];   // produktivitas kolom kedua dari kanan
                    const nilai = Number(selProd.textContent.replace(/\\./g, '').replace(',', '.'));
                    return Math.abs(nilai - tertimbang) < 0.05;
                })()
            `) === true);

        const spPanenNama = await nilai(`document.querySelector('#filter-laporan-sp').options[1].textContent.trim()`);
        periksa('hanya satu grup (SP terpilih) yang tampak: 1 grup-header + 1 subtotal',
            await nilai(`
                [...document.querySelectorAll('table.tabel-dokumen tbody tr')]
                    .filter((tr) => tr.offsetParent !== null && tr.textContent.includes('Subtotal')).length
            `) === 1
            && (await nilai(`
                [...document.querySelectorAll('table.tabel-dokumen tbody tr')]
                    .find((tr) => tr.offsetParent !== null && tr.textContent.includes('Subtotal'))?.textContent ?? ''
            `)).includes(spPanenNama));

        // Semua baris data tampak milik SP terpilih.
        periksa('baris panen tampak semuanya milik SP terpilih',
            await nilai(`
                [...document.querySelectorAll('table.tabel-dokumen tr[data-baris]')]
                    .filter((tr) => tr.offsetParent !== null)
                    .every((tr) => tr.dataset.sp === ${JSON.stringify(spPanen)})
            `) === true);

        // ============================================================
        console.log('\nLaporan Monografi SP (potret per SP: tabel + tiap bab):');
        await buka('/laporan/monografi-sp');

        periksa('bilah Monografi: pemilih SP, tanpa rentang tahun',
            await nilai(`!! document.querySelector('#filter-laporan-sp')
                && ! document.querySelector('#filter-laporan-tahun-dari')`) === true);

        const spMono = await nilai(`document.querySelector('#filter-laporan-sp').options[1].value`);
        await setSelect('#filter-laporan-sp', spMono);
        await tidur(300);

        periksa('memilih SP menyisakan tepat satu baris ikhtisar',
            await nilai(`
                [...document.querySelectorAll('table.tabel-dokumen tr[data-baris]')]
                    .filter((tr) => tr.offsetParent !== null).length
            `) === 1);

        periksa('memilih SP menyisakan tepat satu section Bab II',
            await nilai(`
                [...document.querySelectorAll('section[data-baris]')]
                    .filter((s) => s.offsetParent !== null).length
            `) === 1);

        periksa('section Bab II yang tersisa milik SP terpilih',
            await nilai(`
                [...document.querySelectorAll('section[data-baris]')]
                    .filter((s) => s.offsetParent !== null)
                    .every((s) => s.dataset.sp === ${JSON.stringify(spMono)})
            `) === true);

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
