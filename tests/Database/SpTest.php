<?php

/*
 * Task 4.2 -- CRUD satuan permukiman, INDUK inventaris/fasilitas/infrastruktur.
 */

use App\Models\SatuanPermukiman;
use App\Models\User;
use Database\Seeders\KawasanSeeder;
use Database\Seeders\SpSeeder;
use Database\Seeders\WilayahSeeder;

require_once __DIR__.'/DatabaseHelpers.php';

beforeEach(function () {
    $petugas = User::factory()->create();
    $petugas->semuaIzin = true;
    $this->actingAs($petugas);
    $this->seed(WilayahSeeder::class);
    $this->seed(KawasanSeeder::class);
    $this->seed(SpSeeder::class);
});

it('menanam enam SP lokus beserta desa dan slugnya', function () {
    expect(SatuanPermukiman::count())->toBe(6);

    $pertama = SatuanPermukiman::where('kode_sp', 'SP-01')->first();

    expect($pertama->nama)->toBe('SP Kapitan Meo')
        ->and($pertama->slug)->toBe('sp-kapitan-meo')
        ->and($pertama->desa?->nama)->toBe('Kapitan Meo')
        ->and($pertama->desa?->kecamatan?->nama)->toBe('Laen Manen');
});

it('menyimpan SP baru', function () {
    $this->post(route('sp.simpan'), [
        'nama' => 'SP UJI BARU',
        'kode_sp' => 'SP-99',
        'kawasan_id' => 1,
        'desa_id' => 1,
        'tahun_penempatan' => 2020,
        'luas_lahan' => '500.25',
        'jumlah_kk_rencana' => 150,
    ])->assertRedirect(route('sp.index'));

    $baru = SatuanPermukiman::where('kode_sp', 'SP-99')->first();

    expect($baru)->not->toBeNull()
        ->and($baru->slug)->toBe('sp-uji-baru');
});

it('menolak kode SP yang sudah dipakai SP lain', function () {
    $this->post(route('sp.simpan'), [
        'nama' => 'SP KEMBAR',
        'kode_sp' => 'SP-01',
        'kawasan_id' => 1,
        'desa_id' => 1,
    ])->assertSessionHasErrors('kode_sp');
});

it('menolak rentang keadaan wilayah yang terbalik', function (string $min, string $maks) {
    // Rentang terbalik (mis. curah hujan 3000-500) lolos diam-diam lalu
    // terbaca sebagai rentang kosong pada Laporan Monografi SP.
    $this->post(route('sp.simpan'), [
        'nama' => 'SP RENTANG TERBALIK',
        'kawasan_id' => 1,
        'desa_id' => 1,
        'curah_hujan_bulan_min_mm' => $min,
        'curah_hujan_bulan_maks_mm' => $maks,
    ])->assertSessionHasErrors('curah_hujan_bulan_maks_mm');

    expect(SatuanPermukiman::where('nama', 'SP RENTANG TERBALIK')->exists())->toBeFalse();
})->with([['3000', '500'], ['100', '99']]);

it('menerima rentang yang sah termasuk nilai min sama dengan maks', function () {
    $this->post(route('sp.simpan'), [
        'nama' => 'SP RENTANG SAH',
        'kawasan_id' => 1,
        'desa_id' => 1,
        'curah_hujan_bulan_min_mm' => '100',
        'curah_hujan_bulan_maks_mm' => '100',
        'suhu_min_c' => '22',
        'suhu_maks_c' => '34',
        'ph_tanah_min' => '5.5',
        'ph_tanah_maks' => '7',
    ])->assertSessionHasNoErrors();

    expect(SatuanPermukiman::where('nama', 'SP RENTANG SAH')->exists())->toBeTrue();
});

it('menolak menghapus SP yang masih menaungi data turunan', function () {
    $sp = SatuanPermukiman::find(1);

    buatTransmigran($sp);

    $this->from(route('sp.index'))
        ->delete(route('sp.hapus', $sp->id_satuan_permukiman))
        ->assertRedirect(route('sp.index'))
        ->assertSessionHas('galat');

    expect(SatuanPermukiman::find(1))->not->toBeNull();
});

it('menghapus SP yang belum menaungi apa pun', function () {
    $sp = SatuanPermukiman::where('kode_sp', 'SP-06')->first();

    $this->delete(route('sp.hapus', $sp->id_satuan_permukiman))
        ->assertRedirect(route('sp.index'));

    expect(SatuanPermukiman::count())->toBe(5);
});
