<?php

/*
 * Task 3.2 -- Masuk, keluar, throttle, dan ganti kata sandi wajib.
 *
 * Berjalan di MySQL/MariaDB nyata (`DatabaseTestCase`): `Auth::attempt`,
 * `is_aktif`, dan audit log semuanya menyentuh tabel `user`/`audit_log`.
 * 732 uji lama tetap di SQLite dan tidak terpengaruh -- rute internal BELUM
 * dibungkus `auth` (opsi "mekanik dulu"; penegakan = Task 3.2b).
 */

use App\Enums\AksiAuditLog;
use App\Http\Middleware\PastikanGantiKataSandi;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/** Kata sandi baku yang di-`Hash` UserFactory. */
function sandiUji(): string
{
    return 'password';
}

/*
|--------------------------------------------------------------------------
| Masuk
|--------------------------------------------------------------------------
*/

it('menerima masuk memakai email', function () {
    $user = User::factory()->create(['email' => 'nara@malakakab.go.id']);

    $this->post(route('login.kirim'), ['kredensial' => 'nara@malakakab.go.id', 'password' => sandiUji()])
        ->assertRedirect(route('beranda'));

    expect(Auth::check())->toBeTrue()
        ->and(Auth::id())->toBe($user->id_user);
});

it('menerima masuk memakai username', function () {
    $user = User::factory()->create(['username' => 'nara.wijaya']);

    $this->post(route('login.kirim'), ['kredensial' => 'nara.wijaya', 'password' => sandiUji()])
        ->assertRedirect(route('beranda'));

    expect(Auth::id())->toBe($user->id_user);
});

it('mencatat last_login_at dan meregenerasi sesi saat masuk berhasil', function () {
    $user = User::factory()->create(['last_login_at' => null]);
    $sesiLama = session()->getId();

    $this->post(route('login.kirim'), ['kredensial' => $user->email, 'password' => sandiUji()]);

    expect($user->fresh()->last_login_at)->not->toBeNull()
        ->and(session()->getId())->not->toBe($sesiLama);
});

it('menolak akun nonaktif dengan pesan khusus, bukan "tidak cocok"', function () {
    $user = User::factory()->nonaktif()->create();

    $this->post(route('login.kirim'), ['kredensial' => $user->email, 'password' => sandiUji()])
        ->assertSessionHasErrors(['kredensial' => 'Akun Anda dinonaktifkan. Silakan hubungi Admin.']);

    expect(Auth::check())->toBeFalse();
});

it('menolak kata sandi salah, mempertahankan isian kredensial', function () {
    $user = User::factory()->create();

    $this->post(route('login.kirim'), ['kredensial' => $user->email, 'password' => 'salahsemua'])
        ->assertSessionHasErrors('kredensial')
        ->assertSessionHasInput('kredensial', $user->email);

    expect(Auth::check())->toBeFalse();
    expect(session('errors')->first('kredensial'))->not->toContain('dinonaktifkan');
});

it('mengarahkan ke ganti kata sandi bila password_harus_diganti', function () {
    $user = User::factory()->harusGantiSandi()->create();

    $this->post(route('login.kirim'), ['kredensial' => $user->email, 'password' => sandiUji()])
        ->assertRedirect(route('ganti-kata-sandi'));

    expect(Auth::id())->toBe($user->id_user);
});

/*
|--------------------------------------------------------------------------
| Throttle (rules.md 14c.2: 5 kegagalan per menit)
|--------------------------------------------------------------------------
*/

it('memblokir setelah lima kegagalan masuk beruntun', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $_) {
        $this->post(route('login.kirim'), ['kredensial' => $user->email, 'password' => 'salah'])
            ->assertSessionHasErrors('kredensial');
    }

    $this->post(route('login.kirim'), ['kredensial' => $user->email, 'password' => sandiUji()])
        ->assertSessionHasErrors('kredensial');

    // Kata sandi kali ini benar, tetapi tetap ditolak karena throttle.
    expect(Auth::check())->toBeFalse()
        ->and(session('errors')->first('kredensial'))->toContain('Terlalu banyak percobaan');
});

it('membersihkan hitungan throttle setelah masuk berhasil', function () {
    $user = User::factory()->create();

    foreach (range(1, 3) as $_) {
        $this->post(route('login.kirim'), ['kredensial' => $user->email, 'password' => 'salah']);
    }
    $this->post(route('login.kirim'), ['kredensial' => $user->email, 'password' => sandiUji()]);

    $kunci = strtolower($user->email).'|127.0.0.1';
    expect(RateLimiter::attempts($kunci))->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Keluar
|--------------------------------------------------------------------------
*/

it('mengeluarkan pengguna dan menginvalidasi sesi', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('sukses');

    expect(Auth::check())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Ganti kata sandi wajib
|--------------------------------------------------------------------------
*/

it('menyimpan kata sandi baru dan mengosongkan flag wajib ganti', function () {
    $user = User::factory()->harusGantiSandi()->create();

    $this->actingAs($user)->post(route('ganti-kata-sandi.simpan'), [
        'password' => 'sandibaru123',
        'password_confirmation' => 'sandibaru123',
    ])->assertRedirect(route('beranda'));

    $segar = $user->fresh();
    expect(Hash::check('sandibaru123', $segar->password))->toBeTrue()
        ->and($segar->password_harus_diganti)->toBeFalse();
});

it('menolak kata sandi baru yang tidak memuat angka', function () {
    $user = User::factory()->harusGantiSandi()->create();

    $this->actingAs($user)->post(route('ganti-kata-sandi.simpan'), [
        'password' => 'hanyahuruf',
        'password_confirmation' => 'hanyahuruf',
    ])->assertSessionHasErrors('password');

    expect($user->fresh()->password_harus_diganti)->toBeTrue();
});

it('mengarahkan tamu yang menyimpan ganti kata sandi ke halaman masuk', function () {
    $this->post(route('ganti-kata-sandi.simpan'), [
        'password' => 'sandibaru123',
        'password_confirmation' => 'sandibaru123',
    ])->assertRedirect(route('login'));
});

/*
|--------------------------------------------------------------------------
| Audit log
|--------------------------------------------------------------------------
*/

it('mencatat audit log Login, Logout, dan Reset Kata Sandi', function () {
    $user = User::factory()->harusGantiSandi()->create();

    $this->post(route('login.kirim'), ['kredensial' => $user->email, 'password' => sandiUji()]);
    $this->actingAs($user)->post(route('ganti-kata-sandi.simpan'), [
        'password' => 'sandibaru123', 'password_confirmation' => 'sandibaru123',
    ]);
    $this->actingAs($user)->post(route('logout'));

    $aksi = AuditLog::where('user_id', $user->id_user)->pluck('aksi');

    expect($aksi)->toContain(AksiAuditLog::Login)
        ->toContain(AksiAuditLog::ResetKataSandi)
        ->toContain(AksiAuditLog::Logout);

    $login = AuditLog::where('user_id', $user->id_user)->where('aksi', AksiAuditLog::Login)->first();
    expect($login->nama_tabel)->toBe('user')
        ->and($login->record_id)->toBe($user->id_user)
        ->and($login->ip_address)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Middleware PastikanGantiKataSandi -- unit (dilampirkan ke rute internal di C2)
|--------------------------------------------------------------------------
*/

it('mengalihkan pengguna berflag wajib-ganti dari rute biasa', function () {
    $user = new User(['password_harus_diganti' => true]);
    $request = Request::create('/beranda');
    $request->setUserResolver(fn () => $user);

    $respons = (new PastikanGantiKataSandi)->handle($request, fn () => new Response('ok'));

    expect($respons->getStatusCode())->toBe(302)
        ->and($respons->headers->get('Location'))->toBe(route('ganti-kata-sandi'));
});

it('meloloskan rute ganti kata sandi dan logout meski flag menyala', function () {
    $user = new User(['password_harus_diganti' => true]);

    foreach (['ganti-kata-sandi', 'ganti-kata-sandi.simpan', 'logout'] as $nama) {
        $request = Request::create('/apa-saja');
        $rute = (new RoutingRoute(['GET'], '/apa-saja', []))->name($nama);
        $request->setRouteResolver(fn () => $rute);
        $request->setUserResolver(fn () => $user);

        $respons = (new PastikanGantiKataSandi)->handle($request, fn () => new Response('ok'));

        expect($respons->getContent())->toBe('ok');
    }
});

it('meloloskan tamu dan pengguna tanpa flag', function () {
    $tamu = Request::create('/beranda');
    $tamu->setUserResolver(fn () => null);
    expect((new PastikanGantiKataSandi)->handle($tamu, fn () => new Response('ok'))->getContent())->toBe('ok');

    $biasa = Request::create('/beranda');
    $biasa->setUserResolver(fn () => new User(['password_harus_diganti' => false]));
    expect((new PastikanGantiKataSandi)->handle($biasa, fn () => new Response('ok'))->getContent())->toBe('ok');
});

/*
|--------------------------------------------------------------------------
| Penegakan rute (Task 3.2b) -- `auth` + `guest` + `pastikan.ganti.sandi`
| terpasang di routes/internal.php / routes/web.php. Env uji = `testing`,
| jadi MasukOtomatisLokal tidak aktif.
|--------------------------------------------------------------------------
*/

it('mengalihkan tamu dari rute internal ke halaman masuk', function () {
    $this->get(route('beranda'))->assertRedirect(route('login'));
    $this->get(route('transmigran.index'))->assertRedirect(route('login'));
});

it('meloloskan pengguna yang sudah masuk ke rute internal', function () {
    // Uji ini menjaga pintu `auth`, bukan `izin` (Task 3.3) -- pakai pengguna
    // bertanda `semuaIzin` supaya `izin:dashboard,lihat` tak ikut menolak.
    $user = User::factory()->create();
    $user->semuaIzin = true;

    $this->actingAs($user)->get(route('beranda'))->assertOk();
});

it('mengunci pengguna berkata-sandi-sementara ke halaman ganti kata sandi', function () {
    $user = User::factory()->harusGantiSandi()->create();

    $this->actingAs($user)->get(route('beranda'))->assertRedirect(route('ganti-kata-sandi'));
    // Halaman ganti sandi sendiri + keluar tetap dapat dibuka.
    $this->actingAs($user)->get(route('ganti-kata-sandi'))->assertOk();
    $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));
});

it('mengalihkan pengguna yang sudah masuk dari halaman masuk ke beranda', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('login'))
        ->assertRedirect('/');
});
