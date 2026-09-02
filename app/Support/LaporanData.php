<?php

namespace App\Support;

use App\Enums\KondisiRumah;
use App\Enums\PeruntukanLahan;
use App\Enums\StatusAnggotaKeluarga;
use App\Enums\StatusHunian;
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
     * Identitas instansi untuk kop surat rute dokumen laporan (Putaran 5).
     *
     * Dua lambang (keputusan pemilik proyek): logo Kementerian Transmigrasi
     * di kiri, lambang Kabupaten Malaka di kanan. Kabupaten dan provinsi dari
     * `DummyData::kawasan()`. Telepon dan email adalah CONTOH yang mengikuti
     * `refs/contoh format laporan.docx`, bukan kontak resmi -- spanduk
     * "Data contoh" seluruh aplikasi sudah menyanggahnya.
     *
     * @return array<string, string>
     */
    public static function instansi(): array
    {
        $kawasan = DummyData::kawasan()[0] ?? ['kabupaten' => '-', 'provinsi' => '-'];
        $kabupaten = $kawasan['kabupaten'] ?? '-';

        return [
            'kementerian' => 'Kementerian Transmigrasi Republik Indonesia',
            'pemerintah' => 'Pemerintah Kabupaten '.$kabupaten,
            'dinas' => 'Dinas Tenaga Kerja dan Transmigrasi Kabupaten '.$kabupaten,
            'alamat' => 'Jalan Raya Betun, Kompleks Perkantoran Pemerintah Daerah Kab. '
                .$kabupaten.', '.($kawasan['provinsi'] ?? '-'),
            // Placeholder dari berkas rujukan, bukan kontak resmi. Istilah
            // "Email", bukan "Surel" (ui-spec.md 10.1).
            'kontak' => 'Telepon (0389) 123456  |  Email distrans@malakakab.go.id',
            'logoKementerian' => 'images/logo/logo-kementrans-128.png',
            'lambangKabupaten' => 'images/logo/lambang-malaka.png',
        ];
    }

    /**
     * Tahun rujukan dokumen laporan, yaitu tahun TERAKHIR deret data
     * (`DummyData::deretTahunan()`), bukan `date('Y')`.
     *
     * Ikut pola dashboard (`pages/dashboard/index.blade.php`): yang dapat
     * dijamin benar adalah "angka ini milik tahun terakhir yang terdata";
     * menyebut tahun berjalan menjanjikan hal yang belum tentu benar.
     */
    public static function tahunDokumenBawaan(): int
    {
        $tahun = DummyData::deretTahunan()['tahun'] ?? [];

        return $tahun === [] ? (int) date('Y') : (int) end($tahun);
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
                'catatan' => 'Tiap SP memuat Pendahuluan, Keadaan Wilayah, Kependudukan, Sosial Ekonomi, dan Sosial Budaya. Bagian yang belum berdata ditandai kosong; struktur umur dan mutasi penduduk adalah angka contoh turunan.',
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
                'kolom' => 17,
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

    /** @return array<int, array<string, mixed>> */
    private static function petaSaprotanDistribusi(): array
    {
        static $peta = null;

        return $peta ??= collect(DummyData::saprotanDistribusi())->keyBy('id_saprotan_distribusi')->all();
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
        $distribusi = self::petaSaprotanDistribusi();
        $anggotaAktif = self::anggotaAktifPerPoktan();
        $luasPoktan = [];

        $baris = [];

        foreach (DummyData::hasilPanen() as $h) {
            $tanam = $penanaman[$h['penanaman_id']] ?? null;
            $pok = $poktan[$h['poktan_id']] ?? null;

            if (! $tanam || ! $pok) {
                continue;
            }

            $pid = $pok['id_poktan'];
            $luasPoktan[$pid] ??= (float) (DummyData::rekapLahanPoktan($pid)['luas_total'] ?? 0.0);

            // Jejak varietas & tahun pengadaan: hasil_panen -> penanaman
            // -> saprotan_distribusi -> pengadaan (Putaran 7).
            $benih = $distribusi[$tanam['saprotan_distribusi_id'] ?? null] ?? null;

            $realisasiTanam = (float) $tanam['realisasi_tanam'];
            $realisasiPanen = (float) $h['realisasi_panen'];
            $puso = (float) $h['puso'];
            $belumDipanen = max(0.0, round($realisasiTanam - $realisasiPanen - $puso, 2));

            $luasLahan = $luasPoktan[$pid] ?? 0.0;
            $belumDitanam = max(0.0, round($luasLahan - $realisasiTanam, 2));

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
                'sumber_dana' => $benih['sumber_dana'] ?? 'Swadaya',
                'luas_lahan' => $luasLahan,
                'volume_benih' => (float) ($tanam['volume_benih'] ?? 0),
                'realisasi_tanam' => $realisasiTanam,
                'belum_ditanam' => $belumDitanam,
                'realisasi_panen' => $realisasiPanen,
                'puso' => $puso,
                'belum_dipanen' => $belumDipanen,
                'produktivitas' => (float) $h['produktivitas'],
                'produksi_ton' => round(DummyData::keTon((float) $h['produksi'], $h['satuan']), 2),
                'keterangan' => $h['keterangan'] ?? null,
            ];
        }

        return self::kelompokkanPerSp($baris, [
            'luas_lahan', 'volume_benih', 'realisasi_tanam', 'belum_ditanam', 'realisasi_panen',
            'puso', 'belum_dipanen', 'produksi_ton',
        ], 'produktivitas_tertimbang');
    }

    /**
     * Laporan Alsintan.
     *
     * Kolom "laporan alsintan.jpeg": Jenis Alat, Nama Alat, Sumber Dana, Tahun
     * Pengadaan, Poktan Penerima, Ketua Poktan, Alamat (Kec./Desa), Jumlah
     * (Unit). Satu baris per DISTRIBUSI (satu poktan menerima sekian unit dari
     * satu pengadaan), dikelompokkan per SP, subtotal Jumlah.
     *
     * Grain berpindah dari pengadaan ke distribusi sejak Putaran 7: satu batch
     * dapat dibagikan ke banyak poktan lintas SP. `jenis_alat` kini benar-benar
     * `jenis_alsintan` (data master §11.37), bukan `nama_alat` yang dipakai
     * ulang. Pengadaan yang belum disalurkan ke satu poktan pun tidak
     * menghasilkan baris di sini.
     *
     * @return array{kelompok: array<int, mixed>, total: array<string, float>}
     */
    public static function alsintan(): array
    {
        $poktan = self::petaPoktan();

        $baris = [];

        foreach (DummyData::alsintan() as $a) {
            foreach ($a['distribusi'] as $d) {
                $pok = $poktan[$d['poktan_id']] ?? null;

                $baris[] = [
                    'sp_id' => $d['satuan_permukiman_id'],
                    'sp' => $d['satuan_permukiman'],
                    'poktan_id' => $d['poktan_id'],
                    'kecamatan' => $pok['kecamatan'] ?? '-',
                    'desa' => $pok['desa'] ?? '-',
                    'jenis_alat' => $a['jenis_alsintan'],
                    'nama_alat' => $a['nama_alat'],
                    'sumber_dana' => $a['sumber_dana'],
                    'tahun_pengadaan' => $a['tahun_pengadaan'],
                    'poktan' => $d['poktan'],
                    'ketua' => $pok['nama_ketua_terpakai'] ?? '-',
                    'jumlah' => (int) $d['jumlah'],
                ];
            }
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

        // Grain: satu baris per DISTRIBUSI (satu poktan menerima sekian dari
        // satu pengadaan), sejak Putaran 7. Pengadaan yang belum disalurkan
        // ke satu poktan pun tidak menghasilkan baris di sini. Jadwal tanam
        // tetap dari pengadaan (rencana bantuan).
        $jadwal = collect(DummyData::saprotan())->pluck('jadwal_tanam', 'id_saprotan')->all();

        foreach (DummyData::saprotanDistribusi() as $d) {
            $pok = $poktan[$d['poktan_id']] ?? null;

            if ($d['jenis'] === 'Benih') {
                $pid = $d['poktan_id'];
                $luasPoktan[$pid] ??= DummyData::rekapLahanPoktan($pid)['luas_total'];

                $benih[] = [
                    'sp_id' => $d['satuan_permukiman_id'],
                    'sp' => $d['satuan_permukiman'],
                    'poktan_id' => $d['poktan_id'],
                    'kecamatan' => $pok['kecamatan'] ?? '-',
                    'desa' => $pok['desa'] ?? '-',
                    'poktan' => $d['poktan'],
                    'ketua' => $pok['nama_ketua_terpakai'] ?? '-',
                    'nik_ketua' => $pok['nik_ketua_terpakai'] ?? '-',
                    'telepon_ketua' => $pok['telepon_ketua'] ?? '-',
                    'jumlah_anggota' => $pok ? ($anggotaAktif[$pok['id_poktan']] ?? 0) : 0,
                    'luas_lahan' => $luasPoktan[$pid] ?? 0,
                    'komoditas' => $d['komoditas'] ?? '-',
                    'varietas' => $d['varietas'] ?? '-',
                    'volume_benih' => (float) $d['jumlah'],
                    'satuan' => $d['satuan'],
                    'tahun_pengadaan' => $d['tahun_pengadaan'] ?? null,
                    'sumber_dana' => $d['sumber_dana'] ?? '-',
                    'jadwal_tanam' => $jadwal[$d['saprotan_id']] ?? null,
                ];

                continue;
            }

            $nonBenih[] = [
                'sp_id' => $d['satuan_permukiman_id'],
                'poktan_id' => $d['poktan_id'],
                'poktan' => $d['poktan'],
                'sp' => $d['satuan_permukiman'],
                'jenis' => $d['jenis'],
                'volume' => (float) $d['jumlah'],
                'satuan' => $d['satuan'],
                'tahun_pengadaan' => $d['tahun_pengadaan'] ?? null,
                'sumber_dana' => $d['sumber_dana'] ?? '-',
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
     * Bila `$tahun` diisi (Putaran 5), dua belas field iklim digantikan nilai
     * tahun itu lewat `DummyData::iklimSpTahun()`; sisanya (geografi) tetap.
     *
     * @return array<string, array<string, ?string>>
     */
    private static function bab2(array $s, ?int $tahun = null): array
    {
        if ($tahun !== null && isset($s['id_satuan_permukiman'])) {
            $s = array_merge($s, DummyData::iklimSpTahun($s['id_satuan_permukiman'], $tahun));
        }

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
     * Membentuk satu tabel dokumen untuk bagian perluasan Monografi.
     *
     * @param  list<string>  $kolom
     * @param  list<list<string|int|float|null>>  $baris
     * @param  list<string|int|float|null>|null  $total
     * @return array{judul: string, kolom: list<string>, baris: list<mixed>, total: mixed, catatan: ?string, kosong: bool}
     */
    private static function tabelDok(string $judul, array $kolom, array $baris, ?array $total = null, ?string $catatan = null): array
    {
        return [
            'judul' => $judul,
            'kolom' => $kolom,
            'baris' => $baris,
            'total' => $total,
            'catatan' => $catatan,
            'kosong' => $baris === [],
        ];
    }

    /**
     * Keadaan penduduk satu SP pada satu tahun (Putaran 6).
     *
     * KK mengikuti `rekapPerSpTahun()`; jiwa diskalakan dari `jiwaPerSp()`
     * menurut porsi KK tahun itu terhadap tahun terakhir; laki dan perempuan
     * memakai nisbah `strukturUmurSp()`. Semua angka contoh turunan.
     *
     * @return array{kk: int, jiwa: int, laki: int, perempuan: int}
     */
    public static function keadaanPendudukTahun(int $id, int $tahun): array
    {
        $tahunAkhir = self::tahunDokumenBawaan();
        $rekapAkhir = collect(DummyData::rekapPerSp())->firstWhere('satuan_permukiman_id', $id);
        $rekapTahun = collect(DummyData::rekapPerSpTahun($tahun))->firstWhere('satuan_permukiman_id', $id);

        $kkAkhir = (int) ($rekapAkhir['jumlah_kk'] ?? 0);
        $kk = (int) ($rekapTahun['jumlah_kk'] ?? 0);
        $jiwaAkhir = DummyData::jiwaPerSp()[$id] ?? 0;
        $jiwa = $kkAkhir > 0 ? (int) round($jiwaAkhir * $kk / $kkAkhir) : 0;

        $struktur = DummyData::strukturUmurSp($id);
        $lakiAkhir = array_sum(array_column($struktur, 'laki'));
        $laki = $jiwaAkhir > 0 ? (int) round($jiwa * $lakiAkhir / $jiwaAkhir) : 0;

        return ['kk' => $kk, 'jiwa' => $jiwa, 'laki' => $laki, 'perempuan' => $jiwa - $laki];
    }

    /**
     * Bagian perluasan Monografi satu SP: Pendahuluan, Kependudukan, Sosial
     * Ekonomi, Sosial Budaya (Putaran 6).
     *
     * Seluruhnya disaring per SP dari tabel yang sudah ada, kecuali struktur
     * umur dan mutasi penduduk yang dikarang deterministik (DummyData). Bagian
     * yang belum berdata pada satu SP dikembalikan sebagai tabel kosong; view
     * menandainya "belum ada data". Tahun hanya memengaruhi "Keadaan Penduduk
     * Sekarang"; sisanya tidak bersumbu tahun pada data contoh.
     *
     * @param  array<string, mixed>  $s  Baris satuanPermukiman (sudah memuat keadaan wilayah)
     * @return array<string, mixed>
     */
    private static function bagianTambahanSp(array $s, int $tahun): array
    {
        $id = $s['id_satuan_permukiman'];
        $nama = $s['nama'];
        $rp = fn ($n) => $n !== null ? 'Rp '.number_format((float) $n, 0, ',', '.') : '-';

        $transmigranSp = array_values(array_filter(
            DummyData::transmigran(),
            fn ($t) => $t['satuan_permukiman_id'] === $id,
        ));
        $idTransmigran = array_column($transmigranSp, 'id_transmigran');
        $anggotaSp = array_values(array_filter(
            DummyData::anggotaKeluarga(),
            fn ($a) => in_array($a['transmigran_id'], $idTransmigran, true)
                && $a['status'] === StatusAnggotaKeluarga::Aktif->value,
        ));

        // --- Pendahuluan -----------------------------------------------------
        $sekarang = self::keadaanPendudukTahun($id, $tahun);
        $pendahuluan = [
            'kalimat' => sprintf(
                'Satuan Permukiman %s (%s) berada di Desa %s, Kecamatan %s, Kabupaten %s, Provinsi %s. '
                .'Penempatan transmigran dimulai tahun %d sebanyak %s KK. '
                .'Luas wilayah %s ha dengan dasar pencadangan %s.',
                $nama, $s['kode_sp'], $s['desa'], $s['kecamatan'],
                DummyData::kawasan()[0]['kabupaten'] ?? '-', DummyData::kawasan()[0]['provinsi'] ?? '-',
                $s['tahun_penempatan'], self::angka($s['jumlah_kk_terisi'], 0),
                self::angka($s['luas_lahan']),
                $s['nomor_sk_pencadangan'] ?? 'belum dicatat',
            ),
            'ringkas' => [
                'Tahun penempatan' => (string) $s['tahun_penempatan'],
                'KK penempatan awal' => self::angka($s['jumlah_kk_terisi'], 0).' KK',
                'KK sekarang' => self::angka($sekarang['kk'], 0).' KK',
                'Jiwa sekarang' => self::angka($sekarang['jiwa'], 0).' jiwa',
                'Luas wilayah' => self::angka($s['luas_lahan']).' ha',
                'Nomor SK pencadangan' => $s['nomor_sk_pencadangan'] ?? 'belum dicatat',
            ],
        ];

        // --- Kependudukan --------------------------------------------------
        $penempatan = [];
        $grup = [];
        foreach ($transmigranSp as $t) {
            // Nama dibaca dari data master sejak daerah asal menjadi FK
            // (2026-09-02). "Tidak dicatat" dipertahankan untuk baris yang
            // memang belum terisi, dan itu berbeda dari id yang tidak dikenal.
            $asal = DataWilayah::namaKabupaten($t['daerah_asal_kabupaten_id'] ?? null) ?? 'Tidak dicatat';

            $kunci = $asal.'|'.$t['tahun_kedatangan'];
            $grup[$kunci] ??= ['asal' => $asal, 'tahun' => $t['tahun_kedatangan'], 'kk' => 0, 'l' => 0, 'p' => 0];
            $grup[$kunci]['kk']++;
            $grup[$kunci][$t['jenis_kelamin'] === 'Perempuan' ? 'p' : 'l']++;
        }
        foreach ($grup as $g) {
            $penempatan[] = [$g['asal'], $g['tahun'], $g['kk'], $g['l'], $g['p'], $g['l'] + $g['p']];
        }

        $struktur = DummyData::strukturUmurSp($id);
        $barisUmur = array_map(fn ($b) => [$b['kelompok'], $b['laki'], $b['perempuan'], $b['jumlah']], $struktur);
        $totUmur = ['Jumlah', array_sum(array_column($struktur, 'laki')), array_sum(array_column($struktur, 'perempuan')), array_sum(array_column($struktur, 'jumlah'))];

        // Usia sekolah: TK 4-6, SD 7-12, SLTP 13-15, SLTA 16-19. Dipetakan dari
        // kelompok lima tahunan secara proporsional sederhana.
        $u = fn (int $i) => $struktur[$i] ?? ['laki' => 0, 'perempuan' => 0];
        $sekolah = [
            ['TK (4 sampai 6 tahun)', (int) round($u(0)['laki'] * 0.4 + $u(1)['laki'] * 0.2), (int) round($u(0)['perempuan'] * 0.4 + $u(1)['perempuan'] * 0.2)],
            ['SD (7 sampai 12 tahun)', (int) round($u(1)['laki'] * 0.6 + $u(2)['laki'] * 0.6), (int) round($u(1)['perempuan'] * 0.6 + $u(2)['perempuan'] * 0.6)],
            ['SLTP (13 sampai 15 tahun)', (int) round($u(2)['laki'] * 0.4 + $u(3)['laki'] * 0.2), (int) round($u(2)['perempuan'] * 0.4 + $u(3)['perempuan'] * 0.2)],
            ['SLTA (16 sampai 19 tahun)', (int) round($u(3)['laki'] * 0.8), (int) round($u(3)['perempuan'] * 0.8)],
        ];
        $sekolah = array_map(fn ($r) => [$r[0], $r[1], $r[2], $r[1] + $r[2]], $sekolah);

        $mutasi = DummyData::mutasiPendudukSp($id);
        $barisMutasi = array_map(fn ($b) => [$b['jenis'], $b['laki'], $b['perempuan'], $b['jumlah']], $mutasi['baris']);

        $kependudukan = [
            'catatan' => 'Rincian disaring dari data yang tercatat sistem. Struktur umur, usia sekolah, dan '
                .'mutasi penduduk adalah angka contoh turunan, bukan pendataan per orang. SP yang belum '
                .'berdata ditandai kosong.',
            'penempatan' => self::tabelDok(
                'Penempatan transmigran menurut daerah asal',
                ['Daerah Asal', 'Tahun', 'KK', 'Laki-laki', 'Perempuan', 'Jumlah'],
                $penempatan,
                null,
                'Jenis kelamin dihitung dari kepala keluarga; jiwa dalam keluarga tidak dipecah di sini.',
            ),
            'sekarang' => $sekarang,
            'strukturUmur' => self::tabelDok(
                'Struktur penduduk menurut kelompok umur',
                ['Kelompok Umur', 'Laki-laki', 'Perempuan', 'Jumlah'],
                $barisUmur,
                $totUmur,
            ),
            'usiaSekolah' => self::tabelDok(
                'Struktur penduduk menurut usia sekolah',
                ['Jenjang', 'Laki-laki', 'Perempuan', 'Jumlah'],
                $sekolah,
            ),
            'mutasi' => self::tabelDok(
                'Mutasi penduduk kumulatif sejak penempatan',
                ['Jenis Mutasi', 'Laki-laki', 'Perempuan', 'Jumlah'],
                $barisMutasi,
                ['Pertambahan bersih', '', '', $mutasi['bersih']],
                'Kumulatif sejak tahun penempatan, bukan angka tahunan. Tanpa mutasi perkawinan.',
            ),
        ];

        // --- Sosial Ekonomi ----------------------------------------------
        $lahanSp = array_values(array_filter(DummyData::lahan(), fn ($l) => $l['satuan_permukiman_id'] === $id));
        $lahanGrup = [];
        foreach ($lahanSp as $l) {
            $j = $l['peruntukan_lahan'];
            $lahanGrup[$j] ??= ['dibagikan' => 0.0, 'diusahakan' => 0.0];
            $lahanGrup[$j]['dibagikan'] += (float) $l['luas'];
            if (! empty($l['pola_tanam'])) {
                $lahanGrup[$j]['diusahakan'] += (float) $l['luas'];
            }
        }
        $barisLahan = [];
        foreach ($lahanGrup as $jenis => $v) {
            $barisLahan[] = [$jenis, self::angka($v['dibagikan']), self::angka($v['diusahakan'])];
        }
        $totLahan = $barisLahan === [] ? null : [
            'Jumlah',
            self::angka(array_sum(array_column($lahanGrup, 'dibagikan'))),
            self::angka(array_sum(array_column($lahanGrup, 'diusahakan'))),
        ];

        // Cacah BIDANG berdokumen per jenis (Putaran 7): satu dokumen dapat
        // mencakup banyak bidang, jadi yang dihitung adalah bidangnya.
        $idLahanSp = array_column($lahanSp, 'id_lahan');
        $dokGrup = [];
        foreach (DummyData::dokumenLahan() as $d) {
            foreach ($d['lahan_ids'] as $lahanId) {
                if (in_array($lahanId, $idLahanSp, true)) {
                    $dokGrup[$d['jenis_dokumen']] = ($dokGrup[$d['jenis_dokumen']] ?? 0) + 1;
                }
            }
        }
        $barisDok = [];
        foreach ($dokGrup as $jenis => $n) {
            $barisDok[] = [$jenis, $n];
        }

        $panenGrup = [];
        foreach (DummyData::penanaman() as $t) {
            if ($t['satuan_permukiman_id'] !== $id) {
                continue;
            }
            $k = $t['komoditas'];
            $panenGrup[$k] ??= ['tanam' => 0.0, 'panen' => 0.0, 'puso' => 0.0, 'produksi' => 0.0];
            $panenGrup[$k]['tanam'] += (float) $t['realisasi_tanam'];
        }
        foreach (DummyData::hasilPanen() as $h) {
            if (($h['satuan_permukiman_id'] ?? null) !== $id) {
                continue;
            }
            $k = $h['komoditas'];
            $panenGrup[$k] ??= ['tanam' => 0.0, 'panen' => 0.0, 'puso' => 0.0, 'produksi' => 0.0];
            $panenGrup[$k]['panen'] += (float) ($h['realisasi_panen'] ?? $h['luas_panen'] ?? 0);
            $panenGrup[$k]['puso'] += (float) ($h['luas_puso'] ?? $h['puso'] ?? 0);
            $panenGrup[$k]['produksi'] += (float) ($h['produksi_ton'] ?? $h['produksi'] ?? 0);
        }
        $barisTanam = [];
        foreach ($panenGrup as $kom => $v) {
            $barisTanam[] = [$kom, self::angka($v['tanam']), self::angka($v['panen']), self::angka($v['puso']), self::angka($v['produksi'])];
        }

        // Infrastruktur yang MELAYANI SP ini, termasuk aset bersama yang
        // berpangkal di SP lain (Putaran 7).
        $infraSp = array_values(array_filter(
            DummyData::infrastruktur(),
            fn ($x) => in_array($id, $x['satuan_permukiman_ids'] ?? [$x['satuan_permukiman_id']], true),
        ));
        $barisInfra = array_map(fn ($x) => [
            $x['jenis'], $x['nama'], $x['kondisi'], $x['kapasitas'] ?? '-', $x['tahun_perolehan'] ?? '-',
        ], $infraSp);

        $sosialEkonomi = [
            'lahan' => self::tabelDok(
                'Luas lahan tani',
                ['Jenis Lahan', 'Dibagikan (ha)', 'Diusahakan (ha)'],
                $barisLahan,
                $totLahan,
                'Sistem tidak memisah lahan usaha tahap I dan II.',
            ),
            'sertifikat' => self::tabelDok(
                'Sertifikat dan dokumen tanah',
                ['Jenis Dokumen', 'Jumlah Bidang'],
                $barisDok,
                null,
                'Hanya realisasi yang terdata; angka target penerbitan tidak dimodelkan.',
            ),
            'tanamanPangan' => self::tabelDok(
                'Tanaman pangan menurut komoditas',
                ['Komoditas', 'Luas Tanam (ha)', 'Luas Panen (ha)', 'Puso (ha)', 'Produksi (ton)'],
                $barisTanam,
            ),
            'infrastruktur' => self::tabelDok(
                'Prasarana dan infrastruktur',
                ['Jenis', 'Nama', 'Kondisi', 'Kapasitas', 'Tahun'],
                $barisInfra,
            ),
        ];

        // --- Sosial Budaya ---------------------------------------------
        // Fasilitas yang MELAYANI SP ini, termasuk yang berpangkal di SP lain
        // (Putaran 7): SMP Satu Atap, puskesmas pembantu, pasar desa.
        $fasilitasSp = array_values(array_filter(
            DummyData::fasilitasSp(),
            fn ($x) => in_array($id, $x['satuan_permukiman_ids'] ?? [$x['satuan_permukiman_id']], true),
        ));
        $fasilitasJenis = function (array $jenis) use ($fasilitasSp) {
            return array_map(fn ($x) => [$x['nama_fasilitas'], $x['jumlah'], $x['kondisi'], $x['tahun_perolehan']],
                array_values(array_filter($fasilitasSp, fn ($x) => in_array($x['jenis_fasilitas'], $jenis, true))));
        };

        $agamaGrup = [];
        foreach (array_merge($transmigranSp, $anggotaSp) as $orang) {
            $ag = $orang['agama'] ?? 'Tidak dicatat';
            $agamaGrup[$ag] ??= ['l' => 0, 'p' => 0];
            $agamaGrup[$ag][($orang['jenis_kelamin'] ?? 'Laki-laki') === 'Perempuan' ? 'p' : 'l']++;
        }
        $barisAgama = [];
        foreach ($agamaGrup as $ag => $v) {
            $barisAgama[] = [$ag, $v['l'], $v['p'], $v['l'] + $v['p']];
        }

        // Alsintan disaring lewat baris distribusi: satu SP dilayani bila ada
        // poktan di SP itu yang menerima bagian dari pengadaan (Putaran 7).
        $alsintanSp = [];
        foreach (DummyData::alsintan() as $a) {
            foreach ($a['distribusi'] as $d) {
                if ($d['satuan_permukiman_id'] === $id) {
                    $alsintanSp[] = [$a['jenis_alsintan'], $a['nama_alat'], $d['jumlah'], $a['tahun_pengadaan'], $d['poktan']];
                }
            }
        }
        $inventarisSp = array_values(array_filter(DummyData::inventarisSp(), fn ($x) => $x['satuan_permukiman_id'] === $id));

        $sosialBudaya = [
            'pendidikan' => self::tabelDok('Sarana pendidikan', ['Nama', 'Jumlah', 'Kondisi', 'Tahun'],
                $fasilitasJenis(['Pendidikan Dasar', 'Pendidikan Lanjutan'])),
            'kesehatan' => self::tabelDok('Sarana kesehatan', ['Nama', 'Jumlah', 'Kondisi', 'Tahun'],
                $fasilitasJenis(['Kesehatan'])),
            'agama' => self::tabelDok('Penduduk menurut agama', ['Agama', 'Laki-laki', 'Perempuan', 'Jumlah'], $barisAgama,
                null, 'Dihitung dari kepala keluarga dan anggota keluarga aktif yang tercatat.'),
            'rumahIbadah' => self::tabelDok('Rumah ibadah', ['Nama', 'Jumlah', 'Kondisi', 'Tahun'],
                $fasilitasJenis(['Ibadah'])),
            'olahraga' => self::tabelDok('Sarana kesenian dan olahraga', ['Nama', 'Jumlah', 'Kondisi', 'Tahun'],
                $fasilitasJenis(['Olahraga'])),
            'keamanan' => self::tabelDok('Sarana keamanan', ['Nama', 'Jumlah', 'Kondisi', 'Tahun'],
                $fasilitasJenis(['Keamanan'])),
            'alsintan' => self::tabelDok('Alat dan mesin pertanian', ['Jenis', 'Nama Alat', 'Jumlah', 'Tahun', 'Poktan Penerima'],
                $alsintanSp),
            'inventaris' => self::tabelDok('Inventaris UPT', ['Nama Barang', 'Jumlah', 'Satuan', 'Kondisi', 'Tahun'],
                array_map(fn ($x) => [$x['nama_barang'], $x['jumlah'], $x['satuan_barang'], $x['kondisi'], $x['tahun_perolehan']], $inventarisSp)),
            'fasilitasUmum' => self::tabelDok('Fasilitas umum', ['Jenis', 'Nama', 'Jumlah', 'Kondisi', 'Tahun'],
                array_map(fn ($x) => [$x['jenis_fasilitas'], $x['nama_fasilitas'], $x['jumlah'], $x['kondisi'], $x['tahun_perolehan']], $fasilitasSp)),
        ];

        return [
            'pendahuluan' => $pendahuluan,
            'kependudukan' => $kependudukan,
            'sosial_ekonomi' => $sosialEkonomi,
            'sosial_budaya' => $sosialBudaya,
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
        static $memo = null;
        if ($memo !== null) {
            return $memo;
        }

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

        // Rekap per SP untuk tiap tahun laporan (Putaran 5), agar ikhtisar dan
        // Bab II "Iklim" dapat mengikuti tahun terpilih.
        $rekapTahun = [];
        foreach (DummyData::tahunLaporan() as $tahun) {
            $rekapTahun[$tahun] = collect(DummyData::rekapPerSpTahun($tahun))->keyBy('satuan_permukiman_id');
        }

        $baris = [];
        $monografi = [];
        $iklimTahun = [];
        $kependudukanTahun = [];

        foreach (DummyData::satuanPermukiman() as $s) {
            $id = $s['id_satuan_permukiman'];
            $r = $rekap->get($id);

            // Angka ikhtisar yang berubah antar tahun. Kolom struktural (luas
            // wilayah, KK rencana, poktan, lahan tergarap) tetap.
            $perTahun = [];
            foreach (DummyData::tahunLaporan() as $tahun) {
                $rt = $rekapTahun[$tahun]->get($id);
                $perTahun[$tahun] = [
                    'kk_terisi' => (int) ($rt['jumlah_kk'] ?? 0),
                    'rumah_terhuni' => (int) ($rt['rumah_terhuni'] ?? 0),
                    'produksi_ton' => (float) ($rt['volume_panen'] ?? 0),
                    'pengaduan_terbuka' => (int) ($rt['pengaduan_terbuka'] ?? 0),
                ];
            }

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
                'per_tahun' => $perTahun,
            ];

            $kelompok = self::bab2($s);
            $adaIsi = collect($kelompok)->flatten()->contains(fn ($v) => $v !== null && trim((string) $v) !== '');

            // Kalimat kelompok "Iklim" untuk tiap tahun laporan.
            $iklimTahun[$id] = [];
            foreach (DummyData::tahunLaporan() as $tahun) {
                $iklimTahun[$id][$tahun] = self::bab2($s, $tahun)['Iklim'];
            }

            $rute = array_map(fn ($x) => [
                'rute' => $x['rute'],
                'jarak_km' => $x['jarak_km'],
                'sarana_angkutan' => $x['sarana_angkutan'] ?? '-',
                'kondisi_jalan' => $x['kondisi_jalan'] ?? '-',
                'waktu_tempuh' => $x['waktu_tempuh'] ?? '-',
                'ongkos_rp' => $x['ongkos_rp'],
                'keterangan' => $x['keterangan'] ?? null,
            ], DummyData::ruteAksesibilitasSp($id));

            // Keadaan penduduk "sekarang" untuk tiap tahun laporan, agar
            // pemilih tahun mengubah angka KK dan jiwa di sisi peramban.
            $kependudukanTahun[$id] = [];
            foreach (DummyData::tahunLaporan() as $tahun) {
                $kependudukanTahun[$id][$tahun] = self::keadaanPendudukTahun($id, $tahun);
            }

            $bagian = self::bagianTambahanSp($s, self::tahunDokumenBawaan());

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
                'pendahuluan' => $bagian['pendahuluan'],
                'kependudukan' => $bagian['kependudukan'],
                'sosial_ekonomi' => $bagian['sosial_ekonomi'],
                'sosial_budaya' => $bagian['sosial_budaya'],
            ];
        }

        return $memo = [
            'baris' => $baris,
            'monografi' => $monografi,
            'iklimTahun' => $iklimTahun,
            'kependudukanTahun' => $kependudukanTahun,
            'daftarTahun' => DummyData::tahunLaporan(),
        ];
    }

    /**
     * Rekap Indikator Kawasan.
     *
     * Tanpa berkas rujukan; dirancang sebagai ikhtisar satu halaman dari
     * angka yang menopang dashboard. Indikator produksi memakai tahun panen
     * berjalan, beda dari Laporan Hasil Panen yang memakai tahun pengadaan
     * bantuan (rules.md 9 poin 16; basis tahun dipisah menurut tujuan).
     *
     * `perSp` TETAP `rekapPerSp()` (tahun terakhir) supaya penjaga "jumlah enam
     * SP = angka kawasan" tak berubah. `perSpTahun` dan `ringkasanTahun`
     * (Putaran 5) melayani pemilih tahun tunggal di sisi peramban.
     *
     * @return array{kawasan: array, ringkasan: array, perSp: array, perSpTahun: array, ringkasanTahun: array, daftarTahun: list<int>}
     */
    public static function indikatorKawasan(): array
    {
        $r = DummyData::ringkasanDashboard();

        $kelembagaan = [
            'poktan' => count(DummyData::poktan()),
            'alsintan' => count(DummyData::alsintan()),
            'saprotan' => count(DummyData::saprotan()),
        ];

        $perSpTahun = [];
        foreach (DummyData::tahunLaporan() as $tahun) {
            $perSpTahun[$tahun] = DummyData::rekapPerSpTahun($tahun);
        }

        return [
            'kawasan' => DummyData::kawasan()[0] ?? [],
            'ringkasan' => $r + $kelembagaan,
            'perSp' => DummyData::rekapPerSp(),
            'perSpTahun' => $perSpTahun,
            'ringkasanTahun' => DummyData::indikatorKawasanTahun(),
            'daftarTahun' => DummyData::tahunLaporan(),
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
                    [
                        'kunci' => 'statusHunian',
                        'label' => 'Status Hunian',
                        'opsi' => array_map(fn (StatusHunian $c): string => $c->value, StatusHunian::cases()),
                    ],
                    [
                        'kunci' => 'kondisi',
                        'label' => 'Kondisi Rumah',
                        'opsi' => array_map(fn (KondisiRumah $c): string => $c->value, KondisiRumah::cases()),
                    ],
                    [
                        'kunci' => 'peruntukan',
                        'label' => 'Peruntukan Lahan',
                        'opsi' => array_map(fn (PeruntukanLahan $c): string => $c->value, PeruntukanLahan::cases()),
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
                        'opsi' => collect(DummyData::alsintan())->pluck('jenis_alsintan')
                            ->filter()->unique()->sort()->values()->all(),
                    ],
                ],
                'cakupanBawaan' => $cakupanBawaan,
            ],
            'saprotan' => [
                'sp' => $daftarSp,
                'tahun' => true,
                'labelTahun' => 'Tahun Pengadaan',
                'daftarTahun' => self::tahunUnik(array_column(DummyData::saprotan(), 'tahun_pengadaan')),
                'dimensi' => [
                    [
                        'kunci' => 'komoditas',
                        'label' => 'Komoditas Benih',
                        'opsi' => collect(DummyData::saprotan())->where('jenis', 'Benih')
                            ->pluck('komoditas')->filter()->unique()->sort()->values()->all(),
                    ],
                    [
                        'kunci' => 'jenis',
                        'label' => 'Jenis Sarana (non-benih)',
                        'opsi' => collect(DummyData::saprotan())->where('jenis', '!=', 'Benih')
                            ->pluck('jenis')->filter()->unique()->sort()->values()->all(),
                    ],
                ],
                'cakupanBawaan' => $cakupanBawaan,
            ],
            'hasil-panen' => [
                'sp' => $daftarSp,
                'tahun' => true,
                // rules.md 16a: sumbu laporan panen adalah tahun anggaran
                // bantuan (tahun pengadaan benih), BUKAN tahun panen.
                'labelTahun' => 'Tahun Anggaran',
                'labelTahunDokumen' => 'Tahun Anggaran',
                'daftarTahun' => self::tahunUnik(
                    collect(self::hasilPanen()['kelompok'])
                        ->flatMap(fn (array $g): array => array_column($g['baris'], 'tahun_pengadaan'))
                        ->all()
                ),
                'dimensi' => [
                    [
                        'kunci' => 'komoditas',
                        'label' => 'Komoditas',
                        'opsi' => collect(DummyData::hasilPanen())->pluck('komoditas')
                            ->filter()->unique()->sort()->values()->all(),
                    ],
                    [
                        'kunci' => 'sumber_dana',
                        'label' => 'Sumber Dana',
                        'opsi' => collect(self::hasilPanen()['kelompok'])
                            ->flatMap(fn (array $g): array => array_column($g['baris'], 'sumber_dana'))
                            ->filter()->unique()->sort()->values()->all(),
                    ],
                ],
                'cakupanBawaan' => $cakupanBawaan,
            ],
            'monografi-sp' => [
                'sp' => $daftarSp,
                'tahun' => false,
                'tahunTunggal' => true,
                'labelTahun' => 'Tahun',
                'daftarTahun' => DummyData::tahunLaporan(),
                'tahunBawaan' => self::tahunDokumenBawaan(),
                'dimensi' => [],
                'cakupanBawaan' => $cakupanBawaan,
                // Kalimat Bab II "Iklim" per tahun, dirakit di PHP (aman
                // terhadap penjaga format angka). [spId][tahun][label] => teks.
                'iklimTahun' => self::monografiSp()['iklimTahun'],
                // Keadaan Penduduk Sekarang per tahun (Putaran 6).
                // [spId][tahun] => {kk, jiwa, laki, perempuan}.
                'kependudukanTahun' => self::monografiSp()['kependudukanTahun'],
            ],
            'indikator-kawasan' => [
                'sp' => $daftarSp,
                'tahun' => false,
                'tahunTunggal' => true,
                'labelTahun' => 'Tahun',
                'daftarTahun' => DummyData::tahunLaporan(),
                'tahunBawaan' => self::tahunDokumenBawaan(),
                'dimensi' => [],
                'cakupanBawaan' => $cakupanBawaan,
                'ringkasanTahun' => DummyData::indikatorKawasanTahun(),
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

            // rules.md §16c: luas_lahan dihitung dari himpunan poktan unik
            if (in_array('luas_lahan', $kolomJumlah, true)) {
                $poktanUnikSp = [];
                $luasUnik = 0.0;
                foreach ($isi as $b) {
                    $pid = $b['poktan_id'] ?? null;
                    if ($pid !== null && ! in_array($pid, $poktanUnikSp, true)) {
                        $poktanUnikSp[] = $pid;
                        $luasUnik += (float) ($b['luas_lahan'] ?? 0);
                    }
                }
                $subtotal['luas_lahan'] = $luasUnik;
            }

            // rules.md §16c: belum_ditanam dihitung per poktan unik: sum(max(0, luas_poktan - tanam_poktan))
            if (in_array('belum_ditanam', $kolomJumlah, true)) {
                $tanamPerPoktan = [];
                $luasPerPoktan = [];
                foreach ($isi as $b) {
                    $pid = $b['poktan_id'] ?? null;
                    if ($pid !== null) {
                        $luasPerPoktan[$pid] = (float) ($b['luas_lahan'] ?? 0);
                        $tanamPerPoktan[$pid] = ($tanamPerPoktan[$pid] ?? 0.0) + (float) ($b['realisasi_tanam'] ?? 0);
                    }
                }
                $sisaBelumTanamSp = 0.0;
                foreach ($luasPerPoktan as $pid => $luas) {
                    $sisaBelumTanamSp += max(0.0, round($luas - ($tanamPerPoktan[$pid] ?? 0), 2));
                }
                $subtotal['belum_ditanam'] = $sisaBelumTanamSp;
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

        // Total kawasan untuk luas_lahan dan belum_ditanam = jumlah seluruh subtotal SP
        if (in_array('luas_lahan', $kolomJumlah, true)) {
            $total['luas_lahan'] = array_sum(array_column(array_column($kelompok, 'subtotal'), 'luas_lahan'));
        }

        if (in_array('belum_ditanam', $kolomJumlah, true)) {
            $total['belum_ditanam'] = array_sum(array_column(array_column($kelompok, 'subtotal'), 'belum_ditanam'));
        }

        if ($produktivitasKey) {
            $total[$produktivitasKey] = ($total['realisasi_panen'] ?? 0) > 0
                ? round(($total['produksi_ton'] ?? 0) / $total['realisasi_panen'], 2)
                : 0.0;
        }

        return ['kelompok' => $kelompok, 'total' => $total];
    }
}
