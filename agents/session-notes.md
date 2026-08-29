# Rencana Eksekusi — Putaran 5: "Generate Laporan" (dokumen resmi + filter dibawa ke tab + filter tahun)

Ditulis 2026-08-29 sesuai `rules.md` §20b. Sementara, boleh ditimpa.
Rencana lengkap + latar di `C:\Users\v28mt\.claude\plans\linked-sprouting-aho.md`.

**Status masuk:** Putaran 3 D3 SELESAI (7/7 laporan berfilter). pest 690, pint 31,
`uji-filter-laporan.mjs` 46/0, `uji-lebar-dokumen.mjs` 28/0, `sim:tautan-statis` 222.

## Lingkup (urutan pengerjaan)

### Part 1 — "Generate Laporan" + filter dibawa lewat HASH (bukan query string)
- `filter-laporan.js`: getter `hashFilter` (serialisasi `sp/td/ts/th/<dimensi>`
  ke `#...` via `URLSearchParams`), metode `dariHash()` (parse `location.hash`
  → terapkan), panggil `dariHash()` di `init()` setelah init `dimensi`+`tahun`.
- `kerangka-laporan.blade.php`:
  - `x-data="filterLaporan(...)"` PINDAH dari `<article>` ke `<div>` pembungkus
    yang memuat `<x-sim.page-header>` + `<article>` + blok unduh.
  - Tombol `:88-95`: "Buka di tab baru" → **"Generate Laporan"**, gaya primer,
    `:href="@js(route('laporan.dokumen',$slug)) + hashFilter"`, `target=_blank`.
  - Cabang `$dokumen`: TANPA `<x-sim.filter-laporan>`, `<header>` "Cakupan
    laporan" (`:116-152`) DIGANTI `<x-sim.kop-laporan :slug>`.
  - `.kertas-dokumen` di rute dokumen: buang `rounded-2xl border shadow-sm`.

### Part 2 — Kop surat dokumen resmi
- `LaporanData::instansi()` BARU — kementerian/pemerintah/dinas/alamat/kontak +
  path 2 logo. Telp/email = CONTOH (komentar; spanduk data-contoh menutupi).
- Aset baru `public/images/logo/lambang-malaka.png` (dari `refs/Lambang_Kabupaten_Malaka.png`).
- `components/sim/kop-laporan.blade.php` BARU — **flex/grid, TANPA `<table>`**
  (uji `kolomTerlebarDariHtml`). 2 lambang (Kementerian kiri, Malaka kanan) +
  teks instansi + blok judul tengah ("Laporan <judul>", sub-judul opsional,
  `<p x-text="kalimatCakupan">`, `<p x-text="labelTahunDokumen">TAHUN 2026</p>`).
- Getter Alpine `labelTahunDokumen`: rentang bila `td/ts`; satu tahun bila
  `tahunTunggal`; selain itu `'TAHUN ' + konfig.tahunAkhir`.
  `konfig.tahunAkhir` = `end(DummyData::deretTahunan()['tahun'])` = 2026.
- Pemisah teks pakai `·` (U+00B7), BUKAN `—` (uji parity menolak em dash).
- Opsional `meta['subjudulDokumen']` per laporan.

### Part 3 ✅ SELESAI (commit `0c12b9f`) — pemilih tahun TUNGGAL
Rekap Indikator Kawasan + Monografi SP. Jendela 5 tahun (2022–2026).
- `DummyData` metode BARU (`deretTahunan()` TAK disentuh): `indikatorKawasanTahun()`
  (irisan 2026 == `ringkasanDashboard()`), `rekapPerSpTahun($tahun)` (2026 ===
  `rekapPerSp()` presis), `iklimSpTahun($id,$tahun)` (goyang deterministik
  `jarak*0.006 + derau`, tak pernah 0 utk tahun lampau), `bagiProporsional()`.
- pest 696, pint 31, `uji-filter-laporan` 53/0, `uji-lebar-dokumen` 28/0.

**PUTARAN 5 SELESAI.** Semua tiga bagian ter-commit (`cb6c311`, `308d670`,
`0c12b9f`). Belum diperiksa mata: Ctrl+P dokumen resmi.
- `filterLaporan()`: `tahunTunggal:true`, `daftarTahun`, `tahunBawaan:2026`,
  `perTahun` blob.
- Klien: state `tahun`, `nilaiTahun('kunci')` baca `konfig.perTahun[tahun]`,
  nilai bertahun jadi `<span x-text>`, satu baris/section per SP.
  `:data-jumlah_kk="nilaiTahun(...)"` reaktif utk `<tfoot>` `jumlahTampak`.
- `filter-laporan.blade.php`: cabang `@if($tahunTunggal)` → `<select id="filter-laporan-tahun" x-model="tahun">`.
- **Bila jauh lebih besar dari perkiraan: berhenti di checkpoint bersih, laporkan.**

## Jebakan / batasan
- Hash `#...` BEKERJA di GitHub Pages; query string TIDAK (`notes.md` 1b.5).
  Tak menambah rute → `sim:tautan-statis` tetap 222, `deploy.yml` lolos.
- Kop WAJIB non-`<table>` — `kolomTerlebarDariHtml` (`HalamanTest.php:4740`).
- Uji parity `:4768` samakan cacah `<table>/<caption>/tabel-dokumen` rute
  berbingkai vs dokumen — kop tak boleh memuat ketiganya, `·` bukan `—`.
- Masthead + bilah rute BERBINGKAI tak berubah (`uji-filter-laporan.mjs` baca
  `dd[x-text="kalimatCakupan"]` di sana).
- Uji berubah: `:4768`, `:4896` (pisah iterasi doc), + uji baru `instansi()`.
  `uji-filter-laporan.mjs:259-265` DIBALIK (doc route TANPA bilah).

## Verifikasi
pest naik dari 690 · pint ≤31 · `sim:tautan-statis` 222 · `npm run build` ·
`uji-filter-laporan.mjs` hijau · `uji-lebar-dokumen.mjs` 28/0 · manual Ctrl+P.
