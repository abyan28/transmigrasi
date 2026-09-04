<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\MenyimpanBerkas;
use App\Models\HasilPanen;
use App\Models\Penanaman;
use App\Support\DummyData;
use App\Support\KonversiPanen;
use App\Support\RekapPoktan;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Hasil panen (Task 7.4) -- paling banyak SATU baris per penanaman.
 *
 * Identitas yang WAJIB berlaku (ditegakkan di sini, bukan hanya di peramban):
 *   realisasi_panen + puso  = penanaman.realisasi_tanam
 *   produksi                = realisasi_panen x produktivitas   (dihitung ulang)
 *
 * `satuan_id` DISALIN dari komoditas penanaman saat simpan (snapshot). Gagal
 * total sah: `realisasi_panen` 0 + puso menutup seluruh luas -> `produktivitas`
 * tidak wajib (`rules.md` 9.9b). `produksi` disimpan, sebab ia angka yang
 * dilaporkan; konversi ke ton hanya saat rekap.
 */
class HasilPanenController extends Controller
{
    use MenyimpanBerkas;

    public function index(Request $request): View
    {
        $semua = $this->daftar();

        $cari = trim((string) $request->query('cari', ''));
        $filterSp = $request->query('sp');
        $filterKomoditas = $request->query('komoditas');
        $tahunDari = $request->query('tahun_dari');
        $tahunSampai = $request->query('tahun_sampai');

        $tahunPanen = fn (array $p) => $p['periode_panen'] ? (int) substr($p['periode_panen'], 0, 4) : null;

        $baris = array_values(array_filter($semua, function (array $p) use ($cari, $filterSp, $filterKomoditas) {
            if ($cari !== ''
                && ! str_contains(mb_strtolower((string) $p['poktan']), mb_strtolower($cari))
                && ! str_contains(mb_strtolower((string) $p['komoditas']), mb_strtolower($cari))) {
                return false;
            }
            if ($filterSp && (string) $p['satuan_permukiman_id'] !== (string) $filterSp) {
                return false;
            }

            return ! ($filterKomoditas && $p['komoditas'] !== $filterKomoditas);
        }));

        $baris = DummyData::saringRentangTahun($baris, $tahunDari, $tahunSampai, $tahunPanen);

        $setaraTon = [];
        $asalTanam = [];
        $kekuatanPoktan = [];

        foreach ($semua as $p) {
            $setaraTon[$p['id_hasil_panen']] = KonversiPanen::keTon($p['produksi'], $p['satuan']);
            $asalTanam[$p['id_hasil_panen']] = [
                'volume_benih' => $p['_volume_benih'],
                'luas_lahan' => $p['_luas_lahan'],
            ];
            $kekuatanPoktan[$p['poktan_id']] ??= ['luas_total' => $p['_luas_lahan']];
        }

        $daftarTahun = array_values(array_filter(array_unique(array_map($tahunPanen, $semua))));
        rsort($daftarTahun);

        return view('pages.panen.index', [
            'title' => 'Hasil Panen',
            'semua' => $semua,
            'baris' => $baris,
            'cari' => $cari,
            'filterSp' => $filterSp,
            'filterKomoditas' => $filterKomoditas,
            'filterTahunDari' => $tahunDari,
            'filterTahunSampai' => $tahunSampai,
            'adaFilter' => $cari !== '' || $filterSp || $filterKomoditas || $tahunDari || $tahunSampai,
            'totalTonTampil' => array_sum(array_map(fn ($p) => $setaraTon[$p['id_hasil_panen']], $baris)),
            'totalTonSemua' => array_sum($setaraTon),
            'setaraTon' => $setaraTon,
            'asalTanam' => $asalTanam,
            'kekuatanPoktan' => $kekuatanPoktan,
            'daftarKomoditas' => array_values(array_unique(array_column($semua, 'komoditas'))),
            'daftarTahun' => $daftarTahun,
            'daftarSp' => DummyData::satuanPermukiman(),
        ]);
    }

    public function detail(int $id): View
    {
        $panen = HasilPanen::with([
            'satuan', 'penanaman.poktan.satuanPermukiman', 'penanaman.komoditas', 'berkas',
        ])->findOrFail($id);

        return view('pages.panen.detail', [
            'title' => 'Panen '.$panen->penanaman?->komoditas?->nama,
            'data' => $this->baris($panen),
            'setaraTon' => KonversiPanen::keTon((float) $panen->produksi, $panen->satuan?->nama),
            'tanam' => $panen->penanaman === null ? null : [
                'id_penanaman' => $panen->penanaman->id_penanaman,
                'realisasi_tanam' => (float) $panen->penanaman->realisasi_tanam,
                'periode_tanam' => $panen->penanaman->periode_tanam,
                'volume_benih' => (float) $panen->penanaman->volume_benih,
            ],
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        [$data, $penanaman] = $this->validasi($request);

        DB::transaction(function () use ($request, $data, $penanaman) {
            $panen = HasilPanen::create($this->kolom($data, $penanaman));
            $this->lampirkanBerkas($request, $panen);
        });

        return redirect()->route('panen.index')->with('sukses', 'Hasil panen tersimpan.');
    }

    public function perbarui(Request $request, int $id): RedirectResponse
    {
        $panen = HasilPanen::findOrFail($id);
        [$data, $penanaman] = $this->validasi($request, $panen);

        DB::transaction(function () use ($request, $panen, $data, $penanaman) {
            $panen->update($this->kolom($data, $penanaman));
            $this->lampirkanBerkas($request, $panen);
        });

        return redirect()->route('panen.detail', $id)->with('sukses', 'Perubahan catatan panen tersimpan.');
    }

    public function hapus(int $id): RedirectResponse
    {
        $panen = HasilPanen::findOrFail($id);
        $panen->berkas()->detach();
        $panen->delete();

        return redirect()->route('panen.index')->with('sukses', 'Catatan panen dihapus.');
    }

    private function lampirkanBerkas(Request $request, HasilPanen $panen): void
    {
        if ($request->hasFile('dokumen_pendukung')) {
            $panen->berkas()->wherePivot('peran', 'pendukung')->detach();
            $this->lekatkanBerkas($panen, array_filter([$request->file('dokumen_pendukung')]), 'panen', 'pendukung');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function kolom(array $data, Penanaman $penanaman): array
    {
        $realisasiPanen = (float) $data['realisasi_panen'];
        $produktivitas = $realisasiPanen > 0 ? (float) ($data['produktivitas'] ?? 0) : 0.0;

        return [
            'uuid' => (string) Str::uuid(),
            'penanaman_id' => $penanaman->id_penanaman,
            'satuan_id' => $penanaman->komoditas->satuan_id,
            'periode_panen' => $data['periode_panen'],
            'realisasi_panen' => $realisasiPanen,
            'puso' => (float) $data['puso'],
            'produktivitas' => $produktivitas,
            // Dihitung ulang di peladen: identitas kedua tak boleh bergantung
            // pada angka tersembunyi yang dikirim peramban.
            'produksi' => round($realisasiPanen * $produktivitas, 3),
            'harga_jual' => $data['harga_jual'] ?? null,
            'keterangan' => $data['keterangan'] ?? null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function daftar(): array
    {
        $panen = HasilPanen::query()
            ->with(['satuan', 'penanaman.poktan.satuanPermukiman', 'penanaman.komoditas'])
            ->orderBy('id_hasil_panen')
            ->get();

        $luas = [];

        return $panen->map(function (HasilPanen $h) use (&$luas) {
            $poktan = $h->penanaman?->poktan;
            $luas[$poktan?->id_poktan] ??= $poktan === null ? 0.0 : RekapPoktan::kekuatan($poktan)['luas_total'];

            return $this->baris($h) + [
                '_volume_benih' => (float) ($h->penanaman?->volume_benih ?? 0),
                '_luas_lahan' => $luas[$poktan?->id_poktan],
            ];
        })->all();
    }

    /**
     * Larik ber-kunci PERSIS satu baris `DummyData::hasilPanen()`.
     *
     * @return array<string, mixed>
     */
    private function baris(HasilPanen $h): array
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

    /**
     * @return array{0: array<string, mixed>, 1: Penanaman}
     */
    private function validasi(Request $request, ?HasilPanen $panen = null): array
    {
        $data = $request->validate([
            'penanaman_id' => ['required', 'integer', Rule::exists('penanaman', 'id_penanaman')],
            'periode_panen' => ['required', 'date_format:Y-m', 'before_or_equal:'.now()->format('Y-m')],
            'realisasi_panen' => ['required', 'numeric', 'min:0', 'max:99999999', 'decimal:0,2'],
            'puso' => ['required', 'numeric', 'min:0', 'max:99999999', 'decimal:0,2'],
            'produktivitas' => ['nullable', 'numeric', 'min:0', 'max:99999999', 'decimal:0,3'],
            'harga_jual' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'dokumen_pendukung' => ValidationRules::dokumen(),
        ], [
            'penanaman_id.required' => 'Penanaman wajib dipilih.',
            'periode_panen.required' => 'Periode panen wajib diisi.',
            'realisasi_panen.required' => 'Realisasi panen wajib diisi.',
            'puso.required' => 'Puso wajib diisi (isi 0 bila tidak ada yang gagal).',
        ] + ValidationRules::pesan());

        $penanaman = Penanaman::with('komoditas')->find($data['penanaman_id']);
        $galat = [];

        // Satu penanaman -> satu panen (kecuali baris yang sedang disunting).
        $lain = HasilPanen::where('penanaman_id', $data['penanaman_id'])
            ->when($panen !== null, fn ($q) => $q->whereKeyNot($panen->id_hasil_panen))
            ->exists();

        if ($lain) {
            $galat['penanaman_id'] = 'Penanaman ini sudah memiliki catatan panen.';
        }

        if ($penanaman !== null) {
            $luasTanam = (float) $penanaman->realisasi_tanam;
            $jumlah = round((float) $data['realisasi_panen'] + (float) $data['puso'], 2);

            if (abs($jumlah - $luasTanam) > 0.001) {
                $galat['puso'] = 'Realisasi panen + puso ('.rtrim(rtrim(number_format($jumlah, 2, ',', '.'), '0'), ',')
                    .' ha) wajib sama dengan realisasi tanam ('.rtrim(rtrim(number_format($luasTanam, 2, ',', '.'), '0'), ',').' ha).';
            }

            $gagalTotal = (float) $data['realisasi_panen'] === 0.0 && (float) $data['puso'] > 0;

            if (! $gagalTotal && (($data['produktivitas'] ?? null) === null || (float) $data['produktivitas'] <= 0)) {
                $galat['produktivitas'] = 'Produktivitas wajib diisi kecuali seluruh hamparan gagal panen.';
            }
        }

        if ($galat !== []) {
            throw ValidationException::withMessages($galat);
        }

        return [$data, $penanaman];
    }
}
