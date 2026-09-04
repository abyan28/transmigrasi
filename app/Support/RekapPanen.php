<?php

namespace App\Support;

use App\Models\HasilPanen;
use App\Models\Penanaman;
use Illuminate\Support\Collection;

/**
 * Rekap panen (Task 7.6), DIHITUNG DARI PENANAMAN dan bukan dari catatan panen.
 *
 * Bila dihitung dari `hasil_panen`, poktan yang sudah menanam tetapi belum
 * panen HILANG dari rekap -- justru keadaan yang paling perlu ditengok. Basis
 * penanaman menahannya tetap tampil sebagai "belum dipanen".
 *
 * TERIKAT PERIODE, tidak pernah kumulatif: luas tak boleh dijumlahkan lintas
 * tahun. Penggolongan tahunnya MEMAKAI TAHUN PANEN (`tahunRekap()`): sudah
 * dipanen ikut tahun panennya, belum dipanen ikut tahun berjalan -- yang kedua
 * BERPINDAH sendiri saat tahun berganti, jadi dihitung tiap kali, tak disimpan
 * (`rules.md` §9.8c).
 *
 * Penyaring silang dicocokkan terhadap PENANAMAN, bukan kunci pengelompokan.
 */
class RekapPanen
{
    /**
     * Tahun rekap satu penanaman: tahun panennya bila sudah dipanen, selain
     * itu tahun berjalan.
     */
    public static function tahunRekap(Penanaman $p): int
    {
        $p->loadMissing('hasilPanen');

        return $p->hasilPanen === null
            ? (int) date('Y')
            : (int) substr((string) $p->hasilPanen->periode_panen, 0, 4);
    }

    /**
     * Tahun panen yang tercatat (tahun berjalan selalu ikut), terbesar dulu.
     *
     * @return array<int, int>
     */
    public static function tahunTercatat(): array
    {
        $tahun = [(int) date('Y')];

        foreach (HasilPanen::query()->pluck('periode_panen') as $periode) {
            $tahun[] = (int) substr((string) $periode, 0, 4);
        }

        $tahun = array_values(array_unique($tahun));
        rsort($tahun);

        return $tahun;
    }

    /**
     * Pilihan penyaring rekap untuk satu tahun, DIHITUNG dari penanaman pada
     * tahun itu (bukan data master): menawarkan opsi dari master berarti
     * menyuguhkan pilihan yang dijamin menghasilkan tabel kosong.
     *
     * @return array{sp: list<string>, komoditas: list<string>}
     */
    public static function opsiFilter(?int $tahun = null): array
    {
        $sp = [];
        $komoditas = [];

        foreach (self::penanaman() as $p) {
            if ($tahun !== null && self::tahunRekap($p) !== $tahun) {
                continue;
            }

            $namaSp = $p->poktan?->satuanPermukiman?->nama;
            $namaKom = $p->komoditas?->nama;

            if ($namaSp !== null) {
                $sp[$namaSp] = true;
            }
            if ($namaKom !== null) {
                $komoditas[$namaKom] = true;
            }
        }

        $sp = array_keys($sp);
        $komoditas = array_keys($komoditas);
        sort($sp);
        sort($komoditas);

        return ['sp' => $sp, 'komoditas' => $komoditas];
    }

    /**
     * Baris rekap, produksi terbesar dulu.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function rekap(
        string $kelompok = 'sp',
        ?int $tahun = null,
        ?string $filterSp = null,
        ?string $filterKomoditas = null,
    ): array {
        $peta = [];
        $kekuatan = [];

        foreach (self::penanaman() as $p) {
            if ($tahun !== null && self::tahunRekap($p) !== $tahun) {
                continue;
            }

            $namaSp = $p->poktan?->satuanPermukiman?->nama;
            $namaKom = $p->komoditas?->nama;
            $namaPoktan = $p->poktan?->nama;

            if ($filterSp !== null && $namaSp !== $filterSp) {
                continue;
            }
            if ($filterKomoditas !== null && $namaKom !== $filterKomoditas) {
                continue;
            }

            $kunci = match ($kelompok) {
                'komoditas' => $namaKom,
                'poktan' => $namaPoktan,
                default => $namaSp,
            };

            $peta[$kunci] ??= [
                'nama' => $kunci,
                'poktan' => [],
                'sp' => [],
                'jumlah_anggota' => 0,
                'luas_lahan' => 0.0,
                'volume_benih' => 0.0,
                'realisasi_tanam' => 0.0,
                'hasil_panen' => 0.0,
                'puso' => 0.0,
                'belum_dipanen' => 0.0,
                'produksi_ton' => 0.0,
                'nilai_jual' => 0.0,
            ];

            $baris = &$peta[$kunci];

            // Cacah poktan & luas lahan dari HIMPUNAN: satu poktan banyak penanaman.
            if ($namaPoktan !== null && ! in_array($namaPoktan, $baris['poktan'], true)) {
                $baris['poktan'][] = $namaPoktan;

                $kekuatan[$p->poktan_id] ??= RekapPoktan::kekuatan($p->poktan);
                $baris['luas_lahan'] += $kekuatan[$p->poktan_id]['luas_total'];
                $baris['jumlah_anggota'] += $kekuatan[$p->poktan_id]['jumlah_anggota'];
            }

            if ($namaSp !== null && ! in_array($namaSp, $baris['sp'], true)) {
                $baris['sp'][] = $namaSp;
            }

            $baris['volume_benih'] += (float) $p->volume_benih;
            $baris['realisasi_tanam'] += (float) $p->realisasi_tanam;

            $panen = $p->hasilPanen;

            if ($panen === null) {
                $baris['belum_dipanen'] += (float) $p->realisasi_tanam;
            } else {
                $baris['hasil_panen'] += (float) $panen->realisasi_panen;
                $baris['puso'] += (float) ($panen->puso ?? 0);
                $baris['produksi_ton'] += KonversiPanen::keTon((float) $panen->produksi, $panen->satuan?->nama);
                $baris['nilai_jual'] += (float) ($panen->harga_jual ?? 0) * (float) $panen->produksi;
            }

            unset($baris);
        }

        $hasil = [];

        foreach ($peta as $baris) {
            $baris['jumlah_poktan'] = count($baris['poktan']);

            foreach (['luas_lahan', 'volume_benih', 'realisasi_tanam', 'hasil_panen', 'puso', 'belum_dipanen'] as $kolom) {
                $baris[$kolom] = round($baris[$kolom], 2);
            }

            $baris['produksi_ton'] = round($baris['produksi_ton'], 3);
            // nilai_jual SENGAJA tidak dibulatkan -- sejalan DummyData::rekapPanen.

            // Produktivitas TERTIMBANG: total produksi (ton) / total luas dipanen.
            // Dari angka yang SUDAH dibulatkan, sebab pembaca mengalikan kolom layar.
            $baris['produktivitas_ton'] = $baris['hasil_panen'] > 0
                ? round($baris['produksi_ton'] / $baris['hasil_panen'], 3)
                : 0.0;

            $hasil[] = $baris;
        }

        usort($hasil, fn ($a, $b) => $b['produksi_ton'] <=> $a['produksi_ton']);

        return $hasil;
    }

    /**
     * @return Collection<int, Penanaman>
     */
    private static function penanaman(): Collection
    {
        return Penanaman::query()
            ->with(['poktan.satuanPermukiman', 'poktan.anggota', 'komoditas', 'hasilPanen.satuan'])
            ->get();
    }
}
