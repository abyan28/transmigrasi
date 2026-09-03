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
 * - `punyaIzin()` / `punyaAksi()` = pemeriksa kewenangan RBAC (Task 3.3).
 *   Cakupan data (global scope) menyusul Task 3.4.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'user';

    protected $primaryKey = 'id_user';

    /**
     * Menandai pengguna semu bertanda SELURUH kewenangan, dipakai HANYA oleh
     * `beforeEach` suite Feature (`tests/Pest.php`). Bukan role nyata:
     * instance ini tak dipersist dan tak punya `role`. `punyaIzin()` memeriksa
     * flag ini lebih dulu.
     *
     * Bypass `MasukOtomatisLokal` dahulu juga memakainya; DICABUT 2026-09-03
     * sebab pengguna tak dipersist berarti `id_user` null, dan itu melanggar
     * `audit_log.record_id` NOT NULL begitu keluar dari sistem.
     */
    public bool $semuaIzin = false;

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

    /**
     * Apakah pengguna memegang satu kewenangan, mis. `transmigran.ubah`.
     *
     * Pemeriksaan `lihat` sebagai prasyarat aksi lain (`data-dictionary.md`
     * 13.3 poin 4) ditegakkan middleware `izin`, bukan di sini -- metode ini
     * menjawab persis kewenangan yang ditanya.
     *
     * Role non-aktif menghilangkan seluruh kewenangannya (Admin menonaktifkan
     * role, penggunanya kehilangan akses sampai dipindahkan).
     */
    public function punyaIzin(string $izin): bool
    {
        if ($this->semuaIzin) {
            return true;
        }

        $role = $this->role;

        if ($role === null || ! $role->is_aktif) {
            return false;
        }

        return $role->permissions->contains('nama', $izin);
    }

    /**
     * Bentuk `modul` + `aksi` terpisah dari `punyaIzin()`.
     */
    public function punyaAksi(string $modul, string $aksi): bool
    {
        return $this->punyaIzin($modul.'.'.$aksi);
    }
}
