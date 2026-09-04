<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Baris kunci-nilai Pengelolaan Konten Sistem (Task 9.6).
 *
 * Dibaca lewat `App\Support\KontenSistem`, bukan langsung -- support itu yang
 * menyediakan bawaan, penafsiran `tipe`, dan pengingat per-permintaan.
 */
class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    protected $primaryKey = 'kunci';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['kunci', 'nilai', 'tipe'];
}
