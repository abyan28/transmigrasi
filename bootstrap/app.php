<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
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
            \App\Http\Middleware\UppercaseInput::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
