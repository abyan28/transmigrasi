# Rencana Eksekusi — Putaran 6: Peristiwa penduduk + perluasan Laporan Monografi SP

Ditulis 2026-08-29 sesuai `rules.md` §20b. Rencana lengkap + latar di
`C:\Users\v28mt\.claude\plans\linked-sprouting-aho.md`.

**Status masuk:** Putaran 5 SELESAI. pest ~698, pint 31, `uji-filter-laporan.mjs`
53/0, `uji-lebar-dokumen.mjs` 28/0, `sim:tautan-statis` 222.

## Lingkup & urutan (commit per checkpoint)

### 1. Model peristiwa anggota keluarga
- `app/Enums/StatusAnggotaKeluarga.php` BARU — `{Aktif, Meninggal, Pindah}`,
  `PunyaLabel` + `PunyaWarnaBadge` (Aktif=success, Meninggal=gray, Pindah=warning).
  Docblock: beda dari `StatusTinggal` (keluarga) & `AlasanPergantianKK` (suksesi KK).
- `DummyData::anggotaKeluarga()` — tiap baris +`status` (default 'Aktif'),
  +`tanggal_peristiwa` (null), +`keterangan_peristiwa` (null).
  Ubah 2 baris jadi contoh: id 12 ROSALIA SERAN → Meninggal 2024-03-12
  ("sakit usia lanjut"); id 27 KORNELIUS TAHU → Pindah 2025-07-01
  ("merantau ke Kupang, kembali ke daerah asal").
- `DummyData::transmigran()` cacah anggota → **hanya `status==='Aktif'`**.
- Docblock `anggotaKeluarga()`: pembalikan sebagian `rules.md §9c`.
- Uji `DummyDataTest`: status ∈ enum; `jumlah_anggota_keluarga` = 1 + cacah Aktif.

### 2. UI transmigran — badge + modal "Catat Peristiwa"
- `routes/web.php`: `POST /transmigran/{id}/anggota/{anggota}/catat-peristiwa`
  → redirect `transmigran.detail` tab=keluarga + flash. Name
  `transmigran.anggota.catat-peristiwa`. (rute non-GET → `sim:tautan-statis` tetap 222)
- `pages/transmigran/detail.blade.php` tab "keluarga": kolom "Status"
  (`<x-sim.status-badge>`), baris non-Aktif redup + sub-baris keterangan
  peristiwa; kolom aksi tombol "Catat Peristiwa" (baris Aktif saja).
  Modal `formPeristiwaAnggota` (pola `formGantiKepalaKeluarga`, field di-set
  Alpine): hidden anggota_id+nama, select status (opsi tanpa Aktif),
  tanggal_peristiwa (default hari ini), keterangan_peristiwa (textarea).
- Tombol "Ganti Kepala Keluarga" yang sudah ada TETAP (itulah peristiwa KK).
- `pages/transmigran/form.blade.php` langkah Anggota Keluarga: repeater hanya
  anggota Aktif; non-Aktif read-only di bawah.
- Uji `HalamanTest`: kolom "Status", tombol, `id="formPeristiwaAnggota"`, rute.

### 3. `DummyData::strukturUmurSp($id)` + `mutasiPendudukSp($id)`
- `strukturUmurSp` — 14 kelompok umur (0-4..65+) × [laki, perempuan]. Σ = jiwa
  SP (= Σ kk SP dari `rekapPerSp()` × rata2 anggota kawasan, dibulatkan).
  Piramida deterministik dari `$id` (pola `iklimSpTahun`), koreksi sisa 1 sel.
- `mutasiPendudukSp` — kumulatif sejak `tahun_penempatan`: Lahir / Meninggal /
  Pindah / Datang / Meninggalkan lokasi × L/P + baris "Pertambahan bersih".
  TANPA perkawinan.
- Uji `DummyDataTest`: Σ strukturUmur == jiwa; deterministik; 14 kelompok;
  mutasi deterministik, bersih = lahir+datang−meninggal−pindah−keluar,
  tak ada "Perkawinan".

### 4. `LaporanData` — `pendahuluan/bab3/bab4/bab5`
- Metode privat pola `bab2()`. `monografiSp()` tiap item +`pendahuluan`,
  `kependudukan`, `sosial_ekonomi`, `sosial_budaya`.
- "Keadaan penduduk sekarang" bertahun → peta `[tahun=>...]` + kunci di
  `filterLaporan('monografi-sp')['kependudukanTahun']`.

### 5. `pages/laporan/isi/monografi-sp.blade.php`
- Buang "Bab II." dari judul Keadaan Wilayah.
- `<h4>` Kependudukan / Sosial Ekonomi / Sosial Budaya + sub-tabel.
- Tiap `<table>`: `.tabel-dokumen` + `<caption>` anak pertama; lebar →
  `overflow-x-auto`; baris Jumlah `.motif-baris-total`.
- `<td x-text="nilaiKependudukan('kk')">` bila bertahun (jangan lipat DOM 5×).
- `filter-laporan.js`: getter `nilaiKependudukan(kunci)` bila dipakai.
- Uji `HalamanTest` ~5144: +'Kependudukan','Sosial Ekonomi','Sosial Budaya',
  'Struktur Penduduk','Mutasi Penduduk'; `not->toContain('Bab II')`.
  Cek `meta('monografi-sp')['kolom']` ≤ 13.

### 6. Dokumen `agents/`
- `rules.md §9c` pembalikan sebagian bertanggal (coretan alasan lama).
- `rules.md` Monografi: bagian disajikan + dilewati & alasan.
- `data-dictionary.md`: field `anggota_keluarga`, enum, metode baru.
- `notes.md` §1v Putaran 6. `ui-spec.md` §6.9 + modal peristiwa. `tasklist.md`.

## Jebakan
- `kolomTerlebarDariHtml` (`HalamanTest.php:4740`) — tabel monografi baru
  jangan > kolom ikhtisar (13). Kop tetap non-`<table>`.
- Penjaga caption: tiap `<table>` butuh `.tabel-dokumen` + `<caption>` anak
  pertama; `str_contains($isi,'<table')` juga cek file mentah.
- `deretTahunan()` panjang seragam 11 — JANGAN tambah seri di sana.
- `rekapPerSp()` Σ == `ringkasanDashboard()` — jangan sentuh.
- Enum baru: `PunyaWarnaBadge` WAJIB metode `warna()`.
- rules.md §19a: keputusan tak boleh berdasar cacah baris contoh.

## Verifikasi
pest naik dari ~698 · pint ≤31 · `sim:tautan-statis` 222 · `npm run build` ·
`uji-lebar-dokumen.mjs` 28/0 · `uji-filter-laporan.mjs` hijau ·
`uji-form-transmigran.mjs`/`uji-gulir-modal.mjs` hijau · manual Ctrl+P.

**Bila jauh lebih besar dari perkiraan: berhenti di checkpoint bersih, laporkan.**
