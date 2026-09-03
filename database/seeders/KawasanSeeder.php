<?php

namespace Database\Seeders;

use App\Models\KawasanTransmigrasi;
use App\Support\DummyData;
use Illuminate\Database\Seeder;

/**
 * Kawasan transmigrasi lokus (Task 4.1b).
 *
 * Dibaca dari `DummyData::kawasan()` supaya id dan isinya sama persis dengan
 * yang masih dipakai modul lain selama Tahap 4 berjalan.
 *
 * `kabupaten_id` ADALAH kebenarannya; kolom teks `kabupaten`/`provinsi` pada
 * data contoh hanya label tampilan dan TIDAK ikut ditanam -- keduanya bukan
 * kolom pada skema.
 *
 * `slug` sengaja tidak disebut: `Sluggable` (Task 3.9) menurunkannya dari
 * nama pada saat penyimpanan.
 *
 * Idempoten: `updateOrCreate` pada kunci utama.
 */
class KawasanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DummyData::kawasan() as $k) {
            KawasanTransmigrasi::updateOrCreate(
                ['id_kawasan_transmigrasi' => $k['id_kawasan_transmigrasi']],
                [
                    'kabupaten_id' => $k['kabupaten_id'],
                    'nama' => $k['nama'],
                    'kode_kawasan' => $k['kode_kawasan'],
                    'tahun_penetapan' => $k['tahun_penetapan'],
                    'nomor_sk' => $k['nomor_sk'],
                    'luas_total' => $k['luas_total'],
                    'keterangan' => $k['keterangan'],
                ],
            );
        }
    }
}
