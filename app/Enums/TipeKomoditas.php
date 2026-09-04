<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Pengelompokan komoditas menurut jenis tanamannya.
 *
 * Daftar nilai baku ada pada agents/rules.md bagian 8.5.
 */
enum TipeKomoditas: string
{
    use PunyaLabel;

    case Pangan = 'Pangan';
    case Palawija = 'Palawija';
    case Hortikultura = 'Hortikultura';
}
