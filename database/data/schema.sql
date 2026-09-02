-- =============================================================================
-- transmigrasi.sql
-- Skema Basis Data Final -- Sistem Informasi Digitalisasi Monitoring Pertanian
-- dan Tata Kelola Data Kawasan Transmigrasi Kobalima Timur
-- =============================================================================
--
-- DDL murni (tanpa data operasional). Disusun dari:
--   agents/erd.md, agents/data-dictionary.md, agents/rules.md, agents/notes.md
--   dengan cross-check terhadap frontend (app/Support/DummyData.php).
--
-- Prioritas sumber kebenaran saat konflik:
--   notes.md (keputusan terbaru) > rules.md > erd.md (relasi/nama)
--   > data-dictionary.md (atribut/tipe) > frontend > refs/ (historis).
--
-- Berkas refs/20260809_T10_22_39.349Z.sql adalah struktur lama 22 tabel dengan
-- arah FK terbalik: REFERENSI HISTORIS SAJA, bukan skema ini.
--
-- Isi: 44 tabel bisnis + 6 tabel infrastruktur framework Laravel = 50 tabel.
--
-- Konvensi:
--   - Nama tabel  : Bahasa Indonesia, lowercase, snake_case, tunggal.
--   - Primary key : id_<nama_tabel>, BIGINT UNSIGNED AUTO_INCREMENT.
--   - Foreign key : <tabel_rujukan>_id, BIGINT UNSIGNED.
--   - Boolean     : awalan is_ / kata sifat, TINYINT(1).
--   - Tanggal     : awalan tanggal_, DATE. Periode bulanan: CHAR(7) YYYY-MM. Tahun: YEAR.
--   - Koordinat   : dua kolom lintang & bujur, DECIMAL(10,7) (bukan GEOMETRY).
--   - Uang        : DECIMAL(15,2). Luas (ha): DECIMAL(12,2). Volume: DECIMAL(12,3).
--   - Dokumen/foto: VARCHAR(255) berisi path relatif storage/app/private/ (bukan BLOB).
--   - created_at/updated_at pada semua tabel; deleted_at hanya pada tabel data utama.
--   - ENGINE=InnoDB, CHARSET=utf8mb4, COLLATE=utf8mb4_unicode_ci.
--
-- Kolom bertanda "REF(jenis=...)" menyimpan TEKS yang nilainya dikelola Admin
-- lewat tabel referensi (data-dictionary.md 5.6); ditulis VARCHAR agar nilai
-- baru cukup INSERT ke referensi tanpa ALTER TABLE. Enum berperilaku tetap ENUM.
--
-- Invariant aritmetika (luas_kering+luas_basah=luas; realisasi_panen+puso=
-- realisasi_tanam; SUM(distribusi.jumlah)<=jumlah_total; produksi=realisasi_panen
-- x produktivitas) ditegakkan di lapisan aplikasi/derivasi, BUKAN CHECK constraint.
--
-- URUTAN CREATE TABLE: dependency order (parent sebelum child), berbasis erd.md 10
-- yang diperluas untuk tabel baru. Header domain di bawah mengikuti pengelompokan
-- fungsional; nomor domain dapat berbeda dari erd.md karena Lahan diletakkan
-- setelah Kelembagaan (lahan.poktan_id menaut ke poktan).
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `transmigrasi`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;
USE `transmigrasi`;

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;


-- #############################################################################
-- DOMAIN 1 : PENGGUNA & SISTEM
-- #############################################################################

-- 1.1 role --------------------------------------------------------------------
-- Role dinamis: dibuat & diatur Admin. 4 role bawaan ditanam seeder (di luar file ini).
CREATE TABLE `role` (
  `id_role`      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama`         VARCHAR(100) NOT NULL,
  `deskripsi`    VARCHAR(255) NULL,
  `cakupan_data` ENUM('Semua','Per SP','Per Bidang') NOT NULL,
  `is_bawaan`    TINYINT(1) NOT NULL DEFAULT 0,
  `is_terkunci`  TINYINT(1) NOT NULL DEFAULT 0,
  `is_aktif`     TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`   TIMESTAMP NULL DEFAULT NULL,
  `updated_at`   TIMESTAMP NULL DEFAULT NULL,
  `deleted_at`   TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_role`),
  UNIQUE KEY `uq_role_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.2 permission ------------------------------------------------------------
-- Kewenangan baku ditanam seeder; Admin tidak dapat menambah/menghapus.
CREATE TABLE `permission` (
  `id_permission` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama`          VARCHAR(100) NOT NULL,
  `modul`         VARCHAR(50) NOT NULL,
  `aksi`          ENUM('lihat','tambah','ubah','hapus') NOT NULL,
  `label`         VARCHAR(150) NOT NULL,
  `urutan`        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`    TIMESTAMP NULL DEFAULT NULL,
  `updated_at`    TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_permission`),
  UNIQUE KEY `uq_permission_nama` (`nama`),
  KEY `idx_permission_modul` (`modul`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.3 role_permission (pivot N:M) -----------------------------------------
CREATE TABLE `role_permission` (
  `id_role_permission` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id`            BIGINT UNSIGNED NOT NULL,
  `permission_id`      BIGINT UNSIGNED NOT NULL,
  `created_at`         TIMESTAMP NULL DEFAULT NULL,
  `updated_at`         TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_role_permission`),
  UNIQUE KEY `uq_role_permission` (`role_id`,`permission_id`),
  KEY `idx_role_permission_permission` (`permission_id`),
  CONSTRAINT `fk_role_permission_role`
    FOREIGN KEY (`role_id`) REFERENCES `role` (`id_role`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_role_permission_permission`
    FOREIGN KEY (`permission_id`) REFERENCES `permission` (`id_permission`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.4 user ----------------------------------------------------------------
-- Menggantikan tabel users bawaan Laravel. Seluruh pengguna adalah petugas
-- (warga tidak punya akun). Login: email ATAU username pada satu isian.
CREATE TABLE `user` (
  `id_user`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id`                BIGINT UNSIGNED NOT NULL,
  `nama`                   VARCHAR(255) NOT NULL,
  `username`               VARCHAR(50) NOT NULL,
  `email`                  VARCHAR(255) NOT NULL,
  `password`               VARCHAR(255) NOT NULL,
  `password_harus_diganti` TINYINT(1) NOT NULL DEFAULT 0,
  `telepon`                VARCHAR(20) NULL,
  `jabatan`                VARCHAR(100) NULL,
  `foto`                   VARCHAR(255) NULL,
  `is_aktif`               TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at`          TIMESTAMP NULL DEFAULT NULL,
  `remember_token`         VARCHAR(100) NULL,
  `created_at`             TIMESTAMP NULL DEFAULT NULL,
  `updated_at`             TIMESTAMP NULL DEFAULT NULL,
  `deleted_at`             TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `uq_user_username` (`username`),
  UNIQUE KEY `uq_user_email` (`email`),
  KEY `idx_user_role` (`role_id`),
  KEY `idx_user_is_aktif` (`is_aktif`),
  CONSTRAINT `fk_user_role`
    FOREIGN KEY (`role_id`) REFERENCES `role` (`id_role`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.5 kode_pemulihan_sandi -----------------------------------------------
-- Kode verifikasi 6 digit (disimpan sebagai sidik). Menggantikan password_reset_tokens.
CREATE TABLE `kode_pemulihan_sandi` (
  `id_kode_pemulihan` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`           BIGINT UNSIGNED NOT NULL,
  `kode_hash`         VARCHAR(255) NOT NULL,
  `kedaluwarsa_pada`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,   -- diisi aplikasi (created_at + 15 menit)
  `percobaan`         TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `dipakai_pada`      TIMESTAMP NULL DEFAULT NULL,
  `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,   -- dasar batas 3 permintaan/jam/akun
  PRIMARY KEY (`id_kode_pemulihan`),
  KEY `idx_kode_pemulihan_user` (`user_id`),
  KEY `idx_kode_pemulihan_kedaluwarsa` (`kedaluwarsa_pada`),
  KEY `idx_kode_pemulihan_created` (`created_at`),
  CONSTRAINT `fk_kode_pemulihan_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.6 audit_log ---------------------------------------------------------
-- Riwayat perubahan data penting. Hanya kolom yang berubah yang disimpan;
-- kolom password wajib dikecualikan. Tabel riwayat: tanpa soft delete.
CREATE TABLE `audit_log` (
  `id_audit_log` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      BIGINT UNSIGNED NULL,
  `aksi`         ENUM('Tambah','Ubah','Hapus','Pulihkan','Login','Logout','Reset Kata Sandi','Nonaktifkan Akun','Aktifkan Akun','Ubah Izin Role') NOT NULL,
  `nama_tabel`   VARCHAR(64) NOT NULL,
  `record_id`    BIGINT UNSIGNED NOT NULL,
  `data_lama`    JSON NULL,
  `data_baru`    JSON NULL,
  `ip_address`   VARCHAR(45) NULL,
  `user_agent`   VARCHAR(255) NULL,
  `created_at`   TIMESTAMP NULL DEFAULT NULL,
  `updated_at`   TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_audit_log`),
  KEY `idx_audit_log_user` (`user_id`),
  KEY `idx_audit_log_tabel` (`nama_tabel`),
  KEY `idx_audit_log_created` (`created_at`),
  CONSTRAINT `fk_audit_log_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- #############################################################################
-- DOMAIN 2 : MASTER WILAYAH & SATUAN PERMUKIMAN
-- Hierarki bercabang dua dari kabupaten:
--   administratif : provinsi -> kabupaten -> kecamatan -> desa
--   program       : kabupaten -> kawasan_transmigrasi
-- Keduanya bertemu di satuan_permukiman (desa_id + kawasan_id, keduanya WAJIB).
-- Tabel referensi wilayah: tanpa soft delete (dilindungi RESTRICT).
-- #############################################################################

-- 2.1 provinsi --------------------------------------------------------------
CREATE TABLE `provinsi` (
  `id_provinsi` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama`        VARCHAR(100) NOT NULL,
  `kode`        VARCHAR(10) NULL,
  `created_at`  TIMESTAMP NULL DEFAULT NULL,
  `updated_at`  TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_provinsi`),
  UNIQUE KEY `uq_provinsi_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.2 kabupaten -----------------------------------------------------------
CREATE TABLE `kabupaten` (
  `id_kabupaten` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provinsi_id`  BIGINT UNSIGNED NOT NULL,
  `nama`         VARCHAR(100) NOT NULL,
  `kode`         VARCHAR(10) NULL,
  `created_at`   TIMESTAMP NULL DEFAULT NULL,
  `updated_at`   TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_kabupaten`),
  UNIQUE KEY `uq_kabupaten_provinsi_nama` (`provinsi_id`,`nama`),
  CONSTRAINT `fk_kabupaten_provinsi`
    FOREIGN KEY (`provinsi_id`) REFERENCES `provinsi` (`id_provinsi`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.3 kecamatan ---------------------------------------------------------
CREATE TABLE `kecamatan` (
  `id_kecamatan` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kabupaten_id` BIGINT UNSIGNED NOT NULL,
  `nama`         VARCHAR(100) NOT NULL,
  `kode`         VARCHAR(10) NULL,
  `created_at`   TIMESTAMP NULL DEFAULT NULL,
  `updated_at`   TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_kecamatan`),
  UNIQUE KEY `uq_kecamatan_kabupaten_nama` (`kabupaten_id`,`nama`),
  CONSTRAINT `fk_kecamatan_kabupaten`
    FOREIGN KEY (`kabupaten_id`) REFERENCES `kabupaten` (`id_kabupaten`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.4 desa ------------------------------------------------------------
CREATE TABLE `desa` (
  `id_desa`      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kecamatan_id` BIGINT UNSIGNED NOT NULL,
  `nama`         VARCHAR(100) NOT NULL,
  `kode`         VARCHAR(10) NULL,
  `created_at`   TIMESTAMP NULL DEFAULT NULL,
  `updated_at`   TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_desa`),
  UNIQUE KEY `uq_desa_kecamatan_nama` (`kecamatan_id`,`nama`),
  CONSTRAINT `fk_desa_kecamatan`
    FOREIGN KEY (`kecamatan_id`) REFERENCES `kecamatan` (`id_kecamatan`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.5 kawasan_transmigrasi -------------------------------------------
-- Cabang program; dikelola pengguna sehingga memakai soft delete.
CREATE TABLE `kawasan_transmigrasi` (
  `id_kawasan_transmigrasi` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kabupaten_id`            BIGINT UNSIGNED NOT NULL,
  `nama`                    VARCHAR(150) NOT NULL,
  `slug`                    VARCHAR(120) NOT NULL,
  `kode_kawasan`            VARCHAR(20) NULL,
  `tahun_penetapan`         YEAR NULL,
  `nomor_sk`                VARCHAR(100) NULL,
  `luas_total`              DECIMAL(12,2) NULL,
  `dokumen_pendukung`       VARCHAR(255) NULL,
  `keterangan`              TEXT NULL,
  `created_at`              TIMESTAMP NULL DEFAULT NULL,
  `updated_at`              TIMESTAMP NULL DEFAULT NULL,
  `deleted_at`              TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_kawasan_transmigrasi`),
  UNIQUE KEY `uq_kawasan_slug` (`slug`),
  UNIQUE KEY `uq_kawasan_kabupaten_nama` (`kabupaten_id`,`nama`),
  KEY `idx_kawasan_kabupaten` (`kabupaten_id`),
  CONSTRAINT `fk_kawasan_kabupaten`
    FOREIGN KEY (`kabupaten_id`) REFERENCES `kabupaten` (`id_kabupaten`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.6 satuan_permukiman --------------------------------------------
-- Lokus utama sistem. Titik temu cabang administratif (desa_id) & program (kawasan_id);
-- keduanya WAJIB. kecamatan_id sengaja TIDAK ada (dibaca via desa_id -> desa -> kecamatan).
-- jumlah_kk_terisi TIDAK disimpan (dihitung dari transmigran).
-- Blok "Keadaan Wilayah" (Bab II Laporan Monografi, Rombongan C 2026-08-28): dokumenter,
-- seluruhnya NULL-able, tidak dipakai perhitungan. Rentang disimpan sebagai pasangan min/maks.
CREATE TABLE `satuan_permukiman` (
  `id_satuan_permukiman`     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kawasan_id`               BIGINT UNSIGNED NOT NULL,
  `desa_id`                  BIGINT UNSIGNED NOT NULL,
  `user_id`                  BIGINT UNSIGNED NULL,
  `nama`                     VARCHAR(150) NOT NULL,
  `slug`                     VARCHAR(120) NOT NULL,
  `kode_sp`                  VARCHAR(20) NULL,
  `tahun_penempatan`         YEAR NULL,
  `luas_lahan`               DECIMAL(12,2) NULL,
  `jumlah_kk_rencana`        INT UNSIGNED NULL,
  `lintang`                  DECIMAL(10,7) NULL,
  `bujur`                    DECIMAL(10,7) NULL,
  `dokumen_pendukung`        VARCHAR(255) NULL,
  `keterangan`               TEXT NULL,
  -- Keadaan Wilayah -- letak astronomis
  `lintang_utara`            DECIMAL(10,7) NULL,
  `lintang_selatan`          DECIMAL(10,7) NULL,
  `bujur_barat`              DECIMAL(10,7) NULL,
  `bujur_timur`              DECIMAL(10,7) NULL,
  -- Keadaan Wilayah -- letak ekonomis
  `jarak_ke_kecamatan_km`    DECIMAL(6,1) NULL,
  `jarak_ke_kabupaten_km`    DECIMAL(6,1) NULL,
  `jarak_ke_provinsi_km`     DECIMAL(6,1) NULL,
  -- Keadaan Wilayah -- batas alam (dihidupkan kembali 2026-08-28)
  `batas_utara`              VARCHAR(150) NULL,
  `batas_timur`              VARCHAR(150) NULL,
  `batas_selatan`            VARCHAR(150) NULL,
  `batas_barat`              VARCHAR(150) NULL,
  -- Keadaan Wilayah -- SK pencadangan
  `nomor_sk_pencadangan`     VARCHAR(100) NULL,
  `tanggal_sk_pencadangan`   DATE NULL,
  -- Keadaan Wilayah -- pola permukiman, tanah, topografi
  `pola_permukiman`          ENUM('Konsentris','Papan Catur','Linear','Menyebar') NULL,
  `tingkat_kesuburan_tanah`  ENUM('Subur','Sedang','Kurang Subur') NULL,
  `ph_tanah_min`             DECIMAL(4,2) NULL,
  `ph_tanah_maks`            DECIMAL(4,2) NULL,
  `bentuk_wilayah`           ENUM('Datar','Bergelombang','Berbukit','Bergunung') NULL,
  `kemiringan_min_persen`    DECIMAL(5,2) NULL,
  `kemiringan_maks_persen`   DECIMAL(5,2) NULL,
  -- Keadaan Wilayah -- iklim
  `curah_hujan_tahunan_mm`     DECIMAL(8,2) NULL,
  `curah_hujan_bulan_min_mm`   DECIMAL(7,2) NULL,
  `curah_hujan_bulan_maks_mm`  DECIMAL(7,2) NULL,
  `suhu_min_c`               DECIMAL(4,1) NULL,
  `suhu_maks_c`              DECIMAL(4,1) NULL,
  `suhu_rata_c`              DECIMAL(4,1) NULL,
  `angin_min_knot`           DECIMAL(4,1) NULL,
  `angin_maks_knot`          DECIMAL(4,1) NULL,
  `angin_rata_knot`          DECIMAL(4,1) NULL,
  `penyinaran_min_persen`    DECIMAL(5,2) NULL,
  `penyinaran_maks_persen`   DECIMAL(5,2) NULL,
  `penyinaran_rata_persen`   DECIMAL(5,2) NULL,
  -- Keadaan Wilayah -- sumberdaya air
  `sumber_air_bersih`        VARCHAR(255) NULL,
  `sumber_air_pertanian`     VARCHAR(255) NULL,
  `created_at`               TIMESTAMP NULL DEFAULT NULL,
  `updated_at`               TIMESTAMP NULL DEFAULT NULL,
  `deleted_at`               TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_satuan_permukiman`),
  UNIQUE KEY `uq_sp_nama` (`nama`),
  UNIQUE KEY `uq_sp_slug` (`slug`),
  UNIQUE KEY `uq_sp_kode` (`kode_sp`),
  KEY `idx_sp_kawasan` (`kawasan_id`),
  KEY `idx_sp_desa` (`desa_id`),
  KEY `idx_sp_user` (`user_id`),
  CONSTRAINT `fk_sp_kawasan`
    FOREIGN KEY (`kawasan_id`) REFERENCES `kawasan_transmigrasi` (`id_kawasan_transmigrasi`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_sp_desa`
    FOREIGN KEY (`desa_id`) REFERENCES `desa` (`id_desa`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_sp_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.7 user_satuan_permukiman (pivot N:M) -------------------------
-- Penugasan SP; hanya bermakna bagi role bercakupan 'Per SP'.
CREATE TABLE `user_satuan_permukiman` (
  `id_user_satuan_permukiman` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`                   BIGINT UNSIGNED NOT NULL,
  `satuan_permukiman_id`      BIGINT UNSIGNED NOT NULL,
  `created_at`                TIMESTAMP NULL DEFAULT NULL,
  `updated_at`                TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_user_satuan_permukiman`),
  UNIQUE KEY `uq_user_sp` (`user_id`,`satuan_permukiman_id`),
  KEY `idx_user_sp_sp` (`satuan_permukiman_id`),
  CONSTRAINT `fk_user_sp_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_sp_sp`
    FOREIGN KEY (`satuan_permukiman_id`) REFERENCES `satuan_permukiman` (`id_satuan_permukiman`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.8 rute_aksesibilitas_sp -------------------------------------
-- Rute pencapaian ke satu SP (Tabel 2.1 Monografi). 1:N; tanpa tabel riwayat.
CREATE TABLE `rute_aksesibilitas_sp` (
  `id_rute_aksesibilitas_sp` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `satuan_permukiman_id`     BIGINT UNSIGNED NOT NULL,
  `rute`                     VARCHAR(255) NOT NULL,
  `jarak_km`                 DECIMAL(7,1) NULL,
  `sarana_angkutan`          VARCHAR(150) NULL,
  `tempat_pemberangkatan`    VARCHAR(150) NULL,
  `kondisi_jalan`            VARCHAR(150) NULL,
  `waktu_tempuh`             VARCHAR(80) NULL,
  `ongkos_rp`                DECIMAL(12,2) NULL,
  `keterangan`               VARCHAR(255) NULL,
  `created_at`               TIMESTAMP NULL DEFAULT NULL,
  `updated_at`               TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_rute_aksesibilitas_sp`),
  KEY `idx_rute_sp` (`satuan_permukiman_id`),
  CONSTRAINT `fk_rute_sp`
    FOREIGN KEY (`satuan_permukiman_id`) REFERENCES `satuan_permukiman` (`id_satuan_permukiman`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- #############################################################################
-- DOMAIN 3 : ASET SP
-- #############################################################################

-- 3.1 inventaris_sp ------------------------------------------------------
-- Barang bergerak milik SP. kondisi = penilaian umum petugas (bukan turunan
-- rincian_kondisi). rincian_kondisi JSON: peta kondisi->jumlah unit, SUM = jumlah.
CREATE TABLE `inventaris_sp` (
  `id_inventaris_sp`     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `satuan_permukiman_id` BIGINT UNSIGNED NOT NULL,
  `jenis_inventaris`     VARCHAR(100) NOT NULL,          -- REF(jenis=jenis_inventaris)
  `nama_barang`          VARCHAR(255) NOT NULL,
  `jumlah`               INT UNSIGNED NOT NULL DEFAULT 1,
  `satuan_barang`        VARCHAR(50) NULL,               -- teks bebas; BUKAN FK ke satuan
  `tahun_perolehan`      YEAR NULL,
  `sumber_dana`          VARCHAR(50) NULL,               -- REF(jenis=sumber_dana)
  `status_penyerahan`    VARCHAR(30) NOT NULL,           -- REF(jenis=status_penyerahan)
  `kondisi`              VARCHAR(20) NULL,               -- REF(jenis=kondisi)
  `rincian_kondisi`      JSON NULL,
  `foto`                 VARCHAR(255) NULL,
  `dokumen_pendukung`    VARCHAR(255) NULL,
  `keterangan`           TEXT NULL,
  `created_at`           TIMESTAMP NULL DEFAULT NULL,
  `updated_at`           TIMESTAMP NULL DEFAULT NULL,
  `deleted_at`           TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_inventaris_sp`),
  KEY `idx_inventaris_sp_sp` (`satuan_permukiman_id`),
  KEY `idx_inventaris_sp_jenis` (`jenis_inventaris`),
  CONSTRAINT `fk_inventaris_sp_sp`
    FOREIGN KEY (`satuan_permukiman_id`) REFERENCES `satuan_permukiman` (`id_satuan_permukiman`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3.2 fasilitas_sp ---------------------------------------------------
-- Bangunan/fasilitas tetap milik SP. satuan_permukiman_id = lokasi/pangkal.
-- jenis_fasilitas tetap dipakai penilaian kondisi SP -> ENUM (bukan teks bebas).
CREATE TABLE `fasilitas_sp` (
  `id_fasilitas_sp`      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `satuan_permukiman_id` BIGINT UNSIGNED NOT NULL,
  `jenis_fasilitas`      ENUM('Kesehatan','Pendidikan Dasar','Pendidikan Lanjutan','Ibadah','Balai Pertemuan','Pasar atau Kios','Olahraga','Keamanan','Lainnya') NOT NULL,
  `nama_fasilitas`       VARCHAR(255) NOT NULL,
  `jumlah`               INT UNSIGNED NOT NULL DEFAULT 1,
  `tahun_perolehan`      YEAR NULL,
  `sumber_dana`          VARCHAR(50) NULL,               -- REF(jenis=sumber_dana)
  `status_penyerahan`    VARCHAR(30) NOT NULL,           -- REF(jenis=status_penyerahan)
  `kondisi`              VARCHAR(20) NULL,               -- REF(jenis=kondisi)
  `rincian_kondisi`      JSON NULL,
  `lintang`              DECIMAL(10,7) NULL,
  `bujur`                DECIMAL(10,7) NULL,
  `foto`                 VARCHAR(255) NULL,
  `dokumen_pendukung`    VARCHAR(255) NULL,
  `keterangan`           TEXT NULL,
  `created_at`           TIMESTAMP NULL DEFAULT NULL,
  `updated_at`           TIMESTAMP NULL DEFAULT NULL,
  `deleted_at`           TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_fasilitas_sp`),
  KEY `idx_fasilitas_sp_sp` (`satuan_permukiman_id`),
  KEY `idx_fasilitas_sp_jenis` (`jenis_fasilitas`),
  CONSTRAINT `fk_fasilitas_sp_sp`
    FOREIGN KEY (`satuan_permukiman_id`) REFERENCES `satuan_permukiman` (`id_satuan_permukiman`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3.3 fasilitas_sp_cakupan (pivot) --------------------------------
-- SP yang dilayani sebuah fasilitas (Putaran 7). WAJIB memuat SP pangkal.
CREATE TABLE `fasilitas_sp_cakupan` (
  `id_fasilitas_sp_cakupan` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fasilitas_sp_id`         BIGINT UNSIGNED NOT NULL,
  `satuan_permukiman_id`    BIGINT UNSIGNED NOT NULL,
  `created_at`              TIMESTAMP NULL DEFAULT NULL,
  `updated_at`              TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_fasilitas_sp_cakupan`),
  UNIQUE KEY `uq_fasilitas_cakupan` (`fasilitas_sp_id`,`satuan_permukiman_id`),
  KEY `idx_fasilitas_cakupan_sp` (`satuan_permukiman_id`),
  CONSTRAINT `fk_fasilitas_cakupan_fasilitas`
    FOREIGN KEY (`fasilitas_sp_id`) REFERENCES `fasilitas_sp` (`id_fasilitas_sp`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fasilitas_cakupan_sp`
    FOREIGN KEY (`satuan_permukiman_id`) REFERENCES `satuan_permukiman` (`id_satuan_permukiman`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- #############################################################################
-- DOMAIN 4 : MASTER REFERENSI & PENILAIAN KONDISI SP
-- #############################################################################

-- 4.1 satuan --------------------------------------------------------
-- Satuan berat + faktor konversi ke ton. Referensi: tanpa soft delete.
CREATE TABLE `satuan` (
  `id_satuan`     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,   -- kamus data menyebut INTEGER UNSIGNED; diseragamkan BIGINT UNSIGNED
  `nama`          VARCHAR(50) NOT NULL,                        -- Ton, Kuintal, Kilogram
  `simbol`        VARCHAR(10) NOT NULL,                        -- t, kw, kg
  `faktor_ke_ton` DECIMAL(10,6) NOT NULL,                      -- 1 / 0.1 / 0.001
  `created_at`    TIMESTAMP NULL DEFAULT NULL,
  `updated_at`    TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_satuan`),
  UNIQUE KEY `uq_satuan_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4.2 komoditas ---------------------------------------------------
CREATE TABLE `komoditas` (
  `id_komoditas` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `satuan_id`    BIGINT UNSIGNED NOT NULL,
  `nama`         VARCHAR(100) NOT NULL,
  `slug`         VARCHAR(120) NOT NULL,
  `tipe`         VARCHAR(20) NOT NULL,               -- REF(jenis=tipe_komoditas): Pangan / Palawija / Hortikultura
  `is_unggulan`  TINYINT(1) NOT NULL DEFAULT 0,
  `deskripsi`    TEXT NULL,
  `created_at`   TIMESTAMP NULL DEFAULT NULL,
  `updated_at`   TIMESTAMP NULL DEFAULT NULL,
  `deleted_at`   TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_komoditas`),
  UNIQUE KEY `uq_komoditas_nama` (`nama`),
  UNIQUE KEY `uq_komoditas_slug` (`slug`),
  KEY `idx_komoditas_tipe` (`tipe`),
  KEY `idx_komoditas_unggulan` (`is_unggulan`),
  KEY `idx_komoditas_satuan` (`satuan_id`),
  CONSTRAINT `fk_komoditas_satuan`
    FOREIGN KEY (`satuan_id`) REFERENCES `satuan` (`id_satuan`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4.3 referensi -------------------------------------------------
-- Daftar pilihan yang dikelola Admin. Kolom pemakainya menyimpan TEKS nilai
-- (bukan id) -- kecuali parameter_penilaian_sp.referensi_id yang menunjuk id.
-- Nilai DINONAKTIFKAN (is_aktif=0), tidak pernah dihapus.
-- bidang_id: self-FK, hanya untuk jenis 'kategori_pengaduan' (menunjuk baris jenis
-- 'bidang_pengaduan'); NULL bermakna "bidang ditetapkan petugas per laporan".
CREATE TABLE `referensi` (
  `id_referensi` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `jenis`        ENUM('sumber_dana','status_penyerahan','kondisi','kondisi_rumah','status_hunian','tipe_komoditas','prioritas_pengaduan','jenis_dokumen_lahan','jabatan_anggota_poktan','jenis_infrastruktur','jenis_fasilitas','bidang_pengaduan','kategori_pengaduan','jenis_alsintan','jenis_inventaris') NOT NULL,
  `nilai`        VARCHAR(100) NOT NULL,
  `urutan`       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `nilai_skor`   DECIMAL(3,2) NULL,                  -- hanya jenis 'kondisi'
  `bidang_id`    BIGINT UNSIGNED NULL,               -- hanya jenis 'kategori_pengaduan'
  `is_aktif`     TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`   TIMESTAMP NULL DEFAULT NULL,
  `updated_at`   TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_referensi`),
  UNIQUE KEY `uq_referensi_jenis_nilai` (`jenis`,`nilai`),
  KEY `idx_referensi_jenis` (`jenis`),
  KEY `idx_referensi_aktif` (`is_aktif`),
  KEY `idx_referensi_bidang` (`bidang_id`),
  CONSTRAINT `fk_referensi_bidang`
    FOREIGN KEY (`bidang_id`) REFERENCES `referensi` (`id_referensi`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4.4 parameter_penilaian_sp ---------------------------------
-- Parameter penilaian kondisi SP + bobotnya (data, bukan konstanta kode).
-- Baris dihasilkan dari jenis infrastruktur/fasilitas; dinonaktifkan, bukan dihapus.
-- referensi_id menunjuk baris referensi (jenis jenis_infrastruktur / jenis_fasilitas).
CREATE TABLE `parameter_penilaian_sp` (
  `id_parameter_penilaian_sp` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kode`         VARCHAR(50) NOT NULL,
  `nama`         VARCHAR(100) NOT NULL,
  `tingkat`      ENUM('Primer','Sekunder','Tersier') NOT NULL,
  `bobot`        TINYINT UNSIGNED NOT NULL,
  `sumber`       ENUM('Infrastruktur','Fasilitas') NOT NULL,
  `referensi_id` BIGINT UNSIGNED NOT NULL,
  `is_dinilai`   TINYINT(1) NOT NULL DEFAULT 0,
  `urutan`       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`   TIMESTAMP NULL DEFAULT NULL,
  `updated_at`   TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_parameter_penilaian_sp`),
  UNIQUE KEY `uq_parameter_penilaian_kode` (`kode`),
  KEY `idx_parameter_penilaian_tingkat` (`tingkat`),
  KEY `idx_parameter_penilaian_referensi` (`referensi_id`),
  CONSTRAINT `fk_parameter_penilaian_referensi`
    FOREIGN KEY (`referensi_id`) REFERENCES `referensi` (`id_referensi`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4.5 status_kondisi_sp ------------------------------------
-- Ambang & wording predikat kondisi SP, disunting dinas lewat /master/penilaian-kondisi.
-- CATATAN: struktur diturunkan dari frontend (DummyData::statusKondisiSp()); belum
-- dimuat pada erd.md / data-dictionary.md. Jumlah baris tetap 3; kode = kunci enum
-- perilaku (StatusKondisiSp::dariSkor); yang disunting hanya nama/keterangan/ambang.
CREATE TABLE `status_kondisi_sp` (
  `id_status_kondisi_sp` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kode`         VARCHAR(30) NOT NULL,               -- Mandiri / Berkembang / Perlu Penanganan
  `nama`         VARCHAR(50) NOT NULL,
  `keterangan`   VARCHAR(255) NULL,
  `ambang_bawah` DECIMAL(5,2) NOT NULL,              -- ambang wajib menurun; terendah terkunci 0
  `warna`        VARCHAR(20) NOT NULL,               -- success / warning / error (tidak disunting)
  `urutan`       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`   TIMESTAMP NULL DEFAULT NULL,
  `updated_at`   TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_status_kondisi_sp`),
  UNIQUE KEY `uq_status_kondisi_sp_kode` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4.6 penilaian_sp ------------------------------------
-- Riwayat penilaian kondisi SP. Satu SP banyak baris; tidak pernah dihitung ulang
-- diam-diam. rincian = salinan bobot/kondisi/nilai yang berlaku saat penilaian dibuat.
-- Tabel riwayat: tanpa soft delete.
CREATE TABLE `penilaian_sp` (
  `id_penilaian_sp`      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `satuan_permukiman_id` BIGINT UNSIGNED NOT NULL,
  `tanggal_penilaian`    DATE NOT NULL,
  `skor`                 DECIMAL(5,2) NOT NULL,       -- 0..100
  `status`               ENUM('Mandiri','Berkembang','Perlu Penanganan') NOT NULL,
  `ada_primer_nol`       TINYINT(1) NOT NULL,
  `rincian`              JSON NOT NULL,
  `user_id`              BIGINT UNSIGNED NULL,        -- NULL bila dihitung sistem
  `catatan`              TEXT NULL,
  `created_at`           TIMESTAMP NULL DEFAULT NULL,
  `updated_at`           TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_penilaian_sp`),
  KEY `idx_penilaian_sp_sp` (`satuan_permukiman_id`),
  KEY `idx_penilaian_sp_tanggal` (`tanggal_penilaian`),
  KEY `idx_penilaian_sp_status` (`status`),
  KEY `idx_penilaian_sp_user` (`user_id`),
  CONSTRAINT `fk_penilaian_sp_sp`
    FOREIGN KEY (`satuan_permukiman_id`) REFERENCES `satuan_permukiman` (`id_satuan_permukiman`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_penilaian_sp_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- #############################################################################
-- DOMAIN 5 : KEPENDUDUKAN
-- #############################################################################

-- 5.1 transmigran ---------------------------------------
-- Satu baris = satu kepala keluarga / KK. usia & jumlah_anggota_keluarga TIDAK
-- disimpan (diturunkan). status_anggota_poktan = penanda cepat (kebenaran di anggota_poktan).
-- daerah_asal_kabupaten_id (2026-09-02): semula VARCHAR(255) teks bebas tanpa indeks,
-- padahal menjadi salah satu dari enam dasar rekap kependudukan (rules.md 10a.4a).
-- Teks bebas memecah satu kabupaten menjadi beberapa baris rekap karena beda ejaan,
-- dan totalnya tetap benar sehingga kebocorannya tidak pernah terlihat. RESTRICT:
-- merapikan data master tidak boleh melenyapkan daerah asal seorang transmigran.
-- pekerjaan_kepala_keluarga SENGAJA tetap teks bebas: himpunannya terbuka (lihat bawah).
CREATE TABLE `transmigran` (
  `id_transmigran`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`                       CHAR(36) NOT NULL,
  `satuan_permukiman_id`       BIGINT UNSIGNED NOT NULL,
  `nik`                        CHAR(16) NOT NULL,
  `no_kk`                      CHAR(16) NOT NULL,
  `nama_kepala_keluarga`       VARCHAR(255) NOT NULL,
  `jenis_kelamin`              ENUM('Laki-laki','Perempuan') NULL,
  `agama`                      ENUM('Islam','Kristen','Katolik','Hindu','Buddha','Konghucu') NULL,
  `tempat_lahir`               VARCHAR(100) NULL,
  `tanggal_lahir`              DATE NULL,
  `pendidikan_terakhir`        ENUM('Tidak Sekolah','SD','SMP','SMA/SMK','Diploma','S1','S2','S3') NULL,
  `pekerjaan_kepala_keluarga`  VARCHAR(100) NOT NULL,    -- teks bebas (datalist), bukan enum
  `pendapatan_per_bulan`       DECIMAL(15,2) NULL,
  `daerah_asal_kabupaten_id`   BIGINT UNSIGNED NULL,     -- kabupaten/kota asal; NULL bila belum terdata
  `tahun_kedatangan`           YEAR NOT NULL,
  `status_tinggal`             ENUM('Aktif','Pindah Penduduk','Tidak Aktif') NOT NULL,
  `status_anggota_poktan`      ENUM('Ya','Tidak') NOT NULL,
  `telepon`                    VARCHAR(20) NULL,
  `dokumen_pendukung`          VARCHAR(255) NULL,
  `keterangan`                 TEXT NULL,
  `created_at`                 TIMESTAMP NULL DEFAULT NULL,
  `updated_at`                 TIMESTAMP NULL DEFAULT NULL,
  `deleted_at`                 TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_transmigran`),
  UNIQUE KEY `uq_transmigran_uuid` (`uuid`),
  UNIQUE KEY `uq_transmigran_nik` (`nik`),
  UNIQUE KEY `uq_transmigran_no_kk` (`no_kk`),
  KEY `idx_transmigran_sp` (`satuan_permukiman_id`),
  KEY `idx_transmigran_nama` (`nama_kepala_keluarga`),
  KEY `idx_transmigran_tahun_kedatangan` (`tahun_kedatangan`),
  KEY `idx_transmigran_pekerjaan` (`pekerjaan_kepala_keluarga`),
  KEY `idx_transmigran_daerah_asal` (`daerah_asal_kabupaten_id`),
  CONSTRAINT `fk_transmigran_sp`
    FOREIGN KEY (`satuan_permukiman_id`) REFERENCES `satuan_permukiman` (`id_satuan_permukiman`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_transmigran_daerah_asal`
    FOREIGN KEY (`daerah_asal_kabupaten_id`) REFERENCES `kabupaten` (`id_kabupaten`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.2 anggota_keluarga -----------------------------
-- Satu baris per anggota keluarga SELAIN kepala keluarga. Mutasi (meninggal/pindah)
-- DITANDAI, tidak dihapus (kecuali saat suksesi: pengganti "naik" ke transmigran lalu
-- barisnya dihapus). usia diturunkan dari tanggal_lahir.
CREATE TABLE `anggota_keluarga` (
  `id_anggota_keluarga`  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `transmigran_id`       BIGINT UNSIGNED NOT NULL,
  `hubungan`             ENUM('Istri','Suami','Anak','Anak Angkat','Orang Tua','Famili Lain') NOT NULL,
  `nama_lengkap`         VARCHAR(255) NOT NULL,
  `nik`                  CHAR(16) NULL,               -- NULL bagi balita; 16 digit bila diisi
  `jenis_kelamin`        ENUM('Laki-laki','Perempuan') NULL,
  `tempat_lahir`         VARCHAR(100) NULL,
  `tanggal_lahir`        DATE NULL,
  `agama`                ENUM('Islam','Kristen','Katolik','Hindu','Buddha','Konghucu') NULL,
  `kegiatan`             ENUM('Belum Sekolah','Masih Sekolah','Bekerja','Tidak Bekerja') NULL,
  `pendidikan_terakhir`  ENUM('Tidak Sekolah','SD','SMP','SMA/SMK','Diploma','S1','S2','S3') NULL,
  `pekerjaan`            VARCHAR(100) NULL,           -- wajib bila kegiatan = Bekerja
  `pendapatan_per_bulan` DECIMAL(15,2) NULL,          -- hanya bila kegiatan = Bekerja
  `telepon`              VARCHAR(20) NULL,
  `keterangan`           VARCHAR(1000) NULL,
  `status`               ENUM('Aktif','Meninggal','Pindah') NOT NULL DEFAULT 'Aktif',
  `tanggal_peristiwa`    DATE NULL,                   -- NULL bila status = Aktif
  `keterangan_peristiwa` VARCHAR(500) NULL,           -- NULL bila status = Aktif
  `created_at`           TIMESTAMP NULL DEFAULT NULL,
  `updated_at`           TIMESTAMP NULL DEFAULT NULL,
  `deleted_at`           TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_anggota_keluarga`),
  KEY `idx_anggota_keluarga_transmigran` (`transmigran_id`),
  KEY `idx_anggota_keluarga_nama` (`nama_lengkap`),
  KEY `idx_anggota_keluarga_nik` (`nik`),
  KEY `idx_anggota_keluarga_status` (`status`),
  CONSTRAINT `fk_anggota_keluarga_transmigran`
    FOREIGN KEY (`transmigran_id`) REFERENCES `transmigran` (`id_transmigran`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.3 rumah -------------------------------------
-- Relasi rumah<->KK satu-ke-satu dua arah: FK di rumah, transmigran_id UNIQUE
-- nullable (NULL = rumah kosong). Constraint UNIQUE WAJIB di level DB (rules.md 6a.6).
CREATE TABLE `rumah` (
  `id_rumah`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`                 CHAR(36) NOT NULL,
  `satuan_permukiman_id` BIGINT UNSIGNED NOT NULL,
  `transmigran_id`       BIGINT UNSIGNED NULL,
  `no_rumah`             VARCHAR(50) NULL,
  `kondisi`              VARCHAR(20) NOT NULL,        -- REF(jenis=kondisi_rumah): Tidak Rusak / Rusak Ringan / Rusak Berat
  `status_hunian`        VARCHAR(20) NOT NULL,        -- REF(jenis=status_hunian): Dihuni / Tidak Dihuni
  `alasan_tidak_dihuni`  TEXT NULL,                   -- wajib bila status_hunian = Tidak Dihuni
  `tahun_pembangunan`    YEAR NULL,
  `luas_bangunan`        DECIMAL(8,2) NULL,           -- meter persegi
  `lintang`              DECIMAL(10,7) NULL,
  `bujur`                DECIMAL(10,7) NULL,
  `foto_rumah`           VARCHAR(255) NULL,
  `catatan_hunian`       TEXT NULL,
  `dokumen_pendukung`    VARCHAR(255) NULL,
  `created_at`           TIMESTAMP NULL DEFAULT NULL,
  `updated_at`           TIMESTAMP NULL DEFAULT NULL,
  `deleted_at`           TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_rumah`),
  UNIQUE KEY `uq_rumah_uuid` (`uuid`),
  UNIQUE KEY `uq_rumah_transmigran` (`transmigran_id`),
  KEY `idx_rumah_sp` (`satuan_permukiman_id`),
  KEY `idx_rumah_status_hunian` (`status_hunian`),
  KEY `idx_rumah_kondisi` (`kondisi`),
  CONSTRAINT `fk_rumah_sp`
    FOREIGN KEY (`satuan_permukiman_id`) REFERENCES `satuan_permukiman` (`id_satuan_permukiman`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_rumah_transmigran`
    FOREIGN KEY (`transmigran_id`) REFERENCES `transmigran` (`id_transmigran`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.4 riwayat_penghunian --------------------
-- Jejak pergantian penghuni; append-only. Tabel riwayat: tanpa soft delete.
CREATE TABLE `riwayat_penghunian` (
  `id_riwayat_penghunian` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rumah_id`              BIGINT UNSIGNED NOT NULL,
  `transmigran_id`        BIGINT UNSIGNED NOT NULL,
  `tanggal_masuk`         DATE NOT NULL,
  `tanggal_keluar`        DATE NULL,                  -- NULL = masih menghuni
  `alasan_keluar`         TEXT NULL,
  `keterangan`            TEXT NULL,
  `created_at`            TIMESTAMP NULL DEFAULT NULL,
  `updated_at`            TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_riwayat_penghunian`),
  KEY `idx_riwayat_penghunian_rumah` (`rumah_id`),
  KEY `idx_riwayat_penghunian_transmigran` (`transmigran_id`),
  KEY `idx_riwayat_penghunian_masuk` (`tanggal_masuk`),
  KEY `idx_riwayat_penghunian_keluar` (`tanggal_keluar`),
  CONSTRAINT `fk_riwayat_penghunian_rumah`
    FOREIGN KEY (`rumah_id`) REFERENCES `rumah` (`id_rumah`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_riwayat_penghunian_transmigran`
    FOREIGN KEY (`transmigran_id`) REFERENCES `transmigran` (`id_transmigran`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.5 riwayat_kepala_keluarga ------------
-- Jejak suksesi kepala keluarga; append-only; kedua sisi identitas didenormalisasi
-- (tanpa FK ke anggota_keluarga yang akan menggantung). Tidak dapat dihapus siapa pun.
CREATE TABLE `riwayat_kepala_keluarga` (
  `id_riwayat_kepala_keluarga` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `transmigran_id`             BIGINT UNSIGNED NOT NULL,   -- rumah tangganya; tidak pernah berubah
  `nik_lama`                   CHAR(16) NOT NULL,
  `nama_lama`                  VARCHAR(255) NOT NULL,
  `nik_baru`                   CHAR(16) NOT NULL,
  `nama_baru`                  VARCHAR(255) NOT NULL,
  `no_kk_lama`                 CHAR(16) NOT NULL,
  `no_kk_baru`                 CHAR(16) NOT NULL,
  `tanggal_pergantian`         DATE NOT NULL,
  `alasan`                     ENUM('Meninggal','Pindah atau Merantau','Cerai','Lainnya') NOT NULL,
  `hubungan_pengganti`         ENUM('Istri','Suami','Anak','Anak Angkat','Orang Tua','Famili Lain') NOT NULL,
  `keterangan`                 TEXT NULL,
  `created_at`                 TIMESTAMP NULL DEFAULT NULL,
  `updated_at`                 TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_riwayat_kepala_keluarga`),
  KEY `idx_riwayat_kk_transmigran` (`transmigran_id`),
  KEY `idx_riwayat_kk_tanggal` (`tanggal_pergantian`),
  KEY `idx_riwayat_kk_alasan` (`alasan`),
  CONSTRAINT `fk_riwayat_kk_transmigran`
    FOREIGN KEY (`transmigran_id`) REFERENCES `transmigran` (`id_transmigran`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- #############################################################################
-- DOMAIN 6 : KELEMBAGAAN & SARANA
-- (Diletakkan sebelum Lahan: lahan.poktan_id menaut ke poktan.)
-- #############################################################################

-- 6.1 poktan --------------------
-- Ketua punya 3 asal-usul (asal_ketua). jumlah_anggota & luas_lahan_kelompok TIDAK
-- disimpan (diturunkan). luas_*_ketua hanya terisi bila asal_ketua = Bukan Transmigran.
-- nama_ketua/nik_ketua hanya untuk Bukan Transmigran; kolom hubungan_ketua DICABUT.
CREATE TABLE `poktan` (
  `id_poktan`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `satuan_permukiman_id`      BIGINT UNSIGNED NOT NULL,
  `slug`                      VARCHAR(120) NOT NULL,
  `nama`                      VARCHAR(255) NOT NULL,
  `asal_ketua`                ENUM('Kepala Keluarga','Anggota Keluarga','Bukan Transmigran') NOT NULL DEFAULT 'Kepala Keluarga',
  `ketua_transmigran_id`      BIGINT UNSIGNED NULL,     -- keluarga yang diwakili; wajib bila asal_ketua != Bukan Transmigran
  `ketua_anggota_keluarga_id` BIGINT UNSIGNED NULL,     -- wajib bila asal_ketua = Anggota Keluarga
  `nama_ketua`                VARCHAR(255) NULL,         -- hanya bila asal_ketua = Bukan Transmigran
  `nik_ketua`                 CHAR(16) NULL,             -- hanya bila asal_ketua = Bukan Transmigran
  `tahun_berdiri`             YEAR NULL,
  `telepon_ketua`             VARCHAR(20) NULL,
  `email_ketua`               VARCHAR(255) NULL,
  `alamat_ketua`              VARCHAR(255) NULL,
  `luas_kering_ketua`         DECIMAL(12,2) NULL,        -- hektare; hanya Bukan Transmigran
  `luas_basah_ketua`          DECIMAL(12,2) NULL,        -- hektare; hanya Bukan Transmigran
  `lintang`                   DECIMAL(10,7) NULL,
  `bujur`                     DECIMAL(10,7) NULL,
  `dokumen_pendukung`         VARCHAR(255) NULL,
  `keterangan`                TEXT NULL,
  `created_at`                TIMESTAMP NULL DEFAULT NULL,
  `updated_at`                TIMESTAMP NULL DEFAULT NULL,
  `deleted_at`                TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_poktan`),
  UNIQUE KEY `uq_poktan_slug` (`slug`),
  UNIQUE KEY `uq_poktan_nama` (`nama`),
  KEY `idx_poktan_sp` (`satuan_permukiman_id`),
  KEY `idx_poktan_ketua_transmigran` (`ketua_transmigran_id`),
  KEY `idx_poktan_ketua_anggota_keluarga` (`ketua_anggota_keluarga_id`),
  CONSTRAINT `fk_poktan_sp`
    FOREIGN KEY (`satuan_permukiman_id`) REFERENCES `satuan_permukiman` (`id_satuan_permukiman`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_poktan_ketua_transmigran`
    FOREIGN KEY (`ketua_transmigran_id`) REFERENCES `transmigran` (`id_transmigran`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_poktan_ketua_anggota_keluarga`
    FOREIGN KEY (`ketua_anggota_keluarga_id`) REFERENCES `anggota_keluarga` (`id_anggota_keluarga`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.2 anggota_poktan ------------
-- transmigran_id menunjuk KELUARGA yang diwakili. asal_wakil enum memuat 3 nilai
-- (agar 1 tipe dipakai bersama poktan.asal_ketua); nilai 'Bukan Transmigran' tidak
-- berlaku di sini (ditegakkan aplikasi). Anggota berhenti ditandai 'Sudah Keluar',
-- tidak dihapus. Kolom nama_wakil/nik_wakil/hubungan_dengan_kk DICABUT.
CREATE TABLE `anggota_poktan` (
  `id_anggota_poktan`   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `poktan_id`           BIGINT UNSIGNED NOT NULL,
  `transmigran_id`      BIGINT UNSIGNED NOT NULL,
  `asal_wakil`          ENUM('Kepala Keluarga','Anggota Keluarga','Bukan Transmigran') NOT NULL DEFAULT 'Kepala Keluarga',
  `anggota_keluarga_id` BIGINT UNSIGNED NULL,          -- wajib bila asal_wakil = Anggota Keluarga
  `telepon_wakil`       VARCHAR(20) NULL,
  `jabatan`             VARCHAR(30) NOT NULL,           -- REF(jenis=jabatan_anggota_poktan): Sekretaris / Bendahara / Anggota (tanpa Ketua)
  `tanggal_masuk`       DATE NOT NULL,
  `status`              ENUM('Aktif','Tidak Aktif','Sudah Keluar') NOT NULL,
  `tanggal_keluar`      DATE NULL,                      -- wajib bila status = Sudah Keluar
  `alasan_keluar`       TEXT NULL,
  `keterangan`          TEXT NULL,
  `created_at`          TIMESTAMP NULL DEFAULT NULL,
  `updated_at`          TIMESTAMP NULL DEFAULT NULL,
  `deleted_at`          TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_anggota_poktan`),
  UNIQUE KEY `uq_anggota_poktan_poktan_transmigran` (`poktan_id`,`transmigran_id`),
  KEY `idx_anggota_poktan_transmigran` (`transmigran_id`),
  KEY `idx_anggota_poktan_status` (`status`),
  KEY `idx_anggota_poktan_anggota_keluarga` (`anggota_keluarga_id`),
  CONSTRAINT `fk_anggota_poktan_poktan`
    FOREIGN KEY (`poktan_id`) REFERENCES `poktan` (`id_poktan`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_anggota_poktan_transmigran`
    FOREIGN KEY (`transmigran_id`) REFERENCES `transmigran` (`id_transmigran`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_anggota_poktan_anggota_keluarga`
    FOREIGN KEY (`anggota_keluarga_id`) REFERENCES `anggota_keluarga` (`id_anggota_keluarga`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.3 alsintan (induk / pengadaan) ---
-- Pola Putaran 7: satu pengadaan -> banyak distribusi. Baris ini mendeskripsikan
-- BENDAnya. Kolom kepemilikan / transmigran_id / poktan_id / kondisi DICABUT dari induk.
-- SUM(alsintan_distribusi.jumlah) <= jumlah_total (ditegakkan aplikasi).
CREATE TABLE `alsintan` (
  `id_alsintan`       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `jenis_alsintan`    VARCHAR(120) NOT NULL,           -- REF(jenis=jenis_alsintan)
  `nama_alat`         VARCHAR(255) NOT NULL,           -- merek/tipe spesifik
  `jumlah_total`      INT UNSIGNED NOT NULL,
  `tahun_pengadaan`   YEAR NULL,
  `sumber_dana`       VARCHAR(50) NULL,                -- REF(jenis=sumber_dana)
  `dokumen_pendukung` VARCHAR(255) NULL,
  `keterangan`        TEXT NULL,
  `created_at`        TIMESTAMP NULL DEFAULT NULL,
  `updated_at`        TIMESTAMP NULL DEFAULT NULL,
  `deleted_at`        TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_alsintan`),
  KEY `idx_alsintan_jenis` (`jenis_alsintan`),
  KEY `idx_alsintan_tahun` (`tahun_pengadaan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.4 alsintan_distribusi -----------
-- Satu baris per poktan penerima. kondisi diamati per unit di lapangan.
-- penanda_terima_id = anggota poktan yang tanda tangan BA (bukan pemilik).
-- satuan_permukiman_id turunan (mengikuti poktan) -> tidak disimpan.
CREATE TABLE `alsintan_distribusi` (
  `id_alsintan_distribusi` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `alsintan_id`            BIGINT UNSIGNED NOT NULL,
  `poktan_id`              BIGINT UNSIGNED NOT NULL,
  `jumlah`                 INT UNSIGNED NOT NULL,
  `kondisi`                VARCHAR(20) NULL,           -- REF(jenis=kondisi)
  `penanda_terima_id`      BIGINT UNSIGNED NULL,       -- -> anggota_poktan
  `tanggal_serah`          DATE NULL,
  `foto`                   VARCHAR(255) NULL,
  `keterangan`             TEXT NULL,
  `created_at`             TIMESTAMP NULL DEFAULT NULL,
  `updated_at`             TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_alsintan_distribusi`),
  KEY `idx_alsintan_distribusi_alsintan` (`alsintan_id`),
  KEY `idx_alsintan_distribusi_poktan` (`poktan_id`),
  KEY `idx_alsintan_distribusi_penanda` (`penanda_terima_id`),
  CONSTRAINT `fk_alsintan_distribusi_alsintan`
    FOREIGN KEY (`alsintan_id`) REFERENCES `alsintan` (`id_alsintan`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_alsintan_distribusi_poktan`
    FOREIGN KEY (`poktan_id`) REFERENCES `poktan` (`id_poktan`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_alsintan_distribusi_penanda`
    FOREIGN KEY (`penanda_terima_id`) REFERENCES `anggota_poktan` (`id_anggota_poktan`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.5 saprotan (induk / pengadaan) --
-- komoditas_id & varietas WAJIB hanya bila jenis = Benih. tahun_pengadaan =
-- tahun anggaran APBD/APBN (sumbu laporan panen). jadwal_tanam = rencana YYYY-MM.
-- transmigran_id DICABUT (penerima selalu poktan).
CREATE TABLE `saprotan` (
  `id_saprotan`      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `satuan_id`        BIGINT UNSIGNED NOT NULL,          -- satuan jumlah
  `komoditas_id`     BIGINT UNSIGNED NULL,              -- wajib bila jenis = Benih
  `jenis`            ENUM('Benih','Pupuk','Pestisida','Mulsa','Lainnya') NOT NULL,
  `nama`             VARCHAR(255) NOT NULL,
  `jumlah_total`     DECIMAL(12,3) NOT NULL,
  `varietas`         VARCHAR(120) NULL,                 -- wajib bila jenis = Benih
  `jadwal_tanam`     CHAR(7) NULL,                      -- rencana tanam YYYY-MM
  `tahun_pengadaan`  YEAR NOT NULL,                     -- tahun anggaran
  `sumber_dana`      VARCHAR(50) NULL,                  -- REF(jenis=sumber_dana)
  `foto`             VARCHAR(255) NULL,
  `dokumen_pendukung` VARCHAR(255) NULL,
  `keterangan`       TEXT NULL,
  `created_at`       TIMESTAMP NULL DEFAULT NULL,
  `updated_at`       TIMESTAMP NULL DEFAULT NULL,
  `deleted_at`       TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_saprotan`),
  KEY `idx_saprotan_satuan` (`satuan_id`),
  KEY `idx_saprotan_komoditas` (`komoditas_id`),
  KEY `idx_saprotan_jenis` (`jenis`),
  KEY `idx_saprotan_tahun` (`tahun_pengadaan`),
  CONSTRAINT `fk_saprotan_satuan`
    FOREIGN KEY (`satuan_id`) REFERENCES `satuan` (`id_satuan`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_saprotan_komoditas`
    FOREIGN KEY (`komoditas_id`) REFERENCES `komoditas` (`id_komoditas`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.6 saprotan_distribusi ----------
-- Satu baris per poktan penerima. Sisa benih DIHITUNG per baris ini
-- (jumlah - SUM(penanaman.volume_benih WHERE saprotan_distribusi_id = ini)), tidak disimpan.
CREATE TABLE `saprotan_distribusi` (
  `id_saprotan_distribusi` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `saprotan_id`            BIGINT UNSIGNED NOT NULL,
  `poktan_id`              BIGINT UNSIGNED NOT NULL,   -- SP mengikuti poktan
  `jumlah`                 DECIMAL(12,3) NOT NULL,
  `tanggal_serah`          DATE NULL,
  `keterangan`             TEXT NULL,
  `created_at`             TIMESTAMP NULL DEFAULT NULL,
  `updated_at`             TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_saprotan_distribusi`),
  KEY `idx_saprotan_distribusi_saprotan` (`saprotan_id`),
  KEY `idx_saprotan_distribusi_poktan` (`poktan_id`),
  CONSTRAINT `fk_saprotan_distribusi_saprotan`
    FOREIGN KEY (`saprotan_id`) REFERENCES `saprotan` (`id_saprotan`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_saprotan_distribusi_poktan`
    FOREIGN KEY (`poktan_id`) REFERENCES `poktan` (`id_poktan`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- #############################################################################
-- DOMAIN 7 : LAHAN
-- #############################################################################

-- 7.1 lahan ----------------------------
-- Satu bidang = satu baris. Satu transmigran boleh punya banyak lahan (FK di sini).
-- kering/basah = KOMPOSISI luas (bukan kategori bidang); untuk lahan usaha
-- luas_kering + luas_basah = luas (ditegakkan lewat derivasi, bukan CHECK).
-- Kolom status_hak dan kategori_lahan DICABUT (2026-08-29 / 2026-08-20).
CREATE TABLE `lahan` (
  `id_lahan`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`                 CHAR(36) NOT NULL,
  `transmigran_id`       BIGINT UNSIGNED NOT NULL,
  `satuan_permukiman_id` BIGINT UNSIGNED NOT NULL,
  `poktan_id`            BIGINT UNSIGNED NULL,           -- poktan pengelola bila ada
  `kode_lahan`           VARCHAR(50) NULL,
  `peruntukan_lahan`     ENUM('Lahan Pekarangan','Lahan Usaha') NOT NULL,
  `luas`                 DECIMAL(12,2) NOT NULL,          -- hektare; luas seluruh bidang
  `luas_kering`          DECIMAL(12,2) NULL,              -- hektare; hanya lahan usaha
  `luas_basah`           DECIMAL(12,2) NULL,              -- hektare; hanya lahan usaha
  `tujuan_pemanfaatan`   TEXT NULL,
  `lintang`              DECIMAL(10,7) NULL,
  `bujur`                DECIMAL(10,7) NULL,
  `pola_tanam`           VARCHAR(255) NULL,               -- khusus lahan usaha
  `peralatan_pertanian`  TEXT NULL,                       -- khusus lahan usaha
  `kendala`              TEXT NULL,                       -- khusus lahan usaha
  `keterangan`           TEXT NULL,
  `created_at`           TIMESTAMP NULL DEFAULT NULL,
  `updated_at`           TIMESTAMP NULL DEFAULT NULL,
  `deleted_at`           TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_lahan`),
  UNIQUE KEY `uq_lahan_uuid` (`uuid`),
  UNIQUE KEY `uq_lahan_kode` (`kode_lahan`),
  KEY `idx_lahan_transmigran` (`transmigran_id`),
  KEY `idx_lahan_sp` (`satuan_permukiman_id`),
  KEY `idx_lahan_peruntukan` (`peruntukan_lahan`),
  KEY `idx_lahan_poktan` (`poktan_id`),
  CONSTRAINT `fk_lahan_transmigran`
    FOREIGN KEY (`transmigran_id`) REFERENCES `transmigran` (`id_transmigran`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_lahan_sp`
    FOREIGN KEY (`satuan_permukiman_id`) REFERENCES `satuan_permukiman` (`id_satuan_permukiman`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_lahan_poktan`
    FOREIGN KEY (`poktan_id`) REFERENCES `poktan` (`id_poktan`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7.2 dokumen_lahan (induk) -------------
-- Berkas status lahan. nomor_dokumen & tanggal_terbit WAJIB dipertahankan (data legal).
CREATE TABLE `dokumen_lahan` (
  `id_dokumen_lahan` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `jenis_dokumen`    VARCHAR(50) NOT NULL,             -- REF(jenis=jenis_dokumen_lahan): HPL / SHM
  `nomor_dokumen`    VARCHAR(100) NULL,
  `tanggal_terbit`   DATE NULL,
  `file_dokumen`     VARCHAR(255) NOT NULL,            -- path berkas
  `keterangan`       TEXT NULL,
  `created_at`       TIMESTAMP NULL DEFAULT NULL,
  `updated_at`       TIMESTAMP NULL DEFAULT NULL,
  `deleted_at`       TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_dokumen_lahan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7.3 dokumen_lahan_bidang (pivot M:N) --
-- Satu dokumen (HPL/SK) dapat mencakup banyak bidang (Putaran 7, 2026-08-30).
CREATE TABLE `dokumen_lahan_bidang` (
  `id_dokumen_lahan_bidang` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dokumen_lahan_id`        BIGINT UNSIGNED NOT NULL,
  `lahan_id`                BIGINT UNSIGNED NOT NULL,
  `created_at`              TIMESTAMP NULL DEFAULT NULL,
  `updated_at`              TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_dokumen_lahan_bidang`),
  UNIQUE KEY `uq_dokumen_lahan_bidang` (`dokumen_lahan_id`,`lahan_id`),
  KEY `idx_dokumen_lahan_bidang_lahan` (`lahan_id`),
  CONSTRAINT `fk_dokumen_lahan_bidang_dokumen`
    FOREIGN KEY (`dokumen_lahan_id`) REFERENCES `dokumen_lahan` (`id_dokumen_lahan`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_dokumen_lahan_bidang_lahan`
    FOREIGN KEY (`lahan_id`) REFERENCES `lahan` (`id_lahan`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- #############################################################################
-- DOMAIN 8 : PRODUKSI PERTANIAN
-- #############################################################################

-- 8.1 komoditas_poktan (pivot M:N) ---
CREATE TABLE `komoditas_poktan` (
  `id_komoditas_poktan` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `poktan_id`           BIGINT UNSIGNED NOT NULL,
  `komoditas_id`        BIGINT UNSIGNED NOT NULL,
  `created_at`          TIMESTAMP NULL DEFAULT NULL,
  `updated_at`          TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_komoditas_poktan`),
  UNIQUE KEY `uq_komoditas_poktan` (`poktan_id`,`komoditas_id`),
  KEY `idx_komoditas_poktan_komoditas` (`komoditas_id`),
  CONSTRAINT `fk_komoditas_poktan_poktan`
    FOREIGN KEY (`poktan_id`) REFERENCES `poktan` (`id_poktan`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_komoditas_poktan_komoditas`
    FOREIGN KEY (`komoditas_id`) REFERENCES `komoditas` (`id_komoditas`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8.2 penanaman ------------
-- Berpusat pada poktan (bukan lahan/petani). Dahulu bernama riwayat_tanam.
-- TANPA musim_tanam: sumbu waktu = periode_tanam CHAR(7). saprotan_distribusi_id +
-- volume_benih WAJIB (termasuk bibit swadaya). status panen & luas kelompok = turunan.
-- Constraint UNIQUE (poktan_id, komoditas_id, periode_tanam) DICABUT 2026-09-01.
CREATE TABLE `penanaman` (
  `id_penanaman`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `poktan_id`               BIGINT UNSIGNED NOT NULL,
  `komoditas_id`            BIGINT UNSIGNED NOT NULL,
  `saprotan_distribusi_id`  BIGINT UNSIGNED NOT NULL,   -- jatah distribusi benih yang dipakai
  `volume_benih`            DECIMAL(12,3) NOT NULL,
  `realisasi_tanam`         DECIMAL(12,2) NOT NULL,     -- hektare yang benar-benar ditanami
  `periode_tanam`           CHAR(7) NOT NULL,           -- YYYY-MM
  `dokumen_pendukung`       VARCHAR(255) NULL,
  `keterangan`              TEXT NULL,
  `created_at`              TIMESTAMP NULL DEFAULT NULL,
  `updated_at`              TIMESTAMP NULL DEFAULT NULL,
  `deleted_at`              TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_penanaman`),
  KEY `idx_penanaman_poktan` (`poktan_id`),
  KEY `idx_penanaman_komoditas` (`komoditas_id`),
  KEY `idx_penanaman_periode` (`periode_tanam`),
  KEY `idx_penanaman_saprotan_distribusi` (`saprotan_distribusi_id`),
  CONSTRAINT `fk_penanaman_poktan`
    FOREIGN KEY (`poktan_id`) REFERENCES `poktan` (`id_poktan`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_penanaman_komoditas`
    FOREIGN KEY (`komoditas_id`) REFERENCES `komoditas` (`id_komoditas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_penanaman_saprotan_distribusi`
    FOREIGN KEY (`saprotan_distribusi_id`) REFERENCES `saprotan_distribusi` (`id_saprotan_distribusi`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8.3 hasil_panen ----------
-- Satu penanaman -> paling banyak satu baris panen (ditegakkan aplikasi).
-- Identitas (aplikasi): realisasi_panen + puso = penanaman.realisasi_tanam;
--                       produksi = realisasi_panen x produktivitas.
-- satuan_id DISALIN dari komoditas saat simpan (snapshot). poktan_id DICABUT
-- (diturunkan dari penanaman.poktan_id). kualitas DICABUT (-> produktivitas).
CREATE TABLE `hasil_panen` (
  `id_hasil_panen`    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`              CHAR(36) NOT NULL,
  `penanaman_id`      BIGINT UNSIGNED NOT NULL,
  `satuan_id`         BIGINT UNSIGNED NOT NULL,          -- disalin dari komoditas
  `periode_panen`     CHAR(7) NOT NULL,                  -- YYYY-MM
  `realisasi_panen`   DECIMAL(12,2) NOT NULL,            -- hektare dipanen
  `puso`              DECIMAL(12,2) NULL,                -- hektare gagal panen
  `produktivitas`     DECIMAL(12,3) NOT NULL,            -- per hektare, satuan baku komoditas
  `produksi`          DECIMAL(12,3) NOT NULL,            -- disimpan apa adanya, tanpa konversi
  `harga_jual`        DECIMAL(15,2) NULL,                -- rupiah per satuan baku
  `dokumen_pendukung` VARCHAR(255) NULL,
  `keterangan`        TEXT NULL,
  `created_at`        TIMESTAMP NULL DEFAULT NULL,
  `updated_at`        TIMESTAMP NULL DEFAULT NULL,
  `deleted_at`        TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_hasil_panen`),
  UNIQUE KEY `uq_hasil_panen_uuid` (`uuid`),
  KEY `idx_hasil_panen_penanaman` (`penanaman_id`),
  KEY `idx_hasil_panen_periode` (`periode_panen`),
  KEY `idx_hasil_panen_satuan` (`satuan_id`),
  CONSTRAINT `fk_hasil_panen_penanaman`
    FOREIGN KEY (`penanaman_id`) REFERENCES `penanaman` (`id_penanaman`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_hasil_panen_satuan`
    FOREIGN KEY (`satuan_id`) REFERENCES `satuan` (`id_satuan`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- #############################################################################
-- DOMAIN 9 : INFRASTRUKTUR & PENGADUAN
-- #############################################################################

-- 9.1 infrastruktur --------
-- Pendataan ASET (pelaporan kerusakan -> fitur Pengaduan).
-- satuan_permukiman_id = lokasi/pangkal (wajib). tahun_perolehan (bukan tahun_pengadaan).
CREATE TABLE `infrastruktur` (
  `id_infrastruktur`     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `satuan_permukiman_id` BIGINT UNSIGNED NOT NULL,
  `poktan_id`            BIGINT UNSIGNED NULL,          -- diisi bila dikelola poktan
  `nama`                 VARCHAR(255) NOT NULL,
  `jenis`                VARCHAR(50) NOT NULL,          -- REF(jenis=jenis_infrastruktur)
  `tahun_perolehan`      YEAR NULL,
  `sumber_dana`          VARCHAR(50) NULL,              -- REF(jenis=sumber_dana)
  `kondisi`              VARCHAR(20) NOT NULL,          -- REF(jenis=kondisi); sumber grafik status
  `kapasitas`            VARCHAR(100) NULL,             -- mis. "debit 5 liter/detik", "panjang 2 km"
  `lintang`              DECIMAL(10,7) NULL,
  `bujur`                DECIMAL(10,7) NULL,
  `foto`                 VARCHAR(255) NULL,
  `dokumen_pendukung`    VARCHAR(255) NULL,
  `keterangan`           TEXT NULL,
  `created_at`           TIMESTAMP NULL DEFAULT NULL,
  `updated_at`           TIMESTAMP NULL DEFAULT NULL,
  `deleted_at`           TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_infrastruktur`),
  KEY `idx_infrastruktur_sp` (`satuan_permukiman_id`),
  KEY `idx_infrastruktur_jenis` (`jenis`),
  KEY `idx_infrastruktur_kondisi` (`kondisi`),
  KEY `idx_infrastruktur_poktan` (`poktan_id`),
  CONSTRAINT `fk_infrastruktur_sp`
    FOREIGN KEY (`satuan_permukiman_id`) REFERENCES `satuan_permukiman` (`id_satuan_permukiman`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_infrastruktur_poktan`
    FOREIGN KEY (`poktan_id`) REFERENCES `poktan` (`id_poktan`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9.2 infrastruktur_sp (pivot) --------
-- Cakupan layanan lintas SP (Putaran 7). WAJIB memuat SP pangkal.
CREATE TABLE `infrastruktur_sp` (
  `id_infrastruktur_sp`  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `infrastruktur_id`     BIGINT UNSIGNED NOT NULL,
  `satuan_permukiman_id` BIGINT UNSIGNED NOT NULL,
  `created_at`           TIMESTAMP NULL DEFAULT NULL,
  `updated_at`           TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_infrastruktur_sp`),
  UNIQUE KEY `uq_infrastruktur_sp` (`infrastruktur_id`,`satuan_permukiman_id`),
  KEY `idx_infrastruktur_sp_sp` (`satuan_permukiman_id`),
  CONSTRAINT `fk_infrastruktur_sp_infrastruktur`
    FOREIGN KEY (`infrastruktur_id`) REFERENCES `infrastruktur` (`id_infrastruktur`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_infrastruktur_sp_sp`
    FOREIGN KEY (`satuan_permukiman_id`) REFERENCES `satuan_permukiman` (`id_satuan_permukiman`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9.3 pengaduan ------------
-- Kanal PUBLIK tanpa login: user_id nullable (kosong bila dari warga). bidang nullable
-- (belum ditetapkan). status = terkini saja; riwayat di penanganan_pengaduan.
CREATE TABLE `pengaduan` (
  `id_pengaduan`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`                 CHAR(36) NOT NULL,
  `user_id`              BIGINT UNSIGNED NULL,           -- terisi bila dicatat petugas
  `nama_pelapor`         VARCHAR(255) NOT NULL,
  `kontak_pelapor`       VARCHAR(20) NOT NULL,
  `email_pelapor`        VARCHAR(100) NULL,
  `sumber_laporan`       ENUM('Publik','Petugas') NOT NULL,
  `ip_pelapor`           VARCHAR(45) NULL,
  `satuan_permukiman_id` BIGINT UNSIGNED NOT NULL,
  `nomor_pengaduan`      VARCHAR(30) NOT NULL,           -- mis. PGD-2026-0001-K7F2M9 (6 char acak)
  `tanggal_pengaduan`    DATE NOT NULL,
  `kategori`             VARCHAR(50) NOT NULL,           -- REF(jenis=kategori_pengaduan)
  `bidang`               VARCHAR(30) NULL,               -- REF(jenis=bidang_pengaduan); NULL = belum ditetapkan
  `judul`                VARCHAR(255) NOT NULL,
  `deskripsi`            TEXT NOT NULL,
  `status`               ENUM('Menunggu Diterima','Diterima','Diproses','Selesai') NOT NULL,
  `prioritas`            VARCHAR(20) NOT NULL,           -- REF(jenis=prioritas_pengaduan)
  `lintang`              DECIMAL(10,7) NULL,
  `bujur`                DECIMAL(10,7) NULL,
  `dokumen_pendukung`    VARCHAR(255) NULL,              -- bukti dari pelapor
  `created_at`           TIMESTAMP NULL DEFAULT NULL,
  `updated_at`           TIMESTAMP NULL DEFAULT NULL,
  `deleted_at`           TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_pengaduan`),
  UNIQUE KEY `uq_pengaduan_uuid` (`uuid`),
  UNIQUE KEY `uq_pengaduan_nomor` (`nomor_pengaduan`),
  KEY `idx_pengaduan_user` (`user_id`),
  KEY `idx_pengaduan_sp` (`satuan_permukiman_id`),
  KEY `idx_pengaduan_kategori` (`kategori`),
  KEY `idx_pengaduan_bidang` (`bidang`),
  KEY `idx_pengaduan_status` (`status`),
  KEY `idx_pengaduan_prioritas` (`prioritas`),
  KEY `idx_pengaduan_tanggal` (`tanggal_pengaduan`),
  KEY `idx_pengaduan_sumber` (`sumber_laporan`),
  KEY `idx_pengaduan_ip` (`ip_pelapor`),
  CONSTRAINT `fk_pengaduan_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id_user`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_pengaduan_sp`
    FOREIGN KEY (`satuan_permukiman_id`) REFERENCES `satuan_permukiman` (`id_satuan_permukiman`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9.4 penanganan_pengaduan --
-- Riwayat penanganan; setiap perubahan status = satu baris. Tabel riwayat: tanpa soft delete.
CREATE TABLE `penanganan_pengaduan` (
  `id_penanganan_pengaduan` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pengaduan_id`            BIGINT UNSIGNED NOT NULL,
  `user_id`                 BIGINT UNSIGNED NOT NULL,   -- petugas penangan
  `status_sebelum`          ENUM('Menunggu Diterima','Diterima','Diproses','Selesai') NULL,   -- NULL pada baris pertama
  `status_sesudah`          ENUM('Menunggu Diterima','Diterima','Diproses','Selesai') NOT NULL,
  `tanggal_penanganan`      DATE NOT NULL,
  `catatan`                 TEXT NOT NULL,
  `dokumen_tindak_lanjut`   VARCHAR(255) NULL,
  `created_at`              TIMESTAMP NULL DEFAULT NULL,
  `updated_at`              TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_penanganan_pengaduan`),
  KEY `idx_penanganan_pengaduan_pengaduan` (`pengaduan_id`),
  KEY `idx_penanganan_pengaduan_tanggal` (`tanggal_penanganan`),
  KEY `idx_penanganan_pengaduan_user` (`user_id`),
  CONSTRAINT `fk_penanganan_pengaduan_pengaduan`
    FOREIGN KEY (`pengaduan_id`) REFERENCES `pengaduan` (`id_pengaduan`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_penanganan_pengaduan_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id_user`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- #############################################################################
-- TABEL INFRASTRUKTUR FRAMEWORK LARAVEL
-- Bukan bagian ERD bisnis (erd.md 9). Disertakan agar hasil import langsung
-- menjadi database Laravel yang utuh. Struktur mengikuti migration bawaan Laravel 12.
-- Tabel `users` bawaan TIDAK dibuat -- digantikan tabel `user` di atas.
-- `password_reset_tokens` TIDAK dibuat -- digantikan `kode_pemulihan_sandi`.
-- #############################################################################

CREATE TABLE `sessions` (
  `id`            VARCHAR(255) NOT NULL,
  `user_id`       BIGINT UNSIGNED NULL,
  `ip_address`    VARCHAR(45) NULL,
  `user_agent`    TEXT NULL,
  `payload`       LONGTEXT NOT NULL,
  `last_activity` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sessions_user_id` (`user_id`),
  KEY `idx_sessions_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
  `key`        VARCHAR(255) NOT NULL,
  `value`      MEDIUMTEXT NOT NULL,
  `expiration` INT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key`        VARCHAR(255) NOT NULL,
  `owner`      VARCHAR(255) NOT NULL,
  `expiration` INT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue`        VARCHAR(255) NOT NULL,
  `payload`      LONGTEXT NOT NULL,
  `attempts`     TINYINT UNSIGNED NOT NULL,
  `reserved_at`  INT UNSIGNED NULL,
  `available_at` INT UNSIGNED NOT NULL,
  `created_at`   INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_jobs_queue` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id`             VARCHAR(255) NOT NULL,
  `name`           VARCHAR(255) NOT NULL,
  `total_jobs`     INT NOT NULL,
  `pending_jobs`   INT NOT NULL,
  `failed_jobs`    INT NOT NULL,
  `failed_job_ids` LONGTEXT NOT NULL,
  `options`        MEDIUMTEXT NULL,
  `cancelled_at`   INT NULL,
  `created_at`     INT NOT NULL,
  `finished_at`    INT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`       VARCHAR(255) NOT NULL,
  `connection` TEXT NOT NULL,
  `queue`      TEXT NOT NULL,
  `payload`    LONGTEXT NOT NULL,
  `exception`  LONGTEXT NOT NULL,
  `failed_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_failed_jobs_uuid` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- SELESAI. 44 tabel bisnis + 6 tabel framework Laravel = 50 tabel.
-- =============================================================================
