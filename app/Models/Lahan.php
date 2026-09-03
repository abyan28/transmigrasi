<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Lahan satu keluarga. SATU BARIS = SATU KELUARGA (Putaran 15): tepat satu
 * pekarangan + satu lahan usaha, masing-masing ber-koordinat sendiri
 * (`*_pekarangan`, `*_usaha`). `luas_pekarangan`/`luas_usaha` NULL = BELUM
 * MENERIMA, bukan nol hektare. `luas_kering` + `luas_basah` = `luas_usaha`
 * (`rules.md` 7.5). SHM meliputi seluruh lahan KK -> statusnya di `transmigran`.
 *
 * Pengenal publik URL: `uuid`.
 */
class Lahan extends Model
{
    use SoftDeletes;

    protected $table = 'lahan';

    protected $primaryKey = 'id_lahan';

    protected $fillable = [
        'uuid', 'transmigran_id', 'satuan_permukiman_id', 'poktan_id', 'kode_lahan',
        'luas_pekarangan', 'lintang_pekarangan', 'bujur_pekarangan',
        'luas_usaha', 'luas_kering', 'luas_basah', 'lintang_usaha', 'bujur_usaha',
        'tujuan_pemanfaatan', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'luas_pekarangan' => 'decimal:2',
            'lintang_pekarangan' => 'decimal:7',
            'bujur_pekarangan' => 'decimal:7',
            'luas_usaha' => 'decimal:2',
            'luas_kering' => 'decimal:2',
            'luas_basah' => 'decimal:2',
            'lintang_usaha' => 'decimal:7',
            'bujur_usaha' => 'decimal:7',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Keluarga pemilik lahan (satu KK tepat satu baris -- `transmigran_id` UNIQUE).
     */
    public function transmigran(): BelongsTo
    {
        return $this->belongsTo(Transmigran::class, 'transmigran_id', 'id_transmigran');
    }

    public function satuanPermukiman(): BelongsTo
    {
        return $this->belongsTo(SatuanPermukiman::class, 'satuan_permukiman_id', 'id_satuan_permukiman');
    }

    /**
     * Poktan pengelola lahan bila ada.
     */
    public function poktan(): BelongsTo
    {
        return $this->belongsTo(Poktan::class, 'poktan_id', 'id_poktan');
    }
}
