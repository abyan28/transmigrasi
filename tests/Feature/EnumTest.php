<?php

/**
 * Uji seluruh enum pilihan baku sistem.
 *
 * Enum dipakai di hampir setiap form, tabel, dan badge. Kesalahan pada nilainya
 * akan menyebar ke seluruh modul, sehingga nilai dan labelnya diperiksa
 * terhadap daftar baku pada agents/data-dictionary.md bagian 11.
 */

use App\Enums\AksiAuditLog;
use App\Enums\AksiPermission;
use App\Enums\AlasanPergantianKK;
use App\Enums\BidangPengaduan;
use App\Enums\CakupanData;
use App\Enums\JenisInfrastruktur;
use App\Enums\KategoriPengaduan;
use App\Enums\Kondisi;
use App\Enums\KondisiRumah;
use App\Enums\PrioritasPengaduan;
use App\Enums\StatusPengaduan;
use App\Enums\StatusTinggal;
use App\Enums\SumberDana;
use App\Support\DummyData;
use App\Support\PenilaianKondisiSp;

/*
|--------------------------------------------------------------------------
| Kelengkapan nilai
|--------------------------------------------------------------------------
*/

it('memuat seluruh nilai baku sesuai kamus data', function (string $enum, array $harapan) {
    expect($enum::nilai())->toBe($harapan);
})->with([
    'status pengaduan' => [StatusPengaduan::class, ['Menunggu Diterima', 'Diterima', 'Diproses', 'Selesai']],
    'prioritas pengaduan' => [PrioritasPengaduan::class, ['Rendah', 'Sedang', 'Tinggi', 'Mendesak']],
    'kondisi rumah' => [KondisiRumah::class, ['Tidak Rusak', 'Rusak Ringan', 'Rusak Berat']],
    'kondisi aset' => [Kondisi::class, ['Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang']],
    'bidang pengaduan' => [BidangPengaduan::class, ['Ketransmigrasian', 'Pertanian']],
    'cakupan data' => [CakupanData::class, ['Semua', 'Per SP', 'Per Bidang']],
    'aksi permission' => [AksiPermission::class, ['lihat', 'tambah', 'ubah', 'hapus']],
]);

it('membedakan kondisi rumah dari kondisi aset lain', function () {
    // rules.md 6a.3 menetapkan istilah "Tidak Rusak" khusus untuk rumah,
    // sedangkan aset lain memakai "Baik".
    expect(KondisiRumah::TidakRusak->value)->toBe('Tidak Rusak')
        ->and(Kondisi::Baik->value)->toBe('Baik');
});

it('menilai aset hilang nol, bukan sekadar di bawah rusak berat', function () {
    // "Hilang" bukan tingkat kerusakan melainkan ketiadaan. Nilainya WAJIB
    // sama dengan NILAI_TIDAK_ADA, sebab aturan primer nol pada
    // PenilaianKondisiSp membandingkan tepat terhadap konstanta itu. Bila
    // skornya sekadar kecil, misalnya 0.1, satu-satunya sumur bor yang
    // hilang tidak akan menjatuhkan status SP dan kehilangan itu lolos
    // sebagai "Berkembang".
    $skor = DummyData::skorKondisi();

    expect($skor)->toHaveKey('Hilang')
        ->and($skor['Hilang'])->toBe(PenilaianKondisiSp::NILAI_TIDAK_ADA)
        ->and($skor['Hilang'])->toBeLessThan($skor['Rusak Berat']);
});

it('meniadakan status tinggal Meninggal dan memakai Pindah Penduduk', function () {
    // Status tinggal melekat pada KELUARGA, bukan orang. Kematian kepala
    // keluarga direkam AlasanPergantianKK, dan keluarganya tetap Aktif sebab
    // kedudukan berpindah ke ahli waris. Menyediakan "Meninggal" di kedua
    // enum membuat petugas membubarkan keluarga yang penghuninya masih ada.
    expect(StatusTinggal::nilai())->toBe(['Aktif', 'Pindah Penduduk', 'Tidak Aktif'])
        ->and(StatusTinggal::tryFrom('Meninggal'))->toBeNull()
        ->and(StatusTinggal::tryFrom('Pindah'))->toBeNull()
        ->and(AlasanPergantianKK::Meninggal->value)->toBe('Meninggal');
});

it('memuat dua belas kategori pengaduan tanpa spasi berlebih', function () {
    // Tiga perubahan pada 2026-08-19: 'Peralatan dan Perlengkapan' dipecah
    // menjadi 'Inventaris SP' dan 'Fasilitas SP' sebab satu kategori menaungi
    // dua tabel berbeda; 'Saprotan' ditambahkan agar keluhan bibit dan pupuk
    // tidak menumpang pada 'Produksi Panen'; dan 'Kelompok Tani' ditambahkan
    // sebab poktan modul penuh tetapi keluhannya terpaksa masuk 'Lainnya'.
    expect(KategoriPengaduan::cases())->toHaveCount(12);

    foreach (KategoriPengaduan::cases() as $kategori) {
        expect($kategori->value)->toBe(trim($kategori->value));
    }

    expect(KategoriPengaduan::nilai())
        ->toContain('Inventaris SP')
        ->toContain('Fasilitas SP')
        ->toContain('Saprotan')
        ->toContain('Kelompok Tani')
        ->not->toContain('Peralatan dan Perlengkapan');
});

it('menyediakan kategori bagi tiap modul yang dapat diadukan warga', function () {
    // Penjaga terhadap kelalaian 2026-08-19: modul poktan sempat terlewat
    // sehingga keluhan atasnya terpaksa masuk kategori 'Lainnya' yang justru
    // berbidang kosong, dan itu menambah antrean penyaringan tanpa alasan.
    //
    // Modul internal sistem, data referensi, serta data pribadi transmigran
    // sengaja tidak berkategori (rules.md 10b poin 3a).
    $nilai = KategoriPengaduan::nilai();

    foreach ([
        'rumah' => 'Rumah',
        'lahan' => 'Lahan Usaha',
        'inventaris_sp' => 'Inventaris SP',
        'fasilitas_sp' => 'Fasilitas SP',
        'infrastruktur' => 'Infrastruktur',
        'poktan' => 'Kelompok Tani',
        'alsintan' => 'Alsintan',
        'saprotan' => 'Saprotan',
        'hasil_panen' => 'Produksi Panen',
    ] as $modul => $kategori) {
        // Pesan disusun sendiri sebab toContain() memakai argumen kedua
        // sebagai nilai tambahan yang dicari, bukan sebagai keterangan.
        expect(in_array($kategori, $nilai, true))
            ->toBeTrue("Modul {$modul} tanpa kategori pengaduan");
    }
});

it('memuat sepuluh aksi audit log termasuk tindakan terhadap akun', function () {
    $nilai = AksiAuditLog::nilai();

    expect($nilai)->toHaveCount(10)
        ->and($nilai)->toContain('Reset Kata Sandi')
        ->and($nilai)->toContain('Pulihkan')
        ->and($nilai)->toContain('Ubah Izin Role');
});

it('memuat delapan sumber dana termasuk swadaya', function () {
    expect(SumberDana::nilai())->toHaveCount(8)
        ->and(SumberDana::nilai())->toContain('Swadaya')
        ->and(SumberDana::Apbn->value)->toBe('APBN');
});

it('memuat sepuluh jenis infrastruktur sesuai aturan modul', function () {
    // Diperluas pada Task 2.25 dengan sanitasi, jalan penghubung, dan pasar
    // atau kios saprotan, karena ketiganya berpengaruh pada kelayakan huni
    // dan menjadi parameter penilaian kondisi SP (agents/rules.md bagian 10).
    expect(JenisInfrastruktur::nilai())->toBe([
        'Air', 'Sanitasi', 'Irigasi', 'Listrik', 'Jalan Penghubung',
        'Jalan Produksi', 'Telekomunikasi', 'Gudang',
        'Pasar atau Kios Saprotan', 'Lainnya',
    ]);
});

it('membedakan jalan penghubung dari jalan produksi', function () {
    // Jalan penghubung menentukan akses masuk ke kawasan termasuk bagi
    // kendaraan darurat; jalan produksi menentukan pengangkutan hasil dari
    // lahan usaha. Keduanya berbeda dampak dan berbeda bobot pada penilaian.
    expect(JenisInfrastruktur::JalanPenghubung->value)->toBe('Jalan Penghubung')
        ->and(JenisInfrastruktur::JalanProduksi->value)->toBe('Jalan Produksi')
        ->and(JenisInfrastruktur::JalanPenghubung)->not->toBe(JenisInfrastruktur::JalanProduksi);
});

/*
|--------------------------------------------------------------------------
| Perilaku bersama
|--------------------------------------------------------------------------
*/

it('menyusun daftar pilihan untuk dropdown', function () {
    $opsi = StatusPengaduan::opsi();

    expect($opsi)->toBeArray()
        ->and($opsi)->toHaveCount(4)
        ->and($opsi['Diproses'])->toBe('Diproses');
});

it('mengubah teks menjadi enum dan menolak nilai asing', function () {
    expect(StatusPengaduan::dari('Diproses'))->toBe(StatusPengaduan::Diproses)
        ->and(StatusPengaduan::dari('Entah Apa'))->toBeNull()
        ->and(StatusPengaduan::dari(null))->toBeNull();
});

it('memeriksa keanggotaan pada sekumpulan nilai', function () {
    expect(StatusPengaduan::Diterima->salahSatuDari(StatusPengaduan::Diterima, StatusPengaduan::Diproses))->toBeTrue()
        ->and(StatusPengaduan::Selesai->salahSatuDari(StatusPengaduan::Diterima))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Warna badge
|--------------------------------------------------------------------------
*/

it('memakai warna badge yang dikenali komponen', function () {
    $warnaSah = ['gray', 'teal', 'success', 'warning', 'error'];

    $berbadge = [
        StatusPengaduan::class,
        PrioritasPengaduan::class,
        KondisiRumah::class,
        Kondisi::class,
    ];

    foreach ($berbadge as $enum) {
        foreach ($enum::cases() as $kasus) {
            expect($warnaSah)->toContain($kasus->warna());
        }
    }
});

it('memakai warna hijau hanya untuk keadaan yang baik', function () {
    expect(StatusPengaduan::Selesai->warna())->toBe('success')
        ->and(PrioritasPengaduan::Mendesak->warna())->toBe('error')
        ->and(KondisiRumah::TidakRusak->warna())->toBe('success')
        ->and(KondisiRumah::RusakBerat->warna())->toBe('error');
});

it('menyusun daftar pilihan lengkap dengan warnanya', function () {
    $opsi = StatusPengaduan::opsiBerwarna();

    expect($opsi)->toHaveCount(4)
        ->and($opsi[0])->toHaveKeys(['nilai', 'label', 'warna'])
        ->and($opsi[0]['nilai'])->toBe('Menunggu Diterima')
        ->and($opsi[0]['warna'])->toBe('gray');
});

/*
|--------------------------------------------------------------------------
| Alur status pengaduan
|--------------------------------------------------------------------------
*/

it('mengurutkan alur penanganan pengaduan', function () {
    expect(StatusPengaduan::MenungguDiterima->berikutnya())->toBe(StatusPengaduan::Diterima)
        ->and(StatusPengaduan::Diterima->berikutnya())->toBe(StatusPengaduan::Diproses)
        ->and(StatusPengaduan::Diproses->berikutnya())->toBe(StatusPengaduan::Selesai)
        ->and(StatusPengaduan::Selesai->berikutnya())->toBeNull();
});

it('menolak perpindahan status yang melompat atau mundur', function () {
    // Maju satu langkah, diperbolehkan
    expect(StatusPengaduan::MenungguDiterima->bolehPindahKe(StatusPengaduan::Diterima))->toBeTrue();

    // Melompati tahap
    expect(StatusPengaduan::MenungguDiterima->bolehPindahKe(StatusPengaduan::Selesai))->toBeFalse();

    // Mundur ke tahap sebelumnya
    expect(StatusPengaduan::Diproses->bolehPindahKe(StatusPengaduan::Diterima))->toBeFalse();

    // Bertahan di status yang sama
    expect(StatusPengaduan::Diproses->bolehPindahKe(StatusPengaduan::Diproses))->toBeFalse();
});

it('menandai pengaduan yang masih berjalan', function () {
    expect(StatusPengaduan::Diproses->masihBerjalan())->toBeTrue()
        ->and(StatusPengaduan::Selesai->masihBerjalan())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Penerusan pengaduan ke dinas
|--------------------------------------------------------------------------
*/

it('menurunkan bidang pertanian dari kategori kelembagaan, sarana, dan hasil usaha', function (string $kategori) {
    expect(BidangPengaduan::dariKategori($kategori))->toBe(BidangPengaduan::Pertanian);
})->with([
    'kelompok tani' => 'Kelompok Tani',
    'alsintan' => 'Alsintan',
    'saprotan' => 'Saprotan',
    'produksi panen' => 'Produksi Panen',
]);

it('menurunkan bidang ketransmigrasian dari kategori permukiman dan aset SP', function (string $kategori) {
    expect(BidangPengaduan::dariKategori($kategori))->toBe(BidangPengaduan::Ketransmigrasian);
})->with([
    'rumah' => 'Rumah',
    'lahan pekarangan' => 'Lahan Pekarangan',
    'inventaris sp' => 'Inventaris SP',
    'fasilitas sp' => 'Fasilitas SP',
]);

it('membiarkan bidang kosong pada kategori yang dapat ditangani dua dinas', function (string $kategori) {
    // Menebak bidang untuk kategori semacam ini justru menyesatkan: laporan
    // akan masuk ke daftar dinas yang keliru lalu tertahan di sana.
    // rules.md 10b poin 7a mewajibkannya ditetapkan petugas.
    expect(BidangPengaduan::dariKategori($kategori))->toBeNull()
        ->and(BidangPengaduan::perluDitetapkan($kategori))->toBeTrue();
})->with([
    'lahan usaha' => 'Lahan Usaha',
    'infrastruktur' => 'Infrastruktur',
    'bencana' => 'Bencana',
    'lainnya' => 'Lainnya',
]);

it('memetakan seluruh kategori tanpa terkecuali', function () {
    // Penjaga terhadap kategori baru yang lupa dipetakan. match() tanpa arm
    // penampung akan melempar UnhandledMatchError, dan uji ini memastikan
    // seluruh nilai enum benar-benar dilewatkan.
    $peta = BidangPengaduan::petaDariKategori();

    expect($peta)->toHaveCount(count(KategoriPengaduan::cases()));

    foreach (KategoriPengaduan::cases() as $kategori) {
        expect($peta)->toHaveKey($kategori->value);
    }

    // Empat kategori netral bernilai string kosong, bukan hilang dari peta.
    expect(array_keys($peta, '', true))->toHaveCount(4);
});

it('menyediakan cakupan data per bidang untuk dinas sektoral', function () {
    // rules.md 5.0b poin 6a: Dinas Pertanian bercakupan Per Bidang, sedangkan
    // Dinas Transmigrasi tetap Semua sebab merekalah yang menyaring laporan
    // berbidang kosong.
    expect(CakupanData::cases())->toHaveCount(3)
        ->and(CakupanData::nilai())->toContain('Per Bidang');
});
