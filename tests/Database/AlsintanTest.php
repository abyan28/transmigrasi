<?php

/*
 * Task 6.6 (alsintan): pola INDUK + DISTRIBUSI.
 *
 * Yang dijaga: satu pengadaan tersebar ke banyak poktan lintas SP; kondisi
 * per baris distribusi; SUM(distribusi.jumlah) <= jumlah_total ditolak bila
 * dilanggar; poktan yang dilepas kehilangan barisnya; kondisi diperbarui
 * per baris lewat rute distribusi.
 */

use App\Models\Alsintan;
use App\Models\AlsintanDistribusi;
use App\Models\Poktan;
use App\Models\User;
use App\Support\DummyData;
use Database\Seeders\AlsintanSeeder;
use Database\Seeders\KawasanSeeder;
use Database\Seeders\LahanSeeder;
use Database\Seeders\PoktanSeeder;
use Database\Seeders\ReferensiSeeder;
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
    $this->seed(ReferensiSeeder::class);
    $this->seed(TransmigranSeeder::class);
    $this->seed(LahanSeeder::class);
    $this->seed(PoktanSeeder::class);
    $this->seed(AlsintanSeeder::class);
});

it('menanam alsintan dan distribusinya dari data contoh', function () {
    expect(Alsintan::count())->toBe(count(DummyData::alsintan()))
        ->and(AlsintanDistribusi::count())->toBe(count(DummyData::alsintanDistribusi()));

    $traktor = Alsintan::where('nama_alat', 'TRAKTOR RODA DUA KUBOTA')->first();
    expect($traktor->distribusi)->toHaveCount(3)
        ->and($traktor->distribusi->sum('jumlah'))->toBe(4);
});

it('merender daftar alsintan dengan poktan penerima', function () {
    $this->get(route('alsintan.index'))
        ->assertOk()
        ->assertSee('TRAKTOR RODA DUA KUBOTA')
        ->assertSee('POKTAN MEKAR JAYA');
});

it('menampilkan pengadaan yang belum tersalurkan', function () {
    $perontok = Alsintan::where('nama_alat', 'MESIN PERONTOK JAGUNG')->first();

    $this->get(route('alsintan.detail', $perontok->id_alsintan))
        ->assertOk()
        ->assertSee('Belum tersalurkan');

    expect($perontok->distribusi)->toHaveCount(0);
});

it('membalas 404 untuk alsintan yang tidak ada', function () {
    $this->get('/alsintan/99999')->assertNotFound();
});

it('menyimpan pengadaan baru beserta distribusi ke dua poktan', function () {
    $poktan = Poktan::orderBy('id_poktan')->take(2)->pluck('id_poktan');

    $this->post(route('alsintan.simpan'), [
        'jenis_alsintan' => 'Pompa Air',
        'nama_alat' => 'POMPA AIR BARU',
        'jumlah_total' => 5,
        'tahun_pengadaan' => 2022,
        'sumber_dana' => 'APBN',
        'poktan_id' => $poktan->all(),
        'distribusi' => [
            $poktan[0] => ['jumlah' => 3, 'kondisi' => 'Baik'],
            $poktan[1] => ['jumlah' => 2, 'kondisi' => 'Baik'],
        ],
    ])->assertRedirect(route('alsintan.index'));

    $alsintan = Alsintan::where('nama_alat', 'POMPA AIR BARU')->first();
    expect($alsintan)->not->toBeNull()
        ->and($alsintan->distribusi)->toHaveCount(2)
        ->and($alsintan->distribusi->sum('jumlah'))->toBe(5);
});

it('menolak distribusi yang melebihi jumlah unit total', function () {
    $poktan = Poktan::value('id_poktan');

    $this->post(route('alsintan.simpan'), [
        'jenis_alsintan' => 'Pompa Air',
        'nama_alat' => 'POMPA AIR KELEBIHAN',
        'jumlah_total' => 2,
        'poktan_id' => [$poktan],
        'distribusi' => [
            $poktan => ['jumlah' => 5, 'kondisi' => 'Baik'],
        ],
    ])->assertSessionHasErrors('distribusi');

    expect(Alsintan::where('nama_alat', 'POMPA AIR KELEBIHAN')->exists())->toBeFalse();
});

it('melepas baris distribusi untuk poktan yang tidak lagi menerima', function () {
    $traktor = Alsintan::where('nama_alat', 'TRAKTOR RODA DUA KUBOTA')->first();
    $tetap = $traktor->distribusi->first()->poktan_id;

    $this->put(route('alsintan.perbarui', $traktor->id_alsintan), [
        'jenis_alsintan' => $traktor->jenis_alsintan,
        'nama_alat' => $traktor->nama_alat,
        'jumlah_total' => $traktor->jumlah_total,
        'poktan_id' => [$tetap],
        'distribusi' => [
            $tetap => ['jumlah' => 2, 'kondisi' => 'Baik'],
        ],
    ])->assertRedirect(route('alsintan.detail', $traktor->id_alsintan));

    $traktor->refresh()->load('distribusi');
    expect($traktor->distribusi)->toHaveCount(1)
        ->and($traktor->distribusi->first()->poktan_id)->toBe($tetap);
});

it('memperbarui kondisi satu baris distribusi', function () {
    $baris = AlsintanDistribusi::where('kondisi', 'Baik')->first();

    $this->post("/alsintan/{$baris->alsintan_id}/distribusi/{$baris->id_alsintan_distribusi}/kondisi", [
        'kondisi' => 'Rusak Berat',
    ])->assertRedirect(route('alsintan.detail', $baris->alsintan_id));

    expect($baris->refresh()->kondisi)->toBe('Rusak Berat');
});

it('menghapus pengadaan alsintan secara halus', function () {
    $id = Alsintan::where('nama_alat', 'CANGKUL')->value('id_alsintan');

    $this->delete(route('alsintan.hapus', $id))->assertRedirect(route('alsintan.index'));

    expect(Alsintan::find($id))->toBeNull()
        ->and(Alsintan::withTrashed()->find($id)->trashed())->toBeTrue();
});
