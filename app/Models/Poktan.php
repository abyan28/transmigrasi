<?php

namespace App\Models;

use App\Enums\AsalWakilPoktan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Kelompok tani. Ketua punya 3 asal-usul (`asal_ketua`). `jumlah_anggota` &
 * `luas_lahan_kelompok` TIDAK disimpan (diturunkan). `nama_ketua`/`nik_ketua`/
 * `luas_*_ketua` hanya bila `asal_ketua = Bukan Transmigran`.
 *
 * Pengenal publik URL: `slug`.
 */
class Poktan extends Model
{
    use SoftDeletes;

    protected $table = 'poktan';

    protected $primaryKey = 'id_poktan';

    protected $fillable = [
        'satuan_permukiman_id', 'slug', 'nama', 'asal_ketua', 'ketua_transmigran_id',
        'ketua_anggota_keluarga_id', 'nama_ketua', 'nik_ketua', 'tahun_berdiri',
        'telepon_ketua', 'email_ketua', 'alamat_ketua', 'luas_kering_ketua',
        'luas_basah_ketua', 'lintang', 'bujur', 'berkas_id', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'asal_ketua' => AsalWakilPoktan::class,
            'luas_kering_ketua' => 'decimal:2',
            'luas_basah_ketua' => 'decimal:2',
            'lintang' => 'decimal:7',
            'bujur' => 'decimal:7',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function satuanPermukiman(): BelongsTo
    {
        return $this->belongsTo(SatuanPermukiman::class, 'satuan_permukiman_id', 'id_satuan_permukiman');
    }

    /**
     * Keluarga yang diwakili ketua; NULL bila `asal_ketua = Bukan Transmigran`.
     */
    public function ketuaTransmigran(): BelongsTo
    {
        return $this->belongsTo(Transmigran::class, 'ketua_transmigran_id', 'id_transmigran');
    }

    /**
     * Anggota keluarga yang menjadi ketua; hanya bila `asal_ketua = Anggota Keluarga`.
     */
    public function ketuaAnggotaKeluarga(): BelongsTo
    {
        return $this->belongsTo(AnggotaKeluarga::class, 'ketua_anggota_keluarga_id', 'id_anggota_keluarga');
    }

    /**
     * Dokumen SK pembentukan (FK langsung tunggal).
     */
    public function berkas(): BelongsTo
    {
        return $this->belongsTo(Berkas::class, 'berkas_id', 'id_berkas');
    }

    public function anggota(): HasMany
    {
        return $this->hasMany(AnggotaPoktan::class, 'poktan_id', 'id_poktan');
    }

    public function alsintanDistribusi(): HasMany
    {
        return $this->hasMany(AlsintanDistribusi::class, 'poktan_id', 'id_poktan');
    }

    public function saprotanDistribusi(): HasMany
    {
        return $this->hasMany(SaprotanDistribusi::class, 'poktan_id', 'id_poktan');
    }

    /**
     * Lahan yang dikelola poktan ini.
     */
    public function lahan(): HasMany
    {
        return $this->hasMany(Lahan::class, 'poktan_id', 'id_poktan');
    }
}
