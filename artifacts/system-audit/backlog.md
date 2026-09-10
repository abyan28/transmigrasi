# Backlog Final Task 11.1e

Urutan mengikuti risiko dan shared boundary; tidak menambah dependency baru.

## Batch 1 — Account dan authorization boundary

### BL-01 (M) — Satu service revocation + middleware akun aktif
Sumber: SYS-H01, SYS-H02. Reuse revocation dari pending-email. Panggil pada nonaktif, reset Admin, ganti mandiri/wajib, recovery, Artisan, role/cakupan/SP assignment. Bungkus mutasi user/pivot/audit dalam transaksi (SYS-M14). Acceptance: dua sesi dan remember cookie lama ditolak pada seluruh mutasi; injected sync/audit failure me-rollback seluruh perubahan; reaktivasi tidak menghidupkan sesi lama.

### BL-01b (S) — Lock invariant dan assignment Admin
Sumber: SYS-H08, SYS-H09. Dalam transaction, lock seluruh user ber-role terkunci sebelum hitung+nonaktif/demote. Hanya Admin terkunci boleh memberi/mencabut role terkunci; tolak role nonaktif. Acceptance: dua permintaan silang paralel menyisakan minimal satu Admin aktif, Admin terakhir tidak dapat didemote, dan delegated user manager tidak dapat self-promote.

### BL-02 (S) — Scope dokumen SP
Sumber: SYS-M01. Branch `satuan_permukiman` di `PetaModulBerkas::pemilikTerlihat()` harus memakai `terlihatOlehPengguna()`. Acceptance: Operator SP A mendapat 200 untuk dokumen A, 404 untuk B.

### BL-03 (S, deployment-dependent) — Batasi trusted proxy dan secure cookie
Sumber: SYS-H07, SYS-M13. Set CIDR proxy nyata, tutup origin, dokumentasikan HTTPS + `SESSION_SECURE_COOKIE=true`. Acceptance: direct spoof tidak mengubah IP/bucket; production cookie Secure+HttpOnly+SameSite.

## Batch 2 — Integritas domain

### BL-04 (M) — Tolak soft-delete induk produksi yang masih dipakai
Sumber: SYS-H04. Reuse dependency checks yang sudah ada; cek sibling poktan dan saprotan. Acceptance: penghapusan induk kosong berhasil, induk dengan anggota/distribusi/penanaman/panen ditolak dan seluruh laporan tetap terlihat.

### BL-05 (M) — Simpan dan validasi `rincian_kondisi`
Sumber: SYS-H05. Satu helper shared manual+import untuk daftar kondisi dan invariant jumlah. Acceptance: histogram valid tersimpan; sum mismatch 422; update/import parity; penilaian memakai data baru.

### BL-06 (M) — Lock transisi pengaduan
Sumber: SYS-H06. Load pengaduan dengan `lockForUpdate()` di dalam transaction, lalu hitung ulang `sekarang/tujuan`; kirim efek samping hanya setelah commit sukses. Acceptance: dua transisi paralel menghasilkan satu riwayat sah dan satu penolakan.

### BL-07 (S) — Atomic request/attempt recovery + throttle
Sumber: SYS-M08, SYS-M19. Lock user+kode untuk request/consume, conditional update single-use, dan limiter per IP+kredensial untuk akun ada/tidak ada. Acceptance: request paralel tidak melewati tiga kode/jam atau menyisakan beberapa kode hidup; maksimum lima hash-check efektif; satu konsumsi sukses; flood anonim mendapat 429.

### BL-08 (M) — Kompensasi file orphan
Sumber: SYS-M03. Helper upload shared menghapus file saat registry/attach gagal. Acceptance: injected DB/pivot failure meninggalkan nol file/row/pivot.

### BL-09 (M) — Gate concurrency notifikasi, penilaian, dan nomor pengaduan
Sumber: SYS-M09, SYS-M10, SYS-M16, SYS-M17. Notifikasi/penilaian yang wajib konsisten masuk transaction/outbox idempoten; dedupe memakai invariant DB/lock; snapshot penilaian membandingkan skor/status/rincian, bukan status saja. Untuk nomor urut, buat counter tahunan terkunci hanya bila urut unik memang requirement. Acceptance: dua koneksi menghasilkan satu unread notification, satu snapshot per perubahan, respons tidak 500 setelah business commit, dan dua komponen urut berbeda.

## Batch 3 — Correctness laporan dan test gate

### BL-09b (S) — Konversi produktivitas laporan ke ton/ha
Sumber: SYS-H10. Reuse faktor `KonversiPanen`; konversi produktivitas memakai satuan yang sama dengan produksi. Acceptance: 1.282 kg/ha tampil 1,282 ton/ha dan subtotal tertimbang tetap `produksi_ton / luas`.

### BL-09c (S) — Ekspor integer kuantitas sebagai angka berdasarkan metadata kolom
Sumber: SYS-M20. Jangan mengubah semua digit polos karena NIK/telepon/tahun/kode harus teks. Tandai kolom numerik laporan atau gunakan atribut data pada sel/header. Acceptance: jumlah Alsintan `3` bertipe number di XLSX, NIK/tahun/kode tetap string.

### BL-10 (M) — Perbaiki browser harness
Sumber: SYS-H03, SYS-M06, SYS-L03. Reuse login dari `uji-export-laporan.mjs`; helper shared wajib assert URL final, heading/selector unik, console/Runtime exception, dan skip prasyarat sebagai nonzero. Daftarkan runner wajib di `package.json`. Acceptance: tanpa login/WebSocket semua test internal gagal precondition; dengan login mencapai target; identifier Alpine rusak memerahkan test.

### BL-11 (S, selesai) — Pulihkan master P1/P2 pada setup resmi
Sumber: SYS-M04. Satu seeder/bootstrap authoritative; jangan duplikasi literal. Acceptance: focused `SpTest` dan seluruh 636 Database tests lulus serial.

### BL-12 (S, selesai) — Selaraskan PHP workflow dan lock
Sumber: SYS-M05. Naikkan workflow ke ≥8.3 atau pin dependency kompatibel 8.2 setelah keputusan target. Acceptance: clean `composer install --no-dev` + platform check hijau.

### BL-13 (S, selesai) — Perluas quality gates
Sumber: SYS-L01, SYS-L02. Seluruh named auth route masuk penjaga; workflow umum menjalankan quality gates dan workflow MariaDB 10.11 memaksa `IzinPenegakanRuteTest` lulus tanpa skip. Seluruh GitHub Action dipin SHA.

## Batch 4 — Operasi dan evidence

### BL-14 (M) — Definisikan worker email produksi dan delivery failure policy
Sumber: SYS-M12, SYS-M15. Supervisor/systemd/container worker, restart policy, retry, failed-job alert, dan smoke test delivery. Untuk pending-email berkredensial sementara, jangan mencabut kredensial sebelum enqueue terjamin; bila enqueue gagal, rollback/cancel pending dan beri hasil actionable. Tanpa ini mail queued tidak menjamin terkirim dan akun dapat terkunci tanpa token.

### BL-15 (M, selesai) — Hilangkan cache lintas user dan perbaiki hot path laporan
Sumber: SYS-M11, SYS-M18, SYS-M21. Graf panen dashboard dimuat sekali untuk seluruh rentang dan widget request; static cache user-scoped dihapus. Monografi memakai ulang payload/filter dan dataset lintas-SP, membatch kependudukan, serta menghapus lookup satuan/sertifikat berulang. Fixture enam SP dijaga ≤220 query.

### BL-16 (M, selesai sebagian) — Audit visual ulang terikat HEAD
Sumber: SYS-M07. Capture dark-theme segar mencakup 8 permukaan berisiko pada desktop/mobile. Manifest mengikat SHA, URL akhir, heading, role, viewport, dimensi, path, overflow, dan hash; validator menolak file/hash/dimensi/identitas/duplikat yang tidak sah. Sisa: warna efektif/rasio kontras valid 1..21 serta cakupan theme/role tambahan.

## Batch keputusan terpisah

### DEC-01 (L) — Migrasi URL publik ke UUID
Aturan proyek menghendaki UUID tetapi perubahan menyentuh route, controller, Blade, redirects, notifications, dan compatibility. Buat rencana forward terpisah; acceptance: UUID diterima, integer public route ditolak/redirect sesuai kebijakan, seluruh link/download/notifikasi konsisten.

## Deferred operasional

### BL-17 (M) — Deployment stateful, config bootstrap, backup/restore, dan dokumentasi
Sumber: SYS-H11, SYS-M22, SYS-M23, SYS-M24. Buat runbook target hosting Laravel (PHP/DB/SSL/private storage/migrate/health), pindahkan `SIM_ADMIN_*` ke config agar aman setelah cache, selaraskan README dengan preview manual, dan jadwalkan backup terenkripsi DB+private files. Acceptance: clean deploy stateful; `/up`, login, CRUD, private upload/download, persistence restart; restore fresh environment dengan hash dokumen cocok.

Scheduler, monitoring, benchmark lanjutan, dan UAT tetap prasyarat sebelum label production-ready.
