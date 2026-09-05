<?php

/*
 * Task 3.12 -- Halaman & filter Audit Log.
 *
 * Berumah di grup Database sebab menyentuh `whereYear`, relasi `pelaku`, dan
 * baris nyata yang ditanam `AuditLogObserver` (Task 3.6). HANYA-BACA: tak ada
 * rute tulis.
 */

use App\Enums\AksiAuditLog;
use App\Models\AuditLog;
use App\Models\Komoditas;
use App\Models\User;
use Database\Seeders\PermissionRoleSeeder;

require_once __DIR__.'/DatabaseHelpers.php';

function petugasAuditLog(): User
{
    $petugas = User::factory()->create();
    $petugas->semuaIzin = true;
    test()->actingAs($petugas);

    return $petugas;
}

it('merender halaman audit log', function () {
    petugasAuditLog();

    $this->get(route('audit-log'))->assertOk()->assertSee('Audit Log');
});

it('menampilkan baris Tambah setelah data dibuat', function () {
    petugasAuditLog();
    Komoditas::create(['satuan_id' => buatSatuanTon()->id_satuan, 'nama' => 'Melon Uji', 'tipe' => 'Pangan']);

    $this->get(route('audit-log'))->assertOk()
        ->assertSee('Tambah')
        ->assertSee('komoditas');
});

it('menampilkan selisih kolom pada baris Ubah', function () {
    petugasAuditLog();
    $k = Komoditas::create(['satuan_id' => buatSatuanTon()->id_satuan, 'nama' => 'Sorgum Uji', 'tipe' => 'Pangan']);
    $k->update(['nama' => 'Sorgum Manis Uji']);

    $this->get(route('audit-log'))->assertOk()
        ->assertSee('Mengubah 1 kolom: nama.')
        ->assertSeeText('Sorgum Uji')
        ->assertSeeText('Sorgum Manis Uji');
});

it('tidak pernah menampilkan kolom rahasia walau baris audit memuatnya', function () {
    petugasAuditLog();
    AuditLog::create([
        'user_id' => null,
        'aksi' => AksiAuditLog::Ubah,
        'nama_tabel' => 'user',
        'record_id' => 1,
        'data_lama' => ['password' => 'HASH-LAMA-RAHASIA', 'nama' => 'A'],
        'data_baru' => ['password' => 'HASH-BARU-RAHASIA', 'nama' => 'B'],
    ]);

    $this->get(route('audit-log'))->assertOk()
        ->assertDontSee('RAHASIA')
        ->assertSee('Mengubah 1 kolom: nama.');
});

it('menyaring menurut jenis aksi', function () {
    petugasAuditLog();
    $k = Komoditas::create(['satuan_id' => buatSatuanTon()->id_satuan, 'nama' => 'Ubi Uji', 'tipe' => 'Pangan']);
    $k->delete();

    $this->get(route('audit-log', ['aksi' => 'Hapus']))->assertOk()
        ->assertSee('Menghapus baris.')
        ->assertDontSee('Menambah baris baru.');
});

it('menyaring menurut pelaku, termasuk "Sistem" untuk baris tanpa pelaku', function () {
    $aktor = petugasAuditLog();
    Komoditas::create(['satuan_id' => buatSatuanTon()->id_satuan, 'nama' => 'Kopi Uji', 'tipe' => 'Pangan']);
    AuditLog::create([
        'user_id' => null, 'aksi' => AksiAuditLog::Hapus, 'nama_tabel' => 'komoditas', 'record_id' => 99,
        'data_lama' => ['nama' => 'Baris Sistem'],
    ]);

    $this->get(route('audit-log', ['pengguna' => 'Sistem']))->assertOk()
        ->assertSee('Menghapus baris.')
        ->assertDontSee('Menambah baris baru.');

    $this->get(route('audit-log', ['pengguna' => $aktor->nama]))->assertOk()
        ->assertSee('Menambah baris baru.');
});

it('menyaring menurut rentang tahun peristiwa', function () {
    petugasAuditLog();
    $lama = AuditLog::create([
        'user_id' => null, 'aksi' => AksiAuditLog::Tambah, 'nama_tabel' => 'komoditas', 'record_id' => 1,
        'data_baru' => ['nama' => 'PERISTIWA LAMA'],
    ]);
    $lama->forceFill(['created_at' => '2019-06-01 08:00:00'])->saveQuietly();

    $this->get(route('audit-log', ['tahun_dari' => 2099]))->assertOk()
        ->assertViewHas('baris', fn ($b) => $b->total() === 0);

    $this->get(route('audit-log', ['tahun_dari' => 2019, 'tahun_sampai' => 2019]))->assertOk()
        ->assertSeeText('PERISTIWA LAMA');
});

it('memaginasi sesuai pilihan dan mempertahankannya pada tautan halaman', function () {
    petugasAuditLog();

    for ($i = 1; $i <= 30; $i++) {
        AuditLog::create([
            'user_id' => null, 'aksi' => AksiAuditLog::Tambah, 'nama_tabel' => 'komoditas', 'record_id' => $i,
            'data_baru' => ['nama' => "Baris ke-{$i}"],
        ]);
    }

    $h1 = $this->get(route('audit-log', ['per_halaman' => 10]))->assertOk()
        ->assertSee('<option value="10" selected>10</option>', false)
        ->assertSee('per_halaman=10', false);

    expect($h1->viewData('baris')->count())->toBe(10)
        ->and($h1->viewData('baris')->perPage())->toBe(10)
        ->and($h1->viewData('baris')->total())->toBe(30);

    $this->get(route('audit-log', ['per_halaman' => 10, 'page' => 2]))->assertOk()
        ->assertViewHas('baris', fn ($b) => $b->count() === 10 && $b->currentPage() === 2);

    $this->get(route('audit-log', ['per_halaman' => 999]))->assertOk()
        ->assertViewHas('baris', fn ($b) => $b->perPage() === 25);
});

it('menolak akses role tanpa kewenangan audit_log', function () {
    $this->seed(PermissionRoleSeeder::class);
    $operator = User::factory()->create(['role_id' => 4]); // Operator SP, tanpa audit_log.lihat

    $this->actingAs($operator)->get(route('audit-log'))->assertForbidden();
});

it('tidak menyediakan rute tulis apa pun untuk audit log', function () {
    $namaRute = collect(app('router')->getRoutes()->getRoutes())
        ->map(fn ($r) => $r->getName())
        ->filter()
        ->filter(fn ($n) => str_starts_with($n, 'audit-log'))
        ->values();

    expect($namaRute->all())->toBe(['audit-log']);
});
