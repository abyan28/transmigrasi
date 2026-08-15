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
| Sistem sengaja TIDAK memiliki rute pendaftaran mandiri; seluruh akun dibuat
| Admin. Pemulihan kata sandi tersedia lewat dua jalur yang keduanya sah:
| kode verifikasi ke surel dinas, dan penyetelan ulang oleh Admin. Jalur
| kedua dipertahankan karena jaringan di lokus tidak selalu memadai
| (agents/rules.md bagian 14b poin 7 sampai 12).
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

/*
 * Pemulihan kata sandi mandiri, memakai kode enam digit ke surel dinas.
 * Melengkapi jalur Admin, tidak menggantikannya (rules.md 14b poin 7-12).
 */
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
 * Rute tulis data master kawasan. Tampilannya selesai pada Tahap 2;
 * penyimpanan sungguhan dikerjakan pada Tahap 4.
 */
Route::post('/wilayah', function () {
    // Tahap 4: simpan pada tabel sesuai tingkat yang dipilih. Provinsi tidak
    // memiliki induk, sehingga kolom induknya diabaikan.
    return redirect()->route('wilayah')
        ->with('sukses', 'Data wilayah tersimpan.');
})->name('wilayah.simpan');

Route::post('/master/satuan', function () {
    // Tahap 4: faktor_ke_ton wajib lebih besar dari nol, sebab dipakai
    // sebagai pengali pada seluruh rekap panen.
    return redirect()->route('master.satuan')
        ->with('sukses', 'Data master satuan tersimpan.');
})->name('satuan.simpan');

Route::post('/kawasan', function () {
    // Tahap 4: simpan beserta unggahan salinan SK penetapan.
    return redirect()->route('kawasan')
        ->with('sukses', 'Data kawasan transmigrasi tersimpan.');
})->name('kawasan.simpan');

Route::post('/sp', function () {
    // Tahap 4: SP menempel pada desa dan kawasan sekaligus, sehingga kedua
    // foreign key wajib terisi (erd.md bagian 7.0).
    return redirect()->route('sp.index')
        ->with('sukses', 'Data satuan permukiman tersimpan.');
})->name('sp.simpan');

Route::post('/sp/inventaris', function () {
    // Tahap 4: barang bergerak milik SP.
    return redirect()->route('sp.inventaris')
        ->with('sukses', 'Data inventaris SP tersimpan.');
})->name('inventaris.simpan');

Route::post('/sp/fasilitas', function () {
    // Tahap 4: jenis_fasilitas wajib dari enum agar terbaca penilaian
    // kondisi SP; nama_fasilitas tetap teks bebas.
    return redirect()->route('sp.fasilitas')
        ->with('sukses', 'Data fasilitas SP tersimpan.');
})->name('fasilitas.simpan');

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
    return redirect()->route('transmigran.index')
        ->with('sukses', 'Data transmigran tersimpan.');
})->name('transmigran.simpan');

Route::put('/transmigran/{id}', function (int $id) {
    return redirect()->route('transmigran.detail', $id)
        ->with('sukses', 'Perubahan data transmigran tersimpan.');
})->where('id', '[0-9]+')->name('transmigran.perbarui');

Route::delete('/transmigran/{id}', function () {
    // Tahap 5: soft delete agar data tetap dapat dipulihkan.
    return redirect()->route('transmigran.index')
        ->with('sukses', 'Data transmigran dihapus.');
})->where('id', '[0-9]+')->name('transmigran.hapus');

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
        ->with('sukses', 'Data rumah tersimpan.');
})->name('rumah.simpan');

Route::put('/rumah/{id}', function (int $id) {
    // Tahap 5: pergantian penghuni menambah baris riwayat_penghunian baru.
    return redirect()->route('rumah.detail', $id)->with('sukses', 'Perubahan data rumah tersimpan.');
})->where('id', '[0-9]+')->name('rumah.perbarui');

Route::delete('/rumah/{id}', function () {
    return redirect()->route('rumah.index')->with('sukses', 'Data rumah dihapus.');
})->where('id', '[0-9]+')->name('rumah.hapus');

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
        ->with('sukses', 'Data lahan tersimpan.');
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
        ->with('sukses', 'Hasil panen tersimpan.');
})->name('panen.simpan');

Route::put('/panen/{id}', function (int $id) {
    return redirect()->route('panen.detail', $id)->with('sukses', 'Perubahan catatan panen tersimpan.');
})->where('id', '[0-9]+')->name('panen.perbarui');

Route::delete('/panen/{id}', function () {
    return redirect()->route('panen.index')->with('sukses', 'Catatan panen dihapus.');
})->where('id', '[0-9]+')->name('panen.hapus');

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

Route::post('/pengaduan-warga', function (Illuminate\Http\Request $permintaan) {
    // Tahap 8: simpan pengaduan berstatus Menunggu Diterima, catat ip_pelapor,
    // buat nomor pengaduan berurutan, lalu kirim nomornya ke surel pelapor
    // bila diisi.
    //
    // Nomor contoh sengaja memakai salah satu yang BENAR-BENAR ADA pada data
    // contoh. Sebelumnya dipakai PGD-2026-0006 yang tidak pernah ada, sehingga
    // tombol "Lihat Perkembangan Laporan" selalu berujung pada keadaan nomor
    // tidak ditemukan; kontrol semacam itu dilarang (R-26).
    return back()
        ->with('nomor_pengaduan', 'PGD-2026-0003')
        ->with('email_pelapor', $permintaan->input('email_pelapor'));
})->name('pengaduan-warga.kirim');

Route::get('/lacak-pengaduan', function () {
    return view('pages.publik.lacak', ['title' => 'Lacak Pengaduan']);
})->name('lacak-pengaduan');

/*
|--------------------------------------------------------------------------
| Modul Pengaduan
|--------------------------------------------------------------------------
|
| Pengaduan memakai alur status berurutan: Menunggu Diterima, Diterima,
| Diproses, lalu Selesai.
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


Route::post('/poktan', function () {
    // Tahap 6: ketua dipilih dari transmigran, sehingga ketua_transmigran_id
    // menjadi foreign key, bukan teks bebas.
    return redirect()->route('poktan.index')
        ->with('sukses', 'Data kelompok tani tersimpan.');
})->name('poktan.simpan');

Route::put('/poktan/{id}', function (int $id) {
    return redirect()->route('poktan.detail', $id)
        ->with('sukses', 'Perubahan profil kelompok tani tersimpan.');
})->where('id', '[0-9]+')->name('poktan.perbarui');

Route::post('/anggota-poktan', function () {
    // Tahap 6: anggota yang berhenti DITANDAI Sudah Keluar, tidak pernah
    // dihapus, agar catatan penyaluran saprotan tetap memiliki penerima
    // yang jelas (rules.md 5.1 catatan 5).
    return redirect()->back()
        ->with('sukses', 'Data anggota kelompok tani tersimpan.');
})->name('anggota-poktan.simpan');
Route::get('/alsintan', function () {
    return view('pages.alsintan.index', ['title' => 'Alsintan']);
})->name('alsintan.index');

Route::get('/alsintan/{id}', function (int $id) {
    $data = collect(\App\Support\DummyData::alsintan())->firstWhere('id_alsintan', $id);

    abort_if($data === null, 404);

    return view('pages.alsintan.detail', ['title' => $data['nama_alat'], 'data' => $data]);
})->where('id', '[0-9]+')->name('alsintan.detail');

Route::post('/alsintan', function () {
    // Tahap 6: validasi, simpan, catat audit log. Pemilik disimpan pada
    // poktan_id atau transmigran_id sesuai jenis kepemilikan, tidak pernah
    // keduanya sekaligus.
    return redirect()->route('alsintan.index')
        ->with('sukses', 'Data alsintan tersimpan.');
})->name('alsintan.simpan');

Route::put('/alsintan/{id}', function (int $id) {
    return redirect()->route('alsintan.detail', $id)
        ->with('sukses', 'Perubahan data alsintan tersimpan.');
})->where('id', '[0-9]+')->name('alsintan.perbarui');

Route::get('/saprotan', function () {
    return view('pages.saprotan.index', ['title' => 'Saprotan']);
})->name('saprotan.index');

Route::get('/saprotan/{id}', function (int $id) {
    $data = collect(\App\Support\DummyData::saprotan())->firstWhere('id_saprotan', $id);

    abort_if($data === null, 404);

    return view('pages.saprotan.detail', ['title' => $data['nama'], 'data' => $data]);
})->where('id', '[0-9]+')->name('saprotan.detail');

Route::post('/saprotan', function () {
    // Tahap 6: validasi, simpan, catat audit log. Penerima individu wajib
    // berstatus anggota aktif; pemeriksaan diulang di sisi server sebab
    // penyaringan dropdown saja tidak menghalangi kiriman langsung.
    return redirect()->route('saprotan.index')
        ->with('sukses', 'Data saprotan tersimpan.');
})->name('saprotan.simpan');

Route::put('/saprotan/{id}', function (int $id) {
    return redirect()->route('saprotan.detail', $id)
        ->with('sukses', 'Perubahan data saprotan tersimpan.');
})->where('id', '[0-9]+')->name('saprotan.perbarui');

Route::get('/komoditas', function () {
    return view('pages.komoditas.index', ['title' => 'Data Komoditas']);
})->name('komoditas.index');

Route::get('/komoditas/{id}', function (int $id) {
    $data = collect(\App\Support\DummyData::komoditas())->firstWhere('id_komoditas', $id);

    abort_if($data === null, 404);

    return view('pages.komoditas.detail', ['title' => $data['nama'], 'data' => $data]);
})->where('id', '[0-9]+')->name('komoditas.detail');

Route::post('/komoditas', function () {
    // Tahap 7: validasi, simpan, catat audit log.
    return redirect()->route('komoditas.index')
        ->with('sukses', 'Data komoditas tersimpan.');
})->name('komoditas.simpan');

Route::put('/komoditas/{id}', function (int $id) {
    // Tahap 7: perubahan satuan baku hanya berlaku bagi pencatatan panen
    // berikutnya. Panen yang sudah tersimpan menyalin satuannya sendiri,
    // sehingga angka lama tidak ikut berubah makna.
    return redirect()->route('komoditas.detail', $id)
        ->with('sukses', 'Perubahan data komoditas tersimpan.');
})->where('id', '[0-9]+')->name('komoditas.perbarui');

Route::get('/musim-tanam', function () {
    return view('pages.komoditas.musim-tanam', ['title' => 'Musim Tanam']);
})->name('musim-tanam');

Route::get('/riwayat-tanam', function () {
    return view('pages.komoditas.riwayat-tanam', ['title' => 'Riwayat Tanam']);
})->name('riwayat-tanam');

Route::post('/musim-tanam', function () {
    // Tahap 7: nama dan tahun disimpan pada dua kolom terpisah agar rekap
    // per tahun dapat dihitung tanpa mengurai teks gabungan.
    return redirect()->route('musim-tanam')
        ->with('sukses', 'Data musim tanam tersimpan.');
})->name('musim-tanam.simpan');

Route::post('/riwayat-tanam', function () {
    // Tahap 7: lahan_id wajib, sebab lokasi produksi hasil panen dibaca
    // lewat rantai riwayat tanam ke lahan ke satuan permukiman.
    return redirect()->route('riwayat-tanam')
        ->with('sukses', 'Catatan penanaman tersimpan.');
})->name('riwayat-tanam.simpan');

Route::get('/infrastruktur', function () {
    return view('pages.infrastruktur.index', ['title' => 'Infrastruktur SP']);
})->name('infrastruktur.index');

Route::get('/infrastruktur/{id}', function (int $id) {
    $data = collect(\App\Support\DummyData::infrastruktur())->firstWhere('id_infrastruktur', $id);

    abort_if($data === null, 404);

    return view('pages.infrastruktur.detail', ['title' => $data['nama'], 'data' => $data]);
})->where('id', '[0-9]+')->name('infrastruktur.detail');

Route::post('/infrastruktur', function () {
    // Tahap 8: validasi, simpan, catat audit log. Modul pendataan aset,
    // sehingga tidak ada alur laporan kerusakan di sini.
    return redirect()->route('infrastruktur.index')
        ->with('sukses', 'Data aset infrastruktur tersimpan.');
})->name('infrastruktur.simpan');

Route::put('/infrastruktur/{id}', function (int $id) {
    // Tahap 8: perubahan kondisi ikut memengaruhi penilaian kondisi SP pada
    // penilaian berikutnya, bukan penilaian yang sudah tersimpan.
    return redirect()->route('infrastruktur.detail', $id)
        ->with('sukses', 'Perubahan data aset tersimpan.');
})->where('id', '[0-9]+')->name('infrastruktur.perbarui');

Route::get('/laporan', function () {
    return view('pages.laporan.index', ['title' => 'Pusat Laporan']);
})->name('laporan');

Route::get('/pengguna', function () {
    return view('pages.pengguna.index', ['title' => 'Manajemen Pengguna']);
})->name('pengguna.index');

Route::post('/pengguna', function (Illuminate\Http\Request $permintaan) {
    // Tahap 3: validasi lewat ValidationRules, bangkitkan kata sandi sementara,
    // simpan hashnya, tandai password_harus_diganti, simpan penugasan SP, catat
    // audit log, lalu kirim kredensial ke surel petugas.
    //
    // Username sengaja tidak diminta di sini. Petugas membuatnya sendiri saat
    // pertama kali masuk, bersamaan dengan penggantian kata sandi sementara
    // (rules.md 14b).
    return redirect()->route('pengguna.index')
        ->with('sukses', 'Akun petugas tersimpan.')
        ->with('kredensial_baru', [
            'nama' => $permintaan->input('nama', 'petugas'),
            'email' => $permintaan->input('email', '-'),
            // Tahap 3: dibangkitkan Str::password(), bukan nilai tetap seperti ini.
            'password' => 'Tmg-7K4pQ2',
        ]);
})->name('pengguna.simpan');

Route::put('/pengguna/{id}', function (int $id) {
    // Tahap 3: kata sandi tidak pernah ikut diperbarui di sini
    // (rules.md 14b poin 14).
    return redirect()->route('pengguna.index')
        ->with('sukses', 'Perubahan data akun tersimpan.');
})->where('id', '[0-9]+')->name('pengguna.perbarui');

Route::post('/pengguna/{id}/setel-sandi', function (int $id) {
    // Tahap 3: timpa hash kata sandi, setel password_harus_diganti menjadi
    // TRUE, lalu catat audit log beraksi Reset Kata Sandi beserta pelakunya
    // (rules.md 14b poin 13 dan 15).
    return redirect()->route('pengguna.index')
        ->with('sukses', 'Kata sandi sementara tersimpan. Serahkan langsung kepada petugas yang bersangkutan.');
})->where('id', '[0-9]+')->name('pengguna.setel-sandi');

Route::post('/pengguna/{id}/nonaktifkan', function (int $id) {
    // Tahap 3: tolak bila sasaran adalah Admin aktif terakhir
    // (rules.md 14b poin 16). Pemeriksaan wajib diulang di sisi server,
    // sebab penyembunyian tombol saja tidak menghalangi permintaan langsung.
    return redirect()->route('pengguna.index')
        ->with('sukses', 'Akun dinonaktifkan. Seluruh riwayat tindakannya tetap tersimpan.');
})->where('id', '[0-9]+')->name('pengguna.nonaktifkan');

Route::post('/pengguna/{id}/aktifkan', function (int $id) {
    // Tahap 3: setel is_aktif menjadi TRUE, catat audit log dengan aksi
    // AktifkanAkun. Akun yang dipulihkan memakai kredensial lamanya, sebab
    // penonaktifan tidak pernah mengubah kata sandi.
    return redirect()->route('pengguna.index')
        ->with('sukses', 'Akun diaktifkan kembali. Petugas dapat masuk memakai kredensial yang sama.');
})->where('id', '[0-9]+')->name('pengguna.aktifkan');

Route::get('/pengaturan/role', function () {
    return view('pages.pengguna.role', ['title' => 'Role dan Hak Akses']);
})->name('pengaturan.role');

Route::post('/pengaturan/role', function () {
    // Tahap 3: simpan role beserta pasangan izinnya pada tabel pivot.
    return redirect()->route('pengaturan.role')
        ->with('sukses', 'Role baru tersimpan.');
})->name('role.simpan');

Route::put('/pengaturan/role/{id}', function (int $id) {
    // Tahap 3: tolak perubahan pada role terkunci (rules.md 5.0a), lalu
    // segarkan izin seluruh akun yang memakai role ini.
    return redirect()->route('pengaturan.role')
        ->with('sukses', 'Susunan izin role tersimpan.');
})->where('id', '[0-9]+')->name('role.perbarui');

Route::delete('/pengaturan/role/{id}', function (int $id) {
    // Tahap 3: tolak bila role bawaan atau masih dipakai minimal satu akun
    // (rules.md 5.0c poin 8 dan 9). Kedua pemeriksaan wajib diulang di sisi
    // server, sebab penyembunyian tombol saja tidak menghalangi permintaan
    // langsung. Alasan penghapusan ikut dicatat pada audit log.
    return redirect()->route('pengaturan.role')
        ->with('sukses', 'Role dihapus. Susunan kewenangan akun lain tidak terpengaruh.');
})->where('id', '[0-9]+')->name('role.hapus');

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

/*
|--------------------------------------------------------------------------
| Rute tulis tambahan Tahap 2
|--------------------------------------------------------------------------
|
| Melengkapi kolom aksi yang kini seragam di seluruh halaman daftar.
| Penyimpanan sungguhan dikerjakan pada tahap backend masing-masing modul.
|
*/

Route::put('/sp/{id}', function (int $id) {
    // Tahap 4: SP menempel pada desa dan kawasan sekaligus.
    return redirect()->route('sp.index')->with('sukses', 'Perubahan data satuan permukiman tersimpan.');
})->where('id', '[0-9]+')->name('sp.perbarui');

Route::delete('/sp/{id}', function (int $id) {
    // Tahap 4: memakai soft delete, sebab seluruh data kawasan menaut SP.
    return redirect()->route('sp.index')->with('sukses', 'Satuan permukiman dihapus.');
})->where('id', '[0-9]+')->name('sp.hapus');

Route::put('/sp/inventaris/{id}', function (int $id) {
    return redirect()->route('sp.inventaris')->with('sukses', 'Perubahan data inventaris tersimpan.');
})->where('id', '[0-9]+')->name('inventaris.perbarui');

Route::delete('/sp/inventaris/{id}', function (int $id) {
    return redirect()->route('sp.inventaris')->with('sukses', 'Data inventaris dihapus.');
})->where('id', '[0-9]+')->name('inventaris.hapus');

Route::put('/sp/fasilitas/{id}', function (int $id) {
    return redirect()->route('sp.fasilitas')->with('sukses', 'Perubahan data fasilitas tersimpan.');
})->where('id', '[0-9]+')->name('fasilitas.perbarui');

Route::delete('/sp/fasilitas/{id}', function (int $id) {
    // Tahap 4: penilaian kondisi SP berikutnya tidak lagi menghitung fasilitas ini.
    return redirect()->route('sp.fasilitas')->with('sukses', 'Data fasilitas dihapus.');
})->where('id', '[0-9]+')->name('fasilitas.hapus');

Route::put('/kawasan/{id}', function (int $id) {
    return redirect()->route('kawasan')->with('sukses', 'Perubahan data kawasan tersimpan.');
})->where('id', '[0-9]+')->name('kawasan.perbarui');

Route::delete('/kawasan/{id}', function (int $id) {
    return redirect()->route('kawasan')->with('sukses', 'Kawasan transmigrasi dihapus.');
})->where('id', '[0-9]+')->name('kawasan.hapus');

Route::put('/wilayah/{id}', function (int $id) {
    return redirect()->route('wilayah')->with('sukses', 'Perubahan data wilayah tersimpan.');
})->where('id', '[0-9]+')->name('wilayah.perbarui');

Route::delete('/wilayah/{id}', function (int $id) {
    return redirect()->route('wilayah')->with('sukses', 'Data wilayah dihapus.');
})->where('id', '[0-9]+')->name('wilayah.hapus');

Route::put('/master/satuan/{id}', function (int $id) {
    // Tahap 4: perubahan faktor konversi TIDAK mengubah panen yang sudah
    // tersimpan, sebab tiap panen menyalin satuannya sendiri.
    return redirect()->route('master.satuan')->with('sukses', 'Perubahan data satuan tersimpan.');
})->where('id', '[0-9]+')->name('satuan.perbarui');

Route::delete('/master/satuan/{id}', function (int $id) {
    // Tahap 4: tolak bila masih dipakai komoditas mana pun.
    return redirect()->route('master.satuan')->with('sukses', 'Data satuan dihapus.');
})->where('id', '[0-9]+')->name('satuan.hapus');

Route::delete('/poktan/{id}', function (int $id) {
    return redirect()->route('poktan.index')->with('sukses', 'Kelompok tani dihapus.');
})->where('id', '[0-9]+')->name('poktan.hapus');

Route::put('/musim-tanam/{id}', function (int $id) {
    return redirect()->route('musim-tanam')->with('sukses', 'Perubahan musim tanam tersimpan.');
})->where('id', '[0-9]+')->name('musim-tanam.perbarui');

Route::delete('/musim-tanam/{id}', function (int $id) {
    return redirect()->route('musim-tanam')->with('sukses', 'Musim tanam dihapus.');
})->where('id', '[0-9]+')->name('musim-tanam.hapus');

Route::put('/riwayat-tanam/{id}', function (int $id) {
    return redirect()->route('riwayat-tanam')->with('sukses', 'Perubahan catatan penanaman tersimpan.');
})->where('id', '[0-9]+')->name('riwayat-tanam.perbarui');

Route::delete('/riwayat-tanam/{id}', function (int $id) {
    return redirect()->route('riwayat-tanam')->with('sukses', 'Catatan penanaman dihapus.');
})->where('id', '[0-9]+')->name('riwayat-tanam.hapus');

Route::delete('/alsintan/{id}', function (int $id) {
    return redirect()->route('alsintan.index')->with('sukses', 'Data alsintan dihapus.');
})->where('id', '[0-9]+')->name('alsintan.hapus');

Route::delete('/saprotan/{id}', function (int $id) {
    return redirect()->route('saprotan.index')->with('sukses', 'Data saprotan dihapus.');
})->where('id', '[0-9]+')->name('saprotan.hapus');

Route::delete('/komoditas/{id}', function (int $id) {
    // Tahap 7: tolak bila masih dipakai riwayat tanam atau hasil panen.
    return redirect()->route('komoditas.index')->with('sukses', 'Data komoditas dihapus.');
})->where('id', '[0-9]+')->name('komoditas.hapus');

Route::delete('/infrastruktur/{id}', function (int $id) {
    return redirect()->route('infrastruktur.index')->with('sukses', 'Data aset infrastruktur dihapus.');
})->where('id', '[0-9]+')->name('infrastruktur.hapus');

Route::delete('/pengguna/{id}', function (int $id) {
    // Tahap 3: akun tidak pernah dihapus, hanya dinonaktifkan (rules.md 14b).
    // Rute ini sengaja tidak disediakan; penonaktifan memakai rute tersendiri.
    abort(405);
})->where('id', '[0-9]+')->name('pengguna.hapus');

/*
|--------------------------------------------------------------------------
| Template impor data
|--------------------------------------------------------------------------
|
| Menjawab kebutuhan PRD 8.1: sinyal di lokus tidak selalu stabil, sehingga
| petugas perlu mengunduh template, mengisinya luring di lapangan, lalu
| mengunggahnya kembali saat sambungan tersedia.
|
| Satu rute melayani seluruh entitas, sebab yang membedakan hanya susunan
| kolomnya. Mendaftarkan empat belas rute terpisah hanya akan menyalin
| penanganan yang sama empat belas kali.
|
| Tahap 10: menghasilkan berkas .xlsx sungguhan beserta baris contoh dan
| daftar nilai baku pada kolom berjenis pilihan.
*/
Route::get('/template-impor/{entitas}', function (string $entitas) {
    return back()->with('info', 'Template impor ' . str_replace('-', ' ', $entitas)
        . ' akan tersedia setelah backend selesai.');
})->where('entitas', '[a-z\-]+')->name('template-impor');
