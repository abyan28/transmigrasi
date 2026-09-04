<?php

namespace Database\Seeders;

use App\Models\Berkas;
use App\Models\HasilPanen;
use App\Models\Penanaman;
use App\Support\DummyData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Hasil panen (Task 7.4) + pivot `hasil_panen_berkas`.
 *
 * `id_hasil_panen` dipaksa sama seperti data contoh. `satuan_id` DISALIN dari
 * komoditas penanamannya (snapshot). `uuid` dibangkitkan bila belum ada. FK
 * penanaman -> `PenanamanSeeder`. Pivot ditanam di sini (BerkasSeeder lebih dulu).
 */
class HasilPanenSeeder extends Seeder
{
    public function run(): void
    {
        $satuanPenanaman = Penanaman::with('komoditas')
            ->get()
            ->mapWithKeys(fn (Penanaman $p) => [$p->id_penanaman => $p->komoditas?->satuan_id]);

        foreach (DummyData::hasilPanen() as $h) {
            $panen = HasilPanen::withTrashed()->firstOrNew(['id_hasil_panen' => $h['id_hasil_panen']]);
            $panen->fill([
                'penanaman_id' => $h['penanaman_id'],
                'satuan_id' => $satuanPenanaman[$h['penanaman_id']] ?? null,
                'periode_panen' => $h['periode_panen'],
                'realisasi_panen' => $h['realisasi_panen'],
                'puso' => $h['puso'] ?? null,
                'produktivitas' => $h['produktivitas'],
                'produksi' => $h['produksi'],
                'harga_jual' => $h['harga_jual'] ?? null,
                'keterangan' => $h['keterangan'] ?? null,
            ]);
            $panen->uuid ??= (string) Str::uuid();
            $panen->save();
        }

        $pivot = array_values(array_filter(
            DummyData::berkasPemilik()['hasil_panen_berkas'] ?? [],
            fn ($b) => Berkas::whereKey($b['berkas_id'])->exists()
                && HasilPanen::withTrashed()->whereKey($b['hasil_panen_id'])->exists(),
        ));

        DB::table('hasil_panen_berkas')->delete();

        if ($pivot !== []) {
            DB::table('hasil_panen_berkas')->insert($pivot);
        }
    }
}
