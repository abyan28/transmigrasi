<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;
use App\Enums\Concerns\PunyaWarnaBadge;

/**
 * Kemajuan serah terima aset kepada penerima.
 *
 * Daftar nilai baku ada pada agents/rules.md bagian 4b.4.
 */
enum StatusPenyerahan: string
{
    use PunyaLabel;
    use PunyaWarnaBadge;

    case SudahDiserahkan = 'Sudah Diserahkan';
    case DalamProses = 'Dalam Proses';
    case BelumDiserahkan = 'Belum Diserahkan';

    public function warna(): string
    {
        return match ($this) {
            self::SudahDiserahkan => 'success',
            self::DalamProses => 'warning',
            self::BelumDiserahkan => 'gray',
        };
    }
}
