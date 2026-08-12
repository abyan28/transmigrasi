<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Jenis fasilitas tetap milik satuan permukiman.
 *
 * Enum ini diperlukan agar penilaian kondisi SP dapat dihitung otomatis.
 * Tanpa enum, teks bebas membuat "SEKOLAH DASAR" dan "SD Negeri 1" tidak
 * terbaca sebagai hal yang sama.
 *
 * Nama spesifik sebagaimana dikenal warga tetap dicatat pada kolom
 * `nama_fasilitas` (agents/data-dictionary.md bagian 4.2).
 */
enum JenisFasilitas: string
{
    use PunyaLabel;

    case Kesehatan = 'Kesehatan';
    case PendidikanDasar = 'Pendidikan Dasar';
    case PendidikanLanjutan = 'Pendidikan Lanjutan';
    case Ibadah = 'Ibadah';
    case BalaiPertemuan = 'Balai Pertemuan';
    case PasarKios = 'Pasar atau Kios';
    case Olahraga = 'Olahraga';
    case Keamanan = 'Keamanan';
    case Lainnya = 'Lainnya';
}
