<?php

namespace App\Http\Controllers;

use App\Enums\AsalWakilPoktan;
use App\Enums\JenisReferensi;
use App\Enums\JenisSaprotan;
use App\Enums\StatusKeaktifanAnggota;
use App\Http\Controllers\Concerns\MenyimpanBerkas;
use App\Models\AlsintanDistribusi;
use App\Models\AnggotaPoktan;
use App\Models\Lahan;
use App\Models\Poktan;
use App\Models\SaprotanDistribusi;
use App\Support\DummyData;
use App\Support\RekapLahan;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Modul Kelompok Tani (Task 6.4).
 *
 * KETUA punya TIGA asal-usul (`asal_ketua`, `rules.md` 7a.2a): Kepala Keluarga
 * & Anggota Keluarga membaca identitas lewat relasi; Bukan Transmigran mengetik
 * nama/NIK + luas lahan (satu-satunya jalur yang mengetik luas). `jumlah_anggota`
 * dan luas lahan kelompok DITURUNKAN, tidak disimpan (`erd.md` 7.3).
 *
 * `slug` otomatis (`BerslugOtomatis`). SK pembentukan = FK tunggal `berkas_id`.
 * Manajemen anggota penuh lewat `AnggotaPoktanController`; langkah 3 form ini
 * hanya menambah anggota baru bersama defaultnya.
 */
class PoktanController extends Controller
{
    use MenyimpanBerkas;

    public function index(Request $request): View
    {
        $semua = $this->daftar();
        $anggota = $this->semuaAnggota();

        $cari = trim((string) $request->query('cari', ''));
        $filterSp = $request->query('sp');

        $baris = array_values(array_filter($semua, function (array $p) use ($cari, $filterSp) {
            if ($cari !== ''
                && ! str_contains(mb_strtolower($p['nama']), mb_strtolower($cari))
                && ! str_contains(mb_strtolower((string) $p['nama_ketua']), mb_strtolower($cari))) {
                return false;
            }

            return ! ($filterSp && (string) $p['satuan_permukiman_id'] !== (string) $filterSp);
        }));

        return view('pages.poktan.index', [
            'title' => 'Kelompok Tani',
            'semua' => $semua,
            'anggota' => $anggota,
            'baris' => $baris,
            'cari' => $cari,
            'filterSp' => $filterSp,
            'adaFilter' => $cari !== '' || $filterSp,
            'totalAnggota' => array_sum(array_column($semua, 'jumlah_anggota')),
            'anggotaAktif' => count(array_filter($anggota, fn ($a) => $a['status'] === StatusKeaktifanAnggota::Aktif->value)),
            'daftarSp' => DummyData::satuanPermukiman(),
        ]);
    }

    public function detail(int $id): View
    {
        $poktan = Poktan::with([
            'satuanPermukiman', 'ketuaTransmigran', 'ketuaAnggotaKeluarga', 'berkas',
            'anggota.transmigran',
        ])->findOrFail($id);

        $data = $this->baris($poktan);
        $anggota = $poktan->anggota
            ->sortBy('id_anggota_poktan')
            ->map(fn (AnggotaPoktan $a) => $this->barisAnggota($a))
            ->values()
            ->all();

        $ketua = $this->identitasKetua($poktan);

        $namaKkWakil = [];
        foreach ($poktan->anggota as $a) {
            if ($a->asal_wakil !== AsalWakilPoktan::KepalaKeluarga) {
                $namaKkWakil[$a->transmigran_id] = $a->transmigran?->nama_kepala_keluarga ?? '-';
            }
        }

        $lahanKetua = $ketua['asal']->dariKeluargaTransmigran()
            ? $this->rekapLahanKeluarga($poktan->ketua_transmigran_id)
            : ['kering' => (float) ($poktan->luas_kering_ketua ?? 0), 'basah' => (float) ($poktan->luas_basah_ketua ?? 0)];

        return view('pages.poktan.detail', [
            'title' => $poktan->nama,
            'data' => $data,
            'anggota' => $anggota,
            'alsintan' => $this->alsintanPoktan($id),
            'saprotan' => $this->saprotanPoktan($id),
            'aktif' => count(array_filter($anggota, fn ($a) => $a['status'] === StatusKeaktifanAnggota::Aktif->value)),
            'ketua' => $ketua,
            'keluargaKetua' => $poktan->ketuaTransmigran === null ? null : [
                'nama_kepala_keluarga' => $poktan->ketuaTransmigran->nama_kepala_keluarga,
            ],
            'namaKkWakil' => $namaKkWakil,
            'lahanKetua' => $lahanKetua,
            'luasKelompokKering' => array_sum(array_column($anggota, 'luas_kering')),
            'luasKelompokBasah' => array_sum(array_column($anggota, 'luas_basah')),
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        [$data, $anggota, $disunting] = $this->pisahkan($this->validasi($request));

        DB::transaction(function () use ($request, $data, $anggota, $disunting) {
            $poktan = Poktan::create($data);

            if ($disunting) {
                $this->tambahAnggotaBaru($poktan, $anggota);
            }

            $this->lampirkanSk($request, $poktan);
        });

        return redirect()->route('poktan.index')->with('sukses', 'Data kelompok tani tersimpan.');
    }

    public function perbarui(Request $request, int $id): RedirectResponse
    {
        $poktan = Poktan::findOrFail($id);

        [$data, $anggota, $disunting] = $this->pisahkan($this->validasi($request, $poktan));

        DB::transaction(function () use ($request, $poktan, $data, $anggota, $disunting) {
            $poktan->update($data);

            if ($disunting) {
                $this->tambahAnggotaBaru($poktan, $anggota);
            }

            $this->lampirkanSk($request, $poktan);
        });

        return redirect()->route('poktan.detail', $id)->with('sukses', 'Perubahan profil kelompok tani tersimpan.');
    }

    public function hapus(int $id): RedirectResponse
    {
        Poktan::findOrFail($id)->delete();

        return redirect()->route('poktan.index')->with('sukses', 'Data kelompok tani dihapus.');
    }

    private function lampirkanSk(Request $request, Poktan $poktan): void
    {
        if (! $request->hasFile('dokumen_pendukung')) {
            return;
        }

        // SK poktan = FK TUNGGAL (bukan pivot). Simpan berkasnya lalu tunjuk.
        $berkas = $this->rekamBerkas($request->file('dokumen_pendukung'), 'poktan', (int) $poktan->id_poktan, 'sk');

        $poktan->forceFill(['berkas_id' => $berkas->id_berkas])->save();
    }

    /**
     * Menambah anggota BARU dari langkah 3 form. Baris yang sudah menjadi
     * anggota poktan ini dilewati; manajemen status lewat AnggotaPoktanController.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function tambahAnggotaBaru(Poktan $poktan, array $rows): void
    {
        $sudah = $poktan->anggota()->pluck('transmigran_id')->all();

        foreach ($rows as $row) {
            $transmigranId = (int) ($row['transmigran_id'] ?? 0);

            if ($transmigranId === 0 || in_array($transmigranId, $sudah, true)) {
                continue;
            }

            $poktan->anggota()->create([
                'transmigran_id' => $transmigranId,
                'asal_wakil' => AsalWakilPoktan::KepalaKeluarga->value,
                'jabatan' => $row['jabatan'] ?? 'Anggota',
                'keterangan' => $row['keterangan'] ?? null,
                'tanggal_masuk' => now()->toDateString(),
                'status' => StatusKeaktifanAnggota::Aktif->value,
            ]);

            $sudah[] = $transmigranId;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: array<int, array<string, mixed>>, 2: bool}
     */
    private function pisahkan(array $data): array
    {
        $anggota = $data['anggota'] ?? [];
        $disunting = ($data['_anggota_disunting'] ?? null) == '1';

        unset($data['anggota'], $data['_anggota_disunting'], $data['dokumen_pendukung']);

        // Kolom yang hanya berlaku pada satu jalur ketua dibersihkan agar tidak
        // menyimpan sisa dari cabang form yang tersembunyi.
        $asal = AsalWakilPoktan::from($data['asal_ketua']);

        if ($asal !== AsalWakilPoktan::BukanTransmigran) {
            $data['nama_ketua'] = null;
            $data['nik_ketua'] = null;
            $data['luas_kering_ketua'] = null;
            $data['luas_basah_ketua'] = null;
        } else {
            $data['ketua_transmigran_id'] = null;
        }

        if ($asal !== AsalWakilPoktan::AnggotaKeluarga) {
            $data['ketua_anggota_keluarga_id'] = null;
        }

        return [$data, $anggota, $disunting];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function daftar(): array
    {
        return Poktan::query()
            ->with(['satuanPermukiman', 'ketuaTransmigran', 'ketuaAnggotaKeluarga', 'berkas', 'anggota'])
            ->orderBy('id_poktan')
            ->get()
            ->map(fn (Poktan $p) => $this->baris($p))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function semuaAnggota(): array
    {
        return AnggotaPoktan::query()
            ->with(['transmigran', 'anggotaKeluarga', 'poktan'])
            ->orderBy('id_anggota_poktan')
            ->get()
            ->map(fn (AnggotaPoktan $a) => $this->barisAnggota($a))
            ->all();
    }

    /**
     * Larik ber-kunci PERSIS satu baris `DummyData::poktan()`.
     *
     * @return array<string, mixed>
     */
    private function baris(Poktan $p): array
    {
        $ketua = $this->identitasKetua($p);
        $kekuatan = $this->kekuatanPoktan($p);

        return [
            'id_poktan' => $p->id_poktan,
            'nama' => $p->nama,
            'satuan_permukiman' => $p->satuanPermukiman?->nama,
            'satuan_permukiman_id' => $p->satuan_permukiman_id,
            'asal_ketua' => $p->asal_ketua->value,
            'ketua_transmigran_id' => $p->ketua_transmigran_id,
            'ketua_anggota_keluarga_id' => $p->ketua_anggota_keluarga_id,
            'nama_ketua' => $ketua['nama'],
            'nik_ketua' => $ketua['nik'] === '-' ? null : $ketua['nik'],
            'hubungan_ketua' => $ketua['hubungan'],
            'telepon_ketua' => $p->telepon_ketua,
            'email_ketua' => $p->email_ketua,
            'alamat_ketua' => $p->alamat_ketua,
            'tahun_berdiri' => $p->tahun_berdiri === null ? null : (int) $p->tahun_berdiri,
            'jumlah_anggota' => $kekuatan['jumlah_anggota'],
            'luas_kering_ketua' => $p->luas_kering_ketua === null ? null : (float) $p->luas_kering_ketua,
            'luas_basah_ketua' => $p->luas_basah_ketua === null ? null : (float) $p->luas_basah_ketua,
            'lintang' => $p->lintang === null ? null : (float) $p->lintang,
            'bujur' => $p->bujur === null ? null : (float) $p->bujur,
            'keterangan' => $p->keterangan,
            'dokumen_pendukung' => $p->berkas?->nama_file,
        ];
    }

    /**
     * Larik ber-kunci PERSIS satu baris `DummyData::anggotaPoktan()` mapped.
     *
     * @return array<string, mixed>
     */
    private function barisAnggota(AnggotaPoktan $a): array
    {
        $identitas = $this->identitasWakilAnggota($a);
        $lahan = $this->rekapLahanKeluarga($a->transmigran_id);

        return [
            'id_anggota_poktan' => $a->id_anggota_poktan,
            'poktan_id' => $a->poktan_id,
            'poktan' => $a->poktan?->nama,
            'transmigran_id' => $a->transmigran_id,
            'asal_wakil' => $a->asal_wakil->value,
            'anggota_keluarga_id' => $a->anggota_keluarga_id,
            'telepon_wakil' => $a->telepon_wakil,
            'jabatan' => $a->jabatan,
            'tanggal_masuk' => $a->tanggal_masuk?->toDateString(),
            'tanggal_keluar' => $a->tanggal_keluar?->toDateString(),
            'status' => $a->status->value,
            'alasan_keluar' => $a->alasan_keluar,
            'keterangan' => $a->keterangan,
            'nama' => $identitas['nama'],
            'nik' => $identitas['nik'],
            'telepon' => $identitas['telepon'],
            'hubungan_wakil' => $identitas['hubungan'],
            'luas_kering' => $lahan['kering'],
            'luas_basah' => $lahan['basah'],
            'lintang' => $lahan['lintang'],
            'bujur' => $lahan['bujur'],
        ];
    }

    /**
     * Identitas ketua dari jalur mana pun (`rules.md` 7a.2a).
     *
     * @return array{nama: string, nik: string, telepon: string|null, hubungan: string|null, asal: AsalWakilPoktan}
     */
    private function identitasKetua(Poktan $p): array
    {
        $asal = $p->asal_ketua;

        if ($asal === AsalWakilPoktan::KepalaKeluarga && $p->ketuaTransmigran !== null) {
            return [
                'nama' => $p->ketuaTransmigran->nama_kepala_keluarga,
                'nik' => $p->ketuaTransmigran->nik,
                'telepon' => $p->telepon_ketua ?: $p->ketuaTransmigran->telepon,
                'hubungan' => null,
                'asal' => $asal,
            ];
        }

        if ($asal === AsalWakilPoktan::AnggotaKeluarga && $p->ketuaAnggotaKeluarga !== null) {
            $ak = $p->ketuaAnggotaKeluarga;

            return [
                'nama' => $ak->nama_lengkap,
                'nik' => $ak->nik ?? '-',
                'telepon' => $ak->telepon ?: $p->ketuaTransmigran?->telepon,
                'hubungan' => $ak->hubungan->value,
                'asal' => $asal,
            ];
        }

        return [
            'nama' => $p->nama_ketua ?? '-',
            'nik' => $p->nik_ketua ?? '-',
            'telepon' => $p->telepon_ketua,
            'hubungan' => null,
            'asal' => $asal,
        ];
    }

    /**
     * @return array{nama: string, nik: string, telepon: string|null, hubungan: string|null}
     */
    private function identitasWakilAnggota(AnggotaPoktan $a): array
    {
        if ($a->asal_wakil === AsalWakilPoktan::AnggotaKeluarga && $a->anggotaKeluarga !== null) {
            $ak = $a->anggotaKeluarga;

            return [
                'nama' => $ak->nama_lengkap,
                'nik' => $ak->nik ?? '-',
                'telepon' => $a->telepon_wakil ?: ($ak->telepon ?: $a->transmigran?->telepon),
                'hubungan' => $ak->hubungan->value,
            ];
        }

        return [
            'nama' => $a->transmigran?->nama_kepala_keluarga ?? '-',
            'nik' => $a->transmigran?->nik ?? '-',
            'telepon' => $a->telepon_wakil ?: $a->transmigran?->telepon,
            'hubungan' => null,
        ];
    }

    /**
     * Cacah anggota aktif + luas lahan kelompok, seluruhnya diturunkan.
     * Ketua ikut dihitung sekali bila belum terdaftar sebagai anggota aktif.
     *
     * @return array{jumlah_anggota: int, luas_kering: float, luas_basah: float, luas_total: float}
     */
    private function kekuatanPoktan(Poktan $p): array
    {
        $aktif = $p->anggota->filter(fn (AnggotaPoktan $a) => $a->status === StatusKeaktifanAnggota::Aktif);

        $kering = 0.0;
        $basah = 0.0;

        foreach ($aktif as $a) {
            $lahan = $this->rekapLahanKeluarga($a->transmigran_id);
            $kering += $lahan['kering'];
            $basah += $lahan['basah'];
        }

        $ketuaTerhitung = $p->ketua_transmigran_id !== null
            && $aktif->contains('transmigran_id', $p->ketua_transmigran_id);

        if (! $ketuaTerhitung) {
            $lahanKetua = $p->asal_ketua->dariKeluargaTransmigran()
                ? $this->rekapLahanKeluarga($p->ketua_transmigran_id)
                : ['kering' => (float) ($p->luas_kering_ketua ?? 0), 'basah' => (float) ($p->luas_basah_ketua ?? 0)];

            $kering += $lahanKetua['kering'];
            $basah += $lahanKetua['basah'];
        }

        return [
            'jumlah_anggota' => $aktif->count() + ($ketuaTerhitung ? 0 : 1),
            'luas_kering' => round($kering, 2),
            'luas_basah' => round($basah, 2),
            'luas_total' => round($kering + $basah, 2),
        ];
    }

    /**
     * @return array{kering: float, basah: float, total: float, lintang: float|null, bujur: float|null, jumlah_bidang: int}
     */
    private function rekapLahanKeluarga(?int $transmigranId): array
    {
        return RekapLahan::keluarga(
            $transmigranId === null ? null : Lahan::where('transmigran_id', $transmigranId)->first(),
        );
    }

    /**
     * Distribusi alsintan yang diterima poktan ini (Task 6.6, ber-Eloquent).
     *
     * @return array<int, array<string, mixed>>
     */
    private function alsintanPoktan(int $poktanId): array
    {
        return AlsintanDistribusi::query()
            ->with('alsintan')
            ->where('poktan_id', $poktanId)
            ->orderBy('id_alsintan_distribusi')
            ->get()
            ->map(fn (AlsintanDistribusi $d) => [
                'id_alsintan' => $d->alsintan_id,
                'id_alsintan_distribusi' => $d->id_alsintan_distribusi,
                'jenis_alsintan' => $d->alsintan?->jenis_alsintan,
                'nama_alat' => $d->alsintan?->nama_alat,
                'tahun_pengadaan' => $d->alsintan?->tahun_pengadaan,
                'sumber_dana' => $d->alsintan?->sumber_dana,
                'jumlah' => (int) $d->jumlah,
                'kondisi' => $d->kondisi,
            ])
            ->all();
    }

    /**
     * Distribusi saprotan yang diterima poktan ini (Task 6.7, ber-Eloquent).
     * Sisa benih diturunkan per baris (jatah - pemakaian penanaman baris itu).
     *
     * @return array<int, array<string, mixed>>
     */
    private function saprotanPoktan(int $poktanId): array
    {
        return SaprotanDistribusi::query()
            ->with(['saprotan.satuan', 'penanaman'])
            ->where('poktan_id', $poktanId)
            ->orderBy('id_saprotan_distribusi')
            ->get()
            ->map(function (SaprotanDistribusi $d) {
                $benih = $d->saprotan?->jenis === JenisSaprotan::Benih;
                $jumlah = (float) $d->jumlah;

                return [
                    'saprotan_id' => $d->saprotan_id,
                    'jenis' => $d->saprotan?->jenis?->value,
                    'nama' => $d->saprotan?->nama,
                    'jumlah' => $jumlah,
                    'satuan' => $d->saprotan?->satuan?->nama,
                    'tahun_pengadaan' => $d->saprotan?->tahun_pengadaan,
                    'sisa_benih' => $benih
                        ? max(0.0, round($jumlah - (float) $d->penanaman->sum('volume_benih'), 3))
                        : null,
                ];
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?Poktan $poktan = null): array
    {
        $id = $poktan?->id_poktan;

        return $request->validate([
            'satuan_permukiman_id' => ['required', 'integer', Rule::exists('satuan_permukiman', 'id_satuan_permukiman')],
            'nama' => [
                'required', 'string', 'max:255',
                Rule::unique('poktan', 'nama')->ignore($id, 'id_poktan'),
            ],
            'tahun_berdiri' => ['nullable', 'integer', 'min:1950', 'max:'.date('Y')],
            'alamat_ketua' => ['nullable', 'string', 'max:255'],
            'lintang' => ValidationRules::lintang(),
            'bujur' => ValidationRules::bujur(),

            'asal_ketua' => ['required', Rule::enum(AsalWakilPoktan::class)],
            'ketua_transmigran_id' => [
                'nullable', 'integer', Rule::exists('transmigran', 'id_transmigran'),
                Rule::requiredIf(fn () => $request->input('asal_ketua') !== AsalWakilPoktan::BukanTransmigran->value),
            ],
            'ketua_anggota_keluarga_id' => [
                'nullable', 'integer', Rule::exists('anggota_keluarga', 'id_anggota_keluarga'),
                Rule::requiredIf(fn () => $request->input('asal_ketua') === AsalWakilPoktan::AnggotaKeluarga->value),
            ],
            'nama_ketua' => [
                'nullable', 'string', 'max:255',
                Rule::requiredIf(fn () => $request->input('asal_ketua') === AsalWakilPoktan::BukanTransmigran->value),
            ],
            'nik_ketua' => [
                'nullable', 'digits:16',
                Rule::requiredIf(fn () => $request->input('asal_ketua') === AsalWakilPoktan::BukanTransmigran->value),
            ],
            'telepon_ketua' => ['nullable', 'string', 'max:20'],
            'email_ketua' => ['nullable', 'email:rfc', 'max:255'],
            'luas_kering_ketua' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'luas_basah_ketua' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],

            'keterangan' => ['nullable', 'string', 'max:1000'],
            'dokumen_pendukung' => ValidationRules::dokumen(),

            '_anggota_disunting' => ['nullable', 'string'],
            'anggota' => ['nullable', 'array'],
            'anggota.*.transmigran_id' => ['required', 'integer', Rule::exists('transmigran', 'id_transmigran')],
            'anggota.*.jabatan' => ValidationRules::referensi(JenisReferensi::JabatanAnggotaPoktan, wajib: true),
            'anggota.*.keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'satuan_permukiman_id.required' => 'Satuan permukiman wajib dipilih.',
            'nama.required' => 'Nama kelompok tani wajib diisi.',
            'nama.unique' => 'Nama kelompok tani ini sudah dipakai.',
            'asal_ketua.required' => 'Asal-usul ketua wajib dipilih.',
            'ketua_transmigran_id.required' => 'Keluarga yang diwakili ketua wajib dipilih.',
            'ketua_anggota_keluarga_id.required' => 'Anggota keluarga yang menjadi ketua wajib dipilih.',
            'nama_ketua.required' => 'Nama ketua wajib diisi.',
            'nik_ketua.required' => 'NIK ketua wajib diisi.',
        ] + ValidationRules::pesan());
    }
}
