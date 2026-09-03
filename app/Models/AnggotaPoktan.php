<?php

namespace App\Models;

use App\Enums\AsalWakilPoktan;
use App\Enums\StatusKeaktifanAnggota;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Keanggotaan poktan. `transmigran_id` menunjuk KELUARGA yang diwakili;
 * `asal_wakil` memuat 3 nilai (satu tipe dengan `poktan.asal_ketua`), tetapi
 * 'Bukan Transmigran' tidak berlaku di sini (ditegakkan aplikasi). `jabatan` =
 * VARCHAR REF, TANPA 'Ketua'. Anggota berhenti ditandai 'Sudah Keluar', tidak dihapus.
 */
class AnggotaPoktan extends Model
{
    use SoftDeletes;

    protected $table = 'anggota_poktan';

    protected $primaryKey = 'id_anggota_poktan';

    protected $fillable = [
        'poktan_id', 'transmigran_id', 'asal_wakil', 'anggota_keluarga_id',
        'telepon_wakil', 'jabatan', 'tanggal_masuk', 'status', 'tanggal_keluar',
        'alasan_keluar', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'asal_wakil' => AsalWakilPoktan::class,
            'tanggal_masuk' => 'date',
            'status' => StatusKeaktifanAnggota::class,
            'tanggal_keluar' => 'date',
        ];
    }

    public function poktan(): BelongsTo
    {
        return $this->belongsTo(Poktan::class, 'poktan_id', 'id_poktan');
    }

    /**
     * Keluarga yang diwakili anggota ini.
     */
    public function transmigran(): BelongsTo
    {
        return $this->belongsTo(Transmigran::class, 'transmigran_id', 'id_transmigran');
    }

    /**
     * Anggota keluarga yang mewakili; hanya bila `asal_wakil = Anggota Keluarga`.
     */
    public function anggotaKeluarga(): BelongsTo
    {
        return $this->belongsTo(AnggotaKeluarga::class, 'anggota_keluarga_id', 'id_anggota_keluarga');
    }
}
