<?php

namespace App\Models;

use App\Models\Concerns\BerslugOtomatis;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Jenis komoditas pertanian. `tipe` disimpan sebagai TEKS nilai referensi
 * (jenis `tipe_komoditas`), bukan di-cast ke PHP Enum: Admin boleh menambah
 * tipe baru lewat data master tanpa ALTER TABLE (`data-dictionary.md` 5.6).
 *
 * Pengenal publik URL: `slug`.
 */
class Komoditas extends Model
{
    use BerslugOtomatis;
    use SoftDeletes;

    protected $table = 'komoditas';

    protected $primaryKey = 'id_komoditas';

    protected $fillable = ['satuan_id', 'nama', 'slug', 'tipe', 'is_unggulan', 'deskripsi'];

    protected function casts(): array
    {
        return [
            'is_unggulan' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_id', 'id_satuan');
    }

    /**
     * Poktan yang mengusahakan komoditas ini (pivot M:N `komoditas_poktan`).
     */
    public function poktan(): BelongsToMany
    {
        return $this->belongsToMany(
            Poktan::class,
            'komoditas_poktan',
            'komoditas_id',
            'poktan_id',
            'id_komoditas',
            'id_poktan',
        )->withTimestamps();
    }

    public function penanaman(): HasMany
    {
        return $this->hasMany(Penanaman::class, 'komoditas_id', 'id_komoditas');
    }

    /**
     * Pengadaan saprotan berjenis Benih untuk komoditas ini.
     */
    public function saprotan(): HasMany
    {
        return $this->hasMany(Saprotan::class, 'komoditas_id', 'id_komoditas');
    }
}
