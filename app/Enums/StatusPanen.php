<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;
use App\Enums\Concerns\PunyaWarnaBadge;

/**
 * Sejauh mana sebuah penanaman sudah dipanen.
 *
 * DITURUNKAN, TIDAK DISIMPAN. Nilainya dihitung dari sisa luas yang belum
 * dipanen, mengikuti identitas yang sudah berlaku sejak 2026-08-22
 * (agents/rules.md bagian 9 poin 9 dan 10):
 *
 *     realisasi_panen + puso + belum_dipanen = penanaman.realisasi_tanam
 *
 * Menyimpannya sebagai kolom berarti angka itu basi begitu satu baris panen
 * disunting atau dihapus, dan kebasian itu tidak pernah memerahkan apa pun.
 * Alasannya sama persis dengan `belum_dipanen` yang juga tidak disimpan.
 *
 * PUSO BUKAN STATUS KEEMPAT (ditetapkan pemilik proyek 2026-08-24). Penanaman
 * yang seluruhnya gagal panen menyisakan nol, sehingga berstatus Selesai
 * Dipanen sama seperti yang berhasil penuh. Pembedanya kolom Puso tersendiri,
 * persis cara laporan Polri MT.II 2025 membedakan keduanya: di sana Realisasi
 * Panen dan Puso adalah dua kolom bersebelahan, bukan dua nilai pada satu
 * kolom status.
 */
enum StatusPanen: string
{
    use PunyaLabel;
    use PunyaWarnaBadge;

    case BelumDipanen = 'Belum Dipanen';
    case DipanenSebagian = 'Dipanen Sebagian';
    case SelesaiDipanen = 'Selesai Dipanen';

    public function warna(): string
    {
        return match ($this) {
            self::BelumDipanen => 'gray',
            self::DipanenSebagian => 'warning',
            self::SelesaiDipanen => 'success',
        };
    }

    /**
     * Status yang dapat dipakai menyaring daftar HASIL PANEN.
     *
     * `BelumDipanen` sengaja tidak ikut: menurut definisinya, penanaman yang
     * belum dipanen tidak memiliki satu pun baris panen untuk ditampilkan,
     * sehingga pilihan itu selalu menghasilkan tabel kosong. Merendernya
     * berarti memasang kontrol mati yang dilarang `ui-spec.md` R-26.
     *
     * Penemuan "siapa yang belum panen" adalah tugas halaman Penanaman, yang
     * memang menampilkan ketiga status.
     *
     * @return array<int, self> Status yang sah sebagai penyaring panen
     */
    public static function penyaringPanen(): array
    {
        return [self::DipanenSebagian, self::SelesaiDipanen];
    }
}
