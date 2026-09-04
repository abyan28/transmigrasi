<?php

/*
 * Task 7.3 (penanaman).
 *
 * Yang dijaga: seed dari data contoh; benih wajib milik poktan & komoditas yang
 * tepat; volume tak melebihi sisa jatah; realisasi_tanam tak melebihi lahan
 * kelompok yang belum ditanami; penanaman ber-panen tak dapat dihapus.
 */

use App\Models\Komoditas;
use App\Models\Penanaman;
use App\Models\Poktan;
use App\Models\SaprotanDistribusi;
use App\Models\User;
use App\Support\DummyData;
use App\Support\RekapPoktan;
use Database\Seeders\HasilPanenSeeder;
use Database\Seeders\KawasanSeeder;
use Database\Seeders\KomoditasSeeder;
use Database\Seeders\LahanSeeder;
use Database\Seeders\PenanamanSeeder;
use Database\Seeders\PoktanSeeder;
use Database\Seeders\ReferensiSeeder;
use Database\Seeders\SaprotanSeeder;
use Database\Seeders\SatuanSeeder;
use Database\Seeders\SpSeeder;
use Database\Seeders\TransmigranSeeder;
use Database\Seeders\WilayahSeeder;

require_once __DIR__.'/DatabaseHelpers.php';

beforeEach(function () {
    $petugas = User::factory()->create();
    $petugas->semuaIzin = true;
    $this->actingAs($petugas);
    $this->seed(WilayahSeeder::class);
    $this->seed(KawasanSeeder::class);
    $this->seed(SpSeeder::class);
    $this->seed(SatuanSeeder::class);
    $this->seed(ReferensiSeeder::class);
    $this->seed(TransmigranSeeder::class);
    $this->seed(LahanSeeder::class);
    $this->seed(PoktanSeeder::class);
    $this->seed(KomoditasSeeder::class);
    $this->seed(SaprotanSeeder::class);
    $this->seed(PenanamanSeeder::class);
    $this->seed(HasilPanenSeeder::class);
});

it('menanam penanaman dari data contoh', function () {
    expect(Penanaman::count())->toBe(count(DummyData::penanaman()));

    $p = Penanaman::with('komoditas', 'poktan')->find(1);
    expect($p->komoditas->nama)->toBe('JAGUNG')
        ->and($p->poktan->nama)->toBe('POKTAN MEKAR JAYA')
        ->and((float) $p->realisasi_tanam)->toBe(1.5);
});

it('merender daftar penanaman dengan status panen turunan', function () {
    // Penanaman 6 (POKTAN SUBUR MAKMUR) satu-satunya yang belum dipanen.
    $this->get(route('penanaman'))
        ->assertOk()
        ->assertSee('POKTAN SUBUR MAKMUR')
        ->assertSee('Belum Dipanen');
});

it('membalas 404 untuk penanaman yang tidak ada', function () {
    $this->get('/penanaman/99999')->assertNotFound();
});

it('menyimpan penanaman baru dengan benih yang stoknya cukup', function () {
    // Distribusi 2 (BENIH JAGUNG HIBRIDA -> POKTAN SUBUR MAKMUR, 100 kg, belum terpakai).
    $dist = SaprotanDistribusi::find(2);
    $poktan = $dist->poktan_id;
    $komoditas = $dist->saprotan->komoditas_id;

    $this->post(route('penanaman.simpan'), [
        'poktan_id' => $poktan,
        'komoditas_id' => $komoditas,
        'saprotan_distribusi_id' => 2,
        'volume_benih' => '20',
        'realisasi_tanam' => '1.00',
        'periode_tanam' => '2026-03',
    ])->assertRedirect(route('penanaman'));

    expect(Penanaman::where('saprotan_distribusi_id', 2)->where('volume_benih', 20)->exists())->toBeTrue();
});

it('menolak benih yang bukan jatah kelompok tani terpilih', function () {
    $dist = SaprotanDistribusi::find(2); // jatah poktan 2
    $poktanLain = Poktan::where('id_poktan', '!=', $dist->poktan_id)->value('id_poktan');

    $this->post(route('penanaman.simpan'), [
        'poktan_id' => $poktanLain,
        'komoditas_id' => $dist->saprotan->komoditas_id,
        'saprotan_distribusi_id' => 2,
        'volume_benih' => '10',
        'realisasi_tanam' => '0.50',
        'periode_tanam' => '2026-03',
    ])->assertSessionHasErrors('saprotan_distribusi_id');
});

it('menolak volume benih melebihi sisa jatah', function () {
    $dist = SaprotanDistribusi::find(2); // 100 kg, belum terpakai

    $this->post(route('penanaman.simpan'), [
        'poktan_id' => $dist->poktan_id,
        'komoditas_id' => $dist->saprotan->komoditas_id,
        'saprotan_distribusi_id' => 2,
        'volume_benih' => '250',
        'realisasi_tanam' => '1.00',
        'periode_tanam' => '2026-03',
    ])->assertSessionHasErrors('volume_benih');
});

it('menolak realisasi tanam melebihi lahan kelompok yang belum ditanami', function () {
    $dist = SaprotanDistribusi::find(2);
    $poktan = Poktan::find($dist->poktan_id);
    $tersedia = RekapPoktan::lahanTersedia($poktan);

    $this->post(route('penanaman.simpan'), [
        'poktan_id' => $poktan->id_poktan,
        'komoditas_id' => $dist->saprotan->komoditas_id,
        'saprotan_distribusi_id' => 2,
        'volume_benih' => '10',
        'realisasi_tanam' => number_format($tersedia + 5, 2, '.', ''),
        'periode_tanam' => '2026-03',
    ])->assertSessionHasErrors('realisasi_tanam');
});

it('menolak menghapus penanaman yang sudah punya catatan panen', function () {
    $this->from(route('penanaman.detail', 1))
        ->delete(route('penanaman.hapus', 1))
        ->assertRedirect(route('penanaman.detail', 1))
        ->assertSessionHas('galat');

    expect(Penanaman::find(1))->not->toBeNull();
});

it('menghapus penanaman yang belum dipanen secara halus', function () {
    $this->delete(route('penanaman.hapus', 6))->assertRedirect(route('penanaman'));

    expect(Penanaman::find(6))->toBeNull()
        ->and(Penanaman::withTrashed()->find(6)->trashed())->toBeTrue();
});
