<?php

/**
 * Uji penyedia data contoh.
 *
 * Data contoh dipakai membangun seluruh halaman Tahap 2. Bila strukturnya
 * tidak sesuai kamus data, penggantian ke data nyata pada tahap backend akan
 * memaksa pembongkaran berkas Blade. Uji ini menjaga hal itu tidak terjadi.
 */

use App\Enums\BidangPengaduan;
use App\Enums\CakupanData;
use App\Enums\JenisDokumenLahan;
use App\Enums\JenisInfrastruktur;
use App\Enums\JenisReferensi;
use App\Enums\JenisSaprotan;
use App\Enums\KategoriPengaduan;
use App\Enums\Kondisi;
use App\Enums\KondisiRumah;
use App\Enums\PeruntukanLahan;
use App\Enums\PrioritasPengaduan;
use App\Enums\StatusHunian;
use App\Enums\StatusPanen;
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
            ->and(SumberLaporan::dari($baris['sumber_laporan']))->not->toBeNull();

        // Bidang boleh kosong, artinya belum ditetapkan petugas. Bila terisi,
        // nilainya wajib dikenali enum.
        if (! empty($baris['bidang'])) {
            expect(BidangPengaduan::dari($baris['bidang']))->not->toBeNull();
        }
    }
});

it('memakai nilai enum yang sah pada data infrastruktur', function () {
    foreach (DummyData::infrastruktur() as $baris) {
        expect(JenisInfrastruktur::dari($baris['jenis']))->not->toBeNull()
            ->and(Kondisi::dari($baris['kondisi']))->not->toBeNull();
    }
});

it('menetapkan bidang pengaduan sesuai kategorinya', function () {
    // Kategori netral dilewati: bidangnya ditetapkan petugas berdasarkan isi
    // laporan, sehingga tidak dapat diadu dengan nilai turunan mana pun
    // (rules.md 10b poin 7a).
    foreach (DummyData::pengaduan() as $baris) {
        $bidangSeharusnya = BidangPengaduan::dariKategori($baris['kategori']);

        if ($bidangSeharusnya === null) {
            continue;
        }

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
        $id = $lahan['transmigran_id'];
        $perKeluarga[$id][$kunci] = ($perKeluarga[$id][$kunci] ?? 0) + 1;
    }

    foreach ($perKeluarga as $id => $jumlah) {
        expect($jumlah['pekarangan'] ?? 0)->toBeLessThanOrEqual(1, "transmigran {$id} punya lebih dari satu pekarangan");
        expect($jumlah['usaha'] ?? 0)->toBeLessThanOrEqual(1, "transmigran {$id} punya lebih dari satu lahan usaha");
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

it('memecah luas lahan usaha menjadi bagian kering dan basah', function () {
    // Aturan rules.md 7.5: keduanya KOMPOSISI, bukan kategori. Jumlahnya wajib
    // sama dengan luas bidang, dan hanya lahan usaha yang memilikinya.
    foreach (DummyData::lahan() as $lahan) {
        $peruntukan = PeruntukanLahan::from($lahan['peruntukan_lahan']);
        $kode = $lahan['kode_lahan'];

        if (! $peruntukan->lahanUsaha()) {
            expect($lahan['luas_kering'])->toBeNull("{$kode} pekarangan tidak boleh berkomposisi");
            expect($lahan['luas_basah'])->toBeNull("{$kode} pekarangan tidak boleh berkomposisi");

            continue;
        }

        expect($lahan['luas_kering'])->not->toBeNull("{$kode} lahan usaha wajib berkomposisi");
        expect($lahan['luas_basah'])->not->toBeNull("{$kode} lahan usaha wajib berkomposisi");

        // Dibandingkan sebagai nilai berpembulatan dua angka, sesuai
        // DECIMAL(12,2) pada kamus data. Membandingkan float mentah akan
        // memerah karena ekor pecahan biner, bukan karena datanya salah.
        expect(round($lahan['luas_kering'] + $lahan['luas_basah'], 2))
            ->toBe(round($lahan['luas'], 2), "{$kode} komposisinya tidak berjumlah luas bidang");
    }
});

it('menyediakan satu bidang berkomposisi campuran pada data contoh', function () {
    // Bidang campuran adalah SELURUH ALASAN kolom ini dipecah. Tanpa satu pun
    // contohnya, tampilan komposisi tidak pernah teruji pada keadaan yang
    // membedakannya dari kolom enum lama, dan tidak ada yang akan menyadari
    // bila penjumlahannya keliru.
    $campuran = array_filter(
        DummyData::lahan(),
        fn ($l) => (float) ($l['luas_kering'] ?? 0) > 0 && (float) ($l['luas_basah'] ?? 0) > 0
    );

    expect($campuran)->not->toBeEmpty('data contoh wajib memuat bidang kering sekaligus basah');
});

it('mempersempit jenis dokumen lahan menjadi HPL dan SHM', function () {
    // Dipersempit 2026-08-20 atas keputusan pemilik proyek: pendataan di
    // Kobalima Timur hanya mengenal kedua berkas ini.
    $dokumen = array_column(JenisDokumenLahan::cases(), 'value');

    expect($dokumen)->toBe(['HPL', 'SHM']);

    // Status hak atas tanah dicabut seluruhnya pada tanggal yang sama. Enumnya
    // ikut dihapus, sehingga HPL dan SHM tidak mungkin lagi dipakai sebagai
    // status hak perorangan - kekeliruan yang diperbaiki 2026-08-18.
    expect(enum_exists('App\Enums\StatusHakLahan'))->toBeFalse();

    foreach (DummyData::lahan() as $lahan) {
        expect($lahan)->not->toHaveKey('status_hak');
    }
});

it('menautkan lahan ke pemiliknya lewat id, bukan nama', function () {
    // Dua kepala keluarga dapat bernama sama. Pencocokan nama akan menautkan
    // bidang ke profil orang yang keliru tanpa ada yang menyadarinya.
    $idTransmigran = array_column(DummyData::transmigran(), 'id_transmigran');

    foreach (DummyData::lahan() as $lahan) {
        expect($lahan)->toHaveKey('transmigran_id');
        expect(in_array($lahan['transmigran_id'], $idTransmigran, true))
            ->toBeTrue("{$lahan['kode_lahan']} menunjuk transmigran yang tidak ada");
    }
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

/*
|--------------------------------------------------------------------------
| Data per tahun untuk pemilih tahun tunggal (Putaran 5)
|--------------------------------------------------------------------------
|
| Rekap Indikator Kawasan & Monografi SP dapat dilihat "keadaan tahun X".
| Irisan tahun terakhir WAJIB tepat sama dengan sumber yang sudah ada,
| supaya pemilih tahun tidak diam-diam mengubah angka bawaan.
*/

it('menyamakan indikator kawasan tahun terakhir dengan ringkasan dashboard', function () {
    $tahunAkhir = (int) end(DummyData::deretTahunan()['tahun']);
    $tahun = DummyData::indikatorKawasanTahun()[$tahunAkhir];
    $r = DummyData::ringkasanDashboard();

    foreach ($tahun as $kunci => $nilai) {
        expect(round((float) $nilai, 3))->toBe(round((float) $r[$kunci], 3), "indikator {$kunci} tahun terakhir menyimpang dari ringkasan");
    }

    // Lima tahun disediakan, seluruhnya di dalam deret dashboard.
    expect(array_keys(DummyData::indikatorKawasanTahun()))->toBe(DummyData::tahunLaporan());
});

it('menyamakan rekap per SP tahun terakhir dengan rekapPerSp apa adanya', function () {
    $tahunAkhir = (int) end(DummyData::deretTahunan()['tahun']);

    expect(DummyData::rekapPerSpTahun($tahunAkhir))->toBe(DummyData::rekapPerSp());
});

it('menjumlahkan rekap per SP tiap tahun tepat ke angka kawasan tahun itu', function () {
    $peta = [
        'jumlah_kk' => 'jumlah_kk',
        'rumah_terhuni' => 'rumah_terhuni',
        'luas_lahan' => 'luas_lahan_total',
        'volume_panen' => 'volume_panen_ton',
        'pengaduan_terbuka' => 'pengaduan_terbuka',
    ];

    foreach (DummyData::tahunLaporan() as $tahun) {
        $perSp = DummyData::rekapPerSpTahun($tahun);
        $kawasan = DummyData::indikatorKawasanTahun()[$tahun];

        expect($perSp)->toHaveCount(6);

        foreach ($peta as $kolomSp => $kolomKawasan) {
            $jumlah = round(array_sum(array_column($perSp, $kolomSp)), 2);
            expect($jumlah)->toBe(round((float) $kawasan[$kolomKawasan], 2), "kolom {$kolomSp} tahun {$tahun} tidak menjumlah ke angka kawasan");
        }
    }
});

it('mempertahankan iklim SP tahun terakhir dan menggoyangnya secara deterministik', function () {
    $tahunAkhir = (int) end(DummyData::deretTahunan()['tahun']);

    // Tahun terakhir == nilai dasar (subset field iklim keadaanWilayahSp).
    $iklim2026 = DummyData::iklimSpTahun(1, $tahunAkhir);
    expect($iklim2026)->toHaveKey('curah_hujan_tahunan_mm')
        ->and($iklim2026)->toHaveKey('suhu_rata_c');
    expect($iklim2026)->not->toHaveKey('batas_utara');   // geografi tidak ikut

    // Deterministik: dua panggilan identik.
    expect(DummyData::iklimSpTahun(3, 2023))->toBe(DummyData::iklimSpTahun(3, 2023));

    // Tahun lampau berbeda dari tahun terakhir untuk minimal satu field.
    expect(DummyData::iklimSpTahun(1, 2022))->not->toBe($iklim2026);
});

it('membagi jiwa kawasan ke enam SP tepat sama dengan ringkasan dashboard', function () {
    $jiwa = DummyData::jiwaPerSp();

    expect($jiwa)->toHaveCount(6)
        ->and(array_sum($jiwa))->toBe(DummyData::ringkasanDashboard()['jumlah_jiwa']);
});

it('mengarang struktur umur SP yang jumlahnya tepat sama dengan jiwa SP', function () {
    foreach (array_keys(DummyData::jiwaPerSp()) as $id) {
        $struktur = DummyData::strukturUmurSp($id);
        $jiwaSp = DummyData::jiwaPerSp()[$id];

        expect($struktur)->toHaveCount(14, "SP {$id} bukan 14 kelompok umur");

        $totalSel = 0;
        foreach ($struktur as $b) {
            expect($b['laki'])->toBeGreaterThanOrEqual(0)
                ->and($b['perempuan'])->toBeGreaterThanOrEqual(0)
                ->and($b['jumlah'])->toBe($b['laki'] + $b['perempuan']);
            $totalSel += $b['jumlah'];
        }

        expect($totalSel)->toBe($jiwaSp, "SP {$id} struktur umur tidak menutup jiwa");

        // Deterministik.
        expect(DummyData::strukturUmurSp($id))->toBe($struktur);
    }
});

it('menghitung mutasi penduduk SP secara kumulatif tanpa perkawinan', function () {
    foreach (array_keys(DummyData::jiwaPerSp()) as $id) {
        $mutasi = DummyData::mutasiPendudukSp($id);

        expect($mutasi)->toHaveKeys(['baris', 'bersih']);

        $jenis = array_column($mutasi['baris'], 'jenis');
        expect($jenis)->not->toContain('Perkawinan')
            ->and($jenis)->toContain('Kelahiran')
            ->and($jenis)->toContain('Kematian');

        // Pertambahan bersih = (lahir + datang) - (mati + pindah + keluar).
        $peta = array_column($mutasi['baris'], 'jumlah', 'jenis');
        $bersihHitung = $peta['Kelahiran'] + $peta['Transmigran datang (pengganti atau spontan)']
            - $peta['Kematian'] - $peta['Pindah keluar keluarga'] - $peta['Meninggalkan lokasi'];
        expect($mutasi['bersih'])->toBe($bersihHitung);

        // Deterministik.
        expect(DummyData::mutasiPendudukSp($id))->toBe($mutasi);
    }

    // Peristiwa anggota keluarga yang tercatat (SP 1) ikut terhitung pada
    // baris Kematian dan Pindah.
    $sp1 = array_column(DummyData::mutasiPendudukSp(1)['baris'], 'jumlah', 'jenis');
    expect($sp1['Kematian'])->toBeGreaterThan(0)
        ->and($sp1['Pindah keluar keluarga'])->toBeGreaterThan(0);
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
        ->and(CakupanData::tryFrom($pengguna['role']['cakupan_data']))->not->toBeNull();
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

/*
|--------------------------------------------------------------------------
| Alsintan: satu pengadaan dibagikan ke banyak poktan (Putaran 7)
|--------------------------------------------------------------------------
*/

it('memisahkan pengadaan alsintan dari distribusinya ke poktan', function () {
    // Model lama: satu poktan_id pada baris pengadaan, sehingga satu batch
    // ke tiga poktan harus diketik jadi tiga baris. Kini pengadaan hanya
    // mendeskripsikan bendanya; poktan penerima ada di baris distribusi.
    foreach (DummyData::alsintan() as $a) {
        expect($a)->toHaveKeys(['jenis_alsintan', 'nama_alat', 'jumlah_total', 'distribusi', 'jumlah_tersalur', 'jumlah_belum_tersalur'])
            ->and($a)->not->toHaveKey('poktan_id')
            ->and($a)->not->toHaveKey('kondisi');

        // Jenis alsintan wajib nilai data master yang sah.
        expect(array_keys(DummyData::opsiReferensi(JenisReferensi::JenisAlsintan)))
            ->toContain($a['jenis_alsintan']);

        // Sigma distribusi tidak melebihi jumlah total; sisa terhitung benar.
        $tersalur = array_sum(array_column($a['distribusi'], 'jumlah'));
        expect($tersalur)->toBeLessThanOrEqual($a['jumlah_total'])
            ->and($a['jumlah_tersalur'])->toBe($tersalur)
            ->and($a['jumlah_belum_tersalur'])->toBe($a['jumlah_total'] - $tersalur);
    }

    // Data contoh WAJIB memuat kasus yang model lama tidak sanggup:
    // satu pengadaan dibagikan ke lebih dari satu poktan, lintas SP.
    $lintas = collect(DummyData::alsintan())->first(function ($a) {
        $sp = array_unique(array_column($a['distribusi'], 'satuan_permukiman_id'));

        return count($a['distribusi']) > 1 && count($sp) > 1;
    });
    expect($lintas)->not->toBeNull('data contoh wajib memuat pengadaan lintas SP');

    // Dan satu pengadaan yang belum tersalurkan sama sekali.
    $belum = collect(DummyData::alsintan())->first(fn ($a) => count($a['distribusi']) === 0);
    expect($belum)->not->toBeNull('data contoh wajib memuat pengadaan belum tersalurkan')
        ->and($belum['jumlah_belum_tersalur'])->toBe($belum['jumlah_total']);
});

it('menautkan tiap distribusi alsintan ke pengadaan dan poktan yang sah', function () {
    $idPengadaan = array_column(DummyData::alsintan(), 'id_alsintan');
    $idPoktan = array_column(DummyData::poktan(), 'id_poktan');

    foreach (DummyData::alsintanDistribusi() as $d) {
        expect($idPengadaan)->toContain($d['alsintan_id'])
            ->and($idPoktan)->toContain($d['poktan_id'])
            ->and($d['satuan_permukiman_id'])->not->toBeNull()
            ->and($d['jumlah'])->toBeGreaterThan(0);

        // SP mengikuti poktan, tidak dipilih terpisah (rules.md §7b poin 3).
        $poktan = collect(DummyData::poktan())->firstWhere('id_poktan', $d['poktan_id']);
        expect($d['satuan_permukiman_id'])->toBe($poktan['satuan_permukiman_id']);

        // Penanda tangan, bila ada, anggota poktan yang sama.
        if ($d['penanda_terima_id'] !== null) {
            $anggota = collect(DummyData::anggotaPoktan())->firstWhere('id_anggota_poktan', $d['penanda_terima_id']);
            expect($anggota)->not->toBeNull()
                ->and($anggota['poktan_id'])->toBe($d['poktan_id']);
        }
    }
});

it('membiarkan satu dokumen lahan mencakup banyak bidang', function () {
    // Putaran 7: satu HPL / SK pencadangan lazim mencakup ratusan bidang.
    // Model lama membawa satu lahan_id per baris, memaksa dokumen yang sama
    // diketik ulang dan berkasnya diunggah ulang per bidang.
    $idLahan = array_column(DummyData::lahan(), 'id_lahan');

    foreach (DummyData::dokumenLahan() as $d) {
        expect($d)->toHaveKey('lahan_ids')
            ->and($d['lahan_ids'])->not->toBeEmpty()
            // Kompatibilitas: lahan_id tunggal = bidang pertama.
            ->and($d['lahan_id'])->toBe($d['lahan_ids'][0]);

        foreach ($d['lahan_ids'] as $lid) {
            expect($idLahan)->toContain($lid);
        }
    }

    // Data contoh wajib memuat satu dokumen lintas bidang.
    $lintas = collect(DummyData::dokumenLahan())->first(fn ($d) => count($d['lahan_ids']) > 1);
    expect($lintas)->not->toBeNull('data contoh wajib memuat dokumen lahan lintas bidang');

    // Penyaringan per bidang mengembalikan dokumen yang mencakupnya.
    foreach ($lintas['lahan_ids'] as $lid) {
        expect(collect(DummyData::dokumenLahan($lid))->pluck('id_dokumen_lahan'))
            ->toContain($lintas['id_dokumen_lahan']);
    }

    // Tabel penghubung menautkan ke dokumen yang sah.
    $idDok = array_column(DummyData::dokumenLahan(), 'id_dokumen_lahan');
    foreach (DummyData::dokumenLahanBidang() as $b) {
        expect($idDok)->toContain($b['dokumen_lahan_id'])
            ->and($idLahan)->toContain($b['lahan_id']);
    }
});

/*
|--------------------------------------------------------------------------
| Saprotan: kaitan benih ke komoditas dan sisa stok
|--------------------------------------------------------------------------
*/

it('mewajibkan komoditas pada benih dan mengosongkannya pada jenis lain', function () {
    // Benih selalu benih SESUATU. Tanpa kolom ini kaitannya hanya tersirat
    // dari teks namanya, sehingga sistem tidak tahu "BENIH JAGUNG HIBRIDA"
    // itu benih jagung dan form penanaman tidak dapat menyaringnya.
    //
    // Sebaliknya pupuk dan pestisida sengaja TIDAK berkomoditas: urea dipakai
    // tanaman apa pun, dan mengisinya berarti mengarang data.
    foreach (DummyData::saprotan() as $baris) {
        expect($baris)->toHaveKey('komoditas_id');

        if ($baris['jenis'] === JenisSaprotan::Benih->value) {
            expect($baris['komoditas_id'])->not->toBeNull("benih {$baris['nama']} tanpa komoditas");

            // Menunjuk komoditas yang benar-benar ada, bukan id karangan.
            $komoditas = collect(DummyData::komoditas())
                ->firstWhere('id_komoditas', $baris['komoditas_id']);

            expect($komoditas)->not->toBeNull()
                ->and($baris['komoditas'])->toBe($komoditas['nama']);
        } else {
            expect($baris['komoditas_id'])->toBeNull("{$baris['jenis']} {$baris['nama']} tidak boleh berkomoditas");
        }
    }
});

it('menghitung sisa benih per DISTRIBUSI dari jatah dikurangi pemakaian penanaman', function () {
    // Grain berpindah ke distribusi (Putaran 7): jatah satu poktan dikurangi
    // hanya penanaman poktan itu sendiri. Rumusnya tetap satu pengurangan
    // yang mengoreksi diri sendiri saat baris penanaman disunting.
    foreach (DummyData::saprotanDistribusi() as $d) {
        if ($d['jenis'] !== JenisSaprotan::Benih->value) {
            expect(DummyData::sisaBenih($d['id_saprotan_distribusi']))->toBe(0.0);

            continue;
        }

        $terpakai = 0.0;
        foreach (DummyData::penanaman() as $tanam) {
            if (($tanam['saprotan_distribusi_id'] ?? null) === $d['id_saprotan_distribusi']) {
                $terpakai += (float) ($tanam['volume_benih'] ?? 0);
            }
        }

        $harapan = max(0.0, round((float) $d['jumlah'] - $terpakai, 3));
        expect(DummyData::sisaBenih($d['id_saprotan_distribusi']))->toBe($harapan)
            ->and($d['sisa_benih'])->toBe($harapan);
    }

    // Baris yang tidak ada tetap menjawab angka, bukan melempar galat.
    expect(DummyData::sisaBenih(9999))->toBe(0.0);
});

it('menjumlahkan distribusi saprotan tidak melebihi jumlah total pengadaan', function () {
    foreach (DummyData::saprotan() as $s) {
        $tersalur = round(array_sum(array_column($s['distribusi'], 'jumlah')), 3);

        expect($tersalur)->toBeLessThanOrEqual($s['jumlah_total'], "distribusi {$s['nama']} melebihi total")
            ->and($s['jumlah_tersalur'])->toBe($tersalur)
            ->and($s['jumlah_belum_tersalur'])->toBe(max(0.0, round($s['jumlah_total'] - $tersalur, 3)));
    }

    // Data contoh WAJIB memuat pengadaan yang dibagikan ke > 1 poktan.
    $banyak = collect(DummyData::saprotan())->first(fn ($s) => count($s['distribusi']) > 1);
    expect($banyak)->not->toBeNull('data contoh wajib memuat pengadaan saprotan ke banyak poktan');

    // Dan satu pengadaan yang belum tersalur seluruhnya (barang di gudang UPT).
    $sebagian = collect(DummyData::saprotan())->first(fn ($s) => $s['jumlah_belum_tersalur'] > 0);
    expect($sebagian)->not->toBeNull('data contoh wajib memuat pengadaan yang belum tersalur penuh');
});

it('menyembunyikan benih yang stoknya habis dari daftar tersedia', function () {
    // INTI ATURAN STOK. Benih habis sekali pakai, tetapi penguncian terjadi
    // ketika STOKNYA HABIS, bukan ketika pertama kali dipakai (penanaman
    // bertahap: 3 ha lalu 7 ha dari jatah yang sama). Grain kini distribusi.
    $habis = collect(DummyData::saprotanDistribusi())
        ->first(fn ($d) => $d['jenis'] === JenisSaprotan::Benih->value
            && DummyData::sisaBenih($d['id_saprotan_distribusi']) <= 0);

    expect($habis)->not->toBeNull('data contoh wajib memuat satu distribusi benih yang habis');

    $tersedia = collect(DummyData::benihTersedia())->pluck('id_saprotan_distribusi');
    expect($tersedia)->not->toContain($habis['id_saprotan_distribusi']);

    $bersisa = collect(DummyData::saprotanDistribusi())
        ->first(fn ($d) => $d['jenis'] === JenisSaprotan::Benih->value
            && DummyData::sisaBenih($d['id_saprotan_distribusi']) > 0);
    expect($tersedia)->toContain($bersisa['id_saprotan_distribusi']);
});

it('menyaring benih tersedia menurut poktan dan komoditasnya', function () {
    // Inilah yang membuat petugas tidak dapat memilih benih padi untuk
    // penanaman jagung, maupun memakai jatah poktan lain.
    foreach (DummyData::benihTersedia(1, 1) as $benih) {
        expect($benih['poktan_id'])->toBe(1)
            ->and($benih['komoditas_id'])->toBe(1)
            ->and($benih['jenis'])->toBe(JenisSaprotan::Benih->value);
    }

    // Poktan yang jatah benihnya sudah habis menerima daftar kosong.
    expect(DummyData::benihTersedia(3))->toBe([]);

    foreach (DummyData::benihTersedia() as $benih) {
        expect($benih)->toHaveKey('sisa_benih')
            ->and($benih['label_benih'])->toContain('sisa')
            ->and($benih['label_benih'])->toContain($benih['nama']);
    }
});

it('menautkan volume benih penanaman ke baris distribusi saprotan yang sah', function () {
    foreach (DummyData::penanaman() as $tanam) {
        expect($tanam['saprotan_distribusi_id'])->not->toBeNull("penanaman {$tanam['id_penanaman']} tanpa benih")
            ->and($tanam['volume_benih'])->not->toBeNull();

        $benih = collect(DummyData::saprotanDistribusi())
            ->firstWhere('id_saprotan_distribusi', $tanam['saprotan_distribusi_id']);

        expect($benih)->not->toBeNull()
            ->and($benih['jenis'])->toBe(JenisSaprotan::Benih->value)
            ->and($tanam['volume_benih'])->toBeGreaterThan(0)
            // Komoditas benih wajib cocok dengan komoditas yang ditanam.
            ->and($benih['komoditas'])->toBe($tanam['komoditas'])
            // Benih milik poktan yang menanam (batas #33 kamus data).
            ->and($benih['poktan_id'])->toBe($tanam['poktan_id']);
    }
});

it('menjaga pemakaian benih tidak melebihi jatah distribusi poktan', function () {
    foreach (DummyData::saprotanDistribusi() as $d) {
        if ($d['jenis'] !== JenisSaprotan::Benih->value) {
            continue;
        }

        $terpakai = 0.0;
        foreach (DummyData::penanaman() as $tanam) {
            if (($tanam['saprotan_distribusi_id'] ?? null) === $d['id_saprotan_distribusi']) {
                $terpakai += (float) ($tanam['volume_benih'] ?? 0);
            }
        }

        expect($terpakai)->toBeLessThanOrEqual(
            (float) $d['jumlah'],
            "pemakaian {$d['nama']} oleh {$d['poktan']} melebihi jatahnya"
        );
    }
});

/*
|--------------------------------------------------------------------------
| Penanaman berpusat pada poktan
|--------------------------------------------------------------------------
*/

it('memusatkan penanaman pada poktan, bukan lahan perorangan', function () {
    // Seluruh pencatatan Produksi Pertanian berpusat pada kelompok, dan
    // lapangan membenarkannya: laporan Polri MT.II 2025 mencatat satu baris
    // per POKTAN, bukan per bidang lahan.
    foreach (DummyData::penanaman() as $baris) {
        expect($baris)->toHaveKey('poktan_id')
            ->and($baris)->not->toHaveKey('lahan_id')
            ->and($baris)->not->toHaveKey('petani')
            ->and($baris)->not->toHaveKey('luas_tanam');

        // Menunjuk poktan yang benar-benar ada.
        $poktan = collect(DummyData::poktan())->firstWhere('id_poktan', $baris['poktan_id']);

        expect($poktan)->not->toBeNull()
            ->and($baris['poktan'])->toBe($poktan['nama']);

        // Rantai lokasi tetap utuh tanpa lahan: penanaman -> poktan -> SP.
        expect($baris['satuan_permukiman_id'])->toBe($poktan['satuan_permukiman_id']);
    }
});

it('menghitung kekuatan poktan dari anggota aktif dan lahannya', function () {
    // Angka yang ditulis tangan menjadi basi begitu satu anggota keluar atau
    // satu bidang lahan dibetulkan, dan kebasian itu tidak pernah memerahkan
    // apa pun. Kolom `luas_lahan_kelompok` sudah dicabut karena alasan itu.
    foreach (DummyData::poktan() as $poktan) {
        $rekap = DummyData::rekapLahanPoktan($poktan['id_poktan']);

        expect($rekap)->toHaveKeys(['jumlah_anggota', 'luas_kering', 'luas_basah', 'luas_total']);

        // Total selalu jumlah keduanya, tidak pernah dihitung terpisah.
        expect($rekap['luas_total'])->toBe(round($rekap['luas_kering'] + $rekap['luas_basah'], 2));

        // Anggota yang sudah keluar TIDAK dihitung: lahannya tidak lagi
        // digarap kelompok ini.
        $aktif = collect(DummyData::anggotaPoktan($poktan['id_poktan']))
            ->where('status', 'Aktif')
            ->count();

        expect($rekap['jumlah_anggota'])->toBeGreaterThanOrEqual($aktif);
    }

    // Poktan yang tidak ada menjawab nol, bukan melempar galat.
    expect(DummyData::rekapLahanPoktan(9999)['jumlah_anggota'])->toBe(0);
});

it('mengembalikan lahan poktan setelah panennya tercatat', function () {
    // BEDA SIFAT dari sisa benih, dan perbedaan itu disengaja: benih habis
    // selamanya begitu ditabur, sedangkan lahan kembali tersedia setelah
    // dipanen. Menghitung seluruh penanaman sepanjang sejarah akan membuat
    // lahan poktan tampak habis setelah beberapa musim, padahal bidang yang
    // sama memang ditanami berulang kali tiap tahun.
    // Yang masih menahan lahan hanyalah penanaman yang BELUM DIPANEN sama
    // sekali. Kriteria itu disederhanakan kembali 2026-08-24: penghalusan
    // "sisa luasnya nol" dahulu dibuat untuk menangani panen bertahap, dan
    // panen bertahap kini dicabut.
    foreach (DummyData::poktan() as $poktan) {
        $id = $poktan['id_poktan'];
        $rekap = DummyData::rekapLahanPoktan($id);

        $belum = 0.0;

        foreach (DummyData::penanaman() as $tanam) {
            if ($tanam['poktan_id'] === $id
                && DummyData::statusPanen($tanam['id_penanaman']) === StatusPanen::BelumDipanen) {
                $belum += (float) $tanam['realisasi_tanam'];
            }
        }

        $harapan = max(0.0, round($rekap['luas_total'] - $belum, 2));

        expect(DummyData::lahanTersedia($id))->toBe($harapan);
    }
});

it('tidak pernah mengembalikan lahan tersedia bertanda minus', function () {
    foreach (DummyData::poktan() as $poktan) {
        expect(DummyData::lahanTersedia($poktan['id_poktan']))->toBeGreaterThanOrEqual(0.0);
    }

    expect(DummyData::lahanTersedia(9999))->toBe(0.0);
});

it('menautkan hasil panen ke penanaman lewat id, bukan pencocokan teks', function () {
    // Pencocokan lewat pasangan komoditas dan petani menyatukan dua penanaman
    // berbeda yang kebetulan sama, sehingga volumenya terhitung dua kali.
    foreach (DummyData::hasilPanen() as $panen) {
        expect($panen)->toHaveKey('penanaman_id');

        $tanam = collect(DummyData::penanaman())
            ->firstWhere('id_penanaman', $panen['penanaman_id']);

        expect($tanam)->not->toBeNull("panen {$panen['id_hasil_panen']} menunjuk penanaman yang tidak ada");

        // Komoditas dan poktannya wajib sejalan dengan penanamannya, sebab
        // panen memang kelanjutan dari penanaman itu.
        expect($panen['komoditas'])->toBe($tanam['komoditas'])
            ->and($panen['poktan_id'])->toBe($tanam['poktan_id']);
    }
});

it('menjaga realisasi tanam tidak melebihi luas lahan poktannya', function () {
    // Sebelum kolom ini berpusat pada poktan, tidak ada apa pun yang mencegah
    // satu kelompok mencatat penanaman melebihi lahan yang dimilikinya.
    foreach (DummyData::poktan() as $poktan) {
        $luas = DummyData::rekapLahanPoktan($poktan['id_poktan'])['luas_total'];

        foreach (DummyData::penanaman() as $tanam) {
            if ($tanam['poktan_id'] !== $poktan['id_poktan']) {
                continue;
            }

            expect((float) $tanam['realisasi_tanam'])->toBeLessThanOrEqual(
                $luas,
                "penanaman {$tanam['id_penanaman']} melebihi lahan {$poktan['nama']}"
            );
        }
    }
});

/*
|--------------------------------------------------------------------------
| Hasil panen: dua identitas aritmetika
|--------------------------------------------------------------------------
*/

it('menjaga hasil panen ditambah puso sama dengan realisasi tanam', function () {
    // IDENTITAS DUA SUKU (diubah 2026-08-24). Suku "belum dipanen" dicabut
    // bersama panen bertahap, atas keterangan pemilik proyek: satu penanaman
    // hanya bisa satu panen, dan realisasi tanam 2 ha berarti panen ditambah
    // puso juga tepat 2 ha.
    //
    // Bentuk ini pula yang dipakai laporan lapangan rujukan, yang kolomnya
    // memang hanya Realisasi Tanam, Realisasi Panen, dan Puso.
    foreach (DummyData::penanaman() as $tanam) {
        $panen = 0.0;
        $puso = 0.0;
        $ada = false;

        foreach (DummyData::hasilPanen() as $p) {
            if (($p['penanaman_id'] ?? null) !== $tanam['id_penanaman']) {
                continue;
            }

            $ada = true;
            $panen += (float) $p['realisasi_panen'];
            $puso += (float) ($p['puso'] ?? 0);
        }

        // Penanaman yang belum dipanen tidak dituntut apa pun.
        if (! $ada) {
            continue;
        }

        expect(round($panen + $puso, 2))->toBe(round((float) $tanam['realisasi_tanam'], 2),
            "penanaman {$tanam['id_penanaman']} tidak tertutup habis");
    }
});

it('menjaga satu penanaman hanya memiliki satu panen', function () {
    // Panen bertahap dicabut 2026-08-24. Dua baris panen pada penanaman yang
    // sama berarti luasnya terhitung dua kali pada rekap, dan itu tidak akan
    // memerahkan apa pun tanpa uji ini.
    $cacah = [];

    foreach (DummyData::hasilPanen() as $p) {
        $id = $p['penanaman_id'];
        $cacah[$id] = ($cacah[$id] ?? 0) + 1;
    }

    foreach ($cacah as $id => $jumlah) {
        expect($jumlah)->toBe(1, "penanaman {$id} memiliki lebih dari satu panen");
    }
});

it('menjaga produksi sama dengan hasil panen dikali produktivitas', function () {
    // Identitas kedua. Produksi tetap DISIMPAN meski dapat dihitung: ia
    // angka yang dilaporkan ke dinas, dan pembulatan hasil perkalian dapat
    // berbeda tipis dari angka yang benar-benar ditimbang.
    foreach (DummyData::hasilPanen() as $p) {
        $hitung = (float) $p['realisasi_panen'] * (float) $p['produktivitas'];

        expect(abs($hitung - (float) $p['produksi']))->toBeLessThan(0.01,
            "panen {$p['id_hasil_panen']} tidak sejalan dengan produktivitasnya");
    }
});

/*
|--------------------------------------------------------------------------
| Status panen: diturunkan, tidak disimpan
|--------------------------------------------------------------------------
*/

it('menyimpulkan status panen dari ada tidaknya catatan panen', function () {
    // DITURUNKAN agar tidak pernah basi. Menyimpannya sebagai kolom berarti
    // nilainya menjadi salah begitu satu baris panen dihapus.
    foreach (DummyData::penanaman() as $tanam) {
        expect($tanam)->not->toHaveKey('status_panen');

        $id = $tanam['id_penanaman'];

        $adaPanen = collect(DummyData::hasilPanen())
            ->contains(fn ($p) => ($p['penanaman_id'] ?? null) === $id);

        $harusnya = $adaPanen ? StatusPanen::SelesaiDipanen : StatusPanen::BelumDipanen;

        expect(DummyData::statusPanen($id))->toBe($harusnya, "penanaman {$id}");
    }
});

it('meniadakan status Dipanen Sebagian', function () {
    // Dicabut 2026-08-24 bersama panen bertahap: keadaan itu tidak lagi
    // mungkin ada, sebab satu panen selalu menutup seluruh luas yang ditanam.
    expect(StatusPanen::cases())->toHaveCount(2);

    foreach (StatusPanen::cases() as $status) {
        expect($status->value)->not->toContain('Sebagian');
    }

    // Metode penyaring khusus daftar panen ikut gugur: seluruh barisnya kini
    // berstatus sama, sehingga penyaringnya tidak menyaring apa pun.
    expect(method_exists(StatusPanen::class, 'penyaringPanen'))->toBeFalse();
});

it('menyediakan contoh untuk kedua status agar seluruhnya dapat ditinjau', function () {
    // Status yang tidak punya benda nyata tidak akan pernah tampil di layar,
    // dan ujinya hanya menguji dirinya sendiri.
    $terpakai = collect(DummyData::penanaman())
        ->map(fn ($t) => DummyData::statusPanen($t['id_penanaman'])->value)
        ->unique()
        ->sort()
        ->values()
        ->all();

    expect($terpakai)->toBe(collect(StatusPanen::cases())
        ->map(fn ($s) => $s->value)
        ->sort()
        ->values()
        ->all());
});

it('menganggap penanaman yang gagal total sebagai selesai dipanen', function () {
    // GAGAL TOTAL: panen 0 ha, puso menutup seluruh luas. Keadaan sah, dan
    // statusnya tetap Selesai Dipanen sebab barisnya ada - yang membedakan
    // kolom puso, bukan status.
    $gagalTotal = collect(DummyData::hasilPanen())
        ->filter(fn ($p) => (float) $p['realisasi_panen'] === 0.0 && (float) ($p['puso'] ?? 0) > 0);

    // Sekurang-kurangnya satu contoh wajib ada, agar cabang ini teruji.
    expect($gagalTotal)->not->toBeEmpty();

    foreach ($gagalTotal as $p) {
        expect(DummyData::statusPanen($p['penanaman_id']))->toBe(StatusPanen::SelesaiDipanen);

        // Produktivitas dan produksi nol, dan itu SAH: tidak ada yang
        // ditimbang, sehingga memaksa angka berarti mengarang hasil.
        expect((float) $p['produktivitas'])->toBe(0.0)
            ->and((float) $p['produksi'])->toBe(0.0);
    }
});

it('memberi warna badge yang berbeda untuk tiap status panen', function () {
    $warna = collect(StatusPanen::cases())->map(fn ($s) => $s->warna());

    expect($warna->unique())->toHaveCount(2)
        ->and(StatusPanen::BelumDipanen->warna())->toBe('gray')
        ->and(StatusPanen::SelesaiDipanen->warna())->toBe('success');
});

it('meniadakan kualitas panen dan memakai istilah produksi', function () {
    // Kualitas dicabut 2026-08-22 atas keputusan pemilik proyek, dan
    // `volume` berganti nama menjadi `produksi` agar tidak tertukar dengan
    // `volume_benih` pada penanaman.
    foreach (DummyData::hasilPanen() as $p) {
        expect($p)->toHaveKey('produksi')
            ->and($p)->toHaveKey('realisasi_panen')
            ->and($p)->toHaveKey('produktivitas')
            ->and($p)->toHaveKey('periode_panen')
            ->and($p)->not->toHaveKey('kualitas')
            ->and($p)->not->toHaveKey('volume')
            ->and($p)->not->toHaveKey('petani')
            ->and($p)->not->toHaveKey('tanggal_panen');

        // Periode berbentuk YYYY-MM, bukan tanggal penuh.
        expect($p['periode_panen'])->toMatch('/^\d{4}-\d{2}'.'/');
    }

    // Enumnya ikut dihapus, bukan sekadar tidak dipakai.
    expect(enum_exists('App\Enums\KualitasPanen'))->toBeFalse();
});

it('melepaskan lahan seketika begitu panennya tercatat', function () {
    // Sejak panen bertahap dicabut 2026-08-24, sebuah penanaman menahan lahan
    // SELURUHNYA atau tidak sama sekali - tidak ada keadaan setengah.
    //
    // Penghalusan 2026-08-22 yang melepaskan lahan sedikit demi sedikit
    // dibuat untuk menangani panen bertahap, dan kini gugur bersamanya.
    foreach (DummyData::poktan() as $poktan) {
        $id = $poktan['id_poktan'];
        $luas = DummyData::rekapLahanPoktan($id)['luas_total'];

        $tertahan = 0.0;

        foreach (DummyData::penanaman() as $tanam) {
            if ($tanam['poktan_id'] !== $id) {
                continue;
            }

            // Yang menahan hanyalah penanaman tanpa catatan panen.
            if (DummyData::statusPanen($tanam['id_penanaman']) === StatusPanen::BelumDipanen) {
                $tertahan += (float) $tanam['realisasi_tanam'];
            }
        }

        expect(DummyData::lahanTersedia($id))->toBe(max(0.0, round($luas - $tertahan, 2)));
    }

    // POKTAN MEKAR JAYA seluruh panennya sudah tuntas, sehingga lahannya
    // kembali utuh. Angka nyata dipakai agar uji tidak sekadar mengulang
    // rumus yang sama dengan kodenya.
    expect(DummyData::lahanTersedia(1))->toBe(DummyData::rekapLahanPoktan(1)['luas_total']);

    // POKTAN SUBUR MAKMUR menahan 1 ha yang belum dipanen dari 2 ha lahannya.
    expect(DummyData::lahanTersedia(2))->toBe(1.0);
});

it('mencatat penanaman dan panen memakai periode bulan, bukan tanggal', function () {
    // Penanaman maupun panen satu hamparan berlangsung berhari-hari,
    // sehingga menuntut satu tanggal pasti membuat petugas menebak - dan
    // tebakan itu lalu dipakai sebagai dasar rekap seolah-olah data
    // terukur. Bulan sudah cukup halus untuk seluruh rekap yang ada.
    foreach (DummyData::penanaman() as $t) {
        expect($t)->toHaveKey('periode_tanam')
            ->and($t)->not->toHaveKey('tanggal_tanam');

        expect($t['periode_tanam'])->toMatch('/^\d{4}-\d{2}'.'/');
    }

    foreach (DummyData::hasilPanen() as $p) {
        expect($p['periode_panen'])->toMatch('/^\d{4}-\d{2}'.'/');
    }
});

it('tidak memanen sebelum bulan tanamnya', function () {
    // Aturan integritas nomor 41. Panen yang mendahului tanam adalah
    // kekeliruan pencatatan yang tidak akan pernah terlihat pada tampilan
    // mana pun, sebab keduanya dirender pada halaman berbeda.
    foreach (DummyData::hasilPanen() as $p) {
        $tanam = collect(DummyData::penanaman())
            ->firstWhere('id_penanaman', $p['penanaman_id']);

        expect($p['periode_panen'])->toBeGreaterThanOrEqual(
            $tanam['periode_tanam'],
            "panen {$p['id_hasil_panen']} mendahului bulan tanamnya"
        );
    }
});
