<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;
use App\Enums\Concerns\PunyaWarnaBadge;

/**
 * Keaktifan anggota kelompok tani.
 *
 * Daftar nilai baku ada pada agents/rules.md bagian 7a.4.
 */
enum StatusKeaktifanAnggota: string
{
    use PunyaLabel;
    use PunyaWarnaBadge;

    case Aktif = 'Aktif';
    case TidakAktif = 'Tidak Aktif';
    case SudahKeluar = 'Sudah Keluar';

    public function warna(): string
    {
        return match ($this) {
            self::Aktif => 'success',
            self::TidakAktif => 'gray',
            self::SudahKeluar => 'error',
        };
    }
}
