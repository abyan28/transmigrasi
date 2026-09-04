<?php

namespace App\Support;

use App\Enums\AlasanPergantianKK;
use App\Enums\AsalWakilPoktan;
use App\Enums\CakupanData;
use App\Enums\HubunganAnggotaKeluarga;
use App\Enums\JenisInfrastruktur;
use App\Enums\JenisReferensi;
use App\Enums\JenisSaprotan;
use App\Enums\KategoriPengaduan;
use App\Enums\Kondisi;
use App\Enums\KondisiRumah;
use App\Enums\PrioritasPengaduan;
use App\Enums\StatusAnggotaKeluarga;
use App\Enums\StatusHunian;
use App\Enums\StatusKondisiSp;
use App\Enums\StatusPanen;
use App\Enums\StatusPengaduan;
use App\Enums\StatusTinggal;
use App\Enums\SumberLaporan;
use App\Enums\TingkatKebutuhan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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
        $data = [
            [
                'id_kawasan_transmigrasi' => 1,
                'nama' => 'Kobalima Timur',

                // `kabupaten_id` ADALAH KEBENARANNYA, dua kolom teks di
                // bawahnya hanya label tampilan. Ditambahkan 2026-09-02 sebab
                // penyaringan desa pada form SP menempuh kawasan lalu
                // kabupatennya, dan pencocokan lewat nama akan putus diam-diam
                // begitu ejaan data master berubah. Skema sudah lebih dulu
                // benar (`kawasan_transmigrasi.kabupaten_id`, FK RESTRICT).
                'kabupaten_id' => 5321,
                'kabupaten' => 'Kabupaten Malaka',
                'provinsi' => 'Nusa Tenggara Timur',
                'kode_kawasan' => 'KWS-KBT',
                'tahun_penetapan' => 2015,
                'nomor_sk' => 'SK.123/MEN-TRANS/2015',
                'luas_total' => 4250.75,
                'jumlah_sp' => 6,
                'keterangan' => 'Kawasan mencakup empat kecamatan, sehingga tidak dapat diwakili satu wilayah administratif.',
            ],
        ];

        return self::lekatkanBerkas($data, 'kawasan_transmigrasi_berkas', 'kawasan_transmigrasi_id', 'id_kawasan_transmigrasi', ['sk' => 'dokumen_pendukung']);
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
        $data = [
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
                'keterangan' => 'SP tertua di kawasan, seluruh lahan usaha sudah dibagikan.',
                'berkas_id' => 2,
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
                'keterangan' => null,
                'berkas_id' => 3,
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
                'keterangan' => null,
                'dokumen_pendukung' => null,
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
                'keterangan' => null,
                'dokumen_pendukung' => null,
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
                'keterangan' => null,
                'dokumen_pendukung' => null,
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
                'keterangan' => 'SP terbaru, sebagian lahan usaha masih dalam proses penetapan.',
                'dokumen_pendukung' => null,
            ],
        ];

        $data = array_map(function (array $s): array {
            $berkas = self::cariBerkas($s['berkas_id'] ?? null);

            return $s + ['dokumen_pendukung' => $berkas['nama_file'] ?? null, 'dokumen_pendukung_meta' => $berkas];
        }, $data);

        // Field "Keadaan Wilayah" (Bab II Monografi) disatukan di sini agar
        // baris identitas di atas tetap terbaca sekali pandang. Rombongan C,
        // 2026-08-28. SP Kapitan Meo memakai nilai persis dari berkas
        // "LAPORAN MONOGRAFI UPT KAPITAN MEO 2025"; SP lain nilai yang wajar.
        $keadaan = self::keadaanWilayahSp();

        return array_map(
            fn ($sp) => $sp + ($keadaan[$sp['id_satuan_permukiman']] ?? self::keadaanWilayahKosong()),
            $data,
        );
    }

    /**
     * Kolom kosong "Keadaan Wilayah", agar setiap SP punya kunci yang sama
     * meski datanya belum diisi.
     *
     * @return array<string, null>
     */
    private static function keadaanWilayahKosong(): array
    {
        return array_fill_keys([
            'lintang_utara', 'lintang_selatan', 'bujur_barat', 'bujur_timur',
            'jarak_ke_kecamatan_km', 'jarak_ke_kabupaten_km', 'jarak_ke_provinsi_km',
            'batas_utara', 'batas_timur', 'batas_selatan', 'batas_barat',
            'nomor_sk_pencadangan', 'tanggal_sk_pencadangan',
            'pola_permukiman', 'tingkat_kesuburan_tanah', 'ph_tanah_min', 'ph_tanah_maks',
            'bentuk_wilayah', 'kemiringan_min_persen', 'kemiringan_maks_persen',
            'curah_hujan_tahunan_mm', 'curah_hujan_bulan_min_mm', 'curah_hujan_bulan_maks_mm',
            'suhu_min_c', 'suhu_maks_c', 'suhu_rata_c',
            'angin_min_knot', 'angin_maks_knot', 'angin_rata_knot',
            'penyinaran_min_persen', 'penyinaran_maks_persen', 'penyinaran_rata_persen',
            'sumber_air_bersih', 'sumber_air_pertanian',
        ], null);
    }

    /**
     * Data "Keadaan Wilayah" per SP, menurut id.
     *
     * Bab II Laporan Monografi: letak, batas, luas dan bentuk, tanah,
     * topografi, iklim, dan sumberdaya air. Seluruhnya dokumenter (dipakai
     * laporan, tidak dihitung). Batas wilayah dihidupkan kembali di sini
     * setelah dicabut 2026-08-18, sebab Monografi memerlukannya.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function keadaanWilayahSp(): array
    {
        return [
            1 => [
                'lintang_utara' => -9.5043200, 'lintang_selatan' => -9.5216800,
                'bujur_barat' => 124.9051200, 'bujur_timur' => 124.9189700,
                'jarak_ke_kecamatan_km' => 2.0, 'jarak_ke_kabupaten_km' => 22.0, 'jarak_ke_provinsi_km' => 245.0,
                'batas_utara' => 'Desa Tesa', 'batas_timur' => 'Desa Tniumanu',
                'batas_selatan' => 'Desa Uabau', 'batas_barat' => 'Desa Kusa',
                'nomor_sk_pencadangan' => '79/HK/2018', 'tanggal_sk_pencadangan' => '2018-02-13',
                'pola_permukiman' => 'Konsentris', 'tingkat_kesuburan_tanah' => 'Subur',
                'ph_tanah_min' => 7.01, 'ph_tanah_maks' => 7.69,
                'bentuk_wilayah' => 'Datar', 'kemiringan_min_persen' => 8.0, 'kemiringan_maks_persen' => 15.0,
                'curah_hujan_tahunan_mm' => 1607.18, 'curah_hujan_bulan_min_mm' => 100.0, 'curah_hujan_bulan_maks_mm' => 200.0,
                'suhu_min_c' => 23.0, 'suhu_maks_c' => 31.0, 'suhu_rata_c' => 27.7,
                'angin_min_knot' => 4.0, 'angin_maks_knot' => 4.6, 'angin_rata_knot' => 4.3,
                'penyinaran_min_persen' => 45.0, 'penyinaran_maks_persen' => 74.4, 'penyinaran_rata_persen' => 55.6,
                'sumber_air_bersih' => 'Perpipaan dan mata air',
                'sumber_air_pertanian' => 'Air hujan dan embung',
            ],
            2 => [
                'lintang_utara' => -9.4905400, 'lintang_selatan' => -9.5061300,
                'bujur_barat' => 124.8801100, 'bujur_timur' => 124.8951800,
                'jarak_ke_kecamatan_km' => 4.0, 'jarak_ke_kabupaten_km' => 20.0, 'jarak_ke_provinsi_km' => 243.0,
                'batas_utara' => 'Desa Tniumanu', 'batas_timur' => 'Sungai Benanain',
                'batas_selatan' => 'Desa Alas', 'batas_barat' => 'Desa Kapitan Meo',
                'nomor_sk_pencadangan' => '79/HK/2018', 'tanggal_sk_pencadangan' => '2018-02-13',
                'pola_permukiman' => 'Konsentris', 'tingkat_kesuburan_tanah' => 'Sedang',
                'ph_tanah_min' => 6.50, 'ph_tanah_maks' => 7.20,
                'bentuk_wilayah' => 'Bergelombang', 'kemiringan_min_persen' => 8.0, 'kemiringan_maks_persen' => 25.0,
                'curah_hujan_tahunan_mm' => 1550.00, 'curah_hujan_bulan_min_mm' => 90.0, 'curah_hujan_bulan_maks_mm' => 210.0,
                'suhu_min_c' => 22.5, 'suhu_maks_c' => 31.5, 'suhu_rata_c' => 27.5,
                'angin_min_knot' => 3.8, 'angin_maks_knot' => 4.7, 'angin_rata_knot' => 4.2,
                'penyinaran_min_persen' => 43.0, 'penyinaran_maks_persen' => 73.0, 'penyinaran_rata_persen' => 54.0,
                'sumber_air_bersih' => 'Sumur bor dan mata air',
                'sumber_air_pertanian' => 'Air hujan dan embung',
            ],
            3 => [
                'lintang_utara' => -9.4478900, 'lintang_selatan' => -9.4623500,
                'bujur_barat' => 124.9381200, 'bujur_timur' => 124.9520400,
                'jarak_ke_kecamatan_km' => 6.0, 'jarak_ke_kabupaten_km' => 15.0, 'jarak_ke_provinsi_km' => 238.0,
                'batas_utara' => 'Desa Naets', 'batas_timur' => 'Desa Kereana',
                'batas_selatan' => 'Desa Harekakae Induk', 'batas_barat' => 'Kawasan hutan produksi',
                'nomor_sk_pencadangan' => '112/HK/2016', 'tanggal_sk_pencadangan' => '2016-05-04',
                'pola_permukiman' => 'Papan Catur', 'tingkat_kesuburan_tanah' => 'Subur',
                'ph_tanah_min' => 6.80, 'ph_tanah_maks' => 7.40,
                'bentuk_wilayah' => 'Datar', 'kemiringan_min_persen' => 3.0, 'kemiringan_maks_persen' => 12.0,
                'curah_hujan_tahunan_mm' => 1720.00, 'curah_hujan_bulan_min_mm' => 110.0, 'curah_hujan_bulan_maks_mm' => 230.0,
                'suhu_min_c' => 23.2, 'suhu_maks_c' => 30.8, 'suhu_rata_c' => 27.4,
                'angin_min_knot' => 4.1, 'angin_maks_knot' => 4.9, 'angin_rata_knot' => 4.5,
                'penyinaran_min_persen' => 46.0, 'penyinaran_maks_persen' => 75.0, 'penyinaran_rata_persen' => 56.5,
                'sumber_air_bersih' => 'Perpipaan gravitasi',
                'sumber_air_pertanian' => 'Irigasi setengah teknis dan air hujan',
            ],
            4 => [
                'lintang_utara' => -9.4135600, 'lintang_selatan' => -9.4288900,
                'bujur_barat' => 124.9741200, 'bujur_timur' => 124.9887300,
                'jarak_ke_kecamatan_km' => 3.0, 'jarak_ke_kabupaten_km' => 28.0, 'jarak_ke_provinsi_km' => 251.0,
                'batas_utara' => 'Laut Timor', 'batas_timur' => 'Desa Badarai',
                'batas_selatan' => 'Desa Weoe Induk', 'batas_barat' => 'Desa Halibasar',
                'nomor_sk_pencadangan' => '112/HK/2016', 'tanggal_sk_pencadangan' => '2016-05-04',
                'pola_permukiman' => 'Linear', 'tingkat_kesuburan_tanah' => 'Sedang',
                'ph_tanah_min' => 6.20, 'ph_tanah_maks' => 7.00,
                'bentuk_wilayah' => 'Datar', 'kemiringan_min_persen' => 2.0, 'kemiringan_maks_persen' => 8.0,
                'curah_hujan_tahunan_mm' => 1480.00, 'curah_hujan_bulan_min_mm' => 80.0, 'curah_hujan_bulan_maks_mm' => 195.0,
                'suhu_min_c' => 24.0, 'suhu_maks_c' => 32.5, 'suhu_rata_c' => 28.4,
                'angin_min_knot' => 4.5, 'angin_maks_knot' => 6.2, 'angin_rata_knot' => 5.1,
                'penyinaran_min_persen' => 48.0, 'penyinaran_maks_persen' => 78.0, 'penyinaran_rata_persen' => 58.0,
                'sumber_air_bersih' => 'Sumur bor dangkal',
                'sumber_air_pertanian' => 'Air hujan',
            ],
            5 => [
                'lintang_utara' => -9.3901200, 'lintang_selatan' => -9.4074100,
                'bujur_barat' => 125.0051200, 'bujur_timur' => 125.0195600,
                'jarak_ke_kecamatan_km' => 5.0, 'jarak_ke_kabupaten_km' => 32.0, 'jarak_ke_provinsi_km' => 255.0,
                'batas_utara' => 'Desa Biudukfoho', 'batas_timur' => 'Desa Webetun',
                'batas_selatan' => 'Desa Naet Induk', 'batas_barat' => 'Kawasan hutan lindung',
                'nomor_sk_pencadangan' => '145/HK/2017', 'tanggal_sk_pencadangan' => '2017-09-18',
                'pola_permukiman' => 'Konsentris', 'tingkat_kesuburan_tanah' => 'Subur',
                'ph_tanah_min' => 6.90, 'ph_tanah_maks' => 7.50,
                'bentuk_wilayah' => 'Berbukit', 'kemiringan_min_persen' => 15.0, 'kemiringan_maks_persen' => 30.0,
                'curah_hujan_tahunan_mm' => 1850.00, 'curah_hujan_bulan_min_mm' => 120.0, 'curah_hujan_bulan_maks_mm' => 260.0,
                'suhu_min_c' => 21.8, 'suhu_maks_c' => 29.5, 'suhu_rata_c' => 26.2,
                'angin_min_knot' => 3.5, 'angin_maks_knot' => 4.4, 'angin_rata_knot' => 3.9,
                'penyinaran_min_persen' => 42.0, 'penyinaran_maks_persen' => 71.0, 'penyinaran_rata_persen' => 53.0,
                'sumber_air_bersih' => 'Mata air pegunungan dan perpipaan',
                'sumber_air_pertanian' => 'Mata air dan air hujan',
            ],
            6 => [
                'lintang_utara' => -9.3678900, 'lintang_selatan' => -9.3851200,
                'bujur_barat' => 125.0271200, 'bujur_timur' => 125.0420100,
                'jarak_ke_kecamatan_km' => 7.0, 'jarak_ke_kabupaten_km' => 35.0, 'jarak_ke_provinsi_km' => 258.0,
                'batas_utara' => 'Desa Weain Induk', 'batas_timur' => 'Sungai Motamauk',
                'batas_selatan' => 'Desa Fatuklaran', 'batas_barat' => 'Desa Nanaet',
                'nomor_sk_pencadangan' => '145/HK/2017', 'tanggal_sk_pencadangan' => '2017-09-18',
                'pola_permukiman' => 'Menyebar', 'tingkat_kesuburan_tanah' => 'Kurang Subur',
                'ph_tanah_min' => 5.80, 'ph_tanah_maks' => 6.60,
                'bentuk_wilayah' => 'Bergelombang', 'kemiringan_min_persen' => 10.0, 'kemiringan_maks_persen' => 22.0,
                'curah_hujan_tahunan_mm' => 1390.00, 'curah_hujan_bulan_min_mm' => 70.0, 'curah_hujan_bulan_maks_mm' => 185.0,
                'suhu_min_c' => 23.5, 'suhu_maks_c' => 32.0, 'suhu_rata_c' => 27.9,
                'angin_min_knot' => 4.2, 'angin_maks_knot' => 5.0, 'angin_rata_knot' => 4.6,
                'penyinaran_min_persen' => 44.0, 'penyinaran_maks_persen' => 72.5, 'penyinaran_rata_persen' => 55.0,
                'sumber_air_bersih' => 'Sumur gali dan penampungan air hujan',
                'sumber_air_pertanian' => 'Air hujan',
            ],
        ];
    }

    /**
     * Rute pencapaian menuju satu SP (Tabel 2.1 Laporan Monografi).
     *
     * Ditambahkan 2026-08-28 (Rombongan C, Stage C2). Satu SP punya beberapa
     * baris. SP Kapitan Meo memakai isi Tabel 2.1 berkas Monografi; SP lain
     * nilai yang wajar.
     *
     * @param  int|null  $satuanPermukimanId  Menyaring satu SP; null berarti seluruhnya
     * @return array<int, array<string, mixed>>
     */
    public static function ruteAksesibilitasSp(?int $satuanPermukimanId = null): array
    {
        $data = [
            // SP Kapitan Meo (id 1), dari Tabel 2.1 Monografi.
            ['id_rute_aksesibilitas_sp' => 1, 'satuan_permukiman_id' => 1, 'rute' => 'Kupang (Ibu Kota Provinsi) ke UPT', 'jarak_km' => 245.0, 'sarana_angkutan' => 'Angkutan darat', 'tempat_pemberangkatan' => 'Terminal Kupang', 'kondisi_jalan' => 'Baik, aspal', 'waktu_tempuh' => '6 jam', 'ongkos_rp' => 125000.0, 'keterangan' => 'Alternatif pesawat ke Atambua lalu darat, 45 menit, Rp 516.000.'],
            ['id_rute_aksesibilitas_sp' => 2, 'satuan_permukiman_id' => 1, 'rute' => 'UPT ke Ibu Kota Kabupaten (Betun)', 'jarak_km' => 20.0, 'sarana_angkutan' => 'Roda dua', 'tempat_pemberangkatan' => 'UPT', 'kondisi_jalan' => 'Baik, aspal', 'waktu_tempuh' => '2 jam', 'ongkos_rp' => 100000.0, 'keterangan' => 'Roda empat sekitar 2,5 jam, Rp 35.000 per orang.'],
            ['id_rute_aksesibilitas_sp' => 3, 'satuan_permukiman_id' => 1, 'rute' => 'UPT ke Ibu Kota Kecamatan (Laen Manen)', 'jarak_km' => 2.0, 'sarana_angkutan' => 'Roda dua atau roda empat', 'tempat_pemberangkatan' => 'UPT', 'kondisi_jalan' => 'Baik, pengerasan dan aspal', 'waktu_tempuh' => '5 menit', 'ongkos_rp' => 5000.0, 'keterangan' => null],
            ['id_rute_aksesibilitas_sp' => 4, 'satuan_permukiman_id' => 1, 'rute' => 'UPT ke pasar terdekat', 'jarak_km' => 3.0, 'sarana_angkutan' => 'Roda dua', 'tempat_pemberangkatan' => 'UPT', 'kondisi_jalan' => 'Aspal', 'waktu_tempuh' => '15 menit', 'ongkos_rp' => 10000.0, 'keterangan' => null],
            ['id_rute_aksesibilitas_sp' => 5, 'satuan_permukiman_id' => 1, 'rute' => 'UPT ke pelabuhan terdekat', 'jarak_km' => 50.0, 'sarana_angkutan' => 'Roda dua atau roda empat', 'tempat_pemberangkatan' => 'UPT', 'kondisi_jalan' => 'Baik, aspal', 'waktu_tempuh' => '2 sampai 3 jam', 'ongkos_rp' => 100000.0, 'keterangan' => null],

            // SP Tniumanu (id 2)
            ['id_rute_aksesibilitas_sp' => 6, 'satuan_permukiman_id' => 2, 'rute' => 'Kupang ke UPT', 'jarak_km' => 243.0, 'sarana_angkutan' => 'Angkutan darat', 'tempat_pemberangkatan' => 'Terminal Kupang', 'kondisi_jalan' => 'Baik, aspal', 'waktu_tempuh' => '6 jam', 'ongkos_rp' => 125000.0, 'keterangan' => null],
            ['id_rute_aksesibilitas_sp' => 7, 'satuan_permukiman_id' => 2, 'rute' => 'UPT ke Ibu Kota Kabupaten (Betun)', 'jarak_km' => 18.0, 'sarana_angkutan' => 'Roda dua', 'tempat_pemberangkatan' => 'UPT', 'kondisi_jalan' => 'Sebagian pengerasan', 'waktu_tempuh' => '1,5 jam', 'ongkos_rp' => 90000.0, 'keterangan' => null],
            ['id_rute_aksesibilitas_sp' => 8, 'satuan_permukiman_id' => 2, 'rute' => 'UPT ke Ibu Kota Kecamatan', 'jarak_km' => 4.0, 'sarana_angkutan' => 'Roda dua', 'tempat_pemberangkatan' => 'UPT', 'kondisi_jalan' => 'Pengerasan', 'waktu_tempuh' => '10 menit', 'ongkos_rp' => 5000.0, 'keterangan' => null],

            // SP Harekakae (id 3)
            ['id_rute_aksesibilitas_sp' => 9, 'satuan_permukiman_id' => 3, 'rute' => 'Kupang ke UPT', 'jarak_km' => 238.0, 'sarana_angkutan' => 'Angkutan darat', 'tempat_pemberangkatan' => 'Terminal Kupang', 'kondisi_jalan' => 'Baik, aspal', 'waktu_tempuh' => '5,5 jam', 'ongkos_rp' => 120000.0, 'keterangan' => null],
            ['id_rute_aksesibilitas_sp' => 10, 'satuan_permukiman_id' => 3, 'rute' => 'UPT ke Ibu Kota Kabupaten', 'jarak_km' => 15.0, 'sarana_angkutan' => 'Roda dua atau roda empat', 'tempat_pemberangkatan' => 'UPT', 'kondisi_jalan' => 'Baik, aspal', 'waktu_tempuh' => '30 menit', 'ongkos_rp' => 25000.0, 'keterangan' => null],

            // SP Weoe / Uluk Lubuk (id 4)
            ['id_rute_aksesibilitas_sp' => 11, 'satuan_permukiman_id' => 4, 'rute' => 'Kupang ke UPT', 'jarak_km' => 251.0, 'sarana_angkutan' => 'Angkutan darat', 'tempat_pemberangkatan' => 'Terminal Kupang', 'kondisi_jalan' => 'Baik, aspal', 'waktu_tempuh' => '6,5 jam', 'ongkos_rp' => 130000.0, 'keterangan' => null],
            ['id_rute_aksesibilitas_sp' => 12, 'satuan_permukiman_id' => 4, 'rute' => 'UPT ke Ibu Kota Kabupaten', 'jarak_km' => 28.0, 'sarana_angkutan' => 'Roda dua', 'tempat_pemberangkatan' => 'UPT', 'kondisi_jalan' => 'Baik, aspal', 'waktu_tempuh' => '2 jam', 'ongkos_rp' => 100000.0, 'keterangan' => null],
            ['id_rute_aksesibilitas_sp' => 13, 'satuan_permukiman_id' => 4, 'rute' => 'UPT ke pelabuhan penyeberangan', 'jarak_km' => 12.0, 'sarana_angkutan' => 'Roda dua atau roda empat', 'tempat_pemberangkatan' => 'UPT', 'kondisi_jalan' => 'Baik, aspal', 'waktu_tempuh' => '25 menit', 'ongkos_rp' => 15000.0, 'keterangan' => null],

            // SP Tualaran (id 5)
            ['id_rute_aksesibilitas_sp' => 14, 'satuan_permukiman_id' => 5, 'rute' => 'Kupang ke UPT', 'jarak_km' => 255.0, 'sarana_angkutan' => 'Angkutan darat', 'tempat_pemberangkatan' => 'Terminal Kupang', 'kondisi_jalan' => 'Baik, aspal berbukit', 'waktu_tempuh' => '7 jam', 'ongkos_rp' => 135000.0, 'keterangan' => null],
            ['id_rute_aksesibilitas_sp' => 15, 'satuan_permukiman_id' => 5, 'rute' => 'UPT ke Ibu Kota Kecamatan (Rinhat)', 'jarak_km' => 5.0, 'sarana_angkutan' => 'Roda dua', 'tempat_pemberangkatan' => 'UPT', 'kondisi_jalan' => 'Pengerasan', 'waktu_tempuh' => '15 menit', 'ongkos_rp' => 7000.0, 'keterangan' => null],

            // SP Weain (id 6)
            ['id_rute_aksesibilitas_sp' => 16, 'satuan_permukiman_id' => 6, 'rute' => 'Kupang ke UPT', 'jarak_km' => 258.0, 'sarana_angkutan' => 'Angkutan darat', 'tempat_pemberangkatan' => 'Terminal Kupang', 'kondisi_jalan' => 'Sebagian pengerasan', 'waktu_tempuh' => '7,5 jam', 'ongkos_rp' => 140000.0, 'keterangan' => 'Ruas terakhir 7 km jalan tanah, sulit saat musim hujan.'],
            ['id_rute_aksesibilitas_sp' => 17, 'satuan_permukiman_id' => 6, 'rute' => 'UPT ke Ibu Kota Kecamatan (Rinhat)', 'jarak_km' => 7.0, 'sarana_angkutan' => 'Roda dua', 'tempat_pemberangkatan' => 'UPT', 'kondisi_jalan' => 'Tanah dan pengerasan', 'waktu_tempuh' => '25 menit', 'ongkos_rp' => 10000.0, 'keterangan' => null],
        ];

        if ($satuanPermukimanId === null) {
            return $data;
        }

        return array_values(array_filter($data, fn ($b) => $b['satuan_permukiman_id'] === $satuanPermukimanId));
    }

    /**
     * Daftar transmigran beserta data kependudukannya.
     *
     * `jumlah_anggota_keluarga` DITURUNKAN sejak 2026-08-28 (Rombongan B):
     * 1 (kepala keluarga) + cacah baris `anggotaKeluarga()` untuk keluarga
     * ini. Sebelumnya disimpan sebagai angka justru karena belum ada baris
     * anggota untuk dihitung (erd.md 7.4, kini dibalik). Menyimpannya membuat
     * nilainya dapat berselisih dengan daftar anggota yang sebenarnya.
     *
     * @return array<int, array<string, mixed>> Data transmigran
     */
    public static function transmigran(): array
    {
        $data = [
            [
                'id_transmigran' => 1,
                'nik' => '5321011505800001',
                'tempat_lahir' => 'KUPANG',
                'no_kk' => '5321010102150001',
                'nama_kepala_keluarga' => 'YOHANES BERE',
                'jenis_kelamin' => 'Laki-laki',
                'agama' => 'Katolik',
                'tanggal_lahir' => '1980-05-15',
                'pendidikan_terakhir' => 'SMA/SMK',
                'pekerjaan_kepala_keluarga' => 'PETANI',
                'pendapatan_per_bulan' => 2350000,
                'daerah_asal_kabupaten_id' => 5301,
                'tahun_kedatangan' => 2016,
                'status_tinggal' => StatusTinggal::Aktif->value,
                'status_anggota_poktan' => 'Ya',
                'status_sertifikat' => 'Sudah',
                'telepon' => '081234567801',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
            ],
            [
                'id_transmigran' => 2,
                'nik' => '5321012203850002',
                'tempat_lahir' => 'ATAMBUA',
                'no_kk' => '5321010102150002',
                'nama_kepala_keluarga' => 'MARIA DA COSTA',
                'jenis_kelamin' => 'Perempuan',
                'agama' => 'Katolik',
                'tanggal_lahir' => '1985-03-22',
                'pendidikan_terakhir' => 'SMP',
                'pekerjaan_kepala_keluarga' => 'PETANI',
                'pendapatan_per_bulan' => 1850000,
                'daerah_asal_kabupaten_id' => 5304,
                'tahun_kedatangan' => 2016,
                'status_tinggal' => StatusTinggal::Aktif->value,
                'status_anggota_poktan' => 'Ya',
                'status_sertifikat' => 'Sudah',
                'telepon' => '081234567802',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
            ],
            [
                'id_transmigran' => 3,
                'nik' => '5321010809780003',
                'tempat_lahir' => 'SOE',
                'no_kk' => '5321010102160003',
                'nama_kepala_keluarga' => 'PETRUS NAHAK',
                'jenis_kelamin' => 'Laki-laki',
                'agama' => 'Katolik',
                'tanggal_lahir' => '1978-09-08',
                'pendidikan_terakhir' => 'SD',
                'pekerjaan_kepala_keluarga' => 'PETANI',
                'pendapatan_per_bulan' => 2100000,
                'daerah_asal_kabupaten_id' => 5302,
                'tahun_kedatangan' => 2016,
                'status_tinggal' => StatusTinggal::Aktif->value,
                'status_anggota_poktan' => 'Tidak',
                'status_sertifikat' => 'Belum',
                'telepon' => '081234567803',
                'satuan_permukiman' => 'SP Tniumanu',
                'satuan_permukiman_id' => 2,
            ],
            [
                'id_transmigran' => 4,
                'nik' => '5321011712900004',
                'tempat_lahir' => 'BETUN',
                'no_kk' => '5321010102170004',
                'nama_kepala_keluarga' => 'ANGELA SERAN',
                'jenis_kelamin' => 'Perempuan',
                'agama' => 'Katolik',
                'tanggal_lahir' => '1990-12-17',
                'pendidikan_terakhir' => 'SMA/SMK',
                'pekerjaan_kepala_keluarga' => 'PEDAGANG',
                'pendapatan_per_bulan' => 2750000,
                'daerah_asal_kabupaten_id' => 5321,
                'tahun_kedatangan' => 2017,
                'status_tinggal' => StatusTinggal::Aktif->value,
                'status_anggota_poktan' => 'Tidak',
                'status_sertifikat' => 'Belum Didata',
                'telepon' => '081234567804',
                'satuan_permukiman' => 'SP Harekakae',
                'satuan_permukiman_id' => 3,
            ],
            [
                'id_transmigran' => 5,
                'nik' => '5321010304820005',
                'tempat_lahir' => 'ATAMBUA',
                'no_kk' => '5321010102170005',
                'nama_kepala_keluarga' => 'DOMINGGUS TAEK',
                'jenis_kelamin' => 'Laki-laki',
                'agama' => 'Kristen',
                'tanggal_lahir' => '1982-04-03',
                'pendidikan_terakhir' => 'SMP',
                'pekerjaan_kepala_keluarga' => 'PETANI',
                'pendapatan_per_bulan' => 1950000,
                'daerah_asal_kabupaten_id' => 5304,
                'tahun_kedatangan' => 2017,
                'status_tinggal' => StatusTinggal::PindahPenduduk->value,
                'status_anggota_poktan' => 'Ya',
                'status_sertifikat' => 'Belum',
                'telepon' => '081234567805',
                'satuan_permukiman' => 'SP Weoe / Uluk Lubuk',
                'satuan_permukiman_id' => 4,
            ],
            [
                'id_transmigran' => 6,
                'nik' => '5321012511870006',
                'tempat_lahir' => 'KUPANG',
                'no_kk' => '5321010102180006',
                'nama_kepala_keluarga' => 'FRANSISKA BRIA',
                'jenis_kelamin' => 'Perempuan',
                'agama' => 'Katolik',
                'tanggal_lahir' => '1987-11-25',
                'pendidikan_terakhir' => 'Diploma',
                'pekerjaan_kepala_keluarga' => 'GURU',
                'pendapatan_per_bulan' => 3200000,
                'daerah_asal_kabupaten_id' => 5301,
                'tahun_kedatangan' => 2018,
                'status_tinggal' => StatusTinggal::Aktif->value,
                'status_anggota_poktan' => 'Tidak',
                'status_sertifikat' => 'Belum Didata',
                'telepon' => '081234567806',
                'satuan_permukiman' => 'SP Tualaran',
                'satuan_permukiman_id' => 5,
            ],
            [
                'id_transmigran' => 7,
                'nik' => '5321010107750007',
                'tempat_lahir' => 'KEFAMENANU',
                'no_kk' => '5321010102180007',
                'nama_kepala_keluarga' => 'GABRIEL LEKI',
                'jenis_kelamin' => 'Laki-laki',
                'agama' => 'Katolik',
                'tanggal_lahir' => '1975-07-01',
                'pendidikan_terakhir' => 'SD',
                'pekerjaan_kepala_keluarga' => 'PETANI',
                'pendapatan_per_bulan' => 1700000,
                'daerah_asal_kabupaten_id' => 5303,
                'tahun_kedatangan' => 2018,
                'status_tinggal' => StatusTinggal::Aktif->value,
                'status_anggota_poktan' => 'Ya',
                'status_sertifikat' => 'Sudah',
                'telepon' => '081234567807',
                'satuan_permukiman' => 'SP Weain',
                'satuan_permukiman_id' => 6,
            ],
            [
                'id_transmigran' => 8,
                'nik' => '5321011409910008',
                'tempat_lahir' => 'BETUN',
                'no_kk' => '5321010102190008',
                'nama_kepala_keluarga' => 'YULITA HOAR',
                'jenis_kelamin' => 'Perempuan',
                'agama' => 'Katolik',
                'tanggal_lahir' => '1991-09-14',
                'pendidikan_terakhir' => 'SMA/SMK',
                'pekerjaan_kepala_keluarga' => 'BURUH TANI',
                'pendapatan_per_bulan' => 1450000,
                'daerah_asal_kabupaten_id' => 5321,
                'tahun_kedatangan' => 2019,
                'status_tinggal' => StatusTinggal::Aktif->value,
                'status_anggota_poktan' => 'Ya',
                'status_sertifikat' => 'Belum',
                'telepon' => '081234567808',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
            ],
        ];

        // Cacah anggota keluarga per keluarga, untuk menurunkan
        // `jumlah_anggota_keluarga` (lihat docblock).
        $cacahAnggota = [];
        foreach (self::anggotaKeluarga() as $anggota) {
            // Anggota yang sudah meninggal atau pindah tidak ikut dihitung
            // sebagai jiwa keluarga (Putaran 6).
            if (($anggota['status'] ?? StatusAnggotaKeluarga::Aktif->value) !== StatusAnggotaKeluarga::Aktif->value) {
                continue;
            }
            $cacahAnggota[$anggota['transmigran_id']] = ($cacahAnggota[$anggota['transmigran_id']] ?? 0) + 1;
        }

        return array_map(
            fn ($t) => $t + [
                'jumlah_anggota_keluarga' => 1 + ($cacahAnggota[$t['id_transmigran']] ?? 0),
            ],
            $data,
        );
    }

    /**
     * Anggota keluarga transmigran, satu baris per orang SELAIN kepala keluarga.
     *
     * Ditambahkan 2026-08-28 (Rombongan B). Membalik keputusan erd.md 7.4
     * yang menyatakan sistem tidak mendata anggota keluarga satu per satu;
     * pemilik proyek memintanya agar suksesi kepala keluarga dan pemilihan
     * wakil poktan tidak lagi mengetik nama dari nol.
     *
     * `usia` TIDAK disimpan, dihitung dari `tanggal_lahir` (bertambah sendiri
     * tiap tahun). `nik` boleh kosong bagi balita yang belum memilikinya.
     *
     * Cabang isian menurut `kegiatan` (lihat App\Enums\KegiatanAnggota):
     * Bekerja mengisi `pendidikan_terakhir` + `pekerjaan` + `pendapatan_per_bulan`;
     * Masih Sekolah mengisi `pendidikan_terakhir` sebagai jenjang berjalan;
     * Belum Sekolah tidak mengisi apa-apa.
     *
     * `status`, `tanggal_peristiwa`, dan `keterangan_peristiwa` ditambahkan
     * 2026-08-29 (Putaran 6), membalik sebagian agents/rules.md 9c: anggota
     * yang meninggal atau pindah TIDAK lagi dihapus, melainkan ditandai di
     * sini supaya Laporan Monografi SP bisa menghitung mutasi penduduk.
     * Anggota non-Aktif dikeluarkan dari cacah `jumlah_anggota_keluarga`.
     *
     * @return array<int, array<string, mixed>> Data anggota keluarga
     */
    public static function anggotaKeluarga(): array
    {
        $data = [
            // Keluarga 1 - YOHANES BERE (L): istri + tiga anak.
            [
                'id_anggota_keluarga' => 1, 'transmigran_id' => 1, 'hubungan' => 'Istri',
                'nama_lengkap' => 'MARIA BERE HOAR', 'nik' => '5321015507830011',
                'jenis_kelamin' => 'Perempuan', 'tempat_lahir' => 'BETUN', 'tanggal_lahir' => '1983-07-15',
                'agama' => 'Katolik', 'kegiatan' => 'Bekerja', 'pendidikan_terakhir' => 'SMP',
                'pekerjaan' => 'BURUH TANI', 'pendapatan_per_bulan' => 900000,
                'telepon' => '081234567811', 'keterangan' => null,
            ],
            [
                'id_anggota_keluarga' => 2, 'transmigran_id' => 1, 'hubungan' => 'Anak',
                'nama_lengkap' => 'YOSEP BERE', 'nik' => '5321010204030012',
                'jenis_kelamin' => 'Laki-laki', 'tempat_lahir' => 'KUPANG', 'tanggal_lahir' => '2003-04-02',
                'agama' => 'Katolik', 'kegiatan' => 'Bekerja', 'pendidikan_terakhir' => 'SMA/SMK',
                'pekerjaan' => 'PETANI', 'pendapatan_per_bulan' => 1200000,
                'telepon' => '081234567812', 'keterangan' => null,
            ],
            [
                'id_anggota_keluarga' => 3, 'transmigran_id' => 1, 'hubungan' => 'Anak',
                'nama_lengkap' => 'ANANIA BERE', 'nik' => '5321014109100013',
                'jenis_kelamin' => 'Perempuan', 'tempat_lahir' => 'KAPITAN MEO', 'tanggal_lahir' => '2010-09-01',
                'agama' => 'Katolik', 'kegiatan' => 'Masih Sekolah', 'pendidikan_terakhir' => 'SMP',
                'pekerjaan' => null, 'pendapatan_per_bulan' => null,
                'telepon' => null, 'keterangan' => 'Kelas 3 SMP Negeri Kapitan Meo.',
            ],
            [
                'id_anggota_keluarga' => 4, 'transmigran_id' => 1, 'hubungan' => 'Anak',
                'nama_lengkap' => 'GERSON BERE', 'nik' => null,
                'jenis_kelamin' => 'Laki-laki', 'tempat_lahir' => 'KAPITAN MEO', 'tanggal_lahir' => '2022-02-19',
                'agama' => 'Katolik', 'kegiatan' => 'Belum Sekolah', 'pendidikan_terakhir' => null,
                'pekerjaan' => null, 'pendapatan_per_bulan' => null,
                'telepon' => null, 'keterangan' => 'Belum memiliki NIK, menunggu perekaman.',
            ],

            // Keluarga 2 - MARIA DA COSTA (P): suami + dua anak.
            [
                'id_anggota_keluarga' => 5, 'transmigran_id' => 2, 'hubungan' => 'Suami',
                'nama_lengkap' => 'YOSEF DA COSTA', 'nik' => '5321011811820021',
                'jenis_kelamin' => 'Laki-laki', 'tempat_lahir' => 'ATAMBUA', 'tanggal_lahir' => '1982-11-18',
                'agama' => 'Katolik', 'kegiatan' => 'Bekerja', 'pendidikan_terakhir' => 'SMP',
                'pekerjaan' => 'TUKANG KAYU', 'pendapatan_per_bulan' => 1600000,
                'telepon' => '081234567821', 'keterangan' => null,
            ],
            [
                'id_anggota_keluarga' => 6, 'transmigran_id' => 2, 'hubungan' => 'Anak',
                'nama_lengkap' => 'FELISITAS DA COSTA', 'nik' => '5321015003090022',
                'jenis_kelamin' => 'Perempuan', 'tempat_lahir' => 'KAPITAN MEO', 'tanggal_lahir' => '2009-03-10',
                'agama' => 'Katolik', 'kegiatan' => 'Masih Sekolah', 'pendidikan_terakhir' => 'SMA/SMK',
                'pekerjaan' => null, 'pendapatan_per_bulan' => null,
                'telepon' => null, 'keterangan' => null,
            ],
            [
                'id_anggota_keluarga' => 7, 'transmigran_id' => 2, 'hubungan' => 'Anak',
                'nama_lengkap' => 'MARSEL DA COSTA', 'nik' => '5321012006130023',
                'jenis_kelamin' => 'Laki-laki', 'tempat_lahir' => 'KAPITAN MEO', 'tanggal_lahir' => '2013-06-20',
                'agama' => 'Katolik', 'kegiatan' => 'Masih Sekolah', 'pendidikan_terakhir' => 'SD',
                'pekerjaan' => null, 'pendapatan_per_bulan' => null,
                'telepon' => null, 'keterangan' => null,
            ],

            // Keluarga 3 - PETRUS NAHAK (L): istri + tiga anak + ibu.
            [
                'id_anggota_keluarga' => 8, 'transmigran_id' => 3, 'hubungan' => 'Istri',
                'nama_lengkap' => 'YOVITA NAHAK SERAN', 'nik' => '5321016502800031',
                'jenis_kelamin' => 'Perempuan', 'tempat_lahir' => 'SOE', 'tanggal_lahir' => '1980-02-25',
                'agama' => 'Katolik', 'kegiatan' => 'Tidak Bekerja', 'pendidikan_terakhir' => 'SD',
                'pekerjaan' => null, 'pendapatan_per_bulan' => null,
                'telepon' => '081234567831', 'keterangan' => 'Mengurus rumah tangga.',
            ],
            [
                'id_anggota_keluarga' => 9, 'transmigran_id' => 3, 'hubungan' => 'Anak',
                'nama_lengkap' => 'AGUSTINUS NAHAK', 'nik' => '5321011708000032',
                'jenis_kelamin' => 'Laki-laki', 'tempat_lahir' => 'SOE', 'tanggal_lahir' => '2000-08-17',
                'agama' => 'Katolik', 'kegiatan' => 'Bekerja', 'pendidikan_terakhir' => 'SMA/SMK',
                'pekerjaan' => 'PETANI', 'pendapatan_per_bulan' => 1400000,
                'telepon' => '081234567832', 'keterangan' => null,
            ],
            [
                'id_anggota_keluarga' => 10, 'transmigran_id' => 3, 'hubungan' => 'Anak',
                'nama_lengkap' => 'REGINA NAHAK', 'nik' => '5321015512050033',
                'jenis_kelamin' => 'Perempuan', 'tempat_lahir' => 'TNIUMANU', 'tanggal_lahir' => '2005-12-15',
                'agama' => 'Katolik', 'kegiatan' => 'Tidak Bekerja', 'pendidikan_terakhir' => 'SMP',
                'pekerjaan' => null, 'pendapatan_per_bulan' => null,
                'telepon' => null, 'keterangan' => 'Membantu usaha tani keluarga tanpa upah tetap.',
            ],
            [
                'id_anggota_keluarga' => 11, 'transmigran_id' => 3, 'hubungan' => 'Anak',
                'nama_lengkap' => 'DAMIANUS NAHAK', 'nik' => null,
                'jenis_kelamin' => 'Laki-laki', 'tempat_lahir' => 'TNIUMANU', 'tanggal_lahir' => '2021-10-05',
                'agama' => 'Katolik', 'kegiatan' => 'Belum Sekolah', 'pendidikan_terakhir' => null,
                'pekerjaan' => null, 'pendapatan_per_bulan' => null,
                'telepon' => null, 'keterangan' => null,
            ],
            [
                'id_anggota_keluarga' => 12, 'transmigran_id' => 3, 'hubungan' => 'Orang Tua',
                'nama_lengkap' => 'ROSALIA SERAN', 'nik' => '5321014403520034',
                'jenis_kelamin' => 'Perempuan', 'tempat_lahir' => 'SOE', 'tanggal_lahir' => '1952-03-04',
                'agama' => 'Katolik', 'kegiatan' => 'Tidak Bekerja', 'pendidikan_terakhir' => 'Tidak Sekolah',
                'pekerjaan' => null, 'pendapatan_per_bulan' => null,
                'telepon' => null, 'keterangan' => 'Ibu kandung kepala keluarga, ikut tinggal sejak 2019.',
            ],

            // Keluarga 4 - ANGELA SERAN (P, tanpa pasangan): dua anak.
            [
                'id_anggota_keluarga' => 13, 'transmigran_id' => 4, 'hubungan' => 'Anak',
                'nama_lengkap' => 'PATRISIA SERAN', 'nik' => '5321016009110041',
                'jenis_kelamin' => 'Perempuan', 'tempat_lahir' => 'BETUN', 'tanggal_lahir' => '2011-09-20',
                'agama' => 'Katolik', 'kegiatan' => 'Masih Sekolah', 'pendidikan_terakhir' => 'SMP',
                'pekerjaan' => null, 'pendapatan_per_bulan' => null,
                'telepon' => null, 'keterangan' => null,
            ],
            [
                'id_anggota_keluarga' => 14, 'transmigran_id' => 4, 'hubungan' => 'Anak',
                'nama_lengkap' => 'YANUARIUS SERAN', 'nik' => '5321010703160042',
                'jenis_kelamin' => 'Laki-laki', 'tempat_lahir' => 'HAREKAKAE', 'tanggal_lahir' => '2016-03-07',
                'agama' => 'Katolik', 'kegiatan' => 'Masih Sekolah', 'pendidikan_terakhir' => 'SD',
                'pekerjaan' => null, 'pendapatan_per_bulan' => null,
                'telepon' => null, 'keterangan' => null,
            ],

            // Keluarga 5 - DOMINGGUS TAEK (L, Pindah Penduduk): istri + dua anak + anak angkat.
            [
                'id_anggota_keluarga' => 15, 'transmigran_id' => 5, 'hubungan' => 'Istri',
                'nama_lengkap' => 'BERNADETA TAEK', 'nik' => '5321015208850051',
                'jenis_kelamin' => 'Perempuan', 'tempat_lahir' => 'ATAMBUA', 'tanggal_lahir' => '1985-08-12',
                'agama' => 'Kristen', 'kegiatan' => 'Bekerja', 'pendidikan_terakhir' => 'SMP',
                'pekerjaan' => 'PEDAGANG', 'pendapatan_per_bulan' => 1100000,
                'telepon' => '081234567851', 'keterangan' => null,
            ],
            [
                'id_anggota_keluarga' => 16, 'transmigran_id' => 5, 'hubungan' => 'Anak',
                'nama_lengkap' => 'IMANUEL TAEK', 'nik' => '5321012501070052',
                'jenis_kelamin' => 'Laki-laki', 'tempat_lahir' => 'BELU', 'tanggal_lahir' => '2007-01-25',
                'agama' => 'Kristen', 'kegiatan' => 'Masih Sekolah', 'pendidikan_terakhir' => 'SMA/SMK',
                'pekerjaan' => null, 'pendapatan_per_bulan' => null,
                'telepon' => null, 'keterangan' => null,
            ],
            [
                'id_anggota_keluarga' => 17, 'transmigran_id' => 5, 'hubungan' => 'Anak',
                'nama_lengkap' => 'PRISKILA TAEK', 'nik' => '5321015504120053',
                'jenis_kelamin' => 'Perempuan', 'tempat_lahir' => 'WEOE', 'tanggal_lahir' => '2012-04-15',
                'agama' => 'Kristen', 'kegiatan' => 'Masih Sekolah', 'pendidikan_terakhir' => 'SD',
                'pekerjaan' => null, 'pendapatan_per_bulan' => null,
                'telepon' => null, 'keterangan' => null,
            ],
            [
                'id_anggota_keluarga' => 18, 'transmigran_id' => 5, 'hubungan' => 'Anak Angkat',
                'nama_lengkap' => 'OKTAVIANUS TAEK', 'nik' => '5321011010140054',
                'jenis_kelamin' => 'Laki-laki', 'tempat_lahir' => 'WEOE', 'tanggal_lahir' => '2014-10-10',
                'agama' => 'Kristen', 'kegiatan' => 'Masih Sekolah', 'pendidikan_terakhir' => 'SD',
                'pekerjaan' => null, 'pendapatan_per_bulan' => null,
                'telepon' => null, 'keterangan' => 'Anak dari keluarga kerabat, diasuh sejak 2017.',
            ],

            // Keluarga 6 - FRANSISKA BRIA (P, GURU): suami + dua anak.
            [
                'id_anggota_keluarga' => 19, 'transmigran_id' => 6, 'hubungan' => 'Suami',
                'nama_lengkap' => 'GREGORIUS BRIA', 'nik' => '5321010906840061',
                'jenis_kelamin' => 'Laki-laki', 'tempat_lahir' => 'KEFAMENANU', 'tanggal_lahir' => '1984-06-09',
                'agama' => 'Katolik', 'kegiatan' => 'Bekerja', 'pendidikan_terakhir' => 'SMA/SMK',
                'pekerjaan' => 'PETANI', 'pendapatan_per_bulan' => 1500000,
                'telepon' => '081234567861', 'keterangan' => null,
            ],
            [
                'id_anggota_keluarga' => 20, 'transmigran_id' => 6, 'hubungan' => 'Anak',
                'nama_lengkap' => 'CLARA BRIA', 'nik' => '5321014712120062',
                'jenis_kelamin' => 'Perempuan', 'tempat_lahir' => 'TUALARAN', 'tanggal_lahir' => '2012-12-07',
                'agama' => 'Katolik', 'kegiatan' => 'Masih Sekolah', 'pendidikan_terakhir' => 'SD',
                'pekerjaan' => null, 'pendapatan_per_bulan' => null,
                'telepon' => null, 'keterangan' => null,
            ],
            [
                'id_anggota_keluarga' => 21, 'transmigran_id' => 6, 'hubungan' => 'Anak',
                'nama_lengkap' => 'DAVID BRIA', 'nik' => null,
                'jenis_kelamin' => 'Laki-laki', 'tempat_lahir' => 'TUALARAN', 'tanggal_lahir' => '2020-05-30',
                'agama' => 'Katolik', 'kegiatan' => 'Belum Sekolah', 'pendidikan_terakhir' => null,
                'pekerjaan' => null, 'pendapatan_per_bulan' => null,
                'telepon' => null, 'keterangan' => null,
            ],

            // Keluarga 7 - GABRIEL LEKI (L): istri + empat anak + keponakan.
            [
                'id_anggota_keluarga' => 22, 'transmigran_id' => 7, 'hubungan' => 'Istri',
                'nama_lengkap' => 'ELISABETH LEKI', 'nik' => '5321014403780071',
                'jenis_kelamin' => 'Perempuan', 'tempat_lahir' => 'KEFAMENANU', 'tanggal_lahir' => '1978-03-04',
                'agama' => 'Katolik', 'kegiatan' => 'Bekerja', 'pendidikan_terakhir' => 'SD',
                'pekerjaan' => 'BURUH TANI', 'pendapatan_per_bulan' => 850000,
                'telepon' => '081234567871', 'keterangan' => null,
            ],
            [
                'id_anggota_keluarga' => 23, 'transmigran_id' => 7, 'hubungan' => 'Anak',
                'nama_lengkap' => 'YUSTINA LEKI', 'nik' => '5321015901990072',
                'jenis_kelamin' => 'Perempuan', 'tempat_lahir' => 'TIMOR TENGAH UTARA', 'tanggal_lahir' => '1999-01-19',
                'agama' => 'Katolik', 'kegiatan' => 'Bekerja', 'pendidikan_terakhir' => 'SMP',
                'pekerjaan' => 'BURUH TANI', 'pendapatan_per_bulan' => 950000,
                'telepon' => '081234567872', 'keterangan' => null,
            ],
            [
                'id_anggota_keluarga' => 24, 'transmigran_id' => 7, 'hubungan' => 'Anak',
                'nama_lengkap' => 'PAULUS LEKI', 'nik' => '5321012208040073',
                'jenis_kelamin' => 'Laki-laki', 'tempat_lahir' => 'WEAIN', 'tanggal_lahir' => '2004-08-22',
                'agama' => 'Katolik', 'kegiatan' => 'Bekerja', 'pendidikan_terakhir' => 'SMA/SMK',
                'pekerjaan' => 'PETANI', 'pendapatan_per_bulan' => 1300000,
                'telepon' => '081234567873', 'keterangan' => null,
            ],
            [
                'id_anggota_keluarga' => 25, 'transmigran_id' => 7, 'hubungan' => 'Anak',
                'nama_lengkap' => 'MARGARETHA LEKI', 'nik' => '5321016307100074',
                'jenis_kelamin' => 'Perempuan', 'tempat_lahir' => 'WEAIN', 'tanggal_lahir' => '2010-07-23',
                'agama' => 'Katolik', 'kegiatan' => 'Masih Sekolah', 'pendidikan_terakhir' => 'SMP',
                'pekerjaan' => null, 'pendapatan_per_bulan' => null,
                'telepon' => null, 'keterangan' => null,
            ],
            [
                'id_anggota_keluarga' => 26, 'transmigran_id' => 7, 'hubungan' => 'Anak',
                'nama_lengkap' => 'THOMAS LEKI', 'nik' => '5321011503170075',
                'jenis_kelamin' => 'Laki-laki', 'tempat_lahir' => 'WEAIN', 'tanggal_lahir' => '2017-03-15',
                'agama' => 'Katolik', 'kegiatan' => 'Masih Sekolah', 'pendidikan_terakhir' => 'SD',
                'pekerjaan' => null, 'pendapatan_per_bulan' => null,
                'telepon' => null, 'keterangan' => null,
            ],
            [
                'id_anggota_keluarga' => 27, 'transmigran_id' => 7, 'hubungan' => 'Famili Lain',
                'nama_lengkap' => 'KORNELIUS TAHU', 'nik' => '5321012409020076',
                'jenis_kelamin' => 'Laki-laki', 'tempat_lahir' => 'KEFAMENANU', 'tanggal_lahir' => '2002-09-24',
                'agama' => 'Katolik', 'kegiatan' => 'Bekerja', 'pendidikan_terakhir' => 'SMA/SMK',
                'pekerjaan' => 'BURUH TANI', 'pendapatan_per_bulan' => 1000000,
                'telepon' => '081234567876', 'keterangan' => 'Keponakan kepala keluarga, ikut menggarap lahan.',
            ],

            // Keluarga 8 - YULITA HOAR (P, tanpa pasangan): dua anak.
            [
                'id_anggota_keluarga' => 30, 'transmigran_id' => 8, 'hubungan' => 'Famili Lain',
                'nama_lengkap' => 'ANDREAS HOAR', 'nik' => '5321011803050108',
                'jenis_kelamin' => 'Laki-laki', 'tempat_lahir' => 'BETUN', 'tanggal_lahir' => '1995-03-18',
                'agama' => 'Katolik', 'kegiatan' => 'Bekerja', 'pendidikan_terakhir' => 'SMA/SMK',
                'pekerjaan' => 'PETANI', 'pendapatan_per_bulan' => 1300000,
                'telepon' => '081234567808', 'keterangan' => 'Adik kepala keluarga, mewakili keluarga di POKTAN MEKAR JAYA.',
            ],
            [
                'id_anggota_keluarga' => 28, 'transmigran_id' => 8, 'hubungan' => 'Anak',
                'nama_lengkap' => 'FIDELIS HOAR', 'nik' => '5321011106120081',
                'jenis_kelamin' => 'Laki-laki', 'tempat_lahir' => 'BETUN', 'tanggal_lahir' => '2012-06-11',
                'agama' => 'Katolik', 'kegiatan' => 'Masih Sekolah', 'pendidikan_terakhir' => 'SD',
                'pekerjaan' => null, 'pendapatan_per_bulan' => null,
                'telepon' => null, 'keterangan' => null,
            ],
            [
                'id_anggota_keluarga' => 29, 'transmigran_id' => 8, 'hubungan' => 'Anak',
                'nama_lengkap' => 'IRENA HOAR', 'nik' => null,
                'jenis_kelamin' => 'Perempuan', 'tempat_lahir' => 'KAPITAN MEO', 'tanggal_lahir' => '2023-01-08',
                'agama' => 'Katolik', 'kegiatan' => 'Belum Sekolah', 'pendidikan_terakhir' => null,
                'pekerjaan' => null, 'pendapatan_per_bulan' => null,
                'telepon' => null, 'keterangan' => null,
            ],
        ];

        // Peristiwa mutasi anggota keluarga (Putaran 6). Baris tetap ada,
        // hanya ditandai. Kepala keluarga tidak di sini ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â peristiwanya lewat
        // alur ganti kepala keluarga.
        $peristiwa = [
            12 => ['status' => 'Meninggal', 'tanggal_peristiwa' => '2024-03-12',
                'keterangan_peristiwa' => 'Meninggal karena sakit usia lanjut.'],
            27 => ['status' => 'Pindah', 'tanggal_peristiwa' => '2025-07-01',
                'keterangan_peristiwa' => 'Merantau ke Kupang mengikuti kerabat, tidak kembali ke lokasi.'],
        ];

        return array_map(function ($b) use ($peristiwa) {
            $p = $peristiwa[$b['id_anggota_keluarga']] ?? [];

            return $b + [
                'status' => $p['status'] ?? StatusAnggotaKeluarga::Aktif->value,
                'tanggal_peristiwa' => $p['tanggal_peristiwa'] ?? null,
                'keterangan_peristiwa' => $p['keterangan_peristiwa'] ?? null,
            ];
        }, $data);
    }

    /**
     * Satu anggota keluarga menurut idnya, atau null bila tidak ada.
     *
     * Dipakai saat wakil poktan atau ketua poktan memilih anggota keluarga
     * dari daftar (Stage B2): nama, NIK, telepon, dan hubungannya dibaca dari
     * baris ini, bukan diketik ulang.
     *
     * @return array<string, mixed>|null
     */
    public static function cariAnggotaKeluarga(?int $id): ?array
    {
        if ($id === null) {
            return null;
        }

        foreach (self::anggotaKeluarga() as $baris) {
            if ($baris['id_anggota_keluarga'] === $id) {
                return $baris;
            }
        }

        return null;
    }

    /**
     * Anggota keluarga dikelompokkan menurut keluarga (transmigran_id).
     *
     * Dibaca Alpine pada form poktan agar pilihan wakil/ketua menyempit ke
     * anggota keluarga yang bersangkutan begitu keluarganya dipilih.
     *
     * @return array<int, array<int, array{id: int, nama: string, hubungan: string, nik: string|null}>>
     */
    public static function anggotaKeluargaPerKeluarga(): array
    {
        $peta = [];

        foreach (self::anggotaKeluarga() as $a) {
            if (($a['status'] ?? 'Aktif') !== StatusAnggotaKeluarga::Aktif->value) {
                continue;
            }
            $peta[$a['transmigran_id']][] = [
                'id' => $a['id_anggota_keluarga'],
                'nama' => $a['nama_lengkap'],
                'hubungan' => $a['hubungan'],
                'nik' => $a['nik'],
            ];
        }

        return $peta;
    }

    /**
     * Calon pengganti kepala keluarga: anggota keluarga yang ada.
     *
     * Dipakai modal suksesi (Stage B3, 2026-08-28). Pengganti dipilih dari
     * daftar ini, bukan diketik; nama, NIK, dan hubungannya "naik" menimpa
     * baris `transmigran`, lalu baris `anggota_keluarga` pengganti dihapus.
     *
     * Urutan Dukcapil (pasangan lalu anak tertua) tidak ditegakkan sistem;
     * yang direkam adalah siapa yang benar-benar ditunjuk. Pasangan
     * ditampilkan lebih dulu sebagai penunjuk lazim, sisanya menurut usia.
     *
     * @return array<int, array{id: int, nama: string, nik: string|null, hubungan: string, jenis_kelamin: string|null, usia: int|null}>
     */
    public static function calonPenggantiKk(int $transmigranId): array
    {
        $anggota = array_values(array_filter(
            self::anggotaKeluarga(),
            fn ($a) => $a['transmigran_id'] === $transmigranId
                && ($a['status'] ?? 'Aktif') === StatusAnggotaKeluarga::Aktif->value
        ));

        $usia = function (?string $tanggal): ?int {
            if ($tanggal === null) {
                return null;
            }

            try {
                return Carbon::parse($tanggal)->age;
            } catch (\Throwable) {
                return null;
            }
        };

        $nilaiPasangan = array_map(fn ($h) => $h->value, HubunganAnggotaKeluarga::pasangan());

        usort($anggota, function ($x, $y) use ($usia, $nilaiPasangan) {
            $pasanganX = in_array($x['hubungan'], $nilaiPasangan, true);
            $pasanganY = in_array($y['hubungan'], $nilaiPasangan, true);

            if ($pasanganX !== $pasanganY) {
                return $pasanganX ? -1 : 1;
            }

            return ($usia($y['tanggal_lahir']) ?? 0) <=> ($usia($x['tanggal_lahir']) ?? 0);
        });

        return array_map(fn ($a) => [
            'id' => $a['id_anggota_keluarga'],
            'nama' => $a['nama_lengkap'],
            'nik' => $a['nik'],
            'hubungan' => $a['hubungan'],
            'jenis_kelamin' => $a['jenis_kelamin'] ?? null,
            'usia' => $usia($a['tanggal_lahir'] ?? null),
        ], $anggota);
    }

    /**
     * Registry metadata seluruh berkas sistem (Putaran 12, 2026-09-02).
     *
     * Menggantikan 24 kolom VARCHAR path yang dahulu tersebar di 17 tabel.
     * Alasannya bukan keseragaman semata: kolom path telanjang TIDAK merekam
     * `mime` maupun `ukuran`, padahal agents/rules.md 14a.1 dan 14a.2
     * mewajibkan keduanya divalidasi di sisi server. Tanpa merekamnya, tidak
     * ada cara memeriksa ulang apa yang sebenarnya tersimpan.
     *
     * BUKAN tabel polymorphic. Kepemilikan dinyatakan pivot per domain, bukan
     * kolom `entity_type`/`entity_id`; lihat berkasPemilik().
     *
     * @return array<int, array<string, mixed>> Metadata berkas
     */
    public static function berkas(): array
    {
        return [
            ['id_berkas' => 1, 'uuid' => 'brk-0001', 'jenis_berkas_id' => null, 'nama_file' => 'sk-penetapan-kobalima-timur.pdf', 'nama_asli' => 'sk-penetapan-kobalima-timur.pdf', 'path' => 'kawasan/1/sk-penetapan-kobalima-timur.pdf', 'mime' => 'application/pdf', 'ekstensi' => 'pdf', 'ukuran' => 412000, 'disk' => 'local', 'keterangan' => 'SK Penetapan Kawasan', 'user_id' => 1],
            ['id_berkas' => 2, 'uuid' => 'brk-0002', 'jenis_berkas_id' => null, 'nama_file' => 'sk-penempatan-kapitan-meo.pdf', 'nama_asli' => 'sk-penempatan-kapitan-meo.pdf', 'path' => 'sp/1/sk-penempatan-kapitan-meo.pdf', 'mime' => 'application/pdf', 'ekstensi' => 'pdf', 'ukuran' => 298000, 'disk' => 'local', 'keterangan' => 'SK Penempatan SP', 'user_id' => 1],
            ['id_berkas' => 3, 'uuid' => 'brk-0003', 'jenis_berkas_id' => null, 'nama_file' => 'sk-penempatan-tniumanu.pdf', 'nama_asli' => 'sk-penempatan-tniumanu.pdf', 'path' => 'sp/2/sk-penempatan-tniumanu.pdf', 'mime' => 'application/pdf', 'ekstensi' => 'pdf', 'ukuran' => 287000, 'disk' => 'local', 'keterangan' => 'SK Penempatan SP', 'user_id' => 1],
            ['id_berkas' => 4, 'uuid' => 'brk-0004', 'jenis_berkas_id' => null, 'nama_file' => 'bast-panen-jagung-apr-2026.pdf', 'nama_asli' => 'bast-panen-jagung-apr-2026.pdf', 'path' => 'panen/1/bast-panen-jagung-apr-2026.pdf', 'mime' => 'application/pdf', 'ekstensi' => 'pdf', 'ukuran' => 180500, 'disk' => 'local', 'keterangan' => 'Berita acara panen', 'user_id' => 1],
            ['id_berkas' => 5, 'uuid' => 'brk-0005', 'jenis_berkas_id' => null, 'nama_file' => 'foto-panen-padi-mei-2026.jpg', 'nama_asli' => 'foto-panen-padi-mei-2026.jpg', 'path' => 'panen/3/foto-panen-padi-mei-2026.jpg', 'mime' => 'image/jpeg', 'ekstensi' => 'jpg', 'ukuran' => 742000, 'disk' => 'local', 'keterangan' => 'Foto hamparan panen', 'user_id' => 1],
            ['id_berkas' => 6, 'uuid' => 'brk-0006', 'jenis_berkas_id' => null, 'nama_file' => 'foto-saluran-tersumbat.jpg', 'nama_asli' => 'foto-saluran-tersumbat.jpg', 'path' => 'pengaduan/1/foto-saluran-tersumbat.jpg', 'mime' => 'image/jpeg', 'ekstensi' => 'jpg', 'ukuran' => 865000, 'disk' => 'local', 'keterangan' => 'Bukti dari pelapor', 'user_id' => 1],
            ['id_berkas' => 7, 'uuid' => 'brk-0007', 'jenis_berkas_id' => null, 'nama_file' => 'foto-daun-jagung-terserang.jpg', 'nama_asli' => 'foto-daun-jagung-terserang.jpg', 'path' => 'pengaduan/5/foto-daun-jagung-terserang.jpg', 'mime' => 'image/jpeg', 'ekstensi' => 'jpg', 'ukuran' => 921000, 'disk' => 'local', 'keterangan' => 'Bukti dari pelapor', 'user_id' => 1],
            ['id_berkas' => 8, 'uuid' => 'brk-0008', 'jenis_berkas_id' => null, 'nama_file' => 'kondisi-irigasi-blok-a.jpg', 'nama_asli' => 'kondisi-irigasi-blok-a.jpg', 'path' => 'infrastruktur/1/kondisi-irigasi-blok-a.jpg', 'mime' => 'image/jpeg', 'ekstensi' => 'jpg', 'ukuran' => 688000, 'disk' => 'local', 'keterangan' => 'Foto kondisi lapangan', 'user_id' => 1],
            ['id_berkas' => 9, 'uuid' => 'brk-0009', 'jenis_berkas_id' => null, 'nama_file' => 'berkas-pembangunan-irigasi.pdf', 'nama_asli' => 'berkas-pembangunan-irigasi.pdf', 'path' => 'infrastruktur/1/berkas-pembangunan-irigasi.pdf', 'mime' => 'application/pdf', 'ekstensi' => 'pdf', 'ukuran' => 1240000, 'disk' => 'local', 'keterangan' => 'Berkas pembangunan', 'user_id' => 1],
            ['id_berkas' => 10, 'uuid' => 'brk-0010', 'jenis_berkas_id' => null, 'nama_file' => 'kondisi-kursi-plastik.jpg', 'nama_asli' => 'kondisi-kursi-plastik.jpg', 'path' => 'inventaris/2/kondisi-kursi-plastik.jpg', 'mime' => 'image/jpeg', 'ekstensi' => 'jpg', 'ukuran' => 534000, 'disk' => 'local', 'keterangan' => 'Foto kondisi barang', 'user_id' => 1],
            ['id_berkas' => 11, 'uuid' => 'brk-0011', 'jenis_berkas_id' => null, 'nama_file' => 'berita-acara-kursi.pdf', 'nama_asli' => 'berita-acara-kursi.pdf', 'path' => 'inventaris/2/berita-acara-kursi.pdf', 'mime' => 'application/pdf', 'ekstensi' => 'pdf', 'ukuran' => 165000, 'disk' => 'local', 'keterangan' => 'Berita acara', 'user_id' => 1],
            ['id_berkas' => 12, 'uuid' => 'brk-0012', 'jenis_berkas_id' => null, 'nama_file' => 'kondisi-sekolah-dasar.jpg', 'nama_asli' => 'kondisi-sekolah-dasar.jpg', 'path' => 'fasilitas/1/kondisi-sekolah-dasar.jpg', 'mime' => 'image/jpeg', 'ekstensi' => 'jpg', 'ukuran' => 712000, 'disk' => 'local', 'keterangan' => 'Foto kondisi bangunan', 'user_id' => 1],
            ['id_berkas' => 13, 'uuid' => 'brk-0013', 'jenis_berkas_id' => null, 'nama_file' => 'sk-pembentukan-mekar-jaya.pdf', 'nama_asli' => 'sk-pembentukan-mekar-jaya.pdf', 'path' => 'poktan/1/sk-pembentukan-mekar-jaya.pdf', 'mime' => 'application/pdf', 'ekstensi' => 'pdf', 'ukuran' => 221000, 'disk' => 'local', 'keterangan' => 'SK pembentukan', 'user_id' => 1],
            ['id_berkas' => 14, 'uuid' => 'brk-0014', 'jenis_berkas_id' => null, 'nama_file' => 'bast-tanam-jagung-nov-2025.pdf', 'nama_asli' => 'bast-tanam-jagung-nov-2025.pdf', 'path' => 'penanaman/1/bast-tanam-jagung-nov-2025.pdf', 'mime' => 'application/pdf', 'ekstensi' => 'pdf', 'ukuran' => 193000, 'disk' => 'local', 'keterangan' => 'Berita acara tanam', 'user_id' => 1],
            ['id_berkas' => 15, 'uuid' => 'brk-0015', 'jenis_berkas_id' => null, 'nama_file' => 'foto-traktor-roda-dua.jpg', 'nama_asli' => 'foto-traktor-roda-dua.jpg', 'path' => 'alsintan-distribusi/1/foto-traktor-roda-dua.jpg', 'mime' => 'image/jpeg', 'ekstensi' => 'jpg', 'ukuran' => 604000, 'disk' => 'local', 'keterangan' => 'Kondisi unit di poktan', 'user_id' => 1],
            ['id_berkas' => 16, 'uuid' => 'brk-0016', 'jenis_berkas_id' => null, 'nama_file' => 'foto-benih-jagung.jpg', 'nama_asli' => 'foto-benih-jagung.jpg', 'path' => 'saprotan/1/foto-benih-jagung.jpg', 'mime' => 'image/jpeg', 'ekstensi' => 'jpg', 'ukuran' => 498000, 'disk' => 'local', 'keterangan' => 'Foto barang', 'user_id' => 1],
            ['id_berkas' => 17, 'uuid' => 'brk-0017', 'jenis_berkas_id' => null, 'nama_file' => 'bast-benih-jagung.pdf', 'nama_asli' => 'bast-benih-jagung.pdf', 'path' => 'saprotan/1/bast-benih-jagung.pdf', 'mime' => 'application/pdf', 'ekstensi' => 'pdf', 'ukuran' => 176000, 'disk' => 'local', 'keterangan' => 'Berita acara penyaluran', 'user_id' => 1],
            ['id_berkas' => 18, 'uuid' => 'brk-0018', 'jenis_berkas_id' => null, 'nama_file' => 'BeritaAcaraPeninjauan_pgd-2026-0001.pdf', 'nama_asli' => 'BeritaAcaraPeninjauan_pgd-2026-0001.pdf', 'path' => 'pengaduan/1/BeritaAcaraPeninjauan_pgd-2026-0001.pdf', 'mime' => 'application/pdf', 'ekstensi' => 'pdf', 'ukuran' => 156000, 'disk' => 'local', 'keterangan' => 'Dokumen tindak lanjut', 'user_id' => 1],
            ['id_berkas' => 19, 'uuid' => 'brk-0019', 'jenis_berkas_id' => null, 'nama_file' => 'HasilPemeriksaanHama_pgd-2026-0005.pdf', 'nama_asli' => 'HasilPemeriksaanHama_pgd-2026-0005.pdf', 'path' => 'pengaduan/5/HasilPemeriksaanHama_pgd-2026-0005.pdf', 'mime' => 'application/pdf', 'ekstensi' => 'pdf', 'ukuran' => 143000, 'disk' => 'local', 'keterangan' => 'Dokumen tindak lanjut', 'user_id' => 1],
            ['id_berkas' => 20, 'uuid' => 'brk-0020', 'jenis_berkas_id' => null, 'nama_file' => 'BeritaAcaraPenyelesaian_pgd-2026-0005.pdf', 'nama_asli' => 'BeritaAcaraPenyelesaian_pgd-2026-0005.pdf', 'path' => 'pengaduan/5/BeritaAcaraPenyelesaian_pgd-2026-0005.pdf', 'mime' => 'application/pdf', 'ekstensi' => 'pdf', 'ukuran' => 171000, 'disk' => 'local', 'keterangan' => 'Dokumen tindak lanjut', 'user_id' => 1],
            ['id_berkas' => 21, 'uuid' => 'brk-0021', 'jenis_berkas_id' => null, 'nama_file' => 'bast-sekolah-dasar.pdf', 'nama_asli' => 'bast-sekolah-dasar.pdf', 'path' => 'fasilitas/3/bast-sekolah-dasar.pdf', 'mime' => 'application/pdf', 'ekstensi' => 'pdf', 'ukuran' => 208000, 'disk' => 'local', 'keterangan' => 'Berita acara serah terima', 'user_id' => 1],
            ['id_berkas' => 23, 'uuid' => 'brk-0023', 'jenis_berkas_id' => null, 'nama_file' => 'kondisi-sekolah-dasar-tniumanu.jpg', 'nama_asli' => 'kondisi-sekolah-dasar-tniumanu.jpg', 'path' => 'fasilitas/3/kondisi-sekolah-dasar-tniumanu.jpg', 'mime' => 'image/jpeg', 'ekstensi' => 'jpg', 'ukuran' => 655000, 'disk' => 'local', 'keterangan' => 'Foto kondisi bangunan', 'user_id' => 1],
            ['id_berkas' => 24, 'uuid' => 'brk-0024', 'jenis_berkas_id' => null, 'nama_file' => 'SHM-MLK-2021-0871.pdf', 'nama_asli' => 'SHM-MLK-2021-0871.pdf', 'path' => 'transmigran/2/SHM-MLK-2021-0871.pdf', 'mime' => 'application/pdf', 'ekstensi' => 'pdf', 'ukuran' => 331000, 'disk' => 'local', 'keterangan' => 'Sertifikat hak milik; meliputi pekarangan dan lahan usaha', 'user_id' => 1],
            ['id_berkas' => 25, 'uuid' => 'brk-0025', 'jenis_berkas_id' => null, 'nama_file' => 'HPL-NTT-2016-0142.pdf', 'nama_asli' => 'HPL-NTT-2016-0142.pdf', 'path' => 'kawasan/1/HPL-NTT-2016-0142.pdf', 'mime' => 'application/pdf', 'ekstensi' => 'pdf', 'ukuran' => 587000, 'disk' => 'local', 'keterangan' => 'Hak Pengelolaan atas tanah kawasan; milik instansi, bukan per bidang', 'user_id' => 1],
            ['id_berkas' => 26, 'uuid' => 'brk-0026', 'jenis_berkas_id' => null, 'nama_file' => 'peta-kawasan-kobalima-timur.pdf', 'nama_asli' => 'peta-kawasan-kobalima-timur.pdf', 'path' => 'kawasan/1/peta-kawasan-kobalima-timur.pdf', 'mime' => 'application/pdf', 'ekstensi' => 'pdf', 'ukuran' => 1450000, 'disk' => 'local', 'keterangan' => 'Peta cakupan kawasan', 'user_id' => 1],
            ['id_berkas' => 22, 'uuid' => 'brk-0022', 'jenis_berkas_id' => null, 'nama_file' => 'berita-acara-traktor.pdf', 'nama_asli' => 'berita-acara-traktor.pdf', 'path' => 'alsintan/1/berita-acara-traktor.pdf', 'mime' => 'application/pdf', 'ekstensi' => 'pdf', 'ukuran' => 189000, 'disk' => 'local', 'keterangan' => 'Berita acara pengadaan', 'user_id' => 1],
            // Berkas tambahan (Putaran 14) agar keadaan MULTI benar-benar terlihat
            // di layar, bukan hanya mungkin secara struktur.
            ['id_berkas' => 27, 'uuid' => 'brk-0027', 'jenis_berkas_id' => null, 'nama_file' => 'kondisi-irigasi-blok-a-hilir.jpg', 'nama_asli' => 'kondisi-irigasi-blok-a-hilir.jpg', 'path' => 'infrastruktur/1/kondisi-irigasi-blok-a-hilir.jpg', 'mime' => 'image/jpeg', 'ekstensi' => 'jpg', 'ukuran' => 702000, 'disk' => 'local', 'keterangan' => 'Titik kedua: bagian hilir tertimbun longsor', 'user_id' => 1],
            ['id_berkas' => 28, 'uuid' => 'brk-0028', 'jenis_berkas_id' => null, 'nama_file' => 'kondisi-irigasi-blok-a-pintu-air.jpg', 'nama_asli' => 'kondisi-irigasi-blok-a-pintu-air.jpg', 'path' => 'infrastruktur/1/kondisi-irigasi-blok-a-pintu-air.jpg', 'mime' => 'image/jpeg', 'ekstensi' => 'jpg', 'ukuran' => 664000, 'disk' => 'local', 'keterangan' => 'Titik ketiga: pintu air macet', 'user_id' => 1],
            ['id_berkas' => 29, 'uuid' => 'brk-0029', 'jenis_berkas_id' => null, 'nama_file' => 'foto-saluran-tersumbat-dekat.jpg', 'nama_asli' => 'foto-saluran-tersumbat-dekat.jpg', 'path' => 'pengaduan/1/foto-saluran-tersumbat-dekat.jpg', 'mime' => 'image/jpeg', 'ekstensi' => 'jpg', 'ukuran' => 813000, 'disk' => 'local', 'keterangan' => 'Foto dekat sumbatan', 'user_id' => null],
            ['id_berkas' => 30, 'uuid' => 'brk-0030', 'jenis_berkas_id' => null, 'nama_file' => 'KartuTandaPenduduk_maria-da-costa.jpg', 'nama_asli' => 'KartuTandaPenduduk_maria-da-costa.jpg', 'path' => 'transmigran/2/KartuTandaPenduduk_maria-da-costa.jpg', 'mime' => 'image/jpeg', 'ekstensi' => 'jpg', 'ukuran' => 388000, 'disk' => 'local', 'keterangan' => 'KTP kepala keluarga', 'user_id' => 1],
            ['id_berkas' => 31, 'uuid' => 'brk-0031', 'jenis_berkas_id' => null, 'nama_file' => 'KartuKeluarga_maria-da-costa.pdf', 'nama_asli' => 'KartuKeluarga_maria-da-costa.pdf', 'path' => 'transmigran/2/KartuKeluarga_maria-da-costa.pdf', 'mime' => 'application/pdf', 'ekstensi' => 'pdf', 'ukuran' => 246000, 'disk' => 'local', 'keterangan' => 'Kartu keluarga', 'user_id' => 1],
            ['id_berkas' => 32, 'uuid' => 'brk-0032', 'jenis_berkas_id' => null, 'nama_file' => 'SkPenempatan_maria-da-costa.pdf', 'nama_asli' => 'SkPenempatan_maria-da-costa.pdf', 'path' => 'transmigran/2/SkPenempatan_maria-da-costa.pdf', 'mime' => 'application/pdf', 'ekstensi' => 'pdf', 'ukuran' => 275000, 'disk' => 'local', 'keterangan' => 'SK penempatan transmigran', 'user_id' => 1],
            // Putaran 14 lanjutan: berkas jamak bagi domain yang bentuk jamaknya
            // memang nyata di lapangan (beberapa sudut/sisi atas satu objek).
            ['id_berkas' => 33, 'uuid' => 'brk-0033', 'jenis_berkas_id' => null, 'nama_file' => 'kondisi-kursi-plastik-sandaran.jpg', 'nama_asli' => 'kondisi-kursi-plastik-sandaran.jpg', 'path' => 'inventaris/2/kondisi-kursi-plastik-sandaran.jpg', 'mime' => 'image/jpeg', 'ekstensi' => 'jpg', 'ukuran' => 421000, 'disk' => 'local', 'keterangan' => 'Sandaran retak', 'user_id' => 1],
            ['id_berkas' => 34, 'uuid' => 'brk-0034', 'jenis_berkas_id' => null, 'nama_file' => 'kondisi-sekolah-dasar-atap.jpg', 'nama_asli' => 'kondisi-sekolah-dasar-atap.jpg', 'path' => 'fasilitas/3/kondisi-sekolah-dasar-atap.jpg', 'mime' => 'image/jpeg', 'ekstensi' => 'jpg', 'ukuran' => 738000, 'disk' => 'local', 'keterangan' => 'Atap ruang kelas bocor', 'user_id' => 1],
            ['id_berkas' => 35, 'uuid' => 'brk-0035', 'jenis_berkas_id' => null, 'nama_file' => 'foto-rumah-a-01-depan.jpg', 'nama_asli' => 'foto-rumah-a-01-depan.jpg', 'path' => 'rumah/1/foto-rumah-a-01-depan.jpg', 'mime' => 'image/jpeg', 'ekstensi' => 'jpg', 'ukuran' => 612000, 'disk' => 'local', 'keterangan' => 'Tampak depan', 'user_id' => 1],
            ['id_berkas' => 36, 'uuid' => 'brk-0036', 'jenis_berkas_id' => null, 'nama_file' => 'foto-rumah-a-01-dinding-retak.jpg', 'nama_asli' => 'foto-rumah-a-01-dinding-retak.jpg', 'path' => 'rumah/1/foto-rumah-a-01-dinding-retak.jpg', 'mime' => 'image/jpeg', 'ekstensi' => 'jpg', 'ukuran' => 559000, 'disk' => 'local', 'keterangan' => 'Dinding belakang retak', 'user_id' => 1],
            // Putaran 15: foto barang pada alsintan INDUK, sejajar saprotan
            // (keputusan 11 Putaran 12). Berbeda dari foto per baris distribusi
            // yang merekam kondisi unit di tiap poktan, foto ini merekam wujud
            // batch pengadaan saat diterima.
            ['id_berkas' => 37, 'uuid' => 'brk-0037', 'jenis_berkas_id' => null, 'nama_file' => 'foto-traktor-roda-dua-pengadaan.jpg', 'nama_asli' => 'foto-traktor-roda-dua-pengadaan.jpg', 'path' => 'alsintan/1/foto-traktor-roda-dua-pengadaan.jpg', 'mime' => 'image/jpeg', 'ekstensi' => 'jpg', 'ukuran' => 571000, 'disk' => 'local', 'keterangan' => 'Wujud unit saat batch diterima', 'user_id' => 1],
        ];
    }

    /**
     * Tautan berkas ke pemiliknya, menggantikan kolom path pada tabel domain.
     *
     * Berbentuk pivot per domain, BUKAN polymorphic. Dua alasannya menentukan:
     * foreign key tetap dapat ditegakkan basis data di kedua arah, dan
     * penyaring cakupan data tetap tunggal sebab tiap pivot punya induk yang
     * tetap (agents/rules.md 5.0b-1 poin 8). Tabel polymorphic mencabut
     * keduanya sekaligus.
     *
     * `peran` menggantikan nama kolom lama: `foto` dan `dokumen_pendukung`
     * yang dahulu dua kolom kini dua baris berperan berbeda.
     *
     * @return array<string, array<int, array<string, mixed>>> Peta pivot ke barisnya
     */
    public static function berkasPemilik(): array
    {
        return [
            'kawasan_transmigrasi_berkas' => [
                ['kawasan_transmigrasi_id' => 1, 'berkas_id' => 1, 'peran' => 'sk', 'urutan' => 0],
                // HPL adalah alas hak KAWASAN, bukan dokumen per bidang lahan
                // (rules.md 7.4a). Sebelum Putaran 12 ia salah tempat pada
                // dokumen_lahan, dan dari situ lahir pivot m2m yang menambal akibatnya.
                ['kawasan_transmigrasi_id' => 1, 'berkas_id' => 25, 'peran' => 'hpl', 'urutan' => 1],
                ['kawasan_transmigrasi_id' => 1, 'berkas_id' => 26, 'peran' => 'peta', 'urutan' => 2],
            ],
            'transmigran_berkas' => [
                // SHM meliputi SELURUH lahan satu KK, pekarangan maupun usaha,
                // sehingga melekat pada keluarganya dan diunggah sekali saja.
                ['transmigran_id' => 2, 'berkas_id' => 24, 'peran' => 'shm', 'urutan' => 0],
                // KTP, KK, dan SK penempatan adalah TIGA dokumen berbeda. Sebelum
                // Putaran 12 ketiganya dipaksa ke satu kolom dokumen_pendukung,
                // sehingga hanya satu yang benar-benar dapat disimpan.
                ['transmigran_id' => 2, 'berkas_id' => 30, 'peran' => 'ktp', 'urutan' => 1],
                ['transmigran_id' => 2, 'berkas_id' => 31, 'peran' => 'kk', 'urutan' => 2],
                ['transmigran_id' => 2, 'berkas_id' => 32, 'peran' => 'sk', 'urutan' => 3],
            ],
            'satuan_permukiman_berkas' => [
                ['satuan_permukiman_id' => 1, 'berkas_id' => 2, 'peran' => 'sk', 'urutan' => 0],
                ['satuan_permukiman_id' => 2, 'berkas_id' => 3, 'peran' => 'sk', 'urutan' => 0],
            ],
            'hasil_panen_berkas' => [
                ['hasil_panen_id' => 1, 'berkas_id' => 4, 'peran' => 'pendukung', 'urutan' => 0],
                ['hasil_panen_id' => 3, 'berkas_id' => 5, 'peran' => 'pendukung', 'urutan' => 0],
            ],
            'pengaduan_berkas' => [
                ['pengaduan_id' => 1, 'berkas_id' => 6, 'peran' => 'bukti', 'urutan' => 0],
                ['pengaduan_id' => 1, 'berkas_id' => 29, 'peran' => 'bukti', 'urutan' => 1],
                ['pengaduan_id' => 5, 'berkas_id' => 7, 'peran' => 'bukti', 'urutan' => 0],
            ],
            'infrastruktur_berkas' => [
                // Satu irigasi 1,2 km punya beberapa titik kerusakan, dan satu
                // foto tidak sanggup menunjukkan seluruhnya.
                ['infrastruktur_id' => 1, 'berkas_id' => 8, 'peran' => 'foto', 'urutan' => 0],
                ['infrastruktur_id' => 1, 'berkas_id' => 27, 'peran' => 'foto', 'urutan' => 1],
                ['infrastruktur_id' => 1, 'berkas_id' => 28, 'peran' => 'foto', 'urutan' => 2],
                ['infrastruktur_id' => 1, 'berkas_id' => 9, 'peran' => 'pendukung', 'urutan' => 3],
            ],
            // Pivot ini ada di schema.sql sejak Putaran 12 tetapi belum pernah
            // punya data contoh, sehingga bentuk jamaknya tidak teruji di layar.
            'rumah_berkas' => [
                ['rumah_id' => 1, 'berkas_id' => 35, 'peran' => 'foto', 'urutan' => 0],
                ['rumah_id' => 1, 'berkas_id' => 36, 'peran' => 'foto', 'urutan' => 1],
            ],
            'inventaris_sp_berkas' => [
                ['inventaris_sp_id' => 2, 'berkas_id' => 10, 'peran' => 'foto', 'urutan' => 0],
                ['inventaris_sp_id' => 2, 'berkas_id' => 33, 'peran' => 'foto', 'urutan' => 1],
                ['inventaris_sp_id' => 2, 'berkas_id' => 11, 'peran' => 'pendukung', 'urutan' => 2],
            ],
            'fasilitas_sp_berkas' => [
                ['fasilitas_sp_id' => 1, 'berkas_id' => 12, 'peran' => 'foto', 'urutan' => 0],
                ['fasilitas_sp_id' => 3, 'berkas_id' => 23, 'peran' => 'foto', 'urutan' => 0],
                ['fasilitas_sp_id' => 3, 'berkas_id' => 34, 'peran' => 'foto', 'urutan' => 1],
                ['fasilitas_sp_id' => 3, 'berkas_id' => 21, 'peran' => 'pendukung', 'urutan' => 2],
            ],
            'penanaman_berkas' => [
                ['penanaman_id' => 1, 'berkas_id' => 14, 'peran' => 'pendukung', 'urutan' => 0],
            ],
            'penanganan_pengaduan_berkas' => [
                ['penanganan_pengaduan_id' => 1, 'berkas_id' => 18, 'peran' => 'tindak_lanjut', 'urutan' => 0],
                ['penanganan_pengaduan_id' => 5, 'berkas_id' => 19, 'peran' => 'tindak_lanjut', 'urutan' => 0],
                ['penanganan_pengaduan_id' => 6, 'berkas_id' => 20, 'peran' => 'tindak_lanjut', 'urutan' => 1],
            ],
            'alsintan_berkas' => [
                ['alsintan_id' => 1, 'berkas_id' => 37, 'peran' => 'foto', 'urutan' => 0],
                ['alsintan_id' => 1, 'berkas_id' => 22, 'peran' => 'pendukung', 'urutan' => 1],
            ],
        ];
    }

    /**
     * Menempelkan nama berkas dari registry ke baris domain.
     *
     * Selama tahap frontend, view membaca berkas lewat kunci lamanya
     * (`foto`, `dokumen_pendukung`, dan sejenisnya). Nilainya kini TIDAK
     * lagi disimpan pada baris domain melainkan pada registry `berkas()`,
     * sehingga tanpa penempelan ini setiap tautan berkas menjadi kosong.
     *
     * Penempelan dilakukan di sini, bukan di view, sebab view dilarang
     * mengambil datanya sendiri. Bentuknya sengaja mempertahankan kunci lama
     * agar perpindahan ke registry tidak menuntut 25 view disunting
     * serentak; yang berubah hanya ASAL nilainya.
     *
     * `*_berkas_meta` membawa metadata penuh (mime, ukuran, peran) bagi
     * pemakai yang memerlukannya, misalnya penanda jenis berkas.
     *
     * @param  array<int, array<string, mixed>>  $baris  Baris domain
     * @param  string  $pivot  Nama pivot pada berkasPemilik()
     * @param  string  $kunciInduk  Nama kolom induk pada pivot
     * @param  string  $kunciId  Nama kolom id pada baris domain
     * @param  array<string, string>  $peta  Peta peran ke nama kunci lama
     * @return array<int, array<string, mixed>> Baris beserta berkasnya
     */
    private static function lekatkanBerkas(array $baris, string $pivot, string $kunciInduk, string $kunciId, array $peta): array
    {
        return array_map(function (array $b) use ($pivot, $kunciInduk, $kunciId, $peta) {
            foreach ($peta as $peran => $kunciLama) {
                $berkas = self::berkasSatu($pivot, $kunciInduk, (int) $b[$kunciId], $peran);

                $b[$kunciLama] = $berkas['nama_file'] ?? null;
                $b[$kunciLama.'_meta'] = $berkas;
            }

            return $b;
        }, $baris);
    }

    /**
     * Berkas milik satu baris domain, terurut.
     *
     * Dipakai view menggantikan pembacaan kolom path langsung. Mengembalikan
     * larik, sebab sebelas domain kini boleh memegang lebih dari satu berkas;
     * domain yang hanya memegang satu tetap memakai bentuk yang sama agar
     * pemanggilnya tidak perlu tahu bedanya.
     *
     * @param  string  $pivot  Nama pivot, contoh `rumah_berkas`
     * @param  string  $kunci  Nama kolom induk, contoh `rumah_id`
     * @param  int  $id  Nilai id induk
     * @param  string|null  $peran  Menyaring satu peran; null berarti seluruhnya
     * @return array<int, array<string, mixed>> Berkas beserta metadatanya
     */
    public static function berkasMilik(string $pivot, string $kunci, int $id, ?string $peran = null): array
    {
        $baris = self::berkasPemilik()[$pivot] ?? [];
        $peta = array_column(self::berkas(), null, 'id_berkas');

        $hasil = [];

        foreach ($baris as $b) {
            if (($b[$kunci] ?? null) !== $id) {
                continue;
            }

            if ($peran !== null && $b['peran'] !== $peran) {
                continue;
            }

            if (isset($peta[$b['berkas_id']])) {
                $hasil[] = $peta[$b['berkas_id']] + ['peran' => $b['peran'], 'urutan' => $b['urutan']];
            }
        }

        usort($hasil, fn ($a, $b) => $a['urutan'] <=> $b['urutan']);

        return $hasil;
    }

    /**
     * Satu berkas milik domain, atau null bila tidak ada.
     *
     * Peredam bagi pemanggil yang memang hanya menampilkan satu berkas,
     * misalnya foto utama pada halaman daftar.
     *
     * @return array<string, mixed>|null Berkas pertama menurut urutan
     */
    public static function berkasSatu(string $pivot, string $kunci, int $id, ?string $peran = null): ?array
    {
        return self::berkasMilik($pivot, $kunci, $id, $peran)[0] ?? null;
    }

    /**
     * Metadata satu berkas menurut idnya.
     *
     * Dipakai FK langsung pada domain berkas tunggal, yang menyimpan
     * `berkas_id` alih-alih menempuh pivot.
     *
     * @return array<string, mixed>|null Berkas, atau null bila id tak dikenal
     */
    public static function cariBerkas(?int $id): ?array
    {
        return $id === null ? null : (array_column(self::berkas(), null, 'id_berkas')[$id] ?? null);
    }

    /**
     * Daftar rumah beserta penghuninya.
     *
     * Satu rumah dihuni tepat satu KK, dan rumah kosong ditandai
     * `transmigran_id` bernilai null (agents/rules.md bagian 6a.5 dan 6a.7).
     *
     * `transmigran_id` ADALAH KEBENARANNYA, `penghuni` hanya label tampilan.
     * Ditambahkan 2026-09-02; sebelumnya penautan hanya berupa nama, dan
     * tabel ini menjadi satu-satunya yang menaut lewat teks sementara
     * `lahan`, `penanaman`, dan `riwayat_penghunian` sudah memakai id.
     *
     * Bukan sekadar kerapian. Suksesi kepala keluarga MENYUNTING baris
     * `transmigran` yang sama (agents/rules.md bagian 6.5), sehingga
     * `nama_kepala_keluarga` berubah. Penautan lewat nama akan putus pada
     * saat itu juga, tanpa satu pun pesan galat: rumahnya tetap tampil,
     * penghuninya hilang, dan tidak ada yang menegur. Dua KK bernama sama
     * juga cukup untuk menautkan rumah kepada keluarga yang keliru.
     *
     * Skema sudah lebih dulu benar: `rumah.transmigran_id` nullable ber-UNIQUE
     * (`database/data/schema.sql`, `uq_rumah_transmigran`), sesuai kewajiban
     * agents/rules.md bagian 6a.6 bahwa relasi satu-ke-satu dijaga basis data,
     * bukan hanya validasi form.
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
                'transmigran_id' => 1,
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
                'transmigran_id' => 2,
                'penghuni' => 'MARIA DA COSTA',
                'kondisi' => KondisiRumah::RusakRingan->value,
                'status_hunian' => StatusHunian::Dihuni->value,
                'tahun_pembangunan' => 2016,
                'luas_bangunan' => 36.00,
                'lintang' => -9.5125300,
                'bujur' => 124.9125100,
            ],
            [
                // Pernah dihuni DOMINGGUS TAEK (transmigran 5) sampai 2025-09-30.
                // Kolom ini menyimpan penghuni SEKARANG, sehingga bernilai null;
                // kepergiannya tetap terbaca pada riwayatPenghunian() baris 3.
                'id_rumah' => 3,
                'no_rumah' => 'A-03',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'transmigran_id' => null,
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
                'transmigran_id' => 3,
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
                'transmigran_id' => 4,
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
                'transmigran_id' => null,
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
     * Riwayat pergantian kedudukan kepala keluarga.
     *
     * Satu baris `transmigran` adalah satu RUMAH TANGGA, bukan satu orang.
     * Ketika kepala keluarganya meninggal atau merantau, barisnya disunting dan
     * ketujuh relasi yang menautinya tetap utuh: jatah rumah dan lahan
     * diberikan kepada KK, bukan kepada suaminya secara pribadi.
     *
     * Peristiwanya direkam di sini, BUKAN cukup pada `audit_log`. Audit log
     * memang mencatat bahwa `nama_kepala_keluarga` berubah, tetapi ia tidak
     * dapat membedakan suksesi dari perbaikan salah ketik: keduanya berbentuk
     * aksi `Ubah` pada kolom yang sama. Data contoh audit log sendiri sudah
     * memuat contoh yang kedua (agents/rules.md bagian 6 poin 5a).
     *
     * Kedua sisi identitas disimpan agar riwayat dapat dibaca berdiri sendiri
     * tanpa merangkainya dari baris berikutnya.
     *
     * @param  int|null  $transmigranId  Menyaring riwayat satu keluarga; null berarti seluruhnya
     * @return array<int, array<string, mixed>> Riwayat suksesi, terbaru di atas
     */
    public static function riwayatKepalaKeluarga(?int $transmigranId = null): array
    {
        $data = [
            // Kasus paling lazim: suami meninggal, istri menggantikan. Nomor
            // KK ikut berubah sebab Dukcapil menerbitkan KK baru.
            [
                'id_riwayat_kepala_keluarga' => 1,
                'transmigran_id' => 6,
                'nik_lama' => '5321010512830106',
                'nama_lama' => 'YAKOBUS BRIA',
                'nik_baru' => '5321012511870006',
                'nama_baru' => 'FRANSISKA BRIA',
                'no_kk_lama' => '5321010102160006',
                'no_kk_baru' => '5321010102180006',
                'tanggal_pergantian' => '2024-08-22',
                'alasan' => AlasanPergantianKK::Meninggal->value,
                'hubungan_pengganti' => HubunganAnggotaKeluarga::Istri->value,
                'keterangan' => 'Akta kematian dan KK baru sudah diserahkan ke kantor SP.',
            ],
            // Kepala keluarga merantau, keluarganya tetap tinggal. Nomor KK
            // TIDAK berubah, dan keadaan itu memang sah: keduanya diisi sama.
            // Sengaja ada agar tampilan tidak mengandaikan nomor KK selalu
            // berganti setiap kali kepala keluarganya berganti.
            [
                'id_riwayat_kepala_keluarga' => 2,
                'transmigran_id' => 4,
                'nik_lama' => '5321010703860104',
                'nama_lama' => 'LUKAS SERAN',
                'nik_baru' => '5321011712900004',
                'nama_baru' => 'ANGELA SERAN',
                'no_kk_lama' => '5321010102170004',
                'no_kk_baru' => '5321010102170004',
                'tanggal_pergantian' => '2025-02-10',
                'alasan' => AlasanPergantianKK::PindahAtauMerantau->value,
                'hubungan_pengganti' => HubunganAnggotaKeluarga::Istri->value,
                'keterangan' => 'Bekerja di Kupang sejak awal 2025, keluarga tetap menggarap lahan.',
            ],
        ];

        // Terbaru di atas, sama seperti riwayat penghunian dan catatan log.
        usort($data, fn ($a, $b) => strcmp($b['tanggal_pergantian'], $a['tanggal_pergantian']));

        if ($transmigranId === null) {
            return $data;
        }

        return array_values(array_filter($data, fn ($b) => $b['transmigran_id'] === $transmigranId));
    }

    /**
     * Poktan yang diketuai sebuah keluarga lewat jalur kepala keluarga.
     *
     * Dipakai saat suksesi: jabatan ketua TIDAK diwariskan, sehingga petugas
     * wajib memutuskan apakah jabatan itu dikosongkan atau diteruskan kepada
     * kepala keluarga baru (agents/rules.md bagian 6 poin 5e). Membiarkannya
     * berpindah sendiri berarti sistem mengangkat ketua tanpa seorang pun
     * memutuskan, padahal ketua dipilih anggota.
     *
     * Hanya jalur `Kepala Keluarga` yang terpengaruh. Ketua yang berupa anggota
     * keluarga punya nama dan NIK tersendiri, sehingga tidak ikut berubah
     * ketika kepala keluarganya berganti.
     *
     * @param  int  $transmigranId  Keluarga yang diperiksa
     * @return array<int, array<string, mixed>> Poktan yang diketuainya
     */
    public static function poktanDiketuaiKeluarga(int $transmigranId): array
    {
        return array_values(array_filter(
            self::poktan(),
            fn ($p) => $p['ketua_transmigran_id'] === $transmigranId
                && $p['asal_ketua'] === AsalWakilPoktan::KepalaKeluarga->value
        ));
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
        return array_values(array_filter(self::rumah(), fn ($r) => $r['transmigran_id'] === null));
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
        $sudahPunya = array_filter(array_column(self::rumah(), 'transmigran_id'));

        return array_values(array_filter(
            self::transmigran(),
            fn ($t) => ! in_array($t['id_transmigran'], $sudahPunya, true)
        ));
    }

    /**
     * Daftar transmigran yang belum memiliki baris lahan.
     *
     * Sejak Putaran 15 satu keluarga tepat satu baris lahan, ditegakkan
     * `UNIQUE (transmigran_id)`. Alur "Tambah Lahan" karena itu hanya berlaku
     * bagi KK yang belum punya baris; KK yang sudah punya disunting lewat alur
     * Ubah. Menawarkan Tambah untuk KK yang sudah terdata akan selalu ditolak
     * UNIQUE, dan kegagalan itu tidak menjelaskan apa pun kepada petugas.
     *
     * @return array<int, array<string, mixed>> Transmigran tanpa baris lahan
     */
    public static function transmigranTanpaLahan(): array
    {
        $sudahPunya = array_filter(array_column(self::lahan(), 'transmigran_id'));

        return array_values(array_filter(
            self::transmigran(),
            fn ($t) => ! in_array($t['id_transmigran'], $sudahPunya, true)
        ));
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
     * Menyaring baris menurut rentang tahun (dari sampai).
     *
     * Ditambahkan 2026-08-28 untuk komponen `x-sim.filter-rentang-tahun`.
     * Dipakai bersama oleh rute `/panen`, `/penanaman`, dan `/audit-log` agar
     * ketiganya menyaring dengan cara yang sama, termasuk saat batasnya
     * kosong atau terbalik.
     *
     * HANYA untuk daftar transaksi, tempat tiap baris berdiri sendiri.
     * Dilarang dipakai menyaring rekap agregat lintas tahun (rules.md 9
     * poin 8b): luas tertanam akan terhitung ganda.
     *
     * - Batas kosong berarti terbuka pada sisi itu.
     * - Batas terbalik (`dari` > `sampai`) ditukar, bukan menghasilkan daftar
     *   kosong tanpa penjelasan.
     * - Baris tanpa tahun yang terbaca ikut tersaring keluar begitu salah
     *   satu batas dipasang.
     *
     * @param  array<int, array<string, mixed>>  $baris
     * @param  int|string|null  $dari
     * @param  int|string|null  $sampai
     * @param  callable  $ambilTahun  Menerima satu baris, mengembalikan int|null
     * @return array<int, array<string, mixed>>
     */
    public static function saringRentangTahun(array $baris, $dari, $sampai, callable $ambilTahun): array
    {
        $dari = ($dari === null || $dari === '') ? null : (int) $dari;
        $sampai = ($sampai === null || $sampai === '') ? null : (int) $sampai;

        if ($dari !== null && $sampai !== null && $dari > $sampai) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        if ($dari === null && $sampai === null) {
            return array_values($baris);
        }

        return array_values(array_filter($baris, function ($satu) use ($dari, $sampai, $ambilTahun) {
            $tahun = $ambilTahun($satu);

            if ($tahun === null) {
                return false;
            }

            if ($dari !== null && $tahun < $dari) {
                return false;
            }

            if ($sampai !== null && $tahun > $sampai) {
                return false;
            }

            return true;
        }));
    }

    /**
     * Daftar lahan milik transmigran, SATU BARIS PER KELUARGA.
     *
     * DISATUKAN 2026-09-02 (Putaran 15). Sebelumnya satu baris adalah satu
     * BIDANG, sehingga keluarga dengan pekarangan dan lahan usaha menempati dua
     * baris. Jumlahnya memang tetap - tepat satu pekarangan dan satu lahan usaha
     * (rules.md 7.8) - sehingga keduanya kini menjadi kolom, bukan baris.
     *
     * KOORDINAT TETAP DUA PASANG. Pekarangan dan lahan usaha berada di tempat
     * yang berbeda, sehingga menyatukannya menjadi satu titik berarti membuang
     * lokasi yang sudah terdata.
     *
     * KOLOM PEKARANGAN NULL BERARTI BELUM MENERIMA, bukan menerima seluas nol.
     * Dua dari empat keluarga pada data contoh memang hanya memegang lahan
     * usaha, dan keadaan itu sengaja dipertahankan agar tampilan yang
     * membedakan keduanya benar-benar teruji.
     *
     * LUAS KERING DAN BASAH ADALAH KOMPOSISI LAHAN USAHA, BUKAN KATEGORI. Satu
     * bidang dapat digarap sebagian kering dan sebagian basah sekaligus, dan
     * jumlah keduanya wajib sama dengan `luas_usaha` (rules.md 7.5).
     *
     * `transmigran_id` wajib ada pada setiap baris: rekap luas per keluarga
     * membacanya lewat id, bukan mencocokkan nama pemilik. Kolom `pemilik`
     * dipertahankan sebagai teks tampilan agar tabel tidak perlu menelusuri
     * relasi hanya untuk mencetak satu nama.
     *
     * @return array<int, array<string, mixed>> Data lahan
     */
    public static function lahan(): array
    {
        $data = [
            // Pekarangan + lahan usaha seluruhnya kering. Bagian basah ditulis 0,
            // bukan null, agar penjumlahan rekap tidak perlu membedakan nol dari
            // kosong pada keluarga yang memang memegang lahan usaha.
            [
                'id_lahan' => 1,
                'kode_lahan' => 'LH-001',
                'transmigran_id' => 1,
                'pemilik' => 'YOHANES BERE',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'luas_pekarangan' => 0.25,
                'lintang_pekarangan' => -9.5124100,
                'bujur_pekarangan' => 124.9126200,
                'luas_usaha' => 1.50,
                'luas_kering' => 1.50,
                'luas_basah' => 0.00,
                'lintang_usaha' => -9.5138400,
                'bujur_usaha' => 124.9152700,
            ],
            // Bidang usaha campuran, satu-satunya pada data contoh. Sengaja ada
            // agar keadaan yang menjadi seluruh alasan pemecahan kolom kering dan
            // basah benar-benar terlihat saat peninjauan, bukan hanya terbaca di
            // dokumen. Uji integritas memeriksa jumlahnya sama dengan luas usaha.
            [
                'id_lahan' => 2,
                'kode_lahan' => 'LH-002',
                'transmigran_id' => 2,
                'pemilik' => 'MARIA DA COSTA',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'luas_pekarangan' => 0.25,
                'lintang_pekarangan' => -9.5483200,
                'bujur_pekarangan' => 124.8891000,
                'luas_usaha' => 2.00,
                'luas_kering' => 1.25,
                'luas_basah' => 0.75,
                'lintang_usaha' => -9.4982600,
                'bujur_usaha' => 124.9411800,
            ],
            // BELUM MENERIMA PEKARANGAN. Kolom pekarangan null, bukan nol.
            [
                'id_lahan' => 3,
                'kode_lahan' => 'LH-003',
                'transmigran_id' => 3,
                'pemilik' => 'PETRUS NAHAK',
                'satuan_permukiman' => 'SP Tniumanu',
                'satuan_permukiman_id' => 2,
                'luas_pekarangan' => null,
                'lintang_pekarangan' => null,
                'bujur_pekarangan' => null,
                'luas_usaha' => 1.25,
                'luas_kering' => 1.25,
                'luas_basah' => 0.00,
                'lintang_usaha' => -9.4995300,
                'bujur_usaha' => 124.9438100,
            ],
            // Seluruhnya basah, dan juga belum menerima pekarangan.
            [
                'id_lahan' => 4,
                'kode_lahan' => 'LH-004',
                'transmigran_id' => 8,
                'pemilik' => 'YULITA HOAR',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'luas_pekarangan' => null,
                'lintang_pekarangan' => null,
                'bujur_pekarangan' => null,
                'luas_usaha' => 0.75,
                'luas_kering' => 0.00,
                'luas_basah' => 0.75,
                'lintang_usaha' => -9.5471900,
                'bujur_usaha' => 124.8873500,
            ],
        ];

        /*
            SHM dan status sertifikat DITEMPELKAN dari keluarganya (Putaran 15
            lanjutan, 2026-09-03). Keduanya secara hukum milik KELUARGA, bukan
            bidang: SHM meliputi seluruh lahan satu KK dan tersimpan pada
            `transmigran_berkas` peran `shm`, statusnya pada
            `transmigran.status_sertifikat`. Yang berubah hanya permukaan
            entrinya - sejak satu keluarga tepat satu baris lahan, form lahan
            menjadi tempat kanonis mengunggahnya (rules.md 7.6a). Ditempel di
            sini agar detail, form ubah, dan baris daftar membacanya seragam.
        */
        $statusPerKeluarga = array_column(self::transmigran(), 'status_sertifikat', 'id_transmigran');

        return array_map(function (array $l) use ($statusPerKeluarga): array {
            $shm = self::berkasSatu('transmigran_berkas', 'transmigran_id', $l['transmigran_id'], 'shm');

            return $l + [
                'shm' => $shm['nama_file'] ?? null,
                'shm_meta' => $shm,
                'status_sertifikat' => $statusPerKeluarga[$l['transmigran_id']] ?? 'Belum Didata',
            ];
        }, $data);
    }

    /**
     * Daftar hasil panen.
     *
     * Produksi disimpan apa adanya sesuai satuan baku komoditas, konversi ke
     * ton hanya dilakukan saat rekap (agents/rules.md bagian 8a).
     *
     * DIROMBAK 2026-08-22 mengikuti kolom laporan lapangan. Tiga hal berubah:
     *
     * 1. `volume` berganti nama menjadi `produksi`, sejalan dengan istilah
     *    laporan dan agar tidak tertukar dengan `volume_benih` pada penanaman.
     * 2. Ditambahkan `realisasi_panen`, `puso`, dan `produktivitas`. Ketiganya
     *    yang membuat panen bertahap dapat dicatat: satu penanaman dapat
     *    dipanen sebagian, sebagian lagi puso, sisanya menyusul.
     * 3. `kualitas` DICABUT atas keputusan pemilik proyek.
     *
     * DITAMBAHKAN 2026-08-24: `keterangan` dan `dokumen_pendukung`. Keduanya
     * SUDAH tercantum pada kamus data 9.3 dan SUDAH punya isian di form, tetapi
     * tidak pernah ada di sini. Halaman rincian membacanya lewat `?? '-'`,
     * sehingga selalu bertuliskan "-" tanpa pernah memerahkan apa pun: petugas
     * mengetik catatan, menekan simpan, dan catatannya lenyap tanpa pesan.
     *
     * Bukan kolom baru, melainkan janji dokumen yang belum ditepati kode.
     *
     * Dua identitas aritmetika yang WAJIB berlaku, keduanya terbukti pada 96
     * baris laporan Polri MT.II 2025:
     *
     *     realisasi_panen + puso + belum_dipanen = penanaman.realisasi_tanam
     *     produksi                               = realisasi_panen x produktivitas
     *
     * `belum_dipanen` TIDAK disimpan; ia selisih dari identitas pertama.
     * Menyimpannya berarti tiga angka yang saling menentukan disimpan
     * terpisah, dan ketiganya dapat berbeda tanpa ada yang menegur.
     *
     * `produksi` sengaja tetap disimpan meski dapat dihitung dari dua kolom
     * lain: ia angka yang dilaporkan ke dinas, dan pembulatan hasil perkalian
     * dapat berbeda tipis dari angka yang benar-benar ditimbang.
     *
     * CAKUPANNYA BEBERAPA TRANSAKSI CONTOH, bukan seluruh panen kawasan.
     * Isinya sengaja sedikit dan beragam satuan agar tampilan tabel, konversi
     * ton, serta penyaringan dapat diuji; totalnya karena itu jauh lebih kecil
     * daripada `sebaranKomoditas()` yang merupakan agregat kawasan setahun.
     *
     * Perbedaan itu disengaja. Menjumlahkan baris di sini lalu membandingkannya
     * dengan angka dashboard akan selalu tampak timpang, dan bukan itu yang
     * hendak dijawab keduanya.
     *
     * @return array<int, array<string, mixed>> Data hasil panen
     */
    public static function hasilPanen(): array
    {
        $data = [
            // Panen sebagian beserta puso: 1,25 + 0,25 = 1,50 ha yang ditanam.
            // Sengaja ada agar cabang puso ikut terlihat saat peninjauan.
            [
                'id_hasil_panen' => 1,
                'penanaman_id' => 1,
                'poktan' => 'POKTAN MEKAR JAYA',
                'komoditas' => 'JAGUNG',
                'satuan' => 'Ton',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'periode_panen' => '2026-04',
                'realisasi_panen' => 1.25,
                'puso' => 0.25,
                'produktivitas' => 3.400,
                'produksi' => 4.250,
                'harga_jual' => 4500000,
                'keterangan' => 'Sebagian hamparan terendam saat hujan deras awal April.',
            ],
            [
                'id_hasil_panen' => 2,
                'penanaman_id' => 3,
                'poktan' => 'POKTAN MEKAR JAYA',
                'komoditas' => 'JAGUNG',
                'satuan' => 'Ton',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'periode_panen' => '2026-04',
                // DIBETULKAN 2026-08-24. Sebelumnya panen 1,20 ha dari
                // penanaman seluas 2,00 ha, menyisakan 0,80 ha yang tercatat
                // "belum dipanen" sejak November 2025 - sepuluh bulan tanpa
                // pernah ditutup. Jagung tidak berdiri selama itu; angka itu
                // pencatatan yang menggantung, bukan tanaman yang masih hidup.
                //
                // Kini luasnya ditutup penuh: 1,20 ha dipanen, 0,80 ha puso.
                'realisasi_panen' => 1.20,
                'puso' => 0.80,
                'produktivitas' => 2.900,
                'produksi' => 3.480,
                'harga_jual' => 4750000,
                'keterangan' => 'Sebagian hamparan diserang hama tikus menjelang panen.',
                'dokumen_pendukung' => null,
            ],
            [
                'id_hasil_panen' => 3,
                'penanaman_id' => 2,
                'poktan' => 'POKTAN MEKAR JAYA',
                'komoditas' => 'PADI',
                'satuan' => 'Ton',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'periode_panen' => '2026-05',
                'realisasi_panen' => 0.75,
                'puso' => 0.00,
                'produktivitas' => 2.800,
                'produksi' => 2.100,
                'harga_jual' => 5200000,
                'keterangan' => null,
                // Foto, bukan PDF. Sengaja berbeda dari baris pertama agar
                // pelepasan batasan `:hanya-gambar` ikut terlihat pada data.
            ],
            // Satuan kilogram, sehingga produktivitasnya pun kg/ha. Satuan
            // TIDAK dipaksa ton: cabai memang ditimbang kilogram, dan
            // memaksanya membuat harga jual per ton menjadi angka yang tidak
            // pernah dipakai siapa pun di lapangan.
            [
                'id_hasil_panen' => 4,
                'penanaman_id' => 4,
                'poktan' => 'POKTAN TANI BERSATU',
                'komoditas' => 'CABAI',
                'satuan' => 'Kilogram',
                'satuan_permukiman' => 'SP Tniumanu',
                'satuan_permukiman_id' => 2,
                'periode_panen' => '2026-03',
                'realisasi_panen' => 0.25,
                'puso' => 0.05,
                'produktivitas' => 1282.000,
                'produksi' => 320.500,
                'harga_jual' => 28000,
                'keterangan' => 'Dijual ke pengepul setempat, satuan setara 1 karung 25 kg.',
                'dokumen_pendukung' => null,
            ],
            [
                'id_hasil_panen' => 5,
                'penanaman_id' => 5,
                'poktan' => 'POKTAN MEKAR JAYA',
                'komoditas' => 'JAGUNG',
                'satuan' => 'Ton',
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'periode_panen' => '2025-11',
                'realisasi_panen' => 1.30,
                'puso' => 0.20,
                'produktivitas' => 3.000,
                'produksi' => 3.900,
                'harga_jual' => 4300000,
                'keterangan' => null,
                'dokumen_pendukung' => null,
            ],
            // GAGAL TOTAL: seluruh luas tercatat puso, tidak ada yang dipanen.
            //
            // Produktivitas dan produksi bernilai nol, dan itu SAH: tidak ada
            // yang ditimbang, sehingga memaksa angka di sini berarti mengarang
            // hasil yang tidak pernah ada. Harga jual pun kosong.
            //
            // Sengaja ada agar cabang gagal total ikut terlihat saat
            // peninjauan; tanpa baris ini, keadaan itu hanya teori.
            [
                'id_hasil_panen' => 6,
                'penanaman_id' => 7,
                'poktan' => 'POKTAN HARAPAN BARU',
                'komoditas' => 'PADI',
                'satuan' => 'Ton',
                'satuan_permukiman' => 'SP Weain',
                'satuan_permukiman_id' => 6,
                'periode_panen' => '2026-05',
                'realisasi_panen' => 0.00,
                'puso' => 0.50,
                'produktivitas' => 0.000,
                'produksi' => 0.000,
                'harga_jual' => null,
                'keterangan' => 'Hamparan terendam banjir sebelum sempat dipanen.',
                'dokumen_pendukung' => null,
            ],
        ];

        $data = self::lekatkanBerkas($data, 'hasil_panen_berkas', 'hasil_panen_id', 'id_hasil_panen', ['pendukung' => 'dokumen_pendukung']);

        // `poktan_id` DITURUNKAN dari penanamannya (Putaran 7), bukan disimpan
        // pada baris ini. Kolom sebelahnya `satuan_id` memang disalin dari
        // komoditas dengan alasan snapshot historis (data-dictionary.md), tetapi
        // `poktan_id` tak punya alasan itu: `penanaman.poktan_id` tak pernah
        // sah berubah makna, dan salinan yang menggantung diam-diam berselisih.
        $poktanPenanaman = [];
        foreach (self::penanaman() as $t) {
            $poktanPenanaman[$t['id_penanaman']] = $t['poktan_id'];
        }

        return array_map(function (array $h) use ($poktanPenanaman): array {
            return $h + ['poktan_id' => $poktanPenanaman[$h['penanaman_id']] ?? null];
        }, $data);
    }

    /**
     * Apakah sebuah penanaman sudah dipanen.
     *
     * DITURUNKAN dari ada atau tidaknya catatan panen, bukan disimpan sebagai
     * kolom. Menyimpannya berarti nilainya menjadi salah begitu satu baris
     * panen dihapus, dan kesalahan itu tidak pernah memerahkan apa pun.
     *
     * DISEDERHANAKAN 2026-08-24 bersama pencabutan panen bertahap. Sebelumnya
     * memeriksa sisa luas untuk membedakan `Dipanen Sebagian`; keadaan itu
     * kini tidak lagi mungkin ada, sebab satu penanaman hanya boleh memiliki
     * satu panen yang menutup seluruh luasnya.
     *
     * Penanaman yang GAGAL TOTAL tetap berstatus Selesai Dipanen: barisnya
     * ada, hanya seluruh luasnya tercatat sebagai puso. Pembedanya kolom puso,
     * bukan status.
     *
     * @param  int  $penanamanId  Nilai id_penanaman
     * @return StatusPanen Status yang berlaku saat ini
     */
    public static function statusPanen(int $penanamanId): StatusPanen
    {
        $adaPanen = collect(self::hasilPanen())
            ->contains(fn ($p) => ($p['penanaman_id'] ?? null) === $penanamanId);

        return $adaPanen ? StatusPanen::SelesaiDipanen : StatusPanen::BelumDipanen;
    }

    /**
     * Tahun panen yang tercatat, terbesar lebih dulu.
     *
     * BERBASIS TAHUN PANEN, bukan tahun tanam (diubah 2026-08-24). Lihat
     * `rekapPanen()` untuk alasannya.
     *
     * Tahun berjalan selalu ikut, sebab penanaman yang belum dipanen memang
     * digolongkan ke sana - dan tanpa baris ini, tahun itu dapat tidak muncul
     * pada daftar pilihan meski memiliki isi.
     *
     * @return array<int, int> Daftar tahun
     */
    public static function tahunPanenTercatat(): array
    {
        $tahun = [(int) date('Y')];

        foreach (self::hasilPanen() as $panen) {
            $tahun[] = (int) substr($panen['periode_panen'], 0, 4);
        }

        $tahun = array_values(array_unique($tahun));
        rsort($tahun);

        return $tahun;
    }

    /**
     * Tahun rekap sebuah penanaman.
     *
     * SATU PENANAMAN HANYA MUNCUL DI SATU TAHUN, dan tahun itu adalah tahun
     * panennya - bukan tahun tanamnya. Ditetapkan pemilik proyek 2026-08-24
     * dengan alasan yang tepat: ini rekap PANEN, bukan rekap penanaman.
     *
     * Aturannya dua:
     *
     * 1. Sudah dipanen  -> tahun panen itu.
     * 2. Belum dipanen   -> tahun berjalan, sebab di situlah panennya masih
     *    mungkin terjadi.
     *
     * Akibat aturan kedua, baris yang belum dipanen BERPINDAH mengikuti waktu.
     * Penanaman Oktober 2026 yang belum dipanen tampil pada rekap 2026 selama
     * tahun itu masih berjalan; begitu sistem memasuki 2027 dan panennya tetap
     * belum tercatat, ia pindah ke 2027. Perpindahan itu disengaja: peluang
     * panen pada tahun sebelumnya memang sudah tertutup.
     *
     * Penanaman yang sudah dipanen TIDAK pernah berpindah lagi.
     *
     * @param  int  $penanamanId  Nilai id_penanaman
     * @return int Tahun tempat penanaman ini direkap
     */
    public static function tahunRekapPanen(int $penanamanId): int
    {
        foreach (self::hasilPanen() as $panen) {
            if (($panen['penanaman_id'] ?? null) === $penanamanId) {
                return (int) substr($panen['periode_panen'], 0, 4);
            }
        }

        return (int) date('Y');
    }

    /**
     * Pilihan penyaring rekap panen untuk satu tahun.
     *
     * DIHITUNG DARI PENANAMAN pada tahun itu, bukan dari data master. Bedanya
     * besar dan menentukan: master memuat enam satuan permukiman dan lima
     * komoditas, sedangkan tahun 2025 hanya memiliki satu dari masing-masing.
     *
     * Menawarkan pilihan dari master berarti menyuguhkan opsi yang DIJAMIN
     * menghasilkan tabel kosong - kontrol mati yang dilarang `ui-spec.md`
     * R-26. Bukan tombol yang tidak berfungsi, melainkan pilihan yang sia-sia
     * sejak sebelum diklik.
     *
     * Konsekuensinya disengaja: mengganti tahun mengubah isi kedua daftar,
     * dan nilai penyaring yang tidak lagi tersedia wajib dilepas beserta
     * pemberitahuannya (lihat pemakaiannya pada halaman rekap).
     *
     * @param  int|null  $tahun  Tahun panen; null berarti seluruh tahun
     * @return array<string, array<int, string>> Dua daftar: `sp` dan `komoditas`
     */
    public static function opsiFilterRekapPanen(?int $tahun = null): array
    {
        $sp = [];
        $komoditas = [];

        foreach (self::penanaman() as $tanam) {
            if ($tahun !== null && self::tahunRekapPanen($tanam['id_penanaman']) !== $tahun) {
                continue;
            }

            $sp[$tanam['satuan_permukiman']] = true;
            $komoditas[$tanam['komoditas']] = true;
        }

        $sp = array_keys($sp);
        $komoditas = array_keys($komoditas);

        sort($sp);
        sort($komoditas);

        return ['sp' => $sp, 'komoditas' => $komoditas];
    }

    /**
     * Rekap panen, DIHITUNG DARI PENANAMAN dan bukan dari catatan panen.
     *
     * Perbedaan basis ini menentukan dan disengaja. Bila dihitung dari
     * `hasilPanen()`, poktan yang sudah menanam tetapi belum panen sama sekali
     * HILANG dari rekap, sehingga dinas membaca "tidak ada masalah" justru
     * pada keadaan yang paling perlu ditengok.
     *
     * TERIKAT PERIODE, tidak pernah kumulatif sejak awal waktu. Dua sebab:
     *
     * 1. Luas TIDAK BOLEH dijumlahkan lintas tahun. Bidang 2 ha yang ditanami
     *    tiga tahun berturut-turut akan terbaca "6 ha".
     * 2. Total kumulatif hanya dapat naik, sehingga musim yang hancur pun
     *    tetap tampak sebagai kabar baik.
     *
     * PENGGOLONGAN TAHUNNYA MEMAKAI TAHUN PANEN (diubah 2026-08-24), lihat
     * `tahunRekapPanen()`. Sebelumnya memakai tahun tanam, dan itu keliru:
     * panen April 2026 dari penanaman November 2025 tidak terlihat sama sekali
     * pada rekap 2026, padahal timbangannya nyata terjadi tahun itu.
     *
     * PENYARING SILANG (ditambahkan 2026-08-24). Tab menentukan baris APA,
     * penyaring menentukan baris MANA - dua sumbu terpisah yang berguna justru
     * ketika digabung: "berapa produksi jagung di SP Weain" tidak dapat
     * dijawab tanpa keduanya.
     *
     * Penyaring dicocokkan terhadap PENANAMAN, bukan terhadap kunci
     * pengelompokan. Bedanya terasa pada tab poktan: menyaring komoditas di
     * sana berarti "poktan yang menanam komoditas itu", dan angkanya hanya
     * mencakup penanaman komoditas tersebut - bukan seluruh penanaman poktan.
     *
     * @param  string  $kelompok  Dasar pengelompokan: sp, komoditas, atau poktan
     * @param  int|null  $tahun  Tahun panen; null berarti seluruh tahun
     * @param  string|null  $filterSp  Nama satuan permukiman; null berarti seluruhnya
     * @param  string|null  $filterKomoditas  Nama komoditas; null berarti seluruhnya
     * @return array<int, array<string, mixed>> Baris rekap, produksi terbesar dulu
     */
    public static function rekapPanen(
        string $kelompok = 'sp',
        ?int $tahun = null,
        ?string $filterSp = null,
        ?string $filterKomoditas = null,
    ): array {
        // Panen dikelompokkan lebih dulu menurut penanamannya, agar tiap
        // penanaman cukup sekali disusuri.
        $panenPer = [];

        foreach (self::hasilPanen() as $panen) {
            $panenPer[$panen['penanaman_id']][] = $panen;
        }

        $peta = [];

        foreach (self::penanaman() as $tanam) {
            if ($tahun !== null && self::tahunRekapPanen($tanam['id_penanaman']) !== $tahun) {
                continue;
            }

            if ($filterSp !== null && $tanam['satuan_permukiman'] !== $filterSp) {
                continue;
            }

            if ($filterKomoditas !== null && $tanam['komoditas'] !== $filterKomoditas) {
                continue;
            }

            $kunci = match ($kelompok) {
                'komoditas' => $tanam['komoditas'],
                'poktan' => $tanam['poktan'],
                default => $tanam['satuan_permukiman'],
            };

            if (! isset($peta[$kunci])) {
                $peta[$kunci] = [
                    'nama' => $kunci,
                    'poktan' => [],
                    'sp' => [],
                    'jumlah_anggota' => 0,
                    'luas_lahan' => 0.0,
                    'volume_benih' => 0.0,
                    'realisasi_tanam' => 0.0,
                    'hasil_panen' => 0.0,
                    'puso' => 0.0,
                    'belum_dipanen' => 0.0,
                    'produksi_ton' => 0.0,
                    'nilai_jual' => 0.0,
                ];
            }

            $baris = &$peta[$kunci];

            /*
             * Cacah poktan dan luas lahan dihitung dari HIMPUNAN, bukan jumlah
             * baris: satu poktan dapat memiliki banyak penanaman, dan luas
             * lahannya akan terhitung berkali-kali bila dijumlahkan per baris.
             *
             * Luas lahan hanya mencakup poktan yang MENANAM pada tahun itu,
             * sejalan dengan kolom cacah poktan di sebelahnya. Bila mencakup
             * seluruh poktan di SP, pembaca yang membagi luas dengan cacah
             * poktan akan mendapat angka yang tidak masuk akal.
             */
            if (! in_array($tanam['poktan'], $baris['poktan'], true)) {
                $baris['poktan'][] = $tanam['poktan'];

                $kekuatan = self::rekapLahanPoktan($tanam['poktan_id']);
                $baris['luas_lahan'] += $kekuatan['luas_total'];
                $baris['jumlah_anggota'] += $kekuatan['jumlah_anggota'];
            }

            // SP asal, dipakai tab Per Kelompok Tani. Dihimpun agar tetap
            // benar bila kelak satu poktan menggarap lintas SP.
            if (! in_array($tanam['satuan_permukiman'], $baris['sp'], true)) {
                $baris['sp'][] = $tanam['satuan_permukiman'];
            }

            // Volume benih dijumlahkan per PENANAMAN, sebab satu poktan dapat
            // memakai beberapa benih pada musim yang sama.
            $baris['volume_benih'] += (float) ($tanam['volume_benih'] ?? 0);

            $baris['realisasi_tanam'] += (float) $tanam['realisasi_tanam'];

            // Penanaman yang belum dipanen menyisakan SELURUH luasnya. Sisa
            // parsial tidak lagi mungkin ada sejak panen bertahap dicabut.
            if (! isset($panenPer[$tanam['id_penanaman']])) {
                $baris['belum_dipanen'] += (float) $tanam['realisasi_tanam'];
            }

            foreach ($panenPer[$tanam['id_penanaman']] ?? [] as $panen) {
                $baris['hasil_panen'] += (float) $panen['realisasi_panen'];
                $baris['puso'] += (float) ($panen['puso'] ?? 0);
                $baris['produksi_ton'] += self::keTon($panen['produksi'], $panen['satuan']);
                $baris['nilai_jual'] += ($panen['harga_jual'] ?? 0) * $panen['produksi'];
            }

            unset($baris);
        }

        $hasil = [];

        foreach ($peta as $baris) {
            $baris['jumlah_poktan'] = count($baris['poktan']);

            foreach (['luas_lahan', 'volume_benih', 'realisasi_tanam', 'hasil_panen', 'puso', 'belum_dipanen'] as $kolom) {
                $baris[$kolom] = round($baris[$kolom], 2);
            }

            $baris['produksi_ton'] = round($baris['produksi_ton'], 3);

            /*
             * PRODUKTIVITAS TERTIMBANG, bukan rata-rata kolom produktivitas.
             *
             * Merata-ratakannya menghasilkan angka yang tidak ada di alam:
             * jagung 3,4 ton/ha dan cabai 1.282 kg/ha dirata-rata menjadi 642
             * ton/ha. Yang benar total produksi dibagi total luas dipanen,
             * seluruhnya setelah dikonversi ke ton.
             *
             * Dihitung dari angka yang SUDAH dibulatkan, sebab pembaca
             * mengalikan dua kolom yang tampil di layar untuk memeriksa ulang.
             */
            $baris['produktivitas_ton'] = $baris['hasil_panen'] > 0
                ? round($baris['produksi_ton'] / $baris['hasil_panen'], 3)
                : 0.0;

            $hasil[] = $baris;
        }

        usort($hasil, fn ($a, $b) => $b['produksi_ton'] <=> $a['produksi_ton']);

        return $hasil;
    }

    /**
     * Daftar pengaduan dari warga maupun yang dicatatkan petugas.
     *
     * @return array<int, array<string, mixed>> Data pengaduan
     */
    public static function pengaduan(): array
    {
        $data = [
            [
                'id_pengaduan' => 1,
                'nomor_pengaduan' => 'PGD-2026-0001-PMTUXK',
                'tanggal_pengaduan' => '2026-08-02',
                'nama_pelapor' => 'YOHANES BERE',
                'kontak_pelapor' => '081234567801',
                'email_pelapor' => 'yohanes.bere@example.id',
                'sumber_laporan' => SumberLaporan::Publik->value,
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'kategori' => KategoriPengaduan::Infrastruktur->value,
                /*
                 * Kategori Infrastruktur bersifat netral, sehingga bidang ini
                 * BUKAN nilai turunan melainkan hasil penetapan petugas saat
                 * meninjau. Contoh keadaan setelah kewajiban 10b.7b dipenuhi.
                 */
                'bidang' => 'Ketransmigrasian',
                'judul' => 'Saluran irigasi tersumbat',
                'deskripsi' => 'Saluran irigasi di blok A tersumbat sejak awal musim hujan, air tidak sampai ke lahan usaha.',
                'status' => StatusPengaduan::Diproses->value,
                'lintang' => -9.5131500,
                'bujur' => 124.9139800,
                'prioritas' => PrioritasPengaduan::Tinggi->value,
                /*
                 * Bukti yang dilampirkan PELAPOR saat melapor, berbeda dari
                 * `dokumen_tindak_lanjut` pada penanganan yang diunggah
                 * PETUGAS. Keduanya perlu terpisah agar terbaca siapa yang
                 * menyerahkan berkas mana.
                 */
            ],
            [
                'id_pengaduan' => 2,
                'nomor_pengaduan' => 'PGD-2026-0002-3EKHZA',
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
                'nomor_pengaduan' => 'PGD-2026-0003-3NYVEN',
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
                'nomor_pengaduan' => 'PGD-2026-0004-TGMZ79',
                'tanggal_pengaduan' => '2026-08-09',
                'nama_pelapor' => 'FRANSISKA BRIA',
                'kontak_pelapor' => '081234567806',
                'sumber_laporan' => SumberLaporan::Publik->value,
                'satuan_permukiman' => 'SP Tualaran',
                'satuan_permukiman_id' => 5,
                'kategori' => KategoriPengaduan::Bencana->value,
                /*
                 * Sengaja NULL. Kategori Bencana tidak dapat disimpulkan
                 * bidangnya, dan laporan ini masih berstatus Menunggu Diterima
                 * sehingga belum melewati ambang kewajiban (rules.md 10b.7b).
                 * Keadaan "belum ditentukan" perlu ikut terlihat pada tampilan.
                 */
                'bidang' => null,
                'judul' => 'Longsor kecil di jalan produksi',
                'deskripsi' => 'Terjadi longsor kecil menutup sebagian jalan produksi menuju lahan usaha.',
                'status' => StatusPengaduan::MenungguDiterima->value,
                'lintang' => -9.4991000,
                'bujur' => 124.9430200,
                'prioritas' => PrioritasPengaduan::Mendesak->value,
            ],
            [
                'id_pengaduan' => 5,
                'nomor_pengaduan' => 'PGD-2026-0005-96RY4X',
                'tanggal_pengaduan' => '2026-07-28',
                'nama_pelapor' => 'GABRIEL LEKI',
                'kontak_pelapor' => '081234567807',
                'email_pelapor' => 'gabriel.leki@example.id',
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

            /*
             * Tiga baris berikut melengkapi ragam kategori: pengaduan atas
             * inventaris, atas fasilitas, dan atas kejadian yang tidak
             * bertaut aset. (Tautan objek pengaduan dicabut 2026-08-19,
             * notes.md; satu laporan ditangani satu dinas.)
             */
            [
                'id_pengaduan' => 6,
                'nomor_pengaduan' => 'PGD-2026-0006-KCJSY6',
                'tanggal_pengaduan' => '2026-08-11',
                'nama_pelapor' => 'YULITA HOAR',
                'kontak_pelapor' => '081234567804',
                'sumber_laporan' => SumberLaporan::Petugas->value,
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'kategori' => KategoriPengaduan::InventarisSp->value,
                'bidang' => 'Ketransmigrasian',
                'judul' => 'Meja kantor balai pertemuan rusak',
                'deskripsi' => 'Salah satu meja di ruang kepala patah kakinya sehingga tidak dapat dipakai.',
                'status' => StatusPengaduan::Diproses->value,
                'prioritas' => PrioritasPengaduan::Rendah->value,
            ],
            [
                'id_pengaduan' => 7,
                'nomor_pengaduan' => 'PGD-2026-0007-3YDKAW',
                'tanggal_pengaduan' => '2026-08-12',
                'nama_pelapor' => 'AGUSTINUS SERAN',
                'kontak_pelapor' => '081234567808',
                'sumber_laporan' => SumberLaporan::Publik->value,
                'satuan_permukiman' => 'SP Tniumanu',
                'satuan_permukiman_id' => 2,
                'kategori' => KategoriPengaduan::FasilitasSp->value,
                'bidang' => 'Ketransmigrasian',
                'judul' => 'Plafon ruang kelas sekolah dasar bocor',
                'deskripsi' => 'Plafon dua ruang kelas bocor sehingga kegiatan belajar terganggu saat hujan.',
                'status' => StatusPengaduan::Diproses->value,
                'prioritas' => PrioritasPengaduan::Tinggi->value,
            ],
            [
                'id_pengaduan' => 8,
                'nomor_pengaduan' => 'PGD-2026-0008-2QZY3Q',
                'tanggal_pengaduan' => '2026-08-13',
                'nama_pelapor' => 'THERESIA BAU',
                'kontak_pelapor' => '081234567809',
                'sumber_laporan' => SumberLaporan::Publik->value,
                'satuan_permukiman' => 'SP Harekakae',
                'satuan_permukiman_id' => 3,
                'kategori' => KategoriPengaduan::InventarisSp->value,
                'bidang' => 'Ketransmigrasian',
                'judul' => 'Mesin pompa balai desa tidak berfungsi',
                'deskripsi' => 'Mesin pompa di balai desa mati total, warga tidak dapat mengambil air.',
                'status' => StatusPengaduan::Diproses->value,
                'prioritas' => PrioritasPengaduan::Tinggi->value,
            ],

            /*
             * Contoh kategori Kelompok Tani, ditambahkan 2026-08-19. Keluhan
             * semacam ini sebelumnya terpaksa masuk kategori Lainnya yang
             * justru berbidang kosong, sehingga menambah antrean penyaringan
             * padahal urusannya jelas milik Dinas Pertanian.
             *
             * Sengaja berstatus Menunggu Diterima agar keadaan laporan yang
             * belum ditinjau ikut terwakili, sekaligus tidak menuntut riwayat
             * penanganan.
             */
            [
                'id_pengaduan' => 9,
                'nomor_pengaduan' => 'PGD-2026-0009-669C3Z',
                'tanggal_pengaduan' => '2026-08-15',
                'nama_pelapor' => 'DOMINGGUS TAEK',
                'kontak_pelapor' => '081234567810',
                'sumber_laporan' => SumberLaporan::Publik->value,
                'satuan_permukiman' => 'SP Kapitan Meo',
                'satuan_permukiman_id' => 1,
                'kategori' => KategoriPengaduan::KelompokTani->value,
                'bidang' => 'Pertanian',
                'judul' => 'Pembagian bantuan kelompok tani tidak merata',
                'deskripsi' => 'Sebagian anggota POKTAN MEKAR JAYA belum menerima pembagian bantuan, mohon ditinjau pengelolaannya.',
                'status' => StatusPengaduan::MenungguDiterima->value,
                'prioritas' => PrioritasPengaduan::Sedang->value,
            ],
        ];

        return self::lekatkanBerkas($data, 'pengaduan_berkas', 'pengaduan_id', 'id_pengaduan', ['bukti' => 'dokumen_pendukung']);
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
    public static function penangananPengaduan(string $nomorPengaduan = 'PGD-2026-0001-PMTUXK'): array
    {
        $data = [
            'PGD-2026-0001-PMTUXK' => [
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
                    'dokumen_tindak_lanjut' => self::cariBerkas(18)['nama_file'] ?? null,
                ],
            ],
            'PGD-2026-0002-3EKHZA' => [
                [
                    'tanggal_penanganan' => '2026-08-06',
                    'petugas' => 'SITI RAHMAWATI',
                    'status_sebelum' => StatusPengaduan::MenungguDiterima->value,
                    'status_sesudah' => StatusPengaduan::Diterima->value,
                    'catatan' => 'Laporan diterima, menunggu jadwal peninjauan kondisi atap.',
                    'dokumen_tindak_lanjut' => null,
                ],
            ],
            'PGD-2026-0005-96RY4X' => [
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
                    'dokumen_tindak_lanjut' => self::cariBerkas(19)['nama_file'] ?? null,
                ],
                [
                    'tanggal_penanganan' => '2026-08-04',
                    'petugas' => 'AGUS PRASETYO',
                    'status_sebelum' => StatusPengaduan::Diproses->value,
                    'status_sesudah' => StatusPengaduan::Selesai->value,
                    'catatan' => 'Pendampingan penyemprotan selesai, kondisi tanaman membaik. Petani diberi panduan pengendalian hama.',
                    'dokumen_tindak_lanjut' => self::cariBerkas(20)['nama_file'] ?? null,
                ],
            ],
            'PGD-2026-0006-KCJSY6' => [
                [
                    'tanggal_penanganan' => '2026-08-12',
                    'petugas' => 'NARA WIJAYA',
                    'status_sebelum' => StatusPengaduan::MenungguDiterima->value,
                    'status_sesudah' => StatusPengaduan::Diterima->value,
                    'catatan' => 'Laporan kerusakan meja balai pertemuan diterima.',
                    'dokumen_tindak_lanjut' => null,
                ],
                [
                    'tanggal_penanganan' => '2026-08-14',
                    'petugas' => 'NARA WIJAYA',
                    'status_sebelum' => StatusPengaduan::Diterima->value,
                    'status_sesudah' => StatusPengaduan::Diproses->value,
                    'catatan' => 'Meja ditautkan ke inventaris SP dan diusulkan masuk perbaikan triwulan berikutnya.',
                    'dokumen_tindak_lanjut' => null,
                ],
            ],
            'PGD-2026-0007-3YDKAW' => [
                [
                    'tanggal_penanganan' => '2026-08-13',
                    'petugas' => 'NARA WIJAYA',
                    'status_sebelum' => StatusPengaduan::MenungguDiterima->value,
                    'status_sesudah' => StatusPengaduan::Diterima->value,
                    'catatan' => 'Laporan kebocoran plafon sekolah diterima.',
                    'dokumen_tindak_lanjut' => null,
                ],
                [
                    'tanggal_penanganan' => '2026-08-15',
                    'petugas' => 'NARA WIJAYA',
                    'status_sebelum' => StatusPengaduan::Diterima->value,
                    'status_sesudah' => StatusPengaduan::Diproses->value,
                    'catatan' => 'Peninjauan lapangan selesai, kerusakan cocok dengan catatan kondisi fasilitas.',
                    'dokumen_tindak_lanjut' => null,
                ],
            ],
            'PGD-2026-0008-2QZY3Q' => [
                [
                    'tanggal_penanganan' => '2026-08-14',
                    'petugas' => 'SITI RAHMAWATI',
                    'status_sebelum' => StatusPengaduan::MenungguDiterima->value,
                    'status_sesudah' => StatusPengaduan::Diterima->value,
                    'catatan' => 'Laporan mesin pompa balai desa diterima.',
                    'dokumen_tindak_lanjut' => null,
                ],
                [
                    'tanggal_penanganan' => '2026-08-16',
                    'petugas' => 'SITI RAHMAWATI',
                    'status_sebelum' => StatusPengaduan::Diterima->value,
                    'status_sesudah' => StatusPengaduan::Diproses->value,
                    'catatan' => 'Mesin pompa tidak ditemukan pada inventaris SP, ditandai belum terdata dan diusulkan masuk pendataan berikutnya.',
                    'dokumen_tindak_lanjut' => null,
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
        $data = [
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
                // Satu baris sengaja memuat kedua berkas beserta catatannya
                // agar tampilan pada halaman rincian benar-benar teruji; baris
                // lain dibiarkan kosong supaya keadaan kosong ikut terlihat.
                'keterangan' => 'Bagian hilir tertimbun longsor sejak Januari 2026.',
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
            // Aset penyeimbang data contoh, ditambahkan 2026-08-21 bersama
            // empat parameter yang dahulu terlewat. Sebarannya semula timpang
            // 1 Mandiri, 1 Berkembang, dan 4 Perlu Penanganan, sehingga dua
            // status pertama masing-masing hanya punya satu contoh untuk
            // memeriksa lencana, kartu rekap, dan penyortiran.
            ['id_infrastruktur' => 25, 'nama' => 'GUDANG PASCAPANEN KAPITAN MEO', 'jenis' => JenisInfrastruktur::Gudang->value, 'satuan_permukiman' => 'SP Kapitan Meo', 'satuan_permukiman_id' => 1, 'tahun_perolehan' => 2021, 'sumber_dana' => 'APBN', 'kondisi' => Kondisi::Baik->value, 'kapasitas' => 'Daya tampung 60 ton'],
            ['id_infrastruktur' => 26, 'nama' => 'JALAN PRODUKSI BLOK TIMUR', 'jenis' => JenisInfrastruktur::JalanProduksi->value, 'satuan_permukiman' => 'SP Kapitan Meo', 'satuan_permukiman_id' => 1, 'tahun_perolehan' => 2022, 'sumber_dana' => 'APBD Kabupaten', 'kondisi' => Kondisi::Baik->value, 'kapasitas' => 'Panjang 2,4 km'],
            ['id_infrastruktur' => 27, 'nama' => 'SANITASI KOMUNAL TNIUMANU', 'jenis' => JenisInfrastruktur::Sanitasi->value, 'satuan_permukiman' => 'SP Tniumanu', 'satuan_permukiman_id' => 2, 'tahun_perolehan' => 2021, 'sumber_dana' => 'APBN', 'kondisi' => Kondisi::Baik->value, 'kapasitas' => 'Melayani 45 KK'],
            ['id_infrastruktur' => 28, 'nama' => 'MENARA TELEKOMUNIKASI TNIUMANU', 'jenis' => JenisInfrastruktur::Telekomunikasi->value, 'satuan_permukiman' => 'SP Tniumanu', 'satuan_permukiman_id' => 2, 'tahun_perolehan' => 2022, 'sumber_dana' => 'Swadaya', 'kondisi' => Kondisi::Baik->value, 'kapasitas' => 'Jangkauan 4G'],
            ['id_infrastruktur' => 29, 'nama' => 'SALURAN IRIGASI TNIUMANU', 'jenis' => JenisInfrastruktur::Irigasi->value, 'satuan_permukiman' => 'SP Tniumanu', 'satuan_permukiman_id' => 2, 'tahun_perolehan' => 2019, 'sumber_dana' => 'APBD Provinsi', 'kondisi' => Kondisi::RusakRingan->value, 'kapasitas' => 'Mengairi 18 ha'],
            ['id_infrastruktur' => 30, 'nama' => 'SANITASI KOMUNAL HAREKAKAE', 'jenis' => JenisInfrastruktur::Sanitasi->value, 'satuan_permukiman' => 'SP Harekakae', 'satuan_permukiman_id' => 3, 'tahun_perolehan' => 2021, 'sumber_dana' => 'APBN', 'kondisi' => Kondisi::Baik->value, 'kapasitas' => 'Melayani 52 KK'],
            ['id_infrastruktur' => 31, 'nama' => 'SALURAN IRIGASI HAREKAKAE', 'jenis' => JenisInfrastruktur::Irigasi->value, 'satuan_permukiman' => 'SP Harekakae', 'satuan_permukiman_id' => 3, 'tahun_perolehan' => 2020, 'sumber_dana' => 'APBD Provinsi', 'kondisi' => Kondisi::Baik->value, 'kapasitas' => 'Mengairi 26 ha'],
            ['id_infrastruktur' => 32, 'nama' => 'JALAN PRODUKSI HAREKAKAE', 'jenis' => JenisInfrastruktur::JalanProduksi->value, 'satuan_permukiman' => 'SP Harekakae', 'satuan_permukiman_id' => 3, 'tahun_perolehan' => 2022, 'sumber_dana' => 'APBD Kabupaten', 'kondisi' => Kondisi::Baik->value, 'kapasitas' => 'Panjang 3,1 km'],
            ['id_infrastruktur' => 33, 'nama' => 'KIOS SAPROTAN HAREKAKAE', 'jenis' => JenisInfrastruktur::PasarKios->value, 'satuan_permukiman' => 'SP Harekakae', 'satuan_permukiman_id' => 3, 'tahun_perolehan' => 2022, 'sumber_dana' => 'Swadaya', 'kondisi' => Kondisi::Baik->value, 'kapasitas' => 'Satu unit kios'],
            ['id_infrastruktur' => 34, 'nama' => 'MENARA TELEKOMUNIKASI TUALARAN', 'jenis' => JenisInfrastruktur::Telekomunikasi->value, 'satuan_permukiman' => 'SP Tualaran', 'satuan_permukiman_id' => 5, 'tahun_perolehan' => 2023, 'sumber_dana' => 'APBN', 'kondisi' => Kondisi::Baik->value, 'kapasitas' => 'Jangkauan 4G'],
            ['id_infrastruktur' => 35, 'nama' => 'SANITASI KOMUNAL TUALARAN', 'jenis' => JenisInfrastruktur::Sanitasi->value, 'satuan_permukiman' => 'SP Tualaran', 'satuan_permukiman_id' => 5, 'tahun_perolehan' => 2019, 'sumber_dana' => 'APBN', 'kondisi' => Kondisi::RusakRingan->value, 'kapasitas' => 'Melayani 38 KK'],
        ];

        $data = self::lekatkanBerkas($data, 'infrastruktur_berkas', 'infrastruktur_id', 'id_infrastruktur', ['foto' => 'foto', 'pendukung' => 'dokumen_pendukung']);

        // Cakupan layanan lintas SP (Putaran 7). Sebelumnya kenyataan ini
        // hanya tertulis di `kapasitas` sebagai teks ("Melayani 3 SP sekitar"),
        // sebab satu FK tunggal tidak dapat menampungnya. `satuan_permukiman_id`
        // TETAP sebagai lokasi/pangkal; `satuan_permukiman_ids` menambahkan SP
        // lain yang benar-benar dilayani, dan penilaian kondisi SP membacanya.
        $cakupanTambahan = [
            1 => [2],       // SALURAN IRIGASI BLOK A juga mengairi blok di SP Tniumanu
            9 => [2, 5],    // KIOS SAPROTAN DESA "melayani 3 SP sekitar"
            19 => [5],      // IRIGASI BLOK BARAT mengairi lahan di SP Tualaran juga
        ];

        return array_map(function (array $a) use ($cakupanTambahan): array {
            $ids = array_values(array_unique(array_merge(
                [$a['satuan_permukiman_id']],
                $cakupanTambahan[$a['id_infrastruktur']] ?? [],
            )));
            sort($ids);

            return $a + ['satuan_permukiman_ids' => $ids];
        }, $data);
    }

    /**
     * Cakupan layanan tiap aset infrastruktur, satu baris per SP dilayani
     * (Putaran 7). Wajib memuat SP pangkal (`satuan_permukiman_id`).
     *
     * @return array<int, array{infrastruktur_id: int, satuan_permukiman_id: int, pangkal: bool}>
     */
    public static function infrastrukturCakupan(): array
    {
        $hasil = [];

        foreach (self::infrastruktur() as $a) {
            foreach ($a['satuan_permukiman_ids'] as $spId) {
                $hasil[] = [
                    'infrastruktur_id' => $a['id_infrastruktur'],
                    'satuan_permukiman_id' => $spId,
                    'pangkal' => $spId === $a['satuan_permukiman_id'],
                ];
            }
        }

        return $hasil;
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
        // Provinsi dan kabupaten dibaca dari data referensi nasional
        // (2026-09-02), bukan lagi dua baris tulis tangan yang hanya memuat
        // NTT dan Malaka. Alasannya: daftar ini melayani pemilihan daerah asal
        // transmigran yang dapat berasal dari mana pun di Indonesia, sedangkan
        // dua baris itu hanya cukup untuk lokusnya sendiri.
        //
        // Idnya mengikuti kode BPS, sehingga Malaka bernilai 5321 dan bukan 1.
        // Kecamatan di bawah menyesuaikan diri terhadap kode itu.
        $provinsi = array_map(fn (array $p) => [
            'id_provinsi' => $p['id'],
            'nama' => $p['nama'],
            'kode' => $p['kode'],
        ], DataWilayah::provinsi());

        $namaProvinsi = DataWilayah::petaProvinsi();

        $kabupaten = array_map(fn (array $k) => [
            'id_kabupaten' => $k['id'],
            'provinsi_id' => $k['provinsi_id'],
            'provinsi' => $namaProvinsi[$k['provinsi_id']] ?? '',
            'nama' => $k['nama'],
            'kode' => $k['kode'],
        ], DataWilayah::kabupaten());

        // KECAMATAN DAN DESA TETAP WILAYAH LOKUS, bukan seluruh Indonesia.
        // Berkas sumber memuat 7.000 kecamatan dan 83.000 kelurahan, sedangkan
        // hanya wilayah ber-SP yang bermakna di sini: dropdown pemilihan desa
        // pada form SP akan berisi puluhan ribu pilihan yang seluruhnya keliru
        // kecuali enam. Pemuatan penuh menunggu Tahap 3, ketika pengambilan
        // bertahap lewat endpoint sudah tersedia.
        return [
            'provinsi' => $provinsi,
            'kabupaten' => $kabupaten,
            'kecamatan' => [
                ['id_kecamatan' => 1, 'kabupaten_id' => 5321, 'kabupaten' => 'Kabupaten Malaka', 'nama' => 'Laen Manen', 'jumlah_desa' => 2],
                ['id_kecamatan' => 2, 'kabupaten_id' => 5321, 'kabupaten' => 'Kabupaten Malaka', 'nama' => 'Malaka Tengah', 'jumlah_desa' => 1],
                ['id_kecamatan' => 3, 'kabupaten_id' => 5321, 'kabupaten' => 'Kabupaten Malaka', 'nama' => 'Wewiku', 'jumlah_desa' => 1],
                ['id_kecamatan' => 4, 'kabupaten_id' => 5321, 'kabupaten' => 'Kabupaten Malaka', 'nama' => 'Rinhat', 'jumlah_desa' => 2],
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
     * Daftar desa beserta id kabupatennya.
     *
     * `kabupaten_id` DITURUNKAN lewat kecamatan, tidak disimpan pada baris
     * desa. Menyimpannya berarti dua kolom yang saling menentukan dapat
     * berselisih diam-diam ketika satu kecamatan dipindah kabupaten.
     *
     * Dipakai form SP untuk menyaring desa menurut kabupaten kawasan yang
     * dipilih. Penyaringan menempuh KABUPATEN, bukan relasi kawasan ke desa:
     * kawasan dan desa adalah dua cabang terpisah yang baru bertemu di SP
     * (agents/rules.md bagian 4a.2).
     *
     * @return array<int, array<string, mixed>> Desa beserta kabupaten_id
     */
    public static function desaBerkabupaten(): array
    {
        $wilayah = self::wilayah();
        $kecamatan = array_column($wilayah['kecamatan'], null, 'id_kecamatan');

        return array_map(fn (array $d) => $d + [
            'kabupaten_id' => $kecamatan[$d['kecamatan_id']]['kabupaten_id'] ?? null,
        ], $wilayah['desa']);
    }

    /**
     * Inventaris SP, yaitu barang bergerak milik satuan permukiman.
     *
     * @return array<int, array<string, mixed>> Data inventaris
     */
    public static function inventarisSp(): array
    {
        // `rincian_kondisi` (Putaran 7): histogram kondisi per jenis barang,
        // sehingga "sebagian retak" jadi angka, bukan kalimat. Tetap per
        // jenis, bukan per unit ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â kursi ke-3 masih tak dapat dibedakan dari
        // kursi ke-7.
        $data = [
            ['id_inventaris_sp' => 1, 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'jenis_inventaris' => 'Perabotan', 'nama_barang' => 'MEJA KANTOR', 'jumlah' => 12, 'satuan_barang' => 'unit', 'tahun_perolehan' => 2016, 'sumber_dana' => 'APBN', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'keterangan' => null],
            ['id_inventaris_sp' => 2, 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'jenis_inventaris' => 'Perabotan', 'nama_barang' => 'KURSI PLASTIK', 'jumlah' => 60, 'satuan_barang' => 'buah', 'tahun_perolehan' => 2016, 'sumber_dana' => 'APBN', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Rusak Ringan', 'rincian_kondisi' => ['Baik' => 43, 'Rusak Ringan' => 15, 'Rusak Berat' => 2], 'keterangan' => 'Sebagian retak pada sandaran.'],
            ['id_inventaris_sp' => 3, 'satuan_permukiman_id' => 2, 'satuan_permukiman' => 'SP Tniumanu', 'jenis_inventaris' => 'Elektronik & Mesin', 'nama_barang' => 'GENSET 5000 WATT', 'jumlah' => 1, 'satuan_barang' => 'unit', 'tahun_perolehan' => 2018, 'sumber_dana' => 'APBD Kabupaten', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'keterangan' => null],
            ['id_inventaris_sp' => 4, 'satuan_permukiman_id' => 3, 'satuan_permukiman' => 'SP Harekakae', 'jenis_inventaris' => 'Elektronik & Mesin', 'nama_barang' => 'KOMPUTER DESKTOP', 'jumlah' => 2, 'satuan_barang' => 'unit', 'tahun_perolehan' => 2019, 'sumber_dana' => 'Dinas Transmigrasi Kabupaten', 'status_penyerahan' => 'Dalam Proses', 'kondisi' => 'Baik', 'keterangan' => 'Berita acara sedang diproses.'],
            ['id_inventaris_sp' => 5, 'satuan_permukiman_id' => 5, 'satuan_permukiman' => 'SP Tualaran', 'jenis_inventaris' => 'Peralatan Kantor', 'nama_barang' => 'LEMARI ARSIP', 'jumlah' => 4, 'satuan_barang' => 'unit', 'tahun_perolehan' => 2019, 'sumber_dana' => 'APBD Provinsi', 'status_penyerahan' => 'Belum Diserahkan', 'kondisi' => 'Baik', 'rincian_kondisi' => ['Baik' => 3, 'Rusak Ringan' => 1], 'keterangan' => null],
        ];

        $data = self::lekatkanBerkas($data, 'inventaris_sp_berkas', 'inventaris_sp_id', 'id_inventaris_sp', ['foto' => 'foto', 'pendukung' => 'dokumen_pendukung']);

        return self::denganRincianKondisi($data);
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
        $data = [
            ['id_fasilitas_sp' => 1, 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'jenis_fasilitas' => 'Balai Pertemuan', 'nama_fasilitas' => 'BALAI PERTEMUAN', 'jumlah' => 1, 'tahun_perolehan' => 2016, 'sumber_dana' => 'APBN', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'lintang' => -9.5124500, 'bujur' => 124.9125000, 'keterangan' => null],
            ['id_fasilitas_sp' => 2, 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'jenis_fasilitas' => 'Kesehatan', 'nama_fasilitas' => 'PUSKESMAS PEMBANTU', 'jumlah' => 1, 'tahun_perolehan' => 2017, 'sumber_dana' => 'APBD Kabupaten', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'lintang' => -9.5127000, 'bujur' => 124.9128000, 'keterangan' => null],
            ['id_fasilitas_sp' => 3, 'satuan_permukiman_id' => 2, 'satuan_permukiman' => 'SP Tniumanu', 'jenis_fasilitas' => 'Pendidikan Dasar', 'nama_fasilitas' => 'SEKOLAH DASAR', 'jumlah' => 1, 'tahun_perolehan' => 2016, 'sumber_dana' => 'APBN', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Rusak Ringan', 'lintang' => -9.4982000, 'bujur' => 124.8878000, 'keterangan' => 'Plafon ruang kelas dua bocor.'],
            ['id_fasilitas_sp' => 4, 'satuan_permukiman_id' => 3, 'satuan_permukiman' => 'SP Harekakae', 'jenis_fasilitas' => 'Ibadah', 'nama_fasilitas' => 'RUMAH IBADAH', 'jumlah' => 2, 'tahun_perolehan' => 2017, 'sumber_dana' => 'Lembaga Swadaya Masyarakat', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'lintang' => -9.4554000, 'bujur' => 124.9453000, 'keterangan' => null],
            // "Dua pos lapuk" kini DATA, bukan kalimat di keterangan (Putaran 7).
            ['id_fasilitas_sp' => 5, 'satuan_permukiman_id' => 6, 'satuan_permukiman' => 'SP Weain', 'jenis_fasilitas' => 'Keamanan', 'nama_fasilitas' => 'POS KAMLING', 'jumlah' => 3, 'tahun_perolehan' => 2018, 'sumber_dana' => 'APBD Provinsi', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Rusak Berat', 'rincian_kondisi' => ['Baik' => 1, 'Rusak Berat' => 2], 'lintang' => -9.3766000, 'bujur' => 125.0346000, 'keterangan' => 'Dua pos lapuk dimakan rayap.'],

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
            // Fasilitas penyeimbang data contoh (2026-08-21). Empat jenis
            // terakhir baru ikut dinilai sejak parameter dihasilkan dari data
            // master, sehingga sebelumnya tidak ada satu pun contohnya.
            ['id_fasilitas_sp' => 14, 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'jenis_fasilitas' => 'Pendidikan Lanjutan', 'nama_fasilitas' => 'SMP SATU ATAP KAPITAN MEO', 'jumlah' => 1, 'tahun_perolehan' => 2021, 'sumber_dana' => 'APBN', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'lintang' => -9.3612000, 'bujur' => 124.9871000, 'keterangan' => null],
            ['id_fasilitas_sp' => 15, 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'jenis_fasilitas' => 'Pasar atau Kios', 'nama_fasilitas' => 'PASAR DESA KAPITAN MEO', 'jumlah' => 1, 'tahun_perolehan' => 2022, 'sumber_dana' => 'APBD Kabupaten', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'lintang' => -9.3618000, 'bujur' => 124.9880000, 'keterangan' => null],
            ['id_fasilitas_sp' => 16, 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'jenis_fasilitas' => 'Olahraga', 'nama_fasilitas' => 'LAPANGAN SERBAGUNA KAPITAN MEO', 'jumlah' => 1, 'tahun_perolehan' => 2020, 'sumber_dana' => 'Swadaya', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'lintang' => -9.3625000, 'bujur' => 124.9865000, 'keterangan' => null],
            ['id_fasilitas_sp' => 17, 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'jenis_fasilitas' => 'Keamanan', 'nama_fasilitas' => 'POS KAMLING KAPITAN MEO', 'jumlah' => 2, 'tahun_perolehan' => 2021, 'sumber_dana' => 'Swadaya', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'lintang' => -9.3609000, 'bujur' => 124.9858000, 'keterangan' => null],
            ['id_fasilitas_sp' => 18, 'satuan_permukiman_id' => 2, 'satuan_permukiman' => 'SP Tniumanu', 'jenis_fasilitas' => 'Balai Pertemuan', 'nama_fasilitas' => 'BALAI PERTEMUAN TNIUMANU', 'jumlah' => 1, 'tahun_perolehan' => 2020, 'sumber_dana' => 'APBD Kabupaten', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'lintang' => -9.3745000, 'bujur' => 124.9993000, 'keterangan' => null],
            ['id_fasilitas_sp' => 19, 'satuan_permukiman_id' => 2, 'satuan_permukiman' => 'SP Tniumanu', 'jenis_fasilitas' => 'Ibadah', 'nama_fasilitas' => 'GEREJA STASI TNIUMANU', 'jumlah' => 1, 'tahun_perolehan' => 2018, 'sumber_dana' => 'Swadaya', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'lintang' => -9.3751000, 'bujur' => 124.9987000, 'keterangan' => null],
            ['id_fasilitas_sp' => 20, 'satuan_permukiman_id' => 2, 'satuan_permukiman' => 'SP Tniumanu', 'jenis_fasilitas' => 'Keamanan', 'nama_fasilitas' => 'POS KAMLING TNIUMANU', 'jumlah' => 1, 'tahun_perolehan' => 2019, 'sumber_dana' => 'Swadaya', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Rusak Ringan', 'lintang' => -9.3738000, 'bujur' => 125.0001000, 'keterangan' => null],
            ['id_fasilitas_sp' => 21, 'satuan_permukiman_id' => 3, 'satuan_permukiman' => 'SP Harekakae', 'jenis_fasilitas' => 'Pendidikan Lanjutan', 'nama_fasilitas' => 'SMP NEGERI HAREKAKAE', 'jumlah' => 1, 'tahun_perolehan' => 2019, 'sumber_dana' => 'APBN', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Rusak Ringan', 'lintang' => -9.3881000, 'bujur' => 125.0072000, 'keterangan' => null],
            ['id_fasilitas_sp' => 22, 'satuan_permukiman_id' => 3, 'satuan_permukiman' => 'SP Harekakae', 'jenis_fasilitas' => 'Pasar atau Kios', 'nama_fasilitas' => 'PASAR DESA HAREKAKAE', 'jumlah' => 1, 'tahun_perolehan' => 2021, 'sumber_dana' => 'APBD Kabupaten', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'lintang' => -9.3888000, 'bujur' => 125.0065000, 'keterangan' => null],
            ['id_fasilitas_sp' => 23, 'satuan_permukiman_id' => 3, 'satuan_permukiman' => 'SP Harekakae', 'jenis_fasilitas' => 'Olahraga', 'nama_fasilitas' => 'LAPANGAN SEPAK BOLA HAREKAKAE', 'jumlah' => 1, 'tahun_perolehan' => 2020, 'sumber_dana' => 'Swadaya', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'lintang' => -9.3875000, 'bujur' => 125.0081000, 'keterangan' => null],
            ['id_fasilitas_sp' => 24, 'satuan_permukiman_id' => 3, 'satuan_permukiman' => 'SP Harekakae', 'jenis_fasilitas' => 'Keamanan', 'nama_fasilitas' => 'POS KAMLING HAREKAKAE', 'jumlah' => 2, 'tahun_perolehan' => 2021, 'sumber_dana' => 'Swadaya', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'lintang' => -9.3869000, 'bujur' => 125.0058000, 'keterangan' => null],
            ['id_fasilitas_sp' => 25, 'satuan_permukiman_id' => 5, 'satuan_permukiman' => 'SP Tualaran', 'jenis_fasilitas' => 'Kesehatan', 'nama_fasilitas' => 'POSKESDES TUALARAN', 'jumlah' => 1, 'tahun_perolehan' => 2022, 'sumber_dana' => 'APBN', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'lintang' => -9.3985000, 'bujur' => 125.0134000, 'keterangan' => null],
            ['id_fasilitas_sp' => 26, 'satuan_permukiman_id' => 5, 'satuan_permukiman' => 'SP Tualaran', 'jenis_fasilitas' => 'Balai Pertemuan', 'nama_fasilitas' => 'BALAI PERTEMUAN NAET', 'jumlah' => 1, 'tahun_perolehan' => 2021, 'sumber_dana' => 'APBD Kabupaten', 'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'lintang' => -9.3979000, 'bujur' => 125.0141000, 'keterangan' => null],
        ];

        $data = self::lekatkanBerkas($data, 'fasilitas_sp_berkas', 'fasilitas_sp_id', 'id_fasilitas_sp', ['foto' => 'foto', 'pendukung' => 'dokumen_pendukung']);

        // Cakupan layanan lintas SP (Putaran 7), pola sama dengan
        // infrastruktur: SMP Satu Atap dan Puskesmas Pembantu di satu SP
        // melayani anak dan warga SP tetangga yang berjalan ke sana. Tanpa ini
        // penilaian kondisi SP tetangga mencatatnya sebagai tidak punya SMP.
        $cakupanTambahan = [
            2 => [2],       // PUSKESMAS PEMBANTU Kapitan Meo melayani SP Tniumanu juga
            14 => [2],      // SMP SATU ATAP KAPITAN MEO menampung anak SP Tniumanu
            15 => [2],      // PASAR DESA KAPITAN MEO
        ];

        return array_map(function (array $a) use ($cakupanTambahan): array {
            $ids = array_values(array_unique(array_merge(
                [$a['satuan_permukiman_id']],
                $cakupanTambahan[$a['id_fasilitas_sp']] ?? [],
            )));
            sort($ids);

            return $a + ['satuan_permukiman_ids' => $ids];
        }, self::denganRincianKondisi($data));
    }

    /**
     * Cakupan layanan tiap fasilitas SP, satu baris per SP dilayani
     * (Putaran 7). Wajib memuat SP pangkal.
     *
     * @return array<int, array{fasilitas_sp_id: int, satuan_permukiman_id: int, pangkal: bool}>
     */
    public static function fasilitasSpCakupan(): array
    {
        $hasil = [];

        foreach (self::fasilitasSp() as $a) {
            foreach ($a['satuan_permukiman_ids'] as $spId) {
                $hasil[] = [
                    'fasilitas_sp_id' => $a['id_fasilitas_sp'],
                    'satuan_permukiman_id' => $spId,
                    'pangkal' => $spId === $a['satuan_permukiman_id'],
                ];
            }
        }

        return $hasil;
    }

    /**
     * Daftar pilihan yang dikelola Admin lewat data master referensi.
     *
     * Menggantikan sembilan enum yang sebelumnya ditulis di dalam kode. Yang
     * tersimpan pada kolom pemakainya tetap TEKS `nilai`, bukan id, sebab
     * kolom-kolom itu bertipe ENUM pada SQL referensi dan sudah dipakai
     * puluhan tampilan tanpa join (kamus data 5.6).
     *
     * Nilai DINONAKTIFKAN, tidak pernah dihapus: menghapus `Hibah` dari sumber
     * dana membuat baris infrastruktur lama menunjuk nilai yang lenyap, dan
     * rekapnya kehilangan baris itu tanpa pesan apa pun.
     *
     * @param  JenisReferensi|null  $jenis  Menyaring satu jenis; null berarti seluruhnya
     * @param  bool  $hanyaAktif  Menyaring nilai yang masih ditawarkan
     * @return array<int, array<string, mixed>> Data referensi, terurut
     */
    public static function referensi(?JenisReferensi $jenis = null, bool $hanyaAktif = false): array
    {
        $data = [];
        $id = 1;

        // Disusun dari daftar per jenis agar urutan dan penomorannya konsisten
        // tanpa perlu menuliskan id satu per satu.
        $daftar = [
            JenisReferensi::SumberDana->value => [
                'APBN', 'APBD Provinsi', 'APBD Kabupaten', 'Dinas Transmigrasi Kabupaten',
                'Dinas Pertanian Kabupaten', 'Lembaga Swadaya Masyarakat', 'Swadaya', 'Lainnya',
            ],
            JenisReferensi::StatusPenyerahan->value => [
                'Sudah Diserahkan', 'Dalam Proses', 'Belum Diserahkan',
            ],
            // Berskor: nilainya dipakai menghitung kondisi SP (rules.md 10c).
            JenisReferensi::Kondisi->value => ['Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang'],
            JenisReferensi::KondisiRumah->value => ['Tidak Rusak', 'Rusak Ringan', 'Rusak Berat'],
            JenisReferensi::StatusHunian->value => ['Dihuni', 'Tidak Dihuni'],
            JenisReferensi::TipeKomoditas->value => ['Pangan', 'Palawija', 'Hortikultura'],
            // Berjenjang: urutannya dipakai menyortir daftar pengaduan.
            JenisReferensi::PrioritasPengaduan->value => ['Rendah', 'Sedang', 'Tinggi', 'Mendesak'],
            // Tanpa `Ketua`: ketua ditetapkan pada profil poktan, dan
            // menyediakannya di sini membuat satu poktan dapat memiliki dua
            // ketua tanpa penjaga apa pun (rules.md 7a poin 4b).
            JenisReferensi::JabatanAnggotaPoktan->value => ['Sekretaris', 'Bendahara', 'Anggota'],
            // Dirujuk parameter penilaian SP lewat `referensi_id`, bukan teks.
            // Urutannya karena itu TIDAK boleh diacak sembarangan: id-nya
            // sudah ditunjuk `PenilaianKondisiSp::parameter()`.
            JenisReferensi::JenisInfrastruktur->value => [
                'Air', 'Sanitasi', 'Irigasi', 'Listrik', 'Jalan Penghubung',
                'Jalan Produksi', 'Telekomunikasi', 'Gudang', 'Pasar atau Kios Saprotan', 'Lainnya',
            ],
            JenisReferensi::JenisFasilitas->value => [
                'Kesehatan', 'Pendidikan Dasar', 'Pendidikan Lanjutan', 'Ibadah',
                'Balai Pertemuan', 'Pasar atau Kios', 'Olahraga', 'Keamanan', 'Lainnya',
            ],
            // Bidang didaftarkan SEBELUM kategori, sebab kategori merujuknya.
            JenisReferensi::BidangPengaduan->value => ['Ketransmigrasian', 'Pertanian'],
            JenisReferensi::KategoriPengaduan->value => [
                'Lahan Usaha', 'Lahan Pekarangan', 'Rumah', 'Infrastruktur',
                'Inventaris SP', 'Fasilitas SP', 'Kelompok Tani', 'Alsintan',
                'Saprotan', 'Produksi Panen', 'Bencana', 'Lainnya',
            ],
            // Ditambahkan 2026-08-30 (Putaran 7). WAJIB di urutan paling akhir:
            // penomoran id mengikuti urutan deklarasi, dan menyisipkan di tengah
            // akan menggeser id `jenis_infrastruktur` / `jenis_fasilitas` yang
            // sudah ditunjuk PenilaianKondisiSp::parameter(). Sebelumnya alsintan
            // tidak punya `jenis` sama sekali; "Jenis Alat" pada laporan adalah
            // `nama_alat` yang dipakai ulang.
            JenisReferensi::JenisAlsintan->value => [
                'Traktor Roda Dua', 'Traktor Roda Empat', 'Pompa Air', 'Hand Sprayer',
                'Mesin Perontok', 'Cultivator', 'Alat Panen', 'Lainnya',
            ],
            JenisReferensi::JenisInventaris->value => [
                'Peralatan Kantor', 'Elektronik & Mesin', 'Perabotan', 'Kendaraan Operasional', 'Peralatan Lainnya',
            ],
        ];

        // Bidang penanganan bawaan tiap kategori, menggantikan `match` pada
        // BidangPengaduan::dariKategori(). Kategori yang TIDAK tercantum di
        // sini sengaja berbidang null: ia dapat jatuh ke dua dinas sekaligus,
        // sehingga bidangnya wajib ditetapkan petugas sebelum status maju ke
        // Diproses (rules.md 10b poin 7b). Menebaknya justru menyesatkan,
        // sebab laporan masuk ke daftar dinas yang keliru lalu tertahan.
        $bidangBawaan = [
            'Rumah' => 'Ketransmigrasian',
            'Lahan Pekarangan' => 'Ketransmigrasian',
            'Inventaris SP' => 'Ketransmigrasian',
            'Fasilitas SP' => 'Ketransmigrasian',
            'Kelompok Tani' => 'Pertanian',
            'Alsintan' => 'Pertanian',
            'Saprotan' => 'Pertanian',
            'Produksi Panen' => 'Pertanian',
        ];

        // Skor kondisi, dipisah dari daftar di atas agar daftar nilainya tetap
        // terbaca sebagai satu baris. Nilai ini yang menggantikan konstanta
        // NILAI_KONDISI pada PenilaianKondisiSp.
        //
        // "Hilang" bernilai 0.0, bukan sekadar lebih kecil dari "Rusak Berat":
        // barang yang lenyap tidak melayani siapa pun, sedangkan barang rusak
        // berat masih dapat diperbaiki. Menyamakan keduanya membuat SP yang
        // kehilangan inventarisnya tetap terhitung memiliki layanan itu.
        $skor = ['Baik' => 1.0, 'Rusak Ringan' => 0.5, 'Rusak Berat' => 0.2, 'Hilang' => 0.0];

        // Satu nilai sengaja dinonaktifkan agar keadaan itu ikut terlihat saat
        // peninjauan: ia tetap terbaca pada data lama, hanya tidak lagi
        // ditawarkan pada data baru.
        $nonaktif = [JenisReferensi::SumberDana->value => ['Lembaga Swadaya Masyarakat']];

        // Id bidang dikumpulkan saat barisnya dibuat, lalu dipakai kategori.
        // Berhasil karena bidang didaftarkan lebih dulu pada $daftar di atas.
        $idBidang = [];

        foreach ($daftar as $kunciJenis => $nilai) {
            $jenisIni = JenisReferensi::from($kunciJenis);

            foreach (array_values($nilai) as $urutan => $satu) {
                if ($jenisIni === JenisReferensi::BidangPengaduan) {
                    $idBidang[$satu] = $id;
                }

                $data[] = [
                    'id_referensi' => $id++,
                    'jenis' => $kunciJenis,
                    'jenis_label' => $jenisIni->label(),
                    'nilai' => $satu,
                    'urutan' => $urutan + 1,
                    'nilai_skor' => $jenisIni->berskor() ? ($skor[$satu] ?? null) : null,
                    'bidang_id' => $jenisIni->berbidang()
                        ? ($idBidang[$bidangBawaan[$satu] ?? ''] ?? null)
                        : null,
                    'is_aktif' => ! in_array($satu, $nonaktif[$kunciJenis] ?? [], true),
                ];
            }
        }

        if ($hanyaAktif) {
            $data = array_values(array_filter($data, fn ($b) => $b['is_aktif']));
        }

        if ($jenis === null) {
            return $data;
        }

        return array_values(array_filter($data, fn ($b) => $b['jenis'] === $jenis->value));
    }

    /**
     * Peta nilai referensi untuk dropdown, hanya yang masih aktif.
     *
     * Menggantikan pemanggilan `Enum::opsi()` pada view. Bentuk kembaliannya
     * sengaja disamakan, yaitu peta nilai ke label, sehingga view yang beralih
     * tidak perlu mengubah cara membacanya.
     *
     * Nilai NONAKTIF tidak ikut, sebab dropdown menawarkan pilihan untuk data
     * BARU. Data lama yang memakainya tetap menampilkan teksnya apa adanya,
     * sebab yang tersimpan memang teks itu sendiri, bukan id.
     *
     * @param  JenisReferensi  $jenis  Daftar yang diambil
     * @return array<string, string> Peta nilai ke label
     */
    public static function opsiReferensi(JenisReferensi $jenis): array
    {
        $hasil = [];

        foreach (self::referensi($jenis, true) as $baris) {
            $hasil[$baris['nilai']] = $baris['nilai'];
        }

        return $hasil;
    }

    /**
     * Id baris referensi dari jenis dan nilainya.
     *
     * Dipakai `PenilaianKondisiSp::parameter()` untuk menunjuk jenis rujukan
     * lewat `referensi_id`, bukan teks. Nilai NONAKTIF tetap ditemukan, sebab
     * parameter yang sudah menunjuknya harus tetap dapat membacanya; yang
     * berhenti adalah menawarkannya sebagai pilihan baru.
     *
     * @param  JenisReferensi  $jenis  Daftar yang dicari
     * @param  string  $nilai  Teks nilainya
     * @return int|null Id referensi, null bila tidak ada
     */
    public static function referensiId(JenisReferensi $jenis, string $nilai): ?int
    {
        foreach (self::referensi($jenis) as $baris) {
            if ($baris['nilai'] === $nilai) {
                return $baris['id_referensi'];
            }
        }

        return null;
    }

    /**
     * Nilai referensi dari idnya.
     *
     * Kebalikan referensiId(). Dipakai penilaian kondisi SP untuk mencocokkan
     * parameter dengan kolom `jenis` pada aset, yang masih menyimpan teks.
     *
     * @param  int  $id  Id referensi
     * @return string|null Teks nilainya, null bila tidak ada
     */
    public static function referensiNilai(int $id): ?string
    {
        foreach (self::referensi() as $baris) {
            if ($baris['id_referensi'] === $id) {
                return $baris['nilai'];
            }
        }

        return null;
    }

    /**
     * Peta nilai referensi untuk FILTER, termasuk yang sudah nonaktif.
     *
     * Berbeda dari opsiReferensi() dan perbedaannya disengaja. Dropdown pada
     * form menawarkan pilihan untuk data BARU, sehingga nilai nonaktif memang
     * tidak boleh ikut. Dropdown filter menyaring data LAMA, dan data lama
     * masih memakai nilai yang kini nonaktif. Memakai daftar aktif di filter
     * membuat baris-baris itu tidak dapat dicari sama sekali: nilainya ada di
     * kolom, tetapi tidak ada pilihan yang cocok untuk memanggilnya.
     *
     * @param  JenisReferensi  $jenis  Daftar yang diambil
     * @return array<string, string> Peta nilai ke label
     */
    public static function opsiFilterReferensi(JenisReferensi $jenis): array
    {
        $hasil = [];

        foreach (self::referensi($jenis) as $baris) {
            $hasil[$baris['nilai']] = $baris['nilai'];
        }

        return $hasil;
    }

    /**
     * Peta kategori pengaduan ke bidang penanganan bawaannya.
     *
     * Menggantikan `match` pada `BidangPengaduan::dariKategori()`. Selama peta
     * itu berupa `match` tanpa `default`, kategori TIDAK BOLEH ditambah lewat
     * data master: kategori baru akan melempar `UnhandledMatchError` begitu ada
     * yang memilihnya, sehingga form pengaduan mati total.
     *
     * Kategori netral bernilai string kosong, bukan null, agar Alpine dapat
     * membedakannya dari bidang yang sudah pasti tanpa memeriksa null.
     *
     * @return array<string, string> Peta nilai kategori ke nilai bidang
     */
    public static function petaBidangKategori(): array
    {
        $hasil = [];

        foreach (self::referensi(JenisReferensi::KategoriPengaduan) as $baris) {
            $hasil[$baris['nilai']] = $baris['bidang_id'] === null
                ? ''
                : (self::referensiNilai($baris['bidang_id']) ?? '');
        }

        return $hasil;
    }

    /**
     * Peta nilai kondisi ke skornya, dibaca penilaian kondisi SP.
     *
     * Menggantikan konstanta `PenilaianKondisiSp::NILAI_KONDISI`. Dibaca dari
     * data agar Admin dapat menyesuaikannya tanpa mengubah kode, sejalan
     * dengan bobot parameter yang sudah lebih dulu berupa data (erd.md 7.3).
     *
     * @return array<string, float> Peta nilai kondisi ke skornya
     */
    public static function skorKondisi(): array
    {
        $hasil = [];

        foreach (self::referensi(JenisReferensi::Kondisi) as $baris) {
            if ($baris['nilai_skor'] !== null) {
                $hasil[$baris['nilai']] = (float) $baris['nilai_skor'];
            }
        }

        return $hasil;
    }

    /**
     * Menempelkan `rincian_kondisi` (peta kondisi ke jumlah unit) pada baris
     * aset ber-`jumlah` (Putaran 7).
     *
     * Sebelumnya satu baris `fasilitas_sp` / `inventaris_sp` hanya punya satu
     * `kondisi` meski `jumlah` > 1, sehingga "dua dari tiga pos lapuk" lolos
     * ke kolom `keterangan` sebagai kalimat, bukan data. Ini BUKAN pendataan
     * per unit (pos ke-2 masih tak dapat dibedakan dari pos ke-3), melainkan
     * histogram kondisi per jenis.
     *
     * `kondisi` TIDAK diturunkan darinya ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â ia tetap penilaian umum yang diketik
     * petugas (lencana daftar, cacah "perlu perbaikan"). Baris yang sudah
     * membawa `rincian_kondisi` literal dipakai apa adanya; sisanya diisi
     * `[kondisi => jumlah]`.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private static function denganRincianKondisi(array $rows): array
    {
        return array_map(function (array $r): array {
            $r['rincian_kondisi'] = $r['rincian_kondisi'] ?? [$r['kondisi'] => $r['jumlah']];

            return $r;
        }, $rows);
    }

    /**
     * Parameter penilaian kondisi SP, DIHASILKAN DARI DATA MASTER JENIS.
     *
     * BUKAN LAGI DAFTAR TULIS TANGAN, dan itu inti perbaikannya. Sebelumnya
     * `PenilaianKondisiSp::parameter()` memuat tiga belas baris yang ditulis
     * satu per satu, sehingga jenis infrastruktur atau fasilitas yang
     * ditambahkan Admin tidak pernah ikut dinilai: dropdownnya hidup, petugas
     * dapat mendata asetnya, tetapi skor SP tidak berubah sama sekali.
     *
     * Akibatnya sudah nyata pada data contoh. `Pendidikan Lanjutan`,
     * `Pasar atau Kios`, `Olahraga`, dan `Keamanan` tidak pernah masuk daftar,
     * sehingga POS KAMLING di SP Weain yang berkondisi Rusak Berat terdata
     * rapi, tampil di daftar fasilitas, dan tidak menyumbang apa pun pada
     * skornya. Bukan keputusan sadar, hanya daftar yang berhenti di baris ke
     * tiga belas.
     *
     * `sumber` DISIMPULKAN dari jenisnya, tidak diisi manual: jenis
     * infrastruktur selalu dibaca dari tabel `infrastruktur` kolom `jenis`,
     * jenis fasilitas dari `fasilitas_sp` kolom `jenis_fasilitas`. Menyimpannya
     * sebagai isian terpisah membuka peluang parameter menunjuk tabel yang
     * tidak memuat jenisnya.
     *
     * `is_dinilai` menjawab pertanyaan "mana yang masuk penilaian". Jenis baru
     * lahir dalam keadaan TIDAK dinilai, sebab menambah jenis adalah tindakan
     * pendataan sedangkan memasukkannya ke penilaian adalah keputusan
     * kebijakan. Menyatukan keduanya membuat skor seluruh SP turun hanya
     * karena Admin menambah satu pilihan dropdown, sebab penyebutnya bertambah.
     *
     * `Lainnya` tidak dinilai dan itu bukan pengecualian di dalam kode,
     * melainkan sekadar tidak dicentang. Ia keranjang penampung, bukan satu
     * jenis barang; menilai "ketersediaan Lainnya" berarti memberi nilai penuh
     * kepada SP yang memiliki satu benda tak jelas.
     *
     * @return array<int, array<string, mixed>> Parameter penilaian
     */
    public static function parameterPenilaian(): array
    {
        // Parameter yang berlaku, dipetakan dari nilai jenisnya. Yang tidak
        // tercantum di sini ada sebagai baris tetapi belum dinilai.
        //
        // `nama` sengaja boleh berbeda dari nama jenisnya: jenis menjawab
        // "aset ini apa", parameter menjawab "apa yang dinilai". Petugas
        // mendata aset berjenis `Gudang`, dinas menilai ketersediaan
        // `Gudang Pascapanen`.
        // Kunci larik: nilai jenis pada data master. Isi: kode, nama, tingkat.
        //
        // $kode DITULIS TETAP, tidak diturunkan dari nama jenisnya. Ia penunjuk
        // yang tersalin ke $penilaian_sp.rincian`, sehingga menurunkannya dari
        // teks jenis membuat penilaian lama kehilangan pasangannya begitu Admin
        // memperbaiki ejaan. Alasannya sama dengan $referensi_id` menggantikan
        // rujukan berbasis teks.
        $berlaku = [
            // Primer: tanpa ini tempat tidak layak dihuni.
            'Air' => ['air_bersih', 'Air Bersih', TingkatKebutuhan::Primer],
            'Jalan Penghubung' => ['jalan_penghubung', 'Jalan Penghubung', TingkatKebutuhan::Primer],
            'Listrik' => ['listrik', 'Listrik', TingkatKebutuhan::Primer],

            // Sekunder: masih dapat dihuni, tetapi tidak berkembang.
            'Kesehatan' => ['kesehatan', 'Fasilitas Kesehatan', TingkatKebutuhan::Sekunder],
            'Pendidikan Dasar' => ['pendidikan_dasar', 'Pendidikan Dasar', TingkatKebutuhan::Sekunder],
            'Telekomunikasi' => ['telekomunikasi', 'Telekomunikasi', TingkatKebutuhan::Sekunder],
            'Sanitasi' => ['sanitasi', 'Sanitasi', TingkatKebutuhan::Sekunder],

            // Tersier: penunjang produktivitas dan kehidupan sosial.
            'Irigasi' => ['irigasi', 'Irigasi', TingkatKebutuhan::Tersier],
            'Gudang' => ['gudang', 'Gudang Pascapanen', TingkatKebutuhan::Tersier],
            'Jalan Produksi' => ['jalan_produksi', 'Jalan Produksi', TingkatKebutuhan::Tersier],
            'Balai Pertemuan' => ['balai', 'Balai Pertemuan', TingkatKebutuhan::Tersier],
            'Ibadah' => ['ibadah', 'Rumah Ibadah', TingkatKebutuhan::Tersier],
            'Pasar atau Kios Saprotan' => ['pasar_kios', 'Pasar atau Kios Saprotan', TingkatKebutuhan::Tersier],

            // Empat yang dahulu terlewat. POS KAMLING berjenis Keamanan
            // terdata sejak awal tetapi tidak pernah ikut dihitung.
            'Pendidikan Lanjutan' => ['pendidikan_lanjutan', 'Pendidikan Lanjutan', TingkatKebutuhan::Tersier],
            'Pasar atau Kios' => ['pasar_kios_fasilitas', 'Pasar atau Kios', TingkatKebutuhan::Tersier],
            'Olahraga' => ['olahraga', 'Sarana Olahraga', TingkatKebutuhan::Tersier],
            'Keamanan' => ['keamanan', 'Sarana Keamanan', TingkatKebutuhan::Tersier],
        ];

        $hasil = [];
        $id = 1;

        foreach ([JenisReferensi::JenisInfrastruktur, JenisReferensi::JenisFasilitas] as $jenis) {
            $sumber = $jenis === JenisReferensi::JenisFasilitas ? 'Fasilitas' : 'Infrastruktur';

            foreach (self::referensi($jenis) as $urutan => $baris) {
                $cocok = $berlaku[$baris['nilai']] ?? null;

                $hasil[] = [
                    'id_parameter_penilaian_sp' => $id++,
                    'kode' => $cocok[0] ?? Str::slug($baris['nilai'], '_'),
                    'nama' => $cocok[1] ?? $baris['nilai'],
                    'tingkat' => $cocok[2] ?? TingkatKebutuhan::Tersier,
                    'bobot' => ($cocok[2] ?? TingkatKebutuhan::Tersier)->bobotBawaan(),
                    'sumber' => $sumber,
                    'jenis' => $jenis->value,
                    'referensi_id' => $baris['id_referensi'],
                    'nilai_jenis' => $baris['nilai'],
                    'is_dinilai' => $cocok !== null,
                    'urutan' => $urutan + 1,
                ];
            }
        }

        return $hasil;
    }

    /**
     * Parameter yang benar-benar ikut dihitung, terurut.
     *
     * Dipisah dari parameterPenilaian() sebab halaman pengaturan perlu melihat
     * SELURUH jenis termasuk yang belum dinilai, sedangkan penghitung skor
     * hanya boleh membaca yang dicentang.
     *
     * @return array<int, array<string, mixed>> Parameter yang dinilai
     */
    public static function parameterDinilai(): array
    {
        return array_values(array_filter(
            self::parameterPenilaian(),
            fn ($p) => $p['is_dinilai']
        ));
    }

    /**
     * Ambang dan wording status kondisi SP, dapat disunting dinas.
     *
     * ENUM TETAP MENJADI KUNCI PERILAKU, hanya teks tampil dan ambangnya yang
     * berupa data. Pola ini sama dengan bidang pengaduan: `dariSkor()` wajib
     * mengembalikan salah satu dari tiga case, sehingga statusnya TIDAK dapat
     * ditambah maupun dihapus lewat antarmuka. Yang bebas ditentukan dinas
     * adalah namanya, sebab tiap dinas punya istilah sendiri.
     *
     * Status juga BUKAN pilihan melainkan kesimpulan: tidak ada satu pun form
     * yang menyuruh petugas memilihnya, ia selalu hasil hitungan. Itu sebabnya
     * ia tidak ikut menjadi baris pada data master referensi, yang seluruh
     * isinya adalah pilihan pada dropdown.
     *
     * `ambang_bawah` pada status terendah bernilai 0 dan tidak disunting: ia
     * penampung sisa, sehingga tidak ada skor yang jatuh tanpa status.
     *
     * Warna tidak ikut disunting. Hijau, kuning, dan merah terikat makna
     * urutan keparahan, bukan selera; menukarnya membuat rekap dashboard
     * terbaca terbalik.
     *
     * @return array<int, array<string, mixed>> Status beserta ambangnya
     */
    public static function statusKondisiSp(): array
    {
        return [
            [
                'kode' => StatusKondisiSp::Mandiri->value,
                'nama' => 'Mandiri',
                'keterangan' => 'Seluruh layanan dasar tersedia dan berfungsi baik',
                'ambang_bawah' => 80,
                'warna' => 'success',
                'urutan' => 1,
            ],
            [
                'kode' => StatusKondisiSp::Berkembang->value,
                'nama' => 'Berkembang',
                'keterangan' => 'Sebagian layanan tersedia, ada yang perlu diperbaiki',
                'ambang_bawah' => 55,
                'warna' => 'warning',
                'urutan' => 2,
            ],
            [
                'kode' => StatusKondisiSp::PerluPenanganan->value,
                'nama' => 'Perlu Penanganan',
                'keterangan' => 'Ada layanan dasar yang belum tersedia atau tidak berfungsi',
                'ambang_bawah' => 0,
                'warna' => 'error',
                'urutan' => 3,
            ],
        ];
    }

    /**
     * Satu baris status kondisi SP berdasarkan kodenya.
     *
     * @param  string  $kode  Nilai enum StatusKondisiSp
     * @return array<string, mixed>|null Baris status, null bila tidak ada
     */
    public static function statusKondisiSpDari(string $kode): ?array
    {
        foreach (self::statusKondisiSp() as $baris) {
            if ($baris['kode'] === $kode) {
                return $baris;
            }
        }

        return null;
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
            // Satuan non-berat untuk saprotan cair / gulungan (Task 6.7).
            // `faktor_ke_ton` NULL: tak dikonversi ke ton, tak masuk rekap panen.
            ['id_satuan' => 4, 'nama' => 'Liter', 'simbol' => 'L', 'faktor_ke_ton' => null, 'dipakai_komoditas' => 0],
            ['id_satuan' => 5, 'nama' => 'Rol', 'simbol' => 'rol', 'faktor_ke_ton' => null, 'dipakai_komoditas' => 0],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Kelembagaan dan sarana pertanian
    |--------------------------------------------------------------------------
    */

    /**
     * Rekap luas lahan dan titik koordinat milik satu keluarga.
     *
     * DITURUNKAN, TIDAK DISIMPAN. Luas lahan ketua maupun anggota poktan tidak
     * pernah menjadi kolom pada `poktan` atau `anggota_poktan`, sebab nilainya
     * akan basi begitu petugas membetulkan luas di modul lahan. Kekeliruan
     * yang sama pernah terjadi pada `poktan.jumlah_anggota` (erd.md 7.3).
     *
     * Hanya lahan USAHA yang dihitung: lahan pekarangan tidak memiliki
     * komposisi kering dan basah (rules.md 7.5), dan poktan mengurus usaha
     * pertanian bukan pekarangan. Koordinatnya pun koordinat lahan usaha,
     * sebab itulah bidang yang digarap bersama kelompok.
     *
     * Dipusatkan di sini, bukan ditulis ulang di tiap view: penjumlahan luas
     * yang tersebar pernah kehilangan sebagian bidang tanpa ada yang
     * menyadarinya (agents/notes.md butir 2026-08-18).
     *
     * @param  int|null  $transmigranId  Keluarga yang dihitung; null menghasilkan rekap kosong
     * @return array{kering: float, basah: float, total: float, lintang: float|null, bujur: float|null, jumlah_bidang: int}
     */
    public static function rekapLahanKeluarga(?int $transmigranId): array
    {
        $kosong = ['kering' => 0.0, 'basah' => 0.0, 'total' => 0.0, 'lintang' => null, 'bujur' => null, 'jumlah_bidang' => 0];

        if ($transmigranId === null) {
            return $kosong;
        }

        /*
            Satu keluarga tepat satu baris sejak Putaran 15, sehingga tidak ada
            lagi yang perlu dijumlahkan antar baris: cukup dibaca. Penyaring
            peruntukan pun tidak diperlukan lagi, sebab lahan usaha kini kolom
            tersendiri dan tidak mungkin tertukar dengan pekarangan.
        */
        $baris = collect(self::lahan())->firstWhere('transmigran_id', $transmigranId);

        // Keluarga yang belum menerima lahan usaha dihitung kosong, bukan nol
        // paksa: `luas_usaha` null berarti belum menerima (rules.md 7.5a).
        if ($baris === null || $baris['luas_usaha'] === null) {
            return $kosong;
        }

        return [
            'kering' => round((float) ($baris['luas_kering'] ?? 0), 2),
            'basah' => round((float) ($baris['luas_basah'] ?? 0), 2),
            'total' => round((float) $baris['luas_usaha'], 2),
            // Koordinat lahan USAHA, bukan pekarangan: inilah bidang yang
            // digarap bersama kelompok.
            'lintang' => $baris['lintang_usaha'] ?? null,
            'bujur' => $baris['bujur_usaha'] ?? null,
            // Selalu 1 bila keluarganya memegang lahan usaha. Dipertahankan
            // agar pemanggil yang menampilkan jumlah bidang tidak perlu diubah.
            'jumlah_bidang' => 1,
        ];
    }

    /**
     * Kelompok tani beserta profil ketuanya.
     *
     * KETUA PUNYA TIGA ASAL-USUL, bukan dua (rules.md 7a poin 2a). Kolom
     * `is_ketua_transmigran` bertipe boolean digantikan `asal_ketua` bertipe
     * enum, sebab boolean hanya sanggup membedakan dua keadaan sedangkan
     * keadaan lapangan ada tiga. Pada jalur `Kepala Keluarga`, nama dan NIK
     * dibiarkan null dan dibaca lewat relasi agar tidak ada dua versi data.
     *
     * @return array<int, array<string, mixed>> Data poktan
     */
    public static function poktan(): array
    {
        $data = [
            ['id_poktan' => 1, 'nama' => 'POKTAN MEKAR JAYA', 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'asal_ketua' => AsalWakilPoktan::KepalaKeluarga->value, 'ketua_transmigran_id' => 1, 'ketua_anggota_keluarga_id' => null, 'nama_ketua' => null, 'nik_ketua' => null, 'hubungan_ketua' => null, 'telepon_ketua' => '081234567801', 'email_ketua' => 'yohanes.bere@example.id', 'alamat_ketua' => 'RT 02 RW 01, SP Kapitan Meo', 'tahun_berdiri' => 2016, 'jumlah_anggota' => 24, 'luas_kering_ketua' => null, 'luas_basah_ketua' => null, 'lintang' => -9.5127800, 'bujur' => 124.9131400, 'keterangan' => 'Kelompok aktif mengikuti pelatihan penyuluh setiap musim.', 'berkas_id' => 13],
            ['id_poktan' => 2, 'nama' => 'POKTAN SUBUR MAKMUR', 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'asal_ketua' => AsalWakilPoktan::KepalaKeluarga->value, 'ketua_transmigran_id' => 2, 'ketua_anggota_keluarga_id' => null, 'nama_ketua' => null, 'nik_ketua' => null, 'hubungan_ketua' => null, 'telepon_ketua' => '081234567802', 'email_ketua' => null, 'alamat_ketua' => null, 'tahun_berdiri' => 2017, 'jumlah_anggota' => 18, 'luas_kering_ketua' => null, 'luas_basah_ketua' => null, 'lintang' => -9.5476500, 'bujur' => 124.8882300],
            // Ketua diwakili anggota keluarga, bukan kepala keluarganya.
            // Keadaan lapangannya: kepala keluarga PETRUS NAHAK merantau,
            // sehingga istrinya yang menggarap dan memimpin kelompok. FK
            // keluarga TETAP terisi, sebab yang ditunjuk adalah keluarganya.
            // Sejak Stage B2 identitas ketua DIPILIH dari daftar anggota
            // keluarga (`ketua_anggota_keluarga_id` = 8, YOVITA NAHAK SERAN),
            // tidak diketik. Luas lahan tetap terbaca dari bidang milik
            // keluarga PETRUS NAHAK.
            ['id_poktan' => 3, 'nama' => 'POKTAN TANI BERSATU', 'satuan_permukiman_id' => 2, 'satuan_permukiman' => 'SP Tniumanu', 'asal_ketua' => AsalWakilPoktan::AnggotaKeluarga->value, 'ketua_transmigran_id' => 3, 'ketua_anggota_keluarga_id' => 8, 'nama_ketua' => null, 'nik_ketua' => null, 'hubungan_ketua' => null, 'telepon_ketua' => '081234567803', 'email_ketua' => null, 'alamat_ketua' => null, 'tahun_berdiri' => 2017, 'jumlah_anggota' => 21, 'luas_kering_ketua' => null, 'luas_basah_ketua' => null, 'lintang' => -9.4988700, 'bujur' => 124.9425600],
            // Ketua bukan transmigran, melainkan penduduk setempat. Satu-satunya
            // jalur yang mengetik luas lahannya sendiri, sebab bidangnya memang
            // tidak terdata pada tabel lahan.
            ['id_poktan' => 4, 'nama' => 'POKTAN HARAPAN BARU', 'satuan_permukiman_id' => 6, 'satuan_permukiman' => 'SP Weain', 'asal_ketua' => AsalWakilPoktan::BukanTransmigran->value, 'ketua_transmigran_id' => null, 'ketua_anggota_keluarga_id' => null, 'nama_ketua' => 'YOSEPH KLAU', 'nik_ketua' => '5321010207700099', 'hubungan_ketua' => null, 'telepon_ketua' => '081234567890', 'email_ketua' => null, 'alamat_ketua' => 'Desa Weain, Kobalima Timur', 'tahun_berdiri' => 2019, 'jumlah_anggota' => 15, 'luas_kering_ketua' => 0.80, 'luas_basah_ketua' => 0.20, 'lintang' => -9.5731200, 'bujur' => 124.8654900],
        ];

        // Nama berkas dibaca dari registry lewat FK langsung (Putaran 12):
        // baris poktan menyimpan $berkas_id, bukan lagi path berkasnya.
        $data = array_map(function (array $p): array {
            $berkas = self::cariBerkas($p['berkas_id'] ?? null);

            return $p + ['dokumen_pendukung' => $berkas['nama_file'] ?? null, 'dokumen_pendukung_meta' => $berkas];
        }, $data);

        // Nama, NIK, dan hubungan ketua DISELESAIKAN di sini lewat identitasWakil,
        // agar setiap tempat yang menampilkannya (daftar, rincian, laporan)
        // membaca nilai yang sama tanpa mengulang percabangan tiga jalur.
        // Jalur Kepala Keluarga membacanya dari relasi transmigran; jalur
        // Anggota Keluarga dari baris anggota_keluarga (Stage B2); jalur Bukan
        // Transmigran memakai nilai yang diketik.
        return array_map(function (array $p): array {
            $identitas = self::identitasWakil($p, 'ketua');

            return array_merge($p, [
                'nama_ketua' => $identitas['nama'],
                'nik_ketua' => $identitas['nik'] === '-' ? null : $identitas['nik'],
                'hubungan_ketua' => $identitas['hubungan'],
            ]);
        }, $data);
    }

    /**
     * Identitas wakil sebuah keluarga di poktan, dari jalur mana pun.
     *
     * Menyatukan pembacaan nama, NIK, dan telepon yang jalurnya bercabang tiga.
     * Tanpa ini, setiap tempat yang menampilkan nama wakil harus mengulang
     * percabangan yang sama, dan satu di antaranya pasti terlewat saat aturannya
     * berubah.
     *
     * Sejak Stage B2 (2026-08-28), jalur `Anggota Keluarga` tidak lagi
     * mengetik nama dan NIK: bila `anggota_keluarga_id` (atau
     * `ketua_anggota_keluarga_id`) terisi, keduanya beserta telepon dan
     * hubungan dibaca dari baris `anggota_keluarga`. Nilai yang diketik hanya
     * dipakai sebagai cadangan bagi data lama yang belum menunjuk id.
     *
     * @param  array<string, mixed>  $baris  Baris poktan atau anggota_poktan
     * @param  string  $awalan  `ketua` untuk poktan, `wakil` untuk anggota
     * @return array{nama: string, nik: string, telepon: string|null, hubungan: string|null, asal: AsalWakilPoktan}
     */
    public static function identitasWakil(array $baris, string $awalan): array
    {
        $asal = AsalWakilPoktan::from($baris["asal_{$awalan}"] ?? $baris['asal_wakil'] ?? $baris['asal_ketua']);
        $keluarga = self::cariTransmigran($baris['ketua_transmigran_id'] ?? $baris['transmigran_id'] ?? null);

        // Jalur Kepala Keluarga membaca lewat relasi keluarga.
        if ($asal->identitasDariRelasi() && $keluarga !== null) {
            return [
                'nama' => $keluarga['nama_kepala_keluarga'],
                'nik' => $keluarga['nik'],
                'telepon' => $baris["telepon_{$awalan}"] ?? $keluarga['telepon'] ?? null,
                'hubungan' => null,
                'asal' => $asal,
            ];
        }

        // Jalur Anggota Keluarga yang sudah menunjuk baris anggota_keluarga.
        $anggota = self::cariAnggotaKeluarga(
            $baris['anggota_keluarga_id'] ?? $baris['ketua_anggota_keluarga_id'] ?? null
        );

        if ($asal === AsalWakilPoktan::AnggotaKeluarga && $anggota !== null) {
            return [
                'nama' => $anggota['nama_lengkap'],
                'nik' => $anggota['nik'] ?? '-',
                'telepon' => $anggota['telepon'] ?? ($keluarga['telepon'] ?? null),
                'hubungan' => $anggota['hubungan'],
                'asal' => $asal,
            ];
        }

        // Cadangan: jalur Bukan Transmigran, atau Anggota Keluarga data lama
        // yang identitasnya masih diketik.
        return [
            'nama' => $baris["nama_{$awalan}"] ?? '-',
            'nik' => $baris["nik_{$awalan}"] ?? '-',
            'telepon' => $baris["telepon_{$awalan}"] ?? null,
            'hubungan' => $baris['hubungan_dengan_kk'] ?? $baris['hubungan_ketua'] ?? null,
            'asal' => $asal,
        ];
    }

    /**
     * Mencari satu keluarga transmigran menurut idnya.
     *
     * @param  int|null  $id  Id transmigran
     * @return array<string, mixed>|null Baris transmigran, atau null
     */
    public static function cariTransmigran(?int $id): ?array
    {
        if ($id === null) {
            return null;
        }

        foreach (self::transmigran() as $baris) {
            if ($baris['id_transmigran'] === $id) {
                return $baris;
            }
        }

        return null;
    }

    /**
     * Anggota kelompok tani.
     *
     * Anggota yang berhenti ditandai berstatus Sudah Keluar, bukan dihapus,
     * agar riwayat tetap utuh (agents/rules.md bagian 5.1 catatan 5).
     *
     * KEANGGOTAAN MELEKAT PADA KELUARGA, BUKAN PADA KEPALA KELUARGA
     * (rules.md 7a poin 3a). `transmigran_id` menunjuk keluarga yang diwakili,
     * sedangkan `asal_wakil` menyatakan siapa wakilnya. Kunci `nama` dan `nik`
     * adalah nilai TAMPILAN hasil percabangan itu, disiapkan di sini agar view
     * tidak perlu mengulang percabangan yang sama di banyak tempat.
     *
     * @param  int|null  $poktanId  Menyaring anggota satu poktan; null berarti seluruhnya
     * @return array<int, array<string, mixed>> Data anggota
     */
    public static function anggotaPoktan(?int $poktanId = null): array
    {
        $kk = AsalWakilPoktan::KepalaKeluarga->value;
        $anggotaKeluarga = AsalWakilPoktan::AnggotaKeluarga->value;

        $data = [
            ['id_anggota_poktan' => 1, 'poktan_id' => 1, 'poktan' => 'POKTAN MEKAR JAYA', 'transmigran_id' => 1, 'asal_wakil' => $kk, 'nama_wakil' => null, 'nik_wakil' => null, 'telepon_wakil' => null, 'hubungan_dengan_kk' => null, 'jabatan' => 'Anggota', 'tanggal_masuk' => '2016-08-01', 'tanggal_keluar' => null, 'status' => 'Aktif', 'alasan_keluar' => null, 'keterangan' => null],
            ['id_anggota_poktan' => 2, 'poktan_id' => 1, 'poktan' => 'POKTAN MEKAR JAYA', 'transmigran_id' => 2, 'asal_wakil' => $kk, 'nama_wakil' => null, 'nik_wakil' => null, 'telepon_wakil' => null, 'hubungan_dengan_kk' => null, 'jabatan' => 'Sekretaris', 'tanggal_masuk' => '2016-08-01', 'tanggal_keluar' => null, 'status' => 'Aktif', 'alasan_keluar' => null, 'keterangan' => null],
            // Diwakili adik kepala keluarga, sebab YULITA HOAR mengurus dua
            // anak kecil. Sejak Stage B2 identitasnya DIPILIH dari daftar
            // anggota keluarga (`anggota_keluarga_id` = 30), tidak diketik;
            // nama, NIK, telepon, dan hubungan dibaca dari baris itu. Luas
            // lahan tetap terbaca dari bidang milik keluarga YULITA HOAR.
            ['id_anggota_poktan' => 3, 'poktan_id' => 1, 'poktan' => 'POKTAN MEKAR JAYA', 'transmigran_id' => 8, 'asal_wakil' => $anggotaKeluarga, 'anggota_keluarga_id' => 30, 'nama_wakil' => null, 'nik_wakil' => null, 'telepon_wakil' => null, 'hubungan_dengan_kk' => null, 'jabatan' => 'Anggota', 'tanggal_masuk' => '2019-03-12', 'tanggal_keluar' => null, 'status' => 'Aktif', 'alasan_keluar' => null, 'keterangan' => 'Mewakili keluarga sejak 2019.'],
            ['id_anggota_poktan' => 4, 'poktan_id' => 1, 'poktan' => 'POKTAN MEKAR JAYA', 'transmigran_id' => 5, 'asal_wakil' => $kk, 'nama_wakil' => null, 'nik_wakil' => null, 'telepon_wakil' => null, 'hubungan_dengan_kk' => null, 'jabatan' => 'Anggota', 'tanggal_masuk' => '2017-05-20', 'tanggal_keluar' => '2025-09-30', 'status' => 'Sudah Keluar', 'alasan_keluar' => 'Pindah mengikuti keluarga ke SP Weoe.', 'keterangan' => null],
            ['id_anggota_poktan' => 5, 'poktan_id' => 3, 'poktan' => 'POKTAN TANI BERSATU', 'transmigran_id' => 3, 'asal_wakil' => $kk, 'nama_wakil' => null, 'nik_wakil' => null, 'telepon_wakil' => null, 'hubungan_dengan_kk' => null, 'jabatan' => 'Anggota', 'tanggal_masuk' => '2017-02-15', 'tanggal_keluar' => null, 'status' => 'Aktif', 'alasan_keluar' => null, 'keterangan' => null],
            ['id_anggota_poktan' => 6, 'poktan_id' => 4, 'poktan' => 'POKTAN HARAPAN BARU', 'transmigran_id' => 7, 'asal_wakil' => $kk, 'nama_wakil' => null, 'nik_wakil' => null, 'telepon_wakil' => null, 'hubungan_dengan_kk' => null, 'jabatan' => 'Anggota', 'tanggal_masuk' => '2019-01-10', 'tanggal_keluar' => null, 'status' => 'Aktif', 'alasan_keluar' => null, 'keterangan' => null],
            ['id_anggota_poktan' => 7, 'poktan_id' => 4, 'poktan' => 'POKTAN HARAPAN BARU', 'transmigran_id' => 6, 'asal_wakil' => $kk, 'nama_wakil' => null, 'nik_wakil' => null, 'telepon_wakil' => null, 'hubungan_dengan_kk' => null, 'jabatan' => 'Bendahara', 'tanggal_masuk' => '2019-01-10', 'tanggal_keluar' => null, 'status' => 'Tidak Aktif', 'alasan_keluar' => null, 'keterangan' => null],
        ];

        // Nama, NIK, telepon, dan rekap lahan disiapkan di sini agar view tidak
        // mengulang percabangan tiga jalur di setiap tempat yang menampilkannya.
        $data = array_map(function (array $baris): array {
            $identitas = self::identitasWakil($baris, 'wakil');
            $lahan = self::rekapLahanKeluarga($baris['transmigran_id']);

            return $baris + [
                // Kolom sejak Stage B2; baris lama tidak menunjuk anggota.
                'anggota_keluarga_id' => $baris['anggota_keluarga_id'] ?? null,
                'nama' => $identitas['nama'],
                'nik' => $identitas['nik'],
                'telepon' => $identitas['telepon'],
                'hubungan_wakil' => $identitas['hubungan'],
                'luas_kering' => $lahan['kering'],
                'luas_basah' => $lahan['basah'],
                'lintang' => $lahan['lintang'],
                'bujur' => $lahan['bujur'],
            ];
        }, $data);

        if ($poktanId === null) {
            return $data;
        }

        return array_values(array_filter($data, fn ($b) => $b['poktan_id'] === $poktanId));
    }

    /**
     * Rekap kekuatan satu poktan: cacah anggota aktif dan luas lahannya.
     *
     * SELURUHNYA DIHITUNG, tidak satu pun disimpan. Angka yang ditulis tangan
     * menjadi basi begitu satu anggota keluar atau satu bidang lahan
     * dibetulkan, dan kebasian itu tidak pernah memerahkan apa pun. Kolom
     * `luas_lahan_kelompok` sudah dicabut 2026-08-20 karena persis alasan itu
     * (agents/erd.md 7.3).
     *
     * Luas lahan menjumlahkan bidang milik ketua DAN seluruh anggota aktif.
     * Ketua diperlakukan terpisah sebab ia belum tentu terdaftar sebagai
     * anggota: pada jalur `Bukan Transmigran` lahannya bahkan tidak terdata
     * pada modul Lahan sehingga nilainya diketik pada profil poktan.
     *
     * Hanya anggota berstatus Aktif yang dihitung. Anggota yang sudah keluar
     * tetap tersimpan pada riwayat keanggotaan, tetapi lahannya tidak lagi
     * digarap kelompok ini.
     *
     * @param  int  $poktanId  Nilai id_poktan
     * @return array{jumlah_anggota: int, luas_kering: float, luas_basah: float, luas_total: float}
     */
    public static function rekapLahanPoktan(int $poktanId): array
    {
        $kosong = ['jumlah_anggota' => 0, 'luas_kering' => 0.0, 'luas_basah' => 0.0, 'luas_total' => 0.0];

        $poktan = collect(self::poktan())->firstWhere('id_poktan', $poktanId);

        if ($poktan === null) {
            return $kosong;
        }

        $anggotaAktif = array_values(array_filter(
            self::anggotaPoktan($poktanId),
            fn ($a) => $a['status'] === 'Aktif',
        ));

        $kering = array_sum(array_column($anggotaAktif, 'luas_kering'));
        $basah = array_sum(array_column($anggotaAktif, 'luas_basah'));

        // Lahan ketua. Dua jalur pertama membacanya dari bidang milik
        // keluarganya; jalur `Bukan Transmigran` memakai nilai yang diketik
        // pada profil poktan, sebab lahannya memang tidak terdata.
        $asalKetua = AsalWakilPoktan::tryFrom($poktan['asal_ketua'] ?? '');

        $lahanKetua = $asalKetua !== null && $asalKetua->dariKeluargaTransmigran()
            ? self::rekapLahanKeluarga($poktan['ketua_transmigran_id'] ?? null)
            : ['kering' => (float) ($poktan['luas_kering_ketua'] ?? 0), 'basah' => (float) ($poktan['luas_basah_ketua'] ?? 0)];

        // Ketua yang JUGA terdaftar sebagai anggota tidak dihitung dua kali.
        $ketuaSudahTerhitung = collect($anggotaAktif)
            ->contains('transmigran_id', $poktan['ketua_transmigran_id'] ?? null);

        if (! $ketuaSudahTerhitung) {
            $kering += $lahanKetua['kering'];
            $basah += $lahanKetua['basah'];
        }

        return [
            // Ketua ikut dihitung sebagai orang meski tidak terdaftar pada
            // tabel keanggotaan: ia tetap menggarap lahannya sendiri.
            'jumlah_anggota' => count($anggotaAktif) + ($ketuaSudahTerhitung ? 0 : 1),
            'luas_kering' => round($kering, 2),
            'luas_basah' => round($basah, 2),
            'luas_total' => round($kering + $basah, 2),
        ];
    }

    /**
     * Alat dan mesin pertanian, satu baris per PENGADAAN (induk).
     *
     * Diubah 2026-08-30 (Putaran 7): satu batch pengadaan (mis. 4 traktor
     * anggaran APBN 2018) lazim dibagikan ke BEBERAPA poktan, bahkan lintas
     * SP. Model lama membawa satu `poktan_id` pada baris ini, sehingga satu
     * batch harus diketik ulang per poktan menjadi baris-baris terpisah yang
     * tidak saling tahu dan dapat berselisih diam-diam.
     *
     * Kini baris ini hanya mendeskripsikan BENDAnya: `jenis_alsintan` (data
     * master, Ãƒâ€šÃ‚Â§11.37), `nama_alat`, `jumlah_total`, `tahun_pengadaan`,
     * `sumber_dana`. Poktan penerima, jumlah per poktan, kondisi per poktan,
     * penanda tangan, dan tanggal serah pindah ke `alsintanDistribusi()`.
     * `distribusi[]`, `jumlah_tersalur`, `jumlah_belum_tersalur`, dan
     * `ringkasan_kondisi` ditempel di sini sebagai turunan.
     *
     * Pengadaan yang belum dibagikan ke satu poktan pun tetap tercatat
     * (barang di gudang UPT); `distribusi` kosong dan seluruh jumlahnya
     * terhitung "belum tersalurkan".
     *
     * @return array<int, array<string, mixed>> Data pengadaan alsintan
     */
    public static function alsintan(): array
    {
        $data = [
            // Satu batch traktor dibagikan ke tiga poktan di tiga SP berbeda.
            // Inilah kasus yang model lama memaksa diketik jadi tiga baris.
            ['id_alsintan' => 1, 'jenis_alsintan' => 'Traktor Roda Dua', 'nama_alat' => 'TRAKTOR RODA DUA KUBOTA', 'jumlah_total' => 4, 'tahun_pengadaan' => 2018, 'sumber_dana' => 'APBN', 'keterangan' => 'Bantuan mekanisasi lahan kering, dibagi rata tiga poktan.'],
            ['id_alsintan' => 2, 'jenis_alsintan' => 'Pompa Air', 'nama_alat' => 'POMPA AIR 3 INCI', 'jumlah_total' => 3, 'tahun_pengadaan' => 2019, 'sumber_dana' => 'APBD Kabupaten', 'keterangan' => null, 'dokumen_pendukung' => null],
            ['id_alsintan' => 3, 'jenis_alsintan' => 'Hand Sprayer', 'nama_alat' => 'HAND SPRAYER', 'jumlah_total' => 2, 'tahun_pengadaan' => 2021, 'sumber_dana' => 'Swadaya', 'keterangan' => 'Dibeli dari iuran anggota dua kelompok.', 'dokumen_pendukung' => null],
            // BELUM TERSALURKAN, satu-satunya pada data contoh. Barang sudah
            // di gudang UPT, pembagian belum diputuskan. Sengaja ada agar
            // keadaan itu punya benda nyata untuk dilihat dan diuji.
            ['id_alsintan' => 4, 'jenis_alsintan' => 'Mesin Perontok', 'nama_alat' => 'MESIN PERONTOK JAGUNG', 'jumlah_total' => 2, 'tahun_pengadaan' => 2020, 'sumber_dana' => 'Dinas Pertanian Kabupaten', 'keterangan' => 'Menunggu penetapan poktan penerima.', 'dokumen_pendukung' => null],
            ['id_alsintan' => 5, 'jenis_alsintan' => 'Lainnya', 'nama_alat' => 'CANGKUL', 'jumlah_total' => 8, 'tahun_pengadaan' => 2019, 'sumber_dana' => 'Swadaya', 'keterangan' => null, 'dokumen_pendukung' => null],
        ];

        $data = self::lekatkanBerkas($data, 'alsintan_berkas', 'alsintan_id', 'id_alsintan', ['foto' => 'foto', 'pendukung' => 'dokumen_pendukung']);

        $distribusiPer = [];
        foreach (self::alsintanDistribusi() as $d) {
            $distribusiPer[$d['alsintan_id']][] = $d;
        }

        return array_map(function (array $baris) use ($distribusiPer): array {
            $dist = $distribusiPer[$baris['id_alsintan']] ?? [];
            $tersalur = array_sum(array_column($dist, 'jumlah'));

            $ringkasan = [];
            foreach ($dist as $d) {
                $ringkasan[$d['kondisi']] = ($ringkasan[$d['kondisi']] ?? 0) + $d['jumlah'];
            }

            return $baris + [
                'distribusi' => $dist,
                'jumlah_tersalur' => $tersalur,
                'jumlah_belum_tersalur' => max(0, $baris['jumlah_total'] - $tersalur),
                'ringkasan_kondisi' => $ringkasan,
                'poktan_penerima' => array_values(array_unique(array_column($dist, 'poktan'))),
            ];
        }, $data);
    }

    /**
     * Distribusi pengadaan alsintan ke poktan, satu baris per poktan penerima.
     *
     * Ditambahkan 2026-08-30 (Putaran 7). `kondisi` melekat di sini, bukan di
     * induk, sebab kondisi diamati per unit di lapangan: traktor di satu poktan
     * dapat rusak berat sementara yang di poktan lain masih baik. `poktan_id`,
     * `satuan_permukiman_id`, dan `satuan_permukiman` MENGIKUTI poktan
     * (rules.md Ãƒâ€šÃ‚Â§7b poin 3), tidak dipilih terpisah. `penanda_terima` dan
     * `poktan` ditempel sebagai turunan.
     *
     * @return array<int, array<string, mixed>> Data distribusi alsintan
     */
    public static function alsintanDistribusi(): array
    {
        $data = [
            ['id_alsintan_distribusi' => 1, 'alsintan_id' => 1, 'poktan_id' => 1, 'jumlah' => 2, 'kondisi' => 'Baik', 'penanda_terima_id' => 1, 'tanggal_serah' => '2018-11-20', 'foto_berkas_id' => 15, 'keterangan' => 'Servis berkala terakhir Maret 2026.'],
            ['id_alsintan_distribusi' => 2, 'alsintan_id' => 1, 'poktan_id' => 3, 'jumlah' => 1, 'kondisi' => 'Rusak Ringan', 'penanda_terima_id' => 5, 'tanggal_serah' => '2018-11-22', 'foto' => null, 'keterangan' => 'Kopling mulai selip, sudah diajukan perbaikan.'],
            ['id_alsintan_distribusi' => 3, 'alsintan_id' => 1, 'poktan_id' => 4, 'jumlah' => 1, 'kondisi' => 'Baik', 'penanda_terima_id' => 6, 'tanggal_serah' => '2018-12-05', 'foto' => null, 'keterangan' => null],
            ['id_alsintan_distribusi' => 4, 'alsintan_id' => 2, 'poktan_id' => 1, 'jumlah' => 3, 'kondisi' => 'Rusak Ringan', 'penanda_terima_id' => 2, 'tanggal_serah' => '2019-07-14', 'foto' => null, 'keterangan' => null],
            ['id_alsintan_distribusi' => 5, 'alsintan_id' => 3, 'poktan_id' => 1, 'jumlah' => 1, 'kondisi' => 'Baik', 'penanda_terima_id' => null, 'tanggal_serah' => null, 'foto' => null, 'keterangan' => null],
            ['id_alsintan_distribusi' => 6, 'alsintan_id' => 3, 'poktan_id' => 3, 'jumlah' => 1, 'kondisi' => 'Baik', 'penanda_terima_id' => 5, 'tanggal_serah' => '2021-05-03', 'foto' => null, 'keterangan' => null],
            ['id_alsintan_distribusi' => 7, 'alsintan_id' => 5, 'poktan_id' => 4, 'jumlah' => 8, 'kondisi' => 'Baik', 'penanda_terima_id' => 6, 'tanggal_serah' => '2019-02-18', 'foto' => null, 'keterangan' => null],
        ];

        $namaAnggota = [];
        $poktanAnggota = [];
        foreach (self::anggotaPoktan() as $a) {
            $namaAnggota[$a['id_anggota_poktan']] = $a['nama'];
            $poktanAnggota[$a['id_anggota_poktan']] = $a['poktan_id'];
        }

        $poktanPeta = [];
        foreach (self::poktan() as $p) {
            $poktanPeta[$p['id_poktan']] = $p;
        }

        return array_map(function (array $d) use ($namaAnggota, $poktanPeta): array {
            $p = $poktanPeta[$d['poktan_id']] ?? null;
            $pt = $d['penanda_terima_id'] ?? null;

            return $d + [
                'foto' => self::cariBerkas($d['foto_berkas_id'] ?? null)['nama_file'] ?? null,
                'foto_meta' => self::cariBerkas($d['foto_berkas_id'] ?? null),
                'poktan' => $p['nama'] ?? null,
                'satuan_permukiman_id' => $p['satuan_permukiman_id'] ?? null,
                'satuan_permukiman' => $p['satuan_permukiman'] ?? null,
                'penanda_terima' => $pt === null ? null : ($namaAnggota[$pt] ?? null),
            ];
        }, $data);
    }

    /**
     * Pengadaan sarana produksi pertanian, satu baris per PENGADAAN (induk).
     *
     * Diubah 2026-08-30 (Putaran 7): satu batch bantuan (mis. 250 kg benih
     * jagung anggaran Dinas 2025) lazim dibagikan ke BEBERAPA poktan. Model
     * lama membawa satu `poktan_id` pada baris ini, sehingga satu batch harus
     * diketik ulang per poktan menjadi baris-baris terpisah yang tidak saling
     * tahu; sisa benih pun tak terdefinisi bila jatah satu poktan tergerus
     * penanaman poktan lain.
     *
     * Kini baris ini mendeskripsikan BENDAnya: `jenis`, `nama`, `komoditas_id`
     * (WAJIB bila Benih, kosong bagi jenis lain), `varietas` (idem),
     * `jadwal_tanam`, `jumlah_total`, `satuan_id`, `tahun_pengadaan` (tahun
     * anggaran, Ãƒâ€šÃ‚Â§8.4), `sumber_dana`. Poktan penerima, jumlah per poktan, dan
     * tanggal serah pindah ke `saprotanDistribusi()`. `distribusi[]`,
     * `jumlah_tersalur`, `jumlah_belum_tersalur`, `poktan_penerima` ditempel
     * di sini sebagai turunan.
     *
     * `satuan` dibetulkan menjadi `satuan_id` (FK) sekalian: data lama
     * menyimpan nama sedangkan form mengirim id dan kamus data menyatakan FK.
     *
     * @return array<int, array<string, mixed>> Data pengadaan saprotan
     */
    public static function saprotan(): array
    {
        $data = self::saprotanPengadaan();

        $distribusiPer = [];
        foreach (self::saprotanDistribusi() as $d) {
            $distribusiPer[$d['saprotan_id']][] = $d;
        }

        return array_map(function (array $baris) use ($distribusiPer): array {
            $dist = $distribusiPer[$baris['id_saprotan']] ?? [];
            $tersalur = round(array_sum(array_column($dist, 'jumlah')), 3);

            return $baris + [
                'distribusi' => $dist,
                'jumlah_tersalur' => $tersalur,
                'jumlah_belum_tersalur' => max(0.0, round($baris['jumlah_total'] - $tersalur, 3)),
                'poktan_penerima' => array_values(array_unique(array_column($dist, 'poktan'))),
            ];
        }, $data);
    }

    /**
     * Distribusi pengadaan saprotan ke poktan, satu baris per poktan penerima.
     *
     * Ditambahkan 2026-08-30 (Putaran 7). `poktan_id`, `satuan_permukiman_id`,
     * dan `satuan_permukiman` MENGIKUTI poktan (rules.md Ãƒâ€šÃ‚Â§7c poin 4), tidak
     * dipilih terpisah. `sisa_benih` dihitung PER BARIS (jatah poktan ini
     * dikurangi pemakaian penanaman yang menunjuk baris ini), tidak disimpan
     * (rules.md Ãƒâ€šÃ‚Â§7c poin 8). `poktan`, `komoditas`, `varietas`,
     * `tahun_pengadaan`, dan `jenis` ditempel dari pengadaan sebagai turunan.
     *
     * @return array<int, array<string, mixed>> Data distribusi saprotan
     */
    public static function saprotanDistribusi(): array
    {
        $data = [
            ['id_saprotan_distribusi' => 1, 'saprotan_id' => 1, 'poktan_id' => 1, 'jumlah' => 150.0, 'tanggal_serah' => '2025-01-28', 'keterangan' => null],
            ['id_saprotan_distribusi' => 2, 'saprotan_id' => 1, 'poktan_id' => 2, 'jumlah' => 100.0, 'tanggal_serah' => '2025-01-30', 'keterangan' => 'Belum dipakai, disimpan di lumbung kelompok.'],
            // Sebagian UREA masih di gudang UPT: 800 dari 1200 tersalur.
            ['id_saprotan_distribusi' => 3, 'saprotan_id' => 2, 'poktan_id' => 1, 'jumlah' => 800.0, 'tanggal_serah' => '2025-02-10', 'keterangan' => null],
            ['id_saprotan_distribusi' => 4, 'saprotan_id' => 3, 'poktan_id' => 3, 'jumlah' => 40.0, 'tanggal_serah' => '2026-01-15', 'keterangan' => null],
            ['id_saprotan_distribusi' => 5, 'saprotan_id' => 4, 'poktan_id' => 1, 'jumlah' => 80.0, 'tanggal_serah' => '2025-02-18', 'keterangan' => null],
            ['id_saprotan_distribusi' => 6, 'saprotan_id' => 5, 'poktan_id' => 4, 'jumlah' => 15.0, 'tanggal_serah' => '2026-02-01', 'keterangan' => null],
            ['id_saprotan_distribusi' => 7, 'saprotan_id' => 6, 'poktan_id' => 1, 'jumlah' => 30.0, 'tanggal_serah' => '2025-05-20', 'keterangan' => null],
            ['id_saprotan_distribusi' => 8, 'saprotan_id' => 7, 'poktan_id' => 3, 'jumlah' => 1.5, 'tanggal_serah' => '2025-11-25', 'keterangan' => null],
            ['id_saprotan_distribusi' => 9, 'saprotan_id' => 8, 'poktan_id' => 2, 'jumlah' => 15.0, 'tanggal_serah' => '2026-05-30', 'keterangan' => null],
            ['id_saprotan_distribusi' => 10, 'saprotan_id' => 9, 'poktan_id' => 4, 'jumlah' => 12.0, 'tanggal_serah' => '2025-12-15', 'keterangan' => null],
        ];

        $pengadaan = [];
        foreach (self::saprotanPengadaan() as $s) {
            $pengadaan[$s['id_saprotan']] = $s;
        }

        $poktanPeta = [];
        foreach (self::poktan() as $p) {
            $poktanPeta[$p['id_poktan']] = $p;
        }

        // Pemakaian penanaman per baris distribusi.
        $terpakai = [];
        foreach (self::penanaman() as $t) {
            $did = $t['saprotan_distribusi_id'] ?? null;
            if ($did !== null) {
                $terpakai[$did] = ($terpakai[$did] ?? 0.0) + (float) ($t['volume_benih'] ?? 0);
            }
        }

        return array_map(function (array $d) use ($pengadaan, $poktanPeta, $terpakai): array {
            $s = $pengadaan[$d['saprotan_id']] ?? [];
            $p = $poktanPeta[$d['poktan_id']] ?? null;
            $sisa = $s['jenis'] === 'Benih'
                ? max(0.0, round($d['jumlah'] - ($terpakai[$d['id_saprotan_distribusi']] ?? 0.0), 3))
                : null;

            return $d + [
                'poktan' => $p['nama'] ?? null,
                'satuan_permukiman_id' => $p['satuan_permukiman_id'] ?? null,
                'satuan_permukiman' => $p['satuan_permukiman'] ?? null,
                'jenis' => $s['jenis'] ?? null,
                'nama' => $s['nama'] ?? null,
                'komoditas_id' => $s['komoditas_id'] ?? null,
                'komoditas' => $s['komoditas'] ?? null,
                'varietas' => $s['varietas'] ?? null,
                'satuan' => $s['satuan'] ?? null,
                'tahun_pengadaan' => $s['tahun_pengadaan'] ?? null,
                'sumber_dana' => $s['sumber_dana'] ?? null,
                'sisa_benih' => $sisa,
            ];
        }, $data);
    }

    /**
     * Baris pengadaan saprotan (induk) TANPA turunan distribusi.
     *
     * Sumber tunggal daftar pengadaan. `saprotan()` menempelkan turunan
     * distribusi ke sini; `saprotanDistribusi()` memanggilnya untuk konteks
     * pengadaan. Memisahkannya menghindari rekursi antara keduanya.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function saprotanPengadaan(): array
    {
        $data = [
            // Benih jagung hibrida dibagikan ke DUA poktan. Model lama memaksa
            // ini jadi dua baris terpisah.
            ['id_saprotan' => 1, 'jenis' => 'Benih', 'nama' => 'BENIH JAGUNG HIBRIDA', 'komoditas_id' => 1, 'komoditas' => 'JAGUNG', 'varietas' => 'Hibrida Bisi-18', 'jadwal_tanam' => '2026-02', 'jumlah_total' => 250.0, 'satuan_id' => 3, 'satuan' => 'Kilogram', 'tahun_pengadaan' => 2025, 'sumber_dana' => 'Dinas Pertanian Kabupaten', 'keterangan' => 'Disalurkan menjelang penanaman awal tahun.', 'foto_berkas_id' => 16, 'berkas_id' => 17],
            ['id_saprotan' => 2, 'jenis' => 'Pupuk', 'nama' => 'PUPUK UREA', 'komoditas_id' => null, 'komoditas' => null, 'varietas' => null, 'jadwal_tanam' => null, 'jumlah_total' => 1200.0, 'satuan_id' => 3, 'satuan' => 'Kilogram', 'tahun_pengadaan' => 2025, 'sumber_dana' => 'APBN', 'keterangan' => 'Sebagian masih di gudang UPT.', 'foto' => null, 'dokumen_pendukung' => null],
            ['id_saprotan' => 3, 'jenis' => 'Pestisida', 'nama' => 'INSEKTISIDA CAIR', 'komoditas_id' => null, 'komoditas' => null, 'varietas' => null, 'jadwal_tanam' => null, 'jumlah_total' => 40.0, 'satuan_id' => 4, 'satuan' => 'Liter', 'tahun_pengadaan' => 2026, 'sumber_dana' => 'Dinas Pertanian Kabupaten', 'keterangan' => null, 'foto' => null, 'dokumen_pendukung' => null],
            ['id_saprotan' => 4, 'jenis' => 'Benih', 'nama' => 'BENIH PADI IR64', 'komoditas_id' => 2, 'komoditas' => 'PADI', 'varietas' => 'IR64', 'jadwal_tanam' => '2026-03', 'jumlah_total' => 80.0, 'satuan_id' => 3, 'satuan' => 'Kilogram', 'tahun_pengadaan' => 2025, 'sumber_dana' => 'APBD Provinsi', 'keterangan' => null, 'foto' => null, 'dokumen_pendukung' => null],
            ['id_saprotan' => 5, 'jenis' => 'Mulsa', 'nama' => 'MULSA PLASTIK HITAM PERAK', 'komoditas_id' => null, 'komoditas' => null, 'varietas' => null, 'jadwal_tanam' => null, 'jumlah_total' => 15.0, 'satuan_id' => 5, 'satuan' => 'Rol', 'tahun_pengadaan' => 2026, 'sumber_dana' => 'Lembaga Swadaya Masyarakat', 'keterangan' => null, 'foto' => null, 'dokumen_pendukung' => null],
            // Benih jagung kedua bagi POKTAN MEKAR JAYA, seluruhnya terpakai:
            // tidak boleh muncul pada pilihan benih form penanaman (dijaga uji).
            ['id_saprotan' => 6, 'jenis' => 'Benih', 'nama' => 'BENIH JAGUNG LOKAL', 'komoditas_id' => 1, 'komoditas' => 'JAGUNG', 'varietas' => 'Lokal Kobalima', 'jadwal_tanam' => '2025-06', 'jumlah_total' => 30.0, 'satuan_id' => 3, 'satuan' => 'Kilogram', 'tahun_pengadaan' => 2025, 'sumber_dana' => 'Swadaya', 'keterangan' => 'Benih swadaya anggota, habis dipakai penanaman Juni 2025.', 'foto' => null, 'dokumen_pendukung' => null],
            // Tiga benih swadaya (2026-08-24): mendaftarkannya memberi benih
            // swadaya sebuah STOK, sehingga penanaman tidak seolah tak terbatas.
            ['id_saprotan' => 7, 'jenis' => 'Benih', 'nama' => 'BIBIT CABAI SEMAI SENDIRI', 'komoditas_id' => 5, 'komoditas' => 'CABAI', 'varietas' => 'Semai Sendiri', 'jadwal_tanam' => '2025-12', 'jumlah_total' => 1.5, 'satuan_id' => 3, 'satuan' => 'Kilogram', 'tahun_pengadaan' => 2025, 'sumber_dana' => 'Swadaya', 'keterangan' => 'Disemai anggota dari buah panen sebelumnya.', 'foto' => null, 'dokumen_pendukung' => null],
            ['id_saprotan' => 8, 'jenis' => 'Benih', 'nama' => 'BENIH JAGUNG SWADAYA KELOMPOK', 'komoditas_id' => 1, 'komoditas' => 'JAGUNG', 'varietas' => 'Lokal Kobalima', 'jadwal_tanam' => '2026-06', 'jumlah_total' => 15.0, 'satuan_id' => 3, 'satuan' => 'Kilogram', 'tahun_pengadaan' => 2026, 'sumber_dana' => 'Swadaya', 'keterangan' => 'Dibeli kelompok dari kas iuran anggota.', 'foto' => null, 'dokumen_pendukung' => null],
            ['id_saprotan' => 9, 'jenis' => 'Benih', 'nama' => 'BENIH PADI LOKAL SWADAYA', 'komoditas_id' => 2, 'komoditas' => 'PADI', 'varietas' => 'Lokal Kobalima', 'jadwal_tanam' => '2026-01', 'jumlah_total' => 12.0, 'satuan_id' => 3, 'satuan' => 'Kilogram', 'tahun_pengadaan' => 2025, 'sumber_dana' => 'Swadaya', 'keterangan' => 'Sisa gabah panen lalu yang disisihkan untuk benih.', 'foto' => null, 'dokumen_pendukung' => null],
        ];

        // Foto barang dan berita acara dibaca dari registry lewat FK langsung.
        return array_map(function (array $s): array {
            $foto = self::cariBerkas($s['foto_berkas_id'] ?? null);
            $dok = self::cariBerkas($s['berkas_id'] ?? null);

            return $s + [
                'foto' => $foto['nama_file'] ?? null,
                'foto_meta' => $foto,
                'dokumen_pendukung' => $dok['nama_file'] ?? null,
                'dokumen_pendukung_meta' => $dok,
            ];
        }, $data);
    }

    /**
     * Sisa benih yang belum terpakai pada satu baris DISTRIBUSI saprotan.
     *
     * Grain berpindah dari pengadaan ke distribusi sejak Putaran 7: satu batch
     * dapat dibagikan ke banyak poktan, dan jatah tiap poktan dikurangi hanya
     * oleh penanaman poktan itu sendiri. Menghitungnya di tingkat pengadaan
     * membuat penanaman poktan A menggerus jatah poktan B.
     *
     * Rumusnya tetap satu pengurangan yang MENGOREKSI DIRINYA SENDIRI ketika
     * baris penanaman disunting:
     *
     *     sisa = saprotan_distribusi.jumlah - SUM(penanaman.volume_benih WHERE saprotan_distribusi_id = ini)
     *
     * Itulah sebabnya tidak ada mekanisme "pengembalian stok" di mana pun.
     * Benih HABIS SEKALI PAKAI, tetapi penguncian terjadi ketika STOKNYA
     * HABIS, bukan ketika pertama kali dipakai (laporan Polri MT.II 2025:
     * satu poktan menanam 3 ha lalu 7 ha dari jatah yang sama).
     *
     * @param  int  $distribusiId  Nilai id_saprotan_distribusi
     * @return float Sisa dalam satuan aslinya, tidak pernah negatif
     */
    public static function sisaBenih(int $distribusiId): float
    {
        $baris = collect(self::saprotanDistribusi())->firstWhere('id_saprotan_distribusi', $distribusiId);

        if ($baris === null || $baris['jenis'] !== 'Benih') {
            return 0.0;
        }

        $terpakai = 0.0;

        foreach (self::penanaman() as $tanam) {
            if (($tanam['saprotan_distribusi_id'] ?? null) === $distribusiId) {
                $terpakai += (float) ($tanam['volume_benih'] ?? 0);
            }
        }

        return max(0.0, round((float) $baris['jumlah'] - $terpakai, 3));
    }

    /**
     * Sisa satu PENGADAAN yang belum dibagikan ke poktan mana pun (Putaran 7).
     *
     *     belum_tersalur = saprotan.jumlah_total - SUM(saprotan_distribusi.jumlah)
     *
     * Berbeda dari `sisaBenih()`: yang ini tentang barang di gudang UPT yang
     * belum diserahkan, bukan jatah poktan yang belum ditanam.
     *
     * @param  int  $saprotanId  Nilai id_saprotan
     */
    public static function sisaBenihPengadaan(int $saprotanId): float
    {
        $baris = collect(self::saprotan())->firstWhere('id_saprotan', $saprotanId);

        return $baris === null ? 0.0 : (float) $baris['jumlah_belum_tersalur'];
    }

    /**
     * Benih milik satu poktan untuk satu komoditas yang stoknya masih ada.
     *
     * Dipakai form penanaman: begitu poktan dan komoditas dipilih, hanya
     * benih yang benar-benar dapat ditanam yang ditawarkan. Sejak Putaran 7
     * yang diiterasi adalah baris DISTRIBUSI (jatah satu poktan), bukan
     * pengadaan; `id` yang dikembalikan adalah `id_saprotan_distribusi`, yang
     * lalu disimpan `penanaman.saprotan_distribusi_id`.
     *
     * Benih yang stoknya habis SENGAJA tidak muncul.
     *
     * @param  int|null  $poktanId  Penyaring poktan, null berarti semua
     * @param  int|null  $komoditasId  Penyaring komoditas, null berarti semua
     * @return array<int, array<string, mixed>> Baris distribusi beserta sisanya
     */
    public static function benihTersedia(?int $poktanId = null, ?int $komoditasId = null): array
    {
        $hasil = [];

        foreach (self::saprotanDistribusi() as $baris) {
            if ($baris['jenis'] !== JenisSaprotan::Benih->value) {
                continue;
            }

            if ($poktanId !== null && $baris['poktan_id'] !== $poktanId) {
                continue;
            }

            if ($komoditasId !== null && ($baris['komoditas_id'] ?? null) !== $komoditasId) {
                continue;
            }

            $sisa = self::sisaBenih($baris['id_saprotan_distribusi']);

            if ($sisa <= 0) {
                continue;
            }

            $hasil[] = $baris + [
                'sisa_benih' => $sisa,
                'label_benih' => $baris['nama'].' - sisa '
                    .rtrim(rtrim(number_format($sisa, 2, ',', '.'), '0'), ',')
                    .' '.$baris['satuan'],
            ];
        }

        return $hasil;
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
     * Penanaman: lahan mana, ditanami apa, kapan.
     *
     * DAHULU BERNAMA "RIWAYAT TANAM", diubah 2026-08-22 atas keberatan pemilik
     * proyek. Kata "riwayat" menyiratkan catatan masa lalu, padahal barisnya
     * dibuat justru ketika penanaman baru dimulai dan hasil panennya belum
     * ada. Lebih menyesatkan lagi, `hasil_panen` menaut ke tabel inilah:
     * menyebut induk dari panen sebagai "riwayat" membuat orang mengira
     * penanaman yang sedang berjalan dicatat di tempat lain.
     *
     * TANPA MUSIM TANAM. Fitur itu dicabut pada hari yang sama atas keputusan
     * pemilik proyek: di lapangan poktan menanam secara fleksibel, tidak
     * mengikuti periode baku MT1/MT2 yang ditetapkan dari meja. Memaksa setiap
     * penanaman memilih salah satu musim membuat petugas menebak, dan tebakan
     * itu lalu dipakai sebagai dasar rekap.
     *
     * Sumbu waktunya kini `periode_tanam` di sini dan `periode_panen` pada
     * hasil panen. Keduanya sudah ada, memang dicatat petugas, dan tidak
     * memerlukan tabel tersendiri.
     *
     * KEDUANYA BULAN, bukan tanggal penuh (diubah 2026-08-22). Penanaman satu
     * hamparan berlangsung berhari-hari, sehingga menuntut satu tanggal pasti
     * membuat petugas menebak - dan tebakan itu lalu dipakai sebagai dasar
     * rekap seolah-olah data terukur. Bulan sudah cukup halus untuk seluruh
     * rekap yang ada.
     *
     * `saprotan_id` dan `volume_benih` ditambahkan 2026-08-22. Keduanya
     * menautkan penanaman ke benih yang dipakainya, sehingga sisa stok dapat
     * dihitung tanpa mekanisme apa pun selain satu pengurangan (lihat
     * `sisaBenih()`).
     *
     * Volume benih SENGAJA disimpan di sini, bukan dihitung dari luas tanam
     * memakai rasio baku. Laporan Polri MT.II 2025 memang memakai 15 kg/ha di
     * hampir seluruh barisnya, tetapi rasio itu keputusan program pada satu
     * bantuan, bukan hukum alam: benih swadaya dan komoditas lain memakai
     * takaran berbeda. Menghitungnya otomatis membuat angka karangan tampil
     * seolah-olah hasil pendataan.
     *
     * Keduanya boleh kosong: penanaman dari benih yang tidak tercatat pada
     * modul saprotan tetap harus dapat didata, sebab menolaknya berarti
     * memaksa petugas mengarang penyaluran yang tidak pernah terjadi.
     *
     * BERPUSAT PADA POKTAN, bukan lahan perorangan (diubah 2026-08-22).
     * Kolom `lahan_id` dan `petani` dicabut; `poktan_id` menggantikannya
     * sebagai penentu pelaku sekaligus lokasi. Seluruh pencatatan Produksi
     * Pertanian memang berpusat pada kelompok, dan lapangan membenarkannya:
     * laporan Polri MT.II 2025 mencatat satu baris per POKTAN, bukan per
     * bidang lahan.
     *
     * Rantai lokasinya tetap utuh tanpa lahan: `penanaman -> poktan ->
     * satuan_permukiman`, sebab poktan sudah menyimpan SP-nya sendiri.
     *
     * `luas_tanam` berganti nama menjadi `realisasi_tanam` agar sejalan
     * dengan istilah laporan, dan agar tidak tertukar dengan LUAS LAHAN milik
     * poktan yang merupakan angka terhitung, bukan isian.
     *
     * @return array<int, array<string, mixed>> Data penanaman
     */
    public static function penanaman(): array
    {
        $data = [
            ['id_penanaman' => 1, 'poktan_id' => 1, 'poktan' => 'POKTAN MEKAR JAYA', 'komoditas_id' => 1, 'komoditas' => 'JAGUNG', 'saprotan_distribusi_id' => 1, 'volume_benih' => 22.5, 'realisasi_tanam' => 1.50, 'periode_tanam' => '2025-11', 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'keterangan' => null],
            ['id_penanaman' => 2, 'poktan_id' => 1, 'poktan' => 'POKTAN MEKAR JAYA', 'komoditas_id' => 2, 'komoditas' => 'PADI', 'saprotan_distribusi_id' => 5, 'volume_benih' => 20.0, 'realisasi_tanam' => 0.75, 'periode_tanam' => '2025-12', 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'keterangan' => null],
            ['id_penanaman' => 3, 'poktan_id' => 1, 'poktan' => 'POKTAN MEKAR JAYA', 'komoditas_id' => 1, 'komoditas' => 'JAGUNG', 'saprotan_distribusi_id' => 1, 'volume_benih' => 30.0, 'realisasi_tanam' => 2.00, 'periode_tanam' => '2025-11', 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'keterangan' => 'Penanaman bertahap, sisa lahan menyusul.'],
            // Tanpa benih tercatat: cabai ditanam dari bibit swadaya yang
            // tidak pernah masuk modul saprotan. Sengaja ada agar cabang
            // nullable ikut terlihat saat peninjauan.
            ['id_penanaman' => 4, 'poktan_id' => 3, 'poktan' => 'POKTAN TANI BERSATU', 'komoditas_id' => 5, 'komoditas' => 'CABAI', 'saprotan_distribusi_id' => 8, 'volume_benih' => 1.5, 'realisasi_tanam' => 0.30, 'periode_tanam' => '2025-12', 'satuan_permukiman_id' => 2, 'satuan_permukiman' => 'SP Tniumanu', 'keterangan' => 'Bibit swadaya anggota, didaftarkan lebih dulu pada penyaluran saprotan.'],
            // Menghabiskan seluruh 30 kg BENIH JAGUNG LOKAL, sehingga benih
            // itu tidak lagi muncul sebagai pilihan pada penanaman berikutnya.
            ['id_penanaman' => 5, 'poktan_id' => 1, 'poktan' => 'POKTAN MEKAR JAYA', 'komoditas_id' => 1, 'komoditas' => 'JAGUNG', 'saprotan_distribusi_id' => 7, 'volume_benih' => 30.0, 'realisasi_tanam' => 1.50, 'periode_tanam' => '2025-06', 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'keterangan' => null],
            // BELUM DIPANEN SAMA SEKALI, satu-satunya pada data contoh.
            //
            // Sengaja ada agar status ketiga punya benda nyata untuk dilihat
            // saat peninjauan dan diperiksa uji. Tanpa baris ini, seluruh
            // penanaman sudah dipanen dan lencana `Belum Dipanen` tidak akan
            // pernah tampil di layar mana pun.
            //
            // Ditaruh pada POKTAN SUBUR MAKMUR yang belum pernah menanam,
            // BUKAN pada POKTAN MEKAR JAYA: lahan tersedia milik Mekar Jaya
            // sudah dikunci uji peramban pada angka 3,45 ha, dan menambah
            // penanaman di sana akan memerahkannya tanpa ada yang rusak.
            ['id_penanaman' => 6, 'poktan_id' => 2, 'poktan' => 'POKTAN SUBUR MAKMUR', 'komoditas_id' => 1, 'komoditas' => 'JAGUNG', 'saprotan_distribusi_id' => 9, 'volume_benih' => 15.0, 'realisasi_tanam' => 1.00, 'periode_tanam' => '2026-06', 'satuan_permukiman_id' => 1, 'satuan_permukiman' => 'SP Kapitan Meo', 'keterangan' => 'Tanaman masih berdiri, panen diperkirakan tiga bulan lagi.'],
            // GAGAL TOTAL, satu-satunya pada data contoh. Panennya tercatat
            // 0 ha dengan puso menutup seluruh luas, sehingga cabang itu
            // ikut terlihat saat peninjauan dan punya benda nyata untuk
            // diuji. Ditaruh pada POKTAN HARAPAN BARU yang belum pernah
            // menanam, agar tidak mengganggu perhitungan lahan poktan lain
            // yang sudah dikunci uji peramban.
            ['id_penanaman' => 7, 'poktan_id' => 4, 'poktan' => 'POKTAN HARAPAN BARU', 'komoditas_id' => 2, 'komoditas' => 'PADI', 'saprotan_distribusi_id' => 10, 'volume_benih' => 12.0, 'realisasi_tanam' => 0.50, 'periode_tanam' => '2026-01', 'satuan_permukiman_id' => 6, 'satuan_permukiman' => 'SP Weain', 'keterangan' => 'Benih swadaya kelompok, disisihkan dari gabah panen sebelumnya.'],
        ];

        return self::lekatkanBerkas($data, 'penanaman_berkas', 'penanaman_id', 'id_penanaman', ['pendukung' => 'dokumen_pendukung']);
    }

    /**
     * Luas lahan poktan yang BELUM ditanami pada saat ini.
     *
     * Berbeda sifat dari sisa benih, dan perbedaan itu disengaja:
     *
     * - Benih HABIS selamanya begitu ditabur.
     * - Lahan KEMBALI TERSEDIA setelah panennya tercatat.
     *
     * Karena itu yang dikurangkan hanyalah penanaman yang belum dipanen.
     * Menghitung seluruh penanaman sepanjang sejarah akan membuat lahan poktan
     * tampak habis setelah beberapa musim, padahal bidang yang sama memang
     * ditanami berulang kali tiap tahun.
     *
     * DISEDERHANAKAN 2026-08-24. Kriterianya kembali menjadi "sudah punya
     * catatan panen", bukan "sisa luasnya nol". Penghalusan 2026-08-22 dibuat
     * untuk menangani panen bertahap, dan panen bertahap kini dicabut: satu
     * penanaman selalu dipanen sekali dan sekaligus menutup seluruh luasnya,
     * entah sebagai hasil panen, puso, atau campuran keduanya.
     *
     * @param  int  $poktanId  Nilai id_poktan
     * @return float Sisa luas dalam hektare, tidak pernah negatif
     */
    public static function lahanTersedia(int $poktanId): float
    {
        $rekap = self::rekapLahanPoktan($poktanId);

        // Yang masih menahan lahan hanyalah penanaman yang belum dipanen.
        $terpakai = 0.0;

        foreach (self::penanaman() as $tanam) {
            if ($tanam['poktan_id'] !== $poktanId) {
                continue;
            }

            if (self::statusPanen($tanam['id_penanaman']) === StatusPanen::BelumDipanen) {
                $terpakai += (float) $tanam['realisasi_tanam'];
            }
        }

        return max(0.0, round($rekap['luas_total'] - $terpakai, 2));
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
            ['id_role' => 1, 'nama' => 'Admin', 'deskripsi' => 'Akses penuh termasuk manajemen pengguna, role, dan audit log.', 'cakupan_data' => CakupanData::Semua->value, 'is_bawaan' => true, 'is_terkunci' => true, 'is_aktif' => true, 'jumlah_izin' => 97, 'jumlah_pengguna' => 1],
            ['id_role' => 2, 'nama' => 'Dinas Transmigrasi', 'deskripsi' => 'Mengelola data wilayah, transmigran, rumah, lahan, dan infrastruktur.', 'cakupan_data' => CakupanData::Semua->value, 'is_bawaan' => true, 'is_terkunci' => false, 'is_aktif' => true, 'jumlah_izin' => 49, 'jumlah_pengguna' => 1],
            ['id_role' => 3, 'nama' => 'Dinas Pertanian', 'deskripsi' => 'Mengelola data poktan, komoditas, panen, alsintan, dan saprotan.', 'cakupan_data' => CakupanData::PerBidang->value, 'is_bawaan' => true, 'is_terkunci' => false, 'is_aktif' => true, 'jumlah_izin' => 44, 'jumlah_pengguna' => 1],
            ['id_role' => 4, 'nama' => 'Operator SP', 'deskripsi' => 'Memasukkan data pada satuan permukiman yang ditugaskan. Tanpa kewenangan hapus.', 'cakupan_data' => CakupanData::PerSp->value, 'is_bawaan' => true, 'is_terkunci' => false, 'is_aktif' => true, 'jumlah_izin' => 49, 'jumlah_pengguna' => 2],

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
            ['id_audit_log' => 4, 'waktu' => '2026-08-10 14:22:41', 'pengguna' => 'AGUS PRASETYO', 'aksi' => 'Ubah', 'nama_tabel' => 'pengaduan', 'record_id' => 5, 'ringkasan' => 'Menutup pengaduan PGD-2026-0005-96RY4X berstatus Selesai.', 'ip_address' => '10.14.2.55'],
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
            ['id_audit_log' => 15, 'waktu' => '2026-08-02 13:47:26', 'pengguna' => 'MARIA GORETI', 'aksi' => 'Tambah', 'nama_tabel' => 'pengaduan', 'record_id' => 1, 'ringkasan' => 'Mencatat pengaduan PGD-2026-0001-PMTUXK dari warga SP Kapitan Meo.', 'ip_address' => '10.14.2.91'],
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
     * CAKUPANNYA AGREGAT KAWASAN, bukan penjumlahan tabel transaksi. Angka di
     * sini mencerminkan seluruh keluarga di kawasan; `penanaman()` dan
     * `hasilPanen()` hanya memuat beberapa baris contoh untuk menguji tampilan,
     * dan totalnya karena itu ratusan kali lebih kecil.
     *
     * Keduanya BUKAN dua versi dari angka yang sama. Menurunkan yang satu dari
     * yang lain akan membuat dashboard menyatakan kawasan berisi ribuan
     * keluarga hanya menanam 7 hektare.
     *
     * EMPAT ANGKA PRODUKSI ditambahkan 2026-08-24, menutup indikator 17 pada
     * ui-spec.md 9. Keempatnya WAJIB memenuhi dua identitas yang sama seperti
     * pada tabel transaksi (rules.md 9.9 dan 9.11):
     *
     *     realisasi_tanam = hasil_panen + puso + belum_dipanen
     *     volume_panen    = hasil_panen x produktivitas
     *
     * Urutan penyusunannya sengaja begitu: produktivitas ditetapkan lebih
     * dulu sebagai angka yang wajar bagi kawasan berbasis jagung, lalu luas
     * panen DITURUNKAN darinya. Menetapkan luas lebih dulu akan menghasilkan
     * produktivitas berkoma panjang yang tampak seperti hasil pengukuran,
     * padahal justru angka sisa pembagian.
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
            // Produksi pertanian kawasan, indikator 17.
            //
            // Realisasi tanam 635,21 ha dari 3.250,75 ha lahan tergarap, atau
            // sekitar 19,5%. Angka itu wajar dan memang tidak mendekati 100%:
            // satu bidang tidak ditanami sepanjang tahun, dan sebagian lahan
            // diberakan atau ditanami tanaman keras.
            'realisasi_tanam_ha' => 635.21,
            'hasil_panen_ha' => 568.46,
            'puso_ha' => 24.60,
            'belum_dipanen_ha' => 42.15,
            'produktivitas_ton_ha' => 3.250,
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
     * Daftar tahun yang tercatat pada data deret kependudukan.
     *
     * @return list<int>
     */
    public static function daftarTahunKependudukan(): array
    {
        return self::deretTahunan()['tahun'];
    }

    /**
     * Menyesuaikan sebaran kategori agar jumlah totalnya tepat sama dengan jumlah KK pada tahun tertentu.
     * Menjamin rekonsiliasi total 100% konsisten antar-tab (rules.md Ãƒâ€šÃ‚Â§10a.4b).
     *
     * @param  array<string, int>  $sebaran  Peta kategori => jumlah KK pada tahun terakhir (1140)
     * @param  int|null  $tahun  Tahun yang dipilih
     * @return array<string, int> Peta kategori => jumlah KK pada tahun terpilih
     */
    public static function skalakanSebaranKependudukan(array $sebaran, ?int $tahun = null): array
    {
        if ($tahun === null) {
            return $sebaran;
        }

        $deret = self::deretTahunan();
        $idx = array_search($tahun, $deret['tahun'], true);
        if ($idx === false || $tahun === end($deret['tahun'])) {
            return $sebaran;
        }

        $targetTotal = $deret['jumlah_kk'][$idx];
        $baseTotal = array_sum($sebaran);
        if ($baseTotal === 0 || $targetTotal === $baseTotal) {
            return $sebaran;
        }

        $rasio = $targetTotal / $baseTotal;
        $hasil = [];
        $kunciTerbesar = null;
        $nilaiTerbesar = -1;

        foreach ($sebaran as $kunci => $nilai) {
            if ($nilai === 0) {
                $hasil[$kunci] = 0;

                continue;
            }
            $dibulatkan = (int) round($nilai * $rasio);
            $hasil[$kunci] = $dibulatkan;
            if ($nilai > $nilaiTerbesar) {
                $nilaiTerbesar = $nilai;
                $kunciTerbesar = $kunci;
            }
        }

        $selisih = $targetTotal - array_sum($hasil);
        if ($selisih !== 0 && $kunciTerbesar !== null) {
            $hasil[$kunciTerbesar] += $selisih;
        }

        return $hasil;
    }

    /**
     * Rekap penghuni kawasan menurut status tinggalnya.
     *
     * Indikator ke-14 dashboard (agents/ui-spec.md bagian 9).
     * Mendukung filter tahun pada halaman Rekap Kependudukan.
     *
     * @param  int|null  $tahun  Tahun data terpilih (default tahun terakhir)
     * @return array<string, int> Peta status tinggal ke jumlah KK
     */
    public static function rekapPenghuni(?int $tahun = null): array
    {
        $base = [
            StatusTinggal::Aktif->value => 1063,
            StatusTinggal::PindahPenduduk->value => 54,
            // Enam keluarga yang sebelumnya berstatus `Meninggal` dilebur ke
            // sini, sehingga jumlah seluruhnya tetap 1140 KK. Statusnya memang
            // Tidak Aktif: yang membubarkan keluarga bukan kematian kepalanya,
            // melainkan tidak adanya lagi penghuni yang meneruskan.
            StatusTinggal::TidakAktif->value => 23,
        ];

        return self::skalakanSebaranKependudukan($base, $tahun);
    }

    /**
     * Sebaran pekerjaan kepala keluarga untuk histogram.
     * Mendukung filter tahun pada halaman Rekap Kependudukan.
     *
     * @param  int|null  $tahun  Tahun data terpilih (default tahun terakhir)
     * @return array<string, int> Peta pekerjaan ke jumlah KK
     */
    public static function sebaranPekerjaan(?int $tahun = null): array
    {
        $base = [
            'Petani' => 892,
            'Buruh Tani' => 118,
            'Pedagang' => 54,
            'Wiraswasta' => 32,
            'Guru' => 18,
            'Aparat Desa' => 14,
            'Lainnya' => 12,
        ];

        return self::skalakanSebaranKependudukan($base, $tahun);
    }

    /**
     * Sebaran daerah asal kepala keluarga.
     *
     * CAKUPANNYA AGREGAT KAWASAN, bukan penghitungan `transmigran()`. Alasannya
     * sama dengan `sebaranPekerjaan()` di atas: data contoh hanya memuat 8 KK,
     * sedangkan kawasan berisi 1.140. Menghitungnya dari sana akan membuat
     * rekap menampilkan "8 KK" di sebelah tab lain yang menampilkan "1.140 KK",
     * dan pembaca wajar mengira salah satunya rusak.
     *
     * TOTALNYA WAJIB SAMA dengan `ringkasanDashboard()['jumlah_kk']`. Keenam tab
     * rekap kependudukan membagi keluarga yang sama menurut sudut pandang
     * berbeda; totalnya yang berlainan berarti salah satu pembagiannya bocor.
     *
     * Diurutkan dari jumlah terbesar, dengan `Lainnya` sebagai penampung sisa -
     * pola yang sama dengan `sebaranKomoditas()`.
     *
     * BERBASIS `kabupaten_id`, BUKAN TEKS (diubah 2026-09-02). Kuncinya adalah
     * id pada `DataWilayah::kabupaten()`, dan namanya dibaca dari sana saat
     * dirender. Sebelum ini `transmigran.daerah_asal` berupa teks bebas, dan
     * pada data nyata ejaannya akan beragam: "KUPANG", "Kab. Kupang", dan
     * "KABUPATEN KUPANG" menjadi tiga baris berbeda meski menunjuk tempat yang
     * sama. `UppercaseInput` menyeragamkan huruf besarnya, tetapi tidak
     * ejaannya, sehingga pembagiannya bocor tanpa memerahkan apa pun.
     *
     * Nama kabupaten juga tidak unik: Kabupaten Kupang (5301) berbeda dari
     * Kota Kupang (5371), sedangkan teks "KUPANG" tidak dapat membedakannya.
     *
     * `Lainnya` TETAP berkunci teks, sebab ia penampung sisa dan bukan satu
     * kabupaten tertentu. Memberinya id berarti mengarang tempat yang tidak
     * pernah didata.
     *
     * @param  int|null  $tahun  Tahun data terpilih (default tahun terakhir)
     * @return array<int|string, int> Peta id kabupaten (atau `Lainnya`) ke jumlah KK
     */
    public static function sebaranDaerahAsal(?int $tahun = null): array
    {
        $base = [
            5321 => 402,  // Kabupaten Malaka
            5304 => 286,  // Kabupaten Belu
            5303 => 178,  // Kabupaten Timor Tengah Utara
            5301 => 145,  // Kabupaten Kupang
            5302 => 96,   // Kabupaten Timor Tengah Selatan
            'Lainnya' => 33,
        ];

        return self::skalakanSebaranKependudukan($base, $tahun);
    }

    /**
     * Sebaran daerah asal beserta nama kabupatennya, siap ditampilkan.
     *
     * Pemisahan ini disengaja: `sebaranDaerahAsal()` menyimpan id sebagai
     * kebenarannya, sedangkan pelabelan terjadi di satu tempat saja. Tanpa itu
     * tiap pemakainya akan melabeli sendiri-sendiri, dan pelabelan yang keliru
     * gagal secara senyap - id yang tidak ketemu tampil sebagai tanda hubung,
     * dan tanda itu terbaca "tidak ada data" padahal artinya "kodenya tidak
     * menemukan namanya" (pola yang sama dengan agents/rules.md 9 poin 8i).
     *
     * @param  int|null  $tahun  Tahun data terpilih (default tahun terakhir)
     * @return array<string, int> Peta nama kabupaten ke jumlah KK
     */
    public static function sebaranDaerahAsalBerlabel(?int $tahun = null): array
    {
        $hasil = [];

        foreach (self::sebaranDaerahAsal($tahun) as $kunci => $jumlah) {
            $nama = is_int($kunci)
                ? (DataWilayah::namaKabupaten($kunci) ?? 'Tidak dikenal')
                : $kunci;

            $hasil[$nama] = ($hasil[$nama] ?? 0) + $jumlah;
        }

        return $hasil;
    }

    /**
     * Sebaran pendidikan terakhir kepala keluarga.
     *
     * Agregat kawasan, lihat keterangan pada `sebaranDaerahAsal()`.
     *
     * DIURUTKAN MENURUT JENJANG, bukan menurut jumlah. Pendidikan bertingkat,
     * sehingga mengurutkannya menurut jumlah membuat `SD` muncul mendahului
     * `Tidak Sekolah` dan pembaca kehilangan bentuk piramidanya. Urutannya
     * mengikuti enum `PendidikanTerakhir`.
     *
     * Jenjang yang tidak berpenghuni TETAP ditampilkan bernilai nol. "Tidak ada
     * lulusan S3 di kawasan ini" adalah informasi; baris yang hilang sama
     * sekali membuat pembaca tidak dapat membedakannya dari data yang belum
     * didata.
     *
     * @param  int|null  $tahun  Tahun data terpilih (default tahun terakhir)
     * @return array<string, int> Peta jenjang pendidikan ke jumlah KK
     */
    public static function sebaranPendidikan(?int $tahun = null): array
    {
        $base = [
            'Tidak Sekolah' => 61,
            'SD' => 402,
            'SMP' => 331,
            'SMA/SMK' => 274,
            'Diploma' => 38,
            'S1' => 32,
            'S2' => 2,
            'S3' => 0,
        ];

        return self::skalakanSebaranKependudukan($base, $tahun);
    }

    /**
     * Sebaran komoditas utama untuk grafik donat.
     *
     * CAKUPANNYA AGREGAT KAWASAN SETAHUN, bukan penjumlahan `hasilPanen()`.
     * Angkanya mencerminkan panen seluruh keluarga di kawasan, sehingga wajar
     * bernilai ribuan ton; `hasilPanen()` hanya memuat lima transaksi contoh
     * untuk menguji tampilan tabel dan totalnya jauh lebih kecil.
     *
     * Keduanya BUKAN dua versi dari angka yang sama, dan tidak boleh
     * diturunkan satu dari yang lain. Nilai di sini menopang empat angka
     * dashboard yang saling konsisten: `ringkasanDashboard()['volume_panen_ton']`,
     * jumlah seluruh `rekapPerSp()['volume_panen']`, dan nilai terakhir
     * `deretTahunan()['volume_panen']`. Mengubah salah satunya tanpa yang lain
     * membuat dashboard menampilkan angka yang saling bertentangan.
     *
     * KUNCINYA WAJIB SAMA PERSIS dengan `komoditas()['nama']`, yaitu huruf
     * kapital seluruhnya (diseragamkan 2026-08-24). Sebelumnya memakai huruf
     * judul, dan ketidakcocokan itu memaksa dua berkas menormalkan huruf
     * sendiri-sendiri - salah satunya keliru.
     *
     * Akibatnya nyata dan senyap: `/komoditas` mencocokkan lewat
     * `ucfirst(mb_strtolower(...))` yang hanya mengapitalkan huruf PERTAMA,
     * sehingga `KACANG TANAH` dicari sebagai `Kacang tanah` dan tidak pernah
     * ketemu. Kolom Volume Tercatat lalu menampilkan tanda hubung, dan tanda
     * itu terbaca sebagai "belum ada panen" padahal artinya "kodenya tidak
     * menemukan datanya". Dua keadaan yang berbeda ditampilkan sama.
     *
     * `Lainnya` sengaja TIDAK berhuruf kapital: ia bukan komoditas, melainkan
     * penampung sisa yang memang tidak ada pada data master.
     *
     * @return array<string, float> Peta komoditas ke volume dalam ton
     */
    public static function sebaranKomoditas(): array
    {
        return [
            'JAGUNG' => 1284.5,
            'PADI' => 342.8,
            'KACANG TANAH' => 118.4,
            'UBI KAYU' => 68.2,
            'CABAI' => 21.6,
            'Lainnya' => 12.0,
        ];
    }

    /**
     * Rekap ringkas per satuan permukiman untuk tabel dan drill-down.
     * Mendukung filter tahun pada halaman Rekap Kependudukan.
     *
     * Kolom `satuan_permukiman_id` diperlukan agar baris rekap dapat ditaut
     * ke halaman rincian `/dashboard/sp/{id}`.
     *
     * @param  int|null  $tahun  Tahun data terpilih (default tahun terakhir)
     * @return array<int, array<string, mixed>> Rekap per SP
     */
    public static function rekapPerSp(?int $tahun = null): array
    {
        $base = [
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

        if ($tahun === null) {
            return $base;
        }

        $deret = self::deretTahunan();
        $idx = array_search($tahun, $deret['tahun'], true);
        if ($idx === false || $tahun === end($deret['tahun'])) {
            return $base;
        }

        $targetKk = $deret['jumlah_kk'][$idx];
        $baseKk = 1140;
        $rasio = $targetKk / $baseKk;

        $hasil = [];
        $totalKkHitung = 0;
        $maxKkIdx = 0;

        foreach ($base as $i => $baris) {
            $kkBaris = (int) round($baris['jumlah_kk'] * $rasio);
            $huniBaris = (int) round($baris['rumah_terhuni'] * $rasio);
            $totalKkHitung += $kkBaris;

            $barisBaru = $baris;
            $barisBaru['jumlah_kk'] = $kkBaris;
            $barisBaru['rumah_terhuni'] = $huniBaris;
            $hasil[] = $barisBaru;

            if ($baris['jumlah_kk'] > $base[$maxKkIdx]['jumlah_kk']) {
                $maxKkIdx = $i;
            }
        }

        $selisih = $targetKk - $totalKkHitung;
        if ($selisih !== 0) {
            $hasil[$maxKkIdx]['jumlah_kk'] += $selisih;
        }

        return $hasil;
    }

    /**
     * Lima tahun terakhir yang dilayani pemilih tahun tunggal Laporan Rekap
     * Indikator Kawasan dan Laporan Monografi SP (Putaran 5). Dibatasi lima
     * tahun untuk menahan ukuran DOM (6 SP x 5 tahun = 30 baris per tabel).
     *
     * @return list<int>
     */
    public static function tahunLaporan(): array
    {
        return array_slice(self::deretTahunan()['tahun'], -5);
    }

    /**
     * Indikator tingkat kawasan per tahun, untuk pemilih tahun tunggal
     * (Putaran 5). Dikunci per tahun (lihat `tahunLaporan()`).
     *
     * IRISAN TAHUN TERAKHIR WAJIB SAMA dengan `ringkasanDashboard()` untuk
     * setiap kunci di sini -- itulah invarian yang menjaga blok ringkasan dan
     * tabel per SP tidak saling membantah. Kunci yang punya deret sendiri di
     * `deretTahunan()` dibaca dari sana; sisanya diskalakan menurut porsi KK
     * tahun itu terhadap tahun terakhir. Angka sebelum tahun terakhir adalah
     * CONTOH turunan, bukan pendataan; hanya irisan tahun terakhir yang
     * mengikat invarian. Cacah kelembagaan (poktan/alsintan/saprotan) TIDAK
     * bertahun -- itu cacah baris contoh, ditampilkan apa adanya dengan catatan.
     *
     * @return array<int, array<string, float|int>>
     */
    public static function indikatorKawasanTahun(): array
    {
        $deret = self::deretTahunan();
        $r = self::ringkasanDashboard();
        $indeks = array_flip($deret['tahun']);
        $kkAkhir = (int) end($deret['jumlah_kk']);

        $hasil = [];

        foreach (self::tahunLaporan() as $tahun) {
            $i = $indeks[$tahun];
            $akhir = $tahun === (int) end($deret['tahun']);
            $f = $kkAkhir > 0 ? $deret['jumlah_kk'][$i] / $kkAkhir : 1.0;

            $hasil[$tahun] = [
                'jumlah_kk' => $deret['jumlah_kk'][$i],
                'jumlah_jiwa' => $deret['jumlah_jiwa'][$i],
                'jumlah_petani' => $deret['jumlah_petani'][$i],
                'harga_rata_rata' => $deret['harga_rata_rata'][$i],
                'volume_panen_ton' => $akhir ? $r['volume_panen_ton'] : round($deret['volume_panen'][$i], 2),
                'rumah_total' => $akhir ? $r['rumah_total'] : (int) round($r['rumah_total'] * $f),
                'rumah_terhuni' => $akhir ? $r['rumah_terhuni'] : (int) round($r['rumah_terhuni'] * $f),
                'luas_lahan_total' => $akhir ? $r['luas_lahan_total'] : round($r['luas_lahan_total'] * $f, 2),
                'realisasi_tanam_ha' => $akhir ? $r['realisasi_tanam_ha'] : round($r['realisasi_tanam_ha'] * $f, 2),
                'hasil_panen_ha' => $akhir ? $r['hasil_panen_ha'] : round($r['hasil_panen_ha'] * $f, 2),
                'puso_ha' => $akhir ? $r['puso_ha'] : round($r['puso_ha'] * $f, 2),
                'belum_dipanen_ha' => $akhir ? $r['belum_dipanen_ha'] : round($r['belum_dipanen_ha'] * $f, 2),
                'pengaduan_terbuka' => $akhir ? $r['pengaduan_terbuka'] : (int) round($r['pengaduan_terbuka'] * $f),
            ];

            $hasil[$tahun]['produktivitas_ton_ha'] = $akhir
                ? $r['produktivitas_ton_ha']
                : ($hasil[$tahun]['hasil_panen_ha'] > 0
                    ? round($hasil[$tahun]['volume_panen_ton'] / $hasil[$tahun]['hasil_panen_ha'], 3)
                    : 0.0);
        }

        return $hasil;
    }

    /**
     * Membagi sebuah total menurut porsi, dengan koreksi sisa pembulatan
     * ditaruh pada porsi terbesar supaya jumlahnya tepat.
     *
     * @param  array<int|string, float>  $porsi
     * @return array<int|string, float>
     */
    private static function bagiProporsional(array $porsi, float $total, int $desimal): array
    {
        if ($porsi === []) {
            return [];
        }

        $kunciMaks = array_keys($porsi, max($porsi))[0];
        $keluar = [];
        $akum = 0.0;

        foreach ($porsi as $k => $p) {
            $keluar[$k] = round($total * $p, $desimal);
            $akum += $keluar[$k];
        }

        $keluar[$kunciMaks] = round($keluar[$kunciMaks] + $total - $akum, $desimal);

        return $keluar;
    }

    /**
     * Rekap per SP untuk satu tahun (Putaran 5). `rekapPerSpTahun(tahunAkhir)`
     * WAJIB SAMA PERSIS dengan `rekapPerSp()` (mengikat uji "menjaga jumlah
     * enam SP"). Tahun lain: porsi SP tahun terakhir dikalikan total kawasan
     * tahun itu (`indikatorKawasanTahun()`), dengan koreksi sisa supaya Sigma
     * per kolom tetap sama dengan angka kawasan tahun itu.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function rekapPerSpTahun(int $tahun): array
    {
        $dasar = self::rekapPerSp();
        $tahunAkhir = (int) end(self::deretTahunan()['tahun']);

        if ($tahun === $tahunAkhir) {
            return $dasar;
        }

        $kawasanAkhir = self::indikatorKawasanTahun()[$tahunAkhir];
        $kawasanTahun = self::indikatorKawasanTahun()[$tahun];

        $petaKawasan = [
            'jumlah_kk' => 'jumlah_kk',
            'rumah_terhuni' => 'rumah_terhuni',
            'luas_lahan' => 'luas_lahan_total',
            'volume_panen' => 'volume_panen_ton',
            'pengaduan_terbuka' => 'pengaduan_terbuka',
        ];
        $desimal = [
            'jumlah_kk' => 0, 'rumah_terhuni' => 0, 'luas_lahan' => 2,
            'volume_panen' => 2, 'pengaduan_terbuka' => 0,
        ];

        $baris = array_map(fn (array $b): array => [
            'satuan_permukiman_id' => $b['satuan_permukiman_id'],
            'satuan_permukiman' => $b['satuan_permukiman'],
        ], $dasar);

        foreach ($petaKawasan as $kolomSp => $kolomKawasan) {
            $porsi = [];
            foreach ($dasar as $i => $b) {
                $porsi[$i] = $kawasanAkhir[$kolomKawasan] > 0
                    ? $b[$kolomSp] / $kawasanAkhir[$kolomKawasan]
                    : 0.0;
            }

            $bagi = self::bagiProporsional($porsi, (float) $kawasanTahun[$kolomKawasan], $desimal[$kolomSp]);

            foreach ($bagi as $i => $nilai) {
                $baris[$i][$kolomSp] = $desimal[$kolomSp] === 0 ? (int) $nilai : $nilai;
            }
        }

        return array_values($baris);
    }

    /**
     * Field iklim satu SP untuk satu tahun (Putaran 5). Hanya dua belas field
     * iklim yang bergerak; seluruh field geografi (koordinat, jarak, batas,
     * SK, pola, tanah, air) TETAP -- geografi tidak berubah antar tahun.
     *
     * `iklimSpTahun($id, tahunAkhir)` == nilai `keadaanWilayahSp()` apa adanya.
     * Tahun lain: goyangan DETERMINISTIK kecil (bukan `rand()`, supaya uji
     * stabil) di sekitar nilai dasar -- angka contoh, bukan pengamatan.
     *
     * @return array<string, float|null>
     */
    public static function iklimSpTahun(int $id, int $tahun): array
    {
        $fieldIklim = [
            'curah_hujan_tahunan_mm', 'curah_hujan_bulan_min_mm', 'curah_hujan_bulan_maks_mm',
            'suhu_min_c', 'suhu_maks_c', 'suhu_rata_c',
            'angin_min_knot', 'angin_maks_knot', 'angin_rata_knot',
            'penyinaran_min_persen', 'penyinaran_maks_persen', 'penyinaran_rata_persen',
        ];

        $dasar = array_intersect_key(self::keadaanWilayahSp()[$id] ?? [], array_flip($fieldIklim));
        $tahunAkhir = (int) end(self::deretTahunan()['tahun']);

        if ($tahun === $tahunAkhir) {
            return $dasar;
        }

        // Tren ringan menjauh dari tahun terakhir + derau kecil; ditentukan id
        // SP dan tahun, sama tiap panggilan, dan tidak pernah tepat nol untuk
        // tahun lampau (0,006 x jarak bukan kelipatan 0,005).
        $jarak = $tahunAkhir - $tahun;
        $derau = ((($id * 13 + $tahun * 7) % 9) - 4) / 200;
        $goyang = $jarak * 0.006 + $derau;

        return array_map(
            fn ($nilai) => $nilai === null ? null : round($nilai * (1 + $goyang), 2),
            $dasar,
        );
    }

    /**
     * Jumlah jiwa per SP (Putaran 6). Diturunkan dari porsi KP SP terhadap
     * kawasan, dikalikan `ringkasanDashboard()['jumlah_jiwa']`, dengan koreksi
     * sisa supaya Sigma keenam SP tepat sama dengan angka kawasan.
     *
     * Data contoh tidak menyimpan angka jiwa per SP; ia dihitung di sini agar
     * satu-satunya sumber tetap `ringkasanDashboard()` (rules.md 19a: tidak
     * menghitung dari cacah baris contoh).
     *
     * @return array<int, int> Peta id_satuan_permukiman ke jumlah jiwa
     */
    public static function jiwaPerSp(): array
    {
        $rekap = self::rekapPerSp();
        $totalKk = array_sum(array_column($rekap, 'jumlah_kk'));
        $totalJiwa = self::ringkasanDashboard()['jumlah_jiwa'];

        $porsi = [];
        foreach ($rekap as $r) {
            $porsi[$r['satuan_permukiman_id']] = $totalKk > 0 ? $r['jumlah_kk'] / $totalKk : 0;
        }

        return array_map('intval', self::bagiProporsional($porsi, (float) $totalJiwa, 0));
    }

    /**
     * Struktur penduduk satu SP menurut kelompok umur lima tahunan (Putaran 6).
     *
     * ANGKA CONTOH TURUNAN, bukan pendataan per orang: sistem Tahap 2 belum
     * mendata umur seluruh penduduk, hanya kepala keluarga dan anggota yang
     * tercatat. Sebaran memakai bentuk piramida penduduk muda yang lazim di
     * kawasan transmigrasi, digoyang deterministik menurut id SP (pola
     * `iklimSpTahun()`), dengan koreksi sisa supaya jumlah seluruh sel tepat
     * sama dengan `jiwaPerSp()`.
     *
     * @return array<int, array{kelompok: string, laki: int, perempuan: int, jumlah: int}>
     */
    public static function strukturUmurSp(int $id): array
    {
        $kelompok = [
            '0ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“4', '5ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“9', '10ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“14', '15ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“19', '20ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“24', '25ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“29', '30ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“34',
            '35ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“39', '40ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“44', '45ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“49', '50ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“54', '55ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“59', '60ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“64', '65+',
        ];

        // Bobot piramida: balita dan usia produktif besar, lansia kecil.
        $bobotDasar = [11, 10.5, 10, 9.2, 8.6, 8, 7.4, 6.6, 5.8, 4.8, 4, 3.2, 2.4, 2.7];
        $jiwa = self::jiwaPerSp()[$id] ?? 0;

        $bobot = [];
        foreach ($bobotDasar as $i => $b) {
            $derau = ((($id * 7 + $i * 13) % 7) - 3) / 100;
            $bobot[$i] = $b * (1 + $derau);
        }
        $totalBobot = array_sum($bobot);

        $porsi = [];
        foreach ($bobot as $i => $b) {
            $porsi[$i] = $totalBobot > 0 ? $b / $totalBobot : 0;
        }
        $perKelompok = self::bagiProporsional($porsi, (float) $jiwa, 0);

        $baris = [];
        foreach ($kelompok as $i => $label) {
            $n = (int) $perKelompok[$i];
            $porsiLaki = 0.51 + ((($id * 5 + $i * 11) % 5) - 2) / 100;
            $bagi = self::bagiProporsional(
                ['laki' => $porsiLaki, 'perempuan' => 1 - $porsiLaki],
                (float) $n,
                0,
            );

            $baris[] = [
                'kelompok' => $label,
                'laki' => (int) $bagi['laki'],
                'perempuan' => (int) $bagi['perempuan'],
                'jumlah' => $n,
            ];
        }

        return $baris;
    }

    /**
     * Mutasi penduduk satu SP, KUMULATIF sejak tahun penempatan (Putaran 6).
     *
     * ANGKA CONTOH TURUNAN: dihitung dari jumlah jiwa SP dan lama sejak
     * penempatan memakai laju kasar per penduduk per tahun, digoyang
     * deterministik menurut id SP. TANPA baris perkawinan (dikecualikan
     * pemilik proyek). Kematian dan kepindahan anggota keluarga yang benar-
     * benar tercatat (`anggota_keluarga.status`) ikut ditambahkan supaya
     * peristiwa yang didata petugas tetap terlihat pada laporan.
     *
     * @return array{baris: array<int, array{jenis: string, laki: int, perempuan: int, jumlah: int}>, bersih: int}
     */
    public static function mutasiPendudukSp(int $id): array
    {
        $sp = self::cariSp($id);
        $tahunAkhir = (int) end(self::deretTahunan()['tahun']);
        $tahunTempat = $sp['tahun_penempatan'] ?? $tahunAkhir;
        $lama = max(1, $tahunAkhir - $tahunTempat);
        $jiwa = self::jiwaPerSp()[$id] ?? 0;

        // Laju kasar per penduduk per tahun; digoyang +-15% menurut id SP.
        $goyang = 1 + ((($id * 17) % 7) - 3) / 20;
        $hitung = fn (float $laju) => (int) round($jiwa * $lama * $laju * $goyang);

        // Peristiwa anggota keluarga yang sungguh tercatat di SP ini.
        $transmigranSp = array_column(
            array_filter(self::transmigran(), fn ($t) => $t['satuan_permukiman_id'] === $id),
            null,
            'id_transmigran',
        );
        $meninggalDicatat = ['Laki-laki' => 0, 'Perempuan' => 0];
        $pindahDicatat = ['Laki-laki' => 0, 'Perempuan' => 0];
        foreach (self::anggotaKeluarga() as $a) {
            if (! isset($transmigranSp[$a['transmigran_id']])) {
                continue;
            }
            $jk = $a['jenis_kelamin'] ?? 'Laki-laki';
            if ($a['status'] === StatusAnggotaKeluarga::Meninggal->value) {
                $meninggalDicatat[$jk] = ($meninggalDicatat[$jk] ?? 0) + 1;
            } elseif ($a['status'] === StatusAnggotaKeluarga::Pindah->value) {
                $pindahDicatat[$jk] = ($pindahDicatat[$jk] ?? 0) + 1;
            }
        }

        $pisah = function (int $total, int $tambahanL = 0, int $tambahanP = 0): array {
            $l = (int) round($total * 0.51) + $tambahanL;
            $p = $total - (int) round($total * 0.51) + $tambahanP;

            return [$l, $p];
        };

        [$lahirL, $lahirP] = $pisah($hitung(0.020));
        [$datangL, $datangP] = $pisah($hitung(0.004));
        [$matiL, $matiP] = $pisah($hitung(0.006), $meninggalDicatat['Laki-laki'], $meninggalDicatat['Perempuan']);
        [$pindahL, $pindahP] = $pisah($hitung(0.004), $pindahDicatat['Laki-laki'], $pindahDicatat['Perempuan']);
        [$keluarL, $keluarP] = $pisah($hitung(0.003));

        $baris = [
            ['jenis' => 'Kelahiran', 'laki' => $lahirL, 'perempuan' => $lahirP],
            ['jenis' => 'Transmigran datang (pengganti atau spontan)', 'laki' => $datangL, 'perempuan' => $datangP],
            ['jenis' => 'Kematian', 'laki' => $matiL, 'perempuan' => $matiP],
            ['jenis' => 'Pindah keluar keluarga', 'laki' => $pindahL, 'perempuan' => $pindahP],
            ['jenis' => 'Meninggalkan lokasi', 'laki' => $keluarL, 'perempuan' => $keluarP],
        ];

        $baris = array_map(fn ($b) => $b + ['jumlah' => $b['laki'] + $b['perempuan']], $baris);

        $bersih = ($lahirL + $lahirP) + ($datangL + $datangP)
            - ($matiL + $matiP) - ($pindahL + $pindahP) - ($keluarL + $keluarP);

        return ['baris' => $baris, 'bersih' => $bersih];
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
        // Izin `export` dicabut 2026-08-17: ekspor mengikuti `lihat`, sebab ia
        // hanya cara lain membaca data yang sudah boleh dilihat. Akibatnya
        // `penuh` dan `kelolaSaja` menjadi satu, sehingga tinggal satu nama.
        $penuh = ['lihat', 'tambah', 'ubah', 'hapus'];
        $bacaSaja = ['lihat'];

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
                    ['kunci' => 'role', 'nama' => 'Pengaturan role', 'aksi' => $penuh],
                    ['kunci' => 'audit_log', 'nama' => 'Audit log', 'aksi' => $bacaSaja],
                    // Pengelolaan Konten (CMS 5 tab). Isinya disunting, bukan
                    // ditambah/dihapus: himpunan blok kontennya tetap.
                    ['kunci' => 'cms', 'nama' => 'Pengelolaan konten', 'aksi' => ['lihat', 'ubah']],
                ],
            ],
            [
                'kelompok' => 'Wilayah dan SP',
                'modul' => [
                    ['kunci' => 'wilayah', 'nama' => 'Data master wilayah', 'aksi' => $penuh],
                    ['kunci' => 'kawasan', 'nama' => 'Kawasan transmigrasi', 'aksi' => $penuh],
                    ['kunci' => 'sp', 'nama' => 'Satuan permukiman (SP)', 'aksi' => $penuh],
                    // Inventaris dan fasilitas sengaja dipisah: dua tabel, dua
                    // halaman, sehingga kewenangannya dapat dibedakan
                    // (rules.md 5.1 catatan 5).
                    ['kunci' => 'inventaris_sp', 'nama' => 'Inventaris SP', 'aksi' => $penuh],
                    ['kunci' => 'fasilitas_sp', 'nama' => 'Fasilitas SP', 'aksi' => $penuh],
                    ['kunci' => 'satuan', 'nama' => 'Data master satuan', 'aksi' => $penuh],
                    // Tanpa hapus: nilai referensi dinonaktifkan, bukan
                    // dihapus, agar data lama yang memakainya tetap terbaca
                    // (kamus data 5.6).
                    ['kunci' => 'referensi', 'nama' => 'Data master referensi', 'aksi' => $tanpaHapus],
                    // Hanya lihat dan ubah. Parameter penilaian TIDAK dapat
                    // ditambah maupun dihapus lewat antarmuka: barisnya
                    // dihasilkan dari jenis infrastruktur dan fasilitas, dan
                    // status kondisi wajib tetap tiga sebab `dariSkor()` hanya
                    // mengembalikan tiga keluaran.
                    ['kunci' => 'penilaian_kondisi', 'nama' => 'Penilaian kondisi SP', 'aksi' => ['lihat', 'ubah']],
                ],
            ],
            [
                'kelompok' => 'Kependudukan',
                'modul' => [
                    ['kunci' => 'transmigran', 'nama' => 'Transmigran', 'aksi' => $penuh],
                    ['kunci' => 'rumah', 'nama' => 'Rumah dan hunian', 'aksi' => $penuh],
                    ['kunci' => 'riwayat_penghunian', 'nama' => 'Riwayat penghunian', 'aksi' => $penuh],
                    // Tanpa hapus: riwayat suksesi menyatakan siapa pemegang
                    // jatah lahan pada rentang waktu tertentu, sehingga
                    // menghapusnya menghilangkan dasar penguasaan lahan
                    // (rules.md 5.1 catatan 8).
                    ['kunci' => 'riwayat_kepala_keluarga', 'nama' => 'Riwayat kepala keluarga', 'aksi' => $tanpaHapus],
                ],
            ],
            [
                'kelompok' => 'Lahan',
                'modul' => [
                    ['kunci' => 'lahan', 'nama' => 'Lahan', 'aksi' => $penuh],
                ],
            ],
            [
                'kelompok' => 'Kelembagaan',
                'modul' => [
                    ['kunci' => 'poktan', 'nama' => 'Kelompok tani', 'aksi' => $penuh],
                    ['kunci' => 'anggota_poktan', 'nama' => 'Anggota poktan', 'aksi' => $tanpaHapus],
                    ['kunci' => 'alsintan', 'nama' => 'Alsintan', 'aksi' => $penuh],
                    ['kunci' => 'saprotan', 'nama' => 'Saprotan', 'aksi' => $penuh],
                ],
            ],
            [
                'kelompok' => 'Pertanian',
                'modul' => [
                    ['kunci' => 'komoditas', 'nama' => 'Komoditas', 'aksi' => $penuh],
                    ['kunci' => 'penanaman', 'nama' => 'Penanaman', 'aksi' => $penuh],
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
                    ['kunci' => 'dashboard', 'nama' => 'Dashboard', 'aksi' => $bacaSaja],
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
        // Sejak izin `export` dicabut (2026-08-17), pola yang dulu berbeda
        // hanya karena huruf E kini bertemu: `lituhe` menjadi sama dengan
        // `k`, dan `ltue` menjadi sama dengan `ltu`. Nama lamanya tidak
        // dipertahankan sebagai alias agar tidak ada dua nama untuk satu hal.
        $k = ['lihat', 'tambah', 'ubah', 'hapus'];
        $ltu = ['lihat', 'tambah', 'ubah'];
        $lt = ['lihat', 'tambah'];
        $l = ['lihat'];

        $peta = [
            // Admin. Kolom kedua tabel rules.md 5.1.
            1 => [
                'wilayah' => $k, 'kawasan' => $k, 'sp' => $k,
                'inventaris_sp' => $k, 'fasilitas_sp' => $k, 'satuan' => $k, 'referensi' => $ltu,
                'penilaian_kondisi' => ['lihat', 'ubah'],
                'transmigran' => $k, 'rumah' => $k, 'riwayat_penghunian' => $k,
                // Riwayat suksesi tidak dapat dihapus siapa pun, termasuk
                // Admin: ia menyatakan siapa pemegang jatah lahan pada rentang
                // waktu tertentu (rules.md 5.1 catatan 8).
                'riwayat_kepala_keluarga' => $ltu,
                'lahan' => $k,
                'poktan' => $k, 'anggota_poktan' => $ltu, 'alsintan' => $k, 'saprotan' => $k,
                'komoditas' => $k, 'penanaman' => $k, 'hasil_panen' => $k,
                'infrastruktur' => $k, 'pengaduan' => $k, 'penanganan_pengaduan' => $ltu,
                'dashboard' => $l,
                // Akun tidak pernah dihapus, hanya dinonaktifkan, sehingga
                // modul pengguna berhenti di ubah (rules.md 14b poin 16).
                'pengguna' => $ltu, 'role' => $k, 'audit_log' => $l,
                'cms' => ['lihat', 'ubah'],
            ],
            // Dinas Transmigrasi. Mengelola wilayah, kependudukan, dan lahan.
            // Pada modul pertanian hanya dapat melihat. Memegang CMS bersama
            // Admin: dinas pengelola kawasan yang menyunting isi halaman publik.
            2 => [
                'wilayah' => $l, 'kawasan' => $l, 'sp' => $ltu,
                'inventaris_sp' => $ltu, 'fasilitas_sp' => $ltu, 'satuan' => $l, 'referensi' => $ltu,
                'penilaian_kondisi' => ['lihat', 'ubah'],
                'transmigran' => $ltu, 'rumah' => $ltu, 'riwayat_penghunian' => $lt,
                'riwayat_kepala_keluarga' => $lt,
                'lahan' => $ltu,
                'poktan' => $l, 'anggota_poktan' => $l, 'alsintan' => $l, 'saprotan' => $l,
                'komoditas' => $l, 'penanaman' => $l, 'hasil_panen' => $l,
                'infrastruktur' => $ltu, 'pengaduan' => $ltu, 'penanganan_pengaduan' => $ltu,
                'dashboard' => $l,
                'cms' => ['lihat', 'ubah'],
            ],
            // Dinas Pertanian. Mengelola kelembagaan dan produksi pertanian.
            // Pada modul kependudukan hanya dapat melihat.
            3 => [
                'wilayah' => $l, 'kawasan' => $l, 'sp' => $l,
                'inventaris_sp' => $l, 'fasilitas_sp' => $l, 'satuan' => $l, 'referensi' => $l,
                'penilaian_kondisi' => $l,
                'transmigran' => $l, 'rumah' => $l, 'riwayat_penghunian' => $l,
                'riwayat_kepala_keluarga' => $l,
                'lahan' => $l,
                'poktan' => $ltu, 'anggota_poktan' => $ltu, 'alsintan' => $ltu, 'saprotan' => $ltu,
                'komoditas' => $ltu,
                'penanaman' => $ltu, 'hasil_panen' => $ltu,
                'infrastruktur' => $ltu, 'pengaduan' => $ltu, 'penanganan_pengaduan' => $ltu,
                'dashboard' => $l,
            ],
            // Operator SP. Memasukkan data, sengaja tanpa izin hapus
            // (rules.md 5.1 catatan 4). Tidak memegang izin apa pun pada
            // penanganan pengaduan.
            4 => [
                'wilayah' => $l, 'kawasan' => $l, 'sp' => $l,
                'inventaris_sp' => $ltu, 'fasilitas_sp' => $ltu, 'satuan' => $l, 'referensi' => $l,
                'penilaian_kondisi' => $l,
                'transmigran' => $ltu, 'rumah' => $ltu, 'riwayat_penghunian' => $lt,
                'riwayat_kepala_keluarga' => $l,
                'lahan' => $ltu,
                'poktan' => $ltu, 'anggota_poktan' => $ltu, 'alsintan' => $ltu, 'saprotan' => $ltu,
                'komoditas' => $l, 'penanaman' => $ltu, 'hasil_panen' => $ltu,
                'infrastruktur' => $ltu, 'pengaduan' => $lt,
                'dashboard' => $l,
            ],
            // Pendamping Lapangan. Role buatan Admin, bukan bawaan sistem.
            // Hanya membaca, sehingga dapat diberikan kepada pendamping yang
            // bertugas sementara tanpa risiko data berubah.
            5 => [
                'sp' => $l, 'inventaris_sp' => $l, 'fasilitas_sp' => $l,
                'transmigran' => $l, 'rumah' => $l, 'riwayat_penghunian' => $l,
                'lahan' => $l, 'poktan' => $l, 'anggota_poktan' => $l,
                'alsintan' => $l, 'saprotan' => $l, 'komoditas' => $l,
                'penanaman' => $l, 'hasil_panen' => $l,
                'infrastruktur' => $l, 'dashboard' => $l,
            ],
        ];

        return $peta[$roleId] ?? [];
    }
}
