<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ambang + wording predikat kondisi SP, disunting dinas lewat
 * /master/penilaian-kondisi. Jumlah baris tetap 3; `kode` = kunci enum
 * perilaku `App\Enums\StatusKondisiSp::dariSkor`. Tanpa FK, tanpa soft delete.
 *
 * Model ini (data ambang) berbeda dari enum `App\Enums\StatusKondisiSp`
 * (perilaku): yang disunting hanya nama/keterangan/ambang, bukan `kode`.
 */
class StatusKondisiSp extends Model
{
    protected $table = 'status_kondisi_sp';

    protected $primaryKey = 'id_status_kondisi_sp';

    protected $fillable = ['kode', 'nama', 'keterangan', 'ambang_bawah', 'warna', 'urutan'];

    protected function casts(): array
    {
        return [
            'ambang_bawah' => 'decimal:2',
            'urutan' => 'integer',
        ];
    }
}
