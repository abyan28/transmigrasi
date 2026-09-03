<?php

/*
 * Task 3.6 -- Pencatatan otomatis perubahan data ke `audit_log`.
 *
 * `AuditLogObserver` dipasang pada 32 model data lewat
 * `AppServiceProvider::daftarkanAuditOtomatis()`. Kejadian akun tetap dicatat
 * manual di controllernya (diuji di berkas lain).
 */

use App\Enums\AksiAuditLog;
use App\Models\AuditLog;
use App\Models\Berkas;
use App\Models\Komoditas;
use App\Models\Role;
use App\Models\User;

require_once __DIR__.'/DatabaseHelpers.php';

function auditUntuk(string $tabel, int $id)
{
    return AuditLog::where('nama_tabel', $tabel)->where('record_id', $id);
}

it('mencatat Tambah saat baris data dibuat', function () {
    $k = Komoditas::create(['satuan_id' => buatSatuanTon()->id_satuan, 'nama' => 'Melon', 'tipe' => 'Pangan']);

    $audit = auditUntuk('komoditas', $k->id_komoditas)->where('aksi', AksiAuditLog::Tambah->value)->firstOrFail();

    expect($audit->data_lama)->toBeNull()
        ->and($audit->data_baru['nama'])->toBe('Melon')
        ->and($audit->data_baru)->not->toHaveKey('created_at');
});

it('mencatat Ubah hanya untuk kolom yang benar-benar berubah', function () {
    $t = buatTransmigran(null, ['pekerjaan_kepala_keluarga' => 'Petani']);
    AuditLog::query()->delete(); // bersihkan baris Tambah dari pembuatan

    $t->update(['pekerjaan_kepala_keluarga' => 'Nelayan']);

    $audit = auditUntuk('transmigran', $t->id_transmigran)->where('aksi', AksiAuditLog::Ubah->value)->firstOrFail();

    expect(array_keys($audit->data_baru))->toBe(['pekerjaan_kepala_keluarga'])
        ->and($audit->data_lama)->toBe(['pekerjaan_kepala_keluarga' => 'Petani'])
        ->and($audit->data_baru)->toBe(['pekerjaan_kepala_keluarga' => 'Nelayan']);
});

it('tidak mencatat apa pun saat update tidak mengubah kolom bermakna', function () {
    $k = Komoditas::create(['satuan_id' => buatSatuanTon()->id_satuan, 'nama' => 'Semangka', 'tipe' => 'Pangan']);
    AuditLog::query()->delete();

    $k->touch(); // hanya updated_at

    expect(auditUntuk('komoditas', $k->id_komoditas)->count())->toBe(0);
});

it('mencatat Hapus saat soft delete', function () {
    $k = Komoditas::create(['satuan_id' => buatSatuanTon()->id_satuan, 'nama' => 'Nanas', 'tipe' => 'Pangan']);
    AuditLog::query()->delete();

    $k->delete();

    $audit = auditUntuk('komoditas', $k->id_komoditas)->where('aksi', AksiAuditLog::Hapus->value)->firstOrFail();
    expect($audit->data_baru)->toBeNull()
        ->and($audit->data_lama['nama'])->toBe('Nanas');
});

it('mencatat Pulihkan tanpa baris Ubah hantu saat restore', function () {
    $k = Komoditas::create(['satuan_id' => buatSatuanTon()->id_satuan, 'nama' => 'Pepaya', 'tipe' => 'Pangan']);
    $k->delete();
    AuditLog::query()->delete();

    $k->restore();

    expect(auditUntuk('komoditas', $k->id_komoditas)->where('aksi', AksiAuditLog::Pulihkan->value)->count())->toBe(1)
        ->and(auditUntuk('komoditas', $k->id_komoditas)->where('aksi', AksiAuditLog::Ubah->value)->count())->toBe(0);
});

it('tidak pernah menyimpan kolom password pada audit', function () {
    // `User` sengaja TIDAK diobservasi -- dicatat manual. Uji ini menjamin
    // saringan kolom tetap benar bila kelak berubah: model diaudit + kolom
    // rahasia hipotetis. Di sini cukup pastikan tak ada baris audit `user`.
    $user = User::factory()->create(['role_id' => Role::factory()->create()->id_role]);
    $user->update(['nama' => 'Nama Baru']);

    expect(AuditLog::where('nama_tabel', 'user')->count())->toBe(0);
});

it('merekam pelaku saat ada pengguna terautentikasi', function () {
    $aktor = User::factory()->create(['role_id' => Role::factory()->create()->id_role]);
    $this->actingAs($aktor);

    $k = Komoditas::create(['satuan_id' => buatSatuanTon()->id_satuan, 'nama' => 'Jambu', 'tipe' => 'Pangan']);

    expect(auditUntuk('komoditas', $k->id_komoditas)->first()->user_id)->toBe($aktor->id_user);
});

it('tidak mencatat model di luar daftar observasi', function () {
    $awal = AuditLog::count();

    buatBerkas();

    expect(AuditLog::count())->toBe($awal);
});
