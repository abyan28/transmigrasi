<?php

/*
 * Task 6.6 (alsintan): pola INDUK + DISTRIBUSI.
 *
 * Yang dijaga: satu pengadaan tersebar ke banyak poktan lintas SP; kondisi
 * per baris distribusi; SUM(distribusi.jumlah) <= jumlah_total ditolak bila
 * dilanggar; poktan yang dilepas kehilangan barisnya; kondisi diperbarui
 * per baris lewat rute distribusi.
 */

use App\Enums\CakupanData;
use App\Models\Alsintan;
use App\Models\AlsintanDistribusi;
use App\Models\Poktan;
use App\Models\Role;
use App\Models\User;
use App\Support\DummyData;
use Database\Seeders\AlsintanSeeder;
use Database\Seeders\DaftarPilihanSeeder;
use Database\Seeders\KawasanSeeder;
use Database\Seeders\LahanSeeder;
use Database\Seeders\PoktanSeeder;
use Database\Seeders\SpSeeder;
use Database\Seeders\TransmigranSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/DatabaseHelpers.php';

beforeEach(function () {
    $petugas = User::factory()->create();
    $petugas->semuaIzin = true;
    $this->actingAs($petugas);
    $this->seed(WilayahSeeder::class);
    $this->seed(KawasanSeeder::class);
    $this->seed(SpSeeder::class);
    $this->seed(DaftarPilihanSeeder::class);
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
        'ganti_distribusi' => '1',
        'poktan_id' => [$tetap],
        'distribusi' => [
            $tetap => ['jumlah' => 2, 'kondisi' => 'Baik'],
        ],
    ])->assertRedirect(route('alsintan.detail', $traktor->id_alsintan));

    $traktor->refresh()->load('distribusi');
    expect($traktor->distribusi)->toHaveCount(1)
        ->and($traktor->distribusi->first()->poktan_id)->toBe($tetap);
});

it('mempertahankan distribusi bila pembaruan tidak membawa penanda penggantian', function () {
    $traktor = Alsintan::where('nama_alat', 'TRAKTOR RODA DUA KUBOTA')->first();
    $sebelum = $traktor->distribusi()->orderBy('poktan_id')->pluck('jumlah', 'poktan_id')->all();

    $this->put(route('alsintan.perbarui', $traktor->id_alsintan), [
        'jenis_alsintan' => $traktor->jenis_alsintan,
        'nama_alat' => 'TRAKTOR AMAN QUICK EDIT',
        'jumlah_total' => $traktor->jumlah_total,
        'tahun_pengadaan' => $traktor->tahun_pengadaan,
        'sumber_dana' => $traktor->sumber_dana,
        'keterangan' => $traktor->keterangan,
    ])->assertRedirect(route('alsintan.detail', $traktor->id_alsintan));

    expect($traktor->refresh()->nama_alat)->toBe('TRAKTOR AMAN QUICK EDIT')
        ->and($traktor->distribusi()->orderBy('poktan_id')->pluck('jumlah', 'poktan_id')->all())->toBe($sebelum);
});

it('menolak penanda terima dari poktan lain atau anggota tidak aktif', function () {
    $traktor = Alsintan::where('nama_alat', 'TRAKTOR RODA DUA KUBOTA')->first();
    $poktan = $traktor->distribusi()->first()->poktan_id;

    $payload = [
        'jenis_alsintan' => $traktor->jenis_alsintan,
        'nama_alat' => $traktor->nama_alat,
        'jumlah_total' => $traktor->jumlah_total,
        'tahun_pengadaan' => $traktor->tahun_pengadaan,
        'sumber_dana' => $traktor->sumber_dana,
        'keterangan' => $traktor->keterangan,
        'ganti_distribusi' => '1',
        'poktan_id' => [$poktan],
        'distribusi' => [$poktan => ['jumlah' => 2, 'kondisi' => 'Baik', 'penanda_terima_id' => 5]],
    ];

    $this->put(route('alsintan.perbarui', $traktor->id_alsintan), $payload)
        ->assertSessionHasErrors("distribusi.{$poktan}.penanda_terima_id");

    $payload['poktan_id'] = [4];
    $payload['distribusi'] = [4 => ['jumlah' => 1, 'kondisi' => 'Baik', 'penanda_terima_id' => 7]];

    $this->put(route('alsintan.perbarui', $traktor->id_alsintan), $payload)
        ->assertSessionHasErrors('distribusi.4.penanda_terima_id');
});

it('menjaga distribusi lintas cakupan dari aktor Per SP', function () {
    $traktor = Alsintan::where('nama_alat', 'TRAKTOR RODA DUA KUBOTA')->first();
    $role = Role::factory()->create(['cakupan_data' => CakupanData::PerSp->value]);
    $operator = User::factory()->create(['role_id' => $role->id_role]);
    $operator->satuanPermukiman()->attach(1);
    $operator->semuaIzin = true;
    $this->actingAs($operator);

    $induk = [
        'jenis_alsintan' => $traktor->jenis_alsintan,
        'nama_alat' => $traktor->nama_alat,
        'jumlah_total' => $traktor->jumlah_total,
        'tahun_pengadaan' => $traktor->tahun_pengadaan,
        'sumber_dana' => $traktor->sumber_dana,
        'keterangan' => $traktor->keterangan,
    ];

    $this->put(route('alsintan.perbarui', $traktor->id_alsintan), $induk + [
        'ganti_distribusi' => '1',
        'poktan_id' => [3],
        'distribusi' => [3 => ['jumlah' => 1, 'kondisi' => 'Baik']],
    ])->assertNotFound();

    $this->put(route('alsintan.perbarui', $traktor->id_alsintan), $induk + [
        'ganti_distribusi' => '1',
        'poktan_id' => [1],
        'distribusi' => [1 => ['jumlah' => 3, 'kondisi' => 'Baik', 'penanda_terima_id' => 1]],
    ])->assertSessionHasErrors('distribusi');

    $this->put(route('alsintan.perbarui', $traktor->id_alsintan), $induk + [
        'ganti_distribusi' => '1',
        'poktan_id' => [1],
        'distribusi' => [1 => ['jumlah' => 1, 'kondisi' => 'Baik', 'penanda_terima_id' => 1]],
    ])->assertRedirect(route('alsintan.detail', $traktor->id_alsintan));

    expect(AlsintanDistribusi::withoutGlobalScopes()->where('alsintan_id', $traktor->id_alsintan)->count())->toBe(3)
        ->and(AlsintanDistribusi::withoutGlobalScopes()->where('alsintan_id', $traktor->id_alsintan)->where('poktan_id', 3)->value('jumlah'))->toBe(1);
});

it('melarang aktor Per SP mengubah metadata atau menghapus induk bersama', function () {
    $traktor = Alsintan::where('nama_alat', 'TRAKTOR RODA DUA KUBOTA')->first();
    $role = Role::factory()->create(['cakupan_data' => CakupanData::PerSp->value]);
    $operator = User::factory()->create(['role_id' => $role->id_role]);
    $operator->satuanPermukiman()->attach(1);
    $operator->semuaIzin = true;
    $this->actingAs($operator);

    $this->put(route('alsintan.perbarui', $traktor->id_alsintan), [
        'jenis_alsintan' => $traktor->jenis_alsintan,
        'nama_alat' => 'UBAH INDUK BERSAMA',
        'jumlah_total' => $traktor->jumlah_total,
        'tahun_pengadaan' => $traktor->tahun_pengadaan,
        'sumber_dana' => $traktor->sumber_dana,
        'keterangan' => $traktor->keterangan,
    ])->assertForbidden();

    $this->delete(route('alsintan.hapus', $traktor->id_alsintan))->assertForbidden();
    expect($traktor->fresh())->not->toBeNull();
});

it('menegakkan satu distribusi alsintan per poktan di basis data', function () {
    $baris = AlsintanDistribusi::first();

    expect(fn () => DB::table('alsintan_distribusi')->insert([
        'alsintan_id' => $baris->alsintan_id,
        'poktan_id' => $baris->poktan_id,
        'jumlah' => 1,
    ]))->toThrow(QueryException::class);
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
