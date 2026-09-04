<?php

namespace App\Http\Controllers;

use App\Models\Desa;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\Provinsi;
use App\Support\Paginasi;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Data master wilayah administratif: provinsi > kabupaten > kecamatan > desa
 * (Task 4.1). Menggantikan closure `routes/internal.php`.
 *
 * Empat tingkat dilayani SATU controller sebab strukturnya sama persis --
 * nama, kode, dan satu induk. Tingkat menentukan tabel sasaran beserta kolom
 * induk yang diminta; provinsi tak punya induk sama sekali.
 *
 * Provinsi (38) dan kabupaten (514) berasal dari data referensi nasional dan
 * pada praktiknya tidak disunting petugas; keduanya tetap dilayani di sini
 * supaya tidak ada tingkat yang diam-diam tak dapat dibetulkan.
 */
class WilayahController extends Controller
{
    /**
     * Peta tingkat -> [kelas model, kunci utama, kolom induk].
     *
     * Ditulis sekali di sini, bukan diulang pada tiap metode: menambah
     * tingkat baru cukup menyentuh satu tempat.
     *
     * @var array<string, array{kelas: class-string<Model>, kunci: string, induk: ?string, relasiInduk: ?string, tabelInduk: ?string, kunciInduk: ?string, turunan: array<string, string>}>
     */
    private const TINGKAT = [
        'provinsi' => [
            'kelas' => Provinsi::class, 'kunci' => 'id_provinsi',
            'induk' => null, 'relasiInduk' => null, 'tabelInduk' => null, 'kunciInduk' => null,
            'turunan' => ['kabupaten' => 'kabupaten/kota'],
        ],
        'kabupaten' => [
            'kelas' => Kabupaten::class, 'kunci' => 'id_kabupaten',
            'induk' => 'provinsi_id', 'relasiInduk' => 'provinsi', 'tabelInduk' => 'provinsi', 'kunciInduk' => 'id_provinsi',
            'turunan' => ['kecamatan' => 'kecamatan', 'kawasanTransmigrasi' => 'kawasan transmigrasi'],
        ],
        'kecamatan' => [
            'kelas' => Kecamatan::class, 'kunci' => 'id_kecamatan',
            'induk' => 'kabupaten_id', 'relasiInduk' => 'kabupaten', 'tabelInduk' => 'kabupaten', 'kunciInduk' => 'id_kabupaten',
            'turunan' => ['desa' => 'desa'],
        ],
        'desa' => [
            'kelas' => Desa::class, 'kunci' => 'id_desa',
            'induk' => 'kecamatan_id', 'relasiInduk' => 'kecamatan', 'tabelInduk' => 'kecamatan', 'kunciInduk' => 'id_kecamatan',
            'turunan' => ['satuanPermukiman' => 'satuan permukiman'],
        ],
    ];

    public function index(Request $request): View
    {
        $baris = $this->semuaBaris();
        $cacah = $baris->countBy(fn (array $b) => $b['tingkat'])->all();

        foreach (array_keys(self::TINGKAT) as $t) {
            $cacah[$t] ??= 0;
        }

        $filterTingkat = (string) $request->query('tingkat', '');
        $cari = trim((string) $request->query('cari', ''));

        if (! array_key_exists($filterTingkat, self::TINGKAT)) {
            $filterTingkat = '';
        }

        if ($filterTingkat !== '') {
            $baris = $baris->where('tingkat', $filterTingkat);
        }

        // Dicocokkan pada nama MAUPUN induk dan kode: petugas kerap mengingat
        // kabupatennya ketika nama kecamatannya sendiri sudah kabur.
        if ($cari !== '') {
            $kunci = mb_strtolower($cari);

            $baris = $baris->filter(fn (array $b) => str_contains(mb_strtolower($b['nama']), $kunci)
                || str_contains(mb_strtolower((string) $b['induk']), $kunci)
                || str_contains(mb_strtolower((string) $b['kode']), $kunci));
        }

        // Jumlah dihitung SEBELUM pemotongan halaman, supaya keterangan
        // "menampilkan sekian dari sekian" menyebut angka yang benar.
        $jumlah = $baris->count();

        $perHalaman = Paginasi::perHalaman($request);

        $halaman = max(1, (int) $request->query('page', 1));

        $barisHalaman = $baris->slice(($halaman - 1) * $perHalaman, $perHalaman)->values();

        // Mekanisme pemotongan larik TIDAK berubah (sudah benar sejak semula:
        // jumlah dihitung sebelum dipotong, `per_halaman` divalidasi ke
        // pilihan sah). `LengthAwarePaginator` di sini murni untuk MERENDER
        // tautan halaman lewat `x-sim.data-table` yang sama dipakai
        // controller lain (Fase 1, 2026-09-05) -- bukan pengganti mekanismenya.
        $paginator = new LengthAwarePaginator(
            $barisHalaman,
            $jumlah,
            $perHalaman,
            $halaman,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('pages.master.wilayah', [
            'title' => 'Data Master Wilayah',
            'baris' => $barisHalaman->all(),
            'paginator' => $paginator,
            'jumlahBaris' => $jumlah,
            'perHalaman' => $perHalaman,
            'cacahTingkat' => $cacah,
            'filterTingkat' => $filterTingkat,
            'cari' => $cari,
            'adaFilter' => $filterTingkat !== '' || $cari !== '',
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $peta = self::TINGKAT[$data['tingkat']];

        $atribut = ['nama' => $data['nama'], 'kode' => $data['kode'] ?? null];

        if ($peta['induk'] !== null) {
            $atribut[$peta['induk']] = $data[$peta['induk']];
        }

        $peta['kelas']::create($atribut);

        return redirect()->route('wilayah')->with('sukses', 'Data wilayah tersimpan.');
    }

    /**
     * Tingkat dibawa DI ALAMAT, bukan hanya di body.
     *
     * Kunci utama keempat tabel berdiri sendiri-sendiri, sehingga id 1 sah
     * sebagai kecamatan (Laen Manen) MAUPUN desa (Kapitan Meo). Alamat
     * `/wilayah/1` karena itu tidak pernah cukup untuk menunjuk satu baris,
     * dan penghapusan yang menebak tingkatnya akan membuang baris yang keliru
     * tanpa memerahkan apa pun.
     */
    public function perbarui(Request $request, string $tingkat, int $id): RedirectResponse
    {
        $peta = $this->peta($tingkat);
        $model = $peta['kelas']::findOrFail($id);

        $data = $this->validasi($request, $tingkat, $id);
        $atribut = ['nama' => $data['nama'], 'kode' => $data['kode'] ?? null];

        if ($peta['induk'] !== null) {
            $atribut[$peta['induk']] = $data[$peta['induk']];
        }

        $model->update($atribut);

        return redirect()->route('wilayah')->with('sukses', 'Perubahan data wilayah tersimpan.');
    }

    /**
     * Penghapusan DITOLAK bila wilayah masih menaungi turunan atau SP.
     *
     * Basis data sudah menahannya lewat FK RESTRICT, tetapi galat SQL mentah
     * tidak terbaca petugas. Diperiksa lebih dulu di sini supaya alasannya
     * tersampaikan dengan kalimat yang dapat ditindaklanjuti.
     */
    public function hapus(string $tingkat, int $id): RedirectResponse
    {
        $peta = $this->peta($tingkat);
        $model = $peta['kelas']::findOrFail($id);

        foreach ($peta['turunan'] as $relasi => $sebutan) {
            if ($model->{$relasi}()->exists()) {
                return back()->with('galat', "Wilayah ini masih menaungi {$sebutan} sehingga tidak dapat dihapus.");
            }
        }

        $model->delete();

        return redirect()->route('wilayah')->with('sukses', 'Data wilayah dihapus.');
    }

    /**
     * @return array{kelas: class-string<Model>, kunci: string, induk: ?string, tabel: string, turunan: array<string, string>}
     */
    private function peta(string $tingkat): array
    {
        abort_unless(array_key_exists($tingkat, self::TINGKAT), 404);

        return self::TINGKAT[$tingkat];
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?string $tingkat = null, ?int $id = null): array
    {
        // Pada simpan(), tingkat datang dari isian form; pada perbarui() ia
        // sudah dipastikan dari alamat sehingga tidak dapat diselundupkan lewat body.
        $aturan = ['nama' => ['required', 'string', 'max:100'], 'kode' => ['nullable', 'string', 'max:10']];

        if ($tingkat === null) {
            $aturan['tingkat'] = ['required', Rule::in(array_keys(self::TINGKAT))];
        }

        $peta = $tingkat === null ? null : $this->peta($tingkat);
        $tingkatDipakai = $tingkat ?? (string) $request->input('tingkat');

        if (array_key_exists($tingkatDipakai, self::TINGKAT)) {
            $peta ??= self::TINGKAT[$tingkatDipakai];

            // Nama provinsi UNIQUE di skema; ketiga tingkat lain tidak, sebab
            // nama desa yang sama sah pada kecamatan berbeda.
            if ($tingkatDipakai === 'provinsi') {
                $aturan['nama'][] = Rule::unique('provinsi', 'nama')->ignore($id, 'id_provinsi');
            }

            if ($peta['induk'] !== null) {
                $aturan[$peta['induk']] = [
                    'required', 'integer',
                    Rule::exists($peta['tabelInduk'], $peta['kunciInduk']),
                ];
            }
        }

        $data = $request->validate($aturan, [
            'nama.required' => 'Nama wilayah wajib diisi.',
            'nama.unique' => 'Nama provinsi ini sudah terdaftar.',
            'tingkat.in' => 'Tingkat wilayah tidak sah.',
        ] + ValidationRules::pesan());

        $data['tingkat'] = $tingkatDipakai;

        return $data;
    }

    /**
     * Keempat tingkat disatukan menjadi SATU daftar rata (2026-09-02),
     * dengan tingkat sebagai kolom sekaligus penyaring.
     *
     * Nama induk diambil lewat eager loading, bukan kueri per baris: daftar
     * ini memuat 500+ kabupaten sehingga membacanya satu per satu menghasilkan
     * ratusan kueri untuk satu halaman.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function semuaBaris(): Collection
    {
        $hasil = collect();

        foreach (self::TINGKAT as $tingkat => $peta) {
            $kueri = $peta['kelas']::query()->orderBy('nama');

            if ($peta['relasiInduk'] !== null) {
                $kueri->with($peta['relasiInduk']);
            }

            foreach ($kueri->get() as $baris) {
                $induk = $peta['relasiInduk'] === null
                    ? null
                    : $baris->{$peta['relasiInduk']}?->nama;

                // `asli` dipakai modal Ubah untuk mengisi ulang formnya,
                // sehingga nama kuncinya wajib sama dengan atribut `name=`
                // pada form-wilayah.blade.php.
                $asli = [
                    $peta['kunci'] => $baris->getKey(),
                    'nama' => $baris->nama,
                    'kode' => $baris->kode,
                ];

                if ($peta['induk'] !== null) {
                    $asli[$peta['induk']] = $baris->{$peta['induk']};
                }

                $hasil->push([
                    'id' => $baris->getKey(),
                    'tingkat' => $tingkat,
                    'nama' => $baris->nama,
                    'induk' => $induk,
                    'kode' => $baris->kode,
                    'asli' => $asli,
                ]);
            }
        }

        return $hasil;
    }
}
