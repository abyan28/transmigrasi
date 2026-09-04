<?php

namespace App\Support;

use App\Models\Lahan;

/**
 * Rekap luas lahan USAHA satu keluarga, dari bidangnya yang sudah Eloquent.
 *
 * Dipakai bersama modul poktan (luas ketua & wakil diturunkan dari bidang
 * keluarga) dan form-form yang menampilkannya sebagai bacaan lewat
 * `ViewServiceProvider`. `luas_usaha` NULL = belum menerima -> rekap kosong,
 * bukan nol paksa (`rules.md` 7.5a). Hanya lahan usaha yang berkomposisi
 * kering/basah; pekarangan tidak ikut.
 */
class RekapLahan
{
    /**
     * @return array{kering: float, basah: float, total: float, lintang: float|null, bujur: float|null, jumlah_bidang: int}
     */
    public static function keluarga(?Lahan $lahan): array
    {
        $kosong = ['kering' => 0.0, 'basah' => 0.0, 'total' => 0.0, 'lintang' => null, 'bujur' => null, 'jumlah_bidang' => 0];

        if ($lahan === null || $lahan->luas_usaha === null) {
            return $kosong;
        }

        return [
            'kering' => round((float) ($lahan->luas_kering ?? 0), 2),
            'basah' => round((float) ($lahan->luas_basah ?? 0), 2),
            'total' => round((float) $lahan->luas_usaha, 2),
            'lintang' => $lahan->lintang_usaha === null ? null : (float) $lahan->lintang_usaha,
            'bujur' => $lahan->bujur_usaha === null ? null : (float) $lahan->bujur_usaha,
            'jumlah_bidang' => 1,
        ];
    }
}
