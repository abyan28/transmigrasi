<?php

/*
 * Task 3.11 -- Pemulihan kata sandi mandiri lewat kode verifikasi 6 digit.
 *
 * `DatabaseTestCase` (MySQL/MariaDB nyata): kode, percobaan, dan audit log
 * menyentuh tabel `kode_pemulihan_sandi`/`user`/`audit_log`. Surel di-`fake`.
 */

use App\Enums\AksiAuditLog;
use App\Mail\KodePemulihanSandiMail;
use App\Models\AuditLog;
use App\Models\KodePemulihanSandi;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

require_once __DIR__.'/DatabaseHelpers.php';

/** Meminta kode untuk `$user`, mengembalikan kode 6 digit yang dikirim. */
function mintaKode(User $user): string
{
    Mail::fake();

    test()->post(route('lupa-kata-sandi.kirim'), ['kredensial' => $user->email])
        ->assertRedirect(route('verifikasi-kode'));

    $kode = null;
    Mail::assertSent(KodePemulihanSandiMail::class, function ($mail) use (&$kode, $user) {
        $kode = $mail->kode;

        return $mail->hasTo($user->email);
    });

    return $kode;
}

it('membalas sama baik akun ada maupun tidak ada', function () {
    Mail::fake();
    $ada = User::factory()->create(['email' => 'ada@malakakab.go.id']);

    $r1 = $this->post(route('lupa-kata-sandi.kirim'), ['kredensial' => 'ada@malakakab.go.id']);
    $r2 = $this->post(route('lupa-kata-sandi.kirim'), ['kredensial' => 'hantu@malakakab.go.id']);

    $r1->assertRedirect(route('verifikasi-kode'));
    $r2->assertRedirect(route('verifikasi-kode'));
    $r1->assertSessionMissing('errors');
    $r2->assertSessionMissing('errors');

    expect(KodePemulihanSandi::where('user_id', $ada->id_user)->count())->toBe(1)
        ->and(KodePemulihanSandi::count())->toBe(1); // tak ada baris untuk akun hantu
});

it('menyimpan sidik kode, bukan angkanya', function () {
    $user = User::factory()->create();
    $kode = mintaKode($user);

    $baris = KodePemulihanSandi::where('user_id', $user->id_user)->firstOrFail();

    expect($baris->kode_hash)->not->toBe($kode)
        ->and(Hash::check($kode, $baris->kode_hash))->toBeTrue();
});

it('mereset kata sandi dengan kode sah tanpa menyetel password_harus_diganti', function () {
    $user = User::factory()->create(['password_harus_diganti' => false]);
    $hashLama = $user->password;
    $kode = mintaKode($user);

    $this->post(route('atur-ulang-sandi'), [
        'kode' => $kode,
        'password_baru' => 'RahasiaBaru9',
        'password_baru_konfirmasi' => 'RahasiaBaru9',
    ])->assertRedirect(route('login'));

    $user->refresh();
    expect(Hash::check('RahasiaBaru9', $user->password))->toBeTrue()
        ->and($user->password)->not->toBe($hashLama)
        ->and($user->password_harus_diganti)->toBeFalse();

    $audit = AuditLog::where('nama_tabel', 'user')->where('record_id', $user->id_user)
        ->where('aksi', AksiAuditLog::ResetKataSandi->value)->firstOrFail();
    expect($audit->data_baru['jalur'])->toBe('Kode verifikasi')
        ->and($audit->user_id)->toBe($user->id_user);
});

it('menandai kode terpakai sehingga tidak dapat dipakai dua kali', function () {
    $user = User::factory()->create();
    $kode = mintaKode($user);

    $this->post(route('atur-ulang-sandi'), [
        'kode' => $kode, 'password_baru' => 'RahasiaBaru9', 'password_baru_konfirmasi' => 'RahasiaBaru9',
    ])->assertRedirect(route('login'));

    expect(KodePemulihanSandi::where('user_id', $user->id_user)->first()->dipakai_pada)->not->toBeNull();

    $this->post(route('atur-ulang-sandi'), [
        'kode' => $kode, 'password_baru' => 'LagiLagi123', 'password_baru_konfirmasi' => 'LagiLagi123',
    ])->assertSessionHasErrors('kode');
});

it('menambah percobaan saat kode salah dan tidak mengubah kata sandi', function () {
    $user = User::factory()->create();
    $hashLama = $user->password;
    mintaKode($user);

    $this->post(route('atur-ulang-sandi'), [
        'kode' => '000000', 'password_baru' => 'RahasiaBaru9', 'password_baru_konfirmasi' => 'RahasiaBaru9',
    ])->assertSessionHasErrors('kode');

    $user->refresh();
    expect($user->password)->toBe($hashLama)
        ->and(KodePemulihanSandi::where('user_id', $user->id_user)->first()->percobaan)->toBe(1);
});

it('menghanguskan kode setelah 5 percobaan salah', function () {
    $user = User::factory()->create();
    $kode = mintaKode($user);

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('atur-ulang-sandi'), [
            'kode' => '111111', 'password_baru' => 'RahasiaBaru9', 'password_baru_konfirmasi' => 'RahasiaBaru9',
        ]);
    }

    // Kode yang benar pun tak lagi diterima.
    $this->post(route('atur-ulang-sandi'), [
        'kode' => $kode, 'password_baru' => 'RahasiaBaru9', 'password_baru_konfirmasi' => 'RahasiaBaru9',
    ])->assertSessionHasErrors('kode');

    expect(KodePemulihanSandi::where('user_id', $user->id_user)->first()->percobaan)->toBe(5);
});

it('menolak kode yang sudah kedaluwarsa', function () {
    $user = User::factory()->create();
    $kode = mintaKode($user);

    KodePemulihanSandi::where('user_id', $user->id_user)
        ->update(['kedaluwarsa_pada' => now()->subMinute()]);

    $this->post(route('atur-ulang-sandi'), [
        'kode' => $kode, 'password_baru' => 'RahasiaBaru9', 'password_baru_konfirmasi' => 'RahasiaBaru9',
    ])->assertSessionHasErrors('kode');
});

it('membatalkan kode lama ketika kode baru diminta', function () {
    $user = User::factory()->create();
    mintaKode($user);
    $lama = KodePemulihanSandi::where('user_id', $user->id_user)->firstOrFail();

    mintaKode($user);

    $lama->refresh();
    expect($lama->kedaluwarsa_pada->isPast())->toBeTrue()
        ->and(KodePemulihanSandi::where('user_id', $user->id_user)->count())->toBe(2);
});

it('membatasi permintaan kode 3 kali per jam per akun', function () {
    Mail::fake();
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('lupa-kata-sandi.kirim'), ['kredensial' => $user->email])
            ->assertRedirect(route('verifikasi-kode'));
    }

    expect(KodePemulihanSandi::where('user_id', $user->id_user)->count())->toBe(3);
});

it('tidak menerbitkan kode untuk akun nonaktif', function () {
    Mail::fake();
    $user = User::factory()->nonaktif()->create();

    $this->post(route('lupa-kata-sandi.kirim'), ['kredensial' => $user->email])
        ->assertRedirect(route('verifikasi-kode'));

    expect(KodePemulihanSandi::where('user_id', $user->id_user)->count())->toBe(0);
    Mail::assertNothingSent();
});

it('menolak atur ulang tanpa sesi permintaan', function () {
    $this->post(route('atur-ulang-sandi'), [
        'kode' => '123456', 'password_baru' => 'RahasiaBaru9', 'password_baru_konfirmasi' => 'RahasiaBaru9',
    ])->assertSessionHasErrors('kode');
});

it('menolak kata sandi baru yang lemah atau tidak sama', function () {
    $user = User::factory()->create();
    $kode = mintaKode($user);

    $this->post(route('atur-ulang-sandi'), [
        'kode' => $kode, 'password_baru' => 'pendek', 'password_baru_konfirmasi' => 'pendek',
    ])->assertSessionHasErrors('password_baru');

    $this->post(route('atur-ulang-sandi'), [
        'kode' => $kode, 'password_baru' => 'RahasiaBaru9', 'password_baru_konfirmasi' => 'BedaSekali9',
    ])->assertSessionHasErrors('password_baru_konfirmasi');
});
