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
use App\Enums\BidangPengaduan;
use App\Enums\CakupanData;
use App\Enums\JenisInfrastruktur;
use App\Enums\KategoriPengaduan;
use App\Enums\Kondisi;
use App\Enums\KondisiRumah;
use App\Enums\PrioritasPengaduan;
use App\Enums\StatusPengaduan;
use App\Enums\SumberDana;

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
    'kondisi aset' => [Kondisi::class, ['Baik', 'Rusak Ringan', 'Rusak Berat']],
    'bidang pengaduan' => [BidangPengaduan::class, ['Ketransmigrasian', 'Pertanian']],
    'cakupan data' => [CakupanData::class, ['Semua', 'Per SP', 'Milik Sendiri']],
    'aksi permission' => [AksiPermission::class, ['lihat', 'tambah', 'ubah', 'hapus', 'export']],
]);

it('membedakan kondisi rumah dari kondisi aset lain', function () {
    // rules.md 6a.3 menetapkan istilah "Tidak Rusak" khusus untuk rumah,
    // sedangkan aset lain memakai "Baik".
    expect(KondisiRumah::TidakRusak->value)->toBe('Tidak Rusak')
        ->and(Kondisi::Baik->value)->toBe('Baik');
});

it('memuat sembilan kategori pengaduan tanpa spasi berlebih', function () {
    expect(KategoriPengaduan::cases())->toHaveCount(9);

    foreach (KategoriPengaduan::cases() as $kategori) {
        expect($kategori->value)->toBe(trim($kategori->value));
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

it('meneruskan pengaduan bidang pertanian ke Dinas Pertanian', function (KategoriPengaduan $kategori) {
    expect(BidangPengaduan::dariKategori($kategori))->toBe(BidangPengaduan::Pertanian);
})->with([
    'lahan usaha' => KategoriPengaduan::LahanUsaha,
    'alsintan' => KategoriPengaduan::Alsintan,
    'produksi panen' => KategoriPengaduan::ProduksiPanen,
]);

it('meneruskan pengaduan lainnya ke Dinas Transmigrasi', function (KategoriPengaduan $kategori) {
    expect(BidangPengaduan::dariKategori($kategori))->toBe(BidangPengaduan::Ketransmigrasian);
})->with([
    'rumah' => KategoriPengaduan::Rumah,
    'infrastruktur' => KategoriPengaduan::Infrastruktur,
    'lahan pekarangan' => KategoriPengaduan::LahanPekarangan,
    'bencana' => KategoriPengaduan::Bencana,
]);

it('menetapkan bidang untuk seluruh kategori tanpa terkecuali', function () {
    foreach (KategoriPengaduan::cases() as $kategori) {
        expect(BidangPengaduan::dariKategori($kategori))->toBeInstanceOf(BidangPengaduan::class);
    }
});


