<?php

/*
 * Task 5.3 (CRUD rumah) + Task 5.4 (riwayat penghunian).
 *
 * Yang dijaga: relasi rumah <-> KK satu-ke-satu ditegakkan basis data, dan
 * pergantian penghuni menutup baris riwayat lama lalu membuka yang baru tanpa
 * menimpa (rules.md 6a.5/6a.6/6a.9).
 */

use App\Models\RiwayatPenghunian;
use App\Models\Rumah;
use App\Models\Transmigran;
use App\Models\User;
use App\Support\DummyData;
use Database\Seeders\DaftarPilihanSeeder;
use Database\Seeders\KawasanSeeder;
use Database\Seeders\RumahSeeder;
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
    $this->seed(WilayahSeeder::class);
    $this->seed(KawasanSeeder::class);
    $this->seed(SpSeeder::class);
    $this->seed(DaftarPilihanSeeder::class);
    $this->seed(TransmigranSeeder::class);
    $this->seed(RumahSeeder::class);
});

it('menanam rumah dan riwayat penghunian dari data contoh', function () {
    expect(Rumah::count())->toBe(count(DummyData::rumah()))
        ->and(RiwayatPenghunian::count())->toBe(count(DummyData::riwayatPenghunian()));

    $a01 = Rumah::with('penghuni')->where('no_rumah', 'A-01')->first();
    expect($a01->penghuni->nama_kepala_keluarga)->toBe('YOHANES BERE')
        ->and($a01->uuid)->not->toBeNull();
});

it('merender daftar rumah dari basis data', function () {
    $respons = $this->get(route('rumah.index'))->assertOk();

    foreach (DummyData::rumah() as $r) {
        $respons->assertSee($r['no_rumah']);
    }
});

it('menyaring daftar rumah menurut status hunian', function () {
    $respons = $this->get(route('rumah.index', ['status_hunian' => 'Tidak Dihuni']))
        ->assertOk()
        ->assertSee('A-03');

    // Baris terhuni tersaring keluar dari tabel (dicek lewat viewData, sebab
    // nomor rumah lain bisa muncul di tempat lain pada markup).
    expect(collect($respons->viewData('baris'))->pluck('no_rumah'))
        ->not->toContain('A-01');
});

it('merender rincian rumah beserta riwayat penghuniannya', function () {
    $a03 = Rumah::where('no_rumah', 'A-03')->value('id_rumah');

    $this->get(route('rumah.detail', $a03))
        ->assertOk()
        ->assertSee('DOMINGGUS TAEK')
        ->assertSee('Pindah mengikuti keluarga ke SP Weoe.')
        ->assertSee('Sudah keluar');
});

it('membalas 404 untuk rumah yang tidak ada', function () {
    $this->get('/rumah/99999')->assertNotFound();
});

it('menyimpan rumah baru dan membuka riwayat penghunian', function () {
    $kk = Transmigran::whereDoesntHave('rumah')->first();

    $this->post(route('rumah.simpan'), [
        'satuan_permukiman_id' => $kk->satuan_permukiman_id,
        'transmigran_id' => $kk->id_transmigran,
        'no_rumah' => 'Z-99',
        'kondisi' => 'Tidak Rusak',
        'status_hunian' => 'Dihuni',
    ])->assertRedirect(route('rumah.index'));

    $rumah = Rumah::where('no_rumah', 'Z-99')->first();

    expect($rumah)->not->toBeNull()
        ->and($rumah->transmigran_id)->toBe($kk->id_transmigran)
        ->and($rumah->riwayatPenghunian()->whereNull('tanggal_keluar')->count())->toBe(1);
});

it('menolak penghuni yang sudah menempati rumah lain', function () {
    $sudahPunya = Rumah::whereNotNull('transmigran_id')->value('transmigran_id');
    $spKosong = Transmigran::whereDoesntHave('rumah')->value('satuan_permukiman_id');

    $this->post(route('rumah.simpan'), [
        'satuan_permukiman_id' => $spKosong,
        'transmigran_id' => $sudahPunya,
        'kondisi' => 'Tidak Rusak',
        'status_hunian' => 'Dihuni',
    ])->assertSessionHasErrors('transmigran_id');
});

it('mewajibkan alasan saat rumah tidak dihuni', function () {
    $spKosong = Transmigran::whereDoesntHave('rumah')->value('satuan_permukiman_id');

    $this->post(route('rumah.simpan'), [
        'satuan_permukiman_id' => $spKosong,
        'kondisi' => 'Rusak Berat',
        'status_hunian' => 'Tidak Dihuni',
    ])->assertSessionHasErrors('alasan_tidak_dihuni');
});

it('mencatat pergantian penghuni sebagai riwayat baru tanpa menimpa yang lama', function () {
    $rumah = Rumah::with('penghuni')->where('no_rumah', 'A-01')->first();
    $lama = $rumah->transmigran_id;
    $penggantiId = Transmigran::whereDoesntHave('rumah')->value('id_transmigran');

    $this->put(route('rumah.perbarui', $rumah->id_rumah), [
        'satuan_permukiman_id' => $rumah->satuan_permukiman_id,
        'transmigran_id' => $penggantiId,
        'no_rumah' => $rumah->no_rumah,
        'kondisi' => 'Tidak Rusak',
        'status_hunian' => 'Dihuni',
        'alasan_keluar' => 'Pindah ke luar kawasan.',
    ])->assertRedirect(route('rumah.detail', $rumah->id_rumah));

    $rumah->refresh();
    expect($rumah->transmigran_id)->toBe($penggantiId);

    // Baris lama TETAP ADA, hanya ditutup dengan alasan.
    $riwayatLama = RiwayatPenghunian::where('rumah_id', $rumah->id_rumah)
        ->where('transmigran_id', $lama)->first();
    expect($riwayatLama)->not->toBeNull()
        ->and($riwayatLama->tanggal_keluar)->not->toBeNull()
        ->and($riwayatLama->alasan_keluar)->toBe('Pindah ke luar kawasan.');

    // Baris baru terbuka untuk pengganti.
    expect(RiwayatPenghunian::where('rumah_id', $rumah->id_rumah)
        ->where('transmigran_id', $penggantiId)->whereNull('tanggal_keluar')->count())->toBe(1);
});

it('mengosongkan rumah menutup riwayat tanpa membuka baris baru', function () {
    $rumah = Rumah::where('no_rumah', 'A-02')->first();

    $sebelum = RiwayatPenghunian::where('rumah_id', $rumah->id_rumah)->count();

    $this->put(route('rumah.perbarui', $rumah->id_rumah), [
        'satuan_permukiman_id' => $rumah->satuan_permukiman_id,
        'no_rumah' => $rumah->no_rumah,
        'kondisi' => 'Rusak Ringan',
        'status_hunian' => 'Tidak Dihuni',
        'alasan_tidak_dihuni' => 'Keluarga pindah, menunggu penempatan baru.',
        'alasan_keluar' => 'Keluarga pindah ke SP lain.',
    ])->assertRedirect(route('rumah.detail', $rumah->id_rumah));

    $rumah->refresh();
    expect($rumah->transmigran_id)->toBeNull()
        ->and(RiwayatPenghunian::where('rumah_id', $rumah->id_rumah)->count())->toBe($sebelum)
        ->and(RiwayatPenghunian::where('rumah_id', $rumah->id_rumah)->whereNull('tanggal_keluar')->count())->toBe(0);
});

it('menghapus rumah secara halus', function () {
    $id = Rumah::where('no_rumah', 'C-02')->value('id_rumah');

    $this->delete(route('rumah.hapus', $id))->assertRedirect(route('rumah.index'));

    expect(Rumah::find($id))->toBeNull()
        ->and(Rumah::withTrashed()->find($id)->trashed())->toBeTrue();
});

it('mengunggah foto rumah sebagai berkas berperan foto', function () {
    Storage::fake('local');

    $rumah = Rumah::where('no_rumah', 'C-02')->first();

    $this->put(route('rumah.perbarui', $rumah->id_rumah), [
        'satuan_permukiman_id' => $rumah->satuan_permukiman_id,
        'no_rumah' => $rumah->no_rumah,
        'kondisi' => 'Tidak Rusak',
        'status_hunian' => 'Tidak Dihuni',
        'alasan_tidak_dihuni' => 'Belum ada penempatan.',
        'foto_rumah' => [
            UploadedFile::fake()->image('depan.jpg'),
            UploadedFile::fake()->image('atap.jpg'),
        ],
    ])->assertRedirect(route('rumah.detail', $rumah->id_rumah));

    expect($rumah->berkas()->wherePivot('peran', 'foto')->count())->toBe(2);
});
