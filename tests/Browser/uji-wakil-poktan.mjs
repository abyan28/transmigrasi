/**
 * Uji peramban untuk percabangan tiga jalur wakil keluarga di poktan.
 *
 * Mengapa uji peramban, bukan uji Pest: yang menentukan benar tidaknya fitur
 * ini adalah PERILAKU percabangan, bukan keberadaan atribut `name` di HTML.
 * Uji string tidak dapat membedakan isian yang tampil dari yang tersembunyi,
 * tidak dapat memastikan luas lahan benar-benar terisi saat keluarga dipilih,
 * dan tidak dapat menangkap isian tersembunyi yang lupa dinonaktifkan sehingga
 * ikut terkirim. Kekeliruan semacam itu sudah tercatat dua kali pada
 * agents/notes.md bagian 1d.2 dan butir b799.
 *
 * Dijalankan lewat Edge headless dan protokol DevTools, TANPA menambah
 * dependensi. Menuntut peladen hidup di 127.0.0.1:8099.
 *
 *   php artisan serve --port=8099
 *   node tests/Browser/uji-wakil-poktan.mjs
 */

import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { setTimeout as tidur } from 'node:timers/promises';

const ASAL = 'http://127.0.0.1:8099';
const PORT_DEVTOOLS = 9334;

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

        // SELURUH PEMERIKSAAN DIBATASI PADA SATU MODAL.
        //
        // Halaman merender modal tambah dan modal ubah sekaligus, sehingga
        // `document.querySelector` mengambil elemen milik modal mana pun yang
        // kebetulan lebih dulu di DOM. Pemeriksaan keterlihatan lalu membaca
        // modal yang sedang tertutup dan memerah tanpa sebab, atau lebih buruk,
        // menghijau padahal yang benar-benar dibuka petugas keliru.
        //
        // Pembatasnya id isian, yang memang sudah berawalan per modal
        // (`tambah_`/`ubahBaris_`). Untuk isian tanpa id dipakai `closest`
        // terhadap wadah modal yang sedang terbuka.
        const MODAL = '#tambah_nama_ketua';

        // Memilih radio lewat peristiwa asli, bukan menyetel `.checked`, agar
        // Alpine benar-benar bereaksi seperti saat petugas mengklik.
        const pilihRadio = (nama, nilaiRadio) => nilai(`
            (() => {
                const wadah = document.querySelector('${MODAL}')?.closest('[x-data]');
                const el = (wadah ?? document).querySelector('input[name="${nama}"][value="${nilaiRadio}"]');
                if (! el) return false;
                el.click();
                return true;
            })()
        `);

        const terlihat = (pemilih) => nilai(`
            (document.querySelector('${pemilih}')?.getClientRects().length ?? 0) > 0
        `);

        // Menghitung isian hanya di dalam modal yang sedang dibuka.
        const jumlahDiModal = (pemilih) => nilai(`
            (() => {
                const wadah = document.querySelector('${MODAL}')?.closest('form');
                return (wadah ?? document).querySelectorAll('${pemilih}').length;
            })()
        `);

        // Membaca isian tanpa id, tetap dibatasi modal yang sedang dibuka.
        const diModal = (pemilih) => `
            (document.querySelector('${MODAL}')?.closest('form') ?? document)
                .querySelector('${pemilih}')
        `;

        await kirim('Page.enable');
        await kirim('Runtime.enable');

        /* ---------------------------------------------------------------
         | Form poktan: ketua bercabang tiga jalur
         --------------------------------------------------------------- */

        console.log('\nForm ketua poktan:');
        await buka('/poktan');
        await nilai(`window.dispatchEvent(new CustomEvent('buka-modal', { detail: 'formTambahPoktan' }))`);
        await tidur(600);

        const adaRadio = await jumlahDiModal('input[name="asal_ketua"]');
        periksa('tiga pilihan asal ketua dirender', adaRadio === 3, `terbaca ${adaRadio}`);

        // Jalur 1: kepala keluarga. Nama dan NIK dibaca lewat relasi,
        // sehingga isiannya wajib tersembunyi.
        await pilihRadio('asal_ketua', 'Kepala Keluarga');
        await tidur(400);

        periksa(
            'jalur kepala keluarga menyembunyikan isian nama',
            (await terlihat('#tambah_nama_ketua')) === false
        );
        periksa(
            'jalur kepala keluarga menampilkan pemilih keluarga',
            (await nilai(`(${diModal('[name="ketua_transmigran_id"]')}?.closest('div[x-show]')?.getClientRects().length ?? 0) > 0`)) === true
        );

        // Jalur 2: anggota keluarga. Nama, NIK, dan hubungan wajib diketik,
        // sedangkan pemilih keluarga TETAP tampil sebab yang ditunjuk adalah
        // keluarganya. Inilah keadaan yang mustahil dilayani boolean.
        await pilihRadio('asal_ketua', 'Anggota Keluarga');
        await tidur(400);

        periksa(
            'jalur anggota keluarga menampilkan isian nama',
            (await terlihat('#tambah_nama_ketua')) === true
        );
        periksa(
            'jalur anggota keluarga menampilkan isian hubungan',
            (await terlihat('#tambah_hubungan_ketua')) === true
        );
        periksa(
            'jalur anggota keluarga TETAP meminta keluarga yang diwakili',
            (await nilai(`(${diModal('[name="ketua_transmigran_id"]')}?.closest('div[x-show]')?.getClientRects().length ?? 0) > 0`)) === true
        );
        periksa(
            'jalur anggota keluarga menyembunyikan isian luas',
            (await terlihat('#tambah_luas_kering_ketua')) === false
        );

        // Jalur 3: bukan transmigran. Luas diketik, hubungan tidak berlaku.
        await pilihRadio('asal_ketua', 'Bukan Transmigran');
        await tidur(400);

        periksa(
            'jalur non-transmigran menampilkan isian luas',
            (await terlihat('#tambah_luas_kering_ketua')) === true
        );
        periksa(
            'jalur non-transmigran menyembunyikan isian hubungan',
            (await terlihat('#tambah_hubungan_ketua')) === false
        );
        periksa(
            'jalur non-transmigran tidak meminta keluarga yang diwakili',
            (await nilai(`(${diModal('[name="ketua_transmigran_id"]')}?.closest('div[x-show]')?.getClientRects().length ?? 0) > 0`)) === false
        );
        // Isian tersembunyi wajib nonaktif, jika tidak ikut terkirim dan
        // peladen menerima dua sumber identitas yang bertentangan.
        periksa(
            'pemilih keluarga nonaktif saat tersembunyi',
            (await nilai(`${diModal('[name="ketua_transmigran_id"]')}?.disabled === true`)) === true
        );

        /* ---------------------------------------------------------------
         | Form anggota: dua jalur + penurunan luas lahan
         --------------------------------------------------------------- */

        console.log('\nForm anggota poktan:');
        await buka('/poktan/1');
        await nilai(`window.dispatchEvent(new CustomEvent('buka-modal', { detail: 'formTambahAnggota' }))`);
        await tidur(600);

        // Modal anggota punya awalan id sendiri, sehingga pembatasnya berbeda
        // dari modal poktan di atas.
        const FORM_ANGGOTA = `(document.querySelector('#tambah_tanggal_masuk_anggota')?.closest('form') ?? document)`;

        const radioAnggota = await nilai(`${FORM_ANGGOTA}.querySelectorAll('input[name="asal_wakil"]').length`);
        periksa('anggota hanya menawarkan dua jalur', radioAnggota === 2, `terbaca ${radioAnggota}`);

        const adaBukanTransmigran = await nilai(`
            !! ${FORM_ANGGOTA}.querySelector('input[name="asal_wakil"][value="Bukan Transmigran"]')
        `);
        periksa('anggota tidak menawarkan Bukan Transmigran', adaBukanTransmigran === false);

        // Inti pengujian: luas lahan benar-benar terbaca dari keluarga yang
        // dipilih, bukan sekadar isian kosong yang menunggu diketik.
        // Keluarga 2 (MARIA DA COSTA) memiliki bidang campuran 1,25 + 0,75.
        const pilihKeluarga = (id) => nilai(`
            (() => {
                const el = ${FORM_ANGGOTA}.querySelector('[name="transmigran_id"]');
                el.value = '${id}';
                el.dispatchEvent(new Event('change', { bubbles: true }));
                return el.value;
            })()
        `);

        const bacaKotakLahan = () => nilai(`
            (() => {
                const kotak = [...${FORM_ANGGOTA}.querySelectorAll('span')]
                    .find((s) => s.textContent.trim().startsWith('Luas Lahan Usaha Keluarga'));
                return kotak?.parentElement?.textContent?.replace(/\\s+/g, ' ').trim() ?? '';
            })()
        `);

        await pilihKeluarga(2);
        await tidur(500);

        const teksLahan = await bacaKotakLahan();

        periksa(
            'luas lahan terbaca dari keluarga terpilih',
            teksLahan.includes('1.25') && teksLahan.includes('0.75'),
            `terbaca "${teksLahan.slice(0, 120)}"`
        );
        periksa(
            'koordinat lahan ikut terbaca',
            teksLahan.includes('-9.498260'),
            `terbaca "${teksLahan.slice(0, 120)}"`
        );

        // Keluarga tanpa lahan usaha wajib berkata demikian, bukan menampilkan
        // nol yang terbaca seolah lahannya memang nol hektare.
        await pilihKeluarga(4);
        await tidur(500);

        const teksKosong = await bacaKotakLahan();

        periksa(
            'keluarga tanpa lahan dinyatakan apa adanya',
            teksKosong.includes('belum memiliki lahan usaha'),
            `terbaca "${teksKosong.slice(0, 120)}"`
        );

        // Percabangan identitas wakil.
        await pilihRadio('asal_wakil', 'Anggota Keluarga');
        await tidur(400);

        periksa(
            'wakil anggota keluarga menampilkan isian nama dan NIK',
            (await terlihat('#tambah_nama_wakil')) === true
                && (await terlihat('#tambah_nik_wakil')) === true
        );
        periksa(
            'wakil anggota keluarga menampilkan isian hubungan',
            (await terlihat('#tambah_hubungan_dengan_kk')) === true
        );

        await pilihRadio('asal_wakil', 'Kepala Keluarga');
        await tidur(400);

        periksa(
            'wakil kepala keluarga menyembunyikan isian nama',
            (await terlihat('#tambah_nama_wakil')) === false
        );
        periksa(
            'isian nama wakil nonaktif saat tersembunyi',
            (await nilai(`document.querySelector('#tambah_nama_wakil')?.disabled === true`)) === true
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
