<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Kedudukan seorang anggota keluarga terhadap kepala keluarga.
 *
 * BEDA dari `HubunganKeluarga` (bagian 11.35). Enum itu "sengaja kasar",
 * dipakai `anggota_poktan` dan `riwayat_kepala_keluarga` saat sistem BELUM
 * mendata anggota keluarga. Enum ini dipakai tabel `anggota_keluarga` yang
 * memang mendata mereka satu per satu (Rombongan B, 2026-08-28), sehingga
 * boleh lebih rinci.
 *
 * Pasangan dipisah Istri/Suami agar jenis kelaminnya jelas dari hubungannya.
 * Tidak dirinci sampai "anak kedua": urutan kelahiran tidak dipakai
 * perhitungan mana pun.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.
 */
enum HubunganAnggotaKeluarga: string
{
    use PunyaLabel;

    case Istri = 'Istri';
    case Suami = 'Suami';
    case Anak = 'Anak';
    case AnakAngkat = 'Anak Angkat';
    case OrangTua = 'Orang Tua';
    case FamiliLain = 'Famili Lain';

    /**
     * Hubungan yang menandai pasangan kepala keluarga.
     *
     * Dipakai suksesi kepala keluarga: pasangan adalah calon pengganti yang
     * paling lazim, dan jenis kelamin kepala keluarga baru mengikuti.
     *
     * @return array<int, self>
     */
    public static function pasangan(): array
    {
        return [self::Istri, self::Suami];
    }
}
