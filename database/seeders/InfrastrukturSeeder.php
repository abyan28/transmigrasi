<?php

namespace Database\Seeders;

use App\Models\Infrastruktur;
use App\Support\DummyData;
use Illuminate\Database\Seeder;

/**
 * Infrastruktur SP (Task 4.6, dipindah dari Task 8.1).
 *
 * `satuan_permukiman_id` adalah LOKASI/PANGKAL, sedangkan pivot
 * `infrastruktur_sp` mencatat SP mana saja yang benar-benar DILAYANI.
 * Sebelum Putaran 7 kenyataan itu hanya tertulis pada `kapasitas` sebagai
 * teks ("Melayani 3 SP sekitar"), sebab satu FK tunggal tak menampungnya --
 * dan penilaian kondisi SP karena itu tak dapat membacanya.
 *
 * SP pangkal WAJIB ikut tercantum pada cakupannya.
 */
class InfrastrukturSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DummyData::infrastruktur() as $i) {
            $infra = Infrastruktur::updateOrCreate(
                ['id_infrastruktur' => $i['id_infrastruktur']],
                [
                    'satuan_permukiman_id' => $i['satuan_permukiman_id'],
                    'nama' => $i['nama'],
                    'jenis' => $i['jenis'],
                    'tahun_perolehan' => $i['tahun_perolehan'],
                    'sumber_dana' => $i['sumber_dana'],
                    'kondisi' => $i['kondisi'],
                    'kapasitas' => $i['kapasitas'] ?? null,
                    'lintang' => $i['lintang'] ?? null,
                    'bujur' => $i['bujur'] ?? null,
                    'keterangan' => $i['keterangan'] ?? null,
                ],
            );

            $infra->cakupan()->sync($i['satuan_permukiman_ids'] ?? [$i['satuan_permukiman_id']]);
        }
    }
}
