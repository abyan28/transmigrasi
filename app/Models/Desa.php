<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Referensi wilayah -- desa (cabang administratif). Kabupaten desa dibaca lewat
 * kecamatan (`rules.md` 4a.11a) -- tidak ada relasi langsung ke kawasan.
 */
class Desa extends Model
{
    protected $table = 'desa';

    protected $primaryKey = 'id_desa';

    protected $fillable = ['kecamatan_id', 'nama', 'kode'];

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id', 'id_kecamatan');
    }

    public function satuanPermukiman(): HasMany
    {
        return $this->hasMany(SatuanPermukiman::class, 'desa_id', 'id_desa');
    }
}
