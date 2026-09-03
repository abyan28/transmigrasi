<?php

/*
 * Task 3.13 -- Profil pengguna & ubah kata sandi.
 *
 * Grup Database sebab menyentuh `unique:user,email`, `current_password`, dan
 * hashing -- ketiganya diuji terhadap MySQL nyata + pengguna persisted.
 */

use App\Enums\AksiAuditLog;
use App\Enums\CakupanData;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionRoleSeeder;
use Illuminate\Support\Facades\Hash;

require_once __DIR__.'/DatabaseHelpers.php';

function petugasProfil(array $atribut = []): User
{
    $petugas = User::factory()->create($atribut);
    test()->actingAs($petugas);

    return $petugas;
}

it('memutakhirkan email dan telepon serta mencatat audit Ubah', function () {
    $petugas = petugasProfil(['email' => 'lama@malakakab.go.id', 'telepon' => '081200000001']);

    $this->put(route('profil.simpan'), [
        'email' => 'baru@malakakab.go.id',
        'telepon' => '081234567890',
    ])->assertRedirect()->assertSessionHas('sukses');

    $petugas->refresh();
    expect($petugas->email)->toBe('baru@malakakab.go.id')
        ->and($petugas->telepon)->toBe('081234567890');

    $audit = AuditLog::where('nama_tabel', 'user')->where('record_id', $petugas->id_user)
        ->where('aksi', AksiAuditLog::Ubah->value)->firstOrFail();
    expect($audit->user_id)->toBe($petugas->id_user)
        ->and($audit->data_baru['sesudah']['email'])->toBe('baru@malakakab.go.id');
});

it('menolak email yang sudah dipakai akun lain', function () {
    petugasProfil(['email' => 'saya@malakakab.go.id']);
    User::factory()->create(['email' => 'dipakai@malakakab.go.id']);

    $this->put(route('profil.simpan'), ['email' => 'dipakai@malakakab.go.id'])
        ->assertSessionHasErrors('email');
});

it('menerima email yang tidak berubah (mengabaikan diri sendiri)', function () {
    $petugas = petugasProfil(['email' => 'tetap@malakakab.go.id']);

    $this->put(route('profil.simpan'), ['email' => 'tetap@malakakab.go.id', 'telepon' => '081211112222'])
        ->assertRedirect()->assertSessionHasNoErrors();

    expect($petugas->refresh()->telepon)->toBe('081211112222');
});

it('menolak format telepon yang salah', function () {
    petugasProfil();

    $this->put(route('profil.simpan'), ['email' => 'x@malakakab.go.id', 'telepon' => '12345'])
        ->assertSessionHasErrors('telepon');
});

it('menolak ubah kata sandi bila kata sandi lama salah', function () {
    $petugas = petugasProfil();
    $hashLama = $petugas->password;

    $this->put(route('profil.kata-sandi.simpan'), [
        'password_lama' => 'salah-sekali',
        'password' => 'RahasiaBaru9',
        'password_confirmation' => 'RahasiaBaru9',
    ])->assertSessionHasErrors('password_lama');

    expect($petugas->refresh()->password)->toBe($hashLama);
});

it('mengganti kata sandi: hash baru, TANPA wajib-ganti, audit jalur Mandiri', function () {
    $petugas = petugasProfil(['password_harus_diganti' => false]);
    $hashLama = $petugas->password;

    $this->put(route('profil.kata-sandi.simpan'), [
        'password_lama' => 'password', // bawaan UserFactory
        'password' => 'RahasiaBaru9',
        'password_confirmation' => 'RahasiaBaru9',
    ])->assertRedirect(route('profil'))->assertSessionHas('sukses');

    $petugas->refresh();
    expect(Hash::check('RahasiaBaru9', $petugas->password))->toBeTrue()
        ->and($petugas->password)->not->toBe($hashLama)
        ->and($petugas->password_harus_diganti)->toBeFalse();

    $audit = AuditLog::where('nama_tabel', 'user')->where('record_id', $petugas->id_user)
        ->where('aksi', AksiAuditLog::ResetKataSandi->value)->firstOrFail();
    expect($audit->data_baru['jalur'])->toBe('Mandiri')
        ->and($audit->user_id)->toBe($petugas->id_user);
});

it('menolak kata sandi baru yang lemah atau tidak sama', function () {
    petugasProfil();

    $this->put(route('profil.kata-sandi.simpan'), [
        'password_lama' => 'password', 'password' => 'pendek', 'password_confirmation' => 'pendek',
    ])->assertSessionHasErrors('password');

    $this->put(route('profil.kata-sandi.simpan'), [
        'password_lama' => 'password', 'password' => 'RahasiaBaru9', 'password_confirmation' => 'BedaSekali9',
    ])->assertSessionHasErrors('password');
});

it('merender profil untuk Operator SP tanpa penugasan (cakupan Per SP)', function () {
    $this->seed(PermissionRoleSeeder::class);
    $operator = User::factory()->create(['role_id' => 4]); // Operator SP, Per SP

    expect(Role::find(4)->cakupan_data)->toBe(CakupanData::PerSp);

    $this->actingAs($operator)->get(route('profil'))->assertOk()
        ->assertSee($operator->nama);
});
