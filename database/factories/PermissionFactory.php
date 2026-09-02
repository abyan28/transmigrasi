<?php

namespace Database\Factories;

use App\Enums\AksiPermission;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    public function definition(): array
    {
        $modul = fake()->unique()->word();
        $aksi = fake()->randomElement(AksiPermission::cases())->value;

        return [
            'nama' => "{$modul}.{$aksi}",
            'modul' => $modul,
            'aksi' => $aksi,
            'label' => ucfirst($aksi).' '.$modul,
            'urutan' => fake()->numberBetween(0, 100),
        ];
    }
}
