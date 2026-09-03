<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak pergantian penghuni rumah; append-only. Tabel riwayat: TANPA soft delete.
 * FK rumah CASCADE, FK transmigran RESTRICT.
 */
class RiwayatPenghunian extends Model
{
    protected $table = 'riwayat_penghunian';

    protected $primaryKey = 'id_riwayat_penghunian';

    protected $fillable = [
        'rumah_id', 'transmigran_id', 'tanggal_masuk', 'tanggal_keluar',
        'alasan_keluar', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_masuk' => 'date',
            'tanggal_keluar' => 'date',
        ];
    }

    public function rumah(): BelongsTo
    {
        return $this->belongsTo(Rumah::class, 'rumah_id', 'id_rumah');
    }

    public function transmigran(): BelongsTo
    {
        return $this->belongsTo(Transmigran::class, 'transmigran_id', 'id_transmigran');
    }
}
