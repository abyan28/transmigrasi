<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Kedudukan seseorang dalam kelompok tani.
 *
 * Nilai `Ketua` dicabut 2026-08-17. Ketua ditetapkan pada tabel `poktan`
 * lewat `is_ketua_transmigran` beserta pasangannya, sebab ketua tidak selalu
 * berasal dari anggota yang terdaftar di sini: banyak poktan diketuai
 * penduduk setempat yang bukan peserta program. Menyediakan `Ketua` pada
 * kedua tempat membuat satu poktan dapat memiliki dua ketua berbeda tanpa
 * ada penjaga yang menyadarinya.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.15.
 */
enum JabatanAnggotaPoktan: string
{
    use PunyaLabel;

    case Sekretaris = 'Sekretaris';
    case Bendahara = 'Bendahara';
    case Anggota = 'Anggota';
}