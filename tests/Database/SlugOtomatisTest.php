<?php

/*
 * Task 3.9 -- Slug pengenal publik pada data master.
 *
 * `rules.md` 4.0a poin 1-3 / `data-dictionary.md` 2 catatan slug: slug
 * diturunkan dari `nama` saat dibuat, unik, dan tidak berubah meski nama
 * disunting. Trait `App\Models\Concerns\BerslugOtomatis`.
 *
 * Catatan: keempat tabel ber-slug juga UNIQUE pada `nama`, sehingga tabrakan
 * slug hanya mungkin dari nama BERBEDA yang meluruh ke slug sama, mis.
 * "Ubi Kayu" vs "Ubi-Kayu".
 */

use App\Enums\CakupanData;
use App\Models\Komoditas;
use App\Models\Poktan;
use App\Models\Role;
use App\Models\SatuanPermukiman;
use App\Models\User;

require_once __DIR__.'/DatabaseHelpers.php';

function buatKomoditas(array $atribut = []): Komoditas
{
    return Komoditas::create(array_merge([
        'satuan_id' => buatSatuanTon()->id_satuan,
        'nama' => 'Jagung Manis',
        'tipe' => 'Pangan',
    ], $atribut));
}

it('menurunkan slug dari nama saat baris dibuat', function () {
    $k = buatKomoditas(['nama' => 'Kacang Tanah']);

    expect($k->slug)->toBe('kacang-tanah');
});

it('menghormati slug yang sudah diisi pemanggil', function () {
    $k = buatKomoditas(['nama' => 'Padi Ladang', 'slug' => 'padi-khusus-2026']);

    expect($k->slug)->toBe('padi-khusus-2026');
});

it('membuat slug unik saat nama berbeda meluruh ke slug sama', function () {
    $a = buatKomoditas(['nama' => 'Ubi Kayu']);
    $b = buatKomoditas(['nama' => 'Ubi-Kayu']);
    $c = buatKomoditas(['nama' => 'Ubi  Kayu']);

    expect($a->slug)->toBe('ubi-kayu')
        ->and($b->slug)->toBe('ubi-kayu-2')
        ->and($c->slug)->toBe('ubi-kayu-3');
});

it('tidak mengubah slug meski nama disunting', function () {
    $k = buatKomoditas(['nama' => 'Sorgum']);
    $slugAwal = $k->slug;

    $k->update(['nama' => 'Sorgum Varietas Baru']);

    expect($k->fresh()->slug)->toBe($slugAwal);
});

it('mengabaikan upaya mengganti slug secara langsung', function () {
    $k = buatKomoditas(['nama' => 'Kedelai']);

    $k->update(['slug' => 'slug-paksaan']);

    expect($k->fresh()->slug)->toBe('kedelai');
});

it('memperhitungkan baris terhapus lunak saat menjaga keunikan slug', function () {
    $lama = buatKomoditas(['nama' => 'Kopi Robusta']);
    $lama->delete();

    $baru = buatKomoditas(['nama' => 'Kopi-Robusta']);

    expect($baru->slug)->toBe('kopi-robusta-2');
});

it('memangkas slug panjang agar muat kolom VARCHAR(120)', function () {
    // `poktan.nama` VARCHAR(255) -> slug mentahnya bisa melebihi 120.
    $nama = trim(str_repeat('Kelompok Tani Panjang ', 10)); // ~210 karakter
    $poktan = Poktan::create([
        'satuan_permukiman_id' => buatSp()->id_satuan_permukiman,
        'nama' => $nama,
    ]);

    expect(mb_strlen($poktan->slug))->toBeLessThanOrEqual(120);
});

it('menurunkan slug SP dari nama', function () {
    $induk = buatSp();

    $sp = SatuanPermukiman::create([
        'kawasan_id' => $induk->kawasan_id,
        'desa_id' => $induk->desa_id,
        'nama' => 'SP Alas Selatan',
    ]);

    expect($sp->slug)->toBe('sp-alas-selatan');
});

it('menjaga keunikan slug poktan lintas cakupan data pengguna', function () {
    $spA = buatSp();
    $spB = buatSp();
    Poktan::create(['satuan_permukiman_id' => $spA->id_satuan_permukiman, 'nama' => 'Tani Makmur']);

    // Pengguna Per SP hanya ditugaskan ke spB -- global scope menyembunyikan
    // poktan spA. Slug tetap wajib unik apa adanya.
    $role = Role::factory()->create(['cakupan_data' => CakupanData::PerSp->value]);
    $user = User::factory()->create(['role_id' => $role->id_role]);
    $user->satuanPermukiman()->attach($spB->id_satuan_permukiman);
    $this->actingAs($user);

    $poktanB = Poktan::create(['satuan_permukiman_id' => $spB->id_satuan_permukiman, 'nama' => 'Tani-Makmur']);

    expect($poktanB->slug)->toBe('tani-makmur-2');
});
