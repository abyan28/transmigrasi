<?php

namespace App\Support;

use App\Enums\JenisReferensi;
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
    /**
     * Nilai skor untuk tiap kondisi aset, dibaca dari data master referensi.
     *
     * SEJAK 2026-08-20 BUKAN LAGI KONSTANTA. Nilainya berpindah ke kolom
     * `referensi.nilai_skor` agar Admin dapat menyesuaikannya lewat antarmuka,
     * sejalan dengan bobot parameter yang sudah lebih dulu berupa data
     * (erd.md 7.3). Sebelumnya bobot dapat disunting tetapi nilai kondisinya
     * tidak, padahal keduanya sama-sama menentukan skor akhir.
     *
     * Mengubahnya mempengaruhi penilaian BERIKUTNYA saja. Penilaian yang sudah
     * tersimpan tidak ikut berubah, sebab `penilaian_sp.rincian` menyalin nilai
     * yang berlaku saat penilaian dibuat (kamus data 5.5). Tanpa salinan itu,
     * laporan yang sudah dicetak akan berbeda dari tampilan sistem setiap kali
     * Admin menyunting skor.
     *
     * @return array<string, float> Peta nilai kondisi ke skornya
     */
    public static function nilaiKondisi(): array
    {
        return DummyData::skorKondisi();
    }

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
     * `referensi_id` MENGGANTIKAN `jenis_rujukan` yang dulu berupa teks.
     * Alasannya bukan kerapian: jenis infrastruktur dan fasilitas kini
     * dikelola Admin, dan rujukan berbasis teks putus tanpa pesan apa pun
     * begitu Admin memperbaiki ejaan `Air` menjadi `Air Bersih`. Parameter itu
     * lalu diam-diam menilai setiap SP sebagai tidak punya air, dan status SP
     * jatuh karena satu penyuntingan ejaan.
     *
     * @return array<int, array<string, mixed>> Parameter penilaian
     */
    public static function parameter(): array
    {
        $p = TingkatKebutuhan::Primer;
        $s = TingkatKebutuhan::Sekunder;
        $t = TingkatKebutuhan::Tersier;

        $infra = fn (string $nilai) => DummyData::referensiId(JenisReferensi::JenisInfrastruktur, $nilai);
        $fasil = fn (string $nilai) => DummyData::referensiId(JenisReferensi::JenisFasilitas, $nilai);

        return [
            // Primer: tanpa ini tempat tidak layak dihuni
            ['kode' => 'air_bersih', 'nama' => 'Air Bersih', 'tingkat' => $p, 'bobot' => $p->bobotBawaan(), 'sumber' => 'Infrastruktur', 'referensi_id' => $infra('Air')],
            ['kode' => 'jalan_penghubung', 'nama' => 'Jalan Penghubung', 'tingkat' => $p, 'bobot' => $p->bobotBawaan(), 'sumber' => 'Infrastruktur', 'referensi_id' => $infra('Jalan Penghubung')],
            ['kode' => 'listrik', 'nama' => 'Listrik', 'tingkat' => $p, 'bobot' => $p->bobotBawaan(), 'sumber' => 'Infrastruktur', 'referensi_id' => $infra('Listrik')],

            // Sekunder: masih dapat dihuni, tetapi tidak berkembang
            ['kode' => 'kesehatan', 'nama' => 'Fasilitas Kesehatan', 'tingkat' => $s, 'bobot' => $s->bobotBawaan(), 'sumber' => 'Fasilitas', 'referensi_id' => $fasil('Kesehatan')],
            ['kode' => 'pendidikan_dasar', 'nama' => 'Pendidikan Dasar', 'tingkat' => $s, 'bobot' => $s->bobotBawaan(), 'sumber' => 'Fasilitas', 'referensi_id' => $fasil('Pendidikan Dasar')],
            ['kode' => 'telekomunikasi', 'nama' => 'Telekomunikasi', 'tingkat' => $s, 'bobot' => $s->bobotBawaan(), 'sumber' => 'Infrastruktur', 'referensi_id' => $infra('Telekomunikasi')],
            ['kode' => 'sanitasi', 'nama' => 'Sanitasi', 'tingkat' => $s, 'bobot' => $s->bobotBawaan(), 'sumber' => 'Infrastruktur', 'referensi_id' => $infra('Sanitasi')],

            // Tersier: penunjang produktivitas dan kehidupan sosial
            ['kode' => 'irigasi', 'nama' => 'Irigasi', 'tingkat' => $t, 'bobot' => $t->bobotBawaan(), 'sumber' => 'Infrastruktur', 'referensi_id' => $infra('Irigasi')],
            ['kode' => 'gudang', 'nama' => 'Gudang Pascapanen', 'tingkat' => $t, 'bobot' => $t->bobotBawaan(), 'sumber' => 'Infrastruktur', 'referensi_id' => $infra('Gudang')],
            ['kode' => 'jalan_produksi', 'nama' => 'Jalan Produksi', 'tingkat' => $t, 'bobot' => $t->bobotBawaan(), 'sumber' => 'Infrastruktur', 'referensi_id' => $infra('Jalan Produksi')],
            ['kode' => 'balai', 'nama' => 'Balai Pertemuan', 'tingkat' => $t, 'bobot' => $t->bobotBawaan(), 'sumber' => 'Fasilitas', 'referensi_id' => $fasil('Balai Pertemuan')],
            ['kode' => 'ibadah', 'nama' => 'Rumah Ibadah', 'tingkat' => $t, 'bobot' => $t->bobotBawaan(), 'sumber' => 'Fasilitas', 'referensi_id' => $fasil('Ibadah')],
            ['kode' => 'pasar_kios', 'nama' => 'Pasar atau Kios Saprotan', 'tingkat' => $t, 'bobot' => $t->bobotBawaan(), 'sumber' => 'Infrastruktur', 'referensi_id' => $infra('Pasar atau Kios Saprotan')],
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

            // Kolom `jenis` pada aset masih menyimpan teks, sedangkan parameter
            // menunjuk id. Penerjemahan terjadi di sini, satu tempat, sehingga
            // penggantian ejaan oleh Admin ikut terbawa dengan sendirinya.
            //
            // Bila idnya tidak ditemukan, parameter itu DILEWATI, bukan dinilai
            // nol. Menilainya nol berarti seluruh SP mendadak dianggap tidak
            // punya air hanya karena satu baris referensi hilang, dan pada
            // parameter primer itu langsung menjatuhkan status setiap SP.
            $jenisRujukan = DummyData::referensiNilai($par['referensi_id']);

            if ($jenisRujukan === null) {
                continue;
            }

            $kondisi = self::kondisiTerbaik($sumber, $kolom, $jenisRujukan);
            $nilai = self::nilaiKondisi()[$kondisi] ?? self::NILAI_TIDAK_ADA;

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
            $nilai = self::nilaiKondisi()[$a['kondisi'] ?? ''] ?? self::NILAI_TIDAK_ADA;

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
            if ($r['nilai'] <= (self::nilaiKondisi()['Rusak Berat'] ?? 0.2)) {
                $penyebab[] = $r['nama'].' ('.mb_strtolower($r['kondisi']).')';
            }
        }

        return $penyebab;
    }
}
