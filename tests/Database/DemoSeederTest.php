<?php

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
        ->and(DB::table('pengaduan')->count())->toBe(60)
        ->and(DB::table('penanaman')->count())->toBe(60)
        ->and(DB::table('hasil_panen')->count())->toBe(50)
        ->and(DB::table('pengaduan')->where('prioritas', 'Mendesak')->where('status', '!=', 'Selesai')->count())->toBeGreaterThan(0)
        ->and(DB::table('transmigran')->where('id_transmigran', 1)->value('nama_kepala_keluarga'))
        ->toBe(DummyData::transmigran()[0]['nama_kepala_keluarga']);

    $user = User::where('is_aktif', true)->first();
    $user->semuaIzin = true;
    $this->actingAs($user)->get(route('transmigran.index', ['per_halaman' => 10, 'page' => 2]))
        ->assertOk()
        ->assertViewHas('baris', fn ($baris) => $baris->count() === 10 && $baris->lastPage() === 9);
});
