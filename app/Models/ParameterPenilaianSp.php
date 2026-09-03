<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Parameter penilaian kondisi SP + bobotnya (data, bukan konstanta kode).
 * `referensi_id` menunjuk baris `referensi` (jenis jenis_infrastruktur /
 * jenis_fasilitas) lewat id -- rujukan berbasis id, bukan teks, supaya
 * perbaikan ejaan nilai tidak memutus parameter (`JenisReferensi::dirujukParameter`).
 *
 * `tingkat`/`sumber` ENUM tetap, tanpa PHP Enum khusus: dibaca apa adanya.
 * Tabel referensi: tanpa soft delete (dinonaktifkan lewat `is_dinilai`).
 */
class ParameterPenilaianSp extends Model
{
    protected $table = 'parameter_penilaian_sp';

    protected $primaryKey = 'id_parameter_penilaian_sp';

    protected $fillable = [
        'kode', 'nama', 'tingkat', 'bobot', 'sumber', 'referensi_id', 'is_dinilai', 'urutan',
    ];

    protected function casts(): array
    {
        return [
            'bobot' => 'integer',
            'is_dinilai' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    /**
     * Nilai referensi (jenis infrastruktur / fasilitas) yang dinilai parameter ini.
     */
    public function referensi(): BelongsTo
    {
        return $this->belongsTo(Referensi::class, 'referensi_id', 'id_referensi');
    }
}
