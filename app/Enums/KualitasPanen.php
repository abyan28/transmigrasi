<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;
use App\Enums\Concerns\PunyaWarnaBadge;

/**
 * Mutu hasil panen menurut penilaian petugas.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.19.
 */
enum KualitasPanen: string
{
    use PunyaLabel;
    use PunyaWarnaBadge;

    case SangatBaik = 'Sangat Baik';
    case Baik = 'Baik';
    case Cukup = 'Cukup';
    case Kurang = 'Kurang';

    public function warna(): string
    {
        return match ($this) {
            self::SangatBaik => 'success',
            self::Baik => 'teal',
            self::Cukup => 'warning',
            self::Kurang => 'error',
        };
    }
}