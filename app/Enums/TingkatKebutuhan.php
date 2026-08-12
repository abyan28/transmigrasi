<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Tingkat kebutuhan sebuah parameter penilaian kondisi SP.
 *
 * Dikelompokkan menurut satu pertanyaan: tanpa parameter ini, apakah tempat
 * tersebut masih layak dihuni (agents/rules.md bagian 10c.2).
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.29.
 */
enum TingkatKebutuhan: string
{
    use PunyaLabel;

    case Primer = 'Primer';
    case Sekunder = 'Sekunder';
    case Tersier = 'Tersier';

    /**
     * Bobot bawaan tiap tingkat.
     *
     * PENTING: nilai ini hanya bawaan saat penanaman data awal. Bobot yang
     * berlaku dibaca dari tabel `parameter_penilaian_sp`, karena Admin dapat
     * menyesuaikannya lewat antarmuka (agents/rules.md bagian 10c.3 poin 6).
     *
     * Jarak 5, 3, 1 dipilih agar kegagalan pada layanan dasar tidak tertutupi
     * oleh kelengkapan fasilitas penunjang.
     *
     * @return int Bobot bawaan
     */
    public function bobotBawaan(): int
    {
        return match ($this) {
            self::Primer => 5,
            self::Sekunder => 3,
            self::Tersier => 1,
        };
    }

    /**
     * Penjelasan singkat makna tingkat ini, untuk ditampilkan di antarmuka.
     *
     * @return string Keterangan berbahasa Indonesia
     */
    public function keterangan(): string
    {
        return match ($this) {
            self::Primer => 'Tanpa ini tempat tidak layak dihuni',
            self::Sekunder => 'Masih dapat dihuni, tetapi tidak berkembang',
            self::Tersier => 'Penunjang produktivitas dan kehidupan sosial',
        };
    }
}
