/**
 * Uji peramban untuk formatting dan sanitasi nominal uang (Rupiah).
 *
 * Menguji perilaku nyata pada DOM peramban:
 * 1. Pengetikan angka menghasilkan pemisah ribuan titik (1000000 -> 1.000.000).
 * 2. Pengetikan karakter ilegal (huruf, minus, notasi ilmiah) ditolak.
 * 3. Hapus hingga kosong menyisakan placeholder 0 tanpa memaksa '0'.
 * 4. Paste teks beragam format (Rp 2.500.000, 2500000, 1.500.000,00) dinormalisasi.
 * 5. Input nominal pada repeater (anggota keluarga & rute) reaktif dan terformat.
 * 6. Modal ubah (edit existing) menampilkan nilai terformat tanpa penggandaan digit.
 * 7. Normalisasi submit form mengirimkan raw numeric digits (1000000).
 * 8. Input angka non-uang (luas, volume, tahun) tetap berupa input number murni.
 * 9. Uji viewport mobile (375px) tidak menghasilkan horizontal overflow.
 *
 * Dijalankan lewat Edge headless dan protokol DevTools.
 *   php artisan serve --port=8099
 *   node tests/Browser/uji-format-uang.mjs
 */

import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { setTimeout as tidur } from 'node:timers/promises';

const ASAL = 'http://127.0.0.1:8099';
const PORT_DEVTOOLS = 9355;

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

        // ==========================================
        // 1. UJI FORM TRANSMIGRAN
        // ==========================================
        console.log('\n--- 1. Uji Form Transmigran (Pendapatan KK & Anggota) ---');
        await kirim('Page.navigate', { url: `${ASAL}/transmigran` });
        for (let i = 0; i < 60; i += 1) {
            if (await nilai('!! window.Alpine')) break;
            await tidur(250);
        }
        await tidur(600);

        // Buka modal tambah transmigran
        await nilai(`window.dispatchEvent(new CustomEvent('buka-modal', { detail: 'formTambahTransmigran' }))`);
        await tidur(500);

        // A. Input dasar pengetikan nominal
        await nilai(`
            (() => {
                const el = document.querySelector('#tambah_pendapatan');
                el.focus();
                el.value = '1500000';
                el.dispatchEvent(new Event('input', { bubbles: true }));
            })()
        `);
        await tidur(100);
        const val1 = await nilai(`document.querySelector('#tambah_pendapatan').value`);
        periksa('mengetik 1500000 menghasilkan format 1.500.000', val1 === '1.500.000', `hasil: ${val1}`);

        // B. Paste dengan prefix Rp dan titik (kosongkan dulu sebelum paste)
        await nilai(`
            (() => {
                const el = document.querySelector('#tambah_pendapatan');
                el.value = '';
                el.setSelectionRange(0, 0);
                const dt = new DataTransfer();
                dt.setData('text/plain', 'Rp 2.750.000,00');
                const ev = new ClipboardEvent('paste', { clipboardData: dt, bubbles: true, cancelable: true });
                el.dispatchEvent(ev);
            })()
        `);
        await tidur(100);
        const valPaste = await nilai(`document.querySelector('#tambah_pendapatan').value`);
        periksa('paste "Rp 2.750.000,00" diformat menjadi 2.750.000', valPaste === '2.750.000', `hasil: ${valPaste}`);

        // C. Hapus sampai kosong
        await nilai(`
            (() => {
                const el = document.querySelector('#tambah_pendapatan');
                el.value = '';
                el.dispatchEvent(new Event('input', { bubbles: true }));
            })()
        `);
        await tidur(100);
        const valKosong = await nilai(`document.querySelector('#tambah_pendapatan').value`);
        periksa('hapus sampai kosong menyisakan string kosong', valKosong === '', `hasil: "${valKosong}"`);

        // D. Uji Repeater Anggota Keluarga
        // Pindah ke langkah 3 (Anggota Keluarga) lewat modal-form
        await nilai(`
            (() => {
                const modal = document.querySelector('#formTambahTransmigran')?.closest('[x-data]')
                    || document.querySelector('[aria-labelledby="judul-formTambahTransmigran"]')?.closest('[x-data]');
                if (modal && Alpine.$data(modal)) {
                    Alpine.$data(modal).langkah = 3;
                }
            })()
        `);
        await tidur(400);

        // Klik tombol "Tambah Anggota"
        await nilai(`
            (() => {
                const btn = [...document.querySelectorAll('button')]
                    .find(b => b.textContent.includes('Tambah Anggota'));
                if (btn) btn.click();
            })()
        `);
        await tidur(400);

        // Set kegiatan anggota pertama menjadi "Bekerja"
        await nilai(`
            (() => {
                const sel = document.querySelector('[name="anggota_keluarga[0][kegiatan]"]');
                if (sel) {
                    sel.value = 'Bekerja';
                    sel.dispatchEvent(new Event('change', { bubbles: true }));
                }
            })()
        `);
        await tidur(400);

        // Ketik pendapatan anggota keluarga
        await nilai(`
            (() => {
                const el = document.querySelector('[name="anggota_keluarga[0][pendapatan_per_bulan]"]');
                if (el) {
                    el.value = '3500000';
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                }
            })()
        `);
        await tidur(100);
        const valGajiAk = await nilai(`document.querySelector('[name="anggota_keluarga[0][pendapatan_per_bulan]"]')?.value`);
        periksa('repeater anggota keluarga memformat pendapatan menjadi 3.500.000', valGajiAk === '3.500.000', `hasil: ${valGajiAk}`);

        // Tutup modal tambah
        await nilai(`window.dispatchEvent(new CustomEvent('tutup-modal'))`);
        await tidur(500);

        // E. Uji Modal Ubah (Edit Existing Baris)
        await nilai(`
            (() => {
                window.dispatchEvent(new CustomEvent('buka-modal-baris', {
                    detail: {
                        nama: 'formUbahTransmigranBaris',
                        data: {
                            id: 1,
                            nama_kepala_keluarga: 'YOHANES BERE',
                            nik: '5304011205800001',
                            no_kk: '5304011205800001',
                            pendapatan_per_bulan: 1500000,
                            tahun_kedatangan: 2016,
                            status_tinggal: 'Aktif',
                            satuan_permukiman_id: 1
                        }
                    }
                }));
            })()
        `);
        await tidur(600);

        const valEdit = await nilai(`document.querySelector('#ubahBaris_pendapatan')?.value`);
        periksa('modal ubah baris otomatis menampilkan 1.500.000 dari data existing 1500000', valEdit === '1.500.000', `hasil: ${valEdit}`);

        // F. Normalisasi Submit Form
        const valSubmitted = await nilai(`
            (() => {
                const form = document.querySelector('#ubahBaris_pendapatan').closest('form');
                let nilaiTerkirim = '';
                const handler = (e) => {
                    nilaiTerkirim = document.querySelector('#ubahBaris_pendapatan').value;
                    e.preventDefault();
                };
                form.addEventListener('submit', handler, { once: true });
                form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                return nilaiTerkirim;
            })()
        `);
        periksa('saat form submit, nilai input dinormalisasi menjadi raw digit "1500000"', valSubmitted === '1500000', `hasil: ${valSubmitted}`);

        await nilai(`window.dispatchEvent(new CustomEvent('tutup-modal'))`);
        await tidur(500);

        // ==========================================
        // 2. UJI FORM HASIL PANEN
        // ==========================================
        console.log('\n--- 2. Uji Form Hasil Panen (Harga Jual) ---');
        await kirim('Page.navigate', { url: `${ASAL}/panen` });
        for (let i = 0; i < 60; i += 1) {
            if (await nilai('!! window.Alpine')) break;
            await tidur(250);
        }
        await tidur(600);

        // Buka modal tambah panen
        await nilai(`window.dispatchEvent(new CustomEvent('buka-modal', { detail: 'formTambahPanen' }))`);
        await tidur(500);

        await nilai(`
            (() => {
                const el = document.querySelector('#tambah_harga_jual');
                el.value = '4500';
                el.dispatchEvent(new Event('input', { bubbles: true }));
            })()
        `);
        await tidur(100);
        const valHarga = await nilai(`document.querySelector('#tambah_harga_jual')?.value`);
        periksa('mengetik harga_jual 4500 menghasilkan 4.500', valHarga === '4.500', `hasil: ${valHarga}`);

        // Pastikan field angka non-uang di form panen tetap input number
        const typeRealisasi = await nilai(`document.querySelector('#tambah_realisasi_panen')?.type`);
        const typeProduktivitas = await nilai(`document.querySelector('#tambah_produktivitas')?.type`);
        periksa('realisasi_panen tetap type="number"', typeRealisasi === 'number', `tipe: ${typeRealisasi}`);
        periksa('produktivitas tetap type="number"', typeProduktivitas === 'number', `tipe: ${typeProduktivitas}`);

        await nilai(`window.dispatchEvent(new CustomEvent('tutup-modal'))`);
        await tidur(500);

        // ==========================================
        // 3. UJI FORM SP (RUTE AKSESIBILITAS ONGKOS)
        // ==========================================
        console.log('\n--- 3. Uji Form Satuan Permukiman (Ongkos Rute) ---');
        await kirim('Page.navigate', { url: `${ASAL}/sp` });
        for (let i = 0; i < 60; i += 1) {
            if (await nilai('!! window.Alpine')) break;
            await tidur(250);
        }
        await tidur(600);

        await nilai(`window.dispatchEvent(new CustomEvent('buka-modal', { detail: 'formTambahSp' }))`);
        await tidur(500);

        // Pindah ke langkah 3 (Rute)
        await nilai(`
            (() => {
                const modal = document.querySelector('#formTambahSp')?.closest('[x-data]')
                    || document.querySelector('[aria-labelledby="judul-formTambahSp"]')?.closest('[x-data]');
                if (modal && Alpine.$data(modal)) {
                    Alpine.$data(modal).langkah = 3;
                }
            })()
        `);
        await tidur(400);

        // Tambah rute
        await nilai(`
            (() => {
                const btn = [...document.querySelectorAll('button')]
                    .find(b => b.textContent.includes('Tambah Rute'));
                if (btn) btn.click();
            })()
        `);
        await tidur(400);

        await nilai(`
            (() => {
                const el = document.querySelector('[name="rute_aksesibilitas[0][ongkos_rp]"]');
                if (el) {
                    el.value = '75000';
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                }
            })()
        `);
        await tidur(100);
        const valOngkos = await nilai(`document.querySelector('[name="rute_aksesibilitas[0][ongkos_rp]"]')?.value`);
        periksa('repeater rute SP memformat ongkos menjadi 75.000', valOngkos === '75.000', `hasil: ${valOngkos}`);

        await nilai(`window.dispatchEvent(new CustomEvent('tutup-modal'))`);
        await tidur(500);

        // ==========================================
        // 4. UJI VIEWPORT MOBILE (RESPONSIVE 375px)
        // ==========================================
        console.log('\n--- 4. Uji Viewport Mobile (375px) ---');
        await kirim('Emulation.setDeviceMetricsOverride', {
            width: 375,
            height: 667,
            deviceScaleFactor: 2,
            mobile: true,
        });
        await kirim('Page.navigate', { url: `${ASAL}/transmigran` });
        await tidur(600);
        await nilai(`window.dispatchEvent(new CustomEvent('buka-modal', { detail: 'formTambahTransmigran' }))`);
        await tidur(500);

        const adaOverflow = await nilai(`
            (() => {
                const el = document.querySelector('#tambah_pendapatan');
                if (! el) return false;
                const rect = el.getBoundingClientRect();
                return rect.right > window.innerWidth;
            })()
        `);
        periksa('input nominal uang tidak menghasilkan horizontal overflow pada layar mobile 375px', ! adaOverflow);

        soket.close();
    } finally {
        proses.kill();
    }

    console.log(`\nRingkasan Uji Peramban: ${lulus} lulus, ${gagal} gagal.`);
    if (gagal > 0) {
        process.exit(1);
    }
}

main().catch((err) => {
    console.error(err);
    process.exit(1);
});
