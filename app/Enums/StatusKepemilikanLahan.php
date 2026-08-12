<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Dasar hukum penguasaan lahan.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.13.
 */
enum StatusKepemilikanLahan: string
{
    use PunyaLabel;

    case Hpl = 'HPL';
    case Shm = 'SHM';
    case Sewa = 'Sewa';
    case Garapan = 'Garapan';
    case Lainnya = 'Lainnya';
}