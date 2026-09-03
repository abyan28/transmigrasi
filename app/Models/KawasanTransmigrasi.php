<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Kawasan transmigrasi (cabang PROGRAM). Subjek utama sistem; dapat memotong
 * batas kecamatan. HPL kawasan melekat di sini (`rules.md` 7.4a), lewat pivot
 * `kawasan_transmigrasi_berkas` (batch berikutnya).
 *
 * Pengenal publik URL: `slug` -- bukan data pribadi, boleh terbaca.
 */
class KawasanTransmigrasi extends Model
{
    use SoftDeletes;

    protected $table = 'kawasan_transmigrasi';

    protected $primaryKey = 'id_kawasan_transmigrasi';

    protected $fillable = [
        'kabupaten_id', 'nama', 'slug', 'kode_kawasan',
        'tahun_penetapan', 'nomor_sk', 'luas_total', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'luas_total' => 'decimal:2',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function kabupaten(): BelongsTo
    {
        return $this->belongsTo(Kabupaten::class, 'kabupaten_id', 'id_kabupaten');
    }

    public function satuanPermukiman(): HasMany
    {
        return $this->hasMany(SatuanPermukiman::class, 'kawasan_id', 'id_kawasan_transmigrasi');
    }

    /**
     * Dokumen kawasan (HPL, SK penetapan, peta) lewat pivot
     * `kawasan_transmigrasi_berkas`. HPL kawasan melekat di sini (`rules.md` 7.4a).
     */
    public function berkas(): BelongsToMany
    {
        return $this->belongsToMany(
            Berkas::class,
            'kawasan_transmigrasi_berkas',
            'kawasan_transmigrasi_id',
            'berkas_id',
            'id_kawasan_transmigrasi',
            'id_berkas',
        )->withPivot('peran', 'urutan')->withTimestamps();
    }
}
