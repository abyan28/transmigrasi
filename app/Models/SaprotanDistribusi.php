<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Distribusi saprotan ke satu poktan penerima. Sisa benih DIHITUNG per baris
 * ini (`jumlah` - SUM(`penanaman.volume_benih`)), tidak disimpan. SP ikut poktan.
 * TANPA soft delete. FK saprotan CASCADE, poktan RESTRICT.
 */
class SaprotanDistribusi extends Model
{
    protected $table = 'saprotan_distribusi';

    protected $primaryKey = 'id_saprotan_distribusi';

    protected $fillable = ['saprotan_id', 'poktan_id', 'jumlah', 'tanggal_serah', 'keterangan'];

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:3',
            'tanggal_serah' => 'date',
        ];
    }

    public function saprotan(): BelongsTo
    {
        return $this->belongsTo(Saprotan::class, 'saprotan_id', 'id_saprotan');
    }

    public function poktan(): BelongsTo
    {
        return $this->belongsTo(Poktan::class, 'poktan_id', 'id_poktan');
    }
}
