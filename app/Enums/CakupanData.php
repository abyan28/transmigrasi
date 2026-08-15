<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Luas data yang boleh dilihat pemegang sebuah role.
 *
 * Daftar nilai baku ada pada agents/rules.md bagian 5.0b.
 *
 * Cakupan dinyatakan dalam satuan permukiman, sebab itulah batas yang
 * sungguh dipakai di lapangan: seorang petugas memegang seluruh SP di
 * kawasan, atau hanya SP yang ditugaskan padanya. Karena itu antarmuka
 * menyebutnya "Akses ke SP", bukan "cakupan data".
 *
 * Nilai `Milik Sendiri` ditiadakan sejak 13 Agustus 2026. Tidak ada peran
 * di kawasan ini yang hanya boleh melihat barisnya sendiri; setiap petugas
 * bekerja untuk satu SP atau lebih. Menyediakan pilihan yang tidak pernah
 * dipakai hanya membuat Admin menebak maknanya saat menyusun role.
 */
enum CakupanData: string
{
    use PunyaLabel;

    case Semua = 'Semua SP';
    case PerSp = 'Per SP';
}