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
