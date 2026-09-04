<?php

namespace App\Http\Controllers;

use App\Models\Satuan;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Data master satuan jumlah beserta faktor konversinya ke ton (Task 4.5 / 6.7).
 *
 * `faktor_ke_ton` adalah PENGALI seluruh rekap panen: bila diisi, nilainya
 * wajib lebih besar dari nol -- faktor nol membuat volume panen lenyap dari
 * rekap tanpa memerahkan apa pun. Boleh KOSONG untuk satuan non-berat (Liter,
 * Rol pada saprotan) yang tidak pernah dikonversi ke ton.
 *
 * Mengubah faktor TIDAK menyentuh panen yang sudah tersimpan: tiap baris
 * `hasil_panen` menyalin `satuan_id`-nya sendiri (Task 3.1 B8).
 */
class MasterSatuanController extends Controller
{
    public function index(): View
    {
        return view('pages.master.satuan', [
            'title' => 'Data Master Satuan',
            'satuan' => Satuan::query()
                ->withCount('komoditas')
                ->orderByDesc('faktor_ke_ton')
                ->get()
                ->map(fn (Satuan $s) => [
                    'id_satuan' => $s->id_satuan,
                    'nama' => $s->nama,
                    'simbol' => $s->simbol,
                    'faktor_ke_ton' => $s->faktor_ke_ton === null ? null : (float) $s->faktor_ke_ton,
                    // Dipakai tampilan untuk memberi tahu bahwa satuan ini
                    // masih terpakai, sehingga tombol hapusnya akan ditolak.
                    'dipakai_komoditas' => $s->komoditas_count,
                ])
                ->all(),
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        Satuan::create($this->validasi($request));

        return redirect()->route('master.satuan')->with('sukses', 'Data master satuan tersimpan.');
    }

    public function perbarui(Request $request, int $id): RedirectResponse
    {
        $satuan = Satuan::findOrFail($id);
        $satuan->update($this->validasi($request, $satuan));

        return redirect()->route('master.satuan')->with('sukses', 'Perubahan data satuan tersimpan.');
    }

    /**
     * Penghapusan DITOLAK bila satuan masih dipakai komoditas mana pun.
     *
     * FK RESTRICT sudah menahannya, tetapi galat SQL mentah tidak dapat
     * ditindaklanjuti petugas. Diperiksa lebih dulu supaya alasannya
     * tersampaikan beserta jumlah komoditas yang menahannya.
     */
    public function hapus(int $id): RedirectResponse
    {
        $satuan = Satuan::findOrFail($id);
        $dipakai = $satuan->komoditas()->count();

        if ($dipakai > 0) {
            return back()->with('galat', 'Satuan ini masih dipakai '.$dipakai.' komoditas sehingga tidak dapat dihapus.');
        }

        $satuan->delete();

        return redirect()->route('master.satuan')->with('sukses', 'Data satuan dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?Satuan $satuan = null): array
    {
        return $request->validate([
            'nama' => [
                'required', 'string', 'max:50',
                Rule::unique('satuan', 'nama')->ignore($satuan?->id_satuan, 'id_satuan'),
            ],
            'simbol' => ['required', 'string', 'max:10'],
            // Boleh kosong (satuan non-berat). Bila diisi: `gt:0`, bukan sekadar
            // `numeric` -- faktor nol atau negatif membuat volume panen lenyap
            // atau berbalik tanda pada rekap.
            'faktor_ke_ton' => ['nullable', 'numeric', 'gt:0', 'max:1000000'],
        ], [
            'nama.required' => 'Nama satuan wajib diisi.',
            'nama.unique' => 'Nama satuan ini sudah terdaftar.',
            'simbol.required' => 'Simbol satuan wajib diisi.',
            'faktor_ke_ton.gt' => 'Faktor konversi wajib lebih besar dari nol.',
        ] + ValidationRules::pesan());
    }
}
