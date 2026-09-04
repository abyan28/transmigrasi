<?php

namespace Database\Seeders;

use App\Models\Lahan;
use App\Support\DummyData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Lahan (Task 6.1).
 *
 * SATU BARIS = SATU KELUARGA (`UNIQUE lahan.transmigran_id`). `id_lahan`
 * dipaksa sama seperti data contoh; `uuid` ditulis sekali lalu dipertahankan.
 * `poktan_id` NULL -- poktan pengelola belum didata. `status_sertifikat` dan
 * SHM tidak ditanam di sini: keduanya milik `transmigran` (`TransmigranSeeder`
 * + `BerkasSeeder`), form lahan hanya permukaan entrinya.
 *
 * Dijalankan SETELAH `TransmigranSeeder` (FK `transmigran_id`).
 */
class LahanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DummyData::lahan() as $l) {
            $baris = Lahan::withTrashed()->firstOrNew(['id_lahan' => $l['id_lahan']]);
            $baris->uuid ??= (string) Str::uuid();
            $baris->fill([
                'transmigran_id' => $l['transmigran_id'],
                'satuan_permukiman_id' => $l['satuan_permukiman_id'],
                'poktan_id' => null,
                'kode_lahan' => $l['kode_lahan'] ?? null,
                'luas_pekarangan' => $l['luas_pekarangan'] ?? null,
                'lintang_pekarangan' => $l['lintang_pekarangan'] ?? null,
                'bujur_pekarangan' => $l['bujur_pekarangan'] ?? null,
                'luas_usaha' => $l['luas_usaha'] ?? null,
                'luas_kering' => $l['luas_kering'] ?? null,
                'luas_basah' => $l['luas_basah'] ?? null,
                'lintang_usaha' => $l['lintang_usaha'] ?? null,
                'bujur_usaha' => $l['bujur_usaha'] ?? null,
                'tujuan_pemanfaatan' => $l['tujuan_pemanfaatan'] ?? null,
                'keterangan' => $l['keterangan'] ?? null,
            ]);
            $baris->save();
        }
    }
}
