<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder utama.
 *
 * - role bawaan + kewenangan  -> `PermissionRoleSeeder` (Task 3.3) [SELESAI]
 * - akun Admin awal           -> `AdminAwalSeeder` (Task 3.5) [SELESAI]
 * - wilayah bertingkat        -> `WilayahSeeder` (Task 4.1) [SELESAI]
 * - satuan berat + konversi    -> SatuanSeeder (Task 4.5) [SELESAI]
 * - daftar pilihan (referensi) -> `ReferensiSeeder` (Task 4.7) [SELESAI]
 * - SP lokus + turunannya     -> Task 4.2
 *
 * Urutan mengikuti dependensi: wilayah ditanam sebelum SP, sebab
 * `satuan_permukiman.desa_id` menunjuk ke sana.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionRoleSeeder::class);
        $this->call(AdminAwalSeeder::class);
        $this->call(WilayahSeeder::class);
        $this->call(SatuanSeeder::class);
        $this->call(ReferensiSeeder::class);
        $this->call(KawasanSeeder::class);
        $this->call(SpSeeder::class);
        $this->call(AsetSpSeeder::class);
        $this->call(InfrastrukturSeeder::class);
        $this->call(PenilaianKondisiSeeder::class);
        $this->call(TransmigranSeeder::class);
        $this->call(RumahSeeder::class);
        $this->call(LahanSeeder::class);
        $this->call(BerkasSeeder::class);
    }
}
