<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Jenis tindakan yang dapat diizinkan pada sebuah modul.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.26.
 */
enum AksiPermission: string
{
    use PunyaLabel;

    case Lihat = 'lihat';
    case Tambah = 'tambah';
    case Ubah = 'ubah';
    case Hapus = 'hapus';
    case Verifikasi = 'verifikasi';
    case Export = 'export';
}