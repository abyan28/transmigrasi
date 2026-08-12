<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Bidang yang menentukan dinas mana yang menangani sebuah pengaduan.
 *
 * Pengaduan diteruskan sesuai bidangnya: bidang ketransmigrasian ke Dinas
 * Transmigrasi, bidang pertanian ke Dinas Pertanian (agents/rules.md bagian 10b).
 */
enum BidangPengaduan: string
{
    use PunyaLabel;

    case Ketransmigrasian = 'Ketransmigrasian';
    case Pertanian = 'Pertanian';

    /**
     * Menyimpulkan bidang penanganan dari kategori pengaduan.
     *
     * Dipakai agar warga tidak perlu memilih bidang sendiri pada halaman
     * pengaduan publik; cukup memilih kategori masalahnya.
     *
     * @param  KategoriPengaduan  $kategori  Kategori yang dipilih pelapor
     * @return self Bidang penanganan yang sesuai
     */
    public static function dariKategori(KategoriPengaduan $kategori): self
    {
        return match ($kategori) {
            KategoriPengaduan::LahanUsaha,
            KategoriPengaduan::Alsintan,
            KategoriPengaduan::ProduksiPanen => self::Pertanian,

            default => self::Ketransmigrasian,
        };
    }
}
