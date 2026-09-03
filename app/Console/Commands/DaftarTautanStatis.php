<?php

namespace App\Console\Commands;

use App\Support\DummyData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

/**
 * Menuliskan alamat HALAMAN PUBLIK yang perlu digilas menjadi berkas statis.
 *
 * Sejak Task 3.2b seluruh rute internal ber-`auth` dan membalas pengalihan ke
 * `/login` (bukan 200), sehingga penerbitan statis dibatasi ke halaman yang
 * boleh dibuka tanpa login: masuk, pemulihan kata sandi, dan kanal pengaduan
 * warga (keputusan `agents/notes.md` §1b.7 poin 1 / §6 A1). Rute ber-`auth`
 * dilewati otomatis lewat pemeriksaan `gatherMiddleware()`.
 *
 * Daftar ini dibangkitkan dari sumbernya langsung, bukan ditulis tangan.
 * Dipakai oleh alur kerja `.github/workflows/deploy.yml`.
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
     * Alamat yang sengaja tidak ikut digilas walau publik.
     *
     * - `uji-403` membalas 403 sebagai pemicu tampilan galat (kini ber-`auth`
     *   juga, jadi tersaring dua kali).
     * - `up` adalah pemeriksa kesehatan bawaan Laravel, bukan halaman.
     * - `infrastruktur` adalah rute lama ber-redirect 301.
     *
     * @var list<string>
     */
    private const DIKECUALIKAN = ['uji-403', 'up', 'infrastruktur'];

    /**
     * Rute GET PUBLIK tanpa parameter (tidak ber-`auth`).
     *
     * Rute internal membalas pengalihan ke `/login`, bukan 200, sehingga tidak
     * boleh masuk daftar gilas. Rute pengunduhan dokumen juga dilewati -- ia
     * melayani berkas dari cakram privat, bukan halaman.
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

            // Rute ber-`auth` tidak punya wujud statis: tamu diarahkan ke /login.
            if (in_array('auth', $rute->gatherMiddleware(), true)) {
                continue;
            }

            $hasil[] = '/'.ltrim($uri === '/' ? '' : $uri, '/');
        }

        return $hasil;
    }

    /**
     * Halaman rincian berparameter yang PUBLIK.
     *
     * Sejak Task 3.2b hanya tinggal tautan tetap lacak pengaduan
     * (`/lacak-pengaduan/{nomor}`). Seluruh rincian modul (transmigran, rumah,
     * lahan, panen, SP, dst.) dan tab rekap kini ber-`auth` dan tidak lagi
     * digilas -- riwayatnya ada di kontrol versi bila kelak dibutuhkan
     * pratinjau internal.
     *
     * @return list<string>
     */
    private function rincianDariDataContoh(): array
    {
        $hasil = [];

        // Tautan tetap lacak pengaduan memakai nomor, bukan id. Halaman lacak
        // publik: warga membuka laporannya tanpa akun (rules.md 10b poin 1).
        foreach (DummyData::pengaduan() as $baris) {
            if (isset($baris['nomor_pengaduan'])) {
                $hasil[] = '/lacak-pengaduan/'.$baris['nomor_pengaduan'];
            }
        }

        return $hasil;
    }
}
