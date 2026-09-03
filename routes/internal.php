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

use App\Enums\AsalWakilPoktan;
use App\Enums\JenisReferensi;
use App\Enums\PrioritasPengaduan;
use App\Enums\StatusKondisiSp;
use App\Enums\StatusPanen;
use App\Enums\StatusPengaduan;
use App\Http\Controllers\DokumenController;
use App\Http\Controllers\PengaturanPenggunaController;
use App\Http\Controllers\PengaturanRoleController;
use App\Support\DummyData;
use App\Support\LaporanData;
use App\Support\PenilaianKondisiSp;
use Illuminate\Support\Carbon;
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

Route::put('/sp/{sp}', function (int $sp) {
    // Tahap 4: simpan perubahan data SP
    return back()->with('sukses', 'Perubahan data satuan permukiman tersimpan.');
})->where('sp', '[0-9]+')->name('sp.perbarui');

Route::get('/profil', function () {
    $pengguna = DummyData::penggunaSaatIni();

    return view('pages.profil.index', [
        'title' => 'Profil Saya',
        'pengguna' => $pengguna,
        'inisialPengguna' => DummyData::inisial($pengguna['nama']),
    ]);
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
Route::get('/wilayah', function () {
    $wilayah = DummyData::wilayah();

    /*
     * Keempat tingkat disatukan menjadi SATU daftar rata (2026-09-02),
     * menggantikan empat tab yang masing-masing merender seluruh barisnya.
     *
     * Dua alasan. Pertama, sejak provinsi dan kabupaten dibaca dari data
     * referensi nasional, tab Kabupaten memuat 514 baris tanpa pencarian
     * maupun paginasi. Kedua, mencari satu nama menuntut petugas menebak
     * lebih dulu ia berada di tab mana, padahal yang ia tahu hanya namanya.
     *
     * Tingkat berubah dari tab menjadi KOLOM sekaligus penyaring, sehingga
     * keempatnya tetap dapat dilihat terpisah maupun sekaligus.
     */
    $induk = [
        'provinsi' => fn (array $b) => null,
        'kabupaten' => fn (array $b) => $b['provinsi'] ?? null,
        'kecamatan' => fn (array $b) => $b['kabupaten'] ?? null,
        'desa' => fn (array $b) => $b['kecamatan'] ?? null,
    ];

    $kunciId = [
        'provinsi' => 'id_provinsi',
        'kabupaten' => 'id_kabupaten',
        'kecamatan' => 'id_kecamatan',
        'desa' => 'id_desa',
    ];

    $baris = [];
    $cacah = [];

    foreach ($kunciId as $tingkat => $kunci) {
        $cacah[$tingkat] = count($wilayah[$tingkat]);

        foreach ($wilayah[$tingkat] as $b) {
            $baris[] = [
                'id' => $b[$kunci],
                'tingkat' => $tingkat,
                'nama' => $b['nama'],
                'induk' => $induk[$tingkat]($b),
                'kode' => $b['kode'] ?? null,
                'asli' => $b,
            ];
        }
    }

    $filterTingkat = request()->query('tingkat');
    $cari = trim((string) request()->query('cari', ''));

    if ($filterTingkat !== null && $filterTingkat !== '' && isset($kunciId[$filterTingkat])) {
        $baris = array_values(array_filter($baris, fn ($b) => $b['tingkat'] === $filterTingkat));
    } else {
        $filterTingkat = '';
    }

    // Dicocokkan pada nama MAUPUN induknya: petugas kerap mengingat
    // kabupatennya ketika nama kecamatannya sendiri sudah kabur.
    if ($cari !== '') {
        $baris = array_values(array_filter(
            $baris,
            fn ($b) => str_contains(mb_strtolower($b['nama']), mb_strtolower($cari))
                || str_contains(mb_strtolower((string) $b['induk']), mb_strtolower($cari))
                || str_contains(mb_strtolower((string) $b['kode']), mb_strtolower($cari))
        ));
    }

    // Dipotong menurut halaman, sebab daftarnya kini memuat ratusan baris dan
    // merender seluruhnya membuat halaman berat tanpa ada yang membacanya.
    // Jumlah SEBELUM pemotongan tetap dibawa agar paginasi dan keterangan
    // "menampilkan sekian dari sekian" menyebut angka yang benar.
    $jumlah = count($baris);
    $perHalaman = (int) request()->query('per_halaman', 25);

    if (! in_array($perHalaman, [10, 25, 50, 100], true)) {
        $perHalaman = 25;
    }

    $halaman = max(1, (int) request()->query('page', 1));
    $barisHalaman = array_slice($baris, ($halaman - 1) * $perHalaman, $perHalaman);

    return view('pages.master.wilayah', [
        'title' => 'Data Master Wilayah',
        'wilayah' => $wilayah,
        'baris' => $barisHalaman,
        'jumlahBaris' => $jumlah,
        'perHalaman' => $perHalaman,
        'cacahTingkat' => $cacah,
        'filterTingkat' => $filterTingkat,
        'cari' => $cari,
        'adaFilter' => $filterTingkat !== '' || $cari !== '',
    ]);
})->name('wilayah');

Route::get('/master/satuan', function () {
    return view('pages.master.satuan', [
        'title' => 'Data Master Satuan',
        'satuan' => DummyData::satuan(),
    ]);
})->name('master.satuan');

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
Route::get('/master/referensi', function () {
    // Alamat lama `?tab={jenis}` dialihkan, bukan dibiarkan mati. Bentuk itu
    // sempat dipakai form untuk menentukan jenis awal, dan tautan yang sudah
    // tersimpan siapa pun tidak boleh mendarat di halaman yang salah tanpa
    // penjelasan.
    $tabLama = JenisReferensi::tryFrom((string) request('tab'));

    if ($tabLama !== null) {
        return redirect()->route('referensi.jenis', ['jenis' => $tabLama->value], 301);
    }

    $semua = DummyData::referensi();

    // Dihitung sekali, dipakai seluruh kartu.
    $jumlah = [];
    $nonaktif = [];

    foreach ($semua as $b) {
        $jumlah[$b['jenis']] = ($jumlah[$b['jenis']] ?? 0) + 1;

        if (! $b['is_aktif']) {
            $nonaktif[$b['jenis']] = ($nonaktif[$b['jenis']] ?? 0) + 1;
        }
    }

    return view('pages.master.referensi', [
        'title' => 'Data Master Daftar Pilihan',
        'semua' => $semua,
        'jumlah' => $jumlah,
        'nonaktif' => $nonaktif,
    ]);
})->name('master.referensi');

Route::get('/master/referensi/{jenis}', function (string $jenis) {
    $pilihan = JenisReferensi::tryFrom($jenis);

    // Jenis karangan membalas 404, bukan halaman kosong: daftar yang tidak ada
    // dan daftar yang kebetulan masih kosong adalah dua keadaan berbeda, dan
    // menyamakannya membuat salah ketik tampak seperti data yang belum diisi.
    abort_if($pilihan === null, 404);

    $baris = DummyData::referensi($pilihan);

    /*
     * Nama bidang penanganan tiap baris, dikumpulkan sekali.
     *
     * Bentuk lamanya memanggil `referensiNilai()` DI DALAM perulangan tabel,
     * yakni satu penelusuran seluruh data referensi untuk setiap baris yang
     * berbidang.
     */
    $nilaiBidang = [];
    foreach ($baris as $b) {
        if ($b['bidang_id'] !== null) {
            $nilaiBidang[$b['bidang_id']] ??= DummyData::referensiNilai($b['bidang_id']);
        }
    }

    return view('pages.master.detail-referensi', [
        'title' => $pilihan->label(),
        'jenis' => $pilihan,
        'baris' => $baris,
        'jumlahNonaktif' => count(array_filter($baris, fn ($b) => ! $b['is_aktif'])),
        'nilaiBidang' => $nilaiBidang,
    ]);
})->where('jenis', '[a-z_]+')->name('referensi.jenis');

Route::post('/master/referensi', function () {
    // Tahap 4: simpan baris baru pada tabel `referensi`, lalu perbarui urutan
    // pada jenis yang sama bila nomornya bertabrakan.
    //
    // Kembali ke halaman DAFTARNYA, bukan ke indeks: petugas baru saja
    // menambah satu nilai dan perlu melihat hasilnya pada daftar itu juga.
    $jenis = JenisReferensi::tryFrom((string) request('jenis'));

    return redirect()
        ->route(
            $jenis !== null ? 'referensi.jenis' : 'master.referensi',
            $jenis !== null ? ['jenis' => $jenis->value] : []
        )
        ->with('sukses', 'Pilihan baru tersimpan dan langsung tersedia pada form.');
})->name('referensi.simpan');

Route::put('/master/referensi/{id}', function (int $id) {
    // Tahap 4: penonaktifan hanya menyetel `is_aktif`, tidak menyentuh baris
    // data lain yang sudah memakai nilainya.
    $jenis = JenisReferensi::tryFrom((string) request('jenis'));

    return redirect()
        ->route(
            $jenis !== null ? 'referensi.jenis' : 'master.referensi',
            $jenis !== null ? ['jenis' => $jenis->value] : []
        )
        ->with('sukses', 'Perubahan pilihan tersimpan.');
})->where('id', '[0-9]+')->name('referensi.perbarui');

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
Route::get('/master/penilaian-kondisi', function () {
    $parameter = DummyData::parameterPenilaian();
    $dinilai = array_filter($parameter, fn ($p) => $p['is_dinilai']);

    // Dikelompokkan per sumber, sebab keduanya dibaca dari tabel berbeda dan
    // petugas mencarinya lewat modul tempat ia mendata asetnya.
    $perSumber = [];
    foreach ($parameter as $p) {
        $perSumber[$p['sumber']][] = $p;
    }

    return view('pages.master.penilaian-kondisi', [
        'title' => 'Penilaian Kondisi SP',
        'parameter' => $parameter,
        'status' => DummyData::statusKondisiSp(),
        'dinilai' => $dinilai,
        'totalBobot' => array_sum(array_column($dinilai, 'bobot')),
        'perSumber' => $perSumber,
    ]);
})->name('master.penilaian-kondisi');

Route::put('/master/penilaian-kondisi/parameter/{id}', function (int $id) {
    // Tahap 4: sunting `parameter_penilaian_sp`. Penilaian yang sudah
    // tersimpan tidak dihitung ulang, sebab `penilaian_sp.rincian` menyalin
    // bobot yang berlaku saat penilaian dibuat (rules.md 10c.6).
    return redirect()->route('master.penilaian-kondisi')
        ->with('sukses', 'Parameter penilaian tersimpan dan berlaku pada penilaian berikutnya.');
})->where('id', '[0-9]+')->name('penilaian-kondisi.parameter');

Route::put('/master/penilaian-kondisi/status/{kode}', function (string $kode) {
    abort_if(StatusKondisiSp::tryFrom($kode) === null, 404);

    // Tahap 4: ambang wajib menurun menurut urutan status. Ambang Mandiri
    // yang lebih kecil daripada Berkembang membuat Berkembang mustahil
    // dicapai, sebab pembacaannya berhenti pada ambang tertinggi yang cocok.
    return redirect()->route('master.penilaian-kondisi')
        ->with('sukses', 'Status kondisi SP tersimpan.');
})->name('penilaian-kondisi.status');

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

Route::get('/kawasan', function () {
    $daftarSp = DummyData::satuanPermukiman();
    $rekap = DummyData::rekapPerSp();

    /*
     * Berkas kawasan dipetakan per id LEBIH DULU, bukan dicari di dalam
     * perulangan kartu kawasan. Memanggil berkasMilik() di dalam @foreach
     * berarti satu penelusuran registry per kawasan (N+1), bentuk yang
     * sudah tercatat pada notes.md 1g.5.
     */
    $berkasKawasan = [];

    foreach ($daftarKawasan = DummyData::kawasan() as $k) {
        $berkasKawasan[$k['id_kawasan_transmigrasi']] = DummyData::berkasMilik(
            'kawasan_transmigrasi_berkas',
            'kawasan_transmigrasi_id',
            $k['id_kawasan_transmigrasi']
        );
    }

    return view('pages.sp.kawasan', [
        'title' => 'Kawasan Transmigrasi',
        'kawasan' => $daftarKawasan,
        'berkasKawasan' => $berkasKawasan,
        'daftarSp' => $daftarSp,
        'rekap' => $rekap,
        'totalKk' => array_sum(array_column($rekap, 'jumlah_kk')),
        'kecamatan' => array_unique(array_column($daftarSp, 'kecamatan')),
    ]);
})->name('kawasan');

// Rute beruas dua didaftarkan sebelum /sp agar tidak tertukar.
Route::get('/sp/inventaris', function () {
    $semua = DummyData::inventarisSp();

    $cari = trim((string) request('cari', ''));
    $filterSp = request('sp');
    $filterStatus = request('status_penyerahan');

    $baris = array_values(array_filter($semua, function ($b) use ($cari, $filterSp, $filterStatus) {
        if ($cari !== '' && ! str_contains(mb_strtolower($b['nama_barang']), mb_strtolower($cari))) {
            return false;
        }
        if ($filterSp && (string) $b['satuan_permukiman_id'] !== (string) $filterSp) {
            return false;
        }
        if ($filterStatus && $b['status_penyerahan'] !== $filterStatus) {
            return false;
        }

        return true;
    }));

    return view('pages.sp.inventaris', [
        'title' => 'Inventaris SP',
        'semua' => $semua,
        'baris' => $baris,
        'cari' => $cari,
        'filterSp' => $filterSp,
        'filterStatus' => $filterStatus,
        'adaFilter' => $cari !== '' || $filterSp || $filterStatus,
        'totalUnit' => array_sum(array_column($semua, 'jumlah')),
        'sudahDiserahkan' => count(array_filter($semua, fn ($b) => $b['status_penyerahan'] === 'Sudah Diserahkan')),
        'perluPerhatian' => count(array_filter($semua, fn ($b) => $b['kondisi'] !== 'Baik')),
        'daftarSp' => DummyData::satuanPermukiman(),
        'opsiFilterStatusPenyerahan' => DummyData::opsiFilterReferensi(JenisReferensi::StatusPenyerahan),
    ]);
})->name('sp.inventaris');

Route::get('/sp/fasilitas', function () {
    $semua = DummyData::fasilitasSp();

    $cari = trim((string) request('cari', ''));
    $filterSp = request('sp');
    $filterKondisi = request('kondisi');

    $baris = array_values(array_filter($semua, function ($b) use ($cari, $filterSp, $filterKondisi) {
        if ($cari !== '' && ! str_contains(mb_strtolower($b['nama_fasilitas']), mb_strtolower($cari))) {
            return false;
        }
        if ($filterSp && (string) $b['satuan_permukiman_id'] !== (string) $filterSp) {
            return false;
        }
        if ($filterKondisi && $b['kondisi'] !== $filterKondisi) {
            return false;
        }

        return true;
    }));

    return view('pages.sp.fasilitas', [
        'title' => 'Fasilitas SP',
        'semua' => $semua,
        'baris' => $baris,
        'cari' => $cari,
        'filterSp' => $filterSp,
        'filterKondisi' => $filterKondisi,
        'adaFilter' => $cari !== '' || $filterSp || $filterKondisi,
        'totalUnit' => array_sum(array_column($semua, 'jumlah')),
        'rusak' => count(array_filter($semua, fn ($b) => $b['kondisi'] !== 'Baik')),
        'daftarSp' => DummyData::satuanPermukiman(),
        'opsiFilterKondisi' => DummyData::opsiFilterReferensi(JenisReferensi::Kondisi),
    ]);
})->name('sp.fasilitas');

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
Route::get('/sp/inventaris/{id}', function (int $id) {
    $data = collect(DummyData::inventarisSp())->firstWhere('id_inventaris_sp', $id);

    abort_if($data === null, 404);

    return view('pages.sp.detail-inventaris', [
        'title' => $data['nama_barang'],
        'data' => $data,

        // Foto jamak sejak Putaran 14; satu barang kerap difoto beberapa sudut.
        'berkasFoto' => DummyData::berkasMilik('inventaris_sp_berkas', 'inventaris_sp_id', $id, 'foto'),
    ]);
})->where('id', '[0-9]+')->name('sp.inventaris.detail');

Route::get('/sp/fasilitas/{id}', function (int $id) {
    $data = collect(DummyData::fasilitasSp())->firstWhere('id_fasilitas_sp', $id);

    abort_if($data === null, 404);

    return view('pages.sp.detail-fasilitas', [
        'title' => $data['nama_fasilitas'],
        'data' => $data,
        'daftarSp' => DummyData::satuanPermukiman(),

        // Foto jamak sejak Putaran 14; satu bangunan punya beberapa sisi.
        'berkasFoto' => DummyData::berkasMilik('fasilitas_sp_berkas', 'fasilitas_sp_id', $id, 'foto'),
    ]);
})->where('id', '[0-9]+')->name('sp.fasilitas.detail');

Route::get('/sp', function () {
    $semua = DummyData::satuanPermukiman();

    $cari = trim((string) request('cari', ''));
    $filterKecamatan = request('kecamatan');

    $baris = array_values(array_filter($semua, function ($sp) use ($cari, $filterKecamatan) {
        if ($cari !== '' && ! str_contains(mb_strtolower($sp['nama']), mb_strtolower($cari))
            && ! str_contains(mb_strtolower($sp['desa']), mb_strtolower($cari))) {
            return false;
        }

        if ($filterKecamatan && $sp['kecamatan'] !== $filterKecamatan) {
            return false;
        }

        return true;
    }));

    return view('pages.sp.index', [
        'title' => 'Satuan Permukiman',
        'semua' => $semua,
        'baris' => $baris,
        'rekap' => collect(DummyData::rekapPerSp())->keyBy('satuan_permukiman_id'),

        // Status kondisi layanan dasar tiap SP, indikator ke-16.
        'kondisi' => collect(PenilaianKondisiSp::nilaiSeluruhSp())->keyBy('satuan_permukiman_id'),

        'cari' => $cari,
        'filterKecamatan' => $filterKecamatan,
        'adaFilter' => $cari !== '' || $filterKecamatan,
        'daftarKecamatan' => array_values(array_unique(array_column($semua, 'kecamatan'))),
        'totalLuas' => array_sum(array_column($semua, 'luas_lahan')),
        'totalRencana' => array_sum(array_column($semua, 'jumlah_kk_rencana')),
        'totalTerisi' => array_sum(array_column($semua, 'jumlah_kk_terisi')),
    ]);
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
    $semua = DummyData::transmigran();

    // Penyaringan dan pencarian dibaca dari query string agar hasilnya
    // bertahan setelah halaman dimuat ulang.
    $cari = trim((string) request('cari', ''));
    $filterSp = request('sp');
    $filterTinggal = request('status_tinggal');

    $baris = array_values(array_filter($semua, function ($t) use ($cari, $filterSp, $filterTinggal) {
        if ($cari !== '') {
            $cocok = str_contains(mb_strtolower($t['nama_kepala_keluarga']), mb_strtolower($cari))
                || str_contains($t['nik'], $cari)
                || str_contains($t['no_kk'], $cari);

            if (! $cocok) {
                return false;
            }
        }

        if ($filterSp && (string) $t['satuan_permukiman_id'] !== (string) $filterSp) {
            return false;
        }

        if ($filterTinggal && $t['status_tinggal'] !== $filterTinggal) {
            return false;
        }

        return true;
    }));

    return view('pages.transmigran.index', [
        'title' => 'Data Transmigran',
        'semua' => $semua,
        'baris' => $baris,
        'cari' => $cari,
        'filterSp' => $filterSp,
        'filterTinggal' => $filterTinggal,
        'adaFilter' => $cari !== '' || $filterSp || $filterTinggal,
        'daftarSp' => DummyData::satuanPermukiman(),
    ]);
})->name('transmigran.index');

Route::get('/transmigran/{id}', function (int $id) {
    $data = collect(DummyData::transmigran())->firstWhere('id_transmigran', $id);

    abort_if($data === null, 404);

    $anggotaPoktan = DummyData::anggotaPoktan();

    // Lahan dibaca lewat id, bukan mencocokkan nama: dua kepala keluarga
    // dapat bernama sama, dan pencocokan nama akan menautkan bidang milik
    // orang lain ke halaman ini tanpa ada yang menyadarinya.
    $lahan = array_values(array_filter(
        DummyData::lahan(),
        fn ($l) => $l['transmigran_id'] === $data['id_transmigran']
    ));

    return view('pages.transmigran.detail', [
        'title' => $data['nama_kepala_keluarga'],
        'data' => $data,

        // Dibaca lewat id sejak 2026-09-02, sejalan dengan lahan dan riwayat
        // penghunian. Penyaringan menurut nama sebelumnya putus diam-diam
        // begitu suksesi mengganti nama kepala keluarga (rules.md 6.5).
        'rumah' => collect(DummyData::rumah())
            ->firstWhere('transmigran_id', $data['id_transmigran']),

        'lahan' => $lahan,
        // Satu baris per KK (Putaran 15): total luas keluarga adalah jumlah
        // kolom pekarangan dan usaha, bukan jumlah baris.
        'totalLuas' => array_sum(array_map(
            fn ($l) => (float) ($l['luas_pekarangan'] ?? 0) + (float) ($l['luas_usaha'] ?? 0),
            $lahan
        )),

        // Dipisah per peran, sebab KTP, KK, dan SK penempatan adalah dokumen
        // yang berbeda dan tidak boleh saling menimpa (rules.md 14a.8b).
        'berkasKtp' => DummyData::berkasMilik('transmigran_berkas', 'transmigran_id', $data['id_transmigran'], 'ktp'),
        'berkasKk' => DummyData::berkasMilik('transmigran_berkas', 'transmigran_id', $data['id_transmigran'], 'kk'),
        'berkasSk' => DummyData::berkasMilik('transmigran_berkas', 'transmigran_id', $data['id_transmigran'], 'sk'),

        // Seluruh berkas keluarga tanpa saringan peran, untuk panel Dokumen.
        // Termasuk SHM, yang tidak punya isian unggah tersendiri di form.
        'berkasKeluarga' => DummyData::berkasMilik('transmigran_berkas', 'transmigran_id', $data['id_transmigran']),

        // Anggota keluarga selain kepala keluarga (Rombongan B, 2026-08-28).
        // Dibaca lewat id, sejalan dengan lahan.
        'anggotaKeluarga' => array_values(array_filter(
            DummyData::anggotaKeluarga(),
            fn ($a) => $a['transmigran_id'] === $data['id_transmigran']
        )),

        /*
         * TAB HASIL PANEN DICABUT 2026-08-22.
         *
         * Panen kini dicatat per POKTAN, bukan per orang, sehingga tidak ada
         * lagi cara yang sahih menyaringnya bagi satu keluarga. Digantikan
         * tautan ke poktan tempat keluarga ini bernaung.
         */
        'poktanBernaung' => array_values(array_filter(
            $anggotaPoktan,
            fn ($a) => $a['transmigran_id'] === $data['id_transmigran']
                && $a['status'] === 'Aktif'
        )),

        // Peta poktan ke SP-nya. Mencarinya di dalam perulangan berarti
        // menyusuri seluruh daftar poktan untuk tiap baris.
        'spPoktan' => collect(DummyData::poktan())
            ->pluck('satuan_permukiman', 'id_poktan')
            ->all(),

        // Riwayat suksesi kepala keluarga. Satu baris transmigran adalah satu
        // RUMAH TANGGA, sehingga pergantian kepalanya menyunting baris ini dan
        // peristiwanya direkam terpisah (rules.md 6 poin 5).
        'riwayatKk' => DummyData::riwayatKepalaKeluarga($data['id_transmigran']),

        // Calon pengganti kepala keluarga: anggota keluarga yang ada. Dipilih
        // dari sini pada modal suksesi, tidak diketik (Stage B3, 2026-08-28).
        'calonPengganti' => DummyData::calonPenggantiKk($data['id_transmigran']),

        // Jabatan ketua poktan TIDAK diwariskan. Bila keluarga ini menjabat
        // ketua lewat jalur Kepala Keluarga, petugas wajib memutuskan nasib
        // jabatannya saat suksesi (rules.md 6 poin 5e).
        'poktanDiketuai' => DummyData::poktanDiketuaiKeluarga($data['id_transmigran']),

        // Keanggotaan poktan justru MENGIKUTI, sebab melekat pada keluarga
        // (rules.md 7a poin 3a). Petugas cukup diberi tahu, tidak diminta
        // memutuskan. Hanya wakil berjalur Kepala Keluarga yang ikut berganti.
        'keanggotaanIkut' => array_values(array_filter(
            $anggotaPoktan,
            fn ($a) => $a['transmigran_id'] === $data['id_transmigran']
                && $a['asal_wakil'] === AsalWakilPoktan::KepalaKeluarga->value
                && $a['status'] !== 'Sudah Keluar'
        )),

        'inisial' => DummyData::inisial($data['nama_kepala_keluarga']),
    ]);
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

/*
 * Pergantian kepala keluarga.
 *
 * Rute TERSENDIRI, bukan bagian dari perbarui. Suksesi adalah tindakan yang
 * berbeda dari menyunting data, dan menyatukannya membuat setiap perbaikan
 * ejaan nama ikut tercatat sebagai pergantian kepala keluarga (rules.md 6.5b).
 *
 * Tahap 5, satu transaksi:
 *  1. baca pengganti dari `pengganti_anggota_keluarga_id`;
 *  2. sunting baris transmigran (nama, NIK, no_kk) dengan data pengganti;
 *  3. HAPUS baris `anggota_keluarga` pengganti (ia kini kepala keluarga);
 *  4. tambahkan baris `riwayat_kepala_keluarga` (kedua sisi identitas);
 *  5. terapkan pilihan nasib jabatan ketua poktan.
 */
Route::post('/transmigran/{id}/ganti-kepala-keluarga', function (int $id) {
    return redirect()->route('transmigran.detail', ['id' => $id, 'tab' => 'riwayat-kk'])
        ->with('sukses', 'Pergantian kepala keluarga tercatat pada riwayat.');
})->where('id', '[0-9]+')->name('transmigran.ganti-kepala-keluarga');

/*
 * Mencatat peristiwa pada satu anggota keluarga SELAIN kepala keluarga
 * (Putaran 6): meninggal atau pindah. Barisnya tidak dihapus, hanya ditandai
 * `status` + `tanggal_peristiwa` + `keterangan_peristiwa`.
 *
 * Kepala keluarga TIDAK lewat sini; peristiwanya selalu lewat alur ganti
 * kepala keluarga di atas.
 *
 * Tahap 5: sunting satu baris `anggota_keluarga`, catat audit log.
 */
Route::post('/transmigran/{id}/anggota/{anggota}/catat-peristiwa', function (int $id) {
    return redirect()->route('transmigran.detail', ['id' => $id, 'tab' => 'keluarga'])
        ->with('sukses', 'Peristiwa anggota keluarga tercatat.');
})->where(['id' => '[0-9]+', 'anggota' => '[0-9]+'])->name('transmigran.anggota.catat-peristiwa');

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
    $semua = DummyData::rumah();

    $cari = trim((string) request('cari', ''));
    $filterSp = request('sp');
    $filterKondisi = request('kondisi');
    $filterHunian = request('status_hunian');

    $baris = array_values(array_filter($semua, function ($r) use ($cari, $filterSp, $filterKondisi, $filterHunian) {
        if ($cari !== '') {
            $cocok = str_contains(mb_strtolower((string) $r['no_rumah']), mb_strtolower($cari))
                || str_contains(mb_strtolower((string) ($r['penghuni'] ?? '')), mb_strtolower($cari));

            if (! $cocok) {
                return false;
            }
        }

        if ($filterSp && (string) $r['satuan_permukiman_id'] !== (string) $filterSp) {
            return false;
        }

        if ($filterKondisi && $r['kondisi'] !== $filterKondisi) {
            return false;
        }

        if ($filterHunian && $r['status_hunian'] !== $filterHunian) {
            return false;
        }

        return true;
    }));

    return view('pages.rumah.index', [
        'title' => 'Rumah dan Hunian',
        'semua' => $semua,
        'baris' => $baris,
        'cari' => $cari,
        'filterSp' => $filterSp,
        'filterKondisi' => $filterKondisi,
        'filterHunian' => $filterHunian,
        'adaFilter' => $cari !== '' || $filterSp || $filterKondisi || $filterHunian,
        'jumlahDihuni' => count(array_filter($semua, fn ($r) => $r['status_hunian'] === 'Dihuni')),
        'jumlahRusak' => count(array_filter($semua, fn ($r) => $r['kondisi'] !== 'Tidak Rusak')),

        'daftarSp' => DummyData::satuanPermukiman(),
        'opsiFilterKondisiRumah' => DummyData::opsiFilterReferensi(JenisReferensi::KondisiRumah),
        'opsiFilterStatusHunian' => DummyData::opsiFilterReferensi(JenisReferensi::StatusHunian),
    ]);
})->name('rumah.index');

Route::get('/rumah/{id}', function (int $id) {
    $data = collect(DummyData::rumah())->firstWhere('id_rumah', $id);

    abort_if($data === null, 404);

    return view('pages.rumah.detail', [
        'title' => 'Rumah '.$data['no_rumah'],
        'data' => $data,
        'riwayat' => DummyData::riwayatPenghunian($data['id_rumah']),

        // Foto jamak sejak Putaran 14; kondisi rumah dinilai dari beberapa sisi.
        'berkasFotoRumah' => DummyData::berkasMilik('rumah_berkas', 'rumah_id', $id, 'foto'),
    ]);
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
    $semua = DummyData::lahan();

    $cari = trim((string) request('cari', ''));
    $filterSp = request('sp');
    $filterJenis = request('peruntukan_lahan');
    $filterKategori = request('kategori_lahan');

    $baris = array_values(array_filter($semua, function ($l) use ($cari, $filterSp, $filterJenis, $filterKategori) {
        if ($cari !== '') {
            $cocok = str_contains(mb_strtolower((string) $l['kode_lahan']), mb_strtolower($cari))
                || str_contains(mb_strtolower($l['pemilik']), mb_strtolower($cari));

            if (! $cocok) {
                return false;
            }
        }

        if ($filterSp && (string) $l['satuan_permukiman_id'] !== (string) $filterSp) {
            return false;
        }

        // Penyaring peruntukan kini menanyakan "punya bidang ini?", bukan
        // "barisnya berperuntukan ini". Sejak satu keluarga tepat satu baris,
        // kedua bidang berada pada baris yang sama.
        if ($filterJenis === 'Lahan Pekarangan' && $l['luas_pekarangan'] === null) {
            return false;
        }

        if ($filterJenis === 'Lahan Usaha' && $l['luas_usaha'] === null) {
            return false;
        }

        // Kering dan basah adalah KOMPOSISI, bukan kategori bidang, sehingga
        // penyaringnya menanyakan "punya bagian basah?" bukan "seluruhnya
        // basah?". Bidang campuran 1,25 ha kering + 0,75 ha basah wajib
        // muncul pada kedua penyaring, dan itu memang maksudnya
        // (agents/rules.md 7.5c).
        if ($filterKategori === 'kering' && (float) ($l['luas_kering'] ?? 0) <= 0) {
            return false;
        }

        if ($filterKategori === 'basah' && (float) ($l['luas_basah'] ?? 0) <= 0) {
            return false;
        }

        return true;
    }));

    /*
        MENJUMLAH KOLOM, BUKAN BARIS (Putaran 15).

        Sebelumnya luas per peruntukan dihitung dengan menyaring baris menurut
        `peruntukan_lahan` lalu menjumlahkan kolom `luas`. Sejak kedua bidang
        berada pada satu baris, yang dijumlahkan adalah kolomnya masing-masing.

        Keluarga yang belum menerima salah satu bidang bernilai null dan ikut
        terhitung nol lewat penjumlahan biasa, tanpa perlu percabangan.
    */
    $jumlahKolom = fn (array $rows, string $kolom): float => array_sum(array_map(
        static fn ($r): float => (float) ($r[$kolom] ?? 0),
        $rows
    ));

    return view('pages.lahan.index', [
        'title' => 'Data Lahan',
        'semua' => $semua,
        'baris' => $baris,
        'cari' => $cari,
        'filterSp' => $filterSp,
        'filterJenis' => $filterJenis,
        'filterKategori' => $filterKategori,
        'adaFilter' => $cari !== '' || $filterSp || $filterJenis || $filterKategori,

        // Total luas satu baris adalah pekarangan ditambah usahanya.
        'totalLuasTampil' => $jumlahKolom($baris, 'luas_pekarangan') + $jumlahKolom($baris, 'luas_usaha'),

        'luasPekarangan' => $jumlahKolom($semua, 'luas_pekarangan'),
        'luasUsaha' => $jumlahKolom($semua, 'luas_usaha'),

        // Cacah bidang, bukan cacah baris: satu baris dapat memuat dua bidang.
        'jumlahBidang' => count(array_filter($semua, fn ($l) => $l['luas_pekarangan'] !== null))
            + count(array_filter($semua, fn ($l) => $l['luas_usaha'] !== null)),

        'daftarSp' => DummyData::satuanPermukiman(),
    ]);
})->name('lahan.index');

Route::get('/lahan/{id}', function (int $id) {
    $data = collect(DummyData::lahan())->firstWhere('id_lahan', $id);

    abort_if($data === null, 404);

    return view('pages.lahan.detail', [
        'title' => 'Lahan '.$data['kode_lahan'],
        'data' => $data,

        // Dibaca lewat id, bukan mencocokkan nama. Dua kepala keluarga dapat
        // bernama sama, dan pencocokan nama akan menautkan bidang ini ke
        // profil orang yang keliru tanpa ada yang menyadarinya.
        'pemilik' => collect(DummyData::transmigran())
            ->firstWhere('id_transmigran', $data['transmigran_id']),

        // Legalitas dibaca dari tempatnya yang benar (Putaran 12): SHM melekat
        // pada keluarga sebab meliputi seluruh bidangnya, HPL melekat pada
        // kawasan sebab ia alas hak milik instansi (rules.md 7.4a).
        'shm' => DummyData::berkasSatu('transmigran_berkas', 'transmigran_id', $data['transmigran_id'], 'shm'),
        'hpl' => DummyData::berkasSatu('kawasan_transmigrasi_berkas', 'kawasan_transmigrasi_id', 1, 'hpl'),
    ]);
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
    $semua = DummyData::hasilPanen();

    $cari = trim((string) request('cari', ''));
    $filterSp = request('sp');
    $filterKomoditas = request('komoditas');

    // Penyaring tahun tunggal diganti rentang dari-sampai 2026-08-28
    // (rules.md 12 poin 12). Halaman daftar transaksi ini aman untuk rentang
    // sebab tiap baris berdiri sendiri; rekap panen TIDAK, lihat 9 poin 8b.
    $filterTahunDari = request('tahun_dari');
    $filterTahunSampai = request('tahun_sampai');

    // Tahun panen diturunkan dari tanggalnya, menggantikan penyaringan per
    // musim tanam yang dicabut 2026-08-22 bersama fiturnya.
    $tahunPanen = fn ($p) => $p['periode_panen']
        ? (int) substr($p['periode_panen'], 0, 4)
        : null;

    $baris = array_values(array_filter($semua, function ($p) use ($cari, $filterSp, $filterKomoditas) {
        if ($cari !== '') {
            $cocok = str_contains(mb_strtolower($p['poktan']), mb_strtolower($cari))
                || str_contains(mb_strtolower($p['komoditas']), mb_strtolower($cari));

            if (! $cocok) {
                return false;
            }
        }

        if ($filterSp && (string) $p['satuan_permukiman_id'] !== (string) $filterSp) {
            return false;
        }

        if ($filterKomoditas && $p['komoditas'] !== $filterKomoditas) {
            return false;
        }

        return true;
    }));

    $baris = DummyData::saringRentangTahun($baris, $filterTahunDari, $filterTahunSampai, $tahunPanen);

    /*
     * Volume benih dan luas lahan dibaca LEWAT PENANAMAN, sebab keduanya milik
     * penanaman dan poktan, bukan milik catatan panen.
     *
     * Disusun sekali di sini alih-alih dicari ulang pada tiap baris: pencarian
     * penanaman beserta perhitungan lahan poktannya menyusuri seluruh
     * keanggotaan, dan mengulanginya per baris membuat halaman menghitung hal
     * yang sama berkali-kali.
     */
    $petaPenanaman = collect(DummyData::penanaman())->keyBy('id_penanaman');
    $kekuatanPoktan = [];
    $asalTanam = [];

    // Setara ton per baris, dahulu dihitung ulang di dalam perulangan tabel.
    $setaraTon = [];

    foreach ($semua as $p) {
        $tanam = $petaPenanaman[$p['penanaman_id']] ?? null;

        $kekuatanPoktan[$p['poktan_id']] ??= DummyData::rekapLahanPoktan($p['poktan_id']);

        $asalTanam[$p['id_hasil_panen']] = [
            'volume_benih' => (float) ($tanam['volume_benih'] ?? 0),
            'luas_lahan' => $kekuatanPoktan[$p['poktan_id']]['luas_total'],
        ];

        $setaraTon[$p['id_hasil_panen']] = DummyData::keTon($p['produksi'], $p['satuan']);
    }

    $daftarTahun = array_values(array_filter(array_unique(array_map($tahunPanen, $semua))));
    rsort($daftarTahun);

    return view('pages.panen.index', [
        'title' => 'Hasil Panen',
        'semua' => $semua,
        'baris' => $baris,
        'cari' => $cari,
        'filterSp' => $filterSp,
        'filterKomoditas' => $filterKomoditas,
        'filterTahunDari' => $filterTahunDari,
        'filterTahunSampai' => $filterTahunSampai,
        'adaFilter' => $cari !== '' || $filterSp || $filterKomoditas || $filterTahunDari || $filterTahunSampai,

        // Total dihitung setelah konversi ke ton, bukan menjumlahkan volume
        // mentah.
        'totalTonTampil' => array_sum(array_map(fn ($p) => $setaraTon[$p['id_hasil_panen']], $baris)),
        'totalTonSemua' => array_sum($setaraTon),

        'setaraTon' => $setaraTon,
        'asalTanam' => $asalTanam,
        'kekuatanPoktan' => $kekuatanPoktan,
        'daftarKomoditas' => array_values(array_unique(array_column($semua, 'komoditas'))),
        'daftarTahun' => $daftarTahun,
        'daftarSp' => DummyData::satuanPermukiman(),
    ]);
})->name('panen.index');

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
    $daftarTahun = DummyData::tahunPanenTercatat();
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
    $opsiFilter = DummyData::opsiFilterRekapPanen($tahunPanen);

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

    $rekap = DummyData::rekapPanen($kelompok, $tahunPanen, $filterSp, $filterKomoditas);

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

Route::get('/panen/{id}', function (int $id) {
    $data = collect(DummyData::hasilPanen())->firstWhere('id_hasil_panen', $id);

    abort_if($data === null, 404);

    return view('pages.panen.detail', [
        'title' => 'Panen '.$data['komoditas'],
        'data' => $data,
        'setaraTon' => DummyData::keTon($data['produksi'], $data['satuan']),

        // Penanaman asal panen ini, dibaca lewat relasi. Menyediakan tautan
        // balik ke penanaman asalnya.
        'tanam' => collect(DummyData::penanaman())->firstWhere('id_penanaman', $data['penanaman_id']),
    ]);
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
/*
 * Rekap kependudukan, dipakai DUA rute seperti rekap panen dan rekap pengaduan.
 */
$susunRekapKependudukan = function (?string $kelompokRute = null) {
    $kelompok = $kelompokRute ?? request('kelompok', 'tahun');
    $daftarTahun = DummyData::daftarTahunKependudukan();
    $tahunTerakhir = end($daftarTahun);
    $tahunDipilih = (int) request('tahun', $tahunTerakhir);

    if (! in_array($tahunDipilih, $daftarTahun, true)) {
        $tahunDipilih = $tahunTerakhir;
    }

    $perSp = DummyData::rekapPerSp($tahunDipilih);
    $penghuni = DummyData::rekapPenghuni($tahunDipilih);
    $pekerjaan = DummyData::sebaranPekerjaan($tahunDipilih);
    // Berlabel, sebab `sebaranDaerahAsal()` berkunci id kabupaten sejak
    // 2026-09-02. Pelabelan terpusat agar tidak tiap view melabeli sendiri.
    $daerahAsal = DummyData::sebaranDaerahAsalBerlabel($tahunDipilih);
    $pendidikan = DummyData::sebaranPendidikan($tahunDipilih);

    return view('pages.kependudukan.rekap', [
        'title' => 'Rekap Kependudukan',
        'kelompok' => $kelompok,
        'daftarTahun' => $daftarTahun,
        'tahunPilihan' => $tahunDipilih,
        'tahunTerakhir' => $tahunTerakhir,
        'perTahun' => DummyData::rekapKependudukan(),
        'perSp' => $perSp,
        'penghuni' => $penghuni,
        'pekerjaan' => $pekerjaan,
        'daerahAsal' => $daerahAsal,
        'pendidikan' => $pendidikan,
        'ringkasan' => DummyData::ringkasanDashboard(),

        /*
         * Daftar ini WAJIB sejalan dengan batasan `where` pada rute
         * `kependudukan.rekap.kelompok` dan larik pada DaftarTautanStatis.
         * Ketiganya mengunci hal yang sama, dan mengubah salah satunya saja
         * membuat halaman terbit membalas 404 tanpa penjaga apa pun
         * (notes.md 1e.5).
         */
        'labelKelompok' => [
            'tahun' => 'Tahun',
            'sp' => 'Satuan Permukiman',
            'status' => 'Status Tinggal',
            'pekerjaan' => 'Pekerjaan',
            'asal' => 'Daerah Asal',
            'pendidikan' => 'Pendidikan',
        ],
    ]);
};

Route::get('/kependudukan/rekap', fn () => $susunRekapKependudukan())->name('kependudukan.rekap');

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
Route::get('/kependudukan/rekap/{kelompok}', fn (string $kelompok) => $susunRekapKependudukan($kelompok))
    ->where('kelompok', 'tahun|sp|status|pekerjaan|asal|pendidikan')->name('kependudukan.rekap.kelompok');

Route::get('/poktan', function () {
    $semua = DummyData::poktan();
    $anggota = DummyData::anggotaPoktan();

    $cari = trim((string) request('cari', ''));
    $filterSp = request('sp');

    $baris = array_values(array_filter($semua, function ($p) use ($cari, $filterSp) {
        if ($cari !== '' && ! str_contains(mb_strtolower($p['nama']), mb_strtolower($cari))
            && ! str_contains(mb_strtolower($p['nama_ketua']), mb_strtolower($cari))) {
            return false;
        }
        if ($filterSp && (string) $p['satuan_permukiman_id'] !== (string) $filterSp) {
            return false;
        }

        return true;
    }));

    return view('pages.poktan.index', [
        'title' => 'Kelompok Tani',
        'semua' => $semua,
        'anggota' => $anggota,
        'baris' => $baris,
        'cari' => $cari,
        'filterSp' => $filterSp,
        'adaFilter' => $cari !== '' || $filterSp,
        'totalAnggota' => array_sum(array_column($semua, 'jumlah_anggota')),
        'anggotaAktif' => count(array_filter($anggota, fn ($a) => $a['status'] === 'Aktif')),
        'daftarSp' => DummyData::satuanPermukiman(),
    ]);
})->name('poktan.index');

Route::get('/poktan/{id}', function (int $id) {
    $data = collect(DummyData::poktan())->firstWhere('id_poktan', $id);

    abort_if($data === null, 404);

    $anggota = DummyData::anggotaPoktan($data['id_poktan']);

    // Identitas ketua bercabang tiga jalur, dipusatkan pada satu helper agar
    // tidak diulang di setiap tempat yang menampilkannya.
    $ketua = DummyData::identitasWakil($data, 'ketua');

    /*
     * Nama kepala keluarga setiap wakil, dikumpulkan sekali di sini.
     *
     * Bentuk lamanya memanggil `cariTransmigran()` DI DALAM perulangan
     * anggota, yakni satu penelusuran seluruh data transmigran untuk setiap
     * wakil yang bukan kepala keluarga. Hanya wakil semacam itu yang
     * membutuhkannya, sehingga petanya pun hanya memuat mereka.
     */
    $namaKkWakil = [];
    foreach ($anggota as $a) {
        if ($a['asal_wakil'] !== AsalWakilPoktan::KepalaKeluarga->value) {
            $kk = DummyData::cariTransmigran($a['transmigran_id']);
            $namaKkWakil[$a['transmigran_id']] = $kk['nama_kepala_keluarga'] ?? '-';
        }
    }

    return view('pages.poktan.detail', [
        'title' => $data['nama'],
        'data' => $data,
        'anggota' => $anggota,
        // Alsintan yang bagiannya diterima poktan ini (Putaran 7): satu baris
        // per distribusi, membawa konteks pengadaannya.
        'alsintan' => (function () use ($data) {
            $hasil = [];
            foreach (DummyData::alsintan() as $a) {
                foreach ($a['distribusi'] as $d) {
                    if ($d['poktan_id'] === $data['id_poktan']) {
                        $hasil[] = $d + [
                            'jenis_alsintan' => $a['jenis_alsintan'],
                            'nama_alat' => $a['nama_alat'],
                            'tahun_pengadaan' => $a['tahun_pengadaan'],
                            'sumber_dana' => $a['sumber_dana'],
                            'id_alsintan' => $a['id_alsintan'],
                        ];
                    }
                }
            }

            return $hasil;
        })(),
        // Saprotan yang bagiannya diterima poktan ini (Putaran 7): satu baris
        // per distribusi, membawa konteks pengadaannya.
        'saprotan' => array_values(array_filter(
            DummyData::saprotanDistribusi(),
            fn ($d) => $d['poktan_id'] === $data['id_poktan'],
        )),
        'aktif' => count(array_filter($anggota, fn ($a) => $a['status'] === 'Aktif')),
        'ketua' => $ketua,
        'keluargaKetua' => DummyData::cariTransmigran($data['ketua_transmigran_id']),
        'namaKkWakil' => $namaKkWakil,

        // Luas lahan ketua diturunkan dari bidang milik keluarganya, kecuali
        // bagi ketua non-transmigran yang lahannya tidak terdata sehingga
        // diketik.
        'lahanKetua' => $ketua['asal']->dariKeluargaTransmigran()
            ? DummyData::rekapLahanKeluarga($data['ketua_transmigran_id'])
            : ['kering' => $data['luas_kering_ketua'] ?? 0, 'basah' => $data['luas_basah_ketua'] ?? 0],

        // Luas lahan kelompok dijumlahkan dari seluruh anggotanya. Kolom
        // `luas_lahan_kelompok` sudah dicabut sebab nilainya basi begitu luas
        // dibetulkan di modul lahan (erd.md 7.3).
        'luasKelompokKering' => array_sum(array_column($anggota, 'luas_kering')),
        'luasKelompokBasah' => array_sum(array_column($anggota, 'luas_basah')),
    ]);
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
    // yang jelas (rules.md 5.1 catatan 7).
    //
    // Tahap 6 juga wajib menolak transmigran yang masih berstatus Aktif pada
    // poktan lain, sebab satu transmigran hanya boleh aktif di satu kelompok
    // (rules.md 6.4). UNIQUE (poktan_id, transmigran_id) tidak menangkap ini,
    // karena poktannya memang berbeda.
    return redirect()->back()
        ->with('sukses', 'Data anggota kelompok tani tersimpan.');
})->name('anggota-poktan.simpan');

Route::put('/anggota-poktan/{id}', function (string $id) {
    // Satu-satunya jalur mengubah status keaktifan dan mengisi tanggal
    // keluar. Sebelum rute ini ada, keduanya hanya dapat diisi saat anggota
    // pertama kali ditambahkan, padahal justru keduanya yang berubah
    // belakangan (rules.md 7a.4).
    //
    // Anggota yang pindah kelompok ditandai Sudah Keluar di sini, lalu
    // didaftarkan sebagai baris baru pada kelompok tujuannya. Memindahkan
    // poktan_id pada baris yang sama akan menghapus jejak keanggotaan di
    // kelompok lama seolah tidak pernah ada.
    return redirect()->back()
        ->with('sukses', 'Perubahan data anggota tersimpan.');
})->where('id', '[0-9]+')->name('anggota-poktan.perbarui');
/*
 * Daftar alsintan. Pengambilan dan penyaringan datanya dipindahkan ke sini
 * 2026-08-27; sebelumnya dikerjakan blok `@php` di dalam view.
 *
 * Alasannya bukan kerapian melainkan biaya Tahap 4: selama view mengambil
 * datanya sendiri, mengganti `DummyData` dengan Eloquent berarti menyunting
 * viewnya, dan setiap perulangan di dalamnya berubah menjadi N+1. Lihat
 * agents/notes.md butir tindak lanjut 12.
 */
Route::get('/alsintan', function () {
    $semua = DummyData::alsintan();

    $cari = trim((string) request('cari', ''));
    $filterSp = request('sp');
    $filterKondisi = request('kondisi');

    $baris = array_values(array_filter($semua, function ($a) use ($cari, $filterSp, $filterKondisi) {
        $poktanTeks = mb_strtolower(implode(' ', $a['poktan_penerima']));

        if ($cari !== '' && ! str_contains(mb_strtolower($a['nama_alat']), mb_strtolower($cari))
            && ! str_contains(mb_strtolower($a['jenis_alsintan']), mb_strtolower($cari))
            && ! str_contains($poktanTeks, mb_strtolower($cari))) {
            return false;
        }
        // SP cocok bila ADA distribusi di SP itu (Putaran 7).
        if ($filterSp && ! in_array((int) $filterSp, array_column($a['distribusi'], 'satuan_permukiman_id'), true)) {
            return false;
        }
        // Kondisi cocok bila ADA distribusi berkondisi itu.
        if ($filterKondisi && ! in_array($filterKondisi, array_column($a['distribusi'], 'kondisi'), true)) {
            return false;
        }

        return true;
    }));

    return view('pages.alsintan.index', [
        'title' => 'Alsintan',
        'semua' => $semua,
        'baris' => $baris,
        'cari' => $cari,
        'filterSp' => $filterSp,
        'filterKondisi' => $filterKondisi,
        'adaFilter' => $cari !== '' || $filterSp || $filterKondisi,
        'totalUnit' => array_sum(array_column($semua, 'jumlah_total')),
        'belumTersalur' => array_sum(array_column($semua, 'jumlah_belum_tersalur')),

        // Cacah poktan penerima di seluruh distribusi (Putaran 7).
        'poktanPenerima' => count(array_unique(array_merge(
            [], ...array_map(fn ($a) => array_column($a['distribusi'], 'poktan_id'), $semua)
        ))),
        'rusak' => count(array_filter($semua, fn ($a) => in_array('Rusak Ringan', array_column($a['distribusi'], 'kondisi'), true)
            || in_array('Rusak Berat', array_column($a['distribusi'], 'kondisi'), true))),

        // Dropdown penyaring, bukan dropdown form: memakai varian yang ikut
        // memuat nilai nonaktif, sebab data lama masih memakainya.
        'daftarSp' => DummyData::satuanPermukiman(),
        'opsiFilterKondisi' => DummyData::opsiFilterReferensi(JenisReferensi::Kondisi),
    ]);
})->name('alsintan.index');

Route::get('/alsintan/{id}', function (int $id) {
    $data = collect(DummyData::alsintan())->firstWhere('id_alsintan', $id);

    abort_if($data === null, 404);

    return view('pages.alsintan.detail', [
        'title' => $data['nama_alat'],
        'data' => $data,
        'opsiKondisi' => DummyData::opsiReferensi(JenisReferensi::Kondisi),
    ]);
})->where('id', '[0-9]+')->name('alsintan.detail');

Route::post('/alsintan', function () {
    // Tahap 6: validasi, simpan, catat audit log. Satu baris pengadaan
    // (jenis, nama, jumlah total, tahun, sumber dana) beserta baris
    // distribusi per poktan penerima; Sigma distribusi <= jumlah total.
    return redirect()->route('alsintan.index')
        ->with('sukses', 'Data alsintan tersimpan.');
})->name('alsintan.simpan');

Route::put('/alsintan/{id}', function (int $id) {
    return redirect()->route('alsintan.detail', $id)
        ->with('sukses', 'Perubahan data alsintan tersimpan.');
})->where('id', '[0-9]+')->name('alsintan.perbarui');

/*
 * Memperbarui kondisi satu baris distribusi alsintan (Putaran 7). Kondisi
 * melekat pada distribusi, bukan pengadaan, sebab diamati per unit di
 * lapangan dan berubah setelah barang dibagikan.
 */
Route::post('/alsintan/{id}/distribusi/{dist}/kondisi', function (int $id) {
    return redirect()->route('alsintan.detail', $id)
        ->with('sukses', 'Kondisi alat diperbarui.');
})->where(['id' => '[0-9]+', 'dist' => '[0-9]+'])->name('alsintan.distribusi.kondisi');

Route::get('/saprotan', function () {
    $semua = DummyData::saprotan();

    $cari = trim((string) request('cari', ''));
    $filterSp = request('sp');
    $filterJenis = request('jenis');

    $baris = array_values(array_filter($semua, function ($s) use ($cari, $filterSp, $filterJenis) {
        $poktanTeks = mb_strtolower(implode(' ', $s['poktan_penerima']));

        if ($cari !== '' && ! str_contains(mb_strtolower($s['nama']), mb_strtolower($cari))
            && ! str_contains($poktanTeks, mb_strtolower($cari))) {
            return false;
        }
        // SP cocok bila ADA distribusi di SP itu (Putaran 7).
        if ($filterSp && ! in_array((int) $filterSp, array_column($s['distribusi'], 'satuan_permukiman_id'), true)) {
            return false;
        }
        if ($filterJenis && $s['jenis'] !== $filterJenis) {
            return false;
        }

        return true;
    }));

    /*
     * Sisa benih dihitung sekali di sini, bukan sekali per baris di dalam
     * perulangan view. Bentuk lamanya adalah N+1 yang sesungguhnya: satu
     * penelusuran seluruh catatan penanaman untuk SETIAP baris benih yang
     * tampil. Selama sumbernya array hal itu hanya lambat; begitu Tahap 7
     * menggantinya dengan kueri, ia menjadi satu kueri per baris.
     *
     * Hanya benih yang dihitung, sebab hanya benih yang dikurangi pemakaian
     * penanaman. Menghitungnya untuk pupuk berarti menjanjikan angka yang
     * tidak pernah dimaksudkan.
     */
    // Sisa PENGADAAN yang belum tersalurkan (barang di gudang UPT), per baris.
    $belumTersalur = [];
    foreach ($baris as $s) {
        $belumTersalur[$s['id_saprotan']] = $s['jumlah_belum_tersalur'];
    }

    return view('pages.saprotan.index', [
        'title' => 'Saprotan',
        'semua' => $semua,
        'baris' => $baris,
        'cari' => $cari,
        'filterSp' => $filterSp,
        'filterJenis' => $filterJenis,
        'adaFilter' => $cari !== '' || $filterSp || $filterJenis,
        'jenisUnik' => array_values(array_unique(array_column($semua, 'jenis'))),

        // Banyaknya poktan penerima di seluruh distribusi (Putaran 7).
        'poktanPenerima' => count(array_unique(array_merge(
            [], ...array_map(fn ($s) => array_column($s['distribusi'], 'poktan_id'), $semua)
        ))),
        'belumTersalur' => $belumTersalur,
        'daftarSp' => DummyData::satuanPermukiman(),
    ]);
})->name('saprotan.index');

Route::get('/saprotan/{id}', function (int $id) {
    $data = collect(DummyData::saprotan())->firstWhere('id_saprotan', $id);

    abort_if($data === null, 404);

    return view('pages.saprotan.detail', [
        'title' => $data['nama'],
        'data' => $data,
    ]);
})->where('id', '[0-9]+')->name('saprotan.detail');

Route::post('/saprotan', function () {
    // Tahap 6: validasi, simpan, catat audit log. Satu baris pengadaan
    // beserta baris distribusi per poktan penerima; sisa benih dihitung
    // per baris distribusi, tidak disimpan (rules.md §7c poin 8).
    return redirect()->route('saprotan.index')
        ->with('sukses', 'Data saprotan tersimpan.');
})->name('saprotan.simpan');

Route::put('/saprotan/{id}', function (int $id) {
    return redirect()->route('saprotan.detail', $id)
        ->with('sukses', 'Perubahan data saprotan tersimpan.');
})->where('id', '[0-9]+')->name('saprotan.perbarui');

Route::get('/komoditas', function () {
    $semua = DummyData::komoditas();

    $cari = trim((string) request('cari', ''));
    $filterTipe = request('tipe');

    $baris = array_values(array_filter($semua, function ($k) use ($cari, $filterTipe) {
        if ($cari !== '' && ! str_contains(mb_strtolower($k['nama']), mb_strtolower($cari))) {
            return false;
        }
        if ($filterTipe && $k['tipe'] !== $filterTipe) {
            return false;
        }

        return true;
    }));

    return view('pages.komoditas.index', [
        'title' => 'Data Komoditas',
        'semua' => $semua,
        'baris' => $baris,
        'sebaran' => DummyData::sebaranKomoditas(),
        'cari' => $cari,
        'filterTipe' => $filterTipe,
        'adaFilter' => $cari !== '' || $filterTipe,
        'unggulan' => count(array_filter($semua, fn ($k) => $k['is_unggulan'])),
        'opsiFilterTipe' => DummyData::opsiFilterReferensi(JenisReferensi::TipeKomoditas),
    ]);
})->name('komoditas.index');

Route::get('/komoditas/{id}', function (int $id) {
    $data = collect(DummyData::komoditas())->firstWhere('id_komoditas', $id);

    abort_if($data === null, 404);

    /*
     * Riwayat penanaman komoditas ini, dicocokkan lewat `komoditas_id` dan
     * bukan nama. Pencocokan teks putus begitu Admin membetulkan ejaan satu
     * komoditas, dan putusnya tidak memerahkan apa pun: tabnya sekadar
     * berubah menjadi kosong.
     */
    $riwayat = array_values(array_filter(
        DummyData::penanaman(),
        fn ($r) => $r['komoditas_id'] === $data['id_komoditas'],
    ));

    return view('pages.komoditas.detail', [
        'title' => $data['nama'],
        'data' => $data,
        'riwayat' => $riwayat,
    ]);
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

Route::get('/penanaman', function () {
    $semua = DummyData::penanaman();

    $cari = trim((string) request('cari', ''));
    $filterSp = request('sp');
    $filterKomoditas = request('komoditas');
    $filterStatus = request('status');

    // Penyaring tahun tunggal diganti rentang dari-sampai 2026-08-28
    // (rules.md 12 poin 12). Daftar transaksi ini aman untuk rentang; rekap
    // agregat tidak (9 poin 8b).
    $filterTahunDari = request('tahun_dari');
    $filterTahunSampai = request('tahun_sampai');

    // Tahun tanam diturunkan dari tanggalnya, bukan disimpan terpisah.
    // Menyimpannya sebagai kolom sendiri membuat nilainya dapat berbeda dari
    // tanggal yang menjadi sumbernya.
    $tahunTanam = fn ($r) => $r['periode_tanam']
        ? Carbon::parse($r['periode_tanam'].'-01')->year
        : null;

    // Status panen DITURUNKAN dari sisa luas, tidak disimpan sebagai kolom
    // (agents/rules.md bagian 7d poin 11). Disusun sekali di sini agar
    // penyaring, kolom tabel, dan kartu ringkasan membaca sumber yang sama.
    $statusPanen = [];
    foreach ($semua as $r) {
        $statusPanen[$r['id_penanaman']] = DummyData::statusPanen($r['id_penanaman']);
    }

    /*
     * Kekuatan tiap poktan: cacah anggota aktif dan luas lahannya.
     *
     * DIHITUNG, tidak disimpan (rules.md 7d.3). Disusun sekali per poktan di
     * sini, bukan dipanggil ulang pada tiap baris - satu poktan dapat memiliki
     * banyak penanaman, dan perhitungannya menyusuri seluruh keanggotaan
     * beserta lahannya.
     */
    $kekuatanPoktan = [];
    foreach ($semua as $r) {
        $kekuatanPoktan[$r['poktan_id']] ??= DummyData::rekapLahanPoktan($r['poktan_id']);
    }

    $baris = array_values(array_filter($semua, function ($r) use ($cari, $filterSp, $filterKomoditas, $filterStatus, $statusPanen) {
        if ($cari !== '' && ! str_contains(mb_strtolower($r['poktan']), mb_strtolower($cari))
            && ! str_contains(mb_strtolower($r['komoditas']), mb_strtolower($cari))) {
            return false;
        }
        if ($filterSp && (string) $r['satuan_permukiman_id'] !== (string) $filterSp) {
            return false;
        }
        if ($filterKomoditas && $r['komoditas'] !== $filterKomoditas) {
            return false;
        }
        if ($filterStatus && $statusPanen[$r['id_penanaman']]->value !== $filterStatus) {
            return false;
        }

        return true;
    }));

    $baris = DummyData::saringRentangTahun($baris, $filterTahunDari, $filterTahunSampai, $tahunTanam);

    $daftarTahun = array_values(array_filter(array_unique(array_map($tahunTanam, $semua))));
    rsort($daftarTahun);

    return view('pages.penanaman.index', [
        'title' => 'Penanaman',
        'semua' => $semua,
        'baris' => $baris,
        'cari' => $cari,
        'filterSp' => $filterSp,
        'filterTahunDari' => $filterTahunDari,
        'filterTahunSampai' => $filterTahunSampai,
        'filterKomoditas' => $filterKomoditas,
        'filterStatus' => $filterStatus,
        'adaFilter' => $cari !== '' || $filterSp || $filterTahunDari || $filterTahunSampai || $filterKomoditas || $filterStatus,
        'statusPanen' => $statusPanen,
        'kekuatanPoktan' => $kekuatanPoktan,
        'totalLuas' => array_sum(array_column($baris, 'realisasi_tanam')),

        /*
         * Luas yang masih berdiri tanaman, yaitu seluruh penanaman yang belum
         * dipanen sama sekali.
         *
         * DISEDERHANAKAN 2026-08-24: sebelumnya menjumlahkan sisa parsial tiap
         * penanaman. Sisa parsial kini tidak lagi mungkin ada, sebab satu panen
         * selalu menutup seluruh luas yang ditanam.
         */
        'totalBelumDipanen' => array_sum(array_map(
            fn ($r) => $statusPanen[$r['id_penanaman']] === StatusPanen::BelumDipanen
                ? (float) $r['realisasi_tanam']
                : 0.0,
            $semua
        )),

        'daftarTahun' => $daftarTahun,
        'daftarKomoditas' => array_values(array_unique(array_column($semua, 'komoditas'))),
        'daftarSp' => DummyData::satuanPermukiman(),
    ]);
})->name('penanaman');

/*
 * Halaman rincian penanaman.
 *
 * Ditambahkan 2026-08-20 agar modul ini memiliki tab Catatan Log seperti modul
 * lain. Sebelumnya hanya ada halaman daftar, sehingga perubahan datanya tidak
 * dapat ditelusuri dari tempat datanya sendiri.
 *
 * Rute rincian didaftarkan SETELAH rute daftarnya, mengikuti catatan lama pada
 * berkas ini bahwa alamat beruas dua dapat tertangkap sebagai id.
 *
 * Alamatnya `/penanaman`, DAHULU `/riwayat-tanam` (diubah 2026-08-22). Tidak
 * disediakan pengalihan dari alamat lama: Tahap 2 belum pernah terbit sebagai
 * sistem yang dipakai, sehingga tidak ada tautan lama yang perlu dijaga.
 *
 * Rute musim tanam DIHAPUS pada tanggal yang sama bersama fiturnya.
 */
Route::get('/penanaman/{id}', function (int $id) {
    $data = collect(DummyData::penanaman())->firstWhere('id_penanaman', $id);

    abort_if($data === null, 404);

    /*
     * Panen dari penanaman ini, dibaca lewat relasi `penanaman_id`.
     *
     * Sebelumnya dicocokkan lewat pasangan komoditas dan petani, sebab hasil
     * panen belum menyimpan tautannya. Pencocokan teks semacam itu menyatukan
     * dua penanaman berbeda yang kebetulan sama komoditas dan penggarapnya,
     * sehingga volumenya terhitung dua kali.
     */
    $panen = array_values(array_filter(
        DummyData::hasilPanen(),
        fn ($p) => ($p['penanaman_id'] ?? null) === $data['id_penanaman'],
    ));

    return view('pages.penanaman.detail', [
        'title' => $data['komoditas'].' - '.$data['poktan'],
        'data' => $data,
        'panen' => $panen,

        /*
         * DIPERBAIKI 2026-08-24: sebelumnya menjumlahkan kunci `volume` yang
         * sudah dihapus pada perombakan 2026-08-22, sehingga baris "Total
         * volume" SELALU 0,00.
         *
         * Dijumlahkan setelah konversi ke ton, bukan angka mentah: satu
         * penanaman memang satu komoditas, tetapi menuliskannya begini membuat
         * halaman ini tidak menjadi pengecualian dari rules.md 8a.5.
         */
        'produksiTon' => array_sum(array_map(
            fn ($p) => DummyData::keTon($p['produksi'], $p['satuan']),
            $panen
        )),

        'luasDipanen' => array_sum(array_column($panen, 'realisasi_panen')),
        'luasPuso' => array_sum(array_map(fn ($p) => (float) ($p['puso'] ?? 0), $panen)),

        // Tiga angka turunan, seluruhnya dihitung bukan disimpan, sehingga
        // selalu mengikuti keanggotaan dan lahan terbaru.
        'status' => DummyData::statusPanen($data['id_penanaman']),
        'belumDitanam' => DummyData::lahanTersedia($data['poktan_id']),
        'rekapPoktan' => DummyData::rekapLahanPoktan($data['poktan_id']),

        // Benih dibaca lewat baris distribusi (jatah poktan ini), lalu
        // konteks pengadaannya (Putaran 7).
        'benih' => $data['saprotan_distribusi_id']
            ? collect(DummyData::saprotanDistribusi())->firstWhere('id_saprotan_distribusi', $data['saprotan_distribusi_id'])
            : null,
    ]);
})->where('id', '[0-9]+')->name('penanaman.detail');

Route::post('/penanaman', function () {
    // Tahap 7: lahan_id wajib, sebab lokasi produksi hasil panen dibaca
    // lewat rantai penanaman ke lahan ke satuan permukiman.
    return redirect()->route('penanaman')
        ->with('sukses', 'Catatan penanaman tersimpan.');
})->name('penanaman.simpan');

Route::get('/sp/infrastruktur', function () {
    $semua = DummyData::infrastruktur();

    $cari = trim((string) request('cari', ''));
    $filterSp = request('sp');
    $filterJenis = request('jenis');
    $filterKondisi = request('kondisi');

    $baris = array_values(array_filter($semua, function ($i) use ($cari, $filterSp, $filterJenis, $filterKondisi) {
        if ($cari !== '' && ! str_contains(mb_strtolower($i['nama']), mb_strtolower($cari))) {
            return false;
        }
        if ($filterSp && (string) $i['satuan_permukiman_id'] !== (string) $filterSp) {
            return false;
        }
        if ($filterJenis && $i['jenis'] !== $filterJenis) {
            return false;
        }
        if ($filterKondisi && $i['kondisi'] !== $filterKondisi) {
            return false;
        }

        return true;
    }));

    return view('pages.infrastruktur.index', [
        'title' => 'Infrastruktur SP',
        'semua' => $semua,
        'baris' => $baris,

        // Rekap kondisi per jenis, dipakai tabel ringkas di bawah daftar.
        // Sengaja dihitung atas SELURUH data, bukan hasil penyaringan, sebab
        // yang dijawabnya adalah keadaan kawasan, bukan keadaan tampilan.
        'statusJenis' => DummyData::statusInfrastruktur(),

        'cari' => $cari,
        'filterSp' => $filterSp,
        'filterJenis' => $filterJenis,
        'filterKondisi' => $filterKondisi,
        'adaFilter' => $cari !== '' || $filterSp || $filterJenis || $filterKondisi,
        'rusakBerat' => count(array_filter($semua, fn ($i) => $i['kondisi'] === 'Rusak Berat')),
        'perluPerbaikan' => count(array_filter($semua, fn ($i) => $i['kondisi'] !== 'Baik')),

        'daftarSp' => DummyData::satuanPermukiman(),
        'opsiFilterJenis' => DummyData::opsiFilterReferensi(JenisReferensi::JenisInfrastruktur),
        'opsiFilterKondisi' => DummyData::opsiFilterReferensi(JenisReferensi::Kondisi),
    ]);
})->name('infrastruktur.index');

// Redirect 301 untuk kompatibilitas alamat lama /infrastruktur
Route::get('/infrastruktur', function () {
    return redirect()->route('infrastruktur.index', request()->query(), 301);
});

Route::get('/sp/infrastruktur/{id}', function (int $id) {
    $data = collect(DummyData::infrastruktur())->firstWhere('id_infrastruktur', $id);

    abort_if($data === null, 404);

    return view('pages.infrastruktur.detail', [
        'title' => $data['nama'],
        'data' => $data,
        'daftarSp' => DummyData::satuanPermukiman(),

        // Satu aset dapat punya beberapa titik kerusakan, sehingga fotonya
        // jamak. Diambil di sini, sebab view dilarang mengambil datanya
        // sendiri (notes.md 1g.5).
        'berkasFoto' => DummyData::berkasMilik('infrastruktur_berkas', 'infrastruktur_id', $id, 'foto'),
    ]);
})->where('id', '[0-9]+')->name('infrastruktur.detail');

// Redirect 301 untuk kompatibilitas alamat lama /infrastruktur/{id}
Route::get('/infrastruktur/{id}', function (int $id) {
    return redirect()->route('infrastruktur.detail', ['id' => $id], 301);
})->where('id', '[0-9]+');

Route::post('/sp/infrastruktur', function () {
    // Tahap 8: validasi, simpan, catat audit log. Modul pendataan aset,
    // sehingga tidak ada alur laporan kerusakan di sini.
    return redirect()->route('infrastruktur.index')
        ->with('sukses', 'Data aset infrastruktur tersimpan.');
})->name('infrastruktur.simpan');

Route::put('/sp/infrastruktur/{id}', function (int $id) {
    // Tahap 8: perubahan kondisi ikut memengaruhi penilaian kondisi SP pada
    // penilaian berikutnya, bukan penilaian yang sudah tersimpan.
    return redirect()->route('infrastruktur.detail', $id)
        ->with('sukses', 'Perubahan data aset tersimpan.');
})->where('id', '[0-9]+')->name('infrastruktur.perbarui');

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

Route::get('/audit-log', function () {
    $semua = DummyData::auditLog();

    $cari = trim((string) request('cari', ''));
    $filterAksi = request('aksi');
    $filterPengguna = request('pengguna');

    // Penyaring rentang tahun ditambahkan 2026-08-28 (rules.md 12 poin 12);
    // audit log memperolehnya untuk pertama kalinya. Aman: tiap baris satu
    // peristiwa, menyaring rentang hanya menyempitkan daftar.
    $filterTahunDari = request('tahun_dari');
    $filterTahunSampai = request('tahun_sampai');
    $tahunPeristiwa = fn ($a) => $a['waktu']
        ? (int) substr($a['waktu'], 0, 4)
        : null;

    $baris = array_values(array_filter($semua, function ($a) use ($cari, $filterAksi, $filterPengguna) {
        if ($cari !== '' && ! str_contains(mb_strtolower($a['ringkasan']), mb_strtolower($cari))
            && ! str_contains(mb_strtolower($a['nama_tabel']), mb_strtolower($cari))) {
            return false;
        }
        if ($filterAksi && $a['aksi'] !== $filterAksi) {
            return false;
        }
        if ($filterPengguna && $a['pengguna'] !== $filterPengguna) {
            return false;
        }

        return true;
    }));

    $baris = DummyData::saringRentangTahun($baris, $filterTahunDari, $filterTahunSampai, $tahunPeristiwa);

    $daftarTahun = array_values(array_filter(array_unique(array_map($tahunPeristiwa, $semua))));
    rsort($daftarTahun);

    return view('pages.pengguna.audit-log', [
        'title' => 'Audit Log',
        'semua' => $semua,
        'baris' => $baris,
        'cari' => $cari,
        'filterAksi' => $filterAksi,
        'filterPengguna' => $filterPengguna,
        'filterTahunDari' => $filterTahunDari,
        'filterTahunSampai' => $filterTahunSampai,
        'adaFilter' => $cari !== '' || $filterAksi || $filterPengguna || $filterTahunDari || $filterTahunSampai,
        'daftarAksi' => array_values(array_unique(array_column($semua, 'aksi'))),
        'daftarPengguna' => array_values(array_unique(array_column($semua, 'pengguna'))),
        'daftarTahun' => $daftarTahun,
    ]);
})->name('audit-log');

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

Route::put('/penanaman/{id}', function (int $id) {
    return redirect()->route('penanaman')->with('sukses', 'Perubahan catatan penanaman tersimpan.');
})->where('id', '[0-9]+')->name('penanaman.perbarui');

Route::delete('/penanaman/{id}', function (int $id) {
    return redirect()->route('penanaman')->with('sukses', 'Catatan penanaman dihapus.');
})->where('id', '[0-9]+')->name('penanaman.hapus');

Route::delete('/alsintan/{id}', function (int $id) {
    return redirect()->route('alsintan.index')->with('sukses', 'Data alsintan dihapus.');
})->where('id', '[0-9]+')->name('alsintan.hapus');

Route::delete('/saprotan/{id}', function (int $id) {
    return redirect()->route('saprotan.index')->with('sukses', 'Data saprotan dihapus.');
})->where('id', '[0-9]+')->name('saprotan.hapus');

Route::delete('/komoditas/{id}', function (int $id) {
    // Tahap 7: tolak bila masih dipakai penanaman atau hasil panen.
    return redirect()->route('komoditas.index')->with('sukses', 'Data komoditas dihapus.');
})->where('id', '[0-9]+')->name('komoditas.hapus');

Route::delete('/sp/infrastruktur/{id}', function (int $id) {
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
    return back()->with('info', 'Template impor '.str_replace('-', ' ', $entitas)
        .' akan tersedia setelah backend selesai.');
})->where('entitas', '[a-z\-]+')->name('template-impor');
