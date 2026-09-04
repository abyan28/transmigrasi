<?php

namespace App\Models;

use App\Enums\JenisDaftarPilihan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nilai dropdown yang dikelola Admin (`data-dictionary.md` 5.6). Nilai baru
 * cukup INSERT tanpa ALTER TABLE. `jenis` ber-ENUM 14 nilai (bukan daftar
 * pilihan: enum berperilaku tetap ENUM). `nilai_skor` hanya untuk jenis
 * `kondisi`; `bidang_id` (self-FK) hanya untuk jenis `kategori_pengaduan`.
 *
 * Tanpa soft delete: dinonaktifkan lewat `is_aktif`, tidak dihapus.
 */
class DaftarPilihan extends Model
{
    protected $table = 'daftar_pilihan';

    protected $primaryKey = 'id_daftar_pilihan';

    protected $fillable = ['jenis', 'nilai', 'urutan', 'nilai_skor', 'bidang_id', 'is_aktif'];

    protected function casts(): array
    {
        return [
            'jenis' => JenisDaftarPilihan::class,
            'nilai_skor' => 'decimal:2',
            'urutan' => 'integer',
            'is_aktif' => 'boolean',
        ];
    }

    /**
     * Bidang penanganan (jenis `bidang_pengaduan`) yang menaungi baris
     * `kategori_pengaduan` ini. NULL untuk jenis lain.
     */
    public function bidang(): BelongsTo
    {
        return $this->belongsTo(self::class, 'bidang_id', 'id_daftar_pilihan');
    }
}
