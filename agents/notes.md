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
| 2026-08-11 | Pemulihan kata sandi lewat Admin, bukan tautan surel | Tidak semua transmigran memiliki alamat surel yang dapat diakses, dan jaringan di lokus tidak selalu memungkinkan penerimaan surel tepat waktu. Tabel `password_reset_tokens` bawaan Laravel tidak dipakai |
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
- di form dropdown pada halaman dahsboard saat di mode dark, warna latar gulirannya putih dan font-nya juga putih, sehingga tidak terlihat.
