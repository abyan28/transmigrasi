/**
 * Uji peramban untuk pergantian kepala keluarga.
 *
 * Mengapa uji peramban, bukan uji Pest: yang menentukan benar tidaknya fitur
 * ini adalah PERILAKU modal, bukan keberadaan atribut `name` di HTML. Uji
 * string tidak dapat membedakan blok peringatan yang tampil dari yang hanya
 * ada di markup, tidak dapat memastikan pilihan nasib jabatan ketua benar-benar
 * wajib, dan tidak dapat menangkap tab riwayat yang tidak dapat dibuka.
 *
 * Kekeliruan semacam itu sudah tercatat tiga kali pada agents/notes.md bagian
 * 1d.2, butir b799, dan temuan `pilih-cari` pada 2026-08-20.
 *
 * Dijalankan lewat Edge headless dan protokol DevTools, TANPA menambah
 * dependensi. Menuntut peladen hidup di 127.0.0.1:8099.
 *
 *   php artisan serve --port=8099
 *   node tests/Browser/uji-suksesi-kk.mjs
 */

import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { setTimeout as tidur } from 'node:timers/promises';

const ASAL = 'http://127.0.0.1:8099';
const PORT_DEVTOOLS = 9338;

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

        // Modal WAJIB ditutup sebelum berpindah halaman: penguncian gulir milik
        // modal lama masih menempel saat halaman berikutnya dimuat, sehingga
        // panel yang baru terbaca belum bergeometri (notes.md 1d.4).
        const tutupModal = async () => {
            await nilai(`window.dispatchEvent(new CustomEvent('tutup-modal'))`);
            await tidur(400);
        };

        // `offsetParent` tidak dapat dipakai memeriksa keterlihatan pada elemen
        // di dalam panel berposisi fixed; nilainya null meski elemennya tampil.
        const terlihat = (pemilih) => nilai(`
            (document.querySelector('${pemilih}')?.getClientRects().length ?? 0) > 0
        `);

        await kirim('Page.enable');
        await kirim('Runtime.enable');

        /* ---------------------------------------------------------------
         | Keluarga 1: menjabat ketua poktan lewat jalur Kepala Keluarga
         --------------------------------------------------------------- */

        console.log('\nKeluarga yang menjabat ketua poktan:');
        await buka('/transmigran/1');

        const adaTombol = await nilai(`
            [...document.querySelectorAll('button')]
                .some((b) => b.textContent.trim() === 'Ganti Kepala Keluarga')
        `);
        periksa('tombol suksesi tersedia', adaTombol === true);

        // Modal suksesi TERPISAH dari modal ubah, dan itu inti rancangannya.
        await nilai(`window.dispatchEvent(new CustomEvent('buka-modal', { detail: 'formGantiKepalaKeluarga' }))`);
        await tidur(600);

        periksa('modal suksesi terbuka', (await terlihat('#suksesi_nama_baru')) === true);
        periksa(
            'modal ubah TIDAK ikut terbuka',
            (await nilai(`(document.querySelector('#ubah_nik')?.getClientRects().length ?? 0) > 0`)) === false
        );

        // Inti Fase 3: petugas wajib memutuskan nasib jabatan ketua.
        const pilihanKetua = await nilai(`
            document.querySelectorAll('input[name="nasib_ketua_poktan"]').length
        `);
        periksa('dua pilihan nasib jabatan ketua dirender', pilihanKetua === 2, `terbaca ${pilihanKetua}`);

        periksa(
            'blok peringatan ketua benar-benar tampil',
            (await terlihat('input[name="nasib_ketua_poktan"]')) === true
        );

        const wajibDipilih = await nilai(`
            [...document.querySelectorAll('input[name="nasib_ketua_poktan"]')]
                .every((r) => r.required === true)
        `);
        periksa('pilihan nasib jabatan bersifat wajib', wajibDipilih === true);

        // Sisi lama dikirim tanpa diketik ulang.
        const nikLama = await nilai(`
            document.querySelector('input[name="nik_lama"]')?.value
        `);
        periksa('NIK lama terkirim tanpa diketik ulang', nikLama === '5321011505800001', `terbaca "${nikLama}"`);

        // Nomor KK terisi nilai lama dan tetap dapat disunting.
        const kkTerisi = await nilai(`document.querySelector('#suksesi_no_kk_baru')?.value`);
        const kkDapatDisunting = await nilai(`
            document.querySelector('#suksesi_no_kk_baru')?.readOnly === false
                && document.querySelector('#suksesi_no_kk_baru')?.disabled === false
        `);
        periksa('nomor KK terisi nilai lama', kkTerisi === '5321010102150001', `terbaca "${kkTerisi}"`);
        periksa('nomor KK tetap dapat disunting', kkDapatDisunting === true);

        // Form tidak boleh terkirim selama nasib jabatan belum dipilih.
        const validSebelum = await nilai(`
            document.querySelector('#suksesi_nama_baru')?.closest('form')?.checkValidity()
        `);
        periksa('form tertahan selama isian wajib kosong', validSebelum === false);

        await nilai(`
            (() => {
                const isi = (id, v) => {
                    const el = document.querySelector(id);
                    el.value = v;
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                };
                isi('#suksesi_nama_baru', 'MARIA BERE');
                isi('#suksesi_nik_baru', '5321015208850101');
                isi('#suksesi_hubungan', 'Istri/Suami');
                isi('#suksesi_alasan', 'Meninggal');
                document.querySelector('input[name="nasib_ketua_poktan"][value="kosongkan"]').click();
            })()
        `);
        await tidur(400);

        const validSesudah = await nilai(`
            document.querySelector('#suksesi_nama_baru')?.closest('form')?.checkValidity()
        `);
        periksa('form sah setelah seluruh isian wajib terisi', validSesudah === true);

        await tutupModal();

        /* ---------------------------------------------------------------
         | Keluarga 8: anggota poktan, bukan ketua
         --------------------------------------------------------------- */

        console.log('\nKeluarga yang bukan ketua poktan:');
        await buka('/transmigran/8');
        await nilai(`window.dispatchEvent(new CustomEvent('buka-modal', { detail: 'formGantiKepalaKeluarga' }))`);
        await tidur(600);

        periksa('modal suksesi tetap tersedia', (await terlihat('#suksesi_nama_baru')) === true);

        // Kontrol yang tidak menentukan apa pun adalah kontrol mati (R-26).
        const adaPilihanKetua = await nilai(`
            !! document.querySelector('input[name="nasib_ketua_poktan"]')
        `);
        periksa('pilihan nasib jabatan TIDAK dirender', adaPilihanKetua === false);

        // Tanpa pilihan itu, form tetap dapat disahkan hanya dengan isian inti.
        await nilai(`
            (() => {
                const isi = (id, v) => {
                    const el = document.querySelector(id);
                    el.value = v;
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                };
                isi('#suksesi_nama_baru', 'ANDREAS HOAR');
                isi('#suksesi_nik_baru', '5321011803050108');
                isi('#suksesi_hubungan', 'Anak');
                isi('#suksesi_alasan', 'Cerai');
            })()
        `);
        await tidur(400);

        const sahTanpaKetua = await nilai(`
            document.querySelector('#suksesi_nama_baru')?.closest('form')?.checkValidity()
        `);
        periksa('form sah tanpa pilihan jabatan ketua', sahTanpaKetua === true);

        await tutupModal();

        /* ---------------------------------------------------------------
         | Tab riwayat suksesi
         --------------------------------------------------------------- */

        console.log('\nTab riwayat suksesi:');
        await buka('/transmigran/6');

        const bukaTab = await nilai(`
            (() => {
                const tab = [...document.querySelectorAll('button[role="tab"]')]
                    .find((b) => b.textContent.includes('Riwayat Kepala Keluarga'));
                if (! tab) return false;
                tab.click();
                return true;
            })()
        `);
        periksa('tab riwayat dapat diklik', bukaTab === true);
        await tidur(500);

        const isiTab = await nilai(`
            (() => {
                const panel = [...document.querySelectorAll('[role="tabpanel"]')]
                    .find((p) => p.getClientRects().length > 0
                        && p.textContent.includes('YAKOBUS BRIA'));
                return panel?.textContent?.replace(/\\s+/g, ' ').trim() ?? '';
            })()
        `);

        periksa(
            'riwayat menampilkan kedua sisi identitas',
            isiTab.includes('YAKOBUS BRIA') && isiTab.includes('FRANSISKA BRIA'),
            `terbaca "${isiTab.slice(0, 100)}"`
        );
        periksa('riwayat menampilkan perubahan nomor KK', isiTab.includes('5321010102160006'));

        // Tab Catatan Log wajib tetap paling kanan (ui-spec.md 5.1c).
        const urutanTab = await nilai(`
            [...document.querySelectorAll('button[role="tab"]')]
                .map((b) => b.textContent.trim())
        `);
        periksa(
            'Catatan Log tetap tab paling kanan',
            urutanTab[urutanTab.length - 1] === 'Catatan Log',
            `terbaca "${urutanTab.join(' | ')}"`
        );

        // Keluarga tanpa riwayat: keadaan kosong dinyatakan, bukan tab hilang.
        await buka('/transmigran/2');
        await nilai(`
            (() => {
                const tab = [...document.querySelectorAll('button[role="tab"]')]
                    .find((b) => b.textContent.includes('Riwayat Kepala Keluarga'));
                tab?.click();
            })()
        `);
        await tidur(500);

        const kosong = await nilai(`
            document.body.textContent.includes('Belum pernah berganti kepala keluarga')
        `);
        periksa('keadaan kosong dinyatakan apa adanya', kosong === true);

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
