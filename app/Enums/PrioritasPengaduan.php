<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;
use App\Enums\Concerns\PunyaWarnaBadge;

/**
 * Tingkat kesegeraan penanganan pengaduan.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.24.
 */
enum PrioritasPengaduan: string
{
    use PunyaLabel;
    use PunyaWarnaBadge;

    case Rendah = 'Rendah';
    case Sedang = 'Sedang';
    case Tinggi = 'Tinggi';
    case Mendesak = 'Mendesak';

    public function warna(): string
    {
        return match ($this) {
            self::Rendah => 'gray',
            self::Sedang => 'teal',
            self::Tinggi => 'warning',
            self::Mendesak => 'error',
        };
    }
}