<?php

/*
 * Task 4.8 -- parameter bobot + ambang status penilaian kondisi SP.
 */

use App\Models\ParameterPenilaianSp;
use App\Models\StatusKondisiSp;
use App\Models\User;
use Database\Seeders\DaftarPilihanSeeder;
use Database\Seeders\PenilaianKondisiSeeder;

require_once __DIR__.'/DatabaseHelpers.php';

beforeEach(function () {
    $petugas = User::factory()->create();
    $petugas->semuaIzin = true;
    $this->actingAs($petugas);
    $this->seed(DaftarPilihanSeeder::class);
    $this->seed(PenilaianKondisiSeeder::class);
});

it('menanam parameter dan tiga status berambang menurun', function () {
    expect(ParameterPenilaianSp::count())->toBeGreaterThan(0);

    $ambang = StatusKondisiSp::orderBy('urutan')->pluck('ambang_bawah')
        ->map(fn ($a) => (float) $a)->all();

    expect($ambang)->toBe([80.0, 55.0, 0.0]);
});

it('menyimpan perubahan bobot parameter', function () {
    $par = ParameterPenilaianSp::orderBy('urutan')->first();

    $this->put(route('penilaian-kondisi.parameter', $par->id_parameter_penilaian_sp), [
        'nama' => $par->nama,
        'bobot' => '13',
        'is_dinilai' => '1',
    ])->assertRedirect(route('master.penilaian-kondisi'));

    expect((int) $par->fresh()->bobot)->toBe(13);
});

it('menonaktifkan parameter tanpa menghapusnya', function () {
    // Parameter dinonaktifkan, bukan dihapus, agar riwayat penilaian yang
    // memakainya tetap terbaca.
    $par = ParameterPenilaianSp::where('is_dinilai', true)->first();

    $this->put(route('penilaian-kondisi.parameter', $par->id_parameter_penilaian_sp), [
        'nama' => $par->nama,
        'bobot' => (string) $par->bobot,
    ])->assertRedirect(route('master.penilaian-kondisi'));

    expect(ParameterPenilaianSp::find($par->id_parameter_penilaian_sp))->not->toBeNull()
        ->and($par->fresh()->is_dinilai)->toBeFalse();
});

it('menolak ambang yang membuat status di bawahnya mustahil dicapai', function () {
    // Pembacaan status berhenti pada ambang tertinggi yang cocok, sehingga
    // ambang Mandiri yang lebih KECIL daripada Berkembang membuat Berkembang
    // mustahil dicapai -- seluruh SP di rentang itu terbaca Mandiri.
    //
    // Kegagalan senyap: tak ada galat, hanya satu status yang lenyap dari
    // kawasan tanpa ada yang menyadarinya.
    $mandiri = StatusKondisiSp::where('urutan', 1)->first();

    $this->from(route('master.penilaian-kondisi'))
        ->put(route('penilaian-kondisi.status', $mandiri->kode), [
            'nama' => $mandiri->nama,
            'ambang_bawah' => '40',
        ])->assertSessionHasErrors('ambang_bawah');

    expect((float) $mandiri->fresh()->ambang_bawah)->toBe(80.0);
});

it('menolak ambang yang melampaui status di atasnya', function () {
    $berkembang = StatusKondisiSp::where('urutan', 2)->first();

    $this->from(route('master.penilaian-kondisi'))
        ->put(route('penilaian-kondisi.status', $berkembang->kode), [
            'nama' => $berkembang->nama,
            'ambang_bawah' => '85',
        ])->assertSessionHasErrors('ambang_bawah');

    expect((float) $berkembang->fresh()->ambang_bawah)->toBe(55.0);
});

it('menerima ambang yang tetap menjaga urutan menurun', function () {
    $berkembang = StatusKondisiSp::where('urutan', 2)->first();

    $this->put(route('penilaian-kondisi.status', $berkembang->kode), [
        'nama' => $berkembang->nama,
        'ambang_bawah' => '60',
    ])->assertRedirect(route('master.penilaian-kondisi'));

    expect((float) $berkembang->fresh()->ambang_bawah)->toBe(60.0);
});

it('membalas 404 untuk kode status yang tidak ada', function () {
    $this->put('/master/penilaian-kondisi/status/Sejahtera', [
        'nama' => 'Sejahtera',
        'ambang_bawah' => '90',
    ])->assertNotFound();
});

it('menolak bobot desimal yang akan dibulatkan diam-diam', function () {
    // `bobot` bertipe TINYINT UNSIGNED di skema, sehingga 12,5 tersimpan
    // sebagai 13. Tanpa penjaga ini petugas menyimpan satu angka lalu membaca
    // angka lain tanpa peringatan apa pun -- dan total bobot yang ia susun
    // agar berjumlah 100 diam-diam meleset.
    $par = ParameterPenilaianSp::orderBy('urutan')->first();
    $semula = (int) $par->bobot;

    $this->from(route('master.penilaian-kondisi'))
        ->put(route('penilaian-kondisi.parameter', $par->id_parameter_penilaian_sp), [
            'nama' => $par->nama,
            'bobot' => '12.5',
        ])->assertSessionHasErrors('bobot');

    expect((int) $par->fresh()->bobot)->toBe($semula);
});
