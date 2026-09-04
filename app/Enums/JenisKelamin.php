<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Jenis kelamin kepala keluarga.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 6.1.
 */
enum JenisKelamin: string
{
    use PunyaLabel;

    case LakiLaki = 'Laki-laki';
    case Perempuan = 'Perempuan';
}
