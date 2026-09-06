<?php

namespace App\Console\Commands;

use App\Models\Pengaduan;
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
     * @return list<string>
     */
    private function kumpulkan(): array
    {
        $alamat = [...$this->ruteTanpaParameter(), ...$this->tautanLacakPengaduan()];
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
     * @return list<string>
     */
    private function tautanLacakPengaduan(): array
    {
        return Pengaduan::query()
            ->whereNotNull('nomor_pengaduan')
            ->orderBy('nomor_pengaduan')
            ->pluck('nomor_pengaduan')
            ->map(fn (string $nomor) => route('lacak-pengaduan.nomor', ['nomor' => $nomor], false))
            ->all();
    }
}
