<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mengunci seluruh halaman selain ganti kata sandi selama
 * `password_harus_diganti = TRUE` (`rules.md` 14b poin 13).
 *
 * Kata sandi sementara -- dari akun baru maupun setelan ulang Admin -- hanya
 * boleh dipakai sekali untuk masuk lalu wajib segera diganti. Tanpa penjaga
 * ini, kata sandi yang diserahkan lisan atau lewat surel tetap berlaku
 * selamanya.
 *
 * Task 3.2: kelas ini SUDAH dibangun dan diberi alias, tetapi BELUM dilampirkan
 * ke grup rute mana pun -- pelampirannya menyusul di Task 3.2b bersama `auth`.
 */
class PastikanGantiKataSandi
{
    /**
     * Rute yang tetap boleh dibuka meski kata sandi belum diganti: halaman
     * ganti sandi itu sendiri dan keluar dari akun (satu-satunya jalan keluar).
     *
     * @var array<int, string>
     */
    private array $dikecualikan = ['ganti-kata-sandi', 'ganti-kata-sandi.simpan', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $pengguna = $request->user();

        if ($pengguna !== null
            && $pengguna->password_harus_diganti
            && ! $request->routeIs(...$this->dikecualikan)) {
            return redirect()->route('ganti-kata-sandi');
        }

        return $next($request);
    }
}
