<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Status sertifikat hak atas tanah satu KELUARGA.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 6.1 kolom
 * `status_sertifikat`. SHM meliputi SELURUH lahan satu KK (pekarangan maupun
 * lahan usaha), sehingga statusnya melekat pada keluarga, bukan pada tiap
 * bidang (agents/rules.md 7.6c).
 *
 * `Belum Didata` memisahkan keluarga yang dipastikan belum bersertifikat dari
 * yang belum pernah ditanyakan petugas. Tanpa nilai ketiga itu, laporan ke
 * dinas mencampur keduanya - kekeliruan yang sama dengan menghitung "belum
 * bersertifikat" dari ketiadaan unggahan.
 */
enum StatusSertifikat: string
{
    use PunyaLabel;

    case Sudah = 'Sudah';
    case Belum = 'Belum';
    case BelumDidata = 'Belum Didata';
}
