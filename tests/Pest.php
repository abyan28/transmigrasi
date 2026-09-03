<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;
use Tests\TestCase;

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

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        // Task 4.1: sejak tampilan beralih dari `DummyData` ke Eloquent,
        // suite Feature MEMBUTUHKAN skema. `RefreshDatabase` dinyalakan --
        // tetap SQLite `:memory:` (`phpunit.xml`) sehingga cepat, hanya kini
        // bertabel. Sebelumnya mati sepanjang Tahap 2-3 sebab tak satu pun
        // halaman menyentuh basis data.
        //
        // `WilayahSeeder` ikut ditanam sebab wilayah adalah data master yang
        // DIANDAIKAN ADA oleh banyak halaman: dropdown provinsi/kabupaten pada
        // form SP, form transmigran, dan penyaring laporan. Tanpa itu puluhan
        // uji tampilan memerah bukan karena perilakunya salah, melainkan karena
        // datanya kosong -- dan penjaga yang memerah tanpa sebab akan dimatikan
        // orang berikutnya.
        //
        // Ditanam lewat `RefreshDatabase::\` (sekali per kelas uji,
        // hasilnya dipakai ulang lewat transaksi), BUKAN `\->seed()` di
        // sini. Menanamnya per-uji berarti menulis 552 baris provinsi+kabupaten
        // sebanyak 732 kali: terukur menaikkan suite Feature dari ~60 detik
        // menjadi 516 detik. Beda keduanya hanya satu baris, dan seluruhnya
        // ada pada penempatan.

        // Task 3.2b/3.3: seluruh rute internal ber-`auth` + `izin`. Autentikasi
        // pengguna semu -- TIDAK dipersist, tanpa DB -- bertanda `semuaIzin`
        // supaya ~340 panggilan HTTP suite Feature tetap 200. Tak mengubah satu
        // byte pun HTML: tak ada `@auth`/`Auth::` di resources/views/, header/
        // profil dari `DummyData::penggunaSaatIni()`. Uji perilaku-tamu memakai
        // `auth()->logout()` / `withoutMiddleware(RedirectIfAuthenticated::class)`.
        $semu = new User(['nama' => 'DEV', 'password_harus_diganti' => false]);
        $semu->semuaIzin = true;

        $this->actingAs($semu);
    })
    ->in('Feature');

// Grup uji migration & model Eloquent (Task 3.1): MySQL/MariaDB nyata +
// RefreshDatabase. Dipisah ke tests/Database/ (bukan tests/Feature/) sebab
// Pest tidak mengizinkan dua base class bertumpuk pada satu pohon direktori.
pest()->extend(DatabaseTestCase::class)->in('Database');

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
