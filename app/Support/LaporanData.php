<?php

namespace App\Support;

use App\Enums\StatusTinggal;
use Illuminate\Support\Carbon;

/**
 * Penyusun data untuk halaman-halaman di menu "Laporan".
 *
 * Ditambahkan 2026-08-28 (Tahap 2c). View halaman laporan DILARANG memanggil
 * `DummyData` langsung (penjaga Ide C); seluruhnya lewat kelas ini, sehingga
 * ada satu tempat untuk memeriksa bentuk datanya dan menjaganya lewat uji.
 *
 * Lima laporan mengikuti berkas rujukan di `refs/` (Laporan Hasil Panen dari
 * "Lap. Akhir Panen Jagung Polri MT. I 2025", Alsintan dan Saprotan dari dua
 * berkas gambar, Monografi SP dari "LAPORAN MONOGRAFI UPT KAPITAN MEO 2025",
 * Daftar Poktan dari "Poktan Wilayah Transmigrasi.xlsx"); dua sisanya (Rekap
 * Indikator Kawasan dan Daftar Transmigran) dirancang dari kolom data yang
 * sudah ada.
 *
 * Pengelompokan memakai Satuan Permukiman sebagai unit, bukan kecamatan
 * seperti berkas rujukan, sebab seluruh sistem ini berpusat pada SP dan satu
 * kawasan Kobalima Timur memuat beberapa SP. Kolom Kecamatan dan Desa tetap
 * ditampilkan.
 */
class LaporanData
{
    /**
     * Ambang jumlah kolom yang membuat satu laporan dicetak landscape.
     *
     * A4 potret menyisakan lebar isi sekitar 190mm, yang nyaman menampung
     * delapan kolom teks tabular. Laporan berkolom lebih banyak dipaksa
     * digulir mendatar bila tetap potret, dan itulah keluhan yang memicu
     * Putaran 3 D2b.
     *
     * Ambang ini dijaga uji yang MENGHITUNG ULANG jumlah kolom dari HTML
     * terender, sehingga menambah kolom tanpa memperbarui `meta()` memerah.
     */
    public const KOLOM_LANDSCAPE = 9;

    /**
     * Orientasi kertas satu laporan, diturunkan dari jumlah kolomnya.
     *
     * Bukan dipilih tangan per laporan: yang menentukan memang lebar tabel,
     * sehingga menuliskannya dua kali hanya membuka peluang keduanya
     * berselisih.
     */
    public static function orientasi(string $slug): string
    {
        return (self::meta($slug)['kolom'] ?? 0) >= self::KOLOM_LANDSCAPE
            ? 'landscape'
            : 'portrait';
    }

    /**
     * Metadata tiap laporan: judul, izin, cakupan, dasar periode, tautan
     * sumber, catatan, dan jumlah kolom tabel terlebarnya.
     *
     * **Satu-satunya sumber nama dan urutan laporan** (disatukan Putaran 4,
     * 2026-08-29). Dulu nama ditulis dua kali (MenuHelper + routes/web.php)
     * dan slugnya tiga kali, tanpa pengunci. Sekarang: MenuHelper membangun
     * submenu dari sini, routes menurunkan daftar rutenya dari sini, dan
     * `kerangka-laporan` membaca judul dari sini. **Urutan larik = urutan
     * submenu sidebar.**
     *
     * Kunci `kolom` (D2b) adalah jumlah kolom tabel TERLEBAR laporan itu, dan
     * dari situlah orientasi kertasnya diturunkan (lihat `orientasi()`).
     * Nilainya dijaga uji yang menghitung ulang dari HTML terender.
     *
     * @param  string|null  $slug  Null mengembalikan seluruh peta
     * @return array<string, mixed>
     */
    public static function meta(?string $slug = null): array
    {
        $jumlahSp = DummyData::kawasan()[0]['jumlah_sp'] ?? count(DummyData::satuanPermukiman());

        $semua = [
            'indikator-kawasan' => [
                'judul' => 'Rekap Indikator Kawasan',
                'izin' => 'dashboard.lihat',
                'cakupan' => 'Seluruh kawasan transmigrasi Kobalima Timur, gabungan '.$jumlahSp.' satuan permukiman.',
                'dasarPeriode' => 'Keadaan terkini kawasan; indikator produksi memakai tahun panen berjalan, bukan tahun pengadaan bantuan.',
                'sumberLabel' => 'Dashboard',
                'sumberRute' => 'beranda',
                'catatan' => null,
                'kolom' => 6,
            ],
            'monografi-sp' => [
                'judul' => 'Laporan Monografi SP',
                'izin' => 'sp.lihat',
                'cakupan' => 'Seluruh satuan permukiman di kawasan transmigrasi Kobalima Timur.',
                'dasarPeriode' => 'Potret keadaan terkini tiap SP pada tahun berjalan, bukan rekap lintas tahun.',
                'sumberLabel' => 'Data Satuan Permukiman',
                'sumberRute' => 'sp.index',
                'catatan' => 'Bab II "Keadaan Wilayah" diisi lewat modul Satuan Permukiman. Bagian yang belum diisi tetap tampil dengan penanda "belum dicatat".',
                'kolom' => 13,
            ],
            'transmigran' => [
                'judul' => 'Laporan Transmigran',
                'izin' => 'transmigran.lihat',
                'cakupan' => 'Seluruh kepala keluarga transmigran di kawasan Kobalima Timur, beserta data rumah dan lahannya.',
                'dasarPeriode' => 'Potret keadaan terkini seluruh kepala keluarga transmigran.',
                'sumberLabel' => 'Data Transmigran',
                'sumberRute' => 'transmigran.index',
                'catatan' => null,
                'kolom' => 14,
            ],
            'poktan' => [
                'judul' => 'Laporan Poktan',
                'izin' => 'poktan.lihat',
                'cakupan' => 'Seluruh kelompok tani beserta anggotanya di kawasan transmigrasi Kobalima Timur.',
                'dasarPeriode' => 'Potret keadaan terkini kelembagaan tani, bukan rekap lintas tahun.',
                'sumberLabel' => 'Data Kelompok Tani',
                'sumberRute' => 'poktan.index',
                'catatan' => null,
                'kolom' => 9,
            ],
            'alsintan' => [
                'judul' => 'Laporan Alsintan',
                'izin' => 'alsintan.lihat',
                'cakupan' => 'Alat dan mesin pertanian milik seluruh kelompok tani di kawasan Kobalima Timur.',
                'dasarPeriode' => 'Dikelompokkan menurut tahun pengadaan bantuan (tahun anggaran).',
                'sumberLabel' => 'Data Alsintan',
                'sumberRute' => 'alsintan.index',
                'catatan' => null,
                'kolom' => 9,
            ],
            'saprotan' => [
                'judul' => 'Laporan Saprotan',
                'izin' => 'saprotan.lihat',
                'cakupan' => 'Penyaluran benih, pupuk, pestisida, dan mulsa kepada petani di kawasan Kobalima Timur.',
                'dasarPeriode' => 'Dikelompokkan menurut tahun pengadaan bantuan (tahun anggaran), sesuai kolom tahun_pengadaan pada data saprotan.',
                'sumberLabel' => 'Data Saprotan',
                'sumberRute' => 'saprotan.index',
                'catatan' => 'Jadwal tanam adalah rencana dari berita acara penyaluran, bukan realisasi. Selisihnya dengan periode tanam yang sebenarnya justru berguna diamati.',
                'kolom' => 15,
            ],
            'hasil-panen' => [
                'judul' => 'Laporan Hasil Panen',
                'izin' => 'hasil_panen.lihat',
                'cakupan' => 'Seluruh satuan permukiman di kawasan transmigrasi Kobalima Timur.',
                'dasarPeriode' => 'Dikelompokkan menurut tahun pengadaan bantuan (tahun anggaran), bukan tahun panen.',
                'sumberLabel' => 'Data Hasil Panen',
                'sumberRute' => 'panen.index',
                'catatan' => 'Bagian benih menampilkan rantai penuh dari bantuan sampai hasil panennya. Bantuan pupuk tidak tertaut ke satu penanaman tertentu, sehingga hanya tampil pada Laporan Saprotan.',
                'kolom' => 16,
            ],
        ];

        return $slug === null ? $semua : ($semua[$slug] ?? []);
    }

    /**
     * SP menurut id, untuk melacak kecamatan dan desa satu poktan.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function petaSp(): array
    {
        static $peta = null;

        return $peta ??= collect(DummyData::satuanPermukiman())
            ->keyBy('id_satuan_permukiman')
            ->all();
    }

    /**
     * Poktan menurut id, dengan nama dan NIK ketua yang sudah diselesaikan.
     *
     * Ketua yang berasal dari transmigran tidak menyimpan namanya pada baris
     * poktan (rules.md 7a); namanya dibaca lewat `ketua_transmigran_id`.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function petaPoktan(): array
    {
        static $peta = null;

        if ($peta !== null) {
            return $peta;
        }

        $transmigran = collect(DummyData::transmigran())->keyBy('id_transmigran');

        $peta = [];

        foreach (DummyData::poktan() as $p) {
            $namaKetua = $p['nama_ketua'] ?: '-';
            $nikKetua = $p['nik_ketua'] ?: null;

            if (! $p['nama_ketua'] && $p['ketua_transmigran_id']) {
                $t = $transmigran->get($p['ketua_transmigran_id']);
                if ($t) {
                    $namaKetua = $t['nama_kepala_keluarga'];
                    $nikKetua = $t['nik'];
                }
            }

            $sp = self::petaSp()[$p['satuan_permukiman_id']] ?? null;

            $peta[$p['id_poktan']] = $p + [
                'nama_ketua_terpakai' => $namaKetua,
                'nik_ketua_terpakai' => $nikKetua,
                'kecamatan' => $sp['kecamatan'] ?? '-',
                'desa' => $sp['desa'] ?? '-',
            ];
        }

        return $peta;
    }

    /** @return array<int, array<string, mixed>> */
    private static function petaPenanaman(): array
    {
        static $peta = null;

        return $peta ??= collect(DummyData::penanaman())->keyBy('id_penanaman')->all();
    }

    /** @return array<int, array<string, mixed>> */
    private static function petaSaprotan(): array
    {
        static $peta = null;

        return $peta ??= collect(DummyData::saprotan())->keyBy('id_saprotan')->all();
    }

    /**
     * Jumlah anggota aktif per poktan, dihitung dari keanggotaan sungguhan
     * alih-alih memakai kolom `jumlah_anggota` yang dapat basi (notes.md 1a.7).
     *
     * @return array<int, int>
     */
    private static function anggotaAktifPerPoktan(): array
    {
        static $peta = null;

        if ($peta !== null) {
            return $peta;
        }

        $peta = [];

        foreach (DummyData::anggotaPoktan() as $a) {
            if ($a['status'] === 'Aktif') {
                $peta[$a['poktan_id']] = ($peta[$a['poktan_id']] ?? 0) + 1;
            }
        }

        return $peta;
    }

    /**
     * Laporan Hasil Panen.
     *
     * Mengikuti kolom "Lap. Akhir Panen Jagung Polri MT. I 2025": satu baris
     * per catatan panen, dikelompokkan per SP dengan subtotal, lalu total
     * kawasan. Belum Dipanen dihitung: realisasi tanam - realisasi panen -
     * puso. Produksi diseragamkan ke ton lewat `DummyData::keTon()`.
     *
     * @return array{kelompok: array<int, mixed>, total: array<string, float>}
     */
    public static function hasilPanen(): array
    {
        $poktan = self::petaPoktan();
        $penanaman = self::petaPenanaman();
        $saprotan = self::petaSaprotan();
        $anggotaAktif = self::anggotaAktifPerPoktan();

        $baris = [];

        foreach (DummyData::hasilPanen() as $h) {
            $tanam = $penanaman[$h['penanaman_id']] ?? null;
            $pok = $poktan[$h['poktan_id']] ?? null;

            if (! $tanam || ! $pok) {
                continue;
            }

            $benih = $saprotan[$tanam['saprotan_id']] ?? null;

            $realisasiTanam = (float) $tanam['realisasi_tanam'];
            $realisasiPanen = (float) $h['realisasi_panen'];
            $puso = (float) $h['puso'];
            $belumDipanen = max(0.0, round($realisasiTanam - $realisasiPanen - $puso, 2));

            $baris[] = [
                'sp_id' => $pok['satuan_permukiman_id'],
                'sp' => $pok['satuan_permukiman'],
                'poktan_id' => $pok['id_poktan'],
                'kecamatan' => $pok['kecamatan'],
                'desa' => $pok['desa'],
                'poktan' => $pok['nama'],
                'ketua' => $pok['nama_ketua_terpakai'],
                'jumlah_anggota' => $anggotaAktif[$pok['id_poktan']] ?? 0,
                'komoditas' => $h['komoditas'],
                'varietas' => $benih['varietas'] ?? '-',
                'tahun_pengadaan' => $benih['tahun_pengadaan'] ?? null,
                'volume_benih' => (float) ($tanam['volume_benih'] ?? 0),
                'realisasi_tanam' => $realisasiTanam,
                'realisasi_panen' => $realisasiPanen,
                'puso' => $puso,
                'belum_dipanen' => $belumDipanen,
                'produktivitas' => (float) $h['produktivitas'],
                'produksi_ton' => round(DummyData::keTon((float) $h['produksi'], $h['satuan']), 2),
            ];
        }

        return self::kelompokkanPerSp($baris, [
            'volume_benih', 'realisasi_tanam', 'realisasi_panen',
            'puso', 'belum_dipanen', 'produksi_ton',
        ], 'produktivitas_tertimbang');
    }

    /**
     * Laporan Alsintan.
     *
     * Kolom "laporan alsintan.jpeg": Jenis Alat, Sumber Dana, Tahun Pengadaan,
     * Poktan Penerima, Ketua Poktan, Alamat (Kec./Desa), Jumlah (Unit). Satu
     * baris per alsintan, dikelompokkan per SP, subtotal Jumlah.
     *
     * Field alsintan bernama `sumber_dana` dan `tahun_pengadaan` sejak
     * diseragamkan 2026-08-28 (dulu `sumber_perolehan` / `tahun_perolehan`),
     * cocok dengan saprotan dan kamus data §8.3.
     *
     * @return array{kelompok: array<int, mixed>, total: array<string, float>}
     */
    public static function alsintan(): array
    {
        $poktan = self::petaPoktan();

        $baris = [];

        foreach (DummyData::alsintan() as $a) {
            $pok = $poktan[$a['poktan_id']] ?? null;

            $baris[] = [
                'sp_id' => $a['satuan_permukiman_id'],
                'sp' => $a['satuan_permukiman'],
                'poktan_id' => $a['poktan_id'],
                'kecamatan' => $pok['kecamatan'] ?? '-',
                'desa' => $pok['desa'] ?? '-',
                'jenis_alat' => $a['nama_alat'],
                'sumber_dana' => $a['sumber_dana'],
                'tahun_pengadaan' => $a['tahun_pengadaan'],
                'poktan' => $a['pemilik'],
                'ketua' => $pok['nama_ketua_terpakai'] ?? '-',
                'jumlah' => (int) $a['jumlah'],
            ];
        }

        return self::kelompokkanPerSp($baris, ['jumlah']);
    }

    /**
     * Laporan Saprotan, dua bagian terpisah (rules.md 9 poin 16, notes 1m.4).
     *
     * Bagian benih mengikuti kolom "laporan saprotan.jpeg". Bagian non-benih
     * (pupuk, pestisida, mulsa) hanya penyalurannya, sebab sarana itu tidak
     * tertaut ke satu penanaman tertentu.
     *
     * @return array{benih: array, nonBenih: array}
     */
    public static function saprotan(): array
    {
        $poktan = self::petaPoktan();
        $anggotaAktif = self::anggotaAktifPerPoktan();
        $luasPoktan = [];

        $benih = [];
        $nonBenih = [];

        foreach (DummyData::saprotan() as $s) {
            $pok = $s['poktan_id'] ? ($poktan[$s['poktan_id']] ?? null) : null;

            if ($s['jenis'] === 'Benih') {
                $pid = $s['poktan_id'];
                $luasPoktan[$pid] ??= $pid ? DummyData::rekapLahanPoktan($pid)['luas_total'] : 0;

                $benih[] = [
                    'sp_id' => $pok['satuan_permukiman_id'] ?? $s['satuan_permukiman_id'] ?? null,
                    'sp' => $pok['satuan_permukiman'] ?? $s['satuan_permukiman'] ?? '-',
                    'poktan_id' => $s['poktan_id'],
                    'kecamatan' => $pok['kecamatan'] ?? '-',
                    'desa' => $pok['desa'] ?? '-',
                    'poktan' => $s['penerima'],
                    'ketua' => $pok['nama_ketua_terpakai'] ?? '-',
                    'nik_ketua' => $pok['nik_ketua_terpakai'] ?? '-',
                    'telepon_ketua' => $pok['telepon_ketua'] ?? '-',
                    'jumlah_anggota' => $pok ? ($anggotaAktif[$pok['id_poktan']] ?? 0) : 0,
                    'luas_lahan' => $pid ? ($luasPoktan[$pid] ?? 0) : 0,
                    'komoditas' => $s['komoditas'] ?? '-',
                    'varietas' => $s['varietas'] ?? '-',
                    'volume_benih' => (float) $s['jumlah'],
                    'satuan' => $s['satuan'],
                    'tahun_pengadaan' => $s['tahun_pengadaan'] ?? null,
                    'sumber_dana' => $s['sumber_dana'] ?? '-',
                    'jadwal_tanam' => $s['jadwal_tanam'] ?? null,
                ];

                continue;
            }

            $nonBenih[] = [
                'sp_id' => $pok['satuan_permukiman_id'] ?? $s['satuan_permukiman_id'] ?? null,
                'poktan_id' => $s['poktan_id'],
                'poktan' => $s['penerima'],
                'sp' => $pok['satuan_permukiman'] ?? $s['satuan_permukiman'] ?? '-',
                'jenis' => $s['jenis'],
                'volume' => (float) $s['jumlah'],
                'satuan' => $s['satuan'],
                'tahun_pengadaan' => $s['tahun_pengadaan'] ?? null,
                'sumber_dana' => $s['sumber_dana'] ?? '-',
            ];
        }

        return ['benih' => $benih, 'nonBenih' => $nonBenih];
    }

    /**
     * Angka desimal ringkas: koma sebagai pemisah desimal, titik ribuan.
     *
     * Satu-satunya perumus angka untuk seluruh halaman laporan (dulu tujuh
     * salinan berbeda tanda tangan di tiap view). Nol di ekor dibuang HANYA
     * bila ada bagian desimal; tanpa penjaga `$desimal > 0` itu, `rtrim`
     * memakan angka bulat "1.200" menjadi "1.2".
     */
    public static function angka(int|float|string|null $n, int $desimal = 2): string
    {
        $teks = number_format((float) $n, max(0, $desimal), ',', '.');

        return $desimal > 0 ? rtrim(rtrim($teks, '0'), ',') : $teks;
    }

    /**
     * Teks "min sampai maks satuan" untuk sepasang field numerik.
     *
     * Bentuk narasi Bab II Monografi ("kisaran 7,01-7,69"), tetapi tanda
     * hubung diganti kata "sampai" (ANTISLOP-ID R-02, tanpa em dash).
     */
    private static function rentang(array $s, string $min, string $maks, string $satuan = ''): ?string
    {
        if (($s[$min] ?? null) === null && ($s[$maks] ?? null) === null) {
            return null;
        }

        return trim(self::angka($s[$min] ?? 0).' sampai '.self::angka($s[$maks] ?? 0).' '.$satuan);
    }

    /**
     * Bab II "Keadaan Wilayah" satu SP, dikelompokkan sesuai urutan monografi.
     *
     * Nilai yang belum diisi dibiarkan null; view menampilkannya sebagai
     * "belum dicatat". Format angka disamakan dengan blok Keadaan Wilayah di
     * halaman dashboard SP.
     *
     * @return array<string, array<string, ?string>>
     */
    private static function bab2(array $s): array
    {
        $km = fn (string $k) => ($s[$k] ?? null) !== null ? self::angka($s[$k], 1).' km' : null;
        $rata = fn (string $k, string $satuan) => ($s[$k] ?? null) !== null
            ? ', rata-rata '.self::angka($s[$k], 1).' '.$satuan : '';

        $astronomis = ($s['lintang_utara'] ?? null) !== null
            ? number_format((float) $s['lintang_utara'], 6).' sampai '.number_format((float) $s['lintang_selatan'], 6).' LS, '
                .number_format((float) $s['bujur_barat'], 6).' sampai '.number_format((float) $s['bujur_timur'], 6).' BT'
            : null;

        $suhu = self::rentang($s, 'suhu_min_c', 'suhu_maks_c', 'derajat C');
        $angin = self::rentang($s, 'angin_min_knot', 'angin_maks_knot', 'knot');
        $sinar = self::rentang($s, 'penyinaran_min_persen', 'penyinaran_maks_persen', '%');

        return [
            'Letak' => [
                'Letak astronomis' => $astronomis,
                'Jarak ke Ibu Kota Kecamatan' => $km('jarak_ke_kecamatan_km'),
                'Jarak ke Ibu Kota Kabupaten' => $km('jarak_ke_kabupaten_km'),
                'Jarak ke Ibu Kota Provinsi' => $km('jarak_ke_provinsi_km'),
            ],
            'Batas Wilayah' => [
                'Sebelah Utara' => $s['batas_utara'] ?? null,
                'Sebelah Timur' => $s['batas_timur'] ?? null,
                'Sebelah Selatan' => $s['batas_selatan'] ?? null,
                'Sebelah Barat' => $s['batas_barat'] ?? null,
            ],
            'Luas dan Bentuk' => [
                'Luas wilayah' => ($s['luas_lahan'] ?? null) !== null ? self::angka($s['luas_lahan']).' ha' : null,
                'Nomor SK Pencadangan Areal' => $s['nomor_sk_pencadangan'] ?? null,
                'Tanggal SK Pencadangan' => ($s['tanggal_sk_pencadangan'] ?? null)
                    ? Carbon::parse($s['tanggal_sk_pencadangan'])->translatedFormat('d F Y') : null,
                'Pola permukiman' => $s['pola_permukiman'] ?? null,
            ],
            'Tanah dan Topografi' => [
                'Tingkat kesuburan tanah' => $s['tingkat_kesuburan_tanah'] ?? null,
                'pH tanah' => self::rentang($s, 'ph_tanah_min', 'ph_tanah_maks'),
                'Bentuk wilayah' => $s['bentuk_wilayah'] ?? null,
                'Kemiringan lereng' => self::rentang($s, 'kemiringan_min_persen', 'kemiringan_maks_persen', '%'),
            ],
            'Iklim' => [
                'Curah hujan rata-rata per tahun' => ($s['curah_hujan_tahunan_mm'] ?? null) !== null
                    ? number_format((float) $s['curah_hujan_tahunan_mm'], 2, ',', '.').' mm' : null,
                'Curah hujan bulanan' => self::rentang($s, 'curah_hujan_bulan_min_mm', 'curah_hujan_bulan_maks_mm', 'mm'),
                'Suhu udara' => $suhu !== null ? $suhu.$rata('suhu_rata_c', 'derajat C') : null,
                'Kecepatan angin' => $angin !== null ? $angin.$rata('angin_rata_knot', 'knot') : null,
                'Penyinaran matahari' => $sinar !== null ? $sinar.$rata('penyinaran_rata_persen', '%') : null,
            ],
            'Sumberdaya Air' => [
                'Sumber air bersih' => $s['sumber_air_bersih'] ?? null,
                'Sumber air pertanian' => $s['sumber_air_pertanian'] ?? null,
            ],
        ];
    }

    /**
     * Laporan Monografi SP.
     *
     * Dua lapis. `baris`: ikhtisar satu baris per SP (kependudukan, lahan,
     * produksi, pengaduan). `monografi`: Bab II "Keadaan Wilayah" tiap SP
     * (letak, batas, luas dan bentuk, tanah, topografi, iklim, sumberdaya
     * air) berikut rute pencapaiannya (Tabel 2.1). SP Kapitan Meo memakai
     * angka berkas monografi asli; SP lain nilai yang wajar. Bagian yang
     * belum diisi dibiarkan kosong dan ditandai "belum dicatat" oleh view.
     *
     * @return array{baris: array<int, array<string, mixed>>, monografi: array<int, array<string, mixed>>}
     */
    public static function monografiSp(): array
    {
        $rekap = collect(DummyData::rekapPerSp())->keyBy('satuan_permukiman_id');
        $kawasan = DummyData::kawasan()[0] ?? ['kabupaten' => '-', 'provinsi' => '-'];

        $poktanPerSp = [];
        foreach (DummyData::poktan() as $p) {
            $poktanPerSp[$p['satuan_permukiman_id']] = ($poktanPerSp[$p['satuan_permukiman_id']] ?? 0) + 1;
        }

        $lahanTergarap = [];
        foreach (DummyData::penanaman() as $t) {
            $lahanTergarap[$t['satuan_permukiman_id']] = ($lahanTergarap[$t['satuan_permukiman_id']] ?? 0)
                + (float) $t['realisasi_tanam'];
        }

        $baris = [];
        $monografi = [];

        foreach (DummyData::satuanPermukiman() as $s) {
            $id = $s['id_satuan_permukiman'];
            $r = $rekap->get($id);

            $baris[] = [
                'sp_id' => $id,
                'sp' => $s['nama'],
                'kode' => $s['kode_sp'],
                'kecamatan' => $s['kecamatan'],
                'desa' => $s['desa'],
                'tahun_penempatan' => $s['tahun_penempatan'],
                'luas_wilayah' => (float) $s['luas_lahan'],
                'kk_rencana' => (int) $s['jumlah_kk_rencana'],
                'kk_terisi' => (int) $s['jumlah_kk_terisi'],
                'rumah_terhuni' => $r['rumah_terhuni'] ?? 0,
                'poktan' => $poktanPerSp[$id] ?? 0,
                'lahan_tergarap' => round($lahanTergarap[$id] ?? 0, 2),
                'produksi_ton' => $r['volume_panen'] ?? 0,
                'pengaduan_terbuka' => $r['pengaduan_terbuka'] ?? 0,
            ];

            $kelompok = self::bab2($s);
            $adaIsi = collect($kelompok)->flatten()->contains(fn ($v) => $v !== null && trim((string) $v) !== '');

            $rute = array_map(fn ($x) => [
                'rute' => $x['rute'],
                'jarak_km' => $x['jarak_km'],
                'sarana_angkutan' => $x['sarana_angkutan'] ?? '-',
                'kondisi_jalan' => $x['kondisi_jalan'] ?? '-',
                'waktu_tempuh' => $x['waktu_tempuh'] ?? '-',
                'ongkos_rp' => $x['ongkos_rp'],
                'keterangan' => $x['keterangan'] ?? null,
            ], DummyData::ruteAksesibilitasSp($id));

            $monografi[] = [
                'sp_id' => $id,
                'sp' => $s['nama'],
                'kode' => $s['kode_sp'],
                'desa' => $s['desa'],
                'kecamatan' => $s['kecamatan'],
                'kabupaten' => $kawasan['kabupaten'],
                'provinsi' => $kawasan['provinsi'],
                'tahun_penempatan' => $s['tahun_penempatan'],
                'ada_isi' => $adaIsi,
                'kelompok' => $kelompok,
                'rute' => $rute,
            ];
        }

        return ['baris' => $baris, 'monografi' => $monografi];
    }

    /**
     * Rekap Indikator Kawasan.
     *
     * Tanpa berkas rujukan; dirancang sebagai ikhtisar satu halaman dari
     * angka yang menopang dashboard. Indikator produksi memakai tahun panen
     * berjalan, beda dari Laporan Hasil Panen yang memakai tahun pengadaan
     * bantuan (rules.md 9 poin 16; basis tahun dipisah menurut tujuan).
     *
     * @return array{kawasan: array, ringkasan: array, perSp: array}
     */
    public static function indikatorKawasan(): array
    {
        $r = DummyData::ringkasanDashboard();

        $kelembagaan = [
            'poktan' => count(DummyData::poktan()),
            'alsintan' => count(DummyData::alsintan()),
            'saprotan' => count(DummyData::saprotan()),
        ];

        return [
            'kawasan' => DummyData::kawasan()[0] ?? [],
            'ringkasan' => $r + $kelembagaan,
            'perSp' => DummyData::rekapPerSp(),
        ];
    }

    /**
     * Laporan Daftar Poktan.
     *
     * Kolom mengikuti "Poktan Wilayah Transmigrasi.xlsx": tiap poktan
     * memuat baris anggota beserta NIK, nomor HP, luas lahan (kering dan
     * basah), dan titik koordinat, ditutup subtotal luas per poktan.
     *
     * @return array{poktan: array<int, array<string, mixed>>}
     */
    public static function poktan(): array
    {
        $anggotaPerPoktan = [];
        foreach (DummyData::anggotaPoktan() as $a) {
            $anggotaPerPoktan[$a['poktan_id']][] = $a;
        }

        $daftar = [];

        foreach (self::petaPoktan() as $p) {
            $anggota = $anggotaPerPoktan[$p['id_poktan']] ?? [];

            $rincian = array_map(fn ($a) => [
                'nama' => $a['nama'],
                'nik' => $a['nik'] ?: '-',
                'telepon' => $a['telepon'] ?: '-',
                'luas_basah' => (float) ($a['luas_basah'] ?? 0),
                'luas_kering' => (float) ($a['luas_kering'] ?? 0),
                'lintang' => $a['lintang'] ?? '-',
                'bujur' => $a['bujur'] ?? '-',
                'status' => $a['status'],
            ], $anggota);

            $daftar[] = [
                'id_poktan' => $p['id_poktan'],
                'sp_id' => $p['satuan_permukiman_id'],
                'nama' => $p['nama'],
                'sp' => $p['satuan_permukiman'],
                'kecamatan' => $p['kecamatan'],
                'desa' => $p['desa'],
                'ketua' => $p['nama_ketua_terpakai'],
                'anggota' => $rincian,
                'jumlah_basah' => round(array_sum(array_column($rincian, 'luas_basah')), 2),
                'jumlah_kering' => round(array_sum(array_column($rincian, 'luas_kering')), 2),
            ];
        }

        return ['poktan' => $daftar];
    }

    /**
     * Laporan Daftar Transmigran beserta data Rumah dan Lahan.
     *
     * Tanpa berkas rujukan; disusun dari kolom yang sudah ada. Tiga bagian:
     * transmigran, rumah, lahan. Rumah ditaut ke transmigran lewat nama
     * penghuni, lahan lewat `transmigran_id`.
     *
     * @return array{transmigran: array, rumah: array, lahan: array}
     */
    public static function transmigran(): array
    {
        return [
            'transmigran' => DummyData::transmigran(),
            'rumah' => DummyData::rumah(),
            'lahan' => DummyData::lahan(),
        ];
    }

    /**
     * Daftar tahun unik terurut dari sebuah kolom, untuk mengisi opsi pemilih
     * rentang tahun pada bilah filter laporan.
     *
     * @param  iterable<int|string|null>  $nilai
     * @return list<int>
     */
    private static function tahunUnik(iterable $nilai): array
    {
        $tahun = [];

        foreach ($nilai as $t) {
            if ($t !== null && $t !== '') {
                $tahun[(int) $t] = true;
            }
        }

        $tahun = array_keys($tahun);
        sort($tahun);

        return $tahun;
    }

    /**
     * Konfigurasi bilah filter satu halaman laporan (Putaran 3 D3).
     *
     * Dibaca `x-sim.kerangka-laporan` dan dialirkan ke `x-sim.filter-laporan`
     * serta ke `x-data="filterLaporan(konfig)"`. Penyaringannya sendiri berjalan
     * di peramban (resources/js/filter-laporan.js): GitHub Pages tidak melayani
     * query string (notes.md 1b.5).
     *
     * Daftar SP adalah data master, bukan cacahan baris contoh, sehingga tidak
     * melanggar rules.md 19a. Larik kosong berarti laporan itu belum berfilter.
     *
     * @return array<string, mixed>
     */
    public static function filterLaporan(string $slug): array
    {
        $daftarSp = array_map(
            fn (array $s): array => ['id' => $s['id_satuan_permukiman'], 'nama' => $s['nama']],
            DummyData::satuanPermukiman()
        );

        $cakupanBawaan = self::meta($slug)['cakupan'] ?? '';

        return match ($slug) {
            'transmigran' => [
                'sp' => $daftarSp,
                'tahun' => true,
                'labelTahun' => 'Tahun Kedatangan',
                'daftarTahun' => self::tahunUnik(array_column(DummyData::transmigran(), 'tahun_kedatangan')),
                'dimensi' => [
                    [
                        'kunci' => 'status',
                        'label' => 'Status Tinggal',
                        'opsi' => array_map(fn (StatusTinggal $c): string => $c->value, StatusTinggal::cases()),
                    ],
                ],
                'cakupanBawaan' => $cakupanBawaan,
            ],
            'poktan' => [
                'sp' => $daftarSp,
                'tahun' => false,
                'dimensi' => [],
                'cakupanBawaan' => $cakupanBawaan,
            ],
            'alsintan' => [
                'sp' => $daftarSp,
                'tahun' => true,
                'labelTahun' => 'Tahun Pengadaan',
                'daftarTahun' => self::tahunUnik(array_column(DummyData::alsintan(), 'tahun_pengadaan')),
                'dimensi' => [
                    [
                        'kunci' => 'jenis',
                        'label' => 'Jenis Alat',
                        'opsi' => collect(DummyData::alsintan())->pluck('nama_alat')
                            ->unique()->sort()->values()->all(),
                    ],
                ],
                'cakupanBawaan' => $cakupanBawaan,
            ],
            default => [],
        };
    }

    /**
     * Mengelompokkan baris menurut SP, menghitung subtotal kolom angka dan
     * satu total kawasan. Dipakai Laporan Hasil Panen dan Alsintan.
     *
     * @param  array<int, array<string, mixed>>  $baris
     * @param  list<string>  $kolomJumlah  Kolom yang dijumlahkan
     * @param  string|null  $produktivitasKey  Bila diisi, subtotal dan total
     *                                         mendapat produktivitas tertimbang
     *                                         (produksi ton dibagi realisasi panen)
     * @return array{kelompok: array<int, mixed>, total: array<string, float>}
     */
    private static function kelompokkanPerSp(array $baris, array $kolomJumlah, ?string $produktivitasKey = null): array
    {
        // Dikelompokkan menurut id SP, bukan namanya: dua SP bernama sama akan
        // lebur bila dikunci nama, dan D3 (penyaring) menyaring lewat id.
        $perSp = [];

        foreach ($baris as $b) {
            $perSp[$b['sp_id']][] = $b;
        }

        $kelompok = [];
        $total = array_fill_keys($kolomJumlah, 0.0);

        foreach ($perSp as $spId => $isi) {
            $subtotal = array_fill_keys($kolomJumlah, 0.0);

            foreach ($isi as $b) {
                foreach ($kolomJumlah as $k) {
                    $subtotal[$k] += (float) ($b[$k] ?? 0);
                    $total[$k] += (float) ($b[$k] ?? 0);
                }
            }

            if ($produktivitasKey) {
                $subtotal[$produktivitasKey] = $subtotal['realisasi_panen'] > 0
                    ? round($subtotal['produksi_ton'] / $subtotal['realisasi_panen'], 2)
                    : 0.0;
            }

            $kelompok[] = [
                'sp_id' => $spId,
                'sp' => $isi[0]['sp'] ?? '-',
                'kecamatan' => $isi[0]['kecamatan'] ?? '-',
                'desa' => $isi[0]['desa'] ?? '-',
                'baris' => $isi,
                'subtotal' => $subtotal,
            ];
        }

        if ($produktivitasKey) {
            $total[$produktivitasKey] = $total['realisasi_panen'] > 0
                ? round($total['produksi_ton'] / $total['realisasi_panen'], 2)
                : 0.0;
        }

        return ['kelompok' => $kelompok, 'total' => $total];
    }
}
