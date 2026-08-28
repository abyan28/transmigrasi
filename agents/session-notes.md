# Rencana Eksekusi — Putaran 3 D2b: orientasi dokumen + garis tabel

Ditulis 2026-08-28 sesuai `rules.md` §20b. Sementara, boleh ditimpa.

## Lingkup

Peninjauan pemilik proyek atas D2 memunculkan dua keluhan:

1. **Orientasi.** Laporan berkolom banyak (Hasil Panen 16 kolom) dipaksa ke
   kertas potret sehingga selalu perlu digulir. Seharusnya landscape,
   ditentukan banyaknya kolom.
2. **Garis tabel.** Tak ada garis pemisah antar kolom sama sekali, dan
   beberapa garis pemisah baris juga hilang.

D1 dan D2 sudah selesai (commit `5bf52b0`, `9c4076c`).

## Keputusan pemilik proyek (2026-08-28)

| # | Keputusan |
|---|---|
| 6 | Di aplikasi kertas **memenuhi ruang yang tersedia**; tab dokumen dan cetak memakai proporsi A4 sesungguhnya |
| 7 | Laporan landscape **dirapatkan** (huruf dan jarak sel lebih kecil); gulir tetap ada sebagai jaring pengaman, teks tak pernah terpotong |
| 8 | Berhenti setelah D2b untuk ditinjau lagi |

## EMPAT JEBAKAN UJI (wajib dipatuhi saat menulis kode)

1. **Kelas TIDAK BOLEH mengandung `>`.** Penjaga `memberi nama pada setiap
   tabel` (`HalamanTest.php:7160`) regex `/<table\b[^>]*>/` berhenti di `>`
   pertama. `[&>td]:border-r` memecah tag, `<caption>` tak lagi terbaca
   sebagai anak pertama, ketujuh berkas isi memerah. Penjaga
   `memberi scope pada setiap header kolom tabel` (`:2333`) sama.
   -> Kelas biasa + CSS terpusat.
2. **`overflow-x-auto` TIDAK BOLEH dicabut** (`:2101` mewajibkan tiap berkas
   ber-`<table` memuat literalnya).
3. **`w-[NNNpx]` > 360 dilarang** (`:2073`, regex `^w-\[(\d+)px\]$`).
   `max-w-[1160px]` lolos, `w-[1160px]` memerah.
4. **`@utility` Tailwind v4 tak bisa menargetkan turunan.** Preseden repo:
   CSS telanjang top level, contoh `.motif-baris-total` (`app.css:328-336`).

## Dua temuan yang ikut dibereskan

- **Rute dokumen nol penjaga.** `/laporan/{slug}/dokumen` +
  `pages/laporan/dokumen` + `layouts/dokumen` tak punya satu uji pun. Dua
  penyapu rute global melewatkannya (URI ber-`{` di `:2319`; `{slug}` diganti
  `1` lalu 404 dan `continue` di `:7064`). Bisa 500 tanpa memerahkan apa pun.
- **Pelanggaran `ui-spec.md` §2.3 baris 99.** Baris total wajib garis atas
  `navy-500` 2px, "bukan garis abu-abu biasa"; `.motif-baris-total` sudah
  dipakai 15 halaman. Tapi `isi/alsintan` + `isi/hasil-panen` menulis
  `border-t-2 border-gray-300` (persis yang dilarang), `isi/poktan` tak punya
  garis atas sama sekali.

## Rancangan

### Orientasi dari jumlah kolom

`LaporanData::KOLOM_LANDSCAPE = 9` (A4 potret nyaman menampung 8 kolom teks).
`meta()` menambah kunci `kolom`; `orientasi($slug)` menurunkannya.

| Laporan | Kolom terlebar | Orientasi |
|---|---|---|
| Hasil Panen | 16 (9 tetap + 7 dinamis) | landscape |
| Saprotan | 15 | landscape |
| Daftar Transmigran | 14 | landscape |
| Monografi SP | 13 | landscape |
| Daftar Poktan | 9 (kepala dua tingkat) | landscape |
| Alsintan | 9 | landscape |
| Rekap Indikator Kawasan | 6 | potret |

### Lebar kertas

| Tempat | Potret | Landscape |
|---|---|---|
| Halaman berbingkai | `max-w-5xl` (tetap) | **memenuhi ruang** (`max-w-full`) |
| Tab dokumen + cetak | `max-w-[820px]` | `max-w-[1160px]` |

`layouts/dokumen` `<main>`: `max-w-6xl` -> `max-w-full`.

### Cetak

`@page` pertama di repo, didorong `kerangka-laporan` lewat `@push('gaya')`;
`@stack('gaya')` ditambahkan ke `<head>` `layouts/app` dan `layouts/dokumen`
(push dari `@section` sampai ke head sebab layout dirender paling akhir).
Garis cetak digelapkan ke `#667085` -- `gray-200` lenyap di atas kertas.
`thead { display: table-header-group }` agar kepala berulang tiap halaman.

### Garis tabel

CSS telanjang top level `app.css`: `.tabel-dokumen th, .tabel-dokumen td`
border 1px `gray-200` / gelap `gray-800`. `.dokumen-landscape` merapatkan
padding dan huruf. Dipasang sebagai kelas biasa pada 12 tabel di
`pages/laporan/isi/*`; `divide-y divide-gray-100` dicabut (berlebihan).

### Baris total

`border-t-2 border-gray-300` -> `motif-baris-total` di alsintan + hasil-panen;
poktan yang belum punya ikut diberi.

## Penjaga baru

1. Rute dokumen tiap laporan 200, isi tabel sama dengan halaman berbingkai,
   tanpa kromo aplikasi. **Menutup celah nol-cakupan.**
2. Orientasi cocok dengan jumlah kolom **dihitung ulang dari HTML terender**:
   tiap `<table>`, jumlahkan `colspan` pada `<tr>` pertama di `<thead>`, ambil
   terbesar. Menangani kepala dua tingkat Poktan (5x1 + 2x2 = 9) dan kolom
   dinamis Hasil Panen (9 + 7 = 16).
3. Tiap tabel laporan memuat kelas `tabel-dokumen`.
4. Baris total memakai `motif-baris-total`; tak ada lagi
   `border-t-2 border-gray-300` di berkas laporan.
5. `tests/Browser/uji-lebar-dokumen.mjs` (PORT_DEVTOOLS 9349, belum terpakai):
   rute dokumen landscape pada 1440x900, `scrollWidth <= clientWidth`.
   `rules.md` §876 poin 10 mewajibkan uji peramban untuk tata letak.

## Verifikasi — SELESAI

1. **678 uji hijau** (naik dari 662; 16 uji baru dari 4 penjaga berdataset)
2. `pint` **31** (baseline)
3. `sim:tautan-statis` **223 alamat**, semua lolos regex `deploy.yml:110`
4. Tanpa em dash di berkas laporan (R-02)
5. `npm run build` bersih
6. **`node tests/Browser/uji-lebar-dokumen.mjs`: 28 lulus, 0 gagal** --
   ketujuh laporan muat tanpa gulir mendatar
7. Ctrl+P: **belum diperiksa mata**, menunggu peninjauan pemilik proyek

## Penyetelan lebar (dari pengukuran peramban, bukan tebakan)

Kepadatan awal `.375rem .5rem` menyisakan dua laporan meluber:
saprotan 1117>1108 (9px) dan transmigran 1186>1108 (78px). Dirapatkan ke
`.3125rem .375rem` -> saprotan muat, transmigran tinggal 1130>1108 (22px).
Lebar kertas dokumen landscape dinaikkan 1160px -> **1200px** -> semua muat.

**Cetak diberi kepadatan sendiri.** Layar memberi kertas landscape 1200px,
sedangkan A4 landscape dikurangi margin hanya ~277mm (~1047px). Selisih itu
nyata: yang pas di layar akan terpotong bila dicetak dengan kepadatan sama.
`@media print` karena itu menyetel `font-size: 8pt; padding: 2pt 3pt` untuk
tabel landscape. **Belum terverifikasi mesin** -- uji peramban hanya mengukur
layar; perlu Ctrl+P oleh pemilik proyek.

## Belum dikerjakan (D4)

Dokumen acuan belum disentuh: `ui-spec.md` belum memuat `.tabel-dokumen`,
`.kertas-dokumen`, kelas orientasi, maupun `@page`. `rules.md` §12 dan
`prd.md` §7.9 juga masih menunggu. Sesuai rencana, D4 dikerjakan setelah
bentuk dokumennya disetujui agar tidak ditulis dua kali.
