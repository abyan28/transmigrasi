<?php

namespace App\Support;

use App\Models\Lahan;
use App\Models\SatuanPermukiman;
use App\Models\Scopes\CakupanDataSp;
use App\Models\Transmigran;
use Illuminate\Support\Str;

class OperasiLahan
{
    public static function buat(array $data): Lahan
    {
        $pemilik = Transmigran::withoutGlobalScope(CakupanDataSp::class)
            ->findOrFail((int) $data['transmigran_id']);
        CakupanDataSp::pastikanDapatDitulis($pemilik);
        CakupanDataSp::pastikanDapatDitulis(SatuanPermukiman::findOrFail($pemilik->satuan_permukiman_id));

        $statusSertifikat = $data['status_sertifikat'];
        unset($data['status_sertifikat'], $data['shm']);

        $kering = $data['luas_kering'] ?? null;
        $basah = $data['luas_basah'] ?? null;
        if (($kering === null || $kering === '') && ($basah === null || $basah === '')) {
            $data['luas_kering'] = $data['luas_basah'] = $data['luas_usaha'] = null;
        } else {
            $data['luas_kering'] = (float) ($kering ?: 0);
            $data['luas_basah'] = (float) ($basah ?: 0);
            $data['luas_usaha'] = $data['luas_kering'] + $data['luas_basah'];
        }

        $data['satuan_permukiman_id'] = $pemilik->satuan_permukiman_id;
        $lahan = Lahan::create($data + ['uuid' => (string) Str::uuid()]);
        $pemilik->update(['status_sertifikat' => $statusSertifikat]);

        return $lahan;
    }
}
