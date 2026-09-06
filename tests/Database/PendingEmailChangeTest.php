<?php

use App\Enums\AksiAuditLog;
use App\Mail\AccountChangeNoticeMail;
use App\Mail\PendingEmailChangeMail;
use App\Models\AuditLog;
use App\Models\KodePemulihanSandi;
use App\Models\PendingEmailChange;
use App\Models\Role;
use App\Models\User;
use App\Support\PendingEmailChangeService;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

function stage3Admin(): User
{
    $role = Role::factory()->terkunci()->create(['nama' => 'Admin '.Str::random(5)]);
    $admin = User::factory()->create(['role_id' => $role->id_role]);
    $admin->semuaIzin = true;
    test()->actingAs($admin);

    return $admin;
}

function issueEmailChange(User $user, string $email): string
{
    Mail::fake();
    app(PendingEmailChangeService::class)->request($user, $email);
    $token = null;

    Mail::assertQueued(PendingEmailChangeMail::class, function ($mail) use (&$token, $email) {
        if (! $mail->hasTo($email)) {
            return false;
        }

        $token = $mail->token;

        return true;
    });

    return $token;
}

beforeEach(function () {
    Mail::fake();
});

it('queues encrypted verification and account notice mail', function () {
    $token = str_repeat('a', 64);
    $verification = new PendingEmailChangeMail('Nama', 'baru@example.test', $token);
    $notice = new AccountChangeNoticeMail('Nama', 'Akun berubah.');

    expect($verification)
        ->toBeInstanceOf(ShouldQueue::class)
        ->toBeInstanceOf(ShouldBeEncrypted::class)
        ->and($verification->render())->toContain(route('email-change.show', $token))
        ->and($notice)
        ->toBeInstanceOf(ShouldQueue::class)
        ->toBeInstanceOf(ShouldBeEncrypted::class)
        ->and($notice->render())->toContain('Akun berubah.');
});

it('keeps a permanent account email and password pending until POST while GET is non-consuming', function () {
    $user = User::factory()->create([
        'email' => 'old@malakakab.go.id',
        'password_harus_diganti' => false,
    ]);
    $oldHash = $user->password;
    $token = issueEmailChange($user, 'new@malakakab.go.id');
    $pending = PendingEmailChange::where('user_id', $user->id_user)->firstOrFail();

    expect($pending->token_hash)->toBe(hash('sha256', $token))
        ->and($pending->token_hash)->not->toBe($token)
        ->and($user->refresh()->email)->toBe('old@malakakab.go.id');

    $this->get(route('email-change.show', $token))->assertOk()->assertSee('new@malakakab.go.id');

    expect($pending->refresh()->used_at)->toBeNull()
        ->and($user->refresh()->email)->toBe('old@malakakab.go.id');

    $this->post(route('email-change.confirm', $token))->assertRedirect(route('login'));

    expect($pending->refresh()->used_at)->not->toBeNull()
        ->and($user->refresh()->email)->toBe('new@malakakab.go.id')
        ->and($user->password)->toBe($oldHash);

    Mail::assertQueued(AccountChangeNoticeMail::class, fn ($mail) => $mail->hasTo('old@malakakab.go.id'));
});

it('rotates a temporary credential at request then requires permanent password and username', function () {
    $user = User::factory()->create([
        'email' => 'temporary-old@malakakab.go.id',
        'username' => User::AWALAN_USERNAME_SEMENTARA.'lama1234',
        'password' => 'Temporary123',
        'password_harus_diganti' => true,
        'remember_token' => 'remember-temporary',
    ]);
    DB::table('sessions')->insert([
        'id' => 'temporary-session',
        'user_id' => $user->id_user,
        'payload' => 'payload',
        'last_activity' => now()->timestamp,
    ]);
    config(['session.driver' => 'database', 'session.connection' => 'mysql_testing']);
    $oldHash = $user->password;
    $token = issueEmailChange($user, 'temporary-new@malakakab.go.id');

    expect($user->refresh()->password)->not->toBe($oldHash)
        ->and(Hash::check('Temporary123', $user->password))->toBeFalse()
        ->and($user->password_harus_diganti)->toBeTrue()
        ->and($user->remember_token)->not->toBe('remember-temporary')
        ->and(DB::table('sessions')->where('user_id', $user->id_user)->exists())->toBeFalse();

    $this->post(route('email-change.confirm', $token), [
        'username' => 'nara.permanen',
        'password' => 'Permanen123',
        'password_confirmation' => 'Permanen123',
    ])->assertRedirect(route('login'));

    $user->refresh();
    expect($user->email)->toBe('temporary-new@malakakab.go.id')
        ->and($user->username)->toBe('nara.permanen')
        ->and(Hash::check('Permanen123', $user->password))->toBeTrue()
        ->and($user->password_harus_diganti)->toBeFalse();
});

it('invalidates the old token when the same profile email change is requested again', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->put(route('profil.simpan'), [
        'email' => 'resend@malakakab.go.id',
        'telepon' => $user->telepon,
        'current_password' => 'password',
    ])->assertSessionHasNoErrors();
    $oldToken = null;
    Mail::assertQueued(PendingEmailChangeMail::class, function ($mail) use (&$oldToken) {
        $oldToken = $mail->token;

        return true;
    });
    $oldPending = PendingEmailChange::where('user_id', $user->id_user)->firstOrFail();

    Mail::fake();
    $this->put(route('profil.simpan'), [
        'email' => 'resend@malakakab.go.id',
        'telepon' => $user->telepon,
        'current_password' => 'password',
    ])->assertSessionHasNoErrors();
    $newToken = null;
    Mail::assertQueued(PendingEmailChangeMail::class, function ($mail) use (&$newToken) {
        $newToken = $mail->token;

        return true;
    });

    expect($oldPending->refresh()->cancelled_at)->not->toBeNull()
        ->and(PendingEmailChange::where('user_id', $user->id_user)->valid()->count())->toBe(1);

    $this->post(route('email-change.confirm', $oldToken))->assertSessionHasErrors('token');
    expect($user->refresh()->email)->not->toBe('resend@malakakab.go.id');

    $this->post(route('email-change.confirm', $newToken))->assertRedirect(route('login'));
    expect($user->refresh()->email)->toBe('resend@malakakab.go.id');
});

it('handles expired used and unknown tokens with the same generic message', function () {
    $expiredUser = User::factory()->create();
    $expiredToken = issueEmailChange($expiredUser, 'expired@malakakab.go.id');
    PendingEmailChange::where('user_id', $expiredUser->id_user)->update(['expires_at' => now()->subMinute()]);

    $usedUser = User::factory()->create();
    $usedToken = issueEmailChange($usedUser, 'used@malakakab.go.id');
    PendingEmailChange::where('user_id', $usedUser->id_user)->update(['used_at' => now()]);

    foreach ([$expiredToken, $usedToken, str_repeat('f', 64)] as $token) {
        $this->get(route('email-change.show', $token))
            ->assertOk()
            ->assertSee('Tautan verifikasi tidak valid atau sudah kedaluwarsa.');
        $this->post(route('email-change.confirm', $token))
            ->assertSessionHasErrors(['token' => 'Tautan verifikasi tidak valid atau sudah kedaluwarsa.']);
    }
});

it('revokes recovery codes database sessions and remember token then audits without secrets', function () {
    $user = User::factory()->create([
        'email' => 'secure-old@malakakab.go.id',
        'remember_token' => 'remember-old',
    ]);
    KodePemulihanSandi::create([
        'user_id' => $user->id_user,
        'kode_hash' => Hash::make('123456'),
        'kedaluwarsa_pada' => now()->addMinutes(15),
        'percobaan' => 0,
    ]);
    DB::table('sessions')->insert([
        'id' => 'target-session',
        'user_id' => $user->id_user,
        'payload' => 'payload',
        'last_activity' => now()->timestamp,
    ]);
    config(['session.driver' => 'database', 'session.connection' => 'mysql_testing']);
    $token = issueEmailChange($user, 'secure-new@malakakab.go.id');
    $this->actingAs($user);

    $this->post(route('email-change.confirm', $token))->assertRedirect(route('login'));
    $this->assertGuest();

    $audit = AuditLog::where('record_id', $user->id_user)
        ->where('aksi', AksiAuditLog::Ubah->value)
        ->latest('id_audit_log')
        ->firstOrFail();
    $auditJson = json_encode([$audit->data_lama, $audit->data_baru]);

    expect(KodePemulihanSandi::where('user_id', $user->id_user)->whereNull('dipakai_pada')->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('user_id', $user->id_user)->exists())->toBeFalse()
        ->and($user->refresh()->remember_token)->not->toBe('remember-old')
        ->and($auditJson)->not->toContain($token)
        ->and($auditJson)->not->toContain('password')
        ->and($audit->data_baru['email'])->toBe('secure-new@malakakab.go.id');
});

it('requires the current password for a self email change', function () {
    $user = User::factory()->create(['email' => 'self-old@malakakab.go.id']);
    $this->actingAs($user);

    $this->put(route('profil.simpan'), [
        'email' => 'self-new@malakakab.go.id',
        'telepon' => $user->telepon,
    ])->assertSessionHasErrors('current_password');

    expect($user->refresh()->email)->toBe('self-old@malakakab.go.id')
        ->and(PendingEmailChange::where('user_id', $user->id_user)->exists())->toBeFalse();

    $this->put(route('profil.simpan'), [
        'email' => 'self-new@malakakab.go.id',
        'telepon' => $user->telepon,
        'current_password' => 'password',
    ])->assertSessionHasNoErrors();

    expect($user->refresh()->email)->toBe('self-old@malakakab.go.id')
        ->and(PendingEmailChange::where('user_id', $user->id_user)->valid()->exists())->toBeTrue();
});

it('applies a phone-only self change immediately and sends only an account notice', function () {
    $user = User::factory()->create([
        'email' => 'phone@malakakab.go.id',
        'telepon' => '081200000001',
    ]);
    $this->actingAs($user);

    $this->put(route('profil.simpan'), [
        'email' => $user->email,
        'telepon' => '081234567890',
    ])->assertSessionHasNoErrors();

    expect($user->refresh()->telepon)->toBe('081234567890')
        ->and(PendingEmailChange::where('user_id', $user->id_user)->exists())->toBeFalse();
    Mail::assertQueued(AccountChangeNoticeMail::class, fn ($mail) => $mail->hasTo('phone@malakakab.go.id'));
    Mail::assertNotQueued(PendingEmailChangeMail::class);
});

it('saves admin non-email fields immediately and sends a notice without pending email', function () {
    stage3Admin();
    $role = Role::factory()->create();
    $user = User::factory()->create([
        'role_id' => $role->id_role,
        'email' => 'unchanged@malakakab.go.id',
        'nama' => 'Nama Lama',
    ]);

    $this->put(route('pengguna.perbarui', $user->id_user), [
        'nama' => 'Nama Baru',
        'email' => $user->email,
        'role_id' => $role->id_role,
        'telepon' => '081234567890',
    ])->assertSessionHasNoErrors();

    expect($user->refresh()->nama)->toBe('NAMA BARU')
        ->and(PendingEmailChange::where('user_id', $user->id_user)->exists())->toBeFalse();
    Mail::assertQueued(AccountChangeNoticeMail::class, fn ($mail) => $mail->hasTo('unchanged@malakakab.go.id'));
    Mail::assertNotQueued(PendingEmailChangeMail::class);
});

it('saves admin non-email fields immediately and leaves changed email pending', function () {
    stage3Admin();
    $role = Role::factory()->create();
    $user = User::factory()->create([
        'role_id' => $role->id_role,
        'email' => 'admin-target-old@malakakab.go.id',
        'nama' => 'Nama Lama',
    ]);

    $this->put(route('pengguna.perbarui', $user->id_user), [
        'nama' => 'Nama Baru',
        'email' => 'admin-target-new@malakakab.go.id',
        'role_id' => $role->id_role,
        'telepon' => '081234567890',
    ])->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->nama)->toBe('NAMA BARU')
        ->and($user->telepon)->toBe('081234567890')
        ->and($user->email)->toBe('admin-target-old@malakakab.go.id')
        ->and(PendingEmailChange::where('user_id', $user->id_user)->value('new_email'))
        ->toBe('admin-target-new@malakakab.go.id');

    Mail::assertQueued(PendingEmailChangeMail::class, fn ($mail) => $mail->hasTo('admin-target-new@malakakab.go.id'));
    Mail::assertQueued(AccountChangeNoticeMail::class, fn ($mail) => $mail->hasTo('admin-target-old@malakakab.go.id'));
});
