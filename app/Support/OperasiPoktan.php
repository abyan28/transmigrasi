<?php

namespace App\Support;

use App\Enums\AsalWakilPoktan;
use App\Enums\StatusKeaktifanAnggota;
use App\Models\AnggotaKeluarga;
use App\Models\AnggotaPoktan;
use App\Models\Poktan;
use App\Models\Scopes\CakupanDataSp;
use App\Models\Transmigran;
use Illuminate\Validation\ValidationException;

class OperasiPoktan
{
    /** @param array<int, array<string, mixed>> $anggota */
    public static function buat(array $data, array $anggota): Poktan
    {
        $spId = (int) $data['satuan_permukiman_id'];
        CakupanDataSp::pastikanDapatDitulis($spId);
        $asal = AsalWakilPoktan::from($data['asal_ketua']);

        if ($asal->dariKeluargaTransmigran()) {
            $ketua = Transmigran::find((int) $data['ketua_transmigran_id']);
            if ($ketua === null || $ketua->satuan_permukiman_id !== $spId) {
                throw ValidationException::withMessages(['ketua_transmigran_id' => 'Keluarga ketua harus berasal dari satuan permukiman kelompok tani.']);
            }
            if ($asal === AsalWakilPoktan::AnggotaKeluarga) {
                $orang = AnggotaKeluarga::find((int) $data['ketua_anggota_keluarga_id']);
                if ($orang === null || $orang->transmigran_id !== $ketua->id_transmigran) {
                    throw ValidationException::withMessages(['ketua_anggota_keluarga_id' => 'Anggota keluarga yang dipilih bukan bagian dari keluarga ketua.']);
                }
            }
        }

        foreach ($anggota as $i => &$baris) {
            $baris += [
                'asal_wakil' => AsalWakilPoktan::KepalaKeluarga->value,
                'status' => StatusKeaktifanAnggota::Aktif->value,
                'tanggal_masuk' => now()->toDateString(),
            ];
            AnggotaPoktan::pastikanSah($spId, $baris, awalanGalat: "anggota.{$i}.");
        }
        unset($baris);

        $poktan = Poktan::create($data);
        foreach ($anggota as $baris) {
            $poktan->anggota()->create($baris);
        }

        return $poktan;
    }
}
