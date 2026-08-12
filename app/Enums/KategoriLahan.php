<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Sifat pengairan lahan usaha.
 *
 * Daftar nilai baku ada pada agents/rules.md bagian 7.5.
 */
enum KategoriLahan: string
{
    use PunyaLabel;

    case LahanBasah = 'Lahan Basah';
    case LahanKering = 'Lahan Kering';
}