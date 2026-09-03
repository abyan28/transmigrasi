<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menegakkan kewenangan RBAC pada sebuah rute (Task 3.3).
 *
 * Dipakai: `->middleware('izin:transmigran,ubah')`. Aksi boleh dikosongkan
 * untuk rute baca murni: `->middleware('izin:dashboard')` setara
 * `izin:dashboard,lihat`.
 *
 * `lihat` SELALU menjadi prasyarat aksi lain pada modul yang sama
 * (`data-dictionary.md` 13.3 poin 4): `izin:transmigran,ubah` menuntut BAIK
 * `transmigran.lihat` MAUPUN `transmigran.ubah`.
 *
 * Menolak dengan **403**, bukan 404: ini persoalan kewenangan AKSI, bukan
 * cakupan data (`rules.md` 5.0b-1 poin 11). Cakupan data (404) = Task 3.4.
 */
class EnsureIzin
{
    public function handle(Request $request, Closure $next, string $modul, ?string $aksi = null): Response
    {
        $pengguna = $request->user();

        $wajib = $aksi === null || $aksi === 'lihat'
            ? [$modul.'.lihat']
            : [$modul.'.lihat', $modul.'.'.$aksi];

        foreach ($wajib as $izin) {
            if ($pengguna === null || ! $pengguna->punyaIzin($izin)) {
                abort(403, 'Anda tidak memiliki kewenangan untuk membuka atau melakukan tindakan ini.');
            }
        }

        return $next($request);
    }
}
