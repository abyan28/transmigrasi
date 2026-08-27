<?php

namespace App\Support;

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
            'puso', 'belum_dipanen', 'produksi_ton', 'jumlah_anggota',
        ], 'produktivitas_tertimbang');
    }

    /**
     * Laporan Alsintan.
     *
     * Kolom "laporan alsintan.jpeg": Jenis Alat, Sumber Dana, Tahun Pengadaan,
     * Poktan Penerima, Ketua Poktan, Alamat (Kec./Desa), Jumlah (Unit). Satu
     * baris per alsintan, dikelompokkan per SP, subtotal Jumlah.
     *
     * CATATAN: field kita bernama `sumber_perolehan` dan `tahun_perolehan`;
     * label kolom mengikuti berkas rujukan. Penyeragaman nama field alsintan
     * ke `sumber_dana` / `tahun_pengadaan` (seperti saprotan) adalah usul
     * revisi tersendiri.
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
                'kecamatan' => $pok['kecamatan'] ?? '-',
                'desa' => $pok['desa'] ?? '-',
                'jenis_alat' => $a['nama_alat'],
                'sumber_dana' => $a['sumber_perolehan'],
                'tahun_pengadaan' => $a['tahun_perolehan'],
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
                    'sp_id' => $pok['satuan_permukiman_id'] ?? null,
                    'sp' => $pok['satuan_permukiman'] ?? $s['satuan_permukiman'] ?? '-',
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
     * Laporan Monografi SP.
     *
     * Monografi UPT asli sangat naratif (iklim, topografi, sertifikat tanah,
     * KB, agama) dan data itu belum ada di sistem. Yang disajikan di sini
     * indikator per SP yang memang kita punya. Monografi penuh per SP menyusul
     * bersama field Bab II Monografi (Rombongan C).
     *
     * @return array{baris: array<int, array<string, mixed>>}
     */
    public static function monografiSp(): array
    {
        $rekap = collect(DummyData::rekapPerSp())->keyBy('satuan_permukiman_id');

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

        foreach (DummyData::satuanPermukiman() as $s) {
            $r = $rekap->get($s['id_satuan_permukiman']);

            $baris[] = [
                'sp' => $s['nama'],
                'kode' => $s['kode_sp'],
                'kecamatan' => $s['kecamatan'],
                'desa' => $s['desa'],
                'tahun_penempatan' => $s['tahun_penempatan'],
                'luas_wilayah' => (float) $s['luas_lahan'],
                'kk_rencana' => (int) $s['jumlah_kk_rencana'],
                'kk_terisi' => (int) $s['jumlah_kk_terisi'],
                'rumah_terhuni' => $r['rumah_terhuni'] ?? 0,
                'poktan' => $poktanPerSp[$s['id_satuan_permukiman']] ?? 0,
                'lahan_tergarap' => round($lahanTergarap[$s['id_satuan_permukiman']] ?? 0, 2),
                'produksi_ton' => $r['volume_panen'] ?? 0,
                'pengaduan_terbuka' => $r['pengaduan_terbuka'] ?? 0,
            ];
        }

        return ['baris' => $baris];
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
        $perSp = [];

        foreach ($baris as $b) {
            $perSp[$b['sp']][] = $b;
        }

        $kelompok = [];
        $total = array_fill_keys($kolomJumlah, 0.0);

        foreach ($perSp as $namaSp => $isi) {
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
                'sp' => $namaSp,
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
