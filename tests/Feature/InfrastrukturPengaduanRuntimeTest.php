<?php

use App\Enums\BidangPengaduan;
use App\Enums\CakupanData;
use App\Enums\JenisDaftarPilihan;
use App\Models\DaftarPilihan;
use App\Models\Role;
use App\Models\SatuanPermukiman;
use App\Models\User;
use App\Support\RekapDashboard;

it('reads SP lists, filter choices, mappings, and infrastructure recap from the database', function () {
    $sp = SatuanPermukiman::query()->firstOrFail();
    $sp->update(['nama' => 'SP PILIHAN NYATA']);

    $kondisi = DaftarPilihan::query()
        ->where('jenis', JenisDaftarPilihan::Kondisi->value)
        ->where('nilai', 'Rusak Ringan')
        ->firstOrFail();
    $kondisi->update(['is_aktif' => false]);

    $status = DaftarPilihan::query()
        ->where('jenis', JenisDaftarPilihan::StatusPenyerahan->value)
        ->where('nilai', 'Dalam Proses')
        ->firstOrFail();
    $status->update(['is_aktif' => false]);

    $kategori = DaftarPilihan::query()
        ->where('jenis', JenisDaftarPilihan::KategoriPengaduan->value)
        ->where('nilai', 'Rumah')
        ->firstOrFail();
    $pertanian = DaftarPilihan::query()
        ->where('jenis', JenisDaftarPilihan::BidangPengaduan->value)
        ->where('nilai', 'Pertanian')
        ->firstOrFail();
    $kategori->update(['bidang_id' => $pertanian->id_daftar_pilihan, 'is_aktif' => false]);

    $infrastruktur = $this->get(route('infrastruktur.index'))->assertOk();
    $fasilitas = $this->get(route('sp.fasilitas'))->assertOk();
    $inventaris = $this->get(route('sp.inventaris'))->assertOk();
    $pengaduan = $this->get(route('pengaduan.index'))->assertOk();
    $publik = $this->get(route('pengaduan-warga'))->assertOk();

    expect(collect($infrastruktur->viewData('daftarSp'))->pluck('nama'))->toContain('SP PILIHAN NYATA')
        ->and($infrastruktur->viewData('opsiFilterKondisi'))->toHaveKey('Rusak Ringan')
        ->and($infrastruktur->viewData('statusJenis'))->toBe(RekapDashboard::statusInfrastruktur())
        ->and($fasilitas->viewData('opsiFilterKondisi'))->toHaveKey('Rusak Ringan')
        ->and($inventaris->viewData('opsiFilterStatusPenyerahan'))->toHaveKey('Dalam Proses')
        ->and($pengaduan->viewData('opsiFilterKategori'))->toHaveKey('Rumah')
        ->and($publik->viewData('opsiKategoriPengaduan'))->not->toHaveKey('Rumah')
        ->and(BidangPengaduan::dariKategori('Rumah'))->toBe(BidangPengaduan::Pertanian);
});

it('scopes authenticated SP choices and records without narrowing the public form', function () {
    $sp = SatuanPermukiman::query()->orderBy('id_satuan_permukiman')->get();
    $role = Role::factory()->create(['cakupan_data' => CakupanData::PerSp]);
    $pengguna = User::factory()->create(['role_id' => $role->id_role]);
    $pengguna->semuaIzin = true;
    $pengguna->satuanPermukiman()->attach($sp[0]->id_satuan_permukiman);
    $this->actingAs($pengguna);

    foreach (['infrastruktur.index', 'sp.fasilitas', 'sp.inventaris', 'pengaduan.index'] as $rute) {
        $respons = $this->get(route($rute))->assertOk();

        expect(collect($respons->viewData('daftarSp'))->pluck('id_satuan_permukiman')->all())
            ->toBe([$sp[0]->id_satuan_permukiman]);
        expect(collect($respons->viewData('baris'))->pluck('satuan_permukiman_id')->unique()->values()->all())
            ->not->toContain($sp[1]->id_satuan_permukiman);
    }

    expect(collect($this->get(route('pengaduan-warga'))->assertOk()->viewData('daftarSp'))
        ->pluck('id_satuan_permukiman')->all())->toHaveCount($sp->count());
});

it('contains no runtime DummyData calls in the migrated controllers and mapping', function () {
    foreach ([
        app_path('Http/Controllers/InfrastrukturController.php'),
        app_path('Http/Controllers/FasilitasSpController.php'),
        app_path('Http/Controllers/InventarisSpController.php'),
        app_path('Http/Controllers/PengaduanController.php'),
        app_path('Http/Controllers/PengaduanPublikController.php'),
        app_path('Enums/BidangPengaduan.php'),
    ] as $file) {
        expect(file_get_contents($file))->not->toContain('DummyData::');
    }
});
