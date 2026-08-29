# Rencana Eksekusi — Putaran 3 D3: Filter per Halaman Laporan (Alpine)

Ditulis 2026-08-29 sesuai `rules.md` §20b. Sementara, boleh ditimpa.

**Status:** D3-1 SELESAI (commit menyusul/ter-commit). Berikutnya **D3-2**.
Catatan permanen D3-1 di `notes.md` §1t. Repo hijau: `pest` 684, `pint` 31,
`sim:tautan-statis` 222, `uji-filter-laporan.mjs` 18/0, uji peramban lain hijau.

**D3 adalah satu-satunya sisa Tahap 2.** Semua butir lain sudah selesai atau
ditunda ke Tahap 3 (fondasi `user`, `notes.md` §1g.7 Temuan 3).

**Yang sudah terbukti di D3-1 (dipakai D3-2..D3-5 tanpa diubah):**
- `x-data="filterLaporan(konfig)"` pada `<article>` di `kerangka-laporan`;
  konfig diturunkan dari `LaporanData::filterLaporan($slug)` — jadi menambah
  laporan = satu arm `match` + `data-*` pada `<tr>` partial isi, TANPA
  menyentuh berkas halaman.
- Cakupan Alpine mewaris ke slot: `x-show`/`x-text` di `isi/{slug}` langsung
  bekerja.
- `cocok(tr)`: dimensi filter hanya berlaku atas baris yang membawa atribut
  datanya. `jumlahTampak()` / `cacahTampak()` siap untuk subtotal D3-2/D3-3.
- Nomor urut lewat penghitung CSS (`td[data-nomor]`), sudah ada di `app.css`.
- `uji-filter-laporan.mjs` (PORT 9353) — tambah blok per laporan baru.

---

## Kontrak D3 (sudah ditetapkan, jangan diperdebatkan ulang)

Sumber: `rules.md` §12 poin 5, 8, 11; `ui-spec.md` §6.10, §6.11; `notes.md`
§1r.4.

1. **Tiap halaman laporan punya bilah filternya sendiri** di kepala kertas
   (BUKAN laci — di laporan filter adalah kontrol utama). Isi: Satuan
   Permukiman + periode (bila relevan) + dimensi khas laporan.
2. **Filter dikerjakan Alpine di sisi peramban** — BUKAN query string.
   GitHub Pages tidak melayani query string (`notes.md` §1b.5); filter mati
   di situs terbit adalah kontrol mati (R-26). Blade merender SELURUH baris;
   Alpine menyembunyikan `<tr>` dan menghitung ulang subtotal yang tampak.
3. **Mekanisme:** tiap `<tr>` data diberi `data-sp`, `data-tahun`,
   `data-poktan`, dst. Alpine memfilter berdasarkan atribut itu; sel
   subtotal/total memakai `x-text` yang menjumlah ulang baris yang tampak.
4. **Filter rentang tahun** (`x-sim.filter-rentang-tahun`, sudah ada):
   dipasang HANYA di laporan yang barisnya transaksi bersumbu waktu —
   **Hasil Panen, Alsintan, Saprotan** (tiap baris milik tepat satu tahun
   pengadaan). **DILARANG** di rekap agregat (Monografi SP, Rekap Indikator
   Kawasan) — menjumlah lintas tahun membuat 2 ha × 3 musim terbaca 6 ha
   (`rules.md` §9 poin 8b).
5. **Kalimat cakupan di kepala kertas ikut berubah** mengikuti filter aktif
   (`rules.md` §12 poin 8). Dokumen yang dicetak/difoto kehilangan kontrol
   filter, jadi cakupan wajib tercetak sebagai kalimat.
6. Bilah filter memakai `.cetak-sembunyi` (tak ikut tercetak).
7. **Rekap Indikator Kawasan:** angka tingkat kawasan tetap dari dashboard
   (`ringkasanDashboard()` TIDAK disentuh); rincian per SP dihitung dari data
   mentah lewat agregasi tersendiri, supaya filter SP menyempitkan halaman.

---

## Pembagian bertahap (tiap tahap = satu commit, diuji terpisah)

### D3-1 — Komponen `x-sim.filter-laporan` + Laporan Transmigran ✅ SELESAI

Dikerjakan 2026-08-29. Hasil (lihat `notes.md` §1t.1 untuk rinciannya):

- `resources/js/filter-laporan.js` — `Alpine.data('filterLaporan', konfig)`
  dengan `cocok(tr)`, `jumlahTampak()`, `cacahTampak()`, `kalimatCakupan`,
  `bersihkan()`. Didaftarkan di `resources/js/app.js`.
- `resources/views/components/sim/filter-laporan.blade.php` — bilah selalu
  tampak, `.cetak-sembunyi`, `<select x-model>` SP + rentang tahun + dimensi.
  TIDAK membuat cakupan Alpine sendiri.
- `LaporanData::filterLaporan($slug)` — `match` per slug; D3-1 hanya
  `transmigran` (SP + Tahun Kedatangan + Status Tinggal), lain `[]`.
- `kerangka-laporan.blade.php` — `x-data="filterLaporan(@js($konfigFilter))"`
  pada `<article>`; `<x-sim.filter-laporan>` sesudah masthead bila `$filter`
  terisi; `<dd x-text="kalimatCakupan">{{ $cakupan }}</dd>`.
- `isi/transmigran.blade.php` — `<tr data-baris data-sp data-tahun data-status
  x-show="cocok($el)">` di 3 bagian; `<td data-nomor>` (penghitung CSS);
  baris "Tidak ada … yang cocok" `x-show="cacahTampak(...) === 0"`.
- `app.css` — 3 aturan penghitung baris (`.tabel-dokumen tbody/tr[data-baris]/
  td[data-nomor]::before`).
- `tests/Browser/uji-filter-laporan.mjs` (PORT 9353) — 18/0.
- `HalamanTest` +5 penjaga. **pest 684** (dari 679), pint 31, tautan-statis 222.

**Keputusan arsitektur terkonfirmasi:** `x-data` di `<article>`, bukan di
komponen bilah — kalimat cakupan (sibling slot) wajib ikut bereaksi. Scope
Alpine `x-data` diturunkan ke DOM anak termasuk isi slot Blade. `x-sim.
filter-rentang-tahun`/`tombol-filter` yang ada berbasis submit `<form>`, jadi
TIDAK dipakai ulang apa adanya (bilah membangun `<select x-model>` sendiri).

### D3-2 — Laporan Poktan ✅ SELESAI

Dikerjakan 2026-08-29. Poktan = satu tabel per kelompok tani, tiap poktan
milik tepat satu SP → penyaring SP menyembunyikan **wadah tabel utuh**
(`<div data-poktan-wadah data-sp x-show="cocok($el)">`), bukan baris. Tanpa
`x-text` subtotal (subtotal per-poktan ikut hilang bersama tabelnya). Dimensi
status keaktifan poktan **dilewati** — `poktan()` tak menyimpannya.

- `resources/js/filter-laporan.js` — helper baru `kosong(cakupanEl, penanda)`.
- `LaporanData::filterLaporan('poktan')` → SP saja.
- `isi/poktan.blade.php` — wadah `data-poktan-wadah data-sp x-show`; blok
  "Tidak ada kelompok tani yang cocok" `x-show="kosong($root, 'div[data-poktan-wadah]')"`.
- `uji-filter-laporan.mjs` +5 (21/0). Pest +1 (`['transmigran', 'poktan']`).

### D3-3a — Laporan Alsintan ✅ SELESAI (2026-08-29)

Pola grup-per-SP terbukti (dipakai ulang D3-3b/c):
- Baris data `data-baris data-sp data-tahun data-jenis data-jumlah` +
  `x-show="cocok($el)"`.
- Grup-header + subtotal `x-show="! kosong($el.closest('table'), selSp({sp_id}))"`.
- Sel subtotal `x-text="jumlahTampak($el.closest('table'), 'jumlah', 0, selSp({sp_id}))"`;
  total `<tfoot>` tanpa penanda. Angka Blade dipertahankan (jaring JS mati).
- §8o: `<span x-show="adaFilter" x-text="'(' + kalimatCakupan + ')'">` di baris total.

Helper JS: `_baris(cakupan, penanda)` menormalkan elemen ATAU NodeList;
`selSp(id)`; `rasioTampak(pembilang, penyebut)` untuk produktivitas tertimbang
(dipakai D3-3c). Nomor urut → `td[data-nomor]` (penghitung CSS, tak lagi
`++$nomor`). `pest` 686, `uji-filter-laporan.mjs` 28/0.

### D3-3b — Laporan Saprotan ✅ SELESAI (2026-08-29)

**Bukan** grup-per-SP — dua tabel datar (benih + non-benih), tanpa subtotal.
Pola Transmigran: hanya `x-show` baris. Benih: `data-sp data-tahun
data-komoditas`. Non-benih: `data-sp data-tahun data-jenis`. Dua dimensi
(Komoditas Benih, Jenis Sarana non-benih) — masing-masing hanya menyentuh
tabel yang membawa atributnya. `cocok()` diperketat: atribut `''` = tidak ada.
`uji-filter-laporan.mjs` 32/0.

### D3-3c — Laporan Hasil Panen ✅ SELESAI (2026-08-29)

Pola Alsintan. Closure Blade `$selHitung($kunci, $penanda)` menghasilkan
`jumlahTampak(...)` per kolom KECUALI `produktivitas_tertimbang` →
`rasioTampak($el.closest('table'), 'produksi_ton', 'realisasi_panen', 2, …)`.
`data-tahun` = `tahun_pengadaan` (§16a), label "Tahun Anggaran Bantuan".
`data-*` kolom angka ber-underscore. `uji-filter-laporan.mjs` 37/0 (cek
produktivitas tertimbang numerik).

--- Ketujuh laporan menyusul selesai; sisa D3-4 & D3-5 di bawah. ---

### D3-4 — Rekap Indikator Kawasan: agregasi per SP + filter SP

**Prasyarat (kerjakan lebih dulu, di `LaporanData` / helper baru):**
- Metode agregasi 16 indikator dashboard (BUKAN 17 — dashboard turun ke 16
  setelah indikator Mutu Data dihapus, `notes.md` bagian 4 baris ~1636)
  **per SP**, dihitung dari data mentah `DummyData`, TANPA menyentuh
  `ringkasanDashboard()`.
- **Penjaga wajib** (`HalamanTest` atau test baru): untuk tiap indikator
  yang bersifat penjumlahan, `Σ (enam SP) === angka kawasan dari
  ringkasanDashboard()`. Indikator yang rata-rata/rasio dikecualikan
  eksplisit dengan komentar alasannya.
- **Jangan** dasarkan pemetaan indikator pada cacah baris `DummyData`
  (`rules.md` §19a). Pakai definisi indikator di `prd.md` §7.8 / `ui-spec.md`
  §9.
- Setelah agregasi ada: `isi/indikator-kawasan.blade.php` merender kolom
  per SP; filter SP menyembunyikan kolom/among-baris; kolom "Kawasan" tetap.

**Checkpoint:** commit "Putaran 3 D3-4: Rekap Indikator Kawasan per SP +
filter". Ini menutup D3.

### D3-5 — Monografi SP ✅ SELESAI (2026-08-29)

Pemilih SP menyembunyikan `<tr data-baris data-sp>` ikhtisar + `<section
data-baris data-sp>` Bab II. Tanpa rentang tahun. `filterLaporan('monografi-sp')`
→ SP saja. `uji-filter-laporan.mjs` 41/0.

### D3-4 — Rekap Indikator Kawasan (SATU-SATUNYA TERSISA)

**Prasyarat:** metode `LaporanData` agregasi 16 indikator dashboard **per SP**
dari data mentah `DummyData`, TANPA menyentuh `ringkasanDashboard()`.
- Pakai definisi indikator `prd.md` §7.8 / `ui-spec.md` §9, JANGAN cacah baris
  `DummyData` (`rules.md` §19a).
- **Penjaga wajib** (Pest): indikator jumlah → `Σ(6 SP) === angka kawasan
  ringkasanDashboard()`. Rasio/rata-rata dikecualikan eksplisit + komentar.
- `isi/indikator-kawasan.blade.php`: cek strukturnya dulu (`indikatorKawasan()`
  mengembalikan `kawasan`, `ringkasan`, `perSp` — `perSp` = `rekapPerSp()`).
  Kemungkinan tabel per-SP sudah ada sebagian; filter SP tinggal `data-sp` +
  `x-show` + baris "Kawasan" tetap.
- Kalau kolom per SP belum lengkap 16 indikator, itu pekerjaan agregasi
  tersendiri sebelum filternya.

---

## Dokumen yang harus diperbarui saat D3 selesai

- `notes.md`: bagian baru §1t "Putaran 3 D3 selesai" — pola filter, keputusan
  scope Alpine, penjaga Σ-SP.
- `tasklist.md`: Stage D3 ⬜ → ✅; hapus "Satu-satunya sisa Putaran 3/4".
- `ui-spec.md` §6.11: ganti "(Putaran 3 D3)" jadi tanggal selesai + rincian
  `data-*` final dan cara kalimat cakupan disusun.
- `rules.md`: tak perlu diubah (kontrak sudah ditulis di §12 poin 5/8/11).

## Verifikasi akhir D3

1. `pest.bat` hijau (naik dari 679 — penjaga Σ-SP + agregasi per SP).
2. `pint.bat --test` ≤ 31.
3. `sim:tautan-statis` tetap 222 (tak ada rute baru — filter di peramban).
4. `npm run build`; `uji-lebar-dokumen.mjs` 28/0 tetap.
5. `uji-filter-laporan.mjs` hijau untuk ketujuh laporan.
6. Buka tiap `/laporan/*`: pilih SP → baris SP lain hilang, subtotal turun,
   kalimat "Wilayah" di kepala kertas berubah. Kosongkan filter → pulih.
7. `/laporan/*/dokumen`: bilah filter TIDAK muncul saat dicetak
   (`.cetak-sembunyi`); kalimat cakupan tetap tercetak.

## Jebakan yang sudah diketahui

- **Scope Alpine lintas slot komponen.** `isi/{slug}` di-`@include` ke slot
  `kerangka-laporan`. Variabel Blade tak diwariskan (sudah ditangani lewat
  `isiLaporan`), TAPI `x-data` pada elemen pembungkus slot DITURUNKAN ke DOM
  anak. Uji dengan satu `x-text` sebelum membangun logika penuh.
- **Query string terlarang** — semua di Alpine. Tak ada `?sp=` di URL.
- **Subtotal `DummyData` vs subtotal tampak.** Blade merender subtotal
  penuh; Alpine menimpanya dengan `x-text` hasil penjumlahan baris tampak.
  Jangan hapus nilai Blade — ia jaring bila JS mati.
- **`data-tahun` Hasil Panen** memakai tahun anggaran bantuan, bukan tahun
  panen (`rules.md` §16a). Salah sumbu = angka benar untuk pertanyaan salah.
- **Jangan hitung indikator dari cacah baris `DummyData`** (`rules.md` §19a).
- Uji peramban: `php artisan serve --port=8099` lebih dulu; PORT DevTools
  unik per file (9349/9351/9341 terpakai — pakai 9353).
- `pest.bat --filter` pecah pada filter multi-kata — pakai satu kata.
- Hapus berkas lewat PowerShell `Remove-Item`, bukan `rm`.
