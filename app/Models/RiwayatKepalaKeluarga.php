<?php

namespace App\Models;

use App\Enums\AlasanPergantianKK;
use App\Enums\HubunganAnggotaKeluarga;
use App\Models\Concerns\DisaringLewatInduk;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak suksesi kepala keluarga; append-only; kedua sisi identitas
 * didenormalisasi (tanpa FK ke `anggota_keluarga` yang akan menggantung).
 * TANPA soft delete -- tidak dapat dihapus siapa pun. FK transmigran RESTRICT.
 */
class RiwayatKepalaKeluarga extends Model
{
    use DisaringLewatInduk;

    protected static string $indukCakupan = 'transmigran';

    protected $table = 'riwayat_kepala_keluarga';

    protected $primaryKey = 'id_riwayat_kepala_keluarga';

    protected $fillable = [
        'transmigran_id', 'nik_lama', 'nama_lama', 'nik_baru', 'nama_baru',
        'no_kk_lama', 'no_kk_baru', 'tanggal_pergantian', 'alasan',
        'hubungan_pengganti', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pergantian' => 'date',
            'alasan' => AlasanPergantianKK::class,
            'hubungan_pengganti' => HubunganAnggotaKeluarga::class,
        ];
    }

    /**
     * Rumah tangga tempat suksesi terjadi; tidak pernah berubah.
     */
    public function transmigran(): BelongsTo
    {
        return $this->belongsTo(Transmigran::class, 'transmigran_id', 'id_transmigran');
    }
}
