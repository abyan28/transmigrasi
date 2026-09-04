<?php

use App\Http\Controllers\Auth\GantiKataSandiController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PemulihanSandiController;
use App\Http\Controllers\PengaduanPublikController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute Web Publik
|--------------------------------------------------------------------------
|
| HANYA rute yang boleh dibuka tanpa login: masuk, pemulihan kata sandi,
| dan kanal pengaduan warga. Seluruh rute internal (dashboard, modul data,
| master, laporan, dokumen) ada di routes/internal.php dan dibungkus
| `auth` + `pastikan.ganti.sandi` lewat bootstrap/app.php.
|
| Sistem sengaja TIDAK memiliki rute pendaftaran mandiri; seluruh akun
| dibuat Admin. Pemulihan kata sandi lewat dua jalur yang keduanya sah:
| kode verifikasi ke surel dinas, dan penyetelan ulang oleh Admin
| (agents/rules.md bagian 14b poin 7 sampai 12).
|
*/

/*
 * Masuk dan keluar sistem (Task 3.2). Logika berjalan penuh: email atau
 * username pada satu kolom, tolak akun nonaktif dengan pesan khusus, throttle
 * 5 kegagalan per menit, regenerasi sesi, catat `last_login_at` + audit log.
 *
 * `guest` mengalihkan pengguna yang sudah masuk ke beranda
 * (bootstrap/app.php `redirectUsersTo('/')`).
 */
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'tampil'])->name('login');
    Route::post('/login', [LoginController::class, 'masuk'])->name('login.kirim');

    // Pemulihan kata sandi mandiri lewat kode verifikasi 6 digit (Task 3.11).
    // Balasan POST /lupa-kata-sandi SAMA baik akun ada maupun tidak
    // (rules.md 14b poin 9). Perilaku nyata diuji di tests/Database.
    Route::get('/lupa-kata-sandi', [PemulihanSandiController::class, 'tampilPermintaan'])->name('lupa-kata-sandi');
    Route::post('/lupa-kata-sandi', [PemulihanSandiController::class, 'kirimKode'])->name('lupa-kata-sandi.kirim');
    Route::get('/verifikasi-kode', [PemulihanSandiController::class, 'tampilVerifikasi'])->name('verifikasi-kode');
    Route::post('/atur-ulang-sandi', [PemulihanSandiController::class, 'aturUlang'])->name('atur-ulang-sandi');
});

// Keluar boleh dari keadaan apa pun (termasuk saat wajib ganti kata sandi).
Route::post('/logout', [LoginController::class, 'keluar'])->name('logout');

/*
 * Halaman wajib ganti kata sandi. Butuh login, TETAPI dikecualikan dari
 * `pastikan.ganti.sandi` (middleware itu sendiri sudah self-exclude nama
 * rutenya) supaya pengguna berkata-sandi sementara dapat mencapainya.
 */
Route::middleware('auth')->group(function () {
    Route::get('/ganti-kata-sandi', [GantiKataSandiController::class, 'tampil'])->name('ganti-kata-sandi');
    Route::post('/ganti-kata-sandi', [GantiKataSandiController::class, 'simpan'])->name('ganti-kata-sandi.simpan');
    // Task 3.14: cek ketersediaan username saat diketik (rules.md 14b poin 5a).
    // Di grup ini, BUKAN routes/internal.php: `pastikan.ganti.sandi` akan
    // memantulkan pengguna berflag wajib-ganti yang justru sedang memakainya.
    Route::get('/ganti-kata-sandi/cek-username', [GantiKataSandiController::class, 'cekUsername'])
        ->name('ganti-kata-sandi.cek-username');
});

/*
|--------------------------------------------------------------------------
| Halaman Publik Tanpa Login
|--------------------------------------------------------------------------
|
| Warga transmigran tidak memiliki akun sistem, sehingga pengaduan dibuka
| lewat kanal publik (rules.md bagian 10b poin 1). Kedua halaman ini memakai
| tata letak terpisah tanpa sidebar.
|
| Pembatasan 3 pengiriman per jam per alamat IP dipasang pada Tahap 8 memakai
| middleware throttle. Sistem sengaja TIDAK memakai CAPTCHA karena membebani
| pengguna berjaringan lemah di lokus (poin 1d sampai 1g).
|
*/
Route::get('/pengaduan-warga', [PengaduanPublikController::class, 'formWarga'])->name('pengaduan-warga');

Route::middleware('throttle:kirim-pengaduan')
    ->post('/pengaduan-warga', [PengaduanPublikController::class, 'kirim'])
    ->name('pengaduan-warga.kirim');

/*
 * Pelacakan pengaduan, dipakai DUA rute.
 *
 * Nomor dapat datang dari dua arah: kueri `?nomor=` milik formulir, dan segmen
 * rute `/lacak-pengaduan/{nomor}` yang menjadi tautan tetap. Keduanya sah, dan
 * yang kedua membuat halaman ini tetap berfungsi pada build statis yang tidak
 * dapat melayani kueri (`notes.md` bagian 1b).
 */
Route::middleware('throttle:lacak-publik')->group(function () {
    Route::get('/lacak-pengaduan', [PengaduanPublikController::class, 'lacak'])->name('lacak-pengaduan');

    Route::get('/lacak-pengaduan/{nomor}', [PengaduanPublikController::class, 'lacak'])
        ->where('nomor', '[A-Za-z0-9\-]+')->name('lacak-pengaduan.nomor');
});
