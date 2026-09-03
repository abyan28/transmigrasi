<?php

namespace App\Models;

use App\Enums\Agama;
use App\Enums\HubunganAnggotaKeluarga;
use App\Enums\JenisKelamin;
use App\Enums\KegiatanAnggota;
use App\Enums\PendidikanTerakhir;
use App\Enums\StatusAnggotaKeluarga;
use App\Models\Concerns\DisaringLewatInduk;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Satu baris per anggota keluarga SELAIN kepala keluarga. Mutasi (meninggal/
 * pindah) DITANDAI lewat `status` + `tanggal_peristiwa`, tidak dihapus --
 * kecuali suksesi (pengganti "naik" ke `transmigran`, barisnya lalu dihapus).
 * `usia` diturunkan dari `tanggal_lahir`.
 */
class AnggotaKeluarga extends Model
{
    use DisaringLewatInduk;
    use SoftDeletes;

    protected static string $indukCakupan = 'transmigran';

    protected $table = 'anggota_keluarga';

    protected $primaryKey = 'id_anggota_keluarga';

    protected $fillable = [
        'transmigran_id', 'hubungan', 'nama_lengkap', 'nik', 'jenis_kelamin',
        'tempat_lahir', 'tanggal_lahir', 'agama', 'kegiatan', 'pendidikan_terakhir',
        'pekerjaan', 'pendapatan_per_bulan', 'telepon', 'keterangan',
        'status', 'tanggal_peristiwa', 'keterangan_peristiwa',
    ];

    protected function casts(): array
    {
        return [
            'hubungan' => HubunganAnggotaKeluarga::class,
            'jenis_kelamin' => JenisKelamin::class,
            'tanggal_lahir' => 'date',
            'agama' => Agama::class,
            'kegiatan' => KegiatanAnggota::class,
            'pendidikan_terakhir' => PendidikanTerakhir::class,
            'pendapatan_per_bulan' => 'decimal:2',
            'status' => StatusAnggotaKeluarga::class,
            'tanggal_peristiwa' => 'date',
        ];
    }

    public function transmigran(): BelongsTo
    {
        return $this->belongsTo(Transmigran::class, 'transmigran_id', 'id_transmigran');
    }
}
