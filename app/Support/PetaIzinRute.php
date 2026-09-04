<?php

namespace App\Support;

/**
 * Peta nama-rute -> kewenangan RBAC (Task 3.3).
 *
 * Dipakai `bootstrap/app.php` untuk melampirkan middleware `izin:<modul>,<aksi>`
 * ke tiap rute internal SETELAH `routes/internal.php` dimuat. Ditaruh sebagai
 * data terpusat, bukan disebar `->middleware()` di 137 baris rute, agar mudah
 * ditinjau sekali pandang dan `sanityCheck()` dapat menjamin tak ada rute
 * internal yang lolos tanpa pemeriksaan.
 *
 * Aksi mengikuti `data-dictionary.md` 13.1; pemetaan halaman -> izin mengikuti
 * `MenuHelper::definisiMenu()` dan `LaporanData::meta()`.
 */
class PetaIzinRute
{
    /**
     * @return array<string, string> nama rute => "modul,aksi"
     */
    public static function peta(): array
    {
        return [
            // Dashboard & SP
            'beranda' => 'dashboard,lihat',
            'sp.detail' => 'sp,lihat',
            'dashboard.sp' => 'sp,lihat',
            'sp.index' => 'sp,lihat',
            'sp.perbarui' => 'sp,ubah',
            'sp.simpan' => 'sp,tambah',
            'sp.hapus' => 'sp,hapus',

            // Data master wilayah & satuan & daftar pilihan
            'wilayah' => 'wilayah,lihat',
            'wilayah.simpan' => 'wilayah,tambah',
            'wilayah.perbarui' => 'wilayah,ubah',
            'wilayah.hapus' => 'wilayah,hapus',
            'master.satuan' => 'satuan,lihat',
            'satuan.simpan' => 'satuan,tambah',
            'satuan.perbarui' => 'satuan,ubah',
            'satuan.hapus' => 'satuan,hapus',
            'master.daftar-pilihan' => 'daftar_pilihan,lihat',
            'daftar-pilihan.jenis' => 'daftar_pilihan,lihat',
            'daftar-pilihan.simpan' => 'daftar_pilihan,tambah',
            'daftar-pilihan.perbarui' => 'daftar_pilihan,ubah',
            'master.penilaian-kondisi' => 'penilaian_kondisi,lihat',
            'penilaian-kondisi.parameter' => 'penilaian_kondisi,ubah',
            'penilaian-kondisi.status' => 'penilaian_kondisi,ubah',

            // Kawasan
            'kawasan' => 'kawasan,lihat',
            'kawasan.simpan' => 'kawasan,tambah',
            'kawasan.perbarui' => 'kawasan,ubah',
            'kawasan.hapus' => 'kawasan,hapus',

            // Inventaris & Fasilitas SP
            'sp.inventaris' => 'inventaris_sp,lihat',
            'sp.inventaris.detail' => 'inventaris_sp,lihat',
            'inventaris.simpan' => 'inventaris_sp,tambah',
            'inventaris.perbarui' => 'inventaris_sp,ubah',
            'inventaris.hapus' => 'inventaris_sp,hapus',
            'sp.fasilitas' => 'fasilitas_sp,lihat',
            'sp.fasilitas.detail' => 'fasilitas_sp,lihat',
            'fasilitas.simpan' => 'fasilitas_sp,tambah',
            'fasilitas.perbarui' => 'fasilitas_sp,ubah',
            'fasilitas.hapus' => 'fasilitas_sp,hapus',

            // Transmigran (+ anggota keluarga & suksesi KK yang dikelola lewatnya)
            'transmigran.index' => 'transmigran,lihat',
            'transmigran.detail' => 'transmigran,lihat',
            'transmigran.simpan' => 'transmigran,tambah',
            'transmigran.perbarui' => 'transmigran,ubah',
            'transmigran.hapus' => 'transmigran,hapus',
            'transmigran.ganti-kepala-keluarga' => 'riwayat_kepala_keluarga,tambah',
            'transmigran.anggota.catat-peristiwa' => 'transmigran,ubah',

            // Rumah & Lahan
            'rumah.index' => 'rumah,lihat',
            'rumah.detail' => 'rumah,lihat',
            'rumah.simpan' => 'rumah,tambah',
            'rumah.perbarui' => 'rumah,ubah',
            'rumah.hapus' => 'rumah,hapus',
            'lahan.index' => 'lahan,lihat',
            'lahan.detail' => 'lahan,lihat',
            'lahan.simpan' => 'lahan,tambah',
            'lahan.perbarui' => 'lahan,ubah',
            'lahan.hapus' => 'lahan,hapus',
            'lahan.dokumen.simpan' => 'lahan,ubah',

            // Hasil panen + rekap + rekap kependudukan
            'panen.index' => 'hasil_panen,lihat',
            'panen.detail' => 'hasil_panen,lihat',
            'panen.rekap' => 'hasil_panen,lihat',
            'panen.rekap.kelompok' => 'hasil_panen,lihat',
            'panen.simpan' => 'hasil_panen,tambah',
            'panen.perbarui' => 'hasil_panen,ubah',
            'panen.hapus' => 'hasil_panen,hapus',
            'kependudukan.rekap' => 'transmigran,lihat',
            'kependudukan.rekap.kelompok' => 'transmigran,lihat',

            // Kelembagaan
            'poktan.index' => 'poktan,lihat',
            'poktan.detail' => 'poktan,lihat',
            'poktan.simpan' => 'poktan,tambah',
            'poktan.perbarui' => 'poktan,ubah',
            'poktan.hapus' => 'poktan,hapus',
            'anggota-poktan.simpan' => 'anggota_poktan,tambah',
            'anggota-poktan.perbarui' => 'anggota_poktan,ubah',
            'alsintan.index' => 'alsintan,lihat',
            'alsintan.detail' => 'alsintan,lihat',
            'alsintan.simpan' => 'alsintan,tambah',
            'alsintan.perbarui' => 'alsintan,ubah',
            'alsintan.hapus' => 'alsintan,hapus',
            'alsintan.distribusi.kondisi' => 'alsintan,ubah',
            'saprotan.index' => 'saprotan,lihat',
            'saprotan.detail' => 'saprotan,lihat',
            'saprotan.simpan' => 'saprotan,tambah',
            'saprotan.perbarui' => 'saprotan,ubah',
            'saprotan.hapus' => 'saprotan,hapus',

            // Pertanian
            'komoditas.index' => 'komoditas,lihat',
            'komoditas.detail' => 'komoditas,lihat',
            'komoditas.simpan' => 'komoditas,tambah',
            'komoditas.perbarui' => 'komoditas,ubah',
            'komoditas.hapus' => 'komoditas,hapus',
            'penanaman' => 'penanaman,lihat',
            'penanaman.detail' => 'penanaman,lihat',
            'penanaman.simpan' => 'penanaman,tambah',
            'penanaman.perbarui' => 'penanaman,ubah',
            'penanaman.hapus' => 'penanaman,hapus',

            // Infrastruktur
            'infrastruktur.index' => 'infrastruktur,lihat',
            'infrastruktur.detail' => 'infrastruktur,lihat',
            'infrastruktur.simpan' => 'infrastruktur,tambah',
            'infrastruktur.perbarui' => 'infrastruktur,ubah',
            'infrastruktur.hapus' => 'infrastruktur,hapus',

            // Pengaduan
            'pengaduan.index' => 'pengaduan,lihat',
            'pengaduan.detail' => 'pengaduan,lihat',
            'pengaduan.rekap' => 'pengaduan,lihat',
            'pengaduan.rekap.kelompok' => 'pengaduan,lihat',
            'pengaduan.simpan' => 'pengaduan,tambah',
            'pengaduan.hapus' => 'pengaduan,hapus',
            'pengaduan.tangani' => 'penanganan_pengaduan,tambah',

            // Sistem
            'pengguna.index' => 'pengguna,lihat',
            'pengguna.simpan' => 'pengguna,tambah',
            'pengguna.perbarui' => 'pengguna,ubah',
            'pengguna.setel-sandi' => 'pengguna,ubah',
            'pengguna.nonaktifkan' => 'pengguna,ubah',
            'pengguna.aktifkan' => 'pengguna,ubah',
            'pengguna.hapus' => 'pengguna,ubah', // rute abort(405); tak ada izin pengguna.hapus
            'pengaturan.role' => 'role,lihat',
            'role.simpan' => 'role,tambah',
            'role.perbarui' => 'role,ubah',
            'role.hapus' => 'role,hapus',
            'audit-log' => 'audit_log,lihat',
            'cms' => 'cms,lihat',
            'cms.simpan' => 'cms,ubah',

            // Laporan (per LaporanData::meta()); dokumen polos = dashboard sbg lantai
            'laporan.indikator-kawasan' => 'dashboard,lihat',
            'laporan.monografi-sp' => 'sp,lihat',
            'laporan.transmigran' => 'transmigran,lihat',
            'laporan.poktan' => 'poktan,lihat',
            'laporan.alsintan' => 'alsintan,lihat',
            'laporan.saprotan' => 'saprotan,lihat',
            'laporan.hasil-panen' => 'hasil_panen,lihat',
            'laporan.dokumen' => 'dashboard,lihat',
        ];
    }

    /**
     * Rute internal yang SENGAJA tak ber-`izin`.
     *
     * @return array<int, string>
     */
    public static function dikecualikan(): array
    {
        return [
            // Ganti kata sandi wajib: tiap pengguna berhak (dan diwajibkan)
            // menggantinya, lepas dari role -- ber-`auth` di routes/web.php.
            'ganti-kata-sandi', 'ganti-kata-sandi.simpan',
            // Profil sendiri: tiap pengguna berhak menyunting kontak & sandinya.
            'profil', 'profil.simpan', 'profil.kata-sandi', 'profil.kata-sandi.simpan',
            // Halaman informasi umum.
            'tentang', 'panduan',
            // Dev-only, dijadwalkan dihapus sebelum penyerahan.
            'galeri-komponen', 'uji-403',
            // Template impor: hanya susunan kolom kosong + baris contoh, tanpa
            // data nyata. Tautannya hanya muncul di halaman modul yang sudah
            // menuntut `{modul}.lihat` untuk dibuka.
            'template-impor',
            // Pemeriksaan `{modul}.lihat` dinamis di dalam DokumenController.
            'dokumen.tampilkan',
        ];
    }
}
