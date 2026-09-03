<?php

/*
 * Task 3.5b -- Perintah artisan pemulihan darurat kata sandi Admin
 * (`sim:pulihkan-admin`, `rules.md` 14b poin 17).
 */

use App\Enums\AksiAuditLog;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;

require_once __DIR__.'/DatabaseHelpers.php';

function roleAdminTerkunci(): Role
{
    return Role::factory()->terkunci()->create(['nama' => 'Admin '.Str::random(4)]);
}

it('menyetel ulang kata sandi Admin tunggal tanpa argumen', function () {
    $admin = User::factory()->create([
        'role_id' => roleAdminTerkunci()->id_role,
        'password_harus_diganti' => false,
    ]);
    $hashLama = $admin->password;

    $this->artisan('sim:pulihkan-admin')->assertSuccessful();

    $admin->refresh();
    expect($admin->password)->not->toBe($hashLama)
        ->and($admin->password_harus_diganti)->toBeTrue();

    $audit = AuditLog::where('nama_tabel', 'user')->where('record_id', $admin->id_user)
        ->where('aksi', AksiAuditLog::ResetKataSandi->value)->firstOrFail();

    expect($audit->user_id)->toBeNull()
        ->and($audit->data_baru['jalur'])->toBe('Artisan darurat');
});

it('gagal bila tidak ada akun Admin', function () {
    $this->artisan('sim:pulihkan-admin')->assertFailed();
});

it('menuntut argumen bila ada lebih dari satu Admin', function () {
    $role = roleAdminTerkunci();
    User::factory()->create(['role_id' => $role->id_role]);
    User::factory()->create(['role_id' => $role->id_role]);

    $this->artisan('sim:pulihkan-admin')->assertFailed();
});

it('memilih Admin menurut email pada argumen', function () {
    $role = roleAdminTerkunci();
    $sasaran = User::factory()->create(['role_id' => $role->id_role, 'email' => 'target@malakakab.go.id']);
    $lain = User::factory()->create(['role_id' => $role->id_role]);
    $hashLain = $lain->password;

    $this->artisan('sim:pulihkan-admin', ['identitas' => 'target@malakakab.go.id'])->assertSuccessful();

    expect($sasaran->refresh()->password_harus_diganti)->toBeTrue()
        ->and($lain->refresh()->password)->toBe($hashLain);
});

it('menolak akun non-Admin', function () {
    roleAdminTerkunci(); // ada Admin lain, tapi argumen menunjuk non-Admin
    $operator = User::factory()->create(['email' => 'operator@malakakab.go.id']);

    $this->artisan('sim:pulihkan-admin', ['identitas' => 'operator@malakakab.go.id'])->assertFailed();

    expect($operator->refresh()->password_harus_diganti)->toBeFalse();
});
