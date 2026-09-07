<?php

namespace App\Http\Controllers;

use App\Enums\JenisDaftarPilihan;
use App\Models\DaftarPilihan;
use App\Models\ParameterPenilaianSp;
use App\Support\Paginasi;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Daftar Pilihan -- nilai dropdown yang dikelola Admin (Task 4.7).
 *
 * INDUK SELURUH DROPDOWN sistem: kondisi, sumber dana, jenis alsintan,
 * kategori/bidang/prioritas pengaduan, dan sepuluh daftar lain. Nilai baru
 * cukup INSERT tanpa ALTER TABLE (`data-dictionary.md` 5.6).
 *
 * TANPA RUTE HAPUS, dan itu disengaja: nilai yang tidak lagi dipakai
 * dinonaktifkan lewat `is_aktif`. Menghapusnya membuat data lama menunjuk
 * pilihan yang lenyap, dan rekapnya kehilangan baris itu tanpa pesan apa pun.
 */
class MasterDaftarPilihanController extends Controller
{
    public function index(Request $request): RedirectResponse|View
    {
        // Alamat lama `?tab={jenis}` dialihkan, bukan dibiarkan mati. Tautan
        // yang sudah tersimpan siapa pun tidak boleh mendarat di halaman
        // yang salah tanpa penjelasan.
        $tabLama = JenisDaftarPilihan::tryFrom((string) $request->query('tab'));

        if ($tabLama !== null) {
            return redirect()->route('daftar-pilihan.jenis', ['jenis' => $tabLama->value], 301);
        }

        $semua = DaftarPilihan::query()->orderBy('jenis')->orderBy('urutan')->get();

        return view('pages.master.daftar-pilihan', [
            'title' => 'Data Master Daftar Pilihan',
            'semua' => $semua->map(fn (DaftarPilihan $r) => $this->baris($r))->all(),
            'jumlah' => $semua->countBy(fn (DaftarPilihan $r) => $r->jenis->value)->all(),
            'nonaktif' => $semua->reject->is_aktif->countBy(fn (DaftarPilihan $r) => $r->jenis->value)->all(),
        ]);
    }

    public function jenis(string $jenis, Request $request): View
    {
        $pilihan = JenisDaftarPilihan::tryFrom($jenis);

        // Jenis karangan membalas 404, bukan halaman kosong: daftar yang
        // tidak ada dan daftar yang kebetulan masih kosong adalah dua
        // keadaan berbeda, dan menyamakannya membuat salah ketik tampak
        // seperti data yang belum diisi.
        abort_if($pilihan === null, 404);

        // `bidang` di-eager-load supaya nama bidang tiap baris tidak dibaca
        // satu per satu di dalam perulangan tabel.
        $query = DaftarPilihan::query()->with('bidang')->where('jenis', $pilihan->value);

        // Kawasan-penuh (jenis ini SELURUHNYA), bukan hasil halaman ini.
        $jumlahNonaktif = (clone $query)->where('is_aktif', false)->count();

        $baris = $query->orderBy('urutan')->paginate(Paginasi::perHalaman($request))->withQueryString();

        // $nilaiBidang hanya dipakai VIEW untuk baris yang TAMPIL, jadi cukup
        // dihitung dari model mentah HALAMAN INI -- diambil SEBELUM
        // ->through() menukar isi koleksi menjadi larik tampilan.
        $nilaiBidang = $baris->getCollection()->pluck('bidang')->filter()->unique('id_daftar_pilihan')
            ->mapWithKeys(fn (DaftarPilihan $b) => [$b->id_daftar_pilihan => $b->nilai])->all();

        $baris->through(fn (DaftarPilihan $r) => $this->baris($r));

        return view('pages.master.detail-daftar-pilihan', [
            'title' => $pilihan->label(),
            'jenis' => $pilihan,
            'baris' => $baris,
            'jumlahNonaktif' => $jumlahNonaktif,
            'nilaiBidang' => $nilaiBidang,
            'nilaiTetap' => $pilihan->nilaiTetap(),
            'dapatDitambah' => $pilihan->dapatDitambah(),
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        abort_unless(JenisDaftarPilihan::from($data['jenis'])->dapatDitambah(), 422, 'Daftar ini tidak menerima nilai baru dari halaman ini.');

        DB::transaction(function () use ($data, $request): void {
            $pilihan = DaftarPilihan::create($data + ['is_aktif' => $request->boolean('is_aktif', true)]);

            if ($pilihan->jenis->dirujukParameter()) {
                ParameterPenilaianSp::create([
                    'kode' => Str::limit(Str::slug($pilihan->nilai, '_'), 28, '').'_'.$pilihan->id_daftar_pilihan,
                    'nama' => $pilihan->nilai,
                    'tingkat' => 'Tersier',
                    'bobot' => 1,
                    'sumber' => $pilihan->jenis === JenisDaftarPilihan::JenisFasilitas ? 'Fasilitas' : 'Infrastruktur',
                    'daftar_pilihan_id' => $pilihan->id_daftar_pilihan,
                    'is_dinilai' => false,
                    'urutan' => (int) ParameterPenilaianSp::max('urutan') + 1,
                ]);
            }
        });

        return $this->kembali($data['jenis'], 'Pilihan baru tersimpan dan langsung tersedia pada form.');
    }

    /**
     * Penonaktifan hanya menyetel `is_aktif`; baris data lain yang sudah
     * memakai nilainya TIDAK disentuh sama sekali.
     */
    public function perbarui(Request $request, int $id): RedirectResponse
    {
        $daftarPilihan = DaftarPilihan::findOrFail($id);
        $data = $this->validasi($request, $daftarPilihan);

        if ($daftarPilihan->jenis->nilaiTetap()) {
            $data['nilai'] = $daftarPilihan->nilai;
            $data['is_aktif'] = true;
        }

        $daftarPilihan->update($data + ['is_aktif' => $data['is_aktif'] ?? $request->boolean('is_aktif')]);

        return $this->kembali($data['jenis'], 'Perubahan pilihan tersimpan.');
    }

    /**
     * Kembali ke halaman DAFTARNYA, bukan ke indeks: petugas baru saja
     * menyentuh satu nilai dan perlu melihat hasilnya pada daftar itu juga.
     */
    private function kembali(string $jenis, string $pesan): RedirectResponse
    {
        $pilihan = JenisDaftarPilihan::tryFrom($jenis);

        return redirect()->route(
            $pilihan !== null ? 'daftar-pilihan.jenis' : 'master.daftar-pilihan',
            $pilihan !== null ? ['jenis' => $pilihan->value] : [],
        )->with('sukses', $pesan);
    }

    /**
     * Bentuk larik yang dikenali tampilan. Nama kuncinya wajib sama dengan
     * `DummyData::daftarPilihan()` supaya view tidak perlu disentuh.
     *
     * @return array<string, mixed>
     */
    private function baris(DaftarPilihan $r): array
    {
        return [
            'id_daftar_pilihan' => $r->id_daftar_pilihan,
            'jenis' => $r->jenis->value,
            'jenis_label' => $r->jenis->label(),
            'nilai' => $r->nilai,
            'label' => $r->label,
            'kode_perilaku' => $r->kode_perilaku,
            'urutan' => $r->urutan,
            'nilai_skor' => $r->nilai_skor === null ? null : (float) $r->nilai_skor,
            'bidang_id' => $r->bidang_id,
            'is_aktif' => $r->is_aktif,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?DaftarPilihan $daftarPilihan = null): array
    {
        $jenis = JenisDaftarPilihan::tryFrom((string) $request->input('jenis'));

        $data = $request->validate([
            'jenis' => [
                'required', Rule::enum(JenisDaftarPilihan::class),
                Rule::when($daftarPilihan !== null, Rule::in([$daftarPilihan?->jenis->value])),
            ],
            'nilai' => [
                'required', 'string', 'max:100',
                Rule::when($daftarPilihan !== null, Rule::in([$daftarPilihan?->nilai])),
                // Unik DALAM jenisnya, bukan lintas jenis: "Lainnya" sah
                // muncul pada banyak daftar sekaligus.
                Rule::unique('daftar_pilihan', 'nilai')
                    ->where('jenis', $jenis?->value)
                    ->ignore($daftarPilihan?->id_daftar_pilihan, 'id_daftar_pilihan'),
            ],
            'label' => ['nullable', 'string', 'max:100'],
            'urutan' => ['nullable', 'integer', 'min:1', 'max:999'],
            // Hanya bermakna bagi jenis berskor (`kondisi`); dipakai
            // menghitung kondisi SP, sehingga rentangnya dikunci 0..1.
            'nilai_skor' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'kode_perilaku' => [
                Rule::requiredIf($jenis?->perilakuWajib() ?? false), 'nullable', 'string',
                Rule::when($jenis?->opsiPerilaku() !== [], Rule::in(array_keys($jenis->opsiPerilaku()))),
                Rule::when($daftarPilihan !== null, Rule::in([$daftarPilihan?->kode_perilaku])),
            ],
            // Hanya bermakna bagi `kategori_pengaduan` (self-FK).
            'bidang_id' => ['nullable', 'integer', Rule::exists('daftar_pilihan', 'id_daftar_pilihan')
                ->where('jenis', JenisDaftarPilihan::BidangPengaduan->value)],
        ], [
            'nilai.required' => 'Nilai pilihan wajib diisi.',
            'nilai.unique' => 'Nilai ini sudah ada pada daftar yang sama.',
            'jenis.required' => 'Jenis daftar wajib dipilih.',
        ] + ValidationRules::pesan());

        // Kolom yang tak berlaku bagi jenisnya DIKOSONGKAN, bukan dibiarkan
        // terbawa: skor pada daftar tak berskor tidak pernah dibaca siapa pun
        // dan hanya menyesatkan pembaca tabel.
        $data['label'] = ($data['label'] ?? null) ?: $data['nilai'];
        $data['nilai_skor'] = $jenis?->berskor() ? ($data['nilai_skor'] ?? null) : null;
        $data['kode_perilaku'] = $jenis?->opsiPerilaku() !== [] ? ($data['kode_perilaku'] ?? null) : null;
        $data['bidang_id'] = $jenis?->berbidang() ? ($data['bidang_id'] ?? null) : null;
        $data['urutan'] ??= (int) DaftarPilihan::where('jenis', $jenis?->value)->max('urutan') + 1;

        return $data;
    }
}
