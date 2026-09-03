<?php

namespace App\Models\Concerns;

use App\Models\Scopes\CakupanDataSp;
use Illuminate\Database\Eloquent\Builder;

/**
 * Cakupan data untuk model yang TIDAK menyimpan `satuan_permukiman_id`
 * sendiri melainkan mewarisinya lewat induk (`rules.md` 5.0b-1 poin 9).
 *
 * Delegasi murni: scope hanya `whereHas(<induk>)`, dan scope induklah yang
 * menyaring SP -- logika SP tidak disalin ke sini. Model wajib
 * mendeklarasikan `protected static string $indukCakupan` = nama relasi
 * belongsTo ke induknya.
 *
 * Contoh: `HasilPanen::$indukCakupan = 'penanaman'` -> `whereHas('penanaman')`,
 * dan `Penanaman` sendiri disaring lewat `poktan`.
 */
trait DisaringLewatInduk
{
    public static function bootDisaringLewatInduk(): void
    {
        static::addGlobalScope('cakupanViaInduk', function (Builder $builder) {
            if (CakupanDataSp::penggunaWajibDisaring() !== null) {
                $builder->whereHas(static::$indukCakupan);
            }
        });
    }
}
