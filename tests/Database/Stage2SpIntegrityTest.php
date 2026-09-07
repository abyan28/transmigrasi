<?php

use App\Enums\CakupanData;
use App\Models\Desa;
use App\Models\FasilitasSp;
use App\Models\Infrastruktur;
use App\Models\InventarisSp;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\Pengaduan;
use App\Models\Role;
use App\Models\SatuanPermukiman;
use App\Models\User;
use Database\Seeders\AsetSpSeeder;
use Database\Seeders\DaftarPilihanSeeder;
use Database\Seeders\InfrastrukturSeeder;
use Database\Seeders\KawasanSeeder;
use Database\Seeders\PengaduanSeeder;
use Database\Seeders\SpSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(WilayahSeeder::class);
    $this->seed(KawasanSeeder::class);
    $this->seed(SpSeeder::class);
    $this->seed(DaftarPilihanSeeder::class);
    $this->seed(AsetSpSeeder::class);
    $this->seed(InfrastrukturSeeder::class);
    $this->seed(PengaduanSeeder::class);

    $petugas = User::factory()->create();
    $petugas->semuaIzin = true;
    $this->actingAs($petugas);
});

function penggunaStage2(array $sp): User
{
    $role = Role::factory()->create(['cakupan_data' => CakupanData::PerSp]);
    $pengguna = User::factory()->create(['role_id' => $role->id_role]);
    $pengguna->semuaIzin = true;
    $pengguna->satuanPermukiman()->attach($sp);

    return $pengguna;
}

function desaStage2DiKabupatenLain(): Desa
{
    $kabupaten = Kabupaten::query()->where('id_kabupaten', '!=', 5321)->firstOrFail();
    $kecamatan = Kecamatan::create([
        'kabupaten_id' => $kabupaten->id_kabupaten,
        'nama' => 'KECAMATAN UJI SILANG',
    ]);

    return Desa::create([
        'kecamatan_id' => $kecamatan->id_kecamatan,
        'nama' => 'DESA UJI SILANG',
    ]);
}

it('menolak pembuatan dan pembaruan SP lintas kabupaten', function () {
    $desa = desaStage2DiKabupatenLain();

    $this->post(route('sp.simpan'), [
        'nama' => 'SP SILANG KABUPATEN BARU',
        'kawasan_id' => 1,
        'desa_id' => $desa->id_desa,
    ])->assertSessionHasErrors('desa_id');

    $sp = SatuanPermukiman::query()->findOrFail(1);
    $this->put(route('sp.perbarui', $sp->id_satuan_permukiman), [
        'nama' => $sp->nama,
        'kode_sp' => $sp->kode_sp,
        'kawasan_id' => $sp->kawasan_id,
        'desa_id' => $desa->id_desa,
    ])->assertSessionHasErrors('desa_id');

    expect(SatuanPermukiman::query()->where('nama', 'SP SILANG KABUPATEN BARU')->exists())->toBeFalse()
        ->and($sp->fresh()->desa_id)->not->toBe($desa->id_desa);
});

it('menolak pembuatan dan pemindahan inventaris ke SP yang tidak ditugaskan', function () {
    $sp = SatuanPermukiman::query()->orderBy('id_satuan_permukiman')->get();
    $this->actingAs(penggunaStage2([$sp[0]->id_satuan_permukiman]));

    $this->post(route('inventaris.simpan'), [
        'satuan_permukiman_id' => $sp[1]->id_satuan_permukiman,
        'nama_barang' => 'INVENTARIS PALSU',
        'jumlah' => 1,
        'status_penyerahan' => 'Sudah Diserahkan',
    ])->assertNotFound();

    $inventaris = InventarisSp::query()->where('satuan_permukiman_id', $sp[0]->id_satuan_permukiman)->firstOrFail();
    $this->put(route('inventaris.perbarui', $inventaris->id_inventaris_sp), [
        'satuan_permukiman_id' => $sp[1]->id_satuan_permukiman,
        'nama_barang' => $inventaris->nama_barang,
        'jumlah' => $inventaris->jumlah,
        'status_penyerahan' => $inventaris->status_penyerahan,
    ])->assertNotFound();

    expect(InventarisSp::withoutGlobalScopes()->where('nama_barang', 'INVENTARIS PALSU')->exists())->toBeFalse()
        ->and($inventaris->fresh()->satuan_permukiman_id)->toBe($sp[0]->id_satuan_permukiman);
});

it('menolak pembuatan dan pemindahan fasilitas ke SP yang tidak ditugaskan', function () {
    $sp = SatuanPermukiman::query()->orderBy('id_satuan_permukiman')->get();
    $this->actingAs(penggunaStage2([$sp[0]->id_satuan_permukiman]));

    $this->post(route('fasilitas.simpan'), [
        'satuan_permukiman_id' => $sp[1]->id_satuan_permukiman,
        'jenis_fasilitas' => 'Kesehatan',
        'nama_fasilitas' => 'FASILITAS PALSU',
        'jumlah' => 1,
        'status_penyerahan' => 'Sudah Diserahkan',
    ])->assertNotFound();

    $fasilitas = FasilitasSp::query()->where('satuan_permukiman_id', $sp[0]->id_satuan_permukiman)->firstOrFail();
    $this->put(route('fasilitas.perbarui', $fasilitas->id_fasilitas_sp), [
        'satuan_permukiman_id' => $sp[1]->id_satuan_permukiman,
        'jenis_fasilitas' => $fasilitas->jenis_fasilitas,
        'nama_fasilitas' => $fasilitas->nama_fasilitas,
        'jumlah' => $fasilitas->jumlah,
        'status_penyerahan' => $fasilitas->status_penyerahan,
    ])->assertNotFound();

    expect(FasilitasSp::withoutGlobalScopes()->where('nama_fasilitas', 'FASILITAS PALSU')->exists())->toBeFalse()
        ->and($fasilitas->fresh()->satuan_permukiman_id)->toBe($sp[0]->id_satuan_permukiman);
});

it('menolak pembuatan dan pemindahan infrastruktur ke SP yang tidak ditugaskan', function () {
    $sp = SatuanPermukiman::query()->orderBy('id_satuan_permukiman')->get();
    $this->actingAs(penggunaStage2([$sp[0]->id_satuan_permukiman]));

    $this->post(route('infrastruktur.simpan'), [
        'satuan_permukiman_id' => $sp[1]->id_satuan_permukiman,
        'nama' => 'INFRASTRUKTUR PALSU',
        'jenis' => 'Irigasi',
        'kondisi' => 'Baik',
    ])->assertNotFound();

    $infrastruktur = Infrastruktur::query()->where('satuan_permukiman_id', $sp[0]->id_satuan_permukiman)->firstOrFail();
    $this->put(route('infrastruktur.perbarui', $infrastruktur->id_infrastruktur), [
        'satuan_permukiman_id' => $sp[1]->id_satuan_permukiman,
        'nama' => $infrastruktur->nama,
        'jenis' => $infrastruktur->jenis,
        'kondisi' => $infrastruktur->kondisi,
    ])->assertNotFound();

    expect(Infrastruktur::withoutGlobalScopes()->where('nama', 'INFRASTRUKTUR PALSU')->exists())->toBeFalse()
        ->and($infrastruktur->fresh()->satuan_permukiman_id)->toBe($sp[0]->id_satuan_permukiman);
});

it('menolak pembuatan dan pemindahan pengaduan ke SP yang tidak ditugaskan', function () {
    $sp = SatuanPermukiman::query()->orderBy('id_satuan_permukiman')->get();
    $this->actingAs(penggunaStage2([$sp[0]->id_satuan_permukiman]));

    $this->post(route('pengaduan.simpan'), [
        'nama_pelapor' => 'PELAPOR PALSU',
        'kontak_pelapor' => '081200000000',
        'satuan_permukiman_id' => $sp[1]->id_satuan_permukiman,
        'tanggal_pengaduan' => '2026-09-01',
        'kategori' => 'Rumah',
        'prioritas' => 'Sedang',
        'judul' => 'PENGADUAN PALSU',
        'deskripsi' => 'Target SP tidak ditugaskan.',
    ])->assertNotFound();

    $pengaduan = Pengaduan::query()->where('satuan_permukiman_id', $sp[0]->id_satuan_permukiman)->firstOrFail();
    $this->put(route('pengaduan.perbarui', $pengaduan->id_pengaduan), [
        'nama_pelapor' => $pengaduan->nama_pelapor,
        'kontak_pelapor' => $pengaduan->kontak_pelapor,
        'satuan_permukiman_id' => $sp[1]->id_satuan_permukiman,
        'tanggal_pengaduan' => $pengaduan->tanggal_pengaduan->toDateString(),
        'kategori' => $pengaduan->kategori,
        'bidang' => $pengaduan->bidang,
        'prioritas' => $pengaduan->prioritas,
        'judul' => $pengaduan->judul,
        'deskripsi' => $pengaduan->deskripsi,
    ])->assertNotFound();

    expect(Pengaduan::withoutGlobalScopes()->where('judul', 'PENGADUAN PALSU')->exists())->toBeFalse()
        ->and($pengaduan->fresh()->satuan_permukiman_id)->toBe($sp[0]->id_satuan_permukiman);
});

it('menolak cakupan fasilitas dan infrastruktur pada SP yang tidak ditugaskan', function () {
    $sp = SatuanPermukiman::query()->orderBy('id_satuan_permukiman')->get();
    $this->actingAs(penggunaStage2([$sp[0]->id_satuan_permukiman]));

    $this->post(route('fasilitas.simpan'), [
        'satuan_permukiman_id' => $sp[0]->id_satuan_permukiman,
        'satuan_permukiman_ids_lain' => [$sp[1]->id_satuan_permukiman],
        'jenis_fasilitas' => 'Kesehatan',
        'nama_fasilitas' => 'CAKUPAN FASILITAS PALSU',
        'jumlah' => 1,
        'status_penyerahan' => 'Sudah Diserahkan',
    ])->assertNotFound();

    $this->post(route('infrastruktur.simpan'), [
        'satuan_permukiman_id' => $sp[0]->id_satuan_permukiman,
        'satuan_permukiman_ids_lain' => [$sp[1]->id_satuan_permukiman],
        'nama' => 'CAKUPAN INFRASTRUKTUR PALSU',
        'jenis' => 'Irigasi',
        'kondisi' => 'Baik',
    ])->assertNotFound();
});

it('mempertahankan seluruh cakupan saat ubah cepat tidak membawa penanda cakupan', function () {
    $fasilitas = FasilitasSp::with('cakupan')->findOrFail(2);
    $cakupanFasilitas = $fasilitas->cakupan->pluck('id_satuan_permukiman')->sort()->values()->all();
    $this->put(route('fasilitas.perbarui', $fasilitas->id_fasilitas_sp), [
        'satuan_permukiman_id' => $fasilitas->satuan_permukiman_id,
        'jenis_fasilitas' => $fasilitas->jenis_fasilitas,
        'nama_fasilitas' => 'FASILITAS QUICK EDIT',
        'jumlah' => $fasilitas->jumlah,
        'status_penyerahan' => $fasilitas->status_penyerahan,
        'kondisi' => $fasilitas->kondisi,
    ])->assertRedirect(route('sp.fasilitas'));

    $infrastruktur = Infrastruktur::with('cakupan')->findOrFail(1);
    $cakupanInfrastruktur = $infrastruktur->cakupan->pluck('id_satuan_permukiman')->sort()->values()->all();
    $this->put(route('infrastruktur.perbarui', $infrastruktur->id_infrastruktur), [
        'satuan_permukiman_id' => $infrastruktur->satuan_permukiman_id,
        'nama' => 'INFRASTRUKTUR QUICK EDIT',
        'jenis' => $infrastruktur->jenis,
        'kondisi' => $infrastruktur->kondisi,
    ])->assertRedirect(route('infrastruktur.index'));

    expect($fasilitas->fresh()->cakupan->pluck('id_satuan_permukiman')->sort()->values()->all())->toBe($cakupanFasilitas)
        ->and($infrastruktur->fresh()->cakupan->pluck('id_satuan_permukiman')->sort()->values()->all())->toBe($cakupanInfrastruktur);
});

it('mengganti cakupan terlihat tetapi mempertahankan cakupan tersembunyi pada edit eksplisit', function () {
    $sp = SatuanPermukiman::query()->orderBy('id_satuan_permukiman')->get();
    $fasilitas = FasilitasSp::withoutGlobalScopes()->findOrFail(1);
    $infrastruktur = Infrastruktur::withoutGlobalScopes()->findOrFail(1);
    $fasilitas->cakupan()->sync([$sp[0]->id_satuan_permukiman, $sp[1]->id_satuan_permukiman, $sp[2]->id_satuan_permukiman]);
    $infrastruktur->cakupan()->sync([$sp[0]->id_satuan_permukiman, $sp[1]->id_satuan_permukiman, $sp[2]->id_satuan_permukiman]);

    $this->actingAs(penggunaStage2([
        $sp[0]->id_satuan_permukiman,
        $sp[2]->id_satuan_permukiman,
        $sp[3]->id_satuan_permukiman,
    ]));

    $this->put(route('fasilitas.perbarui', $fasilitas->id_fasilitas_sp), [
        '_cakupan_disunting' => 1,
        'satuan_permukiman_id' => $sp[0]->id_satuan_permukiman,
        'satuan_permukiman_ids_lain' => [$sp[3]->id_satuan_permukiman],
        'jenis_fasilitas' => $fasilitas->jenis_fasilitas,
        'nama_fasilitas' => $fasilitas->nama_fasilitas,
        'jumlah' => $fasilitas->jumlah,
        'status_penyerahan' => $fasilitas->status_penyerahan,
        'kondisi' => $fasilitas->kondisi,
    ])->assertRedirect(route('sp.fasilitas'));

    $this->put(route('infrastruktur.perbarui', $infrastruktur->id_infrastruktur), [
        '_cakupan_disunting' => 1,
        'satuan_permukiman_id' => $sp[0]->id_satuan_permukiman,
        'satuan_permukiman_ids_lain' => [$sp[3]->id_satuan_permukiman],
        'nama' => $infrastruktur->nama,
        'jenis' => $infrastruktur->jenis,
        'kondisi' => $infrastruktur->kondisi,
    ])->assertRedirect(route('infrastruktur.index'));

    $diharapkan = [$sp[0]->id_satuan_permukiman, $sp[1]->id_satuan_permukiman, $sp[3]->id_satuan_permukiman];
    expect($fasilitas->fresh()->cakupan->pluck('id_satuan_permukiman')->sort()->values()->all())->toBe($diharapkan)
        ->and($infrastruktur->fresh()->cakupan->pluck('id_satuan_permukiman')->sort()->values()->all())->toBe($diharapkan);
});

it('menggulung balik induk ketika penulisan pivot gagal', function (string $pivot, string $rute, string $tabel, string $kolom, string $nilai, array $data) {
    DB::listen(function (QueryExecuted $query) use ($pivot) {
        if (str_contains($query->sql, $pivot) && str_starts_with(strtolower(ltrim($query->sql)), 'insert')) {
            throw new RuntimeException('Gagal pivot untuk menguji transaksi.');
        }
    });
    $this->withoutExceptionHandling();

    try {
        $this->post(route($rute), $data);
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('Gagal pivot untuk menguji transaksi.');
    }

    expect(DB::table($tabel)->where($kolom, $nilai)->exists())->toBeFalse();
})->with([
    'fasilitas' => [
        'fasilitas_sp_cakupan',
        'fasilitas.simpan',
        'fasilitas_sp',
        'nama_fasilitas',
        'FASILITAS ROLLBACK',
        [
            'satuan_permukiman_id' => 1,
            'jenis_fasilitas' => 'Kesehatan',
            'nama_fasilitas' => 'FASILITAS ROLLBACK',
            'jumlah' => 1,
            'status_penyerahan' => 'Sudah Diserahkan',
        ],
    ],
    'infrastruktur' => [
        'infrastruktur_sp',
        'infrastruktur.simpan',
        'infrastruktur',
        'nama',
        'INFRASTRUKTUR ROLLBACK',
        [
            'satuan_permukiman_id' => 1,
            'nama' => 'INFRASTRUKTUR ROLLBACK',
            'jenis' => 'Irigasi',
            'kondisi' => 'Baik',
        ],
    ],
]);

it('menggulung balik perubahan induk ketika pembaruan pivot gagal', function (string $modelClass, int $id, string $pivot, string $rute, string $kolom, array $data) {
    $model = $modelClass::with('cakupan')->findOrFail($id);
    $nilaiLama = $model->{$kolom};
    $cakupanLama = $model->cakupan->pluck('id_satuan_permukiman')->sort()->values()->all();

    DB::listen(function (QueryExecuted $query) use ($pivot) {
        if (str_contains($query->sql, $pivot) && str_starts_with(strtolower(ltrim($query->sql)), 'insert')) {
            throw new RuntimeException('Gagal pivot untuk menguji transaksi.');
        }
    });
    $this->withoutExceptionHandling();

    try {
        $this->put(route($rute, $model->getKey()), $data);
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('Gagal pivot untuk menguji transaksi.');
    }

    expect($model->fresh()->{$kolom})->toBe($nilaiLama)
        ->and($model->fresh()->cakupan->pluck('id_satuan_permukiman')->sort()->values()->all())->toBe($cakupanLama);
})->with([
    'fasilitas' => [
        FasilitasSp::class,
        1,
        'fasilitas_sp_cakupan',
        'fasilitas.perbarui',
        'nama_fasilitas',
        [
            '_cakupan_disunting' => 1,
            'satuan_permukiman_id' => 1,
            'satuan_permukiman_ids_lain' => [3],
            'jenis_fasilitas' => 'Balai Pertemuan',
            'nama_fasilitas' => 'FASILITAS UPDATE ROLLBACK',
            'jumlah' => 1,
            'status_penyerahan' => 'Sudah Diserahkan',
            'kondisi' => 'Baik',
        ],
    ],
    'infrastruktur' => [
        Infrastruktur::class,
        1,
        'infrastruktur_sp',
        'infrastruktur.perbarui',
        'nama',
        [
            '_cakupan_disunting' => 1,
            'satuan_permukiman_id' => 1,
            'satuan_permukiman_ids_lain' => [2, 3],
            'nama' => 'INFRASTRUKTUR UPDATE ROLLBACK',
            'jenis' => 'Irigasi',
            'kondisi' => 'Rusak Ringan',
        ],
    ],
]);
