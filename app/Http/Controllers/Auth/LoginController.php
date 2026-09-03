<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AksiAuditLog;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Masuk dan keluar sistem. Seluruh pengguna adalah petugas; tidak ada
 * pendaftaran mandiri dan tidak ada rute pemulihan sandi bawaan (`rules.md`
 * §14b). Pemulihan lewat kode verifikasi = Task 3.11.
 *
 * Task 3.2 (opsi "mekanik dulu"): logika masuk sudah berjalan penuh, tetapi
 * rute internal BELUM dibungkus `auth` -- itu Task 3.2b bersama penyesuaian
 * ±350 uji HTTP.
 */
class LoginController extends Controller
{
    /**
     * Kegagalan masuk yang ditoleransi sebelum diblokir sementara
     * (`rules.md` 14c.2: 5 kegagalan per menit, per IP dan akun).
     */
    private const MAKS_KEGAGALAN = 5;

    public function tampil(): View
    {
        return view('pages.auth.signin', ['title' => 'Masuk']);
    }

    /**
     * Menerima kredensial. Satu kolom menerima email ATAU username; pencocokan
     * dilakukan terhadap kedua kolom sekaligus (`rules.md` 14b poin 4).
     */
    public function masuk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kredensial' => ValidationRules::kredensialMasuk(),
            'password' => ['required', 'string'],
        ], [
            'kredensial.required' => 'Email atau username wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $kunci = $this->kunciThrottle($request);

        if (RateLimiter::tooManyAttempts($kunci, self::MAKS_KEGAGALAN)) {
            $detik = RateLimiter::availableIn($kunci);

            throw ValidationException::withMessages([
                'kredensial' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$detik} detik.",
            ]);
        }

        $pengguna = User::query()
            ->where('email', $data['kredensial'])
            ->orWhere('username', $data['kredensial'])
            ->first();

        // Akun dinonaktifkan: pesan KHUSUS, dibedakan dari kredensial salah,
        // sebab petugas perlu tahu harus menghubungi Admin bukan mencoba lagi
        // (`rules.md` §14b). Diperiksa sebelum kata sandi supaya pesannya tepat
        // meski kata sandinya benar.
        if ($pengguna !== null && ! $pengguna->is_aktif) {
            throw ValidationException::withMessages([
                'kredensial' => 'Akun Anda dinonaktifkan. Silakan hubungi Admin.',
            ]);
        }

        if ($pengguna === null || ! Hash::check($data['password'], $pengguna->password)) {
            RateLimiter::hit($kunci, 60);

            throw ValidationException::withMessages([
                'kredensial' => 'Email atau username dan kata sandi tidak cocok.',
            ]);
        }

        RateLimiter::clear($kunci);

        Auth::login($pengguna, $request->boolean('ingat_saya'));
        $request->session()->regenerate();

        $pengguna->forceFill(['last_login_at' => now()])->save();
        $this->catat($request, $pengguna, AksiAuditLog::Login);

        if ($pengguna->password_harus_diganti) {
            return redirect()->route('ganti-kata-sandi');
        }

        return redirect()->intended(route('beranda'));
    }

    public function keluar(Request $request): RedirectResponse
    {
        /** @var User|null $pengguna */
        $pengguna = Auth::user();

        if ($pengguna !== null) {
            $this->catat($request, $pengguna, AksiAuditLog::Logout);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('sukses', 'Anda sudah keluar dari sistem.');
    }

    /**
     * Kunci throttle: kredensial (huruf kecil) digabung IP, mengikuti pola
     * bawaan Laravel. Hanya kegagalan yang menaikkannya (`rules.md` 14c.2).
     */
    private function kunciThrottle(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('kredensial')).'|'.$request->ip());
    }

    private function catat(Request $request, User $pengguna, AksiAuditLog $aksi): void
    {
        AuditLog::create([
            'user_id' => $pengguna->id_user,
            'aksi' => $aksi,
            'nama_tabel' => 'user',
            'record_id' => $pengguna->id_user,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
        ]);
    }
}
