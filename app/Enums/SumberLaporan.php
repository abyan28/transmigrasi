<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Cara pengaduan masuk ke sistem.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.28.
 */
enum SumberLaporan: string
{
    use PunyaLabel;

    case Publik = 'Publik';
    case Petugas = 'Petugas';
}