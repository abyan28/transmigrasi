<?php

/*
 * Task 3.5 -- Akun Admin pertama (`AdminAwalSeeder`, `rules.md` 14b poin 1).
 */

use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdminAwalSeeder;
use Database\Seeders\PermissionRoleSeeder;

require_once __DIR__.'/DatabaseHelpers.php';

beforeEach(function () {
    $this->seed(PermissionRoleSeeder::class);
});

it('menanam satu akun Admin ketika belum ada akun mana pun', function () {
    $this->seed(AdminAwalSeeder::class);

    $roleAdmin = Role::where('is_terkunci', true)->firstOrFail();
    $admin = User::where('role_id', $roleAdmin->id_role)->get();

    expect($admin)->toHaveCount(1)
        ->and($admin->first()->password_harus_diganti)->toBeTrue()
        ->and($admin->first()->is_aktif)->toBeTrue();
});

it('idempoten: dijalankan ulang tidak menambah akun Admin kedua', function () {
    $this->seed(AdminAwalSeeder::class);
    $this->seed(AdminAwalSeeder::class);

    $roleAdmin = Role::where('is_terkunci', true)->firstOrFail();

    expect(User::where('role_id', $roleAdmin->id_role)->count())->toBe(1);
});

it('dilewati bila sudah ada akun Admin', function () {
    $roleAdmin = Role::where('is_terkunci', true)->firstOrFail();
    $adaLebihDulu = User::factory()->create(['role_id' => $roleAdmin->id_role]);

    $this->seed(AdminAwalSeeder::class);

    expect(User::where('role_id', $roleAdmin->id_role)->pluck('id_user')->all())
        ->toBe([$adaLebihDulu->id_user]);
});
