<?php

namespace App\Http\Controllers;

use App\Enums\AsalWakilPoktan;
use App\Enums\StatusAnggotaKeluarga;
use App\Models\AnggotaKeluarga;
use App\Models\Transmigran;
use App\Support\DummyData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Modul transmigran (Task 5.1).
 *
 * Peralihan jalur BACA -- daftar dan rincian -- dari data contoh ke Eloquent.
 * `data` (baris transmigran) dan `anggotaKeluarga` kini dibaca dari basis data;
 * rumah, lahan, berkas, riwayat suksesi, dan data poktan pada halaman rincian
 * masih memakai `DummyData` sampai Task 5.2/5.3/6 memindahkannya.
 *
 * Rute tulis (`simpan`/`perbarui`/`hapus`/suksesi/catat-peristiwa) masih closure
 * di `routes/internal.php`; validasi + penyimpanannya adalah Task 5.2.
 */
class TransmigranController extends Controller
{
    public function index(Request $request): View
    {
        $semua = $this->daftar();

        $cari = trim((string) $request->query('cari', ''));
        $filterSp = $request->query('sp');
        $filterTinggal = $request->query('status_tinggal');

        $baris = array_values(array_filter($semua, function (array $t) use ($cari, $filterSp, $filterTinggal) {
            if ($cari !== '') {
                $cocok = str_contains(mb_strtolower($t['nama_kepala_keluarga']), mb_strtolower($cari))
                    || str_contains($t['nik'], $cari)
                    || str_contains($t['no_kk'], $cari);

                if (! $cocok) {
                    return false;
                }
            }

            if ($filterSp && (string) $t['satuan_permukiman_id'] !== (string) $filterSp) {
                return false;
            }

            return ! ($filterTinggal && $t['status_tinggal'] !== $filterTinggal);
        }));

        return view('pages.transmigran.index', [
            'title' => 'Data Transmigran',
            'semua' => $semua,
            'baris' => $baris,
            'cari' => $cari,
            'filterSp' => $filterSp,
            'filterTinggal' => $filterTinggal,
            'adaFilter' => $cari !== '' || $filterSp || $filterTinggal,
            'daftarSp' => DummyData::satuanPermukiman(),
        ]);
    }

    public function detail(int $id): View
    {
        $transmigran = Transmigran::with(['satuanPermukiman', 'anggotaKeluarga'])->findOrFail($id);

        $data = $this->baris($transmigran);

        $anggotaPoktan = DummyData::anggotaPoktan();

        // Lahan dan rumah masih data contoh (Task 6 / Task 5.3). Dibaca lewat id
        // transmigran, bukan mencocokkan nama: dua kepala keluarga dapat bernama
        // sama, dan suksesi mengganti nama tanpa memutus tautan id.
        $lahan = array_values(array_filter(
            DummyData::lahan(),
            fn ($l) => $l['transmigran_id'] === $id,
        ));

        return view('pages.transmigran.detail', [
            'title' => $data['nama_kepala_keluarga'],
            'data' => $data,

            'rumah' => collect(DummyData::rumah())->firstWhere('transmigran_id', $id),

            'lahan' => $lahan,
            'totalLuas' => array_sum(array_map(
                fn ($l) => (float) ($l['luas_pekarangan'] ?? 0) + (float) ($l['luas_usaha'] ?? 0),
                $lahan,
            )),

            'berkasKtp' => DummyData::berkasMilik('transmigran_berkas', 'transmigran_id', $id, 'ktp'),
            'berkasKk' => DummyData::berkasMilik('transmigran_berkas', 'transmigran_id', $id, 'kk'),
            'berkasSk' => DummyData::berkasMilik('transmigran_berkas', 'transmigran_id', $id, 'sk'),
            'berkasKeluarga' => DummyData::berkasMilik('transmigran_berkas', 'transmigran_id', $id),

            'anggotaKeluarga' => $transmigran->anggotaKeluarga
                ->sortBy('id_anggota_keluarga')
                ->map(fn (AnggotaKeluarga $a) => $this->barisAnggota($a))
                ->values()
                ->all(),

            'poktanBernaung' => array_values(array_filter(
                $anggotaPoktan,
                fn ($a) => $a['transmigran_id'] === $id && $a['status'] === 'Aktif',
            )),

            'spPoktan' => collect(DummyData::poktan())->pluck('satuan_permukiman', 'id_poktan')->all(),

            'riwayatKk' => DummyData::riwayatKepalaKeluarga($id),
            'calonPengganti' => DummyData::calonPenggantiKk($id),
            'poktanDiketuai' => DummyData::poktanDiketuaiKeluarga($id),

            'keanggotaanIkut' => array_values(array_filter(
                $anggotaPoktan,
                fn ($a) => $a['transmigran_id'] === $id
                    && $a['asal_wakil'] === AsalWakilPoktan::KepalaKeluarga->value
                    && $a['status'] !== 'Sudah Keluar',
            )),

            'inisial' => DummyData::inisial($data['nama_kepala_keluarga']),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function daftar(): array
    {
        return Transmigran::query()
            ->with(['satuanPermukiman', 'anggotaKeluarga'])
            ->orderBy('id_transmigran')
            ->get()
            ->map(fn (Transmigran $t) => $this->baris($t))
            ->all();
    }

    /**
     * Larik ber-kunci PERSIS satu baris `DummyData::transmigran()`, sehingga
     * Blade daftar dan rincian tidak perlu disentuh.
     *
     * @return array<string, mixed>
     */
    private function baris(Transmigran $t): array
    {
        $jiwaAnggota = $t->anggotaKeluarga
            ->filter(fn (AnggotaKeluarga $a) => $a->status === StatusAnggotaKeluarga::Aktif)
            ->count();

        return [
            'id_transmigran' => $t->id_transmigran,
            'nik' => $t->nik,
            'tempat_lahir' => $t->tempat_lahir,
            'no_kk' => $t->no_kk,
            'nama_kepala_keluarga' => $t->nama_kepala_keluarga,
            'jenis_kelamin' => $t->jenis_kelamin?->value,
            'agama' => $t->agama?->value,
            'tanggal_lahir' => $t->tanggal_lahir?->toDateString(),
            'pendidikan_terakhir' => $t->pendidikan_terakhir?->value,
            'pekerjaan_kepala_keluarga' => $t->pekerjaan_kepala_keluarga,
            'pendapatan_per_bulan' => $t->pendapatan_per_bulan === null ? null : (int) $t->pendapatan_per_bulan,
            'daerah_asal_kabupaten_id' => $t->daerah_asal_kabupaten_id,
            'tahun_kedatangan' => (int) $t->tahun_kedatangan,
            'status_tinggal' => $t->status_tinggal->value,
            'status_anggota_poktan' => $t->status_anggota_poktan->value,
            'status_sertifikat' => $t->status_sertifikat->value,
            'telepon' => $t->telepon,
            'keterangan' => $t->keterangan,
            'satuan_permukiman' => $t->satuanPermukiman?->nama,
            'satuan_permukiman_id' => $t->satuan_permukiman_id,
            'jumlah_anggota_keluarga' => 1 + $jiwaAnggota,
        ];
    }

    /**
     * Larik ber-kunci PERSIS satu baris `DummyData::anggotaKeluarga()`.
     *
     * @return array<string, mixed>
     */
    private function barisAnggota(AnggotaKeluarga $a): array
    {
        return [
            'id_anggota_keluarga' => $a->id_anggota_keluarga,
            'transmigran_id' => $a->transmigran_id,
            'hubungan' => $a->hubungan->value,
            'nama_lengkap' => $a->nama_lengkap,
            'nik' => $a->nik,
            'jenis_kelamin' => $a->jenis_kelamin?->value,
            'tempat_lahir' => $a->tempat_lahir,
            'tanggal_lahir' => $a->tanggal_lahir?->toDateString(),
            'agama' => $a->agama?->value,
            'kegiatan' => $a->kegiatan?->value,
            'pendidikan_terakhir' => $a->pendidikan_terakhir?->value,
            'pekerjaan' => $a->pekerjaan,
            'pendapatan_per_bulan' => $a->pendapatan_per_bulan === null ? null : (int) $a->pendapatan_per_bulan,
            'telepon' => $a->telepon,
            'keterangan' => $a->keterangan,
            'status' => $a->status->value,
            'tanggal_peristiwa' => $a->tanggal_peristiwa?->toDateString(),
            'keterangan_peristiwa' => $a->keterangan_peristiwa,
        ];
    }
}
