/**
 * Export Excel untuk halaman Laporan (Task 10.1, 2026-09-05), sepenuhnya di
 * sisi peramban -- keputusan pemilik proyek membalik penundaan 2026-09-04
 * (menunggu spesifikasi hosing Task 11.3 sebelum menambah paket Composer).
 * Lihat `agents/rules.md` 12 poin 11.
 *
 * SATU worksheet per `<table class="tabel-dokumen">` yang DITEMUKAN di
 * dalam kertas laporan -- generik, tanpa kode per-laporan, sebab konvensi
 * komponen (`kerangka-laporan.blade.php`) MEWAJIBKAN `<caption>` sebagai
 * anak pertama tiap tabel (dipakai sebagai nama lembar).
 *
 * `display: true` pada `table_to_sheet` melewati baris yang disembunyikan
 * `x-show` filter Alpine (`filter-laporan.js`) -- itulah Task 10.3 "filter
 * sebelum export": tabel yang DIBACA sudah tersaring, tanpa menduplikasi
 * logika penyaringan di sini.
 *
 * `raw: true` menahan SheetJS menebak tipe sel (heuristiknya memakai kaidah
 * AS -- titik desimal, bukan pemisah ribuan Indonesia -- dan akan salah
 * membaca "1.234" sebagai 1,234 bukan seribu dua ratus tiga puluh empat).
 * Deteksi angka Indonesia dikerjakan sendiri sesudahnya, HANYA pada sel
 * yang benar-benar berpola ribuan/desimal Indonesia -- deretan digit polos
 * (NIK, no_kk, nomor telepon, tahun) SENGAJA dibiarkan teks apa adanya,
 * sebab itu pengenal, bukan kuantitas, dan mengubahnya jadi angka berisiko
 * menghilangkan nol di depan atau presisi.
 *
 * `xlsx` (~1 MB) dimuat lewat `import()` DINAMIS di dalam `keExcel()`,
 * bukan diimpor statis di atas -- pustaka sebesar itu tidak pantas
 * membengkakkan `app.js` yang dimuat di SETIAP halaman padahal cuma
 * dipakai saat tombol "Unduh Excel" benar-benar diklik. Vite memecahnya
 * jadi chunk terpisah secara otomatis.
 */

/**
 * Pola angka Indonesia yang AMAN dikonversi: wajib ada titik pemisah ribuan
 * ATAU koma desimal -- deretan digit polos tanpa keduanya (kode_lahan, NIK,
 * no_kk, telepon, tahun) tidak pernah cocok.
 */
const POLA_ANGKA_ID = /^-?\d{1,3}(\.\d{3})+(,\d+)?$|^-?\d+,\d+$/;

/**
 * Mengubah teks angka Indonesia ("1.234,56") menjadi number JavaScript
 * (1234.56), atau null bila teks tidak berpola angka Indonesia yang aman.
 */
function angkaDariTeks(teks) {
    const t = teks.trim();

    if (!POLA_ANGKA_ID.test(t)) {
        return null;
    }

    const angka = parseFloat(t.replace(/\./g, '').replace(',', '.'));

    return Number.isFinite(angka) ? angka : null;
}

/**
 * Menimpa sel teks pada worksheet dengan tipe number asli di mana teksnya
 * berpola angka Indonesia -- supaya Excel dapat menjumlah/mengurutkannya,
 * bukan sekadar teks yang terbaca seperti angka.
 */
function konversiAngka(XLSX, ws) {
    if (!ws['!ref']) {
        return;
    }

    const jangkauan = XLSX.utils.decode_range(ws['!ref']);

    for (let baris = jangkauan.s.r; baris <= jangkauan.e.r; baris++) {
        for (let kolom = jangkauan.s.c; kolom <= jangkauan.e.c; kolom++) {
            const ref = XLSX.utils.encode_cell({ r: baris, c: kolom });
            const sel = ws[ref];

            if (!sel || sel.t !== 's' || typeof sel.v !== 'string') {
                continue;
            }

            const angka = angkaDariTeks(sel.v);

            if (angka !== null) {
                sel.t = 'n';
                sel.v = angka;
                delete sel.w; // teks terformat lama tak lagi cocok dengan tipe baru
            }
        }
    }
}

/**
 * Nama lembar yang sah bagi Excel: maks 31 karakter, tanpa `\ / ? * [ ]`,
 * tak boleh kembar dalam satu berkas (mis. blok berulang per SP pada
 * Monografi SP) -- disambiguasi dengan angka urut.
 */
function namaLembar(mentah, dipakai) {
    let nama = (mentah || 'Lembar').replace(/[\\/?*[\]]/g, ' ').trim().slice(0, 31) || 'Lembar';

    let final = nama;
    let n = 2;
    while (dipakai.has(final)) {
        const akhiran = ' (' + n + ')';
        final = nama.slice(0, 31 - akhiran.length) + akhiran;
        n++;
    }

    dipakai.add(final);

    return final;
}

/**
 * Membangun dan mengunduh satu berkas .xlsx dari seluruh
 * `table.tabel-dokumen` di dalam `root` -- SATU lembar per tabel, nama
 * lembar dari `<caption>`-nya. Baris yang tersaring filter Alpine aktif
 * (`x-show`) tidak ikut (Task 10.3).
 *
 * @param {HTMLElement} root Elemen pembungkus laporan (mis. `<article>`)
 * @param {string} slug Slug laporan, dipakai sebagai nama berkas
 * @returns {Promise<void>}
 */
async function keExcel(root, slug) {
    const tabel = root.querySelectorAll('table.tabel-dokumen');

    if (tabel.length === 0) {
        window.alert('Tidak ada tabel untuk diunduh pada laporan ini.');

        return;
    }

    const { default: XLSX } = await import('xlsx');

    const wb = XLSX.utils.book_new();
    const dipakai = new Set();

    tabel.forEach((tbl) => {
        const ws = XLSX.utils.table_to_sheet(tbl, { display: true, raw: true });
        konversiAngka(XLSX, ws);

        const caption = tbl.querySelector('caption')?.textContent || 'Lembar';
        XLSX.utils.book_append_sheet(wb, ws, namaLembar(caption, dipakai));
    });

    const tanggal = new Date().toISOString().slice(0, 10);
    XLSX.writeFile(wb, slug + '-' + tanggal + '.xlsx');
}

export default { keExcel, angkaDariTeks, namaLembar };
