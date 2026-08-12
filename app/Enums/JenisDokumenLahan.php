<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Jenis surat yang membuktikan status lahan.
 *
 * Daftar nilai baku ada pada agents/rules.md bagian 7.6.
 */
enum JenisDokumenLahan: string
{
    use PunyaLabel;

    case Hpl = 'HPL';
    case Shm = 'SHM';
    case Skt = 'SKT';
    case SuratKeteranganDesa = 'Surat Keterangan Desa';
    case Lainnya = 'Lainnya';
}