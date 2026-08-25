<?php

namespace App\Console\Commands;

use App\Enums\JenisReferensi;
use App\Support\DummyData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use RuntimeException;

/**
 * Menuliskan seluruh alamat yang perlu digilas menjadi berkas statis.
 *
 * Penggilas mengikuti tautan dari halaman ke halaman, tetapi tidak semua
 * halaman tertaut dari halaman lain. Galeri komponen dan halaman uji 403 hanya
 * dapat dicapai bila alamatnya diketahui lebih dulu, sedangkan halaman rincian
 * bergantung pada isi data contoh yang dapat bertambah sewaktu-waktu.
 *
 * Daftar ini dibangkitkan dari sumbernya langsung, bukan ditulis tangan, agar
 * penambahan data contoh tidak diam-diam meninggalkan halaman yang tidak ikut
 * tergilas. Dipakai oleh alur kerja `.github/workflows/deploy.yml`.
 *
 * Lihat agents/notes.md bagian 1b mengenai penyajian statis di GitHub Pages.
 */
class DaftarTautanStatis extends Command
{
    protected $signature = 'sim:tautan-statis';

    protected $description = 'Menuliskan daftar alamat untuk digilas menjadi berkas statis';

    public function handle(): int
    {
        foreach ($this->kumpulkan() as $alamat) {
            $this->line($alamat);
        }

        return self::SUCCESS;
    }

    /**
     * Mengumpulkan alamat dari dua sumber: rute tanpa parameter, dan rute
     * berparameter yang nilainya diambil dari data contoh.
     *
     * @return list<string>
     */
    private function kumpulkan(): array
    {
        $alamat = $this->ruteTanpaParameter();

        foreach ($this->rincianDariDataContoh() as $satu) {
            $alamat[] = $satu;
        }

        $alamat = array_values(array_unique($alamat));
        sort($alamat);

        return $alamat;
    }

    /**
     * Alamat yang sengaja tidak ikut digilas.
     *
     * - `uji-403` memang membalas 403 sebagai pemicu tampilan galat, sehingga
     *   penggilas akan menganggapnya kegagalan.
     * - `up` adalah pemeriksa kesehatan bawaan Laravel, bukan halaman.
     *
     * @var list<string>
     */
    private const DIKECUALIKAN = ['uji-403', 'up'];

    /**
     * Seluruh rute GET yang tidak memerlukan parameter.
     *
     * Rute pengunduhan dokumen sengaja dilewati karena melayani berkas dari
     * cakram privat, bukan halaman, dan tidak ada wujud statisnya.
     *
     * @return list<string>
     */
    private function ruteTanpaParameter(): array
    {
        $hasil = [];

        foreach (Route::getRoutes() as $rute) {
            if (! in_array('GET', $rute->methods(), true)) {
                continue;
            }

            $uri = $rute->uri();

            if (str_contains($uri, '{') || str_starts_with($uri, 'dokumen/')) {
                continue;
            }

            if (in_array($uri, self::DIKECUALIKAN, true)) {
                continue;
            }

            $hasil[] = '/'.ltrim($uri === '/' ? '' : $uri, '/');
        }

        return $hasil;
    }

    /**
     * Halaman rincian, satu per baris data contoh.
     *
     * @return list<string>
     */
    private function rincianDariDataContoh(): array
    {
        $peta = [
            'transmigran' => ['transmigran', 'id_transmigran'],
            'rumah' => ['rumah', 'id_rumah'],
            'lahan' => ['lahan', 'id_lahan'],
            'panen' => ['hasilPanen', 'id_hasil_panen'],
            'pengaduan' => ['pengaduan', 'id_pengaduan'],
            'poktan' => ['poktan', 'id_poktan'],
            'alsintan' => ['alsintan', 'id_alsintan'],
            'saprotan' => ['saprotan', 'id_saprotan'],
            'komoditas' => ['komoditas', 'id_komoditas'],
            'infrastruktur' => ['infrastruktur', 'id_infrastruktur'],
            'penanaman' => ['penanaman', 'id_penanaman'],
            'sp/inventaris' => ['inventarisSp', 'id_inventaris_sp'],
            'sp/fasilitas' => ['fasilitasSp', 'id_fasilitas_sp'],
            'dashboard/sp' => ['satuanPermukiman', 'id_satuan_permukiman'],
        ];

        $hasil = [];

        foreach ($peta as $awalan => [$sumber, $kunci]) {
            foreach (DummyData::$sumber() as $baris) {
                // Kunci yang salah tulis WAJIB menghentikan penerbitan, bukan
                // dilewati diam-diam. Pemeriksaan `isset` sebelumnya membuat
                // kekeliruan `no_sp` (seharusnya `id_satuan_permukiman`) lolos
                // tanpa jejak, sehingga enam halaman rincian SP tidak pernah
                // ikut tergilas dan baru ketahuan sebagai 404 di situs terbit.
                if (! array_key_exists($kunci, $baris)) {
                    throw new RuntimeException(sprintf(
                        'Kunci "%s" tidak ada pada DummyData::%s(). Kunci yang tersedia: %s.',
                        $kunci,
                        $sumber,
                        implode(', ', array_keys($baris)),
                    ));
                }

                $hasil[] = '/'.$awalan.'/'.$baris[$kunci];
            }
        }

        // Tautan tetap lacak pengaduan memakai nomor, bukan id.
        foreach (DummyData::pengaduan() as $baris) {
            if (isset($baris['nomor_pengaduan'])) {
                $hasil[] = '/lacak-pengaduan/'.$baris['nomor_pengaduan'];
            }
        }

        // Tautan tetap tab rekap panen. Nilainya terbatas dan ditentukan
        // tampilan, bukan data, sehingga disebut langsung. Daftar ini wajib
        // sejalan dengan batasan `where` pada rute `panen.rekap.kelompok`.
        foreach (['sp', 'komoditas', 'poktan'] as $kelompok) {
            $hasil[] = '/panen/rekap/'.$kelompok;
        }

        // Tautan tetap tab rekap pengaduan, mengikuti pola yang sama. Daftar
        // ini wajib sejalan dengan batasan `where` pada rute
        // `pengaduan.rekap.kelompok` dan $labelKelompok pada viewnya.
        foreach (['kategori', 'status', 'sp', 'prioritas', 'bidang'] as $kelompok) {
            $hasil[] = '/pengaduan/rekap/'.$kelompok;
        }

        // Tautan tetap tab rekap kependudukan, mengikuti pola yang sama.
        // Daftar ini wajib sejalan dengan batasan `where` pada rute
        // `kependudukan.rekap.kelompok` dan $labelKelompok pada viewnya.
        foreach (['tahun', 'sp', 'status', 'pekerjaan', 'asal', 'pendidikan'] as $kelompok) {
            $hasil[] = '/kependudukan/rekap/'.$kelompok;
        }

        // Halaman satu daftar referensi. Dibaca dari enumnya, bukan disebut
        // satu per satu: jenis baru wajib ikut terperiksa dengan sendirinya,
        // sebab halaman yang tidak masuk daftar ini tidak pernah tergilas dan
        // barulah ketahuan sebagai 404 di situs terbit.
        foreach (JenisReferensi::cases() as $jenis) {
            $hasil[] = '/master/referensi/'.$jenis->value;
        }

        return $hasil;
    }
}
