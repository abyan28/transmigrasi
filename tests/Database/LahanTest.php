<?php

/*
 * Task 6.1-6.3 -- lahan (satu baris per keluarga, koordinat dua pasang,
 * komposisi luas usaha).
 *
 * Yang dijaga: `UNIQUE (lahan.transmigran_id)`, `luas_usaha` diturunkan dari
 * kering + basah, dan legalitas (SHM + status sertifikat) ditulis ke sisi
 * KELUARGA, bukan bidang (rules.md 7.5/7.6/7.8/7.9).
 */

use App\Enums\CakupanData;
use App\Models\Lahan;
use App\Models\Role;
use App\Models\Transmigran;
use App\Models\User;
use App\Support\DummyData;
use Database\Seeders\DaftarPilihanSeeder;
use Database\Seeders\KawasanSeeder;
use Database\Seeders\LahanSeeder;
use Database\Seeders\SpSeeder;
use Database\Seeders\TransmigranSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/DatabaseHelpers.php';

beforeEach(function () {
    $petugas = User::factory()->create();
    $petugas->semuaIzin = true;
    $this->actingAs($petugas);
    $this->seed(DaftarPilihanSeeder::class);
    $this->seed(WilayahSeeder::class);
    $this->seed(KawasanSeeder::class);
    $this->seed(SpSeeder::class);
    $this->seed(TransmigranSeeder::class);
    $this->seed(LahanSeeder::class);
});

it('menanam lahan satu baris per keluarga dari data contoh', function () {
    expect(Lahan::count())->toBe(count(DummyData::lahan()));

    $lh1 = Lahan::with('transmigran')->where('kode_lahan', 'LH-001')->first();
    expect($lh1->transmigran->nama_kepala_keluarga)->toBe('YOHANES BERE')
        ->and($lh1->uuid)->not->toBeNull()
        ->and((float) $lh1->luas_usaha)->toBe((float) (($lh1->luas_kering ?? 0) + ($lh1->luas_basah ?? 0)));
});

it('merender daftar lahan dan menyaring menurut bidang', function () {
    $this->get(route('lahan.index'))->assertOk()->assertSee('LH-001')->assertSee('LH-003');

    $this->get(route('lahan.index', ['peruntukan_lahan' => 'Lahan Pekarangan']))
        ->assertOk()->assertSee('LH-001')->assertDontSee('LH-003');
});

it('merender rincian lahan beserta legalitas keluarganya', function () {
    $lh = Lahan::where('kode_lahan', 'LH-002')->value('id_lahan');

    $this->get(route('lahan.detail', $lh))
        ->assertOk()
        ->assertSee('Sertifikat keluarga (SHM)')
        ->assertSee('Alas hak kawasan (HPL)')
        ->assertSee('Status sertifikat');
});

it('membalas 404 untuk lahan yang tidak ada', function () {
    $this->get('/lahan/99999')->assertNotFound();
});

it('mewajibkan kode lahan dan minimal satu bidang', function () {
    $kk = Transmigran::whereDoesntHave('lahan')->firstOrFail();

    $this->post(route('lahan.simpan'), [
        'transmigran_id' => $kk->id_transmigran,
        'satuan_permukiman_id' => $kk->satuan_permukiman_id,
        'status_sertifikat' => 'Belum',
    ])->assertSessionHasErrors(['kode_lahan', 'luas_pekarangan']);
});

it('menyimpan lahan baru dan menurunkan luas usaha dari kering + basah', function () {
    $kk = Transmigran::whereDoesntHave('lahan')->first();

    $this->post(route('lahan.simpan'), [
        'transmigran_id' => $kk->id_transmigran,
        'satuan_permukiman_id' => $kk->satuan_permukiman_id,
        'kode_lahan' => 'LH-999',
        'luas_pekarangan' => '0.25',
        'luas_kering' => '1.20',
        'luas_basah' => '0.80',
        'luas_usaha' => '999',  // dikirim form, WAJIB diabaikan
        'status_sertifikat' => 'Sudah',
    ])->assertRedirect(route('lahan.index'));

    $lahan = Lahan::where('kode_lahan', 'LH-999')->first();

    expect($lahan)->not->toBeNull()
        ->and((float) $lahan->luas_usaha)->toBe(2.0)
        ->and((float) $lahan->luas_kering)->toBe(1.2)
        ->and($kk->fresh()->status_sertifikat)->toBe('Sudah');
});

it('menolak KK yang sudah punya baris lahan', function () {
    $sudahPunya = Lahan::value('transmigran_id');
    $sp = Transmigran::find($sudahPunya)->satuan_permukiman_id;

    $this->post(route('lahan.simpan'), [
        'transmigran_id' => $sudahPunya,
        'satuan_permukiman_id' => $sp,
        'luas_kering' => '1.0',
        'luas_basah' => '0',
        'status_sertifikat' => 'Belum',
    ])->assertSessionHasErrors('transmigran_id');
});

it('mengunci pilihan SP lahan tanpa menghilangkan nilai kiriman', function () {
    $isi = $this->get(route('lahan.index'))->assertOk()->getContent();

    expect($isi)->toContain(':disabled="pemilikId !== \'\'"')
        ->and($isi)->toContain('type="hidden" name="satuan_permukiman_id"');
});

it('menurunkan SP lahan dari pemilik dan mengabaikan SP palsu', function () {
    $kk = Transmigran::whereDoesntHave('lahan')->first();
    $spPalsu = Transmigran::where('satuan_permukiman_id', '!=', $kk->satuan_permukiman_id)
        ->value('satuan_permukiman_id');

    $this->post(route('lahan.simpan'), [
        'transmigran_id' => $kk->id_transmigran,
        'satuan_permukiman_id' => $spPalsu,
        'kode_lahan' => 'LH-FORGE',
        'luas_pekarangan' => '0.25',
        'status_sertifikat' => 'Belum',
    ])->assertRedirect(route('lahan.index'));

    expect(Lahan::where('kode_lahan', 'LH-FORGE')->value('satuan_permukiman_id'))
        ->toBe($kk->satuan_permukiman_id);
});

it('membatasi penulisan lahan dan pemilik ke SP petugas', function () {
    $kkDiizinkan = Transmigran::whereDoesntHave('lahan')->first();
    $kkLain = Transmigran::whereDoesntHave('lahan')
        ->where('satuan_permukiman_id', '!=', $kkDiizinkan->satuan_permukiman_id)
        ->first();
    $role = Role::factory()->create(['cakupan_data' => CakupanData::PerSp->value]);
    $operator = User::factory()->create(['role_id' => $role->id_role]);
    $operator->semuaIzin = true;
    $operator->satuanPermukiman()->attach($kkDiizinkan->satuan_permukiman_id);
    $this->actingAs($operator);

    $this->post(route('lahan.simpan'), [
        'transmigran_id' => $kkLain->id_transmigran,
        'satuan_permukiman_id' => $kkDiizinkan->satuan_permukiman_id,
        'kode_lahan' => 'LH-DILARANG',
        'luas_pekarangan' => '0.25',
        'status_sertifikat' => 'Belum',
    ])->assertNotFound();

    $this->post(route('lahan.simpan'), [
        'transmigran_id' => $kkDiizinkan->id_transmigran,
        'satuan_permukiman_id' => $kkDiizinkan->satuan_permukiman_id,
        'kode_lahan' => 'LH-SCOPED',
        'luas_pekarangan' => '0.25',
        'status_sertifikat' => 'Belum',
    ])->assertRedirect(route('lahan.index'));

    $lahan = Lahan::where('kode_lahan', 'LH-SCOPED')->firstOrFail();
    $this->put(route('lahan.perbarui', $lahan->id_lahan), [
        'transmigran_id' => $kkLain->id_transmigran,
        'kode_lahan' => $lahan->kode_lahan,
        'luas_pekarangan' => $lahan->luas_pekarangan,
        'status_sertifikat' => 'Belum',
    ])->assertNotFound();

    // Hapus lintas-SP juga ditolak.
    $lahanLuar = Lahan::withoutGlobalScopes()
        ->where('satuan_permukiman_id', '!=', $kkDiizinkan->satuan_permukiman_id)
        ->firstOrFail();
    $this->delete(route('lahan.hapus', $lahanLuar->id_lahan))->assertNotFound();
    expect(Lahan::withoutGlobalScopes()->whereKey($lahanLuar->id_lahan)->whereNull('deleted_at')->exists())->toBeTrue();
});

it('menolak pemilik lahan yang sudah dihapus halus', function () {
    $kk = Transmigran::whereDoesntHave('lahan')->first();
    $kk->delete();

    $this->post(route('lahan.simpan'), [
        'transmigran_id' => $kk->id_transmigran,
        'satuan_permukiman_id' => $kk->satuan_permukiman_id,
        'status_sertifikat' => 'Belum',
    ])->assertSessionHasErrors('transmigran_id');
});

it('menyimpan lahan tanpa bidang usaha sebagai NULL, bukan nol', function () {
    $kk = Transmigran::whereDoesntHave('lahan')->first();

    $this->post(route('lahan.simpan'), [
        'transmigran_id' => $kk->id_transmigran,
        'satuan_permukiman_id' => $kk->satuan_permukiman_id,
        'kode_lahan' => 'LH-KOSONG',
        'luas_pekarangan' => '0.30',
        'status_sertifikat' => 'Belum Didata',
    ])->assertRedirect(route('lahan.index'));

    $lahan = Lahan::where('kode_lahan', 'LH-KOSONG')->first();

    expect($lahan->luas_usaha)->toBeNull()
        ->and($lahan->luas_kering)->toBeNull()
        ->and($lahan->luas_basah)->toBeNull()
        ->and((float) $lahan->luas_pekarangan)->toBe(0.3);
});

it('memperbarui lahan dan menulis SHM ke transmigran_berkas peran shm', function () {
    Storage::fake('local');

    $lahan = Lahan::with('transmigran')->where('kode_lahan', 'LH-003')->first();

    $this->put(route('lahan.perbarui', $lahan->id_lahan), [
        'transmigran_id' => $lahan->transmigran_id,
        'satuan_permukiman_id' => $lahan->satuan_permukiman_id,
        'kode_lahan' => $lahan->kode_lahan,
        'luas_kering' => '1.25',
        'luas_basah' => '0',
        'status_sertifikat' => 'Sudah',
        'shm' => UploadedFile::fake()->create('shm.pdf', 60, 'application/pdf'),
    ])->assertRedirect(route('lahan.detail', $lahan->id_lahan));

    $kk = $lahan->transmigran->fresh();
    expect($kk->status_sertifikat)->toBe('Sudah')
        ->and($kk->berkas()->wherePivot('peran', 'shm')->count())->toBe(1);
});

it('menghapus lahan secara halus', function () {
    $id = Lahan::where('kode_lahan', 'LH-004')->value('id_lahan');

    $this->delete(route('lahan.hapus', $id))->assertRedirect(route('lahan.index'));

    expect(Lahan::find($id))->toBeNull()
        ->and(Lahan::withTrashed()->find($id)->trashed())->toBeTrue();
});
