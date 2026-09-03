<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Distribusi alsintan ke satu poktan penerima. `kondisi` diamati per unit
 * (VARCHAR REF). `penanda_terima_id` = anggota poktan penanda tangan BA (bukan
 * pemilik). `satuan_permukiman_id` turunan (ikut poktan) -> tidak disimpan.
 * TANPA soft delete.
 */
class AlsintanDistribusi extends Model
{
    protected $table = 'alsintan_distribusi';

    protected $primaryKey = 'id_alsintan_distribusi';

    protected $fillable = [
        'alsintan_id', 'poktan_id', 'jumlah', 'kondisi', 'penanda_terima_id',
        'tanggal_serah', 'foto_berkas_id', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'tanggal_serah' => 'date',
        ];
    }

    public function alsintan(): BelongsTo
    {
        return $this->belongsTo(Alsintan::class, 'alsintan_id', 'id_alsintan');
    }

    public function poktan(): BelongsTo
    {
        return $this->belongsTo(Poktan::class, 'poktan_id', 'id_poktan');
    }

    /**
     * Anggota poktan yang menandatangani berita acara serah terima.
     */
    public function penandaTerima(): BelongsTo
    {
        return $this->belongsTo(AnggotaPoktan::class, 'penanda_terima_id', 'id_anggota_poktan');
    }

    /**
     * Foto kondisi unit di poktan ini (FK langsung tunggal).
     */
    public function foto(): BelongsTo
    {
        return $this->belongsTo(Berkas::class, 'foto_berkas_id', 'id_berkas');
    }
}
