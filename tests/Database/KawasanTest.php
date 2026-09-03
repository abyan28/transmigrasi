<?php

/*
 * Task 4.1b -- CRUD kawasan transmigrasi + unggah berkas alas hak.
 *
 * Berumah di grup Database sebab menyentuh FK RESTRICT ke `satuan_permukiman`,
 * UNIQUE `nama`, dan pivot `kawasan_transmigrasi_berkas`.
 */

use App\Models\Berkas;
use App\Models\KawasanTransmigrasi;
use App\Models\User;
use Database\Seeders\KawasanSeeder;
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
});

it('menanam kawasan lokus beserta slug otomatisnya', function () {
    $kawasan = KawasanTransmigrasi::find(1);

    expect($kawasan->nama)->toBe('Kobalima Timur')
        ->and($kawasan->slug)->toBe('kobalima-timur')
        ->and($kawasan->kabupaten_id)->toBe(5321);
});

it('menyimpan kawasan baru', function () {
    $this->post(route('kawasan.simpan'), [
        'nama' => 'KAWASAN UJI',
        'kabupaten_id' => 5321,
        'tahun_penetapan' => 2020,
        'luas_total' => '1500.5',
    ])->assertRedirect(route('kawasan'));

    expect(KawasanTransmigrasi::where('nama', 'KAWASAN UJI')->exists())->toBeTrue();
});

it('menolak kabupaten yang tidak terdaftar', function () {
    $this->post(route('kawasan.simpan'), [
        'nama' => 'KAWASAN SESAT',
        'kabupaten_id' => 999999,
    ])->assertSessionHasErrors('kabupaten_id');
});

it('menyimpan unggahan ke cakram privat dan melekatkannya lewat pivot', function () {
    // Unggahan sungguhan PERTAMA di proyek ini. Yang dijaga: berkas fisik
    // masuk cakram privat (BUKAN public/), registry erkas terisi, dan
    // pivotnya menautkan keduanya beserta peran.
    Storage::fake('local');

    $this->post(route('kawasan.simpan'), [
        'nama' => 'KAWASAN BERBERKAS',
        'kabupaten_id' => 5321,
        'dokumen_kawasan' => [
            UploadedFile::fake()->create('sk-penetapan.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('peta.pdf', 80, 'application/pdf'),
        ],
    ])->assertRedirect(route('kawasan'));

    $kawasan = KawasanTransmigrasi::where('nama', 'KAWASAN BERBERKAS')->first();

    expect($kawasan->berkas)->toHaveCount(2);

    foreach ($kawasan->berkas as $b) {
        Storage::disk('local')->assertExists($b->path);

        expect($b->uuid)->not->toBeNull()
            ->and($b->disk)->toBe('local')
            ->and($b->pivot->peran)->toBe('sk')
            // Path TIDAK boleh menyentuh folder publik.
            ->and($b->path)->not->toContain('public');
    }

    // Urutan dinomori berurutan supaya berkas utama dapat ditentukan.
    expect($kawasan->berkas->pluck('pivot.urutan')->all())->toBe([1, 2]);
});

it('menolak berkas melebihi 5 MB', function () {
    // Batas berlaku PER BERKAS, bukan total (Putaran 14).
    Storage::fake('local');

    $this->post(route('kawasan.simpan'), [
        'nama' => 'KAWASAN BERAT',
        'kabupaten_id' => 5321,
        'dokumen_kawasan' => [UploadedFile::fake()->create('besar.pdf', 6000, 'application/pdf')],
    ])->assertSessionHasErrors('dokumen_kawasan.0');

    expect(KawasanTransmigrasi::where('nama', 'KAWASAN BERAT')->exists())->toBeFalse()
        ->and(Berkas::count())->toBe(0);
});

it('menolak menghapus kawasan yang masih menaungi SP', function () {
    $kawasan = KawasanTransmigrasi::find(1);
    buatSp(['kawasan_id' => $kawasan->id_kawasan_transmigrasi]);

    $this->from(route('kawasan'))
        ->delete(route('kawasan.hapus', $kawasan->id_kawasan_transmigrasi))
        ->assertRedirect(route('kawasan'))
        ->assertSessionHas('galat');

    expect(KawasanTransmigrasi::find(1))->not->toBeNull();
});

it('melepas pivot tanpa menghapus registry berkas saat kawasan dihapus', function () {
    // Registry `berkas` melayani banyak modul, sehingga barisnya TIDAK ikut
    // hilang saat satu pemiliknya dihapus (Task 3.1 B4).
    Storage::fake('local');

    $this->post(route('kawasan.simpan'), [
        'nama' => 'KAWASAN SEMENTARA',
        'kabupaten_id' => 5321,
        'dokumen_kawasan' => [UploadedFile::fake()->create('sk.pdf', 50, 'application/pdf')],
    ]);

    $kawasan = KawasanTransmigrasi::where('nama', 'KAWASAN SEMENTARA')->first();

    $this->delete(route('kawasan.hapus', $kawasan->id_kawasan_transmigrasi))
        ->assertRedirect(route('kawasan'));

    expect(KawasanTransmigrasi::find($kawasan->id_kawasan_transmigrasi))->toBeNull()
        ->and(Berkas::count())->toBe(1);
});
