<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satuan berat + faktor konversi ke ton (Ton/Kuintal/Kilogram). Referensi
 * murni: tanpa soft delete, dilindungi RESTRICT dari sisi `komoditas`.
 */
class Satuan extends Model
{
    protected $table = 'satuan';

    protected $primaryKey = 'id_satuan';

    protected $fillable = ['nama', 'simbol', 'faktor_ke_ton'];

    protected function casts(): array
    {
        return [
            'faktor_ke_ton' => 'decimal:6',
        ];
    }

    public function komoditas(): HasMany
    {
        return $this->hasMany(Komoditas::class, 'satuan_id', 'id_satuan');
    }
}
