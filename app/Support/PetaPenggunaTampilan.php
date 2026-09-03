<?php

namespace App\Support;

use App\Enums\CakupanData;
use App\Models\User;

/**
 * Menormalkan `App\Models\User` menjadi larik bentuk `DummyData::penggunaSaatIni()`
 * (Task 3.13).
 *
 * Dipakai oleh rute `profil` dan composer `components.header.user-dropdown` --
 * dua pemakai terakhir `DummyData::penggunaSaatIni()`. View tetap membaca larik
 * dengan akses indeks (`$pengguna['role']['nama']`), dan `cakupan_data`
 * tetap STRING supaya `CakupanData::dari()` di view sah.
 *
 * Null-safe: pengguna semu suite Feature (`tests/Pest.php`, `new User(...)`
 * tanpa role) merender lewat cabang default.
 */
class PetaPenggunaTampilan
{
    /**
     * @return array<string, mixed>
     */
    public static function untuk(?User $pengguna): array
    {
        if ($pengguna === null) {
            return self::kosong();
        }

        if ($pengguna->exists) {
            $pengguna->loadMissing('role', 'satuanPermukiman');
        }

        $role = $pengguna->exists ? $pengguna->role : null;

        return [
            'id_user' => $pengguna->id_user,
            'nama' => $pengguna->nama ?? 'Pengguna',
            'username' => $pengguna->username,
            'email' => $pengguna->email,
            'telepon' => $pengguna->telepon,
            'jabatan' => $pengguna->jabatan,
            'foto' => null,
            'is_aktif' => (bool) ($pengguna->is_aktif ?? true),
            'password_harus_diganti' => (bool) ($pengguna->password_harus_diganti ?? false),
            'last_login_at' => $pengguna->last_login_at,
            'created_at' => $pengguna->created_at,
            'role' => [
                'id_role' => $role?->id_role,
                'nama' => $role?->nama ?? '-',
                'deskripsi' => $role?->deskripsi,
                'cakupan_data' => ($role?->cakupan_data ?? CakupanData::Semua)->value,
                'is_bawaan' => (bool) ($role?->is_bawaan ?? false),
                'is_terkunci' => (bool) ($role?->is_terkunci ?? false),
            ],
            'satuan_permukiman' => $pengguna->relationLoaded('satuanPermukiman')
                ? $pengguna->satuanPermukiman->pluck('nama')->all()
                : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function kosong(): array
    {
        return [
            'id_user' => null,
            'nama' => 'Pengguna',
            'username' => null,
            'email' => null,
            'telepon' => null,
            'jabatan' => null,
            'foto' => null,
            'is_aktif' => true,
            'password_harus_diganti' => false,
            'last_login_at' => null,
            'created_at' => null,
            'role' => [
                'id_role' => null,
                'nama' => '-',
                'deskripsi' => null,
                'cakupan_data' => CakupanData::Semua->value,
                'is_bawaan' => false,
                'is_terkunci' => false,
            ],
            'satuan_permukiman' => [],
        ];
    }
}
