<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\MenyimpanBerkas;
use App\Models\KawasanTransmigrasi;
use App\Support\DummyData;
use App\Support\Paginasi;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Kawasan transmigrasi beserta berkas alas haknya (Task 4.1b).
 *
 * **HPL melekat pada KAWASAN, bukan pada bidang lahan** (`rules.md` 7.4a):
 * ia Hak Pengelolaan milik instansi atas tanah kawasan, sehingga tidak pernah
 * menjadi hak seorang transmigran. SHM yang memang milik perorangan diunggah
 * dari form lahan (Tahap 6).
 *
 * Unggahan sungguhan PERTAMA di proyek ini: registry `berkas` +
 * `PenyimpananDokumen` mulai dipakai nyata, tidak lagi berhenti di data
 * contoh. Berkas masuk cakram privat; basis data hanya menyimpan path.
 *
 * Kawasan tak punya halaman rincian sendiri -- berkasnya dibuka dari kartu
 * di `/kawasan`.
 */
class KawasanController extends Controller
{
    use MenyimpanBerkas;

    public function index(Request $request): View
    {
        // `berkas` di-eager-load supaya daftar berkas tiap kawasan tidak
        // dibaca satu per satu di dalam perulangan kartu (N+1, notes.md 1g.5).
        $kawasan = KawasanTransmigrasi::query()->with(['berkas', 'kabupaten.provinsi'])
            ->withCount('satuanPermukiman')
            ->orderBy('nama')
            ->paginate(Paginasi::perHalaman($request))
            ->withQueryString();

        // $berkasKawasan hanya dipakai VIEW untuk kartu yang TAMPIL, jadi
        // cukup dihitung dari model mentah HALAMAN INI -- diambil SEBELUM
        // ->through() menukar isi koleksi menjadi larik tampilan.
        $berkasKawasan = $kawasan->getCollection()->mapWithKeys(fn (KawasanTransmigrasi $k) => [
            $k->id_kawasan_transmigrasi => $k->berkas->map(fn ($b) => [
                'nama_file' => $b->nama_file,
                'peran' => $b->pivot->peran,
                'keterangan' => $b->keterangan,
            ])->all(),
        ])->all();

        $kawasan->through(fn (KawasanTransmigrasi $k) => [
            'id_kawasan_transmigrasi' => $k->id_kawasan_transmigrasi,
            'nama' => $k->nama,
            'kode_kawasan' => $k->kode_kawasan,
            'tahun_penetapan' => $k->tahun_penetapan,
            'nomor_sk' => $k->nomor_sk,
            'luas_total' => (float) $k->luas_total,
            'keterangan' => $k->keterangan,
            'kabupaten_id' => $k->kabupaten_id,
            // Label tampilan; kebenarannya tetap kabupaten_id. Dibaca
            // lewat relasi yang sudah di-eager-load, bukan kueri per kartu.
            'kabupaten' => $k->kabupaten?->nama,
            'provinsi' => $k->kabupaten?->provinsi?->nama,
            'jumlah_sp' => $k->satuan_permukiman_count,
        ]);

        $daftarSp = DummyData::satuanPermukiman();
        $rekap = DummyData::rekapPerSp();

        return view('pages.sp.kawasan', [
            'title' => 'Kawasan Transmigrasi',
            'kawasan' => $kawasan,
            'berkasKawasan' => $berkasKawasan,
            'daftarSp' => $daftarSp,
            'rekap' => $rekap,
            'totalKk' => array_sum(array_column($rekap, 'jumlah_kk')),
            'kecamatan' => array_unique(array_column($daftarSp, 'kecamatan')),
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $kawasan = KawasanTransmigrasi::create($this->validasi($request));

        $this->simpanBerkasKawasan($request, $kawasan);

        return redirect()->route('kawasan')->with('sukses', 'Data kawasan transmigrasi tersimpan.');
    }

    public function perbarui(Request $request, int $id): RedirectResponse
    {
        $kawasan = KawasanTransmigrasi::findOrFail($id);
        $kawasan->update($this->validasi($request, $kawasan));

        $this->simpanBerkasKawasan($request, $kawasan);

        return redirect()->route('kawasan')->with('sukses', 'Perubahan data kawasan tersimpan.');
    }

    /**
     * Penghapusan DITOLAK bila kawasan masih menaungi SP.
     *
     * FK RESTRICT menahannya, tetapi galat SQL mentah tidak dapat
     * ditindaklanjuti petugas.
     */
    public function hapus(int $id): RedirectResponse
    {
        $kawasan = KawasanTransmigrasi::findOrFail($id);
        $jumlahSp = $kawasan->satuanPermukiman()->count();

        if ($jumlahSp > 0) {
            return back()->with('galat', 'Kawasan ini masih menaungi '.$jumlahSp.' satuan permukiman sehingga tidak dapat dihapus.');
        }

        // Pivot ikut lepas; baris registry `berkas` TIDAK ikut hilang
        // (Task 3.1 B4) sebab registry melayani banyak modul.
        $kawasan->berkas()->detach();
        $kawasan->delete();

        return redirect()->route('kawasan')->with('sukses', 'Data kawasan dihapus.');
    }

    /**
     * Berkas kawasan berperan `hpl`, `sk`, atau `peta` (Putaran 12 keputusan 7).
     *
     * Form mengirim satu isian multi-berkas `dokumen_kawasan[]`, sehingga peran
     * tiap berkas belum dapat dibedakan dari sana. Seluruhnya direkam berperan
     * `sk` -- peran bawaan yang sama dipakai `DummyData` -- dan penajaman peran
     * per berkas menunggu isian pemilihnya ada di form (perubahan UI).
     */
    private function simpanBerkasKawasan(Request $request, KawasanTransmigrasi $kawasan): void
    {
        $this->lekatkanBerkas(
            pemilik: $kawasan,
            berkasDiunggah: (array) $request->file('dokumen_kawasan', []),
            modul: 'kawasan_transmigrasi',
            peran: 'sk',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?KawasanTransmigrasi $kawasan = null): array
    {
        $data = $request->validate([
            'nama' => [
                'required', 'string', 'max:100',
                Rule::unique('kawasan_transmigrasi', 'nama')
                    ->ignore($kawasan?->id_kawasan_transmigrasi, 'id_kawasan_transmigrasi'),
            ],
            'kabupaten_id' => ['required', 'integer', Rule::exists('kabupaten', 'id_kabupaten')],
            'kode_kawasan' => ['nullable', 'string', 'max:20'],
            'tahun_penetapan' => ValidationRules::tahun(),
            'nomor_sk' => ['nullable', 'string', 'max:100'],
            'luas_total' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            // Batas 5 MB berlaku PER BERKAS, bukan total (Putaran 14).
            'dokumen_kawasan' => ['nullable', 'array'],
            'dokumen_kawasan.*' => ValidationRules::dokumen(),
        ], [
            'nama.required' => 'Nama kawasan wajib diisi.',
            'nama.unique' => 'Nama kawasan ini sudah terdaftar.',
            'kabupaten_id.required' => 'Kabupaten wajib dipilih.',
        ] + ValidationRules::pesan());

        unset($data['dokumen_kawasan']);

        return $data;
    }
}
