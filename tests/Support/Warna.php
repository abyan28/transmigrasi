<?php

namespace Tests\Support;

use RuntimeException;

/**
 * Pembaca palet warna dan penghitung kontras WCAG 2.1.
 *
 * Warna dibaca dari `resources/css/app.css` supaya uji dan tampilan selalu
 * memeriksa nilai yang sama. Menyalin nilai warna ke dalam berkas uji membuat
 * salinannya menua diam-diam: warna yang disunting di CSS tetap lulus karena
 * yang diperiksa adalah angka lama.
 *
 * Rumus mengikuti WCAG 2.1 bagian "relative luminance" dan "contrast ratio".
 */
class Warna
{
    /** @var array<string, string>|null Tembolok agar app.css tidak dibaca berulang */
    private static ?array $tembolok = null;

    /**
     * Seluruh warna heksadesimal pada app.css, berkunci nama tanpa awalan.
     *
     * Contoh kunci: `navy-500`, `gold-700`, `white`, `gray-dark`.
     *
     * @return array<string, string>
     */
    public static function semua(): array
    {
        if (self::$tembolok !== null) {
            return self::$tembolok;
        }

        $isi = file_get_contents(resource_path('css/app.css'));

        // Hanya bentuk heksadesimal yang diambil. Nilai seperti `oklch(...)`
        // dan `var(...)` sengaja dilewati, sebab keduanya perlu penguraian
        // tersendiri dan tidak dipakai pada palet identitas.
        preg_match_all('/--color-([a-z0-9-]+):\s*(#[0-9a-fA-F]{6})\b/', $isi, $cocok, PREG_SET_ORDER);

        $hasil = [];

        foreach ($cocok as $baris) {
            // Definisi terakhir yang menang, sama seperti perilaku CSS. Ini
            // penting karena `--color-brand-*` sengaja ditimpa navy.
            $hasil[$baris[1]] = strtolower($baris[2]);
        }

        return self::$tembolok = $hasil;
    }

    /**
     * Nilai heksadesimal satu warna.
     *
     * @param  string  $nama  Nama tanpa awalan `--color-`, misalnya `navy-500`
     *
     * @throws RuntimeException Bila warna tidak ada, agar salah ketik pada
     *                          nama warna tidak lolos sebagai uji yang hijau
     */
    public static function nilai(string $nama): string
    {
        $warna = self::semua();

        if (! isset($warna[$nama])) {
            throw new RuntimeException("Warna '{$nama}' tidak ada di resources/css/app.css.");
        }

        return $warna[$nama];
    }

    /**
     * Luminansi relatif menurut WCAG 2.1, bernilai 0 (hitam) sampai 1 (putih).
     */
    public static function luminansi(string $heks): float
    {
        $heks = ltrim($heks, '#');

        $kanal = [
            hexdec(substr($heks, 0, 2)) / 255,
            hexdec(substr($heks, 2, 2)) / 255,
            hexdec(substr($heks, 4, 2)) / 255,
        ];

        // Linearisasi sRGB. Kanal gelap memakai pembagian lurus, selebihnya
        // pemangkatan 2,4; batasnya 0,03928 sesuai spesifikasi.
        $linear = array_map(
            fn (float $n): float => $n <= 0.03928 ? $n / 12.92 : (($n + 0.055) / 1.055) ** 2.4,
            $kanal
        );

        // Bobot mengikuti kepekaan mata: hijau paling berpengaruh, biru paling
        // sedikit. Karena itu teks biru di atas putih terasa kurang terbaca
        // dibanding hijau pada tingkat kegelapan yang sama.
        return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
    }

    /**
     * Rasio kontras dua warna, 1,0 (sama persis) sampai 21,0 (hitam lawan putih).
     *
     * Urutan argumen tidak memengaruhi hasil.
     */
    public static function rasio(string $satu, string $dua): float
    {
        $a = self::luminansi($satu);
        $b = self::luminansi($dua);

        $terang = max($a, $b);
        $gelap = min($a, $b);

        // Penambah 0,05 mewakili pantulan layar; tanpa itu warna yang sangat
        // gelap menghasilkan rasio tak hingga.
        return round(($terang + 0.05) / ($gelap + 0.05), 2);
    }
}
