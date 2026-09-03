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
use Illuminate\Support\Str;

/**
 * Ganti kata sandi WAJIB, muncul saat `password_harus_diganti = TRUE`
 * (`rules.md` 14b poin 13: pengguna tidak dapat mengakses halaman lain sebelum
 * menggantinya). Penegakan oleh middleware `PastikanGantiKataSandi`, yang di
 * Task 3.2 sudah dibangun tetapi belum dilampirkan ke grup rute (Task 3.2b).
 *
 * CATATAN: `rules.md` 14b poin 5 juga meminta username dibuat di sini pada
 * masuk pertama. View `ganti-kata-sandi.blade.php` belum memiliki kolomnya;
 * penambahannya adalah perubahan UI yang ditunda ke Task 3.2b/3.5.
 */
class GantiKataSandiController extends Controller
{
    public function tampil(): View
    {
        return view('pages.auth.ganti-kata-sandi', ['title' => 'Ganti Kata Sandi']);
    }

    public function simpan(Request $request): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $request->validate(
            ['password' => ValidationRules::password()],
            ValidationRules::pesan(),
        );

        /** @var User $pengguna */
        $pengguna = Auth::user();
        $pengguna->update([
            'password' => $request->input('password'),
            'password_harus_diganti' => false,
        ]);

        // Atas nama pemilik akun sendiri (`rules.md` 14b poin 15).
        AuditLog::create([
            'user_id' => $pengguna->id_user,
            'aksi' => AksiAuditLog::ResetKataSandi,
            'nama_tabel' => 'user',
            'record_id' => $pengguna->id_user,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
        ]);

        return redirect()->route('beranda')->with('sukses', 'Kata sandi berhasil diganti.');
    }
}
