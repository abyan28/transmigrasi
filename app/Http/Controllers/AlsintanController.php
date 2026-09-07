<?php

namespace App\Http\Controllers;

use App\Enums\CakupanData;
use App\Enums\JenisDaftarPilihan;
use App\Enums\StatusKeaktifanAnggota;
use App\Http\Controllers\Concerns\MenyimpanBerkas;
use App\Models\Alsintan;
use App\Models\AlsintanDistribusi;
use App\Models\AnggotaPoktan;
use App\Models\DaftarPilihan;
use App\Models\Poktan;
use App\Models\SatuanPermukiman;
use App\Models\Scopes\CakupanDataSp;
use App\Support\Paginasi;
use App\Support\PenyajianAlsintan;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            'daftarSp' => SatuanPermukiman::opsiTerlihat(),
            'opsiFilterKondisi' => DaftarPilihan::opsi(JenisDaftarPilihan::Kondisi, false),
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
            'opsiKondisi' => DaftarPilihan::opsi(JenisDaftarPilihan::Kondisi),
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $distribusi = $this->distribusiTerpilih($request, $data);
        $this->pastikanPoktanDapatDitulis((array) $request->input('poktan_id', []));

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
        DB::transaction(function () use ($request, $id) {
            $alsintan = Alsintan::whereKey($id)->lockForUpdate()->firstOrFail();
            $lama = $this->distribusiLengkap($alsintan, true);
            $gantiDistribusi = $request->boolean('ganti_distribusi');
            $data = $this->validasi($request, $alsintan, $lama, $gantiDistribusi);
            $distribusi = $gantiDistribusi ? $this->distribusiTerpilih($request, $data) : [];

            $this->pastikanPoktanDapatDitulis((array) $request->input('poktan_id', []));
            $this->pastikanMetadataDapatDiubah($request, $alsintan, $data, $lama);
            $alsintan->update($this->kolomInduk($data));

            if ($gantiDistribusi) {
                $idBaru = array_map('intval', array_keys($distribusi));
                $hapus = $alsintan->distribusi()->withoutGlobalScopes();
                $spIds = $this->spDitugaskan();

                if ($spIds !== null) {
                    $hapus->whereHas('poktan', fn ($q) => $q
                        ->withoutGlobalScope(CakupanDataSp::class)
                        ->whereIn('satuan_permukiman_id', $spIds));
                }

                $hapus->when($idBaru !== [], fn ($q) => $q->whereNotIn('poktan_id', $idBaru))->delete();

                foreach ($distribusi as $poktanId => $baris) {
                    $alsintan->distribusi()->withoutGlobalScopes()->updateOrCreate(
                        ['poktan_id' => $poktanId],
                        $baris,
                    );
                }
            }

            $this->lampirkanBerkas($request, $alsintan);
        });

        return redirect()->route('alsintan.detail', $id)->with('sukses', 'Perubahan data alsintan tersimpan.');
    }

    public function hapus(int $id): RedirectResponse
    {
        DB::transaction(function () use ($id) {
            $alsintan = Alsintan::whereKey($id)->lockForUpdate()->firstOrFail();
            // 403, BUKAN 404: pengadaan alsintan terlihat semua role (tak
            // ber-cakupan). Yang dilarang adalah menghapus induk yang masih
            // menyuplai distribusi ke SP di luar cakupan aktor -- sumber daya
            // ADA dan terlihat, tindakannya yang ditolak.
            abort_if($this->distribusiDiLuarCakupan($this->distribusiLengkap($alsintan, true))->isNotEmpty(), 403);
            $alsintan->berkas()->detach();
            $alsintan->delete();
        });

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
            $berkas = $this->rekamBerkas(
                $request->file('foto'),
                'alsintan',
                $id,
                'foto',
                $baris->alsintan?->nama_alat ?? '',
                1,
                'distribusi/'.$baris->id_alsintan_distribusi.'/foto',
            );
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
     * Larik ber-kunci persis bentuk tampilan satu baris alsintan.
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

    private function distribusiLengkap(Alsintan $alsintan, bool $kunci = false): Collection
    {
        return $alsintan->distribusi()->withoutGlobalScopes()
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

    private function pastikanMetadataDapatDiubah(Request $request, Alsintan $alsintan, array $data, Collection $lama): void
    {
        if ($this->distribusiDiLuarCakupan($lama)->isEmpty()) {
            return;
        }

        $baru = $this->kolomInduk($data);
        $salinan = clone $alsintan;
        $salinan->forceFill(['nama_alat' => mb_strtoupper(trim((string) $salinan->nama_alat))]);
        $salinan->syncOriginal();
        $salinan->fill($baru);

        abort_if($salinan->isDirty(array_keys($baru)) || $request->hasFile('foto') || $request->hasFile('dokumen_pendukung'), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?Alsintan $alsintan = null, ?Collection $lama = null, bool $gantiDistribusi = true): array
    {
        $data = $request->validate([
            'jenis_alsintan' => ValidationRules::daftarPilihan(JenisDaftarPilihan::JenisAlsintan, wajib: true),
            'nama_alat' => ['required', 'string', 'max:255'],
            'jumlah_total' => ['required', 'integer', 'min:1', 'max:999999'],
            'tahun_pengadaan' => ValidationRules::tahun(),
            'sumber_dana' => ValidationRules::daftarPilihan(JenisDaftarPilihan::SumberDana),
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'ganti_distribusi' => ['sometimes', 'accepted'],
            'poktan_id' => ['nullable', 'array'],
            'poktan_id.*' => ['integer', 'distinct', Rule::exists('poktan', 'id_poktan')],
            'distribusi' => ['nullable', 'array'],
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

        $baru = $alsintan === null || $gantiDistribusi ? $this->distribusiTerpilih($request, $data) : [];
        $dipertahankan = $alsintan === null
            ? collect()
            : ($gantiDistribusi ? $this->distribusiDiLuarCakupan($lama) : $lama);
        $tersalur = (int) $dipertahankan->sum('jumlah') + array_sum(array_column($baru, 'jumlah'));

        if ($tersalur > (int) $data['jumlah_total']) {
            throw ValidationException::withMessages([
                'distribusi' => 'Jumlah seluruh distribusi ('.$tersalur.') melebihi jumlah unit total ('.$data['jumlah_total'].').',
            ]);
        }

        foreach ($baru as $poktanId => $baris) {
            if ($baris['penanda_terima_id'] !== null && ! AnggotaPoktan::query()
                ->whereKey($baris['penanda_terima_id'])
                ->where('poktan_id', $poktanId)
                ->where('status', StatusKeaktifanAnggota::Aktif->value)
                ->exists()) {
                throw ValidationException::withMessages([
                    "distribusi.{$poktanId}.penanda_terima_id" => 'Penanda tangan harus anggota aktif kelompok tani penerima.',
                ]);
            }
        }

        return $data;
    }
}
