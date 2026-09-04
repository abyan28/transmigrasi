<?php

/*
 * Task 9.1 (2026-09-04, rules.md 8g dibalik) -- App\Support\RekapDashboard
 * dari Eloquent, berjalan di MySQL nyata. Kecocokan dengan SQLite (Feature
 * suite) tidak menjamin `groupBy`/`selectRaw` aman di MySQL sungguhan
 * (mis. ONLY_FULL_GROUP_BY) -- diuji terpisah di sini.
 */

use App\Enums\PendidikanTerakhir;
use App\Models\SatuanPermukiman;
use App\Models\Transmigran;
use App\Models\User;
use App\Support\LaporanData;
use App\Support\RekapDashboard;
use Database\Seeders\DaftarPilihanSeeder;
use Database\Seeders\HasilPanenSeeder;
use Database\Seeders\KawasanSeeder;
use Database\Seeders\KomoditasSeeder;
use Database\Seeders\LahanSeeder;
use Database\Seeders\PenanamanSeeder;
use Database\Seeders\PoktanSeeder;
use Database\Seeders\RumahSeeder;
use Database\Seeders\SaprotanSeeder;
use Database\Seeders\SatuanSeeder;
use Database\Seeders\SpSeeder;
use Database\Seeders\TransmigranSeeder;
use Database\Seeders\WilayahSeeder;

beforeEach(function () {
    $this->seed(WilayahSeeder::class);
    $this->seed(KawasanSeeder::class);
    $this->seed(SpSeeder::class);
    $this->seed(SatuanSeeder::class);
    $this->seed(DaftarPilihanSeeder::class);
    $this->seed(TransmigranSeeder::class);
    $this->seed(RumahSeeder::class);
    $this->seed(LahanSeeder::class);
    $this->seed(PoktanSeeder::class);
    $this->seed(KomoditasSeeder::class);
    $this->seed(SaprotanSeeder::class);
    $this->seed(PenanamanSeeder::class);
    $this->seed(HasilPanenSeeder::class);

    $petugas = User::factory()->create();
    $petugas->semuaIzin = true;
    $this->actingAs($petugas);
});

it('menghitung ringkasan dashboard dari data sungguhan, bukan larik tetap', function () {
    $r = RekapDashboard::ringkasan();

    expect($r['jumlah_kk'])->toBe(Transmigran::where('status_tinggal', 'Aktif')->count())
        ->and($r['jumlah_kk'])->toBeGreaterThan(0)
        ->and($r['rumah_total'])->toBeGreaterThan(0)
        ->and($r['luas_lahan_total'])->toBeGreaterThan(0.0);

    // Identitas rules.md 9.9: realisasi_tanam = hasil_panen + puso + belum_dipanen.
    expect(round($r['hasil_panen_ha'] + $r['puso_ha'] + $r['belum_dipanen_ha'], 2))
        ->toBe(round($r['realisasi_tanam_ha'], 2));
});

it('menandai KK keluar lewat transmigran.tahun_keluar, bukan riwayat tersembunyi', function () {
    $keluar = Transmigran::where('nama_kepala_keluarga', 'DOMINGGUS TAEK')->first();
    expect($keluar)->not->toBeNull()
        ->and((int) $keluar->tahun_keluar)->toBe(2025);

    $deret = RekapDashboard::deret();
    $idx = array_search(2025, $deret['tahun'], true);

    expect($idx)->not->toBeFalse()
        ->and($deret['kk_keluar'][$idx])->toBeGreaterThanOrEqual(1);
});

it('menjumlahkan perSp sama dengan ringkasan kawasan untuk jumlah_kk', function () {
    $r = RekapDashboard::ringkasan();
    $perSp = RekapDashboard::perSp();

    expect(array_sum(array_column($perSp, 'jumlah_kk')))->toBe($r['jumlah_kk']);
});

it('mengisi jenjang pendidikan yang kosong sebagai nol pada rekap per tahun', function () {
    $tahun = (int) date('Y');
    $pendidikan = RekapDashboard::pendidikanPerTahun($tahun);

    expect($pendidikan)->toHaveCount(count(PendidikanTerakhir::nilai()))
        ->and(array_filter($pendidikan, fn ($j) => $j === 0))->not->toBeEmpty();
});

it('merender dashboard dan rekap kependudukan tanpa galat query MySQL', function () {
    $this->get(route('beranda'))->assertOk();

    foreach (['tahun', 'sp', 'status', 'pekerjaan', 'asal', 'pendidikan'] as $kelompok) {
        $this->get(route('kependudukan.rekap.kelompok', ['kelompok' => $kelompok]))->assertOk();
    }
});

it('merender laporan indikator-kawasan dan monografi-sp tanpa galat query MySQL setelah Task 9.1 lanjutan', function () {
    // rules.md 8g dibalik 2026-09-04: kedua laporan ini sebelumnya TERBLOKIR
    // (Task 10.5) sebab bergantung pada larik tetap DummyData berskala
    // kawasan. Sekarang lewat RekapDashboard -- diuji terpisah di MySQL
    // sungguhan sebab groupBy/selectRaw pada kkJiwaSpTahun()/ringkasanTahun()
    // belum tentu aman di sana (ONLY_FULL_GROUP_BY dsb).
    $this->get(route('laporan.indikator-kawasan'))->assertOk();
    $this->get(route('laporan.monografi-sp'))->assertOk();
});

it('menyamakan identitas kk perSp indikator-kawasan dengan RekapDashboard::ringkasan di MySQL nyata', function () {
    $perSp = LaporanData::indikatorKawasan()['perSp'];
    $ringkasan = RekapDashboard::ringkasan();

    expect(array_sum(array_column($perSp, 'jumlah_kk')))->toBe($ringkasan['jumlah_kk']);
});

/*
 * Task 9.2 (2026-09-05, rules.md 11 poin 4) -- filter wilayah + periode.
 * `Builder::where('satuan_permukiman_id', ...)` dan `whereHas()` bersarang
 * (hargaRataRata) belum tentu aman di MySQL sungguhan sebagaimana di SQLite.
 */
it('menjumlahkan ringkasan(spId) per SP sama dengan ringkasan kawasan penuh di MySQL nyata', function () {
    $kawasan = RekapDashboard::ringkasan();

    $totalKk = 0;
    foreach (SatuanPermukiman::pluck('id_satuan_permukiman') as $id) {
        $totalKk += RekapDashboard::ringkasan((int) $id)['jumlah_kk'];
    }

    expect($totalKk)->toBe($kawasan['jumlah_kk']);
});

it('menghitung hargaRataRata per SP lewat whereHas bersarang tanpa galat MySQL', function () {
    $id = SatuanPermukiman::query()->value('id_satuan_permukiman');

    // Hanya menuntut tak melempar galat (mis. ONLY_FULL_GROUP_BY pada
    // whereHas bertingkat penanaman.poktan.satuanPermukiman) -- nilainya
    // boleh nol bila SP itu kebetulan belum panen tahun acuan.
    $r = RekapDashboard::ringkasan((int) $id);

    expect($r['harga_rata_rata'])->toBeGreaterThanOrEqual(0.0);
});

it('memotong deret ber-spId lewat potongDeret tanpa galat MySQL', function () {
    $id = SatuanPermukiman::query()->value('id_satuan_permukiman');
    $deret = RekapDashboard::deret((int) $id);

    expect(count($deret['tahun']))->toBeGreaterThan(0);

    $dipotong = RekapDashboard::potongDeret($deret, $deret['tahun'][0], $deret['tahun'][0]);

    expect($dipotong['tahun'])->toBe([$deret['tahun'][0]])
        ->and($dipotong['jumlah_kk'])->toHaveCount(1);
});

it('merender dashboard dengan filter sp dan periode aktif tanpa galat query MySQL', function () {
    $id = SatuanPermukiman::query()->value('id_satuan_permukiman');
    $tahun = (int) date('Y');

    $this->get(route('beranda', ['sp' => $id, 'tahun_awal' => $tahun, 'tahun_akhir' => $tahun]))
        ->assertOk()
        ->assertSee('Menampilkan:');
});
