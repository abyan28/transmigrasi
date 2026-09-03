<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Akun petugas. Menggantikan tabel `users` bawaan Laravel.
 *
 * - PK `id_user`, tabel `user` (bentuk tunggal, `rules.md` 4.0).
 * - Seluruh pemegang akun adalah petugas; warga tidak punya akun (`rules.md` §5).
 * - Login memakai email ATAU username pada satu isian (Task 3.2).
 * - Helper pemeriksa izin (`punyaIzin()`, cakupan data) ditambahkan Task 3.3
 *   bersama RBAC; model ini baru menyediakan strukturnya.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'user';

    protected $primaryKey = 'id_user';

    protected $fillable = [
        'role_id',
        'nama',
        'username',
        'email',
        'password',
        'telepon',
        'jabatan',
        'is_aktif',
        'password_harus_diganti',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'password_harus_diganti' => 'boolean',
            'is_aktif' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Role yang menentukan kewenangan dan cakupan data pengguna.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id_role');
    }

    /**
     * SP yang ditugaskan ke pengguna ini. Hanya bermakna bagi role bercakupan
     * `Per SP`; akun `Per SP` tanpa penugasan melihat NOL baris (`rules.md`
     * 5.0b-1 poin 10), ditegakkan global scope pada Task 3.4.
     */
    public function satuanPermukiman(): BelongsToMany
    {
        return $this->belongsToMany(
            SatuanPermukiman::class,
            'user_satuan_permukiman',
            'user_id',
            'satuan_permukiman_id',
            'id_user',
            'id_satuan_permukiman',
        )->withTimestamps();
    }

    /**
     * Foto profil lewat pivot `user_berkas` (UNIQUE `user_id` -- paling banyak
     * satu). Pivot dipakai, bukan FK langsung, untuk memutus siklus
     * `berkas.user_id` -> `user`.
     */
    public function fotoProfil(): BelongsToMany
    {
        return $this->belongsToMany(
            Berkas::class,
            'user_berkas',
            'user_id',
            'berkas_id',
            'id_user',
            'id_berkas',
        )->withPivot('peran', 'urutan')->withTimestamps();
    }
}
