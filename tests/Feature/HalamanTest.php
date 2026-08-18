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

    // Sebelas wadah grafik memuat 17 indikator; sebagian indikator disajikan
    // sebagai kartu statistik dan tabel, sesuai pemetaan ui-spec.md bagian 9.
    foreach ([
        'grafikPenduduk', 'grafikKomoditas', 'grafikPendapatan', 'grafikMutasiKk',
        'grafikPenghuni', 'grafikPekerjaan', 'grafikPanen', 'grafikHarga',
        'grafikInfrastruktur', 'grafikStatusPengaduan',
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

it('menautkan rincian transmigran ke satuan permukimannya', function () {
    $this->get(route('transmigran.detail', 1))
        ->assertSee(route('dashboard.sp', 1), false);
});

it('menyediakan seluruh rute tulis modul transmigran', function () {
    // Setiap tombol pada antarmuka wajib punya tujuan nyata, bukan kontrol mati.
    // Halaman daftar memuat seluruh tindakan tulis: tambah lewat modal, ubah
    // lewat pola aksi per baris, dan hapus lewat kolom aksi.
    $isi = $this->get(route('transmigran.index'))->getContent();

    foreach ([
        route('transmigran.simpan'),
        route('transmigran.hapus', 1),
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
        'tahun_kedatangan', 'status_tinggal', 'telepon',
        'dokumen_pendukung', 'keterangan', 'satuan_permukiman_id',
    ] as $kolom) {
        expect($isi)->toContain('name="' . $kolom . '"');
    }

    // `status_anggota_poktan` sengaja BUKAN isian (rules.md 7a.8). Nilainya
    // turunan dari keanggotaan berstatus Aktif pada `anggota_poktan`, dan
    // menyediakannya sebagai pilihan Ya/Tidak di sini menciptakan dua sumber
    // kebenaran yang tidak pernah tersinkron: petugas dapat menyatakan "Ya"
    // tanpa seorang pun mendaftarkannya ke kelompok mana pun.
    expect($isi)->not->toContain('name="status_anggota_poktan"');
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

it('menyaring daftar lahan menurut peruntukan dan kategori', function () {
    $this->get(route('lahan.index', ['peruntukan_lahan' => 'Lahan Pekarangan']))
        ->assertOk()
        ->assertSee('LP-001')
        ->assertDontSee('LU-001');

    $this->get(route('lahan.index', ['peruntukan_lahan' => 'Lahan Usaha']))
        ->assertOk()
        ->assertSee('LU-001')
        ->assertDontSee('LP-001');

    $this->get(route('lahan.index', ['kategori_lahan' => 'Lahan Basah']))
        ->assertSee('LU-002');
});

it('menampilkan dokumen lahan pada tab tersendiri', function () {
    // Satu lahan dapat memiliki lebih dari satu dokumen, sehingga dokumen
    // dikelola terpisah dari form lahan (data-dictionary.md bagian 7.2).
    $isi = $this->get(route('lahan.detail', 1))
        ->assertOk()
        ->assertSee('HPL/NTT/2016/0142')
        ->getContent();

    // Sejak modal-form mendukung aksi dinamis, tujuan form disimpan pada
    // atribut Alpine dan dirender lewat @js(), sehingga garis miringnya
    // ter-escape menjadi \/. Yang diperiksa karena itu adalah tujuannya,
    // bukan bentuk penulisannya.
    expect(str_replace('\\/', '/', $isi))
        ->toContain(route('lahan.dokumen.simpan', 1));
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
    // Yang dijaga adalah baris totalnya terender, bukan kalimatnya. Mengunci
    // teks membuat penyuntingan wording memerahkan uji padahal tidak ada yang
    // rusak, sehingga penandanya yang diperiksa.
    foreach (['sp', 'komoditas', 'musim', 'petani'] as $kelompok) {
        $this->get(route('panen.rekap', ['kelompok' => $kelompok]))
            ->assertOk()
            ->assertSee('motif-baris-total', false);
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
    // Memeriksa penanda baris total, bukan kalimatnya. Lihat alasannya pada
    // uji rekap panen di atas.
    foreach (['kategori', 'status', 'sp', 'prioritas', 'bidang'] as $kelompok) {
        $this->get(route('pengaduan.rekap', ['kelompok' => $kelompok]))
            ->assertOk()
            ->assertSee('motif-baris-total', false);
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
    $diperiksa = 0;

    foreach (App\Helpers\MenuHelper::definisiMenu() as $kelompok) {
        foreach ($kelompok['items'] as $item) {
            // Sejak sidebar memakai submenu, sebagian besar path berada satu
            // tingkat lebih dalam. Menelusuri 'items' saja membuat uji ini
            // melewati hampir seluruh menu lalu lulus tanpa memeriksa apa pun.
            $tujuan = isset($item['subItems'])
                ? $item['subItems']
                : [$item];

            foreach ($tujuan as $t) {
                $path = $t['path'] ?? null;

                if ($path === null) {
                    continue;
                }

                $diperiksa++;
                $status = $this->get($path)->getStatusCode();

                if ($status !== 200) {
                    $mati[] = $t['name'] . ' (' . $path . ') -> ' . $status;
                }
            }
        }
    }

    expect($mati)->toBe([]);

    // Penjaga agar uji tidak diam-diam berhenti memeriksa ketika struktur
    // menu berubah lagi. Angkanya sengaja longgar, yang penting bukan nol.
    expect($diperiksa)->toBeGreaterThanOrEqual(20);
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

/*
|--------------------------------------------------------------------------
| Manajemen pengguna dan role
|--------------------------------------------------------------------------
*/

it('menyediakan seluruh modal manajemen pengguna', function () {
    $isi = $this->get(route('pengguna.index'))->getContent();

    foreach ([
        'formTambahPengguna',
        'buka-detail-pengguna',
        'buka-setel-sandi',
        'nonaktifkanPengguna',
    ] as $penanda) {
        expect($isi)->toContain($penanda);
    }
});

it('menyembunyikan tombol nonaktifkan pada admin aktif terakhir', function () {
    // rules.md 14b poin 16. Merender tombol lalu menolaknya di server berarti
    // memasang kontrol yang tidak berfungsi, yang dilarang R-26. Data contoh
    // hanya memuat satu akun Admin aktif, sehingga akun itu wajib dilindungi.
    $adminAktif = array_values(array_filter(
        DummyData::pengguna(),
        fn ($u) => $u['role'] === 'Admin' && $u['is_aktif'],
    ));

    expect($adminAktif)->toHaveCount(1);

    $isi = $this->get(route('pengguna.index'))->getContent();

    // Yang dijaga adalah tidak adanya jalur penonaktifan, bukan penandanya.
    // Sejak 2026-08-17 label "Admin terakhir" per baris dihapus; alasannya
    // dinyatakan sekali lewat keterangan di bawah tabel.
    expect($isi)->not->toContain('/pengguna/' . $adminAktif[0]['id_user'] . '/nonaktifkan')
        ->and($isi)->toContain('tidak memiliki tombol nonaktifkan');
});

it('tidak pernah menampilkan kolom kata sandi pada modal ubah pengguna', function () {
    // rules.md 14b poin 14. Sistem hanya menyimpan sidik, sehingga nilai lama
    // tidak dapat dibaca siapa pun. Menyediakan kolomnya akan menyiratkan
    // sebaliknya.
    //
    // Diperiksa langsung pada berkas Blade, bukan pada halaman terender,
    // sebab modal ubah baru muncul setelah baris tabel dipilih.
    $sumber = file_get_contents(resource_path('views/pages/pengguna/form.blade.php'));

    expect($sumber)->toContain("mode === 'tambah'")
        ->and($sumber)->toContain('Kata sandi tidak dapat disunting di sini');

    // Sejak 2026-08-14 kata sandi awal tidak lagi diketik Admin melainkan
    // dibuatkan sistem, sehingga tidak ada kolom isian kata sandi di mana pun
    // pada form ini, baik mode tambah maupun ubah.
    expect($sumber)->not->toContain('nama="password_awal"')
        ->and($sumber)->not->toContain('name="password_awal"');

    // Keterangan pembuatan kata sandi wajib berada di dalam cabang tambah,
    // sebab modal ubah tidak boleh menyinggung kata sandi sama sekali.
    $posisiCabang = strpos($sumber, "mode === 'tambah'");
    $posisiKeterangan = strpos($sumber, 'Kata sandi sementara dibuatkan sistem');

    expect($posisiKeterangan)->toBeGreaterThan($posisiCabang);
});

it('menampilkan pilihan penugasan SP hanya untuk role bercakupan Per SP', function () {
    // rules.md 14b poin 2. Pilihan dikendalikan Alpine lewat peta cakupan,
    // sehingga yang diuji adalah keberadaan pengendali itu, bukan sekadar
    // ada tidaknya daftar SP.
    $isi = $this->get(route('pengguna.index'))->getContent();

    expect($isi)->toContain('perluSp')
        ->and($isi)->toContain('Penugasan Satuan Permukiman');
});

it('merender matriks izin role sesuai tabel rules.md 5.1', function () {
    $isi = $this->get(route('pengaturan.role'))->getContent();

    // Jumlah kotak tercentang tiap role wajib sama dengan jumlah izinnya.
    foreach (DummyData::role() as $role) {
        $izin = DummyData::izinRole($role['id_role']);
        $jumlah = array_sum(array_map('count', $izin));

        expect($jumlah)->toBe($role['jumlah_izin']);
    }

    expect($isi)->toContain('Kewenangan per Fitur');
});

it('menyajikan role terkunci sebagai hanya baca', function () {
    // rules.md 5.0a. Role Admin tidak dapat dikurangi izinnya, sehingga
    // matriksnya dirender tanpa kotak centang sama sekali, bukan dengan
    // kotak yang tampak dapat diklik lalu ditolak diam-diam.
    $adminRole = collect(DummyData::role())->firstWhere('is_terkunci', true);

    expect($adminRole)->not->toBeNull();

    $isi = $this->get(route('pengaturan.role'))->getContent();

    expect($isi)->toContain('Role ini terkunci dan hanya dapat dilihat');

    // Modal role terkunci wajib tanpa kotak centang sama sekali.
    //
    // Titik potong memakai atribut id, bukan sekadar nama penandanya, sebab
    // nama itu muncul dua kali per modal: sekali pada aria-labelledby milik
    // pembungkus, sekali pada id judulnya. Memotong dari kemunculan pertama
    // menghasilkan potongan yang berhenti sebelum isi modal, sehingga uji
    // lulus tanpa memeriksa apa pun.
    $awal = strpos($isi, 'id="judul-formRole' . $adminRole['id_role'] . '"');
    $berikutnya = strpos($isi, 'id="judul-formRole', $awal + 20);
    $potongan = substr($isi, $awal, $berikutnya === false ? null : $berikutnya - $awal);

    expect($potongan)->not->toContain('name="izin[');

    // Jumlah tanda centang hanya-baca wajib sama dengan jumlah izin Admin,
    // sehingga matriks benar-benar menampilkan data, bukan tabel kosong.
    expect(substr_count($potongan, '&#10003;'))->toBe($adminRole['jumlah_izin']);
});

it('membuat rute tulis pengguna dan role mengembalikan redirect', function () {
    $this->post(route('pengguna.simpan'))->assertRedirect();
    $this->put(route('pengguna.perbarui', 1))->assertRedirect();
    $this->post(route('pengguna.setel-sandi', 1))->assertRedirect();
    $this->post(route('pengguna.nonaktifkan', 1))->assertRedirect();
    $this->post(route('role.simpan'))->assertRedirect();
    $this->put(route('role.perbarui', 2))->assertRedirect();
});

/*
|--------------------------------------------------------------------------
| Pemulihan kata sandi
|--------------------------------------------------------------------------
*/

it('merender halaman pemulihan kata sandi', function () {
    $this->get(route('lupa-kata-sandi'))->assertOk();
    $this->get(route('verifikasi-kode'))->assertOk();
});

it('tidak membocorkan apakah sebuah akun terdaftar', function () {
    // rules.md 14b poin 9. Pesan yang membedakan "terkirim" dan "tidak
    // ditemukan" mengubah halaman publik ini menjadi alat memeriksa siapa
    // saja yang memiliki akun dinas.
    $isi = $this->get(route('verifikasi-kode'))->getContent();

    expect($isi)->toContain('Bila alamat yang Anda masukkan terdaftar');

    foreach (['tidak ditemukan', 'tidak terdaftar', 'akun tidak ada'] as $bocor) {
        expect($isi)->not->toContain($bocor);
    }
});

it('menawarkan jalur admin pada setiap halaman pemulihan', function () {
    // rules.md 14b poin 11. Jalur Admin satu-satunya yang bekerja tanpa
    // sambungan surel, sehingga tidak boleh hilang dari tampilan ketika
    // jalur mandiri ditambahkan.
    //
    // Yang dijaga adalah keberadaan ajakan menghubungi admin, bukan kalimat
    // persisnya. Ketiga halaman menuliskannya dengan susunan berbeda sesuai
    // konteks masing-masing, dan penyuntingan teks tidak boleh membuat uji ini
    // gagal selama jalurnya masih ditawarkan.
    foreach ([route('lupa-kata-sandi'), route('verifikasi-kode'), route('login')] as $tujuan) {
        $isi = strtolower($this->get($tujuan)->getContent());

        expect($isi)->toContain('hubungi admin');
    }
});

it('mengirim kode enam digit, bukan tautan sekali klik', function () {
    $isi = $this->get(route('verifikasi-kode'))->getContent();

    expect($isi)->toContain('pattern="[0-9]{6}"')
        ->and($isi)->toContain('one-time-code')
        ->and($isi)->toContain('15 menit');
});

it('menautkan halaman masuk ke pemulihan kata sandi', function () {
    $isi = $this->get(route('login'))->getContent();

    expect($isi)->toContain(route('lupa-kata-sandi'))
        ->and($isi)->not->toContain('tidak menyediakan halaman pemulihan mandiri');
});

it('membuat rute tulis pemulihan mengembalikan redirect', function () {
    $this->post(route('lupa-kata-sandi.kirim'))->assertRedirect();
    $this->post(route('atur-ulang-sandi'))->assertRedirect();
});

/*
|--------------------------------------------------------------------------
| Konsistensi daftar izin dengan dokumen acuan
|--------------------------------------------------------------------------
*/

it('menyamakan daftar izin dengan kamus data dan rules', function () {
    // Uji ini lahir dari cacat nyata. Pembanding pertama hanya mengadu
    // daftarIzin() dengan rules.md 5.1, lalu menyatakan "cocok sempurna"
    // padahal modul fasilitas_sp sama sekali tidak ada di dalamnya.
    // Penyebabnya: rules.md sendiri menggabungkan Inventaris dan Fasilitas SP
    // menjadi satu baris, sementara kamus data memisahkannya.
    //
    // Memeriksa satu sumber memberi rasa aman yang keliru ketika sumbernya
    // sendiri belum sejalan. Karena itu uji ini mengadu dengan KEDUANYA.
    $aksiUrut = ['lihat', 'tambah', 'ubah', 'hapus'];
    $huruf = ['L' => 'lihat', 'T' => 'tambah', 'U' => 'ubah', 'H' => 'hapus'];

    $kamus = preg_split('/\r\n|\r|\n/', file_get_contents(base_path('agents/data-dictionary.md')));
    $rules = preg_split('/\r\n|\r|\n/', file_get_contents(base_path('agents/rules.md')));

    // Sumber 1: kamus 13.1, modul beserta aksi yang tersedia padanya.
    //
    // Pembacaan dibatasi pada wilayah tabel 13.1 saja. Sebelumnya pembatasnya
    // hanyalah "jumlah sel sama dengan jumlah aksi", dan itu bekerja secara
    // kebetulan selama tabel 13.1 punya 5 kolom. Ketika kewenangan `export`
    // dicabut dan kolomnya menjadi 4, pola yang sama mulai mencocoki 343 baris
    // tabel kolom database di seluruh kamus data, sebab bentuknya memang sama:
    // satu nama berbingkai backtick diikuti empat sel.
    //
    // Menambatkannya pada judul bagian membuat uji ini tidak lagi bergantung
    // pada kebetulan jumlah kolom.
    $awal = null;
    $akhir = null;
    foreach ($kamus as $i => $b) {
        if (str_starts_with(trim($b), '### 13.1 ')) {
            $awal = $i;

            continue;
        }
        if ($awal !== null && str_starts_with(trim($b), '### ')) {
            $akhir = $i;
            break;
        }
    }

    expect($awal)->not->toBeNull('Bagian "### 13.1" tidak ditemukan pada data-dictionary.md');

    $baris131 = array_slice($kamus, $awal, ($akhir ?? count($kamus)) - $awal);

    $modulKamus = [];
    foreach ($baris131 as $b) {
        if (! preg_match('/^\|\s*`([a-z_]+)`\s*\|(.+)\|\s*$/', trim($b), $m)) {
            continue;
        }
        $sel = array_map('trim', explode('|', $m[2]));
        if (count($sel) !== count($aksiUrut)) {
            continue;
        }
        $aksi = [];
        foreach ($sel as $i => $isi) {
            if ($isi !== '') {
                $aksi[] = $aksiUrut[$i];
            }
        }
        $modulKamus[$m[1]] = $aksi;
    }

    expect($modulKamus)->not->toBeEmpty();

    // Yang dipakai kode.
    $dariKode = [];
    foreach (DummyData::daftarIzin() as $kelompok) {
        foreach ($kelompok['modul'] as $modul) {
            $dariKode[$modul['kunci']] = $modul['aksi'];
        }
    }

    $selisih = [];

    foreach ($modulKamus as $kunci => $aksi) {
        if (! isset($dariKode[$kunci])) {
            $selisih[] = "modul '{$kunci}' ada di kamus 13.1 tetapi tidak di daftarIzin()";

            continue;
        }
        $a = $aksi;
        $b2 = $dariKode[$kunci];
        sort($a);
        sort($b2);
        if ($a !== $b2) {
            $selisih[] = "aksi '{$kunci}' berbeda antara kamus dan kode";
        }
    }

    foreach (array_keys($dariKode) as $kunci) {
        if (! isset($modulKamus[$kunci])) {
            $selisih[] = "modul '{$kunci}' ada di daftarIzin() tetapi tidak di kamus 13.1";
        }
    }

    // Sumber 2: rules 5.1, izin tiap role bawaan.
    $petaNama = [
        'Manajemen pengguna' => 'pengguna', 'Pengaturan role' => 'role', 'Audit log' => 'audit_log',
        'Data master wilayah' => 'wilayah', 'Kawasan transmigrasi' => 'kawasan',
        'Satuan permukiman (SP)' => 'sp', 'Inventaris SP' => 'inventaris_sp',
        'Fasilitas SP' => 'fasilitas_sp', 'Data master satuan' => 'satuan',
        'Transmigran' => 'transmigran', 'Rumah & hunian' => 'rumah',
        'Riwayat penghunian' => 'riwayat_penghunian', 'Lahan' => 'lahan',
        'Dokumen lahan (HPL/SHM)' => 'dokumen_lahan', 'Kelompok tani' => 'poktan',
        'Anggota poktan' => 'anggota_poktan', 'Alsintan' => 'alsintan', 'Saprotan' => 'saprotan',
        'Komoditas' => 'komoditas', 'Musim tanam' => 'musim_tanam',
        'Riwayat tanam' => 'riwayat_tanam', 'Hasil panen' => 'hasil_panen',
        'Infrastruktur SP' => 'infrastruktur', 'Pengaduan' => 'pengaduan',
        'Penanganan pengaduan' => 'penanganan_pengaduan', 'Dashboard' => 'dashboard',
    ];

    $izinRules = [];
    foreach ($rules as $b) {
        if (! str_starts_with(trim($b), '|')) {
            continue;
        }
        $sel = array_map('trim', explode('|', trim($b, " \t|")));
        if (count($sel) !== 5 || ! isset($petaNama[$sel[0]])) {
            continue;
        }
        for ($r = 1; $r <= 4; $r++) {
            $a = [];
            if ($sel[$r] !== '-' && $sel[$r] !== '') {
                foreach (preg_split('/\s+/', $sel[$r]) as $h) {
                    if (isset($huruf[$h])) {
                        $a[] = $huruf[$h];
                    }
                }
            }
            $izinRules[$r][$petaNama[$sel[0]]] = $a;
        }
    }

    expect($izinRules[1] ?? [])->toHaveCount(count($modulKamus));

    foreach ([1, 2, 3, 4] as $r) {
        $kode = DummyData::izinRole($r);

        foreach ($izinRules[$r] as $kunci => $harusnya) {
            $ada = $kode[$kunci] ?? [];
            sort($harusnya);
            sort($ada);
            if ($harusnya !== $ada) {
                $selisih[] = "role {$r} modul '{$kunci}' berbeda antara rules 5.1 dan kode";
            }
        }

        foreach (array_keys($kode) as $kunci) {
            if (! isset($izinRules[$r][$kunci])) {
                $selisih[] = "role {$r} modul '{$kunci}' ada di kode tetapi tidak di rules 5.1";
            }
        }
    }

    expect($selisih)->toBe([]);
});

it('mengelompokkan izin sesuai kamus data 13.2', function () {
    // Pengelompokan mengikuti urutan menu sidebar, sehingga admin menemukan
    // modul di tempat yang sama seperti ketika menavigasi sistem.
    $kamus = preg_split('/\r\n|\r|\n/', file_get_contents(base_path('agents/data-dictionary.md')));

    $kelompokKamus = [];
    foreach ($kamus as $b) {
        if (! preg_match('/^\|\s*([A-Za-z ]+)\s*\|\s*(`[a-z_]+`(?:,\s*`[a-z_]+`)*)\s*\|\s*$/', trim($b), $m)) {
            continue;
        }
        preg_match_all('/`([a-z_]+)`/', $m[2], $mm);
        $kelompokKamus[trim($m[1])] = $mm[1];
    }

    expect($kelompokKamus)->not->toBeEmpty();

    $kelompokKode = [];
    foreach (DummyData::daftarIzin() as $kelompok) {
        $kelompokKode[$kelompok['kelompok']] = array_column($kelompok['modul'], 'kunci');
    }

    $selisih = [];

    foreach ($kelompokKamus as $nama => $modul) {
        if (! isset($kelompokKode[$nama])) {
            $selisih[] = "kelompok '{$nama}' ada di kamus 13.2 tetapi tidak di daftarIzin()";

            continue;
        }
        $a = $modul;
        $b2 = $kelompokKode[$nama];
        sort($a);
        sort($b2);
        if ($a !== $b2) {
            $selisih[] = "isi kelompok '{$nama}' berbeda antara kamus dan kode";
        }
    }

    expect($selisih)->toBe([]);
});

it('mencatat jumlah izin yang sama dengan hasil hitungan', function () {
    // jumlah_izin dipakai halaman role sebagai ringkasan. Angka yang ditulis
    // manual mudah meleset tanpa ada yang menyadari: tiga dari empat angka
    // awal ternyata salah, dan koreksi pertamanya pun masih salah.
    foreach (DummyData::role() as $role) {
        $terhitung = array_sum(array_map('count', DummyData::izinRole($role['id_role'])));

        expect($role['jumlah_izin'])->toBe($terhitung);
    }
});

it('memisahkan inventaris dan fasilitas SP sebagai dua modul', function () {
    // Keduanya dua tabel, dua halaman, dan dua izin (rules.md 5.1 catatan 5).
    // Sempat tergabung menjadi satu modul, sehingga fasilitas_sp hilang dari
    // matriks izin tanpa ada yang menyadarinya.
    $kunci = [];
    foreach (DummyData::daftarIzin() as $kelompok) {
        $kunci = array_merge($kunci, array_column($kelompok['modul'], 'kunci'));
    }

    expect($kunci)->toContain('inventaris_sp')
        ->and($kunci)->toContain('fasilitas_sp');

    foreach ([1, 2, 3, 4] as $r) {
        $izin = DummyData::izinRole($r);

        expect($izin)->toHaveKey('inventaris_sp')
            ->and($izin)->toHaveKey('fasilitas_sp');
    }
});

/*
|--------------------------------------------------------------------------
| Kelengkapan form isian
|--------------------------------------------------------------------------
*/

it('menyediakan modal form pada setiap halaman daftar', function () {
    // Uji ini lahir dari celah nyata. Tahap 2 membangun 51 halaman, tetapi
    // form isian hanya dibuat untuk lima modul: Task 2.13 sampai 2.18 hanya
    // menulis "Membuat halaman", sehingga form-nya tidak pernah masuk lingkup
    // dan menyatu ke task CRUD Tahap 4 sampai 8.
    //
    // Akibatnya 14 halaman daftar hanya dapat dibaca tanpa ada yang
    // menyadarinya, sebab halaman baca-saja tidak pernah tampak rusak.
    // Daftar di bawah dibaca dari berkas, bukan ditulis tangan, agar halaman
    // daftar baru ikut terperiksa.
    $tanpaForm = [];

    foreach (BerkasBlade::semua() as $berkas) {
        $nama = BerkasBlade::namaPendek($berkas);
        $isi = file_get_contents($berkas);

        // Komponen bersama memuat contoh pemakaian pada komentarnya, sehingga
        // ikut tertangkap bila tidak dikecualikan.
        if (str_starts_with($nama, 'components/')) {
            continue;
        }

        // Hanya halaman daftar yang wajib punya tombol tambah.
        if (! str_contains($isi, '<x-sim.halaman-daftar')) {
            continue;
        }

        // Halaman rekap memang tidak menerima input; ia menampilkan agregat.
        if (str_contains($nama, 'rekap') || str_contains($nama, 'audit-log')) {
            continue;
        }

        if (! str_contains($isi, 'buka-modal')) {
            $tanpaForm[] = $nama;
        }
    }

    expect($tanpaForm)->toBe([]);
});

it('membuat setiap modal form menunjuk rute yang benar-benar ada', function () {
    // Modal yang aksinya menunjuk rute tak terdaftar akan gagal saat dikirim,
    // dan kegagalan itu baru terlihat ketika pengguna menekan Simpan.
    $namaRute = [];

    foreach (Route::getRoutes() as $rute) {
        if ($rute->getName() !== null) {
            $namaRute[] = $rute->getName();
        }
    }

    $mati = [];

    foreach (BerkasBlade::semua() as $berkas) {
        $isi = file_get_contents($berkas);

        preg_match_all('/:aksi="route\(\'([a-z0-9._-]+)\'/i', $isi, $cocok);

        foreach ($cocok[1] as $nama) {
            if (! in_array($nama, $namaRute, true)) {
                $mati[] = BerkasBlade::namaPendek($berkas) . ' -> ' . $nama;
            }
        }
    }

    expect($mati)->toBe([]);
});

it('menyediakan halaman rincian bagi modul yang dapat disunting', function () {
    // Pola baku sejak Task 2.7: Tambah di halaman daftar, Ubah di halaman
    // rincian. Modul tanpa rincian karena itu tidak punya tempat menaruh
    // tombol Ubah.
    foreach (['alsintan', 'saprotan', 'komoditas', 'infrastruktur'] as $modul) {
        $this->get('/' . $modul . '/1')->assertOk();
        $this->get('/' . $modul . '/999')->assertNotFound();
    }
});

it('menjaga aturan modul pada form yang mudah tergeser', function () {
    // Empat aturan yang tertulis di dokumen tetapi mudah hilang saat form
    // ditata ulang. Masing-masing punya alasan yang tidak terlihat dari
    // tampilannya sendiri.
    $sumber = fn (string $path) => file_get_contents(resource_path('views/pages/' . $path));

    // Anggota poktan ditandai keluar, bukan dihapus, agar catatan penyaluran
    // saprotan tetap memiliki penerima yang jelas.
    expect($sumber('poktan/form-anggota.blade.php'))
        ->toContain('Sudah Keluar')
        ->and($sumber('poktan/form-anggota.blade.php'))->not->toContain('name="hapus"');

    // Penyaluran saprotan hanya untuk anggota aktif.
    expect($sumber('saprotan/form.blade.php'))->toContain("'status'] === 'Aktif'");

    // Infrastruktur adalah pendataan aset, bukan pelaporan kerusakan.
    expect($sumber('infrastruktur/form.blade.php'))
        ->not->toContain('Lapor Kerusakan')
        ->and($sumber('infrastruktur/form.blade.php'))->toContain('fitur pengaduan');

    // Alsintan menampilkan pemilik bergantian, tidak pernah keduanya.
    expect($sumber('alsintan/form.blade.php'))->toContain('KepemilikanAlsintan::Pribadi->value');
});

it('memakai nilai enum pada data contoh, bukan teks yang menyerupainya', function () {
    // Data contoh alsintan sempat memakai 'Milik Pribadi' sedangkan enumnya
    // bernilai 'Pribadi', sehingga filter kepemilikan pada halaman daftar
    // tidak pernah cocok dan selalu menghasilkan nol baris. Cacat semacam ini
    // tidak terlihat pada tampilan biasa, hanya muncul ketika filter dipakai.
    $sah = array_column(App\Enums\KepemilikanAlsintan::cases(), 'value');

    foreach (DummyData::alsintan() as $baris) {
        expect($sah)->toContain($baris['kepemilikan']);
    }

    $kondisiSah = array_column(App\Enums\Kondisi::cases(), 'value');

    foreach (DummyData::alsintan() as $baris) {
        expect($kondisiSah)->toContain($baris['kondisi']);
    }
});

/*
|--------------------------------------------------------------------------
| Keterbacaan mode gelap dan akses berkas
|--------------------------------------------------------------------------
*/

it('memberi warna pada daftar dropdown di mode gelap', function () {
    // Uji ini lahir dari cacat nyata. Seluruh <select> memakai bg-transparent
    // agar menyatu dengan kartu di belakangnya. Pada mode gelap, daftar yang
    // TERBUKA dirender peramban memakai latar bawaannya yang putih, sementara
    // teksnya mewarisi warna terang. Akibatnya isi dropdown tidak terbaca.
    //
    // Luput lama karena verifikasi visual hanya melihat select dalam keadaan
    // tertutup. Yang tertutup tampak baik, sebab yang dirender saat itu adalah
    // kotaknya, bukan daftarnya.
    $css = file_get_contents(resource_path('css/app.css'));

    // Diperiksa sebagai blok aturan, bukan sekadar keberadaan teksnya.
    // Mencari '.dark select' saja tidak cukup: teks itu tetap ditemukan di
    // dalam '.dark select option', sehingga uji lulus meski aturan untuk
    // elemen select-nya sendiri sudah terhapus.
    expect($css)->toMatch('/\.dark\s+select\s*\{/')
        ->and($css)->toMatch('/\.dark\s+select\s+option\s*\{/');

    // Aturan wajib benar-benar menetapkan warna latar dan teks.
    preg_match('/\.dark\s+select\s*\{([^}]*)\}/', $css, $blok);

    expect($blok[1] ?? '')->toContain('background-color')
        ->and($blok[1] ?? '')->toContain('color:');
});

it('menyediakan cara membuka setiap berkas yang sudah diunggah', function () {
    // DokumenController beserta rutenya sudah lengkap sejak awal, tetapi tidak
    // satu pun halaman memakainya: nama berkas hanya ditampilkan sebagai teks.
    // Petugas karena itu tidak punya cara membuka dokumen yang diunggahnya
    // sendiri.
    $tanpaTautan = [];

    foreach (BerkasBlade::semua() as $berkas) {
        $nama = BerkasBlade::namaPendek($berkas);
        $isi = file_get_contents($berkas);

        if (str_starts_with($nama, 'components/')) {
            continue;
        }

        // Halaman yang menampilkan nama berkas wajib menyediakan tautannya.
        if (! str_contains($isi, 'basename(')) {
            continue;
        }

        if (! str_contains($isi, 'tautan-dokumen') && ! str_contains($isi, 'dokumen.tampilkan')) {
            $tanpaTautan[] = $nama;
        }
    }

    expect($tanpaTautan)->toBe([]);
});

it('tidak pernah menaut berkas lewat path penyimpanan', function () {
    // Berkas berada di luar folder public, sehingga path mentah tidak dapat
    // dibuka peramban sekaligus melewati pemeriksaan izin pada controller.
    $langsung = [];

    foreach (BerkasBlade::semua() as $berkas) {
        $isi = BerkasBlade::bersihkan(file_get_contents($berkas));

        if (preg_match('/href="\{\{\s*\$\w+\[.(dokumen_pendukung|file_dokumen|foto_rumah)/', $isi)) {
            $langsung[] = BerkasBlade::namaPendek($berkas);
        }
    }

    expect($langsung)->toBe([]);
});

it('menyusun sidebar sesuai kelompok yang disepakati', function () {
    // Susunan menu mengikuti cara petugas bekerja, bukan struktur tabel.
    // Dua penempatan yang mudah tergeser kembali: lahan berada di bawah
    // Kependudukan sebab selalu melekat pada satu kepala keluarga, dan
    // Infrastruktur SP di bawah Wilayah sebab asetnya milik SP, bukan poktan.
    $kelompok = array_column(App\Helpers\MenuHelper::definisiMenu(), 'title');

    expect($kelompok)->toBe([
        'Menu',
        'Transmigrasi',
        'Pertanian',
        'Pengaduan',
        'Administrasi Sistem',
    ]);

    $letak = [];

    foreach (App\Helpers\MenuHelper::definisiMenu() as $grup) {
        foreach ($grup['items'] as $item) {
            foreach ($item['subItems'] ?? [$item] as $sub) {
                if (isset($sub['path'])) {
                    $letak[$sub['path']] = $grup['title'];
                }
            }
        }
    }

    expect($letak['/lahan'] ?? null)->toBe('Transmigrasi')
        ->and($letak['/infrastruktur'] ?? null)->toBe('Transmigrasi')
        ->and($letak['/panen/rekap'] ?? null)->toBe('Pertanian');
});

it('menyaring submenu menurut izin, bukan hanya item induknya', function () {
    // Induk submenu tidak punya izinnya sendiri, sehingga kelayakannya
    // ditentukan submenu yang tersisa. Induk yang seluruh submenunya tersaring
    // akan membuka daftar kosong bila tetap dirender.
    foreach (App\Helpers\MenuHelper::getMenuGroups() as $grup) {
        foreach ($grup['items'] as $item) {
            if (isset($item['subItems'])) {
                expect($item['subItems'])->not->toBeEmpty();
            }
        }
    }
});

/*
|--------------------------------------------------------------------------
| Kelengkapan aksi baris dan kanal pengaduan
|--------------------------------------------------------------------------
*/

it('menyediakan tombol ubah pada setiap baris tabel yang dapat disunting', function () {
    // Sebelumnya hanya Hapus yang tersedia di baris, sementara Ubah harus
    // lewat halaman rincian. Susunan itu janggal: menghapus jauh lebih
    // berisiko daripada menyunting, tetapi justru lebih mudah dijangkau.
    $tanpaUbah = [];

    foreach (BerkasBlade::semua() as $berkas) {
        $nama = BerkasBlade::namaPendek($berkas);
        $isi = file_get_contents($berkas);

        if (str_starts_with($nama, 'components/') || str_contains($nama, 'galeri-komponen')) {
            continue;
        }

        // Hanya halaman DAFTAR yang diperiksa, dikenali dari perulangan baris
        // tabel. Halaman rincian menampilkan satu baris saja, sehingga tombol
        // ubahnya berada di kepala halaman, bukan di dalam tabel.
        //
        // Tidak memakai penanda <x-sim.halaman-daftar>: lima halaman utama
        // justru tidak memakainya, sehingga penyaringan seperti itu membuat
        // uji melewati modul yang paling perlu diperiksa.
        if (! str_contains($isi, '@foreach ($baris as')) {
            continue;
        }

        // Halaman daftar yang punya tombol hapus per baris wajib punya
        // tombol ubah juga. Menghapus jauh lebih berisiko daripada menyunting.
        if (! str_contains($isi, 'buka-konfirmasi')) {
            continue;
        }

        if (! str_contains($isi, 'buka-modal-baris')) {
            $tanpaUbah[] = $nama;
        }
    }

    expect($tanpaUbah)->toBe([]);
});

it('memakai satu modal ubah untuk seluruh baris, bukan satu per baris', function () {
    // Merender modal di dalam perulangan baris akan menggandakan form
    // sebanyak baris. Satu halaman berisi dua puluh baris berarti dua puluh
    // salinan isian yang sama.
    $isi = $this->get(route('transmigran.index'))->getContent();

    // Banyak tombol, tetapi hanya satu modal.
    expect(substr_count($isi, 'buka-modal-baris'))->toBeGreaterThan(1)
        ->and(substr_count($isi, 'id="judul-formUbahTransmigranBaris"'))->toBe(1);
});

it('mengisi aksi modal berbaris dari data baris yang diklik', function () {
    // Pola aksi memuat penanda :id yang diganti nilai sebenarnya saat modal
    // dibuka, sehingga satu modal cukup melayani seluruh baris.
    $isi = $this->get(route('transmigran.index'))->getContent();

    // Pola aksi dirender lewat @js(), sehingga garis miringnya ter-escape.
    // Yang diperiksa adalah polanya, bukan bentuk penulisannya.
    expect(str_replace('\\/', '/', $isi))->toContain('/transmigran/:id')
        ->and($isi)->toContain(':action="aksi"');
});

it('menyediakan surel opsional pada pengaduan warga', function () {
    // Surel adalah pelengkap, bukan syarat. Sistem tidak boleh bergantung
    // padanya: jaringan di lokus tidak selalu memadai dan sebagian warga
    // tidak memilikinya (rules.md 10b poin 1c-1).
    $isi = $this->get(route('pengaduan-warga'))->getContent();

    expect($isi)->toContain('name="email_pelapor"')
        ->and($isi)->toContain('boleh dikosongkan');

    // Kolom surel wajib TIDAK bertanda required.
    preg_match('/<input[^>]*id="email_pelapor"[^>]*>/', $isi, $cocok);

    expect($cocok[0] ?? '')->not->toContain('required');

    // Nomor HP tetap wajib, sebab itulah jalur yang paling terjangkau warga.
    preg_match('/<input[^>]*id="kontak_pelapor"[^>]*>/', $isi, $hp);

    expect($hp[0] ?? '')->toContain('required');
});

/*
|--------------------------------------------------------------------------
| Keseragaman navigasi dan kolom aksi
|--------------------------------------------------------------------------
*/

it('memakai ikon yang benar-benar terdaftar pada setiap menu', function () {
    // getIconSvg() mengembalikan ikon bintang untuk nama yang tidak dikenal.
    // Empat menu sempat memakai nama yang tidak ada dalam daftar, sehingga
    // keempatnya tampak seragam berbentuk bintang tanpa ada yang menyadari
    // bahwa itu sebenarnya penanda ikon yang hilang.
    $bintang = 'M12 2l3.09 6.26';
    $tanpaIkon = [];

    foreach (App\Helpers\MenuHelper::definisiMenu() as $kelompok) {
        foreach ($kelompok['items'] as $item) {
            $svg = App\Helpers\MenuHelper::getIconSvg($item['icon'] ?? '');

            if (str_contains($svg, $bintang)) {
                $tanpaIkon[] = $item['name'] . ' (' . ($item['icon'] ?? '-') . ')';
            }
        }
    }

    expect($tanpaIkon)->toBe([]);
});

it('memberi ikon berbeda pada setiap kelompok menu', function () {
    // Ikon yang berulang membuat kelompok sulit dibedakan sekilas.
    $ikon = [];

    foreach (App\Helpers\MenuHelper::definisiMenu() as $kelompok) {
        foreach ($kelompok['items'] as $item) {
            $ikon[] = $item['icon'] ?? '';
        }
    }

    expect($ikon)->toBe(array_unique($ikon));
});

it('membuka submenu pada halaman turunan, bukan hanya halaman daftarnya', function () {
    // Membuka /transmigran/1 berarti pengguna masih berada di dalam cakupan
    // menu Transmigran. Menutup submenunya membuat ia kehilangan jejak posisi.
    $isi = $this->get(route('transmigran.detail', 1))->getContent();

    expect($isi)->toContain("startsWith('transmigran/')");

    // Sorotan memakai jalur terpanjang yang cocok, agar membuka /sp/inventaris
    // tidak menyorot Satuan Permukiman sekaligus Inventaris SP.
    expect($isi)->toContain('semuaJalur');
});

it('tidak mempercayai lebar layar bernilai nol', function () {
    // Tab yang dibuka di latar belakang belum dilukis peramban, sehingga
    // innerWidth-nya sempat bernilai 0. Sidebar mengira layarnya sempit lalu
    // menyempit, dan tampilan baru pulih setelah halaman disegarkan.
    foreach (['layouts/app.blade.php', 'layouts/fullscreen-layout.blade.php'] as $berkas) {
        // TIDAK memakai BerkasBlade::bersihkan(), sebab pembantu itu membuang
        // seluruh isi <script> sementara justru di sanalah kode yang diperiksa
        // berada. Yang dibuang cukup komentarnya saja: penjelasan mengenai
        // cacat ini menyebut innerWidth dan akan terbaca sebagai pelanggaran.
        $isi = file_get_contents(resource_path('views/' . $berkas));
        $isi = preg_replace('#/\*[\s\S]*?\*/#', '', $isi);
        $isi = preg_replace('#//[^\n]*#', '', $isi);

        // Setiap pembacaan innerWidth wajib punya nilai cadangan, sebab tab
        // yang belum dilukis mengembalikan nol.
        preg_match_all('/window\.innerWidth(?![\s]*\|\|)/', $isi, $telanjang);

        expect($telanjang[0])->toBe([], $berkas . ' memakai innerWidth tanpa nilai cadangan');
    }
});

it('menyeragamkan kolom aksi berbentuk ikon di seluruh halaman daftar', function () {
    // Audit menemukan tiga tingkat ketidakkonsistenan sekaligus: kelengkapan
    // tindakan, bentuk ikon versus teks, dan delapan halaman tanpa kolom aksi
    // sama sekali. Petugas karena itu harus menebak letak dan bentuk tindakan
    // setiap kali berpindah modul.
    $bermasalah = [];

    foreach (BerkasBlade::semua() as $berkas) {
        $nama = BerkasBlade::namaPendek($berkas);
        $isi = file_get_contents($berkas);

        if (str_starts_with($nama, 'components/') || str_contains($nama, 'galeri-komponen')) {
            continue;
        }

        // Hanya halaman utuh yang diperiksa. Berkas form adalah potongan yang
        // di-include ke dalam modal, dan audit log memang hanya dibaca.
        if (str_contains($nama, '/form') || str_contains($nama, 'audit-log')) {
            continue;
        }

        // Halaman daftar dikenali dari perulangan baris tabelnya.
        if (! preg_match('/@foreach \(\$(baris|satuan|kawasan)\b/', $isi)) {
            continue;
        }

        // Setiap halaman daftar wajib menyediakan jalan menyunting barisnya.
        if (! str_contains($isi, 'buka-modal-baris') && ! str_contains($isi, 'aksi-baris')) {
            $bermasalah[] = $nama . ' (tanpa aksi baris)';
        }
    }

    expect($bermasalah)->toBe([]);
});

it('tidak meminta warga menilai prioritas laporannya sendiri', function () {
    // Warga tidak mengetahui skala prioritas dinas, dan meminta ia menilainya
    // membuat hampir seluruh laporan ditandai mendesak sehingga penandanya
    // kehilangan makna. Prioritas awal diturunkan dari kategori, lalu direvisi
    // petugas saat meninjau (rules.md 10b poin 6a dan 6b).
    //
    // Koordinat SEBELUMNYA juga tidak diminta di sini, tetapi keputusan itu
    // direvisi: koordinat kini diminta sebagai isian opsional, sebab titik
    // lokasi membantu petugas menemukan masalah tanpa bertanya ulang, dan
    // sifatnya yang opsional menjaga kanal ini tetap terbuka (poin 6c).
    $isi = $this->get(route('pengaduan-warga'))->getContent();

    expect($isi)->not->toContain('name="prioritas"');
});

it('memisahkan penanganan pengaduan dari penyuntingan datanya', function () {
    // Dua tindakan ini tercatat berbeda pada audit log. Menggabungkannya
    // berisiko status ikut berubah ketika petugas hanya membetulkan salah
    // ketik.
    $isi = $this->get(route('pengaduan.index'))->getContent();

    expect($isi)->toContain('buka-tangani-pengaduan')
        ->and($isi)->toContain('buka-modal-baris')
        ->and($isi)->toContain('hanya dapat maju satu langkah');
});

/*
|--------------------------------------------------------------------------
| Struktur tabel
|--------------------------------------------------------------------------
*/

it('menempatkan setiap sel tabel di dalam barisnya', function () {
    // Uji ini lahir dari cacat nyata. Penyisipan kolom aksi memakai nomor
    // baris yang meleset satu posisi, sehingga <td> berakhir SETELAH </tr>.
    // Peramban tidak dapat merender sel di luar baris, lalu melemparnya ke
    // luar tabel, dan tata letak halaman hancur.
    //
    // Cacat itu lolos dari uji keseimbangan tag, sebab <tr><td></td></tr><td></td>
    // seimbang secara pasangan: setiap tag punya penutupnya. Yang salah adalah
    // KEDUDUKANNYA, dan itu tidak pernah diperiksa.
    $bermasalah = [];

    foreach (BerkasBlade::semua() as $berkas) {
        $isi = BerkasBlade::bersihkan(file_get_contents($berkas));

        // <td> yang muncul tepat setelah </tr>, hanya dipisahkan spasi.
        if (preg_match('#</tr>\s*<td[\s>]#', $isi)) {
            $bermasalah[] = BerkasBlade::namaPendek($berkas);
        }
    }

    expect($bermasalah)->toBe([]);
});

it('menutup slot kepala setelah seluruh kolom judul', function () {
    // Header yang tersisip setelah </x-slot:kepala> tidak ikut masuk ke
    // <thead>, sehingga jumlah kolom judul dan kolom isi menjadi berbeda.
    $bermasalah = [];

    foreach (BerkasBlade::semua() as $berkas) {
        $isi = BerkasBlade::bersihkan(file_get_contents($berkas));

        if (! str_contains($isi, '</x-slot:kepala>')) {
            continue;
        }

        // Bagian setelah penutup slot tidak boleh memuat <th> lagi.
        [, $sesudah] = explode('</x-slot:kepala>', $isi, 2);

        // Batasi sampai sebelum slot berikutnya, agar tabel lain pada halaman
        // yang sama tidak ikut terbaca.
        $sesudah = explode('<x-slot:', $sesudah, 2)[0];

        if (preg_match('/<th[\s>]/', $sesudah)) {
            $bermasalah[] = BerkasBlade::namaPendek($berkas);
        }
    }

    expect($bermasalah)->toBe([]);
});

it('menyamakan jumlah kolom judul dengan jumlah sel pada halaman daftar', function () {
    // Pemeriksaan paling langsung: kolom yang tidak sejajar berarti tabelnya
    // tampak bergeser, sekalipun seluruh tagnya berpasangan dengan benar.
    $halaman = [
        'sp.index', 'sp.inventaris', 'sp.fasilitas', 'poktan.index',
        'musim-tanam', 'riwayat-tanam', 'master.satuan',
        'alsintan.index', 'saprotan.index', 'komoditas.index',
        'infrastruktur.index', 'pengguna.index',
        'transmigran.index', 'rumah.index', 'lahan.index',
        'panen.index', 'pengaduan.index',
    ];

    $bermasalah = [];

    foreach ($halaman as $namaRute) {
        $isi = $this->get(route($namaRute))->getContent();

        if (! preg_match('/<thead[\s\S]*?<\/thead>/', $isi, $kepala)) {
            continue;
        }

        $jumlahJudul = preg_match_all('/<th[\s>]/', $kepala[0]);

        // Baris data pertama di dalam tbody.
        if (! preg_match('/<tbody[\s\S]*?<tr[\s\S]*?<\/tr>/', $isi, $baris)) {
            continue;
        }

        $jumlahSel = preg_match_all('/<td[\s>]/', $baris[0]);

        if ($jumlahJudul !== $jumlahSel) {
            $bermasalah[] = $namaRute . ": {$jumlahJudul} judul, {$jumlahSel} sel";
        }
    }

    expect($bermasalah)->toBe([]);
});

it('merapatkan kolom aksi ke tepi kanan tabel', function () {
    // Tombol aksi yang menggantung di tengah kolom membuat mata harus
    // mencari-cari letaknya pada setiap baris.
    $isi = $this->get(route('wilayah'))->getContent();

    expect($isi)->toMatch('/text-right[^>]*>\s*Aksi/');
});

/*
|--------------------------------------------------------------------------
| Tata letak dan peta koordinat
|--------------------------------------------------------------------------
*/

it('mencegah konten meluber di samping sidebar', function () {
    // Sidebar berposisi fixed, sehingga ia keluar dari alur dan tidak memakan
    // ruang di dalam pembungkus flex. Tanpa min-w-0, flex-1 menghitung lebar
    // penuh layar lalu margin 290px menambahkannya lagi, sehingga lebar total
    // menjadi 100% + 290px dan konten paling kanan terpotong.
    //
    // Gejalanya paling terlihat pada tab yang dibuka lewat "buka di tab baru",
    // dan pulih setelah disegarkan, sehingga mudah dikira masalah pemuatan
    // padahal cacatnya ada pada tata letak.
    $isi = file_get_contents(resource_path('views/layouts/app.blade.php'));

    expect($isi)->toContain('min-w-0 flex-1');
});

it('memuat pustaka peta hanya ketika diperlukan', function () {
    // Leaflet berukuran sekitar 150 KB. Hanya enam form yang memerlukan peta,
    // sedangkan seluruh halaman lain tidak; menyertakannya pada bundel utama
    // membebani setiap halaman tanpa alasan.
    $appJs = file_get_contents(resource_path('js/app.js'));

    // Bundel utama tidak boleh mengimpor Leaflet secara statis.
    expect($appJs)->not->toContain("from 'leaflet'")
        ->and($appJs)->not->toContain('import leaflet');

    // Pemuatannya lewat impor dinamis pada modul terpisah.
    $petaJs = file_get_contents(resource_path('js/peta.js'));

    expect($petaJs)->toContain("import('leaflet')");
});

it('menyediakan pemilihan titik lewat peta pada setiap isian koordinat', function () {
    // GPS ponsel di lokus kerap meleset puluhan meter, sedangkan pengisi paling
    // mengetahui letak sebenarnya (rules.md 10b poin 6d).
    $isi = file_get_contents(resource_path('views/components/sim/koordinat-input.blade.php'));

    expect($isi)->toContain('Pilih di peta')
        ->and($isi)->toContain('Ambil lokasi saat ini')
        // Isian manual wajib tetap ada sebagai jalan terakhir.
        ->and($isi)->toContain('name="{{ $namaLintang }}"')
        // Kegagalan memuat peta tidak boleh menghentikan pengisian.
        ->and($isi)->toContain('petaGagal');
});

it('menampilkan tautan peta pada halaman yang memuat koordinat', function () {
    // Koordinat berupa angka tidak berarti apa-apa bagi petugas tanpa cara
    // melihatnya di peta.
    $halaman = [
        ['dashboard.sp', 1],
        ['rumah.detail', 1],
        ['lahan.detail', 1],
        ['pengaduan.detail', 1],
        ['poktan.detail', 1],
    ];

    $tanpaTautan = [];

    foreach ($halaman as [$rute, $id]) {
        if (! str_contains($this->get(route($rute, $id))->getContent(), 'Lihat di peta')) {
            $tanpaTautan[] = $rute;
        }
    }

    expect($tanpaTautan)->toBe([]);
});

it('tidak menampilkan tautan peta ketika koordinat kosong', function () {
    // Tombol yang tidak menuju ke mana pun dilarang (R-26). Pengaduan kedua
    // pada data contoh sengaja dibiarkan tanpa koordinat untuk menguji ini.
    $tanpaTitik = collect(DummyData::pengaduan())
        ->first(fn ($p) => empty($p['lintang']));

    expect($tanpaTitik)->not->toBeNull();

    expect($this->get(route('pengaduan.detail', $tanpaTitik['id_pengaduan']))->getContent())
        ->not->toContain('Lihat di peta');
});

it('menyediakan koordinat opsional pada pengaduan warga', function () {
    // Warga melapor lewat ponsel berjaringan terbatas, sehingga koordinat
    // membantu tetapi tidak boleh menghalangi pengiriman
    // (rules.md 10b poin 6c).
    $isi = $this->get(route('pengaduan-warga'))->getContent();

    expect($isi)->toContain('name="lintang"')
        ->and($isi)->toContain('Pilih di peta')
        ->and($isi)->toContain('boleh dikosongkan');

    preg_match('/<input[^>]*id="lintang"[^>]*>/', $isi, $cocok);

    expect($cocok[0] ?? '')->not->toContain('required');
});

it('melengkapi koordinat pada data contoh yang kamus datanya memuatnya', function () {
    // Tautan peta tidak pernah tampil bila datanya kosong, sehingga fiturnya
    // tampak tidak berfungsi padahal kodenya benar.
    $kosong = [];

    foreach (['lahan', 'rumah', 'poktan'] as $modul) {
        foreach (DummyData::{$modul}() as $baris) {
            if (empty($baris['lintang']) || empty($baris['bujur'])) {
                $kosong[] = $modul;

                break;
            }
        }
    }

    expect($kosong)->toBe([]);
});

/*
|--------------------------------------------------------------------------
| Ketahanan grafik pada tab latar belakang
|--------------------------------------------------------------------------
*/

it('membatasi lebar wadah grafik', function () {
    // Uji ini lahir dari cacat nyata yang bertahan setelah perbaikan pertama.
    // ApexCharts menghitung lebar kanvasnya sekali saat digambar, dari lebar
    // elemen wadahnya. Pada tab yang dibuka di latar belakang, peramban belum
    // melakukan layout sehingga lebar itu terbaca nol, lalu ApexCharts jatuh
    // ke lebar bawaannya yang jauh lebih besar.
    //
    // Tanpa pembatas, kanvas berlebih itu mendorong kartunya ikut melebar dan
    // merusak tata letak seluruh halaman. Pembatas ini penahan terakhir:
    // kartunya tetap utuh sekalipun grafiknya belum sempat menyesuaikan diri.
    $isi = file_get_contents(resource_path('views/components/sim/chart-card.blade.php'));

    expect($isi)->toContain('w-full max-w-full overflow-hidden');
});

it('menggambar ulang grafik ketika wadahnya benar-benar terlihat', function () {
    // Pembatas lebar hanya menahan tata letak; grafiknya sendiri tetap perlu
    // dihitung ulang agar isinya pas di dalam kartu.
    $isi = file_get_contents(resource_path('js/chart-config.js'));

    expect($isi)->toContain('IntersectionObserver')
        // Pengamat wajib dilepas setelah dipakai, agar tidak menumpuk pada
        // halaman yang memuat banyak grafik.
        ->and($isi)->toContain('pengamat.disconnect()');
});

it('membuat grafik menyesuaikan diri saat wadahnya berubah lebar', function () {
    // Pengaman berkelanjutan, bukan hanya saat pemuatan pertama: sidebar yang
    // dilipat dan jendela yang diubah ukurannya sama-sama mengubah lebar wadah.
    $isi = file_get_contents(resource_path('js/chart-config.js'));

    expect($isi)->toContain('redrawOnParentResize: true');
});

/*
|--------------------------------------------------------------------------
| Pengambilan lokasi di dalam modal peta
|--------------------------------------------------------------------------
*/

it('menyediakan tombol ambil lokasi di dalam modal peta pemilih', function () {
    // Tombol pengambilan lokasi sudah ada di luar modal, tetapi tidak dapat
    // dijangkau selagi peta terbuka: modal menutup seluruh layar. Petugas yang
    // sudah menggeser penanda lalu ingin kembali ke posisi sebenarnya terpaksa
    // menutup peta lebih dulu, dan itu memutus pekerjaannya di tengah jalan.
    $isi = file_get_contents(resource_path('views/components/sim/koordinat-input.blade.php'));

    // Kaki modal, tempat tombol Batal dan Gunakan Titik Ini berada.
    $kakiModal = substr($isi, strrpos($isi, 'border-t border-gray-200'));

    expect($kakiModal)->toContain('ambilLokasi()');
});

it('menampilkan galat lokasi di dalam modal peta yang sedang terbuka', function () {
    // Pesan galat yang berada di luar modal terhalang lapisan penutup layar.
    // Tanpa salinan di dalam, izin lokasi yang ditolak tidak menghasilkan
    // tanda apa pun dan tombolnya tampak rusak.
    $isi = file_get_contents(resource_path('views/components/sim/koordinat-input.blade.php'));

    $isiModal = substr($isi, strpos($isi, 'Modal peta pemilih titik'));

    expect($isiModal)->toContain('x-text="galat"');
});

it('tidak menaruh tombol ambil lokasi pada modal peta baca-saja', function () {
    // Komponen ini dipakai di halaman rincian, tempat koordinat hanya DILIHAT.
    // Titiknya milik lahan atau rumah yang sedang dibuka, bukan milik pengguna,
    // sehingga tombol yang memindahkannya akan mengubah data yang seharusnya
    // tidak dapat disunting dari sana.
    $isi = file_get_contents(resource_path('views/components/sim/tautan-peta.blade.php'));

    expect($isi)->not->toContain('ambilLokasi');
});

/*
|--------------------------------------------------------------------------
| Pengelompokan dashboard
|--------------------------------------------------------------------------
*/

it('mengelompokkan visualisasi dashboard ke dalam bagian bertajuk', function () {
    // Dashboard memuat 12 grafik, 8 kartu, dan 2 tabel besar. Tanpa tajuk
    // pemisah seluruhnya terbaca sebagai satu tumpukan panjang, dan pembaca
    // tidak dapat membangun gambaran utuh tentang satu topik pun.
    $isi = $this->get(route('beranda'))->getContent();

    foreach ([
        'Ringkasan Kawasan', 'Kependudukan', 'Pertanian dan Ekonomi',
        'Infrastruktur dan Layanan', 'Perbandingan Antar Satuan Permukiman',
    ] as $tajuk) {
        expect($isi)->toContain($tajuk);
    }
});

it('mengurutkan bagian dashboard menurut topik, bukan nomor indikator', function () {
    // Inti perbaikannya justru pada urutan. Sebelumnya grafik disusun menurut
    // nomor indikator PRD, sehingga pembaca dilempar dari penduduk ke
    // pertanian, kembali ke penduduk, lalu ke pengaduan. Topik pertanian
    // bahkan terpecah di tiga tempat yang berjauhan.
    $isi = $this->get(route('beranda'))->getContent();

    // Dicocokkan pada tajuk <h2> saja. Menu sidebar memuat "Rekap
    // Kependudukan", yang akan tertangkap lebih dulu bila seluruh halaman
    // yang dicari, dan urutannya jadi salah dibaca.
    preg_match_all('/<h2[^>]*>([^<]+)<\/h2>/', $isi, $cocok);
    $tajuk = array_map('trim', $cocok[1]);

    expect($tajuk)->toBe([
        'Ringkasan Kawasan',
        'Kependudukan',
        'Pertanian dan Ekonomi',
        'Infrastruktur dan Layanan',
        'Perbandingan Antar Satuan Permukiman',
    ]);
});

it('mengumpulkan grafik pertanian dalam satu bagian yang sama', function () {
    // Sebelumnya Sebaran Komoditas berada di posisi kedua, sedangkan Volume
    // Panen dan Harga Jual di posisi kedelapan dan kesembilan: terpisah enam
    // kartu, sehingga ketiganya tidak pernah terbaca sebagai satu pokok.
    $isi = $this->get(route('beranda'))->getContent();

    $awal = strpos($isi, 'Pertanian dan Ekonomi');
    $akhir = strpos($isi, 'Infrastruktur dan Layanan');
    $bagian = substr($isi, $awal, $akhir - $awal);

    foreach (['grafikPanen', 'grafikKomoditas', 'grafikHarga', 'grafikPendapatan'] as $id) {
        expect($bagian)->toContain('id="' . $id . '"');
    }
});

it('menjaga hierarki tajuk dashboard tidak melompat', function () {
    // Judul halaman memakai <h1> dan judul kartu grafik memakai <h3>. Tanpa
    // lapisan <h2> di antaranya, pembaca layar kehilangan penanda pindah
    // bagian dan urutan tajuknya melompat.
    $isi = $this->get(route('beranda'))->getContent();

    expect($isi)->toContain('<h2');
});

/*
|--------------------------------------------------------------------------
| Impor data massal
|--------------------------------------------------------------------------
*/

it('menyediakan tombol impor pada modul berdata banyak', function (string $url, string $namaModal) {
    // PRD 8.1 mewajibkan template luring: sinyal di lokus tidak selalu stabil,
    // sehingga petugas mengunduh template, mengisinya di lapangan, lalu
    // mengunggahnya saat sambungan tersedia. Tanpa jalur ini, pendataan
    // ratusan kepala keluarga harus dikerjakan satu per satu sambil daring.
    $isi = $this->get($url)->assertOk()->getContent();

    // Diperiksa keduanya: tombol pemicunya DAN komponen modalnya. Memeriksa
    // nama modal saja tidak cukup, sebab nama itu juga muncul pada komponen
    // modalnya sendiri, sehingga tombol yang hilang tetap lolos.
    expect($isi)->toContain("buka-modal', '" . $namaModal . "')")
        ->and($isi)->toContain('Impor Data')
        ->and($isi)->toContain('judul-' . $namaModal);
})->with([
    ['/transmigran', 'imporTransmigran'],
    ['/rumah', 'imporRumah'],
    ['/lahan', 'imporLahan'],
    ['/panen', 'imporPanen'],
    ['/riwayat-tanam', 'imporRiwayatTanam'],
    ['/infrastruktur', 'imporInfrastruktur'],
    ['/sp/inventaris', 'imporInventaris'],
    ['/wilayah', 'imporWilayah'],
    ['/master/satuan', 'imporSatuan'],
    ['/poktan', 'imporPoktan'],
    ['/alsintan', 'imporAlsintan'],
    ['/saprotan', 'imporSaprotan'],
    ['/sp/fasilitas', 'imporFasilitas'],
    ['/komoditas', 'imporKomoditas'],
]);

it('tidak menyediakan impor pada modul yang tidak boleh diisi massal', function (string $url) {
    // Pengecualian ini disengaja dan punya alasan masing-masing:
    //
    // - Pengaduan datang dari kanal publik satu per satu, dan nomornya wajib
    //   memuat bagian acak (rules.md 10b poin 4) sehingga tidak dapat
    //   disiapkan lebih dulu di dalam berkas Excel.
    // - Pengguna: kata sandi awal diserahkan langsung kepada orangnya
    //   (rules.md 14b poin 3). Impor massal berarti kata sandi berkeliaran
    //   di dalam berkas yang berpindah tangan.
    // - Role, Kawasan, SP, dan Musim Tanam jumlah barisnya sedikit dan jarang
    //   berubah, sehingga impor hanya menambah jalur masuk tanpa manfaat.
    $isi = $this->get($url)->assertOk()->getContent();

    expect($isi)->not->toContain("buka-modal', 'impor");
})->with([
    '/pengaduan',
    '/pengguna',
    '/pengaturan/role',
    '/kawasan',
    '/sp',
    '/musim-tanam',
]);

it('memandu impor lewat tiga langkah beserta kolom wajibnya', function () {
    // Alurnya dipecah agar tiap tahap punya satu pekerjaan saja. Langkah
    // ketiga yang paling menentukan: impor yang hanya berkata "gagal"
    // memaksa petugas menebak barisnya, padahal berkas berisi ratusan baris
    // tidak mungkin diperiksa manual.
    $isi = $this->get('/transmigran')->getContent();

    expect($isi)->toContain('Unduh template')
        ->and($isi)->toContain('Unggah berkas')
        ->and($isi)->toContain('Baris yang perlu diperbaiki')
        // Kolom wajib ditampilkan agar petugas tahu isian apa yang diperlukan
        // sebelum berangkat ke lapangan.
        ->and($isi)->toContain('nama_lengkap');
});

it('menyatakan terus terang bahwa impor belum tersambung backend', function () {
    // Tombolnya terlihat berfungsi penuh padahal penyimpanannya belum ada.
    // Tanpa peringatan ini petugas dapat mengira datanya sudah masuk, lalu
    // kehilangan hasil pendataan sehari penuh.
    $this->get('/transmigran')->assertSee('Fitur belum aktif.');
});

it('menyediakan rute unduh template untuk seluruh entitas', function () {
    // Satu rute melayani semua entitas, sebab yang membedakan hanya susunan
    // kolomnya. Empat belas rute terpisah hanya akan menyalin penanganan
    // yang sama empat belas kali.
    $this->get(route('template-impor', 'transmigran'))->assertRedirect();
    $this->get(route('template-impor', 'hasil-panen'))->assertRedirect();
});

/*
|--------------------------------------------------------------------------
| Akun dan role
|--------------------------------------------------------------------------
*/

it('menyediakan jalur mengaktifkan kembali akun yang dinonaktifkan', function () {
    // Cacat nyata: akun nonaktif sebelumnya terkunci selamanya. Tombol
    // nonaktifkan hanya dirender untuk akun aktif, dan tidak ada cabang bagi
    // akun yang sudah mati, padahal akun memang tidak pernah dihapus.
    $isi = $this->get(route('pengguna.index'))->assertOk()->getContent();

    $nonaktif = collect(DummyData::pengguna())->firstWhere('is_aktif', false);

    expect($nonaktif)->not->toBeNull()
        ->and($isi)->toContain('Aktifkan kembali akun ' . $nonaktif['nama'])
        ->and($isi)->toContain('/pengguna/' . $nonaktif['id_user'] . '/aktifkan');
});

it('tidak menawarkan pengaktifan pada akun yang sudah aktif', function () {
    // Kontrol yang tidak menuju ke mana pun dilarang (R-26): akun aktif tidak
    // boleh punya tombol aktifkan.
    $isi = $this->get(route('pengguna.index'))->getContent();

    foreach (collect(DummyData::pengguna())->where('is_aktif', true) as $akun) {
        expect($isi)->not->toContain('Aktifkan kembali akun ' . $akun['nama']);
    }
});

it('tetap melindungi admin aktif terakhir dari penonaktifan', function () {
    // Penambahan tombol aktifkan tidak boleh melemahkan perlindungan yang
    // sudah ada (rules.md 14b poin 16).
    $isi = $this->get(route('pengguna.index'))->getContent();

    expect($isi)->not->toContain('Nonaktifkan akun SITI RAHMAWATI')
        ->and($isi)->toContain('tidak memiliki tombol nonaktifkan');
});

it('tidak menyediakan izin hapus pada modul pengguna', function () {
    // Akun tidak pernah dihapus, hanya dinonaktifkan. Menawarkan kotak centang
    // bagi kewenangan yang mustahil dijalankan menyesatkan admin penyusun role.
    $modul = collect(DummyData::daftarIzin())
        ->flatMap(fn ($kelompok) => $kelompok['modul'])
        ->keyBy('kunci');

    expect($modul['pengguna']['aksi'])->not->toContain('hapus')
        // Role JUSTRU boleh dihapus selama bukan bawaan dan tidak dipakai
        // akun mana pun (rules.md 5.0c poin 9), jadi izinnya tetap ada.
        ->and($modul['role']['aksi'])->toContain('hapus');
});

it('membuat akun baru tanpa isian username dan tanpa toggle aktif', function () {
    // Username dibuat petugas sendiri saat pertama kali masuk, sebab dialah
    // yang akan mengetiknya setiap hari. Aktif/nonaktif hanya lewat tombol
    // pada halaman daftar, agar riwayat audit tidak terpecah dua jalur.
    $isi = $this->get(route('pengguna.index'))->getContent();

    expect($isi)->not->toContain('name="username"')
        ->and($isi)->not->toContain('name="is_aktif"')
        ->and($isi)->toContain('Username dibuat petugas');
});

it('mewajibkan surel pada akun baru', function () {
    // Konsekuensi username dibuat petugas: surel menjadi satu-satunya
    // kredensial yang dimilikinya saat pertama kali masuk.
    expect($this->get(route('pengguna.index'))->getContent())
        ->toContain('name="email" required');
});

it('membuatkan kata sandi sementara alih-alih meminta admin mengetiknya', function () {
    // Kata sandi karangan manusia cenderung berpola dan dipakai ulang untuk
    // banyak akun sekaligus.
    $isi = $this->get(route('pengguna.index'))->getContent();

    expect($isi)->not->toContain('name="password_awal"')
        ->and($isi)->toContain('Kata sandi sementara dibuatkan sistem');
});

it('menyatakan terus terang bahwa pengiriman email belum aktif', function () {
    // Tampilannya sudah lengkap, tetapi pengirimannya menunggu backend. Tanpa
    // keterangan ini admin dapat mengira petugas sudah menerima emailnya, lalu
    // tidak menyerahkan kata sandi secara langsung.
    //
    // Spanduk kredensial hanya muncul SETELAH akun dibuat, sehingga sesinya
    // perlu diisi lebih dulu. Membuka halaman biasa tidak akan pernah
    // menampilkannya, dan uji yang tidak menyadarinya akan lulus tanpa
    // benar-benar memeriksa apa pun.
    $isi = $this->withSession(['kredensial_baru' => [
        'nama' => 'PETUGAS UJI',
        'email' => 'petugas.uji@malakakab.go.id',
        'password' => 'Tmg-7K4pQ2',
    ]])->get(route('pengguna.index'))->assertOk()->getContent();

    expect($isi)->toContain('belum aktif')
        // Sekaligus menjaga istilahnya, sebab teks ini termasuk yang dilihat
        // pengguna (ui-spec.md 10.1).
        ->and(mb_strtolower($isi))->not->toContain('surel');
});

it('menampilkan tombol hapus hanya pada role yang memang dapat dihapus', function () {
    // Role bawaan dan role yang masih dipakai akun tidak boleh dihapus
    // (rules.md 5.0c poin 8 dan 9). Merender tombol lalu menolaknya di server
    // berarti memasang kontrol mati.
    $isi = $this->get(route('pengaturan.role'))->assertOk()->getContent();

    $dapatDihapus = collect(DummyData::role())
        ->filter(fn ($r) => ! $r['is_bawaan'] && $r['jumlah_pengguna'] === 0);

    expect($dapatDihapus)->not->toBeEmpty();

    foreach ($dapatDihapus as $role) {
        expect($isi)->toContain('/pengaturan/role/' . $role['id_role']);
    }

    foreach (collect(DummyData::role())->where('is_bawaan', true) as $role) {
        expect($isi)->not->toContain("aksi: '/pengaturan/role/" . $role['id_role'] . "'");
    }
});

it('menyediakan contoh role buatan admin, bukan hanya bawaan sistem', function () {
    // Tanpa satu pun role buatan sendiri, keadaan "dapat dihapus" tidak pernah
    // terlihat pada antarmuka dan bentuk tampilannya tidak dapat dinilai.
    $buatanSendiri = collect(DummyData::role())->where('is_bawaan', false);

    expect($buatanSendiri)->not->toBeEmpty();

    foreach ($buatanSendiri as $role) {
        expect(DummyData::izinRole($role['id_role']))->not->toBeEmpty();
    }
});

/*
|--------------------------------------------------------------------------
| Pengaduan: prioritas, dokumen, dan penanganan
|--------------------------------------------------------------------------
*/

it('menyerahkan penentuan prioritas sepenuhnya kepada petugas', function () {
    // Penurunan otomatis dari kategori dibatalkan pada 2026-08-14. Kategori
    // hanya menyatakan pokok masalah, sedangkan kegentingan bergantung pada
    // keadaan lapangan yang tidak terbaca dari kategori: dua laporan
    // berkategori sama dapat berbeda jauh kemendesakannya.
    expect(method_exists(PrioritasPengaduan::class, 'dariKategori'))->toBeFalse();

    // Form petugas tetap menyediakan pilihannya, sebab dialah yang menilai.
    expect($this->get(route('pengaduan.index'))->getContent())
        ->toContain('name="prioritas"');
});

it('menampilkan dokumen tindak lanjut pada riwayat penanganan', function () {
    // Modal penanganan sudah lama menyediakan isian unggahnya, tetapi hasilnya
    // tidak pernah ditampilkan kembali sehingga berkas yang sudah diunggah
    // petugas tidak dapat dibuka siapa pun.
    $riwayat = DummyData::penangananPengaduan('PGD-2026-0001');

    $berdokumen = collect($riwayat)->firstWhere('dokumen_tindak_lanjut', '!=', null);

    expect($berdokumen)->not->toBeNull();

    $this->get(route('pengaduan.detail', 1))
        ->assertOk()
        ->assertSee(basename($berdokumen['dokumen_tindak_lanjut']));
});

it('memberitahu warga adanya dokumen tanpa membuka berkasnya', function () {
    // Halaman lacak terbuka tanpa login dan hanya berbekal nomor pengaduan,
    // sehingga siapa pun yang mengetahui nomornya akan ikut memperoleh
    // berkasnya. Dokumen tindak lanjut kerap memuat nama petugas dan hasil
    // peninjauan.
    $isi = $this->get(route('lacak-pengaduan', ['nomor' => 'PGD-2026-0001']))
        ->assertOk()
        ->getContent();

    expect($isi)->toContain('melampirkan dokumen tindak lanjut')
        // Berkasnya sendiri tidak boleh dapat diunduh dari sini.
        ->and($isi)->not->toContain('BeritaAcaraPeninjauan');
});

it('menyediakan satu tombol penanganan saja pada halaman rincian pengaduan', function () {
    // Sebelumnya tombol yang sama dirender dua kali: di kepala halaman dan di
    // kolom kiri. Yang dipertahankan adalah yang berdampingan dengan stepper
    // alur, sebab di sanalah petugas melihat konteks tahapnya.
    $isi = $this->get(route('pengaduan.detail', 1))->getContent();

    expect(substr_count($isi, "buka-modal', 'formPenanganan'"))->toBe(1);
});

it('menyamakan isian penanganan pada daftar dengan yang di halaman rincian', function () {
    // Dua modal untuk satu tindakan yang sama tidak boleh meminta hal berbeda,
    // sebab jejak yang dihasilkannya akan timpang: sebagian bertanggal dan
    // berdokumen, sebagian tidak.
    $isi = $this->get(route('pengaduan.index'))->getContent();

    foreach (['tanggal_penanganan', 'catatan', 'dokumen_tindak_lanjut', 'status_sesudah'] as $isian) {
        expect($isi)->toContain('name="' . $isian . '"');
    }

    // Unggahan berkas mustahil terkirim tanpa enctype, dan kegagalannya
    // berlangsung diam-diam.
    expect($isi)->toContain('enctype="multipart/form-data"');
});

it('menyediakan penyalinan nomor pengaduan bagi warga', function () {
    // Nomor pengaduan adalah satu-satunya bekal warga untuk melacak laporannya,
    // sedangkan mengetik ulang sederet nomor di ponsel mudah keliru.
    $isi = $this->get(route('pengaduan-warga'))->getContent();

    // Panel nomor hanya muncul setelah pengiriman, sehingga yang diperiksa di
    // sini adalah berkas sumbernya.
    $sumber = file_get_contents(resource_path('views/pages/publik/pengaduan.blade.php'));

    expect($sumber)->toContain('navigator.clipboard')
        ->and($sumber)->toContain('Ketuk nomor untuk menyalin')
        // Menyalin hanya menaruh nomor di papan klip yang mudah tertimpa,
        // sehingga ajakan mencatat tetap wajib ada. Dicocokkan pada intinya
        // saja, bukan kalimat penuh, agar wording bebas disunting.
        ->and($isi)->toContain('Catat atau foto');
});

it('mengarahkan tombol lacak ke nomor yang benar-benar ada', function () {
    // Sebelumnya rute kirim membalas PGD-2026-0006 yang tidak pernah ada pada
    // data contoh, sehingga tombol "Lihat Perkembangan Laporan" selalu berujung
    // pada keadaan nomor tidak ditemukan. Kontrol semacam itu dilarang (R-26).
    $nomorTersedia = collect(DummyData::pengaduan())->pluck('nomor_pengaduan')->all();

    $balasan = $this->post(route('pengaduan-warga.kirim'), []);
    $nomor = session('nomor_pengaduan');

    expect($nomor)->toBeIn($nomorTersedia);

    // Dan nomor itu memang menghasilkan halaman lacak yang berisi.
    $this->get(route('lacak-pengaduan', ['nomor' => $nomor]))
        ->assertOk()
        ->assertDontSee('tidak ditemukan');
});

/*
|--------------------------------------------------------------------------
| Catatan log pada halaman rincian
|--------------------------------------------------------------------------
*/

it('menyaring riwayat memakai nama tabel DAN nomor barisnya', function () {
    // Keduanya wajib dipakai bersama. Menyaring nama tabel saja membuat setiap
    // baris menampilkan riwayat baris lain pada tabel yang sama, sehingga
    // pembaca mengira datanya pernah diubah padahal tidak.
    $riwayat = DummyData::riwayatData('transmigran', 1);

    expect($riwayat)->not->toBeEmpty();

    foreach ($riwayat as $jejak) {
        expect($jejak['nama_tabel'])->toBe('transmigran')
            ->and((int) $jejak['record_id'])->toBe(1);
    }

    // Baris lain pada tabel yang sama tidak boleh ikut terbawa.
    $nomorLain = collect(DummyData::riwayatData('transmigran', 4))->pluck('id_audit_log');
    $nomorIni = collect($riwayat)->pluck('id_audit_log');

    expect($nomorIni->intersect($nomorLain))->toBeEmpty();
});

it('mengurutkan riwayat data dari yang terbaru', function () {
    // Yang pertama dicari pembaca biasanya perubahan terakhir, bukan asal-usul
    // datanya.
    $waktu = collect(DummyData::riwayatData('transmigran', 1))->pluck('waktu')->all();

    $urut = $waktu;
    rsort($urut);

    expect($waktu)->toBe($urut);
});

it('menyediakan tab catatan log pada setiap halaman rincian utama', function (string $url, string $namaTabel, int $recordId) {
    // Pertanyaan "siapa yang memasukkan data ini dan siapa yang mengubahnya"
    // dijawab di tempat datanya dibaca, bukan dengan menelusuri halaman audit
    // log yang memuat seluruh sistem.
    $isi = $this->get($url)->assertOk()->getContent();

    expect($isi)->toContain('Catatan Log')
        ->and($isi)->toContain("tab === 'log'");

    // Isi tabnya wajib benar-benar memuat jejak milik baris ini.
    foreach (DummyData::riwayatData($namaTabel, $recordId) as $jejak) {
        expect($isi)->toContain($jejak['ringkasan']);
    }
})->with([
    ['/transmigran/1', 'transmigran', 1],
    ['/rumah/1', 'rumah', 1],
    ['/lahan/1', 'lahan', 1],
    ['/poktan/1', 'poktan', 1],
    ['/pengaduan/1', 'pengaduan', 1],
    ['/alsintan/1', 'alsintan', 1],
    ['/saprotan/1', 'saprotan', 1],
    ['/infrastruktur/1', 'infrastruktur', 1],
    ['/komoditas/1', 'komoditas', 1],
    ['/panen/1', 'hasil_panen', 1],
]);

it('membedakan riwayat kosong dari kegagalan pencatatan', function () {
    // Riwayat kosong berarti datanya memang belum pernah disentuh sejak
    // dicatat, bukan berarti pencatatannya gagal. Transmigran 2 sengaja
    // dibiarkan tanpa jejak agar keadaan ini ikut teruji.
    expect(DummyData::riwayatData('transmigran', 2))->toBeEmpty();

    $this->get('/transmigran/2')
        ->assertOk()
        ->assertSee('Belum ada perubahan tercatat');
});

it('tidak menggantikan halaman audit log dengan tab catatan log', function () {
    // Keduanya menjawab pertanyaan berbeda: audit log menjawab apa saja yang
    // terjadi di seluruh sistem, tab ini menjawab apa yang terjadi pada satu
    // data saja. Tab karena itu menautkan ke halaman audit log, bukan
    // menggantikannya.
    $this->get(route('audit-log'))->assertOk();

    expect($this->get('/transmigran/1')->getContent())
        ->toContain(route('audit-log'));
});

it('menyaring riwayat akun pengguna menurut akun yang dibuka', function () {
    // Cacat lama: penyaringan hanya memakai nama_tabel, sehingga setiap akun
    // menampilkan riwayat akun orang lain. Komentar lamanya bahkan mengaku
    // mencocokkan nomor baris, padahal kodenya tidak melakukannya.
    //
    // Modal ini melayani seluruh baris secara bergantian, sehingga akun yang
    // dibuka baru diketahui saat modal dipanggil; penyaringannya karena itu
    // berada di sisi klien.
    $isi = $this->get(route('pengguna.index'))->getContent();

    expect($isi)->toContain('Number(baris.record_id) === Number(this.akun.id_user)');
});

it('memasang catatan log pada SETIAP halaman rincian yang ada', function () {
    // Penjaga kelengkapan. Daftar halamannya dibaca dari tabel rute, bukan
    // ditulis tetap, sehingga halaman rincian baru yang lupa dipasangi Catatan
    // Log langsung tertangkap tanpa perlu menyunting uji ini.
    //
    // Uji ini lahir dari kelalaian nyata: pemasangan pertama hanya menyentuh
    // lima halaman yang kebetulan sudah bertab, sedangkan lima halaman lain
    // terlewat justru karena belum bertab.
    $rute = collect(app('router')->getRoutes())
        ->filter(fn ($r) => in_array('GET', $r->methods(), true))
        // Halaman rincian entitas berpola "<modul>/{id}" beruas dua.
        ->filter(fn ($r) => preg_match('#^[a-z-]+/\{id\}$#', $r->uri()) === 1)
        ->map(fn ($r) => str_replace('{id}', '1', $r->uri()))
        ->values();

    expect($rute)->not->toBeEmpty();

    $tanpaLog = [];

    foreach ($rute as $jalur) {
        $balasan = $this->get('/' . $jalur);

        if ($balasan->status() !== 200) {
            continue;
        }

        if (! str_contains($balasan->getContent(), "tab === 'log'")) {
            $tanpaLog[] = $jalur;
        }
    }

    expect($tanpaLog)->toBe([]);
});

it('menyeragamkan seluruh halaman rincian memakai tab', function () {
    // ui-spec.md 2.2 menetapkan komposisi halaman detail: ringkasan entitas
    // menetap di kiri, tab konten di kanan. Lima halaman sempat memakai kartu
    // bersusun tanpa tab, sehingga letak Catatan Log berbeda-beda antarmodul
    // dan petugas harus menebaknya tiap berpindah.
    foreach ([
        '/transmigran/1', '/rumah/1', '/lahan/1', '/poktan/1', '/pengaduan/1',
        '/alsintan/1', '/saprotan/1', '/infrastruktur/1', '/komoditas/1', '/panen/1',
    ] as $jalur) {
        expect($this->get($jalur)->getContent())
            ->toContain('hashTabs(')
            ->and($this->get($jalur)->getContent())->toContain('role="tablist"');
    }
});

/*
|--------------------------------------------------------------------------
| Penandaan isian wajib
|--------------------------------------------------------------------------
*/

it('menandai wajib setiap isian yang kolomnya tidak boleh kosong', function () {
    // Penjaga berbasis kamus data, bukan daftar tetap. Kolom bertanda
    // "Null = TIDAK" berarti wajib di database, sehingga formnya pun harus
    // menuntutnya sejak di peramban.
    //
    // Uji ini lahir dari audit yang menemukan 43 isian tanpa penanda apa pun.
    // Cacatnya ternyata mengelompok: seluruh form master dan aset tidak pernah
    // dilewati penandaan, sedangkan form kependudukan sudah benar sejak awal.
    $wajibPerTabel = kolomWajibDariKamusData();

    // Berkas form dipetakan ke tabelnya. Yang tidak tercantum di sini belum
    // punya form tersendiri pada Tahap 2.
    $peta = [
        'master/form-satuan' => 'satuan',
        'sp/form-kawasan' => 'kawasan_transmigrasi',
        'sp/form' => 'satuan_permukiman',
        'sp/form-inventaris' => 'inventaris_sp',
        'sp/form-fasilitas' => 'fasilitas_sp',
        'transmigran/form' => 'transmigran',
        'rumah/form' => 'rumah',
        'lahan/form' => 'lahan',
        'poktan/form' => 'poktan',
        'poktan/form-anggota' => 'anggota_poktan',
        'alsintan/form' => 'alsintan',
        'saprotan/form' => 'saprotan',
        'komoditas/form' => 'komoditas',
        'komoditas/form-musim-tanam' => 'musim_tanam',
        'komoditas/form-riwayat-tanam' => 'riwayat_tanam',
        'panen/form' => 'hasil_panen',
        'infrastruktur/form' => 'infrastruktur',
        'pengaduan/form' => 'pengaduan',
    ];

    // Kolom yang sengaja tidak diminta lewat formulir.
    $dikecualikan = [
        // Diisi sistem, bukan pengguna.
        'user' => ['username', 'password', 'password_harus_diganti', 'is_aktif'],
        'role' => ['is_bawaan', 'is_terkunci'],
        // Kolom boolean berbawaan tidak memerlukan required; memasangnya pada
        // kotak centang justru berarti "harus dicentang".
        'komoditas' => ['is_unggulan'],
        // Diturunkan sistem dari kategori, bukan dipilih pengguna.
        'pengaduan' => ['bidang_penanganan', 'sumber_laporan', 'status', 'nomor_pengaduan'],
        'hasil_panen' => ['satuan_id'],
        'inventaris_sp' => ['status_penyerahan'],
        'fasilitas_sp' => ['status_penyerahan'],
        'infrastruktur' => ['kondisi'],
        'alsintan' => ['kepemilikan'],
        'rumah' => ['kode_rumah'],
    ];

    $bolong = [];

    foreach ($peta as $berkas => $tabel) {
        $sumber = file_get_contents(resource_path("views/pages/{$berkas}.blade.php"));

        foreach ($wajibPerTabel[$tabel] ?? [] as $kolom) {
            if (in_array($kolom, $dikecualikan[$tabel] ?? [], true)) {
                continue;
            }

            // Isian boleh tidak ada sama sekali; yang dilarang adalah ada
            // tetapi tanpa penanda wajib.
            if (! str_contains($sumber, 'name="' . $kolom . '"')) {
                continue;
            }

            $pola = preg_quote($kolom, '/');

            // Isian tersembunyi selalu terisi nilai dari sistem, sehingga
            // `required` di sana tidak menambah apa pun.
            if (preg_match('/<input type="hidden" name="' . $pola . '"/', $sumber) === 1) {
                continue;
            }

            // Select tanpa <option value=""> mustahil dikirim kosong: pilihan
            // pertamanya sudah menjadi nilai bawaan.
            if (preg_match('/<select[^>]*name="' . $pola . '"(.*?)<\/select>/s', $sumber, $m) === 1
                && ! str_contains($m[1], 'value=""')) {
                continue;
            }

            $berRequired = preg_match(
                '/name="' . $pola . '"[^>]*\srequired/s',
                $sumber,
            ) === 1;

            if (! $berRequired) {
                $bolong[] = "{$berkas} -> {$kolom}";
            }
        }
    }

    expect($bolong)->toBe([]);
});

it('memasangkan bintang wajib dengan atribut required', function () {
    // Keduanya wajib berpasangan. Bintang tanpa `required` menjanjikan sesuatu
    // yang tidak ditegakkan, dan cacat persis itu ditemukan pada halaman masuk:
    // bintang sudah terpasang sejak awal, `required` tidak pernah ada.
    $timpang = [];

    $berkas = array_merge(
        glob(resource_path('views/pages/*/form*.blade.php')),
        glob(resource_path('views/pages/auth/*.blade.php')),
    );

    foreach ($berkas as $path) {
        $sumber = file_get_contents($path);
        $nama = str_replace(resource_path('views/pages/'), '', $path);

        // Hitung bintang pada label dan isian ber-required. Komponen bersama
        // memancarkan keduanya sekaligus, sehingga ikut dihitung lewat prop
        // `wajib`. Larik kotak centang ditegakkan Alpine saat submit, sebab
        // `required` di sana berarti "setiap kotak harus dicentang".
        $bintang = substr_count($sumber, 'text-error-500">*</span>');
        $required = preg_match_all('/\srequired(\s|\/|>)/', $sumber)
            + substr_count($sumber, ':wajib="true"')
            + substr_count($sumber, ':required=')
            + substr_count($sumber, '$event.preventDefault()');

        if ($bintang > $required) {
            $timpang[] = "{$nama}: {$bintang} bintang, {$required} required";
        }
    }

    expect($timpang)->toBe([]);
});

/*
|--------------------------------------------------------------------------
| Istilah pada teks antarmuka
|--------------------------------------------------------------------------
*/

it('memakai istilah email, bukan surel, pada teks yang dilihat pengguna', function (string $jalur) {
    // ui-spec.md 10.1. Diperiksa pada halaman TERENDER, bukan berkas sumber,
    // sebab komentar kode bebas memakai istilah mana pun; yang mengikat hanya
    // teks yang benar-benar sampai ke pengguna.
    //
    // Sesi diisi lebih dulu agar spanduk yang hanya muncul setelah pengiriman
    // formulir ikut terperiksa. Tanpa itu, sebagian teks tidak pernah dirender
    // dan uji lulus tanpa memeriksa apa pun.
    $isi = $this->withSession([
        'kredensial_baru' => ['nama' => 'UJI', 'email' => 'uji@malakakab.go.id', 'password' => 'Rahasia123'],
        'nomor_pengaduan' => 'PGD-2026-0003',
        'email_pelapor' => 'warga@contoh.id',
    ])->get($jalur)->assertOk()->getContent();

    expect(mb_strtolower($isi))->not->toContain('surel');
})->with([
    '/login',
    '/lupa-kata-sandi',
    '/verifikasi-kode',
    '/pengguna',
    '/pengaturan/role',
    '/pengaduan-warga',
    '/lacak-pengaduan',
    '/profil',
    '/profil/kata-sandi',
]);

it('menuliskan baris total tanpa penanda cakupan', function (string $jalur) {
    // Judul halaman dan filter yang sedang aktif sudah menyatakan cakupannya,
    // sehingga "Total kawasan" mengulang informasi yang ada tepat di atasnya.
    $isi = $this->get($jalur)->assertOk()->getContent();

    expect($isi)->toContain('motif-baris-total')
        ->and($isi)->not->toContain('>Total kawasan<');
})->with([
    '/panen/rekap',
    '/pengaduan/rekap',
    '/kependudukan/rekap',
    '/sp',
    '/kawasan',
    '/infrastruktur',
]);

it('memakai istilah fitur dan kewenangan pada teks yang dilihat pengguna', function (string $jalur) {
    // ui-spec.md 10.1. "Modul" dan "izin" adalah istilah pengembang; petugas
    // dinas lebih mengenali "fitur" dan "kewenangan".
    //
    // Diperiksa pada TEKS terender, bukan berkas sumber. Tag dibuang lebih
    // dulu sebab atribut seperti type="module" dan name="izin[]" memang tetap
    // memakai istilah teknis, dan tidak pernah dibaca pengguna.
    $isi = $this->get($jalur)->assertOk()->getContent();

    $teks = preg_replace('/<script.*?<\/script>/s', ' ', $isi);
    $teks = preg_replace('/<[^>]+>/', ' ', $teks);

    // "Izin lokasi" adalah istilah peramban yang muncul pada pesan Geolocation
    // API, bukan istilah sistem ini. Menggantinya justru membuat pesannya tidak
    // cocok dengan yang dilihat pengguna pada dialog peramban.
    $teks = str_ireplace('izin lokasi', '', $teks);

    expect($teks)->not->toMatch('/\bmodul\b/i')
        // "diizinkan" dan "mengizinkan" adalah kata kerja yang tetap sah;
        // yang dilarang hanya kata benda "izin" berdiri sendiri.
        ->and($teks)->not->toMatch('/\bizin\b/i');
})->with([
    '/pengaturan/role',
    '/pengguna',
    '/infrastruktur',
    '/infrastruktur/1',
    '/audit-log',
    '/galeri-komponen',
]);

it('menyaring ketikan bukan angka pada isian angka', function () {
    // `type="number"` menolak huruf, tetapi masih menerima notasi ilmiah `e`,
    // tanda minus, dan tempelan teks. Ketiganya baru tertangkap saat
    // penyimpanan, padahal petugas sudah telanjur mengisi seluruh formulir.
    $sumber = file_get_contents(resource_path('js/input-angka.js'));

    expect($sumber)->toContain("isian.type !== 'number'")
        ->and($sumber)->toContain('keydown')
        ->and($sumber)->toContain('paste')
        // Isian berlangkah pecahan tetap menerima koma desimal, sedangkan
        // tahun dan jumlah unit tidak.
        ->and($sumber)->toContain('menerimaPecahan');

    // Terpasang sekali pada app.js, bukan disalin ke tiap isian. Dicocokkan
    // pada baris yang benar-benar dijalankan, sebab pemanggilan yang sekadar
    // dikomentari tetap lolos bila hanya kata kuncinya yang dicari.
    $app = file_get_contents(resource_path('js/app.js'));

    expect($app)->toMatch('/^pasangPenjagaAngka\(\);$/m')
        ->and($app)->toContain("from './input-angka'");
});
/*
|--------------------------------------------------------------------------
| Ekspor menempel pada tabel
|--------------------------------------------------------------------------
*/

it('menyediakan tombol ekspor pada setiap halaman berdaftar', function (string $jalur) {
    // rules.md 12 poin 5 mewajibkan laporan dapat difilter sebelum diekspor.
    // Halaman laporan terpusat tidak pernah memenuhinya: ia hanya memuat
    // kartu unduhan tanpa satu pun kontrol filter, sehingga petugas selalu
    // menerima seluruh isi tabel. Karena itu ekspor dipindah ke halaman yang
    // filternya memang sudah bekerja, dan halaman terpusatnya dihapus.
    $this->get($jalur)->assertOk()->assertSee('data-ekspor', false);
})->with([
    '/transmigran',
    '/rumah',
    '/lahan',
    '/panen',
    '/pengaduan',
    '/poktan',
    '/alsintan',
    '/saprotan',
    '/komoditas',
    '/infrastruktur',
    '/sp',
    '/pengguna',
    '/audit-log',
    '/panen/rekap',
    '/pengaduan/rekap',
    '/kependudukan/rekap',
    '/',
]);

it('membawa filter yang sedang aktif ke dalam alamat ekspor', function () {
    // Inti perubahan ini. Tombol yang tidak meneruskan filter menghasilkan
    // berkas berisi seluruh kawasan padahal layar sedang menampilkan satu SP
    // saja, dan selisihnya baru disadari setelah berkas dibuka di lapangan.
    $isi = $this->get('/transmigran?cari=NARA&status_tinggal=Menetap')
        ->assertOk()
        ->getContent();

    preg_match('/data-parameter="([^"]*)"/', $isi, $cocok);

    expect($cocok)->not->toBeEmpty('Atribut data-parameter tidak dirender');

    $terurai = [];
    parse_str(html_entity_decode($cocok[1]), $terurai);

    expect($terurai)->toMatchArray([
        'cari' => 'NARA',
        'status_tinggal' => 'Menetap',
    ]);
});

it('tidak membawa parameter apa pun ketika tabel belum disaring', function () {
    // Kebalikannya juga wajib benar: tanpa filter, alamat ekspor harus bersih.
    // Parameter sisa dari kunjungan sebelumnya akan menyaring diam-diam.
    $isi = $this->get('/transmigran')->assertOk()->getContent();

    preg_match('/data-parameter="([^"]*)"/', $isi, $cocok);

    expect($cocok[1])->toBe('');
});

it('menghapus halaman laporan terpusat beserta jejaknya', function () {
    // Halaman itu menyalahi aturannya sendiri: menawarkan sembilan unduhan
    // tanpa filter. Setelah ekspor menempel pada tabel, mempertahankannya
    // berarti dua jalan menuju satu hasil, dan yang satu lebih buruk.
    $this->get('/laporan')->assertNotFound();

    // Menu yang menunjuk halaman tidak ada melanggar R-24, dan baru ketahuan
    // saat petugas mengkliknya.
    $tujuan = [];
    foreach (App\Helpers\MenuHelper::definisiMenu() as $kelompok) {
        foreach ($kelompok['items'] as $item) {
            $tujuan[] = $item['path'] ?? null;
            foreach ($item['subItems'] ?? [] as $sub) {
                $tujuan[] = $sub['path'] ?? null;
            }
        }
    }

    expect($tujuan)->not->toContain('/laporan');
});

it('mencabut kewenangan export dari seluruh sumber kebenaran', function () {
    // Ekspor kini mengikuti `lihat`, sebab ia hanya cara lain membaca data
    // yang sudah boleh dilihat (rules.md 5.1 catatan 5). Nilai yang tertinggal
    // di salah satu sumber akan menghidupkan kembali kotak centang yang tidak
    // menjaga apa pun.
    expect(array_column(App\Enums\AksiPermission::cases(), 'value'))
        ->toBe(['lihat', 'tambah', 'ubah', 'hapus']);

    foreach (App\Support\DummyData::daftarIzin() as $kelompok) {
        foreach ($kelompok['modul'] as $modul) {
            expect($modul['aksi'])->not->toContain('export');
        }
    }

    foreach ([1, 2, 3, 4, 5] as $idRole) {
        foreach (App\Support\DummyData::izinRole($idRole) as $aksi) {
            expect($aksi)->not->toContain('export');
        }
    }

    // Huruf E pada matriks rules.md 5.1 ikut dicabut, agar dokumen tidak
    // menjanjikan kewenangan yang tidak lagi ada di kode.
    expect(file_get_contents(base_path('agents/rules.md')))
        ->not->toContain('**E** = export');
});

/*
|--------------------------------------------------------------------------
| Kelompok tani: ketua, jabatan, dan keanggotaan
|--------------------------------------------------------------------------
*/

it('menyediakan dua jalur pengisian ketua poktan', function () {
    // Ketua poktan tidak selalu transmigran (rules.md 7a.2a). Banyak poktan
    // diketuai penduduk setempat yang bukan peserta program, dan membatasi
    // pilihan pada daftar transmigran membuat poktan semacam itu tidak dapat
    // didata sama sekali.
    $isi = $this->get(route('poktan.index'))->assertOk()->getContent();

    expect($isi)->toContain('name="is_ketua_transmigran"')
        // Jalur 1: dipilih dari daftar, agar NIK dan tautan profilnya sahih.
        ->and($isi)->toContain('name="ketua_transmigran_id"')
        // Jalur 2: diketik langsung untuk ketua non-transmigran.
        ->and($isi)->toContain('name="nama_ketua"')
        ->and($isi)->toContain('name="nik_ketua"');
});

it('menyimpan kontak ketua, bukan kontak kelompok, pada poktan', function () {
    // Nama kolom lama (`telepon`, `email`, `alamat_sekretariat`) menyatakan
    // kontak kelompok, padahal data contoh dan halaman rincian sejak awal
    // memperlakukannya sebagai kontak ketua. Penamaan disamakan agar dokumen
    // dan kode menyebut hal yang sama (rules.md 7a.2b).
    $isi = $this->get(route('poktan.index'))->assertOk()->getContent();

    expect($isi)->toContain('name="telepon_ketua"')
        ->and($isi)->toContain('name="email_ketua"')
        ->and($isi)->toContain('name="alamat_ketua"');
});

it('mencabut Ketua dari pilihan jabatan anggota poktan', function () {
    // Ketua ditetapkan pada profil poktan. Menyediakannya juga di daftar
    // anggota membuat satu poktan dapat memiliki dua ketua berbeda tanpa
    // penjaga apa pun (rules.md 7a.4b).
    expect(array_column(App\Enums\JabatanAnggotaPoktan::cases(), 'value'))
        ->toBe(['Sekretaris', 'Bendahara', 'Anggota']);

    foreach (App\Support\DummyData::anggotaPoktan() as $anggota) {
        expect($anggota['jabatan'])->not->toBe('Ketua');
    }
});

it('menyediakan jalur mengubah data anggota poktan', function () {
    // Tanpa ini, status keaktifan dan tanggal keluar tidak pernah dapat diisi
    // setelah anggota tersimpan, padahal justru keduanya yang berubah
    // belakangan (rules.md 7a.4a). Sebelumnya halaman rincian hanya punya
    // tombol tambah.
    $isi = $this->get(route('poktan.detail', 1))->assertOk()->getContent();

    // Pola aksi dikirim ke Alpine lewat @js, sehingga garis miringnya lolos
    // sebagai `\/`. Dicocokkan setelah lolosan itu dibuang agar uji tidak
    // bergantung pada cara Blade menuliskan JSON.
    $tanpaLolosan = str_replace('\\/', '/', $isi);

    expect($isi)->toContain('formUbahAnggotaPoktan')
        ->and($tanpaLolosan)->toContain('/anggota-poktan/:id');

    // Rutenya benar-benar ada, bukan hanya modal yang menganga.
    expect(Illuminate\Support\Facades\Route::has('anggota-poktan.perbarui'))->toBeTrue();
});

it('tidak menyediakan penghapusan anggota poktan', function () {
    // Anggota yang berhenti ditandai Sudah Keluar agar catatan penyaluran
    // saprotan di masa lalu tetap memiliki penerima yang jelas. Karena itu
    // huruf H dicabut dari matriks kewenangan agar dokumen tidak menjanjikan
    // tindakan yang memang tidak ada.
    expect(Illuminate\Support\Facades\Route::has('anggota-poktan.hapus'))->toBeFalse();

    expect(App\Support\DummyData::izinRole(1)['anggota_poktan'])
        ->not->toContain('hapus');

    expect(file_get_contents(base_path('agents/rules.md')))
        ->toContain('| Anggota poktan | L T U | L | L T U | L T U |');
});

it('menetapkan keanggotaan poktan dari sisi poktan saja', function () {
    // Dua sumber kebenaran untuk satu fakta selalu berakhir berbeda: petugas
    // dapat menyatakan "Ya" pada form transmigran tanpa seorang pun
    // mendaftarkannya ke kelompok mana pun (rules.md 7a.8).
    $transmigran = $this->get(route('transmigran.index'))->assertOk()->getContent();

    expect($transmigran)->not->toContain('name="status_anggota_poktan"');

    // Sebaliknya, form anggota poktan tetap memegang penetapannya.
    $poktan = $this->get(route('poktan.detail', 1))->assertOk()->getContent();

    expect($poktan)->toContain('name="transmigran_id"');
});

/*
|--------------------------------------------------------------------------
| Dokumen pendukung dan pilihan berdaftar panjang
|--------------------------------------------------------------------------
*/

it('menyediakan unggahan dokumen pada modul yang kolomnya sudah ada', function (string $jalur, string $isian) {
    // Kedelapan kolom dokumen sudah lama tercatat pada data-dictionary.md,
    // tetapi tujuh form tidak pernah punya isiannya. Akibatnya SK pembentukan
    // poktan dan berita acara penyaluran saprotan tidak dapat diunggah ke mana
    // pun, padahal justru keduanya yang diminta saat pemeriksaan.
    $this->get($jalur)->assertOk()->assertSee('name="' . $isian . '"', false);
})->with([
    ['/sp', 'dokumen_pendukung'],
    ['/sp/inventaris', 'dokumen_pendukung'],
    ['/sp/fasilitas', 'dokumen_pendukung'],
    ['/infrastruktur', 'dokumen_pendukung'],
    // Infrastruktur punya dua kolom terpisah: foto merekam kondisi lapangan,
    // dokumen menyimpan berkas administratifnya.
    ['/infrastruktur', 'foto'],
    ['/poktan', 'dokumen_pendukung'],
    ['/alsintan', 'dokumen_pendukung'],
    ['/saprotan', 'dokumen_pendukung'],
]);

it('mengirim unggahan lewat form yang benar-benar menerima berkas', function () {
    // Tanpa enctype multipart, berkas yang dipilih petugas tidak pernah
    // terkirim dan kegagalannya berlangsung diam-diam: form tetap tersimpan,
    // hanya berkasnya yang hilang.
    expect(file_get_contents(resource_path('views/components/sim/modal-form.blade.php')))
        ->toContain('enctype="multipart/form-data"');
});

it('menyediakan pencarian pada pilihan yang daftarnya panjang', function () {
    // Data contoh hanya berisi 8 transmigran sehingga select biasa masih
    // nyaman, tetapi PRD menyebut sekitar 1.140 kepala keluarga di kawasan
    // ini. Menggulir 1.140 baris untuk mencari satu nama adalah pekerjaan
    // yang tidak akan dilakukan siapa pun dengan sabar.
    foreach (['/poktan', '/alsintan', '/lahan', '/panen'] as $jalur) {
        $this->get($jalur)->assertOk()->assertSee('Ketik untuk menyaring daftar');
    }
});

it('tidak memasang kotak pencarian pada daftar yang masih pendek', function () {
    // Kotak pencarian di atas empat pilihan justru menambah satu benda yang
    // harus dilewati, bukan mempercepat. Ambangnya dihitung dari jumlah opsi
    // yang benar-benar dirender, bukan disetel per halaman.
    expect(count(DummyData::transmigranTanpaRumah()))->toBeLessThan(8);

    $this->get('/rumah')->assertOk()->assertDontSee('Ketik untuk menyaring daftar');
});

it('mengirim nilai lewat isian bernama kolomnya, bukan lewat panel', function () {
    // Panel hanyalah antarmuka. Nilai yang terkirim berasal dari isian bernama
    // sama seperti kolomnya, sehingga Form Request pada tahap backend tidak
    // perlu tahu komponen ini ada.
    //
    // Isian itu BUKAN `type="hidden"`: peramban mengabaikan `required` pada
    // isian tersembunyi, sehingga form akan terkirim tanpa peringatan meski
    // isian wajib masih kosong. Dipakai `sr-only` agar tetap dapat divalidasi.
    $isi = $this->get('/lahan')->assertOk()->getContent();

    expect($isi)->toContain('name="transmigran_id"')
        ->and($isi)->toMatch('/name="transmigran_id"[^>]*class="sr-only"/');
});

it('menyediakan cadangan tanpa JavaScript pada pilihan berpanel', function () {
    // Sinyal di lokus tidak selalu stabil, dan form yang mustahil diisi karena
    // satu berkas gagal diunduh adalah kegagalan yang tidak perlu. Isi
    // <noscript> hanya diuraikan peramban ketika JavaScript benar-benar mati.
    $isi = $this->get('/lahan')->assertOk()->getContent();

    expect($isi)->toContain('<noscript>')
        // Cadangannya berupa select asli yang tetap membawa nama kolom.
        ->and($isi)->toMatch('/<noscript>.*?<select name="transmigran_id"/s');
});

it('menandai pilihan berpanel dengan peran ARIA yang benar', function () {
    // Tidak ada preseden combobox di repositori ini, sehingga perannya ditulis
    // eksplisit: tanpa role dan aria-expanded, pembaca layar hanya mengumumkan
    // sebuah tombol tanpa memberi tahu bahwa ada daftar yang dapat dibuka
    // (ANTISLOP-ID R-32).
    $isi = $this->get('/lahan')->assertOk()->getContent();

    expect($isi)->toContain('role="combobox"')
        ->and($isi)->toContain('role="listbox"')
        ->and($isi)->toContain('role="option"')
        ->and($isi)->toContain('aria-haspopup="listbox"');
});

it('mendefinisikan x-cloak secara global', function () {
    // Sebelum 2026-08-17 aturan ini hanya ada inline di components/ui/modal,
    // dan komponen itu tidak dipakai satu halaman pun. Akibatnya 96 pemakaian
    // x-cloak di repositori tidak berfungsi: panel dan modal sempat berkedip
    // terlihat setiap kali halaman dimuat.
    expect(file_get_contents(resource_path('css/app.css')))
        ->toMatch('/\[x-cloak\]\s*\{/');
});

it('tidak menyentuh document.body sebelum DOM siap', function () {
    // Skrip tema berjalan di dalam <head>, saat document.body belum ada.
    // Versi sebelumnya melempar "Cannot read properties of null" pada setiap
    // pemuatan halaman; galatnya tidak menghentikan apa pun, tetapi membanjiri
    // konsol dan menyamarkan galat lain yang benar-benar penting.
    foreach (['layouts/app', 'layouts/fullscreen-layout'] as $berkas) {
        $sumber = file_get_contents(resource_path("views/{$berkas}.blade.php"));

        // Ambil hanya skrip anti-kedip yang berada di dalam <head>.
        preg_match('/const savedTheme.*?\}\)\(\);/s', $sumber, $cocok);

        expect($cocok)->not->toBeEmpty("Skrip tema tidak ditemukan pada {$berkas}");
        expect($cocok[0])->toContain('DOMContentLoaded');
    }
});

/*
|--------------------------------------------------------------------------
| Bidang lahan: peruntukan, status hak, dan dokumen
|--------------------------------------------------------------------------
*/

it('menyediakan dokumen pertama langsung pada form lahan', function () {
    // Dokumen semula hanya dapat diunggah lewat tab tersendiri di halaman
    // rincian, dengan alasan satu bidang dapat memiliki lebih dari satu
    // dokumen. Alasan itu benar secara teori, tetapi memaksa dua langkah untuk
    // keadaan yang paling lazim: pada data contoh, tidak satu pun bidang
    // memiliki lebih dari satu dokumen.
    $isi = $this->get(route('lahan.index'))->assertOk()->getContent();

    foreach (['jenis_dokumen', 'nomor_dokumen', 'tanggal_terbit', 'file_dokumen'] as $isian) {
        expect($isi)->toContain('name="' . $isian . '"');
    }
});

it('mempertahankan tab dokumen untuk berkas tambahan', function () {
    // Dokumen kedua tetap perlu tempat, misalnya bidang yang sertifikatnya
    // terbit menyusul setelah surat keterangan pembagian tanah.
    $this->get(route('lahan.detail', 1))
        ->assertOk()
        ->assertSee('Tambah Dokumen Lahan')
        ->assertSee('Dokumen pertama diisi pada form lahan');
});

it('menyediakan dua peruntukan lahan, bukan lebih', function () {
    // Tahap I dan II sempat ditambahkan pada 2026-08-18 atas dugaan bahwa
    // lahan usaha dibagikan bertahap. Dugaan itu dibatalkan pada hari yang
    // sama: satu transmigran menerima satu pekarangan dan satu lahan usaha.
    // Pilihan yang tidak pernah berbeda hanya menambah keputusan bagi petugas.
    $isi = $this->get(route('lahan.index'))->assertOk()->getContent();

    expect($isi)->toContain('name="peruntukan_lahan"')
        ->and($isi)->not->toContain('Lahan Usaha I<')
        ->and($isi)->not->toContain('Lahan Usaha II')
        // Nama kolom lama tidak boleh tertinggal di mana pun.
        ->and($isi)->not->toContain('name="jenis_lahan"')
        ->and($isi)->not->toContain('name="status_kepemilikan"');
});

it('menempatkan area unggah dokumen di baris penuh, bukan berpasangan', function () {
    // Area unggah jauh lebih tinggi daripada isian teks, sehingga menaruhnya
    // dalam grid berpasangan menyisakan ruang kosong besar di kolom sebelahnya.
    // Tiga belas form lain sudah menempatkannya di baris penuh.
    $sumber = file_get_contents(resource_path('views/pages/lahan/form.blade.php'));

    // Ketiga keterangan dokumen berjajar tiga kolom.
    expect($sumber)->toContain('sm:grid-cols-3');

    // Area unggah berada SETELAH grid ditutup, bukan di dalamnya.
    preg_match('/sm:grid-cols-3(.*?)file_dokumen/s', $sumber, $cocok);

    expect($cocok)->not->toBeEmpty()
        ->and($cocok[1])->toContain('</div>');
});

it('memakai status hak atas tanah, bukan status kepemilikan', function () {
    // HPL adalah Hak Pengelolaan milik instansi atas tanah kawasan, sehingga
    // tidak pernah menjadi hak seorang transmigran; SHM adalah nama
    // sertifikatnya, bukan nama haknya. Menampilkannya sebagai pilihan status
    // membuat sistem menyatakan warga "memiliki lahan berstatus HPL".
    $isi = $this->get(route('lahan.index'))->assertOk()->getContent();

    expect($isi)->toContain('name="status_hak"')
        ->and($isi)->toContain('Status Hak Atas Tanah')
        ->and($isi)->toContain('Belum Bersertifikat');
});

it('menjumlahkan luas lahan usaha dari seluruh tahapnya', function () {
    // Penjumlahan semula mencocokkan teks "Lahan Usaha" persis, sehingga
    // bidang tahap kedua akan hilang dari rekap tanpa ada yang menyadarinya.
    $nilaiUsaha = App\Enums\PeruntukanLahan::nilaiLahanUsaha();

    $luasUsaha = array_sum(array_column(
        array_filter(
            DummyData::lahan(),
            fn ($l) => in_array($l['peruntukan_lahan'], $nilaiUsaha, true)
        ),
        'luas'
    ));

    // 1,50 + 0,75 + 2,00 + 1,25 = 5,50 hektare pada data contoh.
    expect($luasUsaha)->toBe(5.5);

    $this->get(route('lahan.index'))
        ->assertOk()
        ->assertSee(number_format($luasUsaha, 2, ',', '.'));
});

it('mencabut batas wilayah SP dari seluruh sumber', function () {
    // Keempat kolom `batas_*` menyimpan sebutan naratif seperti "Hutan
    // lindung", bukan koordinat, sehingga tidak pernah dipakai perhitungan,
    // indikator dashboard, penilaian kondisi SP, maupun peta. Satu-satunya
    // kegunaannya menyalin isi berkas penetapan.
    //
    // Uji ini menjaga agar isian yang sudah dicabut tidak kembali sebagian:
    // form tanpa kolom, atau kolom tanpa form, sama-sama menyesatkan.
    $arah = ['batas_utara', 'batas_timur', 'batas_selatan', 'batas_barat'];

    $formSp = $this->get(route('sp.index'))->assertOk()->getContent();
    $rincianSp = $this->get(route('dashboard.sp', 1))->assertOk()->getContent();

    foreach ($arah as $kolom) {
        expect($formSp)->not->toContain('name="' . $kolom . '"')
            ->and($rincianSp)->not->toContain($kolom);
    }

    // Data contoh dan kamus data ikut bersih, agar tidak ada kolom yatim yang
    // tersimpan tanpa pernah dapat diisi.
    foreach (DummyData::satuanPermukiman() as $sp) {
        foreach ($arah as $kolom) {
            expect($sp)->not->toHaveKey($kolom);
        }
    }

    expect(file_get_contents(base_path('agents/data-dictionary.md')))
        ->not->toContain('| `batas_utara` |');
});

/*
|--------------------------------------------------------------------------
| Komoditas unggulan: penandaan manusia, bukan hitungan
|--------------------------------------------------------------------------
*/

it('menandai komoditas unggulan lewat penandaan petugas, bukan hitungan volume', function () {
    // rules.md 8.1 menyebut unggulan sebagai yang "disebut dalam proposal",
    // dan 8.3 memakai kata "penandaan". Jagung sudah ditandai unggulan sebelum
    // satu baris panen pun tercatat, sehingga menghitungnya dari volume berarti
    // menjawab pertanyaan yang berbeda.
    //
    // Menghitung otomatis juga menutup kasus yang justru paling perlu
    // ditandai: komoditas prioritas program yang volumenya masih kecil.
    $isi = $this->get(route('komoditas.index'))->assertOk()->getContent();

    expect($isi)->toContain('name="is_unggulan"')
        ->and($isi)->toContain('Ditetapkan menurut proposal atau kebijakan dinas');
});

it('menampilkan volume tercatat sebagai bahan pertimbangan penandaan', function () {
    // Petugas tetap memutuskan, tetapi tidak lagi menebak: keadaan volume
    // ditampilkan di samping centang.
    $this->get(route('komoditas.detail', 1))
        ->assertOk()
        ->assertSee('Volume tercatat')
        // Jagung volumenya terbesar, sehingga tidak diperingatkan.
        ->assertDontSee('bukan yang volumenya terbesar');
});

it('memperingatkan bila yang ditandai unggulan bukan volume terbesar', function () {
    // Peringatan, bukan penolakan. Unggulan bervolume kecil adalah keadaan
    // yang sah; yang tidak boleh adalah petugas menandainya tanpa menyadari.
    $this->get(route('komoditas.detail', 3))
        ->assertOk()
        ->assertSee('bukan yang volumenya terbesar');
});

it('memilih komoditas utama dashboard menurut nilai, bukan urutan larik', function () {
    // `array_key_first()` sempat dipakai dan kebetulan benar hanya karena
    // sebaranKomoditas() ditulis terurut. Begitu urutannya berubah, kartu ini
    // menampilkan komoditas yang keliru tanpa ada yang menyadarinya.
    $sumber = file_get_contents(resource_path('views/pages/dashboard/index.blade.php'));

    expect($sumber)->toContain('max($sebaranKomoditas)')
        ->and($sumber)->not->toContain('array_key_first($sebaranKomoditas)');

    // Hasilnya tetap benar: jagung memang bervolume terbesar.
    $sebaran = DummyData::sebaranKomoditas();

    expect(array_search(max($sebaran), $sebaran, true))->toBe('Jagung');
});

it('memisahkan agregat kawasan dari transaksi panen contoh', function () {
    // Keduanya menjawab pertanyaan berbeda dan tidak boleh diturunkan satu
    // dari yang lain: sebaranKomoditas() adalah agregat kawasan setahun,
    // hasilPanen() hanya beberapa transaksi contoh untuk menguji tampilan.
    //
    // Uji ini menjaga agar keduanya tidak "diperbaiki" menjadi konsisten,
    // sebab menyamakannya akan membuat dashboard menampilkan belasan ton untuk
    // kawasan berisi ribuan keluarga.
    $agregat = array_sum(DummyData::sebaranKomoditas());

    $transaksi = 0.0;
    foreach (DummyData::hasilPanen() as $panen) {
        $transaksi += DummyData::keTon((float) $panen['volume'], $panen['satuan']);
    }

    expect($agregat)->toBeGreaterThan($transaksi * 10);

    // Empat angka dashboard wajib tetap saling konsisten dengan agregat.
    $ringkasan = DummyData::ringkasanDashboard();
    $totalSp = array_sum(array_column(DummyData::rekapPerSp(), 'volume_panen'));
    $deret = DummyData::deretTahunan();

    expect(round($ringkasan['volume_panen_ton'], 1))->toBe(round($agregat, 1))
        ->and(round($totalSp, 1))->toBe(round($agregat, 1))
        ->and(round((float) end($deret['volume_panen']), 1))->toBe(round($agregat, 1));
});
