<?php

/*
 * Task 5.1 -- peralihan transmigran + anggota_keluarga ke Eloquent.
 *
 * Jalur BACA (daftar + rincian) lewat `TransmigranController`. Rute tulis masih
 * closure sampai Task 5.2, jadi belum diuji di sini.
 */

use App\Enums\StatusAnggotaKeluarga;
use App\Models\AnggotaKeluarga;
use App\Models\Transmigran;
use App\Models\User;
use App\Support\DummyData;
use Database\Seeders\KawasanSeeder;
use Database\Seeders\SpSeeder;
use Database\Seeders\TransmigranSeeder;
use Database\Seeders\WilayahSeeder;

require_once __DIR__.'/DatabaseHelpers.php';

beforeEach(function () {
    $petugas = User::factory()->create();
    $petugas->semuaIzin = true;
    $this->actingAs($petugas);
    $this->seed(WilayahSeeder::class);
    $this->seed(KawasanSeeder::class);
    $this->seed(SpSeeder::class);
    $this->seed(TransmigranSeeder::class);
});

it('menanam seluruh transmigran dan anggota keluarga dari data contoh', function () {
    expect(Transmigran::count())->toBe(count(DummyData::transmigran()))
        ->and(AnggotaKeluarga::count())->toBe(count(DummyData::anggotaKeluarga()));

    $yohanes = Transmigran::where('nik', '5321011505800001')->first();
    expect($yohanes)->not->toBeNull()
        ->and($yohanes->uuid)->not->toBeNull()
        ->and($yohanes->satuanPermukiman->nama)->toBe('SP Kapitan Meo');
});

it('mempertahankan uuid saat ditanam ulang', function () {
    $sebelum = Transmigran::orderBy('id_transmigran')->pluck('uuid', 'id_transmigran');

    $this->seed(TransmigranSeeder::class);

    expect(Transmigran::orderBy('id_transmigran')->pluck('uuid', 'id_transmigran')->all())
        ->toBe($sebelum->all());
});

it('merender daftar transmigran dari basis data', function () {
    $respons = $this->get(route('transmigran.index'))->assertOk();

    foreach (DummyData::transmigran() as $t) {
        $respons->assertSee($t['nama_kepala_keluarga'])->assertSee($t['nik']);
    }
});

it('menyaring daftar transmigran menurut satuan permukiman', function () {
    $this->get(route('transmigran.index', ['sp' => 2]))
        ->assertOk()
        ->assertSee('PETRUS NAHAK')
        ->assertDontSee('YOHANES BERE');
});

it('mencari transmigran memakai nomor KK', function () {
    $this->get(route('transmigran.index', ['cari' => '5321010102150001']))
        ->assertOk()
        ->assertSee('YOHANES BERE')
        ->assertDontSee('MARIA DA COSTA');
});

it('merender rincian transmigran beserta anggota keluarganya', function () {
    $petrus = Transmigran::where('nama_kepala_keluarga', 'PETRUS NAHAK')->first();

    $this->get(route('transmigran.detail', $petrus->id_transmigran))
        ->assertOk()
        ->assertSee('PETRUS NAHAK')
        ->assertSee($petrus->no_kk)
        ->assertSee('YOVITA NAHAK SERAN')
        ->assertSee('ROSALIA SERAN');
});

it('membalas 404 untuk transmigran yang tidak ada', function () {
    $this->get('/transmigran/99999')->assertNotFound();
});

it('menurunkan jumlah anggota keluarga dari cacah baris aktif', function () {
    foreach (DummyData::transmigran() as $contoh) {
        $kk = Transmigran::with('anggotaKeluarga')->find($contoh['id_transmigran']);

        $aktif = $kk->anggotaKeluarga
            ->filter(fn ($a) => $a->status === StatusAnggotaKeluarga::Aktif)
            ->count();

        expect(1 + $aktif)->toBe($contoh['jumlah_anggota_keluarga']);
    }
});
