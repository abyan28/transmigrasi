<?php

namespace Database\Factories;

use App\Enums\CakupanData;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama' => ucwords(fake()->unique()->words(2, true)),
            'deskripsi' => fake()->sentence(),
            'cakupan_data' => CakupanData::Semua->value,
            'is_bawaan' => false,
            'is_terkunci' => false,
            'is_aktif' => true,
        ];
    }

    public function bawaan(): static
    {
        return $this->state(['is_bawaan' => true]);
    }

    public function terkunci(): static
    {
        return $this->state(['is_bawaan' => true, 'is_terkunci' => true]);
    }
}
