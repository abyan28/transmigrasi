# notes.md
## Catatan Teknis dan Temuan

Dokumen ini berisi catatan temuan, keputusan, dan hal yang perlu ditindaklanjuti selama penyusunan dokumen dan pengembangan sistem.

> **Status per 2026-08-11:** seluruh koreksi pada bagian 1 sudah **diterapkan** pada skema final di `erd.md` dan `data-dictionary.md`. Bagian 1 dipertahankan sebagai jejak alasan perubahan. Temuan yang muncul belakangan dicatat pada bagian 1a.

---

## 1. Catatan Revisi Skema Database

Sumber: `docs/20260809_T10_22_39.349Z.sql` (22 tabel). Berkas ini diperlakukan sebagai **referensi**, bukan skema final. Skema ini sudah menangkap kebutuhan lapangan dengan baik, tetapi ada beberapa hal yang perlu diperbaiki sebelum diterjemahkan menjadi migration Laravel.

### 1.1 Arah foreign key terbalik (prioritas tinggi)

Beberapa `ALTER TABLE` memasang FK pada tabel induk yang menunjuk ke tabel anak, bukan sebaliknya. Akibatnya relasi tidak akan berfungsi seperti yang dimaksud.

| Baris SQL | Kondisi sekarang | Seharusnya |
|---|---|---|
| `inventaris_sp` | `inventaris_sp.id_inventaris_sp` ? `satuan_permukiman.id_inventaris_sp` | `inventaris_sp.no_sp` ? `satuan_permukiman.no_sp` |
| `fasilitas_sp` | `satuan_permukiman.id_fasilitas_sp` ? `fasilitas_sp.id_fasilitas_sp` | `fasilitas_sp.no_sp` ? `satuan_permukiman.no_sp` |
| `kategori_lahan_sp` | `kategori_lahan_sp.id_kategori_lahan_sp` ? `rumah_sp` dan ? `lahan_usaha_sp` | `rumah_sp` dan `lahan_usaha_sp` yang menunjuk ke `kategori_lahan_sp` |
| `daftar_anggota` | `daftar_anggota.id_daftar_anggota` ? `profil_poktan.id_daftar_anggota` | `daftar_anggota.id_profil` ? `profil_poktan.id_profil` |
| `kategori_lahan`, `komoditas` | menunjuk ke `profil_poktan` dan `riwayat_tanam` | dibalik, tabel referensi tidak boleh menunjuk ke tabel transaksi |
| `alsintan`, `infrastruktur_pertanian`, `profil_poktan` | menunjuk ke `pertanian` | dibalik atau `pertanian` dihapus (lihat 1.2) |
| `transmigran` | `transmigran.id_transmigran` ? `saprotan`, ? `profil_poktan`, ? `pengaduan` | ketiga tabel itu yang menunjuk ke `transmigran` |
| `lahan_sp` | `transmigran.id_lahan_sp` ? `lahan_sp.id_lahan_sp` | `lahan_sp.id_transmigran` ? `transmigran.id_transmigran`. Wajib dibalik karena satu transmigran boleh punya lebih dari satu lahan usaha (lihat keputusan 2026-08-10) |
| `rumah_sp` | `transmigran.id_rumah_sp` ? `rumah_sp.id_rumah_sp` | `rumah_sp.id_transmigran` ? `transmigran.id_transmigran`, **UNIQUE nullable**. Relasi satu-ke-satu, NULL berarti rumah kosong |
| `user` | `user.id_user` ? `transmigran.id_transmigran` dan ? `status_penanganan.id_user` | `transmigran.id_user` ? `user.id_user`; `status_penanganan.id_user` ? `user.id_user` |
| `satuan_permukiman` | `satuan_permukiman.no_sp` ? `transmigran.no_sp` | `transmigran.no_sp` ? `satuan_permukiman.no_sp` |

**Aturan umum:** sisi "banyak" yang menyimpan FK, bukan sisi "satu".

### 1.2 Tabel `pertanian` kemungkinan tidak diperlukan

`pertanian` hanya berisi tiga kolom FK (`id_profil_poktan`, `id_alsintan`, `id_infrastruktur_pertanian`) tanpa atribut sendiri. Ini tabel perantara yang tidak menambah informasi. Lebih baik:
- `alsintan` menyimpan `id_profil` (poktan pemilik) atau `id_transmigran` (pemilik pribadi),
- `infrastruktur_pertanian` menyimpan `no_sp` dan opsional `id_profil`.

### 1.3 ENUM untuk nama wilayah menghambat replikasi

`satuan_permukiman.nama_sp`, `provinsi`, `kabupaten`, `kecamatan`, dan `desa` memakai ENUM berisi nama wilayah spesifik. Masalahnya:
- menambah desa/SP baru berarti mengubah struktur tabel (`ALTER TABLE`), bukan sekadar menambah baris;
- bertentangan dengan `rules.md` �4a.4 yang mewajibkan struktur wilayah dapat ditambah tanpa mengubah skema agar sistem dapat direplikasi ke kawasan transmigrasi lain;
- `desa` pada `satuan_permukiman` hanya memuat 2 nilai (`Kapitan Meo`, `Weain`), sedangkan pada `profil_poktan` memuat 6 nilai. Tidak konsisten.

**Saran:** buat tabel referensi bertingkat `provinsi`, `kabupaten`, `kecamatan`, `desa`, lalu `satuan_permukiman` cukup menyimpan `id_desa`. Nilai awal diisi lewat seeder.

### 1.4 Tipe data yang perlu dikoreksi

| Kolom | Sekarang | Saran | Alasan |
|---|---|---|---|
| `dokumen_pendukung` (semua tabel) | `BLOB` | `VARCHAR(255)` berisi path file | PRD �16 mewajibkan file disimpan di `storage/app/private/...`, bukan di dalam database. BLOB membuat backup berat dan query lambat. |
| `lahan_usaha_sp.volumen_panen` | `VARCHAR(255)` | `DECIMAL(12,3)` + FK `id_satuan` | Angka harus dapat dijumlahkan untuk dashboard. Presisi 3 desimal agar panen kecil tetap terekam (0,001 ton = 1 kg). Sekaligus perbaiki salah ketik `volumen` ? `volume`. |
| `lahan_usaha_sp.harga_jual` | `VARCHAR(255)` | `DECIMAL(15,2)` | Perlu dihitung rata-ratanya di dashboard. |
| `profil_poktan.jumlah_anggota` | `VARCHAR(255)` | `INTEGER UNSIGNED` | Nilai numerik, atau lebih baik dihitung otomatis dari `daftar_anggota`. |
| `satuan_permukiman.luas_lahan`, `profil_poktan.luas_lahan` | `VARCHAR(255)` | `DECIMAL(12,2)` | Perlu dijumlahkan. |
| `alsintan.tahun_perolehan`, `infrastruktur_pertanian.tahun_perolehan`, `saprotan.tahun_perolehan` | `DATE` | `YEAR` | Tidak konsisten dengan `inventaris_sp`/`fasilitas_sp` yang sudah memakai `YEAR`. |
| `user.foto` | `BLOB` | `VARCHAR(255)` berisi path | Sama seperti dokumen pendukung. |

### 1.4a Tabel `satuan` belum ada

Keputusan 2026-08-10 menetapkan satuan panen ditentukan **per komoditas**, bukan satu satuan tunggal untuk semua. Karena itu diperlukan tabel referensi baru:

```
satuan
  id_satuan        INTEGER UNSIGNED PK
  nama_satuan      VARCHAR(50)      -- Ton, Kuintal, Kilogram
  simbol           VARCHAR(10)      -- t, kw, kg
  faktor_ke_ton    DECIMAL(10,6)    -- 1 / 0,1 / 0,001
```

Lalu `komoditas` menyimpan `id_satuan` sebagai satuan bakunya. Volume panen disimpan apa adanya sesuai satuan komoditas; konversi ke ton hanya dilakukan saat agregasi dashboard, memakai `volume * faktor_ke_ton`.

Alasan tidak menyimpan langsung dalam ton: nilai asli lapangan tetap terjaga dan dapat diverifikasi ulang oleh operator, sekaligus menghindari galat pembulatan berulang.

### 1.5 Kolom status ganda pada `pengaduan`

`pengaduan` punya `status_penanganan` (ENUM) sekaligus `id_status_penanganan` (FK) dan `catatan_penanganan`, sementara tabel `status_penanganan` juga menyimpan `catatan_penanganan`. Ini duplikasi.

**Saran:** `pengaduan` menyimpan status terkini saja (ENUM), sedangkan `status_penanganan` menjadi tabel riwayat (satu pengaduan punya banyak baris riwayat) dengan FK `id_pengaduan`. Kolom `id_status_penanganan` dan `catatan_penanganan` pada `pengaduan` dihapus.

### 1.6 Field yang belum ada tetapi diminta PRD

| Kebutuhan PRD | Status di SQL |
|---|---|
| Nomor KK (�7.3) | belum ada di `transmigran` |
| Jumlah anggota keluarga (�7.3) | belum ada di `transmigran` |
| Status keanggotaan poktan (�7.3) | ada di `lahan_usaha_sp`, sebaiknya dipindah/diduplikasi ke `transmigran` |
| Status penyerahan inventaris (�7.2) | belum ada di `inventaris_sp` dan `fasilitas_sp` |
| Kualitas panen sebagai data terstruktur | ada sebagai `VARCHAR` bebas, sebaiknya ENUM |
| Audit log perubahan data (�8.2) | belum ada tabelnya |
| Timestamps (`created_at`, `updated_at`) | belum ada di seluruh tabel |
| Soft delete | belum ada, padahal data lapangan rawan terhapus tidak sengaja |
| Kolom `password` pada `user` | belum ada, padahal sistem butuh login |
| UNIQUE constraint pada `rumah_sp.id_transmigran` | belum ada, padahal relasi rumah�KK bersifat satu-ke-satu |
| Tabel `riwayat_penghunian` (id_rumah, id_transmigran, tanggal masuk, tanggal keluar, alasan) | belum ada, padahal pergantian penghuni harus tersimpan tanpa menimpa data lama |
| Tabel `satuan` beserta `faktor_ke_ton` | belum ada, lihat bagian 1.4a |

### 1.7 Catatan kecil

- `komoditas` memakai kolom berulang `tipe_komoditas_1..3` dan `nama_komoditas_1..3`. Sebaiknya dinormalisasi menjadi satu baris per komoditas, lalu direlasikan many-to-many ke lahan/poktan. Pola kolom berulang membatasi jumlah komoditas maksimal tiga dan menyulitkan agregasi.
- `pengaduan.kategori_pengaduan` punya nilai `'Lainnya '` dengan spasi di akhir. Perlu dibersihkan.
- `profil_poktan` menyimpan `komoditas` sebagai `VARCHAR` sekaligus `id_komoditas` sebagai FK. Pilih salah satu.
- `satuan_permukiman` menyimpan `id_transmigran`, padahal relasinya satu SP ke banyak transmigran. FK seharusnya ada di `transmigran`.
- `lahan_sp` dan `kategori_lahan_sp` sama-sama memuat ENUM `('Lahan Usaha', 'Lahan Pekarangan')`. Redundan, cukup salah satu.
- Belum ada indeks pada kolom yang sering difilter (`no_sp`, `id_transmigran`, `id_profil`, tanggal). Dashboard akan lambat tanpa indeks ini (`rules.md` �11.7).

---

## 1a. Temuan Tambahan (2026-08-11)

Temuan berikut muncul saat menyusun skema final dan tidak tercakup pada bagian 1. Seluruhnya sudah diterapkan pada `erd.md` �8.2.

### 1a.1 Data panen menempel di tabel lahan (prioritas tinggi)

`lahan_usaha_sp` menyimpan `volumen_panen`, `harga_jual`, `kualitas_panen`, dan `musim_tanam` sebagai kolom biasa. Akibatnya **satu lahan hanya bisa memiliki satu catatan panen selamanya** � panen musim berikutnya akan menimpa data sebelumnya.

Ini bertabrakan langsung dengan:
- PRD �7.6 yang mewajibkan penyimpanan riwayat panen per periode,
- PRD �7.8 yang meminta grafik total volume panen tiap tahun,
- `rules.md` �9.1 yang menyatakan hasil panen harus dicatat per periode.

**Keputusan:** dibuat tabel `hasil_panen` tersendiri, ditaut lewat `riwayat_tanam` (lahan + musim tanam + komoditas). Dengan begitu satu lahan dapat memiliki banyak panen lintas musim dan lintas tahun.

### 1a.2 Tipe GEOMETRY menyulitkan Laravel

`satuan_permukiman.koordinat_lokasi`, `lahan_usaha_sp.koordinat_lokasi_lahan`, `rumah_sp.koordinat_lokasi`, dan `profil_poktan.titik_koordinat_lahan` memakai tipe `GEOMETRY`.

Masalahnya, Eloquent tidak mendukung tipe spasial secara natif. Setiap pembacaan dan penulisan butuh raw query `ST_AsText()`/`ST_GeomFromText()` atau paket pihak ketiga. Padahal kebutuhan sistem hanya menampilkan lintang dan bujur dengan 6 angka desimal (`ui-spec.md` �10), tanpa query spasial apa pun seperti pencarian radius atau perpotongan poligon.

**Keputusan:** diganti dua kolom `lintang` dan `bujur` bertipe `DECIMAL(10,7)`. Presisi 7 desimal setara ketelitian �1 cm. Bila kelak dibutuhkan query spasial, kolom `POINT` dapat ditambahkan sebagai kolom turunan tanpa membongkar data.

### 1a.3 Tabel `koordinat_lokasi_sp` salah nama dan tidak perlu

Tabel ini berisi empat kolom `TEXT` bernama `Utara`, `Timur`, `Selatan`, dan `Barat`. Isinya sebenarnya **deskripsi batas wilayah** ("berbatasan dengan Desa X"), bukan koordinat. Relasinya ke `satuan_permukiman` bersifat satu-ke-satu wajib, sehingga tabel terpisah hanya menambah satu join tanpa manfaat.

**Keputusan:** dilebur menjadi empat kolom `batas_utara`, `batas_timur`, `batas_selatan`, `batas_barat` pada `satuan_permukiman`. **DICABUT 2026-08-18:** keempat kolom hasil peleburan ini akhirnya dihapus seluruhnya, sebab isinya tidak pernah dipakai perhitungan, indikator, maupun peta. Peleburan tabelnya tetap keputusan yang benar; yang salah adalah menyimpan isinya sama sekali.

### 1a.4 Empat tabel untuk satu konsep lahan

`lahan_sp`, `lahan_usaha_sp`, `kategori_lahan_sp`, dan `kategori_lahan` semuanya menggambarkan lahan. Dua di antaranya (`lahan_sp.jenis_lahan` dan `kategori_lahan_sp.nama_lahan`) bahkan memuat ENUM yang identik.

**Keputusan:** digabung menjadi satu tabel `lahan` dengan kolom `jenis_lahan` (Pekarangan/Usaha) dan `kategori_lahan` (Basah/Kering). Kolom khusus lahan usaha � pola tanam, peralatan, kendala � dibuat nullable dan hanya diisi bila jenisnya lahan usaha.

### 1a.5 Field wajib menurut `rules.md` yang belum ada di SQL

| Tabel | Field yang ditambahkan | Dasar |
|---|---|---|
| `transmigran` | `tahun_kedatangan`, `status_tinggal` | PRD �7.8 meminta grafik per tahun; tanpa kolom ini agregasi per tahun mustahil |
| `alsintan` | `jumlah`, `kondisi`, `sumber_perolehan`, `kepemilikan` | `rules.md` �7b.2 |
| `saprotan` | `jenis_saprotan`, `nama_saprotan`, `jumlah`, `satuan_id` | `rules.md` �7c.2 |
| `infrastruktur` | `jenis`, `kondisi`, `sumber_dana`, `lintang`, `bujur` | `rules.md` �10.2�4 |
| `musim_tanam` | `nama`, `tahun`, `tanggal_mulai`, `tanggal_selesai` | Grafik panen per tahun butuh periode terstruktur |
| `inventaris_sp`, `fasilitas_sp` | `status_penyerahan`, `jumlah`, `kondisi` | `rules.md` �4b.4 |
| `pengaduan` | `nomor_pengaduan`, `bidang`, `prioritas`, `judul` | `rules.md` �10b.6�7 |

### 1a.6 Data ketua poktan terduplikasi

`profil_poktan` menyimpan `nama_ketua_poktan` dan `nik_ketua_poktan` sebagai teks, padahal juga memiliki `id_transmigran`. Bila data transmigran diperbarui, salinan teks di poktan menjadi basi.

**Keputusan:** cukup `ketua_transmigran_id` yang menunjuk ke `transmigran`. Nama dan NIK dibaca lewat relasi. Kolom `telepon` dan `email` poktan tetap dipertahankan karena kontak kelompok bisa berbeda dari kontak pribadi ketua.

### 1a.7 Kolom `jumlah_anggota` berpotensi basi

`profil_poktan.jumlah_anggota` disimpan sebagai nilai statis. Nilai ini akan berbeda dari jumlah baris `daftar_anggota` begitu ada anggota masuk atau keluar.

**Keputusan:** kolom dihapus. Jumlah anggota dihitung dari `anggota_poktan` berstatus Aktif memakai `withCount`. Sebaliknya, `transmigran.jumlah_anggota_keluarga` **tetap disimpan** karena sistem memang tidak mendata anggota keluarga satu per satu.

### 1a.8 Kawasan transmigrasi tidak punya wujud di database (prioritas tinggi)

Pada SQL referensi, `satuan_permukiman` langsung menyimpan kolom `desa`, sehingga hierarkinya `provinsi ? kabupaten ? kecamatan ? desa ? SP`. Kawasan transmigrasi sama sekali tidak terwakili, padahal ia adalah subjek utama seluruh sistem: "Kawasan Transmigrasi Kobalima Timur" hanya hidup di judul dokumen.

Dua akibatnya:

1. **Kawasan tidak dapat direkap sebagai satu kesatuan.** Untuk menghitung total kawasan, sistem harus mengandaikan bahwa keenam SP tersebut memang milik kawasan yang sama, tanpa dasar data apa pun.
2. **Replikasi ke kawasan lain mustahil**, padahal diwajibkan `rules.md` �4a.4.

Masalah tambahannya, kawasan transmigrasi adalah wilayah **perencanaan** yang memotong batas administratif. Kobalima Timur menaungi 6 SP yang tersebar di **4 kecamatan** berbeda. Hierarki administratif tunggal tidak mungkin mewakili pengelompokan semacam ini.

**Keputusan (2026-08-11):** hierarki dibuat bercabang dua di tingkat kabupaten.

```
provinsi ? kabupaten --- kecamatan ? desa -----+
                      �  (cabang administratif) �
                      +- kawasan_transmigrasi ----? satuan_permukiman
                         (cabang program)
```

`satuan_permukiman` menjadi titik temu, menyimpan `desa_id` sekaligus `kawasan_id`. Kolom `kecamatan_id` **tidak** disimpan di SP karena sudah terbaca lewat rantai desa; menyimpannya terpisah hanya membuka peluang data tidak sinkron bila desa berpindah kecamatan.

Konsekuensi lanjutan: seluruh data operasional tetap menaut ke SP, tidak pernah langsung ke desa maupun kawasan. Rekap per kawasan, kecamatan, maupun desa semuanya dihitung lewat SP.

### 1a.9 Izin saja tidak cukup, cakupan data adalah dimensi terpisah

Saat merancang role dinamis, muncul persoalan yang tidak terlihat pada pendekatan role tetap: **izin per modul tidak cukup menggambarkan hak akses**.

Contohnya, dua operator dengan role "Operator SP" yang sama:
- Maria bertugas di SP Kapitan Meo,
- Yosef bertugas di SP Weain.

Keduanya memiliki izin identik: `transmigran.lihat`, `transmigran.tambah`, `transmigran.ubah`. Bila hanya izin yang diatur, keduanya akan melihat **seluruh 1.200 KK dari 6 SP**, dan Maria dapat mengubah data warga SP Weain yang tidak pernah ia temui.

Ini melanggar `rules.md` �5 tentang pembatasan data pribadi, sekaligus membuka risiko salah ubah data.

**Keputusan:** cakupan data dijadikan atribut tersendiri pada tabel `role`, dengan tiga nilai:

| Cakupan | Penyaring query |
|---|---|
| `Semua` | tanpa penyaring |
| `Per SP` | `WHERE satuan_permukiman_id IN (SP pada user_satuan_permukiman)` |
| `Milik Sendiri` | dibatasi baris terkait pengguna |

Tanda `*` pada matriks kewenangan lama (misalnya `L T U*`) sebenarnya sudah menyatakan cakupan data, hanya belum punya wujud di database. Dengan kolom ini, tanda tersebut akhirnya dapat dijalankan sistem.

**Catatan penting:** akun bercakupan `Per SP` yang belum ditugaskan SP mana pun **tidak melihat data apa pun**, bukan melihat seluruhnya. Ini disengaja agar kelalaian penugasan tidak berubah menjadi kebocoran data.

### 1a.10 Hak verifikasi tertulis tetapi mustahil dijalankan

> **DIBATALKAN 2026-08-14.** Seluruh fitur verifikasi dicabut atas kesepakatan tim. Bagian 1a.10 dan 1a.11 di bawah **tidak lagi berlaku**, tetapi sengaja dipertahankan sebagai jejak keputusan: keduanya menjelaskan mengapa tabel `verifikasi` dan aturan `rules.md` 5.2 pernah ada, sehingga pembaca berikutnya tidak mengira keduanya hilang tanpa sebab. Ringkasan pencabutannya ada pada tabel keputusan bertanggal 2026-08-14.

Matriks kewenangan `rules.md` �5.1 memberi hak verifikasi (`V`) pada **17 modul**, dan `prd.md` menyebut verifikasi sebagai fungsi utama kedua dinas. Namun pemeriksaan menyeluruh menunjukkan **tidak ada satu pun kolom verifikasi** di seluruh tabel: tidak ada `status_verifikasi`, `diverifikasi_oleh`, maupun `tanggal_verifikasi`.

Artinya hak tersebut tertulis di dokumen tetapi tidak mungkin diimplementasikan.

**Keputusan:** dibuat tabel `verifikasi` terpusat memakai pasangan `nama_tabel` dan `record_id` sebagai penunjuk baris. Dipilih daripada menambahkan tiga kolom ke setiap tabel karena dua alasan: hanya satu tabel yang perlu dikelola untuk 17 modul, dan riwayat verifikasi tetap terlacak lewat `audit_log`.

Perlu ditegaskan, **verifikasi tidak mengubah isi data**. Ia hanya menandai bahwa suatu baris sudah diperiksa petugas berwenang, beserta siapa dan kapan. Data yang sudah terverifikasi lalu diubah akan kembali berstatus `Belum Diverifikasi` agar diperiksa ulang.

### 1a.11 Verifikasi otomatis merusak makna indikator mutu data

Saat membahas alur verifikasi, muncul pertanyaan wajar: bila petugas dinas sendiri yang memasukkan data, apakah otomatis terverifikasi?

Matriks kewenangan memang memberi Dinas hak `T` (tambah) sekaligus `V` (verifikasi) pada modul yang sama, sehingga secara teknis dinas dapat memverifikasi data yang ia masukkan sendiri. Ini memunculkan dua tafsir yang sama-sama masuk akal:

| Tafsir | Konsekuensi |
|---|---|
| Verifikasi = pemeriksaan ketelitian | Verifikasi diri sendiri tidak bermakna, sebab orang cenderung membaca apa yang ia niatkan tulis |
| Verifikasi = persetujuan otoritas | Verifikasi diri sendiri bermakna, sebab dinas menjamin data tersebut |

**Bahayanya** terletak pada indikator dashboard. Bila data input dinas otomatis terverifikasi, angka "74% data terverifikasi" bergeser maknanya:

- dari **"74% data sudah diperiksa"**,
- menjadi **"74% data kebetulan dimasukkan orang yang punya hak verifikasi"**.

Indikator mutu data menjadi menyesatkan, terutama saat dilaporkan ke Kementerian.

**Keputusan (2026-08-11):** tidak ada verifikasi otomatis. Data baru selalu berstatus `Belum Diverifikasi`. Sebagai penyeimbang, petugas berizin verifikasi mendapat tombol **"Simpan dan Verifikasi"** agar cukup sekali klik. Meski begitu, sistem mencatatnya sebagai **dua entri audit log terpisah**, sehingga verifikasi tetap terlacak sebagai tindakan tersendiri.

**Yang belum diterapkan: prinsip empat mata.** Sistem masih memperbolehkan petugas memverifikasi data yang ia masukkan sendiri. Pembatasan ini lazim dalam tata kelola data, tetapi berisiko menghambat bila tim dinas di kawasan hanya beberapa orang. Bila kelak jumlah petugas memadai, aturan dapat diperketat **tanpa mengubah skema**, cukup membandingkan pelaku verifikasi terhadap penginput asli yang terekam pada `audit_log`.

---

## 1b. Penyajian Statis di GitHub Pages (2026-08-17)

Antarmuka Tahap 2 diterbitkan sebagai **berkas statis** ke GitHub Pages agar dapat ditinjau tim dan dinas tanpa biaya, tanpa kartu kredit, dan tanpa laptop pengembang harus menyala. Alamatnya `https://abyan28.github.io/transmigrasi/`, diperbarui otomatis setiap `git push` ke `main`.

Pilihan ini masuk akal **justru karena Tahap 2 belum punya backend**: seluruh isi halaman berasal dari `app/Support/DummyData.php`, tidak ada satu pun kueri basis data, dan tidak ada autentikasi. Aplikasi hanya dijalankan sebentar di runner, digilas menjadi HTML, lalu HTML-nya yang disajikan.

### 1b.1 Cara kerjanya

`.github/workflows/deploy.yml` menjalankan: pasang PHP 8.2 dan Node, `composer install`, `npm run build`, `php artisan serve`, lalu menggilas setiap alamat dari `php artisan sim:tautan-statis` menjadi `folder/index.html`. Alamat tetap bersih tanpa akhiran `.html`, sama persis dengan versi yang dilayani Laravel.

Perintah `sim:tautan-statis` (`app/Console/Commands/DaftarTautanStatis.php`) membangkitkan daftar dari sumbernya langsung: rute GET tanpa parameter, ditambah halaman rincian yang dijabarkan dari `DummyData`. **Sengaja tidak ditulis tangan**, supaya penambahan data contoh tidak diam-diam meninggalkan halaman yang tidak ikut terbit. Hasil per 2026-08-17: **113 halaman, seluruhnya membalas 200.**

Dua alamat dikecualikan lewat konstanta `DIKECUALIKAN`: `uji-403` yang memang sengaja membalas 403, dan `up` yang merupakan pemeriksa kesehatan bawaan Laravel, bukan halaman.

### 1b.2 Sub-path, sumber masalah terbesar

GitHub Pages menyajikan repositori di `/transmigrasi/`, bukan di akar domain. Akibatnya `route()`, `url()`, dan `asset()` yang menghitung akar dari request kehilangan awalan itu, dan **seluruh tautan beserta gambar rusak**.

Penanganannya di `AppServiceProvider::samakanAlamatDasar()`:

| Fungsi | Ditangani oleh |
|---|---|
| `asset()` | `config('app.asset_url')`, kunci baru pada `config/app.php` |
| `route()` dan `url()` | `forceRootUrl()` |
| Skema `https` | `forceScheme('https')`, hanya bila `ASSET_URL` berskema https |

**Hanya aktif bila `ASSET_URL` diisi.** Pengembangan di localhost, akses lewat jaringan lokal, dan terowongan Cloudflare tidak menyetel variabel ini, sehingga perilakunya sama sekali tidak berubah: akar tetap diambil dari request yang masuk. Sudah diverifikasi untuk ketiga skenario.

Penyamaan skema perlu karena penggilasan berjalan lewat `php artisan serve` yang berbicara http, sedangkan hasilnya disajikan lewat https. Tanpa itu `route()` mencetak http sementara `asset()` mencetak https, dan peramban memblokir asetnya sebagai muatan campuran.

### 1b.3 Path absolut yang harus dibersihkan

Peninggalan template TailAdmin berupa **24 path absolut** di 11 berkas (`src="/images/..."`, `href="/"`, favicon) diganti menjadi `asset()` dan `route()`. Ditemukan pula dua sumber lain saat pengujian:

- **`layouts/sidebar.blade.php`** � 25 alamat menu dari `MenuHelper` dipakai mentah sebagai `href`. Dibungkus `url()`. Nilai `path` di `MenuHelper` **sengaja dibiarkan relatif**, sebab dipakai juga untuk membandingkan status menu aktif; mengubahnya menjadi alamat lengkap akan merusak penandaan itu.
- **`components/sim/stat-card.blade.php`** � atribut `url` dipakai mentah. Dibungkus `url()`, dengan pengecualian untuk alamat yang sudah memuat skema.

**Aturan untuk pengerjaan selanjutnya: jangan pernah menulis `href="/sesuatu"` atau `src="/images/..."` secara langsung.** Selalu lewat `route()`, `url()`, atau `asset()`. Bila tidak, tautannya akan rusak di GitHub Pages sementara tetap tampak benar di localhost, dan kesalahan seperti ini tidak tertangkap uji berbasis HTTP.

### 1b.4 Jebakan `public/hot`

Ditemukan saat pengujian: bila `public/hot` ikut terbawa, `@vite` mengalihkan seluruh aset ke `localhost:5173` dan situs terbit **tanpa gaya sama sekali**. Berkas itu dibuat `npm run dev` dan sudah masuk `.gitignore`, tetapi alur kerja tetap menghapusnya sebagai pengaman.

Hal yang sama berlaku saat memakai terowongan Cloudflare: jangan jalankan `npm run dev` selagi demo berlangsung.

### 1b.5 Yang tidak berfungsi pada versi statis

1. **Seluruh tombol simpan, ubah, dan hapus.** 70 rute POST/PUT/DELETE hanya `return back()` tanpa menyimpan apa pun. Pada versi ber-PHP tombol memunculkan pesan; pada versi statis tidak terjadi apa-apa. **Bukan kemunduran**, sebab keduanya sama-sama tidak menyimpan.
2. **Pencarian dan penyaringan tabel.** **DIKOREKSI 2026-08-25.** Keterangan semula berbunyi "memang belum berfungsi sejak awal", dan itu **sudah tidak benar**: sejak gelombang 2 seluruh halaman daftar menyaring sungguhan lewat `request()` di blok PHP masing-masing. Terverifikasi, `/alsintan` menampilkan 10 baris sedangkan `/alsintan?kondisi=Baik` menampilkan 8.

   Yang tidak berfungsi adalah **versi statisnya**, dan sebabnya struktural: GitHub Pages tidak menjalankan PHP, sedangkan langkah penggilasan hanya mengambil alamat tanpa query string. Maka yang terbit hanyalah `/alsintan/index.html` versi tanpa saringan, dan menekan Cari mengubah alamat di bilah peramban tanpa mengubah isi halaman.

   Sengaja **tidak diakali**. Pola tautan tetap yang menolong lacak pengaduan dan tab rekap panen tidak dapat dipakai di sini, sebab kombinasi filter berlipat: SP dikali kondisi dikali kategori menghasilkan ratusan halaman untuk isi yang sama. Batasan ini lenyap sendiri saat Tahap 3 pindah ke hosting ber-PHP, sesuai 1b.7.
3. **Penyaring dashboard.** Formulir GET yang belum menyaring apa pun.

### 1b.6 Lacak pengaduan dan utang yang ditinggalkan

Halaman lacak semula hanya menerima `?nomor=`, yang tidak dapat dilayani berkas statis. Ditambahkan rute **tautan tetap** `/lacak-pengaduan/{nomor}`, sehingga setiap nomor punya halaman sendiri dan ikut tergilas. Formulir diarahkan ke sana lewat `x-on:submit` bertanda `ponytail:`.

Rancangannya dibuat agar mudah dibongkar:

- Blok PHP di `lacak.blade.php` **tidak diubah logikanya**, hanya ditambah pembacaan `$nomorRute`.
- Bila JavaScript mati, atribut terabaikan dan formulir kembali mengirim GET seperti biasa.
- Kueri `?nomor=` lama tetap bekerja.

**Pada Tahap 8**, ketika controller pengaduan mengambil alih: hapus atribut `x-on:submit` beserta komentar `ponytail:` di atasnya. Rute tautan tetap sebaiknya **dipertahankan**, karena hasil pencarian yang dapat ditandai dan dibagikan tetap berguna pada versi ber-backend.

### 1b.6a Tiga temuan setelah penerbitan pertama (2026-08-18)

**Rincian SP membalas 404 di situs terbit.** `DaftarTautanStatis` menyebut kunci `no_sp`, padahal kunci sebenarnya pada `DummyData::satuanPermukiman()` adalah `id_satuan_permukiman`. Sepuluh peta lainnya sudah benar; hanya baris ini yang salah.

Yang membuatnya berbahaya bukan salah ketiknya, melainkan `isset($baris[$kunci])` yang membungkusnya. Kunci yang tidak ada membuat kondisi bernilai salah, sehingga keenam halaman **dilewati tanpa suara**: penggilasan tetap hijau, penerbitan tetap sukses, dan kekeliruannya baru muncul sebagai 404 di tangan pengguna. Pemeriksaan diganti `array_key_exists` yang melempar `RuntimeException` beserta daftar kunci yang tersedia.

> **Aturan:** pada perkakas pembangkit daftar, kunci yang tidak dikenal wajib menghentikan proses. Melewati diam-diam menukar kegagalan yang terlihat saat build dengan kegagalan yang tidak terlihat sampai produksi.

**Modal ikut menggulir sampai tenggelam.** Seluruh lapisan memanggil `document.body.classList.add('overflow-hidden')`. Namun `layouts/app.blade.php` menetapkan `<html class="h-full">` sedangkan `<body>` tidak diberi tinggi, sehingga elemen yang benar-benar menggulir adalah `<html>`. Penguncian pada `<body>` karena itu tidak mengunci apa pun.

Polanya tersalin ke delapan berkas, dan `confirm-dialog.blade.php` yang dipakai 24 kali malah tidak mengunci sama sekali. Diganti satu modul bersama `resources/js/kunci-gulir.js`, diekspos sebagai `window.kunciGulir`, dipakai sembilan komponen.

Dua hal yang membuat modul ini tidak sesederhana satu penanda:

- **Penghitung lapisan.** Dialog konfirmasi dapat dibuka dari dalam modal formulir. Bila setiap penutupan langsung melepas kunci, gulir terbuka kembali padahal modal di bawahnya masih tampil.
- **Penjaga `if (! this.terbuka) return`** pada setiap `tutup()`. Peristiwa `tutup-modal.window` disiarkan ke seluruh modal di halaman sekaligus, sehingga tanpa penjaga ini puluhan modal yang sedang tertutup ikut memanggil `lepas()` dan penghitungnya jatuh ke bawah nol.

Padding pengganti lebar bilah gulir dipasang agar tata letak tidak melompat mendatar saat bilah gulir menghilang.

**Tab rekap panen tidak dapat dibuka.** Pemilihnya memakai `?kelompok=`, dan kueri tidak dilayani berkas statis. Ditambahkan tautan tetap `/panen/rekap/{kelompok}` dengan pola sama seperti lacak pengaduan, dibatasi `where` pada empat nilai yang sah. Rute wajib didaftarkan **sebelum** `/panen/{id}`, sebab `routes/web.php` sudah mencatat bahwa `/panen/rekap` dapat tertangkap sebagai id.

Nilai kelompoknya ditulis langsung pada `DaftarTautanStatis`, tidak dibangkitkan dari data, sebab pilihan tab ditentukan tampilan bukan isi data. Daftar itu wajib dijaga sejalan dengan batasan `where` pada rutenya.

Setelah ketiganya diperbaiki, jumlah halaman terbit naik dari 113 menjadi **122**.

### 1b.7 Yang harus dilakukan saat backend masuk

Begitu Tahap 3 dan seterusnya berjalan, sistem memerlukan PHP dan basis data yang hidup, sehingga **GitHub Pages tidak lagi memadai**. Yang perlu diputuskan saat itu:

1. **Autentikasi mematikan penggilasan.** Setelah Tahap 3 aktif, halaman yang butuh login akan membalas pengalihan ke `/login`, bukan 200, dan penerbitan gagal. Pilihannya: batasi daftar gilas hanya ke halaman publik, atau hentikan penerbitan statis sama sekali.
2. **Pindah ke hosting ber-PHP.** `prd.md` A9 sudah menetapkan hosting dengan SSL dan cadangan terjadwal. Alur kerja ini dapat dihapus atau dialihkan menjadi penerbitan pratinjau saja.
3. **Yang tetap berguna** meski beralih hosting: penyeragaman `asset()`, `url()`, dan `route()` pada 1b.3, serta kepercayaan pada `X-Forwarded-*` di `bootstrap/app.php`. Keduanya justru **syarat** untuk hosting di belakang reverse proxy.

### 1b.8 Ketergantungan yang perlu diingat

Pengaturan GitHub Pages harus disetel sekali secara manual: **Settings ? Pages ? Source: GitHub Actions**. Tanpa itu alur kerja berjalan tetapi hasilnya tidak terbit.

---

## 1c. Penalaran Melingkar Berbasis Data Contoh (2026-08-19)

Ditegur pemilik proyek: keputusan struktur berulang kali disandarkan pada `DummyData`, padahal berkas itu dikarang AI sendiri. Kalimat "skenario itu tidak ada pada data contoh" karena itu tidak pernah menjadi fakta tentang Kobalima Timur, melainkan fakta tentang apa yang terlintas saat menuliskannya.

Aturannya kini tertulis pada `rules.md` 19a. Bagian ini menyimpan buktinya agar larangan itu tidak terbaca sebagai kehati-hatian berlebihan.

### 1c.1 Mengapa data contoh secara struktural tidak dapat menjawab

Tiga sifat berikut melekat pada `DummyData` dan tidak dapat dihilangkan dengan menambah baris:

1. **Potret sesaat tanpa sumbu waktu.** Pengaduan menumpuk, dokumen bertambah, kerusakan berulang. Seluruh "banyak" yang lahir dari perjalanan waktu mustahil tampak pada larik statis.
2. **Sengaja dibuat minimal.** Catatan bertanggal 2026-08-14 dan 2026-08-17 berulang kali menyatakan suatu baris "sengaja dibiarkan kosong agar keadaan kosong ikut teruji". Ketiadaan sebuah kasus karena itu adalah **keputusan desain**, bukan temuan. Memakainya sebagai bukti sama dengan menaruh barang di laci lalu terkejut menemukannya di sana.
3. **Dikalibrasi ke tampilan, bukan ke lapangan.** 8 KK melawan sekitar 1.140 KK yang disebut `prd.md`, rasio 1:140.

Ditambah satu bentuk yang baru terlihat pada audit 2026-08-19 dan **arahnya terbalik** dari ketiganya:

4. **Data karangan mengalahkan alasan lapangan yang sudah ditulis benar.** Bukan sekadar menyimpulkan dari data contoh ketika alasan lain belum ada, melainkan **membatalkan** alasan lapangan yang sudah tercatat demi menyesuaikan dokumen pada bentuk `DummyData`. Ini yang paling sulit terlihat, sebab menyamar sebagai perapian ketidakkonsistenan antara dokumen dan kode. Pembedanya satu pertanyaan: **mana yang disesuaikan pada mana?** Bila dokumen yang mengalah pada data contoh, arahnya sudah salah.

### 1c.2 Enam pelanggaran yang tercatat

Tiga yang pertama ditemukan bersamaan pada 2026-08-19. Dua berikutnya ditemukan pada **audit menyeluruh** hari yang sama, yang menyisir seluruh keputusan `notes.md` terhadap `rules.md` 19a.

**Pertama, tautan objek pengaduan (2026-08-19).** Tabel `pengaduan_objek` sempat ditolak dengan alasan "dari 5 pengaduan contoh, tidak satu pun menyangkut lebih dari satu objek". Pernyataan itu **salah pada data contoh itu sendiri**: `PGD-2026-0001` berbunyi *"saluran irigasi tersumbat ... air tidak sampai ke lahan usaha"* dan `PGD-2026-0004` berbunyi *"longsor menutup sebagian jalan produksi menuju lahan usaha"*. Dua dari lima baris, masing-masing menyebut dua objek dalam satu kalimat.

Yang memberatkan: `PGD-2026-0004` justru dikutip sendiri pada paragraf sebelumnya sebagai bukti "kategori tidak sama dengan objek". Baris yang sama dibaca teliti untuk satu keperluan, lalu buta untuk keperluan lain. Itu bukan pemeriksaan data, melainkan pencarian pembenaran atas kesimpulan yang sudah diambil.

**Kedua, dokumen lahan (butir b735).** Pemisahan `dokumen_lahan` dibatalkan alasannya dengan kalimat "dari 6 bidang, tidak satu pun memiliki lebih dari satu dokumen, skenario yang menjadi seluruh alasan pemisahan tidak pernah ada". Padahal `data-dictionary.md` 7.2 sampai kini berbunyi sebaliknya, dan secara lapangan sertifikasi transmigrasi memang berlapis: Surat Keterangan Pembagian Tanah lebih dulu, sertifikat menyusul bertahun kemudian. Satu bidang **pasti** melewati dua dokumen. Yang tidak punya sumbu waktu adalah data contohnya, bukan tanahnya. Kesimpulan akhirnya kebetulan tetap benar sebab tabelnya dipertahankan, tetapi penalarannya sudah cacat sejak itu.

**Ketiga, ambang searchable dropdown (butir b789).** Ambang 8 opsi disetel lalu dibenarkan dengan "dengan data contoh sekarang, `/rumah` dan `/riwayat-tanam` sengaja tidak menampilkannya sebab daftarnya masih 4 dan 6 baris". Empat baris di bawahnya tertulis pengakuan sendiri bahwa "PRD menyebut 1.140 KK, sedangkan data contoh hanya 8". Jurangnya dilihat, dituliskan, lalu kalibrasinya tetap mengikuti data contoh.

**Keempat, peruntukan lahan bertahap (2026-08-18).** Enum `PeruntukanLahan` dipecah menjadi `Lahan Usaha I` dan `Lahan Usaha II` dengan alasan "YOHANES BERE tercatat memiliki 3 bidang, tetapi dua di antaranya bernama sama persis tanpa pembeda apa pun", diperkuat kalimat "dua kekeliruan nyata ditemukan, keduanya **terbukti dari data proyek sendiri**".

**Inilah satu-satunya yang kerusakannya sudah nyata, bukan potensi.** Enum dipasang, data contoh disesuaikan, lalu dicabut pada hari yang sama setelah pemilik proyek menyatakan keadaan sebenarnya: satu transmigran menerima **satu** pekarangan dan **satu** lahan usaha.

Yang membuatnya lebih dalam: keterangan lapangan itu sekaligus membatalkan keputusan **2026-08-10** yang berbunyi "boleh lebih dari satu lahan usaha, kondisi lapangan" � yang ternyata juga tidak pernah berdasar lapangan. Satu penalaran melingkar menutupi penalaran melingkar lain selama delapan hari, dan keduanya baru terbongkar oleh satu kalimat pemilik proyek.

**Kelima, kontak poktan (2026-08-17).** Kolom kontak kelompok diganti menjadi kontak ketua dengan alasan "kontak poktan **ternyata sudah lama menjadi kontak ketua di dalam kode**, hanya dokumennya yang menyebut lain".

Ini bentuk keempat pada 1c.1: alasan itu **membatalkan keterangan lapangan yang sudah ditulis benar** pada bagian 1a.6, yaitu "kontak kelompok bisa berbeda dari kontak pribadi ketua". Dokumen disesuaikan pada data karangan, bukan sebaliknya. Jejaknya sempat menyebar ke `data-dictionary.md` 8.1 dan `rules.md` 7a.2b sebelum diperbaiki.

Keputusannya **kebetulan tetap benar** setelah dikonfirmasi pemilik proyek pada 2026-08-19: poktan di Kobalima Timur memang tidak memiliki kontak sendiri. Tetapi kebenaran itu baru diketahui **dua hari setelah** keputusannya diambil, dan selama itu dasarnya tidak sah.

**Keenam, ambang searchable dropdown, DAN INI PELANGGARAN YANG SAMA UNTUK KETIGA KALINYA (2026-08-20).** Butirnya persis butir ketiga di atas. Yang membuatnya perlu dicatat terpisah bukan kekeliruannya, melainkan **berapa kali ia lolos**:

| Tanggal | Kejadian |
|---|---|
| 2026-08-17 | Ambang 8 disetel, dibenarkan dengan menghitung baris `DummyData` |
| 2026-08-19 | Ditandai sebagai pelanggaran pada 1c.2, dicatat sebagai tindak lanjut butir 10 bagian 4, dan kalimat pembenarannya dicoret |
| 2026-08-20 | **Dipakai lagi sebagai pembenaran**: "poktan baru 4 baris pada data contoh, sehingga tetap tampil sebagai dropdown biasa untuk sementara" |

Pemilik proyek yang membongkarnya, dan pertanyaannya menohok tepat pada lingkarannya: *"kenapa ada ambang 8, sedangkan yang buat seberapa banyak data dummy itu kamu sendiri?"*

Ada dua kekeliruan bertumpuk di sini, dan yang kedua lebih halus:

1. **Ambangnya dibandingkan terhadap data karangan sendiri.** Ini bentuk pertama pada 1c.1, sudah dikenali dan tetap terulang.
2. **Pertanyaan pengguna dijawab dengan fakta tentang kode.** Pemilik proyek bertanya "mana yang belum searchable" � pertanyaan tentang **yang terlihat di layar**. Yang dijawab adalah "mana yang belum memakai komponen `pilih-cari`" � pertanyaan tentang **kode**. Keduanya terdengar sama tetapi berbeda jawabannya, sebab komponen yang terpasang pun tidak menampilkan kotak pencarian selama di bawah ambang. Catatan hasil pengerjaan lalu menyatakan dua isian "sudah searchable", dan pernyataan itu **benar tentang kode tetapi menyesatkan tentang layar**.

Usulan pertama untuk memperbaikinya juga keliru arah: "perbanyak data contoh menjadi 8 agar kotak pencarian muncul". Itu mengarang data agar cocok dengan ambang, bukan memperbaiki ambang yang memang tidak berdasar.

**Kriteria yang benar dirumuskan pemilik proyek sendiri:** *"ketika ada kemungkinan data/opsi di dropdown itu bertambah dari tambah data, maka sudah sewajarnya pakai searchable dropdown."* Itu pertanyaan tentang **sifat sumbernya**, bukan tentang jumlah barisnya hari ini � dan ternyata **sudah menjadi kalimat pembuka `ui-spec.md` 6.0a sejak awal**. Ambang pada butir 5 bertentangan dengan kalimat pembukanya sendiri selama tiga hari tanpa ada yang menyadarinya.

**Pelajaran yang berbeda dari lima pelanggaran sebelumnya:** kelimanya ditemukan lewat audit terhadap dokumen. Yang keenam ini hanya dapat ditemukan oleh **orang yang benar-benar memakai sistemnya**, sebab gejalanya tidak tertulis di mana pun � ia hanya terlihat sebagai dropdown yang tidak punya kotak cari. Audit dokumen tidak akan pernah menangkapnya.

### 1c.3 Mengapa pelajaran 2026-08-18 tidak menahannya

Catatan bertanggal 2026-08-18 sudah memuat pelajaran yang tepat: *"bertanya lebih dulu lebih murah daripada menyimpulkan"*. Ia tidak bekerja sebab ditulis terlalu sempit, hanya berlaku "untuk hal yang bergantung praktik dinas setempat", sehingga tidak terpicu ketika pertanyaannya menyangkut kardinalitas.

Pelajaran yang dicatat terlalu spesifik tidak akan pernah menahan kesalahan berikutnya, sebab kesalahan berikutnya selalu datang dalam bentuk yang sedikit berbeda. `rules.md` 19a karena itu ditulis sebagai pembedaan jenis pertanyaan, bukan sebagai daftar topik.

### 1c.4 Hasil audit menyeluruh (2026-08-19)

Seluruh 992 baris `notes.md` disisir terhadap `rules.md` 19a. **36 keputusan** menyebut data contoh sebagai alasan:

| Golongan | Jumlah | Tindakan |
|---|---|---|
| **Cacat, menyangkut struktur data** | 5 | Alasannya diperbaiki atau ditandai dicabut |
| Ragu, menyangkut struktur data | 4 | Ditinjau; seluruhnya bertahan dengan alasan yang sah |
| Cacat atau ragu, hanya tampilan | 4 | Satu ditandai perlu tinjau ulang saat data nyata masuk |
| **Sah** � menjawab pertanyaan tentang kode | 23 | Tidak perlu tindakan |

Dua puluh tiga yang sah menunjukkan aturan ini **tidak melarang memakai data contoh**, hanya melarang memakainya untuk pertanyaan yang salah. Menghitung "96 pemakaian `x-cloak` mati", "19 dari 41 rute tidak pernah diuji", atau "nilai enum `'Milik Pribadi'` tidak cocok dengan `'Pribadi'`" seluruhnya sah, sebab ketiganya pertanyaan tentang kode.

**Dua pertanyaan lapangan yang muncul dari audit dijawab pemilik proyek, bukan disimpulkan:**

| Pertanyaan | Jawaban | Akibat |
|---|---|---|
| Apakah poktan punya kontak sendiri? | Tidak | Keputusan 2026-08-17 bertahan, alasannya diperbaiki |
| Perlukah impor massal musim tanam? | Perlu | Pengecualian dicabut, fitur impor ditambahkan |

Inilah perilaku yang diminta 19a poin 11, dan justru pertanyaan kedua membuktikan nilainya: pengecualian musim tanam dari impor **memang keliru**, dan tidak akan pernah ketahuan bila alasannya tidak diperiksa.

### 1c.5 Aturan yang dilanggar oleh penulisnya sendiri

Hal yang paling perlu dicatat dari audit ini: `rules.md` 19a **sudah ada** ketika dua pelanggaran terakhir ditemukan, dan `notes.md` 1c.1 poin 1 tentang "tanpa sumbu waktu" **sudah tertulis 400 baris di atas** kalimat yang melanggarnya (pengecualian musim tanam dari impor).

Menulis aturan tidak cukup. Yang kurang adalah **kebiasaan memeriksa aturan itu terhadap pekerjaan sendiri**, dan audit berkala semacam inilah bentuknya. Karena itu audit ini dicatat sebagai kegiatan yang dapat diulang, bukan sebagai perbaikan sekali jalan.

---

## 1d. Fitur Lulus Uji tetapi Tidak Dapat Dipakai (2026-08-19)

Ditemukan pemilik proyek beberapa saat setelah tautan objek pengaduan dinyatakan selesai: pada halaman rincian pengaduan hanya tampil daftar objek yang sudah tertaut, tanpa satu pun cara menautkannya. Pemeriksaan membenarkan hal itu dan menemukan **empat cacat**, seluruhnya lolos dari 449 uji yang hijau.

### 1d.1 Empat cacat

| # | Cacat | Akibat |
|---|---|---|
| 1 | Isian hanya menerima **satu objek** | Kejamakan yang menjadi seluruh alasan tabel `pengaduan_objek` tidak dapat dijalankan petugas |
| 2 | Isian hanya ada di dalam modal penanganan | Pengaduan berstatus Selesai **tidak dapat ditaut sama sekali**, sebab modal itu tidak dirender ketika tidak ada status lanjutan |
| 3 | Form ubah pengaduan tanpa isian objek | Menyunting laporan tidak menyentuh objeknya |
| 4 | Tidak ada tombol mencabut tautan | Salah taut bersifat permanen |

Cacat 1 yang paling telak. Data contohnya jamak, tabelnya jamak, tampilan rinciannya jamak, uji integritasnya memeriksa kejamakan � **hanya isiannya yang tunggal**. Petugas tidak akan pernah dapat menghasilkan data seperti `PGD-2026-0004` yang justru dipajang sebagai contoh pembenaran tabel ini.

Cacat 2 lebih memalukan lagi: aturan `rules.md` 10b.6h yang berbunyi *"tautan boleh disunting kapan saja, tidak dikunci status"* ditulis pada hari yang sama, lalu dilanggar pada berkas berikutnya.

### 1d.2 Mengapa 449 uji tidak menangkapnya

**Sebab pertama, uji memeriksa keberadaan string, bukan kemampuan.** Ketiga uji yang ditulis berbunyi seperti ini:

```php
expect($isi)->toContain('name="objek_tipe"');     // ada? ya. jamak? tidak diperiksa
expect($isi)->toContain('value="belum_terdata"'); // ada? ya
```

Tidak satu pun menanyakan apakah petugas dapat **menambah objek kedua**. Ini persis kekeliruan yang sudah tercatat pada butir b799 pada 2026-08-17 � *"uji Pest hanya memastikan sebuah string ada di HTML, bukan bahwa tampilannya masuk akal"* � dan terulang dua hari kemudian.

**Sebab kedua, id contoh yang kebetulan menguntungkan.** Uji membaca `/pengaduan/1` yang berstatus Diproses, sehingga modal penanganannya dirender dan isian objeknya ikut terbaca. Bila membaca `/pengaduan/5` yang berstatus Selesai, uji yang sama akan memerah. Memilih satu baris contoh tanpa alasan berarti menguji keadaan yang paling ramah, bukan keadaan yang paling mungkin gagal.

**Akar keduanya sama:** yang diuji adalah **apa yang dibangun**, bukan **apa yang dijanjikan**. Uji disusun setelah kode selesai dengan membaca kode itu sendiri, sehingga ia hanya dapat menemukan hal yang sudah diketahui penulisnya.

### 1d.3 Yang diperbaiki

- Isian menjadi **daftar baris** ber-`objek[i][tipe]`, dengan tombol Tambah objek dan tombol cabut per baris.
- Kedua pernyataan dipindah **ke dalam** dropdown jenis, sehingga satu laporan dapat memuat objek tertaut sekaligus pernyataan, persis `PGD-2026-0004`.
- Modal **Kelola Objek** tersendiri, dirender tanpa memeriksa status, dan terisi tautan yang sudah ada agar dapat disunting. Modal penanganan tetap memuat isian yang sama sebagai jalan pintas.
- Rute `POST /pengaduan/{id}/objek`.
- `rules.md` 10b ditambah poin **6h-1** dan **6h-2** agar kedua cacat ini tidak dapat terulang tanpa melanggar aturan tertulis.

### 1d.4 Uji peramban dijadikan syarat, bukan pelengkap

Ditambahkan `tests/Browser/uji-isian-objek.mjs`, dijalankan lewat Edge headless dan protokol DevTools **tanpa menambah dependensi**. Enam pemeriksaan menguji perilaku, bukan markup: menambah baris benar-benar menambah, mencabut benar-benar mengurangi, memilih pernyataan benar-benar menyembunyikan pemilih aset, dan pengaduan berstatus Selesai benar-benar dapat ditaut.

Nilainya terbukti seketika: uji ini **langsung memerah** pada pemeriksaan terakhir saat pertama dijalankan, yaitu pemeriksaan yang tidak dapat ditiru satu pun uji string.

Dua jebakan ditemukan saat menyusunnya, dan keduanya layak dicatat:

- **`offsetParent` tidak dapat dipakai memeriksa keterlihatan** pada elemen di dalam panel berposisi `fixed`; nilainya `null` meski elemennya tampil. Diganti `getClientRects().length > 0`. Pemeriksaan pertama memerahkan halaman yang sebenarnya sehat.
- **Modal wajib ditutup sebelum berpindah halaman.** Penguncian gulir milik modal lama masih menempel saat halaman berikutnya dimuat, sehingga panel yang baru terbaca belum bergeometri.

Berkas `uji-combobox.mjs` di akar proyek ternyata **kosong 0 byte**, sisa yang tidak terbersihkan dari pekerjaan 2026-08-17. Dihapus, dan uji peramban kini ditempatkan pada `tests/Browser/` agar tidak lagi tercecer di akar.

---

## 1e. Tautan Objek Dicabut, Bidang Menggantikannya (2026-08-19)

Ditetapkan pemilik proyek beberapa jam setelah tautan objek diperbaiki: fitur pengelolaan objek **ditiadakan seluruhnya**, dan halaman daftar pengaduan diberi filter bidang.

### 1e.1 Mengapa dicabut

Alasannya bukan cacat pelaksanaan, melainkan pergeseran dasar keputusan. Sepanjang pembahasan hari itu ditetapkan bahwa **satu laporan ditangani satu dinas**. Begitu itu berlaku, pertanyaan yang perlu dijawab sistem berubah: bukan lagi "benda apa saja yang rusak", melainkan **"dinas mana yang menangani"**. Mengelola daftar objek per laporan menjadi pekerjaan tambahan yang tidak menjawab pertanyaan itu.

Penghapusan dipilih **menyeluruh**, bukan sekadar mencabut isiannya. Bila hanya cara mengisinya yang dihapus, tab Pengaduan Terkait pada sembilan halaman rincian dan rekap aset akan selamanya kosong pada sistem nyata, sebab tidak ada lagi jalan menautkan. Itu kontrol mati yang dilarang R-26.

### 1e.2 Yang ikut hilang dan yang dipertahankan

| Dihapus | Dipertahankan |
|---|---|
| `ObjekPengaduan`, `pengaduan_objek`, komponen, partial isian | **Halaman rincian Inventaris SP & Fasilitas SP** beserta rute dan tombolnya |
| 7 metode `DummyData`, tab pada 9 halaman rincian | Pemecahan kategori `Inventaris SP` / `Fasilitas SP` |
| Rekap "Aset paling sering diadukan", rute `pengaduan.objek` | Uji privasi halaman lacak publik |
| 23 uji, `tests/Browser/uji-isian-objek.mjs` | `rules.md` 16.0a tentang uji yang menyasar janji |

Kedua halaman rincian SP dipertahankan sebab lahir bersama fitur ini tetapi **sudah berdiri sendiri**: keduanya menampilkan rincian aset dan Catatan Log, dan dijaga tiga uji tersendiri.

Tab lama pada rincian infrastruktur yang menampilkan seluruh pengaduan se-SP **tidak dikembalikan**. Komentarnya sendiri dulu mengakui itu menyesatkan: keluhan atas jalan produksi ikut muncul pada halaman sumur bor.

### 1e.3 Bidang menggantikan objek

Penentu dinas kini kembali ke kategori, tetapi dengan tiga perbedaan penting dari bentuk semula:

1. **`dariKategori()` bertipe `?self`.** Empat kategori sengaja mengembalikan `null`: lahan usaha, infrastruktur, bencana, dan lainnya. Pokok masalahnya dapat jatuh ke dua dinas sekaligus, sehingga menebak justru menyesatkan; laporan akan masuk ke daftar dinas yang keliru lalu tertahan di sana.
2. **Nilai turunan dapat ditimpa.** Isian bidang berupa pilihan, bukan tampilan baca-saja seperti sebelumnya. Pilihan manual dijaga penanda `disentuh` agar tidak tertimpa ketika kategori disunting kemudian.
3. **Kolom `bidang` menjadi nullable**, wajib terisi sebelum status maju ke Diproses.

Ditambah kategori **Saprotan**, sebab keluhan bibit, pupuk, dan obat sebelumnya menumpang pada Produksi Panen. Jumlah kategori 10 menjadi **11**.

### 1e.4 Cakupan data kedua dinas sengaja tidak simetris

Dinas Transmigrasi bercakupan `Semua`, Dinas Pertanian bercakupan `Per Bidang`. Alasannya dinyatakan pemilik proyek: **sistem ini milik Dinas Transmigrasi** sebagai pengelola kawasan, dan merekalah yang menyaring laporan berbidang kosong.

Konsekuensi yang diterima sadar: satu-satunya jalan laporan sampai ke Dinas Pertanian adalah lewat penetapan Admin atau Dinas Transmigrasi. Bila keduanya lambat meninjau, laporan hama atau alsintan rusak akan menunggu. Peredamnya dua, keduanya sudah terpasang: filter bidang menyediakan pilihan **Belum ditentukan beserta jumlahnya**, dan kolom bidang yang kosong ditulis sebagai keterangan bertanda gold, bukan sel hampa yang terbaca sebagai data gagal termuat.

### 1e.5 Tiga tempat yang mengunci daftar kelompok rekap

Nilai `objek` pada tab rekap terkunci di **tiga berkas sekaligus**: batasan `where` pada rute, `$labelKelompok` pada view, dan larik pada `DaftarTautanStatis`. Ketiganya wajib diubah serentak; mengubah dua di antaranya membuat penerbitan statis membalas 404, dan berbeda dari peta rincian, tidak ada `array_key_exists` yang melempar galat sebagai penjaga.

### 1e.6 Kategori Kelompok Tani terlewat

Ditemukan pemilik proyek setelah pekerjaan bidang dinyatakan selesai: **kategori pengaduan tidak memuat poktan**, padahal poktan adalah modul penuh dengan halaman daftar, rincian, pengelolaan anggota, dan empat kewenangannya sendiri.

Sebabnya kelalaian bertanya. Pada penetapan peta bidang, pemilik proyek menulis *"untuk kategori poktan, alsintan, saprotan, dan panen otomatis bidang pertanian"*. Dua di antaranya ternyata belum ada sebagai kategori, yaitu saprotan dan poktan. Saprotan ditanyakan dan ditambahkan; **poktan tidak ditanyakan dan ikut terlupa**. Memeriksa satu ketidakcocokan lalu berhenti, padahal daftarnya belum habis diperiksa.

Akibatnya bukan sekadar pilihan yang kurang. Keluhan soal kelompok tani terpaksa masuk kategori `Lainnya`, dan `Lainnya` justru **berbidang kosong** sehingga setiap laporan semacam itu menambah antrean penyaringan Dinas Transmigrasi, padahal urusannya jelas milik Dinas Pertanian.

**Penyisiran menyeluruh** atas 26 fitur berkewenangan lalu dilakukan atas permintaan pemilik proyek, dan hasilnya **tepat satu yang terlewat**. Modul yang sengaja tidak berkategori beserta alasannya kini tercatat pada `data-dictionary.md` �11.21, dan kewajiban pemetaan lengkap dua arah pada `rules.md` 10b.3a. Ditambah uji penjaga yang mengadu daftar modul dengan daftar kategori, sehingga modul baru yang dapat dikeluhkan warga tidak dapat lagi lolos tanpa kategorinya.

> **Pelajaran:** ketika pemilik proyek menyebut sederet nilai, **seluruhnya wajib diperiksa terhadap keadaan sistem**, bukan hanya yang kebetulan menarik perhatian lebih dulu. Satu ketidakcocokan yang ditemukan justru pertanda daftar itu perlu diperiksa sampai habis.

### 1e.7 Kekeliruan encoding yang hampir lolos

Penyuntingan `data-dictionary.md` lewat `Set-Content` PowerShell **merusak seluruh karakter non-ASCII** pada berkas: 259 kemunculan mojibake menggantikan separator `�`, tanda `�`, `�`, dan em dash. Kerusakannya menyebar ke ratusan baris yang sama sekali tidak berhubungan dengan pekerjaan hari itu.

Ketahuan hanya karena satu penggantian teks gagal mencocokkan pola. Berkas dipulihkan lewat `git checkout HEAD`, lalu seluruh suntingan diterapkan ulang memakai perkakas penyuntingan yang menjaga encoding.

> **Aturan:** penyuntingan berkas dokumen **wajib memakai perkakas edit**, bukan penulisan ulang seluruh isi lewat shell. Bila terpaksa memakai shell, hasilnya wajib diperiksa terhadap mojibake sebelum dilanjutkan, sebab kerusakan semacam ini tidak memerahkan satu uji pun.

---

## 1f. Field Form Tanpa Tempat Tampil (2026-08-25)

Diminta pemilik proyek: menyisir seluruh form dan memastikan setiap isian punya tempat tampil di halaman rinciannya. Pemicunya temuan sebelumnya, yaitu catatan dan unggahan yang dapat diisi tetapi tidak pernah terbaca kembali.

Diperiksa **24 berkas form** terhadap halaman rincian pasangannya. Ditemukan **8 field** yang tersimpan tetapi tidak punya tempat tampil sama sekali, tersebar di 7 modul.

### 1f.1 Delapan temuan

| # | Modul | Field | Diisi di | Keadaan sebelumnya |
|---|---|---|---|---|
| 1 | Saprotan | `foto` | `form.blade.php` baris 224 | Rincian hanya menautkan `dokumen_pendukung` |
| 2 | Alsintan | `foto` | `form.blade.php` | Sama, hanya satu tautan dari dua berkas |
| 3 | Pengaduan | `dokumen_pendukung` | form petugas dan form warga | Rincian hanya menautkan `dokumen_tindak_lanjut` milik petugas, sedangkan bukti dari **pelapor** tidak pernah dapat dibuka |
| 4 | Poktan | `alamat_ketua` | `form.blade.php` baris 343 | Kartu ketua memuat nama, NIK, telepon, lahan, email, tanpa alamat |
| 5 | Poktan anggota | `telepon_wakil` | `form-anggota.blade.php` baris 173 | Sudah disiapkan `anggotaPoktan()` sebagai kunci `telepon`, tetapi tidak dipasang di kolom mana pun |
| 6 | Poktan anggota | `alasan_keluar`, `keterangan` | `form-anggota.blade.php` baris 298 | Tabel anggota hanya menampilkan badge status, sehingga **sebab** seseorang berhenti tidak terbaca |
| 7 | SP dan Kawasan | `keterangan`, `dokumen_pendukung` | `form.blade.php`, `form-kawasan.blade.php` | Keduanya tidak punya tempat tampil di mana pun |
| 8 | Pengaduan publik | `email_pelapor` | `publik/pengaduan.blade.php` baris 179 | Dipakai sekali pada pesan konfirmasi lalu hilang, tidak pernah tersimpan maupun tampil |

Dua sisa refactor ikut dibersihkan: `saprotan/detail.blade.php` memuat judul **Status** diikuti wadah kosong, dan `poktan/detail.blade.php` memuat wadah kosong serupa. Pola yang sama sudah pernah ditambal di `panen/detail.blade.php` pada 2026-08-24 tetapi tidak disisir ke modul lain.

### 1f.2 Akar masalahnya

**Form dan halaman rincian dikerjakan sebagai dua pekerjaan terpisah.** Menambah kolom ke form terasa selesai begitu isiannya tampil dan tersimpan, sedangkan sisi tampil menuntut membuka berkas lain yang tidak sedang dikerjakan.

Tambalan 2026-08-20 sudah membereskan `keterangan` dan `dokumen_pendukung` di enam modul, dan komentarnya masih terbaca pada berkas rincian alsintan dan saprotan. Tambalan itu **melewatkan `foto`**, sebab pemisahan kolom `foto` dari `dokumen_pendukung` terjadi pada pekerjaan yang sama tetapi hanya diteruskan ke inventaris dan fasilitas. Dua modul yang menerima pemisahan belakangan tidak ikut disisir.

Pola ini sejenis dengan yang sudah tercatat pada 1d: fitur lulus uji tetapi tidak dapat dipakai. Bedanya, di sana isian yang tidak memadai; di sini isian sudah benar tetapi hasilnya tidak punya jalan pulang.

**Yang paling menohok:** seluruh kolom yang hilang tampilnya **sudah tertulis lengkap pada `data-dictionary.md` sejak awal**, termasuk `satuan_permukiman.keterangan`, `kawasan_transmigrasi.dokumen_pendukung`, dan `pengaduan.email_pelapor` beserta keterangan "bila diisi, nomor pengaduan dikirim juga ke sini". Tidak satu pun perlu ditambahkan saat pencatatan hasil audit ini.

Jadi kamus datanya benar, migrasinya benar, formnya benar, dan hanya sisi tampil yang tertinggal. Ini menyingkirkan dugaan bahwa penyebabnya kolom yang tidak terpikirkan; yang terjadi adalah **janji dokumen yang tidak ditepati kode**, persis frasa yang sudah dipakai komentar `DummyData` pada tambalan 2026-08-20 untuk kasus yang sama.

### 1f.3 Mengapa 605 uji tidak menangkapnya

Uji `menyediakan cara membuka berkas dari halaman rincian modulnya` sudah ada dan justru menyasar persis masalah ini. Yang membuatnya tidak menolong adalah **angka ekspektasinya**:

```php
['/alsintan/1', 1],   // padahal modulnya punya dua berkas
['/saprotan/1', 1],   // sama
['/infrastruktur/1', 2],
```

Angka `1` ditulis dengan membaca keadaan halaman saat itu, bukan dengan menghitung berapa berkas yang **seharusnya** dapat dibuka. Uji karena itu mengunci keadaan cacat sebagai kebenaran, dan akan tetap hijau selamanya sekalipun cacatnya tidak pernah diperbaiki.

Ini varian dari sebab yang sudah dicatat pada 1d.2: yang diuji adalah apa yang dibangun, bukan apa yang dijanjikan. Bentuknya di sini lebih halus, sebab ujinya ada, namanya tepat, dan warnanya hijau.

> **Aturan:** uji yang mengunci jumlah wajib menyebut alasan angkanya pada komentar. Angka yang ditulis dari hasil pengamatan, bukan dari kewajiban, mengabadikan keadaan yang sedang berlaku.

### 1f.4 Yang diperbaiki

Seluruh delapan temuan ditutup. Foto dipasang berdampingan dengan dokumen mengikuti pola `sp/detail-inventaris.blade.php` yang sudah benar sejak awal. Bukti pelapor dipisahkan tegas dari dokumen tindak lanjut, masing-masing diberi label yang menyebut **siapa** yang menyerahkannya.

`DummyData` menerima kunci baru agar setiap tempat tampil punya contoh isi: `satuan_permukiman` dan `kawasan_transmigrasi` memperoleh `keterangan` dan `dokumen_pendukung`, `pengaduan` memperoleh `email_pelapor` dan `dokumen_pendukung`.

Penjaganya diperluas: daftar uji berkas kini memuat `/dashboard/sp/1` dan `/pengaduan/1`, dan angka alsintan serta saprotan dinaikkan menjadi dua beserta komentar yang menyebut alasannya.

### 1f.5 Kekeliruan diksi pada pekerjaan yang sama

Label email pelapor semula ditulis **"Surel"**, melanggar `ui-spec.md` 10.1 yang menetapkan "email" untuk seluruh teks yang dilihat pengguna.

Larangan itu sudah punya uji penjaga, dan ujinya benar. Yang kurang adalah **cakupannya**: daftar `with()` hanya memuat sembilan halaman auth dan publik, tidak satu pun halaman rincian. Kesalahan karena itu lolos tanpa memerahkan apa pun.

Daftar diperluas ke `/pengaduan/1` dan `/poktan/1`. Dibuktikan lewat mutasi: mengembalikan label ke "Surel" membuat uji memerah.

> **Aturan:** uji yang menjaga konvensi teks wajib menjangkau halaman rincian, bukan hanya halaman auth dan publik. Sebagian besar teks yang dilihat pengguna justru berada di sana.

---

## 1g. Audit Menyeluruh Antarmuka (2026-08-25)

Diminta pemilik proyek: memeriksa seluruh pekerjaan sampai titik ini, mencari yang kurang maupun yang rusak. Lingkupnya **antarmuka saja**, sebab Tahap 2 memang belum menyentuh backend.

Disisir **128 berkas Blade** (22 ribu baris), **132 rute**, **609 uji**, dan `DummyData` 3.879 baris. Penyisiran disebar ke empat penelusur paralel — kesiapan Tahap 3, konsistensi UI, integritas data contoh, serta aksesibilitas dan mutu uji — lalu setiap temuan diverifikasi ulang sendiri sebelum diakui.

### 1g.1 Yang sudah benar

Dicatat lebih dulu, sebab audit yang hanya menyebut cacat memberi gambaran yang keliru tentang keadaan sistem:

Tautan mati **nol**. `scope="col"` **lengkap 100%**. Warna sebagai satu-satunya pembawa makna **nol** — `status-badge` selalu membawa teks beserta titik. Tab kosong **nol**. `@csrf` **lengkap** pada seluruh form tulis. Seluruh **57 rute GET** tersentuh uji.

### 1g.2 Sembilan temuan

| # | Tingkat | Temuan | Ukuran kerusakan |
|---|---|---|---|
| 1 | 🔴 | `auth/signin.blade.php` memuat `<form>` telanjang: tanpa `action`, `method`, maupun `@csrf`, dan `POST /login` tidak terdaftar | Tombol Masuk hanya memuat ulang halaman |
| 2 | 🔴 | `chart-config.js` menulis `window.location.href = '/dashboard/sp/' + id` | Penelusuran **17 grafik** dashboard membalas 404 di situs terbit |
| 3 | 🔴 | Migration membuat `users` (jamak, PK `id`, kolom `name`), kamus data mewajibkan `user` (tunggal, PK `id_user`, kolom `nama`) | **0 dari 36** tabel punya migration; **1 dari ~36** model ada; **0 validasi** aktif di 75 rute tulis |
| 4 | 🟠 | Empat tombol ikon tanpa nama di header, ditambah teks "Notification" dan dua `console.log` | Header muncul di **seluruh** halaman berautentikasi |
| 5 | 🟠 | Focus trap hilang di `confirm-dialog` | Dipakai **21 halaman**, dan yang bocor adalah dialog **hapus** |
| 6 | 🟠 | `<caption>` **nol** di seluruh tabel | Akarnya 2 komponen yang melayani 28 pemakaian |
| 7 | 🟡 | 15 komponen TailAdmin yatim: 612 baris Blade, nol pemakaian | Kebersihan |
| 8 | 🟡 | **37 path absolut** pada prop aksi: `:hapus-url` 15, `pola-aksi` 22 | Akarnya **2 komponen**, bukan 37 halaman |
| 9 | 🟡 | Tidak ada uji penjaga path absolut sama sekali | Inilah sebab nomor 2 dan 8 dapat masuk tanpa ketahuan |

Temuan 2 dibuktikan, tidak disimpulkan: dengan `ASSET_URL` aktif, `route()` menghasilkan `https://abyan28.github.io/transmigrasi/...` sedangkan literal itu menuju `/dashboard/sp/1`. Bedanya dengan temuan 8 menentukan urutan pengerjaan — nomor 2 adalah navigasi **GET** sehingga benar-benar rusak, sedangkan nomor 8 melekat pada form POST yang memang sudah mati di versi statis.

### 1g.3 Dua laporan penelusur yang terbukti salah

Penelusur melaporkan form tanpa `@csrf` dan menyebut `error-state` serta `skeleton` sebagai kode mati. Keduanya diperiksa dan **tidak benar**: `@csrf` lengkap, dan kedua komponen itu dipakai `galeri-komponen`. Tidak satu pun dimasukkan ke daftar temuan.

> **Aturan:** laporan penelusur adalah kandidat temuan, bukan temuan. Setiap butir wajib diverifikasi sendiri terhadap berkas sebelum diakui, sebab dua dari sebelas laporan terbukti keliru dan keduanya akan menghasilkan pekerjaan atas masalah yang tidak ada.

### 1g.4 Yang diperbaiki

Disepakati pemilik proyek sebagai satu paket: temuan **1, 2, 4, 5**, beserta penjaganya.

**Temuan 1** ditutup dengan merangkai formnya saja, **tanpa autentikasi**. Itu tetap pekerjaan Tahap 3. Yang dikerjakan adalah menyamakan signin dengan ketiga tetangganya — `lupa-kata-sandi`, `verifikasi-kode`, dan `ganti-kata-sandi` sudah punya `action`, `@csrf`, dan rute POST berisi komentar "Tahap 3" beserta redirect kosong, persis gaya 74 rute tulis lain di sistem ini. Signin satu-satunya yang tidak. Rute `login.kirim` karena itu berisi nol `Auth::`, nol kueri, hanya redirect dan daftar apa yang harus dikerjakan Tahap 3. Ditambah blok `@error('kredensial')` dan `old('kredensial')`, sebab tanpa tempat menampilkan galat, validasi Tahap 3 akan gagal senyap dan halamannya menjadi kontrol mati (R-26).

**Temuan 2** ditutup dengan mengoper alamat dasar dari Blade: `drilldownSp(idSp)` menjadi `drilldownSp(idSp, basisUrl)`, pemanggilnya mengirim `url('/dashboard/sp')`. Berkas JavaScript tidak mengenal `url()`, jadi Blade adalah satu-satunya sumber alamat yang benar.

**Temuan 4** ditutup dengan empat nama tombol, penggantian "Notification" menjadi "Notifikasi", dan pencabutan dua `console.log` — satu-satunya di seluruh repo. Label tombol tema sengaja dibuat berganti mengikuti modenya, sebab tombol itu menyatakan **tujuan** bukan keadaan: saat gelap aktif, yang ditawarkan adalah beralih ke terang. Kedua penangan klik `handleItemClick` dan `handleViewAllClick` ikut dicabut, sebab keduanya hanya memanggil `console.log` peninggalan TailAdmin dan sudah kehilangan pemanggil sejak daftar notifikasi diganti keadaan kosong yang jujur.

**Temuan 5** ditutup dengan menyalin blok `@keydown.tab` dari `modal-form.blade.php` yang sudah terbukti sejak awal.

### 1g.5 Mengapa 609 uji tidak menangkapnya

Ketiganya lolos lewat sebab yang berbeda, dan hanya satu yang menyangkut kerumitan:

**Temuan 1 — tidak ada yang memeriksa.** `<form>` tanpa `action` tetap merender markup yang sah dan membalas 200. Tidak ada uji berbasis HTTP yang dapat memerah karenanya. Yang membuatnya luput bukan kesulitan, melainkan ketiadaan penjaga; kekeliruannya bahkan tampak seperti keputusan yang disengaja, sebab tetangganya lengkap.

**Temuan 2 — larangannya tertulis, penjaganya tidak ada.** Larangan path absolut sudah tercatat pada bagian 1b.3 sejak **2026-08-17**, dan repo ini sudah dua kali kena masalah yang sama (1b.3 dan 1b.6a). Uji lama justru **mengunci kekeliruannya**: ia memeriksa `assertSee('drilldownSp(data.spId)')`, yakni nama pemanggilan, bukan alamat yang dituju. Uji itu akan tetap hijau selamanya sekalipun alamatnya salah.

**Temuan 4 — tidak pernah masuk cakupan.** Aksesibilitas tombol ikon polos tidak punya uji sama sekali, padahal tempatnya justru di header bersama yang tampil di setiap halaman.

> **Aturan:** setiap larangan yang tertulis pada `notes.md` wajib punya uji penjaga. Larangan tanpa penjaga terbukti tidak menahan apa pun — yang ini bertahan delapan hari setelah ditulis, di berkas yang sama sekali tidak disentuh sejak larangannya berlaku.

> **Aturan:** uji yang menjaga tujuan wajib memeriksa **tujuannya**, bukan bentuk pemanggilannya. `assertSee('drilldownSp(data.spId)')` mengunci nama fungsi; yang perlu dijaga adalah alamat yang dihasilkannya.

### 1g.6 Lima penjaga baru

Seluruhnya diperiksa dari **berkas sumber**, sebab yang dijaga adalah markup dan pemanggilan, bukan hasil render satu halaman. Masing-masing dibuktikan lewat mutasi.

| Penjaga | Kelas kekeliruan yang ditangkap |
|---|---|
| Setiap form auth punya `method`, `action` ke rute yang **benar-benar terdaftar**, dan `@csrf` | Temuan 1, untuk ketiga form sekaligus |
| Halaman masuk punya `@error('kredensial')` dan `old('kredensial')` | Kegagalan senyap pada Tahap 3 |
| Tidak ada alamat mutlak pada `window.location`, `fetch`, maupun `axios` di `resources/js` | Temuan 2 |
| Tombol yang isinya hanya ikon wajib punya `aria-label` atau `sr-only` | Temuan 4 |
| Tidak ada sisa teks Inggris TailAdmin pada teks antarmuka | `ui-spec.md` 11.2 |

Dua jebakan ditemui saat menulisnya dan keduanya layak diingat. Pertama, `toContain` pada Pest memperlakukan argumen kedua sebagai **nilai yang ikut dicari**, bukan pesan galat, sehingga rantai berpesan justru memerahkan berkas yang sehat. Kedua, pola pencari alamat mutlak wajib mensyaratkan **huruf** sesudah garis miring; tanpa itu, `'/'` sebagai pemisah ruas dan `'//'` milik protokol ikut memerah.

Penjaga alamat mutlak sengaja dibatasi ke `resources/js` dan penjaga nama tombol ke layout serta komponen header. Memaksakan keduanya ke seluruh 128 berkas menghasilkan positif palsu pada tombol yang namanya memang datang dari teks isinya.

### 1g.7 Yang sengaja belum dikerjakan

Dicatat di sini agar tidak hilang bersama sesinya.

**Temuan 3 — fondasi `user`.** Ditunda ke Tahap 3 atas pilihan pemilik proyek. Yang perlu diingat saat tahap itu dibuka: `ValidationRules.php` (425 baris) sudah **hardcode** `unique:user,email,...,id_user` mengikuti kamus data, sedangkan migration yang ada masih membuat `users` bawaan Laravel. Berkas itu belum pernah dipanggil sekali pun dari rute, jadi pertentangannya belum menimbulkan galat — dan justru itu yang membuatnya mudah terlewat.

**Temuan 6 — `<caption>` nol.** Belum dikerjakan. Perbaikannya kecil dan terpusat: 2 komponen, `data-table` dan `tabel-ringkas`, melayani 28 pemakaian.

**Temuan 7 — 15 komponen yatim.** Belum dikerjakan. `ui/modal`, `ui/badge`, `ui/alert`, dan `common/page-breadcrumb` masih tercatat pada `ui-spec.md` sebagai komponen basis, padahal polanya sudah diserap seluruhnya ke `x-sim.*`. Pencabutannya wajib disertai penyuntingan `ui-spec.md`, bukan penghapusan berkas begitu saja.

**Temuan 8 — 37 path absolut pada prop aksi.** Belum dikerjakan, dan **penjaganya pun belum ada**: uji baru hanya menyisir `resources/js`, tidak menyentuh prop aksi Blade. Diverifikasi ulang 2026-08-27, jumlahnya masih tepat 37. Akarnya dua komponen, `aksi-baris.blade.php` dan `modal-form.blade.php`, dan pola `url()` yang benar sudah ada di `stat-card.blade.php`.

**Ide B — angkat `x-sim.aksi-daftar` dan `x-sim.tombol-filter`.** Blok tombol Impor beserta Tambah identik 21 baris di 14 berkas; blok filter identik 16 baris di 11 berkas. Sekitar **470 baris duplikat** berisi kelas Tailwind panjang yang akan menyimpang satu per satu.

**Ide C — pindahkan pengambilan data ke rute sebelum Tahap 3.** Ini yang paling menentukan biaya tahap berikutnya. Ada **272 pemanggilan `DummyData::`** tersebar di **67 berkas Blade**. Selama view mengambil datanya sendiri, migrasi ke Eloquent bukan pekerjaan controller melainkan penyuntingan 67 view, dan setiap perulangan menjadi N+1. Mengubah `return view('x')` menjadi `return view('x', ['baris' => ...])` **sekarang**, selagi isinya masih array, jauh lebih murah daripada sesudah Eloquent masuk.

### 1g.8 Sesi terputus, catatan disusun belakangan

Perbaikan dikerjakan 2026-08-25 dan seluruhnya selesai, tetapi sesinya terputus karena galat penyedia model tepat sebelum pencatatan. Bagian ini karena itu ditulis **2026-08-27**, disusun ulang dari riwayat sesi beserta daftar tugasnya yang masih tersimpan, lalu dicocokkan terhadap keadaan berkas yang sebenarnya.

Pencocokan itu bukan formalitas: ia yang memastikan angka 37 masih berlaku, membuktikan bundel `public/build` sudah memuat perbaikan, dan menemukan komentar pengantar uji yang masih menyebut "Ketiga uji" padahal yang ditulis lima.

> **Aturan:** pencatatan adalah bagian dari pekerjaan, bukan penutupnya. Selama belum dicatat, hasil audit hanya hidup di dalam sesi yang mengerjakannya — dan sesi dapat mati kapan saja.

---

## 2. Catatan Dokumen Proposal

Lembar pengesahan pada `docs/Revisi_Proposal_Budi_TEP ITS 2026_Kobalima_Timur_Upload_10_6_2026_a.pdf` masih memuat judul dan pengusul dari proposal lain:

> "Penyediaan Infrastruktur Jaringan Air Bersih di SKP Oransbari", Kawasan Transmigrasi Momiwaren, pengusul Dr. Catur Arif Prastyanto, S.T., M.Eng.

Seharusnya: "Digitalisasi Monitoring Pertanian dan Tata Kelola Data Kawasan Transmigrasi Kobalima Timur", pengusul Dr. Budi Setiyono, S.Si, M.T. Sisa template ini perlu diperbaiki pada dokumen proposalnya, di luar cakupan dokumen `agents/`.

---

## 3. Keputusan yang Sudah Diambil

| Tanggal | Keputusan | Alasan |
|---|---|---|
| 2026-08-10 | Role sistem mengikuti `kategori_user` pada SQL: Admin, Dinas Transmigrasi, Dinas Pertanian, Transmigran, Ketua Poktan | Menyelaraskan PRD dengan skema database yang sudah dirancang |
| 2026-08-10 | Pengaduan menjadi modul tersendiri; fitur pelaporan masalah dicabut dari modul Infrastruktur | Modul infrastruktur difokuskan sebagai pendataan aset, sesuai isi tabel `infrastruktur_pertanian` |
| 2026-08-10 | Modul Pendampingan dihapus dari aplikasi | Sudah dihapus dari PRD; pendampingan tetap berjalan sebagai kegiatan proyek (pelatihan, FGD, BAST), bukan fitur sistem |
| 2026-08-10 | ~~Satu transmigran boleh memiliki lebih dari satu lahan usaha~~ **DIKOREKSI 2026-08-18** | Alasan semula "kondisi lapangan", tetapi keterangan pemilik proyek menyatakan sebaliknya: satu transmigran menerima **satu** pekarangan dan **satu** lahan usaha. Relasi tetap one-to-many sebab satu KK memegang dua bidang berbeda peruntukan, tetapi jumlahnya kini dinyatakan wajar pada `rules.md` 7.8 |
| 2026-08-10 | Satu rumah hanya dihuni satu KK, dan satu KK hanya menempati satu rumah | Sesuai pola pembagian rumah transmigrasi; relasi one-to-one dengan UNIQUE constraint dua arah |
| 2026-08-10 | Satuan panen ditetapkan per komoditas pada data master, dengan ton sebagai satuan agregasi | Komoditas hortikultura lazim dihitung kilogram, sedangkan jagung dalam ton; konversi dilakukan saat rekap |
| 2026-08-11 | Berkas `.sql` diperlakukan sebagai referensi, bukan skema final; database dibangun ulang dari nol | Ditegaskan oleh user. Skema final dituangkan pada `erd.md` dan `data-dictionary.md` |
| 2026-08-11 | Fondasi antarmuka memakai TailAdmin Laravel (MIT) | Sudah menyediakan layout, sidebar, komponen form, tabel, modal, dan ApexCharts; berlisensi MIT sehingga aman untuk instansi pemerintah |
| 2026-08-11 | Design token ditulis di `resources/css/app.css` lewat blok `@theme` | TailAdmin memakai Tailwind v4 yang meniadakan `tailwind.config.js`. Rencana awal pada `ui-spec.md` �3.1 disesuaikan |
| 2026-08-11 | Font antarmuka Outfit, bukan Inter | Seluruh komponen TailAdmin sudah ditata dengan metrik Outfit; mengganti font akan menggeser tinggi baris di banyak komponen tanpa manfaat sepadan |
| 2026-08-11 | Laravel 12.x di atas PHP 8.2.12 milik XAMPP | PHP di PATH adalah 8.5.8, di luar rentang dukungan resmi Laravel 12 (8.2�8.4). Memakai PHP XAMPP sekaligus menyamakan lingkungan dengan hosting |
| 2026-08-11 | Basis data MySQL/MariaDB XAMPP dipakai sejak awal | Diminta user; menghindari migrasi ulang dari SQLite saat memasuki tahap backend |
| 2026-08-11 | Konvensi kunci: PK `id_transmigran`, FK `transmigran_id` | Diminta user agar kode lebih mudah dibaca. Konsekuensinya setiap model wajib menyetel `$primaryKey` dan setiap relasi menyebut kunci secara eksplisit |
| 2026-08-11 | Koordinat memakai dua kolom `lintang` dan `bujur` DECIMAL(10,7) | Eloquent tidak mendukung GEOMETRY secara natif, sedangkan kebutuhan hanya menampilkan lintang/bujur. Lihat bagian 1a.2 |
| 2026-08-11 | Data panen dipindah ke tabel `hasil_panen` tersendiri | Struktur lama membatasi satu lahan hanya punya satu panen selamanya. Lihat bagian 1a.1 |
| 2026-08-11 | Tahap 2 dipecah menjadi dua gelombang | Agar revisi hasil FGD tidak membongkar 43 halaman sekaligus; gelombang 1 memvalidasi pola, gelombang 2 menerapkannya |
| 2026-08-11 | Hierarki wilayah diubah menjadi bercabang dua, dengan tabel `kawasan_transmigrasi` baru | Diminta user. Kawasan transmigrasi adalah subjek utama sistem tetapi sebelumnya tidak punya wujud di database. Lihat bagian 1a.8 |
| 2026-08-11 | SP menyimpan `desa_id` dan `kawasan_id`, tanpa `kecamatan_id` | Kecamatan terbaca lewat rantai desa; menyimpannya terpisah membuka peluang data tidak sinkron |
| 2026-08-11 | `ANTISLOP-ID.md` diadopsi sebagai filter desain, R-02 hanya berlaku untuk teks antarmuka | Dokumen `agents/` tidak dilihat pengguna akhir, sehingga menyisir 121 em dash di dalamnya tidak menambah nilai |
| 2026-08-11 | Dial desain ditetapkan ENERGI 1 / RITME 2 / GERAK 1 | Sistem pendataan harian menuntut ketenangan, tetapi keseragaman penuh berisiko jatuh ke Sterile Default. RITME 2 menjadikan variasi komposisi sebagai penanda orientasi antar-jenis halaman |
| 2026-08-11 | Mode gelap dipertahankan sebagai toggle dua mode | Dipilih user. Konsekuensi R-34: seluruh komponen, 15 grafik, dan seluruh badge wajib diuji di kedua mode, dan tabel kontras WCAG disusun untuk masing-masing mode |
| 2026-08-11 | Tidak ada pendaftaran mandiri; akun hanya dibuat Admin | Ditegaskan user. Sistem memuat data kependudukan, sehingga pendaftaran terbuka berarti siapa pun dapat membuat akun. Sudah selaras dengan `prd.md` �7.1 ("akun yang diberikan") dan matriks `rules.md` �5.1 |
| 2026-08-11 | ~~Pemulihan kata sandi lewat Admin, bukan tautan surel~~ **Sebagian dicabut 2026-08-12** | Tidak semua transmigran memiliki alamat surel yang dapat diakses, dan jaringan di lokus tidak selalu memungkinkan penerimaan surel tepat waktu. Tabel `password_reset_tokens` bawaan Laravel tidak dipakai. **Alasan pertama gugur** setelah ditetapkan warga tidak memiliki akun sama sekali, sehingga seluruh pemegang akun adalah petugas bersurel dinas. Alasan kedua tetap berlaku, dan itulah sebabnya jalur Admin dipertahankan |
| 2026-08-11 | Kredensial dua jenis pada satu kolom isian: email untuk dinas, NIK untuk transmigran | NIK pasti dimiliki setiap penduduk, sedangkan surel belum tentu. Kolom `user.email` menjadi nullable, ditambah kolom `nik` |
| 2026-08-11 | Kata sandi hasil setel ulang wajib diganti saat masuk berikutnya | Kolom `password_harus_diganti` mencegah kata sandi sementara buatan Admin dipakai terus-menerus |
| 2026-08-11 | Role diubah dari enum tetap menjadi **dinamis** lewat tabel `role`, `permission`, `role_permission` | Diminta user. Menambah role pada bentuk enum berarti mengubah struktur tabel sehingga hanya bisa dilakukan programmer. Sekaligus menjawab kebutuhan role Operator SP yang tidak ada pada daftar semula |
| 2026-08-11 | Cakupan data dijadikan dimensi terpisah dari izin, dengan nilai Semua / Per SP / Milik Sendiri | Izin menjawab *boleh melakukan apa*, cakupan menjawab *boleh melihat data siapa*. Tanpa pemisahan ini, dua operator SP berbeda akan melihat data yang persis sama. Lihat bagian 1a.9 |
| 2026-08-11 | Role Transmigran dan Ketua Poktan **dihapus** | Melatih ratusan warga memakai sistem tidak sebanding manfaatnya, dan jaringan di lokus tidak selalu mendukung. Data warga tetap dikelola petugas |
| 2026-08-11 | Pengaduan dibuka sebagai **kanal publik tanpa login** | Konsekuensi penghapusan role Transmigran. Warga tetap dapat melapor cukup dengan nama dan kontak, tanpa perlu akun maupun pelatihan |
| 2026-08-11 | Kredensial login berupa email atau username; kolom `nik` dicabut | Seluruh pengguna sistem kini petugas yang pasti memiliki surel. Username disediakan karena sebagian petugas lebih terbiasa mengetik nama pengguna singkat |
| 2026-08-11 | Verifikasi memakai **tabel terpusat** `verifikasi`, bukan kolom di tiap tabel | Satu tabel melayani 17 modul, sehingga tidak perlu menambah 3 kolom ke 17 tabel. Lihat bagian 1a.10 |
| 2026-08-11 | **Tidak ada verifikasi otomatis.** Data baru selalu `Belum Diverifikasi`, sekalipun penginputnya berizin verifikasi | Bila data input dinas otomatis terverifikasi, indikator dashboard bergeser makna dari "sudah diperiksa" menjadi "kebetulan diinput orang berizin". Lihat bagian 1a.11 |
| 2026-08-11 | Disediakan tombol **"Simpan dan Verifikasi"** yang menghasilkan dua entri audit log terpisah | Menjaga kemudahan bagi petugas dinas tanpa mengorbankan kejujuran jejak audit |
| 2026-08-11 | Indikator mutu data hanya menghitung baris berstatus `Terverifikasi` | Angka yang dilaporkan ke Kementerian harus mencerminkan pemeriksaan nyata, bukan sekadar asal-usul penginput |
| 2026-08-12 | Penilaian kondisi SP memakai istilah **Mandiri, Berkembang, Perlu Penanganan** | Istilah "terbelakang" atau "tertinggal" melabeli warga, padahal yang dinilai jalan dan listrik, hal yang berada di luar kendali mereka. Label bernada merendahkan juga mudah dikutip di luar konteks dan tidak memberi tahu tindakan apa yang perlu diambil |
| 2026-08-12 | Bobot penilaian **5 / 3 / 1**, disimpan sebagai data bukan enum | Jarak lebar agar kegagalan layanan dasar tidak tertutupi kelengkapan fasilitas penunjang. Disimpan sebagai data agar Admin dapat menyesuaikan lewat CMS, mengikuti pola `role` dinamis dan `faktor_ke_ton` |
| 2026-08-12 | **Aturan primer nol**: satu parameter primer bernilai nol membuat SP otomatis Perlu Penanganan | Tanpa aturan ini, SP tanpa air bersih tetapi lengkap fasilitas penunjangnya dapat mencapai skor 70, dan angka itu menyesatkan. Rata-rata tidak boleh menutupi kegagalan pada hal yang mutlak |
| 2026-08-12 | Skor **disimpan sebagai riwayat** beserta salinan bobot saat itu | Bobot dapat diubah Admin. Tanpa salinan, laporan yang sudah dicetak dan dikirim dinas akan berbeda dari tampilan sistem setelah bobot berubah. Prinsip sama dengan penyalinan `satuan_id` pada hasil panen |
| 2026-08-12 | **Ketiadaan dan kerusakan dibedakan**, keduanya bukan hal yang sama | SP yang belum pernah punya telekomunikasi memerlukan pembangunan; yang punya tetapi rusak memerlukan perbaikan. Menyamakan keduanya menyembunyikan perbedaan penanganan |
| 2026-08-12 | `nama_fasilitas` tetap teks bebas, **ditambah** kolom `jenis_fasilitas` enum | Enum diperlukan agar penilaian dapat dihitung otomatis, sebab teks bebas membuat "SEKOLAH DASAR" dan "SD Negeri 1" tidak terbaca sebagai hal yang sama. Nama bebas dipertahankan agar petugas tetap dapat menulis sebutan yang dikenal warga |
| 2026-08-12 | Pengenal URL: **UUID** untuk data pribadi, **slug** untuk data master | Slug dari nama orang justru membocorkan identitas lewat riwayat peramban dan log server, sehingga menurunkan kerahasiaan dibanding id angka. Data master bukan data pribadi, sehingga keterbacaan lebih berharga |
| 2026-08-12 | Nomor pengaduan publik memuat **bagian acak** | Halaman lacak dapat dibuka tanpa login, dan nomor berurutan dapat ditebak satu per satu untuk memanen judul serta catatan penanganan warga lain. Inilah permukaan serangan yang nyata, berbeda dari id petugas yang sudah terlindung login |
| 2026-08-12 | Rate limiting **per akun** untuk halaman internal, bukan per IP | Satu kantor dinas kerap memakai satu sambungan bersama; menghitung per IP membuat sepuluh operator saling menghabiskan jatah satu sama lain |
| 2026-08-12 | Inventaris halaman `ui-spec.md` **dibersihkan dari role T dan KP** | Legenda masih mendefinisikan Transmigran dan Ketua Poktan, padahal `rules.md` menetapkan seluruh pengguna adalah petugas. Akibatnya 14 baris menjanjikan halaman yang tidak boleh dibangun, dan audit berikutnya akan menyimpulkan ada pekerjaan tertinggal yang sebenarnya tidak ada |
| 2026-08-12 | Halaman yang berupa **tab ditulis "tab pada ..."**, bukan rute palsu | Riwayat penghunian, dokumen lahan, anggota poktan, dan template laporan sudah ada sebagai tab. Menuliskannya sebagai `GET /rumah/{id}/riwayat` membuat inventaris tampak bolong padahal fiturnya lengkap |
| 2026-08-12 | Uji rute memakai **daftar dari router**, bukan daftar tulis tangan | Audit menemukan 19 dari 41 rute GET tidak pernah dibuka satu uji pun, karena uji menyebut halaman satu per satu sehingga halaman baru mudah luput. Membaca dari router membuat rute baru otomatis ikut teruji |
| 2026-08-12 | `scope="col"` diwajibkan lewat uji, bukan sekadar kebiasaan | 23 berkas sudah konsisten memakainya, tetapi dashboard tertinggal 42 header. Tanpa uji, aturan ini tergeser diam-diam sebab header baru biasanya disalin dari baris tetangga yang juga belum benar |
| 2026-08-12 | Pemulihan mandiri memakai **kode enam digit**, bukan tautan sekali klik | Kode dapat dibaca di ponsel lalu diketik di komputer, sehingga tetap berguna ketika surel dan peramban berada di perangkat berbeda, atau ketika jaringan lokus gagal memuat tautan panjang. Tautan mengandaikan satu perangkat dan satu sesi peramban, andaian yang sering meleset di lapangan |
| 2026-08-12 | Jalur Admin **dipertahankan** meski jalur surel ditambahkan | Jaringan di lokus tidak selalu memadai, dan jalur Admin satu-satunya yang bekerja tanpa sambungan surel. Menghapusnya berarti menyisakan satu titik kegagalan pada satu-satunya cara masuk kembali ke sistem |
| 2026-08-12 | Halaman lupa sandi **tidak memberi tahu apakah akun terdaftar** | Pesan yang membedakan "terkirim" dan "tidak ditemukan" menjadikan halaman publik ini alat memeriksa siapa saja yang memiliki akun di dinas. Pesannya karena itu selalu sama |
| 2026-08-12 | Kode disimpan sebagai **sidik, bukan angka aslinya** | Basis data yang bocor tidak boleh langsung memberi jalan masuk. Alasannya sama dengan kata sandi, dan berlaku meski kode hanya hidup 15 menit |
| 2026-08-12 | `jumlah_izin` pada data contoh dikoreksi 120/68/64/32 ? **119/68/74/50** | Angka lama tidak pernah dicocokkan dengan tabel izin. Setelah dihitung ulang, tiga dari empat meleset, dan Dinas Pertanian ternyata memegang izin lebih banyak daripada yang tercatat. **Koreksi pertama hari ini (114/63/72/47) juga keliru** karena modul `fasilitas_sp` terlewat; angka 68 milik Dinas Transmigrasi ternyata sudah benar sejak awal |
| 2026-08-12 | `inventaris_sp` dan `fasilitas_sp` **dipisah** menjadi dua modul berizin | `rules.md` 5.1 menggabungkan keduanya jadi satu baris, sementara `data-dictionary.md` 13.1, `erd.md`, dan `ui-spec.md` sejak awal memisahkan: dua tabel, dua halaman, dua izin. `rules.md` yang menyimpang, dan itulah yang diperbaiki. Pemisahan juga memungkinkan Admin memberi kewenangan berbeda antara aset bergerak dan bangunan fasilitas |
| 2026-08-12 | Pembanding izin mengadu dengan **tiga sumber**, bukan satu | Audit pertama hanya membandingkan dengan `rules.md` 5.1, sehingga modul yang hilang dari kamus data tidak pernah tertangkap dan hasilnya sempat dinyatakan "cocok sempurna" padahal kurang satu modul. Memeriksa satu sumber saja memberi rasa aman yang keliru ketika sumber-sumbernya sendiri belum sejalan |
| 2026-08-12 | Form isian **tertinggal untuk 14 modul** sejak Tahap 2 | Task 2.13 sampai 2.18 hanya menulis Membuat halaman, sehingga form tidak pernah masuk lingkupnya dan menyatu ke task CRUD Tahap 4 sampai 8. Akibatnya 14 halaman daftar hanya dapat dibaca. Petunjuknya sudah ada sejak awal: `ui-spec.md` mencantumkan Form SP modal, tetapi berkasnya tidak pernah dibuat |
| 2026-08-12 | Halaman rincian dibuat lebih dulu, sebelum form | Pola baku sejak Task 2.7 menempatkan tombol Tambah di halaman daftar dan Ubah di halaman rincian. Empat modul tanpa rincian karena itu tidak punya tempat menaruh tombol Ubah, sehingga rinciannya menjadi prasyarat |
| 2026-08-12 | Nilai data contoh **wajib memakai enum**, bukan teks yang mirip | Alsintan tercatat `'Milik Pribadi'` sedangkan enumnya `'Pribadi'`, sehingga filter kepemilikan tidak pernah cocok dan selalu menghasilkan nol baris. Cacat semacam ini tidak terlihat pada tampilan biasa, hanya muncul ketika filter dipakai |
| 2026-08-12 | Pratinjau ditampilkan pada isian yang dampaknya menyebar | Faktor konversi satuan dan bobot penilaian sama-sama memengaruhi banyak rekap sekaligus. Kekeliruannya baru terlihat berbulan kemudian ketika angka gabungan tampak janggal, sehingga pratinjau perhitungan ditampilkan saat mengisi |
| 2026-08-13 | Jumlah kolom diperiksa pada **halaman terender**, bukan hanya berkas | Cacat ini muncul pada hasil akhir, bukan pada sumbernya. Membandingkan jumlah ``<th>`` di ``<thead>`` dengan jumlah ``<td>`` pada baris pertama menangkapnya langsung, sekalipun seluruh tag pada berkas Blade sudah berpasangan benar |
| 2026-08-13 | Penyisipan markup berbasis **nomor baris** dihentikan | Kolom aksi disisipkan lewat skrip yang menghitung titik sisip dari nomor baris, dan melesetnya satu posisi membuat ``<th>`` jatuh di luar ``</x-slot:kepala>`` sekaligus ``<td>`` jatuh di luar ``</tr>`` pada enam halaman. Peramban tidak dapat merender sel di luar baris, sehingga tata letak tabelnya hancur. Penyuntingan berikutnya memakai pencocokan teks beserta konteksnya, bukan nomor baris |
| 2026-08-13 | Uji keseimbangan tag **tidak cukup** menjamin struktur tabel | ``<tr><td></td></tr><td></td>`` seimbang secara pasangan: setiap tag punya penutupnya, sehingga uji lama menyatakannya sehat. Yang salah adalah kedudukannya, dan itu tidak pernah diperiksa. Ditambah tiga uji: sel wajib berada di dalam baris, slot kepala wajib tertutup setelah seluruh judul, dan jumlah kolom judul wajib sama dengan jumlah sel |
| 2026-08-13 | ApexCharts mengukur lebar kanvas **sekali saja** saat digambar | Pada tab latar belakang, peramban belum melakukan layout sehingga lebar wadah terbaca nol dan ApexCharts jatuh ke lebar bawaannya. Kanvas keliru itu tidak pernah dihitung ulang dengan sendirinya, sehingga grafik menembus tepi kartunya sampai halaman disegarkan manual |
| 2026-08-13 | Cacat yang hanya muncul pada tab latar belakang **tidak dapat diuji otomatis** | Perilakunya bergantung pada urutan layout peramban sungguhan. Uji hanya memastikan ketiga mekanismenya terpasang, sedangkan pembuktian akhirnya tetap memerlukan pemeriksaan manual. Karena itu ``ui-spec.md`` 3.2.3 kini mewajibkan pemeriksaan tab baru sampai bagian paling bawah halaman, termasuk grafiknya |
| 2026-08-13 | Grafik ditangani **tiga lapis**, bukan satu perbaikan | Pembatas ``w-full max-w-full overflow-hidden`` menahan tata letak agar tidak rusak walau grafiknya belum pas; ``IntersectionObserver`` menggambar ulang sekali ketika wadahnya benar-benar memiliki lebar; ``redrawOnParentResize`` menjaga grafik tetap menyesuaikan saat sidebar dilipat atau jendela diubah. Satu lapisan saja menyisakan celah: pembatas tanpa penggambaran ulang membuat grafik terpotong di dalam kartu, sedangkan penggambaran ulang tanpa pembatas kehilangan penahan bila gagal |
| 2026-08-13 | Tata letak melebar pada tab baru ternyata punya **dua penyebab berlapis** | Perbaikan pertama menambal nilai ``innerWidth``, dan itu salah sasaran. Penyebab pertama yang sebenarnya adalah ``flex-1`` tanpa ``min-w-0`` di samping sidebar berposisi ``fixed``, sehingga lebar totalnya menjadi 100% + 290px. Setelah itu diperbaiki, tangkapan layar berikutnya memperlihatkan menu pengguna dan tombol sudah pulih, tetapi **grafik masih melebar**: itulah penyebab kedua yang berbeda sama sekali |
| 2026-08-14 | **Fitur verifikasi data dicabut seluruhnya** atas kesepakatan tim | Membatalkan keputusan 2026-08-11 pada 1a.10 dan 1a.11. Yang ikut hilang: enum ``StatusVerifikasi``, tabel ``verifikasi``, aturan ``rules.md`` 5.2, delapan rute verifikasi/tolak, tombol "Simpan dan Verifikasi", serta kolom ``status_verifikasi`` pada delapan entitas. Kedua bagian lama sengaja dipertahankan bertanda dibatalkan, sebab ``notes.md`` berfungsi sebagai jejak keputusan, bukan cerminan keadaan terkini |
| 2026-08-14 | Empat konstanta izin **runtuh menjadi kembar** setelah aksi verifikasi dibuang | ``$penuh`` dan ``$p`` menjadi sama dengan ``$lituhe``, ``$kelolaVerifikasi`` sama dengan ``$kelolaSaja``, dan ``$ltuv`` sama dengan ``$ltu``. Seluruhnya digabung agar tidak ada dua nama untuk hal yang sama. Hanya ``$ltuve`` yang dipertahankan dengan isi baru, sebab dipakai tujuh belas kali |
| 2026-08-14 | Indikator 15 **Mutu Data ikut dihapus**, bukan diberi makna baru | Seluruh perhitungannya bersandar pada status verifikasi; tidak ada komponen lain di dalamnya. Mempertahankan kartunya berarti menampilkan angka tanpa dasar. Dashboard turun dari 17 menjadi 16 indikator dan dari 6 menjadi 5 bagian, sebab tajuk "Tata Kelola Data" hanya menaungi grafik ini |
| 2026-08-14 | Jumlah izin **dihitung ulang serentak di dua tempat** | Total izin 137 menjadi 118, Admin 119 menjadi 118, Dinas Transmigrasi 68 menjadi 57, Dinas Pertanian 74 menjadi 64, Operator SP tetap 50. Angka ini tertulis pada ``DummyData::role()`` sekaligus ``data-dictionary.md`` 13.1; memperbarui salah satunya saja membuat keduanya saling bertentangan. Operator SP tidak berubah karena memang tidak pernah memegang izin verifikasi |
| 2026-08-14 | Satu baris audit log memakai aksi ``Verifikasi`` untuk **konteks yang sama sekali berbeda** | ``DummyData::auditLog()`` baris keempat memakainya untuk menutup pengaduan, bukan memverifikasi data. Menghapus enum tanpa membaca baris ini akan merusak contoh audit log secara diam-diam. Aksinya diganti ``Ubah``, sebab yang berubah adalah tahap penanganannya |
| 2026-08-14 | **Username kini dibuat petugas sendiri**, bukan dikarang Admin | Petugaslah yang mengetiknya setiap hari, sehingga ia pula yang paling berkepentingan memilihnya. Konsekuensi yang harus disepakati lebih dulu: surel menjadi **wajib**, sebab itulah satu-satunya kredensial yang dimiliki petugas ketika pertama kali masuk. Kata sandi awal ikut dibangkitkan sistem, karena kata sandi karangan manusia cenderung berpola dan dipakai ulang untuk banyak akun |
| 2026-08-14 | Akun nonaktif ternyata **terkunci selamanya** | Cabang kondisi pada halaman daftar tidak menghasilkan apa pun ketika akun sudah mati, sehingga tidak ada jalur mengaktifkannya kembali, padahal akun memang tidak pernah dihapus. Enum ``AksiAuditLog::AktifkanAkun`` sudah disiapkan sejak lama tetapi tidak pernah dipicu antarmuka mana pun |
| 2026-08-14 | Cakupan data ``Milik Sendiri`` **dibuang** | **Diminta pemilik proyek.** Nilai ini hanya ditandai "untuk kebutuhan mendatang" pada dua dokumen tanpa satu pun aturan yang menjelaskan kapan dipakai, sehingga menambah beban baca tanpa manfaat. Dapat ditambahkan kembali bila kelak benar-benar diperlukan. *(Alasan "tidak pernah dipakai role mana pun" dicabut 2026-08-19: itu merujuk ``DummyData::role()`` yang dikarang sendiri, bukan bukti tentang kebutuhan lapangan. Keputusannya tetap sah sebab bersumber dari permintaan pemilik proyek.)* |
| 2026-08-14 | Ditambahkan role dummy **Pendamping Lapangan** yang bukan bawaan sistem | Keempat role bawaan seluruhnya bernilai "Dapat dihapus: Tidak", sehingga bentuk tampilan role buatan Admin tidak pernah terlihat dan tidak dapat dinilai. Role ini sengaja dibuat tanpa pengguna agar keadaan tersebut ikut teruji |
| 2026-08-14 | Izin ``hapus`` dicabut dari modul ``pengguna``, **tetapi dipertahankan pada ``role``** | Akun tidak pernah dihapus melainkan dinonaktifkan, sehingga kotak centangnya menawarkan kewenangan yang mustahil dijalankan. Sebaliknya role **boleh** dihapus selama bukan bawaan dan tidak dipakai akun mana pun (``rules.md`` 5.0c poin 9); yang keliru justru tombolnya yang belum pernah dibuat. Total izin 118 menjadi 117 |
| 2026-08-14 | Kata sandi dikirim lewat surel **sekaligus tetap tampil di layar** | Permintaan pengiriman surel dipenuhi, tetapi tampilan layar dipertahankan. Jalur Admin justru dibuat untuk petugas di lokus bersinyal lemah; bila kata sandi hanya dikirim lewat surel, petugas yang sedang berdiri di depan Admin tidak dapat membacanya sampai memperoleh sinyal. Peringatan pada modal setel-sandi ditulis ulang, bukan dihapus |
| 2026-08-14 | **Pembobotan prioritas pengaduan dibatalkan** atas permintaan | Membatalkan keputusan sebelumnya yang menurunkan prioritas otomatis dari kategori. Alasannya: kategori hanya menyatakan pokok masalah, sedangkan kegentingan bergantung pada keadaan lapangan yang tidak terbaca dari kategori. Dua laporan berkategori sama dapat berbeda jauh kemendesakannya, dan nilai turunan yang tampak berwibawa justru berisiko diterima begitu saja tanpa ditinjau ulang |
| 2026-08-14 | Dokumen tindak lanjut **terlihat petugas, tidak terbuka bagi warga** | Halaman lacak dapat dibuka tanpa login dan hanya berbekal nomor pengaduan, sehingga siapa pun yang mengetahui nomornya akan ikut memperoleh berkasnya. Dokumen tindak lanjut kerap memuat nama petugas, hasil peninjauan, dan kadang data warga lain. Warga cukup diberi tahu keberadaannya beserta cara memintanya |
| 2026-08-14 | Dua modal penanganan **meminta hal yang berbeda** untuk tindakan yang sama | Modal pada halaman daftar tidak memiliki tanggal penanganan maupun unggahan dokumen, dan nama isian statusnya pun berbeda. Jejak yang dihasilkannya menjadi timpang: sebagian bertanggal dan berdokumen, sebagian tidak. Ditemukan pula formnya tanpa ``enctype``, sehingga berkas tidak akan pernah terkirim dan kegagalannya berlangsung diam-diam |
| 2026-08-14 | Isian unggah dokumen ternyata **tidak pernah menampilkan hasilnya** | Modal penanganan sudah lama menyediakan unggahan, tetapi tidak ada satu pun tempat yang merender berkasnya kembali. Berkas yang sudah diunggah petugas karena itu tidak dapat dibuka siapa pun, termasuk oleh yang mengunggahnya sendiri |
| 2026-08-14 | Metode ``dariKategori()`` ternyata **tidak pernah dipanggil kode produksi** | Hanya hidup di dalam enum dan dikunci dua uji, sedangkan form petugas justru memakai nilai bawaan ``Sedang``. Pembatalannya karena itu tidak mengubah satu pun perilaku yang sedang berjalan; yang berubah hanya aturan tertulis dan ujinya |
| 2026-08-14 | Tombol lacak setelah kirim pengaduan **selalu buntu** | Rute kirim membalas ``PGD-2026-0006`` yang tidak pernah ada pada data contoh, sehingga tombol "Lihat Perkembangan Laporan" selalu berujung pada keadaan nomor tidak ditemukan. Inilah yang membuat halaman lacak tampak tidak berdata, padahal datanya sudah ada sejak awal |
| 2026-08-14 | Penyaringan riwayat **wajib memakai ``nama_tabel`` DAN ``record_id``** | Menyaring nama tabel saja membuat setiap baris menampilkan riwayat baris lain pada tabel yang sama, sehingga pembaca mengira datanya pernah diubah padahal tidak. Cacat persis ini ditemukan pada modal rincian pengguna dan ikut diperbaiki |
| 2026-08-14 | Riwayat perubahan ditampilkan **di tempat datanya dibaca**, bukan hanya di halaman audit log | Pertanyaan "siapa yang memasukkan data ini dan siapa yang pernah mengubahnya" muncul justru saat membaca datanya. Menjawabnya lewat halaman audit log berarti petugas harus menelusuri sendiri baris mana yang menyangkut data yang sedang dibukanya. Halaman audit log tetap ada dan ditautkan, sebab ia menjawab pertanyaan yang berbeda |
| 2026-08-14 | **Lima halaman rincian diubah menjadi bertab** demi keseragaman | Pemasangan Catatan Log yang pertama hanya menyentuh lima halaman yang kebetulan sudah bertab; lima lainnya terlewat justru karena belum bertab. Ketimbang memakai dua bentuk berbeda, seluruh halaman rincian diseragamkan sehingga letak Catatan Log dapat ditebak tanpa menghafal per modul, sejalan dengan komposisi ``ui-spec.md`` 2.2 |
| 2026-08-14 | Ditambahkan **uji penjaga berbasis daftar rute**, bukan daftar tetap | Uji membaca seluruh rute berpola ``<modul>/{id}`` lalu memastikan tiap halamannya memuat Catatan Log. Dengan begitu halaman rincian baru yang lupa dipasangi langsung tertangkap tanpa perlu menyunting ujinya. Uji ini lahir dari kelalaian nyata yang baru ketahuan setelah ditanyakan |
| 2026-08-14 | Keseragaman **dipilih meski sebagian tab jadi tipis** | Saprotan dan Panen hanya menghasilkan dua tab, dengan tab pertama berisi satu kartu. Secara ``rules.md`` 13.2 poin 2 tab memang untuk memecah tumpukan, sehingga ini melampaui syarat minimalnya. Keseragaman letak dinilai lebih menolong petugas daripada penghematan satu klik |
| 2026-08-17 | **45 isian wajib ternyata boleh dikosongkan** | Audit menyeluruh atas 220 isian menemukan 43 kolom bertanda ``Null = TIDAK`` tanpa penanda wajib apa pun, ditambah 2 bintang tanpa ``required`` pada halaman masuk. Yang terakhir paling menyesatkan: penandanya ada tetapi tidak menegakkan apa pun |
| 2026-08-17 | Cacatnya **mengelompok, bukan tersebar** | Sembilan berkas benar 100 persen, sedangkan seluruh form master dan aset tidak memiliki satu pun ``required`` maupun bintang. Ini menandakan satu golongan berkas yang belum pernah dilewati penandaan, bukan kelalaian pada isian satu per satu. Temuan ini mengubah cara perbaikannya: ditandai serentak berpedoman kamus data, bukan ditambal per kasus |
| 2026-08-17 | Kamus data **disesuaikan ke kode**, bukan sebaliknya, untuk empat kolom | ``jenis_saprotan`` menjadi ``jenis``, ``nama_saprotan`` menjadi ``nama``, ``status_keaktifan`` menjadi ``status``, dan ``tanggal_berdiri`` DATE menjadi ``tahun_berdiri`` YEAR. Awalan nama modul hanya mengulang informasi yang sudah dinyatakan tabelnya, sedangkan mengubah kode akan menyentuh puluhan tempat dengan manfaat yang baru terasa pada Tahap 6 sampai 8 |
| 2026-08-17 | Larik kotak centang **tidak dapat memakai ``required``** | Pada larik, atribut itu menuntut setiap kotak dicentang, bukan minimal satu. Penugasan SP karena itu ditegakkan lewat Alpine beserta pesan galat. Janji "Wajib dipilih minimal satu" sudah tertulis sejak lama tetapi tidak pernah ditegakkan |
| 2026-08-17 | Uji penjaga **membaca kamus data**, bukan daftar tetap | Kolom ``Null = TIDAK`` diurai langsung dari ``data-dictionary.md`` lalu dicocokkan dengan formulirnya. Dengan begitu kamus data dan formulir tidak dapat berbeda diam-diam: menambah kolom wajib otomatis menuntut formnya ikut menandai, tanpa perlu menyunting uji |
| 2026-08-17 | **Pemulihan lewat git wajib seizin pengguna** | Satu baris revisi wording tertimpa ``git checkout`` yang dijalankan tanpa bertanya, setelah salah menyimpulkan asalnya. Waktu ubah berkas sebenarnya sudah membuktikan itu pekerjaan pengguna, tetapi tidak diperiksa. Sejak sekarang temuan semacam ini dilaporkan beserta buktinya, lalu menunggu keputusan |
| 2026-08-17 | **Uji dilarang mengunci kalimat penuh** sebagai penanda keberadaan elemen | Dua uji rekap memerah bukan karena ada yang rusak, melainkan karena wordingnya disunting. Keduanya diubah memeriksa kelas ``motif-baris-total``. Menguji kata membuat penyuntingan bahasa terasa berisiko, padahal justru itu yang paling sering berubah |
| 2026-08-17 | Baris total ditulis **"Total" saja**, tanpa penanda cakupan | Judul halaman dan filter yang sedang aktif sudah menyatakan cakupannya, sehingga "Total kawasan" mengulang informasi tepat di atasnya. Lima tempat disamakan, sedangkan baris yang menjelaskan APA yang dijumlahkan seperti "Total luas lahan" tetap dipertahankan |
| 2026-08-17 | Istilah antarmuka **diseragamkan ke "email"**, bukan "surel" | Lebih dikenal petugas dan warga di lokus meski "surel" padanan baku. Hanya menyentuh empat tempat yang benar-benar tampil di layar; dua puluh empat kemunculan lain berada di dalam komentar kode dan dibiarkan, sebab pembacanya pengembang. Aturannya dicatat pada ``ui-spec.md`` 10.1 agar pekerjaan berikutnya tidak kembali memakai "surel" |
| 2026-08-17 | Uji penjaga istilah sempat **lulus tanpa memeriksa apa pun** | Spanduk kredensial hanya dirender setelah formulir dikirim, sedangkan uji membuka halaman biasa sehingga teksnya tidak pernah ada. Ketahuan lewat mutasi yang tidak memerah. Sesi kini diisi lebih dulu supaya keadaan setelah pengiriman ikut terperiksa |
| 2026-08-19 | **Data contoh dilarang menjadi bukti tentang lapangan** | Ditegur pemilik proyek. Kesimpulan struktur berulang kali disandarkan pada `DummyData` yang dikarang AI sendiri, sehingga penalarannya melingkar: "tidak ada di data" hanya berarti "belum terpikir saat menuliskannya". Aturannya kini tertulis pada `rules.md` 19a. Lihat bagian 1c |
| 2026-08-19 | **Tautan objek pengaduan dicabut seluruhnya**, digantikan bidang berbasis kategori | Setelah ditetapkan satu laporan ditangani satu dinas, mengelola daftar objek per laporan menjadi beban tanpa menjawab pertanyaan yang sebenarnya, yaitu dinas mana yang menangani. Dicabut menyeluruh sebab mencabut isiannya saja menyisakan tab dan rekap yang selamanya kosong, dan itu kontrol mati (R-26). Lihat bagian 1e |
| 2026-08-19 | Bidang **boleh kosong** untuk empat kategori yang dapat ditangani dua dinas | Lahan usaha, infrastruktur, bencana, dan lainnya menyatakan pokok masalah yang dapat jatuh ke dua dinas. Menebak bidangnya membuat laporan masuk ke daftar dinas yang keliru lalu tertahan di sana. Nilai turunan pun selalu dapat ditimpa petugas, sebab penentuan dinas bergantung isi laporan yang tidak selalu terbaca dari kategori |
| 2026-08-19 | Cakupan kedua dinas **sengaja tidak simetris**: Transmigrasi `Semua`, Pertanian `Per Bidang` | Sistem ini milik Dinas Transmigrasi sebagai pengelola kawasan, dan merekalah yang menyaring laporan berbidang kosong. Konsekuensinya laporan hanya sampai ke Dinas Pertanian lewat penetapan mereka, diredam filter "Belum ditentukan" beserta jumlahnya agar antrean tidak menumpuk diam-diam |
| 2026-08-19 | **Audit menyeluruh `rules.md` 19a dijalankan**, menemukan dua pelanggaran baru | Bagian 1c semula mencatat tiga pelanggaran; penyisiran 992 baris menemukan **lima**. Yang terberat: enum `PeruntukanLahan` sempat dipecah menjadi Lahan Usaha I/II berdasarkan hitungan baris data contoh, dipasang lalu dicabut pada hari yang sama. Yang paling halus: kontak poktan, satu-satunya yang **membatalkan alasan lapangan yang sudah benar** demi menyesuaikan dokumen pada bentuk `DummyData`. Lihat bagian 1c.2 dan 1c.4 |
| 2026-08-19 | Bentuk keempat ditambahkan pada 1c.1: **data karangan mengalahkan alasan lapangan** | Berbeda dari tiga bentuk sebelumnya, arahnya terbalik: bukan menyimpulkan ketika alasan lain belum ada, melainkan membatalkan alasan lapangan yang sudah tercatat. Menyamar sebagai perapian ketidakkonsistenan dokumen dan kode, sehingga tampak seperti perbaikan. Pembedanya satu pertanyaan: mana yang disesuaikan pada mana |
| 2026-08-19 | **Musim Tanam diberi impor**, dikeluarkan dari daftar pengecualian | Sempat disamaratakan dengan Role, Kawasan, dan SP lewat alasan "jumlah barisnya sedikit". Ketiganya berjumlah tetap menurut `prd.md`, sedangkan musim tanam bertambah dua kali setahun tanpa henti. Kebutuhannya dipastikan keterangan pemilik proyek: dinas perlu memasukkan riwayat musim tahun-tahun sebelumnya secara massal. Modul berimpor 14 menjadi 15 |
| 2026-08-19 | Kontak poktan **tetap kontak ketua**, tetapi alasannya diperbaiki | Keputusan 2026-08-17 kebetulan benar dan dipastikan keterangan pemilik proyek: poktan di Kobalima Timur tidak memiliki kontak sendiri. Yang dicabut hanya alasan lamanya yang bersandar pada bentuk `DummyData`, sebab kebenaran itu baru diketahui dua hari setelah keputusannya diambil |
| 2026-08-19 | Aturan `rules.md` 19a ditambah poin **13 dan 14** | Poin 13 memperingatkan bentuk berarah terbalik; poin 14 mewajibkan aturan ini diperiksa ulang berkala terhadap keputusan yang sudah diambil, sebab menulis aturan terbukti tidak cukup: dua pelanggaran terjadi setelah aturannya berlaku |
| 2026-08-19 | Kategori **Kelompok Tani** ditambahkan setelah terlewat | Poktan modul penuh tetapi keluhan atasnya terpaksa masuk kategori `Lainnya` yang berbidang kosong, sehingga menambah antrean penyaringan padahal urusannya jelas milik Dinas Pertanian. Terlewat karena hanya sebagian dari sederet nilai yang disebut pemilik proyek diperiksa terhadap keadaan sistem. Penyisiran 26 fitur menemukan tepat satu yang terlewat, dan pemetaan modul-kategori kini dijaga uji. Lihat bagian 1e.6 |
| 2026-08-19 | Penyuntingan dokumen **wajib lewat perkakas edit**, bukan penulisan ulang lewat shell | `Set-Content` merusak 259 karakter non-ASCII pada `data-dictionary.md`, menyebar ke ratusan baris yang tidak berhubungan dengan pekerjaan. Kerusakan semacam ini tidak memerahkan satu uji pun dan hampir lolos; ketahuan hanya karena satu penggantian teks gagal mencocokkan pola |
| 2026-08-19 | Skenario **disisir sendiri**, tidak menunggu disodorkan | Pemilik proyek menyampaikan dua kasus tautan aset, dan penyisiran lanjutan menemukan 17 skenario lain, tiga di antaranya menyangkut privasi yang dapat membocorkan data pribadi di kanal publik. Menunggu kasus disodorkan berarti menyerahkan kelengkapan analisis kepada ingatan pemilik proyek. Dituangkan pada `rules.md` 20a |

---

## 3b. Keputusan Penyajian Statis (2026-08-17)

| Tanggal | Keputusan | Alasan |
|---|---|---|
| 2026-08-17 | Antarmuka Tahap 2 diterbitkan **sebagai berkas statis** ke GitHub Pages | Gratis tanpa kartu kredit, pembaruan cukup `git push`, dan tidak menuntut laptop pengembang menyala. Masuk akal justru karena Tahap 2 tidak memiliki kueri basis data maupun autentikasi; seluruh isi halaman berasal dari `DummyData` |
| 2026-08-17 | Render dan Railway **gugur lebih dulu** | Render tidak memiliki runtime PHP native sehingga mensyaratkan Docker, sedangkan Railway meminta kartu kredit. InfinityFree menyediakan PHP asli tetapi pembaruannya lewat unggahan FTP manual, berlawanan dengan syarat mudah direvisi |
| 2026-08-17 | Sub-path ditangani `ASSET_URL` + `forceRootUrl()`, **bukan menyunting tautan satu per satu** | GitHub Pages menyajikan repositori di `/transmigrasi/`. Menambal 205 pemanggilan `route()` mustahil dirawat, sedangkan satu titik pengaturan di `AppServiceProvider` menangani `route()`, `url()`, dan `asset()` sekaligus. Tidak aktif bila `ASSET_URL` kosong, sehingga localhost tidak terpengaruh |
| 2026-08-17 | Daftar alamat **dibangkitkan dari kode**, bukan ditulis tangan | `sim:tautan-statis` membaca tabel rute dan `DummyData` langsung. Daftar tetap akan diam-diam ketinggalan setiap kali data contoh bertambah, dan halaman yang tidak ikut terbit tidak menimbulkan galat apa pun sehingga sulit disadari |
| 2026-08-17 | Nilai `path` pada `MenuHelper` **sengaja tetap relatif** | Dipakai ganda: sebagai `href` sekaligus pembanding status menu aktif. Mengubahnya menjadi alamat lengkap akan merusak penandaan menu aktif. Yang dibungkus `url()` hanya `href`-nya di `sidebar.blade.php` |
| 2026-08-17 | Penggilasan **diuji lengkap secara lokal** sebelum alur kerja diserahkan | Tiga cacat hanya muncul pada hasil gilasan, tidak pada uji Pest maupun tampilan localhost: `public/hot` yang membuat situs terbit tanpa gaya, 25 alamat menu telanjang, dan atribut `url` pada `stat-card`. Uji berbasis HTTP tidak memeriksa bentuk tautan pada keluaran |
| 2026-08-17 | Tautan tetap `/lacak-pengaduan/{nomor}` **dipertahankan meski backend masuk** | Ditambahkan agar halaman lacak tetap bekerja tanpa kueri, tetapi hasil pencarian yang dapat ditandai dan dibagikan tetap berguna pada versi ber-backend. Yang dihapus pada Tahap 8 hanya pengalihan `x-on:submit` bertanda `ponytail:` |
| 2026-08-18 | Kunci yang salah tulis pada pembangkit daftar **wajib melempar galat**, bukan dilewati | `isset()` membuat kekeliruan `no_sp` lolos tanpa jejak sehingga enam halaman rincian SP tidak pernah terbit, dan baru ketahuan sebagai 404 di tangan pengguna. Kegagalan yang terlihat saat build selalu lebih murah daripada kegagalan yang tersembunyi sampai produksi |
| 2026-08-18 | Penguncian gulir menyasar `<html>`, **bukan `<body>`** | `<html class="h-full">` dengan `<body>` tanpa tinggi membuat elemen penggulir adalah `<html>`, sehingga `overflow-hidden` pada `<body>` tidak mengunci apa pun dan panel modal ikut terbawa naik sampai tenggelam |
| 2026-08-18 | Penguncian gulir **dipusatkan pada satu modul**, bukan disalin ke tiap lapisan | Pola lama tersalin ke delapan berkas dan satu komponen justru terlewat tidak mengunci sama sekali. Modul bersama memakai penghitung lapisan agar modal bertumpuk tidak saling membuka kunci lebih awal |
| 2026-08-18 | Setiap `tutup()` diberi penjaga **keadaan terbuka** | `tutup-modal.window` disiarkan ke seluruh modal di halaman sekaligus. Tanpa penjaga, puluhan modal yang sedang tertutup ikut melepas kunci dan penghitung lapisan jatuh ke bawah nol |
| 2026-08-18 | Kolom asal SP **hanya muncul pada rekap per petani** | Pada rekap per SP kolom itu mengulang kolom pertama, sedangkan pada rekap per komoditas dan per musim isinya selalu daftar panjang lintas SP yang tidak menjawab pertanyaan apa pun. Disimpan sebagai himpunan agar tetap benar bila kelak satu petani berlahan di lebih dari satu SP |
| 2026-08-25 | Setiap field yang diterima form **wajib punya tempat tampil** pada halaman rinciannya | Delapan field di tujuh modul dapat diisi tetapi tidak pernah terbaca kembali, termasuk foto barang dan bukti dari pelapor. Unggahan yang tidak punya jalan dibuka adalah kontrol mati (R-26), dan catatan yang hilang dari pandangan sama saja dengan tidak dicatat. Lihat bagian 1f |
| 2026-08-25 | Uji yang mengunci **jumlah** wajib menyebut alasan angkanya | Ekspektasi "1 tautan berkas" pada alsintan dan saprotan ditulis dari hasil pengamatan, bukan dari kewajiban, sehingga uji tetap hijau selamanya sekalipun separuh berkasnya tidak dapat dibuka. Angka yang tidak beralasan mengabadikan keadaan yang sedang berlaku |
| 2026-08-25 | Uji penjaga konvensi teks wajib menjangkau **halaman rincian**, bukan hanya auth dan publik | Label "Surel" lolos ke rincian pengaduan meski larangannya sudah ada pada `ui-spec.md` 10.1 beserta ujinya, sebab daftar halaman yang diperiksa tidak memuat satu pun halaman rincian. Sebagian besar teks yang dilihat pengguna justru berada di sana |
| 2026-08-25 | Filter pada situs statis **dibiarkan tidak berfungsi**, hanya dicatat sebagai batasan | Penyaringan bekerja sungguhan di server sejak gelombang 2. Yang tidak bekerja adalah versi GitHub Pages, sebab query string tidak dilayani berkas statis. Pola tautan tetap tidak dapat dipakai karena kombinasi filter berlipat menjadi ratusan halaman untuk isi yang sama, dan batasannya lenyap sendiri saat Tahap 3 pindah ke hosting ber-PHP |
| 2026-08-25 | Form masuk **dirangkai sekarang**, tetapi tetap tanpa autentikasi | Tiga form auth lain sudah punya `action`, `@csrf`, dan rute POST berisi komentar "Tahap 3" beserta redirect kosong. Signin satu-satunya yang tidak, sehingga ini bukan pekerjaan yang belum waktunya melainkan satu form yang terlewat dari pola yang sudah ditetapkan. Tahap 3 tinggal mengisi badan rutenya, tidak perlu merangkai form dari nol. Lihat bagian 1g |
| 2026-08-25 | Alamat dasar untuk modul JavaScript **wajib dioper dari Blade** | Berkas JavaScript tidak mengenal `url()`, sehingga alamat yang ditulis tetap di dalamnya selalu salah begitu situs berpindah ke sub-path. `chart-config.js` merusak penelusuran 17 grafik dashboard dengan cara ini, delapan hari setelah larangannya tertulis pada 1b.3 |
| 2026-08-25 | Setiap larangan pada `notes.md` **wajib punya uji penjaga** | Larangan path absolut sudah tertulis sejak 2026-08-17 dan repo tetap kena masalah yang sama untuk ketiga kalinya. Aturan yang hanya tertulis terbukti tidak menahan apa pun; yang menahan adalah uji yang memerah |
| 2026-08-25 | Laporan penelusur diperlakukan sebagai **kandidat**, bukan temuan | Dua dari sebelas laporan terbukti keliru saat diverifikasi terhadap berkas, yaitu form tanpa `@csrf` dan dua komponen yang disebut mati. Mengerjakannya berarti menghabiskan waktu atas masalah yang tidak ada |
| 2026-08-25 | Temuan 6, 7, dan 8 **ditunda dengan sengaja**, bukan terlupa | Ketiganya nyata tetapi tidak merusak apa pun yang sedang dipakai: `<caption>` menyangkut pembaca layar, komponen yatim menyangkut kebersihan, dan 37 path absolut melekat pada form POST yang toh sudah mati di versi statis. Dicatat lengkap pada 1g.7 agar tidak hilang bersama sesinya |

---

## 4. Tindak Lanjut yang Disarankan

Poin 1 dan 2 sudah selesai pada 2026-08-11.

1. ~~Susun ulang ERD berdasarkan koreksi pada bagian 1, sebelum menulis migration Laravel.~~ **Selesai** ? `erd.md`
2. ~~Buat data dictionary sebagai deliverable Fase 2 (`workflow.md` �2.2).~~ **Selesai** ? `data-dictionary.md`
3. Konfirmasi ke tim lapangan: daftar satuan yang benar-benar dipakai per komoditas, untuk mengisi data master `satuan` dan menetapkan satuan baku tiap komoditas. *Sementara memakai Ton, Kuintal, Kilogram.*
4. Konfirmasi apakah lahan pekarangan juga dapat lebih dari satu per KK, atau dipastikan selalu satu. *Saat ini struktur dibuat one-to-many agar fleksibel.*
5. Pastikan penanganan kasus rumah yang ditinggalkan sementara: apakah tetap berstatus Dihuni dengan penghuni terdaftar, atau dilepas menjadi kosong. *Sementara tetap Dihuni, dicatat pada `rumah.catatan_hunian`.*
6. Konfirmasi apakah satu transmigran dapat menjadi anggota lebih dari satu poktan. *Sementara diasumsikan tidak, mengikuti `rules.md` �6.4.*
7. Konfirmasi spesifikasi hosting/VPS target, khususnya versi PHP yang tersedia, sebelum tahap deployment.
8. Putuskan apakah mode gelap bawaan TailAdmin dipertahankan atau dimatikan, setelah uji coba bersama operator lapangan.
9. ~~**Audit keputusan lama yang bersandar pada data contoh** (disepakati 2026-08-19).~~ **Selesai 2026-08-19.** Seluruh 992 baris `notes.md` disisir; 36 keputusan menyebut data contoh sebagai alasan, **5 di antaranya cacat dan menyangkut struktur data**. Dua pertanyaan lapangan yang muncul dijawab pemilik proyek, bukan disimpulkan. Hasil lengkapnya pada bagian 1c.4, dan dua pelanggaran baru yang ditemukan tercatat pada 1c.2 sebagai pelanggaran keempat dan kelima.
10. ~~**Tinjau ulang ambang searchable dropdown setelah data nyata masuk**, khusus `/rumah` dan `/riwayat-tanam`.~~ **SELESAI 2026-08-20, dengan cara yang berbeda dari yang direncanakan di sini.** Butir ini menulis `Ambang 8 opsinya sendiri tidak bermasalah`, dan kalimat itu ternyata keliru: ambangnya justru yang bermasalah, sebab ia membandingkan terhadap jumlah baris data contoh yang dikarang sendiri. Ambang dicabut seluruhnya dan kriterianya menjadi sifat sumber. Tidak ada lagi yang perlu ditinjau saat data nyata masuk. Lihat bagian 1c.2 pelanggaran keenam.
11. **Ulangi audit `rules.md` 19a secara berkala**, tidak cukup sekali. Audit 2026-08-19 menemukan dua pelanggaran yang terjadi **setelah** aturannya berlaku, salah satunya melanggar prinsip yang tertulis 400 baris di atasnya pada dokumen yang sama. Lihat bagian 1c.5.
12. **Putuskan ide C sebelum Tahap 4 dibuka**, yaitu memindahkan pengambilan data dari view ke rute selagi isinya masih array. 272 pemanggilan `DummyData::` tersebar di 67 berkas Blade; selama view mengambil datanya sendiri, migrasi ke Eloquent berubah menjadi penyuntingan 67 view beserta N+1 di setiap perulangan. Biayanya hanya naik seiring waktu. Lihat bagian 1g.7.
13. **Perluas penjaga path absolut ke prop aksi Blade.** Uji yang dibuat 2026-08-25 hanya menyisir `resources/js`, sedangkan 37 kemunculan pada `:hapus-url` dan `pola-aksi` masih ada dan belum dijaga apa pun. Akarnya dua komponen saja. Lihat bagian 1g.7 temuan 8.
14. **Sisir ulang `ui-spec.md` saat 15 komponen yatim dicabut.** Empat di antaranya masih tercatat di sana sebagai komponen basis, sehingga menghapus berkasnya saja akan meninggalkan dokumen yang menjanjikan sesuatu yang tidak ada. Lihat bagian 1g.7 temuan 7.


## 5. Catatan Ide
- [done] update hirarki. setelah kabupaten itu bukan desa, melainkan kawasan transmigrasi. Jadi nanti dari kawasan transmigrasi bakal menginputkan daftar Satuan Pemukiman (SP) yang ada pada kabupaten tersebut. Nah, nanti informasi desa dan kecamatan itu ada di dalam SP gitu.
- [done] Untuk lebih lengkapnya, hirarkinya kira-kira begini: Provinsi --> Kabupaten --> Kawasan Transmigrasi X --> Satuan Pemukiman (SP). Di mana nanti untuk SP memuat informasi seperti: Nama SP, kecamatan SP, desa SP, koordinat SP, Inventaris SP, dan fasilitas SP.
- [done] rute aplikasi sim transmigrasinya pindah ke folder sistem informasi transmigrasi, jadi bukan dalam folder sistem informasi transmigrasi lalu buat folder app dan menaruh semua folder dan file proyeknya di dalam folder app.
- [done] apakah sudah ada halaman login dan lupa password? Oh iya, ini gak ada halaman signup ya, sebab nanti untuk pembuatan akun ada di role admin di manajemen akun. Bagaimana menurutmu untuk sistem ini?
  * Keputusan: tanpa pendaftaran mandiri, tanpa pemulihan lewat surel. Kredensial dua jenis (email untuk dinas, NIK untuk transmigran). Rincian pada `rules.md` �14b.
- [done] bahas role kira2 apa saja
  * Keputusan: role dinamis lewat tabel `role`, `permission`, `role_permission`. Empat role bawaan: Admin (terkunci), Dinas Transmigrasi, Dinas Pertanian, Operator SP. Role Transmigran dan Ketua Poktan dihapus, pengaduan warga lewat kanal publik tanpa login. Rincian pada `rules.md` �5.
- [done] di form dropdown pada halaman dahsboard saat di dark mode, warna latar gulirannya putih dan font-nya juga putih, sehingga tidak terlihat. perbaiki semua form dropdown yg terdapat pada sistem ini misal saat di dark mode, warna latar gulirannya putih dan font-nya putih juga, sehingga tidak terlihat. Soalnya saya cek di beberapa halaman lainnya, kasusnya mirip di for dropdown pada halaman dashboard saat di dark mode.
  * Keputusan: diperbaiki lewat **satu aturan CSS global** di `app.css`, bukan menambal 111 `<select>` satu per satu. Cacat ini luput lama karena verifikasi visual hanya melihat select dalam keadaan tertutup; yang tertutup selalu tampak baik sebab yang dirender adalah kotaknya, bukan daftarnya. `ui-spec.md` 3.2.3 kini mewajibkan dropdown dibuka saat verifikasi.
- [done] hyperlink lupa kata sandi di halaman login harusnya ada di kata "Lupa kata sandi?", bukan di "Minta kode verifikasi".
  * Keputusan: tautan dipindah ke frasa `Lupa kata sandi?`, sebab itulah yang dicari mata pengguna. Komentar usang yang masih menyatakan sistem tidak menyediakan pemulihan lewat surel ikut dihapus.
- [done] semua berkas yg diunggah ke sistem, harusnya terdapat fitur untuk lihat/buka berkasnya. Apakah sudah ada fiturnya?
  * Keputusan: `DokumenController` beserta rutenya ternyata **sudah lengkap sejak awal**, tetapi tidak satu pun halaman memakainya. Dibuatkan komponen `x-sim.tautan-dokumen` lalu dipasang pada dokumen lahan, rumah, dan transmigran. Halaman transmigran sempat menaut **path penyimpanan mentah**, yang tidak akan pernah terbuka sebab berkas berada di luar folder public.
- [done] kenapa dokumen lahan di-upload terpisah?
  * Keputusan: **tetap terpisah**. Satu lahan dapat memiliki HPL dan SHM sekaligus, masing-masing dengan nomor dan tanggal terbitnya sendiri, sehingga tidak muat pada satu kolom unggahan di form lahan. Alasannya kini ditampilkan di tab Dokumen agar pemisahan itu tidak tampak sebagai ketidakkonsistenan.
- [done] restrukturisasi side bar:
    MENU - Dashboard
    WILAYAH TRANSMIGRASI |
                         |__ (dropdown) Kawasan Transmigrasi, Satuan Pemukiman, Inventaris SP, Fasilitas SP

    KEPENDUDUKAN |
                 |__ (dropdown) Transmigran, Rumah & Hunian, Daftar Lahan, Rekap Kependudukan

    PERKUMPULAN |
                |__ (dropdown) Kelompok Tani, Alsintan, Saprotan, Infrastruktur (rename-> Infrastruktur Pertanian)

    PERTANIAN |
              |__ (dropdown) Komoditas, Musim Tanam, Riwayat Tanam, Hasil Panen

    PENGADUAN |
              |__ (dropdown) Daftar Pengaduan, Rekap Pengaduan

    ADMINISTRASI SISTEM |
                        |__ (dropdown) Pusat Laporan, Data Master Wilayah, Data Master Satuan, Pengguna, Role & Hak Akses, Audit Log

  * Keputusan: disusun menjadi **tujuh kelompok bersubmenu** sesuai usulan, dengan dua penyesuaian. **Daftar Lahan** dipindahkan ke Kependudukan seperti diminta. **Infrastruktur SP** tidak dimasukkan ke Perkumpulan, melainkan ke Wilayah Transmigrasi: alsintan dan saprotan milik poktan, sedangkan irigasi, listrik, dan jalan milik satuan permukiman, sehingga menggabungkannya justru menyiratkan kepemilikan yang keliru.
  * **Rekap Panen** ikut ditambahkan ke menu. Halaman itu sudah ada sejak Task 2.10 tetapi tidak pernah tercantum di sidebar, sehingga hanya dapat dicapai dari halaman panen.
  * Modul infrastruktur **dipertahankan, bukan ditiadakan**. Modul ini menyuplai 9 dari 13 parameter penilaian kondisi SP, termasuk ketiga parameter primer (air bersih, jalan penghubung, listrik). Kebingungan yang muncul berasal dari namanya, bukan dari data yang tumpang tindih: kedua enum tidak memiliki satu pun nilai yang beririsan. Namanya kini **Infrastruktur SP**, sejajar dengan Inventaris SP dan Fasilitas SP.
  * Pembeda keduanya: **fasilitas adalah bangunan tempat warga berkegiatan** (sekolah, puskesmas, balai), sedangkan **infrastruktur adalah jaringan yang mengalirkan sesuatu** (air, daya, lalu lintas). Perbedaan itu terbaca dari kolomnya: fasilitas punya ``jumlah`` dan ``status_penyerahan``, infrastruktur punya ``kapasitas`` dan ``poktan_id``.

- [done] di tiap datatable itu kan bisa lihat masing2 data dan hapus data, nah kenapa untuk update data harus klik lihat data dan baru muncul tombol update-nya?
  * Keputusan: tombol **Ubah ditambahkan pada baris tabel**, sejajar dengan Hapus. Susunan lama janggal: menghapus jauh lebih berisiko daripada menyunting, tetapi justru lebih mudah dijangkau. Satu modal melayani seluruh baris, datanya dikirim lewat peristiwa saat tombol diklik. Merender satu modal per baris akan menggandakan form sebanyak baris pada satu halaman.
- [done] setelah restrukturisasi side bar tadi, apakah sudah menangani ketika misal role Operator SP login, harusnya ada beberapa fitur/halaman di side bar yg di-hide berdasarkan hak akses role Operator SP gitu kan? Ini cuma tanya saja.
  * Jawaban: **mekanismenya sudah siap, tetapi belum aktif.** `MenuHelper::bolehLihat()` masih selalu mengembalikan true dengan penanda `ponytail:` yang menyebut penggantinya di Tahap 3. Struktur penyaringnya sudah lengkap, termasuk penyaringan submenu: induk yang seluruh submenunya tersaring tidak ikut dirender, agar tidak ada menu yang membuka daftar kosong. Begitu RBAC aktif, Operator SP otomatis tidak melihat Pengguna, Role, Audit Log, dan Data Master.
- [done] apakah sudah ada pendataan terkait pemisahan lahan basah dan lahan kering?
  * Jawaban: **sudah lengkap**. Enum `KategoriLahan` bernilai Lahan Basah dan Lahan Kering, dipakai pada form lahan, filter halaman daftar, kolom tabel, halaman rincian, dan data contoh. Diatur pada `rules.md` 7.5.
- [done] restrukturisasi side bar lagi:
  Menu - Dashboard
  Transmigrasi - Wilayah & Aset SP, Penduduk & Lahan
  Pertanian - Poktan & Sarana, Produksi Pertanian
  Pengaduan - Pengaduan Warga
  Administrasi Sistem - Laporan & Pengaturan
  * Keputusan: digabung menjadi **lima kelompok** sesuai usulan. Kelompok Transmigrasi dan Pertanian masing-masing memuat dua submenu, sehingga satu judul kelompok menaungi lebih dari satu daftar. Nama submenu mengikuti penulisan pada catatan: Poktan & Sarana menggantikan Kelompok & Sarana.
- [done] tambahkan form email di halaman pengaduan agar nanti kode pelacakannya bisa dikirim lewat email.
  * Keputusan: kolom surel ditambahkan sebagai **opsional**, diletakkan setelah nomor HP. Bila diisi, nomor pengaduan dikirim juga ke sana sebagai salinan. Nomor tetap ditampilkan besar di layar setelah kirim, sehingga surel tidak pernah menjadi satu-satunya cara menerimanya. Tidak diwajibkan karena jaringan lokus tidak selalu memadai dan sebagian warga tidak memiliki surel; mewajibkannya akan menutup kanal yang justru paling perlu terbuka (`rules.md` 10b poin 1c-1).
- [done] di halaman dashboard, samping kanan card visualiasi perkerjaan kepala keluarga kan ada space kosong. Enaknya diisi apa ya? Ada ide kah? Mungkin ada visualisasi data yg belum ditampilkan? Atau kalau tidak ada lagi, bagaimana kalau card pekerjaan kepala keluarga dibuat memanjang untuk menutupi space kosong tadi? Bagaimana menurutmu? Apakah ada solusi lain?
  * Keputusan: kartu **Pengaduan per Status** ditambahkan sebagai indikator ke-17, mengisi kolom yang menganggur di samping kartu pekerjaan. Data `rekapPengaduan` sudah tersedia tetapi belum pernah ditampilkan pada dashboard utama, padahal menjawab pertanyaan yang paling sering muncul: berapa laporan yang masih menunggu ditangani. Kartu pekerjaan tetap 2 kolom sebab labelnya panjang dan justru lebih terbaca pada kartu lebar.
- [done] buat icon yg sesuai pada masing-masing menu Penduduk & Lahan, Poktan & Sarana, Produksi Pertanian, dan Laporan & Pengaturan. Jangan pakai icon bintang semua atau icon yg sama dengan menu lainnya yg sudah ada.
  * Penyebab: `MenuHelper::getIconSvg()` hanya mengenal lima belas nama ikon, dan **ikon bintang adalah nilai cadangan** untuk nama yang tidak dikenal. Empat menu memakai nama yang tidak terdaftar, sehingga keempatnya jatuh ke cadangan yang sama.
  * Keputusan: ditambahkan empat ikon bermakna, yaitu dua sosok untuk Penduduk & Lahan, tiga sosok berhimpun untuk Poktan & Sarana, tunas bertumbuh untuk Produksi Pertanian, dan roda gigi untuk Laporan & Pengaturan. Ditambah uji yang memerah bila ada menu memakai ikon tak terdaftar, sebab akar masalahnya justru di situ.
- [done] bisa gak ya ketika user sedang klik rincian pada suatu sub menu (misal sub menu transmigran-->lihat transmigran), side bar sub menu Transmigran tetap terbuka, sebab dia kan masih dalam cakupan sub menu Transmigran itu, jadi harusnya side barnya masih aktif terbuka. Bagaimana menurutmu?
  * Keputusan: pencocokan diubah dari **sama persis** menjadi **awalan diikuti garis miring**, sehingga `/transmigran/1` tetap membuka submenu Transmigran. Sorotan memakai jalur terpanjang yang cocok, agar membuka `/sp/inventaris` tidak menyorot Satuan Permukiman dan Inventaris SP sekaligus.
- [done] Ketika aku coba open link in new tab, mengapa tiba-tiba bagian kanan pada side bar itu resolusinya membesar di pembesaran/zoom 100%? Namun ketika di-refresh, resolusinya balik ke normal di pembesaran/zoom 100%.
  * Penyebab: `window.innerWidth` bernilai **nol** pada tab yang dibuka di latar belakang, sebab tab itu belum dilukis peramban. Sidebar mengira layarnya sempit lalu menyempit ke 90px dan konten di sebelahnya ikut bergeser. Setelah disegarkan, tab sudah aktif dan lebarnya terbaca benar.
  * Keputusan: nilai nol **tidak lagi dipercaya**; selama lebar belum terbaca, tampilan desktop dianggap berlaku. Ditambah pendengar `pageshow` dan `visibilitychange` agar tata letak dihitung ulang saat pengguna berpindah ke tab tersebut, sehingga tidak perlu disegarkan manual.
- [done] Di menu Wilayah & Aset SP kenapa pada tabelnya mayoritas tidak punya fitur view, edit, delete ya? Lalu di sub menu infrastruktur SP, di bagian kolom aksi, kenapa hanya ada rincian dan ubah? Apakah delete tidak perlu? Lalu kenapa di sub menu infrastruktur tombol aksinya berupa tulisan sedangkan sepertinya di halaman lain dalam bentuk icon gitu?
  * Keputusan: seluruh halaman daftar kini memakai komponen `x-sim.aksi-baris` berbentuk **ikon** dengan urutan sama: Rincian, Ubah, lalu Hapus paling kanan. Delapan halaman yang sebelumnya tanpa kolom aksi kini memilikinya. **Satuan Permukiman memakai halaman drill-down yang sudah ada** di `/dashboard/sp/{sp}`, bukan halaman rincian baru, sebab halaman itu sudah menampilkan inventaris, fasilitas, penduduk, dan penilaian kondisinya. Aturannya dicatat pada `ui-spec.md` 5.1a.
- [done] Di menu Poktan & Sarana, kenapa di halaman Kelompok Tani tidak punya fitur view, edit, delete ya? Lalu di sub menu alsintan dan saprotan, di bagian kolom aksi, kenapa hanya ada rincian dan ubah? Apakah delete tidak perlu? Lalu kenapa di sub menu infrastruktur tombol aksinya berupa tulisan sedangkan sepertinya di halaman lain dalam bentuk icon gitu?
  * Keputusan: sudah seragam. Kelompok Tani kini punya kolom aksi lengkap; Alsintan dan Saprotan mendapat tombol Hapus serta diubah ke bentuk ikon.
- [done] Untuk pengisian data ketua poktan kan sudah ada di halaman kelompok tani. Namun untuk pengisian anggotanya yg diambil dari data Transmigran apakah sudah ada? Oh iya, misal ketua poktan ternyata merupakan transmigran, maka ambil datanya dari Transmigran. Tolong buat mekanismenya ya. Bagaimana menurutmu?
  * Jawaban: **mekanismenya sudah ada**. Ketua poktan dipilih dari daftar transmigran lewat `ketua_transmigran_id`, bukan diketik bebas, agar NIK dan tautan profilnya tetap sahih. Form anggota poktan juga sudah ada di halaman rincian poktan, dan anggotanya dipilih dari data transmigran yang sama.
- [done] Di menu produksi pertanian, kenapa di halaman Musim Tanam dan Riwayat Tanam tidak punya fitur view, edit, delete ya? Lalu di sub menu komoditas, di bagian kolom aksi, kenapa hanya ada rincian dan ubah? Apakah delete tidak perlu? Lalu kenapa di sub menu infrastruktur tombol aksinya berupa tulisan sedangkan sepertinya di halaman lain dalam bentuk icon gitu?
  * Keputusan: sudah seragam. Musim Tanam dan Riwayat Tanam kini punya kolom aksi lengkap, dan Komoditas mendapat tombol Hapus serta diubah ke bentuk ikon.
- [done] Di datatabel pada halaman daftar pengaduan, ada kolom prioritas. Bagaimana sistem mengetahui terkait skala prioritas yg ditampilkan di datatabel pada halaman daftar pengaduan? Soalnya di halaman pengaduan warga, tidak ada kolom prioritas. Lalu di form float modal ubah/edit, terdapat form titik kejadian/gps/titik koordinat, namun di halaman pengaduan warga tidak tersedia kolom gps/titik koordinat. Lalu untuk alur penanganan, bagaimana kalau di float modal ubah/edit ditambahkan juga untuk update status penanganan? Jadi nanti admin bisa lebih efisien misal dia mau langsung update status penanganannya tanpa harus klik lihat pengaduan. Atau bisa juga tambah icon di kolom aksi untuk update status penanganan. Pokoknya mana yg lebih baik menurutmu. Bagaimana pendapatmu?
  * Keputusan: **prioritas diturunkan otomatis dari kategori**, lalu dapat direvisi petugas saat meninjau. Warga tidak diminta menilai kegentingan laporannya sendiri, sebab ia tidak mengetahui skala prioritas dinas dan hampir seluruh laporan akan ditandai mendesak sehingga penandanya kehilangan makna. Mengikuti pola `BidangPengaduan::dariKategori()` yang sudah dipakai untuk menentukan dinas penanganan (`rules.md` 10b poin 6a dan 6b).
  * **Koordinat sengaja tidak diminta pada kanal publik.** Warga melapor lewat ponsel berjaringan terbatas; menambah isian itu membebani kanal yang justru paling perlu terbuka. Petugas melengkapinya saat verifikasi lapangan (poin 6c).
  * **Pembaruan status ditambahkan sebagai ikon tersendiri** di kolom aksi, bukan digabung ke modal ubah. Menangani laporan berbeda sifat dari menyunting isinya dan tercatat berbeda pada audit log; menggabungkannya berisiko status ikut berubah ketika petugas hanya membetulkan salah ketik. Alur tetap maju satu langkah dan tidak boleh melompat.
- [done] Di menu laporan & pengaturan, kenapa di halaman data master wilayah dan data master satuan tidak punya fitur view, edit, delete ya? Lalu di sub menu pengguna, di bagian kolom aksi, apakah memungkinkan untuk dibuatkan icon agar lebih ringkas?
  * Jawaban: **sudah seragam sekarang**. Data master wilayah dan satuan kini punya kolom aksi Ubah dan Hapus, tanpa Rincian sebab seluruh datanya sudah tampil di tabel. Halaman pengguna diubah dari tombol teks menjadi ikon, memuat empat tindakan: rincian, ubah, setel ulang kata sandi, dan nonaktifkan. Penanda `Admin terakhir` tetap menggantikan tombol nonaktifkan pada Admin aktif terakhir (`rules.md` 14b poin 16).
- [done] pada semua isian titik koordinat di sistem ini, apakah sudah dibuat fungsi/tombol untuk lihat lokasinya berdasarkan titik koordinat yg sudah diberikan? mungkin nanti bentuknya dalam modal float yg mengakses ke google maps. bagaiman menurutmu?
  * Temuan: fiturnya **sebagian sudah ada**, memakai OpenStreetMap, tetapi hanya pada tiga dari enam tempat dan ditulis manual di masing-masing halaman.
  * Keputusan: dibuatkan komponen `x-sim.tautan-peta` berisi modal peta baca-saja, lalu dipasang pada rincian lahan, pengaduan, fasilitas SP, dan poktan. Tiga tautan manual yang lama ikut digantikan agar polanya seragam.
  * Untuk form pengisian, `x-sim.koordinat-input` mendapat **peta pemilih titik** dengan penanda yang dapat digeser. GPS ponsel di lokus kerap meleset puluhan meter, sedangkan pengisi paling mengetahui letak sebenarnya (`rules.md` 10b poin 6d).
  * Leaflet dimuat lewat **impor dinamis**, hanya ketika peta dibuka. Hanya enam form yang memerlukannya, sehingga menyertakannya pada bundel utama membebani seluruh halaman lain tanpa alasan.
  * **Data contoh ikut dilengkapi.** Lahan, poktan, dan pengaduan tidak memiliki kolom koordinat sama sekali padahal kamus data mencantumkannya, sehingga tautan peta tidak akan pernah tampil dan fiturnya tampak tidak berfungsi. Dua pengaduan sengaja dibiarkan tanpa koordinat agar keadaan tanpa titik ikut teruji.
- [done] titik koordinat tetap dibuat di halaman pengaduan warga saja.
  * Keputusan: koordinat ditambahkan pada kanal publik sebagai **opsional**. Pengaduan tetap dapat dikirim tanpa mengisinya, sebab warga melapor lewat ponsel berjaringan terbatas dan mewajibkannya akan menutup kanal yang justru paling perlu terbuka. `rules.md` 10b poin 6c direvisi dari tidak diminta menjadi diminta tetapi opsional.
- [done] halaman baru yg terbuka dari klik open new tab dari logo transmigrasi di cms itu halaman dashboard di sebelah kanan sidebar-nya masih membesar gitu. Sudah saya SS kan kondisinya di (../docs/ss-ketika-open-new-tab-dari-logo-transmigrasi-di-cms.jpg). Nah, ketika halaman dashboard yg membesar itu di-refresh, halaman dashboard-nya jadi normal lagi seperti yg terlihat di (../docs/ss-setelah-di-refresh.jpg)
  * **Penyebab sebenarnya ditemukan, dan berbeda dari dugaan awal.** Sidebar berposisi `fixed` sehingga keluar dari alur dan tidak memakan ruang di dalam pembungkus `flex`. Akibatnya `flex-1` menghitung lebar penuh layar, lalu `xl:ml-[290px]` menambahkan 290px lagi: lebar totalnya menjadi 100% + 290px.
  * Perbaikan sebelumnya yang menambal nilai `innerWidth` **menangani gejala yang salah** dan sudah dibuang. Tangkapan layar membuktikannya: sidebar tampil lebar penuh, jadi `isExpanded` memang sudah benar. Yang meluber justru kontennya.
  * Keputusan: menambahkan `min-w-0` pada pembungkus konten, penanda baku agar item flex boleh menyusut lebih kecil dari isinya sehingga `flex-1` menghitung ruang yang benar-benar tersisa setelah margin.
- [done] untuk setiap form yg membutuhkan titik koordinat, selain minta akses Lokasi ke device, munculkan maps juga agar titik koordinatnya bisa diarahkan semisal gps-nya kurang akurat.
  * Keputusan: peta pemilih titik ditambahkan pada `x-sim.koordinat-input`, sehingga seluruh enam form yang memerlukan koordinat mendapatkannya sekaligus. Penanda dapat digeser, dan mengetuk peta juga memindahkannya, sebab pada layar sentuh menggeser penanda jauh lebih sulit daripada menyentuh titik yang dituju.
  * Peta memakai **Leaflet dengan ubin OpenStreetMap**, tanpa kunci API, sejalan dengan tautan peta yang sudah dipakai proyek ini. Dimuat lewat impor dinamis sehingga bundel utama tetap seringan sebelumnya.
  * Peta adalah **pelengkap, bukan syarat**. Bila ubin gagal dimuat karena jaringan lemah, muncul keterangan yang menyebutkan bahwa isian manual dan tombol lokasi otomatis tetap dapat dipakai.
- [done] tambahkan juga tombol "ambil lokasi saat ini" di dalam semua modal float peta di sistem ini.
  * Temuan: **logikanya sudah ada, tombolnya yang belum**. `ambilLokasi()` pada `x-sim.koordinat-input` sejak awal sudah memindahkan peta yang sedang terbuka, tetapi tombolnya hanya diletakkan di luar modal. Selagi peta terbuka tombol itu terhalang lapisan modal, sehingga petugas yang ingin kembali ke posisi sebenarnya setelah menggeser penanda terpaksa menutup peta lebih dulu.
  * Keputusan: "semua modal float peta" ditafsirkan sebagai **modal form saja**, yaitu tujuh form pemakai `x-sim.koordinat-input`. Modal baca-saja `x-sim.tautan-peta` sengaja dilewati sebab titiknya milik lahan atau rumah yang sedang dilihat, bukan milik pengguna; tombol yang memindahkannya akan mengubah data yang justru tidak boleh disunting dari halaman rincian.
  * **Pesan galat ikut disalin ke dalam modal.** Pesan yang lama berada di luar dan terhalang lapisan penutup layar, sehingga izin lokasi yang ditolak tidak memunculkan tanda apa pun dan tombolnya tampak rusak ketika ditekan.
- grafik di dashboard masih ada yang melebar ketika halaman dibuka lewat tab baru, meski tiga lapisan penanganan sudah dipasang. Ditunda atas permintaan, belum ditelusuri ulang.
- [done] ada saran gak agar Kumpulan visualisasi di dashboard yg banyak itu bisa rapi, enak dipandang, dan user bisa lebih mudah memahami informasinya? Sekarang kan visualisasinya campur dan acak gitu ya. Jadi orang awam pun bakal susah mencerna informasinya. Coba untuk hal ini kita diskusi. Apakah kamu punya ide yg bagus terkait masalah ini?
  * Penyebab: **urutannya, bukan jumlahnya.** Dua puluh dua blok tersusun menurut nomor indikator PRD, bukan menurut topik, sehingga mata pembaca dilempar dari penduduk ke pertanian, kembali ke penduduk, lalu ke pengaduan. Topik pertanian bahkan terpecah di posisi 2, 8, dan 9 &mdash; terpisah enam kartu, sehingga tidak pernah terbaca sebagai satu pokok bahasan.
  * Keputusan: dikelompokkan menjadi **enam bagian bertajuk** yang mengikuti pertanyaan pemangku kepentingan, yaitu Ringkasan Kawasan, Kependudukan, Pertanian dan Ekonomi, Infrastruktur dan Layanan, Perbandingan Antar SP, lalu Tata Kelola Data. Tidak ada grafik dihapus maupun ditambah; seluruhnya hanya dipindah dan diberi tajuk.
  * **Mutu data dipindah ke bagian akhir.** Yang diukur adalah mutu datanya sendiri, bukan keadaan kawasan, sehingga letaknya di antara grafik panen dan infrastruktur membuatnya terbaca keliru sebagai indikator lapangan.
  * Dibuatkan komponen `x-sim.judul-bagian` memakai `<h2>`, sekaligus **membetulkan hierarki tajuk** yang sebelumnya melompat dari `<h1>` halaman langsung ke `<h3>` kartu grafik.
  * Lebar kartu disetel ulang agar **tiap baris grid genap tiga kolom**: Pendapatan Keluarga dijadikan kartu lebar, sedangkan Perbandingan Antar SP dan Mutu Data berdiri selebar halaman. Tanpa itu tersisa kolom menganggur di tiga tempat.
  * Skrip grafik **tidak perlu disentuh sama sekali**, sebab `buatGrafik()` memanggil lewat id, bukan urutan DOM.
- [sebagian] tambahkan fungsi import data setelah backend selesai
  * **Antarmukanya selesai, penyimpanannya menunggu backend.** Dikerjakan lebih dulu mengikuti strategi Tahap 2 yang membangun tampilan dengan data contoh.
  * Temuan: impor ternyata **bukan tambahan, melainkan kebutuhan PRD 8.1** yang belum dikerjakan. Sinyal di lokus tidak selalu stabil, sehingga sistem wajib menyediakan template luring yang diunduh, diisi di lapangan, lalu diunggah kembali saat sambungan tersedia. `erd.md` 5 juga sudah menyebut impor sebagai jalur masuk data yang integritasnya harus dijaga database, bukan hanya form.
  * Keputusan: dipasang pada **14 modul berdata banyak**, yaitu Transmigran, Rumah, Lahan, Panen, Riwayat Tanam, Infrastruktur, Inventaris SP, Master Wilayah, Master Satuan, Poktan, Alsintan, Saprotan, Fasilitas SP, dan Komoditas.
  * **Lima modul sengaja dikecualikan.** Pengaduan datang dari kanal publik satu per satu dan nomornya wajib memuat bagian acak sehingga tidak dapat disiapkan di Excel; Pengguna tidak diberi impor sebab kata sandi awal diserahkan langsung kepada orangnya (`rules.md` 14b poin 3) dan impor massal berarti kata sandi berkeliaran di berkas yang berpindah tangan; Role, Kawasan, dan SP **berjumlah tetap menurut `prd.md`** yaitu satu kawasan, enam SP, dan empat role bawaan, sehingga impor hanya menambah jalur masuk tanpa manfaat.
  * **KOREKSI 2026-08-19: Musim Tanam dikeluarkan dari daftar pengecualian** dan kini memiliki impor. Ia sempat disamaratakan dengan Role, Kawasan, dan SP lewat alasan "jumlah barisnya sedikit", padahal ketiga modul itu berjumlah tetap menurut dokumen acuan sedangkan **musim tanam bertambah dua kali setahun tanpa henti**. Alasan lamanya menghitung baris data contoh, dan itu persis penalaran yang dilarang `rules.md` 19a; lebih menusuk lagi, ia melanggar prinsip "tanpa sumbu waktu" yang tertulis pada bagian 1c.1 poin 1 di dokumen yang sama. Kebutuhannya dipastikan lewat keterangan pemilik proyek: dinas memang perlu memasukkan riwayat musim tahun-tahun sebelumnya secara massal. Modul berimpor 14 menjadi **15**.
  * Alurnya **tiga langkah**: unduh template, unggah berkas, lalu pratinjau hasil. Langkah ketiga yang paling menentukan, sebab impor yang hanya berkata "gagal" memaksa petugas menebak barisnya, padahal berkas berisi ratusan baris tidak mungkin diperiksa manual. Karena itu kegagalan selalu disertai nomor baris beserta alasannya.
  * **Spanduk "Fitur belum aktif" wajib ada** di dalam modal. Tombolnya terlihat berfungsi penuh padahal penyimpanannya belum ada, sehingga tanpa peringatan petugas dapat mengira datanya sudah masuk lalu kehilangan hasil pendataan sehari penuh.
  * Satu rute `GET /template-impor/{entitas}` melayani seluruh entitas, sebab yang membedakan hanya susunan kolomnya.
- [done] Toggle "akun aktif" pada form di modal floating tambah akun petugas dihilangkan, jadi ketika menambahkan akun petugas itu auto aktif. Selain itu, setelah akunnya terbuat, sistem otomatis kirim email notifikasi berupa detail informasi akun beserta password-nya. Bagaimana menurutmu? Apakah ada concern mengapa toggle akun aktif harus ada?
  * **Toggle dihapus sepenuhnya**, bukan hanya pada modal tambah. Halaman daftar sudah memiliki tombol ikon nonaktifkan per baris, sehingga toggle pada formulir menjadi jalur kedua untuk keadaan yang sama. Dua jalur membuat riwayat audit terpecah dan menyulitkan penelusuran. Akun baru kini selalu langsung aktif.
  * **Surel memuat kata sandi, sekaligus tetap tampil di layar.** Permintaan pengiriman lewat surel dipenuhi, tetapi tampilan layar dipertahankan sebab jalur Admin justru dibuat untuk petugas di lokus bersinyal lemah. Bila kata sandi hanya dikirim lewat surel, petugas yang sedang berdiri di depan Admin tidak dapat membacanya sampai memperoleh sinyal.
- [done] Pada form kata sandi awal di modal floating tambah akun petugas, apakah lebih baik password di-generate sistem atau admin yg memasukkan password-nya? Bagaimana dengan username juga? Harusnya yg buat username itu pemilik akun sendiri gak sih? Bukan adminnya yg pusing bikin username orang lain? Jadi nanti yg wajib diganti/diisi pertama kali saat user login awal adalah ganti password dan buat username. Bagaimana menurutmu? Oh iya, field form username tambahakn real time checking pakai ajax ya apakah sudah ada yg pakai username tersebut atau belum.
  * Keputusan: **username dibuat petugas sendiri** saat pertama kali masuk, bersamaan dengan penggantian kata sandi sementara. Admin tidak lagi mengarangkannya, sebab petugaslah yang akan mengetiknya setiap hari.
  * **Konsekuensinya surel menjadi wajib.** Bila username belum ada saat akun lahir, surel adalah satu-satunya kredensial yang dimiliki petugas untuk masuk pertama kali. Isian surel karena itu ditandai wajib pada modal tambah.
  * **Kata sandi awal dibangkitkan sistem**, bukan diketik Admin. Kata sandi karangan manusia cenderung berpola dan dipakai ulang untuk banyak akun sekaligus.
  * Pemeriksaan ketersediaan username saat diketik dicatat pada `rules.md` 14b poin 5a; pelaksanaannya menunggu backend.
- [done] ketika kata sandi diubah oleh admin, kirimkan email notifikasi ke user bahwa sandinya telah diubah dan kasih tahu sandinya juga di email.
  * Dipenuhi bersama b471: kata sandi hasil setel ulang dikirim ke surel pengguna. Peringatan pada modal setel-sandi ditulis ulang, bukan dihapus, sebab penyerahan langsung tetap wajib bagi petugas di lokus bersinyal lemah.
- [done] Di halaman pengguna kan ada icon tombol non aktifkan akun. Nah, bisa gak tambahkan icon tombol aktifkan akun pada akun yg sudah non aktif?
  * **Cacat nyata, bukan sekadar permintaan fitur.** Cabang `@if/@elseif` pada halaman daftar tidak menghasilkan apa pun ketika `is_aktif` bernilai salah, sehingga akun yang sudah dinonaktifkan terkunci selamanya: tombolnya tidak ada, rutenya pun tidak ada. Padahal akun memang tidak pernah dihapus.
  * Enum `AksiAuditLog::AktifkanAkun` beserta pemetaan warna badgenya ternyata sudah disiapkan sejak lama, tetapi tidak pernah ada antarmuka yang memicunya.
- [done] Bisa jelaskan lebih detail dan kasih contoh nyata dari fitur RBAC di bagian cakupan data? Soalnya itu pilihannya ada "semua", "per SP", "milik sendiri". Nah, ini aku gak paham maksudnya.
  * Jawaban: RBAC di sistem ini punya **dua dimensi terpisah**. Izin menjawab *boleh melakukan apa*, cakupan data menjawab *boleh melihat data siapa*. Keduanya berlapis, bukan menggantikan.
  * Contoh nyata: YOSEP KLAU berrole Operator SP bercakupan `Per SP` dan ditugaskan di SP Weain. Ia berizin tambah transmigran, tetapi membuka `/transmigran` hanya menampilkan KK di SP Weain. Data SP lain tidak disembunyikan tombolnya, melainkan tidak ikut terambil dari database. Bandingkan dengan BUDI SANTOSO bercakupan `Semua` yang melihat keenam SP sekaligus.
  * **Nilai `Milik Sendiri` dibuang** atas permintaan pemilik proyek. Nilai itu hanya ditandai "untuk kebutuhan mendatang" di dua dokumen tanpa satu pun aturan yang menjelaskan kapan dipakai, sehingga menambah beban baca tanpa manfaat.
- [done] Di modal floating ubah role kan ada kolom hapus ya. Nah, karena akun pengguna itu tidak bisa dihapus, berarti kolom checklist hapus pada manajemen pengguna harusnya gak ada kan?
  * Benar, dan **sudah diperbaiki**: izin `hapus` dicabut dari modul `pengguna`, sebab akun tidak pernah dihapus melainkan dinonaktifkan. Rutenya bahkan membalas `abort(405)`.
  * **Namun modul `role` tetap memiliki izin hapus.** Ini koreksi terhadap dugaan awal: `rules.md` 5.0c poin 9 membolehkan role dihapus selama bukan bawaan dan tidak dipakai akun mana pun. Yang keliru bukan izinnya, melainkan tombolnya yang belum pernah dibuat.
  * Akibatnya total izin turun dari 118 menjadi **117**, dan Admin dari 118 menjadi **117**. Angka ini diperbarui serentak pada `DummyData::role()` dan `data-dictionary.md` 13.1.
- [done] Untuk role itu bisa dihapus atau gak ya? Kalau bisa, tolong buatkan data dummy role yg buatan sendiri (bukan bawaan sistem) agar kita tahu aja gimana bentuk front-end-nya jika role tersebut bukan bawaan sistem. 
  * Jawaban: **bisa**, dengan dua syarat pada `rules.md` 5.0c, yaitu bukan role bawaan (poin 8) dan tidak dipakai akun mana pun (poin 9).
  * Temuan: keempat role bawaan bernilai "Dapat dihapus: Tidak", sehingga **bentuk tampilan role buatan sendiri tidak pernah terlihat**. Ditambahkan role dummy **Pendamping Lapangan** (`is_bawaan` salah, tanpa pengguna, 16 izin baca) yang memunculkan keadaan tersebut.
  * Tombol Hapus Role ditambahkan, dirender **hanya** bila kedua syarat terpenuhi. Merender tombol lalu menolaknya di server berarti memasang kontrol mati (R-26).
- [done] Di halaman pengaduan warga, ketika warga telah mengirimkan laporan, kan muncul nomor pengaduannya di atas halaman. Nah, apakah bisa nomor pengaduannya itu dibuatkan fitur copy sehingga ketika user menekan nomor pengaduan tersebut otomatis nomor pengaduannya langsung ter-copy. Oh iya, itu ketika pegaduannya terkirim, maka detail pengaduan beserta nomor pengaduannya bakal dikirim ke email warga yg diisi pada form email pengaduan kan?
  * Nomor kini **dapat disalin sekali ketuk**. Tombol salin adalah pelengkap, bukan pengganti: menyalin hanya menaruh nomor di papan klip yang mudah tertimpa salinan berikutnya, sehingga nomornya tetap ditampilkan besar dan ajakan mencatat tetap ada.
  * Jawaban soal surel: **belum terkirim**. Isian surel dan janji "nomor dikirim juga ke surel Anda" sudah ada sejak lama, tetapi pengirimannya menunggu backend. Ditambahkan spanduk yang menyatakannya terus terang, sebab tanpa itu warga dapat menunggu surel yang tidak akan pernah datang lalu kehilangan nomornya.
- [done] Untuk status prioritas pada pengaduan, kalau gak salah, aku pernah request agar pengaduan dari warga itu ada pembobotan sehingga bisa memunculkan status prioritas secara otomatis. Bener gak? Kalau bener, aku minta untuk dibatalkan request-ku itu dan menyerahkan secara full untuk status prioritas pengaduan ke admin/petugas yg menanganinya.
  * Benar, permintaan itu memang pernah ada dan kini **dibatalkan**. ``PrioritasPengaduan::dariKategori()`` dihapus beserta ujinya, dan ``rules.md`` 10b poin 6a ditulis ulang.
  * Temuan yang memudahkan pembatalan: metode itu ternyata **tidak pernah dipanggil kode produksi mana pun**. Ia hanya hidup di dalam enum dan dikunci dua uji, sedangkan form petugas justru memakai nilai bawaan ``Sedang``. Jadi tidak ada perilaku berjalan yang berubah.
  * Alasan pembatalan dicatat pada aturan: kategori hanya menyatakan pokok masalah, sedangkan kegentingan bergantung pada keadaan lapangan yang tidak terbaca dari kategori. Dua laporan berkategori sama dapat berbeda jauh kemendesakannya.
- [done] Pada halaman detail pengaduan (cms admin) di slidebar Riwayat Penanganan, berikan contoh ketika ada dokumen yg diunggah terkait dokumen tindak lanjut. Harsnya dokumen tersebut masuk ke riwayat penanganan dan bisa dilihat/dibuka, kan?
  * Benar, dan ini **celah nyata**: modal penanganan sudah lama menyediakan isian unggah dokumen, tetapi hasilnya tidak pernah ditampilkan kembali di mana pun. Berkas yang sudah diunggah petugas karena itu tidak dapat dibuka siapa pun, termasuk oleh yang mengunggahnya.
  * Ditambahkan kunci ``dokumen_tindak_lanjut`` pada data penanganan beserta tautan pembukanya lewat ``x-sim.tautan-dokumen`` yang sudah ada. Dua contoh diisi pada PGD-2026-0005 agar keadaan berdokumen dan tanpa dokumen sama-sama teruji.
- [done] Pada halaman lacak pengaduan, berikan data dummy untuk contoh front-end ketika warga ingin melacak nomor pengaduannya. Soalnya sekarang kan gak ada contohnya, cuma kosongan. Kasih contoh ketika pengaduannya masih proses dan ketika sudah selesai. Apakah bisa? Dan apakah ketika admin/petugas upload dokumen terkait penanganan, apakah dokumen tersebut bisa dilihat warga melalui halaman lacak pengaduan? 
  * **Ternyata datanya sudah ada** dan tidak perlu ditambah: PGD-2026-0001 berstatus Diproses dengan dua jejak, PGD-2026-0005 berstatus Selesai dengan tiga jejak.
  * Yang membuatnya tampak kosong adalah **cacat lain**: tombol "Lihat Perkembangan Laporan" setelah kirim mengarah ke ``PGD-2026-0006`` yang tidak pernah ada pada data contoh, sehingga alur kirim-lalu-lacak selalu berujung pada keadaan nomor tidak ditemukan. Nomornya diperbaiki ke salah satu yang benar-benar ada.
  * Jawaban soal dokumen: warga **diberi tahu keberadaannya, tanpa dapat membukanya**. Halaman lacak terbuka tanpa login dan hanya berbekal nomor pengaduan, sehingga siapa pun yang mengetahui nomornya akan ikut memperoleh berkasnya. Dokumen tindak lanjut kerap memuat nama petugas, hasil peninjauan, dan kadang data warga lain.
- [done] Button pada halaman detail pengaduan (cms admin) kan ada 2 tuh: Di atas deket judul pengaduan dan di bawah setelah detail laporan. Apakah butoon yg di atas deket judul pengaduan dihapus saja? Sepertinya cukup 1 button saja, kan?
  * Benar, cukup satu. Yang dihapus adalah tombol di **kepala halaman**; yang dipertahankan ada di kolom kiri, berdampingan dengan stepper alur penanganan dan keterangan bahwa status hanya dapat maju satu tahap. Di sanalah petugas melihat konteks tahapnya sebelum menekan.
- [done] Pada modal floating Perbarui Status Penanganan yg ada di button pada halaman Daftar Pengaduan, tambahkan form upload dokumen dan tanggal penanganan. Pokoknya sesuaikan dengan modal floating update status penanganan yg ada pada halaman detail pengaduan.
  * Disamakan: ditambahkan tanggal penanganan, unggahan dokumen tindak lanjut, dan ``required`` pada catatan yang sebelumnya hanya bertanda bintang tanpa penegakan.
  * **Dua cacat ikut ditemukan.** Pertama, nama isian statusnya berbeda antara kedua modal (``status_tujuan`` dan ``status_sesudah``), sehingga satu penangan di sisi server tidak akan melayani keduanya; kini diseragamkan. Kedua, formnya **tidak memiliki ``enctype="multipart/form-data"``**, sehingga berkas yang diunggah tidak akan pernah terkirim dan kegagalannya berlangsung diam-diam.
- [done] Pada sistem ini kan semua yg melakukan CRUD bakal masuk log kan? Nah, bisa gak riwayat perubahan semua data itu dicatat pada masing-masing slidebar data yg ada pada halaman detail data (view/lihat data)? Aku kasih contoh salah satu saja ya agar kamu lebih paham yg aku maksud. Misal pada menu transmigran, ketika diklik view salah satu datanya (contoh data A) kan bakal mengarah ke halaman detail transmigran A dan bakal ada slidebar biodata, rumah, lahan, hasil panen, dokumen. Nah, nanti tambahkan slidebar catatan log di paling kanan (dalam konteks ini berarti sebelah dokumen) untuk catat siapa yg create datanya, siapa yg ubah, dll gitu. Bagaimana menurutmu?
  * Jawaban: **benar**, seluruh tindakan tulis tercatat pada audit log lengkap dengan ``nama_tabel`` dan ``record_id``, sehingga penyaringan per baris data memang mungkin. Yang belum ada hanyalah tempat menampilkannya.
  * Ditambahkan tab **Catatan Log** pada lima halaman rincian bertab: Transmigran, Rumah, Lahan, Poktan, dan Pengaduan. Isinya dibungkus komponen ``x-sim.catatan-log`` agar markupnya tidak disalin lima kali.
  * **Tab ini tidak menggantikan halaman Audit Log.** Keduanya menjawab pertanyaan berbeda: audit log menjawab "apa saja yang terjadi hari ini di seluruh sistem", sedangkan tab ini menjawab "apa yang pernah terjadi pada data ini saja". Karena itu tab menautkan ke halaman audit log, bukan menghapusnya.
  * Entri **terbaru diletakkan paling atas**, sebab yang pertama dicari pembaca biasanya perubahan terakhir, bukan asal-usul datanya.
  * Data contoh audit log diperluas dari 8 menjadi 15 baris agar tiap entitas yang dapat dibuka rinciannya punya minimal satu entri ``Tambah``. Transmigran 1 sengaja diberi rangkaian terpanjang, sedangkan transmigran 2 **sengaja dibiarkan tanpa jejak** agar keadaan kosong ikut terlihat.
  * **Cacat lama ikut diperbaiki.** ``pengguna/detail.blade.php`` menyaring riwayat hanya dengan ``nama_tabel``, tanpa mencocokkan ``record_id``, sehingga setiap akun menampilkan riwayat akun orang lain. Komentar lamanya bahkan mengaku mencocokkan nomor baris padahal kodenya tidak melakukannya. Penyaringan dipindah ke sisi klien, sebab satu modal melayani seluruh baris secara bergantian.
  - [done] form angka harusnya cuma bisa input angka saja. jadi buat validasi di sisi client.
    * Dibuat ``resources/js/input-angka.js`` yang dipasang **sekali** lewat satu pendengar di tingkat dokumen, bukan disalin ke tiap isian. Isian angka pada sistem ini tersebar di puluhan form, dan menempelkan penjaga per isian berarti setiap form baru harus mengingatnya.
    * Yang disaring bukan sekadar huruf. ``type="number"`` bawaan peramban **sudah** menolak huruf, sehingga menulis ulang penolakan itu tidak menambah apa pun. Yang lolos justru ``e`` (notasi ilmiah), ``+``, ``-``, dan **tempelan teks** � ketiganya membuat isian tampak wajar tetapi terkirim sebagai nilai tak terduga.
    * Isian berlangkah pecahan (luas lahan, tonase panen) tetap menerima koma desimal; tahun dan jumlah unit tidak. Pembedanya diambil dari atribut ``step`` yang sudah ada, bukan dari daftar nama isian yang harus dirawat.
    * **Ini lapisan kenyamanan, bukan pengaman.** Validasi sisi server tetap wajib dan tidak dikurangi sedikit pun; penjaga ini hanya mencegah petugas mengisi satu form penuh sebelum tahu isiannya ditolak.
  - [done] admin kan tidak bisa dihapus. di halaman pengguna, khusus untuk role admin ada tampilan (admin terakhir). Nah, bagaimana kalau itu dihapus dan dihilangkan saja tombol nonaktifnya gitu.
    * Label ``(admin terakhir)`` pada baris tabel dihapus, tombol nonaktifkan ikut ditiadakan untuk baris tersebut.
    * **Keterangan di bawah tabel sengaja dipertahankan.** Tanpa label, hilangnya tombol pada satu baris menjadi tidak dapat dijelaskan; petugas akan mengira tampilannya rusak. Perbedaannya: label mengulang informasi di setiap baris, sedangkan keterangan menjelaskan aturannya satu kali.
    * Aturan yang ditegakkan tetap sama � sistem menolak penonaktifan Admin aktif terakhir. Yang berubah hanya cara menyampaikannya.
  - [done] kata-kata "modul" di sistem ini bagaimana kalau diubah jadi "fitur"? Atau mungkin ada padanan kata lain yg formal namun lebih umum?
  - [done] Diksi "izin" pada fitur hak akses, bagaimana kalau diubah jadi "hak akses"? Soalnya kok agak kurang cocok memakai diksi izin gitu.
    * Kedua permintaan dikerjakan bersama sebab menyentuh teks yang sama. "modul" ? **fitur**; "izin" ? **kewenangan**.
    * **"hak akses" sengaja tidak dipakai** meski itu yang diminta. Menu induknya sendiri bernama *Role dan Hak Akses*, sehingga memakai istilah itu untuk salah satu isinya membuat pembaca mengira keduanya hal yang sama. Sistem ini memisahkan **kewenangan** (boleh melakukan apa) dari **cakupan data** (boleh melihat data siapa) � keduanya adalah hak akses, jadi istilah itu terlalu luas untuk menamai salah satunya.
    * **Nama di dalam kode tidak ikut diubah.** Tabel ``permission``, kolom ``permission.modul``, dan parameter rute ``{modul}`` mengikuti konvensi Laravel serta menyentuh skema dan URL; menggantinya adalah migrasi basis data demi kata yang tak pernah dibaca pengguna. Batas ini dicatat pada ``ui-spec.md`` butir 8.
    * **"Izin lokasi" pada peta juga tetap**, sebab itu istilah dialog peramban (Geolocation API). Menggantinya membuat pesan sistem tidak cocok dengan yang benar-benar dilihat pengguna.
    * Uji penjaga memeriksa **teks terender**, bukan berkas sumber � atribut seperti ``type="module"`` memang harus tetap teknis. Diuji lewat mutasi: mengembalikan satu judul ke "Izin per Modul" membuat uji merah.
  - [done] Nama "Budi Santoso" yg terdapat di sistem ini ganti dengan "Nara Wijaya".
    * Diganti di seluruh rujukan, termasuk bentuk kapital ``NARA WIJAYA`` pada avatar dan inisial ``NW`` yang sebelumnya ``BS``. Inisial adalah yang paling mudah terlewat sebab tidak memuat nama aslinya.
  - [done] toggle role aktif di floating modal pada halaman role bagaimana kalau dihapus saja?
    * Dihapus. Alasan yang menguatkan: status aktif sudah dapat diubah dari daftar role, sehingga modal menyediakan jalan kedua untuk hal yang sama � dan dua tempat mengubah satu nilai adalah sumber kebingungan, bukan kemudahan.
  - [done] Buat highlight color di halaman role pada bagian isi dari Jumlah Izin, Jumlah Akun, Dapat Dihapus. Sekalian rename "Dipakai akun" -> "Jumlah Akun"
    * "Dipakai akun" ? **Jumlah Akun**; "Jumlah Izin" ikut menjadi **Jumlah Kewenangan** menyesuaikan istilah baru.
    * Warna dipilih menurut **makna, bukan sekadar pembeda**: Dapat Dihapus memakai hijau/abu sebab menyatakan boleh-tidaknya suatu tindakan, sedangkan dua kolom jumlah memakai warna netral � angka yang besar tidak berarti baik atau buruk, dan mewarnainya seolah begitu akan menyesatkan.
  - [done] "email dinas"/"surel dinas" ganti jadi "email" saja.
    * Diganti pada label, placeholder, dan teks bantuan. Sejalan dengan ``ui-spec.md`` yang sudah lebih dulu memutuskan **email** (bukan "surel") sebagai istilah baku sistem ini.
  - [batal] banner "Data contoh. Seluruh angka dan nama pada halaman ini adalah contoh untuk keperluan pembangunan tampilan, bukan data lapangan yang sebenarnya." dihilangkan saja.
    * **Dibatalkan atas keputusan pemilik proyek** setelah konflik dilaporkan; banner tetap apa adanya.
    * Banner ini bukan hiasan melainkan **kewajiban aturan proyek**: ``rules.md`` R-17/R-38 mensyaratkan setiap halaman menampilkan penanda "Data contoh" selama tahap dummy, agar angka di layar tidak disalahartikan sebagai data lapangan.
    * Cakupan penghapusan karena itu jauh lebih luas dari satu blok Blade: mengubah ``rules.md`` dan ``ui-spec.md``, membongkar tiga uji penjaga (``HalamanTest.php`` b53 dan b1009, ``DummyDataTest.php`` b235), serta mencatat ulang ``delivery-gate-gelombang-1.md`` yang memakai penanda ini sebagai bukti lolos audit R-38.
    * Bila kelak ingin dikerjakan, jalan yang paling murah adalah **meringkasnya menjadi lencana kecil di header** � kewajiban tetap terpenuhi tanpa memakan ruang tiap halaman. Penghapusan total sebaiknya menunggu ``MEMAKAI_DATA_CONTOH`` menjadi ``false``, sebab saat itu banner hilang dengan sendirinya tanpa menyentuh aturan apa pun.

- [done] Bagaimana kalau fitur laporan ekspor itu ditaruh di tiap datatabel yg berkaitan?
  * **Idenya justru menutup pelanggaran aturan yang sudah ada.** `rules.md` 12 poin 5 mewajibkan laporan dapat difilter sebelum diekspor, dan halaman `/laporan` tidak pernah memenuhinya: ia hanya memuat sembilan kartu unduhan tanpa satu pun kontrol filter, sehingga petugas selalu menerima seluruh isi tabel. Sementara 17 halaman daftar sudah memiliki pencarian dan filter yang bekerja lewat query string. Jadi ini bukan memindahkan fitur, melainkan menaruhnya di satu-satunya tempat yang filternya memang ada.
  * Dibuat komponen `x-sim.tombol-ekspor`. Dipasang sekali pada `x-sim.halaman-daftar` sehingga **12 halaman memperolehnya sekaligus**, ditambah 5 halaman yang memakai `x-sim.data-table` langsung dan 3 halaman rekap bertabel manual.
  * **Filter aktif ikut terbawa** lewat `request()->query()`. Inilah inti nilainya: tanpa itu, petugas yang sudah menyaring satu SP tetap menerima berkas berisi seluruh kawasan, dan selisihnya baru disadari setelah berkas dibuka di lapangan. Dijaga dua uji, satu memastikan filter terbawa dan satu memastikan alamat tetap bersih ketika tabel belum disaring.
  * Halaman `/laporan` **dihapus sepenuhnya** atas keputusan pemilik proyek, beserta rute, berkas Blade, dan item menunya. Nama kelompok menu "Laporan & Pengaturan" ikut menjadi "Pengaturan Sistem".
  * **Tab template luring tidak dipindah ke mana pun**, sebab ternyata duplikat: modal impor sudah memuat langkah "Unduh template" di 14 halaman. Menghapusnya justru menghilangkan dua tempat untuk satu hal, bukan menghilangkan fitur.
  * **Indikator Kawasan dipindah ke dashboard.** Ia satu-satunya isi `/laporan` yang benar-benar tidak punya tabel padanan, sebab sumbernya lintas modul. Diletakkan di kepala dashboard, sejalan dengan prinsip yang sama: ekspor menempel pada data yang ditampilkan.
  * Konsekuensi yang diterima sadar: **tidak ada lagi satu tempat** untuk melihat seluruh ekspor yang tersedia. Petugas yang ingin mengunduh data panen membuka halaman panen.
- [done] Bagaimana kalau sekalian hapus RBAC yg kolom export?
  * **Analisis pertama saya keliru dan dikoreksi pemilik proyek.** Saya sempat menyimpulkan huruf E adalah pembeda Operator SP dari role dinas, sebab Operator SP memegang `lihat` pada 23 fitur tanpa satu pun `export`. Penelusuran lebih teliti membantahnya: Dinas Transmigrasi juga memegang `lihat` tanpa `export` pada 9 fitur, dan total ada **24 sel** berpola sama. Jadi E tidak pernah membedakan peran secara konsisten, melainkan tersebar tanpa alasan yang dapat dijelaskan.
  * Pembeda peran yang sesungguhnya adalah **Penanganan pengaduan** (`rules.md` 5.1), satu-satunya baris tempat Operator SP bertanda `-` sementara ketiga role lain memegang L T U. Menangani pengaduan berarti memutuskan tindak lanjut atas nama dinas, dan itu kewenangan jabatan, bukan soal kemampuan teknis.
  * Keputusan: kewenangan `export` **dicabut seluruhnya**. Ekspor adalah cara lain membaca data yang sudah boleh dilihat, bukan tindakan baru, sehingga ia mengikuti `lihat`. Memisahkannya memaksa Admin menyusun satu maksud dua kali.
  * Dampak angka: **117 kewenangan menjadi 96**, dari 27 fitur menjadi 26 (fitur `laporan` ikut hilang). Operator SP yang tadinya tidak dapat mengekspor apa pun kini dapat mengekspor 23 fitur yang ia lihat, tetapi **tetap terbatas SP yang ditugaskan padanya** sebab penyaringan terjadi pada cakupan data di tingkat query, bukan pada tombol.
  * Enam sumber kebenaran diselaraskan serentak: enum `AksiPermission`, `DummyData`, kamus data 11.26 dan 13.1, matriks `rules.md` 5.1, dan dua uji. Ketidakcocokan sekecil apa pun langsung memerahkan uji pembanding, dan itu memang gunanya.
  * **Satu uji rapuh ikut ditemukan dan diperbaiki.** `HalamanTest` membaca tabel kamus data 13.1 hanya berbekal pola "nama berbingkai backtick diikuti sejumlah sel", dan itu bekerja secara kebetulan selama tabelnya berkolom lima. Begitu `export` dicabut dan kolomnya menjadi empat, pola yang sama mulai mencocoki **343 baris tabel kolom database** di seluruh kamus data. Pembacaannya kini ditambatkan pada judul bagian, bukan pada jumlah kolom.
  * Risiko yang diterima sadar: siapa pun yang boleh melihat sebuah tabel kini boleh mengunduh seluruh isinya, termasuk NIK dan pendapatan keluarga. Bila kelak perlu dibatasi, jalannya lewat **pencatatan ekspor pada audit log**, bukan menghidupkan kembali dimensi izin ini.
- buat cache/cookies/pwa untuk atasi sinyal ketika kirim data tapi sinyal jelek/putus. dikerjakan setelah backend selesai.

## 6. Revisi
- [done] Batas utara, timur, selatan, barat pada form tambah dan ubah di halaman SP dihapus saja.
  * **Dihapus sepenuhnya**, bukan hanya dari form: kolom kamus data, tampilan dashboard SP, 24 nilai data contoh, dan pengecualian pada `UppercaseInput` ikut dicabut.
  * **Alasan yang membuat penghapusan aman:** keempat kolom dipakai **0 perhitungan, 0 indikator dashboard, 0 parameter penilaian kondisi SP, 0 fitur peta, dan 0 uji**. Menghapusnya tidak memerahkan satu uji pun, dan itu sendiri pertanda tidak ada yang bergantung padanya.
  * Isinya memang bukan geometri melainkan sebutan naratif seperti `Hutan lindung` atau `Sungai Benanain`, sehingga mustahil dipakai menggambar batas. `peta.js` hanya memplot titik dan tidak memiliki `polygon` maupun `geojson`.
  * **Bentrok dokumen diselesaikan, bukan diabaikan.** `rules.md` 4a.4 sempat menulis batas wilayah sebagai hal yang "wajib disimpan", diikuti `prd.md` dan `workflow.md`. Ketiganya disunting agar tidak menjanjikan isian yang sudah tidak ada.
  * **Nilainya dokumenter, dan itu diakui.** Keempat kolom menyalin isi berkas penetapan SP; kegunaannya sebagai arsip, bukan sebagai data yang diolah. Bila dinas kelak menyatakan memerlukannya, jalan mengembalikannya jelas: tambahkan kembali 4 kolom pada kamus data 3.6, satu bagian pada `sp/form`, dan satu blok tampilan pada `dashboard/sp`. Riwayat ini sengaja ditulis rinci agar keputusan dapat dibalik tanpa menebak-nebak.
  * Keputusan lama 2026-08-11 yang melebur `koordinat_lokasi_sp` menjadi empat kolom ini **tetap benar pada bagian peleburannya**; yang keliru adalah menyimpan isinya sama sekali. Catatan pada bagian 1a.3 ditandai dicabut, bukan dihapus.
- [done] Bagaimana kalau topik pengaduan warga bisa ditautkan ke inventaris, fasilitas, dan infrastruktur SP? Begitupun juga ditautkan ke poktan, alsintan, saprotan, hasil panen, rumah transmigran? Mungkin ini yg menautkannya admin? Atau masyarakat?
  * **Yang menautkan petugas, bukan warga.** Warga melapor lewat ponsel berjaringan terbatas dan tidak mengetahui id aset; meminta ia memilih dari 1.140 KK akan menutup kanal yang justru paling perlu terbuka. Petugas melengkapinya saat meninjau, pola yang sama dengan koordinat pada `rules.md` 10b.6c. Form publik **tidak disentuh sama sekali**.
  * Dibuat tabel **`pengaduan_objek`**, bukan dua kolom pada `pengaduan`. Alasannya disusun dari lapangan: kategori `Bencana` memang untuk kejadian yang merusak banyak hal sekaligus, dan infrastruktur di kawasan transmigrasi dipakai bersama sehingga satu irigasi rusak menghambat puluhan bidang. Bila hanya satu objek dapat ditaut, objek kedua akan ditulis ke `deskripsi` sebagai teks bebas dan **tidak terhitung pada rekap** � kegagalan yang berlangsung diam-diam.
  * **Kewajiban tanpa memaksa berbohong.** Objek wajib dinyatakan sebelum status maju ke Diproses, dipenuhi salah satu dari tiga cara: ditautkan, ditandai `belum_terdata`, atau ditandai `tidak_ada`. Memaksa memilih dari daftar akan membuat petugas menaut ke aset yang sekadar mirip demi dapat melanjutkan, dan rekap lalu menuduh aset yang tidak bersalah. Penandaan `belum_terdata` sekaligus menjadi daftar kerja pendataan.
  * **Batas ketelitian diakui terbuka.** Inventaris didata per jenis, sehingga satu baris "MEJA KANTOR" mewakili 12 unit dan sistem tidak sanggup menunjuk meja yang mana. Cacat ini sudah ada sebelum tautan objek: data contoh sendiri menulis kursi plastik berkondisi "Rusak Ringan" dengan keterangan *"sebagian retak"*, dan kata "sebagian" itulah pengakuan bahwa modelnya tidak sanggup menyimpan kondisi per unit. Unit spesifik ditulis pada `keterangan`. Pemecahan per unit **tidak dikerjakan**, sebab menuntut penomoran dan pelabelan fisik di lapangan.
  * **Tiga temuan privasi, seluruhnya ditutup.** Halaman lacak publik tidak menampilkan objek sama sekali (`10b.6i`), sebab siapa pun yang tahu nomor pengaduan akan ikut mengetahui alamat keluarga yang bersangkutan. Rumah, lahan, hasil panen, dan alsintan hanya tampil sebagai **angka gabungan** pada rekap (`10b.8b`), sebab menyebut unitnya mengumumkan keluarga mana yang paling sering mengeluh. Daftar objek pada dropdown wajib disaring cakupan data (`10b.6j`), sebab dropdown adalah jalur baru yang dapat melewati pembatasan Per SP.
  * **Rekap dipecah dua tabel**, bukan satu. "Aset belum terdata" bukan nama aset; menaruhnya pada tabel peringkat membuatnya bersaing dengan aset sungguhan. Tabel kedua **wajib ditampilkan**: bila sebagian besar laporan masuk ke sana, peringkatnya tidak mewakili keadaan dan pembaca berhak tahu sebelum memakainya menyusun anggaran.
  * Kolom **jumlah unit** ditampilkan berdampingan, tanpa rasio otomatis. Kursi plastik 60 unit wajar lebih sering diadukan daripada genset 1 unit, tetapi angka turunan yang tampak berwibawa cenderung diterima tanpa ditinjau � alasan yang sama dengan penolakan prioritas otomatis (10b.6a) dan unggulan otomatis (8.3).
  * **Satu cacat lama ikut diperbaiki.** Tab "Pengaduan" pada rincian infrastruktur selama ini menampilkan **seluruh pengaduan se-SP**, dan komentarnya sendiri mengakui *"bukan daftar keluhan atas aset ini, sebab pengaduan tidak menaut ke id infrastruktur"*. Akibatnya keluhan atas jalan produksi ikut muncul di halaman sumur bor.
  * **Dua halaman rincian baru** dibuat untuk Inventaris SP dan Fasilitas SP, sebab keduanya hanya punya halaman daftar sehingga keluhan atas sebuah barang tidak punya tempat ditampilkan. Uji penjaga berbasis daftar rute langsung menuntut keduanya bertab dan ber-Catatan Log tanpa ujinya disunting, dan itu memang gunanya.
  * Kategori `Peralatan dan Perlengkapan` **dipecah** menjadi `Inventaris SP` dan `Fasilitas SP`. Ternyata murah: nilai lama tidak dipakai satu pun data contoh dan seluruh view membacanya lewat `::opsi()`.
  * Halaman terbit **122 menjadi 152**. Dijaga 20 uji baru, termasuk penjaga privasi lacak publik dan penjaga kejamakan yang memerah bila contoh pengaduan berobjek jamak hilang.
  * **KOREKSI PADA HARI YANG SAMA.** Pemilik proyek menemukan bahwa isiannya ternyata **hanya dapat menaut satu objek**, sehingga janji kejamakan yang menjadi seluruh alasan tabel ini tidak pernah dapat dijalankan petugas. Rinciannya beserta sebab lolosnya dari 449 uji ada pada bagian 1d.
  * **DICABUT SELURUHNYA PADA HARI YANG SAMA.** Setelah ditetapkan bahwa satu laporan ditangani satu dinas, pemilik proyek menilai pengelolaan objek menambah beban petugas tanpa menjawab kebutuhan. Seluruh fitur dihapus dan digantikan penentuan bidang berbasis kategori. Lihat bagian 1e.
- [done] Tambah dokumen pendukung pada form tambah dan ubah pada halaman Satuan Permukiman, inventaris, fasilitas, infrastruktur, kelompok tani, alsintan, saprotan
  * **Bukan pekerjaan baru, melainkan komponen yang belum dipasang.** Kedelapan kolom dokumen sudah lama tercatat pada `data-dictionary.md` (3.6, 4.1, 4.2, 8.1, 8.3, 8.4, dan dua kolom pada 10.1), dan `x-sim.file-upload` sudah matang serta terpakai di lima form lain. Yang tidak ada hanya isiannya.
  * Akibat sebelumnya nyata: **SK pembentukan poktan dan berita acara penyaluran saprotan tidak dapat diunggah ke mana pun**, padahal justru keduanya yang paling sering diminta saat pemeriksaan.
  * **Infrastruktur diberi dua isian terpisah**, `foto` dan `dokumen_pendukung`, mengikuti kamus data 10.1. Keduanya menjawab hal berbeda: foto merekam kondisi lapangan saat pendataan, dokumen menyimpan berkas administratifnya. Menggabungkannya membuat foto kondisi tertimpa dokumen pengadaan.
  * `enctype="multipart/form-data"` sudah ada pada `x-sim.modal-form`, sehingga berkas benar-benar terkirim. Dijaga uji tersendiri, sebab kegagalannya berlangsung diam-diam: form tetap tersimpan, hanya berkasnya yang hilang.
- [done] untuk dokumen lahan, bagaimana kalau ditambahkan di form tambah lahan saja? begitupun juga di form ubah data lahan. soalnya agak bingung juga kalau dipisah.
  * **Dikerjakan bersama koreksi konsep hak atas tanah**, sebab keduanya menyentuh form yang sama.
  * ~~**Dasar keputusan lama ternyata gugur.** `notes.md` sebelumnya memutuskan dokumen tetap terpisah karena "satu lahan dapat memiliki HPL dan SHM sekaligus". Pemeriksaan data membantahnya: dari **6 bidang, tidak satu pun memiliki lebih dari satu dokumen**. Skenario yang menjadi seluruh alasan pemisahan tidak pernah ada.~~ **ALASAN INI DICABUT 2026-08-19.** Menghitung baris data contoh tidak dapat membuktikan apa pun tentang kardinalitas di lapangan (`rules.md` 19a), dan kenyataannya justru sebaliknya: sertifikasi transmigrasi memang berlapis, Surat Keterangan Pembagian Tanah lebih dulu lalu sertifikat menyusul bertahun kemudian, sehingga satu bidang **pasti** melewati lebih dari satu dokumen. Yang tidak punya sumbu waktu adalah data contohnya, bukan tanahnya. Lihat bagian 1c.2 pelanggaran kedua.
  * **Keputusannya tetap berlaku** meski alasannya gugur, sebab tabel `dokumen_lahan` memang dipertahankan. Yang berubah hanya dasarnya: pemisahan dibenarkan oleh sifat sertifikasi berlapis, bukan oleh jumlah baris data contoh.
  * Ketidakkonsistenan yang Anda rasakan juga terukur: **12 tabel** memakai `dokumen_pendukung` satu kolom, **1 tabel** punya tabel dokumen sendiri.
  * Keputusan: **dokumen pertama diisi pada form lahan**, tab pada halaman rincian tetap ada untuk dokumen kedua dan seterusnya. Kasus lazim selesai satu langkah; kasus jarang tetap terlayani.
  * Ketiga keterangan dokumen (`jenis_dokumen`, `nomor_dokumen`, `tanggal_terbit`) **dipertahankan** dan tidak disederhanakan menjadi satu kolom unggahan seperti modul lain. Nomor sertifikat adalah data legal yang harus dapat dicari, bukan sekadar lampiran.
  * `rules.md` 7.6 tetap terpenuhi apa adanya, sebab aturannya hanya mewajibkan dokumen "dapat diunggah dan ditautkan" tanpa menentukan di form mana.

- [done] **Koreksi konsep hak atas tanah dan peruntukan lahan** (dari masukan pemilik proyek, 2026-08-18)
  * **Dua kekeliruan nyata ditemukan, keduanya terbukti dari data proyek sendiri.**
  * **Pertama: HPL dipakai sebagai status kepemilikan perorangan.** Data menunjukkan `LP-001 YOHANES BERE ... HPL`, yang berarti sistem menyatakan seorang transmigran "memiliki lahan berstatus HPL". Itu tidak mungkin: HPL adalah **Hak Pengelolaan** milik instansi atas tanah kawasan, bukan hak individu. Yang diterima transmigran adalah **Hak Milik**, dan sebelum sertifikat terbit statusnya belum bersertifikat.
  * Sumber kekeliruannya ikut ditemukan: **`HPL` dan `SHM` muncul di DUA enum sekaligus** yaitu `StatusKepemilikanLahan` (11.13) dan `JenisDokumenLahan` (11.14). Nilai yang sama dipakai untuk dua maksud berbeda.
  * Perbaikan: enum menjadi **`StatusHakLahan`** berisi Belum Bersertifikat, Hak Milik, Hak Milik Bersama, Hak Pakai, Sewa, Garapan. HPL dan SHM **tetap ada sebagai jenis dokumen** karena keduanya memang nama berkas; ditambah `Surat Keterangan Pembagian Tanah` sebagai sandaran legalitas sebelum sertifikat terbit.
  * **Kedua: lahan usaha tahap I dan II tidak terwakili.** `YOHANES BERE` tercatat memiliki 3 bidang, tetapi dua di antaranya bernama sama persis "Lahan Usaha" tanpa pembeda apa pun. Petugas tidak dapat mengetahui bidang mana tahap pertama dan mana tahap kedua.
  * Perbaikan: `JenisLahan` menjadi **`PeruntukanLahan`** berisi Lahan Pekarangan, Lahan Usaha I, Lahan Usaha II. Dipilih bentuk satu dimensi alih-alih dua isian terpisah (jenis + tahap), sebab petugas cukup memilih sekali.
  * **Satu cacat laten ikut tertutup.** Penjumlahan luas lahan usaha pada halaman daftar mencocokkan teks `'Lahan Usaha'` persis, sehingga bidang tahap kedua akan **hilang dari rekap tanpa ada yang menyadarinya**. Pemeriksaannya dipindah ke `PeruntukanLahan::lahanUsaha()` agar penambahan tahap berikutnya tidak melewatkan halaman mana pun.
  * Data contoh disesuaikan: `YOHANES BERE` kini contoh keluarga berlahan lengkap (LP + LU I + LU II), dan status haknya memakai nilai yang benar.
  * **BUTIR KONFIRMASI DINAS.** Istilah pada `StatusHakLahan` disusun dari masukan yang merujuk PP 19/2024 dan dokumen Kemendesa, tetapi **kedua sumber itu tidak dapat saya verifikasi langsung**. Yang dapat dipastikan: usulannya konsisten secara internal dan memperbaiki kekeliruan yang terbukti dari data. Sebelum skema dikunci pada tahap backend, **istilahnya perlu dicocokkan dengan berkas penetapan yang dipakai dinas setempat**, sebab tiap daerah dapat memakai sebutan berbeda.
  * **KOREKSI PADA HARI YANG SAMA.** Nilai `Lahan Usaha I` dan `Lahan Usaha II` sempat dipasang, lalu **dibatalkan** setelah pemilik proyek menyampaikan keadaan sebenarnya di Kobalima Timur: satu transmigran menerima **satu lahan pekarangan dan satu lahan usaha**, tidak lebih. Peruntukan kembali dua nilai.
  * Keterangan lapangan itu sekaligus **menjawab dua hal yang menggantung**: keputusan 2026-08-10 yang menyatakan "boleh lebih dari satu lahan usaha, kondisi lapangan" ternyata keliru dan sudah dicoret pada tabel keputusan; dan butir `tasklist.md` "konfirmasi apakah lahan pekarangan bisa lebih dari satu per KK" kini terjawab: tidak.
  * **Yang tetap dipertahankan meski tahap dibatalkan:** nama `PeruntukanLahan` (lebih tepat daripada `JenisLahan`, sebab yang dibedakan adalah untuk apa bidang diberikan, bukan sifat fisiknya), serta metode `lahanUsaha()` yang memusatkan pemeriksaan pada enum. Metode itulah yang menutup cacat penjumlahan luas, dan alasannya tidak ikut gugur.
  * **Relasi tetap one-to-many.** Satu KK memegang dua bidang berbeda peruntukan, sehingga one-to-one tidak mungkin. Jumlah pada aturan dinyatakan sebagai **jumlah yang wajar, bukan batas yang ditolak sistem**: bila satu jatah lahan usaha terletak pada dua petak berkoordinat berbeda, keduanya tetap perlu dicatat tersendiri.
  * Data contoh disesuaikan tanpa menghapus bidang: `LU-002` **dialihkan ke YULITA HOAR** yang berada di SP sama dan belum punya lahan, bukan dibuang. Menghapusnya akan memutus rantai riwayat tanam dan menghilangkan satu-satunya contoh Lahan Basah.
  * **Pelajaran yang dicatat:** perubahan ini dikerjakan lalu sebagian dibatalkan dalam satu hari, sebab saya menyimpulkan dari sumber sekunder tanpa keterangan lapangan. Untuk hal yang bergantung praktik dinas setempat, **bertanya lebih dulu lebih murah daripada menyimpulkan**.

  * **Tata letak blok dokumen ikut dirapikan.** Empat isian sempat dipaksa berpasangan dalam grid dua kolom, padahal area unggah jauh lebih tinggi daripada isian teks sehingga kolom sebelahnya menyisakan ruang kosong besar. Kini tiga keterangan berjajar tiga kolom dan area unggah berdiri di baris penuh, mengikuti pola yang sudah dipakai 13 form lain.
- [done] untuk status unggulan komoditas apakah bisa ditentukan lewat banyaknya komoditas yg ditanam pada suatu kawasan? Atau mungkin lewat hasil panen terbanyak? jadi bukan manual seperti sekarang.
  * **Jawabannya: tetap manual, tetapi petugas diberi bahan pertimbangan.** Pertanyaannya beralasan, dan penelusuran dokumen justru menunjukkan mengapa perhitungan otomatis tidak tepat di sini.
  * **Tiga bukti bahwa unggulan adalah keputusan program, bukan hasil hitungan.** `prd.md` 2 menyebut komoditas unggulan berasal dari **proposal**; `rules.md` 8.1 menulis jagung sebagai yang "disebut dalam proposal"; dan `rules.md` 8.3 memakai kata **penandaan**, bukan penentuan, sedangkan `workflow.md` menulis "tandai bila diperlukan". Jagung sudah ditandai unggulan **sebelum satu baris panen pun tercatat**.
  * Deskripsi jagung pada data contoh bahkan menyebut alasan non-volume: *"ditanam hampir seluruh keluarga"*. Itu ukuran **sebaran penanam**, yang tidak dapat diturunkan dari volume panen.
  * **Kerugian bila diotomatiskan:** komoditas prioritas program yang volumenya kecil justru tidak akan pernah tertandai, padahal volumenya kecil **karena** baru dirintis dan itulah alasan ia perlu didorong. Jumlah penanda aksen gold juga menjadi tidak terkendali, bertentangan dengan `ui-spec.md` 2.4.
  * Pola ini **sudah pernah diputuskan di proyek ini**: `rules.md` 10b.6a melarang warga menilai prioritas pengaduannya sendiri, dengan alasan hampir semua akan ditandai mendesak sehingga penandanya kehilangan makna. Logikanya sama.
  * Yang ditambahkan: form menampilkan **volume tercatat** di samping centang, beserta **peringatan** bila yang ditandai bukan bervolume terbesar. Peringatan itu tidak menghalangi penyimpanan.

  * **Rencana menyatukan sumber angka DIBATALKAN setelah diperiksa.** Semula direncanakan `sebaranKomoditas()` diturunkan dari `hasilPanen()` agar tidak lagi berselisih 113 kali lipat. Pemeriksaan sebelum menyentuh kode menunjukkan **empat angka dashboard sudah saling konsisten** pada 1.847,5 ton: `ringkasanDashboard()`, jumlah `rekapPerSp()`, dan nilai terakhir `deretTahunan()`. Menurunkannya akan merusak ketiganya sekaligus, dan membuat dashboard menampilkan belasan ton untuk kawasan berisi ribuan keluarga.
  * Jadi keduanya **bukan dua versi angka yang sama**, melainkan menjawab pertanyaan berbeda: `sebaranKomoditas()` agregat kawasan setahun, `hasilPanen()` beberapa transaksi contoh untuk menguji tampilan tabel. Yang kurang selama ini hanyalah keterangan itu sendiri, dan kini ditulis pada kedua metode beserta uji penjaga.
  * **"Komoditas utama" dan "komoditas unggulan" tetap dua hal berbeda** dan sengaja tidak disatukan. Utama dihitung dari volume dan berubah mengikuti musim; unggulan ditetapkan program. Keduanya kebetulan menunjuk jagung, dan itulah yang membuat perbedaannya mudah terlewat. Kini dijelaskan di layar maupun di komentar kode.
  * **Satu cacat laten ikut diperbaiki.** Kartu "Komoditas Utama" memilih lewat `array_key_first()`, sehingga hasilnya bergantung **urutan penulisan larik**, bukan nilai terbesar. Selama ini benar hanya karena `sebaranKomoditas()` kebetulan ditulis terurut. Diganti `max()`.
- [done] fitur update anggota poktan di mana ya? apakah sudah ada? lalu apakah anggota poktan bisa dihapus atau cuma bisa status aktif/nonaktif? lalu bagaimana kalau ada case perpindahan anggota gitu? sepertinya pada form tambah dan update pada transmigran gak perlu field anggota kelompok tani, sebab nanti yg menentukan itu apakah nantinya dia masuk ke poktan yg ditambahkan pada halaman poktan.
- [done] pada form tambah anggota poktan, harusnya pada field jabatan itu tidak ada ketua.
- [done] pada form tambah poktan, kenapa opsi ketuanya hanya dari transmigran? padahal realitanya banyak ketua poktan yg bukan dari transmigran. mungkin bisa disiasati sebelum menginput data ketuanya itu dikasih field apakah ketuanya dari transmigran atau bukan. Jika iya, maka nanti field-nya bisa diisi dari data transmigran yg sudah ada. Jika tidak, maka ya field-nya diisi data non transmigran tersebut.
- [done] pada form tambah poktan, field telepon, email, dan alamat itu diambil dari data ketua, bukan milik kelompok gitu. Jadi jika ketuanya dari transmigran, maka field telepon, email, dan alamat otomatis terisi dari data ketua transmigran yg sudah ada. 
  * **Empat poin ini dikerjakan bersama sebab saling mengunci:** keputusan asal-usul ketua menentukan bentuk isian jabatan sekaligus sumber kontak poktan.
  * **Temuan terbesar, dan bukan sekadar preferensi: anggota poktan tidak dapat diubah sama sekali.** Tidak ada tombol edit per baris, tidak ada modal ubah, dan tidak ada rute PUT. Akibatnya `status` keaktifan dan `tanggal_keluar` **tidak pernah dapat diisi** setelah anggota tersimpan, padahal justru keduanya yang berubah belakangan (`rules.md` 7a.4). Ditutup dengan kolom Aksi, modal `formUbahAnggotaPoktan` berpola `:id`, dan rute `anggota-poktan.perbarui`.
  * **Dokumen sempat bertentangan sendiri.** Matriks `rules.md` memberi huruf **H** pada Anggota poktan untuk Admin, sementara catatan 5.1 melarang penghapusan demi menjaga riwayat. Kode memilih larangan, dan kini huruf H dicabut agar dokumen tidak menjanjikan tindakan yang memang tidak ada. Total kewenangan turun 96 menjadi **95**.
  * **Ketua sempat dapat ditetapkan di dua tempat sekaligus:** `poktan.ketua_transmigran_id` dan `anggota_poktan.jabatan = 'Ketua'`, tanpa satu pun validasi silang maupun batas satu ketua per poktan. Nilai `Ketua` karena itu dicabut dari enum jabatan; ketua kini hanya hidup di profil poktan.
  * **Ketua tidak selalu transmigran, dan itu keadaan lapangan yang nyata.** Form kini bercabang lewat `is_ketua_transmigran`: bila ya, dipilih dari daftar agar NIK dan tautan profilnya sahih; bila tidak, `nama_ketua` dan `nik_ketua` diketik langsung. Data contoh POKTAN HARAPAN BARU sengaja dibuat berketua non-transmigran agar cabang kedua ikut terlihat saat peninjauan.
  * **Kontak poktan adalah kontak ketua, bukan kontak kelompok.** Penamaan diseragamkan menjadi `telepon_ketua`, `email_ketua`, `alamat_ketua`.
  * **ALASAN DIPERBAIKI 2026-08-19.** Alasan yang semula ditulis bersandar pada bentuk `DummyData` � "kontak poktan ternyata sudah lama menjadi kontak ketua di dalam kode, hanya dokumennya yang menyebut lain" � dan itu **penalaran melingkar**: data contoh dikarang AI sendiri, sehingga ia tidak dapat membuktikan apa pun tentang keadaan lapangan (`rules.md` 19a). Lebih buruk lagi, alasan itu membatalkan keterangan lapangan yang sudah ditulis benar pada bagian 1a.6 yaitu "kontak kelompok bisa berbeda dari kontak pribadi ketua", sehingga arah penalarannya terbalik: dokumen disesuaikan ke data karangan, bukan sebaliknya.
  * Keputusannya **kebetulan tetap benar**, dan kini berdasar keterangan pemilik proyek (2026-08-19): kelompok tani di Kobalima Timur **tidak memiliki kontak sendiri** yang berbeda dari kontak ketuanya. Itulah dasar yang sah, bukan bentuk `DummyData`. Yang sebenarnya ditemukan pada kode hanyalah **ketidakcocokan penamaan** � form mengirim `name="telepon"` tetapi membaca `$data['telepon_ketua']` � dan itu pertanyaan tentang kode yang memang boleh dijawab dari data contoh.
  * `email_ketua` **menjadi satu-satunya tempat email ketua dapat disimpan**, sebab tabel `transmigran` tidak memiliki kolom email padahal `rules.md` 7a.2 mewajibkannya. Telepon terisi sendiri dari data transmigran tetapi tetap dapat disunting, karena petugas kerap memegang nomor yang lebih baru daripada yang tercatat.
  * **Perpindahan anggota dicatat sebagai dua baris**, bukan dengan memindahkan `poktan_id`. Memindahkan baris yang sama akan menghapus jejak keanggotaan di poktan lama seolah tidak pernah ada. Ditambah aturan bahwa seorang transmigran hanya boleh **Aktif di satu poktan**; UNIQUE `(poktan_id, transmigran_id)` tidak menangkap ini sebab poktannya memang berbeda.
  * **Field poktan pada form transmigran dijadikan turunan, bukan isian.** Benar seperti dugaan: `status_anggota_poktan` tidak pernah tersinkron dengan `anggota_poktan`, sehingga petugas dapat menyatakan "Ya" tanpa seorang pun mendaftarkannya ke kelompok mana pun. Keanggotaan kini ditetapkan dari sisi poktan saja.
  * **Satu cacat Alpine ikut ditemukan.** Isian status memakai `x-model`, yang menimpa kembali nilai yang disuntikkan modal ubah, sehingga bagian tanggal keluar tidak pernah muncul untuk anggota yang memang sudah berstatus Sudah Keluar. Diganti `x-init` + `@change`.
  * Dijaga 6 uji baru, seluruhnya dibuktikan lewat mutasi.
- [done] tambahkan searchable dropdown pada semua dropdown yg kemungkinan list datanya banyak banget.
  * Dibuat `x-sim.pilih-cari`, dipasang pada **7 isian** yang sumbernya tabel data: ketua poktan, anggota poktan, pemilik alsintan, pemilik lahan, petani panen, penghuni rumah, dan lahan pada riwayat tanam.
  * **Isian sesungguhnya tetap `<select>` biasa.** Kotak pencarian hanya menyaring opsi yang ditampilkan, sedangkan nilai yang terkirim tetap berasal dari elemen bernama sama. Akibatnya Form Request pada tahap backend tidak perlu tahu komponen ini ada, dan halaman tetap berfungsi bila JavaScript gagal dimuat: yang hilang hanya kotak pencariannya, bukan kemampuan memilih.
  * **Kotak pencarian hanya muncul bila daftarnya memang panjang** (ambang 8 opsi). Memasangnya di atas empat pilihan justru menambah satu benda yang harus dilewati, bukan mempercepat.
  * ~~Dengan data contoh sekarang, `/rumah` dan `/riwayat-tanam` sengaja tidak menampilkannya sebab daftarnya masih 4 dan 6 baris.~~ **DITANDAI PERLU TINJAU ULANG 2026-08-19.** Kalimat ini mengalibrasi tampilan pada hitungan data contoh, padahal `/rumah` menampilkan daftar rumah dan `/riwayat-tanam` menampilkan daftar lahan yang pada data nyata mencapai ribuan baris. Ambang 8 opsinya sendiri tidak bermasalah; yang bermasalah adalah **menyimpulkan bahwa kedua halaman itu tidak memerlukan pencarian**. Begitu data nyata masuk, keduanya akan menampilkan dropdown ribuan baris tanpa kotak pencarian. Dicatat sebagai tindak lanjut butir 10 pada bagian 4. Lihat bagian 1c.2 pelanggaran ketiga.
  * Alasan tidak memakai pustaka pihak ketiga: perilaku yang dibutuhkan hanya menyaring daftar, dan Alpine yang sudah terpasang cukup. Menambah dependensi berarti menambah berkas yang harus diunduh petugas di lokus yang sinyalnya tidak selalu stabil.
  * Pencarian mencocokkan **teks maupun keterangannya**, sebab petugas kerap mengingat asal SP lebih dulu daripada nama lengkapnya.
  * **Satu jebakan Alpine ditemukan saat pemasangan.** `x-model` pada select yang opsinya dirender lewat `x-for` menyetel ulang nilainya setiap daftar opsi berubah, sehingga pilihan petugas hilang begitu ia mengetik di kotak pencarian. Diganti `@change` pada isian ketua poktan.
  * Mendesaknya baru terasa pada data nyata: PRD menyebut sekitar **1.140 kepala keluarga**, sedangkan data contoh hanya 8.
  * **DIBANGUN ULANG 2026-08-17 setelah pemilik proyek meninjau tampilannya.** Rancangan pertama keliru: kotak pencarian ditaruh **di atas** `<select>` sebagai dua kontrol berjajar, sehingga pengguna melihat dua kotak dan harus menebak sendiri bahwa yang satu menyaring yang lain. Satu pekerjaan tidak boleh memerlukan dua kontrol yang kaitannya tidak terlihat.
  * Bentuk sekarang combobox sungguhan: satu tombol menampilkan pilihan aktif, dan kotak pencarian berada **di dalam** panel bersama daftarnya.
  * **Aturan lama pada `ui-spec.md` 6.0a butir 1 ikut dicabut.** Butir itu berbunyi "isian sesungguhnya tetap `<select>` biasa", dan aturan itulah yang membenarkan bentuk lama. Nilai kini disimpan pada isian ber-`name` berkelas `sr-only`, **bukan `type="hidden"`**, sebab peramban mengabaikan `required` pada isian tersembunyi: form akan terkirim tanpa peringatan meski isian wajib masih kosong.
  * Jaminan tanpa JavaScript **tidak dicabut**, hanya dipindah: `<select>` asli kini dirender di dalam `<noscript>`.
  * **Empat kontrak tersembunyi ditemukan lewat riset sebelum menulis kode**, dan semuanya akan rusak diam-diam bila diabaikan: `isiFormulir()` milik modal yang menyetel `.value` lalu memancarkan `change`; `@change` pada form poktan yang membaca `$event.target.value`; Escape modal yang akan ikut tertutup tanpa `.stop`; dan focus trap modal yang hanya menjaring `a/button/input/select/textarea`, sehingga pemicu wajib berupa `<button>`.
  * **Diuji di peramban sungguhan, bukan hanya lewat string HTML.** Empat belas pemeriksaan lewat Edge headless: buka panel, fokus pindah ke kotak cari, penyaringan, Enter memilih, Escape berlapis, kontrak `isiFormulir`, penegakan `required`, dan geometri panel. Justru pemeriksaan inilah yang kemarin luput, sebab uji Pest hanya memastikan sebuah string ada di HTML, bukan bahwa tampilannya masuk akal.

  **Dua bug lama ikut ditemukan saat pengujian peramban:**
  * **`x-cloak` tidak berfungsi sama sekali di seluruh sistem.** Definisinya hanya ada inline di `components/ui/modal.blade.php`, dan komponen itu **tidak dipakai satu halaman pun**. Akibatnya **96 pemakaian** `x-cloak` mati: modal, laci filter, dan panel sempat berkedip terlihat setiap kali halaman dimuat. Diperbaiki dengan satu aturan di `app.css`.
  * **Skrip tema melempar galat pada setiap pemuatan halaman.** Ia berjalan di dalam `<head>` dan menyentuh `document.body` yang belum ada, sehingga melempar "Cannot read properties of null". Galatnya tidak menghentikan apa pun sehingga tidak pernah disadari, tetapi ia membanjiri konsol dan menyamarkan galat lain yang benar-benar penting. Terjadi di dua layout sekaligus.


------------------------------------------------------------------------------------------------------------------------------------

- [done] Ketika klik data master wilayah, kenapa url-nya langsung menuju ke tab kecamatan?
  * **Tidak ada alasannya.** `hashTabs('kecamatan')` ditulis tanpa pertimbangan, dan keliru pada dua hal sekaligus: pembacaannya melompati dua tingkat pertama sehingga susunan hierarki yang baru saja dijelaskan di kepala halaman tidak terlihat, dan pengunjung yang mengklik menu langsung mendapat alamat `?tab=kecamatan` seolah ia pernah memilihnya sendiri. Diubah menjadi `provinsi`.
  * **Satu cacat kecil ikut terlihat begitu bawaannya diperbaiki.** Panel provinsi satu-satunya yang tanpa `x-cloak`, sementara bawaannya kecamatan � sehingga panel provinsi justru **berkedip terlihat** lalu tergantikan. Keduanya kini sejalan: panel bawaan sengaja tanpa `x-cloak` agar halaman tidak kosong sesaat, tiga lainnya memakainya.
- [done] Pada form tambah wilayah, kenapa field Tingkat wilayah otomatis terisi desa?
  * **Tingkat bawaan kini mengikuti tab yang sedang dibuka**, bukan nilai tetap. Sebelumnya selalu `desa`, sehingga petugas yang membuka tab Kecamatan lalu menekan Tambah mendapat form bertingkat Desa dan harus menggantinya setiap kali. Tab yang sedang dibuka adalah pernyataan paling jelas tentang apa yang hendak ditambahkan.
  * Nilai tab **disaring** terhadap daftar yang sah; alamat yang dikarang seperti `?tab=ngawur` jatuh ke tingkat teratas, bukan diteruskan apa adanya.
  * **CACAT BESAR IKUT DITEMUKAN: form ini tidak pernah dapat dikirim untuk tingkat apa pun.** Ketiga isian induk saling meniadakan, tetapi ketiganya bertanda `required` **tetap**. Peramban karena itu menuntut ketiganya terisi sekaligus, padahal dua di antaranya selalu tersembunyi. Lebih buruk lagi, pesan galatnya menunjuk elemen tersembunyi sehingga petugas **tidak melihat apa yang kurang** � form seolah menolak diam-diam. Diganti pasangan `:required`/`:disabled` bersyarat, pola yang sudah dipakai form poktan dan form lahan.
  * Ditemukan hanya karena uji peramban menanyakan `checkValidity()`, bukan apakah atributnya tertulis. Mutasi membuktikannya: mengembalikan satu `required` tetap langsung memerahkan dua pemeriksaan, salah satunya berbunyi *"form dapat dikirim untuk tingkat provinsi"*.
  * `@selected` ditambahkan di samping `x-model` pada keempat isian, sebab tanpa itu tidak ada option yang terpilih ketika JavaScript gagal dimuat.
- [done] di form tambah Kawasan trans, sebelum form kabupaten, bukankah harusnya ada form provinsi dan lalu baru form kabupaten (multilevel dropdown)?
  * **Benar, dan alasannya bukan sekadar kerapian.** Menyodorkan daftar kabupaten se-Indonesia tanpa menanyakan provinsinya membuat petugas mencari di antara lima ratusan nama yang sebagian besar tidak pernah relevan, dan **nama kabupaten tidak selalu unik antar-provinsi**. Kawasan memang hierarki program yang memotong batas kecamatan, tetapi pangkalnya tetap sama: kabupaten berada di bawah provinsi.
  * **Provinsi tidak disimpan.** `kawasan` hanya menyimpan `kabupaten_id`; provinsinya terbaca lewat rantai itu. Menyimpannya terpisah membuka peluang data tidak sinkron � kekeliruan yang sama sudah dihindari saat memutuskan SP tidak menyimpan `kecamatan_id` (1a.8). Isian provinsi karena itu **tidak bertanda wajib**: ia hanya menyaring.
  * **Penyaringan di sisi klien**, seluruh kabupaten dirender lalu disaring `x-for`. Dipilih agar tidak ada permintaan tambahan ke peladen, sejalan dengan pola autofill form poktan. Bila kelak daftarnya ribuan baris, barulah pemuatan bertahap sepadan.
  * Kabupaten **terkunci** selama provinsi belum dipilih, dengan ajakan "Pilih provinsi lebih dulu". Dropdown yang tampak dapat dibuka tetapi tidak menawarkan apa pun menyesatkan.
  * Jaminan tanpa JavaScript mengikuti pola `pilih-cari`: `<noscript>` merender daftar penuh beserta nama provinsinya, dan aturan gayanya menyembunyikan kedua select bertingkat agar tidak ada dua kontrol berebut satu nama.

  * **Satu uji peramban yang saya tulis sendiri ternyata menguji hal yang salah, dan mutasi yang membongkarnya.** Pemeriksaan "kabupaten dilepas saat provinsi berganti" membaca `.value` DOM, padahal peramban **mengosongkan `.value` dengan sendirinya** ketika opsi terpilih lenyap dari daftar. Uji itu karena itu selalu hijau � termasuk ketika pelepasannya sengaja dilumpuhkan. Yang sesungguhnya bermasalah adalah `x-model` yang masih memegang id lama, dan nilai itulah yang ikut terkirim begitu isian kembali punya opsi yang cocok. Pembacaannya dipindah ke state Alpine, dan mutasi yang sama kini memerah.
  * **Pelajarannya menambah catatan 1d.2:** uji perilaku pun dapat menguji hal yang salah bila yang dibaca adalah gejala yang kebetulan ikut berubah, bukan keadaan yang benar-benar menentukan. Mutasi bukan formalitas � di sini ia satu-satunya yang membedakan uji yang menjaga dari uji yang menemani.
  * Ketiganya dijaga **5 uji Pest baru** dan `tests/Browser/uji-master-wilayah.mjs` berisi **19 pemeriksaan perilaku**.
- [done] coba cek lagi untuk beberapa halaman detail dari sebuah data (view detail) di github page itu masih ada yg gak bisa dibuka
  * **Bukan bug kode, melainkan situs yang basi dua hari.** Penerbitan terakhir (`9ea3918`, 19 Agustus) **gagal**, sehingga GitHub Pages masih menyajikan hasil 18 Agustus yaitu sebelum halaman rincian Inventaris SP dan Fasilitas SP ada. Terverifikasi: keduanya 404 di situs sementara halaman daftarnya 200, dan seluruh 153 alamat lolos 200 di lokal.
  * **Kegagalannya sendiri berlangsung buta.** Log hanya berbunyi `Process completed with exit code 3`, tanpa satu pun baris `GAGAL ###` maupun ringkasan yang sudah disiapkan skripnya. Sebabnya `shell: bash -e`: satu perintah gagal menghentikan skrip **seketika** beserta kode keluar milik perintah itu, sehingga penghitung `gagal` tidak pernah terpakai dan pesan yang seharusnya menjelaskan justru tidak pernah tercetak. Kode 3 adalah kode `curl` untuk URL malformed.
  * **Akar rapuhnya: keluaran Artisan dipakai mentah sebagai daftar alamat.** Satu peringatan PHP atau pesan deprecation ikut terbawa ke `alamat.txt`, lalu dirangkai menjadi URL. Kini keluarannya **disaring** terhadap pola alamat, dan baris yang tidak lolos **menghentikan penerbitan dengan menyebut baris mana** yang bermasalah, bukan meneruskannya ke `curl`.
  * Tiga lapis penjagaan ditambahkan: `mkdir` dan `curl` masing-masing dijaga dan kegagalannya ikut terhitung, `set +e` dipakai agar satu laporan memuat **seluruh** halaman bermasalah bukan hanya yang pertama, dan satu langkah baru memastikan setiap alamat benar-benar menghasilkan berkas tidak kosong.
  * Langkah terakhir itu menutup kegagalan yang paling berbahaya: `curl` membalas 200 tetapi berkasnya kosong atau foldernya gagal terbentuk. Kekeliruan semacam itu baru muncul sebagai 404 di tangan pengguna, persis yang terjadi pada halaman rincian SP (1b.6a).
  * **Butir ini selesai begitu penerbitan berikutnya berhasil**, bukan dengan menyunting kode aplikasi.
- [done] semua modal float (yg saya cek cuma modal float tambah dan ubah) ketika scroll terlalu ke bawah, susah untuk scroll ke atas lagi. kemarin sudah sempet dibenerin tapi kayaknya masih bisa scroll terlalu ke bawah hingga modalnya tenggelam ke atas yg menyebabkan susah untuk men-center-kannya lagi dengan scrolling. Harus di-refresh halamannya.
  * **Perbaikan sebelumnya benar tetapi menyasar sebab yang berbeda.** `kunci-gulir.js` menutup gulir halaman di belakang modal, dan itu memang perlu. Yang tersisa adalah geometri modal itu sendiri.
  * **Sebab pertama: `items-center` pada wadah yang juga bergulir.** Panel yang lebih tinggi daripada layar dan diratakan tengah akan meluber ke **dua arah**, dan luberan atasnya **tidak pernah dapat dijangkau** sebab `scrollTop` tidak bisa bernilai negatif. Persis gejala yang dilaporkan: modal tenggelam ke atas dan hanya pulih dengan memuat ulang halaman.
  * **Sebab kedua: dua wilayah bergulir bertumpuk.** Wadah terluar dan badan formulir sama-sama `overflow-y-auto`, sehingga yang bergerak bergantung posisi kursor.
  * **Penyelesaiannya tetap memusatkan modal**, sesuai permintaan: `sm:items-start` dipasang bersama `my-auto` pada panelnya. Keduanya tampak sama persis selama modal lebih pendek daripada layar; bedanya baru muncul ketika lebih tinggi, dan di situ `my-auto` berhenti memusatkan sehingga panel menempel di atas dan seluruh isinya terjangkau.
  * Tinggi badan yang semula ditebak `calc(100vh-16rem)` diganti tata letak flex: kepala dan kaki `shrink-0`, badan `flex-1` yang menyusut. Tebakan `calc` pasti meleset begitu kepala atau kaki lebih tinggi daripada perkiraan.
  * **`min-h-0` pada elemen `<form>` wajib ada, dan mutasi membuktikannya.** Item flex bernilai bawaan `min-height: auto` sehingga menolak menyusut di bawah tinggi isinya; tanpa itu `max-h-full` pada panel tidak pernah berlaku. Menghapusnya membuat wilayah bergulir menjadi **nol** dan modal tumbuh melampaui layar lagi. Sebaliknya `min-h-0` pada badan ternyata **tidak** diperlukan, dan mutasi juga yang menunjukkannya.
  * Diterapkan pada **5 komponen** berpola sama: `modal-form`, `modal-impor`, `koordinat-input`, `tautan-peta`, `confirm-dialog`. Yang terakhir sebelumnya satu-satunya yang memakai `items-center` tanpa varian titik henti.
  * **Satu jebakan alat ikut ditemukan.** Perbaikan sempat terbaca tidak bekerja padahal kelasnya sudah benar: `max-h-full` menghasilkan `max-height: none` sebab **aset CSS belum dibangun ulang**. Tailwind v4 hanya memuat kelas yang benar-benar dipakai, sehingga kelas baru tidak ada di berkas hasil build lama. Uji peramban yang memerah justru yang mengungkapnya; uji string tidak akan pernah bisa.
  * Dijaga `tests/Browser/uji-gulir-modal.mjs`, **24 pemeriksaan** pada viewport 1280x500 yang sengaja pendek agar setiap modal pasti melampaui layar. Mutasi mengembalikan `items-center` langsung memerahkan empat pemeriksaan, dengan panel terbaca berada pada **-1049px** yaitu jauh di luar layar. Diperiksa pula pada 390x600 (mobile): panel memenuhi layar, satu wilayah gulir.
- [done] halaman musim tanam, Riwayat tanam, inventaris, dan fasilitas sp tambahkan view/halaman detail untuk dibuatkan tab catatan log
  * **Dua dari empat sudah ada.** Rincian Inventaris SP dan Fasilitas SP dibuat 2026-08-19 bersama fitur tautan objek pengaduan, dan keduanya sudah bertab Catatan Log sejak itu. Yang benar-benar belum ada hanya musim tanam dan riwayat tanam.
  * Keduanya kini punya halaman rincian bertab: musim tanam menampilkan rentang waktu beserta penanaman yang jatuh di dalamnya, riwayat tanam menampilkan penanaman beserta panen yang dihasilkannya. Halaman terbit **153 menjadi 161**.
  * **Rincian riwayat tanam berpusat pada penanaman, bukan pada musimnya.** Alasannya struktural: `hasil_panen.riwayat_tanam_id` menentukan lahan, musim, dan komoditas sekaligus (kamus data 9.3), sehingga satu baris riwayat tanam adalah simpul yang menghubungkan ketiganya.

  * **CACAT PADA UJI PENJAGANYA SENDIRI IKUT DITEMUKAN.** Uji `memasang catatan log pada SETIAP halaman rincian yang ada` membaca daftar rute lewat pola `^[a-z-]+/\{id\}$`, dan pola itu **hanya cocok pada URI beruas dua**. Akibatnya `sp/inventaris/{id}` dan `sp/fasilitas/{id}` tidak pernah ikut terperiksa sejak dibuat.
  * Keduanya kebetulan memang sudah memasang tab log, sehingga tidak ada kerusakan nyata. Yang rusak adalah **penjaganya**: uji yang dibuat khusus untuk mencegah halaman rincian terlewat, justru melewatkan dua halaman dengan diam. Polanya kini menerima dua maupun tiga ruas, dan ditambah pemeriksaan eksplisit bahwa kedua halaman itu benar-benar terjaring.
- [done] fitur upload dokumen pada halaman inventaris dan fasilitas itu bisa upload banyak dokumen/foto atau cuma 1 doang? Kalau cuma 1, bagaimana kalau tambah fitur upload foto seperti infrastruktur.
  * **Jawabannya: cuma satu.** Komponen `x-sim.file-upload` memang satu berkas per instansi, tanpa atribut `multiple`, dan mengunggah berkas baru **mengganti** yang lama.
  * Ditambahkan kolom `foto` pada `inventaris_sp` dan `fasilitas_sp`, mengikuti pola `infrastruktur` yang sejak awal memisahkan keduanya. Dipilih dua kolom, bukan tabel anak seperti `dokumen_lahan`, sebab yang diperlukan memang dua berkas berbeda jenis, bukan banyak berkas sejenis.
  * **Cacat yang membuatnya mendesak:** label fasilitas berbunyi *"Dokumen atau Foto Fasilitas"* dengan keterangan yang menawarkan tiga kemungkinan isi, padahal slotnya cuma satu. Petugas yang mengunggah berita acara setelah foto akan **kehilangan fotonya tanpa peringatan apa pun** � form tetap tersimpan, hanya berkasnya yang tertimpa.

  * **TEMUAN YANG LEBIH BESAR: ketiga halaman aset tidak pernah menampilkan berkasnya sama sekali.** Bukan hanya inventaris dan fasilitas � **infrastruktur juga**, padahal ia satu-satunya yang sejak awal punya dua kolom terpisah. Ketiganya menerima unggahan lewat form lalu tidak menyediakan cara membukanya. Itu kontrol mati yang dilarang R-26: petugas mengunggah berita acara, lalu tidak menemukan cara membacanya.
  * **Uji penjaganya sendiri melewatkan ini dengan diam.** Uji `menyediakan cara membuka setiap berkas yang sudah diunggah` hanya memeriksa berkas Blade yang memuat `basename(`. Halaman yang **tidak menampilkan apa pun** karena itu tidak pernah masuk pemeriksaan � penjaga yang hanya mengawasi yang sudah benar.
  * Keenam halaman rincian kini menampilkan berkasnya lewat `x-sim.tautan-dokumen`: alsintan, saprotan, poktan masing-masing satu berkas; infrastruktur, inventaris SP, fasilitas SP masing-masing dua. Dijaga uji berbasis daftar yang **menghitung jumlah tautan**, sehingga satu berkas yang lupa ditampilkan langsung memerah.
  * Aturannya ditulis pada `ui-spec.md` 6.4: satu instansi satu berkas, modul yang perlu foto sekaligus dokumen memasang dua instansi, dan berkas yang dapat diunggah **wajib dapat dibuka kembali**.
- [done] pada semua form dropdown statis di semua halaman yang ada form dropdown statisnya, apakah memungkinkan jika dibuatkan data master untuk CRUD pilihan pada form dropdown-nya? Atau kalau gak dibuatkan data master, mungkin bisa dibuat CRUD nya itu di halaman yg bersangkutan biar gak terlalu bingung. Namun aku bingung nanti letaknya di mana agar tidak terlalu mengganggu estetika halaman yg sudah jadi. Coba kita diskusikan ini.
  * **Dijawab bersama butir berikutnya**, yang menyebut daftar dropdown mana saja yang dimaksud. Pilihan "CRUD di halaman yang bersangkutan" dikesampingkan: sepuluh tempat untuk satu pekerjaan, dan estetika sepuluh halaman yang sudah jadi menjadi taruhannya.
  * Dipilih **satu halaman data master tersendiri** di `/master/referensi`, bertab sembilan. Tempatnya sudah ada polanya: `/wilayah` dan `/master/satuan` sudah lebih dulu hidup di menu Pengaturan Sistem, dan `/wilayah` bahkan sudah memakai pola tab yang sama.
- [done] field form dropdown yg belum searchable dropdown: c/u (create/update) rumah di field kepala keluarga penghuni, c/u kelompok tani di form tambah alsintan dan saprotan, c/u lahan di form riwayat tanam, c/u field form musim tanam di riwayat tanam dan hasil panen. Coba cek lagi apakah ada yg terlewat lagi.
  * **Dua dari lima yang disebut ternyata sudah searchable:** kepala keluarga penghuni pada form rumah, dan lahan pada form riwayat tanam. Keduanya memang sudah memakai `pilih-cari` sejak dipasang.
  * Yang benar-benar tersisa dipasangi seluruhnya: **poktan** pada form alsintan dan saprotan, **musim tanam** pada form riwayat tanam, dan **catatan tanam** pada form panen. Ditambah satu yang **terlewat dari daftar**: `transmigran_id` pada form saprotan, satu-satunya isian transmigran yang masih `<select>` biasa padahal enam form lain sudah memakai `pilih-cari`.
  * **Ambang 8 opsi yang menentukan, bukan halaman.** Poktan baru 4 baris dan musim tanam 3 baris pada data contoh, sehingga keduanya tetap tampil sebagai dropdown biasa untuk sementara. Yang penting: begitu data nyata masuk, pencariannya sudah ada tanpa perlu menyunting form lagi. Ini sekaligus menutup tindak lanjut yang ditandai pada butir 2026-08-19 tentang mengalibrasi tampilan pada hitungan data contoh.
  * Dijaga uji yang **membaca daftar isian bersumber tabel**, dan memerah bila salah satunya kembali menjadi `<select>` bertulis tangan.

  **DUA CACAT IKUT DITEMUKAN, dan yang kedua jauh lebih berat:**
  * **Musim tanam pada form riwayat tanam salah membandingkan.** Isiannya mengirim `musim_tanam_id` tetapi `@selected` membandingkan `old('musim_tanam')` terhadap label, sehingga pilihan petugas **hilang setiap kali form gagal tersimpan**.
  * **Form panen menawarkan musim tanam yang ditulis tangan.** Isiannya bernama `riwayat_tanam_id` tetapi opsinya berupa tiga label harfiah `MT1 2026`, `MT2 2025`, `MT1 2025`. Dua hal keliru sekaligus: nilai yang terkirim berupa **teks label, bukan id**; dan daftarnya **tidak pernah bertambah** ketika musim tanam baru didata, sehingga panen musim berikutnya tidak akan dapat dicatat sama sekali.
  * Diperbaiki menjadi pilihan **catatan tanam**, bukan musim, sebab `hasil_panen.riwayat_tanam_id` menentukan lahan, musim, dan komoditas sekaligus (kamus data 9.3). Labelnya menyebut ketiganya agar petugas tahu persis penanaman mana yang sedang dipanen.

  * **KOREKSI 2026-08-20 ATAS TEGURAN PEMILIK PROYEK.** Catatan di atas menyatakan dua isian "sudah searchable", dan itu **benar tentang kode tetapi menyesatkan tentang layar**: komponennya memang terpasang, tetapi kotak pencariannya tidak muncul sebab daftarnya di bawah ambang 8. Yang ditanyakan adalah "mana yang belum searchable", yaitu pertanyaan tentang yang terlihat; yang dijawab adalah pertanyaan tentang kode.
  * **Ambang 8 dicabut seluruhnya.** Kotak pencarian kini selalu dirender, dan kriterianya sifat sumber: bila daftarnya bertambah ketika petugas menambah data, pencariannya diperlukan berapa pun jumlahnya hari ini. Kedelapan halaman berisian `pilih-cari` kini benar-benar menampilkannya; sebelumnya hanya satu, yaitu `/lahan` yang kebetulan daftarnya tepat 8 baris.
  * `satuan_id` dikecualikan atas keputusan pemilik proyek: ia memang dapat ditambah Admin lewat data master, tetapi satuan takaran tidak akan pernah menuntut pencarian. Pengecualian ini disebut satu per satu pada `ui-spec.md` 6.0a.5c, bukan dinyatakan sebagai ambang baru, agar tidak ada lagi yang perlu ditebak.
  * Rinciannya beserta sebab kekeliruan yang sama terulang tiga kali ada pada bagian 1c.2 pelanggaran keenam.
- [done] field form dropdown yg perlu data master: sumber dana, status penyerahan, kondisi, jenis (fasilitas dan infrastruktur), kondisi rumah, status hunian, status tinggal, tipe komoditas, kualitas panen, prioritas, jenis dokumen, jabatan. Coba cek lagi apakah ada yg terlewat lagi? Untuk nama field form-nya tolong seragamkan semua. Untuk field form kategori dan bidang penanganan di form catat pengaduan warga enaknya dibuat master datanya atau gak ya? Sebab dia kan saling terhubung gitu. Poin ini menjawab poin yg dropdown statis.
  * **Sepuluh daftar menjadi data master**, dikelola lewat `/master/referensi` bertab. `jabatan` yang kamu sebut ikut masuk, sehingga daftarnya menjadi sepuluh: sumber dana, status penyerahan, kondisi, kondisi rumah, status hunian, tipe komoditas, kualitas panen, prioritas pengaduan, jenis dokumen lahan, dan jabatan anggota poktan. **26 dropdown** pada 12 berkas dialihkan membacanya.
  * **Satu tabel, bukan sepuluh.** Strukturnya identik, sehingga sepuluh tabel berarti sepuluh migration, sepuluh model, dan sepuluh halaman CRUD untuk perbedaan yang hanya terletak pada nama jenisnya.
  * **Nilai DINONAKTIFKAN, tidak pernah dihapus.** Inilah inti rancangannya, dan alasannya kamu tanyakan sendiri: menghapus `Hibah` dari sumber dana membuat puluhan baris infrastruktur lama menunjuk nilai yang lenyap, dan rekapnya kehilangan baris itu **tanpa pesan apa pun**. Nilai nonaktif tetap terbaca pada data lama, hanya berhenti ditawarkan pada data baru. Tanpa rute hapus sama sekali, dan tanpa kewenangan hapus bagi siapa pun.
  * **Yang tersimpan tetap TEKS, bukan id.** Kolom-kolom pemakainya bertipe ENUM pada SQL referensi dan sudah dipakai puluhan tampilan tanpa join; mengubahnya menjadi FK berarti membongkar seluruhnya demi keuntungan yang tidak ada. Pengecualiannya hanya `parameter_penilaian_sp.jenis_rujukan` pada Fase 4 nanti, sebab di sanalah penggantian teks berakibat fatal.

  **Tiga hal yang ikut ditemukan saat mengerjakan:**
  * **`kondisi_rumah` ternyata TIDAK dipakai perhitungan apa pun.** Ia tampak sebagai kembaran `kondisi` � skala kerusakan yang sama persis � tetapi hanya `kondisi` yang dibaca `PenilaianKondisiSp`. Karena itu hanya `kondisi` yang diberi kolom `nilai_skor`. Memberikannya kepada `kondisi_rumah` berarti menyediakan isian yang tidak menentukan apa pun, dan Admin yang menyuntingnya akan menyangka skor SP ikut berubah.
  * **Skor kondisi dicabut dari konstanta kode.** `PenilaianKondisiSp::NILAI_KONDISI` (Baik 1,0 / Rusak Ringan 0,5 / Rusak Berat 0,2) berpindah ke kolom `nilai_skor`. Sebelumnya **bobot parameter dapat disunting Admin tetapi nilai kondisinya tidak**, padahal keduanya sama-sama menentukan skor akhir � separuh perhitungan dapat diatur, separuhnya terkunci.
  * **Penilaian lama tidak ikut berubah**, sesuai keputusanmu menyimpan salinan. Ternyata strukturnya **sudah ada**: `penilaian_sp.rincian` sejak awal menyalin bobot, kondisi, dan nilai yang berlaku saat penilaian dibuat, beserta alasan yang merujuk preseden `hasil_panen.satuan_id`. Yang perlu dikerjakan hanya memastikan sumbernya master, bukan konstanta.

  * Kewenangan menjadi fitur ke-28: **98 menjadi 101**, per role 101/48/47/51. Admin dan Dinas Transmigrasi sama-sama `L T U`, sesuai prinsipmu bahwa keduanya hampir setara kecuali fitur sistem.
  * Dijaga **8 uji baru** pada `tests/Feature/ReferensiTest.php`, salah satunya menyisir seluruh berkas view dan memerah bila ada yang kembali memakai enum lama � sebab enum yang tertinggal menjadi sumber kedua yang diam-diam berbeda begitu Admin menambah satu nilai. Dibuktikan lewat tiga mutasi.
  * Saat Fase 1 sampai 3 selesai, **kategori dan bidang pengaduan belum boleh ikut** (Fase 5): `BidangPengaduan::dariKategori()` masih `match` tanpa `default`, sehingga kategori baru akan meruntuhkan form pengaduan. Begitu pula `jenis_infrastruktur` dan `jenis_fasilitas` (Fase 4), sebab keduanya dipakai sebagai `jenis_rujukan` pada 13 parameter penilaian SP. Keduanya dikerjakan berikutnya, dan halangan itulah yang lebih dulu dibereskan.
  * **Fase 4 dan 5 dikerjakan bersama**, sebab keduanya menyentuh berkas yang sama. Empat jenis ditambahkan sehingga totalnya menjadi 14 daftar dan 68 nilai: `jenis_infrastruktur`, `jenis_fasilitas`, `bidang_pengaduan`, dan `kategori_pengaduan`.
  * **`jenis_rujukan` menjadi `referensi_id`**, satu-satunya kolom yang menyimpan id, bukan teks. Alasannya justru dampaknya: daftar lain hanya menampilkan teksnya kembali, sedangkan dua daftar ini menentukan hasil perhitungan. Kalau Admin memperbaiki ejaan `Air` menjadi `Air Bersih`, rujukan berbasis teks putus tanpa pesan apa pun, dan parameter `air_bersih` diam-diam menilai SETIAP SP sebagai tidak punya air. Karena itu parameter primer, status seluruh SP jatuh menjadi Perlu Perhatian gara-gara satu penyuntingan ejaan.
  * **Rujukan yang hilang membuat parameter DILEWATI, bukan dinilai nol.** Ini keputusan tersendiri yang perlu dicatat: menilainya nol berarti satu baris referensi yang hilang menjatuhkan status setiap SP, dan pada parameter primer akibatnya seketika. Melewatinya membuat SP dinilai dari parameter yang tersisa, yang keliru tetapi tidak menyesatkan.
  * **`BidangPengaduan::dariKategori()` tidak lagi berupa `match`.** Inilah yang sejak awal menghalangi kategori menjadi data master, dan sudah dicatat saat Fase 1: `match` itu memuat dua belas kategori tanpa `default`, sehingga kategori baru yang ditambahkan Admin akan melempar `UnhandledMatchError` begitu ada yang memilihnya, dan form pengaduan mati total. Sekarang petanya dibaca dari `referensi.bidang_id`.
  * **NULL pada `bidang_id` BERMAKNA, bukan kosong.** Ia menyatakan kategori yang dapat jatuh ke dua dinas sekaligus dan wajib ditimbang petugas. Karena itu di tabel master ia ditulis "Ditetapkan petugas", bukan tanda hubung: tanda hubung membuatnya tampak seperti data yang lupa diisi, dan Admin berikutnya akan "membetulkannya".

  **Cacat yang ditemukan saat mengerjakan, dan bukan bagian dari rencana:**
  * **Delapan dropdown FILTER memakai daftar nilai aktif saja** - dan itu cacat yang saya sendiri bawa masuk pada Fase 2. Dropdown form menawarkan pilihan untuk data BARU, sehingga nilai nonaktif memang tidak boleh ikut. Tetapi dropdown filter menyaring data LAMA, dan data lama masih memakai nilai yang kini nonaktif. Akibatnya baris-baris itu **tidak dapat dicari sama sekali**: nilainya ada di kolom, tetapi tidak ada pilihan yang cocok untuk memanggilnya. Ditambahkan `opsiFilterReferensi()` dan sepuluh filter dialihkan.
  * Cacat itu **tidak terlihat sampai ada nilai yang dinonaktifkan**, dan kebetulan hanya satu nilai contoh yang nonaktif. Uji penjaganya karena itu membaca berkas view: setiap dropdown yang idnya berawalan `filter_` wajib memakai daftar lengkap.
  * **Tiga enum tertinggal di view** dan lolos dari uji Fase 2, sebab daftar enum pada ujinya ditulis tangan dan belum memuat lima jenis baru: filter jenis infrastruktur, dropdown ubah bidang pada rincian pengaduan, dan form pengaduan publik. Daftarnya dilengkapi.
  * **Tabel kategori pada `rules.md` 10b poin 7a melewatkan "Kelompok Tani"**, padahal kodenya sejak dulu memetakannya ke Pertanian. Ketahuan hanya karena petanya kini dibandingkan langsung dengan data.

  * Dijaga **6 uji baru** (total 14 pada berkas ini), dibuktikan lewat tiga mutasi: mengembalikan satu parameter ke teks, memberi bidang pada kategori netral, dan mengembalikan satu filter ke daftar aktif.
  * **`StatusTinggal` tetap ditunda** menunggu rincian revisi darimu.
  * `status_tinggal` ditunda atas permintaanmu, menunggu butir revisi yang lebih rinci.
- [done] kan mayoritas pada form tambah data ada kolom catatan, cek apakah pada halaman detail data sudah menyediakan field untuk menampilkan catatan tersebut. Oh iya, semua catatan itu misal diubah/diupdate, maka data sebelumnya masih ada ya, bukan ditimpa.
  * **Lima halaman rincian tidak menampilkannya**: alsintan, infrastruktur, saprotan, poktan, dan pengguna. Keempat yang pertama punya kolom `keterangan` di kamus data, jadi catatannya memang dapat diketik tetapi **tidak pernah terbaca kembali** � sama saja dengan tidak dicatat.
  * Kelimanya kini menampilkan catatan beserta berkasnya. Keadaan kosong dinyatakan apa adanya ("Tidak ada catatan tambahan."), bukan disembunyikan, sebab bagian yang lenyap terbaca sebagai data gagal termuat.
  * **Soal catatan lama: benar, tidak tertimpa.** `audit_log` menyimpan `data_lama` dan `data_baru` per kolom yang berubah (kamus data 2.2), sehingga isi catatan sebelum penyuntingan tetap tersimpan dan dapat ditelusuri lewat tab Catatan Log. Keterangan itu kini ditulis di layar, bukan hanya di dokumen.
  * **UTANG TAHAP 4 YANG PERLU DIINGAT.** Komponen `catatan-log` merender `$jejak['ringkasan']`, padahal tabel `audit_log` **tidak punya kolom bernama itu** � yang ada `data_lama`/`data_baru`. Pada Tahap 2 hal ini tidak terlihat sebab `DummyData::auditLog()` mengarang kuncinya. Begitu backend masuk, tampilan wajib dibangun dari pasangan nilai lama dan baru; bila tidak, tab log pada **dua belas halaman rincian** akan kosong atau melempar galat. Ditulis sebagai peringatan di kepala komponennya.
- [done] Aku cek ada beberapa form tambah/ubah data yg belum dikasih kolom catatan. Coba cek pada form di halaman apa saja yg belum ada. Sekalian ubah penamaannya agar seragam semua ? Catatan/Keterangan/jika ada penamaan yg mirip, di-rename jadi Catatan. Agar semuanya seragam.
  * **Tujuh form tidak punya isian catatan sama sekali**, dan empat di antaranya justru punya kolomnya di kamus data: alsintan (8.3), infrastruktur (10.1), poktan (8.1), saprotan (8.4). Keempatnya kini punya. Tiga sisanya memang tidak punya kolom catatan: master satuan, master wilayah, dan pengguna.
  * **Empat penamaan berbeda dipakai bergantian** untuk satu maksud yang sama: Keterangan, Catatan, Catatan Hunian, dan Keterangan Satuan Lokal. Diseragamkan menjadi **Catatan** pada sembilan form. Kolom databasenya tetap `keterangan` mengikuti kamus data; yang diseragamkan adalah teks yang dibaca petugas.
  * **Tiga pengecualian sengaja dipertahankan** (menjadi DUA sejak 2026-08-22, `keterangan_satuan_lokal` dicabut seluruhnya), sebab maknanya memang berbeda dan menyamakannya justru menyesatkan: `rumah.catatan_hunian` ("Catatan Hunian", kolomnya memang bernama demikian), `hasil_panen.keterangan_satuan_lokal` ("Keterangan Satuan Lokal", kolom tersendiri di samping `keterangan`), dan `pengaduan.deskripsi` ("Uraian Masalah", isi laporan yang wajib diisi bukan catatan tambahan).
  * **Dua cacat kecil ikut diperbaiki.** Label pada form lahan berkelas `sr-only` sehingga tidak tampak, dan form SP **sama sekali tanpa `<label>`** � satu-satunya isian catatan yang demikian, sehingga pembaca layar hanya mengumumkan sebuah kotak teks tanpa memberi tahu isinya apa.
  * Dijaga tiga uji berbasis daftar: keberadaan isian pada 14 form, keseragaman labelnya, dan tampilannya pada 8 halaman rincian. Aturannya ditulis pada `ui-spec.md` 6.4a beserta ketiga pengecualiannya.
- [done] Ini kita mau diskusi. Misal ada suatu kasus di mana kepala keluarga (suami) meninggal, kan admin bakal update datanya dan mengubah agar yg jadi kepala keluarga itu istrinya. Namun gimana agar system mencatat perubahan tersebut secara detail ya? Apakah via log atau bagaimana?
  * **Jawabannya: audit log tidak cukup, dan sebabnya satu kalimat.** Audit log memang merekam bahwa `nama_kepala_keluarga` berubah, tetapi ia **tidak dapat membedakan suksesi dari perbaikan salah ketik** � keduanya berbentuk aksi `Ubah` pada kolom yang sama. Data contoh audit log sendiri sudah memuat contoh yang kedua: *"Memperbaiki ejaan nama YOHANES BERE"*. Dibuat tabel `riwayat_kepala_keluarga`.
  * **Barisnya disunting, bukan diganti baris baru.** Satu baris `transmigran` adalah satu **rumah tangga**, bukan satu orang, sehingga rumah tangganya berlanjut dan yang berganti kepalanya. Alasannya bukan kepraktisan: jatah rumah dan lahan diberikan kepada **KK**, bukan kepada suaminya secara pribadi, sehingga ketujuh relasi yang menaut ke `transmigran` memang seharusnya tetap utuh. Jalan sebaliknya menuntut melepas UNIQUE `no_kk`, memindahkan tujuh FK manual (dua di antaranya ber-RESTRICT sehingga justru memblokir), dan membuat setiap hitungan "jumlah KK" pada dashboard menghitung ganda.
  * **Suksesi adalah tindakan tersendiri, bukan efek samping form ubah.** Tombol dan modal terpisah, rute `POST` tersendiri. Bila ia lahir dari penyuntingan nama pada form biasa, setiap perbaikan ejaan akan mengotori riwayat suksesi � persis kekaburan yang tabel ini dibuat untuk menutupnya. Halaman rumah memakai pola efek-samping, dan **sengaja tidak diikuti** di sini.
  * **Kedua sisi identitas disimpan**, bukan hanya yang lama. Merangkai nama pengganti dari baris berikutnya menghemat tiga kolom tetapi menukarnya dengan kueri berantai yang rapuh dan riwayat yang tidak dapat dibaca berdiri sendiri.
  * **Nomor KK ikut disimpan dua sisi**, sebab Dukcapil menerbitkan KK baru ketika kepala keluarganya berganti (dikonfirmasi pemilik proyek). Bila tidak berubah, keduanya diisi sama, dan tampilannya menulis "tidak berubah" alih-alih dua nomor identik yang membuat pembaca menduga ada perubahan yang tidak ada.
  * **Urutan pengganti tidak ditegakkan sistem.** Aturan istri lalu anak pertama adalah ketentuan Dukcapil; sistem tidak mendata anggota keluarga satu per satu (`erd.md` 7.4) sehingga tidak punya baris untuk memvalidasinya. Identitas pengganti diketik petugas: sistem merekam siapa penggantinya, **bukan menebaknya**.

  **Penyisiran skenario (`rules.md` 20a) menemukan dua cacat, dan keduanya berlawanan arah:**
  * **Jabatan ketua poktan TIDAK boleh diwariskan.** Tanpa penjaga, menyunting baris transmigran membuat istri **otomatis menjadi ketua poktan** tanpa seorang pun memutuskan, padahal ketua dipilih anggota. Modal karena itu menuntut petugas memilih: kosongkan jabatan, atau teruskan. Pilihan itu `required`, dan hanya dirender bila keluarga tersebut memang menjabat � kontrol yang tidak menentukan apa pun adalah kontrol mati (R-26).
  * **Keanggotaan poktan JUSTRU mengikuti, dan itu benar.** Sejak Fase A keanggotaan melekat pada keluarga, bukan pada kepala keluarganya, sehingga petugas cukup **diberi tahu**, tidak diminta memutuskan. Perbedaan perlakuan inilah hasil penyisirannya: satu perlu keputusan, satu tidak.
  * **Ketua berjalur `Anggota Keluarga` tidak ikut terpengaruh** sama sekali, sebab ia punya nama dan NIK tersendiri. Pemeriksaannya karena itu menyaring `asal_ketua === Kepala Keluarga`, bukan sekadar mencocokkan `ketua_transmigran_id`.
  * **Kejujuran angka:** setelah suksesi, `status_tinggal` keluarga tetap `Aktif` sebab istrinya masih hidup dan menempati rumah yang sama. Akibatnya rekap "Meninggal" pada status tinggal hanya menghitung **keluarga yang bubar**, bukan orang yang meninggal. Ditulis eksplisit pada kamus data 11.36 agar tidak terbaca keliru; angka kematian sesungguhnya dihitung dari tabel riwayat ini. Pemilik proyek sudah menyatakan nilai `Meninggal` pada status tinggal akan dicabut pada revisi berikutnya.
  * **Privasi:** riwayat memuat NIK lama dan baru, tetapi hanya hidup sebagai tab pada rincian transmigran yang sudah tersaring cakupan data. Tidak ditampilkan pada rekap kependudukan mana pun � nama pasangan almarhum bukan angka agregat.
  * **Alasan pergantian adalah enum tersendiri**, bukan berbagi dengan `StatusTinggal`. Keduanya menjawab pertanyaan berbeda: yang satu keadaan terkini sebuah keluarga, yang lain peristiwa bertanggal. `Pindah atau Merantau` sengaja tidak dipecah dua, sebab membedakannya menuntut petugas menilai niat kepergian dan itu tidak dapat diverifikasi.

  * Kewenangan menjadi fitur ke-27: **95 kewenangan menjadi 98**, per role 98/45/46/50. **Tanpa hapus bagi siapa pun termasuk Admin**, sebab riwayat suksesi menyatakan siapa pemegang jatah lahan pada rentang waktu tertentu. Admin tetap memegang `ubah` untuk membetulkan salah ketik; tanpa itu petugas akan mencatat suksesi kedua sebagai penebus kekeliruan yang pertama.
  * Tab riwayat disisipkan **sebelum** Catatan Log, sebab log wajib tetap paling kanan (`ui-spec.md` 5.1c). Disajikan sebagai garis waktu mengikuti riwayat penghunian.
  * Data contoh memuat dua suksesi yang sengaja berbeda cabang: keluarga 6 (`YAKOBUS BRIA` meninggal, nomor KK berganti) dan keluarga 4 (`LUKAS SERAN` merantau, nomor KK tetap). Dijaga uji yang menuntut sisi baru riwayat **selalu cocok** dengan data transmigran terkini, sebab riwayat yang tidak selaras akan bertentangan dengan kartu profil di halaman yang sama.
  * Dijaga **9 uji Pest baru** dan `tests/Browser/uji-suksesi-kk.mjs` berisi **19 pemeriksaan perilaku**. Dibuktikan lewat mutasi: menghapus penyaring jalur ketua memerahkan uji Pest, dan mencabut `required` pada pilihan nasib jabatan memerahkan uji peramban.
  * **Tersisa untuk Tahap 5:** rutenya masih stub. Saat backend masuk, ketiga langkah wajib satu transaksi � sunting baris transmigran, tambah baris riwayat, terapkan pilihan nasib jabatan ketua.
- [done] Bagian alur yg ada di tiap halaman (contoh "Beranda/Dashboard"), tolong seragamkan dengan format seperti ini: [Beranda/{nama_menu}/{sub_menu}/{detail_data}/ dan seterusnya]. Contoh: Beranda/Wilayah & SP/Infrastruktur/Sumur Bor.
  * **Remah kini dibaca otomatis dari `MenuHelper`** lewat `RemahHelper::untuk()`, bukan ditulis tangan per halaman. Dipasang pada **42 halaman**.
  * **Ketidakseragamannya ternyata total: tidak satu pun ruas pertama cocok dengan menu yang benar-benar dipakai.** Transmigran menulis "Kependudukan" padahal menunya "Penduduk & Lahan"; poktan menulis "Kelembagaan" padahal menunya "Poktan & Sarana"; lahan menulis "Lahan" padahal ia berada di bawah "Penduduk & Lahan". Yang paling telak: halaman **daftar** inventaris menulis "Wilayah dan SP" sedangkan halaman **rinciannya sendiri** menulis "Wilayah dan Aset SP" � dua nama berbeda untuk satu modul, pada dua halaman yang saling bertautan.
  * **Remah yang tidak sejalan dengan menu lebih buruk daripada tidak ada remah sama sekali**, sebab ia menyatakan pengguna berada pada cabang yang tidak pernah ia lewati. Menulisnya tangan berarti setiap halaman baru berpeluang mengarang nama baru lagi, dan itulah yang terjadi berulang kali.
  * Bentuknya **tiga ruas** sesuai permintaan: `Beranda / {submenu} / {halaman} / {rincian}`. Judul kelompok menu seperti "Transmigrasi" sengaja tidak diikutkan; empat ruas terlalu panjang pada layar sempit, dan yang benar-benar menolong adalah nama submenu tempat halaman itu hidup. Hasilnya persis contoh yang diminta: `Beranda / Wilayah & Aset SP / Infrastruktur SP / SALURAN IRIGASI BLOK A`.
  * Dua penyimpangan sengaja dipertahankan beserta alasannya: **dashboard SP** menempel pada Dashboard bukan pada menu Satuan Permukiman, sebab ia menyajikan rekap kawasan per SP bukan data SP-nya; dan **halaman di luar sidebar** (profil, galeri komponen) labelnya disusun dari alamatnya sendiri, sebab remah kosong menghilangkan penunjuk posisi sama sekali.
  * Dijaga 4 uji: satu menyisir seluruh berkas dan memerah bila ada yang kembali menulis remah tangan, satu membandingkan langsung terhadap `MenuHelper` sehingga penggantian nama menu terbaca tanpa menyunting uji. Mutasi membuktikan keduanya: mengembalikan satu halaman ke remah tangan, dan mengganti nama submenu di `MenuHelper`, masing-masing memerahkan ujinya.
- [done] Di form c/u (create/update) data lahan terdapat status hak atas tanah dan jenis dokumen. Field form status hak atas tanah, nomor dokumen, dan tanggal terbit hapus saja. Untuk opsi jenis dokumen hanya HPL dan SHM. Perbaiki ulang juga tampilan dari form c/u data lahan setelah ada beberapa form yg dihapus sehingga enak dilihat.
  * **[done] Dikerjakan bersama pemecahan komposisi luas**, sebab keduanya membongkar form yang sama. Membongkarnya dua kali berarti merapikan tata letak dua kali pula.
  * **Status hak atas tanah dicabut MENYELURUH**, bukan hanya isiannya: enum `StatusHakLahan` dihapus, kolom `status_hak` dicabut dari kamus data 7.1 dan aturan `rules.md` 7.4a, 6 nilai pada data contoh dibuang, dan keterangan "Status kepemilikan" pada halaman rincian ikut hilang. Form adalah **satu-satunya** jalan mengisi kolom itu; menyisakan tampilannya akan membuat keterangan yang tidak pernah terisi, kontrol mati yang dilarang R-26. Pola sama dengan pencabutan batas wilayah SP pada butir pertama bagian ini.
  * **Nomor dokumen dan tanggal terbit hanya dicabut dari form lahan**, kolomnya tetap hidup. Keduanya masih terisi lewat modal Tambah Dokumen Lahan di halaman rincian, sebab keduanya memang keterangan **per dokumen**, bukan per bidang. Ini yang membedakannya dari status hak.
  * `JenisDokumenLahan` **6 nilai menjadi 2**: `Surat Keterangan Pembagian Tanah`, `SKT`, `Surat Keterangan Desa`, dan `Lainnya` dibuang. Sifat sertifikasi berlapis **tetap terwakili** dan tabel `dokumen_lahan` tetap terpisah: HPL adalah alas hak kawasan yang terbit lebih dulu, SHM menyusul bertahun kemudian. Alasan pemisahan pada bagian 1c.2 pelanggaran kedua tidak ikut gugur.
  * Tata letak dirapikan: blok dokumen dari tiga isian berjajar menjadi satu, dan area unggah tetap di baris penuh.

- [done] **Komposisi luas lahan menggantikan kategori lahan** (dari keterangan pemilik proyek, 2026-08-20)
  * **Kekeliruan yang ditemukan:** `lahan.kategori_lahan` adalah enum **satu nilai per bidang** (`Lahan Basah` atau `Lahan Kering`), sehingga satu bidang hanya boleh bersifat salah satu. Keterangan pemilik proyek menyatakan sebaliknya: satu lahan usaha 1 ha dapat digarap **0,5 ha kering dan 0,5 ha basah sekaligus**, dan pembagiannya ditentukan penggarapnya.
  * **Akibatnya bukan sekadar ketidaklengkapan.** Bidang campuran tidak dapat dicatat sama sekali, sehingga petugas terpaksa memilih salah satu dan **separuh luasnya hilang dari rekap tanpa ada yang menyadarinya**. Kegagalan senyap yang persis pernah terjadi pada penjumlahan luas lahan usaha (butir 2026-08-18).
  * **Keputusan:** enum dicabut, digantikan dua kolom `luas_kering` dan `luas_basah` yang jumlahnya wajib sama dengan `luas`. Dipilih dua kolom, **bukan tabel `komposisi_lahan`**, sebab kategorinya tetap dua dan tidak bertambah; tabel terpisah hanya menambah join tanpa menambah kemampuan.
  * **Bidangnya tetap satu baris dengan satu titik koordinat.** Yang dipecah hanya angka luasnya, sebab pemecahan kering/basah tidak melahirkan bidang baru dan tidak berpindah tempat. Ini yang membedakannya dari memecah bidang menjadi dua baris.
  * **Aturan jumlah ditegakkan dengan menurunkan, bukan memvalidasi.** Total lahan usaha dihitung Alpine dari kedua bagiannya dan tidak dapat disunting, sehingga petugas **tidak mungkin** memasukkan angka yang jumlahnya keliru dan tidak ada pesan galat yang perlu ditulis. Lahan pekarangan tidak berkomposisi, luasnya diketik langsung.
  * Nilai total dikirim lewat isian ber-`name` berkelas `sr-only`, **bukan `type="hidden"`**, mengikuti keputusan yang sama pada `pilih-cari` (`ui-spec.md` 6.0a): peramban mengabaikan `required` pada isian tersembunyi.
  * **Makna penyaring berubah, dan itu disengaja.** "Ada lahan basah" berarti `luas_basah > 0`, yaitu bidang yang **memiliki bagian** basah, bukan yang seluruhnya basah. Bidang campuran karena itu muncul pada kedua penyaring sekaligus. Nilainya `kering`/`basah`, bukan nama enum lama, agar tautan lama tidak diam-diam cocok dan menyaring keliru.
  * **Satu uji lama ternyata lolos secara kebetulan.** `HalamanTest` baris 381 menyaring `?kategori_lahan=Lahan Basah` lalu memeriksa `LU-002` muncul. Nilai yang tidak dikenal membuat penyaringnya **diabaikan**, sehingga seluruh baris tetap muncul termasuk yang dicari. Ditambahkan `assertDontSee` agar penyaringnya benar-benar teruji.
  * **`transmigran_id` ditambahkan pada data contoh lahan.** Sebelumnya lahan menaut ke pemiliknya lewat **pencocokan nama** di tiga view. Dua kepala keluarga dapat bernama sama, dan pencocokan nama akan menautkan bidang ke profil orang yang keliru. Perbaikan ini prasyarat rekap luas per keluarga pada pekerjaan poktan berikutnya.
  * Satu bidang campuran sengaja ada pada data contoh (`LU-003`, 1,25 ha kering + 0,75 ha basah), dijaga uji tersendiri: tanpa satu pun contohnya, keadaan yang menjadi **seluruh alasan** pemecahan kolom ini tidak pernah teruji.
  * **Diuji di peramban sungguhan**, sebab total adalah nilai turunan yang tidak dapat dibuktikan uji string. `tests/Browser/uji-komposisi-lahan.mjs`, 9 pemeriksaan lewat Edge headless tanpa menambah dependensi. Nilainya dibuktikan lewat mutasi: menghapus bagian basah dari perhitungan langsung memerahkan 3 pemeriksaan, sedangkan seluruh 439 uji Pest tetap hijau.
  * Kolom `kategori_lahan` pada tampilan diganti "Komposisi" bertulis `1,25 K / 0,75 B`, dan kartu statistik daftar lahan menjadi enam: dua peruntukan, dua komposisi lahan usaha, total bidang, dan total luas. Keduanya diberi label "Lahan Usaha" agar tidak terbaca sebagai bagian dari total seluruh lahan.
  * Nomor bagian `data-dictionary.md` 11.12 sengaja **tidak dipakai ulang**, agar rujukan lama pada dokumen dan riwayat tetap dapat ditelusuri.

- [done] **Wakil keluarga di poktan: keanggotaan melekat pada keluarga, bukan pada kepala keluarga** (dari keterangan pemilik proyek, 2026-08-20)
  * **Keadaan lapangannya:** yang terdaftar di poktan adalah orang yang benar-benar menggarap dan menghadiri pertemuan, dan ia tidak selalu kepala keluarga. Bila kepala keluarga merantau, istri atau anaknya yang mewakili. Kalimat pemilik proyek yang menentukan: *"tautan ke data transmigran-nya berupa KK, bukan NIK kepala keluarga"*.
  * **Sebelumnya mustahil didata.** `anggota_poktan` hanya punya `transmigran_id` tanpa kolom identitas sendiri, dan catatan kamus datanya berbunyi *"nama dan NIK anggota dibaca lewat relasi, tidak disalin"*. Padahal `transmigran` adalah satu baris per kepala keluarga. Pilihan petugas dua-duanya buruk: membiarkan nama kepala keluarga yang tidak ada di kawasan, atau tidak mendaftarkan keluarga itu sama sekali dan menutup aksesnya ke saprotan.
  * **Boolean digantikan enum tiga nilai.** `poktan.is_ketua_transmigran` ? `asal_ketua` bertipe `AsalWakilPoktan`: `Kepala Keluarga`, `Anggota Keluarga`, `Bukan Transmigran`. Boolean hanya sanggup membedakan dua keadaan, sedangkan keadaan lapangan ada tiga. Enum yang sama dipakai `anggota_poktan.asal_wakil`, dengan nilai ketiga dilarang di tingkat aplikasi sebab seluruh anggota wajib berasal dari keluarga transmigran.
  * **Dua penurunan sengaja dibedakan, dan menyatukannya adalah kekeliruan yang membuat jalur kedua mustahil dilayani.** `identitasDariRelasi()` menjawab "apakah nama dan NIK dapat dibaca lewat relasi" � hanya kepala keluarga. `dariKeluargaTransmigran()` menjawab "apakah luas lahan dapat dibaca dari bidang keluarga" � dua jalur pertama. Jalur `Anggota Keluarga` bernilai **beda** pada keduanya: identitasnya diketik, tetapi lahannya tetap terbaca.
  * **`transmigran_id` tetap terisi pada jalur kedua**, sebab yang ditunjuk adalah **keluarga** yang diwakili, bukan orangnya. Inilah yang membuat luas lahan tetap terbaca meski wakilnya tidak punya baris sendiri.
  * **Luas lahan dan koordinat diturunkan, bukan disimpan.** `DummyData::rekapLahanKeluarga()` menjumlahkan bidang lahan usaha milik keluarga. Menyimpannya sebagai kolom akan basi begitu petugas membetulkan luas di modul lahan � kekeliruan yang sama dengan `jumlah_anggota` yang sudah dicabut (`erd.md` 7.3). Satu-satunya pengecualian ketua `Bukan Transmigran`, yang lahannya memang tidak terdata sehingga wajib diketik.
  * **`poktan.luas_lahan_kelompok` ikut dihapus.** Ternyata kolom mati: **0 pemakaian di seluruh repo**, tidak ada isian, tidak ada tampilan, tidak ada uji, dan `DummyData::poktan()` bahkan tidak memuat kuncinya. Luas kelompok kini dijumlahkan dari lahan seluruh anggotanya, tampil sebagai baris total pada tabel anggota.
  * **`alasan_keluar` dipisahkan dari `keterangan`.** Kolom `keterangan` dipakai dua maksud sekaligus: kamus data menyebutnya catatan umum, sedangkan form melabelinya "Alasan Keluar", sehingga catatan keanggotaan biasa **tidak punya tempat sama sekali**. Pemisahannya mengikuti `riwayat_penghunian` �6.3 yang sudah membedakan keduanya sejak awal.
  * **Kardinalitas tidak berubah, dan itu kebetulan yang menguntungkan.** Satu wakil per keluarga per poktan, dan satu keluarga satu poktan � keduanya **sudah** ditegakkan UNIQUE `(poktan_id, transmigran_id)` yang ada, begitu `transmigran_id` bermakna keluarga. `rules.md` 7a.4d hanya perlu diperjelas kata-katanya dari "seorang transmigran" menjadi "satu keluarga", bukan diubah logikanya.
  * Data contoh diperluas agar ketiga cabang terlihat: POKTAN TANI BERSATU berketua istri (`YOVITA NAHAK`, mewakili keluarga `PETRUS NAHAK`), POKTAN HARAPAN BARU berketua penduduk setempat, dan satu anggota diwakili anak (`ANDREAS HOAR`, keluarga `YULITA HOAR`).

  **DUA CACAT LAMA DITEMUKAN SAAT PENGUJIAN PERAMBAN, keduanya berlangsung diam-diam sejak 2026-08-17:**
  * **`x-sim.pilih-cari` tidak pernah meneruskan `:required` dan `:disabled`.** Komponen membacanya lewat `$attributes->get(':required')`, padahal **Blade memperlakukan `:nama` sebagai atribut TERIKAT**: nilainya dievaluasi sebagai PHP lalu disimpan pada kunci `required` **tanpa titik dua**. Pembacaannya karena itu selalu menghasilkan null. Akibatnya nyata: isian pada cabang form yang sedang tersembunyi **tetap aktif dan ikut terkirim**, sehingga peladen menerima dua sumber identitas yang bertentangan.
  * **`@change` milik pemanggil hilang seluruhnya.** Komponen tidak pernah memanggil `$attributes->merge()` pada elemen mana pun, dan isian nilainya sendiri sudah memakai `@change="selaraskan()"`. Akibatnya **autofill telepon ketua poktan tidak pernah bekerja sejak ditulis**, padahal `rules.md` 7a.2b menjanjikannya dan satu uji Pest "menjaganya".
  * **Keduanya lolos dari 443 uji hijau** sebab seluruh uji yang menyentuhnya hanya memeriksa keberadaan atribut `name` di HTML � persis kekeliruan yang sudah tercatat pada bagian 1d.2 dan butir b799, dan terulang untuk ketiga kalinya. Ditemukan hanya karena uji peramban menanyakan **apakah isiannya benar-benar nonaktif**, bukan apakah atributnya tertulis.

  * Dijaga **5 uji Pest baru** dan `tests/Browser/uji-wakil-poktan.mjs` berisi **20 pemeriksaan perilaku** lewat Edge headless tanpa dependensi. Nilainya dibuktikan lewat mutasi: mengembalikan pembacaan atribut ke bentuk lama langsung memerahkan uji Pest penjaga sekaligus satu pemeriksaan peramban, dan membuat rekap lahan ikut menghitung pekarangan memerahkan uji penurunan luas.
  * **Satu uji lama ikut diperbaiki.** Penjaga isian wajib memakai pola `name="..."[^>]*required`, dan pembatas `[^>]*` **putus** oleh tanda `>` di dalam nilai atribut Blade seperti `{{ $asal->value }}`. Uji melaporkan isian yang sebenarnya sudah bertanda wajib. Diganti jendela 200 karakter.
- [done] Kolom export pada tabel kewenangan per fitur dihapus saja, karena kosong juga
  * **Kolomnya memang kosong, dan itu gejala, bukan penyebabnya.** Kewenangan `export` sudah dicabut 2026-08-17 dari enum `AksiPermission`, dari `daftarIzin()`, dari `izinRole()`, dan dari matriks `rules.md` 5.1. Tetapi `form-role.blade.php` **menyalin daftar aksi itu dengan tangan**, dan salinannya tidak tahu sumbernya sudah berubah. Kolom `export` bertahan tiga hari, kosong pada seluruh 28 fitur.
  * **Tidak ada satu pun uji yang memerah**, padahal `HalamanTest` sudah punya uji khusus berjudul "mencabut kewenangan export dari seluruh sumber kebenaran". Uji itu menyisir empat sumber � enum, `daftarIzin()`, `izinRole()`, dan `rules.md` � lalu berhenti tepat sebelum sumber kelima, yaitu tampilan. Kolom kosong tidak membuat apa pun tampak rusak, sehingga tidak ada yang mencarinya.
  * Perbaikannya bukan menghapus satu baris, melainkan **menghapus salinannya**: ditambahkan `AksiPermission::opsi()` dan view membaca dari sana. Daftar tulis-tangan yang menggandakan enum akan basi lagi pada pencabutan berikutnya, dan tidak ada alasan menyimpan dua daftar untuk satu kebenaran.
  * Ikut ditemukan pada halaman yang sama: kalimat pengantar `role.blade.php` masih berbunyi "melihat, menambah, mengubah, menghapus, **atau mengekspor** data", menjanjikan kewenangan yang sudah tidak ada kepada Admin yang membacanya.
  * Uji "mencabut kewenangan export" diperluas ke sumber kelima, dan dibuktikan dengan mengembalikan kolomnya � uji memerah.
  * **Ditambahkan `pint.json` yang mengecualikan `routes/`.** Tidak berkaitan dengan butir ini, tetapi ditemukan pada kesempatan yang sama: tanpa berkas konfigurasi, Pint memformat ulang `routes/web.php` seluruhnya setiap kali dijalankan � BOM, urutan import, spasi concat � sehingga diff-nya membengkak dan rute yang baru ditambahkan sempat hilang saat dipulihkan. Ini sudah tercatat sebagai jebakan berulang, dan sekarang penyebabnya dihapus, bukan dihindari.

- [done] data master referensi apakah memungkinkan untuk dibuatkan per menu gitu? Jadi bukan per-tab hingga Panjang banget gitu sehingga user mana pun juga bakal bingung.
  * **Kekhawatiranmu terbukti, dan angkanya lebih buruk dari dugaan.** Diukur di peramban pada halaman yang berjalan: bar tab mencapai **2309px pada ruang 705px**, sehingga hanya **4 dari 14 tab yang terlihat** dan **10 tersembunyi** di balik gulir mendatar. Tidak ada yang tampak rusak, sebab keempat belas tab tetap ada di HTML dan `overflow-x-auto` bekerja persis seperti seharusnya.
  * **Tetapi "per menu" tidak dikerjakan seperti yang diminta**, dan alasannya perlu dicatat. Menu Pengaturan Sistem sudah berisi 6 butir; menambah 14 lagi menjadikannya 20 butir dalam satu menu yang ikut menggulir. Itu memindahkan baris panjang yang sama dari bar tab ke bilah sisi, bukan menghilangkannya. Bilah sisi juga hanya mendukung dua tingkat, jadi tidak ada tempat menaruh pengelompokan.
  * Yang dipilih: **halaman indeks berkartu + satu halaman per daftar**. Menu tetap 1 butir, tetapi seluruh 14 daftar terlihat sekaligus sebagai kartu berkelompok, dan tiap daftar punya alamatnya sendiri di `/master/referensi/{jenis}`.
  * **Dikelompokkan per modul yang memakainya**, bukan per kemiripan bentuk: Aset & Infrastruktur (5), Rumah & Lahan (3), Pertanian (3), Pengaduan (3). Petugas mencari `jenis_fasilitas` karena sedang mengurus aset SP, bukan karena ingat isinya sembilan baris.
  * Label dibiarkan apa adanya sesuai permintaanmu.  * **Keterangan akibat menyunting pindah ke halaman daftarnya**, tidak ikut di indeks. Peringatan bahwa skor menentukan penilaian SP tidak berguna bagi orang yang sedang memilih daftar mana yang mau dibuka. Yang tetap tampil di indeks hanya lencana singkat penanda daftar berdampak.
  * **Isian jenis dikunci ke halaman**, dikirim sebagai `<input type="hidden">`. Sebelumnya dropdown berisi keempat belas daftar, dan itu wajar ketika semuanya di satu halaman; kini halamannya sendiri sudah menyatakan daftarnya, dan membiarkannya dapat diganti berarti nilai baru bisa mendarat di daftar lain tanpa disadari.
  * Alamat lama `?tab={jenis}` **dialihkan 301**, tidak dibiarkan mati. Jenis karangan membalas **404**, bukan halaman kosong: daftar yang tidak ada dan daftar yang kebetulan kosong adalah dua keadaan berbeda, dan menyamakannya membuat salah ketik tampak seperti data yang belum diisi.
  * Ditambahkan **`KelompokReferensi`** sebagai enum, bukan string lepas, dan `JenisReferensi::kelompok()` memakai `match` tanpa `default`. Jenis tanpa kelompok tidak muncul di indeks sama sekali, dan karena indeks satu-satunya jalan menuju halamannya, daftar itu jadi tidak terjangkau tanpa mengetik alamatnya. Dibuktikan lewat mutasi: menambah jenis baru tanpa mengelompokkannya melempar `UnhandledMatchError` seketika.
  * Hasilnya diukur ulang: **14 dari 14 kartu terlihat, tanpa gulir mendatar**.  * **Uji peramban baru** `tests/Browser/uji-master-referensi.mjs` (10 pemeriksaan), sebab alasan perubahan ini adalah angka yang diukur di peramban. Uji string tidak dapat melihatnya: keempat belas judul tetap ada di HTML meski hanya empat yang terlihat.
  * **Uji itu sempat salah, dan mutasi yang menyingkapnya.** Versi pertama memeriksa "terlihat" hanya lewat lebar dan tinggi. Saat kartu sengaja dikembalikan menjadi baris bergulir, uji tetap hijau pada butir itu: elemen yang terdorong ke luar batas mendatar tetap melaporkan ukuran seperti biasa. Diperbaiki menjadi memeriksa elemen benar-benar berada di dalam batas layar, dan mutasi yang sama kini memerah.

- [done] Tolong "Wilayah & Aset SP" di-rename jadi "Wilayah & SP".
  * Diubah di `MenuHelper` beserta komentarnya, `HalamanTest`, dan `ui-spec.md`. Breadcrumb ikut dengan sendirinya, sebab `RemahHelper` membacanya dari `MenuHelper`.
  * `notes.md` **sengaja tidak disunting**: catatan lama merekam keputusan pada saat itu, dan menyelaraskannya dengan keadaan sekarang menghapus jejak alasannya.
- [done] breadcrumb pada halaman detail SP kenapa masih "Beranda / Dashboard / SP Kapitan Meo"? Bukankah seharusnya "Beranda / Wilayah & Aset SP / Satuan Permukiman / SP Kapitan Meo"?
  * **Benar, itu cacat.** Penyebabnya `pages/dashboard/sp.blade.php` memanggil `RemahHelper::untuk('/', ...)`, dan `/` adalah Dashboard.
  * **Ada komentar yang membenarkannya, dan komentar itu keliru.** Tertulis halaman ini "menyajikan rekap kawasan per SP, bukan data SP-nya". Isinya diperiksa: Profil Satuan Permukiman, lalu transmigran, rumah, lahan, panen, pengaduan, dan infrastruktur **milik SP itu**. Ia halaman rincian SP, dan letaknya di menu mengikuti isinya, bukan mengikuti alamat rutenya.
  * Diperbaiki menjadi `untuk('/sp', ...)`, menghasilkan **Beranda / Wilayah & SP / Satuan Permukiman / SP Kapitan Meo**, dengan "Satuan Permukiman" bertaut kembali ke `/sp` seperti seluruh halaman rincian lain.
  * **Alamat `/dashboard/sp/{id}` sengaja dibiarkan.** 21 tempat menautkannya, dan memindahkannya ke `/sp/{id}` berisiko tertangkap rute `/sp/inventaris` serta `/sp/fasilitas` yang sudah ada, persis jebakan yang sudah tercatat di `routes/web.php`. Yang salah remahnya, bukan alamatnya.
  * **Ditambahkan penjaga umum**, sebab cacat ini lolos justru karena tidak ada yang memeriksanya: tidak ada halaman rincian mana pun yang boleh memakai `RemahHelper::untuk('/', ...)` dengan ruas rincian. Hanya Dashboard sendiri yang berhak menunjuk `/`.

- [done] Aku cek barusan kayaknya belum ada ya fitur untuk set nilai bobot dan set nilai ambang predikat ya? Baru nilai kondisi saja yg sudah ada. Kalau bisa, status mandiri, berkembang, dan perlu penanganan juga bisa di-edit lewat master. Barangkali dinas punya wording tersendiri terkait itu.
  * **Benar, dan ini bukan fitur baru melainkan janji yang belum ditepati.** `data-dictionary.md` 5.4 sudah merancang tabel `parameter_penilaian_sp` lengkap dengan kolom `bobot`, `rules.md` 497 sudah menyatakan bobot dan ambang adalah "keputusan kebijakan, bukan teknis, wajib divalidasi dinas", dan `TingkatKebutuhan::bobotBawaan()` bahkan sudah diberi komentar "hanya bawaan saat penanaman data awal". Antarmukanya saja yang tidak pernah dibuat.
  * Ketiga tuas kini berupa data: nilai kondisi lewat master referensi, bobot dan ambang lewat `/master/penilaian-kondisi`.
  * **Wording status dapat disunting, tetapi jumlahnya tetap tiga.** Enum tetap menjadi kunci perilaku, hanya teks tampilnya yang data, pola yang sama dengan bidang pengaduan. Status keempat tidak dapat ditambah sebab `dariSkor()` hanya mengembalikan tiga keluaran, `penilaian_sp.status` bertipe ENUM SQL, dan `warna()` memakai `match` tanpa `default`. Sudah dibuktikan: mengganti wording menjadi "Prioritas Pembinaan" terbawa ke seluruh tampilan.
  * Warna tidak ikut disunting: hijau, kuning, merah menyatakan urutan keparahan, bukan selera.
  **Pertanyaanmu mengubah rancangan, dan menyingkap cacat yang lebih besar.**
  * Kamu bertanya apakah jenis yang baru ditambahkan Admin otomatis muncul di parameter penilaian. Jawabannya waktu itu **tidak**, dan itu cacat: `parameter()` berupa daftar tulis tangan tiga belas baris. Jenis baru muncul di dropdown, petugas dapat mendata asetnya, tetapi skor SP **tidak berubah sama sekali**.
  * **Itulah sebab Pendidikan Lanjutan, Pasar atau Kios, Olahraga, dan Keamanan tidak pernah dinilai.** Bukan keputusan sadar, hanya daftar yang berhenti di baris ke tiga belas. Korbannya nyata: **POS KAMLING di SP Weain berkondisi Rusak Berat** terdata rapi, tampil di daftar fasilitas, dan tidak menyumbang apa pun pada skornya.
  * Rancangan diubah: **parameter kini dihasilkan dari data master jenis**, sehingga jenis baru muncul sendiri dan tidak ada yang dapat terlewat lagi. `sumber` pun disimpulkan dari jenisnya, tidak diisi manual.
  * **Kotak centang "Dinilai" menjawab pertanyaanmu** tentang mana yang masuk penilaian dan mana yang tidak. `Lainnya` cukup tidak dicentang, tanpa pengecualian khusus di dalam kode.
  * Jenis baru lahir **belum dicentang** atas keputusanmu: menambah jenis itu pendataan, memasukkannya ke penilaian itu kebijakan. Menyatukannya membuat skor seluruh SP turun hanya karena satu pilihan dropdown bertambah.
  * `tingkat` tiga parameter primer dikunci. Memindahkan Listrik ke Tersier bukan menurunkan bobot, melainkan mencabut aturan primer nol.
  **Koreksi atas dua hal yang aku sendiri keliru.**
  * Kamu menegur bahwa aku terlalu risau pada skor SP yang turun, padahal itu data contoh buatanku sendiri. **Benar.** Yang lebih berguna justru saranmu: sebarannya semula timpang 1 Mandiri, 1 Berkembang, 4 Perlu Penanganan, sehingga dua status pertama masing-masing hanya punya satu contoh untuk memeriksa lencana dan kartu rekap. Kini **2 Mandiri, 2 Berkembang, 2 Perlu Penanganan**, dan keduanya yang terendah kena aturan primer nol agar aturan itu terlihat di layar.
  * Aku sempat berkeberatan soal "kolom bobot yang kosong" pada jenis tanpa parameter. **Keberatan itu salah arah**: penyebabnya bukan tabelnya, melainkan enam jenis yang memang belum punya parameter. Setelah parameter dihasilkan dari jenis, masalahnya hilang dengan sendirinya.
  * Yang tetap kupertahankan: nilai kondisi tetap di master referensi, bukan di parameter. Ia dipakai **7 dropdown** di luar penilaian SP, termasuk form alsintan yang tidak ikut dinilai sama sekali. Menaruhnya di tabel parameter membuat dropdown alsintan membaca tabel penilaian SP.

  * Menu **dipecah dua** atas keberatanmu soal panjangnya: `Data Master` (4 butir) dan `Pengaturan Sistem` (3 butir). Isinya memang dua hal berbeda, dan tiga butir pertama bahkan sudah berawalan "Data Master" sehingga awalan itu kini dicabut dari nama submenunya.
  * Halaman baru memakai **2 tab** sesuai pilihanmu. Ini tidak bertentangan dengan pembongkaran tab pada data master referensi: yang membatasi adalah lebar judul terhadap wadahnya, bukan cacah tabnya (`ui-spec.md` 5.1d), dan dua judul pendek jelas muat.
  * Dijaga **12 uji Pest baru** dan **12 pemeriksaan peramban**, dibuktikan lewat lima mutasi.
  * **Uji peramban menangkap satu cacat nyata**: penguncian tingkat parameter primer tidak bekerja, sebab formnya membaca `$modalData` yang tidak pernah ada. Data baris ternyata disalurkan lewat variabel `baris` milik `modal-form`. Uji string tidak akan pernah melihatnya, sebab atribut `:disabled` memang tertulis rapi di dalam markup.



------------------------------------------------------------------------------------------------------------------------------------


- [done] Di form Inventaris SP, tambahkan opsi kondisi "Hilang" pada dropdown kondisi. Tambahkan juga di data master-nya.
  * Ditambahkan pada enum `Kondisi`, data master referensi, dan kamus data 11.5. Berlaku serentak untuk inventaris, fasilitas, alsintan, dan infrastruktur, sebab keempatnya membaca enum yang sama.
  * **Skornya 0,0, dan angka itu bukan pilihan bebas.** `PenilaianKondisiSp` membandingkan **tepat** terhadap konstanta `NILAI_TIDAK_ADA` untuk menegakkan aturan primer nol. Bila "Hilang" diberi angka yang sekadar kecil, misalnya 0,1, satu-satunya sumur bor yang hilang **tidak akan** menjatuhkan status SP dan kehilangan itu lolos sebagai "Berkembang". Dengan 0,0 keduanya setara: aset yang lenyap tidak melayani siapa pun, persis seperti aset yang tidak pernah ada.
  * "Hilang" bukan tingkat kerusakan melainkan **ketiadaan**. Sebelumnya petugas terpaksa memilih "Rusak Berat" untuk barang yang tidak ditemukan, sehingga inventaris yang lenyap tetap terhitung ada dan dinilai 0,2.
- [done] Di form Transmigran, ubah pilihan Status Tinggal jadi 3 saja: Aktif, Tidak Aktif, Pindah Penduduk (hapus "Meninggal", ganti "Pindah" jadi "Pindah Penduduk"). Update juga di data master-nya.
  * **Pencabutan "Meninggal" punya dasar struktural, bukan sekadar penyederhanaan pilihan.** Status tinggal melekat pada **keluarga**, bukan orang. Ketika kepala keluarga wafat lalu istrinya menggantikan, keluarganya tetap `Aktif` sebab istrinya masih hidup dan menempati rumah yang sama.
  * Pencatatan kematian **tidak hilang**: ia direkam `AlasanPergantianKK::Meninggal`, enum terpisah yang memang mencatat orang beserta tanggalnya. Menyediakan "Meninggal" di kedua tempat membuat petugas membubarkan keluarga yang penghuninya masih ada, dan rumah, lahan, serta keanggotaan poktan keluarga itu ikut hilang dari rekap.
  * Enam keluarga berstatus `Meninggal` pada `rekapPenghuni()` dilebur ke `Tidak Aktif`, sehingga totalnya tetap **1.140 KK** dan uji "penghuni aktif = rumah terhuni" tetap lolos.
  * Docblock `AlasanPergantianKK` ikut dibetulkan: kalimatnya masih menerangkan nilai "Meninggal" pada status tinggal yang kini sudah tiada.
- [done] Di form Saprotan dan alsintan, field Satuan Permukiman dibuat otomatis mengikuti Poktan yang dipilih, bukan dropdown manual.
  * Dropdown manual dicabut, diganti isian terbaca beserta `<input type="hidden">`. Nilainya memang sudah ditentukan begitu poktan dipilih, sehingga menanyakannya lagi hanya membuka peluang satu penyaluran tercatat di SP yang **berbeda** dari poktannya, tanpa penjaga apa pun.
  * ~~Pada alsintan sumber SP **ikut berpindah** mengikuti jenis kepemilikan~~ **TIDAK BERLAKU LAGI sejak 2026-08-22:** kepemilikan pribadi dicabut, sehingga SP selalu terbaca dari poktan.
  * **`pilih-cari` mengabaikan `x-model`.** Komponen itu hanya mengambil `required`, `disabled`, dan `@change` dari pemanggilnya; atribut lain hilang begitu saja sebab ia tidak pernah memanggil `$attributes->merge()`. Dipakai `@change`, sejalan dengan catatan yang sudah ada pada berkas komponennya.
- [done] Tambahkan halaman detail/lihat untuk Riwayat Tanam, ikuti pola halaman detail modul lain. (Dibangun 2026-08-20, dirombak menjadi berpusat poktan pada Tahap 5.)
- [done] Di form Saprotan dan alsintan, hapus pilihan Jenis Penerima individu. keputusan: penerima selalu Poktan saja.
  * `jenis_penerima` dan `transmigran_id` dicabut dari saprotan, `poktan_id` berubah dari nullable menjadi **wajib**. Aturan lama "penyaluran hanya untuk anggota berstatus Aktif" ikut gugur dengan sendirinya: yang menerima kini kelompok, bukan perorangan.
  * Baris contoh BENIH PADI IR64 yang tercatat atas nama YOHANES BERE dialihkan ke POKTAN MEKAR JAYA, poktan tempatnya bernaung dan ber-SP sama.
  * Kartu ringkasan "Kepada Poktan" dan "Kepada Individu" diganti satu kartu **"Poktan Penerima"**. Dipertahankan apa adanya, pasangan kartu itu akan menampilkan seluruh data pada yang satu dan angka nol tetap pada yang lain.
  * ~~Alsintan **tetap** memiliki kepemilikan pribadi: yang dicabut hanya penerima saprotan.~~ **KEPUTUSAN INI KELIRU DAN DIBATALKAN 2026-08-22.** Butir aslinya berbunyi "Di form Saprotan **dan alsintan**" - dua modul, bukan satu. Aku mengerjakan saprotan saja lalu memutuskan sendiri bahwa alsintan dikecualikan, dan menguburnya sebagai satu butir di antara belasan butir lain alih-alih menanyakannya. Pemilik proyek menemukannya sendiri tiga tahap kemudian. Alasan yang kupakai pun tidak bertahan: seluruh menu Pertanian mencatat KELOMPOK, dan data membuktikannya - dua alat berkepemilikan pribadi ternyata **yatim navigasi**, tidak dapat dijangkau dari rincian poktan maupun transmigran.
  * Dijaga **2 uji Pest baru** pada `EnumTest` dan `tests/Browser/uji-sp-otomatis.mjs` berisi **16 pemeriksaan perilaku**.
  * **Uji peramban dipakai, bukan uji string, dan alasannya spesifik.** Yang dijaga di sini adalah perilaku: nilai `satuan_permukiman_id` kini dihitung Alpine, bukan diketik. Atribut `:value` yang salah tulis tetap **terlihat benar** di markup tetapi mengirim nilai kosong ke peladen � persis pola cacat `$modalData` yang dahulu lolos seluruh uji string (bagian 6).
  * **Mutasi membuktikannya:** mencabut satu `@change` pada form saprotan langsung memerahkan tiga pemeriksaan dengan bunyi `sp ""`. Uji yang tidak pernah dapat memerah tidak menjaga apa pun.
  * Dokumen ikut diselaraskan: kamus data 8.4, 11.5, 11.8, 11.33 beserta tabel aturan integritas nomor 5 dan 9; `erd.md` relasi 24-26, daftar indeks, dan urutan migration; `rules.md` 7c.
- [done] rename menu "Riwayat Tanam" menjadi "Penanaman" agar gak bingung, sebab diksi Riwayat Tanam itu seakan-akan kayak sudah melakukan penanaman.
  * Alasanmu tepat, dan kodenya sendiri membenarkannya. Kata "riwayat" menyiratkan catatan masa lalu, padahal barisnya dibuat justru ketika penanaman baru dimulai dan panennya belum ada. Lebih menyesatkan lagi, `hasil_panen` menaut ke tabel inilah: menyebut induk dari panen sebagai "riwayat" membuat orang mengira penanaman yang sedang berjalan dicatat di tempat lain.
  * Diganti pada SELURUH lapisan sekaligus, bukan labelnya saja: 3 berkas blade (lewat `git mv` agar riwayat berkasnya tidak putus), 5 rute beserta nama rutenya, kunci `id_riwayat_tanam` menjadi `id_penanaman`, `hasil_panen.riwayat_tanam_id` menjadi `penanaman_id`, method `DummyData::riwayatTanam()`, kunci izin, item menu, dan seluruh label tampilan.
  * **Rename setengah jalan lebih buruk daripada tidak sama sekali.** Alamat yang masih `/riwayat-tanam` sementara menunya berbunyi "Penanaman" membuat satu hal disebut dua nama, dan petugas berikutnya mewarisi kebingungan itu. Karena itu yang dijaga uji bukan hanya labelnya.
  * **Kata "riwayat" sendirian TIDAK disentuh.** Empat modul lain memakainya tanpa ada hubungannya: riwayat penghunian, riwayat kepala keluarga, riwayat penanganan pengaduan, dan variabel `$riwayat` pada komponen catatan log. Penggantian karena itu memakai token PERSIS, dan diperiksa ulang setelahnya: 48 kemunculan milik modul lain tetap utuh.
  * **Satu jebakan PowerShell tercatat:** kunci hashtable-nya TIDAK peka huruf besar-kecil, sehingga `riwayatTanam` dan `RiwayatTanam` dianggap kunci yang sama dan skrip menolak berjalan. Diganti larik berpasangan. Urutannya juga menentukan: token panjang wajib lebih dulu, sebab `riwayat_tanam_id` memuat `riwayat_tanam` di dalamnya.
  * **Tanpa pengalihan dari alamat lama.** Tahap 2 belum pernah terbit sebagai sistem yang dipakai, sehingga tidak ada tautan lama yang perlu dijaga. Menambahkan pengalihan justru menyisakan dua alamat untuk satu halaman.
  * Dijaga **1 uji Pest baru berisi 31 pemeriksaan** yang menyisir seluruh lapisan: method, kunci larik tiap baris, 4 nama rute, respons 200 dan 404, keberadaan 6 berkas blade, kunci izin, definisi menu, dan nama isian pada form panen. **Dibuktikan lewat dua mutasi**: mengembalikan label menu, dan mengembalikan nama isian `riwayat_tanam_id` pada form panen. Keduanya memerah.
  * Diperiksa juga di luar uji: **196 alamat statis** seluruhnya 200, **129 pemeriksaan peramban** hijau, dan delapan halaman disisir memastikan tidak ada label lama yang tertinggal di HTML terbit.
  * Dokumen ikut diselaraskan: kamus data 9.2 (beserta alasan penggantiannya), 9.3, 13.1, 13.2; `erd.md` domain, diagram ASCII (lebar kotak dijaga tetap agar rangkanya tidak bergeser), relasi 30/32/33, aturan integritas, indeks, urutan migration; `rules.md`; `prd.md`; `ui-spec.md`; `workflow.md`; `tasklist.md`.
  * Catatan lama pada `tasklist.md` TIDAK ditulis ulang, hanya diberi keterangan penggantian nama. Menulis ulang riwayat keputusan membuat catatan itu berbohong tentang apa yang dahulu diputuskan.
- [done] Fitur musim tanam dihapus saja, sebab realitanya poktan melakukan penanamannya secara fleksibel. Nanti untuk pencatatan kapan poktan mulai menanam dan kapan poktan panen ada di menu riwayat tanam dan hasil panen.
  * **Dihapus seluruhnya**, bukan disembunyikan: 5 rute, 3 halaman, item menu, izin `musim_tanam`, `DummyData::musimTanam()`, peta tautan statis, kelompok `musim` pada rekap panen, kolom tabel pada 6 halaman, dan 2 uji.
  * **Sumbu waktunya berpindah ke tanggal yang memang sudah dicatat.** `penanaman.tanggal_tanam` dan `hasil_panen.tanggal_panen` sudah ada sejak awal, sehingga tidak ada data yang hilang � yang hilang hanyalah keharusan menebak penanaman itu masuk MT1 atau MT2.
  * **`tanggal_tanam` naik status menjadi WAJIB.** Ia kini satu-satunya pembeda antara dua penanaman komoditas yang sama pada bidang yang sama. Kunci uniknya ikut berubah dari `(lahan_id, musim_tanam_id, komoditas_id)` menjadi `(lahan_id, komoditas_id, tanggal_tanam)`; tanpa itu kunci lama akan menolak penanaman kedua pada lahan yang sama, padahal satu bidang memang ditanami berulang kali.
  * **Rekap per periode tidak ikut hilang.** `rules.md` 8b.8 mewajibkannya, dan kewajiban itu tetap terpenuhi lewat penyaringan **Tahun Tanam** dan **Tahun Panen** yang dihitung dari tanggalnya. Tahun sengaja diturunkan, bukan disimpan sebagai kolom: kolom terpisah dapat berbeda dari tanggal yang menjadi sumbernya.
  * **Kolom "Musim" pada tabel dihapus, bukan diganti tanggal.** Pada daftar riwayat tanam dan hasil panen, kolom tanggal sudah bersebelahan sehingga penggantian hanya akan menampilkan hal yang sama dua kali. `colspan` baris total ikut disesuaikan � luput menyesuaikannya membuat baris total bergeser satu kolom tanpa memerahkan uji mana pun.
  * **Label pilihan catatan tanam pada form panen memakai bulan tanam**, bukan lagi label musim. Tanpa penanda waktu, dua penanaman pada lahan dan komoditas yang sama tampil sebagai dua pilihan yang bunyinya identik.
  * **Jumlah kewenangan turun 101 ke 99**, dan angka `jumlah_izin` keempat role bawaan ikut dibetulkan. Angka itu ditulis manual pada `DummyData::role()` dan dijaga uji yang membandingkannya dengan hasil hitungan.
  * Dijaga **1 uji Pest baru** yang menyisir seluruh jejaknya sekaligus: method `DummyData`, lima nama rute, respons 404, keberadaan ketiga berkas blade, kunci izin, definisi menu, dan kelompok rekap. **Mutasi membuktikannya**: menyisipkan kembali item menu musim tanam langsung memerahkannya.
  * Diperiksa juga di luar uji: **196 alamat** pada peta tautan statis seluruhnya membalas 200, dan **129 pemeriksaan peramban** pada tujuh berkas uji tetap hijau.
  * Dokumen ikut diselaraskan: kamus data 5.3 (jadi nisan beserta alasannya), 9.2, 9.3, 13.1, 13.2; `erd.md` domain, diagram, relasi 31, aturan integritas 10, indeks, urutan migration, data awal, dan **penyelesaian nomor 22 ditandai DIBATALKAN** � di situlah keputusan lama menambah `nama`/`tahun` pada `musim_tanam` dicatat, sehingga membiarkannya membuat dokumen menjanjikan tabel yang tidak ada; `rules.md`; `prd.md`; `ui-spec.md`; `workflow.md`; `tasklist.md`.
- [done] Form tanggal pada riwayat tanam dan hasil panen, dibuat jadi Bulan-Tahun saja tanpa tanggal.
  * **`tanggal_tanam` menjadi `periode_tanam`**, sejajar dengan `periode_panen` yang sudah lebih dulu berbentuk bulan. Keduanya kini `CHAR(7)` berbentuk `YYYY-MM`, dan isiannya memakai `<input type="month">`.
  * Alasannya sama untuk keduanya: penanaman maupun panen satu hamparan berlangsung berhari-hari, sehingga menuntut satu tanggal pasti membuat petugas **menebak** - dan tebakan itu lalu dipakai sebagai dasar rekap seolah-olah data terukur.
  * **Kunci unik ikut berubah** menjadi `(poktan_id, komoditas_id, periode_tanam)`. Konsekuensinya disengaja: satu poktan tidak dapat mencatat dua penanaman komoditas yang sama pada bulan yang sama. Bila kelak diperlukan, pembedanya harus ditambahkan sebagai kolom tersendiri, bukan dikembalikan ke tanggal.
  * **Satu jebakan Carbon tercatat:** `Carbon::parse('2025-11')` menafsirkannya sebagai WAKTU, bukan bulan. Seluruh pemanggilan karena itu diberi imbuhan `. '-01'`. Tanpa itu tampilan tanggal melenceng tanpa melempar galat apa pun.
- [done] tambah field upload dokumen pada form Penanaman dan Hasil Panen.
  * Penanaman sebelumnya tidak punya isian unggahan sama sekali, padahal **berita acara tanam** adalah bukti yang paling sering diminta saat pemeriksaan program bantuan.
  * Hasil Panen sudah punya, tetapi **dibatasi gambar saja** lewat `:hanya-gambar`. Batasan itu dilepas: berita acara lazimnya PDF hasil pindaian, sedangkan foto hamparan berupa gambar. Membatasinya pada salah satu memaksa petugas menyimpan yang lain di luar sistem - dan berkas yang disimpan di luar sistem sama saja dengan tidak ada.
  * Label keduanya diseragamkan menjadi **"Dokumen atau Foto ..."**, mengikuti pola yang sudah dipakai Alsintan. Label yang menjanjikan dua hal wajib benar-benar menerima keduanya; ketidaksesuaian persis itu pernah tercatat pada Fasilitas SP.
  * Dijaga **2 uji Pest baru** dan **4 pemeriksaan peramban baru**. Dibuktikan lewat dua mutasi: mengembalikan `type="date"` dan mencabut isian unggahan. Keduanya memerah.
  * Dokumen ikut diselaraskan: kamus data 9.2 (kolom `periode_tanam` dan `dokumen_pendukung`, kunci unik, aturan integritas 10 dan 41); `erd.md` indeks dan aturan integritas 10; `rules.md` bagian 7d poin 9-10 dan bagian 9 poin 15.
- [done] Di semua menu Produksi Pertanian, fokus pencatatannya itu dari Poktan, bukan per individu. (Saprotan, Penanaman, dan Hasil Panen seluruhnya berpusat poktan sejak 2026-08-22.)
- [done] Sepertinya pada menu riwayat tanam dan hasil panen bakal dirombak besar-besaran untuk pengisian form-nya. Nanti untuk kolom datatable maupun data lain yg ditampilkan tinggal mengikuti dari field-field yg harus diisi. Berikut field form baru untuk input data pada menu riwayat tanam: Kelompok Tani, Jumlah Anggota, Luas Lahan (ha), Volume Benih (kg), Bulan+Tahun, Catatan. Sedangkan untuk garis besar field form baru untuk input data menu Hasil Panen: Kelompok Tani, Jumlah Anggota, Luas Lahan (ha), Volume Benih (kg), Realisasi Tanam (ha), Belum Ditanam (ha), Penanaman (Bulan+Tahun), Periode Panen (Bulan+Tahun), Hasil Panen (ha), Puso (ha), Belum Dipanen (ha), Produktivitas (ton/ha), Produksi (ton), Catatan. Untuk Produksi itu dihitung dari Hasil Panen (ha) x Produktivitas (ton/ha). Oh iya, mungkin pada form Hasil Panen, tinggal pilih poktan lalu muncul data penanaman dan pilih data penanaman oleh poktan tersebut sehingga memunculkan data Luas Lahan (ha), Volume Benih (kg), Penanaman (Bulan+Tahun). 
  * **[done - menu Penanaman]** Form Penanaman dirombak 2026-08-22 mengikuti daftar field barumu.
  * **[done - menu Hasil Panen]** Form Hasil Panen dirombak 2026-08-22 mengikuti garis besar field barumu, termasuk Periode Panen (Bulan+Tahun) yang kau tambahkan kemudian.
  * **Satu pilihan mengisi DELAPAN isian.** Petugas memilih penanaman, lalu Kelompok Tani, Jumlah Anggota, Luas Lahan, Volume Benih, Realisasi Tanam, Penanaman (Bulan), Komoditas, dan Satuan terbaca sendiri. Yang benar-benar diketik hanya lima: Hasil Panen, Puso, Produktivitas, Harga Jual, Catatan.
  * **Dua identitas aritmetika dijaga**, keduanya terbukti pada 96 baris laporan Polri: `hasil panen + puso + belum dipanen = realisasi tanam` dan `produksi = hasil panen x produktivitas`. Belum Dipanen dan Produksi karena itu terkunci, tidak pernah diketik.
  * **Belum Dipanen dihitung dari SISA penanaman, bukan realisasi tanam mentah.** Perbedaannya baru terasa saat panen bertahap: penanaman 2 ha yang sudah dipanen 1,2 ha menyisakan 0,8 ha, dan memakai angka mentah akan menawarkan lahan yang sebenarnya sudah habis dipanen.
  * **`lahanTersedia()` ikut diperhalus.** Sebelumnya satu baris panen dianggap menuntaskan SELURUH penanaman. Itu keliru sejak panen dapat bertahap: penanaman 10 ha yang baru dipanen 3 ha akan langsung melepaskan seluruh 10 ha, sehingga lahan yang masih berdiri tanaman tampak siap ditanami lagi.
  * **Produksi tetap disimpan** meski dapat dihitung dari dua kolom lain. Ia angka yang dilaporkan ke dinas, dan pembulatan hasil perkalian dapat berbeda tipis dari yang benar-benar ditimbang.
  * **Satuan tidak dipaksa ton**, sesuai keputusanmu. Produktivitas ikut satuan baku komoditasnya: jagung ton/ha, cabai kg/ha. Memaksanya ton membuat harga jual cabai per ton menjadi angka yang tidak pernah dipakai siapa pun di lapangan. Mesin konversi `keTon()` karena itu TIDAK jadi kode mati.
  * `volume` berganti nama menjadi `produksi` sesuai istilahmu, sekaligus mencegahnya tertukar dengan `volume_benih` pada penanaman.
  * **Kualitas panen dicabut** beserta enum, data master referensi, kolom tabel, dan ujinya. Digantikan Produktivitas pada kolom daftar: angka terukur lebih berguna daripada label mutu yang menuntut penilaian tak terverifikasi. Daftar referensi turun 14 ke 13.
  * **Rekap panen: kelompok `petani` diganti `poktan`.** Panen dicatat per kelompok, sehingga rekap per orang berarti mengarang pembagian yang tidak pernah didata.
  * Satu data contoh diubah menjadi **panen bertahap**: penanaman #3 seluas 2 ha baru dipanen 1,2 ha. Tanpa itu seluruh penanaman tuntas dipanen dan cabang panen bertahap tidak punya benda nyata untuk diuji.
  * Dijaga **4 uji Pest baru** dan `tests/Browser/uji-form-panen.mjs` berisi **15 pemeriksaan perilaku**. Dibuktikan lewat **lima mutasi**: produktivitas tak sejalan produksi, identitas luas timpang, `belumDipanen` abaikan puso, form pakai realisasi tanam mentah, dan produksi tak dikali hasil panen. Seluruhnya memerah.
  * **Dua uji peramban lama ikut memerah dan itu benar**: batas lahan berubah 4,25 ke 3,45 sebab 0,8 ha masih berdiri tanaman, dan kartu referensi 14 ke 13 sebab kualitas dicabut. Keduanya disesuaikan, bukan dilonggarkan.
  * Dokumen ikut diselaraskan: kamus data 9.3 (tabel kolom, dua identitas, tabel perubahan), 11.19 dijadikan nisan beserta alasannya, aturan integritas 39-41 dan nomor 10; `erd.md` relasi 33a, indeks, urutan migration; `rules.md` bagian 9 poin 8-14; `prd.md`; `ui-spec.md`; `workflow.md`; `tasklist.md`.
  * **Berpusat pada Poktan.** Kolom `lahan_id` dan `petani` dicabut, digantikan `poktan_id`. Rantai lokasinya tetap utuh tanpa lahan: `penanaman -> poktan -> satuan_permukiman`, sebab poktan sudah menyimpan SP-nya sendiri.
  * **Tiga isian TERKUNCI, tidak pernah diketik:** Jumlah Anggota, Luas Lahan, dan Belum Ditanam. Ketiganya turunan dari data yang sudah ada. Menyimpannya berarti angka itu basi begitu satu anggota keluar atau satu bidang lahan dibetulkan, dan kebasian itu tidak pernah memerahkan apa pun - persis alasan kolom `luas_lahan_kelompok` dicabut 2026-08-20.
  * **Luas lahan memakai definisimu:** akumulasi lahan ketua beserta seluruh anggota AKTIF. Anggota yang sudah keluar tidak dihitung, sebab lahannya tidak lagi digarap kelompok ini. Ketua yang juga terdaftar sebagai anggota tidak dihitung dua kali.
  * **Alur formnya menurun**, tiap langkah menyempitkan langkah berikutnya: Kelompok Tani mengisi anggota dan luas, Komoditas menyaring benih, Benih membatasi volume, Realisasi Tanam menghitung Belum Ditanam. Petugas tidak pernah memilih poktan dua kali - benih sudah pasti miliknya karena ikut tersaring.
  * **Lahan kembali setelah dipanen, benih tidak.** Perbedaan sifat ini kupasang sebagai `lahanTersedia()`: yang dikurangkan hanya penanaman yang BELUM tuntas dipanen. Mengurangkan seluruh penanaman sepanjang sejarah membuat lahan poktan tampak habis setelah beberapa musim, padahal bidang yang sama memang ditanami berulang kali tiap tahun.
  * `luas_tanam` berganti nama menjadi `realisasi_tanam` dan menjadi wajib. Bukan kosmetik: penggantian itu mencegahnya tertukar dengan LUAS LAHAN poktan yang merupakan angka terhitung, bukan isian.
  * **Hasil panen kini menaut lewat `penanaman_id`**, bukan pencocokan teks komoditas dan petani. Pencocokan teks menyatukan dua penanaman berbeda yang kebetulan sama, sehingga volumenya terhitung dua kali.
  * **Halaman Transmigran kehilangan tab Hasil Panen**, diganti tab Kelompok Tani sesuai keputusanmu. Panen dicatat per poktan, sehingga menyaringnya per keluarga berarti menebak: satu poktan berisi banyak keluarga dan panennya milik kelompok. Menautkan ke poktan lebih jujur daripada mengarang pembagian per orang yang tidak pernah didata.
  * Dijaga **6 uji Pest baru** dan `tests/Browser/uji-form-penanaman.mjs` berisi **20 pemeriksaan perilaku**. Dibuktikan lewat **tiga mutasi**: benih tak disaring komoditas, batas volume dicabut, dan lahan tak pernah kembali setelah dipanen. Ketiganya memerah.
  * **Satu jebakan Blade tercatat:** `@php(...)` bentuk ringkas PECAH bila argumennya memuat tanda kurung bersarang seperti `firstWhere(...)`. Galatnya berbunyi "unexpected end of file" dan menunjuk akhir berkas, bukan baris yang keliru. Diganti peta yang disusun sekali di blok `@php` biasa.
  * Dokumen ikut diselaraskan: kamus data 9.2 (beserta tabel tiga angka terhitung dan alasannya), tabel aturan integritas nomor 36-38; `erd.md` relasi 30, indeks, aturan integritas 10, urutan migration; `rules.md` bagian 7d baru berisi 8 aturan Penanaman.
- [done] diskusikan mengenai relasi antara Saprotan, Komoditas, dan riwayat tanam.
  * **Jawabannya datang dari dokumen rujukanmu sendiri**, bukan dikarang: kolom laporan Polri MT.II 2025 berbunyi Kelompok Tani, Jumlah Anggota, Luas Lahan (ha), **Volume Benih (Kg)**, Rencana Tanam (Bln), Realisasi Tanam (Ha). Volume Benih itulah saprotan, dan ia menempel pada PENANAMAN, bukan berdiri sendiri di modul terpisah.
  * Peta relasinya: **Poktan pelakunya, Komoditas penyaringnya, Saprotan stoknya, Penanaman peristiwanya.** Benih ditaut ke komoditas lewat `saprotan.komoditas_id`, dan dikonsumsi penanaman lewat `penanaman.saprotan_id` beserta `volume_benih`.
  * **Temuan yang mengubah asumsi awal: benih TIDAK habis sekali pakai.** Instingmu benar bahwa benih barang konsumsi, tetapi penguncian setelah pemakaian pertama akan mematahkan alur nyata. Bukti aritmetiknya rapat di 96 baris laporan: Realisasi Tanam + Sisa Belum Ditanam = Luas Lahan, tepat. Poktan Wanibesak menerima 150 kg untuk 10 ha lalu **baru menanam 3 ha**; bila benih terkunci pada pemakaian pertama, penanaman 7 ha sisanya tidak dapat dicatat sama sekali dan petugas terpaksa mengarang entri penyaluran baru untuk bantuan yang tidak pernah datang.
  * **Aturan yang benar: benih terkunci ketika STOKNYA HABIS, bukan ketika pertama dipakai.** Rumusnya satu pengurangan, `sisa = jumlah - SUM(volume_benih)`, dan ia **mengoreksi dirinya sendiri** ketika baris penanaman disunting. Itulah sebabnya usulanmu soal mengedit baris tidak memerlukan mekanisme pengembalian stok apa pun: catat alokasi penuh dulu, lalu sunting saat ketahuan hanya 3 ha yang ditanam, dan 105 kg muncul kembali dengan sendirinya.
  * **Volume benih DISIMPAN, bukan dihitung dari luas tanam.** Rasio 15 kg/ha berlaku pada 92 dari 96 baris laporan, tetapi itu keputusan program pada satu bantuan, bukan hukum alam: benih swadaya dan komoditas lain memakai takaran berbeda. Menghitungnya otomatis membuat angka karangan tampil seolah-olah hasil pendataan, dan itu dilarang rules.md 19a.
  * **Komoditas hanya diwajibkan pada benih.** Urea dipakai tanaman apa pun; memaksanya memilih satu komoditas berarti mengarang data yang tidak ada di lapangan. Isiannya karena itu muncul bersyarat, beserta `:required` DAN `:disabled` yang mengikuti jenis - tanpa `:disabled`, isian wajib yang sedang tersembunyi akan menahan pengiriman sambil menunjuk elemen yang tidak tampak, sehingga form seolah menolak diam-diam.
  * Sisa stok TIDAK disimpan sebagai kolom. Menyimpannya menuntut koreksi setiap kali satu baris penanaman disunting, dan koreksi yang terlewat tidak akan pernah ketahuan. Halaman daftar dan rincian saprotan kini menampilkan sisa beserta jumlah terpakainya, dan benih yang habis ditandai tegas.
  * Dijaga **7 uji Pest baru** dan `tests/Browser/uji-benih-komoditas.mjs` berisi **15 pemeriksaan perilaku**. Dibuktikan lewat **empat mutasi**: sisaBenih mengabaikan pemakaian, benihTersedia tidak menyaring stok habis, pupuk diberi komoditas, dan `required` tetap tanpa `:disabled`. Keempatnya memerah.
  * **Uji peramban menangkap satu cacat nyata pada uji itu sendiri.** Pemeriksaan pertama membaca `select[name=jenis]` dengan querySelector polos, dan yang ditemukan ternyata **penyaring tabel** pada halaman daftar, bukan isian di dalam modal - sebab penyaring dirender lebih dulu. Uji itu akan hijau selamanya sambil menguji benda yang salah. Diperbaiki dengan mencarinya di dalam modal.
  * Satu data contoh sengaja ditambahkan: BENIH JAGUNG LOKAL 30 kg yang **seluruhnya terpakai**, agar keadaan stok habis ikut terlihat saat peninjauan dan uji punya benda nyata untuk diperiksa.
  * Dokumen ikut diselaraskan: kamus data 8.4 (beserta rumus sisa stok dan alasannya), 9.2, tabel aturan integritas nomor 31-35; `erd.md` relasi 26a dan 32a, daftar indeks, urutan migration; `rules.md` 7c.7-10.
- [done] Menu Alsintan dan Saprotan kasih field upload foto dan dokumen (2 field) seperti pada form menu inventaris dkk.
  * Kolom `foto` ditambahkan pada kamus data 8.3 dan 8.4, beserta data contohnya. Keduanya kini memakai grid dua kolom berdampingan, sama seperti inventaris, fasilitas, dan infrastruktur SP.
  * Alasannya sama dengan yang sudah tercatat pada modul-modul itu: **foto merekam wujud dan kondisi barang saat pendataan, dokumen menyimpan berkas administratifnya.** Satu slot untuk keduanya memaksa petugas memilih salah satu, dan yang mengunggah dokumen setelah foto kehilangan fotonya tanpa peringatan apa pun.
  * Label lama "Dokumen atau Foto Alat" memang sudah menjanjikan dua hal untuk satu slot - ketidaksesuaian persis yang pernah tercatat pada Fasilitas SP.
  * Dijaga lewat penambahan dua baris pada dataset uji unggahan yang sudah ada. Dibuktikan lewat mutasi: mengganti `nama="foto"` memerahkan uji.
- [done] keterangan satuan local di form hasil panen hapus saja.
  * **Dihapus TOTAL**, bukan sekadar disembunyikan dari layar: kolom pada kamus data 9.3, isian pada form, tampilan pada halaman rincian, daftar `UppercaseInput`, dan data contohnya.
  * Kolom ini dipegang **empat dokumen acuan** sekaligus, dan seluruhnya ikut disunting - bukan dibiarkan bertentangan: `rules.md` 8a.6 dan 9.4, `ui-spec.md` 6.4a (tiga pengecualian penamaan menjadi **dua**), dan `data-dictionary.md` 5.2 yang menerangkan mengapa karung/ikat tidak masuk tabel satuan.
  * Padanan satuan setempat kini ditulis pada kolom `keterangan` biasa bila memang perlu dicatat. Aturan bahwa produksi selalu memakai satuan baku komoditasnya **tidak berubah**; yang hilang hanya kolom tersendiri untuk padanannya.
- [done] untuk semua modal form yang ada field upload foto/dokumen + catatan, diseragamkan posisinya agar field foto/dokumen ditaruh paling akhir/bawah. Sedangkan untuk posisi catatan ditaruh sebelum field upload foto/dokumen.
  * **Tujuh form ditukar**, lima sudah benar sejak awal, empat tidak tersentuh sebab hanya punya salah satunya. Seluruh 12 form yang punya keduanya kini seragam.
  * Yang ditukar: alsintan, saprotan, poktan, sp, sp/form-kawasan, lahan, infrastruktur. Tiga di antaranya bukan tukar-posisi sederhana: pada `form-kawasan` keduanya berada dalam satu grid sehingga yang ditukar dua `<div>` bersaudara; pada `infrastruktur` unggahan menumpang seksi "Pendanaan dan Kondisi" sehingga ia diangkat menjadi seksi "Dokumentasi" tersendiri; pada `lahan` yang berpindah adalah seluruh seksi "Dokumen Status Lahan".
  * Alasannya ditulis pada `ui-spec.md` 6.4a poin 5 yang baru: **isian berkas menuntut perhatian lebih lama daripada isian teks.** Petugas berhenti mengetik, membuka penjelajah berkas, mencari, lalu kembali. Menaruhnya di tengah memutus alur pengisian, dan catatan yang berada sesudahnya kerap terlewat.
  * Dijaga **1 uji Pest baru** yang menyisir SELURUH berkas `form*.blade.php` dan membandingkan posisi markup keduanya, bukan sekadar keberadaannya. Uji ini berlaku untuk form yang belum ada sekalipun. Dibuktikan lewat mutasi: membalik urutan pada `sp/form` memerahkannya beserta nama berkasnya.
- [done] Sesuaikan menu rekap panen dengan semua menu Pertanian yg telah dirombak besar-besaran
- [done] Apakah lebih baik ditambahkan filter untuk hasil rekap pertahun atau bagaimana baiknya? Diskusikan dan brainstorming sekalian cari referensi dari proyek2 yg sudah ada di internet dll. 
  * **Pencarian internet tidak menghasilkan rujukan yang layak dikutip**; mesin pencari hanya membalas hasil sampah. Tidak ada referensi yang dikarang untuk menutupinya. Rujukan terkuat justru ada di repo sendiri: `refs/Lap. Panen Sisa Tanam Polri MT. II 2025.pdf`, dan judulnya sudah menjawab pertanyaannya - laporan itu SENDIRI adalah sebuah rekap panen.
  * **Rekap sebelumnya belum ikut dirombak sama sekali.** Ia masih berbentuk sebelum Tahap 6: hanya Jumlah Catatan, Volume, dan Nilai Jual. Realisasi tanam, puso, produktivitas, dan belum dipanen yang ditambahkan perombakan 2026-08-22 tidak satu pun muncul, sehingga dinas melihat "4,25 ton" tanpa tahu dari berapa hektare.
  * **Basis diubah dari hasil panen menjadi PENANAMAN**, dan inilah keputusan terpentingnya. Pada basis lama, poktan yang sudah menanam tetapi belum panen **hilang sama sekali** dari rekap, sehingga dinas membaca "tidak ada masalah" justru pada keadaan yang paling perlu ditengok. Laporan rujukan memilih basis yang sama, dan judulnya menyatakannya: kolom "Sisa Tanam" mustahil ada bila barisnya bukan poktan yang menanam.
  * **Perbedaan basis ini TIDAK terlihat pada data contoh** ketika diusulkan, sebab kelima penanaman kebetulan sudah dipanen semua. Itu sengaja TIDAK dipakai sebagai alasan apa pun (`rules.md` 19a); alasannya sifat pertanyaannya, bukan cacah baris pada `DummyData`. Baris #6 yang ditambahkan pada tahap sebelumnya lalu memberi cabang itu benda nyata untuk diuji.
  * **Rekap kini selalu terikat satu tahun**, menjawab kekhawatiranmu soal baris Total. Sebelumnya `$semua = DummyData::hasilPanen()` tanpa batas waktu apa pun: dengan lima baris contoh tidak terasa, tetapi setelah sepuluh tahun "Total" berarti total sejak sistem berdiri. Dua cacatnya - total kumulatif hanya dapat NAIK sehingga musim yang hancur pun tampak sebagai kabar baik, dan LUAS tidak boleh dijumlahkan lintas tahun sebab bidang 2 ha yang ditanami tiga tahun akan terbaca "6 ha".
  * Periodenya **tertulis pada judul tabel dan baris total**, bukan disembunyikan di penyaring. Angka rekap tanpa periodenya tidak dapat disalin ke laporan mana pun. Baris totalnya berbunyi "Total tahun tanam 2026", bukan "Total".
  * **Penyaringnya TAHUN TANAM, bukan tahun panen**, dan ini mengikuti langsung dari pilihan basis: penanaman yang belum dipanen tidak punya periode panen sama sekali, sehingga menyaringnya dengan tahun panen akan membuangnya - justru kebutaan yang hendak ditutup. Satu baris karena itu berarti "musim tanam tahun ini", persis arti "MT. II 2025" pada laporan rujukan.
  * Bawaannya **tahun berjalan** sesuai keputusanmu. Konsekuensinya diterima sadar: tiap awal tahun halaman nyaris kosong sampai penanaman pertama dicatat, dan itu jujur. Tahun berjalan tetap ditawarkan pada dropdown meski belum ada penanamannya - tanpa itu, pilihan yang sedang aktif justru tidak ada di dalam daftarnya sendiri.
  * **Kolom "Jumlah Catatan" dicabut.** Ia menghitung baris entri, bukan besaran lapangan: poktan yang panen bertahap tiga kali tampak "lebih banyak" daripada yang panen sekali, meski luasnya lebih kecil. Digantikan cacah poktan, dihitung sebagai himpunan sebab satu poktan dapat punya banyak penanaman. Pada tab poktan kolom itu tidak dirender, sebab nilainya selalu satu.
  * **Produktivitas WAJIB tertimbang.** Merata-ratakan kolomnya mencampur ton/ha dengan kg/ha: jagung 3,4 ton/ha dan cabai 1.282 kg/ha dirata-rata menghasilkan **642 ton/ha**, angka yang tidak ada di alam. Rumusnya total produksi dibagi total luas dipanen, keduanya setelah konversi.
  * **Uji menemukan satu cacat yang tidak terduga.** Produktivitas mula-mula dihitung dari produksi MENTAH sebelum pembulatan, menghasilkan 1,284 untuk CABAI sedangkan kolom yang tampil (0,321 dibagi 0,25) menghasilkan 1,282. Pembaca yang mengalikan dua kolom di layar untuk memeriksa ulang tidak akan pernah cocok. Diperbaiki agar dihitung dari angka yang sudah dibulatkan, dan aturannya ditulis sebagai `rules.md` 9.8e.
  * **Grafik dan kartu statistik sengaja TIDAK ditambahkan.** `ui-spec.md` 2.2 mengunci halaman rekap sebagai tabel agregat tanpa kartu, dan dashboard sudah punya grafik "Volume Panen per Tahun". Menambahkannya menduplikasi dashboard sekaligus melanggar dial RITME.
  * **Tab tetap tiga**, sehingga tiga tempat terkunci pada 1e.5 tidak tersentuh sama sekali.
  * Yang sengaja **dibiarkan terbuka**: dengan dropdown tahun, membandingkan antartahun menuntut petugas berganti pilihan berulang kali. Tab "Per Tahun Tanam" akan menjawabnya dalam satu layar, tetapi kau memilih dropdown saja - dan itu dapat ditambahkan kelak tanpa membongkar apa pun.
  * Satu hal yang perlu diingat: penyaring `?tahun=` **tidak dilayani GitHub Pages**, sehingga di situs terbit hanya tahun berjalan yang terbuka. Sejalan dengan 1b.5 yang sudah mencatat seluruh penyaring tabel memang belum berfungsi di sana, jadi bukan kemunduran baru.
  * Dijaga **7 uji Pest baru**, dibuktikan lewat **tiga mutasi**: basis dikembalikan ke hasil panen, produktivitas tidak dibagi luas, dan cacah poktan dipatok angka tetap. Seluruhnya memerah.
  * Diperiksa di peramban sungguhan, sebab lebar tabel tidak dapat dijawab uji string: **9 kolom pada tab SP dan 8 pada tab poktan, cacah sel footer sama persis dengan header, tanpa gulir mendatar**.
  * Diperiksa juga di luar uji: **577 uji Pest** hijau, **197 alamat statis** seluruhnya 200, dan **183 pemeriksaan peramban** pada sebelas berkas tetap hijau.
  * Dokumen ikut diselaraskan: `rules.md` 9.8a sampai 8f; kamus data 9.3 beserta seksi rekap dan catatan dua kolom yang belum pernah ada.
- [done] Sekalian tambahkan semacam informasi, entah dibuat kolom baru atau bagaimana di datatabel pada halaman Penanaman dan Hasil Panen agar tahu apakah Penanaman A sudah dipanen atau sudah dipanen sebagian atau belum dipanen sama sekali. Jadi nanti bisa difilter juga data penanaman/hasil panen mana yg belum dipanen/baru dipanen sebagian dan mana yg sudah dipanen sepenuhnya.
  * **Tidak ada kolom baru di database.** Status diturunkan lewat `StatusPanen` dan `DummyData::statusPanen()`, memakai `belumDipanen()` yang sudah ada sejak perombakan 2026-08-22. Alasannya sama dengan `belum_dipanen` itu sendiri: kolom tersimpan menjadi salah begitu satu baris panen disunting atau dihapus, dan kesalahan itu tidak pernah memerahkan apa pun.
  * **Dikerjakan MENDAHULUI rekap panen atas pertimbangan urutan.** Rekap berbasis penanaman membutuhkan angka sisa yang sama, sehingga mengerjakan rekap lebih dulu berarti menulis aturan yang sama di tiga tempat: rekap, daftar Penanaman, dan daftar Hasil Panen. Tiga salinan yang dapat berbeda diam-diam.
  * **Keadaan `Belum Dipanen` TIDAK dapat disimpulkan dari sisa luas saja**, dan ini yang paling mudah keliru. Penanaman yang belum disentuh dan penanaman yang dipanen nol hektare sama-sama menyisakan seluruh luasnya. Keberadaan barisnya karena itu diperiksa tersendiri, dan mutasi yang mencabut pemeriksaan itu memang memerahkan uji.
  * **Puso tetap kolom tersendiri, bukan status keempat**, sesuai keputusanmu dan bentuk laporan rujukan. Konsekuensinya diuji terang-terangan alih-alih dibiarkan sebagai kejutan: lahan yang gagal total menyisakan nol, sehingga berstatus `Selesai Dipanen` sama dengan yang berhasil penuh.
  * **Daftar Hasil Panen sengaja hanya menawarkan DUA pilihan penyaring.** Penanaman yang belum dipanen tidak punya satu pun baris panen, sehingga pilihan `Belum Dipanen` di sana akan selalu menghasilkan tabel kosong - kontrol mati yang dilarang R-26, kekeliruan yang persis sama sudah tercatat pada 1d.1. Pembatasannya tinggal pada `StatusPanen::penyaringPanen()`, bukan ditulis sebagai pengecualian di view.
  * **Judul kolomnya sengaja berbeda antar halaman:** "Status Panen" pada Penanaman, "Status **Penanaman**" pada Hasil Panen. Yang ditandai di sana adalah keadaan penanaman induknya, dan judul yang lebih pendek akan terbaca sebagai "catatan panen ini belum lengkap diisi".
  * **Satu data contoh ditambahkan**, penanaman #6 milik POKTAN SUBUR MAKMUR yang belum dipanen. Sebelumnya kelima penanaman sudah dipanen seluruhnya, sehingga lencana `Belum Dipanen` tidak akan pernah tampil di layar mana pun dan ujinya hanya menguji dirinya sendiri. Ditaruh pada SUBUR MAKMUR, **bukan** MEKAR JAYA, sebab lahan tersedia Mekar Jaya sudah dikunci uji peramban pada 3,45 ha.
  * Kartu "Tahun Tercatat" diganti **"Belum Dipanen (ha)"**. Cacah tahun hanya menyatakan seberapa lama sistem dipakai; sisa tanam menyatakan berapa hektare yang masih menunggu panen.
  * Dijaga **6 uji Pest baru pada `DummyDataTest`** dan **4 pada `HalamanTest`**. Yang di HalamanTest menghitung **tautan rincian yang benar-benar terender**, bukan mencari nama poktan: nama poktan juga muncul pada dropdown di dalam modal form, sehingga `assertDontSee` atasnya memerah meski penyaringnya bekerja benar. Ditambah pemeriksaan bahwa tanpa penyaring keenam barisnya tampil, agar uji di atas tidak lolos hanya karena halamannya kosong.
  * **Dibuktikan lewat tiga mutasi**, seluruhnya memerah: ambang sisa dinaikkan mustahil, pemeriksaan keberadaan baris panen dicabut, dan `BelumDipanen` dikembalikan ke penyaring panen.
  * Diperiksa di peramban sungguhan, sebab dua hal tidak dapat dijawab uji string: lencananya **benar-benar terlihat** (6 baris di Penanaman, 5 di Hasil Panen), dan laci filter yang kini lima kolom **tetap muat tanpa gulir mendatar**. Terverifikasi pula bahwa `/penanaman` menawarkan tiga opsi sedangkan `/panen` hanya dua.
  * Diperiksa juga di luar uji: **564 uji Pest** hijau, **197 alamat statis** seluruhnya 200, dan **183 pemeriksaan peramban** pada sebelas berkas tetap hijau.
  * Dokumen ikut diselaraskan: `rules.md` 7d.11 sampai 11d; kamus data 9.2 (tabel angka terhitung dan seksi status panen) beserta 11.33a.
- [done] Coba cek lagi pada halaman detail Penanaman dan halaman detail Hasil Panen. Ada beberapa informasi yg belum ditampilkan, padahal ada datanya dari inputan form yg diberikan.
  * **Delapan temuan, dan DUA di antaranya cacat nyata - bukan sekadar kurang tampil.**
  * **Total volume pada rincian penanaman SELALU 0,00.** Barisnya menjumlahkan kunci `volume` yang sudah dihapus pada perombakan 2026-08-22. Perombakan itu tidak menyisir halaman ini, dan uji yang ada memeriksa keberadaan string alih-alih kebenaran angka - persis pola yang sudah dua kali tercatat (b799 dan 1d.2). Uji penggantinya karena itu **membandingkan angka**, bukan mencari teks.
  * **Dua kolom tertukar pada tabel panen.** Header berbunyi "Hasil Panen" lalu "Produksi", sedangkan selnya mencetak produksi lalu hasil panen: angka TON tampil di bawah judul HEKTARE. Diperbaiki beserta satuan yang kini ikut dicetak, agar tertukarnya kolom terlihat mata alih-alih menjadi angka yang diam-diam salah.
  * **Isian yang tidak punya tempat tujuan.** `keterangan` dan `dokumen_pendukung` SUDAH tercantum pada kamus data 9.3 dan SUDAH punya isian di form, tetapi tidak pernah ada pada `hasilPanen()`. Halaman rincian membacanya lewat `?? '-'`, sehingga selalu bertuliskan "-" tanpa pernah memerah: petugas mengetik catatan, menekan simpan, dan catatannya lenyap tanpa pesan apa pun. Bukan kolom baru, melainkan janji dokumen yang belum ditepati kode.
  * Sisanya: periode tanam dicetak `d F Y` sehingga memunculkan tanggal "01" yang berasal dari imbuhan Carbon dan bukan dari pendataan; label "Tanggal tanam" yang tertinggal; Belum Ditanam dan dokumen yang tidak pernah tampil; "Setara ton" tercetak dua kali; dan satu `<div class="mt-4">` KOSONG sisa kualitas yang dicabut.
  * Ditambahkan pula **identitas luas tertulis terang-terangan** pada tab panen (`dipanen + puso + belum dipanen = realisasi tanam`) beserta Realisasi Tanam sebagai penyebut pada rincian panen. Tanpa penyebutnya, ketiga angka luas melayang tanpa acuan dan pembaca tidak dapat memeriksa sendiri.
  * **Satu jebakan Blade tercatat:** `@if` yang menempel langsung pada teks (`asalnya@if`) tidak dikenali dan melempar ParseError yang menunjuk **akhir berkas**, bukan baris yang keliru. Kalimatnya dirakit di blok `@php` biasa. Jebakan serupa sudah pernah tercatat pada bentuk ringkas `@php(...)`.
  * **Satu jebakan cache tercatat:** setelah mutasi dikembalikan, uji tetap memerah sebab Blade masih memakai view terkompilasi milik mutasinya. `php artisan view:clear` wajib dijalankan sebelum menyimpulkan uji yang memerah setelah mutasi pada berkas blade.
  * Dijaga **6 uji Pest baru**, dibuktikan lewat **tiga mutasi**: penjumlahan ton dinolkan, urutan kolom dibalik, dan kunci dokumen dicabut. Seluruhnya memerah.
- [done] Dengan adanya perombakan total di semua menu pertanian, termasuk rekap hasil panen, apakah ada yg terdampak dari grafik yg ditampilkan pada dashboard? Coba cek dan apakah sudah sesuai dengan hasil perombakan semua menu pertanian.
  * **Jawabannya: struktur dashboard TIDAK rusak, tetapi audit menemukan satu cacat nyata di tempat lain.** Seluruh berkas blade disisir terhadap kunci yang dihapus perombakan - `volume`, `kualitas`, `tanggal_panen`, `musim`, `petani` - dan hasilnya **nol kemunculan**. Keempat angka dashboard juga masih konsisten pada 1.847,5 ton.
  * **Sebabnya pemisahan sumber yang sudah diputuskan 2026-08-18.** Dashboard bersumber agregat kawasan yang berdiri sendiri, bukan diturunkan dari `hasilPanen()`. Perombakan menyentuh tabel transaksi, sehingga tidak menjalar ke sana. Halaman rincian SP pun sudah ikut dirombak dan memakai `poktan` beserta `periode_panen`.
  **Cacat nyata: dua komoditas tampil seolah belum pernah panen.**
  * `/komoditas` menampilkan tanda hubung pada kolom Volume Tercatat untuk **KACANG TANAH dan UBI KAYU**, padahal keduanya tercatat 118,4 dan 68,2 ton.
  * Penyebabnya `ucfirst(mb_strtolower(...))` yang hanya mengapitalkan huruf **pertama**, sehingga `KACANG TANAH` dicari sebagai `Kacang tanah`. **Hanya nama satu kata yang kebetulan berhasil**, dan itulah yang membuatnya lolos pemeriksaan sepintas selama ini.
  * Yang berbahaya bukan salah fungsinya, melainkan **kegagalannya senyap**: `?? null` mengubah kunci tak ketemu menjadi tanda hubung, dan tanda itu terbaca "belum ada panen" padahal artinya "kodenya tidak menemukan datanya". Dua keadaan yang berbeda ditampilkan sama - pola yang sudah tercatat pada 1b.6a.
  * **Diperbaiki di sumbernya**, sesuai pilihanmu: kunci `sebaranKomoditas()` diseragamkan menjadi huruf kapital sesuai data master. Angkanya TIDAK diubah, sehingga keempat angka dashboard tetap konsisten. Normalisasi huruf di dua berkas karena itu tidak lagi perlu menebak.
  * `'Lainnya'` sengaja dibiarkan berhuruf judul: ia bukan komoditas, melainkan penampung sisa yang memang tidak ada pada data master.
  * Pencocokan tak peka huruf pada `form.blade.php` **dipertahankan** sebagai jaring pengaman, tetapi alasannya diperbaiki: yang dijaga bukan lagi ketidakcocokan sumber, melainkan nama komoditas yang baru diketik petugas dan belum tentu berhuruf kapital saat form dirender.
  **Empat indikator produksi ditambahkan (indikator 17).**
  * Realisasi tanam, hasil panen, puso, dan produktivitas sebelumnya **tidak terwakili sama sekali** di dashboard. Yang tampil hanya volume panen, sehingga pembaca tahu berapa ton dihasilkan tetapi tidak tahu dari berapa hektare, berapa yang gagal, dan berapa yang masih menunggu.
  * **Angkanya agregat kawasan baru, bukan dihitung dari `penanaman()`**, sesuai pilihanmu. Alasannya skala: transaksi contoh hanya 7,05 ha sedangkan luas lahan kawasan 3.250 ha. Menyandingkan keduanya membuat dashboard menyatakan kawasan berisi 1.140 keluarga nyaris tidak menanam apa pun - jebakan yang sudah tercatat pada catatan 2026-08-18.
  * **Urutan penyusunan angkanya disengaja:** produktivitas ditetapkan lebih dulu sebagai angka yang wajar bagi kawasan berbasis jagung, lalu luas panen DITURUNKAN darinya. Menetapkan luas lebih dulu menghasilkan produktivitas berkoma panjang yang tampak seperti hasil pengukuran, padahal justru angka sisa pembagian.
  * Kedua identitas dijaga uji: `realisasi tanam = hasil panen + puso + belum dipanen` dan `volume panen = hasil panen x produktivitas`. Tanpa itu, dua kartu yang bersebelahan dapat saling membantah tanpa ada yang menegur.
  * **Angka mutlak diberi porsinya.** Puso 24,60 ha tidak dapat dinilai pembaca tanpa penyebut: terdengar kecil bagi kawasan 3.250 ha, padahal yang menentukan adalah luas yang benar-benar ditanam - 3,9%.
  * Diletakkan di **Ringkasan Kawasan**, bukan bagian Pertanian: bagian itu berisi `chart-card` bergrid tiga kolom, dan menyisipkan kartu statistik ke sana memecah polanya. Empat kartu tepat satu baris, memenuhi `ui-spec.md` 9 poin 11.
  * **Perlu ditegaskan: angka-angka ini KARANGAN**, sama seperti dua belas agregat kawasan yang sudah ada sejak awal. Yang dijaga hanya konsistensi aritmetikanya, bukan kebenaran lapangannya. Begitu Task 9.1 berjalan, seluruhnya diganti kueri nyata.
  **Kartu volume panen menyebut tahunnya.**
  * Label "Volume Panen Tahun Ini" memakai angka tetap yang **kebetulan** cocok karena deret berakhir pada tahun berjalan. Begitu tahun berganti, labelnya berbohong tanpa ada yang menegur - sifat cacat yang sama dengan baris total rekap yang baru diperbaiki.
  * Diganti "Volume Panen 2026", dibaca dari **tahun terakhir deret** dan bukan `date('Y')`. Yang dapat dijamin benar adalah "angka ini milik tahun terakhir yang terdata"; menyebutnya tahun berjalan menjanjikan hal yang belum tentu benar.
  **Satu jebakan format tercatat.** `round()` menghasilkan `19.5` bertitik, sedangkan seluruh angka lain di dashboard memakai koma. Satu angka bertitik di antara puluhan angka berkoma terbaca sebagai kekeliruan cetak. Diganti `number_format` dan dijaga uji tersendiri.
  * Dijaga **7 uji Pest baru**, dibuktikan lewat **empat mutasi**: satu kunci komoditas dikembalikan ke huruf judul, puso ditimpangkan, produktivitas ditimpangkan, dan label kartu dikembalikan ke "Tahun Ini". Seluruhnya memerah.
  * Diperiksa di peramban sungguhan, sebab keterlihatan dan kegenapan grid tidak dapat dijawab uji string: **empat baris kartu masing-masing berisi tepat empat kartu tanpa kolom menganggur**, ketiga indikator produksi terlihat, dan kolom Volume Tercatat kini terisi untuk kelima komoditas.
  * Diperiksa juga di luar uji: **584 uji Pest** hijau, **197 alamat statis** seluruhnya 200, dan **183 pemeriksaan peramban** pada sebelas berkas tetap hijau.
  * Dokumen ikut diselaraskan: `ui-spec.md` 9 (indikator 17 beserta alasannya) dan `rules.md` 9.8g-8i. Kamus data TIDAK disunting: agregat dashboard bukan tabel, sehingga memang tidak punya tempat di sana.

- [done] Coba kamu cek di halaman rekap panen, kenapa cuma ada 1 data saja ya?
  * **Gejalanya sepele, biangnya tidak.** Rekap tampak berisi satu baris sebab bawaannya tahun berjalan, sedangkan lima dari enam penanaman bertahun 2025. Tetapi penelusuranmu sendiri yang menemukan sebab sebenarnya, dan ia jauh lebih dalam: **form Hasil Panen membiarkan luas panen kurang dari luas tanam tanpa menagih sisanya.**
  * Kalimatmu yang menutup diskusi: *"Realisasi tanam 2 ha, maka realisasi panennya juga harus 2 ha. Gak bisa 1.5 ha panen, lalu panen lagi 0.5 ha dari realisasi tanam 2 ha tadi."* Satu penanaman hanya bisa satu panen.
  * **PANEN BERTAHAP DICABUT SELURUHNYA**, beserta konsep "belum dipanen". Identitasnya kembali dua suku: `hasil panen + puso = realisasi tanam`, tepat.
  * **Bukti terkuatnya ada di dokumen rujukan sendiri.** Kolom laporan Polri MT.II 2025 berbunyi Realisasi Tanam, Realisasi Panen, Puso, Produktivitas, Produksi - **tidak ada kolom belum dipanen**. Di dokumen yang menjadi dasar seluruh perombakan ini, satu baris memang hanya mengenal tiga angka luas.
  * **Data contoh membuktikan cacatnya secara telanjang.** Penanaman #3 ditanam November 2025, dipanen sebagian April 2026, dan sisa 0,80 ha tercatat "masih berdiri" sampai Agustus 2026. Jagung tidak bertahan sepuluh bulan di lahan; angka itu pencatatan yang menggantung, bukan tanaman yang hidup. Dan **aku sendiri yang mengarangnya** sehari sebelumnya untuk menguji cabang "Dipanen Sebagian".
  * **Sistem tidak pernah punya cara menutupnya.** Form membatasi isian dengan `:max` sisa luas lalu membiarkan petugas berhenti. Tidak ada penutupan musim, tidak ada penagihan. Lencana "Dipanen Sebagian" yang dipasang sehari sebelumnya hanya MEMPERLIHATKAN keadaan itu, tanpa memaksa siapa pun menyelesaikannya.
  **Yang berubah pada form panen.**
  * **Hasil panen dan puso SALING MENGISI.** Jumlah keduanya sudah tertentu, sehingga mengetik salah satunya menentukan yang lain. Petugas yang tahu 0,25 ha gagal tidak perlu menghitung sisanya, dan angka yang tidak menutup luas menjadi mustahil.
  * **Puso naik status dari opsional menjadi WAJIB.** Dialah yang menerangkan mengapa panen kurang dari tanam; tanpa isian itu, selisihnya menggantung tanpa penjelasan.
  * **Gagal total didukung penuh** atas keteranganmu: panen 0 ha, puso menutup seluruh luas. Produktivitas **dilumpuhkan** pada keadaan itu, sebab tidak ada yang ditimbang dan memaksa angka berarti menuntut petugas mengarang hasil. Dipakai `:required` DAN `:disabled` bersama - tanpa yang kedua, isian wajib yang sedang tidak berlaku menahan pengiriman sambil menunjuk elemen yang tampak sehat.
  * **Penanaman yang sudah dipanen tidak lagi ditawarkan** pada dropdown. Menawarkannya berarti mengundang baris kedua yang luasnya terhitung dua kali.
  **Rekap panen: masalah tiga putaran diskusi lenyap dengan sendirinya.**
  * Tanpa sisa menggantung, penanaman selalu tuntas dalam satu peristiwa panen. Pertanyaan "angka panen lintas tahun ditaruh di mana" karena itu **tidak lagi punya kasus** - kedua pilihan yang diperdebatkan menghasilkan angka yang sama persis.
  * **Basis berpindah dari tahun tanam ke TAHUN PANEN**, sesuai aturanmu: yang sudah dipanen masuk tahun panennya dan tidak pernah berpindah lagi; yang belum dipanen mengambang di tahun berjalan sampai panennya tercatat, lalu pindah sendiri begitu tahun berganti.
  * Alasanmu tepat dan sederhana: **ini rekap PANEN, bukan rekap penanaman.** Bentuk lama membuang panen April 2026 dari rekap 2026 hanya karena penanamannya bermula November 2025, padahal timbangannya nyata terjadi tahun itu.
  * Hasilnya pada data contoh: 2025 berisi 1 baris, 2026 berisi 5. Kebalikan dari sebelumnya, dan lebih jujur.
  **Koreksi klaim dokumen.**
  * Kamus data 9.3 menyatakan identitas tiga suku **"terbukti pada 96 baris laporan Polri MT.II 2025"**. Klaim itu terlalu jauh: laporan tersebut tidak punya kolom belum dipanen sama sekali, sehingga yang terbukti hanya identitas dua suku. Suku ketiga adalah tambahan sistem yang lalu disebut sebagai temuan lapangan.
  * **Dicatat sebagai koreksi, bukan dihapus diam-diam.** Ini bentuk lain dari pola yang sudah enam kali tercatat pada 1c, dan menghapus jejaknya menghilangkan pelajarannya.
  **Data contoh dibetulkan.**
  * Penanaman #3 ditutup penuh: 1,20 ha dipanen + 0,80 ha puso = 2,00 ha, dengan keterangan hama tikus.
  * Ditambahkan penanaman #7 milik POKTAN HARAPAN BARU beserta panen gagal totalnya - 0 ha dipanen, 0,50 ha puso, produktivitas nol - agar cabang itu terlihat di layar dan teruji, bukan hanya teori.
  * **Uji menangkap satu kekeliruanku sendiri:** penanaman #7 semula kutaruh di SP Tniumanu, padahal POKTAN HARAPAN BARU bernaung di SP Weain. Uji rantai lokasi `penanaman -> poktan -> SP` langsung memerah.
  * **16 uji lama memerah dan seluruhnya benar** - semuanya menguji perilaku yang sengaja dicabut. Disesuaikan, bukan dilonggarkan.
  * Dijaga uji baru pada kedua berkas Pest beserta perombakan `uji-form-panen.mjs`, dibuktikan lewat **tujuh mutasi**: identitas dua suku ditimpangkan, tahun rekap digeser, contoh gagal total dihapus, penyaringan dropdown dicabut, penyaring status dikembalikan, saling-isi dicabut, dan pelumpuhan produktivitas dicabut. Seluruhnya memerah.
  * Diperiksa juga di luar uji: **584 uji Pest** hijau, **199 alamat statis** seluruhnya 200, dan **188 pemeriksaan peramban** pada sebelas berkas hijau.
  * Dokumen ikut diselaraskan: `rules.md` 9.8c beserta 8c-1 dan 8c-2, 9.9 sampai 9.9d, dan 9.10; kamus data 9.2 (status panen dan lahan kembali), 9.3 (identitas beserta koreksinya), 11.33a, dan aturan integritas nomor 39.
  * **Penyeragaman istilah menyusul pada hari yang sama.** Label kolom luas panen diganti "Hasil Panen" menjadi **"Realisasi Panen"** di form, halaman rincian panen, rincian penanaman, dan rekap - sejajar dengan Realisasi Tanam tepat di atasnya, dan sama persis dengan kolom laporan lapangan. Nama isiannya memang sudah `realisasi_panen` sejak awal; hanya labelnya yang tertinggal.
  * Kata "Hasil Panen" **tetap dipakai** sebagai nama modul, judul halaman, dan label tab. Yang diganti hanya label kolom, sebab yang perlu satu nama adalah satu BESARAN, bukan satu modul.
  * Kolom rekap **"Belum Dipanen (ha)" menjadi "Menunggu Panen (ha)"**. Istilah lama dicabut dari form bersama panen bertahap, dan memakainya kembali di rekap dengan arti yang BERBEDA - bukan sisa panen setengah jalan, melainkan penanaman yang belum disentuh - justru membingungkan. Kunci array `belum_dipanen` sengaja tidak ikut diganti: ia nama teknis, dan yang membingungkan petugas adalah label di layar.
  * **Pertanyaan lanjutanmu layak dicatat:** apakah penanaman yang belum dipanen masih perlu ditampilkan, mengingat keputusan itu lahir saat panen bertahap masih ada. Jawabannya tetap ya, dan alasannya justru MENGUAT - alasan aslinya bukan panen bertahap melainkan "poktan yang menanam tetapi belum panen akan hilang dari rekap", dan kalimat itu tidak berubah sedikit pun. Yang berubah malah membaik: kolomnya dahulu mencampur dua makna, kini tinggal satu.
  * Yang menentukan: tanpa baris itu, **tidak ada satu pun halaman** yang menjawab "berapa hektare sedang menunggu panen per SP". Kartu di halaman Penanaman hanya total seluruh tahun, tidak terpecah per wilayah.
  * Dijaga **1 uji Pest baru** yang menyisir empat halaman sekaligus, dibuktikan lewat **dua mutasi**: istilah lama dikembalikan ke rekap, dan label form dikembalikan. Keduanya memerah.
  * **Satu jebakan Pest terulang dan tercatat lagi:** `toContain($teks, $pesan)` memperlakukan argumen kedua sebagai nilai LAIN yang ikut dicari, bukan keterangan - sehingga pesannya sendiri ikut diperiksa dan uji memerah. Jebakan yang sama sudah pernah kena pada uji sebaran status panen.
  * Diperiksa juga di luar uji: **585 uji Pest** hijau, **199 alamat statis** seluruhnya 200, dan **188 pemeriksaan peramban** hijau.
  * **Satu pelajaran yang layak dicatat:** pertanyaanmu bermula dari gejala tampilan - "kenapa cuma satu data" - dan berakhir mencabut satu konsep yang sudah menyebar ke lima berkas blade, dua enum, dan belasan uji. Gejala tampilan yang tampak sepele kadang satu-satunya jalan masuk menuju cacat model data.
- [done] Tab Per Satuan Pemukiman pada halaman Rekap Hasil Panen, bagaimana kalau ditambahkan filter per komoditas yg masuk dalam rekap tersebut (Misal cabai masuk komoditas namun pada tahun itu tidak ada yg menanam cabai, maka opsi cabai tidak ada di dropdown filter)? Jadi nanti data yg muncul hanya yg dari komoditas tersebut dalam tab satuan pemukian. Begitupun juga pada tab Per Komoditas, bagaimana kalau ditambahkan filter SP? Sehingga data yg ditampilkan nanti hanya dari SP yg dipilih pada tab Komoditas. Sedangkan untuk tab Per Kelompok Tani, bagaimana kalau ditambahkan filter SP dan Komoditas? Sehingga data yg ditampilkan ya sesuai filternya namun pada tab Per Kelompok Tani gitu. Bagaimana menurutmu?
  * **Usulmu diterima seluruhnya, dan alasannya kuat:** tab menentukan baris APA, penyaring menentukan baris MANA. Dua sumbu terpisah, dan justru gabungannya yang menjawab pertanyaan yang selama ini mustahil - "berapa produksi jagung di SP Weain".
  * Penyaring yang dirender **berbeda tiap tab**, sesuai daftarmu: Per SP mendapat penyaring komoditas, Per Komoditas mendapat penyaring SP, Per Kelompok Tani mendapat keduanya. Menyaring SP pada tab Per SP hanya menyisakan satu baris yang sudah terlihat sejak awal, dan kontrol yang tidak berguna sama saja dengan kontrol mati.
  * **Detail yang kau sebut ternyata yang paling menentukan.** Opsi dihitung dari penanaman pada tahun terpilih, bukan dari data master, dan perbedaannya tajam: master memuat 6 SP dan 5 komoditas, sedangkan tahun 2025 hanya punya 1 dari masing-masing. Menawarkan sisanya berarti menyuguhkan pilihan yang DIJAMIN menghasilkan tabel kosong - kontrol mati yang dilarang R-26, bukan tombol yang tidak berfungsi melainkan pilihan yang sia-sia sejak sebelum diklik.
  * **Nilai penyaring yang tidak lagi tersedia DILEPAS beserta pemberitahuannya.** Keadaannya nyata: menyaring CABAI pada 2026 lalu berpindah ke 2025, dan cabai tidak ditanam tahun itu. Tanpa pelepasan halaman tampak rusak; tanpa pemberitahuan petugas mengira penyaringnya yang tidak bekerja.
  * **Baris total ikut menyempit, dan itu wajib dinyatakan.** Dengan penyaring jagung aktif, "Total tahun panen 2026" berisi jagung saja - angka yang dapat disalin ke laporan sebagai total kawasan dan salah. Judul tabel dan baris total karena itu menyebutkan cakupannya, alasan yang sama dengan kewajiban menulis tahun kemarin.
  * Konsekuensi yang perlu disadari: dengan penyaring jagung, kolom "Poktan" pada tab Per SP berarti **poktan yang menanam jagung**, bukan seluruh poktan di SP itu. Konsisten dengan keputusan luas lahan sebelumnya - kalau tidak, pembaca yang membagi Luas dibagi Poktan mendapat angka ngawur.
  * **Satu jebakan Blade tercatat:** `{{ implode(' &middot; ', ...) }}` mencetak entitas HTML apa adanya, sebab `{{ }}` meng-escape isinya. Entitas wajib berada DI LUAR kurung kurawal; diganti `@foreach` yang menuliskan pemisahnya sendiri. Terlihat hanya lewat pemeriksaan peramban - HTML mentahnya tampak benar.
  * Dijaga **5 uji Pest baru**, dibuktikan lewat **empat mutasi**: penyaring komoditas dicabut, opsi diambil dari seluruh tahun, penyaring SP dirender juga di tab SP, dan pelepasan filter dicabut. Seluruhnya memerah.
  * Diukur di peramban sungguhan, sebab lebar formulir tidak dapat dijawab uji string: **tab Kelompok Tani memuat 3 dropdown beserta tombolnya dalam satu baris, seluruhnya dalam layar tanpa gulir mendatar.** Titik tengah pada judul terverifikasi terender benar, bukan sebagai entitas mentah.
  * Diperiksa juga di luar uji: **595 uji Pest** hijau, **202 alamat statis** seluruhnya 200, dan **188 pemeriksaan peramban** hijau.
  * Satu hal yang perlu diingat: penyaring `?sp=` dan `?komoditas=` **tidak dilayani GitHub Pages**, sama seperti `?tahun=`. Di situs terbit hanya bawaan yang terbuka - sejalan dengan 1b.5, bukan kemunduran baru.
  * Dokumen ikut diselaraskan: `rules.md` 9.8l sampai 8o.
- [done] Revisi kolom datatabel pada halaman Rekap Hasil Panen untuk tab Per SP: SP, Poktan (jumlah poktan), Luas Lahan (total luas lahan semua poktan), Realisasi Tanam (ha), Realisasi Panen (ha),	Puso (ha),	Menunggu Panen (ha),	Produktivitas (ton/ha),	Produksi (ton),	Perkiraan Nilai Jual. Oh iya, untuk nilai 2,942 pada kolom produktivitas di row total tahun panen 2026 itu didapat dari mana ya?
- [done] Revisi kolom datatabel pada halaman Rekap Hasil Panen untuk tab Per Komoditas: Komoditas, Poktan (jumlah poktan), Volumen Benih (Kg), Realisasi Tanam (ha), Realisasi Panen (ha),	Puso (ha),	Menunggu Panen (ha),	Produktivitas (ton/ha),	Produksi (ton),	Perkiraan Nilai Jual.
- [done] Revisi kolom datatabel pada halaman Rekap Hasil Panen untuk tab Per Kelompok Tani: Poktan (Sisipkan SP asal di bawahnya), Luas Lahan (total ketua+ anggota poktan), Realisasi Tanam (ha), Realisasi Panen (ha),	Puso (ha),	Menunggu Panen (ha),	Produktivitas (ton/ha),	Produksi (ton),	Perkiraan Nilai Jual.
  * **Ketiganya dikerjakan, dan satu pertanyaanmu menyingkap asumsi yang keliru.**
  * **Angka 2,942** yang kau tanyakan adalah produktivitas TERTIMBANG: 10,151 ton dibagi 3,45 ha luas dipanen. Bukan rata-rata kolom di atasnya - kalau dirata-rata naif hasilnya 1,452, tertarik turun oleh baris yang gagal total dan berproduktivitas nol padahal luas panennya nol pula sehingga tidak seharusnya ikut menimbang. Alasannya kini ditulis sebagai komentar tepat di sebelah kodenya, bukan hanya di `rules.md` 9.8d.
  **Benih wajib lewat saprotan, termasuk swadaya.**
  * Bermula dari kebingunganmu: *"Bukankah itu wajib diisi saat pencatatan saprotan?"* Lalu usulmu sendiri yang menyelesaikannya - **daftarkan saja benih swadaya di saprotan lebih dulu.**
  * **Sistem sudah mendukungnya sejak awal, dan datanya sudah membuktikan.** Enum sumber perolehan memuat `Swadaya`, dan BENIH JAGUNG LOKAL 30 kg pada data contoh sudah memakainya. Yang kurang hanyalah keseragaman.
  * Aturan `rules.md` 7d.8 yang menyatakan bibit swadaya "tidak melalui modul saprotan" karena itu **KELIRU DAN DICABUT**. Aku menerimanya begitu saja tanpa memeriksa - pola yang sudah beberapa kali tercatat pada 1c: cara sistem kebetulan dipakai lalu dianggap batasan lapangan.
  * `saprotan_id` dan `volume_benih` menjadi **WAJIB**, opsi "Tanpa benih tercatat" dihapus. Tiga penanaman swadaya pada data contoh diberi baris saprotannya masing-masing.
  * **Manfaatnya bukan kerapian data semata: benih swadaya kini ikut punya STOK.** Sebelumnya ia seolah tak terbatas - poktan dapat mencatat penanaman sebanyak apa pun tanpa ada yang menegur.
  * **Isian wajib tanpa jalan mengisinya justru berbahaya**, sebab mendorong petugas mengarang entri agar dapat melanjutkan. Karena itu dropdown kosong diganti keterangan beserta **tautan langsung ke form saprotan**, lengkap dengan penjelasan bahwa benih swadaya pun didaftarkan sebagai penyaluran bersumber Swadaya.
  **Kolom rekap per tab.**
  * Ketiga tab kini berbeda kolom keduanya, sesuai daftarmu: **Luas Lahan** pada Per SP dan Per Poktan, **Volume Benih** pada Per Komoditas.
  * Luas lahan sengaja TIDAK ada pada tab komoditas, dan ini bukan penyederhanaan: satu poktan menanam beberapa komoditas, sehingga lahannya akan terhitung berkali-kali dan totalnya melampaui luas kawasan yang sebenarnya.
  * **Luas Lahan hanya mencakup poktan yang menanam pada tahun itu**, sesuai pilihanmu. Sejalan dengan kolom Poktan di sebelahnya, sehingga pembaca yang membagi luas dengan cacah poktan mendapat angka yang masuk akal.
  * Luas lahan dihimpun per POKTAN, bukan per baris penanaman. POKTAN MEKAR JAYA punya beberapa penanaman pada tahun yang sama; menjumlahkannya per baris akan menghitung lahan yang sama berulang.
  * Kolom Poktan dibuang dari tab Kelompok Tani sesuai daftarmu - nilainya selalu satu. **SP asal disisipkan di bawah nama poktan**, sebab nama poktan sendiri tidak menyatakan lokasinya.
  * Urutan Produktivitas dan Produksi ditukar mengikuti daftarmu, pada header, sel, dan baris total sekaligus.
  * Dijaga **5 uji Pest baru**, salah satunya membandingkan **cacah sel header, badan, dan baris total** pada ketiga tab. Penjagaan itu perlu sebab kolom kedua kini berbeda tiap tab, dan baris total yang bergeser satu kolom tidak memerahkan apa pun - kekeliruan yang sudah pernah terjadi saat kolom Musim dicabut.
  * Dibuktikan lewat **empat mutasi**: satu penanaman dikembalikan tanpa benih, luas lahan dirender juga di tab komoditas, sel luas lahan dihapus dari baris total, dan tautan menuntun dicabut. Seluruhnya memerah.
  * **Satu uji lama menjadi kode mati dan itu tertangkap.** Cabang `if (saprotan_id === null)` pada uji volume benih tidak lagi pernah dijalankan setelah data dibetulkan, sehingga uji tetap hijau sambil berhenti menjaga apa pun. Diganti penjagaan sebaliknya: seluruh penanaman WAJIB punya benih.
  * Diukur di peramban sungguhan, sebab lebar tabel tidak dapat dijawab uji string: **10 kolom pada tab SP dan Komoditas, 9 pada tab Poktan, seluruhnya muat tanpa gulir mendatar dan tanpa judul terpotong.**
  * Diperiksa juga di luar uji: **590 uji Pest** hijau, **202 alamat statis** seluruhnya 200, dan **188 pemeriksaan peramban** hijau.
  * Dokumen ikut diselaraskan: `rules.md` 7d.8 sampai 8c; kamus data 9.2 (kedua kolom menjadi wajib beserta koreksi alasannya) dan aturan integritas nomor 32.
- [done] Revisi kolom datatabel pada halaman Penanaman: Ada tambahan 2 kolom, yaitu kolom "Jumlah Anggota" di antara kolom Kelompok Tani dan Komoditas serta kolom "Luas Lahan" di antara kolom Volume Benih dan Realisasi Tanam.
- [done] Revisi kolom datatabel pada halaman Hasil Panen: Kelompok Tani (di bawahnya disisipkan asal SP), Komoditas, Volume Benih, Luas Lahan, Realisasi Panen, Puso, Periode Panen, Produksi, Perkiraan Nilai Jual. Cek dulu apakah kira2 muat tanpa memperjelek tampilan datatabelnya atau gak. Kalau malah memperjelek karena saking banyaknya kolom, amri kita diskusikan lagi kolom mana saja yg menurutmu paling penting untuk ditampilkan.
- [done] Revisi kolom datatabel pada halaman Rekap Hasil Panen untuk tab Per Kelompok Tani: Ada tambahan 1 kolom saja, yaitu "Jumlah Anggota" setelah kolom Kelompok Tani dan sebelum kolom Luas Lahan.
- [done] Coba cek untuk filter pada datatable di halaman Penanaman dan Hasil Panen, apakah bisa 4 dan 3 kolom filter + tombol "Terapkan Filter" yg ada pada 2 halaman tersebut dijadikan 1 line tanpa ada tulisan yg terpotong? Jika gak bisa, ya sudah tidak perlu diubah.
  * **Keempat butir dikerjakan, tetapi pemeriksaannya lebih dulu menemukan cacat yang lebih besar.**
  **CSS terbangun basi tiga hari, dan tidak ada yang menegur.**
  * `public/build` terakhir dibangun **21 Agustus**, sedangkan seluruh perombakan pertanian terjadi 22-24 Agustus. Kelas seperti `grid-cols-5`, `bg-yellow-50` pada peringatan filter dilepas, `w-52`, dan `disabled:opacity-50` pada produktivitas saat gagal total **tidak ada sama sekali** di CSS - elemen itu dirender tanpa gaya.
  * **Yang paling merugikan: pengukuran peramban berhari-hari ini sebagiannya mengukur halaman tanpa gaya lengkap.** Kesimpulan "tabel muat tanpa gulir" dan "grid genap" ternyata diambil dari halaman yang gridnya jatuh ke perilaku bawaan peramban.
  * Butir filtermu justru yang menyingkapnya. Kusangka `lg:grid-cols-5` tidak muat, padahal **kelasnya tidak pernah ada**. Setelah `npm run build`, filter langsung sebaris - `barisFilter: [5]` pada Penanaman dan `[4]` pada Hasil Panen, tanpa satu pun label terpotong.
  * **Jawaban butir keempat karena itu: sudah satu baris, tanpa perlu mengubah apa pun.** Yang perlu diperbaiki bukan tata letaknya melainkan kebiasaan membangun aset.
  > **Jebakan yang dicatat:** uji peramban **tidak sahih bila aset basi**, dan tidak ada yang memerah. `npm run build` wajib dijalankan sebelum menyimpulkan pengukuran tampilan apa pun - sejajar dengan `php artisan view:clear` yang sudah tercatat sebelumnya.
  * **Situs terbit TIDAK pernah terdampak**, dan itu diperiksa bukan diandaikan: `.github/workflows/deploy.yml` menjalankan `npm ci` beserta `npm run build` pada tiap penerbitan. Cacat ini murni lokal.
  * Justru itu pula sebabnya ia lolos begitu lama. `public/build` masuk `.gitignore`, sehingga aset basi **tidak pernah tampak pada `git status`** dan tidak ada yang mengingatkan. Yang terpengaruh hanya pemeriksaan di mesin sendiri - dan pemeriksaan itulah yang kupakai menyimpulkan tampilan.
  **Kolom ketiga halaman.**
  * **Penanaman** menjadi 9 kolom dengan tambahan Jumlah Anggota dan Luas Lahan. Keduanya **dihitung** dari `rekapLahanPoktan()`, tidak disimpan (`rules.md` 7d.3), dan disusun sekali per poktan alih-alih dipanggil ulang tiap baris.
  * **Hasil Panen** dirombak menjadi 10 kolom sesuai daftarmu. **Produktivitas dicabut** atas keputusanmu: nilainya dapat dihitung sendiri dari Produksi dibagi Realisasi Panen yang keduanya tampil di layar, sehingga tidak ada data yang hilang. Ia **tetap ada pada rekap**, sebab di sana agregat tertimbang tidak dapat dihitung ulang pembaca dari dua kolom mana pun.
  * Volume Benih dan Luas Lahan pada Hasil Panen dibaca **lewat `penanaman_id`**, sebab keduanya milik penanaman dan poktan - bukan milik catatan panen. Menyalinnya ke tabel panen berarti dua tempat yang dapat berbeda diam-diam.
  * **Rekap tab Kelompok Tani** mendapat Jumlah Anggota, menjadi 10 kolom. Sengaja hanya di tab itu: pada tab SP dan Komoditas ia menjumlahkan anggota beberapa poktan sekaligus - angka yang benar secara aritmetika tetapi tidak menjawab pertanyaan apa pun.
  * **Volume Benih tidak perlu dikorbankan.** Kau menyiapkan pilihan itu bila kolomnya tidak muat; hasil pengukuran menunjukkan seluruhnya muat tanpa gulir mendatar, tanpa judul terpotong, dan tanpa sel terpotong.
  **Mutasi ketiga menemukan cacat nyata yang lolos uji.**
  * Mutasi yang menghitung jumlah anggota **per baris penanaman** alih-alih per himpunan poktan **tidak memerahkan apa pun**. Diperiksa manual: POKTAN MEKAR JAYA terbaca **9 anggota** padahal beranggota 3, sebab ia punya tiga penanaman pada tahun yang sama.
  * Yang membuatnya berbahaya: **angka 9 tampak wajar sekilas**. Tidak ada yang mustahil pada kelompok tani beranggota sembilan orang, sehingga kekeliruannya tidak akan pernah menarik perhatian.
  * Uji luas lahan yang sudah ada tidak menangkapnya, sebab luas dihimpun terpisah. Ditambahkan penjagaan tersendiri, dan mutasi yang sama kini memerah dengan bunyi `9 is identical to 3`.
  * Dijaga **5 uji Pest baru**, dibuktikan lewat **empat mutasi**: `colspan` penanaman dikembalikan, Jumlah Anggota dirender di semua tab, jumlah anggota dihitung per baris penanaman, dan Produktivitas dikembalikan ke daftar panen. Seluruhnya memerah.
  * **Satu uji sempat memerah karena penyebab yang benar:** "Produktivitas" masih ditemukan pada halaman panen - tetapi berasal dari **form modal** yang memang masih memuat isiannya. Yang dicabut kolom tabelnya, bukan isiannya; ujinya dipersempit ke kepala tabel saja.
  * Diukur di peramban **setelah build**: Penanaman 9 kolom, Hasil Panen 10 kolom, Rekap tab Poktan 10 kolom - seluruhnya muat tanpa gulir mendatar dan tanpa satu pun judul atau sel terpotong.
  * Diperiksa juga di luar uji: **600 uji Pest** hijau, **202 alamat statis** seluruhnya 200, dan **188 pemeriksaan peramban** hijau.
  * Dokumen ikut diselaraskan: `rules.md` 9.8p sampai 8r.

- [done] Sufiks satuan pada form Penanaman menabrak tombol naik-turun (dilaporkan lewat tangkapan layar).
  * Nama penuh **"Kilogram"** menempati sudut kanan yang sama dengan tombol naik-turun bawaan `input[type=number]`, sehingga keduanya bertumpuk dan angkanya sulit dibaca.
  * Diganti **simbol** dari data master (`kg`), bukan disingkat sendiri lewat `substr` maupun daftar tulis tangan - satuan baru yang didata Admin ikut punya singkatan tanpa perlu disunting kode.
  * Keterangan di bawah isian **tetap memakai nama penuh**: ruangnya lapang, dan "Tersisa 197,5 Kilogram" lebih jelas dibaca daripada singkatan.
  * **Isian produktivitas pada form Hasil Panen punya cacat yang sama dan ikut diperbaiki**, malah lebih parah - sufiksnya "Kilogram/ha". Tidak dilaporkan, tetapi ditemukan saat menyisir isian sejenis.
  > **Jebakan yang dicatat, dan ini yang paling berharga:** perbaikan pertama memakai `right-8`, nilai yang tampak masuk akal di antara `right-4` dan `right-10`. Kelas itu **tidak pernah dibangkitkan Tailwind pada proyek ini**, sehingga sufiksnya justru terdorong ke LUAR kotak isian. Markupnya tertulis rapi, halaman membalas 200, dan tidak ada yang memerah.
  * Yang menangkapnya **uji geometri yang baru ditulis pada saat yang sama**: `jarak dari tepi kanan -15px`. Angka minus berarti sufiks berada di luar isian - keadaan yang mustahil terbaca dari markup.
  * Pelajarannya bukan "periksa CSS dulu", melainkan **kelas Tailwind yang tidak dipakai di mana pun tidak ada di CSS**. Menebak nilai yang "sepertinya ada" berisiko sama dengan menulis kelas yang salah ketik.
  * Dijaga **4 pemeriksaan peramban baru** pada dua berkas uji, seluruhnya mengukur GEOMETRI bukan markup: sufiks memakai simbol, dan jaraknya dari tepi kanan menyisakan ruang bagi tombol naik-turun.
  * Dibuktikan lewat **tiga mutasi**: nama penuh dikembalikan pada volume benih, `right-4` dikembalikan, dan nama penuh dikembalikan pada produktivitas. Seluruhnya memerah.
  * Diperiksa juga di luar uji: **600 uji Pest** hijau, **202 alamat statis** seluruhnya 200, dan **192 pemeriksaan peramban** pada sebelas berkas hijau.

- [done] Bisa coba cek rekap kependudukan? Apakah sudah sesuai dengan data yg ada (cek dari form yg disediakan, bukan dari data dummy)? Lalu apakah perlu halaman rekap SP juga?
  * **Jawaban singkat: belum sesuai, dan halaman rekap SP TIDAK perlu.**
  * Form transmigran memuat 14 isian; yang terekap hanya **tiga** - tahun kedatangan, status tinggal, dan pekerjaan. Enam isian lain diketik petugas lalu tidak pernah terlihat kembali sebagai angka: jenis kelamin, pendidikan, daerah asal, jumlah anggota keluarga, pendapatan, dan usia.
  * **Daerah asal yang paling disayangkan.** Ia satu-satunya yang menjawab `dari mana warga berasal` - pertanyaan khas program transmigrasi, bukan pertanyaan kependudukan umum.
  * **Halaman rekap SP tidak perlu, sebab sudah ada dan lebih baik bentuknya.** `/dashboard/sp/{id}` menyajikan 4 kartu statistik beserta 6 tab - jauh lebih kaya daripada satu baris tabel rekap.
  * Tab `Per SP` yang memuat Luas Lahan dan Volume Panen **sengaja dipertahankan** atas keputusanmu, sebagai ringkasan lintas domain per wilayah.
  **Dua tab baru: Daerah Asal dan Pendidikan.**
  * Angkanya **agregat kawasan**, bukan dihitung dari `transmigran()`. Kedua versi kuhitung dan kutunjukkan berdampingan sebelum kau memilih: versi hitungan menghasilkan **8 KK** di sebelah empat tab lain yang menampilkan **1.140 KK**, dan pembaca yang berpindah tab wajar mengira salah satunya rusak.
  * Versi hitungan juga **menghilangkan empat jenjang pendidikan** - Tidak Sekolah, S1, S2, S3 tidak muncul sama sekali sebab kebetulan tidak ada pada 8 baris contoh. Baris yang hilang membuat pembaca tidak dapat membedakan `tidak ada` dari `belum didata`.
  * Lebih jauh, versi hitungan **menyesatkan soal daerah asal**: ia menyatakan Kupang, Belu, dan Malaka sama-sama 25% - kesimpulan tentang asal-usul 1.140 keluarga yang ditarik dari 8 baris karangan. Persis yang dilarang `rules.md` 19a.
  * **Angka agregatnya tetap karangan**, dan itu diakui terang-terangan: Malaka 402, Belu 286, dan seterusnya tidak punya dasar lapangan apa pun. Yang dijaga hanya konsistensi aritmetikanya. Bedanya dengan versi hitungan: yang ini **jujur mengaku karangan** dan sudah punya dua belas saudara di sistem ini, sedangkan versi hitungan **tampak seperti data nyata** padahal sama-sama berasal dari baris karangan.
  * **Pendidikan diurutkan menurut JENJANG, bukan jumlah.** Mengurutkannya menurut jumlah membuat SD mendahului Tidak Sekolah dan pembaca kehilangan bentuk piramidanya. Jenjang tanpa penghuni tetap ditampilkan bernilai nol, ditandai warna redup.
  * Keempat tab yang membagi KK - status, pekerjaan, asal, pendidikan - **wajib bertotal sama** yaitu 1.140. Total yang berlainan berarti salah satu pembagiannya bocor.
  **Temuan tambahan: lima dari enam tab tidak dapat dibuka di situs terbit.**
  * Rekap kependudukan hanya punya kueri `?kelompok=`, tanpa tautan tetap. Kueri **tidak dilayani berkas statis**, sehingga di GitHub Pages hanya tab Tahun yang terbuka.
  * Cacat yang **sama persis** pernah ditemukan pada rekap panen dan diperbaiki (1b.6a), lalu ditiru rekap pengaduan - tetapi kependudukan terlewat. Menambah dua tab tanpa memperbaiki ini berarti enam tab yang lima di antaranya tak terjangkau.
  * Ditambahkan `/kependudukan/rekap/{kelompok}` mengikuti pola yang sudah ada. Alamat lama tetap bekerja. Aturannya kini ditulis umum pada `rules.md` 10a.4d agar berlaku bagi seluruh halaman rekap, bukan diperbaiki satu per satu setiap kali ketahuan.
  * Dijaga **5 uji Pest baru**, dibuktikan lewat **empat mutasi**: total daerah asal ditimpangkan, urutan pendidikan diacak, jenjang kosong dibuang, dan dua tab baru dicabut dari batasan rute. Seluruhnya memerah.
  * **Uji peramban menangkap regresi yang kubuat sendiri.** Setelah rangkaian mutasi, `right-10` pada sufiks satuan tertinggal sebagai `right-4` karena pemulihan berkas yang tidak lengkap. Dua pemeriksaan geometri langsung memerah dengan bunyi `jarak dari tepi kanan 16px`. Tanpa uji itu, cacat yang baru saja diperbaiki akan kembali diam-diam dalam sesi yang sama.
  * Diukur di peramban: **keenam tab muat sebaris** tanpa gulir mendatar, tab aktif tertandai benar, dan kedua tabel baru bertotal 1.140.
  * Diperiksa juga di luar uji: **605 uji Pest** hijau, **208 alamat statis** seluruhnya 200 - naik dari 202, persis enam tab baru - dan **192 pemeriksaan peramban** hijau.
  * **Yang sengaja TIDAK dikerjakan** dan dicatat sebagai kesenjangan yang diketahui, bukan kelalaian: jenis kelamin, usia, dan jumlah anggota keluarga tetap tidak direkap. Pendapatan sudah terwakili lewat kolom Pendapatan Rata-rata pada tab Tahun.
  * Dokumen ikut diselaraskan: `rules.md` 10a.4a sampai 4e, dan `ui-spec.md` daftar rute.

- [done] Kenapa halaman Penanaman lokasi file view-nya dijadikan satu di folder komoditas? Bisa tolong pisahkan agar lebih enak mempelajarinya?
  * **Keberatanmu tepat, dan ini satu-satunya penyimpangan semacam itu di seluruh proyek.** Folder `komoditas/` memuat DUA modul: tiga berkas komoditas dan tiga berkas penanaman, yang belakangan diberi akhiran `-penanaman` agar tidak bertabrakan.
  * Penanaman bahkan **lebih besar** daripada komoditas itu sendiri - 60 KB melawan 31 KB. Ia modul penuh dengan menu, rute, dan kewenangannya sendiri, tetapi menumpang di folder tetangga.
  * **Yang paling menentukan: alamatnya `/penanaman`, bukan `/komoditas/penanaman`.** Foldernya bertentangan dengan alamatnya sendiri. Kuduga peninggalan masa ia masih bernama `riwayat tanam` dan dianggap bagian dari komoditas; setelah perombakan 2026-08-22 ia jelas berdiri sendiri.
  * Dipindah ke `pages/penanaman/` sebagai `index`, `detail`, dan `form` - akhiran `-penanaman` dilepas sebab sudah dinyatakan nama foldernya, persis pola `panen/form.blade.php` yang bukan `panen/form-panen.blade.php`.
  * Dipakai `git mv`, bukan salin-lalu-hapus. Ketiganya punya jejak keputusan panjang pada `git log` - penggantian nama riwayat tanam, pencabutan musim tanam, pencabutan panen bertahap - dan menyalinnya akan memutus riwayat itu.
  * **Rute, nama rute, alamat URL, menu, breadcrumb, dan kewenangan TIDAK tersentuh.** Yang berpindah hanya letak berkas; seluruhnya membaca nama rute, bukan jalur view.
  **Sisir menyeluruh atas permintaanmu: tiga folder lain berisi banyak berkas, tetapi TIDAK menumpang.**
  * `sp/` memuat 10 berkas untuk empat hal, `master/` 10 berkas untuk empat daftar, `pengguna/` 7 berkas untuk tiga hal.
  * Bedanya menentukan: ketiganya **berbagi awalan alamat** - `/sp/inventaris`, `/master/referensi`, `/pengguna/role`. Foldernya mencerminkan alamatnya, dan itu konsisten. Hanya penanaman yang foldernya membantah alamatnya.
  * Karena itu ketiganya **sengaja tidak disentuh**. Memecah `sp/` menuntut keputusan lain lebih dulu - apakah Inventaris dan Fasilitas SP layak menjadi modul mandiri beralamat sendiri - dan itu pertanyaan tentang struktur menu, bukan tentang letak berkas.
  * **Satu uji memerah dan itu benar**: `HalamanTest` mengunci keberadaan ketiga berkas di `views/pages/komoditas/`. Uji itu lahir saat penggantian nama riwayat tanam, untuk memastikan berkasnya benar-benar berpindah. Kini jalurnya disesuaikan, sekaligus **diperluas** - ia memeriksa berkas ada di folder baru DAN tidak tersisa di folder lama, termasuk nama lamanya.
  * **Satu kekeliruanku tercatat:** batas potongan saat menyisipkan uji meleset satu baris, dan `php -l` menangkapnya. Pemulihan lewat `git checkout` lalu mengembalikan SELURUH berkas - termasuk tiga peta jalur yang sudah benar - sehingga ketiganya harus dikerjakan ulang. Pemulihan seberkas penuh terlalu kasar ketika sebagian suntingan sudah benar.
  * Dibuktikan lewat **dua mutasi**: berkas lama dikembalikan ke folder komoditas, dan berkas index dihilangkan dari folder baru. Keduanya memerah.
  * Diperiksa juga di luar uji: **605 uji Pest** hijau, **208 alamat statis** seluruhnya 200, dan uji peramban `uji-form-penanaman` beserta tiga berkas terkait tetap hijau. Ketiga modal form diperiksa benar-benar terender di peramban, sebab Blade tidak menegur `@include` yang salah sampai halamannya dibuka.
