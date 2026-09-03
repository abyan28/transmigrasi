<?php

use App\Enums\JenisReferensi;
use App\Http\Controllers\Auth\GantiKataSandiController;
use App\Http\Controllers\Auth\LoginController;
use App\Support\DummyData;
use Illuminate\Http\Request;
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

    Route::get('/lupa-kata-sandi', function () {
        return view('pages.auth.lupa-kata-sandi', ['title' => 'Lupa Kata Sandi']);
    })->name('lupa-kata-sandi');

    Route::post('/lupa-kata-sandi', function () {
        // Tahap 3: batalkan kode lama milik akun ini, buat kode enam digit baru,
        // simpan sidiknya beserta kedaluwarsa 15 menit, lalu kirim lewat surel.
        //
        // Redirect ini berlaku SAMA baik akun ditemukan maupun tidak
        // (rules.md 14b poin 9). Membedakan keduanya menjadikan halaman ini
        // alat memeriksa siapa saja yang memiliki akun.
        return redirect()->route('verifikasi-kode');
    })->name('lupa-kata-sandi.kirim');

    Route::get('/verifikasi-kode', function () {
        return view('pages.auth.verifikasi-kode', ['title' => 'Masukkan Kode Verifikasi']);
    })->name('verifikasi-kode');

    Route::post('/atur-ulang-sandi', function () {
        // Tahap 3: cocokkan sidik kode, periksa kedaluwarsa dan hitungan
        // percobaan, tandai kode terpakai, simpan hash kata sandi baru, lalu
        // catat audit log beraksi Reset Kata Sandi atas nama pemilik akun
        // (rules.md 14b poin 15).
        return redirect()->route('login')
            ->with('sukses', 'Kata sandi berhasil diganti. Silakan masuk memakai kata sandi baru Anda.');
    })->name('atur-ulang-sandi');
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
Route::get('/pengaduan-warga', function () {
    return view('pages.publik.pengaduan', [
        'title' => 'Kirim Pengaduan',
        'daftarSp' => DummyData::satuanPermukiman(),
        'opsiKategoriPengaduan' => DummyData::opsiReferensi(JenisReferensi::KategoriPengaduan),
    ]);
})->name('pengaduan-warga');

Route::post('/pengaduan-warga', function (Request $permintaan) {
    // Tahap 8: simpan pengaduan berstatus Menunggu Diterima, catat ip_pelapor,
    // buat nomor pengaduan berbagian acak, lalu kirim nomornya ke surel pelapor
    // bila diisi.
    //
    // Nomor contoh sengaja memakai salah satu yang BENAR-BENAR ADA pada data
    // contoh. Sebelumnya dipakai PGD-2026-0006 yang tidak pernah ada, sehingga
    // tombol "Lihat Perkembangan Laporan" selalu berujung pada keadaan nomor
    // tidak ditemukan; kontrol semacam itu dilarang (R-26).
    return back()
        ->with('nomor_pengaduan', 'PGD-2026-0003-3NYVEN')
        ->with('email_pelapor', $permintaan->input('email_pelapor'));
})->name('pengaduan-warga.kirim');

/*
 * Pelacakan pengaduan, dipakai DUA rute.
 *
 * Nomor dapat datang dari dua arah: kueri `?nomor=` milik formulir, dan segmen
 * rute `/lacak-pengaduan/{nomor}` yang menjadi tautan tetap. Keduanya sah, dan
 * yang kedua membuat halaman ini tetap berfungsi pada build statis yang tidak
 * dapat melayani kueri.
 */
$susunLacakPengaduan = function (?string $nomorRute = null) {
    $nomor = trim((string) ($nomorRute ?? request('nomor', '')));
    $pengaduan = null;
    $riwayat = [];

    if ($nomor !== '') {
        $pengaduan = collect(DummyData::pengaduan())
            ->firstWhere('nomor_pengaduan', mb_strtoupper($nomor));

        if ($pengaduan) {
            $riwayat = DummyData::penangananPengaduan($pengaduan['nomor_pengaduan']);
        }
    }

    return view('pages.publik.lacak', [
        'title' => 'Lacak Pengaduan',
        'nomor' => $nomor,
        'pengaduan' => $pengaduan,
        'riwayat' => $riwayat,
    ]);
};

Route::get('/lacak-pengaduan', fn () => $susunLacakPengaduan())->name('lacak-pengaduan');

// Tautan tetap per nomor pengaduan. Hasil pencarian menjadi dapat ditandai dan
// dibagikan, dan inilah yang membuat halaman lacak tetap bekerja pada build
// statis GitHub Pages, tempat kueri `?nomor=` tidak dapat dilayani.
// Lihat agents/notes.md bagian 1b.
Route::get('/lacak-pengaduan/{nomor}', fn (string $nomor) => $susunLacakPengaduan($nomor))
    ->where('nomor', '[A-Za-z0-9\-]+')->name('lacak-pengaduan.nomor');
