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
                nilaiPanen: ambil('realisasi_panen') ? ambil('realisasi_panen').value : null,
                nilaiPuso: ambil('puso') ? ambil('puso').value : null,
                pusoWajib: ambil('puso') ? ambil('puso').required : null,
                produktivitasLumpuh: ambil('produktivitas') ? ambil('produktivitas').disabled : null,
                adaBelumDipanen: modal.innerText.includes('Belum Dipanen'),
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

        // Dropdown kini HANYA menawarkan penanaman yang belum dipanen
        // (sejak 2026-08-24), sehingga indeks 0 adalah satu-satunya pilihan.
        const hasil = await pilihPenanaman(0);
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

        // Penanaman #6 seluas 1 ha. Memilihnya langsung mengisi hasil panen
        // dengan seluruh luas dan puso nol: panen mulus lebih lazim daripada
        // gagal, sehingga itulah bawaan yang paling jarang perlu diubah.
        periksa(
            'memilih penanaman langsung menutup seluruh luasnya',
            sesudah.nilaiPanen === '1' && sesudah.nilaiPuso === '0',
            `panen=${sesudah.nilaiPanen}, puso=${sesudah.nilaiPuso}, seharusnya 1 dan 0`
        );

        periksa(
            'hasil panen dibatasi luas yang ditanam',
            sesudah.maxPanen === '1',
            `max=${sesudah.maxPanen}, seharusnya 1`
        );

        /*
         * Sufiks produktivitas TIDAK menabrak tombol naik-turun.
         *
         * Nama penuh "Kilogram/ha" menempati sudut kanan yang sama dengan
         * tombol bawaan input number, sehingga keduanya bertumpuk dan angkanya
         * sulit dibaca. Ditemukan pemilik proyek lewat tangkapan layar pada
         * isian sejenis di form penanaman.
         *
         * Uji string tidak akan pernah melihatnya - markupnya tertulis rapi.
         * Yang membedakan hanya geometri di layar.
         */
        const sufiksProduktivitas = JSON.parse(await nilai(`(() => {
            const modal = ${modalPanen};
            const isian = modal.querySelector('[name="produktivitas"]');
            const sufiks = isian.parentElement.querySelector('span');
            const a = isian.getBoundingClientRect();
            const b = sufiks.getBoundingClientRect();

            return JSON.stringify({
                teks: sufiks.textContent.trim().replace(/\\s+/g, ''),
                // Tombol naik-turun Chromium selebar ~17px di tepi kanan.
                jarakDariTepi: Math.round(a.right - b.right),
            });
        })()`));

        periksa(
            'sufiks produktivitas memakai simbol satuan',
            sufiksProduktivitas.teks === 't/ha',
            `sufiks="${sufiksProduktivitas.teks}", seharusnya "t/ha" - nama penuh menabrak tombol naik-turun`
        );

        periksa(
            'sufiks produktivitas tidak menabrak tombol naik-turun',
            sufiksProduktivitas.jarakDariTepi >= 20,
            `jarak dari tepi kanan ${sufiksProduktivitas.jarakDariTepi}px, tombol naik-turun butuh sekitar 17px`
        );

        // ------------------------------------------------------------------
        // SALING MENGISI: mengetik salah satu menentukan yang lain.
        //
        // Inilah yang menggantikan isian "Belum Dipanen" yang dicabut. Dahulu
        // luas boleh tidak tertutup habis, dan sisanya mengambang tanpa batas
        // waktu - penanaman November 2025 masih menyisakan 0,80 ha sampai
        // Agustus 2026, padahal jagung tidak berdiri sepuluh bulan.
        // ------------------------------------------------------------------
        await isiKontrol('realisasi_panen', '0.6');
        await tidur(400);

        const sesudahPanen = await keadaan();

        periksa(
            'mengetik hasil panen mengisi puso',
            sesudahPanen.nilaiPuso === '0.4',
            `puso=${sesudahPanen.nilaiPuso}, seharusnya 1 - 0,6 = 0,4`
        );

        await isiKontrol('puso', '0.25');
        await tidur(400);

        const sesudahPuso = await keadaan();

        periksa(
            'mengetik puso mengisi hasil panen',
            sesudahPuso.nilaiPanen === '0.75',
            `panen=${sesudahPuso.nilaiPanen}, seharusnya 1 - 0,25 = 0,75`
        );

        periksa(
            'identitas luas tertulis terang-terangan',
            sesudahPuso.teks.includes('ha yang ditanam'),
            'petugas perlu dapat memeriksa sendiri bahwa luasnya tertutup habis'
        );

        await isiKontrol('produktivitas', '3');
        await tidur(400);

        const sesudahProd = await keadaan();

        periksa(
            'produksi terhitung dari hasil panen dikali produktivitas',
            sesudahProd.produksiTersembunyi === '2.25',
            `produksi=${sesudahProd.produksiTersembunyi}, seharusnya 0,75 x 3 = 2,25`
        );

        // ------------------------------------------------------------------
        // GAGAL TOTAL: seluruh luas puso, tidak ada yang dipanen.
        //
        // Produktivitas dilumpuhkan pada keadaan ini, sebab tidak ada yang
        // ditimbang. Memaksanya berarti menuntut petugas mengarang hasil.
        // ------------------------------------------------------------------
        await isiKontrol('realisasi_panen', '0');
        await tidur(400);

        const gagalTotal = await keadaan();

        periksa(
            'gagal total mengalihkan seluruh luas ke puso',
            gagalTotal.nilaiPuso === '1',
            `puso=${gagalTotal.nilaiPuso}, seharusnya seluruh 1 ha`
        );

        periksa(
            'produktivitas dilumpuhkan saat gagal total',
            gagalTotal.produktivitasLumpuh === true,
            'tidak ada yang ditimbang, sehingga memaksa angka berarti mengarang hasil'
        );

        periksa(
            'gagal total dinyatakan terang-terangan',
            gagalTotal.teks.includes('Seluruh hamparan gagal panen'),
            'petugas perlu tahu bahwa yang ia catat adalah kegagalan penuh'
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
