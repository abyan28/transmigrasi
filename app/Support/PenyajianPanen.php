<?php

namespace App\Support;

use App\Models\HasilPanen;
use App\Models\Penanaman;

/**
 * Penyajian data Penanaman & Hasil Panen sebagai larik siap pakai (Task 10.5).
 *
 * Pemetaan yang dahulu privat di `PenanamanController` / `HasilPanenController`
 * ("larik ber-kunci PERSIS `DummyData::penanaman()` / `hasilPanen()`") dipindah
 * ke sini agar halaman daftar/rincian dan Laporan Hasil Panen membaca satu
 * sumber.
 */
class PenyajianPanen
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function penanaman(): array
    {
        return Penanaman::query()
            ->withoutGlobalScopes()
            ->with([
                'poktan' => fn ($q) => $q->withoutGlobalScopes()->with('satuanPermukiman'),
                'komoditas', 'berkas',
            ])
            ->orderBy('id_penanaman')
            ->get()
            ->map(fn (Penanaman $p): array => self::barisPenanaman($p))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function barisPenanaman(Penanaman $p): array
    {
        return [
            'id_penanaman' => $p->id_penanaman,
            'poktan_id' => $p->poktan_id,
            'poktan' => $p->poktan?->nama,
            'komoditas_id' => $p->komoditas_id,
            'komoditas' => $p->komoditas?->nama,
            'saprotan_distribusi_id' => $p->saprotan_distribusi_id,
            'volume_benih' => (float) $p->volume_benih,
            'realisasi_tanam' => (float) $p->realisasi_tanam,
            'periode_tanam' => $p->periode_tanam,
            'satuan_permukiman_id' => $p->poktan?->satuan_permukiman_id,
            'satuan_permukiman' => $p->poktan?->satuanPermukiman?->nama,
            'keterangan' => $p->keterangan,
            'dokumen_pendukung' => $p->berkas->firstWhere('pivot.peran', 'pendukung')?->nama_file
                ?? $p->berkas->first()?->nama_file,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function hasilPanen(): array
    {
        return HasilPanen::query()
            ->withoutGlobalScopes()
            ->with([
                'satuan', 'berkas',
                'penanaman' => fn ($q) => $q->withoutGlobalScopes()->with([
                    'poktan' => fn ($q) => $q->withoutGlobalScopes()->with('satuanPermukiman'),
                    'komoditas',
                ]),
            ])
            ->orderBy('id_hasil_panen')
            ->get()
            ->map(fn (HasilPanen $h): array => self::barisPanen($h))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function barisPanen(HasilPanen $h): array
    {
        $tanam = $h->penanaman;

        return [
            'id_hasil_panen' => $h->id_hasil_panen,
            'uuid' => $h->uuid,
            'penanaman_id' => $h->penanaman_id,
            'poktan_id' => $tanam?->poktan_id,
            'poktan' => $tanam?->poktan?->nama,
            'komoditas' => $tanam?->komoditas?->nama,
            'satuan' => $h->satuan?->nama,
            'satuan_permukiman' => $tanam?->poktan?->satuanPermukiman?->nama,
            'satuan_permukiman_id' => $tanam?->poktan?->satuan_permukiman_id,
            'periode_panen' => $h->periode_panen,
            'realisasi_panen' => (float) $h->realisasi_panen,
            'puso' => $h->puso === null ? null : (float) $h->puso,
            'produktivitas' => (float) $h->produktivitas,
            'produksi' => (float) $h->produksi,
            'harga_jual' => $h->harga_jual === null ? null : (float) $h->harga_jual,
            'keterangan' => $h->keterangan,
            'dokumen_pendukung' => $h->berkas->first()?->nama_file,
        ];
    }
}
