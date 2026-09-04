<?php

namespace App\Support;

use App\Enums\AsalWakilPoktan;
use App\Models\Alsintan;
use App\Models\AlsintanDistribusi;

/**
 * Penyajian data Alsintan sebagai larik siap pakai (Task 10.5).
 *
 * Pola INDUK + DISTRIBUSI (Putaran 7): satu pengadaan dibagikan ke beberapa
 * poktan lintas SP. Logika pemetaan dahulu privat di `AlsintanController`
 * dengan janji "larik ber-kunci PERSIS `DummyData::alsintan()` mapped";
 * dipindah ke sini agar halaman daftar/rincian dan Laporan Alsintan sama.
 */
class PenyajianAlsintan
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function daftar(): array
    {
        return Alsintan::query()
            ->withoutGlobalScopes()
            ->with([
                'berkas',
                // Laporan = kawasan penuh: distribusi tidak ikut disaring cakupan.
                'distribusi' => fn ($q) => $q->withoutGlobalScopes()->with([
                    'poktan' => fn ($q) => $q->withoutGlobalScopes()->with('satuanPermukiman'),
                    'penandaTerima.transmigran',
                    'penandaTerima.anggotaKeluarga',
                    'foto',
                ]),
            ])
            ->orderBy('id_alsintan')
            ->get()
            ->map(fn (Alsintan $a): array => self::baris($a))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function baris(Alsintan $a): array
    {
        $distribusi = $a->distribusi
            ->sortBy('id_alsintan_distribusi')
            ->map(fn (AlsintanDistribusi $d): array => self::barisDistribusi($d))
            ->values();

        $tersalur = (int) $distribusi->sum('jumlah');

        $ringkasan = [];
        foreach ($distribusi as $d) {
            $ringkasan[$d['kondisi']] = ($ringkasan[$d['kondisi']] ?? 0) + $d['jumlah'];
        }

        return [
            'id_alsintan' => $a->id_alsintan,
            'jenis_alsintan' => $a->jenis_alsintan,
            'nama_alat' => $a->nama_alat,
            'jumlah_total' => (int) $a->jumlah_total,
            'tahun_pengadaan' => $a->tahun_pengadaan === null ? null : (int) $a->tahun_pengadaan,
            'sumber_dana' => $a->sumber_dana,
            'keterangan' => $a->keterangan,
            'distribusi' => $distribusi->all(),
            'jumlah_tersalur' => $tersalur,
            'jumlah_belum_tersalur' => max(0, (int) $a->jumlah_total - $tersalur),
            'ringkasan_kondisi' => $ringkasan,
            'poktan_penerima' => $distribusi->pluck('poktan')->filter()->unique()->values()->all(),
            'foto' => self::berkasNama($a, 'foto'),
            'dokumen_pendukung' => self::berkasNama($a, 'pendukung'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function barisDistribusi(AlsintanDistribusi $d): array
    {
        $pt = $d->penandaTerima;

        $penandaNama = null;
        if ($pt !== null) {
            $penandaNama = $pt->asal_wakil === AsalWakilPoktan::AnggotaKeluarga && $pt->anggotaKeluarga !== null
                ? $pt->anggotaKeluarga->nama_lengkap
                : $pt->transmigran?->nama_kepala_keluarga;
        }

        return [
            'id_alsintan_distribusi' => $d->id_alsintan_distribusi,
            'alsintan_id' => $d->alsintan_id,
            'poktan_id' => $d->poktan_id,
            'poktan' => $d->poktan?->nama,
            'satuan_permukiman_id' => $d->poktan?->satuan_permukiman_id,
            'satuan_permukiman' => $d->poktan?->satuanPermukiman?->nama,
            'jumlah' => (int) $d->jumlah,
            'kondisi' => $d->kondisi,
            'penanda_terima_id' => $d->penanda_terima_id,
            'penanda_terima' => $penandaNama,
            'tanggal_serah' => $d->tanggal_serah?->toDateString(),
            'foto' => $d->foto?->nama_file,
            'keterangan' => $d->keterangan,
        ];
    }

    private static function berkasNama(Alsintan $a, string $peran): ?string
    {
        return $a->berkas->firstWhere('pivot.peran', $peran)?->nama_file;
    }
}
