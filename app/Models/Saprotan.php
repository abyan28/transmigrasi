<?php

namespace App\Models;

use App\Enums\JenisSaprotan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sarana produksi pertanian -- induk / pengadaan. `komoditas_id` & `varietas`
 * wajib hanya bila `jenis = Benih` (ditegakkan aplikasi). `tahun_pengadaan` =
 * tahun anggaran (sumbu laporan panen). `jadwal_tanam` = rencana YYYY-MM.
 * Penerima selalu poktan.
 */
class Saprotan extends Model
{
    use SoftDeletes;

    protected $table = 'saprotan';

    protected $primaryKey = 'id_saprotan';

    protected $fillable = [
        'satuan_id', 'komoditas_id', 'jenis', 'nama', 'jumlah_total', 'varietas',
        'jadwal_tanam', 'tahun_pengadaan', 'sumber_dana', 'foto_berkas_id',
        'berkas_id', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'jenis' => JenisSaprotan::class,
            'jumlah_total' => 'decimal:3',
        ];
    }

    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_id', 'id_satuan');
    }

    /**
     * Komoditas benih; hanya bila `jenis = Benih`.
     */
    public function komoditas(): BelongsTo
    {
        return $this->belongsTo(Komoditas::class, 'komoditas_id', 'id_komoditas');
    }

    /**
     * Foto barang (FK langsung tunggal).
     */
    public function foto(): BelongsTo
    {
        return $this->belongsTo(Berkas::class, 'foto_berkas_id', 'id_berkas');
    }

    /**
     * Berita acara penyaluran (FK langsung tunggal).
     */
    public function berkas(): BelongsTo
    {
        return $this->belongsTo(Berkas::class, 'berkas_id', 'id_berkas');
    }

    public function distribusi(): HasMany
    {
        return $this->hasMany(SaprotanDistribusi::class, 'saprotan_id', 'id_saprotan');
    }
}
