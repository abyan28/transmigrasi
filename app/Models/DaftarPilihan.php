<?php

namespace App\Models;

use App\Enums\JenisDaftarPilihan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nilai dropdown yang dikelola Admin (`data-dictionary.md` 5.6). Nilai baru
 * cukup INSERT tanpa ALTER TABLE. `jenis` ber-ENUM 19 nilai sebagai registry
 * daftar yang dikenal sistem. `nilai_skor` hanya untuk jenis
 * `kondisi`; `bidang_id` (self-FK) hanya untuk jenis `kategori_pengaduan`.
 * `nilai` yang sudah tersimpan tidak dapat diganti karena menjadi identitas
 * teks pada tabel pemakainya.
 *
 * Tanpa soft delete: dinonaktifkan lewat `is_aktif`, tidak dihapus.
 */
class DaftarPilihan extends Model
{
    protected $table = 'daftar_pilihan';

    protected $primaryKey = 'id_daftar_pilihan';

    protected $fillable = ['jenis', 'nilai', 'label', 'kode_perilaku', 'urutan', 'nilai_skor', 'bidang_id', 'is_aktif'];

    protected static function booted(): void
    {
        static::creating(function (self $pilihan): void {
            $pilihan->label ??= $pilihan->nilai;
        });
    }

    public static function nilaiPerilaku(JenisDaftarPilihan $jenis, string $kode): ?string
    {
        return self::query()
            ->where('jenis', $jenis->value)
            ->where('kode_perilaku', $kode)
            ->where('is_aktif', true)
            ->value('nilai');
    }

    public static function memilikiPerilaku(JenisDaftarPilihan $jenis, ?string $nilai, string $kode): bool
    {
        return $nilai !== null && self::query()
            ->where('jenis', $jenis->value)
            ->where('nilai', $nilai)
            ->where('kode_perilaku', $kode)
            ->exists();
    }

    public static function labelUntuk(JenisDaftarPilihan $jenis, ?string $nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        return self::query()
            ->where('jenis', $jenis->value)
            ->where('nilai', $nilai)
            ->value('label') ?? $nilai;
    }

    protected function casts(): array
    {
        return [
            'jenis' => JenisDaftarPilihan::class,
            'nilai_skor' => 'decimal:2',
            'urutan' => 'integer',
            'is_aktif' => 'boolean',
        ];
    }

    public static function opsi(JenisDaftarPilihan $jenis, bool $hanyaAktif = true): array
    {
        return self::query()
            ->where('jenis', $jenis->value)
            ->when($hanyaAktif, fn ($query) => $query->where('is_aktif', true))
            ->orderBy('urutan')
            ->orderBy('id_daftar_pilihan')
            ->pluck('label', 'nilai')
            ->all();
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
