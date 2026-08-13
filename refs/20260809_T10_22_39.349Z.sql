CREATE TABLE IF NOT EXISTS `user` (
	`id_user` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`foto` BLOB,
	`nama` VARCHAR(255),
	`email` VARCHAR(255),
	`telepon` VARCHAR(255),
	`kategori_user` ENUM('Admin', 'Dinas Transmigrasi', 'Dinas Pertanian', 'Transmigran', 'Ketua Poktan'),
	PRIMARY KEY(`id_user`)
);


CREATE TABLE IF NOT EXISTS `satuan_permukiman` (
	`no_sp` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`nama_sp` ENUM('Kapitan Meo', 'Tniumanu', 'Harekakae', 'Weoe', 'Tualaran', 'Weain'),
	`provinsi` ENUM('Nusa Tenggara Timur', 'Nusa Tenggara Barat') DEFAULT 'Nusa Tenggara Timur',
	`kabupaten` ENUM('Malaka', 'Belu', 'Timur Tengah Utara', 'Timur Tengah Selatan') DEFAULT 'Malaka',
	`kecamatan` ENUM('Laenmanen', 'Malaka Tengah', 'Wewiku', 'Rinhat') DEFAULT 'Laenmanen',
	`desa` ENUM('Kapitan Meo', 'Weain'),
	`koordinat_lokasi` GEOMETRY,
	`luas_lahan` VARCHAR(255),
	`id_user` INTEGER,
	`id_koordinat_sp` INTEGER,
	`id_inventaris_sp` INTEGER,
	`id_fasilitas_sp` INTEGER,
	`id_transmigran` INTEGER,
	`dokumen_pendukung` BLOB,
	PRIMARY KEY(`no_sp`)
);


CREATE TABLE IF NOT EXISTS `koordinat_lokasi_sp` (
	`id_koordinat_sp` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`Timur` TEXT(65535),
	`Selatan` TEXT(65535),
	`Barat` TEXT(65535),
	`Utara` TEXT(65535),
	PRIMARY KEY(`id_koordinat_sp`)
);


CREATE TABLE IF NOT EXISTS `inventaris_sp` (
	`id_inventaris_sp` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`nama_barang` VARCHAR(255),
	`tahun_perolehan` YEAR,
	`sumber_dana` ENUM('Sumber Perolehan Dana APBN', 'Sumber Perolehan Dana APBD Provinsi', 'Sumber Perolehan Dana APBD Kabupaten', 'Sumber Perolehan Dana Dinas Transmigrasi Malaka', 'Sumber Perolehan Dana Dinas Pertanian Malaka', 'Lembaga Swadaya Masyarakat', 'Lainnya'),
	`dokumen_pendukung` BLOB,
	PRIMARY KEY(`id_inventaris_sp`)
);


CREATE TABLE IF NOT EXISTS `fasilitas_sp` (
	`id_fasilitas_sp` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`nama_fasilitas` VARCHAR(255),
	`tahun_perolehan` YEAR,
	`sumber_dana` ENUM('Sumber Perolehan Dana APBN', 'Sumber Perolehan Dana APBD Provinsi', 'Sumber Perolehan Dana APBD Kabupaten', 'Sumber Perolehan Dana Dinas Transmigrasi Malaka', 'Sumber Perolehan Dana Dinas Pertanian Malaka', 'Lembaga Swadaya Masyarakat', 'Lainnya'),
	`dokumen_pendukung` BLOB,
	PRIMARY KEY(`id_fasilitas_sp`)
);


CREATE TABLE IF NOT EXISTS `transmigran` (
	`id_transmigran` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`NIK` VARCHAR(16),
	`nama_kepala_keluarga` VARCHAR(255),
	`pekerjaan_kepala_keluarga` VARCHAR(255),
	`jumlah_pendapatan_keluarga_perbulan` DECIMAL,
	`id_lahan_sp` INTEGER,
	`id_rumah_sp` INTEGER,
	`dokumen_pendukung` BLOB,
	`no_sp` INTEGER,
	PRIMARY KEY(`id_transmigran`)
);


CREATE TABLE IF NOT EXISTS `lahan_sp` (
	`id_lahan_sp` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`jenis_lahan` ENUM('Lahan Usaha', 'Lahan Pekarangan'),
	`luas_lahan` DECIMAL,
	`tujuan_lahan` TEXT(65535),
	`id_kategori_lahan_sp` INTEGER,
	PRIMARY KEY(`id_lahan_sp`)
);


CREATE TABLE IF NOT EXISTS `kategori_lahan_sp` (
	`id_kategori_lahan_sp` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`nama_lahan` ENUM('Lahan Usaha', 'Lahan Pekarangan'),
	PRIMARY KEY(`id_kategori_lahan_sp`)
);


CREATE TABLE IF NOT EXISTS `lahan_usaha_sp` (
	`id_lahan_usaha_sp` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`koordinat_lokasi_lahan` GEOMETRY,
	`status_keikutsertaan_kelompok_tani` ENUM('Ya', 'Tidak'),
	`peralatan_perlengkapan_pertanian` TEXT(65535),
	`komoditas` TEXT(65535),
	`musim_tanam` ENUM('MT1', 'MT2'),
	`pola_tanam` VARCHAR(255),
	`volumen_panen` VARCHAR(255),
	`kualitas_panen` VARCHAR(255),
	`harga_jual` VARCHAR(255),
	`kendala` TEXT(65535),
	`dokumen_pendukung` BLOB,
	`id_kategori_lahan_sp` INTEGER,
	PRIMARY KEY(`id_lahan_usaha_sp`)
);


CREATE TABLE IF NOT EXISTS `rumah_sp` (
	`id_rumah_sp` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`koordinat_lokasi` GEOMETRY,
	`kondisi` ENUM('Tidak Rusak', 'Rusak Ringan', 'Rusak Berat'),
	`status_hunian` ENUM('Dihuni', 'Tidak Dihuni'),
	`alasan_jika_tidak_dihuni` TEXT(65535),
	`catatan_hunian` TEXT(65535),
	`dokumen_pendukung` BLOB,
	`id_kategori_lahan_sp` INTEGER,
	PRIMARY KEY(`id_rumah_sp`)
);


CREATE TABLE IF NOT EXISTS `pertanian` (
	`id_pertanian` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_profil_poktan` INTEGER,
	`id_alsintan` INTEGER,
	`id_infrastruktur_pertanian` INTEGER,
	PRIMARY KEY(`id_pertanian`)
);


CREATE TABLE IF NOT EXISTS `profil_poktan` (
	`id_profil` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`desa` ENUM('Kapitan Meo', 'Tniumanu', 'Harekakae', 'Weoe', 'Naet', 'Weain'),
	`nama_poktan` VARCHAR(255),
	`nama_ketua_poktan` VARCHAR(255),
	`nik_ketua_poktan` VARCHAR(16),
	`telepon` VARCHAR(255),
	`email` VARCHAR(255),
	`jumlah_anggota` VARCHAR(255),
	`id_daftar_anggota` INTEGER,
	`id_transmigran` INTEGER,
	`titik_koordinat_lahan` GEOMETRY,
	`luas_lahan` VARCHAR(255),
	`id_kategori_lahan` INTEGER,
	`komoditas` VARCHAR(255),
	`id_komoditas` INTEGER,
	`dokumen_pendukung` BLOB,
	PRIMARY KEY(`id_profil`)
);


CREATE TABLE IF NOT EXISTS `daftar_anggota` (
	`id_daftar_anggota` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`nama` VARCHAR(255),
	`nik` VARCHAR(16),
	`tanggal_masuk` DATE,
	`status_keaktifan` ENUM('Aktif', 'Tidak Aktif', 'Sudah Keluar'),
	`tanggal_keluar` DATE,
	PRIMARY KEY(`id_daftar_anggota`)
);


CREATE TABLE IF NOT EXISTS `kategori_lahan` (
	`id_kategori_lahan` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`kategori_lahan` ENUM('Lahan Basah', 'Lahan Kering'),
	PRIMARY KEY(`id_kategori_lahan`)
);


CREATE TABLE IF NOT EXISTS `komoditas` (
	`id_komoditas` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`tipe_komoditas_1` ENUM('Pangan', 'Palawija', 'Hortikultura'),
	`nama_komoditas_1` VARCHAR(255),
	`tipe_komoditas_2` ENUM('Pangan', 'Palawija', 'Hortikultura'),
	`nama_komoditas_2` VARCHAR(255),
	`tipe_komoditas_3` ENUM('Pangan', 'Palawija', 'Hortikultura'),
	`nama_komoditas_3` VARCHAR(255),
	PRIMARY KEY(`id_komoditas`)
);


CREATE TABLE IF NOT EXISTS `alsintan` (
	`id_alsintan` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`nama` VARCHAR(255),
	`tahun_perolehan` DATE,
	`dokumen_pendukung` BLOB,
	PRIMARY KEY(`id_alsintan`)
);


CREATE TABLE IF NOT EXISTS `infrastruktur_pertanian` (
	`id_infrastruktur_pertanian` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`nama` VARCHAR(255),
	`tahun_perolehan` DATE,
	`dokumen_pendukung` BLOB,
	PRIMARY KEY(`id_infrastruktur_pertanian`)
);


CREATE TABLE IF NOT EXISTS `saprotan` (
	`id_saprotan` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`tahun_perolehan` DATE,
	`pembagian_kepada_anggota_status_aktif` VARCHAR(255),
	`id_anggota` INTEGER,
	`dokumen_pendukung` VARCHAR(255),
	`id_transmigran` INTEGER,
	PRIMARY KEY(`id_saprotan`)
);


CREATE TABLE IF NOT EXISTS `riwayat_tanam` (
	`id_riwayat_tanam` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_kategori_lahan` INTEGER,
	`id_musim_tanam` INTEGER,
	`id_komoditas` INTEGER,
	PRIMARY KEY(`id_riwayat_tanam`)
);


CREATE TABLE IF NOT EXISTS `musim_tanam` (
	`id_musim_tanam` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`keterangan` TEXT(65535),
	PRIMARY KEY(`id_musim_tanam`)
);


CREATE TABLE IF NOT EXISTS `pengaduan` (
	`id_pengaduan` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_transmigran` INTEGER,
	`id_user` INTEGER,
	`tanggal_pengaduan` DATE,
	`kategori_pengaduan` ENUM('Lahan Usaha', 'Lahan Pekarangan', 'Rumah', 'Infrastruktur', 'Peralatan & Perlengkapan', 'Alsintan', 'Produksi Panen', 'Bencana', 'Lainnya '),
	`deskripsi_pengaduan` TEXT(65535),
	`dokumen_pendukung` BLOB,
	`status_penanganan` ENUM('Menunggu Diterima', 'Diterima', 'Diproses', 'Selesai'),
	`id_status_penanganan` INTEGER,
	`catatan_penanganan` TEXT(65535),
	PRIMARY KEY(`id_pengaduan`)
);


CREATE TABLE IF NOT EXISTS `status_penanganan` (
	`id_status_penanganan` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_user` INTEGER,
	`tanggal_penanganan` DATE,
	`catatan_penanganan` TEXT(65535),
	`dokumen_pendukung` BLOB,
	PRIMARY KEY(`id_status_penanganan`)
);


ALTER TABLE `satuan_permukiman`
ADD FOREIGN KEY(`id_fasilitas_sp`) REFERENCES `fasilitas_sp`(`id_fasilitas_sp`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `inventaris_sp`
ADD FOREIGN KEY(`id_inventaris_sp`) REFERENCES `satuan_permukiman`(`id_inventaris_sp`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `satuan_permukiman`
ADD FOREIGN KEY(`id_transmigran`) REFERENCES `transmigran`(`id_transmigran`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `transmigran`
ADD FOREIGN KEY(`id_lahan_sp`) REFERENCES `lahan_sp`(`id_lahan_sp`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `satuan_permukiman`
ADD FOREIGN KEY(`id_koordinat_sp`) REFERENCES `koordinat_lokasi_sp`(`id_koordinat_sp`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `lahan_sp`
ADD FOREIGN KEY(`id_kategori_lahan_sp`) REFERENCES `kategori_lahan_sp`(`id_kategori_lahan_sp`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `kategori_lahan_sp`
ADD FOREIGN KEY(`id_kategori_lahan_sp`) REFERENCES `rumah_sp`(`id_kategori_lahan_sp`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `kategori_lahan_sp`
ADD FOREIGN KEY(`id_kategori_lahan_sp`) REFERENCES `lahan_usaha_sp`(`id_kategori_lahan_sp`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `satuan_permukiman`
ADD FOREIGN KEY(`id_user`) REFERENCES `user`(`id_user`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `daftar_anggota`
ADD FOREIGN KEY(`id_daftar_anggota`) REFERENCES `profil_poktan`(`id_daftar_anggota`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `kategori_lahan`
ADD FOREIGN KEY(`id_kategori_lahan`) REFERENCES `profil_poktan`(`id_kategori_lahan`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `komoditas`
ADD FOREIGN KEY(`id_komoditas`) REFERENCES `profil_poktan`(`id_komoditas`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `profil_poktan`
ADD FOREIGN KEY(`id_profil`) REFERENCES `pertanian`(`id_profil_poktan`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `alsintan`
ADD FOREIGN KEY(`id_alsintan`) REFERENCES `pertanian`(`id_alsintan`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `infrastruktur_pertanian`
ADD FOREIGN KEY(`id_infrastruktur_pertanian`) REFERENCES `pertanian`(`id_infrastruktur_pertanian`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `daftar_anggota`
ADD FOREIGN KEY(`id_daftar_anggota`) REFERENCES `saprotan`(`id_anggota`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `transmigran`
ADD FOREIGN KEY(`id_transmigran`) REFERENCES `saprotan`(`id_transmigran`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `kategori_lahan`
ADD FOREIGN KEY(`id_kategori_lahan`) REFERENCES `riwayat_tanam`(`id_kategori_lahan`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `musim_tanam`
ADD FOREIGN KEY(`id_musim_tanam`) REFERENCES `riwayat_tanam`(`id_musim_tanam`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `komoditas`
ADD FOREIGN KEY(`id_komoditas`) REFERENCES `riwayat_tanam`(`id_komoditas`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `transmigran`
ADD FOREIGN KEY(`id_transmigran`) REFERENCES `profil_poktan`(`id_transmigran`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `transmigran`
ADD FOREIGN KEY(`id_transmigran`) REFERENCES `pengaduan`(`id_transmigran`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `pengaduan`
ADD FOREIGN KEY(`id_status_penanganan`) REFERENCES `status_penanganan`(`id_status_penanganan`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `user`
ADD FOREIGN KEY(`id_user`) REFERENCES `transmigran`(`id_transmigran`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `user`
ADD FOREIGN KEY(`id_user`) REFERENCES `status_penanganan`(`id_user`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `satuan_permukiman`
ADD FOREIGN KEY(`no_sp`) REFERENCES `transmigran`(`no_sp`)
ON UPDATE NO ACTION ON DELETE NO ACTION;