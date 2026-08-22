/**
 * Uji peramban untuk form Hasil Panen yang dirombak.
 *
 * Mengapa uji peramban: satu pilihan mengisi DELAPAN isian, dan dua angka
 * lagi terhitung dari isian yang diketik. Seluruh rantai itu hidup di Alpine.
 * Uji string hanya dapat memastikan atributnya tertulis rapi di sumbernya -
 * dan atribut yang tertulis rapi tetapi tidak pernah menyala terlihat persis
 * sama.
 *
 * Yang dijaga:
 *
 * 1. Isian lama benar-benar dicabut: kualitas, volume, petani, tanggal penuh.
 * 2. Periode panen memakai pemilih BULAN, bukan tanggal.
 * 3. Memilih penanaman mengisi kelompok tani, anggota, satuan, dan poktan_id.
 * 4. Belum Dipanen terhitung dari sisa penanaman, bukan realisasi tanam mentah.
 * 5. Produksi terhitung dari Hasil Panen x Produktivitas.
 * 6. Panen melebihi sisa penanaman ditegur.
 *
 * Preseden yang membenarkan cara uji ini: penguncian parameter primer pernah
 * membaca variabel yang tidak pernah ada dan lolos seluruh uji string, sebab
 * atributnya memang tertulis rapi di markup (agents/notes.md bagian 6).
 *
 * Dijalankan lewat Edge headless dan protokol DevTools, TANPA menambah
 * dependensi. Menuntut peladen hidup di 127.0.0.1:8099.
 *
 *   php artisan serve --port=8099
 *   node tests/Browser/uji-form-panen.mjs
 */
import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { setTimeout as tidur } from 'node:timers/promises';

const ASAL = 'http://127.0.0.1:8099';
const PORT_DEVTOOLS = 9347;

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

        const modalPanen = `document.querySelector('#judul-formTambahPanen').closest('[role="dialog"]')`;

        const isiKontrol = async (namaIsian, nilaiBaru) => nilai(`(() => {
            const modal = ${modalPanen};
            const el = modal.querySelector('[name="${namaIsian}"]');

            if (! el) { return 'tidak ada'; }

            el.value = ${JSON.stringify(nilaiBaru)};
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));

            return el.value;
        })()`);

        const pilihPenanaman = async (urutan) => nilai(`(() => {
            const modal = ${modalPanen};
            const wadah = modal.querySelector('[name="penanaman_id"]').closest('[x-data]');

            wadah.querySelector('button[type="button"]').click();

            return new Promise((selesai) => setTimeout(() => {
                const opsi = [...wadah.querySelectorAll('[role="option"], li button, ul button')];
                const dipilih = opsi[${urutan}];

                if (! dipilih) { selesai('opsi tidak ada'); return; }

                dipilih.click();
                setTimeout(() => selesai('ok'), 300);
            }, 300));
        })()`);

        const keadaan = async () => JSON.parse(await nilai(`(() => {
            const modal = ${modalPanen};
            const ambil = (n) => modal.querySelector('[name="' + n + '"]');

            return JSON.stringify({
                teks: modal.innerText,
                produksiTersembunyi: ambil('produksi') ? ambil('produksi').value : null,
                satuanTersembunyi: ambil('satuan_id') ? ambil('satuan_id').value : null,
                poktanTersembunyi: ambil('poktan_id') ? ambil('poktan_id').value : null,
                maxPanen: ambil('realisasi_panen') ? ambil('realisasi_panen').getAttribute('max') : null,
                adaKualitas: !! ambil('kualitas'),
                adaVolume: !! ambil('volume'),
                adaPetani: !! ambil('transmigran_id'),
                adaTanggalPenuh: !! ambil('tanggal_panen'),
                tipePeriode: ambil('periode_panen') ? ambil('periode_panen').type : null,
                adaUnggahan: !! ambil('dokumen_pendukung'),
            });
        })()`));


        await buka('/panen');
        await nilai(`window.dispatchEvent(new CustomEvent('buka-modal', { detail: 'formTambahPanen' }))`);
        await tidur(700);

        const awal = await keadaan();

        periksa('isian kualitas sudah dicabut', awal.adaKualitas === false);
        periksa('isian volume berganti produksi', awal.adaVolume === false);
        periksa('isian petani sudah dicabut', awal.adaPetani === false);
        periksa('tanggal penuh berganti periode bulan', awal.adaTanggalPenuh === false);
        periksa(
            'periode panen memakai pemilih bulan',
            awal.tipePeriode === 'month',
            `type=${awal.tipePeriode}, panen satu hamparan berhari-hari sehingga tanggal pasti memaksa petugas menebak`
        );

        periksa(
            'tersedia unggahan dokumen panen',
            awal.adaUnggahan === true,
            'berita acara panen dan bukti timbangan perlu tempat menyimpan'
        );

        periksa(
            'delapan isian belum terisi sebelum penanaman dipilih',
            awal.teks.includes('Terisi setelah penanaman dipilih'),
            'ajakan mengisi wajib tampil, bukan angka nol yang menyesatkan'
        );

        const hasil = await pilihPenanaman(2);
        periksa('opsi penanaman dapat diklik', hasil === 'ok', String(hasil));

        const sesudah = await keadaan();

        periksa('kelompok tani terbaca dari penanaman', sesudah.teks.includes('POKTAN'));
        periksa('jumlah anggota terbaca dari penanaman', sesudah.teks.includes('orang'));
        periksa(
            'satuan mengikuti komoditas penanamannya',
            sesudah.satuanTersembunyi !== '' && sesudah.satuanTersembunyi !== null,
            `satuan=${sesudah.satuanTersembunyi}`
        );
        periksa(
            'poktan ikut terkirim tanpa diketik',
            sesudah.poktanTersembunyi !== '' && sesudah.poktanTersembunyi !== null,
            `poktan_id=${sesudah.poktanTersembunyi}`
        );
        periksa(
            'hasil panen dibatasi luas yang belum dipanen',
            sesudah.maxPanen !== null,
            `max=${sesudah.maxPanen}`
        );


        await isiKontrol('realisasi_panen', '0.50');
        await isiKontrol('puso', '0.10');
        await tidur(400);

        const sesudahLuas = await keadaan();

        periksa(
            'belum dipanen terhitung dari sisa penanaman, bukan realisasi tanam',
            sesudahLuas.teks.includes('0,2 ha') || sesudahLuas.teks.includes('0.2 ha'),
            'penanaman #3 menyisakan 0,80 ha; dikurangi 0,50 panen dan 0,10 puso jadi 0,20 - bukan 2,00 ha realisasi tanamnya'
        );

        await isiKontrol('produktivitas', '3');
        await tidur(400);

        const sesudahProd = await keadaan();

        periksa(
            'produksi terhitung dari hasil panen dikali produktivitas',
            sesudahProd.produksiTersembunyi === '1.5',
            `produksi=${sesudahProd.produksiTersembunyi}, seharusnya 0,50 x 3 = 1,5`
        );

        await isiKontrol('realisasi_panen', '99');
        await tidur(400);

        const melebihi = await keadaan();

        periksa(
            'panen melebihi sisa penanaman ditegur',
            melebihi.teks.includes('melebihi luas yang belum dipanen'),
            'tanpa teguran, angka mustahil tersimpan tanpa ada yang menyadari'
        );

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
