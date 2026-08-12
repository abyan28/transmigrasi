<?php

/**
 * Uji perenderan halaman Tahap 2.
 *
 * Menjaga agar halaman yang sudah dibangun tetap merender tanpa galat dan
 * tetap memenuhi aturan yang mudah tergeser tanpa disadari, terutama larangan
 * kontrol mati (ANTISLOP-ID R-24 dan R-26) serta kewajiban penanda data contoh
 * (R-17 dan R-38).
 */

use App\Support\DummyData;
use Illuminate\Support\Facades\Route;
use Tests\Support\BerkasBlade;

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

it('merender dashboard beserta seluruh wadah grafiknya', function () {
    $respons = $this->get(route('beranda'));

    $respons->assertOk();

    // Sepuluh wadah grafik memuat 16 indikator; sebagian indikator disajikan
    // sebagai kartu statistik dan tabel, sesuai pemetaan ui-spec.md bagian 9.
    foreach ([
        'grafikPenduduk', 'grafikKomoditas', 'grafikPendapatan', 'grafikMutasiKk',
        'grafikPenghuni', 'grafikPekerjaan', 'grafikPanen', 'grafikHarga',
        'grafikInfrastruktur', 'grafikMutuData',
    ] as $idGrafik) {
        $respons->assertSee('id="' . $idGrafik . '"', false);
        $respons->assertSee("buatGrafik('" . $idGrafik . "'", false);
    }
});

it('menyediakan tabel data alternatif untuk setiap grafik', function () {
    // Grafik wajib punya padanan tabel agar isinya terbaca pembaca layar
    // (agents/ui-spec.md bagian 9 poin 7). Jumlahnya dibandingkan terhadap
    // banyaknya grafik, bukan angka tetap, agar grafik baru yang lupa diberi
    // tabel langsung tertangkap tanpa perlu menyunting uji ini.
    $isi = $this->get(route('beranda'))->getContent();

    $jumlahGrafik = substr_count($isi, 'buatGrafik(');
    $jumlahTabel = substr_count($isi, 'Lihat tabel data');

    expect($jumlahTabel)->toBe($jumlahGrafik)
        ->and($jumlahGrafik)->toBeGreaterThan(0);
});

it('menampilkan penanda data contoh pada dashboard', function () {
    $this->get(route('beranda'))->assertSee('Data contoh');
});

it('menampilkan angka ringkasan dalam format Indonesia', function () {
    $ringkasan = DummyData::ringkasanDashboard();

    // Titik sebagai pemisah ribuan (agents/rules.md bagian 13.3 poin 3).
    $this->get(route('beranda'))
        ->assertSee(number_format($ringkasan['jumlah_kk'], 0, ',', '.'))
        ->assertSee(number_format($ringkasan['volume_panen_ton'], 3, ',', '.'));
});

it('menyimpan filter dashboard pada query string', function () {
    // Filter belum menyaring data sampai Task 9.2, tetapi kontrolnya sudah
    // nyata: pilihan bertahan setelah halaman dimuat ulang.
    $this->get(route('beranda', ['sp' => 2]))
        ->assertOk()
        ->assertSee('Bersihkan');
});

/*
|--------------------------------------------------------------------------
| Drill-down per satuan permukiman
|--------------------------------------------------------------------------
*/

it('merender halaman rincian untuk keenam satuan permukiman', function () {
    foreach (DummyData::satuanPermukiman() as $sp) {
        $this->get(route('dashboard.sp', $sp['id_satuan_permukiman']))
            ->assertOk()
            ->assertSee($sp['nama'])
            ->assertSee($sp['kecamatan']);
    }
});

it('membalas 404 untuk satuan permukiman yang tidak ada', function () {
    // Alamat karangan tidak boleh menghasilkan halaman kosong yang membingungkan.
    $this->get('/dashboard/sp/99')->assertNotFound();
    $this->get('/dashboard/sp/0')->assertNotFound();
});

it('menampilkan hanya data milik satuan permukiman yang dibuka', function () {
    // SP Tniumanu memuat PETRUS NAHAK, bukan YOHANES BERE yang ada di SP Kapitan Meo.
    $this->get(route('dashboard.sp', 2))
        ->assertSee('PETRUS NAHAK')
        ->assertDontSee('YOHANES BERE');
});

it('menyediakan enam tab rincian beserta jumlah barisnya', function () {
    $respons = $this->get(route('dashboard.sp', 1));

    foreach (['Transmigran (', 'Rumah (', 'Lahan (', 'Panen (', 'Pengaduan (', 'Infrastruktur ('] as $label) {
        $respons->assertSee($label);
    }
});

it('menyertakan tabel alternatif untuk grafik halaman rincian SP', function () {
    $isi = $this->get(route('dashboard.sp', 1))->getContent();

    expect(substr_count($isi, 'Lihat tabel data'))->toBe(substr_count($isi, 'buatGrafik('));
});

it('menampilkan keadaan kosong pada tab yang tidak punya data', function () {
    // SP Tualaran belum memiliki data rumah, lahan, panen, dan infrastruktur
    // pada data contoh, sehingga keempat tabnya wajib memakai keadaan kosong.
    $this->get(route('dashboard.sp', 5))->assertSee('Belum ada data');
});

it('menautkan dashboard kawasan ke rincian setiap SP', function () {
    // Penelusuran wajib punya jalur teks, bukan hanya klik pada grafik, agar
    // tetap dapat dijangkau pengguna keyboard (ANTISLOP-ID R-32).
    $respons = $this->get(route('beranda'));

    foreach (DummyData::rekapPerSp() as $baris) {
        $respons->assertSee(route('dashboard.sp', $baris['satuan_permukiman_id']), false);
    }
});

it('memasang penelusuran klik pada grafik bersumbu satuan permukiman', function () {
    $this->get(route('beranda'))
        ->assertSee('id="grafikPerSp"', false)
        ->assertSee('drilldownSp(data.spId)', false);
});

it('menyediakan tautan pindah antar SP pada halaman rincian', function () {
    $respons = $this->get(route('dashboard.sp', 1));

    foreach (DummyData::satuanPermukiman() as $sp) {
        $respons->assertSee(route('dashboard.sp', $sp['id_satuan_permukiman']), false);
    }
});

/*
|--------------------------------------------------------------------------
| Profil dan kata sandi
|--------------------------------------------------------------------------
*/

it('merender halaman profil beserta identitas akun', function () {
    $pengguna = DummyData::penggunaSaatIni();

    $this->get(route('profil'))
        ->assertOk()
        ->assertSee($pengguna['nama'])
        ->assertSee($pengguna['username'])
        ->assertSee($pengguna['role']['nama']);
});

it('menampilkan nama dan username sebagai teks, bukan isian yang dapat diubah', function () {
    // Keduanya hanya dapat diubah admin (agents/rules.md bagian 14b poin 1),
    // sehingga tidak boleh dirender sebagai input yang tampak dapat disunting.
    $isi = $this->get(route('profil'))->getContent();

    expect($isi)->not->toContain('name="nama"')
        ->and($isi)->not->toContain('name="username"');
});

it('merender halaman ubah kata sandi beserta ketiga isiannya', function () {
    $this->get(route('profil.kata-sandi'))
        ->assertOk()
        ->assertSee('name="password_lama"', false)
        ->assertSee('name="password"', false)
        ->assertSee('name="password_confirmation"', false);
});

it('merender halaman wajib ganti kata sandi tanpa meminta kata sandi lama', function () {
    // Pengguna baru saja menerima kata sandi sementara dari admin dan sudah
    // membuktikan kepemilikan lewat proses masuk.
    $respons = $this->get(route('ganti-kata-sandi'));

    $respons->assertOk()->assertSee('name="password"', false);

    expect($respons->getContent())->not->toContain('name="password_lama"');
});

it('menyediakan jalan keluar dari halaman wajib ganti kata sandi', function () {
    // Seluruh halaman lain terkunci, sehingga tombol keluar wajib ada agar
    // pengguna tidak terjebak tanpa pilihan.
    $this->get(route('ganti-kata-sandi'))->assertSee(route('logout'), false);
});

/*
|--------------------------------------------------------------------------
| Modul transmigran
|--------------------------------------------------------------------------
*/

it('merender daftar transmigran beserta seluruh barisnya', function () {
    $respons = $this->get(route('transmigran.index'));

    $respons->assertOk();

    foreach (DummyData::transmigran() as $baris) {
        $respons->assertSee($baris['nama_kepala_keluarga'])->assertSee($baris['nik']);
    }
});

it('menyaring daftar transmigran menurut kata kunci', function () {
    $this->get(route('transmigran.index', ['cari' => 'YOHANES']))
        ->assertOk()
        ->assertSee('YOHANES BERE')
        ->assertDontSee('PETRUS NAHAK');
});

it('mencari transmigran memakai NIK maupun nomor KK', function () {
    $this->get(route('transmigran.index', ['cari' => '5321011505800001']))
        ->assertSee('YOHANES BERE')
        ->assertDontSee('MARIA DA COSTA');
});

it('menyaring daftar transmigran menurut satuan permukiman', function () {
    // SP Tniumanu hanya memuat PETRUS NAHAK pada data contoh.
    $this->get(route('transmigran.index', ['sp' => 2]))
        ->assertSee('PETRUS NAHAK')
        ->assertDontSee('YOHANES BERE');
});

it('menyaring daftar transmigran menurut status verifikasi', function () {
    $this->get(route('transmigran.index', ['status_verifikasi' => 'Ditolak']))
        ->assertSee('ANGELA SERAN')
        ->assertDontSee('YOHANES BERE');
});

it('menampilkan keadaan pencarian nihil beserta jalan keluarnya', function () {
    // Keadaan ini wajib dibedakan dari daftar yang memang belum berisi data
    // (agents/ui-spec.md bagian 7).
    $this->get(route('transmigran.index', ['cari' => 'ZZZTIDAKADA']))
        ->assertSee('Tidak ditemukan hasil untuk')
        ->assertSee('Bersihkan Filter');
});

it('merender rincian setiap transmigran', function () {
    foreach (DummyData::transmigran() as $baris) {
        $this->get(route('transmigran.detail', $baris['id_transmigran']))
            ->assertOk()
            ->assertSee($baris['nama_kepala_keluarga'])
            ->assertSee($baris['no_kk']);
    }
});

it('membalas 404 untuk transmigran yang tidak ada', function () {
    $this->get('/transmigran/99')->assertNotFound();
});

it('menulis alasan penolakan secara lengkap pada halaman rincian', function () {
    // Alasan adalah satu-satunya petunjuk perbaikan bagi operator, sehingga
    // tidak boleh hanya menjadi tooltip (agents/rules.md bagian 5.2 poin 7).
    $this->get(route('transmigran.detail', 4))
        ->assertSee('Data ditolak saat diperiksa')
        ->assertSee('Jumlah anggota keluarga tidak sesuai kartu keluarga. Mohon diperiksa ulang.');
});

it('menyediakan tombol Simpan dan Verifikasi pada modal form', function () {
    // Tombol gabungan hanya dirender bila pengguna berizin verifikasi
    // (agents/ui-spec.md bagian 6.2).
    $this->get(route('transmigran.index'))->assertSee('Simpan dan Verifikasi');
});

it('menuntut alasan saat menolak verifikasi', function () {
    $isi = $this->get(route('transmigran.detail', 1))->getContent();

    expect($isi)->toContain('Alasan penolakan')
        ->and($isi)->toContain('name="alasan"');
});

it('menautkan rincian transmigran ke satuan permukimannya', function () {
    $this->get(route('transmigran.detail', 1))
        ->assertSee(route('dashboard.sp', 1), false);
});

it('menyediakan seluruh rute tulis modul transmigran', function () {
    // Setiap tombol pada antarmuka wajib punya tujuan nyata, bukan kontrol mati.
    $isi = $this->get(route('transmigran.detail', 1))->getContent();

    foreach ([
        route('transmigran.perbarui', 1),
        route('transmigran.verifikasi', 1),
        route('transmigran.tolak', 1),
    ] as $tujuan) {
        expect($isi)->toContain($tujuan);
    }
});

it('memakai nama kolom kamus data pada isian form', function () {
    // Nama isian mengikuti agents/data-dictionary.md bagian 6.1, sehingga Form
    // Request pada Tahap 5 dapat membaca nama yang sama tanpa menyunting Blade.
    $isi = $this->get(route('transmigran.index'))->getContent();

    foreach ([
        'nama_kepala_keluarga', 'nik', 'no_kk', 'jenis_kelamin', 'tempat_lahir',
        'tanggal_lahir', 'pendidikan_terakhir', 'pekerjaan_kepala_keluarga',
        'jumlah_anggota_keluarga', 'pendapatan_per_bulan', 'daerah_asal',
        'tahun_kedatangan', 'status_tinggal', 'status_anggota_poktan', 'telepon',
        'dokumen_pendukung', 'keterangan', 'satuan_permukiman_id',
    ] as $kolom) {
        expect($isi)->toContain('name="' . $kolom . '"');
    }
});

/*
|--------------------------------------------------------------------------
| Modul rumah dan hunian
|--------------------------------------------------------------------------
*/

it('merender daftar rumah beserta seluruh barisnya', function () {
    $respons = $this->get(route('rumah.index'));

    $respons->assertOk();

    foreach (DummyData::rumah() as $baris) {
        $respons->assertSee($baris['no_rumah']);
    }
});

it('hanya menawarkan keluarga yang belum menempati rumah', function () {
    // Satu KK hanya boleh menempati satu rumah, dan pembatasan itu wajib
    // terlihat sejak di antarmuka (agents/rules.md bagian 6a poin 8).
    $isi = $this->get(route('rumah.index'))->getContent();

    $sudahPunyaRumah = array_filter(array_column(DummyData::rumah(), 'penghuni'));

    foreach ($sudahPunyaRumah as $nama) {
        expect($isi)->not->toContain($nama . ' (');
    }

    // Sebaliknya, yang belum punya rumah wajib muncul sebagai pilihan.
    foreach (DummyData::transmigranTanpaRumah() as $kk) {
        expect($isi)->toContain($kk['nama_kepala_keluarga'] . ' (');
    }
});

it('menyaring daftar rumah menurut status hunian', function () {
    $this->get(route('rumah.index', ['status_hunian' => 'Tidak Dihuni']))
        ->assertOk()
        ->assertSee('A-03');
});

it('menampilkan riwayat penghunian sebagai jejak yang tidak menimpa', function () {
    // Rumah 3 pernah dihuni lalu ditinggalkan; jejaknya wajib tetap terbaca
    // beserta alasan keluarnya (agents/rules.md bagian 6a poin 9).
    $this->get(route('rumah.detail', 3))
        ->assertOk()
        ->assertSee('DOMINGGUS TAEK')
        ->assertSee('Pindah mengikuti keluarga ke SP Weoe.')
        ->assertSee('Sudah keluar');
});

it('menjelaskan alasan rumah tidak dihuni', function () {
    $this->get(route('rumah.detail', 3))
        ->assertSee('Rumah tidak dihuni')
        ->assertSee('Atap rusak berat setelah angin kencang, sedang menunggu perbaikan.');
});

it('membalas 404 untuk rumah yang tidak ada', function () {
    $this->get('/rumah/99')->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Modul lahan
|--------------------------------------------------------------------------
*/

it('merender daftar lahan beserta total luasnya', function () {
    $respons = $this->get(route('lahan.index'));

    $respons->assertOk();

    // Rekap luas wajib memakai penjumlahan seluruh lahan, bukan satu baris
    // (agents/rules.md bagian 7.10).
    $total = array_sum(array_column(DummyData::lahan(), 'luas'));
    $respons->assertSee(number_format($total, 2, ',', '.'));
});

it('menyaring daftar lahan menurut jenis dan kategori', function () {
    $this->get(route('lahan.index', ['jenis_lahan' => 'Lahan Pekarangan']))
        ->assertOk()
        ->assertSee('LP-001')
        ->assertDontSee('LU-001');

    $this->get(route('lahan.index', ['kategori_lahan' => 'Lahan Basah']))
        ->assertSee('LU-002');
});

it('menampilkan dokumen lahan pada tab tersendiri', function () {
    // Satu lahan dapat memiliki lebih dari satu dokumen, sehingga dokumen
    // dikelola terpisah dari form lahan (data-dictionary.md bagian 7.2).
    $this->get(route('lahan.detail', 1))
        ->assertOk()
        ->assertSee('HPL/NTT/2016/0142')
        ->assertSee(route('lahan.dokumen.simpan', 1), false);
});

it('menyembunyikan tab pengelolaan untuk lahan pekarangan', function () {
    // Pola tanam, peralatan, dan kendala hanya berlaku bagi lahan usaha
    // (data-dictionary.md bagian 7.1).
    $pekarangan = $this->get(route('lahan.detail', 1))->getContent();
    $usaha = $this->get(route('lahan.detail', 2))->getContent();

    expect($pekarangan)->not->toContain('Kendala yang dihadapi')
        ->and($usaha)->toContain('Kendala yang dihadapi');
});

it('menampilkan keadaan kosong untuk lahan tanpa dokumen', function () {
    // Lahan 3 belum memiliki dokumen pada data contoh.
    $this->get(route('lahan.detail', 3))->assertSee('Belum ada dokumen lahan');
});

it('membalas 404 untuk lahan yang tidak ada', function () {
    $this->get('/lahan/99')->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Modul hasil panen
|--------------------------------------------------------------------------
*/

it('merender daftar panen beserta satuan asli tiap komoditas', function () {
    $this->get(route('panen.index'))
        ->assertOk()
        ->assertSee('320,500 Kilogram')
        ->assertSee('4,250 Ton');
});

it('menampilkan setara ton untuk satuan selain ton', function () {
    // Operator perlu melihat dua angka sekaligus: yang ia catat di lapangan,
    // dan yang dipakai sistem saat merekap.
    $this->get(route('panen.index'))->assertSee('setara 0,321 ton');
});

it('menjumlahkan volume panen memakai hasil konversi, bukan angka mentah', function () {
    // Menjumlahkan 4,250 ton dan 320,500 kilogram begitu saja menghasilkan
    // angka yang keliru (agents/rules.md bagian 8a poin 5).
    $benar = array_sum(array_map(
        fn ($p) => DummyData::keTon($p['volume'], $p['satuan']),
        DummyData::hasilPanen()
    ));

    $mentah = array_sum(array_column(DummyData::hasilPanen(), 'volume'));

    expect(round($benar, 3))->toBe(16.371)
        ->and($mentah)->toBeGreaterThan($benar);

    $this->get(route('panen.index'))->assertSee(number_format($benar, 3, ',', '.'));
});

it('mengubah volume ke ton memakai faktor satuannya', function () {
    expect(DummyData::keTon(1, 'Ton'))->toBe(1.0)
        ->and(DummyData::keTon(1, 'Kuintal'))->toBe(0.1)
        ->and(DummyData::keTon(1000, 'Kilogram'))->toBe(1.0);
});

it('merender rekap panen pada keempat dasar pengelompokan', function () {
    foreach (['sp', 'komoditas', 'musim', 'petani'] as $kelompok) {
        $this->get(route('panen.rekap', ['kelompok' => $kelompok]))
            ->assertOk()
            ->assertSee('Total kawasan');
    }
});

it('memakai baris total bermotif pada halaman rekap', function () {
    // Rekap adalah jenis komposisi keempat: tabel agregat dengan baris total
    // yang ditegaskan, tanpa kartu statistik (agents/ui-spec.md bagian 2.2).
    $isi = $this->get(route('panen.rekap'))->getContent();

    expect($isi)->toContain('motif-baris-total')
        ->and($isi)->not->toContain('motif-judul-kartu');
});

it('tidak menganggap rekap sebagai id panen', function () {
    // Rute /panen/rekap wajib didaftarkan sebelum /panen/{id}.
    $this->get(route('panen.rekap'))->assertOk()->assertSee('Rekap per');
});

it('membalas 404 untuk catatan panen yang tidak ada', function () {
    $this->get('/panen/99')->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Modul pengaduan
|--------------------------------------------------------------------------
*/

it('merender daftar pengaduan beserta seluruh barisnya', function () {
    $respons = $this->get(route('pengaduan.index'));

    $respons->assertOk();

    foreach (DummyData::pengaduan() as $baris) {
        $respons->assertSee($baris['nomor_pengaduan'])->assertSee($baris['judul']);
    }
});

it('menyaring pengaduan menurut status, kategori, dan prioritas', function () {
    $this->get(route('pengaduan.index', ['status' => 'Selesai']))
        ->assertSee('PGD-2026-0005')
        ->assertDontSee('PGD-2026-0001');

    $this->get(route('pengaduan.index', ['prioritas' => 'Mendesak']))
        ->assertSee('PGD-2026-0004');

    $this->get(route('pengaduan.index', ['kategori' => 'Rumah']))
        ->assertSee('PGD-2026-0002');
});

it('hanya menawarkan satu status berikutnya yang sah', function () {
    // Inti aturan modul ini: alur wajib berurutan dan tidak boleh melompat
    // (agents/rules.md bagian 10b poin 4). Antarmuka mencegahnya dengan
    // merender tepat satu tombol tujuan, bukan daftar seluruh status.
    $harapan = [
        3 => 'Diterima',   // sedang Menunggu Diterima
        2 => 'Diproses',   // sedang Diterima
        1 => 'Selesai',    // sedang Diproses
    ];

    foreach ($harapan as $id => $tujuan) {
        $isi = $this->get(route('pengaduan.detail', $id))->getContent();

        expect($isi)->toContain('Tandai ' . $tujuan)
            ->and($isi)->toContain('name="status_sesudah" value="' . $tujuan . '"');

        // Status lain tidak boleh ikut ditawarkan sebagai nilai kiriman.
        foreach (App\Enums\StatusPengaduan::cases() as $lain) {
            if ($lain->value !== $tujuan) {
                expect($isi)->not->toContain('name="status_sesudah" value="' . $lain->value . '"');
            }
        }
    }
});

it('tidak menawarkan tahap lanjutan pada pengaduan yang sudah selesai', function () {
    // Tombol mati lebih menyesatkan daripada tidak ada tombol (R-26).
    $isi = $this->get(route('pengaduan.detail', 5))->getContent();

    expect($isi)->toContain('Tidak ada tahap lanjutan')
        ->and($isi)->not->toContain('name="status_sesudah"');
});

it('menuntut catatan tindakan pada setiap penanganan', function () {
    // Riwayat tanpa catatan tidak menjelaskan apa pun kepada pembacanya
    // (agents/rules.md bagian 10b poin 5).
    $isi = $this->get(route('pengaduan.detail', 1))->getContent();

    expect($isi)->toContain('name="catatan"')
        ->and($isi)->toContain('Catatan Tindakan');
});

it('menampilkan riwayat penanganan berurutan', function () {
    $this->get(route('pengaduan.detail', 5))
        ->assertOk()
        ->assertSee('Laporan serangan hama diterima Dinas Pertanian.')
        ->assertSee('Pendampingan penyemprotan selesai, kondisi tanaman membaik. Petani diberi panduan pengendalian hama.');
});

it('menampilkan keadaan kosong untuk pengaduan yang belum ditangani', function () {
    // Pengaduan 3 dan 4 masih Menunggu Diterima, belum punya riwayat.
    $this->get(route('pengaduan.detail', 3))->assertSee('Belum ada penanganan');
});

it('menyimpulkan bidang penanganan dari kategori, bukan pilihan manual', function () {
    // Petugas pencatat tidak perlu hafal pembagian tugas antar-dinas
    // (App\Enums\BidangPengaduan::dariKategori).
    $isi = $this->get(route('pengaduan.index'))->getContent();

    expect($isi)->toContain('Mengikuti kategori terpilih')
        ->and($isi)->not->toContain('<select id="tambah_bidang"');
});

it('menandai pengaduan berprioritas mendesak yang belum selesai', function () {
    $this->get(route('pengaduan.detail', 4))
        ->assertSee('Pengaduan berprioritas mendesak');
});

it('merender rekap pengaduan pada seluruh dasar pengelompokan', function () {
    foreach (['kategori', 'status', 'sp', 'prioritas', 'bidang'] as $kelompok) {
        $this->get(route('pengaduan.rekap', ['kelompok' => $kelompok]))
            ->assertOk()
            ->assertSee('Total kawasan');
    }
});

it('menjumlahkan rekap pengaduan sesuai jumlah datanya', function () {
    $rekap = DummyData::rekapPengaduan('kategori');

    expect(array_sum(array_column($rekap, 'jumlah')))->toBe(count(DummyData::pengaduan()));

    // Selesai dan belum selesai wajib genap membagi keseluruhan.
    foreach ($rekap as $baris) {
        expect($baris['selesai'] + $baris['belum_selesai'])->toBe($baris['jumlah']);
    }
});

it('memakai komposisi rekap tanpa kartu statistik', function () {
    $isi = $this->get(route('pengaduan.rekap'))->getContent();

    expect($isi)->toContain('motif-baris-total')
        ->and($isi)->not->toContain('motif-judul-kartu');
});

it('tidak menganggap rekap sebagai id pengaduan', function () {
    $this->get(route('pengaduan.rekap'))->assertOk()->assertSee('Rekap per');
});

it('membalas 404 untuk pengaduan yang tidak ada', function () {
    $this->get('/pengaduan/99')->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Halaman publik tanpa login
|--------------------------------------------------------------------------
*/

it('merender halaman pengaduan warga tanpa perlu masuk', function () {
    $this->get(route('pengaduan-warga'))
        ->assertOk()
        ->assertSee('Sampaikan Pengaduan Anda');
});

it('tidak merender navigasi petugas pada halaman publik', function () {
    // Seluruh tujuan menu petugas memerlukan login, sehingga merendernya di
    // halaman warga berarti mengirim kontrol mati (R-24 dan R-26).
    $isi = $this->get(route('pengaduan-warga'))->getContent();

    // Diperiksa sebagai atribut href lengkap, bukan potongan alamat, karena
    // "/pengaduan" adalah awalan dari "/pengaduan-warga" sehingga pencocokan
    // potongan akan selalu cocok dan ujinya jadi tidak bermakna.
    expect($isi)->not->toContain('id="sidebar"')
        ->and($isi)->not->toContain('href="' . route('transmigran.index') . '"')
        ->and($isi)->not->toContain('href="' . route('pengaduan.index') . '"')
        ->and($isi)->not->toContain('href="' . route('rumah.index') . '"');
});

it('menyembunyikan sidebar dan spanduk petugas dari halaman publik', function () {
    $isi = $this->get(route('pengaduan-warga'))->getContent();

    expect($isi)->not->toContain('id="sidebar"')
        ->and($isi)->not->toContain('Seluruh angka dan nama pada halaman ini');
});

it('tidak meminta warga memilih bidang maupun prioritas', function () {
    // Penilaian kegentingan dan pembagian dinas adalah tugas petugas, bukan
    // beban pelapor (agents/ui-spec.md bagian 4.1a poin 2).
    $isi = $this->get(route('pengaduan-warga'))->getContent();

    expect($isi)->not->toContain('name="prioritas"')
        ->and($isi)->not->toContain('name="bidang"');
});

it('menampilkan nomor pengaduan besar setelah pengiriman berhasil', function () {
    // Nomor adalah satu-satunya cara warga melacak laporannya, sehingga wajib
    // tampil jelas beserta anjuran mencatatnya (ui-spec.md bagian 4.1a poin 3).
    $this->post(route('pengaduan-warga.kirim'), [
        'nama_pelapor' => 'TEST WARGA',
        'kontak_pelapor' => '081200000000',
        'satuan_permukiman_id' => 1,
        'kategori' => 'Rumah',
        'tanggal_pengaduan' => '2026-08-11',
        'judul' => 'Uji kirim',
        'deskripsi' => 'Uji alur kirim pengaduan.',
    ])->assertSessionHas('nomor_pengaduan');

    $this->followingRedirects()
        ->get(route('pengaduan-warga'))
        ->assertOk();
});

it('merender halaman lacak pengaduan', function () {
    $this->get(route('lacak-pengaduan'))
        ->assertOk()
        ->assertSee('Lacak Pengaduan');
});

it('menampilkan status dan catatan penanganan pada halaman lacak', function () {
    $this->get(route('lacak-pengaduan', ['nomor' => 'PGD-2026-0005']))
        ->assertOk()
        ->assertSee('Serangan hama pada tanaman jagung')
        ->assertSee('Pendampingan penyemprotan selesai, kondisi tanaman membaik. Petani diberi panduan pengendalian hama.');
});

it('tidak pernah menampilkan data pribadi pelapor pada halaman lacak', function () {
    // Aturan privasi paling penting modul ini: siapa pun yang menebak nomor
    // pengaduan tidak boleh memanen data pribadi warga lain
    // (agents/rules.md bagian 10b poin 1c).
    $isi = $this->get(route('lacak-pengaduan', ['nomor' => 'PGD-2026-0005']))->getContent();

    $pengaduan = collect(DummyData::pengaduan())->firstWhere('nomor_pengaduan', 'PGD-2026-0005');

    expect($isi)->not->toContain($pengaduan['nama_pelapor'])
        ->and($isi)->not->toContain($pengaduan['kontak_pelapor']);

    // Nama petugas penangan pun tidak ditampilkan kepada warga.
    foreach (DummyData::penangananPengaduan('PGD-2026-0005') as $jejak) {
        expect($isi)->not->toContain($jejak['petugas']);
    }
});

it('menjelaskan jalan keluar saat nomor pengaduan tidak ditemukan', function () {
    $this->get(route('lacak-pengaduan', ['nomor' => 'SALAH123']))
        ->assertOk()
        ->assertSee('Nomor pengaduan tidak ditemukan')
        ->assertSee('Kirim Pengaduan Baru');
});

it('memberi keterangan pada pengaduan yang belum ditangani', function () {
    // PGD-2026-0003 masih Menunggu Diterima dan belum punya riwayat.
    $this->get(route('lacak-pengaduan', ['nomor' => 'PGD-2026-0003']))
        ->assertSee('Belum ada catatan penanganan');
});

it('menautkan halaman masuk petugas ke kanal pengaduan warga', function () {
    $this->get(route('login'))
        ->assertSee(route('pengaduan-warga'), false)
        ->assertSee(route('lacak-pengaduan'), false);
});

/*
|--------------------------------------------------------------------------
| Halaman galat
|--------------------------------------------------------------------------
*/

it('merender halaman 404 untuk alamat yang tidak ada', function () {
    $this->get('/alamat-yang-tidak-pernah-ada')
        ->assertNotFound()
        ->assertSee('Halaman tidak ditemukan');
});

it('memakai halaman 404 kustom pada seluruh modul', function () {
    // abort(404) pada rute modul wajib memakai halaman yang sama, bukan
    // tampilan bawaan Laravel.
    foreach (['/transmigran/99', '/rumah/99', '/lahan/99', '/panen/99', '/pengaduan/99', '/dashboard/sp/99'] as $alamat) {
        $this->get($alamat)->assertNotFound()->assertSee('Galat 404');
    }
});

it('menyediakan jalan keluar pada halaman 404', function () {
    // Halaman galat tanpa jalan keluar memaksa pengguna menekan tombol kembali
    // peramban, dan itu bukan penanganan keadaan yang layak (ui-spec.md 7).
    $this->get('/alamat-yang-tidak-pernah-ada')
        ->assertSee('Buka Dashboard');
});

it('merender halaman 403 beserta jalan keluarnya', function () {
    // Rute uji dibuat sementara karena RBAC yang memicu 403 baru aktif pada
    // Tahap 3; yang diperiksa di sini adalah tampilannya.
    Illuminate\Support\Facades\Route::get('/uji-403', fn () => abort(403));

    $this->get('/uji-403')
        ->assertForbidden()
        ->assertSee('Galat 403')
        ->assertSee('Anda tidak memiliki akses ke halaman ini')
        ->assertSee('Kembali ke Dashboard');
});

it('menyediakan ilustrasi galat untuk kedua mode tema', function () {
    // Mode terang dan gelap sama-sama wajib berfungsi penuh (R-34).
    $isi = $this->get('/alamat-yang-tidak-pernah-ada')->getContent();

    expect($isi)->toContain('/images/error/404.svg')
        ->and($isi)->toContain('/images/error/404-dark.svg');
});

/*
|--------------------------------------------------------------------------
| Lima pola keadaan halaman
|--------------------------------------------------------------------------
*/

it('menyediakan kelima pola keadaan pada galeri komponen', function () {
    // Kelimanya wajib ditangani setiap halaman daftar dan rincian
    // (agents/ui-spec.md bagian 7).
    $this->get(route('galeri-komponen'))
        ->assertOk()
        ->assertSee('Belum ada data lahan')          // kosong
        ->assertSee('Tidak ditemukan hasil untuk')   // pencarian nihil
        ->assertSee('Sedang memuat data')            // memuat
        ->assertSee('Data gagal ditampilkan')        // galat
        ->assertSee('Buka Halaman 403');             // tanpa izin
});

it('memakai skeleton untuk keadaan memuat, bukan spinner layar penuh', function () {
    // Spinner menutupi seluruh halaman sehingga pengguna kehilangan konteks;
    // skeleton memberi tahu bentuk konten yang sedang datang.
    $isi = $this->get(route('galeri-komponen'))->getContent();

    expect($isi)->toContain('animate-pulse')
        ->and($isi)->toContain('role="status"');
});

it('menulis pesan galat tanpa istilah teknis', function () {
    // Pesan wajib dimengerti operator lapangan (agents/rules.md 13.3 poin 7).
    $isi = $this->get(route('galeri-komponen'))->getContent();

    expect($isi)->toContain('jaringan sedang tidak stabil')
        ->and($isi)->toContain('Coba Lagi');

    // Istilah teknis dilarang muncul sebagai pesan bagi pengguna.
    foreach (['HTTP 500', 'Internal Server Error', 'stack trace', 'Exception'] as $istilah) {
        expect($isi)->not->toContain($istilah);
    }
});

/*
|--------------------------------------------------------------------------
| Kesiapan dua mode tema dan responsif
|--------------------------------------------------------------------------
*/

it('menyediakan tombol ganti tema pada seluruh jenis tata letak', function () {
    // Mode terang dan gelap sama-sama wajib berfungsi penuh (R-34), termasuk
    // pada halaman galat dan halaman publik yang memakai tata letak berbeda.
    foreach ([route('beranda'), route('login'), route('pengaduan-warga')] as $alamat) {
        expect($this->get($alamat)->getContent())->toContain('theme.toggle');
    }

    expect($this->get('/alamat-tidak-ada')->getContent())->toContain('theme.toggle');
});

it('menyeimbangkan tag HTML pada seluruh berkas Blade', function () {
    // Uji ini lahir dari kegagalan nyata: penyuntingan berkas lewat pemotongan
    // indeks string meninggalkan </form> yatim dan dua </div> berlebih pada
    // layouts/app-header.blade.php. Akibatnya peramban menutup <header> lebih
    // awal dan seluruh konten terlempar keluar wadahnya.
    //
    // Yang membuatnya berbahaya: PHP, Blade, dan seluruh uji berbasis HTTP
    // tetap hijau, karena hanya peramban yang terdampak saat membangun DOM.
    $pelanggaran = [];

    foreach (BerkasBlade::semua() as $path) {
        $galat = BerkasBlade::periksaTag(file_get_contents($path));

        if ($galat !== []) {
            $pelanggaran[] = BerkasBlade::namaPendek($path) . ': ' . implode(', ', $galat);
        }
    }

    expect($pelanggaran)->toBe([]);
});

it('tidak memakai teks putih di atas permukaan yang terang', function () {
    // Uji ini lahir dari cacat nyata: nama sistem pada sidebar memakai
    // `text-white` tanpa pasangan mode terang, sementara sidebar berlatar
    // putih pada mode terang dan navy pada mode gelap. Akibatnya nama sistem
    // tidak terbaca sama sekali di mode terang (R-25 dan R-34).
    //
    // Cacat itu luput berbulan-bulan karena verifikasi hanya dilakukan pada
    // mode gelap, tempat teksnya kebetulan terbaca.
    $pelanggaran = [];

    // Latar berwarna pekat: teks putih di atasnya memang benar.
    $latarPekat = '/bg-(brand|navy|teal|gold|green|red|blue|error|success|warning|gray-[6-9]00|black)/';

    foreach (BerkasBlade::semua() as $path) {
        $isi = BerkasBlade::bersihkan(file_get_contents($path));
        preg_match_all('/class="([^"]*)"/', $isi, $cocok);

        foreach ($cocok[1] as $kelas) {
            $putihPolos = preg_match('/(^|\s)text-white(\s|$)/', $kelas);

            if (! $putihPolos) {
                continue;
            }

            // Aman bila latar pekat ditulis langsung pada kelas yang sama.
            if (preg_match($latarPekat, $kelas)) {
                continue;
            }

            // Aman bila sudah punya pasangan warna untuk mode terang.
            if (str_contains($kelas, 'dark:text-')) {
                continue;
            }

            // Aman bila latar disuntikkan lewat ekspresi Blade, contoh
            // `{{ $gayaTombol }}` yang berisi bg-red-600 atau bg-brand-500.
            // Kasus semacam ini tidak dapat dinilai dari kelasnya saja.
            if (str_contains($kelas, '{{')) {
                continue;
            }

            $pelanggaran[] = BerkasBlade::namaPendek($path) . ': ' . $kelas;
        }
    }

    expect($pelanggaran)->toBe([]);
});

it('memasangkan setiap latar terang dengan varian gelapnya', function () {
    // Latar terang tanpa pasangan dark: akan menghasilkan teks putih di atas
    // latar putih saat mode gelap aktif.
    $pelanggaran = [];

    foreach (BerkasBlade::semua() as $path) {
        $isi = BerkasBlade::bersihkan(file_get_contents($path));
        preg_match_all('/class="([^"]*)"/', $isi, $cocok);

        foreach ($cocok[1] as $kelas) {
            $latarTerang = preg_match('/(^|\s)bg-(white|gray-50|gray-100)(\s|$)/', $kelas);

            if ($latarTerang && ! str_contains($kelas, 'dark:bg-')) {
                $pelanggaran[] = BerkasBlade::namaPendek($path) . ': ' . $kelas;
            }
        }
    }

    expect($pelanggaran)->toBe([]);
});

it('tidak memakai lebar tetap yang berlaku pada layar sempit', function () {
    // Lebar tetap besar adalah penyebab paling sering gulir mendatar di ponsel
    // (agents/ui-spec.md bagian 8).
    //
    // Dua koreksi dari bentuk awal uji ini:
    // 1. SVG dibuang lebih dulu, karena koordinat path seperti "361" sempat
    //    terbaca sebagai angka lebar kelas.
    // 2. Hanya kelas TANPA prefix titik henti yang diperiksa. Kelas seperti
    //    `sm:w-[361px]` hanya aktif pada layar 640px ke atas, sehingga tidak
    //    pernah berlaku di layar 360px dan bukan pelanggaran.
    $pelanggaran = [];

    foreach (BerkasBlade::semua() as $path) {
        $isi = BerkasBlade::bersihkan(file_get_contents($path));
        preg_match_all('/class="([^"]*)"/', $isi, $cocok);

        foreach ($cocok[1] as $daftarKelas) {
            foreach (preg_split('/\s+/', $daftarKelas) as $kelas) {
                if (preg_match('/^w-\[(\d+)px\]$/', $kelas, $ukuran) && (int) $ukuran[1] > 360) {
                    $pelanggaran[] = BerkasBlade::namaPendek($path) . ': ' . $kelas;
                }
            }
        }
    }

    expect($pelanggaran)->toBe([]);
});

it('membungkus setiap tabel agar tidak meluber di layar sempit', function () {
    $pelanggaran = [];

    foreach (BerkasBlade::semua() as $path) {
        $isi = file_get_contents($path);

        // Tabel wajib berada dalam wadah bergulir, atau disertai tata letak
        // kartu untuk layar sempit lewat slot kartu.
        if (str_contains($isi, '<table')
            && ! str_contains($isi, 'overflow-x-auto')
            && ! str_contains($isi, 'slot:kartu')) {
            $pelanggaran[] = BerkasBlade::namaPendek($path);
        }
    }

    expect($pelanggaran)->toBe([]);
});

/*
|--------------------------------------------------------------------------
| Delivery Gate ANTISLOP
|--------------------------------------------------------------------------
*/

it('tidak memuat em dash pada teks antarmuka', function () {
    // R-02: em dash dilarang pada seluruh teks yang tampil di antarmuka.
    // Dokumen di folder agents dikecualikan (rules.md bagian 13.0).
    $pelanggaran = [];

    foreach (BerkasBlade::semua() as $path) {
        if (str_contains(BerkasBlade::bersihkan(file_get_contents($path)), "\u{2014}")) {
            $pelanggaran[] = BerkasBlade::namaPendek($path);
        }
    }

    expect($pelanggaran)->toBe([]);
});

it('tidak menyisakan teks bawaan template berbahasa Inggris', function () {
    // Komentar dibuang lebih dulu, karena komentar yang MENJELASKAN larangan
    // sebuah teks sempat terbaca sebagai pelanggaran larangan itu sendiri.
    $terlarang = ['Search or type', 'Back to Home Page', 'Card Title Here', 'Toggle Sidebar', 'Get Started'];
    $pelanggaran = [];

    foreach (BerkasBlade::semua() as $path) {
        $isi = BerkasBlade::bersihkan(file_get_contents($path));

        foreach ($terlarang as $teks) {
            if (str_contains($isi, $teks)) {
                $pelanggaran[] = BerkasBlade::namaPendek($path) . ': ' . $teks;
            }
        }
    }

    expect($pelanggaran)->toBe([]);
});

it('memakai CTA yang menyebut objeknya', function () {
    // R-15: tombol wajib menyebut objek yang dikenainya, bukan kata kerja
    // telanjang seperti "Simpan" saja.
    $isi = $this->get(route('transmigran.index'))->getContent();

    expect($isi)->toContain('Simpan Data Transmigran')
        ->and($isi)->toContain('Tambah Data Transmigran');

    expect($this->get(route('panen.index'))->getContent())
        ->toContain('Simpan Hasil Panen');
});

it('menampilkan penanda data contoh pada seluruh halaman petugas', function () {
    // R-17 dan R-38: angka contoh dilarang disajikan seolah data nyata.
    foreach ([
        route('beranda'),
        route('transmigran.index'),
        route('rumah.index'),
        route('lahan.index'),
        route('panen.index'),
        route('pengaduan.index'),
    ] as $alamat) {
        $this->get($alamat)->assertSee('Data contoh');
    }
});

/*
|--------------------------------------------------------------------------
| Navigasi jujur
|--------------------------------------------------------------------------
*/

it('menautkan modul kependudukan, lahan, dan panen satu sama lain', function () {
    // Operator kerap berpindah dari satu entitas ke entitas terkait; tanpa
    // tautan ini ia harus kembali ke daftar dan mencari ulang.
    $transmigran = $this->get(route('transmigran.detail', 1))->getContent();

    expect($transmigran)->toContain(route('rumah.detail', 1))
        ->and($transmigran)->toContain(route('lahan.detail', 1))
        ->and($transmigran)->toContain(route('panen.detail', 1));

    // Sebaliknya, rincian rumah dan lahan menaut balik ke pemiliknya.
    expect($this->get(route('rumah.detail', 1))->getContent())
        ->toContain(route('transmigran.detail', 1));

    expect($this->get(route('lahan.detail', 1))->getContent())
        ->toContain(route('transmigran.detail', 1));
});

it('menautkan halaman rincian SP ke seluruh modul terkait', function () {
    $isi = $this->get(route('dashboard.sp', 1))->getContent();

    expect($isi)->toContain(route('transmigran.detail', 1))
        ->and($isi)->toContain(route('rumah.detail', 1))
        ->and($isi)->toContain(route('lahan.detail', 1))
        ->and($isi)->toContain(route('panen.detail', 1))
        ->and($isi)->toContain(route('pengaduan.detail', 1));
});

it('menautkan isu prioritas dashboard ke rincian pengaduannya', function () {
    // Indikator ke-13 wajib dapat ditelusuri sampai ke laporan aslinya
    // (agents/ui-spec.md bagian 9).
    $this->get(route('beranda'))->assertSee(route('pengaduan.detail', 1), false);
});

it('menautkan setiap item menu sidebar ke halaman yang benar-benar ada', function () {
    // Uji ini lahir dari kegagalan gate: laporan menyatakan "0 tautan mati
    // dari 726", padahal 18 item menu sidebar membalas 404. Skrip gate saat
    // itu hanya memeriksa apakah href kosong atau bernilai '#', sehingga
    // tautan ke /poktan dianggap sah karena bentuknya benar, tanpa pernah
    // menanyakan apakah tujuannya ada.
    //
    // Ini pola kesalahan yang sama dengan dua cacat sebelumnya: memeriksa
    // BENTUK, bukan KENYATAAN. Karena itu uji ini membuka setiap tujuan
    // menu ke aplikasi sungguhan (R-24).
    $mati = [];

    foreach (App\Helpers\MenuHelper::definisiMenu() as $kelompok) {
        foreach ($kelompok['items'] as $item) {
            $path = $item['path'] ?? null;

            if ($path === null) {
                continue;
            }

            $status = $this->get($path)->getStatusCode();

            if ($status !== 200) {
                $mati[] = $item['name'] . ' (' . $path . ') -> ' . $status;
            }
        }
    }

    expect($mati)->toBe([]);
});

it('menautkan menu pengguna hanya ke halaman yang benar-benar ada', function () {
    $isi = $this->get(route('beranda'))->getContent();

    foreach ([route('profil'), route('profil.kata-sandi'), route('logout')] as $tujuan) {
        expect($isi)->toContain($tujuan);
    }
});

it('membuat rute profil dan kata sandi dapat diakses', function () {
    foreach (['profil', 'profil.kata-sandi', 'ganti-kata-sandi', 'login'] as $namaRute) {
        $this->get(route($namaRute))->assertOk();
    }
});

/*
|--------------------------------------------------------------------------
| Asap seluruh rute
|--------------------------------------------------------------------------
*/

it('merender setiap rute GET yang terdaftar tanpa galat', function () {
    // Uji-uji di atas menyebut halaman satu per satu, sehingga halaman yang
    // ditambahkan belakangan mudah luput dari pengujian tanpa ada yang
    // menyadarinya. Audit menemukan 19 dari 41 rute GET tidak pernah dibuka
    // satu uji pun.
    //
    // Uji ini membaca daftar rute langsung dari router, bukan dari daftar
    // yang ditulis tangan, sehingga rute baru otomatis ikut teruji.
    $lewati = [
        'up',              // health check bawaan Laravel
        'uji-403',         // sengaja mengembalikan 403
        'logout',          // mengubah keadaan, diuji terpisah
    ];

    $gagal = [];

    foreach (Route::getRoutes() as $rute) {
        if (! in_array('GET', $rute->methods(), true)) {
            continue;
        }

        $uri = $rute->uri();

        // Rute berparameter memerlukan nilai yang sahih, sedangkan uji ini
        // hanya menjamin halaman tanpa parameter dapat dibuka. Rute
        // berparameter sudah dicakup uji khusus per modul di atas.
        if (str_contains($uri, '{') || in_array($uri, $lewati, true)) {
            continue;
        }

        $status = $this->get('/' . ltrim($uri, '/'))->getStatusCode();

        if ($status !== 200) {
            $gagal[] = $uri . ' -> ' . $status;
        }
    }

    expect($gagal)->toBe([]);
});

it('memberi scope pada setiap header kolom tabel', function () {
    // Pembaca layar memakai scope untuk mengaitkan sel dengan headernya.
    // Tanpa itu, tabel padat pada dashboard terbaca sebagai deretan angka
    // tanpa makna. Aturan ini mudah tergeser karena header baru biasanya
    // disalin dari baris tetangga.
    $tanpaScope = [];

    foreach (BerkasBlade::semua() as $berkas) {
        // Komentar dokumentasi di dalam komponen memuat contoh markup yang
        // akan terbaca sebagai pelanggaran bila tidak dibersihkan lebih dulu.
        $isi = BerkasBlade::bersihkan(file_get_contents($berkas));

        if (preg_match('/<th(?![^>]*scope=)(\s[^>]*)?>/', $isi)) {
            $tanpaScope[] = BerkasBlade::namaPendek($berkas);
        }
    }

    expect($tanpaScope)->toBe([]);
});
