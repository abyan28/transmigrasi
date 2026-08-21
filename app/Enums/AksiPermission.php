<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Jenis tindakan yang dapat diizinkan pada sebuah modul.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.26.
 *
 * Nilai `export` dicabut 2026-08-17. Ekspor adalah cara lain membaca data
 * yang sudah boleh dilihat, bukan kewenangan baru, sehingga ia kini mengikuti
 * `lihat`. Memisahkannya memaksa Admin menyusun role dua kali untuk satu
 * maksud, dan pada praktiknya tidak pernah dipakai membedakan role: 24 sel
 * pada matriks `rules.md` 5.1 memberi `lihat` tanpa `export` tanpa alasan
 * yang dapat dijelaskan. Pembatasan sebaran data ditangani cakupan data
 * (`rules.md` 5.2), bukan dimensi izin tersendiri.
 */
enum AksiPermission: string
{
    use PunyaLabel;

    case Lihat = 'lihat';
    case Tambah = 'tambah';
    case Ubah = 'ubah';
    case Hapus = 'hapus';

    /**
     * Label kolom pada matriks kewenangan, dipetakan dari enum ini.
     *
     * Ditambahkan 2026-08-20 sebab `form-role.blade.php` menyalin daftar ini
     * dengan tangan, dan salinannya masih memuat `export` selama tiga hari
     * setelah kewenangan itu dicabut dari enum. Kolomnya selalu kosong, tetapi
     * tidak ada yang memerah: salinan tulis-tangan tidak tahu sumbernya berubah.
     *
     * @return array<string, string>
     */
    public static function opsi(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $bawa, self $aksi) => $bawa + [$aksi->value => ucfirst($aksi->value)],
            [],
        );
    }
}
