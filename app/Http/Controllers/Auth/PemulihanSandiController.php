<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AksiAuditLog;
use App\Http\Controllers\Controller;
use App\Mail\KodePemulihanSandiMail;
use App\Models\AuditLog;
use App\Models\KodePemulihanSandi;
use App\Models\User;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Pemulihan kata sandi mandiri lewat kode verifikasi 6 digit (Task 3.11,
 * `rules.md` 14b poin 7-15).
 *
 * Jalur ini PELENGKAP, bukan pengganti jalur Admin (`PengaturanPenggunaController`
 * ::setelSandi + `sim:pulihkan-admin`). Prinsip yang dijaga:
 *
 * - `POST /lupa-kata-sandi` MEMBALAS SAMA baik akun ada maupun tidak (poin 9):
 *   redirect identik, dan kerja bcrypt tetap dijalankan walau akun tak ada
 *   supaya selisih waktu tidak membocorkan keberadaan akun.
 * - Kode disimpan sebagai SIDIK (`Hash::make`), bukan angkanya (poin 7).
 * - Kode: 6 digit, berlaku 15 menit, sekali pakai, maksimal 5 percobaan,
 *   maksimal 3 permintaan per jam per akun (poin 8, 10).
 * - Kode lama dibatalkan begitu kode baru diminta (poin 8).
 * - Reset lewat kode TIDAK menyetel `password_harus_diganti` -- petugas sudah
 *   memilih sandi finalnya di sini (keputusan pemilik proyek 2026-09-03;
 *   `rules.md` 14b poin 13 hanya berlaku untuk sandi sementara Admin).
 * - Setiap reset tercatat audit `Reset Kata Sandi` atas nama pemilik akun,
 *   jalur `Kode verifikasi` (poin 15).
 */
class PemulihanSandiController extends Controller
{
    private const MENIT_BERLAKU = 15;

    private const MAKS_PERMINTAAN_PER_JAM = 3;

    /** Pesan tunggal untuk semua kegagalan verifikasi -- tidak membedakan sebab. */
    private const PESAN_KODE_TAK_SAH = 'Kode salah atau sudah kedaluwarsa. Periksa kembali atau minta kode baru.';

    public function tampilPermintaan(): View
    {
        return view('pages.auth.lupa-kata-sandi', ['title' => 'Lupa Kata Sandi']);
    }

    public function kirimKode(Request $request): RedirectResponse
    {
        $data = $request->validate(
            ['kredensial' => ['required', 'string', 'max:255']],
            ['kredensial.required' => 'Email atau username wajib diisi.'],
        );

        $pengguna = User::query()
            ->where('is_aktif', true)
            ->where(fn ($q) => $q->where('email', $data['kredensial'])->orWhere('username', $data['kredensial']))
            ->first();

        $kode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        if ($pengguna === null) {
            // Akun tak ada: tetap bekerja setara (bcrypt) agar waktu balas
            // tidak membocorkan keberadaan akun (`rules.md` 14b poin 9).
            Hash::make($kode);
            $request->session()->forget('pemulihan_user_id');

            return $this->keVerifikasi();
        }

        if ($this->melebihiBatasPermintaan($pengguna)) {
            // Diam-diam tidak menerbitkan kode baru; balasan tetap sama.
            $request->session()->put('pemulihan_user_id', $pengguna->id_user);

            return $this->keVerifikasi();
        }

        // Batalkan kode lama yang belum dipakai (poin 8: tidak boleh ada dua
        // kode sah beredar bersamaan).
        KodePemulihanSandi::query()
            ->where('user_id', $pengguna->id_user)
            ->whereNull('dipakai_pada')
            ->where('kedaluwarsa_pada', '>', now())
            ->update(['kedaluwarsa_pada' => now()]);

        KodePemulihanSandi::create([
            'user_id' => $pengguna->id_user,
            'kode_hash' => Hash::make($kode),
            'kedaluwarsa_pada' => now()->addMinutes(self::MENIT_BERLAKU),
            'percobaan' => 0,
        ]);

        try {
            // Diantre (bukan `->send()` langsung): permintaan HTTP tidak boleh
            // menunggu SMTP, apalagi di jalur ini yang dapat dipicu tamu tanpa
            // sesi masuk. `KodePemulihanSandiMail` ber-`ShouldBeEncrypted`
            // sebab payload antrean membawa kode pemulihan mentah.
            Mail::to($pengguna->email)->queue(new KodePemulihanSandiMail($kode, self::MENIT_BERLAKU));
        } catch (\Throwable $e) {
            // Gangguan penulisan antrean tidak boleh jadi 500 yang membocorkan
            // keberadaan akun. Petugas dapat minta kode ulang atau menempuh
            // jalur Admin.
            Log::error('Gagal mengantre kode pemulihan sandi: '.$e->getMessage());
        }

        $request->session()->put('pemulihan_user_id', $pengguna->id_user);

        return $this->keVerifikasi();
    }

    public function tampilVerifikasi(): View
    {
        return view('pages.auth.verifikasi-kode', ['title' => 'Masukkan Kode Verifikasi']);
    }

    public function aturUlang(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'digits:6'],
            'password_baru' => ValidationRules::password(konfirmasi: false),
            'password_baru_konfirmasi' => ['required', 'same:password_baru'],
        ], [
            'kode.required' => 'Kode verifikasi wajib diisi.',
            'kode.digits' => 'Kode verifikasi terdiri dari 6 angka.',
            'password_baru.required' => 'Kata sandi baru wajib diisi.',
            'password_baru.min' => 'Kata sandi minimal 8 karakter.',
            'password_baru.regex' => 'Kata sandi harus memuat huruf dan angka.',
            'password_baru_konfirmasi.required' => 'Ulangi kata sandi baru.',
            'password_baru_konfirmasi.same' => 'Ulangi kata sandi belum sama.',
        ]);

        $userId = $request->session()->get('pemulihan_user_id');
        $pengguna = $userId !== null
            ? User::query()->where('is_aktif', true)->find($userId)
            : null;

        if ($pengguna === null) {
            throw ValidationException::withMessages(['kode' => self::PESAN_KODE_TAK_SAH]);
        }

        $baris = KodePemulihanSandi::query()
            ->where('user_id', $pengguna->id_user)
            ->masihBerlaku()
            ->latest('id_kode_pemulihan')
            ->first();

        if ($baris === null) {
            throw ValidationException::withMessages(['kode' => self::PESAN_KODE_TAK_SAH]);
        }

        if (! Hash::check($data['kode'], $baris->kode_hash)) {
            $baris->increment('percobaan');

            throw ValidationException::withMessages(['kode' => self::PESAN_KODE_TAK_SAH]);
        }

        $baris->forceFill(['dipakai_pada' => now()])->save();

        // TANPA `password_harus_diganti`: petugas sudah memilih sandi finalnya.
        $pengguna->forceFill(['password' => $data['password_baru']])->save();

        AuditLog::create([
            'user_id' => $pengguna->id_user,
            'aksi' => AksiAuditLog::ResetKataSandi,
            'nama_tabel' => 'user',
            'record_id' => $pengguna->id_user,
            'data_baru' => ['jalur' => 'Kode verifikasi'],
            'ip_address' => $request->ip(),
        ]);

        $request->session()->forget('pemulihan_user_id');

        // Jaga-jaga bila ada sesi yang masih hidup: paksa masuk ulang dengan
        // sandi baru.
        Auth::logout();

        return redirect()->route('login')
            ->with('sukses', 'Kata sandi berhasil diganti. Silakan masuk memakai kata sandi baru Anda.');
    }

    /**
     * Apakah akun sudah meminta 3 kode dalam satu jam terakhir (`rules.md`
     * 14b poin 10). Dihitung dari `created_at` tabel, bukan RateLimiter,
     * sesuai rancangan `data-dictionary.md` 2.3.
     */
    private function melebihiBatasPermintaan(User $pengguna): bool
    {
        return KodePemulihanSandi::query()
            ->where('user_id', $pengguna->id_user)
            ->where('created_at', '>', now()->subHour())
            ->count() >= self::MAKS_PERMINTAAN_PER_JAM;
    }

    private function keVerifikasi(): RedirectResponse
    {
        return redirect()->route('verifikasi-kode');
    }
}
