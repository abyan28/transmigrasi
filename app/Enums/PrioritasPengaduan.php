<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;
use App\Enums\Concerns\PunyaWarnaBadge;

/**
 * Tingkat kesegeraan penanganan pengaduan.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.24.
 */
enum PrioritasPengaduan: string
{
    use PunyaLabel;
    use PunyaWarnaBadge;

    case Rendah = 'Rendah';
    case Sedang = 'Sedang';
    case Tinggi = 'Tinggi';
    case Mendesak = 'Mendesak';

    public function warna(): string
    {
        return match ($this) {
            self::Rendah => 'gray',
            self::Sedang => 'teal',
            self::Tinggi => 'warning',
            self::Mendesak => 'error',
        };
    }

    /**
     * Menyimpulkan prioritas awal dari kategori pengaduan.
     *
     * Warga tidak diminta menilai kegentingan laporannya sendiri. Selain
     * karena ia tidak mengetahui skala prioritas dinas, meminta warga
     * menilainya membuat hampir seluruh laporan ditandai mendesak, sehingga
     * penandanya kehilangan makna.
     *
     * Nilai di sini hanyalah PERKIRAAN AWAL agar laporan tidak menumpuk tanpa
     * urutan sebelum sempat ditinjau. Petugas yang memutuskan prioritas
     * sebenarnya saat meninjau, dan revisinya tercatat pada audit log
     * (agents/rules.md bagian 10b).
     *
     * Mengikuti pola BidangPengaduan::dariKategori() yang sudah dipakai untuk
     * menentukan dinas penanganan.
     *
     * @param  KategoriPengaduan  $kategori  Kategori yang dipilih pelapor
     * @return self Prioritas awal yang diperkirakan
     */
    public static function dariKategori(KategoriPengaduan $kategori): self
    {
        return match ($kategori) {
            // Menyangkut keselamatan jiwa, tidak dapat menunggu antrean.
            KategoriPengaduan::Bencana => self::Mendesak,

            // Menyangkut layanan dasar dan tempat tinggal: air, listrik,
            // jalan, dan rumah. Terhambat berarti kehidupan sehari-hari
            // ikut terhenti.
            KategoriPengaduan::Infrastruktur,
            KategoriPengaduan::Rumah => self::Tinggi,

            // Menyangkut penghidupan: lahan garapan, alat, dan hasil panen.
            // Mendesak bagi pemiliknya, tetapi masih dapat dijadwalkan.
            KategoriPengaduan::LahanUsaha,
            KategoriPengaduan::Alsintan,
            KategoriPengaduan::ProduksiPanen => self::Sedang,

            // Sisanya, termasuk Lainnya, ditinjau menurut urutan masuk.
            default => self::Rendah,
        };
    }
}