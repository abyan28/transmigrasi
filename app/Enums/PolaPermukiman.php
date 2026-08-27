<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Pola tata ruang satu satuan permukiman transmigrasi.
 *
 * Diisi dari Bab II sub-bagian 3.2 Laporan Monografi (Rombongan C,
 * 2026-08-28). "Pola Konsentris" pada berkas Kapitan Meo berarti permukiman
 * terpusat lalu dikelilingi lahan pekarangan dan lahan usaha.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.
 */
enum PolaPermukiman: string
{
    use PunyaLabel;

    case Konsentris = 'Konsentris';
    case PapanCatur = 'Papan Catur';
    case Linear = 'Linear';
    case Menyebar = 'Menyebar';
}
