<?php

/**
 * Uji kontras warna menurut WCAG 2.1 (ANTISLOP-ID R-25, ui-spec.md 3.2).
 *
 * Dokumen gate gelombang 1 sempat mengklaim "11 pasangan warna diuji dengan
 * rumus WCAG 2.1 lewat Node". Klaim itu keliru: berkas `uji-chart-config.mjs`
 * berukuran 0 byte sejak pertama masuk repositori, dan penelusuran seluruh
 * riwayat git tidak menemukan satu baris pun kode perhitungan kontras. Jadi
 * angka yang dilaporkan tidak pernah benar-benar dihitung.
 *
 * Berkas ini menggantikannya dengan perhitungan yang sungguh dijalankan, di
 * dalam Pest agar ikut `vendor\bin\pest` yang sudah menjadi satu-satunya
 * perintah verifikasi. Menambah runner Node kedua berarti merawat dua jalur
 * uji untuk hasil yang sama.
 *
 * Nilai warna dibaca langsung dari `resources/css/app.css`, bukan disalin ke
 * dalam uji. Salinan akan menua diam-diam: warna yang disunting di CSS tetap
 * lulus di sini karena yang diperiksa adalah salinan lamanya.
 */

use Tests\Support\Warna;

/*
|--------------------------------------------------------------------------
| Rumus WCAG 2.1
|--------------------------------------------------------------------------
*/

it('menghitung luminansi relatif sesuai nilai acuan WCAG', function () {
    // Tiga titik yang nilainya sudah pasti menurut definisi WCAG 2.1: hitam 0,
    // putih 1. Tanpa penambat ini, rumus yang salah tetap menghasilkan angka
    // yang tampak masuk akal dan seluruh uji di bawahnya ikut salah.
    expect(Warna::luminansi('#000000'))->toBe(0.0)
        ->and(Warna::luminansi('#ffffff'))->toBe(1.0);

    // Ambang linearisasi sRGB ada di 0,03928. Abu-abu 0,5 berada di atasnya,
    // sehingga cabang pemangkatan 2,4 yang dipakai, bukan cabang pembagian.
    expect(Warna::luminansi('#808080'))->toBeGreaterThan(0.21)
        ->and(Warna::luminansi('#808080'))->toBeLessThan(0.22);
});

it('menghitung rasio kontras sesuai nilai acuan WCAG', function () {
    // Hitam di atas putih adalah kontras tertinggi yang mungkin, tepat 21:1.
    expect(Warna::rasio('#000000', '#ffffff'))->toBe(21.0);

    // Warna yang sama dengan dirinya sendiri selalu 1:1.
    expect(Warna::rasio('#163b54', '#163b54'))->toBe(1.0);

    // Urutan argumen tidak boleh mengubah hasil, sebab rumusnya memakai yang
    // lebih terang sebagai pembilang, bukan argumen pertama.
    expect(Warna::rasio('#163b54', '#ffffff'))
        ->toBe(Warna::rasio('#ffffff', '#163b54'));
});

/*
|--------------------------------------------------------------------------
| Pasangan warna yang benar-benar dipakai antarmuka
|--------------------------------------------------------------------------
*/

it('memenuhi kontras AA pada teks di atas permukaan terang dan gelap', function () {
    // Ambang 4,5:1 berlaku untuk teks berukuran normal (WCAG 1.4.3). Yang
    // diuji adalah pasangan yang benar-benar dipakai tata letak, bukan seluruh
    // kombinasi yang mungkin: memeriksa 92 warna saling silang akan
    // menghasilkan ribuan pasangan yang tak satu pun muncul di layar.
    $pasangan = [
        // [depan, latar, keterangan]
        ['gray-700', 'white', 'teks utama, mode terang'],
        ['gray-500', 'white', 'teks sekunder, mode terang'],
        ['navy-500', 'white', 'judul dan tautan, mode terang'],
        ['gray-400', 'gray-dark', 'teks sekunder, mode gelap'],
        ['white', 'navy-500', 'teks di atas tombol utama'],
        ['white', 'navy-600', 'teks di atas tombol utama saat ditekan'],
    ];

    $gagal = [];

    foreach ($pasangan as [$depan, $latar, $keterangan]) {
        $rasio = Warna::rasio(Warna::nilai($depan), Warna::nilai($latar));

        if ($rasio < 4.5) {
            $gagal[] = sprintf('%s di atas %s (%s) hanya %.2f:1', $depan, $latar, $keterangan, $rasio);
        }
    }

    expect($gagal)->toBe([]);
});

it('memenuhi kontras AA pada teks badge di atas latar badge', function () {
    // Badge status memakai teks gelap di atas latar muda sewarna. Pasangan ini
    // paling rawan sebab keduanya berasal dari satu skala warna, sehingga
    // selisihnya mudah terlalu tipis.
    //
    // ui-spec.md menetapkan lima warna badge; kelimanya diperiksa agar tidak
    // ada satu status pun yang tak terbaca.
    $badge = [
        ['success-700', 'success-50'],
        ['warning-700', 'warning-50'],
        ['error-700', 'error-50'],
        ['navy-700', 'navy-50'],
        ['gold-700', 'gold-50'],
    ];

    $gagal = [];

    foreach ($badge as [$teks, $latar]) {
        $rasio = Warna::rasio(Warna::nilai($teks), Warna::nilai($latar));

        if ($rasio < 4.5) {
            $gagal[] = sprintf('badge %s di atas %s hanya %.2f:1', $teks, $latar, $rasio);
        }
    }

    expect($gagal)->toBe([]);
});

it('memenuhi kontras 3:1 pada warna aksen yang dipakai sebagai pembatas', function () {
    // WCAG 1.4.11 memakai ambang lebih rendah, 3:1, untuk elemen bukan teks
    // seperti garis pembatas, ikon, dan cincin fokus. Menyamakannya dengan 4,5
    // akan memaksa pembatas menjadi gelap berlebihan.
    $pasangan = [
        ['gold-700', 'white', 'aksen gold sebagai penanda'],
        ['teal-500', 'white', 'aksen teal'],
    ];

    $gagal = [];

    foreach ($pasangan as [$depan, $latar, $keterangan]) {
        $rasio = Warna::rasio(Warna::nilai($depan), Warna::nilai($latar));

        if ($rasio < 3.0) {
            $gagal[] = sprintf('%s di atas %s (%s) hanya %.2f:1', $depan, $latar, $keterangan, $rasio);
        }
    }

    expect($gagal)->toBe([]);
});

/*
|--------------------------------------------------------------------------
| Penjaga sumber warna
|--------------------------------------------------------------------------
*/

it('membaca warna langsung dari app.css, bukan dari salinan di dalam uji', function () {
    // Bila blok @theme dipindah atau namanya berubah, uji di atas akan lulus
    // dengan daftar warna kosong dan kegagalannya tidak terlihat. Uji ini
    // memastikan sumbernya benar-benar terbaca.
    $warna = Warna::semua();

    expect(count($warna))->toBeGreaterThan(80)
        ->and($warna)->toHaveKeys(['navy-500', 'teal-500', 'gold-700', 'white']);

    // Palet resmi Kementerian (tasklist.md keputusan 7). Bila salah satu
    // bergeser, identitas visualnya ikut bergeser tanpa ada yang menyadari.
    expect($warna['navy-500'])->toBe('#163b54')
        ->and($warna['teal-500'])->toBe('#33809c');
});
