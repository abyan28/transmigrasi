<?php

namespace App\Http\Controllers;

use App\Enums\CakupanData;
use App\Enums\JenisDaftarPilihan;
use App\Http\Controllers\Concerns\MenyimpanBerkas;
use App\Models\DaftarPilihan;
use App\Models\Poktan;
use App\Models\Saprotan;
use App\Models\SaprotanDistribusi;
use App\Models\SatuanPermukiman;
use App\Models\Scopes\CakupanDataSp;
use App\Support\OperasiSaprotan;
use App\Support\Paginasi;
use App\Support\PenyajianSaprotan;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Saprotan (Task 6.7) -- pola INDUK + DISTRIBUSI (Putaran 7).
 *
 * Baris `saprotan` mendeskripsikan BENDAnya (jenis, nama, jumlah total, satuan,
 * tahun anggaran). `komoditas_id` & `varietas` wajib HANYA bila `jenis = Benih`.
 * `saprotan_distribusi` = satu baris per poktan penerima (jumlah + tanggal
 * serah). `SUM(distribusi.jumlah) <= jumlah_total` ditegakkan di sini. Sisa
 * benih DITURUNKAN per baris distribusi (`jumlah` - Sigma `penanaman.volume_benih`
 * yang menunjuk baris itu), tidak disimpan. SP mengikuti poktan.
 *
 * foto barang -> FK tunggal `foto_berkas_id`; berita acara penyaluran -> FK
 * tunggal `berkas_id`.
 */
class SaprotanController extends Controller
{
    use MenyimpanBerkas;

    public function index(Request $request): View
    {
        $cari = trim((string) $request->query('cari', ''));
        $filterSp = $request->query('sp');
        $filterJenis = $request->query('jenis');
        $perHalaman = Paginasi::perHalaman($request);

        $baris = Saprotan::query()
            ->with(['satuan', 'komoditas', 'distribusi.poktan.satuanPermukiman', 'distribusi.penanaman'])
            ->when($cari !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('nama', 'like', "%{$cari}%")
                ->orWhereHas('distribusi.poktan', fn ($p) => $p->where('nama', 'like', "%{$cari}%"))))
            ->when($filterSp, fn ($q) => $q->whereHas('distribusi.poktan', fn ($p) => $p->where('satuan_permukiman_id', $filterSp)))
            ->when($filterJenis, fn ($q) => $q->where('jenis', $filterJenis))
            ->orderBy('id_saprotan')
            ->paginate($perHalaman)
            ->withQueryString();

        $baris->through(fn (Saprotan $s) => $this->baris($s));

        return view('pages.saprotan.index', [
            'title' => 'Saprotan',
            'baris' => $baris,
            'cari' => $cari,
            'filterSp' => $filterSp,
            'filterJenis' => $filterJenis,
            'adaFilter' => $cari !== '' || $filterSp || $filterJenis,
            // Kartu ringkasan kawasan-penuh, bukan hasil saringan/halaman ini.
            'pengadaan' => Saprotan::query()->count(),
            'jenisUnik' => Saprotan::query()->distinct()->pluck('jenis')->all(),
            'poktanPenerima' => SaprotanDistribusi::query()->distinct('poktan_id')->count('poktan_id'),
            'daftarSp' => SatuanPermukiman::opsiTerlihat(),
        ]);
    }

    public function detail(int $id): View
    {
        $saprotan = Saprotan::with([
            'satuan', 'komoditas', 'foto', 'berkas',
            'distribusi.poktan.satuanPermukiman', 'distribusi.penanaman',
        ])->findOrFail($id);

        return view('pages.saprotan.detail', [
            'title' => $saprotan->nama,
            'data' => $this->baris($saprotan),
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $distribusi = $this->distribusiTerpilih($request, $data);
        $this->pastikanPoktanDapatDitulis((array) $request->input('poktan_id', []));

        DB::transaction(function () use ($request, $data, $distribusi) {
            $barisDistribusi = array_map(
                fn (array $baris, int $poktanId): array => $baris + ['poktan_id' => $poktanId],
                array_values($distribusi),
                array_map('intval', array_keys($distribusi)),
            );
            $saprotan = OperasiSaprotan::buat($this->kolomInduk($data), $barisDistribusi);

            $this->lampirkanBerkas($request, $saprotan);
        });

        return redirect()->route('saprotan.index')->with('sukses', 'Data saprotan tersimpan.');
    }

    public function perbarui(Request $request, int $id): RedirectResponse
    {
        DB::transaction(function () use ($request, $id) {
            $saprotan = Saprotan::whereKey($id)->lockForUpdate()->firstOrFail();
            $lama = $this->distribusiLengkap($saprotan, true);
            $gantiDistribusi = $request->boolean('ganti_distribusi');
            $data = $this->validasi($request, $saprotan, $lama, $gantiDistribusi);
            $distribusi = $gantiDistribusi ? $this->distribusiTerpilih($request, $data) : [];

            $this->pastikanPoktanDapatDitulis((array) $request->input('poktan_id', []));
            $this->pastikanMetadataDapatDiubah($request, $saprotan, $data, $lama);
            $this->pastikanPemakaianTetapSah($saprotan, $data, $distribusi, $lama, $gantiDistribusi);
            $saprotan->update($this->kolomInduk($data));

            if ($gantiDistribusi) {
                $idBaru = array_map('intval', array_keys($distribusi));
                $hapus = $saprotan->distribusi()->withoutGlobalScopes();
                $spIds = $this->spDitugaskan();

                if ($spIds !== null) {
                    $hapus->whereHas('poktan', fn ($q) => $q
                        ->withoutGlobalScope(CakupanDataSp::class)
                        ->whereIn('satuan_permukiman_id', $spIds));
                }

                $hapus->when($idBaru !== [], fn ($q) => $q->whereNotIn('poktan_id', $idBaru))->delete();

                foreach ($distribusi as $poktanId => $baris) {
                    $saprotan->distribusi()->withoutGlobalScopes()->updateOrCreate(['poktan_id' => $poktanId], $baris);
                }
            }

            $this->lampirkanBerkas($request, $saprotan);
        });

        return redirect()->route('saprotan.detail', $id)->with('sukses', 'Perubahan data saprotan tersimpan.');
    }

    public function hapus(int $id): RedirectResponse
    {
        $dihapus = DB::transaction(function () use ($id) {
            $saprotan = Saprotan::whereKey($id)->lockForUpdate()->firstOrFail();
            // 403, BUKAN 404: pengadaan saprotan terlihat semua role (tak
            // ber-cakupan). Yang dilarang adalah menghapus induk yang masih
            // menyuplai distribusi ke SP di luar cakupan aktor -- sumber daya
            // ADA dan terlihat, tindakannya yang ditolak.
            abort_if($this->distribusiDiLuarCakupan($this->distribusiLengkap($saprotan, true))->isNotEmpty(), 403);

            if ($saprotan->distribusi()->withoutGlobalScopes()->exists()) {
                return false;
            }

            $saprotan->delete();

            return true;
        });

        if (! $dihapus) {
            return back()->with('galat', 'Saprotan masih memiliki distribusi sehingga tidak dapat dihapus.');
        }

        return redirect()->route('saprotan.index')->with('sukses', 'Data saprotan dihapus.');
    }

    private function lampirkanBerkas(Request $request, Saprotan $saprotan): void
    {
        $ubah = [];

        if ($request->hasFile('foto')) {
            $ubah['foto_berkas_id'] = $this->rekamBerkas($request->file('foto'), 'saprotan', (int) $saprotan->id_saprotan, 'foto', $saprotan->kode_saprotan, 1, 'foto')->id_berkas;
        }

        if ($request->hasFile('dokumen_pendukung')) {
            $ubah['berkas_id'] = $this->rekamBerkas($request->file('dokumen_pendukung'), 'saprotan', (int) $saprotan->id_saprotan, 'pendukung', $saprotan->kode_saprotan, 1, 'pendukung')->id_berkas;
        }

        if ($ubah !== []) {
            $saprotan->forceFill($ubah)->save();
        }
    }

    /**
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
                'jumlah' => (float) ($baris['jumlah'] ?? 0),
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
        $benih = DaftarPilihan::memilikiPerilaku(JenisDaftarPilihan::JenisSaprotan, $data['jenis'], 'benih');

        return [
            'kode_saprotan' => $data['kode_saprotan'],
            'jenis' => $data['jenis'],
            'nama' => $data['nama'],
            'komoditas_id' => $benih ? $data['komoditas_id'] : null,
            'varietas' => $benih ? $data['varietas'] : null,
            'jumlah_total' => $data['jumlah_total'],
            'satuan_id' => $data['satuan_id'],
            'jadwal_tanam' => $data['jadwal_tanam'] ?? null,
            'tahun_pengadaan' => $data['tahun_pengadaan'],
            'sumber_dana' => $data['sumber_dana'] ?? null,
            'keterangan' => $data['keterangan'] ?? null,
        ];
    }

    /**
     * Larik ber-kunci persis bentuk tampilan satu baris saprotan.
     *
     * Pemetaan dipindah ke `App\Support\PenyajianSaprotan` (Task 10.5) supaya
     * halaman daftar/rincian dan Laporan Saprotan membaca satu sumber.
     *
     * @return array<string, mixed>
     */
    private function baris(Saprotan $s): array
    {
        return PenyajianSaprotan::baris($s);
    }

    private function distribusiLengkap(Saprotan $saprotan, bool $kunci = false): Collection
    {
        return $saprotan->distribusi()->withoutGlobalScopes()
            ->with(['poktan' => fn ($q) => $q->withoutGlobalScope(CakupanDataSp::class)])
            ->when($kunci, fn ($q) => $q->lockForUpdate())
            ->get();
    }

    private function spDitugaskan(): ?array
    {
        $pengguna = CakupanDataSp::penggunaWajibDisaring();

        return $pengguna?->role?->cakupan_data === CakupanData::PerSp
            ? CakupanDataSp::spDitugaskan($pengguna)
            : null;
    }

    private function distribusiDiLuarCakupan(Collection $distribusi): Collection
    {
        $spIds = $this->spDitugaskan();

        return $spIds === null
            ? $distribusi->take(0)
            : $distribusi->reject(fn ($baris) => in_array((int) $baris->poktan?->satuan_permukiman_id, $spIds, true));
    }

    private function pastikanPoktanDapatDitulis(array $poktanIds): void
    {
        $poktanIds = array_values(array_unique(array_map('intval', $poktanIds)));

        abort_unless(
            $poktanIds === [] || Poktan::query()->whereKey($poktanIds)->count() === count($poktanIds),
            404,
        );
    }

    private function pastikanMetadataDapatDiubah(Request $request, Saprotan $saprotan, array $data, Collection $lama): void
    {
        if ($this->distribusiDiLuarCakupan($lama)->isEmpty()) {
            return;
        }

        $baru = $this->kolomInduk($data);
        $salinan = clone $saprotan;
        $salinan->forceFill([
            'nama' => mb_strtoupper(trim((string) $salinan->nama)),
            'varietas' => $salinan->varietas === null ? null : mb_strtoupper(trim((string) $salinan->varietas)),
        ]);
        $salinan->syncOriginal();
        $salinan->fill($baru);

        abort_if($salinan->isDirty(array_keys($baru)) || $request->hasFile('foto') || $request->hasFile('dokumen_pendukung'), 403);
    }

    private function pastikanPemakaianTetapSah(Saprotan $saprotan, array $data, array $baru, Collection $lama, bool $gantiDistribusi): void
    {
        $terpakai = DB::table('penanaman')
            ->whereNull('deleted_at')
            ->whereIn('saprotan_distribusi_id', $lama->pluck('id_saprotan_distribusi'))
            ->selectRaw('saprotan_distribusi_id, SUM(volume_benih) AS total')
            ->groupBy('saprotan_distribusi_id')
            ->pluck('total', 'saprotan_distribusi_id');

        if ($terpakai->isEmpty()) {
            return;
        }

        if (! DaftarPilihan::memilikiPerilaku(JenisDaftarPilihan::JenisSaprotan, $data['jenis'], 'benih')
            || (int) $data['komoditas_id'] !== (int) $saprotan->komoditas_id
            || (int) $data['satuan_id'] !== (int) $saprotan->satuan_id) {
            throw ValidationException::withMessages([
                'jenis' => 'Saprotan yang sudah dipakai penanaman harus tetap berupa benih dengan komoditas dan satuan yang sama.',
            ]);
        }

        if (! $gantiDistribusi) {
            return;
        }

        $dipertahankan = $this->distribusiDiLuarCakupan($lama)->keyBy('id_saprotan_distribusi');

        foreach ($lama as $baris) {
            $jumlahTerpakai = (float) ($terpakai[$baris->id_saprotan_distribusi] ?? 0);
            $jumlahBaru = $dipertahankan->has($baris->id_saprotan_distribusi)
                ? (float) $baris->jumlah
                : (float) ($baru[$baris->poktan_id]['jumlah'] ?? 0);

            if ($jumlahTerpakai > 0 && round($jumlahBaru - $jumlahTerpakai, 3) < 0) {
                throw ValidationException::withMessages([
                    "distribusi.{$baris->poktan_id}.jumlah" => 'Jumlah distribusi tidak boleh lebih kecil dari volume benih yang sudah dipakai ('.$jumlahTerpakai.').',
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?Saprotan $saprotan = null, ?Collection $lama = null, bool $gantiDistribusi = true): array
    {
        $benih = fn () => DaftarPilihan::memilikiPerilaku(JenisDaftarPilihan::JenisSaprotan, $request->input('jenis'), 'benih');
        $data = $request->validate([
            'kode_saprotan' => [
                Rule::requiredIf($saprotan === null), 'nullable', 'string', 'max:50',
                Rule::unique('saprotan', 'kode_saprotan')->ignore($saprotan?->id_saprotan, 'id_saprotan'),
            ],
            'jenis' => ValidationRules::daftarPilihan(JenisDaftarPilihan::JenisSaprotan, wajib: true, nilaiSaatIni: $saprotan?->jenis),
            'nama' => ['required', 'string', 'max:255'],
            'komoditas_id' => [
                'nullable', 'integer', Rule::exists('komoditas', 'id_komoditas'),
                Rule::requiredIf($benih),
            ],
            'varietas' => ['nullable', 'string', 'max:120', Rule::requiredIf($benih)],
            'jumlah_total' => ['required', 'numeric', 'gt:0', 'max:99999999', 'decimal:0,3'],
            'satuan_id' => ['required', 'integer', Rule::exists('satuan', 'id_satuan')],
            'jadwal_tanam' => ['nullable', 'date_format:Y-m'],
            'tahun_pengadaan' => ValidationRules::tahun(wajib: true),
            'sumber_dana' => ValidationRules::daftarPilihan(JenisDaftarPilihan::SumberDana, nilaiSaatIni: $saprotan?->sumber_dana),
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'ganti_distribusi' => ['sometimes', 'accepted'],
            'poktan_id' => ['nullable', 'array'],
            'poktan_id.*' => ['integer', 'distinct', Rule::exists('poktan', 'id_poktan')],
            'distribusi' => ['nullable', 'array'],
            'distribusi.*.jumlah' => ['required', 'numeric', 'min:0', 'max:99999999', 'decimal:0,3'],
            'distribusi.*.tanggal_serah' => ['nullable', 'date', 'before_or_equal:today'],
            'foto' => ValidationRules::foto(),
            'dokumen_pendukung' => ValidationRules::dokumen(),
        ], [
            'jenis.required' => 'Jenis saprotan wajib dipilih.',
            'kode_saprotan.required' => 'Kode saprotan wajib diisi.',
            'kode_saprotan.unique' => 'Kode saprotan ini sudah dipakai.',
            'nama.required' => 'Nama sarana wajib diisi.',
            'komoditas_id.required' => 'Komoditas wajib dipilih untuk jenis Benih.',
            'varietas.required' => 'Varietas wajib diisi untuk jenis Benih.',
            'jumlah_total.required' => 'Jumlah total wajib diisi.',
            'satuan_id.required' => 'Satuan wajib dipilih.',
            'tahun_pengadaan.required' => 'Tahun anggaran pengadaan wajib diisi.',
        ] + ValidationRules::pesan());

        if ($saprotan !== null) {
            $data['kode_saprotan'] = $saprotan->kode_saprotan;
        }

        $baru = $saprotan === null || $gantiDistribusi ? $this->distribusiTerpilih($request, $data) : [];
        $dipertahankan = $saprotan === null
            ? collect()
            : ($gantiDistribusi ? $this->distribusiDiLuarCakupan($lama) : $lama);
        $tersalur = (float) $dipertahankan->sum('jumlah') + array_sum(array_column($baru, 'jumlah'));

        if (round($tersalur - (float) $data['jumlah_total'], 3) > 0) {
            throw ValidationException::withMessages([
                'distribusi' => 'Jumlah seluruh distribusi ('.$tersalur.') melebihi jumlah total ('.$data['jumlah_total'].').',
            ]);
        }

        return $data;
    }
}
