<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Pokok masalah yang diadukan warga.
 *
 * Daftar nilai baku ada pada agents/rules.md bagian 10b.3.
 */
enum KategoriPengaduan: string
{
    use PunyaLabel;

    case LahanUsaha = 'Lahan Usaha';
    case LahanPekarangan = 'Lahan Pekarangan';
    case Rumah = 'Rumah';
    case Infrastruktur = 'Infrastruktur';
    case PeralatanDanPerlengkapan = 'Peralatan dan Perlengkapan';
    case Alsintan = 'Alsintan';
    case ProduksiPanen = 'Produksi Panen';
    case Bencana = 'Bencana';
    case Lainnya = 'Lainnya';
}