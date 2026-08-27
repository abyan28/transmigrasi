# Rencana Eksekusi — Tahap 2c: Isi Kolom 7 Halaman Laporan

Ditulis 2026-08-28 sesuai `rules.md` §20b. Bersifat sementara, boleh ditimpa.

## Lingkup

Mengisi kerangka 7 halaman laporan (dibuat Putaran 2) dengan kolom dan data
contoh. Lima laporan mengikuti berkas rujukan di `refs/`; dua dirancang dari
kolom data yang sudah ada.

## Referensi yang terbaca

| Berkas | Alat | Dipakai untuk |
|---|---|---|
| `Lap. Akhir Panen Jagung Polri MT. I 2025.pdf` | pdftotext -layout | Laporan Hasil Panen |
| `laporan alsintan.jpeg` | baca gambar | Laporan Alsintan |
| `laporan saprotan.jpeg` | baca gambar | Laporan Saprotan |
| `LAPORAN MONOGRAFI UPT KAPITAN MEO 2025.doc` | antiword | Laporan Monografi SP |
| `Poktan Wilayah Transmigrasi.xlsx` | unzip + regex | Laporan Daftar Poktan |

## Kolom tiap laporan

### 1. Laporan Hasil Panen  (ref: POLRI MT. I 2025)
Kolom ref: No, Kecamatan, Desa, Kelompok Tani, Ketua, Jumlah Anggota, Luas
Lahan (ha), Volume Benih (kg), Varietas, Realisasi Tanam (ha), Rencana Tanam
(bln), Realisasi Panen (ha), Puso (ha), Sisa belum dipanen (ha), Produktivitas
(ton/ha), Produksi (ton). Dikelompokkan per kecamatan + subtotal + grand total.

Adaptasi: satu baris per `hasil_panen`, dikelompokkan per **Satuan Permukiman**
(unit sistem kita), kolom Kecamatan/Desa tetap tampil. Tambah kolom Komoditas
dan Tahun Pengadaan. Belum Dipanen = realisasi_tanam - realisasi_panen - puso.
Sumber: `hasilPanen` -> `penanaman` (penanaman_id) -> `poktan` + `saprotan`
(penanaman.saprotan_id) + SP.

### 2. Laporan Monografi SP  (ref: Monografi Kapitan Meo, BAB II-V)
Monografi asli sangat naratif (iklim, topografi, sertifikat, KB, agama) - data
itu TIDAK ada di sistem. Yang dibuat: satu baris per SP dengan indikator yang
kita punya: Kecamatan/Desa, Tahun Penempatan, Luas Wilayah, KK Rencana, KK
Terisi, Rumah Terhuni, Poktan, Luas Lahan Tergarap, Produksi Panen (ton),
Pengaduan Terbuka. Catatan jujur: monografi lengkap per SP menunggu field Bab
II Monografi (Rombongan C). Sumber: `satuanPermukiman` + `rekapPerSp` + hitung
poktan per SP.

### 3. Laporan Alsintan  (ref: laporan alsintan.jpeg)
Kolom ref: No, Jenis Alat, Sumber Dana, Tahun Pengadaan, Poktan Penerima,
Ketua Poktan, Alamat (Kec./Desa), Jumlah (Unit). Satu baris per alsintan,
kelompok per SP, subtotal Jumlah + grand total.
Sumber: `alsintan` + `poktan` + SP. CATATAN: field kita bernama
`sumber_perolehan` / `tahun_perolehan`, sedangkan ref (dan saprotan) memakai
"Sumber Dana" / "Tahun Pengadaan". Laporan memakai label ref, membaca field
lama. Penyeragaman nama field alsintan = usul revisi terpisah, dicatat.

### 4. Laporan Saprotan  (ref: laporan saprotan.jpeg)
Kolom ref: No, Kecamatan, Desa, Kelompok Tani, Nama Ketua, NIK, No HP, Jumlah
Anggota, Luas Lahan (ha), Volume Benih (Kg), Varietas Benih, Volume Pupuk NPK
(Kg), Jadwal Tanam, Ket.
Adaptasi: model kita menyimpan benih dan pupuk sebagai baris terpisah. Bagian 1
= satu baris per saprotan Benih (SP, Kec, Desa, Poktan, Ketua, Kontak, Anggota,
Luas Lahan, Komoditas, Varietas, Volume Benih kg, Thn Pengadaan, Sumber Dana,
Jadwal Tanam). Bagian 2 = saprotan non-benih (pupuk/pestisida/mulsa): Poktan,
Jenis, Volume, Thn Pengadaan, Sumber Dana. Sesuai "dua bagian terpisah"
(rules §9 poin 16, notes 1m.4).

### 5. Rekap Indikator Kawasan  (tanpa ref, dirancang)
Kepala: identitas kawasan (nama, kabupaten, provinsi, no SK, tahun penetapan,
luas total, jumlah SP). Lalu blok indikator sebagai tabel:
- Kependudukan: KK, jiwa, petani, rumah terhuni/total, tingkat hunian
- Lahan & Produksi: luas lahan, realisasi tanam ha, hasil panen ha, puso ha,
  belum dipanen ha, produktivitas ton/ha, volume panen ton, harga rata-rata
- Kelembagaan: jumlah poktan, alsintan, saprotan
- Pengaduan: terbuka
- Rincian per SP (dari `rekapPerSp`): SP, KK, Rumah Terhuni, Luas Lahan,
  Volume Panen, Pengaduan Terbuka.
Sumber: `ringkasanDashboard` + `rekapPerSp` + `kawasan` + hitung kelembagaan.
Dasar periode: tahun panen berjalan (indikator produksi), beda dari Laporan
Hasil Panen yang pakai tahun pengadaan.

### 6. Laporan Daftar Poktan  (ref: Poktan Wilayah Transmigrasi.xlsx)
Kepala per SP: SP, Desa, Kecamatan, Kabupaten. Tabel: No, Kelompok Tani, Nama
Petani, NIK, No HP, Luas Lahan (Sawah/Basah | Kering), Titik Koordinat
(Lintang | Bujur), Ket. Dikelompokkan per poktan, baris anggota dari
`anggotaPoktan`, subtotal Luas per poktan. Sumber: `poktan` + `anggotaPoktan`
+ SP.

### 7. Laporan Daftar Transmigran (+ Rumah + Lahan)  (tanpa ref, kolom yang ada)
Tiga bagian dalam satu laporan:
- Bagian A - Transmigran: No, NIK, Nama KK, No KK, JK, Tempat/Tgl Lahir,
  Pendidikan, Pekerjaan, Jumlah Anggota, Pendapatan/bln, Daerah Asal, Tahun
  Kedatangan, SP, Status Tinggal.
- Bagian B - Rumah: No, No Rumah, SP, Penghuni, Kondisi, Status Hunian, Tahun
  Pembangunan, Luas Bangunan.
- Bagian C - Lahan: No, Kode Lahan, Pemilik, SP, Peruntukan, Luas, Luas
  Kering, Luas Basah.
Sumber: `transmigran` + `rumah` + `lahan`.

## Rencana teknis

1. `app/Support/LaporanData.php` baru - satu metode per laporan
   (`hasilPanen()`, `monografiSp()`, `alsintan()`, `saprotan()`,
   `indikatorKawasan()`, `poktan()`, `transmigran()`), masing-masing
   mengembalikan larik data untuk view. View DILARANG memanggil `DummyData`
   (guard Ide C) - semua lewat sini.
2. `routes/web.php`: loop laporan memanggil `LaporanData::{camel(slug)}()`
   dan menyatukan hasilnya ke `view(...)`.
3. `x-sim.kerangka-laporan`: bila `$slot` berisi, render isinya (tabel
   laporan) alih-alih penampung "format menyusul". Bagian cakupan + tombol
   unduh tetap.
4. 7 blade `pages/laporan/*` diisi tabel. Setiap `<table>` WAJIB `<caption>`
   sebagai anak pertama (guard Temuan 6). Tanpa em dash (R-02). Angka:
   `number_format(...,',','.')`, `translatedFormat` untuk tanggal.
5. Uji `HalamanTest`: tiap laporan memuat kolom-kolom kuncinya pada keluaran
   terender; `LaporanData` menghasilkan baris > 0; total = jumlah subtotal
   (untuk yang berkelompok). Dibuktikan lewat mutasi bila datanya bisa
   membedakan benar dari salah.

## Status pelaksanaan (2026-08-28)

**SELESAI.** 648 uji hijau (naik dari 635), `pint` 31. `app/Support/LaporanData.php`
baru; `x-sim.kerangka-laporan` render tabel bila diisi; 7 blade laporan berisi
tabel data contoh. Render nyata 7 halaman diperiksa. Dicatat ke `notes.md` 1o
dan `tasklist.md`. Berkas ini boleh ditimpa sesi berikutnya.

## Verifikasi (tercapai)
1. `vendor/bin/pest.bat` hijau, 648 (dari 635)
2. `vendor/bin/pint.bat --test` = 31 berkas
3. Render nyata 7 halaman laporan memuat tabel berisi
4. Dicatat ke `notes.md` 1o + `tasklist.md`

## Poin revisi yang BELUM dikerjakan setelah ini
- Rombongan B: anggota keluarga (istri + anak) dynamic form + field usia/agama
  pada form transmigran
- Rombongan C: field SP dari Bab II Monografi (untuk Laporan Monografi SP penuh)
- Pintasan laporan dari halaman daftar (bawa filter aktif)
- Pemilih periode untuk laporan lintas modul
- Penyeragaman nama field alsintan (`tahun_perolehan` -> `tahun_pengadaan`,
  `sumber_perolehan` -> `sumber_dana`) mengikuti saprotan
- Butir bagian 6 lain yang belum dibahas
