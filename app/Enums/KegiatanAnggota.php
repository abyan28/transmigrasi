<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Kegiatan utama seorang anggota keluarga: bersekolah, bekerja, atau tidak.
 *
 * Menggantikan pilihan "Pendidikan/Kerja" bercabang pada form anggota
 * keluarga (Rombongan B, 2026-08-28). Cabang isian yang menyusul:
 *
 * - `BelumSekolah` : balita, tidak ada isian tambahan.
 * - `MasihSekolah`  : `pendidikan_terakhir` diisi sebagai jenjang yang sedang
 *                     ditempuh.
 * - `Bekerja`       : `pendidikan_terakhir` + `pekerjaan` + `pendapatan_per_bulan`.
 * - `TidakBekerja`  : `pendidikan_terakhir` saja.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.
 */
enum KegiatanAnggota: string
{
    use PunyaLabel;

    case BelumSekolah = 'Belum Sekolah';
    case MasihSekolah = 'Masih Sekolah';
    case Bekerja = 'Bekerja';
    case TidakBekerja = 'Tidak Bekerja';
}
