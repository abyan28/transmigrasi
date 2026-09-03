<?php

namespace App\Http\Controllers;

use App\Enums\TingkatKebutuhan;
use App\Models\ParameterPenilaianSp;
use App\Models\StatusKondisiSp;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Parameter bobot + ambang status penilaian kondisi SP (Task 4.8).
 *
 * Keduanya keputusan KEBIJAKAN yang wajib divalidasi dinas (`rules.md` 10c
 * poin 13), bukan angka teknis -- karena itu dikelola lewat antarmuka.
 *
 * Penilaian yang SUDAH tersimpan tidak dihitung ulang: `penilaian_sp.rincian`
 * menyalin bobot yang berlaku saat penilaian dibuat (`rules.md` 10c.6).
 * Perubahan di sini hanya berlaku pada penilaian berikutnya.
 */
class PenilaianKondisiController extends Controller
{
    public function index(): View
    {
        $parameter = ParameterPenilaianSp::query()
            ->with('referensi')
            ->orderBy('urutan')
            ->get()
            ->map(fn (ParameterPenilaianSp $p) => [
                'id_parameter_penilaian_sp' => $p->id_parameter_penilaian_sp,
                'kode' => $p->kode,
                'nama' => $p->nama,
                // Dikembalikan sebagai ENUM: view memanggil ->value padanya.
                // Kolomnya ENUM di skema tetapi model belum meng-cast-nya, sehingga
                // Eloquent menyerahkannya sebagai string biasa.
                'tingkat' => TingkatKebutuhan::tryFrom((string) $p->tingkat),
                'bobot' => (float) $p->bobot,
                'sumber' => $p->sumber,
                'referensi_id' => $p->referensi_id,
                // Nama jenis yang ditunjuk parameter ini. Dibaca lewat relasi
                // ter-eager-load, bukan kueri per baris.
                'jenis' => $p->referensi?->jenis?->value,
                'nilai_jenis' => $p->referensi?->nilai,
                'is_dinilai' => $p->is_dinilai,
                'urutan' => $p->urutan,
            ])
            ->all();

        $dinilai = array_filter($parameter, fn (array $p) => $p['is_dinilai']);

        // Dikelompokkan per sumber, sebab keduanya dibaca dari tabel berbeda
        // dan petugas mencarinya lewat modul tempat ia mendata asetnya.
        $perSumber = [];
        foreach ($parameter as $p) {
            $perSumber[$p['sumber']][] = $p;
        }

        return view('pages.master.penilaian-kondisi', [
            'title' => 'Penilaian Kondisi SP',
            'parameter' => $parameter,
            'status' => $this->daftarStatus(),
            'dinilai' => $dinilai,
            'totalBobot' => array_sum(array_column($dinilai, 'bobot')),
            'perSumber' => $perSumber,
        ]);
    }

    public function parameter(Request $request, int $id): RedirectResponse
    {
        $parameter = ParameterPenilaianSp::findOrFail($id);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            // INTEGER, bukan numeric: kolomnya TINYINT UNSIGNED di skema,
            // sehingga 12,5 akan dibulatkan diam-diam menjadi 13 -- petugas
            // menyimpan satu angka lalu membaca angka lain tanpa peringatan.
            'bobot' => ['required', 'integer', 'min:0', 'max:100'],
            'is_dinilai' => ['nullable', 'boolean'],
        ], [
            'nama.required' => 'Nama parameter wajib diisi.',
            'bobot.required' => 'Bobot wajib diisi.',
            'bobot.integer' => 'Bobot wajib berupa bilangan bulat.',
            'bobot.max' => 'Bobot tidak boleh melebihi 100.',
        ] + ValidationRules::pesan());

        $parameter->update([
            'nama' => $data['nama'],
            'bobot' => $data['bobot'],
            'is_dinilai' => $request->boolean('is_dinilai'),
        ]);

        return redirect()->route('master.penilaian-kondisi')
            ->with('sukses', 'Parameter penilaian tersimpan dan berlaku pada penilaian berikutnya.');
    }

    /**
     * Ambang WAJIB menurun menurut urutan status.
     *
     * Pembacaan status berhenti pada ambang tertinggi yang cocok, sehingga
     * ambang Mandiri yang lebih kecil daripada Berkembang membuat Berkembang
     * MUSTAHIL dicapai -- seluruh SP di rentang itu akan terbaca Mandiri.
     * Kegagalan senyap: tak ada galat, hanya satu status yang lenyap dari
     * kawasan tanpa ada yang menyadarinya.
     */
    public function status(Request $request, string $kode): RedirectResponse
    {
        $status = StatusKondisiSp::where('kode', $kode)->first();

        abort_if($status === null, 404);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:50'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'ambang_bawah' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'nama.required' => 'Nama status wajib diisi.',
            'ambang_bawah.required' => 'Ambang bawah wajib diisi.',
        ] + ValidationRules::pesan());

        $this->pastikanAmbangMenurun($status, (float) $data['ambang_bawah']);

        $status->update($data);

        return redirect()->route('master.penilaian-kondisi')->with('sukses', 'Status kondisi SP tersimpan.');
    }

    /**
     * Menolak ambang yang merusak urutan menurun.
     *
     * Diperiksa terhadap TETANGGA langsungnya saja: status di atasnya wajib
     * berambang lebih besar, status di bawahnya lebih kecil. Memeriksa seluruh
     * daftar tidak diperlukan sebab urutannya sudah terjaga sebelum ini.
     */
    private function pastikanAmbangMenurun(StatusKondisiSp $status, float $ambang): void
    {
        $atas = StatusKondisiSp::where('urutan', '<', $status->urutan)
            ->orderByDesc('urutan')->first();

        $bawah = StatusKondisiSp::where('urutan', '>', $status->urutan)
            ->orderBy('urutan')->first();

        if ($atas !== null && $ambang >= (float) $atas->ambang_bawah) {
            throw ValidationException::withMessages([
                'ambang_bawah' => 'Ambang wajib lebih kecil daripada '.$atas->nama.' ('.(float) $atas->ambang_bawah.').',
            ]);
        }

        if ($bawah !== null && $ambang <= (float) $bawah->ambang_bawah) {
            throw ValidationException::withMessages([
                'ambang_bawah' => 'Ambang wajib lebih besar daripada '.$bawah->nama.' ('.(float) $bawah->ambang_bawah.').',
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function daftarStatus(): array
    {
        return StatusKondisiSp::query()->orderBy('urutan')->get()
            ->map(fn (StatusKondisiSp $s) => [
                'kode' => $s->kode,
                'nama' => $s->nama,
                'keterangan' => $s->keterangan,
                'ambang_bawah' => (float) $s->ambang_bawah,
                'warna' => $s->warna,
                'urutan' => $s->urutan,
            ])
            ->all();
    }
}
