<?php

namespace Database\Seeders;

use App\Models\Alsintan;
use App\Models\AlsintanDistribusi;
use App\Models\Berkas;
use App\Support\DummyData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Alsintan: pengadaan + distribusi per poktan + pivot berkas (Task 6.6).
 *
 * `id_alsintan` / `id_alsintan_distribusi` dipaksa sama seperti data contoh.
 * FK poktan/penanda -> `PoktanSeeder`; FK berkas -> `BerkasSeeder`. Karena
 * dijalankan SETELAH keduanya, pivot `alsintan_berkas` ditanam di sini
 * (BerkasSeeder melewatinya: induknya belum ada saat itu).
 */
class AlsintanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DummyData::alsintan() as $a) {
            $alsintan = Alsintan::withTrashed()->firstOrNew(['id_alsintan' => $a['id_alsintan']]);
            $alsintan->fill([
                'jenis_alsintan' => $a['jenis_alsintan'],
                'nama_alat' => $a['nama_alat'],
                'jumlah_total' => $a['jumlah_total'],
                'tahun_pengadaan' => $a['tahun_pengadaan'] ?? null,
                'sumber_dana' => $a['sumber_dana'] ?? null,
                'keterangan' => $a['keterangan'] ?? null,
            ]);
            $alsintan->save();
        }

        foreach (DummyData::alsintanDistribusi() as $d) {
            $fotoId = $d['foto_berkas_id'] ?? null;

            AlsintanDistribusi::updateOrCreate(
                ['id_alsintan_distribusi' => $d['id_alsintan_distribusi']],
                [
                    'alsintan_id' => $d['alsintan_id'],
                    'poktan_id' => $d['poktan_id'],
                    'jumlah' => $d['jumlah'],
                    'kondisi' => $d['kondisi'] ?? null,
                    'penanda_terima_id' => $d['penanda_terima_id'] ?? null,
                    'tanggal_serah' => $d['tanggal_serah'] ?? null,
                    'foto_berkas_id' => $fotoId !== null && Berkas::whereKey($fotoId)->exists() ? $fotoId : null,
                    'keterangan' => $d['keterangan'] ?? null,
                ],
            );
        }

        $pivot = DummyData::berkasPemilik()['alsintan_berkas'] ?? [];
        $pivot = array_values(array_filter(
            $pivot,
            fn ($b) => Berkas::whereKey($b['berkas_id'])->exists()
                && Alsintan::withTrashed()->whereKey($b['alsintan_id'])->exists(),
        ));

        DB::table('alsintan_berkas')->delete();

        if ($pivot !== []) {
            DB::table('alsintan_berkas')->insert($pivot);
        }
    }
}
