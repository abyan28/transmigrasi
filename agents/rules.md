# rules.md
## Aturan Pengembangan Sistem Informasi Digitalisasi Monitoring Pertanian dan Tata Kelola Data Kawasan Transmigrasi Kobalima Timur

### 1. Prinsip Umum
1. Sistem harus berbasis web dan dapat diakses melalui browser dan mobile phone.
2. Sistem harus mendukung monitoring pertanian dan tata kelola data kawasan transmigrasi secara terintegrasi.
3. Sistem harus dibuat sederhana, ringkas, dan mudah dipahami oleh operator lapangan.
4. Setiap fitur harus mengutamakan kebutuhan lapangan, bukan sekadar tampilan.
5. Semua fitur harus mendukung pengambilan keputusan berbasis data.
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
1. Struktur fitur harus dipisahkan dengan jelas per domain data.
2. Fitur inti minimal terdiri dari:
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
   - hasil panen dan penanaman,
   - infrastruktur SP,
   - penghuni/data kependudukan kawasan,
   - pengaduan dan penanganannya,
   - dashboard monitoring,
   - laporan/export,
   - dokumentasi dan SOP.
3. Setiap fitur harus punya alur input, validasi, penyimpanan, pencarian, dan rekap data.
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
| Nilai uang | `DECIMAL(15,2)` | Rupiah; diformat dengan pemisah ribuan titik tanpa desimal (`1.000.000`) via Alpine `x-uang` dan dinormalkan ke integer murni saat submit |
| Luas lahan | `DECIMAL(12,2)`, satuan hektare | |
| Volume panen | `DECIMAL(12,3)` | 3 desimal agar panen 1 kg tetap terekam |
| Dokumen dan foto | `VARCHAR(255)` berisi path berkas | dilarang memakai `BLOB` |
| Nama wilayah | disimpan sebagai baris pada tabel daftar pilihan | dilarang memakai `ENUM` berisi nama wilayah |

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
4a. **Bagian acak berada DI LUAR kendali CMS** (ditetapkan 2026-09-02). Dinas dapat mengatur awalan dan pola nomor urut lewat Pengelolaan Konten, tetapi bagian acaknya selalu ditambahkan sistem dan tidak dapat dimatikan. Alasannya keamanan, bukan gaya penulisan: menyerahkannya kepada pilihan dinas berarti perlindungan yang paling menentukan dapat dilepas tanpa disadari akibatnya. Template bawaan CMS sempat berbunyi `PGD-{TAHUN}-{NOMOR}` tanpa bagian acak, dan itu bertentangan dengan poin 4.
4b. **Bentuknya enam karakter beralfabet tanpa huruf dan angka yang mudah tertukar** (tanpa `0`, `O`, `1`, `I`, dan `L`). Warga membaca nomornya dari layar ponsel lalu menyalinnya ke halaman lacak, dan salah baca satu karakter membuat laporannya seolah tidak pernah ada.
4c. **Nomor urutnya dipertahankan, tidak diganti acak seluruhnya.** Ia tetap berguna bagi petugas untuk mengurutkan dan menyebut laporan; yang menutup penyusuran adalah bagian acaknya.
4d. **Nomor tidak pernah berubah setelah terbit.** Bagian acaknya dibangkitkan sekali saat penerbitan lalu ikut tersimpan, bukan dihitung ulang tiap kali dibaca. Warga sudah mencatat atau memotretnya, dan nomor yang berubah membuat laporannya tidak dapat dilacak lagi.
5. Penggantian ke UUID dilakukan **bertahap**, dimulai dari fitur berdata pribadi. Mengubah seluruh fitur sekaligus memperbesar risiko tanpa menambah perlindungan yang sepadan.
5a. **Pengenal publik dipasang bersamaan dengan pembuatan Model, bukan sesudahnya**
(ditetapkan 2026-09-02). Tiap model yang tabelnya memiliki kolom `uuid` wajib lahir
dengan `getRouteKeyName()` bernilai `uuid` sejak commit pertamanya. Alasannya biaya:
pada saat Model dibuat, penggantiannya cukup satu method per model; setelah controller,
rute, dan tautan terlanjur ditulis di atas id integer, penggantiannya menuntut
penyisiran setiap pemanggilan `route()`. Biaya itu naik terus selama ditunda.

5b. **Lima tabel sudah menyediakan kolom `uuid`** pada skema: `transmigran`,
`rumah`, `lahan`, `hasil_panen`, dan `pengaduan`. Urutan penerapannya mengikuti
poin 5, yaitu data pribadi lebih dulu: `transmigran`, `rumah`, `pengaduan`, lalu
`lahan` dan `hasil_panen`.

5c. **Rute tahap frontend TIDAK diubah lebih dulu.** Selama data masih disimulasikan
`DummyData`, mengganti `{id}` menjadi UUID hanya menambah kerumitan tanpa
perlindungan apa pun: belum ada Model yang menerjemahkan pengenal publik menjadi kunci
internal, sehingga penerjemahannya terpaksa ditulis tangan lalu dibuang lagi.
Pembatas rutenya (`[0-9]+`) wajib ikut disesuaikan pada saat Model dibuat, sebab
pembatas angka akan menolak UUID dan menghasilkan 404 yang membingungkan.

6. Pembatasan laju melengkapi, bukan menggantikan, pengenal tak tertebak (§14c).

**Aturan tambahan:**
1. Semua tabel memiliki `created_at` dan `updated_at`.
2. Tabel data utama memakai soft delete (`deleted_at`); tabel referensi dan tabel riwayat tidak.
3. Semua tabel memakai `ENGINE=InnoDB`, `CHARSET=utf8mb4`, `COLLATE=utf8mb4_unicode_ci`.
4. Nilai enum tidak ditulis langsung di kode maupun view, melainkan didefinisikan sebagai **PHP Enum** di `app/Enums/` sesuai daftar pada `data-dictionary.md` §11.

#### 4.1 Aturan umum

1. Struktur database dan kamus istilah wajib didokumentasikan dalam **ERD** dan **data dictionary** sebelum implementasi fitur dimulai.
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
   - penanaman,
   - hasil panen,
   - infrastruktur SP,
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
4. Setiap SP wajib menyimpan: nama SP, desa, kawasan, titik koordinat, luas lahan, dokumen pendukung, dan penanggung jawab data. Inventaris dan fasilitas SP dikelola sebagai daftar terpisah yang menempel pada SP (§4b).
4a. **SP menyimpan field "Keadaan Wilayah"** (letak astronomis dan ekonomis, batas wilayah, SK pencadangan, pola permukiman, tanah, topografi, iklim, sumberdaya air) untuk Laporan Monografi SP. Seluruhnya opsional dan dokumenter: dipakai laporan, tidak dihitung. Angka rentang disimpan sebagai pasangan min/maks, bukan teks. Ditambahkan 2026-08-28 (Rombongan C), mengikuti Bab II Laporan Monografi.
4b. **Batas wilayah Utara/Timur/Selatan/Barat dihidupkan kembali 2026-08-28** setelah dicabut 2026-08-18. Alasan pencabutan (isinya sebutan naratif, tak dipakai hitungan/indikator/peta) tetap benar, tetapi Bab II Laporan Monografi memuatnya sehingga dinas memerlukannya. Riwayat pencabutan dipertahankan pada `notes.md` bagian 6.
4c. **Rute aksesibilitas SP** (rute perjalanan menuju SP: jarak, sarana angkutan, kondisi jalan, waktu tempuh, ongkos) disimpan sebagai daftar terpisah `rute_aksesibilitas_sp`, mengikuti Tabel 2.1 Monografi.
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

9. **Provinsi dan kabupaten/kota dimuat dari data referensi nasional** (2026-09-02): 38 provinsi dan 514 kabupaten/kota berkode BPS pada `app/Support/DataWilayah.php`. Daftar ini melayani pemilihan daerah asal transmigran, yang dapat berasal dari mana pun di Indonesia, sehingga dua baris lokus tidak memadai.
9a. **Kecamatan dan desa TETAP terbatas wilayah lokus** sampai Tahap 3. Berkas sumber memuat 7.000 kecamatan dan 83.000 kelurahan, sedangkan komponen `pilih-cari` menyematkan seluruh opsi ke dalam HTML; hanya wilayah ber-SP yang bermakna pada pemilihan desa. Pemuatan penuh menunggu pengambilan bertahap lewat endpoint.
9b. **Nama kabupaten disimpan beserta awalan Kabupaten atau Kota.** Awalan itu bukan hiasan: nama kabupaten TIDAK unik secara nasional, dan Kabupaten Kupang (5301) berbeda dari Kota Kupang (5371). Setiap isian yang memilih kabupaten wajib menampilkan nama provinsinya sebagai pembeda.
10. **Halaman Data Master Wilayah menyajikan keempat tingkat dalam SATU tabel**, dengan tingkat sebagai kolom sekaligus penyaring (2026-09-02, menggantikan empat tab). Penyaring tingkat **wajib mencantumkan jumlah tiap tingkat**, sebab judul tab lama menampilkannya; menghapusnya tanpa memindahkan angka itu membuat pembaca kehilangan keterangan yang sebelumnya ada. Pencarian mencakup nama, induk, dan kode.
10a. Alasan pencabutan tab: sejak poin 9, tab Kabupaten memuat 514 baris tanpa pencarian maupun paginasi, dan mencari satu nama menuntut petugas menebak lebih dulu ia berada di tab mana. Tab juga sudah dua kali melahirkan cacat, yaitu tab bawaan yang keliru dan tingkat form yang tidak mengikuti tab yang terbuka.
11. **Form SP menempatkan Kawasan sebelum Desa**, dan memilih kawasan menyaring daftar desa menjadi desa pada kabupaten kawasan tersebut (2026-09-02). Sejalan dengan pola form Rumah (6a.12) dan form Lahan (7.12) yang menaruh penentu sebelum yang ditentukan.
11a. **Penyaringannya menempuh KABUPATEN, bukan relasi kawasan ke desa.** Kawasan dan desa adalah dua cabang terpisah yang baru bertemu di SP (poin 2), sehingga menautkan desa langsung ke kawasan berarti mengarang relasi yang sengaja tidak dimodelkan. Rantai yang dipakai sudah tersedia seluruhnya: kawasan menaut kabupaten, dan desa menaut kecamatan lalu kabupaten.
11b. Desa tetap dapat dipilih ketika kawasan belum dipilih. Urutan pengisian adalah anjuran, bukan penghalang.

### 4b. Aturan Fitur Inventaris dan Fasilitas SP
1. Inventaris SP dan fasilitas SP dikelola sebagai dua daftar terpisah yang menempel pada satu SP.
2. Setiap entri wajib memuat nama barang/fasilitas, tahun perolehan, dan sumber dana.
3. Sumber dana mengikuti pilihan baku: APBN, APBD Provinsi, APBD Kabupaten, Dinas Transmigrasi Kabupaten, Dinas Pertanian Kabupaten, Lembaga Swadaya Masyarakat, dan Lainnya.
4. Setiap entri wajib mencatat status penyerahan dan dapat dilampiri dokumen pendukung.
5. Inventaris dan fasilitas harus dapat direkap per SP untuk kebutuhan laporan aset kawasan.

### 4c. Aturan Halaman Detail Satuan Permukiman (SP)
1. **Standardisasi Rute RESTful:** Rute rincian SP menggunakan pola RESTful baku `Route::get('/sp/{sp}', ...)->where('sp', '[0-9]+')->name('sp.detail')`. Rute lama `/dashboard/sp/{sp}` dialihkan secara permanen (HTTP 301) ke `/sp/{sp}`.
2. **Struktur Tata Letak 2-Kolom Asimetris:** Halaman rincian SP (`resources/views/pages/sp/detail.blade.php`) menggunakan grid 2-kolom (`lg:grid-cols-[20rem_minmax(0,1fr)]`):
   - **Kolom Kiri (Sticky Sidebar):** Kartu identitas profil SP, kode SP, kecamatan/desa, tahun penempatan, luas lahan, status kondisi & skor SP, kapasitas/keterisian KK, dokumen SK penetapan, catatan/keterangan wilayah, dan peta mini koordinat Leaflet OSM.
   - **Kolom Kanan (6 Tab Domain):**
     - `ringkasan`: 4 Stat Cards KPI, 2 grafik ApexCharts khusus SP (Tren KK & Panen), dan rincian 16 Parameter Layanan Dasar SP.
     - `warga`: Tabel Warga Transmigran / KK dan Tabel Rumah & Hunian beserta tautan drill-down ke modul masing-masing.
     - `pertanian`: Tabel Bidang Lahan, Kelompok Tani (Poktan), dan Catatan Hasil Panen beserta tautan drill-down.
     - `aset`: Tabel Infrastruktur Kawasan, Fasilitas Umum SP, dan Inventaris Operasional SP beserta tautan drill-down.
     - `pengaduan`: Tabel Pengaduan Masuk dari SP tersebut beserta tautan drill-down.
     - `monografi`: Data Geografis & Iklim Bab II Monografi SP dan Tabel Rute Aksesibilitas.
3. **Peniadaan Switcher SP:** Bilah navigasi switcher antar-SP di halaman rincian ditiadakan; navigasi berpindah SP dilakukan melalui Daftar SP (`/sp`) atau breadcrumb `Wilayah & SP > Satuan Permukiman > {Nama SP}`.
4. **Tombol Aksi Header:** Header menyediakan tombol primer "Ubah Data SP" (membuka modal edit data SP) dan tombol sekunder "Kembali ke Daftar SP" (`route('sp.index')`).
5. **State Navigasi Tab URL Query String:** Menggunakan komponen helper Alpine.js `hashTabs('ringkasan')` yang otomatis menyinkronkan state aktif ke URL `?tab=...` (misal `/sp/1?tab=pertanian`), memungkinkan bookmark, deep-linking, dan menjaga posisi tab saat modal form tersimpan.

### 5. Aturan Hak Akses

#### 5.0 Prinsip dasar

1. Sistem wajib menggunakan **role-based access control** dengan role yang bersifat **dinamis**, bukan dikunci di dalam kode.
2. Role disimpan sebagai data pada tabel `role`, sehingga Admin dapat membuat, mengubah, dan menonaktifkan role lewat antarmuka tanpa mengubah struktur database.
3. Hak akses ditentukan oleh **dua hal yang terpisah**:
   - **Kewenangan** menjawab *boleh melakukan apa*, contoh `transmigran.lihat` dan `transmigran.ubah`,
   - **Cakupan data** menjawab *boleh melihat data siapa*, dengan nilai `Semua` atau `Per SP`.
4. Daftar kewenangan ditanam sistem lewat seeder dan **tidak dapat ditambah atau dihapus Admin**, karena setiap kewenangan harus punya pasangan pemeriksa di dalam kode. Admin hanya memasangkannya ke role.
5. **Seluruh pengguna sistem adalah petugas.** Warga transmigran tidak memiliki akun; data mereka dikelola petugas, sedangkan pengaduan diajukan lewat kanal publik tanpa login (§10b).

#### 5.0a Empat role bawaan

Dibuat lewat seeder sebagai konfigurasi awal agar sistem langsung dapat dipakai. Seluruhnya bertanda `is_bawaan` sehingga tidak dapat dihapus, tetapi kewenangannya masih dapat disesuaikan kecuali role Admin.

| Role | Cakupan data | Ringkasan kewenangan |
|---|---|---|
| **Admin** | Semua | Akses penuh: kelola pengguna, role, data master, konfigurasi, dan audit log. **Terkunci**, kewenangannya tidak dapat diubah |
| **Dinas Transmigrasi** | Semua | Pantau dashboard dan laporan kawasan; tambah dan ubah data wilayah, SP, transmigran, rumah, lahan, dan infrastruktur; tangani pengaduan bidang ketransmigrasian |
| **Dinas Pertanian** | Semua | Pantau dashboard dan laporan pertanian; tambah dan ubah data poktan, komoditas, panen, alsintan, dan saprotan; tangani pengaduan bidang pertanian |
| **Operator SP** | Per SP | Tambah dan ubah data transmigran, rumah, lahan, dan panen **hanya pada SP yang ditugaskan**. Tanpa kewenangan hapus, tanpa akses manajemen pengguna dan audit log |

#### 5.0b Cakupan data

6. Cakupan data wajib diterapkan sebagai **penyaring query**, bukan sekadar menyembunyikan menu:

   | Cakupan | Penyaring |
   |---|---|
   | `Semua` | tanpa penyaring |
   | `Per SP` | dibatasi SP yang ditugaskan pada tabel `user_satuan_permukiman` |
   | `Per Bidang` | dibatasi `pengaduan.bidang` yang sesuai dinasnya |

6a. **Kedua dinas sengaja tidak simetris.** Dinas Transmigrasi bercakupan `Semua`, bukan `Per Bidang`, sebab sistem ini milik Dinas Transmigrasi sebagai pengelola kawasan; merekalah yang menyaring laporan berbidang kosong dan menetapkan bidangnya (10b poin 7d). Dinas Pertanian bercakupan `Per Bidang` agar daftarnya tidak dibanjiri laporan ketransmigrasian.

6b. Konsekuensi yang diterima sadar: satu-satunya jalan laporan sampai ke Dinas Pertanian adalah lewat penetapan Admin atau Dinas Transmigrasi. Peredamnya, filter bidang pada halaman daftar menyediakan pilihan **Belum ditentukan** beserta jumlahnya (10b poin 7e), sehingga antrean penyaringan tidak menumpuk diam-diam.
   
7. Akun berrole bercakupan `Per SP` **wajib** memiliki minimal satu penugasan SP. Bila belum ditugaskan, pengguna tidak melihat data apa pun, bukan melihat seluruhnya. Ini disengaja agar kelalaian penugasan tidak berubah menjadi kebocoran data.

#### 5.0b-1 Rancangan penegakan cakupan data (ditetapkan 2026-09-02, mengikat Tahap 3)

> **Kedudukan bagian ini.** Poin 6 mewajibkan cakupan ditegakkan sebagai penyaring query,
> tetapi tidak menyatakan DI MANA penyaring itu dipasang. Tanpa penetapan itu,
> penegakannya akan tersebar ke tiap tempat pengambilan data satu per satu, dan satu
> tempat yang terlewat tidak memerahkan apa pun. Bagian ini menutup kekosongan tersebut
> sebelum Model pertama ditulis.

8. **Titik penegakan tunggal: Eloquent Global Scope.** Tiap model yang datanya bercakupan
   wajib memasang satu global scope yang membaca cakupan pengguna aktif. Penyaringnya
   melekat pada MODEL, bukan pada pemanggilnya, sehingga query yang lupa menyaring tidak
   mungkin ada. Alasannya terukur: pengambilan data pada tahap frontend tersebar di 167
   pemanggilan `DummyData` di dalam `routes/web.php` yang mencakup 65 metode berbeda.
   Menegakkan cakupan satu per satu di sana berarti 167 peluang terlewat, dan yang
   terlewat gagal secara senyap: datanya tampil, tidak ada galat, dan tidak ada yang tahu.

8a. **Dua cara yang ditolak, beserta alasannya.** Penyaringan di dalam controller ditolak
   sebab ia mengulang maksud yang sama di tiap aksi dan tidak berlaku bagi query yang
   dipanggil dari tempat lain, misalnya laporan, ekspor, dan perintah artisan.
   Penyaringan di dalam view ditolak lebih tegas: view dilarang mengambil datanya
   sendiri, dan menyaring di sana berarti data yang tidak boleh dilihat SUDAH terlanjur
   diambil dari basis data.

9. **Penyaring dipasang pada pemilik SP, bukan diulang pada tiap turunannya.** Tiga belas
   tabel membawa `satuan_permukiman_id` secara langsung: `rute_aksesibilitas_sp`,
   `inventaris_sp`, `fasilitas_sp`, `fasilitas_sp_cakupan`, `penilaian_sp`,
   `transmigran`, `rumah`, `poktan`, `lahan`, `infrastruktur`,
   `infrastruktur_sp`, `pengaduan`, dan `user_satuan_permukiman`. Sisanya mewarisi
   SP lewat induknya:

   | Tabel | Mewarisi lewat |
   |---|---|
   | `anggota_keluarga`, `riwayat_kepala_keluarga` | `transmigran` |
   | `riwayat_penghunian` | `rumah` |
   | `anggota_poktan`, `komoditas_poktan`, `penanaman` | `poktan` |
   | `hasil_panen` | `penanaman` lalu `poktan` |
   | `alsintan_distribusi`, `saprotan_distribusi` | `poktan` |

9a. **Pengadaan `alsintan` dan `saprotan` induk TIDAK disaring per SP.** Sejak
   Putaran 7 barisnya mendeskripsikan bendanya, dan SP baru muncul pada baris
   distribusinya. Pengadaan yang belum disalurkan tidak berada di SP mana pun, sehingga
   menyaringnya per SP menyembunyikan barang gudang UPT dari semua orang termasuk yang
   berhak. Yang disaring adalah distribusinya.

9b. **Data referensi tidak pernah disaring:** wilayah, kawasan, satuan, komoditas,
   `daftar_pilihan`, `parameter_penilaian_sp`, `status_kondisi_sp`, role, dan permission.
   Seluruhnya data master yang justru dibutuhkan tiap pengguna untuk membaca datanya
   sendiri; menyaringnya membuat dropdown kosong tanpa sebab yang terlihat.

10. **Akun `Per SP` tanpa penugasan melihat NOL baris, bukan seluruhnya** (menegaskan
    poin 7 pada tingkat teknis). Global scope yang menerima daftar SP kosong wajib
    menghasilkan penyaring yang tidak meloloskan apa pun, BUKAN melewatkan penyaringan.
    Ini kekeliruan yang paling mudah terjadi, sebab daftar kosong secara naluriah
    diterjemahkan menjadi tanpa syarat. Akibatnya kebalikan dari yang dimaksud: akun
    yang paling tidak berhak justru melihat segalanya.

11. **Data yang tidak boleh dilihat membalas 404, bukan 403.** Balasan 403 menyatakan
    barisnya ADA tetapi tidak boleh dibuka, dan pernyataan itu sendiri kebocoran:
    penyerang dapat memetakan keberadaan data SP lain hanya dari beda balasannya.
    Balasan 403 tetap dipakai untuk kewenangan AKSI, misalnya menekan hapus tanpa izin
    hapus; keduanya persoalan berbeda.

12. **Angka rekap dan dashboard yang menyempit wajib menyatakan cakupannya.** Operator SP
    yang melihat dashboard mendapat angka SP-nya sendiri, bukan angka kawasan. Tanpa
    keterangan itu pada judul, angkanya dapat disalin ke laporan sebagai total kawasan.
    Alasannya sama dengan kewajiban menulis periode dan cakupan pada rekap panen
    (9 poin 8b dan 8o), dan berlaku pada kalimat cakupan laporan (12 poin 8).

13. **Penyaringan terjadi sebelum paginasi.** Menyaring koleksi hasil setelah query
    membuat penghitung halaman ikut menghitung baris yang tidak boleh dilihat, sehingga
    jumlah halaman membocorkan banyaknya data SP lain meski isinya tidak tampil.

14. **Cakupan `Per Bidang` berdiri sendiri dan hanya berlaku pada `pengaduan`.** Ia
    tidak menggantikan `Per SP` maupun bertumpuk dengannya: Dinas Pertanian bercakupan
    `Per Bidang` dengan jangkauan seluruh SP, sedangkan Operator SP bercakupan
    `Per SP` tanpa pembatasan bidang.

15. **Satu jalan memintas penyaring, dan ia wajib eksplisit.** Perintah artisan, seeder,
    dan pekerjaan latar berjalan tanpa pengguna aktif, sehingga scope tidak menemukan
    cakupan siapa pun. Keadaan itu wajib menghasilkan data LENGKAP, tetapi hanya lewat
    pemanggilan yang menyatakannya sendiri, bukan karena scope diam-diam menyerah ketika
    tidak menemukan pengguna. Yang kedua akan membuat setiap kekeliruan autentikasi
    berubah menjadi kebocoran menyeluruh.

16. **Diuji sebagai penjaga, bukan diperiksa manual.** Tahap 3 wajib menyertakan uji yang
    memastikan: akun `Per SP` hanya menerima baris SP-nya, akun `Per SP` tanpa
    penugasan menerima nol baris, data SP lain membalas 404, dan akun bercakupan
    `Semua` tidak ikut tersaring. Tanpa uji, kebocoran cakupan tidak pernah
    memerahkan apa pun sebab tampilannya normal.


#### 5.0c Perlindungan

8. Role Admin tidak dapat dihapus maupun dikurangi kewenangannya, agar sistem tidak pernah kehilangan jalur administrasi.
9. Role yang masih dipakai minimal satu akun tidak dapat dihapus.
10. Kewenangan `lihat` adalah prasyarat seluruh aksi lain pada fitur yang sama. Memberi kewenangan `ubah` tanpa `lihat` ditolak sistem sebagai galat konfigurasi.
11. Setiap perubahan susunan kewenangan sebuah role wajib tercatat pada audit log.
12. Data pribadi transmigran dan penghuni kawasan bersifat sensitif: tampilan penuh hanya untuk role berwenang, sedangkan role lain menerima data dalam bentuk agregat.
13. Pembatasan akses wajib diterapkan di sisi query dan controller, bukan sekadar menyembunyikan menu di antarmuka. Menu yang tidak berhak diakses tidak dirender sama sekali.

#### 5.1 Susunan kewenangan role bawaan

> **Kedudukan tabel ini.** Sejak role menjadi dinamis (§5.0), tabel di bawah bukan lagi aturan permanen yang dikunci di dalam kode, melainkan **konfigurasi awal** yang ditanam seeder. Admin dapat mengubahnya lewat menu Pengaturan Role, kecuali baris role Admin yang terkunci.

Keterangan: **L** = lihat / **T** = tambah / **U** = ubah / **H** = hapus / **-** = tanpa akses

| Fitur | Admin | Dinas Transmigrasi | Dinas Pertanian | Operator SP |
|---|---|---|---|---|
| Manajemen pengguna | L T U | - | - | - |
| Pengaturan role | L T U H | - | - | - |
| Audit log | L | - | - | - |
| Data master wilayah | L T U H | L | L | L |
| Kawasan transmigrasi | L T U H | L | L | L |
| Satuan permukiman (SP) | L T U H | L T U | L | L |
| Inventaris SP | L T U H | L T U | L | L T U |
| Fasilitas SP | L T U H | L T U | L | L T U |
| Data master satuan | L T U H | L | L | L |
| Transmigran | L T U H | L T U | L | L T U |
| Rumah & hunian | L T U H | L T U | L | L T U |
| Riwayat penghunian | L T U H | L T | L | L T |
| Riwayat kepala keluarga | L T U | L T | L | L |
| Data master daftar pilihan | L T U | L T U | L | L |
| Penilaian kondisi SP | L U | L U | L | L |
| Lahan | L T U H | L T U | L | L T U |
| Kelompok tani | L T U H | L | L T U | L T U |
| Anggota poktan | L T U | L | L T U | L T U |
| Alsintan | L T U H | L | L T U | L T U |
| Saprotan | L T U H | L | L T U | L T U |
| Komoditas | L T U H | L | L T U | L |
| Penanaman | L T U H | L | L T U | L T U |
| Hasil panen | L T U H | L | L T U | L T U |
| Infrastruktur SP | L T U H | L T U | L T U | L T U |
| Pengaduan | L T U H | L T U | L T U | L T |
| Penanganan pengaduan | L T U | L T U | L T U | - |
| Pengelolaan Konten (CMS) | L U | L U | - | - |
| Dashboard | L | L | L | L |

**Cakupan data tiap role:** Admin, Dinas Transmigrasi, dan Dinas Pertanian bercakupan `Semua`. Operator SP bercakupan `Per SP`, sehingga seluruh kewenangannya otomatis terbatas pada SP yang ditugaskan padanya.

**Catatan penting:**
1. Dinas hanya menangani pengaduan sesuai bidangnya: bidang ketransmigrasian untuk Dinas Transmigrasi, bidang pertanian untuk Dinas Pertanian. Pembatasan ini berlaku pada level query, bukan lewat kewenangan.
2. Penghapusan data utama memakai *soft delete* agar dapat dipulihkan dan tetap tercatat pada audit log.
3. Operator SP sengaja tidak diberi kewenangan hapus. Ia bertugas memasukkan dan memutakhirkan data, sedangkan penghapusan menjadi kewenangan dinas dan admin.
4. **Operator SP tidak memegang kewenangan apa pun pada Penanganan pengaduan,** dan inilah pembeda pokoknya dari role dinas. Menangani pengaduan berarti **memutuskan tindak lanjut atas nama dinas** beserta menutup laporan warga; itu kewenangan jabatan, bukan soal kemampuan teknis. Operator SP tetap boleh melihat dan mencatat pengaduan dari wilayahnya, sehingga laporan warga tidak pernah tertahan menunggu petugas dinas hadir di lokus.
5. **Ekspor tidak lagi menjadi kewenangan tersendiri** (dicabut 2026-08-17). Mengekspor adalah cara lain membaca data yang **sudah** boleh dilihat, bukan tindakan baru, sehingga ia mengikuti kewenangan `lihat` pada fitur yang bersangkutan. Sebelumnya huruf `E` berdiri terpisah, tetapi 24 sel memberi `lihat` tanpa `export` tanpa alasan yang dapat dijelaskan, dan Admin terpaksa menyusun satu maksud dua kali. Pembatasan sebaran data ditangani **cakupan data** (5.2): Operator SP hanya dapat mengekspor data SP yang ditugaskan padanya, sebab penyaringannya terjadi di tingkat query, bukan di tombol.
6. **Inventaris SP dan Fasilitas SP adalah dua fitur terpisah**, masing-masing dengan kewenangannya sendiri. Keduanya memang bernilai sama pada konfigurasi awal, tetapi tetap dipisah karena berupa dua tabel dan dua halaman yang berbeda (§4b poin 1), sehingga Admin dapat memberi kewenangan berbeda antara aset bergerak dan bangunan fasilitas. Sampai 2026-08-12 keduanya tertulis sebagai satu baris di sini, tidak sejalan dengan `data-dictionary.md` §13.1, `erd.md`, dan `ui-spec.md` yang sejak awal memisahkannya.
7. Anggota poktan yang berhenti ditandai berstatus "Sudah Keluar", bukan dihapus, agar riwayat tetap utuh.
8. **Riwayat kepala keluarga tidak dapat dihapus siapa pun**, termasuk Admin. Admin hanya memegang `ubah` untuk membetulkan salah ketik pada tanggal atau sebab; tanpa itu petugas akan mencatat suksesi kedua sebagai penebus kekeliruan yang pertama. Riwayat ini menyangkut keabsahan penguasaan lahan, sebab ia menyatakan siapa pemegang jatah pada rentang waktu tertentu (6.5f).

### 6. Aturan Fitur Transmigran
1. Data transmigran harus menjadi data inti sistem.
2. Field minimal yang wajib dicatat:
   - nama kepala keluarga,
   - NIK,
   - nomor KK,
   - agama,
   - pekerjaan kepala keluarga,
   - jumlah pendapatan kepala keluarga per bulan,
   - status keanggotaan kelompok tani.
2a. **`jumlah anggota keluarga` bukan lagi isian** (diubah 2026-08-28, Rombongan B). Dihitung 1 (kepala keluarga) + cacah baris `anggota_keluarga`. Diketik petugas, ia cepat berselisih dengan daftar anggota yang sebenarnya.
2b. **`usia` tidak dicatat, dihitung dari `tanggal_lahir`.** Bertambah sendiri tiap tahun. Berlaku bagi kepala keluarga maupun tiap anggota keluarga.
3. NIK wajib 16 digit dan divalidasi keunikannya; nomor KK divalidasi formatnya.
4. Satu data transmigran harus bisa dikaitkan dengan desa/SP, rumah, lahan, komoditas, dan hasil panen, dengan kardinalitas:
   - satu transmigran dapat memiliki **banyak lahan usaha** (one-to-many),
   - satu transmigran menempati **tepat satu rumah** (one-to-one),
   - satu transmigran dapat menjadi anggota satu kelompok tani.
5. **Pergantian kepala keluarga dicatat sebagai riwayat tersendiri** (ditetapkan 2026-08-20 atas keterangan pemilik proyek). Ketika kepala keluarga meninggal, merantau, atau bercerai, kedudukannya berpindah kepada istri, lalu kepada anak pertama bila istrinya juga tiada. Rumah tangganya berlanjut, sehingga baris `transmigran` yang ada **disunting**, bukan diganti baris baru: jatah rumah dan lahan diberikan kepada KK, bukan kepada suaminya secara pribadi, dan ketujuh relasi yang menaut ke transmigran memang seharusnya tetap utuh.
5a. **Audit log saja tidak cukup.** Ia merekam bahwa nama berubah, tetapi **tidak dapat membedakan suksesi dari perbaikan salah ketik**, sebab keduanya berbentuk aksi `Ubah` pada kolom yang sama. Karena itu dibuat tabel `riwayat_kepala_keluarga` yang menyimpan kedua sisi identitas, nomor KK sebelum dan sesudah, tanggal, sebab, serta kedudukan pengganti.
5b. **Suksesi adalah tindakan tersendiri, bukan efek samping form ubah.** Ia dijalankan lewat tombol dan modal khusus di halaman rincian. Bila ia lahir dari penyuntingan nama pada form biasa, setiap perbaikan ejaan akan mengotori riwayat suksesi, yaitu kekaburan yang justru hendak ditutup.
5c. **Nomor KK dapat ikut berubah**, sebab Dukcapil menerbitkan KK baru ketika kepala keluarganya berganti. Keduanya disimpan; bila tidak berubah, diisi sama.
5d. **Pengganti dipilih dari daftar anggota keluarga**, tidak diketik (diubah 2026-08-28, Stage B3; membalik `erd.md` §7.4a). Nama, NIK, dan hubungannya "naik" menimpa baris `transmigran`, lalu barisnya sebagai anggota keluarga **dihapus**. **Urutan pengganti tidak ditegakkan sistem**: aturan pasangan lalu anak tertua adalah ketentuan Dukcapil, dan daftarnya hanya diurutkan sebagai penunjuk. Yang direkam adalah siapa yang benar-benar ditunjuk. Bila keluarga belum punya anggota terdata, suksesi tidak dapat dijalankan sampai anggotanya dicatat.
5e. **Jabatan ketua poktan tidak diwariskan.** Bila keluarga yang bersangkutan menjabat ketua lewat jalur `Kepala Keluarga`, suksesi wajib menuntut petugas memutuskan: mengosongkan jabatan itu atau meneruskannya kepada kepala keluarga baru. Membiarkannya berpindah sendiri berarti sistem mengangkat ketua tanpa seorang pun memutuskan, padahal ketua dipilih anggota. Sebaliknya **keanggotaan poktan memang mengikuti**, sebab keanggotaan melekat pada keluarga (7a poin 3a); petugas cukup diberi tahu.
5f. Kewenangan suksesi dipegang **Admin dan Dinas Transmigrasi** saja. Admin memegang `ubah` untuk membetulkan salah ketik pada riwayat; **tidak ada kewenangan hapus** bagi siapa pun, sebab riwayat suksesi menyangkut keabsahan penguasaan lahan.
6. Setiap transmigran dapat dilampiri dokumen pendukung.
7. Data transmigran harus bisa ditambah, diubah, dicari, difilter, dan diekspor.
8. Data transmigran harus mendukung kebutuhan monitoring kawasan dan pendataan awal.
9. **Anggota keluarga didata satu per satu** (ditetapkan 2026-08-28 atas permintaan pemilik proyek, membalik `erd.md` §7.4). Tabel `anggota_keluarga` menyimpan istri atau suami, anak, dan anggota lain selain kepala keluarga. Diisi lewat daftar dinamis pada form kepala keluarga.
9a. **NIK anggota boleh kosong** bagi balita yang belum memilikinya; bila diisi, tetap 16 digit dan divalidasi keunikannya.
9b. **Cabang isian anak menurut kegiatannya** (`Belum Sekolah` / `Masih Sekolah` / `Bekerja` / `Tidak Bekerja`). `Belum Sekolah` tidak menambah isian; `Masih Sekolah` mengisi jenjang berjalan; `Bekerja` mengisi pendidikan terakhir, pekerjaan, dan pendapatan; `Tidak Bekerja` mengisi pendidikan terakhir saja. Isian bersyarat memakai `:required`/`:disabled` Alpine, bukan `required` tetap.
9c. **Mutasi anggota keluarga ditandai, tidak dihapus** (diubah 2026-08-29, Putaran 6; membalik sebagian ketentuan lama). Anggota keluarga selain kepala keluarga memiliki `status` (`Aktif` / `Meninggal` / `Pindah`, enum `StatusAnggotaKeluarga`), `tanggal_peristiwa`, dan `keterangan_peristiwa`. Baris anggota yang meninggal atau pindah **tetap disimpan** dan tidak lagi dihitung sebagai jiwa keluarga (`jumlah_anggota_keluarga`, pilihan pengganti KK, rekap agama). Alasannya: Laporan Monografi SP memerlukan angka mutasi penduduk, dan menghapus barisnya menghilangkan satu-satunya sumbernya. Pencatatannya lewat tombol "Catat Peristiwa" per baris pada tab Anggota Keluarga halaman rincian transmigran (rute `transmigran.anggota.catat-peristiwa`). Ini **bukan** riwayat lengkap, hanya penanda satu peristiwa terakhir.
    - **Kepala keluarga tidak memakai `status` ini.** Peristiwa pada kepala keluarga (meninggal, pindah, cerai) selalu lewat alur suksesi (poin 9d + `riwayat_kepala_keluarga` + `AlasanPergantianKK`), sebab kepala keluarga membawa rumah, lahan, dan keanggotaan poktan yang harus berpindah bersamanya.
    - **Kelahiran tidak ditandai di halaman transmigran**; Laporan Monografi menghitungnya dari `tanggal_lahir` anggota dibanding `tahun_penempatan` SP.
    - ~~Ketentuan lama (dicabut sebagian 2026-08-29): "Tidak ada riwayat anggota keluarga. Anggota yang meninggal atau pindah dihapus dari daftar." Alasan lama: peristiwa yang perlu jejak permanen hanya pergantian kepala keluarga. Masih benar untuk kepala keluarga; tidak lagi untuk anggota biasa sejak laporan mutasi penduduk dibutuhkan.~~
9d. **Suksesi kepala keluarga memilih pengganti dari daftar anggota keluarga** (diubah 2026-08-28; menggantikan poin 5d yang mengharuskan mengetik). Baris `anggota_keluarga` sang pengganti dihapus, datanya menimpa baris `transmigran`, dan peristiwanya direkam `riwayat_kepala_keluarga`. Rincian alur pada §6.5 setelah Stage B3.

### 6a. Aturan Fitur Rumah dan Hunian
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
12. **Hirarki Form Rumah: Penghunian menentukan Satuan Permukiman** (2026-08-31). Bagian Penghunian ditempatkan di Section 1 sebelum Spesifikasi Bangunan. Saat status `Dihuni`, memilih KK Penghuni otomatis mengisi dan memilih Satuan Permukiman sesuai SP transmigran. Saat status `Tidak Dihuni`, isian KK dinonaktifkan (`disabled`) dan pemilihan SP dilakukan secara manual untuk mendata lokasi rumah kosong.

### 7. Aturan Fitur Lahan
1. Setiap lahan harus memiliki identitas yang jelas.
2. Lahan dibedakan menurut **peruntukannya**: **lahan pekarangan** dan **lahan usaha**. **SATU BARIS = SATU KELUARGA sejak Putaran 15 (2026-09-02):** kedua bidang dicatat sebagai KOLOM pada baris yang sama (`luas_pekarangan`, `luas_usaha`), bukan dua baris berperuntukan, sebab jumlahnya memang tetap (poin 8). Koordinatnya TETAP DUA PASANG (`lintang_pekarangan`/`bujur_pekarangan` dan `lintang_usaha`/`bujur_usaha`) karena kedua bidang berada di tempat berbeda. Sifat pengairan dicatat sebagai komposisi luas lahan USAHA (poin 5).
2a. **`NULL` pada `luas_pekarangan` (atau `luas_usaha`) berarti keluarga BELUM MENERIMA bidang itu, bukan menerima seluas nol.** Rekap luas MENJUMLAH KOLOM (`SUM(luas_pekarangan) + SUM(luas_usaha)`), bukan menjumlahkan baris. Enum `PeruntukanLahan` beserta `lahanUsaha()`/`nilaiLahanUsaha()` dicabut; "punya lahan usaha?" dijawab dengan `luas_usaha !== null`.
2b. Nilai `Lahan Usaha I` dan `Lahan Usaha II` sempat ditambahkan pada 2026-08-18 atas dugaan bahwa lahan usaha dibagikan bertahap, lalu **dibatalkan pada hari yang sama** setelah keadaan lapangan diketahui (lihat poin 8). Dicatat di sini agar tidak diusulkan ulang tanpa konfirmasi.
3. Data lahan harus dapat dikaitkan dengan transmigran, kelompok tani, dan komoditas.
4. Data lahan minimal memuat informasi luas, lokasi, titik koordinat, status, dan tujuan/jenis pemanfaatan.
5. **Lahan kering dan lahan basah adalah komposisi luas lahan USAHA, bukan kategori bidang** (ditetapkan 2026-08-20 atas keterangan lapangan pemilik proyek). Satu bidang lahan usaha seluas 1 ha dapat digarap 0,5 ha kering dan 0,5 ha basah sekaligus, dan pembagiannya ditentukan penggarapnya. Karena itu luasnya dicatat pada dua kolom, `luas_kering` dan `luas_basah`, yang jumlahnya wajib sama dengan `luas_usaha`.
5a. Sebelum tanggal itu sifat pengairan disimpan sebagai enum satu nilai per bidang, sehingga **bidang campuran tidak dapat dicatat sama sekali**. Petugas terpaksa memilih salah satu, dan separuh luasnya hilang dari rekap tanpa ada yang menyadarinya — kegagalan senyap yang sudah pernah terjadi pada penjumlahan luas usaha (poin 2a).
5b. **Lahan usaha tetap satu bidang dengan satu titik koordinat.** Yang dipecah hanya angka luasnya, sebab pemecahan kering/basah tidak melahirkan bidang baru dan tidak berpindah tempat. Bidang yang seluruhnya kering diisi `luas_basah = 0`, bukan dikosongkan.
5c. Penyaringan "lahan basah" pada halaman daftar berarti **bidang yang memiliki bagian basah** (`luas_basah > 0`), bukan bidang yang seluruhnya basah. Rekapnya memakai penjumlahan kolom, sejalan dengan poin 10.
4a. **Status hak atas tanah bukan status kepemilikan** (diperbaiki 2026-08-18). Nilainya `Belum Bersertifikat`, `Hak Milik`, `Hak Milik Bersama`, `Hak Pakai`, `Sewa`, `Garapan`. **HPL dan SHM dicabut dari daftar ini**: HPL adalah Hak Pengelolaan milik instansi atas tanah kawasan sehingga tidak pernah menjadi hak seorang transmigran, sedangkan SHM adalah nama sertifikatnya, bukan nama haknya. Keduanya menjadi jenis dokumen. Rantainya: tanah kawasan berstatus Hak Pengelolaan, lalu bidangnya dibagikan dengan status Hak Milik; sebelum sertifikat terbit, sandarannya surat keterangan pembagian tanah.
6. **Bidang lahan TIDAK memegang dokumennya sendiri** (ditetapkan 2026-09-02, Putaran 12). Legalitas lahan berada pada dua tingkat yang berbeda, dan keduanya bukan tingkat bidang:
   - **SHM** meliputi **seluruh lahan satu keluarga**, pekarangan maupun lahan usaha, sehingga melekat pada `transmigran` (peran berkas `shm`) dan diunggah **sekali**.
   - **HPL** adalah alas hak **kawasan** milik instansi (poin 4a), sehingga melekat pada `kawasan_transmigrasi` dan cukup satu untuk seluruh bidang di dalamnya.
6a. **SHM dan status sertifikat diisi dari FORM DATA LAHAN; HPL tetap bacaan** (ditetapkan 2026-09-03 atas keputusan pemilik proyek, membalik ketentuan lama "halaman lahan hanya bacaan"). Alasan lama — unggahan per-bidang melahirkan salinan sertifikat yang sama di banyak baris — **gugur sejak Putaran 15**: satu keluarga kini tepat SATU baris lahan, sehingga form lahan adalah tempat kanonis seluruh legalitas lahan keluarga. Berkas SHM tetap tersimpan pada `transmigran_berkas` peran `shm` dan statusnya pada `transmigran.status_sertifikat` (form lahan hanya permukaan entrinya). **HPL tetap bacaan** beserta tautan ke Data Kawasan, sebab ia alas hak kawasan milik instansi — satu untuk seluruh bidang, bukan hak keluarga.
6b. ~~Ketentuan lama (dicabut 2026-09-02): tabel `dokumen_lahan` tersendiri beserta `nomor_dokumen` dan `tanggal_terbit` yang "wajib dipertahankan sebab data legal yang harus dapat dicari", serta pivot `dokumen_lahan_bidang` agar satu HPL mencakup banyak bidang.~~ **Alasan pencabutan:** pivot m2m itu menambal AKIBAT, bukan sebab. Begitu HPL ditempatkan pada kawasan dan SHM pada keluarga, kebutuhan satu-dokumen-banyak-bidang lenyap dengan sendirinya. Pemilik proyek juga menyatakan nomor sertifikat tidak pernah dicari lewat sistem, dan modul lain yang menyimpan SK pun tidak diperlakukan istimewa.
6c. **Status sertifikat dicatat tersendiri** pada `transmigran.status_sertifikat` bernilai `Sudah`, `Belum`, atau `Belum Didata` (enum `StatusSertifikat`; isiannya di **form Data Lahan**, sepola 6a). Ketiadaan unggahan **tidak boleh** disimpulkan sebagai belum bersertifikat: ia dapat berarti sudah punya tetapi belum diunggah petugas. Nilai ketiga memisahkan keduanya, sepola dengan 10a.4c yang menolak baris hilang sebab "pembaca tidak dapat membedakan tidak ada dari belum didata". Inilah yang dibaca laporan ketika dinas menanyakan berapa keluarga yang belum bersertifikat.
7. ~~**DICABUT 2026-09-02.** Lahan usaha juga mencatat pola tanam, peralatan/perlengkapan pertanian, dan kendala yang dihadapi.~~ Ketiganya dihapus atas keputusan pemilik proyek beserta tab Pengelolaan yang hanya menampung ketiganya. Rekap Monografi yang dahulu memakai `pola_tanam` sebagai penanda lahan diusahakan kini membacanya dari kolom `luas_usaha`: lahan usaha memang diusahakan, pekarangan tidak.
8. **Satu transmigran menerima TEPAT satu lahan pekarangan dan satu lahan usaha** (dipertegas 2026-09-02 atas keputusan pemilik proyek; sebelumnya "umumnya", dikoreksi 2026-08-18 dari keputusan 2026-08-10 yang menyatakan boleh lebih dari satu).
9. **Jumlah pada poin 8 kini BATAS yang ditegakkan basis data**, bukan sekadar kewajaran. Sejak Putaran 15 ditegakkan `UNIQUE (transmigran_id)` pada tabel `lahan` (menggantikan `UNIQUE (transmigran_id, peruntukan_lahan)` setelah kedua bidang menjadi kolom pada satu baris), sepola dengan `uq_rumah_transmigran` (6a.6): aturan yang hanya dijaga form dapat ditembus lewat impor maupun akses langsung.
9a. **Alur Tambah vs Ubah:** karena `transmigran_id` UNIQUE, halaman lahan hanya menawarkan alur "Tambah" bagi KK yang belum punya baris; KK yang sudah terdata disunting lewat alur "Ubah". Menawarkan Tambah untuk KK yang sudah ada akan selalu ditolak UNIQUE tanpa menjelaskan apa pun kepada petugas.
9b. **Konsekuensi yang diterima sadar:** bila kelak ditemukan keluarga dengan lahan usaha di dua petak terpisah, sistem menolak mendatanya sebagai dua entri dan petugas menjumlahkan luasnya serta memilih satu koordinat. Pemilik proyek menyatakan keadaan itu tidak ada di Kobalima Timur.
9c. **Relasi menjadi satu-ke-satu** (`UNIQUE` pada `lahan.transmigran_id`, FK tetap di `lahan`). Sebelum Putaran 15 relasinya satu-ke-banyak sebab satu KK memegang dua baris berperuntukan; setelah kedua bidang menjadi kolom, satu KK tepat satu baris.
10. Rekap luas lahan per transmigran, per poktan, maupun per desa/SP wajib **menjumlahkan kolom** (`luas_pekarangan`, `luas_usaha`, dan komponen usaha bila perlu) dari seluruh baris terkait, bukan mengambil satu baris data saja. Setelah satu baris per KK, **jumlah baris lahan ≠ jumlah bidang lahan**.
11. Lahan harus bisa dipakai sebagai dasar analisis produksi dan perencanaan.
12. **Hirarki Form Lahan: Pemilik menentukan Satuan Permukiman** (2026-08-31). Field Pemilik (transmigran) ditempatkan paling awal di Section 1 sebelum Satuan Permukiman. Memilih Pemilik langsung mengisi otomatis dropdown Satuan Permukiman sesuai SP penempatan transmigran tersebut.

### 7a. Aturan Fitur Kelompok Tani (Poktan)
1. Setiap poktan wajib memiliki profil berisi nama poktan dan desa/SP asal.
2. Data ketua poktan minimal memuat nama, NIK, telepon, dan email.
2a. **Ketua poktan punya tiga asal-usul** (diperluas 2026-08-20 atas keterangan pemilik proyek). Kolom `is_ketua_transmigran` bertipe boolean digantikan `asal_ketua` bertipe enum, sebab boolean hanya sanggup membedakan dua keadaan sedangkan keadaan lapangan ada tiga:
   - **Kepala Keluarga** — dipilih dari daftar transmigran; nama, NIK, dan telepon dibaca lewat relasi agar tidak ada dua versi data yang berbeda ejaan.
   - **Anggota Keluarga** — keluarganya dipilih dari daftar, lalu **orangnya dipilih dari daftar anggota keluarga** itu (`ketua_anggota_keluarga_id`). Nama, NIK, dan hubungannya dibaca dari baris `anggota_keluarga`, tidak diketik (diubah 2026-08-28, Stage B2; sebelumnya wajib diketik sebab `erd.md` §7.4 menyatakan sistem tidak mendata anggota keluarga).
   - **Bukan Transmigran** — penduduk setempat yang bukan peserta program. Nama dan NIK diketik, dan hanya jalur inilah yang mengetik luas lahan sendiri.

   Membatasi pilihan pada daftar transmigran membuat poktan berketua penduduk setempat tidak dapat didata sama sekali, sedangkan membatasinya pada kepala keluarga membuat poktan berketua istri atau anak tidak dapat didata dengan benar.
2b. **Kontak yang disimpan pada poktan adalah kontak ketua, bukan kontak kelompok** (`telepon_ketua`, `email_ketua`, `alamat_ketua`). Dasarnya keterangan pemilik proyek: kelompok tani di Kobalima Timur tidak memiliki kontak sendiri yang berbeda dari kontak ketuanya, sehingga menyediakan dua pasang kolom hanya menyisakan satu yang selalu kosong. Telepon terisi sendiri dari data transmigran saat ketua dipilih dari daftar, tetapi **tetap dapat disunting** sebab petugas kerap memegang nomor yang lebih baru. Email diisi manual, karena tabel `transmigran` tidak menyimpan email padahal poin 2 mewajibkannya.
3. Sistem mencatat jumlah anggota beserta daftar anggota, dan **setiap anggota wajib berasal dari keluarga transmigran**. Berbeda dari ketua, anggota tidak boleh berasal dari penduduk setempat.
3a. **Keanggotaan poktan melekat pada keluarga, bukan pada kepala keluarga** (ditetapkan 2026-08-20 atas keterangan pemilik proyek). Yang terdaftar adalah orang yang benar-benar menggarap dan menghadiri pertemuan, dan ia tidak selalu kepala keluarga: bila kepala keluarga merantau, istri atau anaknya yang mewakili. Karena itu `anggota_poktan.transmigran_id` menunjuk **keluarga** yang diwakili, sedangkan `asal_wakil` menyatakan siapa wakilnya. Bila wakilnya bukan kepala keluarga, **wakilnya dipilih dari daftar anggota keluarga** itu (`anggota_keluarga_id`); nama, NIK, telepon, dan hubungan dibaca dari baris itu (diubah 2026-08-28, Stage B2; sebelumnya diketik). Telepon tetap dapat disunting.
3b. **Satu keluarga diwakili satu orang saja pada satu poktan.** Sudah ditegakkan UNIQUE `(poktan_id, transmigran_id)` yang ada, sebab `transmigran_id` kini bermakna keluarga.
3c. **Luas lahan dan koordinat ketua maupun anggota diturunkan, tidak disimpan.** Keduanya dijumlahkan dari bidang milik keluarga yang bersangkutan (7.10), sehingga tidak pernah basi ketika luas dibetulkan di modul lahan dan tidak berubah ketika wakilnya berganti. Pengecualiannya hanya ketua bertanda `Bukan Transmigran`, yang lahannya memang tidak terdata sehingga wajib diketik.
3d. **SIM Transmigrasi hanya mencatat anggota poktan dari keluarga transmigran** (2026-08-31). Bila sebuah kelompok tani di lapangan memiliki anggota campuran dengan penduduk lokal setempat, anggota non-transmigran sengaja tidak didata dan tidak dihitung dalam sistem ini. Batasan ruang lingkup ini ditegaskan pada subjudul header, metrik sidebar, tab rincian, banner edukatif di atas tabel anggota, dan bantuan form.
4. Setiap anggota mencatat nama, NIK, tanggal masuk, status keaktifan (Aktif, Tidak Aktif, Sudah Keluar), dan tanggal keluar bila ada.
4a. **Data anggota wajib dapat diubah setelah tersimpan.** Status keaktifan dan tanggal keluar pada poin 4 justru berubah belakangan, sehingga menyediakannya hanya pada saat penambahan membuat keduanya tidak pernah dapat diisi. Yang tidak disediakan adalah **penghapusan**, sesuai 5.1 catatan 7.
4b. **Jabatan anggota tidak memuat nilai `Ketua`.** Ketua ditetapkan pada profil poktan (poin 2a), dan menyediakannya juga pada daftar anggota membuat satu poktan dapat memiliki dua ketua berbeda tanpa penjaga apa pun.
4c. **Perpindahan anggota antar poktan dicatat sebagai dua baris.** Baris pada poktan lama ditandai `Sudah Keluar` beserta tanggal dan alasannya, lalu dibuat baris baru pada poktan tujuan. Memindahkan `poktan_id` pada baris yang sama akan menghapus jejak keanggotaan di poktan lama seolah tidak pernah ada.
4d. **Satu keluarga hanya boleh berstatus Aktif pada satu poktan** dalam satu waktu (6.4). Dinyatakan per keluarga, bukan per orang: sejak 3a, `transmigran_id` bermakna keluarga, sehingga satu keluarga tidak dapat diwakili istri di satu poktan dan anak di poktan lain sekaligus. UNIQUE `(poktan_id, transmigran_id)` hanya mencegah baris ganda pada poktan yang sama, sehingga pembatasan lintas-poktan ini ditegakkan di tingkat aplikasi saat menyimpan anggota baru.
4e. **Alasan keluar dipisahkan dari keterangan.** Kolom `keterangan` sempat dipakai dua maksud sekaligus: kamus data menyebutnya catatan umum, sedangkan form melabelinya "Alasan Keluar", sehingga catatan keanggotaan biasa tidak punya tempat. Kini `alasan_keluar` berdiri sendiri, mengikuti `riwayat_penghunian` yang sudah membedakan keduanya.
5. Poktan dapat ditautkan ke lahan, komoditas, alsintan, dan saprotan.
6. Poktan dapat dilampiri dokumen pendukung.
7. Rekap jumlah poktan dan anggotanya harus tersedia per desa/SP.
8. **Keanggotaan poktan ditetapkan dari sisi poktan, bukan dari form transmigran.** Kolom `status_anggota_poktan` pada transmigran adalah **penanda turunan** yang dihitung dari keanggotaan berstatus Aktif, bukan isian mandiri. Menyediakannya sebagai isian membuat dua sumber kebenaran yang tidak pernah tersinkron.

### 7b. Aturan Fitur Alsintan
1. **Alsintan selalu milik kelompok tani.** Kepemilikan pribadi dicabut 2026-08-22: seluruh menu Pertanian mencatat kelompok, bukan individu. Alat yang dibeli dari iuran anggota tetap tercatat atas nama kelompok, dengan sumber dana Swadaya.
2. **Satu pengadaan, banyak poktan** (Putaran 7, 2026-08-30). Baris `alsintan` mendeskripsikan BENDAnya: `jenis_alsintan` (data master §11.37, baru), `nama_alat`, `jumlah_total`, `tahun_pengadaan`, `sumber_dana`. Poktan penerima, jumlah per poktan, kondisi per poktan, penanda tangan, dan tanggal serah pindah ke `alsintan_distribusi` (satu baris per poktan penerima). Satu batch traktor yang dibagi ke tiga poktan dahulu harus diketik jadi tiga baris `alsintan` yang tidak saling tahu; kini satu baris pengadaan + tiga baris distribusi.
   - ~~Poin lama: "Setiap alsintan wajib mencatat nama alat, jumlah, tahun pengadaan, sumber dana, dan kondisi" beserta satu `poktan_id` per baris. Dibalik Putaran 7 sebab memaksa input redundan dan baris yang berselisih diam-diam.~~
3. **Satuan permukiman mengikuti poktan**, terbaca per baris distribusi, tidak dipilih terpisah — isian mandiri hanya membuka peluang satu alat tercatat di SP yang berbeda dari kelompoknya.
4. **Pengadaan yang belum dibagikan ke satu poktan pun tetap tercatat** (barang di gudang UPT); `jumlah_belum_tersalur` = `jumlah_total` − Σ distribusi. Σ distribusi tidak boleh melebihi `jumlah_total`.
5. **Kondisi melekat pada baris distribusi**, bukan pengadaan: unit di satu poktan dapat berkondisi berbeda dari unit yang sama di poktan lain, dan kondisi berubah di lapangan setelah barang dibagikan.
6. Setiap pengadaan dapat dilampiri dokumen pendukung (berita acara pengadaan); foto kondisi diunggah per baris distribusi.
7. Alsintan harus dapat direkap per desa/SP, per poktan, dan **per jenis alat** (kini benar-benar `jenis_alsintan`, bukan `nama_alat` yang dipakai ulang).
8. **Penanda tangan serah terima dicatat pada baris distribusi, dan itu bukan kepemilikan.** Menunjuk anggota poktan penerima yang menandatangani berita acara; alat tetap milik kelompok. Pilihannya dibatasi anggota poktan itu, ketua maupun anggota biasa.
9. Saat pengisian, memilih beberapa poktan lewat **searchable multi-select** (ui-spec §6.0a) langsung membagi rata jumlah total; petugas menyesuaikan yang tidak rata.

### 7c. Aturan Fitur Saprotan
1. Saprotan mencatat sarana produksi pertanian seperti benih, pupuk, pestisida, dan mulsa.
2. **Satu pengadaan, banyak poktan** (Putaran 7, 2026-08-30), pola sama dengan alsintan §7b poin 2. Baris `saprotan` mendeskripsikan bendanya (jenis, nama, `komoditas_id`, `varietas`, `jadwal_tanam`, `jumlah_total`, `satuan_id`, `tahun_pengadaan`, `sumber_dana`); poktan penerima, jumlah per poktan, dan tanggal serah pindah ke `saprotan_distribusi`.
   - ~~Poin lama: satu `poktan_id` per baris `saprotan`. Dibalik Putaran 7: sisa benih tak terdefinisi bila jatah satu poktan tergerus penanaman poktan lain.~~
3. **Satuan permukiman mengikuti poktan penerimanya**, terbaca per baris distribusi. Pengadaan boleh dicatat sebelum dibagikan (barang di gudang UPT); `jumlah_belum_tersalur` terhitung sendiri.
4. Setiap pengadaan dapat dilampiri dokumen pendukung.
5. Saprotan harus dapat direkap per periode, per poktan, dan per desa/SP.
6. **Benih wajib menyebut komoditasnya**, jenis lain tidak. Benih selalu benih sesuatu, dan tanpa kaitan itu sistem tidak dapat menyaring benih mana yang boleh dipakai satu penanaman. Pupuk, pestisida, dan mulsa sengaja tidak ditanya: urea dipakai tanaman apa pun, dan memaksanya memilih satu komoditas berarti mengarang data.
7. **Sisa benih dihitung PER BARIS DISTRIBUSI, tidak disimpan** (Putaran 7 menurunkan grain). Nilainya adalah jatah satu poktan dikurangi seluruh pemakaian penanaman poktan itu (`penanaman.saprotan_distribusi_id`). Menghitungnya di tingkat pengadaan membuat penanaman poktan A menggerus jatah poktan B; menyimpannya sebagai kolom menuntut koreksi tiap penyuntingan penanaman.
8. **Benih yang stoknya habis tidak lagi ditawarkan** pada form penanaman; petugas harus mendata penyaluran baru lebih dulu. Penguncian terjadi ketika stoknya habis, **bukan** ketika pertama kali dipakai — sebab satu bantuan lazim dipakai bertahap untuk beberapa kali tanam.
9. Pemakaian benih tidak boleh melebihi jatah distribusi poktan itu. `penanaman.saprotan_distribusi_id` hanya boleh menunjuk baris distribusi berjenis Benih milik `poktan_id` yang sama (batas #33).
10. **Tahun pengadaan adalah tahun anggaran, bukan tahun barang diterima.** Bantuan APBD/APBN 2025 dapat diserahkan Januari 2026; laporan hasil panennya tetap masuk 2025 (ditetapkan Dinas Pertanian). Diisi petugas dari berita acara, tidak diturunkan dari tanggal apa pun. Menjadi sumbu pengelompokan laporan hasil panen (§8d). Tetap di baris pengadaan, bukan distribusi.
11. **Benih wajib menyebut varietasnya**, jenis lain tidak. Alasannya sama dengan poin 6: benih selalu benih varietas tertentu, dan varietas menentukan perlakuan tanam. Pupuk dan pestisida tidak punya varietas.
12. **Jadwal tanam bersifat rencana**, dicatat dari berita acara dalam bentuk `YYYY-MM`. Ia BUKAN realisasi: kapan bantuan benar-benar ditanam dicatat `penanaman.periode_tanam`. Selisih keduanya justru berguna dilaporkan. Tetap di baris pengadaan.

### 7bc. Infrastruktur, fasilitas, dan dokumen lahan lintas cakupan (Putaran 7, 2026-08-30)
1. **Infrastruktur dan fasilitas SP dapat melayani beberapa SP sekaligus.** `satuan_permukiman_id` TETAP sebagai lokasi/pangkal (wajib); tabel `infrastruktur_sp` / `fasilitas_sp_cakupan` menyimpan seluruh SP yang dilayani dan **wajib memuat SP pangkal**. Satu irigasi 1,2 km, jalan masuk kawasan, kios saprotan "melayani 3 SP", SMP Satu Atap, atau puskesmas pembantu tidak berhenti di batas satu SP; sebelumnya kenyataan itu hanya tertulis di kolom `kapasitas` sebagai teks.
2. **Penilaian kondisi SP membaca cakupan, bukan hanya pangkal.** `PenilaianKondisiSp::nilai()` menyaring `in_array($spId, satuan_permukiman_ids)`. Aturan lama (`=== $spId`) membuat SP tetangga yang sesungguhnya dilayani jatuh ke `Perlu Penanganan` lewat aturan primer nol (air bersih, jalan penghubung), yaitu skor SP yang **salah**, bukan sekadar kurang rapi.
3. ~~**DICABUT 2026-09-02 (Putaran 12).** Satu dokumen lahan (HPL/SHM/SK) dapat mencakup banyak bidang lewat `dokumen_lahan` + `dokumen_lahan_bidang`.~~ Pivot m2m itu menambal AKIBAT dari penempatan yang keliru, bukan sebabnya: HPL adalah alas hak KAWASAN dan SHM meliputi seluruh lahan satu KELUARGA, sehingga tidak satu pun benar-benar milik bidang. Setelah keduanya ditempatkan pada tingkat yang benar (7.6), kebutuhan satu-dokumen-banyak-bidang lenyap dengan sendirinya dan kedua tabel dihapus. Rinciannya pada `notes.md` bagian 6 bertanggal 2026-09-02.
4. **`hasil_panen.poktan_id` dicabut** (diturunkan dari `penanaman.poktan_id`). Kolom sebelahnya `satuan_id` disalin dari komoditas dengan alasan snapshot historis; `poktan_id` tak punya alasan itu — `penanaman.poktan_id` tak pernah sah berubah makna, dan salinan yang menggantung diam-diam berselisih.
5. **`fasilitas_sp` / `inventaris_sp` ber-`jumlah` > 1 membawa `rincian_kondisi`** (histogram kondisi → jumlah unit; Σ = `jumlah`). "Dua dari tiga pos lapuk" kini DATA, bukan kalimat di `keterangan`. Ini BUKAN pendataan per unit (pos ke-2 tak dapat dibedakan dari pos ke-3), melainkan histogram per jenis. Kolom `kondisi` **tetap penilaian umum yang diketik petugas** (lencana daftar, cacah "perlu perbaikan"), tidak diturunkan dari rincian. `PenilaianKondisiSp::kondisiTerbaik()` membaca `rincian_kondisi`: untuk "apakah SP punya X yang berfungsi", satu unit terbaik yang jumlahnya > 0 sudah cukup — aset tanpa rincian dibaca lewat `kondisi` tunggalnya (mundur aman).

### 7d. Aturan Fitur Penanaman
1. Penanaman dicatat **per kelompok tani**, bukan per bidang lahan maupun per petani (ditetapkan 2026-08-22). Lapangan membenarkannya: laporan bantuan benih mencatat satu baris per poktan.
2. Setiap penanaman wajib mencatat kelompok tani, komoditas, realisasi tanam, dan tanggal tanam.
3. **Jumlah anggota dan luas lahan kelompok dihitung, tidak diketik.** Keduanya turunan dari keanggotaan aktif dan data lahan, sehingga angka yang diketik akan menjadi basi begitu satu anggota keluar atau satu bidang dibetulkan — dan kebasian itu tidak pernah terlihat.
4. Luas lahan kelompok adalah akumulasi lahan ketua beserta seluruh anggota **aktif**. Anggota yang sudah keluar tidak dihitung, sebab lahannya tidak lagi digarap kelompok ini.
5. **Realisasi tanam tidak boleh melebihi lahan yang belum ditanami.**
6. **Lahan kembali tersedia setelah panennya tuntas**, berbeda dari benih yang habis selamanya. Tanpa aturan ini, lahan poktan akan tampak habis setelah beberapa musim padahal bidang yang sama memang ditanami berulang kali tiap tahun.
7. Benih yang ditawarkan hanya milik kelompok itu, untuk komoditas itu, dan yang stoknya masih ada.
8. **Setiap penanaman wajib menyebut benih yang dipakai** beserta volumenya (diubah 2026-08-24). **Termasuk bibit swadaya**, yang didaftarkan lebih dulu sebagai penyaluran bersumber `Swadaya`.
8a. **Aturan lama DICABUT.** Sebelumnya penanaman tanpa benih dinyatakan sah, dengan alasan "bibit swadaya tidak melalui modul saprotan". Alasan itu **keliru**: enum sumber perolehan sudah memuat `Swadaya` sejak awal, dan satu baris data contoh sudah memakainya. Yang kurang hanyalah keseragaman pemakaian — bukan batasan lapangan, melainkan cara sistem kebetulan dipakai lalu dianggap sebagai keharusan.
8b. Manfaat pewajibannya bukan kerapian data semata: **benih swadaya jadi ikut punya stok**. Tanpa itu ia seolah tak terbatas, dan poktan dapat mencatat penanaman sebanyak apa pun tanpa ada yang menegur.
8c. Ketika belum ada benih yang dapat dipakai, form **wajib menuntun petugas mendaftarkannya** beserta tautan ke penyaluran saprotan — bukan menampilkan dropdown kosong. Dropdown yang tidak dapat dipilih apa pun adalah kontrol mati (`ui-spec.md` R-26), dan mewajibkan isian tanpa menyediakan jalan mengisinya justru mendorong petugas mengarang entri agar dapat melanjutkan.
9. **Periode tanam dicatat sebagai bulan**, bukan tanggal. Penanaman satu hamparan berlangsung berhari-hari, sehingga menuntut satu tanggal pasti membuat petugas menebak.
10. Setiap penanaman dapat dilampiri dokumen pendukung: berita acara tanam, foto hamparan, atau bukti penyaluran benih. Tidak dibatasi gambar saja, sebab berita acara lazimnya PDF hasil pindaian.
11. **Status panen dihitung, tidak disimpan** (ditetapkan 2026-08-24). Nilainya diturunkan dari sisa luas yang belum dipanen, dengan tiga keadaan: `Belum Dipanen` bila tidak ada satu pun catatan panen, `Dipanen Sebagian` bila masih bersisa, dan `Selesai Dipanen` bila sisanya nol. Alasannya sama dengan poin 3 dan aturan §9.10: kolom tersimpan menjadi salah begitu satu baris panen disunting atau dihapus, dan kesalahan itu tidak pernah memerahkan apa pun.
11a. Keadaan `Belum Dipanen` **tidak boleh disimpulkan dari sisa luas saja**. Penanaman yang belum disentuh dan penanaman yang dipanen nol hektare sama-sama menyisakan seluruh luasnya, sehingga keberadaan catatan panennya wajib diperiksa tersendiri.
11b. **Puso bukan status tersendiri**, melainkan kolom angka. Penanaman yang seluruhnya gagal panen menyisakan nol, sehingga berstatus `Selesai Dipanen` sama seperti yang berhasil penuh; pembedanya kolom puso. Ini mengikuti bentuk laporan lapangan yang menaruh Realisasi Panen dan Puso sebagai dua kolom bersebelahan, bukan dua nilai pada satu kolom status.
11c. **Daftar Penanaman menawarkan ketiga status sebagai penyaring, daftar Hasil Panen hanya dua.** Pilihan `Belum Dipanen` sengaja tidak dirender pada Hasil Panen sebab penanaman yang belum dipanen tidak memiliki satu pun baris panen, sehingga penyaring itu selalu menghasilkan tabel kosong — kontrol mati yang dilarang `ui-spec.md` R-26. Menemukan penanaman yang belum dipanen adalah tugas halaman Penanaman.
11d. Pada daftar Hasil Panen, kolomnya berjudul **"Status Penanaman"**, bukan "Status". Yang ditandai adalah keadaan penanaman induknya, bukan kelengkapan catatan panen itu sendiri, dan judul yang lebih pendek akan terbaca sebagai hal yang kedua.

### 8. Aturan Fitur Komoditas
1. Sistem harus mendukung komoditas unggulan kawasan, terutama komoditas utama yang disebut dalam proposal, yaitu jagung.
2. Komoditas harus dapat dikaitkan dengan poktan, penanaman, dan hasil panen.
3. Sistem harus mendukung penandaan komoditas unggulan.
3a. **Unggulan ditandai petugas, bukan dihitung sistem** (ditegaskan 2026-08-18). Dasarnya proposal atau kebijakan dinas, sebagaimana poin 1 yang menyebut jagung "disebut dalam proposal" — penetapannya mendahului data panen mana pun. Menghitungnya dari volume terbesar akan menutup kasus yang justru paling perlu ditandai: komoditas prioritas program yang volumenya masih kecil karena baru dirintis. Perhitungan otomatis juga membuat jumlah penanda aksen gold tidak terkendali, bertentangan dengan `ui-spec.md` 2.4 yang membatasi pemakaiannya.
3b. Form komoditas **menampilkan volume tercatat sebagai bahan pertimbangan**, beserta peringatan bila yang ditandai bukan bervolume terbesar. Peringatan itu **tidak menghalangi penyimpanan**: unggulan bervolume kecil adalah keadaan yang sah, yang tidak boleh terjadi hanyalah petugas menandainya tanpa menyadari keadaan itu.
3c. **"Komoditas utama" pada dashboard berbeda dari "komoditas unggulan"** dan keduanya sengaja tidak disatukan. Utama dihitung dari volume terbesar dan berubah mengikuti musim; unggulan ditetapkan program dan tidak berubah hanya karena panen satu musim naik atau turun. Pemilihan komoditas utama wajib memakai nilai terbesar, bukan urutan larik.
4. Setiap komoditas wajib memiliki **satuan panen baku** yang ditetapkan pada data master, misalnya jagung dalam ton dan cabai dalam kilogram.
5. Komoditas dikelompokkan menurut tipenya: pangan, palawija, dan hortikultura.
6. Komoditas harus bisa dianalisis per desa/SP atau per periode.

### 8a. Aturan Data Master Satuan
1. Sistem menyediakan data master satuan untuk produksi panen dan penyaluran saprotan.
2. Setiap satuan wajib menyimpan nama, simbol, dan **faktor konversi ke ton** sebagai satuan agregasi baku.
3. Contoh faktor konversi: ton = 1; kuintal = 0,1; kilogram = 0,001.
4. Produksi disimpan apa adanya sesuai satuan baku komoditasnya, tanpa dikonversi saat penyimpanan.
5. Konversi ke ton hanya dilakukan pada saat rekap, agregasi, dan penyajian dashboard, agar data asli lapangan tetap terjaga.
6. Satuan lokal seperti karung dan ikat tidak dipakai sebagai satuan baku. **Kolom keterangan tambahan tersendiri dicabut 2026-08-22**; bila padanan satuan setempat memang perlu dicatat, tempatnya pada kolom catatan biasa.
7. Penambahan satuan baru cukup menambah baris data, tanpa mengubah struktur tabel.

### 9. Aturan Fitur Hasil Panen
1. Hasil panen harus dicatat per periode.
2. Minimal data panen yang dicatat:
   - jenis komoditas,
   - produksi,
   - satuan panen,
   - produktivitas per hektare,
   - harga jual,
   - periode panen,
   - lokasi produksi.
3. Produksi dicatat memakai **satuan baku milik komoditas** yang bersangkutan, mengacu pada data master satuan.
4. Satuan lokal seperti karung atau ikat **tidak** dipakai sebagai satuan panen; produksi selalu dicatat dalam satuan baku komoditasnya agar rekap tetap konsisten.
5. Rekap dan agregasi lintas komoditas wajib dikonversi terlebih dahulu ke satuan **ton** memakai faktor konversi pada data master.
6. Nilai produksi disimpan dengan presisi desimal yang cukup agar panen berskala kecil tidak hilang saat pembulatan.
7. Riwayat panen harus dapat dipantau untuk melihat potensi produksi kawasan.
8. Hasil panen harus dapat direkap per desa/SP, per poktan, per komoditas, dan per periode. Rekap per transmigran dicabut 2026-08-22: panen dicatat per kelompok, sehingga membaginya per orang berarti mengarang angka yang tidak pernah didata.
8a. **Rekap panen dihitung dari catatan PENANAMAN, bukan dari catatan panen** (ditetapkan 2026-08-24). Pada basis panen, kelompok yang sudah menanam tetapi belum panen sama sekali hilang dari rekap, sehingga dinas membaca "tidak ada masalah" justru pada keadaan yang paling perlu ditengok. Laporan lapangan memakai basis yang sama, dan judulnya menyatakannya: "Panen Sisa Tanam" hanya dapat disusun bila barisnya poktan yang menanam.
8b. **Rekap panen wajib terikat satu periode, tidak pernah kumulatif sejak awal waktu.** Dua sebab: luas **tidak boleh** dijumlahkan lintas tahun sebab bidang yang sama ditanami berulang kali — 2 ha yang ditanami tiga tahun akan terbaca "6 ha"; dan total kumulatif hanya dapat naik, sehingga musim yang hancur pun tampak sebagai kabar baik. Periodenya wajib **tertulis pada judul tabel dan baris total**, bukan hanya tersembunyi di penyaring.
8c. **Penyaring periode rekap memakai TAHUN PANEN** (diubah 2026-08-24 atas keterangan pemilik proyek; sebelumnya tahun tanam). Ini rekap **panen**, sehingga yang menggolongkan adalah peristiwa panennya. Bentuk lama membuang panen April 2026 dari rekap 2026 hanya karena penanamannya bermula November 2025, padahal timbangannya nyata terjadi tahun itu.
8c-1. **Satu penanaman hanya muncul pada satu tahun**, sebab luasnya akan terhitung dua kali bila muncul di dua tahun. Penanaman yang **sudah dipanen** digolongkan ke tahun panennya dan tidak pernah berpindah lagi; yang **belum dipanen** digolongkan ke tahun berjalan, sebab di situlah panennya masih mungkin terjadi.
8c-2. Akibat aturan di atas, baris yang belum dipanen **berpindah mengikuti waktu**. Penanaman Oktober 2026 yang belum dipanen tampil pada rekap 2026 selama tahun itu berjalan; begitu sistem memasuki 2027 dan panennya tetap belum tercatat, ia pindah ke 2027. Perpindahan itu disengaja: peluang panen pada tahun sebelumnya memang sudah tertutup.
8d. **Produktivitas agregat wajib tertimbang**, yaitu total produksi dibagi total luas dipanen, keduanya setelah dikonversi ke ton. Merata-ratakan kolom produktivitas mencampur ton per hektare dengan kilogram per hektare: jagung 3,4 ton/ha dan cabai 1.282 kg/ha dirata-rata menghasilkan 642 ton/ha, angka yang tidak ada di lapangan.
8e. Angka turunan pada rekap **dihitung dari nilai yang sudah dibulatkan seperti yang tampil di layar**, bukan dari nilai mentah sebelumnya. Pembaca mengalikan atau membagi dua kolom yang ia lihat untuk memeriksa ulang, dan angka yang diturunkan dari nilai lain tidak akan pernah cocok.
8f. Rekap **tidak menghitung cacah catatan** sebagai kolom. Cacah baris entri bukan besaran lapangan: kelompok yang panen bertahap tiga kali tampak "lebih banyak" daripada yang panen sekali, meski luasnya lebih kecil. Cacah poktan dihitung sebagai himpunan, sebab satu poktan dapat memiliki banyak penanaman.
8g. ~~Indikator produksi pada dashboard memakai agregat kawasan, bukan penjumlahan tabel transaksi (ditegaskan 2026-08-24).~~ **DIBALIK 2026-09-04, keputusan pemilik proyek (Task 9.1).** `prd.md` §7.8 justru meminta dashboard "grafik ... tiap tahun" -- artinya dashboard SEHARUSNYA tumbuh mengikuti data yang benar-benar tercatat lewat CRUD sistem, bukan angka kawasan tetap yang tak pernah berubah walau data bertambah. Basis data saat ini kecil (data contoh, 8 KK) karena pendataan sungguhan belum berjalan -- itu keadaan jujur, bukan alasan memalsukan skala kawasan. Dashboard, `/kependudukan/rekap`, dan laporan `indikator-kawasan`/bagian penduduk `monografi-sp` sekarang dihitung dari Eloquent (`App\Support\RekapDashboard`), dan tumbuh benar seiring petugas menambah data.
    - Konsekuensinya WAJIB disadari: dua kartu di sisi dashboard TIDAK bisa lagi saling membantah bukan lewat identitas aritmetika terpisah (poin 9/11 tetap berlaku untuk agregat produksi yang dihitung sendiri di sini), melainkan karena keduanya berasal dari SATU kueri yang sama.
    - **Dua celah nyata yang TIDAK terselesaikan sekadar dengan "hitung dari Eloquent"**, keduanya disetujui pemilik proyek: (1) "KK Keluar per tahun" butuh `transmigran.tahun_keluar` (kolom baru, diisi form saat status disunting ke Pindah Penduduk/Tidak Aktif; riwayat SEBELUM kolom itu ada tetap tak terlacak); (2) "Pendapatan Keluarga per tahun" TIDAK ada padanannya sama sekali (`pendapatan_per_bulan` kolom keadaan-sekarang tanpa riwayat, ditimpa tiap disunting) -- diganti kartu "keadaan sekarang" (`RekapDashboard::pendapatanSaatIni()`), bukan dipaksa jadi tren 11 tahun yang datanya tidak pernah ada.
    - Grafik tren historis (`RekapDashboard::deret()`) yang butuh angka masa lalu (jumlah KK/jiwa/petani per tahun) adalah TAKSIRAN kumulatif dari `tahun_kedatangan`/`tahun_keluar` yang ada sekarang, BUKAN potret riwayat sungguhan -- `transmigran` tabel keadaan-sekarang, bukan tabel snapshot bertanggal. Rentang tahunnya dimulai dari data nyata yang ada (`tahun_kedatangan` paling awal), bukan tahun karangan.
    - Volume panen dan harga jual PER TAHUN justru genuinely historis (bukan taksiran): `hasil_panen.periode_panen`/`penanaman.periode_tanam` punya baris bertanggal per transaksi, dipakai ulang lewat `App\Support\RekapPanen` (Task 7) yang sudah menegakkan §8b/8c di atas.
8h. **Kartu bertahun wajib menyebut tahunnya**, bukan berbunyi "Tahun Ini". Angka yang tetap akan berbohong begitu tahun berganti sementara datanya belum masuk. Tahun yang disebut adalah tahun terakhir yang benar-benar **terdata**, sebab hanya itu yang dapat dijamin benar.
8i. **Nama komoditas pada agregat dashboard wajib sama persis dengan data master.** Ketidakcocokan penamaan memaksa tiap pemakainya menormalkan huruf sendiri-sendiri, dan normalisasi yang keliru gagal secara senyap: kunci yang tidak ketemu tampil sebagai tanda hubung, dan tanda itu terbaca "belum ada panen" padahal artinya "kodenya tidak menemukan datanya".
8j. **Istilah antarmuka untuk kolom luas panen adalah "Realisasi Panen"**, bukan "Hasil Panen" (diseragamkan 2026-08-24). Sejajar dengan Realisasi Tanam dan sama persis dengan kolom laporan lapangan. "Hasil Panen" tetap dipakai sebagai **nama modul**, judul halaman, dan label tab; yang diganti hanya label kolom beserta isiannya. Satu besaran wajib satu nama di seluruh halaman, sebab dua nama membuat petugas mengira keduanya angka berbeda.
8k. Kolom rekap untuk penanaman yang belum dipanen berjudul **"Menunggu Panen"**, bukan "Belum Dipanen". Istilah kedua dicabut dari form bersama panen bertahap, dan memakainya kembali di rekap dengan arti yang berbeda — bukan sisa panen setengah jalan, melainkan penanaman yang belum disentuh — justru membingungkan.
8l. **Rekap menyediakan penyaring silang** (ditambahkan 2026-08-24): tab menentukan baris **apa**, penyaring menentukan baris **mana**. Keduanya sumbu terpisah, dan justru gabungannya yang berguna — "berapa produksi jagung di SP Weain" tidak dapat dijawab tanpa keduanya. Penyaring yang dirender berbeda tiap tab; menyaring SP pada tab Per SP hanya menyisakan satu baris yang sudah terlihat sejak awal, dan kontrol yang tidak berguna sama saja dengan kontrol mati.
8m. **Pilihan penyaring dihitung dari data pada periode terpilih, bukan dari data master.** Master memuat enam satuan permukiman dan lima komoditas, sedangkan satu tahun tertentu mungkin hanya memiliki satu dari masing-masing. Menawarkan sisanya berarti menyuguhkan pilihan yang **dijamin** menghasilkan tabel kosong — kontrol mati yang dilarang `ui-spec.md` R-26; bukan tombol yang tidak berfungsi, melainkan pilihan yang sia-sia sejak sebelum diklik.
8n. **Nilai penyaring yang tidak tersedia pada periode terpilih wajib dilepas beserta pemberitahuannya.** Keadaannya nyata: petugas menyaring satu komoditas lalu berpindah tahun, dan komoditas itu tidak ditanam tahun tersebut. Tanpa pelepasan, halaman tampak rusak; tanpa pemberitahuan, petugas mengira penyaringnya yang tidak bekerja.
8o. **Baris total ikut menyempit mengikuti penyaring, dan itu wajib dinyatakan** pada judul tabel maupun baris totalnya. Tanpa keterangan itu, angkanya dapat disalin ke laporan sebagai total kawasan padahal hanya mencakup satu komoditas. Alasannya sama dengan kewajiban menulis periode pada poin 8b.
8p. **Angka milik poktan dijumlahkan per himpunan poktan, bukan per baris penanaman.** Berlaku bagi jumlah anggota maupun luas lahan. Satu poktan lazim memiliki beberapa penanaman pada tahun yang sama, sehingga menjumlahkannya per baris menghitung kelompok yang sama berkali-kali — kelompok beranggota 3 orang dengan tiga penanaman akan terbaca 9 orang, dan angka itu **tampak wajar sekilas** sehingga tidak ada yang menyadarinya.
8q. **Jumlah Anggota hanya ditampilkan pada rekap per kelompok tani.** Pada rekap per SP maupun per komoditas ia menjumlahkan anggota beberapa poktan sekaligus — angka yang benar secara aritmetika tetapi tidak menjawab pertanyaan apa pun, sebab yang dinilai di sana wilayah dan komoditas, bukan orangnya.
8r. **Produktivitas tidak ditampilkan pada daftar hasil panen**, hanya pada rekap. Pada daftar ia dapat dihitung sendiri dari produksi dibagi realisasi panen yang keduanya tampil di layar, sehingga mencabutnya tidak menghilangkan data. Pada rekap ia justru wajib ada, sebab agregat tertimbang tidak dapat dihitung ulang pembaca dari dua kolom mana pun.
9. **Satu kegiatan penanaman hanya boleh satu catatan panen** (ditetapkan 2026-08-24). Luas yang ditanam wajib tertutup habis pada satu pencatatan panen: **realisasi panen + puso = realisasi tanam**, tepat. Satu hamparan penanaman 2 ha tidak dapat dicatat dipanen 1,5 ha lalu menyusul 0,5 ha dari penanaman yang sama.
9a. **Panen bertahap dari satu hamparan DICABUT** pada tanggal yang sama, beserta seluruh konsep "belum dipanen" yang menggantung pada satu baris penanaman. Satu hamparan yang ditanam serentak akan matang serentak pula, sehingga bagian yang tidak menghasilkan adalah **Puso (gagal panen)**, bukan sisa hidup yang menunggu panen bulan berikutnya. **Penanaman bertahap dari satu alokasi distribusi benih TETAP SAH dan DIDUKUNG**, yaitu dengan mencatatnya sebagai **baris kegiatan penanaman baru** yang menunjuk jatah distribusi benih yang sama.
9b. **Gagal total adalah keadaan yang sah**: realisasi panen 0 ha dengan puso menutup seluruh luas. Pada keadaan itu **produktivitas tidak diwajibkan** dan produksi bernilai nol, sebab tidak ada yang ditimbang; memaksa angka berarti menuntut petugas mengarang hasil yang tidak pernah ada.
9c. **Realisasi panen dan puso saling mengisi pada form.** Jumlah keduanya sudah tertentu, sehingga mengetik salah satunya menentukan yang lain. Tanpa itu petugas harus menghitung sendiri, dan angka yang tidak menutup luas dapat tersimpan tanpa ada yang menegur.
9d. **Penanaman yang sudah dipanen tidak lagi ditawarkan** pada form panen. Menawarkannya berarti mengundang baris kedua yang tidak sah, dan luasnya akan terhitung dua kali pada rekap.
10. **Status panen bernilai dua**, yaitu `Belum Dipanen` dan `Selesai Dipanen`, diturunkan dari ada tidaknya catatan panen. Nilai `Dipanen Sebagian` dicabut 2026-08-24 sebab keadaannya tidak lagi mungkin ada. Penanaman yang gagal total tetap `Selesai Dipanen`; pembedanya kolom puso, bukan status.
11. **Produksi = hasil panen dikali produktivitas.** Nilainya tetap disimpan meski dapat dihitung, sebab ia angka yang dilaporkan ke dinas dan pembulatan perkalian dapat berbeda tipis dari timbangan sebenarnya.
12. **Produktivitas memakai satuan baku komoditasnya**, bukan selalu ton per hektare. Jagung ton/ha, cabai kg/ha. Memaksanya ton membuat harga jual cabai per ton menjadi angka yang tidak pernah dipakai siapa pun di lapangan.
13. **Kualitas panen dicabut 2026-08-22** atas keputusan pemilik proyek, digantikan produktivitas. Label mutu menuntut penilaian yang tidak dapat diverifikasi, sedangkan produktivitas per hektare dihitung dari timbangan.
14. **Periode panen dicatat sebagai bulan**, bukan tanggal. Panen satu hamparan berlangsung berhari-hari, sehingga menuntut satu tanggal pasti membuat petugas menebak.
15. Setiap catatan panen dapat dilampiri dokumen pendukung: berita acara panen, foto hamparan, atau bukti timbangan.
16. **Laporan hasil panen untuk Dinas Pertanian dikelompokkan menurut `saprotan.tahun_pengadaan`**, bukan tahun panen (ditetapkan dari pertemuan dengan Dinas Pertanian). Bantuan benih beranggaran 2025 yang ditanam dan dipanen 2026 tetap dilaporkan sebagai capaian 2025. Penelusurannya lewat rantai yang sudah ada: `hasil_panen.penanaman_id` -> `penanaman.saprotan_distribusi_id` -> `saprotan_distribusi.saprotan_id` -> `saprotan.tahun_pengadaan`.
16a. **Sumbu ini KHUSUS laporan, tidak menggantikan rekap.** Rekap panen dan dashboard tetap memakai tahun panen (§9 poin 8c) sebab menjawab pertanyaan berbeda: rekap menjawab "apa yang terjadi tahun ini", laporan menjawab "apa hasil dari bantuan anggaran 2025". Masing-masing wajib menyebut basisnya pada judul agar angka yang sama tidak tertukar.
16b. **Pupuk, pestisida, dan mulsa tidak tertaut ke penanaman**, sehingga laporannya berdiri sebagai bagian terpisah: penyalurannya per poktan per tahun pengadaan, tanpa rantai ke hasil panen. Menyatukannya paksa dengan bagian benih berarti mengarang kaitan yang tidak didata.
16c. **Agregasi Subtotal SP dan Total Kawasan pada Laporan Hasil Panen:** Kolom profil poktan (`luas_lahan`, `jumlah_anggota`, dan `belum_ditanam`) **wajib dihitung dari himpunan poktan unik**, bukan penjumlahan baris mentah. Bila 1 poktan memiliki lebih dari 1 kegiatan penanaman di SP yang sama, luas lahan fisiknya hanya dihitung satu kali agar luas wilayah dan populasi tidak terdistorsi (2026-09-01). `belum_ditanam` pada subtotal/total dihitung: `total luas lahan poktan unik - total realisasi tanam`. Kolom aktivitas (`volume_benih`, `realisasi_tanam`, `realisasi_panen`, `puso`, `belum_dipanen`, `produksi_ton`) tetap dijumlahkan per baris; `produktivitas` adalah rasio tertimbang `total produksi / total realisasi panen`.
16d. **Pemisahan Sumber Dana pada Laporan Hasil Panen (2026-09-01):** Laporan hasil panen mendukung penyaringan menurut `sumber_dana` (`APBN`, `APBD Provinsi`, `APBD Kabupaten`, `Swadaya`, atau `Semua`). Ini memungkinkan instansi mencetak laporan pertanggungjawaban khusus bantuan APBN/APBD (tanpa bercampur swadaya) maupun mencetak laporan menyeluruh produksi kawasan.

### 10. Aturan Fitur Infrastruktur SP
1. Fitur infrastruktur berisi **pendataan aset**, bukan pelaporan masalah. Pelaporan kerusakan ditangani fitur Pengaduan (§10b).
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
7a. **Barisnya dihasilkan dari jenis infrastruktur dan fasilitas** pada data master, bukan ditulis satu per satu. Daftar tulis tangan membuat jenis yang ditambahkan Admin tidak pernah ikut dinilai: dropdownnya hidup dan petugas dapat mendata asetnya, tetapi skor tidak berubah sama sekali. Keadaan itu pernah terjadi dan baru ketahuan setelah empat jenis fasilitas terlewat.
7b. **Jenis baru belum dinilai sampai dinas mencentangnya.** Menambah jenis adalah pendataan, memasukkannya ke penilaian adalah kebijakan; menyatukan keduanya membuat skor seluruh SP turun hanya karena satu pilihan dropdown bertambah.
7c. **Tingkat tiga parameter primer terkunci.** Memindahkan air bersih, jalan penghubung, atau listrik ke tingkat lain bukan menurunkan bobotnya, melainkan mencabut aturan primer nol pada poin 11. Bobotnya tetap dapat disesuaikan.
7d. Jenis penampung seperti `Lainnya` **tidak dinilai**. Ia bukan satu jenis barang, sehingga menilai ketersediaannya berarti memberi nilai penuh kepada SP yang memiliki satu benda tak jelas.

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
13a. **Ketiganya kini dapat disunting dinas**, tidak lagi terkunci di dalam kode: nilai kondisi aset lewat data master daftar pilihan, sedangkan bobot dan ambang lewat `/master/penilaian-kondisi`. Sebelumnya hanya yang pertama yang berupa data, sehingga separuh perhitungan dapat diatur dan separuhnya tidak.
13b. **Nama status juga dapat disesuaikan**, sebab tiap dinas punya istilah sendiri. Yang tersimpan tetap nilai enum; yang berubah hanya teks tampilnya. Jumlahnya tetap tiga, sebab perhitungan hanya mengenal tiga keluaran.
13c. Larangan istilah merendahkan pada A10c.1 berlaku atas **nilai bawaan**. Wording hasil suntingan dinas tidak diperiksa sistem, sehingga tanggung jawabnya berpindah ke dinas yang menyuntingnya.

#### 10c.6 Riwayat penilaian

14. Setiap penilaian **disimpan sebagai baris riwayat**, memuat skor, label, tanggal penilaian, rincian nilai tiap parameter, dan **salinan bobot yang berlaku saat itu**.
15. Alasannya: bobot dapat diubah Admin. Tanpa salinan, laporan yang sudah dicetak dan dikirim ke dinas akan berbeda dari yang ditampilkan sistem setelah bobot diubah. Prinsip ini sama dengan penyalinan `satuan_id` pada hasil panen (`data-dictionary.md` §9.3).
16. Riwayat memungkinkan perkembangan kondisi SP terbaca dari waktu ke waktu, misalnya kenaikan dari Perlu Penanganan menjadi Berkembang setelah jalan penghubung diperbaiki. Perkembangan ini justru lebih berguna bagi perencanaan daripada angka hari ini saja.
17. Penilaian **tidak dihitung ulang secara diam-diam** saat halaman dibuka. Penilaian baru dibuat sebagai baris baru, sehingga yang lama tetap utuh.

### 10a. Aturan Fitur Penghuni Kawasan
1. Sistem harus mencatat data penghuni/transmigran kawasan beserta status tinggal, pindah, dan aktif/tidak aktif.
2. Data penghuni wajib tertaut ke data rumah, mencakup kondisi rumah, foto rumah, koordinat lokasi, riwayat kepemilikan, dan catatan tambahan.
3. Data penghuni harus tertaut ke desa/SP dan dapat difilter per lokus.
4. Sistem harus menyediakan rekap kependudukan kawasan, termasuk KK masuk dan keluar per tahun.
4a. Rekap kependudukan dikelompokkan menurut **enam dasar**: tahun, satuan permukiman, status tinggal, pekerjaan, daerah asal, dan pendidikan terakhir. Daerah asal ditambahkan 2026-08-25 sebab ia khas program transmigrasi — menjawab "dari mana warga berasal", pertanyaan yang tidak dijawab pengelompokan lain.
4b. **Keenam pengelompokan wajib menghasilkan total jumlah KK yang sama.** Seluruhnya membagi keluarga yang sama menurut sudut pandang berbeda; total yang berlainan berarti salah satu pembagiannya bocor, dan pembaca yang berpindah tab akan mengira salah satunya rusak.
4c. **Pendidikan diurutkan menurut jenjang, bukan menurut jumlah.** Pendidikan bertingkat, sehingga mengurutkannya menurut jumlah membuat `SD` mendahului `Tidak Sekolah` dan pembaca kehilangan bentuk piramidanya. Jenjang tanpa penghuni **tetap ditampilkan bernilai nol**: baris yang hilang membuat pembaca tidak dapat membedakan "tidak ada" dari "belum didata".
4d. **Tiap dasar pengelompokan wajib punya tautan tetap**, bukan hanya kueri `?kelompok=`. Kueri tidak dilayani berkas statis, sehingga tanpa tautan tetap hanya tab bawaan yang terbuka di situs terbit. Berlaku bagi seluruh halaman rekap; aturan ini ditulis setelah rekap kependudukan ditemukan terlewat pada 2026-08-25, padahal rekap panen sudah diperbaiki jauh sebelumnya.
4e. Beberapa isian form transmigran **sengaja belum direkap**: jenis kelamin, usia, dan jumlah anggota keluarga. Bukan kelalaian melainkan keputusan; pendapatan sudah terwakili lewat kolom pendapatan rata-rata pada rekap per tahun.
4f. **Tab 'Per Tahun' pada Rekap Kependudukan tidak menampilkan kartu filter tahun** (2026-08-31). Karena tab ini menyajikan tabel agregat multi-tahun longitudinal (2016–2026), filter tahun tunggal dihilangkan pada tab ini guna menghindari kontrol mati/disabled yang membingungkan pengguna, sementara filter tahun tetap aktif di lima tab demografi lainnya.
5. Data penghuni bersifat sensitif dan wajib dibatasi oleh RBAC serta ditampilkan agregat bagi pihak terbatas.

### 10b. Aturan Fitur Pengaduan

#### Kanal publik tanpa login
1. Pengaduan diajukan lewat **halaman publik tanpa login**, karena warga transmigran tidak memiliki akun sistem. Warga cukup mengisi nama, kontak, lokasi SP, kategori, dan uraian masalah.
1a. Petugas juga dapat mencatatkan pengaduan atas nama warga yang melapor lisan. Sumber laporan dibedakan lewat kolom `sumber_laporan` bernilai `Publik` atau `Petugas`.
1b. Setelah mengirim, warga menerima **nomor pengaduan** yang dipakai untuk melacak perkembangan laporannya pada halaman lacak publik.
1c. Halaman lacak hanya menampilkan status, tanggal, dan catatan penanganan. Data pribadi pelapor tidak pernah ditampilkan.
1c-1. Warga **boleh mencantumkan alamat surel**, tetapi tidak diwajibkan. Bila diisi, nomor pengaduan dikirim juga ke sana sebagai salinan. Nomor tetap ditampilkan besar di layar setelah pengiriman berhasil, sehingga surel tidak pernah menjadi satu-satunya cara menerimanya. Kolom ini dibuat opsional karena jaringan di lokus tidak selalu memadai dan sebagian warga tidak memiliki surel; mewajibkannya akan menutup kanal yang justru paling perlu terbuka.

#### Pengamanan kanal publik
1d. Pengiriman dibatasi **3 pengaduan per jam untuk setiap alamat IP**.
1e. Seluruh pengaduan publik masuk berstatus `Menunggu Diterima`, sehingga petugas menyaring lebih dulu sebelum diproses.
1f. Alamat IP pelapor disimpan untuk menelusuri penyalahgunaan.
1g. Sistem **tidak memakai CAPTCHA**, karena membebani pengguna berjaringan lemah di lokus. Pembatasan laju dinilai memadai untuk skala kawasan ini.

#### Pencatatan dan penanganan
2. Setiap pengaduan wajib mencatat tanggal, nama dan kontak pelapor, lokasi/SP, kategori, dan deskripsi.
3. Kategori pengaduan memakai pilihan baku: lahan usaha, lahan pekarangan, rumah, infrastruktur, inventaris SP, fasilitas SP, kelompok tani, alsintan, saprotan, produksi panen, bencana, dan lainnya. Tiga perubahan pada 2026-08-19: nilai "peralatan dan perlengkapan" **dipecah** menjadi inventaris SP dan fasilitas SP sebab satu kategori menaungi dua daftar berbeda; **saprotan ditambahkan** agar keluhan bibit, pupuk, serta obat tidak menumpang pada produksi panen; dan **kelompok tani ditambahkan** sebab poktan adalah modul penuh tetapi keluhan atasnya terpaksa masuk kategori "lainnya" yang justru berbidang kosong.

3a. **Daftar kategori memetakan modul yang dapat diadukan warga**, dan pemetaannya wajib lengkap dua arah: tiap modul yang mungkin dikeluhkan punya kategorinya, dan tiap kategori punya modul padanannya. Modul internal sistem, data referensi, serta data pribadi transmigran sengaja tidak berkategori; rinciannya pada `data-dictionary.md` §11.21. Menambah modul baru yang dapat dikeluhkan warga menuntut pemeriksaan ulang daftar ini.
4. Alur status penanganan wajib berurutan: **Menunggu Diterima → Diterima → Diproses → Selesai**.
5. Setiap perubahan status wajib menyimpan riwayat berisi petugas penangan, tanggal penanganan, catatan, dan dokumen tindak lanjut.
5a. **Isian penanganan sama di mana pun dibuka**, baik lewat halaman rincian maupun lewat kolom aksi pada halaman daftar. Meminta hal berbeda pada dua tempat menghasilkan riwayat yang timpang: sebagian jejak bertanggal dan berdokumen, sebagian tidak.
5b. **Dokumen tindak lanjut yang sudah diunggah wajib dapat dibuka kembali** pada riwayat penanganan. Menyediakan isian unggah tanpa menampilkan hasilnya membuat berkas tersimpan tanpa dapat dijangkau siapa pun, termasuk oleh yang mengunggahnya.
6. Pengaduan dapat dilampiri dokumen/foto pendukung dan diberi penanda prioritas.
6a. **Prioritas ditentukan sepenuhnya oleh petugas** yang meninjau laporan, tidak diisi warga dan tidak diturunkan otomatis dari kategori. Warga tidak mengetahui skala prioritas dinas, dan meminta warga menilainya sendiri membuat hampir seluruh laporan ditandai mendesak sehingga penandanya kehilangan makna.

> **Perubahan 2026-08-14.** Penurunan otomatis dari kategori sempat ditetapkan lalu **dibatalkan**. Kategori hanya menyatakan pokok masalah, sedangkan kegentingan bergantung pada keadaan lapangan yang tidak terbaca dari kategori: dua laporan berkategori sama dapat berbeda jauh kemendesakannya. Nilai turunan yang tampak berwibawa justru berisiko diterima begitu saja tanpa ditinjau ulang.

6b. Prioritas dapat direvisi kapan pun selama laporan berjalan. Setiap revisi tercatat pada audit log beserta pelakunya.
6c. **Titik koordinat diminta pada kanal publik, tetapi opsional.** Pengaduan tetap dapat dikirim tanpa mengisinya, sebab warga melapor lewat ponsel dengan jaringan yang tidak selalu memadai dan mewajibkannya akan menutup kanal yang justru paling perlu terbuka. Bila diisi, petugas terbantu menemukan titik masalah tanpa bertanya ulang. Petugas tetap melengkapinya saat verifikasi lapangan bila kosong.
6d. Setiap isian koordinat, baik pada kanal publik maupun form petugas, **wajib menyediakan pemilihan lewat peta** di samping pengambilan lokasi otomatis. GPS ponsel di lokus kerap meleset puluhan meter, sedangkan pelapor paling mengetahui letak sebenarnya. Peta memakai ubin OpenStreetMap tanpa kunci API, dimuat hanya ketika dibuka. Bila peta gagal dimuat karena jaringan lemah, isian manual dan tombol lokasi otomatis tetap berfungsi.
7. Pengaduan diteruskan ke dinas sesuai bidangnya: bidang pertanian ke Dinas Pertanian, bidang ketransmigrasian ke Dinas Transmigrasi. **Satu laporan ditangani satu dinas**, sehingga alur statusnya tunggal dan tidak dipecah per bidang.

    7a. **Bidang diturunkan dari kategori sebagai nilai awal.** Petanya berupa DATA pada `daftar_pilihan.bidang_id`, bukan `match` di dalam kode, sebab kategori kini dapat ditambah Admin lewat data master; `match` tanpa `default` akan melempar `UnhandledMatchError` begitu ada yang memilih kategori baru. Kategori yang menunjuk urusan tertentu langsung mengisi bidangnya:

| Kategori | Bidang bawaan |
|---|---|
| Rumah, lahan pekarangan, inventaris SP, fasilitas SP | Ketransmigrasian |
    | Kelompok tani, alsintan, saprotan, produksi panen | Pertanian |
| Lahan usaha, infrastruktur, bencana, lainnya | **kosong**, wajib ditetapkan petugas |

Empat kategori terakhir sengaja dibiarkan kosong sebab pokok masalahnya dapat jatuh ke dua dinas sekaligus: sengketa lahan usaha bisa menyangkut pembagian lahan maupun produktivitasnya, sedangkan bencana dan "lainnya" memang tidak menunjuk urusan tertentu. Menebak bidangnya justru menyesatkan, sebab laporan akan masuk ke daftar dinas yang keliru lalu tertahan di sana.

7b. **Bidang wajib terisi sebelum status maju ke Diproses.** Sebelum itu laporan boleh berbidang kosong, sebab laporan yang baru masuk dari kanal publik memang belum ditinjau siapa pun.

7c. **Nilai turunan selalu dapat ditimpa petugas.** Kategori hanya menyatakan pokok masalah, sedangkan penentuan dinas bergantung isi laporan yang tidak selalu terbaca dari kategori. Isian bidang karena itu tetap berupa pilihan, bukan tampilan baca-saja, dan pilihan manual tidak boleh tertimpa ketika kategori disunting kemudian.

7d. **Penetapan bidang menjadi tugas Admin dan Dinas Transmigrasi.** Keduanya bercakupan `Semua` sehingga melihat seluruh laporan termasuk yang belum berbidang, sedangkan Dinas Pertanian bercakupan `Per Bidang` dan hanya melihat laporan bidangnya. Laporan bidang pertanian baru muncul pada daftar Dinas Pertanian setelah bidangnya ditetapkan.

7e. Halaman daftar pengaduan wajib menyediakan **filter bidang**, termasuk pilihan **Belum ditentukan** beserta jumlahnya. Tanpa itu antrean penyaringan awal menumpuk tanpa terlihat, dan laporan bidang pertanian tertahan pada dinas yang tidak pernah tahu.
8. Rekap pengaduan per kategori, per status, dan per desa/SP wajib tersedia sebagai sumber indikator isu prioritas pada dashboard.

### 11. Aturan Dashboard Monitoring
1. Dashboard harus menampilkan indikator utama kawasan secara ringkas.
2. Minimal indikator dashboard:
   - jumlah transmigran, disajikan sebagai grafik per tahun,
   - jumlah KK, disajikan sebagai grafik per tahun,
   - jumlah petani, disajikan sebagai grafik per tahun,
   - jumlah pendapatan keluarga saat ini, disajikan sebagai kartu KPI snapshot per KK aktif (bukan deret waktu tahunan fiktif, keputusan pemilik proyek 2026-09-04 / 2026-09-05),
   - visualisasi KK masuk dan keluar per tahun,
   - jumlah rumah yang terhuni,
   - visualisasi pekerjaan kepala keluarga dalam bentuk histogram,
   - luas lahan,
   - komoditas utama (terbanyak),
   - total volume panen per tahun, dinyatakan dalam ton hasil konversi lintas komoditas,
   - harga rata-rata,
   - status infrastruktur,
   - isu prioritas per desa/SP yang bersumber dari fitur Pengaduan (dibatasi maksimal 5 laporan pada tampilan awal dashboard dengan tautan ke halaman pengaduan lengkap),
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
5. **Setiap halaman laporan punya bilah filternya sendiri** (ditetapkan 2026-08-29, Putaran 3 D3, mengganti rencana pewarisan filter). Isi filternya: Satuan Permukiman, periode, dan dimensi khas laporan itu (poktan, komoditas, jenis, status, dst). Filter dikerjakan **di sisi peramban** dengan Alpine — bukan query string di rute — sebab GitHub Pages tidak melayani query string (`notes.md` 1b.5) dan filter yang tidak bereaksi di situs terbit adalah kontrol mati (R-26). Blade tetap merender seluruh baris; Alpine menyembunyikan baris dan menghitung ulang subtotal yang tampak.
   - **Alasan pembalikan:** rencana lama (poin 9–10 versi 2026-08-28) mewariskan filter dari halaman daftar pasangan lewat pintasan, dengan pemilih periode terpisah untuk laporan lintas-modul. `prd.md` §7.9 menuntut "menyediakan filter data" untuk Laporan, dan cara termurah memenuhinya adalah filter di tempat, bukan mekanisme pewarisan dua arah. Begitu tiap laporan punya filternya sendiri, pintasan pembawa filter dan pemilih periode lintas-modul **tidak diperlukan lagi** dan dicabut dari daftar tunggu.
6. **Laporan adalah dokumen bernama, bukan potret tabel yang sedang tersaring** (ditetapkan 2026-08-28, membalik keputusan 2026-08-17 yang menempelkan tombol ekspor pada tiap tabel). Alasan pembalikan: berkas rujukan di `refs/` memang dokumen berformat tetap — "Lap. Akhir Panen Jagung Polri MT. I 2025", "LAPORAN MONOGRAFI UPT KAPITAN MEO 2025", "laporan alsintan", "laporan saprotan" — masing-masing dengan kolom baku yang ditentukan dinas, bukan cerminan tabel modul mana pun. Menu **"Laporan"** menjadi rumah seluruh laporan; tiap laporan satu halaman berformat tetap. **Menu ini tidak punya butir "Semua Laporan"** (dicabut 2026-08-29): submenu memuat ketujuh laporan langsung, dan halaman indeks `/laporan` hanya mengulang isi submenu.
7. **Tombol ekspor dicabut dari kerangka bersama `halaman-daftar`.** Sebelumnya ia berada di dalam komponen itu sehingga muncul otomatis pada setiap halaman daftar — belasan tombol, sebagian besar tanpa laporan di baliknya. Itu kontrol mati (ANTISLOP R-26).
8. **Cakupan laporan tetap dinyatakan sebagai kalimat**, disusun otomatis dari filter yang sedang aktif (`kalimatCakupan`). Alasannya: dokumen yang dicetak atau difoto lalu diserahkan ke dinas kehilangan kontrol filternya, sehingga laporan yang hanya memuat satu SP akan terbaca seolah mewakili seluruh kawasan. Ini memenuhi §9: "angka rekap tanpa cakupannya tidak dapat disalin ke laporan mana pun". **Di halaman berbingkai** kalimat itu ada di blok "Cakupan laporan"; **di rute dokumen** (Putaran 5) ia pindah ke blok judul kop, di bawah baris "TAHUN ...".
9. **Tampilan dokumen.** Isi tiap laporan disajikan sebagai "kertas" berbingkai (`.kertas-dokumen`). Tiap laporan juga punya rute dokumen polos `/laporan/{slug}/dokumen` tanpa sidebar/header; **tabelnya sama persis** dengan halaman berbingkai (satu partial `pages/laporan/isi/{slug}`), tetapi (Putaran 5) rute dokumen adalah **dokumen resmi**: kop surat dua lambang (Kementerian Transmigrasi + lambang Kabupaten, `x-sim.kop-laporan` dari `LaporanData::instansi()`), blok judul di tengah, baris "TAHUN ..." (rentang bila filter tahun aktif, selain itu tahun terakhir deret data), **tanpa bilah filter**. Tombol di halaman berbingkai berjudul **"Generate Laporan"** dan membawa keadaan filter ke tab baru lewat **fragmen hash** (`#sp=..`) -- bukan query string, sebab GitHub Pages tidak melayaninya (`notes.md` 1b.5); `filterLaporan.dariHash()` menerapkannya. Orientasi kertas **diturunkan dari jumlah kolom**: ≥ 9 kolom → landscape (`LaporanData::KOLOM_LANDSCAPE`). `@page` menyetel ukuran kertas saat dicetak.
10. **Rekap indikator kawasan menjadi salah satu halaman laporan**; angka tingkat kawasan tetap dari dashboard, sedangkan rincian per SP dihitung dari data mentah (agregasi tersendiri, `ringkasanDashboard()` tidak disentuh) supaya filter SP menyempitkan seluruh halaman.
11. **Filter tahun pada laporan mengikuti sifat datanya:**
    - **Rentang tahun** (dari–sampai) di halaman daftar bersumbu waktu (`/panen`, `/penanaman`, `/audit-log`) dan di bilah filter laporan yang barisnya transaksi (Hasil Panen, Alsintan, Saprotan — tiap baris milik tepat satu tahun pengadaan).
    - **DILARANG menjumlah lintas tahun** pada rekap agregat: rekap panen yang dijumlah lintas tahun membuat luas 2 ha yang ditanami tiga tahun terbaca 6 ha (§9 poin 8b).
    - **Pemilih tahun TUNGGAL diizinkan** pada laporan snapshot (Rekap Indikator Kawasan, Monografi SP) — memilih SATU tahun untuk melihat "keadaan tahun itu", bukan menjumlah rentang, jadi §9 poin 8b tidak dilanggar (Putaran 5). Datanya per tahun (`DummyData::indikatorKawasanTahun()`, `rekapPerSpTahun()`, `iklimSpTahun()`); irisan tahun terakhir wajib sama persis dengan sumber yang sudah ada. Keadaan fisik wilayah Monografi (SK, batas, jarak, tanah) TIDAK bertahun.
14. **Laporan Monografi SP menyajikan bagian di luar Keadaan Wilayah** (Putaran 6), disaring per SP dari tabel yang sudah ada:
    - **Pendahuluan** (kalimat + ringkas: tahun/KK penempatan, KK/jiwa sekarang, luas, SK), **Keadaan Wilayah** (sebelumnya "Bab II"), **Kependudukan** (penempatan per daerah asal, keadaan penduduk sekarang [ikut tahun terpilih], struktur umur, usia sekolah, mutasi penduduk kumulatif tanpa perkawinan), **Sosial Ekonomi** (luas lahan tani, sertifikat tanah, tanaman pangan, prasarana), **Sosial Budaya** (pendidikan, kesehatan, agama + rumah ibadah, olahraga, keamanan, alsintan, inventaris UPT, fasilitas umum).
    - **Judul tanpa awalan "Bab X."** Berlaku juga untuk judul lama.
    - **Dikarang deterministik** (bukan pendataan per orang, ditandai di view): `DummyData::strukturUmurSp($id)` (14 kelompok umur, Σ = `jiwaPerSp()`), `DummyData::mutasiPendudukSp($id)` (laju kasar × jiwa × lama sejak penempatan; peristiwa `anggota_keluarga.status` yang tercatat ikut ditambahkan). Ini pengecualian sadar terhadap §19a untuk data yang belum dimodelkan; angkanya turunan dari `ringkasanDashboard()`, bukan cacah baris contoh.
    - **Dilewati** (tak ada di sistem): tanaman perkebunan/peternakan/perikanan, harga bahan pokok, koperasi/BUMDES, layanan kesehatan & KB rinci, kelompok kesenian, organisasi desa, harta rumah tangga, jumlah guru & murid, **mutasi perkawinan** (dikecualikan pemilik proyek).
    - SP tanpa data pada satu sub-tabel menampilkan "belum ada data"; pemilik proyek memilih "tampilkan apa adanya + catatan".
12. **Ekspor bukan kewenangan tersendiri.** Ia mengikuti kewenangan `lihat` pada fitur yang bersangkutan; lihat 5.1 catatan 5. Pembatasan sebaran data ditangani cakupan data, bukan dengan menahan tombol ekspor.
13. Template isian luring tidak diletakkan pada halaman laporan, melainkan menjadi **langkah pertama modal impor** di tiap modul yang menerimanya. Menyediakannya di dua tempat berarti dua berkas template yang dapat berbeda diam-diam.
14. **Export Excel/PDF (poin 1-2) dikerjakan SEPENUHNYA di sisi peramban, TANPA paket Composer** (Task 10.1/10.2, keputusan pemilik proyek 2026-09-05, membalik penundaan 2026-08-29 yang menunggu spesifikasi hosting Task 11.3). Dua alasan teknis:
    - **PDF** memakai ulang `@media print` yang sudah matang sejak poin 9/Putaran 3 D2 (kop surat, ukuran A4/F4, kepadatan tabel cetak) lewat dialog cetak peramban ("Simpan sebagai PDF"), dipicu tombol **"Unduh PDF"** -- href sama seperti "Generate Laporan" (rute dokumen + filter lewat hash) DITAMBAH `cetak=1`; rute dokumen memanggil `window.print()` sendiri begitu terbuka (`filterLaporan.dariHash()`, `resources/js/filter-laporan.js`). "Generate Laporan" TETAP tanpa auto-cetak (untuk ditinjau di layar dulu).
    - **Excel** memakai `xlsx` (SheetJS, `resources/js/export-laporan.js`, `window.exportLaporan.keExcel($root, slug)`) membaca tabel yang SUDAH DIRENDER lewat `XLSX.utils.table_to_sheet(tabel, {display:true, raw:true})` -- SATU worksheet per `<table class="tabel-dokumen">`, nama lembar dari `<caption>`-nya (karenanya poin 6 di atas MEWAJIBKAN caption sebagai anak pertama). `display:true` melewati baris yang disembunyikan `x-show` filter Alpine -- **inilah "filter sebelum export" (poin 1): tabel yang dibaca sudah tersaring, tanpa logika penyaringan terpisah.** `raw:true` menahan tebakan tipe sel SheetJS (kaidahnya AS, bukan format Indonesia); konversi angka Indonesia (`1.234,56` → number asli) dikerjakan sendiri, HANYA pada sel berpola ribuan/desimal -- deretan digit polos (NIK, no_kk, telepon, tahun) sengaja dibiarkan teks, sebab itu pengenal bukan kuantitas.
    - Pustaka `xlsx` diinstal dari **CDN SheetJS sendiri** (`https://cdn.sheetjs.com/xlsx-<versi>/xlsx-<versi>.tgz`), BUKAN dari npm registry: rilis npm terakhir (0.18.5) mengandung dua CVE berstatus "No fix available" (prototype pollution, ReDoS) yang HANYA memengaruhi jalur BACA/parse berkas tak tepercaya -- ekspor/tulis eksplisit tidak terdampak -- tetapi versi terbarunya cuma tersedia lewat CDN itu, npm tidak lagi diperbarui. Dimuat lewat `import()` DINAMIS (bukan diimpor statis di `app.js`): pustakanya ~1 MB, tak pantas membengkakkan bundel yang dimuat di setiap halaman untuk fitur yang cuma dipakai saat tombol "Unduh Excel" diklik.

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
9. **SQL mentah (`orderByRaw`, dsb.) wajib portabel SQLite DAN MariaDB.** `tests/Feature/*` berjalan di SQLite `:memory:`, `tests/Database/*` di MariaDB nyata -- keduanya dapat menyentuh rute yang sama. Fungsi khas satu mesin (mis. `FIELD()`, hanya ada di MariaDB) meloloskan `tests/Database` tetapi meruntuhkan `tests/Feature` begitu ada rute yang sama-sama diuji di kedua suite (ditemukan Fase 1, 2026-09-05: `PengaduanController` sempat memakai `FIELD()` dengan catatan "aman sebab tak ada uji Feature untuk `/pengaduan`" -- catatan itu sendiri sudah salah saat ditulis). `CASE WHEN ... THEN ... END` dipahami kedua mesin dan dipakai sebagai gantinya.

#### 13.3 Aturan tampilan dan format
1. Zona waktu aplikasi adalah **WITA (`Asia/Makassar`, UTC+8)** mengikuti lokasi Kabupaten Malaka, dan locale aplikasi adalah `id`.
2. Paginasi tabel bawaan **25 baris**, dengan pilihan 10, 25, 50, dan 100.
3. Angka desimal memakai koma sebagai pemisah desimal dan titik sebagai pemisah ribuan.
4. Volume panen ditampilkan dengan 3 angka desimal beserta satuannya; luas lahan dengan 2 angka desimal beserta satuan hektare.
5. Data kosong ditampilkan sebagai tanda hubung `—`, bukan string kosong atau teks `null`.
6. Setiap halaman daftar dan detail wajib menangani lima keadaan: kosong, memuat, galat, tanpa kewenangan, dan hasil pencarian nihil.
7. Pesan galat dan validasi wajib berbahasa Indonesia yang mudah dipahami operator lapangan, bukan istilah teknis.
8. Palet warna, tipografi, komponen bersama, struktur menu, dan inventaris halaman mengikuti `ui-spec.md`.
9. Kombinasi warna wajib memenuhi rasio kontras WCAG AA sesuai tabel pada `ui-spec.md` §3.2.
10. Tata letak seluruh halaman menggunakan kerangka flexbox vertikal dengan tinggi minimal layar (`min-h-screen flex flex-col` pada kontainer utama dan `mt-auto` pada komponen footer) agar footer selalu menempel wajar di bawah pada halaman berkonten sedikit tanpa menetapkan tinggi tetap (*hardcoded fixed height*).
11. Animasi penanda status/urgensi (seperti titik denyut pada kartu indikator pengaduan) wajib dibungkus `motion-safe:animate-ping` agar mematuhi preferensi sistem pengguna (*prefers-reduced-motion*), berdimensi tetap dengan `shrink-0` (*zero layout shift*), serta hanya berdenyut bila nilai metrik `> 0`.

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
8. **Metadata berkas disimpan pada registry `berkas`, bukan kolom path pada tabel domain** (ditetapkan 2026-09-02, Putaran 12). Sebelumnya 24 kolom `VARCHAR(255)` tersebar di 17 tabel, dan tidak satu pun merekam `mime` maupun `ukuran` - padahal poin 1 dan 2 mewajibkan keduanya divalidasi di sisi server. Tanpa merekamnya, tidak ada cara memeriksa ulang apa yang sebenarnya tersimpan.
8a. **BUKAN tabel polymorphic.** Registry tidak memiliki kolom `entity_type`/`entity_id`. Kepemilikan dinyatakan **pivot per domain** bagi yang boleh memegang banyak berkas, dan **foreign key langsung** bagi yang selalu satu. Pilihan itu menjaga dua hal yang polymorphic justru mencabut: integritas referensial yang ditegakkan basis data, dan penyaring cakupan data tunggal (5.0b-1 poin 8) yang menempel pada model pemilik SP.
8b. **Dua belas domain memakai pivot** dan boleh memegang lebih dari satu berkas: transmigran, rumah, kawasan, inventaris SP, fasilitas SP, infrastruktur, alsintan, penanaman, hasil panen, pengaduan, penanganan pengaduan, dan pengguna. Kolom `peran` menggantikan nama kolom lama, sehingga `foto` dan `dokumen_pendukung` yang dahulu dua kolom kini dua baris berperan berbeda.
8c. **Lima kolom memakai FK langsung** sebab berkasnya memang selalu satu: SP, poktan, saprotan (foto dan berita acara), dan distribusi alsintan. `ON DELETE SET NULL`, sebab menghapus berkas tidak boleh menghapus barisnya.
8d. **Foto pengguna memakai pivot meski selalu satu.** FK langsung pada tabel `user` melahirkan siklus: `berkas.user_id` menunjuk `user`, sedangkan `user.foto_berkas_id` menunjuk balik. Tidak ada urutan `CREATE TABLE` yang memenuhi keduanya. Pembatasan satu foto ditegakkan `UNIQUE` pada `user_id` saja.
8e. **`user_id` pada registry NULLABLE.** Warga mengunggah bukti lewat kanal publik tanpa akun (10b.1), sehingga mewajibkannya menutup kanal yang justru paling perlu terbuka.
8f. **Kolom `disk` menyimpan nama disk Laravel** (`local`, `s3`, `gcs`), bukan penanda boolean. Boolean hanya sanggup membedakan dua keadaan, sedangkan menyimpan namanya membuat `Storage::disk()` langsung terpakai dan penambahan penyimpanan ketiga tidak menuntut kolom baru. Menyiapkan peningkatan ke object storage yang disebut 2.2 poin 6.
8g. **Registry menyimpan METADATA, bukan isi berkas.** Poin 3 dan 4 tetap berlaku sepenuhnya: berkas fisik berada di disk privat, basis data hanya menyimpan pathnya.
9. **Tautan publik permanen dilarang disimpan.** Kolom semacam `public_link` bertentangan dengan poin 6; bila kelak dipakai signed URL, ia dibangkitkan saat diminta dan tidak dipersistensi sebab kedaluwarsa.
10. **Pengunggah tidak dicatat ulang sebagai kolom penyunting terakhir.** `audit_log` sudah menyimpan pelaku beserta `data_lama` dan `data_baru`; kolom `updater` hanya merekam perubahan terakhir dan menimpa jejak sebelumnya.
11. **Isian unggah jamak memakai `x-sim.berkas-unggah`; yang tunggal tetap `x-sim.file-upload`** (ditetapkan 2026-09-02, Putaran 14). Poin 8b membuat multi-berkas MUNGKIN pada tingkat struktur, tetapi kemampuan itu tidak dapat dicapai petugas selama formnya masih memasang isian tunggal. Jamak dipakai hanya bila lapangannya memang jamak (beberapa titik kerusakan pada satu aset, beberapa sudut foto atas satu kejadian, atau dokumen berbeda jenis seperti KTP/KK/SK); memaksa jamak pada yang memang selalu satu hanya menyulitkan tanpa memberi apa pun.
11a. **Batas 5 MB poin 1 diperiksa PER BERKAS, dan pesan galat wajib menyebut nama berkas yang ditolak.** Petugas yang memilih lima berkas sekaligus lalu menerima pesan tanpa nama tidak tahu mana yang harus diganti.
11b. **Tautan buka berkas adalah milik panel rincian, bukan form unggah.** Halaman rincian meng-include formnya sendiri, sehingga memasang tautan pada komponen unggah menerbitkan dua tautan ke berkas yang sama. Form cukup menampilkan nama berkas yang sudah tersimpan.
11c. **Isian wajib berarti PALING SEDIKIT satu berkas ada, bukan selalu diunggah ulang.** Penanda `required` dipasang hanya ketika belum ada berkas tersimpan; bila sudah ada, menyunting data lain pada form yang sama tidak boleh memaksa pengunggahan ulang.
11d. **Panel rincian wajib membaca registry, bukan kolom lama.** Dua panel sempat masih membaca kolom yang sudah dicabut Putaran 12, sehingga berkas yang nyata-nyata ada tidak muncul sama sekali - unggahan tanpa jalan dibuka, yaitu kontrol mati yang dilarang R-26.
11e. **Berpivot TIDAK otomatis berarti jamak di layar.** Poin 8b menyebut dua belas domain berpivot, dan itu benar sebagai daftar STRUKTUR, tetapi tidak seluruhnya layak dijamakkan pada antarmuka. Yang dijamakkan Putaran 14 hanya tujuh: transmigran, infrastruktur, pengaduan, kawasan, rumah, inventaris SP, dan fasilitas SP. `user_berkas` tetap tunggal sebab `UNIQUE (user_id)` memang membatasinya (8d); `penanganan_pengaduan` belum dapat dijamakkan sebab barisnya tidak punya kolom id untuk dicocokkan; sedangkan poktan, saprotan, dan SP sama sekali bukan pivot melainkan FK langsung (8c). Sebelum menjamakkan sebuah domain, periksa `schema.sql`, bukan daftar pada 8b.

### 14b. Aturan Akun dan Pemulihan Kata Sandi

#### Pembuatan akun

> **Perubahan 2026-08-14.** Sebelumnya Admin mengisi username sekaligus mengetik kata sandi awal, dan surel bersifat opsional. Susunan itu membebani Admin dengan mengarang username orang lain, sedangkan kata sandi karangan manusia cenderung berpola dan dipakai ulang untuk banyak akun. Poin 3 sampai 5 di bawah menggantikannya.

1. **Tidak ada pendaftaran mandiri.** Sistem tidak menyediakan halaman daftar akun. Seluruh akun dibuat oleh Admin lewat menu Manajemen Pengguna.
2. Setiap akun wajib diberi satu role. Bila role tersebut bercakupan `Per SP`, akun wajib pula diberi minimal satu penugasan SP.
3. **Kata sandi awal dibangkitkan sistem**, bukan diketik Admin, lalu ditandai `password_harus_diganti = TRUE`. Nilainya ditampilkan **satu kali** di layar setelah akun tersimpan dan tidak pernah dapat dibaca ulang.
3a. Kata sandi tersebut **dikirim juga ke surel** petugas, tetapi penyerahan langsung tetap dianjurkan. Jaringan di lokus tidak selalu memadai, sehingga surel adalah salinan, bukan pengganti.
3b. Akun baru **selalu langsung aktif**. Tidak ada pilihan menonaktifkan pada formulir; penonaktifan dan pengaktifan kembali dilakukan lewat tombol pada halaman daftar agar seluruh perubahan keadaan akun tercatat lewat satu jalur yang sama.

#### Kredensial masuk
4. Sistem menerima **email atau username** pada satu kolom isian yang sama. Keduanya unik antar-akun.
4a. **Surel wajib diisi Admin** saat akun dibuat, sebab itulah satu-satunya kredensial yang dimiliki petugas ketika pertama kali masuk.
5. **Username dibuat sendiri oleh petugas** pada saat masuk pertama kali, bersamaan dengan penggantian kata sandi sementara. Admin tidak mengarangkannya, sebab petugaslah yang akan mengetiknya setiap hari.
5a. Username hanya boleh memuat huruf kecil, angka, titik, dan garis bawah, dengan panjang 3 sampai 50 karakter. Ketersediaannya diperiksa saat diketik, sebelum formulir dikirim.
6. Seluruh pengguna sistem adalah petugas, sehingga tidak ada kredensial berbasis NIK. Warga tidak memiliki akun.

#### Pemulihan kata sandi

> **Perubahan 2026-08-12.** Sebelumnya sistem sama sekali tidak menyediakan pemulihan mandiri. Alasannya waktu itu: tidak semua transmigran memiliki alamat surel. Alasan tersebut **gugur** setelah ditetapkan bahwa warga tidak memiliki akun sama sekali (§5.0 poin 5), sehingga seluruh pemegang akun adalah petugas bersurel dinas. Jalur mandiri kini ditambahkan **sebagai pelengkap**, bukan pengganti. Alasan kedua, yaitu jaringan lokus yang tidak selalu memadai, masih berlaku dan itulah sebabnya jalur Admin dipertahankan.

**Dua jalur pemulihan, keduanya sah:**

| Jalur | Dipakai ketika |
|---|---|
| **Kode verifikasi lewat surel** | Petugas memiliki surel dinas aktif dan jaringan memadai |
| **Setel ulang oleh Admin** | Surel tidak diterima, akun tanpa surel aktif, atau petugas berada di lokus bersinyal lemah |

7. Sistem mengirim **kode verifikasi enam digit**, bukan tautan yang dapat diklik. Kode dapat dibaca dari layar lain lalu diketik, sehingga tetap dapat dipakai ketika surel hanya dapat dibuka di perangkat berbeda atau ketika peramban gagal memuat tautan panjang di jaringan lemah.
8. Kode berlaku **15 menit**, sekali pakai, dan hangus begitu kode baru diminta. Kode lama wajib dibatalkan agar tidak ada dua kode sah beredar bersamaan.
9. Halaman permintaan kode **tidak pernah menyatakan apakah alamat terdaftar**. Pesan yang ditampilkan selalu sama, sebab pesan yang membedakan keduanya mengubah halaman ini menjadi alat memeriksa siapa saja yang memiliki akun.
10. Permintaan kode dibatasi **3 kali per jam per akun** dan percobaan pemasukan kode dibatasi **5 kali per kode** (§14c). Setelah itu kode hangus dan petugas wajib meminta yang baru.
11. Jalur Admin pada poin 12 sampai 15 **tetap berlaku penuh** dan tidak boleh dihapus. Jalur inilah satu-satunya yang bekerja tanpa sambungan surel.
12. Pengguna yang lupa kata sandi dapat menghubungi Admin. Admin menyetel ulang lewat Manajemen Pengguna, lalu menyerahkan kata sandi sementara **secara langsung**. Sejak 2026-08-14 kata sandi tersebut dikirim juga ke surel pengguna, tetapi penyerahan langsung tetap wajib dilakukan: jalur Admin justru disediakan untuk petugas di lokus bersinyal lemah, yang belum tentu dapat membuka surelnya saat itu juga.
13. **Penyetelan ulang oleh Admin** (dan perintah artisan darurat) menghasilkan kata sandi **sementara**, sehingga `password_harus_diganti` bernilai `TRUE`: pengguna diarahkan ke halaman ganti kata sandi saat masuk berikutnya dan **tidak dapat mengakses halaman lain** sebelum menggantinya. **Pemulihan lewat kode verifikasi TIDAK menyetel `password_harus_diganti`** (diubah 2026-09-03): pada jalur itu pengguna sudah mengetik sendiri kata sandi finalnya di halaman verifikasi, sehingga memaksanya mengganti lagi hanya menyuruh mengetik dua kali. Alasan kolom ini sejak awal adalah mencegah kata sandi sementara buatan Admin dipakai terus-menerus (`notes.md` 2026-08-11), dan jalur kode verifikasi tidak pernah membuat kata sandi sementara.
14. Admin **tidak dapat melihat** kata sandi pengguna mana pun, karena hanya hash yang tersimpan. Admin hanya dapat menimpanya dengan nilai baru.
15. Setiap penyetelan ulang wajib tercatat pada audit log dengan aksi `Reset Kata Sandi`, memuat petugas pelaku, akun sasaran, waktu kejadian, dan **jalur yang dipakai**. Pemulihan mandiri tercatat atas nama pemilik akun itu sendiri.

#### Perlindungan akun terakhir
16. Sistem menolak penonaktifan maupun penghapusan akun Admin terakhir yang masih aktif, agar sistem tidak pernah kehilangan seluruh jalur administrasinya.
17. Wajib tersedia perintah artisan khusus untuk menyetel ulang kata sandi Admin lewat terminal server, sebagai jalur pemulihan darurat bila seluruh Admin kehilangan akses.

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

### 14d. Aturan Notifikasi Internal
1. Notifikasi disimpan **satu baris per penerima dan kejadian** agar status dibaca tidak berpindah antar-akun.
2. Penerima wajib memiliki izin lihat modul terkait dan memenuhi cakupan data role pada saat kejadian dibuat.
3. Lima sumber notifikasi: pengaduan baru, pengaduan mendesak belum selesai, SP berubah menjadi Perlu Penanganan, infrastruktur Rusak Berat, serta akun dibuat/reset oleh Admin.
4. Deduplikasi notifikasi wajib memasukkan `user_id`; satu penerima yang belum membaca tidak boleh menghalangi penerima lain.
5. Membuka dropdown tidak menandai notifikasi dibaca. Baris ditandai dibaca saat dipilih, atau lewat tindakan "Tandai semua dibaca".
6. Aksi baca wajib membatasi kueri ke `user_id` pengguna yang sedang masuk; id notifikasi tidak boleh membuka milik akun lain.
7. Pembentukan notifikasi dilakukan setelah transaksi bisnis dan sinkronisasi pivot berhasil.
8. Notifikasi adalah pemberitahuan ringkas, bukan pengganti audit log maupun surel kepada warga.

### 14e. Aturan Surel Sistem
1. Seluruh surel memakai layout resmi bersama yang membaca identitas instansi dari CMS; isi dinamis penting seperti kode verifikasi, nomor pengaduan, status, dan masa berlaku tetap dikuasai kode.
2. CMS hanya boleh mengubah sapaan, penutup, nama pengirim, dan catatan kaki agar kesalahan redaksional tidak dapat menghapus informasi keamanan.
3. Surel harus ringan: CSS inline, tanpa font eksternal dan tanpa pelacak. Nomor/kode tetap terlihat sebagai teks bila gambar tidak dimuat.
4. Pengaduan dengan alamat email menerima nomor saat dikirim dan pembaruan pada setiap perubahan status. Surel tidak pernah menjadi satu-satunya cara memperoleh nomor atau perkembangan.
5. Kegagalan SMTP dicatat ke log tetapi tidak membatalkan transaksi bisnis. Antarmuka wajib mengatakan apakah salinan email berhasil dikirim.

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

#### 16.0a Uji wajib menyasar janji, bukan kode yang sudah ditulis

7. **Uji disusun dari aturan dan janji fitur, bukan dari membaca kode yang baru selesai.** Uji yang ditulis dengan menelusuri kode sendiri hanya dapat menemukan hal yang sudah diketahui penulisnya, sehingga sebuah fitur dapat lulus seluruh uji tanpa dapat dipakai.

8. **Keberadaan string di HTML bukan bukti fitur bekerja.** `toContain('name="..."')` hanya membuktikan atribut ada. Untuk isian yang dapat bertambah, berkurang, atau berubah bentuk, uji wajib memeriksa **kemampuannya**: dapatkah baris ditambah, dapatkah dicabut, apakah bagian yang seharusnya tersembunyi benar-benar tersembunyi.

9. **Dilarang memilih satu baris data contoh tanpa alasan.** Uji yang membaca `/modul/1` menguji keadaan yang paling ramah. Bila perilakunya bergantung status, uji wajib **menyisir seluruh nilai status** atau menyebut alasan mengapa satu baris sudah mewakili. Cacat 2026-08-19 lolos justru karena ujinya membaca pengaduan berstatus Diproses, sedangkan yang rusak adalah yang berstatus Selesai.

10. **Perilaku yang bergantung peramban wajib diuji di peramban sungguhan.** Berkasnya diletakkan pada `tests/Browser/`, dijalankan lewat Edge headless dan protokol DevTools tanpa menambah dependensi. Berlaku untuk isian dinamis, modal berlapis, penguncian gulir, grafik, dan tata letak. Uji Pest tetap wajib, tetapi tidak dapat menggantikannya.

11. Dua jebakan yang sudah terbukti dan wajib dihindari saat menulis uji peramban: keterlihatan diperiksa lewat `getClientRects().length`, **bukan** `offsetParent` yang bernilai `null` pada elemen berposisi `fixed`; dan modal wajib ditutup sebelum berpindah halaman, sebab penguncian gulirnya masih menempel pada halaman berikutnya.

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
8a. Kode JavaScript inline di dalam atribut HTML (`x-data`, `x-bind`, `x-on`) **dilarang memuat tanda kutip yang sama dengan pembungkus atribut tanpa encoding**. Selector CSS di dalam `x-data="..."` memakai nilai atribut tanpa kutip bila sah (`meta[name=csrf-token]`) atau dipindahkan ke modul JavaScript. Satu kutip ganda mentah dapat menutup atribut dan membuat sisa kode tampil sebagai teks pada setiap pemakai komponen.
8b. Setiap komponen dengan `x-data` inline yang memuat fungsi panjang wajib memiliki uji HTML terender yang membuktikan atribut tetap utuh sampai atribut berikutnya; memeriksa keberadaan potongan JavaScript saja tidak cukup karena kode yang bocor tetap ditemukan sebagai teks halaman.

#### 19a. Batas kesaksian data contoh

9. **Data contoh (`DummyData`) sah sebagai bukti tentang kode, tidak pernah sebagai bukti tentang lapangan.**

| Pertanyaan | Boleh dijawab dari data contoh? |
|---|---|
| Apakah kolom ini terender, apakah filter ini cocok, apakah keadaan kosong tertangani, apakah enum ini terpakai | **ya** |
| Apakah kasus ini pernah terjadi, seberapa sering, apakah bisa lebih dari satu, apakah perlu tabel tersendiri | **tidak** |

10. Untuk pertanyaan jenis kedua, sumbernya hanya tiga: `prd.md`/`rules.md`, sifat domain yang dapat dijelaskan alasannya, atau **bertanya kepada pemilik proyek**. Kalimat "tidak ada pada data contoh" **dilarang** dipakai sebagai alasan keputusan struktur.
11. **Kardinalitas ditanyakan, tidak disimpulkan.** Setiap kali muncul pertanyaan "satu atau banyak", itu pertanyaan lapangan. Data contoh tidak dapat menjawabnya sebab ia potret sesaat tanpa sumbu waktu, sengaja dibuat minimal, dan dikalibrasi ke kebutuhan tampilan, bukan ke keadaan lapangan.
12. Alasan larangan ini melingkar: data contoh dikarang oleh AI sendiri, sehingga "tidak ada di data" hanya berarti "belum terpikir saat menuliskannya". Itu fakta tentang penulisnya, bukan tentang Kobalima Timur. **Lima pelanggaran nyata** beserta akibatnya tercatat pada `notes.md` bagian 1c.

13. Waspadai bentuk yang arahnya terbalik: **data karangan mengalahkan alasan lapangan yang sudah ditulis benar**. Ia menyamar sebagai perapian ketidakkonsistenan antara dokumen dan kode, sehingga tampak seperti perbaikan. Pembedanya satu pertanyaan: *mana yang disesuaikan pada mana?* Bila dokumen yang mengalah pada data contoh, arahnya sudah salah.

14. Aturan ini **wajib diperiksa ulang secara berkala** terhadap keputusan yang sudah diambil, bukan hanya diterapkan pada pekerjaan baru. Audit 2026-08-19 membuktikan alasannya: dua pelanggaran ditemukan setelah aturan ini berlaku, dan salah satunya melanggar prinsip yang tertulis 400 baris di atasnya pada dokumen yang sama. Menulis aturan tidak cukup tanpa kebiasaan memeriksanya terhadap pekerjaan sendiri.

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

#### 20a. Menyisir skenario, bukan menunggu disodorkan

9. Ketika pemilik proyek menyampaikan satu skenario atau kasus, AI **wajib menyisir skenario lain sendiri** sebelum menyusun rencana. Kasus yang disebut pemilik proyek adalah titik masuk, bukan batas pembahasan.
10. Penyisiran menempuh lima sudut berikut, dan yang tidak menghasilkan temuan dinyatakan kosong, bukan didiamkan:

| Sudut | Pertanyaan penuntun |
|---|---|
| **Privasi** | Apakah data ini terlihat di kanal publik? Apakah menyangkut orang tertentu? Apakah cakupan data role menyaringnya? |
| **Siklus hidup** | Apa yang terjadi bila data induknya dihapus, dipindah, atau penghuninya berganti setelah baris ini dibuat? |
| **Kejujuran angka** | Apakah rekapnya dapat menyesatkan? Apakah jumlah besar berarti buruk? Apakah data yang bolong tersembunyi? |
| **Alur kerja** | Bagaimana data lama yang lahir sebelum aturan ini? Apakah petugas dapat memperbaikinya kemudian? |
| **Teknis** | Dampak ke impor, ekspor, halaman statis, middleware, dan uji penjaga yang berbasis daftar rute |

11. Hasil penyisiran disampaikan **sebelum** kode ditulis, dengan menandai mana yang butuh keputusan pemilik proyek dan mana yang sudah ada usulan penanganannya. Tujuannya menekan revisi susulan akibat kasus yang sebenarnya dapat diperkirakan sejak awal.

#### 20b. Menulis rencana sebelum eksekusi

12. **Sebelum menyentuh kode, AI wajib menuliskan rencana pengerjaan yang lengkap ke `agents/session-notes.md`**: berkas yang akan disunting, keputusan yang sudah diambil pemilik proyek, penjaga yang wajib dipatuhi, dan cara verifikasinya. Rencana yang hanya hidup di kepala AI hilang begitu sesi berganti atau terputus, dan agent berikutnya harus menebak-nebak dari diff yang setengah jadi. Aturan ini lahir dari sesi yang terhenti di tengah audit tanpa jejak rencana yang dapat dibaca (`notes.md` 1g.8).
13. Rencana di `session-notes.md` bersifat sementara dan boleh ditimpa tiap sesi; yang permanen tetap `notes.md` dan `tasklist.md`.
