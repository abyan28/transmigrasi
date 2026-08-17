<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Membaca kolom wajib setiap tabel langsung dari kamus data.
 *
 * Kolom bertanda `Null = TIDAK` pada agents/data-dictionary.md berarti wajib
 * terisi di database. Dibaca dari dokumennya, bukan disalin ke dalam uji,
 * supaya keduanya tidak dapat berbeda diam-diam: menambah kolom wajib di
 * kamus data otomatis menuntut formnya ikut menandai.
 *
 * Kunci utama (berawalan `id_`) dilewati, sebab dibuat sistem dan tidak
 * pernah diminta lewat formulir.
 *
 * @return array<string, array<int, string>> Nama tabel berisi daftar kolom wajib
 */
function kolomWajibDariKamusData(): array
{
    $baris = preg_split('/\r\n|\r|\n/', file_get_contents(base_path('agents/data-dictionary.md')));

    $tabel = null;
    $hasil = [];

    foreach ($baris as $b) {
        $teks = trim($b);

        // Judul definisi tabel, contoh: ### 8.4 `saprotan`
        if (preg_match('/^### [\d.]+[a-z]? `(\w+)`/', $teks, $m) === 1) {
            $tabel = $m[1];

            continue;
        }

        if ($tabel === null) {
            continue;
        }

        // Baris kolom: | `nama` | `TIPE` | TIDAK | ... |
        if (preg_match('/^\|\s*`(\w+)`\s*\|[^|]*\|\s*(TIDAK|YA)\s*\|/', $teks, $m) !== 1) {
            continue;
        }

        if ($m[2] === 'TIDAK' && ! str_starts_with($m[1], 'id_')) {
            $hasil[$tabel][] = $m[1];
        }
    }

    return $hasil;
}
