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

Tabel referensi wilayah (`provinsi`, `kabupaten`, `kecamatan`, `desa`), `satuan`, `musim_tanam`, `riwayat_penghunian`, `riwayat_kepala_keluarga`, `riwayat_tanam`, `penanganan_pengaduan`, dan `audit_log` **tidak** memakai soft delete: tabel referensi dilindungi `RESTRICT`, sedangkan tabel riwayat memang tidak boleh dihapus.

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
| `dokumen_pendukung` | `VARCHAR(255)` | YA | | Path berkas |
| `keterangan` | `TEXT` | YA | | Catatan bebas |

**Catatan:**
- Empat kolom `batas_utara`, `batas_timur`, `batas_selatan`, dan `batas_barat` **dicabut 2026-08-18**. Keempatnya menggantikan tabel `koordinat_lokasi_sp` pada SQL referensi, tetapi isinya berupa sebutan naratif seperti "Berbatasan dengan Desa Naet", bukan koordinat, sehingga tidak pernah dipakai perhitungan, indikator dashboard, penilaian kondisi SP, maupun peta mana pun. Rinciannya pada `notes.md` bagian 6.
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
| `foto` | `VARCHAR(255)` | YA | | Dokumentasi kondisi barang |
| `dokumen_pendukung` | `VARCHAR(255)` | YA | | Berita acara atau bukti pengadaan |
| `keterangan` | `TEXT` | YA | | |

**Catatan:**
- `satuan_barang` sengaja berupa teks bebas dan **tidak** menaut ke tabel `satuan`, karena tabel `satuan` khusus menyimpan satuan berat beserta faktor konversi ke ton.
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
| `kondisi` | `ENUM` | YA | | Lihat §11.5 |
| `lintang` | `DECIMAL(10,7)` | YA | | Lokasi fasilitas |
| `bujur` | `DECIMAL(10,7)` | YA | | Lokasi fasilitas |
| `foto` | `VARCHAR(255)` | YA | | Dokumentasi kondisi bangunan |
| `dokumen_pendukung` | `VARCHAR(255)` | YA | | Berita acara serah terima atau berkas pembangunan |
| `keterangan` | `TEXT` | YA | | |

**Catatan:**
- **Kolom `foto` ditambahkan 2026-08-20**, alasan sama dengan §4.1. Sebelumnya satu slot dipakai untuk keduanya, dan labelnya bahkan berbunyi "Dokumen atau Foto Fasilitas" — menjanjikan dua hal untuk satu tempat penyimpanan.
- `jenis_fasilitas` dan `nama_fasilitas` sengaja berdampingan. Enum diperlukan agar penilaian kondisi SP dapat menghitung otomatis, sebab teks bebas membuat "SEKOLAH DASAR" dan "SD Negeri 1" tidak terbaca sebagai hal yang sama. Nama bebas tetap dipertahankan agar petugas dapat menulis sebutan yang dikenal warga setempat.

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
| `referensi_id` | `BIGINT UNSIGNED` | TIDAK | FK, IDX | Baris `referensi` yang dicari pada tabel sumber, contoh jenis infrastruktur `Air` |
| `is_aktif` | `BOOLEAN` | TIDAK | | Parameter nonaktif tidak ikut dihitung pada penilaian baru |
| `urutan` | `SMALLINT UNSIGNED` | TIDAK | | Urutan tampil pada halaman pengaturan dan rincian skor |

**Catatan:**
- Parameter **dinonaktifkan, bukan dihapus**, agar riwayat penilaian yang memakainya tetap dapat dibaca.
- `sumber` dan `referensi_id` menjelaskan dari mana nilai kondisi diambil: parameter `air_bersih` membaca `infrastruktur` berjenis `Air`, sedangkan `kesehatan` membaca `fasilitas_sp` berjenis `Kesehatan`.
- `referensi_id` **menggantikan `jenis_rujukan` yang dulu berupa teks**, sejak jenis infrastruktur dan fasilitas menjadi data master. Rujukan berbasis teks putus tanpa pesan apa pun begitu Admin memperbaiki ejaannya, dan parameter itu lalu diam-diam menilai setiap SP sebagai tidak punya aset tersebut. Bila idnya tidak ditemukan, parameter **dilewati**, bukan dinilai nol: menilainya nol berarti seluruh SP jatuh statusnya hanya karena satu baris referensi hilang.

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

### 5.6 `referensi`

Daftar pilihan yang **dikelola Admin lewat antarmuka**, bukan ditulis sebagai enum di dalam kode (`rules.md` 4 poin 12).

Empat belas daftar disatukan pada satu tabel karena strukturnya identik. Empat belas tabel terpisah berarti empat belas migration, empat belas model, dan empat belas halaman CRUD untuk perbedaan yang hanya terletak pada nama jenisnya.

| Kolom | Tipe | Null | Kunci | Keterangan |
|---|---|---|---|---|
| `id_referensi` | `BIGINT UNSIGNED AUTO_INCREMENT` | TIDAK | PK | |
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
- Enum yang **tetap di dalam kode** dan tidak menjadi referensi: seluruh enum yang membawa perilaku (`StatusPengaduan` dengan state machine-nya, `StatusKondisiSp` dengan `dariSkor()`, `PeruntukanLahan` dengan `lahanUsaha()`, `AsalWakilPoktan`, `CakupanData`, `AksiPermission`, `AksiAuditLog`), serta enum yang nilainya terikat ketentuan luar (`JenisKelamin`, `PendidikanTerakhir`).

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
| `hubungan_pengganti` | `ENUM` | TIDAK | | Lihat 11.35; kedudukan pengganti terhadap kepala keluarga lama |
| `keterangan` | `TEXT` | YA | | |

**Catatan:**

- **Rumah tangganya berlanjut, yang berganti kepalanya.** Karena itu suksesi menyunting baris `transmigran` yang ada, bukan membuat baris baru. Alasannya bukan kepraktisan: jatah rumah dan lahan transmigrasi diberikan kepada **KK**, bukan kepada suaminya secara pribadi, sehingga ketujuh relasi yang menaut ke `transmigran` memang seharusnya tetap utuh. Membuat baris baru juga menuntut melepas UNIQUE pada `no_kk` dan memindahkan tujuh FK secara manual, dan setiap hitungan "jumlah KK" pada dashboard akan menghitung ganda kecuali disaring status.
- **`audit_log` saja tidak cukup, dan itulah sebab tabel ini ada.** Audit log memang merekam bahwa `nama_kepala_keluarga` berubah, tetapi ia **tidak dapat membedakan suksesi dari perbaikan salah ketik**: keduanya tercatat sebagai aksi `Ubah` pada kolom yang sama. Data contoh audit log sendiri sudah memuat contoh yang pertama, yaitu *"Memperbaiki ejaan nama YOHANES BERE"*.
- **Kedua sisi disimpan**, bukan hanya yang lama. Merangkai nama pengganti dari baris riwayat berikutnya memang menghemat tiga kolom, tetapi menukarnya dengan kueri berantai yang rapuh dan riwayat yang tidak dapat dibaca berdiri sendiri.
- **`no_kk` ikut disimpan dua sisi** sebab Dukcapil menerbitkan KK baru ketika kepala keluarganya berganti. Bila nomornya tidak berubah, keduanya diisi sama.
- Tanpa kolom `user_id`: pelaku suksesi sudah terekam `audit_log`, sama seperti `riwayat_penghunian`.
- **Suksesi adalah tindakan tersendiri, bukan efek samping form ubah** (`rules.md` §6.5b). Bila ia lahir dari penyuntingan nama pada form biasa, setiap perbaikan ejaan akan mengotori riwayat suksesi — persis kekaburan yang tabel ini dibuat untuk menutupnya.

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
| `peruntukan_lahan` | `ENUM` | TIDAK | IDX | Lihat 11.11 |
| `luas` | `DECIMAL(12,2)` | TIDAK | | Hektare; luas seluruh bidang |
| `luas_kering` | `DECIMAL(12,2)` | YA | | Hektare; bagian lahan kering. Hanya untuk lahan usaha |
| `luas_basah` | `DECIMAL(12,2)` | YA | | Hektare; bagian lahan basah. Hanya untuk lahan usaha |
| `status_hak` | `ENUM` | YA | | Lihat 11.13 |
| `tujuan_pemanfaatan` | `TEXT` | YA | | |
| `lintang` | `DECIMAL(10,7)` | YA | | |
| `bujur` | `DECIMAL(10,7)` | YA | | |
| `pola_tanam` | `VARCHAR(255)` | YA | | Khusus lahan usaha: monokultur, tumpang sari |
| `peralatan_pertanian` | `TEXT` | YA | | Khusus lahan usaha |
| `kendala` | `TEXT` | YA | | Khusus lahan usaha |
| `keterangan` | `TEXT` | YA | | |

**Catatan:**
- FK berada di tabel ini, **bukan** di `transmigran`, karena satu transmigran dapat memiliki lebih dari satu lahan usaha (`rules.md` §7.8).
- Tiga kolom terakhir sebelum `keterangan` hanya relevan bila peruntukannya lahan usaha; untuk lahan pekarangan ketiganya dibiarkan `NULL`.
- Rekap luas lahan **wajib** memakai `SUM(luas)`, bukan mengambil satu baris (`rules.md` §7.10).
- **Kering dan basah adalah komposisi luas, bukan kategori bidang** (ditetapkan 2026-08-20 atas keterangan lapangan pemilik proyek). Sebelumnya sifat pengairan disimpan sebagai kolom enum `kategori_lahan` bernilai `Lahan Basah` atau `Lahan Kering`, sehingga satu bidang hanya boleh bersifat salah satu. Keadaan sebenarnya di Kobalima Timur: satu bidang lahan usaha seluas 1 ha dapat digarap 0,5 ha kering dan 0,5 ha basah sekaligus. Bidang campuran semacam itu **tidak dapat dicatat** oleh kolom enum, dan petugas terpaksa memilih salah satu — membuat separuh luasnya hilang dari rekap tanpa ada yang menyadarinya, kegagalan yang persis pernah terjadi pada penjumlahan luas (`notes.md` §1c.2 dan butir 2026-08-18).
- **Bidangnya tetap satu baris dengan satu titik koordinat.** Yang dipecah hanya angka luasnya, sebab pemecahan kering/basah tidak melahirkan bidang baru dan tidak berpindah tempat. Karena itu dipilih dua kolom luas, bukan tabel komposisi tersendiri: kategorinya tetap dua dan tidak bertambah, sehingga tabel terpisah hanya menambah join tanpa menambah kemampuan.
- **Aturan jumlah:** untuk lahan usaha, `luas_kering + luas_basah` wajib sama dengan `luas`. Bidang yang seluruhnya kering diisi `luas_kering = luas` dan `luas_basah = 0`, bukan `NULL`, agar penjumlahan rekap tidak perlu membedakan nol dari kosong.
- Rekap luas kering dan basah memakai `SUM(luas_kering)` dan `SUM(luas_basah)` atas lahan berperuntukan usaha. Penyaringan "lahan basah" berarti `luas_basah > 0`, yaitu **bidang yang memiliki bagian basah**, bukan bidang yang seluruhnya basah.

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
| `asal_ketua` | `ENUM` | TIDAK | | Lihat 11.34; bawaan `Kepala Keluarga`. Menentukan jalur pengisian data ketua |
| `ketua_transmigran_id` | `BIGINT UNSIGNED` | YA | FK | Keluarga yang diwakili ketua. Wajib bila `asal_ketua` bukan `Bukan Transmigran` |
| `nama_ketua` | `VARCHAR(255)` | YA | | Wajib bila `asal_ketua` bukan `Kepala Keluarga`, selain itu `NULL` |
| `nik_ketua` | `CHAR(16)` | YA | | Wajib bila `asal_ketua` bukan `Kepala Keluarga`; tepat 16 digit angka |
| `hubungan_ketua` | `ENUM` | YA | | Lihat 11.35; wajib bila `asal_ketua` = `Anggota Keluarga`, selain itu `NULL` |
| `nama` | `VARCHAR(255)` | TIDAK | UQ | |
| `tahun_berdiri` | `YEAR` | YA | | Tahun saja; tanggal pendirian poktan lama kerap tidak terdokumentasi |
| `telepon_ketua` | `VARCHAR(20)` | YA | | Kontak ketua, bukan kontak kelompok |
| `email_ketua` | `VARCHAR(255)` | YA | | Kontak ketua; `transmigran` tidak menyimpan email, sehingga di sinilah tempatnya |
| `alamat_ketua` | `VARCHAR(255)` | YA | | Alamat ketua atau sekretariat kelompok |
| `luas_kering_ketua` | `DECIMAL(12,2)` | YA | | Hektare; **hanya** bila `asal_ketua` = `Bukan Transmigran` |
| `luas_basah_ketua` | `DECIMAL(12,2)` | YA | | Hektare; **hanya** bila `asal_ketua` = `Bukan Transmigran` |
| `lintang` | `DECIMAL(10,7)` | YA | | |
| `bujur` | `DECIMAL(10,7)` | YA | | |
| `dokumen_pendukung` | `VARCHAR(255)` | YA | | SK pembentukan |
| `keterangan` | `TEXT` | YA | | |

**Catatan:**
- **Ketua poktan punya tiga asal-usul, bukan dua** (diperluas 2026-08-20). Kolom `is_ketua_transmigran` bertipe boolean digantikan `asal_ketua` bertipe enum, sebab boolean hanya sanggup membedakan dua keadaan sedangkan keadaan lapangan ada tiga. Jalur pengisiannya:

  | `asal_ketua` | `ketua_transmigran_id` | `nama_ketua`, `nik_ketua` | `hubungan_ketua` | Luas & koordinat |
  |---|---|---|---|---|
  | `Kepala Keluarga` | wajib | `NULL`, dibaca lewat relasi | `NULL` | dari lahan keluarga |
  | `Anggota Keluarga` | wajib | **wajib diketik** | wajib | dari lahan keluarga |
  | `Bukan Transmigran` | `NULL` | **wajib diketik** | `NULL` | `luas_*_ketua` diketik |

  - Jalur pertama tidak menyalin nama dan NIK agar tidak ada dua versi data yang berpotensi tidak sinkron (`erd.md` §8.2 nomor 25).
  - Jalur kedua **harus** mengetiknya: sistem tidak mendata anggota keluarga satu per satu (`erd.md` §7.4), sehingga tidak ada relasi yang dapat dibaca. `ketua_transmigran_id` tetap terisi karena yang ditunjuk adalah **keluarga** yang diwakili, bukan orangnya.
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
| `nama_wakil` | `VARCHAR(255)` | YA | | Wajib bila `asal_wakil` = `Anggota Keluarga`, selain itu `NULL` |
| `nik_wakil` | `CHAR(16)` | YA | | Wajib bila `asal_wakil` = `Anggota Keluarga`; tepat 16 digit angka |
| `telepon_wakil` | `VARCHAR(20)` | YA | | Kontak wakil; terisi sendiri dari transmigran bila wakilnya kepala keluarga |
| `hubungan_dengan_kk` | `ENUM` | YA | | Lihat 11.35; wajib bila `asal_wakil` = `Anggota Keluarga` |
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
  - `Kepala Keluarga` → nama, NIK, dan telepon dibaca lewat relasi ke `transmigran`; ketiga kolom `*_wakil` dibiarkan `NULL`.
  - `Anggota Keluarga` → ketiganya wajib diketik beserta `hubungan_dengan_kk`, sebab sistem tidak mendata anggota keluarga satu per satu (`erd.md` §7.4).
  - `Bukan Transmigran` **tidak berlaku** bagi anggota: seluruh anggota wajib berasal dari keluarga transmigran (`rules.md` §7a poin 3). Pembatasannya ditegakkan aplikasi, sebab ENUM database memuat ketiga nilai agar dapat dipakai bersama `poktan.asal_ketua`.
- **Luas lahan dan koordinat anggota diturunkan, tidak disimpan.** Keduanya dijumlahkan dari bidang milik keluarga yang diwakili, sehingga tidak berubah ketika wakilnya berganti dan tidak pernah basi ketika luas dibetulkan di modul lahan.
- **`alasan_keluar` dipisahkan dari `keterangan`** (2026-08-20). Sebelumnya `keterangan` dipakai dua maksud sekaligus: kamus data menyebutnya catatan umum, sedangkan form melabelinya "Alasan Keluar", sehingga catatan keanggotaan biasa tidak punya tempat. Pemisahan ini mengikuti `riwayat_penghunian` §6.3 yang sudah membedakan `alasan_keluar` dari `keterangan`.
- **Jabatan tidak lagi memuat nilai `Ketua`** (2026-08-17). Ketua ditetapkan pada tabel `poktan`, dan menyediakannya juga di sini berarti satu poktan dapat memiliki dua ketua berbeda tanpa ada yang menyadarinya. Lihat §11.15.
- **Perpindahan anggota antar poktan** dicatat sebagai dua baris: baris di poktan lama ditandai `Sudah Keluar` beserta `tanggal_keluar` dan alasannya, lalu dibuat baris baru pada poktan tujuan. Memindahkan `poktan_id` pada baris yang sama akan menghapus jejak keanggotaan di poktan lama seolah tidak pernah ada.
- Seorang transmigran hanya boleh berstatus **Aktif pada satu poktan** dalam satu waktu (`rules.md` §6.4). UNIQUE gabungan di atas hanya mencegah baris ganda pada poktan yang sama, sehingga pembatasan ini ditegakkan di tingkat aplikasi.

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
| `jenis` | `ENUM` | TIDAK | IDX | Lihat §11.18 |
| `nama` | `VARCHAR(255)` | TIDAK | | Contoh: Urea, benih jagung hibrida |
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

Pendataan **aset** infrastruktur. Pelaporan kerusakan ditangani fitur Pengaduan (`rules.md` §10.1).

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
| `bidang` | `ENUM` | **YA** | IDX | Lihat §11.22; menentukan dinas penanganan. `NULL` berarti belum ditetapkan petugas |
| `judul` | `VARCHAR(255)` | TIDAK | | Ringkasan singkat |
| `deskripsi` | `TEXT` | TIDAK | | |
| `status` | `ENUM` | TIDAK | IDX | Lihat §11.23; hanya status **terkini** |
| `prioritas` | `ENUM` | TIDAK | IDX | Lihat §11.24 |
| `lintang` | `DECIMAL(10,7)` | YA | | Titik kejadian |
| `bujur` | `DECIMAL(10,7)` | YA | | Titik kejadian |
| `dokumen_pendukung` | `VARCHAR(255)` | YA | | Foto bukti |

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
- `Ubah Izin Role` mencatat perubahan susunan kewenangan sebuah role, karena tindakan ini dapat memperluas akses banyak pengguna sekaligus.
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

### 11.11 Peruntukan lahan
`Lahan Pekarangan` — `Lahan Usaha`

Satu transmigran umumnya menerima **satu bidang tiap peruntukan** (`rules.md` 7.8). Nilai `Lahan Usaha I` dan `Lahan Usaha II` sempat ditambahkan pada 2026-08-18 atas dugaan pembagian bertahap, lalu dibatalkan pada hari yang sama setelah keadaan lapangan diketahui. Pemeriksaan "apakah ini lahan usaha" **dilarang** membandingkan satu nilai teks; pakai `PeruntukanLahan::lahanUsaha()`.

### 11.12 Kategori lahan — **DICABUT 2026-08-20**

Semula bernilai `Lahan Basah` · `Lahan Kering` sebagai kolom enum pada `lahan`. Dicabut sebab sifat pengairan ternyata **komposisi luas, bukan sifat bidang**: satu bidang lahan usaha dapat digarap sebagian kering dan sebagian basah. Digantikan kolom `luas_kering` dan `luas_basah` pada 7.1, yang alasannya tercatat di sana.

Nomor bagian ini sengaja **tidak dipakai ulang** agar rujukan lama pada dokumen dan riwayat tetap dapat ditelusuri.

### 11.13 Status hak atas tanah
`Belum Bersertifikat` — `Hak Milik` — `Hak Milik Bersama` — `Hak Pakai` — `Sewa` — `Garapan`

**Diperbaiki 2026-08-18.** Nilai sebelumnya `HPL`, `SHM`, `Sewa`, `Garapan`, `Lainnya`, dan dua yang pertama keliru sebagai status hak perorangan. **HPL** adalah Hak Pengelolaan yang dipegang instansi atas tanah kawasan, sehingga tidak pernah menjadi hak seorang transmigran; menuliskannya di sini membuat sistem menyatakan warga "memiliki lahan berstatus HPL". **SHM** adalah nama sertifikatnya, bukan nama haknya; haknya bernama Hak Milik. Keduanya kini menjadi jenis dokumen (11.14).

Rantai yang sebenarnya: tanah kawasan berstatus Hak Pengelolaan, lalu bidang-bidangnya dibagikan kepada transmigran dengan status Hak Milik. Sebelum sertifikatnya terbit, bidang berstatus `Belum Bersertifikat` dan legalitas penggunaannya bersandar pada surat keterangan pembagian tanah.

> Istilah pada daftar ini **masih menunggu konfirmasi dinas** (`notes.md` bagian 6), sebab berkas penetapan di tiap daerah dapat memakai sebutan berbeda.

### 11.14 Jenis dokumen lahan
`SHM` — `Surat Keterangan Pembagian Tanah` — `SKT` — `Surat Keterangan Desa` — `HPL` — `Lainnya`

### 11.15 Jabatan anggota poktan
`Sekretaris` — `Bendahara` — `Anggota`

Nilai `Ketua` **dicabut 2026-08-17**. Ketua ditetapkan pada tabel `poktan` lewat `is_ketua_transmigran` beserta pasangannya, sebab ketua tidak selalu berasal dari anggota yang terdaftar di sini. Menyediakan `Ketua` pada kedua tempat membuat satu poktan dapat memiliki dua ketua berbeda tanpa penjaga apa pun.

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
`Lahan Usaha` · `Lahan Pekarangan` · `Rumah` · `Infrastruktur` · `Inventaris SP` · `Fasilitas SP` · `Kelompok Tani` · `Alsintan` · `Saprotan` · `Produksi Panen` · `Bencana` · `Lainnya`

Tiga perubahan pada 2026-08-19: nilai `Peralatan dan Perlengkapan` **dipecah** menjadi `Inventaris SP` dan `Fasilitas SP`, sebab satu kategori menaungi dua daftar berbeda sehingga petugas tidak dapat mengetahui yang mana dimaksud pelapor; `Saprotan` **ditambahkan** agar keluhan bibit, pupuk, serta obat tidak menumpang pada `Produksi Panen`; dan `Kelompok Tani` **ditambahkan** sebab poktan adalah modul penuh tetapi keluhan atasnya tidak punya kategori sendiri.

**Daftar kategori memetakan modul yang dapat diadukan warga.** Penyisiran 2026-08-19 atas 26 fitur berkewenangan (§13.1) menyimpulkan pemetaannya kini lengkap dua arah. Modul yang sengaja **tidak** berkategori: `pengguna`, `role`, `audit_log`, `dashboard` (urusan internal sistem); `wilayah`, `kawasan`, `sp`, `satuan` (data referensi, bukan benda yang dapat rusak); `transmigran`, `riwayat_penghunian`, `riwayat_kepala_keluarga`, `dokumen_lahan`, `anggota_poktan`, `penanganan_pengaduan` (catatan administratif tentang warga; warga mengadukan masalah, bukan sesama warga, dan kekeliruan pencatatan diperbaiki lewat petugas bukan lewat kanal pengaduan); serta `komoditas`, `musim_tanam`, `riwayat_tanam` (data master pertanian yang keluhannya bermuara ke `Produksi Panen`).

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

### 11.31 Sumber nilai parameter penilaian

`Infrastruktur` · `Fasilitas`

Menentukan tabel mana yang dibaca untuk menilai sebuah parameter: `infrastruktur` atau `fasilitas_sp`.

### 11.32 Jenis fasilitas SP

`Kesehatan` · `Pendidikan Dasar` · `Pendidikan Lanjutan` · `Ibadah` · `Balai Pertemuan` · `Pasar atau Kios` · `Olahraga` · `Keamanan` · `Lainnya`

Enum ini diperlukan agar penilaian kondisi SP dapat menghitung otomatis. Nama spesifik tetap dicatat pada kolom `nama_fasilitas` (4.2).

### 11.33 Nilai kondisi pada penilaian SP

`Baik` = 1,0 · `Rusak Ringan` = 0,5 · `Rusak Berat` = 0,2 · `Tidak Ada` = 0

`Tidak Ada` **bukan** nilai enum tersendiri pada tabel `infrastruktur` maupun `fasilitas_sp`, melainkan keadaan ketika tidak ditemukan satu pun aset yang bersesuaian. Ketiadaan dan kerusakan wajib dibedakan karena berbeda penanganannya: yang satu memerlukan pembangunan, yang lain perbaikan (`rules.md` 10c.4 poin 9).

### 11.34 Asal wakil poktan

`Kepala Keluarga` · `Anggota Keluarga` · `Bukan Transmigran`

Dipakai bersama oleh `poktan.asal_ketua` dan `anggota_poktan.asal_wakil`. Menggantikan `poktan.is_ketua_transmigran` bertipe boolean, sebab keadaan lapangan ada tiga sedangkan boolean hanya sanggup membedakan dua.

**Anggota poktan hanya boleh memakai dua nilai pertama**; seluruh anggota wajib berasal dari keluarga transmigran (`rules.md` 7a poin 3). Nilai ketiga khusus ketua. Pembatasannya ditegakkan aplikasi, bukan ENUM database, agar satu tipe dapat dipakai kedua tabel.

Pemeriksaan "apakah identitasnya dapat dibaca lewat relasi" **dilarang** membandingkan nilai teks; pakai `AsalWakilPoktan::identitasDariRelasi()` dan `dariKeluargaTransmigran()`.

### 11.35 Hubungan dengan kepala keluarga

`Istri/Suami` · `Anak` · `Menantu` · `Lainnya`

Diisi bila wakil keluarga di poktan bukan kepala keluarganya sendiri. Sengaja kasar dan tidak dirinci sampai urutan anak: yang perlu diketahui hanyalah kedudukan wakil terhadap kepala keluarga, agar petugas dapat menelusuri bila namanya tidak dikenali. Merincinya lebih jauh menuntut pendataan anggota keluarga yang memang di luar lingkup PRD (`erd.md` 7.4).

Dipakai bersama oleh `anggota_poktan.hubungan_dengan_kk`, `poktan.hubungan_ketua`, dan `riwayat_kepala_keluarga.hubungan_pengganti`.

### 11.37 Jenis referensi

`sumber_dana` - `status_penyerahan` - `kondisi` - `kondisi_rumah` - `status_hunian` - `tipe_komoditas` - `kualitas_panen` - `prioritas_pengaduan` - `jenis_dokumen_lahan` - `jabatan_anggota_poktan` - `jenis_infrastruktur` - `jenis_fasilitas` - `bidang_pengaduan` - `kategori_pengaduan`

Menyatakan daftar mana saja yang dikelola Admin lewat data master referensi (5.6). **Enum ini sendiri tidak ikut menjadi data**, sebab ia menyatakan daftar mana yang ada, bukan isi daftarnya.

Setiap nilai di sini **wajib punya kolom yang membacanya**. Menambah satu nilai karena itu selalu berpasangan dengan menyunting kolom pada kamus data; tanpa itu, daftar yang dikelolanya tidak pernah tampil di mana pun.

Pemeriksaan "apakah jenis ini berskor" dan "apakah urutannya bermakna" **dilarang** membandingkan nilai teks; pakai `JenisReferensi::berskor()` dan `berjenjang()`.

### 11.36 Alasan pergantian kepala keluarga

`Meninggal` · `Pindah atau Merantau` · `Cerai` · `Lainnya`

**Bukan pengganti status tinggal (11.8).** Keduanya menjawab pertanyaan berbeda: status tinggal menyatakan keadaan terkini sebuah **keluarga**, sedangkan enum ini merekam satu **peristiwa bertanggal**. Ketika kepala keluarga meninggal lalu istrinya menggantikan, keluarganya tetap berstatus `Aktif` sebab istrinya masih hidup dan menempati rumah yang sama; kematian itu hanya terekam di sini.

Konsekuensi yang perlu disadari saat membaca dashboard: nilai `Meninggal` pada status tinggal hanya menghitung **keluarga yang bubar**, bukan orang yang meninggal. Angka kematian yang sesungguhnya dihitung dari tabel `riwayat_kepala_keluarga`.

`Pindah atau Merantau` sengaja tidak dipecah dua. Dari sisi pendataan keduanya sama: kepala keluarga tidak lagi berada di kawasan sementara keluarganya tetap tinggal. Membedakannya menuntut petugas menilai niat kepergian, dan itu tidak dapat diverifikasi.

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
| 2 | `tanggal_keluar` wajib bila `status` = Sudah Keluar | `anggota_poktan` |
| 3 | `tanggal_keluar` tidak boleh mendahului `tanggal_masuk` | `anggota_poktan`, `riwayat_penghunian` |
| 4 | `transmigran_id` wajib bila `kepemilikan` = Pribadi; `poktan_id` wajib bila Bantuan Poktan | `alsintan` |
| 5 | Minimal satu di antara `transmigran_id` dan `poktan_id` terisi | `saprotan` |
| 6 | `luas_kering` dan `luas_basah` wajib bila peruntukannya lahan usaha, dan jumlah keduanya sama dengan `luas` | `lahan` |
| 7 | Pilihan rumah hanya menampilkan baris dengan `transmigran_id` bernilai `NULL` | `rumah` |
| 8 | Perubahan status pengaduan wajib mengikuti urutan yang ditetapkan | `pengaduan` |
| 9 | Penerima saprotan lewat poktan wajib berstatus keaktifan Aktif | `saprotan` |
| 10 | `tanggal_panen` tidak boleh mendahului `tanggal_tanam` pada riwayat tanam terkait | `hasil_panen` |
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
| 23 | `nama_wakil`, `nik_wakil`, dan `hubungan_dengan_kk` wajib bila `asal_wakil` = `Anggota Keluarga`, dan wajib `NULL` bila `Kepala Keluarga` | `anggota_poktan` |
| 24 | `asal_wakil` tidak boleh bernilai `Bukan Transmigran`; seluruh anggota wajib berasal dari keluarga transmigran | `anggota_poktan` |
| 25 | `ketua_transmigran_id` wajib bila `asal_ketua` bukan `Bukan Transmigran`, dan wajib `NULL` bila `Bukan Transmigran` | `poktan` |
| 26 | `nama_ketua` dan `nik_ketua` wajib bila `asal_ketua` bukan `Kepala Keluarga`, dan wajib `NULL` bila `Kepala Keluarga` | `poktan` |
| 27 | `luas_kering_ketua` dan `luas_basah_ketua` hanya terisi bila `asal_ketua` = `Bukan Transmigran`; selain itu diturunkan dari lahan keluarga | `poktan` |
| 28 | `nik_baru` tidak boleh sama dengan `nik_lama` pada baris yang sama | `riwayat_kepala_keluarga` |
| 29 | `tanggal_pergantian` tidak boleh mendahului `tahun_kedatangan` keluarganya, dan tidak boleh melampaui hari ini | `riwayat_kepala_keluarga` |
| 30 | Suksesi wajib menyetel ulang `poktan.ketua_transmigran_id` bila keluarga tersebut menjabat ketua lewat jalur `Kepala Keluarga`; jabatan ketua tidak diwariskan | `poktan` |

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
| `referensi` | v | v | v | |
| `lahan` | v | v | v | v |
| `dokumen_lahan` | v | v | v | v |
| `poktan` | v | v | v | v |
| `anggota_poktan` | v | v | v |   |
| `alsintan` | v | v | v | v |
| `saprotan` | v | v | v | v |
| `komoditas` | v | v | v | v |
| `musim_tanam` | v | v | v | v |
| `riwayat_tanam` | v | v | v | v |
| `hasil_panen` | v | v | v | v |
| `infrastruktur` | v | v | v | v |
| `pengaduan` | v | v | v | v |
| `penanganan_pengaduan` | v | v | v |   |
| `dashboard` | v |   |   |   |

Total **101 kewenangan** dari 28 fitur, dihitung dari tabel di atas.

Jumlah kewenangan yang benar-benar dipegang tiap role bawaan lebih sedikit, sesuai susunan pada `rules.md` 5.1: Admin 101, Dinas Transmigrasi 48, Dinas Pertanian 47, Operator SP 51.

### 13.2 Kelompok fitur pada antarmuka

Agar halaman pengaturan role mudah dibaca, kewenangan dikelompokkan sesuai struktur menu:

| Kelompok | Fitur |
|---|---|
| Sistem | `pengguna`, `role`, `audit_log` |
| Wilayah dan SP | `wilayah`, `kawasan`, `sp`, `inventaris_sp`, `fasilitas_sp`, `satuan`, `referensi` |
| Kependudukan | `transmigran`, `rumah`, `riwayat_penghunian`, `riwayat_kepala_keluarga` |
| Lahan | `lahan`, `dokumen_lahan` |
| Kelembagaan | `poktan`, `anggota_poktan`, `alsintan`, `saprotan` |
| Pertanian | `komoditas`, `musim_tanam`, `riwayat_tanam`, `hasil_panen` |
| Infrastruktur | `infrastruktur` |
| Pengaduan | `pengaduan`, `penanganan_pengaduan` |
| Pemantauan | `dashboard` |

### 13.3 Aturan pemeriksaan kewenangan

1. Pemeriksaan wajib dilakukan pada **level query dan controller**, bukan sekadar menyembunyikan menu (`rules.md` §5).
2. Menu sidebar dirender hanya bila pengguna memiliki kewenangan `lihat` pada fitur bersangkutan. Menu yang tidak berhak **tidak dirender sama sekali**.
3. Tombol aksi (Tambah, Ubah, Hapus, Export) dirender hanya bila kewenangan terkait dimiliki.
4. Kewenangan `lihat` adalah prasyarat seluruh aksi lain pada fitur yang sama. Memberi kewenangan `ubah` tanpa `lihat` dianggap galat konfigurasi dan ditolak sistem.
5. Setiap perubahan susunan kewenangan sebuah role wajib tercatat pada `audit_log`.
