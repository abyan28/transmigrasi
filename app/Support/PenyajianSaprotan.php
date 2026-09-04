<?php

namespace App\Support;

use App\Enums\JenisSaprotan;
use App\Models\Saprotan;
use App\Models\SaprotanDistribusi;

/**
 * Penyajian data Saprotan sebagai larik siap pakai (Task 10.5).
 *
 * Pola INDUK + DISTRIBUSI (Putaran 7). `daftar()`/`baris()` mengembalikan baris
 * pengadaan dengan turunan distribusi bersarang (kunci persis
 * `DummyData::saprotan()`); `distribusi()` mengembalikan daftar DATAR satu baris
 * per penyaluran (kunci persis `DummyData::saprotanDistribusi()`), dipakai
 * Laporan Saprotan dan penelusuran benih Laporan Hasil Panen.
 */
class PenyajianSaprotan
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function daftar(): array
    {
        return Saprotan::query()
            ->withoutGlobalScopes()
            ->with([
                'satuan', 'komoditas', 'foto', 'berkas',
                'distribusi' => fn ($q) => $q->withoutGlobalScopes()->with([
                    'poktan' => fn ($q) => $q->withoutGlobalScopes()->with('satuanPermukiman'),
                    'penanaman' => fn ($q) => $q->withoutGlobalScopes(),
                ]),
            ])
            ->orderBy('id_saprotan')
            ->get()
            ->map(fn (Saprotan $s): array => self::baris($s))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function baris(Saprotan $s): array
    {
        $benih = $s->jenis === JenisSaprotan::Benih;

        $distribusi = $s->distribusi
            ->sortBy('id_saprotan_distribusi')
            ->map(function (SaprotanDistribusi $d) use ($benih): array {
                $jumlah = (float) $d->jumlah;
                $terpakai = (float) $d->penanaman->sum('volume_benih');

                return [
                    'id_saprotan_distribusi' => $d->id_saprotan_distribusi,
                    'saprotan_id' => $d->saprotan_id,
                    'poktan_id' => $d->poktan_id,
                    'poktan' => $d->poktan?->nama,
                    'satuan_permukiman_id' => $d->poktan?->satuan_permukiman_id,
                    'satuan_permukiman' => $d->poktan?->satuanPermukiman?->nama,
                    'jumlah' => $jumlah,
                    'tanggal_serah' => $d->tanggal_serah?->toDateString(),
                    'keterangan' => $d->keterangan,
                    'sisa_benih' => $benih ? max(0.0, round($jumlah - $terpakai, 3)) : null,
                ];
            })
            ->values();

        $tersalur = round((float) $distribusi->sum('jumlah'), 3);

        return [
            'id_saprotan' => $s->id_saprotan,
            'jenis' => $s->jenis?->value,
            'nama' => $s->nama,
            'komoditas_id' => $s->komoditas_id,
            'komoditas' => $s->komoditas?->nama,
            'varietas' => $s->varietas,
            'jadwal_tanam' => $s->jadwal_tanam,
            'jumlah_total' => (float) $s->jumlah_total,
            'satuan_id' => $s->satuan_id,
            'satuan' => $s->satuan?->nama,
            'tahun_pengadaan' => $s->tahun_pengadaan === null ? null : (int) $s->tahun_pengadaan,
            'sumber_dana' => $s->sumber_dana,
            'keterangan' => $s->keterangan,
            'distribusi' => $distribusi->all(),
            'jumlah_tersalur' => $tersalur,
            'jumlah_belum_tersalur' => max(0.0, round((float) $s->jumlah_total - $tersalur, 3)),
            'poktan_penerima' => $distribusi->pluck('poktan')->filter()->unique()->values()->all(),
            'foto' => $s->foto?->nama_file,
            'dokumen_pendukung' => $s->berkas?->nama_file,
        ];
    }

    /**
     * Daftar DATAR distribusi saprotan, kunci persis `DummyData::saprotanDistribusi()`:
     * baris penyaluran + turunan dari pengadaannya (`jenis`, `komoditas`,
     * `varietas`, `satuan`, `tahun_pengadaan`, `sumber_dana`).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function distribusi(): array
    {
        $datar = [];

        foreach (self::daftar() as $induk) {
            foreach ($induk['distribusi'] as $d) {
                $datar[] = $d + [
                    'jenis' => $induk['jenis'],
                    'nama' => $induk['nama'],
                    'komoditas_id' => $induk['komoditas_id'],
                    'komoditas' => $induk['komoditas'],
                    'varietas' => $induk['varietas'],
                    'satuan' => $induk['satuan'],
                    'tahun_pengadaan' => $induk['tahun_pengadaan'],
                    'sumber_dana' => $induk['sumber_dana'],
                ];
            }
        }

        return $datar;
    }
}
