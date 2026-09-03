<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Slug pengenal publik untuk data master (Task 3.9, `rules.md` 4.0a poin 1-3).
 *
 * - PK integer tetap kunci internal; slug hanya pengenal URL yang terbaca
 *   ("/dashboard/sp/kapitan-meo").
 * - Slug DITURUNKAN dari `nama` saat baris dibuat, lalu **tidak pernah
 *   berubah** meski nama disunting -- supaya tautan yang sudah dibagikan tidak
 *   rusak (poin 3). Perubahan `slug` lewat form/mass-assignment diabaikan
 *   diam-diam.
 * - Slug yang sudah diisi pemanggil (mis. seeder, uji) dihormati apa adanya.
 * - Keunikan diperiksa LINTAS seluruh baris: termasuk yang ter-`softDelete`
 *   dan tanpa memandang global scope (cakupan data), sebab kolomnya UNIQUE di
 *   tingkat basis data.
 *
 * Dipakai `SatuanPermukiman`, `KawasanTransmigrasi`, `Poktan`, `Komoditas`
 * (`data-dictionary.md` 2 catatan slug). Model boleh menimpa `kolomSumberSlug()`
 * bila sumbernya bukan `nama`.
 */
trait BerslugOtomatis
{
    public static function bootBerslugOtomatis(): void
    {
        static::creating(function (Model $model): void {
            if (blank($model->slug)) {
                $model->slug = $model->slugUnik(
                    Str::slug((string) $model->{$model->kolomSumberSlug()})
                );
            }
        });

        static::updating(function (Model $model): void {
            if ($model->isDirty('slug')) {
                // Slug tidak berubah setelah dibuat (`rules.md` 4.0a poin 3).
                $model->slug = $model->getOriginal('slug');
            }
        });
    }

    /**
     * Kolom asal slug. Keempat pemakainya memakai `nama`.
     */
    protected function kolomSumberSlug(): string
    {
        return 'nama';
    }

    /**
     * Menjadikan `$dasar` unik dengan menambah `-2`, `-3`, ... bila perlu.
     * `slug` maksimal `VARCHAR(120)`, jadi dasarnya dipangkas lebih dulu agar
     * masih ada ruang untuk akhiran angka.
     */
    protected function slugUnik(string $dasar): string
    {
        $dasar = Str::limit(trim($dasar, '-'), 110, '');
        $dasar = $dasar !== '' ? $dasar : 'data';

        $slug = $dasar;
        $urut = 2;

        while ($this->slugTerpakai($slug)) {
            $slug = $dasar.'-'.$urut++;
        }

        return $slug;
    }

    /**
     * Apakah `$slug` sudah dipakai baris lain -- termasuk baris terhapus lunak
     * dan di luar cakupan data pengguna aktif (kolomnya UNIQUE apa adanya).
     *
     * `withoutGlobalScopes()` sekaligus melepas SoftDeletingScope, sehingga
     * baris terhapus lunak ikut terhitung tanpa perlu `withTrashed()`.
     */
    protected function slugTerpakai(string $slug): bool
    {
        return static::query()->withoutGlobalScopes()->where('slug', $slug)->exists();
    }
}
