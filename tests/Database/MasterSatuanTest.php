<?php

/*
 * Task 4.5 -- data master satuan + faktor konversi ke ton.
 *
 * Berumah di grup Database sebab yang dijaga menyentuh UNIQUE `nama` dan FK
 * RESTRICT dari `komoditas` -- keduanya tidak ditegakkan SQLite sekeras MySQL.
 */

use App\Models\Komoditas;
use App\Models\Satuan;
use App\Models\User;
use Database\Seeders\SatuanSeeder;

require_once __DIR__.'/DatabaseHelpers.php';

beforeEach(function () {
    $petugas = User::factory()->create();
    $petugas->semuaIzin = true;
    $this->actingAs($petugas);
    $this->seed(SatuanSeeder::class);
});

it('menanam tiga satuan awal beserta faktor konversinya', function () {
    expect(Satuan::count())->toBe(3)
        ->and((float) Satuan::where('nama', 'Ton')->first()->faktor_ke_ton)->toBe(1.0)
        ->and((float) Satuan::where('nama', 'Kuintal')->first()->faktor_ke_ton)->toBe(0.1)
        ->and((float) Satuan::where('nama', 'Kilogram')->first()->faktor_ke_ton)->toBe(0.001);
});

it('idempoten: dijalankan ulang tidak menggandakan satuan', function () {
    $this->seed(SatuanSeeder::class);

    expect(Satuan::count())->toBe(3);
});

it('menyimpan satuan baru', function () {
    $this->post(route('satuan.simpan'), [
        'nama' => 'GRAM',
        'simbol' => 'g',
        'faktor_ke_ton' => '0.000001',
    ])->assertRedirect(route('master.satuan'));

    expect(Satuan::where('nama', 'GRAM')->exists())->toBeTrue();
});

it('menolak faktor konversi nol atau negatif', function (string $faktor) {
    // Faktor nol membuat volume panen LENYAP dari rekap tanpa memerahkan
    // apa pun; faktor negatif membalik tandanya. Keduanya kegagalan senyap.
    $this->post(route('satuan.simpan'), [
        'nama' => 'UJI FAKTOR',
        'simbol' => 'uf',
        'faktor_ke_ton' => $faktor,
    ])->assertSessionHasErrors('faktor_ke_ton');

    expect(Satuan::where('nama', 'UJI FAKTOR')->exists())->toBeFalse();
})->with(['0', '-1', '-0.5']);

it('menolak nama satuan yang sudah terdaftar', function () {
    $this->post(route('satuan.simpan'), [
        'nama' => 'Ton',
        'simbol' => 'tt',
        'faktor_ke_ton' => '1',
    ])->assertSessionHasErrors('nama');

    expect(Satuan::count())->toBe(3);
});

it('mengubah faktor konversi tanpa menyentuh nama satuan lain', function () {
    $ton = Satuan::where('nama', 'Ton')->first();

    $this->put(route('satuan.perbarui', $ton->id_satuan), [
        'nama' => 'Ton',
        'simbol' => 'ton',
        'faktor_ke_ton' => '1',
    ])->assertRedirect(route('master.satuan'));

    // UppercaseInput mengapitalkan isian teks (rules.md 13.2) -- simbol ikut,
    // dan itu memang perilaku yang dikehendaki.
    expect($ton->fresh()->simbol)->toBe('TON')
        ->and(Satuan::count())->toBe(3);
});

it('menghapus satuan yang belum dipakai komoditas', function () {
    $kg = Satuan::where('nama', 'Kilogram')->first();

    $this->delete(route('satuan.hapus', $kg->id_satuan))
        ->assertRedirect(route('master.satuan'));

    expect(Satuan::count())->toBe(2);
});

it('menolak menghapus satuan yang masih dipakai komoditas', function () {
    // FK RESTRICT sudah menahannya di basis data, tetapi galat SQL mentah
    // tidak terbaca petugas. Yang dijaga: alasannya sampai sebagai kalimat
    // yang dapat ditindaklanjuti, bukan 500.
    $ton = Satuan::where('nama', 'Ton')->first();

    Komoditas::create([
        'satuan_id' => $ton->id_satuan,
        'nama' => 'JAGUNG UJI',
        'tipe' => 'Pangan',
        'is_unggulan' => false,
    ]);

    $this->from(route('master.satuan'))
        ->delete(route('satuan.hapus', $ton->id_satuan))
        ->assertRedirect(route('master.satuan'))
        ->assertSessionHas('galat');

    expect(Satuan::find($ton->id_satuan))->not->toBeNull();
});

it('menghitung jumlah komoditas pemakai tiap satuan', function () {
    $ton = Satuan::where('nama', 'Ton')->first();

    Komoditas::create([
        'satuan_id' => $ton->id_satuan, 'nama' => 'PADI UJI', 'tipe' => 'Pangan', 'is_unggulan' => false,
    ]);

    $isi = $this->get(route('master.satuan'))->assertOk()->getContent();

    expect($isi)->toContain('1 komoditas');
});
