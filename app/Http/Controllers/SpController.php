<?php

namespace App\Http\Controllers;

use App\Models\SatuanPermukiman;
use App\Support\DummyData;
use App\Support\Paginasi;
use App\Support\PenilaianKondisiSp;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Satuan permukiman -- INDUK inventaris, fasilitas, dan infrastruktur SP
 * (Task 4.2), sehingga dikerjakan sebelum ketiganya.
 *
 * `jumlah_kk_terisi` BUKAN kolom: ia turunan cacah transmigran. Selama Tahap 5
 * belum berjalan, angkanya masih dibaca dari `DummyData::rekapPerSp()` --
 * dicatat di sini supaya tidak ada yang mengira nilainya sudah nyata.
 */
class SpController extends Controller
{
    /**
     * Pasangan min/maks keadaan wilayah: awalan => [akhiran satuan, batas atas].
     *
     * Ditulis sekali di sini supaya aturan dan pesannya tidak dapat berselisih:
     * menambah pasangan baru cukup menyentuh satu tempat.
     *
     * @var array<string, array{0: string, 1: int|float}>
     */
    private const RENTANG = [
        'kemiringan' => ['_persen', 100],
        'curah_hujan_bulan' => ['_mm', 10000],
        'suhu' => ['_c', 60],
        'angin' => ['_knot', 200],
        'penyinaran' => ['_persen', 100],
    ];

    public function index(Request $request): View
    {
        $cari = trim((string) $request->query('cari', ''));
        $filterKecamatan = $request->query('kecamatan');
        $perHalaman = Paginasi::perHalaman($request);

        $baris = SatuanPermukiman::query()
            ->with(['desa.kecamatan', 'kawasan'])
            ->when($cari !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('nama', 'like', "%{$cari}%")
                ->orWhereHas('desa', fn ($d) => $d->where('nama', 'like', "%{$cari}%"))))
            ->when($filterKecamatan, fn ($q) => $q->whereHas('desa.kecamatan', fn ($k) => $k->where('nama', $filterKecamatan)))
            ->orderBy('kode_sp')
            ->paginate($perHalaman)
            ->withQueryString();

        // Turunan cacah transmigran; masih data contoh sampai Tahap 5 --
        // dibaca terlepas dari halaman/saringan, sebab jumlah SP-nya sendiri
        // (bukan turunannya) yang menentukan berapa baris tampil.
        $terisi = collect(DummyData::rekapPerSp())->keyBy('satuan_permukiman_id');

        $baris->through(fn (SatuanPermukiman $sp) => $this->baris($sp, $terisi));

        return view('pages.sp.index', [
            'title' => 'Satuan Permukiman',
            'baris' => $baris,
            'rekap' => $terisi,
            'kondisi' => collect(PenilaianKondisiSp::nilaiSeluruhSp())->keyBy('satuan_permukiman_id'),
            'cari' => $cari,
            'filterKecamatan' => $filterKecamatan,
            'adaFilter' => $cari !== '' || $filterKecamatan,
            // Kartu ringkasan kawasan-penuh, bukan hasil saringan/halaman ini.
            'jumlahSp' => SatuanPermukiman::query()->count(),
            'daftarKecamatan' => SatuanPermukiman::query()
                ->join('desa', 'desa.id_desa', '=', 'satuan_permukiman.desa_id')
                ->join('kecamatan', 'kecamatan.id_kecamatan', '=', 'desa.kecamatan_id')
                ->distinct()->orderBy('kecamatan.nama')->pluck('kecamatan.nama')->all(),
            'totalLuas' => (float) SatuanPermukiman::query()->sum('luas_lahan'),
            'totalRencana' => (int) SatuanPermukiman::query()->sum('jumlah_kk_rencana'),
            'totalTerisi' => array_sum(array_column(DummyData::rekapPerSp(), 'jumlah_kk')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function baris(SatuanPermukiman $sp, Collection $terisi): array
    {
        return [
            'id_satuan_permukiman' => $sp->id_satuan_permukiman,
            'nama' => $sp->nama,
            'kode_sp' => $sp->kode_sp,
            'desa' => $sp->desa?->nama,
            'kecamatan' => $sp->desa?->kecamatan?->nama,
            'kawasan' => $sp->kawasan?->nama,
            'tahun_penempatan' => $sp->tahun_penempatan,
            'luas_lahan' => (float) $sp->luas_lahan,
            'jumlah_kk_rencana' => $sp->jumlah_kk_rencana,
            // Turunan cacah transmigran; masih data contoh sampai Tahap 5.
            'jumlah_kk_terisi' => $terisi[$sp->id_satuan_permukiman]['jumlah_kk'] ?? 0,
            'lintang' => $sp->lintang === null ? null : (float) $sp->lintang,
            'bujur' => $sp->bujur === null ? null : (float) $sp->bujur,
            'keterangan' => $sp->keterangan,
            'berkas_id' => $sp->berkas_id,
            'desa_id' => $sp->desa_id,
            'kawasan_id' => $sp->kawasan_id,
        ];
    }

    public function simpan(Request $request): RedirectResponse
    {
        SatuanPermukiman::create($this->validasi($request));

        return redirect()->route('sp.index')->with('sukses', 'Data satuan permukiman tersimpan.');
    }

    public function perbarui(Request $request, int $sp): RedirectResponse
    {
        $model = SatuanPermukiman::findOrFail($sp);
        $model->update($this->validasi($request, $model));

        return back()->with('sukses', 'Perubahan data satuan permukiman tersimpan.');
    }

    /**
     * Penghapusan DITOLAK bila SP masih menaungi data turunan.
     *
     * Diperiksa satu per satu supaya alasannya menyebut MODUL MANA yang
     * menahan; galat FK mentah hanya menyebut nama tabel.
     */
    public function hapus(int $id): RedirectResponse
    {
        $sp = SatuanPermukiman::findOrFail($id);

        $turunan = [
            'transmigran' => 'keluarga transmigran',
            'rumah' => 'rumah',
            'inventaris' => 'inventaris',
            'fasilitas' => 'fasilitas',
            'poktan' => 'kelompok tani',
            'lahan' => 'bidang lahan',
            'pengaduan' => 'pengaduan',
        ];

        foreach ($turunan as $relasi => $sebutan) {
            $jumlah = $sp->{$relasi}()->count();

            if ($jumlah > 0) {
                return back()->with('galat', 'SP ini masih menaungi '.$jumlah.' '.$sebutan.' sehingga tidak dapat dihapus.');
            }
        }

        $sp->delete();

        return redirect()->route('sp.index')->with('sukses', 'Data satuan permukiman dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?SatuanPermukiman $sp = null): array
    {
        $abaikan = $sp?->id_satuan_permukiman;

        $data = $request->validate([
            'nama' => [
                'required', 'string', 'max:100',
                Rule::unique('satuan_permukiman', 'nama')->ignore($abaikan, 'id_satuan_permukiman'),
            ],
            'kode_sp' => [
                'nullable', 'string', 'max:20',
                Rule::unique('satuan_permukiman', 'kode_sp')->ignore($abaikan, 'id_satuan_permukiman'),
            ],
            'kawasan_id' => ['required', 'integer', Rule::exists('kawasan_transmigrasi', 'id_kawasan_transmigrasi')],
            'desa_id' => ['required', 'integer', Rule::exists('desa', 'id_desa')],
            'tahun_penempatan' => ValidationRules::tahun(),
            'luas_lahan' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'jumlah_kk_rencana' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'keterangan' => ['nullable', 'string', 'max:1000'],

            // Keadaan wilayah (Rombongan C). Seluruhnya opsional: SP lama
            // kerap belum punya datanya, dan mewajibkannya membuat
            // penyuntingan hal lain ikut tertahan.
            'nomor_sk_pencadangan' => ['nullable', 'string', 'max:100'],
            'tanggal_sk_pencadangan' => ['nullable', 'date'],
            'pola_permukiman' => ['nullable', 'string', 'max:50'],
            'tingkat_kesuburan_tanah' => ['nullable', 'string', 'max:50'],
            'bentuk_wilayah' => ['nullable', 'string', 'max:50'],
            'sumber_air_bersih' => ['nullable', 'string', 'max:100'],
            'sumber_air_pertanian' => ['nullable', 'string', 'max:100'],
        ] +
            $this->aturanRentang() + [
                'ph_tanah_min' => ['nullable', 'numeric', 'min:0', 'max:14'],
                'ph_tanah_maks' => ['nullable', 'numeric', 'min:0', 'max:14', 'gte:ph_tanah_min'],
            ], [
                'nama.required' => 'Nama satuan permukiman wajib diisi.',
                'nama.unique' => 'Nama SP ini sudah terdaftar.',
                'kode_sp.unique' => 'Kode SP ini sudah dipakai SP lain.',
                'kawasan_id.required' => 'Kawasan transmigrasi wajib dipilih.',
                'desa_id.required' => 'Desa wajib dipilih.',
                'ph_tanah_maks.gte' => 'pH maksimum tidak boleh lebih kecil dari pH minimum.',
            ] + $this->pesanRentang() + ValidationRules::pesan());

        return $data;
    }

    /**
     * Pasangan min/maks keadaan wilayah.
     *
     * Maks WAJIB `gte` minnya. Tanpa itu petugas dapat menyimpan rentang
     * terbalik (mis. curah hujan 3000-500) yang lolos diam-diam lalu terbaca
     * sebagai rentang kosong pada Laporan Monografi SP.
     *
     * @return array<string, array<int, string>>
     */
    private function aturanRentang(): array
    {
        $aturan = [];

        foreach (self::RENTANG as $awalan => [$akhiran, $maksNilai]) {
            $min = $awalan.'_min'.$akhiran;
            $maks = $awalan.'_maks'.$akhiran;

            $aturan[$min] = ['nullable', 'numeric', 'min:0', 'max:'.$maksNilai];
            $aturan[$maks] = ['nullable', 'numeric', 'min:0', 'max:'.$maksNilai, 'gte:'.$min];
        }

        return $aturan;
    }

    /**
     * @return array<string, string>
     */
    private function pesanRentang(): array
    {
        $pesan = [];

        foreach (self::RENTANG as $awalan => [$akhiran]) {
            $pesan[$awalan.'_maks'.$akhiran.'.gte'] = 'Nilai maksimum tidak boleh lebih kecil dari minimumnya.';
        }

        return $pesan;
    }
}
