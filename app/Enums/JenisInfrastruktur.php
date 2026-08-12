<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Jenis aset infrastruktur pertanian kawasan.
 *
 * Daftar nilai baku ada pada agents/rules.md bagian 10 poin 2.
 *
 * Jalan penghubung dan jalan produksi sengaja dibedakan: yang pertama
 * menentukan akses masuk ke kawasan SP termasuk bagi kendaraan darurat,
 * yang kedua menentukan pengangkutan hasil dari lahan usaha. Keduanya
 * berbeda dampak dan berbeda bobot pada penilaian kondisi SP (bagian 10c).
 */
enum JenisInfrastruktur: string
{
    use PunyaLabel;

    case Air = 'Air';
    case Sanitasi = 'Sanitasi';
    case Irigasi = 'Irigasi';
    case Listrik = 'Listrik';
    case JalanPenghubung = 'Jalan Penghubung';
    case JalanProduksi = 'Jalan Produksi';
    case Telekomunikasi = 'Telekomunikasi';
    case Gudang = 'Gudang';
    case PasarKios = 'Pasar atau Kios Saprotan';
    case Lainnya = 'Lainnya';
}