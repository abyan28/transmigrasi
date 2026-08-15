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
5. [Domain Master Referensi](#5-domain-master-referensi)
6. [Domain Kependudukan](#6-domain-kependudukan)
7. [Domain Lahan](#7-domain-lahan)
8. [Domain Kelembagaan dan Sarana](#8-domain-kelembagaan-dan-sarana)
9. [Domain Produksi Pertanian](#9-domain-produksi-pertanian)
10. [Domain Infrastruktur dan Pengaduan](#10-domain-infrastruktur-dan-pengaduan)
11. [Daftar Nilai Enum Terpusat](#11-daftar-nilai-enum-terpusat)
12. [Aturan Validasi Bersama](#12-aturan-validasi-bersama)
13. [Daftar Izin (Permission)](#13-daftar-izin-permission)

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

Tabel referensi wilayah (`provinsi`, `kabupaten`, `kecamatan`, `desa`), `satuan`, `musim_tanam`, `riwayat_penghunian`, `riwayat_tanam`, `penanganan_pengaduan`, dan `audit_log` **tidak** memakai soft delete: tabel referensi dilindungi `RESTRICT`, sedangkan tabel riwayat memang tidak boleh dihapus.

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
| `role_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | Menunjuk role beserta izin dan cakupan datanya |
| `nama` | `VARCHAR(255)` | TIDAK | | Nama lengkap pengguna |
| `username` | `VARCHAR(50)` | TIDAK | UQ, IDX | Kredensial login alternatif, huruf kecil tanpa spasi |
| `email` | `VARCHAR(255)` | TIDAK | UQ, IDX | Kredensial login utama |
| `password` | `VARCHAR(255)` | TIDAK | | Hash bcrypt, tidak pernah ditampilkan |
| `password_harus_diganti` | `BOOLEAN` | TIDAK | | Bawaan `FALSE`. Bernilai `TRUE` setelah Admin menyetel ulang kata sandi, memaksa penggantian saat login berikutnya |
| `telepon` | `VARCHAR(20)` | YA | | Format `08xxxxxxxxxx` |
| `jabatan` | `VARCHAR(100)` | YA | | Jabatan pada instansi, contoh "Staf Bidang Ketransmigrasian" |
| `foto` | `VARCHAR(255)` | YA | | Path foto profil |
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
| `is_terkunci` | `BOOLEAN` | TIDAK | | `TRUE` hanya untuk role Admin; izinnya tidak dapat diubah |
| `is_aktif` | `BOOLEAN` | TIDAK | | Role nonaktif tidak dapat dipilih saat membuat akun baru |

**Catatan:**
- Role Admin memiliki `is_bawaan = TRUE` dan `is_terkunci = TRUE`, sehingga tidak dapat dihapus maupun dikurangi izinnya. Ini menjamin sistem tidak pernah kehilangan jalur administrasi.
- Role yang masih dipakai minimal satu akun tidak dapat dihapus. Aturan hapus FK memakai `RESTRICT`.

### 2.1b `permission`

Daftar izin baku yang ditanam sistem lewat seeder. **Admin tidak dapat menambah atau menghapus izin,** karena setiap izin harus memiliki pasangan pemeriksa di dalam kode. Admin hanya memasangkannya ke role.

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_permission` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `nama` | `VARCHAR(100)` | TIDAK | UQ | Format `modul.aksi`, contoh `transmigran.lihat` |
| `modul` | `VARCHAR(50)` | TIDAK | IDX | Pengelompokan pada antarmuka pengaturan role |
| `aksi` | `ENUM` | TIDAK | | Lihat §11.26 |
| `label` | `VARCHAR(150)` | TIDAK | | Teks Bahasa Indonesia yang tampil di antarmuka |
| `urutan` | `SMALLINT UNSIGNED` | TIDAK | | Urutan tampil dalam kelompok modulnya |

Daftar lengkap izin ada pada §13.

### 2.1c `role_permission`

Tabel pivot penghubung role dan izin.

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
| `dokumen_pendukung` | `VARCHAR(255)` | YA | | Path SK penetapan atau peta kawasan |
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
| `batas_utara` | `VARCHAR(255)` | YA | | Deskripsi batas wilayah |
| `batas_timur` | `VARCHAR(255)` | YA | | Deskripsi batas wilayah |
| `batas_selatan` | `VARCHAR(255)` | YA | | Deskripsi batas wilayah |
| `batas_barat` | `VARCHAR(255)` | YA | | Deskripsi batas wilayah |
| `dokumen_pendukung` | `VARCHAR(255)` | YA | | Path berkas |
| `keterangan` | `TEXT` | YA | | Catatan bebas |

**Catatan:**
- Empat kolom `batas_*` menggantikan tabel `koordinat_lokasi_sp` pada SQL referensi. Isinya deskripsi seperti "Berbatasan dengan Desa Naet", bukan koordinat (`erd.md` §8.2 nomor 17).
- **Kolom `kecamatan_id` sengaja tidak ada.** Kecamatan dibaca lewat rantai `desa_id → desa → kecamatan`. Menyimpannya secara terpisah membuka peluang data tidak sinkron bila desa berpindah kecamatan.
- SP menyimpan **dua** foreign key wilayah yang saling melengkapi: `kawasan_id` menjawab "bagian dari program mana", `desa_id` menjawab "berdiri di wilayah administratif mana". Keduanya wajib diisi.

---

## 4. Domain Aset SP

### 4.1 `inventaris_sp`

Barang bergerak milik SP (`rules.md` §4b).

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_inventaris_sp` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `satuan_permukiman_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | |
| `nama_barang` | `VARCHAR(255)` | TIDAK | | |
| `jumlah` | `INT UNSIGNED` | TIDAK | | Bawaan 1 |
| `satuan_barang` | `VARCHAR(50)` | YA | | Teks bebas: unit, buah, set |
| `tahun_perolehan` | `YEAR` | YA | | |
| `sumber_dana` | `ENUM` | YA | | Lihat §11.3 |
| `status_penyerahan` | `ENUM` | TIDAK | | Lihat §11.4 |
| `kondisi` | `ENUM` | YA | | Lihat §11.5 |
| `dokumen_pendukung` | `VARCHAR(255)` | YA | | |
| `keterangan` | `TEXT` | YA | | |

**Catatan:** `satuan_barang` sengaja berupa teks bebas dan **tidak** menaut ke tabel `satuan`, karena tabel `satuan` khusus menyimpan satuan berat beserta faktor konversi ke ton.

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
| `kondisi` | `ENUM` | YA | | Lihat §11.5 |
| `lintang` | `DECIMAL(10,7)` | YA | | Lokasi fasilitas |
| `bujur` | `DECIMAL(10,7)` | YA | | Lokasi fasilitas |
| `dokumen_pendukung` | `VARCHAR(255)` | YA | | |
| `keterangan` | `TEXT` | YA | | |

**Catatan:** `jenis_fasilitas` dan `nama_fasilitas` sengaja berdampingan. Enum diperlukan agar penilaian kondisi SP dapat menghitung otomatis, sebab teks bebas membuat "SEKOLAH DASAR" dan "SD Negeri 1" tidak terbaca sebagai hal yang sama. Nama bebas tetap dipertahankan agar petugas dapat menulis sebutan yang dikenal warga setempat.

---

## 5. Domain Master Referensi

### 5.1 `satuan`

Satuan berat beserta faktor konversi ke ton (`rules.md` §8a).

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_satuan` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `nama` | `VARCHAR(50)` | TIDAK | UQ | Ton, Kuintal, Kilogram |
| `simbol` | `VARCHAR(10)` | TIDAK | | t, kw, kg |
| `faktor_ke_ton` | `DECIMAL(10,6)` | TIDAK | | Ton = 1; Kuintal = 0,1; Kilogram = 0,001 |

**Catatan:** satuan lokal seperti karung dan ikat **tidak** dimasukkan ke tabel ini karena beratnya tidak baku. Satuan lokal dicatat pada kolom `keterangan_satuan_lokal` di `hasil_panen` (`rules.md` §8a.6).

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

### 5.3 `musim_tanam`

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_musim_tanam` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `nama` | `VARCHAR(50)` | TIDAK | UQ¹ | MT1, MT2 |
| `tahun` | `YEAR` | TIDAK | UQ¹, IDX | Pemisah antar-tahun |
| `tanggal_mulai` | `DATE` | YA | | |
| `tanggal_selesai` | `DATE` | YA | | |
| `keterangan` | `TEXT` | YA | | |

¹ UNIQUE gabungan `(nama, tahun)`, sehingga "MT1 2026" dan "MT1 2027" adalah dua baris berbeda.

**Catatan:** SQL referensi hanya menyediakan kolom `keterangan` bertipe teks. Kolom `tahun` ditambahkan karena grafik volume panen per tahun mustahil dihitung dari teks bebas (`erd.md` §8.2 nomor 22).

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
| `bobot` | `TINYINT UNSIGNED` | TIDAK | | Bawaan mengikuti tingkat: Primer 5, Sekunder 3, Tersier 1 |
| `sumber` | `ENUM` | TIDAK | | Lihat 11.31; menentukan tabel mana yang dibaca |
| `jenis_rujukan` | `VARCHAR(50)` | TIDAK | | Nilai enum yang dicari pada tabel sumber, contoh `Air` atau `Kesehatan` |
| `is_aktif` | `BOOLEAN` | TIDAK | | Parameter nonaktif tidak ikut dihitung pada penilaian baru |
| `urutan` | `SMALLINT UNSIGNED` | TIDAK | | Urutan tampil pada halaman pengaturan dan rincian skor |

**Catatan:**
- Parameter **dinonaktifkan, bukan dihapus**, agar riwayat penilaian yang memakainya tetap dapat dibaca.
- `sumber` dan `jenis_rujukan` menjelaskan dari mana nilai kondisi diambil: parameter `air_bersih` membaca `infrastruktur` berjenis `Air`, sedangkan `kesehatan` membaca `fasilitas_sp` berjenis `Kesehatan`.

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
| `tempat_lahir` | `VARCHAR(100)` | YA | | |
| `tanggal_lahir` | `DATE` | YA | | |
| `pendidikan_terakhir` | `ENUM` | YA | | Lihat §11.7 |
| `pekerjaan_kepala_keluarga` | `VARCHAR(100)` | TIDAK | IDX | Sumber histogram dashboard |
| `jumlah_anggota_keluarga` | `TINYINT UNSIGNED` | TIDAK | | Termasuk kepala keluarga |
| `pendapatan_per_bulan` | `DECIMAL(15,2)` | YA | | Rupiah |
| `daerah_asal` | `VARCHAR(255)` | YA | | Kabupaten/provinsi asal |
| `tahun_kedatangan` | `YEAR` | TIDAK | IDX | Dasar grafik jumlah transmigran per tahun |
| `status_tinggal` | `ENUM` | TIDAK | IDX | Lihat §11.8 |
| `status_anggota_poktan` | `ENUM` | TIDAK | | Ya, Tidak |
| `telepon` | `VARCHAR(20)` | YA | | |
| `dokumen_pendukung` | `VARCHAR(255)` | YA | | KTP, KK, SK penempatan |
| `keterangan` | `TEXT` | YA | | |

**Catatan:**
- `pekerjaan_kepala_keluarga` sengaja berupa `VARCHAR` bukan `ENUM`, karena ragam pekerjaan di lapangan sulit dibatasi di muka. Konsistensi dijaga lewat daftar saran pada antarmuka.
- `tahun_kedatangan` wajib diisi karena menjadi sumbu grafik dashboard PRD §7.8.
- `status_anggota_poktan` disimpan sebagai penanda cepat; kebenarannya tetap mengacu ke `anggota_poktan`.

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
| `foto_rumah` | `VARCHAR(255)` | YA | | |
| `catatan_hunian` | `TEXT` | YA | | Termasuk catatan rumah ditinggal sementara |
| `dokumen_pendukung` | `VARCHAR(255)` | YA | | |

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

---

## 7. Domain Lahan

### 7.1 `lahan`

Menggabungkan `lahan_sp`, `lahan_usaha_sp`, `kategori_lahan_sp`, dan `kategori_lahan` dari SQL referensi menjadi satu tabel (`erd.md` §8.2 nomor 18).

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_lahan` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `transmigran_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | Pemilik; satu transmigran boleh punya banyak lahan |
| `satuan_permukiman_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | |
| `poktan_id` | `BIGINT UNSIGNED` | YA | FK | Poktan pengelola bila ada |
| `kode_lahan` | `VARCHAR(50)` | YA | UQ | Identitas lahan (`rules.md` §7.1) |
| `jenis_lahan` | `ENUM` | TIDAK | IDX | Lihat §11.11 |
| `kategori_lahan` | `ENUM` | YA | | Lihat §11.12; hanya untuk Lahan Usaha |
| `luas` | `DECIMAL(12,2)` | TIDAK | | Hektare |
| `status_kepemilikan` | `ENUM` | YA | | Lihat §11.13 |
| `tujuan_pemanfaatan` | `TEXT` | YA | | |
| `lintang` | `DECIMAL(10,7)` | YA | | |
| `bujur` | `DECIMAL(10,7)` | YA | | |
| `pola_tanam` | `VARCHAR(255)` | YA | | Khusus lahan usaha: monokultur, tumpang sari |
| `peralatan_pertanian` | `TEXT` | YA | | Khusus lahan usaha |
| `kendala` | `TEXT` | YA | | Khusus lahan usaha |
| `keterangan` | `TEXT` | YA | | |

**Catatan:**
- FK berada di tabel ini, **bukan** di `transmigran`, karena satu transmigran dapat memiliki lebih dari satu lahan usaha (`rules.md` §7.8).
- Empat kolom terakhir sebelum `keterangan` hanya relevan bila `jenis_lahan` = Lahan Usaha; untuk lahan pekarangan dibiarkan `NULL`.
- Rekap luas lahan **wajib** memakai `SUM(luas)`, bukan mengambil satu baris (`rules.md` §7.10).

### 7.2 `dokumen_lahan`

Dokumen status lahan (HPL/SHM) dipisah ke tabel sendiri karena satu lahan dapat memiliki lebih dari satu dokumen.

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_dokumen_lahan` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `lahan_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | |
| `jenis_dokumen` | `ENUM` | TIDAK | | Lihat §11.14 |
| `nomor_dokumen` | `VARCHAR(100)` | YA | | |
| `tanggal_terbit` | `DATE` | YA | | |
| `file_dokumen` | `VARCHAR(255)` | TIDAK | | Path berkas |
| `keterangan` | `TEXT` | YA | | |

---

## 8. Domain Kelembagaan dan Sarana

### 8.1 `poktan`

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_poktan` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `satuan_permukiman_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | |
| `ketua_transmigran_id` | `BIGINT UNSIGNED` | YA | FK | Menunjuk data transmigran ketua |
| `nama` | `VARCHAR(255)` | TIDAK | UQ | |
| `tanggal_berdiri` | `DATE` | YA | | |
| `telepon` | `VARCHAR(20)` | YA | | Kontak kelompok, boleh berbeda dari kontak pribadi ketua |
| `email` | `VARCHAR(255)` | YA | | Kontak kelompok |
| `alamat_sekretariat` | `VARCHAR(255)` | YA | | |
| `luas_lahan_kelompok` | `DECIMAL(12,2)` | YA | | Hektare |
| `lintang` | `DECIMAL(10,7)` | YA | | |
| `bujur` | `DECIMAL(10,7)` | YA | | |
| `dokumen_pendukung` | `VARCHAR(255)` | YA | | SK pembentukan |
| `keterangan` | `TEXT` | YA | | |

**Catatan:**
- Nama dan NIK ketua **tidak** disalin ke tabel ini; keduanya dibaca lewat relasi `ketua_transmigran_id` agar tidak ada dua versi data yang berpotensi tidak sinkron (`erd.md` §8.2 nomor 25).
- Kolom `jumlah_anggota` sengaja **tidak ada**; nilainya dihitung dari `anggota_poktan` berstatus Aktif memakai `withCount` (`erd.md` §7.3).

### 8.2 `anggota_poktan`

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_anggota_poktan` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `poktan_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX, UQ¹ | |
| `transmigran_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX, UQ¹ | |
| `jabatan` | `ENUM` | TIDAK | | Lihat §11.15 |
| `tanggal_masuk` | `DATE` | TIDAK | | |
| `status_keaktifan` | `ENUM` | TIDAK | IDX | Lihat §11.16 |
| `tanggal_keluar` | `DATE` | YA | | Wajib diisi bila status Sudah Keluar |
| `keterangan` | `TEXT` | YA | | |

¹ UNIQUE gabungan `(poktan_id, transmigran_id)`.

**Catatan:** anggota yang berhenti **tidak dihapus**, melainkan ditandai `status_keaktifan = 'Sudah Keluar'` agar riwayat tetap utuh (`rules.md` §5.1 catatan 1). Nama dan NIK anggota dibaca lewat relasi ke `transmigran`, tidak disalin.

### 8.3 `alsintan`

Alat dan mesin pertanian.

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_alsintan` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `transmigran_id` | `BIGINT UNSIGNED` | YA | FK, IDX | Diisi bila milik pribadi |
| `poktan_id` | `BIGINT UNSIGNED` | YA | FK, IDX | Diisi bila bantuan lewat poktan |
| `nama_alat` | `VARCHAR(255)` | TIDAK | | Traktor, sprayer, cultivator |
| `jumlah` | `INT UNSIGNED` | TIDAK | | Bawaan 1 |
| `tahun_perolehan` | `YEAR` | YA | IDX | |
| `kepemilikan` | `ENUM` | TIDAK | IDX | Lihat §11.17 |
| `sumber_perolehan` | `ENUM` | YA | | Lihat §11.3 |
| `kondisi` | `ENUM` | YA | | Lihat §11.5 |
| `dokumen_pendukung` | `VARCHAR(255)` | YA | | |
| `keterangan` | `TEXT` | YA | | |

**Aturan wajib:** tepat satu di antara `transmigran_id` dan `poktan_id` harus terisi, sesuai nilai `kepemilikan`. Bila `kepemilikan` = Pribadi maka `transmigran_id` wajib; bila Bantuan Poktan maka `poktan_id` wajib. Aturan ini divalidasi di sisi aplikasi karena MySQL 5.7 belum mendukung `CHECK` constraint.

### 8.4 `saprotan`

Sarana produksi pertanian: benih, pupuk, pestisida, mulsa.

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_saprotan` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `transmigran_id` | `BIGINT UNSIGNED` | YA | FK, IDX | Penerima perorangan |
| `poktan_id` | `BIGINT UNSIGNED` | YA | FK, IDX | Penerima kelompok |
| `satuan_id` | `BIGINT UNSIGNED` | TIDAK | FK | Satuan jumlah yang disalurkan |
| `jenis_saprotan` | `ENUM` | TIDAK | IDX | Lihat §11.18 |
| `nama_saprotan` | `VARCHAR(255)` | TIDAK | | Contoh: Urea, benih jagung hibrida |
| `jumlah` | `DECIMAL(12,3)` | TIDAK | | |
| `tahun_perolehan` | `YEAR` | YA | IDX | |
| `tanggal_penyaluran` | `DATE` | YA | | |
| `sumber_dana` | `ENUM` | YA | | Lihat §11.3 |
| `dokumen_pendukung` | `VARCHAR(255)` | YA | | Berita acara penyaluran |
| `keterangan` | `TEXT` | YA | | |

**Aturan wajib:** minimal satu di antara `transmigran_id` dan `poktan_id` terisi. Penyaluran kepada anggota poktan hanya diperbolehkan untuk anggota berstatus Aktif (`rules.md` §7c.4).

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

### 9.2 `riwayat_tanam`

Catatan penanaman: lahan mana, musim apa, komoditas apa.

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_riwayat_tanam` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `lahan_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX, UQ¹ | |
| `musim_tanam_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX, UQ¹ | |
| `komoditas_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX, UQ¹ | |
| `luas_tanam` | `DECIMAL(12,2)` | YA | | Hektare; bisa lebih kecil dari luas lahan |
| `tanggal_tanam` | `DATE` | YA | | |
| `keterangan` | `TEXT` | YA | | |

¹ UNIQUE gabungan `(lahan_id, musim_tanam_id, komoditas_id)`.

### 9.3 `hasil_panen`

**Tabel baru**, menggantikan kolom panen yang pada SQL referensi menempel di `lahan_usaha_sp` (`erd.md` §8.2 nomor 15).

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_hasil_panen` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `riwayat_tanam_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | Menentukan lahan, musim, dan komoditas |
| `satuan_id` | `BIGINT UNSIGNED` | TIDAK | FK | Disalin dari komoditas saat penyimpanan |
| `tanggal_panen` | `DATE` | TIDAK | IDX | Dasar grafik volume panen per tahun |
| `volume` | `DECIMAL(12,3)` | TIDAK | | Disimpan apa adanya, tanpa konversi |
| `kualitas` | `ENUM` | YA | | Lihat §11.19 |
| `harga_jual` | `DECIMAL(15,2)` | YA | | Rupiah per satuan |
| `keterangan_satuan_lokal` | `VARCHAR(255)` | YA | | Contoh: "setara 40 karung" |
| `dokumen_pendukung` | `VARCHAR(255)` | YA | | Foto panen |
| `keterangan` | `TEXT` | YA | | |

**Catatan penting:**
- `volume` disimpan dalam satuan baku komoditasnya, **tidak** dikonversi saat penyimpanan (`rules.md` §8a.4).
- Agregasi lintas komoditas memakai `SUM(volume × satuan.faktor_ke_ton)` dan hanya dilakukan saat rekap (`rules.md` §8a.5).
- `satuan_id` sengaja disalin dari komoditas, bukan sekadar dibaca lewat relasi, agar data historis tetap sahih bila satuan baku komoditas kelak diubah.
- Lokasi produksi tidak disimpan di sini; dibaca lewat rantai `riwayat_tanam → lahan → satuan_permukiman`.

---

## 10. Domain Infrastruktur dan Pengaduan

### 10.1 `infrastruktur`

Pendataan **aset** infrastruktur. Pelaporan kerusakan ditangani modul Pengaduan (`rules.md` §10.1).

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_infrastruktur` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
| `satuan_permukiman_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | |
| `poktan_id` | `BIGINT UNSIGNED` | YA | FK | Diisi bila dikelola poktan |
| `nama` | `VARCHAR(255)` | TIDAK | | |
| `jenis` | `ENUM` | TIDAK | IDX | Lihat §11.20 |
| `tahun_perolehan` | `YEAR` | YA | | |
| `sumber_dana` | `ENUM` | YA | | Lihat §11.3 |
| `kondisi` | `ENUM` | TIDAK | IDX | Lihat §11.5; sumber grafik status infrastruktur |
| `kapasitas` | `VARCHAR(100)` | YA | | Contoh: "debit 5 liter/detik", "panjang 2 km" |
| `lintang` | `DECIMAL(10,7)` | YA | | |
| `bujur` | `DECIMAL(10,7)` | YA | | |
| `foto` | `VARCHAR(255)` | YA | | Dokumentasi kondisi lapangan |
| `dokumen_pendukung` | `VARCHAR(255)` | YA | | |
| `keterangan` | `TEXT` | YA | | |

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
| `bidang` | `ENUM` | TIDAK | IDX | Lihat §11.22; menentukan dinas penanganan |
| `judul` | `VARCHAR(255)` | TIDAK | | Ringkasan singkat |
| `deskripsi` | `TEXT` | TIDAK | | |
| `status` | `ENUM` | TIDAK | IDX | Lihat §11.23; hanya status **terkini** |
| `prioritas` | `ENUM` | TIDAK | IDX | Lihat §11.24 |
| `lintang` | `DECIMAL(10,7)` | YA | | Titik kejadian |
| `bujur` | `DECIMAL(10,7)` | YA | | Titik kejadian |
| `dokumen_pendukung` | `VARCHAR(255)` | YA | | Foto bukti |

**Catatan:**
- Kolom `catatan_penanganan` dan `id_status_penanganan` pada SQL referensi **dihapus**; keduanya duplikatif (`notes.md` §1.5).
- `bidang` menentukan penerusan: Ketransmigrasian ke Dinas Transmigrasi, Pertanian ke Dinas Pertanian (`rules.md` §10b.7).
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
| `dokumen_tindak_lanjut` | `VARCHAR(255)` | YA | | |

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
- `Ubah Izin Role` mencatat perubahan susunan izin sebuah role, karena tindakan ini dapat memperluas akses banyak pengguna sekaligus.
- Entri `Tambah` dan `Ubah` pada baris data yang sama memungkinkan penelusuran siapa penginput asli dan siapa yang memutakhirkannya.

### 11.3 Sumber dana / sumber perolehan
`APBN` · `APBD Provinsi` · `APBD Kabupaten` · `Dinas Transmigrasi Kabupaten` · `Dinas Pertanian Kabupaten` · `Lembaga Swadaya Masyarakat` · `Swadaya` · `Lainnya`

Nilai disederhanakan dari SQL referensi yang menuliskan awalan berulang "Sumber Perolehan Dana ...". Nilai `Swadaya` ditambahkan untuk alsintan milik pribadi.

### 11.4 Status penyerahan
`Sudah Diserahkan` · `Belum Diserahkan` · `Dalam Proses`

### 11.5 Kondisi (barang, fasilitas, alsintan, infrastruktur)
`Baik` · `Rusak Ringan` · `Rusak Berat`

### 11.6 Tipe komoditas
`Pangan` · `Palawija` · `Hortikultura`

### 11.7 Pendidikan terakhir
`Tidak Sekolah` · `SD` · `SMP` · `SMA/SMK` · `Diploma` · `S1` · `S2` · `S3`

### 11.8 Status tinggal transmigran
`Aktif` · `Pindah` · `Tidak Aktif` · `Meninggal`

### 11.9 Kondisi rumah
`Tidak Rusak` · `Rusak Ringan` · `Rusak Berat`

Sengaja berbeda dari §11.5 karena `rules.md` §6a.3 menetapkan istilah `Tidak Rusak` khusus untuk rumah.

### 11.10 Status hunian
`Dihuni` · `Tidak Dihuni`

### 11.11 Jenis lahan
`Lahan Pekarangan` · `Lahan Usaha`

### 11.12 Kategori lahan
`Lahan Basah` · `Lahan Kering`

### 11.13 Status kepemilikan lahan
`HPL` · `SHM` · `Sewa` · `Garapan` · `Lainnya`

### 11.14 Jenis dokumen lahan
`HPL` · `SHM` · `SKT` · `Surat Keterangan Desa` · `Lainnya`

### 11.15 Jabatan anggota poktan
`Ketua` · `Sekretaris` · `Bendahara` · `Anggota`

### 11.16 Status keaktifan anggota
`Aktif` · `Tidak Aktif` · `Sudah Keluar`

### 11.17 Kepemilikan alsintan
`Pribadi` · `Bantuan Poktan`

### 11.18 Jenis saprotan
`Benih` · `Pupuk` · `Pestisida` · `Mulsa` · `Lainnya`

### 11.19 Kualitas panen
`Sangat Baik` · `Baik` · `Cukup` · `Kurang`

Pada SQL referensi kolom ini bertipe `VARCHAR` bebas; dijadikan ENUM agar dapat direkap (`notes.md` §1.6).

### 11.20 Jenis infrastruktur
`Air` · `Irigasi` · `Listrik` · `Jalan Produksi` · `Telekomunikasi` · `Gudang` · `Lainnya`

### 11.21 Kategori pengaduan
`Lahan Usaha` · `Lahan Pekarangan` · `Rumah` · `Infrastruktur` · `Peralatan dan Perlengkapan` · `Alsintan` · `Produksi Panen` · `Bencana` · `Lainnya`

### 11.22 Bidang pengaduan
`Ketransmigrasian` · `Pertanian`

### 11.23 Status pengaduan
`Menunggu Diterima` · `Diterima` · `Diproses` · `Selesai`

### 11.24 Prioritas pengaduan
`Rendah` · `Sedang` · `Tinggi` · `Mendesak`

### 11.25 Akses ke SP
`Semua SP` · `Per SP`

Menentukan **SP mana** datanya boleh dilihat, terpisah dari izin yang menentukan **boleh melakukan apa**. Kolomnya tetap bernama `role.cakupan_data`; yang berubah sejak 13 Agustus 2026 adalah sebutannya di antarmuka.

| Nilai | Penyaring query | Pemakai |
|---|---|---|
| `Semua SP` | tanpa penyaring | Admin, Dinas Transmigrasi, Dinas Pertanian |
| `Per SP` | dibatasi SP pada `user_satuan_permukiman` | Operator SP |

Nilai `Milik Sendiri` pernah tersedia "untuk kebutuhan mendatang" dan dihapus pada tanggal yang sama. Tidak ada peran di kawasan ini yang hanya boleh melihat barisnya sendiri, dan pilihan yang tidak pernah dipakai hanya membuat Admin menebak maknanya saat menyusun role.

### 11.26 Aksi permission
`lihat` · `tambah` · `ubah` · `hapus` · `export`

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

### 11.31 Sumber nilai parameter penilaian

`Infrastruktur` · `Fasilitas`

Menentukan tabel mana yang dibaca untuk menilai sebuah parameter: `infrastruktur` atau `fasilitas_sp`.

### 11.32 Jenis fasilitas SP

`Kesehatan` · `Pendidikan Dasar` · `Pendidikan Lanjutan` · `Ibadah` · `Balai Pertemuan` · `Pasar atau Kios` · `Olahraga` · `Keamanan` · `Lainnya`

Enum ini diperlukan agar penilaian kondisi SP dapat menghitung otomatis. Nama spesifik tetap dicatat pada kolom `nama_fasilitas` (4.2).

### 11.33 Nilai kondisi pada penilaian SP

`Baik` = 1,0 · `Rusak Ringan` = 0,5 · `Rusak Berat` = 0,2 · `Tidak Ada` = 0

`Tidak Ada` **bukan** nilai enum tersendiri pada tabel `infrastruktur` maupun `fasilitas_sp`, melainkan keadaan ketika tidak ditemukan satu pun aset yang bersesuaian. Ketiadaan dan kerusakan wajib dibedakan karena berbeda penanganannya: yang satu memerlukan pembangunan, yang lain perbaikan (`rules.md` 10c.4 poin 9).

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
| `volume` | angka, lebih besar dari 0, maksimal 3 desimal | "Volume panen harus lebih dari 0." |
| `uang` | angka bulat, minimal 0 | "Nilai tidak boleh negatif." |
| `lintang` | opsional, antara −90 dan 90 | "Lintang harus antara −90 dan 90." |
| `bujur` | opsional, antara −180 dan 180 | "Bujur harus antara −180 dan 180." |
| `dokumen` | maksimal 5 MB, tipe jpg/jpeg/png/webp/pdf | "Ukuran berkas maksimal 5 MB." |

**Aturan lintas-field yang divalidasi di sisi aplikasi:**

| # | Aturan | Tabel |
|---|---|---|
| 1 | `alasan_tidak_dihuni` wajib bila `status_hunian` = Tidak Dihuni | `rumah` |
| 2 | `tanggal_keluar` wajib bila `status_keaktifan` = Sudah Keluar | `anggota_poktan` |
| 3 | `tanggal_keluar` tidak boleh mendahului `tanggal_masuk` | `anggota_poktan`, `riwayat_penghunian` |
| 4 | `transmigran_id` wajib bila `kepemilikan` = Pribadi; `poktan_id` wajib bila Bantuan Poktan | `alsintan` |
| 5 | Minimal satu di antara `transmigran_id` dan `poktan_id` terisi | `saprotan` |
| 6 | `kategori_lahan` wajib bila `jenis_lahan` = Lahan Usaha | `lahan` |
| 7 | Pilihan rumah hanya menampilkan baris dengan `transmigran_id` bernilai `NULL` | `rumah` |
| 8 | Perubahan status pengaduan wajib mengikuti urutan yang ditetapkan | `pengaduan` |
| 9 | Penerima saprotan lewat poktan wajib berstatus keaktifan Aktif | `saprotan` |
| 10 | `tanggal_panen` tidak boleh mendahului `tanggal_tanam` pada riwayat tanam terkait | `hasil_panen` |
| 11 | `email` dan `username` wajib terisi dan unik antar-akun | `user` |
| 12 | `username` hanya boleh huruf kecil, angka, titik, dan garis bawah, panjang 3 sampai 50 karakter | `user` |
| 13 | Akun berrole bercakupan `Per SP` wajib memiliki minimal satu penugasan SP | `user_satuan_permukiman` |
| 14 | Admin tidak boleh menonaktifkan atau menghapus akun Admin terakhir yang masih aktif | `user` |
| 15 | Role bertanda `is_bawaan` tidak dapat dihapus | `role` |
| 16 | Role bertanda `is_terkunci` tidak dapat diubah izinnya | `role` |
| 17 | Role yang masih dipakai minimal satu akun tidak dapat dihapus | `role` |
| 19 | `nama_pelapor` dan `kontak_pelapor` wajib pada seluruh pengaduan, baik publik maupun dicatat petugas | `pengaduan` |
| 20 | `user_id` wajib kosong bila `sumber_laporan` bernilai Publik, dan wajib terisi bila Petugas | `pengaduan` |
| 21 | Pengaduan publik dibatasi 3 laporan per jam untuk setiap alamat IP | `pengaduan` |

---

## 13. Daftar Izin (Permission)

Izin ditanam sistem lewat seeder dan **tidak dapat ditambah atau dihapus Admin**, karena setiap izin harus memiliki pasangan pemeriksa di dalam kode. Admin hanya memasangkannya ke role lewat `role_permission`.

Penamaan memakai pola `modul.aksi`, contoh `transmigran.lihat`.

### 13.1 Aksi yang tersedia per modul

Tanda centang berarti izin tersebut dibuat untuk modul bersangkutan.

| Modul | lihat | tambah | ubah | hapus | export |
|---|:---:|:---:|:---:|:---:|:---:|
| `pengguna` | v | v | v | | |
| `role` | v | v | v | v | |
| `audit_log` | v | | | | v |
| `wilayah` | v | v | v | v | |
| `kawasan` | v | v | v | v | v |
| `sp` | v | v | v | v | v |
| `inventaris_sp` | v | v | v | v | v |
| `fasilitas_sp` | v | v | v | v | v |
| `satuan` | v | v | v | v | |
| `transmigran` | v | v | v | v | v |
| `rumah` | v | v | v | v | v |
| `riwayat_penghunian` | v | v | v | v | v |
| `lahan` | v | v | v | v | v |
| `dokumen_lahan` | v | v | v | v | |
| `poktan` | v | v | v | v | v |
| `anggota_poktan` | v | v | v | v | v |
| `alsintan` | v | v | v | v | v |
| `saprotan` | v | v | v | v | v |
| `komoditas` | v | v | v | v | v |
| `musim_tanam` | v | v | v | v | |
| `riwayat_tanam` | v | v | v | v | v |
| `hasil_panen` | v | v | v | v | v |
| `infrastruktur` | v | v | v | v | v |
| `pengaduan` | v | v | v | v | v |
| `penanganan_pengaduan` | v | v | v | | |
| `dashboard` | v | | | | v |
| `laporan` | v | | | | v |

Total **117 izin** dari 27 modul, dihitung dari tabel di atas.

Jumlah izin yang benar-benar dipegang tiap role bawaan lebih sedikit, sesuai susunan pada `rules.md` 5.1: Admin 117, Dinas Transmigrasi 57, Dinas Pertanian 64, Operator SP 50.

### 13.2 Kelompok modul pada antarmuka

Agar halaman pengaturan role mudah dibaca, izin dikelompokkan sesuai struktur menu:

| Kelompok | Modul |
|---|---|
| Sistem | `pengguna`, `role`, `audit_log` |
| Wilayah dan SP | `wilayah`, `kawasan`, `sp`, `inventaris_sp`, `fasilitas_sp`, `satuan` |
| Kependudukan | `transmigran`, `rumah`, `riwayat_penghunian` |
| Lahan | `lahan`, `dokumen_lahan` |
| Kelembagaan | `poktan`, `anggota_poktan`, `alsintan`, `saprotan` |
| Pertanian | `komoditas`, `musim_tanam`, `riwayat_tanam`, `hasil_panen` |
| Infrastruktur | `infrastruktur` |
| Pengaduan | `pengaduan`, `penanganan_pengaduan` |
| Pemantauan | `dashboard`, `laporan` |

### 13.3 Aturan pemeriksaan izin

1. Pemeriksaan wajib dilakukan pada **level query dan controller**, bukan sekadar menyembunyikan menu (`rules.md` §5).
2. Menu sidebar dirender hanya bila pengguna memiliki izin `lihat` pada modul bersangkutan. Menu yang tidak berhak **tidak dirender sama sekali**.
3. Tombol aksi (Tambah, Ubah, Hapus, Export) dirender hanya bila izin terkait dimiliki.
4. Izin `lihat` adalah prasyarat seluruh aksi lain pada modul yang sama. Memberi izin `ubah` tanpa `lihat` dianggap galat konfigurasi dan ditolak sistem.
5. Setiap perubahan susunan izin sebuah role wajib tercatat pada `audit_log`.
