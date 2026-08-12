<?php

namespace App\Support;

use App\Enums\JenisFasilitas;
use App\Enums\JenisInfrastruktur;
use App\Enums\StatusKondisiSp;
use App\Enums\TingkatKebutuhan;

/**
 * Penghitung status kondisi satuan permukiman.
 *
 * Menilai kesiapan layanan dasar sebuah SP lalu menyimpulkannya menjadi satu
 * label yang mudah dibaca pemangku kepentingan (agents/rules.md bagian 10c).
 *
 * TIGA HAL YANG MUDAH KELIRU BILA KELAK DIUBAH:
 *
 * 1. **Ketiadaan berbeda dari kerusakan.** SP yang belum pernah memiliki
 *    jaringan telekomunikasi memerlukan pembangunan, sedangkan yang
 *    memilikinya tetapi rusak memerlukan perbaikan. Parameter tanpa aset
 *    yang bersesuaian dinilai Tidak Ada (0), bukan dikeluarkan dari
 *    perhitungan. Mengeluarkannya akan menaikkan skor SP yang justru paling
 *    membutuhkan perhatian.
 *
 * 2. **Aturan primer nol mengalahkan skor.** Satu parameter primer yang
 *    tidak tersedia membuat SP berstatus Perlu Penanganan berapa pun skornya,
 *    sebab rata-rata tidak boleh menutupi kegagalan pada hal yang mutlak.
 *
 * 3. **Bobot berasal dari data, bukan dari kode.** Bobot yang dipakai
 *    disalin ke dalam hasil penilaian, agar laporan yang sudah dicetak tetap
 *    sahih ketika Admin mengubah bobot di kemudian hari. Prinsip ini sama
 *    dengan penyalinan satuan_id pada hasil panen.
 */
class PenilaianKondisiSp
{
    /** Nilai skor untuk tiap kondisi aset. */
    public const NILAI_KONDISI = [
        'Baik' => 1.0,
        'Rusak Ringan' => 0.5,
        'Rusak Berat' => 0.2,
    ];

    /** Nilai untuk parameter yang asetnya tidak ditemukan sama sekali. */
    public const NILAI_TIDAK_ADA = 0.0;

    /** Label kondisi ketika aset tidak ditemukan. */
    public const KONDISI_TIDAK_ADA = 'Tidak Ada';

    /**
     * Daftar parameter penilaian beserta bobot bawaannya.
     *
     * CATATAN: pada Tahap 4 daftar ini dibaca dari tabel
     * `parameter_penilaian_sp` agar Admin dapat menyesuaikan bobotnya lewat
     * antarmuka. Bentuk larik di sini sengaja dibuat sama dengan kolom tabel
     * tersebut, sehingga penggantian sumber tidak mengubah pemakainya.
     *
     * @return array<int, array<string, mixed>> Parameter penilaian
     */
    public static function parameter(): array
    {
        $p = TingkatKebutuhan::Primer;
        $s = TingkatKebutuhan::Sekunder;
        $t = TingkatKebutuhan::Tersier;

        return [
            // Primer: tanpa ini tempat tidak layak dihuni
            ['kode' => 'air_bersih', 'nama' => 'Air Bersih', 'tingkat' => $p, 'bobot' => $p->bobotBawaan(), 'sumber' => 'Infrastruktur', 'jenis_rujukan' => JenisInfrastruktur::Air->value],
            ['kode' => 'jalan_penghubung', 'nama' => 'Jalan Penghubung', 'tingkat' => $p, 'bobot' => $p->bobotBawaan(), 'sumber' => 'Infrastruktur', 'jenis_rujukan' => JenisInfrastruktur::JalanPenghubung->value],
            ['kode' => 'listrik', 'nama' => 'Listrik', 'tingkat' => $p, 'bobot' => $p->bobotBawaan(), 'sumber' => 'Infrastruktur', 'jenis_rujukan' => JenisInfrastruktur::Listrik->value],

            // Sekunder: masih dapat dihuni, tetapi tidak berkembang
            ['kode' => 'kesehatan', 'nama' => 'Fasilitas Kesehatan', 'tingkat' => $s, 'bobot' => $s->bobotBawaan(), 'sumber' => 'Fasilitas', 'jenis_rujukan' => JenisFasilitas::Kesehatan->value],
            ['kode' => 'pendidikan_dasar', 'nama' => 'Pendidikan Dasar', 'tingkat' => $s, 'bobot' => $s->bobotBawaan(), 'sumber' => 'Fasilitas', 'jenis_rujukan' => JenisFasilitas::PendidikanDasar->value],
            ['kode' => 'telekomunikasi', 'nama' => 'Telekomunikasi', 'tingkat' => $s, 'bobot' => $s->bobotBawaan(), 'sumber' => 'Infrastruktur', 'jenis_rujukan' => JenisInfrastruktur::Telekomunikasi->value],
            ['kode' => 'sanitasi', 'nama' => 'Sanitasi', 'tingkat' => $s, 'bobot' => $s->bobotBawaan(), 'sumber' => 'Infrastruktur', 'jenis_rujukan' => JenisInfrastruktur::Sanitasi->value],

            // Tersier: penunjang produktivitas dan kehidupan sosial
            ['kode' => 'irigasi', 'nama' => 'Irigasi', 'tingkat' => $t, 'bobot' => $t->bobotBawaan(), 'sumber' => 'Infrastruktur', 'jenis_rujukan' => JenisInfrastruktur::Irigasi->value],
            ['kode' => 'gudang', 'nama' => 'Gudang Pascapanen', 'tingkat' => $t, 'bobot' => $t->bobotBawaan(), 'sumber' => 'Infrastruktur', 'jenis_rujukan' => JenisInfrastruktur::Gudang->value],
            ['kode' => 'jalan_produksi', 'nama' => 'Jalan Produksi', 'tingkat' => $t, 'bobot' => $t->bobotBawaan(), 'sumber' => 'Infrastruktur', 'jenis_rujukan' => JenisInfrastruktur::JalanProduksi->value],
            ['kode' => 'balai', 'nama' => 'Balai Pertemuan', 'tingkat' => $t, 'bobot' => $t->bobotBawaan(), 'sumber' => 'Fasilitas', 'jenis_rujukan' => JenisFasilitas::BalaiPertemuan->value],
            ['kode' => 'ibadah', 'nama' => 'Rumah Ibadah', 'tingkat' => $t, 'bobot' => $t->bobotBawaan(), 'sumber' => 'Fasilitas', 'jenis_rujukan' => JenisFasilitas::Ibadah->value],
            ['kode' => 'pasar_kios', 'nama' => 'Pasar atau Kios Saprotan', 'tingkat' => $t, 'bobot' => $t->bobotBawaan(), 'sumber' => 'Infrastruktur', 'jenis_rujukan' => JenisInfrastruktur::PasarKios->value],
        ];
    }

    /**
     * Menilai satu satuan permukiman.
     *
     * @param  int  $satuanPermukimanId  Id SP yang dinilai
     * @param  array<int, array<string, mixed>>|null  $infrastruktur  Aset infrastruktur; null berarti dibaca dari data contoh
     * @param  array<int, array<string, mixed>>|null  $fasilitas  Fasilitas SP; null berarti dibaca dari data contoh
     * @return array<string, mixed> Hasil penilaian beserta rinciannya
     */
    public static function nilai(int $satuanPermukimanId, ?array $infrastruktur = null, ?array $fasilitas = null): array
    {
        $infrastruktur ??= DummyData::infrastruktur();
        $fasilitas ??= DummyData::fasilitasSp();

        // Hanya aset milik SP yang sedang dinilai
        $asetInfra = array_filter($infrastruktur, fn ($a) => ($a['satuan_permukiman_id'] ?? null) === $satuanPermukimanId);
        $asetFasilitas = array_filter($fasilitas, fn ($a) => ($a['satuan_permukiman_id'] ?? null) === $satuanPermukimanId);

        $rincian = [];
        $totalBobotNilai = 0.0;
        $totalBobot = 0;
        $adaPrimerNol = false;

        foreach (self::parameter() as $par) {
            $sumber = $par['sumber'] === 'Fasilitas' ? $asetFasilitas : $asetInfra;
            $kolom = $par['sumber'] === 'Fasilitas' ? 'jenis_fasilitas' : 'jenis';

            $kondisi = self::kondisiTerbaik($sumber, $kolom, $par['jenis_rujukan']);
            $nilai = self::NILAI_KONDISI[$kondisi] ?? self::NILAI_TIDAK_ADA;

            // Aturan primer nol: satu saja cukup untuk menentukan status
            if ($par['tingkat'] === TingkatKebutuhan::Primer && $nilai === self::NILAI_TIDAK_ADA) {
                $adaPrimerNol = true;
            }

            $totalBobotNilai += $par['bobot'] * $nilai;
            $totalBobot += $par['bobot'];

            $rincian[] = [
                'kode' => $par['kode'],
                'nama' => $par['nama'],
                'tingkat' => $par['tingkat']->value,
                'bobot' => $par['bobot'],
                'kondisi' => $kondisi,
                'nilai' => $nilai,
            ];
        }

        $skor = $totalBobot > 0 ? round($totalBobotNilai / $totalBobot * 100, 2) : 0.0;
        $status = StatusKondisiSp::dariSkor($skor, $adaPrimerNol);

        return [
            'satuan_permukiman_id' => $satuanPermukimanId,
            'skor' => $skor,
            'status' => $status,
            'ada_primer_nol' => $adaPrimerNol,
            'rincian' => $rincian,
            'tanggal_penilaian' => date('Y-m-d'),
        ];
    }

    /**
     * Mengambil kondisi TERBAIK di antara aset sejenis milik satu SP.
     *
     * Dipilih yang terbaik, bukan rata-rata, karena satu sumur bor yang
     * berfungsi sudah memenuhi kebutuhan air meski ada sumur lain yang rusak.
     * Rata-rata akan menghukum SP yang justru memiliki aset cadangan.
     *
     * @param  array<int, array<string, mixed>>  $aset  Aset milik SP
     * @param  string  $kolom  Nama kolom jenis pada aset
     * @param  string  $jenis  Nilai jenis yang dicari
     * @return string Nama kondisi, atau `Tidak Ada` bila tidak ditemukan
     */
    private static function kondisiTerbaik(array $aset, string $kolom, string $jenis): string
    {
        $cocok = array_filter($aset, fn ($a) => ($a[$kolom] ?? null) === $jenis);

        if ($cocok === []) {
            return self::KONDISI_TIDAK_ADA;
        }

        $terbaik = self::KONDISI_TIDAK_ADA;
        $nilaiTerbaik = -1.0;

        foreach ($cocok as $a) {
            $nilai = self::NILAI_KONDISI[$a['kondisi'] ?? ''] ?? self::NILAI_TIDAK_ADA;

            if ($nilai > $nilaiTerbaik) {
                $nilaiTerbaik = $nilai;
                $terbaik = $a['kondisi'] ?? self::KONDISI_TIDAK_ADA;
            }
        }

        return $terbaik;
    }

    /**
     * Menilai seluruh satuan permukiman sekaligus.
     *
     * @return array<int, array<string, mixed>> Hasil penilaian per SP
     */
    public static function nilaiSeluruhSp(): array
    {
        $hasil = [];

        foreach (DummyData::satuanPermukiman() as $sp) {
            $penilaian = self::nilai($sp['id_satuan_permukiman']);
            $penilaian['satuan_permukiman'] = $sp['nama'];
            $hasil[] = $penilaian;
        }

        return $hasil;
    }

    /**
     * Menghitung jumlah SP per status, untuk kartu statistik dashboard.
     *
     * @return array<string, int> Peta nama status ke jumlah SP
     */
    public static function rekapStatus(): array
    {
        $rekap = [];

        foreach (StatusKondisiSp::cases() as $status) {
            $rekap[$status->value] = 0;
        }

        foreach (self::nilaiSeluruhSp() as $p) {
            $rekap[$p['status']->value]++;
        }

        return $rekap;
    }

    /**
     * Menyusun daftar parameter yang menjadi penyebab sebuah SP bermasalah.
     *
     * Dipakai antarmuka agar label selalu disertai alasannya, sebab label
     * tanpa rincian berhenti sebagai stempel (agents/rules.md bagian 10c.1
     * poin 4).
     *
     * @param  array<string, mixed>  $penilaian  Hasil dari nilai()
     * @return array<int, string> Nama parameter yang bernilai nol atau rusak berat
     */
    public static function penyebabUtama(array $penilaian): array
    {
        $penyebab = [];

        foreach ($penilaian['rincian'] as $r) {
            if ($r['nilai'] <= self::NILAI_KONDISI['Rusak Berat']) {
                $penyebab[] = $r['nama'] . ' (' . mb_strtolower($r['kondisi']) . ')';
            }
        }

        return $penyebab;
    }
}
