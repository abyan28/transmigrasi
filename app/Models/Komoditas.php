<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
}
