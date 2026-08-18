<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Status hak atas tanah pada sebuah bidang lahan transmigran.
 *
 * Menggantikan `StatusKepemilikanLahan` yang memuat nilai `HPL` dan `SHM`.
 * Keduanya keliru sebagai status hak perorangan:
 *
 * - **HPL** adalah Hak Pengelolaan, dipegang instansi atas tanah kawasan.
 *   Transmigran tidak pernah menjadi pemegang HPL; HPL adalah asal hak
 *   kawasan, bukan hak individu. Menuliskannya sebagai status kepemilikan
 *   membuat sistem menyatakan seorang warga "memiliki lahan berstatus HPL",
 *   yang tidak mungkin terjadi.
 * - **SHM** adalah nama sertifikatnya, bukan nama haknya. Haknya bernama
 *   Hak Milik; SHM adalah berkas yang membuktikannya, dan tempatnya di
 *   `dokumen_lahan.jenis_dokumen`.
 *
 * Rantai yang sebenarnya: tanah kawasan berstatus Hak Pengelolaan, lalu
 * bidang-bidangnya dibagikan kepada transmigran dengan status Hak Milik.
 * Sebelum sertifikatnya terbit, bidang itu berstatus belum bersertifikat dan
 * legalitas penggunaannya bersandar pada surat keterangan pembagian tanah.
 *
 * Istilah pada enum ini masih menunggu konfirmasi dinas (`notes.md`), sebab
 * berkas penetapan di tiap daerah dapat memakai sebutan yang berbeda.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.13.
 */
enum StatusHakLahan: string
{
    use PunyaLabel;

    case BelumBersertifikat = 'Belum Bersertifikat';
    case HakMilik = 'Hak Milik';
    case HakMilikBersama = 'Hak Milik Bersama';
    case HakPakai = 'Hak Pakai';
    case Sewa = 'Sewa';
    case Garapan = 'Garapan';
}
