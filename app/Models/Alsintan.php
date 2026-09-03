<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Alat/mesin pertanian -- induk / pengadaan (pola Putaran 7: satu pengadaan ->
 * banyak distribusi). Baris ini mendeskripsikan BENDAnya; kepemilikan/kondisi
 * ada di `alsintan_distribusi`. `jenis_alsintan`/`sumber_dana` = VARCHAR REF.
 * SUM(distribusi.jumlah) <= `jumlah_total` (ditegakkan aplikasi).
 */
class Alsintan extends Model
{
    use SoftDeletes;

    protected $table = 'alsintan';

    protected $primaryKey = 'id_alsintan';

    protected $fillable = [
        'jenis_alsintan', 'nama_alat', 'jumlah_total', 'tahun_pengadaan',
        'sumber_dana', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_total' => 'integer',
        ];
    }

    public function distribusi(): HasMany
    {
        return $this->hasMany(AlsintanDistribusi::class, 'alsintan_id', 'id_alsintan');
    }

    /**
     * Foto barang + berita acara pengadaan, lewat pivot `alsintan_berkas`.
     */
    public function berkas(): BelongsToMany
    {
        return $this->belongsToMany(
            Berkas::class,
            'alsintan_berkas',
            'alsintan_id',
            'berkas_id',
            'id_alsintan',
            'id_berkas',
        )->withPivot('peran', 'urutan')->withTimestamps();
    }
}
