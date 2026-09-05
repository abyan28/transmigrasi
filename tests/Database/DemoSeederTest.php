<?php

use App\Models\Notifikasi;
use App\Models\PenilaianSp;
use App\Models\User;
use App\Support\DummyData;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\DB;

it('menanam data demo besar tanpa mengubah baris contoh awal', function () {
    $this->seed(DemoSeeder::class);

    expect(DB::table('transmigran')->count())->toBe(90)
        ->and(DB::table('anggota_keluarga')->count())->toBe(270)
        ->and(DB::table('rumah')->count())->toBe(90)
        ->and(DB::table('lahan')->count())->toBe(85)
        ->and(DB::table('poktan')->count())->toBe(30)
        ->and(DB::table('alsintan')->count())->toBe(35)
        ->and(DB::table('saprotan')->count())->toBeGreaterThanOrEqual(40)
        ->and(DB::table('pengaduan')->count())->toBe(60)
        ->and(DB::table('penanaman')->count())->toBe(60)
        ->and(DB::table('hasil_panen')->count())->toBe(50)
        ->and(DB::table('inventaris_sp')->count())->toBe(30)
        ->and(DB::table('fasilitas_sp')->count())->toBe(30)
        ->and(DB::table('pengaduan')->where('prioritas', 'Mendesak')->where('status', '!=', 'Selesai')->count())->toBeGreaterThan(0)
        ->and(DB::table('transmigran')->where('id_transmigran', 1)->value('nama_kepala_keluarga'))
        ->toBe(DummyData::transmigran()[0]['nama_kepala_keluarga']);

    // Notifikasi & riwayat penilaian_sp lewat mesin sungguhan, bukan tabel
    // kosong -- pengaduan/fasilitas demo dulunya ditulis lewat DB::table()
    // mentah, sehingga sama sekali tak tersentuh mesin notifikasi.
    expect(Notifikasi::count())->toBeGreaterThan(0)
        ->and(PenilaianSp::withoutGlobalScopes()->count())->toBeGreaterThan(0);

    $user = User::where('is_aktif', true)->first();
    $user->semuaIzin = true;
    $this->actingAs($user)->get(route('transmigran.index', ['per_halaman' => 10, 'page' => 2]))
        ->assertOk()
        ->assertViewHas('baris', fn ($baris) => $baris->count() === 10 && $baris->lastPage() === 9);
});

it('menyimpan ulang penanaman demo tanpa perubahan tanpa ditolak validasi', function () {
    // Sebelumnya `produksi()` memasangkan poktan_id/komoditas_id/
    // saprotan_distribusi_id secara lepas (tiga siklus independen) dan
    // membuat poktan demo tanpa lahan sama sekali -- menyunting lalu
    // menyimpan ulang baris apa pun akan ditolak PenanamanController
    // ("bukan jatah kelompok", "melebihi sisa benih", atau "melebihi lahan
    // yang belum ditanami"). Uji ini membuktikan celah itu tertutup: baris
    // demo TERAKHIR (pasti hasil `produksi()`, bukan data contoh tetap)
    // disunting tanpa perubahan apa pun dan wajib diterima.
    $this->seed(DemoSeeder::class);

    $user = User::where('is_aktif', true)->first();
    $user->semuaIzin = true;
    $this->actingAs($user);

    $penanaman = DB::table('penanaman')->orderByDesc('id_penanaman')->first();

    $this->put(route('penanaman.perbarui', $penanaman->id_penanaman), [
        'poktan_id' => $penanaman->poktan_id,
        'komoditas_id' => $penanaman->komoditas_id,
        'saprotan_distribusi_id' => $penanaman->saprotan_distribusi_id,
        'volume_benih' => $penanaman->volume_benih,
        'realisasi_tanam' => $penanaman->realisasi_tanam,
        'periode_tanam' => $penanaman->periode_tanam,
    ])->assertSessionHasNoErrors()
        ->assertRedirect(route('penanaman.detail', $penanaman->id_penanaman));
});
