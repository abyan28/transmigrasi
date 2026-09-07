<?php

namespace App\Http\Controllers;

use App\Enums\CakupanData;
use App\Enums\JenisDaftarPilihan;
use App\Http\Controllers\Concerns\MenyimpanBerkas;
use App\Models\DaftarPilihan;
use App\Models\Infrastruktur;
use App\Models\SatuanPermukiman;
use App\Models\Scopes\CakupanDataSp;
use App\Support\LayananNotifikasi;
use App\Support\Paginasi;
use App\Support\RekapDashboard;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Infrastruktur SP (Task 4.6, dipindah dari Task 8.1).
 *
 * `satuan_permukiman_id` adalah LOKASI/PANGKAL; pivot `infrastruktur_sp`
 * mencatat SP mana saja yang benar-benar DILAYANI. Sebelum Putaran 7 kenyataan
 * itu hanya tertulis pada `kapasitas` sebagai teks, sehingga penilaian kondisi
 * SP tak dapat membacanya.
 */
class InfrastrukturController extends Controller
{
    use MenyimpanBerkas;

    public function index(Request $request): View
    {
        $cari = trim((string) $request->query('cari', ''));
        $filterSp = $request->query('sp');
        $filterJenis = $request->query('jenis');
        $filterKondisi = $request->query('kondisi');
        $perHalaman = Paginasi::perHalaman($request);

        $baris = Infrastruktur::query()
            ->with(['satuanPermukiman', 'cakupan', 'berkas'])
            ->when($cari !== '', fn ($q) => $q->where('nama', 'like', "%{$cari}%"))
            ->when($filterSp, fn ($q) => $q->where('satuan_permukiman_id', $filterSp))
            ->when($filterJenis, fn ($q) => $q->where('jenis', $filterJenis))
            ->when($filterKondisi, fn ($q) => $q->where('kondisi', $filterKondisi))
            ->orderBy('id_infrastruktur')
            ->paginate($perHalaman)
            ->withQueryString();

        $baris->through(fn (Infrastruktur $i) => $this->baris($i));

        return view('pages.infrastruktur.index', [
            'title' => 'Infrastruktur SP',
            'baris' => $baris,
            // Rekap kondisi per jenis, dihitung atas SELURUH data bukan hasil
            // penyaringan: yang dijawabnya keadaan KAWASAN, bukan keadaan tampilan.
            'statusJenis' => RekapDashboard::statusInfrastruktur(),
            'cari' => $cari,
            'filterSp' => $filterSp,
            'filterJenis' => $filterJenis,
            'filterKondisi' => $filterKondisi,
            'adaFilter' => $cari !== '' || $filterSp || $filterJenis || $filterKondisi,
            // Kartu ringkasan kawasan-penuh, bukan hasil saringan/halaman ini.
            'totalAset' => Infrastruktur::query()->count(),
            'kondisiBaik' => Infrastruktur::query()->where('kondisi', 'Baik')->count(),
            'rusakBerat' => Infrastruktur::query()->where('kondisi', 'Rusak Berat')->count(),
            'perluPerbaikan' => Infrastruktur::query()->where('kondisi', '!=', 'Baik')->count(),
            'daftarSp' => SatuanPermukiman::opsiTerlihat(),
            'opsiFilterJenis' => DaftarPilihan::opsi(JenisDaftarPilihan::JenisInfrastruktur, false),
            'opsiFilterKondisi' => DaftarPilihan::opsi(JenisDaftarPilihan::Kondisi, false),
        ]);
    }

    public function detail(int $id): View
    {
        $infra = Infrastruktur::with(['satuanPermukiman', 'cakupan', 'berkas'])->findOrFail($id);

        return view('pages.infrastruktur.detail', [
            'title' => $infra->nama,
            'data' => $this->baris($infra),
            'daftarSp' => SatuanPermukiman::opsiTerlihat(),
            // Satu aset dapat punya beberapa titik kerusakan, sehingga fotonya jamak.
            'berkasFoto' => $infra->berkas
                ->filter(fn ($b) => $b->pivot->peran === 'foto')
                ->map(fn ($b) => ['nama_file' => $b->nama_file, 'keterangan' => $b->keterangan])
                ->values()->all(),
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $cakupan = $this->cakupan($request, $data['satuan_permukiman_id']);

        $infra = DB::transaction(function () use ($request, $data, $cakupan) {
            $infra = Infrastruktur::create($data);
            $infra->cakupan()->sync($cakupan);
            $this->lekatkanBerkas($infra, (array) $request->file('foto', []), 'infrastruktur', 'foto');

            return $infra;
        });

        LayananNotifikasi::infrastrukturRusakBerat($infra);
        LayananNotifikasi::hitungUlangSp($cakupan);

        return redirect()->route('infrastruktur.index')->with('sukses', 'Data infrastruktur SP tersimpan.');
    }

    public function perbarui(Request $request, int $id): RedirectResponse
    {
        $infra = Infrastruktur::with('cakupan')->findOrFail($id);
        CakupanDataSp::pastikanDapatDitulis($infra);
        $cakupanLama = $infra->cakupan->pluck('id_satuan_permukiman')->all();
        $data = $this->validasi($request, $infra);
        $cakupanDisunting = $request->boolean('_cakupan_disunting');
        $cakupan = $cakupanDisunting
            ? array_values(array_unique([
                ...$this->cakupanTakTerlihat($cakupanLama),
                ...$this->cakupan($request, $data['satuan_permukiman_id']),
            ]))
            : array_values(array_unique([...$cakupanLama, $data['satuan_permukiman_id']]));

        DB::transaction(function () use ($request, $infra, $data, $cakupan, $cakupanDisunting) {
            $infra->update($data);

            $cakupanDisunting
                ? $infra->cakupan()->sync($cakupan)
                : $infra->cakupan()->syncWithoutDetaching([$data['satuan_permukiman_id']]);
            $this->lekatkanBerkas($infra, (array) $request->file('foto', []), 'infrastruktur', 'foto');
        });

        LayananNotifikasi::infrastrukturRusakBerat($infra);
        LayananNotifikasi::hitungUlangSp([...$cakupanLama, ...$cakupan]);

        return redirect()->route('infrastruktur.index')->with('sukses', 'Perubahan data infrastruktur tersimpan.');
    }

    public function hapus(int $id): RedirectResponse
    {
        $infra = Infrastruktur::with('cakupan')->findOrFail($id);
        CakupanDataSp::pastikanDapatDitulis($infra);
        $cakupan = $infra->cakupan->pluck('id_satuan_permukiman')->all();

        DB::transaction(function () use ($infra) {
            $infra->berkas()->detach();
            $infra->cakupan()->detach();
            $infra->delete();
        });

        LayananNotifikasi::hapusInfrastruktur($infra);
        LayananNotifikasi::hitungUlangSp($cakupan);

        return redirect()->route('infrastruktur.index')->with('sukses', 'Data infrastruktur dihapus.');
    }

    /**
     * SP pangkal SELALU disertakan: infrastruktur yang tak melayani SP
     * tempatnya berdiri tidak masuk akal.
     *
     * @return array<int, int>
     */
    private function cakupan(Request $request, int $pangkal): array
    {
        return array_values(array_unique([
            $pangkal,
            ...array_map('intval', (array) $request->input('satuan_permukiman_ids_lain', [])),
        ]));
    }

    private function cakupanTakTerlihat(array $cakupan): array
    {
        $pengguna = CakupanDataSp::penggunaWajibDisaring();

        if ($pengguna?->role?->cakupan_data !== CakupanData::PerSp) {
            return [];
        }

        return array_values(array_diff($cakupan, CakupanDataSp::spDitugaskan($pengguna)));
    }

    /**
     * @return array<string, mixed>
     */
    private function baris(Infrastruktur $i): array
    {
        return [
            'id_infrastruktur' => $i->id_infrastruktur,
            'satuan_permukiman_id' => $i->satuan_permukiman_id,
            'satuan_permukiman' => $i->satuanPermukiman?->nama,
            'satuan_permukiman_ids' => $i->cakupan->pluck('id_satuan_permukiman')->all(),
            'nama' => $i->nama,
            'jenis' => $i->jenis,
            'tahun_perolehan' => $i->tahun_perolehan,
            'sumber_dana' => $i->sumber_dana,
            'kondisi' => $i->kondisi,
            'kapasitas' => $i->kapasitas,
            'lintang' => $i->lintang === null ? null : (float) $i->lintang,
            'bujur' => $i->bujur === null ? null : (float) $i->bujur,
            'keterangan' => $i->keterangan,
            'foto' => $i->berkas->firstWhere('pivot.peran', 'foto')?->nama_file,
            'dokumen_pendukung' => $i->berkas->firstWhere('pivot.peran', 'pendukung')?->nama_file,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?Infrastruktur $infra = null): array
    {
        $data = $request->validate([
            'satuan_permukiman_id' => ['required', 'integer', Rule::exists('satuan_permukiman', 'id_satuan_permukiman')],
            'nama' => ['required', 'string', 'max:150'],
            'jenis' => ValidationRules::daftarPilihan(JenisDaftarPilihan::JenisInfrastruktur, wajib: true, nilaiSaatIni: $infra?->jenis),
            'tahun_perolehan' => ValidationRules::tahun(),
            'sumber_dana' => ValidationRules::daftarPilihan(JenisDaftarPilihan::SumberDana, nilaiSaatIni: $infra?->sumber_dana),
            'kondisi' => ValidationRules::daftarPilihan(JenisDaftarPilihan::Kondisi, wajib: true, nilaiSaatIni: $infra?->kondisi),
            'kapasitas' => ['nullable', 'string', 'max:100'],
            'lintang' => ValidationRules::lintang(),
            'bujur' => ValidationRules::bujur(),
            'keterangan' => ['nullable', 'string', 'max:500'],
            'satuan_permukiman_ids_lain' => ['nullable', 'array'],
            'satuan_permukiman_ids_lain.*' => ['integer', Rule::exists('satuan_permukiman', 'id_satuan_permukiman')],
            '_cakupan_disunting' => ['nullable', 'boolean'],
            'foto' => ['nullable', 'array'],
            'foto.*' => ValidationRules::foto(),
        ], [
            'satuan_permukiman_id.required' => 'Satuan permukiman wajib dipilih.',
            'nama.required' => 'Nama infrastruktur wajib diisi.',
            'jenis.required' => 'Jenis infrastruktur wajib dipilih.',
            'kondisi.required' => 'Kondisi wajib dipilih.',
        ] + ValidationRules::pesan());

        unset($data['foto'], $data['satuan_permukiman_ids_lain'], $data['_cakupan_disunting']);
        CakupanDataSp::pastikanDapatDitulis((int) $data['satuan_permukiman_id']);

        foreach ((array) $request->input('satuan_permukiman_ids_lain', []) as $sp) {
            CakupanDataSp::pastikanDapatDitulis((int) $sp);
        }

        return $data;
    }
}
