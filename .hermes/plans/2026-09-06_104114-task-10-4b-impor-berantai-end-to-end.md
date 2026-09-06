# Task 10.4b — Impor Berantai End-to-End Implementation Plan

> **For Hermes:** Use subagent-driven-development skill to implement this plan task-by-task.

**Goal:** Mengaktifkan impor Rumah, Lahan, Kelompok Tani, Saprotan, Penanaman, dan Hasil Panen secara aman, dapat diulang tanpa menggandakan data, serta konsisten dengan form, basis data, riwayat, laporan, kewenangan, dan aturan bisnis aplikasi.

**Architecture:** Pertahankan satu mesin `App\Support\ImporEngine`, satu berkas per entitas, satu sheet input `Data`, serta parser XLSX/CSV yang sama. Tambahkan pengelompokan baris hanya untuk Poktan dan Saprotan. Pusatkan operasi domain yang dipakai bersama oleh form manual dan impor agar aturan riwayat hunian, keanggotaan, distribusi, stok, lahan, dan panen tidak disalin ke dua tempat.

**Tech Stack:** Laravel 12, PHP 8.2, Eloquent/MariaDB, Pest, PhpSpreadsheet 5.9, Blade/AlpineJS, Vite.

---

## 1. Keputusan final hasil rekonsiliasi

Plan ini memakai keputusan terbaru dari diskusi pengguna dan hasil audit OpenCode, bukan paket minimal lama.

1. Impor bersifat **buat baru yang aman diulang**:
   - identitas belum ada: dibuat;
   - identitas sudah ada dengan isi identik: dilewati tanpa perubahan;
   - identitas sama dengan isi berbeda: gagal dan pengguna diarahkan ke menu Ubah;
   - data terhapus tidak dipulihkan otomatis.
2. Satu berkas per entitas dan tetap satu sheet input `Data`.
3. Rumah/Lahan/Penanaman/Panen atomik per baris; Poktan atomik per kelompok nama Poktan; Saprotan atomik per kelompok kode pengadaan.
4. Nomor rumah wajib dan unik di dalam satu SP.
5. Riwayat hunian memakai **tahun untuk seluruh alur**, dengan nama semantik `tahun_mulai_menghuni` dan `tahun_selesai_menghuni`; `tahun_pembangunan` tetap dipertahankan.
6. Lahan minimal memiliki pekarangan atau usaha; `luas_usaha` dihitung dari `luas_kering + luas_basah`; status sertifikat diperbarui atomik pada keluarga.
7. Impor Poktan mencakup profil dan anggota.
8. Impor Saprotan mencakup pengadaan dan distribusi; satu distribusi agregat per Poktan.
9. `kode_saprotan` dan `kode_penanaman` diisi petugas, unik, dan tidak dapat diubah.
10. Hasil Panen merujuk `kode_penanaman`; produksi dihitung server-side.
11. Hasil Panen yang salah **dibatalkan**, bukan dihapus sebagai koreksi: alasan, petugas, dan waktu pembatalan disimpan; catatan batal tetap terlihat; satu pengganti aktif boleh dibuat.
12. Instruksi urutan impor tampil singkat pada modal dan lengkap pada sheet `Petunjuk`, menggunakan bahasa formal yang mudah dipahami dan tanpa istilah teknis.
13. Berkas/foto tidak ikut impor dan tetap dilampirkan melalui halaman data setelah impor.

## 2. Baseline setelah audit pekerjaan Claude

Pekerjaan Claude sudah selesai dan masuk commit `141902c` (`Tahap 12: perbaikan hasil audit Putaran 16 (Gelombang 7)`). Working tree bersih selain direktori `.hermes/` yang memuat plan ini. Audit terhadap commit tersebut menghasilkan keputusan berikut:

1. **Reuse tanpa dibuat ulang:**
   - penjaga hapus lintas-SP pada Rumah, Lahan, dan Transmigran;
   - perbaikan penguncian SP pada form Rumah;
   - FK komposit Lahan → Transmigran dan migration konvergensi `2026_09_06_140000_selaraskan_fk_komposit_lahan_transmigran.php`;
   - unique gate satu keanggotaan Poktan aktif;
   - local scope `SatuanPermukiman::terlihatOlehPengguna()`.
2. **Fondasi yang harus dikonversi, bukan diduplikasi:** migration `2026_09_06_130000_hasil_panen_unik_ber_gate_soft_delete.php` sudah memungkinkan panen baru setelah soft delete, tetapi keputusan final proyek adalah **Batalkan + Ganti** dengan alasan, petugas, waktu, dan riwayat tetap terlihat. Buat migration lanjutan; jangan mengedit migration yang sudah tercatat dan jangan membuat unique gate kedua yang saling bertabrakan.
3. **Celah keamanan yang harus selesai sebelum aktivasi impor:** endpoint unduh template belum melakukan pemeriksaan dinamis `lihat+tambah`, dan beberapa penyaji/laporan melewati cakupan SP dengan `withoutGlobalScopes()`.
4. **Prasyarat identitas yang belum selesai:** `lahan.kode_lahan` masih nullable; kontrak plan mengharuskannya wajib dan unik. Template Rumah/Lahan saat ini juga masih kontrak lama.
5. **Integritas Rumah belum setara dengan Lahan:** Rumah masih memiliki FK penghuni dan SP yang independen. Tambahkan FK komposit Rumah → Transmigran agar jalur selain controller pun tidak dapat menyimpan penghuni dan SP yang berbeda.
6. **Migration konvergensi Lahan perlu preflight:** `_140000` hanya bertindak ketika FK lama dengan nama tertentu masih ada. Sebelum impor Lahan aktif, periksa keberadaan FK target dan mismatch data; tangani keadaan upgrade parsial lewat migration korektif bila ditemukan.
7. Tes Database memakai satu schema `digitrans_test` dan menjalankan `migrate:fresh`; **jangan menjalankan file tes Database secara paralel**. Kegagalan tabel hilang/deadlock pada audit berasal dari beberapa proses tes yang saling merombak schema. Saat dijalankan serial, suite terfokus lulus.
8. Sebelum implementasi, jalankan ulang `git status --short`; bila proses lain kembali menulis repository, hentikan pekerjaan. Tidak membuat commit kecuali pengguna meminta secara eksplisit.

---

## 3. Urutan data yang dijanjikan kepada pengguna

Gunakan urutan berikut sebagai satu sumber isi petunjuk:

1. Data Transmigran.
2. Data Rumah, Data Lahan, dan Kelompok Tani.
3. Data Saprotan beserta pembagiannya kepada kelompok tani.
4. Data Penanaman.
5. Data Hasil Panen.

Teks ringkas pada modal:

> Isi data secara berurutan agar setiap data dapat ditemukan dan dihubungkan dengan benar. Pastikan data yang disebut dalam berkas sudah tercatat terlebih dahulu. Baris yang belum memiliki data pendukung akan dilewati dan ditampilkan pada hasil impor.

Contoh petunjuk khusus Penanaman:

> Sebelum mengimpor Data Penanaman, pastikan kelompok tani, lahan anggotanya, serta pembagian benih kepada kelompok tersebut sudah tercatat.

Hindari istilah `relasi`, `foreign key`, `parent`, `dependency`, `record`, `payload`, dan `id database` pada teks pengguna.

---

## 4. Rencana implementasi

### Task 1: Rekonsiliasi dan kunci baseline kerja

**Objective:** Memastikan plan diterapkan di atas keadaan repository yang benar tanpa menimpa perubahan lain.

**Files inspected:** commit `c5ec5e4..141902c`, khususnya perubahan panen, FK komposit Lahan, cakupan SP, dan pengujian impor.

**Steps:**
1. Pastikan HEAD minimal `141902c` dan working tree tidak memuat pekerjaan lain.
2. Catat komponen commit yang direuse serta fondasi panen yang harus dikonversi.
3. Jalankan tes baseline **secara serial**, bukan beberapa proses Pest terhadap `digitrans_test` yang sama.
4. Catat baseline lulus/gagal; jangan mengubah kode pada langkah ini.

**Verify:** `git diff --check`; `SpRuntimeTest`, `RumahTest`, `LahanTest`, `HasilPanenTest`, `Domain8ProduksiTest`, `RekapPanenTest`, dan `ImporTest` secara serial.

### Task 2: Ubah riwayat hunian dari tanggal menjadi tahun end-to-end

**Objective:** Menyimpan informasi yang benar-benar diketahui pengguna tanpa membuat tanggal 1 Januari palsu.

**Files:**
- Create migration baru setelah timestamp migration terakhir; jangan hanya mengedit migration lama yang mungkin sudah berjalan.
- Modify `database/data/schema.sql`.
- Modify `app/Models/RiwayatPenghunian.php`.
- Modify `app/Http/Controllers/RumahController.php`.
- Modify `resources/views/pages/rumah/form.blade.php`.
- Modify `resources/views/pages/rumah/detail.blade.php`.
- Modify pemetaan rumah di `app/Http/Controllers/TransmigranController.php` bila diperlukan.
- Modify `database/seeders/RumahSeeder.php`, `database/seeders/DemoSeeder.php`, dan fixture terkait.
- Modify `agents/data-dictionary.md`, `agents/erd.md`, `agents/rules.md`, dan `agents/ui-spec.md`.
- Test `tests/Database/RumahTest.php`, `tests/Database/Domain5KependudukanTest.php`, serta tes halaman terkait.

**TDD slices:**
1. RED: Tahun mulai wajib ketika rumah Dihuni; tidak berlaku untuk rumah kosong.
2. GREEN: Tambahkan `tahun_mulai_menghuni` pada form/controller dan simpan ke riwayat.
3. RED: Pergantian/pengosongan penghuni wajib membawa `tahun_selesai_menghuni`; tahun selesai tidak boleh sebelum tahun mulai.
4. GREEN: Tutup riwayat lama dan buka riwayat baru secara atomik.
5. RED: Migrasi data lama mempertahankan tahun dari `tanggal_masuk/tanggal_keluar`.
6. GREEN: Tambahkan kolom YEAR baru, salin `YEAR(tanggal_*)`, lalu cabut kolom tanggal setelah data tersalin.
7. RED/GREEN: Halaman detail menampilkan tahun, bukan format tanggal lengkap.

**Verify:** RumahTest, Domain5KependudukanTest, `php artisan sim:banding-skema --lengkap`, `php artisan view:cache` lalu `view:clear`.

### Task 3: Tambahkan identitas domain yang stabil

**Objective:** Menyediakan kode yang dapat dipakai ulang tanpa mengandalkan nama atau PK internal.

**Files:**
- Create migration untuk unique `(satuan_permukiman_id, no_rumah)` dan kewajiban `no_rumah`.
- Create migration untuk indeks/FK komposit nullable `(transmigran_id, satuan_permukiman_id)` Rumah → Transmigran; Rumah kosong dengan `transmigran_id = NULL` tetap sah.
- Create migration untuk mengisi `lahan.kode_lahan` lama secara deterministik, lalu menjadikannya NOT NULL + UNIQUE.
- Create migration untuk `saprotan.kode_saprotan` UNIQUE.
- Create migration untuk `penanaman.kode_penanaman` UNIQUE.
- Modify `database/data/schema.sql`.
- Modify `app/Models/Rumah.php`, `Lahan.php`, `Saprotan.php`, `Penanaman.php`.
- Modify form/controller/detail Lahan, Saprotan, dan Penanaman agar kode tersedia juga pada alur manual.
- Modify seeders/factories/demo data.
- Modify dokumentasi skema.
- Test domain Rumah, Saprotan, Penanaman, dan pembanding skema.

**Rules:**
- Format contoh: `SAP-2026-001`, `TAN-2026-001`.
- Kode wajib saat create dan tidak dapat diubah setelah tersimpan.
- Data lama diberi kode deterministik dalam migration, bukan berdasarkan urutan hasil query tanpa `orderBy`.
- Identitas terhapus tetap menahan kode; importer tidak memulihkan otomatis.
- Jangan membuat ulang FK komposit Lahan; gunakan struktur akhir yang sudah dijamin migration `2026_09_06_140000`.
- Sebelum mengandalkan `_140000`, uji keadaan legacy dan keadaan parsial “unique sudah ada, FK lama tidak ada, FK target belum ada”; migration korektif harus memeriksa FK target, bukan hanya nama FK lama, serta menolak mismatch data dengan pesan yang jelas.

### Task 4: Ganti koreksi panen dari “hapus” menjadi “batalkan”

**Objective:** Menjaga jejak kesalahan dan memungkinkan satu hasil pengganti aktif.

**Files:**
- Create migration **setelah `2026_09_06_140000`** yang mengonversi gate `deleted_at` menjadi gate status aktif: tambah `status`, `dibatalkan_pada`, `dibatalkan_oleh`, `alasan_pembatalan`; ganti ekspresi generated `penanaman_aktif_id`; pertahankan nama unique yang final tanpa membuat gate paralel.
- Modify `app/Models/HasilPanen.php`, `app/Models/Penanaman.php`.
- Modify `app/Http/Controllers/HasilPanenController.php`.
- Modify route panen di `routes/internal.php`.
- Modify `resources/views/pages/panen/index.blade.php`, detail panen, dan detail penanaman.
- Modify `app/Support/PenyajianPanen.php`, `RekapPanen.php`, dashboard/laporan consumers.
- Modify `app/Providers/ViewServiceProvider.php` dan `app/Support/PetaIzinRute.php` untuk relasi aktif/riwayat dan aksi Batalkan.
- Test `tests/Database/HasilPanenTest.php`, `Domain8ProduksiTest.php`, report/dashboard tests.

**Behavior:**
- Aksi UI: `Batalkan`, bukan `Hapus`.
- Alasan wajib; aktor dan waktu otomatis.
- Panen batal tetap terlihat pada rincian Penanaman, tetapi tidak ikut dashboard, laporan, dan rekap.
- Hanya satu panen Aktif per Penanaman.
- Pengganti boleh dibuat jika tidak ada panen Aktif.
- UUID dibuat hanya pada create; update tidak mengganti UUID.
- Data lama yang masih hidup dimigrasikan menjadi Aktif. Data lama yang sudah soft-delete tetap menjadi jejak lama dan tidak otomatis dipulihkan; tetapkan aturan konversinya secara eksplisit di migration/test agar tidak masuk laporan.
- Relasi dibedakan menjadi panen aktif dan riwayat panen; aksi Batalkan tidak melepas berkas dan tidak soft-delete catatan.
- Audit seluruh consumer: daftar/rincian Panen, rincian Penanaman dan SP, `ViewServiceProvider`, `PenyajianPanen`, `RekapPanen`, `RekapDashboard`, dan `LaporanData`.
- Gunakan izin `hasil_panen,hapus` yang sudah ada untuk aksi Batalkan; jangan menambah permission baru tanpa kebutuhan.
- Migration rollback harus mendeteksi keberadaan lebih dari satu riwayat per Penanaman dan gagal dengan pesan jelas; jangan mencoba memasang kembali UNIQUE polos secara buta.

### Task 5: Hilangkan sumber kebenaran ganda status anggota Poktan

**Objective:** Menurunkan status keanggotaan dari `anggota_poktan` agar impor anggota tidak perlu menyinkronkan flag duplikat.

**Files:**
- Create migration yang menghapus `transmigran.status_anggota_poktan` setelah seluruh consumer dialihkan.
- Modify `app/Models/Transmigran.php`, `app/Enums/StatusAnggotaPoktan.php` bila sudah tanpa pemakai, controller, laporan, tampilan daftar/detail/form.
- Modify seeders/factories/dummy fixture.
- Modify `database/data/schema.sql` dan dokumen skema.
- Test Feature/Database yang saat ini membaca kolom tersebut.

**Rule:** Tampilan “Anggota kelompok tani” dihitung dari keanggotaan Aktif, bukan disalin menjadi kolom lain.

Turunkan status dengan relasi/`withExists` ke keanggotaan Aktif agar daftar tidak menimbulkan N+1. Task ini wajib selesai sebelum Task 9.

**Reuse:** Unique gate satu keanggotaan aktif dari `2026_09_06_000000_enforce_single_active_poktan_membership.php` sudah tersedia; jangan membuat constraint pengganti.

### Task 6: Pusatkan operasi domain minimum yang dipakai form dan impor

**Objective:** Menghindari salinan aturan bisnis di controller dan importer.

**Files likely:**
- Create support/service kecil hanya untuk operasi yang benar-benar digunakan dua jalur, misalnya `app/Support/OperasiRumah.php`, `OperasiPoktan.php`, `OperasiSaprotan.php`, `OperasiPenanaman.php`, `OperasiHasilPanen.php`.
- Modify controller terkait agar memanggil operasi yang sama.
- Modify `ImporEngine` untuk memanggilnya.

**Boundary:** Jangan membuat interface/factory/repository. Ekstrak hanya transaksi/validasi lintas tabel yang sekarang ada di controller dan akan dipakai importer.

**TDD:** Pindahkan satu domain per siklus sambil memastikan tes controller lama tetap hijau sebelum handler impor ditambahkan.

### Task 7: Perluas kontrak hasil ImporEngine untuk idempotensi dan kelompok

**Objective:** Mendukung dibuat/dilewati/gagal tanpa parser kedua.

**Files:**
- Modify `app/Support/ImporEngine.php`.
- Modify `app/Http/Controllers/ImporController.php` bila bentuk respons berubah.
- Modify `resources/views/components/sim/modal-impor.blade.php`.
- Test `tests/Database/ImporTest.php`, `tests/Feature/HalamanTest.php`.

**Result contract:**
- `diproses`
- `dibuat`
- `dilewati`
- `jumlah_gagal`
- `gagal[]` dengan baris atau rentang baris dan pesan tindakan
- `galat_dibatasi`

**Activation/RBAC boundary:** Pisahkan peta `entitas → modul/izin` untuk seluruh entitas template dari registry handler yang benar-benar aktif. Jangan memakai satu konstanta yang membuat penambahan pemetaan izin sekaligus mengaktifkan handler/tombol sebelum implementasinya lulus.

**Grouping:**
- Rumah/Lahan/Penanaman/Panen: unit satu baris.
- Poktan: kelompok seluruh baris dengan `nama_poktan` sama.
- Saprotan: kelompok seluruh baris dengan `kode_saprotan` sama.
- Profil induk yang diulang dalam kelompok wajib identik; konflik menggagalkan seluruh kelompok.

### Task 8: Aktifkan impor Rumah dan Lahan

**Objective:** Implementasikan dua tracer bullet pertama dengan efek lintas tabel atomik.

**Files:**
- Modify `app/Support/SkemaImpor.php`.
- Modify `app/Support/ImporEngine.php`.
- Modify modal calls di halaman Rumah/Lahan agar kolom wajib berasal dari skema, bukan daftar salinan.
- Test `tests/Database/ImporTest.php` dan domain tests.

**Rumah columns:** `no_rumah`, `satuan_permukiman`, `nik_penghuni`, `tahun_mulai_menghuni`, `kondisi`, `status_hunian`, `alasan_tidak_dihuni`, `tahun_pembangunan`, `luas_bangunan`, `lintang`, `bujur`, `catatan_hunian`.

**Lahan columns:** `kode_lahan`, `nik_pemilik`, `satuan_permukiman`, `luas_pekarangan`, `lintang_pekarangan`, `bujur_pekarangan`, `luas_kering`, `luas_basah`, `lintang_usaha`, `bujur_usaha`, `tujuan_pemanfaatan`, `status_sertifikat`, `keterangan`.

**Checks:** SP turunan wajib cocok dengan kolom tampilan; pasangan koordinat lengkap; minimal satu bidang; status sertifikat dan Lahan satu transaksi; replay identik dilewati; konflik ditolak.

### Task 9: Aktifkan impor Poktan lengkap

**Objective:** Membuat satu profil beserta seluruh anggota dalam satu transaksi kelompok.

**Files:** `SkemaImpor`, `ImporEngine`, operasi Poktan, modal/page Poktan, `ImporTest`, `PoktanTest`.

**Workbook:** Satu baris per anggota; kolom profil Poktan diulang. Baris anggota boleh kosong untuk Poktan tanpa anggota.

**Checks:** tiga jalur ketua; anggota keluarga harus berasal dari keluarga dan SP yang benar; satu keluarga tidak aktif di dua Poktan; tanggal/status keluar konsisten; izin `poktan.tambah` dan izin tambah anggota; satu anggota salah menggagalkan seluruh kelompok.

Gate wajib memeriksa `poktan.lihat+tambah` **dan** `anggota_poktan.tambah` sebelum kelompok pertama diproses. Kekurangan salah satu izin menghasilkan 403 tanpa menyimpan baris apa pun.

### Task 10: Aktifkan impor Saprotan lengkap

**Objective:** Membuat satu pengadaan dan seluruh distribusi dalam transaksi kelompok.

**Files:** `SkemaImpor`, `ImporEngine`, operasi Saprotan, modal/page Saprotan, `ImporTest`, `SaprotanTest`.

**Workbook:** Satu baris per Poktan penerima; kolom pengadaan diulang. Distribusi boleh kosong untuk pengadaan belum tersalur.

**Identity:** `kode_saprotan`; distribusi `(kode_saprotan, poktan_slug)`.

**Checks:** komoditas/varietas hanya untuk Benih; jumlah distribusi positif; satu distribusi agregat per Poktan; total tidak melebihi pengadaan; cakupan seluruh penerima; replay identik dilewati; satu distribusi salah menggagalkan kelompok.

### Task 11: Aktifkan impor Penanaman

**Objective:** Menggunakan distribusi benih yang tidak ambigu dan menjaga stok/lahan saat impor bersamaan.

**Files:** `SkemaImpor`, `ImporEngine`, operasi Penanaman, modal/page Penanaman, `ImporTest`, `PenanamanTest`.

**Columns:** `kode_penanaman`, `poktan_slug`, `kode_saprotan`, `periode_tanam`, `volume_benih`, `realisasi_tanam_ha`, `keterangan`.

**Transaction order:** kunci Saprotan → distribusi → Poktan → periksa cakupan → hitung stok → hitung lahan → create.

**Checks:** kode Saprotan harus menunjuk Benih; distribusi Poktan harus ada; komoditas diturunkan; replay identik dilewati; konflik kode ditolak; uji dua proses terhadap sisa stok/lahan.

### Task 12: Aktifkan impor Hasil Panen

**Objective:** Membuat satu panen aktif berdasarkan kode Penanaman dan mendukung pengganti setelah pembatalan.

**Files:** `SkemaImpor`, `ImporEngine`, operasi Panen, modal/page Panen, `ImporTest`, `HasilPanenTest`.

**Columns:** `kode_penanaman`, `periode_panen`, `realisasi_panen_ha`, `puso_ha`, `produktivitas`, `harga_jual`, `keterangan`.

**Checks:** periode; luas panen+puso; produktivitas; satuan snapshot; produksi dihitung; pencarian idempotensi hanya membaca panen Aktif; aktif identik dilewati; aktif berbeda konflik; hanya riwayat batal memungkinkan pengganti; data soft-deleted legacy tidak dipulihkan otomatis; unique gate status aktif tetap menjaga balapan dua pengganti.

### Task 13: Tambahkan petunjuk awam sebagai satu sumber

**Objective:** Menjelaskan urutan dan prasyarat di modal serta workbook tanpa duplikasi teks.

**Files:**
- Modify `app/Support/SkemaImpor.php` untuk menyediakan judul awam, prasyarat, urutan, dan kolom wajib.
- Modify `app/Http/Controllers/TemplateImporController.php` agar sheet `Petunjuk` memakai sumber itu.
- Modify `resources/views/components/sim/modal-impor.blade.php` agar modal memakai sumber sama.
- Hapus daftar `kolom-wajib` yang ditulis ulang pada tiap Blade setelah komponen membacanya dari skema.
- Test `tests/Feature/HalamanTest.php` dan template XLSX.

**Security:** Endpoint unduh template harus memeriksa login serta kewenangan `lihat+tambah` entitas secara dinamis, sama seperti `ImporController::unggah()`. Rute parameter `{entitas}` tidak cukup dilindungi satu izin statis, dan tombol tersembunyi bukan pengamanan. Tambahkan uji GET CSV dan XLSX untuk: tanpa izin, hanya lihat, hanya tambah, dan keduanya.

### Task 14: Tutup kebocoran izin/cakupan laporan dan audit status panen

**Objective:** Memastikan dokumen memakai izin laporan yang tepat, data SP lain tidak tertanam di HTML pengguna Per SP, dan panen batal tidak masuk penyajian aktif.

**Files inspected/likely modified:** `PenyajianPoktan.php`, `PenyajianSaprotan.php`, `PenyajianAlsintan.php`, `PenyajianPanen.php`, `LaporanData.php`, route laporan, tests cakupan.

**Rule:** Jangan mencabut `withoutGlobalScopes()` secara buta. Untuk setiap pemakaian, tentukan apakah laporan memang kawasan-lebar dan route-nya hanya untuk role berwenang, atau apakah scope pengguna wajib berlaku. Hak `{modul}.lihat` saat ini tidak membedakan cakupan Semua vs Per SP, sehingga metadata bertuliskan “seluruh kawasan” bukan otorisasi untuk melewati scope. Tambahkan tes aktor Per SP yang memeriksa data luar cakupan tidak ada dalam HTML/ekspor.

**Known hotspots from audit:**
- `LaporanData::transmigran()` dan helper SP memakai `withoutGlobalScopes()` untuk Transmigran/Rumah/Lahan;
- `PenyajianPoktan::daftar()/daftarAnggota()`;
- `PenyajianSaprotan::daftar()` beserta distribusi/Poktan/Penanaman;
- `PenyajianPanen::penanaman()/hasilPanen()`;
- penyaji Alsintan, rekap, dashboard, dan detail SP yang membaca sumber bersama tersebut.

Pisahkan API penyaji menjadi jalur **tercakup untuk halaman pengguna** dan jalur kawasan penuh hanya bila ada izin/cakupan eksplisit yang benar-benar membenarkannya. Jangan memperbaiki satu halaman sambil membiarkan data luar SP tetap tertanam di HTML atau file ekspor.

**Route authorization:** Rute umum `/laporan/{slug}/dokumen` tidak boleh hanya bergantung pada `dashboard,lihat`. Ambil izin dari `LaporanData::meta($slug)['izin']` dan tolak slug yang izinnya tidak dimiliki pengguna.

**Panen status:** Semua daftar/rekap/dashboard/laporan/harga rata-rata harus menyaring panen Aktif. Riwayat batal hanya tampil pada rincian Penanaman/Panen yang memang ditujukan untuk audit.

### Task 15: Uji akhir dan sinkronisasi dokumen

**Objective:** Menutup Task 10.4b dengan bukti end-to-end.

**Verification order:**
1. Tes terfokus tiap domain selama RED/GREEN.
2. `php artisan test tests/Database/ImporTest.php`.
3. Tes Database domain Rumah, Lahan, Poktan, Saprotan, Penanaman, HasilPanen.
4. Suite Feature penuh.
5. Suite Database penuh.
6. `vendor/bin/pint --test`.
7. `php artisan sim:banding-skema --lengkap` — harus NOL SELISIH.
8. `php artisan view:cache` lalu `php artisan view:clear`.
9. `composer validate --strict` dan audit dependency bila lockfile berubah.
10. `npm run build`.
11. `git diff --check` dan audit rahasia/debug.

Jalankan suite Database secara serial pada satu proses. Jangan membagi file Database ke beberapa proses yang sama-sama memakai `digitrans_test` dan `migrate:fresh`, karena itu merusak schema tes dan menghasilkan kegagalan palsu berupa tabel hilang, deadlock, atau “table definition has changed”.

**Docs after each verified task, not batched:** update `agents/tasklist.md`, `agents/notes.md`, `agents/session-notes.md` bila status keseluruhan berubah, serta `rules.md`/`data-dictionary.md`/`erd.md`/`ui-spec.md` sesuai perubahan kontrak.

Perbaiki klaim dokumentasi lama yang terbukti basi: komentar route template yang masih menyebut XLSX “menyusul”, status “belum di-commit” untuk `141902c`, klaim cakupan laporan, dan cakupan jaminan migration `_140000`.

---

## 5. Acceptance criteria

- Keenam tombol impor aktif hanya setelah handler dan tes masing-masing lulus.
- CSV dan XLSX menghasilkan perilaku sama.
- Replay berkas identik tidak membuat duplikat.
- Konflik identitas tidak mengubah data lama.
- Partial success berlaku antar-unit bisnis; satu unit bisnis tidak pernah tersimpan setengah.
- Seluruh relasi ditentukan oleh kode/NIK/slug stabil dan diverifikasi terhadap teks pendamping.
- Semua operasi mematuhi cakupan SP dan izin.
- Stok benih/lahan aman terhadap impor paralel.
- Riwayat hunian memakai tahun end-to-end tanpa tanggal buatan.
- Panen batal tetap terlihat tetapi tidak masuk rekap; pengganti aktif dapat dibuat.
- Modal dan XLSX menjelaskan urutan impor dalam bahasa formal yang mudah dipahami.
- Tidak ada file/foto yang diproses lewat spreadsheet.
- Skema, UI manual, importer, seeder, laporan, dokumentasi, dan tests menggunakan kontrak yang sama.

## 6. Deliberate exclusions

- Tidak mendukung `.xls`, `.xlsm`, `.xlsb`, formula, tautan eksternal, atau lebih dari 1.000 baris.
- Tidak memulihkan soft-deleted data melalui impor.
- Tidak mengimpor foto/dokumen.
- Tidak membuat parser kedua atau framework importer generik baru.
- Tidak memakai nama bebas atau ID database sebagai identitas lintas berkas bila kode stabil tersedia.
- Tidak melakukan commit/push tanpa permintaan eksplisit pengguna.
