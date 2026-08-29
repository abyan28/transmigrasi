<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;
use App\Enums\Concerns\PunyaWarnaBadge;

/**
 * Keadaan seorang anggota keluarga SELAIN kepala keluarga.
 *
 * Ditambahkan 2026-08-29 (Putaran 6). Membalik sebagian agents/rules.md 9c
 * yang menyuruh menghapus baris anggota yang meninggal atau pindah: barisnya
 * kini disimpan dengan status ini beserta tanggal dan keterangan peristiwa,
 * supaya Laporan Monografi SP dapat menghitung mutasi penduduk. Ini BUKAN
 * pendataan riwayat lengkap, hanya penanda satu peristiwa terakhir.
 *
 * BUKAN pengganti dua enum yang sudah ada:
 * - `StatusTinggal` menyatakan keadaan sebuah KELUARGA, bukan orang. Kematian
 *   kepala keluarga tidak membubarkan barisnya (kedudukan berpindah ke ahli
 *   waris), maka `StatusTinggal` tidak punya nilai `Meninggal`.
 * - `AlasanPergantianKK` merekam PERISTIWA suksesi kepala keluarga pada
 *   riwayat. Kepala keluarga tidak memakai enum ini; peristiwanya selalu
 *   lewat alur ganti kepala keluarga.
 *
 * Enum ini hanya untuk anggota non-kepala, yang tidak punya rumah, lahan,
 * atau keanggotaan poktan sehingga barisnya aman ditandai per orang.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.
 */
enum StatusAnggotaKeluarga: string
{
    use PunyaLabel;
    use PunyaWarnaBadge;

    case Aktif = 'Aktif';
    case Meninggal = 'Meninggal';
    case Pindah = 'Pindah';

    public function warna(): string
    {
        return match ($this) {
            self::Aktif => 'success',
            self::Meninggal => 'gray',
            self::Pindah => 'warning',
        };
    }

    /**
     * Pilihan untuk form pencatatan peristiwa: tanpa `Aktif`, sebab peristiwa
     * yang dicatat petugas selalu memindahkan anggota keluar dari hitungan.
     *
     * @return array<string, string> Peta nilai ke label
     */
    public static function opsiPeristiwa(): array
    {
        return [
            self::Meninggal->value => self::Meninggal->label(),
            self::Pindah->value => self::Pindah->label(),
        ];
    }
}
