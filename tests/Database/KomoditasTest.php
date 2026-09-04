<?php

/*
 * Task 7.1 (komoditas -> Eloquent).
 *
 * Yang dijaga: seed dari data contoh; slug otomatis; tipe disimpan TEKS
 * referensi; nama unik; satuan panen baku wajib; is_unggulan checkbox.
 */

use App\Models\Komoditas;
use App\Models\Penanaman;
use App\Models\Satuan;
use App\Models\User;
use App\Support\DummyData;
use Database\Seeders\HasilPanenSeeder;
use Database\Seeders\KawasanSeeder;
use Database\Seeders\KomoditasSeeder;
use Database\Seeders\LahanSeeder;
use Database\Seeders\PenanamanSeeder;
use Database\Seeders\PoktanSeeder;
use Database\Seeders\ReferensiSeeder;
use Database\Seeders\SaprotanSeeder;
use Database\Seeders\SatuanSeeder;
use Database\Seeders\SpSeeder;
use Database\Seeders\TransmigranSeeder;
use Database\Seeders\WilayahSeeder;

require_once __DIR__.'/DatabaseHelpers.php';

beforeEach(function () {
    $petugas = User::factory()->create();
    $petugas->semuaIzin = true;
    $this->actingAs($petugas);
    $this->seed(SatuanSeeder::class);
    $this->seed(ReferensiSeeder::class);
    $this->seed(KomoditasSeeder::class);
});

it('menanam komoditas dari data contoh dengan slug otomatis', function () {
    expect(Komoditas::count())->toBe(count(DummyData::komoditas()));

    $jagung = Komoditas::where('nama', 'JAGUNG')->first();
    expect($jagung->slug)->toBe('jagung')
        ->and($jagung->tipe)->toBe('Pangan')
        ->and($jagung->is_unggulan)->toBeTrue()
        ->and($jagung->satuan->nama)->toBe('Ton');
});

it('merender daftar komoditas dengan satuan dari relasi', function () {
    $this->get(route('komoditas.index'))
        ->assertOk()
        ->assertSee('JAGUNG')
        ->assertSee('Ton');
});

it('membalas 404 untuk komoditas yang tidak ada', function () {
    $this->get('/komoditas/99999')->assertNotFound();
});

it('menyimpan komoditas baru', function () {
    $satuan = Satuan::value('id_satuan');

    $this->post(route('komoditas.simpan'), [
        'nama' => 'KEDELAI',
        'tipe' => 'Palawija',
        'satuan_id' => $satuan,
        'is_unggulan' => '1',
        'deskripsi' => 'Uji.',
    ])->assertRedirect(route('komoditas.index'));

    $kedelai = Komoditas::where('nama', 'KEDELAI')->first();
    expect($kedelai)->not->toBeNull()
        ->and($kedelai->slug)->toBe('kedelai')
        ->and($kedelai->is_unggulan)->toBeTrue();
});

it('menolak nama komoditas yang sudah terdaftar', function () {
    $satuan = Satuan::value('id_satuan');

    $this->post(route('komoditas.simpan'), [
        'nama' => 'JAGUNG',
        'tipe' => 'Pangan',
        'satuan_id' => $satuan,
    ])->assertSessionHasErrors('nama');
});

it('mewajibkan tipe dan satuan panen baku', function () {
    $this->post(route('komoditas.simpan'), [
        'nama' => 'SORGUM',
    ])->assertSessionHasErrors(['tipe', 'satuan_id']);
});

it('memperbarui satuan baku komoditas', function () {
    $jagung = Komoditas::where('nama', 'JAGUNG')->first();
    $lain = Satuan::where('id_satuan', '!=', $jagung->satuan_id)->value('id_satuan');

    $this->put(route('komoditas.perbarui', $jagung->id_komoditas), [
        'nama' => $jagung->nama,
        'tipe' => $jagung->tipe,
        'satuan_id' => $lain,
    ])->assertRedirect(route('komoditas.detail', $jagung->id_komoditas));

    expect($jagung->refresh()->satuan_id)->toBe($lain)
        ->and($jagung->is_unggulan)->toBeFalse();
});

it('menghapus komoditas yang belum dipakai secara halus', function () {
    $ubi = Komoditas::where('nama', 'UBI KAYU')->value('id_komoditas');

    $this->delete(route('komoditas.hapus', $ubi))->assertRedirect(route('komoditas.index'));

    expect(Komoditas::find($ubi))->toBeNull()
        ->and(Komoditas::withTrashed()->find($ubi)->trashed())->toBeTrue();
});

it('menolak menghapus komoditas yang masih dipakai penanaman', function () {
    // Rantai penanaman butuh poktan/lahan/saprotan.
    $this->seed(WilayahSeeder::class);
    $this->seed(KawasanSeeder::class);
    $this->seed(SpSeeder::class);
    $this->seed(TransmigranSeeder::class);
    $this->seed(LahanSeeder::class);
    $this->seed(PoktanSeeder::class);
    $this->seed(SaprotanSeeder::class);
    $this->seed(PenanamanSeeder::class);
    $this->seed(HasilPanenSeeder::class);

    $jagung = Komoditas::where('nama', 'JAGUNG')->value('id_komoditas');
    expect(Penanaman::where('komoditas_id', $jagung)->exists())->toBeTrue();

    $this->from(route('komoditas.detail', $jagung))
        ->delete(route('komoditas.hapus', $jagung))
        ->assertRedirect(route('komoditas.detail', $jagung))
        ->assertSessionHas('galat');

    expect(Komoditas::find($jagung))->not->toBeNull();
});
