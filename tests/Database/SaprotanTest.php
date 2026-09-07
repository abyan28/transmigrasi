<?php

/*
 * Task 6.7 (saprotan): pola INDUK + DISTRIBUSI.
 *
 * Yang dijaga: satu pengadaan tersebar ke banyak poktan; komoditas & varietas
 * wajib hanya untuk jenis Benih; Sigma distribusi <= jumlah_total; poktan yang
 * dilepas kehilangan barisnya; satuan non-berat (Liter/Rol) dapat dipakai.
 */

use App\Enums\CakupanData;
use App\Enums\JenisDaftarPilihan;
use App\Models\DaftarPilihan;
use App\Models\Komoditas;
use App\Models\Poktan;
use App\Models\Role;
use App\Models\Saprotan;
use App\Models\SaprotanDistribusi;
use App\Models\Satuan;
use App\Models\User;
use App\Support\DummyData;
use Database\Seeders\DaftarPilihanSeeder;
use Database\Seeders\KawasanSeeder;
use Database\Seeders\KomoditasSeeder;
use Database\Seeders\LahanSeeder;
use Database\Seeders\PenanamanSeeder;
use Database\Seeders\PoktanSeeder;
use Database\Seeders\SaprotanSeeder;
use Database\Seeders\SatuanSeeder;
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
    $this->seed(SatuanSeeder::class);
    $this->seed(DaftarPilihanSeeder::class);
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
    expect($benih->jenis)->toBe('Benih')
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

it('mewajibkan kode saprotan pada pengadaan baru', function () {
    $this->post(route('saprotan.simpan'), [
        'jenis' => 'Pupuk',
        'nama' => 'PUPUK TANPA KODE',
        'jumlah_total' => '10',
        'satuan_id' => Satuan::where('nama', 'Kilogram')->value('id_satuan'),
        'tahun_pengadaan' => 2025,
    ])->assertSessionHasErrors('kode_saprotan');
});

it('menyimpan pengadaan benih dengan komoditas dan varietas', function () {
    $poktan = Poktan::orderBy('id_poktan')->take(2)->pluck('id_poktan');
    $satuan = Satuan::where('nama', 'Kilogram')->value('id_satuan');
    $komoditas = Komoditas::where('nama', 'JAGUNG')->value('id_komoditas');

    $this->post(route('saprotan.simpan'), [
        'kode_saprotan' => 'SAP-UJI-001',
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
        'kode_saprotan' => 'SAP-UJI-002',
        'jenis' => 'Benih',
        'nama' => 'BENIH TANPA KOMODITAS',
        'jumlah_total' => '50',
        'satuan_id' => $satuan,
        'tahun_pengadaan' => 2025,
    ])->assertSessionHasErrors(['komoditas_id', 'varietas']);
});

it('menerapkan perilaku benih pada jenis saprotan baru dari data master', function () {
    DaftarPilihan::create([
        'jenis' => JenisDaftarPilihan::JenisSaprotan->value,
        'nilai' => 'Bibit',
        'label' => 'Bibit Tanaman',
        'kode_perilaku' => 'benih',
    ]);

    $this->post(route('saprotan.simpan'), [
        'kode_saprotan' => 'SAP-BIBIT-001',
        'jenis' => 'Bibit',
        'nama' => 'BIBIT TANPA KOMODITAS',
        'jumlah_total' => '10',
        'satuan_id' => Satuan::where('nama', 'Kilogram')->value('id_satuan'),
        'tahun_pengadaan' => 2026,
    ])->assertSessionHasErrors(['komoditas_id', 'varietas']);
});

it('menampilkan label jenis saprotan tanpa mengubah kode tersimpan', function () {
    DaftarPilihan::where('jenis', JenisDaftarPilihan::JenisSaprotan->value)
        ->where('nilai', 'Benih')->update(['label' => 'Bibit']);

    $saprotan = Saprotan::where('jenis', 'Benih')->firstOrFail();
    $baris = \App\Support\PenyajianSaprotan::baris($saprotan);

    expect($saprotan->jenis)->toBe('Benih')
        ->and($baris['jenis_label'])->toBe('Bibit');
});

it('tidak menyimpan komoditas untuk jenis non-Benih', function () {
    $satuan = Satuan::where('nama', 'Liter')->value('id_satuan');
    $komoditas = Komoditas::where('nama', 'JAGUNG')->value('id_komoditas');

    $this->post(route('saprotan.simpan'), [
        'kode_saprotan' => 'SAP-UJI-003',
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
        'kode_saprotan' => 'SAP-UJI-004',
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
        'jenis' => $benih->jenis,
        'nama' => $benih->nama,
        'komoditas_id' => $benih->komoditas_id,
        'varietas' => $benih->varietas,
        'jumlah_total' => (string) $benih->jumlah_total,
        'satuan_id' => $benih->satuan_id,
        'tahun_pengadaan' => $benih->tahun_pengadaan,
        'ganti_distribusi' => '1',
        'poktan_id' => [$tetap],
        'distribusi' => [$tetap => ['jumlah' => '150']],
    ])->assertRedirect(route('saprotan.detail', $benih->id_saprotan));

    $benih->refresh()->load('distribusi');
    expect($benih->distribusi)->toHaveCount(1)
        ->and($benih->distribusi->first()->poktan_id)->toBe($tetap);
});

it('mempertahankan distribusi bila pembaruan tidak membawa penanda penggantian', function () {
    $benih = Saprotan::where('nama', 'BENIH JAGUNG HIBRIDA')->first();
    $sebelum = $benih->distribusi()->orderBy('poktan_id')->pluck('jumlah', 'poktan_id')->all();

    $this->put(route('saprotan.perbarui', $benih->id_saprotan), [
        'jenis' => $benih->jenis,
        'nama' => 'BENIH AMAN QUICK EDIT',
        'komoditas_id' => $benih->komoditas_id,
        'varietas' => $benih->varietas,
        'jumlah_total' => $benih->jumlah_total,
        'satuan_id' => $benih->satuan_id,
        'jadwal_tanam' => $benih->jadwal_tanam,
        'tahun_pengadaan' => $benih->tahun_pengadaan,
        'sumber_dana' => $benih->sumber_dana,
        'keterangan' => $benih->keterangan,
    ])->assertRedirect(route('saprotan.detail', $benih->id_saprotan));

    expect($benih->refresh()->nama)->toBe('BENIH AMAN QUICK EDIT')
        ->and($benih->distribusi()->orderBy('poktan_id')->pluck('jumlah', 'poktan_id')->all())->toBe($sebelum);
});

it('menjaga distribusi lintas cakupan dari aktor Per SP', function () {
    $saprotan = Saprotan::find(1);
    $saprotan->update(['jumlah_total' => 300]);
    SaprotanDistribusi::create(['saprotan_id' => 1, 'poktan_id' => 3, 'jumlah' => 50]);
    $role = Role::factory()->create(['cakupan_data' => CakupanData::PerSp->value]);
    $operator = User::factory()->create(['role_id' => $role->id_role]);
    $operator->satuanPermukiman()->attach(1);
    $operator->semuaIzin = true;
    $this->actingAs($operator);

    $induk = [
        'jenis' => $saprotan->jenis,
        'nama' => $saprotan->nama,
        'komoditas_id' => $saprotan->komoditas_id,
        'varietas' => $saprotan->varietas,
        'jumlah_total' => $saprotan->jumlah_total,
        'satuan_id' => $saprotan->satuan_id,
        'jadwal_tanam' => $saprotan->jadwal_tanam,
        'tahun_pengadaan' => $saprotan->tahun_pengadaan,
        'sumber_dana' => $saprotan->sumber_dana,
        'keterangan' => $saprotan->keterangan,
    ];

    $this->put(route('saprotan.perbarui', $saprotan->id_saprotan), $induk + [
        'ganti_distribusi' => '1',
        'poktan_id' => [3],
        'distribusi' => [3 => ['jumlah' => 10]],
    ])->assertNotFound();

    $this->put(route('saprotan.perbarui', $saprotan->id_saprotan), $induk + [
        'ganti_distribusi' => '1',
        'poktan_id' => [1],
        'distribusi' => [1 => ['jumlah' => 260]],
    ])->assertSessionHasErrors('distribusi');

    $this->put(route('saprotan.perbarui', $saprotan->id_saprotan), $induk + [
        'ganti_distribusi' => '1',
        'poktan_id' => [1],
        'distribusi' => [1 => ['jumlah' => 140]],
    ])->assertRedirect(route('saprotan.detail', $saprotan->id_saprotan));

    expect(SaprotanDistribusi::withoutGlobalScopes()->where('saprotan_id', $saprotan->id_saprotan)->count())->toBe(2)
        ->and((float) SaprotanDistribusi::withoutGlobalScopes()->where('saprotan_id', $saprotan->id_saprotan)->where('poktan_id', 3)->value('jumlah'))->toBe(50.0);
});

it('melarang aktor Per SP mengubah metadata atau menghapus induk bersama', function () {
    $saprotan = Saprotan::find(1);
    $role = Role::factory()->create(['cakupan_data' => CakupanData::PerSp->value]);
    $operator = User::factory()->create(['role_id' => $role->id_role]);
    $operator->satuanPermukiman()->attach(2);
    $operator->semuaIzin = true;
    $this->actingAs($operator);

    $this->put(route('saprotan.perbarui', $saprotan->id_saprotan), [
        'jenis' => $saprotan->jenis,
        'nama' => 'UBAH INDUK BERSAMA',
        'komoditas_id' => $saprotan->komoditas_id,
        'varietas' => $saprotan->varietas,
        'jumlah_total' => $saprotan->jumlah_total,
        'satuan_id' => $saprotan->satuan_id,
        'jadwal_tanam' => $saprotan->jadwal_tanam,
        'tahun_pengadaan' => $saprotan->tahun_pengadaan,
        'sumber_dana' => $saprotan->sumber_dana,
        'keterangan' => $saprotan->keterangan,
    ])->assertForbidden();

    $this->delete(route('saprotan.hapus', $saprotan->id_saprotan))->assertForbidden();
    expect($saprotan->fresh())->not->toBeNull();
});

it('menolak perubahan saprotan yang membuat pemakaian benih tidak sah', function () {
    $this->seed(PenanamanSeeder::class);
    $saprotan = Saprotan::find(1);
    $poktan = $saprotan->distribusi()->first()->poktan_id;

    $induk = [
        'jenis' => 'Pupuk',
        'nama' => $saprotan->nama,
        'jumlah_total' => $saprotan->jumlah_total,
        'satuan_id' => $saprotan->satuan_id,
        'jadwal_tanam' => $saprotan->jadwal_tanam,
        'tahun_pengadaan' => $saprotan->tahun_pengadaan,
        'sumber_dana' => $saprotan->sumber_dana,
        'keterangan' => $saprotan->keterangan,
    ];

    $this->put(route('saprotan.perbarui', $saprotan->id_saprotan), $induk)->assertSessionHasErrors('jenis');

    $this->put(route('saprotan.perbarui', $saprotan->id_saprotan), [
        ...$induk,
        'jenis' => 'Benih',
        'komoditas_id' => Komoditas::where('id_komoditas', '!=', $saprotan->komoditas_id)->value('id_komoditas'),
        'varietas' => $saprotan->varietas,
    ])->assertSessionHasErrors('jenis');

    $this->put(route('saprotan.perbarui', $saprotan->id_saprotan), [
        ...$induk,
        'jenis' => 'Benih',
        'komoditas_id' => $saprotan->komoditas_id,
        'varietas' => $saprotan->varietas,
        'satuan_id' => Satuan::where('id_satuan', '!=', $saprotan->satuan_id)->value('id_satuan'),
    ])->assertSessionHasErrors('jenis');

    expect($saprotan->fresh()->jenis)->toBe('Benih');

    $this->put(route('saprotan.perbarui', $saprotan->id_saprotan), [
        ...$induk,
        'jenis' => 'Benih',
        'komoditas_id' => $saprotan->komoditas_id,
        'varietas' => $saprotan->varietas,
        'ganti_distribusi' => '1',
        'poktan_id' => [$poktan],
        'distribusi' => [$poktan => ['jumlah' => '50']],
    ])->assertSessionHasErrors("distribusi.{$poktan}.jumlah");

    expect((float) $saprotan->distribusi()->where('poktan_id', $poktan)->value('jumlah'))->toBe(150.0);
});

it('menegakkan satu distribusi saprotan per poktan di basis data', function () {
    $baris = SaprotanDistribusi::first();

    expect(fn () => DB::table('saprotan_distribusi')->insert([
        'saprotan_id' => $baris->saprotan_id,
        'poktan_id' => $baris->poktan_id,
        'jumlah' => 1,
    ]))->toThrow(QueryException::class);
});

it('menghapus pengadaan saprotan secara halus', function () {
    $id = Saprotan::where('nama', 'INSEKTISIDA CAIR')->value('id_saprotan');

    $this->delete(route('saprotan.hapus', $id))->assertRedirect(route('saprotan.index'));

    expect(Saprotan::find($id))->toBeNull()
        ->and(Saprotan::withTrashed()->find($id)->trashed())->toBeTrue();
});
