<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Asal kepemilikan alat dan mesin pertanian.
 *
 * Daftar nilai baku ada pada agents/rules.md bagian 7b.1.
 */
enum KepemilikanAlsintan: string
{
    use PunyaLabel;

    case Pribadi = 'Pribadi';
    case BantuanPoktan = 'Bantuan Poktan';
}