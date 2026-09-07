<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PastikanPenggunaAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_aktif === false) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['kredensial' => 'Akun Anda dinonaktifkan. Silakan hubungi Admin.']);
        }

        return $next($request);
    }
}
