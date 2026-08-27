<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Agama yang dianut, untuk kepala keluarga maupun anggota keluarganya.
 *
 * Enam agama yang dilayani pencatatan sipil (Dukcapil) dan tercetak pada
 * KTP serta kartu keluarga. "Penghayat Kepercayaan terhadap Tuhan YME"
 * sengaja tidak diikutkan pada tahap ini atas keputusan pemilik proyek
 * (2026-08-28); bila dinas memerlukannya, cukup ditambahkan satu case.
 *
 * Ditambahkan 2026-08-28 bersama pendataan anggota keluarga (Rombongan B).
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.
 */
enum Agama: string
{
    use PunyaLabel;

    case Islam = 'Islam';
    case Kristen = 'Kristen';
    case Katolik = 'Katolik';
    case Hindu = 'Hindu';
    case Buddha = 'Buddha';
    case Konghucu = 'Konghucu';
}
