<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder utama untuk data bootstrap dan referensi otoritatif.
 *
 * Data domain contoh hanya ditanam melalui `DemoSeeder`.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionRoleSeeder::class);
        $this->call(AdminAwalSeeder::class);
        $this->call(WilayahSeeder::class);
        $this->call(SatuanSeeder::class);
        $this->call(DaftarPilihanSeeder::class);
        $this->call(PenilaianKondisiSeeder::class);
    }
}
