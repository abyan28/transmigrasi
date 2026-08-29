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

            // Atribut data yang kosong ('') diperlakukan sebagai TIDAK ADA:
            // dimensi itu tak berlaku atas baris tsb (mis. baris benih tanpa SP).
            if (this.sp !== '' && d.sp && String(d.sp) !== String(this.sp)) {
                return false;
            }

            if (d.tahun) {
                const t = Number(d.tahun);

                if (this.tahunDari !== '' && t < Number(this.tahunDari)) {
                    return false;
                }

                if (this.tahunSampai !== '' && t > Number(this.tahunSampai)) {
                    return false;
                }
            }

            for (const [kunci, nilai] of Object.entries(this.dimensi)) {
                if (nilai !== '' && d[kunci] != null && d[kunci] !== '' && String(d[kunci]) !== String(nilai)) {
                    return false;
                }
            }

            return true;
        },

        /**
         * Menjumlahkan `data-<kolom>` dari `<tr data-baris>` yang cocok di dalam
         * `cakupan`, lalu memformatnya ala Indonesia. Dipakai `x-text` sel
         * subtotal dan total.
         *
         * `cakupan` boleh berupa elemen (turunannya diambil dengan `penanda`)
         * ATAU sebuah NodeList/array baris yang sudah disiapkan pemanggil.
         * Untuk subtotal per grup SP, pemanggil mengoper penanda ber-`data-sp`.
         *
         * Memakai `cocok(tr)` alih-alih membaca `display`, supaya nilainya benar
         * meski Alpine belum sempat menerapkan `x-show` ke DOM dan supaya
         * ketergantungan reaktifnya terlacak.
         *
         * @param {HTMLElement|Iterable<HTMLElement>} cakupan
         * @param {string} kolom  nama kunci pada dataset, mis. 'jumlah'
         * @param {number} desimal
         * @param {string} penanda
         * @returns {string}
         */
        jumlahTampak(cakupan, kolom, desimal = 0, penanda = 'tr[data-baris]') {
            let total = 0;

            for (const tr of this._baris(cakupan, penanda)) {
                if (! this.cocok(tr)) {
                    continue;
                }

                const v = Number(tr.dataset[kolom] ?? 0);

                if (! Number.isNaN(v)) {
                    total += v;
                }
            }

            return this.angka(total, desimal);
        },

        /**
         * Rasio dua kolom terjumlah dari baris yang cocok, mis. produktivitas
         * tertimbang = Σ produksi / Σ realisasi panen. Tidak sama dengan
         * merata-ratakan produktivitas per baris.
         *
         * @param {HTMLElement|Iterable<HTMLElement>} cakupan
         * @param {string} pembilang
         * @param {string} penyebut
         * @param {number} desimal
         * @param {string} penanda
         * @returns {string}
         */
        rasioTampak(cakupan, pembilang, penyebut, desimal = 2, penanda = 'tr[data-baris]') {
            let atas = 0;
            let bawah = 0;

            for (const tr of this._baris(cakupan, penanda)) {
                if (! this.cocok(tr)) {
                    continue;
                }

                atas += Number(tr.dataset[pembilang] ?? 0) || 0;
                bawah += Number(tr.dataset[penyebut] ?? 0) || 0;
            }

            return this.angka(bawah > 0 ? atas / bawah : 0, desimal);
        },

        /**
         * Apakah tak ada satu pun baris ber-`penanda` yang cocok di dalam
         * `cakupan`. Dipakai `x-show` pesan "tidak ada yang cocok" dan
         * penyembunyian baris grup-header/subtotal saat grupnya kosong.
         *
         * @param {HTMLElement|Iterable<HTMLElement>} cakupan
         * @param {string} penanda
         * @returns {boolean}
         */
        kosong(cakupan, penanda = 'tr[data-baris]') {
            return ! this._baris(cakupan, penanda).some((el) => this.cocok(el));
        },

        /**
         * Cacah baris ber-`penanda` yang cocok di dalam `cakupan`.
         *
         * @param {HTMLElement|Iterable<HTMLElement>} cakupan
         * @param {string} penanda
         * @returns {number}
         */
        cacahTampak(cakupan, penanda = 'tr[data-baris]') {
            return this._baris(cakupan, penanda).filter((el) => this.cocok(el)).length;
        },

        /**
         * Menormalkan `cakupan` menjadi array baris. Elemen -> ambil turunannya
         * dengan `penanda`; NodeList/array -> pakai apa adanya.
         *
         * @param {HTMLElement|Iterable<HTMLElement>|null} cakupan
         * @param {string} penanda
         * @returns {HTMLElement[]}
         */
        _baris(cakupan, penanda = 'tr[data-baris]') {
            if (! cakupan) {
                return [];
            }

            if (typeof cakupan.querySelectorAll === 'function') {
                return [...cakupan.querySelectorAll(penanda)];
            }

            return [...cakupan];
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

        /**
         * Selektor CSS untuk baris data milik satu SP. Dipakai baris subtotal
         * per grup pada laporan `kelompokkanPerSp` (Alsintan, Saprotan, Panen)
         * supaya `jumlahTampak`/`kosong` hanya menghitung grup itu.
         */
        selSp(spId) {
            return 'tr[data-baris][data-sp="' + spId + '"]';
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
