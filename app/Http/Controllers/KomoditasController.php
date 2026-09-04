<?php

namespace App\Http\Controllers;

use App\Enums\JenisDaftarPilihan;
use App\Models\Komoditas;
use App\Support\DummyData;
use App\Support\Paginasi;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Data Komoditas (Task 7.1: peralihan ke Eloquent).
 *
 * `tipe` disimpan TEKS nilai daftar pilihan (`tipe_komoditas`), bukan enum PHP --
 * Admin boleh menambah tipe lewat data master. `slug` otomatis
 * (`BerslugOtomatis`). Satuan panen baku (`satuan_id`) mengunci satuan setiap
 * pencatatan panen komoditas ini; perubahannya hanya berlaku bagi panen
 * berikutnya (panen lama menyalin satuannya sendiri).
 *
 * Sebaran volume panen kawasan masih agregat sintetis (`DummyData`), sejalan
 * dengan rekap kependudukan -- konversinya milik Tahap rekap, bukan di sini.
 */
class KomoditasController extends Controller
{
    public function index(Request $request): View
    {
        $cari = trim((string) $request->query('cari', ''));
        $filterTipe = $request->query('tipe');
        $perHalaman = Paginasi::perHalaman($request);

        $baris = Komoditas::query()
            ->with('satuan')
            ->when($cari !== '', fn ($q) => $q->where('nama', 'like', "%{$cari}%"))
            ->when($filterTipe, fn ($q) => $q->where('tipe', $filterTipe))
            ->orderBy('id_komoditas')
            ->paginate($perHalaman)
            ->withQueryString();

        $baris->through(fn (Komoditas $k) => $this->baris($k));

        return view('pages.komoditas.index', [
            'title' => 'Data Komoditas',
            'baris' => $baris,
            'sebaran' => DummyData::sebaranKomoditas(),
            'cari' => $cari,
            'filterTipe' => $filterTipe,
            'adaFilter' => $cari !== '' || $filterTipe,
            // Kartu ringkasan kawasan-penuh, bukan hasil saringan/halaman ini.
            'totalKomoditas' => Komoditas::query()->count(),
            'unggulan' => Komoditas::query()->where('is_unggulan', true)->count(),
            'satuanDipakai' => Komoditas::query()->distinct('satuan_id')->count('satuan_id'),
            'opsiFilterTipe' => DummyData::opsiFilterDaftarPilihan(JenisDaftarPilihan::TipeKomoditas),
        ]);
    }

    public function detail(int $id): View
    {
        $komoditas = Komoditas::with('satuan')->findOrFail($id);

        // Penanaman masih data contoh (Tahap 7 lanjutan); dicocokkan lewat id.
        $riwayat = array_values(array_filter(
            DummyData::penanaman(),
            fn ($r) => $r['komoditas_id'] === $komoditas->id_komoditas,
        ));

        return view('pages.komoditas.detail', [
            'title' => $komoditas->nama,
            'data' => $this->baris($komoditas),
            'riwayat' => $riwayat,
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        Komoditas::create($this->validasi($request));

        return redirect()->route('komoditas.index')->with('sukses', 'Data komoditas tersimpan.');
    }

    public function perbarui(Request $request, int $id): RedirectResponse
    {
        $komoditas = Komoditas::findOrFail($id);
        $komoditas->update($this->validasi($request, $komoditas));

        return redirect()->route('komoditas.detail', $id)->with('sukses', 'Perubahan data komoditas tersimpan.');
    }

    /**
     * Penghapusan DITOLAK bila komoditas masih dipakai penanaman atau pengadaan
     * benih (Task 7.2). FK RESTRICT sudah menahannya, tetapi galat SQL mentah
     * tak dapat ditindaklanjuti petugas -- alasannya disampaikan lebih dulu.
     */
    public function hapus(int $id): RedirectResponse
    {
        $komoditas = Komoditas::withCount(['penanaman', 'saprotan'])->findOrFail($id);

        $penahan = [];
        if ($komoditas->penanaman_count > 0) {
            $penahan[] = $komoditas->penanaman_count.' catatan penanaman';
        }
        if ($komoditas->saprotan_count > 0) {
            $penahan[] = $komoditas->saprotan_count.' pengadaan benih';
        }

        if ($penahan !== []) {
            return back()->with('galat', 'Komoditas ini masih dipakai '.implode(' dan ', $penahan).' sehingga tidak dapat dihapus.');
        }

        $komoditas->poktan()->detach();
        $komoditas->delete();

        return redirect()->route('komoditas.index')->with('sukses', 'Data komoditas dihapus.');
    }

    /**
     * Larik ber-kunci PERSIS satu baris `DummyData::komoditas()`.
     *
     * @return array<string, mixed>
     */
    private function baris(Komoditas $k): array
    {
        return [
            'id_komoditas' => $k->id_komoditas,
            'nama' => $k->nama,
            'tipe' => $k->tipe,
            'satuan' => $k->satuan?->nama,
            'satuan_id' => $k->satuan_id,
            'is_unggulan' => (bool) $k->is_unggulan,
            'deskripsi' => $k->deskripsi,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?Komoditas $komoditas = null): array
    {
        $data = $request->validate([
            'nama' => [
                'required', 'string', 'max:100',
                Rule::unique('komoditas', 'nama')->ignore($komoditas?->id_komoditas, 'id_komoditas'),
            ],
            'tipe' => ValidationRules::daftarPilihan(JenisDaftarPilihan::TipeKomoditas, wajib: true),
            'satuan_id' => ['required', 'integer', Rule::exists('satuan', 'id_satuan')],
            'is_unggulan' => ['nullable', 'boolean'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
        ], [
            'nama.required' => 'Nama komoditas wajib diisi.',
            'nama.unique' => 'Nama komoditas ini sudah terdaftar.',
            'tipe.required' => 'Tipe komoditas wajib dipilih.',
            'satuan_id.required' => 'Satuan panen baku wajib dipilih.',
        ] + ValidationRules::pesan());

        $data['is_unggulan'] = $request->boolean('is_unggulan');

        return $data;
    }
}
