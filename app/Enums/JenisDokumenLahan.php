<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Jenis surat yang membuktikan status lahan.
 *
 * Di sinilah tempat `HPL` dan `SHM`, sebab keduanya memang nama BERKAS.
 * Keduanya sempat juga menjadi nilai status kepemilikan, dan itu yang keliru:
 * status hak menyatakan hak apa yang dipegang, sedangkan enum ini menyatakan
 * surat apa yang dilampirkan. Lihat `StatusHakLahan` untuk uraiannya.
 *
 * `Surat Keterangan Pembagian Tanah` ditambahkan 2026-08-18: inilah berkas
 * yang menjadi sandaran legalitas penggunaan tanah sebelum sertifikat Hak
 * Milik terbit, dan tanpa nilai ini bidang yang belum bersertifikat tidak
 * memiliki jenis dokumen yang tepat untuk dilampirkan.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.14.
 */
enum JenisDokumenLahan: string
{
    use PunyaLabel;

    case Shm = 'SHM';
    case SuratKeteranganPembagianTanah = 'Surat Keterangan Pembagian Tanah';
    case Skt = 'SKT';
    case SuratKeteranganDesa = 'Surat Keterangan Desa';
    // Berkas alas hak kawasan. Dilampirkan sebagai rujukan asal tanah, bukan
    // sebagai bukti hak transmigran atas bidangnya.
    case Hpl = 'HPL';
    case Lainnya = 'Lainnya';
}