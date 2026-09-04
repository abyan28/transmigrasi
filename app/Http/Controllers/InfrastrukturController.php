<?php

namespace App\Http\Controllers;

use App\Enums\JenisDaftarPilihan;
use App\Http\Controllers\Concerns\MenyimpanBerkas;
use App\Models\Infrastruktur;
use App\Support\DummyData;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $semua = $this->daftar();

        $cari = trim((string) $request->query('cari', ''));
        $filterSp = $request->query('sp');
        $filterJenis = $request->query('jenis');
        $filterKondisi = $request->query('kondisi');

        $baris = array_values(array_filter($semua, function (array $i) use ($cari, $filterSp, $filterJenis, $filterKondisi) {
            if ($cari !== '' && ! str_contains(mb_strtolower($i['nama']), mb_strtolower($cari))) {
                return false;
            }
            if ($filterSp && (string) $i['satuan_permukiman_id'] !== (string) $filterSp) {
                return false;
            }
            if ($filterJenis && $i['jenis'] !== $filterJenis) {
                return false;
            }

            return ! ($filterKondisi && $i['kondisi'] !== $filterKondisi);
        }));

        return view('pages.infrastruktur.index', [
            'title' => 'Infrastruktur SP',
            'semua' => $semua,
            'baris' => $baris,
            // Rekap kondisi per jenis, dihitung atas SELURUH data bukan hasil
            // penyaringan: yang dijawabnya keadaan KAWASAN, bukan keadaan tampilan.
            'statusJenis' => DummyData::statusInfrastruktur(),
            'cari' => $cari,
            'filterSp' => $filterSp,
            'filterJenis' => $filterJenis,
            'filterKondisi' => $filterKondisi,
            'adaFilter' => $cari !== '' || $filterSp || $filterJenis || $filterKondisi,
            'rusakBerat' => count(array_filter($semua, fn ($i) => $i['kondisi'] === 'Rusak Berat')),
            'perluPerbaikan' => count(array_filter($semua, fn ($i) => $i['kondisi'] !== 'Baik')),
            'daftarSp' => DummyData::satuanPermukiman(),
            'opsiFilterJenis' => DummyData::opsiFilterDaftarPilihan(JenisDaftarPilihan::JenisInfrastruktur),
            'opsiFilterKondisi' => DummyData::opsiFilterDaftarPilihan(JenisDaftarPilihan::Kondisi),
        ]);
    }

    public function detail(int $id): View
    {
        $infra = Infrastruktur::with(['satuanPermukiman', 'cakupan', 'berkas'])->findOrFail($id);

        return view('pages.infrastruktur.detail', [
            'title' => $infra->nama,
            'data' => $this->baris($infra),
            'daftarSp' => DummyData::satuanPermukiman(),
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
        $infra = Infrastruktur::create($data);

        $infra->cakupan()->sync($this->cakupan($request, $data['satuan_permukiman_id']));
        $this->lekatkanBerkas($infra, (array) $request->file('foto', []), 'infrastruktur', 'foto');

        return redirect()->route('infrastruktur.index')->with('sukses', 'Data infrastruktur SP tersimpan.');
    }

    public function perbarui(Request $request, int $id): RedirectResponse
    {
        $infra = Infrastruktur::findOrFail($id);
        $data = $this->validasi($request, $infra);
        $infra->update($data);

        $infra->cakupan()->sync($this->cakupan($request, $data['satuan_permukiman_id']));
        $this->lekatkanBerkas($infra, (array) $request->file('foto', []), 'infrastruktur', 'foto');

        return redirect()->route('infrastruktur.index')->with('sukses', 'Perubahan data infrastruktur tersimpan.');
    }

    public function hapus(int $id): RedirectResponse
    {
        $infra = Infrastruktur::findOrFail($id);

        $infra->berkas()->detach();
        $infra->cakupan()->detach();
        $infra->delete();

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
        $lain = array_map('intval', (array) $request->input('satuan_permukiman_ids_lain', []));

        return array_values(array_unique(array_merge([$pangkal], $lain)));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function daftar(): array
    {
        return Infrastruktur::query()
            ->with(['satuanPermukiman', 'cakupan', 'berkas'])
            ->orderBy('id_infrastruktur')
            ->get()
            ->map(fn (Infrastruktur $i) => $this->baris($i))
            ->all();
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
            'jenis' => ValidationRules::daftarPilihan(JenisDaftarPilihan::JenisInfrastruktur, wajib: true),
            'tahun_perolehan' => ValidationRules::tahun(),
            'sumber_dana' => ValidationRules::daftarPilihan(JenisDaftarPilihan::SumberDana),
            'kondisi' => ValidationRules::daftarPilihan(JenisDaftarPilihan::Kondisi, wajib: true),
            'kapasitas' => ['nullable', 'string', 'max:100'],
            'lintang' => ValidationRules::lintang(),
            'bujur' => ValidationRules::bujur(),
            'keterangan' => ['nullable', 'string', 'max:500'],
            'satuan_permukiman_ids_lain' => ['nullable', 'array'],
            'satuan_permukiman_ids_lain.*' => ['integer', Rule::exists('satuan_permukiman', 'id_satuan_permukiman')],
            'foto' => ['nullable', 'array'],
            'foto.*' => ValidationRules::foto(),
        ], [
            'satuan_permukiman_id.required' => 'Satuan permukiman wajib dipilih.',
            'nama.required' => 'Nama infrastruktur wajib diisi.',
            'jenis.required' => 'Jenis infrastruktur wajib dipilih.',
            'kondisi.required' => 'Kondisi wajib dipilih.',
        ] + ValidationRules::pesan());

        unset($data['foto'], $data['satuan_permukiman_ids_lain']);

        return $data;
    }
}
