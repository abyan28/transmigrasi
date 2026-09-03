<?php

/*
 * Task 3.1 -- DOMAIN 4b, pivot `*_berkas` yang induknya sudah ada.
 *
 * Batch ini: user_berkas, kawasan_transmigrasi_berkas, inventaris_sp_berkas,
 * fasilitas_sp_berkas. Delapan pivot `*_berkas` sisanya menyusul bersama
 * batch domainnya (B5-B9). Pivot murni tanpa model -- diuji lewat relasi
 * `belongsToMany` pada model induk.
 *
 * `buatSp()` di-share dari Domain2WilayahSpTest.
 */

use App\Enums\JenisFasilitas;
use App\Models\Berkas;
use App\Models\FasilitasSp;
use App\Models\InventarisSp;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

function buatBerkas(?int $userId = null): Berkas
{
    return Berkas::create([
        'uuid' => (string) Str::uuid(),
        'nama_file' => 'x-'.Str::random(6).'.pdf',
        'path' => 'uji/'.Str::random(8).'.pdf',
        'mime' => 'application/pdf',
        'ekstensi' => 'pdf',
        'ukuran' => 2048,
        'user_id' => $userId,
    ]);
}

it('membuat keempat pivot berkas batch ini', function () {
    foreach ([
        'user_berkas', 'kawasan_transmigrasi_berkas', 'inventaris_sp_berkas', 'fasilitas_sp_berkas',
    ] as $tabel) {
        expect(Schema::hasTable($tabel))->toBeTrue("tabel {$tabel} tidak dibuat");
    }
});

it('menautkan foto profil user paling banyak satu (UNIQUE user_id)', function () {
    $user = User::factory()->create(['role_id' => Role::factory()->create()->id_role]);
    $b1 = buatBerkas($user->id_user);
    $b2 = buatBerkas($user->id_user);

    $user->fotoProfil()->attach($b1->id_berkas, ['peran' => 'foto']);

    expect($user->fotoProfil()->count())->toBe(1)
        ->and($user->fotoProfil->first()->pivot->peran)->toBe('foto');

    // foto kedua ditolak -- UNIQUE pada user_id saja.
    expect(fn () => $user->fotoProfil()->attach($b2->id_berkas, ['peran' => 'foto']))
        ->toThrow(QueryException::class);
});

it('menautkan banyak berkas ke kawasan lewat pivot dengan peran + urutan', function () {
    $kawasan = buatSp()->kawasan;
    $hpl = buatBerkas();
    $peta = buatBerkas();

    $kawasan->berkas()->attach($hpl->id_berkas, ['peran' => 'hpl', 'urutan' => 0]);
    $kawasan->berkas()->attach($peta->id_berkas, ['peran' => 'peta', 'urutan' => 1]);

    expect($kawasan->berkas()->count())->toBe(2)
        ->and($kawasan->berkas()->wherePivot('peran', 'hpl')->first()->id_berkas)->toBe($hpl->id_berkas);

    // pasangan (kawasan, berkas) yang sama tidak boleh dobel.
    expect(fn () => $kawasan->berkas()->attach($hpl->id_berkas, ['peran' => 'hpl']))
        ->toThrow(QueryException::class);
});

it('menautkan berkas ke inventaris & fasilitas SP dan ikut terhapus saat induk hilang (CASCADE)', function () {
    $sp = buatSp();
    $inventaris = InventarisSp::create([
        'satuan_permukiman_id' => $sp->id_satuan_permukiman, 'jenis_inventaris' => 'Kendaraan',
        'nama_barang' => 'Traktor', 'status_penyerahan' => 'Belum',
    ]);
    $fasilitas = FasilitasSp::create([
        'satuan_permukiman_id' => $sp->id_satuan_permukiman,
        'jenis_fasilitas' => JenisFasilitas::Kesehatan->value,
        'nama_fasilitas' => 'Pustu', 'status_penyerahan' => 'Sudah',
    ]);
    $bInv = buatBerkas();
    $bFas = buatBerkas();

    $inventaris->berkas()->attach($bInv->id_berkas, ['peran' => 'foto']);
    $fasilitas->berkas()->attach($bFas->id_berkas, ['peran' => 'foto']);

    expect($inventaris->berkas()->count())->toBe(1)
        ->and($fasilitas->berkas()->count())->toBe(1);

    $inventaris->forceDelete();
    $fasilitas->forceDelete();

    expect(DB::table('inventaris_sp_berkas')->count())->toBe(0)
        ->and(DB::table('fasilitas_sp_berkas')->count())->toBe(0)
        // berkas induk (registry) TIDAK ikut terhapus -- hanya baris pivotnya.
        ->and(Berkas::whereIn('id_berkas', [$bInv->id_berkas, $bFas->id_berkas])->count())->toBe(2);
});

it('menghapus baris pivot saat berkas registry dihapus permanen (CASCADE ke berkas)', function () {
    $sp = buatSp();
    $fasilitas = FasilitasSp::create([
        'satuan_permukiman_id' => $sp->id_satuan_permukiman,
        'jenis_fasilitas' => JenisFasilitas::Ibadah->value,
        'nama_fasilitas' => 'Masjid', 'status_penyerahan' => 'Sudah',
    ]);
    $berkas = buatBerkas();
    $fasilitas->berkas()->attach($berkas->id_berkas, ['peran' => 'foto']);

    $berkas->forceDelete();

    expect($fasilitas->berkas()->count())->toBe(0);
});
