<?php

namespace App\Support;

use App\Models\HasilPanen;
use App\Models\Penanaman;
use App\Models\Scopes\CakupanDataSp;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OperasiHasilPanen
{
    public static function buat(array $data, Penanaman $penanaman): HasilPanen
    {
        CakupanDataSp::pastikanDapatDitulis($penanaman);
        if (HasilPanen::where('penanaman_id', $penanaman->id_penanaman)->where('status', 'Aktif')->exists()) {
            throw ValidationException::withMessages(['penanaman_id' => 'Penanaman ini sudah memiliki catatan panen aktif.']);
        }
        if ($data['periode_panen'] < $penanaman->periode_tanam) {
            throw ValidationException::withMessages(['periode_panen' => 'Periode panen tidak boleh mendahului periode tanam.']);
        }

        $realisasi = (float) $data['realisasi_panen'];
        $puso = (float) $data['puso'];
        if (abs(round($realisasi + $puso - (float) $penanaman->realisasi_tanam, 2)) > 0.001) {
            throw ValidationException::withMessages(['puso' => 'Realisasi panen + puso wajib sama dengan realisasi tanam.']);
        }
        $produktivitas = $realisasi > 0 ? (float) ($data['produktivitas'] ?? 0) : 0.0;
        if ($realisasi > 0 && $produktivitas <= 0) {
            throw ValidationException::withMessages(['produktivitas' => 'Produktivitas wajib diisi kecuali seluruh hamparan gagal panen.']);
        }

        return HasilPanen::create([
            'uuid' => (string) Str::uuid(), 'penanaman_id' => $penanaman->id_penanaman,
            'satuan_id' => $penanaman->komoditas->satuan_id, 'periode_panen' => $data['periode_panen'],
            'realisasi_panen' => $realisasi, 'puso' => $puso, 'produktivitas' => $produktivitas,
            'produksi' => round($realisasi * $produktivitas, 3), 'harga_jual' => $data['harga_jual'] ?? null,
            'keterangan' => $data['keterangan'] ?? null,
        ]);
    }
}
