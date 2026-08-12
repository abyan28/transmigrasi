# rules.md
## Aturan Pengembangan Sistem Informasi Digitalisasi Monitoring Pertanian dan Tata Kelola Data Kawasan Transmigrasi Kobalima Timur

### 1. Prinsip Umum
1. Sistem harus berbasis web dan dapat diakses melalui browser dan mobile phone.
2. Sistem harus mendukung monitoring pertanian dan tata kelola data kawasan transmigrasi secara terintegrasi.
3. Sistem harus dibuat sederhana, ringkas, dan mudah dipahami oleh operator lapangan.
4. Setiap fitur harus mengutamakan kebutuhan lapangan, bukan sekadar tampilan.
5. Semua modul harus mendukung pengambilan keputusan berbasis data.
6. Pengembangan dilakukan bertahap dengan pendekatan prototype, uji coba, perbaikan, lalu deployment.

### 2. Aturan Teknologi

#### 2.1 Versi yang ditetapkan

| Komponen | Versi | Catatan |
|---|---|---|
| PHP | **8.2.12** (bawaan XAMPP) | Dilarang memakai PHP 8.5 yang ada di PATH sistem, karena berada di luar rentang dukungan resmi Laravel 12 |
| Laravel | **12.x** | |
| Basis data | **MySQL/MariaDB** (XAMPP) | Nama database `sim_transmigrasi` |
| Fondasi antarmuka | **TailAdmin Laravel** (MIT) | https://github.com/TailAdmin/tailadmin-laravel |
| Tailwind CSS | **v4** | Token ditulis di `resources/css/app.css` lewat `@theme`, **bukan** `tailwind.config.js` |
| Alpine.js | 3.x | |
| ApexCharts | 5.x | |
| Vite | 7.x | |
| Font | Outfit | Mengikuti bawaan TailAdmin |

#### 2.2 Aturan umum

1. Framework utama yang digunakan adalah **Laravel**.
2. Struktur sistem mengikuti pola **MVC**.
3. Database yang digunakan adalah **MySQL**.
4. Sistem harus bisa di-host secara daring agar dapat diakses oleh pemangku kepentingan sesuai hak akses.
5. Infrastruktur hosting wajib mendukung:
   - domain atau subdomain,
   - SSL/HTTPS,
   - database terkelola,
   - penyimpanan dokumen/foto,
   - backup terjadwal,
   - log error dasar.
6. Bila beban data meningkat, sistem harus siap ditingkatkan ke VPS/cloud server dengan dukungan caching, queue, dan object storage.
7. Penambahan paket pihak ketiga harus seperlunya. Sebelum memasang paket baru, periksa lebih dulu apakah kebutuhan sudah tertutupi oleh Laravel bawaan atau paket yang sudah terpasang.
8. Berkas `LICENSE` milik TailAdmin wajib dipertahankan di dalam repositori sebagai pemenuhan syarat lisensi MIT.

### 3. Aturan Arsitektur Sistem
1. Struktur modul harus dipisahkan dengan jelas per domain data.
2. Modul inti minimal terdiri dari:
   - autentikasi dan manajemen pengguna,
   - data master wilayah dan satuan permukiman (SP),
   - inventaris SP,
   - fasilitas SP,
   - data transmigran (termasuk keluarga),
   - data rumah dan kondisi hunian,
   - data lahan (lahan pekarangan dan lahan usaha),
   - kelompok tani (poktan) dan daftar anggota,
   - data alsintan,
   - data saprotan,
   - data komoditas,
   - hasil panen dan riwayat tanam,
   - infrastruktur pertanian,
   - penghuni/data kependudukan kawasan,
   - pengaduan dan penanganannya,
   - dashboard monitoring,
   - laporan/export,
   - dokumentasi dan SOP.
3. Setiap modul harus punya alur input, validasi, penyimpanan, pencarian, dan rekap data.
4. Dashboard tidak boleh menjadi sekadar tampilan dekoratif; harus menampilkan indikator utama kawasan.

### 4. Aturan Data dan Basis Data

#### 4.0 Konvensi penamaan dan tipe data (WAJIB)

Skema final ada pada `erd.md`, rincian kolom pada `data-dictionary.md`. Berkas `docs/20260809_T10_22_39.349Z.sql` berstatus **referensi**, bukan skema final; database dibangun ulang dari nol.

| Aspek | Aturan | Contoh |
|---|---|---|
| Nama tabel | Bahasa Indonesia, huruf kecil, `snake_case`, **bentuk tunggal** | `transmigran`, `hasil_panen` |
| Primary key | `id_` + nama tabel | `id_transmigran` |
| Foreign key | nama tabel rujukan + `_id` | `transmigran_id` |
| Kolom boolean | awalan `is_` | `is_unggulan` |
| Koordinat | dua kolom `lintang` dan `bujur` bertipe `DECIMAL(10,7)` | dilarang memakai tipe `GEOMETRY` |
| Nilai uang | `DECIMAL(15,2)` | dilarang memakai `FLOAT` atau `VARCHAR` |
| Luas lahan | `DECIMAL(12,2)`, satuan hektare | |
| Volume panen | `DECIMAL(12,3)` | 3 desimal agar panen 1 kg tetap terekam |
| Dokumen dan foto | `VARCHAR(255)` berisi path berkas | dilarang memakai `BLOB` |
| Nama wilayah | disimpan sebagai baris pada tabel referensi | dilarang memakai `ENUM` berisi nama wilayah |

**Konsekuensi pola PK/FK:** karena berbeda dari asumsi bawaan Eloquent, setiap model wajib mendeklarasikan `protected $primaryKey` dan setiap definisi relasi wajib menyebutkan kunci asing serta kunci lokal secara eksplisit. Contoh:

```php
// Satu transmigran memiliki banyak lahan
public function lahan()
{
    return $this->hasMany(Lahan::class, 'transmigran_id', 'id_transmigran');
}
```

#### 4.0a Pengenal pada alamat URL

Alamat URL **tidak menampilkan primary key berurutan**. Pola berurutan seperti `/transmigran/5` membocorkan perkiraan jumlah data dan memudahkan penelusuran satu per satu.

| Jenis data | Pengenal pada URL | Alasan |
|---|---|---|
| Data pribadi: transmigran, rumah, pengaduan | **UUID** | Tidak dapat ditebak, dan tidak membocorkan identitas seperti yang dilakukan slug bernama |
| Data master: SP, poktan, komoditas | **Slug** | Bukan data pribadi, sehingga keterbacaan lebih berharga. Contoh `/dashboard/sp/kapitan-meo` |
| Data operasional lain: lahan, panen | UUID | Tidak memiliki nama alami yang unik |

**Aturan yang mengikat:**

1. **Primary key integer tetap dipakai di dalam database** untuk relasi antar-tabel, karena lebih ringan sebagai indeks. UUID dan slug adalah pengenal **publik**, bukan pengganti kunci internal.
2. **Slug dilarang diturunkan dari data pribadi.** Nama orang tidak boleh menjadi slug, sebab alamat URL tersimpan pada riwayat peramban, log server, dan terlihat siapa pun yang memandang layar. Untuk data pribadi, slug justru menurunkan kerahasiaan dibandingkan id angka.
3. Slug wajib unik dan tidak berubah setelah dibuat, meski namanya kelak disunting, agar tautan yang sudah dibagikan tidak rusak.
4. Nomor pengaduan publik wajib memuat **bagian acak**, contoh `PGD-2026-0001-K7F2M9`. Nomor berurutan dapat ditebak, dan halaman lacak dapat diakses tanpa login sehingga menjadi permukaan serangan yang nyata.
5. Penggantian ke UUID dilakukan **bertahap**, dimulai dari modul berdata pribadi. Mengubah seluruh modul sekaligus memperbesar risiko tanpa menambah perlindungan yang sepadan.
6. Pembatasan laju melengkapi, bukan menggantikan, pengenal tak tertebak (§14c).

**Aturan tambahan:**
1. Semua tabel memiliki `created_at` dan `updated_at`.
2. Tabel data utama memakai soft delete (`deleted_at`); tabel referensi dan tabel riwayat tidak.
3. Semua tabel memakai `ENGINE=InnoDB`, `CHARSET=utf8mb4`, `COLLATE=utf8mb4_unicode_ci`.
4. Nilai enum tidak ditulis langsung di kode maupun view, melainkan didefinisikan sebagai **PHP Enum** di `app/Enums/` sesuai daftar pada `data-dictionary.md` §11.

#### 4.1 Aturan umum

1. Struktur database dan kamus istilah wajib didokumentasikan dalam **ERD** dan **data dictionary** sebelum implementasi modul dimulai.
2. Data harus tersimpan terstruktur dan saling terhubung.
3. Data utama yang wajib dikelola adalah:
   - wilayah dan satuan permukiman (SP),
   - satuan dan faktor konversi,
   - inventaris dan fasilitas SP,
   - transmigran dan keluarganya,
   - rumah dan kondisi hunian,
   - lahan pekarangan dan lahan usaha,
   - kelompok tani (poktan) dan daftar anggota,
   - alsintan,
   - saprotan,
   - komoditas,
   - musim tanam dan riwayat tanam,
   - hasil panen,
   - infrastruktur pertanian,
   - penghuni/kawasan,
   - pengaduan dan status penanganan,
   - laporan.
4. Setiap data penting harus bisa ditelusuri riwayat perubahannya.
5. Data awal dari desa/SP prioritas harus bisa diinput ke sistem.
6. Data harus bisa diperbarui secara berkala.
7. Data harus bisa dicari, difilter, dan diekspor.
8. Data yang bersifat lapangan sebaiknya dapat dilampiri foto/dokumentasi beserta koordinat lokasi (geotagging sederhana).
9. Desain data harus modular dan menyediakan field fleksibel, karena relasi antar-data dapat berubah saat validasi lapangan.
10. Karena sinyal di lokus tidak selalu stabil, sistem harus menyediakan template isian luring yang dapat diunduh dan diinput/diunggah kembali saat koneksi tersedia.

### 4a. Aturan Data Master Wilayah

1. Data wilayah mengikuti hierarki **bercabang dua** yang berpisah di tingkat kabupaten:

   ```
   provinsi → kabupaten ─┬─ kecamatan → desa ─────┐
                         │  (cabang administratif) │
                         └─ kawasan transmigrasi ──┴─→ satuan permukiman (SP)
                            (cabang program)
   ```

2. **Cabang administratif** mencatat pembagian pemerintahan. **Cabang program** mencatat kawasan transmigrasi, yaitu wilayah perencanaan yang dapat memotong batas kecamatan. Keduanya bertemu di SP.
3. Setiap SP wajib menaut ke **satu desa** dan **satu kawasan transmigrasi** sekaligus. Informasi kecamatan tidak disimpan langsung pada SP, melainkan dibaca lewat desanya.
4. Setiap SP wajib menyimpan: nama SP, desa, kawasan, titik koordinat, batas wilayah Utara/Timur/Selatan/Barat, luas lahan, dokumen pendukung, dan penanggung jawab data. Inventaris dan fasilitas SP dikelola sebagai daftar terpisah yang menempel pada SP (§4b).
5. Lokus awal sistem adalah **Kawasan Transmigrasi Kobalima Timur**, Kabupaten Malaka, Nusa Tenggara Timur, yang menaungi 6 SP tersebar di 4 kecamatan:

   | Satuan Permukiman / Lokus | Desa | Kecamatan |
   |---|---|---|
   | SP Kapitan Meo | Kapitan Meo | Laen Manen |
   | SP Tniumanu | Tniumanu | Laen Manen |
   | SP Harekakae | Harekakae | Malaka Tengah |
   | SP Weoe / Uluk Lubuk | Weoe | Wewiku |
   | SP Tualaran | Naet | Rinhat |
   | SP Weain | Weain | Rinhat |

   Sebaran ini adalah alasan kawasan dipisahkan dari hierarki administratif: satu kawasan menaungi SP di empat kecamatan berbeda, sehingga tidak dapat diwakili oleh struktur administratif saja.

6. Seluruh data operasional wajib tertaut ke **SP**, tidak pernah langsung ke desa maupun kawasan. Rekap per kawasan, kecamatan, atau desa dihitung lewat SP.
7. Struktur wilayah dan kawasan harus dapat ditambah tanpa mengubah skema, agar sistem dapat direplikasi ke kawasan transmigrasi lain. Nama wilayah dan nama kawasan disimpan sebagai data referensi, bukan nilai tetap di dalam struktur tabel.
8. Setiap kawasan transmigrasi menyimpan nama, kabupaten, tahun penetapan, nomor SK, luas total, dan dokumen pendukung.

### 4b. Aturan Modul Inventaris dan Fasilitas SP
1. Inventaris SP dan fasilitas SP dikelola sebagai dua daftar terpisah yang menempel pada satu SP.
2. Setiap entri wajib memuat nama barang/fasilitas, tahun perolehan, dan sumber dana.
3. Sumber dana mengikuti pilihan baku: APBN, APBD Provinsi, APBD Kabupaten, Dinas Transmigrasi Kabupaten, Dinas Pertanian Kabupaten, Lembaga Swadaya Masyarakat, dan Lainnya.
4. Setiap entri wajib mencatat status penyerahan dan dapat dilampiri dokumen pendukung.
5. Inventaris dan fasilitas harus dapat direkap per SP untuk kebutuhan laporan aset kawasan.

### 5. Aturan Hak Akses

#### 5.0 Prinsip dasar

1. Sistem wajib menggunakan **role-based access control** dengan role yang bersifat **dinamis**, bukan dikunci di dalam kode.
2. Role disimpan sebagai data pada tabel `role`, sehingga Admin dapat membuat, mengubah, dan menonaktifkan role lewat antarmuka tanpa mengubah struktur database.
3. Hak akses ditentukan oleh **dua hal yang terpisah**:
   - **Izin** menjawab *boleh melakukan apa*, contoh `transmigran.lihat` dan `transmigran.ubah`,
   - **Cakupan data** menjawab *boleh melihat data siapa*, dengan nilai `Semua`, `Per SP`, atau `Milik Sendiri`.
4. Daftar izin ditanam sistem lewat seeder dan **tidak dapat ditambah atau dihapus Admin**, karena setiap izin harus punya pasangan pemeriksa di dalam kode. Admin hanya memasangkannya ke role.
5. **Seluruh pengguna sistem adalah petugas.** Warga transmigran tidak memiliki akun; data mereka dikelola petugas, sedangkan pengaduan diajukan lewat kanal publik tanpa login (§10b).

#### 5.0a Empat role bawaan

Dibuat lewat seeder sebagai konfigurasi awal agar sistem langsung dapat dipakai. Seluruhnya bertanda `is_bawaan` sehingga tidak dapat dihapus, tetapi izinnya masih dapat disesuaikan kecuali role Admin.

| Role | Cakupan data | Ringkasan kewenangan |
|---|---|---|
| **Admin** | Semua | Akses penuh: kelola pengguna, role, data master, konfigurasi, dan audit log. **Terkunci**, izinnya tidak dapat diubah |
| **Dinas Transmigrasi** | Semua | Pantau dashboard dan laporan kawasan; tambah, ubah, dan verifikasi data wilayah, SP, transmigran, rumah, lahan, dan infrastruktur; tangani pengaduan bidang ketransmigrasian |
| **Dinas Pertanian** | Semua | Pantau dashboard dan laporan pertanian; tambah, ubah, dan verifikasi data poktan, komoditas, panen, alsintan, dan saprotan; tangani pengaduan bidang pertanian |
| **Operator SP** | Per SP | Tambah dan ubah data transmigran, rumah, lahan, dan panen **hanya pada SP yang ditugaskan**. Tanpa izin hapus, tanpa izin verifikasi, tanpa akses manajemen pengguna dan audit log |

#### 5.0b Cakupan data

6. Cakupan data wajib diterapkan sebagai **penyaring query**, bukan sekadar menyembunyikan menu:

   | Cakupan | Penyaring |
   |---|---|
   | `Semua` | tanpa penyaring |
   | `Per SP` | dibatasi SP yang ditugaskan pada tabel `user_satuan_permukiman` |
   | `Milik Sendiri` | dibatasi baris yang terkait pengguna bersangkutan |

7. Akun berrole bercakupan `Per SP` **wajib** memiliki minimal satu penugasan SP. Bila belum ditugaskan, pengguna tidak melihat data apa pun, bukan melihat seluruhnya. Ini disengaja agar kelalaian penugasan tidak berubah menjadi kebocoran data.

#### 5.0c Perlindungan

8. Role Admin tidak dapat dihapus maupun dikurangi izinnya, agar sistem tidak pernah kehilangan jalur administrasi.
9. Role yang masih dipakai minimal satu akun tidak dapat dihapus.
10. Izin `lihat` adalah prasyarat seluruh aksi lain pada modul yang sama. Memberi izin `ubah` tanpa `lihat` ditolak sistem sebagai galat konfigurasi.
11. Setiap perubahan susunan izin sebuah role wajib tercatat pada audit log.
12. Data pribadi transmigran dan penghuni kawasan bersifat sensitif: tampilan penuh hanya untuk role berwenang, sedangkan role lain menerima data dalam bentuk agregat.
13. Pembatasan akses wajib diterapkan di sisi query dan controller, bukan sekadar menyembunyikan menu di antarmuka. Menu yang tidak berhak diakses tidak dirender sama sekali.

#### 5.1 Susunan izin role bawaan

> **Kedudukan tabel ini.** Sejak role menjadi dinamis (§5.0), tabel di bawah bukan lagi aturan permanen yang dikunci di dalam kode, melainkan **konfigurasi awal** yang ditanam seeder. Admin dapat mengubahnya lewat menu Pengaturan Role, kecuali baris role Admin yang terkunci.

Keterangan: **L** = lihat / **T** = tambah / **U** = ubah / **H** = hapus / **V** = verifikasi / **E** = export / **-** = tanpa akses

| Modul | Admin | Dinas Transmigrasi | Dinas Pertanian | Operator SP |
|---|---|---|---|---|
| Manajemen pengguna | L T U H | - | - | - |
| Pengaturan role | L T U H | - | - | - |
| Audit log | L E | - | - | - |
| Data master wilayah | L T U H | L | L | L |
| Kawasan transmigrasi | L T U H E | L V E | L E | L |
| Satuan permukiman (SP) | L T U H E | L T U V E | L E | L |
| Inventaris & fasilitas SP | L T U H E | L T U V E | L E | L T U |
| Data master satuan | L T U H | L | L | L |
| Transmigran | L T U H E | L T U V E | L E | L T U |
| Rumah & hunian | L T U H E | L T U V E | L E | L T U |
| Riwayat penghunian | L T U H E | L T V E | L | L T |
| Lahan | L T U H E | L T U V E | L E | L T U |
| Dokumen lahan (HPL/SHM) | L T U H | L T V | L | L T |
| Kelompok tani | L T U H E | L E | L T U V E | L T U |
| Anggota poktan | L T U H E | L | L T U V E | L T U |
| Alsintan | L T U H E | L | L T U V E | L T U |
| Saprotan | L T U H E | L | L T U V E | L T U |
| Komoditas | L T U H E | L | L T U V E | L |
| Musim tanam | L T U H | L | L T U V | L |
| Riwayat tanam | L T U H E | L | L T U V E | L T U |
| Hasil panen | L T U H E | L | L T U V E | L T U |
| Infrastruktur pertanian | L T U H E | L T U V E | L T U V E | L T U |
| Pengaduan | L T U H V E | L T U V E | L T U V E | L T |
| Penanganan pengaduan | L T U | L T U | L T U | - |
| Dashboard | L E | L E | L E | L |
| Laporan & export | L E | L E | L E | L |

**Cakupan data tiap role:** Admin, Dinas Transmigrasi, dan Dinas Pertanian bercakupan `Semua`. Operator SP bercakupan `Per SP`, sehingga seluruh izinnya otomatis terbatas pada SP yang ditugaskan padanya.

**Catatan penting:**
1. Dinas hanya menangani pengaduan sesuai bidangnya: bidang ketransmigrasian untuk Dinas Transmigrasi, bidang pertanian untuk Dinas Pertanian. Pembatasan ini berlaku pada level query, bukan lewat izin.
2. Penghapusan data utama memakai *soft delete* agar dapat dipulihkan dan tetap tercatat pada audit log.
3. Aksi verifikasi tidak mengubah isi data, hanya menandai bahwa data sudah diperiksa oleh petugas berwenang beserta waktunya (§5.2).
4. Operator SP sengaja tidak diberi izin hapus maupun verifikasi. Ia bertugas memasukkan data, sedangkan pemeriksaan dan penghapusan menjadi kewenangan dinas dan admin.
5. Anggota poktan yang berhenti ditandai berstatus "Sudah Keluar", bukan dihapus, agar riwayat tetap utuh.

#### 5.2 Aturan verifikasi data

1. Verifikasi adalah penandaan bahwa suatu baris data **sudah diperiksa kebenarannya** oleh petugas berwenang. Verifikasi **tidak mengubah isi data**.
2. Status verifikasi memakai tiga nilai: `Belum Diverifikasi`, `Terverifikasi`, dan `Ditolak`.
3. Data yang baru dimasukkan berstatus `Belum Diverifikasi`.
4. **Tidak ada verifikasi otomatis.** Data baru selalu berstatus `Belum Diverifikasi` tanpa memandang izin penginputnya. Petugas dinas yang memasukkan data sendiri pun tetap harus memverifikasinya sebagai tindakan terpisah.
5. Untuk memudahkan, petugas yang memiliki izin verifikasi pada modul bersangkutan mendapat tombol **"Simpan dan Verifikasi"**. Tombol ini menjalankan dua tindakan berurutan dan tercatat sebagai **dua entri terpisah** pada audit log.
6. Alasan aturan 4 dan 5: indikator mutu data pada dashboard hanya bermakna bila mencerminkan pemeriksaan yang benar-benar dilakukan, bukan sekadar status izin orang yang kebetulan mengetik. Verifikasi wajib menjadi tindakan sadar yang dapat dipertanggungjawabkan.
7. Bila petugas menolak, alasan penolakan **wajib** diisi agar operator tahu bagian mana yang perlu diperbaiki.
8. Data yang sudah `Terverifikasi` lalu diubah kembali menjadi `Belum Diverifikasi`, sehingga perubahan diperiksa ulang.
9. Status verifikasi disimpan terpusat pada tabel `verifikasi`, memakai pasangan nama tabel dan id baris.
10. Setiap perubahan status verifikasi wajib tercatat pada audit log, memuat petugas pelaku, waktu, serta status sebelum dan sesudah.
11. Indikator mutu data pada dashboard **hanya menghitung baris berstatus `Terverifikasi`**. Data yang dimasukkan petugas dinas tetapi belum diverifikasi tidak ikut dihitung.
12. Laporan resmi dapat disaring agar hanya memuat data terverifikasi.

**Catatan terbuka: prinsip empat mata.** Sistem saat ini **belum** melarang petugas memverifikasi data yang ia masukkan sendiri. Pembatasan semacam itu lazim dalam tata kelola data, tetapi berisiko menghambat bila tim dinas di kawasan hanya terdiri atas beberapa orang. Bila kelak jumlah petugas memadai, aturan ini dapat diperketat **tanpa mengubah skema**, cukup menambah pemeriksaan di sisi aplikasi dengan membandingkan pelaku verifikasi terhadap penginput asli yang terekam pada audit log.

### 6. Aturan Modul Transmigran
1. Data transmigran harus menjadi data inti sistem.
2. Field minimal yang wajib dicatat:
   - nama kepala keluarga,
   - NIK,
   - nomor KK,
   - jumlah anggota keluarga,
   - pekerjaan kepala keluarga,
   - jumlah pendapatan keluarga per bulan,
   - status keanggotaan kelompok tani.
3. NIK wajib 16 digit dan divalidasi keunikannya; nomor KK divalidasi formatnya.
4. Satu data transmigran harus bisa dikaitkan dengan desa/SP, rumah, lahan, komoditas, dan hasil panen, dengan kardinalitas:
   - satu transmigran dapat memiliki **banyak lahan usaha** (one-to-many),
   - satu transmigran menempati **tepat satu rumah** (one-to-one),
   - satu transmigran dapat menjadi anggota satu kelompok tani.
5. Setiap transmigran dapat dilampiri dokumen pendukung.
6. Data transmigran harus bisa ditambah, diubah, dicari, difilter, dan diekspor.
7. Data transmigran harus mendukung kebutuhan monitoring kawasan dan pendataan awal.

### 6a. Aturan Modul Rumah dan Hunian
1. Setiap rumah wajib tertaut ke SP dan dapat tertaut ke transmigran penghuninya.
2. Data rumah minimal memuat titik koordinat lokasi, kondisi rumah, dan status hunian.
3. Kondisi rumah memakai pilihan baku: Tidak Rusak, Rusak Ringan, dan Rusak Berat.
4. Status hunian memakai pilihan baku: Dihuni dan Tidak Dihuni; bila tidak dihuni wajib diisi alasannya.
5. Relasi rumah dan KK bersifat **satu-ke-satu**: satu rumah hanya boleh dihuni satu KK, dan satu KK hanya boleh menempati satu rumah.
6. Pembatasan tersebut wajib dijaga dengan **UNIQUE constraint pada database**, bukan sekadar validasi form, agar tidak bisa ditembus lewat proses impor atau akses langsung.
7. Kolom penghuni pada rumah bersifat nullable; nilai kosong berarti rumah tidak berpenghuni.
8. Saat menautkan KK ke rumah, sistem hanya menampilkan pilihan rumah yang masih kosong.
9. Pergantian penghuni dicatat sebagai **riwayat penghunian** (tanggal masuk, tanggal keluar, alasan), tidak menimpa data penghuni sebelumnya.
10. Sistem harus menyimpan catatan hunian dan foto rumah.
11. Jumlah rumah terhuni harus dapat direkap per desa/SP untuk kebutuhan dashboard.

### 7. Aturan Modul Lahan
1. Setiap lahan harus memiliki identitas yang jelas.
2. Lahan dibedakan menjadi dua jenis: **lahan pekarangan** dan **lahan usaha**.
3. Data lahan harus dapat dikaitkan dengan transmigran, kelompok tani, dan komoditas.
4. Data lahan minimal memuat informasi luas, lokasi, titik koordinat, status, dan tujuan/jenis pemanfaatan.
5. Kategori lahan usaha dibedakan menjadi lahan basah dan lahan kering.
6. Dokumen status lahan (HPL/SHM) wajib dapat diunggah dan ditautkan ke data lahan.
7. Lahan usaha juga mencatat pola tanam, musim tanam, peralatan/perlengkapan pertanian, dan kendala yang dihadapi.
8. Satu transmigran dapat memiliki **lebih dari satu lahan usaha**, sehingga foreign key disimpan pada tabel lahan, bukan pada tabel transmigran.
9. Lahan pekarangan umumnya satu per KK, tetapi struktur data tetap mengikuti pola satu-ke-banyak agar fleksibel.
10. Rekap luas lahan per transmigran, per poktan, maupun per desa/SP wajib memakai penjumlahan seluruh lahan terkait, bukan mengambil satu baris data saja.
11. Lahan harus bisa dipakai sebagai dasar analisis produksi dan perencanaan.

### 7a. Aturan Modul Kelompok Tani (Poktan)
1. Setiap poktan wajib memiliki profil berisi nama poktan dan desa/SP asal.
2. Data ketua poktan minimal memuat nama, NIK, telepon, dan email.
3. Sistem mencatat jumlah anggota beserta daftar anggota yang berasal dari transmigran.
4. Setiap anggota mencatat nama, NIK, tanggal masuk, status keaktifan (Aktif, Tidak Aktif, Sudah Keluar), dan tanggal keluar bila ada.
5. Poktan dapat ditautkan ke lahan, komoditas, alsintan, dan saprotan.
6. Poktan dapat dilampiri dokumen pendukung.
7. Rekap jumlah poktan dan anggotanya harus tersedia per desa/SP.

### 7b. Aturan Modul Alsintan
1. Sistem membedakan alsintan **milik pribadi transmigran** dan **bantuan pemerintah yang disalurkan melalui poktan**.
2. Setiap alsintan wajib mencatat nama alat, jumlah, tahun perolehan, sumber perolehan, dan kondisi.
3. Alsintan bantuan wajib ditautkan ke poktan penerima; alsintan pribadi ditautkan ke transmigran pemilik.
4. Setiap alsintan dapat dilampiri dokumen pendukung.
5. Alsintan harus dapat direkap per desa/SP, per poktan, dan per jenis alat.

### 7c. Aturan Modul Saprotan
1. Saprotan mencatat sarana produksi pertanian seperti benih, pupuk, pestisida, dan mulsa.
2. Setiap penyaluran wajib mencatat jenis saprotan, jumlah, satuan, dan waktu perolehan.
3. Penerima saprotan dapat berupa kelompok tani maupun individu transmigran, dan wajib ditautkan ke penerimanya.
4. Penyaluran kepada anggota poktan hanya untuk anggota berstatus aktif.
5. Setiap penyaluran dapat dilampiri dokumen pendukung.
6. Saprotan harus dapat direkap per periode, per poktan, dan per desa/SP.

### 8. Aturan Modul Komoditas
1. Sistem harus mendukung komoditas unggulan kawasan, terutama komoditas utama yang disebut dalam proposal, yaitu jagung.
2. Komoditas harus dapat dikaitkan dengan transmigran, poktan, lahan, dan hasil panen.
3. Sistem harus mendukung penandaan komoditas unggulan.
4. Setiap komoditas wajib memiliki **satuan panen baku** yang ditetapkan pada data master, misalnya jagung dalam ton dan cabai dalam kilogram.
5. Komoditas dikelompokkan menurut tipenya: pangan, palawija, dan hortikultura.
6. Komoditas harus bisa dianalisis per desa/SP atau per periode.

### 8a. Aturan Data Master Satuan
1. Sistem menyediakan data master satuan untuk volume panen dan penyaluran saprotan.
2. Setiap satuan wajib menyimpan nama, simbol, dan **faktor konversi ke ton** sebagai satuan agregasi baku.
3. Contoh faktor konversi: ton = 1; kuintal = 0,1; kilogram = 0,001.
4. Volume panen disimpan apa adanya sesuai satuan baku komoditasnya, tanpa dikonversi saat penyimpanan.
5. Konversi ke ton hanya dilakukan pada saat rekap, agregasi, dan penyajian dashboard, agar data asli lapangan tetap terjaga.
6. Satuan lokal seperti karung dan ikat tidak dipakai sebagai satuan baku, melainkan dicatat pada kolom keterangan tambahan.
7. Penambahan satuan baru cukup menambah baris data, tanpa mengubah struktur tabel.

### 9. Aturan Modul Hasil Panen
1. Hasil panen harus dicatat per periode.
2. Minimal data panen yang dicatat:
   - jenis komoditas,
   - volume panen,
   - satuan panen,
   - kualitas panen,
   - harga jual,
   - periode/musim tanam,
   - lokasi produksi.
3. Volume panen dicatat memakai **satuan baku milik komoditas** yang bersangkutan, mengacu pada data master satuan.
4. Satuan lokal seperti karung atau ikat dicatat pada kolom keterangan tambahan agar rekap tetap konsisten.
5. Rekap dan agregasi lintas komoditas wajib dikonversi terlebih dahulu ke satuan **ton** memakai faktor konversi pada data master.
6. Nilai volume panen disimpan dengan presisi desimal yang cukup agar panen berskala kecil tidak hilang saat pembulatan.
7. Riwayat panen harus dapat dipantau untuk melihat potensi produksi kawasan.
8. Hasil panen harus dapat direkap per desa/SP, per transmigran, per poktan, per komoditas, dan per periode.

### 10. Aturan Modul Infrastruktur Pertanian
1. Modul infrastruktur berisi **pendataan aset**, bukan pelaporan masalah. Pelaporan kerusakan ditangani modul Pengaduan (§10b).
2. Infrastruktur yang dicatat minimal mencakup:
   - air,
   - irigasi,
   - listrik,
   - jalan penghubung (akses masuk ke kawasan SP),
   - jalan produksi (akses menuju lahan usaha),
   - telekomunikasi,
   - sanitasi,
   - gudang,
   - pasar atau kios saprotan.

   **Jalan penghubung dan jalan produksi sengaja dibedakan.** Jalan penghubung menentukan apakah warga, petugas, dan kendaraan darurat dapat mencapai SP; jalan produksi menentukan apakah hasil panen dapat diangkut dari lahan. Keduanya berbeda dampak dan berbeda bobot pada penilaian kondisi SP (§10c).
3. Setiap infrastruktur wajib mencatat nama, tahun perolehan, sumber dana, dan kondisi terkini.
4. Setiap infrastruktur dapat disertai foto kondisi lapangan dan titik koordinat.
5. Infrastruktur wajib tertaut ke desa/SP, dan ke poktan bila relevan.
6. Data infrastruktur dipakai sebagai dasar peta aset dan perencanaan perbaikan kawasan.

### 10c. Aturan Penilaian Kondisi Satuan Permukiman

#### 10c.1 Tujuan dan batasan

1. Sistem menilai kondisi tiap SP berdasarkan **ketersediaan dan kondisi layanan dasar**, lalu menyajikannya sebagai satu label yang mudah dibaca pemangku kepentingan.
2. Penilaian ini menilai **infrastruktur dan fasilitas**, bukan warganya. Label tidak boleh dibaca sebagai penilaian atas kemampuan, kemauan, atau martabat penghuni SP.
3. Karena itu istilah yang dipakai adalah **Mandiri**, **Berkembang**, dan **Perlu Penanganan**. Istilah bernada merendahkan seperti "terbelakang" atau "tertinggal" **dilarang** dipakai di antarmuka maupun laporan, sebab yang dinilai adalah jalan dan listrik, hal yang justru berada di luar kendali warga.
4. Label **wajib** disertai rincian pembentuknya. Menampilkan label tanpa menyebutkan penyebabnya membuat penilaian berhenti sebagai stempel, bukan alat perencanaan.

#### 10c.2 Tiga tingkat kebutuhan

Parameter dikelompokkan menurut satu pertanyaan: **tanpa ini, apakah tempat tersebut masih layak dihuni?**

| Tingkat | Makna | Parameter |
|---|---|---|
| **Primer** | Tanpa ini tempat tidak layak huni | Air bersih, Jalan penghubung, Listrik |
| **Sekunder** | Masih dapat dihuni, tetapi tidak berkembang | Fasilitas kesehatan, Pendidikan dasar, Telekomunikasi, Sanitasi |
| **Tersier** | Penunjang produktivitas dan kehidupan sosial | Irigasi, Gudang, Jalan produksi, Balai pertemuan, Rumah ibadah, Pasar atau kios saprotan |

#### 10c.3 Bobot

5. Bobot awal: **Primer 5, Sekunder 3, Tersier 1**. Jarak ini disengaja agar kegagalan pada layanan dasar tidak tertutupi oleh kelengkapan fasilitas penunjang.
6. Bobot **disimpan sebagai data** pada tabel `parameter_penilaian_sp`, bukan ditulis di dalam kode, sehingga Admin dapat menyesuaikannya lewat antarmuka tanpa mengubah struktur database. Pola ini mengikuti keputusan yang sama pada role dinamis (§5.0) dan faktor konversi satuan (§8a).
7. Parameter dapat dinonaktifkan tanpa dihapus, agar riwayat penilaian yang memakainya tetap dapat dibaca.

#### 10c.4 Cara menghitung

8. Skor kondisi tiap parameter:

   | Kondisi | Nilai |
   |---|---|
   | Baik | 1,0 |
   | Rusak Ringan | 0,5 |
   | Rusak Berat | 0,2 |
   | **Tidak Ada** | **0** |

9. **Ketiadaan dan kerusakan wajib dibedakan.** SP yang belum pernah memiliki jaringan telekomunikasi berbeda persoalannya dengan SP yang memilikinya tetapi rusak; yang pertama memerlukan pembangunan, yang kedua perbaikan. Parameter tanpa data aset yang bersesuaian dinilai `Tidak Ada`, bukan diabaikan dari perhitungan.
10. Rumus:

    ```
    skor = (jumlah dari bobot x nilai kondisi) / (jumlah seluruh bobot) x 100
    ```

11. **Aturan primer nol.** Bila satu saja parameter primer bernilai `Tidak Ada`, SP otomatis berlabel **Perlu Penanganan**, berapa pun skor totalnya. Tanpa aturan ini, SP tanpa air bersih tetapi lengkap fasilitas penunjangnya dapat mencapai skor tinggi, dan angka itu menyesatkan.

#### 10c.5 Ambang batas

12. Ambang awal:

    | Label | Syarat |
    |---|---|
    | **Mandiri** | Skor >= 80 **dan** seluruh parameter primer minimal Rusak Ringan |
    | **Berkembang** | Skor 55 sampai 79, tanpa parameter primer bernilai nol |
    | **Perlu Penanganan** | Skor < 55, **atau** ada parameter primer bernilai nol |

13. Bobot pada §10c.3 dan ambang pada poin 12 adalah **keputusan kebijakan, bukan keputusan teknis**. Keduanya wajib divalidasi dinas sebelum dipakai pada laporan resmi.

#### 10c.6 Riwayat penilaian

14. Setiap penilaian **disimpan sebagai baris riwayat**, memuat skor, label, tanggal penilaian, rincian nilai tiap parameter, dan **salinan bobot yang berlaku saat itu**.
15. Alasannya: bobot dapat diubah Admin. Tanpa salinan, laporan yang sudah dicetak dan dikirim ke dinas akan berbeda dari yang ditampilkan sistem setelah bobot diubah. Prinsip ini sama dengan penyalinan `satuan_id` pada hasil panen (`data-dictionary.md` §9.3).
16. Riwayat memungkinkan perkembangan kondisi SP terbaca dari waktu ke waktu, misalnya kenaikan dari Perlu Penanganan menjadi Berkembang setelah jalan penghubung diperbaiki. Perkembangan ini justru lebih berguna bagi perencanaan daripada angka hari ini saja.
17. Penilaian **tidak dihitung ulang secara diam-diam** saat halaman dibuka. Penilaian baru dibuat sebagai baris baru, sehingga yang lama tetap utuh.

### 10a. Aturan Modul Penghuni Kawasan
1. Sistem harus mencatat data penghuni/transmigran kawasan beserta status tinggal, pindah, dan aktif/tidak aktif.
2. Data penghuni wajib tertaut ke data rumah, mencakup kondisi rumah, foto rumah, koordinat lokasi, riwayat kepemilikan, dan catatan tambahan.
3. Data penghuni harus tertaut ke desa/SP dan dapat difilter per lokus.
4. Sistem harus menyediakan rekap kependudukan kawasan, termasuk KK masuk dan keluar per tahun.
5. Data penghuni bersifat sensitif dan wajib dibatasi oleh RBAC serta ditampilkan agregat bagi pihak terbatas.

### 10b. Aturan Modul Pengaduan

#### Kanal publik tanpa login
1. Pengaduan diajukan lewat **halaman publik tanpa login**, karena warga transmigran tidak memiliki akun sistem. Warga cukup mengisi nama, kontak, lokasi SP, kategori, dan uraian masalah.
1a. Petugas juga dapat mencatatkan pengaduan atas nama warga yang melapor lisan. Sumber laporan dibedakan lewat kolom `sumber_laporan` bernilai `Publik` atau `Petugas`.
1b. Setelah mengirim, warga menerima **nomor pengaduan** yang dipakai untuk melacak perkembangan laporannya pada halaman lacak publik.
1c. Halaman lacak hanya menampilkan status, tanggal, dan catatan penanganan. Data pribadi pelapor tidak pernah ditampilkan.

#### Pengamanan kanal publik
1d. Pengiriman dibatasi **3 pengaduan per jam untuk setiap alamat IP**.
1e. Seluruh pengaduan publik masuk berstatus `Menunggu Diterima`, sehingga petugas menyaring lebih dulu sebelum diproses.
1f. Alamat IP pelapor disimpan untuk menelusuri penyalahgunaan.
1g. Sistem **tidak memakai CAPTCHA**, karena membebani pengguna berjaringan lemah di lokus. Pembatasan laju dinilai memadai untuk skala kawasan ini.

#### Pencatatan dan penanganan
2. Setiap pengaduan wajib mencatat tanggal, nama dan kontak pelapor, lokasi/SP, kategori, dan deskripsi.
3. Kategori pengaduan memakai pilihan baku: lahan usaha, lahan pekarangan, rumah, infrastruktur, peralatan dan perlengkapan, alsintan, produksi panen, bencana, dan lainnya.
4. Alur status penanganan wajib berurutan: **Menunggu Diterima → Diterima → Diproses → Selesai**.
5. Setiap perubahan status wajib menyimpan riwayat berisi petugas penangan, tanggal penanganan, catatan, dan dokumen tindak lanjut.
6. Pengaduan dapat dilampiri dokumen/foto pendukung dan diberi penanda prioritas.
7. Pengaduan diteruskan ke dinas sesuai bidangnya: bidang pertanian ke Dinas Pertanian, bidang ketransmigrasian ke Dinas Transmigrasi.
8. Rekap pengaduan per kategori, per status, dan per desa/SP wajib tersedia sebagai sumber indikator isu prioritas pada dashboard.

### 11. Aturan Dashboard Monitoring
1. Dashboard harus menampilkan indikator utama kawasan secara ringkas.
2. Minimal indikator dashboard:
   - jumlah transmigran, disajikan sebagai grafik per tahun,
   - jumlah KK, disajikan sebagai grafik per tahun,
   - jumlah petani, disajikan sebagai grafik per tahun,
   - jumlah pendapatan keluarga, disajikan sebagai grafik per tahun,
   - visualisasi KK masuk dan keluar per tahun,
   - jumlah rumah yang terhuni,
   - visualisasi pekerjaan kepala keluarga dalam bentuk histogram,
   - luas lahan,
   - komoditas utama (terbanyak),
   - total volume panen per tahun, dinyatakan dalam ton hasil konversi lintas komoditas,
   - harga rata-rata,
   - status infrastruktur,
   - isu prioritas per desa/SP yang bersumber dari modul Pengaduan,
   - rekap data penghuni kawasan.
3. Dashboard harus mudah dibaca oleh pengguna nonteknis.
4. Informasi penting harus dapat difilter berdasarkan wilayah (kawasan/kecamatan/desa/SP) atau periode.
5. Grafik atau visualisasi yang menampilkan rekap gabungan seluruh SP **wajib dapat diklik (drill-down)** untuk menampilkan rincian data per SP.
6. Dashboard harus membantu pengambilan keputusan, bukan hanya merangkum data.
7. Query dashboard harus memakai indeks, paginasi, agregasi, dan eager loading agar tetap cepat saat data bertambah.

### 12. Aturan Laporan dan Export
1. Sistem harus menyediakan export laporan.
2. Format export minimal: Excel dan PDF.
3. Laporan harus bisa digunakan untuk kebutuhan desa, dinas, pendamping, dan kementerian.
4. Laporan harus mendukung rekap data utama:
   - transmigran dan keluarga,
   - rumah dan hunian,
   - lahan,
   - poktan dan anggota,
   - alsintan dan saprotan,
   - komoditas,
   - panen,
   - inventaris dan fasilitas SP,
   - infrastruktur,
   - pengaduan dan status penanganan,
   - indikator kawasan.
5. Laporan harus bisa difilter sebelum diekspor.

### 13. Aturan UI/UX

#### 13.0 Filter desain ANTISLOP (WAJIB)

`ANTISLOP-ID.md` berlaku sebagai **filter** untuk seluruh pekerjaan antarmuka. Ia tidak menetapkan arah desain; arah ditetapkan `ui-spec.md` §2, sedangkan ANTISLOP menyaring hasilnya.

**Arah desain yang mengikat** (`ui-spec.md` §2):
- Dial: **ENERGI 1 / RITME 2 / GERAK 1**
- Motif identitas: bentuk sudut miring dari logo Kementerian, diulang di empat titik
- Aksen tunggal: gold, dipakai hemat pada empat hal saja

**Ruang lingkup R-02 (larangan em dash):** berlaku untuk **teks yang tampil di antarmuka** saja, yaitu label, tombol, pesan validasi, pesan galat, judul halaman, dan seluruh konten aplikasi. Dokumen internal di folder `agents/` dikecualikan karena tidak dilihat pengguna akhir.

**Aturan turunan yang wajib dipatuhi:**

| Aturan | Penerapan |
|---|---|
| R-15 | CTA menyebut objeknya: "Simpan Data Transmigran", bukan "Simpan" |
| R-16 | Dilarang buzzword. Pakai kalimat yang menyebut kejadian nyata: "3 pengaduan menunggu ditindaklanjuti" |
| R-17, R-38 | Selama tahap data dummy, setiap halaman wajib menampilkan penanda **"Data contoh"** yang terlihat jelas. Angka dummy dilarang disajikan seolah data nyata |
| R-23 | Dilarang membuat logo, avatar, atau aset visual tanpa instruksi. Pakai placeholder berlabel jujur |
| R-24, R-26 | Menu dan tombol hanya untuk halaman/aksi yang benar-benar ada. Kontrol mati dihapus, bukan dibiarkan diam |
| R-25 | Kontras WCAG AA wajib dipenuhi pada **kedua** mode tema (`ui-spec.md` §3.2) |
| R-30 | TailAdmin adalah fondasi berlisensi MIT dengan palet ditimpa penuh dan motif identitas sendiri, bukan peniruan identitas produk lain |
| R-31 | Setiap keputusan desain utama wajib punya alasan satu baris, dicatat pada `ui-spec.md` §2.5 |
| R-32 | Seluruh alur wajib dapat dioperasikan dengan keyboard, dengan indikator fokus terlihat di kedua mode |
| R-33 | Dilarang menambahkan fitur lewat script yang menulis ulang berkas sumber atau CSS |
| R-34 | Mode terang dan gelap sama-sama wajib berfungsi penuh |

#### 13.1 Prinsip dasar
1. Antarmuka harus responsif dan mobile friendly untuk desktop dan ponsel.
2. Desain form harus bertahap, tidak terlalu padat.
3. Tabel harus dilengkapi filter agar mudah dipakai untuk data besar.
4. Tampilan dashboard harus ringkas dan fokus pada informasi utama.
5. Navigasi harus mudah dipahami oleh operator lapangan.
6. Bahasa antarmuka harus sederhana dan konsisten.

#### 13.2 Konvensi wajib (diterapkan otomatis pada fitur baru dan perubahan)

Pola berikut adalah **standar yang harus dibangun dan dipatuhi** sejak awal proyek. Berkas dan helper yang disebut di bawah belum ada dan menjadi bagian dari pekerjaan Tahap 1; nama berkas dipertahankan sebagai target agar konsisten.

1. **Tab-halaman persisten setelah reload.** Halaman dengan tab (bukan sidebar utama kiri) yang punya aksi submit (`back()` reload) wajib menyimpan tab aktif pada URL **query string** (`?tab=`) agar tidak kembali ke tab bawaan. Dilarang memakai hash `#tab`, karena fragment hilang saat form POST dan tidak terkirim lewat Referer.
   - Bangun helper global `hashTabs('defaultTab')` pada `resources/views/layouts/dashboard.blade.php`, dengan tombol memakai `setTab('x')`.
   - Untuk tab bertingkat, pakai `?tab=&sub=` dengan fungsi `syncUrl()` yang menulis kedua parameter.
   - Fungsi `init()` wajib menulis query saat halaman dimuat agar submit dari modal tetap membawa posisi tab lewat Referer.
   - Berlaku bila controller memakai `return back()`. Tidak diperlukan untuk perpindahan tanpa submit-reload, modal, atau dropdown.
2. **Sub-tab bila konten menumpuk.** Jika satu halaman menampung banyak card/section sehingga scroll memanjang, pecah menjadi sub-tab dengan hanya satu section tampil via `x-show`.
3. **Form leluasa memakai modal floating.** Form isian panjang memakai modal floating, bukan form inline sempit. Tombol pemicu (Tambah/Ubah) diletakkan di header card dan tidak boleh overflow.
4. **Input teks pengguna otomatis HURUF KAPITAL** melalui middleware `UppercaseInput` yang dibuat di `app/Http/Middleware/UppercaseInput.php`, dengan pengecualian kredensial, enum, teks naratif, dan field `*_id`.
5. **Tanggal ditampilkan dalam Bahasa Indonesia** memakai `translatedFormat`; nilai uang memakai format `Rp x.xxx.xxx` via `number_format(...,0,',','.')`.
6. **Validasi terpusat dan DRY.** Aturan nama, nomor telepon, NIK, nomor KK, dan sejenisnya ditulis di `app/Support/ValidationRules.php`. Dilarang menulis ulang regex atau rule di tiap form.
7. **Eager loading wajib.** Query yang dipakai di dalam loop view wajib memakai `with([...])` untuk mencegah N+1.
8. **Verifikasi sebelum menyatakan selesai.** `php artisan test`, `npm run build`, dan `php artisan view:cache` harus hijau; lakukan smoke test di browser untuk setiap perubahan UI.

#### 13.3 Aturan tampilan dan format
1. Zona waktu aplikasi adalah **WITA (`Asia/Makassar`, UTC+8)** mengikuti lokasi Kabupaten Malaka, dan locale aplikasi adalah `id`.
2. Paginasi tabel bawaan **25 baris**, dengan pilihan 10, 25, 50, dan 100.
3. Angka desimal memakai koma sebagai pemisah desimal dan titik sebagai pemisah ribuan.
4. Volume panen ditampilkan dengan 3 angka desimal beserta satuannya; luas lahan dengan 2 angka desimal beserta satuan hektare.
5. Data kosong ditampilkan sebagai tanda hubung `—`, bukan string kosong atau teks `null`.
6. Setiap halaman daftar dan detail wajib menangani lima keadaan: kosong, memuat, galat, tanpa izin, dan hasil pencarian nihil.
7. Pesan galat dan validasi wajib berbahasa Indonesia yang mudah dipahami operator lapangan, bukan istilah teknis.
8. Palet warna, tipografi, komponen bersama, struktur menu, dan inventaris halaman mengikuti `ui-spec.md`.
9. Kombinasi warna wajib memenuhi rasio kontras WCAG AA sesuai tabel pada `ui-spec.md` §3.2.

### 14. Aturan Keamanan
1. Sistem wajib menggunakan HTTPS.
2. Password harus disimpan dengan hashing.
3. Validasi input harus dilakukan di sisi server **dan** di sisi client/UI.
4. Akses data harus dibatasi berdasarkan role, diterapkan pada level query.
5. Sistem harus memiliki audit log perubahan data penting.
6. Login perlu dilindungi dari percobaan berulang yang berlebihan (rate limiting).
7. Backup harus tersedia agar data tidak mudah hilang.
8. Unggahan foto/dokumen wajib dibatasi ukurannya, divalidasi tipenya, dan dikompresi agar hemat penyimpanan serta kuota pengguna lapangan.

### 14a. Aturan File dan Upload
1. Batas ukuran setiap file dokumen yang diunggah adalah **5 MB**.
2. Format file yang diterima adalah gambar dan PDF, dan wajib divalidasi tipenya di sisi server.
3. File disimpan pada filesystem di folder `storage/app/private/[transmigran]/[id-transmigran]/`, bukan disimpan sebagai BLOB di dalam database.
4. Database hanya menyimpan path/nama file, bukan isi filenya.
5. Format penamaan file dokumen: `[Nama Dokumen berdasarkan tabel pada database]_[nama-transmigran].[ekstensi]`, dengan spasi pada nama transmigran diganti tanda hubung `-`.
6. Akses file bersifat privat dan harus melewati pemeriksaan hak akses, tidak boleh diakses langsung lewat URL publik.
7. File dokumen wajib ikut diperhitungkan dalam strategi backup.

### 14b. Aturan Akun dan Pemulihan Kata Sandi

#### Pembuatan akun
1. **Tidak ada pendaftaran mandiri.** Sistem tidak menyediakan halaman daftar akun. Seluruh akun dibuat oleh Admin lewat menu Manajemen Pengguna.
2. Setiap akun wajib diberi satu role. Bila role tersebut bercakupan `Per SP`, akun wajib pula diberi minimal satu penugasan SP.
3. Admin menetapkan kata sandi awal dan menyerahkannya langsung kepada pengguna. Kata sandi awal wajib ditandai `password_harus_diganti = TRUE`.

#### Kredensial masuk
4. Sistem menerima **email atau username** pada satu kolom isian yang sama. Keduanya wajib diisi dan unik antar-akun.
5. Username hanya boleh memuat huruf kecil, angka, titik, dan garis bawah, dengan panjang 3 sampai 50 karakter.
6. Seluruh pengguna sistem adalah petugas, sehingga tidak ada kredensial berbasis NIK. Warga tidak memiliki akun.

#### Pemulihan kata sandi
7. **Sistem tidak mengirim tautan pemulihan lewat surel.** Tabel `password_reset_tokens` bawaan Laravel tidak dipakai.
8. Pengguna yang lupa kata sandi menghubungi Admin. Admin menyetel ulang lewat Manajemen Pengguna, lalu menyerahkan kata sandi sementara secara langsung.
9. Setelah disetel ulang, kolom `password_harus_diganti` bernilai `TRUE`. Pengguna diarahkan ke halaman ganti kata sandi saat masuk berikutnya dan **tidak dapat mengakses halaman lain** sebelum menggantinya.
10. Admin **tidak dapat melihat** kata sandi pengguna mana pun, karena hanya hash yang tersimpan. Admin hanya dapat menimpanya dengan nilai baru.
11. Setiap penyetelan ulang wajib tercatat pada audit log dengan aksi `Reset Kata Sandi`, memuat petugas pelaku, akun sasaran, dan waktu kejadian.

#### Perlindungan akun terakhir
12. Sistem menolak penonaktifan maupun penghapusan akun Admin terakhir yang masih aktif, agar sistem tidak pernah kehilangan seluruh jalur administrasinya.
13. Wajib tersedia perintah artisan khusus untuk menyetel ulang kata sandi Admin lewat terminal server, sebagai jalur pemulihan darurat bila seluruh Admin kehilangan akses.

### 14c. Aturan Pembatasan Laju (Rate Limiting)

#### 14c.1 Prinsip

1. Pembatasan laju melindungi sistem dari pemanenan data dan percobaan masuk beruntun, **tanpa mengganggu pekerjaan operator yang wajar**.
2. Batas **berbeda menurut jenis akses**, bukan satu angka untuk seluruh sistem. Halaman baca internal dan kanal publik tanpa login punya risiko yang jauh berbeda.
3. Batas untuk halaman internal dihitung **per akun**, bukan per alamat IP. Satu kantor dinas kerap memakai satu sambungan internet bersama; menghitung per IP membuat sepuluh operator saling menghabiskan jatah satu sama lain.

#### 14c.2 Batas per jenis akses

| Jenis akses | Batas | Basis hitungan | Alasan |
|---|---|---|---|
| Halaman baca internal | **120 per menit** | per akun | Operator tercepat jarang melewati 30 halaman per menit, sehingga batas ini tidak pernah terasa |
| Halaman tulis internal | **40 per menit** | per akun | Penyimpanan data selalu diselingi pengisian form |
| Lacak pengaduan publik | **10 per menit** | per alamat IP | Warga memeriksa satu atau dua nomor; pemanen otomatis memerlukan ribuan |
| Kirim pengaduan publik | **3 per jam** | per alamat IP | Sudah ditetapkan pada §10b poin 1d |
| Percobaan masuk | **5 kegagalan per menit** | per alamat IP dan akun | Hanya kegagalan yang dihitung; masuk yang berhasil tidak terpengaruh |

#### 14c.3 Ketentuan

4. Permintaan aset statis seperti CSS, gambar, dan JavaScript **tidak dihitung**.
5. Penolakan karena pembatasan laju wajib memakai pesan berbahasa Indonesia yang menyebut jalan keluarnya, bukan kode galat teknis. Contoh untuk kanal publik: "Anda sudah mengirim beberapa pengaduan. Silakan coba lagi satu jam lagi."
6. Rute yang secara wajar menembakkan banyak permintaan sekaligus, misalnya export massal dan unggah template luring, **wajib dikecualikan** dari batas halaman biasa dan diberi batasnya sendiri. Menaikkan batas untuk seluruh pengguna demi satu fitur adalah penyelesaian yang keliru.
7. Sistem tidak memakai CAPTCHA, sesuai §10b poin 1g.

### 15. Aturan Backup dan Pemeliharaan
1. Backup data harus dilakukan secara terjadwal.
2. File foto/dokumen harus ikut dipertimbangkan dalam strategi backup.
3. Log error harus dipantau secara berkala.
4. Sistem harus disiapkan agar dapat dipelihara oleh pengelola lokal.
5. SOP penggunaan harus dibuat agar sistem tetap dapat dipakai meskipun operator berganti.

### 16. Aturan Pengujian
1. Pengujian dilakukan bertingkat: **alpha testing** internal, **beta testing** bersama calon pengguna, lalu validasi akhir (UAT).
2. Alpha testing minimal mencakup login, role, CRUD, validasi input, filter, upload, export, dashboard, audit log, dan keamanan akses.
3. Bug bersifat blocker wajib diselesaikan sebelum deployment.
4. Hasil pengujian dicatat dalam dokumen testing beserta daftar bug dan status penyelesaiannya.
5. Masukan pengguna dari beta testing dipisahkan menjadi revisi wajib dan usulan pengembangan lanjutan.
6. Pengujian antarmuka wajib dilakukan pada **matriks 2 mode tema × 2 lebar layar** (360px dan 1280px).

#### 16.1 Delivery Gate ANTISLOP

Sebelum menyatakan pekerjaan antarmuka selesai, **wajib** menjalankan Delivery Gate pada `ANTISLOP-ID.md` dan menyertakan hasilnya sebagai **laporan PASS/FAIL**.

**Ketentuan:**
1. Satu baris per item, dan setiap `PASS` disertai **bukti konkret**, bukan pernyataan kosong. Contoh: `R-26 PASS: seluruh 34 tombol punya rute atau handler nyata; tidak ada kontrol mati.`
2. Empat blok wajib dijalankan seluruhnya: Hard Gate, Purpose-Gate, Liveliness, serta Craftsmanship dan Quality Locks.
3. Laporan yang **mengandung satu saja FAIL dilarang diserahkan**. Perbaiki lebih dulu, jalankan ulang gate, baru serahkan.
4. Gate dijalankan pada akhir setiap gelombang Tahap 2, bukan sekali di akhir proyek.
5. Blok Liveliness menuntut jawaban **ya** untuk seluruh item, termasuk kesesuaian hasil dengan dial yang dinyatakan. RITME 2 yang berakhir dengan seluruh halaman berkomposisi seragam dihitung sebagai FAIL.

### 17. Aturan Implementasi dan Serah Terima
1. Pengembangan dimulai dari kebutuhan pengguna dan validasi lapangan.
2. Uji coba internal harus dilakukan sebelum sistem diaktifkan.
3. Setelah uji coba, sistem harus diuji bersama calon pengguna.
4. Pelatihan harus diberikan kepada operator desa/SP, pendamping, dan pihak terkait.
5. Sistem, akun, SOP, panduan, dan dokumentasi teknis harus diserahterimakan secara resmi melalui BAST.
6. Penanggung jawab lokal (operator) harus ditetapkan agar sistem tetap terpelihara setelah program selesai.
7. Implementasi dianggap berhasil bila pengguna dapat login, input data, melihat dashboard, dan membuat laporan.

### 18. Aturan Pengembangan Berkelanjutan
1. Setiap masukan dari pengguna harus dicatat sebagai bahan perbaikan.
2. Sistem harus siap dikembangkan lanjutan setelah versi awal berjalan.
3. Perubahan besar harus didahului evaluasi kebutuhan.
4. Pengembangan berikutnya harus tetap menjaga kesederhanaan penggunaan.
5. Semua penambahan fitur harus tetap selaras dengan tujuan monitoring pertanian dan tata kelola data kawasan.

### 19. Aturan Penulisan Kode
1. Setiap fungsi yang dibuat wajib diberi komentar dalam **Bahasa Indonesia** agar mudah dipahami programmer berikutnya.
2. Komentar menjelaskan tujuan fungsi, parameter penting, dan nilai yang dikembalikan, bukan mengulang isi baris kode.
3. Penamaan tabel dan kolom mengikuti skema database yang sudah disepakati pada `erd.md` dan `data-dictionary.md`, dengan konvensi PK `id_namatabel` dan FK `namatabel_id` sesuai §4.0.
4. Logika yang dipakai berulang diletakkan pada service atau helper bersama, tidak disalin ke banyak controller.
5. Struktur kode mengikuti pola MVC Laravel beserta migration, middleware, dan service layer.
6. Setiap model wajib mendeklarasikan `$primaryKey`, `$fillable`, dan `$casts` secara eksplisit; dilarang mengandalkan asumsi bawaan Eloquent.
7. Nilai enum diakses lewat PHP Enum di `app/Enums/`, tidak ditulis sebagai teks berkode keras di controller maupun view.
8. Komponen antarmuka mengutamakan komponen TailAdmin yang sudah tersedia; komponen khusus domain dibangun sebagai pembungkus, bukan tulisan ulang dari nol.

### 20. Aturan Pengerjaan AI (Tasklist)
Setiap kali selesai mengerjakan satu tugas/fitur, AI wajib memperbarui `agents/tasklist.md` sebelum melaporkan hasil kepada user, dengan ketentuan:
1. Tandai task yang selesai dengan centang `[✓]`.
2. Tambahkan emoji ✅ di depan task.
3. Perbarui progres keseluruhan proyek, misalnya `Progress: 35%`.
4. Tambahkan catatan singkat di bawah task mengenai file yang dibuat atau diubah.

   ```markdown
   - [✓] ✅ Task 2.3 - Membuat Room Migration `[Mudah]` (Selesai)
     * Membuat file `database/migrations/xxxx_create_students_table.php`
   ```
5. Perbarui `tasklist.md` setiap selesai satu task, bukan diakhir sesi.
6. Berikan ringkasan yang jelas pada akhir setiap task.
7. Bila mendekati batas konteks, berhenti pada checkpoint yang rapi.
8. Tujuannya agar agent berikutnya cukup membaca tasklist untuk tahu persis pekerjaan mana yang dilanjutkan.
