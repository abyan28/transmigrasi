<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Penanda apakah transmigran tergabung dalam kelompok tani.
 *
 * Daftar nilai baku ada pada agents/rules.md bagian 6.2.
 */
enum StatusAnggotaPoktan: string
{
    use PunyaLabel;

    case Ya = 'Ya';
    case Tidak = 'Tidak';
}