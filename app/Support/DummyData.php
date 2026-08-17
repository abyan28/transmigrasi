<?php

namespace App\Support;

use App\Enums\CakupanData;
use App\Enums\JenisInfrastruktur;
use App\Enums\JenisLahan;
use App\Enums\KategoriLahan;
use App\Enums\KategoriPengaduan;
use App\Enums\KepemilikanAlsintan;
use App\Enums\Kondisi;
use App\Enums\KondisiRumah;
use App\Enums\PrioritasPengaduan;
use App\Enums\StatusHunian;
use App\Enums\StatusPengaduan;
use App\Enums\StatusTinggal;

use App\Enums\SumberLaporan;

/**
 * Penyedia data contoh untuk pembangunan antarmuka.
 *
 * Seluruh halaman Tahap 2 dibangun memakai data dari kelas ini, bukan dari
 * database, agar tampilan dapat divalidasi bersama pengguna sebelum backend
 * dikerjakan (agents/workflow.md bagian 2.2 Langkah B).
 *
 * PENTING: struktur array di sini sengaja dibuat sama persis dengan nama kolom
 * pada agents/data-dictionary.md. Ketika backend siap, penggantian cukup
 * menukar sumber datanya tanpa menyentuh satu pun berkas Blade.
 *
 * Data ini adalah CONTOH, bukan data nyata lapangan. Setiap halaman yang
 * memakainya wajib menampilkan penanda "Data contoh" agar tidak disalahartikan
 * sebagai angka sungguhan (ANTISLOP-ID R-17 dan R-38).
 */
class DummyData
{
    /**
     * Menandai bahwa aplikasi masih memakai data contoh.
     *
     * Dipakai layout untuk memutuskan apakah spanduk penanda perlu tampil.
     * Diubah menjadi false ketika backend sudah tersambung.
     */
    public const MEMAKAI_DATA_CONTOH = true;

    /**
     * Daftar kawasan transmigrasi.
     *
     * @return array<int, array<string, mixed>> Data kawasan
     */
    public static function kawasan(): array
    {
        return [
            [
                'id_kawasan_transmigrasi' => 1,
                'nama' => 'Kobalima Timur',
                'kabupaten' => 'Malaka',
                'provinsi' => 'Nusa Tenggara Timur',
                'kode_kawasan' => 'KWS-KBT',
                'tahun_penetapan' => 2015,
                'nomor_sk' => 'SK.123/MEN-TRANS/2015',
                'luas_total' => 4250.75,
                'jumlah_sp' => 6,
            ],
        ];
    }

    /**
     * Daftar satuan permukiman beserta wilayah administratifnya.
     *
     * Enam SP ini adalah lokus sebenarnya, tersebar di empat kecamatan
     * (agents/rules.md bagian 4a.5).
     *
     * @return array<int, array<string, mixed>> Data SP
     */
    public static function satuanPermukiman(): array
    {
        return [
            [
                'id_satuan_permukiman' => 1,
                'nama' => 'SP Kapitan Meo',
                'kode_sp' => 'SP-01',
                'desa' => 'Kapitan Meo',
                'kecamatan' => 'Laen Manen',
                'kawasan' => 'Kobalima Timur',
                'tahun_penempatan' => 2016,
                'luas_lahan' => 820.50,
                'jumlah_kk_rencana' => 250,
                'jumlah_kk_terisi' => 218,
                'lintang' => -9.5123450,
                'bujur' => 124.9123450,
                'batas_utara' => 'Desa Tniumanu',
                'batas_timur' => 'Sungai Benanain',
                'batas_selatan' => 'Desa Weain',
                'batas_barat' => 'Hutan lindung',
            ],
            [
                'id_satuan_permukiman' => 2,
                'nama' => 'SP Tniumanu',
                'kode_sp' => 'SP-02',
                'desa' => 'Tniumanu',
                'kecamatan' => 'Laen Manen',
                'kawasan' => 'Kobalima Timur',
                'tahun_penempatan' => 2016,
                'luas_lahan' => 645.25,
                'jumlah_kk_rencana' => 200,
                'jumlah_kk_terisi' => 187,
                'lintang' => -9.4980120,
                'bujur' => 124.8875600,
                'batas_utara' => 'Desa Harekakae',
                'batas_timur' => 'Desa Kapitan Meo',
                'batas_selatan' => 'Kebun rakyat',
                'batas_barat' => 'Jalan kabupaten',
            ],
            [
                'id_satuan_permukiman' => 3,
                'nama' => 'SP Harekakae',
                'kode_sp' => 'SP-03',
                'desa' => 'Harekakae',
                'kecamatan' => 'Malaka Tengah',
                'kawasan' => 'Kobalima Timur',
                'tahun_penempatan' => 2017,
                'luas_lahan' => 710.00,
                'jumlah_kk_rencana' => 220,
                'jumlah_kk_terisi' => 195,
                'lintang' => -9.4551230,
                'bujur' => 124.9450780,
                'batas_utara' => 'Persawahan Harekakae',
                'batas_timur' => 'Desa Weoe',
                'batas_selatan' => 'Desa Tniumanu',
                'batas_barat' => 'Sungai kecil',
            ],
            [
                'id_satuan_permukiman' => 4,
                'nama' => 'SP Weoe / Uluk Lubuk',
                'kode_sp' => 'SP-04',
                'desa' => 'Weoe',
                'kecamatan' => 'Wewiku',
                'kawasan' => 'Kobalima Timur',
                'tahun_penempatan' => 2017,
                'luas_lahan' => 690.00,
                'jumlah_kk_rencana' => 210,
                'jumlah_kk_terisi' => 176,
                'lintang' => -9.4210900,
                'bujur' => 124.9812340,
                'batas_utara' => 'Pantai selatan',
                'batas_timur' => 'Desa Naet',
                'batas_selatan' => 'Desa Harekakae',
                'batas_barat' => 'Lahan usaha warga',
            ],
            [
                'id_satuan_permukiman' => 5,
                'nama' => 'SP Tualaran',
                'kode_sp' => 'SP-05',
                'desa' => 'Naet',
                'kecamatan' => 'Rinhat',
                'kawasan' => 'Kobalima Timur',
                'tahun_penempatan' => 2018,
                'luas_lahan' => 735.50,
                'jumlah_kk_rencana' => 230,
                'jumlah_kk_terisi' => 201,
                'lintang' => -9.3987650,
                'bujur' => 125.0123450,
                'batas_utara' => 'Desa Weain',
                'batas_timur' => 'Perbukitan',
                'batas_selatan' => 'Desa Weoe',
                'batas_barat' => 'Jalan produksi',
            ],
            [
                'id_satuan_permukiman' => 6,
                'nama' => 'SP Weain',
                'kode_sp' => 'SP-06',
                'desa' => 'Weain',
                'kecamatan' => 'Rinhat',
                'kawasan' => 'Kobalima Timur',
                'tahun_penempatan' => 2018,
                'luas_lahan' => 649.50,
                'jumlah_kk_rencana' => 190,
                'jumlah_kk_terisi' => 163,
                'lintang' => -9.3765430,
                'bujur' => 125.0345670,
                'batas_utara' => 'Hutan produksi',
                'batas_timur' => 'Batas kabupaten',
                'batas_selatan' => 'Desa Naet',
                'batas_barat' => 'Sungai Benanain',
            ],
        ];
    }

    /**
     * Daftar transmigran beserta data kependudukannya.
     *
     * @return array<int, array<string, mixed>> Data transmigran
     */
    public static function transmigran(): array
    {
        return [
            [
                'id_transmigran' => 1,
                'nik' => '5321011505800001',
                'tempat_lahir' => 'KUPANG',
                'no_kk' => '5321010102150001',
                'nama_kepala_keluarga' => 'YOHANES BERE',
                'jenis_kelamin' => 'Laki-laki',
                'tanggal_lahir' => '1980-05-15',
                'pendidikan_terakhir' => 'SMA/SMK',
                'pekerjaan_kepala_keluarga' => 'PETANI',
                'jumlah_anggota_keluarga' => 5,
                'pendapatan_per_bulan' => 2350000,
                'daerah_asal' => 'KUPANG',
                'tahun_kedatangan' => 2016,
                'status_tinggal' => StatusTinggal::Aktif->value,
                'status_anggota_poktan' => 'Ya',
                'telepon' => '081234567801',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'satuan_permukiman_id' => 1,
            ],
            [
                'id_transmigran' => 2,
                'nik' => '5321012203850002',
                'tempat_lahir' => 'ATAMBUA',
                'no_kk' => '5321010102150002',
                'nama_kepala_keluarga' => 'MARIA DA COSTA',
                'jenis_kelamin' => 'Perempuan',
                'tanggal_lahir' => '1985-03-22',
                'pendidikan_terakhir' => 'SMP',
                'pekerjaan_kepala_keluarga' => 'PETANI',
                'jumlah_anggota_keluarga' => 4,
                'pendapatan_per_bulan' => 1850000,
                'daerah_asal' => 'BELU',
                'tahun_kedatangan' => 2016,
                'status_tinggal' => StatusTinggal::Aktif->value,
                'status_anggota_poktan' => 'Ya',
                'telepon' => '081234567802',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'satuan_permukiman_id' => 1,
            ],
            [
                'id_transmigran' => 3,
                'nik' => '5321010809780003',
                'tempat_lahir' => 'SOE',
                'no_kk' => '5321010102160003',
                'nama_kepala_keluarga' => 'PETRUS NAHAK',
                'jenis_kelamin' => 'Laki-laki',
                'tanggal_lahir' => '1978-09-08',
                'pendidikan_terakhir' => 'SD',
                'pekerjaan_kepala_keluarga' => 'PETANI',
                'jumlah_anggota_keluarga' => 6,
                'pendapatan_per_bulan' => 2100000,
                'daerah_asal' => 'TIMOR TENGAH SELATAN',
                'tahun_kedatangan' => 2016,
                'status_tinggal' => StatusTinggal::Aktif->value,
                'status_anggota_poktan' => 'Tidak',
                'telepon' => '081234567803',
                'satuan_permukiman' => 'SP Tniumanu',
                'satuan_permukiman_id' => 2,
                'satuan_permukiman_id' => 2,
            ],
            [
                'id_transmigran' => 4,
                'nik' => '5321011712900004',
                'tempat_lahir' => 'BETUN',
                'no_kk' => '5321010102170004',
                'nama_kepala_keluarga' => 'ANGELA SERAN',
                'jenis_kelamin' => 'Perempuan',
                'tanggal_lahir' => '1990-12-17',
                'pendidikan_terakhir' => 'SMA/SMK',
                'pekerjaan_kepala_keluarga' => 'PEDAGANG',
                'jumlah_anggota_keluarga' => 3,
                'pendapatan_per_bulan' => 2750000,
                'daerah_asal' => 'MALAKA',
                'tahun_kedatangan' => 2017,
                'status_tinggal' => StatusTinggal::Aktif->value,
                'status_anggota_poktan' => 'Tidak',
                'telepon' => '081234567804',
                'satuan_permukiman' => 'SP Harekakae',
                'satuan_permukiman_id' => 3,
                'satuan_permukiman_id' => 3,
            ],
            [
                'id_transmigran' => 5,
                'nik' => '5321010304820005',
                'tempat_lahir' => 'ATAMBUA',
                'no_kk' => '5321010102170005',
                'nama_kepala_keluarga' => 'DOMINGGUS TAEK',
                'jenis_kelamin' => 'Laki-laki',
                'tanggal_lahir' => '1982-04-03',
                'pendidikan_terakhir' => 'SMP',
                'pekerjaan_kepala_keluarga' => 'PETANI',
                'jumlah_anggota_keluarga' => 5,
                'pendapatan_per_bulan' => 1950000,
                'daerah_asal' => 'BELU',
                'tahun_kedatangan' => 2017,
                'status_tinggal' => StatusTinggal::Pindah->value,
                'status_anggota_poktan' => 'Ya',
                'telepon' => '081234567805',
                'satuan_permukiman' => 'SP Weoe / Uluk Lubuk',
                'satuan_permukiman_id' => 4,
                'satuan_permukiman_id' => 4,
            ],
            [
                'id_transmigran' => 6,
                'nik' => '5321012511870006',
                'tempat_lahir' => 'KUPANG',
                'no_kk' => '5321010102180006',
                'nama_kepala_keluarga' => 'FRANSISKA BRIA',
                'jenis_kelamin' => 'Perempuan',
                'tanggal_lahir' => '1987-11-25',
                'pendidikan_terakhir' => 'Diploma',
                'pekerjaan_kepala_keluarga' => 'GURU',
                'jumlah_anggota_keluarga' => 4,
                'pendapatan_per_bulan' => 3200000,
                'daerah_asal' => 'KUPANG',
                'tahun_kedatangan' => 2018,
                'status_tinggal' => StatusTinggal::Aktif->value,
                'status_anggota_poktan' => 'Tidak',
                'telepon' => '081234567806',
                'satuan_permukiman' => 'SP Tualaran',
                'satuan_permukiman_id' => 5,
                'satuan_permukiman_id' => 5,
            ],
            [
                'id_transmigran' => 7,
                'nik' => '5321010107750007',
                'tempat_lahir' => 'KEFAMENANU',
                'no_kk' => '5321010102180007',
                'nama_kepala_keluarga' => 'GABRIEL LEKI',
                'jenis_kelamin' => 'Laki-laki',
                'tanggal_lahir' => '1975-07-01',
                'pendidikan_terakhir' => 'SD',
                'pekerjaan_kepala_keluarga' => 'PETANI',
                'jumlah_anggota_keluarga' => 7,
                'pendapatan_per_bulan' => 1700000,
                'daerah_asal' => 'TIMOR TENGAH UTARA',
                'tahun_kedatangan' => 2018,
                'status_tinggal' => StatusTinggal::Aktif->value,
                'status_anggota_poktan' => 'Ya',
                'telepon' => '081234567807',
                'satuan_permukiman' => 'SP Weain',
                'satuan_permukiman_id' => 6,
                'satuan_permukiman_id' => 6,
            ],
            [
                'id_transmigran' => 8,
                'nik' => '5321011409910008',
                'tempat_lahir' => 'BETUN',
                'no_kk' => '5321010102190008',
                'nama_kepala_keluarga' => 'YULITA HOAR',
                'jenis_kelamin' => 'Perempuan',
                'tanggal_lahir' => '1991-09-14',
                'pendidikan_terakhir' => 'SMA/SMK',
                'pekerjaan_kepala_keluarga' => 'BURUH TANI',
                'jumlah_anggota_keluarga' => 3,
                'pendapatan_per_bulan' => 1450000,
                'daerah_asal' => 'MALAKA',
                'tahun_kedatangan' => 2019,
                'status_tinggal' => StatusTinggal::Aktif->value,
                'status_anggota_poktan' => 'Ya',
                'telepon' => '081234567808',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'satuan_permukiman_id' => 1,
            ],
        ];
    }

    /**
     * Daftar rumah beserta penghuninya.
     *
     * Satu rumah dihuni tepat satu KK, dan rumah kosong ditandai penghuni
     * bernilai null (agents/rules.md bagian 6a.5).
     *
     * @return array<int, array<string, mixed>> Data rumah
     */
    public static function rumah(): array
    {
        return [
            [
                'id_rumah' => 1,
                'no_rumah' => 'A-01',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'penghuni' => 'YOHANES BERE',
                'kondisi' => KondisiRumah::TidakRusak->value,
                'status_hunian' => StatusHunian::Dihuni->value,
                'tahun_pembangunan' => 2016,
                'luas_bangunan' => 36.00,
                'lintang' => -9.5124100,
                'bujur' => 124.9124200,
            ],
            [
                'id_rumah' => 2,
                'no_rumah' => 'A-02',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'penghuni' => 'MARIA DA COSTA',
                'kondisi' => KondisiRumah::RusakRingan->value,
                'status_hunian' => StatusHunian::Dihuni->value,
                'tahun_pembangunan' => 2016,
                'luas_bangunan' => 36.00,
                'lintang' => -9.5125300,
                'bujur' => 124.9125100,
            ],
            [
                'id_rumah' => 3,
                'no_rumah' => 'A-03',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'penghuni' => null,
                'kondisi' => KondisiRumah::RusakBerat->value,
                'status_hunian' => StatusHunian::TidakDihuni->value,
                'alasan_tidak_dihuni' => 'Atap rusak berat setelah angin kencang, sedang menunggu perbaikan.',
                'tahun_pembangunan' => 2016,
                'luas_bangunan' => 36.00,
                'lintang' => -9.5126400,
                'bujur' => 124.9126800,
            ],
            [
                'id_rumah' => 4,
                'no_rumah' => 'B-01',
                'satuan_permukiman' => 'SP Tniumanu',
                'satuan_permukiman_id' => 2,
                'penghuni' => 'PETRUS NAHAK',
                'kondisi' => KondisiRumah::TidakRusak->value,
                'status_hunian' => StatusHunian::Dihuni->value,
                'tahun_pembangunan' => 2016,
                'luas_bangunan' => 42.00,
                'lintang' => -9.4981200,
                'bujur' => 124.8876700,
            ],
            [
                'id_rumah' => 5,
                'no_rumah' => 'C-01',
                'satuan_permukiman' => 'SP Harekakae',
                'satuan_permukiman_id' => 3,
                'penghuni' => 'ANGELA SERAN',
                'kondisi' => KondisiRumah::TidakRusak->value,
                'status_hunian' => StatusHunian::Dihuni->value,
                'tahun_pembangunan' => 2017,
                'luas_bangunan' => 36.00,
                'lintang' => -9.4552100,
                'bujur' => 124.9451900,
            ],
            [
                'id_rumah' => 6,
                'no_rumah' => 'C-02',
                'satuan_permukiman' => 'SP Harekakae',
                'satuan_permukiman_id' => 3,
                'penghuni' => null,
                'kondisi' => KondisiRumah::TidakRusak->value,
                'status_hunian' => StatusHunian::TidakDihuni->value,
                'alasan_tidak_dihuni' => 'Belum ada penempatan keluarga baru.',
                'tahun_pembangunan' => 2017,
                'luas_bangunan' => 36.00,
                'lintang' => -9.4553300,
                'bujur' => 124.9452400,
            ],
        ];
    }

    /**
     * Riwayat pergantian penghuni sebuah rumah.
     *
     * Pergantian penghuni dicatat sebagai baris baru, tidak pernah menimpa
     * data penghuni sebelumnya (agents/rules.md bagian 6a poin 9). Baris
     * dengan `tanggal_keluar` bernilai null berarti masih menghuni.
     *
     * @param  int|null  $rumahId  Menyaring riwayat satu rumah; null berarti seluruhnya
     * @return array<int, array<string, mixed>> Riwayat penghunian
     */
    public static function riwayatPenghunian(?int $rumahId = null): array
    {
        $data = [
            [
                'id_riwayat_penghunian' => 1,
                'rumah_id' => 1,
                'no_rumah' => 'A-01',
                'transmigran' => 'YOHANES BERE',
                'transmigran_id' => 1,
                'tanggal_masuk' => '2016-07-12',
                'tanggal_keluar' => null,
                'alasan_keluar' => null,
                'keterangan' => 'Penempatan awal rombongan pertama.',
            ],
            [
                'id_riwayat_penghunian' => 2,
                'rumah_id' => 2,
                'no_rumah' => 'A-02',
                'transmigran' => 'MARIA DA COSTA',
                'transmigran_id' => 2,
                'tanggal_masuk' => '2016-07-12',
                'tanggal_keluar' => null,
                'alasan_keluar' => null,
                'keterangan' => 'Penempatan awal rombongan pertama.',
            ],
            [
                'id_riwayat_penghunian' => 3,
                'rumah_id' => 3,
                'no_rumah' => 'A-03',
                'transmigran' => 'DOMINGGUS TAEK',
                'transmigran_id' => 5,
                'tanggal_masuk' => '2017-03-04',
                'tanggal_keluar' => '2025-09-30',
                'alasan_keluar' => 'Pindah mengikuti keluarga ke SP Weoe.',
                'keterangan' => 'Rumah dikosongkan setelah kepergian, menunggu perbaikan atap.',
            ],
            [
                'id_riwayat_penghunian' => 4,
                'rumah_id' => 4,
                'no_rumah' => 'B-01',
                'transmigran' => 'PETRUS NAHAK',
                'transmigran_id' => 3,
                'tanggal_masuk' => '2016-08-20',
                'tanggal_keluar' => null,
                'alasan_keluar' => null,
                'keterangan' => null,
            ],
            [
                'id_riwayat_penghunian' => 5,
                'rumah_id' => 5,
                'no_rumah' => 'C-01',
                'transmigran' => 'ANGELA SERAN',
                'transmigran_id' => 4,
                'tanggal_masuk' => '2017-05-15',
                'tanggal_keluar' => null,
                'alasan_keluar' => null,
                'keterangan' => null,
            ],
        ];

        if ($rumahId === null) {
            return $data;
        }

        return array_values(array_filter($data, fn ($b) => $b['rumah_id'] === $rumahId));
    }

    /**
     * Daftar rumah yang belum berpenghuni.
     *
     * Saat menautkan KK ke rumah, sistem hanya boleh menawarkan rumah kosong
     * (agents/rules.md bagian 6a poin 8). Pembatasan ini menjaga aturan satu
     * rumah satu KK sejak di antarmuka, sebelum dijaga UNIQUE constraint.
     *
     * @return array<int, array<string, mixed>> Rumah tanpa penghuni
     */
    public static function rumahKosong(): array
    {
        return array_values(array_filter(self::rumah(), fn ($r) => $r['penghuni'] === null));
    }

    /**
     * Daftar transmigran yang belum menempati rumah mana pun.
     *
     * Sisi sebaliknya dari rumahKosong(): satu KK hanya boleh menempati satu
     * rumah, sehingga KK yang sudah punya rumah tidak ditawarkan lagi.
     *
     * @return array<int, array<string, mixed>> Transmigran tanpa rumah
     */
    public static function transmigranTanpaRumah(): array
    {
        $sudahPunya = array_filter(array_column(self::rumah(), 'penghuni'));

        return array_values(array_filter(
            self::transmigran(),
            fn ($t) => ! in_array($t['nama_kepala_keluarga'], $sudahPunya, true)
        ));
    }

    /**
     * Dokumen status lahan (HPL, SHM, dan sejenisnya).
     *
     * Dipisah dari tabel lahan karena satu lahan dapat memiliki lebih dari
     * satu dokumen (agents/data-dictionary.md bagian 7.2).
     *
     * @param  int|null  $lahanId  Menyaring dokumen satu lahan; null berarti seluruhnya
     * @return array<int, array<string, mixed>> Dokumen lahan
     */
    public static function dokumenLahan(?int $lahanId = null): array
    {
        $data = [
            [
                'id_dokumen_lahan' => 1,
                'lahan_id' => 1,
                'jenis_dokumen' => 'HPL',
                'nomor_dokumen' => 'HPL/NTT/2016/0142',
                'tanggal_terbit' => '2016-11-08',
                'file_dokumen' => 'lahan/1/HPL_yohanes-bere.pdf',
                'keterangan' => 'Hak pengelolaan lahan pekarangan.',
            ],
            [
                'id_dokumen_lahan' => 2,
                'lahan_id' => 2,
                'jenis_dokumen' => 'HPL',
                'nomor_dokumen' => 'HPL/NTT/2016/0143',
                'tanggal_terbit' => '2016-11-08',
                'file_dokumen' => 'lahan/2/HPL_yohanes-bere.pdf',
                'keterangan' => 'Hak pengelolaan lahan usaha satu.',
            ],
            [
                'id_dokumen_lahan' => 3,
                'lahan_id' => 5,
                'jenis_dokumen' => 'SHM',
                'nomor_dokumen' => 'SHM/MLK/2021/0871',
                'tanggal_terbit' => '2021-03-19',
                'file_dokumen' => 'lahan/5/SHM_maria-da-costa.pdf',
                'keterangan' => 'Sertifikat hak milik atas nama pemilik lahan.',
            ],
            [
                'id_dokumen_lahan' => 4,
                'lahan_id' => 6,
                'jenis_dokumen' => 'HPL',
                'nomor_dokumen' => 'HPL/NTT/2017/0219',
                'tanggal_terbit' => '2017-02-27',
                'file_dokumen' => 'lahan/6/HPL_petrus-nahak.pdf',
                'keterangan' => null,
            ],
        ];

        if ($lahanId === null) {
            return $data;
        }

        return array_values(array_filter($data, fn ($b) => $b['lahan_id'] === $lahanId));
    }

    /**
     * Data master satuan beserta faktor konversinya ke ton.
     *
     * Volume panen disimpan apa adanya sesuai satuan baku komoditas; konversi
     * hanya dilakukan saat rekap agar data asli lapangan tetap terjaga
     * (agents/rules.md bagian 8a poin 4 dan 5).
     *
     * @return array<string, float> Peta nama satuan ke faktor konversi ke ton
     */
    public static function faktorKonversiTon(): array
    {
        return [
            'Ton' => 1.0,
            'Kuintal' => 0.1,
            'Kilogram' => 0.001,
        ];
    }

    /**
     * Mengubah volume panen menjadi ton memakai faktor satuannya.
     *
     * Dipakai seluruh rekap dan dashboard agar penjumlahan lintas komoditas
     * tetap sepadan (agents/rules.md bagian 9 poin 5).
     *
     * @param  float  $volume  Volume dalam satuan aslinya
     * @param  string  $satuan  Nama satuan, contoh Kilogram
     * @return float Volume setara dalam ton
     */
    public static function keTon(float $volume, string $satuan): float
    {
        return $volume * (self::faktorKonversiTon()[$satuan] ?? 1.0);
    }

    /**
     * Daftar lahan milik transmigran.
     *
     * Satu transmigran boleh memiliki lebih dari satu lahan usaha
     * (agents/rules.md bagian 7.8).
     *
     * @return array<int, array<string, mixed>> Data lahan
     */
    public static function lahan(): array
    {
        return [
            [
                'id_lahan' => 1,
                'kode_lahan' => 'LP-001',
                'pemilik' => 'YOHANES BERE',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'jenis_lahan' => JenisLahan::LahanPekarangan->value,
                'kategori_lahan' => null,
                'luas' => 0.25,
                'status_kepemilikan' => 'HPL',
                'lintang' => -9.5124100,
                'bujur' => 124.9126200,
            ],
            [
                'id_lahan' => 2,
                'kode_lahan' => 'LU-001',
                'pemilik' => 'YOHANES BERE',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'jenis_lahan' => JenisLahan::LahanUsaha->value,
                'kategori_lahan' => KategoriLahan::LahanKering->value,
                'luas' => 1.50,
                'status_kepemilikan' => 'HPL',
                'pola_tanam' => 'MONOKULTUR JAGUNG',
                'lintang' => -9.5138400,
                'bujur' => 124.9152700,
            ],
            [
                'id_lahan' => 3,
                'kode_lahan' => 'LU-002',
                'pemilik' => 'YOHANES BERE',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'jenis_lahan' => JenisLahan::LahanUsaha->value,
                'kategori_lahan' => KategoriLahan::LahanBasah->value,
                'luas' => 0.75,
                'status_kepemilikan' => 'GARAPAN',
                'pola_tanam' => 'PADI SAWAH',
                'lintang' => -9.5471900,
                'bujur' => 124.8873500,
            ],
            [
                'id_lahan' => 4,
                'kode_lahan' => 'LP-002',
                'pemilik' => 'MARIA DA COSTA',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'jenis_lahan' => JenisLahan::LahanPekarangan->value,
                'kategori_lahan' => null,
                'luas' => 0.25,
                'status_kepemilikan' => 'HPL',
                'lintang' => -9.5483200,
                'bujur' => 124.8891000,
            ],
            [
                'id_lahan' => 5,
                'kode_lahan' => 'LU-003',
                'pemilik' => 'MARIA DA COSTA',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'jenis_lahan' => JenisLahan::LahanUsaha->value,
                'kategori_lahan' => KategoriLahan::LahanKering->value,
                'luas' => 2.00,
                'status_kepemilikan' => 'SHM',
                'pola_tanam' => 'TUMPANG SARI JAGUNG DAN KACANG',
                'lintang' => -9.4982600,
                'bujur' => 124.9411800,
            ],
            [
                'id_lahan' => 6,
                'kode_lahan' => 'LU-004',
                'pemilik' => 'PETRUS NAHAK',
                'satuan_permukiman' => 'SP Tniumanu',
                'satuan_permukiman_id' => 2,
                'jenis_lahan' => JenisLahan::LahanUsaha->value,
                'kategori_lahan' => KategoriLahan::LahanKering->value,
                'luas' => 1.25,
                'status_kepemilikan' => 'HPL',
                'pola_tanam' => 'MONOKULTUR JAGUNG',
                'lintang' => -9.4995300,
                'bujur' => 124.9438100,
            ],
        ];
    }

    /**
     * Daftar hasil panen.
     *
     * Volume disimpan apa adanya sesuai satuan baku komoditas, konversi ke ton
     * hanya dilakukan saat rekap (agents/rules.md bagian 8a).
     *
     * @return array<int, array<string, mixed>> Data hasil panen
     */
    public static function hasilPanen(): array
    {
        return [
            [
                'id_hasil_panen' => 1,
                'komoditas' => 'JAGUNG',
                'satuan' => 'Ton',
                'petani' => 'YOHANES BERE',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'musim_tanam' => 'MT1 2026',
                'tanggal_panen' => '2026-04-18',
                'volume' => 4.250,
                'kualitas' => 'Baik',
                'harga_jual' => 4500000,
            ],
            [
                'id_hasil_panen' => 2,
                'komoditas' => 'JAGUNG',
                'satuan' => 'Ton',
                'petani' => 'MARIA DA COSTA',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'musim_tanam' => 'MT1 2026',
                'tanggal_panen' => '2026-04-20',
                'volume' => 5.800,
                'kualitas' => 'Sangat Baik',
                'harga_jual' => 4750000,
            ],
            [
                'id_hasil_panen' => 3,
                'komoditas' => 'PADI',
                'satuan' => 'Ton',
                'petani' => 'YOHANES BERE',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'musim_tanam' => 'MT1 2026',
                'tanggal_panen' => '2026-05-02',
                'volume' => 2.100,
                'kualitas' => 'Baik',
                'harga_jual' => 5200000,
            ],
            [
                'id_hasil_panen' => 4,
                'komoditas' => 'CABAI',
                'satuan' => 'Kilogram',
                'petani' => 'PETRUS NAHAK',
                'satuan_permukiman' => 'SP Tniumanu',
                'satuan_permukiman_id' => 2,
                'musim_tanam' => 'MT1 2026',
                'tanggal_panen' => '2026-03-28',
                'volume' => 320.500,
                'kualitas' => 'Cukup',
                'harga_jual' => 28000,
                'keterangan_satuan_lokal' => 'Setara 13 karung ukuran sedang',
            ],
            [
                'id_hasil_panen' => 5,
                'komoditas' => 'JAGUNG',
                'satuan' => 'Ton',
                'petani' => 'GABRIEL LEKI',
                'satuan_permukiman' => 'SP Weain',
                'satuan_permukiman_id' => 6,
                'musim_tanam' => 'MT2 2025',
                'tanggal_panen' => '2025-11-15',
                'volume' => 3.900,
                'kualitas' => 'Baik',
                'harga_jual' => 4300000,
            ],
        ];
    }

    /**
     * Daftar pengaduan dari warga maupun yang dicatatkan petugas.
     *
     * @return array<int, array<string, mixed>> Data pengaduan
     */
    public static function pengaduan(): array
    {
        return [
            [
                'id_pengaduan' => 1,
                'nomor_pengaduan' => 'PGD-2026-0001',
                'tanggal_pengaduan' => '2026-08-02',
                'nama_pelapor' => 'YOHANES BERE',
                'kontak_pelapor' => '081234567801',
                'sumber_laporan' => SumberLaporan::Publik->value,
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'kategori' => KategoriPengaduan::Infrastruktur->value,
                'bidang' => 'Ketransmigrasian',
                'judul' => 'Saluran irigasi tersumbat',
                'deskripsi' => 'Saluran irigasi di blok A tersumbat sejak awal musim hujan, air tidak sampai ke lahan usaha.',
                'status' => StatusPengaduan::Diproses->value,
                'lintang' => -9.5131500,
                'bujur' => 124.9139800,
                'prioritas' => PrioritasPengaduan::Tinggi->value,
            ],
            [
                'id_pengaduan' => 2,
                'nomor_pengaduan' => 'PGD-2026-0002',
                'tanggal_pengaduan' => '2026-08-05',
                'nama_pelapor' => 'MARIA DA COSTA',
                'kontak_pelapor' => '081234567802',
                'sumber_laporan' => SumberLaporan::Publik->value,
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'kategori' => KategoriPengaduan::Rumah->value,
                'bidang' => 'Ketransmigrasian',
                'judul' => 'Atap rumah bocor',
                'deskripsi' => 'Atap bagian belakang bocor cukup parah ketika hujan deras.',
                'status' => StatusPengaduan::Diterima->value,
                'prioritas' => PrioritasPengaduan::Sedang->value,
            ],
            [
                'id_pengaduan' => 3,
                'nomor_pengaduan' => 'PGD-2026-0003',
                'tanggal_pengaduan' => '2026-08-08',
                'nama_pelapor' => 'PETRUS NAHAK',
                'kontak_pelapor' => '081234567803',
                'sumber_laporan' => SumberLaporan::Petugas->value,
                'satuan_permukiman' => 'SP Tniumanu',
                'satuan_permukiman_id' => 2,
                'kategori' => KategoriPengaduan::Alsintan->value,
                'bidang' => 'Pertanian',
                'judul' => 'Traktor bantuan tidak dapat dinyalakan',
                'deskripsi' => 'Traktor roda dua bantuan tahun lalu tidak dapat dinyalakan sejak dua pekan terakhir.',
                'status' => StatusPengaduan::MenungguDiterima->value,
                'lintang' => -9.5479200,
                'bujur' => 124.8886400,
                'prioritas' => PrioritasPengaduan::Sedang->value,
            ],
            [
                'id_pengaduan' => 4,
                'nomor_pengaduan' => 'PGD-2026-0004',
                'tanggal_pengaduan' => '2026-08-09',
                'nama_pelapor' => 'FRANSISKA BRIA',
                'kontak_pelapor' => '081234567806',
                'sumber_laporan' => SumberLaporan::Publik->value,
                'satuan_permukiman' => 'SP Tualaran',
                'satuan_permukiman_id' => 5,
                'kategori' => KategoriPengaduan::Bencana->value,
                'bidang' => 'Ketransmigrasian',
                'judul' => 'Longsor kecil di jalan produksi',
                'deskripsi' => 'Terjadi longsor kecil menutup sebagian jalan produksi menuju lahan usaha.',
                'status' => StatusPengaduan::MenungguDiterima->value,
                'lintang' => -9.4991000,
                'bujur' => 124.9430200,
                'prioritas' => PrioritasPengaduan::Mendesak->value,
            ],
            [
                'id_pengaduan' => 5,
                'nomor_pengaduan' => 'PGD-2026-0005',
                'tanggal_pengaduan' => '2026-07-28',
                'nama_pelapor' => 'GABRIEL LEKI',
                'kontak_pelapor' => '081234567807',
                'sumber_laporan' => SumberLaporan::Publik->value,
                'satuan_permukiman' => 'SP Weain',
                'satuan_permukiman_id' => 6,
                'kategori' => KategoriPengaduan::ProduksiPanen->value,
                'bidang' => 'Pertanian',
                'judul' => 'Serangan hama pada tanaman jagung',
                'deskripsi' => 'Sebagian tanaman jagung terserang hama ulat, mohon pendampingan penyuluh.',
                'status' => StatusPengaduan::Selesai->value,
                'prioritas' => PrioritasPengaduan::Tinggi->value,
            ],
        ];
    }

    /**
     * Riwayat penanganan untuk sebuah pengaduan.
     *
     * Kunci `dokumen_tindak_lanjut` bersifat opsional: tidak setiap langkah
     * penanganan menghasilkan berkas. Yang menghasilkan biasanya langkah
     * peninjauan lapangan dan penutupan laporan, sebab keduanya melahirkan
     * berita acara.
     *
     * @param  string  $nomorPengaduan  Nomor pengaduan yang dicari
     * @return array<int, array<string, mixed>> Riwayat penanganan
     */
    public static function penangananPengaduan(string $nomorPengaduan = 'PGD-2026-0001'): array
    {
        $data = [
            'PGD-2026-0001' => [
                [
                    'tanggal_penanganan' => '2026-08-03',
                    'petugas' => 'NARA WIJAYA',
                    'status_sebelum' => StatusPengaduan::MenungguDiterima->value,
                    'status_sesudah' => StatusPengaduan::Diterima->value,
                    'catatan' => 'Pengaduan diterima dan dijadwalkan peninjauan lapangan.',
                    'dokumen_tindak_lanjut' => null,
                ],
                [
                    'tanggal_penanganan' => '2026-08-06',
                    'petugas' => 'NARA WIJAYA',
                    'status_sebelum' => StatusPengaduan::Diterima->value,
                    'status_sesudah' => StatusPengaduan::Diproses->value,
                    'catatan' => 'Peninjauan selesai. Pembersihan saluran dijadwalkan pekan depan bersama warga.',
                    'dokumen_tindak_lanjut' => 'pengaduan/1/BeritaAcaraPeninjauan_pgd-2026-0001.pdf',
                ],
            ],
            'PGD-2026-0002' => [
                [
                    'tanggal_penanganan' => '2026-08-06',
                    'petugas' => 'SITI RAHMAWATI',
                    'status_sebelum' => StatusPengaduan::MenungguDiterima->value,
                    'status_sesudah' => StatusPengaduan::Diterima->value,
                    'catatan' => 'Laporan diterima, menunggu jadwal peninjauan kondisi atap.',
                    'dokumen_tindak_lanjut' => null,
                ],
            ],
            'PGD-2026-0005' => [
                [
                    'tanggal_penanganan' => '2026-07-29',
                    'petugas' => 'AGUS PRASETYO',
                    'status_sebelum' => StatusPengaduan::MenungguDiterima->value,
                    'status_sesudah' => StatusPengaduan::Diterima->value,
                    'catatan' => 'Laporan serangan hama diterima Dinas Pertanian.',
                    'dokumen_tindak_lanjut' => null,
                ],
                [
                    'tanggal_penanganan' => '2026-08-01',
                    'petugas' => 'AGUS PRASETYO',
                    'status_sebelum' => StatusPengaduan::Diterima->value,
                    'status_sesudah' => StatusPengaduan::Diproses->value,
                    'catatan' => 'Penyuluh meninjau lahan dan mengambil sampel tanaman terserang.',
                    'dokumen_tindak_lanjut' => 'pengaduan/5/HasilPemeriksaanHama_pgd-2026-0005.pdf',
                ],
                [
                    'tanggal_penanganan' => '2026-08-04',
                    'petugas' => 'AGUS PRASETYO',
                    'status_sebelum' => StatusPengaduan::Diproses->value,
                    'status_sesudah' => StatusPengaduan::Selesai->value,
                    'catatan' => 'Pendampingan penyemprotan selesai, kondisi tanaman membaik. Petani diberi panduan pengendalian hama.',
                    'dokumen_tindak_lanjut' => 'pengaduan/5/BeritaAcaraPenyelesaian_pgd-2026-0005.pdf',
                ],
            ],
        ];

        return $data[$nomorPengaduan] ?? [];
    }

    /**
     * Rekap pengaduan menurut kolom pengelompokan tertentu.
     *
     * Dipakai halaman rekap pengaduan untuk melihat sebaran per kategori,
     * status, dan SP sebagai sumber indikator isu prioritas
     * (agents/rules.md bagian 10b poin 8).
     *
     * @param  string  $kelompok  Salah satu: kategori, status, sp, prioritas, bidang
     * @return array<int, array<string, mixed>> Rekap terurut dari terbanyak
     */
    public static function rekapPengaduan(string $kelompok = 'kategori'): array
    {
        $peta = [];

        foreach (self::pengaduan() as $p) {
            $kunci = match ($kelompok) {
                'status' => $p['status'],
                'sp' => $p['satuan_permukiman'],
                'prioritas' => $p['prioritas'],
                'bidang' => $p['bidang'],
                default => $p['kategori'],
            };

            if (! isset($peta[$kunci])) {
                $peta[$kunci] = [
                    'nama' => $kunci,
                    'jumlah' => 0,
                    'selesai' => 0,
                    'belum_selesai' => 0,
                    'mendesak' => 0,
                ];
            }

            $peta[$kunci]['jumlah']++;

            if ($p['status'] === StatusPengaduan::Selesai->value) {
                $peta[$kunci]['selesai']++;
            } else {
                $peta[$kunci]['belum_selesai']++;
            }

            if ($p['prioritas'] === PrioritasPengaduan::Mendesak->value) {
                $peta[$kunci]['mendesak']++;
            }
        }

        $hasil = array_values($peta);
        usort($hasil, fn ($a, $b) => $b['jumlah'] <=> $a['jumlah']);

        return $hasil;
    }

    /**
     * Daftar aset infrastruktur SP.
     *
     * @return array<int, array<string, mixed>> Data infrastruktur
     */
    public static function infrastruktur(): array
    {
        return [
            [
                'id_infrastruktur' => 1,
                'nama' => 'SALURAN IRIGASI BLOK A',
                'jenis' => JenisInfrastruktur::Irigasi->value,
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'tahun_perolehan' => 2016,
                'sumber_dana' => 'APBN',
                'kondisi' => Kondisi::RusakRingan->value,
                'kapasitas' => 'Panjang 1,2 km',
            ],
            [
                'id_infrastruktur' => 2,
                'nama' => 'SUMUR BOR TENGAH',
                'jenis' => JenisInfrastruktur::Air->value,
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'tahun_perolehan' => 2017,
                'sumber_dana' => 'APBD Kabupaten',
                'kondisi' => Kondisi::Baik->value,
                'kapasitas' => 'Debit 3 liter per detik',
            ],
            [
                'id_infrastruktur' => 3,
                'nama' => 'JALAN PRODUKSI UTARA',
                'jenis' => JenisInfrastruktur::JalanProduksi->value,
                'satuan_permukiman' => 'SP Tniumanu',
                'satuan_permukiman_id' => 2,
                'tahun_perolehan' => 2018,
                'sumber_dana' => 'APBD Provinsi',
                'kondisi' => Kondisi::RusakBerat->value,
                'kapasitas' => 'Panjang 2,4 km',
            ],
            [
                'id_infrastruktur' => 4,
                'nama' => 'GUDANG PASCAPANEN',
                'jenis' => JenisInfrastruktur::Gudang->value,
                'satuan_permukiman' => 'SP Harekakae',
                'satuan_permukiman_id' => 3,
                'tahun_perolehan' => 2019,
                'sumber_dana' => 'Dinas Pertanian Kabupaten',
                'kondisi' => Kondisi::Baik->value,
                'kapasitas' => 'Daya tampung 40 ton',
            ],

            /*
             * Aset di bawah melengkapi keenam SP agar penilaian kondisi SP
             * dapat diperagakan dengan variasi yang bermakna:
             *
             * - SP Kapitan Meo  : layanan dasar lengkap, sebagian kecil rusak
             * - SP Tniumanu     : jalan penghubung rusak berat
             * - SP Harekakae    : cukup lengkap
             * - SP Weoe         : tanpa listrik sama sekali, menguji aturan primer nol
             * - SP Tualaran     : layanan dasar ada, penunjang minim
             * - SP Weain        : tanpa air bersih dan tanpa jalan penghubung
             */

            // SP Kapitan Meo
            ['id_infrastruktur' => 5, 'nama' => 'JARINGAN LISTRIK PLN', 'jenis' => JenisInfrastruktur::Listrik->value, 'satuan_permukiman' => 'SP Kapitan Meo', 'satuan_permukiman_id' => 1, 'tahun_perolehan' => 2017, 'sumber_dana' => 'APBN', 'kondisi' => Kondisi::Baik->value, 'kapasitas' => 'Melayani 218 KK'],
            ['id_infrastruktur' => 6, 'nama' => 'JALAN MASUK KAWASAN', 'jenis' => JenisInfrastruktur::JalanPenghubung->value, 'satuan_permukiman' => 'SP Kapitan Meo', 'satuan_permukiman_id' => 1, 'tahun_perolehan' => 2016, 'sumber_dana' => 'APBN', 'kondisi' => Kondisi::Baik->value, 'kapasitas' => 'Panjang 4,2 km, beraspal'],
            ['id_infrastruktur' => 7, 'nama' => 'MENARA TELEKOMUNIKASI', 'jenis' => JenisInfrastruktur::Telekomunikasi->value, 'satuan_permukiman' => 'SP Kapitan Meo', 'satuan_permukiman_id' => 1, 'tahun_perolehan' => 2020, 'sumber_dana' => 'APBD Provinsi', 'kondisi' => Kondisi::Baik->value, 'kapasitas' => 'Jangkauan 3 km'],
            ['id_infrastruktur' => 8, 'nama' => 'SANITASI KOMUNAL', 'jenis' => JenisInfrastruktur::Sanitasi->value, 'satuan_permukiman' => 'SP Kapitan Meo', 'satuan_permukiman_id' => 1, 'tahun_perolehan' => 2018, 'sumber_dana' => 'APBD Kabupaten', 'kondisi' => Kondisi::RusakRingan->value, 'kapasitas' => 'Melayani 80 KK'],
            ['id_infrastruktur' => 9, 'nama' => 'KIOS SAPROTAN DESA', 'jenis' => JenisInfrastruktur::PasarKios->value, 'satuan_permukiman' => 'SP Kapitan Meo', 'satuan_permukiman_id' => 1, 'tahun_perolehan' => 2021, 'sumber_dana' => 'Swadaya', 'kondisi' => Kondisi::Baik->value, 'kapasitas' => 'Melayani 3 SP sekitar'],

            // SP Tniumanu
            ['id_infrastruktur' => 10, 'nama' => 'SUMUR BOR TNIUMANU', 'jenis' => JenisInfrastruktur::Air->value, 'satuan_permukiman' => 'SP Tniumanu', 'satuan_permukiman_id' => 2, 'tahun_perolehan' => 2017, 'sumber_dana' => 'APBN', 'kondisi' => Kondisi::Baik->value, 'kapasitas' => 'Debit 2,5 liter per detik'],
            ['id_infrastruktur' => 11, 'nama' => 'JARINGAN LISTRIK PLN', 'jenis' => JenisInfrastruktur::Listrik->value, 'satuan_permukiman' => 'SP Tniumanu', 'satuan_permukiman_id' => 2, 'tahun_perolehan' => 2018, 'sumber_dana' => 'APBN', 'kondisi' => Kondisi::RusakRingan->value, 'kapasitas' => 'Sering padam saat hujan'],
            ['id_infrastruktur' => 12, 'nama' => 'JALAN MASUK KAWASAN', 'jenis' => JenisInfrastruktur::JalanPenghubung->value, 'satuan_permukiman' => 'SP Tniumanu', 'satuan_permukiman_id' => 2, 'tahun_perolehan' => 2016, 'sumber_dana' => 'APBD Kabupaten', 'kondisi' => Kondisi::RusakBerat->value, 'kapasitas' => 'Panjang 6,8 km, berlubang parah'],

            // SP Harekakae
            ['id_infrastruktur' => 13, 'nama' => 'SUMUR BOR HAREKAKAE', 'jenis' => JenisInfrastruktur::Air->value, 'satuan_permukiman' => 'SP Harekakae', 'satuan_permukiman_id' => 3, 'tahun_perolehan' => 2017, 'sumber_dana' => 'APBN', 'kondisi' => Kondisi::Baik->value, 'kapasitas' => 'Debit 4 liter per detik'],
            ['id_infrastruktur' => 14, 'nama' => 'JARINGAN LISTRIK PLN', 'jenis' => JenisInfrastruktur::Listrik->value, 'satuan_permukiman' => 'SP Harekakae', 'satuan_permukiman_id' => 3, 'tahun_perolehan' => 2018, 'sumber_dana' => 'APBN', 'kondisi' => Kondisi::Baik->value, 'kapasitas' => 'Melayani 195 KK'],
            ['id_infrastruktur' => 15, 'nama' => 'JALAN MASUK KAWASAN', 'jenis' => JenisInfrastruktur::JalanPenghubung->value, 'satuan_permukiman' => 'SP Harekakae', 'satuan_permukiman_id' => 3, 'tahun_perolehan' => 2017, 'sumber_dana' => 'APBD Provinsi', 'kondisi' => Kondisi::RusakRingan->value, 'kapasitas' => 'Panjang 3,5 km'],
            ['id_infrastruktur' => 16, 'nama' => 'MENARA TELEKOMUNIKASI', 'jenis' => JenisInfrastruktur::Telekomunikasi->value, 'satuan_permukiman' => 'SP Harekakae', 'satuan_permukiman_id' => 3, 'tahun_perolehan' => 2021, 'sumber_dana' => 'APBD Provinsi', 'kondisi' => Kondisi::Baik->value, 'kapasitas' => 'Jangkauan 2,5 km'],

            // SP Weoe, sengaja TANPA listrik untuk menguji aturan primer nol
            ['id_infrastruktur' => 17, 'nama' => 'SUMUR BOR WEOE', 'jenis' => JenisInfrastruktur::Air->value, 'satuan_permukiman' => 'SP Weoe / Uluk Lubuk', 'satuan_permukiman_id' => 4, 'tahun_perolehan' => 2018, 'sumber_dana' => 'APBN', 'kondisi' => Kondisi::Baik->value, 'kapasitas' => 'Debit 3 liter per detik'],
            ['id_infrastruktur' => 18, 'nama' => 'JALAN MASUK KAWASAN', 'jenis' => JenisInfrastruktur::JalanPenghubung->value, 'satuan_permukiman' => 'SP Weoe / Uluk Lubuk', 'satuan_permukiman_id' => 4, 'tahun_perolehan' => 2017, 'sumber_dana' => 'APBD Kabupaten', 'kondisi' => Kondisi::Baik->value, 'kapasitas' => 'Panjang 5,1 km'],
            ['id_infrastruktur' => 19, 'nama' => 'IRIGASI BLOK BARAT', 'jenis' => JenisInfrastruktur::Irigasi->value, 'satuan_permukiman' => 'SP Weoe / Uluk Lubuk', 'satuan_permukiman_id' => 4, 'tahun_perolehan' => 2018, 'sumber_dana' => 'APBN', 'kondisi' => Kondisi::Baik->value, 'kapasitas' => 'Mengairi 120 ha'],

            // SP Tualaran
            ['id_infrastruktur' => 20, 'nama' => 'SUMUR BOR TUALARAN', 'jenis' => JenisInfrastruktur::Air->value, 'satuan_permukiman' => 'SP Tualaran', 'satuan_permukiman_id' => 5, 'tahun_perolehan' => 2019, 'sumber_dana' => 'APBN', 'kondisi' => Kondisi::Baik->value, 'kapasitas' => 'Debit 2,8 liter per detik'],
            ['id_infrastruktur' => 21, 'nama' => 'JARINGAN LISTRIK PLN', 'jenis' => JenisInfrastruktur::Listrik->value, 'satuan_permukiman' => 'SP Tualaran', 'satuan_permukiman_id' => 5, 'tahun_perolehan' => 2019, 'sumber_dana' => 'APBN', 'kondisi' => Kondisi::Baik->value, 'kapasitas' => 'Melayani 201 KK'],
            ['id_infrastruktur' => 22, 'nama' => 'JALAN MASUK KAWASAN', 'jenis' => JenisInfrastruktur::JalanPenghubung->value, 'satuan_permukiman' => 'SP Tualaran', 'satuan_permukiman_id' => 5, 'tahun_perolehan' => 2018, 'sumber_dana' => 'APBD Provinsi', 'kondisi' => Kondisi::RusakRingan->value, 'kapasitas' => 'Panjang 7,4 km'],

            // SP Weain, sengaja TANPA air bersih dan TANPA jalan penghubung
            ['id_infrastruktur' => 23, 'nama' => 'JARINGAN LISTRIK PLN', 'jenis' => JenisInfrastruktur::Listrik->value, 'satuan_permukiman' => 'SP Weain', 'satuan_permukiman_id' => 6, 'tahun_perolehan' => 2020, 'sumber_dana' => 'APBN', 'kondisi' => Kondisi::RusakRingan->value, 'kapasitas' => 'Melayani 163 KK'],
            ['id_infrastruktur' => 24, 'nama' => 'GUDANG PASCAPANEN', 'jenis' => JenisInfrastruktur::Gudang->value, 'satuan_permukiman' => 'SP Weain', 'satuan_permukiman_id' => 6, 'tahun_perolehan' => 2021, 'sumber_dana' => 'Dinas Pertanian Kabupaten', 'kondisi' => Kondisi::Baik->value, 'kapasitas' => 'Daya tampung 25 ton'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Data master wilayah dan aset SP
    |--------------------------------------------------------------------------
    */

    /**
     * Wilayah administratif bertingkat.
     *
     * Hierarki bercabang dua di tingkat kabupaten: cabang administratif
     * (kecamatan, desa) dan cabang program (kawasan transmigrasi), bertemu
     * di satuan permukiman (agents/rules.md bagian 4a).
     *
     * @return array<string, array<int, array<string, mixed>>> Peta tingkat ke daftar wilayah
     */
    public static function wilayah(): array
    {
        return [
            'provinsi' => [
                ['id_provinsi' => 1, 'nama' => 'Nusa Tenggara Timur', 'kode' => '53'],
            ],
            'kabupaten' => [
                ['id_kabupaten' => 1, 'provinsi_id' => 1, 'provinsi' => 'Nusa Tenggara Timur', 'nama' => 'Malaka', 'kode' => '5321'],
            ],
            'kecamatan' => [
                ['id_kecamatan' => 1, 'kabupaten_id' => 1, 'kabupaten' => 'Malaka', 'nama' => 'Laen Manen', 'jumlah_desa' => 2],
                ['id_kecamatan' => 2, 'kabupaten_id' => 1, 'kabupaten' => 'Malaka', 'nama' => 'Malaka Tengah', 'jumlah_desa' => 1],
                ['id_kecamatan' => 3, 'kabupaten_id' => 1, 'kabupaten' => 'Malaka', 'nama' => 'Wewiku', 'jumlah_desa' => 1],
                ['id_kecamatan' => 4, 'kabupaten_id' => 1, 'kabupaten' => 'Malaka', 'nama' => 'Rinhat', 'jumlah_desa' => 2],
            ],
            'desa' => [
                ['id_desa' => 1, 'kecamatan_id' => 1, 'kecamatan' => 'Laen Manen', 'nama' => 'Kapitan Meo', 'jumlah_sp' => 1],
                ['id_desa' => 2, 'kecamatan_id' => 1, 'kecamatan' => 'Laen Manen', 'nama' => 'Tniumanu', 'jumlah_sp' => 1],
                ['id_desa' => 3, 'kecamatan_id' => 2, 'kecamatan' => 'Malaka Tengah', 'nama' => 'Harekakae', 'jumlah_sp' => 1],
                ['id_desa' => 4, 'kecamatan_id' => 3, 'kecamatan' => 'Wewiku', 'nama' => 'Weoe', 'jumlah_sp' => 1],
                ['id_desa' => 5, 'kecamatan_id' => 4, 'kecamatan' => 'Rinhat', 'nama' => 'Naet', 'jumlah_sp' => 1],
                ['id_desa' => 6, 'kecamatan_id' => 4, 'kecamatan' => 'Rinhat', 'nama' => 'Weain', 'jumlah_sp' => 1],
            ],
        ];
    }

    /**
     * Inventaris SP, yaitu barang bergerak milik satuan permukiman.
     *
     * @return array<int, array<string, mixed>> Data inventaris
     */
    public static function inventarisSp(): array
    {
        return [
            ['id_inventaris_sp' => 1, 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'nama_barang' => 'MEJA KANTOR', 'jumlah' => 12, 'satuan_barang' => 'unit', 'tahun_perolehan' => 2016, 'sumber_dana' => 'APBN', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'keterangan' => null],
            ['id_inventaris_sp' => 2, 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'nama_barang' => 'KURSI PLASTIK', 'jumlah' => 60, 'satuan_barang' => 'buah', 'tahun_perolehan' => 2016, 'sumber_dana' => 'APBN', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Rusak Ringan', 'keterangan' => 'Sebagian retak pada sandaran.'],
            ['id_inventaris_sp' => 3, 'satuan_permukiman_id' => 2, 'satuan_permukiman' => 'SP Tniumanu', 'nama_barang' => 'GENSET 5000 WATT', 'jumlah' => 1, 'satuan_barang' => 'unit', 'tahun_perolehan' => 2018, 'sumber_dana' => 'APBD Kabupaten', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'keterangan' => null],
            ['id_inventaris_sp' => 4, 'satuan_permukiman_id' => 3, 'satuan_permukiman' => 'SP Harekakae', 'nama_barang' => 'KOMPUTER DESKTOP', 'jumlah' => 2, 'satuan_barang' => 'unit', 'tahun_perolehan' => 2019, 'sumber_dana' => 'Dinas Transmigrasi Kabupaten', 'status_penyerahan' => 'Dalam Proses', 'kondisi' => 'Baik', 'keterangan' => 'Berita acara sedang diproses.'],
            ['id_inventaris_sp' => 5, 'satuan_permukiman_id' => 5, 'satuan_permukiman' => 'SP Tualaran', 'nama_barang' => 'LEMARI ARSIP', 'jumlah' => 4, 'satuan_barang' => 'unit', 'tahun_perolehan' => 2019, 'sumber_dana' => 'APBD Provinsi', 'status_penyerahan' => 'Belum Diserahkan', 'kondisi' => 'Baik', 'keterangan' => null],
        ];
    }

    /**
     * Fasilitas SP, yaitu bangunan dan sarana tetap.
     *
     * Dipisah dari inventaris agar rekap aset dapat dibedakan
     * (agents/rules.md bagian 4b poin 1).
     *
     * @return array<int, array<string, mixed>> Data fasilitas
     */
    public static function fasilitasSp(): array
    {
        return [
            ['id_fasilitas_sp' => 1, 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'jenis_fasilitas' => 'Balai Pertemuan', 'nama_fasilitas' => 'BALAI PERTEMUAN', 'jumlah' => 1, 'tahun_perolehan' => 2016, 'sumber_dana' => 'APBN', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'lintang' => -9.5124500, 'bujur' => 124.9125000, 'keterangan' => null],
            ['id_fasilitas_sp' => 2, 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'jenis_fasilitas' => 'Kesehatan', 'nama_fasilitas' => 'PUSKESMAS PEMBANTU', 'jumlah' => 1, 'tahun_perolehan' => 2017, 'sumber_dana' => 'APBD Kabupaten', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'lintang' => -9.5127000, 'bujur' => 124.9128000, 'keterangan' => null],
            ['id_fasilitas_sp' => 3, 'satuan_permukiman_id' => 2, 'satuan_permukiman' => 'SP Tniumanu', 'jenis_fasilitas' => 'Pendidikan Dasar', 'nama_fasilitas' => 'SEKOLAH DASAR', 'jumlah' => 1, 'tahun_perolehan' => 2016, 'sumber_dana' => 'APBN', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Rusak Ringan', 'lintang' => -9.4982000, 'bujur' => 124.8878000, 'keterangan' => 'Plafon ruang kelas dua bocor.'],
            ['id_fasilitas_sp' => 4, 'satuan_permukiman_id' => 3, 'satuan_permukiman' => 'SP Harekakae', 'jenis_fasilitas' => 'Ibadah', 'nama_fasilitas' => 'RUMAH IBADAH', 'jumlah' => 2, 'tahun_perolehan' => 2017, 'sumber_dana' => 'Lembaga Swadaya Masyarakat', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'lintang' => -9.4554000, 'bujur' => 124.9453000, 'keterangan' => null],
            ['id_fasilitas_sp' => 5, 'satuan_permukiman_id' => 6, 'satuan_permukiman' => 'SP Weain', 'jenis_fasilitas' => 'Keamanan', 'nama_fasilitas' => 'POS KAMLING', 'jumlah' => 3, 'tahun_perolehan' => 2018, 'sumber_dana' => 'APBD Provinsi', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Rusak Berat', 'lintang' => -9.3766000, 'bujur' => 125.0346000, 'keterangan' => 'Dua pos lapuk dimakan rayap.'],

            /*
             * Fasilitas di bawah melengkapi sebaran layanan sosial agar
             * penilaian kondisi SP memperlihatkan variasi status yang wajar,
             * bukan seluruhnya Perlu Penanganan.
             */
            ['id_fasilitas_sp' => 6, 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'jenis_fasilitas' => 'Pendidikan Dasar', 'nama_fasilitas' => 'SD INPRES KAPITAN MEO', 'jumlah' => 1, 'tahun_perolehan' => 2016, 'sumber_dana' => 'APBN', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'lintang' => -9.5122000, 'bujur' => 124.9121000, 'keterangan' => null],
            ['id_fasilitas_sp' => 7, 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'jenis_fasilitas' => 'Ibadah', 'nama_fasilitas' => 'GEREJA STASI KAPITAN MEO', 'jumlah' => 1, 'tahun_perolehan' => 2017, 'sumber_dana' => 'Swadaya', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'lintang' => -9.5129000, 'bujur' => 124.9130000, 'keterangan' => null],
            ['id_fasilitas_sp' => 8, 'satuan_permukiman_id' => 3, 'satuan_permukiman' => 'SP Harekakae', 'jenis_fasilitas' => 'Kesehatan', 'nama_fasilitas' => 'POSKESDES HAREKAKAE', 'jumlah' => 1, 'tahun_perolehan' => 2018, 'sumber_dana' => 'APBD Kabupaten', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'lintang' => -9.4556000, 'bujur' => 124.9455000, 'keterangan' => null],
            ['id_fasilitas_sp' => 9, 'satuan_permukiman_id' => 3, 'satuan_permukiman' => 'SP Harekakae', 'jenis_fasilitas' => 'Pendidikan Dasar', 'nama_fasilitas' => 'SD NEGERI HAREKAKAE', 'jumlah' => 1, 'tahun_perolehan' => 2017, 'sumber_dana' => 'APBN', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'lintang' => -9.4557000, 'bujur' => 124.9458000, 'keterangan' => null],
            ['id_fasilitas_sp' => 10, 'satuan_permukiman_id' => 3, 'satuan_permukiman' => 'SP Harekakae', 'jenis_fasilitas' => 'Balai Pertemuan', 'nama_fasilitas' => 'BALAI DESA HAREKAKAE', 'jumlah' => 1, 'tahun_perolehan' => 2018, 'sumber_dana' => 'APBD Kabupaten', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'lintang' => -9.4559000, 'bujur' => 124.9460000, 'keterangan' => null],
            ['id_fasilitas_sp' => 11, 'satuan_permukiman_id' => 2, 'satuan_permukiman' => 'SP Tniumanu', 'jenis_fasilitas' => 'Kesehatan', 'nama_fasilitas' => 'POSKESDES TNIUMANU', 'jumlah' => 1, 'tahun_perolehan' => 2019, 'sumber_dana' => 'APBD Kabupaten', 'status_penyerahan' => 'Dalam Proses', 'kondisi' => 'Rusak Ringan', 'lintang' => -9.4984000, 'bujur' => 124.8880000, 'keterangan' => 'Atap ruang periksa bocor.'],
            ['id_fasilitas_sp' => 12, 'satuan_permukiman_id' => 5, 'satuan_permukiman' => 'SP Tualaran', 'jenis_fasilitas' => 'Pendidikan Dasar', 'nama_fasilitas' => 'SD INPRES NAET', 'jumlah' => 1, 'tahun_perolehan' => 2019, 'sumber_dana' => 'APBN', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'lintang' => -9.3989000, 'bujur' => 125.0125000, 'keterangan' => null],
            ['id_fasilitas_sp' => 13, 'satuan_permukiman_id' => 5, 'satuan_permukiman' => 'SP Tualaran', 'jenis_fasilitas' => 'Ibadah', 'nama_fasilitas' => 'GEREJA STASI NAET', 'jumlah' => 1, 'tahun_perolehan' => 2020, 'sumber_dana' => 'Swadaya', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'lintang' => -9.3991000, 'bujur' => 125.0128000, 'keterangan' => null],
        ];
    }

    /**
     * Data master satuan beserta faktor konversinya.
     *
     * Bentuk tabel dari faktorKonversiTon(), dipakai halaman data master.
     *
     * @return array<int, array<string, mixed>> Data satuan
     */
    public static function satuan(): array
    {
        return [
            ['id_satuan' => 1, 'nama' => 'Ton', 'simbol' => 't', 'faktor_ke_ton' => 1.0, 'dipakai_komoditas' => 2],
            ['id_satuan' => 2, 'nama' => 'Kuintal', 'simbol' => 'kw', 'faktor_ke_ton' => 0.1, 'dipakai_komoditas' => 2],
            ['id_satuan' => 3, 'nama' => 'Kilogram', 'simbol' => 'kg', 'faktor_ke_ton' => 0.001, 'dipakai_komoditas' => 1],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Kelembagaan dan sarana pertanian
    |--------------------------------------------------------------------------
    */

    /**
     * Kelompok tani beserta profil ketuanya.
     *
     * @return array<int, array<string, mixed>> Data poktan
     */
    public static function poktan(): array
    {
        return [
            ['id_poktan' => 1, 'nama' => 'POKTAN MEKAR JAYA', 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'nama_ketua' => 'YOHANES BERE', 'nik_ketua' => '5321011505800001', 'telepon_ketua' => '081234567801', 'email_ketua' => 'mekarjaya@example.id', 'tahun_berdiri' => 2016, 'jumlah_anggota' => 24, 'lintang' => -9.5127800, 'bujur' => 124.9131400],
            ['id_poktan' => 2, 'nama' => 'POKTAN SUBUR MAKMUR', 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'nama_ketua' => 'MARIA DA COSTA', 'nik_ketua' => '5321012203850002', 'telepon_ketua' => '081234567802', 'email_ketua' => null, 'tahun_berdiri' => 2017, 'jumlah_anggota' => 18, 'lintang' => -9.5476500, 'bujur' => 124.8882300],
            ['id_poktan' => 3, 'nama' => 'POKTAN TANI BERSATU', 'satuan_permukiman_id' => 2, 'satuan_permukiman' => 'SP Tniumanu', 'nama_ketua' => 'PETRUS NAHAK', 'nik_ketua' => '5321010809780003', 'telepon_ketua' => '081234567803', 'email_ketua' => null, 'tahun_berdiri' => 2017, 'jumlah_anggota' => 21, 'lintang' => -9.4988700, 'bujur' => 124.9425600],
            ['id_poktan' => 4, 'nama' => 'POKTAN HARAPAN BARU', 'satuan_permukiman_id' => 6, 'satuan_permukiman' => 'SP Weain', 'nama_ketua' => 'GABRIEL LEKI', 'nik_ketua' => '5321010107750007', 'telepon_ketua' => '081234567807', 'email_ketua' => null, 'tahun_berdiri' => 2019, 'jumlah_anggota' => 15, 'lintang' => -9.5731200, 'bujur' => 124.8654900],
        ];
    }

    /**
     * Anggota kelompok tani.
     *
     * Anggota yang berhenti ditandai berstatus Sudah Keluar, bukan dihapus,
     * agar riwayat tetap utuh (agents/rules.md bagian 5.1 catatan 5).
     *
     * @param  int|null  $poktanId  Menyaring anggota satu poktan; null berarti seluruhnya
     * @return array<int, array<string, mixed>> Data anggota
     */
    public static function anggotaPoktan(?int $poktanId = null): array
    {
        $data = [
            ['id_anggota_poktan' => 1, 'poktan_id' => 1, 'poktan' => 'POKTAN MEKAR JAYA', 'transmigran_id' => 1, 'nama' => 'YOHANES BERE', 'nik' => '5321011505800001', 'jabatan' => 'Ketua', 'tanggal_masuk' => '2016-08-01', 'tanggal_keluar' => null, 'status' => 'Aktif'],
            ['id_anggota_poktan' => 2, 'poktan_id' => 1, 'poktan' => 'POKTAN MEKAR JAYA', 'transmigran_id' => 2, 'nama' => 'MARIA DA COSTA', 'nik' => '5321012203850002', 'jabatan' => 'Sekretaris', 'tanggal_masuk' => '2016-08-01', 'tanggal_keluar' => null, 'status' => 'Aktif'],
            ['id_anggota_poktan' => 3, 'poktan_id' => 1, 'poktan' => 'POKTAN MEKAR JAYA', 'transmigran_id' => 8, 'nama' => 'YULITA HOAR', 'nik' => '5321011409910008', 'jabatan' => 'Anggota', 'tanggal_masuk' => '2019-03-12', 'tanggal_keluar' => null, 'status' => 'Aktif'],
            ['id_anggota_poktan' => 4, 'poktan_id' => 1, 'poktan' => 'POKTAN MEKAR JAYA', 'transmigran_id' => 5, 'nama' => 'DOMINGGUS TAEK', 'nik' => '5321010304820005', 'jabatan' => 'Anggota', 'tanggal_masuk' => '2017-05-20', 'tanggal_keluar' => '2025-09-30', 'status' => 'Sudah Keluar'],
            ['id_anggota_poktan' => 5, 'poktan_id' => 3, 'poktan' => 'POKTAN TANI BERSATU', 'transmigran_id' => 3, 'nama' => 'PETRUS NAHAK', 'nik' => '5321010809780003', 'jabatan' => 'Ketua', 'tanggal_masuk' => '2017-02-15', 'tanggal_keluar' => null, 'status' => 'Aktif'],
            ['id_anggota_poktan' => 6, 'poktan_id' => 4, 'poktan' => 'POKTAN HARAPAN BARU', 'transmigran_id' => 7, 'nama' => 'GABRIEL LEKI', 'nik' => '5321010107750007', 'jabatan' => 'Ketua', 'tanggal_masuk' => '2019-01-10', 'tanggal_keluar' => null, 'status' => 'Aktif'],
            ['id_anggota_poktan' => 7, 'poktan_id' => 4, 'poktan' => 'POKTAN HARAPAN BARU', 'transmigran_id' => 6, 'nama' => 'FRANSISKA BRIA', 'nik' => '5321012511870006', 'jabatan' => 'Bendahara', 'tanggal_masuk' => '2019-01-10', 'tanggal_keluar' => null, 'status' => 'Tidak Aktif'],
        ];

        if ($poktanId === null) {
            return $data;
        }

        return array_values(array_filter($data, fn ($b) => $b['poktan_id'] === $poktanId));
    }

    /**
     * Alat dan mesin pertanian.
     *
     * Dibedakan antara milik pribadi transmigran dan bantuan pemerintah yang
     * disalurkan lewat poktan (agents/rules.md bagian 7b poin 1).
     *
     * @return array<int, array<string, mixed>> Data alsintan
     */
    public static function alsintan(): array
    {
        return [
            ['id_alsintan' => 1, 'nama_alat' => 'TRAKTOR RODA DUA', 'jumlah' => 2, 'tahun_perolehan' => 2018, 'sumber_perolehan' => 'APBN', 'kepemilikan' => KepemilikanAlsintan::BantuanPoktan->value, 'pemilik' => 'POKTAN MEKAR JAYA', 'poktan_id' => 1, 'transmigran_id' => null, 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'kondisi' => 'Baik'],
            ['id_alsintan' => 2, 'nama_alat' => 'POMPA AIR', 'jumlah' => 3, 'tahun_perolehan' => 2019, 'sumber_perolehan' => 'APBD Kabupaten', 'kepemilikan' => KepemilikanAlsintan::BantuanPoktan->value, 'pemilik' => 'POKTAN MEKAR JAYA', 'poktan_id' => 1, 'transmigran_id' => null, 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'kondisi' => 'Rusak Ringan'],
            ['id_alsintan' => 3, 'nama_alat' => 'HAND SPRAYER', 'jumlah' => 1, 'tahun_perolehan' => 2021, 'sumber_perolehan' => 'Pembelian Sendiri', 'kepemilikan' => KepemilikanAlsintan::Pribadi->value, 'pemilik' => 'YOHANES BERE', 'poktan_id' => null, 'transmigran_id' => 1, 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'kondisi' => 'Baik'],
            ['id_alsintan' => 4, 'nama_alat' => 'MESIN PERONTOK JAGUNG', 'jumlah' => 1, 'tahun_perolehan' => 2020, 'sumber_perolehan' => 'Dinas Pertanian Kabupaten', 'kepemilikan' => KepemilikanAlsintan::BantuanPoktan->value, 'pemilik' => 'POKTAN TANI BERSATU', 'poktan_id' => 3, 'transmigran_id' => null, 'satuan_permukiman_id' => 2, 'satuan_permukiman' => 'SP Tniumanu', 'kondisi' => 'Rusak Berat'],
            ['id_alsintan' => 5, 'nama_alat' => 'CANGKUL', 'jumlah' => 8, 'tahun_perolehan' => 2019, 'sumber_perolehan' => 'Pembelian Sendiri', 'kepemilikan' => KepemilikanAlsintan::Pribadi->value, 'pemilik' => 'GABRIEL LEKI', 'poktan_id' => null, 'transmigran_id' => 7, 'satuan_permukiman_id' => 6, 'satuan_permukiman' => 'SP Weain', 'kondisi' => 'Baik'],
        ];
    }

    /**
     * Penyaluran sarana produksi pertanian.
     *
     * Penyaluran kepada anggota poktan hanya untuk anggota berstatus aktif
     * (agents/rules.md bagian 7c poin 4).
     *
     * @return array<int, array<string, mixed>> Data saprotan
     */
    public static function saprotan(): array
    {
        return [
            ['id_saprotan' => 1, 'jenis' => 'Benih', 'nama' => 'BENIH JAGUNG HIBRIDA', 'jumlah' => 250.0, 'satuan' => 'Kilogram', 'tanggal_perolehan' => '2026-01-15', 'sumber' => 'Dinas Pertanian Kabupaten', 'penerima' => 'POKTAN MEKAR JAYA', 'jenis_penerima' => 'Poktan', 'poktan_id' => 1, 'transmigran_id' => null, 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo'],
            ['id_saprotan' => 2, 'jenis' => 'Pupuk', 'nama' => 'PUPUK UREA', 'jumlah' => 1200.0, 'satuan' => 'Kilogram', 'tanggal_perolehan' => '2026-01-20', 'sumber' => 'APBN', 'penerima' => 'POKTAN MEKAR JAYA', 'jenis_penerima' => 'Poktan', 'poktan_id' => 1, 'transmigran_id' => null, 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo'],
            ['id_saprotan' => 3, 'jenis' => 'Pestisida', 'nama' => 'INSEKTISIDA CAIR', 'jumlah' => 40.0, 'satuan' => 'Liter', 'tanggal_perolehan' => '2026-02-08', 'sumber' => 'Dinas Pertanian Kabupaten', 'penerima' => 'POKTAN TANI BERSATU', 'jenis_penerima' => 'Poktan', 'poktan_id' => 3, 'transmigran_id' => null, 'satuan_permukiman_id' => 2, 'satuan_permukiman' => 'SP Tniumanu'],
            ['id_saprotan' => 4, 'jenis' => 'Benih', 'nama' => 'BENIH PADI IR64', 'jumlah' => 80.0, 'satuan' => 'Kilogram', 'tanggal_perolehan' => '2026-02-12', 'sumber' => 'APBD Provinsi', 'penerima' => 'YOHANES BERE', 'jenis_penerima' => 'Individu', 'poktan_id' => null, 'transmigran_id' => 1, 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo'],
            ['id_saprotan' => 5, 'jenis' => 'Mulsa', 'nama' => 'MULSA PLASTIK HITAM PERAK', 'jumlah' => 15.0, 'satuan' => 'Rol', 'tanggal_perolehan' => '2026-03-02', 'sumber' => 'Lembaga Swadaya Masyarakat', 'penerima' => 'POKTAN HARAPAN BARU', 'jenis_penerima' => 'Poktan', 'poktan_id' => 4, 'transmigran_id' => null, 'satuan_permukiman_id' => 6, 'satuan_permukiman' => 'SP Weain'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Produksi pertanian
    |--------------------------------------------------------------------------
    */

    /**
     * Data master komoditas beserta satuan panen bakunya.
     *
     * Satuan ditetapkan per komoditas, misalnya jagung dalam ton dan cabai
     * dalam kilogram (agents/rules.md bagian 8 poin 4).
     *
     * @return array<int, array<string, mixed>> Data komoditas
     */
    public static function komoditas(): array
    {
        return [
            ['id_komoditas' => 1, 'nama' => 'JAGUNG', 'tipe' => 'Pangan', 'satuan' => 'Ton', 'satuan_id' => 1, 'is_unggulan' => true, 'deskripsi' => 'Komoditas utama kawasan, ditanam hampir seluruh keluarga.'],
            ['id_komoditas' => 2, 'nama' => 'PADI', 'tipe' => 'Pangan', 'satuan' => 'Ton', 'satuan_id' => 1, 'is_unggulan' => false, 'deskripsi' => 'Ditanam pada lahan basah di sebagian SP.'],
            ['id_komoditas' => 3, 'nama' => 'KACANG TANAH', 'tipe' => 'Palawija', 'satuan' => 'Kuintal', 'satuan_id' => 2, 'is_unggulan' => false, 'deskripsi' => null],
            ['id_komoditas' => 4, 'nama' => 'UBI KAYU', 'tipe' => 'Palawija', 'satuan' => 'Kuintal', 'satuan_id' => 2, 'is_unggulan' => false, 'deskripsi' => null],
            ['id_komoditas' => 5, 'nama' => 'CABAI', 'tipe' => 'Hortikultura', 'satuan' => 'Kilogram', 'satuan_id' => 3, 'is_unggulan' => false, 'deskripsi' => 'Ditanam pada lahan pekarangan.'],
        ];
    }

    /**
     * Musim tanam.
     *
     * Nama dan tahun dipisah agar grafik per tahun dapat dihitung; SQL
     * referensi hanya menyediakan teks bebas (agents/erd.md 8.2 nomor 22).
     *
     * @return array<int, array<string, mixed>> Data musim tanam
     */
    public static function musimTanam(): array
    {
        return [
            ['id_musim_tanam' => 1, 'nama' => 'MT1', 'tahun' => 2026, 'label' => 'MT1 2026', 'tanggal_mulai' => '2025-11-01', 'tanggal_selesai' => '2026-05-31', 'jumlah_tanam' => 4, 'keterangan' => 'Musim hujan, penanaman utama jagung.'],
            ['id_musim_tanam' => 2, 'nama' => 'MT2', 'tahun' => 2025, 'label' => 'MT2 2025', 'tanggal_mulai' => '2025-06-01', 'tanggal_selesai' => '2025-10-31', 'jumlah_tanam' => 2, 'keterangan' => 'Musim kemarau, bergantung irigasi.'],
            ['id_musim_tanam' => 3, 'nama' => 'MT1', 'tahun' => 2025, 'label' => 'MT1 2025', 'tanggal_mulai' => '2024-11-01', 'tanggal_selesai' => '2025-05-31', 'jumlah_tanam' => 3, 'keterangan' => null],
        ];
    }

    /**
     * Riwayat penanaman: lahan mana, musim apa, komoditas apa.
     *
     * @return array<int, array<string, mixed>> Data riwayat tanam
     */
    public static function riwayatTanam(): array
    {
        return [
            ['id_riwayat_tanam' => 1, 'lahan_id' => 2, 'kode_lahan' => 'LU-001', 'petani' => 'YOHANES BERE', 'musim_tanam' => 'MT1 2026', 'komoditas' => 'JAGUNG', 'luas_tanam' => 1.50, 'tanggal_tanam' => '2025-11-20', 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo'],
            ['id_riwayat_tanam' => 2, 'lahan_id' => 3, 'kode_lahan' => 'LU-002', 'petani' => 'YOHANES BERE', 'musim_tanam' => 'MT1 2026', 'komoditas' => 'PADI', 'luas_tanam' => 0.75, 'tanggal_tanam' => '2025-12-05', 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo'],
            ['id_riwayat_tanam' => 3, 'lahan_id' => 5, 'kode_lahan' => 'LU-003', 'petani' => 'MARIA DA COSTA', 'musim_tanam' => 'MT1 2026', 'komoditas' => 'JAGUNG', 'luas_tanam' => 2.00, 'tanggal_tanam' => '2025-11-18', 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo'],
            ['id_riwayat_tanam' => 4, 'lahan_id' => 6, 'kode_lahan' => 'LU-004', 'petani' => 'PETRUS NAHAK', 'musim_tanam' => 'MT1 2026', 'komoditas' => 'CABAI', 'luas_tanam' => 0.30, 'tanggal_tanam' => '2025-12-01', 'satuan_permukiman_id' => 2, 'satuan_permukiman' => 'SP Tniumanu'],
            ['id_riwayat_tanam' => 5, 'lahan_id' => 2, 'kode_lahan' => 'LU-001', 'petani' => 'YOHANES BERE', 'musim_tanam' => 'MT2 2025', 'komoditas' => 'JAGUNG', 'luas_tanam' => 1.50, 'tanggal_tanam' => '2025-06-10', 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Sistem: pengguna, role, audit log
    |--------------------------------------------------------------------------
    */

    /**
     * Daftar akun pengguna sistem.
     *
     * Seluruh pengguna adalah petugas; warga tidak memiliki akun
     * (agents/rules.md bagian 5.0 poin 5).
     *
     * @return array<int, array<string, mixed>> Data pengguna
     */
    public static function pengguna(): array
    {
        return [
            ['id_user' => 1, 'nama' => 'NARA WIJAYA', 'username' => 'nara.wijaya', 'email' => 'nara.wijaya@malakakab.go.id', 'role' => 'Dinas Transmigrasi', 'role_id' => 2, 'jabatan' => 'Staf Bidang Ketransmigrasian', 'telepon' => '081234567890', 'is_aktif' => true, 'password_harus_diganti' => false, 'last_login_at' => '2026-08-11 07:42:00', 'satuan_permukiman' => []],
            ['id_user' => 2, 'nama' => 'SITI RAHMAWATI', 'username' => 'siti.rahmawati', 'email' => 'siti.r@malakakab.go.id', 'role' => 'Admin', 'role_id' => 1, 'jabatan' => 'Administrator Sistem', 'telepon' => '081234567891', 'is_aktif' => true, 'password_harus_diganti' => false, 'last_login_at' => '2026-08-11 08:15:00', 'satuan_permukiman' => []],
            ['id_user' => 3, 'nama' => 'AGUS PRASETYO', 'username' => 'agus.prasetyo', 'email' => 'agus.p@malakakab.go.id', 'role' => 'Dinas Pertanian', 'role_id' => 3, 'jabatan' => 'Penyuluh Pertanian', 'telepon' => '081234567892', 'is_aktif' => true, 'password_harus_diganti' => false, 'last_login_at' => '2026-08-10 14:20:00', 'satuan_permukiman' => []],
            ['id_user' => 4, 'nama' => 'YOSEP KLAU', 'username' => 'yosep.klau', 'email' => 'yosep.klau@malakakab.go.id', 'role' => 'Operator SP', 'role_id' => 4, 'jabatan' => 'Operator SP Kapitan Meo', 'telepon' => '081234567893', 'is_aktif' => true, 'password_harus_diganti' => true, 'last_login_at' => null, 'satuan_permukiman' => ['SP Kapitan Meo']],
            ['id_user' => 5, 'nama' => 'MARIA GORETI', 'username' => 'maria.goreti', 'email' => 'maria.g@malakakab.go.id', 'role' => 'Operator SP', 'role_id' => 4, 'jabatan' => 'Operator SP Tniumanu', 'telepon' => '081234567894', 'is_aktif' => false, 'password_harus_diganti' => false, 'last_login_at' => '2026-05-02 09:00:00', 'satuan_permukiman' => ['SP Tniumanu']],
        ];
    }

    /**
     * Role beserta cakupan data dan jumlah izinnya.
     *
     * @return array<int, array<string, mixed>> Data role
     */
    public static function role(): array
    {
        return [
            ['id_role' => 1, 'nama' => 'Admin', 'deskripsi' => 'Akses penuh termasuk manajemen pengguna, role, dan audit log.', 'cakupan_data' => CakupanData::Semua->value, 'is_bawaan' => true, 'is_terkunci' => true, 'is_aktif' => true, 'jumlah_izin' => 117, 'jumlah_pengguna' => 1],
            ['id_role' => 2, 'nama' => 'Dinas Transmigrasi', 'deskripsi' => 'Mengelola data wilayah, transmigran, rumah, lahan, dan infrastruktur.', 'cakupan_data' => CakupanData::Semua->value, 'is_bawaan' => true, 'is_terkunci' => false, 'is_aktif' => true, 'jumlah_izin' => 57, 'jumlah_pengguna' => 1],
            ['id_role' => 3, 'nama' => 'Dinas Pertanian', 'deskripsi' => 'Mengelola data poktan, komoditas, panen, alsintan, dan saprotan.', 'cakupan_data' => CakupanData::Semua->value, 'is_bawaan' => true, 'is_terkunci' => false, 'is_aktif' => true, 'jumlah_izin' => 64, 'jumlah_pengguna' => 1],
            ['id_role' => 4, 'nama' => 'Operator SP', 'deskripsi' => 'Memasukkan data pada satuan permukiman yang ditugaskan. Tanpa kewenangan hapus.', 'cakupan_data' => CakupanData::PerSp->value, 'is_bawaan' => true, 'is_terkunci' => false, 'is_aktif' => true, 'jumlah_izin' => 50, 'jumlah_pengguna' => 2],

            // Role buatan Admin, bukan bawaan sistem. Sengaja dibuat tanpa
            // pengguna agar keadaan "dapat dihapus" ikut terlihat pada
            // antarmuka; keempat role bawaan di atas tidak akan pernah
            // menampilkannya (rules.md 5.0c poin 8 dan 9).
            ['id_role' => 5, 'nama' => 'Pendamping Lapangan', 'deskripsi' => 'Memantau perkembangan kawasan tanpa kewenangan mengubah data. Disusun Admin untuk pendamping yang bertugas sementara.', 'cakupan_data' => CakupanData::PerSp->value, 'is_bawaan' => false, 'is_terkunci' => false, 'is_aktif' => true, 'jumlah_izin' => 16, 'jumlah_pengguna' => 0],
        ];
    }

    /**
     * Catatan audit log perubahan data.
     *
     * @return array<int, array<string, mixed>> Data audit log
     */
    public static function auditLog(): array
    {
        return [
            ['id_audit_log' => 1, 'waktu' => '2026-08-11 08:20:14', 'pengguna' => 'SITI RAHMAWATI', 'aksi' => 'Ubah', 'nama_tabel' => 'transmigran', 'record_id' => 1, 'ringkasan' => 'Memperbaiki ejaan nama YOHANES BERE sesuai kartu keluarga.', 'ip_address' => '10.14.2.31'],
            ['id_audit_log' => 2, 'waktu' => '2026-08-11 08:04:52', 'pengguna' => 'YOSEP KLAU', 'aksi' => 'Tambah', 'nama_tabel' => 'hasil_panen', 'record_id' => 5, 'ringkasan' => 'Mencatat panen jagung 3,900 ton di SP Weain.', 'ip_address' => '10.14.2.77'],
            ['id_audit_log' => 3, 'waktu' => '2026-08-10 16:32:09', 'pengguna' => 'NARA WIJAYA', 'aksi' => 'Ubah', 'nama_tabel' => 'rumah', 'record_id' => 3, 'ringkasan' => 'Mengubah status hunian A-03 menjadi Tidak Dihuni.', 'ip_address' => '10.14.2.31'],
            // Penutupan pengaduan tercatat sebagai perubahan status, bukan
            // verifikasi data, sebab yang berubah adalah tahap penanganannya.
            ['id_audit_log' => 4, 'waktu' => '2026-08-10 14:22:41', 'pengguna' => 'AGUS PRASETYO', 'aksi' => 'Ubah', 'nama_tabel' => 'pengaduan', 'record_id' => 5, 'ringkasan' => 'Menutup pengaduan PGD-2026-0005 berstatus Selesai.', 'ip_address' => '10.14.2.55'],
            ['id_audit_log' => 5, 'waktu' => '2026-08-09 11:07:33', 'pengguna' => 'SITI RAHMAWATI', 'aksi' => 'Reset Kata Sandi', 'nama_tabel' => 'user', 'record_id' => 4, 'ringkasan' => 'Menyetel ulang kata sandi akun yosep.klau.', 'ip_address' => '10.14.2.31'],
            ['id_audit_log' => 6, 'waktu' => '2026-08-08 09:45:12', 'pengguna' => 'SITI RAHMAWATI', 'aksi' => 'Nonaktifkan Akun', 'nama_tabel' => 'user', 'record_id' => 5, 'ringkasan' => 'Menonaktifkan akun maria.goreti atas permintaan dinas.', 'ip_address' => '10.14.2.31'],
            ['id_audit_log' => 7, 'waktu' => '2026-08-07 15:18:55', 'pengguna' => 'NARA WIJAYA', 'aksi' => 'Hapus', 'nama_tabel' => 'transmigran', 'record_id' => 4, 'ringkasan' => 'Menghapus data ANGELA SERAN yang terdaftar ganda.', 'ip_address' => '10.14.2.31'],
            ['id_audit_log' => 8, 'waktu' => '2026-08-06 10:02:19', 'pengguna' => 'YOSEP KLAU', 'aksi' => 'Hapus', 'nama_tabel' => 'lahan', 'record_id' => 9, 'ringkasan' => 'Menghapus data lahan duplikat LU-009.', 'ip_address' => '10.14.2.77'],

            /*
                Jejak di bawah menopang tab Catatan Log pada halaman rincian.
                Setiap entitas contoh yang dapat dibuka rinciannya perlu punya
                minimal satu entri `Tambah`, sebab pertanyaan pertama pembaca
                riwayat selalu sama: siapa yang memasukkan data ini.

                Transmigran 1 sengaja diberi rangkaian terpanjang agar keadaan
                riwayat bertumpuk ikut teruji, sedangkan transmigran 2 sengaja
                dibiarkan tanpa jejak sama sekali agar keadaan kosong juga
                terlihat pada antarmuka.
            */
            ['id_audit_log' => 9, 'waktu' => '2026-03-14 09:12:40', 'pengguna' => 'YOSEP KLAU', 'aksi' => 'Tambah', 'nama_tabel' => 'transmigran', 'record_id' => 1, 'ringkasan' => 'Menambahkan data kepala keluarga YOHANES BERE.', 'ip_address' => '10.14.2.77'],
            ['id_audit_log' => 10, 'waktu' => '2026-05-22 14:38:05', 'pengguna' => 'YOSEP KLAU', 'aksi' => 'Ubah', 'nama_tabel' => 'transmigran', 'record_id' => 1, 'ringkasan' => 'Memperbarui jumlah anggota keluarga menjadi 5 orang.', 'ip_address' => '10.14.2.77'],
            ['id_audit_log' => 11, 'waktu' => '2026-04-02 10:25:17', 'pengguna' => 'YOSEP KLAU', 'aksi' => 'Tambah', 'nama_tabel' => 'rumah', 'record_id' => 1, 'ringkasan' => 'Menambahkan data rumah A-01 beserta penghuninya.', 'ip_address' => '10.14.2.77'],
            ['id_audit_log' => 12, 'waktu' => '2026-04-02 10:41:03', 'pengguna' => 'YOSEP KLAU', 'aksi' => 'Tambah', 'nama_tabel' => 'lahan', 'record_id' => 1, 'ringkasan' => 'Menambahkan lahan pekarangan LP-001 milik YOHANES BERE.', 'ip_address' => '10.14.2.77'],
            ['id_audit_log' => 13, 'waktu' => '2026-06-18 11:03:52', 'pengguna' => 'NARA WIJAYA', 'aksi' => 'Ubah', 'nama_tabel' => 'lahan', 'record_id' => 1, 'ringkasan' => 'Melengkapi titik koordinat lahan hasil peninjauan lapangan.', 'ip_address' => '10.14.2.31'],
            ['id_audit_log' => 14, 'waktu' => '2026-05-09 08:55:31', 'pengguna' => 'AGUS PRASETYO', 'aksi' => 'Tambah', 'nama_tabel' => 'poktan', 'record_id' => 1, 'ringkasan' => 'Mendaftarkan kelompok tani POKTAN MEKAR JAYA.', 'ip_address' => '10.14.2.55'],
            ['id_audit_log' => 15, 'waktu' => '2026-08-02 13:47:26', 'pengguna' => 'MARIA GORETI', 'aksi' => 'Tambah', 'nama_tabel' => 'pengaduan', 'record_id' => 1, 'ringkasan' => 'Mencatat pengaduan PGD-2026-0001 dari warga SP Kapitan Meo.', 'ip_address' => '10.14.2.91'],
            /*
                Jejak bagi lima modul aset dan produksi. Tanpa entri di sini,
                tab Catatan Log pada halaman rinciannya akan selalu kosong
                sehingga bentuk jadinya tidak pernah terlihat.

                Saprotan SENGAJA dibiarkan tanpa jejak sama sekali, agar
                keadaan kosong ikut teruji pada halaman sungguhan. Pasangannya
                di sisi kependudukan adalah transmigran 2.
            */
            ['id_audit_log' => 16, 'waktu' => '2026-02-18 09:33:12', 'pengguna' => 'AGUS PRASETYO', 'aksi' => 'Tambah', 'nama_tabel' => 'alsintan', 'record_id' => 1, 'ringkasan' => 'Mencatat bantuan traktor roda dua untuk POKTAN MEKAR JAYA.', 'ip_address' => '10.14.2.55'],
            ['id_audit_log' => 17, 'waktu' => '2026-07-11 15:20:44', 'pengguna' => 'AGUS PRASETYO', 'aksi' => 'Ubah', 'nama_tabel' => 'alsintan', 'record_id' => 1, 'ringkasan' => 'Memperbarui kondisi traktor menjadi Rusak Ringan setelah pemeriksaan.', 'ip_address' => '10.14.2.55'],
            ['id_audit_log' => 18, 'waktu' => '2026-01-27 10:14:58', 'pengguna' => 'NARA WIJAYA', 'aksi' => 'Tambah', 'nama_tabel' => 'infrastruktur', 'record_id' => 1, 'ringkasan' => 'Mendata jalan penghubung utama SP Kapitan Meo.', 'ip_address' => '10.14.2.31'],
            ['id_audit_log' => 19, 'waktu' => '2026-06-30 13:52:07', 'pengguna' => 'NARA WIJAYA', 'aksi' => 'Ubah', 'nama_tabel' => 'infrastruktur', 'record_id' => 1, 'ringkasan' => 'Memutakhirkan kondisi jalan setelah perbaikan pengerasan.', 'ip_address' => '10.14.2.31'],
            ['id_audit_log' => 20, 'waktu' => '2026-01-15 08:41:36', 'pengguna' => 'AGUS PRASETYO', 'aksi' => 'Tambah', 'nama_tabel' => 'komoditas', 'record_id' => 1, 'ringkasan' => 'Menambahkan komoditas JAGUNG beserta satuan panen bakunya.', 'ip_address' => '10.14.2.55'],
            ['id_audit_log' => 21, 'waktu' => '2026-04-19 11:26:49', 'pengguna' => 'SITI RAHMAWATI', 'aksi' => 'Ubah', 'nama_tabel' => 'komoditas', 'record_id' => 1, 'ringkasan' => 'Melengkapi keterangan masa tanam komoditas JAGUNG.', 'ip_address' => '10.14.2.31'],
            ['id_audit_log' => 22, 'waktu' => '2026-07-24 16:08:23', 'pengguna' => 'YOSEP KLAU', 'aksi' => 'Tambah', 'nama_tabel' => 'hasil_panen', 'record_id' => 1, 'ringkasan' => 'Mencatat hasil panen jagung dari lahan LU-002.', 'ip_address' => '10.14.2.77'],
            ['id_audit_log' => 23, 'waktu' => '2026-07-25 09:17:55', 'pengguna' => 'AGUS PRASETYO', 'aksi' => 'Ubah', 'nama_tabel' => 'hasil_panen', 'record_id' => 1, 'ringkasan' => 'Membetulkan satuan volume panen dari kilogram menjadi ton.', 'ip_address' => '10.14.2.55'],
        ];
    }

    /**
     * Riwayat perubahan satu baris data tertentu.
     *
     * Menyaring audit log memakai pasangan nama tabel dan nomor baris, lalu
     * mengurutkannya dari yang terbaru. Dipakai tab "Catatan Log" pada halaman
     * rincian, sehingga pertanyaan "siapa yang memasukkan data ini dan siapa
     * yang pernah mengubahnya" terjawab di tempat datanya dibaca, bukan dengan
     * menelusuri halaman audit log yang memuat seluruh sistem.
     *
     * KEDUA penyaring wajib dipakai bersama. Menyaring nama tabel saja membuat
     * setiap baris menampilkan riwayat baris lain pada tabel yang sama.
     *
     * @param  string  $namaTabel  Nama tabel sesuai kolom audit log
     * @param  int  $recordId  Nomor baris data yang dibuka
     * @return array<int, array<string, mixed>> Riwayat, terbaru lebih dulu
     */
    public static function riwayatData(string $namaTabel, int $recordId): array
    {
        $cocok = array_values(array_filter(
            self::auditLog(),
            fn ($baris) => $baris['nama_tabel'] === $namaTabel
                && (int) $baris['record_id'] === $recordId,
        ));

        usort($cocok, fn ($a, $b) => strcmp($b['waktu'], $a['waktu']));

        return $cocok;
    }

    /**
     * Rekap kependudukan kawasan per tahun.
     *
     * Sumber grafik KK masuk dan keluar (agents/rules.md bagian 10a poin 4).
     *
     * @return array<int, array<string, mixed>> Rekap per tahun
     */
    public static function rekapKependudukan(): array
    {
        $deret = self::deretTahunan();
        $hasil = [];

        foreach ($deret['tahun'] as $i => $tahun) {
            $hasil[] = [
                'tahun' => $tahun,
                'jumlah_kk' => $deret['jumlah_kk'][$i],
                'jumlah_jiwa' => $deret['jumlah_jiwa'][$i],
                'jumlah_petani' => $deret['jumlah_petani'][$i],
                'kk_masuk' => $deret['kk_masuk'][$i],
                'kk_keluar' => $deret['kk_keluar'][$i],
                'pendapatan_rata_rata' => $deret['pendapatan_rata_rata'][$i],
            ];
        }

        return $hasil;
    }

    /*
    |--------------------------------------------------------------------------
    | Pengguna sistem
    |--------------------------------------------------------------------------
    */

    /**
     * Data pengguna yang sedang masuk, untuk halaman profil dan menu header.
     *
     * Seluruh pengguna sistem adalah petugas; warga tidak memiliki akun
     * (agents/rules.md bagian 5.0 poin 5). Struktur mengikuti kolom tabel
     * `user` dan `role` pada agents/data-dictionary.md bagian 2.1 dan 2.1a.
     *
     * @return array<string, mixed> Data akun beserta role dan penugasannya
     */
    public static function penggunaSaatIni(): array
    {
        return [
            'id_user' => 1,
            'nama' => 'NARA WIJAYA',
            'username' => 'nara.wijaya',
            'email' => 'nara.wijaya@malakakab.go.id',
            'telepon' => '081234567890',
            'jabatan' => 'Staf Bidang Ketransmigrasian',
            'foto' => null,
            'is_aktif' => true,
            'password_harus_diganti' => false,
            'last_login_at' => '2026-08-11 07:42:00',
            'created_at' => '2026-02-03 09:15:00',
            'role' => [
                'id_role' => 2,
                'nama' => 'Dinas Transmigrasi',
                'deskripsi' => 'Memantau dashboard dan laporan kawasan, serta mengelola data wilayah, transmigran, rumah, dan lahan.',
                'cakupan_data' => CakupanData::Semua->value,
                'is_bawaan' => true,
                'is_terkunci' => false,
            ],
            // Hanya bermakna untuk role bercakupan Per SP. Untuk cakupan Semua
            // daftar ini diabaikan (agents/data-dictionary.md bagian 2.1d).
            'satuan_permukiman' => [],
        ];
    }

    /**
     * Inisial nama untuk avatar berbasis huruf.
     *
     * Sistem sengaja tidak memakai foto orang sebagai penanda pengguna
     * (ANTISLOP-ID R-18 dan R-23); yang dipakai adalah inisial nama sendiri.
     *
     * @param  string  $nama  Nama lengkap pengguna
     * @return string Maksimal dua huruf kapital
     */
    public static function inisial(string $nama): string
    {
        $bagian = preg_split('/\s+/', trim($nama)) ?: [];
        $huruf = array_map(fn ($kata) => mb_substr($kata, 0, 1), array_slice($bagian, 0, 2));

        return mb_strtoupper(implode('', $huruf));
    }

    /*
    |--------------------------------------------------------------------------
    | Ringkasan untuk dashboard
    |--------------------------------------------------------------------------
    */

    /**
     * Angka ringkasan untuk kartu statistik dashboard.
     *
     * @return array<string, mixed> Ringkasan indikator kawasan
     */
    public static function ringkasanDashboard(): array
    {
        return [
            'jumlah_kk' => 1140,
            'jumlah_jiwa' => 4863,
            'jumlah_petani' => 892,
            'rumah_terhuni' => 1063,
            'rumah_total' => 1300,
            'luas_lahan_total' => 3250.75,
            'volume_panen_ton' => 1847.500,
            'harga_rata_rata' => 4520000,
            'pengaduan_terbuka' => 12,
        ];
    }

    /**
     * Deret tahunan untuk grafik garis dan batang.
     *
     * @return array<string, array<int|string, mixed>> Deret data per indikator
     */
    public static function deretTahunan(): array
    {
        return [
            'tahun' => [2016, 2017, 2018, 2019, 2020, 2021, 2022, 2023, 2024, 2025, 2026],
            'jumlah_kk' => [405, 618, 812, 905, 968, 1012, 1058, 1089, 1112, 1128, 1140],
            'jumlah_jiwa' => [1682, 2571, 3389, 3798, 4085, 4283, 4498, 4640, 4742, 4810, 4863],
            'jumlah_petani' => [318, 486, 638, 703, 748, 782, 815, 845, 868, 882, 892],
            'pendapatan_rata_rata' => [1250000, 1380000, 1520000, 1680000, 1750000, 1820000, 1960000, 2080000, 2190000, 2280000, 2350000],
            'kk_masuk' => [405, 213, 194, 93, 63, 44, 46, 31, 23, 16, 12],
            'kk_keluar' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            'volume_panen' => [420.5, 685.2, 912.8, 1105.4, 1220.6, 1348.9, 1462.3, 1588.7, 1695.2, 1782.4, 1847.5],
            // Harga jual rata-rata seluruh komoditas, rupiah per ton
            'harga_rata_rata' => [2850000, 3050000, 3240000, 3480000, 3610000, 3780000, 3950000, 4120000, 4290000, 4410000, 4520000],
        ];
    }

    /**
     * Rekap penghuni kawasan menurut status tinggalnya.
     *
     * Indikator ke-14 dashboard (agents/ui-spec.md bagian 9).
     *
     * @return array<string, int> Peta status tinggal ke jumlah KK
     */
    public static function rekapPenghuni(): array
    {
        return [
            StatusTinggal::Aktif->value => 1063,
            StatusTinggal::Pindah->value => 54,
            StatusTinggal::TidakAktif->value => 17,
            StatusTinggal::Meninggal->value => 6,
        ];
    }

    /**
     * Sebaran pekerjaan kepala keluarga untuk histogram.
     *
     * @return array<string, int> Peta pekerjaan ke jumlah KK
     */
    public static function sebaranPekerjaan(): array
    {
        return [
            'Petani' => 892,
            'Buruh Tani' => 118,
            'Pedagang' => 54,
            'Wiraswasta' => 32,
            'Guru' => 18,
            'Aparat Desa' => 14,
            'Lainnya' => 12,
        ];
    }

    /**
     * Sebaran komoditas utama untuk grafik donat.
     *
     * @return array<string, float> Peta komoditas ke volume dalam ton
     */
    public static function sebaranKomoditas(): array
    {
        return [
            'Jagung' => 1284.5,
            'Padi' => 342.8,
            'Kacang Tanah' => 118.4,
            'Ubi Kayu' => 68.2,
            'Cabai' => 21.6,
            'Lainnya' => 12.0,
        ];
    }

    /**
     * Rekap ringkas per satuan permukiman untuk tabel dan drill-down.
     *
     * Kolom `satuan_permukiman_id` diperlukan agar baris rekap dapat ditaut
     * ke halaman rincian `/dashboard/sp/{id}`.
     *
     * @return array<int, array<string, mixed>> Rekap per SP
     */
    public static function rekapPerSp(): array
    {
        return [
            ['satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo',
                'jumlah_kk' => 218, 'rumah_terhuni' => 205, 'luas_lahan' => 620.50, 'volume_panen' => 385.20, 'pengaduan_terbuka' => 3],
            ['satuan_permukiman_id' => 2, 'satuan_permukiman' => 'SP Tniumanu',
                'jumlah_kk' => 187, 'rumah_terhuni' => 174, 'luas_lahan' => 512.25, 'volume_panen' => 312.80, 'pengaduan_terbuka' => 2],
            ['satuan_permukiman_id' => 3, 'satuan_permukiman' => 'SP Harekakae',
                'jumlah_kk' => 195, 'rumah_terhuni' => 182, 'luas_lahan' => 548.00, 'volume_panen' => 328.40, 'pengaduan_terbuka' => 1],
            ['satuan_permukiman_id' => 4, 'satuan_permukiman' => 'SP Weoe / Uluk Lubuk',
                'jumlah_kk' => 176, 'rumah_terhuni' => 164, 'luas_lahan' => 498.00, 'volume_panen' => 289.60, 'pengaduan_terbuka' => 2],
            ['satuan_permukiman_id' => 5, 'satuan_permukiman' => 'SP Tualaran',
                'jumlah_kk' => 201, 'rumah_terhuni' => 189, 'luas_lahan' => 574.50, 'volume_panen' => 341.30, 'pengaduan_terbuka' => 3],
            ['satuan_permukiman_id' => 6, 'satuan_permukiman' => 'SP Weain',
                'jumlah_kk' => 163, 'rumah_terhuni' => 149, 'luas_lahan' => 497.50, 'volume_panen' => 190.20, 'pengaduan_terbuka' => 1],
        ];
    }

    /**
     * Mencari satu satuan permukiman menurut idnya.
     *
     * @param  int  $id  Nilai id_satuan_permukiman
     * @return array<string, mixed>|null Data SP, atau null bila tidak ada
     */
    public static function cariSp(int $id): ?array
    {
        foreach (self::satuanPermukiman() as $sp) {
            if ($sp['id_satuan_permukiman'] === $id) {
                return $sp;
            }
        }

        return null;
    }

    /**
     * Mengambil baris rekap milik satu satuan permukiman.
     *
     * @param  int  $id  Nilai id_satuan_permukiman
     * @return array<string, mixed>|null Rekap SP, atau null bila tidak ada
     */
    public static function rekapSp(int $id): ?array
    {
        foreach (self::rekapPerSp() as $baris) {
            if ($baris['satuan_permukiman_id'] === $id) {
                return $baris;
            }
        }

        return null;
    }

    /**
     * Menyaring sebuah daftar menurut nama satuan permukimannya.
     *
     * Dipakai halaman rincian SP untuk mengambil transmigran, rumah, lahan,
     * pengaduan, dan infrastruktur yang berada di SP bersangkutan.
     *
     * @param  array<int, array<string, mixed>>  $daftar  Data yang disaring
     * @param  string  $namaSp  Nama SP yang dicari
     * @return array<int, array<string, mixed>> Baris yang cocok
     */
    public static function saringPerSp(array $daftar, string $namaSp): array
    {
        return array_values(array_filter(
            $daftar,
            fn ($baris) => ($baris['satuan_permukiman'] ?? null) === $namaSp
        ));
    }

    /**
     * Deret tahunan milik satu SP, diturunkan dari deret kawasan.
     *
     * PENTING: angka ini adalah CONTOH yang diturunkan secara proporsional
     * menurut porsi KK milik SP tersebut terhadap seluruh kawasan, bukan
     * pendataan per SP yang sebenarnya. Pada Task 9.1 metode ini diganti
     * query nyata yang mengelompokkan data menurut satuan_permukiman_id.
     *
     * @param  int  $id  Nilai id_satuan_permukiman
     * @return array<string, array<int, mixed>> Deret tahun, KK, dan volume panen
     */
    public static function deretTahunanSp(int $id): array
    {
        $deret = self::deretTahunan();
        $rekap = self::rekapSp($id);

        if ($rekap === null) {
            return ['tahun' => [], 'jumlah_kk' => [], 'volume_panen' => []];
        }

        $porsiKk = $rekap['jumlah_kk'] / self::ringkasanDashboard()['jumlah_kk'];
        $porsiPanen = $rekap['volume_panen'] / self::ringkasanDashboard()['volume_panen_ton'];

        return [
            'tahun' => $deret['tahun'],
            'jumlah_kk' => array_map(fn ($n) => (int) round($n * $porsiKk), $deret['jumlah_kk']),
            'volume_panen' => array_map(fn ($n) => round($n * $porsiPanen, 2), $deret['volume_panen']),
        ];
    }

    /**
     * Rekap status infrastruktur untuk grafik batang bertumpuk.
     *
     * @return array<int, array<string, mixed>> Rekap kondisi per jenis
     */
    public static function statusInfrastruktur(): array
    {
        return [
            ['jenis' => 'Irigasi', 'baik' => 8, 'rusak_ringan' => 4, 'rusak_berat' => 2],
            ['jenis' => 'Air', 'baik' => 12, 'rusak_ringan' => 3, 'rusak_berat' => 1],
            ['jenis' => 'Jalan Produksi', 'baik' => 6, 'rusak_ringan' => 5, 'rusak_berat' => 3],
            ['jenis' => 'Listrik', 'baik' => 9, 'rusak_ringan' => 2, 'rusak_berat' => 0],
            ['jenis' => 'Gudang', 'baik' => 4, 'rusak_ringan' => 1, 'rusak_berat' => 1],
            ['jenis' => 'Telekomunikasi', 'baik' => 3, 'rusak_ringan' => 2, 'rusak_berat' => 1],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Izin dan role
    |--------------------------------------------------------------------------
    */

    /**
     * Seluruh modul berizin beserta aksi yang berlaku padanya.
     *
     * Disalin dari dua sumber yang wajib sejalan: daftar modul beserta
     * aksinya pada agents/data-dictionary.md bagian 13.1, dan susunan izin
     * role bawaan pada agents/rules.md bagian 5.1. Keduanyalah sumber
     * kebenarannya, bukan daftar ini.
     *
     * Pengelompokan mengikuti data-dictionary.md bagian 13.2 agar susunannya
     * sama dengan urutan menu sidebar, sehingga Admin menemukan modul di
     * tempat yang sama seperti ketika menavigasi sistem.
     *
     * Tidak semua modul mengenal keenam aksi. Dashboard hanya dapat dilihat
     * dan diekspor, sebab tidak ada yang dapat ditambahkan padanya. Menyajikan
     * kotak centang untuk aksi yang tidak berlaku membuat matriks tampak
     * menawarkan kewenangan yang sebenarnya mustahil.
     *
     * @return array<int, array<string, mixed>> Kelompok berisi modul dan aksinya
     */
    public static function daftarIzin(): array
    {
        $penuh = ['lihat', 'tambah', 'ubah', 'hapus', 'export'];
        $kelolaSaja = ['lihat', 'tambah', 'ubah', 'hapus'];
        $bacaEkspor = ['lihat', 'export'];

        // Akun tidak pernah dihapus, hanya dinonaktifkan (rules.md 14b poin 16),
        // sehingga izin hapus tidak disediakan untuk modul pengguna. Menawarkan
        // kotak centang bagi kewenangan yang mustahil dijalankan hanya
        // menyesatkan admin yang menyusun role.
        $tanpaHapus = ['lihat', 'tambah', 'ubah'];

        return [
            [
                'kelompok' => 'Sistem',
                'modul' => [
                    ['kunci' => 'pengguna', 'nama' => 'Manajemen pengguna', 'aksi' => $tanpaHapus],
                    // Role BOLEH dihapus selama bukan bawaan dan tidak dipakai
                    // akun mana pun (rules.md 5.0c poin 8 dan 9).
                    ['kunci' => 'role', 'nama' => 'Pengaturan role', 'aksi' => $kelolaSaja],
                    ['kunci' => 'audit_log', 'nama' => 'Audit log', 'aksi' => $bacaEkspor],
                ],
            ],
            [
                'kelompok' => 'Wilayah dan SP',
                'modul' => [
                    ['kunci' => 'wilayah', 'nama' => 'Data master wilayah', 'aksi' => $kelolaSaja],
                    ['kunci' => 'kawasan', 'nama' => 'Kawasan transmigrasi', 'aksi' => $penuh],
                    ['kunci' => 'sp', 'nama' => 'Satuan permukiman (SP)', 'aksi' => $penuh],
                    // Inventaris dan fasilitas sengaja dipisah: dua tabel, dua
                    // halaman, sehingga kewenangannya dapat dibedakan
                    // (rules.md 5.1 catatan 5).
                    ['kunci' => 'inventaris_sp', 'nama' => 'Inventaris SP', 'aksi' => $penuh],
                    ['kunci' => 'fasilitas_sp', 'nama' => 'Fasilitas SP', 'aksi' => $penuh],
                    ['kunci' => 'satuan', 'nama' => 'Data master satuan', 'aksi' => $kelolaSaja],
                ],
            ],
            [
                'kelompok' => 'Kependudukan',
                'modul' => [
                    ['kunci' => 'transmigran', 'nama' => 'Transmigran', 'aksi' => $penuh],
                    ['kunci' => 'rumah', 'nama' => 'Rumah dan hunian', 'aksi' => $penuh],
                    ['kunci' => 'riwayat_penghunian', 'nama' => 'Riwayat penghunian', 'aksi' => $penuh],
                ],
            ],
            [
                'kelompok' => 'Lahan',
                'modul' => [
                    ['kunci' => 'lahan', 'nama' => 'Lahan', 'aksi' => $penuh],
                    ['kunci' => 'dokumen_lahan', 'nama' => 'Dokumen lahan (HPL/SHM)', 'aksi' => $kelolaSaja],
                ],
            ],
            [
                'kelompok' => 'Kelembagaan',
                'modul' => [
                    ['kunci' => 'poktan', 'nama' => 'Kelompok tani', 'aksi' => $penuh],
                    ['kunci' => 'anggota_poktan', 'nama' => 'Anggota poktan', 'aksi' => $penuh],
                    ['kunci' => 'alsintan', 'nama' => 'Alsintan', 'aksi' => $penuh],
                    ['kunci' => 'saprotan', 'nama' => 'Saprotan', 'aksi' => $penuh],
                ],
            ],
            [
                'kelompok' => 'Pertanian',
                'modul' => [
                    ['kunci' => 'komoditas', 'nama' => 'Komoditas', 'aksi' => $penuh],
                    ['kunci' => 'musim_tanam', 'nama' => 'Musim tanam', 'aksi' => $kelolaSaja],
                    ['kunci' => 'riwayat_tanam', 'nama' => 'Riwayat tanam', 'aksi' => $penuh],
                    ['kunci' => 'hasil_panen', 'nama' => 'Hasil panen', 'aksi' => $penuh],
                ],
            ],
            [
                'kelompok' => 'Infrastruktur',
                'modul' => [
                    ['kunci' => 'infrastruktur', 'nama' => 'Infrastruktur SP', 'aksi' => $penuh],
                ],
            ],
            [
                'kelompok' => 'Pengaduan',
                'modul' => [
                    ['kunci' => 'pengaduan', 'nama' => 'Pengaduan', 'aksi' => $penuh],
                    ['kunci' => 'penanganan_pengaduan', 'nama' => 'Penanganan pengaduan', 'aksi' => ['lihat', 'tambah', 'ubah']],
                ],
            ],
            [
                'kelompok' => 'Pemantauan',
                'modul' => [
                    ['kunci' => 'dashboard', 'nama' => 'Dashboard', 'aksi' => $bacaEkspor],
                    ['kunci' => 'laporan', 'nama' => 'Laporan dan export', 'aksi' => $bacaEkspor],
                ],
            ],
        ];
    }

    /**
     * Izin yang dimiliki sebuah role, dipetakan per modul.
     *
     * Nilai disalin dari tabel agents/rules.md bagian 5.1. Sejak role menjadi
     * dinamis, tabel itu berkedudukan sebagai konfigurasi awal yang ditanam
     * seeder, bukan aturan permanen di dalam kode.
     *
     * @param  int  $roleId  Pengenal role
     * @return array<string, array<int, string>> Kunci modul berisi daftar aksi
     */
    public static function izinRole(int $roleId): array
    {
        $lituhe = ['lihat', 'tambah', 'ubah', 'hapus', 'export'];
        $k = ['lihat', 'tambah', 'ubah', 'hapus'];
        $ltue = ['lihat', 'tambah', 'ubah', 'export'];
        $lte = ['lihat', 'tambah', 'export'];
        $ltu = ['lihat', 'tambah', 'ubah'];
        $lt = ['lihat', 'tambah'];
        $le = ['lihat', 'export'];
        $l = ['lihat'];

        $peta = [
            // Admin. Kolom kedua tabel rules.md 5.1.
            1 => [
                'wilayah' => $k, 'kawasan' => $lituhe, 'sp' => $lituhe,
                'inventaris_sp' => $lituhe, 'fasilitas_sp' => $lituhe, 'satuan' => $k,
                'transmigran' => $lituhe, 'rumah' => $lituhe, 'riwayat_penghunian' => $lituhe,
                'lahan' => $lituhe, 'dokumen_lahan' => $k,
                'poktan' => $lituhe, 'anggota_poktan' => $lituhe, 'alsintan' => $lituhe, 'saprotan' => $lituhe,
                'komoditas' => $lituhe, 'musim_tanam' => $k, 'riwayat_tanam' => $lituhe, 'hasil_panen' => $lituhe,
                'infrastruktur' => $lituhe, 'pengaduan' => $lituhe, 'penanganan_pengaduan' => $ltu,
                'dashboard' => $le, 'laporan' => $le,
                // Akun tidak pernah dihapus, hanya dinonaktifkan, sehingga
                // modul pengguna berhenti di ubah (rules.md 14b poin 16).
                'pengguna' => $ltu, 'role' => $k, 'audit_log' => $le,
            ],
            // Dinas Transmigrasi. Mengelola wilayah, kependudukan, dan lahan.
            // Pada modul pertanian hanya dapat melihat.
            2 => [
                'wilayah' => $l, 'kawasan' => ['lihat', 'export'], 'sp' => $ltue,
                'inventaris_sp' => $ltue, 'fasilitas_sp' => $ltue, 'satuan' => $l,
                'transmigran' => $ltue, 'rumah' => $ltue, 'riwayat_penghunian' => $lte,
                'lahan' => $ltue, 'dokumen_lahan' => ['lihat', 'tambah'],
                'poktan' => $le, 'anggota_poktan' => $l, 'alsintan' => $l, 'saprotan' => $l,
                'komoditas' => $l, 'musim_tanam' => $l, 'riwayat_tanam' => $l, 'hasil_panen' => $l,
                'infrastruktur' => $ltue, 'pengaduan' => $ltue, 'penanganan_pengaduan' => $ltu,
                'dashboard' => $le, 'laporan' => $le,
            ],
            // Dinas Pertanian. Mengelola kelembagaan dan produksi pertanian.
            // Pada modul kependudukan hanya dapat melihat.
            3 => [
                'wilayah' => $l, 'kawasan' => $le, 'sp' => $le,
                'inventaris_sp' => $le, 'fasilitas_sp' => $le, 'satuan' => $l,
                'transmigran' => $le, 'rumah' => $le, 'riwayat_penghunian' => $l,
                'lahan' => $le, 'dokumen_lahan' => $l,
                'poktan' => $ltue, 'anggota_poktan' => $ltue, 'alsintan' => $ltue, 'saprotan' => $ltue,
                'komoditas' => $ltue, 'musim_tanam' => $ltu,
                'riwayat_tanam' => $ltue, 'hasil_panen' => $ltue,
                'infrastruktur' => $ltue, 'pengaduan' => $ltue, 'penanganan_pengaduan' => $ltu,
                'dashboard' => $le, 'laporan' => $le,
            ],
            // Operator SP. Memasukkan data, sengaja tanpa izin hapus
            // (rules.md 5.1 catatan 4). Tidak memegang izin apa pun pada
            // penanganan pengaduan.
            4 => [
                'wilayah' => $l, 'kawasan' => $l, 'sp' => $l,
                'inventaris_sp' => $ltu, 'fasilitas_sp' => $ltu, 'satuan' => $l,
                'transmigran' => $ltu, 'rumah' => $ltu, 'riwayat_penghunian' => $lt,
                'lahan' => $ltu, 'dokumen_lahan' => $lt,
                'poktan' => $ltu, 'anggota_poktan' => $ltu, 'alsintan' => $ltu, 'saprotan' => $ltu,
                'komoditas' => $l, 'musim_tanam' => $l, 'riwayat_tanam' => $ltu, 'hasil_panen' => $ltu,
                'infrastruktur' => $ltu, 'pengaduan' => $lt,
                'dashboard' => $l, 'laporan' => $l,
            ],
            // Pendamping Lapangan. Role buatan Admin, bukan bawaan sistem.
            // Hanya membaca, sehingga dapat diberikan kepada pendamping yang
            // bertugas sementara tanpa risiko data berubah.
            5 => [
                'sp' => $l, 'inventaris_sp' => $l, 'fasilitas_sp' => $l,
                'transmigran' => $l, 'rumah' => $l, 'riwayat_penghunian' => $l,
                'lahan' => $l, 'poktan' => $l, 'anggota_poktan' => $l,
                'alsintan' => $l, 'saprotan' => $l, 'komoditas' => $l,
                'riwayat_tanam' => $l, 'hasil_panen' => $l,
                'infrastruktur' => $l, 'dashboard' => $l,
            ],
        ];

        return $peta[$roleId] ?? [];
    }
}