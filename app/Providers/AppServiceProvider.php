<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\AuditLogObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->daftarkanBatasLaju();
        $this->daftarkanAuditOtomatis();
    }

    /**
     * Memasang `AuditLogObserver` pada seluruh model data (Task 3.6).
     * Daftarnya di `AuditLogObserver::MODEL`; dipasang lewat perulangan supaya
     * ke-32 model tidak perlu disunting satu per satu.
     */
    private function daftarkanAuditOtomatis(): void
    {
        foreach (AuditLogObserver::MODEL as $model) {
            $model::observe(AuditLogObserver::class);
        }
    }

    /**
     * Mendefinisikan pembatas laju bernama (Task 3.10, `rules.md` 14c).
     *
     * Halaman internal dihitung PER AKUN, bukan per IP: satu kantor dinas
     * kerap berbagi satu sambungan, sehingga hitungan per IP membuat operator
     * saling menghabiskan jatah. Kanal publik tetap per IP. Angkanya dari
     * `config/sim.php` supaya dapat disetel lapangan.
     *
     * Percobaan masuk (5 kegagalan/menit) ditangani `LoginController` sendiri
     * lewat `RateLimiter` manual -- tidak didaftarkan di sini.
     */
    private function daftarkanBatasLaju(): void
    {
        // `config()` dibaca DI DALAM tiap closure (saat permintaan), bukan
        // ditangkap di sini, supaya uji dapat menyalakannya lewat config().
        $perAkun = fn (Request $r): string => (string) ($r->user()?->getAuthIdentifier() ?? $r->ip());

        $internal = fn (string $kunci) => fn (Request $r) => config('sim.batas_laju.aktif')
            ? Limit::perMinute((int) config("sim.batas_laju.{$kunci}"))->by($perAkun($r))
                ->response($this->tanggapanBatas('Terlalu banyak permintaan dalam waktu singkat. Tunggu sebentar, lalu muat ulang halaman.'))
            : Limit::none();

        RateLimiter::for('baca-internal', $internal('baca_internal'));
        RateLimiter::for('tulis-internal', $internal('tulis_internal'));
        RateLimiter::for('berkas-besar', $internal('berkas_besar'));

        RateLimiter::for('lacak-publik', fn (Request $r) => config('sim.batas_laju.aktif')
            ? Limit::perMinute((int) config('sim.batas_laju.lacak_publik'))->by($r->ip())
                ->response($this->tanggapanBatas('Terlalu banyak pencarian dari jaringan ini. Silakan coba lagi satu menit lagi.'))
            : Limit::none());

        RateLimiter::for('kirim-pengaduan', fn (Request $r) => config('sim.batas_laju.aktif')
            ? Limit::perHour((int) config('sim.batas_laju.kirim_pengaduan'))->by($r->ip())
                ->response($this->tanggapanBatas('Anda sudah mengirim beberapa pengaduan. Silakan coba lagi satu jam lagi.'))
            : Limit::none());
    }

    /**
     * Penanggap 429 berbahasa Indonesia yang menyebut jalan keluarnya, bukan
     * kode galat teknis (`rules.md` 14c.3 poin 5).
     */
    private function tanggapanBatas(string $pesan): callable
    {
        return fn (Request $request, array $headers) => response($pesan, 429, $headers);
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
