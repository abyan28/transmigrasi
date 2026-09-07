<?php

namespace App\Support;

use App\Enums\JenisDaftarPilihan;
use App\Models\DaftarPilihan;
use App\Models\Saprotan;
use Illuminate\Validation\ValidationException;

class OperasiSaprotan
{
    /** @param array<int, array<string, mixed>> $distribusi */
    public static function buat(array $data, array $distribusi): Saprotan
    {
        if (! DaftarPilihan::memilikiPerilaku(JenisDaftarPilihan::JenisSaprotan, $data['jenis'], 'benih')) {
            $data['komoditas_id'] = $data['varietas'] = null;
        }

        $total = array_sum(array_column($distribusi, 'jumlah'));
        if (round($total - (float) $data['jumlah_total'], 3) > 0) {
            throw ValidationException::withMessages(['distribusi' => 'Jumlah seluruh distribusi melebihi jumlah total pengadaan.']);
        }

        $saprotan = Saprotan::create($data);
        foreach ($distribusi as $baris) {
            $saprotan->distribusi()->create($baris);
        }

        return $saprotan;
    }
}
