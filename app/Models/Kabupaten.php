<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Referensi wilayah -- kabupaten/kota. Nama disimpan beserta awalan
 * "Kabupaten"/"Kota" (`rules.md` 4a.9b): tidak unik nasional tanpa itu.
 */
class Kabupaten extends Model
{
    protected $table = 'kabupaten';

    protected $primaryKey = 'id_kabupaten';

    protected $fillable = ['provinsi_id', 'nama', 'kode'];

    public function provinsi(): BelongsTo
    {
        return $this->belongsTo(Provinsi::class, 'provinsi_id', 'id_provinsi');
    }

    public function kecamatan(): HasMany
    {
        return $this->hasMany(Kecamatan::class, 'kabupaten_id', 'id_kabupaten');
    }

    public function kawasanTransmigrasi(): HasMany
    {
        return $this->hasMany(KawasanTransmigrasi::class, 'kabupaten_id', 'id_kabupaten');
    }
}
