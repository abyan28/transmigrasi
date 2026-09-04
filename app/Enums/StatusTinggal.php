<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;
use App\Enums\Concerns\PunyaWarnaBadge;

/**
 * Keberadaan transmigran di kawasan.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.8.
 *
 * TANPA `Meninggal`. Status ini melekat pada KELUARGA, bukan orang, sehingga
 * kematian kepala keluarga tidak membubarkan barisnya: kedudukan berpindah ke
 * ahli waris dan keluarganya tetap `Aktif`. Peristiwa kematiannya sendiri
 * terekam pada `AlasanPergantianKK::Meninggal`, yang memang mencatat orang.
 *
 * Menyediakan keduanya membuat petugas menandai keluarga `Meninggal` ketika
 * yang wafat hanyalah kepalanya, dan seluruh rumah, lahan, serta keanggotaan
 * poktan keluarga itu ikut hilang dari rekap padahal penghuninya masih ada.
 */
enum StatusTinggal: string
{
    use PunyaLabel;
    use PunyaWarnaBadge;

    case Aktif = 'Aktif';
    case PindahPenduduk = 'Pindah Penduduk';
    case TidakAktif = 'Tidak Aktif';

    public function warna(): string
    {
        return match ($this) {
            self::Aktif => 'success',
            self::PindahPenduduk => 'warning',
            self::TidakAktif => 'gray',
        };
    }
}
