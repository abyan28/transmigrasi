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
        $this->call(DaftarPilihanSeeder::class);
        $this->call(KawasanSeeder::class);
        $this->call(SpSeeder::class);
        $this->call(AsetSpSeeder::class);
        $this->call(InfrastrukturSeeder::class);
        $this->call(PenilaianKondisiSeeder::class);
        // Sebelum BerkasSeeder: pivot `transmigran_berkas`/`rumah_berkas` menaut
        // ke induknya. RumahSeeder setelah TransmigranSeeder (FK transmigran_id).
        $this->call(TransmigranSeeder::class);
        $this->call(RumahSeeder::class);
        $this->call(LahanSeeder::class);
        $this->call(BerkasSeeder::class);
        $this->call(PoktanSeeder::class);
        $this->call(AlsintanSeeder::class);
        $this->call(KomoditasSeeder::class);
        $this->call(SaprotanSeeder::class);
        $this->call(PenanamanSeeder::class);
        $this->call(HasilPanenSeeder::class);
    }
}
