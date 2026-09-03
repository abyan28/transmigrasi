<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Barang bergerak milik SP. `kondisi` = penilaian umum petugas, BUKAN turunan
 * `rincian_kondisi`. `rincian_kondisi` JSON: peta kondisi->jumlah unit,
 * SUM = `jumlah`. Kolom `jenis_inventaris`/`sumber_dana`/`status_penyerahan`/
 * `kondisi` menyimpan TEKS nilai referensi, bukan enum.
 */
class InventarisSp extends Model
{
    use SoftDeletes;

    protected $table = 'inventaris_sp';

    protected $primaryKey = 'id_inventaris_sp';

    protected $fillable = [
        'satuan_permukiman_id', 'jenis_inventaris', 'nama_barang', 'jumlah',
        'satuan_barang', 'tahun_perolehan', 'sumber_dana', 'status_penyerahan',
        'kondisi', 'rincian_kondisi', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'rincian_kondisi' => 'array',
        ];
    }

    public function satuanPermukiman(): BelongsTo
    {
        return $this->belongsTo(SatuanPermukiman::class, 'satuan_permukiman_id', 'id_satuan_permukiman');
    }

    /**
     * Foto kondisi per unit + berita acara, lewat pivot `inventaris_sp_berkas`.
     */
    public function berkas(): BelongsToMany
    {
        return $this->belongsToMany(
            Berkas::class,
            'inventaris_sp_berkas',
            'inventaris_sp_id',
            'berkas_id',
            'id_inventaris_sp',
            'id_berkas',
        )->withPivot('peran', 'urutan')->withTimestamps();
    }
}
