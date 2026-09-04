<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AksiAuditLog;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Ganti kata sandi WAJIB, muncul saat `password_harus_diganti = TRUE`
 * (`rules.md` 14b poin 13: pengguna tidak dapat mengakses halaman lain sebelum
 * menggantinya). Ditegakkan middleware `PastikanGantiKataSandi` (dilampirkan
 * ke grup rute internal lewat `bootstrap/app.php`).
 *
 * Task 3.14: bila akun masih memakai username SEMENTARA (`petugas.xxxxxxxx`),
 * halaman ini SEKALIGUS meminta petugas membuat usernamenya sendiri
 * (`rules.md` 14b poin 5), lengkap dengan pemeriksaan ketersediaan saat
 * diketik (`cekUsername`, poin 5a).
 */
class GantiKataSandiController extends Controller
{
    public function tampil(Request $request): View
    {
        return view('pages.auth.ganti-kata-sandi', [
            'title' => 'Ganti Kata Sandi',
            'perluUsername' => $request->user()->perluBuatUsername(),
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        /** @var User $pengguna */
        $pengguna = Auth::user();
        $perlu = $pengguna->perluBuatUsername();

        $aturan = ['password' => ValidationRules::password()];

        if ($perlu) {
            $aturan['username'] = ValidationRules::username($pengguna->id_user);
        }

        $data = $request->validate($aturan, ValidationRules::pesan());

        $atribut = ['password' => $data['password'], 'password_harus_diganti' => false];

        if ($perlu) {
            $atribut['username'] = $data['username'];
        }

        $pengguna->forceFill($atribut)->save();

        // Atas nama pemilik akun sendiri (`rules.md` 14b poin 15). Jalur
        // seragam dengan `Admin` / `Mandiri` / `Kode verifikasi`.
        AuditLog::create([
            'user_id' => $pengguna->id_user,
            'aksi' => AksiAuditLog::ResetKataSandi,
            'nama_tabel' => 'user',
            'record_id' => $pengguna->id_user,
            'data_baru' => ['jalur' => $perlu ? 'Masuk pertama' : 'Ganti wajib'],
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
        ]);

        return redirect()->route('beranda')->with('sukses', 'Kata sandi berhasil diganti.');
    }

    /**
     * Pemeriksaan ketersediaan username saat diketik (`rules.md` 14b poin 5a).
     * SELALU membalas 200 -- input kosong/sah-tidak-sah cukup menjawab
     * `tersedia: false`, tanpa menyentuh basis data bila formatnya sudah salah.
     */
    public function cekUsername(Request $request): JsonResponse
    {
        $username = (string) $request->query('username', '');
        $formatSah = preg_match('/^[a-z0-9._]{3,50}$/', $username) === 1;

        return response()->json([
            'tersedia' => $formatSah && ! User::where('username', $username)->exists(),
        ]);
    }
}
