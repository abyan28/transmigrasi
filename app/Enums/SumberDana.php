<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Asal pembiayaan aset, inventaris, dan bantuan.
 *
 * Daftar nilai baku ada pada agents/rules.md bagian 4b.3.
 */
enum SumberDana: string
{
    use PunyaLabel;

    case Apbn = 'APBN';
    case ApbdProvinsi = 'APBD Provinsi';
    case ApbdKabupaten = 'APBD Kabupaten';
    case DinasTransmigrasiKabupaten = 'Dinas Transmigrasi Kabupaten';
    case DinasPertanianKabupaten = 'Dinas Pertanian Kabupaten';
    case LembagaSwadayaMasyarakat = 'Lembaga Swadaya Masyarakat';
    case Swadaya = 'Swadaya';
    case Lainnya = 'Lainnya';
}