<?php

namespace App\Support;

use App\Enums\StatusKeaktifanAnggota;
use App\Models\Lahan;
use App\Models\Penanaman;
use App\Models\Poktan;

/**
 * Kekuatan satu poktan: cacah anggota aktif, luas lahan kelompok, dan sisa
 * lahan yang belum ditanami. SELURUHNYA DITURUNKAN, tidak disimpan (`erd.md`
 * 7.3 / `rules.md` 7d.3).
 *
 * Dipakai bersama `PoktanController`, `PenanamanController`, dan
 * `ViewServiceProvider` (form penanaman/panen). Luas keluarga dibaca lewat
 * `RekapLahan::keluarga()`; ketua ikut dihitung sekali bila belum terdaftar
 * sebagai anggota aktif.
 */
class RekapPoktan
{
    /**
     * @return array{jumlah_anggota: int, luas_kering: float, luas_basah: float, luas_total: float}
     */
    public static function kekuatan(Poktan $poktan): array
    {
        $poktan->loadMissing('anggota');

        $aktif = $poktan->anggota->filter(
            fn ($a) => $a->status === StatusKeaktifanAnggota::Aktif,
        );

        $kering = 0.0;
        $basah = 0.0;

        foreach ($aktif as $a) {
            $lahan = self::lahanKeluarga($a->transmigran_id);
            $kering += $lahan['kering'];
            $basah += $lahan['basah'];
        }

        $ketuaTerhitung = $poktan->ketua_transmigran_id !== null
            && $aktif->contains('transmigran_id', $poktan->ketua_transmigran_id);

        if (! $ketuaTerhitung) {
            $lahanKetua = $poktan->asal_ketua->dariKeluargaTransmigran()
                ? self::lahanKeluarga($poktan->ketua_transmigran_id)
                : ['kering' => (float) ($poktan->luas_kering_ketua ?? 0), 'basah' => (float) ($poktan->luas_basah_ketua ?? 0)];

            $kering += $lahanKetua['kering'];
            $basah += $lahanKetua['basah'];
        }

        return [
            'jumlah_anggota' => $aktif->count() + ($ketuaTerhitung ? 0 : 1),
            'luas_kering' => round($kering, 2),
            'luas_basah' => round($basah, 2),
            'luas_total' => round($kering + $basah, 2),
        ];
    }

    /**
     * Luas lahan kelompok yang BELUM ditanami saat ini.
     *
     * Berbeda sifat dari sisa benih: benih habis selamanya begitu ditabur,
     * lahan KEMBALI TERSEDIA setelah panennya tercatat. Karena itu yang
     * dikurangkan hanyalah penanaman yang belum dipanen.
     */
    public static function lahanTersedia(Poktan $poktan): float
    {
        $total = self::kekuatan($poktan)['luas_total'];

        $terpakai = (float) Penanaman::query()
            ->where('poktan_id', $poktan->id_poktan)
            ->whereDoesntHave('hasilPanen')
            ->sum('realisasi_tanam');

        return max(0.0, round($total - $terpakai, 2));
    }

    /**
     * @return array{kering: float, basah: float}
     */
    private static function lahanKeluarga(?int $transmigranId): array
    {
        $rekap = RekapLahan::keluarga(
            $transmigranId === null ? null : Lahan::where('transmigran_id', $transmigranId)->first(),
        );

        return ['kering' => $rekap['kering'], 'basah' => $rekap['basah']];
    }
}
