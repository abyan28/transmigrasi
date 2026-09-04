<?php

/*
 * Task 6.7 (saprotan): pola INDUK + DISTRIBUSI.
 *
 * Yang dijaga: satu pengadaan tersebar ke banyak poktan; komoditas & varietas
 * wajib hanya untuk jenis Benih; Sigma distribusi <= jumlah_total; poktan yang
 * dilepas kehilangan barisnya; satuan non-berat (Liter/Rol) dapat dipakai.
 */

use App\Models\Komoditas;
use App\Models\Poktan;
use App\Models\Saprotan;
use App\Models\SaprotanDistribusi;
use App\Models\Satuan;
use App\Models\User;
use App\Support\DummyData;
use Database\Seeders\KawasanSeeder;
use Database\Seeders\KomoditasSeeder;
use Database\Seeders\LahanSeeder;
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
    $this->seed(WilayahSeeder::class);
    $this->seed(KawasanSeeder::class);
    $this->seed(SpSeeder::class);
    $this->seed(SatuanSeeder::class);
    $this->seed(ReferensiSeeder::class);
    $this->seed(TransmigranSeeder::class);
    $this->seed(LahanSeeder::class);
    $this->seed(PoktanSeeder::class);
    $this->seed(KomoditasSeeder::class);
    $this->seed(SaprotanSeeder::class);
});

it('menanam saprotan dan distribusinya dari data contoh', function () {
    expect(Saprotan::count())->toBe(count(DummyData::saprotan()))
        ->and(SaprotanDistribusi::count())->toBe(count(DummyData::saprotanDistribusi()));

    $benih = Saprotan::where('nama', 'BENIH JAGUNG HIBRIDA')->first();
    expect($benih->jenis->value)->toBe('Benih')
        ->and($benih->komoditas->nama)->toBe('JAGUNG')
        ->and($benih->distribusi)->toHaveCount(2);
});

it('menanam saprotan bersatuan non-berat', function () {
    $mulsa = Saprotan::where('nama', 'MULSA PLASTIK HITAM PERAK')->first();

    expect($mulsa->satuan->nama)->toBe('Rol')
        ->and($mulsa->satuan->faktor_ke_ton)->toBeNull();
});

it('merender daftar saprotan dengan poktan penerima', function () {
    $this->get(route('saprotan.index'))
        ->assertOk()
        ->assertSee('BENIH JAGUNG HIBRIDA')
        ->assertSee('POKTAN MEKAR JAYA');
});

it('membalas 404 untuk saprotan yang tidak ada', function () {
    $this->get('/saprotan/99999')->assertNotFound();
});

it('menyimpan pengadaan benih dengan komoditas dan varietas', function () {
    $poktan = Poktan::orderBy('id_poktan')->take(2)->pluck('id_poktan');
    $satuan = Satuan::where('nama', 'Kilogram')->value('id_satuan');
    $komoditas = Komoditas::where('nama', 'JAGUNG')->value('id_komoditas');

    $this->post(route('saprotan.simpan'), [
        'jenis' => 'Benih',
        'nama' => 'BENIH JAGUNG UJI',
        'komoditas_id' => $komoditas,
        'varietas' => 'Uji-1',
        'jumlah_total' => '100',
        'satuan_id' => $satuan,
        'tahun_pengadaan' => 2025,
        'poktan_id' => $poktan->all(),
        'distribusi' => [
            $poktan[0] => ['jumlah' => '60'],
            $poktan[1] => ['jumlah' => '40'],
        ],
    ])->assertRedirect(route('saprotan.index'));

    $saprotan = Saprotan::where('nama', 'BENIH JAGUNG UJI')->first();
    expect($saprotan->komoditas_id)->toBe($komoditas)
        ->and($saprotan->varietas)->toBe('UJI-1')
        ->and($saprotan->distribusi->sum('jumlah') + 0)->toBe(100.0);
});

it('mewajibkan komoditas dan varietas untuk jenis Benih', function () {
    $satuan = Satuan::where('nama', 'Kilogram')->value('id_satuan');

    $this->post(route('saprotan.simpan'), [
        'jenis' => 'Benih',
        'nama' => 'BENIH TANPA KOMODITAS',
        'jumlah_total' => '50',
        'satuan_id' => $satuan,
        'tahun_pengadaan' => 2025,
    ])->assertSessionHasErrors(['komoditas_id', 'varietas']);
});

it('tidak menyimpan komoditas untuk jenis non-Benih', function () {
    $satuan = Satuan::where('nama', 'Liter')->value('id_satuan');
    $komoditas = Komoditas::where('nama', 'JAGUNG')->value('id_komoditas');

    $this->post(route('saprotan.simpan'), [
        'jenis' => 'Pestisida',
        'nama' => 'HERBISIDA UJI',
        'komoditas_id' => $komoditas,
        'varietas' => 'diabaikan',
        'jumlah_total' => '20',
        'satuan_id' => $satuan,
        'tahun_pengadaan' => 2026,
    ])->assertRedirect(route('saprotan.index'));

    $saprotan = Saprotan::where('nama', 'HERBISIDA UJI')->first();
    expect($saprotan->komoditas_id)->toBeNull()
        ->and($saprotan->varietas)->toBeNull();
});

it('menolak distribusi yang melebihi jumlah total', function () {
    $poktan = Poktan::value('id_poktan');
    $satuan = Satuan::where('nama', 'Kilogram')->value('id_satuan');

    $this->post(route('saprotan.simpan'), [
        'jenis' => 'Pupuk',
        'nama' => 'PUPUK KELEBIHAN',
        'jumlah_total' => '100',
        'satuan_id' => $satuan,
        'tahun_pengadaan' => 2025,
        'poktan_id' => [$poktan],
        'distribusi' => [$poktan => ['jumlah' => '250']],
    ])->assertSessionHasErrors('distribusi');

    expect(Saprotan::where('nama', 'PUPUK KELEBIHAN')->exists())->toBeFalse();
});

it('melepas baris distribusi untuk poktan yang tidak lagi menerima', function () {
    $benih = Saprotan::where('nama', 'BENIH JAGUNG HIBRIDA')->first();
    $tetap = $benih->distribusi->first()->poktan_id;

    $this->put(route('saprotan.perbarui', $benih->id_saprotan), [
        'jenis' => $benih->jenis->value,
        'nama' => $benih->nama,
        'komoditas_id' => $benih->komoditas_id,
        'varietas' => $benih->varietas,
        'jumlah_total' => (string) $benih->jumlah_total,
        'satuan_id' => $benih->satuan_id,
        'tahun_pengadaan' => $benih->tahun_pengadaan,
        'poktan_id' => [$tetap],
        'distribusi' => [$tetap => ['jumlah' => '150']],
    ])->assertRedirect(route('saprotan.detail', $benih->id_saprotan));

    $benih->refresh()->load('distribusi');
    expect($benih->distribusi)->toHaveCount(1)
        ->and($benih->distribusi->first()->poktan_id)->toBe($tetap);
});

it('menghapus pengadaan saprotan secara halus', function () {
    $id = Saprotan::where('nama', 'INSEKTISIDA CAIR')->value('id_saprotan');

    $this->delete(route('saprotan.hapus', $id))->assertRedirect(route('saprotan.index'));

    expect(Saprotan::find($id))->toBeNull()
        ->and(Saprotan::withTrashed()->find($id)->trashed())->toBeTrue();
});
