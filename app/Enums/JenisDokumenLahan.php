<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Jenis surat yang membuktikan status lahan.
 *
 * Di sinilah tempat `HPL` dan `SHM`, sebab keduanya memang nama BERKAS.
 * Keduanya sempat juga menjadi nilai status kepemilikan, dan itu yang keliru:
 * status hak menyatakan hak apa yang dipegang, sedangkan enum ini menyatakan
 * surat apa yang dilampirkan.
 *
 * DIPERSEMPIT MENJADI DUA NILAI 2026-08-20 atas keputusan pemilik proyek.
 * Sebelumnya memuat enam nilai: ditambah `Surat Keterangan Pembagian Tanah`,
 * `SKT`, `Surat Keterangan Desa`, dan `Lainnya`. Keempatnya dicabut sebab
 * pendataan di Kobalima Timur hanya mengenal kedua berkas ini.
 *
 * Sifat sertifikasi yang berlapis TETAP terwakili, dan itulah sebabnya tabel
 * `dokumen_lahan` tetap terpisah: HPL adalah alas hak kawasan yang terbit
 * lebih dulu, sedangkan SHM menyusul bertahun kemudian saat bidangnya
 * disertifikatkan atas nama transmigran. Satu bidang tetap melewati lebih
 * dari satu dokumen (agents/notes.md 1c.2 pelanggaran kedua).
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.14.
 */
enum JenisDokumenLahan: string
{
    use PunyaLabel;

    // Berkas alas hak kawasan, terbit lebih dulu. Dilampirkan sebagai rujukan
    // asal tanah, bukan sebagai bukti hak transmigran atas bidangnya.
    case Hpl = 'HPL';
    case Shm = 'SHM';
}
