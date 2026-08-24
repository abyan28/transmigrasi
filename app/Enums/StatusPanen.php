<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;
use App\Enums\Concerns\PunyaWarnaBadge;

/**
 * Apakah sebuah penanaman sudah dipanen.
 *
 * DITURUNKAN, TIDAK DISIMPAN. Nilainya dibaca dari ada atau tidaknya catatan
 * panen yang menaut ke penanaman itu. Menyimpannya sebagai kolom berarti nilai
 * itu menjadi salah begitu satu baris panen dihapus, dan kesalahan itu tidak
 * pernah memerahkan apa pun.
 *
 * DUA NILAI, bukan tiga (diubah 2026-08-24). Nilai `Dipanen Sebagian` dicabut
 * bersama seluruh konsep panen bertahap, atas keterangan pemilik proyek:
 *
 *     Satu penanaman hanya bisa satu panen. Realisasi tanam 2 ha berarti
 *     realisasi panen ditambah puso juga tepat 2 ha. Tidak bisa dipanen
 *     1,5 ha lalu menyusul 0,5 ha dari penanaman yang sama.
 *
 * Keadaan "dipanen sebagian" karena itu tidak lagi mungkin ada. Sebelumnya
 * ia dapat muncul karena form membiarkan luas panen kurang dari luas tanam
 * tanpa menagih sisanya, dan sisa itu lalu mengambang tanpa batas waktu.
 *
 * Bentuk ini sejalan dengan laporan lapangan yang menjadi rujukan seluruh
 * perombakan menu Pertanian: kolomnya hanya Realisasi Tanam, Realisasi Panen,
 * dan Puso - tanpa kolom untuk luas yang belum dipanen.
 */
enum StatusPanen: string
{
    use PunyaLabel;
    use PunyaWarnaBadge;

    case BelumDipanen = 'Belum Dipanen';
    case SelesaiDipanen = 'Selesai Dipanen';

    public function warna(): string
    {
        return match ($this) {
            self::BelumDipanen => 'gray',
            self::SelesaiDipanen => 'success',
        };
    }
}
