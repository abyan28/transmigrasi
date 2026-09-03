<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder utama.
 *
 * - role bawaan + kewenangan  -> `PermissionRoleSeeder` (Task 3.3) [SELESAI]
 * - akun Admin awal           -> `AdminAwalSeeder` (Task 3.5) [SELESAI]
 * - wilayah bertingkat        -> `WilayahSeeder` (Task 4.1) [SELESAI]
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
    }
}
