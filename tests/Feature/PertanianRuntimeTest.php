<?php

use App\Enums\CakupanData;
use App\Enums\JenisDaftarPilihan;
use App\Models\Alsintan;
use App\Models\DaftarPilihan;
use App\Models\HasilPanen;
use App\Models\Komoditas;
use App\Models\Penanaman;
use App\Models\Role;
use App\Models\SatuanPermukiman;
use App\Models\User;
use App\Support\KonversiPanen;

it('menghapus pemakaian DummyData dari controller pertanian', function () {
    foreach ([
        'AlsintanController.php',
        'SaprotanController.php',
        'PenanamanController.php',
        'HasilPanenController.php',
        'KomoditasController.php',
    ] as $controller) {
        expect(file_get_contents(app_path('Http/Controllers/'.$controller)))->not->toContain('DummyData');
    }
});

it('membaca pilihan filter dari daftar pilihan tersimpan', function () {
    DaftarPilihan::create([
        'jenis' => JenisDaftarPilihan::Kondisi,
        'nilai' => 'Kondisi Arsip Runtime',
        'urutan' => 999,
        'is_aktif' => false,
    ]);
    DaftarPilihan::create([
        'jenis' => JenisDaftarPilihan::TipeKomoditas,
        'nilai' => 'Tipe Arsip Runtime',
        'urutan' => 999,
        'is_aktif' => false,
    ]);

    $alsintan = $this->get(route('alsintan.index'))->assertOk();
    $komoditas = $this->get(route('komoditas.index'))->assertOk();

    expect($alsintan->viewData('opsiFilterKondisi'))->toHaveKey('Kondisi Arsip Runtime')
        ->and($komoditas->viewData('opsiFilterTipe'))->toHaveKey('Tipe Arsip Runtime');

    $id = Alsintan::query()->value('id_alsintan');
    expect($this->get(route('alsintan.detail', $id))->assertOk()->viewData('opsiKondisi'))
        ->not->toHaveKey('Kondisi Arsip Runtime');
});

it('membatasi pilihan SP halaman pertanian menurut penugasan pengguna', function () {
    $sp = SatuanPermukiman::query()->orderBy('id_satuan_permukiman')->get();
    $role = Role::factory()->create(['cakupan_data' => CakupanData::PerSp]);
    $pengguna = User::factory()->create(['role_id' => $role->id_role]);
    $pengguna->semuaIzin = true;
    $pengguna->satuanPermukiman()->attach($sp[0]->id_satuan_permukiman);
    $this->actingAs($pengguna);

    foreach (['alsintan.index', 'saprotan.index', 'penanaman', 'panen.index'] as $rute) {
        $daftarSp = $this->get(route($rute))->assertOk()->viewData('daftarSp');

        expect(array_column($daftarSp, 'id_satuan_permukiman'))
            ->toBe([$sp[0]->id_satuan_permukiman]);
    }
});

it('menghitung volume komoditas dari hasil panen tersimpan', function () {
    HasilPanen::query()->update(['produksi' => 0]);
    $panen = HasilPanen::query()->with(['penanaman.komoditas', 'satuan'])->firstOrFail();
    $panen->update([
        'periode_panen' => now()->format('Y-m'),
        'produksi' => 123.456,
    ]);

    $sebaran = $this->get(route('komoditas.index'))->assertOk()->viewData('sebaran');

    expect($sebaran)->toBe([
        $panen->penanaman->komoditas->nama => KonversiPanen::keTon(123.456, $panen->satuan),
    ]);
});

it('menampilkan riwayat penanaman komoditas dari Eloquent sesuai cakupan', function () {
    $komoditas = Komoditas::query()->where('nama', 'PADI')->firstOrFail();
    $terlihat = Penanaman::query()
        ->where('komoditas_id', $komoditas->id_komoditas)
        ->whereHas('poktan', fn ($query) => $query->where('satuan_permukiman_id', 1))
        ->firstOrFail();
    $tertutup = Penanaman::query()
        ->where('komoditas_id', $komoditas->id_komoditas)
        ->whereHas('poktan', fn ($query) => $query->where('satuan_permukiman_id', '!=', 1))
        ->firstOrFail();
    $terlihat->update(['periode_tanam' => '2024-07']);

    $role = Role::factory()->create(['cakupan_data' => CakupanData::PerSp]);
    $pengguna = User::factory()->create(['role_id' => $role->id_role]);
    $pengguna->semuaIzin = true;
    $pengguna->satuanPermukiman()->attach(1);
    $this->actingAs($pengguna);

    $riwayat = collect($this->get(route('komoditas.detail', $komoditas->id_komoditas))
        ->assertOk()
        ->viewData('riwayat'));

    expect($riwayat->pluck('id_penanaman')->all())->toContain($terlihat->id_penanaman)
        ->not->toContain($tertutup->id_penanaman)
        ->and($riwayat->firstWhere('id_penanaman', $terlihat->id_penanaman)['periode_tanam'])->toBe('2024-07');
});

it('menghitung rekap status dan volume panen dalam cakupan pengguna', function () {
    $sp = SatuanPermukiman::query()->orderBy('id_satuan_permukiman')->get();
    HasilPanen::query()->update(['produksi' => 0]);
    $terlihat = HasilPanen::query()
        ->whereHas('penanaman.poktan', fn ($query) => $query->where('satuan_permukiman_id', $sp[0]->id_satuan_permukiman))
        ->firstOrFail();
    $tertutup = HasilPanen::query()
        ->whereHas('penanaman.poktan', fn ($query) => $query->where('satuan_permukiman_id', '!=', $sp[0]->id_satuan_permukiman))
        ->firstOrFail();
    $terlihat->update(['produksi' => 10]);
    $tertutup->update(['produksi' => 999]);

    $role = Role::factory()->create(['cakupan_data' => CakupanData::PerSp]);
    $pengguna = User::factory()->create(['role_id' => $role->id_role]);
    $pengguna->semuaIzin = true;
    $pengguna->satuanPermukiman()->attach($sp[0]->id_satuan_permukiman);
    $this->actingAs($pengguna);

    $panen = $this->get(route('panen.index'))->assertOk();
    $penanaman = $this->get(route('penanaman'))->assertOk();

    expect($panen->viewData('totalTonSemua'))->toBe(KonversiPanen::keTon(10, $terlihat->satuan_id))
        ->and($panen->viewData('totalTonTampil'))->toBe($panen->viewData('totalTonSemua'))
        ->and($penanaman->viewData('statusPanen'))->toHaveKeys(
            $penanaman->viewData('baris')->getCollection()->pluck('id_penanaman')->all(),
        );
});
