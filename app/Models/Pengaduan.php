<?php

namespace App\Models;

use App\Enums\StatusPengaduan;
use App\Enums\SumberLaporan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Pengaduan warga. Kanal PUBLIK tanpa login: `user_id` NULL bila dari warga,
 * terisi bila dicatat petugas. `bidang` NULL = belum ditetapkan. `status` =
 * terkini saja; riwayat perubahan di `penanganan_pengaduan`. `kategori`/
 * `bidang`/`prioritas` = TEKS nilai referensi.
 *
 * Pengenal publik URL: `uuid` (bukan `nomor_pengaduan` yang tampil ke pelapor).
 */
class Pengaduan extends Model
{
    use SoftDeletes;

    protected $table = 'pengaduan';

    protected $primaryKey = 'id_pengaduan';

    protected $fillable = [
        'uuid', 'user_id', 'nama_pelapor', 'kontak_pelapor', 'email_pelapor',
        'sumber_laporan', 'ip_pelapor', 'satuan_permukiman_id', 'nomor_pengaduan',
        'tanggal_pengaduan', 'kategori', 'bidang', 'judul', 'deskripsi', 'status',
        'prioritas', 'lintang', 'bujur',
    ];

    protected function casts(): array
    {
        return [
            'sumber_laporan' => SumberLaporan::class,
            'status' => StatusPengaduan::class,
            'tanggal_pengaduan' => 'date',
            'lintang' => 'decimal:7',
            'bujur' => 'decimal:7',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Petugas yang mencatat; NULL bila diajukan langsung lewat kanal publik.
     */
    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id_user');
    }

    public function satuanPermukiman(): BelongsTo
    {
        return $this->belongsTo(SatuanPermukiman::class, 'satuan_permukiman_id', 'id_satuan_permukiman');
    }

    public function penanganan(): HasMany
    {
        return $this->hasMany(PenangananPengaduan::class, 'pengaduan_id', 'id_pengaduan');
    }

    /**
     * Foto bukti dari pelapor, lewat pivot `pengaduan_berkas`.
     */
    public function berkas(): BelongsToMany
    {
        return $this->belongsToMany(
            Berkas::class,
            'pengaduan_berkas',
            'pengaduan_id',
            'berkas_id',
            'id_pengaduan',
            'id_berkas',
        )->withPivot('peran', 'urutan')->withTimestamps();
    }
}
