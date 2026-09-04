<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Jenjang pendidikan formal terakhir yang ditamatkan.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.7.
 */
enum PendidikanTerakhir: string
{
    use PunyaLabel;

    case TidakSekolah = 'Tidak Sekolah';
    case Sd = 'SD';
    case Smp = 'SMP';
    case SmaSmk = 'SMA/SMK';
    case Diploma = 'Diploma';
    case S1 = 'S1';
    case S2 = 'S2';
    case S3 = 'S3';
}
