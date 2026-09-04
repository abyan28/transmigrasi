<?php

namespace Database\Seeders;

use App\Models\Komoditas;
use App\Models\Satuan;
use App\Support\DummyData;
use Illuminate\Database\Seeder;

/**
 * Jenis komoditas pertanian (Task 7.1).
 *
 * `id_komoditas` dipaksa sama seperti data contoh. `slug` diturunkan
 * `BerslugOtomatis` saat penyimpanan. FK `satuan_id` -> `SatuanSeeder`
 * (data master). Pivot `komoditas_poktan` belum berdata contoh.
 */
class KomoditasSeeder extends Seeder
{
    public function run(): void
    {
        // `satuan_id` data contoh (1..3) tidak dapat dipercaya di uji: rollback
        // RefreshDatabase tidak menyetel ulang AUTO_INCREMENT, jadi baris
        // `satuan` bisa ber-id > 3. Resolusi lewat nama.
        $satuanPerNama = Satuan::pluck('id_satuan', 'nama');

        foreach (DummyData::komoditas() as $k) {
            $komoditas = Komoditas::withTrashed()->firstOrNew(['id_komoditas' => $k['id_komoditas']]);
            $komoditas->fill([
                'satuan_id' => $satuanPerNama[$k['satuan']] ?? $k['satuan_id'],
                'nama' => $k['nama'],
                'tipe' => $k['tipe'],
                'is_unggulan' => $k['is_unggulan'] ?? false,
                'deskripsi' => $k['deskripsi'] ?? null,
            ]);
            $komoditas->save();
        }
    }
}
