<?php

/**
 * Uji penilaian kondisi satuan permukiman.
 *
 * Penilaian ini menjadi dasar indikator ke-16 dashboard dan dapat dikutip
 * pada laporan resmi, sehingga aturan hitungnya perlu dijaga agar tidak
 * bergeser tanpa disadari.
 *
 * Aturan yang paling mudah keliru bila kode kelak diubah:
 * 1. Ketiadaan aset dinilai nol, bukan dikeluarkan dari perhitungan.
 * 2. Satu parameter primer bernilai nol mengalahkan skor berapa pun.
 * 3. Kondisi terbaik yang dipakai bila ada beberapa aset sejenis.
 */

use App\Enums\StatusKondisiSp;
use App\Enums\TingkatKebutuhan;
use App\Support\PenilaianKondisiSp;

/*
|--------------------------------------------------------------------------
| Susunan parameter dan bobot
|--------------------------------------------------------------------------
*/

it('memakai bobot 5, 3, dan 1 sesuai tingkat kebutuhan', function () {
    // Jarak lebar disengaja agar kegagalan layanan dasar tidak tertutupi
    // kelengkapan fasilitas penunjang (agents/rules.md bagian 10c.3).
    expect(TingkatKebutuhan::Primer->bobotBawaan())->toBe(5)
        ->and(TingkatKebutuhan::Sekunder->bobotBawaan())->toBe(3)
        ->and(TingkatKebutuhan::Tersier->bobotBawaan())->toBe(1);
});

it('menetapkan tiga parameter primer yang menentukan kelayakan huni', function () {
    $primer = array_filter(
        PenilaianKondisiSp::parameter(),
        fn ($p) => $p['tingkat'] === TingkatKebutuhan::Primer
    );

    $kode = array_column($primer, 'kode');

    expect($kode)->toContain('air_bersih')
        ->toContain('jalan_penghubung')
        ->toContain('listrik')
        ->toHaveCount(3);
});

it('memberi bobot sesuai tingkat pada setiap parameter', function () {
    foreach (PenilaianKondisiSp::parameter() as $p) {
        expect($p['bobot'])->toBe($p['tingkat']->bobotBawaan(), "Bobot {$p['kode']} tidak sesuai tingkatnya");
    }
});

/*
|--------------------------------------------------------------------------
| Aturan primer nol
|--------------------------------------------------------------------------
*/

it('menjadikan SP Perlu Penanganan bila satu parameter primer tidak ada', function () {
    // Inti aturan: rata-rata tidak boleh menutupi kegagalan pada hal mutlak.
    // SP tanpa air bersih tetapi lengkap penunjangnya dapat mencapai skor
    // tinggi, dan angka itu menyesatkan (agents/rules.md 10c.4 poin 11).
    expect(StatusKondisiSp::dariSkor(95.0, adaPrimerNol: true))
        ->toBe(StatusKondisiSp::PerluPenanganan);

    expect(StatusKondisiSp::dariSkor(100.0, adaPrimerNol: true))
        ->toBe(StatusKondisiSp::PerluPenanganan);
});

it('menandai SP yang kehilangan layanan dasar', function () {
    // SP Weoe sengaja tanpa listrik, SP Weain tanpa air dan jalan penghubung.
    $weoe = PenilaianKondisiSp::nilai(4);
    $weain = PenilaianKondisiSp::nilai(6);

    expect($weoe['ada_primer_nol'])->toBeTrue()
        ->and($weoe['status'])->toBe(StatusKondisiSp::PerluPenanganan)
        ->and($weain['ada_primer_nol'])->toBeTrue()
        ->and($weain['status'])->toBe(StatusKondisiSp::PerluPenanganan);
});

it('tidak menandai primer nol bila seluruh layanan dasar tersedia', function () {
    // SP Kapitan Meo memiliki air, listrik, dan jalan penghubung.
    $hasil = PenilaianKondisiSp::nilai(1);

    expect($hasil['ada_primer_nol'])->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Ketiadaan berbeda dari kerusakan
|--------------------------------------------------------------------------
*/

it('menilai parameter tanpa aset sebagai Tidak Ada, bukan mengabaikannya', function () {
    // Bila parameter tanpa aset dikeluarkan dari perhitungan, skor SP yang
    // paling membutuhkan perhatian justru akan naik.
    $hasil = PenilaianKondisiSp::nilai(6);

    $air = collect($hasil['rincian'])->firstWhere('kode', 'air_bersih');

    expect($air['kondisi'])->toBe('Tidak Ada')
        ->and($air['nilai'])->toBe(0.0);

    // Seluruh parameter tetap ikut dihitung, tidak ada yang hilang.
    expect($hasil['rincian'])->toHaveCount(count(PenilaianKondisiSp::parameter()));
});

it('membedakan nilai rusak berat dari tidak ada', function () {
    // Yang satu memerlukan perbaikan, yang lain pembangunan. Menyamakan
    // keduanya menyembunyikan perbedaan penanganan.
    expect(PenilaianKondisiSp::NILAI_KONDISI['Rusak Berat'])->toBe(0.2)
        ->and(PenilaianKondisiSp::NILAI_TIDAK_ADA)->toBe(0.0);
});

/*
|--------------------------------------------------------------------------
| Perhitungan skor
|--------------------------------------------------------------------------
*/

it('menghitung skor pada rentang nol sampai seratus', function () {
    foreach (PenilaianKondisiSp::nilaiSeluruhSp() as $p) {
        expect($p['skor'])->toBeGreaterThanOrEqual(0)
            ->and($p['skor'])->toBeLessThanOrEqual(100);
    }
});

it('memakai kondisi terbaik bila ada beberapa aset sejenis', function () {
    // Satu sumur bor yang berfungsi sudah memenuhi kebutuhan air meski ada
    // sumur lain yang rusak. Rata-rata akan menghukum SP yang justru punya
    // aset cadangan.
    $infrastruktur = [
        ['satuan_permukiman_id' => 99, 'jenis' => 'Air', 'kondisi' => 'Rusak Berat'],
        ['satuan_permukiman_id' => 99, 'jenis' => 'Air', 'kondisi' => 'Baik'],
    ];

    $hasil = PenilaianKondisiSp::nilai(99, $infrastruktur, []);
    $air = collect($hasil['rincian'])->firstWhere('kode', 'air_bersih');

    expect($air['kondisi'])->toBe('Baik')
        ->and($air['nilai'])->toBe(1.0);
});

it('memberi skor nol untuk SP tanpa satu pun aset', function () {
    $hasil = PenilaianKondisiSp::nilai(999, [], []);

    expect($hasil['skor'])->toBe(0.0)
        ->and($hasil['ada_primer_nol'])->toBeTrue()
        ->and($hasil['status'])->toBe(StatusKondisiSp::PerluPenanganan);
});

/*
|--------------------------------------------------------------------------
| Ambang batas status
|--------------------------------------------------------------------------
*/

it('menyimpulkan status sesuai ambang batas', function () {
    expect(StatusKondisiSp::dariSkor(80.0, false))->toBe(StatusKondisiSp::Mandiri)
        ->and(StatusKondisiSp::dariSkor(79.9, false))->toBe(StatusKondisiSp::Berkembang)
        ->and(StatusKondisiSp::dariSkor(55.0, false))->toBe(StatusKondisiSp::Berkembang)
        ->and(StatusKondisiSp::dariSkor(54.9, false))->toBe(StatusKondisiSp::PerluPenanganan);
});

/*
|--------------------------------------------------------------------------
| Bahasa yang dipakai
|--------------------------------------------------------------------------
*/

it('tidak memakai istilah yang merendahkan penghuni', function () {
    // Yang dinilai adalah jalan dan listrik, hal yang berada di luar kendali
    // warga. Label bernada merendahkan dilarang (agents/rules.md 10c.1).
    $terlarang = ['terbelakang', 'tertinggal', 'miskin', 'gagal', 'buruk'];

    foreach (StatusKondisiSp::cases() as $status) {
        $teks = mb_strtolower($status->value . ' ' . $status->keterangan());

        foreach ($terlarang as $kata) {
            expect($teks)->not->toContain($kata);
        }
    }
});

it('menyediakan keterangan untuk setiap status dan tingkat', function () {
    foreach (StatusKondisiSp::cases() as $status) {
        expect($status->keterangan())->not->toBeEmpty();
    }

    foreach (TingkatKebutuhan::cases() as $tingkat) {
        expect($tingkat->keterangan())->not->toBeEmpty();
    }
});

/*
|--------------------------------------------------------------------------
| Rincian pembentuk skor
|--------------------------------------------------------------------------
*/

it('menyalin bobot yang dipakai ke dalam rincian', function () {
    // Bobot dapat diubah Admin. Tanpa salinan, laporan yang sudah dicetak
    // akan berbeda dari tampilan sistem setelah bobot berubah.
    $hasil = PenilaianKondisiSp::nilai(1);

    foreach ($hasil['rincian'] as $r) {
        expect($r)->toHaveKeys(['kode', 'nama', 'tingkat', 'bobot', 'kondisi', 'nilai'])
            ->and($r['bobot'])->toBeGreaterThan(0);
    }
});

it('menyebutkan penyebab pada SP yang bermasalah', function () {
    // Label tanpa rincian berhenti sebagai stempel dan tidak membantu
    // perencanaan (agents/rules.md 10c.1 poin 4).
    $weain = PenilaianKondisiSp::nilai(6);
    $penyebab = PenilaianKondisiSp::penyebabUtama($weain);

    expect($penyebab)->not->toBeEmpty();

    $gabungan = implode(' ', $penyebab);
    expect($gabungan)->toContain('Air Bersih')
        ->and($gabungan)->toContain('Jalan Penghubung');
});

/*
|--------------------------------------------------------------------------
| Rekap kawasan
|--------------------------------------------------------------------------
*/

it('menilai keenam satuan permukiman', function () {
    expect(PenilaianKondisiSp::nilaiSeluruhSp())->toHaveCount(6);
});

it('menjumlahkan rekap status sesuai jumlah SP', function () {
    $rekap = PenilaianKondisiSp::rekapStatus();

    expect(array_sum($rekap))->toBe(6)
        ->and(array_keys($rekap))->toHaveCount(count(StatusKondisiSp::cases()));
});

it('menghasilkan sebaran status yang beragam', function () {
    // Data contoh sengaja disusun agar ketiga status terwakili, sehingga
    // tampilan dapat diperagakan apa adanya saat validasi bersama dinas.
    $rekap = PenilaianKondisiSp::rekapStatus();

    expect($rekap[StatusKondisiSp::Mandiri->value])->toBeGreaterThan(0)
        ->and($rekap[StatusKondisiSp::Berkembang->value])->toBeGreaterThan(0)
        ->and($rekap[StatusKondisiSp::PerluPenanganan->value])->toBeGreaterThan(0);
});
