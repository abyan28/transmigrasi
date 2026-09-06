<?php

namespace App\Support;

use App\Enums\StatusHunian;
use App\Models\Rumah;
use App\Models\SatuanPermukiman;
use App\Models\Scopes\CakupanDataSp;
use App\Models\Transmigran;
use Illuminate\Support\Str;

class OperasiRumah
{
    public static function buat(array $data): Rumah
    {
        $tahunMulai = isset($data['tahun_mulai_menghuni']) ? (int) $data['tahun_mulai_menghuni'] : null;
        unset($data['tahun_mulai_menghuni'], $data['tahun_selesai_menghuni'], $data['alasan_keluar']);

        if (($data['status_hunian'] ?? null) === StatusHunian::TidakDihuni->value) {
            $data['transmigran_id'] = null;
        }

        if (($data['transmigran_id'] ?? null) !== null) {
            $penghuni = Transmigran::withoutGlobalScope(CakupanDataSp::class)
                ->findOrFail((int) $data['transmigran_id']);
            CakupanDataSp::pastikanDapatDitulis($penghuni);
            $data['satuan_permukiman_id'] = $penghuni->satuan_permukiman_id;
        }

        $sp = SatuanPermukiman::findOrFail((int) $data['satuan_permukiman_id']);
        CakupanDataSp::pastikanDapatDitulis($sp);

        $rumah = Rumah::create($data + ['uuid' => (string) Str::uuid()]);

        if ($rumah->transmigran_id !== null) {
            $rumah->riwayatPenghunian()->create([
                'transmigran_id' => $rumah->transmigran_id,
                'tahun_mulai_menghuni' => $tahunMulai,
            ]);
        }

        return $rumah;
    }
}
