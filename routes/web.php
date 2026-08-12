<?php

use App\Http\Controllers\DokumenController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute Web
|--------------------------------------------------------------------------
|
| Rute aplikasi SIM Transmigrasi. Halaman contoh bawaan template TailAdmin
| sudah dihapus pada Task 1.4b. Rute sebenarnya dibangun bertahap pada
| Tahap 2 mengikuti inventaris halaman di `agents/ui-spec.md` bagian 4.
|
*/

// Dashboard monitoring kawasan, 15 indikator dengan data contoh.
// Penggantian ke query nyata dikerjakan pada Tahap 9.
Route::get('/', function () {
    return view('pages.dashboard.index', ['title' => 'Dashboard']);
})->name('beranda');

// Rincian satu satuan permukiman, tujuan penelusuran dari dashboard kawasan.
// SP yang tidak dikenal membalas 404 agar alamat karangan tidak menghasilkan
// halaman kosong yang membingungkan.
Route::get('/dashboard/sp/{sp}', function (int $sp) {
    $data = \App\Support\DummyData::cariSp($sp);

    abort_if($data === null, 404);

    return view('pages.dashboard.sp', ['title' => $data['nama'], 'sp' => $data]);
})->where('sp', '[0-9]+')->name('dashboard.sp');

/*
|--------------------------------------------------------------------------
| Autentikasi dan Profil
|--------------------------------------------------------------------------
|
| Masih berupa tampilan dengan data contoh. Proses masuk, keluar, dan
| penyimpanan yang sebenarnya dikerjakan pada Tahap 3, sedangkan bentuk
| halamannya sudah final di sini.
|
| Sistem sengaja TIDAK memiliki rute pendaftaran mandiri maupun pemulihan
| kata sandi lewat surel (agents/rules.md bagian 14b).
|
*/
Route::get('/login', function () {
    return view('pages.auth.signin', ['title' => 'Masuk']);
})->name('login');

Route::post('/logout', function () {
    // Tahap 3: Auth::logout() beserta invalidasi sesi.
    return redirect()->route('login')->with('sukses', 'Anda sudah keluar dari sistem.');
})->name('logout');

// Halaman wajib ganti kata sandi, muncul ketika password_harus_diganti bernilai TRUE.
Route::get('/ganti-kata-sandi', function () {
    return view('pages.auth.ganti-kata-sandi', ['title' => 'Ganti Kata Sandi']);
})->name('ganti-kata-sandi');

Route::post('/ganti-kata-sandi', function () {
    // Tahap 3: simpan hash baru, kosongkan password_harus_diganti, catat audit log.
    return redirect()->route('beranda')->with('sukses', 'Kata sandi berhasil diganti.');
})->name('ganti-kata-sandi.simpan');

Route::get('/profil', function () {
    return view('pages.profil.index', ['title' => 'Profil Saya']);
})->name('profil');

Route::put('/profil', function () {
    // Tahap 3: validasi memakai ValidationRules lalu simpan data kontak.
    return back()->with('sukses', 'Data kontak Anda tersimpan.');
})->name('profil.simpan');

Route::get('/profil/kata-sandi', function () {
    return view('pages.profil.kata-sandi', ['title' => 'Ubah Kata Sandi']);
})->name('profil.kata-sandi');

Route::put('/profil/kata-sandi', function () {
    // Tahap 3: periksa kata sandi lama, simpan hash baru, catat audit log.
    return redirect()->route('profil')->with('sukses', 'Kata sandi berhasil diperbarui.');
})->name('profil.kata-sandi.simpan');


// Galeri komponen bersama, halaman internal untuk pengembangan.
// Dihapus sebelum penyerahan akhir.
Route::get('/galeri-komponen', function () {
    return view('pages.galeri-komponen', ['title' => 'Galeri Komponen']);
})->name('galeri-komponen');

// Pemicu halaman 403 untuk peninjauan tampilan. RBAC yang memicunya secara
// alami baru aktif pada Tahap 3. Dihapus bersama galeri komponen.
Route::get('/uji-403', function () {
    abort(403);
})->name('uji-403');
/*
|--------------------------------------------------------------------------
| Data Master Wilayah, SP, dan Aset
|--------------------------------------------------------------------------
|
| Task 2.13. Data master jarang berubah, sehingga halamannya menekankan
| keterbacaan susunan wilayah, bukan kecepatan penyuntingan.
|
*/
Route::get('/wilayah', function () {
    return view('pages.master.wilayah', ['title' => 'Data Master Wilayah']);
})->name('wilayah');

Route::get('/master/satuan', function () {
    return view('pages.master.satuan', ['title' => 'Data Master Satuan']);
})->name('master.satuan');

Route::get('/kawasan', function () {
    return view('pages.sp.kawasan', ['title' => 'Kawasan Transmigrasi']);
})->name('kawasan');

// Rute beruas dua didaftarkan sebelum /sp agar tidak tertukar.
Route::get('/sp/inventaris', function () {
    return view('pages.sp.inventaris', ['title' => 'Inventaris SP']);
})->name('sp.inventaris');

Route::get('/sp/fasilitas', function () {
    return view('pages.sp.fasilitas', ['title' => 'Fasilitas SP']);
})->name('sp.fasilitas');

Route::get('/sp', function () {
    return view('pages.sp.index', ['title' => 'Satuan Permukiman']);
})->name('sp.index');

/*
|--------------------------------------------------------------------------
| Modul Transmigran
|--------------------------------------------------------------------------
|
| Halaman CRUD pertama, polanya diikuti modul berikutnya. Penyimpanan yang
| sebenarnya beserta validasi dan audit log dikerjakan pada Tahap 5; di sini
| rute penulisan hanya memulangkan pesan agar alur antarmuka dapat dicoba
| tanpa menyisakan tombol mati (ANTISLOP-ID R-26).
|
*/
Route::get('/transmigran', function () {
    return view('pages.transmigran.index', ['title' => 'Data Transmigran']);
})->name('transmigran.index');

Route::get('/transmigran/{id}', function (int $id) {
    $data = collect(\App\Support\DummyData::transmigran())->firstWhere('id_transmigran', $id);

    abort_if($data === null, 404);

    return view('pages.transmigran.detail', ['title' => $data['nama_kepala_keluarga'], 'data' => $data]);
})->where('id', '[0-9]+')->name('transmigran.detail');

Route::post('/transmigran', function () {
    // Tahap 5: validasi lewat ValidationRules, simpan, catat audit log.
    // Tombol "Simpan dan Verifikasi" mengirim tindakan=simpan_verifikasi dan
    // wajib menghasilkan dua entri audit log terpisah (rules.md bagian 5.2).
    return redirect()->route('transmigran.index')
        ->with('sukses', 'Data transmigran tersimpan dan menunggu diverifikasi.');
})->name('transmigran.simpan');

Route::put('/transmigran/{id}', function (int $id) {
    // Tahap 5: perubahan mengembalikan status verifikasi ke Belum Diverifikasi.
    return redirect()->route('transmigran.detail', $id)
        ->with('sukses', 'Perubahan data transmigran tersimpan.');
})->where('id', '[0-9]+')->name('transmigran.perbarui');

Route::delete('/transmigran/{id}', function () {
    // Tahap 5: soft delete agar data tetap dapat dipulihkan.
    return redirect()->route('transmigran.index')
        ->with('sukses', 'Data transmigran dihapus.');
})->where('id', '[0-9]+')->name('transmigran.hapus');

Route::post('/transmigran/{id}/verifikasi', function (int $id) {
    // Tahap 3 Task 3.7: tulis ke tabel verifikasi terpusat.
    return redirect()->route('transmigran.detail', $id)
        ->with('sukses', 'Data transmigran ditandai terverifikasi.');
})->where('id', '[0-9]+')->name('transmigran.verifikasi');

Route::post('/transmigran/{id}/tolak', function (int $id) {
    // Alasan penolakan wajib diisi (rules.md bagian 5.2 poin 7).
    return redirect()->route('transmigran.detail', $id)
        ->with('peringatan', 'Data transmigran ditolak, alasan sudah dicatat.');
})->where('id', '[0-9]+')->name('transmigran.tolak');

/*
|--------------------------------------------------------------------------
| Modul Rumah dan Hunian
|--------------------------------------------------------------------------
|
| Dua aturan modul ini yang dijaga sejak antarmuka: dropdown penghuni hanya
| menawarkan rumah kosong, dan pergantian penghuni dicatat sebagai riwayat
| baru tanpa menimpa data lama (rules.md bagian 6a poin 8 dan 9).
|
*/
Route::get('/rumah', function () {
    return view('pages.rumah.index', ['title' => 'Rumah dan Hunian']);
})->name('rumah.index');

Route::get('/rumah/{id}', function (int $id) {
    $data = collect(\App\Support\DummyData::rumah())->firstWhere('id_rumah', $id);

    abort_if($data === null, 404);

    return view('pages.rumah.detail', ['title' => 'Rumah ' . $data['no_rumah'], 'data' => $data]);
})->where('id', '[0-9]+')->name('rumah.detail');

Route::post('/rumah', function () {
    return redirect()->route('rumah.index')
        ->with('sukses', 'Data rumah tersimpan dan menunggu diverifikasi.');
})->name('rumah.simpan');

Route::put('/rumah/{id}', function (int $id) {
    // Tahap 5: pergantian penghuni menambah baris riwayat_penghunian baru.
    return redirect()->route('rumah.detail', $id)->with('sukses', 'Perubahan data rumah tersimpan.');
})->where('id', '[0-9]+')->name('rumah.perbarui');

Route::delete('/rumah/{id}', function () {
    return redirect()->route('rumah.index')->with('sukses', 'Data rumah dihapus.');
})->where('id', '[0-9]+')->name('rumah.hapus');

Route::post('/rumah/{id}/verifikasi', function (int $id) {
    return redirect()->route('rumah.detail', $id)->with('sukses', 'Data rumah ditandai terverifikasi.');
})->where('id', '[0-9]+')->name('rumah.verifikasi');

Route::post('/rumah/{id}/tolak', function (int $id) {
    return redirect()->route('rumah.detail', $id)
        ->with('peringatan', 'Data rumah ditolak, alasan sudah dicatat.');
})->where('id', '[0-9]+')->name('rumah.tolak');

/*
|--------------------------------------------------------------------------
| Modul Lahan
|--------------------------------------------------------------------------
|
| Dokumen HPL dan SHM dikelola lewat rute tersendiri, karena satu lahan dapat
| memiliki lebih dari satu dokumen (data-dictionary.md bagian 7.2).
|
*/
Route::get('/lahan', function () {
    return view('pages.lahan.index', ['title' => 'Data Lahan']);
})->name('lahan.index');

Route::get('/lahan/{id}', function (int $id) {
    $data = collect(\App\Support\DummyData::lahan())->firstWhere('id_lahan', $id);

    abort_if($data === null, 404);

    return view('pages.lahan.detail', ['title' => 'Lahan ' . $data['kode_lahan'], 'data' => $data]);
})->where('id', '[0-9]+')->name('lahan.detail');

Route::post('/lahan', function () {
    return redirect()->route('lahan.index')
        ->with('sukses', 'Data lahan tersimpan dan menunggu diverifikasi.');
})->name('lahan.simpan');

Route::put('/lahan/{id}', function (int $id) {
    return redirect()->route('lahan.detail', $id)->with('sukses', 'Perubahan data lahan tersimpan.');
})->where('id', '[0-9]+')->name('lahan.perbarui');

Route::delete('/lahan/{id}', function () {
    return redirect()->route('lahan.index')->with('sukses', 'Data lahan dihapus.');
})->where('id', '[0-9]+')->name('lahan.hapus');

Route::post('/lahan/{id}/dokumen', function (int $id) {
    return redirect()->route('lahan.detail', ['id' => $id, 'tab' => 'dokumen'])
        ->with('sukses', 'Dokumen lahan tersimpan.');
})->where('id', '[0-9]+')->name('lahan.dokumen.simpan');

Route::post('/lahan/{id}/verifikasi', function (int $id) {
    return redirect()->route('lahan.detail', $id)->with('sukses', 'Data lahan ditandai terverifikasi.');
})->where('id', '[0-9]+')->name('lahan.verifikasi');

Route::post('/lahan/{id}/tolak', function (int $id) {
    return redirect()->route('lahan.detail', $id)
        ->with('peringatan', 'Data lahan ditolak, alasan sudah dicatat.');
})->where('id', '[0-9]+')->name('lahan.tolak');

/*
|--------------------------------------------------------------------------
| Modul Hasil Panen
|--------------------------------------------------------------------------
|
| Rute rekap diletakkan SEBELUM rute berparameter, agar /panen/rekap tidak
| tertangkap sebagai id (Laravel mencocokkan rute menurut urutan pendaftaran).
|
*/
Route::get('/panen', function () {
    return view('pages.panen.index', ['title' => 'Hasil Panen']);
})->name('panen.index');

Route::get('/panen/rekap', function () {
    return view('pages.panen.rekap', ['title' => 'Rekap Hasil Panen']);
})->name('panen.rekap');

Route::get('/panen/{id}', function (int $id) {
    $data = collect(\App\Support\DummyData::hasilPanen())->firstWhere('id_hasil_panen', $id);

    abort_if($data === null, 404);

    return view('pages.panen.detail', ['title' => 'Panen ' . $data['komoditas'], 'data' => $data]);
})->where('id', '[0-9]+')->name('panen.detail');

Route::post('/panen', function () {
    return redirect()->route('panen.index')
        ->with('sukses', 'Hasil panen tersimpan dan menunggu diverifikasi.');
})->name('panen.simpan');

Route::put('/panen/{id}', function (int $id) {
    return redirect()->route('panen.detail', $id)->with('sukses', 'Perubahan catatan panen tersimpan.');
})->where('id', '[0-9]+')->name('panen.perbarui');

Route::delete('/panen/{id}', function () {
    return redirect()->route('panen.index')->with('sukses', 'Catatan panen dihapus.');
})->where('id', '[0-9]+')->name('panen.hapus');

Route::post('/panen/{id}/verifikasi', function (int $id) {
    return redirect()->route('panen.detail', $id)->with('sukses', 'Catatan panen ditandai terverifikasi.');
})->where('id', '[0-9]+')->name('panen.verifikasi');

Route::post('/panen/{id}/tolak', function (int $id) {
    return redirect()->route('panen.detail', $id)
        ->with('peringatan', 'Catatan panen ditolak, alasan sudah dicatat.');
})->where('id', '[0-9]+')->name('panen.tolak');

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
    return view('pages.publik.pengaduan', ['title' => 'Kirim Pengaduan']);
})->name('pengaduan-warga');

Route::post('/pengaduan-warga', function () {
    // Tahap 8: simpan pengaduan berstatus Menunggu Diterima, catat ip_pelapor,
    // lalu buat nomor pengaduan berurutan.
    // Nomor contoh di bawah dipakai agar alur antarmuka dapat dicoba utuh.
    return back()->with('nomor_pengaduan', 'PGD-2026-0006');
})->name('pengaduan-warga.kirim');

Route::get('/lacak-pengaduan', function () {
    return view('pages.publik.lacak', ['title' => 'Lacak Pengaduan']);
})->name('lacak-pengaduan');

/*
|--------------------------------------------------------------------------
| Modul Pengaduan
|--------------------------------------------------------------------------
|
| Berbeda dari modul lain, pengaduan tidak memakai verifikasi data melainkan
| alur status berurutan: Menunggu Diterima, Diterima, Diproses, Selesai.
| Perpindahan hanya boleh maju satu tahap (rules.md bagian 10b poin 4).
|
| Antarmuka sudah mencegah lompatan dengan hanya merender satu tombol tujuan,
| dan pemeriksaan ulang di sisi server memakai StatusPengaduan::bolehPindahKe()
| dikerjakan pada Tahap 8.
|
| Rute rekap didaftarkan sebelum rute berparameter.
|
*/
Route::get('/pengaduan', function () {
    return view('pages.pengaduan.index', ['title' => 'Pengaduan']);
})->name('pengaduan.index');

Route::get('/pengaduan/rekap', function () {
    return view('pages.pengaduan.rekap', ['title' => 'Rekap Pengaduan']);
})->name('pengaduan.rekap');

Route::get('/pengaduan/{id}', function (int $id) {
    $data = collect(\App\Support\DummyData::pengaduan())->firstWhere('id_pengaduan', $id);

    abort_if($data === null, 404);

    return view('pages.pengaduan.detail', ['title' => $data['nomor_pengaduan'], 'data' => $data]);
})->where('id', '[0-9]+')->name('pengaduan.detail');

Route::post('/pengaduan', function () {
    // Dicatat petugas atas laporan lisan warga; sumber_laporan bernilai Petugas.
    return redirect()->route('pengaduan.index')
        ->with('sukses', 'Pengaduan tercatat dan menunggu diterima petugas.');
})->name('pengaduan.simpan');

Route::post('/pengaduan/{id}/tangani', function (int $id) {
    // Tahap 8: periksa ulang StatusPengaduan::bolehPindahKe() sebelum menyimpan,
    // lalu tambahkan baris penanganan_pengaduan dan perbarui status pengaduan.
    return redirect()->route('pengaduan.detail', ['id' => $id, 'tab' => 'riwayat'])
        ->with('sukses', 'Penanganan tercatat dan status pengaduan diperbarui.');
})->where('id', '[0-9]+')->name('pengaduan.tangani');

Route::delete('/pengaduan/{id}', function () {
    return redirect()->route('pengaduan.index')->with('sukses', 'Pengaduan dihapus.');
})->where('id', '[0-9]+')->name('pengaduan.hapus');

/*
|--------------------------------------------------------------------------
| Kependudukan, Kelembagaan, Pertanian, dan Sistem
|--------------------------------------------------------------------------
|
| Task 2.14 sampai 2.20. Halaman gelombang 2 memakai pola daftar yang sama,
| dibungkus komponen x-sim.halaman-daftar agar tidak menyalin markup.
|
*/
Route::get('/kependudukan/rekap', function () {
    return view('pages.kependudukan.rekap', ['title' => 'Rekap Kependudukan']);
})->name('kependudukan.rekap');

Route::get('/poktan', function () {
    return view('pages.poktan.index', ['title' => 'Kelompok Tani']);
})->name('poktan.index');

Route::get('/poktan/{id}', function (int $id) {
    $data = collect(\App\Support\DummyData::poktan())->firstWhere('id_poktan', $id);

    abort_if($data === null, 404);

    return view('pages.poktan.detail', ['title' => $data['nama'], 'data' => $data]);
})->where('id', '[0-9]+')->name('poktan.detail');

Route::get('/alsintan', function () {
    return view('pages.alsintan.index', ['title' => 'Alsintan']);
})->name('alsintan.index');

Route::get('/saprotan', function () {
    return view('pages.saprotan.index', ['title' => 'Saprotan']);
})->name('saprotan.index');

Route::get('/komoditas', function () {
    return view('pages.komoditas.index', ['title' => 'Data Komoditas']);
})->name('komoditas.index');

Route::get('/musim-tanam', function () {
    return view('pages.komoditas.musim-tanam', ['title' => 'Musim Tanam']);
})->name('musim-tanam');

Route::get('/riwayat-tanam', function () {
    return view('pages.komoditas.riwayat-tanam', ['title' => 'Riwayat Tanam']);
})->name('riwayat-tanam');

Route::get('/infrastruktur', function () {
    return view('pages.infrastruktur.index', ['title' => 'Infrastruktur Pertanian']);
})->name('infrastruktur.index');

Route::get('/laporan', function () {
    return view('pages.laporan.index', ['title' => 'Pusat Laporan']);
})->name('laporan');

Route::get('/pengguna', function () {
    return view('pages.pengguna.index', ['title' => 'Manajemen Pengguna']);
})->name('pengguna.index');

Route::get('/pengaturan/role', function () {
    return view('pages.pengguna.role', ['title' => 'Role dan Hak Akses']);
})->name('pengaturan.role');

Route::get('/audit-log', function () {
    return view('pages.pengguna.audit-log', ['title' => 'Audit Log']);
})->name('audit-log');

/*
|--------------------------------------------------------------------------
| Dokumen Privat
|--------------------------------------------------------------------------
|
| Berkas pada storage/app/private hanya dapat diakses lewat rute ini, agar
| setiap permintaan melewati pemeriksaan hak akses lebih dulu. Rute bawaan
| Laravel /storage/{path} sengaja dimatikan pada config/filesystems.php.
|
*/
Route::get('/dokumen/{modul}/{id}/{namaBerkas}', [DokumenController::class, 'tampilkan'])
    ->where('modul', '[a-z_]+')
    ->where('id', '[0-9]+')
    ->name('dokumen.tampilkan');
