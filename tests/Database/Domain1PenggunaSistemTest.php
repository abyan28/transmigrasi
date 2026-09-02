<?php

/*
 * Task 3.1 -- DOMAIN 1 (Pengguna & Sistem).
 *
 * Menerjemahkan `database/data/schema.sql` menjadi migration + model. Berjalan
 * di MySQL/MariaDB nyata (Tests\DatabaseTestCase) supaya ENUM, UNSIGNED, FK, dan
 * UNIQUE benar-benar ditegakkan -- SQLite tidak melakukannya.
 *
 * Kecocokan kolom/indeks/FK terhadap schema.sql dijaga terpisah oleh
 * `php artisan sim:banding-skema`.
 */

use App\Enums\AksiPermission;
use App\Enums\CakupanData;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

it('membuat keenam tabel Domain 1', function () {
    foreach (['role', 'permission', 'role_permission', 'user', 'kode_pemulihan_sandi', 'audit_log'] as $tabel) {
        expect(Schema::hasTable($tabel))->toBeTrue("tabel {$tabel} tidak dibuat");
    }

    // Tabel bawaan yang DICABUT.
    expect(Schema::hasTable('users'))->toBeFalse('tabel `users` bawaan tidak boleh ada');
    expect(Schema::hasTable('password_reset_tokens'))->toBeFalse('password_reset_tokens digantikan kode_pemulihan_sandi');
});

it('memakai PK dan nama tabel bentuk tunggal pada model', function () {
    expect((new User)->getTable())->toBe('user')
        ->and((new User)->getKeyName())->toBe('id_user')
        ->and((new Role)->getTable())->toBe('role')
        ->and((new Role)->getKeyName())->toBe('id_role')
        ->and((new Permission)->getTable())->toBe('permission')
        ->and((new Permission)->getKeyName())->toBe('id_permission');
});

it('menyimpan dan membaca user beserta rolenya lewat kunci eksplisit', function () {
    $role = Role::factory()->create(['nama' => 'Operator SP', 'cakupan_data' => CakupanData::PerSp->value]);
    $user = User::factory()->create(['role_id' => $role->id_role, 'username' => 'operator.a']);

    expect($user->id_user)->toBeInt()
        ->and($user->role)->not->toBeNull()
        ->and($user->role->id_role)->toBe($role->id_role)
        ->and($user->role->cakupan_data)->toBe(CakupanData::PerSp)
        ->and($role->users->pluck('id_user'))->toContain($user->id_user);
});

it('meng-cast kolom ENUM ke PHP Enum dan boolean ke bool', function () {
    $role = Role::factory()->terkunci()->create(['cakupan_data' => CakupanData::Semua->value]);
    $perm = Permission::factory()->create(['aksi' => AksiPermission::Hapus->value]);

    expect($role->cakupan_data)->toBe(CakupanData::Semua)
        ->and($role->is_terkunci)->toBeTrue()
        ->and($role->is_bawaan)->toBeTrue()
        ->and($perm->aksi)->toBe(AksiPermission::Hapus);
});

it('menghubungkan role dan permission lewat pivot role_permission', function () {
    $role = Role::factory()->create();
    $perms = Permission::factory()->count(3)->create();

    $role->permissions()->attach($perms->pluck('id_permission'));

    expect($role->permissions()->count())->toBe(3)
        ->and($perms->first()->roles()->pluck('id_role'))->toContain($role->id_role);
});

it('menegakkan UNIQUE email dan username pada user', function () {
    $role = Role::factory()->create();
    User::factory()->create(['role_id' => $role->id_role, 'email' => 'a@dinas.go.id', 'username' => 'petugas.a']);

    expect(fn () => User::factory()->create(['role_id' => $role->id_role, 'email' => 'a@dinas.go.id']))
        ->toThrow(QueryException::class);
    expect(fn () => User::factory()->create(['role_id' => $role->id_role, 'username' => 'petugas.a']))
        ->toThrow(QueryException::class);
});

it('menegakkan FK user.role_id RESTRICT saat role dihapus', function () {
    $role = Role::factory()->create();
    User::factory()->create(['role_id' => $role->id_role]);

    // ON DELETE RESTRICT: role dengan pengguna tidak dapat dihapus permanen.
    expect(fn () => $role->forceDelete())->toThrow(QueryException::class);
});

it('menegakkan domain ENUM cakupan_data di dua lapisan', function () {
    // Lapisan aplikasi: cast PHP Enum menolak lebih dulu.
    expect(fn () => Role::factory()->create(['cakupan_data' => 'Ngawur']))
        ->toThrow(ValueError::class);

    // Lapisan basis data: sisipan mentah yang melewati cast tetap ditolak MySQL
    // ENUM (SQLite tidak melakukannya -- inilah alasan grup uji ini di MySQL).
    expect(fn () => DB::table('role')->insert([
        'nama' => 'Palsu', 'cakupan_data' => 'Ngawur', 'is_bawaan' => 0,
        'is_terkunci' => 0, 'is_aktif' => 1,
    ]))->toThrow(QueryException::class);
});

it('mengaktifkan soft delete pada role dan user, tidak pada permission', function () {
    expect(in_array(SoftDeletes::class, class_uses_recursive(User::class), true))->toBeTrue()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(Role::class), true))->toBeTrue()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(Permission::class), true))->toBeFalse();

    $user = User::factory()->create();
    $id = $user->id_user;
    $user->delete();

    expect(User::find($id))->toBeNull()
        ->and(User::withTrashed()->find($id))->not->toBeNull();
});

it('menyembunyikan password dan meng-hash-nya', function () {
    $user = User::factory()->create();

    expect($user->toArray())->not->toHaveKey('password')
        ->and($user->getAuthPassword())->not->toBe('password')
        ->and(Hash::check('password', $user->getAuthPassword()))->toBeTrue();
});
