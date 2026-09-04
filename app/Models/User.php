<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * Akun petugas. Menggantikan tabel `users` bawaan Laravel.
 *
 * - PK `id_user`, tabel `user` (bentuk tunggal, `rules.md` 4.0).
 * - Seluruh pemegang akun adalah petugas; warga tidak punya akun (`rules.md` §5).
 * - Login memakai email ATAU username pada satu isian (Task 3.2).
 * - `punyaIzin()` / `punyaAksi()` = pemeriksa kewenangan RBAC (Task 3.3).
 *   Cakupan data (global scope) menyusul Task 3.4.
 * - Username DIBUAT petugas sendiri saat masuk pertama (`rules.md` 14b poin 5).
 *   Sampai itu akun memakai username SEMENTARA berawalan `petugas.`; lihat
 *   `buatUsernameSementara()` / `perluBuatUsername()` (Task 3.14).
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Awalan username yang dibangkitkan sistem sebagai pengisi sementara
     * kolom `user.username` (NOT NULL) sebelum petugas membuat miliknya
     * sendiri saat masuk pertama.
     */
    public const AWALAN_USERNAME_SEMENTARA = 'petugas.';

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

    /**
     * Username sementara berformat sah (`rules.md` 14b poin 5a) untuk mengisi
     * kolom NOT NULL saat Admin membuat akun. Diperiksa keunikannya walau
     * tabrakan praktis mustahil. Dipakai `PengaturanPenggunaController` dan
     * `AdminAwalSeeder`; digantikan petugas lewat form ganti-kata-sandi.
     */
    public static function buatUsernameSementara(): string
    {
        do {
            $kandidat = self::AWALAN_USERNAME_SEMENTARA.Str::lower(Str::random(8));
        } while (self::withTrashed()->where('username', $kandidat)->exists());

        return $kandidat;
    }

    /**
     * Apakah pengguna belum pernah membuat usernamenya sendiri -- masih
     * memakai pengisi sementara (atau, pada instance uji yang tak dipersist,
     * belum berisi). Menjadi pemicu kolom username pada halaman ganti kata
     * sandi wajib (Task 3.14).
     */
    public function perluBuatUsername(): bool
    {
        return $this->username === null
            || str_starts_with((string) $this->username, self::AWALAN_USERNAME_SEMENTARA);
    }
}
