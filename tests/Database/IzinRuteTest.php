<?php

/*
 * Task 3.3 -- middleware `izin` (EnsureIzin). Berjalan di MySQL nyata.
 * Pelampiran ke rute sebenarnya diuji di batch C4.
 */

use App\Http\Middleware\EnsureIzin;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionRoleSeeder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(PermissionRoleSeeder::class);
});

function jalankanIzin(?User $pengguna, string $modul, ?string $aksi): Response
{
    $request = Request::create('/x');
    $request->setUserResolver(fn () => $pengguna);

    return (new EnsureIzin)->handle(
        $request,
        fn () => new Response('lolos'),
        $modul,
        $aksi,
    );
}

it('meloloskan pengguna yang berwenang', function () {
    $admin = User::factory()->create(['role_id' => 1]);

    expect(jalankanIzin($admin, 'transmigran', 'ubah')->getContent())->toBe('lolos')
        ->and(jalankanIzin($admin, 'dashboard', null)->getContent())->toBe('lolos');
});

it('menolak 403 bila kewenangan aksi tidak dipegang', function () {
    $operator = User::factory()->create(['role_id' => 4]); // tanpa transmigran.hapus

    jalankanIzin($operator, 'transmigran', 'hapus');
})->throws(HttpException::class);

it('menuntut lihat sebagai prasyarat aksi lain (data-dictionary 13.3 poin 4)', function () {
    // Role yang memegang `ubah` tetapi TIDAK `lihat` pada satu modul.
    $role = Role::factory()->create();
    $role->permissions()->sync(
        Permission::where('nama', 'komoditas.ubah')->pluck('id_permission'),
    );
    $user = User::factory()->create(['role_id' => $role->id_role]);

    expect($user->punyaIzin('komoditas.ubah'))->toBeTrue()
        ->and($user->punyaIzin('komoditas.lihat'))->toBeFalse();

    // Middleware tetap menolak: `lihat` wajib ada.
    expect(fn () => jalankanIzin($user, 'komoditas', 'ubah'))
        ->toThrow(HttpException::class);
});

it('menolak tamu', function () {
    expect(fn () => jalankanIzin(null, 'dashboard', null))
        ->toThrow(HttpException::class);
});

it('menegakkan izin pada rute nyata lewat alias', function () {
    Route::middleware(['web', 'izin:transmigran,hapus'])
        ->get('/_uji-izin', fn () => 'ok')
        ->name('_uji-izin');

    $this->actingAs(User::factory()->create(['role_id' => 1]))->get('/_uji-izin')->assertOk();
    $this->actingAs(User::factory()->create(['role_id' => 4]))->get('/_uji-izin')->assertForbidden();
});
