<?php

namespace App\Support;

use App\Enums\JenisDaftarPilihan;
use App\Models\DaftarPilihan;
use App\Models\Penanaman;
use App\Models\Saprotan;
use App\Models\SaprotanDistribusi;
use App\Models\Scopes\CakupanDataSp;
use Illuminate\Validation\ValidationException;

class OperasiPenanaman
{
    public static function distribusiTerkunci(int $id): SaprotanDistribusi
    {
        $distribusi = SaprotanDistribusi::query()->whereHas('poktan')->whereHas('saprotan')->findOrFail($id);
        CakupanDataSp::pastikanDapatDitulis($distribusi);
        $saprotan = Saprotan::query()->with('satuan')->lockForUpdate()->findOrFail($distribusi->saprotan_id);
        $distribusi = SaprotanDistribusi::query()->lockForUpdate()->findOrFail($id);
        $distribusi->setRelation('saprotan', $saprotan);
        $distribusi->setRelation('poktan', $distribusi->poktan()->lockForUpdate()->firstOrFail());

        return $distribusi;
    }

    public static function buat(array $data, SaprotanDistribusi $distribusi): Penanaman
    {
        if (! DaftarPilihan::memilikiPerilaku(JenisDaftarPilihan::JenisSaprotan, $distribusi->saprotan->jenis, 'benih')) {
            throw ValidationException::withMessages(['saprotan_distribusi_id' => 'Penyaluran yang dipilih bukan benih.']);
        }
        $sisa = $distribusi->sisaBenih();
        if (round((float) $data['volume_benih'] - $sisa, 3) > 0) {
            throw ValidationException::withMessages(['volume_benih' => 'Melebihi sisa jatah benih kelompok ini.']);
        }
        $tersedia = RekapPoktan::lahanTersedia($distribusi->poktan);
        if (round((float) $data['realisasi_tanam'] - $tersedia, 2) > 0) {
            throw ValidationException::withMessages(['realisasi_tanam' => 'Melebihi lahan kelompok yang belum ditanami.']);
        }

        return Penanaman::create([
            'kode_penanaman' => $data['kode_penanaman'], 'poktan_id' => $distribusi->poktan_id,
            'komoditas_id' => $distribusi->saprotan->komoditas_id,
            'saprotan_distribusi_id' => $distribusi->id_saprotan_distribusi,
            'volume_benih' => $data['volume_benih'], 'realisasi_tanam' => $data['realisasi_tanam'],
            'periode_tanam' => $data['periode_tanam'], 'keterangan' => $data['keterangan'] ?? null,
        ]);
    }
}
