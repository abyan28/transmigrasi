<?php

namespace App\Support;

use App\Enums\PrioritasPengaduan;
use App\Enums\StatusPengaduan;
use App\Models\Pengaduan;

/**
 * Rekap pengaduan (Task 8.6), ber-Eloquent.
 *
 * Sebaran per kategori / status / SP / prioritas / bidang sebagai sumber
 * indikator isu prioritas pada dashboard (`rules.md` 10b poin 8). Tiap baris
 * membawa cacah total, selesai, belum selesai, dan mendesak.
 *
 * Menghormati global scope `CakupanDataSp`: Dinas Pertanian hanya melihat
 * bidang pertanian, Operator SP hanya SP-nya.
 */
class RekapPengaduan
{
    /**
     * @param  string  $kelompok  kategori | status | sp | prioritas | bidang
     * @return array<int, array<string, mixed>> Terbanyak dulu
     */
    public static function rekap(string $kelompok = 'kategori'): array
    {
        $peta = [];

        $pengaduan = Pengaduan::query()->with('satuanPermukiman')->get();

        foreach ($pengaduan as $p) {
            $kunci = match ($kelompok) {
                'status' => $p->status?->value,
                'sp' => $p->satuanPermukiman?->nama,
                'prioritas' => $p->prioritas,
                'bidang' => $p->bidang,
                default => $p->kategori,
            };

            $peta[$kunci] ??= [
                'nama' => $kunci,
                'jumlah' => 0,
                'selesai' => 0,
                'belum_selesai' => 0,
                'mendesak' => 0,
            ];

            $peta[$kunci]['jumlah']++;

            if ($p->status === StatusPengaduan::Selesai) {
                $peta[$kunci]['selesai']++;
            } else {
                $peta[$kunci]['belum_selesai']++;
            }

            if ($p->prioritas === PrioritasPengaduan::Mendesak->value) {
                $peta[$kunci]['mendesak']++;
            }
        }

        $hasil = array_values($peta);
        usort($hasil, fn ($a, $b) => $b['jumlah'] <=> $a['jumlah']);

        return $hasil;
    }
}
