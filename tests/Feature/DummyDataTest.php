<?php

/**
 * Uji penyedia data contoh.
 *
 * Data contoh dipakai membangun seluruh halaman Tahap 2. Bila strukturnya
 * tidak sesuai kamus data, penggantian ke data nyata pada tahap backend akan
 * memaksa pembongkaran berkas Blade. Uji ini menjaga hal itu tidak terjadi.
 */

use App\Enums\BidangPengaduan;
use App\Enums\JenisInfrastruktur;
use App\Enums\PeruntukanLahan;
use App\Enums\KategoriPengaduan;
use App\Enums\Kondisi;
use App\Enums\KondisiRumah;
use App\Enums\PrioritasPengaduan;
use App\Enums\StatusHunian;
use App\Enums\StatusPengaduan;
use App\Enums\StatusTinggal;
use App\Enums\SumberLaporan;
use App\Support\DummyData;

/*
|--------------------------------------------------------------------------
| Kesesuaian dengan lokus sebenarnya
|--------------------------------------------------------------------------
*/

it('memuat enam satuan permukiman sesuai lokus kegiatan', function () {
    $sp = DummyData::satuanPermukiman();

    expect($sp)->toHaveCount(6);

    $nama = array_column($sp, 'nama');
    expect($nama)->toContain('SP Kapitan Meo')
        ->toContain('SP Tniumanu')
        ->toContain('SP Harekakae')
        ->toContain('SP Weoe / Uluk Lubuk')
        ->toContain('SP Tualaran')
        ->toContain('SP Weain');
});

it('menyebar enam SP pada empat kecamatan', function () {
    $kecamatan = array_unique(array_column(DummyData::satuanPermukiman(), 'kecamatan'));

    sort($kecamatan);

    expect($kecamatan)->toBe(['Laen Manen', 'Malaka Tengah', 'Rinhat', 'Wewiku']);
});

it('menaungi seluruh SP di bawah satu kawasan transmigrasi', function () {
    $kawasan = array_unique(array_column(DummyData::satuanPermukiman(), 'kawasan'));

    expect($kawasan)->toBe(['Kobalima Timur'])
        ->and(DummyData::kawasan()[0]['jumlah_sp'])->toBe(6);
});

it('menempatkan koordinat di sekitar Kabupaten Malaka', function () {
    foreach (DummyData::satuanPermukiman() as $sp) {
        // Kobalima Timur berada di sekitar -9,4 lintang dan 124,9 bujur
        expect($sp['lintang'])->toBeLessThan(-9.0)->toBeGreaterThan(-10.0)
            ->and($sp['bujur'])->toBeGreaterThan(124.0)->toBeLessThan(126.0);
    }
});

/*
|--------------------------------------------------------------------------
| Kesesuaian nilai dengan enum
|--------------------------------------------------------------------------
*/

it('memakai nilai enum yang sah pada data transmigran', function () {
    foreach (DummyData::transmigran() as $baris) {
        expect(StatusTinggal::dari($baris['status_tinggal']))->not->toBeNull();
    }
});

it('memakai nilai enum yang sah pada data rumah', function () {
    foreach (DummyData::rumah() as $baris) {
        expect(KondisiRumah::dari($baris['kondisi']))->not->toBeNull()
            ->and(StatusHunian::dari($baris['status_hunian']))->not->toBeNull();
    }
});

it('memakai nilai enum yang sah pada data pengaduan', function () {
    foreach (DummyData::pengaduan() as $baris) {
        expect(KategoriPengaduan::dari($baris['kategori']))->not->toBeNull()
            ->and(StatusPengaduan::dari($baris['status']))->not->toBeNull()
            ->and(PrioritasPengaduan::dari($baris['prioritas']))->not->toBeNull()
            ->and(SumberLaporan::dari($baris['sumber_laporan']))->not->toBeNull()
            ->and(BidangPengaduan::dari($baris['bidang']))->not->toBeNull();
    }
});

it('memakai nilai enum yang sah pada data infrastruktur', function () {
    foreach (DummyData::infrastruktur() as $baris) {
        expect(JenisInfrastruktur::dari($baris['jenis']))->not->toBeNull()
            ->and(Kondisi::dari($baris['kondisi']))->not->toBeNull();
    }
});

it('menetapkan bidang pengaduan sesuai kategorinya', function () {
    foreach (DummyData::pengaduan() as $baris) {
        $kategori = KategoriPengaduan::from($baris['kategori']);
        $bidangSeharusnya = BidangPengaduan::dariKategori($kategori);

        expect($baris['bidang'])->toBe($bidangSeharusnya->value);
    }
});

/*
|--------------------------------------------------------------------------
| Aturan bisnis yang tercermin di data
|--------------------------------------------------------------------------
*/

it('menjaga satu rumah hanya dihuni satu keluarga', function () {
    $penghuni = array_filter(array_column(DummyData::rumah(), 'penghuni'));

    expect(count($penghuni))->toBe(count(array_unique($penghuni)));
});

it('mengosongkan penghuni pada rumah yang tidak dihuni', function () {
    foreach (DummyData::rumah() as $rumah) {
        if ($rumah['status_hunian'] === StatusHunian::TidakDihuni->value) {
            expect($rumah['penghuni'])->toBeNull()
                ->and($rumah)->toHaveKey('alasan_tidak_dihuni');
        } else {
            expect($rumah['penghuni'])->not->toBeNull();
        }
    }
});

it('memberi tiap keluarga paling banyak satu pekarangan dan satu lahan usaha', function () {
    // Keadaan di Kobalima Timur: satu transmigran menerima satu lahan
    // pekarangan dan satu lahan usaha, tidak lebih. Data contoh yang melebihi
    // itu akan menyesatkan pembaca yang memakainya sebagai acuan.
    //
    // Aturan ini menyatakan jumlah yang WAJAR, bukan batas yang ditegakkan
    // sistem: bila satu jatah lahan usaha terletak pada dua petak berkoordinat
    // berbeda, keduanya tetap perlu dicatat sebagai baris tersendiri.
    $perKeluarga = [];

    foreach (DummyData::lahan() as $lahan) {
        $kunci = PeruntukanLahan::from($lahan['peruntukan_lahan'])->lahanUsaha() ? 'usaha' : 'pekarangan';
        $perKeluarga[$lahan['pemilik']][$kunci] = ($perKeluarga[$lahan['pemilik']][$kunci] ?? 0) + 1;
    }

    foreach ($perKeluarga as $pemilik => $jumlah) {
        expect($jumlah['pekarangan'] ?? 0)->toBeLessThanOrEqual(1, "{$pemilik} punya lebih dari satu pekarangan");
        expect($jumlah['usaha'] ?? 0)->toBeLessThanOrEqual(1, "{$pemilik} punya lebih dari satu lahan usaha");
    }
});

it('mencatat kedua peruntukan lahan pada data contoh', function () {
    // Keduanya wajib terwakili, sebab lahan pekarangan dan lahan usaha
    // berbeda perlakuan: hanya lahan usaha yang memiliki kategori beserta
    // empat kolom pengelolaan.
    $peruntukan = array_column(DummyData::lahan(), 'peruntukan_lahan');

    foreach (PeruntukanLahan::cases() as $kasus) {
        expect($peruntukan)->toContain($kasus->value);
    }

    // Tepat dua nilai; tahap I dan II sempat ditambahkan pada 2026-08-18 lalu
    // dibatalkan pada hari yang sama setelah keadaan lapangan diketahui.
    expect(PeruntukanLahan::cases())->toHaveCount(2);
});

it('mengisi kategori lahan hanya untuk lahan usaha', function () {
    foreach (DummyData::lahan() as $lahan) {
        $peruntukan = PeruntukanLahan::from($lahan['peruntukan_lahan']);

        if ($peruntukan->lahanUsaha()) {
            expect($lahan['kategori_lahan'])->not->toBeNull();
        } else {
            expect($lahan['kategori_lahan'])->toBeNull();
        }
    }
});

it('tidak memakai HPL maupun SHM sebagai status hak perorangan', function () {
    // HPL adalah Hak Pengelolaan milik instansi atas tanah kawasan, sehingga
    // tidak pernah menjadi hak seorang transmigran. SHM adalah nama
    // sertifikatnya, bukan nama haknya. Keduanya kini menjadi jenis dokumen.
    expect(array_column(App\Enums\StatusHakLahan::cases(), 'value'))
        ->not->toContain('HPL')
        ->not->toContain('SHM');

    foreach (DummyData::lahan() as $lahan) {
        expect($lahan['status_hak'])->not->toBe('HPL');
        expect($lahan['status_hak'])->not->toBe('SHM');
    }

    // Sebaliknya, keduanya WAJIB tetap ada sebagai jenis dokumen: SHM sebagai
    // bukti hak milik, HPL sebagai rujukan asal tanah kawasan.
    $dokumen = array_column(App\Enums\JenisDokumenLahan::cases(), 'value');

    expect($dokumen)->toContain('SHM')->toContain('HPL')
        // Sandaran legalitas sebelum sertifikat terbit.
        ->toContain('Surat Keterangan Pembagian Tanah');
});

it('memakai NIK dan nomor KK sepanjang 16 digit', function () {
    foreach (DummyData::transmigran() as $baris) {
        expect(strlen($baris['nik']))->toBe(16)
            ->and(strlen($baris['no_kk']))->toBe(16)
            ->and(ctype_digit($baris['nik']))->toBeTrue()
            ->and(ctype_digit($baris['no_kk']))->toBeTrue();
    }
});

it('menjaga NIK tetap unik antar transmigran', function () {
    $nik = array_column(DummyData::transmigran(), 'nik');

    expect(count($nik))->toBe(count(array_unique($nik)));
});

it('menuliskan nama dan alamat dalam huruf kapital', function () {
    // Middleware UppercaseInput mengubah isian teks jadi huruf besar,
    // sehingga data contoh perlu mencerminkan hasil akhirnya.
    foreach (DummyData::transmigran() as $baris) {
        expect($baris['nama_kepala_keluarga'])->toBe(mb_strtoupper($baris['nama_kepala_keluarga']))
            ->and($baris['pekerjaan_kepala_keluarga'])->toBe(mb_strtoupper($baris['pekerjaan_kepala_keluarga']));
    }
});

/*
|--------------------------------------------------------------------------
| Data dashboard
|--------------------------------------------------------------------------
*/

it('menyediakan deret tahunan dengan panjang seragam', function () {
    $deret = DummyData::deretTahunan();
    $jumlahTahun = count($deret['tahun']);

    foreach ($deret as $nama => $nilai) {
        expect($nilai)->toHaveCount($jumlahTahun, "Deret {$nama} tidak sepanjang deret tahun");
    }
});

it('menaikkan jumlah KK dari tahun ke tahun', function () {
    $kk = DummyData::deretTahunan()['jumlah_kk'];

    for ($i = 1; $i < count($kk); $i++) {
        expect($kk[$i])->toBeGreaterThanOrEqual($kk[$i - 1]);
    }
});

it('menyelaraskan angka ringkasan dengan tahun terakhir deret', function () {
    $ringkasan = DummyData::ringkasanDashboard();
    $deret = DummyData::deretTahunan();

    expect($ringkasan['jumlah_kk'])->toBe(end($deret['jumlah_kk']))
        ->and($ringkasan['jumlah_jiwa'])->toBe(end($deret['jumlah_jiwa']))
        ->and($ringkasan['jumlah_petani'])->toBe(end($deret['jumlah_petani']));
});

it('menjumlahkan rekap per SP mendekati angka ringkasan kawasan', function () {
    $rekap = DummyData::rekapPerSp();
    $ringkasan = DummyData::ringkasanDashboard();

    expect(array_sum(array_column($rekap, 'jumlah_kk')))->toBe($ringkasan['jumlah_kk']);
});

it('menyediakan rekap untuk keenam SP', function () {
    expect(DummyData::rekapPerSp())->toHaveCount(6);

    $namaSp = array_column(DummyData::rekapPerSp(), 'satuan_permukiman');
    $namaAsli = array_column(DummyData::satuanPermukiman(), 'nama');

    sort($namaSp);
    sort($namaAsli);

    expect($namaSp)->toBe($namaAsli);
});

it('menjaga rumah terhuni tidak melebihi jumlah rumah', function () {
    $r = DummyData::ringkasanDashboard();

    expect($r['rumah_terhuni'])->toBeLessThanOrEqual($r['rumah_total']);
});

it('menandai bahwa aplikasi masih memakai data contoh', function () {
    // Penanda ini dipakai layout untuk menampilkan spanduk "Data contoh",
    // memenuhi ANTISLOP-ID R-17 dan R-38.
    expect(DummyData::MEMAKAI_DATA_CONTOH)->toBeTrue();
});

it('menyelaraskan harga rata-rata ringkasan dengan tahun terakhir deret', function () {
    $deret = DummyData::deretTahunan();

    expect(DummyData::ringkasanDashboard()['harga_rata_rata'])->toBe(end($deret['harga_rata_rata']));
});

it('memakai status tinggal yang sah pada rekap penghuni', function () {
    $rekap = DummyData::rekapPenghuni();

    foreach (array_keys($rekap) as $status) {
        expect(StatusTinggal::tryFrom($status))->not->toBeNull("Status {$status} bukan nilai enum yang sah");
    }
});

it('menyamakan jumlah penghuni aktif dengan rumah terhuni', function () {
    // Satu rumah dihuni tepat satu KK (agents/rules.md bagian 6a.5), sehingga
    // banyaknya KK berstatus Aktif harus sama dengan banyaknya rumah terhuni.
    $rekap = DummyData::rekapPenghuni();

    expect($rekap[StatusTinggal::Aktif->value])->toBe(DummyData::ringkasanDashboard()['rumah_terhuni']);
});

/*
|--------------------------------------------------------------------------
| Pengguna sistem
|--------------------------------------------------------------------------
*/

it('menyediakan akun contoh beserta role dan cakupan datanya', function () {
    $pengguna = DummyData::penggunaSaatIni();

    expect($pengguna)->toHaveKeys(['id_user', 'nama', 'username', 'email', 'role'])
        ->and($pengguna['role'])->toHaveKeys(['nama', 'cakupan_data'])
        ->and(App\Enums\CakupanData::tryFrom($pengguna['role']['cakupan_data']))->not->toBeNull();
});

it('memakai username berhuruf kecil sesuai aturan kredensial', function () {
    // Username hanya boleh memuat huruf kecil, angka, titik, dan garis bawah
    // (agents/rules.md bagian 14b poin 5).
    expect(DummyData::penggunaSaatIni()['username'])->toMatch('/^[a-z0-9._]{3,50}$/');
});

it('menyusun inisial dari maksimal dua kata pertama nama', function () {
    expect(DummyData::inisial('NARA WIJAYA'))->toBe('NW')
        ->and(DummyData::inisial('Yohanes Bere Nahak'))->toBe('YB')
        ->and(DummyData::inisial('Maria'))->toBe('M');
});

/*
|--------------------------------------------------------------------------
| Pencarian dan penyaringan per SP
|--------------------------------------------------------------------------
*/

it('mencari satuan permukiman menurut idnya', function () {
    expect(DummyData::cariSp(1)['nama'])->toBe('SP Kapitan Meo')
        ->and(DummyData::cariSp(6)['nama'])->toBe('SP Weain')
        ->and(DummyData::cariSp(99))->toBeNull();
});

it('memakai id rekap yang cocok dengan daftar satuan permukiman', function () {
    // Halaman rincian menaut rekap ke profil SP lewat id ini; bila tidak
    // sepadan, drill-down akan membuka SP yang salah.
    $idRekap = array_column(DummyData::rekapPerSp(), 'satuan_permukiman_id');
    $idSp = array_column(DummyData::satuanPermukiman(), 'id_satuan_permukiman');

    sort($idRekap);
    sort($idSp);

    expect($idRekap)->toBe($idSp);
});

it('menyelaraskan nama rekap dengan nama SP pada id yang sama', function () {
    foreach (DummyData::rekapPerSp() as $baris) {
        expect(DummyData::cariSp($baris['satuan_permukiman_id'])['nama'])
            ->toBe($baris['satuan_permukiman']);
    }
});

it('menyaring daftar menurut nama satuan permukiman', function () {
    $hasil = DummyData::saringPerSp(DummyData::transmigran(), 'SP Kapitan Meo');

    expect($hasil)->not->toBeEmpty();

    foreach ($hasil as $baris) {
        expect($baris['satuan_permukiman'])->toBe('SP Kapitan Meo');
    }
});

it('mengembalikan daftar kosong untuk SP tanpa data', function () {
    expect(DummyData::saringPerSp(DummyData::transmigran(), 'SP Yang Tidak Ada'))->toBe([]);
});

it('menyusun deret tahunan per SP sepanjang deret kawasan', function () {
    $deretSp = DummyData::deretTahunanSp(1);
    $jumlahTahun = count(DummyData::deretTahunan()['tahun']);

    expect($deretSp['tahun'])->toHaveCount($jumlahTahun)
        ->and($deretSp['jumlah_kk'])->toHaveCount($jumlahTahun)
        ->and($deretSp['volume_panen'])->toHaveCount($jumlahTahun);
});

it('menjaga deret SP tidak melebihi deret kawasan', function () {
    // Satu SP adalah bagian dari kawasan, sehingga angkanya mustahil lebih besar.
    $kawasan = DummyData::deretTahunan();
    $deretSp = DummyData::deretTahunanSp(1);

    foreach ($deretSp['jumlah_kk'] as $i => $nilai) {
        expect($nilai)->toBeLessThanOrEqual($kawasan['jumlah_kk'][$i]);
    }
});

it('mengembalikan deret kosong untuk SP yang tidak ada', function () {
    expect(DummyData::deretTahunanSp(99)['tahun'])->toBe([]);
});

