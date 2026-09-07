<?php

/*
 * Task 4.2 -- CRUD satuan permukiman, INDUK inventaris/fasilitas/infrastruktur.
 */

use App\Models\Berkas;
use App\Models\RuteAksesibilitasSp;
use App\Models\SatuanPermukiman;
use App\Models\User;
use App\Support\DummyData;
use Database\Seeders\DaftarPilihanSeeder;
use Database\Seeders\KawasanSeeder;
use Database\Seeders\SpSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/DatabaseHelpers.php';

beforeEach(function () {
    $petugas = User::factory()->create();
    $petugas->semuaIzin = true;
    $this->actingAs($petugas);
    $this->seed(WilayahSeeder::class);
    $this->seed(KawasanSeeder::class);
    $this->seed(DaftarPilihanSeeder::class);
    $this->seed(SpSeeder::class);
});

function dataSpLengkap(array $ubah = []): array
{
    return array_replace([
        'nama' => 'SP UJI LENGKAP',
        'kode_sp' => 'SP-98',
        'kawasan_id' => 1,
        'desa_id' => 1,
        'tahun_penempatan' => 2022,
        'luas_lahan' => '321.45',
        'jumlah_kk_rencana' => 77,
        'lintang' => '-9.1234567',
        'bujur' => '124.1234567',
        'keterangan' => 'Catatan lengkap.',
        'lintang_utara' => '-9.1000000',
        'lintang_selatan' => '-9.2000000',
        'bujur_barat' => '124.1000000',
        'bujur_timur' => '124.2000000',
        'jarak_ke_kecamatan_km' => '2.3',
        'jarak_ke_kabupaten_km' => '20.4',
        'jarak_ke_provinsi_km' => '245.5',
        'batas_utara' => 'DESA UTARA',
        'batas_timur' => 'SUNGAI TIMUR',
        'batas_selatan' => 'DESA SELATAN',
        'batas_barat' => 'HUTAN BARAT',
        'nomor_sk_pencadangan' => '12/SK/2020',
        'tanggal_sk_pencadangan' => '2020-01-02',
        'pola_permukiman' => 'Linear',
        'tingkat_kesuburan_tanah' => 'Sedang',
        'ph_tanah_min' => '5.50',
        'ph_tanah_maks' => '7.25',
        'bentuk_wilayah' => 'Bergelombang',
        'kemiringan_min_persen' => '2.50',
        'kemiringan_maks_persen' => '12.75',
        'curah_hujan_tahunan_mm' => '1607.18',
        'curah_hujan_bulan_min_mm' => '100.25',
        'curah_hujan_bulan_maks_mm' => '200.75',
        'suhu_min_c' => '23.1',
        'suhu_maks_c' => '31.2',
        'suhu_rata_c' => '27.7',
        'angin_min_knot' => '4.1',
        'angin_maks_knot' => '4.6',
        'angin_rata_knot' => '4.3',
        'penyinaran_min_persen' => '45.10',
        'penyinaran_maks_persen' => '74.40',
        'penyinaran_rata_persen' => '55.60',
        'sumber_air_bersih' => 'MATA AIR',
        'sumber_air_pertanian' => 'EMBUNG',
        '_rute_disunting' => '1',
        'rute_aksesibilitas' => [[
            'rute' => 'KUPANG KE SP',
            'jarak_km' => '245.5',
            'sarana_angkutan' => 'ANGKUTAN DARAT',
            'tempat_pemberangkatan' => 'TERMINAL KUPANG',
            'kondisi_jalan' => 'BAIK',
            'waktu_tempuh' => '6 JAM',
            'ongkos_rp' => '125000',
            'keterangan' => 'Rute utama.',
        ]],
    ], $ubah);
}

it('menanam seluruh kolom dan rute SP dari fixture', function () {
    expect(SatuanPermukiman::count())->toBe(6)
        ->and(RuteAksesibilitasSp::count())->toBe(count(DummyData::ruteAksesibilitasSp()));

    foreach (DummyData::satuanPermukiman() as $fixture) {
        $sp = SatuanPermukiman::findOrFail($fixture['id_satuan_permukiman']);

        foreach (array_intersect((new SatuanPermukiman)->getFillable(), array_keys($fixture)) as $kolom) {
            if ($kolom !== 'berkas_id' || $fixture[$kolom] === null || Berkas::whereKey($fixture[$kolom])->exists()) {
                expect($sp->getRawOriginal($kolom))->toEqual($fixture[$kolom]);
            }
        }
    }

    $pertama = SatuanPermukiman::where('kode_sp', 'SP-01')->firstOrFail();

    expect($pertama->slug)->toBe('sp-kapitan-meo')
        ->and($pertama->desa?->nama)->toBe('Kapitan Meo')
        ->and($pertama->desa?->kecamatan?->nama)->toBe('Laen Manen')
        ->and($pertama->batas_utara)->toBe('Desa Tesa')
        ->and((float) $pertama->curah_hujan_tahunan_mm)->toBe(1607.18)
        ->and($pertama->berkas?->nama_file)->toBe('sk-penempatan-kapitan-meo.pdf');
});

it('menyimpan seluruh isian SP dan rute sampai dapat dibaca kembali', function () {
    $data = dataSpLengkap();

    $this->post(route('sp.simpan'), $data)->assertRedirect(route('sp.index'));

    $sp = SatuanPermukiman::where('kode_sp', 'SP-98')->firstOrFail();
    $tersimpan = collect($data)->only($sp->getFillable());
    foreach ($tersimpan as $kolom => $nilai) {
        is_numeric($nilai)
            ? expect((float) $sp->getRawOriginal($kolom))->toBe((float) $nilai, $kolom)
            : expect($sp->getRawOriginal($kolom))->toBe($nilai, $kolom);
    }
    $this->assertDatabaseHas('rute_aksesibilitas_sp', [
        'satuan_permukiman_id' => $sp->id_satuan_permukiman,
        ...$data['rute_aksesibilitas'][0],
    ]);

    $this->get(route('sp.detail', $sp->id_satuan_permukiman))
        ->assertOk()
        ->assertSee('HUTAN BARAT')
        ->assertSee('KUPANG KE SP');
});

it('menyinkronkan rute hanya dari form edit eksplisit', function () {
    $sp = SatuanPermukiman::findOrFail(1);
    $awal = $sp->ruteAksesibilitas()->pluck('rute')->all();
    $dasar = dataSpLengkap(['nama' => $sp->nama, 'kode_sp' => $sp->kode_sp]);

    $cepat = $dasar;
    unset($cepat['_rute_disunting'], $cepat['rute_aksesibilitas']);
    $this->put(route('sp.perbarui', $sp->id_satuan_permukiman), $cepat)->assertSessionHasNoErrors();
    expect($sp->ruteAksesibilitas()->pluck('rute')->all())->toBe($awal);

    $this->put(route('sp.perbarui', $sp->id_satuan_permukiman), $dasar)->assertSessionHasNoErrors();
    expect($sp->ruteAksesibilitas()->pluck('rute')->all())->toBe(['KUPANG KE SP']);
});

it('tidak mengubah SP atau rute ketika rute kiriman tidak sah', function () {
    $sp = SatuanPermukiman::findOrFail(1);
    $nama = $sp->nama;
    $rute = $sp->ruteAksesibilitas()->pluck('rute')->all();
    $data = dataSpLengkap([
        'nama' => 'SP YANG TIDAK BOLEH TERSIMPAN',
        'kode_sp' => $sp->kode_sp,
        'rute_aksesibilitas' => [['rute' => '', 'jarak_km' => -1]],
    ]);

    $this->put(route('sp.perbarui', $sp->id_satuan_permukiman), $data)
        ->assertSessionHasErrors(['rute_aksesibilitas.0.rute', 'rute_aksesibilitas.0.jarak_km']);

    expect($sp->fresh()->nama)->toBe($nama)
        ->and($sp->ruteAksesibilitas()->pluck('rute')->all())->toBe($rute);
});

it('menyimpan dokumen SP melalui FK berkas pada cakram privat', function () {
    Storage::fake('local');

    $this->post(route('sp.simpan'), dataSpLengkap([
        'nama' => 'SP BERDOKUMEN',
        'kode_sp' => 'SP-97',
        'dokumen_pendukung' => UploadedFile::fake()->create('penetapan.pdf', 100, 'application/pdf'),
    ]))->assertSessionHasNoErrors();

    $sp = SatuanPermukiman::where('kode_sp', 'SP-97')->firstOrFail();
    $berkas = $sp->berkas;

    expect($berkas)->toBeInstanceOf(Berkas::class)
        ->and($berkas->disk)->toBe('local')
        ->and($berkas->path)->not->toContain('public');
    Storage::disk('local')->assertExists($berkas->path);
});

it('menolak kode SP yang sudah dipakai SP lain', function () {
    $this->post(route('sp.simpan'), [
        'nama' => 'SP KEMBAR',
        'kode_sp' => 'SP-01',
        'kawasan_id' => 1,
        'desa_id' => 1,
    ])->assertSessionHasErrors('kode_sp');
});

it('menolak rentang keadaan wilayah yang terbalik', function (string $min, string $maks) {
    // Rentang terbalik (mis. curah hujan 3000-500) lolos diam-diam lalu
    // terbaca sebagai rentang kosong pada Laporan Monografi SP.
    $this->post(route('sp.simpan'), [
        'nama' => 'SP RENTANG TERBALIK',
        'kawasan_id' => 1,
        'desa_id' => 1,
        'curah_hujan_bulan_min_mm' => $min,
        'curah_hujan_bulan_maks_mm' => $maks,
    ])->assertSessionHasErrors('curah_hujan_bulan_maks_mm');

    expect(SatuanPermukiman::where('nama', 'SP RENTANG TERBALIK')->exists())->toBeFalse();
})->with([['3000', '500'], ['100', '99']]);

it('menerima rentang yang sah termasuk nilai min sama dengan maks', function () {
    $this->post(route('sp.simpan'), [
        'nama' => 'SP RENTANG SAH',
        'kawasan_id' => 1,
        'desa_id' => 1,
        'curah_hujan_bulan_min_mm' => '100',
        'curah_hujan_bulan_maks_mm' => '100',
        'suhu_min_c' => '22',
        'suhu_maks_c' => '34',
        'ph_tanah_min' => '5.5',
        'ph_tanah_maks' => '7',
    ])->assertSessionHasNoErrors();

    expect(SatuanPermukiman::where('nama', 'SP RENTANG SAH')->exists())->toBeTrue();
});

it('menolak menghapus SP yang masih menaungi data turunan', function () {
    $sp = SatuanPermukiman::find(1);

    buatTransmigran($sp);

    $this->from(route('sp.index'))
        ->delete(route('sp.hapus', $sp->id_satuan_permukiman))
        ->assertRedirect(route('sp.index'))
        ->assertSessionHas('galat');

    expect(SatuanPermukiman::find(1))->not->toBeNull();
});

it('menghapus SP yang belum menaungi apa pun', function () {
    $sp = SatuanPermukiman::where('kode_sp', 'SP-06')->first();

    $this->delete(route('sp.hapus', $sp->id_satuan_permukiman))
        ->assertRedirect(route('sp.index'));

    expect(SatuanPermukiman::count())->toBe(5);
});
