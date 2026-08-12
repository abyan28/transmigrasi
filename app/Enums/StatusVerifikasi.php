<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;
use App\Enums\Concerns\PunyaWarnaBadge;

/**
 * Status pemeriksaan kebenaran data oleh petugas berwenang.
 *
 * Verifikasi TIDAK mengubah isi data, hanya menandai bahwa baris tersebut
 * sudah diperiksa beserta siapa dan kapan (agents/rules.md bagian 5.2).
 *
 * Nilai `BelumDiverifikasi` diwakili oleh ketiadaan baris pada tabel
 * `verifikasi`, bukan disimpan sebagai nilai.
 */
enum StatusVerifikasi: string
{
    use PunyaLabel;
    use PunyaWarnaBadge;

    case BelumDiverifikasi = 'Belum Diverifikasi';
    case Terverifikasi = 'Terverifikasi';
    case Ditolak = 'Ditolak';

    public function warna(): string
    {
        return match ($this) {
            self::BelumDiverifikasi => 'gray',
            self::Terverifikasi => 'success',
            self::Ditolak => 'error',
        };
    }

    /**
     * Menentukan apakah data perlu ditindaklanjuti operator.
     *
     * @return bool True bila status menuntut perbaikan atau pemeriksaan
     */
    public function perluTindakan(): bool
    {
        return $this !== self::Terverifikasi;
    }
}
