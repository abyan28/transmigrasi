<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seluruh data master yang DIANDAIKAN ADA oleh tampilan (Task 4.5).
 *
 * Dipakai `Tests\TestCase::$seeder` supaya suite Feature punya isi tanpa
 * tiap berkas uji menyebut seedernya satu per satu. Menambah data master
 * baru sepanjang Tahap 4 cukup mendaftarkannya DI SINI; berkas uji tidak
 * perlu disentuh lagi.
 *
 * Berbeda dari `DatabaseSeeder`, kelas ini SENGAJA tidak menanam role, izin,
 * maupun akun: suite Feature memakai pengguna semu bertanda `semuaIzin`
 * (`tests/Pest.php`), sehingga menanamnya hanya memperlambat tanpa dipakai.
 */
class DataMasterSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(WilayahSeeder::class);
        $this->call(SatuanSeeder::class);
        $this->call(ReferensiSeeder::class);
    }
}
