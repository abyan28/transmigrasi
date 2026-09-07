<?php

namespace App\Http\Controllers;

use App\Enums\JenisDaftarPilihan;
use App\Enums\StatusPanen;
use App\Http\Controllers\Concerns\MenyimpanBerkas;
use App\Models\DaftarPilihan;
use App\Models\HasilPanen;
use App\Models\Penanaman;
use App\Models\Saprotan;
use App\Models\SaprotanDistribusi;
use App\Models\SatuanPermukiman;
use App\Models\Scopes\CakupanDataSp;
use App\Support\KonversiPanen;
use App\Support\OperasiPenanaman;
use App\Support\Paginasi;
use App\Support\PenyajianPanen;
use App\Support\RekapPanen;
use App\Support\RekapPoktan;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $cari = trim((string) $request->query('cari', ''));
        $filterSp = $request->query('sp');
        $filterKomoditas = $request->query('komoditas');
        $filterStatus = $request->query('status');
        $tahunDari = $request->query('tahun_dari') !== null && $request->query('tahun_dari') !== ''
            ? (int) $request->query('tahun_dari') : null;
        $tahunSampai = $request->query('tahun_sampai') !== null && $request->query('tahun_sampai') !== ''
            ? (int) $request->query('tahun_sampai') : null;
        if ($tahunDari !== null && $tahunSampai !== null && $tahunDari > $tahunSampai) {
            [$tahunDari, $tahunSampai] = [$tahunSampai, $tahunDari];
        }
        $perHalaman = Paginasi::perHalaman($request);

        $query = Penanaman::query()
            ->with(['poktan.satuanPermukiman', 'komoditas', 'hasilPanen', 'berkas'])
            ->when($cari !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->orWhereHas('poktan', fn ($p) => $p->where('nama', 'like', "%{$cari}%"))
                ->orWhereHas('komoditas', fn ($k) => $k->where('nama', 'like', "%{$cari}%"))))
            ->when($filterSp, fn ($q) => $q->whereHas('poktan', fn ($p) => $p->where('satuan_permukiman_id', $filterSp)))
            ->when($filterKomoditas, fn ($q) => $q->whereHas('komoditas', fn ($k) => $k->where('nama', $filterKomoditas)))
            // Status panen DITURUNKAN dari ada/tidaknya baris hasil_panen
            // yang menaut (rules.md, lihat StatusPanen), bukan kolom -- jadi
            // disaring lewat keberadaan relasinya, bukan ->where() biasa.
            ->when($filterStatus === StatusPanen::BelumDipanen->value, fn ($q) => $q->whereDoesntHave('hasilPanen'))
            ->when($filterStatus === StatusPanen::SelesaiDipanen->value, fn ($q) => $q->whereHas('hasilPanen'))
            // periode_tanam CHAR(7) 'YYYY-MM' -- perbandingan string sejalan
            // perbandingan kronologis untuk format ini.
            ->when($tahunDari !== null, fn ($q) => $q->where('periode_tanam', '>=', sprintf('%04d-01', $tahunDari)))
            ->when($tahunSampai !== null, fn ($q) => $q->where('periode_tanam', '<=', sprintf('%04d-12', $tahunSampai)));

        // Jumlah luas TERSARING (bukan cuma halaman ini) -- lihat catatan
        // yang sama pada LahanController.
        $totalLuas = (float) (clone $query)->sum('realisasi_tanam');

        $baris = $query->orderBy('id_penanaman')->paginate($perHalaman)->withQueryString();

        // $statusPanen/$kekuatanPoktan hanya dipakai VIEW untuk baris yang
        // TAMPIL (di dalam @foreach ($baris as $r)), jadi cukup dihitung dari
        // model mentah HALAMAN INI -- diambil SEBELUM ->through() menukar
        // isi koleksi menjadi larik tampilan.
        $statusPanen = [];
        $kekuatanPoktan = [];
        foreach ($baris->getCollection() as $p) {
            $statusPanen[$p->id_penanaman] = RekapPanen::status($p);
            $kekuatanPoktan[$p->poktan_id] ??= $p->poktan === null
                ? ['jumlah_anggota' => 0, 'luas_kering' => 0.0, 'luas_basah' => 0.0, 'luas_total' => 0.0]
                : RekapPoktan::kekuatan($p->poktan);
        }

        $baris->through(fn (Penanaman $p) => $this->baris($p));

        return view('pages.penanaman.index', [
            'title' => 'Penanaman',
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
            'totalLuas' => $totalLuas,
            // Kartu ringkasan kawasan-penuh, bukan hasil saringan/halaman ini.
            'totalCatatan' => Penanaman::query()->count(),
            'totalRealisasiTanam' => (float) Penanaman::query()->sum('realisasi_tanam'),
            'totalBelumDipanen' => (float) Penanaman::query()->whereDoesntHave('hasilPanen')->sum('realisasi_tanam'),
            'daftarTahun' => Penanaman::query()
                ->selectRaw('DISTINCT SUBSTRING(periode_tanam, 1, 4) as tahun')
                ->orderByDesc('tahun')->pluck('tahun')->map(fn ($t) => (int) $t)->all(),
            'daftarKomoditas' => Penanaman::query()->join('komoditas', 'komoditas.id_komoditas', '=', 'penanaman.komoditas_id')
                ->distinct()->orderBy('komoditas.nama')->pluck('komoditas.nama')->all(),
            'daftarSp' => SatuanPermukiman::opsiTerlihat(),
        ]);
    }

    public function detail(int $id): View
    {
        $penanaman = Penanaman::with([
            'poktan.satuanPermukiman', 'komoditas.satuan', 'berkas',
            'saprotanDistribusi.saprotan.satuan', 'hasilPanen.satuan',
            'riwayatHasilPanen.satuan', 'riwayatHasilPanen.pembatal',
        ])->findOrFail($id);

        $panen = $penanaman->riwayatHasilPanen
            ->map(fn (HasilPanen $hasil): array => $this->barisPanen($hasil))
            ->all();
        $panenAktif = array_filter($panen, fn (array $hasil): bool => $hasil['status'] === 'Aktif');
        $benihBaris = $penanaman->saprotanDistribusi;

        return view('pages.penanaman.detail', [
            'title' => $penanaman->komoditas?->nama.' - '.$penanaman->poktan?->nama,
            'data' => $this->baris($penanaman),
            'panen' => $panen,
            'produksiTon' => array_sum(array_map(
                fn (array $p) => KonversiPanen::keTon($p['produksi'], $p['satuan']),
                $panenAktif,
            )),
            'luasDipanen' => array_sum(array_column($panenAktif, 'realisasi_panen')),
            'luasPuso' => array_sum(array_map(fn (array $p) => (float) ($p['puso'] ?? 0), $panenAktif)),
            'status' => RekapPanen::status($penanaman),
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
        DB::transaction(function () use ($request) {
            $id = (int) $request->validate(['saprotan_distribusi_id' => ['required', 'integer']])['saprotan_distribusi_id'];
            $distribusi = OperasiPenanaman::distribusiTerkunci($id);
            $data = $this->validasi($request, $distribusi);
            $penanaman = OperasiPenanaman::buat($data, $distribusi);
            $this->lampirkanBerkas($request, $penanaman);
        });

        return redirect()->route('penanaman')->with('sukses', 'Catatan penanaman tersimpan.');
    }

    public function perbarui(Request $request, int $id): RedirectResponse
    {
        DB::transaction(function () use ($request, $id) {
            $distribusi = $this->distribusiTerkunci($request);
            $penanaman = Penanaman::query()->lockForUpdate()->findOrFail($id);
            CakupanDataSp::pastikanDapatDitulis($penanaman);
            $data = $this->validasi($request, $distribusi, $penanaman);
            $this->pastikanStokCukup($data, $distribusi, $penanaman);
            $penanaman->update($this->kolom($data, $distribusi));
            $this->lampirkanBerkas($request, $penanaman);
        });

        return redirect()->route('penanaman.detail', $id)->with('sukses', 'Perubahan catatan penanaman tersimpan.');
    }

    public function hapus(int $id): RedirectResponse
    {
        $dihapus = DB::transaction(function () use ($id) {
            $penanaman = Penanaman::query()->lockForUpdate()->findOrFail($id);
            CakupanDataSp::pastikanDapatDitulis($penanaman);

            if ($penanaman->hasilPanen()->exists()) {
                return false;
            }

            $penanaman->berkas()->detach();
            $penanaman->delete();

            return true;
        });

        return $dihapus
            ? redirect()->route('penanaman')->with('sukses', 'Catatan penanaman dihapus.')
            : back()->with('galat', 'Penanaman ini sudah memiliki catatan panen sehingga tidak dapat dihapus. Hapus catatan panennya lebih dulu.');
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
    private function kolom(array $data, SaprotanDistribusi $distribusi): array
    {
        return [
            'kode_penanaman' => $data['kode_penanaman'],
            'poktan_id' => $distribusi->poktan_id,
            'komoditas_id' => $distribusi->saprotan->komoditas_id,
            'saprotan_distribusi_id' => $distribusi->id_saprotan_distribusi,
            'volume_benih' => $data['volume_benih'],
            'realisasi_tanam' => $data['realisasi_tanam'],
            'periode_tanam' => $data['periode_tanam'],
            'keterangan' => $data['keterangan'] ?? null,
        ];
    }

    /**
     * Larik ber-kunci persis bentuk tampilan satu baris penanaman.
     *
     * Pemetaan dipindah ke `App\Support\PenyajianPanen` (Task 10.5) supaya
     * halaman daftar/rincian dan Laporan Hasil Panen membaca satu sumber.
     *
     * @return array<string, mixed>
     */
    private function baris(Penanaman $p): array
    {
        return PenyajianPanen::barisPenanaman($p);
    }

    /**
     * Subhimpunan baris hasil panen yang ditampilkan pada tab Penanaman.
     *
     * @return array<string, mixed>
     */
    private function barisPanen(HasilPanen $h): array
    {
        $b = PenyajianPanen::barisPanen($h);

        return [
            'id_hasil_panen' => $b['id_hasil_panen'],
            'uuid' => $b['uuid'],
            'periode_panen' => $b['periode_panen'],
            'realisasi_panen' => $b['realisasi_panen'],
            'puso' => $b['puso'],
            'produktivitas' => $b['produktivitas'],
            'produksi' => $b['produksi'],
            'harga_jual' => $b['harga_jual'],
            'status' => $b['status'],
            'dibatalkan_pada' => $b['dibatalkan_pada'],
            'alasan_pembatalan' => $b['alasan_pembatalan'],
            'satuan' => $b['satuan'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, SaprotanDistribusi $distribusi, ?Penanaman $penanaman = null): array
    {
        $data = $request->validate([
            'kode_penanaman' => [
                Rule::requiredIf($penanaman === null), 'nullable', 'string', 'max:50',
                Rule::unique('penanaman', 'kode_penanaman')
                    ->ignore($penanaman?->id_penanaman, 'id_penanaman'),
            ],
            'poktan_id' => ['required', 'integer'],
            'komoditas_id' => ['required', 'integer'],
            'saprotan_distribusi_id' => ['required', 'integer'],
            'volume_benih' => ['required', 'numeric', 'gt:0', 'max:99999999', 'decimal:0,3'],
            'realisasi_tanam' => ['required', 'numeric', 'gt:0', 'max:99999999', 'decimal:0,2'],
            'periode_tanam' => ['required', 'date_format:Y-m', 'before_or_equal:'.now()->format('Y-m')],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'dokumen_pendukung' => ValidationRules::dokumen(),
        ], [
            'poktan_id.required' => 'Kelompok tani wajib dipilih.',
            'kode_penanaman.required' => 'Kode penanaman wajib diisi.',
            'kode_penanaman.unique' => 'Kode penanaman ini sudah dipakai.',
            'komoditas_id.required' => 'Komoditas wajib dipilih.',
            'saprotan_distribusi_id.required' => 'Benih yang dipakai wajib dipilih.',
            'volume_benih.required' => 'Volume benih wajib diisi.',
            'realisasi_tanam.required' => 'Realisasi tanam wajib diisi.',
            'periode_tanam.required' => 'Periode tanam wajib diisi.',
        ] + ValidationRules::pesan());

        if ($penanaman !== null) {
            $data['kode_penanaman'] = $penanaman->kode_penanaman;
        }

        $galat = [];

        if (! DaftarPilihan::memilikiPerilaku(JenisDaftarPilihan::JenisSaprotan, $distribusi->saprotan->jenis, 'benih')) {
            $galat['saprotan_distribusi_id'] = 'Penyaluran yang dipilih bukan benih.';
        } elseif (! $distribusi->saprotan->komoditas()->exists()) {
            $galat['saprotan_distribusi_id'] = 'Komoditas benih tidak tersedia.';
        }

        if ((int) $data['poktan_id'] !== (int) $distribusi->poktan_id) {
            $galat['poktan_id'] = 'Benih itu bukan jatah kelompok tani yang dipilih.';
        }

        if ((int) $data['komoditas_id'] !== (int) $distribusi->saprotan->komoditas_id) {
            $galat['komoditas_id'] = 'Benih itu untuk komoditas yang berbeda.';
        }

        $poktan = $distribusi->poktan;
        $tersedia = RekapPoktan::lahanTersedia($poktan);

        if ($penanaman !== null && ! $penanaman->hasilPanen()->exists() && (int) $penanaman->poktan_id === (int) $poktan->id_poktan) {
            $tersedia = round($tersedia + (float) $penanaman->realisasi_tanam, 2);
        }

        if (round((float) $data['realisasi_tanam'] - $tersedia, 2) > 0) {
            $galat['realisasi_tanam'] = 'Melebihi lahan kelompok yang belum ditanami, yaitu '
                .rtrim(rtrim(number_format($tersedia, 2, ',', '.'), '0'), ',').' ha.';
        }

        if ($galat !== []) {
            throw ValidationException::withMessages($galat);
        }

        return $data;
    }

    private function distribusiTerkunci(Request $request): SaprotanDistribusi
    {
        $id = $request->validate([
            'saprotan_distribusi_id' => ['required', 'integer'],
        ], [
            'saprotan_distribusi_id.required' => 'Benih yang dipakai wajib dipilih.',
        ])['saprotan_distribusi_id'];

        $distribusi = SaprotanDistribusi::query()
            ->whereHas('poktan')
            ->whereHas('saprotan')
            ->findOrFail($id);
        CakupanDataSp::pastikanDapatDitulis($distribusi);

        $saprotan = Saprotan::query()->with('satuan')->lockForUpdate()->findOrFail($distribusi->saprotan_id);
        $distribusi = SaprotanDistribusi::query()->lockForUpdate()->findOrFail($id);
        $distribusi->setRelation('saprotan', $saprotan);
        $distribusi->setRelation('poktan', $distribusi->poktan()->lockForUpdate()->firstOrFail());

        return $distribusi;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function pastikanStokCukup(array $data, SaprotanDistribusi $distribusi, ?Penanaman $penanaman = null): void
    {
        $sisa = $distribusi->sisaBenih($penanaman?->id_penanaman);

        if (round((float) $data['volume_benih'] - $sisa, 3) > 0) {
            throw ValidationException::withMessages([
                'volume_benih' => 'Melebihi sisa jatah benih kelompok ini, yaitu '
                    .rtrim(rtrim(number_format($sisa, 3, ',', '.'), '0'), ',').' '.($distribusi->saprotan->satuan?->nama ?? '').'.',
            ]);
        }
    }
}
