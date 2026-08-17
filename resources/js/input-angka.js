/**
 * Penjaga isian angka.
 *
 * `<input type="number">` memang menolak huruf, tetapi masih menerima tiga hal
 * yang tidak pernah dimaksudkan sebagai angka pada sistem ini:
 *
 *   1. Notasi ilmiah lewat huruf `e`, contoh `1e5`.
 *   2. Tanda `+` dan `-`, padahal tidak ada isian yang boleh bernilai negatif:
 *      luas, jumlah, tahun, dan harga seluruhnya bernilai nol ke atas.
 *   3. Tempelan teks dari papan klip, yang tidak melewati penyaringan ketikan
 *      sama sekali.
 *
 * Ketiganya baru tertangkap saat penyimpanan, padahal petugas di lapangan
 * sudah telanjur mengisi seluruh formulir. Penjaga ini menolaknya sejak
 * tombol ditekan.
 *
 * Dipasang sebagai satu pendengar pada `document`, bukan disalin ke dua puluh
 * lima isian. Pendengar tunggal juga ikut melayani isian yang baru muncul di
 * dalam modal, yang belum ada ketika halaman pertama kali dimuat.
 */

/** Tombol kendali yang tidak boleh ikut ditolak. */
const TOMBOL_KENDALI = [
    'Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'Home', 'End',
    'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
];

/**
 * Menentukan apakah sebuah isian menerima pecahan.
 *
 * Dibaca dari atribut `step`: isian berlangkah bilangan bulat hanya menerima
 * angka bulat, sedangkan yang berlangkah pecahan seperti `0.01` menerima koma
 * desimal. Dengan begitu tahun tetap menolak titik, sementara luas lahan tidak.
 *
 * @param {HTMLInputElement} isian Isian yang diperiksa
 * @returns {boolean} True bila isian menerima pecahan
 */
function menerimaPecahan(isian) {
    const langkah = isian.getAttribute('step');

    if (langkah === null || langkah === 'any') {
        // Tanpa step, bawaan HTML adalah 1 yang berarti bilangan bulat.
        return langkah === 'any';
    }

    return Number(langkah) % 1 !== 0;
}

/**
 * Membersihkan teks tempelan menjadi angka yang sah.
 *
 * @param {string} teks Teks mentah dari papan klip
 * @param {boolean} bolehPecahan True bila titik desimal diperbolehkan
 * @returns {string} Teks yang hanya memuat angka
 */
function bersihkan(teks, bolehPecahan) {
    // Koma diterima sebagai titik desimal, sebab papan tik ponsel dan kebiasaan
    // penulisan Indonesia memakai koma.
    const disatukan = bolehPecahan ? teks.replace(/,/g, '.') : teks;
    const pola = bolehPecahan ? /[^0-9.]/g : /[^0-9]/g;

    let hasil = disatukan.replace(pola, '');

    // Titik desimal hanya boleh satu.
    if (bolehPecahan) {
        const bagian = hasil.split('.');

        if (bagian.length > 2) {
            hasil = bagian.shift() + '.' + bagian.join('');
        }
    }

    return hasil;
}

/** Memasang penjaga pada seluruh isian angka, termasuk yang muncul kemudian. */
export function pasangPenjagaAngka() {
    document.addEventListener('keydown', (peristiwa) => {
        const isian = peristiwa.target;

        if (! (isian instanceof HTMLInputElement) || isian.type !== 'number') {
            return;
        }

        // Pintasan papan tik seperti Ctrl+A dan Ctrl+V dibiarkan lewat.
        if (peristiwa.ctrlKey || peristiwa.metaKey || TOMBOL_KENDALI.includes(peristiwa.key)) {
            return;
        }

        const bolehPecahan = menerimaPecahan(isian);
        const sah = bolehPecahan ? /^[0-9.,]$/ : /^[0-9]$/;

        if (! sah.test(peristiwa.key)) {
            peristiwa.preventDefault();

            return;
        }

        // Titik desimal kedua ditolak, sebab menghasilkan nilai tidak sah yang
        // dibaca peramban sebagai kosong tanpa pesan apa pun.
        if (bolehPecahan && (peristiwa.key === '.' || peristiwa.key === ',')
            && isian.value.includes('.')) {
            peristiwa.preventDefault();
        }
    });

    document.addEventListener('paste', (peristiwa) => {
        const isian = peristiwa.target;

        if (! (isian instanceof HTMLInputElement) || isian.type !== 'number') {
            return;
        }

        const mentah = (peristiwa.clipboardData ?? window.clipboardData)?.getData('text') ?? '';
        const bersih = bersihkan(mentah, menerimaPecahan(isian));

        peristiwa.preventDefault();

        if (bersih === '') {
            return;
        }

        // Disisipkan lewat setRangeText agar posisi kursor dan riwayat urung
        // tetap wajar, lalu peristiwa input dipicu supaya Alpine ikut membaca.
        isian.setRangeText(bersih, isian.selectionStart ?? 0, isian.selectionEnd ?? 0, 'end');
        isian.dispatchEvent(new Event('input', { bubbles: true }));
    });
}

export default { pasangPenjagaAngka };
