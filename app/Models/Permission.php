<?php

namespace App\Models;

use App\Enums\AksiPermission;
use Database\Factories\PermissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Kewenangan baku sistem (`rules.md` 5.0 poin 4). Ditanam seeder pada Task 3.3;
 * Admin TIDAK dapat menambah atau menghapus, sebab tiap kewenangan wajib punya
 * pemeriksa berpasangan di dalam kode. Tanpa soft delete.
 */
class Permission extends Model
{
    /** @use HasFactory<PermissionFactory> */
    use HasFactory;

    protected $table = 'permission';

    protected $primaryKey = 'id_permission';

    protected $fillable = [
        'nama',
        'modul',
        'aksi',
        'label',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'aksi' => AksiPermission::class,
            'urutan' => 'integer',
        ];
    }

    /**
     * Role yang memegang kewenangan ini.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_permission',
            'permission_id',
            'role_id',
            'id_permission',
            'id_role',
        )->withTimestamps();
    }
}
