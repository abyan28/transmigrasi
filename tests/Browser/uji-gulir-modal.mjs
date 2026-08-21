/**
 * Uji peramban untuk gulir modal yang tenggelam.
 *
 * Mengapa uji peramban: gejalanya murni geometri. Uji string tidak dapat
 * mengetahui apakah puncak modal masih terjangkau setelah digulir, sebab
 * yang menentukan adalah nilai `getBoundingClientRect().top` sesudah gulir
 * mentok, bukan kelas CSS yang tertulis.
 *
 * Yang diperiksa: modal yang LEBIH TINGGI daripada layar tetap dapat digulir
 * kembali ke puncaknya. Sebelum perbaikan 2026-08-20, wadah modal memakai
 * `items-center` sekaligus `overflow-y-auto`, sehingga panel meluber ke atas
 * dan ke bawah sekaligus; luberan atasnya tidak pernah dapat dijangkau sebab
 * `scrollTop` tidak bisa bernilai negatif.
 *
 * Dijalankan lewat Edge headless dan protokol DevTools, TANPA menambah
 * dependensi. Menuntut peladen hidup di 127.0.0.1:8099.
 *
 *   php artisan serve --port=8099
 *   node tests/Browser/uji-gulir-modal.mjs
 */

import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { setTimeout as tidur } from 'node:timers/promises';

const ASAL = 'http://127.0.0.1:8099';
const PORT_DEVTOOLS = 9341;

// Sengaja pendek. Modal apa pun akan lebih tinggi daripada ini, sehingga
// keadaan yang menjadi seluruh alasan perbaikan benar-benar terjadi.
const LEBAR_LAYAR = 1280;
const TINGGI_LAYAR = 500;

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

        // Memaksa ukuran viewport, bukan sekadar ukuran jendela. Tanpa ini
        // tinggi terpakai bisa berbeda dari yang diminta dan modal tidak pernah
        // benar-benar melampaui layar.
        await kirim('Emulation.setDeviceMetricsOverride', {
            width: LEBAR_LAYAR,
            height: TINGGI_LAYAR,
            deviceScaleFactor: 1,
            mobile: false,
        });

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

        const tutupModal = async () => {
            await nilai(`window.dispatchEvent(new CustomEvent('tutup-modal'))`);
            await tidur(400);
        };

        /**
         * Menguji satu modal: buka, gulir badan sampai mentok bawah, lalu
         * pastikan puncaknya masih dapat dicapai kembali.
         */
        const ujiModal = async (jalur, namaModal, penanda, judul) => {
            console.log(`\n${judul}:`);
            await buka(jalur);
            await nilai(`window.dispatchEvent(new CustomEvent('buka-modal', { detail: '${namaModal}' }))`);
            await tidur(700);

            const tampil = await nilai(`
                (document.querySelector('${penanda}')?.getClientRects().length ?? 0) > 0
            `);
            periksa('modal terbuka', tampil === true);

            if (! tampil) {
                return;
            }

            // Panel wajib muat di dalam layar. Bila tingginya melampaui
            // viewport, sebagiannya berada di luar jangkauan sejak awal.
            const panel = await nilai(`
                (() => {
                    const p = document.querySelector('${penanda}')?.closest('[x-ref="panel"]')
                        ?? document.querySelector('${penanda}')?.closest('.relative');
                    if (! p) return null;
                    const r = p.getBoundingClientRect();
                    return { atas: Math.round(r.top), bawah: Math.round(r.bottom), tinggi: Math.round(r.height) };
                })()
            `);

            periksa(
                'panel tidak meluber ke atas layar',
                panel !== null && panel.atas >= -1,
                `atas=${panel?.atas}`
            );
            periksa(
                'panel tidak melampaui tinggi layar',
                panel !== null && panel.tinggi <= TINGGI_LAYAR,
                `tinggi=${panel?.tinggi} layar=${TINGGI_LAYAR}`
            );

            // Hanya SATU wilayah yang boleh menggulir di dalam lapisan ini.
            // Dua wilayah bertumpuk membuat gulir bergantung posisi kursor.
            const jumlahBergulir = await nilai(`
                (() => {
                    const lapisan = document.querySelector('${penanda}')?.closest('[role="dialog"]')
                        ?? document.querySelector('${penanda}')?.closest('[role="alertdialog"]');
                    if (! lapisan) return -1;
                    return [lapisan, ...lapisan.querySelectorAll('*')]
                        .filter((el) => el.scrollHeight - el.clientHeight > 4
                            && ['auto', 'scroll'].includes(getComputedStyle(el).overflowY))
                        .length;
                })()
            `);
            periksa(
                'hanya satu wilayah yang menggulir',
                jumlahBergulir === 1,
                `terbaca ${jumlahBergulir}`
            );

            // INTI PENGUJIAN: gulir badan sampai mentok bawah, lalu kembali.
            const hasil = await nilai(`
                (() => {
                    const lapisan = document.querySelector('${penanda}')?.closest('[role="dialog"]')
                        ?? document.querySelector('${penanda}')?.closest('[role="alertdialog"]');
                    const badan = [lapisan, ...lapisan.querySelectorAll('*')]
                        .find((el) => el.scrollHeight - el.clientHeight > 4
                            && ['auto', 'scroll'].includes(getComputedStyle(el).overflowY));
                    if (! badan) return { adaGulir: false };

                    const panel = lapisan.querySelector('[x-ref="panel"]') ?? badan;

                    badan.scrollTop = badan.scrollHeight;
                    const atasSetelahTurun = Math.round(panel.getBoundingClientRect().top);
                    const gulirBawah = badan.scrollTop;

                    badan.scrollTop = 0;
                    const atasSetelahNaik = Math.round(panel.getBoundingClientRect().top);
                    const gulirAtas = badan.scrollTop;

                    return {
                        adaGulir: true,
                        gulirBawah,
                        gulirAtas,
                        atasSetelahTurun,
                        atasSetelahNaik,
                    };
                })()
            `);

            if (! hasil?.adaGulir) {
                periksa('badan modal dapat digulir', false, 'tidak ada wilayah bergulir');

                return;
            }

            periksa('badan modal benar-benar dapat digulir', hasil.gulirBawah > 0, `scrollTop=${hasil.gulirBawah}`);

            // Panel TIDAK boleh ikut bergeser saat badan digulir: kepala dan
            // kaki menempel, hanya isinya yang bergerak.
            periksa(
                'panel tetap di tempat saat badan digulir',
                hasil.atasSetelahTurun === hasil.atasSetelahNaik,
                `turun=${hasil.atasSetelahTurun} naik=${hasil.atasSetelahNaik}`
            );

            periksa('gulir dapat kembali ke puncak', hasil.gulirAtas === 0, `scrollTop=${hasil.gulirAtas}`);

            // Setelah kembali ke puncak, isi teratas wajib terlihat di layar.
            const puncakTerlihat = await nilai(`
                (() => {
                    const el = document.querySelector('${penanda}');
                    const r = el.getBoundingClientRect();
                    return r.top >= 0 && r.top < ${TINGGI_LAYAR};
                })()
            `);
            periksa('isian teratas terlihat setelah kembali ke puncak', puncakTerlihat === true);

            await tutupModal();
        };

        await ujiModal('/transmigran', 'formTambahTransmigran', '#tambah_nama', 'Modal form panjang');
        await ujiModal('/lahan', 'formTambahLahan', '#tambah_kode_lahan', 'Modal form lahan');
        await ujiModal('/transmigran', 'imporTransmigran', '#judul-imporTransmigran', 'Modal impor');

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
