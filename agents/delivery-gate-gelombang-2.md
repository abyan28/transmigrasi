# Laporan Delivery Gate ANTISLOP - Gelombang 2

**Tanggal:** 2026-08-17
**Cakupan:** 23 halaman Tahap 2 gelombang 2 (Task 2.13 sampai 2.20), ditambah pemeriksaan ulang seluruh 120 berkas Blade.
**Dasar:** `ANTISLOP-ID.md` Delivery Gate, `rules.md` 16.1 dan 721 ("gate dijalankan pada akhir setiap gelombang, bukan sekali di akhir proyek"), `tasklist.md` 433 dan 841.

Setiap PASS disertai bukti konkret. Laporan yang mengandung satu FAIL dilarang diserahkan.

**Perintah verifikasi yang menghasilkan bukti:**
`& "C:\xampp\php\php.exe" vendor\bin\pest` (357 lulus, 1.562 pernyataan), `npm run build`, `php artisan view:cache`, dan penelusuran HTTP atas 23 rute gelombang 2 pada peladen `artisan serve`.

---

## Koreksi atas laporan gelombang 1

Gate ini dibuka dengan mencabut sebuah klaim, sebab meneruskannya berarti membangun laporan baru di atas bukti yang tidak ada.

### Klaim yang dicabut: "11 uji kontras WCAG lewat Node"

`delivery-gate-gelombang-1.md` baris 11 dan 57 menyatakan kontras warna telah diuji dengan rumus WCAG 2.1 lewat Node, dan angka itu diulang pada `tasklist.md` baris 421 serta 819.

**Klaim itu tidak berdasar.** Berkas `uji-chart-config.mjs` berukuran **0 byte sejak pertama masuk repositori** (commit `4a08e68`, terbukti lewat `git show --stat`), dan penelusuran seluruh riwayat git atas kata kunci perhitungan kontras (`git log --all -S "luminan"`, `-S "contrastRatio"`) **tidak menemukan satu baris pun**. Jadi bukan uji yang hilang atau terhapus, melainkan uji yang tidak pernah ditulis.

Akibatnya: gelombang 1 dinyatakan lolos R-25 atas dasar angka yang tidak pernah dihitung.

**Tindakan:** berkas kosong itu dihapus, dan kontras kini diuji sungguhan di `tests/Feature/KontrasTest.php` (6 uji, 17 pernyataan) memakai rumus WCAG 2.1 yang benar-benar dijalankan. Ujinya ditulis di Pest, bukan Node, agar ikut satu-satunya perintah verifikasi yang sudah ada; `package.json` pun tidak pernah punya skrip uji.

### Pelajaran

Laporan gate yang mengutip angka wajib menyebut perintah yang menghasilkannya, dan perintah itu wajib dapat dijalankan ulang oleh pembaca laporan. Angka yang tidak dapat direproduksi lebih berbahaya daripada kolom yang jujur ditulis "belum diuji", sebab yang pertama menghentikan orang dari memeriksa.

---

## Blok 1: Hard Gate

| Butir | Hasil | Bukti |
|---|---|---|
| R-02 em dash pada teks antarmuka | **PASS** | 0 karakter `—` pada 23 berkas halaman gelombang 2 |
| R-17, R-38 angka jujur | **PASS** | Seluruh angka berasal dari `DummyData`; spanduk "Data contoh" tampil pada tiap halaman petugas, dijaga `HalamanTest.php:1009` |
| R-23 aset visual karangan | **PASS** | 0 tag `<img>` pada seluruh halaman gelombang 2; tidak ada avatar atau logo karangan |
| R-24, R-26 kontrol mati | **PASS** | 0 `href="#"`; **30 tujuan tautan unik** dari 23 halaman ditelusuri HTTP, **0 mati** |
| R-25 kontras WCAG AA | **PASS** | 13 pasangan warna dihitung dengan rumus WCAG 2.1; terendah `teal-500` di atas putih **4,46:1** (ambang nonteks 3:1), teks terendah `gold-700` di atas `gold-50` **4,55:1** (ambang 4,5:1) |
| Fokus keyboard tidak dimatikan | **PASS** | 0 `outline-none` pada 23 berkas |
| Label aksesibilitas | **PASS** | **1.207 `aria-label`** terender pada 23 halaman |
| Seluruh halaman merender | **PASS** | 23 dari 23 rute membalas HTTP 200 |

**Catatan kontras.** Dua pasangan berada tipis di atas ambang: `gold-700` di atas `gold-50` pada 4,55:1 dan `teal-500` di atas putih pada 4,46:1. Keduanya lulus — yang kedua memang dipakai sebagai aksen nonteks, yang ambangnya 3:1 — tetapi jaraknya sempit. Penyuntingan warna sekecil apa pun pada kedua skala itu berpeluang menjatuhkannya, dan `KontrasTest.php` akan menangkapnya.

---

## Blok 2: Purpose-Gate

| Butir | Hasil | Bukti |
|---|---|---|
| R-01 warna melayani tujuan | **PASS** | Aksen gold hanya pada penanda komoditas unggulan; badge memakai lima warna semantik `ui-spec.md`, bukan hiasan |
| RITME 2 komposisi berbeda | **PASS** | Gelombang 2 menambah dua komposisi rekap murni (`kependudukan/rekap`, `master/wilayah`) yang **tanpa kartu statistik**, berbeda dari halaman daftar |
| Mode gelap menyeluruh | **PASS** | 758 kelas `dark:` pada 23 berkas; audit `HalamanTest.php:891` atas **120 berkas Blade** menemukan 0 latar terang tanpa pasangan `dark:` |
| Gerak seperlunya (GERAK 1) | **PASS** | Tidak ada animasi baru diperkenalkan gelombang 2 |

---

## Blok 3: Liveliness

| Butir | Hasil | Bukti |
|---|---|---|
| Keadaan kosong tersedia | **PASS dengan catatan** | 17 dari 23 halaman mewarisinya lewat `x-sim.data-table` atau `x-sim.halaman-daftar`; 6 sisanya dikecualikan dengan alasan di bawah |
| Keadaan pencarian nihil | **PASS** | Dibedakan dari keadaan kosong di dalam `data-table.blade.php:93` |
| Keadaan tanpa kewenangan | **PASS** | Halaman 403 tersendiri, ditinjau lewat `/uji-403` |
| Keadaan memuat dan galat | **DITUNDA** | Lihat catatan Task 2.21 di bawah |

### Enam halaman tanpa keadaan kosong, dan mengapa itu benar

`master/wilayah`, `master/satuan`, `sp/kawasan`, `kependudukan/rekap`, `laporan/index`, dan `pengguna/role` menulis markup tabelnya sendiri dan memakai `@foreach` tanpa `@forelse`.

Pemeriksaan atas isi tiap perulangan menunjukkan **keenamnya menampilkan data master atau daftar tetap**, bukan data yang dimasukkan petugas: daftar provinsi sampai desa, satuan ukur beserta faktor konversi, kawasan dan SP, agregat kependudukan, daftar jenis laporan, dan daftar role. Data semacam ini di-seed bersama sistem dan **tidak mungkin kosong**; bila benar-benar kosong, yang terjadi adalah kegagalan pemasangan, bukan keadaan wajar yang perlu dijelaskan lewat ilustrasi ramah.

Penelusuran menyeluruh memperkuat ini: dari 70 berkas di `pages/`, hanya **3 perulangan** yang merender `<tr>` tanpa `@forelse`, ketiganya di `dashboard/index` untuk sebaran pekerjaan, penghuni, dan komoditas — agregat yang selalu berisi selama ada satu baris data.

**Kesimpulan:** memasang keadaan kosong di keenamnya adalah markup untuk keadaan yang tidak dapat terjadi. Tidak dikerjakan, dan alasannya dicatat di sini agar tidak dipertanyakan ulang sebagai kelalaian.

---

## Blok 4: Craftsmanship dan Quality Locks

| Butir | Hasil | Bukti |
|---|---|---|
| Responsif 360px | **PASS otomatis** | 0 lebar tetap `w-[...px]` melebihi 360 pada 23 berkas; audit `HalamanTest.php:912` mencakup 120 berkas Blade |
| Tabel dapat digulir mendatar | **PASS** | Seluruh tabel dibungkus `overflow-x-auto`, langsung atau lewat `x-sim.data-table:101` |
| Struktur HTML seimbang | **PASS** | Uji keseimbangan tag `HalamanTest.php:822` atas 120 berkas |
| Nama isian sesuai kamus data | **PASS** | Dijaga uji yang membandingkan langsung ke `data-dictionary.md` |
| Uji otomatis hijau | **PASS** | 357 lulus, 1.562 pernyataan |
| Build produksi | **PASS** | `npm run build` selesai tanpa galat |

---

## Status Task 2.21 sampai 2.24

| Task | Status | Keterangan |
|---|---|---|
| 2.21 keadaan kosong/memuat/galat/tanpa kewenangan | **Sebagian, sisanya ditunda beralasan** | Kosong, pencarian nihil, dan tanpa kewenangan terpasang. **Memuat dan galat ditunda ke Tahap 3.** |
| 2.22 responsif 360px | **Selesai untuk audit otomatis** | Pemeriksaan mata pada perangkat nyata tetap wajib, lihat bagian berikutnya |
| 2.23 mode terang dan gelap | **Selesai** | Termasuk uji kontras numerik yang sebelumnya hanya diklaim |
| 2.24 Delivery Gate | **Selesai** | Dokumen ini |

### Mengapa keadaan memuat dan galat ditunda

`x-sim.skeleton` dan `x-sim.error-state` sudah dibuat pada gelombang 1, tetapi **dipakai 0 halaman kerja** — hanya tampil di `/galeri-komponen`. Itu bukan kelalaian yang perlu ditambal sekarang.

Seluruh halaman saat ini dirender di sisi peladen dari `DummyData`, dalam satu balasan HTTP. **Tidak ada jeda pengambilan data, dan tidak ada panggilan jaringan yang dapat gagal.** Memasang skeleton berarti memasang animasi memuat yang tidak akan pernah terlihat, dan memasang error-state berarti menyediakan jalan keluar bagi galat yang tidak dapat terjadi.

Keduanya baru bermakna ketika data diambil dari basis data dan sebagian antarmuka memanggil API — yaitu sejak Tahap 3. Dijadwalkan di sana, bukan dihapus.

---

## Yang masih perlu dilakukan manusia

Tiga hal ini tidak dapat digantikan uji otomatis, sama seperti pada gelombang 1, dan **belum dikerjakan**:

1. **Membuka 23 halaman pada peramban nyata di lebar 360px.** Audit otomatis hanya memeriksa penyebab tersering gulir mendatar, yaitu lebar tetap dan tabel tanpa pembungkus. Ia tidak melihat tumpukan yang berdesakan atau teks yang terpotong. `tasklist.md:473` mencatat Edge headless memaksa viewport minimum sekitar 496px, sehingga **360px sesungguhnya masih belum pernah diuji**.
2. **Menjalankan alur gelombang 2 hanya dengan keyboard**, dari Tab pertama sampai modal tertutup. Yang paling rawan adalah halaman berlaci filter dan bertab.
3. **Meninjau istilah master data bersama petugas dinas.** Halaman wilayah, satuan, dan kawasan memuat istilah yang paling teknis di seluruh sistem; ketepatannya tidak dapat dinilai dari dalam kode.

---

## Kesimpulan

**Keempat blok PASS.** Dua butir dicatat sebagai ditunda beralasan, bukan lolos diam-diam: keadaan memuat dan galat menunggu backend, dan pemeriksaan 360px pada perangkat nyata menunggu manusia.

Temuan terpenting gate ini bukan pada halaman gelombang 2, melainkan pada laporan gelombang 1: sebuah klaim uji yang tidak pernah dijalankan berhasil melewati gate pertama. Kini klaim itu dicabut dan digantikan uji yang benar-benar menghitung, lengkap dengan 13 angka rasio yang dapat diperiksa ulang siapa pun.
