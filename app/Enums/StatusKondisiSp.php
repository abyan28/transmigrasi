<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;
use App\Enums\Concerns\PunyaWarnaBadge;

/**
 * Status kesiapan layanan dasar sebuah satuan permukiman.
 *
 * PENTING: status ini menilai INFRASTRUKTUR DAN FASILITAS, bukan warganya.
 * Istilah bernada merendahkan seperti "terbelakang" atau "tertinggal"
 * DILARANG dipakai, sebab yang dinilai adalah jalan dan listrik, hal yang
 * justru berada di luar kendali penghuni (agents/rules.md bagian 10c.1).
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.30.
 */
enum StatusKondisiSp: string
{
    use PunyaLabel;
    use PunyaWarnaBadge;

    case Mandiri = 'Mandiri';
    case Berkembang = 'Berkembang';
    case PerluPenanganan = 'Perlu Penanganan';

    public function warna(): string
    {
        return match ($this) {
            self::Mandiri => 'success',
            self::Berkembang => 'warning',
            self::PerluPenanganan => 'error',
        };
    }

    /**
     * Penjelasan status, ditulis agar terbaca sebagai keadaan layanan
     * bukan sebagai penilaian atas penghuninya.
     *
     * @return string Keterangan berbahasa Indonesia
     */
    public function keterangan(): string
    {
        return match ($this) {
            self::Mandiri => 'Seluruh layanan dasar tersedia dan berfungsi baik',
            self::Berkembang => 'Sebagian layanan tersedia, ada yang perlu diperbaiki',
            self::PerluPenanganan => 'Ada layanan dasar yang belum tersedia atau tidak berfungsi',
        };
    }

    /**
     * Menyimpulkan status dari skor dan keadaan parameter primer.
     *
     * ATURAN PRIMER NOL: satu saja parameter primer yang tidak tersedia
     * membuat SP berstatus Perlu Penanganan, berapa pun skor totalnya.
     * Tanpa aturan ini, SP tanpa air bersih tetapi lengkap fasilitas
     * penunjangnya dapat mencapai skor tinggi, dan angka itu menyesatkan
     * (agents/rules.md bagian 10c.4 poin 11).
     *
     * @param  float  $skor  Skor akhir 0 sampai 100
     * @param  bool  $adaPrimerNol  True bila ada parameter primer bernilai nol
     * @return self Status yang berlaku
     */
    public static function dariSkor(float $skor, bool $adaPrimerNol): self
    {
        if ($adaPrimerNol) {
            return self::PerluPenanganan;
        }

        return match (true) {
            $skor >= 80 => self::Mandiri,
            $skor >= 55 => self::Berkembang,
            default => self::PerluPenanganan,
        };
    }
}
