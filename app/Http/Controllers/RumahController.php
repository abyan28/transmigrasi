<?php

namespace App\Http\Controllers;

use App\Enums\JenisReferensi;
use App\Enums\StatusHunian;
use App\Http\Controllers\Concerns\MenyimpanBerkas;
use App\Models\Rumah;
use App\Support\DummyData;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Modul Rumah dan Hunian (Task 5.3 + 5.4).
 *
 * Relasi rumah <-> KK satu-ke-satu, ditegakkan `UNIQUE (rumah.transmigran_id)`
 * di basis data (`rules.md` 6a.6). Pergantian penghuni TIDAK menimpa data lama:
 * baris `riwayat_penghunian` yang terbuka ditutup (`tanggal_keluar`) dan baris
 * baru dibuka (`rules.md` 6a.9). Penghuni sekarang dibaca halaman rincian dari
 * riwayat yang belum punya `tanggal_keluar`, bukan dari kolom -- keduanya
 * dijaga sepadan di sini.
 */
class RumahController extends Controller
{
    use MenyimpanBerkas;

    public function index(Request $request): View
    {
        $semua = $this->daftar();

        $cari = trim((string) $request->query('cari', ''));
        $filterSp = $request->query('sp');
        $filterKondisi = $request->query('kondisi');
        $filterHunian = $request->query('status_hunian');

        $baris = array_values(array_filter($semua, function (array $r) use ($cari, $filterSp, $filterKondisi, $filterHunian) {
            if ($cari !== '') {
                $cocok = str_contains(mb_strtolower((string) $r['no_rumah']), mb_strtolower($cari))
                    || str_contains(mb_strtolower((string) ($r['penghuni'] ?? '')), mb_strtolower($cari));

                if (! $cocok) {
                    return false;
                }
            }

            if ($filterSp && (string) $r['satuan_permukiman_id'] !== (string) $filterSp) {
                return false;
            }

            if ($filterKondisi && $r['kondisi'] !== $filterKondisi) {
                return false;
            }

            return ! ($filterHunian && $r['status_hunian'] !== $filterHunian);
        }));

        return view('pages.rumah.index', [
            'title' => 'Rumah dan Hunian',
            'semua' => $semua,
            'baris' => $baris,
            'cari' => $cari,
            'filterSp' => $filterSp,
            'filterKondisi' => $filterKondisi,
            'filterHunian' => $filterHunian,
            'adaFilter' => $cari !== '' || $filterSp || $filterKondisi || $filterHunian,
            'jumlahDihuni' => count(array_filter($semua, fn ($r) => $r['status_hunian'] === StatusHunian::Dihuni->value)),
            'jumlahRusak' => count(array_filter($semua, fn ($r) => $r['kondisi'] !== 'Tidak Rusak')),
            'daftarSp' => DummyData::satuanPermukiman(),
            'opsiFilterKondisiRumah' => DummyData::opsiFilterReferensi(JenisReferensi::KondisiRumah),
            'opsiFilterStatusHunian' => DummyData::opsiFilterReferensi(JenisReferensi::StatusHunian),
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
                    'tanggal_masuk' => $r->tanggal_masuk?->toDateString(),
                    'tanggal_keluar' => $r->tanggal_keluar?->toDateString(),
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
        [$data] = $this->pisahkan($this->validasi($request));

        DB::transaction(function () use ($request, $data) {
            $rumah = Rumah::create($data + ['uuid' => (string) Str::uuid()]);

            if ($rumah->transmigran_id !== null) {
                $rumah->riwayatPenghunian()->create([
                    'transmigran_id' => $rumah->transmigran_id,
                    'tanggal_masuk' => now()->toDateString(),
                ]);
            }

            $this->lampirkanBerkas($request, $rumah);
        });

        return redirect()->route('rumah.index')->with('sukses', 'Data rumah tersimpan.');
    }

    public function perbarui(Request $request, int $id): RedirectResponse
    {
        $rumah = Rumah::findOrFail($id);

        [$data, $alasanKeluar] = $this->pisahkan($this->validasi($request, $rumah));

        $lama = $rumah->transmigran_id;
        $baru = $data['transmigran_id'] ?? null;

        DB::transaction(function () use ($request, $rumah, $data, $lama, $baru, $alasanKeluar) {
            $rumah->update($data);

            if ((int) $baru !== (int) $lama) {
                // Baris terbuka ditutup satu per satu (bukan mass-update) agar
                // AuditLogObserver menangkap perubahannya.
                $rumah->riwayatPenghunian()
                    ->whereNull('tanggal_keluar')
                    ->get()
                    ->each(fn ($jejak) => $jejak->update([
                        'tanggal_keluar' => now()->toDateString(),
                        'alasan_keluar' => $alasanKeluar,
                    ]));

                if ($baru !== null) {
                    $rumah->riwayatPenghunian()->create([
                        'transmigran_id' => $baru,
                        'tanggal_masuk' => now()->toDateString(),
                    ]);
                }
            }

            $this->lampirkanBerkas($request, $rumah);
        });

        return redirect()->route('rumah.detail', $id)->with('sukses', 'Perubahan data rumah tersimpan.');
    }

    public function hapus(int $id): RedirectResponse
    {
        Rumah::findOrFail($id)->delete();

        return redirect()->route('rumah.index')->with('sukses', 'Data rumah dihapus.');
    }

    private function lampirkanBerkas(Request $request, Rumah $rumah): void
    {
        $this->lekatkanBerkas($rumah, (array) $request->file('foto_rumah', []), 'rumah', 'foto');
        $this->lekatkanBerkas($rumah, array_filter([$request->file('dokumen_pendukung')]), 'rumah', 'pendukung');
    }

    /**
     * Memisahkan `alasan_keluar` (untuk baris riwayat) dan isian berkas dari
     * kolom `rumah`. Rumah tak berpenghuni tak boleh punya `transmigran_id`.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: string|null}
     */
    private function pisahkan(array $data): array
    {
        $alasanKeluar = $data['alasan_keluar'] ?? null;

        unset($data['alasan_keluar'], $data['foto_rumah'], $data['dokumen_pendukung']);

        if (($data['status_hunian'] ?? null) === StatusHunian::TidakDihuni->value) {
            $data['transmigran_id'] = null;
        }

        return [$data, $alasanKeluar];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function daftar(): array
    {
        return Rumah::query()
            ->with(['satuanPermukiman', 'penghuni'])
            ->orderBy('id_rumah')
            ->get()
            ->map(fn (Rumah $r) => $this->baris($r))
            ->all();
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
        return $request->validate([
            'satuan_permukiman_id' => ['required', 'integer', Rule::exists('satuan_permukiman', 'id_satuan_permukiman')],
            'transmigran_id' => [
                'nullable', 'integer',
                Rule::exists('transmigran', 'id_transmigran'),
                Rule::unique('rumah', 'transmigran_id')->ignore($rumah?->id_rumah, 'id_rumah'),
            ],
            'no_rumah' => ['nullable', 'string', 'max:50'],
            'kondisi' => ValidationRules::referensi(JenisReferensi::KondisiRumah, wajib: true),
            'status_hunian' => ValidationRules::referensi(JenisReferensi::StatusHunian, wajib: true),
            'alasan_tidak_dihuni' => ['nullable', 'string', 'max:2000', 'required_if:status_hunian,Tidak Dihuni'],
            'catatan_hunian' => ['nullable', 'string', 'max:2000'],
            'alasan_keluar' => ['nullable', 'string', 'max:2000'],
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
            'transmigran_id.unique' => 'Keluarga ini sudah menempati rumah lain.',
        ] + ValidationRules::pesan());
    }
}
