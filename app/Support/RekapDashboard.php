<?php

namespace App\Support;

use App\Enums\PendidikanTerakhir;
use App\Enums\StatusAnggotaKeluarga;
use App\Enums\StatusHunian;
use App\Enums\StatusPengaduan;
use App\Enums\StatusTinggal;
use App\Models\HasilPanen;
use App\Models\Infrastruktur;
use App\Models\Lahan;
use App\Models\Pengaduan;
use App\Models\Rumah;
use App\Models\SatuanPermukiman;
use App\Models\Transmigran;
use Illuminate\Database\Eloquent\Builder;

/**
 * Angka dashboard dari Eloquent (Task 9.1, rules.md 8g DIBALIK 2026-09-04).
 *
 * Sebelumnya dashboard membaca `DummyData::ringkasanDashboard()` -- larik
 * tetap angka kawasan asli (~1.140 KK) yang tak pernah berubah walau data
 * bertambah. Keputusan pemilik proyek: dashboard SEHARUSNYA tumbuh mengikuti
 * data yang benar-benar tercatat (`prd.md` 7.8 "grafik ... tiap tahun"),
 * bukan angka tetap. Sekarang kecil (data contoh, 8 KK) dan itu JUJUR --
 * tumbuh benar seiring pendataan sungguhan.
 *
 * TIDAK memakai `withoutGlobalScopes()`: berbeda dari `LaporanData` (laporan
 * = dokumen kawasan penuh), dashboard adalah tampilan PER PENGGUNA -- operator
 * Per SP semestinya melihat angka SP-nya saja, scope `CakupanDataSp` berlaku
 * apa adanya di sini.
 *
 * Dua kartu tak dapat direkonstruksi dari tabel keadaan-sekarang (keputusan
 * pemilik 2026-09-04):
 * - "KK Keluar per tahun" butuh `transmigran.tahun_keluar` (kolom baru,
 *   Task 9.1) -- riwayat SEBELUM kolom itu ada tetap tak terlacak.
 * - "Pendapatan Keluarga per tahun" TIDAK ada padanannya sama sekali
 *   (`pendapatan_per_bulan` keadaan sekarang, tanpa riwayat) -- diganti
 *   `pendapatanSaatIni()`, bukan deret tahunan.
 */
class RekapDashboard
{
    /**
     * Ringkasan kartu utama dashboard.
     *
     * @return array<string, mixed>
     */
    public static function ringkasan(): array
    {
        $tahun = self::tahunTerakhir();
        $totalPanen = self::totalPanenTahun($tahun);
        $kkJiwa = self::jumlahKkJiwa();

        return [
            'jumlah_kk' => $kkJiwa['jumlah_kk'],
            'jumlah_jiwa' => $kkJiwa['jumlah_jiwa'],
            'jumlah_petani' => Transmigran::where('status_tinggal', StatusTinggal::Aktif->value)
                ->where('pekerjaan_kepala_keluarga', 'PETANI')->count(),
            'rumah_terhuni' => Rumah::where('status_hunian', StatusHunian::Dihuni->value)->count(),
            'rumah_total' => Rumah::count(),
            'luas_lahan_total' => round((float) Lahan::sum('luas_usaha'), 2),
            'pengaduan_terbuka' => Pengaduan::whereNot('status', StatusPengaduan::Selesai->value)->count(),
            'volume_panen_ton' => $totalPanen['produksi_ton'],
            'harga_rata_rata' => self::hargaRataRata($tahun),
            'realisasi_tanam_ha' => $totalPanen['realisasi_tanam'],
            'hasil_panen_ha' => $totalPanen['hasil_panen'],
            'puso_ha' => $totalPanen['puso'],
            'belum_dipanen_ha' => $totalPanen['belum_dipanen'],
            'produktivitas_ton_ha' => $totalPanen['hasil_panen'] > 0
                ? round($totalPanen['produksi_ton'] / $totalPanen['hasil_panen'], 3) : 0.0,
        ];
    }

    /**
     * Deret tahunan untuk grafik. Rentang tahun dari data nyata yang ada
     * (`tahun_kedatangan` paling awal s.d. tahun berjalan), diisi PENUH
     * (bukan cuma tahun berisi baris) supaya grafik terbaca sebagai garis
     * waktu, bukan titik tersebar. Nol pada tahun yang belum ada datanya --
     * itu keadaan sungguhan, bukan kekosongan yang disembunyikan.
     *
     * @return array<string, mixed>
     */
    public static function deret(): array
    {
        $tahunMasuk = (int) (Transmigran::min('tahun_kedatangan') ?? date('Y'));
        $tahunIni = (int) date('Y');
        $tahun = range($tahunMasuk, $tahunIni);

        $masukPerTahun = Transmigran::query()
            ->selectRaw('tahun_kedatangan, count(*) as jumlah')
            ->groupBy('tahun_kedatangan')->pluck('jumlah', 'tahun_kedatangan');
        $keluarPerTahun = Transmigran::query()
            ->whereNotNull('tahun_keluar')
            ->selectRaw('tahun_keluar, count(*) as jumlah')
            ->groupBy('tahun_keluar')->pluck('jumlah', 'tahun_keluar');
        $petaniPerTahun = Transmigran::query()
            ->where('pekerjaan_kepala_keluarga', 'PETANI')
            ->selectRaw('tahun_kedatangan, count(*) as jumlah')
            ->groupBy('tahun_kedatangan')->pluck('jumlah', 'tahun_kedatangan');

        // Taksiran kumulatif "berapa KK sudah tiba dan belum tercatat keluar
        // pada tahun itu" -- BUKAN potret riwayat sungguhan (transmigran
        // adalah tabel keadaan-sekarang), tetapi dihitung dari data nyata dan
        // tumbuh benar seiring pendataan, bukan angka tetap.
        $kk = [];
        $jiwaPerKk = self::rasioJiwaPerKkSaatIni();
        $petaniKumulatif = [];
        $kumulatif = 0;
        $petaniKum = 0;

        foreach ($tahun as $t) {
            $kumulatif += (int) ($masukPerTahun[$t] ?? 0);
            $kumulatif -= (int) ($keluarPerTahun[$t] ?? 0);
            // Petani yang keluar tidak terlacak terpisah dari KK keluar pada
            // umumnya (`tahun_keluar` tak menyimpan pekerjaannya saat itu),
            // sehingga taksiran ini hanya bertambah -- batas yang bisa
            // dijamin benar oleh data yang ada.
            $petaniKum += (int) ($petaniPerTahun[$t] ?? 0);

            $kk[] = max(0, $kumulatif);
            $petaniKumulatif[] = max(0, $petaniKum);
        }

        return [
            'tahun' => $tahun,
            'jumlah_kk' => $kk,
            'jumlah_jiwa' => array_map(fn ($n) => (int) round($n * $jiwaPerKk), $kk),
            'jumlah_petani' => $petaniKumulatif,
            'kk_masuk' => array_map(fn ($t) => (int) ($masukPerTahun[$t] ?? 0), $tahun),
            'kk_keluar' => array_map(fn ($t) => (int) ($keluarPerTahun[$t] ?? 0), $tahun),
            'volume_panen' => array_map(fn ($t) => self::totalPanenTahun($t)['produksi_ton'], $tahun),
            'harga_rata_rata' => array_map(fn ($t) => self::hargaRataRata($t), $tahun),
        ];
    }

    /**
     * Sebaran pendapatan kepala keluarga SAAT INI -- pengganti kartu tren
     * "per tahun" yang datanya tak tersedia (`pendapatan_per_bulan` keadaan
     * sekarang, tanpa riwayat; keputusan pemilik 2026-09-04).
     *
     * @return array{rata_rata: float, jumlah_kk: int}
     */
    public static function pendapatanSaatIni(): array
    {
        $aktif = Transmigran::where('status_tinggal', StatusTinggal::Aktif->value)
            ->whereNotNull('pendapatan_per_bulan');

        return [
            'rata_rata' => round((float) $aktif->avg('pendapatan_per_bulan'), 0),
            'jumlah_kk' => $aktif->count(),
        ];
    }

    /**
     * Rekap per SP. `jumlah_kk` dan `volume_panen` ikut `$tahun` bila diisi
     * (taksiran, lihat `hadirPadaTahun()`/`RekapPanen`); `rumah_terhuni` dan
     * `luas_lahan` SELALU keadaan sekarang -- rumah/lahan adalah tabel
     * keadaan-sekarang, tanpa riwayat per tahun untuk ditarik.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function perSp(?int $tahun = null): array
    {
        $tahunPanen = $tahun ?? self::tahunTerakhir();
        $panenPerSp = collect(RekapPanen::rekap('sp', $tahunPanen))->keyBy('nama');

        // Kolom KK ikut tahun bila diisi (dipakai `/kependudukan/rekap`,
        // taksiran kumulatif -- lihat `hadirPadaTahun()`); bawaannya keadaan
        // sekarang (dipakai dashboard).
        $kkQuery = $tahun === null
            ? Transmigran::where('status_tinggal', StatusTinggal::Aktif->value)
            : self::hadirPadaTahun($tahun);
        $kkPerSp = $kkQuery
            ->selectRaw('satuan_permukiman_id, count(*) as jumlah')
            ->groupBy('satuan_permukiman_id')->pluck('jumlah', 'satuan_permukiman_id');
        $rumahPerSp = Rumah::where('status_hunian', StatusHunian::Dihuni->value)
            ->selectRaw('satuan_permukiman_id, count(*) as jumlah')
            ->groupBy('satuan_permukiman_id')->pluck('jumlah', 'satuan_permukiman_id');
        $lahanPerSp = Lahan::query()
            ->selectRaw('satuan_permukiman_id, sum(luas_usaha) as total')
            ->groupBy('satuan_permukiman_id')->pluck('total', 'satuan_permukiman_id');

        return SatuanPermukiman::orderBy('nama')->get()->map(fn (SatuanPermukiman $sp): array => [
            'satuan_permukiman_id' => $sp->id_satuan_permukiman,
            'satuan_permukiman' => $sp->nama,
            'jumlah_kk' => (int) ($kkPerSp[$sp->id_satuan_permukiman] ?? 0),
            'rumah_terhuni' => (int) ($rumahPerSp[$sp->id_satuan_permukiman] ?? 0),
            'luas_lahan' => round((float) ($lahanPerSp[$sp->id_satuan_permukiman] ?? 0), 2),
            'volume_panen' => round((float) ($panenPerSp[$sp->nama]['produksi_ton'] ?? 0), 2),
        ])->all();
    }

    /**
     * Deret tahunan dalam bentuk baris (dipakai tabel `/kependudukan/rekap`;
     * `deret()` di atas dipakai grafik). Tanpa `pendapatan_rata_rata` --
     * kolom itu tak dapat direkonstruksi per tahun (lihat `pendapatanSaatIni()`).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function perTahun(): array
    {
        $deret = self::deret();
        $hasil = [];

        foreach ($deret['tahun'] as $i => $tahun) {
            $hasil[] = [
                'tahun' => $tahun,
                'jumlah_kk' => $deret['jumlah_kk'][$i],
                'jumlah_jiwa' => $deret['jumlah_jiwa'][$i],
                'jumlah_petani' => $deret['jumlah_petani'][$i],
                'kk_masuk' => $deret['kk_masuk'][$i],
                'kk_keluar' => $deret['kk_keluar'][$i],
            ];
        }

        return $hasil;
    }

    /**
     * Baris transmigran yang "hadir" pada satu tahun: sudah tiba
     * (`tahun_kedatangan`) dan belum tercatat keluar pada tahun itu
     * (`tahun_keluar` kosong atau setelah tahun itu).
     *
     * TAKSIRAN, bukan potret riwayat sungguhan -- lihat `deret()`. Dipakai
     * seluruh rekap berparameter tahun pada `/kependudukan/rekap`.
     */
    private static function hadirPadaTahun(int $tahun): Builder
    {
        return Transmigran::query()
            ->where('tahun_kedatangan', '<=', $tahun)
            ->where(fn ($q) => $q->whereNull('tahun_keluar')->orWhere('tahun_keluar', '>', $tahun));
    }

    /**
     * @return array<string, int>
     */
    public static function pekerjaanPerTahun(int $tahun): array
    {
        return self::hadirPadaTahun($tahun)
            ->selectRaw('pekerjaan_kepala_keluarga, count(*) as jumlah')
            ->groupBy('pekerjaan_kepala_keluarga')
            ->orderByDesc('jumlah')
            ->pluck('jumlah', 'pekerjaan_kepala_keluarga')
            ->map(fn ($j) => (int) $j)
            ->all();
    }

    /**
     * Sebaran status tinggal di antara yang hadir pada tahun itu. Baris yang
     * sudah tercatat keluar (`tahun_keluar` <= tahun) TIDAK termasuk himpunan
     * "hadir", sehingga secara struktural kelompok ini condong ke Aktif --
     * itulah batas taksirannya: status masa lalu (Pindah/Tidak Aktif PADA
     * tahun itu) tidak tersimpan, yang ada hanya status TERKINI.
     *
     * @return array<string, int>
     */
    public static function penghuniPerTahun(int $tahun): array
    {
        return self::hadirPadaTahun($tahun)
            ->selectRaw('status_tinggal, count(*) as jumlah')
            ->groupBy('status_tinggal')
            ->pluck('jumlah', 'status_tinggal')
            ->map(fn ($j) => (int) $j)
            ->all();
    }

    /**
     * @return array<string, int>
     */
    public static function daerahAsalPerTahun(int $tahun): array
    {
        $perId = self::hadirPadaTahun($tahun)
            ->whereNotNull('daerah_asal_kabupaten_id')
            ->selectRaw('daerah_asal_kabupaten_id, count(*) as jumlah')
            ->groupBy('daerah_asal_kabupaten_id')
            ->pluck('jumlah', 'daerah_asal_kabupaten_id');

        $hasil = [];
        foreach ($perId as $id => $jumlah) {
            $nama = DataWilayah::namaKabupaten((int) $id) ?? 'Tidak dicatat';
            $hasil[$nama] = ($hasil[$nama] ?? 0) + (int) $jumlah;
        }
        arsort($hasil);

        return $hasil;
    }

    /**
     * "Tidak ada lulusan S3" adalah informasi, bukan ketiadaan data -- jenjang
     * yang kosong tetap tampil sebagai nol, bukan hilang dari daftar.
     *
     * @return array<string, int>
     */
    public static function pendidikanPerTahun(int $tahun): array
    {
        $terisi = self::hadirPadaTahun($tahun)
            ->whereNotNull('pendidikan_terakhir')
            ->selectRaw('pendidikan_terakhir, count(*) as jumlah')
            ->groupBy('pendidikan_terakhir')
            ->pluck('jumlah', 'pendidikan_terakhir');

        $hasil = array_fill_keys(PendidikanTerakhir::nilai(), 0);
        foreach ($terisi as $jenjang => $jumlah) {
            $hasil[$jenjang] = (int) $jumlah;
        }

        return $hasil;
    }

    /**
     * @return array<string, int>
     */
    public static function sebaranPekerjaan(): array
    {
        return Transmigran::where('status_tinggal', StatusTinggal::Aktif->value)
            ->selectRaw('pekerjaan_kepala_keluarga, count(*) as jumlah')
            ->groupBy('pekerjaan_kepala_keluarga')
            ->orderByDesc('jumlah')
            ->pluck('jumlah', 'pekerjaan_kepala_keluarga')
            ->map(fn ($j) => (int) $j)
            ->all();
    }

    /**
     * @return array<string, int>
     */
    public static function rekapPenghuni(): array
    {
        return Transmigran::query()
            ->selectRaw('status_tinggal, count(*) as jumlah')
            ->groupBy('status_tinggal')
            ->pluck('jumlah', 'status_tinggal')
            ->map(fn ($j) => (int) $j)
            ->all();
    }

    /**
     * Volume panen tahun terakhir per komoditas, dalam ton.
     *
     * @return array<string, float>
     */
    public static function sebaranKomoditas(): array
    {
        $baris = RekapPanen::rekap('komoditas', self::tahunTerakhir());

        $hasil = [];
        foreach ($baris as $b) {
            if ($b['nama'] !== null && $b['produksi_ton'] > 0) {
                $hasil[$b['nama']] = $b['produksi_ton'];
            }
        }

        return $hasil;
    }

    /**
     * Kondisi infrastruktur per jenis. Tabel kecil (puluhan aset per
     * kawasan), tidak kena masalah skala populasi -- selalu boleh dihitung
     * dari Eloquent langsung.
     *
     * @return array<int, array{jenis: string, baik: int, rusak_ringan: int, rusak_berat: int}>
     */
    public static function statusInfrastruktur(): array
    {
        $baris = Infrastruktur::query()
            ->selectRaw('jenis, kondisi, count(*) as jumlah')
            ->groupBy('jenis', 'kondisi')
            ->get();

        $perJenis = [];
        foreach ($baris as $b) {
            $perJenis[$b->jenis] ??= ['jenis' => $b->jenis, 'baik' => 0, 'rusak_ringan' => 0, 'rusak_berat' => 0];

            $kolom = match ($b->kondisi) {
                'Baik' => 'baik',
                'Rusak Ringan' => 'rusak_ringan',
                'Rusak Berat' => 'rusak_berat',
                default => null, // 'Hilang' tak tercakup 3 kolom tabel ini
            };

            if ($kolom !== null) {
                $perJenis[$b->jenis][$kolom] += (int) $b->jumlah;
            }
        }

        return array_values($perJenis);
    }

    /**
     * Komoditas dengan volume terbesar tahun terakhir ("komoditas utama",
     * beda dari "komoditas unggulan" yang ditetapkan petugas -- rules.md 8.3c).
     */
    public static function komoditasUtama(): ?string
    {
        $sebaran = self::sebaranKomoditas();

        return $sebaran === [] ? null : array_key_first($sebaran);
    }

    /**
     * Tahun terakhir yang benar-benar terdata (bukan `date('Y')`): satu-satunya
     * yang dapat dijamin benar, sejalan `LaporanData::tahunDokumenBawaan()`.
     */
    public static function tahunTerakhir(): int
    {
        $tahun = RekapPanen::tahunTercatat();

        return $tahun[0] ?? (int) date('Y');
    }

    /**
     * Total kawasan tahun tertentu, dijumlah dari rekap per-SP (bukan dari
     * `rekap()` tanpa penyaring tahun -- itu kumulatif lintas tahun, dilarang
     * rules.md 8b).
     *
     * @return array{realisasi_tanam: float, hasil_panen: float, puso: float, belum_dipanen: float, produksi_ton: float}
     */
    private static function totalPanenTahun(int $tahun): array
    {
        $baris = collect(RekapPanen::rekap('sp', $tahun));

        return [
            'realisasi_tanam' => round((float) $baris->sum('realisasi_tanam'), 2),
            'hasil_panen' => round((float) $baris->sum('hasil_panen'), 2),
            'puso' => round((float) $baris->sum('puso'), 2),
            'belum_dipanen' => round((float) $baris->sum('belum_dipanen'), 2),
            'produksi_ton' => round((float) $baris->sum('produksi_ton'), 3),
        ];
    }

    /**
     * Rata-rata sederhana `harga_jual` tahun tertentu (bukan tertimbang lintas
     * satuan/komoditas -- itu jebakan pencampuran satuan; lihat rules.md 8d
     * untuk alasan produktivitas HARUS tertimbang sedangkan harga di sini
     * sengaja tidak, sebab satuannya sendiri belum seragam sebelum konversi).
     */
    private static function hargaRataRata(int $tahun): float
    {
        $rata = HasilPanen::query()
            ->whereNotNull('harga_jual')
            ->where('periode_panen', 'like', $tahun.'-%')
            ->avg('harga_jual');

        return round((float) ($rata ?? 0), 0);
    }

    /**
     * Rasio jiwa per KK di antara transmigran AKTIF saat ini, dipakai
     * menaksir jiwa pada deret tahun sebelumnya (lihat `deret()`).
     */
    private static function rasioJiwaPerKkSaatIni(): float
    {
        $kkJiwa = self::jumlahKkJiwa();

        return $kkJiwa['jumlah_kk'] > 0 ? $kkJiwa['jumlah_jiwa'] / $kkJiwa['jumlah_kk'] : 1.0;
    }

    /**
     * @return array{jumlah_kk: int, jumlah_jiwa: int}
     */
    private static function jumlahKkJiwa(): array
    {
        $jiwaAktif = Transmigran::query()
            ->where('status_tinggal', StatusTinggal::Aktif->value)
            ->withCount(['anggotaKeluarga as jiwa_aktif' => fn ($q) => $q->where('status', StatusAnggotaKeluarga::Aktif->value)])
            ->get();

        return [
            'jumlah_kk' => $jiwaAktif->count(),
            'jumlah_jiwa' => $jiwaAktif->count() + (int) $jiwaAktif->sum('jiwa_aktif'),
        ];
    }
}
