<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder utama.
 *
 * - role bawaan + kewenangan  -> `PermissionRoleSeeder` (Task 3.3) [SELESAI]
 * - wilayah + SP lokus        -> Task 4.1
 * - akun Admin awal           -> Task 3.5
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionRoleSeeder::class);
    }
}
