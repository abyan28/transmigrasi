<?php

/*
 * Task 3.3 (C5) -- backend PengaturanRoleController (role.simpan/perbarui/hapus).
 * Berjalan di MySQL nyata. Diuji lewat state basis data, bukan halaman
 * (index() masih baca DummyData sampai Tahap 4).
 */

use App\Enums\AksiAuditLog;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionRoleSeeder;

beforeEach(function () {
    $this->seed(PermissionRoleSeeder::class);
    $this->admin = User::factory()->create(['role_id' => 1]);
});

it('membuat role baru beserta pasangan kewenangannya', function () {
    $this->actingAs($this->admin)->post(route('role.simpan'), [
        'nama' => 'Auditor Kawasan',
        'deskripsi' => 'Membaca seluruh data tanpa mengubah.',
        'cakupan_data' => 'Semua',
        'izin' => ['transmigran' => ['lihat'], 'rumah' => ['lihat'], 'dashboard' => ['lihat']],
    ])->assertRedirect(route('pengaturan.role'))->assertSessionHas('sukses');

    $role = Role::where('nama', 'Auditor Kawasan')->first();

    expect($role)->not->toBeNull()
        ->and($role->is_bawaan)->toBeFalse()
        ->and($role->permissions()->count())->toBe(3)
        ->and($role->permissions()->where('nama', 'transmigran.lihat')->exists())->toBeTrue();

    expect(AuditLog::where('nama_tabel', 'role')->where('record_id', $role->id_role)
        ->where('aksi', AksiAuditLog::UbahIzinRole)->exists())->toBeTrue();
});

it('menolak nama role yang duplikat atau terlalu pendek', function () {
    $this->actingAs($this->admin)->post(route('role.simpan'), [
        'nama' => 'Admin', 'cakupan_data' => 'Semua',
    ])->assertSessionHasErrors('nama');

    $this->actingAs($this->admin)->post(route('role.simpan'), [
        'nama' => 'AB', 'cakupan_data' => 'Semua',
    ])->assertSessionHasErrors('nama');
});

it('menolak kewenangan aksi tanpa lihat pada modul yang sama', function () {
    $this->actingAs($this->admin)->post(route('role.simpan'), [
        'nama' => 'Salah Konfigurasi',
        'cakupan_data' => 'Semua',
        'izin' => ['transmigran' => ['ubah']], // tanpa 'lihat'
    ])->assertSessionHasErrors('izin');

    expect(Role::where('nama', 'Salah Konfigurasi')->exists())->toBeFalse();
});

it('memperbarui susunan kewenangan role yang tidak terkunci', function () {
    $role = Role::find(4); // Operator SP, is_terkunci = false
    $sebelum = $role->permissions()->count();

    $this->actingAs($this->admin)->put(route('role.perbarui', ['id' => $role->id_role]), [
        'nama' => $role->nama,
        'cakupan_data' => $role->cakupan_data->value,
        'izin' => ['dashboard' => ['lihat'], 'transmigran' => ['lihat']],
    ])->assertRedirect(route('pengaturan.role'));

    expect($role->fresh()->permissions()->count())->toBe(2)
        ->and($sebelum)->toBeGreaterThan(2);
});

it('menolak menyunting role Admin yang terkunci (403)', function () {
    $this->actingAs($this->admin)->put(route('role.perbarui', ['id' => 1]), [
        'nama' => 'Admin', 'cakupan_data' => 'Semua', 'izin' => ['dashboard' => ['lihat']],
    ])->assertForbidden();

    expect(Role::find(1)->permissions()->count())->toBe(97); // tak berubah
});

it('menolak menghapus role bawaan (403)', function () {
    $this->actingAs($this->admin)->delete(route('role.hapus', ['id' => 2]), ['alasan' => 'coba'])
        ->assertForbidden();

    expect(Role::find(2))->not->toBeNull();
});

it('menolak menghapus role yang masih dipakai akun (422)', function () {
    $role = Role::factory()->create();
    User::factory()->create(['role_id' => $role->id_role]);

    $this->actingAs($this->admin)->delete(route('role.hapus', ['id' => $role->id_role]), ['alasan' => 'coba'])
        ->assertStatus(422);
});

it('menghapus role kosong non-bawaan dan mencatat alasannya', function () {
    $role = Role::factory()->create(['nama' => 'Sementara']);

    $this->actingAs($this->admin)->delete(route('role.hapus', ['id' => $role->id_role]))
        ->assertSessionHasErrors('alasan'); // alasan wajib

    $this->actingAs($this->admin)->delete(route('role.hapus', ['id' => $role->id_role]), [
        'alasan' => 'Dibuat keliru saat uji coba.',
    ])->assertRedirect(route('pengaturan.role'));

    expect(Role::find($role->id_role))->toBeNull();

    $jejak = AuditLog::where('nama_tabel', 'role')->where('record_id', $role->id_role)
        ->where('aksi', AksiAuditLog::Hapus)->first();
    expect($jejak->data_baru['catatan'])->toBe('Dibuat keliru saat uji coba.');
});

it('menolak Dinas (bukan role.tambah) membuat role baru', function () {
    $dinas = User::factory()->create(['role_id' => 2]);

    $this->actingAs($dinas)->post(route('role.simpan'), [
        'nama' => 'Percobaan', 'cakupan_data' => 'Semua',
    ])->assertForbidden();
});
