<?php

/*
 * Task 7.6 (rekap panen) -- App\Support\RekapPanen, ber-Eloquent.
 *
 * Yang dijaga: basis penanaman (poktan yang belum panen tetap tampil);
 * penggolongan tahun panen (bukan tahun tanam); produktivitas tertimbang;
 * cocok dengan angka DummyData::rekapPanen() yang menjadi acuan tampilan.
 */

use App\Models\Penanaman;
use App\Models\User;
use App\Support\DummyData;
use App\Support\RekapPanen;
use Database\Seeders\DaftarPilihanSeeder;
use Database\Seeders\HasilPanenSeeder;
use Database\Seeders\KawasanSeeder;
use Database\Seeders\KomoditasSeeder;
use Database\Seeders\LahanSeeder;
use Database\Seeders\PenanamanSeeder;
use Database\Seeders\PoktanSeeder;
use Database\Seeders\SaprotanSeeder;
use Database\Seeders\SatuanSeeder;
use Database\Seeders\SpSeeder;
use Database\Seeders\TransmigranSeeder;
use Database\Seeders\WilayahSeeder;

require_once __DIR__.'/DatabaseHelpers.php';

beforeEach(function () {
    $this->seed(WilayahSeeder::class);
    $this->seed(KawasanSeeder::class);
    $this->seed(SpSeeder::class);
    $this->seed(SatuanSeeder::class);
    $this->seed(DaftarPilihanSeeder::class);
    $this->seed(TransmigranSeeder::class);
    $this->seed(LahanSeeder::class);
    $this->seed(PoktanSeeder::class);
    $this->seed(KomoditasSeeder::class);
    $this->seed(SaprotanSeeder::class);
    $this->seed(PenanamanSeeder::class);
    $this->seed(HasilPanenSeeder::class);
});

it('menggolongkan penanaman menurut tahun panen, bukan tahun tanam', function () {
    // Penanaman 1: tanam 2025-11, panen 2026-04 -> rekap 2026.
    $p = Penanaman::find(1);
    expect(RekapPanen::tahunRekap($p))->toBe(2026);

    // Penanaman 6: belum dipanen -> tahun berjalan.
    expect(RekapPanen::tahunRekap(Penanaman::find(6)))->toBe((int) date('Y'));
});

it('tetap menampilkan poktan yang sudah menanam tetapi belum panen', function () {
    // Penanaman 6 milik POKTAN SUBUR MAKMUR belum dipanen.
    $rekap = collect(RekapPanen::rekap('poktan', (int) date('Y')));
    $subur = $rekap->firstWhere('nama', 'POKTAN SUBUR MAKMUR');

    expect($subur)->not->toBeNull()
        ->and($subur['belum_dipanen'])->toBeGreaterThan(0.0)
        ->and($subur['hasil_panen'])->toBe(0.0);
});

it('menghitung produktivitas rekap secara tertimbang', function () {
    foreach (RekapPanen::rekap('sp', 2026) as $baris) {
        $harap = $baris['hasil_panen'] > 0
            ? round($baris['produksi_ton'] / $baris['hasil_panen'], 3)
            : 0.0;

        expect($baris['produktivitas_ton'])->toBe($harap);
    }
});

it('cocok dengan angka acuan DummyData::rekapPanen', function () {
    foreach (['sp', 'poktan', 'komoditas'] as $kelompok) {
        $eloquent = collect(RekapPanen::rekap($kelompok, 2026))->keyBy('nama');
        $acuan = collect(DummyData::rekapPanen($kelompok, 2026))->keyBy('nama');

        expect($eloquent->keys()->sort()->values()->all())
            ->toBe($acuan->keys()->sort()->values()->all());

        foreach ($acuan as $nama => $baris) {
            foreach (['realisasi_tanam', 'hasil_panen', 'puso', 'belum_dipanen', 'produksi_ton', 'produktivitas_ton', 'jumlah_poktan'] as $kolom) {
                expect(round((float) $eloquent[$nama][$kolom], 3))
                    ->toBe(round((float) $baris[$kolom], 3), "{$kelompok}/{$nama}/{$kolom}");
            }
        }
    }
});

it('menyaring rekap secara silang menurut sp dan komoditas', function () {
    $semua = collect(RekapPanen::rekap('sp', 2026))->sum('realisasi_tanam');
    $jagung = collect(RekapPanen::rekap('sp', 2026, null, 'JAGUNG'))->sum('realisasi_tanam');

    expect($jagung)->toBeLessThan($semua)->toBeGreaterThan(0.0);
});

it('merender halaman rekap panen pada ketiga dasar pengelompokan', function () {
    $petugas = User::factory()->create();
    $petugas->semuaIzin = true;
    $this->actingAs($petugas);

    foreach (['sp', 'komoditas', 'poktan'] as $kelompok) {
        $this->get(route('panen.rekap.kelompok', ['kelompok' => $kelompok]))
            ->assertOk()
            ->assertSee('Rekap');
    }
});
