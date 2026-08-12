<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;
use App\Enums\Concerns\PunyaWarnaBadge;

/**
 * Keberadaan transmigran di kawasan.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.8.
 */
enum StatusTinggal: string
{
    use PunyaLabel;
    use PunyaWarnaBadge;

    case Aktif = 'Aktif';
    case Pindah = 'Pindah';
    case TidakAktif = 'Tidak Aktif';
    case Meninggal = 'Meninggal';

    public function warna(): string
    {
        return match ($this) {
            self::Aktif => 'success',
            self::Pindah => 'warning',
            self::TidakAktif => 'gray',
            self::Meninggal => 'gray',
        };
    }
}