<?php

/*
 * Task 3.3 -- `User::punyaIzin()` / `punyaAksi()` + Gate `@can`.
 *
 * Berjalan di MySQL nyata (`DatabaseTestCase`). Role/Permission dari seeder.
 */

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionRoleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->seed(PermissionRoleSeeder::class);
});

it('menjawab true bila role memegang kewenangan itu', function () {
    $user = User::factory()->create(['role_id' => 4]); // Operator SP

    expect($user->punyaIzin('transmigran.tambah'))->toBeTrue()
        ->and($user->punyaAksi('transmigran', 'tambah'))->toBeTrue()
        ->and($user->punyaIzin('transmigran.hapus'))->toBeFalse()
        ->and($user->punyaIzin('penanganan_pengaduan.lihat'))->toBeFalse();
});

it('menjawab false bila pengguna tanpa role', function () {
    $user = new User(['nama' => 'TANPA ROLE']);

    expect($user->punyaIzin('dashboard.lihat'))->toBeFalse();
});

it('menghormati flag semuaIzin untuk pengguna semu', function () {
    $semu = new User(['nama' => 'DEV']);
    $semu->semuaIzin = true;

    expect($semu->punyaIzin('role.hapus'))->toBeTrue()
        ->and($semu->punyaIzin('apa.saja'))->toBeTrue();
});

it('mencabut seluruh kewenangan saat role dinonaktifkan', function () {
    $role = Role::find(2); // Dinas Transmigrasi
    $user = User::factory()->create(['role_id' => $role->id_role]);

    expect($user->punyaIzin('transmigran.ubah'))->toBeTrue();

    $role->update(['is_aktif' => false]);

    expect($user->fresh()->punyaIzin('transmigran.ubah'))->toBeFalse();
});

it('menyambungkan RBAC ke Gate untuk @can dan $user->can()', function () {
    $admin = User::factory()->create(['role_id' => 1]);
    $operator = User::factory()->create(['role_id' => 4]);

    expect($admin->can('role.hapus'))->toBeTrue()
        ->and($operator->can('role.hapus'))->toBeFalse()
        ->and(Gate::forUser($operator)->allows('transmigran.tambah'))->toBeTrue();
});

it('memuat kewenangan tanpa query berulang lewat eager load role.permissions', function () {
    $user = User::factory()->create(['role_id' => 1]);
    $user->load('role.permissions');

    DB::enableQueryLog();
    $user->punyaIzin('transmigran.lihat');
    $user->punyaIzin('rumah.ubah');
    $user->punyaIzin('lahan.hapus');

    expect(DB::getQueryLog())->toBeEmpty();
    DB::disableQueryLog();

    expect(Permission::count())->toBe(95); // sanity
});
