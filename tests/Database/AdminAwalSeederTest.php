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
    // `SIM_ADMIN_WAJIB_GANTI` boleh dimatikan di `.env` mesin pengembang agar
    // akun telusur lokal tidak terlempar ke /ganti-kata-sandi. Uji WAJIB tidak
    // mewarisi setelan itu: yang dijaga di sini adalah perilaku BAWAAN, yakni
    // apa yang terjadi di server dinas yang tidak menyetel apa pun.
    putenv('SIM_ADMIN_WAJIB_GANTI');
    unset($_ENV['SIM_ADMIN_WAJIB_GANTI'], $_SERVER['SIM_ADMIN_WAJIB_GANTI']);
    putenv('SIM_ADMIN_USERNAME');
    unset($_ENV['SIM_ADMIN_USERNAME'], $_SERVER['SIM_ADMIN_USERNAME']);

    config([
        'sim.admin_awal.username' => null,
        'sim.admin_awal.password' => null,
        'sim.admin_awal.wajib_ganti' => true,
    ]);

    $this->seed(PermissionRoleSeeder::class);
});

it('menanam satu akun Admin ketika belum ada akun mana pun', function () {
    $this->seed(AdminAwalSeeder::class);

    $roleAdmin = Role::where('is_terkunci', true)->firstOrFail();
    $admin = User::where('role_id', $roleAdmin->id_role)->get();

    expect($admin)->toHaveCount(1)
        ->and($admin->first()->password_harus_diganti)->toBeTrue()
        ->and($admin->first()->is_aktif)->toBeTrue()
        // Tanpa SIM_ADMIN_USERNAME: Admin awal ikut membuat usernamenya sendiri
        // saat masuk pertama (Task 3.14).
        ->and($admin->first()->perluBuatUsername())->toBeTrue();
});

it('mematikan wajib-ganti hanya bila SIM_ADMIN_WAJIB_GANTI disetel false', function () {
    // Pengecualian untuk penelusuran lokal. Ditandai di sini supaya nilai
    // bawaannya tidak dapat berubah diam-diam menjadi longgar: akun seed yang
    // lahir tanpa paksa-ganti di server melanggar `rules.md` 14b poin 5.
    config(['sim.admin_awal.wajib_ganti' => false]);

    $this->seed(AdminAwalSeeder::class);

    $roleAdmin = Role::where('is_terkunci', true)->firstOrFail();

    expect(User::where('role_id', $roleAdmin->id_role)->first()->password_harus_diganti)
        ->toBeFalse();

    putenv('SIM_ADMIN_WAJIB_GANTI');
});

it('membaca konfigurasi Admin awal melalui config agar aman saat config cache', function () {
    config([
        'sim.admin_awal.email' => 'cached-admin@malakakab.go.id',
        'sim.admin_awal.nama' => 'ADMIN CACHE',
        'sim.admin_awal.username' => 'admin.cache',
        'sim.admin_awal.password' => 'RahasiaCache9',
        'sim.admin_awal.wajib_ganti' => false,
    ]);

    $this->seed(AdminAwalSeeder::class);

    $admin = User::where('email', 'cached-admin@malakakab.go.id')->firstOrFail();
    expect($admin->nama)->toBe('ADMIN CACHE')
        ->and($admin->username)->toBe('admin.cache')
        ->and($admin->password_harus_diganti)->toBeFalse();
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
