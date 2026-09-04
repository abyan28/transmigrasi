<?php

namespace App\Support;

use App\Models\Satuan;

/**
 * Konversi volume panen ke ton (Task 7.5).
 *
 * Dipakai seluruh rekap dan dashboard agar agregasi lintas komoditas tetap
 * sepadan (`rules.md` §8a poin 5). Produksi disimpan APA ADANYA dalam satuan
 * baku komoditasnya; konversi ke ton hanya terjadi saat rekap, supaya angka
 * asli lapangan tetap terjaga (§8a poin 4).
 *
 * Satuan non-berat (`faktor_ke_ton` NULL, mis. Liter/Rol) tidak pernah
 * menyentuh volume panen -- komoditas selalu bersatuan berat -- tetapi bila
 * toh terjadi, faktornya dianggap 0: menjumlahkannya sebagai tonase adalah
 * kekeliruan, bukan nilai yang perlu ditebak.
 */
class KonversiPanen
{
    /**
     * @param  Satuan|string|int|null  $satuan  Model, nama, atau id satuan
     */
    public static function keTon(float $volume, Satuan|string|int|null $satuan): float
    {
        return round($volume * self::faktor($satuan), 6);
    }

    private static function faktor(Satuan|string|int|null $satuan): float
    {
        if ($satuan instanceof Satuan) {
            return $satuan->faktor_ke_ton === null ? 0.0 : (float) $satuan->faktor_ke_ton;
        }

        if ($satuan === null || $satuan === '') {
            return 0.0;
        }

        $kolom = is_int($satuan) || ctype_digit((string) $satuan) ? 'id_satuan' : 'nama';
        $nilai = Satuan::where($kolom, $satuan)->value('faktor_ke_ton');

        return $nilai === null ? 0.0 : (float) $nilai;
    }
}
