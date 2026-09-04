/**
 * Uji peramban untuk export Excel & PDF halaman Laporan (Task 10.1/10.2/10.3,
 * 2026-09-05).
 *
 * Mengapa uji peramban: yang diuji adalah PERILAKU peramban sungguhan --
 * berkas .xlsx yang benar-benar terunduh, dan `window.print()` yang benar-
 * benar terpanggil begitu rute dokumen dibuka dengan `#...&cetak=1`. Tak ada
 * server yang terlibat (export sepenuhnya sisi peramban, rules.md 12 poin
 * 11), sehingga satu-satunya cara membuktikannya adalah menjalankan
 * peramban sungguhan dan membaca hasilnya -- bukan menebak dari markup.
 *
 * Dijalankan lewat Edge headless dan protokol DevTools, TANPA dependensi
 * (pola sama uji-filter-laporan.mjs). Menuntut peladen hidup di
 * 127.0.0.1:8099.
 *
 *   php artisan serve --port=8099
 *   node tests/Browser/uji-export-laporan.mjs
 */

import { spawn } from 'node:child_process';
import { existsSync, mkdtempSync, readdirSync, readFileSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { setTimeout as tidur } from 'node:timers/promises';
import XLSX from 'xlsx';

const ASAL = 'http://127.0.0.1:8099';
const PORT_DEVTOOLS = 9354;

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

/**
 * Membaca workbook dari path, lewat `XLSX.read(Buffer)` -- BUKAN
 * `XLSX.readFile(path)`. Build ESM (`xlsx.mjs`) yang diimpor di sini tidak
 * mendeteksi modul `fs` Node secara otomatis (variabel internal `_fs` tetap
 * `undefined`), sehingga `readFile`/`readFileSync` SheetJS SENDIRI selalu
 * melempar "Cannot access file" apa pun keadaan berkasnya -- bukan galat
 * kunci berkas sungguhan. Membaca Buffer lewat `node:fs` sendiri lalu
 * menyerahkannya ke `XLSX.read()` melewati deteksi lingkungan itu sama sekali.
 */
function bacaWorkbook(path) {
    return XLSX.read(readFileSync(path));
}

/** Menunggu satu berkas muncul di direktori unduhan, atau null bila lewat batas waktu. */
async function tungguBerkas(dir, cocokNama, batasMs = 8000) {
    const mulai = Date.now();

    while (Date.now() - mulai < batasMs) {
        const berkas = readdirSync(dir).filter((f) => cocokNama(f) && !f.endsWith('.crdownload'));
        if (berkas.length > 0) {
            // Nama berkas sudah muncul di direktori, tetapi Windows kadang
            // masih menahan kuncinya sesaat sesudah selesai ditulis
            // (pemindaian antivirus, dsb.) -- jeda singkat sebelum dibaca.
            await tidur(500);

            return join(dir, berkas[0]);
        }
        await tidur(200);
    }

    return null;
}

async function main() {
    if (typeof WebSocket === 'undefined') {
        console.log('  LEWAT: WebSocket bawaan tidak tersedia pada Node ini.');

        return;
    }

    const dirUnduh = mkdtempSync(join(tmpdir(), 'sim-export-'));
    // Profil TERPISAH, wajib untuk uji ini: tanpa --user-data-dir, Edge
    // headless memuat profil ASLI pengguna, dan `Browser.setDownloadBehavior`
    // diam-diam KALAH oleh pengaturan unduhan profil itu -- berkas
    // mendarat di folder Unduhan sungguhan, bukan `dirUnduh`, sehingga uji
    // ini tak pernah menemukannya (ditemukan 2026-09-05 saat men-debug
    // kegagalan pertama). Uji peramban lain di repo ini tidak pernah
    // mengunduh apa pun, jadi celah ini tidak pernah terlihat sebelumnya.
    const dirProfil = mkdtempSync(join(tmpdir(), 'sim-export-profil-'));

    const proses = spawn(cariEdge(), [
        '--headless=new',
        `--remote-debugging-port=${PORT_DEVTOOLS}`,
        '--no-first-run',
        '--disable-gpu',
        `--user-data-dir=${dirProfil}`,
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
        // Unduhan diarahkan ke direktori sementara -- Task 10.1 tak dapat
        // diuji tanpa membaca berkas .xlsx yang sungguhan terunduh.
        await kirim('Browser.setDownloadBehavior', { behavior: 'allow', downloadPath: dirUnduh });
        // window.print() dilumpuhkan SEBELUM skrip halaman berjalan (Task
        // 10.2): headless tak punya dialog cetak sungguhan, dan yang perlu
        // dibuktikan hanyalah bahwa tombol memicu pemanggilannya.
        await kirim('Page.addScriptToEvaluateOnNewDocument', {
            source: 'window.__dicetak = false; window.print = () => { window.__dicetak = true; };',
        });

        const buka = async (jalur) => {
            await kirim('Page.navigate', { url: 'about:blank' });
            await tidur(150);
            await kirim('Page.navigate', { url: `${ASAL}${jalur}` });
            for (let i = 0; i < 60; i += 1) {
                if (await nilai('!! window.Alpine')) break;
                await tidur(250);
            }
            await tidur(500);
        };

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

        // Seluruh rute /laporan/* WAJIB login (routes/internal.php: 'web',
        // 'auth', 'pastikan.ganti.sandi'). Akun contoh dari AdminAwalSeeder
        // (.env SIM_ADMIN_USERNAME/PASSWORD, SIM_ADMIN_WAJIB_GANTI=false).
        console.log('\nMasuk sebagai admin:');
        await buka('/login');
        await nilai(`
            (() => {
                document.querySelector('#kredensial').value = 'admin@malakakab.go.id';
                document.querySelector('#password').value = 'admin';
                document.querySelector('#kredensial').closest('form').submit();
            })()
        `);
        for (let i = 0; i < 40; i += 1) {
            if (await nilai(`window.location.pathname !== '/login'`)) break;
            await tidur(250);
        }
        await tidur(300);
        periksa('berhasil masuk (dialihkan keluar dari /login)',
            await nilai('window.location.pathname') !== '/login');

        // ============================================================
        console.log('\nLaporan Poktan (satu tabel, tanpa filter aktif):');
        await buka('/laporan/poktan');

        const tombolExcelAda = await nilai(`
            [...document.querySelectorAll('button')].some((b) => b.textContent.trim() === 'Unduh Excel')
        `);
        periksa('tombol "Unduh Excel" sungguhan ada (bukan placeholder "segera hadir")', tombolExcelAda === true);

        await nilai(`
            [...document.querySelectorAll('button')].find((b) => b.textContent.trim() === 'Unduh Excel')?.click()
        `);
        const berkasPoktan = await tungguBerkas(dirUnduh, (f) => f.startsWith('poktan-') && f.endsWith('.xlsx'));
        periksa('mengunduh berkas .xlsx sungguhan (poktan)', berkasPoktan !== null,
            `isi direktori: ${readdirSync(dirUnduh).join(', ')}`);

        if (berkasPoktan) {
            const wb = bacaWorkbook(berkasPoktan);
            periksa('workbook memuat minimal satu lembar', wb.SheetNames.length > 0);

            const namaCaption = await nilai(`document.querySelector('table.tabel-dokumen caption')?.textContent.trim() ?? ''`);
            periksa('nama lembar pertama berasal dari <caption> tabel',
                wb.SheetNames[0] === namaCaption.slice(0, 31),
                `lembar=${wb.SheetNames[0]} caption=${namaCaption}`);

            const ws = wb.Sheets[wb.SheetNames[0]];
            const baris = XLSX.utils.sheet_to_json(ws, { header: 1 });
            periksa('lembar memuat baris data (bukan cuma header)', baris.length > 1);
        }

        // ============================================================
        console.log('\nLaporan Hasil Panen (Task 10.3 -- Excel mewarisi filter SP aktif):');
        await buka('/laporan/hasil-panen');

        const totalBarisPanen = await nilai(`
            document.querySelectorAll('table.tabel-dokumen tr[data-baris]').length
        `);
        const spPanen = await nilai(`document.querySelector('#filter-laporan-sp').options[1].value`);
        await setSelect('#filter-laporan-sp', spPanen);
        await tidur(300);

        const tampakPanen = await nilai(`
            [...document.querySelectorAll('table.tabel-dokumen tr[data-baris]')]
                .filter((tr) => tr.offsetParent !== null).length
        `);
        periksa('filter SP benar-benar menyempitkan baris sebelum diunduh',
            tampakPanen > 0 && tampakPanen < totalBarisPanen,
            `tampak=${tampakPanen} dari ${totalBarisPanen}`);

        await nilai(`
            [...document.querySelectorAll('button')].find((b) => b.textContent.trim() === 'Unduh Excel')?.click()
        `);
        const berkasPanen = await tungguBerkas(dirUnduh, (f) => f.startsWith('hasil-panen-') && f.endsWith('.xlsx'));
        periksa('mengunduh berkas .xlsx (hasil-panen, berfilter)', berkasPanen !== null);

        if (berkasPanen) {
            const wb = bacaWorkbook(berkasPanen);
            const ws = wb.Sheets[wb.SheetNames[0]];
            const baris = XLSX.utils.sheet_to_json(ws, { header: 1, blankrows: false });

            // Baris data = seluruh baris minus header. Dibandingkan dengan
            // SELURUH <tr> tbody/tfoot yang tampak (bukan hanya
            // tr[data-baris]): tabel ini berkelompok per SP dengan baris
            // header-grup + subtotal yang JUGA tersaring x-show sama seperti
            // baris data, dan keduanya SAH ikut Excel -- itulah yang tampak
            // di layar. Task 10.3 tetap teruji: total (seluruh SP) > tampak
            // (satu SP terpilih).
            const tampakSemuaPanen = await nilai(`
                [...document.querySelectorAll('table.tabel-dokumen tbody tr, table.tabel-dokumen tfoot tr')]
                    .filter((tr) => tr.offsetParent !== null).length
            `);
            periksa('jumlah baris di Excel = seluruh baris tampak (tersaring), BUKAN seluruh baris tabel',
                baris.length - 1 === tampakSemuaPanen,
                `excel=${baris.length - 1} tampakSemua=${tampakSemuaPanen} tampakData=${tampakPanen} total=${totalBarisPanen}`);

            const punyaSelAngka = baris.slice(1).some((r) => r.some((sel) => typeof sel === 'number'));
            periksa('minimal satu sel angka Indonesia terkonversi jadi tipe number asli', punyaSelAngka);
        }

        // ============================================================
        console.log('\nRekap Indikator Kawasan (banyak tabel -> banyak lembar):');
        await buka('/laporan/indikator-kawasan');

        const jumlahTabel = await nilai(`document.querySelectorAll('table.tabel-dokumen').length`);
        await nilai(`
            [...document.querySelectorAll('button')].find((b) => b.textContent.trim() === 'Unduh Excel')?.click()
        `);
        const berkasIndikator = await tungguBerkas(dirUnduh, (f) => f.startsWith('indikator-kawasan-') && f.endsWith('.xlsx'));
        periksa('mengunduh berkas .xlsx (indikator-kawasan, multi-tabel)', berkasIndikator !== null);

        if (berkasIndikator) {
            const wb = bacaWorkbook(berkasIndikator);
            periksa('jumlah lembar workbook = jumlah tabel di halaman',
                wb.SheetNames.length === jumlahTabel,
                `lembar=${wb.SheetNames.length} tabel=${jumlahTabel}`);
            periksa('nama lembar tidak ada yang kembar (disambiguasi)',
                new Set(wb.SheetNames).size === wb.SheetNames.length);
        }

        // ============================================================
        console.log('\nTombol "Unduh PDF" (Task 10.2 -- cetak peramban):');
        await buka('/laporan/hasil-panen');
        await setSelect('#filter-laporan-sp', spPanen);
        await tidur(200);

        const hrefUnduhPdf = await nilai(`
            [...document.querySelectorAll('a[target="_blank"]')]
                .find((a) => a.textContent.trim().startsWith('Unduh PDF'))?.getAttribute('href') ?? ''
        `);
        periksa('href "Unduh PDF" menuju rute dokumen + filter aktif + cetak=1',
            hrefUnduhPdf.includes('/laporan/hasil-panen/dokumen#') && hrefUnduhPdf.includes('cetak=1')
                && hrefUnduhPdf.includes('sp=' + spPanen),
            `href=${hrefUnduhPdf}`);

        await buka(hrefUnduhPdf.replace(ASAL, ''));
        await tidur(300);
        periksa('membuka tautan "Unduh PDF" memicu window.print() otomatis',
            await nilai('window.__dicetak') === true);

        periksa('baris tersaring TETAP tersembunyi pada dokumen yang dicetak',
            await nilai(`
                (() => {
                    const b = [...document.querySelectorAll('table.tabel-dokumen tr[data-baris]')];
                    const tampak = b.filter((tr) => tr.offsetParent !== null);
                    return tampak.length > 0 && tampak.length < b.length
                        && tampak.every((tr) => tr.dataset.sp === ${JSON.stringify(spPanen)});
                })()
            `) === true);

        // "Generate Laporan" TETAP tanpa auto-print (hanya "Unduh PDF" yang memicu).
        await buka('/laporan/hasil-panen');
        const hrefGenerate = await nilai(`
            [...document.querySelectorAll('a[target="_blank"]')]
                .find((a) => a.textContent.trim().startsWith('Generate Laporan'))?.getAttribute('href') ?? ''
        `);
        periksa('href "Generate Laporan" TIDAK membawa cetak=1 (tinjau layar, bukan cetak otomatis)',
            ! hrefGenerate.includes('cetak=1'), `href=${hrefGenerate}`);

        soket.close();
    } finally {
        proses.kill();
        // Edge belum tentu langsung melepas kunci berkas profilnya begitu
        // proses diminta berhenti -- jeda singkat, lalu bersihkan tanpa
        // menjatuhkan uji bila masih gagal (bukan bagian dari yang diuji).
        await tidur(500);
        try {
            rmSync(dirUnduh, { recursive: true, force: true });
            rmSync(dirProfil, { recursive: true, force: true });
        } catch {
            // Diam-diam dilewati -- berkas sementara OS, bukan kegagalan uji.
        }
    }

    console.log(`\n${lulus} lulus, ${gagal} gagal.`);
    process.exit(gagal > 0 ? 1 : 0);
}

main().catch((galat) => {
    console.error(galat);
    process.exit(1);
});
