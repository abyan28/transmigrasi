<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Tingkat kesuburan tanah satu satuan permukiman.
 *
 * Diisi dari Bab II sub-bagian 4 Laporan Monografi (Rombongan C, 2026-08-28).
 * Kisaran pH disimpan terpisah pada `ph_tanah_min` dan `ph_tanah_maks`.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.
 */
enum TingkatKesuburanTanah: string
{
    use PunyaLabel;

    case Subur = 'Subur';
    case Sedang = 'Sedang';
    case KurangSubur = 'Kurang Subur';
}
