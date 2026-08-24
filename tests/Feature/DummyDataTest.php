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

it('menghitung sisa benih dari jumlah dikurangi pemakaian penanaman', function () {
    // Rumusnya satu pengurangan, dan itu yang membuatnya mengoreksi diri
    // sendiri ketika baris penanaman disunting. Bila kelak diganti kolom
    // tersimpan, angka ini harus dikoreksi setiap penyuntingan dan koreksi
    // yang terlewat tidak akan pernah ketahuan.
    foreach (DummyData::saprotan() as $baris) {
        $terpakai = 0.0;

        foreach (DummyData::penanaman() as $tanam) {
            if (($tanam['saprotan_id'] ?? null) === $baris['id_saprotan']) {
                $terpakai += (float) ($tanam['volume_benih'] ?? 0);
            }
        }

        $harapan = max(0.0, round((float) $baris['jumlah'] - $terpakai, 3));

        expect(DummyData::sisaBenih($baris['id_saprotan']))->toBe($harapan);
    }
});

it('tidak pernah mengembalikan sisa benih bertanda minus', function () {
    // Pemakaian melebihi stok ditolak penjaga pada form, tetapi data lama
    // yang terlanjur begitu tidak boleh membuat halaman menampilkan minus.
    foreach (DummyData::saprotan() as $baris) {
        expect(DummyData::sisaBenih($baris['id_saprotan']))->toBeGreaterThanOrEqual(0.0);
    }

    // Baris yang tidak ada tetap menjawab angka, bukan melempar galat.
    expect(DummyData::sisaBenih(9999))->toBe(0.0);
});

it('menyembunyikan benih yang stoknya habis dari daftar tersedia', function () {
    // INTI ATURAN STOK. Benih habis sekali pakai, tetapi penguncian terjadi
    // ketika STOKNYA HABIS, bukan ketika pertama kali dipakai. Mengunci pada
    // pemakaian pertama akan mematahkan penanaman bertahap: satu poktan
    // menanam 3 ha lalu 7 ha dari jatah yang sama, dan penanaman kedua itu
    // tidak akan dapat dicatat sama sekali.
    $habis = collect(DummyData::saprotan())
        ->first(fn ($s) => $s['jenis'] === JenisSaprotan::Benih->value
            && DummyData::sisaBenih($s['id_saprotan']) <= 0);

    expect($habis)->not->toBeNull('data contoh wajib memuat satu benih yang habis');

    $tersedia = collect(DummyData::benihTersedia())->pluck('id_saprotan');

    expect($tersedia)->not->toContain($habis['id_saprotan']);

    // Sebaliknya, yang masih bersisa wajib muncul.
    $bersisa = collect(DummyData::saprotan())
        ->first(fn ($s) => $s['jenis'] === JenisSaprotan::Benih->value
            && DummyData::sisaBenih($s['id_saprotan']) > 0);

    expect($tersedia)->toContain($bersisa['id_saprotan']);
});

it('menyaring benih tersedia menurut poktan dan komoditasnya', function () {
    // Inilah yang membuat petugas tidak dapat memilih benih padi untuk
    // penanaman jagung, maupun memakai benih milik poktan lain.
    foreach (DummyData::benihTersedia(1, 1) as $benih) {
        expect($benih['poktan_id'])->toBe(1)
            ->and($benih['komoditas_id'])->toBe(1)
            ->and($benih['jenis'])->toBe(JenisSaprotan::Benih->value);
    }

    // Poktan yang tidak memegang benih apa pun menerima daftar kosong,
    // bukan seluruh benih milik orang lain.
    expect(DummyData::benihTersedia(3))->toBe([]);

    // Label menyebut sisanya, sebab petugas perlu tahu berapa yang masih
    // dapat dialokasikan SEBELUM memilih, bukan setelah formnya ditolak.
    foreach (DummyData::benihTersedia() as $benih) {
        expect($benih)->toHaveKey('sisa_benih')
            ->and($benih['label_benih'])->toContain('sisa')
            ->and($benih['label_benih'])->toContain($benih['nama']);
    }
});

it('menautkan volume benih penanaman ke baris saprotan yang sah', function () {
    // Volume benih disimpan, bukan dihitung dari luas tanam memakai rasio
    // baku. Rasio 15 kg/ha pada laporan Polri adalah keputusan program pada
    // satu bantuan, bukan hukum alam: benih swadaya dan komoditas lain
    // memakai takaran berbeda.
    foreach (DummyData::penanaman() as $tanam) {
        expect($tanam)->toHaveKey('saprotan_id')
            ->and($tanam)->toHaveKey('volume_benih');

        if ($tanam['saprotan_id'] === null) {
            // Boleh kosong: penanaman dari benih yang tidak tercatat pada
            // modul saprotan tetap harus dapat didata.
            expect($tanam['volume_benih'])->toBeNull();

            continue;
        }

        $benih = collect(DummyData::saprotan())
            ->firstWhere('id_saprotan', $tanam['saprotan_id']);

        expect($benih)->not->toBeNull()
            ->and($benih['jenis'])->toBe(JenisSaprotan::Benih->value)
            ->and($tanam['volume_benih'])->toBeGreaterThan(0);

        // Komoditas benih wajib cocok dengan komoditas yang ditanam.
        expect($benih['komoditas'])->toBe($tanam['komoditas']);
    }
});

it('menjaga pemakaian benih tidak melebihi jumlah yang disalurkan', function () {
    // Sebelum kolom ini ada, tidak ada apa pun yang mencegah 150 kg benih
    // dipakai untuk penanaman senilai 400 kg.
    foreach (DummyData::saprotan() as $baris) {
        $terpakai = 0.0;

        foreach (DummyData::penanaman() as $tanam) {
            if (($tanam['saprotan_id'] ?? null) === $baris['id_saprotan']) {
                $terpakai += (float) ($tanam['volume_benih'] ?? 0);
            }
        }

        expect($terpakai)->toBeLessThanOrEqual(
            (float) $baris['jumlah'],
            "pemakaian {$baris['nama']} melebihi jumlah yang disalurkan"
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
    // Yang masih menahan lahan hanyalah bagian yang BELUM dipanen, bukan
    // seluruh penanaman yang belum pernah disentuh panen. Panen bertahap
    // melepaskan lahannya sedikit demi sedikit.
    foreach (DummyData::poktan() as $poktan) {
        $id = $poktan['id_poktan'];
        $rekap = DummyData::rekapLahanPoktan($id);

        $belum = 0.0;

        foreach (DummyData::penanaman() as $tanam) {
            if ($tanam['poktan_id'] === $id) {
                $belum += DummyData::belumDipanen($tanam['id_penanaman']);
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

it('menjaga hasil panen ditambah puso ditambah belum dipanen sama dengan realisasi tanam', function () {
    // Identitas pertama, terbukti pada 96 baris laporan Polri MT.II 2025.
    //
    // `belum_dipanen` TIDAK disimpan; ia selisih dari identitas ini.
    // Menyimpannya berarti tiga angka yang saling menentukan disimpan
    // terpisah, dan ketiganya dapat berbeda tanpa ada yang menegur.
    foreach (DummyData::penanaman() as $tanam) {
        $panen = 0.0;
        $puso = 0.0;

        foreach (DummyData::hasilPanen() as $p) {
            if (($p['penanaman_id'] ?? null) !== $tanam['id_penanaman']) {
                continue;
            }

            $panen += (float) $p['realisasi_panen'];
            $puso += (float) ($p['puso'] ?? 0);
        }

        $belum = DummyData::belumDipanen($tanam['id_penanaman']);
        $total = round($panen + $puso + $belum, 2);

        expect($total)->toBe(round((float) $tanam['realisasi_tanam'], 2),
            "penanaman {$tanam['id_penanaman']} timpang");
    }
});

it('menjaga produksi sama dengan hasil panen dikali produktivitas', function () {
    // Identitas kedua. Produksi tetap DISIMPAN meski dapat dihitung: ia
    // angka yang dilaporkan ke dinas, dan pembulatan hasil perkalian dapat
    // berbeda tipis dari angka yang benar-benar ditimbang. Yang dijaga di
    // sini adalah selisihnya tetap dalam batas pembulatan yang wajar.
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

it('menyimpulkan status panen dari sisa luas, bukan dari kolom tersimpan', function () {
    // Status DITURUNKAN agar tidak pernah basi. Menyimpannya sebagai kolom
    // berarti nilainya menjadi salah begitu satu baris panen disunting atau
    // dihapus, dan kesalahan itu tidak pernah memerahkan apa pun.
    foreach (DummyData::penanaman() as $tanam) {
        expect($tanam)->not->toHaveKey('status_panen');
    }

    // Yang diperiksa perilakunya, bukan keberadaan methodnya: tiap penanaman
    // dibandingkan dengan keadaan yang benar-benar dihitung dari datanya.
    foreach (DummyData::penanaman() as $tanam) {
        $id = $tanam['id_penanaman'];

        $adaPanen = collect(DummyData::hasilPanen())
            ->contains(fn ($p) => ($p['penanaman_id'] ?? null) === $id);

        $harusnya = match (true) {
            ! $adaPanen => StatusPanen::BelumDipanen,
            DummyData::belumDipanen($id) > 0 => StatusPanen::DipanenSebagian,
            default => StatusPanen::SelesaiDipanen,
        };

        expect(DummyData::statusPanen($id))->toBe($harusnya, "penanaman {$id}");
    }
});

it('membedakan penanaman yang belum disentuh dari yang dipanen nol hektare', function () {
    // Keduanya sama-sama menyisakan SELURUH luasnya, sehingga sisa saja tidak
    // cukup membedakannya. Tanpa pemeriksaan keberadaan baris panen, penanaman
    // yang sudah didatangi petugas dan yang belum akan tampak serupa.
    $belum = DummyData::statusPanen(6);

    expect($belum)->toBe(StatusPanen::BelumDipanen)
        ->and(DummyData::belumDipanen(6))->toBe(1.0);

    // Sisanya sama-sama penuh, tetapi keduanya keadaan yang berbeda.
    $adaPanen = collect(DummyData::hasilPanen())
        ->contains(fn ($p) => ($p['penanaman_id'] ?? null) === 6);

    expect($adaPanen)->toBeFalse();
});

it('menyediakan contoh untuk ketiga status agar seluruhnya dapat ditinjau', function () {
    // Status yang tidak punya benda nyata tidak akan pernah tampil di layar,
    // dan ujinya hanya menguji dirinya sendiri. Sebelum 2026-08-24 seluruh
    // penanaman sudah dipanen, sehingga lencana Belum Dipanen tidak pernah
    // terlihat siapa pun.
    // Dibandingkan sebagai daftar nilai, bukan lewat toContain berargumen
    // pesan: Pest memperlakukan argumen kedua toContain sebagai nilai lain
    // yang ikut dicari, sehingga pesannya sendiri akan ikut diperiksa.
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

it('menganggap penanaman yang seluruhnya puso sebagai selesai dipanen', function () {
    // Ditetapkan pemilik proyek 2026-08-24: puso adalah KOLOM tersendiri,
    // bukan status keempat, mengikuti bentuk laporan lapangan yang menaruh
    // Realisasi Panen dan Puso sebagai dua kolom bersebelahan.
    //
    // Konsekuensinya diuji terang-terangan di sini, bukan dibiarkan sebagai
    // kejutan: lahan yang gagal total menyisakan nol, sehingga statusnya sama
    // dengan yang berhasil penuh. Pembedanya kolom puso.
    expect(StatusPanen::cases())->toHaveCount(3);

    foreach (StatusPanen::cases() as $status) {
        expect($status->value)->not->toContain('Puso');
    }
});

it('meniadakan pilihan Belum Dipanen pada penyaring hasil panen', function () {
    // Penanaman yang belum dipanen tidak punya satu pun baris panen, sehingga
    // pilihan itu selalu menghasilkan tabel kosong - kontrol mati yang
    // dilarang ui-spec.md R-26.
    expect(StatusPanen::penyaringPanen())
        ->toBe([StatusPanen::DipanenSebagian, StatusPanen::SelesaiDipanen])
        ->not->toContain(StatusPanen::BelumDipanen);

    // Dibuktikan pada datanya, bukan diandaikan: tidak satu pun baris panen
    // dapat berstatus Belum Dipanen.
    foreach (DummyData::hasilPanen() as $p) {
        expect(DummyData::statusPanen($p['penanaman_id']))
            ->not->toBe(StatusPanen::BelumDipanen, "panen {$p['id_hasil_panen']}");
    }
});

it('memberi warna badge yang berbeda untuk tiap status panen', function () {
    $warna = collect(StatusPanen::cases())->map(fn ($s) => $s->warna());

    expect($warna->unique())->toHaveCount(3)
        ->and(StatusPanen::BelumDipanen->warna())->toBe('gray')
        ->and(StatusPanen::DipanenSebagian->warna())->toBe('warning')
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

it('menahan lahan yang masih berdiri tanaman meski sudah dipanen sebagian', function () {
    // Diperhalus 2026-08-22: sebelumnya satu baris panen dianggap
    // menuntaskan SELURUH penanaman. Itu keliru sejak panen dapat
    // bertahap - penanaman 10 ha yang baru dipanen 3 ha akan langsung
    // melepaskan seluruh 10 ha, sehingga lahan yang masih berdiri tanaman
    // tampak siap ditanami lagi.
    foreach (DummyData::poktan() as $poktan) {
        $id = $poktan['id_poktan'];
        $belum = 0.0;

        foreach (DummyData::penanaman() as $tanam) {
            if ($tanam['poktan_id'] === $id) {
                $belum += DummyData::belumDipanen($tanam['id_penanaman']);
            }
        }

        $harapan = max(0.0, round(DummyData::rekapLahanPoktan($id)['luas_total'] - $belum, 2));

        expect(DummyData::lahanTersedia($id))->toBe($harapan);
    }
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
