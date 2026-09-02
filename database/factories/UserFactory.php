<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 *
 * Disesuaikan ke tabel `user` (bukan `users` bawaan): `nama` bukan `name`,
 * ada `username` & `role_id`, tanpa `email_verified_at` (akun dibuat Admin,
 * bukan pendaftaran mandiri).
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'role_id' => Role::factory(),
            'nama' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'password_harus_diganti' => false,
            'telepon' => fake()->numerify('08##########'),
            'jabatan' => fake()->jobTitle(),
            'is_aktif' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function nonaktif(): static
    {
        return $this->state(['is_aktif' => false]);
    }

    public function harusGantiSandi(): static
    {
        return $this->state(['password_harus_diganti' => true]);
    }
}
