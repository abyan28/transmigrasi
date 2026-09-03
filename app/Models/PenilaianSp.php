<?php

namespace App\Models;

use App\Enums\StatusKondisiSp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat penilaian kondisi SP. Satu SP banyak baris; tidak pernah dihitung
 * ulang diam-diam. `rincian` = salinan bobot/kondisi/nilai yang berlaku saat
 * penilaian dibuat. Tabel riwayat: TANPA soft delete.
 *
 * `status` = enum tetap 3 nilai, sama persis `App\Enums\StatusKondisiSp`
 * (bukan admin-managed) -> aman di-cast.
 */
class PenilaianSp extends Model
{
    protected $table = 'penilaian_sp';

    protected $primaryKey = 'id_penilaian_sp';

    protected $fillable = [
        'satuan_permukiman_id', 'tanggal_penilaian', 'skor', 'status',
        'ada_primer_nol', 'rincian', 'user_id', 'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_penilaian' => 'date',
            'skor' => 'decimal:2',
            'status' => StatusKondisiSp::class,
            'ada_primer_nol' => 'boolean',
            'rincian' => 'array',
        ];
    }

    public function satuanPermukiman(): BelongsTo
    {
        return $this->belongsTo(SatuanPermukiman::class, 'satuan_permukiman_id', 'id_satuan_permukiman');
    }

    /**
     * Petugas yang membuat penilaian; NULL bila dihitung sistem.
     */
    public function penilai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id_user');
    }
}
