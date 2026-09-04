<?php

namespace App\Http\Controllers;

use App\Enums\JenisSaprotan;
use App\Enums\StatusPanen;
use App\Http\Controllers\Concerns\MenyimpanBerkas;
use App\Models\HasilPanen;
use App\Models\Penanaman;
use App\Models\Poktan;
use App\Models\SaprotanDistribusi;
use App\Support\DummyData;
use App\Support\KonversiPanen;
use App\Support\RekapPoktan;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Penanaman (Task 7.3) -- kegiatan tanam satu poktan. Berpusat pada poktan,
 * bukan lahan/petani. Sumbu waktu = `periode_tanam` YYYY-MM.
 *
 * `saprotan_distribusi_id` + `volume_benih` WAJIB (termasuk bibit swadaya yang
 * lebih dulu didaftarkan sebagai penyaluran bersumber Swadaya). `volume_benih`
 * tak boleh melebihi sisa jatah benih poktan itu; `realisasi_tanam` tak boleh
 * melebihi lahan kelompok yang belum ditanami. Status panen & luas kelompok =
 * turunan, tidak disimpan.
 */
class PenanamanController extends Controller
{
    use MenyimpanBerkas;

    public function index(Request $request): View
    {
        $semua = $this->daftar();

        $cari = trim((string) $request->query('cari', ''));
        $filterSp = $request->query('sp');
        $filterKomoditas = $request->query('komoditas');
        $filterStatus = $request->query('status');
        $tahunDari = $request->query('tahun_dari');
        $tahunSampai = $request->query('tahun_sampai');

        $tahunTanam = fn (array $r) => $r['periode_tanam']
            ? Carbon::parse($r['periode_tanam'].'-01')->year
            : null;

        $statusPanen = [];
        foreach ($semua as $r) {
            $statusPanen[$r['id_penanaman']] = $r['_status'];
        }

        $kekuatanPoktan = [];
        foreach ($semua as $r) {
            $kekuatanPoktan[$r['poktan_id']] ??= $r['_kekuatan'];
        }

        $baris = array_values(array_filter($semua, function (array $r) use ($cari, $filterSp, $filterKomoditas, $filterStatus, $statusPanen) {
            if ($cari !== ''
                && ! str_contains(mb_strtolower((string) $r['poktan']), mb_strtolower($cari))
                && ! str_contains(mb_strtolower((string) $r['komoditas']), mb_strtolower($cari))) {
                return false;
            }
            if ($filterSp && (string) $r['satuan_permukiman_id'] !== (string) $filterSp) {
                return false;
            }
            if ($filterKomoditas && $r['komoditas'] !== $filterKomoditas) {
                return false;
            }

            return ! ($filterStatus && $statusPanen[$r['id_penanaman']]->value !== $filterStatus);
        }));

        $baris = DummyData::saringRentangTahun($baris, $tahunDari, $tahunSampai, $tahunTanam);

        $daftarTahun = array_values(array_filter(array_unique(array_map($tahunTanam, $semua))));
        rsort($daftarTahun);

        return view('pages.penanaman.index', [
            'title' => 'Penanaman',
            'semua' => $semua,
            'baris' => $baris,
            'cari' => $cari,
            'filterSp' => $filterSp,
            'filterTahunDari' => $tahunDari,
            'filterTahunSampai' => $tahunSampai,
            'filterKomoditas' => $filterKomoditas,
            'filterStatus' => $filterStatus,
            'adaFilter' => $cari !== '' || $filterSp || $tahunDari || $tahunSampai || $filterKomoditas || $filterStatus,
            'statusPanen' => $statusPanen,
            'kekuatanPoktan' => $kekuatanPoktan,
            'totalLuas' => array_sum(array_column($baris, 'realisasi_tanam')),
            'totalBelumDipanen' => array_sum(array_map(
                fn (array $r) => $statusPanen[$r['id_penanaman']] === StatusPanen::BelumDipanen ? (float) $r['realisasi_tanam'] : 0.0,
                $semua,
            )),
            'daftarTahun' => $daftarTahun,
            'daftarKomoditas' => array_values(array_unique(array_column($semua, 'komoditas'))),
            'daftarSp' => DummyData::satuanPermukiman(),
        ]);
    }

    public function detail(int $id): View
    {
        $penanaman = Penanaman::with([
            'poktan.satuanPermukiman', 'komoditas.satuan', 'berkas',
            'saprotanDistribusi.saprotan.satuan', 'hasilPanen.satuan',
        ])->findOrFail($id);

        $panen = $penanaman->hasilPanen === null ? [] : [$this->barisPanen($penanaman->hasilPanen)];
        $benihBaris = $penanaman->saprotanDistribusi;

        return view('pages.penanaman.detail', [
            'title' => $penanaman->komoditas?->nama.' - '.$penanaman->poktan?->nama,
            'data' => $this->baris($penanaman),
            'panen' => $panen,
            'produksiTon' => array_sum(array_map(
                fn (array $p) => KonversiPanen::keTon($p['produksi'], $p['satuan']),
                $panen,
            )),
            'luasDipanen' => array_sum(array_column($panen, 'realisasi_panen')),
            'luasPuso' => array_sum(array_map(fn (array $p) => (float) ($p['puso'] ?? 0), $panen)),
            'status' => $this->status($penanaman),
            'belumDitanam' => $penanaman->poktan === null ? 0.0 : RekapPoktan::lahanTersedia($penanaman->poktan),
            'rekapPoktan' => $penanaman->poktan === null
                ? ['jumlah_anggota' => 0, 'luas_total' => 0.0]
                : RekapPoktan::kekuatan($penanaman->poktan),
            'benih' => $benihBaris === null ? null : [
                'saprotan_id' => $benihBaris->saprotan_id,
                'nama' => $benihBaris->saprotan?->nama,
                'satuan' => $benihBaris->saprotan?->satuan?->nama,
            ],
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        DB::transaction(function () use ($request, $data) {
            $penanaman = Penanaman::create($this->kolom($data));
            $this->lampirkanBerkas($request, $penanaman);
        });

        return redirect()->route('penanaman')->with('sukses', 'Catatan penanaman tersimpan.');
    }

    public function perbarui(Request $request, int $id): RedirectResponse
    {
        $penanaman = Penanaman::findOrFail($id);
        $data = $this->validasi($request, $penanaman);

        DB::transaction(function () use ($request, $penanaman, $data) {
            $penanaman->update($this->kolom($data));
            $this->lampirkanBerkas($request, $penanaman);
        });

        return redirect()->route('penanaman.detail', $id)->with('sukses', 'Perubahan catatan penanaman tersimpan.');
    }

    public function hapus(int $id): RedirectResponse
    {
        $penanaman = Penanaman::findOrFail($id);

        if ($penanaman->hasilPanen()->exists()) {
            return back()->with('galat', 'Penanaman ini sudah memiliki catatan panen sehingga tidak dapat dihapus. Hapus catatan panennya lebih dulu.');
        }

        $penanaman->berkas()->detach();
        $penanaman->delete();

        return redirect()->route('penanaman')->with('sukses', 'Catatan penanaman dihapus.');
    }

    private function lampirkanBerkas(Request $request, Penanaman $penanaman): void
    {
        if ($request->hasFile('dokumen_pendukung')) {
            $penanaman->berkas()->wherePivot('peran', 'pendukung')->detach();
            $this->lekatkanBerkas($penanaman, array_filter([$request->file('dokumen_pendukung')]), 'penanaman', 'pendukung');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function kolom(array $data): array
    {
        return [
            'poktan_id' => $data['poktan_id'],
            'komoditas_id' => $data['komoditas_id'],
            'saprotan_distribusi_id' => $data['saprotan_distribusi_id'],
            'volume_benih' => $data['volume_benih'],
            'realisasi_tanam' => $data['realisasi_tanam'],
            'periode_tanam' => $data['periode_tanam'],
            'keterangan' => $data['keterangan'] ?? null,
        ];
    }

    private function status(Penanaman $p): StatusPanen
    {
        return $p->relationLoaded('hasilPanen')
            ? ($p->hasilPanen === null ? StatusPanen::BelumDipanen : StatusPanen::SelesaiDipanen)
            : ($p->hasilPanen()->exists() ? StatusPanen::SelesaiDipanen : StatusPanen::BelumDipanen);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function daftar(): array
    {
        $penanaman = Penanaman::query()
            ->with(['poktan.satuanPermukiman', 'komoditas', 'hasilPanen', 'berkas'])
            ->orderBy('id_penanaman')
            ->get();

        $kekuatan = [];

        return $penanaman->map(function (Penanaman $p) use (&$kekuatan) {
            $kekuatan[$p->poktan_id] ??= $p->poktan === null
                ? ['jumlah_anggota' => 0, 'luas_kering' => 0.0, 'luas_basah' => 0.0, 'luas_total' => 0.0]
                : RekapPoktan::kekuatan($p->poktan);

            return $this->baris($p) + [
                '_status' => $this->status($p),
                '_kekuatan' => $kekuatan[$p->poktan_id],
            ];
        })->all();
    }

    /**
     * Larik ber-kunci PERSIS satu baris `DummyData::penanaman()`.
     *
     * @return array<string, mixed>
     */
    private function baris(Penanaman $p): array
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
     * @return array<string, mixed>
     */
    private function barisPanen(HasilPanen $h): array
    {
        return [
            'id_hasil_panen' => $h->id_hasil_panen,
            'uuid' => $h->uuid,
            'periode_panen' => $h->periode_panen,
            'realisasi_panen' => (float) $h->realisasi_panen,
            'puso' => $h->puso === null ? null : (float) $h->puso,
            'produktivitas' => (float) $h->produktivitas,
            'produksi' => (float) $h->produksi,
            'harga_jual' => $h->harga_jual === null ? null : (float) $h->harga_jual,
            'satuan' => $h->satuan?->nama,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?Penanaman $penanaman = null): array
    {
        $data = $request->validate([
            'poktan_id' => ['required', 'integer', Rule::exists('poktan', 'id_poktan')],
            'komoditas_id' => ['required', 'integer', Rule::exists('komoditas', 'id_komoditas')],
            'saprotan_distribusi_id' => [
                'required', 'integer',
                Rule::exists('saprotan_distribusi', 'id_saprotan_distribusi'),
            ],
            'volume_benih' => ['required', 'numeric', 'gt:0', 'max:99999999', 'decimal:0,3'],
            'realisasi_tanam' => ['required', 'numeric', 'gt:0', 'max:99999999', 'decimal:0,2'],
            'periode_tanam' => ['required', 'date_format:Y-m', 'before_or_equal:'.now()->format('Y-m')],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'dokumen_pendukung' => ValidationRules::dokumen(),
        ], [
            'poktan_id.required' => 'Kelompok tani wajib dipilih.',
            'komoditas_id.required' => 'Komoditas wajib dipilih.',
            'saprotan_distribusi_id.required' => 'Benih yang dipakai wajib dipilih.',
            'volume_benih.required' => 'Volume benih wajib diisi.',
            'realisasi_tanam.required' => 'Realisasi tanam wajib diisi.',
            'periode_tanam.required' => 'Periode tanam wajib diisi.',
        ] + ValidationRules::pesan());

        $this->validasiLanjutan($request, $data, $penanaman);

        return $data;
    }

    /**
     * Aturan yang menuntut baris lain: benih milik poktan & komoditas yang
     * tepat, volume tak melebihi sisa jatah, luas tak melebihi lahan tersedia.
     *
     * @param  array<string, mixed>  $data
     */
    private function validasiLanjutan(Request $request, array $data, ?Penanaman $penanaman): void
    {
        $distribusi = SaprotanDistribusi::with('saprotan')->find($data['saprotan_distribusi_id']);

        $galat = [];

        if ($distribusi !== null) {
            if ($distribusi->saprotan?->jenis !== JenisSaprotan::Benih) {
                $galat['saprotan_distribusi_id'] = 'Penyaluran yang dipilih bukan benih.';
            } elseif ((int) $distribusi->poktan_id !== (int) $data['poktan_id']) {
                $galat['saprotan_distribusi_id'] = 'Benih itu bukan jatah kelompok tani yang dipilih.';
            } elseif ((int) $distribusi->saprotan?->komoditas_id !== (int) $data['komoditas_id']) {
                $galat['saprotan_distribusi_id'] = 'Benih itu untuk komoditas yang berbeda.';
            } else {
                $sisa = $distribusi->sisaBenih($penanaman?->id_penanaman);

                if (round((float) $data['volume_benih'] - $sisa, 3) > 0) {
                    $galat['volume_benih'] = 'Melebihi sisa jatah benih kelompok ini, yaitu '
                        .rtrim(rtrim(number_format($sisa, 3, ',', '.'), '0'), ',').' '.($distribusi->saprotan?->satuan?->nama ?? '').'.';
                }
            }
        }

        $poktan = Poktan::find($data['poktan_id']);

        if ($poktan !== null) {
            $tersedia = RekapPoktan::lahanTersedia($poktan);

            // Saat menyunting, luas penanaman ini sendiri kembali dihitung tersedia.
            if ($penanaman !== null && ! $penanaman->hasilPanen()->exists() && (int) $penanaman->poktan_id === (int) $poktan->id_poktan) {
                $tersedia = round($tersedia + (float) $penanaman->realisasi_tanam, 2);
            }

            if (round((float) $data['realisasi_tanam'] - $tersedia, 2) > 0) {
                $galat['realisasi_tanam'] = 'Melebihi lahan kelompok yang belum ditanami, yaitu '
                    .rtrim(rtrim(number_format($tersedia, 2, ',', '.'), '0'), ',').' ha.';
            }
        }

        if ($galat !== []) {
            throw ValidationException::withMessages($galat);
        }
    }
}
