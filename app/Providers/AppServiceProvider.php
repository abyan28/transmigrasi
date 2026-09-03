<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->samakanAlamatDasar();
        $this->daftarkanGateIzin();
    }

    /**
     * Menyambungkan RBAC ke lapisan otorisasi Laravel (Task 3.3).
     *
     * `Gate::before` menjadikan tiap nama kewenangan (`transmigran.ubah`)
     * langsung dapat diperiksa lewat `@can(...)` di Blade, `$user->can(...)`,
     * dan middleware `can:`. Mengembalikan `null` bila tidak berwenang supaya
     * gate/policy lain tetap sempat berjalan, bukan langsung menolak.
     */
    private function daftarkanGateIzin(): void
    {
        Gate::before(function (?User $pengguna, string $izin) {
            return $pengguna?->punyaIzin($izin) ? true : null;
        });
    }

    /**
     * Menyamakan alamat dasar seluruh tautan ketika aplikasi disajikan pada
     * sub-path, bukan pada akar domain.
     *
     * GitHub Pages menaruh situs di `/namarepo/`, sehingga `route()` dan
     * `asset()` yang menghitung akar dari request akan kehilangan awalan itu
     * dan seluruh tautan beserta gambar menjadi rusak.
     *
     * Hanya aktif bila `ASSET_URL` diisi. Pengembangan di localhost, akses
     * lewat jaringan lokal, dan terowongan Cloudflare tidak menyetel variabel
     * ini, sehingga perilakunya sama sekali tidak berubah: akar tetap diambil
     * dari request yang masuk (lihat `bootstrap/app.php` bagian trustProxies).
     */
    private function samakanAlamatDasar(): void
    {
        $alamat = config('app.asset_url');

        if (blank($alamat)) {
            return;
        }

        // Dipakai `route()` dan `url()`. Tanpa ini keduanya hanya menghasilkan
        // skema dan host, tanpa sub-path repositori.
        $this->app['url']->forceRootUrl($alamat);

        // Skema disamakan dengan alamat yang diminta. Proses penggilasan
        // dijalankan lewat `php artisan serve` yang berbicara http, sedangkan
        // hasilnya disajikan GitHub Pages lewat https. Tanpa penyamaan ini
        // `route()` mencetak http sementara `asset()` mencetak https, dan
        // peramban memblokir asetnya sebagai muatan campuran.
        if (str_starts_with($alamat, 'https://')) {
            $this->app['url']->forceScheme('https');
        }
    }
}
