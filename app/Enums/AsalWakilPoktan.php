<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Asal-usul orang yang mewakili sebuah keluarga di kelompok tani.
 *
 * MENGGANTIKAN `poktan.is_ketua_transmigran` yang bertipe boolean. Boolean
 * hanya sanggup membedakan dua keadaan, sedangkan keadaan lapangan ada tiga:
 * kepala keluarga, anggota keluarga transmigran yang bukan kepala keluarga,
 * dan penduduk setempat yang sama sekali bukan peserta program.
 *
 * KEANGGOTAAN POKTAN MELEKAT PADA KELUARGA, BUKAN PADA KEPALA KELUARGA
 * (ditetapkan 2026-08-20 atas keterangan pemilik proyek). Yang terdaftar di
 * poktan adalah orang yang benar-benar menggarap dan menghadiri pertemuan, dan
 * ia tidak selalu kepala keluarga: bila kepala keluarga merantau, istri atau
 * anaknya yang mewakili. Karena itu `anggota_poktan.transmigran_id` menunjuk
 * KELUARGA yang diwakili, sedangkan enum ini menyatakan siapa wakilnya.
 *
 * Anggota poktan hanya boleh memakai dua nilai pertama; seluruh anggota wajib
 * berasal dari keluarga transmigran. Nilai ketiga khusus ketua, sebab banyak
 * poktan diketuai penduduk setempat (agents/rules.md bagian 7a poin 2a).
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.34.
 */
enum AsalWakilPoktan: string
{
    use PunyaLabel;

    case KepalaKeluarga = 'Kepala Keluarga';
    case AnggotaKeluarga = 'Anggota Keluarga';
    case BukanTransmigran = 'Bukan Transmigran';

    /**
     * Menandai wakil yang identitasnya dibaca dari data transmigran.
     *
     * Hanya kepala keluarga yang nama dan NIK-nya dapat dibaca lewat relasi.
     * Kedua nilai lain wajib mengetiknya, sebab sistem memang tidak mendata
     * anggota keluarga satu per satu (`erd.md` 7.4).
     *
     * @return bool True bila identitasnya diturunkan dari relasi
     */
    public function identitasDariRelasi(): bool
    {
        return $this === self::KepalaKeluarga;
    }

    /**
     * Menandai wakil yang masih berasal dari keluarga transmigran.
     *
     * Dipakai memeriksa luas lahan dan koordinat: keduanya dibaca dari bidang
     * milik keluarga yang bersangkutan, sehingga hanya dapat diturunkan bila
     * wakilnya memang mewakili sebuah keluarga terdata.
     *
     * @return bool True bila wakil mewakili keluarga transmigran
     */
    public function dariKeluargaTransmigran(): bool
    {
        return $this !== self::BukanTransmigran;
    }

    /**
     * Nilai yang boleh dipakai anggota poktan, untuk dibaca sisi Blade.
     *
     * `array_values` wajib ada: `array_filter` mempertahankan kunci aslinya,
     * sehingga hasilnya diserialkan `@js()` menjadi OBJEK JavaScript dan
     * pemanggilan `.includes()` di sisi Alpine melempar galat. Jebakan yang
     * sama sudah pernah ditemukan pada `PeruntukanLahan::nilaiLahanUsaha()`.
     *
     * @return array<int, string> Nilai yang sah bagi anggota
     */
    public static function nilaiAnggota(): array
    {
        return array_values(array_map(
            fn (self $a): string => $a->value,
            array_filter(self::cases(), fn (self $a): bool => $a->dariKeluargaTransmigran())
        ));
    }
}
