<?php

namespace App\Models;

use App\Enums\JenisFasilitas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Bangunan/fasilitas tetap milik SP. `jenis_fasilitas` tetap ENUM (dipakai
 * penilaian kondisi SP) -> di-cast. `satuan_permukiman_id` = lokasi/pangkal;
 * SP yang DILAYANI ada di pivot `fasilitas_sp_cakupan` (WAJIB memuat SP pangkal).
 */
class FasilitasSp extends Model
{
    use SoftDeletes;

    protected $table = 'fasilitas_sp';

    protected $primaryKey = 'id_fasilitas_sp';

    protected $fillable = [
        'satuan_permukiman_id', 'jenis_fasilitas', 'nama_fasilitas', 'jumlah',
        'tahun_perolehan', 'sumber_dana', 'status_penyerahan', 'kondisi',
        'rincian_kondisi', 'lintang', 'bujur', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'jenis_fasilitas' => JenisFasilitas::class,
            'jumlah' => 'integer',
            'rincian_kondisi' => 'array',
            'lintang' => 'decimal:7',
            'bujur' => 'decimal:7',
        ];
    }

    /**
     * SP tempat fasilitas ini berdiri.
     */
    public function satuanPermukiman(): BelongsTo
    {
        return $this->belongsTo(SatuanPermukiman::class, 'satuan_permukiman_id', 'id_satuan_permukiman');
    }

    /**
     * Seluruh SP yang dilayani fasilitas ini (termasuk SP pangkal).
     */
    public function cakupan(): BelongsToMany
    {
        return $this->belongsToMany(
            SatuanPermukiman::class,
            'fasilitas_sp_cakupan',
            'fasilitas_sp_id',
            'satuan_permukiman_id',
            'id_fasilitas_sp',
            'id_satuan_permukiman',
        )->withTimestamps();
    }
}
