<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Hasil panen satu penanaman (paling banyak satu baris per penanaman).
 * `satuan_id` DISALIN dari komoditas saat simpan (snapshot -- tetap benar bila
 * satuan baku komoditas kelak diubah). `produksi` disimpan apa adanya, tanpa
 * konversi. Identitas (aplikasi): `realisasi_panen` + `puso` = `penanaman.realisasi_tanam`;
 * `produksi` = `realisasi_panen` x `produktivitas`.
 *
 * Pengenal publik URL: `uuid`.
 */
class HasilPanen extends Model
{
    use SoftDeletes;

    protected $table = 'hasil_panen';

    protected $primaryKey = 'id_hasil_panen';

    protected $fillable = [
        'uuid', 'penanaman_id', 'satuan_id', 'periode_panen', 'realisasi_panen',
        'puso', 'produktivitas', 'produksi', 'harga_jual', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'realisasi_panen' => 'decimal:2',
            'puso' => 'decimal:2',
            'produktivitas' => 'decimal:3',
            'produksi' => 'decimal:3',
            'harga_jual' => 'decimal:2',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function penanaman(): BelongsTo
    {
        return $this->belongsTo(Penanaman::class, 'penanaman_id', 'id_penanaman');
    }

    /**
     * Satuan baku hasil, disalin dari komoditas saat panen disimpan.
     */
    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_id', 'id_satuan');
    }

    /**
     * Berita acara panen, foto hamparan, bukti timbangan -- lewat pivot `hasil_panen_berkas`.
     */
    public function berkas(): BelongsToMany
    {
        return $this->belongsToMany(
            Berkas::class,
            'hasil_panen_berkas',
            'hasil_panen_id',
            'berkas_id',
            'id_hasil_panen',
            'id_berkas',
        )->withPivot('peran', 'urutan')->withTimestamps();
    }
}
