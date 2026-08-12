<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;
use App\Enums\Concerns\PunyaWarnaBadge;

/**
 * Kondisi barang, fasilitas, alsintan, dan infrastruktur.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.5.
 */
enum Kondisi: string
{
    use PunyaLabel;
    use PunyaWarnaBadge;

    case Baik = 'Baik';
    case RusakRingan = 'Rusak Ringan';
    case RusakBerat = 'Rusak Berat';

    public function warna(): string
    {
        return match ($this) {
            self::Baik => 'success',
            self::RusakRingan => 'warning',
            self::RusakBerat => 'error',
        };
    }
}