<?php

use App\Enums\CakupanData;
use App\Models\FasilitasSp;
use App\Models\HasilPanen;
use App\Models\Infrastruktur;
use App\Models\InventarisSp;
use App\Models\Lahan;
use App\Models\Pengaduan;
use App\Models\Poktan;
use App\Models\Role;
use App\Models\Rumah;
use App\Models\RuteAksesibilitasSp;
use App\Models\SatuanPermukiman;
use App\Models\Transmigran;
use App\Models\User;
use Illuminate\Support\Str;

it('merender rincian dan sebaran SP dari data tersimpan termasuk SP baru', function () {
    $sp = SatuanPermukiman::findOrFail(1);
    Transmigran::where('satuan_permukiman_id', 1)->firstOrFail()->update(['nama_kepala_keluarga' => 'KK DATA NYATA']);
    Rumah::where('satuan_permukiman_id', 1)->firstOrFail()->update(['no_rumah' => 'RUMAH DATA NYATA']);
    Lahan::where('satuan_permukiman_id', 1)->firstOrFail()->update(['kode_lahan' => 'LAHAN DATA NYATA']);
    Poktan::where('satuan_permukiman_id', 1)->firstOrFail()->update(['nama' => 'POKTAN DATA NYATA']);
    HasilPanen::whereHas('penanaman.poktan', fn ($q) => $q->where('satuan_permukiman_id', 1))
        ->firstOrFail()->update(['produksi' => 9.876]);
    Pengaduan::where('satuan_permukiman_id', 1)->firstOrFail()->update(['judul' => 'PENGADUAN DATA NYATA']);
    Infrastruktur::findOrFail(1)->update(['nama' => 'INFRASTRUKTUR DATA NYATA']);
    FasilitasSp::where('satuan_permukiman_id', 1)->firstOrFail()->update(['nama_fasilitas' => 'FASILITAS DATA NYATA']);
    InventarisSp::where('satuan_permukiman_id', 1)->firstOrFail()->update(['nama_barang' => 'INVENTARIS DATA NYATA']);
    RuteAksesibilitasSp::create([
        'satuan_permukiman_id' => 1,
        'rute' => 'RUTE DATA NYATA',
    ]);

    $this->get(route('sp.detail', $sp->id_satuan_permukiman))
        ->assertOk()
        ->assertSee('KK DATA NYATA')
        ->assertSee('RUMAH DATA NYATA')
        ->assertSee('LAHAN DATA NYATA')
        ->assertSee('POKTAN DATA NYATA')
        ->assertSee('9,876')
        ->assertSee('PENGADUAN DATA NYATA')
        ->assertSee('INFRASTRUKTUR DATA NYATA')
        ->assertSee('FASILITAS DATA NYATA')
        ->assertSee('INVENTARIS DATA NYATA')
        ->assertSee('RUTE DATA NYATA');

    $this->get(route('sp.detail', 2))
        ->assertOk()
        ->assertSee('INFRASTRUKTUR DATA NYATA')
        ->assertSee('PUSKESMAS PEMBANTU');

    $baru = SatuanPermukiman::create([
        'kawasan_id' => $sp->kawasan_id,
        'desa_id' => $sp->desa_id,
        'nama' => 'SP UJI RUNTIME',
        'kode_sp' => 'SP-RUNTIME',
        'jumlah_kk_rencana' => 3,
    ]);
    Transmigran::create([
        'uuid' => (string) Str::uuid(),
        'satuan_permukiman_id' => $baru->id_satuan_permukiman,
        'nik' => '5321010101010199',
        'no_kk' => '5321010101010299',
        'nama_kepala_keluarga' => 'KK SP UJI RUNTIME',
        'pekerjaan_kepala_keluarga' => 'PETANI',
        'tahun_kedatangan' => 2026,
        'status_tinggal' => 'Aktif',
        'status_anggota_poktan' => 'Tidak',
    ]);

    $this->get(route('sp.index'))
        ->assertOk()
        ->assertSee('SP UJI RUNTIME')
        ->assertSee('1 / 3');

    $this->get(route('sp.detail', $baru->id_satuan_permukiman))
        ->assertOk()
        ->assertSee('SP UJI RUNTIME')
        ->assertSee('KK SP UJI RUNTIME');

    $this->get(route('kawasan'))
        ->assertOk()
        ->assertSee('7 SP tersebar di 4 kecamatan berbeda')
        ->assertSee('SP UJI RUNTIME');
});

it('menghormati cakupan data pada rincian dan hitungan SP', function () {
    $spTerlihat = SatuanPermukiman::findOrFail(1);
    $spTertutup = SatuanPermukiman::findOrFail(2);
    $jumlahTerlihat = Transmigran::where('satuan_permukiman_id', $spTerlihat->id_satuan_permukiman)
        ->where('status_tinggal', 'Aktif')
        ->count();
    $role = Role::factory()->create(['cakupan_data' => CakupanData::PerSp->value]);
    $pengguna = User::factory()->create(['role_id' => $role->id_role]);
    $pengguna->semuaIzin = true;
    $pengguna->satuanPermukiman()->attach($spTerlihat->id_satuan_permukiman);
    $this->actingAs($pengguna);

    $rencanaTerlihat = (int) SatuanPermukiman::whereKey($spTerlihat->id_satuan_permukiman)->value('jumlah_kk_rencana');

    // Daftar /sp disaring: hanya SP yang ditugaskan yang tampil, dan seluruh
    // ringkasan (Daya Tampung, Sudah Terisi) dihitung dari SP itu saja.
    $daftar = $this->get(route('sp.index'))
        ->assertOk()
        ->assertSee($jumlahTerlihat.' / '.$rencanaTerlihat)
        ->assertSee('Sudah Terisi')
        ->assertDontSee($spTertutup->nama);
    expect($daftar->viewData('totalTerisi'))->toBe($jumlahTerlihat)
        ->and($daftar->viewData('totalRencana'))->toBe($rencanaTerlihat)
        ->and($daftar->viewData('jumlahSp'))->toBe(1);

    // Rincian SP di luar cakupan: 404, bukan halaman kosong. Blok Keadaan
    // Wilayah (koordinat, iklim, tanah, Monografi) tidak boleh bocor.
    $this->get(route('sp.detail', $spTertutup->id_satuan_permukiman))
        ->assertNotFound();

    // /kawasan sengaja kawasan-lebar: seluruh SP tetap tampak, tetapi KK-nya 0.
    $kawasan = $this->get(route('kawasan'))
        ->assertOk()
        ->assertSee($spTertutup->nama)
        ->assertSee('0 KK');
    expect(collect($kawasan->viewData('daftarSp'))->firstWhere('id_satuan_permukiman', $spTertutup->id_satuan_permukiman)['jumlah_kk'])
        ->toBe(0);
});

it('membuka rincian SP yang ditugaskan bagi akun Per SP tanpa terhalang', function () {
    $spTerlihat = SatuanPermukiman::findOrFail(1);
    $namaTerlihat = Transmigran::where('satuan_permukiman_id', $spTerlihat->id_satuan_permukiman)
        ->firstOrFail()->nama_kepala_keluarga;
    $role = Role::factory()->create(['cakupan_data' => CakupanData::PerSp->value]);
    $pengguna = User::factory()->create(['role_id' => $role->id_role]);
    $pengguna->semuaIzin = true;
    $pengguna->satuanPermukiman()->attach($spTerlihat->id_satuan_permukiman);
    $this->actingAs($pengguna);

    $rincian = $this->get(route('sp.detail', $spTerlihat->id_satuan_permukiman))
        ->assertOk()
        ->assertSee($spTerlihat->nama)
        ->assertSee($namaTerlihat);
    expect($rincian->viewData('transmigran'))->not->toBe([]);
});
