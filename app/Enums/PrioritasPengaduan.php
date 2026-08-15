<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;
use App\Enums\Concerns\PunyaWarnaBadge;

/**
 * Tingkat kesegeraan penanganan pengaduan.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.24.
 *
 * Prioritas ditentukan SEPENUHNYA oleh petugas yang menangani, tidak
 * diturunkan otomatis dari kategori. Penurunan otomatis sempat dirancang lalu
 * dibatalkan pada 2026-08-14: kategori hanya menyatakan pokok masalah,
 * sedangkan kegentingan bergantung pada keadaan lapangan yang tidak terbaca
 * dari kategori. Dua laporan berkategori sama dapat berbeda jauh
 * kemendesakannya, dan hanya petugas yang meninjau dapat menilainya.
 */
enum PrioritasPengaduan: string
{
    use PunyaLabel;
    use PunyaWarnaBadge;

    case Rendah = 'Rendah';
    case Sedang = 'Sedang';
    case Tinggi = 'Tinggi';
    case Mendesak = 'Mendesak';

    public function warna(): string
    {
        return match ($this) {
            self::Rendah => 'gray',
            self::Sedang => 'teal',
            self::Tinggi => 'warning',
            self::Mendesak => 'error',
        };
    }

}