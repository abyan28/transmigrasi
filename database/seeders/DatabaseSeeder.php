<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder utama.
 *
 * - role bawaan + kewenangan  -> `PermissionRoleSeeder` (Task 3.3) [SELESAI]
 * - akun Admin awal           -> `AdminAwalSeeder` (Task 3.5) [SELESAI]
 * - wilayah + SP lokus        -> Task 4.1
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionRoleSeeder::class);
        $this->call(AdminAwalSeeder::class);
    }
}
