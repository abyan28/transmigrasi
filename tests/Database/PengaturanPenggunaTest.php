<?php

/*
 * Task 3.5 -- Manajemen pengguna oleh Admin (`rules.md` 14b).
 *
 * `DatabaseTestCase` (MySQL/MariaDB nyata): pembuatan akun, reset sandi,
 * nonaktif/aktif, dan audit log semuanya menyentuh tabel `user`/`audit_log`.
 * Halaman `index()` masih baca DummyData -- uji memeriksa basis data langsung.
 */

use App\Enums\AksiAuditLog;
use App\Enums\CakupanData;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

require_once __DIR__.'/DatabaseHelpers.php';

/** Aktor Admin: role terkunci + `semuaIzin` supaya lolos middleware `izin`. */
function aktingAdmin(): User
{
    $role = Role::factory()->terkunci()->create(['nama' => 'Admin '.Str::random(4)]);
    $admin = User::factory()->create(['role_id' => $role->id_role]);
    $admin->semuaIzin = true;

    test()->actingAs($admin);

    return $admin;
}

function roleSemua(): Role
{
    return Role::factory()->create(['cakupan_data' => CakupanData::Semua->value]);
}

function rolePerSp(): Role
{
    return Role::factory()->create(['cakupan_data' => CakupanData::PerSp->value]);
}

it('membuat akun dengan kata sandi sementara yang di-hash dan wajib diganti', function () {
    aktingAdmin();
    $role = roleSemua();

    $this->post(route('pengguna.simpan'), [
        'nama' => 'Nara Wijaya',
        'email' => 'nara@malakakab.go.id',
        'role_id' => $role->id_role,
        'jabatan' => 'Staf Bidang',
    ])->assertRedirect(route('pengguna.index'))
        ->assertSessionHas('kredensial_baru');

    $baru = User::where('email', 'nara@malakakab.go.id')->firstOrFail();

    expect($baru->password_harus_diganti)->toBeTrue()
        ->and($baru->is_aktif)->toBeTrue()
        // username sementara berformat sah; petugas menggantinya saat masuk pertama
        ->and($baru->username)->toStartWith('petugas.')
        ->and($baru->username)->toMatch('/^[a-z0-9._]{3,50}$/')
        ->and($baru->password)->not->toBe('') // ada hash
        ->and(Hash::needsRehash($baru->password))->toBeFalse();
});

it('tidak pernah menyimpan kata sandi apa adanya', function () {
    aktingAdmin();
    $role = roleSemua();

    $respons = $this->post(route('pengguna.simpan'), [
        'nama' => 'Siti Rahma',
        'email' => 'siti@malakakab.go.id',
        'role_id' => $role->id_role,
    ]);

    $sandi = $respons->getSession()->get('kredensial_baru')['password'];
    $baru = User::where('email', 'siti@malakakab.go.id')->firstOrFail();

    expect($baru->getAttributes()['password'])->not->toBe($sandi)
        ->and(Hash::check($sandi, $baru->password))->toBeTrue();
});

it('menolak akun Per SP tanpa penugasan SP', function () {
    aktingAdmin();
    $role = rolePerSp();

    $this->post(route('pengguna.simpan'), [
        'nama' => 'Yosep Klau',
        'email' => 'yosep@malakakab.go.id',
        'role_id' => $role->id_role,
    ])->assertSessionHasErrors('satuan_permukiman');

    expect(User::where('email', 'yosep@malakakab.go.id')->exists())->toBeFalse();
});

it('menyimpan penugasan SP untuk akun Per SP', function () {
    aktingAdmin();
    $role = rolePerSp();
    $sp = buatSp();

    $this->post(route('pengguna.simpan'), [
        'nama' => 'Yosep Klau',
        'email' => 'yosep@malakakab.go.id',
        'role_id' => $role->id_role,
        'satuan_permukiman' => [$sp->id_satuan_permukiman],
    ])->assertRedirect(route('pengguna.index'));

    $baru = User::where('email', 'yosep@malakakab.go.id')->firstOrFail();

    expect($baru->satuanPermukiman->pluck('id_satuan_permukiman')->all())
        ->toBe([$sp->id_satuan_permukiman]);
});

it('menolak email yang sudah dipakai akun lain', function () {
    aktingAdmin();
    $role = roleSemua();
    User::factory()->create(['email' => 'dobel@malakakab.go.id']);

    $this->post(route('pengguna.simpan'), [
        'nama' => 'Orang Baru',
        'email' => 'dobel@malakakab.go.id',
        'role_id' => $role->id_role,
    ])->assertSessionHasErrors('email');
});

it('memperbarui data akun tanpa menyentuh kata sandi', function () {
    aktingAdmin();
    $role = roleSemua();
    $target = User::factory()->create(['role_id' => $role->id_role, 'nama' => 'Nama Lama']);
    $hashLama = $target->password;

    $this->put(route('pengguna.perbarui', $target->id_user), [
        'nama' => 'Nama Baru',
        'email' => $target->email,
        'role_id' => $role->id_role,
    ])->assertRedirect(route('pengguna.index'));

    $target->refresh();
    expect($target->nama)->toBe('NAMA BARU') // UppercaseInput
        ->and($target->password)->toBe($hashLama);
});

it('menyetel ulang kata sandi: hash baru, wajib ganti, audit berjalur Admin', function () {
    aktingAdmin();
    $target = User::factory()->create(['password_harus_diganti' => false]);
    $hashLama = $target->password;

    $respons = $this->post(route('pengguna.setel-sandi', $target->id_user))
        ->assertRedirect(route('pengguna.index'))
        ->assertSessionHas('kredensial_baru');

    $target->refresh();
    $sandiBaru = $respons->getSession()->get('kredensial_baru')['password'];

    expect($target->password)->not->toBe($hashLama)
        ->and($target->password_harus_diganti)->toBeTrue()
        ->and(Hash::check($sandiBaru, $target->password))->toBeTrue();

    $audit = AuditLog::where('nama_tabel', 'user')->where('record_id', $target->id_user)
        ->where('aksi', AksiAuditLog::ResetKataSandi->value)->firstOrFail();

    expect($audit->data_baru['jalur'])->toBe('Admin');
});

it('menonaktifkan lalu mengaktifkan kembali akun beserta audit masing-masing', function () {
    aktingAdmin();
    $target = User::factory()->create();

    $this->post(route('pengguna.nonaktifkan', $target->id_user))->assertRedirect();
    expect($target->refresh()->is_aktif)->toBeFalse();

    $this->post(route('pengguna.aktifkan', $target->id_user))->assertRedirect();
    expect($target->refresh()->is_aktif)->toBeTrue();

    expect(AuditLog::where('record_id', $target->id_user)->where('aksi', AksiAuditLog::NonaktifkanAkun->value)->exists())->toBeTrue()
        ->and(AuditLog::where('record_id', $target->id_user)->where('aksi', AksiAuditLog::AktifkanAkun->value)->exists())->toBeTrue();
});

it('menolak menonaktifkan Admin aktif terakhir', function () {
    $admin = aktingAdmin(); // satu-satunya akun berrole terkunci

    $this->post(route('pengguna.nonaktifkan', $admin->id_user))->assertStatus(422);

    expect($admin->refresh()->is_aktif)->toBeTrue();
});

it('mengizinkan menonaktifkan Admin bila masih ada Admin aktif lain', function () {
    $admin = aktingAdmin();
    $adminLain = User::factory()->create(['role_id' => $admin->role_id]);

    $this->post(route('pengguna.nonaktifkan', $adminLain->id_user))->assertRedirect();

    expect($adminLain->refresh()->is_aktif)->toBeFalse();
});

it('mencatat audit Tambah saat akun dibuat', function () {
    $admin = aktingAdmin();
    $role = roleSemua();

    $this->post(route('pengguna.simpan'), [
        'nama' => 'Agus Prasetyo',
        'email' => 'agus@malakakab.go.id',
        'role_id' => $role->id_role,
    ]);

    $baru = User::where('email', 'agus@malakakab.go.id')->firstOrFail();
    $audit = AuditLog::where('nama_tabel', 'user')->where('record_id', $baru->id_user)
        ->where('aksi', AksiAuditLog::Tambah->value)->firstOrFail();

    expect($audit->user_id)->toBe($admin->id_user)
        ->and($audit->data_baru['email'])->toBe('agus@malakakab.go.id');
});
