<?php

namespace App\Models;

use App\Models\Scopes\CakupanDataSp;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Aset infrastruktur SP. Pelaporan kerusakan mengalir ke fitur Pengaduan.
 * `satuan_permukiman_id` = lokasi/pangkal (wajib); SP yang DILAYANI ada di
 * pivot `infrastruktur_sp` (WAJIB memuat SP pangkal). `jenis`/`sumber_dana`/
 * `kondisi` = TEKS nilai daftar pilihan. `kondisi` = sumber grafik status.
 */
#[ScopedBy([CakupanDataSp::class])]
class Infrastruktur extends Model
{
    use SoftDeletes;

    protected $table = 'infrastruktur';

    protected $primaryKey = 'id_infrastruktur';

    protected $fillable = [
        'satuan_permukiman_id', 'poktan_id', 'nama', 'jenis', 'tahun_perolehan',
        'sumber_dana', 'kondisi', 'kapasitas', 'lintang', 'bujur', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'lintang' => 'decimal:7',
            'bujur' => 'decimal:7',
        ];
    }

    /**
     * SP tempat infrastruktur ini berpangkal.
     */
    public function satuanPermukiman(): BelongsTo
    {
        return $this->belongsTo(SatuanPermukiman::class, 'satuan_permukiman_id', 'id_satuan_permukiman');
    }

    /**
     * Poktan pengelola bila ada.
     */
    public function poktan(): BelongsTo
    {
        return $this->belongsTo(Poktan::class, 'poktan_id', 'id_poktan');
    }

    /**
     * Seluruh SP yang dilayani infrastruktur ini (termasuk SP pangkal).
     */
    public function cakupan(): BelongsToMany
    {
        return $this->belongsToMany(
            SatuanPermukiman::class,
            'infrastruktur_sp',
            'infrastruktur_id',
            'satuan_permukiman_id',
            'id_infrastruktur',
            'id_satuan_permukiman',
        )->withTimestamps();
    }

    /**
     * Foto titik kerusakan, lewat pivot `infrastruktur_berkas`.
     */
    public function berkas(): BelongsToMany
    {
        return $this->belongsToMany(
            Berkas::class,
            'infrastruktur_berkas',
            'infrastruktur_id',
            'berkas_id',
            'id_infrastruktur',
            'id_berkas',
        )->withPivot('peran', 'urutan')->withTimestamps();
    }
}
