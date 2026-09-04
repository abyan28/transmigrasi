<?php

namespace Database\Seeders;

use App\Models\Berkas;
use App\Models\Penanaman;
use App\Support\DummyData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Penanaman (Task 7.3) + pivot `penanaman_berkas`.
 *
 * `id_penanaman` dipaksa sama seperti data contoh. FK poktan -> `PoktanSeeder`,
 * komoditas -> `KomoditasSeeder`, saprotan_distribusi -> `SaprotanSeeder`
 * (seluruhnya ber-PK eksplisit sehingga id-nya stabil). Pivot ditanam di sini
 * sebab `BerkasSeeder` berjalan sebelum induknya ada.
 */
class PenanamanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DummyData::penanaman() as $p) {
            Penanaman::withTrashed()->updateOrCreate(
                ['id_penanaman' => $p['id_penanaman']],
                [
                    'poktan_id' => $p['poktan_id'],
                    'komoditas_id' => $p['komoditas_id'],
                    'saprotan_distribusi_id' => $p['saprotan_distribusi_id'],
                    'volume_benih' => $p['volume_benih'],
                    'realisasi_tanam' => $p['realisasi_tanam'],
                    'periode_tanam' => $p['periode_tanam'],
                    'keterangan' => $p['keterangan'] ?? null,
                ],
            );
        }

        $pivot = array_values(array_filter(
            DummyData::berkasPemilik()['penanaman_berkas'] ?? [],
            fn ($b) => Berkas::whereKey($b['berkas_id'])->exists()
                && Penanaman::withTrashed()->whereKey($b['penanaman_id'])->exists(),
        ));

        DB::table('penanaman_berkas')->delete();

        if ($pivot !== []) {
            DB::table('penanaman_berkas')->insert($pivot);
        }
    }
}
