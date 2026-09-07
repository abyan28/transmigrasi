<?php

namespace App\Http\Controllers;

use App\Enums\JenisDaftarPilihan;
use App\Enums\StatusHunian;
use App\Http\Controllers\Concerns\MenyimpanBerkas;
use App\Models\DaftarPilihan;
use App\Models\Rumah;
use App\Models\SatuanPermukiman;
use App\Models\Scopes\CakupanDataSp;
use App\Models\Transmigran;
use App\Support\OperasiRumah;
use App\Support\Paginasi;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Modul Rumah dan Hunian (Task 5.3 + 5.4).
 *
 * Relasi rumah <-> KK satu-ke-satu, ditegakkan `UNIQUE (rumah.transmigran_id)`
 * di basis data (`rules.md` 6a.6). Pergantian penghuni TIDAK menimpa data lama:
 * baris `riwayat_penghunian` yang terbuka ditutup (`tahun_selesai_menghuni`) dan baris
 * baru dibuka (`rules.md` 6a.9). Penghuni sekarang dibaca halaman rincian dari
 * riwayat yang belum punya `tahun_selesai_menghuni`, bukan dari kolom -- keduanya
 * dijaga sepadan di sini.
 */
class RumahController extends Controller
{
    use MenyimpanBerkas;

    public function index(Request $request): View
    {
        $cari = trim((string) $request->query('cari', ''));
        $filterSp = $request->query('sp');
        $filterKondisi = $request->query('kondisi');
        $filterHunian = $request->query('status_hunian');
        $perHalaman = Paginasi::perHalaman($request);

        $baris = Rumah::query()
            ->with(['satuanPermukiman', 'penghuni'])
            ->when($cari !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('no_rumah', 'like', "%{$cari}%")
                ->orWhereHas('penghuni', fn ($p) => $p->where('nama_kepala_keluarga', 'like', "%{$cari}%"))))
            ->when($filterSp, fn ($q) => $q->where('satuan_permukiman_id', $filterSp))
            ->when($filterKondisi, fn ($q) => $q->where('kondisi', $filterKondisi))
            ->when($filterHunian, fn ($q) => $q->where('status_hunian', $filterHunian))
            ->orderBy('id_rumah')
            ->paginate($perHalaman)
            ->withQueryString();

        $baris->through(fn (Rumah $r) => $this->baris($r));

        return view('pages.rumah.index', [
            'title' => 'Rumah dan Hunian',
            'baris' => $baris,
            'cari' => $cari,
            'filterSp' => $filterSp,
            'filterKondisi' => $filterKondisi,
            'filterHunian' => $filterHunian,
            'adaFilter' => $cari !== '' || $filterSp || $filterKondisi || $filterHunian,
            'jumlahRumah' => Rumah::query()->count(),
            'jumlahDihuni' => Rumah::query()->where('status_hunian', StatusHunian::Dihuni->value)->count(),
            'jumlahRusak' => Rumah::query()->whereNot('kondisi', 'Tidak Rusak')->count(),
            'daftarSp' => $this->daftarSp(),
            'opsiFilterKondisiRumah' => $this->opsiFilter(JenisDaftarPilihan::KondisiRumah),
            'opsiFilterStatusHunian' => $this->opsiFilter(JenisDaftarPilihan::StatusHunian),
        ]);
    }

    public function detail(int $id): View
    {
        $rumah = Rumah::with(['satuanPermukiman', 'penghuni', 'berkas'])->findOrFail($id);

        return view('pages.rumah.detail', [
            'title' => 'Rumah '.$rumah->no_rumah,
            'data' => $this->baris($rumah),
            'riwayat' => $rumah->riwayatPenghunian()
                ->with(['transmigran' => fn ($q) => $q->withTrashed()])
                ->orderBy('id_riwayat_penghunian')
                ->get()
                ->map(fn ($r) => [
                    'id_riwayat_penghunian' => $r->id_riwayat_penghunian,
                    'transmigran_id' => $r->transmigran_id,
                    'transmigran' => $r->transmigran?->nama_kepala_keluarga,
                    'tahun_mulai_menghuni' => $r->tahun_mulai_menghuni,
                    'tahun_selesai_menghuni' => $r->tahun_selesai_menghuni,
                    'alasan_keluar' => $r->alasan_keluar,
                    'keterangan' => $r->keterangan,
                ])
                ->all(),
            'berkasFotoRumah' => $rumah->berkas
                ->filter(fn ($b) => $b->pivot->peran === 'foto')
                ->sortBy(fn ($b) => $b->pivot->urutan)
                ->map(fn ($b) => ['nama_file' => $b->nama_file, 'keterangan' => $b->keterangan])
                ->values()
                ->all(),
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        // Rumah baru belum punya penghuni sebelumnya, jadi `alasan_keluar` tak dipakai.
        $data = $this->validasi($request);

        DB::transaction(function () use ($request, $data) {
            $rumah = OperasiRumah::buat($data);
            $this->lampirkanBerkas($request, $rumah);
        });

        return redirect()->route('rumah.index')->with('sukses', 'Data rumah tersimpan.');
    }

    public function perbarui(Request $request, int $id): RedirectResponse
    {
        $rumah = Rumah::findOrFail($id);
        CakupanDataSp::pastikanDapatDitulis($rumah);

        $data = $this->validasi($request, $rumah);
        $this->pastikanPenghuniDapatDitulis($data['transmigran_id'] ?? null);
        [$data, $alasanKeluar, $tahunMulai, $tahunSelesai] = $this->pisahkan($data);
        $this->tetapkanSp($data);

        $lama = $rumah->transmigran_id;
        $baru = $data['transmigran_id'] ?? null;

        DB::transaction(function () use ($request, $rumah, $data, $lama, $baru, $alasanKeluar, $tahunMulai, $tahunSelesai) {
            $rumah->update($data);

            if ((int) $baru !== (int) $lama) {
                // Baris terbuka ditutup satu per satu (bukan mass-update) agar
                // AuditLogObserver menangkap perubahannya.
                $rumah->riwayatPenghunian()
                    ->whereNull('tahun_selesai_menghuni')
                    ->get()
                    ->each(fn ($jejak) => $jejak->update([
                        'tahun_selesai_menghuni' => $tahunSelesai,
                        'alasan_keluar' => $alasanKeluar,
                    ]));

                if ($baru !== null) {
                    $rumah->riwayatPenghunian()->create([
                        'transmigran_id' => $baru,
                        'tahun_mulai_menghuni' => $tahunMulai,
                    ]);
                }
            }

            $this->lampirkanBerkas($request, $rumah);
        });

        return redirect()->route('rumah.detail', $id)->with('sukses', 'Perubahan data rumah tersimpan.');
    }

    public function hapus(int $id): RedirectResponse
    {
        $rumah = Rumah::findOrFail($id);
        CakupanDataSp::pastikanDapatDitulis($rumah);
        $rumah->delete();

        return redirect()->route('rumah.index')->with('sukses', 'Data rumah dihapus.');
    }

    private function lampirkanBerkas(Request $request, Rumah $rumah): void
    {
        $this->lekatkanBerkas($rumah, (array) $request->file('foto_rumah', []), 'rumah', 'foto');
        $this->lekatkanBerkas($rumah, array_filter([$request->file('dokumen_pendukung')]), 'rumah', 'pendukung');
    }

    /**
     * @return array<int, array{id_satuan_permukiman: int, nama: string}>
     */
    private function daftarSp(): array
    {
        return SatuanPermukiman::query()
            ->terlihatOlehPengguna()
            ->orderBy('id_satuan_permukiman')
            ->get(['id_satuan_permukiman', 'nama'])
            ->toArray();
    }

    /**
     * @return array<string, string>
     */
    private function opsiFilter(JenisDaftarPilihan $jenis): array
    {
        return DaftarPilihan::query()
            ->where('jenis', $jenis->value)
            ->orderBy('urutan')
            ->orderBy('id_daftar_pilihan')
            ->pluck('nilai', 'nilai')
            ->all();
    }

    /**
     * Memisahkan `alasan_keluar` (untuk baris riwayat) dan isian berkas dari
     * kolom `rumah`. Rumah tak berpenghuni tak boleh punya `transmigran_id`.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: string|null, 2: int|null, 3: int|null}
     */
    private function pisahkan(array $data): array
    {
        $alasanKeluar = $data['alasan_keluar'] ?? null;
        $tahunMulai = isset($data['tahun_mulai_menghuni']) ? (int) $data['tahun_mulai_menghuni'] : null;
        $tahunSelesai = isset($data['tahun_selesai_menghuni']) ? (int) $data['tahun_selesai_menghuni'] : null;

        unset(
            $data['alasan_keluar'],
            $data['tahun_mulai_menghuni'],
            $data['tahun_selesai_menghuni'],
            $data['foto_rumah'],
            $data['dokumen_pendukung'],
        );

        if (($data['status_hunian'] ?? null) === StatusHunian::TidakDihuni->value) {
            $data['transmigran_id'] = null;
        }

        return [$data, $alasanKeluar, $tahunMulai, $tahunSelesai];
    }

    private function tetapkanSp(array &$data): void
    {
        if (($data['transmigran_id'] ?? null) !== null) {
            $data['satuan_permukiman_id'] = Transmigran::findOrFail((int) $data['transmigran_id'])
                ->satuan_permukiman_id;
        }

        $sp = SatuanPermukiman::findOrFail((int) $data['satuan_permukiman_id']);
        CakupanDataSp::pastikanDapatDitulis($sp);
    }

    private function pastikanPenghuniDapatDitulis(mixed $transmigranId): void
    {
        if ($transmigranId !== null) {
            $penghuni = Transmigran::withoutGlobalScope(CakupanDataSp::class)
                ->findOrFail((int) $transmigranId);
            CakupanDataSp::pastikanDapatDitulis($penghuni);
        }
    }

    /**
     * Larik ber-kunci PERSIS satu baris `DummyData::rumah()` (+`catatan_hunian`
     * dan `dokumen_pendukung` yang dibaca form dan tab dokumentasi).
     *
     * @return array<string, mixed>
     */
    private function baris(Rumah $r): array
    {
        return [
            'id_rumah' => $r->id_rumah,
            'no_rumah' => $r->no_rumah,
            'satuan_permukiman' => $r->satuanPermukiman?->nama,
            'satuan_permukiman_id' => $r->satuan_permukiman_id,
            'transmigran_id' => $r->transmigran_id,
            'penghuni' => $r->penghuni?->nama_kepala_keluarga,
            'kondisi' => $r->kondisi,
            'status_hunian' => $r->status_hunian,
            'alasan_tidak_dihuni' => $r->alasan_tidak_dihuni,
            'catatan_hunian' => $r->catatan_hunian,
            'tahun_pembangunan' => $r->tahun_pembangunan === null ? null : (int) $r->tahun_pembangunan,
            'luas_bangunan' => $r->luas_bangunan === null ? null : (float) $r->luas_bangunan,
            'lintang' => $r->lintang === null ? null : (float) $r->lintang,
            'bujur' => $r->bujur === null ? null : (float) $r->bujur,
            'dokumen_pendukung' => $r->berkas->firstWhere('pivot.peran', 'pendukung')?->nama_file,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?Rumah $rumah = null): array
    {
        $penghuniBerubah = $rumah !== null
            && (int) $request->input('transmigran_id') !== (int) $rumah->transmigran_id;
        $tahunMulaiLama = $rumah?->riwayatPenghunian()
            ->whereNull('tahun_selesai_menghuni')
            ->value('tahun_mulai_menghuni');

        return $request->validate([
            'satuan_permukiman_id' => [
                Rule::requiredIf(fn () => blank($request->input('transmigran_id'))),
                'nullable', 'integer', Rule::exists('satuan_permukiman', 'id_satuan_permukiman')->whereNull('deleted_at'),
            ],
            'transmigran_id' => [
                Rule::requiredIf(fn () => $request->input('status_hunian') === StatusHunian::Dihuni->value),
                'nullable', 'integer',
                Rule::exists('transmigran', 'id_transmigran')->whereNull('deleted_at'),
                Rule::unique('rumah', 'transmigran_id')->ignore($rumah?->id_rumah, 'id_rumah'),
            ],
            'no_rumah' => ['nullable', 'string', 'max:50'],
            'kondisi' => ValidationRules::daftarPilihan(JenisDaftarPilihan::KondisiRumah, wajib: true, nilaiSaatIni: $rumah?->kondisi),
            'status_hunian' => ValidationRules::daftarPilihan(JenisDaftarPilihan::StatusHunian, wajib: true, nilaiSaatIni: $rumah?->status_hunian),
            'alasan_tidak_dihuni' => ['nullable', 'string', 'max:2000', 'required_if:status_hunian,Tidak Dihuni'],
            'catatan_hunian' => ['nullable', 'string', 'max:2000'],
            'alasan_keluar' => ['nullable', 'string', 'max:2000'],
            'tahun_mulai_menghuni' => [
                Rule::requiredIf(fn () => $request->input('status_hunian') === StatusHunian::Dihuni->value
                    && ($rumah === null || $penghuniBerubah)),
                ...ValidationRules::tahun(),
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    if ($value !== null
                        && $request->filled('tahun_selesai_menghuni')
                        && (int) $value < (int) $request->input('tahun_selesai_menghuni')) {
                        $fail('Tahun mulai menghuni tidak boleh sebelum tahun penghuni sebelumnya selesai.');
                    }

                    if ($value !== null
                        && $request->filled('tahun_pembangunan')
                        && (int) $value < (int) $request->input('tahun_pembangunan')) {
                        $fail('Tahun mulai menghuni tidak boleh sebelum tahun pembangunan rumah.');
                    }
                },
            ],
            'tahun_selesai_menghuni' => [
                Rule::requiredIf(fn () => $penghuniBerubah),
                ...ValidationRules::tahun(),
                function (string $attribute, mixed $value, \Closure $fail) use ($tahunMulaiLama): void {
                    if ($value !== null && $tahunMulaiLama !== null && (int) $value < (int) $tahunMulaiLama) {
                        $fail('Tahun selesai menghuni tidak boleh sebelum tahun mulai menghuni.');
                    }
                },
            ],
            'tahun_pembangunan' => ValidationRules::tahun(),
            'luas_bangunan' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'lintang' => ValidationRules::lintang(),
            'bujur' => ValidationRules::bujur(),
            'foto_rumah' => ['nullable', 'array'],
            'foto_rumah.*' => ValidationRules::foto(),
            'dokumen_pendukung' => ValidationRules::dokumen(),
        ], [
            'satuan_permukiman_id.required' => 'Satuan permukiman wajib dipilih.',
            'kondisi.required' => 'Kondisi rumah wajib dipilih.',
            'status_hunian.required' => 'Status hunian wajib dipilih.',
            'alasan_tidak_dihuni.required_if' => 'Alasan wajib diisi bila rumah tidak dihuni.',
            'transmigran_id.required' => 'Kepala keluarga penghuni wajib dipilih bila rumah dihuni.',
            'transmigran_id.unique' => 'Keluarga ini sudah menempati rumah lain.',
            'tahun_mulai_menghuni.required' => 'Tahun mulai menghuni wajib diisi.',
            'tahun_selesai_menghuni.required' => 'Tahun selesai menghuni wajib diisi saat penghuni diganti atau dikosongkan.',
        ] + ValidationRules::pesan());
    }
}
