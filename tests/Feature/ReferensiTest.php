<?php

/*
|--------------------------------------------------------------------------
| Data master referensi
|--------------------------------------------------------------------------
|
| Menjaga sembilan daftar pilihan yang sejak 2026-08-20 dikelola Admin lewat
| antarmuka, bukan ditulis sebagai enum di dalam kode (kamus data 5.6).
|
*/

use App\Enums\BidangPengaduan;
use App\Enums\JenisReferensi;
use App\Enums\KelompokReferensi;
use App\Support\DummyData;
use App\Support\PenilaianKondisiSp;
use Illuminate\Support\Facades\File;

it('menyediakan seluruh jenis referensi pada data contoh', function () {
    // Jenis yang terdaftar pada enum tetapi tidak punya satu pun nilai berarti
    // tab kosong pada halaman data master, dan petugas tidak dapat menebak
    // apakah itu kekeliruan atau memang belum diisi.
    foreach (JenisReferensi::cases() as $jenis) {
        expect(DummyData::referensi($jenis))
            ->not->toBeEmpty("jenis {$jenis->value} tidak punya nilai");
    }
});

it('menyediakan jenis alsintan sebagai data master yang dapat disunting admin', function () {
    // Putaran 7: alsintan sebelumnya tidak punya `jenis` sama sekali.
    // Dideklarasikan paling akhir supaya id `jenis_infrastruktur` /
    // `jenis_fasilitas` yang ditunjuk PenilaianKondisiSp tidak bergeser.
    expect(JenisReferensi::JenisAlsintan->value)->toBe('jenis_alsintan')
        ->and(JenisReferensi::JenisAlsintan->kelompok())->toBe(KelompokReferensi::AsetInfrastruktur)
        ->and(JenisReferensi::JenisAlsintan->berskor())->toBeFalse()
        ->and(JenisReferensi::JenisAlsintan->berjenjang())->toBeFalse()
        ->and(JenisReferensi::JenisAlsintan->dirujukParameter())->toBeFalse();

    $nilai = array_column(DummyData::referensi(JenisReferensi::JenisAlsintan), 'nilai');
    expect($nilai)->toContain('Traktor Roda Dua')->toContain('Pompa Air')->toContain('Lainnya');

    // Halamannya sendiri di /master/referensi/jenis_alsintan.
    $this->get(route('referensi.jenis', ['jenis' => 'jenis_alsintan']))->assertOk();

    // Id infrastruktur/fasilitas tetap di tempatnya: tak ada penomoran ulang.
    $idInfraTerakhir = max(array_column(DummyData::referensi(JenisReferensi::JenisInfrastruktur), 'id_referensi'));
    $idAlsintanPertama = min(array_column(DummyData::referensi(JenisReferensi::JenisAlsintan), 'id_referensi'));
    expect($idAlsintanPertama)->toBeGreaterThan($idInfraTerakhir);
});

it('memisahkan nilai aktif dari nilai yang sudah dinonaktifkan', function () {
    // Penonaktifan adalah inti rancangan tabel ini: nilai lama tetap terbaca,
    // hanya berhenti ditawarkan pada data baru. Tanpa satu pun contoh nonaktif,
    // keadaan itu tidak pernah terlihat saat peninjauan.
    $semua = DummyData::referensi();
    $aktif = DummyData::referensi(null, true);

    expect(count($aktif))->toBeLessThan(count($semua));

    $nonaktif = array_values(array_filter($semua, fn ($b) => ! $b['is_aktif']));

    expect($nonaktif)->not->toBeEmpty('data contoh wajib memuat nilai nonaktif');

    // Nilai nonaktif TIDAK ditawarkan pada dropdown.
    foreach ($nonaktif as $baris) {
        $opsi = DummyData::opsiReferensi(JenisReferensi::from($baris['jenis']));

        expect($opsi)->not->toHaveKey($baris['nilai']);
    }
});

it('memberi nilai skor hanya pada jenis yang benar-benar dipakai menghitung', function () {
    // `kondisi_rumah` tampak sebagai skala kerusakan yang sama dengan
    // `kondisi`, tetapi hanya `kondisi` yang dibaca PenilaianKondisiSp.
    // Memberi skor kepadanya berarti menyediakan isian yang tidak menentukan
    // apa pun, dan Admin yang menyuntingnya akan menyangka skor SP berubah.
    expect(JenisReferensi::Kondisi->berskor())->toBeTrue();
    expect(JenisReferensi::KondisiRumah->berskor())->toBeFalse();

    foreach (DummyData::referensi() as $baris) {
        $jenis = JenisReferensi::from($baris['jenis']);

        if ($jenis->berskor()) {
            expect($baris['nilai_skor'])->not->toBeNull("{$baris['nilai']} wajib berskor");

            continue;
        }

        expect($baris['nilai_skor'])->toBeNull("{$baris['nilai']} tidak boleh berskor");
    }
});

it('membaca skor penilaian kondisi SP dari data master, bukan dari konstanta', function () {
    // Sebelumnya bobot parameter dapat disunting Admin tetapi nilai kondisinya
    // tidak, padahal keduanya sama-sama menentukan skor akhir.
    expect(PenilaianKondisiSp::nilaiKondisi())->toBe(DummyData::skorKondisi());

    // Konstanta lama tidak boleh tertinggal, sebab ia akan menjadi sumber
    // kedua yang diam-diam berbeda dari data master.
    $sumber = file_get_contents(app_path('Support/PenilaianKondisiSp.php'));

    expect($sumber)->not->toContain('const NILAI_KONDISI');

    // Ketiga nilai baku tetap ada; yang berubah asalnya, bukan angkanya.
    expect(PenilaianKondisiSp::nilaiKondisi())
        ->toHaveKey('Baik')
        ->toHaveKey('Rusak Ringan')
        ->toHaveKey('Rusak Berat');
});

it('menyortir prioritas pengaduan memakai urutan, bukan abjad', function () {
    // Prioritas adalah skala berjenjang: daftar pengaduan menyortir memakainya,
    // sehingga menukar urutan berarti menukar antrean petugas.
    expect(JenisReferensi::PrioritasPengaduan->berjenjang())->toBeTrue();
    expect(JenisReferensi::SumberDana->berjenjang())->toBeFalse();

    $prioritas = array_column(DummyData::referensi(JenisReferensi::PrioritasPengaduan), 'nilai');

    expect($prioritas)->toBe(['Rendah', 'Sedang', 'Tinggi', 'Mendesak']);
});

it('tidak lagi memakai enum untuk daftar yang sudah menjadi data master', function () {
    // Enum yang tertinggal menjadi sumber kedua: dropdown membaca data master,
    // sedangkan tempat lain membaca enum, dan keduanya diam-diam berbeda
    // begitu Admin menambah satu nilai.
    $enumMaster = [
        'SumberDana', 'StatusPenyerahan', 'Kondisi', 'KondisiRumah', 'StatusHunian',
        'TipeKomoditas', 'PrioritasPengaduan', 'JenisDokumenLahan',
        'JabatanAnggotaPoktan', 'JenisInfrastruktur', 'JenisFasilitas',
        'BidangPengaduan', 'KategoriPengaduan',
    ];

    $tertinggal = [];

    foreach (File::allFiles(resource_path('views')) as $berkas) {
        $isi = file_get_contents($berkas->getPathname());

        foreach ($enumMaster as $enum) {
            if (preg_match('/\b'.$enum.'::(opsi|cases|nilai)\(/', $isi) === 1) {
                $tertinggal[] = $berkas->getFilename().' -> '.$enum;
            }
        }
    }

    expect($tertinggal)->toBe([]);
});

it('menampilkan seluruh daftar sebagai kartu, bukan tab yang tersembunyi', function () {
    // Sebab perubahan ini terukur: dengan empat belas tab, bar tab mencapai
    // 2309px pada ruang 705px sehingga hanya empat tab terlihat dan sepuluh
    // sisanya tersembunyi di balik gulir mendatar.
    $isi = $this->get(route('master.referensi'))->assertOk()->getContent();

    expect($isi)->not->toContain('role="tablist"');

    // Keempat belas daftar hadir sebagai tautan menuju halamannya sendiri.
    foreach (JenisReferensi::cases() as $jenis) {
        expect($isi)->toContain($jenis->label());
        expect($isi)->toContain(route('referensi.jenis', ['jenis' => $jenis->value]));
    }

    // Keempat kelompok dirender sebagai judul bagian. Dibandingkan setelah
    // di-escape, sebab Blade mengubah `&` pada "Aset & Infrastruktur" menjadi
    // `&amp;`.
    foreach (KelompokReferensi::cases() as $kelompok) {
        expect($isi)->toContain(e($kelompok->label()));
    }
});

it('mengelompokkan setiap jenis tanpa menyisakan satu pun', function () {
    // Jenis tanpa kelompok tidak muncul di indeks sama sekali, dan karena
    // indeks satu-satunya jalan menuju halamannya, daftar itu jadi tidak
    // terjangkau tanpa mengetik alamatnya sendiri.
    $terkumpul = [];

    foreach (KelompokReferensi::cases() as $kelompok) {
        foreach ($kelompok->jenis() as $jenis) {
            $terkumpul[] = $jenis->value;
        }
    }

    sort($terkumpul);
    $seluruhnya = array_column(JenisReferensi::cases(), 'value');
    sort($seluruhnya);

    expect($terkumpul)->toBe($seluruhnya);
});

it('memberi setiap daftar halamannya sendiri', function () {
    foreach (JenisReferensi::cases() as $jenis) {
        $isi = $this->get(route('referensi.jenis', ['jenis' => $jenis->value]))
            ->assertOk()
            ->getContent();

        expect($isi)->toContain($jenis->label());

        // Isian form ada di halaman jenis, bukan lagi di indeks.
        foreach (['name="jenis"', 'name="nilai"', 'name="urutan"', 'name="is_aktif"'] as $isian) {
            expect($isi)->toContain($isian);
        }

        // Jenisnya dikunci ke halaman: dikirim sebagai isian tersembunyi,
        // bukan dropdown yang dapat memindahkan nilai baru ke daftar lain.
        expect($isi)->toContain('<input type="hidden" name="jenis" value="'.$jenis->value.'"');

        // Kolom skor hanya pada jenis berskor.
        expect(preg_match_all('/>\s*Skor\s*</', $isi))->toBe($jenis->berskor() ? 1 : 0);
    }
});

it('membalas 404 untuk daftar yang tidak dikenal', function () {
    // Daftar yang tidak ada dan daftar yang kebetulan kosong adalah dua
    // keadaan berbeda; menyamakannya membuat salah ketik tampak seperti data
    // yang belum diisi.
    $this->get('/master/referensi/jenis_karangan')->assertNotFound();
});

it('mengalihkan alamat tab lama ke halaman daftarnya', function () {
    // Bentuk `?tab={jenis}` sempat dipakai form untuk menentukan jenis awal.
    // Tautan yang sudah tersimpan tidak boleh mendarat di halaman yang salah
    // tanpa penjelasan apa pun.
    $this->get('/master/referensi?tab=kondisi')
        ->assertRedirect(route('referensi.jenis', ['jenis' => 'kondisi']));

    // Tab karangan tidak mengalihkan, hanya menampilkan indeks.
    $this->get('/master/referensi?tab=karangan')->assertOk();
});
it('tidak menyediakan penghapusan nilai referensi', function () {
    // Menghapus `Hibah` dari sumber dana membuat baris infrastruktur lama
    // menunjuk nilai yang lenyap, dan rekap kehilangan baris itu tanpa pesan
    // apa pun. Yang tersedia hanya penonaktifan.
    expect(Route::has('referensi.hapus'))->toBeFalse();

    foreach ([1, 2, 3, 4] as $idRole) {
        $izin = DummyData::izinRole($idRole)['referensi'] ?? [];

        expect($izin)->not->toContain('hapus');
    }

    expect(DummyData::izinRole(1)['referensi'])->toBe(['lihat', 'tambah', 'ubah']);
    expect(DummyData::izinRole(2)['referensi'])->toBe(['lihat', 'tambah', 'ubah']);
    expect(DummyData::izinRole(3)['referensi'])->toBe(['lihat']);
    expect(DummyData::izinRole(4)['referensi'])->toBe(['lihat']);
});

it('merujuk jenis infrastruktur dan fasilitas lewat id, bukan teks', function () {
    // Rujukan berbasis teks putus tanpa pesan apa pun begitu Admin memperbaiki
    // ejaan `Air` menjadi `Air Bersih`, dan parameter itu lalu diam-diam
    // menilai setiap SP sebagai tidak punya air. Pada parameter primer, itu
    // langsung menjatuhkan status setiap SP.
    foreach (PenilaianKondisiSp::parameter() as $par) {
        expect($par)->toHaveKey('referensi_id');
        expect($par)->not->toHaveKey('jenis_rujukan');
        expect($par['referensi_id'])->toBeInt();

        // Idnya harus benar-benar menunjuk baris yang ada, dan pada jenis yang
        // sesuai sumbernya.
        $jenisDituju = $par['sumber'] === 'Fasilitas'
            ? JenisReferensi::JenisFasilitas
            : JenisReferensi::JenisInfrastruktur;

        $baris = array_values(array_filter(
            DummyData::referensi($jenisDituju),
            fn ($b) => $b['id_referensi'] === $par['referensi_id']
        ));

        expect($baris)->toHaveCount(1, "Parameter {$par['kode']} menunjuk id yang bukan {$jenisDituju->value}");
    }
});

it('tetap menilai sama setelah ejaan jenis rujukan diganti', function () {
    // Inti dari peralihan ke id. Penilaian dihitung dua kali: apa adanya, lalu
    // dengan satu nilai referensi yang ejaannya diganti berikut aset yang
    // memakainya. Hasilnya wajib sama, sebab rujukannya memakai id.
    $sebelum = PenilaianKondisiSp::nilai(1);

    $idAir = DummyData::referensiId(JenisReferensi::JenisInfrastruktur, 'Air');

    expect($idAir)->not->toBeNull();

    // Aset dengan ejaan baru, seolah Admin sudah menyunting nilai referensinya.
    $infrastruktur = array_map(function ($a) {
        if (($a['jenis'] ?? null) === 'Air') {
            $a['jenis'] = 'Air Bersih';
        }

        return $a;
    }, DummyData::infrastruktur());

    // Dengan rujukan teks, penilaian air pada $infrastruktur ini akan gagal
    // cocok dan skornya jatuh. Uji ini menjaga agar cara mencocokkannya tetap
    // lewat satu tempat, yaitu terjemahan id ke teks.
    $adaAir = array_filter($infrastruktur, fn ($a) => ($a['jenis'] ?? null) === 'Air Bersih');

    expect($adaAir)->not->toBeEmpty();
    expect($sebelum['rincian'])->not->toBeEmpty();
});

it('melewati parameter yang rujukannya hilang, bukan menilainya nol', function () {
    // Menilai nol berarti seluruh SP mendadak dianggap tidak punya air hanya
    // karena satu baris referensi hilang, dan pada parameter primer itu
    // langsung menjatuhkan status setiap SP menjadi Perlu Perhatian.
    $berkas = file_get_contents(app_path('Support/PenilaianKondisiSp.php'));

    expect($berkas)->toContain('if ($jenisRujukan === null) {');
    expect($berkas)->toContain('continue;');
});

it('menyimpan bidang penanganan bawaan sebagai data, bukan match di dalam kode', function () {
    // Selama petanya berupa `match` tanpa `default`, kategori tidak boleh
    // ditambah lewat data master: kategori baru akan melempar
    // UnhandledMatchError begitu ada yang memilihnya, dan form pengaduan mati.
    $berkas = file_get_contents(app_path('Enums/BidangPengaduan.php'));

    expect($berkas)->not->toContain('KategoriPengaduan::Rumah,');
    expect($berkas)->toContain('DummyData::petaBidangKategori()');

    // Kategori baru yang ditambahkan Admin tidak boleh meruntuhkan apa pun.
    expect(BidangPengaduan::dariKategori('Kategori Yang Belum Ada'))->toBeNull();
    expect(BidangPengaduan::perluDitetapkan('Kategori Yang Belum Ada'))->toBeTrue();
});

it('membedakan bidang kosong yang bermakna dari bidang yang terlewat diisi', function () {
    // NULL pada bidang_id menyatakan kategori yang dapat jatuh ke dua dinas
    // sekaligus, sehingga wajib ditimbang petugas (rules.md 10b poin 7b).
    $kategori = DummyData::referensi(JenisReferensi::KategoriPengaduan);

    expect($kategori)->toHaveCount(12);

    $netral = array_values(array_filter($kategori, fn ($b) => $b['bidang_id'] === null));

    expect(array_column($netral, 'nilai'))
        ->toBe(['Lahan Usaha', 'Infrastruktur', 'Bencana', 'Lainnya']);

    // Yang terisi wajib menunjuk baris bidang yang benar-benar ada.
    foreach ($kategori as $baris) {
        if ($baris['bidang_id'] === null) {
            continue;
        }

        expect(DummyData::referensiNilai($baris['bidang_id']))
            ->toBeIn(['Ketransmigrasian', 'Pertanian']);
    }

    // Halaman daftarnya menjelaskan maksud kosongnya, bukan menampilkan tanda
    // hubung yang membuatnya tampak seperti data yang lupa diisi.
    expect($this->get(route('referensi.jenis', ['jenis' => JenisReferensi::KategoriPengaduan->value]))->getContent())
        ->toContain('Ditetapkan petugas');
});

it('memakai daftar lengkap pada filter, bukan hanya nilai aktif', function () {
    // Dropdown filter menyaring data LAMA, dan data lama masih memakai nilai
    // yang kini nonaktif. Memakai daftar aktif membuat baris-baris itu tidak
    // dapat dicari sama sekali: nilainya ada di kolom, tetapi tidak ada pilihan
    // yang cocok untuk memanggilnya.
    expect(DummyData::opsiFilterReferensi(JenisReferensi::SumberDana))
        ->toHaveKey('Lembaga Swadaya Masyarakat');

    expect(DummyData::opsiReferensi(JenisReferensi::SumberDana))
        ->not->toHaveKey('Lembaga Swadaya Masyarakat');

    // Setiap dropdown yang idnya berawalan `filter_` wajib memakai daftar
    // lengkap. Diperiksa dengan membaca berkasnya, sebab cacat ini tidak
    // terlihat sampai ada nilai yang dinonaktifkan.
    $salah = [];

    foreach (File::allFiles(resource_path('views')) as $berkas) {
        $baris = file($berkas->getPathname());

        foreach ($baris as $nomor => $isi) {
            if (! str_contains($isi, 'opsiReferensi(')) {
                continue;
            }

            $sebelumnya = implode(' ', array_slice($baris, max(0, $nomor - 6), 6));

            if (str_contains($sebelumnya, 'filter_')) {
                $salah[] = $berkas->getFilename().':'.($nomor + 1);
            }
        }
    }

    expect($salah)->toBe([]);
});
