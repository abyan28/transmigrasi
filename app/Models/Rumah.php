<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Rumah SP. Relasi rumah<->KK satu-ke-satu dua arah: FK di sini,
 * `transmigran_id` UNIQUE nullable (NULL = rumah kosong). `kondisi`/
 * `status_hunian` = TEKS nilai referensi, bukan enum. FK SP RESTRICT,
 * FK transmigran SET NULL.
 *
 * Pengenal publik URL: `uuid` -- lokasi rumah warga tak diekspos lewat id urut.
 */
class Rumah extends Model
{
    use SoftDeletes;

    protected $table = 'rumah';

    protected $primaryKey = 'id_rumah';

    protected $fillable = [
        'uuid', 'satuan_permukiman_id', 'transmigran_id', 'no_rumah', 'kondisi',
        'status_hunian', 'alasan_tidak_dihuni', 'tahun_pembangunan', 'luas_bangunan',
        'lintang', 'bujur', 'catatan_hunian',
    ];

    protected function casts(): array
    {
        return [
            'luas_bangunan' => 'decimal:2',
            'lintang' => 'decimal:7',
            'bujur' => 'decimal:7',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function satuanPermukiman(): BelongsTo
    {
        return $this->belongsTo(SatuanPermukiman::class, 'satuan_permukiman_id', 'id_satuan_permukiman');
    }

    /**
     * KK yang menghuni rumah ini; NULL = rumah kosong.
     */
    public function penghuni(): BelongsTo
    {
        return $this->belongsTo(Transmigran::class, 'transmigran_id', 'id_transmigran');
    }

    public function riwayatPenghunian(): HasMany
    {
        return $this->hasMany(RiwayatPenghunian::class, 'rumah_id', 'id_rumah');
    }

    /**
     * Foto beberapa sisi + dokumen pendukung, lewat pivot `rumah_berkas`.
     */
    public function berkas(): BelongsToMany
    {
        return $this->belongsToMany(
            Berkas::class,
            'rumah_berkas',
            'rumah_id',
            'berkas_id',
            'id_rumah',
            'id_berkas',
        )->withPivot('peran', 'urutan')->withTimestamps();
    }
}
