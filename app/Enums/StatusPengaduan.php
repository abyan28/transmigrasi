<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;
use App\Enums\Concerns\PunyaWarnaBadge;

/**
 * Tahapan penanganan pengaduan.
 *
 * Alur wajib berurutan dan tidak boleh melompat (agents/rules.md bagian 10b).
 */
enum StatusPengaduan: string
{
    use PunyaLabel;
    use PunyaWarnaBadge;

    case MenungguDiterima = 'Menunggu Diterima';
    case Diterima = 'Diterima';
    case Diproses = 'Diproses';
    case Selesai = 'Selesai';

    public function warna(): string
    {
        return match ($this) {
            self::MenungguDiterima => 'gray',
            self::Diterima => 'teal',
            self::Diproses => 'warning',
            self::Selesai => 'success',
        };
    }

    /**
     * Status berikutnya yang diperbolehkan dalam alur penanganan.
     *
     * @return self|null Status lanjutan, atau null bila sudah selesai
     */
    public function berikutnya(): ?self
    {
        return match ($this) {
            self::MenungguDiterima => self::Diterima,
            self::Diterima => self::Diproses,
            self::Diproses => self::Selesai,
            self::Selesai => null,
        };
    }

    /**
     * Memeriksa apakah perpindahan ke status tujuan diperbolehkan.
     *
     * Perpindahan hanya boleh maju satu langkah, agar riwayat penanganan
     * mencerminkan proses yang benar-benar terjadi.
     *
     * @param  self  $tujuan  Status yang dituju
     * @return bool True bila perpindahan sah
     */
    public function bolehPindahKe(self $tujuan): bool
    {
        return $this->berikutnya() === $tujuan;
    }

    /**
     * Menandai pengaduan yang masih dalam penanganan.
     *
     * @return bool True bila belum selesai
     */
    public function masihBerjalan(): bool
    {
        return $this !== self::Selesai;
    }
}
