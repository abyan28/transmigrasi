<?php

namespace App\Models;

use App\Enums\StatusPengaduan;
use App\Models\Concerns\DisaringLewatInduk;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Riwayat penanganan pengaduan; setiap perubahan status = satu baris.
 * `status_sebelum` NULL pada baris pertama. Tabel riwayat: TANPA soft delete.
 * FK pengaduan CASCADE, user (petugas penangan) RESTRICT.
 */
class PenangananPengaduan extends Model
{
    use DisaringLewatInduk;

    protected static string $indukCakupan = 'pengaduan';

    protected $table = 'penanganan_pengaduan';

    protected $primaryKey = 'id_penanganan_pengaduan';

    protected $fillable = [
        'pengaduan_id', 'user_id', 'status_sebelum', 'status_sesudah',
        'tanggal_penanganan', 'catatan',
    ];

    protected function casts(): array
    {
        return [
            'status_sebelum' => StatusPengaduan::class,
            'status_sesudah' => StatusPengaduan::class,
            'tanggal_penanganan' => 'date',
        ];
    }

    public function pengaduan(): BelongsTo
    {
        return $this->belongsTo(Pengaduan::class, 'pengaduan_id', 'id_pengaduan');
    }

    /**
     * Petugas penangan.
     */
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id_user');
    }

    /**
     * Dokumen tindak lanjut tahap ini, lewat pivot `penanganan_pengaduan_berkas`.
     */
    public function berkas(): BelongsToMany
    {
        return $this->belongsToMany(
            Berkas::class,
            'penanganan_pengaduan_berkas',
            'penanganan_pengaduan_id',
            'berkas_id',
            'id_penanganan_pengaduan',
            'id_berkas',
        )->withPivot('peran', 'urutan')->withTimestamps();
    }
}
