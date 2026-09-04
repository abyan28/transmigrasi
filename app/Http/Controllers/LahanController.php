<?php

namespace App\Http\Controllers;

use App\Enums\StatusSertifikat;
use App\Http\Controllers\Concerns\MenyimpanBerkas;
use App\Models\Lahan;
use App\Models\Transmigran;
use App\Support\DummyData;
use App\Support\Paginasi;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Modul Lahan (Task 6.1 + 6.2 + 6.3).
 *
 * SATU BARIS = SATU KELUARGA (`rules.md` 7.8/7.9): `UNIQUE (lahan.transmigran_id)`.
 * `luas_usaha` DITURUNKAN dari `luas_kering + luas_basah` (7.5) -- nilai yang
 * dikirim form diabaikan. `luas_pekarangan`/`luas_usaha` NULL = BELUM MENERIMA.
 *
 * SHM dan `status_sertifikat` diisi DARI FORM LAHAN tetapi tersimpan di sisi
 * KELUARGA (`transmigran.status_sertifikat` + `transmigran_berkas` peran `shm`,
 * `rules.md` 7.6a) -- SHM meliputi seluruh bidang satu KK. HPL (alas hak
 * kawasan) tetap bacaan, diunggah dari Data Kawasan.
 */
class LahanController extends Controller
{
    use MenyimpanBerkas;

    public function index(Request $request): View
    {
        $cari = trim((string) $request->query('cari', ''));
        $filterSp = $request->query('sp');
        $filterJenis = $request->query('peruntukan_lahan');
        $filterKategori = $request->query('kategori_lahan');
        $perHalaman = Paginasi::perHalaman($request);

        $query = Lahan::query()
            ->with(['transmigran.berkas', 'satuanPermukiman'])
            ->when($cari !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('kode_lahan', 'like', "%{$cari}%")
                ->orWhereHas('transmigran', fn ($t) => $t->where('nama_kepala_keluarga', 'like', "%{$cari}%"))))
            ->when($filterSp, fn ($q) => $q->where('satuan_permukiman_id', $filterSp))
            ->when($filterJenis === 'Lahan Pekarangan', fn ($q) => $q->whereNotNull('luas_pekarangan'))
            ->when($filterJenis === 'Lahan Usaha', fn ($q) => $q->whereNotNull('luas_usaha'))
            ->when($filterKategori === 'kering', fn ($q) => $q->where('luas_kering', '>', 0))
            ->when($filterKategori === 'basah', fn ($q) => $q->where('luas_basah', '>', 0));

        // Jumlah luas TERSARING (bukan cuma halaman ini) -- dihitung dari
        // klon query SEBELUM `->paginate()`, sebab rules.md 5.0b-1 poin 13
        // menuntut agregat mengikuti hasil query yang sudah tersaring/
        // ber-cakupan, dan paginasi hanya memotong TAMPILANNYA.
        $totalLuasTampil = (float) (clone $query)->sum('luas_pekarangan')
            + (float) (clone $query)->sum('luas_usaha');

        $baris = $query->orderBy('id_lahan')->paginate($perHalaman)->withQueryString();
        $baris->through(fn (Lahan $l) => $this->baris($l));

        return view('pages.lahan.index', [
            'title' => 'Data Lahan',
            'baris' => $baris,
            'cari' => $cari,
            'filterSp' => $filterSp,
            'filterJenis' => $filterJenis,
            'filterKategori' => $filterKategori,
            'adaFilter' => $cari !== '' || $filterSp || $filterJenis || $filterKategori,
            'totalLuasTampil' => $totalLuasTampil,
            // Kartu ringkasan kawasan-penuh, bukan hasil saringan.
            'luasPekarangan' => (float) Lahan::query()->sum('luas_pekarangan'),
            'luasUsaha' => (float) Lahan::query()->sum('luas_usaha'),
            'jumlahBidang' => Lahan::query()->whereNotNull('luas_pekarangan')->count()
                + Lahan::query()->whereNotNull('luas_usaha')->count(),
            'daftarSp' => DummyData::satuanPermukiman(),
        ]);
    }

    public function detail(int $id): View
    {
        $lahan = Lahan::with(['transmigran.berkas', 'satuanPermukiman'])->findOrFail($id);

        $data = $this->baris($lahan);
        $pemilik = $lahan->transmigran;

        return view('pages.lahan.detail', [
            'title' => 'Lahan '.$lahan->kode_lahan,
            'data' => $data,
            'pemilik' => $pemilik === null ? null : [
                'id_transmigran' => $pemilik->id_transmigran,
                'nama_kepala_keluarga' => $pemilik->nama_kepala_keluarga,
                'status_sertifikat' => $pemilik->status_sertifikat->value,
            ],
            'shm' => $data['shm_meta'],
            'hpl' => DummyData::berkasSatu('kawasan_transmigrasi_berkas', 'kawasan_transmigrasi_id', 1, 'hpl'),
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        [$lahanData, $statusSertifikat, $transmigranId] = $this->pisahkan($this->validasi($request));

        DB::transaction(function () use ($request, $lahanData, $statusSertifikat, $transmigranId) {
            $lahan = Lahan::create($lahanData + ['uuid' => (string) Str::uuid()]);

            $this->simpanLegalitas($request, (int) $transmigranId, $statusSertifikat);
        });

        return redirect()->route('lahan.index')->with('sukses', 'Data lahan tersimpan.');
    }

    public function perbarui(Request $request, int $id): RedirectResponse
    {
        $lahan = Lahan::findOrFail($id);

        [$lahanData, $statusSertifikat, $transmigranId] = $this->pisahkan($this->validasi($request, $lahan));

        DB::transaction(function () use ($request, $lahan, $lahanData, $statusSertifikat, $transmigranId) {
            $lahan->update($lahanData);

            $this->simpanLegalitas($request, (int) $transmigranId, $statusSertifikat);
        });

        return redirect()->route('lahan.detail', $id)->with('sukses', 'Perubahan data lahan tersimpan.');
    }

    public function hapus(int $id): RedirectResponse
    {
        Lahan::findOrFail($id)->delete();

        return redirect()->route('lahan.index')->with('sukses', 'Data lahan dihapus.');
    }

    /**
     * Menulis legalitas ke sisi KELUARGA: `status_sertifikat` pada baris
     * `transmigran`, berkas SHM pada `transmigran_berkas` peran `shm` (satu SHM
     * per KK -- unggahan baru menggantikan yang lama).
     */
    private function simpanLegalitas(Request $request, int $transmigranId, string $statusSertifikat): void
    {
        $transmigran = Transmigran::findOrFail($transmigranId);
        $transmigran->update(['status_sertifikat' => $statusSertifikat]);

        if ($request->hasFile('shm')) {
            $transmigran->berkas()->wherePivot('peran', 'shm')->detach();
            $this->lekatkanBerkas($transmigran, array_filter([$request->file('shm')]), 'transmigran', 'shm');
        }
    }

    /**
     * Memisahkan isian sisi-keluarga dari kolom `lahan`, dan MENURUNKAN
     * `luas_usaha` dari kering + basah (`rules.md` 7.5).
     *
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: string, 2: int}
     */
    private function pisahkan(array $data): array
    {
        $statusSertifikat = $data['status_sertifikat'];
        $transmigranId = (int) $data['transmigran_id'];

        unset($data['status_sertifikat'], $data['shm']);

        $kering = $data['luas_kering'] ?? null;
        $basah = $data['luas_basah'] ?? null;

        if (($kering === null || $kering === '') && ($basah === null || $basah === '')) {
            // Belum menerima lahan usaha: seluruh kolom usaha NULL.
            $data['luas_kering'] = null;
            $data['luas_basah'] = null;
            $data['luas_usaha'] = null;
        } else {
            $data['luas_kering'] = (float) ($kering ?: 0);
            $data['luas_basah'] = (float) ($basah ?: 0);
            $data['luas_usaha'] = $data['luas_kering'] + $data['luas_basah'];
        }

        return [$data, $statusSertifikat, $transmigranId];
    }

    /**
     * Larik ber-kunci PERSIS satu baris `DummyData::lahan()` (+`tujuan_pemanfaatan`
     * dan `keterangan` yang dibaca form & rincian).
     *
     * @return array<string, mixed>
     */
    private function baris(Lahan $l): array
    {
        // SHM tersimpan pada `transmigran_berkas` peran `shm` -- milik keluarga,
        // bukan bidang (`rules.md` 7.6c). Dibaca dari relasi Eloquent.
        $shmBerkas = $l->transmigran?->berkas->firstWhere('pivot.peran', 'shm');
        $shmMeta = $shmBerkas === null ? null : ['nama_file' => $shmBerkas->nama_file];

        return [
            'id_lahan' => $l->id_lahan,
            'kode_lahan' => $l->kode_lahan,
            'transmigran_id' => $l->transmigran_id,
            'pemilik' => $l->transmigran?->nama_kepala_keluarga,
            'satuan_permukiman' => $l->satuanPermukiman?->nama,
            'satuan_permukiman_id' => $l->satuan_permukiman_id,
            'luas_pekarangan' => $l->luas_pekarangan === null ? null : (float) $l->luas_pekarangan,
            'lintang_pekarangan' => $l->lintang_pekarangan === null ? null : (float) $l->lintang_pekarangan,
            'bujur_pekarangan' => $l->bujur_pekarangan === null ? null : (float) $l->bujur_pekarangan,
            'luas_usaha' => $l->luas_usaha === null ? null : (float) $l->luas_usaha,
            'luas_kering' => $l->luas_kering === null ? null : (float) $l->luas_kering,
            'luas_basah' => $l->luas_basah === null ? null : (float) $l->luas_basah,
            'lintang_usaha' => $l->lintang_usaha === null ? null : (float) $l->lintang_usaha,
            'bujur_usaha' => $l->bujur_usaha === null ? null : (float) $l->bujur_usaha,
            'tujuan_pemanfaatan' => $l->tujuan_pemanfaatan,
            'keterangan' => $l->keterangan,
            'status_sertifikat' => $l->transmigran?->status_sertifikat->value ?? StatusSertifikat::BelumDidata->value,
            'shm' => $shmMeta['nama_file'] ?? null,
            'shm_meta' => $shmMeta,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?Lahan $lahan = null): array
    {
        return $request->validate([
            'transmigran_id' => [
                'required', 'integer',
                Rule::exists('transmigran', 'id_transmigran'),
                Rule::unique('lahan', 'transmigran_id')->ignore($lahan?->id_lahan, 'id_lahan'),
            ],
            'satuan_permukiman_id' => ['required', 'integer', Rule::exists('satuan_permukiman', 'id_satuan_permukiman')],
            'kode_lahan' => [
                'nullable', 'string', 'max:50',
                Rule::unique('lahan', 'kode_lahan')->ignore($lahan?->id_lahan, 'id_lahan'),
            ],
            'tujuan_pemanfaatan' => ['nullable', 'string', 'max:2000'],
            'keterangan' => ['nullable', 'string', 'max:1000'],

            'luas_pekarangan' => ValidationRules::luas(wajib: false),
            'lintang_pekarangan' => ValidationRules::lintang(),
            'bujur_pekarangan' => ValidationRules::bujur(),
            'luas_kering' => ['nullable', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999'],
            'luas_basah' => ['nullable', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999'],
            'luas_usaha' => ['nullable', 'numeric'],
            'lintang_usaha' => ValidationRules::lintang(),
            'bujur_usaha' => ValidationRules::bujur(),

            'status_sertifikat' => ['required', Rule::enum(StatusSertifikat::class)],
            'shm' => ValidationRules::dokumen(),
        ], [
            'transmigran_id.required' => 'Pemilik lahan wajib dipilih.',
            'transmigran_id.unique' => 'Keluarga ini sudah punya baris lahan. Gunakan tombol Ubah pada daftar.',
            'satuan_permukiman_id.required' => 'Satuan permukiman wajib dipilih.',
            'kode_lahan.unique' => 'Kode lahan ini sudah dipakai.',
            'status_sertifikat.required' => 'Status sertifikat wajib dipilih.',
        ] + ValidationRules::pesan());
    }
}
