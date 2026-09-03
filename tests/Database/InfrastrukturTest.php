<?php

/*
 * Task 4.6 -- infrastruktur SP (dipindah dari Task 8.1).
 */

use App\Models\Infrastruktur;
use App\Models\User;
use Database\Seeders\InfrastrukturSeeder;
use Database\Seeders\KawasanSeeder;
use Database\Seeders\ReferensiSeeder;
use Database\Seeders\SpSeeder;
use Database\Seeders\WilayahSeeder;

require_once __DIR__.'/DatabaseHelpers.php';

beforeEach(function () {
    $petugas = User::factory()->create();
    $petugas->semuaIzin = true;
    $this->actingAs($petugas);
    $this->seed(WilayahSeeder::class);
    $this->seed(KawasanSeeder::class);
    $this->seed(SpSeeder::class);
    $this->seed(ReferensiSeeder::class);
    $this->seed(InfrastrukturSeeder::class);
});

it('menanam infrastruktur beserta cakupan lintas SP', function () {
    expect(Infrastruktur::count())->toBeGreaterThan(0);

    // Cakupan menampung kenyataan yang dulu hanya tertulis sebagai teks pada
    // `kapasitas`: satu aset dapat melayani beberapa SP sekaligus.
    $irigasi = Infrastruktur::with('cakupan')->find(1);

    expect($irigasi->cakupan->pluck('id_satuan_permukiman')->sort()->values()->all())
        ->toBe([1, 2]);
});

it('menyertakan SP pangkal pada setiap cakupan', function () {
    foreach (Infrastruktur::with('cakupan')->get() as $i) {
        expect($i->cakupan->pluck('id_satuan_permukiman'))
            ->toContain($i->satuan_permukiman_id);
    }
});

it('menyimpan infrastruktur baru', function () {
    $this->post(route('infrastruktur.simpan'), [
        'satuan_permukiman_id' => 1,
        'nama' => 'JEMBATAN UJI',
        'jenis' => 'Jalan Penghubung',
        'kondisi' => 'Baik',
        'tahun_perolehan' => 2021,
        'satuan_permukiman_ids_lain' => [2, 3],
    ])->assertRedirect(route('infrastruktur.index'));

    $baru = Infrastruktur::where('nama', 'JEMBATAN UJI')->first();

    expect($baru)->not->toBeNull()
        ->and($baru->cakupan->pluck('id_satuan_permukiman')->sort()->values()->all())->toBe([1, 2, 3]);
});

it('mewajibkan jenis dan kondisi diisi', function () {
    $this->post(route('infrastruktur.simpan'), [
        'satuan_permukiman_id' => 1,
        'nama' => 'TANPA JENIS',
    ])->assertSessionHasErrors(['jenis', 'kondisi']);
});
