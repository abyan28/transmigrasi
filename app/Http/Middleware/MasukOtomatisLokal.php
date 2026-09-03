<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bypass masuk untuk lingkungan pengembangan lokal.
 *
 * Task 3.2b membungkus seluruh rute internal dengan `auth`, sehingga tanpa
 * bypass ini setiap penelusuran lokal wajib login lebih dulu -- padahal basis
 * data lokal belum di-seed akun mana pun sampai Tahap 4. Middleware ini
 * mengautentikasi pengguna pengembang bila BELUM masuk, HANYA saat:
 *
 *   1. `APP_ENV=local`, DAN
 *   2. `config('sim.masuk_otomatis')` bernilai true (default: true di `local`).
 *
 * Kedua syarat wajib -- production tidak akan pernah mengaktifkannya walau
 * `SIM_MASUK_OTOMATIS=true` keliru terpasang.
 *
 * Penggunanya TIDAK dipersist ke basis data (pola sama dengan uji): sesi hanya
 * menyimpan id null, jadi tiap permintaan melakukan masuk-otomatis lagi. Cukup
 * untuk menelusuri; `Auth::id()` tetap null. Tampilan header/profil masih dibaca
 * dari `DummyData::penggunaSaatIni()` sampai peralihan Eloquent (Tahap 4),
 * sehingga pengembang tetap melihat "NARA WIJAYA" di pojok -- kosmetik, bukan bug.
 *
 * Untuk menguji alur login/logout sungguhan: set `SIM_MASUK_OTOMATIS=false`
 * di `.env` lokal.
 */
class MasukOtomatisLokal
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()
            && app()->environment('local')
            && config('sim.masuk_otomatis')) {
            Auth::login($this->penggunaPengembang());
        }

        return $next($request);
    }

    private function penggunaPengembang(): User
    {
        $dev = new User([
            'nama' => 'PENGEMBANG LOKAL',
            'username' => 'dev',
            'email' => 'dev@lokal.test',
            'is_aktif' => true,
            'password_harus_diganti' => false,
        ]);

        // Tak punya role nyata (tak dipersist) -> tandai seluruh kewenangan
        // supaya rute ber-`izin` (Task 3.3) tetap terbuka saat menelusuri.
        $dev->semuaIzin = true;

        return $dev;
    }
}
