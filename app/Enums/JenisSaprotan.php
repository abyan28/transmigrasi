<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Jenis sarana produksi pertanian yang disalurkan.
 *
 * Daftar nilai baku ada pada agents/rules.md bagian 7c.1.
 */
enum JenisSaprotan: string
{
    use PunyaLabel;

    case Benih = 'Benih';
    case Pupuk = 'Pupuk';
    case Pestisida = 'Pestisida';
    case Mulsa = 'Mulsa';
    case Lainnya = 'Lainnya';
}