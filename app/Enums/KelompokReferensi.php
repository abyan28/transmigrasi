<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Pengelompokan daftar referensi pada halaman indeks data master.
 *
 * ADA KARENA JUMLAHNYA, bukan karena kerapian. Ketika daftar referensi masih
 * sembilan, seluruhnya muat sebagai tab dalam satu baris. Setelah menjadi
 * empat belas, bar tab mencapai 2309px pada ruang 705px: hanya empat tab yang
 * terlihat dan sepuluh sisanya tersembunyi di balik gulir mendatar, yang
 * paling sering tidak disadari orang.
 *
 * Kelompoknya mengikuti MODUL YANG MEMAKAINYA, bukan kemiripan bentuk. Petugas
 * yang hendak menambah jenis fasilitas sedang memikirkan aset satuan
 * permukiman, bukan memikirkan "daftar yang isinya sedikit". Pengelompokan
 * yang tidak sejalan dengan cara orang mencarinya hanya memindahkan
 * kebingungan, tidak menghilangkannya.
 *
 * Dibuat sebagai enum, bukan string lepas, agar kelompok yang salah ketik
 * ketahuan saat itu juga alih-alih memunculkan kartu yatim yang tidak masuk
 * kelompok mana pun.
 */
enum KelompokReferensi: string
{
    use PunyaLabel;

    case AsetInfrastruktur = 'aset_infrastruktur';
    case RumahLahan = 'rumah_lahan';
    case Pertanian = 'pertanian';
    case Pengaduan = 'pengaduan';

    /**
     * Judul kelompok pada halaman indeks.
     *
     * @return string Label berbahasa Indonesia
     */
    public function label(): string
    {
        return match ($this) {
            self::AsetInfrastruktur => 'Aset & Infrastruktur',
            self::RumahLahan => 'Rumah & Lahan',
            self::Pertanian => 'Pertanian',
            self::Pengaduan => 'Pengaduan',
        };
    }

    /**
     * Keterangan singkat, menjelaskan modul mana yang memakai kelompok ini.
     *
     * Ditulis dari sisi pemakainya, sebab petugas mencari daftar lewat modul
     * tempat ia melihat dropdownnya, bukan lewat nama daftarnya.
     *
     * @return string Keterangan satu kalimat
     */
    public function keterangan(): string
    {
        return match ($this) {
            self::AsetInfrastruktur => 'Dipakai pada inventaris, fasilitas, infrastruktur, alsintan, dan saprotan.',
            self::RumahLahan => 'Dipakai pada data rumah dan data lahan.',
            self::Pertanian => 'Dipakai pada komoditas, hasil panen, dan kelompok tani.',
            self::Pengaduan => 'Dipakai pada pencatatan dan penanganan pengaduan warga.',
        };
    }

    /**
     * Daftar referensi yang termasuk kelompok ini, terurut.
     *
     * @return array<int, JenisReferensi> Jenis pada kelompok ini
     */
    public function jenis(): array
    {
        return array_values(array_filter(
            JenisReferensi::cases(),
            fn (JenisReferensi $j) => $j->kelompok() === $this
        ));
    }
}
