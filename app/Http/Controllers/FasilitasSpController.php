<?php

namespace App\Http\Controllers;

use App\Enums\JenisDaftarPilihan;
use App\Enums\JenisFasilitas;
use App\Http\Controllers\Concerns\MenyimpanBerkas;
use App\Models\FasilitasSp;
use App\Support\DummyData;
use App\Support\LayananNotifikasi;
use App\Support\Paginasi;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Fasilitas SP -- bangunan layanan milik satuan permukiman (Task 4.4).
 *
 * Berbeda dari inventaris: fasilitas punya KOORDINAT dan dapat MELAYANI
 * BEBERAPA SP sekaligus lewat pivot `fasilitas_sp_cakupan`. SP pangkal wajib
 * ikut tercantum pada cakupannya -- fasilitas yang tak melayani SP tempatnya
 * berdiri tidak masuk akal.
 *
 * `jenis_fasilitas` adalah ENUM sungguhan di skema (Task 3.1 B3), berbeda dari
 * kolom REF lain pada modul ini yang dibaca dari tabel `daftar_pilihan`.
 */
class FasilitasSpController extends Controller
{
    use MenyimpanBerkas;

    public function index(Request $request): View
    {
        $cari = trim((string) $request->query('cari', ''));
        $filterSp = $request->query('sp');
        $filterKondisi = $request->query('kondisi');
        $perHalaman = Paginasi::perHalaman($request);

        $baris = FasilitasSp::query()
            ->with(['satuanPermukiman', 'cakupan', 'berkas'])
            ->when($cari !== '', fn ($q) => $q->where('nama_fasilitas', 'like', "%{$cari}%"))
            ->when($filterSp, fn ($q) => $q->where('satuan_permukiman_id', $filterSp))
            ->when($filterKondisi, fn ($q) => $q->where('kondisi', $filterKondisi))
            ->orderBy('id_fasilitas_sp')
            ->paginate($perHalaman)
            ->withQueryString();

        $baris->through(fn (FasilitasSp $f) => $this->baris($f));

        return view('pages.sp.fasilitas', [
            'title' => 'Fasilitas SP',
            'baris' => $baris,
            'cari' => $cari,
            'filterSp' => $filterSp,
            'filterKondisi' => $filterKondisi,
            'adaFilter' => $cari !== '' || $filterSp || $filterKondisi,
            // Kartu ringkasan kawasan-penuh, bukan hasil saringan/halaman ini.
            'jenisFasilitas' => FasilitasSp::query()->count(),
            'totalUnit' => (int) FasilitasSp::query()->sum('jumlah'),
            'kondisiBaik' => FasilitasSp::query()->where('kondisi', 'Baik')->count(),
            'rusak' => FasilitasSp::query()->where('kondisi', '!=', 'Baik')->count(),
            'daftarSp' => DummyData::satuanPermukiman(),
            'opsiFilterKondisi' => DummyData::opsiFilterDaftarPilihan(JenisDaftarPilihan::Kondisi),
        ]);
    }

    public function detail(int $id): View
    {
        $fasilitas = FasilitasSp::with(['satuanPermukiman', 'cakupan', 'berkas'])->findOrFail($id);

        return view('pages.sp.detail-fasilitas', [
            'title' => $fasilitas->nama_fasilitas,
            'data' => $this->baris($fasilitas),
            'daftarSp' => DummyData::satuanPermukiman(),
            // Foto jamak sejak Putaran 14; satu bangunan punya beberapa sisi.
            'berkasFoto' => $fasilitas->berkas
                ->filter(fn ($b) => $b->pivot->peran === 'foto')
                ->map(fn ($b) => ['nama_file' => $b->nama_file, 'keterangan' => $b->keterangan])
                ->values()->all(),
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $fasilitas = FasilitasSp::create($data);

        $cakupan = $this->cakupan($request, $data['satuan_permukiman_id']);
        $fasilitas->cakupan()->sync($cakupan);
        $this->lekatkanBerkas($fasilitas, (array) $request->file('foto', []), 'fasilitas_sp', 'foto');

        LayananNotifikasi::hitungUlangSp($cakupan);

        return redirect()->route('sp.fasilitas')->with('sukses', 'Data fasilitas SP tersimpan.');
    }

    public function perbarui(Request $request, int $id): RedirectResponse
    {
        $fasilitas = FasilitasSp::with('cakupan')->findOrFail($id);
        $cakupanLama = $fasilitas->cakupan->pluck('id_satuan_permukiman')->all();
        $data = $this->validasi($request, $fasilitas);
        $fasilitas->update($data);

        $cakupan = $this->cakupan($request, $data['satuan_permukiman_id']);
        $fasilitas->cakupan()->sync($cakupan);
        $this->lekatkanBerkas($fasilitas, (array) $request->file('foto', []), 'fasilitas_sp', 'foto');

        LayananNotifikasi::hitungUlangSp([...$cakupanLama, ...$cakupan]);

        return redirect()->route('sp.fasilitas')->with('sukses', 'Perubahan data fasilitas tersimpan.');
    }

    public function hapus(int $id): RedirectResponse
    {
        $fasilitas = FasilitasSp::with('cakupan')->findOrFail($id);
        $cakupan = $fasilitas->cakupan->pluck('id_satuan_permukiman')->all();

        $fasilitas->berkas()->detach();
        $fasilitas->cakupan()->detach();
        $fasilitas->delete();

        LayananNotifikasi::hitungUlangSp($cakupan);

        return redirect()->route('sp.fasilitas')->with('sukses', 'Data fasilitas dihapus.');
    }

    /**
     * Cakupan lintas SP. SP pangkal SELALU disertakan, apa pun isian formnya:
     * fasilitas yang tak melayani SP tempatnya berdiri tidak masuk akal.
     *
     * @return array<int, int>
     */
    private function cakupan(Request $request, int $pangkal): array
    {
        $lain = array_map('intval', (array) $request->input('satuan_permukiman_ids_lain', []));

        return array_values(array_unique(array_merge([$pangkal], $lain)));
    }

    /**
     * @return array<string, mixed>
     */
    private function baris(FasilitasSp $f): array
    {
        return [
            'id_fasilitas_sp' => $f->id_fasilitas_sp,
            'satuan_permukiman_id' => $f->satuan_permukiman_id,
            'satuan_permukiman' => $f->satuanPermukiman?->nama,
            'satuan_permukiman_ids' => $f->cakupan->pluck('id_satuan_permukiman')->all(),
            'jenis_fasilitas' => $f->jenis_fasilitas?->value ?? $f->jenis_fasilitas,
            'nama_fasilitas' => $f->nama_fasilitas,
            'jumlah' => $f->jumlah,
            'tahun_perolehan' => $f->tahun_perolehan,
            'sumber_dana' => $f->sumber_dana,
            'status_penyerahan' => $f->status_penyerahan,
            'kondisi' => $f->kondisi,
            'rincian_kondisi' => $f->rincian_kondisi,
            'lintang' => $f->lintang === null ? null : (float) $f->lintang,
            'bujur' => $f->bujur === null ? null : (float) $f->bujur,
            'keterangan' => $f->keterangan,
            'foto' => $f->berkas->firstWhere('pivot.peran', 'foto')?->nama_file,
            'dokumen_pendukung' => $f->berkas->firstWhere('pivot.peran', 'pendukung')?->nama_file,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?FasilitasSp $fasilitas = null): array
    {
        $data = $request->validate([
            'satuan_permukiman_id' => ['required', 'integer', Rule::exists('satuan_permukiman', 'id_satuan_permukiman')],
            // ENUM sungguhan di skema, berbeda dari kolom REF lain di modul ini.
            'jenis_fasilitas' => ['required', Rule::enum(JenisFasilitas::class)],
            'nama_fasilitas' => ['required', 'string', 'max:150'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:100000'],
            'tahun_perolehan' => ValidationRules::tahun(),
            'sumber_dana' => ValidationRules::daftarPilihan(JenisDaftarPilihan::SumberDana),
            // NOT NULL di skema, karena itu WAJIB -- bukan sekadar opsional.
            'status_penyerahan' => ValidationRules::daftarPilihan(JenisDaftarPilihan::StatusPenyerahan, wajib: true),
            'kondisi' => ValidationRules::daftarPilihan(JenisDaftarPilihan::Kondisi),
            'lintang' => ValidationRules::lintang(),
            'bujur' => ValidationRules::bujur(),
            'keterangan' => ['nullable', 'string', 'max:500'],
            'satuan_permukiman_ids_lain' => ['nullable', 'array'],
            'satuan_permukiman_ids_lain.*' => ['integer', Rule::exists('satuan_permukiman', 'id_satuan_permukiman')],
            'foto' => ['nullable', 'array'],
            'foto.*' => ValidationRules::foto(),
        ], [
            'satuan_permukiman_id.required' => 'Satuan permukiman wajib dipilih.',
            'jenis_fasilitas.required' => 'Jenis fasilitas wajib dipilih.',
            'nama_fasilitas.required' => 'Nama fasilitas wajib diisi.',
            'jumlah.min' => 'Jumlah unit minimal satu.',
            'status_penyerahan.required' => 'Status penyerahan wajib dipilih.',
        ] + ValidationRules::pesan());

        unset($data['foto'], $data['satuan_permukiman_ids_lain']);

        return $data;
    }
}
