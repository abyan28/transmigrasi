# Rencana Eksekusi — Putaran 3 D3: Filter per Halaman Laporan (Alpine)

Ditulis 2026-08-29 sesuai `rules.md` §20b. Sementara, boleh ditimpa.

**Status masuk:** Putaran 4 (E1+E2+E3/D4) SELESAI dan ter-commit
(`e36de0a`, `2a194b3`). Tercatat permanen di `notes.md` §1s. Repo hijau:
`pest` 679, `pint` 31, `sim:tautan-statis` 222, tiga uji peramban hijau.

**D3 adalah satu-satunya sisa Tahap 2.** Semua butir lain sudah selesai atau
ditunda ke Tahap 3 (fondasi `user`, `notes.md` §1g.7 Temuan 3).

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

### D3-1 — Komponen `x-sim.filter-laporan` + pasang di laporan PALING sederhana

**Tujuan:** membangun pola sekali, buktikan di satu laporan, sebelum
menyalin ke enam lainnya.

- Buat `resources/views/components/sim/filter-laporan.blade.php`:
  - Prop: `:sp="[...]"` (daftar SP untuk `<select>`), `:tahun="false"`
    (pasang `x-sim.filter-rentang-tahun` bila true), slot untuk dimensi khas.
  - `x-data` dengan state `{ sp: '', tahunDari: '', tahunSampai: '', ... }`
    dan getter `cocok(baris)` yang mengembalikan boolean.
  - `x-modelable` atau `$dispatch` supaya `kerangka-laporan` /
    `isi/{slug}` dapat membaca state filter untuk (a) sembunyikan `<tr>`,
    (b) hitung ulang subtotal, (c) susun kalimat cakupan.
  - **Keputusan arsitektur yang harus diambil dulu:** di mana `x-data`
    filter hidup? Usulan: di `kerangka-laporan` `<article>` sebagai
    `x-data="filterLaporan({...})"`, sehingga `isi/{slug}` (yang di-`@include`
    ke dalam slot) berada di dalam scope-nya dan bisa pakai `x-show`/`x-text`
    langsung. Verifikasi: slot komponen TIDAK mewarisi variabel Blade, tapi
    scope Alpine `x-data` DITURUNKAN ke DOM anak — termasuk isi slot. Cek
    dengan satu `x-text` percobaan sebelum menembak jauh.
- Pasang di **Laporan Transmigran** (`isi/transmigran.blade.php`): dimensi
  SP + `tahun_kedatangan`. Paling sederhana: daftar datar, subtotal
  sedikit/tidak ada.
  - Tiap `<tr>` transmigran: `data-sp="{id sp}"` `data-tahun="{tahun_kedatangan}"`,
    `x-show="cocok($el)"` (atau kelas tersembunyi + logika terpusat).
  - Bila ada baris "Jumlah": `x-text` menghitung `<tr>` yang tampak.
  - Kalimat cakupan: `x-text` di `<dd>` Wilayah pada `kerangka-laporan`,
    default `{{ $cakupan }}`, berubah jadi "SP <nama>" saat difilter.
- **Uji:** tambah kasus ke `tests/Browser/` (file baru `uji-filter-laporan.mjs`,
  PORT DevTools baru — 9353): buka `/laporan/transmigran`, pilih satu SP,
  pastikan baris SP lain hilang + kalimat cakupan berubah + subtotal turun.
- **Jangan** sentuh enam laporan lain di tahap ini.

**Checkpoint:** `pest` tetap hijau (uji Blade statis tak berubah), uji
peramban baru hijau, `pint` 31. Commit "Putaran 3 D3-1: komponen
filter-laporan + Laporan Transmigran".

### D3-2 — Salin pola ke laporan datar lain: Poktan, Alsintan, Saprotan

- **Poktan** (`isi/poktan.blade.php`): dimensi SP + status keaktifan poktan.
- **Alsintan**: dimensi SP + poktan pemilik + `x-sim.filter-rentang-tahun`
  (`data-tahun` = tahun pengadaan) + jenis alsintan.
- **Saprotan**: dimensi SP + poktan + rentang tahun + jenis saprotan
  (benih / non-benih sudah dipisah di `LaporanData`).
- Tiap laporan: `data-*` pada `<tr>`, `x-text` subtotal, kalimat cakupan.
- Tambah kasus ke `uji-filter-laporan.mjs` per laporan.

**Checkpoint:** commit "Putaran 3 D3-2: filter Poktan, Alsintan, Saprotan".

### D3-3 — Laporan Hasil Panen (paling rumit: subtotal berjenjang)

- Dimensi: SP + poktan + komoditas + rentang tahun (tahun panen — CATATAN:
  laporan hasil panen memakai **tahun anggaran bantuan** sebagai sumbu,
  `rules.md` §16a; pastikan `data-tahun` memakai sumbu yang benar).
- Subtotal per kelompok (per SP / per komoditas) DAN total keseluruhan
  harus ikut menyempit — tiap sel subtotal `x-text`.
- `rules.md` §8o: baris total yang menyempit WAJIB dinyatakan pada judul
  tabel & baris totalnya ("mencakup filter aktif: ...").

**Checkpoint:** commit "Putaran 3 D3-3: filter Laporan Hasil Panen".

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

### D3-5 — Monografi SP (bila filter SP relevan)

- Monografi SP sudah per-SP secara struktur (satu blok Bab II per SP).
  Filter SP di sini = tampilkan satu SP saja. Dimensi lain: tak ada.
  Mungkin cukup `<select>` SP tunggal tanpa rentang tahun.
- Nilai kecil; boleh digabung ke D3-4 atau dilewati bila pemilik proyek
  menilai tak perlu.

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
