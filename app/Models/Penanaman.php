<?php

namespace App\Models;

use App\Models\Concerns\DisaringLewatInduk;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Kegiatan tanam satu poktan (dahulu `riwayat_tanam`). Berpusat pada poktan,
 * bukan lahan/petani. Sumbu waktu = `periode_tanam` YYYY-MM. `saprotan_distribusi_id`
 * + `volume_benih` WAJIB (termasuk bibit swadaya). Status panen & luas kelompok
 * = turunan.
 */
class Penanaman extends Model
{
    use DisaringLewatInduk;
    use SoftDeletes;

    protected static string $indukCakupan = 'poktan';

    protected $table = 'penanaman';

    protected $primaryKey = 'id_penanaman';

    protected $fillable = [
        'poktan_id', 'komoditas_id', 'saprotan_distribusi_id', 'volume_benih',
        'realisasi_tanam', 'periode_tanam', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'volume_benih' => 'decimal:3',
            'realisasi_tanam' => 'decimal:2',
        ];
    }

    public function poktan(): BelongsTo
    {
        return $this->belongsTo(Poktan::class, 'poktan_id', 'id_poktan');
    }

    public function komoditas(): BelongsTo
    {
        return $this->belongsTo(Komoditas::class, 'komoditas_id', 'id_komoditas');
    }

    /**
     * Jatah distribusi benih yang dipakai penanaman ini.
     */
    public function saprotanDistribusi(): BelongsTo
    {
        return $this->belongsTo(SaprotanDistribusi::class, 'saprotan_distribusi_id', 'id_saprotan_distribusi');
    }

    /**
     * Hasil panen dari penanaman ini (paling banyak satu).
     */
    public function hasilPanen(): HasOne
    {
        return $this->hasOne(HasilPanen::class, 'penanaman_id', 'id_penanaman');
    }

    /**
     * Berita acara tanam, foto hamparan, bukti benih -- lewat pivot `penanaman_berkas`.
     */
    public function berkas(): BelongsToMany
    {
        return $this->belongsToMany(
            Berkas::class,
            'penanaman_berkas',
            'penanaman_id',
            'berkas_id',
            'id_penanaman',
            'id_berkas',
        )->withPivot('peran', 'urutan')->withTimestamps();
    }
}
