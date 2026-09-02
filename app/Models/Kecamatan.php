<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Referensi wilayah -- kecamatan (cabang administratif). Terbatas wilayah lokus
 * sampai pengambilan bertahap lewat endpoint (`rules.md` 4a.9a).
 */
class Kecamatan extends Model
{
    protected $table = 'kecamatan';

    protected $primaryKey = 'id_kecamatan';

    protected $fillable = ['kabupaten_id', 'nama', 'kode'];

    public function kabupaten(): BelongsTo
    {
        return $this->belongsTo(Kabupaten::class, 'kabupaten_id', 'id_kabupaten');
    }

    public function desa(): HasMany
    {
        return $this->hasMany(Desa::class, 'kecamatan_id', 'id_kecamatan');
    }
}
