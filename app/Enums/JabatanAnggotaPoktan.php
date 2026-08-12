<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Kedudukan seseorang dalam kelompok tani.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.15.
 */
enum JabatanAnggotaPoktan: string
{
    use PunyaLabel;

    case Ketua = 'Ketua';
    case Sekretaris = 'Sekretaris';
    case Bendahara = 'Bendahara';
    case Anggota = 'Anggota';
}