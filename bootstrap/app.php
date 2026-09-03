<?php

use App\Http\Middleware\EnsureIzin;
use App\Http\Middleware\PastikanGantiKataSandi;
use App\Http\Middleware\UppercaseInput;
use App\Support\PetaIzinRute;
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

            // Task 3.3: lampirkan `izin:<modul>,<aksi>` per rute dari peta
            // terpusat. Iterasi objek Route langsung (bukan getByName) sebab
            // `->name()` di internal.php dipanggil setelah rute terdaftar,
            // sehingga peta nama koleksi belum tentu memuatnya di titik ini.
            //
            // Task 3.10: sekalian lampirkan pembatas laju. Rute internal
            // (ber-`auth`) dibagi baca vs tulis (`rules.md` 14c.2). Rute
            // berkas besar -- unduh template, dokumen resmi, berkas unggahan --
            // dikecualikan ke batas sendiri (`rules.md` 14c.3 poin 6). Rute
            // publik diberi `throttle:` langsung di routes/web.php.
            $peta = PetaIzinRute::peta();
            $berkasBesar = ['template-impor', 'laporan.dokumen', 'dokumen.tampilkan'];

            foreach (Route::getRoutes()->getRoutes() as $rute) {
                $nama = $rute->getName();

                if ($nama !== null && isset($peta[$nama])) {
                    $rute->middleware("izin:{$peta[$nama]}");
                }

                // `middleware()` (larik mentah), BUKAN `gatherMiddleware()`:
                // yang terakhir menyimpan hasilnya (`computedMiddleware`),
                // sehingga throttle yang dilampirkan sesudahnya tidak ikut
                // terbawa saat permintaan.
                if (! in_array('auth', $rute->middleware(), true)) {
                    continue;
                }

                $metode = $rute->methods();

                $rute->middleware(match (true) {
                    in_array($nama, $berkasBesar, true) => 'throttle:berkas-besar',
                    in_array('GET', $metode, true) => 'throttle:baca-internal',
                    default => 'throttle:tulis-internal',
                });
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Mempercayai header X-Forwarded-* agar asset() dan url() memakai skema
        // dan host asli saat aplikasi diakses lewat tunnel (Cloudflare) atau
        // reverse proxy. Tanpa ini aset dicetak http:// lalu diblokir browser
        // sebagai mixed content.
        $middleware->trustProxies(at: '*');

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
