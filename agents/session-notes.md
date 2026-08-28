# Rencana Eksekusi — Putaran 3: Halaman Laporan (filter sendiri + tampilan dokumen)

Ditulis 2026-08-28 sesuai `rules.md` §20b. Sementara, boleh ditimpa.

## Lingkup

Pemilik proyek menilai menu "Laporan" **berantakan**. Permintaannya: tiap
halaman laporan punya filter datanya sendiri, isinya disajikan seperti dokumen
di dalam bingkai, dengan "buka di tab baru" untuk tampilan penuh.

Dua butir tunggu **gugur**: pintasan laporan berfilter dan pemilih periode
lintas-modul. Keduanya hanya cara mewariskan filter ke halaman yang tidak
punya filter; begitu tiap laporan punya filter sendiri, pewarisan tak perlu.

## Keputusan pemilik proyek (2026-08-28)

| # | Keputusan |
|---|---|
| 1 | **Kertas bergaya**, bukan `<iframe>`. Dokumen dirender di halaman yang sama; penjaga uji tetap utuh |
| 2 | **Filter harus jalan di GitHub Pages** - dikerjakan Alpine di sisi peramban |
| 3 | **SP + periode + dimensi khas tiap laporan**, bukan dua filter seragam |
| 4 | **Cakupan tetap dicetak sebagai kalimat**, kini disusun dari filter aktif |
| 5 | **Rekap Indikator Kawasan: bangun agregasi per SP** (metode baru, dashboard tidak disentuh) |
| 6 | **Berhenti setelah D2 untuk ditinjau** |

## Tiga temuan penelusuran

1. `prd.md` §7.9 baris 217 sudah menuntut "menyediakan filter data" untuk
   Laporan. Ini mengembalikan kepatuhan PRD, bukan cakupan baru. Paragraf
   baris 221 masih memuat keputusan 2026-08-17 yang sudah dibalik Putaran 2.
2. **Filter server-side akan jadi kontrol mati (R-26).** `deploy.yml:110`
   menolak `?` dan `=` lewat regex dan menggagalkan penerbitan.
   `notes.md` 1b.5 baris 306 sudah memutuskan ini "sengaja tidak diakali".
   Karena itu filter WAJIB Alpine sisi peramban.
3. **Tidak ada uji yang melarang filter di halaman laporan.** Larangan hidup
   semata di dokumen. Penjaga baru wajib ditulis, tak bisa mengandalkan yang
   ada.

## "Berantakan" itu terukur

- Hasil Panen 16 kolom, Saprotan benih 15, Transmigran 14, Monografi 13
- Monografi 7 tabel + 6 seksi; Indikator Kawasan 5 tabel; Poktan 4; Transmigran 3
- `$angka` ditulis ulang di **6 view dengan tanda tangan berbeda** (2 desimal
  di hasil-panen/monografi/poktan, 0 di alsintan/indikator-kawasan/transmigran)
  plus salinan ketujuh privat `LaporanData::angka()`
- `transmigran.blade.php` memakai `@foreach` polos, tanpa jaring keadaan kosong

## Prasyarat: id belum dibawa keluar LaporanData

- `poktan_id` **tidak pernah dibawa keluar satu metode pun**
- `kelompokkanPerSp()` mengelompokkan menurut NAMA SP, bukan id (baris 567);
  `sp_id` tidak diteruskan ke tingkat grup. Cacat laten: dua SP bernama sama lebur
- `saprotan().nonBenih` tanpa `sp_id`; `poktan()` buang `id_poktan` +
  `satuan_permukiman_id`; `monografiSp().baris` buang `sp_id`

## Pelaksanaan bertahap

- **D1** - fondasi data: bawa keluar `sp_id`/`poktan_id`; `kelompokkanPerSp()`
  per id; satukan `$angka` (7 salinan jadi 1); `@foreach` -> `@forelse` pada
  transmigran; buang `jumlah_anggota` dari kolom subtotal hasilPanen. Commit.
- **D2** - kertas + tab baru: bingkai kertas di `kerangka-laporan`; badan
  dokumen dipisah ke `pages/laporan/isi/{slug}`; `layouts/dokumen.blade.php`
  baru (BUKAN `fullscreen-layout` yang kode mati + store sidebar tertinggal);
  rute `/laporan/{slug}/dokumen` + 7 slug di `DaftarTautanStatis` (208 -> 215
  alamat); tombol tab baru (`rel="noopener"` + sr-only); `@media print`
  pertama di repo. Commit.
- **TINJAU DI SINI** - terbitkan ke Pages, pemilik proyek periksa bentuknya.
- **D3** - filter Alpine (prasyarat: agregasi per SP Indikator Kawasan).
- **D4** - penjaga + dokumen acuan (`rules.md` §12, `ui-spec.md` §6.9/6.10,
  `prd.md` §7.9 basi, `notes.md` 1r, `tasklist.md`).

## Cara filter bekerja (D3, dicatat sekarang agar D1/D2 tak menutup jalannya)

Blade tetap merender seluruh baris server-side. Alpine hanya menyembunyikan
baris dan menghitung ulang subtotal yang tampak. `data-sp`/`data-tahun`/
`data-poktan` pada tiap `<tr>`; `x-text` pada sel subtotal/total. Alasannya:
seluruh data tetap ada di HTML sehingga penjaga yang membaca `getContent()`
tetap berlaku. Biayanya penjumlahan hidup di dua tempat, ditutup uji peramban.

**Rentang tahun aman di laporan transaksi** meski §12 poin 12 melarang di
"halaman laporan": larangan itu bersandar §9 poin 8b (luas terhitung ganda
lintas tahun). Baris Hasil Panen/Alsintan/Saprotan tiap baris milik tepat satu
tahun pengadaan.

## Verifikasi D1 + D2

1. `vendor/bin/pest.bat` hijau, >= 662 dan naik
2. `vendor/bin/pint.bat --test` <= 31
3. `sim:tautan-statis` 215 alamat, lolos regex `^/[A-Za-z0-9/_.~-]*$`
4. Render 7 `/laporan/*` + 7 `/laporan/*/dokumen`: 200, tanpa
   `Undefined array key`, tanpa em dash (R-02)
5. Halaman berbingkai dan rute dokumen memuat isi tabel IDENTIK
6. Angka sama dirender sama di 7 laporan setelah `$angka` disatukan
