<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Hubungan seseorang terhadap kepala keluarga yang terdata.
 *
 * Dipakai ketika yang mewakili keluarga di poktan bukan kepala keluarganya
 * sendiri. Sistem tidak mendata anggota keluarga satu per satu (`erd.md` 7.4),
 * sehingga hubungan ini diketik petugas, bukan dibaca dari relasi.
 *
 * Sengaja kasar dan tidak dirinci sampai "anak kedua" atau "menantu laki-laki".
 * Yang perlu diketahui hanyalah kedudukan wakil terhadap kepala keluarga, agar
 * petugas dapat menelusuri bila namanya tidak dikenali. Merincinya lebih jauh
 * menuntut pendataan keluarga yang memang di luar lingkup PRD.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.35.
 */
enum HubunganKeluarga: string
{
    use PunyaLabel;

    case IstriSuami = 'Istri/Suami';
    case Anak = 'Anak';
    case Menantu = 'Menantu';
    case Lainnya = 'Lainnya';
}
