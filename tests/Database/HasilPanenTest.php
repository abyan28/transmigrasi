<?php

/*
 * Task 7.4 (hasil panen).
 *
 * Yang dijaga: seed dari data contoh; realisasi_panen + puso = realisasi_tanam;
 * satu penanaman satu panen; satuan_id disalin dari komoditas; gagal total
 * (produktivitas tak wajib); produksi dihitung ulang di peladen.
 */

use App\Enums\CakupanData;
use App\Models\HasilPanen;
use App\Models\Penanaman;
use App\Models\Role;
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

it('menolak periode panen sebelum periode tanam', function () {
    $this->post(route('panen.simpan'), [
        'penanaman_id' => 6,
        'periode_panen' => '2026-05',
        'realisasi_panen' => '1.00',
        'puso' => '0.00',
        'produktivitas' => '3.000',
    ])->assertSessionHasErrors('periode_panen');
});

it('menutup akses penanaman di luar cakupan', function () {
    $role = Role::factory()->create(['cakupan_data' => CakupanData::PerSp]);
    $petugas = User::factory()->create(['role_id' => $role->id_role]);
    $petugas->semuaIzin = true;
    $petugas->satuanPermukiman()->attach(1);
    $this->actingAs($petugas);

    $this->post(route('panen.simpan'), [
        'penanaman_id' => 4,
        'periode_panen' => '2026-01',
        'realisasi_panen' => '0.30',
        'puso' => '0.00',
        'produktivitas' => '3.000',
    ])->assertNotFound();
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

it('membatalkan panen dengan jejak audit lalu membolehkan pengganti aktif', function () {
    $id = HasilPanen::where('penanaman_id', 5)->value('id_hasil_panen');
    $lama = HasilPanen::findOrFail($id);

    $this->delete(route('panen.hapus', $id), [
        'alasan' => 'Angka timbang salah dicatat.',
    ])->assertRedirect(route('panen.index'));

    $lama->refresh();
    expect($lama->status)->toBe('Dibatalkan')
        ->and($lama->dibatalkan_pada)->not->toBeNull()
        ->and($lama->dibatalkan_oleh)->not->toBeNull()
        ->and($lama->alasan_pembatalan)->toBe('Angka timbang salah dicatat.')
        ->and(Penanaman::findOrFail(5)->hasilPanen)->toBeNull()
        ->and(Penanaman::findOrFail(5)->riwayatHasilPanen)->toHaveCount(1);

    $this->post(route('panen.simpan'), [
        'penanaman_id' => 5,
        'periode_panen' => $lama->periode_panen,
        'realisasi_panen' => (string) $lama->realisasi_panen,
        'puso' => (string) ($lama->puso ?? '0.00'),
        'produktivitas' => (string) $lama->produktivitas,
    ])->assertSessionHasNoErrors()->assertRedirect(route('panen.index'));

    expect(HasilPanen::where('penanaman_id', 5)->count())->toBe(2)
        ->and(HasilPanen::where('penanaman_id', 5)->where('status', 'Aktif')->count())->toBe(1);
});

it('mewajibkan alasan saat membatalkan panen', function () {
    $panen = HasilPanen::firstOrFail();

    $this->delete(route('panen.hapus', $panen->id_hasil_panen))
        ->assertSessionHasErrors('alasan');

    expect($panen->fresh()->status)->toBe('Aktif');
});

it('menampilkan panen batal sebagai riwayat tetapi mengeluarkannya dari daftar aktif', function () {
    $panen = HasilPanen::where('penanaman_id', 5)->firstOrFail();

    $this->delete(route('panen.hapus', $panen->id_hasil_panen), [
        'alasan' => 'Duplikasi hasil timbang.',
    ])->assertRedirect(route('panen.index'));

    $this->get(route('panen.index'))
        ->assertOk()
        ->assertDontSee('Duplikasi hasil timbang.');

    $this->get(route('panen.detail', $panen->id_hasil_panen))
        ->assertOk()
        ->assertSee('Dibatalkan')
        ->assertSee('Duplikasi hasil timbang.');

    $this->get(route('penanaman.detail', $panen->penanaman_id))
        ->assertOk()
        ->assertSee('Dibatalkan');
});
