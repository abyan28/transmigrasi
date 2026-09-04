<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Registry METADATA seluruh berkas sistem (Putaran 12). BUKAN polymorphic:
 * kepemilikan lewat pivot `*_berkas` per domain atau FK langsung.
 *
 * `uuid` = pengenal publik URL (PK integer tetap kunci internal). `user_id`
 * NULL = unggahan kanal publik tanpa akun. Soft delete: berkas fisik
 * dibersihkan terjadwal (`rules.md` 14a.8).
 */
class Berkas extends Model
{
    use SoftDeletes;

    protected $table = 'berkas';

    protected $primaryKey = 'id_berkas';

    protected $fillable = [
        'uuid', 'jenis_berkas_id', 'nama_file', 'nama_asli', 'path',
        'mime', 'ekstensi', 'ukuran', 'disk', 'keterangan', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'ukuran' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Penggolongan berkas (jenis `jenis_dokumen` pada daftar pilihan); NULL = tanpa golongan.
     */
    public function jenisBerkas(): BelongsTo
    {
        return $this->belongsTo(DaftarPilihan::class, 'jenis_berkas_id', 'id_daftar_pilihan');
    }

    /**
     * Petugas pengunggah; NULL bila diunggah lewat kanal publik.
     */
    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id_user');
    }
}
