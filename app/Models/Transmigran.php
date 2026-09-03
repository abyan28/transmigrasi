<?php

namespace App\Models;

use App\Enums\Agama;
use App\Enums\JenisKelamin;
use App\Enums\PendidikanTerakhir;
use App\Enums\StatusAnggotaPoktan;
use App\Enums\StatusSertifikat;
use App\Enums\StatusTinggal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Satu baris = satu kepala keluarga / KK. `usia` & `jumlah_anggota_keluarga`
 * TIDAK disimpan (diturunkan). `status_anggota_poktan` = penanda cepat
 * (kebenaran ada di `anggota_poktan`). `status_sertifikat` (SHM) melekat di KK,
 * bukan per bidang lahan (`rules.md` 7.6).
 *
 * Pengenal publik URL: `uuid` -- data pribadi tak boleh terekspos lewat id urut.
 */
class Transmigran extends Model
{
    use SoftDeletes;

    protected $table = 'transmigran';

    protected $primaryKey = 'id_transmigran';

    protected $fillable = [
        'uuid', 'satuan_permukiman_id', 'nik', 'no_kk', 'nama_kepala_keluarga',
        'jenis_kelamin', 'agama', 'tempat_lahir', 'tanggal_lahir', 'pendidikan_terakhir',
        'pekerjaan_kepala_keluarga', 'pendapatan_per_bulan', 'daerah_asal_kabupaten_id',
        'tahun_kedatangan', 'status_tinggal', 'status_anggota_poktan', 'status_sertifikat',
        'telepon', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'jenis_kelamin' => JenisKelamin::class,
            'agama' => Agama::class,
            'pendidikan_terakhir' => PendidikanTerakhir::class,
            'tanggal_lahir' => 'date',
            'pendapatan_per_bulan' => 'decimal:2',
            'status_tinggal' => StatusTinggal::class,
            'status_anggota_poktan' => StatusAnggotaPoktan::class,
            'status_sertifikat' => StatusSertifikat::class,
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
     * Kabupaten/kota daerah asal; NULL bila belum terdata.
     */
    public function daerahAsal(): BelongsTo
    {
        return $this->belongsTo(Kabupaten::class, 'daerah_asal_kabupaten_id', 'id_kabupaten');
    }

    public function anggotaKeluarga(): HasMany
    {
        return $this->hasMany(AnggotaKeluarga::class, 'transmigran_id', 'id_transmigran');
    }

    /**
     * Rumah yang dihuni KK ini (paling banyak satu -- `rumah.transmigran_id` UNIQUE).
     */
    public function rumah(): HasOne
    {
        return $this->hasOne(Rumah::class, 'transmigran_id', 'id_transmigran');
    }

    public function riwayatPenghunian(): HasMany
    {
        return $this->hasMany(RiwayatPenghunian::class, 'transmigran_id', 'id_transmigran');
    }

    public function riwayatKepalaKeluarga(): HasMany
    {
        return $this->hasMany(RiwayatKepalaKeluarga::class, 'transmigran_id', 'id_transmigran');
    }

    /**
     * Dokumen KK (KTP, KK, SK penempatan, SHM) lewat pivot `transmigran_berkas`.
     */
    public function berkas(): BelongsToMany
    {
        return $this->belongsToMany(
            Berkas::class,
            'transmigran_berkas',
            'transmigran_id',
            'berkas_id',
            'id_transmigran',
            'id_berkas',
        )->withPivot('peran', 'urutan')->withTimestamps();
    }
}
