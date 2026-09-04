<?php

namespace Database\Seeders;

use App\Models\ParameterPenilaianSp;
use App\Models\StatusKondisiSp;
use App\Support\DummyData;
use Illuminate\Database\Seeder;

/**
 * Parameter bobot + ambang status penilaian kondisi SP (Task 4.8).
 *
 * Keduanya adalah keputusan KEBIJAKAN yang wajib divalidasi dinas
 * (`rules.md` 10c poin 13), bukan angka teknis -- karena itu dikelola lewat
 * antarmuka, bukan ditanam mati di dalam kode.
 *
 * `daftar_pilihan_id` menunjuk baris `daftar_pilihan` (jenis infrastruktur/fasilitas),
 * sehingga `DaftarPilihanSeeder` WAJIB berjalan lebih dulu.
 */
class PenilaianKondisiSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DummyData::parameterPenilaian() as $urutan => $p) {
            ParameterPenilaianSp::updateOrCreate(
                ['kode' => $p['kode']],
                [
                    'nama' => $p['nama'],
                    'tingkat' => $p['tingkat'] ?? null,
                    'bobot' => $p['bobot'],
                    'sumber' => $p['sumber'],
                    'daftar_pilihan_id' => $p['daftar_pilihan_id'] ?? null,
                    'is_dinilai' => $p['is_dinilai'],
                    'urutan' => $p['urutan'] ?? $urutan + 1,
                ],
            );
        }

        foreach (DummyData::statusKondisiSp() as $s) {
            StatusKondisiSp::updateOrCreate(
                ['kode' => $s['kode']],
                [
                    'nama' => $s['nama'],
                    'keterangan' => $s['keterangan'],
                    'ambang_bawah' => $s['ambang_bawah'],
                    'warna' => $s['warna'],
                    'urutan' => $s['urutan'],
                ],
            );
        }
    }
}
