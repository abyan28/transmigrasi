<?php

namespace Database\Seeders;

use App\Models\Berkas;
use App\Models\Komoditas;
use App\Models\Saprotan;
use App\Models\SaprotanDistribusi;
use App\Models\Satuan;
use App\Support\DummyData;
use Illuminate\Database\Seeder;

/**
 * Saprotan: pengadaan + distribusi per poktan (Task 6.7).
 *
 * `id_saprotan` / `id_saprotan_distribusi` dipaksa sama seperti data contoh.
 * `satuan_id` & `komoditas_id` diresolusi lewat NAMA (id data contoh tak dapat
 * dipercaya di uji: rollback RefreshDatabase tak menyetel ulang AUTO_INCREMENT).
 * FK poktan -> `PoktanSeeder`, komoditas -> `KomoditasSeeder`, satuan ->
 * `SatuanSeeder`, berkas -> `BerkasSeeder`.
 */
class SaprotanSeeder extends Seeder
{
    public function run(): void
    {
        $satuanPerNama = Satuan::pluck('id_satuan', 'nama');
        $komoditasPerNama = Komoditas::pluck('id_komoditas', 'nama');

        foreach (DummyData::saprotan() as $s) {
            $saprotan = Saprotan::withTrashed()->firstOrNew(['id_saprotan' => $s['id_saprotan']]);
            $saprotan->fill([
                'satuan_id' => $satuanPerNama[$s['satuan']] ?? $s['satuan_id'],
                'komoditas_id' => $s['komoditas'] === null ? null : ($komoditasPerNama[$s['komoditas']] ?? null),
                'jenis' => $s['jenis'],
                'nama' => $s['nama'],
                'jumlah_total' => $s['jumlah_total'],
                'varietas' => $s['varietas'] ?? null,
                'jadwal_tanam' => $s['jadwal_tanam'] ?? null,
                'tahun_pengadaan' => $s['tahun_pengadaan'],
                'sumber_dana' => $s['sumber_dana'] ?? null,
            ]);
            $saprotan->save();

            foreach (['foto_berkas_id' => $s['foto_berkas_id'] ?? null, 'berkas_id' => $s['berkas_id'] ?? null] as $kolom => $berkasId) {
                if ($berkasId !== null && Berkas::whereKey($berkasId)->exists()) {
                    $saprotan->forceFill([$kolom => $berkasId])->save();
                }
            }
        }

        foreach (DummyData::saprotanDistribusi() as $d) {
            SaprotanDistribusi::updateOrCreate(
                ['id_saprotan_distribusi' => $d['id_saprotan_distribusi']],
                [
                    'saprotan_id' => $d['saprotan_id'],
                    'poktan_id' => $d['poktan_id'],
                    'jumlah' => $d['jumlah'],
                    'tanggal_serah' => $d['tanggal_serah'] ?? null,
                    'keterangan' => $d['keterangan'] ?? null,
                ],
            );
        }
    }
}
