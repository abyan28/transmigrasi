<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Bentuk permukaan wilayah (topografi) satu satuan permukiman.
 *
 * Diisi dari Bab II sub-bagian 5 Laporan Monografi (Rombongan C, 2026-08-28).
 * Persentase kemiringan lereng disimpan terpisah pada `kemiringan_min_persen`
 * dan `kemiringan_maks_persen`.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.
 */
enum BentukWilayah: string
{
    use PunyaLabel;

    case Datar = 'Datar';
    case Bergelombang = 'Bergelombang';
    case Berbukit = 'Berbukit';
    case Bergunung = 'Bergunung';
}
