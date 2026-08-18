# Sistem Informasi Kawasan Transmigrasi Kobalima Timur

Sistem informasi berbasis web untuk digitalisasi monitoring pertanian dan tata kelola data
kawasan transmigrasi Kobalima Timur, Kabupaten Malaka, Nusa Tenggara Timur.

Data transmigran, rumah, lahan, komoditas, hasil panen, alsintan, saprotan, infrastruktur,
kelembagaan poktan, dan pengaduan kawasan dikumpulkan dalam satu platform, lalu dipakai untuk
monitoring, pelaporan, dan pengambilan keputusan berbasis bukti.

**Lokus:** 6 Satuan Permukiman yang tersebar di 4 kecamatan.

| Satuan Permukiman | Desa | Kecamatan |
|---|---|---|
| SP Kapitan Meo | Kapitan Meo | Laen Manen |
| SP Tniumanu | Tniumanu | Laen Manen |
| SP Harekakae | Harekakae | Malaka Tengah |
| SP Weoe / Uluk Lubuk | Weoe | Wewiku |
| SP Tualaran | Naet | Rinhat |
| SP Weain | Weain | Rinhat |

## Status Pengembangan

**Tahap 2 selesai, progres 84%.** Seluruh antarmuka sudah berdiri, tetapi datanya masih berasal
dari `app/Support/DummyData.php`, bukan dari database. Ini disengaja: tampilan divalidasi
bersama tim dan dinas lebih dahulu, backend menyusul pada Tahap 3 sampai 9 dan hanya menukar
sumber data tanpa mengubah tampilan.

Yang belum tersedia: autentikasi, hak akses, dan seluruh operasi simpan ke database. Tombol
simpan, ubah, dan hapus saat ini mengembalikan pesan tanpa menulis apa pun.

| | |
|---|---|
| Halaman | 69 |
| Komponen bersama | 44 |
| Tata letak | 6 |
| Rute | 123 (50 GET) |
| Enum | 31 |
| Pengujian | 413 uji Pest, 1926 pernyataan |

## Teknologi

| Lapis | Pilihan |
|---|---|
| Framework | Laravel 12.65 |
| PHP | 8.2.12 (XAMPP) |
| Basis data | MySQL / MariaDB, nama `sim_transmigrasi` |
| Gaya | Tailwind CSS v4 |
| Interaktivitas | Alpine.js 3.14 |
| Build | Vite 7 |
| Grafik | ApexCharts 5.3 |
| Pemilih tanggal | Flatpickr 4.6, locale Indonesia |
| Peta | Leaflet 1.9, ubin OpenStreetMap |
| Pengujian | Pest 3 |
| Fondasi UI | TailAdmin Laravel (MIT) |

Tiga keputusan yang perlu diketahui sebelum menyentuh kode:

- **PHP 8.2.12 milik XAMPP dipakai, bukan versi yang ada di PATH.** Laravel 12 mendukung
  8.2 sampai 8.4, sedangkan PHP di PATH mesin pengembangan adalah 8.5. Perintah artisan dan
  Pest dijalankan lewat `C:\xampp\php\php.exe`.
- **Tidak ada `tailwind.config.js`.** Tailwind v4 meniadakan berkas itu; seluruh design token
  ditulis pada blok `@theme` di `resources/css/app.css`.
- **Leaflet dimuat lewat impor dinamis** di `resources/js/peta.js`. Hanya enam formulir yang
  memerlukan peta, sehingga memasukkannya ke bundel utama akan membebani seluruh halaman lain.

## Modul

- **Data master wilayah** — kawasan, satuan permukiman, desa, kecamatan, inventaris, fasilitas
- **Kependudukan** — transmigran, rumah, status hunian, verifikasi data
- **Lahan** — lahan usaha dan pekarangan, dokumen kepemilikan, koordinat
- **Kelembagaan** — profil poktan, daftar anggota, alsintan, saprotan
- **Produksi pertanian** — komoditas, musim tanam, riwayat tanam, hasil panen
- **Infrastruktur** — air, irigasi, listrik, jalan produksi, telekomunikasi, gudang
- **Pengaduan** — pengajuan publik tanpa login, penanganan oleh petugas, pelacakan nomor
- **Laporan** — rekap kawasan dan ekspor

## Instalasi

```bash
git clone https://github.com/abyan28/transmigrasi.git
cd transmigrasi

composer install
npm install

copy .env.example .env
php artisan key:generate
```

Sesuaikan basis data pada `.env`, lalu buat dan migrasikan:

```bash
mysql -u root -e "CREATE DATABASE sim_transmigrasi;"
php artisan migrate
npm run build
```

## Menjalankan

```bash
composer run dev
```

Satu perintah ini menyalakan server Laravel, Vite, queue worker, dan pemantau log sekaligus.
Aplikasi terbuka di http://localhost:8000.

Untuk diakses dari perangkat lain pada jaringan yang sama:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Aset dibangun dengan `npm run build`, bukan `npm run dev`. Menjalankan `npm run dev` membuat
berkas `public/hot` yang mengalihkan seluruh klien ke `localhost:5173`, sehingga gaya tidak
termuat di perangkat lain.

Akses lewat terowongan atau reverse proxy sudah ditangani. `bootstrap/app.php` memercayai
header `X-Forwarded-*`, sehingga `asset()` dan `url()` mengikuti skema dan host aslinya.

## Pengujian

```bash
& "C:\xampp\php\php.exe" vendor\bin\pest
```

Hasil terakhir: 413 lulus, 1926 pernyataan.

## Penerbitan ke GitHub Pages

Antarmuka Tahap 2 diterbitkan sebagai berkas statis di
https://abyan28.github.io/transmigrasi/ dan diperbarui otomatis setiap `git push` ke `main`.

Alur kerja `.github/workflows/deploy.yml` menjalankan aplikasi sebentar di runner, menggilas
setiap alamat dari `php artisan sim:tautan-statis` menjadi HTML, lalu menerbitkannya. Per
2026-08-18 tercatat 122 halaman.

Pengaturan sekali di awal: **Settings → Pages → Source: GitHub Actions**.

Karena yang disajikan hanya berkas statis, seluruh tombol simpan, ubah, dan hapus tidak
berfungsi. Itu bukan kemunduran: rutenya memang belum menyimpan apa pun selama Tahap 2.
Batasan selengkapnya beserta yang harus dikerjakan saat backend masuk ada pada
`agents/notes.md` bagian 1b.

Saat menambah halaman atau tautan, jangan menulis `href="/sesuatu"` maupun `src="/images/..."`
secara langsung. Gunakan `route()`, `url()`, atau `asset()`, sebab tautan mentah akan rusak di
GitHub Pages meski tampak benar di localhost.

## Struktur

```
.github/workflows/
  deploy.yml      penerbitan statis ke GitHub Pages
app/
  Console/        perintah sim:tautan-statis
  Enums/          31 enum, sumber tunggal seluruh pilihan baku
  Helpers/        MenuHelper
  Http/           controller dan middleware
  Support/        DummyData, penyimpanan dokumen, aturan validasi
  View/           kelas komponen Blade
resources/
  css/app.css     design token pada blok @theme
  js/             app.js, chart-config.js, input-angka.js, kunci-gulir.js, peta.js
  views/
    components/   44 komponen bersama
    layouts/      6 tata letak
    pages/        69 halaman
routes/web.php    123 rute
agents/           dokumen acuan: prd, rules, workflow, erd, ui-spec, tasklist, notes
refs/             berkas rujukan, bukan bagian aplikasi
```

Folder `agents/` sengaja ikut terlacak bersama kode. Seluruh keputusan desain, aturan bisnis,
dan riwayat perubahan tercatat di sana; `agents/notes.md` memuat alasan di balik keputusan
teknis yang tidak terlihat dari kode.

## Lisensi dan Atribusi

Antarmuka dibangun di atas [TailAdmin Laravel](https://github.com/TailAdmin/tailadmin-laravel),
templat dasbor berlisensi MIT. Berkas `LICENSE` pada akar proyek adalah lisensi MIT milik
TailAdmin.
