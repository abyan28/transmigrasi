<?php

namespace App\Http\Controllers;

use App\Enums\JenisDaftarPilihan;
use App\Http\Controllers\Concerns\MenyimpanBerkas;
use App\Models\InventarisSp;
use App\Support\DummyData;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Inventaris SP -- barang bergerak milik satuan permukiman (Task 4.3).
 *
 * Kolom REF (`jenis_inventaris`, `sumber_dana`, `status_penyerahan`,
 * `kondisi`) disimpan TEKS dan divalidasi terhadap tabel `daftar_pilihan`
 * (Task 4.7), bukan enum PHP -- Admin boleh menambah nilai lewat master.
 *
 * `rincian_kondisi` adalah histogram kondisi PER JENIS barang, bukan per
 * unit: kursi ke-3 tetap tak dapat dibedakan dari kursi ke-7. Jumlah
 * histogramnya WAJIB sama dengan `jumlah`, sebab selisihnya berarti ada unit
 * yang tak terhitung kondisinya dan rekap kondisi SP ikut meleset.
 */
class InventarisSpController extends Controller
{
    use MenyimpanBerkas;

    public function index(Request $request): View
    {
        $semua = $this->daftar();

        $cari = trim((string) $request->query('cari', ''));
        $filterSp = $request->query('sp');
        $filterStatus = $request->query('status_penyerahan');

        $baris = array_values(array_filter($semua, function (array $b) use ($cari, $filterSp, $filterStatus) {
            if ($cari !== '' && ! str_contains(mb_strtolower($b['nama_barang']), mb_strtolower($cari))) {
                return false;
            }
            if ($filterSp && (string) $b['satuan_permukiman_id'] !== (string) $filterSp) {
                return false;
            }

            return ! ($filterStatus && $b['status_penyerahan'] !== $filterStatus);
        }));

        return view('pages.sp.inventaris', [
            'title' => 'Inventaris SP',
            'semua' => $semua,
            'baris' => $baris,
            'cari' => $cari,
            'filterSp' => $filterSp,
            'filterStatus' => $filterStatus,
            'adaFilter' => $cari !== '' || $filterSp || $filterStatus,
            'totalUnit' => array_sum(array_column($semua, 'jumlah')),
            'sudahDiserahkan' => count(array_filter($semua, fn ($b) => $b['status_penyerahan'] === 'Sudah Diserahkan')),
            'perluPerhatian' => count(array_filter($semua, fn ($b) => $b['kondisi'] !== 'Baik')),
            'daftarSp' => DummyData::satuanPermukiman(),
            'opsiFilterStatusPenyerahan' => DummyData::opsiFilterDaftarPilihan(JenisDaftarPilihan::StatusPenyerahan),
        ]);
    }

    public function detail(int $id): View
    {
        $inventaris = InventarisSp::with(['satuanPermukiman', 'berkas'])->findOrFail($id);

        return view('pages.sp.detail-inventaris', [
            'title' => $inventaris->nama_barang,
            'data' => $this->baris($inventaris),
            // Foto jamak sejak Putaran 14; satu barang kerap difoto beberapa sudut.
            'berkasFoto' => $inventaris->berkas
                ->filter(fn ($b) => $b->pivot->peran === 'foto')
                ->map(fn ($b) => ['nama_file' => $b->nama_file, 'keterangan' => $b->keterangan])
                ->values()->all(),
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $inventaris = InventarisSp::create($this->validasi($request));

        $this->lekatkanBerkas($inventaris, (array) $request->file('foto', []), 'inventaris_sp', 'foto');
        $this->lekatkanBerkas($inventaris, (array) $request->file('dokumen_pendukung', []), 'inventaris_sp', 'pendukung');

        return redirect()->route('sp.inventaris')->with('sukses', 'Data inventaris SP tersimpan.');
    }

    public function perbarui(Request $request, int $id): RedirectResponse
    {
        $inventaris = InventarisSp::findOrFail($id);
        $inventaris->update($this->validasi($request, $inventaris));

        $this->lekatkanBerkas($inventaris, (array) $request->file('foto', []), 'inventaris_sp', 'foto');
        $this->lekatkanBerkas($inventaris, (array) $request->file('dokumen_pendukung', []), 'inventaris_sp', 'pendukung');

        return redirect()->route('sp.inventaris')->with('sukses', 'Perubahan data inventaris tersimpan.');
    }

    public function hapus(int $id): RedirectResponse
    {
        $inventaris = InventarisSp::findOrFail($id);

        // Pivot dilepas; registry `berkas` tetap sebab melayani banyak modul.
        $inventaris->berkas()->detach();
        $inventaris->delete();

        return redirect()->route('sp.inventaris')->with('sukses', 'Data inventaris dihapus.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function daftar(): array
    {
        return InventarisSp::query()
            ->with(['satuanPermukiman', 'berkas'])
            ->orderBy('id_inventaris_sp')
            ->get()
            ->map(fn (InventarisSp $i) => $this->baris($i))
            ->all();
    }

    /**
     * Nama kuncinya wajib sama dengan `DummyData::inventarisSp()` supaya view
     * tidak perlu disentuh.
     *
     * @return array<string, mixed>
     */
    private function baris(InventarisSp $i): array
    {
        return [
            'id_inventaris_sp' => $i->id_inventaris_sp,
            'satuan_permukiman_id' => $i->satuan_permukiman_id,
            'satuan_permukiman' => $i->satuanPermukiman?->nama,
            'jenis_inventaris' => $i->jenis_inventaris,
            'nama_barang' => $i->nama_barang,
            'jumlah' => $i->jumlah,
            'satuan_barang' => $i->satuan_barang,
            'tahun_perolehan' => $i->tahun_perolehan,
            'sumber_dana' => $i->sumber_dana,
            'status_penyerahan' => $i->status_penyerahan,
            'kondisi' => $i->kondisi,
            'rincian_kondisi' => $i->rincian_kondisi,
            'keterangan' => $i->keterangan,
            'foto' => $i->berkas->firstWhere('pivot.peran', 'foto')?->nama_file,
            'dokumen_pendukung' => $i->berkas->firstWhere('pivot.peran', 'pendukung')?->nama_file,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?InventarisSp $inventaris = null): array
    {
        $data = $request->validate([
            'satuan_permukiman_id' => ['required', 'integer', Rule::exists('satuan_permukiman', 'id_satuan_permukiman')],
            'nama_barang' => ['required', 'string', 'max:150'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:1000000'],
            'satuan_barang' => ['nullable', 'string', 'max:20'],
            'tahun_perolehan' => ValidationRules::tahun(),
            // Kolom REF: TEKS yang dicocokkan ke tabel `daftar_pilihan`, bukan enum
            // PHP -- Admin boleh menambah nilainya lewat master (Task 4.7).
            'jenis_inventaris' => ValidationRules::daftarPilihan(JenisDaftarPilihan::JenisInventaris),
            'sumber_dana' => ValidationRules::daftarPilihan(JenisDaftarPilihan::SumberDana),
            // NOT NULL di skema, karena itu WAJIB -- bukan sekadar opsional.
            'status_penyerahan' => ValidationRules::daftarPilihan(JenisDaftarPilihan::StatusPenyerahan, wajib: true),
            'kondisi' => ValidationRules::daftarPilihan(JenisDaftarPilihan::Kondisi),
            'keterangan' => ['nullable', 'string', 'max:500'],
            'foto' => ['nullable', 'array'],
            'foto.*' => ValidationRules::foto(),
            'dokumen_pendukung' => ['nullable', 'array'],
            'dokumen_pendukung.*' => ValidationRules::dokumen(),
        ], [
            'satuan_permukiman_id.required' => 'Satuan permukiman wajib dipilih.',
            'nama_barang.required' => 'Nama barang wajib diisi.',
            'jumlah.required' => 'Jumlah unit wajib diisi.',
            'status_penyerahan.required' => 'Status penyerahan wajib dipilih.',
            'jumlah.min' => 'Jumlah unit minimal satu.',
            'status_penyerahan.required' => 'Status penyerahan wajib dipilih.',
        ] + ValidationRules::pesan());

        unset($data['foto'], $data['dokumen_pendukung']);

        return $data;
    }
}
