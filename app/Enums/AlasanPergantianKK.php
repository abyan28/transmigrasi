<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Sebab berpindahnya kedudukan kepala keluarga.
 *
 * BUKAN pengganti `StatusTinggal`. Keduanya menjawab pertanyaan berbeda:
 * `StatusTinggal` menyatakan keadaan terkini sebuah KELUARGA, sedangkan enum
 * ini merekam satu PERISTIWA bertanggal pada riwayat suksesi.
 *
 * Perbedaannya terlihat pada kasus yang paling lazim: ketika kepala keluarga
 * meninggal lalu istrinya menggantikan, keluarganya tetap `Aktif` sebab
 * istrinya masih hidup dan menempati rumah yang sama. Kematian itu hanya
 * terekam di sini.
 *
 * Karena itu `StatusTinggal` TIDAK memiliki nilai `Meninggal` (dicabut
 * 2026-08-22): satu-satunya tempat kematian dicatat adalah enum ini, dan
 * keluarga yang tidak lagi berpenghuni cukup ditandai `Tidak Aktif`.
 *
 * Nilai `Pindah atau Merantau` sengaja tidak dipecah dua: dari sisi pendataan
 * keduanya sama, yaitu kepala keluarga tidak lagi berada di kawasan sementara
 * keluarganya tetap tinggal. Membedakannya menuntut petugas menilai niat
 * kepergian, dan itu tidak dapat diverifikasi.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.36.
 */
enum AlasanPergantianKK: string
{
    use PunyaLabel;

    case Meninggal = 'Meninggal';
    case PindahAtauMerantau = 'Pindah atau Merantau';
    case Cerai = 'Cerai';
    case Lainnya = 'Lainnya';
}
