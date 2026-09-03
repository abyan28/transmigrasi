<?php

/*
 * Task 3.3 -- PermissionRoleSeeder (97 izin + 5 role + pivot).
 *
 * Berjalan di MySQL/MariaDB nyata (`DatabaseTestCase`). Angka acuan dari
 * `data-dictionary.md` 13.1 + `rules.md` 5.1.
 */

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\PermissionRoleSeeder;

beforeEach(function () {
    $this->seed(PermissionRoleSeeder::class);
});

it('menanam tepat 97 kewenangan dengan pola modul.aksi', function () {
    expect(Permission::count())->toBe(97)
        ->and(Permission::where('nama', 'transmigran.ubah')->exists())->toBeTrue()
        ->and(Permission::where('nama', 'cms.ubah')->exists())->toBeTrue()
        ->and(Permission::where('nama', 'dashboard.lihat')->exists())->toBeTrue()
        // `export` dicabut 2026-08-17; tak ada izin hapus untuk pengguna.
        ->and(Permission::where('aksi', 'export')->exists())->toBeFalse()
        ->and(Permission::where('nama', 'pengguna.hapus')->exists())->toBeFalse()
        ->and(Permission::where('nama', 'audit_log.tambah')->exists())->toBeFalse();
});

it('menanam 5 role: 4 bawaan + 1 contoh non-bawaan', function () {
    expect(Role::count())->toBe(5)
        ->and(Role::where('is_bawaan', true)->count())->toBe(4)
        ->and(Role::find(1)->nama)->toBe('Admin')
        ->and(Role::find(1)->is_terkunci)->toBeTrue()
        ->and(Role::find(5)->nama)->toBe('Pendamping Lapangan')
        ->and(Role::find(5)->is_bawaan)->toBeFalse();
});

it('memberi Admin seluruh 97 kewenangan', function () {
    expect(Role::find(1)->permissions()->count())->toBe(97);
});

it('memberi tiap role bawaan jumlah kewenangan sesuai rules.md 5.1', function () {
    // Dinas Transmigrasi & Admin memegang CMS (cms.lihat + cms.ubah).
    expect(Role::find(2)->permissions()->count())->toBe(49)  // Dinas Transmigrasi (47 + cms)
        ->and(Role::find(3)->permissions()->count())->toBe(44)  // Dinas Pertanian
        ->and(Role::find(4)->permissions()->count())->toBe(49)  // Operator SP
        ->and(Role::find(5)->permissions()->count())->toBe(16)  // Pendamping Lapangan
        ->and(Role::find(2)->permissions()->where('modul', 'cms')->count())->toBe(2)
        ->and(Role::find(3)->permissions()->where('modul', 'cms')->exists())->toBeFalse();
});

it('tidak memberi Operator SP kewenangan penanganan pengaduan (rules.md 5.1 catatan 4)', function () {
    $operator = Role::find(4);

    expect($operator->permissions()->where('modul', 'penanganan_pengaduan')->exists())->toBeFalse()
        ->and($operator->permissions()->where('aksi', 'hapus')->exists())->toBeFalse()
        ->and($operator->permissions()->where('nama', 'transmigran.tambah')->exists())->toBeTrue();
});

it('idempoten: dijalankan ulang tetap 97 izin dan 5 role', function () {
    $this->seed(PermissionRoleSeeder::class);
    $this->seed(PermissionRoleSeeder::class);

    expect(Permission::count())->toBe(97)
        ->and(Role::count())->toBe(5)
        ->and(Role::find(1)->permissions()->count())->toBe(97);
});
