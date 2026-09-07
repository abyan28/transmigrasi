<?php

use App\Enums\CakupanData;
use App\Models\Role;
use App\Models\Rumah;
use App\Models\SatuanPermukiman;
use App\Models\User;
use App\Support\RekapDashboard;
use Illuminate\Support\Str;

it('menyamakan penduduk transmigran dengan populasi aktif dashboard', function () {
    $respons = $this->get(route('transmigran.index'))->assertOk();
    $aktif = RekapDashboard::jumlahKkJiwa();

    expect($respons->viewData('totalAktif'))->toBe($aktif['jumlah_kk'])
        ->and($respons->viewData('totalJiwa'))->toBe($aktif['jumlah_jiwa']);

    $respons->assertSee('Penduduk Aktif')
        ->assertSee('~'.number_format($aktif['jumlah_jiwa'] / $aktif['jumlah_kk'], 1, ',', '.').' jiwa / KK');
});

it('menghitung persentase rumah terhuni terhadap total rumah di SP', function () {
    $sp = SatuanPermukiman::query()->whereHas('rumah')->firstOrFail();

    Rumah::query()->create([
        'uuid' => (string) Str::uuid(),
        'satuan_permukiman_id' => $sp->id_satuan_permukiman,
        'no_rumah' => 'KOSONG-UJI-RASIO',
        'kondisi' => 'Tidak Rusak',
        'status_hunian' => 'Tidak Dihuni',
    ]);

    $respons = $this->get(route('sp.detail', $sp->id_satuan_permukiman))->assertOk();
    $ringkasan = RekapDashboard::ringkasan($sp->id_satuan_permukiman);
    $expected = $ringkasan['rumah_total'] > 0
        ? round($ringkasan['rumah_terhuni'] / $ringkasan['rumah_total'] * 100)
        : 0;

    expect($respons->viewData('persenHuni'))->toBe($expected);
});

it('menggunakan vocabulary baku kondisi rumah pada agregat kerusakan', function () {
    $sumber = file_get_contents(app_path('Http/Controllers/RumahController.php'));

    expect($sumber)
        ->toContain('KondisiRumah::TidakRusak->value')
        ->not->toContain("whereNot('kondisi', 'Tidak Rusak')");
});

it('membedakan jumlah record aset dari total unitnya', function () {
    $fasilitas = file_get_contents(resource_path('views/pages/sp/fasilitas.blade.php'));
    $inventaris = file_get_contents(resource_path('views/pages/sp/inventaris.blade.php'));

    expect($fasilitas)
        ->toContain('label="Data Fasilitas"')
        ->toContain('label="Kondisi Baik" :nilai="$kondisiBaik" satuan="fasilitas"')
        ->toContain('label="Perlu Perbaikan" :nilai="$rusak" satuan="fasilitas"')
        ->and($inventaris)
        ->toContain('label="Jenis Barang" :nilai="$jenisBarang" satuan="jenis"')
        ->toContain('label="Perlu Perhatian" :nilai="$perluPerhatian" satuan="jenis"');
});

it('menampilkan tahun acuan pada metric produksi', function () {
    $tahun = RekapDashboard::tahunTerakhir();

    $this->get(route('komoditas.index'))
        ->assertOk()
        ->assertViewHas('tahunPanen', $tahun)
        ->assertSee('Produksi Tahun '.$tahun);

    $sp = SatuanPermukiman::query()->firstOrFail();
    $this->get(route('sp.detail', $sp->id_satuan_permukiman))
        ->assertOk()
        ->assertViewHas('tahunPanen', $tahun)
        ->assertSee('Tahun '.$tahun);

    $this->get(route('galeri-komponen'))
        ->assertOk()
        ->assertViewHas('tahunPanen', $tahun)
        ->assertSee('Tahun '.$tahun);
});

it('tidak menghardcode rentang SP atau contoh kategori pada metric', function () {
    $berkas = [
        resource_path('views/pages/transmigran/index.blade.php'),
        resource_path('views/pages/penanaman/index.blade.php'),
        resource_path('views/pages/panen/index.blade.php'),
    ];

    foreach ($berkas as $path) {
        $isi = file_get_contents($path);
        expect($isi)
            ->not->toContain('SP 1 sampai SP 4')
            ->not->toContain('Padi, jagung, palawija dll')
            ->not->toContain('Pangan & perkebunan')
            ->not->toContain('Musim tanam terdata');
    }
});

it('menjelaskan cakupan ringkasan yang tidak berubah oleh filter tabel', function () {
    $pesan = 'Seluruh data dalam cakupan akses; filter tabel tidak mengubah ringkasan.';

    $this->get(route('transmigran.index', ['sp' => 1]))
        ->assertOk()
        ->assertSee($pesan);

    $this->get(route('komoditas.index', ['tipe' => 'Pangan']))
        ->assertOk()
        ->assertSee($pesan);
});

it('membatasi pilihan SP galeri menurut cakupan pengguna', function () {
    $sp = SatuanPermukiman::query()->orderBy('id_satuan_permukiman')->get();
    $role = Role::factory()->create(['cakupan_data' => CakupanData::PerSp->value]);
    $pengguna = User::factory()->create(['role_id' => $role->id_role]);
    $pengguna->semuaIzin = true;
    $pengguna->satuanPermukiman()->attach($sp[0]->id_satuan_permukiman);
    $this->actingAs($pengguna);

    $respons = $this->get(route('galeri-komponen'))->assertOk();

    expect(collect($respons->viewData('daftarSp'))->pluck('id_satuan_permukiman')->all())
        ->toBe([$sp[0]->id_satuan_permukiman]);
});
