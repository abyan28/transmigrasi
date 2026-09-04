<?php

/*
 * Task 6.4 (poktan) + 6.5 (anggota poktan).
 *
 * Yang dijaga: ketua tiga jalur (`asal_ketua`), identitas dibaca lewat relasi
 * pada dua jalur pertama; anggota ditandai Sudah Keluar (tak pernah dihapus);
 * satu keluarga hanya Aktif di satu poktan (rules.md 6.4 / 7a).
 */

use App\Enums\StatusKeaktifanAnggota;
use App\Models\AnggotaPoktan;
use App\Models\Poktan;
use App\Models\Transmigran;
use App\Models\User;
use App\Support\DummyData;
use Database\Seeders\DaftarPilihanSeeder;
use Database\Seeders\KawasanSeeder;
use Database\Seeders\LahanSeeder;
use Database\Seeders\PoktanSeeder;
use Database\Seeders\SpSeeder;
use Database\Seeders\TransmigranSeeder;
use Database\Seeders\WilayahSeeder;

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
    $this->seed(LahanSeeder::class);
    $this->seed(PoktanSeeder::class);
});

it('menanam poktan dan anggota dari data contoh', function () {
    expect(Poktan::count())->toBe(count(DummyData::poktan()))
        ->and(AnggotaPoktan::count())->toBe(count(DummyData::anggotaPoktan()));

    $mekar = Poktan::where('nama', 'POKTAN MEKAR JAYA')->first();
    expect($mekar->slug)->not->toBeNull()
        ->and($mekar->ketuaTransmigran->nama_kepala_keluarga)->toBe('YOHANES BERE');
});

it('merender daftar poktan dengan nama ketua dari relasi', function () {
    $this->get(route('poktan.index'))
        ->assertOk()
        ->assertSee('POKTAN MEKAR JAYA')
        ->assertSee('YOHANES BERE'); // ketua jalur Kepala Keluarga, dari relasi
});

it('membaca identitas ketua jalur Anggota Keluarga dari relasi', function () {
    // POKTAN TANI BERSATU (id 3) diketuai YOVITA NAHAK SERAN (anggota keluarga 8),
    // BUKAN kepala keluarganya PETRUS NAHAK.
    $poktan = Poktan::where('nama', 'POKTAN TANI BERSATU')->first();

    $this->get(route('poktan.detail', $poktan->id_poktan))
        ->assertOk()
        ->assertSee('YOVITA NAHAK SERAN')
        ->assertDontSee('Ketua PETRUS NAHAK');
});

it('membalas 404 untuk poktan yang tidak ada', function () {
    $this->get('/poktan/99999')->assertNotFound();
});

it('menyimpan poktan baru jalur Kepala Keluarga', function () {
    $ketua = Transmigran::doesntHave('lahan')->first() ?? Transmigran::first();

    $this->post(route('poktan.simpan'), [
        'satuan_permukiman_id' => $ketua->satuan_permukiman_id,
        'nama' => 'POKTAN UJI BARU',
        'asal_ketua' => 'Kepala Keluarga',
        'ketua_transmigran_id' => $ketua->id_transmigran,
        'tahun_berdiri' => 2020,
    ])->assertRedirect(route('poktan.index'));

    $poktan = Poktan::where('nama', 'POKTAN UJI BARU')->first();
    expect($poktan)->not->toBeNull()
        ->and($poktan->slug)->toBe('poktan-uji-baru')
        ->and($poktan->asal_ketua->value)->toBe('Kepala Keluarga')
        ->and($poktan->nama_ketua)->toBeNull();
});

it('menyimpan poktan jalur Bukan Transmigran dengan nama dan NIK ketua', function () {
    $sp = Transmigran::value('satuan_permukiman_id');

    $this->post(route('poktan.simpan'), [
        'satuan_permukiman_id' => $sp,
        'nama' => 'POKTAN WARGA LOKAL',
        'asal_ketua' => 'Bukan Transmigran',
        'nama_ketua' => 'MARKUS BOROMEO',
        'nik_ketua' => '5321010101800123',
        'luas_kering_ketua' => '0.90',
        'luas_basah_ketua' => '0.10',
    ])->assertRedirect(route('poktan.index'));

    $poktan = Poktan::where('nama', 'POKTAN WARGA LOKAL')->first();
    expect($poktan->nama_ketua)->toBe('MARKUS BOROMEO')
        ->and($poktan->ketua_transmigran_id)->toBeNull()
        ->and((float) $poktan->luas_kering_ketua)->toBe(0.9);
});

it('mewajibkan nama dan NIK ketua hanya pada jalur Bukan Transmigran', function () {
    $sp = Transmigran::value('satuan_permukiman_id');

    $this->post(route('poktan.simpan'), [
        'satuan_permukiman_id' => $sp,
        'nama' => 'POKTAN TANPA KETUA',
        'asal_ketua' => 'Bukan Transmigran',
    ])->assertSessionHasErrors(['nama_ketua', 'nik_ketua']);
});

it('menambah anggota baru lewat langkah 3 form poktan', function () {
    $poktan = Poktan::where('nama', 'POKTAN SUBUR MAKMUR')->first();
    // ANGELA SERAN (transmigran 4) belum tergabung poktan mana pun pada data contoh.
    $baru = Transmigran::where('nama_kepala_keluarga', 'ANGELA SERAN')->first();

    $this->put(route('poktan.perbarui', $poktan->id_poktan), [
        'satuan_permukiman_id' => $poktan->satuan_permukiman_id,
        'nama' => $poktan->nama,
        'asal_ketua' => 'Kepala Keluarga',
        'ketua_transmigran_id' => $poktan->ketua_transmigran_id,
        '_anggota_disunting' => '1',
        'anggota' => [
            ['transmigran_id' => $baru->id_transmigran, 'jabatan' => 'Anggota'],
        ],
    ])->assertRedirect(route('poktan.detail', $poktan->id_poktan));

    expect($poktan->anggota()->where('transmigran_id', $baru->id_transmigran)->exists())->toBeTrue();
});

it('menandai anggota Sudah Keluar tanpa menghapus barisnya', function () {
    $anggota = AnggotaPoktan::where('status', StatusKeaktifanAnggota::Aktif->value)->first();

    $this->put(route('anggota-poktan.perbarui', $anggota->id_anggota_poktan), [
        'poktan_id' => $anggota->poktan_id,
        'transmigran_id' => $anggota->transmigran_id,
        'asal_wakil' => $anggota->asal_wakil->value,
        'jabatan' => $anggota->jabatan,
        'tanggal_masuk' => $anggota->tanggal_masuk->toDateString(),
        'status' => 'Sudah Keluar',
        'tanggal_keluar' => '2026-06-01',
        'alasan_keluar' => 'Pindah ke luar kawasan.',
    ])->assertRedirect();

    $anggota->refresh();
    expect($anggota->status)->toBe(StatusKeaktifanAnggota::SudahKeluar)
        ->and($anggota->tanggal_keluar->toDateString())->toBe('2026-06-01')
        ->and($anggota->trashed())->toBeFalse();
});

it('mewajibkan tanggal keluar saat status Sudah Keluar', function () {
    $anggota = AnggotaPoktan::where('status', StatusKeaktifanAnggota::Aktif->value)->first();

    $this->put(route('anggota-poktan.perbarui', $anggota->id_anggota_poktan), [
        'poktan_id' => $anggota->poktan_id,
        'transmigran_id' => $anggota->transmigran_id,
        'asal_wakil' => $anggota->asal_wakil->value,
        'jabatan' => $anggota->jabatan,
        'tanggal_masuk' => $anggota->tanggal_masuk->toDateString(),
        'status' => 'Sudah Keluar',
    ])->assertSessionHasErrors('tanggal_keluar');
});

it('menolak keluarga yang masih Aktif di poktan lain', function () {
    // transmigran 1 aktif di POKTAN MEKAR JAYA (anggota id 1).
    $lain = Poktan::where('nama', 'POKTAN TANI BERSATU')->first();

    $this->post(route('anggota-poktan.simpan'), [
        'poktan_id' => $lain->id_poktan,
        'transmigran_id' => 1,
        'asal_wakil' => 'Kepala Keluarga',
        'jabatan' => 'Anggota',
        'tanggal_masuk' => '2026-01-01',
        'status' => 'Aktif',
    ])->assertSessionHasErrors('transmigran_id');
});

it('menghapus poktan secara halus', function () {
    $id = Poktan::where('nama', 'POKTAN HARAPAN BARU')->value('id_poktan');

    $this->delete(route('poktan.hapus', $id))->assertRedirect(route('poktan.index'));

    expect(Poktan::find($id))->toBeNull()
        ->and(Poktan::withTrashed()->find($id)->trashed())->toBeTrue();
});
