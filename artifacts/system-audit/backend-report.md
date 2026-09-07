# Audit Backend, Keamanan, dan Integritas

HEAD: `29549bf0b0a2aed6a48fa587f77cec261dfec3bf`

## Ringkasan

Audit route → middleware/permission → controller/service → model/schema → test menemukan **6 High (termasuk satu bersyarat deployment), 11 Medium, dan 2 Low** pada backend. Browser dan integritas audit UI dibahas terpisah di `adversarial-report.md`.

## High

### SYS-H01 — Akun nonaktif tetap dapat memakai sesi aktif
`PengaturanPenggunaController.php:210-226` hanya mengubah `is_aktif`; tidak ada middleware active-user. `User::punyaIzin()` tidak memeriksa flag akun. Tambahkan boundary aktif-akun serta hapus sesi/rotasi remember token saat nonaktif.

### SYS-H02 — Mutasi akun/kredensial tidak mencabut seluruh sesi
Reset Admin (`PengaturanPenggunaController.php:184-207`), role/cakupan (`:140-161`), ganti mandiri (`ProfilController.php:88-106`), wajib-ganti, recovery (`PemulihanSandiController.php:164-185`), dan Artisan tidak memakai revocation shared. Reuse pola `PendingEmailChangeController.php:121-126`. Test minimal membuat dua sesi dan membuktikan keduanya invalid setelah setiap mutasi.

### SYS-H04 — Soft-delete induk menyembunyikan alur produksi aktif
`PoktanController.php:175-182` menghapus tanpa guard meski dependency check sudah ada di `:215-219`. `SaprotanController.php:149-159` juga tidak menolak distribusi/pemakaian aktif. Anak memakai `DisaringLewatInduk`, sehingga anggota/distribusi/penanaman/panen hilang dari laporan meski row tetap ada. Tolak penghapusan induk yang punya dependensi hidup; test poktan+penanaman+panen dan saprotan+distribusi.

### SYS-H05 — `rincian_kondisi` dibuang backend
Form `pages/sp/_rincian-kondisi.blade.php:48-56` mengirim histogram; `InventarisSpController.php:153-185` dan `FasilitasSpController.php:201-236` tidak memvalidasi/menyimpannya; importer juga absen. Simpan JSON dan enforce `Σ rincian = jumlah` pada satu helper validasi shared untuk manual+import.

### SYS-H06 — Transisi pengaduan rentan lost update
`PengaduanController.php:181-241` membaca/validasi status sebelum transaksi. Dua request dapat membuat dua riwayat dari status awal sama dan efek samping ganda. Pindahkan load+validasi transisi ke transaction dengan `lockForUpdate()`; satu request paralel harus ditolak.

### SYS-H07 — Trust proxy wildcard (High bersyarat deployment)
`bootstrap/app.php:61-66` mempercayai semua proxy; limiter dan audit memakai `$request->ip()`. Aman hanya bila origin tertutup dan proxy membersihkan header. Batasi CIDR proxy serta test direct-client forwarded spoof.

## Medium

### SYS-M01 — Dokumen SP lintas cakupan
`PetaModulBerkas.php:65,73-82` memeriksa `SatuanPermukiman::query()` biasa, padahal model memerlukan local scope `terlihatOlehPengguna()`. Operator SP A berpotensi mengunduh dokumen SP B. Tambahkan branch scope dan test 404 lintas SP.

### SYS-M02 — Tipe impor mempercayai ekstensi nama klien
`ImporController.php:39-56` mendahulukan client extension. Probe file teks bernama `.xlsx` menghasilkan client `xlsx`, server detection `txt`. Cocokkan MIME/magic sebelum parser; pertahankan hardening `ImporEngine`.

### SYS-M03 — File dan transaksi DB tidak atomik
`MenyimpanBerkas.php:89-112` menulis disk sebelum registry/pivot; rollback tidak menghapus file. Scheduler cleanup yang disebut model tidak ada. Tambahkan kompensasi shared pada exception; forced registry/attach failure harus meninggalkan nol file/row/pivot.

### SYS-M04 — Fresh setup/suite Database kehilangan master P1/P2
Run serial: 633 pass, 3 fail pada `SpTest`; nilai `Linear`, `Sedang`, `Bergelombang` tidak ter-seed. Tetapkan satu bootstrap master resmi dan jalankan dalam setup SP/fresh install.

### SYS-M05 — PHP workflow tidak cocok lock
Workflow PHP 8.2 bertentangan dengan `maennchen/zipstream-php 3.2.2` (`php-64bit ^8.3`). Selaraskan target runtime atau pin dependency; clean install/platform check wajib hijau.

### SYS-M08 — Race batas percobaan recovery
`PemulihanSandiController.php:148-164` memilih row, hash-check, dan increment tanpa lock; route verifikasi tak punya throttle khusus. Gunakan transaction+`lockForUpdate()` atau atomic conditional update dan test paralel maksimal lima pemeriksaan.

### SYS-M09 — Race deduplikasi notifikasi
`Notifikasi.php:62-69` melakukan `exists()` lalu `create()` tanpa unique gate. Gunakan mekanisme DB/locking yang mempertahankan satu unread notification per recipient/jenis/subjek; test dua koneksi.

### SYS-M10 — Nomor urut pengaduan race
`NomorPengaduan.php:50-60` memakai full-load+`max()+1`; suffix acak membuat nomor lengkap unik tetapi komponen urut dapat sama. Gunakan counter tahunan terkunci bila semantik “urut” wajib unik.

### SYS-M11 — Agregasi full-load berulang
`RekapDashboard.php:128-150` memanggil total/harga per tahun; `RekapPanen` memuat koleksi domain penuh. Tambahkan benchmark/query guard dulu; optimalkan hanya query terbukti panas.

### SYS-M12 — Queue mail tanpa worker produksi
Queue default database dan mail selalu queued, tetapi worker hanya ada di script development. Deployment produksi harus mendefinisikan supervised worker, retry/failed-job alert, dan smoke test delivery.

### SYS-M13 — Cookie Secure tidak dijamin deployment
`config/session.php` mengikuti env dan `.env.example` tidak mendokumentasikan production cookie. Production harus HTTPS + `SESSION_SECURE_COOKIE=true`; smoke test header cookie.

## Low

- **SYS-L01:** Pint menemukan 12 style issue.
- **SYS-L02:** route-map guard hanya menyapu write methods; CI preview tidak menjalankan tests/audits/schema parity dan action refs mutable.

## Medium tambahan

- **SYS-M18:** `LaporanData::anggotaAktifPerPoktan()` memakai static cache tanpa user/scope key; pada Octane/RoadRunner/worker persisten agregat dapat terbawa lintas pengguna.
- **SYS-M19:** endpoint recovery anonim tanpa throttle menjalankan bcrypt bahkan untuk akun tak dikenal.
- **SYS-M20:** exporter Excel menyimpan integer kuantitas polos sebagai teks.
- **SYS-M21:** Monografi enam SP terukur 3.137 query dan 8,74 detik per route.
- **SYS-M22:** bootstrap Admin memakai `env()` langsung sehingga tidak aman setelah config cache.
- **SYS-M23/M24:** dokumentasi deployment tidak cocok dengan preview manual dan belum ada deployment Laravel stateful.

## High tambahan

- **SYS-H09:** `pengguna.ubah` dapat dipakai delegated user manager untuk memberi role Admin terkunci.
- **SYS-H10:** produktivitas hasil panen non-ton salah dilabel ton/ha; runtime kilogram probe mengonfirmasi selisih 1000×.
- **SYS-H11:** backup/restore DB dan private documents diwajibkan tetapi belum tersedia.

## Deferred decision

Rules melarang PK berurutan pada URL, tetapi route/controller/view/notifikasi masih memakai integer walau beberapa model memiliki UUID route key. Migrasi ini benar namun luas; jadwalkan batch kompatibilitas UUID tersendiri dengan redirect/link update dan negative integer-route tests.

## Kontrol terkonfirmasi kuat

- Internal routes: auth + wajib-ganti + RBAC + limiter.
- Per-SP global scope/write guard gagal tertutup.
- Pending email change: token hash, expiry, lock, unique, revocation, audit.
- Import: 1.000 baris, size/ZIP limits, macro/link/embed/formula rejection, transaksi per row/group.
- Private documents umumnya memeriksa izin, registry ownership, traversal, dan owner visibility; pengecualian spesifik SP dicatat SYS-M01.
- Notification read ownership dan recipient filtering kuat.
- Schema parity `NOL SELISIH`; Composer/npm audit nol advisory saat audit.
