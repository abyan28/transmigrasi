# data-dictionary.md
## Kamus Data — Sistem Informasi Digitalisasi Monitoring Pertanian dan Tata Kelola Data Kawasan Transmigrasi Kobalima Timur

Dokumen ini merinci setiap kolom pada 33 tabel yang dirancang di `erd.md`. Dipakai sebagai acuan saat membuat form frontend (nama field, label, dan aturan validasi) maupun saat menulis migration Laravel.

**Cara membaca kolom "Null":** `TIDAK` berarti wajib diisi, `YA` berarti boleh kosong.

**Singkatan pada kolom "Kunci":** PK = primary key, FK = foreign key, UQ = unique, IDX = terindeks.

---

## Daftar Isi

1. [Aturan Umum](#1-aturan-umum)
2. [Domain Pengguna dan Sistem](#2-domain-pengguna-dan-sistem) &mdash; termasuk `kode_pemulihan_sandi` (2.3)
3. [Domain Master Wilayah](#3-domain-master-wilayah)
4. [Domain Aset SP](#4-domain-aset-sp)
5. [Domain Master Daftar Pilihan](#5-domain-master-daftar-pilihan)
6. [Domain Kependudukan](#6-domain-kependudukan)
7. [Domain Lahan](#7-domain-lahan)
8. [Domain Kelembagaan dan Sarana](#8-domain-kelembagaan-dan-sarana)
9. [Domain Produksi Pertanian](#9-domain-produksi-pertanian)
10. [Domain Infrastruktur dan Pengaduan](#10-domain-infrastruktur-dan-pengaduan)
11. [Daftar Nilai Enum Terpusat](#11-daftar-nilai-enum-terpusat)
12. [Aturan Validasi Bersama](#12-aturan-validasi-bersama)
13. [Daftar Kewenangan (Permission)](#13-daftar-kewenangan-permission)

---

## 1. Aturan Umum

Aturan berikut berlaku untuk **seluruh tabel**, sehingga tidak diulang pada tiap bagian.

### 1.1 Kolom waktu sistem

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| `created_at` | `TIMESTAMP` | YA | Diisi otomatis oleh Eloquent saat data dibuat |
| `updated_at` | `TIMESTAMP` | YA | Diisi otomatis oleh Eloquent saat data diubah |
| `deleted_at` | `TIMESTAMP` | YA | Penanda soft delete; hanya pada tabel data utama |

Tabel yang memakai soft delete: `user`, `kawasan_transmigrasi`, `satuan_permukiman`, `inventaris_sp`, `fasilitas_sp`, `transmigran`, `rumah`, `lahan`, `poktan`, `anggota_poktan`, `alsintan`, `saprotan`, `komoditas`, `hasil_panen`, `infrastruktur`, `pengaduan`.

Tabel referensi wilayah (`provinsi`, `kabupaten`, `kecamatan`, `desa`), `satuan`, `riwayat_penghunian`, `riwayat_kepala_keluarga`, `penanaman`, `penanganan_pengaduan`, dan `audit_log` **tidak** memakai soft delete: tabel referensi dilindungi `RESTRICT`, sedangkan tabel riwayat memang tidak boleh dihapus.

`kawasan_transmigrasi` memakai soft delete karena merupakan data yang dikelola pengguna, bukan referensi administratif baku.

### 1.2 Kolom dokumen

Setiap kolom bernama `dokumen_*` atau `foto_*` bertipe `VARCHAR(255)` dan berisi **path relatif** terhadap `storage/app/private/`, bukan isi berkas (`rules.md` §14a.3–4).

- Batas ukuran 5 MB per berkas
- Tipe diterima: `jpg`, `jpeg`, `png`, `webp`, `pdf`
- Pola penamaan: `[NamaDokumen]_[nama-transmigran].[ekstensi]`, spasi diganti tanda hubung
- Contoh nilai tersimpan: `transmigran/12/KartuKeluarga_yohanes-bere.pdf`

### 1.3 Kolom koordinat

Pasangan kolom koordinat selalu memakai bentuk yang sama:

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| `lintang` | `DECIMAL(10,7)` | YA | Rentang −90 sampai 90. Kobalima Timur sekitar −9,5 |
| `bujur` | `DECIMAL(10,7)` | YA | Rentang −180 sampai 180. Kobalima Timur sekitar 124,9 |

Presisi 7 angka desimal setara ketelitian ±1 cm, jauh melampaui kebutuhan lapangan. Ditampilkan 6 desimal sesuai `ui-spec.md` §10.

### 1.3a Kolom pengenal publik

Alamat URL tidak menampilkan primary key berurutan (`rules.md` 4.0a). Dua kolom berikut menyediakan pengenal publik, sedangkan primary key integer tetap dipakai untuk relasi antar-tabel.

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `uuid` | `CHAR(36)` | TIDAK | UQ, IDX | UUID versi 4, dibuat otomatis saat data disimpan |
| `slug` | `VARCHAR(120)` | TIDAK | UQ, IDX | Hanya pada data master; tidak berubah meski nama disunting |

**Tabel yang memakai `uuid`:** `transmigran`, `rumah`, `lahan`, `hasil_panen`, `pengaduan`

**Tabel yang memakai `slug`:** `satuan_permukiman`, `kawasan_transmigrasi`, `poktan`, `komoditas`

**Aturan:**
- **Slug dilarang diturunkan dari data pribadi.** Nama orang tidak boleh menjadi slug, sebab alamat URL tersimpan pada riwayat peramban dan log server. Untuk data pribadi, slug justru menurunkan kerahasiaan dibandingkan id angka.
- Slug dibuat sekali saat data disimpan, lalu tidak berubah, agar tautan yang sudah dibagikan tidak rusak.
- Penerapan `uuid` dilakukan **bertahap**, dimulai dari tabel berdata pribadi.

### 1.4 Kolom uang dan luas

| Jenis | Tipe | Alasan |
|---|---|---|
| Nilai uang (pendapatan, harga jual) | `DECIMAL(15,2)` | Menampung sampai ratusan triliun tanpa galat pembulatan `FLOAT` |
| Luas lahan | `DECIMAL(12,2)` | Satuan hektare, 2 desimal (`rules.md` §13.3.4) |
| Volume panen | `DECIMAL(12,3)` | 3 desimal agar panen 1 kg (0,001 ton) tetap terekam |
| Faktor konversi | `DECIMAL(10,6)` | Menampung faktor kecil seperti 0,001 |

---

## 2. Domain Pengguna dan Sistem

### 2.1 `user`

Akun untuk masuk ke sistem. Menggantikan tabel `users` bawaan Laravel; model wajib menyetel `protected $table = 'user'`.

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_user` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `role_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | Menunjuk role beserta kewenangan dan cakupan datanya |
| `nama` | `VARCHAR(255)` | TIDAK | | Nama lengkap pengguna |
| `username` | `VARCHAR(50)` | TIDAK | UQ, IDX | Kredensial login alternatif, huruf kecil tanpa spasi |
| `email` | `VARCHAR(255)` | TIDAK | UQ, IDX | Kredensial login utama |
| `password` | `VARCHAR(255)` | TIDAK | | Hash bcrypt, tidak pernah ditampilkan |
| `password_harus_diganti` | `BOOLEAN` | TIDAK | | Bawaan `FALSE`. Bernilai `TRUE` setelah Admin menyetel ulang kata sandi, memaksa penggantian saat login berikutnya |
| `telepon` | `VARCHAR(20)` | YA | | Format `08xxxxxxxxxx` |
| `jabatan` | `VARCHAR(100)` | YA | | Jabatan pada instansi, contoh "Staf Bidang Ketransmigrasian" |
| ~~`foto`~~ | | | | **DIPINDAH 2026-09-02** ke registry `berkas` lewat pivot atau FK langsung (`rules.md` 14a.8) |
| `is_aktif` | `BOOLEAN` | TIDAK | | Bawaan `TRUE`; akun nonaktif ditolak saat login |
| `last_login_at` | `TIMESTAMP` | YA | | Pemantauan aktivitas akun |
| `remember_token` | `VARCHAR(100)` | YA | | Bawaan Laravel |

**Seluruh pengguna sistem adalah petugas.** Warga transmigran tidak memiliki akun. Data mereka diinput dan dikelola petugas, sedangkan pengaduan diajukan lewat kanal publik tanpa login (§10.2).

**Dua kredensial login.** Sistem menerima **email atau username** pada satu kolom isian yang sama. Username disediakan karena sebagian petugas lebih terbiasa mengetik nama pengguna singkat daripada alamat surel panjang.

**Cakupan data tidak disimpan di sini,** melainkan pada `role.cakupan_data`. Untuk role bercakupan `Per SP`, daftar SP yang ditugaskan disimpan pada tabel `user_satuan_permukiman` (§2.6).

**Catatan:**
- Penonaktifan akun memakai `is_aktif = FALSE`, bukan penghapusan, agar jejak audit tetap utuh.
- Tidak ada pendaftaran mandiri. Akun hanya dibuat Admin (`rules.md` §5).
- Pemulihan kata sandi dilakukan Admin, bukan lewat tautan surel. Rincian pada `rules.md` §14b.

### 2.1a `role`

Role bersifat **dinamis**: dibuat dan diatur Admin lewat antarmuka, bukan dikunci di dalam kode.

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_role` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `nama` | `VARCHAR(100)` | TIDAK | UQ | Contoh: Operator SP |
| `deskripsi` | `VARCHAR(255)` | YA | | Penjelasan singkat kegunaan role |
| `cakupan_data` | `ENUM` | TIDAK | | Lihat §11.25 |
| `is_bawaan` | `BOOLEAN` | TIDAK | | `TRUE` untuk empat role hasil seeder; tidak dapat dihapus |
| `is_terkunci` | `BOOLEAN` | TIDAK | | `TRUE` hanya untuk role Admin; kewenangannya tidak dapat diubah |
| `is_aktif` | `BOOLEAN` | TIDAK | | Role nonaktif tidak dapat dipilih saat membuat akun baru |

**Catatan:**
- Role Admin memiliki `is_bawaan = TRUE` dan `is_terkunci = TRUE`, sehingga tidak dapat dihapus maupun dikurangi kewenangannya. Ini menjamin sistem tidak pernah kehilangan jalur administrasi.
- Role yang masih dipakai minimal satu akun tidak dapat dihapus. Aturan hapus FK memakai `RESTRICT`.

### 2.1b `permission`

Daftar kewenangan baku yang ditanam sistem lewat seeder. **Admin tidak dapat menambah atau menghapus kewenangan,** karena setiap kewenangan harus memiliki pasangan pemeriksa di dalam kode. Admin hanya memasangkannya ke role.

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_permission` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `nama` | `VARCHAR(100)` | TIDAK | UQ | Format `modul.aksi`, contoh `transmigran.lihat` |
| `modul` | `VARCHAR(50)` | TIDAK | IDX | Pengelompokan pada antarmuka pengaturan role |
| `aksi` | `ENUM` | TIDAK | | Lihat §11.26 |
| `label` | `VARCHAR(150)` | TIDAK | | Teks Bahasa Indonesia yang tampil di antarmuka |
| `urutan` | `SMALLINT UNSIGNED` | TIDAK | | Urutan tampil dalam kelompok modulnya |

Daftar lengkap kewenangan ada pada §13.

### 2.1c `role_permission`

Tabel pivot penghubung role dan kewenangan.

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_role_permission` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `role_id` | `BIGINT UNSIGNED` | TIDAK | FK, UQ¹, IDX | |
| `permission_id` | `BIGINT UNSIGNED` | TIDAK | FK, UQ¹, IDX | |

¹ UNIQUE gabungan `(role_id, permission_id)`.

### 2.1d `user_satuan_permukiman`

Penugasan SP untuk pengguna berrole bercakupan `Per SP`. Satu operator dapat memegang lebih dari satu SP.

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_user_satuan_permukiman` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `user_id` | `BIGINT UNSIGNED` | TIDAK | FK, UQ¹, IDX | |
| `satuan_permukiman_id` | `BIGINT UNSIGNED` | TIDAK | FK, UQ¹, IDX | |

¹ UNIQUE gabungan `(user_id, satuan_permukiman_id)`.

**Catatan:** tabel ini hanya bermakna bagi role bercakupan `Per SP`. Untuk cakupan `Semua`, isinya diabaikan. Bila seorang operator belum ditugaskan SP mana pun, ia tidak melihat data apa pun, bukan melihat semuanya. Ini disengaja agar kelalaian penugasan tidak berubah menjadi kebocoran data.

### 2.2 `audit_log`

Riwayat perubahan data penting (`rules.md` §14.5).

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_audit_log` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `user_id` | `BIGINT UNSIGNED` | YA | FK, IDX | Pelaku perubahan; `NULL` bila akun sudah dihapus |
| `aksi` | `ENUM` | TIDAK | IDX | Lihat §11.2 |
| `nama_tabel` | `VARCHAR(64)` | TIDAK | IDX | Tabel yang diubah, contoh `transmigran` |
| `record_id` | `BIGINT UNSIGNED` | TIDAK | | Nilai primary key baris yang diubah |
| `data_lama` | `JSON` | YA | | Nilai sebelum perubahan; `NULL` saat aksi Tambah |
| `data_baru` | `JSON` | YA | | Nilai setelah perubahan; `NULL` saat aksi Hapus |
| `ip_address` | `VARCHAR(45)` | YA | | Menampung IPv6 |
| `user_agent` | `VARCHAR(255)` | YA | | Peramban dan perangkat |

**Catatan:** hanya kolom yang benar-benar berubah yang disimpan pada `data_lama`/`data_baru`, bukan seluruh baris, agar ukuran log terkendali. Kolom `password` wajib dikecualikan dari pencatatan.

### 2.3 `kode_pemulihan_sandi`

Kode verifikasi pemulihan kata sandi mandiri (`rules.md` 14b poin 7 sampai 10).

Menggantikan tabel bawaan `password_reset_tokens`, yang strukturnya dirancang untuk token panjang pada tautan sekali klik. Sistem ini mengirim **kode enam digit yang diketik**, agar tetap dapat dipakai ketika surel dan peramban berada di perangkat berbeda, atau ketika jaringan lokus gagal memuat tautan panjang.

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_kode_pemulihan` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `user_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | Akun sasaran |
| `kode_hash` | `VARCHAR(255)` | TIDAK | | **Sidik kode, bukan kodenya.** Basis data yang bocor tidak boleh langsung memberi jalan masuk |
| `kedaluwarsa_pada` | `TIMESTAMP` | TIDAK | IDX | 15 menit sejak dibuat |
| `percobaan` | `TINYINT UNSIGNED` | TIDAK | | Bertambah tiap kode salah dimasukkan; kode hangus setelah 5 |
| `dipakai_pada` | `TIMESTAMP` | YA | | Diisi saat kode berhasil dipakai, menjadikannya sekali pakai |
| `created_at` | `TIMESTAMP` | TIDAK | IDX | Dasar penghitungan batas 3 permintaan per jam per akun |

**Catatan penting:**
- Kode disimpan sebagai **sidik**, sama seperti kata sandi. Alasannya berlaku meski kode hanya hidup 15 menit.
- Kode lama milik satu akun **wajib dibatalkan** ketika kode baru diminta, agar tidak ada dua kode sah beredar bersamaan.
- Baris tidak dihapus setelah dipakai, melainkan ditandai lewat `dipakai_pada`, agar percobaan pemakaian ulang tetap terlacak.
- Tabel ini **tidak menyimpan alamat surel tujuan**. Alamat dibaca dari `user` saat pengiriman, sehingga perubahan surel tidak meninggalkan salinan usang di sini.
- Permintaan kode **tidak pernah dibalas berbeda** antara akun yang ada dan tidak ada (`rules.md` 14b poin 9), sehingga tabel ini juga tidak boleh dipakai sebagai sumber pesan galat yang membedakan keduanya.

---

## 3. Domain Master Wilayah

Enam tabel berikut membentuk hierarki **bercabang dua** dari `kabupaten`, dan sengaja dipisah agar wilayah baru cukup ditambah sebagai baris, tanpa `ALTER TABLE` (`rules.md` §4a.4).

```
provinsi → kabupaten ─┬─ kecamatan → desa ─────┐
                      │  (cabang administratif) │
                      └─ kawasan_transmigrasi ──┴─→ satuan_permukiman
                         (cabang program)
```

`satuan_permukiman` menaut ke kedua cabang sekaligus lewat `desa_id` dan `kawasan_id`.

### 3.1 `provinsi`

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_provinsi` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `nama` | `VARCHAR(100)` | TIDAK | UQ | Contoh: Nusa Tenggara Timur |
| `kode` | `VARCHAR(10)` | YA | | Kode wilayah BPS, contoh `53` |

### 3.2 `kabupaten`

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_kabupaten` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `provinsi_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | |
| `nama` | `VARCHAR(100)` | TIDAK | UQ¹ | Contoh: Malaka |
| `kode` | `VARCHAR(10)` | YA | | Kode wilayah BPS |

¹ UNIQUE gabungan `(provinsi_id, nama)`.

### 3.3 `kecamatan`

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_kecamatan` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `kabupaten_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | |
| `nama` | `VARCHAR(100)` | TIDAK | UQ¹ | Laen Manen, Malaka Tengah, Wewiku, Rinhat |
| `kode` | `VARCHAR(10)` | YA | | Kode wilayah BPS |

¹ UNIQUE gabungan `(kabupaten_id, nama)`.

### 3.4 `desa`

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_desa` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `kecamatan_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | |
| `nama` | `VARCHAR(100)` | TIDAK | UQ¹ | Kapitan Meo, Tniumanu, Harekakae, Weoe, Naet, Weain |
| `kode` | `VARCHAR(10)` | YA | | Kode wilayah BPS |

¹ UNIQUE gabungan `(kecamatan_id, nama)`.

### 3.5 `kawasan_transmigrasi`

Kawasan transmigrasi sebagai unit program. Berbeda dari `kecamatan` dan `desa` yang merupakan pembagian administratif, kawasan adalah wilayah perencanaan yang dapat memotong batas kecamatan. Kawasan Kobalima Timur, misalnya, menaungi 6 SP yang tersebar di 4 kecamatan.

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_kawasan_transmigrasi` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `kabupaten_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | |
| `nama` | `VARCHAR(150)` | TIDAK | UQ¹ | Contoh: Kobalima Timur |
| `kode_kawasan` | `VARCHAR(20)` | YA | UQ | Kode ringkas untuk laporan |
| `tahun_penetapan` | `YEAR` | YA | | Tahun kawasan ditetapkan pemerintah |
| `nomor_sk` | `VARCHAR(100)` | YA | | Nomor SK penetapan kawasan |
| `luas_total` | `DECIMAL(12,2)` | YA | | Hektare; luas kawasan keseluruhan |
| ~~`dokumen_pendukung`~~ | | | | **DIPINDAH 2026-09-02** ke registry `berkas` lewat pivot atau FK langsung (`rules.md` 14a.8) |
| `keterangan` | `TEXT` | YA | | |

¹ UNIQUE gabungan `(kabupaten_id, nama)`.

**Catatan:** tabel ini tidak ada pada SQL referensi. Tanpa tabel ini, kawasan transmigrasi hanya hidup di judul dokumen dan sistem tidak dapat direplikasi ke kawasan lain sebagaimana diwajibkan `rules.md` §4a.4.

### 3.6 `satuan_permukiman`

Satuan Permukiman (SP), unit lokus utama sistem. Seluruh data operasional bermuara ke tabel ini agar dashboard dapat dipecah per lokus. SP adalah **titik temu** antara cabang administratif (desa) dan cabang program (kawasan).

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_satuan_permukiman` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `kawasan_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | Kawasan transmigrasi yang menaungi SP ini |
| `desa_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | Desa tempat SP berdiri; menentukan kecamatan secara transitif |
| `user_id` | `BIGINT UNSIGNED` | YA | FK | Penanggung jawab data SP (`rules.md` §4a.6) |
| `nama` | `VARCHAR(150)` | TIDAK | UQ | Contoh: SP Kapitan Meo |
| `kode_sp` | `VARCHAR(20)` | YA | UQ | Kode ringkas untuk laporan |
| `tahun_penempatan` | `YEAR` | YA | | Tahun SP mulai ditempati |
| `luas_lahan` | `DECIMAL(12,2)` | YA | | Total luas kawasan SP, hektare |
| `jumlah_kk_rencana` | `INT UNSIGNED` | YA | | Daya tampung rencana, pembanding realisasi |
| `lintang` | `DECIMAL(10,7)` | YA | | Titik pusat SP |
| `bujur` | `DECIMAL(10,7)` | YA | | Titik pusat SP |
| ~~`dokumen_pendukung`~~ | | | | **DIPINDAH 2026-09-02** ke registry `berkas` lewat pivot atau FK langsung (`rules.md` 14a.8) |
| `keterangan` | `TEXT` | YA | | Catatan bebas |

**Field Keadaan Wilayah** (Bab II Laporan Monografi; ditambahkan 2026-08-28, Rombongan C). Semuanya `NULL`-able dan dokumenter: dipakai laporan, tidak dihitung. Angka rentang disimpan sebagai pasangan min/maks.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `lintang_utara`, `lintang_selatan`, `bujur_barat`, `bujur_timur` | `DECIMAL(10,7)` | Kotak letak astronomis |
| `jarak_ke_kecamatan_km`, `jarak_ke_kabupaten_km`, `jarak_ke_provinsi_km` | `DECIMAL(6,1)` | Letak ekonomis |
| `batas_utara`, `batas_timur`, `batas_selatan`, `batas_barat` | `VARCHAR(150)` | **Dihidupkan kembali 2026-08-28**, lihat catatan |
| `nomor_sk_pencadangan` | `VARCHAR(100)` | SK Pencadangan Areal SP |
| `tanggal_sk_pencadangan` | `DATE` | |
| `pola_permukiman` | `ENUM` | Lihat §11.41 |
| `tingkat_kesuburan_tanah` | `ENUM` | Lihat §11.42 |
| `ph_tanah_min`, `ph_tanah_maks` | `DECIMAL(4,2)` | Kisaran pH |
| `bentuk_wilayah` | `ENUM` | Lihat §11.43 |
| `kemiringan_min_persen`, `kemiringan_maks_persen` | `DECIMAL(5,2)` | Kemiringan lereng |
| `curah_hujan_tahunan_mm` | `DECIMAL(8,2)` | Rata-rata tahunan |
| `curah_hujan_bulan_min_mm`, `curah_hujan_bulan_maks_mm` | `DECIMAL(7,2)` | Bulanan terendah dan tertinggi |
| `suhu_min_c`, `suhu_maks_c`, `suhu_rata_c` | `DECIMAL(4,1)` | Temperatur udara |
| `angin_min_knot`, `angin_maks_knot`, `angin_rata_knot` | `DECIMAL(4,1)` | Kecepatan angin |
| `penyinaran_min_persen`, `penyinaran_maks_persen`, `penyinaran_rata_persen` | `DECIMAL(5,2)` | Lama penyinaran matahari |
| `sumber_air_bersih`, `sumber_air_pertanian` | `VARCHAR(255)` | Teks bebas |

Sub-bagian 2.2 Aksesibilitas (rute perjalanan) disimpan pada tabel tersendiri `rute_aksesibilitas_sp` (§3.6a).

**Catatan:**
- **Batas wilayah dihidupkan kembali 2026-08-28 (Rombongan C).** Keempat kolom `batas_utara`/`batas_timur`/`batas_selatan`/`batas_barat` sempat **dicabut 2026-08-18** karena isinya sebutan naratif ("Berbatasan dengan Desa Naet"), bukan koordinat, dan tidak dipakai perhitungan, indikator, penilaian kondisi SP, maupun peta mana pun. Catatan pencabutan sendiri menuliskan jalan menghidupkannya: "tambahkan kembali 4 kolom pada kamus data 3.6, satu bagian pada sp/form, dan satu blok tampilan pada dashboard/sp". Bab II Laporan Monografi memuatnya, sehingga dinas kini memerlukannya. Alasan pencabutan dipertahankan sebagai riwayat pada `notes.md` bagian 6.
- **Kolom `kecamatan_id` sengaja tidak ada.** Kecamatan dibaca lewat rantai `desa_id → desa → kecamatan`. Menyimpannya secara terpisah membuka peluang data tidak sinkron bila desa berpindah kecamatan.
- SP menyimpan **dua** foreign key wilayah yang saling melengkapi: `kawasan_id` menjawab "bagian dari program mana", `desa_id` menjawab "berdiri di wilayah administratif mana". Keduanya wajib diisi.

### 3.6a `rute_aksesibilitas_sp`

Rute pencapaian ke satu SP (Bab II sub-bagian 2.2 Laporan Monografi, Tabel 2.1). Ditambahkan 2026-08-28 (Rombongan C). Satu SP punya beberapa baris; diisi lewat daftar dinamis pada form SP.

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_rute_aksesibilitas_sp` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `satuan_permukiman_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | `ON DELETE CASCADE` |
| `rute` | `VARCHAR(255)` | TIDAK | | Contoh: "Kupang ke UPT", "UPT ke Kabupaten" |
| `jarak_km` | `DECIMAL(7,1)` | YA | | |
| `sarana_angkutan` | `VARCHAR(150)` | YA | | Contoh: "Roda dua", "Angkutan darat", "Pesawat" |
| `tempat_pemberangkatan` | `VARCHAR(150)` | YA | | |
| `kondisi_jalan` | `VARCHAR(150)` | YA | | Contoh: "Baik, aspal", "Pengerasan" |
| `waktu_tempuh` | `VARCHAR(80)` | YA | | Teks bebas: "6 jam", "45 menit" |
| `ongkos_rp` | `DECIMAL(12,2)` | YA | | Rupiah |
| `keterangan` | `VARCHAR(255)` | YA | | |

**Catatan:**
- `waktu_tempuh` sengaja teks: berkas monografi menulisnya beragam ("6 jam", "2 JAM", "2,5"), dan menyeragamkannya menjadi menit menuntut petugas mengurai sendiri.
- Tidak ada tabel riwayat: rute yang berubah cukup disunting atau dihapus.

---

## 4. Domain Aset SP

### 4.1 `inventaris_sp`

Barang bergerak milik SP (`rules.md` §4b).

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_inventaris_sp` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `satuan_permukiman_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | |
| `jenis_inventaris` | `VARCHAR(100)` | TIDAK | IDX | Master daftar pilihan (Revisi 2026-08-30). Opsi baku: Peralatan Kantor, Elektronik & Mesin, Perabotan, Kendaraan Operasional, Peralatan Lainnya |
| `nama_barang` | `VARCHAR(255)` | TIDAK | | |
| `jumlah` | `INT UNSIGNED` | TIDAK | | Bawaan 1 |
| `satuan_barang` | `VARCHAR(50)` | YA | | Teks bebas: unit, buah, set |
| `tahun_perolehan` | `YEAR` | YA | | |
| `sumber_dana` | `ENUM` | YA | | Lihat §11.3 |
| `status_penyerahan` | `ENUM` | TIDAK | | Lihat §11.4 |
| `kondisi` | `ENUM` | YA | | Lihat §11.5. **Penilaian umum petugas** (lencana, cacah "perlu perbaikan"); tidak diturunkan dari `rincian_kondisi` |
| `rincian_kondisi` | `JSON` | YA | | Peta kondisi → jumlah unit (Putaran 7). Σ = `jumlah`. "Sebagian retak" jadi angka. Tetap per jenis, bukan per unit |
| ~~`foto`~~ | | | | **DIPINDAH 2026-09-02** ke registry `berkas` lewat pivot atau FK langsung (`rules.md` 14a.8) |
| ~~`dokumen_pendukung`~~ | | | | **DIPINDAH 2026-09-02** ke registry `berkas` lewat pivot atau FK langsung (`rules.md` 14a.8) |
| `keterangan` | `TEXT` | YA | | |

**Catatan:**
- `satuan_barang` sengaja berupa teks bebas dan **tidak** menaut ke tabel `satuan`, karena tabel `satuan` khusus menyimpan satuan berat beserta faktor konversi ke ton.
- **`rincian_kondisi` (Putaran 7, 2026-08-30).** Sebelumnya satu baris hanya punya satu `kondisi` meski `jumlah` > 1, sehingga "dua dari tiga pos lapuk" lolos ke `keterangan` sebagai kalimat. `rincian_kondisi` mencatat histogram; `PenilaianKondisiSp::kondisiTerbaik()` membacanya (satu unit terbaik yang jumlahnya > 0 sudah cukup untuk "SP punya X yang berfungsi"). BUKAN pendataan per unit — kursi ke-3 tak dapat dibedakan dari kursi ke-7. Sama untuk `fasilitas_sp`.
- **Kolom `foto` ditambahkan 2026-08-20**, mengikuti pola `infrastruktur` §10.1. Keduanya menjawab hal berbeda: foto merekam kondisi barang saat pendataan, dokumen menyimpan berkas administratifnya. Satu slot untuk keduanya membuat foto kondisi tertimpa berita acara, atau sebaliknya, dan kehilangannya berlangsung diam-diam sebab form tetap tersimpan.

### 4.2 `fasilitas_sp`

Bangunan dan fasilitas tetap milik SP. Struktur sama persis dengan `inventaris_sp`, dibedakan agar rekap aset dapat dipisah (`rules.md` §4b.1).

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_fasilitas_sp` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `satuan_permukiman_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | |
| `jenis_fasilitas` | `ENUM` | TIDAK | IDX | Lihat 11.32; dipakai penilaian kondisi SP |
| `nama_fasilitas` | `VARCHAR(255)` | TIDAK | | Nama sebagaimana disebut warga, contoh "Puskesmas Pembantu Kapitan Meo" |
| `jumlah` | `INT UNSIGNED` | TIDAK | | Bawaan 1 |
| `tahun_perolehan` | `YEAR` | YA | | |
| `sumber_dana` | `ENUM` | YA | | Lihat §11.3 |
| `status_penyerahan` | `ENUM` | TIDAK | | Lihat §11.4 |
| `kondisi` | `ENUM` | YA | | Lihat §11.5. Penilaian umum petugas |
| `rincian_kondisi` | `JSON` | YA | | Peta kondisi → jumlah unit (Putaran 7). Σ = `jumlah`. Lihat catatan §4.1 |
| `lintang` | `DECIMAL(10,7)` | YA | | Lokasi fasilitas |
| `bujur` | `DECIMAL(10,7)` | YA | | Lokasi fasilitas |
| ~~`foto`~~ | | | | **DIPINDAH 2026-09-02** ke registry `berkas` lewat pivot atau FK langsung (`rules.md` 14a.8) |
| ~~`dokumen_pendukung`~~ | | | | **DIPINDAH 2026-09-02** ke registry `berkas` lewat pivot atau FK langsung (`rules.md` 14a.8) |
| `keterangan` | `TEXT` | YA | | |

**Catatan:**
- **`satuan_permukiman_id` = lokasi/pangkal.** Tabel `fasilitas_sp_cakupan` `(fasilitas_sp_id, satuan_permukiman_id)` menyimpan SP yang dilayani, **wajib memuat SP pangkal** (Putaran 7). SMP Satu Atap, puskesmas pembantu, atau pasar desa di satu SP kerap melayani warga SP tetangga; `PenilaianKondisiSp` membacanya sama seperti `infrastruktur_sp` (§10.1). `rincian_kondisi`: lihat §4.1.
- **Kolom `foto` ditambahkan 2026-08-20**, alasan sama dengan §4.1. Sebelumnya satu slot dipakai untuk keduanya, dan labelnya bahkan berbunyi "Dokumen atau Foto Fasilitas" — menjanjikan dua hal untuk satu tempat penyimpanan.
- `jenis_fasilitas` dan `nama_fasilitas` sengaja berdampingan. Enum diperlukan agar penilaian kondisi SP dapat menghitung otomatis, sebab teks bebas membuat "SEKOLAH DASAR" dan "SD Negeri 1" tidak terbaca sebagai hal yang sama. Nama bebas tetap dipertahankan agar petugas dapat menulis sebutan yang dikenal warga setempat.

---


## 4b. Registry Berkas

Satu tempat bagi metadata seluruh berkas sistem, ditambahkan 2026-09-02 (Putaran 12)
menggantikan 24 kolom path yang tersebar di 17 tabel. Aturannya pada `rules.md` 14a
poin 8 sampai 10.

### 4b.1 `berkas`

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_berkas` | `BIGINT UNSIGNED` | TIDAK | PK | Integer, bukan UUID: lebih ringan sebagai indeks dan direferensikan 17 FK (`rules.md` 4.0a.1) |
| `uuid` | `CHAR(36)` | TIDAK | UNIQUE | Pengenal publik |
| `jenis_berkas_id` | `BIGINT UNSIGNED` | YA | FK `daftar_pilihan` | Penggolongan berkas, mis. HPL/SHM; NULL berarti tanpa penggolongan |
| `nama_file` | `VARCHAR(255)` | TIDAK | | Nama tersimpan di disk |
| `nama_asli` | `VARCHAR(255)` | YA | | Nama dari pengunggah, dipakai saat berkas diunduh |
| `path` | `VARCHAR(255)` | TIDAK | | Relatif terhadap disk, bukan URL absolut |
| `mime` | `VARCHAR(127)` | TIDAK | | Hasil pemeriksaan isi berkas, BUKAN klaim klien (14a.2) |
| `ekstensi` | `VARCHAR(10)` | TIDAK | | |
| `ukuran` | `BIGINT UNSIGNED` | TIDAK | | Byte; batas 5 MB pada 14a.1 baru dapat diperiksa ulang lewat kolom ini |
| `disk` | `VARCHAR(20)` | TIDAK | | `local` / `s3` / `gcs`; menyiapkan object storage (2.2.6) |
| `keterangan` | `VARCHAR(500)` | YA | | Mis. tampak samping; menggantikan kolom foto per sisi |
| `user_id` | `BIGINT UNSIGNED` | YA | FK `user` | NULL = unggahan kanal publik tanpa akun (10b.1) |

### 4b.2 Pivot pemilik berkas

Dua belas pivot berpola sama: `<induk>_id`, `berkas_id`, `peran`, `urutan`,
UNIQUE gabungan keduanya, CASCADE ke kedua sisi.

| Pivot | Peran yang dipakai |
|---|---|
| `transmigran_berkas` | `shm`, `ktp`, `kk`, `sk` |
| `kawasan_transmigrasi_berkas` | `hpl`, `sk`, `peta` |
| `rumah_berkas` | `foto`, `pendukung` |
| `inventaris_sp_berkas` | `foto`, `pendukung` |
| `fasilitas_sp_berkas` | `foto`, `pendukung` |
| `infrastruktur_berkas` | `foto`, `pendukung` |
| `alsintan_berkas` | `foto`, `pendukung` |
| `penanaman_berkas` | `pendukung` |
| `hasil_panen_berkas` | `pendukung` |
| `pengaduan_berkas` | `bukti` |
| `penanganan_pengaduan_berkas` | `tindak_lanjut` |
| `user_berkas` | `foto`; UNIQUE hanya pada `user_id` sebab foto profil selalu satu |

### 4b.3 Foreign key langsung

Domain yang berkasnya memang selalu satu memakai FK langsung tanpa pivot,
dengan `ON DELETE SET NULL` sebab menghapus berkas tidak boleh menghapus barisnya:
`satuan_permukiman.berkas_id`, `poktan.berkas_id`, `saprotan.foto_berkas_id`,
`saprotan.berkas_id`, dan `alsintan_distribusi.foto_berkas_id`.

## 5. Domain Master Daftar Pilihan

### 5.1 `satuan`

Satuan jumlah beserta faktor konversi ke ton (`rules.md` §8a).

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_satuan` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `nama` | `VARCHAR(50)` | TIDAK | UQ | Ton, Kuintal, Kilogram, Liter, Rol |
| `simbol` | `VARCHAR(10)` | TIDAK | | t, kw, kg, L, rol |
| `faktor_ke_ton` | `DECIMAL(10,6)` | YA | | Ton = 1; Kuintal = 0,1; Kilogram = 0,001. **NULL** untuk satuan non-berat (Liter, Rol) yang tidak dikonversi ke ton dan tidak pernah masuk rekap panen. |

**Catatan:** satuan berat lokal seperti karung dan ikat **tidak** dimasukkan karena beratnya tidak baku. Satuan **non-berat** (Liter untuk saprotan cair, Rol untuk mulsa) ditambahkan Task 6.7 dengan `faktor_ke_ton` NULL: dipakai saprotan tetapi tidak pernah dijumlahkan sebagai tonase. Kolom `hasil_panen.keterangan_satuan_lokal` yang dahulu menampung satuan setempat **dicabut 2026-08-22**; padanannya kini ditulis pada kolom `keterangan` biasa bila perlu.

### 5.2 `komoditas`

Satu baris per komoditas, menggantikan pola kolom berulang `_1`, `_2`, `_3` pada SQL referensi.

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_komoditas` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `satuan_id` | `BIGINT UNSIGNED` | TIDAK | FK | Satuan panen baku komoditas ini |
| `nama` | `VARCHAR(100)` | TIDAK | UQ | Jagung, Padi, Cabai |
| `tipe` | `ENUM` | TIDAK | IDX | Lihat §11.6 |
| `is_unggulan` | `BOOLEAN` | TIDAK | IDX | Bawaan `FALSE`; jagung bernilai `TRUE` |
| `deskripsi` | `TEXT` | YA | | |

**Catatan:** `satuan_id` menetapkan satuan baku, misalnya jagung dalam ton dan cabai dalam kilogram (`rules.md` §8.4).

### 5.3 `musim_tanam` — DICABUT 2026-08-22

Tabel ini **dihapus** beserta seluruh rute, halaman, menu, dan izinnya.

Alasannya keadaan lapangan, bukan penyederhanaan teknis: poktan menanam secara **fleksibel**, tidak mengikuti periode baku MT1/MT2 yang ditetapkan dari meja. Memaksa setiap penanaman memilih salah satu musim membuat petugas menebak, lalu tebakan itu dipakai sebagai dasar rekap seolah-olah data terukur.

Sumbu waktunya kini `penanaman.periode_tanam` dan `hasil_panen.periode_panen`. Keduanya sudah ada, memang dicatat petugas, dan tidak memerlukan tabel tersendiri. Rekap per periode yang diwajibkan `rules.md` §8b.8 tetap terpenuhi lewat penyaringan **Tahun Tanam** dan **Tahun Panen** yang dihitung dari kedua kolom itu.

---

### 5.4 `parameter_penilaian_sp`

Parameter penilaian kondisi SP beserta bobotnya (`rules.md` 10c).

Bobot disimpan sebagai **data**, bukan ditulis di dalam kode, agar Admin dapat menyesuaikannya lewat antarmuka tanpa mengubah struktur database. Pola ini mengikuti keputusan yang sama pada `role` (2.1a) dan `satuan` (5.1).

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_parameter_penilaian_sp` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `kode` | `VARCHAR(50)` | TIDAK | UQ | Penunjuk parameter, contoh `air_bersih`, `jalan_penghubung` |
| `nama` | `VARCHAR(100)` | TIDAK | | Teks yang tampil, contoh "Air Bersih" |
| `tingkat` | `ENUM` | TIDAK | IDX | Lihat 11.29 |
| `bobot` | `TINYINT UNSIGNED` | TIDAK | | Bawaan mengikuti tingkat: Primer 5, Sekunder 3, Tersier 1. Disunting dinas lewat `/master/penilaian-kondisi` |
| `sumber` | `ENUM` | TIDAK | | Lihat 11.31; menentukan tabel mana yang dibaca |
| `daftar_pilihan_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | Baris `daftar_pilihan` yang dicari pada tabel sumber, contoh jenis infrastruktur `Air` |
| `is_dinilai` | `BOOLEAN` | TIDAK | | Parameter yang tidak dicentang tetap tercatat tetapi tidak menambah pembagi skor |
| `urutan` | `SMALLINT UNSIGNED` | TIDAK | | Urutan tampil pada halaman pengaturan dan rincian skor |

**Catatan:**
- Parameter **dinonaktifkan, bukan dihapus**, agar riwayat penilaian yang memakainya tetap dapat dibaca.
- `sumber` dan `daftar_pilihan_id` menjelaskan dari mana nilai kondisi diambil: parameter `air_bersih` membaca `infrastruktur` berjenis `Air`, sedangkan `kesehatan` membaca `fasilitas_sp` berjenis `Kesehatan`.
- **Barisnya DIHASILKAN dari jenis infrastruktur dan fasilitas**, tidak ditulis satu per satu. Sebelumnya daftar parameter berupa tiga belas baris tulis tangan di dalam kode, sehingga jenis yang ditambahkan Admin tidak pernah ikut dinilai: dropdownnya hidup dan petugas dapat mendata asetnya, tetapi skor SP tidak berubah sama sekali. POS KAMLING di SP Weain berkondisi Rusak Berat terdata rapi dan tidak menyumbang apa pun, semata karena daftar itu berhenti di baris ke tiga belas.
- **Jenis baru lahir dalam keadaan `is_dinilai` bernilai salah.** Menambah jenis adalah tindakan pendataan, sedangkan memasukkannya ke penilaian adalah keputusan kebijakan. Menyatukan keduanya membuat skor seluruh SP turun hanya karena Admin menambah satu pilihan dropdown, sebab pembaginya ikut bertambah.
- **`sumber` disimpulkan dari jenisnya**, tidak diisi manual: jenis infrastruktur selalu dibaca dari tabel `infrastruktur` kolom `jenis`, jenis fasilitas dari `fasilitas_sp` kolom `jenis_fasilitas`. Menyimpannya sebagai isian terpisah membuka peluang parameter menunjuk tabel yang tidak memuat jenisnya.
- **`tingkat` tiga parameter primer terkunci** (`air_bersih`, `jalan_penghubung`, `listrik`). Memindahkannya ke tingkat lain bukan menurunkan bobot, melainkan mencabut aturan primer nol, sehingga SP tanpa listrik berhenti otomatis berstatus Perlu Penanganan.
- **`kode` ditulis tetap, tidak diturunkan dari nama jenisnya.** Ia penunjuk yang tersalin ke `penilaian_sp.rincian`, sehingga menurunkannya dari teks membuat penilaian lama kehilangan pasangannya begitu Admin memperbaiki ejaan.
- `daftar_pilihan_id` **menggantikan `jenis_rujukan` yang dulu berupa teks**, sejak jenis infrastruktur dan fasilitas menjadi data master. Rujukan berbasis teks putus tanpa pesan apa pun begitu Admin memperbaiki ejaannya, dan parameter itu lalu diam-diam menilai setiap SP sebagai tidak punya aset tersebut. Bila idnya tidak ditemukan, parameter **dilewati**, bukan dinilai nol: menilainya nol berarti seluruh SP jatuh statusnya hanya karena satu baris referensi hilang.

### 5.5 `penilaian_sp`

Riwayat penilaian kondisi SP. Satu SP memiliki banyak baris, satu untuk setiap kali dinilai (`rules.md` 10c.6).

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_penilaian_sp` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `satuan_permukiman_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | |
| `tanggal_penilaian` | `DATE` | TIDAK | IDX | |
| `skor` | `DECIMAL(5,2)` | TIDAK | | Nilai 0 sampai 100 |
| `status` | `ENUM` | TIDAK | IDX | Lihat 11.30 |
| `ada_primer_nol` | `BOOLEAN` | TIDAK | | `TRUE` bila satu saja parameter primer bernilai Tidak Ada |
| `rincian` | `JSON` | TIDAK | | Nilai tiap parameter beserta bobot yang dipakai saat itu |
| `user_id` | `BIGINT UNSIGNED` | YA | FK | Petugas yang menjalankan penilaian; `NULL` bila dihitung sistem |
| `catatan` | `TEXT` | YA | | |

**Catatan penting:**
- Kolom `rincian` menyimpan **salinan bobot yang berlaku saat penilaian dibuat**. Tanpa salinan ini, laporan yang sudah dicetak akan berbeda dari tampilan sistem setelah Admin mengubah bobot. Prinsipnya sama dengan penyalinan `satuan_id` pada `hasil_panen` (9.3).
- Penilaian **tidak pernah dihitung ulang secara diam-diam**. Penilaian baru menambah baris baru; yang lama tetap utuh sebagai jejak.
- `ada_primer_nol` disimpan terpisah meski dapat disimpulkan dari `rincian`, agar penyaringan SP bermasalah tidak perlu membongkar JSON.

**Contoh isi `rincian`:**

```json
[
  {"kode": "air_bersih", "nama": "Air Bersih", "tingkat": "Primer", "bobot": 5, "kondisi": "Baik", "nilai": 1.0},
  {"kode": "jalan_penghubung", "nama": "Jalan Penghubung", "tingkat": "Primer", "bobot": 5, "kondisi": "Tidak Ada", "nilai": 0}
]
```

### 5.6 `daftar_pilihan`

Daftar pilihan yang **dikelola Admin lewat antarmuka**, bukan ditulis sebagai enum di dalam kode (`rules.md` §4 poin 4 dan §13.0; kriteria enum-vs-master pada bulir terakhir §5.6 ini).

Empat belas daftar disatukan pada satu tabel karena strukturnya identik. Empat belas tabel terpisah berarti empat belas migration, empat belas model, dan empat belas halaman CRUD untuk perbedaan yang hanya terletak pada nama jenisnya.

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_daftar_pilihan` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `jenis` | `ENUM` | TIDAK | IDX, UQ¹ | Lihat 11.37; menentukan daftar mana nilai ini termasuk |
| `nilai` | `VARCHAR(100)` | TIDAK | UQ¹ | Teks yang tampil sekaligus tersimpan pada kolom pemakainya |
| `urutan` | `SMALLINT UNSIGNED` | TIDAK | | Urutan tampil; bermakna pada jenis berjenjang |
| `nilai_skor` | `DECIMAL(3,2)` | YA | | Bobot nilai pada penilaian kondisi SP; hanya jenis berskor |
| `bidang_id` | `BIGINT UNSIGNED` | YA | FK, IDX | Bidang penanganan bawaan; hanya jenis `kategori_pengaduan`. NULL bermakna: bidang ditetapkan petugas per laporan |
| `is_aktif` | `BOOLEAN` | TIDAK | IDX | Nilai nonaktif tidak ditawarkan pada data baru |

¹ UNIQUE gabungan `(jenis, nilai)`.

**Catatan:**

- **Nilai DINONAKTIFKAN, tidak pernah dihapus.** Menghapus `Hibah` dari sumber dana membuat puluhan baris infrastruktur lama menunjuk baris yang lenyap, dan rekap kehilangan baris itu **tanpa pesan apa pun**. Nilai nonaktif tetap terbaca pada data lama, hanya tidak lagi ditawarkan pada data baru. Pola ini mengikuti `parameter_penilaian_sp` (5.4) yang sudah memakainya lebih dulu dengan alasan sama.
- **Yang tersimpan pada kolom pemakainya adalah TEKS `nilai`, bukan id.** Sengaja demikian: kolom-kolom itu bertipe `ENUM` atau `VARCHAR` pada SQL referensi dan sudah dipakai puluhan tampilan tanpa join. Pengecualiannya hanya `parameter_penilaian_sp.jenis_rujukan` yang menunjuk id (Fase 4), sebab di sanalah penggantian teks berakibat fatal: parameter berhenti menemukan asetnya lalu menilai SP sebagai `Tidak Ada`.
- **`nilai_skor` hanya untuk jenis `kondisi`**, bukan `kondisi_rumah`. Keduanya tampak sebagai skala kerusakan yang sama, tetapi hanya `kondisi` yang dibaca `PenilaianKondisiSp`; kondisi rumah murni tampilan dan tidak pernah masuk perhitungan mana pun. Memberi `nilai_skor` kepadanya berarti menyediakan isian yang tidak menentukan apa pun, dan Admin yang menyuntingnya akan menyangka skor SP ikut berubah. Mengubahnya mengubah cara penilaian BERIKUTNYA dihitung, tetapi tidak mengubah penilaian yang sudah tersimpan: `penilaian_sp.rincian` menyalin nilai yang berlaku saat penilaian dibuat (5.5). Tanpa salinan itu, laporan yang sudah dicetak akan berbeda dari tampilan sistem setiap kali Admin menyunting skor.
- **Dikelola lewat satu halaman per daftar**, bukan satu halaman bertab. Semula keempat belasnya berupa tab dalam satu baris, dan itu berhenti bekerja begitu jumlahnya bertambah: bar tab mencapai 2309px pada ruang 705px, sehingga hanya empat tab yang terlihat dan sepuluh sisanya tersembunyi di balik gulir mendatar. Indeks di `/master/referensi` menampilkan seluruh daftar sebagai kartu berkelompok, dan tiap daftar dibuka di `/master/referensi/{jenis}` (`ui-spec.md` 5.1d).
- **`jenis_infrastruktur` dan `jenis_fasilitas` DIRUJUK LEWAT ID**, satu-satunya pengecualian dari aturan teks di atas. `parameter_penilaian_sp.referensi_id` menunjuk baris pada tabel ini, misalnya parameter `air_bersih` menunjuk jenis infrastruktur `Air`. Alasannya justru dampaknya: daftar lain hanya menampilkan teksnya kembali, sedangkan dua daftar ini menentukan hasil perhitungan. Rujukan berbasis teks putus tanpa pesan apa pun begitu Admin memperbaiki ejaan `Air` menjadi `Air Bersih`, dan parameter itu lalu diam-diam menilai setiap SP sebagai tidak punya air, sehingga status SP jatuh karena satu penyuntingan ejaan.
- **`bidang_id` hanya untuk `kategori_pengaduan`**, dan NULL di sana bermakna. Ia menyatakan kategori yang dapat jatuh ke dua dinas sekaligus, sehingga bidangnya wajib ditetapkan petugas sebelum status maju ke Diproses (`rules.md` 10b poin 7b). Nilai yang terisi hanya menetapkan bidang AWAL; petugas selalu dapat menimpanya.
- **`urutan` bermakna pada `prioritas_pengaduan`**, sebab daftar pengaduan menyortir memakainya. Menukar urutan berarti menukar antrean petugas, bukan sekadar menukar tampilan.
- **Jenisnya tetap enum**, tidak ikut menjadi data. `jenis` menyatakan daftar mana yang ada, bukan isinya; menjadikannya data membuat Admin dapat membuat jenis yang tidak satu pun kolom database menunjuknya.
- Enum yang **tetap di dalam kode** dan tidak menjadi daftar pilihan: seluruh enum yang membawa perilaku (`StatusPengaduan` dengan state machine-nya, `StatusKondisiSp` dengan `dariSkor()`, `AsalWakilPoktan`, `CakupanData`, `AksiPermission`, `AksiAuditLog`), serta enum yang nilainya terikat ketentuan luar (`JenisKelamin`, `PendidikanTerakhir`). `StatusSertifikat` (`Sudah`/`Belum`/`Belum Didata`) juga di kode, sepola `StatusTinggal`.

### 5.7 `status_kondisi_sp`

Ambang skor dan teks tampil (wording) predikat kondisi SP, disunting dinas lewat `/master/penilaian-kondisi`. **Ditambahkan 2026-09-01** bersama penyusunan `database/data/schema.sql`; struktur diturunkan dari frontend (`DummyData::statusKondisiSp()`), yang lebih dulu ada daripada tabelnya.

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_status_kondisi_sp` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `kode` | `VARCHAR(30)` | TIDAK | UQ | `Mandiri` / `Berkembang` / `Perlu Penanganan`; kunci enum perilaku `StatusKondisiSp::dariSkor()` — tidak disunting |
| `nama` | `VARCHAR(50)` | TIDAK | | Teks tampil; dinas boleh memakai istilah sendiri (mis. "Prioritas Pembinaan") |
| `keterangan` | `VARCHAR(255)` | YA | | |
| `ambang_bawah` | `DECIMAL(5,2)` | TIDAK | | Ambang wajib menurun; ambang status terendah terkunci pada 0 |
| `warna` | `VARCHAR(20)` | TIDAK | | `success` / `warning` / `error` — tidak disunting (menyatakan urutan keparahan) |
| `urutan` | `SMALLINT UNSIGNED` | TIDAK | | |

**Catatan:**
- **Jumlahnya tetap tiga** dan tidak dapat ditambah/dihapus: `dariSkor()` hanya mengembalikan tiga keluaran dan `penilaian_sp.status` bertipe `ENUM`. Yang berubah lewat CMS hanya `nama`, `keterangan`, dan `ambang_bawah`.
- Nilai kondisi berskor (Baik 1,0 / Rusak Ringan 0,5 / Rusak Berat 0,2 / Hilang 0) tetap pada `daftar_pilihan` (jenis `kondisi`, kolom `nilai_skor`), bukan di sini. Bobot parameter tetap pada `parameter_penilaian_sp.bobot`.

---

## 6. Domain Kependudukan

### 6.1 `transmigran`

Data inti sistem, satu baris per **kepala keluarga**.

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_transmigran` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `satuan_permukiman_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | SP tempat tinggal |
| `nik` | `CHAR(16)` | TIDAK | UQ, IDX | Tepat 16 digit angka |
| `no_kk` | `CHAR(16)` | TIDAK | UQ | Tepat 16 digit angka |
| `nama_kepala_keluarga` | `VARCHAR(255)` | TIDAK | IDX | |
| `jenis_kelamin` | `ENUM` | YA | | Laki-laki, Perempuan |
| `agama` | `ENUM` | YA | | Lihat §11.38. Ditambahkan 2026-08-28 (Rombongan B) |
| `tempat_lahir` | `VARCHAR(100)` | YA | | |
| `tanggal_lahir` | `DATE` | YA | | Sumber usia (dihitung, tidak disimpan) |
| `pendidikan_terakhir` | `ENUM` | YA | | Lihat §11.7 |
| `pekerjaan_kepala_keluarga` | `VARCHAR(100)` | TIDAK | IDX | Sumber histogram dashboard |
| ~~`jumlah_anggota_keluarga`~~ | ~~`TINYINT UNSIGNED`~~ | | | **Diturunkan sejak 2026-08-28, tidak lagi kolom.** 1 (kepala) + `COUNT(anggota_keluarga)`. Lihat catatan |
| `pendapatan_per_bulan` | `DECIMAL(15,2)` | YA | | Rupiah; pendapatan kepala keluarga |
| `daerah_asal_kabupaten_id` | `BIGINT UNSIGNED` | YA | FK `kabupaten` | Kabupaten/kota asal. **Diubah 2026-09-02** dari `VARCHAR(255)` teks bebas: ia salah satu dari enam dasar rekap kependudukan (`rules.md` 10a.4a), dan teks bebas memecah satu kabupaten menjadi beberapa baris rekap karena beda ejaan tanpa memerahkan apa pun. Nama kabupaten tidak unik nasional, sehingga isian menampilkan nama provinsi sebagai pembeda |
| `tahun_kedatangan` | `YEAR` | TIDAK | IDX | Dasar grafik jumlah transmigran per tahun |
| `status_tinggal` | `ENUM` | TIDAK | IDX | Lihat §11.8 |
| `status_anggota_poktan` | `ENUM` | TIDAK | | Ya, Tidak |
| `status_sertifikat` | `ENUM` | TIDAK | | Status sertifikat lahan keluarga: `Sudah`, `Belum`, `Belum Didata` (enum `StatusSertifikat`). **Ditambahkan 2026-09-02; isian di form Data Lahan sejak 2026-09-03** (bukan form transmigran - `rules.md` 7.6a). SHM meliputi SELURUH lahan satu KK sehingga statusnya melekat di sini, bukan pada tiap bidang. `Belum Didata` memisahkan keluarga yang dipastikan belum bersertifikat dari yang belum pernah ditanyakan; tanpa itu laporan ke dinas mencampur keduanya (`rules.md` 7.6c) |
| `telepon` | `VARCHAR(20)` | YA | | |
| ~~`dokumen_pendukung`~~ | | | | **DIPINDAH 2026-09-02** ke registry `berkas` lewat pivot atau FK langsung (`rules.md` 14a.8) |
| `keterangan` | `TEXT` | YA | | |

**Catatan:**
- `pekerjaan_kepala_keluarga` sengaja berupa `VARCHAR` bukan `ENUM`, karena ragam pekerjaan di lapangan sulit dibatasi di muka. Konsistensi dijaga lewat daftar saran pada antarmuka.
- `tahun_kedatangan` wajib diisi karena menjadi sumbu grafik dashboard PRD §7.8.
- `status_anggota_poktan` disimpan sebagai penanda cepat; kebenarannya tetap mengacu ke `anggota_poktan`.
- **`jumlah_anggota_keluarga` dicabut sebagai kolom 2026-08-28 (Rombongan B).** Dahulu disimpan justru karena sistem tidak mendata anggota keluarga satu per satu (`erd.md` §7.4). Setelah tabel `anggota_keluarga` ada, menyimpannya membuat nilainya dapat berselisih dengan daftar anggota yang sebenarnya, kekeliruan yang sama dengan `poktan.jumlah_anggota`. Kini dihitung: 1 (kepala keluarga) + `COUNT(anggota_keluarga WHERE transmigran_id = ...)`. Seluruh pembaca lama tetap membacanya lewat nama yang sama, sebab `DummyData::transmigran()` menyisipkannya kembali sebagai turunan.
- **`usia` tidak pernah menjadi kolom.** Dihitung dari `tanggal_lahir` dan bertambah sendiri tiap tahun. Menyimpannya berarti nilai yang basi tepat satu tahun setelah dicatat.
- **`agama`** ditambahkan bersama pendataan anggota keluarga; berlaku pula bagi tiap baris `anggota_keluarga`.

### 6.1a `anggota_keluarga`

Satu baris per anggota keluarga transmigran **selain kepala keluarga**. Ditambahkan 2026-08-28 (Rombongan B). Membalik keputusan `erd.md` §7.4 ("sistem tidak mendata anggota keluarga satu per satu, di luar lingkup PRD") atas permintaan pemilik proyek, agar suksesi kepala keluarga dan pemilihan wakil poktan tidak lagi mengetik identitas dari nol.

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_anggota_keluarga` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `transmigran_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | Keluarga yang dinaungi; `ON DELETE CASCADE` |
| `hubungan` | `ENUM` | TIDAK | | Lihat §11.39 |
| `nama_lengkap` | `VARCHAR(255)` | TIDAK | IDX | Huruf kapital otomatis |
| `nik` | `CHAR(16)` | YA | IDX | Tepat 16 digit bila diisi; `NULL` bagi balita yang belum memilikinya |
| `jenis_kelamin` | `ENUM` | YA | | Laki-laki, Perempuan |
| `tempat_lahir` | `VARCHAR(100)` | YA | | |
| `tanggal_lahir` | `DATE` | YA | | Sumber usia (dihitung, tidak disimpan) |
| `agama` | `ENUM` | YA | | Lihat §11.38 |
| `kegiatan` | `ENUM` | YA | | Lihat §11.40; menentukan isian yang menyusul |
| `pendidikan_terakhir` | `ENUM` | YA | | Lihat §11.7. Wajib bila `kegiatan` bukan `Belum Sekolah`; bagi `Masih Sekolah` berarti jenjang yang sedang ditempuh |
| `pekerjaan` | `VARCHAR(100)` | YA | | Wajib bila `kegiatan` = `Bekerja` |
| `pendapatan_per_bulan` | `DECIMAL(15,2)` | YA | | Hanya bila `kegiatan` = `Bekerja` |
| `telepon` | `VARCHAR(20)` | YA | | |
| `keterangan` | `VARCHAR(1000)` | YA | | |
| `status` | `ENUM` | TIDAK | IDX | Lihat §11.44. Bawaan `Aktif`. Ditambahkan 2026-08-29 (Putaran 6) |
| `tanggal_peristiwa` | `DATE` | YA | | Tanggal meninggal atau pindah; `NULL` bila `status` = `Aktif` |
| `keterangan_peristiwa` | `VARCHAR(500)` | YA | | Catatan peristiwa; `NULL` bila `status` = `Aktif` |

**Catatan:**
- **Mutasi anggota keluarga ditandai, tidak dihapus** (Putaran 6, 2026-08-29; membalik sebagian ketentuan lama). Anggota yang meninggal atau pindah **tetap disimpan** dengan `status` + `tanggal_peristiwa` + `keterangan_peristiwa`, dan dikecualikan dari cacah jiwa keluarga, pilihan pengganti KK, serta rekap agama. Alasannya: Laporan Monografi SP membutuhkan angka mutasi penduduk. Dicatat lewat tombol "Catat Peristiwa" per baris (rute `transmigran.anggota.catat-peristiwa`). Bukan riwayat lengkap, hanya penanda peristiwa terakhir. ~~Ketentuan lama: "Anggota yang meninggal atau pindah cukup dihapus dari sini."~~
- **Kepala keluarga tidak memakai `status` ini** — peristiwanya selalu lewat suksesi (`riwayat_kepala_keluarga` §6.4, `AlasanPergantianKK` §11.36). Kelahiran tidak ditandai; Laporan Monografi menghitungnya dari `tanggal_lahir` vs `tahun_penempatan` SP.
- **Pasangan dipisah `Istri`/`Suami` pada `hubungan`** agar jenis kelamin tersirat dari hubungannya, dan agar suksesi dapat menawarkan "pasangan" sebagai calon pengganti pertama.
- **Kepala keluarga sendiri tidak punya baris di sini.** Datanya ada di `transmigran`. Ketika terjadi suksesi, baris `anggota_keluarga` sang pengganti dihapus dan datanya "naik" menimpa baris `transmigran` (`rules.md` §6.5).

### 6.2 `rumah`

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_rumah` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `satuan_permukiman_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | |
| `transmigran_id` | `BIGINT UNSIGNED` | YA | FK, **UQ** | Penghuni; `NULL` berarti rumah kosong |
| `no_rumah` | `VARCHAR(50)` | YA | | Nomor atau blok rumah |
| `kondisi` | `ENUM` | TIDAK | IDX | Lihat §11.9 |
| `status_hunian` | `ENUM` | TIDAK | IDX | Lihat §11.10 |
| `alasan_tidak_dihuni` | `TEXT` | YA | | Wajib diisi bila status Tidak Dihuni |
| `tahun_pembangunan` | `YEAR` | YA | | |
| `luas_bangunan` | `DECIMAL(8,2)` | YA | | Meter persegi |
| `lintang` | `DECIMAL(10,7)` | YA | | |
| `bujur` | `DECIMAL(10,7)` | YA | | |
| ~~`foto_rumah`~~ | | | | **DIPINDAH 2026-09-02** ke registry `berkas` lewat pivot atau FK langsung (`rules.md` 14a.8) |
| `catatan_hunian` | `TEXT` | YA | | Termasuk catatan rumah ditinggal sementara |
| ~~`dokumen_pendukung`~~ | | | | **DIPINDAH 2026-09-02** ke registry `berkas` lewat pivot atau FK langsung (`rules.md` 14a.8) |

**Catatan penting:** `transmigran_id` memiliki constraint `UNIQUE` yang **wajib dibuat di level database**, bukan sekadar validasi form (`rules.md` §6a.6). Constraint ini sekaligus menjamin dua aturan: satu rumah hanya satu KK, dan satu KK hanya satu rumah. Saat menautkan KK, antarmuka hanya menampilkan rumah dengan `transmigran_id` bernilai `NULL`.

### 6.3 `riwayat_penghunian`

Jejak pergantian penghuni. Tidak pernah ditimpa, hanya bertambah (`rules.md` §6a.9).

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_riwayat_penghunian` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `rumah_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | |
| `transmigran_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | |
| `tanggal_masuk` | `DATE` | TIDAK | IDX | Sumber grafik KK masuk per tahun |
| `tanggal_keluar` | `DATE` | YA | IDX | `NULL` berarti masih menghuni; sumber grafik KK keluar |
| `alasan_keluar` | `TEXT` | YA | | Pindah, kembali ke daerah asal, meninggal |
| `keterangan` | `TEXT` | YA | | |

**Catatan:** grafik "KK masuk dan keluar per tahun" (PRD §7.8) dihitung dari tabel ini, bukan dari `transmigran`, agar perpindahan antar-rumah tetap terekam.

### 6.4 `riwayat_kepala_keluarga`

Jejak pergantian kedudukan kepala keluarga pada satu rumah tangga. Tidak pernah ditimpa, hanya bertambah (`rules.md` §6.5).

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_riwayat_kepala_keluarga` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `transmigran_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | Rumah tangganya; **tidak pernah berubah** |
| `nik_lama` | `CHAR(16)` | TIDAK | | NIK kepala keluarga yang digantikan |
| `nama_lama` | `VARCHAR(255)` | TIDAK | | Nama kepala keluarga yang digantikan |
| `nik_baru` | `CHAR(16)` | TIDAK | | NIK penggantinya |
| `nama_baru` | `VARCHAR(255)` | TIDAK | | Nama penggantinya |
| `no_kk_lama` | `CHAR(16)` | TIDAK | | Nomor KK sebelum pergantian |
| `no_kk_baru` | `CHAR(16)` | TIDAK | | Nomor KK sesudahnya; sama dengan yang lama bila KK tidak terbit ulang |
| `tanggal_pergantian` | `DATE` | TIDAK | IDX | |
| `alasan` | `ENUM` | TIDAK | IDX | Lihat 11.36 |
| `hubungan_pengganti` | `ENUM` | TIDAK | | Lihat 11.39 (sejak Stage B3, 2026-08-28; sebelumnya §11.35). Dibaca dari `anggota_keluarga.hubungan` pengganti yang dipilih |
| `keterangan` | `TEXT` | YA | | |

**Catatan:**

- **Rumah tangganya berlanjut, yang berganti kepalanya.** Karena itu suksesi menyunting baris `transmigran` yang ada, bukan membuat baris baru. Alasannya bukan kepraktisan: jatah rumah dan lahan transmigrasi diberikan kepada **KK**, bukan kepada suaminya secara pribadi, sehingga ketujuh relasi yang menaut ke `transmigran` memang seharusnya tetap utuh. Membuat baris baru juga menuntut melepas UNIQUE pada `no_kk` dan memindahkan tujuh FK secara manual, dan setiap hitungan "jumlah KK" pada dashboard akan menghitung ganda kecuali disaring status.
- **`audit_log` saja tidak cukup, dan itulah sebab tabel ini ada.** Audit log memang merekam bahwa `nama_kepala_keluarga` berubah, tetapi ia **tidak dapat membedakan suksesi dari perbaikan salah ketik**: keduanya tercatat sebagai aksi `Ubah` pada kolom yang sama. Data contoh audit log sendiri sudah memuat contoh yang pertama, yaitu *"Memperbaiki ejaan nama YOHANES BERE"*.
- **Kedua sisi disimpan**, bukan hanya yang lama. Merangkai nama pengganti dari baris riwayat berikutnya memang menghemat tiga kolom, tetapi menukarnya dengan kueri berantai yang rapuh dan riwayat yang tidak dapat dibaca berdiri sendiri.
- **`no_kk` ikut disimpan dua sisi** sebab Dukcapil menerbitkan KK baru ketika kepala keluarganya berganti. Bila nomornya tidak berubah, keduanya diisi sama.
- Tanpa kolom `user_id`: pelaku suksesi sudah terekam `audit_log`, sama seperti `riwayat_penghunian`.
- **Suksesi adalah tindakan tersendiri, bukan efek samping form ubah** (`rules.md` §6.5b). Bila ia lahir dari penyuntingan nama pada form biasa, setiap perbaikan ejaan akan mengotori riwayat suksesi — persis kekaburan yang tabel ini dibuat untuk menutupnya.
- **Pengganti dipilih dari daftar anggota keluarga** (Stage B3, 2026-08-28; membalik `erd.md` §7.4a). Petugas memilih orangnya dari `anggota_keluarga` keluarga itu; `nama_baru`, `nik_baru`, dan `hubungan_pengganti` dibaca dari baris itu, lalu barisnya sebagai anggota keluarga **dihapus** sebab ia kini kepala keluarga. Data pada tabel ini tetap denormalisasi (kedua sisi identitas), jadi tidak ada FK ke `anggota_keluarga` yang akan menggantung setelah penghapusan.

---

## 7. Domain Lahan

### 7.1 `lahan`

Menggabungkan `lahan_sp`, `lahan_usaha_sp`, `kategori_lahan_sp`, dan `kategori_lahan` dari SQL referensi menjadi satu tabel (`erd.md` §8.2 nomor 18).

**SATU BARIS = SATU KELUARGA (Putaran 15, 2026-09-02).** Sebelumnya satu baris adalah satu BIDANG ber-`peruntukan_lahan`, sehingga keluarga dengan pekarangan dan lahan usaha menempati dua baris. Disatukan sebab jumlahnya memang tetap: tepat satu pekarangan dan satu lahan usaha per keluarga (`rules.md` §7.8), dan ditegakkan `UNIQUE (transmigran_id)`. Kolom `peruntukan_lahan`, `luas`, `lintang`, `bujur` dan enum `PeruntukanLahan` dicabut seluruhnya.

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_lahan` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `transmigran_id` | `BIGINT UNSIGNED` | TIDAK | FK, **UQ** | Pemilik; **satu keluarga tepat satu baris** |
| `satuan_permukiman_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | |
| `poktan_id` | `BIGINT UNSIGNED` | YA | FK | Poktan pengelola bila ada |
| `kode_lahan` | `VARCHAR(50)` | YA | UQ | Satu kode per keluarga (`rules.md` §7.1) |
| `luas_pekarangan` | `DECIMAL(12,2)` | YA | | Hektare. **`NULL` = keluarga belum menerima pekarangan**, bukan menerima seluas nol |
| `lintang_pekarangan` | `DECIMAL(10,7)` | YA | | Titik bidang pekarangan |
| `bujur_pekarangan` | `DECIMAL(10,7)` | YA | | |
| `luas_usaha` | `DECIMAL(12,2)` | YA | | Hektare. `NULL` = belum menerima lahan usaha. Komposisi: `luas_kering + luas_basah = luas_usaha` |
| `luas_kering` | `DECIMAL(12,2)` | YA | | Hektare; bagian kering lahan USAHA |
| `luas_basah` | `DECIMAL(12,2)` | YA | | Hektare; bagian basah lahan USAHA |
| `lintang_usaha` | `DECIMAL(10,7)` | YA | | Titik bidang usaha, TERPISAH dari pekarangan |
| `bujur_usaha` | `DECIMAL(10,7)` | YA | | |
| ~~`status_hak`~~ | ~~`ENUM`~~ | | | **Dicabut menyeluruh 2026-08-29** beserta enum `StatusHakLahan` (§11.13) |
| `tujuan_pemanfaatan` | `TEXT` | YA | | |
| ~~`pola_tanam` / `peralatan_pertanian` / `kendala`~~ | | | | **DICABUT 2026-09-02** (Putaran 12), beserta tab Pengelolaan (`rules.md` 7.7) |
| `keterangan` | `TEXT` | YA | | |

**Catatan:**
- `transmigran_id` **UNIQUE**: alur "Tambah Lahan" hanya menawarkan KK yang belum punya baris; KK yang sudah terdata disunting lewat alur Ubah (menawarkan Tambah untuk KK yang sudah ada akan selalu ditolak UNIQUE tanpa menjelaskan apa pun).
- **`NULL` pada `luas_pekarangan` berarti BELUM MENERIMA**, bukan nol. Dua dari empat keluarga pada data contoh hanya memegang lahan usaha. Tampilan wajib membedakan "belum menerima" dari "0 ha" (sepola `status_sertifikat`, §6.1).
- **Rekap luas MENJUMLAH KOLOM, bukan baris** (`rules.md` §7.10): `SUM(luas_pekarangan)` + `SUM(luas_usaha)`, serta `SUM(luas_kering)`/`SUM(luas_basah)` bila diperlukan. Setelah satu baris per KK, **jumlah baris lahan ≠ jumlah bidang lahan**.
- **Kering dan basah adalah komposisi luas lahan USAHA, bukan kategori bidang** (2026-08-20). Satu bidang usaha 1 ha dapat digarap 0,5 ha kering + 0,5 ha basah sekaligus. Penyaringan "lahan basah" berarti `luas_basah > 0` (punya bagian basah), bukan seluruhnya basah.
- **Aturan jumlah:** bila `luas_usaha` terisi, `luas_kering + luas_basah` wajib sama dengannya. Bidang seluruhnya kering diisi `luas_basah = 0`, bukan `NULL`.
- **Koordinat dua pasang**, sebab pekarangan dan lahan usaha berada di tempat berbeda; menyatukannya jadi satu titik berarti membuang lokasi yang sudah terdata.

### 7.2 Legalitas lahan — DICABUT sebagai tabel (Putaran 12, 2026-09-02)

Tabel `dokumen_lahan` dan pivot `dokumen_lahan_bidang` **DIHAPUS SELURUHNYA**. Pivot m2m itu menambal AKIBAT penempatan yang keliru: HPL adalah alas hak KAWASAN milik instansi, dan SHM meliputi seluruh lahan satu KELUARGA (pekarangan maupun usaha), sehingga tidak satu pun benar-benar milik bidang (`rules.md` §7.4a, §7.6).

Legalitas kini dibaca dari tempatnya yang benar:

| Dokumen | Tersimpan pada | Peran berkas | Diisi dari | Status |
|---|---|---|---|---|
| SHM | `transmigran` | `transmigran_berkas` peran `shm` | **form Data Lahan** (langkah 3) | `transmigran.status_sertifikat` (§6.1), juga di form Data Lahan |
| HPL | `kawasan_transmigrasi` | `kawasan_transmigrasi_berkas` peran `hpl` | form Data Kawasan | — |

Enum `JenisDokumenLahan` beserta nilai referensi `jenis_dokumen_lahan` `[HPL, SHM]` dan izin `dokumen_lahan` dicabut Putaran 15 (isian bangkai `jenis_dokumen`/`file_dokumen` per bidang ikut dicabut).

**Form lahan langkah 3 (sejak 2026-09-03):** SHM dan status sertifikat adalah ISIAN — sejak satu keluarga tepat satu baris lahan, tidak ada lagi risiko salinan sertifikat ganda per-bidang yang dahulu menjadikannya "bacaan saja" (`rules.md` §7.6a). Berkasnya tetap tersimpan pada `transmigran_berkas`; form lahan hanya permukaan entrinya. HPL tetap BACAAN + tautan ke Data Kawasan.

---

## 8. Domain Kelembagaan dan Sarana

### 8.1 `poktan`

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_poktan` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `satuan_permukiman_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | |
| `asal_ketua` | `ENUM` | TIDAK | | Lihat 11.34; bawaan `Kepala Keluarga`. Menentukan jalur pengisian data ketua |
| `ketua_transmigran_id` | `BIGINT UNSIGNED` | YA | FK | Keluarga yang diwakili ketua. Wajib bila `asal_ketua` bukan `Bukan Transmigran` |
| `ketua_anggota_keluarga_id` | `BIGINT UNSIGNED` | YA | FK | **Wajib bila `asal_ketua` = `Anggota Keluarga`**, selain itu `NULL`. Menunjuk baris `anggota_keluarga`; nama, NIK, dan hubungan dibaca dari sana. Ditambahkan 2026-08-28 (Stage B2) |
| `nama_ketua` | `VARCHAR(255)` | YA | | **Hanya** bila `asal_ketua` = `Bukan Transmigran`, selain itu `NULL` (sejak Stage B2 jalur Anggota Keluarga memakai FK di atas) |
| `nik_ketua` | `CHAR(16)` | YA | | **Hanya** bila `asal_ketua` = `Bukan Transmigran`; tepat 16 digit angka |
| ~~`hubungan_ketua`~~ | ~~`ENUM`~~ | | | **Dicabut 2026-08-28 (Stage B2).** Dibaca dari `anggota_keluarga.hubungan` lewat `ketua_anggota_keluarga_id` |
| `nama` | `VARCHAR(255)` | TIDAK | UQ | |
| `tahun_berdiri` | `YEAR` | YA | | Tahun saja; tanggal pendirian poktan lama kerap tidak terdokumentasi |
| `telepon_ketua` | `VARCHAR(20)` | YA | | Kontak ketua, bukan kontak kelompok |
| `email_ketua` | `VARCHAR(255)` | YA | | Kontak ketua; `transmigran` tidak menyimpan email, sehingga di sinilah tempatnya |
| `alamat_ketua` | `VARCHAR(255)` | YA | | Alamat ketua atau sekretariat kelompok |
| `luas_kering_ketua` | `DECIMAL(12,2)` | YA | | Hektare; **hanya** bila `asal_ketua` = `Bukan Transmigran` |
| `luas_basah_ketua` | `DECIMAL(12,2)` | YA | | Hektare; **hanya** bila `asal_ketua` = `Bukan Transmigran` |
| `lintang` | `DECIMAL(10,7)` | YA | | |
| `bujur` | `DECIMAL(10,7)` | YA | | |
| ~~`dokumen_pendukung`~~ | | | | **DIPINDAH 2026-09-02** ke registry `berkas` lewat pivot atau FK langsung (`rules.md` 14a.8) |
| `keterangan` | `TEXT` | YA | | |

**Catatan:**
- **Ketua poktan punya tiga asal-usul, bukan dua** (diperluas 2026-08-20). Kolom `is_ketua_transmigran` bertipe boolean digantikan `asal_ketua` bertipe enum, sebab boolean hanya sanggup membedakan dua keadaan sedangkan keadaan lapangan ada tiga. Jalur pengisiannya:

  | `asal_ketua` | `ketua_transmigran_id` | `ketua_anggota_keluarga_id` | `nama_ketua`, `nik_ketua` | Luas & koordinat |
  |---|---|---|---|---|
  | `Kepala Keluarga` | wajib | `NULL` | `NULL`, dibaca lewat relasi | dari lahan keluarga |
  | `Anggota Keluarga` | wajib | **wajib** | `NULL`, dibaca dari anggota keluarga | dari lahan keluarga |
  | `Bukan Transmigran` | `NULL` | `NULL` | **wajib diketik** | `luas_*_ketua` diketik |

  - Jalur pertama tidak menyalin nama dan NIK agar tidak ada dua versi data yang berpotensi tidak sinkron (`erd.md` §8.2 nomor 25).
  - **Jalur kedua sejak Stage B2 (2026-08-28) memilih dari daftar anggota keluarga**, bukan mengetik. `erd.md` §7.4 dibalik: sistem kini mendata anggota keluarga satu per satu (§6.1a). `ketua_transmigran_id` tetap terisi karena yang ditunjuk adalah **keluarga**; `ketua_anggota_keluarga_id` menunjuk orangnya di dalam keluarga itu. Hubungannya dibaca dari `anggota_keluarga.hubungan`.
  - Jalur ketiga ada sebab banyak poktan diketuai penduduk setempat yang bukan peserta program; membatasi pilihan pada daftar transmigran membuat poktan semacam itu tidak dapat didata sama sekali.
- **Luas lahan ketua diturunkan, tidak disimpan** — kecuali bagi ketua non-transmigran. Untuk kedua jalur pertama, luas kering dan basah dijumlahkan dari bidang milik keluarga yang bersangkutan (`SUM` atas `lahan`), sejalan dengan `rules.md` §7.10. Menyimpannya sebagai kolom akan basi begitu petugas membetulkan luas di modul lahan, kekeliruan yang sama dengan `jumlah_anggota` (`erd.md` §7.3). Ketua non-transmigran tidak memiliki bidang terdata, sehingga hanya bagi merekalah kedua kolom itu terisi.
- **Kolom `luas_lahan_kelompok` dihapus** (2026-08-20). Ia tidak pernah dipakai satu berkas pun di seluruh sistem: tidak ada isiannya di form, tidak ada tampilannya, tidak ada uji, dan `DummyData::poktan()` bahkan tidak memuat kuncinya. Luas lahan kelompok kini dijumlahkan dari lahan seluruh anggotanya.
- **Kontak yang disimpan adalah kontak ketua, bukan kontak kelompok** (ditetapkan 2026-08-17, alasannya diperbaiki 2026-08-19). Dasarnya keterangan pemilik proyek: kelompok tani di Kobalima Timur **tidak memiliki kontak sendiri** yang berbeda dari kontak ketuanya, sehingga menyediakan dua pasang kolom hanya menyisakan satu yang selalu kosong. Sebelumnya kolom ini bernama `telepon`, `email`, dan `alamat_sekretariat` serta dinyatakan milik kelompok. `email_ketua` juga menjadi satu-satunya tempat email ketua dapat disimpan, sebab tabel `transmigran` tidak memiliki kolom email padahal `rules.md` §7a poin 2 mewajibkannya.
  > Alasan yang semula ditulis di sini bersandar pada bentuk data contoh, dan itu penalaran melingkar yang dilarang `rules.md` §19a. Lihat `notes.md` §1c.2 pelanggaran kelima.
- Kolom `jumlah_anggota` sengaja **tidak ada**; nilainya dihitung dari `anggota_poktan` berstatus Aktif memakai `withCount` (`erd.md` §7.3).

### 8.2 `anggota_poktan`

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_anggota_poktan` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `poktan_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX, UQ¹ | |
| `transmigran_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX, UQ¹ | **Keluarga** yang diwakili, bukan orangnya |
| `asal_wakil` | `ENUM` | TIDAK | | Lihat 11.34; bawaan `Kepala Keluarga`. Nilai `Bukan Transmigran` tidak berlaku di sini |
| `anggota_keluarga_id` | `BIGINT UNSIGNED` | YA | FK | **Wajib bila `asal_wakil` = `Anggota Keluarga`**, selain itu `NULL`. Menunjuk baris `anggota_keluarga`; nama, NIK, telepon, dan hubungan dibaca dari sana. Ditambahkan 2026-08-28 (Stage B2) |
| ~~`nama_wakil`~~ | ~~`VARCHAR(255)`~~ | | | **Dicabut 2026-08-28 (Stage B2).** Dibaca dari `anggota_keluarga` lewat `anggota_keluarga_id` |
| ~~`nik_wakil`~~ | ~~`CHAR(16)`~~ | | | **Dicabut 2026-08-28 (Stage B2).** Idem |
| `telepon_wakil` | `VARCHAR(20)` | YA | | Kontak wakil; boleh disunting. Terisi sendiri dari `anggota_keluarga.telepon` (jalur Anggota Keluarga) atau dari `transmigran` (jalur Kepala Keluarga) |
| ~~`hubungan_dengan_kk`~~ | ~~`ENUM`~~ | | | **Dicabut 2026-08-28 (Stage B2).** Dibaca dari `anggota_keluarga.hubungan` |
| `jabatan` | `ENUM` | TIDAK | | Lihat §11.15 |
| `tanggal_masuk` | `DATE` | TIDAK | | |
| `status` | `ENUM` | TIDAK | IDX | Lihat §11.16 |
| `tanggal_keluar` | `DATE` | YA | | Wajib diisi bila status Sudah Keluar |
| `alasan_keluar` | `TEXT` | YA | | Sebab berhenti atau pindah kelompok |
| `keterangan` | `TEXT` | YA | | Catatan umum keanggotaan |

¹ UNIQUE gabungan `(poktan_id, transmigran_id)`.

**Catatan:**
- Anggota yang berhenti **tidak dihapus**, melainkan ditandai `status = 'Sudah Keluar'` agar riwayat tetap utuh (`rules.md` §5.1 catatan 7).
- **Keanggotaan melekat pada keluarga, bukan pada kepala keluarga** (ditetapkan 2026-08-20 atas keterangan pemilik proyek). Yang terdaftar adalah orang yang benar-benar menggarap dan menghadiri pertemuan, dan ia tidak selalu kepala keluarga: bila kepala keluarga merantau, istri atau anaknya yang mewakili. Karena itu `transmigran_id` menunjuk **keluarga** yang diwakili, sedangkan `asal_wakil` menyatakan siapa wakilnya.
  - `Kepala Keluarga` → nama, NIK, dan telepon dibaca lewat relasi ke `transmigran`; `anggota_keluarga_id` dibiarkan `NULL`.
  - `Anggota Keluarga` → **`anggota_keluarga_id` dipilih dari daftar anggota keluarga** yang bersangkutan (Stage B2, 2026-08-28). Nama, NIK, telepon, dan hubungan dibaca dari baris itu. Sebelumnya diketik, sebab `erd.md` §7.4 menyatakan sistem tidak mendata anggota keluarga satu per satu; keputusan itu dibalik.
  - `Bukan Transmigran` **tidak berlaku** bagi anggota: seluruh anggota wajib berasal dari keluarga transmigran (`rules.md` §7a poin 3). Pembatasannya ditegakkan aplikasi, sebab ENUM database memuat ketiga nilai agar dapat dipakai bersama `poktan.asal_ketua`.
- **Luas lahan dan koordinat anggota diturunkan, tidak disimpan.** Keduanya dijumlahkan dari bidang milik keluarga yang diwakili, sehingga tidak berubah ketika wakilnya berganti dan tidak pernah basi ketika luas dibetulkan di modul lahan.
- **`alasan_keluar` dipisahkan dari `keterangan`** (2026-08-20). Sebelumnya `keterangan` dipakai dua maksud sekaligus: kamus data menyebutnya catatan umum, sedangkan form melabelinya "Alasan Keluar", sehingga catatan keanggotaan biasa tidak punya tempat. Pemisahan ini mengikuti `riwayat_penghunian` §6.3 yang sudah membedakan `alasan_keluar` dari `keterangan`.
- **Jabatan tidak lagi memuat nilai `Ketua`** (2026-08-17). Ketua ditetapkan pada tabel `poktan`, dan menyediakannya juga di sini berarti satu poktan dapat memiliki dua ketua berbeda tanpa ada yang menyadarinya. Lihat §11.15.
- **Perpindahan anggota antar poktan** dicatat sebagai dua baris: baris di poktan lama ditandai `Sudah Keluar` beserta `tanggal_keluar` dan alasannya, lalu dibuat baris baru pada poktan tujuan. Memindahkan `poktan_id` pada baris yang sama akan menghapus jejak keanggotaan di poktan lama seolah tidak pernah ada.
- Seorang transmigran hanya boleh berstatus **Aktif pada satu poktan** dalam satu waktu (`rules.md` §6.4). UNIQUE gabungan di atas hanya mencegah baris ganda pada poktan yang sama, sehingga pembatasan ini ditegakkan di tingkat aplikasi.

### 8.3 `alsintan` (induk) + `alsintan_distribusi`

Alat dan mesin pertanian. **Diubah Putaran 7 (2026-08-30): satu pengadaan, banyak poktan.** Baris `alsintan` mendeskripsikan bendanya; pembagian ke poktan ada di `alsintan_distribusi`.

**`alsintan` (pengadaan):**

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_alsintan` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `jenis_alsintan` | `VARCHAR(120)` | TIDAK | IDX | Nilai data master, lihat §11.37 (`jenis_alsintan`). Menggantikan "Jenis Alat" yang dahulu `nama_alat` dipakai ulang |
| `nama_alat` | `VARCHAR(255)` | TIDAK | | Merek/tipe spesifik: "TRAKTOR RODA DUA KUBOTA" |
| `jumlah_total` | `INT UNSIGNED` | TIDAK | | Unit yang diadakan; Σ `alsintan_distribusi.jumlah` ≤ nilai ini |
| `tahun_pengadaan` | `YEAR` | YA | IDX | Tahun alat diadakan |
| `sumber_dana` | `ENUM` | YA | | Lihat §11.3 |
| ~~`dokumen_pendukung`~~ | | | | **DIPINDAH 2026-09-02** ke registry `berkas` lewat pivot atau FK langsung (`rules.md` 14a.8) |
| `keterangan` | `TEXT` | YA | | |

**`alsintan_distribusi` (satu baris per poktan penerima):**

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_alsintan_distribusi` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `alsintan_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | Pengadaan induk; `ON DELETE CASCADE` |
| `poktan_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | Kelompok tani penerima |
| `jumlah` | `INT UNSIGNED` | TIDAK | | Unit yang diterima poktan ini |
| `kondisi` | `ENUM` | YA | | Lihat §11.5. Melekat di sini, bukan pengadaan: diamati per unit di lapangan |
| `penanda_terima_id` | `BIGINT UNSIGNED` | YA | FK, IDX | Anggota poktan penerima yang menandatangani berita acara. BUKAN pemilik (`rules.md` §7b poin 8). Menunjuk `anggota_poktan.id` |
| `tanggal_serah` | `DATE` | YA | | Tanggal serah terima ke poktan ini |
| ~~`foto`~~ | | | | **DIPINDAH 2026-09-02** ke registry `berkas` lewat pivot atau FK langsung (`rules.md` 14a.8) |
| `keterangan` | `TEXT` | YA | | |

Turunan pada `alsintan()`: `satuan_permukiman_id` per baris distribusi mengikuti poktannya; `jumlah_tersalur`, `jumlah_belum_tersalur` (barang di gudang UPT). `penanda_terima` (nama) dihitung dari `anggotaPoktan()`.

**Pemilik selalu kelompok tani.** Kolom `kepemilikan` dan `transmigran_id` **dicabut 2026-08-22** bersama enum `KepemilikanAlsintan` (dahulu §11.17). ~~`poktan_id` pada `alsintan` (wajib sejak 2026-08-22) dibalik Putaran 7: satu batch ke banyak poktan memaksa input redundan; `poktan_id` pindah ke `alsintan_distribusi`.~~ ~~`jumlah` → `jumlah_total`; `kondisi`/`penanda_terima_id`/`foto` pindah ke distribusi.~~

Sebelumnya dua jalur pemilik disediakan sekaligus, dan akibatnya terlihat pada data: alat berkepemilikan pribadi **tidak dapat dijangkau dari halaman mana pun** kecuali daftar alsintan itu sendiri. Ia tidak muncul pada rincian poktan sebab `poktan_id`-nya kosong, sedangkan halaman transmigran tidak pernah punya tab alsintan.

Alat yang dibeli dari iuran anggota tetap tercatat atas nama kelompok, dengan `sumber_dana` bernilai `Swadaya` (§11.3).

`satuan_permukiman_id` **tidak dipilih petugas**, melainkan terbaca dari poktan pemiliknya. Dropdown terpisah memungkinkan satu alat tercatat pada SP yang berbeda dari kelompoknya tanpa penjaga apa pun.

> **`tahun_perolehan` → `tahun_pengadaan`, `sumber_perolehan` → `sumber_dana` (diseragamkan 2026-08-28).** Saprotan sudah memakai nama itu (§8.4, sejak Putaran 1) dan kedua berkas rujukan (`laporan alsintan.jpeg`, `laporan saprotan.jpeg`) memakai label "Tahun Pengadaan" / "Sumber Dana". Hanya penyeragaman nama; tipe, nullability, dan makna tidak berubah. Modul `inventaris_sp`, `fasilitas_sp`, dan `infrastruktur` **tidak ikut** — mereka tetap `tahun_perolehan`.

### 8.4 `saprotan` (induk) + `saprotan_distribusi`

Sarana produksi pertanian: benih, pupuk, pestisida, mulsa. **Diubah Putaran 7 (2026-08-30): satu pengadaan, banyak poktan** (pola sama §8.3). Sisa benih dihitung per baris distribusi.

**`saprotan` (pengadaan):**

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_saprotan` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `satuan_id` | `BIGINT UNSIGNED` | TIDAK | FK | Satuan jumlah. ~~Data contoh dulu menyimpan nama, dibetulkan jadi FK Putaran 7~~ |
| `komoditas_id` | `BIGINT UNSIGNED` | YA | FK, IDX | **Wajib bila `jenis` = Benih**, kosong bagi jenis lain |
| `jenis` | `ENUM` | TIDAK | IDX | Lihat §11.18 |
| `nama` | `VARCHAR(255)` | TIDAK | | Contoh: Urea, benih jagung hibrida |
| `jumlah_total` | `DECIMAL(12,3)` | TIDAK | | Jumlah yang diadakan; Σ `saprotan_distribusi.jumlah` ≤ nilai ini |
| `varietas` | `VARCHAR(120)` | YA | | **Wajib bila `jenis` = Benih**, kosong bagi jenis lain |
| `jadwal_tanam` | `CHAR(7)` | YA | | Rencana tanam `YYYY-MM` dari berita acara. Tetap di pengadaan, bukan distribusi |
| `tahun_pengadaan` | `YEAR` | TIDAK | IDX | **Tahun anggaran APBD/APBN**, bukan tahun barang diterima. Sumbu pengelompokan laporan panen. Tetap di pengadaan |
| `sumber_dana` | `ENUM` | YA | | Lihat §11.3 |
| ~~`foto`~~ | | | | **DIPINDAH 2026-09-02** ke registry `berkas` lewat pivot atau FK langsung (`rules.md` 14a.8) |
| ~~`dokumen_pendukung`~~ | | | | **DIPINDAH 2026-09-02** ke registry `berkas` lewat pivot atau FK langsung (`rules.md` 14a.8) |
| `keterangan` | `TEXT` | YA | | |

**`saprotan_distribusi` (satu baris per poktan penerima):**

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_saprotan_distribusi` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `saprotan_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | Pengadaan induk; `ON DELETE CASCADE` |
| `poktan_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | Kelompok tani penerima; SP mengikuti poktan |
| `jumlah` | `DECIMAL(12,3)` | TIDAK | | Jatah poktan ini |
| `tanggal_serah` | `DATE` | YA | | Tanggal serah ke poktan ini |
| `keterangan` | `TEXT` | YA | | |

`penanaman.saprotan_id` → **`penanaman.saprotan_distribusi_id`**: penanaman menunjuk jatah SATU poktan, bukan pengadaan (batas #33).

> **`tahun_perolehan` dan `tanggal_penyaluran` dicabut 2026-08-27.** Keduanya tidak pernah diimplementasikan: form dan data contoh memakai `tanggal_perolehan`, nama yang tak pernah ada di kamus, untuk hal yang berbeda. `tahun_pengadaan` menggantikan keduanya dengan makna yang tegas. Bila dinas kelak memerlukan tanggal serah terima yang persis, `tanggal_penyaluran` dikembalikan sebagai `DATE` nullable beserta tempatnya di form.

**Penerima selalu kelompok tani.** Kolom `transmigran_id` **dicabut 2026-08-22** bersama pilihan "Jenis Penerima" pada formnya, dan `poktan_id` berubah dari nullable menjadi wajib. Seluruh pencatatan Produksi Pertanian berpusat pada poktan; pembagian kepada anggota diatur kelompok sendiri di luar sistem. Menyediakan dua jalur penerima membuat sebagian bantuan tercatat atas nama orang dan sebagian atas nama kelompok, sehingga rekap per poktan tidak pernah utuh.

Aturan lama "penyaluran hanya untuk anggota berstatus Aktif" ikut gugur: yang menerima kini kelompok, bukan perorangan.

`saprotan_distribusi.satuan_permukiman_id` (turunan) **tidak dipilih petugas**, melainkan terbaca dari poktan penerimanya per baris distribusi.

#### Benih dan komoditasnya

`komoditas_id` ditambahkan 2026-08-22. Sebelumnya kaitan benih ke komoditas hanya tersirat dari teks `nama`, sehingga sistem tidak tahu "BENIH JAGUNG HIBRIDA" itu benih jagung: tidak ada cara menyaringnya, dan petugas dapat memilih benih padi untuk penanaman jagung tanpa ditegur.

Pupuk, pestisida, dan mulsa sengaja **tidak** diwajibkan berkomoditas. Urea dipakai tanaman apa pun, dan memaksanya memilih satu komoditas berarti mengarang data yang tidak ada di lapangan.

#### Sisa stok benih

**Tidak disimpan sebagai kolom.** Grain PER BARIS DISTRIBUSI sejak Putaran 7:

```
sisa = saprotan_distribusi.jumlah − SUM(penanaman.volume_benih WHERE saprotan_distribusi_id = ini)
```

Menghitungnya di tingkat pengadaan membuat penanaman poktan A menggerus jatah poktan B. Rumus ini **mengoreksi dirinya sendiri** ketika baris penanaman disunting, dan itulah alasannya tidak ada mekanisme "pengembalian stok" di mana pun. Alur nyatanya: poktan menerima 150 kg untuk 10 ha, petugas mencatat penanaman dengan alokasi penuh sehingga sisanya nol; saat ditinjau ulang ternyata baru 3 ha yang ditanam memakai 45 kg, petugas menyunting baris itu, dan sisanya kembali menjadi 105 kg dengan sendirinya. Menyunting baris adalah CRUD biasa, bukan peristiwa yang perlu ditangani khusus.

Menyimpannya sebagai kolom berarti angka itu harus dikoreksi setiap kali satu baris penanaman disunting, dan koreksi yang terlewat tidak akan pernah ketahuan.

**Benih habis sekali pakai, tetapi penguncian terjadi ketika STOKNYA HABIS, bukan ketika pertama kali dipakai.** Mengunci pada pemakaian pertama akan mematahkan penanaman bertahap: laporan Polri MT.II 2025 menunjukkan satu poktan menanam 3 ha lalu 7 ha dari jatah yang sama, dan penanaman kedua itu tidak akan dapat dicatat sama sekali — petugas terpaksa mengarang entri penyaluran baru untuk bantuan yang tidak pernah datang.

Dropdown benih pada form penanaman karena itu hanya menawarkan baris yang `sisa > 0`, berlabel `"BENIH JAGUNG HIBRIDA — sisa 105 kg"`. Petugas perlu tahu berapa yang masih dapat dialokasikan **sebelum** memilih, bukan setelah formnya ditolak.

---

## 9. Domain Produksi Pertanian

### 9.1 `komoditas_poktan`

Tabel pivot: satu poktan dapat mengusahakan banyak komoditas, dan sebaliknya.

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_komoditas_poktan` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `poktan_id` | `BIGINT UNSIGNED` | TIDAK | FK, UQ¹ | |
| `komoditas_id` | `BIGINT UNSIGNED` | TIDAK | FK, UQ¹ | |

¹ UNIQUE gabungan `(poktan_id, komoditas_id)`.

### 9.2 `penanaman`

Catatan penanaman: kelompok tani mana, menanam komoditas apa, kapan, seluas berapa.

**Dahulu bernama `riwayat_tanam`, diubah 2026-08-22** atas keberatan pemilik proyek. Kata "riwayat" menyiratkan catatan masa lalu, padahal barisnya dibuat justru ketika penanaman baru dimulai dan panennya belum ada — bahkan kolom `tanggal_panen` pada §9.3 masih kosong pada saat itu. Lebih menyesatkan lagi, `hasil_panen` menaut ke tabel inilah: menyebut induk dari panen sebagai "riwayat" membuat orang mengira penanaman yang sedang berjalan dicatat di tempat lain.

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_penanaman` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `poktan_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | Kelompok tani pelaksana |
| `komoditas_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | |
| `saprotan_distribusi_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | Jatah distribusi benih yang dipakai; **wajib sejak 2026-08-30 (Putaran 7)** |
| `volume_benih` | `DECIMAL(12,3)` | TIDAK | | **Wajib sejak 2026-08-24** |
| `realisasi_tanam` | `DECIMAL(12,2)` | TIDAK | | Hektare yang benar-benar ditanami |
| `periode_tanam` | `CHAR(7)` | TIDAK | IDX | Bulan tanam, bentuk `YYYY-MM` |
| ~~`dokumen_pendukung`~~ | | | | **DIPINDAH 2026-09-02** ke registry `berkas` lewat pivot atau FK langsung (`rules.md` 14a.8) |
| `keterangan` | `TEXT` | YA | | |

> **Catatan 2026-09-01:** Constraint `UNIQUE (poktan_id, komoditas_id, periode_tanam)` **dicabut**. Satu kelompok tani dapat mencatat lebih dari satu kali penanaman pada bulan yang sama (misal penanaman bertahap di awal dan akhir bulan, atau dari bantuan benih berbeda). `saprotan_id` diperbarui menjadi `saprotan_distribusi_id` mengikuti relasi jatah poktan Putaran 7.

#### Berpusat pada poktan, bukan lahan perorangan

**Kolom `lahan_id` dan `petani` dicabut 2026-08-22**, digantikan `poktan_id`. Seluruh pencatatan Produksi Pertanian berpusat pada kelompok, dan lapangan membenarkannya: laporan Polri MT.II 2025 mencatat satu baris per **poktan**, bukan per bidang lahan.

Rantai lokasinya tetap utuh tanpa lahan: `penanaman → poktan → satuan_permukiman`, sebab poktan sudah menyimpan SP-nya sendiri.

Akibat yang perlu disadari: halaman Lahan kehilangan tab penanaman, dan halaman Transmigran kehilangan data panen per orang. Keduanya diganti tautan ke poktan tempat keluarga itu bernaung.

`luas_tanam` berganti nama menjadi **`realisasi_tanam`** dan berubah menjadi wajib. Penggantian nama itu bukan kosmetik: ia mencegahnya tertukar dengan **luas lahan poktan**, yang merupakan angka terhitung dan bukan isian.

#### Tiga angka yang TIDAK disimpan di sini

| Angka | Dihitung dari |
|---|---|
| Jumlah anggota | Anggota berstatus Aktif pada poktan itu, beserta ketuanya |
| Luas lahan kelompok | Akumulasi lahan ketua dan seluruh anggota aktif |
| Belum ditanam | Lahan tersedia dikurangi `realisasi_tanam` |
| **Status panen** | Keberadaan catatan panen beserta sisa luasnya (lihat di bawah) |

Keempatnya turunan dari data yang sudah ada. Menyimpannya berarti angka itu menjadi basi begitu satu anggota keluar atau satu bidang lahan dibetulkan, dan kebasian itu tidak pernah memerahkan apa pun. Kolom `luas_lahan_kelompok` sudah dicabut 2026-08-20 karena persis alasan ini (`erd.md` §7.3).

#### Lahan kembali, benih tidak

Perbedaan sifat yang disengaja:

- **Benih habis selamanya** begitu ditabur (§8.4).
- **Lahan kembali tersedia** setelah panennya tercatat.

Karena itu perhitungan lahan tersedia hanya mengurangkan penanaman yang **belum dipanen**. Mengurangkan seluruh penanaman sepanjang sejarah akan membuat lahan poktan tampak habis setelah beberapa musim, padahal bidang yang sama memang ditanami berulang kali tiap tahun.

Kriterianya "sudah punya catatan panen", bukan "sisa luasnya nol". Penghalusan 2026-08-22 yang melepaskan lahan sedikit demi sedikit dibuat untuk menangani panen bertahap, dan **gugur bersamanya** pada 2026-08-24: satu penanaman kini menahan lahan seluruhnya atau tidak sama sekali.

#### Status panen (ditambahkan 2026-08-24)

**Tidak ada kolom `status_panen`.** Nilainya diturunkan lewat `DummyData::statusPanen()`, memakai enum `App\Enums\StatusPanen`:

| Status | Syarat | Warna badge |
|---|---|---|
| `Belum Dipanen` | tidak ada satu pun baris `hasil_panen` yang menaut ke sini | gray |
| `Selesai Dipanen` | sudah ada catatan panennya | success |

**DUA NILAI, bukan tiga.** Nilai `Dipanen Sebagian` sempat ada pada hari yang sama lalu dicabut bersama seluruh konsep panen bertahap (`rules.md` §9.9a): keadaan itu tidak lagi mungkin ada, sebab satu panen selalu menutup seluruh luas yang ditanam.

Penanaman yang **gagal total** tetap `Selesai Dipanen`: barisnya ada, hanya seluruh luasnya tercatat sebagai puso. Pembedanya kolom `puso` pada §9.3, bukan status. Bentuk ini mengikuti laporan lapangan yang menaruh Realisasi Panen dan Puso sebagai dua kolom bersebelahan.

Alasan tidak menyimpannya: kolom tersimpan menjadi salah begitu satu baris panen dihapus, dan kesalahan itu tidak pernah memerahkan apa pun.
**`saprotan_id` dan `volume_benih` ditambahkan 2026-08-22.** Keduanya menautkan penanaman ke benih yang dipakainya, sehingga sisa stok dapat dihitung tanpa mekanisme apa pun selain satu pengurangan (§8.4).

`volume_benih` sengaja **disimpan**, bukan dihitung dari `realisasi_tanam` memakai rasio baku. Laporan Polri MT.II 2025 memang memakai 15 kg/ha pada 92 dari 96 barisnya, tetapi rasio itu keputusan program pada satu bantuan, bukan hukum alam: benih swadaya dan komoditas lain memakai takaran berbeda. Menghitungnya otomatis membuat angka karangan tampil seolah-olah hasil pendataan.

**Keduanya WAJIB sejak 2026-08-24**, termasuk untuk bibit swadaya. Komoditas benihnya wajib sama dengan `komoditas_id` di sini.

> **Koreksi.** Kedua kolom dahulu nullable, dengan alasan "penanaman dari benih yang tidak tercatat pada modul saprotan tetap harus dapat didata". Alasan itu **keliru**: enum sumber perolehan pada §8.4 sudah memuat `Swadaya` sejak awal, dan satu baris data contoh sudah memakainya. Bibit swadaya bukan benih yang mustahil didata — ia benih yang kebetulan belum didaftarkan.
>
> Manfaat pewajibannya bukan kerapian data semata: **benih swadaya jadi ikut punya stok**. Tanpa itu ia seolah tak terbatas, dan poktan dapat mencatat penanaman sebanyak apa pun tanpa ada yang menegur (`rules.md` §7d.8b).
>
> Konsekuensi yang diterima sadar: petugas wajib mendaftarkan benih lebih dulu sebelum mencatat penanaman. Peredamnya pesan menuntun beserta tautan ke form saprotan (`rules.md` §7d.8c); tanpa itu, isian wajib tanpa jalan mengisinya justru mendorong petugas mengarang entri.

### 9.3 `hasil_panen`

**Tabel baru**, menggantikan kolom panen yang pada SQL referensi menempel di `lahan_usaha_sp` (`erd.md` §8.2 nomor 15).

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_hasil_panen` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `penanaman_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | Menentukan poktan dan komoditas |
| ~~`poktan_id`~~ | ~~`BIGINT UNSIGNED`~~ | | | **Dicabut Putaran 7 (2026-08-30).** Salinan tanpa alasan snapshot (berbeda dari `satuan_id`); diturunkan dari `penanaman.poktan_id`. Lihat `notes.md` butir Putaran 7-H |
| `satuan_id` | `BIGINT UNSIGNED` | TIDAK | FK | Disalin dari komoditas saat penyimpanan |
| `periode_panen` | `CHAR(7)` | TIDAK | IDX | Bulan panen, bentuk `YYYY-MM` |
| `realisasi_panen` | `DECIMAL(12,2)` | TIDAK | | Hektare yang benar-benar dipanen; tampil sebagai **"Realisasi Panen"** (`rules.md` §9.8j) |
| `puso` | `DECIMAL(12,2)` | YA | | Hektare yang gagal panen |
| `produktivitas` | `DECIMAL(12,3)` | TIDAK | | Per hektare, dalam satuan baku komoditas |
| `produksi` | `DECIMAL(12,3)` | TIDAK | | Disimpan apa adanya, tanpa konversi |
| `harga_jual` | `DECIMAL(15,2)` | YA | | Rupiah per satuan baku |
| ~~`dokumen_pendukung`~~ | | | | **DIPINDAH 2026-09-02** ke registry `berkas` lewat pivot atau FK langsung (`rules.md` 14a.8) |
| `keterangan` | `TEXT` | YA | | |

> **Catatan 2026-08-24.** Kedua kolom terakhir sudah tercantum di sini dan sudah punya isian di form sejak 2026-08-22, tetapi **tidak pernah ada pada data**. Halaman rincian membacanya lewat `?? '-'`, sehingga selalu bertuliskan "-" tanpa pernah memerahkan apa pun: petugas mengetik catatan, menekan simpan, dan catatannya lenyap tanpa pesan. Keterangan `dokumen_pendukung` ikut dibetulkan dari "Foto panen", sebab pembatasan gambar-saja sudah dicabut pada tanggal yang sama.

#### Dua identitas aritmetika

Keduanya WAJIB berlaku:

```
realisasi_panen + puso = penanaman.realisasi_tanam
produksi               = realisasi_panen x produktivitas
```

> **Koreksi 2026-08-24.** Identitas pertama dahulu bersuku tiga, dengan `belum_dipanen` sebagai selisihnya, dan dokumen ini menyatakannya **"terbukti pada 96 baris laporan Polri MT.II 2025"**. Klaim itu terlalu jauh: laporan tersebut **tidak memiliki kolom belum dipanen** sama sekali — kolomnya hanya Realisasi Tanam, Realisasi Panen, dan Puso. Yang terbukti di sana adalah identitas **dua suku**; suku ketiga adalah tambahan sistem yang lalu disebut sebagai temuan lapangan.
>
> Dicatat sebagai koreksi, bukan dihapus diam-diam: ini bentuk lain dari pola yang sudah enam kali tercatat pada `notes.md` §1c, yaitu kesimpulan yang dinyatakan seolah temuan. Bentuk dua suku yang berlaku sekarang justru **sesuai** laporan itu.

**Satu penanaman hanya boleh memiliki satu baris panen** (`rules.md` §9.9). Baris kedua pada penanaman yang sama membuat luasnya terhitung dua kali pada rekap, dan itu tidak akan memerahkan apa pun tanpa penjagaan tersendiri.

**Gagal total** dicatat sebagai `realisasi_panen` nol dengan `puso` menutup seluruh luas. Pada keadaan itu `produktivitas` dan `produksi` bernilai nol, dan itu sah: tidak ada yang ditimbang.

**`produksi` tetap disimpan** meski dapat dihitung dari dua kolom lain: ia angka yang dilaporkan ke dinas, dan pembulatan hasil perkalian dapat berbeda tipis dari angka yang benar-benar ditimbang.

#### Perubahan 2026-08-22

| Kolom | Nasib | Alasan |
|---|---|---|
| `volume` | Berganti nama menjadi `produksi` | Sejalan istilah laporan, dan tidak tertukar dengan `volume_benih` pada penanaman |
| `kualitas` | **Dicabut** beserta enumnya | Keputusan pemilik proyek. Digantikan `produktivitas` yang terukur, bukan label mutu |
| `tanggal_panen` | Berganti `periode_panen` (bulan) | Panen satu hamparan berlangsung berhari-hari; menuntut satu tanggal pasti membuat petugas menebak |
| `petani` | **Dicabut** | Panen dicatat per poktan, bukan per orang |
| `keterangan_satuan_lokal` | **Dicabut** | Padanan satuan setempat ditulis pada `keterangan` biasa; kolom tersendiri jarang terisi dan menambah satu isian yang harus dilewati |

**Catatan penting:**
- `produksi` disimpan dalam satuan baku komoditasnya, **tidak** dikonversi saat penyimpanan (`rules.md` 8a.4).
- Agregasi lintas komoditas memakai `SUM(produksi x satuan.faktor_ke_ton)` dan hanya dilakukan saat rekap (`rules.md` 8a.5).
- `produktivitas` memakai **satuan baku komoditasnya**, bukan selalu ton: jagung ton/ha, cabai kg/ha. Memaksanya ton membuat harga jual cabai per ton menjadi angka yang tidak pernah dipakai siapa pun di lapangan.
- `satuan_id` sengaja disalin dari komoditas, bukan sekadar dibaca lewat relasi, agar data historis tetap sahih bila satuan baku komoditas kelak diubah.
- Lokasi produksi tidak disimpan di sini; dibaca lewat rantai `penanaman -> poktan -> satuan_permukiman`.

#### Rekap dihitung dari penanaman, bukan dari tabel ini (2026-08-24)

Halaman rekap panen **tidak** menjadikan tabel ini sebagai baris dasarnya, melainkan `penanaman` (§9.2). Sebabnya satu: baris di sini hanya ada bila panennya sudah tercatat, sehingga kelompok yang sudah menanam tetapi belum panen **hilang sama sekali** dari rekap — dan justru keadaan itulah yang paling perlu ditengok dinas. Laporan lapangan memilih basis yang sama; kolom "Sisa Tanam" pada laporan itu mustahil ada bila barisnya bukan poktan yang menanam.

Konsekuensi yang mengikutinya:

| Hal | Aturan |
|---|---|
| Periode | Selalu terikat **satu tahun tanam**, tertulis pada judul dan baris total (`rules.md` 9.8b) |
| Penyaring | **Tahun tanam**, bukan tahun panen (`rules.md` 9.8c) |
| Produktivitas agregat | Total produksi dibagi total luas dipanen, **tertimbang** (`rules.md` 9.8d) |
| Angka turunan | Dihitung dari nilai yang **sudah dibulatkan** seperti yang tampil (`rules.md` 9.8e) |
| Cacah | Cacah **poktan** sebagai himpunan; cacah catatan tidak dipakai (`rules.md` 9.8f) |

Luas **tidak pernah** dijumlahkan lintas tahun: bidang 2 ha yang ditanami tiga tahun berturut-turut akan terbaca "6 ha", dan pembaca menyangka kelompok itu memiliki 6 ha lahan.


---

## 10. Domain Infrastruktur dan Pengaduan

### 10.1 `infrastruktur`

Pendataan **aset** infrastruktur. Pelaporan kerusakan ditangani fitur Pengaduan (`rules.md` §10.1).

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_infrastruktur` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `satuan_permukiman_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | **Lokasi/pangkal.** SP tempat aset berada |
| `poktan_id` | `BIGINT UNSIGNED` | YA | FK | Diisi bila dikelola poktan |
| `nama` | `VARCHAR(255)` | TIDAK | | |
| `jenis` | `ENUM` | TIDAK | IDX | Lihat §11.20 |
| `tahun_perolehan` | `YEAR` | YA | | |
| `sumber_dana` | `ENUM` | YA | | Lihat §11.3 |
| `kondisi` | `ENUM` | TIDAK | IDX | Lihat §11.5; sumber grafik status infrastruktur |
| `kapasitas` | `VARCHAR(100)` | YA | | Contoh: "debit 5 liter/detik", "panjang 2 km" |
| `lintang` | `DECIMAL(10,7)` | YA | | |
| `bujur` | `DECIMAL(10,7)` | YA | | |
| ~~`foto`~~ | | | | **DIPINDAH 2026-09-02** ke registry `berkas` lewat pivot atau FK langsung (`rules.md` 14a.8) |
| ~~`dokumen_pendukung`~~ | | | | **DIPINDAH 2026-09-02** ke registry `berkas` lewat pivot atau FK langsung (`rules.md` 14a.8) |
| `keterangan` | `TEXT` | YA | | |

**`infrastruktur_sp` — cakupan layanan lintas SP (Putaran 7, 2026-08-30).** Satu irigasi, jalan masuk kawasan, atau kios "melayani 3 SP" tidak berhenti di batas satu SP; sebelumnya kenyataan itu hanya tertulis di `kapasitas` sebagai teks, dan `PenilaianKondisiSp` menyaring `=== satuan_permukiman_id` sehingga SP tetangga jatuh ke `Perlu Penanganan` lewat aturan primer nol (skor SP yang **salah**).

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_infrastruktur_sp` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `infrastruktur_id` | `BIGINT UNSIGNED` | TIDAK | FK, UQ¹ | `ON DELETE CASCADE` |
| `satuan_permukiman_id` | `BIGINT UNSIGNED` | TIDAK | FK, UQ¹ | SP yang dilayani. **Wajib memuat SP pangkal** |

¹ UNIQUE `(infrastruktur_id, satuan_permukiman_id)`. `PenilaianKondisiSp::nilai()` membaca `in_array($spId, satuan_permukiman_ids)`; mundur aman ke `[satuan_permukiman_id]` bila kolom kosong. **`fasilitas_sp` memakai pola identik** (`fasilitas_sp_cakupan`) untuk SMP Satu Atap, puskesmas pembantu, pasar desa.

### 10.2 `pengaduan`

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_pengaduan` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `user_id` | `BIGINT UNSIGNED` | **YA** | FK, IDX | Diisi bila pengaduan dicatat petugas; kosong bila dilapor warga lewat kanal publik |
| `nama_pelapor` | `VARCHAR(255)` | TIDAK | | Nama warga pelapor |
| `kontak_pelapor` | `VARCHAR(20)` | TIDAK | | Nomor telepon yang dapat dihubungi |
| `email_pelapor` | `VARCHAR(100)` | YA | | **Opsional.** Bila diisi, nomor pengaduan dikirim juga ke sini sebagai salinan |
| `sumber_laporan` | `ENUM` | TIDAK | IDX | Lihat §11.28 |
| `ip_pelapor` | `VARCHAR(45)` | YA | IDX | Alamat IP saat melapor; dipakai untuk pembatasan laju dan penelusuran penyalahgunaan |
| `satuan_permukiman_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | Lokasi kejadian |
| `nomor_pengaduan` | `VARCHAR(30)` | TIDAK | UQ, IDX | Dibuat otomatis, contoh `PGD-2026-0001-K7F2M9`. Enam karakter terakhir **acak**, sehingga nomor tidak dapat ditebak berurutan. Dipakai warga untuk melacak perkembangan laporannya |
| `tanggal_pengaduan` | `DATE` | TIDAK | IDX | |
| `kategori` | `ENUM` | TIDAK | IDX | Lihat §11.21 |
| `bidang` | `ENUM` | **YA** | IDX | Lihat §11.22; menentukan dinas penanganan. `NULL` berarti belum ditetapkan petugas |
| `judul` | `VARCHAR(255)` | TIDAK | | Ringkasan singkat |
| `deskripsi` | `TEXT` | TIDAK | | |
| `status` | `ENUM` | TIDAK | IDX | Lihat §11.23; hanya status **terkini** |
| `prioritas` | `ENUM` | TIDAK | IDX | Lihat §11.24 |
| `lintang` | `DECIMAL(10,7)` | YA | | Titik kejadian |
| `bujur` | `DECIMAL(10,7)` | YA | | Titik kejadian |
| ~~`dokumen_pendukung`~~ | | | | **DIPINDAH 2026-09-02** ke registry `berkas` lewat pivot atau FK langsung (`rules.md` 14a.8) |

**Catatan:**
- Kolom `catatan_penanganan` dan `id_status_penanganan` pada SQL referensi **dihapus**; keduanya duplikatif (`notes.md` §1.5).
- `bidang` menentukan penerusan: Ketransmigrasian ke Dinas Transmigrasi, Pertanian ke Dinas Pertanian (`rules.md` §10b.7). Nilainya diturunkan dari kategori sebagai nilai awal, dapat ditimpa petugas, dan **wajib terisi sebelum status maju ke Diproses**.
- Nilai enum `'Lainnya '` yang mengandung spasi berlebih pada SQL referensi sudah dibersihkan.

**Kanal publik tanpa login.** Warga transmigran tidak memiliki akun sistem, sehingga pengaduan dibuka sebagai halaman publik. Warga cukup mengisi nama, kontak, lokasi SP, kategori, dan uraian masalah.

| Sumber | `user_id` | Cara masuk |
|---|---|---|
| `Publik` | kosong | Warga mengisi sendiri lewat halaman publik |
| `Petugas` | terisi | Petugas mencatatkan laporan yang disampaikan lisan atau tertulis |

**Pengamanan kanal publik:**
1. Pembatasan laju **3 pengaduan per jam per alamat IP**, mencegah pengiriman beruntun otomatis.
2. Seluruh pengaduan publik masuk berstatus `Menunggu Diterima`, sehingga petugas menyaring lebih dulu sebelum diproses.
3. `ip_pelapor` disimpan untuk menelusuri penyalahgunaan.
4. Tidak memakai CAPTCHA, karena membebani pengguna berjaringan lemah di lokus. Pembatasan laju dinilai memadai untuk skala kawasan ini.

**Pelacakan oleh warga.** Setelah mengirim laporan, warga menerima `nomor_pengaduan`. Nomor itu dimasukkan pada halaman lacak publik untuk melihat status terkini beserta riwayat penanganannya. Halaman lacak **hanya menampilkan** status, tanggal, dan catatan penanganan; data pribadi pelapor lain tidak pernah ditampilkan.

**Mengapa nomor memuat bagian acak.** Halaman lacak dapat dibuka tanpa login. Nomor berurutan seperti `PGD-2026-0001` sampai `PGD-2026-0100` dapat ditebak satu per satu, sehingga siapa pun dapat memanen judul pengaduan dan catatan penanganan warga lain. Enam karakter acak di akhir membuat penebakan berurutan tidak berguna, tanpa membuat nomor sulit dicatat warga. Pembatasan laju pada halaman lacak melengkapi perlindungan ini (`rules.md` 14c).

### 10.3 `penanganan_pengaduan`

Riwayat penanganan. Satu pengaduan punya banyak baris; setiap perubahan status menambah satu baris (`rules.md` §10b.5).

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_penanganan_pengaduan` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `pengaduan_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | |
| `user_id` | `BIGINT UNSIGNED` | TIDAK | FK | Petugas penangan |
| `status_sebelum` | `ENUM` | YA | | Lihat §11.23; `NULL` pada baris pertama |
| `status_sesudah` | `ENUM` | TIDAK | | Lihat §11.23 |
| `tanggal_penanganan` | `DATE` | TIDAK | IDX | |
| `catatan` | `TEXT` | TIDAK | | Tindakan yang dilakukan |
| ~~`dokumen_tindak_lanjut`~~ | | | | **DIPINDAH 2026-09-02** ke registry `berkas` lewat pivot atau FK langsung (`rules.md` 14a.8) |

**Catatan:** alur status wajib berurutan Menunggu Diterima → Diterima → Diproses → Selesai (`rules.md` §10b.4). Validasi urutan dilakukan di sisi aplikasi; setiap penyimpanan baris baru juga memperbarui `pengaduan.status`.

---

## 11. Daftar Nilai Enum Terpusat

Nilai berikut dipakai berulang di beberapa tabel. Implementasinya memakai **PHP Enum class** di `app/Enums/` agar tidak ada teks berkode keras di view (`ui-spec.md` §11.7).

### 11.1 Role pengguna

**Bukan enum.** Role disimpan sebagai baris pada tabel `role` (§2.1a) dan dapat dibuat Admin lewat antarmuka. Empat role dibuat lewat seeder sebagai konfigurasi awal:

`Admin` · `Dinas Transmigrasi` · `Dinas Pertanian` · `Operator SP`

Role `Transmigran` dan `Ketua Poktan` pada rancangan semula **dihapus**, karena warga tidak lagi memiliki akun sistem. Pengaduan warga ditangani lewat kanal publik tanpa login (§10.2).

### 11.2 Aksi audit log
`Tambah` · `Ubah` · `Hapus` · `Pulihkan` · `Login` · `Logout` · `Reset Kata Sandi` · `Nonaktifkan Akun` · `Aktifkan Akun` · `Ubah Izin Role`

**Catatan:**
- `Reset Kata Sandi`, `Nonaktifkan Akun`, dan `Aktifkan Akun` mencatat tindakan Admin terhadap akun pengguna lain. `Reset Kata Sandi` wajib tercatat karena memberi Admin kemampuan mengambil alih akses akun mana pun.
- `Ubah Izin Role` mencatat perubahan susunan kewenangan sebuah role, karena tindakan ini dapat memperluas akses banyak pengguna sekaligus.
- Entri `Tambah` dan `Ubah` pada baris data yang sama memungkinkan penelusuran siapa penginput asli dan siapa yang memutakhirkannya.

### 11.3 Sumber dana
`APBN` · `APBD Provinsi` · `APBD Kabupaten` · `Dinas Transmigrasi Kabupaten` · `Dinas Pertanian Kabupaten` · `Lembaga Swadaya Masyarakat` · `Swadaya` · `Lainnya`

Dipakai kolom `sumber_dana` pada `alsintan`, `saprotan`, `infrastruktur`, `inventaris_sp`, dan `fasilitas_sp`. Alsintan sempat menyebutnya `sumber_perolehan`; diseragamkan 2026-08-28 (§8.3).

Nilai disederhanakan dari SQL referensi yang menuliskan awalan berulang "Sumber Perolehan Dana ...". Nilai `Swadaya` dipakai alsintan maupun saprotan yang dibeli kelompok dari iuran anggotanya. Dahulu ditambahkan untuk alsintan milik pribadi; sejak kepemilikan pribadi dicabut 2026-08-22, maknanya bergeser ke pembelian swadaya kelompok.

### 11.4 Status penyerahan
`Sudah Diserahkan` · `Belum Diserahkan` · `Dalam Proses`

### 11.5 Kondisi (barang, fasilitas, alsintan, infrastruktur)
`Baik` · `Rusak Ringan` · `Rusak Berat` · `Hilang`

`Hilang` ditambahkan 2026-08-22. Bukan tingkat kerusakan melainkan **ketiadaan**: barang yang tidak lagi ditemukan saat pendataan. Sebelumnya petugas terpaksa memilih `Rusak Berat`, sehingga inventaris yang lenyap tetap terhitung ada dan dinilai 0,2 alih-alih 0 (lihat §11.28).

### 11.6 Tipe komoditas
`Pangan` · `Palawija` · `Hortikultura`

### 11.7 Pendidikan terakhir
`Tidak Sekolah` · `SD` · `SMP` · `SMA/SMK` · `Diploma` · `S1` · `S2` · `S3`

### 11.8 Status tinggal transmigran
`Aktif` · `Pindah Penduduk` · `Tidak Aktif`

Diubah 2026-08-22: `Pindah` menjadi `Pindah Penduduk`, dan `Meninggal` **dicabut**. Status ini melekat pada **keluarga**, bukan orang, sehingga kematian kepala keluarga tidak membubarkan barisnya: kedudukan berpindah ke ahli waris dan keluarganya tetap `Aktif`. Peristiwa kematian direkam `AlasanPergantianKK` (§11.36), yang memang mencatat orang. Menyediakan `Meninggal` di kedua tempat membuat petugas menandai keluarga yang penghuninya masih ada, dan rumah, lahan, serta keanggotaan poktan keluarga itu ikut hilang dari rekap. Keluarga yang benar-benar tidak lagi berpenghuni cukup ditandai `Tidak Aktif`.

### 11.9 Kondisi rumah
`Tidak Rusak` · `Rusak Ringan` · `Rusak Berat`

Sengaja berbeda dari §11.5 karena `rules.md` §6a.3 menetapkan istilah `Tidak Rusak` khusus untuk rumah.

### 11.10 Status hunian
`Dihuni` · `Tidak Dihuni`

### 11.11 Peruntukan lahan — **DICABUT sebagai enum 2026-09-02 (Putaran 15)**

Semula bernilai `Lahan Pekarangan` · `Lahan Usaha` sebagai kolom enum `peruntukan_lahan` yang menandai peruntukan tiap BARIS. Sejak satu keluarga tepat satu baris (§7.1), kedua bidang berada pada baris yang sama dan masing-masing menjadi kolom tersendiri (`luas_pekarangan`, `luas_usaha`). Enum `PeruntukanLahan` beserta method `lahanUsaha()`/`nilaiLahanUsaha()` dihapus.

Nilai `Lahan Usaha I`/`Lahan Usaha II` yang sempat ditambahkan 2026-08-18 lalu dibatalkan pada hari yang sama tidak lagi relevan. Nomor bagian ini sengaja **tidak dipakai ulang** agar rujukan lama tetap dapat ditelusuri.

Kata `Lahan Pekarangan`/`Lahan Usaha` tetap dipakai sebagai **label penyaring** pada halaman daftar dan laporan lahan (penyaring bertanya "keluarga ini punya bidang itu?"), bukan sebagai nilai enum.

### 11.12 Kategori lahan — **DICABUT 2026-08-20**

Semula bernilai `Lahan Basah` · `Lahan Kering` sebagai kolom enum pada `lahan`. Dicabut sebab sifat pengairan ternyata **komposisi luas, bukan sifat bidang**: satu bidang lahan usaha dapat digarap sebagian kering dan sebagian basah. Digantikan kolom `luas_kering` dan `luas_basah` pada 7.1, yang alasannya tercatat di sana.

Nomor bagian ini sengaja **tidak dipakai ulang** agar rujukan lama pada dokumen dan riwayat tetap dapat ditelusuri.

### 11.13 Status hak atas tanah
`Belum Bersertifikat` — `Hak Milik` — `Hak Milik Bersama` — `Hak Pakai` — `Sewa` — `Garapan`

**Diperbaiki 2026-08-18.** Nilai sebelumnya `HPL`, `SHM`, `Sewa`, `Garapan`, `Lainnya`, dan dua yang pertama keliru sebagai status hak perorangan. **HPL** adalah Hak Pengelolaan yang dipegang instansi atas tanah kawasan, sehingga tidak pernah menjadi hak seorang transmigran; menuliskannya di sini membuat sistem menyatakan warga "memiliki lahan berstatus HPL". **SHM** adalah nama sertifikatnya, bukan nama haknya; haknya bernama Hak Milik. Keduanya kini menjadi jenis dokumen (11.14).

Rantai yang sebenarnya: tanah kawasan berstatus Hak Pengelolaan, lalu bidang-bidangnya dibagikan kepada transmigran dengan status Hak Milik. Sebelum sertifikatnya terbit, bidang berstatus `Belum Bersertifikat` dan legalitas penggunaannya bersandar pada surat keterangan pembagian tanah.

> Istilah pada daftar ini **masih menunggu konfirmasi dinas** (`notes.md` bagian 6), sebab berkas penetapan di tiap daerah dapat memakai sebutan berbeda.

### 11.14 Jenis dokumen lahan — **DICABUT 2026-09-02 (Putaran 15)**

Semula bernilai `HPL` · `SHM` (enum `JenisDokumenLahan`, nilai referensi `jenis_dokumen_lahan`). Dicabut seluruhnya bersama tabel `dokumen_lahan`: HPL adalah alas hak KAWASAN dan SHM meliputi seluruh lahan satu KELUARGA, sehingga tidak ada dokumen yang dimiliki satu bidang saja (§7.2). Nomor bagian ini sengaja **tidak dipakai ulang**.

### 11.15 Jabatan anggota poktan
`Sekretaris` — `Bendahara` — `Anggota`

Nilai `Ketua` **dicabut 2026-08-17**. Ketua ditetapkan pada tabel `poktan` lewat `is_ketua_transmigran` beserta pasangannya, sebab ketua tidak selalu berasal dari anggota yang terdaftar di sini. Menyediakan `Ketua` pada kedua tempat membuat satu poktan dapat memiliki dua ketua berbeda tanpa penjaga apa pun.

### 11.16 Status keaktifan anggota
`Aktif` · `Tidak Aktif` · `Sudah Keluar`

### 11.17 Kepemilikan alsintan — DICABUT 2026-08-22

Nilainya dahulu `Pribadi` · `Bantuan Poktan`.

**Dicabut atas keputusan pemilik proyek**, mengikuti aturan bahwa seluruh menu Pertanian mencatat **kelompok**, bukan individu. Enum bernilai tunggal tidak menerangkan apa pun, sehingga ia dihapus seluruhnya alih-alih disisakan satu nilai. Kolom `alsintan.kepemilikan` dan `alsintan.transmigran_id` ikut lepas (§8.3).
### 11.18 Jenis saprotan
`Benih` · `Pupuk` · `Pestisida` · `Mulsa` · `Lainnya`

### 11.19 Kualitas panen — DICABUT 2026-08-22

Nilainya dahulu `Sangat Baik` · `Baik` · `Cukup` · `Kurang`, dan sempat dijadikan ENUM agar dapat direkap (`notes.md` §1.6).

**Dicabut atas keputusan pemilik proyek.** Kolom `hasil_panen.kualitas` ikut hilang, digantikan `produktivitas` yang merupakan angka terukur. Label mutu menuntut penilaian yang tidak dapat diverifikasi, sedangkan produktivitas per hektare dihitung dari timbangan.

### 11.20 Jenis infrastruktur
`Air` · `Irigasi` · `Listrik` · `Jalan Produksi` · `Telekomunikasi` · `Gudang` · `Lainnya`

### 11.21 Kategori pengaduan
`Lahan Usaha` · `Lahan Pekarangan` · `Rumah` · `Infrastruktur` · `Inventaris SP` · `Fasilitas SP` · `Kelompok Tani` · `Alsintan` · `Saprotan` · `Produksi Panen` · `Bencana` · `Lainnya`

Tiga perubahan pada 2026-08-19: nilai `Peralatan dan Perlengkapan` **dipecah** menjadi `Inventaris SP` dan `Fasilitas SP`, sebab satu kategori menaungi dua daftar berbeda sehingga petugas tidak dapat mengetahui yang mana dimaksud pelapor; `Saprotan` **ditambahkan** agar keluhan bibit, pupuk, serta obat tidak menumpang pada `Produksi Panen`; dan `Kelompok Tani` **ditambahkan** sebab poktan adalah modul penuh tetapi keluhan atasnya tidak punya kategori sendiri.

**Daftar kategori memetakan modul yang dapat diadukan warga.** Penyisiran 2026-08-19 atas 26 fitur berkewenangan (§13.1) menyimpulkan pemetaannya kini lengkap dua arah. Modul yang sengaja **tidak** berkategori: `pengguna`, `role`, `audit_log`, `dashboard` (urusan internal sistem); `wilayah`, `kawasan`, `sp`, `satuan` (data referensi, bukan benda yang dapat rusak); `transmigran`, `riwayat_penghunian`, `riwayat_kepala_keluarga`, `dokumen_lahan`, `anggota_poktan`, `penanganan_pengaduan` (catatan administratif tentang warga; warga mengadukan masalah, bukan sesama warga, dan kekeliruan pencatatan diperbaiki lewat petugas bukan lewat kanal pengaduan); serta `komoditas` dan `penanaman` (data master pertanian yang keluhannya bermuara ke `Produksi Panen`).

### 11.22 Bidang pengaduan
`Ketransmigrasian` · `Pertanian`

Menentukan dinas penanganan. Kolom `pengaduan.bidang` **boleh `NULL`**, artinya bidangnya belum ditetapkan petugas.

Nilai awalnya diturunkan dari kategori lewat `BidangPengaduan::dariKategori()`, tetapi **selalu dapat ditimpa** petugas (`rules.md` §10b.7c):

| Kategori | Bidang bawaan |
|---|---|
| `Rumah`, `Lahan Pekarangan`, `Inventaris SP`, `Fasilitas SP` | Ketransmigrasian |
| `Kelompok Tani`, `Alsintan`, `Saprotan`, `Produksi Panen` | Pertanian |
| `Lahan Usaha`, `Infrastruktur`, `Bencana`, `Lainnya` | **`NULL`**, wajib ditetapkan petugas |

Empat kategori terakhir sengaja netral sebab pokok masalahnya dapat jatuh ke dua dinas sekaligus: sengketa lahan usaha bisa menyangkut pembagian lahan (Ketransmigrasian) maupun produktivitasnya (Pertanian), sedangkan bencana dan `Lainnya` memang tidak menunjuk urusan tertentu. Menebak bidang untuk kategori semacam itu justru menyesatkan, sebab laporan akan masuk ke daftar dinas yang keliru lalu tertahan di sana.

### 11.23 Status pengaduan
`Menunggu Diterima` · `Diterima` · `Diproses` · `Selesai`

### 11.24 Prioritas pengaduan
`Rendah` · `Sedang` · `Tinggi` · `Mendesak`

### 11.25 Cakupan data role
`Semua` · `Per SP` · `Per Bidang`

Menentukan **data siapa** yang boleh dilihat, terpisah dari kewenangan yang menentukan **boleh melakukan apa**.

| Nilai | Penyaring query | Pemakai |
|---|---|---|
| `Semua` | tanpa penyaring | Admin, Dinas Transmigrasi |
| `Per SP` | dibatasi SP pada `user_satuan_permukiman` | Operator SP |
| `Per Bidang` | dibatasi `pengaduan.bidang` yang sesuai | Dinas Pertanian |

**Mengapa kedua dinas tidak simetris.** Dinas Transmigrasi bercakupan `Semua`, bukan `Per Bidang`, sebab sistem ini milik Dinas Transmigrasi sebagai pengelola kawasan. Merekalah yang menyaring laporan berbidang `NULL` dan menetapkan bidangnya, sehingga laporan bidang pertanian baru muncul pada daftar Dinas Pertanian setelah ditetapkan.

Konsekuensi yang diterima sadar: satu-satunya jalan laporan sampai ke Dinas Pertanian adalah lewat penetapan Admin atau Dinas Transmigrasi. Peredamnya, filter bidang pada halaman daftar menyediakan pilihan **Belum ditentukan** beserta jumlahnya, sehingga antrean penyaringan tidak menumpuk diam-diam.

### 11.26 Aksi permission
`lihat` — `tambah` — `ubah` — `hapus`

### 11.28 Sumber laporan pengaduan
`Publik` · `Petugas`

`Publik` berarti warga mengisi sendiri lewat halaman tanpa login. `Petugas` berarti laporan dicatatkan petugas atas nama warga.

### 11.29 Tingkat kebutuhan parameter penilaian SP

`Primer` · `Sekunder` · `Tersier`

Dikelompokkan menurut satu pertanyaan: tanpa parameter ini, apakah tempat tersebut masih layak dihuni. Bobot bawaan berturut-turut 5, 3, dan 1 (`rules.md` 10c.3).

### 11.30 Status kondisi SP

`Mandiri` · `Berkembang` · `Perlu Penanganan`

Istilah bernada merendahkan seperti "terbelakang" atau "tertinggal" **dilarang**, sebab yang dinilai adalah infrastruktur, bukan warganya (`rules.md` 10c.1 poin 3).

Warna badge: Mandiri `success` · Berkembang `warning` · Perlu Penanganan `error`.
**Nama dan ambangnya dapat disunting dinas** lewat `/master/penilaian-kondisi`, sebab tiap dinas punya istilah sendiri. Yang tersimpan pada `penilaian_sp.status` tetap nilai enum di atas; yang berubah hanya teks tampilnya.

**Jumlahnya tetap tiga dan tidak dapat ditambah maupun dihapus.** `StatusKondisiSp::dariSkor()` hanya mengembalikan tiga keluaran, sehingga status keempat tidak akan pernah tercapai satuan permukiman mana pun. Warna juga tidak ikut disunting: hijau, kuning, dan merah menyatakan urutan keparahan, bukan selera.

**Ambang wajib menurun**, dan ambang status terendah terkunci pada 0 sebagai penampung sisa. Bila urutannya terbalik, status tengah tidak akan pernah tercapai sebab pembacaan berhenti pada ambang tertinggi yang cocok lebih dulu.

Larangan istilah merendahkan berlaku atas **nilai bawaan**; wording hasil suntingan dinas tidak diperiksa sistem.

### 11.31 Sumber nilai parameter penilaian

`Infrastruktur` · `Fasilitas`

Menentukan tabel mana yang dibaca untuk menilai sebuah parameter: `infrastruktur` atau `fasilitas_sp`.

### 11.32 Jenis fasilitas SP

`Kesehatan` · `Pendidikan Dasar` · `Pendidikan Lanjutan` · `Ibadah` · `Balai Pertemuan` · `Pasar atau Kios` · `Olahraga` · `Keamanan` · `Lainnya`

Enum ini diperlukan agar penilaian kondisi SP dapat menghitung otomatis. Nama spesifik tetap dicatat pada kolom `nama_fasilitas` (4.2).

### 11.33 Nilai kondisi pada penilaian SP

`Baik` = 1,0 · `Rusak Ringan` = 0,5 · `Rusak Berat` = 0,2 · `Hilang` = 0 · `Tidak Ada` = 0

`Tidak Ada` **bukan** nilai enum tersendiri pada tabel `infrastruktur` maupun `fasilitas_sp`, melainkan keadaan ketika tidak ditemukan satu pun aset yang bersesuaian. Ketiadaan dan kerusakan wajib dibedakan karena berbeda penanganannya: yang satu memerlukan pembangunan, yang lain perbaikan (`rules.md` 10c.4 poin 9).

`Hilang` (§11.5) bernilai **sama dengan** `Tidak Ada`, yaitu 0, dan itu disengaja. Aset yang lenyap tidak melayani siapa pun, persis seperti aset yang tidak pernah ada. Nilainya wajib tepat 0, bukan sekadar lebih kecil daripada `Rusak Berat`: aturan primer nol membandingkan **tepat** terhadap konstanta `NILAI_TIDAK_ADA`, sehingga skor 0,1 akan membuat satu-satunya sumur bor yang hilang gagal menjatuhkan status SP dan kehilangan itu lolos sebagai `Berkembang`.

### 11.33a Status panen

`Belum Dipanen` · `Selesai Dipanen`

**Bukan kolom database.** Diturunkan dari ada tidaknya catatan panen milik satu penanaman, lihat §9.2. Ditambahkan 2026-08-24 agar petugas dapat menemukan penanaman mana yang masih menunggu panen tanpa membuka satu per satu.

Warna badge: Belum Dipanen `gray` · Selesai Dipanen `success`.

**DUA NILAI, bukan tiga.** Nilai `Dipanen Sebagian` sempat ada pada hari yang sama lalu dicabut bersama panen bertahap (`rules.md` §9.9a): satu panen kini selalu menutup seluruh luas yang ditanam, sehingga keadaan setengah tidak lagi mungkin.

**Puso tidak menjadi nilai ketiga.** Penanaman yang gagal total berstatus `Selesai Dipanen`; pembedanya kolom `puso` pada §9.3.

**Daftar Hasil Panen tidak lagi memiliki penyaring status.** Setiap barisnya pasti berasal dari penanaman yang sudah selesai dipanen — sebab barisnya sendiri yang menuntaskannya — sehingga penyaring dengan satu-satunya nilai yang mungkin tidak menyaring apa pun (`ui-spec.md` R-26). Daftar Penanaman tetap memilikinya.

### 11.34 Asal wakil poktan

`Kepala Keluarga` · `Anggota Keluarga` · `Bukan Transmigran`

Dipakai bersama oleh `poktan.asal_ketua` dan `anggota_poktan.asal_wakil`. Menggantikan `poktan.is_ketua_transmigran` bertipe boolean, sebab keadaan lapangan ada tiga sedangkan boolean hanya sanggup membedakan dua.

**Anggota poktan hanya boleh memakai dua nilai pertama**; seluruh anggota wajib berasal dari keluarga transmigran (`rules.md` 7a poin 3). Nilai ketiga khusus ketua. Pembatasannya ditegakkan aplikasi, bukan ENUM database, agar satu tipe dapat dipakai kedua tabel.

Pemeriksaan "apakah identitasnya dapat dibaca lewat relasi" **dilarang** membandingkan nilai teks; pakai `AsalWakilPoktan::identitasDariRelasi()` dan `dariKeluargaTransmigran()`.

### 11.35 Hubungan dengan kepala keluarga

`Istri/Suami` · `Anak` · `Menantu` · `Lainnya`

> **DITINGGALKAN 2026-08-28 (Rombongan B).** Ketiga pemakainya
> (`anggota_poktan.hubungan_dengan_kk`, `poktan.hubungan_ketua`,
> `riwayat_kepala_keluarga.hubungan_pengganti`) kini membaca hubungan dari
> baris `anggota_keluarga` (§11.39), sebab wakil, ketua, dan pengganti sama-sama
> dipilih dari daftar itu. Enum ini dipertahankan hanya untuk membaca data lama.

Dahulu: diisi bila wakil keluarga di poktan bukan kepala keluarganya sendiri. Sengaja kasar dan tidak dirinci, sebab sistem belum mendata anggota keluarga satu per satu (`erd.md` §7.4, kini dibalik).

### 11.37 Jenis daftar pilihan

`sumber_dana` - `status_penyerahan` - `kondisi` - `kondisi_rumah` - `status_hunian` - `tipe_komoditas` - `prioritas_pengaduan` - `jabatan_anggota_poktan` - `jenis_infrastruktur` - `jenis_fasilitas` - `bidang_pengaduan` - `kategori_pengaduan` - `jenis_alsintan` - `jenis_inventaris`

*(`kualitas_panen` dicabut — kualitas panen dihapus, `rules.md` §9. `jenis_alsintan` ditambahkan Putaran 7; `jenis_inventaris` ditambahkan Revisi 2026-08-30 untuk modul `inventaris_sp`.)*

Menyatakan daftar mana saja yang dikelola Admin lewat data master daftar pilihan (5.6). **Enum ini sendiri tidak ikut menjadi data**, sebab ia menyatakan daftar mana yang ada, bukan isi daftarnya.

Setiap nilai di sini **wajib punya kolom yang membacanya**. Menambah satu nilai karena itu selalu berpasangan dengan menyunting kolom pada kamus data; tanpa itu, daftar yang dikelolanya tidak pernah tampil di mana pun.

Pemeriksaan "apakah jenis ini berskor" dan "apakah urutannya bermakna" **dilarang** membandingkan nilai teks; pakai `JenisReferensi::berskor()` dan `berjenjang()`.

### 11.36 Alasan pergantian kepala keluarga

`Meninggal` · `Pindah atau Merantau` · `Cerai` · `Lainnya`

**Bukan pengganti status tinggal (11.8).** Keduanya menjawab pertanyaan berbeda: status tinggal menyatakan keadaan terkini sebuah **keluarga**, sedangkan enum ini merekam satu **peristiwa bertanggal**. Ketika kepala keluarga meninggal lalu istrinya menggantikan, keluarganya tetap berstatus `Aktif` sebab istrinya masih hidup dan menempati rumah yang sama; kematian itu hanya terekam di sini.

Konsekuensi yang perlu disadari saat membaca dashboard: nilai `Meninggal` pada status tinggal hanya menghitung **keluarga yang bubar**, bukan orang yang meninggal. Angka kematian yang sesungguhnya dihitung dari tabel `riwayat_kepala_keluarga`.

`Pindah atau Merantau` sengaja tidak dipecah dua. Dari sisi pendataan keduanya sama: kepala keluarga tidak lagi berada di kawasan sementara keluarganya tetap tinggal. Membedakannya menuntut petugas menilai niat kepergian, dan itu tidak dapat diverifikasi.

### 11.38 Agama

`Islam` · `Kristen` · `Katolik` · `Hindu` · `Buddha` · `Konghucu`

Ditambahkan 2026-08-28 (Rombongan B). Enam agama yang dilayani pencatatan sipil dan tercetak pada KTP serta kartu keluarga. Dipakai `transmigran.agama` dan `anggota_keluarga.agama`.

**Bukan data master.** Keenamnya baku dari Dukcapil dan tidak di-CRUD dinas (keputusan pemilik proyek). "Penghayat Kepercayaan terhadap Tuhan YME" sengaja belum diikutkan; bila dinas memerlukannya cukup satu case ditambahkan.

### 11.39 Hubungan anggota keluarga

`Istri` · `Suami` · `Anak` · `Anak Angkat` · `Orang Tua` · `Famili Lain`

Dipakai `anggota_keluarga.hubungan`. **BEDA dari §11.35** (`HubunganKeluarga`), yang "sengaja kasar" dan dipakai `anggota_poktan` serta `riwayat_kepala_keluarga` saat sistem belum mendata anggota keluarga. Enum ini dipakai tabel yang memang mendata mereka satu per satu, sehingga boleh lebih rinci.

Pasangan dipisah `Istri`/`Suami` agar jenis kelaminnya tersirat dari hubungan, dan agar suksesi kepala keluarga dapat menawarkan pasangan sebagai calon pengganti pertama. Tidak dirinci sampai "anak kedua": urutan kelahiran tidak dipakai perhitungan mana pun.

### 11.40 Kegiatan anggota keluarga

`Belum Sekolah` · `Masih Sekolah` · `Bekerja` · `Tidak Bekerja`

Dipakai `anggota_keluarga.kegiatan`. Menggantikan pilihan "Pendidikan/Kerja" bercabang pada form. Isian yang menyusul:

| Nilai | Isian tambahan |
|---|---|
| `Belum Sekolah` | tidak ada (balita) |
| `Masih Sekolah` | `pendidikan_terakhir` sebagai jenjang yang sedang ditempuh |
| `Bekerja` | `pendidikan_terakhir` + `pekerjaan` + `pendapatan_per_bulan` |
| `Tidak Bekerja` | `pendidikan_terakhir` saja |

### 11.41 Pola permukiman

`Konsentris` · `Papan Catur` · `Linear` · `Menyebar`

Dipakai `satuan_permukiman.pola_permukiman`. Bab II sub-bagian 3.2 Laporan Monografi (Rombongan C, 2026-08-28). "Konsentris" berarti permukiman terpusat lalu dikelilingi lahan pekarangan dan lahan usaha.

### 11.42 Tingkat kesuburan tanah

`Subur` · `Sedang` · `Kurang Subur`

Dipakai `satuan_permukiman.tingkat_kesuburan_tanah`. Bab II sub-bagian 4. Kisaran pH disimpan terpisah pada `ph_tanah_min` / `ph_tanah_maks`.

### 11.43 Bentuk wilayah

`Datar` · `Bergelombang` · `Berbukit` · `Bergunung`

Dipakai `satuan_permukiman.bentuk_wilayah`. Bab II sub-bagian 5 (topografi). Persentase kemiringan lereng disimpan terpisah pada `kemiringan_min_persen` / `kemiringan_maks_persen`.

### 11.44 Status anggota keluarga

`Aktif` · `Meninggal` · `Pindah`

Enum `App\Enums\StatusAnggotaKeluarga`. Dipakai `anggota_keluarga.status` (Putaran 6, 2026-08-29). Warna badge: `Aktif` success, `Meninggal` gray, `Pindah` warning. `opsiPeristiwa()` mengembalikan dua nilai tanpa `Aktif` untuk form pencatatan.

**Berbeda dari dua enum yang mirip:**
- `StatusTinggal` (§11.8) menyatakan keadaan sebuah **keluarga**, tidak punya nilai `Meninggal`.
- `AlasanPergantianKK` (§11.36) merekam **peristiwa suksesi kepala keluarga** pada riwayat.
- `StatusAnggotaKeluarga` hanya untuk anggota **non-kepala**, yang tidak membawa rumah/lahan/poktan sehingga barisnya aman ditandai per orang.

Metode turunan Monografi: `DummyData::strukturUmurSp($id)` (14 kelompok umur × L/P, Σ = `jiwaPerSp($id)`), `DummyData::mutasiPendudukSp($id)` (mutasi kumulatif sejak `tahun_penempatan`, tanpa perkawinan), `DummyData::jiwaPerSp()` (porsi KK × `ringkasanDashboard()['jumlah_jiwa']`). Semuanya angka contoh turunan deterministik.

---

## 12. Aturan Validasi Bersama

Aturan berikut ditulis satu kali di `app/Support/ValidationRules.php` dan dipakai ulang oleh seluruh form. Dilarang menulis ulang regex di tiap form (`rules.md` §13.2 poin 6).

| Field | Aturan | Pesan galat (Bahasa Indonesia) |
|---|---|---|
| `nik` | wajib, tepat 16 digit angka, unik | "NIK harus 16 digit angka." / "NIK ini sudah terdaftar." |
| `no_kk` | wajib, tepat 16 digit angka, unik | "Nomor KK harus 16 digit angka." |
| `nama` | wajib, 3–255 karakter, hanya huruf, spasi, titik, dan apostrof | "Nama hanya boleh berisi huruf." |
| `telepon` | opsional, 10–15 digit, diawali `08` atau `+62` | "Nomor telepon tidak valid." |
| `email` | format email sah, unik pada tabel `user` | "Format email tidak valid." |
| `password` | minimal 8 karakter, mengandung huruf dan angka | "Kata sandi minimal 8 karakter dan mengandung huruf serta angka." |
| `tahun` | 4 digit, antara 1900 dan tahun berjalan | "Tahun tidak valid." |
| `luas` | angka, lebih besar dari 0, maksimal 2 desimal | "Luas harus lebih dari 0." |
| `produksi` | angka, lebih besar dari 0, maksimal 3 desimal | "Produksi harus lebih dari 0." |
| `uang` | angka bulat, minimal 0 | "Nilai tidak boleh negatif." |
| `lintang` | opsional, antara −90 dan 90 | "Lintang harus antara −90 dan 90." |
| `bujur` | opsional, antara −180 dan 180 | "Bujur harus antara −180 dan 180." |
| `dokumen` | maksimal 5 MB, tipe jpg/jpeg/png/webp/pdf | "Ukuran berkas maksimal 5 MB." |

**Aturan lintas-field yang divalidasi di sisi aplikasi:**

| # | Aturan | Tabel |
|---|---|---|
| 1 | `alasan_tidak_dihuni` wajib bila `status_hunian` = Tidak Dihuni | `rumah` |
| 2 | `tanggal_keluar` wajib bila `status` = Sudah Keluar | `anggota_poktan` |
| 3 | `tanggal_keluar` tidak boleh mendahului `tanggal_masuk` | `anggota_poktan`, `riwayat_penghunian` |
| 4 | Σ `alsintan_distribusi.jumlah` ≤ `alsintan.jumlah_total`; tiap distribusi berpoktan (Putaran 7) | `alsintan`, `alsintan_distribusi` |
| 5 | Σ `saprotan_distribusi.jumlah` ≤ `saprotan.jumlah_total`; tiap distribusi berpoktan (Putaran 7) | `saprotan`, `saprotan_distribusi` |
| 6 | `luas_kering` dan `luas_basah` wajib bila peruntukannya lahan usaha, dan jumlah keduanya sama dengan `luas` | `lahan` |
| 7 | Pilihan rumah hanya menampilkan baris dengan `transmigran_id` bernilai `NULL` | `rumah` |
| 8 | Perubahan status pengaduan wajib mengikuti urutan yang ditetapkan | `pengaduan` |
| 9 | `saprotan_distribusi`/`alsintan_distribusi` SP turunan wajib sama dengan SP poktannya (Putaran 7) | `saprotan_distribusi`, `alsintan_distribusi` |
| 10 | `periode_panen` tidak boleh mendahului `periode_tanam` pada penanaman terkait | `hasil_panen` |
| 11 | `email` dan `username` wajib terisi dan unik antar-akun | `user` |
| 12 | `username` hanya boleh huruf kecil, angka, titik, dan garis bawah, panjang 3 sampai 50 karakter | `user` |
| 13 | Akun berrole bercakupan `Per SP` wajib memiliki minimal satu penugasan SP | `user_satuan_permukiman` |
| 14 | Admin tidak boleh menonaktifkan atau menghapus akun Admin terakhir yang masih aktif | `user` |
| 15 | Role bertanda `is_bawaan` tidak dapat dihapus | `role` |
| 16 | Role bertanda `is_terkunci` tidak dapat diubah kewenangannya | `role` |
| 17 | Role yang masih dipakai minimal satu akun tidak dapat dihapus | `role` |
| 19 | `nama_pelapor` dan `kontak_pelapor` wajib pada seluruh pengaduan, baik publik maupun dicatat petugas | `pengaduan` |
| 20 | `user_id` wajib kosong bila `sumber_laporan` bernilai Publik, dan wajib terisi bila Petugas | `pengaduan` |
| 21 | Pengaduan publik dibatasi 3 laporan per jam untuk setiap alamat IP | `pengaduan` |
| 22 | `bidang` wajib terisi sebelum status pengaduan maju ke `Diproses` | `pengaduan` |
| 23 | `anggota_keluarga_id` wajib bila `asal_wakil` = `Anggota Keluarga`, dan wajib `NULL` bila `Kepala Keluarga`; anggota yang ditunjuk wajib milik keluarga `transmigran_id`. Kolom `nama_wakil`/`nik_wakil`/`hubungan_dengan_kk` dicabut 2026-08-28 (Stage B2) | `anggota_poktan` |
| 24 | `asal_wakil` tidak boleh bernilai `Bukan Transmigran`; seluruh anggota wajib berasal dari keluarga transmigran | `anggota_poktan` |
| 25 | `ketua_transmigran_id` wajib bila `asal_ketua` bukan `Bukan Transmigran`, dan wajib `NULL` bila `Bukan Transmigran` | `poktan` |
| 26 | `ketua_anggota_keluarga_id` wajib bila `asal_ketua` = `Anggota Keluarga` (selain itu `NULL`); `nama_ketua`/`nik_ketua` wajib **hanya** bila `asal_ketua` = `Bukan Transmigran` (selain itu `NULL`). `hubungan_ketua` dicabut 2026-08-28 (Stage B2) | `poktan` |
| 27 | `luas_kering_ketua` dan `luas_basah_ketua` hanya terisi bila `asal_ketua` = `Bukan Transmigran`; selain itu diturunkan dari lahan keluarga | `poktan` |
| 28 | `nik_baru` tidak boleh sama dengan `nik_lama` pada baris yang sama | `riwayat_kepala_keluarga` |
| 29 | `tanggal_pergantian` tidak boleh mendahului `tahun_kedatangan` keluarganya, dan tidak boleh melampaui hari ini | `riwayat_kepala_keluarga` |
| 30 | Suksesi wajib menyetel ulang `poktan.ketua_transmigran_id` bila keluarga tersebut menjabat ketua lewat jalur `Kepala Keluarga`; jabatan ketua tidak diwariskan | `poktan` |
| 31 | `komoditas_id` wajib bila `jenis` = Benih, dan wajib `NULL` bagi jenis lain | `saprotan` |
| 32 | `saprotan_distribusi_id` dan `volume_benih` **wajib terisi**, termasuk bibit swadaya bersumber `Swadaya` (Putaran 7: dulu `saprotan_id`) | `penanaman` |
| 33 | `saprotan_distribusi_id` hanya boleh menunjuk baris distribusi berjenis Benih milik `poktan_id` yang sama (Putaran 7: dulu `saprotan_id`) | `penanaman`, `saprotan_distribusi` |
| 34 | Komoditas benih wajib sama dengan `komoditas_id` penanamannya | `penanaman`, `saprotan` |
| 35 | Σ `volume_benih` penanaman per baris distribusi tidak boleh melebihi `saprotan_distribusi.jumlah` (Putaran 7: grain turun ke distribusi) | `penanaman`, `saprotan_distribusi` |
| 36 | `realisasi_tanam` tidak boleh melebihi luas lahan poktannya | `penanaman`, `poktan` |
| 37 | Lahan yang penanamannya sudah tuntas dipanen kembali tersedia untuk penanaman berikutnya | `penanaman`, `hasil_panen` |
| 38 | `penanaman_id` wajib terisi; komoditas dan poktannya wajib sejalan dengan penanamannya | `hasil_panen` |
| 39 | `realisasi_panen` + `puso` wajib **tepat** sama dengan `penanaman.realisasi_tanam`; satu penanaman hanya boleh punya satu baris panen | `hasil_panen`, `penanaman` |
| 40 | `produksi` wajib sama dengan `realisasi_panen` dikali `produktivitas` | `hasil_panen` |
| 41 | `periode_panen` tidak boleh mendahului `periode_tanam` penanamannya | `hasil_panen`, `penanaman` |

---

## 13. Daftar Kewenangan (Permission)

Kewenangan ditanam sistem lewat seeder dan **tidak dapat ditambah atau dihapus Admin**, karena setiap kewenangan harus memiliki pasangan pemeriksa di dalam kode. Admin hanya memasangkannya ke role lewat `role_permission`.

Penamaan memakai pola `modul.aksi`, contoh `transmigran.lihat`.

### 13.1 Aksi yang tersedia per fitur

Tanda centang berarti kewenangan tersebut dibuat untuk fitur bersangkutan.

| Fitur | lihat | tambah | ubah | hapus |
|---|:---:|:---:|:---:|:---:|
| `pengguna` | v | v | v |   |
| `role` | v | v | v | v |
| `audit_log` | v |   |   |   |
| `wilayah` | v | v | v | v |
| `kawasan` | v | v | v | v |
| `sp` | v | v | v | v |
| `inventaris_sp` | v | v | v | v |
| `fasilitas_sp` | v | v | v | v |
| `satuan` | v | v | v | v |
| `transmigran` | v | v | v | v |
| `rumah` | v | v | v | v |
| `riwayat_penghunian` | v | v | v | v |
| `riwayat_kepala_keluarga` | v | v | v | |
| `daftar_pilihan` | v | v | v | |
| `penilaian_kondisi` | v |   | v |   |
| `lahan` | v | v | v | v |
| `poktan` | v | v | v | v |
| `anggota_poktan` | v | v | v |   |
| `alsintan` | v | v | v | v |
| `saprotan` | v | v | v | v |
| `komoditas` | v | v | v | v |
| `penanaman` | v | v | v | v |
| `hasil_panen` | v | v | v | v |
| `infrastruktur` | v | v | v | v |
| `pengaduan` | v | v | v | v |
| `penanganan_pengaduan` | v | v | v |   |
| `cms` | v |   | v |   |
| `dashboard` | v |   |   |   |

Total **97 kewenangan** dari 28 fitur, dihitung dari tabel di atas. Naik dari 95 pada 2026-09-03 (Task 3.3), ketika modul `cms` (Pengelolaan Konten) diberi kewenangannya sendiri `cms.lihat` + `cms.ubah`, dipegang Admin dan Dinas Transmigrasi. Sebelumnya turun dari 99 pada 2026-09-02 (Putaran 15) saat `dokumen_lahan` dicabut, dan dari 101 pada 2026-08-22 saat `musim_tanam` dicabut.

Jumlah kewenangan yang benar-benar dipegang tiap role bawaan lebih sedikit, sesuai susunan pada `rules.md` 5.1: Admin 97, Dinas Transmigrasi 49, Dinas Pertanian 44, Operator SP 49.

### 13.2 Kelompok fitur pada antarmuka

Agar halaman pengaturan role mudah dibaca, kewenangan dikelompokkan sesuai struktur menu:

| Kelompok | Fitur |
|---|---|
| Sistem | `pengguna`, `role`, `audit_log`, `cms` |
| Wilayah dan SP | `wilayah`, `kawasan`, `sp`, `inventaris_sp`, `fasilitas_sp`, `satuan`, `daftar_pilihan`, `penilaian_kondisi` |
| Kependudukan | `transmigran`, `rumah`, `riwayat_penghunian`, `riwayat_kepala_keluarga` |
| Lahan | `lahan` |
| Kelembagaan | `poktan`, `anggota_poktan`, `alsintan`, `saprotan` |
| Pertanian | `komoditas`, `penanaman`, `hasil_panen` |
| Infrastruktur | `infrastruktur` |
| Pengaduan | `pengaduan`, `penanganan_pengaduan` |
| Pemantauan | `dashboard` |

### 13.3 Aturan pemeriksaan kewenangan

1. Pemeriksaan wajib dilakukan pada **level query dan controller**, bukan sekadar menyembunyikan menu (`rules.md` §5).
2. Menu sidebar dirender hanya bila pengguna memiliki kewenangan `lihat` pada fitur bersangkutan. Menu yang tidak berhak **tidak dirender sama sekali**.
3. Tombol aksi (Tambah, Ubah, Hapus, Export) dirender hanya bila kewenangan terkait dimiliki.
4. Kewenangan `lihat` adalah prasyarat seluruh aksi lain pada fitur yang sama. Memberi kewenangan `ubah` tanpa `lihat` dianggap galat konfigurasi dan ditolak sistem.
5. Setiap perubahan susunan kewenangan sebuah role wajib tercatat pada `audit_log`.
