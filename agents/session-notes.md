# Rencana Eksekusi — Penyeragaman nama field alsintan  [SELESAI 2026-08-28]

Ditulis 2026-08-28 sesuai `rules.md` §20b. Sementara, boleh ditimpa.

**Hasil:** 662 uji hijau (satu penjaga baru), pint 31. Semua berkas pada
daftar di bawah disunting. `grep` verifikasi: `sumber_perolehan` nol di
`app/` & `resources/`; `tahun_perolehan` hanya tersisa di infrastruktur /
inventaris_sp / fasilitas_sp (sengaja). Commit menyusul.

## Lingkup (butir 1 daftar tunggu, `notes.md` §1o.4)

`saprotan` sudah memakai `tahun_pengadaan` / `sumber_dana` (diseragamkan
Putaran 1, cocok dengan kamus data). `alsintan` masih memakai
`tahun_perolehan` / `sumber_perolehan`. Kedua berkas rujukan
(`laporan alsintan.jpeg`, `laporan saprotan.jpeg`) memakai label
"Tahun Pengadaan" / "Sumber Dana". Laporan Alsintan sekarang menampilkan
label rujukan sambil membaca field lama lewat pemetaan di `LaporanData`.

**Tujuan:** rename `alsintan.tahun_perolehan` → `tahun_pengadaan`,
`alsintan.sumber_perolehan` → `sumber_dana`. Murni penyeragaman nama;
tipe, nullability, dan makna tetap.

## PENTING: jangan sentuh modul lain

`tahun_perolehan` juga dipakai `inventaris_sp`, `fasilitas_sp`,
`infrastruktur` — **itu tetap `tahun_perolehan`**. `sumber_perolehan`
hanya dipakai alsintan (yang lain sudah `sumber_dana`). Rename hanya baris
yang menyangkut alsintan.

## Berkas yang disentuh

**Kode**
1. `app/Support/DummyData.php` — 5 baris `alsintan()` (id 1-5) + docblock +
   komentar inline: `tahun_perolehan`→`tahun_pengadaan`,
   `sumber_perolehan`→`sumber_dana`.
2. `app/Support/LaporanData.php` — `alsintan()` map: `$a['sumber_perolehan']`
   → `$a['sumber_dana']`, `$a['tahun_perolehan']` → `$a['tahun_pengadaan']`.
   Tulis ulang CATATAN docblock (tak lagi "usul revisi tersendiri").

**Tampilan**
3. `resources/views/pages/alsintan/form.blade.php` — dua field: `name`, `id`,
   `<label>`, `old()`, `$data[...]`. Label "Tahun Perolehan"→"Tahun Pengadaan",
   "Sumber Perolehan"→"Sumber Dana". Var `$opsiSumberDana` sudah ada, tak
   berubah (ViewServiceProvider tidak disentuh).
4. `resources/views/pages/alsintan/detail.blade.php` — 3 tempat + 2 label.
5. `resources/views/pages/alsintan/index.blade.php` — `$a['tahun_perolehan']`
   + header `Tahun` → `Tahun Pengadaan`.
6. `resources/views/pages/poktan/detail.blade.php` — tabel alsintan,
   `$a['tahun_perolehan']`.

**Uji**
7. `tests/Feature/HalamanTest.php` — `it('memakai nilai enum pada data
   contoh...')` baris ~2924: `$baris['sumber_perolehan']`→`$baris['sumber_dana']`
   + sesuaikan komentar. Tambah satu penjaga: form + detail + index alsintan
   memakai `name="tahun_pengadaan"` / `name="sumber_dana"`, tidak lagi nama
   lama; `DummyData::alsintan()` tiap baris punya kunci baru.

**Dokumen acuan**
8. `agents/data-dictionary.md` §8.3 — baris tabel + prosa sekitarnya + catatan
   rename bertanggal (pola sama seperti catatan `tahun_perolehan` dicabut di
   §8.4).
9. `agents/rules.md` §7b poin 1 & 2 — "tahun perolehan, sumber perolehan"
   → "tahun pengadaan, sumber dana".
10. `agents/erd.md` — baris indeks alsintan (269); baris saprotan (270) yang
    juga masih `tahun_perolehan` padahal sudah lama berubah — ikut dibetulkan;
    baris riwayat migrasi #20 diberi catatan.
11. `agents/notes.md` §1o.4 — tandai selesai; tambah subbagian ringkas.
    `agents/tasklist.md` — pindahkan butir dari "Ditunda" ke selesai.

## Verifikasi

1. `vendor/bin/pest.bat` — hijau, ≥ 662 (satu penjaga baru)
2. `vendor/bin/pint.bat --test` — ≤ 31
3. Render nyata: `/alsintan`, `/alsintan/1`, `/alsintan` form (tambah+ubah),
   `/poktan/1`, `/laporan/alsintan` — semua memuat nilai tahun & sumber,
   tak ada `Undefined array key`.
4. `grep -rn "tahun_perolehan\|sumber_perolehan" app/ resources/` — hanya
   sisa yang sah (inventaris/fasilitas/infrastruktur untuk `tahun_perolehan`;
   nol untuk `sumber_perolehan`).

## Catatan

Field `tahun_pengadaan` belum ada di `ValidationRules::label()`; saprotan
pun tak menambahnya (peta itu parsial, hanya untuk label non-obvious).
Ikuti preseden: tidak menambah entri.
