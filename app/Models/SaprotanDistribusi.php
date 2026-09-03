<?php

namespace App\Models;

use App\Models\Concerns\DisaringLewatInduk;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Distribusi saprotan ke satu poktan penerima. Sisa benih DIHITUNG per baris
 * ini (`jumlah` - SUM(`penanaman.volume_benih`)), tidak disimpan. SP ikut poktan.
 * TANPA soft delete. FK saprotan CASCADE, poktan RESTRICT.
 */
class SaprotanDistribusi extends Model
{
    use DisaringLewatInduk;

    protected static string $indukCakupan = 'poktan';

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

    /**
     * Penanaman yang memakai jatah benih dari distribusi ini (untuk hitung sisa).
     */
    public function penanaman(): HasMany
    {
        return $this->hasMany(Penanaman::class, 'saprotan_distribusi_id', 'id_saprotan_distribusi');
    }
}
