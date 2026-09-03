<?php

use App\Http\Middleware\EnsureIzin;
use App\Http\Middleware\MasukOtomatisLokal;
use App\Http\Middleware\PastikanGantiKataSandi;
use App\Http\Middleware\UppercaseInput;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Rute internal: WAJIB login + kunci kata-sandi-sementara.
            // routes/web.php menyimpan rute publik saja (Task 3.2b).
            Route::middleware(['web', 'auth', 'pastikan.ganti.sandi'])
                ->group(base_path('routes/internal.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Mempercayai header X-Forwarded-* agar asset() dan url() memakai skema
        // dan host asli saat aplikasi diakses lewat tunnel (Cloudflare) atau
        // reverse proxy. Tanpa ini aset dicetak http:// lalu diblokir browser
        // sebagai mixed content.
        $middleware->trustProxies(at: '*');

        // Bypass masuk lingkungan lokal (Task 3.2b). Dijalankan SEBELUM `auth`
        // (prepend) agar rute internal dapat ditelusuri tanpa login manual saat
        // APP_ENV=local + SIM_MASUK_OTOMATIS. Tak berefek di lingkungan lain.
        $middleware->web(prepend: [
            MasukOtomatisLokal::class,
        ]);

        // Menyeragamkan isian teks pengguna menjadi huruf kapital agar rekap
        // per wilayah tidak terpecah oleh perbedaan penulisan.
        // Rincian dan daftar pengecualian ada pada agents/rules.md bagian 13.2 poin 4.
        $middleware->web(append: [
            UppercaseInput::class,
        ]);

        // Alias middleware. `pastikan.ganti.sandi` (Task 3.2b) dilampirkan ke
        // grup rute internal lewat `then:` di atas; `izin` (Task 3.3) dipasang
        // per-rute di `routes/internal.php`, mis. `izin:transmigran,ubah`.
        $middleware->alias([
            'pastikan.ganti.sandi' => PastikanGantiKataSandi::class,
            'izin' => EnsureIzin::class,
        ]);

        // Tamu yang sudah masuk lalu membuka rute ber-`guest` (mis. /login)
        // diarahkan ke beranda. Bawaan Laravel menuju /dashboard yang tidak
        // ada di aplikasi ini.
        $middleware->redirectUsersTo('/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
