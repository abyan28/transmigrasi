<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Peruntukan bidang lahan yang diterima transmigran.
 *
 * Menggantikan nama lama `JenisLahan`. Istilah "peruntukan" dipakai karena
 * yang dibedakan adalah untuk apa bidang itu diberikan, bukan sifat fisiknya;
 * sifat pengairannya dicatat terpisah sebagai komposisi luas kering dan basah
 * pada kolom `luas_kering` dan `luas_basah` (agents/rules.md bagian 7.5).
 *
 * Sempat memuat `Lahan Usaha I` dan `Lahan Usaha II` pada 2026-08-18, atas
 * dugaan bahwa lahan usaha dibagikan bertahap. Dugaan itu **dibatalkan pada
 * hari yang sama** setelah pemilik proyek menyampaikan keadaan sebenarnya di
 * Kobalima Timur: satu transmigran menerima satu lahan pekarangan dan satu
 * lahan usaha, tidak lebih. Tanpa tahap yang benar-benar dipakai, dua nilai
 * tambahan itu hanya memaksa petugas memilih di antara pilihan yang tidak
 * pernah berbeda.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.11.
 */
enum PeruntukanLahan: string
{
    use PunyaLabel;

    case LahanPekarangan = 'Lahan Pekarangan';
    case LahanUsaha = 'Lahan Usaha';

    /**
     * Menandai peruntukan yang termasuk lahan usaha.
     *
     * Kategori lahan beserta empat kolom pengelolaan hanya berlaku bagi lahan
     * usaha. Pemeriksaannya dipusatkan di sini, bukan ditulis sebagai
     * perbandingan teks di tiap halaman: cara itu pernah membuat penjumlahan
     * luas kehilangan sebagian bidang tanpa ada yang menyadarinya.
     */
    public function lahanUsaha(): bool
    {
        return $this !== self::LahanPekarangan;
    }

    /**
     * Nilai-nilai yang termasuk lahan usaha, untuk dipakai di sisi Blade.
     *
     * `array_values` wajib ada: `array_filter` mempertahankan kunci aslinya,
     * sehingga hasilnya berkunci selain 0 dan diserialkan `@js()` menjadi
     * OBJEK JavaScript, bukan array. Pemanggilan `.includes()` di sisi Alpine
     * kemudian melempar galat, dan bagian yang bergantung padanya tidak pernah
     * muncul.
     *
     * @return array<int, string>
     */
    public static function nilaiLahanUsaha(): array
    {
        return array_values(array_map(
            fn (self $p): string => $p->value,
            array_filter(self::cases(), fn (self $p): bool => $p->lahanUsaha())
        ));
    }
}
