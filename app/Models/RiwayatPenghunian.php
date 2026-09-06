<?php

namespace App\Models;

use App\Models\Concerns\DisaringLewatInduk;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak pergantian penghuni rumah; append-only. Tabel riwayat: TANPA soft delete.
 * FK rumah CASCADE, FK transmigran RESTRICT.
 */
class RiwayatPenghunian extends Model
{
    use DisaringLewatInduk;

    protected static string $indukCakupan = 'rumah';

    protected $table = 'riwayat_penghunian';

    protected $primaryKey = 'id_riwayat_penghunian';

    protected $fillable = [
        'rumah_id', 'transmigran_id', 'tahun_mulai_menghuni', 'tahun_selesai_menghuni',
        'alasan_keluar', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tahun_mulai_menghuni' => 'integer',
            'tahun_selesai_menghuni' => 'integer',
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
