<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;
use App\Enums\Concerns\PunyaWarnaBadge;

/**
 * Keadaan hunian sebuah rumah.
 *
 * Daftar nilai baku ada pada agents/rules.md bagian 6a.4.
 */
enum StatusHunian: string
{
    use PunyaLabel;
    use PunyaWarnaBadge;

    case Dihuni = 'Dihuni';
    case TidakDihuni = 'Tidak Dihuni';

    public function warna(): string
    {
        return match ($this) {
            self::Dihuni => 'teal',
            self::TidakDihuni => 'gray',
        };
    }
}