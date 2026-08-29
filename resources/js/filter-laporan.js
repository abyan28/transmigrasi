/**
 * Penyaring halaman laporan, dijalankan sepenuhnya di sisi peramban.
 *
 * GitHub Pages tidak melayani query string (notes.md 1b.5), sehingga penyaring
 * laporan TIDAK boleh bergantung pada rute. Blade merender SELURUH baris; modul
 * ini menyembunyikan `<tr>` yang tidak cocok dan menghitung ulang subtotal yang
 * masih tampak.
 *
 * Pola pemasangan (Putaran 3 D3):
 * - `x-sim.kerangka-laporan` memasang `x-data="filterLaporan(konfig)"` pada
 *   `<article>` kertas, sehingga kepala dokumen (kalimat cakupan) DAN isi tabel
 *   sama-sama berada di dalam cakupan yang sama.
 * - Tiap `<tr>` data diberi `data-baris` plus `data-sp`, `data-tahun`,
 *   `data-poktan`, dan seterusnya. `cocok(tr)` membandingkannya dengan keadaan
 *   filter.
 * - Sel subtotal/total memakai `x-text="jumlahTampak($el.closest('table'), 'kolom')"`.
 *
 * Konfig (seluruhnya opsional):
 *   {
 *     sp: [{ id, nama }],          // daftar opsi Satuan Permukiman
 *     tahun: true,                 // pasang sepasang penyaring rentang tahun
 *     dimensi: [{ kunci, label, opsi: [...] }],
 *     cakupanBawaan: 'kalimat cakupan penuh saat tak ada filter',
 *   }
 */
export default function filterLaporan(konfig = {}) {
    return {
        konfig,
        sp: '',
        tahunDari: '',
        tahunSampai: '',

        /** Keadaan tiap dimensi khas laporan, mis. { status: '', komoditas: '' }. */
        dimensi: {},

        init() {
            (this.konfig.dimensi || []).forEach((d) => {
                if (!(d.kunci in this.dimensi)) {
                    this.dimensi[d.kunci] = '';
                }
            });
        },

        get adaFilter() {
            return (
                this.sp !== '' ||
                this.tahunDari !== '' ||
                this.tahunSampai !== '' ||
                Object.values(this.dimensi).some((v) => v !== '')
            );
        },

        /**
         * Apakah satu `<tr data-baris>` lolos seluruh filter yang sedang aktif.
         *
         * Satu dimensi filter hanya berlaku atas baris yang MEMBAWA atribut
         * datanya. Laporan berbagian banyak (transmigran, rumah, lahan dalam
         * satu halaman) hanya menandai bagian yang relevan: filter tahun
         * kedatangan menyaring bagian transmigran, tidak menghapus seluruh
         * baris rumah yang memang tak punya tahun kedatangan.
         *
         * @param {HTMLElement} el
         * @returns {boolean}
         */
        cocok(el) {
            const d = el.dataset;

            if (this.sp !== '' && d.sp !== undefined && String(d.sp) !== String(this.sp)) {
                return false;
            }

            if (d.tahun !== undefined && d.tahun !== '') {
                const t = Number(d.tahun);

                if (this.tahunDari !== '' && t < Number(this.tahunDari)) {
                    return false;
                }

                if (this.tahunSampai !== '' && t > Number(this.tahunSampai)) {
                    return false;
                }
            }

            for (const [kunci, nilai] of Object.entries(this.dimensi)) {
                if (nilai !== '' && d[kunci] !== undefined && String(d[kunci]) !== String(nilai)) {
                    return false;
                }
            }

            return true;
        },

        /**
         * Menjumlahkan `data-<kolom>` dari `<tr data-baris>` yang cocok di dalam
         * `cakupanEl` (biasanya `<table>` atau `<tbody>`), lalu memformatnya ala
         * Indonesia. Dipakai `x-text` sel subtotal.
         *
         * Memakai `cocok(tr)` alih-alih membaca `display`, supaya nilainya benar
         * meski Alpine belum sempat menerapkan `x-show` ke DOM dan supaya
         * ketergantungan reaktifnya terlacak.
         *
         * @param {HTMLElement} cakupanEl
         * @param {string} kolom  nama kunci pada dataset, mis. 'jumlah'
         * @param {number} desimal
         * @returns {string}
         */
        jumlahTampak(cakupanEl, kolom, desimal = 0) {
            let total = 0;

            if (cakupanEl) {
                cakupanEl.querySelectorAll('tr[data-baris]').forEach((tr) => {
                    if (!this.cocok(tr)) {
                        return;
                    }

                    const v = Number(tr.dataset[kolom] ?? 0);

                    if (!Number.isNaN(v)) {
                        total += v;
                    }
                });
            }

            return this.angka(total, desimal);
        },

        /**
         * Apakah tak ada satu pun elemen ber-`penanda` yang cocok di dalam
         * `cakupanEl`. Dipakai `x-show` pesan "tidak ada yang cocok", termasuk
         * pada laporan yang menyaring wadah utuh (satu tabel per poktan), bukan
         * baris.
         *
         * @param {HTMLElement} cakupanEl
         * @param {string} penanda
         * @returns {boolean}
         */
        kosong(cakupanEl, penanda = 'tr[data-baris]') {
            if (! cakupanEl) {
                return false;
            }

            return ! [...cakupanEl.querySelectorAll(penanda)].some((el) => this.cocok(el));
        },

        /**
         * Cacah `<tr data-baris>` yang cocok di dalam `cakupanEl`.
         *
         * @param {HTMLElement} cakupanEl
         * @returns {number}
         */
        cacahTampak(cakupanEl) {
            let n = 0;

            if (cakupanEl) {
                cakupanEl.querySelectorAll('tr[data-baris]').forEach((tr) => {
                    if (this.cocok(tr)) {
                        n += 1;
                    }
                });
            }

            return n;
        },

        /** Format angka ala Indonesia, meniru App\Support\LaporanData::angka(). */
        angka(n, desimal = 0) {
            return Number(n).toLocaleString('id-ID', {
                minimumFractionDigits: Math.max(0, desimal),
                maximumFractionDigits: Math.max(0, desimal),
            });
        },

        bersihkan() {
            this.sp = '';
            this.tahunDari = '';
            this.tahunSampai = '';
            Object.keys(this.dimensi).forEach((k) => {
                this.dimensi[k] = '';
            });
        },

        /** Nama satu SP dari daftar opsi konfig, atau id mentah bila tak ketemu. */
        namaSp(id) {
            const opsi = (this.konfig.sp || []).find((o) => String(o.id) === String(id));

            return opsi ? opsi.nama : id;
        },

        /**
         * Kalimat cakupan untuk kepala kertas (rules.md 12 poin 8). Dokumen yang
         * dicetak atau difoto kehilangan kontrol filternya, sehingga cakupan
         * yang sedang berlaku wajib ikut tercetak sebagai kalimat.
         *
         * @returns {string}
         */
        get kalimatCakupan() {
            if (!this.adaFilter) {
                return this.konfig.cakupanBawaan || '';
            }

            const bagian = [];

            bagian.push(
                this.sp !== ''
                    ? 'Satuan Permukiman ' + this.namaSp(this.sp)
                    : this.konfig.cakupanBawaan || 'Seluruh kawasan'
            );

            if (this.tahunDari !== '' || this.tahunSampai !== '') {
                bagian.push(
                    'tahun ' +
                        (this.tahunDari || 'paling awal') +
                        ' sampai ' +
                        (this.tahunSampai || 'paling akhir')
                );
            }

            (this.konfig.dimensi || []).forEach((d) => {
                if (this.dimensi[d.kunci]) {
                    bagian.push(d.label.toLowerCase() + ' ' + this.dimensi[d.kunci]);
                }
            });

            let kalimat = bagian.join(', ');

            return kalimat.charAt(0).toUpperCase() + kalimat.slice(1) + '.';
        },
    };
}
