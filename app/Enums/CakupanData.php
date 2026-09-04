<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Luas data yang boleh dilihat pemegang sebuah role.
 *
 * Daftar nilai baku ada pada agents/rules.md bagian 5.0b.
 */
enum CakupanData: string
{
    use PunyaLabel;

    case Semua = 'Semua';
    case PerSp = 'Per SP';

    /*
     * Menyaring pengaduan menurut bidang dinas pemegang role. Dipakai Dinas
     * Pertanian agar daftarnya tidak dibanjiri laporan ketransmigrasian.
     *
     * Dinas Transmigrasi sengaja TIDAK memakai nilai ini melainkan `Semua`,
     * sebab sistem ini milik Dinas Transmigrasi dan mereka pula yang menyaring
     * laporan yang bidangnya belum ditetapkan (agents/rules.md 5.0b).
     */
    case PerBidang = 'Per Bidang';
}
