<?php

/*
 * Task 7.4 (hasil panen).
 *
 * Yang dijaga: seed dari data contoh; realisasi_panen + puso = realisasi_tanam;
 * satu penanaman satu panen; satuan_id disalin dari komoditas; gagal total
 * (produktivitas tak wajib); produksi dihitung ulang di peladen.
 */

use App\Models\HasilPanen;
use App\Models\Penanaman;
use App\Models\User;
use App\Support\DummyData;
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
    $petugas = User::factory()->create();
    $petugas->semuaIzin = true;
    $this->actingAs($petugas);
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

it('menanam hasil panen dari data contoh dengan satuan tersalin', function () {
    expect(HasilPanen::count())->toBe(count(DummyData::hasilPanen()));

    $h = HasilPanen::with('satuan', 'penanaman.komoditas')->find(1);
    expect($h->satuan_id)->toBe($h->penanaman->komoditas->satuan_id)
        ->and($h->satuan->nama)->toBe('Ton')
        ->and($h->uuid)->not->toBeNull();
});

it('merender daftar hasil panen', function () {
    $this->get(route('panen.index'))
        ->assertOk()
        ->assertSee('POKTAN MEKAR JAYA');
});

it('membalas 404 untuk panen yang tidak ada', function () {
    $this->get('/panen/99999')->assertNotFound();
});

it('menyimpan panen dan menghitung ulang produksi di peladen', function () {
    // Penanaman 6 belum dipanen, realisasi_tanam 1,00 ha.
    $this->post(route('panen.simpan'), [
        'penanaman_id' => 6,
        'periode_panen' => '2026-08',
        'realisasi_panen' => '1.00',
        'puso' => '0.00',
        'produktivitas' => '3.500',
        // produksi tersembunyi keliru, wajib diabaikan peladen.
        'produksi' => '999',
    ])->assertRedirect(route('panen.index'));

    $h = HasilPanen::where('penanaman_id', 6)->first();
    expect((float) $h->produksi)->toBe(3.5)
        ->and($h->satuan->nama)->toBe('Ton');
});

it('menolak realisasi panen + puso yang tidak sama dengan realisasi tanam', function () {
    $this->post(route('panen.simpan'), [
        'penanaman_id' => 6,
        'periode_panen' => '2026-08',
        'realisasi_panen' => '0.50',
        'puso' => '0.10',
        'produktivitas' => '3.000',
    ])->assertSessionHasErrors('puso');
});

it('menolak panen kedua untuk penanaman yang sudah dipanen', function () {
    $this->post(route('panen.simpan'), [
        'penanaman_id' => 1,
        'periode_panen' => '2026-06',
        'realisasi_panen' => '1.50',
        'puso' => '0.00',
        'produktivitas' => '3.000',
    ])->assertSessionHasErrors('penanaman_id');
});

it('menerima gagal total tanpa produktivitas', function () {
    $this->post(route('panen.simpan'), [
        'penanaman_id' => 6,
        'periode_panen' => '2026-08',
        'realisasi_panen' => '0.00',
        'puso' => '1.00',
    ])->assertRedirect(route('panen.index'));

    $h = HasilPanen::where('penanaman_id', 6)->first();
    expect((float) $h->produktivitas)->toBe(0.0)
        ->and((float) $h->produksi)->toBe(0.0);
});

it('mewajibkan produktivitas saat bukan gagal total', function () {
    $this->post(route('panen.simpan'), [
        'penanaman_id' => 6,
        'periode_panen' => '2026-08',
        'realisasi_panen' => '1.00',
        'puso' => '0.00',
    ])->assertSessionHasErrors('produktivitas');
});

it('menghapus catatan panen secara halus lalu penanaman dapat dihapus', function () {
    $id = HasilPanen::where('penanaman_id', 5)->value('id_hasil_panen');

    $this->delete(route('panen.hapus', $id))->assertRedirect(route('panen.index'));

    expect(HasilPanen::find($id))->toBeNull()
        ->and(Penanaman::find(5))->not->toBeNull();
});
