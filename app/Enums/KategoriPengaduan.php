<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Pokok masalah yang diadukan warga.
 *
 * Daftar nilai baku ada pada agents/rules.md bagian 10b.3.
 *
 * Kategori menentukan bidang dinas penanganan lewat
 * BidangPengaduan::dariKategori(). Sebagian kategori sengaja tidak dapat
 * disimpulkan bidangnya dan wajib ditetapkan petugas (10b poin 7).
 */
enum KategoriPengaduan: string
{
    use PunyaLabel;

    case LahanUsaha = 'Lahan Usaha';
    case LahanPekarangan = 'Lahan Pekarangan';
    case Rumah = 'Rumah';
    case Infrastruktur = 'Infrastruktur';

    /*
     * Nilai 'Peralatan dan Perlengkapan' dipecah menjadi dua (2026-08-19),
     * sebab satu kategori menaungi dua tabel berbeda sehingga petugas tidak
     * dapat mengetahui daftar mana yang dimaksud pelapor.
     */
    case InventarisSp = 'Inventaris SP';
    case FasilitasSp = 'Fasilitas SP';

    case KelompokTani = 'Kelompok Tani';
    case Alsintan = 'Alsintan';
    case Saprotan = 'Saprotan';
    case ProduksiPanen = 'Produksi Panen';
    case Bencana = 'Bencana';
    case Lainnya = 'Lainnya';
}
