<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Peruntukan lahan yang dimiliki transmigran.
 *
 * Daftar nilai baku ada pada agents/rules.md bagian 7.2.
 */
enum JenisLahan: string
{
    use PunyaLabel;

    case LahanPekarangan = 'Lahan Pekarangan';
    case LahanUsaha = 'Lahan Usaha';
}