/**
 * Uji peramban untuk form Penanaman yang berpusat pada Poktan.
 *
 * Mengapa uji peramban: seluruh isi form ini MENURUN dari satu pilihan ke
 * pilihan berikutnya, dan rantai itu hidup di Alpine. Uji string hanya dapat
 * memastikan atributnya tertulis rapi di sumbernya - dan atribut yang
 * tertulis rapi tetapi tidak pernah menyala terlihat persis sama.
 *
 * Yang dijaga:
 *
 * 1. Isian lama (lahan, petani, luas_tanam) benar-benar sudah dicabut.
 * 2. Memilih poktan mengisi Jumlah Anggota dan Luas Lahan yang TERKUNCI.
 * 3. Komoditas menyaring benih: milik poktan itu, komoditas itu, stok masih
 *    ada. Benih padi tidak boleh muncul pada penanaman jagung.
 * 4. Volume benih dibatasi sisa stoknya, bukan dibiarkan bebas.
 * 5. Belum Ditanam terhitung sendiri, dan realisasi yang melebihi lahan
 *    ditegur.
 *
 * Preseden yang membenarkan cara uji ini: penguncian parameter primer pernah
 * membaca variabel yang tidak pernah ada dan lolos seluruh uji string, sebab
 * atributnya memang tertulis rapi di markup (agents/notes.md bagian 6).
 *
 * Dijalankan lewat Edge headless dan protokol DevTools, TANPA menambah
 * dependensi. Menuntut peladen hidup di 127.0.0.1:8099.
 *
 *   php artisan serve --port=8099
 *   node tests/Browser/uji-form-penanaman.mjs
 */
import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { setTimeout as tidur } from 'node:timers/promises';

const ASAL = 'http://127.0.0.1:8099';
const PORT_DEVTOOLS = 9345;

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
            Mengganti nilai kontrol sungguhan beserta peristiwanya.

            Alpine mendengarkan `input`, bukan sekadar perubahan `.value`.
            Menyetel `.value` saja tidak akan pernah memicu apa pun, dan uji
            ini akan hijau selamanya tanpa menguji apa-apa.
        */
        const modalPenanaman = `document.querySelector('#judul-formTambahPenanaman').closest('[role="dialog"]')`;

        const isiKontrol = async (namaIsian, nilaiBaru) => nilai(`(() => {
            const modal = ${modalPenanaman};
            const el = modal.querySelector('[name="${namaIsian}"]');

            if (! el) {
                return 'tidak ada';
            }

            el.value = ${JSON.stringify(nilaiBaru)};
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));

            return el.value;
        })()`);

        /*
            Memilih poktan lewat `pilih-cari`.

            Opsinya diklik sungguhan, bukan nilainya disuntikkan, sebab yang
            diuji justru rantai peristiwanya: klik memanggil `pilih()`, yang
            memancarkan `change` pada isian nilai, yang lalu ditangkap
            `@change` milik pemanggil. Menyuntikkan nilai langsung melewati
            seluruh rantai itu dan uji ini kehilangan gunanya.
        */
        const pilihPoktan = async (urutan) => nilai(`(() => {
            const modal = ${modalPenanaman};
            const nilaiEl = modal.querySelector('[name="poktan_id"]');
            const wadah = nilaiEl.closest('[x-data]');

            wadah.querySelector('button[type="button"]').click();

            return new Promise((selesai) => setTimeout(() => {
                const opsi = [...wadah.querySelectorAll('[role="option"], li button, ul button')];
                const dipilih = opsi[${urutan}];

                if (! dipilih) {
                    selesai('opsi tidak ditemukan, ada ' + opsi.length);

                    return;
                }

                dipilih.click();
                setTimeout(() => selesai('ok'), 300);
            }, 300));
        })()`);

        const keadaan = async () => JSON.parse(await nilai(`(() => {
            const modal = ${modalPenanaman};
            const teks = modal.innerText;
            const benih = modal.querySelector('select[name="saprotan_id"]');
            const volume = modal.querySelector('[name="volume_benih"]');

            const terlihat = (el) => {
                if (! el) {
                    return false;
                }

                const k = el.getBoundingClientRect();

                return k.width > 0 && k.height > 0;
            };

            return JSON.stringify({
                teks: teks,
                benihTerlihat: terlihat(benih),
                opsiBenih: benih ? [...benih.options].map((o) => o.textContent.trim()) : [],
                volumeTerlihat: terlihat(volume),
                volumeMax: volume ? volume.getAttribute('max') : null,
                volumeLumpuh: volume ? volume.disabled : null,
                realisasiMax: modal.querySelector('[name="realisasi_tanam"]')?.getAttribute('max') ?? null,
                // Isian yang TIDAK boleh ada: penanaman berpusat pada poktan.
                adaLahan: !! modal.querySelector('[name="lahan_id"]'),
                adaPetani: !! modal.querySelector('[name="petani"]'),
                adaLuasTanam: !! modal.querySelector('[name="luas_tanam"]'),
                // Periode tanam wajib memakai pemilih BULAN, bukan tanggal.
                tipePeriode: modal.querySelector('[name="periode_tanam"]')?.type ?? null,
                adaTanggalPenuh: !! modal.querySelector('[name="tanggal_tanam"]'),
                adaUnggahan: !! modal.querySelector('[name="dokumen_pendukung"]'),
            });
        })()`));

        await buka('/penanaman');
        await nilai(`window.dispatchEvent(new CustomEvent('buka-modal', { detail: 'formTambahPenanaman' }))`);
        await tidur(700);

        // ------------------------------------------------------------------
        // 1. Isian lama sudah tidak ada
        // ------------------------------------------------------------------
        const awal = await keadaan();

        periksa('isian lahan sudah dicabut', awal.adaLahan === false);
        periksa('isian petani sudah dicabut', awal.adaPetani === false);
        periksa('isian luas_tanam berganti realisasi_tanam', awal.adaLuasTanam === false);

        periksa('tanggal tanam berganti periode bulan', awal.adaTanggalPenuh === false);
        periksa(
            'periode tanam memakai pemilih bulan',
            awal.tipePeriode === 'month',
            `type=${awal.tipePeriode}, penanaman satu hamparan berhari-hari sehingga tanggal pasti memaksa petugas menebak`
        );
        periksa(
            'tersedia unggahan dokumen penanaman',
            awal.adaUnggahan === true,
            'berita acara tanam adalah bukti yang paling sering diminta saat pemeriksaan'
        );

        periksa(
            'jumlah anggota belum terisi sebelum poktan dipilih',
            awal.teks.includes('Terisi setelah kelompok tani dipilih'),
            'ajakan mengisi wajib tampil, bukan angka nol yang menyesatkan'
        );

        periksa(
            'benih belum ditawarkan sebelum poktan dan komoditas dipilih',
            awal.teks.includes('Pilih kelompok tani dan komoditas lebih dulu'),
            'dropdown kosong tanpa keterangan membuat petugas menyangka data hilang'
        );

        // ------------------------------------------------------------------
        // 2. Memilih poktan mengisi tiga angka terkunci
        // ------------------------------------------------------------------
        const hasilPilih = await pilihPoktan(0);
        periksa('opsi poktan dapat diklik', hasilPilih === 'ok', String(hasilPilih));

        const sesudahPoktan = await keadaan();

        // POKTAN MEKAR JAYA: 3 anggota aktif, 4,25 ha lahan.
        periksa(
            'jumlah anggota terisi dari poktan',
            sesudahPoktan.teks.includes('3 orang'),
            'dihitung dari anggota aktif beserta ketuanya'
        );

        periksa(
            'luas lahan terisi dari poktan',
            sesudahPoktan.teks.includes('4,25'),
            'akumulasi lahan ketua dan seluruh anggota aktif'
        );

        periksa(
            'realisasi tanam dibatasi lahan yang tersedia',
            sesudahPoktan.realisasiMax === '4.25',
            `max=${sesudahPoktan.realisasiMax}, seluruh 4,25 ha kembali tersedia sebab semua panennya sudah tuntas`
        );

        // ------------------------------------------------------------------
        // 3. Komoditas menyaring benih
        // ------------------------------------------------------------------
        await isiKontrol('komoditas_id', '1');
        await tidur(400);

        const jagung = await keadaan();

        periksa('memilih komoditas memunculkan pilihan benih', jagung.benihTerlihat === true);

        periksa(
            'hanya benih jagung milik poktan ini yang ditawarkan',
            jagung.opsiBenih.some((o) => o.includes('BENIH JAGUNG HIBRIDA')),
            jagung.opsiBenih.join(' | ')
        );

        // INTI PENYARINGAN. Benih padi milik poktan yang sama TIDAK boleh
        // muncul pada penanaman jagung, dan benih jagung yang stoknya habis
        // juga tidak.
        periksa(
            'benih komoditas lain tidak ikut ditawarkan',
            ! jagung.opsiBenih.some((o) => o.includes('PADI')),
            jagung.opsiBenih.join(' | ')
        );

        periksa(
            'benih yang stoknya habis tidak ditawarkan',
            ! jagung.opsiBenih.some((o) => o.includes('BENIH JAGUNG LOKAL')),
            'BENIH JAGUNG LOKAL sudah habis terpakai penanaman lain'
        );

        periksa(
            'label benih menyebut sisa stoknya',
            jagung.opsiBenih.some((o) => o.includes('sisa')),
            jagung.opsiBenih.join(' | ')
        );

        // Berganti ke PADI: daftarnya wajib ikut berganti, bukan tetap.
        await isiKontrol('komoditas_id', '2');
        await tidur(400);

        const padi = await keadaan();

        periksa(
            'mengganti komoditas mengganti daftar benih',
            padi.opsiBenih.some((o) => o.includes('BENIH PADI IR64'))
                && ! padi.opsiBenih.some((o) => o.includes('BENIH JAGUNG HIBRIDA')),
            padi.opsiBenih.join(' | ')
        );

        // ------------------------------------------------------------------
        // 4. Volume benih dibatasi sisa stok
        // ------------------------------------------------------------------
        await isiKontrol('saprotan_id', '4');
        await tidur(400);

        const sesudahBenih = await keadaan();

        periksa('memilih benih memunculkan isian volume', sesudahBenih.volumeTerlihat === true);
        periksa('volume benih tidak lumpuh setelah benih dipilih', sesudahBenih.volumeLumpuh === false);

        // BENIH PADI IR64: 80 kg diterima, 20 kg terpakai, sisa 60 kg.
        // Tanpa batas ini, 80 kg benih dapat dipakai untuk penanaman senilai
        // 400 kg dan tidak ada yang menegur.
        periksa(
            'volume benih dibatasi sisa stoknya',
            sesudahBenih.volumeMax === '60',
            `max=${sesudahBenih.volumeMax}, seharusnya 60`
        );

        /*
         * Sufiks satuan TIDAK menabrak tombol naik-turun bawaan input number.
         *
         * Ditemukan pemilik proyek lewat tangkapan layar: nama penuh
         * "Kilogram" menempati sudut kanan yang sama dengan tombol itu,
         * sehingga keduanya bertumpuk dan angkanya sulit dibaca.
         *
         * Uji string tidak akan pernah melihatnya - markupnya memang tertulis
         * rapi. Yang membedakan hanya geometri di layar.
         */
        const geometriSufiks = JSON.parse(await nilai(`(() => {
            const modal = ${modalPenanaman};
            const isian = modal.querySelector('[name="volume_benih"]');
            const sufiks = isian.parentElement.querySelector('span');
            const a = isian.getBoundingClientRect();
            const b = sufiks.getBoundingClientRect();

            return JSON.stringify({
                teks: sufiks.textContent.trim(),
                // Tombol naik-turun Chromium selebar ~17px di tepi kanan.
                jarakDariTepi: Math.round(a.right - b.right),
            });
        })()`));

        periksa(
            'sufiks satuan memakai simbol, bukan nama penuh',
            geometriSufiks.teks === 'kg',
            `sufiks="${geometriSufiks.teks}", seharusnya "kg" - nama penuh menabrak tombol naik-turun`
        );

        periksa(
            'sufiks satuan tidak menabrak tombol naik-turun',
            geometriSufiks.jarakDariTepi >= 20,
            `jarak dari tepi kanan ${geometriSufiks.jarakDariTepi}px, tombol naik-turun butuh sekitar 17px`
        );

        // ------------------------------------------------------------------
        // 5. Belum Ditanam terhitung sendiri
        // ------------------------------------------------------------------
        await isiKontrol('realisasi_tanam', '1.45');
        await tidur(400);

        const sesudahRealisasi = await keadaan();

        // 4,25 ha tersedia dikurangi 1,45 ha yang ditanam = 2,8 ha.
        // Naik dari 3,45 sejak panen bertahap dicabut 2026-08-24: penanaman
        // #3 kini tuntas dipanen, sehingga lahannya kembali seluruhnya.
        periksa(
            'belum ditanam terhitung dari lahan tersedia',
            sesudahRealisasi.teks.includes('2,8 ha'),
            '4,25 ha tersedia dikurangi 1,45 ha yang ditanam = 2,8 ha, tampil tanpa diketik petugas'
        );

        // Melebihi lahan wajib ditegur, bukan diterima diam-diam.
        await isiKontrol('realisasi_tanam', '99');
        await tidur(400);

        const melebihi = await keadaan();

        periksa(
            'realisasi melebihi lahan ditegur',
            melebihi.teks.includes('Melebihi lahan yang belum ditanami'),
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
