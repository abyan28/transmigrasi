<?php

namespace App\Models;

use App\Enums\CakupanData;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Role dinamis (`rules.md` 5.0). Dibuat & diatur Admin lewat antarmuka;
 * kewenangannya dipasangkan lewat pivot `role_permission`. Cakupan data
 * (`Semua`/`Per SP`/`Per Bidang`) melekat pada role, bukan pada kewenangan.
 */
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'role';

    protected $primaryKey = 'id_role';

    protected $fillable = [
        'nama',
        'deskripsi',
        'cakupan_data',
        'is_aktif',
    ];

    protected function casts(): array
    {
        return [
            'cakupan_data' => CakupanData::class,
            'is_bawaan' => 'boolean',
            'is_terkunci' => 'boolean',
            'is_aktif' => 'boolean',
        ];
    }

    /**
     * Kewenangan yang dipegang role ini.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permission',
            'role_id',
            'permission_id',
            'id_role',
            'id_permission',
        )->withTimestamps();
    }

    /**
     * Pengguna yang memegang role ini.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id', 'id_role');
    }
}
