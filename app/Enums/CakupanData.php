<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Luas data yang boleh dilihat pemegang sebuah role.
 *
 * Daftar nilai baku ada pada agents/rules.md bagian 5.0b.
 */
enum CakupanData: string
{
    use PunyaLabel;

    case Semua = 'Semua';
    case PerSp = 'Per SP';
}