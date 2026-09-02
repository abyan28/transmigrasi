<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Referensi wilayah -- provinsi (cabang administratif). Tanpa soft delete,
 * dilindungi RESTRICT. Dimuat dari data referensi nasional (`rules.md` 4a.9).
 */
class Provinsi extends Model
{
    protected $table = 'provinsi';

    protected $primaryKey = 'id_provinsi';

    protected $fillable = ['nama', 'kode'];

    public function kabupaten(): HasMany
    {
        return $this->hasMany(Kabupaten::class, 'provinsi_id', 'id_provinsi');
    }
}
