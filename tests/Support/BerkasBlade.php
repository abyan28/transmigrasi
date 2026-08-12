<?php

namespace Tests\Support;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Pembantu penelusuran berkas Blade untuk uji berbasis berkas.
 *
 * Dibuat setelah ditemukan bahwa `glob('views/pages/**' . '/*.blade.php')`
 * TIDAK rekursif di PHP: pola `**` hanya cocok satu tingkat direktori.
 * Akibatnya berkas di akar `pages/` seperti `galeri-komponen.blade.php`
 * tidak pernah ikut diperiksa, dan cakupan tiap uji jadi berbeda-beda.
 *
 * Seluruh uji berbasis berkas wajib memakai kelas ini agar cakupannya
 * seragam dan tidak ada berkas yang luput diam-diam.
 */
class BerkasBlade
{
    /**
     * Seluruh berkas Blade di dalam resources/views, ditelusuri rekursif.
     *
     * @return array<int, string> Daftar path lengkap, terurut
     */
    public static function semua(): array
    {
        $akar = resource_path('views');
        $hasil = [];

        $penelusur = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($akar));

        foreach ($penelusur as $berkas) {
            if ($berkas->isFile() && str_ends_with($berkas->getFilename(), '.blade.php')) {
                $hasil[] = str_replace('\\', '/', $berkas->getPathname());
            }
        }

        sort($hasil);

        return $hasil;
    }

    /**
     * Nama berkas beserta folder induknya, untuk pesan galat yang mudah dilacak.
     *
     * @param  string  $path  Path lengkap berkas
     * @return string Contoh: `layouts/app-header.blade.php`
     */
    public static function namaPendek(string $path): string
    {
        $akar = str_replace('\\', '/', resource_path('views')) . '/';

        return str_replace($akar, '', str_replace('\\', '/', $path));
    }

    /**
     * Membuang bagian yang bukan markup antarmuka sebelum diperiksa.
     *
     * Tanpa pembersihan ini, uji menghasilkan positif palsu:
     * - koordinat pada `<svg>` terbaca sebagai angka lebar kelas,
     * - komentar yang menjelaskan sebuah aturan terbaca sebagai pelanggaran
     *   aturan itu sendiri.
     *
     * @param  string  $isi  Isi berkas Blade
     * @return string Isi tanpa SVG, komentar, script, dan style
     */
    public static function bersihkan(string $isi): string
    {
        $isi = preg_replace('/<svg[\s\S]*?<\/svg>/i', '', $isi);
        $isi = preg_replace('/<script[\s\S]*?<\/script>/i', '', $isi);
        $isi = preg_replace('/<style[\s\S]*?<\/style>/i', '', $isi);
        $isi = preg_replace('/\{\{--[\s\S]*?--\}\}/', '', $isi);
        $isi = preg_replace('/<!--[\s\S]*?-->/', '', $isi);

        return $isi;
    }

    /**
     * Memeriksa keseimbangan tag berstruktur pada sebuah berkas Blade.
     *
     * Memakai tumpukan, bukan sekadar menghitung jumlah buka dan tutup,
     * agar urutan yang salah ikut tertangkap. Menghitung saja tidak cukup:
     * `</div><div>` berjumlah seimbang tetapi strukturnya rusak.
     *
     * @param  string  $isi  Isi berkas Blade
     * @return array<int, string> Daftar galat; kosong berarti seimbang
     */
    public static function periksaTag(string $isi): array
    {
        $isi = self::bersihkan($isi);

        // Hanya tag berstruktur. Tag void seperti <input> dan <img> tidak
        // pernah punya penutup sehingga tidak relevan di sini.
        $tag = 'div|header|footer|form|section|nav|main|aside|article|figure|'
            . 'table|thead|tbody|tfoot|tr|td|th|ul|ol|li|dl|dt|dd|'
            . 'button|label|select|textarea|fieldset';

        preg_match_all("/<(\/?)($tag)\b[^>]*?(\/?)>/is", $isi, $cocok, PREG_SET_ORDER);

        $tumpukan = [];
        $galat = [];

        foreach ($cocok as $t) {
            // Tag yang menutup dirinya sendiri, contoh <div />
            if ($t[3] === '/') {
                continue;
            }

            $nama = strtolower($t[2]);

            if ($t[1] === '/') {
                if ($tumpukan === []) {
                    $galat[] = "penutup yatim </{$nama}>";

                    continue;
                }

                $terakhir = array_pop($tumpukan);

                if ($terakhir !== $nama) {
                    $galat[] = "urutan salah: </{$nama}> menutup <{$terakhir}>";
                }
            } else {
                $tumpukan[] = $nama;
            }
        }

        foreach ($tumpukan as $sisa) {
            $galat[] = "pembuka tanpa penutup <{$sisa}>";
        }

        return $galat;
    }
}
