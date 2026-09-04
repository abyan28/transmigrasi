<?php

namespace App\Http\Controllers;

use App\Enums\JenisReferensi;
use App\Enums\JenisSaprotan;
use App\Http\Controllers\Concerns\MenyimpanBerkas;
use App\Models\Saprotan;
use App\Models\SaprotanDistribusi;
use App\Support\DummyData;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Saprotan (Task 6.7) -- pola INDUK + DISTRIBUSI (Putaran 7).
 *
 * Baris `saprotan` mendeskripsikan BENDAnya (jenis, nama, jumlah total, satuan,
 * tahun anggaran). `komoditas_id` & `varietas` wajib HANYA bila `jenis = Benih`.
 * `saprotan_distribusi` = satu baris per poktan penerima (jumlah + tanggal
 * serah). `SUM(distribusi.jumlah) <= jumlah_total` ditegakkan di sini. Sisa
 * benih DITURUNKAN per baris distribusi (`jumlah` - Sigma `penanaman.volume_benih`
 * yang menunjuk baris itu), tidak disimpan. SP mengikuti poktan.
 *
 * foto barang -> FK tunggal `foto_berkas_id`; berita acara penyaluran -> FK
 * tunggal `berkas_id`.
 */
class SaprotanController extends Controller
{
    use MenyimpanBerkas;

    public function index(Request $request): View
    {
        $semua = $this->daftar();

        $cari = trim((string) $request->query('cari', ''));
        $filterSp = $request->query('sp');
        $filterJenis = $request->query('jenis');

        $baris = array_values(array_filter($semua, function (array $s) use ($cari, $filterSp, $filterJenis) {
            if ($cari !== '') {
                $poktanTeks = mb_strtolower(implode(' ', $s['poktan_penerima']));
                if (! str_contains(mb_strtolower($s['nama']), mb_strtolower($cari))
                    && ! str_contains($poktanTeks, mb_strtolower($cari))) {
                    return false;
                }
            }

            if ($filterSp && ! in_array((int) $filterSp, array_column($s['distribusi'], 'satuan_permukiman_id'), true)) {
                return false;
            }

            return ! ($filterJenis && $s['jenis'] !== $filterJenis);
        }));

        $belumTersalur = [];
        foreach ($semua as $s) {
            $belumTersalur[$s['id_saprotan']] = $s['jumlah_belum_tersalur'];
        }

        return view('pages.saprotan.index', [
            'title' => 'Saprotan',
            'semua' => $semua,
            'baris' => $baris,
            'cari' => $cari,
            'filterSp' => $filterSp,
            'filterJenis' => $filterJenis,
            'adaFilter' => $cari !== '' || $filterSp || $filterJenis,
            'jenisUnik' => array_values(array_unique(array_column($semua, 'jenis'))),
            'poktanPenerima' => count(array_unique(array_merge(
                [], ...array_map(fn ($s) => array_column($s['distribusi'], 'poktan_id'), $semua),
            ))),
            'belumTersalur' => $belumTersalur,
            'daftarSp' => DummyData::satuanPermukiman(),
        ]);
    }

    public function detail(int $id): View
    {
        $saprotan = Saprotan::with([
            'satuan', 'komoditas', 'foto', 'berkas',
            'distribusi.poktan.satuanPermukiman', 'distribusi.penanaman',
        ])->findOrFail($id);

        return view('pages.saprotan.detail', [
            'title' => $saprotan->nama,
            'data' => $this->baris($saprotan),
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $distribusi = $this->distribusiTerpilih($request, $data);

        DB::transaction(function () use ($request, $data, $distribusi) {
            $saprotan = Saprotan::create($this->kolomInduk($data));

            foreach ($distribusi as $poktanId => $baris) {
                $saprotan->distribusi()->create($baris + ['poktan_id' => $poktanId]);
            }

            $this->lampirkanBerkas($request, $saprotan);
        });

        return redirect()->route('saprotan.index')->with('sukses', 'Data saprotan tersimpan.');
    }

    public function perbarui(Request $request, int $id): RedirectResponse
    {
        $saprotan = Saprotan::findOrFail($id);

        $data = $this->validasi($request, $saprotan);
        $distribusi = $this->distribusiTerpilih($request, $data);

        DB::transaction(function () use ($request, $saprotan, $data, $distribusi) {
            $saprotan->update($this->kolomInduk($data));

            $idBaru = array_map('intval', array_keys($distribusi));

            $saprotan->distribusi()
                ->when($idBaru !== [], fn ($q) => $q->whereNotIn('poktan_id', $idBaru))
                ->delete();

            foreach ($distribusi as $poktanId => $baris) {
                $saprotan->distribusi()->updateOrCreate(['poktan_id' => $poktanId], $baris);
            }

            $this->lampirkanBerkas($request, $saprotan);
        });

        return redirect()->route('saprotan.detail', $id)->with('sukses', 'Perubahan data saprotan tersimpan.');
    }

    public function hapus(int $id): RedirectResponse
    {
        Saprotan::findOrFail($id)->delete();

        return redirect()->route('saprotan.index')->with('sukses', 'Data saprotan dihapus.');
    }

    private function lampirkanBerkas(Request $request, Saprotan $saprotan): void
    {
        $ubah = [];

        if ($request->hasFile('foto')) {
            $ubah['foto_berkas_id'] = $this->rekamBerkas($request->file('foto'), 'saprotan', (int) $saprotan->id_saprotan, 'foto')->id_berkas;
        }

        if ($request->hasFile('dokumen_pendukung')) {
            $ubah['berkas_id'] = $this->rekamBerkas($request->file('dokumen_pendukung'), 'saprotan', (int) $saprotan->id_saprotan, 'pendukung')->id_berkas;
        }

        if ($ubah !== []) {
            $saprotan->forceFill($ubah)->save();
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    private function distribusiTerpilih(Request $request, array $data): array
    {
        $terpilih = array_map('intval', (array) $request->input('poktan_id', []));
        $distribusi = (array) ($data['distribusi'] ?? []);

        $hasil = [];

        foreach ($terpilih as $poktanId) {
            $baris = $distribusi[$poktanId] ?? [];

            $hasil[$poktanId] = [
                'jumlah' => (float) ($baris['jumlah'] ?? 0),
                'tanggal_serah' => ($baris['tanggal_serah'] ?? null) ?: null,
            ];
        }

        return $hasil;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function kolomInduk(array $data): array
    {
        $benih = $data['jenis'] === JenisSaprotan::Benih->value;

        return [
            'jenis' => $data['jenis'],
            'nama' => $data['nama'],
            'komoditas_id' => $benih ? $data['komoditas_id'] : null,
            'varietas' => $benih ? $data['varietas'] : null,
            'jumlah_total' => $data['jumlah_total'],
            'satuan_id' => $data['satuan_id'],
            'jadwal_tanam' => $data['jadwal_tanam'] ?? null,
            'tahun_pengadaan' => $data['tahun_pengadaan'],
            'sumber_dana' => $data['sumber_dana'] ?? null,
            'keterangan' => $data['keterangan'] ?? null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function daftar(): array
    {
        return Saprotan::query()
            ->with([
                'satuan', 'komoditas',
                'distribusi.poktan.satuanPermukiman', 'distribusi.penanaman',
            ])
            ->orderBy('id_saprotan')
            ->get()
            ->map(fn (Saprotan $s) => $this->baris($s))
            ->all();
    }

    /**
     * Larik ber-kunci PERSIS satu baris `DummyData::saprotan()` mapped.
     *
     * @return array<string, mixed>
     */
    private function baris(Saprotan $s): array
    {
        $benih = $s->jenis === JenisSaprotan::Benih;

        $distribusi = $s->distribusi
            ->sortBy('id_saprotan_distribusi')
            ->map(function (SaprotanDistribusi $d) use ($benih) {
                $jumlah = (float) $d->jumlah;
                $terpakai = (float) $d->penanaman->sum('volume_benih');

                return [
                    'id_saprotan_distribusi' => $d->id_saprotan_distribusi,
                    'saprotan_id' => $d->saprotan_id,
                    'poktan_id' => $d->poktan_id,
                    'poktan' => $d->poktan?->nama,
                    'satuan_permukiman_id' => $d->poktan?->satuan_permukiman_id,
                    'satuan_permukiman' => $d->poktan?->satuanPermukiman?->nama,
                    'jumlah' => $jumlah,
                    'tanggal_serah' => $d->tanggal_serah?->toDateString(),
                    'keterangan' => $d->keterangan,
                    'sisa_benih' => $benih ? max(0.0, round($jumlah - $terpakai, 3)) : null,
                ];
            })
            ->values();

        $tersalur = round((float) $distribusi->sum('jumlah'), 3);

        return [
            'id_saprotan' => $s->id_saprotan,
            'jenis' => $s->jenis?->value,
            'nama' => $s->nama,
            'komoditas_id' => $s->komoditas_id,
            'komoditas' => $s->komoditas?->nama,
            'varietas' => $s->varietas,
            'jadwal_tanam' => $s->jadwal_tanam,
            'jumlah_total' => (float) $s->jumlah_total,
            'satuan_id' => $s->satuan_id,
            'satuan' => $s->satuan?->nama,
            'tahun_pengadaan' => $s->tahun_pengadaan === null ? null : (int) $s->tahun_pengadaan,
            'sumber_dana' => $s->sumber_dana,
            'keterangan' => $s->keterangan,
            'distribusi' => $distribusi->all(),
            'jumlah_tersalur' => $tersalur,
            'jumlah_belum_tersalur' => max(0.0, round((float) $s->jumlah_total - $tersalur, 3)),
            'poktan_penerima' => $distribusi->pluck('poktan')->filter()->unique()->values()->all(),
            'foto' => $s->foto?->nama_file,
            'dokumen_pendukung' => $s->berkas?->nama_file,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?Saprotan $saprotan = null): array
    {
        $benih = fn () => $request->input('jenis') === JenisSaprotan::Benih->value;

        return $request->validate([
            'jenis' => ['required', Rule::enum(JenisSaprotan::class)],
            'nama' => ['required', 'string', 'max:255'],
            'komoditas_id' => [
                'nullable', 'integer', Rule::exists('komoditas', 'id_komoditas'),
                Rule::requiredIf($benih),
            ],
            'varietas' => ['nullable', 'string', 'max:120', Rule::requiredIf($benih)],
            'jumlah_total' => ['required', 'numeric', 'gt:0', 'max:99999999', 'decimal:0,3'],
            'satuan_id' => ['required', 'integer', Rule::exists('satuan', 'id_satuan')],
            'jadwal_tanam' => ['nullable', 'date_format:Y-m'],
            'tahun_pengadaan' => ValidationRules::tahun(wajib: true),
            'sumber_dana' => ValidationRules::referensi(JenisReferensi::SumberDana),
            'keterangan' => ['nullable', 'string', 'max:1000'],

            'poktan_id' => ['nullable', 'array'],
            'poktan_id.*' => ['integer', Rule::exists('poktan', 'id_poktan')],

            // Invarian Putaran 7: Sigma distribusi <= jumlah total.
            'distribusi' => ['nullable', 'array', function (string $atribut, mixed $nilai, callable $gagal) use ($request) {
                $total = (float) $request->input('jumlah_total', 0);
                $terpilih = array_map('intval', (array) $request->input('poktan_id', []));

                $tersalur = 0.0;
                foreach ($terpilih as $poktanId) {
                    $tersalur += (float) ($nilai[$poktanId]['jumlah'] ?? 0);
                }

                if (round($tersalur - $total, 3) > 0) {
                    $gagal('Jumlah seluruh distribusi ('.$tersalur.') melebihi jumlah total ('.$total.').');
                }
            }],
            'distribusi.*.jumlah' => ['required', 'numeric', 'min:0', 'max:99999999', 'decimal:0,3'],
            'distribusi.*.tanggal_serah' => ['nullable', 'date', 'before_or_equal:today'],

            'foto' => ValidationRules::foto(),
            'dokumen_pendukung' => ValidationRules::dokumen(),
        ], [
            'jenis.required' => 'Jenis saprotan wajib dipilih.',
            'nama.required' => 'Nama sarana wajib diisi.',
            'komoditas_id.required' => 'Komoditas wajib dipilih untuk jenis Benih.',
            'varietas.required' => 'Varietas wajib diisi untuk jenis Benih.',
            'jumlah_total.required' => 'Jumlah total wajib diisi.',
            'satuan_id.required' => 'Satuan wajib dipilih.',
            'tahun_pengadaan.required' => 'Tahun anggaran pengadaan wajib diisi.',
        ] + ValidationRules::pesan());
    }
}
