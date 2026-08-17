<?php

namespace App\Providers;

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
