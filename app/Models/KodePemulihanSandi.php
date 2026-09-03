<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kode verifikasi 6 digit untuk pemulihan kata sandi mandiri (Task 3.11,
 * `rules.md` 14b poin 7-10).
 *
 * Yang disimpan adalah SIDIK kode (`kode_hash`), bukan angkanya -- basis data
 * yang bocor tidak boleh langsung memberi jalan masuk. Kode berlaku 15 menit,
 * sekali pakai (`dipakai_pada`), maksimal 5 percobaan (`percobaan`), dan
 * dibatalkan begitu kode baru diminta. Tabel riwayat singkat: hanya
 * `created_at` (dasar batas 3 permintaan/jam), tanpa `updated_at`, tanpa
 * soft delete.
 */
class KodePemulihanSandi extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'kode_pemulihan_sandi';

    protected $primaryKey = 'id_kode_pemulihan';

    protected $fillable = [
        'user_id', 'kode_hash', 'kedaluwarsa_pada', 'percobaan', 'dipakai_pada',
    ];

    protected function casts(): array
    {
        return [
            'kedaluwarsa_pada' => 'datetime',
            'dipakai_pada' => 'datetime',
            'percobaan' => 'integer',
        ];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id_user');
    }

    /**
     * Kode yang masih dapat dipakai: belum terpakai, belum kedaluwarsa, dan
     * belum melewati 5 percobaan.
     */
    public function scopeMasihBerlaku(Builder $query): Builder
    {
        return $query->whereNull('dipakai_pada')
            ->where('kedaluwarsa_pada', '>', now())
            ->where('percobaan', '<', 5);
    }
}
