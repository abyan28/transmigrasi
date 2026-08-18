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
| `inventaris_sp` | `inventaris_sp.id_inventaris_sp` → `satuan_permukiman.id_inventaris_sp` | `inventaris_sp.no_sp` → `satuan_permukiman.no_sp` |
| `fasilitas_sp` | `satuan_permukiman.id_fasilitas_sp` → `fasilitas_sp.id_fasilitas_sp` | `fasilitas_sp.no_sp` → `satuan_permukiman.no_sp` |
| `kategori_lahan_sp` | `kategori_lahan_sp.id_kategori_lahan_sp` → `rumah_sp` dan → `lahan_usaha_sp` | `rumah_sp` dan `lahan_usaha_sp` yang menunjuk ke `kategori_lahan_sp` |
| `daftar_anggota` | `daftar_anggota.id_daftar_anggota` → `profil_poktan.id_daftar_anggota` | `daftar_anggota.id_profil` → `profil_poktan.id_profil` |
| `kategori_lahan`, `komoditas` | menunjuk ke `profil_poktan` dan `riwayat_tanam` | dibalik, tabel referensi tidak boleh menunjuk ke tabel transaksi |
| `alsintan`, `infrastruktur_pertanian`, `profil_poktan` | menunjuk ke `pertanian` | dibalik atau `pertanian` dihapus (lihat 1.2) |
| `transmigran` | `transmigran.id_transmigran` → `saprotan`, → `profil_poktan`, → `pengaduan` | ketiga tabel itu yang menunjuk ke `transmigran` |
| `lahan_sp` | `transmigran.id_lahan_sp` → `lahan_sp.id_lahan_sp` | `lahan_sp.id_transmigran` → `transmigran.id_transmigran`. Wajib dibalik karena satu transmigran boleh punya lebih dari satu lahan usaha (lihat keputusan 2026-08-10) |
| `rumah_sp` | `transmigran.id_rumah_sp` → `rumah_sp.id_rumah_sp` | `rumah_sp.id_transmigran` → `transmigran.id_transmigran`, **UNIQUE nullable**. Relasi satu-ke-satu, NULL berarti rumah kosong |
| `user` | `user.id_user` → `transmigran.id_transmigran` dan → `status_penanganan.id_user` | `transmigran.id_user` → `user.id_user`; `status_penanganan.id_user` → `user.id_user` |
| `satuan_permukiman` | `satuan_permukiman.no_sp` → `transmigran.no_sp` | `transmigran.no_sp` → `satuan_permukiman.no_sp` |

**Aturan umum:** sisi "banyak" yang menyimpan FK, bukan sisi "satu".

### 1.2 Tabel `pertanian` kemungkinan tidak diperlukan

`pertanian` hanya berisi tiga kolom FK (`id_profil_poktan`, `id_alsintan`, `id_infrastruktur_pertanian`) tanpa atribut sendiri. Ini tabel perantara yang tidak menambah informasi. Lebih baik:
- `alsintan` menyimpan `id_profil` (poktan pemilik) atau `id_transmigran` (pemilik pribadi),
- `infrastruktur_pertanian` menyimpan `no_sp` dan opsional `id_profil`.

### 1.3 ENUM untuk nama wilayah menghambat replikasi

`satuan_permukiman.nama_sp`, `provinsi`, `kabupaten`, `kecamatan`, dan `desa` memakai ENUM berisi nama wilayah spesifik. Masalahnya:
- menambah desa/SP baru berarti mengubah struktur tabel (`ALTER TABLE`), bukan sekadar menambah baris;
- bertentangan dengan `rules.md` §4a.4 yang mewajibkan struktur wilayah dapat ditambah tanpa mengubah skema agar sistem dapat direplikasi ke kawasan transmigrasi lain;
- `desa` pada `satuan_permukiman` hanya memuat 2 nilai (`Kapitan Meo`, `Weain`), sedangkan pada `profil_poktan` memuat 6 nilai. Tidak konsisten.

**Saran:** buat tabel referensi bertingkat `provinsi`, `kabupaten`, `kecamatan`, `desa`, lalu `satuan_permukiman` cukup menyimpan `id_desa`. Nilai awal diisi lewat seeder.

### 1.4 Tipe data yang perlu dikoreksi

| Kolom | Sekarang | Saran | Alasan |
|---|---|---|---|
| `dokumen_pendukung` (semua tabel) | `BLOB` | `VARCHAR(255)` berisi path file | PRD §16 mewajibkan file disimpan di `storage/app/private/...`, bukan di dalam database. BLOB membuat backup berat dan query lambat. |
| `lahan_usaha_sp.volumen_panen` | `VARCHAR(255)` | `DECIMAL(12,3)` + FK `id_satuan` | Angka harus dapat dijumlahkan untuk dashboard. Presisi 3 desimal agar panen kecil tetap terekam (0,001 ton = 1 kg). Sekaligus perbaiki salah ketik `volumen` → `volume`. |
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
| Nomor KK (§7.3) | belum ada di `transmigran` |
| Jumlah anggota keluarga (§7.3) | belum ada di `transmigran` |
| Status keanggotaan poktan (§7.3) | ada di `lahan_usaha_sp`, sebaiknya dipindah/diduplikasi ke `transmigran` |
| Status penyerahan inventaris (§7.2) | belum ada di `inventaris_sp` dan `fasilitas_sp` |
| Kualitas panen sebagai data terstruktur | ada sebagai `VARCHAR` bebas, sebaiknya ENUM |
| Audit log perubahan data (§8.2) | belum ada tabelnya |
| Timestamps (`created_at`, `updated_at`) | belum ada di seluruh tabel |
| Soft delete | belum ada, padahal data lapangan rawan terhapus tidak sengaja |
| Kolom `password` pada `user` | belum ada, padahal sistem butuh login |
| UNIQUE constraint pada `rumah_sp.id_transmigran` | belum ada, padahal relasi rumah–KK bersifat satu-ke-satu |
| Tabel `riwayat_penghunian` (id_rumah, id_transmigran, tanggal masuk, tanggal keluar, alasan) | belum ada, padahal pergantian penghuni harus tersimpan tanpa menimpa data lama |
| Tabel `satuan` beserta `faktor_ke_ton` | belum ada, lihat bagian 1.4a |

### 1.7 Catatan kecil

- `komoditas` memakai kolom berulang `tipe_komoditas_1..3` dan `nama_komoditas_1..3`. Sebaiknya dinormalisasi menjadi satu baris per komoditas, lalu direlasikan many-to-many ke lahan/poktan. Pola kolom berulang membatasi jumlah komoditas maksimal tiga dan menyulitkan agregasi.
- `pengaduan.kategori_pengaduan` punya nilai `'Lainnya '` dengan spasi di akhir. Perlu dibersihkan.
- `profil_poktan` menyimpan `komoditas` sebagai `VARCHAR` sekaligus `id_komoditas` sebagai FK. Pilih salah satu.
- `satuan_permukiman` menyimpan `id_transmigran`, padahal relasinya satu SP ke banyak transmigran. FK seharusnya ada di `transmigran`.
- `lahan_sp` dan `kategori_lahan_sp` sama-sama memuat ENUM `('Lahan Usaha', 'Lahan Pekarangan')`. Redundan, cukup salah satu.
- Belum ada indeks pada kolom yang sering difilter (`no_sp`, `id_transmigran`, `id_profil`, tanggal). Dashboard akan lambat tanpa indeks ini (`rules.md` §11.7).

---

## 1a. Temuan Tambahan (2026-08-11)

Temuan berikut muncul saat menyusun skema final dan tidak tercakup pada bagian 1. Seluruhnya sudah diterapkan pada `erd.md` §8.2.

### 1a.1 Data panen menempel di tabel lahan (prioritas tinggi)

`lahan_usaha_sp` menyimpan `volumen_panen`, `harga_jual`, `kualitas_panen`, dan `musim_tanam` sebagai kolom biasa. Akibatnya **satu lahan hanya bisa memiliki satu catatan panen selamanya** — panen musim berikutnya akan menimpa data sebelumnya.

Ini bertabrakan langsung dengan:
- PRD §7.6 yang mewajibkan penyimpanan riwayat panen per periode,
- PRD §7.8 yang meminta grafik total volume panen tiap tahun,
- `rules.md` §9.1 yang menyatakan hasil panen harus dicatat per periode.

**Keputusan:** dibuat tabel `hasil_panen` tersendiri, ditaut lewat `riwayat_tanam` (lahan + musim tanam + komoditas). Dengan begitu satu lahan dapat memiliki banyak panen lintas musim dan lintas tahun.

### 1a.2 Tipe GEOMETRY menyulitkan Laravel

`satuan_permukiman.koordinat_lokasi`, `lahan_usaha_sp.koordinat_lokasi_lahan`, `rumah_sp.koordinat_lokasi`, dan `profil_poktan.titik_koordinat_lahan` memakai tipe `GEOMETRY`.

Masalahnya, Eloquent tidak mendukung tipe spasial secara natif. Setiap pembacaan dan penulisan butuh raw query `ST_AsText()`/`ST_GeomFromText()` atau paket pihak ketiga. Padahal kebutuhan sistem hanya menampilkan lintang dan bujur dengan 6 angka desimal (`ui-spec.md` §10), tanpa query spasial apa pun seperti pencarian radius atau perpotongan poligon.

**Keputusan:** diganti dua kolom `lintang` dan `bujur` bertipe `DECIMAL(10,7)`. Presisi 7 desimal setara ketelitian ±1 cm. Bila kelak dibutuhkan query spasial, kolom `POINT` dapat ditambahkan sebagai kolom turunan tanpa membongkar data.

### 1a.3 Tabel `koordinat_lokasi_sp` salah nama dan tidak perlu

Tabel ini berisi empat kolom `TEXT` bernama `Utara`, `Timur`, `Selatan`, dan `Barat`. Isinya sebenarnya **deskripsi batas wilayah** ("berbatasan dengan Desa X"), bukan koordinat. Relasinya ke `satuan_permukiman` bersifat satu-ke-satu wajib, sehingga tabel terpisah hanya menambah satu join tanpa manfaat.

**Keputusan:** dilebur menjadi empat kolom `batas_utara`, `batas_timur`, `batas_selatan`, `batas_barat` pada `satuan_permukiman`.

### 1a.4 Empat tabel untuk satu konsep lahan

`lahan_sp`, `lahan_usaha_sp`, `kategori_lahan_sp`, dan `kategori_lahan` semuanya menggambarkan lahan. Dua di antaranya (`lahan_sp.jenis_lahan` dan `kategori_lahan_sp.nama_lahan`) bahkan memuat ENUM yang identik.

**Keputusan:** digabung menjadi satu tabel `lahan` dengan kolom `jenis_lahan` (Pekarangan/Usaha) dan `kategori_lahan` (Basah/Kering). Kolom khusus lahan usaha — pola tanam, peralatan, kendala — dibuat nullable dan hanya diisi bila jenisnya lahan usaha.

### 1a.5 Field wajib menurut `rules.md` yang belum ada di SQL

| Tabel | Field yang ditambahkan | Dasar |
|---|---|---|
| `transmigran` | `tahun_kedatangan`, `status_tinggal` | PRD §7.8 meminta grafik per tahun; tanpa kolom ini agregasi per tahun mustahil |
| `alsintan` | `jumlah`, `kondisi`, `sumber_perolehan`, `kepemilikan` | `rules.md` §7b.2 |
| `saprotan` | `jenis_saprotan`, `nama_saprotan`, `jumlah`, `satuan_id` | `rules.md` §7c.2 |
| `infrastruktur` | `jenis`, `kondisi`, `sumber_dana`, `lintang`, `bujur` | `rules.md` §10.2–4 |
| `musim_tanam` | `nama`, `tahun`, `tanggal_mulai`, `tanggal_selesai` | Grafik panen per tahun butuh periode terstruktur |
| `inventaris_sp`, `fasilitas_sp` | `status_penyerahan`, `jumlah`, `kondisi` | `rules.md` §4b.4 |
| `pengaduan` | `nomor_pengaduan`, `bidang`, `prioritas`, `judul` | `rules.md` §10b.6–7 |

### 1a.6 Data ketua poktan terduplikasi

`profil_poktan` menyimpan `nama_ketua_poktan` dan `nik_ketua_poktan` sebagai teks, padahal juga memiliki `id_transmigran`. Bila data transmigran diperbarui, salinan teks di poktan menjadi basi.

**Keputusan:** cukup `ketua_transmigran_id` yang menunjuk ke `transmigran`. Nama dan NIK dibaca lewat relasi. Kolom `telepon` dan `email` poktan tetap dipertahankan karena kontak kelompok bisa berbeda dari kontak pribadi ketua.

### 1a.7 Kolom `jumlah_anggota` berpotensi basi

`profil_poktan.jumlah_anggota` disimpan sebagai nilai statis. Nilai ini akan berbeda dari jumlah baris `daftar_anggota` begitu ada anggota masuk atau keluar.

**Keputusan:** kolom dihapus. Jumlah anggota dihitung dari `anggota_poktan` berstatus Aktif memakai `withCount`. Sebaliknya, `transmigran.jumlah_anggota_keluarga` **tetap disimpan** karena sistem memang tidak mendata anggota keluarga satu per satu.

### 1a.8 Kawasan transmigrasi tidak punya wujud di database (prioritas tinggi)

Pada SQL referensi, `satuan_permukiman` langsung menyimpan kolom `desa`, sehingga hierarkinya `provinsi → kabupaten → kecamatan → desa → SP`. Kawasan transmigrasi sama sekali tidak terwakili, padahal ia adalah subjek utama seluruh sistem: "Kawasan Transmigrasi Kobalima Timur" hanya hidup di judul dokumen.

Dua akibatnya:

1. **Kawasan tidak dapat direkap sebagai satu kesatuan.** Untuk menghitung total kawasan, sistem harus mengandaikan bahwa keenam SP tersebut memang milik kawasan yang sama, tanpa dasar data apa pun.
2. **Replikasi ke kawasan lain mustahil**, padahal diwajibkan `rules.md` §4a.4.

Masalah tambahannya, kawasan transmigrasi adalah wilayah **perencanaan** yang memotong batas administratif. Kobalima Timur menaungi 6 SP yang tersebar di **4 kecamatan** berbeda. Hierarki administratif tunggal tidak mungkin mewakili pengelompokan semacam ini.

**Keputusan (2026-08-11):** hierarki dibuat bercabang dua di tingkat kabupaten.

```
provinsi → kabupaten ─┬─ kecamatan → desa ─────┐
                      │  (cabang administratif) │
                      └─ kawasan_transmigrasi ──┴─→ satuan_permukiman
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

Ini melanggar `rules.md` §5 tentang pembatasan data pribadi, sekaligus membuka risiko salah ubah data.

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

Matriks kewenangan `rules.md` §5.1 memberi hak verifikasi (`V`) pada **17 modul**, dan `prd.md` menyebut verifikasi sebagai fungsi utama kedua dinas. Namun pemeriksaan menyeluruh menunjukkan **tidak ada satu pun kolom verifikasi** di seluruh tabel: tidak ada `status_verifikasi`, `diverifikasi_oleh`, maupun `tanggal_verifikasi`.

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

- **`layouts/sidebar.blade.php`** — 25 alamat menu dari `MenuHelper` dipakai mentah sebagai `href`. Dibungkus `url()`. Nilai `path` di `MenuHelper` **sengaja dibiarkan relatif**, sebab dipakai juga untuk membandingkan status menu aktif; mengubahnya menjadi alamat lengkap akan merusak penandaan itu.
- **`components/sim/stat-card.blade.php`** — atribut `url` dipakai mentah. Dibungkus `url()`, dengan pengecualian untuk alamat yang sudah memuat skema.

**Aturan untuk pengerjaan selanjutnya: jangan pernah menulis `href="/sesuatu"` atau `src="/images/..."` secara langsung.** Selalu lewat `route()`, `url()`, atau `asset()`. Bila tidak, tautannya akan rusak di GitHub Pages sementara tetap tampak benar di localhost, dan kesalahan seperti ini tidak tertangkap uji berbasis HTTP.

### 1b.4 Jebakan `public/hot`

Ditemukan saat pengujian: bila `public/hot` ikut terbawa, `@vite` mengalihkan seluruh aset ke `localhost:5173` dan situs terbit **tanpa gaya sama sekali**. Berkas itu dibuat `npm run dev` dan sudah masuk `.gitignore`, tetapi alur kerja tetap menghapusnya sebagai pengaman.

Hal yang sama berlaku saat memakai terowongan Cloudflare: jangan jalankan `npm run dev` selagi demo berlangsung.

### 1b.5 Yang tidak berfungsi pada versi statis

1. **Seluruh tombol simpan, ubah, dan hapus.** 70 rute POST/PUT/DELETE hanya `return back()` tanpa menyimpan apa pun. Pada versi ber-PHP tombol memunculkan pesan; pada versi statis tidak terjadi apa-apa. **Bukan kemunduran**, sebab keduanya sama-sama tidak menyimpan.
2. **Pencarian dan penyaringan tabel.** Memang belum berfungsi sejak awal.
3. **Penyaring dashboard.** Formulir GET yang belum menyaring apa pun.

### 1b.6 Lacak pengaduan dan utang yang ditinggalkan

Halaman lacak semula hanya menerima `?nomor=`, yang tidak dapat dilayani berkas statis. Ditambahkan rute **tautan tetap** `/lacak-pengaduan/{nomor}`, sehingga setiap nomor punya halaman sendiri dan ikut tergilas. Formulir diarahkan ke sana lewat `x-on:submit` bertanda `ponytail:`.

Rancangannya dibuat agar mudah dibongkar:

- Blok PHP di `lacak.blade.php` **tidak diubah logikanya**, hanya ditambah pembacaan `$nomorRute`.
- Bila JavaScript mati, atribut terabaikan dan formulir kembali mengirim GET seperti biasa.
- Kueri `?nomor=` lama tetap bekerja.

**Pada Tahap 8**, ketika controller pengaduan mengambil alih: hapus atribut `x-on:submit` beserta komentar `ponytail:` di atasnya. Rute tautan tetap sebaiknya **dipertahankan**, karena hasil pencarian yang dapat ditandai dan dibagikan tetap berguna pada versi ber-backend.

### 1b.7 Yang harus dilakukan saat backend masuk

Begitu Tahap 3 dan seterusnya berjalan, sistem memerlukan PHP dan basis data yang hidup, sehingga **GitHub Pages tidak lagi memadai**. Yang perlu diputuskan saat itu:

1. **Autentikasi mematikan penggilasan.** Setelah Tahap 3 aktif, halaman yang butuh login akan membalas pengalihan ke `/login`, bukan 200, dan penerbitan gagal. Pilihannya: batasi daftar gilas hanya ke halaman publik, atau hentikan penerbitan statis sama sekali.
2. **Pindah ke hosting ber-PHP.** `prd.md` A9 sudah menetapkan hosting dengan SSL dan cadangan terjadwal. Alur kerja ini dapat dihapus atau dialihkan menjadi penerbitan pratinjau saja.
3. **Yang tetap berguna** meski beralih hosting: penyeragaman `asset()`, `url()`, dan `route()` pada 1b.3, serta kepercayaan pada `X-Forwarded-*` di `bootstrap/app.php`. Keduanya justru **syarat** untuk hosting di belakang reverse proxy.

### 1b.8 Ketergantungan yang perlu diingat

Pengaturan GitHub Pages harus disetel sekali secara manual: **Settings → Pages → Source: GitHub Actions**. Tanpa itu alur kerja berjalan tetapi hasilnya tidak terbit.

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
| 2026-08-10 | Satu transmigran boleh memiliki lebih dari satu lahan usaha | Kondisi lapangan; relasi menjadi one-to-many dan FK dipindah ke tabel lahan |
| 2026-08-10 | Satu rumah hanya dihuni satu KK, dan satu KK hanya menempati satu rumah | Sesuai pola pembagian rumah transmigrasi; relasi one-to-one dengan UNIQUE constraint dua arah |
| 2026-08-10 | Satuan panen ditetapkan per komoditas pada data master, dengan ton sebagai satuan agregasi | Komoditas hortikultura lazim dihitung kilogram, sedangkan jagung dalam ton; konversi dilakukan saat rekap |
| 2026-08-11 | Berkas `.sql` diperlakukan sebagai referensi, bukan skema final; database dibangun ulang dari nol | Ditegaskan oleh user. Skema final dituangkan pada `erd.md` dan `data-dictionary.md` |
| 2026-08-11 | Fondasi antarmuka memakai TailAdmin Laravel (MIT) | Sudah menyediakan layout, sidebar, komponen form, tabel, modal, dan ApexCharts; berlisensi MIT sehingga aman untuk instansi pemerintah |
| 2026-08-11 | Design token ditulis di `resources/css/app.css` lewat blok `@theme` | TailAdmin memakai Tailwind v4 yang meniadakan `tailwind.config.js`. Rencana awal pada `ui-spec.md` §3.1 disesuaikan |
| 2026-08-11 | Font antarmuka Outfit, bukan Inter | Seluruh komponen TailAdmin sudah ditata dengan metrik Outfit; mengganti font akan menggeser tinggi baris di banyak komponen tanpa manfaat sepadan |
| 2026-08-11 | Laravel 12.x di atas PHP 8.2.12 milik XAMPP | PHP di PATH adalah 8.5.8, di luar rentang dukungan resmi Laravel 12 (8.2–8.4). Memakai PHP XAMPP sekaligus menyamakan lingkungan dengan hosting |
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
| 2026-08-11 | Tidak ada pendaftaran mandiri; akun hanya dibuat Admin | Ditegaskan user. Sistem memuat data kependudukan, sehingga pendaftaran terbuka berarti siapa pun dapat membuat akun. Sudah selaras dengan `prd.md` §7.1 ("akun yang diberikan") dan matriks `rules.md` §5.1 |
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
| 2026-08-12 | `jumlah_izin` pada data contoh dikoreksi 120/68/64/32 → **119/68/74/50** | Angka lama tidak pernah dicocokkan dengan tabel izin. Setelah dihitung ulang, tiga dari empat meleset, dan Dinas Pertanian ternyata memegang izin lebih banyak daripada yang tercatat. **Koreksi pertama hari ini (114/63/72/47) juga keliru** karena modul `fasilitas_sp` terlewat; angka 68 milik Dinas Transmigrasi ternyata sudah benar sejak awal |
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
| 2026-08-14 | Cakupan data ``Milik Sendiri`` **dibuang** | Tidak pernah dipakai role mana pun dan hanya ditandai "untuk kebutuhan mendatang" pada dua dokumen. Pilihan yang tak pernah dipakai menambah beban baca tanpa manfaat, dan dapat ditambahkan kembali bila kelak benar-benar diperlukan |
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

---

## 4. Tindak Lanjut yang Disarankan

Poin 1 dan 2 sudah selesai pada 2026-08-11.

1. ~~Susun ulang ERD berdasarkan koreksi pada bagian 1, sebelum menulis migration Laravel.~~ **Selesai** → `erd.md`
2. ~~Buat data dictionary sebagai deliverable Fase 2 (`workflow.md` §2.2).~~ **Selesai** → `data-dictionary.md`
3. Konfirmasi ke tim lapangan: daftar satuan yang benar-benar dipakai per komoditas, untuk mengisi data master `satuan` dan menetapkan satuan baku tiap komoditas. *Sementara memakai Ton, Kuintal, Kilogram.*
4. Konfirmasi apakah lahan pekarangan juga dapat lebih dari satu per KK, atau dipastikan selalu satu. *Saat ini struktur dibuat one-to-many agar fleksibel.*
5. Pastikan penanganan kasus rumah yang ditinggalkan sementara: apakah tetap berstatus Dihuni dengan penghuni terdaftar, atau dilepas menjadi kosong. *Sementara tetap Dihuni, dicatat pada `rumah.catatan_hunian`.*
6. Konfirmasi apakah satu transmigran dapat menjadi anggota lebih dari satu poktan. *Sementara diasumsikan tidak, mengikuti `rules.md` §6.4.*
7. Konfirmasi spesifikasi hosting/VPS target, khususnya versi PHP yang tersedia, sebelum tahap deployment.
8. Putuskan apakah mode gelap bawaan TailAdmin dipertahankan atau dimatikan, setelah uji coba bersama operator lapangan.


## 5. Catatan Ide
- [done] update hirarki. setelah kabupaten itu bukan desa, melainkan kawasan transmigrasi. Jadi nanti dari kawasan transmigrasi bakal menginputkan daftar Satuan Pemukiman (SP) yang ada pada kabupaten tersebut. Nah, nanti informasi desa dan kecamatan itu ada di dalam SP gitu.
- [done] Untuk lebih lengkapnya, hirarkinya kira-kira begini: Provinsi --> Kabupaten --> Kawasan Transmigrasi X --> Satuan Pemukiman (SP). Di mana nanti untuk SP memuat informasi seperti: Nama SP, kecamatan SP, desa SP, koordinat SP, Inventaris SP, dan fasilitas SP.
- [done] rute aplikasi sim transmigrasinya pindah ke folder sistem informasi transmigrasi, jadi bukan dalam folder sistem informasi transmigrasi lalu buat folder app dan menaruh semua folder dan file proyeknya di dalam folder app.
- [done] apakah sudah ada halaman login dan lupa password? Oh iya, ini gak ada halaman signup ya, sebab nanti untuk pembuatan akun ada di role admin di manajemen akun. Bagaimana menurutmu untuk sistem ini?
  * Keputusan: tanpa pendaftaran mandiri, tanpa pemulihan lewat surel. Kredensial dua jenis (email untuk dinas, NIK untuk transmigran). Rincian pada `rules.md` §14b.
- [done] bahas role kira2 apa saja
  * Keputusan: role dinamis lewat tabel `role`, `permission`, `role_permission`. Empat role bawaan: Admin (terkunci), Dinas Transmigrasi, Dinas Pertanian, Operator SP. Role Transmigran dan Ketua Poktan dihapus, pengaduan warga lewat kanal publik tanpa login. Rincian pada `rules.md` §5.
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
  * **Enam modul sengaja dikecualikan.** Pengaduan datang dari kanal publik satu per satu dan nomornya wajib memuat bagian acak sehingga tidak dapat disiapkan di Excel; Pengguna tidak diberi impor sebab kata sandi awal diserahkan langsung kepada orangnya (`rules.md` 14b poin 3) dan impor massal berarti kata sandi berkeliaran di berkas yang berpindah tangan; Role, Kawasan, SP, dan Musim Tanam jumlah barisnya sedikit sehingga impor hanya menambah jalur masuk tanpa manfaat.
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
  * **Nilai `Milik Sendiri` dibuang** atas permintaan. Nilai itu tidak pernah dipakai role mana pun dan hanya ditandai "untuk kebutuhan mendatang" di dua dokumen. Pilihan yang tidak pernah dipakai hanya menambah beban baca tanpa manfaat.
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
    * Yang disaring bukan sekadar huruf. ``type="number"`` bawaan peramban **sudah** menolak huruf, sehingga menulis ulang penolakan itu tidak menambah apa pun. Yang lolos justru ``e`` (notasi ilmiah), ``+``, ``-``, dan **tempelan teks** — ketiganya membuat isian tampak wajar tetapi terkirim sebagai nilai tak terduga.
    * Isian berlangkah pecahan (luas lahan, tonase panen) tetap menerima koma desimal; tahun dan jumlah unit tidak. Pembedanya diambil dari atribut ``step`` yang sudah ada, bukan dari daftar nama isian yang harus dirawat.
    * **Ini lapisan kenyamanan, bukan pengaman.** Validasi sisi server tetap wajib dan tidak dikurangi sedikit pun; penjaga ini hanya mencegah petugas mengisi satu form penuh sebelum tahu isiannya ditolak.
  - [done] admin kan tidak bisa dihapus. di halaman pengguna, khusus untuk role admin ada tampilan (admin terakhir). Nah, bagaimana kalau itu dihapus dan dihilangkan saja tombol nonaktifnya gitu.
    * Label ``(admin terakhir)`` pada baris tabel dihapus, tombol nonaktifkan ikut ditiadakan untuk baris tersebut.
    * **Keterangan di bawah tabel sengaja dipertahankan.** Tanpa label, hilangnya tombol pada satu baris menjadi tidak dapat dijelaskan; petugas akan mengira tampilannya rusak. Perbedaannya: label mengulang informasi di setiap baris, sedangkan keterangan menjelaskan aturannya satu kali.
    * Aturan yang ditegakkan tetap sama — sistem menolak penonaktifan Admin aktif terakhir. Yang berubah hanya cara menyampaikannya.
  - [done] kata-kata "modul" di sistem ini bagaimana kalau diubah jadi "fitur"? Atau mungkin ada padanan kata lain yg formal namun lebih umum?
  - [done] Diksi "izin" pada fitur hak akses, bagaimana kalau diubah jadi "hak akses"? Soalnya kok agak kurang cocok memakai diksi izin gitu.
    * Kedua permintaan dikerjakan bersama sebab menyentuh teks yang sama. "modul" → **fitur**; "izin" → **kewenangan**.
    * **"hak akses" sengaja tidak dipakai** meski itu yang diminta. Menu induknya sendiri bernama *Role dan Hak Akses*, sehingga memakai istilah itu untuk salah satu isinya membuat pembaca mengira keduanya hal yang sama. Sistem ini memisahkan **kewenangan** (boleh melakukan apa) dari **cakupan data** (boleh melihat data siapa) — keduanya adalah hak akses, jadi istilah itu terlalu luas untuk menamai salah satunya.
    * **Nama di dalam kode tidak ikut diubah.** Tabel ``permission``, kolom ``permission.modul``, dan parameter rute ``{modul}`` mengikuti konvensi Laravel serta menyentuh skema dan URL; menggantinya adalah migrasi basis data demi kata yang tak pernah dibaca pengguna. Batas ini dicatat pada ``ui-spec.md`` butir 8.
    * **"Izin lokasi" pada peta juga tetap**, sebab itu istilah dialog peramban (Geolocation API). Menggantinya membuat pesan sistem tidak cocok dengan yang benar-benar dilihat pengguna.
    * Uji penjaga memeriksa **teks terender**, bukan berkas sumber — atribut seperti ``type="module"`` memang harus tetap teknis. Diuji lewat mutasi: mengembalikan satu judul ke "Izin per Modul" membuat uji merah.
  - [done] Nama "Budi Santoso" yg terdapat di sistem ini ganti dengan "Nara Wijaya".
    * Diganti di seluruh rujukan, termasuk bentuk kapital ``NARA WIJAYA`` pada avatar dan inisial ``NW`` yang sebelumnya ``BS``. Inisial adalah yang paling mudah terlewat sebab tidak memuat nama aslinya.
  - [done] toggle role aktif di floating modal pada halaman role bagaimana kalau dihapus saja?
    * Dihapus. Alasan yang menguatkan: status aktif sudah dapat diubah dari daftar role, sehingga modal menyediakan jalan kedua untuk hal yang sama — dan dua tempat mengubah satu nilai adalah sumber kebingungan, bukan kemudahan.
  - [done] Buat highlight color di halaman role pada bagian isi dari Jumlah Izin, Jumlah Akun, Dapat Dihapus. Sekalian rename "Dipakai akun" -> "Jumlah Akun"
    * "Dipakai akun" → **Jumlah Akun**; "Jumlah Izin" ikut menjadi **Jumlah Kewenangan** menyesuaikan istilah baru.
    * Warna dipilih menurut **makna, bukan sekadar pembeda**: Dapat Dihapus memakai hijau/abu sebab menyatakan boleh-tidaknya suatu tindakan, sedangkan dua kolom jumlah memakai warna netral — angka yang besar tidak berarti baik atau buruk, dan mewarnainya seolah begitu akan menyesatkan.
  - [done] "email dinas"/"surel dinas" ganti jadi "email" saja.
    * Diganti pada label, placeholder, dan teks bantuan. Sejalan dengan ``ui-spec.md`` yang sudah lebih dulu memutuskan **email** (bukan "surel") sebagai istilah baku sistem ini.
  - [batal] banner "Data contoh. Seluruh angka dan nama pada halaman ini adalah contoh untuk keperluan pembangunan tampilan, bukan data lapangan yang sebenarnya." dihilangkan saja.
    * **Dibatalkan atas keputusan pemilik proyek** setelah konflik dilaporkan; banner tetap apa adanya.
    * Banner ini bukan hiasan melainkan **kewajiban aturan proyek**: ``rules.md`` R-17/R-38 mensyaratkan setiap halaman menampilkan penanda "Data contoh" selama tahap dummy, agar angka di layar tidak disalahartikan sebagai data lapangan.
    * Cakupan penghapusan karena itu jauh lebih luas dari satu blok Blade: mengubah ``rules.md`` dan ``ui-spec.md``, membongkar tiga uji penjaga (``HalamanTest.php`` b53 dan b1009, ``DummyDataTest.php`` b235), serta mencatat ulang ``delivery-gate-gelombang-1.md`` yang memakai penanda ini sebagai bukti lolos audit R-38.
    * Bila kelak ingin dikerjakan, jalan yang paling murah adalah **meringkasnya menjadi lencana kecil di header** — kewajiban tetap terpenuhi tanpa memakan ruang tiap halaman. Penghapusan total sebaiknya menunggu ``MEMAKAI_DATA_CONTOH`` menjadi ``false``, sebab saat itu banner hilang dengan sendirinya tanpa menyentuh aturan apa pun.

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
- Batas utara, timur, selatan, barat pada form tambah dan ubah di halaman SP dihapus saja.
- Bagaimana kalau topik pengaduan warga bisa ditautkan ke inventaris, fasilitas, dan infrastruktur SP? Begitupun juga ditautkan ke poktan, alsintan, saprotan, hasil panen, rumah transmigran? Mungkin ini yg menautkannya admin? Atau masyarakat?
- [done] Tambah dokumen pendukung pada form tambah dan ubah pada halaman Satuan Permukiman, inventaris, fasilitas, infrastruktur, kelompok tani, alsintan, saprotan
  * **Bukan pekerjaan baru, melainkan komponen yang belum dipasang.** Kedelapan kolom dokumen sudah lama tercatat pada `data-dictionary.md` (3.6, 4.1, 4.2, 8.1, 8.3, 8.4, dan dua kolom pada 10.1), dan `x-sim.file-upload` sudah matang serta terpakai di lima form lain. Yang tidak ada hanya isiannya.
  * Akibat sebelumnya nyata: **SK pembentukan poktan dan berita acara penyaluran saprotan tidak dapat diunggah ke mana pun**, padahal justru keduanya yang paling sering diminta saat pemeriksaan.
  * **Infrastruktur diberi dua isian terpisah**, `foto` dan `dokumen_pendukung`, mengikuti kamus data 10.1. Keduanya menjawab hal berbeda: foto merekam kondisi lapangan saat pendataan, dokumen menyimpan berkas administratifnya. Menggabungkannya membuat foto kondisi tertimpa dokumen pengadaan.
  * `enctype="multipart/form-data"` sudah ada pada `x-sim.modal-form`, sehingga berkas benar-benar terkirim. Dijaga uji tersendiri, sebab kegagalannya berlangsung diam-diam: form tetap tersimpan, hanya berkasnya yang hilang.
- untuk dokumen lahan, bagaimana kalau ditambahkan di form tambah lahan saja? begitupun juga di form ubah data lahan. soalnya agak bingung juga kalau dipisah.
- untuk status unggulan komoditas apakah bisa ditentukan lewat banyaknya komoditas yg ditanam pada suatu kawasan? Atau mungkin lewat hasil panen terbanyak? jadi bukan manual seperti sekarang.
- [done] fitur update anggota poktan di mana ya? apakah sudah ada? lalu apakah anggota poktan bisa dihapus atau cuma bisa status aktif/nonaktif? lalu bagaimana kalau ada case perpindahan anggota gitu? sepertinya pada form tambah dan update pada transmigran gak perlu field anggota kelompok tani, sebab nanti yg menentukan itu apakah nantinya dia masuk ke poktan yg ditambahkan pada halaman poktan.
- [done] pada form tambah anggota poktan, harusnya pada field jabatan itu tidak ada ketua.
- [done] pada form tambah poktan, kenapa opsi ketuanya hanya dari transmigran? padahal realitanya banyak ketua poktan yg bukan dari transmigran. mungkin bisa disiasati sebelum menginput data ketuanya itu dikasih field apakah ketuanya dari transmigran atau bukan. Jika iya, maka nanti field-nya bisa diisi dari data transmigran yg sudah ada. Jika tidak, maka ya field-nya diisi data non transmigran tersebut.
- [done] pada form tambah poktan, field telepon, email, dan alamat itu diambil dari data ketua, bukan milik kelompok gitu. Jadi jika ketuanya dari transmigran, maka field telepon, email, dan alamat otomatis terisi dari data ketua transmigran yg sudah ada. 
  * **Empat poin ini dikerjakan bersama sebab saling mengunci:** keputusan asal-usul ketua menentukan bentuk isian jabatan sekaligus sumber kontak poktan.
  * **Temuan terbesar, dan bukan sekadar preferensi: anggota poktan tidak dapat diubah sama sekali.** Tidak ada tombol edit per baris, tidak ada modal ubah, dan tidak ada rute PUT. Akibatnya `status` keaktifan dan `tanggal_keluar` **tidak pernah dapat diisi** setelah anggota tersimpan, padahal justru keduanya yang berubah belakangan (`rules.md` 7a.4). Ditutup dengan kolom Aksi, modal `formUbahAnggotaPoktan` berpola `:id`, dan rute `anggota-poktan.perbarui`.
  * **Dokumen sempat bertentangan sendiri.** Matriks `rules.md` memberi huruf **H** pada Anggota poktan untuk Admin, sementara catatan 5.1 melarang penghapusan demi menjaga riwayat. Kode memilih larangan, dan kini huruf H dicabut agar dokumen tidak menjanjikan tindakan yang memang tidak ada. Total kewenangan turun 96 menjadi **95**.
  * **Ketua sempat dapat ditetapkan di dua tempat sekaligus:** `poktan.ketua_transmigran_id` dan `anggota_poktan.jabatan = 'Ketua'`, tanpa satu pun validasi silang maupun batas satu ketua per poktan. Nilai `Ketua` karena itu dicabut dari enum jabatan; ketua kini hanya hidup di profil poktan.
  * **Ketua tidak selalu transmigran, dan itu keadaan lapangan yang nyata.** Form kini bercabang lewat `is_ketua_transmigran`: bila ya, dipilih dari daftar agar NIK dan tautan profilnya sahih; bila tidak, `nama_ketua` dan `nik_ketua` diketik langsung. Data contoh POKTAN HARAPAN BARU sengaja dibuat berketua non-transmigran agar cabang kedua ikut terlihat saat peninjauan.
  * **Kontak poktan ternyata sudah lama menjadi kontak ketua di dalam kode**, hanya dokumennya yang menyebut lain. `DummyData` sejak awal memakai `nama_ketua`, `nik_ketua`, `telepon_ketua`, `email_ketua`, sedangkan kamus data menyatakannya milik kelompok dan form mengirim `name="telepon"` tetapi membaca `$data['telepon_ketua']`. Penamaan diseragamkan menjadi `telepon_ketua`, `email_ketua`, `alamat_ketua`.
  * `email_ketua` **menjadi satu-satunya tempat email ketua dapat disimpan**, sebab tabel `transmigran` tidak memiliki kolom email padahal `rules.md` 7a.2 mewajibkannya. Telepon terisi sendiri dari data transmigran tetapi tetap dapat disunting, karena petugas kerap memegang nomor yang lebih baru daripada yang tercatat.
  * **Perpindahan anggota dicatat sebagai dua baris**, bukan dengan memindahkan `poktan_id`. Memindahkan baris yang sama akan menghapus jejak keanggotaan di poktan lama seolah tidak pernah ada. Ditambah aturan bahwa seorang transmigran hanya boleh **Aktif di satu poktan**; UNIQUE `(poktan_id, transmigran_id)` tidak menangkap ini sebab poktannya memang berbeda.
  * **Field poktan pada form transmigran dijadikan turunan, bukan isian.** Benar seperti dugaan: `status_anggota_poktan` tidak pernah tersinkron dengan `anggota_poktan`, sehingga petugas dapat menyatakan "Ya" tanpa seorang pun mendaftarkannya ke kelompok mana pun. Keanggotaan kini ditetapkan dari sisi poktan saja.
  * **Satu cacat Alpine ikut ditemukan.** Isian status memakai `x-model`, yang menimpa kembali nilai yang disuntikkan modal ubah, sehingga bagian tanggal keluar tidak pernah muncul untuk anggota yang memang sudah berstatus Sudah Keluar. Diganti `x-init` + `@change`.
  * Dijaga 6 uji baru, seluruhnya dibuktikan lewat mutasi.
- [done] tambahkan searchable dropdown pada semua dropdown yg kemungkinan list datanya banyak banget.
  * Dibuat `x-sim.pilih-cari`, dipasang pada **7 isian** yang sumbernya tabel data: ketua poktan, anggota poktan, pemilik alsintan, pemilik lahan, petani panen, penghuni rumah, dan lahan pada riwayat tanam.
  * **Isian sesungguhnya tetap `<select>` biasa.** Kotak pencarian hanya menyaring opsi yang ditampilkan, sedangkan nilai yang terkirim tetap berasal dari elemen bernama sama. Akibatnya Form Request pada tahap backend tidak perlu tahu komponen ini ada, dan halaman tetap berfungsi bila JavaScript gagal dimuat: yang hilang hanya kotak pencariannya, bukan kemampuan memilih.
  * **Kotak pencarian hanya muncul bila daftarnya memang panjang** (ambang 8 opsi). Memasangnya di atas empat pilihan justru menambah satu benda yang harus dilewati, bukan mempercepat. Dengan data contoh sekarang, `/rumah` dan `/riwayat-tanam` sengaja tidak menampilkannya sebab daftarnya masih 4 dan 6 baris.
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
