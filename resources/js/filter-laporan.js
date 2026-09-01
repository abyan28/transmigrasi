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
 *     tahunTunggal: true,          // pasang SATU pemilih tahun (laporan snapshot)
 *     tahunBawaan: 2026,           // tahun terpilih bawaan untuk tahunTunggal
 *     tahunAkhir: 2026,            // tahun terakhir deret data, utk label dokumen
 *     dimensi: [{ kunci, label, opsi: [...] }],
 *     cakupanBawaan: 'kalimat cakupan penuh saat tak ada filter',
 *   }
 *
 * Tautan "Generate Laporan" membawa keadaan filter ke rute dokumen lewat
 * FRAGMEN HASH (`#sp=..&td=..`), bukan query string -- GitHub Pages tidak
 * melayani query string (notes.md 1b.5) tetapi hash murni sisi peramban.
 * `hashFilter` menserialisasi, `dariHash()` membaca kembali di rute dokumen.
 */
export default function filterLaporan(konfig = {}) {
    return {
        konfig,
        sp: '',
        tahunDari: '',
        tahunSampai: '',
        tahun: '',
        cari: '',
        modeTampilan: 'gabungan',
        ukuranKertas: 'a4',

        /** Keadaan tiap dimensi khas laporan, mis. { status: '', komoditas: '' }. */
        dimensi: {},

        init() {
            (this.konfig.dimensi || []).forEach((d) => {
                if (!(d.kunci in this.dimensi)) {
                    this.dimensi[d.kunci] = '';
                }
            });

            if (this.konfig.tahunTunggal) {
                this.tahun = String(this.konfig.tahunBawaan ?? this.konfig.tahunAkhir ?? '');
            }

            this.dariHash();
        },

        /**
         * Menserialisasi filter yang sedang aktif ke fragmen hash, untuk
         * dipasang pada `href` tombol "Generate Laporan". Kosong bila tak ada
         * filter. Tahun tunggal hanya ditulis bila berbeda dari bawaannya.
         *
         * @returns {string} '' atau '#sp=1&td=2019&...'
         */
        get hashFilter() {
            const p = new URLSearchParams();

            if (this.ukuranKertas && this.ukuranKertas !== 'a4') p.set('kertas', this.ukuranKertas);
            if (this.modeTampilan && this.modeTampilan !== 'gabungan') p.set('mode', this.modeTampilan);
            if (this.sp !== '') p.set('sp', this.sp);
            if (this.cari !== '') p.set('q', this.cari);
            if (this.tahunDari !== '') p.set('td', this.tahunDari);
            if (this.tahunSampai !== '') p.set('ts', this.tahunSampai);

            if (
                this.konfig.tahunTunggal &&
                this.tahun !== '' &&
                this.tahun !== String(this.konfig.tahunBawaan ?? this.konfig.tahunAkhir ?? '')
            ) {
                p.set('th', this.tahun);
            }

            Object.entries(this.dimensi).forEach(([k, v]) => {
                if (v !== '') p.set(k, v);
            });

            const s = p.toString();

            return s ? '#' + s : '';
        },

        /**
         * Membaca `location.hash` dan menerapkannya ke keadaan filter. Dipanggil
         * di `init()`. Di rute dokumen inilah satu-satunya cara filter sampai;
         * di halaman berbingkai hash biasanya kosong (dan tak apa bila terisi --
         * jadi tautan yang dapat ditandai).
         */
        dariHash() {
            const raw = (globalThis.location?.hash || '').replace(/^#/, '');

            if (! raw) {
                return;
            }

            const p = new URLSearchParams(raw);

            if (p.has('kertas')) {
                this.ukuranKertas = p.get('kertas');
                if (this.ukuranKertas === 'f4') {
                    const landscape = document.querySelector('.kertas-dokumen')?.classList.contains('dokumen-landscape');
                    let tag = document.getElementById('style-page-kertas');
                    if (!tag) {
                        tag = document.createElement('style');
                        tag.id = 'style-page-kertas';
                        document.head.appendChild(tag);
                    }
                    tag.textContent = landscape
                        ? '@page { size: 330mm 215mm; margin: 10mm; }'
                        : '@page { size: 215mm 330mm; margin: 12mm; }';
                }
            }
            if (p.has('mode')) this.modeTampilan = p.get('mode');
            if (p.has('sp')) this.sp = p.get('sp');
            if (p.has('q')) this.cari = p.get('q');
            if (p.has('td')) this.tahunDari = p.get('td');
            if (p.has('ts')) this.tahunSampai = p.get('ts');
            if (p.has('th')) this.tahun = p.get('th');

            Object.keys(this.dimensi).forEach((k) => {
                if (p.has(k)) this.dimensi[k] = p.get(k);
            });
        },

        get adaFilter() {
            return (
                this.sp !== '' ||
                this.cari !== '' ||
                this.tahunDari !== '' ||
                this.tahunSampai !== '' ||
                (this.konfig.tahunTunggal &&
                    String(this.tahun) !== String(this.konfig.tahunBawaan ?? '')) ||
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

            // Pencarian kata kunci
            if (this.cari.trim() !== '') {
                const q = this.cari.toLowerCase().trim();
                const haystack = (d.cari || el.textContent || '').toLowerCase();
                if (!haystack.includes(q)) {
                    return false;
                }
            }

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

                // Pemilih tahun tunggal: cocok hanya baris tahun terpilih.
                if (this.konfig.tahunTunggal && this.tahun && t !== Number(this.tahun)) {
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
         * Jumlah kolom profil poktan (mis. luas_lahan) dari baris yang cocok,
         * dihitung dari HIMPUNAN POKTAN UNIK (rules.md §16c) agar satu poktan
         * yang menanam berkali-kali tidak melipatgandakan luas lahan pada subtotal.
         *
         * @param {HTMLElement|Iterable<HTMLElement>} cakupan
         * @param {string} kolom
         * @param {number} desimal
         * @param {string} penanda
         * @returns {string}
         */
        jumlahTampakPoktanUnik(cakupan, kolom, desimal = 2, penanda = 'tr[data-baris]') {
            const poktanDihitung = new Set();
            let total = 0;

            for (const tr of this._baris(cakupan, penanda)) {
                if (! this.cocok(tr)) {
                    continue;
                }

                const poktanId = tr.dataset.poktan_id || tr.dataset.poktan;
                if (poktanId && poktanDihitung.has(poktanId)) {
                    continue;
                }
                if (poktanId) {
                    poktanDihitung.add(poktanId);
                }

                const v = Number(tr.dataset[kolom] ?? 0);
                if (! Number.isNaN(v)) {
                    total += v;
                }
            }

            return this.angka(total, desimal);
        },

        /**
         * Luas sisa belum ditanam pada subtotal/total:
         * Σ per poktan unik max(0, luas_lahan_poktan - Σ realisasi_tanam_poktan) (rules.md §16c).
         *
         * @param {HTMLElement|Iterable<HTMLElement>} cakupan
         * @param {number} desimal
         * @param {string} penanda
         * @returns {string}
         */
        belumDitanamTampak(cakupan, desimal = 2, penanda = 'tr[data-baris]') {
            const luasPoktan = new Map();
            const tanamPoktan = new Map();

            for (const tr of this._baris(cakupan, penanda)) {
                if (! this.cocok(tr)) {
                    continue;
                }

                const poktanId = tr.dataset.poktan_id || tr.dataset.poktan;
                if (poktanId) {
                    if (! luasPoktan.has(poktanId)) {
                        luasPoktan.set(poktanId, Number(tr.dataset.luas_lahan ?? 0) || 0);
                    }
                    const tanamSebelum = tanamPoktan.get(poktanId) || 0;
                    tanamPoktan.set(poktanId, tanamSebelum + (Number(tr.dataset.realisasi_tanam ?? 0) || 0));
                }
            }

            let totalSisa = 0;
            for (const [poktanId, luas] of luasPoktan.entries()) {
                const tanam = tanamPoktan.get(poktanId) || 0;
                totalSisa += Math.max(0, Math.round((luas - tanam) * 100) / 100);
            }

            return this.angka(totalSisa, desimal);
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
            this.cari = '';
            this.tahunDari = '';
            this.tahunSampai = '';
            if (this.konfig.tahunTunggal) {
                this.tahun = String(this.konfig.tahunBawaan ?? '');
            }
            Object.keys(this.dimensi).forEach((k) => {
                this.dimensi[k] = '';
            });
        },

        /**
         * Nilai indikator kawasan untuk tahun yang sedang dipilih, diformat ala
         * Indonesia. Membaca `konfig.ringkasanTahun[tahun]` (Rekap Indikator
         * Kawasan). Dipakai `x-text` sel blok ringkasan.
         *
         * @param {string} kunci
         * @param {number} desimal
         * @returns {string}
         */
        nilaiTahun(kunci, desimal = 0) {
            const t = (this.konfig.ringkasanTahun || {})[this.tahun] || {};

            return this.angka(t[kunci] ?? 0, desimal);
        },

        /** Persentase `pembilang/penyebut` indikator kawasan tahun terpilih. */
        nilaiTahunRasio(pembilang, penyebut, desimal = 1) {
            const t = (this.konfig.ringkasanTahun || {})[this.tahun] || {};
            const b = Number(t[penyebut] ?? 0);

            return this.angka(b > 0 ? (Number(t[pembilang] ?? 0) / b) * 100 : 0, desimal);
        },

        /**
         * Kalimat kelompok "Iklim" Bab II Monografi untuk SP dan tahun terpilih.
         * Dirakit di PHP (`konfig.iklimTahun[spId][tahun][label]`).
         *
         * @param {number|string} spId
         * @param {string} label
         * @returns {string}
         */
        iklimTahun(spId, label) {
            const sp = (this.konfig.iklimTahun || {})[spId] || {};

            return (sp[this.tahun] || {})[label] ?? 'belum dicatat';
        },

        /**
         * Angka "Keadaan Penduduk Sekarang" Monografi untuk SP dan tahun
         * terpilih. Dirakit di PHP (`konfig.kependudukanTahun[spId][tahun]`).
         *
         * @param {number|string} spId
         * @param {'kk'|'jiwa'|'laki'|'perempuan'} kunci
         * @returns {string}
         */
        nilaiKependudukan(spId, kunci) {
            const sp = (this.konfig.kependudukanTahun || {})[spId] || {};
            const nilai = (sp[this.tahun] || {})[kunci];

            return nilai == null ? 'belum dicatat' : new Intl.NumberFormat('id-ID').format(nilai);
        },

        /** Label "(tahun X)" untuk kepala blok keadaan penduduk sekarang. */
        labelTahunTerpilih() {
            return this.konfig.tahunTunggal && this.tahun ? '(tahun ' + this.tahun + ')' : '';
        },

        /**
         * Selektor CSS untuk baris data milik satu SP. Dipakai baris subtotal
         * per grup pada laporan `kelompokkanPerSp` (Alsintan, Saprotan, Panen)
         * supaya `jumlahTampak`/`kosong` hanya menghitung grup itu.
         */
        selSp(spId) {
            return 'tr[data-baris][data-sp="' + spId + '"]';
        },

        /**
         * Baris "TAHUN ..." di blok judul kop dokumen. Rentang bila penyaring
         * rentang tahun aktif; satu tahun bila pemilih tahun tunggal; selain
         * itu tahun terakhir deret data (`konfig.tahunBawaanDokumen`).
         *
         * @returns {string}
         */
        get tahunDokumen() {
            const prefix = this.konfig.labelTahunDokumen ? this.konfig.labelTahunDokumen.toUpperCase() : 'TAHUN';

            if (this.konfig.tahunTunggal && this.tahun) {
                return prefix + ' ' + this.tahun;
            }

            if (this.tahunDari !== '' && this.tahunSampai !== '') {
                return this.tahunDari === this.tahunSampai
                    ? prefix + ' ' + this.tahunDari
                    : prefix + ' ' + this.tahunDari + ' sampai ' + this.tahunSampai;
            }

            if (this.tahunDari !== '' || this.tahunSampai !== '') {
                return prefix + ' ' + (this.tahunDari || 'paling awal') + ' sampai ' + (this.tahunSampai || 'paling akhir');
            }

            return prefix + ' ' + (this.konfig.tahunBawaanDokumen || '');
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
            } else if (this.konfig.tahunTunggal && this.tahun) {
                bagian.push('tahun ' + this.tahun);
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
