<?php

namespace App\Http\Controllers;

use App\Enums\JenisDaftarPilihan;
use App\Http\Controllers\Concerns\MenyimpanBerkas;
use App\Models\Alsintan;
use App\Models\AlsintanDistribusi;
use App\Support\DummyData;
use App\Support\Paginasi;
use App\Support\PenyajianAlsintan;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Alsintan (Task 6.6) -- pola INDUK + DISTRIBUSI (Putaran 7).
 *
 * Baris `alsintan` mendeskripsikan BENDA (jenis, nama, jumlah total, tahun,
 * sumber dana). `alsintan_distribusi` = satu baris per poktan penerima, dengan
 * jumlah + kondisi (diamati per unit) + penanda tangan BA + tanggal serah.
 * `SUM(distribusi.jumlah) <= jumlah_total` ditegakkan di sini. SP mengikuti
 * poktan (turunan, tak disimpan). Kondisi diperbarui per baris lewat
 * `distribusiKondisi()`.
 */
class AlsintanController extends Controller
{
    use MenyimpanBerkas;

    public function index(Request $request): View
    {
        $cari = trim((string) $request->query('cari', ''));
        $filterSp = $request->query('sp');
        $filterKondisi = $request->query('kondisi');
        $perHalaman = Paginasi::perHalaman($request);

        $baris = Alsintan::query()
            ->with([
                'berkas',
                'distribusi.poktan.satuanPermukiman',
                'distribusi.penandaTerima.transmigran',
                'distribusi.penandaTerima.anggotaKeluarga',
                'distribusi.foto',
            ])
            ->when($cari !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('nama_alat', 'like', "%{$cari}%")
                ->orWhere('jenis_alsintan', 'like', "%{$cari}%")
                ->orWhereHas('distribusi.poktan', fn ($p) => $p->where('nama', 'like', "%{$cari}%"))))
            ->when($filterSp, fn ($q) => $q->whereHas('distribusi.poktan', fn ($p) => $p->where('satuan_permukiman_id', $filterSp)))
            ->when($filterKondisi, fn ($q) => $q->whereHas('distribusi', fn ($d) => $d->where('kondisi', $filterKondisi)))
            ->orderBy('id_alsintan')
            ->paginate($perHalaman)
            ->withQueryString();

        $baris->through(fn (Alsintan $a) => $this->baris($a));

        return view('pages.alsintan.index', [
            'title' => 'Alsintan',
            'baris' => $baris,
            'cari' => $cari,
            'filterSp' => $filterSp,
            'filterKondisi' => $filterKondisi,
            'adaFilter' => $cari !== '' || $filterSp || $filterKondisi,
            // Kartu ringkasan kawasan-penuh, bukan hasil saringan/halaman ini.
            'pengadaan' => Alsintan::query()->count(),
            'totalUnit' => (int) Alsintan::query()->sum('jumlah_total'),
            'belumTersalur' => Alsintan::query()->withSum('distribusi', 'jumlah')->get()
                ->sum(fn (Alsintan $a) => $a->jumlah_total - (int) ($a->distribusi_sum_jumlah ?? 0)),
            'poktanPenerima' => AlsintanDistribusi::query()->distinct('poktan_id')->count('poktan_id'),
            'rusak' => Alsintan::query()->whereHas('distribusi',
                fn ($q) => $q->whereIn('kondisi', ['Rusak Ringan', 'Rusak Berat']))->count(),
            'daftarSp' => DummyData::satuanPermukiman(),
            'opsiFilterKondisi' => DummyData::opsiFilterDaftarPilihan(JenisDaftarPilihan::Kondisi),
        ]);
    }

    public function detail(int $id): View
    {
        $alsintan = Alsintan::with([
            'berkas',
            'distribusi.poktan.satuanPermukiman',
            'distribusi.penandaTerima.transmigran',
            'distribusi.penandaTerima.anggotaKeluarga',
            'distribusi.foto',
        ])->findOrFail($id);

        return view('pages.alsintan.detail', [
            'title' => $alsintan->nama_alat,
            'data' => $this->baris($alsintan),
            'opsiKondisi' => DummyData::opsiDaftarPilihan(JenisDaftarPilihan::Kondisi),
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $distribusi = $this->distribusiTerpilih($request, $data);

        DB::transaction(function () use ($request, $data, $distribusi) {
            $alsintan = Alsintan::create($this->kolomInduk($data));

            foreach ($distribusi as $poktanId => $baris) {
                $alsintan->distribusi()->create($baris + ['poktan_id' => $poktanId]);
            }

            $this->lampirkanBerkas($request, $alsintan);
        });

        return redirect()->route('alsintan.index')->with('sukses', 'Data alsintan tersimpan.');
    }

    public function perbarui(Request $request, int $id): RedirectResponse
    {
        $alsintan = Alsintan::findOrFail($id);

        $data = $this->validasi($request, $alsintan);
        $distribusi = $this->distribusiTerpilih($request, $data);

        DB::transaction(function () use ($request, $alsintan, $data, $distribusi) {
            $alsintan->update($this->kolomInduk($data));

            $idBaru = array_map('intval', array_keys($distribusi));

            // Poktan yang tak lagi menerima: barisnya dilepas.
            $alsintan->distribusi()
                ->when($idBaru !== [], fn ($q) => $q->whereNotIn('poktan_id', $idBaru))
                ->delete();

            foreach ($distribusi as $poktanId => $baris) {
                $alsintan->distribusi()->updateOrCreate(
                    ['poktan_id' => $poktanId],
                    $baris,
                );
            }

            $this->lampirkanBerkas($request, $alsintan);
        });

        return redirect()->route('alsintan.detail', $id)->with('sukses', 'Perubahan data alsintan tersimpan.');
    }

    public function hapus(int $id): RedirectResponse
    {
        $alsintan = Alsintan::findOrFail($id);
        $alsintan->berkas()->detach();
        $alsintan->delete();

        return redirect()->route('alsintan.index')->with('sukses', 'Data alsintan dihapus.');
    }

    /**
     * Memperbarui kondisi satu baris distribusi (`rules.md` Putaran 7): kondisi
     * diamati per unit dan berubah setelah barang dibagikan.
     */
    public function distribusiKondisi(Request $request, int $id, int $dist): RedirectResponse
    {
        $baris = AlsintanDistribusi::where('id_alsintan_distribusi', $dist)
            ->where('alsintan_id', $id)
            ->firstOrFail();

        $data = $request->validate([
            'kondisi' => ValidationRules::daftarPilihan(JenisDaftarPilihan::Kondisi, wajib: true),
            'foto' => ValidationRules::foto(),
        ], [
            'kondisi.required' => 'Kondisi wajib dipilih.',
        ] + ValidationRules::pesan());

        $baris->kondisi = $data['kondisi'];

        if ($request->hasFile('foto')) {
            $berkas = $this->rekamBerkas($request->file('foto'), 'alsintan', $id, 'foto');
            $baris->foto_berkas_id = $berkas->id_berkas;
        }

        $baris->save();

        return redirect()->route('alsintan.detail', $id)->with('sukses', 'Kondisi alat diperbarui.');
    }

    private function lampirkanBerkas(Request $request, Alsintan $alsintan): void
    {
        foreach (['foto' => 'foto', 'dokumen_pendukung' => 'pendukung'] as $isian => $peran) {
            if ($request->hasFile($isian)) {
                $alsintan->berkas()->wherePivot('peran', $peran)->detach();
                $this->lekatkanBerkas($alsintan, array_filter([$request->file($isian)]), 'alsintan', $peran);
            }
        }
    }

    /**
     * Baris distribusi per poktan terpilih. Poktan tak terpilih -> tak ada baris
     * (seluruh jumlahnya terhitung belum tersalur).
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    private function distribusiTerpilih(Request $request, array $data): array
    {
        $terpilih = array_map('intval', (array) $request->input('poktan_id', []));
        $distribusi = (array) ($data['distribusi'] ?? []);

        $hasil = [];

        foreach ($terpilih as $poktanId) {
            $baris = $distribusi[$poktanId] ?? [];

            $hasil[$poktanId] = [
                'jumlah' => (int) ($baris['jumlah'] ?? 0),
                'kondisi' => $baris['kondisi'] ?? 'Baik',
                'penanda_terima_id' => ($baris['penanda_terima_id'] ?? null) ?: null,
                'tanggal_serah' => ($baris['tanggal_serah'] ?? null) ?: null,
            ];
        }

        return $hasil;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function kolomInduk(array $data): array
    {
        return [
            'jenis_alsintan' => $data['jenis_alsintan'],
            'nama_alat' => $data['nama_alat'],
            'jumlah_total' => (int) $data['jumlah_total'],
            'tahun_pengadaan' => $data['tahun_pengadaan'] ?? null,
            'sumber_dana' => $data['sumber_dana'] ?? null,
            'keterangan' => $data['keterangan'] ?? null,
        ];
    }

    /**
     * Larik ber-kunci PERSIS satu baris `DummyData::alsintan()` mapped.
     *
     * Pemetaan dipindah ke `App\Support\PenyajianAlsintan` (Task 10.5) supaya
     * halaman daftar/rincian dan Laporan Alsintan membaca satu sumber.
     *
     * @return array<string, mixed>
     */
    private function baris(Alsintan $a): array
    {
        return PenyajianAlsintan::baris($a);
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?Alsintan $alsintan = null): array
    {
        return $request->validate([
            'jenis_alsintan' => ValidationRules::daftarPilihan(JenisDaftarPilihan::JenisAlsintan, wajib: true),
            'nama_alat' => ['required', 'string', 'max:255'],
            'jumlah_total' => ['required', 'integer', 'min:1', 'max:999999'],
            'tahun_pengadaan' => ValidationRules::tahun(),
            'sumber_dana' => ValidationRules::daftarPilihan(JenisDaftarPilihan::SumberDana),
            'keterangan' => ['nullable', 'string', 'max:1000'],

            'poktan_id' => ['nullable', 'array'],
            'poktan_id.*' => ['integer', Rule::exists('poktan', 'id_poktan')],

            // Invarian Putaran 7: Sigma distribusi <= jumlah total.
            'distribusi' => ['nullable', 'array', function (string $atribut, mixed $nilai, callable $gagal) use ($request) {
                $total = (int) $request->input('jumlah_total', 0);
                $terpilih = array_map('intval', (array) $request->input('poktan_id', []));

                $tersalur = 0;
                foreach ($terpilih as $poktanId) {
                    $tersalur += (int) ($nilai[$poktanId]['jumlah'] ?? 0);
                }

                if ($tersalur > $total) {
                    $gagal('Jumlah seluruh distribusi ('.$tersalur.') melebihi jumlah unit total ('.$total.').');
                }
            }],
            'distribusi.*.jumlah' => ['required', 'integer', 'min:0', 'max:999999'],
            'distribusi.*.kondisi' => ValidationRules::daftarPilihan(JenisDaftarPilihan::Kondisi, wajib: true),
            'distribusi.*.penanda_terima_id' => ['nullable', 'integer', Rule::exists('anggota_poktan', 'id_anggota_poktan')],
            'distribusi.*.tanggal_serah' => ['nullable', 'date', 'before_or_equal:today'],

            'foto' => ValidationRules::foto(),
            'dokumen_pendukung' => ValidationRules::dokumen(),
        ], [
            'jenis_alsintan.required' => 'Jenis alat wajib dipilih.',
            'nama_alat.required' => 'Nama alat wajib diisi.',
            'jumlah_total.required' => 'Jumlah unit total wajib diisi.',
        ] + ValidationRules::pesan());
    }
}
