<?php

/*
|--------------------------------------------------------------------------
| Rute Internal DIGITRANS (Task 3.2b)
|--------------------------------------------------------------------------
|
| Dimuat oleh bootstrap/app.php lewat closure `then:` dengan middleware
| ['web', 'auth', 'pastikan.ganti.sandi']. Setiap rute di berkas ini WAJIB
| login; pengguna berkata-sandi sementara dikunci ke /ganti-kata-sandi.
|
| Rute publik (masuk, pemulihan sandi, kanal pengaduan warga) tetap di
| routes/web.php TANPA `auth`.
|
*/

use App\Enums\JenisReferensi;
use App\Enums\PrioritasPengaduan;
use App\Enums\StatusKondisiSp;
use App\Enums\StatusPengaduan;
use App\Http\Controllers\AlsintanController;
use App\Http\Controllers\AnggotaPoktanController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DokumenController;
use App\Http\Controllers\FasilitasSpController;
use App\Http\Controllers\HasilPanenController;
use App\Http\Controllers\InfrastrukturController;
use App\Http\Controllers\InventarisSpController;
use App\Http\Controllers\KawasanController;
use App\Http\Controllers\KependudukanController;
use App\Http\Controllers\KomoditasController;
use App\Http\Controllers\LahanController;
use App\Http\Controllers\MasterReferensiController;
use App\Http\Controllers\MasterSatuanController;
use App\Http\Controllers\PenanamanController;
use App\Http\Controllers\PengaturanPenggunaController;
use App\Http\Controllers\PengaturanRoleController;
use App\Http\Controllers\PenilaianKondisiController;
use App\Http\Controllers\PoktanController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\RumahController;
use App\Http\Controllers\SaprotanController;
use App\Http\Controllers\SpController;
use App\Http\Controllers\TransmigranController;
use App\Http\Controllers\WilayahController;
use App\Support\DummyData;
use App\Support\LaporanData;
use App\Support\PenilaianKondisiSp;
use App\Support\RekapPanen;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// Dashboard monitoring kawasan, 15 indikator dengan data contoh.
// Penggantian ke query nyata dikerjakan pada Tahap 9.
Route::get('/', function () {
    $ringkasan = DummyData::ringkasanDashboard();
    $deret = DummyData::deretTahunan();
    $rekapSp = DummyData::rekapPerSp();

    $penilaianSp = PenilaianKondisiSp::nilaiSeluruhSp();
    $statusInfra = DummyData::statusInfrastruktur();
    $sebaranPekerjaan = DummyData::sebaranPekerjaan();
    $rekapStatusPengaduan = DummyData::rekapPengaduan('status');
    $sebaranKomoditas = DummyData::sebaranKomoditas();
    $rekapPenghuni = DummyData::rekapPenghuni();
    $pengaduan = DummyData::pengaduan();

    /*
     * Tahun terakhir yang terdata, dipakai melabeli kartu volume panen.
     *
     * Dibaca dari deret, BUKAN dari `date('Y')`. Yang dapat dijamin benar
     * adalah "angka ini milik tahun terakhir yang terdata"; menyebutnya tahun
     * berjalan menjanjikan hal yang belum tentu benar begitu tahun berganti
     * sementara datanya belum masuk.
     */
    $tahunTerakhir = end($deret['tahun']);
    reset($deret['tahun']);

    // Isu prioritas: pengaduan yang belum selesai, diurutkan dari yang paling
    // mendesak.
    $urutanPrioritas = ['Mendesak' => 0, 'Tinggi' => 1, 'Sedang' => 2, 'Rendah' => 3];
    $isuPrioritas = array_filter($pengaduan, fn ($p) => $p['status'] !== 'Selesai');
    usort($isuPrioritas, fn ($a, $b) => $urutanPrioritas[$a['prioritas']] <=> $urutanPrioritas[$b['prioritas']]);

    return view('pages.dashboard.index', [
        'title' => 'Dashboard',
        'ringkasan' => $ringkasan,
        'deret' => $deret,
        'rekapSp' => $rekapSp,

        // Indikator ke-16: kondisi layanan dasar tiap SP.
        'penilaianSp' => $penilaianSp,
        'rekapKondisi' => PenilaianKondisiSp::rekapStatus(),

        // Penyebab utama tiap SP, dahulu dihitung di dalam perulangan tabel.
        'penyebabSp' => collect($penilaianSp)
            ->mapWithKeys(fn ($p) => [$p['satuan_permukiman_id'] => PenilaianKondisiSp::penyebabUtama($p)])
            ->all(),

        'statusInfra' => $statusInfra,
        'sebaranPekerjaan' => $sebaranPekerjaan,
        'rekapStatusPengaduan' => $rekapStatusPengaduan,
        'sebaranKomoditas' => $sebaranKomoditas,
        'rekapPenghuni' => $rekapPenghuni,
        'daftarSp' => DummyData::satuanPermukiman(),
        'pengaduan' => $pengaduan,

        'persenHuni' => round($ringkasan['rumah_terhuni'] / $ringkasan['rumah_total'] * 100),
        'tahunTerakhir' => $tahunTerakhir,

        /*
         * Porsi produksi terhadap penyebutnya masing-masing. Angka mutlak tanpa
         * porsi tidak dapat dinilai pembaca: 24 ha puso terdengar kecil bagi
         * kawasan 3.250 ha, padahal yang menentukan luas yang ditanam.
         *
         * Diformat memakai `number_format`, bukan `round`: round menghasilkan
         * "19.5" bertitik, sedangkan seluruh angka lain di halaman ini memakai
         * koma sebagai pemisah desimal. Satu angka bertitik di antara puluhan
         * angka berkoma terbaca sebagai kekeliruan cetak.
         */
        'persenTanam' => number_format($ringkasan['realisasi_tanam_ha'] / $ringkasan['luas_lahan_total'] * 100, 1, ',', '.'),
        'persenPuso' => number_format($ringkasan['puso_ha'] / $ringkasan['realisasi_tanam_ha'] * 100, 1, ',', '.'),

        /*
         * Komoditas dengan volume terbesar, dipakai kartu komoditas utama.
         *
         * Dipilih berdasarkan NILAI, bukan urutan larik. `array_key_first()`
         * sempat dipakai dan kebetulan benar hanya karena `sebaranKomoditas()`
         * ditulis terurut; begitu urutannya berubah, kartu ini akan menampilkan
         * komoditas yang keliru tanpa ada yang menyadarinya.
         *
         * "Utama" berbeda dari "unggulan": yang ini dihitung dari volume dan
         * berubah mengikuti musim, sedangkan unggulan ditetapkan menurut
         * proposal atau kebijakan dinas (`rules.md` 8.1) dan ditandai petugas.
         */
        'komoditasUtama' => array_search(max($sebaranKomoditas), $sebaranKomoditas, true),

        'isuPrioritas' => $isuPrioritas,

        /*
         * Bahan grafik disusun di sini, bukan di dalam blok skrip, karena
         * direktif @js tidak dapat mengurai array bersarang bertingkat.
         */
        'dataGrafik' => [
            'tahun' => $deret['tahun'],
            'jiwa' => $deret['jumlah_jiwa'],
            'kk' => $deret['jumlah_kk'],
            'petani' => $deret['jumlah_petani'],
            'pendapatan' => $deret['pendapatan_rata_rata'],
            'kkMasuk' => $deret['kk_masuk'],
            'kkKeluar' => $deret['kk_keluar'],
            'volumePanen' => $deret['volume_panen'],
            'harga' => $deret['harga_rata_rata'],
            'statusPengaduanNama' => array_column($rekapStatusPengaduan, 'nama'),
            'statusPengaduanNilai' => array_column($rekapStatusPengaduan, 'jumlah'),
            'pekerjaanNama' => array_keys($sebaranPekerjaan),
            'pekerjaanNilai' => array_values($sebaranPekerjaan),
            'komoditasNama' => array_keys($sebaranKomoditas),
            'komoditasNilai' => array_values($sebaranKomoditas),
            'penghuniNama' => array_keys($rekapPenghuni),
            'penghuniNilai' => array_values($rekapPenghuni),
            'infraJenis' => array_column($statusInfra, 'jenis'),
            'infraBaik' => array_column($statusInfra, 'baik'),
            'infraRusakRingan' => array_column($statusInfra, 'rusak_ringan'),
            'infraRusakBerat' => array_column($statusInfra, 'rusak_berat'),
            'spNama' => array_column($rekapSp, 'satuan_permukiman'),
            'spKk' => array_column($rekapSp, 'jumlah_kk'),
            'spPanen' => array_column($rekapSp, 'volume_panen'),

            // Dipakai penelusuran klik menuju /dashboard/sp/{id}
            'spId' => array_column($rekapSp, 'satuan_permukiman_id'),
        ],
    ]);
})->name('beranda');

// Rincian satu satuan permukiman (RESTful baku).
// SP yang tidak dikenal membalas 404 agar alamat karangan tidak menghasilkan
// halaman kosong yang membingungkan.
Route::get('/sp/{sp}', function (int $sp) {
    $data = DummyData::cariSp($sp);

    abort_if($data === null, 404);

    $rekap = DummyData::rekapSp($data['id_satuan_permukiman']);
    $deretSp = DummyData::deretTahunanSp($data['id_satuan_permukiman']);

    return view('pages.sp.detail', [
        'title' => $data['nama'],
        'sp' => $data,
        'rekap' => $rekap,
        'deretSp' => $deretSp,
        'penilaian' => PenilaianKondisiSp::nilai($data['id_satuan_permukiman']),

        'transmigran' => DummyData::saringPerSp(DummyData::transmigran(), $data['nama']),
        'rumah' => DummyData::saringPerSp(DummyData::rumah(), $data['nama']),
        'lahan' => DummyData::saringPerSp(DummyData::lahan(), $data['nama']),
        'poktan' => DummyData::saringPerSp(DummyData::poktan(), $data['nama']),
        'panen' => DummyData::saringPerSp(DummyData::hasilPanen(), $data['nama']),
        'pengaduan' => DummyData::saringPerSp(DummyData::pengaduan(), $data['nama']),
        'infrastruktur' => DummyData::saringPerSp(DummyData::infrastruktur(), $data['nama']),
        'fasilitas' => DummyData::saringPerSp(DummyData::fasilitasSp(), $data['nama']),
        'inventaris' => DummyData::saringPerSp(DummyData::inventarisSp(), $data['nama']),

        // Rute pencapaian menuju SP ini (Tabel 2.1 Monografi, Stage C2).
        'ruteAksesibilitas' => DummyData::ruteAksesibilitasSp($data['id_satuan_permukiman']),

        'persenHuni' => $data['jumlah_kk_terisi'] > 0
            ? round($rekap['rumah_terhuni'] / $data['jumlah_kk_terisi'] * 100)
            : 0,
        'persenIsi' => round($data['jumlah_kk_terisi'] / $data['jumlah_kk_rencana'] * 100),

        'dataGrafik' => [
            'tahun' => $deretSp['tahun'],
            'kk' => $deretSp['jumlah_kk'],
            'panen' => $deretSp['volume_panen'],
        ],
    ]);
})->where('sp', '[0-9]+')->name('sp.detail');

// Redirect 301 untuk kompatibilitas alamat lama /dashboard/sp/{sp}
Route::get('/dashboard/sp/{sp}', function (int $sp) {
    return redirect()->route('sp.detail', ['sp' => $sp], 301);
})->where('sp', '[0-9]+')->name('dashboard.sp');

Route::put('/sp/{sp}', [SpController::class, 'perbarui'])
    ->where('sp', '[0-9]+')->name('sp.perbarui');

// Profil sendiri (Task 3.13). Tanpa `izin:` (PetaIzinRute::dikecualikan) --
// tiap pengguna berhak menyunting kontak & sandinya sendiri.
Route::get('/profil', [ProfilController::class, 'index'])->name('profil');
Route::put('/profil', [ProfilController::class, 'simpan'])->name('profil.simpan');
Route::get('/profil/kata-sandi', [ProfilController::class, 'tampilKataSandi'])->name('profil.kata-sandi');
Route::put('/profil/kata-sandi', [ProfilController::class, 'simpanKataSandi'])->name('profil.kata-sandi.simpan');

Route::get('/tentang', function () {
    return view('pages.tentang.index', ['title' => 'Tentang Sistem']);
})->name('tentang');

Route::get('/panduan', function () {
    return view('pages.panduan.index', ['title' => 'Panduan Penggunaan']);
})->name('panduan');

// Galeri komponen bersama, halaman internal untuk pengembangan.
// Dihapus sebelum penyerahan akhir.
Route::get('/galeri-komponen', function () {
    // Halaman peninjauan komponen, dijadwalkan dihapus bersama `uji-403`.
    // Tetap disisir ide C agar penjaga "view tidak mengambil datanya sendiri"
    // tidak perlu memuat pengecualian yang lalu terlupa dicabut.
    return view('pages.galeri-komponen', [
        'title' => 'Galeri Komponen',
        'ringkasan' => DummyData::ringkasanDashboard(),
        'transmigran' => DummyData::transmigran(),
        'daftarSp' => DummyData::satuanPermukiman(),
        'opsiPrioritasPengaduan' => DummyData::opsiReferensi(JenisReferensi::PrioritasPengaduan),
        'opsiKondisiRumah' => DummyData::opsiReferensi(JenisReferensi::KondisiRumah),
    ]);
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
Route::get('/wilayah', [WilayahController::class, 'index'])->name('wilayah');

Route::get('/master/satuan', [MasterSatuanController::class, 'index'])->name('master.satuan');

/*
 * Data master referensi.
 *
 * Empat belas daftar pilihan yang sebelumnya ditulis sebagai enum di dalam
 * kode, kini dikelola Admin dan Dinas Transmigrasi lewat antarmuka (kamus
 * data 5.6).
 *
 * SATU HALAMAN PER DAFTAR, bukan satu halaman bertab. Semula keempat belasnya
 * berupa tab dalam satu baris, dan itu berhenti bekerja begitu jumlahnya
 * bertambah: bar tab mencapai 2309px pada ruang 705px, sehingga hanya empat
 * tab yang terlihat dan sepuluh sisanya tersembunyi di balik gulir mendatar.
 *
 * TANPA RUTE HAPUS, dan itu disengaja: nilai yang tidak lagi dipakai
 * dinonaktifkan lewat kolom `is_aktif`. Menghapusnya membuat data lama
 * menunjuk pilihan yang lenyap, dan rekapnya kehilangan baris itu tanpa pesan
 * apa pun.
 */
Route::get('/master/referensi', [MasterReferensiController::class, 'index'])->name('master.referensi');

Route::get('/master/referensi/{jenis}', [MasterReferensiController::class, 'jenis'])
    ->where('jenis', '[a-z_]+')->name('referensi.jenis');

Route::post('/master/referensi', [MasterReferensiController::class, 'simpan'])->name('referensi.simpan');

Route::put('/master/referensi/{id}', [MasterReferensiController::class, 'perbarui'])
    ->where('id', '[0-9]+')->name('referensi.perbarui');

/*
 * Pengaturan penilaian kondisi SP.
 *
 * Dua tab pada satu halaman: bobot parameter, dan ambang beserta wording
 * status. Keduanya adalah keputusan KEBIJAKAN yang wajib divalidasi dinas
 * (rules.md 10c poin 13), bukan angka teknis.
 *
 * Dua tab BOLEH di sini, berbeda dari data master referensi yang tabnya
 * dibongkar menjadi kartu: yang membatasi bukan cacah tab melainkan lebar
 * judulnya terhadap wadahnya (ui-spec.md 5.1d), dan dua judul pendek jelas
 * muat dalam satu baris.
 *
 * TANPA RUTE TAMBAH DAN HAPUS. Baris parameter dihasilkan dari jenis
 * infrastruktur dan fasilitas pada data master, sedangkan status wajib tetap
 * tiga sebab `StatusKondisiSp::dariSkor()` hanya mengembalikan tiga keluaran.
 */
Route::get('/master/penilaian-kondisi', [PenilaianKondisiController::class, 'index'])->name('master.penilaian-kondisi');

Route::put('/master/penilaian-kondisi/parameter/{id}', [PenilaianKondisiController::class, 'parameter'])
    ->where('id', '[0-9]+')->name('penilaian-kondisi.parameter');

Route::put('/master/penilaian-kondisi/status/{kode}', [PenilaianKondisiController::class, 'status'])
    ->name('penilaian-kondisi.status');

/*
 * Pengelolaan Konten Sistem (CMS).
 *
 * Mengelola narasi profil kawasan, panduan operasional, identitas aplikasi,
 * portal publik warga, dan banner pengumuman dinas tanpa mengubah kode.
 */
Route::get('/cms', function () {
    return view('pages.cms.index', [
        'title' => 'Pengelolaan Konten',
    ]);
})->name('cms');

Route::put('/cms', function () {
    return redirect()->route('cms')->with('sukses', 'Pengaturan konten berhasil disimpan.');
})->name('cms.simpan');

Route::get('/kawasan', [KawasanController::class, 'index'])->name('kawasan');

// Rute beruas dua didaftarkan sebelum /sp agar tidak tertukar.
Route::get('/sp/inventaris', [InventarisSpController::class, 'index'])->name('sp.inventaris');

Route::get('/sp/fasilitas', [FasilitasSpController::class, 'index'])->name('sp.fasilitas');

/*
 * Rincian inventaris dan fasilitas SP.
 *
 * WAJIB didaftarkan SETELAH rute daftar di atas, sebab '/sp/inventaris'
 * akan tertangkap sebagai '/sp/{id}' bila urutannya terbalik. Pola yang sama
 * sudah dicatat pada rute panen soal '/panen/rekap'.
 *
 * Kedua halaman ini lahir 2026-08-19 bersama tautan objek pengaduan.
 * Sebelumnya keduanya hanya memiliki halaman daftar, sehingga keluhan warga
 * atas sebuah barang atau bangunan tidak punya tempat ditampilkan kembali.
 */
Route::get('/sp/inventaris/{id}', [InventarisSpController::class, 'detail'])
    ->where('id', '[0-9]+')->name('sp.inventaris.detail');

Route::get('/sp/fasilitas/{id}', [FasilitasSpController::class, 'detail'])
    ->where('id', '[0-9]+')->name('sp.fasilitas.detail');

Route::get('/sp', [SpController::class, 'index'])->name('sp.index');

/*
 * Rute tulis data master kawasan. Tampilannya selesai pada Tahap 2;
 * penyimpanan sungguhan dikerjakan pada Tahap 4.
 */
Route::post('/wilayah', [WilayahController::class, 'simpan'])->name('wilayah.simpan');

Route::post('/master/satuan', [MasterSatuanController::class, 'simpan'])->name('satuan.simpan');

Route::post('/kawasan', [KawasanController::class, 'simpan'])->name('kawasan.simpan');

Route::post('/sp', [SpController::class, 'simpan'])->name('sp.simpan');

Route::post('/sp/inventaris', [InventarisSpController::class, 'simpan'])->name('inventaris.simpan');

Route::post('/sp/fasilitas', [FasilitasSpController::class, 'simpan'])->name('fasilitas.simpan');

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
// Task 5.1 (baca) + 5.2 (tulis): seluruhnya `TransmigranController` + Eloquent.
// Yang masih `DummyData` pada rincian: rumah (Task 5.3), lahan + data poktan
// (Task 6). Suksesi memvalidasi `nasib_ketua_poktan` tetapi penerapannya ke
// tabel `poktan` menyusul di Task 6.
Route::get('/transmigran', [TransmigranController::class, 'index'])->name('transmigran.index');

Route::get('/transmigran/{id}', [TransmigranController::class, 'detail'])
    ->where('id', '[0-9]+')->name('transmigran.detail');

Route::post('/transmigran', [TransmigranController::class, 'simpan'])->name('transmigran.simpan');

Route::put('/transmigran/{id}', [TransmigranController::class, 'perbarui'])
    ->where('id', '[0-9]+')->name('transmigran.perbarui');

/*
 * Pergantian kepala keluarga -- tindakan TERSENDIRI, bukan bagian dari perbarui
 * (rules.md 6.5b). Satu transaksi: rekam `riwayat_kepala_keluarga` (kedua sisi
 * identitas), sunting baris `transmigran` dengan data pengganti, lalu hapus
 * baris `anggota_keluarga` pengganti.
 */
Route::post('/transmigran/{id}/ganti-kepala-keluarga', [TransmigranController::class, 'gantiKepalaKeluarga'])
    ->where('id', '[0-9]+')->name('transmigran.ganti-kepala-keluarga');

/*
 * Mencatat peristiwa pada satu anggota keluarga SELAIN kepala keluarga
 * (rules.md 6.9c): meninggal atau pindah. Barisnya tidak dihapus, hanya
 * ditandai. Kepala keluarga TIDAK lewat sini -- peristiwanya lewat suksesi.
 */
Route::post('/transmigran/{id}/anggota/{anggota}/catat-peristiwa', [TransmigranController::class, 'catatPeristiwa'])
    ->where(['id' => '[0-9]+', 'anggota' => '[0-9]+'])->name('transmigran.anggota.catat-peristiwa');

Route::delete('/transmigran/{id}', [TransmigranController::class, 'hapus'])
    ->where('id', '[0-9]+')->name('transmigran.hapus');

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
// Task 5.3 + 5.4: seluruhnya `RumahController` + Eloquent. Pergantian penghuni
// menutup baris `riwayat_penghunian` terbuka lalu membuka yang baru tanpa
// menimpa data lama (rules.md 6a.9).
Route::get('/rumah', [RumahController::class, 'index'])->name('rumah.index');

Route::get('/rumah/{id}', [RumahController::class, 'detail'])
    ->where('id', '[0-9]+')->name('rumah.detail');

Route::post('/rumah', [RumahController::class, 'simpan'])->name('rumah.simpan');

Route::put('/rumah/{id}', [RumahController::class, 'perbarui'])
    ->where('id', '[0-9]+')->name('rumah.perbarui');

Route::delete('/rumah/{id}', [RumahController::class, 'hapus'])
    ->where('id', '[0-9]+')->name('rumah.hapus');

/*
|--------------------------------------------------------------------------
| Modul Lahan
|--------------------------------------------------------------------------
|
| Dokumen HPL dan SHM dikelola lewat rute tersendiri, karena satu lahan dapat
| memiliki lebih dari satu dokumen (data-dictionary.md bagian 7.2).
|
*/
// Task 6.1-6.3: seluruhnya `LahanController` + Eloquent. SHM + status_sertifikat
// ditulis ke sisi KELUARGA (`transmigran`); `luas_usaha` diturunkan kering+basah.
Route::get('/lahan', [LahanController::class, 'index'])->name('lahan.index');

Route::get('/lahan/{id}', [LahanController::class, 'detail'])
    ->where('id', '[0-9]+')->name('lahan.detail');

Route::post('/lahan', [LahanController::class, 'simpan'])->name('lahan.simpan');

Route::put('/lahan/{id}', [LahanController::class, 'perbarui'])
    ->where('id', '[0-9]+')->name('lahan.perbarui');

Route::delete('/lahan/{id}', [LahanController::class, 'hapus'])
    ->where('id', '[0-9]+')->name('lahan.hapus');

// Rute lama unggah dokumen per-bidang: tak ada UI yang menunjuknya sejak SHM
// pindah ke form lahan (Putaran 15). Dibiarkan menjawab agar tautan tersebar
// tak mati; dicabut penuh saat pembersihan rute mati.
Route::post('/lahan/{id}/dokumen', fn (int $id) => redirect()
    ->route('lahan.detail', ['id' => $id, 'tab' => 'dokumen'])
    ->with('sukses', 'Dokumen lahan tersimpan.'))
    ->where('id', '[0-9]+')->name('lahan.dokumen.simpan');

/*
|--------------------------------------------------------------------------
| Modul Hasil Panen
|--------------------------------------------------------------------------
|
| Rute rekap diletakkan SEBELUM rute berparameter, agar /panen/rekap tidak
| tertangkap sebagai id (Laravel mencocokkan rute menurut urutan pendaftaran).
|
*/
Route::get('/panen', [HasilPanenController::class, 'index'])->name('panen.index');

/*
 * Penyusun rekap panen, dipakai DUA rute: tautan tetap per tab dan alamat lama
 * ber-`?kelompok=`. Dipusatkan pada satu closure agar keduanya tidak menyimpang
 * diam-diam; sebelum 2026-08-27 keduanya merender view yang menyusun datanya
 * sendiri, sehingga tidak ada satu tempat pun yang dapat disebut sumbernya.
 */
$susunRekapPanen = function (?string $kelompokRute = null) {
    // Dasar pengelompokan datang dari dua arah: segmen rute yang menjadi
    // tautan tetap, dan kueri `?kelompok=` milik tautan lama. Yang pertama
    // membuat ketiga tab tetap dapat dibuka pada build statis.
    $kelompok = $kelompokRute ?? request('kelompok', 'sp');

    /*
     * PERIODE SELALU TERIKAT, tidak pernah kumulatif sejak awal waktu.
     * Bawaannya TAHUN BERJALAN sesuai keputusan pemilik proyek 2026-08-24.
     *
     * MEMAKAI TAHUN PANEN, bukan tahun tanam. Ini rekap PANEN, sehingga yang
     * menggolongkan adalah peristiwa panennya.
     */
    $daftarTahun = RekapPanen::tahunTercatat();
    $tahunPanen = (int) request('tahun', date('Y'));

    /*
     * PENYARING SILANG. Tab menentukan baris APA, penyaring menentukan baris
     * MANA. Penyaring yang dirender berbeda tiap tab, sebab menyaring SP pada
     * tab Per SP hanya menyisakan satu baris yang sudah terlihat sejak awal.
     */
    $filterSp = $kelompok !== 'sp' ? request('sp') : null;
    $filterKomoditas = $kelompok !== 'komoditas' ? request('komoditas') : null;

    /*
     * Opsi dihitung dari PENANAMAN pada tahun terpilih, bukan dari data master.
     * Master memuat enam SP dan lima komoditas, sedangkan tahun 2025 hanya
     * memiliki satu dari masing-masing; menawarkan sisanya berarti menyuguhkan
     * pilihan yang DIJAMIN menghasilkan tabel kosong.
     */
    $opsiFilter = RekapPanen::opsiFilter($tahunPanen);

    /*
     * Nilai yang tidak lagi tersedia DILEPAS, bukan dibiarkan menghasilkan
     * tabel kosong tanpa penjelasan. Keadaannya nyata: petugas menyaring CABAI
     * pada 2026, lalu berpindah ke 2025 - dan cabai tidak ditanam tahun itu.
     */
    $filterDilepas = [];

    if ($filterSp !== null && $filterSp !== '' && ! in_array($filterSp, $opsiFilter['sp'], true)) {
        $filterDilepas[] = 'Satuan Permukiman '.$filterSp;
        $filterSp = null;
    }

    if ($filterKomoditas !== null && $filterKomoditas !== '' && ! in_array($filterKomoditas, $opsiFilter['komoditas'], true)) {
        $filterDilepas[] = 'Komoditas '.$filterKomoditas;
        $filterKomoditas = null;
    }

    // String kosong berarti "semua", bukan penyaring bernilai kosong.
    $filterSp = $filterSp !== '' ? $filterSp : null;
    $filterKomoditas = $filterKomoditas !== '' ? $filterKomoditas : null;

    $rekap = RekapPanen::rekap($kelompok, $tahunPanen, $filterSp, $filterKomoditas);

    // Dipakai judul tabel dan baris total. Angka rekap tanpa cakupannya tidak
    // dapat disalin ke laporan mana pun.
    $cakupanFilter = array_values(array_filter([$filterSp, $filterKomoditas]));

    $totalPanen = array_sum(array_column($rekap, 'hasil_panen'));
    $totalProduksi = array_sum(array_column($rekap, 'produksi_ton'));

    /*
     * KOLOM KEDUA BERBEDA TIAP TAB, ditetapkan pemilik proyek 2026-08-24.
     * Luas lahan sengaja TIDAK ditampilkan pada tab komoditas: satu poktan
     * menanam beberapa komoditas, sehingga lahannya akan terhitung berkali-kali
     * dan totalnya melampaui luas kawasan yang sebenarnya. Cacah poktan tidak
     * ditampilkan pada tab poktan, sebab nilainya selalu satu.
     *
     * Jumlah Anggota hanya pada tab Kelompok Tani: pada tab SP dan Komoditas ia
     * menjumlahkan anggota beberapa poktan sekaligus, angka yang benar secara
     * aritmetika tetapi tidak menjawab pertanyaan apa pun.
     */
    $tampilkanCacahPoktan = $kelompok !== 'poktan';
    $tampilkanJumlahAnggota = $kelompok === 'poktan';
    $tampilkanLuasLahan = $kelompok !== 'komoditas';
    $tampilkanVolumeBenih = $kelompok === 'komoditas';

    return view('pages.panen.rekap', [
        'title' => 'Rekap Hasil Panen',
        'kelompok' => $kelompok,
        'daftarTahun' => $daftarTahun,
        'tahunPanen' => $tahunPanen,
        'filterSp' => $filterSp,
        'filterKomoditas' => $filterKomoditas,
        'opsiFilter' => $opsiFilter,
        'filterDilepas' => $filterDilepas,
        'rekap' => $rekap,
        'cakupanFilter' => $cakupanFilter,
        'adaFilter' => $cakupanFilter !== [],

        'totalPoktan' => array_sum(array_column($rekap, 'jumlah_poktan')),
        'totalAnggota' => array_sum(array_column($rekap, 'jumlah_anggota')),
        'totalLuas' => array_sum(array_column($rekap, 'luas_lahan')),
        'totalBenih' => array_sum(array_column($rekap, 'volume_benih')),
        'totalTanam' => array_sum(array_column($rekap, 'realisasi_tanam')),
        'totalPanen' => $totalPanen,
        'totalPuso' => array_sum(array_column($rekap, 'puso')),
        'totalBelum' => array_sum(array_column($rekap, 'belum_dipanen')),
        'totalProduksi' => $totalProduksi,
        'totalNilai' => array_sum(array_column($rekap, 'nilai_jual')),

        /*
         * Produktivitas total pun TERTIMBANG, bukan rata-rata kolomnya. Contoh
         * nyata pada 2026: produksi 10,151 ton dibagi luas dipanen 3,45 ha
         * menghasilkan 2,942 ton/ha, sedangkan rata-rata naif ketiga baris
         * justru 1,452 - tertarik turun oleh baris yang gagal total dan
         * berproduktivitas nol, padahal luas panennya nol pula sehingga tidak
         * seharusnya ikut menimbang.
         */
        'produktivitasTotal' => $totalPanen > 0 ? $totalProduksi / $totalPanen : 0.0,

        /*
         * Daftar ini WAJIB sejalan dengan batasan `where` pada rute
         * `panen.rekap.kelompok` dan larik pada DaftarTautanStatis. Ketiganya
         * mengunci hal yang sama, dan mengubah salah satunya saja membuat
         * halaman terbit membalas 404 tanpa penjaga apa pun (notes.md 1e.5).
         */
        'labelKelompok' => [
            'sp' => 'Satuan Permukiman',
            'komoditas' => 'Komoditas',
            'poktan' => 'Kelompok Tani',
        ],

        'tampilkanCacahPoktan' => $tampilkanCacahPoktan,
        'tampilkanJumlahAnggota' => $tampilkanJumlahAnggota,
        'tampilkanLuasLahan' => $tampilkanLuasLahan,
        'tampilkanVolumeBenih' => $tampilkanVolumeBenih,

        // Cacah kolom, dipakai memeriksa kesejajaran baris total. Tetap: nama,
        // 4 kolom luas, produktivitas, produksi, nilai jual.
        'cacahKolom' => 8 + (int) $tampilkanCacahPoktan + (int) $tampilkanJumlahAnggota
            + (int) $tampilkanLuasLahan + (int) $tampilkanVolumeBenih,
    ]);
};

Route::get('/panen/rekap', fn () => $susunRekapPanen())->name('panen.rekap');

// Tautan tetap per dasar pengelompokan. Membuat ketiga tab dapat ditandai,
// dibagikan, dan ikut tergilas pada build statis GitHub Pages yang tidak dapat
// melayani kueri `?kelompok=`. Lihat agents/notes.md bagian 1b.
// Wajib berada SEBELUM /panen/{id} agar tidak tertangkap sebagai id.
//
// Kelompok `musim` dicabut 2026-08-22 bersama fitur musim tanam.
Route::get('/panen/rekap/{kelompok}', fn (string $kelompok) => $susunRekapPanen($kelompok))
    ->where('kelompok', 'sp|komoditas|poktan')->name('panen.rekap.kelompok');

Route::get('/panen/{id}', [HasilPanenController::class, 'detail'])
    ->where('id', '[0-9]+')->name('panen.detail');

Route::post('/panen', [HasilPanenController::class, 'simpan'])->name('panen.simpan');

Route::put('/panen/{id}', [HasilPanenController::class, 'perbarui'])
    ->where('id', '[0-9]+')->name('panen.perbarui');

Route::delete('/panen/{id}', [HasilPanenController::class, 'hapus'])
    ->where('id', '[0-9]+')->name('panen.hapus');

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
    $semua = DummyData::pengaduan();

    $cari = trim((string) request('cari', ''));
    $filterSp = request('sp');
    $filterStatus = request('status');
    $filterKategori = request('kategori');
    $filterPrioritas = request('prioritas');

    /*
     * Filter bidang paling berguna bagi Admin dan Dinas Transmigrasi, sebab
     * keduanya bercakupan Semua sehingga daftarnya memuat laporan kedua dinas
     * sekaligus (rules.md 5.0b). Nilai khusus 'belum' menyaring laporan yang
     * bidangnya belum ditetapkan, dan itulah antrean kerja penyaringan awal.
     */
    $filterBidang = request('bidang');

    $baris = array_values(array_filter($semua, function ($p) use ($cari, $filterSp, $filterStatus, $filterKategori, $filterPrioritas, $filterBidang) {
        if ($cari !== '') {
            $cocok = str_contains(mb_strtolower($p['judul']), mb_strtolower($cari))
                || str_contains(mb_strtolower($p['nomor_pengaduan']), mb_strtolower($cari))
                || str_contains(mb_strtolower($p['nama_pelapor']), mb_strtolower($cari));

            if (! $cocok) {
                return false;
            }
        }

        if ($filterSp && (string) $p['satuan_permukiman_id'] !== (string) $filterSp) {
            return false;
        }

        if ($filterStatus && $p['status'] !== $filterStatus) {
            return false;
        }

        if ($filterKategori && $p['kategori'] !== $filterKategori) {
            return false;
        }

        if ($filterPrioritas && $p['prioritas'] !== $filterPrioritas) {
            return false;
        }

        if ($filterBidang === 'belum' && ! empty($p['bidang'])) {
            return false;
        }

        if ($filterBidang && $filterBidang !== 'belum' && ($p['bidang'] ?? null) !== $filterBidang) {
            return false;
        }

        return true;
    }));

    // Yang belum selesai didahulukan, lalu diurutkan menurut kemendesakan.
    $urutanPrioritas = ['Mendesak' => 0, 'Tinggi' => 1, 'Sedang' => 2, 'Rendah' => 3];
    usort($baris, function ($a, $b) use ($urutanPrioritas) {
        $selesaiA = $a['status'] === StatusPengaduan::Selesai->value ? 1 : 0;
        $selesaiB = $b['status'] === StatusPengaduan::Selesai->value ? 1 : 0;

        if ($selesaiA !== $selesaiB) {
            return $selesaiA <=> $selesaiB;
        }

        return $urutanPrioritas[$a['prioritas']] <=> $urutanPrioritas[$b['prioritas']];
    });

    return view('pages.pengaduan.index', [
        'title' => 'Pengaduan',
        'semua' => $semua,
        'baris' => $baris,
        'cari' => $cari,
        'filterSp' => $filterSp,
        'filterStatus' => $filterStatus,
        'filterKategori' => $filterKategori,
        'filterPrioritas' => $filterPrioritas,
        'filterBidang' => $filterBidang,
        'adaFilter' => $cari !== '' || $filterSp || $filterStatus || $filterKategori || $filterPrioritas || $filterBidang,

        // Antrean penyaringan awal, ditampilkan agar laporan tanpa bidang tidak
        // menumpuk diam-diam menunggu dinas yang tidak pernah tahu.
        'belumBerbidang' => count(array_filter($semua, fn ($p) => empty($p['bidang']))),

        'belumSelesai' => count(array_filter($semua, fn ($p) => $p['status'] !== StatusPengaduan::Selesai->value)),
        'menungguDiterima' => count(array_filter($semua, fn ($p) => $p['status'] === StatusPengaduan::MenungguDiterima->value)),
        'mendesak' => count(array_filter($semua, fn ($p) => $p['prioritas'] === PrioritasPengaduan::Mendesak->value
            && $p['status'] !== StatusPengaduan::Selesai->value)),

        'daftarSp' => DummyData::satuanPermukiman(),
        'opsiFilterBidang' => DummyData::opsiFilterReferensi(JenisReferensi::BidangPengaduan),
        'opsiFilterKategori' => DummyData::opsiFilterReferensi(JenisReferensi::KategoriPengaduan),
        'opsiFilterPrioritas' => DummyData::opsiFilterReferensi(JenisReferensi::PrioritasPengaduan),
    ]);
})->name('pengaduan.index');

/*
 * Rekap pengaduan, dipakai dua rute seperti rekap panen: pemilih ber-`?kelompok=`
 * dan tautan tetap per segmen.
 */
$susunRekapPengaduan = function (?string $kelompokRute = null) {
    $kelompok = $kelompokRute ?? request('kelompok', 'kategori');

    return view('pages.pengaduan.rekap', [
        'title' => 'Rekap Pengaduan',
        'kelompok' => $kelompok,
        'rekap' => DummyData::rekapPengaduan($kelompok),
    ]);
};

Route::get('/pengaduan/rekap', fn () => $susunRekapPengaduan())->name('pengaduan.rekap');

/*
 * Tautan tetap pemilih kelompok rekap.
 *
 * Pemilihnya semula hanya memakai '?kelompok=', dan kueri tidak dapat
 * dilayani berkas statis di GitHub Pages. Polanya menyalin '/panen/rekap/
 * {kelompok}' yang sudah lebih dulu memakai cara ini.
 *
 * Daftar nilai pada `where` WAJIB dijaga sejalan dengan $labelKelompok pada
 * viewnya; keduanya menyatakan hal yang sama di dua tempat.
 */
Route::get('/pengaduan/rekap/{kelompok}', fn (string $kelompok) => $susunRekapPengaduan($kelompok))
    ->where('kelompok', 'kategori|status|sp|prioritas|bidang')->name('pengaduan.rekap.kelompok');

Route::get('/pengaduan/{id}', function (int $id) {
    $data = collect(DummyData::pengaduan())->firstWhere('id_pengaduan', $id);

    abort_if($data === null, 404);

    return view('pages.pengaduan.detail', [
        'title' => $data['nomor_pengaduan'],
        'data' => $data,
        'riwayat' => DummyData::penangananPengaduan($data['nomor_pengaduan']),
        'opsiBidang' => DummyData::opsiReferensi(JenisReferensi::BidangPengaduan),
    ]);
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
// Task 5.5: closure -> KependudukanController. AGREGAT masih `DummyData::rekap*`
// (berskala kawasan ~1.140 KK); kueri nyata satu paket dengan Task 9.1.
Route::get('/kependudukan/rekap', [KependudukanController::class, 'rekap'])->name('kependudukan.rekap');

/*
 * Tautan tetap pemilih kelompok rekap kependudukan.
 *
 * Ditambahkan 2026-08-25. Sebelumnya tab hanya dipilih lewat `?kelompok=`,
 * dan kueri TIDAK dilayani berkas statis di GitHub Pages - sehingga di situs
 * terbit hanya tab Tahun yang terbuka dan lima tab lain tidak dapat dicapai
 * sama sekali.
 *
 * Cacat yang sama pernah ditemukan pada rekap panen (notes.md 1b.6a) lalu
 * diperbaiki, tetapi kependudukan terlewat. Polanya menyalin `/panen/rekap/`.
 *
 * Batasan `where` di bawah WAJIB sejalan dengan $labelKelompok pada viewnya
 * dan larik pada DaftarTautanStatis. Mengubah salah satunya saja membuat
 * halaman terbit membalas 404 tanpa penjaga apa pun.
 */
Route::get('/kependudukan/rekap/{kelompok}', [KependudukanController::class, 'rekap'])
    ->where('kelompok', 'tahun|sp|status|pekerjaan|asal|pendidikan')->name('kependudukan.rekap.kelompok');

// Task 6.4 + 6.5: PoktanController + AnggotaPoktanController + Eloquent.
// Ketua 3 jalur; jumlah_anggota & luas lahan kelompok diturunkan. Tab alsintan
// pada rincian poktan sudah Eloquent (Task 6.6); saprotan menyusul Task 6.7.
Route::get('/poktan', [PoktanController::class, 'index'])->name('poktan.index');

Route::get('/poktan/{id}', [PoktanController::class, 'detail'])
    ->where('id', '[0-9]+')->name('poktan.detail');

Route::post('/poktan', [PoktanController::class, 'simpan'])->name('poktan.simpan');

Route::put('/poktan/{id}', [PoktanController::class, 'perbarui'])
    ->where('id', '[0-9]+')->name('poktan.perbarui');

Route::delete('/poktan/{id}', [PoktanController::class, 'hapus'])
    ->where('id', '[0-9]+')->name('poktan.hapus');

// Anggota poktan ditandai Sudah Keluar, tidak pernah dihapus (rules.md 5.1
// catatan 7) -- tak ada rute hapus. `perbarui` = satu-satunya jalur ubah
// status keaktifan + tanggal keluar; pindah kelompok = tandai keluar lalu
// baris baru di kelompok tujuan.
Route::post('/anggota-poktan', [AnggotaPoktanController::class, 'simpan'])->name('anggota-poktan.simpan');

Route::put('/anggota-poktan/{id}', [AnggotaPoktanController::class, 'perbarui'])
    ->where('id', '[0-9]+')->name('anggota-poktan.perbarui');

/*
 * Alsintan (Task 6.6): pola INDUK + DISTRIBUSI. Satu pengadaan dibagikan ke
 * beberapa poktan lintas SP; kondisi diamati per baris distribusi. Sigma
 * distribusi <= jumlah total ditegakkan controller.
 */
Route::get('/alsintan', [AlsintanController::class, 'index'])->name('alsintan.index');

Route::get('/alsintan/{id}', [AlsintanController::class, 'detail'])
    ->where('id', '[0-9]+')->name('alsintan.detail');

Route::post('/alsintan', [AlsintanController::class, 'simpan'])->name('alsintan.simpan');

Route::put('/alsintan/{id}', [AlsintanController::class, 'perbarui'])
    ->where('id', '[0-9]+')->name('alsintan.perbarui');

Route::delete('/alsintan/{id}', [AlsintanController::class, 'hapus'])
    ->where('id', '[0-9]+')->name('alsintan.hapus');

/*
 * Memperbarui kondisi satu baris distribusi alsintan (Putaran 7). Kondisi
 * melekat pada distribusi, bukan pengadaan, sebab diamati per unit di
 * lapangan dan berubah setelah barang dibagikan.
 */
Route::post('/alsintan/{id}/distribusi/{dist}/kondisi', [AlsintanController::class, 'distribusiKondisi'])
    ->where(['id' => '[0-9]+', 'dist' => '[0-9]+'])->name('alsintan.distribusi.kondisi');

Route::get('/saprotan', [SaprotanController::class, 'index'])->name('saprotan.index');

Route::get('/saprotan/{id}', [SaprotanController::class, 'detail'])
    ->where('id', '[0-9]+')->name('saprotan.detail');

Route::post('/saprotan', [SaprotanController::class, 'simpan'])->name('saprotan.simpan');

Route::put('/saprotan/{id}', [SaprotanController::class, 'perbarui'])
    ->where('id', '[0-9]+')->name('saprotan.perbarui');

/*
 * Data komoditas (Task 7.1: peralihan ke Eloquent). `tipe` = TEKS referensi;
 * `slug` otomatis. Satuan baku mengunci satuan pencatatan panen berikutnya.
 */
Route::get('/komoditas', [KomoditasController::class, 'index'])->name('komoditas.index');

Route::get('/komoditas/{id}', [KomoditasController::class, 'detail'])
    ->where('id', '[0-9]+')->name('komoditas.detail');

Route::post('/komoditas', [KomoditasController::class, 'simpan'])->name('komoditas.simpan');

Route::put('/komoditas/{id}', [KomoditasController::class, 'perbarui'])
    ->where('id', '[0-9]+')->name('komoditas.perbarui');

Route::get('/penanaman', [PenanamanController::class, 'index'])->name('penanaman');

Route::get('/penanaman/{id}', [PenanamanController::class, 'detail'])
    ->where('id', '[0-9]+')->name('penanaman.detail');

Route::post('/penanaman', [PenanamanController::class, 'simpan'])->name('penanaman.simpan');

Route::get('/sp/infrastruktur', [InfrastrukturController::class, 'index'])->name('infrastruktur.index');

// Redirect 301 untuk kompatibilitas alamat lama /infrastruktur
Route::get('/infrastruktur', function () {
    return redirect()->route('infrastruktur.index', request()->query(), 301);
});

Route::get('/sp/infrastruktur/{id}', [InfrastrukturController::class, 'detail'])
    ->where('id', '[0-9]+')->name('infrastruktur.detail');

// Redirect 301 untuk kompatibilitas alamat lama /infrastruktur/{id}
Route::get('/infrastruktur/{id}', function (int $id) {
    return redirect()->route('infrastruktur.detail', ['id' => $id], 301);
})->where('id', '[0-9]+');

Route::post('/sp/infrastruktur', [InfrastrukturController::class, 'simpan'])->name('infrastruktur.simpan');

Route::put('/sp/infrastruktur/{id}', [InfrastrukturController::class, 'perbarui'])
    ->where('id', '[0-9]+')->name('infrastruktur.perbarui');

// Manajemen pengguna oleh Admin (Task 3.5, `rules.md` 14b). `index()` masih
// baca DummyData (peralihan tampilan -> Tahap 4); tulisan menyentuh tabel
// `user` nyata -- diuji di tests/Database/PengaturanPenggunaTest.
Route::get('/pengguna', [PengaturanPenggunaController::class, 'index'])->name('pengguna.index');
Route::post('/pengguna', [PengaturanPenggunaController::class, 'simpan'])->name('pengguna.simpan');
Route::put('/pengguna/{id}', [PengaturanPenggunaController::class, 'perbarui'])
    ->where('id', '[0-9]+')->name('pengguna.perbarui');
Route::post('/pengguna/{id}/setel-sandi', [PengaturanPenggunaController::class, 'setelSandi'])
    ->where('id', '[0-9]+')->name('pengguna.setel-sandi');
Route::post('/pengguna/{id}/nonaktifkan', [PengaturanPenggunaController::class, 'nonaktifkan'])
    ->where('id', '[0-9]+')->name('pengguna.nonaktifkan');
Route::post('/pengguna/{id}/aktifkan', [PengaturanPenggunaController::class, 'aktifkan'])
    ->where('id', '[0-9]+')->name('pengguna.aktifkan');

// Pengelolaan role & kewenangan (Task 3.3). `index` masih baca DummyData
// (tampilan -> Eloquent = Tahap 4); tulis ke tabel `role`/`role_permission`.
Route::get('/pengaturan/role', [PengaturanRoleController::class, 'index'])->name('pengaturan.role');
Route::post('/pengaturan/role', [PengaturanRoleController::class, 'simpan'])->name('role.simpan');
Route::put('/pengaturan/role/{id}', [PengaturanRoleController::class, 'perbarui'])
    ->where('id', '[0-9]+')->name('role.perbarui');
Route::delete('/pengaturan/role/{id}', [PengaturanRoleController::class, 'hapus'])
    ->where('id', '[0-9]+')->name('role.hapus');

// Audit Log (Task 3.12). HANYA-BACA -- tak ada rute tulis. `izin:audit_log,
// lihat` terlampir otomatis lewat nama rute (bootstrap/app.php).
Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log');

/*
|--------------------------------------------------------------------------
| Laporan
|--------------------------------------------------------------------------
|
| Ditambahkan 2026-08-28 (rules.md 12 poin 6, membalik keputusan 2026-08-17).
| Laporan adalah dokumen bernama berformat tetap yang dicetak dan diserahkan
| ke dinas, bukan potret tabel yang sedang tersaring. Menu "Laporan" jadi
| rumahnya; tombol ekspor yang dahulu menempel di tiap halaman daftar dicabut.
|
| Isinya disajikan sebagai "kertas" berbingkai (Putaran 3 D2). Tiap laporan
| punya rute dokumen polos `/laporan/{slug}/dokumen` yang dibuka di tab baru
| untuk tampilan penuh; kerangka dan tabelnya sama persis dengan halaman
| berbingkai (metadata dari LaporanData::meta(), isi dari pages/laporan/isi).
|
| Penyaring per laporan (SP, periode, dimensi khas) menyusul di D3, dikerjakan
| Alpine di sisi peramban -- query string tidak dilayani GitHub Pages
| (notes.md 1b.5).
|
| Nama, izin, dan urutan laporan hanya ditulis di App\Support\LaporanData::meta()
| (Putaran 4). Butir "Semua Laporan" + halaman /laporan dicabut: submenu sudah
| memuat ketujuh laporan langsung.
*/
$judulLaporan = array_map(fn (array $m): string => $m['judul'], LaporanData::meta());

// Data tiap laporan disusun di App\Support\LaporanData, satu metode per
// laporan (nama slug di-camelCase), agar view tidak memanggil DummyData
// langsung (penjaga Ide C). Dikirim sebagai satu larik `isiLaporan` supaya
// halaman berbingkai dan rute dokumen dapat meneruskannya utuh ke partial
// isi lewat @include -- slot komponen tidak mewarisi variabel view.
$dataLaporan = function (string $slug): array {
    $metode = Str::camel($slug);

    return method_exists(LaporanData::class, $metode) ? LaporanData::$metode() : [];
};

foreach ($judulLaporan as $slug => $judul) {
    Route::get('/laporan/'.$slug, function () use ($slug, $judul, $dataLaporan) {
        return view('pages.laporan.'.$slug, [
            'title' => $judul,
            'slug' => $slug,
            'isiLaporan' => $dataLaporan($slug),
        ]);
    })->name('laporan.'.$slug);
}

// Tampilan dokumen polos (tanpa sidebar/header), dibuka di tab baru. Satu
// rute berparameter, dibatasi `where` pada slug yang sah -- pola yang sama
// dengan /panen/rekap/{kelompok}.
Route::get('/laporan/{slug}/dokumen', function (string $slug) use ($judulLaporan, $dataLaporan) {
    return view('pages.laporan.dokumen', [
        'title' => $judulLaporan[$slug],
        'slug' => $slug,
        'isiLaporan' => $dataLaporan($slug),
    ]);
})->where('slug', implode('|', array_keys($judulLaporan)))->name('laporan.dokumen');

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

// `sp.perbarui` TIDAK didaftarkan di sini. Ia sudah ada pada blok Wilayah dan SP
// di bagian atas berkas, dan deklarasi kedua di tempat ini menimpanya diam-diam:
// Laravel memakai yang terakhir, sehingga menyunting SP dari halaman rincian
// melempar petugas ke daftar SP beserta hilangnya posisi tab. Yang dipertahankan
// adalah versi ber-`back()`, sebab ia memenuhi rules.md 4c poin 5 dan tetap benar
// ketika penyuntingan dilakukan dari halaman daftar. Dicabut 2026-09-02.
Route::delete('/sp/{id}', [SpController::class, 'hapus'])
    ->where('id', '[0-9]+')->name('sp.hapus');

Route::put('/sp/inventaris/{id}', [InventarisSpController::class, 'perbarui'])
    ->where('id', '[0-9]+')->name('inventaris.perbarui');

Route::delete('/sp/inventaris/{id}', [InventarisSpController::class, 'hapus'])
    ->where('id', '[0-9]+')->name('inventaris.hapus');

Route::put('/sp/fasilitas/{id}', [FasilitasSpController::class, 'perbarui'])
    ->where('id', '[0-9]+')->name('fasilitas.perbarui');

Route::delete('/sp/fasilitas/{id}', [FasilitasSpController::class, 'hapus'])
    ->where('id', '[0-9]+')->name('fasilitas.hapus');

Route::put('/kawasan/{id}', [KawasanController::class, 'perbarui'])
    ->where('id', '[0-9]+')->name('kawasan.perbarui');

Route::delete('/kawasan/{id}', [KawasanController::class, 'hapus'])
    ->where('id', '[0-9]+')->name('kawasan.hapus');

/*
 * Tingkat dibawa DI ALAMAT, bukan hanya di body (Task 4.1).
 *
 * Kunci utama keempat tabel wilayah berdiri sendiri-sendiri, sehingga id 1 sah
 * sebagai kecamatan (Laen Manen) MAUPUN desa (Kapitan Meo). Alamat lama
 * `/wilayah/{id}` karena itu tidak pernah cukup menunjuk satu baris, dan
 * penghapusan yang menebak tingkatnya akan membuang baris yang keliru tanpa
 * memerahkan apa pun.
 */
Route::put('/wilayah/{tingkat}/{id}', [WilayahController::class, 'perbarui'])
    ->where('tingkat', 'provinsi|kabupaten|kecamatan|desa')
    ->where('id', '[0-9]+')->name('wilayah.perbarui');

Route::delete('/wilayah/{tingkat}/{id}', [WilayahController::class, 'hapus'])
    ->where('tingkat', 'provinsi|kabupaten|kecamatan|desa')
    ->where('id', '[0-9]+')->name('wilayah.hapus');

Route::put('/master/satuan/{id}', [MasterSatuanController::class, 'perbarui'])
    ->where('id', '[0-9]+')->name('satuan.perbarui');

Route::delete('/master/satuan/{id}', [MasterSatuanController::class, 'hapus'])
    ->where('id', '[0-9]+')->name('satuan.hapus');

Route::put('/penanaman/{id}', [PenanamanController::class, 'perbarui'])
    ->where('id', '[0-9]+')->name('penanaman.perbarui');

Route::delete('/penanaman/{id}', [PenanamanController::class, 'hapus'])
    ->where('id', '[0-9]+')->name('penanaman.hapus');

Route::delete('/saprotan/{id}', [SaprotanController::class, 'hapus'])
    ->where('id', '[0-9]+')->name('saprotan.hapus');

Route::delete('/komoditas/{id}', [KomoditasController::class, 'hapus'])
    ->where('id', '[0-9]+')->name('komoditas.hapus');

Route::delete('/sp/infrastruktur/{id}', [InfrastrukturController::class, 'hapus'])
    ->where('id', '[0-9]+')->name('infrastruktur.hapus');

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
    return back()->with('info', 'Template impor '.str_replace('-', ' ', $entitas)
        .' akan tersedia setelah backend selesai.');
})->where('entitas', '[a-z\-]+')->name('template-impor');
