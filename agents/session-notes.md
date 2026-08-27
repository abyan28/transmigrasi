# Rencana Eksekusi — Revisi Putaran 2: Fitur Laporan + Filter Rentang Tahun

Ditulis 2026-08-28 sesuai `rules.md` §20b (rencana wajib sebelum eksekusi).
Bersifat sementara, boleh ditimpa sesi berikutnya.

## Lingkup

Rombongan A sisanya dari `notes.md` bagian 6:
- **A3** — fitur ekspor diganti fitur laporan (menu tersendiri)
- **A4** — filter rentang tahun (dua filter) pada datatable bersumbu waktu
- **A5** — halaman laporan dibuat sekarang dengan data contoh

## Keputusan yang SUDAH diambil pemilik proyek (diskusi 2026-08-27)

Tercatat lengkap di berkas rencana `linked-sprouting-aho.md` bagian "Ditunda":

1. **Laporan = dokumen bernama.** Menu "Laporan" jadi rumahnya; tiap laporan
   satu halaman berformat tetap. Kolomnya ditentukan pemilik proyek.
2. **Tombol ekspor DICABUT dari kerangka `halaman-daftar`.** Sekarang ia di
   dalam komponen bersama itu, muncul otomatis di belasan halaman, sebagian
   besar tanpa laporan di baliknya (kontrol mati R-26).
3. **Dipasang kembali hanya sebagai pintasan**, di halaman yang punya laporan
   pasangannya, membawa filter aktifnya (pewarisan lewat query string).
4. **Rentang tahun di halaman bersumbu waktu, BUKAN di halaman laporan.**
   Laporan menuliskan cakupannya sebagai teks.
5. **Laporan lintas-modul punya pemilih periode sendiri.**

## Temuan pemeriksaan kode

### A3 — tombol ekspor

Dipakai di **11 tempat**, salah satunya `components/sim/halaman-daftar.blade.php`
(kerangka bersama → otomatis di semua halaman daftar). Sisanya eksplisit di:
dashboard, kependudukan/rekap, lahan/index, panen/index, panen/rekap,
pengaduan/index, pengaduan/rekap, rumah/index, transmigran/index.

Komponen `tombol-ekspor` sudah mewarisi filter lewat `request()->query()` dan
sengaja dirender sebagai teks jujur "segera hadir" (belum menghasilkan berkas
sampai Tahap 10).

### A4 — rentang tahun: mana yang AMAN

`rules.md` §9 poin 8b sangat tegas: **rekap panen tidak boleh kumulatif lintas
tahun** — "2 ha yang ditanami tiga tahun akan terbaca 6 ha". Poin 8c-1: "satu
penanaman hanya muncul pada satu tahun".

| Halaman | Sumbu waktu | Rentang tahun aman? |
|---|---|---|
| `/panen` (daftar) | `periode_panen` per baris | AMAN — daftar transaksi, tanpa agregasi luas |
| `/penanaman` (daftar) | `periode_tanam` per baris | AMAN — sama |
| `/audit-log` | tanggal peristiwa | AMAN — tak diagregasi; belum ada filter tahun sama sekali |
| `/kependudukan/rekap` tab "tahun" | `tahun` per baris | AMAN — batasi baris tahun, bukan menjumlahkan |
| `/panen/rekap` | tahun panen tunggal | **TIDAK AMAN** — melanggar §9 poin 8b |
| daftar aset (`/alsintan`, `/rumah`, dll) | "tahun" ambigu | TIDAK — perolehan? anggaran? penempatan? |

Ini **mengoreksi** saran lisan 2026-08-27 yang menyebut "rekap panen" boleh —
keliru, sebab rekap justru yang paling tidak boleh.

### A5 — format kolom belum ada

`refs/` memuat: "Lap. Akhir Panen Jagung Polri MT. I 2025.pdf" (belum terbaca,
mesin tak punya perender PDF), "laporan alsintan.jpeg", "laporan saprotan.jpeg",
"LAPORAN MONOGRAFI UPT KAPITAN MEO 2025.doc".

`rules.md` §12 poin 4 mendaftar 12 kategori rekap. Pemilik proyek menyatakan
akan memberi format kolom "beberapa halaman laporan tertentu".

## Rencana eksekusi bertahap

### Tahap 2a — kerangka (tanpa format kolom, dikerjakan sekarang)
1. `rules.md` §12 direvisi menyeluruh: poin 6 dibalik, keputusan menu Laporan
   ditulis, aturan pewarisan filter + pencabutan dari `halaman-daftar`.
2. `MenuHelper`: grup/menu "Laporan" baru dengan ikon `charts`, berisi
   submenu per laporan.
3. Cabut `<x-sim.tombol-ekspor />` dari `halaman-daftar`; cabut 8 pemasangan
   eksplisit KECUALI yang jadi pintasan ke laporan.
4. Rute + halaman index `/laporan` (daftar laporan tersedia).
5. Rute + kerangka halaman tiap laporan: judul, pernyataan cakupan (teks),
   tabel kosong berlabel "Format kolom menyusul", tombol unduh jujur
   "segera hadir".
6. Komponen `x-sim.pintasan-laporan` (tombol di halaman daftar → laporan,
   membawa query string).
7. Penjaga uji: (a) tak ada `tombol-ekspor` di `halaman-daftar`; (b) setiap
   submenu Laporan menunjuk rute terdaftar; (c) pintasan laporan membawa
   query string.

### Tahap 2b — filter rentang tahun (dikerjakan sekarang)
8. Komponen `x-sim.filter-rentang-tahun` (dua `<select>`: dari–sampai).
9. Pasang di `/panen`, `/penanaman`, `/audit-log`, `/kependudukan/rekap`
   tab tahun. Rute masing-masing menyaring `tahun_dari`..`tahun_sampai`.
10. `/audit-log` dapat filter tahun untuk pertama kalinya.
11. Penjaga uji: rentang menyaring baris dengan benar; `tahun_dari` > 
    `tahun_sampai` ditangani; TIDAK dipasang di `/panen/rekap`.

### Tahap 2c — isi halaman laporan (DITUNDA, menunggu format kolom)
Setelah pemilik proyek memberi format kolom per laporan.

## Penjaga yang WAJIB dipatuhi (sudah ada)
1. View dilarang memanggil `DummyData` → lewat rute / `ViewServiceProvider`
2. Setiap field form wajib punya tempat tampil di rincian
3. Setiap `<table>` wajib `<caption>` anak pertama
4. Alamat aksi tak boleh berakar domain
5. Komponen tanpa pemakai ditolak
6. `pint` tak menambah utang (baseline 31)
7. Blok tombol daftar/filter tak ditulis ulang (pakai komponen)

## Verifikasi
1. `vendor/bin/pest.bat` — hijau, ≥623
2. `vendor/bin/pint.bat --test` — ≤31 berkas
3. Cuplikan render 55 halaman sebelum/sesudah pencabutan tombol ekspor —
   dinormalkan (CSRF, id `uniqid`), dikontrol dua cuplikan; hanya halaman
   yang memang kehilangan tombol yang boleh berbeda
4. Render nyata `/laporan` + tiap halaman laporan + 3 halaman berfilter rentang (`/panen`, `/penanaman`, `/audit-log`)
5. Penjaga baru dibuktikan lewat mutasi
6. Catat ke `notes.md` bagian 1n + `tasklist.md` + baris tabel keputusan

## Keputusan final (dikonfirmasi 2026-08-28)

### A4 — filter rentang tahun dipasang HANYA di:
- `/panen` (daftar)
- `/penanaman` (daftar)
- `/audit-log` (dapat filter tahun untuk pertama kalinya)

Pemilik proyek melihat opsi `/kependudukan/rekap` tab tahun dan tabel
Transmigrasi (`/transmigran` tahun kedatangan, `/rumah` tahun pembangunan)
lalu tidak memilihnya. Cakupan A4 sengaja sempit: tiga daftar transaksi
bersumbu waktu saja. TIDAK di `/panen/rekap` (melanggar rules §9.8b).

### A5 — 7 halaman laporan kerangka (isi kolom menyusul):
1. Laporan Hasil Panen
2. Laporan Monografi SP
3. Laporan Alsintan
4. Laporan Saprotan  (terpisah dari Alsintan, bukan digabung)
5. Rekap Indikator Kawasan
6. Laporan Daftar Poktan
7. Laporan Daftar Transmigran  (memuat data Rumah dan Lahan tiap transmigran)

### Pintasan laporan di halaman daftar
BELUM dikerjakan putaran ini. Menu Laporan saja dulu. Komponen
`x-sim.pintasan-laporan` (Tahap 2a langkah 6) DITUNDA. Tahap 2b langkah 9
tidak lagi menyentuh `/kependudukan/rekap`.

## Status pelaksanaan (2026-08-28)

- **Tahap 2a SELESAI.** `rules.md` §12 ditulis ulang; menu "Laporan" +
  ikon `laporan`; `tombol-ekspor` dicabut dari `halaman-daftar` + 9 halaman
  lalu berkasnya dihapus; rute `/laporan` + 7 rute laporan; komponen
  `x-sim.kerangka-laporan`; 8 blade di `pages/laporan/`. Langkah 6 (pintasan)
  ditunda.
- **Tahap 2b SELESAI.** `x-sim.filter-rentang-tahun`;
  `DummyData::saringRentangTahun()`; dipasang di `/panen`, `/penanaman`,
  `/audit-log`. TIDAK di `/panen/rekap`.
- **Tahap 2c DITUNDA** — menunggu format kolom tiap laporan dari dinas.
- Uji: 635 hijau. `pint`: 31. Dicatat ke `notes.md` 1n + `tasklist.md`.
- Dokumen permanen sudah diperbarui; berkas ini boleh ditimpa sesi berikutnya.
