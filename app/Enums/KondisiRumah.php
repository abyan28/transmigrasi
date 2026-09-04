<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;
use App\Enums\Concerns\PunyaWarnaBadge;

/**
 * Tingkat kerusakan bangunan rumah transmigran.
 *
 * Daftar nilai baku ada pada agents/rules.md bagian 6a.3.
 */
enum KondisiRumah: string
{
    use PunyaLabel;
    use PunyaWarnaBadge;

    case TidakRusak = 'Tidak Rusak';
    case RusakRingan = 'Rusak Ringan';
    case RusakBerat = 'Rusak Berat';

    public function warna(): string
    {
        return match ($this) {
            self::TidakRusak => 'success',
            self::RusakRingan => 'warning',
            self::RusakBerat => 'error',
        };
    }
}
